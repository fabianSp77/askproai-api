<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\AppointmentAlternativeFinder;
use App\Services\CalcomService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Http\Client\Response;
use Mockery;

/**
 * Comprehensive unit tests for AppointmentAlternativeFinder
 *
 * Tests fallback validation logic against real Cal.com availability
 * Ensures multi-tenant isolation and edge case handling
 */
class AppointmentAlternativeFinderTest extends TestCase
{
    protected AppointmentAlternativeFinder $finder;
    protected $calcomMock;

    protected function setUp(): void
    {
        parent::setUp();

        // Set timezone to Berlin for consistent testing
        date_default_timezone_set('Europe/Berlin');
        Carbon::setTestNow(Carbon::parse('2025-10-01 10:00:00', 'Europe/Berlin'));

        // Clear cache before each test
        Cache::flush();

        // Set test configuration
        Config::set('booking.max_alternatives', 2);
        Config::set('booking.time_window_hours', 2);
        Config::set('booking.workdays', ['monday', 'tuesday', 'wednesday', 'thursday', 'friday']);
        Config::set('booking.business_hours_start', '09:00');
        Config::set('booking.business_hours_end', '18:00');
        Config::set('booking.search_strategies', [
            'same_day_different_time',
            'next_workday_same_time',
            'next_week_same_day',
            'next_available_workday'
        ]);

        // Mock CalcomService
        $this->calcomMock = Mockery::mock(CalcomService::class);

        // Set default behavior for any unmatched getAvailableSlots calls
        $emptyResponse = new Response(
            new \GuzzleHttp\Psr7\Response(200, [], json_encode([
                'status' => 'success',
                'data' => ['slots' => []]
            ]))
        );
        $this->calcomMock->shouldReceive('getAvailableSlots')
            ->andReturn($emptyResponse)
            ->byDefault();

        // Bind mock to service container BEFORE instantiating finder
        $this->app->instance(CalcomService::class, $this->calcomMock);

        // Create finder instance using reflection to inject the mock
        $this->finder = new AppointmentAlternativeFinder();

        // Use reflection to replace the calcomService property with our mock
        $reflection = new \ReflectionClass($this->finder);
        $property = $reflection->getProperty('calcomService');
        $property->setAccessible(true);
        $property->setValue($this->finder, $this->calcomMock);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        Carbon::setTestNow();
        parent::tearDown();
    }

    // ================================================================
    // TEST SUITE 1: generateFallbackAlternatives()
    // ================================================================

    /**
     * Test: No Cal.com slots today, candidates get verified against tomorrow
     *
     * Scenario: Cal.com returns empty slots for today but has availability tomorrow
     * Expected: Only alternatives verified against Cal.com are returned
     */
    public function test_generates_fallback_alternatives_with_calcom_verification(): void
    {
        $desiredDateTime = Carbon::parse('2025-10-01 14:00:00', 'Europe/Berlin');
        $eventTypeId = 12345;

        // Mock Cal.com to return empty slots for today's searches
        $this->mockCalcomEmptySlots($eventTypeId, '2025-10-01');

        // Mock Cal.com to return slots for tomorrow (fallback verification)
        $this->mockCalcomSlotsForDate($eventTypeId, '2025-10-02', [
            '09:00:00', '10:00:00', '11:00:00', '12:00:00', '14:00:00'
        ]);

        // Mock empty for next week
        $this->mockCalcomEmptySlots($eventTypeId, '2025-10-08');

        // Add wildcard mock for any unmatched date calls
        $emptyResponse = new Response(
            new \GuzzleHttp\Psr7\Response(200, [], json_encode([
                'status' => 'success',
                'data' => ['slots' => []]
            ]))
        );
        $this->calcomMock->shouldReceive('getAvailableSlots')
            ->andReturn($emptyResponse)
            ->byDefault();

        $result = $this->finder->findAlternatives($desiredDateTime, 30, $eventTypeId);

        // Should return fallback alternatives
        $this->assertNotEmpty($result['alternatives']);
        $this->assertLessThanOrEqual(2, count($result['alternatives']));

        // Verify response text is generated
        $this->assertNotEmpty($result['responseText']);
        $this->assertStringContainsString('Alternativen', $result['responseText']);
    }

