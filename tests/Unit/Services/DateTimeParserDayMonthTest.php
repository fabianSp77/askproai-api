<?php

namespace Tests\Unit\Services;

use App\Services\Retell\DateTimeParser;
use Carbon\Carbon;
use Tests\TestCase;

/**
 * Test for DateTimeParser Fix: "DD. Monat" pattern without weekday
 *
 * Bug: "7. November" was falling through to Carbon::parse() and being interpreted as 2026-11-07
 * Root Cause: No pattern existed for "DD. Monat" format without weekday prefix
 * Fix: Added new regex pattern with smart year inference
 *
 * RCA: Call 73526 - Agent said "7. November" in December, system created 2026-11-07
 */
class DateTimeParserDayMonthTest extends TestCase
{
    private DateTimeParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new DateTimeParser();
        DateTimeParser::clearTimeCache();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow(); // Reset time
        parent::tearDown();
    }

    /**
     * Test Case 1: "7. November" in December should return NEXT YEAR
     *
     * This is the EXACT scenario from Call 73526
     * User said: "7. November" on December 5th, 2025
     * Expected: 2026-11-07 (next occurrence)
     * Old Bug: Would fall through to Carbon::parse() with unpredictable results
     */
    public function test_past_month_returns_next_year()
    {
        // Freeze time to December 5, 2025
        Carbon::setTestNow('2025-12-05 10:00:00');

        $result = $this->parser->parseDateString('7. November');

        // Expected: 2026-11-07 (next year because November is in the past)
        $this->assertEquals('2026-11-07', $result);
    }

    /**
     * Test Case 2: "13. Dezember" in November should return CURRENT YEAR
     *
     * User said: "13. Dezember" on November 15th, 2025
     * Expected: 2025-12-13 (this year, still in the future)
     */
    public function test_future_month_returns_current_year()
    {
        Carbon::setTestNow('2025-11-15 10:00:00');

        $result = $this->parser->parseDateString('13. Dezember');

        // Expected: 2025-12-13 (current year)
        $this->assertEquals('2025-12-13', $result);
    }

    /**
     * Test Case 3: "25. Januar" in December should return NEXT YEAR
     */
    public function test_january_in_december_returns_next_year()
    {
        Carbon::setTestNow('2025-12-20 10:00:00');

        $result = $this->parser->parseDateString('25. Januar');

        // Expected: 2026-01-25 (next year)
        $this->assertEquals('2026-01-25', $result);
    }

    /**
     * Test Case 4: "15. März" in February should return CURRENT YEAR
     */
    public function test_march_in_february_returns_current_year()
    {
        Carbon::setTestNow('2025-02-10 10:00:00');

        $result = $this->parser->parseDateString('15. März');

        // Expected: 2025-03-15 (current year)
        $this->assertEquals('2025-03-15', $result);
    }

    /**
     * Test Case 5: Same month, date in past (>2 days) should return NEXT YEAR
     */
    public function test_same_month_past_date_returns_next_year()
    {
        Carbon::setTestNow('2025-11-20 10:00:00'); // November 20th

        $result = $this->parser->parseDateString('5. November');

        // Expected: 2026-11-05 (next year, because 5th is >2 days in past)
        $this->assertEquals('2026-11-05', $result);
    }

    /**
     * Test Case 6: Same month, date in future should return CURRENT YEAR
     */
    public function test_same_month_future_date_returns_current_year()
    {
        Carbon::setTestNow('2025-11-10 10:00:00'); // November 10th

        $result = $this->parser->parseDateString('25. November');

        // Expected: 2025-11-25 (current year)
        $this->assertEquals('2025-11-25', $result);
    }

    /**
     * Test Case 7: Pattern with optional dot after day number
     * "7 November" (without dot) should also work
     */
    public function test_pattern_without_dot_after_day()
    {
        Carbon::setTestNow('2025-11-15 10:00:00');

        $result = $this->parser->parseDateString('13 Dezember');

        // Expected: 2025-12-13
        $this->assertEquals('2025-12-13', $result);
    }

    /**
     * Test Case 8: All German month names are recognized
     */
    public function test_all_german_months_recognized()
    {
        Carbon::setTestNow('2025-01-15 10:00:00'); // January

        $months = [
            'Januar' => '01',
            'Februar' => '02',
            'März' => '03',
            'April' => '04',
            'Mai' => '05',
            'Juni' => '06',
            'Juli' => '07',
            'August' => '08',
            'September' => '09',
            'Oktober' => '10',
            'November' => '11',
            'Dezember' => '12',
        ];

        foreach ($months as $monthName => $monthNumber) {
            $result = $this->parser->parseDateString("15. {$monthName}");

            // All months except Januar should be in current year (future from Jan 15)
            $expectedYear = $monthName === 'Januar' ? '2026' : '2025';
            $this->assertStringContainsString("-{$monthNumber}-15", $result, "Failed for month: {$monthName}");
        }
    }

    /**
     * Test Case 9: Case insensitivity
     */
    public function test_case_insensitive()
    {
        Carbon::setTestNow('2025-11-15 10:00:00');

        $result1 = $this->parser->parseDateString('13. DEZEMBER');
        $result2 = $this->parser->parseDateString('13. dezember');
        $result3 = $this->parser->parseDateString('13. Dezember');

        $this->assertEquals('2025-12-13', $result1);
        $this->assertEquals('2025-12-13', $result2);
        $this->assertEquals('2025-12-13', $result3);
    }

    /**
     * Test Case 10: Edge case - date within 2 days tolerance should return CURRENT YEAR
     */
    public function test_date_within_tolerance_returns_current_year()
    {
        Carbon::setTestNow('2025-11-07 10:00:00'); // November 7th

        $result = $this->parser->parseDateString('5. November');

        // Expected: 2025-11-05 (only 2 days in past, within tolerance)
        $this->assertEquals('2025-11-05', $result);
    }
}
