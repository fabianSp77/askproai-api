<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Exceptions\CalcomApiException;
use App\ValueObjects\TimePreference;

class AppointmentAlternativeFinder
{
    private CalcomService $calcomService;
    private array $config;
    private int $maxAlternatives = 2;

    // Search strategies in priority order
    const STRATEGY_SAME_DAY = 'same_day_different_time';
    const STRATEGY_NEXT_WORKDAY = 'next_workday_same_time';
    const STRATEGY_NEXT_WEEK = 'next_week_same_day';
    const STRATEGY_NEXT_AVAILABLE = 'next_available_workday';

    // 🔧 NEW 2025-12-08: Time window-aware strategies
    const STRATEGY_SAME_DAY_IN_WINDOW = 'same_day_in_window';      // Same day, within preferred window
    const STRATEGY_SAME_WINDOW_OTHER_DAYS = 'same_window_other_days'; // Other days, same window

    // Multi-tenant context for cache isolation
    private ?int $companyId = null;
    private ?string $branchId = null; // UUID string identifier

    public function __construct()
    {
        $this->calcomService = new CalcomService();
        $this->loadConfig();
    }

    /**
     * 🔧 FIX 2025-10-25: Bug #2 - Generate dynamic date description
     * PROBLEM: Hardcoded "am gleichen Tag" shown for next day alternatives
     * SOLUTION: Compare actual dates and return correct German description
     *
     * @param Carbon $alternativeDate The alternative appointment date
     * @param Carbon $requestedDate The originally requested date
     * @return string German date description
     */
    private function generateDateDescription(Carbon $alternativeDate, Carbon $requestedDate): string
    {
        $altDateOnly = $alternativeDate->copy()->startOfDay();
        $reqDateOnly = $requestedDate->copy()->startOfDay();
        $today = Carbon::today('Europe/Berlin');

        // Same day as requested
        if ($altDateOnly->equalTo($reqDateOnly)) {
            return 'am gleichen Tag';
        }

        // Tomorrow from today (absolute)
        if ($altDateOnly->equalTo($today->copy()->addDay())) {
            return 'morgen';
        }

        // Day after tomorrow from today (absolute)
        if ($altDateOnly->equalTo($today->copy()->addDays(2))) {
            return 'übermorgen';
        }

        // Within next 6 days - use day name
        $daysDiff = $today->diffInDays($altDateOnly, false);
        if ($daysDiff > 0 && $daysDiff <= 6) {
            return 'am ' . $alternativeDate->locale('de')->dayName;
        }

        // Next week (7-13 days)
        if ($daysDiff >= 7 && $daysDiff <= 13) {
            return 'nächste Woche ' . $alternativeDate->locale('de')->dayName;
        }

        // Fallback: full date
        return 'am ' . $alternativeDate->locale('de')->isoFormat('DD.MM.YYYY');
    }

    /**
     * Set tenant context for cache isolation
     * SECURITY: Prevents cross-tenant cache key collisions
     *
     * @param int|null $companyId
     * @param string|null $branchId UUID string identifier for branch
     */
    public function setTenantContext(?int $companyId, ?string $branchId = null): self
    {
        $this->companyId = $companyId;
        $this->branchId = $branchId;

        Log::debug('🔐 Tenant context set for alternative finder', [
            'company_id' => $companyId,
            'branch_id' => $branchId
        ]);

        return $this;
    }

    private function loadConfig(): void
    {
        $this->config = [
            'max_alternatives' => config('booking.max_alternatives', 2),
            'time_window_hours' => config('booking.time_window_hours', 2),
            'workdays' => config('booking.workdays', ['monday', 'tuesday', 'wednesday', 'thursday', 'friday']),
            'search_strategies' => config('booking.search_strategies', [
                self::STRATEGY_SAME_DAY,
                self::STRATEGY_NEXT_WORKDAY,
                self::STRATEGY_NEXT_WEEK,
                self::STRATEGY_NEXT_AVAILABLE
            ]),
            'business_hours' => [
                'start' => config('booking.business_hours_start', '09:00'),
                'end' => config('booking.business_hours_end', '18:00')
            ]
        ];

        $this->maxAlternatives = $this->config['max_alternatives'];
    }

