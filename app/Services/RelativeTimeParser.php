<?php

declare(strict_types=1);

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Str;

/**
 * Parse German relative time expressions and convert to absolute timestamps.
 *
 * Examples:
 * - "seit fünfzehn Minuten" → "Mo, 23. Dez. 17:06 - "seit fünfzehn Minuten""
 * - "seit einer Stunde" → "Mo, 23. Dez. 16:21 - "seit einer Stunde""
 * - "seit heute, ungefähr dreizehn Uhr" → "Di, 24. Dez. 13:00 - "seit heute, ungefähr dreizehn Uhr""
 * - "seit gestern Abend" → "Mo, 23. Dez. 18:00 - "seit gestern Abend""
 */
class RelativeTimeParser
{
    /**
     * German number words to integers
     */
    private const NUMBER_WORDS = [
        'null' => 0,
        'eins' => 1,
        'eine' => 1,
        'einer' => 1,
        'einem' => 1,
        'ein' => 1,
        'zwei' => 2,
        'drei' => 3,
        'vier' => 4,
        'fünf' => 5,
        'sechs' => 6,
        'sieben' => 7,
        'acht' => 8,
        'neun' => 9,
        'zehn' => 10,
        'elf' => 11,
        'zwölf' => 12,
        'dreizehn' => 13,
        'vierzehn' => 14,
        'fünfzehn' => 15,
        'sechzehn' => 16,
        'siebzehn' => 17,
        'achtzehn' => 18,
        'neunzehn' => 19,
        'zwanzig' => 20,
        'einundzwanzig' => 21,
        'zweiundzwanzig' => 22,
        'dreiundzwanzig' => 23,
        'vierundzwanzig' => 24,
        'fünfundzwanzig' => 25,
        'sechsundzwanzig' => 26,
        'siebenundzwanzig' => 27,
        'achtundzwanzig' => 28,
        'neunundzwanzig' => 29,
        'dreißig' => 30,
        'einunddreißig' => 31,
        'vierzig' => 40,
        'fünfzig' => 50,
        'sechzig' => 60,
        // Common compound numbers
        'anderthalb' => 1.5,
        'eineinhalb' => 1.5,
        'zweieinhalb' => 2.5,
    ];

    /**
     * German ordinal number words (for day parsing)
     */
    private const ORDINAL_WORDS = [
        'ersten' => 1, 'erste' => 1, 'erster' => 1, 'erstem' => 1,
        'zweiten' => 2, 'zweite' => 2, 'zweiter' => 2, 'zweitem' => 2,
        'dritten' => 3, 'dritte' => 3, 'dritter' => 3, 'drittem' => 3,
        'vierten' => 4, 'vierte' => 4, 'vierter' => 4, 'viertem' => 4,
        'fünften' => 5, 'fünfte' => 5, 'fünfter' => 5, 'fünftem' => 5,
        'sechsten' => 6, 'sechste' => 6, 'sechster' => 6, 'sechstem' => 6,
        'siebten' => 7, 'siebte' => 7, 'siebter' => 7, 'siebtem' => 7,
        'achten' => 8, 'achte' => 8, 'achter' => 8, 'achtem' => 8,
        'neunten' => 9, 'neunte' => 9, 'neunter' => 9, 'neuntem' => 9,
        'zehnten' => 10, 'zehnte' => 10, 'zehnter' => 10, 'zehntem' => 10,
        'elften' => 11, 'elfte' => 11, 'elfter' => 11, 'elftem' => 11,
        'zwölften' => 12, 'zwölfte' => 12, 'zwölfter' => 12, 'zwölftem' => 12,
        'dreizehnten' => 13, 'dreizehnte' => 13,
        'vierzehnten' => 14, 'vierzehnte' => 14,
        'fünfzehnten' => 15, 'fünfzehnte' => 15,
        'sechzehnten' => 16, 'sechzehnte' => 16,
        'siebzehnten' => 17, 'siebzehnte' => 17,
        'achtzehnten' => 18, 'achtzehnte' => 18,
        'neunzehnten' => 19, 'neunzehnte' => 19,
        'zwanzigsten' => 20, 'zwanzigste' => 20,
        'einundzwanzigsten' => 21, 'einundzwanzigste' => 21,
        'zweiundzwanzigsten' => 22, 'zweiundzwanzigste' => 22,
        'dreiundzwanzigsten' => 23, 'dreiundzwanzigste' => 23,
        'vierundzwanzigsten' => 24, 'vierundzwanzigste' => 24,
        'fünfundzwanzigsten' => 25, 'fünfundzwanzigste' => 25,
        'sechsundzwanzigsten' => 26, 'sechsundzwanzigste' => 26,
        'siebenundzwanzigsten' => 27, 'siebenundzwanzigste' => 27,
        'achtundzwanzigsten' => 28, 'achtundzwanzigste' => 28,
        'neunundzwanzigsten' => 29, 'neunundzwanzigste' => 29,
        'dreißigsten' => 30, 'dreißigste' => 30,
        'einunddreißigsten' => 31, 'einunddreißigste' => 31,
    ];

