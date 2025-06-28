<?php

namespace Tests\Unit\Services;

use App\Models\Branch;
use App\Services\Calcom\CalcomAvailabilityService;
use App\Services\Calcom\CalcomV2Service;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CalcomAvailabilityServiceTest extends TestCase
{
    protected CalcomAvailabilityService $service;
    protected $mockCalcomService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Clear cache
        Cache::flush();
        
        // Mock CalcomV2Service
        $this->mockCalcomService = Mockery::mock(CalcomV2Service::class);
        $this->service = new CalcomAvailabilityService($this->mockCalcomService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]

    public function test_check_availability_returns_available_slots()
    {
        $eventTypeId = 123456;
        $date = '2025-06-20';
        
        // Mock Cal.com response
        $this->mockCalcomService
            ->shouldReceive('checkAvailability')
            ->once()
            ->with($eventTypeId, $date, 'Europe/Berlin')
            ->andReturn([
                'success' => true,
                'data' => [
                    'slots' => [
                        '2025-06-20T09:00:00+02:00',
                        '2025-06-20T10:00:00+02:00',
                        '2025-06-20T14:30:00+02:00',
                        '2025-06-20T15:00:00+02:00',
                    ]
                ]
            ]);
        
        $result = $this->service->checkAvailability($eventTypeId, $date);
        
        $this->assertTrue($result['success']);
        $this->assertTrue($result['available']);
        $this->assertCount(4, $result['slots']);
        $this->assertEquals('09:00', $result['slots'][0]['time']);
        $this->assertEquals('15:00', $result['slots'][3]['time']);
        $this->assertEquals(4, $result['total_slots']);
    }

    #[Test]

    public function test_check_availability_with_no_slots()
    {
        $eventTypeId = 123456;
        $date = '2025-06-21';
        
        $this->mockCalcomService
            ->shouldReceive('checkAvailability')
            ->once()
            ->andReturn([
                'success' => true,
                'data' => [
                    'slots' => []
                ]
            ]);
        
        $result = $this->service->checkAvailability($eventTypeId, $date);
        
        $this->assertTrue($result['success']);
        $this->assertFalse($result['available']);
        $this->assertCount(0, $result['slots']);
    }

    #[Test]

    public function test_check_availability_with_api_error()
    {
        $eventTypeId = 123456;
        $date = '2025-06-22';
        
        $this->mockCalcomService
            ->shouldReceive('checkAvailability')
            ->once()
            ->andReturn([
                'success' => false,
                'message' => 'API Error'
            ]);
        
        $result = $this->service->checkAvailability($eventTypeId, $date);
        
        $this->assertFalse($result['success']);
        $this->assertFalse($result['available']);
        $this->assertCount(0, $result['slots']);
        $this->assertEquals('API Error', $result['error']);
    }

    #[Test]

    public function test_check_availability_uses_cache()
    {
        $eventTypeId = 123456;
        $date = '2025-06-23';
        
        // First call - should hit API
        $this->mockCalcomService
            ->shouldReceive('checkAvailability')
            ->once()
            ->andReturn([
                'success' => true,
                'data' => [
                    'slots' => ['2025-06-23T10:00:00+02:00']
                ]
            ]);
        
        $result1 = $this->service->checkAvailability($eventTypeId, $date);
        
        // Second call - should use cache, not hit API
        $result2 = $this->service->checkAvailability($eventTypeId, $date);
        
        $this->assertEquals($result1, $result2);
    }

    #[Test]

    public function test_is_time_slot_available()
    {
        $eventTypeId = 123456;
        $date = '2025-06-24';
        
        $this->mockCalcomService
            ->shouldReceive('checkAvailability')
            ->once()
            ->andReturn([
                'success' => true,
                'data' => [
                    'slots' => [
                        '2025-06-24T09:00:00+02:00',
                        '2025-06-24T10:00:00+02:00',
                        '2025-06-24T14:30:00+02:00',
                    ]
                ]
            ]);
        
        $this->assertTrue($this->service->isTimeSlotAvailable($eventTypeId, $date, '10:00'));
        $this->assertTrue($this->service->isTimeSlotAvailable($eventTypeId, $date, '14:30'));
        $this->assertFalse($this->service->isTimeSlotAvailable($eventTypeId, $date, '11:00'));
        $this->assertFalse($this->service->isTimeSlotAvailable($eventTypeId, $date, '15:00'));
    }

    #[Test]

    public function test_find_next_available_slot()
    {
        $eventTypeId = 123456;
        
        // Mock no availability for first 2 days, then available
        $this->mockCalcomService
            ->shouldReceive('checkAvailability')
            ->times(3)
            ->andReturn(
                ['success' => true, 'data' => ['slots' => []]],
                ['success' => true, 'data' => ['slots' => []]],
                [
                    'success' => true,
                    'data' => [
                        'slots' => ['2025-06-25T11:00:00+02:00']
                    ]
                ]
            );
        
        $result = $this->service->findNextAvailableSlot($eventTypeId);
        
        $this->assertTrue($result['found']);
        $this->assertEquals(2, $result['days_ahead']);
        $this->assertEquals('11:00', $result['slot']['time']);
    }

    #[Test]

    public function test_find_next_available_slot_with_time_preferences()
    {
        $eventTypeId = 123456;
        
        $this->mockCalcomService
            ->shouldReceive('checkAvailability')
            ->once()
            ->andReturn([
                'success' => true,
                'data' => [
                    'slots' => [
                        '2025-06-20T09:00:00+02:00',
                        '2025-06-20T10:00:00+02:00',
                        '2025-06-20T14:00:00+02:00',
                        '2025-06-20T15:00:00+02:00',
                        '2025-06-20T16:00:00+02:00',
                    ]
                ]
            ]);
        
        $result = $this->service->findNextAvailableSlot($eventTypeId, [
            'min_time' => '14:00',
            'max_time' => '16:00'
        ]);
        
        $this->assertTrue($result['found']);
        $this->assertEquals('14:00', $result['slot']['time']);
    }

    #[Test]

    public function test_find_alternative_slots()
    {
        $eventTypeId = 123456;
        $requestedDate = '2025-06-26';
        $requestedTime = '15:00';
        
        // Mock same day availability
        $this->mockCalcomService
            ->shouldReceive('checkAvailability')
            ->with($eventTypeId, $requestedDate, 'Europe/Berlin')
            ->once()
            ->andReturn([
                'success' => true,
                'data' => [
                    'slots' => [
                        '2025-06-26T14:00:00+02:00',
                        '2025-06-26T14:30:00+02:00',
                        '2025-06-26T16:00:00+02:00',
                        '2025-06-26T16:30:00+02:00',
                    ]
                ]
            ]);
        
        // Mock next day same time
        $this->mockCalcomService
            ->shouldReceive('checkAvailability')
            ->with($eventTypeId, '2025-06-27', 'Europe/Berlin')
            ->once()
            ->andReturn([
                'success' => true,
                'data' => [
                    'slots' => ['2025-06-27T15:00:00+02:00']
                ]
            ]);
        
        $result = $this->service->findAlternativeSlots(
            $eventTypeId,
            $requestedDate,
            $requestedTime,
            ['max_alternatives' => 3]
        );
        
        $this->assertFalse($result['requested']['available']);
        $this->assertGreaterThan(0, $result['total_alternatives']);
        
        // Should include nearby times on same day
        $sameDayAlts = array_filter($result['alternatives'], fn($alt) => 
            $alt['type'] === 'same_day_different_time'
        );
        $this->assertNotEmpty($sameDayAlts);
        
        // Should include same time on different day
        $diffDayAlts = array_filter($result['alternatives'], fn($alt) => 
            $alt['type'] === 'different_day_same_time'
        );
        $this->assertNotEmpty($diffDayAlts);
    }

    #[Test]

    public function test_check_multiple_dates_availability()
    {
        $eventTypeId = 123456;
        $dates = ['2025-06-20', '2025-06-21', '2025-06-22'];
        
        $this->mockCalcomService
            ->shouldReceive('checkAvailability')
            ->times(3)
            ->andReturn(
                ['success' => true, 'data' => ['slots' => ['slot1', 'slot2']]],
                ['success' => true, 'data' => ['slots' => []]],
                ['success' => true, 'data' => ['slots' => ['slot3']]]
            );
        
        $result = $this->service->checkMultipleDatesAvailability($eventTypeId, $dates);
        
        $this->assertTrue($result['success']);
        $this->assertEquals(3, $result['summary']['total_dates']);
        $this->assertEquals(2, $result['summary']['available_dates']);
        $this->assertEquals(3, $result['summary']['total_slots']);
    }

    #[Test]

    public function test_get_branch_availability_summary()
    {
        $branch = new Branch([
            'id' => 1,
            'name' => 'Test Branch',
            'calcom_event_type_id' => 123456
        ]);
        
        // Mock multiple date checks
        $this->mockCalcomService
            ->shouldReceive('checkAvailability')
            ->times(7)
            ->andReturn(
                ['success' => true, 'data' => ['slots' => array_fill(0, 10, 'slot')]],
                ['success' => true, 'data' => ['slots' => array_fill(0, 5, 'slot')]],
                ['success' => true, 'data' => ['slots' => array_fill(0, 8, 'slot')]],
                ['success' => true, 'data' => ['slots' => []]],
                ['success' => true, 'data' => ['slots' => array_fill(0, 12, 'slot')]],
                ['success' => true, 'data' => ['slots' => array_fill(0, 6, 'slot')]],
                ['success' => true, 'data' => ['slots' => array_fill(0, 9, 'slot')]]
            );
        
        $result = $this->service->getBranchAvailabilitySummary($branch, 7);
        
        $this->assertTrue($result['success']);
        $this->assertEquals(7, $result['summary']['period']['days']);
        $this->assertEquals(6, $result['summary']['availability']['available_days']);
        $this->assertEquals(50, $result['summary']['availability']['total_slots']);
        $this->assertNotNull($result['summary']['busiest_day']);
        $this->assertNotNull($result['summary']['quietest_day']);
    }

    #[Test]

    public function test_slots_are_filtered_by_business_hours()
    {
        $eventTypeId = 123456;
        $date = '2025-06-25';
        
        $this->mockCalcomService
            ->shouldReceive('checkAvailability')
            ->once()
            ->andReturn([
                'success' => true,
                'data' => [
                    'slots' => [
                        '2025-06-25T07:00:00+02:00', // Too early
                        '2025-06-25T09:00:00+02:00', // OK
                        '2025-06-25T12:00:00+02:00', // OK
                        '2025-06-25T17:00:00+02:00', // OK
                        '2025-06-25T19:00:00+02:00', // Too late
                    ]
                ]
            ]);
        
        $result = $this->service->checkAvailability($eventTypeId, $date, [
            'business_hours' => [
                'start' => 8,
                'end' => 18
            ]
        ]);
        
        $this->assertCount(3, $result['slots']);
        $this->assertEquals('09:00', $result['slots'][0]['time']);
        $this->assertEquals('17:00', $result['slots'][2]['time']);
    }
}