<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Exceptions\CalcomApiException;

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

    // Multi-tenant context for cache isolation
    private ?int $companyId = null;
    private ?string $branchId = null; // UUID string identifier

    public function __construct()
    {
        $this->calcomService = new CalcomService();
        $this->loadConfig();
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
     * @return array Array with 'alternatives' and 'responseText'
     */
    public function findAlternatives(
        Carbon $desiredDateTime,
        int $durationMinutes,
        int $eventTypeId,
        ?int $customerId = null,
        ?string $preferredLanguage = 'de'
    ): array {
        // Use actual dates without year mapping
        // Cal.com should now handle 2025 dates correctly

        Log::info('🔍 Searching for appointment alternatives', [
            'desired' => $desiredDateTime->format('Y-m-d H:i'),
            'duration' => $durationMinutes,
            'eventTypeId' => $eventTypeId,
            'customer_id' => $customerId
        ]);

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

            foreach ($this->config['search_strategies'] as $strategy) {
                if ($alternatives->count() >= $this->maxAlternatives) {
                    break;
                }

                $found = $this->executeStrategy($strategy, $desiredDateTime, $durationMinutes, $eventTypeId);
                $alternatives = $alternatives->merge($found);
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

            return [
                'alternatives' => $limited->toArray(),
                'responseText' => $responseText
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
                $slotTime = isset($slot['datetime']) ? $slot['datetime'] : Carbon::parse($slot['time']);
                $alternatives->push([
                    'datetime' => $slotTime,
                    'type' => 'same_day_earlier',
                    'description' => 'am gleichen Tag, ' . $slotTime->format('H:i') . ' Uhr',
                    'source' => 'calcom'
                ]);
            }
        }

        // Check slots after desired time
        $laterTime = $desiredDateTime->copy()->addHours($windowHours);
        if ($laterTime->format('H:i') <= $this->config['business_hours']['end']) {
            $slots = $this->getAvailableSlots($desiredDateTime, $laterTime, $eventTypeId);
            foreach ($slots as $slot) {
                $slotTime = isset($slot['datetime']) ? $slot['datetime'] : Carbon::parse($slot['time']);
                $alternatives->push([
                    'datetime' => $slotTime,
                    'type' => 'same_day_later',
                    'description' => 'am gleichen Tag, ' . $slotTime->format('H:i') . ' Uhr',
                    'source' => 'calcom'
                ]);
            }
        }

        return $alternatives;
    }

    /**
     * Find alternatives on next workday at same time
     */
    private function findNextWorkdayAlternatives(
        Carbon $desiredDateTime,
        int $durationMinutes,
        int $eventTypeId
    ): Collection {
        $alternatives = collect();
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
                    $slotTime = Carbon::parse($bestSlot['time']);
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
        // SECURITY FIX: Include company_id and branch_id in cache key to prevent cross-tenant data leakage
        $cacheKey = sprintf(
            'cal_slots_%d_%d_%d_%s_%s',
            $this->companyId ?? 0,
            $this->branchId ?? 0,
            $eventTypeId,
            $startTime->format('Y-m-d-H'),
            $endTime->format('Y-m-d-H')
        );

        return Cache::remember($cacheKey, 300, function() use ($startTime, $endTime, $eventTypeId) {
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
                                $parsedTime = Carbon::parse($slotTime);

                                // Debug logging
                                Log::debug('Checking slot', [
                                    'slot_time' => $slotTime,
                                    'parsed' => $parsedTime->format('Y-m-d H:i:s'),
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
     */
    private function rankAlternatives(Collection $alternatives, Carbon $desiredDateTime): Collection
    {
        return $alternatives->map(function($alt) use ($desiredDateTime) {
            $minutesDiff = abs($desiredDateTime->diffInMinutes($alt['datetime']));

            // Scoring based on proximity and type
            $score = 10000 - $minutesDiff; // Base score from time proximity

            // Bonus points for preferred strategies
            $score += match($alt['type']) {
                'same_day_earlier' => 500,
                'same_day_later' => 400,
                'next_workday' => 300,
                'next_week' => 200,
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
                    'description' => 'am gleichen Tag, ' . $earlier->format('H:i') . ' Uhr',
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
                    'description' => 'am gleichen Tag, ' . $later->format('H:i') . ' Uhr',
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
            $slotTime = isset($slot['datetime']) ? $slot['datetime'] : Carbon::parse($slot['time']);

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
                    $slotTime = isset($slot['datetime']) ? $slot['datetime'] : Carbon::parse($slot['time']);

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
}