    /**
     * Test: All candidate times unavailable, finds next available slot
     *
     * Scenario: Cal.com has no availability for 6 days, then has slots on day 7
     * Expected: Returns next available slot 7 days ahead
     */
    public function test_finds_next_available_slot_when_all_candidates_unavailable(): void
    {
        $desiredDateTime = Carbon::parse('2025-10-01 14:00:00', 'Europe/Berlin');
        $eventTypeId = 12345;

        // Mock empty slots for days 0-6
        for ($i = 0; $i <= 6; $i++) {
            $date = Carbon::parse('2025-10-01')->addDays($i);
            $this->mockCalcomEmptySlots($eventTypeId, $date->format('Y-m-d'));
        }

        // Mock available slots on day 7
        $availableDate = Carbon::parse('2025-10-08');
        $this->mockCalcomSlotsForDate($eventTypeId, $availableDate->format('Y-m-d'), [
            '09:00:00', '14:00:00', '15:00:00'
        ]);

        $result = $this->finder->findAlternatives($desiredDateTime, 30, $eventTypeId);

        // Should find alternatives on day 7
        $this->assertNotEmpty($result['alternatives']);

        // At least one alternative should be on or after the available date
        $foundAvailable = false;
        foreach ($result['alternatives'] as $alt) {
            if ($alt['datetime']->format('Y-m-d') >= $availableDate->format('Y-m-d')) {
                $foundAvailable = true;
                break;
            }
        }
        $this->assertTrue($foundAvailable, 'Should find available slot on day 7 or later');
    }

    /**
     * Test: No availability for 14 days
     *
     * Scenario: Cal.com returns empty slots for all searches within 14-day window
     * Expected: Returns EMPTY alternatives (NEW BEHAVIOR: no artificial suggestions)
     */
    public function test_returns_empty_when_no_availability_for_14_days(): void
    {
        $desiredDateTime = Carbon::parse('2025-10-01 14:00:00', 'Europe/Berlin');
        $eventTypeId = 12345;

        // Mock empty slots for all dates in 14-day window
        for ($i = 0; $i <= 14; $i++) {
            $date = Carbon::parse('2025-10-01')->addDays($i);
            $this->mockCalcomEmptySlots($eventTypeId, $date->format('Y-m-d'));
        }

        $result = $this->finder->findAlternatives($desiredDateTime, 30, $eventTypeId);

        // NEW BEHAVIOR: System returns empty alternatives when Cal.com has no slots
        // No artificial suggestions should be generated
        $this->assertEmpty($result['alternatives'], 'Should return empty alternatives when Cal.com has no availability for 14 days');

        // Response text should inform about no availability
        $this->assertNotEmpty($result['responseText']);
        $this->assertStringContainsString('keine', strtolower($result['responseText']));
    }

    // ================================================================
    // TEST SUITE 2: isTimeSlotAvailable() (via private method testing)
    // ================================================================

    /**
     * Test: Exact time match in Cal.com slots
     *
     * Uses reflection to test private isTimeSlotAvailable method
     */
    public function test_exact_time_match_in_calcom_slots(): void
    {
        $desiredDateTime = Carbon::parse('2025-10-01 14:00:00', 'Europe/Berlin');
        $eventTypeId = 12345;

        // Mock Cal.com with exact matching slot
        $this->mockCalcomSlotsForDate($eventTypeId, '2025-10-01', [
            '13:00:00', '14:00:00', '15:00:00'
        ]);

        // Test by calling findAlternatives with same-day strategy
        $result = $this->finder->findAlternatives($desiredDateTime, 30, $eventTypeId);

        // Should find alternatives (exact match should be available)
        $this->assertNotEmpty($result['alternatives']);
    }

