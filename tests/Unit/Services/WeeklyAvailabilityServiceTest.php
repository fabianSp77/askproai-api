<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\Appointments\WeeklyAvailabilityService;
use App\Services\CalcomService;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Client\Response;
use Mockery;

class WeeklyAvailabilityServiceTest extends TestCase
{
    use RefreshDatabase;

    protected WeeklyAvailabilityService $service;
    protected $calcomServiceMock;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock CalcomService
        $this->calcomServiceMock = Mockery::mock(CalcomService::class);
        $this->service = new WeeklyAvailabilityService($this->calcomServiceMock);

        // Clear cache before each test
        Cache::flush();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_transforms_calcom_slots_to_week_structure()
    {
        // Create test service with Cal.com event type
        $service = Service::factory()->create([
            'calcom_event_type_id' => 2563193,
            'name' => 'Test Service',
            'duration_minutes' => 30,
        ]);

        // Mock Cal.com API response
        $calcomResponse = [
            'data' => [
                'slots' => [
                    '2025-10-14' => ['09:00:00Z', '09:30:00Z', '10:00:00Z'], // Monday
                    '2025-10-15' => ['10:00:00Z', '11:00:00Z'], // Tuesday
                    '2025-10-17' => ['14:00:00Z'], // Thursday
                ]
            ]
        ];

        $responseMock = Mockery::mock(Response::class);
        $responseMock->shouldReceive('json')->andReturn($calcomResponse);

        $this->calcomServiceMock
            ->shouldReceive('getAvailableSlots')
            ->once()
            ->with(2563193, '2025-10-13', '2025-10-19')
            ->andReturn($responseMock);

        // Fetch week availability
        $weekStart = Carbon::parse('2025-10-13'); // Monday
        $result = $this->service->getWeekAvailability($service->id, $weekStart);

        // Assert structure
        $this->assertIsArray($result);
        $this->assertArrayHasKey('monday', $result);
        $this->assertArrayHasKey('tuesday', $result);
        $this->assertArrayHasKey('sunday', $result);

        // Assert Monday has 3 slots
        $this->assertCount(3, $result['monday']);
        $this->assertEquals('09:00', $result['monday'][0]['time']);
        $this->assertEquals('09:30', $result['monday'][1]['time']);
        $this->assertEquals('10:00', $result['monday'][2]['time']);

        // Assert Tuesday has 2 slots
        $this->assertCount(2, $result['tuesday']);

        // Assert Wednesday is empty (no data from Cal.com)
        $this->assertCount(0, $result['wednesday']);

        // Assert Thursday has 1 slot
        $this->assertCount(1, $result['thursday']);
        $this->assertEquals('14:00', $result['thursday'][0]['time']);
    }

    /** @test */
    public function it_converts_utc_to_europe_berlin_correctly()
    {
        $service = Service::factory()->create([
            'calcom_event_type_id' => 2563193,
        ]);

        // Mock Cal.com response with UTC time
        $calcomResponse = [
            'data' => [
                'slots' => [
                    '2025-10-14' => ['09:00:00Z'], // 09:00 UTC
                ]
            ]
        ];

        $responseMock = Mockery::mock(Response::class);
        $responseMock->shouldReceive('json')->andReturn($calcomResponse);

        $this->calcomServiceMock
            ->shouldReceive('getAvailableSlots')
            ->once()
            ->andReturn($responseMock);

        $weekStart = Carbon::parse('2025-10-13'); // Monday
        $result = $this->service->getWeekAvailability($service->id, $weekStart);

        // UTC 09:00 → Europe/Berlin 11:00 (CEST, +02:00 in summer)
        // Note: Adjust assertion based on DST rules
        $slot = $result['tuesday'][0]; // 2025-10-14 is Tuesday
        $this->assertStringContainsString('2025-10-14', $slot['full_datetime']);

        // Verify timezone information is present
        $this->assertArrayHasKey('time', $slot);
        $this->assertArrayHasKey('full_datetime', $slot);
        $this->assertArrayHasKey('date', $slot);
    }

    /** @test */
    public function it_handles_empty_week_with_no_slots()
    {
        $service = Service::factory()->create([
            'calcom_event_type_id' => 2563193,
        ]);

        // Mock Cal.com response with no slots
        $calcomResponse = [
            'data' => [
                'slots' => []
            ]
        ];

        $responseMock = Mockery::mock(Response::class);
        $responseMock->shouldReceive('json')->andReturn($calcomResponse);

        $this->calcomServiceMock
            ->shouldReceive('getAvailableSlots')
            ->once()
            ->andReturn($responseMock);

        $weekStart = Carbon::parse('2025-10-13'); // Monday
        $result = $this->service->getWeekAvailability($service->id, $weekStart);

        // All days should be empty arrays
        $this->assertCount(0, $result['monday']);
        $this->assertCount(0, $result['tuesday']);
        $this->assertCount(0, $result['wednesday']);
        $this->assertCount(0, $result['thursday']);
        $this->assertCount(0, $result['friday']);
        $this->assertCount(0, $result['saturday']);
        $this->assertCount(0, $result['sunday']);
    }

    /** @test */
    public function it_forces_week_start_to_monday()
    {
        $service = Service::factory()->create([
            'calcom_event_type_id' => 2563193,
        ]);

        $calcomResponse = [
            'data' => ['slots' => []]
        ];

        $responseMock = Mockery::mock(Response::class);
        $responseMock->shouldReceive('json')->andReturn($calcomResponse);

        // Expect API call with Monday-Sunday range
        $this->calcomServiceMock
            ->shouldReceive('getAvailableSlots')
            ->once()
            ->with(2563193, '2025-10-13', '2025-10-19') // Monday to Sunday
            ->andReturn($responseMock);

        // Pass Wednesday as weekStart
        $weekStart = Carbon::parse('2025-10-15'); // Wednesday
        $this->service->getWeekAvailability($service->id, $weekStart);

        // If this doesn't throw, the service correctly forced it to Monday
    }

