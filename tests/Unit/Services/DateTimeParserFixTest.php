<?php

namespace Tests\Unit\Services;

use App\Services\Retell\DateTimeParser;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

/**
 * Test for DateTimeParser Fix: "nächster Dienstag" bug
 *
 * Bug: Was incorrectly calculating "nächster Dienstag" as 28. Oktober instead of 21. Oktober
 * Root Cause: Faulty logic in parseRelativeWeekday() that added 1 week if result < 7 days
 * Fix: Removed the faulty condition, now uses calendar occurrence semantics
 *
 * Test Date: 2025-10-18 (Saturday)
 */
class DateTimeParserFixTest extends TestCase
{
    private DateTimeParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new DateTimeParser();
        DateTimeParser::clearTimeCache();
    }

    /**
     * Test Case 1: Saturday → "nächster Dienstag" should be 21. Oktober
     *
     * This is the EXACT scenario from the failing test call
     * User said: "nächste Woche Dienstag" on Saturday 18. October
     * Expected: 21. October (Tuesday, 3 days away)
     * Old Bug: 28. October (Tuesday after next, 10 days away)
     */
    public function test_saturday_to_next_tuesday_returns_tuesday_of_next_week()
    {
        // Freeze time to Saturday, 18. October 2025
        Carbon::setTestNow('2025-10-18 10:00:00');

        // Parse "nächster Dienstag"
        $result = $this->parser->parseRelativeWeekday('dienstag', 'nächster');

        // Expected: 21. Oktober (Tuesday of next week)
        $this->assertEquals('2025-10-21', $result->format('Y-m-d'));
        $this->assertEquals('Tuesday', $result->format('l'));
        $this->assertEquals(3, $result->diffInDays(Carbon::now()));
    }

    /**
     * Test Case 2: Monday → "nächster Dienstag" should be 21. Oktober (tomorrow)
     */
    public function test_monday_to_next_tuesday_returns_tomorrow()
    {
        Carbon::setTestNow('2025-10-20 10:00:00'); // Monday

        $result = $this->parser->parseRelativeWeekday('dienstag', 'nächster');

        // Expected: 21. Oktober (Tomorrow, Tuesday)
        $this->assertEquals('2025-10-21', $result->format('Y-m-d'));
        $this->assertEquals(1, $result->diffInDays(Carbon::now()));
    }

    /**
     * Test Case 3: Tuesday → "nächster Dienstag" should be 28. Oktober (next week)
     */
    public function test_tuesday_to_next_tuesday_returns_next_week()
    {
        Carbon::setTestNow('2025-10-21 10:00:00'); // Tuesday

        $result = $this->parser->parseRelativeWeekday('dienstag', 'nächster');

        // Expected: 28. Oktober (Next Tuesday, one week away)
        $this->assertEquals('2025-10-28', $result->format('Y-m-d'));
        $this->assertEquals(7, $result->diffInDays(Carbon::now()));
    }

    /**
     * Test Case 4: Friday → "nächster Samstag" should be 18. Oktober (tomorrow)
     */
    public function test_friday_to_next_saturday_returns_tomorrow()
    {
        Carbon::setTestNow('2025-10-17 10:00:00'); // Friday

        $result = $this->parser->parseRelativeWeekday('samstag', 'nächster');

        // Expected: 18. Oktober (Tomorrow, Saturday)
        $this->assertEquals('2025-10-18', $result->format('Y-m-d'));
        $this->assertEquals(1, $result->diffInDays(Carbon::now()));
    }

    /**
     * Test Case 5: Saturday → "nächster Sonntag" should be 19. Oktober (tomorrow)
     */
    public function test_saturday_to_next_sunday_returns_tomorrow()
    {
        Carbon::setTestNow('2025-10-18 10:00:00'); // Saturday

        $result = $this->parser->parseRelativeWeekday('sonntag', 'nächster');

        // Expected: 19. Oktober (Tomorrow, Sunday)
        $this->assertEquals('2025-10-19', $result->format('Y-m-d'));
        $this->assertEquals(1, $result->diffInDays(Carbon::now()));
    }

    /**
     * Test Case 6: Sunday → "nächster Montag" should be 20. Oktober (next day)
     */
    public function test_sunday_to_next_monday_returns_next_day()
    {
        Carbon::setTestNow('2025-10-19 10:00:00'); // Sunday

        $result = $this->parser->parseRelativeWeekday('montag', 'nächster');

        // Expected: 20. Oktober (Next day, Monday)
        $this->assertEquals('2025-10-20', $result->format('Y-m-d'));
        $this->assertEquals(1, $result->diffInDays(Carbon::now()));
    }

    /**
     * Test Case 7: Verify "dieser" modifier still works correctly
     * "dieser Dienstag" from Saturday should be next Tuesday (this week semantically)
     */
    public function test_dieser_modifier_still_works()
    {
        Carbon::setTestNow('2025-10-18 10:00:00'); // Saturday

        $result = $this->parser->parseRelativeWeekday('dienstag', 'dieser');

        // Expected: 21. Oktober (Next Tuesday, since Tuesday of this week already passed)
        $this->assertEquals('2025-10-21', $result->format('Y-m-d'));
    }

    /**
     * Regression Test: Verify the bug doesn't return 28. Oktober anymore
     */
    public function test_regression_saturday_tuesday_is_not_28_oktober()
    {
        Carbon::setTestNow('2025-10-18 10:00:00'); // Saturday

        $result = $this->parser->parseRelativeWeekday('dienstag', 'nächster');

        // The buggy result was 28. Oktober - make sure we DON'T return that
        $this->assertNotEquals('2025-10-28', $result->format('Y-m-d'),
            'Bug regression: Should NOT return 28. Oktober for "nächster Dienstag" on Saturday'
        );

        // Should return 21. Oktober instead
        $this->assertEquals('2025-10-21', $result->format('Y-m-d'));
    }
}
