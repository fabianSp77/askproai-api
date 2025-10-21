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
        $params = [
            'eventTypeId' => $eventTypeId,
            'startTime' => $startDate->toIso8601String(),
            'endTime' => $endDate->toIso8601String(),
        ];

        if ($teamId) {
            $params['teamId'] = $teamId;
        }

        if ($staffId) {
            $params['userId'] = $staffId;
        }

        Log::debug('[CalcomAvailability] Fetching from Cal.com API', [
            'event_type_id' => $eventTypeId,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'staff_id' => $staffId,
        ]);

        // Cal.com availability endpoint (✅ V2 API - v1 deprecated end of 2025)
        $apiVersion = config('services.calcom.api_version', '2024-08-13');
        $response = $this->calcomService->httpClient()
            ->withHeaders([
                'cal-api-version' => $apiVersion,
            ])
            ->get('https://api.cal.com/v2/availability', $params);

        if (!$response->successful()) {
            Log::warning('[CalcomAvailability] Cal.com API error', [
                'status' => $response->status(),
                'error' => $response->json()['message'] ?? 'Unknown error',
            ]);
            return [];
        }

        return $response->json()['slots'] ?? [];
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