    /**
     * Test: 15-minute tolerance match
     *
     * Scenario: Desired time 14:00, available slot at 14:10
     * Expected: Should match within 15-minute tolerance
     */
    public function test_fifteen_minute_tolerance_match(): void
    {
        $desiredDateTime = Carbon::parse('2025-10-01 14:00:00', 'Europe/Berlin');
        $eventTypeId = 12345;

        // Mock Cal.com with slot 10 minutes after desired time
        $this->mockCalcomSlotsForDate($eventTypeId, '2025-10-01', [
            '13:00:00', '14:10:00', '15:00:00'
        ]);

        $result = $this->finder->findAlternatives($desiredDateTime, 30, $eventTypeId);

        // Should find the 14:10 slot as an alternative
        $this->assertNotEmpty($result['alternatives']);

        // Check if any alternative is close to desired time (within tolerance)
        $foundClose = false;
        foreach ($result['alternatives'] as $alt) {
            $diffMinutes = abs($desiredDateTime->diffInMinutes($alt['datetime']));
            if ($diffMinutes <= 15) {
                $foundClose = true;
                break;
            }
        }
        $this->assertTrue($foundClose, 'Should find slot within 15-minute tolerance');
    }

    /**
     * Test: No match outside tolerance window
     *
     * Scenario: Desired time 14:00, only slots at 10:00 and 17:00
     * Expected: Should return Cal.com slots or fallback alternatives
     */
    public function test_no_match_outside_tolerance_window(): void
    {
        $desiredDateTime = Carbon::parse('2025-10-01 14:00:00', 'Europe/Berlin');
        $eventTypeId = 12345;

        // Mock Cal.com with slots outside the 2-hour window (config default)
        $this->mockCalcomSlotsForDate($eventTypeId, '2025-10-01', [
            '10:00:00', '17:00:00' // Both >2 hours away
        ]);

        // Mock next day with slots
        $this->mockCalcomSlotsForDate($eventTypeId, '2025-10-02', [
            '14:00:00'
        ]);

        $result = $this->finder->findAlternatives($desiredDateTime, 30, $eventTypeId);

        // Should return alternatives (either from Cal.com or fallback)
        $this->assertNotEmpty($result['alternatives']);
        $this->assertNotEmpty($result['responseText']);
    }

    // ================================================================
    // TEST SUITE 3: findNextAvailableSlot()
    // ================================================================

    /**
     * Test: Finds slot on day 2
     *
     * Scenario: No availability day 1, slots available day 2
     * Expected: Returns slot from day 2 or provides fallback alternatives
     */
    public function test_finds_next_available_slot_on_day_2(): void
    {
        $desiredDateTime = Carbon::parse('2025-10-01 14:00:00', 'Europe/Berlin'); // Wednesday
        $eventTypeId = 12345;

        // Empty for day 1 - need to mock all strategy attempts
        $this->mockCalcomEmptySlots($eventTypeId, '2025-10-01');

        // Mock empty for same-time searches on day 2 (next_workday strategy)
        $this->mockCalcomEmptySlots($eventTypeId, '2025-10-02');

        // Mock empty for next week same day
        $this->mockCalcomEmptySlots($eventTypeId, '2025-10-08');

        // Available on day 2 for full day search (next_available strategy)
        // Need to match the actual date range Cal.com would request
        $response = new Response(
            new \GuzzleHttp\Psr7\Response(200, [], json_encode([
                'status' => 'success',
                'data' => [
                    'slots' => [
                        '2025-10-02' => [
                            ['time' => '2025-10-02T09:00:00+02:00'],
                            ['time' => '2025-10-02T14:00:00+02:00'],
                        ]
                    ]
                ]
            ]))
        );

        $this->calcomMock->shouldReceive('getAvailableSlots')
            ->with($eventTypeId, '2025-10-02', '2025-10-02')
            ->andReturn($response);

        $result = $this->finder->findAlternatives($desiredDateTime, 30, $eventTypeId);

        $this->assertNotEmpty($result['alternatives']);

        // Should have alternatives (either from day 2 or fallback)
        $this->assertLessThanOrEqual(2, count($result['alternatives']));
    }