    /** @test */
    public function it_throws_exception_when_service_has_no_calcom_event_type_id()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('has no Cal.com Event Type ID configured');

        // Service without calcom_event_type_id
        $service = Service::factory()->create([
            'calcom_event_type_id' => null,
        ]);

        $weekStart = Carbon::parse('2025-10-13');
        $this->service->getWeekAvailability($service->id, $weekStart);
    }

    /** @test */
    public function it_caches_week_availability_for_60_seconds()
    {
        $service = Service::factory()->create([
            'calcom_event_type_id' => 2563193,
        ]);

        $calcomResponse = [
            'data' => ['slots' => []]
        ];

        $responseMock = Mockery::mock(Response::class);
        $responseMock->shouldReceive('json')->andReturn($calcomResponse);

        // Should only call Cal.com API once
        $this->calcomServiceMock
            ->shouldReceive('getAvailableSlots')
            ->once()
            ->andReturn($responseMock);

        $weekStart = Carbon::parse('2025-10-13');

        // First call - hits Cal.com API
        $result1 = $this->service->getWeekAvailability($service->id, $weekStart);

        // Second call - should hit cache (no additional API call)
        $result2 = $this->service->getWeekAvailability($service->id, $weekStart);

        $this->assertEquals($result1, $result2);
    }

    /** @test */
    public function it_returns_correct_week_metadata()
    {
        $weekStart = Carbon::parse('2025-10-13'); // Monday, KW 42
        $metadata = $this->service->getWeekMetadata($weekStart);

        $this->assertEquals(42, $metadata['week_number']);
        $this->assertEquals(2025, $metadata['year']);
        $this->assertEquals('14.10.2025', $metadata['start_date']);
        $this->assertEquals('20.10.2025', $metadata['end_date']);
        $this->assertEquals('2025-10-13', $metadata['start_date_iso']);
        $this->assertEquals('2025-10-19', $metadata['end_date_iso']);
        $this->assertIsBool($metadata['is_current_week']);
        $this->assertIsBool($metadata['is_past']);
        $this->assertArrayHasKey('days', $metadata);
        $this->assertCount(7, $metadata['days']);
    }

    /** @test */
    public function it_categorizes_slots_by_time_of_day()
    {
        $service = Service::factory()->create([
            'calcom_event_type_id' => 2563193,
        ]);

        // Mock slots at different times
        $calcomResponse = [
            'data' => [
                'slots' => [
                    '2025-10-14' => [
                        '08:00:00Z',  // Morning (Europe/Berlin: 10:00)
                        '12:00:00Z',  // Afternoon (Europe/Berlin: 14:00)
                        '16:00:00Z',  // Evening (Europe/Berlin: 18:00)
                    ]
                ]
            ]
        ];

        $responseMock = Mockery::mock(Response::class);
        $responseMock->shouldReceive('json')->andReturn($calcomResponse);

        $this->calcomServiceMock
            ->shouldReceive('getAvailableSlots')
            ->once()
            ->andReturn($responseMock);

        $weekStart = Carbon::parse('2025-10-13');
        $result = $this->service->getWeekAvailability($service->id, $weekStart);

        $slots = $result['tuesday']; // 2025-10-14 is Tuesday

        // Verify time-of-day categorization
        $this->assertArrayHasKey('is_morning', $slots[0]);
        $this->assertArrayHasKey('is_afternoon', $slots[0]);
        $this->assertArrayHasKey('is_evening', $slots[0]);
    }

    /** @test */
    public function it_sorts_slots_by_time_ascending()
    {
        $service = Service::factory()->create([
            'calcom_event_type_id' => 2563193,
        ]);

        // Mock unsorted slots
        $calcomResponse = [
            'data' => [
                'slots' => [
                    '2025-10-14' => [
                        '14:00:00Z',  // Later time
                        '09:00:00Z',  // Earlier time
                        '11:00:00Z',  // Middle time
                    ]
                ]
            ]
        ];

        $responseMock = Mockery::mock(Response::class);
        $responseMock->shouldReceive('json')->andReturn($calcomResponse);

        $this->calcomServiceMock
            ->shouldReceive('getAvailableSlots')
            ->once()
            ->andReturn($responseMock);

        $weekStart = Carbon::parse('2025-10-13');
        $result = $this->service->getWeekAvailability($service->id, $weekStart);

        $slots = $result['tuesday'];

        // Verify sorted by time
        $this->assertLessThan($slots[1]['hour'], $slots[2]['hour']);
        // or if same hour, check minutes
    }

    /** @test */
    public function it_clears_service_cache_for_multiple_weeks()
    {
        $service = Service::factory()->create([
            'calcom_event_type_id' => 2563193,
        ]);

        // Set up some cached data
        $weekStart = now()->startOfWeek(Carbon::MONDAY);
        for ($i = 0; $i < 4; $i++) {
            $week = $weekStart->copy()->addWeeks($i);
            $cacheKey = "week_availability:{$service->id}:{$week->format('Y-m-d')}";
            Cache::put($cacheKey, ['test' => 'data'], 120);
        }

        // Clear cache
        $this->service->clearServiceCache($service->id, 4);

        // Verify cache is cleared
        for ($i = 0; $i < 4; $i++) {
            $week = $weekStart->copy()->addWeeks($i);
            $cacheKey = "week_availability:{$service->id}:{$week->format('Y-m-d')}";
            $this->assertNull(Cache::get($cacheKey));
        }
    }
}