    /**
     * Find alternative appointment slots when desired time is not available
     *
     * @param Carbon $desiredDateTime The desired date and time
     * @param int $durationMinutes Duration in minutes
     * @param int $eventTypeId Cal.com event type ID
     * @param int|null $customerId Customer ID to filter out existing appointments
     * @param string|null $preferredLanguage Preferred language for responses
     * @param TimePreference|null $timePreference Customer's time preference (window, from, range, etc.)
     * @return array Array with 'alternatives', 'responseText', and preference context
     */
    public function findAlternatives(
        Carbon $desiredDateTime,
        int $durationMinutes,
        int $eventTypeId,
        ?int $customerId = null,
        ?string $preferredLanguage = 'de',
        ?TimePreference $timePreference = null
    ): array {
        // Use actual dates without year mapping
        // Cal.com should now handle 2025 dates correctly

        // 🔧 NEW 2025-12-08: Default to ANY preference if not specified (backwards compatibility)
        if ($timePreference === null) {
            $timePreference = TimePreference::any();
        }

        // 🔍 Log TimePreference for debugging
        Log::info('🕐 findAlternatives: TimePreference context', [
            'type' => $timePreference->type,
            'window_start' => $timePreference->windowStart,
            'window_end' => $timePreference->windowEnd,
            'label' => $timePreference->label,
        ]);

        Log::info('🔍 Searching for appointment alternatives', [
            'desired' => $desiredDateTime->format('Y-m-d H:i'),
            'duration' => $durationMinutes,
            'eventTypeId' => $eventTypeId,
            'customer_id' => $customerId,
            'time_preference' => [
                'type' => $timePreference->type,
                'window_start' => $timePreference->windowStart,
                'window_end' => $timePreference->windowEnd,
                'label' => $timePreference->label,
            ]
        ]);

        // 🔧 FIX 2025-12-03: Store original requested time BEFORE any adjustments
        // This is the time the user originally asked for - we should NEVER offer this as an alternative
        $originalRequestedTimeStr = $desiredDateTime->copy()->setTimezone('Europe/Berlin')->format('Y-m-d H:i');

        // EDGE CASE FIX: Adjust times outside business hours to nearest business hour
        $adjustment = $this->adjustToBusinessHours($desiredDateTime);
        if ($adjustment['adjusted']) {
            Log::info('✅ Auto-adjusted request time', [
                'original' => $desiredDateTime->format('Y-m-d H:i'),
                'adjusted' => $adjustment['datetime']->format('Y-m-d H:i'),
                'reason' => $adjustment['reason']
            ]);
            // Use adjusted time for search
            $desiredDateTime = $adjustment['datetime'];
        }

        try {
            $alternatives = collect();
            $allMatchPreference = true; // Track if all alternatives match user's preference

            // 🔧 NEW 2025-12-08: Use time window-aware strategies if preference specified
            $strategies = $this->getStrategiesForPreference($timePreference);

            foreach ($strategies as $strategy) {
                if ($alternatives->count() >= $this->maxAlternatives) {
                    break;
                }

                $found = $this->executeStrategyWithPreference(
                    $strategy,
                    $desiredDateTime,
                    $durationMinutes,
                    $eventTypeId,
                    $timePreference
                );
                $alternatives = $alternatives->merge($found);
            }

            // 🔧 FIX 2025-12-03: Filter out the originally requested time from ALL alternatives
            // Prevents contradictory responses like "16:00 is not available, but 16:00 is available"
            $beforeFilterCount = $alternatives->count();
            $alternatives = $alternatives->filter(function($alt) use ($originalRequestedTimeStr) {
                $altTimeStr = $alt['datetime']->copy()->setTimezone('Europe/Berlin')->format('Y-m-d H:i');
                $shouldKeep = $altTimeStr !== $originalRequestedTimeStr;
                if (!$shouldKeep) {
                    Log::debug('🔧 Filtering out originally requested time from alternatives', [
                        'filtered_time' => $altTimeStr,
                        'original_requested' => $originalRequestedTimeStr
                    ]);
                }
                return $shouldKeep;
            });
            $afterFilterCount = $alternatives->count();

            if ($beforeFilterCount > $afterFilterCount) {
                Log::info('✅ Filtered out originally requested time from alternatives', [
                    'original_time' => $originalRequestedTimeStr,
                    'removed_count' => $beforeFilterCount - $afterFilterCount
                ]);
            }

            // 🔧 FIX 2025-11-18: Filter ALL slot conflicts (not just customer's own appointments)
            // ROOT CAUSE: System offered 14:55 even though it overlaps with 15:00-16:00 booking
            // This prevents offering slots that overlap with ANY existing appointment
            $beforeCount = $alternatives->count();
            $alternatives = $this->filterOutAllConflicts(
                $alternatives,
                $durationMinutes,
                $desiredDateTime
            );
            $afterCount = $alternatives->count();

            if ($beforeCount > $afterCount) {
                Log::info('✅ Filtered out conflicting alternatives', [
                    'before_count' => $beforeCount,
                    'after_count' => $afterCount,
                    'removed' => $beforeCount - $afterCount
                ]);
            }

            // 🔧 FIX 2025-10-13: Filter out customer's existing appointments
            // Prevents offering times where customer already has appointments
            if ($customerId) {
                $beforeCount = $alternatives->count();
                $alternatives = $this->filterOutCustomerConflicts(
                    $alternatives,
                    $customerId,
                    $desiredDateTime
                );
                $afterCount = $alternatives->count();

                if ($beforeCount > $afterCount) {
                    Log::info('✅ Filtered out customer conflicts', [
                        'customer_id' => $customerId,
                        'before_count' => $beforeCount,
                        'after_count' => $afterCount,
                        'removed' => $beforeCount - $afterCount
                    ]);
                }
            }

            // Rank and limit alternatives
            $ranked = $this->rankAlternatives($alternatives, $desiredDateTime);
            $limited = $ranked->take($this->maxAlternatives);

            // FALLBACK: If no real alternatives found, provide intelligent suggestions
            if ($limited->isEmpty()) {
                Log::warning('No Cal.com slots available, generating fallback suggestions');
                $limited = $this->generateFallbackAlternatives($desiredDateTime, $durationMinutes, $eventTypeId);
            }

            Log::info('✅ Found alternatives', [
                'count' => $limited->count(),
                'slots' => $limited->map(fn($alt) => $alt['datetime']->format('Y-m-d H:i'))
            ]);

            // Format the response with alternatives and response text
            $responseText = $this->formatResponseText($limited);

            // 🔧 NEW 2025-12-08: Check if all alternatives match the user's preference
            // Note: For ANY or EXACT preferences, legacy strategies are used and all alternatives implicitly match
            $isLegacyPreference = $timePreference->type === TimePreference::TYPE_ANY ||
                                  $timePreference->type === TimePreference::TYPE_EXACT;
            $allMatchPreference = $isLegacyPreference ||
                                  $limited->every(fn($alt) => $alt['matches_preference'] ?? false);

            // If we have a time window preference but no matches inside it
            $outsideWindowCount = $isLegacyPreference ? 0 :
                                  $limited->filter(fn($alt) => !($alt['matches_preference'] ?? true))->count();

            return [
                'alternatives' => $limited->toArray(),
                'responseText' => $responseText,
                // NEW: Preference context for intelligent agent responses
                'preference_context' => [
                    'type' => $timePreference->type,
                    'label' => $timePreference->label,
                    'window_start' => $timePreference->windowStart,
                    'window_end' => $timePreference->windowEnd,
                    'all_match_preference' => $allMatchPreference,
                    'outside_window_count' => $outsideWindowCount,
                    'suggested_followup' => $this->generatePreferenceFollowup($timePreference, $allMatchPreference, $limited)
                ]
            ];

        } catch (CalcomApiException $e) {
            // Cal.com API is down or unavailable
            Log::error('Cal.com API failure prevented availability search', [
                'error' => $e->getMessage(),
                'status_code' => $e->getStatusCode(),
                'desired_time' => $desiredDateTime->format('Y-m-d H:i'),
            ]);

            // Return graceful error message to user
            return [
                'alternatives' => [],
                'responseText' => $e->getUserMessage(),
                'error' => true,
                'error_type' => 'calcom_api_error',
                'error_code' => $e->getStatusCode()
            ];
        }
    }

    /**
     * Execute a specific search strategy
     */
    private function executeStrategy(
        string $strategy,
        Carbon $desiredDateTime,
        int $durationMinutes,
        int $eventTypeId
    ): Collection {
        return match($strategy) {
            self::STRATEGY_SAME_DAY => $this->findSameDayAlternatives($desiredDateTime, $durationMinutes, $eventTypeId),
            self::STRATEGY_NEXT_WORKDAY => $this->findNextWorkdayAlternatives($desiredDateTime, $durationMinutes, $eventTypeId),
            self::STRATEGY_NEXT_WEEK => $this->findNextWeekAlternatives($desiredDateTime, $durationMinutes, $eventTypeId),
            self::STRATEGY_NEXT_AVAILABLE => $this->findNextAvailableAlternatives($desiredDateTime, $durationMinutes, $eventTypeId),
            default => collect()
        };
    }

    /**
     * Find alternatives on the same day at different times
     */
    private function findSameDayAlternatives(
        Carbon $desiredDateTime,
        int $durationMinutes,
        int $eventTypeId
    ): Collection {
        $alternatives = collect();
        $date = $desiredDateTime->copy()->startOfDay();
        $windowHours = $this->config['time_window_hours'];

        // Check slots before desired time
        $earlierTime = $desiredDateTime->copy()->subHours($windowHours);
        if ($earlierTime->format('H:i') >= $this->config['business_hours']['start']) {
            $slots = $this->getAvailableSlots($earlierTime, $desiredDateTime, $eventTypeId);
            foreach ($slots as $slot) {
                // 🔧 FIX 2025-12-14: TIMEZONE BUG - Cal.com returns UTC times, must convert to Berlin
                // Bug: Agent said "06:00 Uhr" when Cal.com website showed 07:00 (first slot)
                // Root cause: Carbon::parse(UTC) was formatted without timezone conversion
                $slotTime = isset($slot['datetime'])
                    ? $slot['datetime']
                    : Carbon::parse($slot['time'])->setTimezone('Europe/Berlin');
                $alternatives->push([
                    'datetime' => $slotTime,
                    'type' => 'same_day_earlier',
                    'description' => $this->generateDateDescription($slotTime, $desiredDateTime) . ', ' . $slotTime->format('H:i') . ' Uhr',
                    'source' => 'calcom'
                ]);
            }
        }

        // Check slots after desired time
        $laterTime = $desiredDateTime->copy()->addHours($windowHours);
        if ($laterTime->format('H:i') <= $this->config['business_hours']['end']) {
            $slots = $this->getAvailableSlots($desiredDateTime, $laterTime, $eventTypeId);
            foreach ($slots as $slot) {
                // 🔧 FIX 2025-12-14: TIMEZONE BUG - Cal.com returns UTC times, must convert to Berlin
                $slotTime = isset($slot['datetime'])
                    ? $slot['datetime']
                    : Carbon::parse($slot['time'])->setTimezone('Europe/Berlin');
                $alternatives->push([
                    'datetime' => $slotTime,
                    'type' => 'same_day_later',
                    'description' => $this->generateDateDescription($slotTime, $desiredDateTime) . ', ' . $slotTime->format('H:i') . ' Uhr',
                    'source' => 'calcom'
                ]);
            }
        }

        return $alternatives;
    }

