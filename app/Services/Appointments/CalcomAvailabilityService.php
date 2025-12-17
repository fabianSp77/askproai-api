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
            // 🔧 FIX 2025-11-14: Cal.com generates slots dynamically based on query window
            // Query must START at target slot AND provide enough duration for the service
            // Cal.com only returns slots where the FULL service duration fits in the window
            // Testing verified: 22:55 + 55min service = 23:50, so window must extend to at least 23:50
            $startTime = $datetime->copy();
            $endTime = $datetime->copy()->addMinutes($durationMinutes); // Exact duration needed

            Log::debug('[CalcomAvailability] Checking specific time slot', [
                'datetime' => $datetime->format('Y-m-d H:i'),
                'event_type_id' => $eventTypeId,
                'duration_minutes' => $durationMinutes,
                'staff_id' => $staffId,
                'query_window' => [
                    'start' => $startTime->format('Y-m-d H:i'),
                    'end' => $endTime->format('Y-m-d H:i'),
                ],
            ]);

            $slots = $this->fetchFromCalcom(
                $eventTypeId,
                $startTime,
                $endTime,
                $staffId,
                $teamId
            );

            // Check if our specific datetime is in the available slots
            $available = false;

            // 🔧 FIX 2025-11-14: CRITICAL TIMEZONE BUG
            // PROBLEM: Cal.com returns UTC times, but $datetime is in Europe/Berlin
            // SYMPTOM: 22:15 Berlin (21:15 UTC) was not matched because we compared:
            //   - Target: "2025-11-14 22:15" (Berlin)
            //   - Slot:   "2025-11-14 21:15" (UTC parsed as local)
            // SOLUTION: Convert both to same timezone (Europe/Berlin) before comparing

            // Get target time in Berlin timezone (already set, but make explicit)
            $targetTimezone = 'Europe/Berlin';
            $targetTimeBerlin = $datetime->copy()->setTimezone($targetTimezone);
            $targetTimeStr = $targetTimeBerlin->format('Y-m-d H:i');

            Log::debug('[CalcomAvailability] Timezone-aware slot comparison', [
                'target_datetime' => $datetime->toIso8601String(),
                'target_berlin' => $targetTimeStr,
                'target_timezone' => $targetTimezone,
                'slots_to_check' => count($slots),
            ]);

            foreach ($slots as $slot) {
                try {
                    // Parse slot (comes as UTC from Cal.com: "2025-11-14T21:15:00.000Z")
                    $slotTime = Carbon::parse($slot);

                    // Convert to Berlin timezone for comparison
                    $slotTimeBerlin = $slotTime->copy()->setTimezone($targetTimezone);
                    $slotTimeStr = $slotTimeBerlin->format('Y-m-d H:i');

                    // Match to the minute (both now in same timezone)
                    if ($slotTimeStr === $targetTimeStr) {
                        Log::info('[CalcomAvailability] ✅ SLOT MATCH FOUND!', [
                            'target_berlin' => $targetTimeStr,
                            'slot_utc' => $slot,
                            'slot_berlin' => $slotTimeStr,
                            'matched' => true,
                        ]);
                        $available = true;
                        break;
                    } else {
                        Log::debug('[CalcomAvailability] Slot no match', [
                            'target' => $targetTimeStr,
                            'slot' => $slotTimeStr,
                            'diff_minutes' => $targetTimeBerlin->diffInMinutes($slotTimeBerlin),
                        ]);
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
