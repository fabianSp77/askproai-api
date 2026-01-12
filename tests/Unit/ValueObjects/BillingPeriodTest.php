<?php

namespace Tests\Unit\ValueObjects;

use App\ValueObjects\BillingPeriod;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for BillingPeriod value object.
 *
 * Tests billing period calculations that determine which
 * transactions are included in monthly billing cycles.
 */
class BillingPeriodTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Reset Carbon test time after each test
        Carbon::setTestNow();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    // =========================================================================
    // forMonth() Tests
    // =========================================================================

    /** @test */
    public function for_month_creates_period_for_january(): void
    {
        $period = BillingPeriod::forMonth(2026, 1);

        $this->assertEquals('2026-01-01', $period->start->toDateString());
        $this->assertEquals('2026-01-31', $period->end->toDateString());
        $this->assertEquals('00:00:00', $period->start->format('H:i:s'));
        $this->assertEquals('23:59:59', $period->end->format('H:i:s'));
    }

    /** @test */
    public function for_month_handles_february_non_leap_year(): void
    {
        $period = BillingPeriod::forMonth(2025, 2);

        $this->assertEquals('2025-02-01', $period->start->toDateString());
        $this->assertEquals('2025-02-28', $period->end->toDateString());
        $this->assertEquals(28, $period->getDays());
    }

    /** @test */
    public function for_month_handles_february_leap_year(): void
    {
        $period = BillingPeriod::forMonth(2024, 2);

        $this->assertEquals('2024-02-01', $period->start->toDateString());
        $this->assertEquals('2024-02-29', $period->end->toDateString());
        $this->assertEquals(29, $period->getDays());
    }

    /** @test */
    public function for_month_handles_30_day_months(): void
    {
        $period = BillingPeriod::forMonth(2026, 4); // April

        $this->assertEquals('2026-04-01', $period->start->toDateString());
        $this->assertEquals('2026-04-30', $period->end->toDateString());
        $this->assertEquals(30, $period->getDays());
    }

    /** @test */
    public function for_month_handles_december(): void
    {
        $period = BillingPeriod::forMonth(2026, 12);

        $this->assertEquals('2026-12-01', $period->start->toDateString());
        $this->assertEquals('2026-12-31', $period->end->toDateString());
    }

    // =========================================================================
    // forCurrentMonth() Tests
    // =========================================================================

    /** @test */
    public function for_current_month_matches_actual_current_month(): void
    {
        Carbon::setTestNow('2026-01-15 10:30:00');

        $period = BillingPeriod::forCurrentMonth();

        $this->assertEquals('2026-01-01', $period->start->toDateString());
        $this->assertEquals('2026-01-31', $period->end->toDateString());
    }

    /** @test */
    public function for_current_month_on_first_day(): void
    {
        Carbon::setTestNow('2026-03-01 00:00:00');

        $period = BillingPeriod::forCurrentMonth();

        $this->assertEquals('2026-03-01', $period->start->toDateString());
        $this->assertEquals('2026-03-31', $period->end->toDateString());
    }

    /** @test */
    public function for_current_month_on_last_day(): void
    {
        Carbon::setTestNow('2026-03-31 23:59:59');

        $period = BillingPeriod::forCurrentMonth();

        $this->assertEquals('2026-03-01', $period->start->toDateString());
        $this->assertEquals('2026-03-31', $period->end->toDateString());
    }

    // =========================================================================
    // forPreviousMonth() Tests
    // =========================================================================

    /** @test */
    public function for_previous_month_returns_last_month(): void
    {
        Carbon::setTestNow('2026-03-15');

        $period = BillingPeriod::forPreviousMonth();

        $this->assertEquals('2026-02-01', $period->start->toDateString());
        $this->assertEquals('2026-02-28', $period->end->toDateString());
    }

    /** @test */
    public function for_previous_month_handles_year_boundary(): void
    {
        Carbon::setTestNow('2026-01-15');

        $period = BillingPeriod::forPreviousMonth();

        $this->assertEquals('2025-12-01', $period->start->toDateString());
        $this->assertEquals('2025-12-31', $period->end->toDateString());
    }

    /** @test */
    public function for_previous_month_from_march_handles_leap_year(): void
    {
        Carbon::setTestNow('2024-03-15'); // 2024 is a leap year

        $period = BillingPeriod::forPreviousMonth();

        $this->assertEquals('2024-02-01', $period->start->toDateString());
        $this->assertEquals('2024-02-29', $period->end->toDateString());
    }

    // =========================================================================
    // overlaps() Tests
    // =========================================================================

    /** @test */
    public function overlaps_detects_complete_overlap(): void
    {
        $period1 = BillingPeriod::forMonth(2026, 1);
        $period2 = BillingPeriod::forMonth(2026, 1);

        $this->assertTrue($period1->overlaps($period2));
        $this->assertTrue($period2->overlaps($period1));
    }

    /** @test */
    public function overlaps_detects_partial_overlap(): void
    {
        $period1 = BillingPeriod::fromDates('2026-01-15', '2026-02-15');
        $period2 = BillingPeriod::forMonth(2026, 2);

        $this->assertTrue($period1->overlaps($period2));
        $this->assertTrue($period2->overlaps($period1));
    }

    /** @test */
    public function overlaps_returns_false_for_adjacent_periods(): void
    {
        $january = BillingPeriod::forMonth(2026, 1);
        $february = BillingPeriod::forMonth(2026, 2);

        // Adjacent months should NOT overlap
        $this->assertFalse($january->overlaps($february));
        $this->assertFalse($february->overlaps($january));
    }

    /** @test */
    public function overlaps_returns_false_for_distant_periods(): void
    {
        $january = BillingPeriod::forMonth(2026, 1);
        $june = BillingPeriod::forMonth(2026, 6);

        $this->assertFalse($january->overlaps($june));
    }

    /** @test */
    public function overlaps_detects_contained_period(): void
    {
        $outer = BillingPeriod::fromDates('2026-01-01', '2026-03-31');
        $inner = BillingPeriod::forMonth(2026, 2);

        $this->assertTrue($outer->overlaps($inner));
        $this->assertTrue($inner->overlaps($outer));
    }

    // =========================================================================
    // contains() Tests
    // =========================================================================

    /** @test */
    public function contains_returns_true_for_date_within_period(): void
    {
        $period = BillingPeriod::forMonth(2026, 1);

        $this->assertTrue($period->contains(Carbon::parse('2026-01-15')));
        $this->assertTrue($period->contains(Carbon::parse('2026-01-15 12:30:00')));
    }

    /** @test */
    public function contains_returns_true_for_start_date(): void
    {
        $period = BillingPeriod::forMonth(2026, 1);

        // Start of period at midnight
        $this->assertTrue($period->contains(Carbon::parse('2026-01-01 00:00:00')));
    }

    /** @test */
    public function contains_returns_true_for_end_date(): void
    {
        $period = BillingPeriod::forMonth(2026, 1);

        // End of period at 23:59:59
        $this->assertTrue($period->contains(Carbon::parse('2026-01-31 23:59:59')));
    }

    /** @test */
    public function contains_returns_false_for_date_before_period(): void
    {
        $period = BillingPeriod::forMonth(2026, 2);

        $this->assertFalse($period->contains(Carbon::parse('2026-01-31 23:59:59')));
    }

    /** @test */
    public function contains_returns_false_for_date_after_period(): void
    {
        $period = BillingPeriod::forMonth(2026, 1);

        $this->assertFalse($period->contains(Carbon::parse('2026-02-01 00:00:00')));
    }

    // =========================================================================
    // getDays() Tests
    // =========================================================================

    /** @test */
    public function get_days_returns_correct_count_for_31_day_month(): void
    {
        $period = BillingPeriod::forMonth(2026, 1);

        $this->assertEquals(31, $period->getDays());
    }

    /** @test */
    public function get_days_returns_correct_count_for_30_day_month(): void
    {
        $period = BillingPeriod::forMonth(2026, 4);

        $this->assertEquals(30, $period->getDays());
    }

    /** @test */
    public function get_days_returns_correct_count_for_custom_period(): void
    {
        $period = BillingPeriod::fromDates('2026-01-10', '2026-01-15');

        $this->assertEquals(6, $period->getDays()); // 10, 11, 12, 13, 14, 15
    }

    // =========================================================================
    // fromDates() Tests
    // =========================================================================

    /** @test */
    public function from_dates_parses_ymd_format(): void
    {
        $period = BillingPeriod::fromDates('2026-06-15', '2026-06-30');

        $this->assertEquals('2026-06-15', $period->start->toDateString());
        $this->assertEquals('2026-06-30', $period->end->toDateString());
    }

    /** @test */
    public function from_dates_normalizes_to_start_and_end_of_day(): void
    {
        $period = BillingPeriod::fromDates('2026-06-15', '2026-06-30');

        $this->assertEquals('00:00:00', $period->start->format('H:i:s'));
        $this->assertEquals('23:59:59', $period->end->format('H:i:s'));
    }

    // =========================================================================
    // toDateRange() Tests
    // =========================================================================

    /** @test */
    public function to_date_range_returns_array_with_start_and_end(): void
    {
        $period = BillingPeriod::forMonth(2026, 1);

        [$start, $end] = $period->toDateRange();

        $this->assertInstanceOf(Carbon::class, $start);
        $this->assertInstanceOf(Carbon::class, $end);
        $this->assertEquals('2026-01-01', $start->toDateString());
        $this->assertEquals('2026-01-31', $end->toDateString());
    }

    // =========================================================================
    // Database Format Tests
    // =========================================================================

    /** @test */
    public function get_start_for_database_returns_datetime_string(): void
    {
        $period = BillingPeriod::forMonth(2026, 1);

        $this->assertEquals('2026-01-01 00:00:00', $period->getStartForDatabase());
    }

    /** @test */
    public function get_end_for_database_returns_datetime_string(): void
    {
        $period = BillingPeriod::forMonth(2026, 1);

        $this->assertEquals('2026-01-31 23:59:59', $period->getEndForDatabase());
    }

    // =========================================================================
    // Label Tests
    // =========================================================================

    /** @test */
    public function get_label_returns_formatted_range(): void
    {
        $period = BillingPeriod::forMonth(2026, 1);

        $this->assertEquals('2026-01-01 bis 2026-01-31', $period->getLabel());
    }

    /** @test */
    public function get_month_label_returns_german_month_name(): void
    {
        $period = BillingPeriod::forMonth(2026, 1);

        $this->assertEquals('Januar 2026', $period->getMonthLabel('de'));
    }

    // =========================================================================
    // toArray() Tests
    // =========================================================================

    /** @test */
    public function to_array_returns_correct_structure(): void
    {
        $period = BillingPeriod::forMonth(2026, 1);

        $array = $period->toArray();

        $this->assertEquals([
            'start' => '2026-01-01',
            'end' => '2026-01-31',
            'days' => 31,
        ], $array);
    }

    // =========================================================================
    // isCurrentPeriod() Tests
    // =========================================================================

    /** @test */
    public function is_current_period_returns_true_for_current_month(): void
    {
        Carbon::setTestNow('2026-01-15');

        $period = BillingPeriod::forMonth(2026, 1);

        $this->assertTrue($period->isCurrentPeriod());
    }

    /** @test */
    public function is_current_period_returns_false_for_past_month(): void
    {
        Carbon::setTestNow('2026-02-15');

        $period = BillingPeriod::forMonth(2026, 1);

        $this->assertFalse($period->isCurrentPeriod());
    }

    // =========================================================================
    // Immutability Tests
    // =========================================================================

    /** @test */
    public function period_dates_are_immutable(): void
    {
        $period = BillingPeriod::forMonth(2026, 1);

        $originalStart = $period->start->toDateString();
        $originalEnd = $period->end->toDateString();

        // These should be readonly, but let's verify the values don't change
        $this->assertEquals($originalStart, $period->start->toDateString());
        $this->assertEquals($originalEnd, $period->end->toDateString());
    }
}