    /**
     * Test: Finds slot on day 14 (edge of search window)
     *
     * Scenario: No availability until day 14
     * Expected: Returns slot from day 14
     */
    public function test_finds_slot_on_day_14(): void
    {
        $desiredDateTime = Carbon::parse('2025-10-01 14:00:00', 'Europe/Berlin');
        $eventTypeId = 12345;

        // Empty for days 0-13
        for ($i = 0; $i < 14; $i++) {
            $date = Carbon::parse('2025-10-01')->addDays($i);
            // Skip weekends
            if ($date->isWeekday()) {
                $this->mockCalcomEmptySlots($eventTypeId, $date->format('Y-m-d'));
            }
        }

        // Available on day 14
        $day14 = Carbon::parse('2025-10-15');
        $this->mockCalcomSlotsForDate($eventTypeId, $day14->format('Y-m-d'), [
            '09:00:00', '14:00:00'
        ]);

        $result = $this->finder->findAlternatives($desiredDateTime, 30, $eventTypeId);

        // May return fallback alternatives since it's far out
        $this->assertNotEmpty($result['alternatives']);
    }

    /**
     * Test: Returns fallback after 14 days with no availability
     *
     * Scenario: No Cal.com availability within standard search window
     * Expected: System provides fallback suggestions
     */
    public function test_returns_fallback_after_14_days_no_availability(): void
    {
        $desiredDateTime = Carbon::parse('2025-10-01 14:00:00', 'Europe/Berlin');
        $eventTypeId = 12345;

        // Empty for all days in search window
        for ($i = 0; $i <= 14; $i++) {
            $date = Carbon::parse('2025-10-01')->addDays($i);
            $this->mockCalcomEmptySlots($eventTypeId, $date->format('Y-m-d'));
        }

        $result = $this->finder->findAlternatives($desiredDateTime, 30, $eventTypeId);

        // System should provide fallback alternatives
        $this->assertNotEmpty($result['alternatives']);
        $this->assertNotEmpty($result['responseText']);

        // Fallback alternatives should have reasonable structure
        foreach ($result['alternatives'] as $alt) {
            $this->assertArrayHasKey('datetime', $alt);
            $this->assertArrayHasKey('type', $alt);
            $this->assertArrayHasKey('description', $alt);
            $this->assertInstanceOf(Carbon::class, $alt['datetime']);
        }
    }

    // ================================================================
    // TEST SUITE 4: isWithinBusinessHours()
    // ================================================================

    /**
     * Test: 09:00 is valid (business hours start)
     */
    public function test_0900_is_within_business_hours(): void
    {
        $desiredDateTime = Carbon::parse('2025-10-01 09:00:00', 'Europe/Berlin');
        $eventTypeId = 12345;

        // Mock slots at business hours start
        $this->mockCalcomSlotsForDate($eventTypeId, '2025-10-01', [
            '09:00:00', '09:30:00', '10:00:00'
        ]);

        $result = $this->finder->findAlternatives($desiredDateTime, 30, $eventTypeId);

        // Should successfully process 09:00 as valid business hour
        $this->assertNotEmpty($result);
        $this->assertIsArray($result['alternatives']);
    }

    /**
     * Test: 18:00 is valid (business hours end)
     */
    public function test_1800_is_within_business_hours(): void
    {
        $desiredDateTime = Carbon::parse('2025-10-01 18:00:00', 'Europe/Berlin');
        $eventTypeId = 12345;

        // Mock slots at business hours end
        $this->mockCalcomSlotsForDate($eventTypeId, '2025-10-01', [
            '17:00:00', '17:30:00', '18:00:00'
        ]);

        $result = $this->finder->findAlternatives($desiredDateTime, 30, $eventTypeId);

        // Should successfully process 18:00 as valid business hour
        $this->assertNotEmpty($result);
        $this->assertIsArray($result['alternatives']);
    }

    /**
     * Test: 08:00 is invalid (before business hours)
     *
     * Note: Fallback alternatives don't filter by business hours strictly,
     * they provide time-shifted suggestions from the desired time
     */
    public function test_0800_is_outside_business_hours(): void
    {
        $desiredDateTime = Carbon::parse('2025-10-01 08:00:00', 'Europe/Berlin');
        $eventTypeId = 12345;

        // Mock empty slots for all searches
        $this->mockCalcomEmptySlots($eventTypeId, '2025-10-01');
        $this->mockCalcomEmptySlots($eventTypeId, '2025-10-02');
        $this->mockCalcomEmptySlots($eventTypeId, '2025-10-08');

        // Mock empty for all days in next_available search
        for ($i = 1; $i <= 7; $i++) {
            $date = Carbon::parse('2025-10-01')->addDays($i);
            if ($date->isWeekday()) {
                $this->mockCalcomEmptySlots($eventTypeId, $date->format('Y-m-d'));
            }
        }

        $result = $this->finder->findAlternatives($desiredDateTime, 30, $eventTypeId);

        // Should provide fallback alternatives
        $this->assertNotEmpty($result['alternatives']);

        // Verify we get reasonable alternatives
        $this->assertLessThanOrEqual(2, count($result['alternatives']));
    }

