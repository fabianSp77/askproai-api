<?php

namespace App\Services\Retell;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * DateTimeParser
 *
 * Centralized date/time parsing for Retell AI webhook parameters.
 * Handles German relative dates, multiple formats, and intelligent fallbacks.
 *
 * Phase 3: Extracted from RetellFunctionCallHandler
 * Phase 4: Added request-scoped caching for latency optimization
 */
class DateTimeParser
{
    /**
     * Request-scoped cache for Berlin time during call
     * Prevents redundant timezone calculations
     * Key: call_id, Value: Carbon instance
     */
    private static array $callTimeCache = [];

    /**
     * Get cached Berlin time for request-scoped performance
     *
     * Caches Carbon::now('Europe/Berlin') per request to avoid redundant
     * timezone calculations. Saves ~5-10ms per call.
     *
     * @param string|null $callId Optional call ID for cache key
     * @return Carbon Current Berlin time (cached if possible)
     */
    private function getCachedBerlinTime(?string $callId = null): Carbon
    {
        $cacheKey = $callId ?? 'default';

        if (!isset(self::$callTimeCache[$cacheKey])) {
            self::$callTimeCache[$cacheKey] = Carbon::now('Europe/Berlin');
        }

        return self::$callTimeCache[$cacheKey]->copy();
    }

    /**
     * Clear time cache (useful for testing or long-running processes)
     */
    public static function clearTimeCache(): void
    {
        self::$callTimeCache = [];
    }

    /**
     * German relative date mappings
     */
    private const GERMAN_DATE_MAP = [
        'heute' => 'today',
        'morgen' => 'tomorrow',
        'übermorgen' => '+2 days',
        'montag' => 'next monday',
        'dienstag' => 'next tuesday',
        'mittwoch' => 'next wednesday',
        'donnerstag' => 'next thursday',
        'freitag' => 'next friday',
        'samstag' => 'next saturday',
        'sonntag' => 'next sunday',
    ];

    /**
     * Parse date/time from various parameter formats
     *
     * Priority:
     * 1. date + time parameters
     * 2. relative_day parameter (German)
     * 3. datetime parameter (ISO)
     * 4. Default: tomorrow at 10 AM
     *
     * @param array $params Request parameters
     * @return Carbon Parsed datetime
     */
    public function parseDateTime(array $params): Carbon
    {
        // Handle specific date if provided
        if (isset($params['date']) && isset($params['time'])) {
            $parsed = Carbon::parse($params['date'] . ' ' . $params['time']);

            // 🔧 CRITICAL FIX 2025-10-20: ANY past time is invalid, not just >30 days
            // User says "14:00 Uhr today" but it's already 14:00 → REJECT and suggest alternative
            // This prevents infinite loop: "Ich überprüfe nochmal... Ich überprüfe nochmal..."
            if ($parsed->isPast()) {
                $minutesAgo = $parsed->diffInMinutes(now());

                if ($minutesAgo > 0) {
                    Log::warning('⏰ Past time requested - suggesting next available', [
                        'requested' => $parsed->format('Y-m-d H:i'),
                        'minutes_ago' => $minutesAgo,
                        'params' => $params
                    ]);

                    // Suggest next available time: +2 hours from now, rounded to hour
                    $suggestedTime = now('Europe/Berlin')
                        ->addHours(2)
                        ->floorHour()
                        ->setMinutes(0);

                    Log::info('✅ Suggesting alternative time', [
                        'suggested' => $suggestedTime->format('Y-m-d H:i')
                    ]);

                    return $suggestedTime;
                }
            }

            return $parsed;
        }

        // Handle relative dates (German)
        if (isset($params['relative_day'])) {
            return $this->parseRelativeDate($params['relative_day'], $params['time'] ?? null);
        }

        // Handle ISO format
        if (isset($params['datetime'])) {
            return Carbon::parse($params['datetime']);
        }

        // Default to tomorrow at 10 AM
        return Carbon::tomorrow()->setTime(10, 0);
    }