    /**
     * German month names to month numbers
     */
    private const MONTH_NAMES = [
        'januar' => 1, 'jan' => 1,
        'februar' => 2, 'feb' => 2,
        'märz' => 3, 'maerz' => 3, 'mär' => 3,
        'april' => 4, 'apr' => 4,
        'mai' => 5,
        'juni' => 6, 'jun' => 6,
        'juli' => 7, 'jul' => 7,
        'august' => 8, 'aug' => 8,
        'september' => 9, 'sep' => 9, 'sept' => 9,
        'oktober' => 10, 'okt' => 10,
        'november' => 11, 'nov' => 11,
        'dezember' => 12, 'dez' => 12,
    ];

    /**
     * Time of day approximations (in hours)
     */
    private const TIME_OF_DAY = [
        'morgen' => 8,
        'morgens' => 8,
        'vormittag' => 10,
        'vormittags' => 10,
        'mittag' => 12,
        'mittags' => 12,
        'nachmittag' => 15,
        'nachmittags' => 15,
        'abend' => 18,
        'abends' => 18,
        'nacht' => 22,
        'nachts' => 22,
        'früh' => 7,
    ];

    /**
     * German weekday abbreviations
     */
    private const WEEKDAYS = [
        1 => 'Mo.',
        2 => 'Di.',
        3 => 'Mi.',
        4 => 'Do.',
        5 => 'Fr.',
        6 => 'Sa.',
        0 => 'So.',
    ];

    /**
     * German month abbreviations
     */
    private const MONTHS = [
        1 => 'Jan.',
        2 => 'Feb.',
        3 => 'Mär.',
        4 => 'Apr.',
        5 => 'Mai',
        6 => 'Jun.',
        7 => 'Jul.',
        8 => 'Aug.',
        9 => 'Sep.',
        10 => 'Okt.',
        11 => 'Nov.',
        12 => 'Dez.',
    ];

    /**
     * Parse a relative time string and return formatted absolute timestamp.
     *
     * @param string $relativeTime The relative time string (e.g., "seit fünfzehn Minuten")
     * @param Carbon|null $referenceTime The reference point (defaults to now)
     * @return array{original: string, absolute: string|null, formatted: string}
     */
    public function parse(string $relativeTime, ?Carbon $referenceTime = null): array
    {
        $referenceTime = $referenceTime ?? now();
        $original = trim($relativeTime);

        // Try to parse absolute date/time first (heute, gestern, am 24. Dezember, 13 Uhr)
        $absoluteTime = $this->parseAbsoluteDateTime($original, $referenceTime);

        if ($absoluteTime !== null) {
            // Successfully parsed absolute date/time
            $formatted = $this->formatWithQuotedOriginal($absoluteTime, $original);

            return [
                'original' => $original,
                'absolute' => $absoluteTime->toIso8601String(),
                'formatted' => $formatted,
            ];
        }

        // Try to parse relative duration (seit X Minuten/Stunden)
        $minutes = $this->extractMinutes($original);

        if ($minutes !== null) {
            // Calculate absolute time from duration
            $absoluteTime = $referenceTime->copy()->subMinutes((int) $minutes);
            $formatted = $this->formatWithQuotedOriginal($absoluteTime, $original);

            return [
                'original' => $original,
                'absolute' => $absoluteTime->toIso8601String(),
                'formatted' => $formatted,
            ];
        }

        // Could not parse - return original without absolute time
        return [
            'original' => $original,
            'absolute' => null,
            'formatted' => $original,
        ];
    }