    /**
     * Find alternatives on next workday at same time
     *
     * 🔧 FIX 2025-10-25: Skip if desired date is already a weekend
     * Bug: When user requests Saturday, getNextWorkday(Sat) returned Monday (+2 days)
     * Fix: Only search next workday if desired date IS a workday
     */
    private function findNextWorkdayAlternatives(
        Carbon $desiredDateTime,
        int $durationMinutes,
        int $eventTypeId
    ): Collection {
        $alternatives = collect();

        // 🔧 FIX 2025-10-25: If desired date is NOT a workday (weekend), skip this strategy
        // Rationale: If user requests Sat 15:00, getNextWorkday(Sat) would return Mon 15:00 (+2 days)
        // But that creates a 2-day jump that confuses users
        // Instead, let other strategies (SAME_DAY info + NEXT_AVAILABLE) handle weekends
        if (!$this->isWorkday($desiredDateTime)) {
            Log::info('⏭️  Skipping NEXT_WORKDAY strategy for weekend date', [
                'desired_date' => $desiredDateTime->format('Y-m-d (l)'),
                'reason' => 'desired_date_is_not_workday'
            ]);
            return collect(); // Return empty - let next strategies handle it
        }

        $nextWorkday = $this->getNextWorkday($desiredDateTime);

        // Check same time on next workday
        $sameTimeNextDay = $nextWorkday->copy()->setTime(
            $desiredDateTime->hour,
            $desiredDateTime->minute
        );

        $slots = $this->getAvailableSlots(
            $sameTimeNextDay->copy()->subMinutes(30),
            $sameTimeNextDay->copy()->addMinutes(30),
            $eventTypeId
        );

        if (!empty($slots)) {
            $alternatives->push([
                'datetime' => $sameTimeNextDay,
                'type' => 'next_workday',
                'description' => $this->formatGermanWeekday($nextWorkday) . ', ' .
                                $sameTimeNextDay->format('d.m. \u\m H:i') . ' Uhr',
                'source' => 'calcom'
            ]);
        }

        return $alternatives;
    }

    /**
     * Find alternatives next week on same day and time
     */
    private function findNextWeekAlternatives(
        Carbon $desiredDateTime,
        int $durationMinutes,
        int $eventTypeId
    ): Collection {
        $alternatives = collect();
        $nextWeek = $desiredDateTime->copy()->addWeek();

        $slots = $this->getAvailableSlots(
            $nextWeek->copy()->subMinutes(30),
            $nextWeek->copy()->addMinutes(30),
            $eventTypeId
        );

        if (!empty($slots)) {
            $alternatives->push([
                'datetime' => $nextWeek,
                'type' => 'next_week',
                'description' => 'nächste Woche ' . $this->formatGermanWeekday($nextWeek) .
                               ', ' . $nextWeek->format('d.m. \u\m H:i') . ' Uhr',
                'source' => 'calcom'
            ]);
        }

        return $alternatives;
    }

    /**
     * Find next available slots regardless of day
     */
    private function findNextAvailableAlternatives(
        Carbon $desiredDateTime,
        int $durationMinutes,
        int $eventTypeId
    ): Collection {
        $alternatives = collect();
        $searchDays = 7; // Search up to 7 days ahead

        for ($i = 1; $i <= $searchDays; $i++) {
            $searchDate = $desiredDateTime->copy()->addDays($i);

            // Skip weekends if configured
            if (!$this->isWorkday($searchDate)) {
                continue;
            }

            $slots = $this->getAvailableSlots(
                $searchDate->copy()->setTime(9, 0),
                $searchDate->copy()->setTime(18, 0),
                $eventTypeId
            );

            if (!empty($slots)) {
                $bestSlot = $this->findClosestSlot($slots, $desiredDateTime);
                if ($bestSlot) {
                    // 🔧 FIX 2025-12-14: TIMEZONE BUG - Cal.com returns UTC times, must convert to Berlin
                    $slotTime = Carbon::parse($bestSlot['time'])->setTimezone('Europe/Berlin');
                    $alternatives->push([
                        'datetime' => $slotTime,
                        'type' => 'next_available',
                        'description' => $this->formatGermanWeekday($slotTime) . ', ' .
                                       $slotTime->format('d.m. \u\m H:i') . ' Uhr',
                        'source' => 'calcom'
                    ]);
                    break; // Only take first available
                }
            }
        }

        return $alternatives;
    }

    /**
     * Get available slots from Cal.com API
     */
    private function getAvailableSlots(
        Carbon $startTime,
        Carbon $endTime,
        int $eventTypeId
    ): array {
        // 🔒 SECURITY FIX 2025-11-19 (CRIT-002): Enforce tenant context before caching
        // Prevents cache poisoning and cross-tenant data leakage
        if ($this->companyId === null || $this->branchId === null) {
            Log::error('⚠️ SECURITY: getAvailableSlots called without tenant context', [
                'company_id' => $this->companyId,
                'branch_id' => $this->branchId,
                'event_type_id' => $eventTypeId,
                'backtrace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3)
            ]);
            throw new \RuntimeException(
                'SECURITY: Tenant context required. Call setTenantContext() before getAvailableSlots()'
            );
        }

        // SECURITY: Include company_id and branch_id in cache key to prevent cross-tenant data leakage
        $cacheKey = sprintf(
            'cal_slots_%d_%s_%d_%s_%s',
            $this->companyId,      // NOT ?? 0
            $this->branchId,       // NOT ?? 0
            $eventTypeId,
            $startTime->format('Y-m-d-H'),
            $endTime->format('Y-m-d-H')
        );

        // 🔧 FIX 2025-11-19: Reduce cache TTL from 300s to 60s
        // 🔧 FIX 2025-11-21: Further reduced from 60s to 30s
        // Reduces race condition window from 1 minute to 30 seconds
        return Cache::remember($cacheKey, 30, function() use ($startTime, $endTime, $eventTypeId) {
            try {
                $response = $this->calcomService->getAvailableSlots(
                    $eventTypeId,
                    $startTime->format('Y-m-d'),
                    $endTime->format('Y-m-d')
                );

                if ($response->successful()) {
                    $data = $response->json();
                    $allSlots = [];

                    // Cal.com returns slots grouped by date
                if (isset($data['data']['slots'])) {
                    foreach ($data['data']['slots'] as $date => $dateSlots) {
                        // Each date has an array of slot times
                        if (is_array($dateSlots)) {
                            foreach ($dateSlots as $slot) {
                                // Each slot is an object with a 'time' property
                                $slotTime = is_array($slot) && isset($slot['time']) ? $slot['time'] : $slot;
                                // 🔧 FIX 2025-12-14: TIMEZONE BUG - Cal.com returns UTC times, must convert to Berlin
                                // Bug: Agent said "06:00 Uhr" when Cal.com website showed 07:00 (first slot)
                                // Root cause: Carbon::parse(UTC) stored datetime without timezone conversion
                                $parsedTime = Carbon::parse($slotTime)->setTimezone('Europe/Berlin');

                                // Debug logging
                                Log::debug('Checking slot', [
                                    'slot_time' => $slotTime,
                                    'slot_utc' => Carbon::parse($slotTime)->format('Y-m-d H:i:s'),
                                    'slot_berlin' => $parsedTime->format('Y-m-d H:i:s'),
                                    'start' => $startTime->format('Y-m-d H:i:s'),
                                    'end' => $endTime->format('Y-m-d H:i:s'),
                                    'in_window' => ($parsedTime >= $startTime && $parsedTime <= $endTime)
                                ]);

                                // Add all slots from the requested date range
                                // Don't filter by time window here - let the caller decide
                                $allSlots[] = [
                                    'time' => $slotTime,
                                    'datetime' => $parsedTime,
                                    'date' => $date
                                ];
                            }
                        }
                    }
                }

                return $allSlots;
            }

            return [];

        } catch (CalcomApiException $e) {
            // Log Cal.com API error
            Log::error('Cal.com API error during availability check', $e->getErrorDetails());

            // Re-throw exception to be handled by caller
            // This allows the application to distinguish between "no slots" and "API down"
            throw $e;
        }
        });
    }

