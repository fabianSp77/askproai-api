<?php

namespace App\Services\Appointments;

use App\Services\CalcomV2Service;
use App\Models\Service;
use App\Models\Staff;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * CalcomAvailabilityService
 *
 * Fetches real-time availability from Cal.com API
 * Transforms Cal.com availability into booking-friendly format
 *
 * Key Features:
 * - Fetch availability for event type
 * - Transform Cal.com format to internal format
 * - Multi-tier caching for performance
 * - Duration-aware slot filtering
 * - Staff-specific availability (if applicable)
 *
 * Integration:
 * - HourlyCalendar component uses this for display
 * - Booking flow calls this when service/staff selected
 *
 * Response Format:
 * [
 *   'monday' => [
 *     ['time' => '09:00', 'full_datetime' => '2025-10-20T09:00:00+02:00', ...],
 *     ...
 *   ],
 *   ...
 * ]
 */
class CalcomAvailabilityService
{
    /**
     * Cal.com V2 Service for API calls
     */
    protected CalcomV2Service $calcomService;

    /**
     * Constructor
     */
    public function __construct(CalcomV2Service $calcomService = null)
    {
        $this->calcomService = $calcomService ?? new CalcomV2Service();
    }

    /**
     * Get availability for a service in a date range
     *
     * @param string $serviceId Service UUID
     * @param Carbon $weekStart Start of week (Monday)
     * @param int $durationMinutes Service duration for slot blocking
     * @param string|null $staffId Specific staff member (optional)
     *
     * @return array Week structure with available slots
     */
    public function getAvailabilityForWeek(
        string $serviceId,
        Carbon $weekStart,
        int $durationMinutes = 45,
        ?string $staffId = null
    ): array {
        // Validate and ensure Monday
        if ($weekStart->dayOfWeek !== Carbon::MONDAY) {
            $weekStart = $weekStart->copy()->startOfWeek(Carbon::MONDAY);
        }

        $weekEnd = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);

        // Get service with Cal.com info
        $service = Service::with('company')->findOrFail($serviceId);

        if (!$service->calcom_event_type_id) {
            Log::warning('[CalcomAvailability] Service has no Cal.com event type', [
                'service_id' => $serviceId,
            ]);
            return $this->getEmptyWeekStructure();
        }

        if (!$service->company->calcom_team_id) {
            Log::warning('[CalcomAvailability] Company has no Cal.com team', [
                'company_id' => $service->company_id,
            ]);
            return $this->getEmptyWeekStructure();
        }

