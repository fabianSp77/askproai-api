<?php

namespace App\Services\Appointments;

use App\Models\Service;
use App\Services\CalcomService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Exceptions\CalcomApiException;

/**
 * WeeklyAvailabilityService
 *
 * Service-specific weekly availability management for appointment booking
 * Integrates with Cal.com API to fetch real availability data
 *
 * Key Features:
 * - Service-based availability (uses service.calcom_event_type_id)
 * - Week-at-a-glance view (Monday-Sunday)
 * - Smart caching (60s TTL, service-specific keys)
 * - Timezone handling (Cal.com UTC → Europe/Berlin)
 *
 * @see CalcomService For Cal.com API integration
 * @see Service For service model with calcom_event_type_id
 */
class WeeklyAvailabilityService
{
    public function __construct(
        protected CalcomService $calcomService
    ) {}

    /**
     * Get available slots for a service for an entire week
     *
     * Fetches availability from Cal.com API for the specified service's event type
     * and transforms it into a week-at-a-glance structure (Monday-Sunday)
     *
     * Performance: Cached for 60 seconds per service per week
     *
     * @param string $serviceId Service UUID
     * @param Carbon $weekStart Start of week (Monday)
     * @return array Week structure with slots per day
     *
     * @throws \Exception If service has no Cal.com Event Type ID
     * @throws CalcomApiException If Cal.com API fails
     *
     * Example return structure:
     * [
     *   'monday' => [
     *     ['time' => '09:00', 'full_datetime' => '2025-10-14T09:00:00+02:00', ...],
     *     ['time' => '09:30', 'full_datetime' => '2025-10-14T09:30:00+02:00', ...],
     *   ],
     *   'tuesday' => [...],
     *   ...
     * ]
     */
    public function getWeekAvailability(string $serviceId, Carbon $weekStart): array
    {
        // Validate weekStart is Monday (force to Monday if not)
        if ($weekStart->dayOfWeek !== Carbon::MONDAY) {
            $weekStart = $weekStart->copy()->startOfWeek(Carbon::MONDAY);
        }

        $weekEnd = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);

        // Get service with Cal.com Event Type ID
        $service = Service::findOrFail($serviceId);

        if (!$service->calcom_event_type_id) {
            throw new \Exception(
                "Service '{$service->name}' has no Cal.com Event Type ID configured. " .
                "Please configure this service in the Cal.com integration settings."
            );
        }

        // Check cache first (service-specific + week-specific)
        // Cache key pattern: week_availability:{service_id}:{week_start_date}
        $cacheKey = "week_availability:{$serviceId}:{$weekStart->format('Y-m-d')}";

        Log::debug('[WeeklyAvailability] Fetching week availability', [
            'service_id' => $serviceId,
            'service_name' => $service->name,
            'calcom_event_type_id' => $service->calcom_event_type_id,
            'week_start' => $weekStart->format('Y-m-d'),
            'week_end' => $weekEnd->format('Y-m-d'),
            'cache_key' => $cacheKey,
        ]);