    /**
     * Parse absolute date/time expressions from German natural language.
     *
     * Examples:
     * - "seit heute, ungefähr dreizehn Uhr" → Carbon for today 13:00
     * - "seit gestern Abend" → Carbon for yesterday 18:00
     * - "am vierundzwanzigsten Dezember, 13 Uhr" → Carbon for Dec 24, 13:00
     *
     * @param string $text The text to parse
     * @param Carbon $referenceTime The reference time
     * @return Carbon|null Parsed datetime or null
     */
    private function parseAbsoluteDateTime(string $text, Carbon $referenceTime): ?Carbon
    {
        $text = mb_strtolower(trim($text));

        // Remove common prefixes iteratively (handles "seit circa", "vor ungefähr", etc.)
        $prefixPattern = '/^(seit|vor|ab|um|ca\.?|circa|etwa|ungefähr|so)\s*/u';
        $maxIterations = 5;
        $iterations = 0;
        while ($iterations < $maxIterations && preg_match($prefixPattern, $text)) {
            $text = preg_replace($prefixPattern, '', $text);
            $iterations++;
        }

        // Start with reference time as base
        $result = $referenceTime->copy();
        $dateFound = false;
        $timeFound = false;

        // === DATE PARSING ===

        // Pattern: "heute"
        if (preg_match('/\bheute\b/u', $text)) {
            // Keep current date
            $dateFound = true;
        }

        // Pattern: "gestern"
        if (preg_match('/\bgestern\b/u', $text)) {
            $result->subDay();
            $dateFound = true;
        }

        // Pattern: "vorgestern"
        if (preg_match('/\bvorgestern\b/u', $text)) {
            $result->subDays(2);
            $dateFound = true;
        }

        // Pattern: "am X." or "am Xten" (day of month with ordinal)
        foreach (self::ORDINAL_WORDS as $word => $dayNum) {
            if (preg_match('/\b' . preg_quote($word, '/') . '\b/u', $text)) {
                $result->day($dayNum);
                $dateFound = true;
                break;
            }
        }

        // Pattern: "am X." with numeric day
        if (preg_match('/\bam\s+(\d{1,2})\b/u', $text, $matches)) {
            $result->day((int) $matches[1]);
            $dateFound = true;
        }

        // Pattern: Month name (Dezember, Januar, etc.)
        foreach (self::MONTH_NAMES as $monthName => $monthNum) {
            if (preg_match('/\b' . preg_quote($monthName, '/') . '\b/u', $text)) {
                $result->month($monthNum);
                // If month is in the future, assume last year
                if ($result->month > $referenceTime->month && !preg_match('/\b\d{4}\b/', $text)) {
                    // Only if no explicit year given
                }
                $dateFound = true;
                break;
            }
        }

        // === TIME PARSING ===

        // Pattern: "X Uhr" with number word (dreizehn Uhr)
        foreach (self::NUMBER_WORDS as $word => $num) {
            if ($num >= 0 && $num <= 24 && preg_match('/\b' . preg_quote($word, '/') . '\s*uhr\b/u', $text)) {
                $result->hour((int) $num)->minute(0)->second(0);
                $timeFound = true;
                break;
            }
        }

        // Pattern: "X Uhr" with numeric (13 Uhr, 13:00 Uhr)
        if (preg_match('/\b(\d{1,2})(?::(\d{2}))?\s*uhr\b/u', $text, $matches)) {
            $result->hour((int) $matches[1]);
            $result->minute(isset($matches[2]) ? (int) $matches[2] : 0);
            $result->second(0);
            $timeFound = true;
        }

        // Pattern: "um X" with number word (um dreizehn)
        if (!$timeFound) {
            foreach (self::NUMBER_WORDS as $word => $num) {
                if ($num >= 0 && $num <= 24 && preg_match('/\bum\s+' . preg_quote($word, '/') . '\b/u', $text)) {
                    $result->hour((int) $num)->minute(0)->second(0);
                    $timeFound = true;
                    break;
                }
            }
        }

        // Pattern: "um X:XX" or "um X" with numeric
        if (!$timeFound && preg_match('/\bum\s+(\d{1,2})(?::(\d{2}))?\b/u', $text, $matches)) {
            $result->hour((int) $matches[1]);
            $result->minute(isset($matches[2]) ? (int) $matches[2] : 0);
            $result->second(0);
            $timeFound = true;
        }

        // Pattern: "halb X" (half past X-1, e.g., "halb zwölf" = 11:30)
        if (!$timeFound) {
            foreach (self::NUMBER_WORDS as $word => $num) {
                if ($num >= 1 && $num <= 24 && preg_match('/\bhalb\s+' . preg_quote($word, '/') . '\b/u', $text)) {
                    $result->hour((int) $num - 1)->minute(30)->second(0);
                    $timeFound = true;
                    break;
                }
            }
        }

        // Pattern: Time of day words (morgens, abends, etc.)
        if (!$timeFound) {
            foreach (self::TIME_OF_DAY as $word => $hour) {
                if (preg_match('/\b' . preg_quote($word, '/') . '\b/u', $text)) {
                    $result->hour($hour)->minute(0)->second(0);
                    $timeFound = true;
                    break;
                }
            }
        }

        // Only return if we found at least date OR time
        if ($dateFound || $timeFound) {
            // If only date found, set time to current reference time
            if ($dateFound && !$timeFound) {
                $result->hour($referenceTime->hour)->minute($referenceTime->minute);
            }

            return $result;
        }

        return null;
    }

