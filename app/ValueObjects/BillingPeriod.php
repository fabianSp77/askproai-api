<?php

namespace App\ValueObjects;

use Carbon\Carbon;

/**
 * BillingPeriod Value Object
 *
 * Represents a billing period with start and end dates.
 * Used by MonthlyBillingAggregator to standardize period handling.
 */
class BillingPeriod
{
    /**
     * @var Carbon Start of the billing period (inclusive)
     */
    public readonly Carbon $start;

    /**
     * @var Carbon End of the billing period (inclusive)
     */
    public readonly Carbon $end;

    /**
     * Create a new BillingPeriod instance
     *
     * @param  Carbon  $start  Start date (will be set to start of day)
     * @param  Carbon  $end  End date (will be set to end of day)
     */
    public function __construct(Carbon $start, Carbon $end)
    {
        $this->start = $start->copy()->startOfDay();
        $this->end = $end->copy()->endOfDay();
    }

    /**
     * Create a billing period for a specific month
     *
     * @param  int  $year  Year (e.g., 2026)
     * @param  int  $month  Month (1-12)
     */
    public static function forMonth(int $year, int $month): self
    {
        $start = Carbon::create($year, $month, 1, 0, 0, 0);
        $end = $start->copy()->endOfMonth();

        return new self($start, $end);
    }

    /**
     * Create a billing period for the current month
     */
    public static function forCurrentMonth(): self
    {
        $now = Carbon::now();

        return self::forMonth($now->year, $now->month);
    }

    /**
     * Create a billing period for the previous month
     */
    public static function forPreviousMonth(): self
    {
        $lastMonth = Carbon::now()->subMonth();

        return self::forMonth($lastMonth->year, $lastMonth->month);
    }

    /**
     * Get the period as a date range array
     *
     * Returns: [$start, $end]
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    public function toDateRange(): array
    {
        return [$this->start, $this->end];
    }

    /**
     * Check if this billing period overlaps with another period
     *
     * @param  BillingPeriod  $other  The other billing period
     * @return bool True if the periods overlap
     */
    public function overlaps(BillingPeriod $other): bool
    {
        return $this->start <= $other->end && $this->end >= $other->start;
    }

    /**
     * Check if a given date falls within this billing period
     *
     * @param  Carbon  $date  The date to check
     * @return bool True if the date is within the period (inclusive)
     */
    public function contains(Carbon $date): bool
    {
        return $date >= $this->start && $date <= $this->end;
    }

    /**
     * Get the number of days in this billing period
     *
     * @return int Number of days (inclusive)
     */
    public function getDays(): int
    {
        return intval($this->start->diffInDays($this->end)) + 1;
    }

    /**
     * Get a human-readable label for this billing period
     *
     * @param  string  $format  Date format for display (default: 'Y-m-d')
     * @return string Period label (e.g., "2026-01-01 bis 2026-01-31")
     */
    public function getLabel(string $format = 'Y-m-d'): string
    {
        return sprintf(
            '%s bis %s',
            $this->start->format($format),
            $this->end->format($format)
        );
    }

    /**
     * Get a short month label (e.g., "Januar 2026")
     *
     * @param  string  $locale  Locale for month name (default: 'de')
     * @return string Month label
     */
    public function getMonthLabel(string $locale = 'de'): string
    {
        return $this->start->locale($locale)->isoFormat('MMMM YYYY');
    }

    /**
     * Check if this is the current billing period (month)
     *
     * @return bool True if this period contains today
     */
    public function isCurrentPeriod(): bool
    {
        return $this->contains(Carbon::now());
    }

    /**
     * Convert to array representation
     *
     * @return array{start: string, end: string, days: int}
     */
    public function toArray(): array
    {
        return [
            'start' => $this->start->toDateString(),
            'end' => $this->end->toDateString(),
            'days' => $this->getDays(),
        ];
    }

    /**
     * Create a BillingPeriod from a start and end date string
     *
     * @param  string  $startDate  Start date (Y-m-d format)
     * @param  string  $endDate  End date (Y-m-d format)
     */
    public static function fromDates(string $startDate, string $endDate): self
    {
        return new self(
            Carbon::parse($startDate),
            Carbon::parse($endDate)
        );
    }

    /**
     * Get the start date formatted for database queries
     *
     * @return string Start date in Y-m-d H:i:s format
     */
    public function getStartForDatabase(): string
    {
        return $this->start->toDateTimeString();
    }

    /**
     * Get the end date formatted for database queries
     *
     * @return string End date in Y-m-d H:i:s format
     */
    public function getEndForDatabase(): string
    {
        return $this->end->toDateTimeString();
    }
}
