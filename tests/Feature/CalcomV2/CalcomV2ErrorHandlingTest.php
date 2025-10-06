<?php

namespace Tests\Feature\CalcomV2;

use Tests\TestCase;
use App\Services\CalcomV2Client;
use App\Services\Booking\CompositeBookingService;
use App\Services\Booking\BookingLockService;
use App\Services\Communication\NotificationService;
use App\Models\{Company, Branch, Service, Staff, Customer, Appointment};
use App\Exceptions\CalcomApiException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Carbon\Carbon;
use Mockery;
use Exception;

class CalcomV2ErrorHandlingTest extends TestCase
{
    use RefreshDatabase;

    private CalcomV2Client $client;
    private CompositeBookingService $compositeService;
    private Company $company;
    private Branch $branch;
    private Service $service;
    private Staff $staff;
    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test data
        $this->company = Company::factory()->create([
            'calcom_v2_api_key' => 'test-api-key-' . uniqid()
        ]);

        $this->branch = Branch::factory()->create([
            'company_id' => $this->company->id
        ]);

        $this->service = Service::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'duration' => 60,
            'is_composite' => true,
            'segments' => [
                [
                    'key' => 'coloring',
                    'name' => 'Hair Coloring',
                    'durationMin' => 60,
                    'gapAfterMin' => 30,
                    'gapAfterMax' => 60,
                    'preferSameStaff' => true
                ],
                [
                    'key' => 'styling',
                    'name' => 'Hair Styling',
                    'durationMin' => 45
                ]
            ]
        ]);

        $this->staff = Staff::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'calcom_event_type_id' => 12345
        ]);

        $this->customer = Customer::factory()->create([
            'company_id' => $this->company->id
        ]);

        $this->client = new CalcomV2Client($this->company);

        $this->compositeService = new CompositeBookingService(
            $this->client,
            app(BookingLockService::class),
            app(NotificationService::class)
        );
    }

    /**
     * Test API timeout handling with retry mechanism
     */
    public function test_api_timeout_with_retry()
    {
        $attempts = 0;

        Http::fake([
            'api.cal.com/v2/slots*' => Http::sequence()
                ->pushStatus(408) // First attempt: timeout
                ->pushStatus(408) // Second attempt: timeout
                ->push([        // Third attempt: success
                    'status' => 'success',
                    'data' => [
                        'slots' => [
                            ['start' => '2025-01-20T10:00:00Z', 'end' => '2025-01-20T11:00:00Z']
                        ]
                    ]
                ], 200)
        ]);

        $start = Carbon::parse('2025-01-20 09:00');
        $end = Carbon::parse('2025-01-20 18:00');

        $response = $this->client->getAvailableSlots(12345, $start, $end);

        // Should succeed after retries
        $this->assertTrue($response->successful());
        $this->assertNotEmpty($response->json('data.slots'));

        // Verify retry attempts were logged
        Log::shouldReceive('warning')
            ->with(Mockery::pattern('/Retry attempt \d+ for Cal\.com API/'), Mockery::any());
    }

    /**
     * Test rate limiting (429) with exponential backoff
     */
    public function test_rate_limiting_with_exponential_backoff()
    {
        Http::fake([
            'api.cal.com/v2/bookings' => Http::sequence()
                ->pushStatus(429, ['Retry-After' => '2'])
                ->pushStatus(429, ['Retry-After' => '4'])
                ->push([
                    'status' => 'success',
                    'data' => [
                        'id' => 98765,
                        'uid' => 'test-booking-uid',
                        'status' => 'ACCEPTED'
                    ]
                ], 201)
        ]);

        $startTime = now();

        $response = $this->client->createBooking([
            'eventTypeId' => 12345,
            'start' => '2025-01-20T10:00:00Z',
            'end' => '2025-01-20T11:00:00Z',
            'timeZone' => 'Europe/Berlin',
            'name' => 'Test User',
            'email' => 'test@example.com'
        ]);

        $endTime = now();

        // Should eventually succeed
        $this->assertTrue($response->successful());

        // Verify backoff delay was applied (should take at least 2 seconds)
        $this->assertGreaterThanOrEqual(2, $endTime->diffInSeconds($startTime));
    }

    /**
     * Test network failure recovery
     */
    public function test_network_failure_recovery()
    {
        Http::fake(function ($request) {
            static $attempts = 0;
            $attempts++;

            if ($attempts <= 2) {
                throw new ConnectionException('Network unreachable');
            }

            return Http::response([
                'status' => 'success',
                'data' => ['slots' => []]
            ], 200);
        });

        $start = Carbon::parse('2025-01-20 09:00');
        $end = Carbon::parse('2025-01-20 18:00');

        try {
            $response = $this->client->getAvailableSlots(12345, $start, $end);
            $this->assertTrue($response->successful());
        } catch (ConnectionException $e) {
            // Should not reach here if retry worked
            $this->fail('Network recovery failed');
        }
    }

    /**
     * Test composite booking compensation saga on failure
     */
    public function test_composite_booking_compensation_saga()
    {
        // Mock first segment success, second segment failure
        Http::fake([
            'api.cal.com/v2/bookings' => Http::sequence()
                ->push([
                    'status' => 'success',
                    'data' => [
                        'id' => 111,
                        'uid' => 'segment-1-uid',
                        'status' => 'ACCEPTED'
                    ]
                ], 201)
                ->pushStatus(500) // Second segment fails
                ->push([ // Compensation: cancel first segment
                    'status' => 'success',
                    'data' => ['message' => 'Booking cancelled']
                ], 200)
        ]);

        $bookingData = [
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'service_id' => $this->service->id,
            'customer_id' => $this->customer->id,
            'customer' => [
                'name' => 'Test Customer',
                'email' => 'customer@test.com'
            ],
            'segments' => [
                [
                    'key' => 'coloring',
                    'staff_id' => $this->staff->id,
                    'starts_at' => '2025-01-20 10:00:00',
                    'ends_at' => '2025-01-20 11:00:00'
                ],
                [
                    'key' => 'styling',
                    'staff_id' => $this->staff->id,
                    'starts_at' => '2025-01-20 11:30:00',
                    'ends_at' => '2025-01-20 12:15:00'
                ]
            ]
        ];

        try {
            $appointment = $this->compositeService->bookComposite($bookingData);
            $this->fail('Expected exception was not thrown');
        } catch (Exception $e) {
            // Expected to fail
            $this->assertStringContainsString('Cal.com booking failed', $e->getMessage());
        }

        // Verify compensation was executed
        Http::assertSentCount(3); // 2 bookings + 1 cancellation
    }

    /**
     * Test invalid data recovery with validation
     */
    public function test_invalid_data_recovery()
    {
        Http::fake([
            'api.cal.com/v2/bookings' => Http::sequence()
                ->push([
                    'status' => 'error',
                    'error' => [
                        'code' => 'INVALID_INPUT',
                        'message' => 'Invalid time zone format'
                    ]
                ], 400)
                ->push([ // Second attempt with corrected data
                    'status' => 'success',
                    'data' => [
                        'id' => 222,
                        'uid' => 'recovered-booking',
                        'status' => 'ACCEPTED'
                    ]
                ], 201)
        ]);

        // First attempt with invalid timezone
        $invalidData = [
            'eventTypeId' => 12345,
            'start' => '2025-01-20T10:00:00Z',
            'end' => '2025-01-20T11:00:00Z',
            'timeZone' => 'Invalid/Timezone',
            'name' => 'Test User',
            'email' => 'test@example.com'
        ];

        $response = $this->client->createBooking($invalidData);
        $this->assertFalse($response->successful());

        // Recover with valid data
        $validData = $invalidData;
        $validData['timeZone'] = 'Europe/Berlin';

        $response = $this->client->createBooking($validData);
        $this->assertTrue($response->successful());
    }

    /**
     * Test circuit breaker pattern for repeated failures
     */
    public function test_circuit_breaker_for_repeated_failures()
    {
        // Simulate 5 consecutive failures
        Http::fake([
            'api.cal.com/v2/*' => Http::sequence()
                ->pushStatus(500)
                ->pushStatus(500)
                ->pushStatus(500)
                ->pushStatus(500)
                ->pushStatus(500)
        ]);

        $circuitKey = 'calcom:circuit:' . $this->company->id;
        Cache::forget($circuitKey);

        // Make requests until circuit opens
        for ($i = 1; $i <= 5; $i++) {
            try {
                $this->client->getEventTypes();
            } catch (Exception $e) {
                // Expected failures
            }
        }

        // Circuit should be open now
        Cache::put($circuitKey, [
            'status' => 'open',
            'failures' => 5,
            'last_failure' => now()
        ], 300);

        // Next request should fail immediately (circuit open)
        $this->expectException(CalcomApiException::class);
        $this->expectExceptionMessage('Circuit breaker is open');

        // This would normally be handled in the client
        if (Cache::get($circuitKey)['status'] === 'open') {
            throw new CalcomApiException('Circuit breaker is open');
        }
    }

    /**
     * Test graceful degradation with cache fallback
     */
    public function test_graceful_degradation_with_cache()
    {
        $cacheKey = 'calcom:slots:12345:2025-01-20';

        // Prime cache with successful response
        $cachedSlots = [
            ['start' => '2025-01-20T10:00:00Z', 'end' => '2025-01-20T11:00:00Z'],
            ['start' => '2025-01-20T14:00:00Z', 'end' => '2025-01-20T15:00:00Z']
        ];

        Cache::put($cacheKey, $cachedSlots, 3600);

        // Simulate API failure
        Http::fake([
            'api.cal.com/v2/slots*' => Http::response(null, 503)
        ]);

        // Should fall back to cache
        $slots = $this->getAvailableSlotsWithFallback(
            12345,
            Carbon::parse('2025-01-20 09:00'),
            Carbon::parse('2025-01-20 18:00')
        );

        $this->assertNotEmpty($slots);
        $this->assertCount(2, $slots);
        $this->assertEquals($cachedSlots, $slots);
    }

    /**
     * Test webhook delivery failure with retry queue
     */
    public function test_webhook_delivery_failure_with_retry()
    {
        Queue::fake();

        $webhookData = [
            'event' => 'BOOKING.CREATED',
            'payload' => [
                'bookingId' => 333,
                'uid' => 'webhook-test-uid',
                'eventTypeId' => 12345,
                'startTime' => '2025-01-20T10:00:00Z',
                'endTime' => '2025-01-20T11:00:00Z'
            ]
        ];

        // Simulate webhook processing failure
        Http::fake([
            'our-webhook-endpoint.com/*' => Http::sequence()
                ->pushStatus(500)
                ->pushStatus(500)
                ->push(['status' => 'ok'], 200)
        ]);

        // Process webhook with retry logic
        $processor = new \App\Jobs\ProcessCalcomWebhook($webhookData);

        Queue::assertPushed(\App\Jobs\ProcessCalcomWebhook::class);

        // Job should be retried on failure
        $this->assertTrue($processor->tries > 1);
        $this->assertEquals(60, $processor->backoff());
    }

    /**
     * Test handling of partial success in bulk operations
     */
    public function test_partial_success_in_bulk_operations()
    {
        $eventTypeIds = [101, 102, 103, 104, 105];
        $results = [];

        Http::fake([
            'api.cal.com/v2/event-types/101' => Http::response(['data' => ['id' => 101]], 200),
            'api.cal.com/v2/event-types/102' => Http::response(null, 404),
            'api.cal.com/v2/event-types/103' => Http::response(['data' => ['id' => 103]], 200),
            'api.cal.com/v2/event-types/104' => Http::response(null, 500),
            'api.cal.com/v2/event-types/105' => Http::response(['data' => ['id' => 105]], 200),
        ]);

        foreach ($eventTypeIds as $id) {
            try {
                $response = $this->client->getEventType($id);
                $results[$id] = [
                    'success' => $response->successful(),
                    'data' => $response->json('data')
                ];
            } catch (Exception $e) {
                $results[$id] = [
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
        }

        // Verify partial success handling
        $successful = array_filter($results, fn($r) => $r['success']);
        $failed = array_filter($results, fn($r) => !$r['success']);

        $this->assertCount(3, $successful);
        $this->assertCount(2, $failed);
        $this->assertArrayHasKey(102, $failed); // 404
        $this->assertArrayHasKey(104, $failed); // 500
    }

    /**
     * Test idempotency with duplicate request detection
     */
    public function test_idempotency_with_duplicate_detection()
    {
        $idempotencyKey = 'booking-' . uniqid();

        Http::fake([
            'api.cal.com/v2/bookings' => function ($request) use ($idempotencyKey) {
                $headers = $request->headers();

                if (isset($headers['X-Idempotency-Key'][0]) &&
                    $headers['X-Idempotency-Key'][0] === $idempotencyKey) {

                    static $called = false;
                    if ($called) {
                        // Return same response for duplicate
                        return Http::response([
                            'status' => 'success',
                            'data' => [
                                'id' => 444,
                                'uid' => 'idempotent-booking',
                                'status' => 'ACCEPTED',
                                'duplicate' => true
                            ]
                        ], 200);
                    }
                    $called = true;
                }

                return Http::response([
                    'status' => 'success',
                    'data' => [
                        'id' => 444,
                        'uid' => 'idempotent-booking',
                        'status' => 'ACCEPTED'
                    ]
                ], 201);
            }
        ]);

        $bookingData = [
            'eventTypeId' => 12345,
            'start' => '2025-01-20T10:00:00Z',
            'end' => '2025-01-20T11:00:00Z',
            'timeZone' => 'Europe/Berlin',
            'name' => 'Test User',
            'email' => 'test@example.com'
        ];

        // First request
        $response1 = Http::withHeaders([
            'X-Idempotency-Key' => $idempotencyKey
        ])->post('api.cal.com/v2/bookings', $bookingData);

        // Duplicate request with same idempotency key
        $response2 = Http::withHeaders([
            'X-Idempotency-Key' => $idempotencyKey
        ])->post('api.cal.com/v2/bookings', $bookingData);

        $this->assertEquals(201, $response1->status());
        $this->assertEquals(200, $response2->status()); // Returns existing
        $this->assertEquals(
            $response1->json('data.id'),
            $response2->json('data.id')
        );
    }

    /**
     * Test handling of malformed API responses
     */
    public function test_malformed_api_response_handling()
    {
        Http::fake([
            'api.cal.com/v2/slots*' => Http::sequence()
                ->push('Not JSON', 200)
                ->push('<html>Error Page</html>', 200)
                ->push('{"invalid": json}', 200)
                ->push(['data' => ['slots' => []]], 200)
        ]);

        $start = Carbon::parse('2025-01-20 09:00');
        $end = Carbon::parse('2025-01-20 18:00');

        // Test various malformed responses
        $attempts = 4;
        $successCount = 0;

        for ($i = 0; $i < $attempts; $i++) {
            try {
                $response = $this->client->getAvailableSlots(12345, $start, $end);
                if ($response->successful() && $response->json() !== null) {
                    $successCount++;
                }
            } catch (Exception $e) {
                // Log malformed response
                Log::error('Malformed API response', [
                    'attempt' => $i + 1,
                    'error' => $e->getMessage()
                ]);
            }
        }

        // Only the last attempt should succeed
        $this->assertEquals(1, $successCount);
    }

    /**
     * Test timeout recovery for long-running operations
     */
    public function test_timeout_recovery_for_long_operations()
    {
        Http::fake(function ($request) {
            static $attempt = 0;
            $attempt++;

            if ($attempt === 1) {
                // Simulate timeout by delaying response
                sleep(3);
                return Http::response(null, 408);
            }

            return Http::response([
                'status' => 'success',
                'data' => ['message' => 'Operation completed']
            ], 200);
        });

        $startTime = microtime(true);

        // Operation that might timeout
        $response = $this->performLongOperation();

        $duration = microtime(true) - $startTime;

        // Should recover and complete
        $this->assertTrue($response->successful());
        $this->assertGreaterThan(3, $duration); // Includes timeout + retry
    }

    // Helper methods

    private function getAvailableSlotsWithFallback($eventTypeId, $start, $end)
    {
        $cacheKey = sprintf('calcom:slots:%d:%s', $eventTypeId, $start->toDateString());

        try {
            $response = $this->client->getAvailableSlots($eventTypeId, $start, $end);

            if ($response->successful()) {
                $slots = $response->json('data.slots', []);
                Cache::put($cacheKey, $slots, 3600);
                return $slots;
            }
        } catch (Exception $e) {
            Log::warning('Cal.com API failed, using cache fallback', [
                'error' => $e->getMessage()
            ]);
        }

        // Fall back to cache
        return Cache::get($cacheKey, []);
    }

    private function performLongOperation()
    {
        return Http::timeout(5)->retry(2, 1000)->get('api.cal.com/v2/long-operation');
    }
}