    /**
     * Convenience method: Get formatted string with absolute time.
     *
     * @param string $relativeTime The relative time string
     * @param Carbon|null $referenceTime The reference point
     * @return string Formatted string like "seit 15 Minuten (17:06 Uhr, Mo. 23. Dez. 2025)"
     */
    public function format(string $relativeTime, ?Carbon $referenceTime = null): string
    {
        return $this->parse($relativeTime, $referenceTime)['formatted'];
    }

    /**
     * Extract minutes from a German relative time expression.
     *
     * @param string $text The text to parse
     * @return float|null Minutes, or null if not parseable
     */
    private function extractMinutes(string $text): ?float
    {
        $text = mb_strtolower(trim($text));

        // Remove common prefixes iteratively (handles "seit circa", "vor ungefähr", etc.)
        $prefixPattern = '/^(seit|vor|ca\.?|circa|etwa|ungefähr|so)\s+/u';
        $maxIterations = 5;
        $iterations = 0;
        while ($iterations < $maxIterations && preg_match($prefixPattern, $text)) {
            $text = preg_replace($prefixPattern, '', $text);
            $iterations++;
        }

        // Try patterns in order of specificity

        // Pattern 1: "X Minuten" or "X Minute"
        if (preg_match('/^(\d+|[a-zäöüß]+)\s*minut/u', $text, $matches)) {
            return $this->parseNumber($matches[1]);
        }

        // Pattern 2: "X Stunden" or "X Stunde"
        if (preg_match('/^(\d+|[a-zäöüß]+)\s*stund/u', $text, $matches)) {
            $hours = $this->parseNumber($matches[1]);
            return $hours !== null ? $hours * 60 : null;
        }

        // Pattern 3: "X Stunden und Y Minuten"
        if (preg_match('/^(\d+|[a-zäöüß]+)\s*stund.*?(\d+|[a-zäöüß]+)\s*minut/u', $text, $matches)) {
            $hours = $this->parseNumber($matches[1]);
            $mins = $this->parseNumber($matches[2]);
            if ($hours !== null && $mins !== null) {
                return ($hours * 60) + $mins;
            }
        }

        // Pattern 4: "einer halben Stunde"
        if (preg_match('/halb.*stund/u', $text)) {
            return 30;
        }

        // Pattern 5: "ein paar Minuten" (assume ~5 minutes)
        if (preg_match('/paar\s*minut/u', $text)) {
            return 5;
        }

        // Pattern 6: "kurzem" or "kurz" (assume ~5 minutes)
        if (preg_match('/^kurz/u', $text)) {
            return 5;
        }

        // Pattern 7: "gerade eben" (assume ~2 minutes)
        if (preg_match('/gerade|eben/u', $text)) {
            return 2;
        }

        return null;
    }