    /**
     * Rank alternatives by proximity to desired time and user preferences
     *
     * 🔧 FIX 2025-10-19: Prefer LATER slots over EARLIER for afternoon requests
     * Bug: User requests 13:00, system suggested 10:30 (3h earlier) instead of 14:00 (1h later)
     * User expectation: If I want afternoon, suggest afternoon alternatives first!
     */
    private function rankAlternatives(Collection $alternatives, Carbon $desiredDateTime): Collection
    {
        return $alternatives->map(function($alt) use ($desiredDateTime) {
            $minutesDiff = abs($desiredDateTime->diffInMinutes($alt['datetime']));

            // Scoring based on proximity and type
            $score = 10000 - $minutesDiff; // Base score from time proximity (most important!)

            // 🔧 FIX 2025-10-19: Smart directional preference based on time of day
            // For afternoon requests (>= 12:00), prefer LATER slots
            // For morning requests (< 12:00), prefer EARLIER slots
            $isAfternoonRequest = $desiredDateTime->hour >= 12;
            $isLaterSlot = $alt['datetime']->greaterThan($desiredDateTime);

            $score += match($alt['type']) {
                // Same day alternatives: prefer direction matching user's preference
                'same_day_later' => $isAfternoonRequest ? 500 : 300,   // Higher if user wants afternoon
                'same_day_earlier' => $isAfternoonRequest ? 300 : 500, // Lower if user wants afternoon
                'next_workday' => 250,
                'next_week' => 150,
                'next_available' => 100,
                default => 0
            };

            $alt['score'] = $score;
            return $alt;
        })->sortByDesc('score')->values();
    }

    /**
     * Check if a date is a workday
     */
    private function isWorkday(Carbon $date): bool
    {
        $dayName = strtolower($date->format('l'));
        return in_array($dayName, $this->config['workdays']);
    }

    /**
     * Get next workday from given date
     */
    private function getNextWorkday(Carbon $date): Carbon
    {
        $next = $date->copy()->addDay();
        while (!$this->isWorkday($next)) {
            $next->addDay();
        }
        return $next;
    }

    /**
     * Find slot closest to desired time
     */
    private function findClosestSlot(array $slots, Carbon $desiredTime): ?array
    {
        if (empty($slots)) {
            return null;
        }

        $desiredHour = $desiredTime->hour;
        $desiredMinute = $desiredTime->minute;

        usort($slots, function($a, $b) use ($desiredHour, $desiredMinute) {
            $timeA = Carbon::parse($a['time']);
            $timeB = Carbon::parse($b['time']);

            $diffA = abs($timeA->hour * 60 + $timeA->minute - ($desiredHour * 60 + $desiredMinute));
            $diffB = abs($timeB->hour * 60 + $timeB->minute - ($desiredHour * 60 + $desiredMinute));

            return $diffA - $diffB;
        });

        return $slots[0];
    }

    /**
     * Format weekday name in German
     */
    private function formatGermanWeekday(Carbon $date): string
    {
        $weekdays = [
            'Monday' => 'Montag',
            'Tuesday' => 'Dienstag',
            'Wednesday' => 'Mittwoch',
            'Thursday' => 'Donnerstag',
            'Friday' => 'Freitag',
            'Saturday' => 'Samstag',
            'Sunday' => 'Sonntag'
        ];

        return $weekdays[$date->format('l')] ?? $date->format('l');
    }

    /**
     * Build response text for Retell agent
     * Voice-optimized format without line breaks
     */
    public function buildAlternativeResponse(array $alternatives, string $language = 'de'): string
    {
        if (empty($alternatives)) {
            return $language === 'de'
                ? "Leider sind keine Termine in den nächsten Tagen verfügbar. Möchten Sie es zu einem späteren Zeitpunkt versuchen oder soll ich Sie zurückrufen?"
                : "Unfortunately, no appointments are available in the coming days. Would you like to try a later date or should I call you back?";
        }

        $response = $language === 'de'
            ? "Der gewünschte Termin ist leider nicht verfügbar. Ich kann Ihnen folgende Alternativen anbieten: "
            : "The requested appointment is not available. I can offer you the following alternatives: ";

        // Voice-friendly format with "oder" between alternatives
        foreach ($alternatives as $i => $alt) {
            if ($i > 0) {
                $response .= $language === 'de' ? " oder " : " or ";
            }
            $response .= $alt['description'];
        }

        $response .= $language === 'de'
            ? ". Welcher Termin würde Ihnen besser passen?"
            : ". Which appointment would suit you better?";

        return $response;
    }

    /**
     * Format the alternatives into a human-readable response text
     * Optimized for voice output to Retell AI agent
     */
    private function formatResponseText(Collection $alternatives): string
    {
        if ($alternatives->isEmpty()) {
            return "Leider konnte ich keine verfügbaren Termine finden. Möchten Sie es zu einem anderen Zeitpunkt versuchen oder soll ich Sie zurückrufen?";
        }

        $text = "Ich habe leider keinen Termin zu Ihrer gewünschten Zeit gefunden, aber ich kann Ihnen folgende Alternativen anbieten: ";

        // Format alternatives in a voice-friendly way (no line breaks for voice)
        foreach ($alternatives as $index => $alt) {
            if ($index > 0) {
                $text .= " oder ";
            }
            $text .= $alt['description'];
        }

        $text .= ". Welcher Termin würde Ihnen besser passen?";

        return $text;
    }

