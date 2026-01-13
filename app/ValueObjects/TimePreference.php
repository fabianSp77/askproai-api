<?php

namespace App\ValueObjects;

use Carbon\Carbon;

/**
 * TimePreference Value Object
 *
 * Represents a customer's time preference for appointment booking.
 * Used by AppointmentAlternativeFinder to search for alternatives
 * within the customer's preferred time window.
 *
 * @package App\ValueObjects
 */
class TimePreference
{
    // Preference types
    public const TYPE_ANY = 'any';           // No preference, any time works
    public const TYPE_EXACT = 'exact';       // Exact time requested (e.g., "14:00")
    public const TYPE_WINDOW = 'window';     // Time window (e.g., "Vormittag" = 09:00-12:00)
    public const TYPE_FROM = 'from';         // From time onwards (e.g., "ab 14 Uhr")
    public const TYPE_RANGE = 'range';       // Specific range (e.g., "zwischen 10 und 12")

    /**
     * @var string Preference type (one of TYPE_* constants)
     */
    public readonly string $type;

    /**
     * @var string|null Start of time window (format: "HH:MM" or "HH")
     */
    public readonly ?string $windowStart;

    /**
     * @var string|null End of time window (format: "HH:MM" or "HH")
     */
    public readonly ?string $windowEnd;

    /**
     * @var string|null Human-readable label (e.g., "Vormittag", "Nachmittag")
     */
    public readonly ?string $label;

    /**
     * Create a new TimePreference instance
     *
     * @param string $type Preference type
     * @param string|null $windowStart Start time (format: "HH:MM" or "HH")
     * @param string|null $windowEnd End time (format: "HH:MM" or "HH")
     * @param string|null $label Human-readable label
     */
    public function __construct(
        string $type = self::TYPE_ANY,
        ?string $windowStart = null,
        ?string $windowEnd = null,
        ?string $label = null
    ) {
        $this->type = $type;
        $this->windowStart = $this->normalizeTime($windowStart);
        $this->windowEnd = $this->normalizeTime($windowEnd);
        $this->label = $label;
    }

    /**
     * Create a preference with no time restriction
     *
     * @return self
     */
    public static function any(): self
    {
        return new self(self::TYPE_ANY);
    }

    /**
     * Create a preference for an exact time
     *
     * @param string $time Time in "HH:MM" format
     * @return self
     */
    public static function exact(string $time): self
    {
        return new self(
            self::TYPE_EXACT,
            $time,
            $time,
            "um {$time} Uhr"
        );
    }

    /**
     * Create a preference for a time window
     *
     * @param string $start Start time (format: "HH:MM" or "HH")
     * @param string $end End time (format: "HH:MM" or "HH")
     * @param string|null $label Optional label (e.g., "Vormittag")
     * @return self
     */
    public static function window(string $start, string $end, ?string $label = null): self
    {
        return new self(
            self::TYPE_WINDOW,
            $start,
            $end,
            $label ?? "zwischen {$start} und {$end} Uhr"
        );
    }

    /**
     * Create a preference for "from X onwards"
     *
     * @param string $from Start time
     * @param string|null $label Optional label
     * @return self
     */
    public static function from(string $from, ?string $label = null): self
    {
        return new self(
            self::TYPE_FROM,
            $from,
            '18:00', // Default to end of business hours
            $label ?? "ab {$from} Uhr"
        );
    }

    /**
     * Create a "Vormittag" (morning) preference (09:00-12:00)
     *
     * @return self
     */
    public static function vormittag(): self
    {
        return new self(
            self::TYPE_WINDOW,
            '09:00',
            '12:00',
            'vormittags'
        );
    }

    /**
     * Create a "Nachmittag" (afternoon) preference (12:00-18:00)
     *
     * @return self
     */
    public static function nachmittag(): self
    {
        return new self(
            self::TYPE_WINDOW,
            '12:00',
            '18:00',
            'nachmittags'
        );
    }

    /**
     * Create a "Mittag" (midday) preference (11:00-14:00)
     *
     * @return self
     */
    public static function mittag(): self
    {
        return new self(
            self::TYPE_WINDOW,
            '11:00',
            '14:00',
            'mittags'
        );
    }