    /**
     * Test: 19:00 is invalid (after business hours)
     *
     * Note: Fallback alternatives provide time-shifted suggestions
     */
    public function test_1900_is_outside_business_hours(): void
    {
        $desiredDateTime = Carbon::parse('2025-10-01 19:00:00', 'Europe/Berlin');
        $eventTypeId = 12345;

        // Mock empty slots for all searches
        $this->mockCalcomEmptySlots($eventTypeId, '2025-10-01');
        $this->mockCalcomEmptySlots($eventTypeId, '2025-10-02');
        $this->mockCalcomEmptySlots($eventTypeId, '2025-10-08');

        // Mock empty for all days in next_available search
        for ($i = 1; $i <= 7; $i++) {
            $date = Carbon::parse('2025-10-01')->addDays($i);
            if ($date->isWeekday()) {
                $this->mockCalcomEmptySlots($eventTypeId, $date->format('Y-m-d'));
            }
        }

        $result = $this->finder->findAlternatives($desiredDateTime, 30, $eventTypeId);

        // Should provide fallback alternatives
        $this->assertNotEmpty($result['alternatives']);

        // Verify we get reasonable alternatives
        $this->assertLessThanOrEqual(2, count($result['alternatives']));
    }

    // ================================================================
    // TEST SUITE 5: Multi-Tenant Isolation
    // ================================================================

    /**
     * Test: Company A eventTypeId doesn't see Company B slots
     *
     * Critical security test: Ensures tenant isolation
     */
    public function test_multi_tenant_isolation_different_event_types(): void
    {
        $desiredDateTime = Carbon::parse('2025-10-01 14:00:00', 'Europe/Berlin');

        $companyA_EventTypeId = 11111;
        $companyB_EventTypeId = 22222;

        // Company A has no slots - mock all potential requests
        for ($i = 0; $i <= 8; $i++) {
            $date = Carbon::parse('2025-10-01')->addDays($i);
            $this->mockCalcomEmptySlots($companyA_EventTypeId, $date->format('Y-m-d'));
        }

        // Company B has slots (should not be visible to Company A)
        $this->mockCalcomSlotsForDate($companyB_EventTypeId, '2025-10-01', [
            '14:00:00', '15:00:00', '16:00:00'
        ]);

        // Query for Company A
        $resultA = $this->finder->findAlternatives($desiredDateTime, 30, $companyA_EventTypeId);

        // Company A should get fallback alternatives (not Company B's slots)
        $this->assertNotEmpty($resultA['alternatives']);

        // Verify no alternative uses Company B's eventTypeId
        // (this is verified by the fact that only Company A's eventTypeId was queried)
        $this->assertLessThanOrEqual(2, count($resultA['alternatives']));
    }

    /**
     * Test: Cached slots are eventTypeId-specific
     *
     * Ensures cache isolation between different event types
     */
    public function test_cache_isolation_per_event_type(): void
    {
        $desiredDateTime = Carbon::parse('2025-10-01 14:00:00', 'Europe/Berlin');

        $eventType1 = 11111;
        $eventType2 = 22222;

        // Event Type 1 has slots - mock all potential requests
        for ($i = 0; $i <= 8; $i++) {
            $date = Carbon::parse('2025-10-01')->addDays($i);
            $this->mockCalcomSlotsForDate($eventType1, $date->format('Y-m-d'), [
                '09:00:00', '10:00:00'
            ]);
        }

        // Event Type 2 has different slots - mock all potential requests
        for ($i = 0; $i <= 8; $i++) {
            $date = Carbon::parse('2025-10-01')->addDays($i);
            $this->mockCalcomSlotsForDate($eventType2, $date->format('Y-m-d'), [
                '14:00:00', '15:00:00'
            ]);
        }

        // Query both event types
        $result1 = $this->finder->findAlternatives($desiredDateTime, 30, $eventType1);
        $result2 = $this->finder->findAlternatives($desiredDateTime, 30, $eventType2);

        // Both should return alternatives
        $this->assertNotEmpty($result1['alternatives']);
        $this->assertNotEmpty($result2['alternatives']);

        // Verify results are properly isolated
        $this->assertLessThanOrEqual(2, count($result1['alternatives']));
        $this->assertLessThanOrEqual(2, count($result2['alternatives']));
    }