    /**
     * Generate fallback alternatives when Cal.com has no availability
     * NOW WITH REAL CAL.COM VALIDATION - No more fake suggestions!
     */
    private function generateFallbackAlternatives(Carbon $desiredDateTime, int $durationMinutes, int $eventTypeId): Collection
    {
        Log::info('🔍 Generating fallback alternatives with Cal.com validation', [
            'desired_datetime' => $desiredDateTime->format('Y-m-d H:i'),
            'duration_minutes' => $durationMinutes,
            'event_type_id' => $eventTypeId
        ]);

        // Step 1: Generate candidate times (algorithmic)
        $candidates = $this->generateCandidateTimes($desiredDateTime);

        Log::debug('📋 Generated candidate times', [
            'count' => $candidates->count(),
            'candidates' => $candidates->map(fn($c) => [
                'datetime' => $c['datetime']->format('Y-m-d H:i'),
                'type' => $c['type']
            ])->toArray()
        ]);

        // Step 2: Verify each candidate against Cal.com
        $verified = collect();

        // Use the eventTypeId parameter passed to this function
        // No need for property checks or config fallback

        foreach ($candidates as $candidate) {
            $datetime = $candidate['datetime'];

            // Get Cal.com slots for the candidate's date
            $startOfDay = $datetime->copy()->startOfDay()->setTime(9, 0);
            $endOfDay = $datetime->copy()->startOfDay()->setTime(18, 0);

            $slots = $this->getAvailableSlots($startOfDay, $endOfDay, $eventTypeId);

            // Check if this specific time exists in Cal.com slots
            if ($this->isTimeSlotAvailable($datetime, $slots)) {
                Log::debug('✅ Candidate verified with Cal.com', [
                    'datetime' => $datetime->format('Y-m-d H:i'),
                    'type' => $candidate['type']
                ]);

                // Add Cal.com source to mark as verified
                $candidate['source'] = 'calcom';
                $verified->push($candidate);

                // Stop once we have enough verified alternatives
                if ($verified->count() >= $this->maxAlternatives) {
                    break;
                }
            } else {
                Log::debug('❌ Candidate NOT available in Cal.com', [
                    'datetime' => $datetime->format('Y-m-d H:i'),
                    'type' => $candidate['type'],
                    'available_slots_count' => count($slots)
                ]);
            }
        }

        // Step 3: If no candidates verified, brute force search for next available slot
        if ($verified->isEmpty()) {
            Log::warning('⚠️ No fallback candidates available, searching for next available slot');

            $nextSlot = $this->findNextAvailableSlot($desiredDateTime, $durationMinutes, $eventTypeId);

            if ($nextSlot) {
                Log::info('✅ Found next available slot through brute force search', [
                    'datetime' => $nextSlot['datetime']->format('Y-m-d H:i')
                ]);
                $verified->push($nextSlot);
            } else {
                Log::warning('⚠️ No available slots found in next 14 days');
            }
        }

        Log::info('✅ Fallback alternatives generation complete', [
            'verified_count' => $verified->count(),
            'alternatives' => $verified->map(fn($v) => $v['datetime']->format('Y-m-d H:i'))->toArray()
        ]);

        return $verified->take($this->maxAlternatives);
    }

    /**
     * Generate candidate times without Cal.com verification
     * Returns algorithmically reasonable times that MUST be validated later
     */
    private function generateCandidateTimes(Carbon $desiredDateTime): Collection
    {
        $candidates = collect();

        // Candidate 1: Same day, 2 hours earlier (if hour >= 10)
        if ($desiredDateTime->hour >= 10) {
            $earlier = $desiredDateTime->copy()->subHours(2);

            if ($this->isWithinBusinessHours($earlier)) {
                $candidates->push([
                    'datetime' => $earlier,
                    'type' => 'same_day_earlier',
                    'description' => $this->generateDateDescription($earlier, $desiredDateTime) . ', ' . $earlier->format('H:i') . ' Uhr',
                    'rank' => 90
                ]);
            }
        }

        // Candidate 2: Same day, 2 hours later (if hour <= 16)
        if ($desiredDateTime->hour <= 16) {
            $later = $desiredDateTime->copy()->addHours(2);

            if ($this->isWithinBusinessHours($later)) {
                $candidates->push([
                    'datetime' => $later,
                    'type' => 'same_day_later',
                    'description' => $this->generateDateDescription($later, $desiredDateTime) . ', ' . $later->format('H:i') . ' Uhr',
                    'rank' => 85
                ]);
            }
        }

        // Candidate 3: Next workday, same time
        $nextWorkday = $this->getNextWorkday($desiredDateTime);
        $nextWorkdayTime = $nextWorkday->copy()->setTime($desiredDateTime->hour, $desiredDateTime->minute);

        if ($this->isWithinBusinessHours($nextWorkdayTime)) {
            $candidates->push([
                'datetime' => $nextWorkdayTime,
                'type' => 'next_workday',
                'description' => $this->formatGermanWeekday($nextWorkday) . ', ' .
                              $nextWorkday->format('d.m.') . ' um ' . $desiredDateTime->format('H:i') . ' Uhr',
                'rank' => 80
            ]);
        }

        // Candidate 4: Same weekday next week
        $nextWeek = $desiredDateTime->copy()->addWeek();

        if ($this->isWithinBusinessHours($nextWeek)) {
            $candidates->push([
                'datetime' => $nextWeek,
                'type' => 'next_week',
                'description' => 'nächste Woche ' . $this->formatGermanWeekday($nextWeek) .
                              ', ' . $nextWeek->format('d.m.') . ' um ' . $nextWeek->format('H:i') . ' Uhr',
                'rank' => 70
            ]);
        }

        return $candidates->sortByDesc('rank');
    }

    /**
     * Check if a specific time slot exists in Cal.com slots array
     * Uses 15-minute tolerance window for matching
     */
    private function isTimeSlotAvailable(Carbon $targetTime, array $slots): bool
    {
        if (empty($slots)) {
            return false;
        }

        // Check if any slot matches the target time (within 15-minute tolerance)
        foreach ($slots as $slot) {
            // 🔧 FIX 2025-12-14: TIMEZONE BUG - Cal.com returns UTC times, must convert to Berlin
            $slotTime = isset($slot['datetime'])
                ? $slot['datetime']
                : Carbon::parse($slot['time'])->setTimezone('Europe/Berlin');

            // Match if within 15 minutes
            $diffMinutes = abs($slotTime->diffInMinutes($targetTime));

            if ($diffMinutes <= 15) {
                Log::debug('🎯 Time slot match found', [
                    'target' => $targetTime->format('Y-m-d H:i'),
                    'slot' => $slotTime->format('Y-m-d H:i'),
                    'diff_minutes' => $diffMinutes
                ]);
                return true;
            }
        }

        return false;
    }

    /**
     * Brute force search for next available slot up to 14 days ahead
     * Used when no candidate times are available
     */
    private function findNextAvailableSlot(Carbon $desiredDateTime, int $durationMinutes, int $eventTypeId): ?array
    {
        $maxDays = 14;

        Log::info('🔍 Starting brute force search for next available slot', [
            'start_date' => $desiredDateTime->format('Y-m-d'),
            'max_days' => $maxDays,
            'event_type_id' => $eventTypeId
        ]);

        for ($dayOffset = 0; $dayOffset <= $maxDays; $dayOffset++) {
            $searchDate = $desiredDateTime->copy()->addDays($dayOffset);

            // Skip weekends if not a workday
            if (!$this->isWorkday($searchDate)) {
                Log::debug('⏭️ Skipping non-workday', [
                    'date' => $searchDate->format('Y-m-d'),
                    'day' => $searchDate->format('l')
                ]);
                continue;
            }

            // Get all slots for this day
            $startOfDay = $searchDate->copy()->startOfDay()->setTime(9, 0);
            $endOfDay = $searchDate->copy()->startOfDay()->setTime(18, 0);

            $slots = $this->getAvailableSlots($startOfDay, $endOfDay, $eventTypeId);

            if (!empty($slots)) {
                // Find the first slot within business hours
                foreach ($slots as $slot) {
                    // 🔧 FIX 2025-12-14: TIMEZONE BUG - Cal.com returns UTC times, must convert to Berlin
                    $slotTime = isset($slot['datetime'])
                        ? $slot['datetime']
                        : Carbon::parse($slot['time'])->setTimezone('Europe/Berlin');

                    if ($this->isWithinBusinessHours($slotTime)) {
                        Log::info('✅ Found available slot', [
                            'datetime' => $slotTime->format('Y-m-d H:i'),
                            'days_ahead' => $dayOffset
                        ]);

                        return [
                            'datetime' => $slotTime,
                            'type' => 'next_available',
                            'description' => $this->formatGermanWeekday($slotTime) . ', ' .
                                          $slotTime->format('d.m.') . ' um ' . $slotTime->format('H:i') . ' Uhr',
                            'rank' => 60,
                            'source' => 'calcom'
                        ];
                    }
                }
            }

            Log::debug('❌ No slots available on date', [
                'date' => $searchDate->format('Y-m-d')
            ]);
        }

        Log::warning('⚠️ No available slots found in next ' . $maxDays . ' days');
        return null;
    }

