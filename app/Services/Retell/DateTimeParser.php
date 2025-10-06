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
 */
class DateTimeParser
{
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
            return Carbon::parse($params['date'] . ' ' . $params['time']);
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

        // Try parsing German date format (DD.MM.YYYY or D.M.YYYY)
        if (preg_match('/^(\d{1,2})\.(\d{1,2})\.(\d{4})$/', $dateString, $matches)) {
            try {
                $carbon = Carbon::createFromFormat('d.m.Y', $dateString);
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
}