    /**
     * Parse a number from string or German word.
     */
    private function parseNumber(string $value): ?float
    {
        $value = mb_strtolower(trim($value));

        // Check if it's a digit
        if (is_numeric($value)) {
            return (float) $value;
        }

        // Check German number words
        if (isset(self::NUMBER_WORDS[$value])) {
            return self::NUMBER_WORDS[$value];
        }

        return null;
    }

    /**
     * Format with quoted original text.
     *
     * New format: "Di, 24. Dez. 13:00 - "seit heute, ungefähr dreizehn Uhr""
     *
     * @param Carbon $absoluteTime Calculated absolute time
     * @param string $original Original text from caller
     * @return string Formatted string with date first, then quoted original
     */
    private function formatWithQuotedOriginal(Carbon $absoluteTime, string $original): string
    {
        $dateFormatted = $this->formatCompactGermanDateTime($absoluteTime);

        return "{$dateFormatted} – \"{$original}\"";
    }

    /**
     * Format a Carbon instance as compact German date/time.
     *
     * New Format: "Di, 24. Dez. 13:00" (compact, without year, without "Uhr")
     *
     * @param Carbon $dateTime The datetime to format
     * @return string Compact German formatted datetime
     */
    public function formatCompactGermanDateTime(Carbon $dateTime): string
    {
        $weekday = self::WEEKDAYS[$dateTime->dayOfWeek];
        $month = self::MONTHS[$dateTime->month];
        $day = $dateTime->day;
        $time = $dateTime->format('H:i');

        return "{$weekday} {$day}. {$month} {$time}";
    }

    /**
     * Format the original text with absolute time appended (legacy format).
     *
     * @param string $original Original relative time text
     * @param Carbon $absoluteTime Calculated absolute time
     * @return string Formatted string
     * @deprecated Use formatWithQuotedOriginal instead
     */
    private function formatWithAbsolute(string $original, Carbon $absoluteTime): string
    {
        $timeFormatted = $this->formatGermanDateTime($absoluteTime);

        return "{$original} ({$timeFormatted})";
    }

    /**
     * Format a Carbon instance as German date/time (legacy format).
     *
     * Format: "17:06 Uhr, Mo. 23. Dez. 2025"
     *
     * @param Carbon $dateTime The datetime to format
     * @return string German formatted datetime
     */
    public function formatGermanDateTime(Carbon $dateTime): string
    {
        $weekday = self::WEEKDAYS[$dateTime->dayOfWeek];
        $month = self::MONTHS[$dateTime->month];
        $day = $dateTime->day;
        $year = $dateTime->year;
        $time = $dateTime->format('H:i');

        return "{$time} Uhr, {$weekday} {$day}. {$month} {$year}";
    }

    /**
     * Check if a string contains a parseable time expression.
     *
     * @param string $text Text to check
     * @return bool
     */
    public function isParseable(string $text): bool
    {
        // Check for relative time (seit X Minuten)
        if ($this->extractMinutes($text) !== null) {
            return true;
        }

        // Check for absolute time (heute, gestern, X Uhr)
        $result = $this->parseAbsoluteDateTime($text, now());

        return $result !== null;
    }
}