    /**
     * Adjust time to nearest business hour if outside business hours
     *
     * Handles edge cases:
     * - Before 09:00 → Adjust to 09:00 same day
     * - After 18:00 → Adjust to 09:00 next workday
     * - Weekend → Adjust to 09:00 next Monday
     *
     * @param Carbon $datetime Original requested time
     * @return array ['datetime' => Carbon, 'adjusted' => bool, 'reason' => string|null]
     */
    private function adjustToBusinessHours(Carbon $datetime): array
    {
        $original = $datetime->copy();
        $adjusted = $datetime->copy();
        $reason = null;

        // Parse business hours
        [$startHour, $startMinute] = explode(':', $this->config['business_hours']['start']);
        [$endHour, $endMinute] = explode(':', $this->config['business_hours']['end']);

        $businessStart = (int) $startHour * 60 + (int) $startMinute;  // minutes since midnight
        $businessEnd = (int) $endHour * 60 + (int) $endMinute;
        $requestedTime = $adjusted->hour * 60 + $adjusted->minute;

        // Case 1: Before business hours (e.g., 08:00 → 09:00)
        if ($requestedTime < $businessStart) {
            $adjusted->setTime((int) $startHour, (int) $startMinute, 0);
            $reason = sprintf(
                'Requested time %s is before business hours. Adjusted to opening time %s.',
                $original->format('H:i'),
                $this->config['business_hours']['start']
            );

            Log::info('⏰ Adjusted early request to business hours', [
                'original' => $original->format('Y-m-d H:i'),
                'adjusted' => $adjusted->format('Y-m-d H:i'),
                'reason' => 'before_opening'
            ]);

            return [
                'datetime' => $adjusted,
                'adjusted' => true,
                'reason' => $reason
            ];
        }

        // Case 2: After business hours (e.g., 19:00 → next day 09:00)
        if ($requestedTime >= $businessEnd) {
            // Move to next workday at opening time
            $nextDay = $this->getNextWorkday($adjusted);
            $nextDay->setTime((int) $startHour, (int) $startMinute, 0);

            $reason = sprintf(
                'Requested time %s is after business hours. Adjusted to next available opening at %s.',
                $original->format('H:i'),
                $nextDay->format('D, d.m.Y H:i')
            );

            Log::info('⏰ Adjusted late request to next business day', [
                'original' => $original->format('Y-m-d H:i'),
                'adjusted' => $nextDay->format('Y-m-d H:i'),
                'reason' => 'after_closing'
            ]);

            return [
                'datetime' => $nextDay,
                'adjusted' => true,
                'reason' => $reason
            ];
        }

        // Case 3: Weekend or holiday (already handled by getNextWorkday if needed)
        $isWorkday = in_array(strtolower($adjusted->format('l')), $this->config['workdays']);
        if (!$isWorkday) {
            $nextDay = $this->getNextWorkday($adjusted);
            $nextDay->setTime((int) $startHour, (int) $startMinute, 0);

            $reason = sprintf(
                'Requested date %s is not a workday. Adjusted to next available opening at %s.',
                $original->format('D, d.m.Y'),
                $nextDay->format('D, d.m.Y H:i')
            );

            Log::info('⏰ Adjusted weekend request to next workday', [
                'original' => $original->format('Y-m-d H:i'),
                'adjusted' => $nextDay->format('Y-m-d H:i'),
                'reason' => 'weekend'
            ]);

            return [
                'datetime' => $nextDay,
                'adjusted' => true,
                'reason' => $reason
            ];
        }

        // Time is already within business hours
        return [
            'datetime' => $adjusted,
            'adjusted' => false,
            'reason' => null
        ];
    }

    /**
     * Validate if time is within business hours (09:00-18:00)
     */
    private function isWithinBusinessHours(Carbon $datetime): bool
    {
        $businessStart = $this->config['business_hours']['start'];
        $businessEnd = $this->config['business_hours']['end'];

        $timeString = $datetime->format('H:i');

        $isWithin = $timeString >= $businessStart && $timeString <= $businessEnd;

        if (!$isWithin) {
            Log::debug('⏰ Time outside business hours', [
                'time' => $timeString,
                'business_start' => $businessStart,
                'business_end' => $businessEnd
            ]);
        }

        return $isWithin;
    }

    /**
     * Filter out alternatives that conflict with customer's existing appointments
     *
     * 🔧 FIX 2025-10-13: Prevents offering times where customer already has appointments
     * This resolves the bug where system offered 14:00 when customer already had appointment at that time
     *
     * @param Collection $alternatives Collection of alternative time slots from Cal.com
     * @param int $customerId Customer ID to check existing appointments
     * @param Carbon $searchDate Date to search for existing appointments
     * @return Collection Filtered alternatives without conflicts
     */
    private function filterOutCustomerConflicts(
        Collection $alternatives,
        int $customerId,
        Carbon $searchDate
    ): Collection {
        // Get customer's existing appointments for the search date
        $existingAppointments = \App\Models\Appointment::where('customer_id', $customerId)
            ->where('status', '!=', 'cancelled')
            ->whereDate('starts_at', $searchDate->format('Y-m-d'))
            ->get();

        // If no existing appointments, no conflicts possible
        if ($existingAppointments->isEmpty()) {
            Log::debug('No existing appointments for customer on this date', [
                'customer_id' => $customerId,
                'date' => $searchDate->format('Y-m-d')
            ]);
            return $alternatives;
        }

        Log::info('🔍 Checking alternatives against existing appointments', [
            'customer_id' => $customerId,
            'date' => $searchDate->format('Y-m-d'),
            'existing_count' => $existingAppointments->count(),
            'existing_times' => $existingAppointments->map(fn($appt) => $appt->starts_at->format('H:i'))->toArray(),
            'alternatives_count' => $alternatives->count()
        ]);

        // Filter out conflicting times
        $filtered = $alternatives->filter(function($alt) use ($existingAppointments) {
            $altTime = $alt['datetime'];

            foreach ($existingAppointments as $appt) {
                // Check for time overlap (alternative time falls within existing appointment)
                // Use between() with exclusive boundaries to check if times overlap
                $startsWithin = $altTime->between($appt->starts_at, $appt->ends_at, false);

                // Also check if alternative ends within existing appointment
                $altEnd = $altTime->copy()->addMinutes(30); // Assume 30min duration
                $endsWithin = $altEnd->between($appt->starts_at, $appt->ends_at, false);

                // Check if alternative completely encompasses existing appointment
                $encompassesAppointment = $altTime->lte($appt->starts_at) && $altEnd->gte($appt->ends_at);

                if ($startsWithin || $endsWithin || $encompassesAppointment) {
                    Log::debug('🚫 Filtered out conflicting time', [
                        'alternative_time' => $altTime->format('H:i'),
                        'conflicts_with_appointment' => $appt->id,
                        'appointment_time' => $appt->starts_at->format('H:i') . '-' . $appt->ends_at->format('H:i'),
                        'conflict_type' => $startsWithin ? 'starts_within' : ($endsWithin ? 'ends_within' : 'encompasses')
                    ]);
                    return false;  // Exclude this alternative (conflict detected)
                }
            }

            return true;  // No conflict, keep this alternative
        });

        $removedCount = $alternatives->count() - $filtered->count();
        if ($removedCount > 0) {
            Log::info('✅ Removed conflicting alternatives', [
                'customer_id' => $customerId,
                'removed_count' => $removedCount,
                'remaining_count' => $filtered->count(),
                'removed_times' => $alternatives->diff($filtered)->map(fn($alt) => $alt['datetime']->format('H:i'))->toArray()
            ]);
        }

        return $filtered;
    }