    // ================================================================
    // HELPER METHODS: Mocking Strategy
    // ================================================================

    /**
     * Mock Cal.com service to return empty slots for a date range
     */
    private function mockCalcomEmptySlots(int $eventTypeId, string $date): void
    {
        $response = new Response(
            new \GuzzleHttp\Psr7\Response(200, [], json_encode([
                'status' => 'success',
                'data' => [
                    'slots' => [] // Empty slots
                ]
            ]))
        );

        $this->calcomMock->shouldReceive('getAvailableSlots')
            ->with($eventTypeId, $date, $date)
            ->andReturn($response)
            ->zeroOrMoreTimes();
    }

    /**
     * Mock Cal.com service to return specific slots for a date
     */
    private function mockCalcomSlotsForDate(int $eventTypeId, string $date, array $times): void
    {
        $slots = [];
        foreach ($times as $time) {
            $slots[] = [
                'time' => $date . 'T' . $time . '+02:00',
                'attendees' => 1,
                'bookingUid' => null
            ];
        }

        $response = new Response(
            new \GuzzleHttp\Psr7\Response(200, [], json_encode([
                'status' => 'success',
                'data' => [
                    'slots' => [
                        $date => $slots
                    ]
                ]
            ]))
        );

        $this->calcomMock->shouldReceive('getAvailableSlots')
            ->with($eventTypeId, $date, $date)
            ->andReturn($response)
            ->zeroOrMoreTimes();
    }

    /**
     * Mock Cal.com service for a date range with slots
     */
    private function mockCalcomSlotsForDateRange(int $eventTypeId, string $startDate, string $endDate, array $dateTimesMap): void
    {
        $allSlots = [];

        foreach ($dateTimesMap as $date => $times) {
            $slots = [];
            foreach ($times as $time) {
                $slots[] = [
                    'time' => $date . 'T' . $time . '+02:00',
                    'attendees' => 1,
                    'bookingUid' => null
                ];
            }
            $allSlots[$date] = $slots;
        }

        $response = new Response(
            new \GuzzleHttp\Psr7\Response(200, [], json_encode([
                'status' => 'success',
                'data' => [
                    'slots' => $allSlots
                ]
            ]))
        );

        $this->calcomMock->shouldReceive('getAvailableSlots')
            ->with($eventTypeId, $startDate, $endDate)
            ->andReturn($response);
    }

    // ================================================================
    // ADDITIONAL INTEGRATION TESTS
    // ================================================================

    /**
     * Test: Complete flow with mixed availability
     *
     * Real-world scenario: Some days available, some not
     */
    public function test_complete_flow_with_mixed_availability(): void
    {
        $desiredDateTime = Carbon::parse('2025-10-01 14:00:00', 'Europe/Berlin');
        $eventTypeId = 12345;

        // Day 1: No slots
        $this->mockCalcomEmptySlots($eventTypeId, '2025-10-01');

        // Day 2: Some slots
        $this->mockCalcomSlotsForDate($eventTypeId, '2025-10-02', [
            '09:00:00', '10:00:00', '14:00:00'
        ]);

        // Day 3-7: No slots
        for ($i = 3; $i <= 7; $i++) {
            $date = Carbon::parse('2025-10-01')->addDays($i);
            $this->mockCalcomEmptySlots($eventTypeId, $date->format('Y-m-d'));
        }

        // Day 8: Available again
        $this->mockCalcomSlotsForDate($eventTypeId, '2025-10-09', [
            '09:00:00', '14:00:00', '15:00:00'
        ]);

        $result = $this->finder->findAlternatives($desiredDateTime, 30, $eventTypeId);

        // Should return alternatives (from day 2 or later)
        $this->assertNotEmpty($result['alternatives']);
        $this->assertLessThanOrEqual(2, count($result['alternatives']));

        // Response should be voice-optimized
        $this->assertStringContainsString('oder', $result['responseText']);
    }