        return Cache::remember($cacheKey, 60, function() use ($service, $weekStart, $weekEnd, $cacheKey) {
            try {
                // Fetch from Cal.com API
                $response = $this->calcomService->getAvailableSlots(
                    eventTypeId: $service->calcom_event_type_id,
                    startDate: $weekStart->format('Y-m-d'),
                    endDate: $weekEnd->format('Y-m-d')
                );

                $calcomData = $response->json();
                $slotsData = $calcomData['data']['slots'] ?? [];

                Log::info('[WeeklyAvailability] Cal.com API response received', [
                    'cache_key' => $cacheKey,
                    'dates_with_slots' => count($slotsData),
                    'total_slots' => array_sum(array_map('count', $slotsData)),
                ]);

                // Transform to week structure
                return $this->transformToWeekStructure($slotsData, $weekStart);

            } catch (CalcomApiException $e) {
                Log::error('[WeeklyAvailability] Cal.com API error', [
                    'cache_key' => $cacheKey,
                    'error' => $e->getMessage(),
                    'status_code' => $e->getStatusCode(),
                ]);

                // Re-throw for handling at component level
                throw $e;

            } catch (\Exception $e) {
                Log::error('[WeeklyAvailability] Unexpected error', [
                    'cache_key' => $cacheKey,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                // Return empty week structure on error (graceful degradation)
                return $this->getEmptyWeekStructure();
            }
        });
    }

    /**
     * Transform Cal.com slots to week structure
     *
     * Converts Cal.com API response format to a week-at-a-glance structure
     * with slots organized by day of week
     *
     * Input format (Cal.com API):
     * {
     *   "2025-10-14": ["09:00:00Z", "09:30:00Z", ...],
     *   "2025-10-15": ["09:00:00Z", ...],
     *   ...
     * }
     *
     * Output format:
     * [
     *   "monday" => [
     *     ['time' => '09:00', 'full_datetime' => '2025-10-14T09:00:00+02:00', ...],
     *     ...
     *   ],
     *   ...
     * ]
     *
     * @param array $slotsData Cal.com slots data (date => array of UTC times)
     * @param Carbon $weekStart Start of the week (Monday)
     * @return array Week structure with slots per day
     */
    protected function transformToWeekStructure(array $slotsData, Carbon $weekStart): array
    {
        // Initialize empty week structure
        $weekStructure = $this->getEmptyWeekStructure();

        // Day mapping (Carbon day of week → array key)
        $dayMap = [
            1 => 'monday',    // Carbon: Monday = 1
            2 => 'tuesday',
            3 => 'wednesday',
            4 => 'thursday',
            5 => 'friday',
            6 => 'saturday',
            0 => 'sunday',    // Carbon: Sunday = 0
        ];

        foreach ($slotsData as $date => $slots) {
            $carbon = Carbon::parse($date, 'UTC');
            $dayOfWeek = $carbon->dayOfWeek;
            $dayKey = $dayMap[$dayOfWeek];

            // Convert UTC timestamps to Europe/Berlin timezone
            $weekStructure[$dayKey] = array_map(function($slot) use ($date) {
                // Parse UTC time from Cal.com
                // Cal.com API V2 format: ["time" => "2025-10-14T15:00:00.000Z"] (Full ISO 8601)
                // Extract time string from array or use directly
                $timeString = is_array($slot) ? ($slot['time'] ?? $slot) : $slot;

                // If timeString is already a full ISO timestamp, parse directly
                // Otherwise concat with date
                if (str_contains($timeString, 'T')) {
                    // Full ISO 8601: "2025-10-14T15:00:00.000Z"
                    $utcTime = Carbon::parse($timeString, 'UTC');
                } else {
                    // Just time: "09:00:00Z"
                    $utcTime = Carbon::parse($date . ' ' . $timeString, 'UTC');
                }

                // Convert to Europe/Berlin
                $localTime = $utcTime->setTimezone('Europe/Berlin');

                return [
                    'time' => $localTime->format('H:i'), // "09:00"
                    'full_datetime' => $localTime->toIso8601String(), // "2025-10-14T09:00:00+02:00"
                    'date' => $localTime->format('Y-m-d'), // "2025-10-14"
                    'day_name' => $localTime->translatedFormat('l'), // "Montag"
                    'is_morning' => $localTime->hour < 12,
                    'is_afternoon' => $localTime->hour >= 12 && $localTime->hour < 17,
                    'is_evening' => $localTime->hour >= 17,
                    'hour' => $localTime->hour,
                    'minute' => $localTime->minute,
                ];
            }, $slots);

            // Sort slots by time (earliest first)
            usort($weekStructure[$dayKey], fn($a, $b) =>
                ($a['hour'] * 60 + $a['minute']) <=> ($b['hour'] * 60 + $b['minute'])
            );
        }

        return $weekStructure;
    }

    /**
     * Get week metadata (week number, date range, etc.)
     *
     * Provides display information about the week for UI rendering
     *
     * @param Carbon $weekStart Start of the week (Monday)
     * @return array Week metadata
     *
     * Example return:
     * [
     *   'week_number' => 42,
     *   'year' => 2025,
     *   'start_date' => '14.10.2025',
     *   'end_date' => '20.10.2025',
     *   'start_date_iso' => '2025-10-14',
     *   'end_date_iso' => '2025-10-20',
     *   'is_current_week' => true,
     *   'is_past' => false,
     *   'days' => ['monday' => [...], ...]
     * ]
     */
    public function getWeekMetadata(Carbon $weekStart): array
    {
        $weekEnd = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);
        $now = now();

        return [
            'week_number' => $weekStart->weekOfYear,
            'year' => $weekStart->year,
            'start_date' => $weekStart->format('d.m.Y'),
            'end_date' => $weekEnd->format('d.m.Y'),
            'start_date_iso' => $weekStart->format('Y-m-d'),
            'end_date_iso' => $weekEnd->format('Y-m-d'),
            'is_current_week' => $weekStart->isSameWeek($now),
            'is_past' => $weekEnd->isPast(),
            'is_future' => $weekStart->isFuture(),
            'days' => [
                'monday' => $weekStart->copy()->format('d.m.'),
                'tuesday' => $weekStart->copy()->addDay()->format('d.m.'),
                'wednesday' => $weekStart->copy()->addDays(2)->format('d.m.'),
                'thursday' => $weekStart->copy()->addDays(3)->format('d.m.'),
                'friday' => $weekStart->copy()->addDays(4)->format('d.m.'),
                'saturday' => $weekStart->copy()->addDays(5)->format('d.m.'),
                'sunday' => $weekEnd->format('d.m.'),
            ],
        ];
    }