    /**
     * Check if this preference has a time window defined
     *
     * @return bool
     */
    public function hasWindow(): bool
    {
        return $this->windowStart !== null && $this->windowEnd !== null;
    }

    /**
     * Check if a given datetime falls within this preference's time window
     *
     * @param Carbon $dateTime The datetime to check
     * @return bool True if datetime is within the window (or if no window is defined)
     */
    public function containsDateTime(Carbon $dateTime): bool
    {
        // If no window, everything matches
        if (!$this->hasWindow()) {
            return true;
        }

        $timeString = $dateTime->format('H:i');

        return $timeString >= $this->windowStart && $timeString <= $this->windowEnd;
    }

    /**
     * Get a German-language label for this preference
     *
     * @return string
     */
    public function getGermanLabel(): string
    {
        if ($this->label !== null) {
            return $this->label;
        }

        return match($this->type) {
            self::TYPE_ANY => 'beliebige Zeit',
            self::TYPE_EXACT => $this->windowStart ? "um {$this->windowStart} Uhr" : 'genaue Zeit',
            self::TYPE_WINDOW => $this->windowStart && $this->windowEnd
                ? "zwischen {$this->windowStart} und {$this->windowEnd} Uhr"
                : 'Zeitfenster',
            self::TYPE_FROM => $this->windowStart ? "ab {$this->windowStart} Uhr" : 'ab bestimmter Zeit',
            self::TYPE_RANGE => $this->windowStart && $this->windowEnd
                ? "von {$this->windowStart} bis {$this->windowEnd} Uhr"
                : 'Zeitraum',
            default => 'unbekannte Präferenz'
        };
    }

    /**
     * Normalize time string to HH:MM format
     *
     * @param string|null $time Time string (e.g., "9", "09", "9:00", "09:00")
     * @return string|null Normalized time (e.g., "09:00") or null
     */
    private function normalizeTime(?string $time): ?string
    {
        if ($time === null) {
            return null;
        }

        // If already in HH:MM format
        if (preg_match('/^\d{2}:\d{2}$/', $time)) {
            return $time;
        }

        // If in H:MM format (e.g., "9:00")
        if (preg_match('/^(\d{1,2}):(\d{2})$/', $time, $matches)) {
            return sprintf('%02d:%s', (int)$matches[1], $matches[2]);
        }

        // If just hours (e.g., "9" or "09")
        if (preg_match('/^\d{1,2}$/', $time)) {
            return sprintf('%02d:00', (int)$time);
        }

        // Return as-is if format is unknown
        return $time;
    }

    /**
     * Create TimePreference from a natural language time expression
     *
     * @param string $expression Natural language expression (e.g., "vormittags", "ab 14 Uhr")
     * @return self
     */
    public static function fromExpression(string $expression): self
    {
        $expression = mb_strtolower(trim($expression));

        // Check for known time windows
        if (str_contains($expression, 'vormittag') || str_contains($expression, 'morgen')) {
            return self::vormittag();
        }

        if (str_contains($expression, 'nachmittag')) {
            return self::nachmittag();
        }

        if (str_contains($expression, 'mittag')) {
            return self::mittag();
        }

        // Check for "ab X Uhr" pattern
        if (preg_match('/ab\s*(\d{1,2})(?:\s*uhr)?/i', $expression, $matches)) {
            return self::from($matches[1]);
        }

        // Check for "zwischen X und Y" pattern
        if (preg_match('/zwischen\s*(\d{1,2})\s*(?:und|bis)\s*(\d{1,2})/i', $expression, $matches)) {
            return self::window($matches[1], $matches[2]);
        }

        // Check for explicit time (e.g., "14 Uhr", "14:30")
        if (preg_match('/(\d{1,2})(?::(\d{2}))?\s*uhr/i', $expression, $matches)) {
            $time = sprintf('%02d:%s', (int)$matches[1], $matches[2] ?? '00');
            return self::exact($time);
        }

        // Default to any
        return self::any();
    }

    /**
     * Convert to array representation
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'window_start' => $this->windowStart,
            'window_end' => $this->windowEnd,
            'label' => $this->label,
        ];
    }
}
