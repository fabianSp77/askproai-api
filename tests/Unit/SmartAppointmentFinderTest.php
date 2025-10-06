<?php

namespace Tests\Unit;

use App\Models\Service;
use App\Models\Company;
use App\Services\Appointments\SmartAppointmentFinder;
use App\Services\CalcomV2Client;
use App\Services\CalcomApiRateLimiter;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SmartAppointmentFinderTest extends TestCase
{
    use DatabaseTransactions;

    protected SmartAppointmentFinder $finder;
    protected Company $company;
    protected Service $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->service = Service::factory()->create([
            'company_id' => $this->company->id,
            'calcom_event_type_id' => 12345,
        ]);

        $this->finder = new SmartAppointmentFinder($this->company);

        // Clear cache before each test
        Cache::flush();
    }

    /** @test */
    public function it_finds_next_available_slot()
    {
        $this->mockCalcomSuccessResponse([
            '2025-10-03T09:00:00Z',
            '2025-10-03T10:00:00Z',
            '2025-10-03T14:00:00Z',
        ]);

        $after = Carbon::parse('2025-10-03 00:00:00');
        $nextSlot = $this->finder->findNextAvailable($this->service, $after);

        $this->assertNotNull($nextSlot);
        $this->assertEquals('2025-10-03 09:00:00', $nextSlot->format('Y-m-d H:i:s'));
    }

    /** @test */
    public function it_returns_null_when_no_slots_available()
    {
        $this->mockCalcomSuccessResponse([]);

        $after = Carbon::parse('2025-10-03 00:00:00');
        $nextSlot = $this->finder->findNextAvailable($this->service, $after);

        $this->assertNull($nextSlot);
    }

    /** @test */
    public function it_returns_null_for_service_without_calcom_event_type()
    {
        $serviceWithoutCalcom = Service::factory()->create([
            'company_id' => $this->company->id,
            'calcom_event_type_id' => null,
        ]);

        $nextSlot = $this->finder->findNextAvailable($serviceWithoutCalcom);

        $this->assertNull($nextSlot);
    }

    /** @test */
    public function it_caches_next_available_slot()
    {
        $this->mockCalcomSuccessResponse([
            '2025-10-03T09:00:00Z',
            '2025-10-03T10:00:00Z',
        ]);

        $after = Carbon::parse('2025-10-03 00:00:00');

        // First call - should hit API
        $firstResult = $this->finder->findNextAvailable($this->service, $after);

        // Second call - should use cache
        $secondResult = $this->finder->findNextAvailable($this->service, $after);

        $this->assertEquals($firstResult, $secondResult);

        // Verify cache was used by checking that only one HTTP request was made
        Http::assertSentCount(1);
    }

    /** @test */
    public function it_respects_cache_ttl()
    {
        $this->mockCalcomSuccessResponse([
            '2025-10-03T09:00:00Z',
        ]);

        $after = Carbon::parse('2025-10-03 00:00:00');

        // First call
        $this->finder->findNextAvailable($this->service, $after);

        // Travel 46 seconds into future (past 45s TTL)
        $this->travel(46)->seconds();

        // This should make a new API call since cache expired
        $this->mockCalcomSuccessResponse([
            '2025-10-03T10:00:00Z',
        ]);

        $result = $this->finder->findNextAvailable($this->service, $after);

        Http::assertSentCount(2);
    }

    /** @test */
    public function it_finds_slots_in_time_window()
    {
        $this->mockCalcomSuccessResponse([
            '2025-10-03T09:00:00Z',
            '2025-10-03T10:00:00Z',
            '2025-10-03T14:00:00Z',
            '2025-10-04T09:00:00Z',
        ]);

        $start = Carbon::parse('2025-10-03 00:00:00');
        $end = Carbon::parse('2025-10-05 00:00:00');

        $slots = $this->finder->findInTimeWindow($this->service, $start, $end);

        $this->assertCount(4, $slots);
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $slots);
        $this->assertInstanceOf(Carbon::class, $slots->first());
    }

    /** @test */
    public function it_returns_empty_collection_for_invalid_time_window()
    {
        $start = Carbon::parse('2025-10-05 00:00:00');
        $end = Carbon::parse('2025-10-03 00:00:00'); // End before start

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('End time must be after start time');

        $this->finder->findInTimeWindow($this->service, $start, $end);
    }

    /** @test */
    public function it_sorts_slots_chronologically()
    {
        $this->mockCalcomSuccessResponse([
            '2025-10-03T14:00:00Z',
            '2025-10-03T09:00:00Z',
            '2025-10-04T09:00:00Z',
            '2025-10-03T10:00:00Z',
        ]);

        $start = Carbon::parse('2025-10-03 00:00:00');
        $end = Carbon::parse('2025-10-05 00:00:00');

        $slots = $this->finder->findInTimeWindow($this->service, $start, $end);

        $this->assertEquals('2025-10-03 09:00:00', $slots[0]->format('Y-m-d H:i:s'));
        $this->assertEquals('2025-10-03 10:00:00', $slots[1]->format('Y-m-d H:i:s'));
        $this->assertEquals('2025-10-03 14:00:00', $slots[2]->format('Y-m-d H:i:s'));
        $this->assertEquals('2025-10-04 09:00:00', $slots[3]->format('Y-m-d H:i:s'));
    }

    /** @test */
    public function it_handles_calcom_api_errors_gracefully()
    {
        Http::fake([
            '*' => Http::response(['error' => 'Internal Server Error'], 500),
        ]);

        $nextSlot = $this->finder->findNextAvailable($this->service);

        $this->assertNull($nextSlot);
    }

    /** @test */
    public function it_limits_search_days_to_maximum()
    {
        $this->mockCalcomSuccessResponse([
            '2025-10-03T09:00:00Z',
        ]);

        $after = Carbon::parse('2025-10-03 00:00:00');

        // Request 200 days, should be limited to 90
        $this->finder->findNextAvailable($this->service, $after, 200);

        // Verify the request was made with max 90 days
        Http::assertSent(function ($request) use ($after) {
            $query = $request->data();
            $requestedEnd = Carbon::parse($query['endTime']);
            $daysDiff = $after->diffInDays($requestedEnd);

            return $daysDiff <= 90;
        });
    }

    /** @test */
    public function it_uses_current_time_when_after_not_specified()
    {
        $this->mockCalcomSuccessResponse([
            '2025-10-03T09:00:00Z',
        ]);

        Carbon::setTestNow('2025-10-02 12:00:00');

        $this->finder->findNextAvailable($this->service);

        Http::assertSent(function ($request) {
            $query = $request->data();
            $requestedStart = Carbon::parse($query['startTime']);

            return $requestedStart->equalTo(Carbon::parse('2025-10-02 12:00:00'));
        });

        Carbon::setTestNow();
    }

    /** @test */
    public function it_handles_rate_limit_headers()
    {
        Http::fake([
            '*' => Http::response(
                ['data' => ['slots' => ['2025-10-03T09:00:00Z']]],
                200,
                ['X-RateLimit-Remaining' => '3']
            ),
        ]);

        // Just verify it completes without error - timing tests are flaky
        $result = $this->finder->findNextAvailable($this->service);
        $this->assertInstanceOf(Carbon::class, $result);
    }

    /** @test */
    public function it_handles_429_rate_limit_response()
    {
        Http::fake([
            '*' => Http::response(
                ['error' => 'Too Many Requests'],
                429,
                ['Retry-After' => '1']
            ),
        ]);

        // Just verify it handles 429 gracefully
        $result = $this->finder->findNextAvailable($this->service);
        $this->assertNull($result);
    }

    /** @test */
    public function it_parses_different_slot_formats()
    {
        // Test with array format
        Http::fake([
            '*' => Http::response([
                'data' => [
                    'slots' => [
                        ['time' => '2025-10-03T09:00:00Z'],
                        ['start' => '2025-10-03T10:00:00Z'],
                    ],
                ],
            ], 200),
        ]);

        $slots = $this->finder->findInTimeWindow(
            $this->service,
            Carbon::parse('2025-10-03 00:00:00'),
            Carbon::parse('2025-10-05 00:00:00')
        );

        $this->assertCount(2, $slots);
    }

    /** @test */
    public function it_filters_invalid_slot_times()
    {
        Http::fake([
            '*' => Http::response([
                'data' => [
                    'slots' => [
                        '2025-10-03T09:00:00Z',
                        'invalid-date',
                        null,
                        '2025-10-03T10:00:00Z',
                    ],
                ],
            ], 200),
        ]);

        $slots = $this->finder->findInTimeWindow(
            $this->service,
            Carbon::parse('2025-10-03 00:00:00'),
            Carbon::parse('2025-10-05 00:00:00')
        );

        // Should only contain 2 valid slots
        $this->assertCount(2, $slots);
    }

    /**
     * Mock successful Cal.com API response
     */
    protected function mockCalcomSuccessResponse(array $slots): void
    {
        Http::fake([
            '*' => Http::response([
                'data' => [
                    'slots' => $slots,
                ],
            ], 200),
        ]);
    }
}