    /**
     * Get empty week structure
     *
     * Returns a week structure with no slots (used for initialization or errors)
     *
     * @return array Empty week structure
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
     * Clear week availability cache for a service
     *
     * Called when appointments are booked/cancelled/rescheduled to ensure
     * fresh availability data on next fetch
     *
     * @param string $serviceId Service UUID
     * @param int $weeksToInvalidate Number of weeks to invalidate (default: 4)
     * @return void
     */
    public function clearServiceCache(string $serviceId, int $weeksToInvalidate = 4): void
    {
        $currentWeekStart = now()->startOfWeek(Carbon::MONDAY);

        // Clear cache for current week + next N weeks
        for ($i = 0; $i < $weeksToInvalidate; $i++) {
            $weekStart = $currentWeekStart->copy()->addWeeks($i);
            $cacheKey = "week_availability:{$serviceId}:{$weekStart->format('Y-m-d')}";
            Cache::forget($cacheKey);
        }

        Log::info('[WeeklyAvailability] Cache cleared', [
            'service_id' => $serviceId,
            'weeks_invalidated' => $weeksToInvalidate,
        ]);
    }

    /**
     * Prefetch next week availability (performance optimization)
     *
     * Fetches and caches next week's availability in background
     * Makes "Next Week" button feel instant for users
     *
     * @param string $serviceId Service UUID
     * @param Carbon $currentWeekStart Current week start (Monday)
     * @return void
     */
    public function prefetchNextWeek(string $serviceId, Carbon $currentWeekStart): void
    {
        $nextWeekStart = $currentWeekStart->copy()->addWeek();

        try {
            // This will cache the data for next week
            $this->getWeekAvailability($serviceId, $nextWeekStart);

            Log::debug('[WeeklyAvailability] Prefetch successful', [
                'service_id' => $serviceId,
                'next_week_start' => $nextWeekStart->format('Y-m-d'),
            ]);

        } catch (\Exception $e) {
            // Prefetch is best-effort, don't fail the main request
            Log::warning('[WeeklyAvailability] Prefetch failed', [
                'service_id' => $serviceId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