    /**
     * 🔧 FIX 2025-11-18: Filter out alternatives that overlap with ANY existing appointment
     * 🔒 SECURITY FIX 2025-11-19 (CRIT-003): Add company_id filter for multi-tenant isolation
     *
     * ROOT CAUSE: System offered 14:55 when 15:00 was occupied
     * PROBLEM: filterOutCustomerConflicts only checks customer's OWN appointments
     * SOLUTION: Check against ALL appointments in the system for the branch
     *
     * @param Collection $alternatives Alternative time slots to validate
     * @param int $durationMinutes Service duration in minutes
     * @param Carbon $searchDate Date to check for existing appointments
     * @return Collection Filtered alternatives without ANY conflicts
     */
    private function filterOutAllConflicts(
        Collection $alternatives,
        int $durationMinutes,
        Carbon $searchDate
    ): Collection {
        // 🔒 SECURITY: Enforce tenant context before conflict checking
        if ($this->companyId === null || $this->branchId === null) {
            Log::error('⚠️ SECURITY: filterOutAllConflicts called without tenant context', [
                'company_id' => $this->companyId,
                'branch_id' => $this->branchId,
                'backtrace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3)
            ]);
            throw new \RuntimeException(
                'SECURITY: Tenant context required. Call setTenantContext() before filterOutAllConflicts()'
            );
        }

        // Get ALL appointments for this company AND branch on the search date
        // 🔒 SECURITY: Filter by BOTH company_id AND branch_id to prevent cross-tenant data leakage
        $existingAppointments = \App\Models\Appointment::where('company_id', $this->companyId)
            ->where('branch_id', $this->branchId)
            ->where('status', '!=', 'cancelled')
            ->whereDate('starts_at', $searchDate->format('Y-m-d'))
            ->get();

        if ($existingAppointments->isEmpty()) {
            Log::debug('No existing appointments on this date', [
                'company_id' => $this->companyId,
                'branch_id' => $this->branchId,
                'date' => $searchDate->format('Y-m-d')
            ]);
            return $alternatives;
        }

        Log::info('🔍 Checking alternatives against ALL existing appointments', [
            'company_id' => $this->companyId,
            'branch_id' => $this->branchId,
            'date' => $searchDate->format('Y-m-d'),
            'existing_count' => $existingAppointments->count(),
            'alternatives_count' => $alternatives->count()
        ]);

        // Filter out conflicting alternatives
        $filtered = $alternatives->filter(function($alt) use ($existingAppointments, $durationMinutes) {
            $altStart = $alt['datetime'];
            $altEnd = $altStart->copy()->addMinutes($durationMinutes);

            foreach ($existingAppointments as $appt) {
                // Check for overlap: two time ranges overlap if start1 < end2 AND start2 < end1
                $overlaps = $altStart < $appt->ends_at && $appt->starts_at < $altEnd;

                if ($overlaps) {
                    Log::debug('🚫 Filtered out overlapping alternative', [
                        'alternative_time' => $altStart->format('H:i') . '-' . $altEnd->format('H:i'),
                        'conflicts_with_appointment' => $appt->id,
                        'appointment_time' => $appt->starts_at->format('H:i') . '-' . $appt->ends_at->format('H:i'),
                        'overlap_minutes' => $this->calculateOverlapMinutes($altStart, $altEnd, $appt->starts_at, $appt->ends_at)
                    ]);
                    return false;  // Exclude this alternative
                }
            }

            return true;  // No conflicts
        });

        return $filtered;
    }

    /**
     * Calculate overlap duration between two time ranges
     *
     * @param Carbon $start1 Start of first time range
     * @param Carbon $end1 End of first time range
     * @param Carbon $start2 Start of second time range
     * @param Carbon $end2 End of second time range
     * @return int Overlap duration in minutes (0 if no overlap)
     */
    private function calculateOverlapMinutes(Carbon $start1, Carbon $end1, Carbon $start2, Carbon $end2): int
    {
        $overlapStart = max($start1, $start2);
        $overlapEnd = min($end1, $end2);

        if ($overlapStart >= $overlapEnd) {
            return 0;
        }

        return $overlapStart->diffInMinutes($overlapEnd);
    }

    // ═══════════════════════════════════════════════════════════════════════════════
    // 🔧 NEW 2025-12-08: TimePreference-aware Strategy Methods
    // ═══════════════════════════════════════════════════════════════════════════════

    /**
     * Generate intelligent follow-up message based on preference match results
     *
     * @param TimePreference $preference Customer's time preference
     * @param bool $allMatch Whether all alternatives match the preference
     * @param Collection $alternatives The found alternatives
     * @return string|null Suggested follow-up message (German)
     */
    private function generatePreferenceFollowup(TimePreference $preference, bool $allMatch, Collection $alternatives): ?string
    {
        // No follow-up needed for ANY preference or if all alternatives match
        if ($preference->type === TimePreference::TYPE_ANY || $allMatch) {
            return null;
        }

        // No alternatives found at all
        if ($alternatives->isEmpty()) {
            return match($preference->type) {
                TimePreference::TYPE_WINDOW => "Möchten Sie, dass ich an einem anderen Tag {$preference->label} schaue?",
                TimePreference::TYPE_FROM => "Soll ich an einem anderen Tag {$preference->label} nachschauen?",
                TimePreference::TYPE_RANGE => "Darf ich an einem anderen Tag in diesem Zeitraum schauen?",
                default => null
            };
        }

        // Alternatives found but not all match preference
        $preferenceLabel = $preference->getGermanLabel();
        return "Die vorgeschlagenen Zeiten liegen außerhalb von {$preferenceLabel}. Käme das auch infrage, oder soll ich an einem anderen Tag {$preferenceLabel} suchen?";
    }

    /**
     * Get search strategies based on time preference type
     *
     * For TIME_EXACT: Use legacy strategies (±2h around exact time)
     * For TIME_WINDOW/FROM/RANGE: Prioritize same window, then same window other days
     * For TIME_ANY: Use all strategies equally
     *
     * @param TimePreference $preference Customer's time preference
     * @return array Strategy constants in priority order
     */
    private function getStrategiesForPreference(TimePreference $preference): array
    {
        // For EXACT time or ANY preference, use legacy behavior
        if ($preference->type === TimePreference::TYPE_EXACT || $preference->type === TimePreference::TYPE_ANY) {
            return [
                self::STRATEGY_SAME_DAY,
                self::STRATEGY_NEXT_WORKDAY,
                self::STRATEGY_NEXT_WEEK,
                self::STRATEGY_NEXT_AVAILABLE
            ];
        }

        // For time windows (WINDOW, FROM, RANGE): Prioritize window-aware strategies
        return [
            self::STRATEGY_SAME_DAY_IN_WINDOW,      // 1. Same day, within preferred window
            self::STRATEGY_SAME_WINDOW_OTHER_DAYS,  // 2. Other days, same time window
            self::STRATEGY_SAME_DAY,                // 3. Same day, outside window (fallback)
            self::STRATEGY_NEXT_AVAILABLE           // 4. Any available slot (last resort)
        ];
    }