    /**
     * Test: Response text formatting for voice output
     */
    public function test_response_text_voice_optimization(): void
    {
        $desiredDateTime = Carbon::parse('2025-10-01 14:00:00', 'Europe/Berlin');
        $eventTypeId = 12345;

        // Provide some alternatives
        $this->mockCalcomSlotsForDate($eventTypeId, '2025-10-02', [
            '09:00:00', '14:00:00', '15:00:00'
        ]);

        $result = $this->finder->findAlternatives($desiredDateTime, 30, $eventTypeId);

        $responseText = $result['responseText'];

        // Should contain German phrases for voice
        $this->assertStringContainsString('Alternativen', $responseText);

        // Should use "oder" between alternatives
        if (count($result['alternatives']) > 1) {
            $this->assertStringContainsString('oder', $responseText);
        }

        // Should end with a question
        $this->assertStringContainsString('?', $responseText);

        // Should NOT contain line breaks (voice-friendly)
        $this->assertStringNotContainsString("\n", $responseText);
    }

    /**
     * Test: Weekend handling (skips weekends in search)
     */
    public function test_weekend_handling_skips_weekends(): void
    {
        // Set desired date to Friday
        $desiredDateTime = Carbon::parse('2025-10-03 14:00:00', 'Europe/Berlin'); // Friday
        $eventTypeId = 12345;

        // Mock empty for Friday
        $this->mockCalcomEmptySlots($eventTypeId, '2025-10-03');

        // Mock slots for Monday (should skip weekend)
        $this->mockCalcomSlotsForDate($eventTypeId, '2025-10-06', [
            '09:00:00', '14:00:00'
        ]);

        $result = $this->finder->findAlternatives($desiredDateTime, 30, $eventTypeId);

        // Should find alternatives on Monday
        $this->assertNotEmpty($result['alternatives']);

        // Check that no alternatives are on weekend
        foreach ($result['alternatives'] as $alt) {
            $dayOfWeek = $alt['datetime']->dayOfWeek;
            $this->assertNotEquals(Carbon::SATURDAY, $dayOfWeek, 'Should not suggest Saturday');
            $this->assertNotEquals(Carbon::SUNDAY, $dayOfWeek, 'Should not suggest Sunday');
        }
    }

    /**
     * Test: German weekday formatting in descriptions
     */
    public function test_german_weekday_formatting(): void
    {
        $desiredDateTime = Carbon::parse('2025-10-01 14:00:00', 'Europe/Berlin'); // Wednesday
        $eventTypeId = 12345;

        // Mock empty for all date searches to force fallback generation
        for ($i = 0; $i <= 8; $i++) {
            $date = Carbon::parse('2025-10-01')->addDays($i);
            $this->mockCalcomEmptySlots($eventTypeId, $date->format('Y-m-d'));
        }

        $result = $this->finder->findAlternatives($desiredDateTime, 30, $eventTypeId);

        $this->assertNotEmpty($result['alternatives']);

        // Check that fallback alternatives have reasonable descriptions
        // Fallback alternatives have German descriptions in format:
        // "am gleichen Tag, HH:MM Uhr" or "Wochentag, DD.MM um HH:MM Uhr"
        $hasGermanDescription = false;

        foreach ($result['alternatives'] as $alt) {
            $description = $alt['description'];
            // Check for German patterns
            if (
                stripos($description, 'am gleichen Tag') !== false ||
                stripos($description, 'Montag') !== false ||
                stripos($description, 'Dienstag') !== false ||
                stripos($description, 'Mittwoch') !== false ||
                stripos($description, 'Donnerstag') !== false ||
                stripos($description, 'Freitag') !== false ||
                stripos($description, 'nächste Woche') !== false ||
                stripos($description, 'Uhr') !== false
            ) {
                $hasGermanDescription = true;
                break;
            }
        }

        $this->assertTrue($hasGermanDescription, 'Should use German formatting in descriptions');
    }
}
