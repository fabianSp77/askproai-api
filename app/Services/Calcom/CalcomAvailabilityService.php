<?php

namespace App\Services\Calcom;

use App\Services\Calcom\CalcomV2Service;
use App\Models\Branch;
use App\Models\Staff;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class CalcomAvailabilityService
{
    protected CalcomV2Service $calcomService;
    protected array $config;

    public function __construct(CalcomV2Service $calcomService = null)
    {
        $this->calcomService = $calcomService ?? app(CalcomV2Service::class);
        $this->config = config('services.calcom', []);
    }

    /**
     * Check availability for a specific date and event type
     *
     * @param int $eventTypeId
     * @param string $date Format: Y-m-d
     * @param array $options Additional options (timezone, duration, etc.)
     * @return array
     */
    public function checkAvailability(int $eventTypeId, string $date, array $options = []): array
    {
        try {
            $cacheKey = $this->getAvailabilityCacheKey($eventTypeId, $date, $options);
            
            // Check cache first
            if ($cached = Cache::get($cacheKey)) {
                Log::debug('Returning cached availability', [
                    'event_type_id' => $eventTypeId,
                    'date' => $date,
                    'cache_key' => $cacheKey
                ]);
                return $cached;
            }

            // Parse date
            $carbonDate = Carbon::parse($date);
            
            // Call Cal.com API
            $response = $this->calcomService->checkAvailability(
                $eventTypeId,
                $carbonDate->format('Y-m-d'),
                $options['timezone'] ?? 'Europe/Berlin'
            );

            if (!$response['success']) {
                Log::error('Cal.com availability check failed', [
                    'event_type_id' => $eventTypeId,
                    'date' => $date,
                    'error' => $response['message'] ?? 'Unknown error'
                ]);
                
                return [
                    'success' => false,
                    'available' => false,
                    'slots' => [],
                    'error' => $response['message'] ?? 'Availability check failed'
                ];
            }

            // Process slots
            $slots = $this->processAvailableSlots(
                $response['data']['slots'] ?? [],
                $carbonDate,
                $options
            );

            $result = [
                'success' => true,
                'available' => count($slots) > 0,
                'date' => $date,
                'slots' => $slots,
                'total_slots' => count($slots),
                'event_type_id' => $eventTypeId,
                'cached_until' => now()->addMinutes(5)->toIso8601String()
            ];

            // Cache the result
            Cache::put($cacheKey, $result, now()->addMinutes(5));

            return $result;

        } catch (\Exception $e) {
            Log::error('Exception during availability check', [
                'event_type_id' => $eventTypeId,
                'date' => $date,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'available' => false,
                'slots' => [],
                'error' => 'Ein Fehler ist aufgetreten bei der Verfügbarkeitsprüfung'
            ];
        }
    }

    /**
     * Check availability for multiple dates
     *
     * @param int $eventTypeId
     * @param array $dates Array of dates in Y-m-d format
     * @param array $options
     * @return array
     */
    public function checkMultipleDatesAvailability(int $eventTypeId, array $dates, array $options = []): array
    {
        $results = [];
        
        foreach ($dates as $date) {
            $results[$date] = $this->checkAvailability($eventTypeId, $date, $options);
        }

        return [
            'success' => true,
            'dates' => $results,
            'summary' => [
                'total_dates' => count($dates),
                'available_dates' => count(array_filter($results, fn($r) => $r['available'] ?? false)),
                'total_slots' => array_sum(array_map(fn($r) => $r['total_slots'] ?? 0, $results))
            ]
        ];
    }

    /**
     * Check availability for a date range
     *
     * @param int $eventTypeId
     * @param string $startDate
     * @param string $endDate
     * @param array $options
     * @return array
     */
    public function checkDateRangeAvailability(
        int $eventTypeId,
        string $startDate,
        string $endDate,
        array $options = []
    ): array {
        $period = CarbonPeriod::create($startDate, $endDate);
        $dates = [];
        
        foreach ($period as $date) {
            $dates[] = $date->format('Y-m-d');
        }

        return $this->checkMultipleDatesAvailability($eventTypeId, $dates, $options);
    }

    /**
     * Find next available slot
     *
     * @param int $eventTypeId
     * @param array $options
     * @return array|null
     */
    public function findNextAvailableSlot(int $eventTypeId, array $options = []): ?array
    {
        $maxDays = $options['max_days'] ?? 30;
        $preferredTimes = $options['preferred_times'] ?? null;
        $minTime = $options['min_time'] ?? null;
        $maxTime = $options['max_time'] ?? null;

        for ($i = 0; $i < $maxDays; $i++) {
            $date = now()->addDays($i)->format('Y-m-d');
            $availability = $this->checkAvailability($eventTypeId, $date, $options);

            if ($availability['available'] ?? false) {
                $slots = $availability['slots'];

                // Filter by preferred times if specified
                if ($preferredTimes || $minTime || $maxTime) {
                    $slots = $this->filterSlotsByTimePreferences(
                        $slots,
                        $preferredTimes,
                        $minTime,
                        $maxTime
                    );
                }

                if (!empty($slots)) {
                    return [
                        'found' => true,
                        'date' => $date,
                        'slot' => $slots[0],
                        'days_ahead' => $i
                    ];
                }
            }
        }

        return [
            'found' => false,
            'searched_days' => $maxDays,
            'message' => 'Keine verfügbaren Termine in den nächsten ' . $maxDays . ' Tagen gefunden'
        ];
    }

    /**
     * Check if specific time slot is available
     *
     * @param int $eventTypeId
     * @param string $date
     * @param string $time Format: H:i
     * @param array $options
     * @return bool
     */
    public function isTimeSlotAvailable(
        int $eventTypeId,
        string $date,
        string $time,
        array $options = []
    ): bool {
        $availability = $this->checkAvailability($eventTypeId, $date, $options);

        if (!($availability['available'] ?? false)) {
            return false;
        }

        $requestedTime = Carbon::parse($date . ' ' . $time)->format('H:i');

        foreach ($availability['slots'] as $slot) {
            if ($slot['time'] === $requestedTime) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get availability summary for a branch
     *
     * @param Branch $branch
     * @param int $days Number of days to check
     * @return array
     */
    public function getBranchAvailabilitySummary(Branch $branch, int $days = 7): array
    {
        if (!$branch->calcom_event_type_id) {
            return [
                'success' => false,
                'error' => 'Branch has no Cal.com event type configured'
            ];
        }

        $startDate = now()->format('Y-m-d');
        $endDate = now()->addDays($days - 1)->format('Y-m-d');

        $availability = $this->checkDateRangeAvailability(
            $branch->calcom_event_type_id,
            $startDate,
            $endDate
        );

        $summary = [
            'branch_id' => $branch->id,
            'branch_name' => $branch->name,
            'period' => [
                'start' => $startDate,
                'end' => $endDate,
                'days' => $days
            ],
            'availability' => [
                'total_slots' => $availability['summary']['total_slots'],
                'available_days' => $availability['summary']['available_dates'],
                'booked_percentage' => 0, // Would need to compare with existing bookings
            ],
            'busiest_day' => null,
            'quietest_day' => null,
            'next_available' => $this->findNextAvailableSlot($branch->calcom_event_type_id)
        ];

        // Find busiest and quietest days
        $daySlots = [];
        foreach ($availability['dates'] as $date => $data) {
            if ($data['available'] ?? false) {
                $daySlots[$date] = $data['total_slots'];
            }
        }

        if (!empty($daySlots)) {
            $summary['busiest_day'] = array_search(max($daySlots), $daySlots);
            $summary['quietest_day'] = array_search(min($daySlots), $daySlots);
        }

        return [
            'success' => true,
            'summary' => $summary
        ];
    }

    /**
     * Find alternative slots based on preferences
     *
     * @param int $eventTypeId
     * @param string $requestedDate
     * @param string $requestedTime
     * @param array $preferences
     * @return array
     */
    public function findAlternativeSlots(
        int $eventTypeId,
        string $requestedDate,
        string $requestedTime,
        array $preferences = []
    ): array {
        $alternatives = [];
        $maxAlternatives = $preferences['max_alternatives'] ?? 3;
        $searchRadius = $preferences['search_radius_days'] ?? 7;
        
        // Parse preferences
        $preferSameDay = $preferences['prefer_same_day'] ?? true;
        $preferSameTime = $preferences['prefer_same_time'] ?? true;
        $timeWindow = $preferences['time_window_hours'] ?? 2;

        // First check same day, different times
        if ($preferSameDay) {
            $sameDayAvailability = $this->checkAvailability($eventTypeId, $requestedDate);
            
            if ($sameDayAvailability['available'] ?? false) {
                $nearbySlots = $this->findNearbyTimeSlots(
                    $sameDayAvailability['slots'],
                    $requestedTime,
                    $timeWindow
                );

                foreach ($nearbySlots as $slot) {
                    if (count($alternatives) < $maxAlternatives) {
                        $alternatives[] = [
                            'date' => $requestedDate,
                            'time' => $slot['time'],
                            'datetime' => $slot['datetime'],
                            'difference_minutes' => $slot['difference_minutes'],
                            'type' => 'same_day_different_time'
                        ];
                    }
                }
            }
        }

        // Then check different days, same time
        if ($preferSameTime && count($alternatives) < $maxAlternatives) {
            for ($i = 1; $i <= $searchRadius && count($alternatives) < $maxAlternatives; $i++) {
                // Check future dates
                $futureDate = Carbon::parse($requestedDate)->addDays($i)->format('Y-m-d');
                if ($this->isTimeSlotAvailable($eventTypeId, $futureDate, $requestedTime)) {
                    $alternatives[] = [
                        'date' => $futureDate,
                        'time' => $requestedTime,
                        'datetime' => $futureDate . ' ' . $requestedTime,
                        'days_difference' => $i,
                        'type' => 'different_day_same_time'
                    ];
                }

                // Check past dates if allowed
                if ($preferences['allow_past_dates'] ?? false) {
                    $pastDate = Carbon::parse($requestedDate)->subDays($i)->format('Y-m-d');
                    if ($pastDate >= now()->format('Y-m-d') &&
                        $this->isTimeSlotAvailable($eventTypeId, $pastDate, $requestedTime)) {
                        $alternatives[] = [
                            'date' => $pastDate,
                            'time' => $requestedTime,
                            'datetime' => $pastDate . ' ' . $requestedTime,
                            'days_difference' => -$i,
                            'type' => 'different_day_same_time'
                        ];
                    }
                }
            }
        }

        // Finally, check any available slots
        if (count($alternatives) < $maxAlternatives) {
            $nextSlots = $this->findNextAvailableSlots(
                $eventTypeId,
                $maxAlternatives - count($alternatives),
                ['start_date' => $requestedDate]
            );

            foreach ($nextSlots as $slot) {
                $alternatives[] = array_merge($slot, ['type' => 'next_available']);
            }
        }

        return [
            'requested' => [
                'date' => $requestedDate,
                'time' => $requestedTime,
                'available' => $this->isTimeSlotAvailable($eventTypeId, $requestedDate, $requestedTime)
            ],
            'alternatives' => $alternatives,
            'total_alternatives' => count($alternatives)
        ];
    }

    /**
     * Process raw slots from Cal.com API
     *
     * @param array $rawSlots
     * @param Carbon $date
     * @param array $options
     * @return array
     */
    protected function processAvailableSlots(array $rawSlots, Carbon $date, array $options = []): array
    {
        $slots = [];
        $timezone = $options['timezone'] ?? 'Europe/Berlin';

        foreach ($rawSlots as $slot) {
            // Parse slot time
            $slotTime = is_array($slot) ? Carbon::parse($slot['time']) : Carbon::parse($slot);
            $slotTime->setTimezone($timezone);

            // Skip slots that are in the past
            if ($slotTime->isPast()) {
                continue;
            }

            // Skip slots outside business hours if specified
            if (isset($options['business_hours'])) {
                $hour = $slotTime->hour;
                if ($hour < $options['business_hours']['start'] ||
                    $hour >= $options['business_hours']['end']) {
                    continue;
                }
            }

            $slots[] = [
                'time' => $slotTime->format('H:i'),
                'datetime' => $slotTime->toIso8601String(),
                'timestamp' => $slotTime->timestamp,
                'formatted' => $slotTime->format('H:i') . ' Uhr',
                'available' => true
            ];
        }

        // Sort slots by time
        usort($slots, fn($a, $b) => $a['timestamp'] <=> $b['timestamp']);

        return $slots;
    }

    /**
     * Filter slots by time preferences
     *
     * @param array $slots
     * @param array|null $preferredTimes
     * @param string|null $minTime
     * @param string|null $maxTime
     * @return array
     */
    protected function filterSlotsByTimePreferences(
        array $slots,
        ?array $preferredTimes,
        ?string $minTime,
        ?string $maxTime
    ): array {
        return array_filter($slots, function ($slot) use ($preferredTimes, $minTime, $maxTime) {
            $slotTime = $slot['time'];

            // Check preferred times
            if ($preferredTimes && !in_array($slotTime, $preferredTimes)) {
                return false;
            }

            // Check min time
            if ($minTime && $slotTime < $minTime) {
                return false;
            }

            // Check max time
            if ($maxTime && $slotTime > $maxTime) {
                return false;
            }

            return true;
        });
    }

    /**
     * Find slots near a specific time
     *
     * @param array $slots
     * @param string $targetTime
     * @param int $windowHours
     * @return array
     */
    protected function findNearbyTimeSlots(array $slots, string $targetTime, int $windowHours = 2): array
    {
        $targetMinutes = $this->timeToMinutes($targetTime);
        $windowMinutes = $windowHours * 60;

        $nearbySlots = [];

        foreach ($slots as $slot) {
            $slotMinutes = $this->timeToMinutes($slot['time']);
            $difference = abs($slotMinutes - $targetMinutes);

            if ($difference <= $windowMinutes && $slot['time'] !== $targetTime) {
                $nearbySlots[] = array_merge($slot, [
                    'difference_minutes' => $slotMinutes - $targetMinutes
                ]);
            }
        }

        // Sort by proximity to target time
        usort($nearbySlots, fn($a, $b) => 
            abs($a['difference_minutes']) <=> abs($b['difference_minutes'])
        );

        return $nearbySlots;
    }

    /**
     * Find next N available slots
     *
     * @param int $eventTypeId
     * @param int $count
     * @param array $options
     * @return array
     */
    protected function findNextAvailableSlots(int $eventTypeId, int $count, array $options = []): array
    {
        $slots = [];
        $startDate = $options['start_date'] ?? now()->format('Y-m-d');
        $maxDays = $options['max_days'] ?? 30;

        for ($i = 0; $i < $maxDays && count($slots) < $count; $i++) {
            $date = Carbon::parse($startDate)->addDays($i)->format('Y-m-d');
            $availability = $this->checkAvailability($eventTypeId, $date, $options);

            if ($availability['available'] ?? false) {
                foreach ($availability['slots'] as $slot) {
                    if (count($slots) < $count) {
                        $slots[] = [
                            'date' => $date,
                            'time' => $slot['time'],
                            'datetime' => $slot['datetime'],
                            'days_ahead' => $i
                        ];
                    }
                }
            }
        }

        return $slots;
    }

    /**
     * Convert time string to minutes
     *
     * @param string $time Format: H:i
     * @return int
     */
    protected function timeToMinutes(string $time): int
    {
        [$hours, $minutes] = explode(':', $time);
        return (int)$hours * 60 + (int)$minutes;
    }

    /**
     * Generate cache key for availability
     *
     * @param int $eventTypeId
     * @param string $date
     * @param array $options
     * @return string
     */
    protected function getAvailabilityCacheKey(int $eventTypeId, string $date, array $options): string
    {
        $optionsHash = md5(json_encode($options));
        return "calcom_availability:{$eventTypeId}:{$date}:{$optionsHash}";
    }
}