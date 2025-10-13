<?php

namespace Tests\Unit\Services\Retell;

use App\Services\Retell\DateTimeParser;
use Carbon\Carbon;
use Tests\TestCase;
use Illuminate\Support\Facades\Log;

/**
 * Test German Short Date Format (Phase 1.3.3)
 *
 * Bug Fix: "15.1" should be interpreted as Day.Month (15. October)
 * NOT as Month.Day or January 15th!
 *
 * Run: php artisan test --filter DateTimeParserShortFormatTest
 */
class DateTimeParserShortFormatTest extends TestCase
{
    private DateTimeParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new DateTimeParser();

        // Mock current date for consistent testing
        Carbon::setTestNow(Carbon::create(2025, 10, 13, 10, 0, 0, 'Europe/Berlin'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow(); // Reset
        parent::tearDown();
    }

    /**
     * Test "15.1" is interpreted as October 15th, NOT January 15th
     *
     * User says "fünfzehnte Punkt eins" → STT: "15.1"
     * Expected: 15. Oktober (current year)
     */
    public function test_15_1_interpreted_as_october_not_january()
    {
        Log::shouldReceive('info', 'warning', 'error')->andReturnNull();

        $result = $this->parser->parseDateString('15.1');

        $this->assertNotNull($result, '"15.1" should be parseable');

        $parsed = Carbon::parse($result);

        // Critical assertion: Month should be 10 (October) NOT 1 (January)
        $this->assertEquals(10, $parsed->month, '"15.1" should be interpreted as October (month 10), not January (month 1)');
        $this->assertEquals(15, $parsed->day, 'Day should be 15');
        $this->assertEquals(2025, $parsed->year, 'Year should be current year (2025)');

        // Verify it's not in the past
        $this->assertFalse(
            $parsed->isPast() && $parsed->diffInDays(Carbon::now(), true) > 1,
            'Parsed date should not be significantly in the past'
        );
    }

    /**
     * Test "15.10" is interpreted as October 15th
     */
    public function test_15_10_october_format()
    {
        Log::shouldReceive('info', 'warning', 'error')->andReturnNull();

        $result = $this->parser->parseDateString('15.10');

        $this->assertNotNull($result);

        $parsed = Carbon::parse($result);

        $this->assertEquals(10, $parsed->month, 'Month should be October');
        $this->assertEquals(15, $parsed->day, 'Day should be 15');
    }

    /**
     * Test "5.11" is interpreted as November 5th
     */
    public function test_5_11_november_format()
    {
        Log::shouldReceive('info', 'warning', 'error')->andReturnNull();

        $result = $this->parser->parseDateString('5.11');

        $this->assertNotNull($result);

        $parsed = Carbon::parse($result);

        $this->assertEquals(11, $parsed->month, 'Month should be November');
        $this->assertEquals(5, $parsed->day, 'Day should be 5');
        $this->assertEquals(2025, $parsed->year, 'Year should be current year');
    }

    /**
     * Test "1.1" is interpreted as next year January 1st
     * (since it's October, January is in the past → assume next year)
     */
    public function test_1_1_next_year_january()
    {
        Log::shouldReceive('info', 'warning', 'error')->andReturnNull();

        $result = $this->parser->parseDateString('1.1');

        $this->assertNotNull($result);

        $parsed = Carbon::parse($result);

        // January is before October (current month), so should be next year
        $this->assertEquals(1, $parsed->month, 'Month should be January');
        $this->assertEquals(1, $parsed->day, 'Day should be 1');
        $this->assertEquals(2026, $parsed->year, 'Year should be next year (2026) since January is past');
    }

    /**
     * Test "25.10" is interpreted as October 25th (future date in same month)
     */
    public function test_25_10_future_same_month()
    {
        Log::shouldReceive('info', 'warning', 'error')->andReturnNull();

        // Today is October 13, so October 25 is in the future
        $result = $this->parser->parseDateString('25.10');

        $this->assertNotNull($result);

        $parsed = Carbon::parse($result);

        $this->assertEquals(10, $parsed->month, 'Month should be October');
        $this->assertEquals(25, $parsed->day, 'Day should be 25');
        $this->assertEquals(2025, $parsed->year, 'Year should be current year');
        $this->assertTrue($parsed->isFuture(), 'Date should be in the future');
    }

    /**
     * Test "5.10" is interpreted as next month (day already passed)
     */
    public function test_5_10_past_day_same_month()
    {
        Log::shouldReceive('info', 'warning', 'error')->andReturnNull();

        // Today is October 13, so October 5 is in the past → should be next month
        $result = $this->parser->parseDateString('5.10');

        $this->assertNotNull($result);

        $parsed = Carbon::parse($result);

        // Since October 5th is in the past (today is 13th), should add month
        $this->assertEquals(11, $parsed->month, 'Should move to next month (November) since day passed');
        $this->assertEquals(5, $parsed->day, 'Day should be 5');
    }

    /**
     * Test "20.12" is interpreted as December 20th (future month)
     */
    public function test_20_12_future_month()
    {
        Log::shouldReceive('info', 'warning', 'error')->andReturnNull();

        $result = $this->parser->parseDateString('20.12');

        $this->assertNotNull($result);

        $parsed = Carbon::parse($result);

        $this->assertEquals(12, $parsed->month, 'Month should be December');
        $this->assertEquals(20, $parsed->day, 'Day should be 20');
        $this->assertEquals(2025, $parsed->year, 'Year should be current year');
        $this->assertTrue($parsed->isFuture(), 'December should be in future');
    }

    /**
     * Test edge case: "31.2" (invalid date) should handle gracefully
     */
    public function test_invalid_date_31_2()
    {
        Log::shouldReceive('info', 'warning', 'error')->andReturnNull();

        $result = $this->parser->parseDateString('31.2');

        // Should either return null or handle gracefully
        // Carbon may adjust to valid date (e.g., February 28/29)
        if ($result !== null) {
            $parsed = Carbon::parse($result);
            // Should not crash, and month should be reasonable
            $this->assertLessThanOrEqual(12, $parsed->month);
            $this->assertGreaterThanOrEqual(1, $parsed->month);
        }
    }

    /**
     * Integration test: Verify all formats work together
     */
    public function test_multiple_formats_integration()
    {
        Log::shouldReceive('info', 'warning', 'error')->andReturnNull();

        $testCases = [
            '15.1' => ['month' => 10, 'day' => 15],  // October 15
            '15.10' => ['month' => 10, 'day' => 15], // October 15
            '5.11' => ['month' => 11, 'day' => 5],   // November 5
            '20.12' => ['month' => 12, 'day' => 20], // December 20
        ];

        foreach ($testCases as $input => $expected) {
            $result = $this->parser->parseDateString($input);

            $this->assertNotNull($result, "Input '$input' should be parseable");

            $parsed = Carbon::parse($result);

            $this->assertEquals(
                $expected['month'],
                $parsed->month,
                "Input '$input' should have month {$expected['month']}"
            );

            $this->assertEquals(
                $expected['day'],
                $parsed->day,
                "Input '$input' should have day {$expected['day']}"
            );
        }
    }
}