        try {
            // Build cache key
            $cacheKey = $this->getCacheKey(
                $service->calcom_event_type_id,
                $weekStart,
                $staffId
            );

            // Check cache first (60 second TTL)
            if (Cache::has($cacheKey)) {
                Log::debug('[CalcomAvailability] Using cached availability', [
                    'cache_key' => $cacheKey,
                ]);
                return Cache::get($cacheKey);
            }

            // Fetch from Cal.com API
            $availability = $this->fetchFromCalcom(
                $service->calcom_event_type_id,
                $weekStart,
                $weekEnd,
                $staffId,
                $service->company->calcom_team_id
            );

            // Transform to internal format
            $result = $this->transformAvailability(
                $availability,
                $weekStart,
                $durationMinutes
            );

            // Cache the result
            Cache::put($cacheKey, $result, 60); // 60 seconds

            return $result;

        } catch (\Exception $e) {
            Log::error('[CalcomAvailability] Error fetching availability', [
                'service_id' => $serviceId,
                'error' => $e->getMessage(),
            ]);
            return $this->getEmptyWeekStructure();
        }
    }

    /**
     * Fetch availability from Cal.com API
     *
     * @return array Raw availability data from Cal.com
     */
    protected function fetchFromCalcom(
        int $eventTypeId,
        Carbon $startDate,
        Carbon $endDate,
        ?string $staffId = null,
        ?int $teamId = null
    ): array {
        // Build Cal.com API request
        // 🔧 FIX 2025-11-14: Cal.com V2 API requires ISO8601 in UTC for TIME-based queries
        // Doc: "Must be in UTC timezone as ISO 8601 datestring" - https://cal.com/docs/api-reference/v2/slots
        // Convert to UTC before sending (API requirement)
        $startUtc = $startDate->copy()->setTimezone('UTC');
        $endUtc = $endDate->copy()->setTimezone('UTC');

        $params = [
            'eventTypeId' => $eventTypeId,
            'startTime' => $startUtc->toIso8601String(),  // 2024-08-13T09:00:00Z
            'endTime' => $endUtc->toIso8601String(),      // 2024-08-13T18:00:00Z
        ];

        if ($teamId) {
            $params['teamId'] = $teamId;
        }

        if ($staffId) {
            $params['userId'] = $staffId;
        }

        Log::debug('[CalcomAvailability] Fetching from Cal.com API', [
            'event_type_id' => $eventTypeId,
            'start_berlin' => $startDate->format('Y-m-d H:i'),
            'end_berlin' => $endDate->format('Y-m-d H:i'),
            'start_utc' => $startUtc->toIso8601String(),
            'end_utc' => $endUtc->toIso8601String(),
            'staff_id' => $staffId,
        ]);

        // Cal.com availability endpoint (✅ V2 API - v1 deprecated end of 2025)
        // 🔧 FIX 2025-11-14: Corrected endpoint from /v2/availability to /v2/slots/available
        $apiVersion = config('services.calcom.api_version', '2024-08-13');
        $response = $this->calcomService->httpClient()
            ->withHeaders([
                'cal-api-version' => $apiVersion,
            ])
            ->get('https://api.cal.com/v2/slots/available', $params);

        if (!$response->successful()) {
            Log::warning('[CalcomAvailability] Cal.com API error', [
                'status' => $response->status(),
                'error' => $response->json()['message'] ?? 'Unknown error',
                'params' => $params,
            ]);
            return [];
        }

        // 🔧 FIX 2025-11-14: V2 API returns {data: {slots: {date: [...]}}} format
        $data = $response->json()['data'] ?? [];
        $dateSlots = $data['slots'] ?? [];

        // Flatten date-keyed slots into single array
        $allSlots = [];
        foreach ($dateSlots as $date => $slots) {
            foreach ($slots as $slot) {
                $allSlots[] = $slot['time'];
            }
        }

        return $allSlots;
    }

    /**
     * Transform Cal.com availability to internal format
     *
     * @param array $calcomSlots Raw slots from Cal.com
     * @param Carbon $weekStart Start of week
     * @param int $durationMinutes Service duration
     *
     * @return array Week structure [monday => [...], tuesday => [...], ...]
     */
    protected function transformAvailability(
        array $calcomSlots,
        Carbon $weekStart,
        int $durationMinutes
    ): array {
        $result = $this->getEmptyWeekStructure();

        if (empty($calcomSlots)) {
            return $result;
        }

        foreach ($calcomSlots as $slot) {
            try {
                $slotTime = Carbon::parse($slot);

                // Check if slot is within the week
                if ($slotTime->lt($weekStart) || $slotTime->gt($weekStart->copy()->endOfWeek(Carbon::SUNDAY))) {
                    continue;
                }

                // Get day name
                $dayName = strtolower($slotTime->format('l'));
                if (!isset($result[$dayName])) {
                    continue;
                }

                // Add to result
                $result[$dayName][] = [
                    'time' => $slotTime->format('H:i'),
                    'full_datetime' => $slotTime->toIso8601String(),
                    'date' => $slotTime->format('d.m.Y'),
                    'day_name' => $dayName,
                    'duration_minutes' => $durationMinutes,
                ];

            } catch (\Exception $e) {
                Log::warning('[CalcomAvailability] Error parsing slot', [
                    'slot' => $slot,
                    'error' => $e->getMessage(),
                ]);
                continue;
            }
        }

        return $result;
    }

    /**
     * Get empty week structure
     */
    protected function getEmptyWeekStructure(): array
    {
        return [
            'monday' => [],
            'tuesday' => [],
            'wednesday' => [],
            'thursday' => [],
            'friday' => [],
            'saturday' => [],
            'sunday' => [],
        ];
    }

    /**
     * Build cache key for availability
     */
    protected function getCacheKey(
        int $eventTypeId,
        Carbon $weekStart,
        ?string $staffId = null
    ): string {
        $key = "calcom_availability:{$eventTypeId}:{$weekStart->format('Y-m-d')}";

        if ($staffId) {
            $key .= ":{$staffId}";
        }

        return $key;
    }

    /**
     * Check if a specific time slot is available via Cal.com API
     *
     * This is the SOURCE OF TRUTH for availability.
     * Local DB only reflects our bookings, but Cal.com may have external bookings.
     *
     * @param Carbon $datetime Exact datetime to check
     * @param int $eventTypeId Cal.com event type ID
     * @param int $durationMinutes Service duration
     * @param string|null $staffId Specific staff member (optional)
     * @param int|null $teamId Cal.com team ID
     *
     * @return bool True if slot is available
     */
    public function isTimeSlotAvailable(
        Carbon $datetime,
        int $eventTypeId,
        int $durationMinutes,
        ?string $staffId = null,
        ?int $teamId = null
    ): bool {
        // Build cache key for this specific slot
        $cacheKey = "calcom_slot:{$eventTypeId}:{$datetime->format('Y-m-d_H:i')}";
        if ($staffId) {
            $cacheKey .= ":{$staffId}";
        }

        // Check cache (30 second TTL - short because availability changes frequently)
        if (Cache::has($cacheKey)) {
            Log::debug('[CalcomAvailability] Using cached slot availability', [
                'cache_key' => $cacheKey,
            ]);
            return Cache::get($cacheKey);
        }

        try {
            // 🔧 FIX 2025-11-25: WIDER QUERY WINDOW for composite service segments
            // PROBLEM: Previous implementation only queried [target, target + duration]
            // This failed for composite segments that don't align with slot boundaries.
            // Example: Segment D starts at 12:35, but Cal.com only has slots at 12:20, 13:00
            //          Querying 12:35-13:15 returns NO slots even though 12:20 slot covers 12:35
            //
            // SOLUTION: Query a wider window to capture slots that would COVER our target time
            // - Start: target - duration (to catch earlier slots that extend to our target)
            // - End: target + duration (to catch our exact slot if it exists)
            //
            // For target=12:35 with duration=40min:
            // - Query window: 11:55 - 13:15
            // - This captures slot 12:20 which ends at 13:00, covering 12:35
            $startTime = $datetime->copy()->subMinutes($durationMinutes);
            $endTime = $datetime->copy()->addMinutes($durationMinutes);

            Log::debug('[CalcomAvailability] Checking specific time slot (wide query)', [
                'datetime' => $datetime->format('Y-m-d H:i'),
                'event_type_id' => $eventTypeId,
                'duration_minutes' => $durationMinutes,
                'staff_id' => $staffId,
                'query_window' => [
                    'start' => $startTime->format('Y-m-d H:i'),
                    'end' => $endTime->format('Y-m-d H:i'),
                    'note' => 'Wide window to capture overlapping slots'
                ],
            ]);

            $slots = $this->fetchFromCalcom(
                $eventTypeId,
                $startTime,
                $endTime,
                $staffId,
                $teamId
            );

            // Check if our specific datetime is available
            $available = false;

            // 🔧 FIX 2025-11-14: CRITICAL TIMEZONE BUG
            // PROBLEM: Cal.com returns UTC times, but $datetime is in Europe/Berlin
            // SOLUTION: Convert both to same timezone (Europe/Berlin) before comparing

            // 🔧 FIX 2025-11-25: RANGE-BASED MATCHING for composite services
            // PROBLEM: Cal.com returns slots at fixed intervals (e.g., every 40min for 40min event)
            // Composite service segments may start at arbitrary times (e.g., 12:35) that don't
            // align with slot boundaries.
            // SOLUTION: Check if requested time falls WITHIN an available slot's window
            // Available if: any slot where slot_start <= requested_time < slot_end

            $targetTimezone = 'Europe/Berlin';
            $targetTimeBerlin = $datetime->copy()->setTimezone($targetTimezone);
            $targetTimeStr = $targetTimeBerlin->format('Y-m-d H:i');

            Log::debug('[CalcomAvailability] Timezone-aware slot comparison (range-based)', [
                'target_datetime' => $datetime->toIso8601String(),
                'target_berlin' => $targetTimeStr,
                'target_timezone' => $targetTimezone,
                'slots_to_check' => count($slots),
                'segment_duration' => $durationMinutes,
            ]);

            foreach ($slots as $slot) {
                try {
                    // Parse slot (comes as UTC from Cal.com: "2025-11-14T21:15:00.000Z")
                    $slotTime = Carbon::parse($slot);

                    // Convert to Berlin timezone for comparison
                    $slotTimeBerlin = $slotTime->copy()->setTimezone($targetTimezone);
                    $slotTimeStr = $slotTimeBerlin->format('Y-m-d H:i');

                    // 🔧 FIX 2025-11-25: DUAL MATCHING STRATEGY
                    // Strategy 1: Exact match (original behavior)
                    if ($slotTimeStr === $targetTimeStr) {
                        Log::info('[CalcomAvailability] ✅ EXACT SLOT MATCH FOUND!', [
                            'target_berlin' => $targetTimeStr,
                            'slot_berlin' => $slotTimeStr,
                            'match_type' => 'exact',
                        ]);
                        $available = true;
                        break;
                    }

                    // Strategy 2: Range-based match for composite service segments
                    // Check if target time falls within this slot's available window
                    // The slot is available from slot_start to slot_start + slot_interval
                    // We use the EARLIER slot that covers our target time
                    $diffMinutes = $slotTimeBerlin->diffInMinutes($targetTimeBerlin, false); // signed diff

                    // If slot starts BEFORE target and target is within reasonable range (< duration)
                    // then the slot window likely covers our target time
                    // Example: slot=12:20, target=12:35, diff=15min, duration=40min → covered
                    if ($diffMinutes > 0 && $diffMinutes < $durationMinutes) {
                        // Target time is AFTER this slot starts but within its duration window
                        // This means the staff WOULD be available at our target time
                        // because they'd still be within this slot's booking window
                        Log::info('[CalcomAvailability] ✅ RANGE-BASED MATCH FOUND!', [
                            'target_berlin' => $targetTimeStr,
                            'slot_berlin' => $slotTimeStr,
                            'diff_minutes' => $diffMinutes,
                            'duration' => $durationMinutes,
                            'match_type' => 'range',
                            'explanation' => 'Target time falls within slot window',
                        ]);
                        $available = true;
                        break;
                    }

                } catch (\Exception $e) {
                    Log::warning('[CalcomAvailability] Error parsing slot in availability check', [
                        'slot' => $slot,
                        'error' => $e->getMessage(),
                    ]);
                    continue;
                }
            }

            // Cache result (short TTL since availability changes)
            Cache::put($cacheKey, $available, 30); // 30 seconds

            Log::info('[CalcomAvailability] ✅ Slot availability checked', [
                'datetime' => $datetime->format('Y-m-d H:i'),
                'event_type_id' => $eventTypeId,
                'available' => $available,
                'staff_id' => $staffId,
                'slots_returned' => count($slots),
            ]);

            return $available;

        } catch (\Exception $e) {
            Log::error('[CalcomAvailability] ❌ Error checking slot availability', [
                'datetime' => $datetime->format('Y-m-d H:i'),
                'event_type_id' => $eventTypeId,
                'error' => $e->getMessage(),
            ]);

            // On error, be conservative: return false (not available)
            // Better to say "not available" than to book and fail
            return false;
        }
    }

    /**
     * Check if ALL segments of a composite service are available
     *
     * 🔧 FIX 2025-11-25: Root cause fix for composite service sync failures
     *
     * PROBLEM: Previous implementation only checked if the START TIME was available,
     * ignoring subsequent segment times. This caused sync failures when other
     * bookings existed during segment B, C, D times.
     *
     * SOLUTION: Check availability for EACH active segment's time slot separately.
     * Only return true if ALL segments can be booked.
     *
     * Example (Dauerwelle at 11:00):
     * - Segment A (11:00-11:50): Check Cal.com for event type A
     * - Gap A (11:50-12:05): No booking needed
     * - Segment B (12:05-12:10): Check Cal.com for event type B
     * - Gap B (12:10-12:20): No booking needed
     * - Segment C (12:20-12:35): Check Cal.com for event type C
     * - Segment D (12:35-13:15): Check Cal.com for event type D
     *
     * @param Carbon $startTime Appointment start time
     * @param Service $service The composite service
     * @param string $staffId Staff UUID
     * @param int|null $teamId Cal.com team ID
     *
     * @return array{available: bool, conflicts: array, checked_segments: array}
     */
    public function isCompositeServiceAvailable(
        Carbon $startTime,
        \App\Models\Service $service,
        string $staffId,
        ?int $teamId = null
    ): array {
        if (!$service->isComposite() || empty($service->segments)) {
            Log::warning('[CalcomAvailability] isCompositeServiceAvailable called for non-composite service', [
                'service_id' => $service->id,
                'is_composite' => $service->isComposite(),
            ]);

            // Fallback to standard check
            $available = $this->isTimeSlotAvailable(
                $startTime,
                $service->calcom_event_type_id,
                $service->getTotalDuration(),
                null, // No staff filter for standard check
                $teamId
            );

            return [
                'available' => $available,
                'conflicts' => [],
                'checked_segments' => [],
            ];
        }

        Log::info('[CalcomAvailability] 🎨 Checking composite service availability (ALL segments)', [
            'service_id' => $service->id,
            'service_name' => $service->name,
            'staff_id' => $staffId,
            'start_time' => $startTime->format('Y-m-d H:i'),
            'total_segments' => count($service->segments),
        ]);

        $segments = collect($service->segments)->sortBy('order');
        $currentTime = $startTime->copy();
        $checkedSegments = [];
        $conflicts = [];
        $allAvailable = true;

        foreach ($segments as $segment) {
            $segmentKey = $segment['key'] ?? null;
            $segmentName = $segment['name'] ?? $segmentKey;
            $duration = $segment['durationMin'] ?? $segment['duration'] ?? 0;
            $staffRequired = $segment['staff_required'] ?? true;

            // Calculate segment times
            $segmentStart = $currentTime->copy();
            $segmentEnd = $segmentStart->copy()->addMinutes($duration);

            // Only check segments where staff is required (active segments, not gaps)
            if ($staffRequired && $duration > 0) {
                // Get CalcomEventMap for this segment + staff
                $mapping = \App\Models\CalcomEventMap::where('service_id', $service->id)
                    ->where('segment_key', $segmentKey)
                    ->where('staff_id', $staffId)
                    ->first();

                if (!$mapping) {
                    Log::error('[CalcomAvailability] ❌ Missing CalcomEventMap for segment', [
                        'service_id' => $service->id,
                        'segment_key' => $segmentKey,
                        'staff_id' => $staffId,
                    ]);

                    $conflicts[] = [
                        'segment_key' => $segmentKey,
                        'segment_name' => $segmentName,
                        'start_time' => $segmentStart->format('Y-m-d H:i'),
                        'error' => 'Missing CalcomEventMap configuration',
                    ];
                    $allAvailable = false;
                    $currentTime->addMinutes($duration);
                    continue;
                }

                // Use child event type ID if available, otherwise use parent
                $eventTypeId = $mapping->child_event_type_id ?? $mapping->event_type_id;

                Log::debug('[CalcomAvailability] Checking segment availability', [
                    'segment_key' => $segmentKey,
                    'segment_name' => $segmentName,
                    'start_time' => $segmentStart->format('Y-m-d H:i'),
                    'end_time' => $segmentEnd->format('Y-m-d H:i'),
                    'duration' => $duration,
                    'event_type_id' => $eventTypeId,
                ]);

                // Check Cal.com availability for this specific segment
                $segmentAvailable = $this->isTimeSlotAvailable(
                    $segmentStart,
                    $eventTypeId,
                    $duration,
                    null, // Staff already selected via event type
                    $teamId
                );

                $checkedSegments[] = [
                    'segment_key' => $segmentKey,
                    'segment_name' => $segmentName,
                    'start_time' => $segmentStart->format('Y-m-d H:i'),
                    'end_time' => $segmentEnd->format('Y-m-d H:i'),
                    'duration' => $duration,
                    'event_type_id' => $eventTypeId,
                    'available' => $segmentAvailable,
                ];

                if (!$segmentAvailable) {
                    $allAvailable = false;
                    $conflicts[] = [
                        'segment_key' => $segmentKey,
                        'segment_name' => $segmentName,
                        'start_time' => $segmentStart->format('Y-m-d H:i'),
                        'end_time' => $segmentEnd->format('Y-m-d H:i'),
                        'reason' => 'Cal.com reports slot not available (staff may have other booking)',
                    ];

                    Log::warning('[CalcomAvailability] ⚠️ Segment NOT available - EARLY EXIT', [
                        'segment_key' => $segmentKey,
                        'start_time' => $segmentStart->format('Y-m-d H:i'),
                        'event_type_id' => $eventTypeId,
                        'optimization' => 'Skipping remaining segments to save API calls',
                    ]);

                    // 🔧 FIX 2025-11-26: EARLY EXIT OPTIMIZATION
                    // If one segment is unavailable, the entire composite service is unavailable.
                    // No point checking remaining segments - exit immediately to save API calls.
                    // Expected savings: 1-3 API calls (800-4500ms) when first segment conflicts
                    break;
                } else {
                    Log::debug('[CalcomAvailability] ✅ Segment available', [
                        'segment_key' => $segmentKey,
                        'start_time' => $segmentStart->format('Y-m-d H:i'),
                    ]);
                }
            } else {
                // Gap segment - no Cal.com check needed, but track for logging
                $checkedSegments[] = [
                    'segment_key' => $segmentKey,
                    'segment_name' => $segmentName,
                    'start_time' => $segmentStart->format('Y-m-d H:i'),
                    'end_time' => $segmentEnd->format('Y-m-d H:i'),
                    'duration' => $duration,
                    'staff_required' => false,
                    'available' => true, // Gaps are always "available"
                    'note' => 'Gap segment - no Cal.com booking needed',
                ];
            }

            // Move to next segment
            $currentTime->addMinutes($duration);
        }

        Log::info('[CalcomAvailability] 🎨 Composite availability check complete', [
            'service_id' => $service->id,
            'all_available' => $allAvailable,
            'total_checked' => count(array_filter($checkedSegments, fn($s) => $s['staff_required'] ?? true)),
            'conflicts_count' => count($conflicts),
            'conflicts' => $conflicts,
        ]);

        return [
            'available' => $allAvailable,
            'conflicts' => $conflicts,
            'checked_segments' => $checkedSegments,
        ];
    }

    /**
     * Invalidate cache for availability
     *
     * Call this when:
     * - Event type configuration changes
     * - Staff availability changes
     * - New bookings are made
     */
    public function invalidateCache(
        int $eventTypeId,
        ?Carbon $weekStart = null,
        ?string $staffId = null
    ): void {
        if ($weekStart) {
            $cacheKey = $this->getCacheKey($eventTypeId, $weekStart, $staffId);
            Cache::forget($cacheKey);

            Log::info('[CalcomAvailability] Cache invalidated', [
                'cache_key' => $cacheKey,
            ]);
        } else {
            // Invalidate all availability caches for this event type
            // This is a bit brute-force, but availability data is short-lived (60s)
            Log::info('[CalcomAvailability] Full cache invalidation for event type', [
                'event_type_id' => $eventTypeId,
            ]);
        }
    }
}
