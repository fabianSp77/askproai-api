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
     * 0. Smart date inference for time-only input
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
        // 🔍 DETAILED MONITORING - Log parseDateTime entry
        Log::info('🎯 DateTimeParser::parseDateTime ENTRY', [
            'params' => $params,
            'params_keys' => array_keys($params),
            'time_param' => $params['time'] ?? $params['uhrzeit'] ?? null,
            'date_param' => $params['date'] ?? $params['datum'] ?? null
        ]);

        // 🔧 NEW 2025-10-23: Smart date inference for time-only input
        // When user says "14 Uhr" without date → infer today vs tomorrow
        $time = $params['time'] ?? $params['uhrzeit'] ?? null;
        $date = $params['date'] ?? $params['datum'] ?? null;

        // 🔧 FIX 2025-11-17: Validate vague date input without time
        // Bug: "diese Woche" without time defaulted to 10:00, causing confusion
        // Solution: Return null to signal caller to ask for time explicitly
        if (!$time && !isset($params['relative_day']) && !isset($params['datetime'])) {
            if ($date) {
                // Check if it's a vague expression requiring clarification
                if (preg_match('/(diese|nächste)\s+woche/i', $date)) {
                    Log::channel('retell')->warning('⚠️ Vague date without time - returning null to prompt user', [
                        'date_input' => $date,
                        'call_id' => $params['call_id'] ?? 'unknown',
                        'params' => $params
                    ]);

                    // Return null to signal need for clarification
                    // Caller should detect this and ask user for time
                    return null;
                }
            }
        }

        if ($time && !$date && !isset($params['relative_day']) && !isset($params['datetime'])) {
            $inference = $this->inferDateFromTime($time);

            if ($inference['date']) {
                // Use inferred date
                $params['date'] = $inference['date'];
                Log::info('📅 Smart date inference applied', [
                    'time' => $time,
                    'inferred_date' => $inference['date'],
                    'confidence' => $inference['confidence'],
                    'reason' => $inference['reason']
                ]);
            }
        }

        // Handle specific date if provided (supports both English and German param names)
        $hasDateAndTime = ($date && $time);

        if ($hasDateAndTime) {
            // 🔧 FIX 2025-11-13: Check if date is a German relative word first
            // BUG: Agent sends {"date":"morgen","time":"10:00"} or {"datum":"morgen","uhrzeit":"10:00"}
            // SOLUTION: Detect German dates and use parseRelativeDate() instead
            $dateValue = strtolower(trim($date));
            $isGermanDate = isset(self::GERMAN_DATE_MAP[$dateValue]);

            // 🔧 FIX 2025-11-14: Also check for week-based expressions
            // Both orders: "nächste Woche Montag" AND "Montag nächste Woche"
            $isWeekBasedDate = preg_match('/(nächste|diese|dieser|nächster|nächstes|kommende|kommender|montag|dienstag|mittwoch|donnerstag|freitag|samstag|sonntag)\s+(woche|montag|dienstag|mittwoch|donnerstag|freitag|samstag|sonntag|nächste|diese)/i', $dateValue);

            if ($isGermanDate || $isWeekBasedDate) {
                // Parse date string first to get the actual date
                $parsedDateString = $this->parseDateString($dateValue);

                if ($parsedDateString) {
                    // Combine parsed date with time
                    $parsedTime = Carbon::parse($time);
                    $result = Carbon::parse($parsedDateString)->setTime($parsedTime->hour, $parsedTime->minute);

                    Log::info('📅 German date + time parsed', [
                        'date_input' => $dateValue,
                        'time_input' => $time,
                        'parsed_date' => $parsedDateString,
                        'final_result' => $result->format('Y-m-d H:i')
                    ]);

                    return $result;
                }

                // Fallback to parseRelativeDate if parseDateString failed
                return $this->parseRelativeDate($dateValue, $time);
            }

            // English date or ISO format - use Carbon::parse()
            $parsed = Carbon::parse($date . ' ' . $time);

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

        // 🔧 FIX 2025-11-17: Default to tomorrow at 9 AM (business opening hour)
        // Changed from 10:00 to align with typical business hours
        return Carbon::tomorrow()->setTime(9, 0);
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
     * Smart date inference for time-only input
     *
     * When user provides only a time (e.g., "14 Uhr") without a date:
     * - If time already passed today → infer tomorrow
     * - If time is still future today → return null (needs clarification)
     *
     * @param string $time Time string (e.g., "14:00", "14 Uhr", "14")
     * @param string|null $callId Optional call ID for cached time
     * @return array ['date' => string|null, 'confidence' => string, 'reason' => string, 'suggestion' => string|null]
     */
    public function inferDateFromTime(string $time, ?string $callId = null): array
    {
        $now = $this->getCachedBerlinTime($callId);
        $parsedTime = $this->extractTimeComponents($time);

        if (!$parsedTime) {
            return [
                'date' => null,
                'confidence' => 'none',
                'reason' => 'invalid_time_format',
                'suggestion' => null
            ];
        }

        // Create datetime for today at the specified time
        $todayAtTime = $now->copy()->setTime($parsedTime['hour'], $parsedTime['minute']);

        // Check if time already passed today
        if ($todayAtTime->isPast()) {
            Log::info('⏰ Time already passed today, inferring tomorrow', [
                'time' => $time,
                'parsed_hour' => $parsedTime['hour'],
                'parsed_minute' => $parsedTime['minute'],
                'now' => $now->format('Y-m-d H:i'),
                'today_at_time' => $todayAtTime->format('Y-m-d H:i')
            ]);

            return [
                'date' => 'morgen',
                'confidence' => 'high',
                'reason' => 'time_passed_today',
                'suggestion' => null
            ];
        }

        // Time is still in the future today - ambiguous, needs clarification
        // Default to 'heute' but flag as low confidence so prompt can enforce asking
        Log::info('🤔 Time is ambiguous (could be today or tomorrow), defaulting to heute', [
            'time' => $time,
            'parsed_hour' => $parsedTime['hour'],
            'parsed_minute' => $parsedTime['minute'],
            'now' => $now->format('Y-m-d H:i'),
            'today_at_time' => $todayAtTime->format('Y-m-d H:i'),
            'default' => 'heute',
            'requires_confirmation' => true
        ]);

        return [
            'date' => 'heute',
            'confidence' => 'low',
            'reason' => 'ambiguous',
            'suggestion' => 'Meinen Sie heute um ' . sprintf('%02d:%02d', $parsedTime['hour'], $parsedTime['minute']) . ' Uhr oder morgen?'
        ];
    }

    /**
     * Extract time components from time string
     *
     * Handles formats:
     * - "14:00"
     * - "14 Uhr"
     * - "14"
     * - "14:30 Uhr"
     *
     * @param string $time Time string
     * @return array|null ['hour' => int, 'minute' => int] or null if invalid
     */
    private function extractTimeComponents(string $time): ?array
    {
        $time = trim(strtolower($time));

        // Remove "Uhr" suffix
        $time = preg_replace('/\s*uhr\s*$/i', '', $time);
        $time = trim($time);

        // Handle HH:MM format
        if (preg_match('/^(\d{1,2}):(\d{2})$/', $time, $matches)) {
            $hour = (int)$matches[1];
            $minute = (int)$matches[2];

            if ($hour >= 0 && $hour < 24 && $minute >= 0 && $minute < 60) {
                return ['hour' => $hour, 'minute' => $minute];
            }
        }

        // Handle hour-only format (e.g., "14")
        if (preg_match('/^\d{1,2}$/', $time)) {
            $hour = (int)$time;

            if ($hour >= 0 && $hour < 24) {
                return ['hour' => $hour, 'minute' => 0];
            }
        }

        return null;
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
        // 🔍 DETAILED MONITORING - Log parseDateString entry
        Log::info('🎯 DateTimeParser::parseDateString ENTRY', [
            'input' => $dateString,
            'input_type' => gettype($dateString),
            'input_length' => $dateString ? strlen($dateString) : 0
        ]);

        if (empty($dateString)) {
            Log::warning('⚠️ parseDateString: empty input', ['input' => $dateString]);
            return null;
        }

        $normalizedDate = strtolower(trim($dateString));

        Log::info('🔍 parseDateString: normalized', [
            'original' => $dateString,
            'normalized' => $normalizedDate
        ]);

        // Handle relative German dates
        if (isset(self::GERMAN_DATE_MAP[$normalizedDate])) {
            return Carbon::parse(self::GERMAN_DATE_MAP[$normalizedDate])->format('Y-m-d');
        }

        // 🔧 FIX 2025-11-19: Handle "Wochentag, den DD. Monat" pattern
        // Pattern: "Mittwoch, den 19. November", "Montag, den 25. Dezember"
        // User said: "Ich hätte gern Friseurtermin für heute. Haben Sie noch was frei um sechzehn Uhr circa?"
        // Agent asks: "Für welchen Tag?"
        // User says: "Mittwoch, den 19. November"
        // PROBLEM: German formatted date with article "den" not recognized
        // SOLUTION: Parse German weekday + day + month, infer year (current or next)
        if (preg_match('/^(montag|dienstag|mittwoch|donnerstag|freitag|samstag|sonntag),?\s+(?:den\s+)?(\d{1,2})\.?\s+(januar|februar|märz|april|mai|juni|juli|august|september|oktober|november|dezember)$/i', $normalizedDate, $matches)) {
            $weekdayName = strtolower($matches[1]);
            $day = (int)$matches[2];
            $monthName = strtolower($matches[3]);

            $monthMap = [
                'januar' => 1,
                'februar' => 2,
                'märz' => 3,
                'april' => 4,
                'mai' => 5,
                'juni' => 6,
                'juli' => 7,
                'august' => 8,
                'september' => 9,
                'oktober' => 10,
                'november' => 11,
                'dezember' => 12,
            ];

            $weekdayMap = [
                'montag' => Carbon::MONDAY,
                'dienstag' => Carbon::TUESDAY,
                'mittwoch' => Carbon::WEDNESDAY,
                'donnerstag' => Carbon::THURSDAY,
                'freitag' => Carbon::FRIDAY,
                'samstag' => Carbon::SATURDAY,
                'sonntag' => Carbon::SUNDAY,
            ];

            if (isset($monthMap[$monthName]) && isset($weekdayMap[$weekdayName])) {
                try {
                    $now = Carbon::now('Europe/Berlin');
                    $currentYear = $now->year;
                    $month = $monthMap[$monthName];

                    // Try current year first
                    $targetDate = Carbon::createFromDate($currentYear, $month, $day, 'Europe/Berlin');

                    // If date is in past (>2 days ago), try next year
                    if ($targetDate->isPast() && $targetDate->diffInDays($now, true) > 2) {
                        $targetDate = Carbon::createFromDate($currentYear + 1, $month, $day, 'Europe/Berlin');
                    }

                    // Validate that the weekday matches (user said "Mittwoch" but date is actually Monday?)
                    if ($targetDate->dayOfWeek !== $weekdayMap[$weekdayName]) {
                        Log::warning('⚠️ Weekday mismatch in German date format', [
                            'input' => $dateString,
                            'user_said_weekday' => $weekdayName,
                            'calculated_weekday' => $targetDate->englishDayOfWeek,
                            'calculated_date' => $targetDate->format('Y-m-d'),
                            'action' => 'using_calculated_date_anyway'
                        ]);
                    }

                    Log::info('📅 Parsed German "Wochentag, den DD. Monat" format', [
                        'input' => $dateString,
                        'normalized' => $normalizedDate,
                        'weekday' => $weekdayName,
                        'day' => $day,
                        'month_name' => $monthName,
                        'month_number' => $month,
                        'year_used' => $targetDate->year,
                        'result_date' => $targetDate->format('Y-m-d (l)'),
                        'logic' => $targetDate->year > $currentYear ? 'next_year' : 'this_year'
                    ]);

                    return $targetDate->format('Y-m-d');
                } catch (\Exception $e) {
                    Log::error('❌ Failed to parse German "Wochentag, den DD. Monat" format', [
                        'input' => $dateString,
                        'error' => $e->getMessage(),
                        'weekday' => $weekdayName ?? null,
                        'day' => $day ?? null,
                        'month' => $monthName ?? null
                    ]);
                }
            }
        }

        // 🔧 FIX 2025-11-14: Handle "Mittwoch diese Woche" (reversed order)
        // Pattern: "Mittwoch diese Woche" → Wednesday this week
        // User said: "Mittwoch diese Woche um 14:00"
        // This is the natural German order (weekday first, then "diese Woche")
        if (preg_match('/^(montag|dienstag|mittwoch|donnerstag|freitag|samstag|sonntag)\s+diese\s+woche$/i', $normalizedDate, $matches)) {
            $weekdayName = strtolower($matches[1]);

            $weekdayMap = [
                'montag' => Carbon::MONDAY,
                'dienstag' => Carbon::TUESDAY,
                'mittwoch' => Carbon::WEDNESDAY,
                'donnerstag' => Carbon::THURSDAY,
                'freitag' => Carbon::FRIDAY,
                'samstag' => Carbon::SATURDAY,
                'sonntag' => Carbon::SUNDAY,
            ];

            if (isset($weekdayMap[$weekdayName])) {
                try {
                    $now = Carbon::now('Europe/Berlin');
                    $targetDayOfWeek = $weekdayMap[$weekdayName];

                    // Get the target weekday in this calendar week
                    $targetDate = $now->copy()->startOfWeek()->addDays($targetDayOfWeek - 1);

                    // If the target day is before today (not today or future) → move to next week
                    if ($targetDate->isBefore($now->copy()->startOfDay())) {
                        $targetDate->addWeek();
                    }

                    Log::info('📅 Parsed "[WEEKDAY] diese Woche" pattern (reversed order)', [
                        'input' => $normalizedDate,
                        'weekday' => $weekdayName,
                        'today' => $now->format('Y-m-d (l)'),
                        'target_date' => $targetDate->format('Y-m-d (l)'),
                        'days_away' => $targetDate->diffInDays($now),
                        'logic' => $targetDate->isBefore($now->copy()->startOfDay()) ? 'moved_to_next_week' : 'this_week'
                    ]);

                    return $targetDate->format('Y-m-d');
                } catch (\Exception $e) {
                    Log::error('❌ Failed to parse "[WEEKDAY] diese Woche"', [
                        'input' => $normalizedDate,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }

        // 🔧 FIX 2025-11-14: Handle "diese Woche" (without specific weekday)
        // Pattern: "diese Woche" → Current day if workweek, or next Monday if weekend
        // User said: "diese Woche"
        // Expected: Today (if workday) or Monday this week (if weekend/past Monday)
        if (preg_match('/^diese\s+woche$/i', $normalizedDate)) {
            try {
                $now = Carbon::now('Europe/Berlin');

                // If it's weekend (Saturday/Sunday), default to Monday of this week
                if ($now->isWeekend()) {
                    $thisMonday = $now->copy()->startOfWeek(Carbon::MONDAY);

                    Log::info('📅 Parsed "diese Woche" (weekend) → this Monday', [
                        'input' => $normalizedDate,
                        'today' => $now->format('Y-m-d (l)'),
                        'this_monday' => $thisMonday->format('Y-m-d (l)'),
                        'week_number' => $thisMonday->weekOfYear
                    ]);

                    return $thisMonday->format('Y-m-d');
                }

                // It's a workday - default to today
                Log::info('📅 Parsed "diese Woche" (workday) → today', [
                    'input' => $normalizedDate,
                    'today' => $now->format('Y-m-d (l)'),
                    'week_number' => $now->weekOfYear
                ]);

                return $now->format('Y-m-d');
            } catch (\Exception $e) {
                Log::error('❌ Failed to parse "diese Woche"', [
                    'input' => $normalizedDate,
                    'error' => $e->getMessage()
                ]);
            }
        }

        // 🔧 FIX 2025-10-21: Handle "dieser/diese/dieses [WEEKDAY]" pattern
        // Pattern: "dieser Donnerstag", "diese Woche Donnerstag" → Thursday this week
        // User said: "Ich hätte gern für einen Donnerstag dreizehn Uhr"
        // Agent asked: "Für welchen Donnerstag?"
        // User said: "Diese Woche" or "Dieser Donnerstag"
        // Expected: If Thursday hasn't passed yet this week → this Thursday
        //          If Thursday already passed → next Thursday
        // IMPORTANT: Must NOT match "nächster" - only "dieser/diese/dieses/am"
        if (preg_match('/^(?:diese(?:r|s|n)?|am)\s+(?:woche\s+)?(montag|dienstag|mittwoch|donnerstag|freitag|samstag|sonntag)$/i', $normalizedDate, $matches)) {
            $weekdayName = strtolower($matches[1]);

            $weekdayMap = [
                'montag' => Carbon::MONDAY,
                'dienstag' => Carbon::TUESDAY,
                'mittwoch' => Carbon::WEDNESDAY,
                'donnerstag' => Carbon::THURSDAY,
                'freitag' => Carbon::FRIDAY,
                'samstag' => Carbon::SATURDAY,
                'sonntag' => Carbon::SUNDAY,
            ];

            if (isset($weekdayMap[$weekdayName])) {
                try {
                    $now = Carbon::now('Europe/Berlin');
                    $targetDayOfWeek = $weekdayMap[$weekdayName];

                    // Get the target weekday in this calendar week
                    $targetDate = $now->copy()->startOfWeek()->addDays($targetDayOfWeek - 1);

                    // If the target day is before today (not today or future) → move to next week
                    // Special case: If it's today but late (after 18:00) → also move to next week
                    if ($targetDate->isBefore($now->copy()->startOfDay())) {
                        // Day already passed this week
                        $targetDate->addWeek();
                    } elseif ($targetDate->isToday() && $now->hour >= 18) {
                        // It's today but late in the day - might mean next occurrence
                        // For now, keep it as today (user can be more explicit if they want next week)
                    }

                    Log::info('📅 Parsed "dieser [WEEKDAY]" pattern', [
                        'input' => $normalizedDate,
                        'weekday' => $weekdayName,
                        'today' => $now->format('Y-m-d (l)'),
                        'target_date' => $targetDate->format('Y-m-d (l)'),
                        'days_away' => $targetDate->diffInDays($now),
                        'is_today' => $targetDate->isToday(),
                        'logic' => $targetDate->isBefore($now->copy()->startOfDay()) ? 'moved_to_next_week' : 'this_week'
                    ]);

                    return $targetDate->format('Y-m-d');
                } catch (\Exception $e) {
                    Log::error('❌ Failed to parse "dieser [WEEKDAY]"', [
                        'input' => $normalizedDate,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }

        // 🔧 FIX 2025-10-21: Handle "nächster/nächste/nächstes [WEEKDAY]" pattern (without "Woche")
        // Pattern: "nächsten Donnerstag", "kommenden Freitag" → next occurrence
        // More natural than "nächste Woche Donnerstag"
        if (preg_match('/^(?:nächste(?:r|n|s)?|kommende(?:r|n|s)?)\s+(montag|dienstag|mittwoch|donnerstag|freitag|samstag|sonntag)$/i', $normalizedDate, $matches)) {
            $weekdayName = strtolower($matches[1]);

            $weekdayMap = [
                'montag' => Carbon::MONDAY,
                'dienstag' => Carbon::TUESDAY,
                'mittwoch' => Carbon::WEDNESDAY,
                'donnerstag' => Carbon::THURSDAY,
                'freitag' => Carbon::FRIDAY,
                'samstag' => Carbon::SATURDAY,
                'sonntag' => Carbon::SUNDAY,
            ];

            if (isset($weekdayMap[$weekdayName])) {
                try {
                    $now = Carbon::now('Europe/Berlin');
                    $targetDayOfWeek = $weekdayMap[$weekdayName];

                    // "nächster [WEEKDAY]" means NEXT week, not this week
                    // Calculate next week's target weekday
                    $targetDate = $now->copy()->next($targetDayOfWeek);

                    // If the target is still this week, move it to next week
                    if ($targetDate->weekOfYear === $now->weekOfYear) {
                        $targetDate->addWeek();
                    }

                    Log::info('📅 Parsed "nächster [WEEKDAY]" pattern', [
                        'input' => $normalizedDate,
                        'weekday' => $weekdayName,
                        'today' => $now->format('Y-m-d (l) W') . ' Woche',
                        'target_date' => $targetDate->format('Y-m-d (l) W') . ' Woche',
                        'days_away' => $targetDate->diffInDays($now),
                        'logic' => 'always_next_week'
                    ]);

                    return $targetDate->format('Y-m-d');
                } catch (\Exception $e) {
                    Log::error('❌ Failed to parse "nächster [WEEKDAY]"', [
                        'input' => $normalizedDate,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }

        // 🔧 FIX 2025-11-14: Handle "Mittwoch nächste Woche" (reversed order)
        // Pattern: "Mittwoch nächste Woche" → Wednesday of next week
        // User said: "Mittwoch nächste Woche um 17:00"
        // This is the natural German order (weekday first, then "nächste Woche")
        if (preg_match('/^(montag|dienstag|mittwoch|donnerstag|freitag|samstag|sonntag)\s+nächste\s+woche$/i', $normalizedDate, $matches)) {
            $weekdayName = strtolower($matches[1]);

            $weekdayMap = [
                'montag' => Carbon::MONDAY,
                'dienstag' => Carbon::TUESDAY,
                'mittwoch' => Carbon::WEDNESDAY,
                'donnerstag' => Carbon::THURSDAY,
                'freitag' => Carbon::FRIDAY,
                'samstag' => Carbon::SATURDAY,
                'sonntag' => Carbon::SUNDAY,
            ];

            if (isset($weekdayMap[$weekdayName])) {
                try {
                    $now = Carbon::now('Europe/Berlin');
                    $targetDayOfWeek = $weekdayMap[$weekdayName];

                    // Get next week's start and then add days to target weekday
                    $nextWeekStart = $now->copy()->startOfWeek()->addWeek();
                    $targetDate = $nextWeekStart->addDays($targetDayOfWeek - 1);

                    Log::info('📅 Parsed "[WEEKDAY] nächste Woche" pattern (reversed order)', [
                        'input' => $normalizedDate,
                        'weekday' => $weekdayName,
                        'today' => $now->format('Y-m-d (l)'),
                        'next_week_target' => $targetDate->format('Y-m-d (l)'),
                        'days_away' => $targetDate->diffInDays($now)
                    ]);

                    return $targetDate->format('Y-m-d');
                } catch (\Exception $e) {
                    Log::error('❌ Failed to parse "[WEEKDAY] nächste Woche"', [
                        'input' => $normalizedDate,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }

        // 🔧 FIX 2025-11-14: Handle "nächste Woche" (without specific weekday)
        // Pattern: "nächste Woche" → Default to Monday of next week
        // User said: "nächste Woche um 17:00"
        // Expected: Next Monday (start of next week)
        if (preg_match('/^nächste\s+woche$/i', $normalizedDate)) {
            try {
                $now = Carbon::now('Europe/Berlin');
                $nextMonday = $now->copy()->next(Carbon::MONDAY);

                // If next Monday is this week, move to the following Monday
                if ($nextMonday->weekOfYear === $now->weekOfYear) {
                    $nextMonday->addWeek();
                }

                Log::info('📅 Parsed "nächste Woche" (without weekday) → next Monday', [
                    'input' => $normalizedDate,
                    'today' => $now->format('Y-m-d (l)'),
                    'next_monday' => $nextMonday->format('Y-m-d (l)'),
                    'days_away' => $nextMonday->diffInDays($now),
                    'week_number' => $nextMonday->weekOfYear
                ]);

                return $nextMonday->format('Y-m-d');
            } catch (\Exception $e) {
                Log::error('❌ Failed to parse "nächste Woche"', [
                    'input' => $normalizedDate,
                    'error' => $e->getMessage()
                ]);
            }
        }

        // 🔧 FIX 2025-10-18: Handle "nächste Woche [WEEKDAY]" pattern
        // Pattern: "nächste Woche Mittwoch" → Wednesday of next week
        // User said: "nächste Woche Mittwoch um 14:15"
        // Expected: Calculate next Wednesday and return date
        if (preg_match('/nächste\s+woche\s+(montag|dienstag|mittwoch|donnerstag|freitag|samstag|sonntag)/i', $normalizedDate, $matches)) {
            $weekdayName = strtolower($matches[1]);

            $weekdayMap = [
                'montag' => Carbon::MONDAY,
                'dienstag' => Carbon::TUESDAY,
                'mittwoch' => Carbon::WEDNESDAY,
                'donnerstag' => Carbon::THURSDAY,
                'freitag' => Carbon::FRIDAY,
                'samstag' => Carbon::SATURDAY,
                'sonntag' => Carbon::SUNDAY,
            ];

            if (isset($weekdayMap[$weekdayName])) {
                try {
                    $now = Carbon::now('Europe/Berlin');
                    $targetDayOfWeek = $weekdayMap[$weekdayName];

                    // Get next week's start and then add days to target weekday
                    $nextWeekStart = $now->copy()->startOfWeek()->addWeek();
                    $targetDate = $nextWeekStart->addDays($targetDayOfWeek - 1);

                    Log::info('📅 Parsed "nächste Woche [WEEKDAY]" pattern', [
                        'input' => $normalizedDate,
                        'weekday' => $weekdayName,
                        'today' => $now->format('Y-m-d (l)'),
                        'next_week_target' => $targetDate->format('Y-m-d (l)'),
                        'days_away' => $targetDate->diffInDays($now)
                    ]);

                    return $targetDate->format('Y-m-d');
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

            Log::info('✅ parseDateString SUCCESS (generic path)', [
                'input' => $dateString,
                'output' => $carbon->format('Y-m-d'),
                'parsed_by' => 'Carbon::parse'
            ]);

            return $carbon->format('Y-m-d');
        } catch (\Exception $e) {
            Log::error('❌ Failed to parse date string', [
                'input' => $dateString,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
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