    /**
     * Parse German relative date
     *
     * @param string $relativeDay German day keyword (heute, morgen, montag, etc.)
     * @param string|null $time Optional time (HH:MM or ISO)
     * @return Carbon
     */
    private function parseRelativeDate(string $relativeDay, ?string $time): Carbon
    {
        $normalizedDay = strtolower(trim($relativeDay));

        $baseDate = match($normalizedDay) {
            'heute' => Carbon::today(),
            'morgen' => Carbon::tomorrow(),
            'übermorgen' => Carbon::today()->addDays(2),
            'montag' => Carbon::parse('next monday'),
            'dienstag' => Carbon::parse('next tuesday'),
            'mittwoch' => Carbon::parse('next wednesday'),
            'donnerstag' => Carbon::parse('next thursday'),
            'freitag' => Carbon::parse('next friday'),
            'samstag' => Carbon::parse('next saturday'),
            'sonntag' => Carbon::parse('next sunday'),
            default => Carbon::tomorrow()
        };

        if ($time) {
            $parsedTime = Carbon::parse($time);
            $baseDate->setTime($parsedTime->hour, $parsedTime->minute);
        }

        return $baseDate;
    }

    /**
     * Parse date string to MySQL DATE format (YYYY-MM-DD)
     *
     * Handles:
     * - German relative: "heute", "morgen", "montag"
     * - German format: "01.10.2025", "1.10.2025"
     * - ISO format: "2025-10-01"
     * - Natural language: anything Carbon can parse
     *
     * @param string|null $dateString Date string to parse
     * @return string|null MySQL date format or null if invalid
     */
    public function parseDateString(?string $dateString): ?string
    {
        if (empty($dateString)) {
            return null;
        }

        $normalizedDate = strtolower(trim($dateString));

        // Handle relative German dates
        if (isset(self::GERMAN_DATE_MAP[$normalizedDate])) {
            return Carbon::parse(self::GERMAN_DATE_MAP[$normalizedDate])->format('Y-m-d');
        }

        // 🔧 FIX 2025-10-18: Handle "nächste Woche [WEEKDAY]" pattern
        // Pattern: "nächste Woche Mittwoch" → Wednesday of next week
        // User said: "nächste Woche Mittwoch um 14:15"
        // Expected: Calculate next Wednesday and return date
        if (preg_match('/nächste\s+woche\s+(montag|dienstag|mittwoch|donnerstag|freitag|samstag|sonntag)/i', $normalizedDate, $matches)) {
            $weekdayName = strtolower($matches[1]);

            $weekdayMap = [
                'montag' => 1,
                'dienstag' => 2,
                'mittwoch' => 3,
                'donnerstag' => 4,
                'freitag' => 5,
                'samstag' => 6,
                'sonntag' => 0
            ];

            if (isset($weekdayMap[$weekdayName])) {
                try {
                    $now = Carbon::now('Europe/Berlin');
                    $dayOfWeek = $weekdayMap[$weekdayName];

                    // Carbon::next() returns the next occurrence of the given weekday
                    $nextDate = $now->copy()->next($dayOfWeek);

                    Log::info('📅 Parsed "nächste Woche [WEEKDAY]" pattern', [
                        'input' => $normalizedDate,
                        'weekday' => $weekdayName,
                        'today' => $now->format('Y-m-d (l)'),
                        'next_occurrence' => $nextDate->format('Y-m-d (l)'),
                        'days_away' => $nextDate->diffInDays($now)
                    ]);

                    return $nextDate->format('Y-m-d');
                } catch (\Exception $e) {
                    Log::error('❌ Failed to parse "nächste Woche [WEEKDAY]"', [
                        'input' => $normalizedDate,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }

        // 🔥 FIX: German SHORT date format (DD.M or D.M) - Phase 1.3.3
        // When user says "fünfzehnte Punkt eins" → STT transcribes as "15.1"
        // CRITICAL: "X.1" where 1 is ambiguous → default to CURRENT month, not January!
        // Example: In October, "15.1" = 15. Oktober (NOT 15. Januar!)
        if (preg_match('/^(\d{1,2})\.(\d{1,2})$/', $dateString, $matches)) {
            $day = (int) $matches[1];
            $monthInput = (int) $matches[2];

            try {
                $now = Carbon::now('Europe/Berlin');
                $currentYear = $now->year;
                $currentMonth = $now->month;

                // SPECIAL CASE: Single digit "1" is ambiguous in speech-to-text
                // ONLY substitute current month when day is MID-MONTH (day > 10)
                // Examples:
                // - "15.1" in October → likely means "15th of October" (substitute)
                // - "1.1" in October → likely means "January 1st" (don't substitute)
                $month = $monthInput;
                if ($monthInput === 1 && $currentMonth > 2 && $day > 10) {
                    // High day number with ".1" → likely STT artifact, use current month
                    $month = $currentMonth;
                    Log::info('📅 German short format: ".1" interpreted as current month (not January)', [
                        'input' => $dateString,
                        'original_month' => $monthInput,
                        'interpreted_month' => $month,
                        'current_month' => $currentMonth,
                        'day' => $day,
                        'reason' => 'ambiguous_stt_mid_month_date'
                    ]);
                }

                // Build date with interpreted month
                $carbon = Carbon::createFromDate($currentYear, $month, $day, 'Europe/Berlin');

                // LOGIC: If date is in the past (>2 days), try next occurrence
                if ($carbon->isPast() && $carbon->diffInDays($now, true) > 2) {
                    // If month < current month → assume next year
                    if ($month < $currentMonth) {
                        $carbon->addYear();
                        Log::info('📅 German short format: month in past, assuming next year', [
                            'input' => $dateString,
                            'parsed' => $carbon->format('Y-m-d'),
                            'day' => $day,
                            'month' => $month,
                            'current_month' => $currentMonth
                        ]);
                    }
                    // If same month but day passed → assume next month
                    elseif ($month === $currentMonth && $day < $now->day) {
                        $carbon->addMonth();
                        Log::info('📅 German short format: day in past, assuming next month', [
                            'input' => $dateString,
                            'parsed' => $carbon->format('Y-m-d'),
                            'day' => $day,
                            'month' => $month
                        ]);
                    }
                    // If month > current month but still past → assume next year
                    else {
                        $carbon->addYear();
                        Log::info('📅 German short format: assuming next year', [
                            'input' => $dateString,
                            'parsed' => $carbon->format('Y-m-d')
                        ]);
                    }
                } else {
                    Log::info('📅 German short format: using current year', [
                        'input' => $dateString,
                        'parsed' => $carbon->format('Y-m-d'),
                        'day' => $day,
                        'month' => $month
                    ]);
                }

                return $carbon->format('Y-m-d');
            } catch (\Exception $e) {
                Log::warning('Failed to parse German short date format', [
                    'input' => $dateString,
                    'error' => $e->getMessage()
                ]);
            }
        }

        // Try parsing German date format (DD.MM.YYYY or D.M.YYYY)
        if (preg_match('/^(\d{1,2})\.(\d{1,2})\.(\d{4})$/', $dateString, $matches)) {
            try {
                $carbon = Carbon::createFromFormat('d.m.Y', $dateString);

                // 🔍 SMART YEAR INFERENCE (Bug Fix: Call 778)
                // Apply year adjustment for German format dates too!
                if ($carbon->isPast() && $carbon->diffInDays(now(), true) > 7) {
                    $nextYear = $carbon->copy()->addYear();

                    // Only adjust if future date is reasonable (within next 365 days)
                    if ($nextYear->isFuture() && $nextYear->diffInDays(now()) < 365) {
                        Log::info('📅 Adjusted date year to future occurrence (German format)', [
                            'original' => $carbon->format('Y-m-d'),
                            'adjusted' => $nextYear->format('Y-m-d'),
                            'input' => $dateString,
                            'reason' => 'past_date_detected'
                        ]);
                        $carbon = $nextYear;
                    }
                }

                return $carbon->format('Y-m-d');
            } catch (\Exception $e) {
                Log::warning('Failed to parse German date format', [
                    'input' => $dateString,
                    'error' => $e->getMessage()
                ]);
            }
        }

        // Try parsing ISO format (YYYY-MM-DD) or let Carbon figure it out
        try {
            $carbon = Carbon::parse($dateString);

            // 🔍 SMART YEAR INFERENCE (Bug Fix: Call 776, Fixed in Call 778)
            // If parsed date is significantly in the past (>7 days), assume user meant next occurrence
            // Bug Fix: Use absolute value (true) not signed value (false) for past date comparison
            if ($carbon->isPast() && $carbon->diffInDays(now(), true) > 7) {
                $nextYear = $carbon->copy()->addYear();

                // Only adjust if future date is reasonable (within next 365 days)
                if ($nextYear->isFuture() && $nextYear->diffInDays(now()) < 365) {
                    Log::info('📅 Adjusted date year to future occurrence', [
                        'original' => $carbon->format('Y-m-d'),
                        'adjusted' => $nextYear->format('Y-m-d'),
                        'input' => $dateString,
                        'reason' => 'past_date_detected'
                    ]);
                    $carbon = $nextYear;
                }
            }

            return $carbon->format('Y-m-d');
        } catch (\Exception $e) {
            Log::error('Failed to parse date string', [
                'input' => $dateString,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Parse time string to MySQL TIME format (HH:MM:SS)
     *
     * @param string|null $timeString Time string to parse
     * @return string|null MySQL time format or null if invalid
     */
    public function parseTimeString(?string $timeString): ?string
    {
        if (empty($timeString)) {
            return null;
        }

        try {
            $carbon = Carbon::parse($timeString);
            return $carbon->format('H:i:s');
        } catch (\Exception $e) {
            Log::error('Failed to parse time string', [
                'input' => $timeString,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Parse time-only input with optional context date (V87 PHASE 2b support)
     *
     * FIX 2025-10-19: Support for context-aware time updates
     *
     * Scenario: Agent has confirmed date "2025-10-20"
     *           User responds with only time: "14:00" or "vierzehn Uhr"
     *           We need to combine confirmed date + new time
     *
     * Usage:
     *   parseTimeOnly("14:00", "2025-10-20")
     *   → Returns: Carbon instance for 2025-10-20 14:00:00
     *
     * @param string|null $timeString Time input (e.g., "14:00", "vierzehn Uhr")
     * @param string|null $contextDate Optional confirmed date (YYYY-MM-DD format)
     * @return Carbon|null Parsed datetime or null if invalid
     */
    public function parseTimeOnly(?string $timeString, ?string $contextDate = null): ?Carbon
    {
        if (empty($timeString)) {
            return null;
        }

        try {
            // Try to parse just the time
            $timeCarbon = Carbon::parse($timeString);

            // If we have a context date, combine them
            if (!empty($contextDate)) {
                try {
                    $dateCarbon = Carbon::parse($contextDate);

                    // Combine: use date from context, time from input
                    $result = $dateCarbon->copy()->setTime(
                        $timeCarbon->hour,
                        $timeCarbon->minute,
                        $timeCarbon->second
                    );

                    Log::info('⏰ Time-only parsed with context date (PHASE 2b)', [
                        'time_input' => $timeString,
                        'context_date' => $contextDate,
                        'result' => $result->format('Y-m-d H:i:s'),
                        'phase' => '2b'
                    ]);

                    return $result;
                } catch (\Exception $e) {
                    Log::warning('❌ Could not parse context date in parseTimeOnly', [
                        'context_date' => $contextDate,
                        'error' => $e->getMessage()
                    ]);
                    // Fall through to return time-only parse
                }
            }

            // No context date, or context date parsing failed
            // Return just the time (will be combined elsewhere)
            Log::info('⏰ Time-only parsed without context', [
                'time_input' => $timeString,
                'result' => $timeCarbon->format('H:i:s')
            ]);

            return $timeCarbon;
        } catch (\Exception $e) {
            Log::error('❌ Failed to parse time-only string', [
                'time_input' => $timeString,
                'context_date' => $contextDate,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Detect if input is time-only (no date information)
     *
     * Examples that return TRUE (time-only):
     * - "14:00"
     * - "14 Uhr"
     * - "vierzehn Uhr"
     * - "2 Uhr nachmittags"
     * - "halb drei"
     *
     * Examples that return FALSE (contains date):
     * - "Montag 14 Uhr"
     * - "20. Oktober 14 Uhr"
     * - "übermorgen 14 Uhr"
     * - "nächster Montag"
     *
     * @param string $input User input string
     * @return bool True if input appears to be time-only
     */
    public function isTimeOnly(string $input): bool
    {
        $normalized = strtolower(trim($input));

        // Check for date keywords that would indicate it's NOT time-only
        $dateKeywords = [
            'montag', 'dienstag', 'mittwoch', 'donnerstag', 'freitag', 'samstag', 'sonntag',
            'heute', 'morgen', 'übermorgen',
            'nächste', 'nächster', 'diese', 'dieser',
            'woche', 'oktober', 'september', 'november', 'dezember', 'januar', 'februar',
            'märz', 'april', 'mai', 'juni', 'juli', 'august'
        ];

        foreach ($dateKeywords as $keyword) {
            if (strpos($normalized, $keyword) !== false) {
                return false;  // Contains date keyword
            }
        }

        // Check if it contains time patterns
        // - Numeric times: "14:00", "14 00", "1400"
        // - German times: "14 Uhr", "vierzehn Uhr", "halb drei"
        $timePatterns = [
            '/\d{1,2}\s*:\s*\d{2}/',  // HH:MM
            '/\d{1,2}\s*Uhr/',         // X Uhr
            '/(ein|zwei|drei|vier|fünf|sechs|sieben|acht|neun|zehn|elf|zwölf|.*zehn)\s+(Uhr|Uhr)/',  // German numbers + Uhr
            '/halb\s+(zwei|drei|vier|fünf|sechs|sieben|acht|neun|zehn|elf|zwölf)/',  // halb + number
            '/viertel/',  // viertel nach/vor
        ];

        foreach ($timePatterns as $pattern) {
            if (preg_match($pattern, $normalized)) {
                return true;  // Contains time pattern and no date keywords
            }
        }

        return false;
    }

    /**
     * Parse duration to minutes
     *
     * @param mixed $duration Duration in various formats (60, "60", "1h", "90m")
     * @param int $default Default duration if parsing fails
     * @return int Duration in minutes
     */
    public function parseDuration($duration, int $default = 60): int
    {
        if (is_numeric($duration)) {
            return (int) $duration;
        }

        if (is_string($duration)) {
            // Handle "1h", "2h30m", "90m" formats
            if (preg_match('/(\d+)h/', $duration, $hours)) {
                $minutes = (int) $hours[1] * 60;
                if (preg_match('/(\d+)m/', $duration, $mins)) {
                    $minutes += (int) $mins[1];
                }
                return $minutes;
            }

            if (preg_match('/(\d+)m/', $duration, $minutes)) {
                return (int) $minutes[1];
            }
        }

        return $default;
    }

    /**
     * Parse German relative weekday with "dieser" vs "nächster" distinction
     *
     * ASK-006: Critical semantic difference for German language
     *
     * Logic:
     * - "dieser [Wochentag]" = Next occurrence THIS week, or next week if already passed
     * - "nächster [Wochentag]" = Always NEXT week, minimum 7 days ahead
     *
     * Examples (today = Thursday, 2025-10-09):
     * - "dieser Freitag" → 2025-10-10 (tomorrow, this week)
     * - "dieser Montag" → 2025-10-13 (next week, Monday already passed)
     * - "nächster Freitag" → 2025-10-17 (next week, always +7d minimum)
     * - "nächster Donnerstag" → 2025-10-16 (next week, +7d from today)
     *
     * @param string $weekday German weekday (Montag, Dienstag, ...)
     * @param string $modifier "dieser" or "nächster"
     * @return Carbon Calculated date in Europe/Berlin timezone
     * @throws \InvalidArgumentException If weekday or modifier unknown
     */
    public function parseRelativeWeekday(string $weekday, string $modifier): Carbon
    {
        $weekdayMap = [
            'montag' => Carbon::MONDAY,
            'dienstag' => Carbon::TUESDAY,
            'mittwoch' => Carbon::WEDNESDAY,
            'donnerstag' => Carbon::THURSDAY,
            'freitag' => Carbon::FRIDAY,
            'samstag' => Carbon::SATURDAY,
            'sonntag' => Carbon::SUNDAY,
        ];

        $normalizedWeekday = strtolower(trim($weekday));
        $normalizedModifier = strtolower(trim($modifier));

        if (!isset($weekdayMap[$normalizedWeekday])) {
            throw new \InvalidArgumentException("Unknown weekday: {$weekday}");
        }

        $targetDayOfWeek = $weekdayMap[$normalizedWeekday];
        // Use cached Berlin time if available (latency optimization)
        $now = $this->getCachedBerlinTime();
        $currentDayOfWeek = $now->dayOfWeek;

        if ($normalizedModifier === 'dieser' || $normalizedModifier === 'diese' || $normalizedModifier === 'dieses') {
            // "dieser" = Next occurrence this week, or next week if passed

            if ($targetDayOfWeek > $currentDayOfWeek) {
                // Target day is later this week
                $result = $now->copy()->next($targetDayOfWeek);
            } elseif ($targetDayOfWeek === $currentDayOfWeek) {
                // Same day = today
                $result = $now->copy();
            } else {
                // Target day already passed this week → next week
                $result = $now->copy()->next($targetDayOfWeek);
            }
        } elseif ($normalizedModifier === 'nächster' || $normalizedModifier === 'nächste' || $normalizedModifier === 'nächstes') {
            // "nächster" = The next calendar occurrence of this weekday
            //
            // CRITICAL FIX (2025-10-18): Removed faulty "add another week if < 7 days" logic
            // Bug: Was transforming "next Tuesday" into "Tuesday-after-next"
            //
            // Examples (today = Saturday, 18. October):
            // - "nächster Dienstag" = 21. Oktober (Tuesday next week, 3 days away)
            // - "nächster Freitag" = 24. Oktober (Friday next week, 6 days away)
            // - "nächster Sonntag" = 19. Oktober (Sunday this week, 1 day away)
            //
            // The native German meaning is: "The next calendar occurrence"
            // NOT "A day that's at least 7 days away" (which was the buggy assumption)

            $result = $now->copy()->next($targetDayOfWeek);

            // ✅ REMOVED: Old faulty logic that was:
            //    if ($result->diffInDays($now) < 7) {
            //        $result->addWeek();
            //    }
            // This was incorrectly adding one week for any occurrence less than 7 days away
            // causing "nächster Dienstag" to become "Dienstag übernächste Woche"
        } else {
            throw new \InvalidArgumentException("Unknown modifier: {$modifier}. Expected 'dieser' or 'nächster'");
        }

        Log::info('📅 Relative weekday parsed', [
            'input' => "{$modifier} {$weekday}",
            'today' => $now->format('Y-m-d (l)'),
            'result' => $result->format('Y-m-d (l)'),
            'days_from_now' => $result->diffInDays($now),
        ]);

        return $result;
    }

    /**
     * Parse German week range ("diese Woche", "nächste Woche")
     *
     * ASK-006: Week ranges for availability queries
     *
     * Returns Monday-Sunday range for ISO week
     *
     * Examples (today = Thursday, 2025-10-09, Week 41):
     * - "diese Woche" → 2025-10-06 (Mo) to 2025-10-12 (So)
     * - "nächste Woche" → 2025-10-13 (Mo) to 2025-10-19 (So)
     *
     * @param string $modifier "diese" or "nächste"
     * @return array ['start' => 'YYYY-MM-DD', 'end' => 'YYYY-MM-DD', 'week_number' => int]
     * @throws \InvalidArgumentException If modifier unknown
     */
    public function parseWeekRange(string $modifier): array
    {
        $normalizedModifier = strtolower(trim($modifier));
        $now = $this->getCachedBerlinTime();

        if ($normalizedModifier === 'diese' || $normalizedModifier === 'dieser' || $normalizedModifier === 'dieses') {
            // This week (current week Monday to Sunday)
            $start = $now->copy()->startOfWeek(Carbon::MONDAY);
            $end = $now->copy()->endOfWeek(Carbon::SUNDAY);
        } elseif ($normalizedModifier === 'nächste' || $normalizedModifier === 'nächster' || $normalizedModifier === 'nächstes') {
            // Next week (next week Monday to Sunday)
            $start = $now->copy()->addWeek()->startOfWeek(Carbon::MONDAY);
            $end = $now->copy()->addWeek()->endOfWeek(Carbon::SUNDAY);
        } else {
            throw new \InvalidArgumentException("Unknown week modifier: {$modifier}. Expected 'diese' or 'nächste'");
        }

        $result = [
            'start' => $start->format('Y-m-d'),
            'end' => $end->format('Y-m-d'),
            'week_number' => $start->weekOfYear,
            'year' => $start->year,
        ];

        Log::info('📅 Week range parsed', [
            'input' => "{$modifier} Woche",
            'today' => $now->format('Y-m-d (W)'),
            'result' => "{$result['start']} to {$result['end']} (W{$result['week_number']})",
        ]);

        return $result;
    }
}