    /**
     * Execute a strategy with TimePreference awareness
     *
     * Routes to preference-aware methods for new strategies,
     * falls back to legacy executeStrategy for old ones.
     *
     * @param string $strategy Strategy constant
     * @param Carbon $desiredDateTime Desired date/time
     * @param int $durationMinutes Service duration
     * @param int $eventTypeId Cal.com event type ID
     * @param TimePreference $timePreference Customer's time preference
     * @return Collection Found alternatives
     */
    private function executeStrategyWithPreference(
        string $strategy,
        Carbon $desiredDateTime,
        int $durationMinutes,
        int $eventTypeId,
        TimePreference $timePreference
    ): Collection {
        return match($strategy) {
            // New TimePreference-aware strategies
            self::STRATEGY_SAME_DAY_IN_WINDOW => $this->findSameDayInWindow(
                $desiredDateTime,
                $durationMinutes,
                $eventTypeId,
                $timePreference
            ),
            self::STRATEGY_SAME_WINDOW_OTHER_DAYS => $this->findSameWindowOtherDays(
                $desiredDateTime,
                $durationMinutes,
                $eventTypeId,
                $timePreference
            ),
            // Legacy strategies (delegate to original method)
            default => $this->executeStrategy($strategy, $desiredDateTime, $durationMinutes, $eventTypeId)
        };
    }

    /**
     * Find alternatives on the same day within the customer's preferred time window
     *
     * Example: Customer wants "Vormittag" (09:00-12:00)
     * → Only search within 09:00-12:00 on the same day
     *
     * @param Carbon $desiredDateTime Original desired date/time
     * @param int $durationMinutes Service duration
     * @param int $eventTypeId Cal.com event type ID
     * @param TimePreference $preference Customer's time window preference
     * @return Collection Alternatives within the time window
     */
    private function findSameDayInWindow(
        Carbon $desiredDateTime,
        int $durationMinutes,
        int $eventTypeId,
        TimePreference $preference
    ): Collection {
        $alternatives = collect();

        // If no window specified, fall back to legacy same-day search
        if (!$preference->hasWindow()) {
            Log::debug('⏭️ No time window in preference, skipping SAME_DAY_IN_WINDOW');
            return $alternatives;
        }

        $date = $desiredDateTime->copy()->startOfDay();

        // Build time range from preference
        $windowStart = $date->copy()->setTimeFromTimeString($preference->windowStart . ':00');
        $windowEnd = $date->copy()->setTimeFromTimeString($preference->windowEnd . ':00');

        // Clamp to business hours
        $businessStart = $date->copy()->setTimeFromTimeString($this->config['business_hours']['start'] . ':00');
        $businessEnd = $date->copy()->setTimeFromTimeString($this->config['business_hours']['end'] . ':00');

        if ($windowStart < $businessStart) {
            $windowStart = $businessStart;
        }
        if ($windowEnd > $businessEnd) {
            $windowEnd = $businessEnd;
        }

        Log::info('🕐 Searching same day in time window', [
            'date' => $date->format('Y-m-d'),
            'window_start' => $windowStart->format('H:i'),
            'window_end' => $windowEnd->format('H:i'),
            'preference_type' => $preference->type,
            'preference_label' => $preference->label
        ]);

        // Get available slots within the window
        $slots = $this->getAvailableSlots($windowStart, $windowEnd, $eventTypeId);

        foreach ($slots as $slot) {
            // 🔧 FIX 2025-12-14: TIMEZONE BUG - Cal.com returns UTC times, must convert to Berlin
            $slotTime = isset($slot['datetime'])
                ? $slot['datetime']
                : Carbon::parse($slot['time'])->setTimezone('Europe/Berlin');

            // Double-check slot is within the preference window
            if ($preference->containsDateTime($slotTime)) {
                $alternatives->push([
                    'datetime' => $slotTime,
                    'type' => 'same_day_in_window',
                    'description' => $this->generateDateDescription($slotTime, $desiredDateTime) . ', ' . $slotTime->format('H:i') . ' Uhr',
                    'source' => 'calcom',
                    'matches_preference' => true
                ]);
            }
        }

        Log::info('✅ Found alternatives in same day window', [
            'count' => $alternatives->count(),
            'slots' => $alternatives->map(fn($alt) => $alt['datetime']->format('H:i'))->toArray()
        ]);

        return $alternatives;
    }

    /**
     * Find alternatives on other days within the same time window
     *
     * Example: Customer wants "Vormittag" (09:00-12:00) but today is full
     * → Search 09:00-12:00 on subsequent workdays
     *
     * @param Carbon $startDate Date to start searching from
     * @param int $durationMinutes Service duration
     * @param int $eventTypeId Cal.com event type ID
     * @param TimePreference $preference Customer's time window preference
     * @param int $daysToSearch Max days to search ahead (default: 7)
     * @return Collection Alternatives on other days within the same window
     */
    private function findSameWindowOtherDays(
        Carbon $startDate,
        int $durationMinutes,
        int $eventTypeId,
        TimePreference $preference,
        int $daysToSearch = 7
    ): Collection {
        $alternatives = collect();

        // If no window specified, skip this strategy
        if (!$preference->hasWindow()) {
            Log::debug('⏭️ No time window in preference, skipping SAME_WINDOW_OTHER_DAYS');
            return $alternatives;
        }

        Log::info('🕐 Searching other days with same time window', [
            'start_date' => $startDate->format('Y-m-d'),
            'days_to_search' => $daysToSearch,
            'window_start' => $preference->windowStart,
            'window_end' => $preference->windowEnd,
            'preference_label' => $preference->label
        ]);

        for ($i = 1; $i <= $daysToSearch; $i++) {
            $searchDate = $startDate->copy()->addDays($i);

            // Skip non-workdays
            if (!$this->isWorkday($searchDate)) {
                continue;
            }

            // Build time window for this day
            $windowStart = $searchDate->copy()->setTimeFromTimeString($preference->windowStart . ':00');
            $windowEnd = $searchDate->copy()->setTimeFromTimeString($preference->windowEnd . ':00');

            // Clamp to business hours
            $businessStart = $searchDate->copy()->setTimeFromTimeString($this->config['business_hours']['start'] . ':00');
            $businessEnd = $searchDate->copy()->setTimeFromTimeString($this->config['business_hours']['end'] . ':00');

            if ($windowStart < $businessStart) {
                $windowStart = $businessStart;
            }
            if ($windowEnd > $businessEnd) {
                $windowEnd = $businessEnd;
            }

            // Get available slots within the window
            $slots = $this->getAvailableSlots($windowStart, $windowEnd, $eventTypeId);

            foreach ($slots as $slot) {
                // 🔧 FIX 2025-12-14: TIMEZONE BUG - Cal.com returns UTC times, must convert to Berlin
                $slotTime = isset($slot['datetime'])
                    ? $slot['datetime']
                    : Carbon::parse($slot['time'])->setTimezone('Europe/Berlin');

                // Double-check slot is within the preference window
                if ($preference->containsDateTime($slotTime)) {
                    $alternatives->push([
                        'datetime' => $slotTime,
                        'type' => 'same_window_other_day',
                        'description' => $this->formatGermanWeekday($slotTime) . ', ' .
                                       $slotTime->format('d.m.') . ' um ' . $slotTime->format('H:i') . ' Uhr',
                        'source' => 'calcom',
                        'matches_preference' => true
                    ]);
                }

                // Stop if we have enough alternatives
                if ($alternatives->count() >= $this->maxAlternatives) {
                    break 2; // Break out of both loops
                }
            }
        }

        Log::info('✅ Found alternatives in same window on other days', [
            'count' => $alternatives->count(),
            'slots' => $alternatives->map(fn($alt) => $alt['datetime']->format('Y-m-d H:i'))->toArray()
        ]);

        return $alternatives;
    }
}