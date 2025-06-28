<?php

namespace Tests\Unit\Services\Calcom;

use App\Services\Calcom\CalcomV2Client;
use App\Services\Calcom\DTOs\BookingDTO;
use App\Services\Calcom\Exceptions\CalcomApiException;
use App\Services\Calcom\Exceptions\CalcomAuthenticationException;
use App\Services\Calcom\Exceptions\CalcomRateLimitException;
use App\Services\Calcom\Exceptions\CalcomValidationException;
use App\Services\CircuitBreaker\CircuitBreaker;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CalcomV2ClientTest extends TestCase
{
    private CalcomV2Client $client;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->client = new CalcomV2Client('test_api_key');
        
        // Clear cache before each test
        Cache::flush();
    }    #[Test]
    public function it_sets_correct_headers_on_requests()
    {
        Http::fake([
            '*' => Http::response(['data' => []], 200)
        ]);

        $this->client->getEventTypes();

        Http::assertSent(function (Request $request) {
            return $request->hasHeader('Authorization', 'Bearer test_api_key') &&
                   $request->hasHeader('Content-Type', 'application/json') &&
                   $request->hasHeader('Accept', 'application/json') &&
                   $request->hasHeader('cal-api-version', '2024-08-13');
        });
    }    #[Test]
    public function it_fetches_event_types_successfully()
    {
        $mockResponse = [
            'data' => [
                [
                    'id' => 1,
                    'slug' => 'test-event',
                    'title' => 'Test Event',
                    'length' => 30,
                    'locations' => []
                ]
            ],
            'count' => 1
        ];

        Http::fake([
            '*/event-types*' => Http::response($mockResponse, 200)
        ]);

        $result = $this->client->getEventTypes();

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('test-event', $result[0]['slug']);
    }    #[Test]
    public function it_caches_event_types()
    {
        $mockResponse = [
            'data' => [['id' => 1, 'title' => 'Test Event']]
        ];

        Http::fake([
            '*/event-types*' => Http::sequence()
                ->push($mockResponse, 200)
                ->push(['data' => []], 500) // Second call would fail
        ]);

        // First call - should hit API
        $result1 = $this->client->getEventTypes();
        
        // Second call - should use cache
        $result2 = $this->client->getEventTypes();

        $this->assertEquals($result1, $result2);
        
        // Only one HTTP call should be made
        Http::assertSentCount(1);
    }    #[Test]
    public function it_fetches_available_slots()
    {
        $mockResponse = [
            'data' => [
                [
                    'start' => '2024-01-01T10:00:00Z',
                    'end' => '2024-01-01T10:30:00Z',
                    'attendees' => [],
                    'eventTypeId' => 1
                ]
            ]
        ];

        Http::fake([
            '*/slots/available*' => Http::response($mockResponse, 200)
        ]);

        $params = [
            'startTime' => '2024-01-01T00:00:00Z',
            'endTime' => '2024-01-02T00:00:00Z',
            'eventTypeId' => 1
        ];

        $result = $this->client->getAvailableSlots($params);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('2024-01-01T10:00:00Z', $result[0]['start']);
    }    #[Test]
    public function it_validates_slot_parameters()
    {
        $this->expectException(CalcomValidationException::class);
        $this->expectExceptionMessage('Missing required parameter: startTime');

        $this->client->getAvailableSlots(['endTime' => '2024-01-01']);
    }    #[Test]
    public function it_requires_event_identifier_for_slots()
    {
        $this->expectException(CalcomValidationException::class);
        $this->expectExceptionMessage('Must provide either eventTypeId or eventTypeSlug');

        $this->client->getAvailableSlots([
            'startTime' => '2024-01-01',
            'endTime' => '2024-01-02'
        ]);
    }    #[Test]
    public function it_creates_booking_successfully()
    {
        $mockResponse = [
            'data' => [
                'id' => 123,
                'uid' => 'booking-uid',
                'title' => 'Test Booking',
                'status' => 'ACCEPTED',
                'startTime' => '2024-01-01T10:00:00Z',
                'endTime' => '2024-01-01T10:30:00Z',
                'attendees' => []
            ]
        ];

        Http::fake([
            '*/bookings' => Http::response($mockResponse, 201)
        ]);

        $bookingData = [
            'start' => '2024-01-01T10:00:00Z',
            'eventTypeId' => 1,
            'responses' => [
                'name' => 'John Doe',
                'email' => 'john@example.com'
            ],
            'metadata' => []
        ];

        $result = $this->client->createBooking($bookingData);

        $this->assertInstanceOf(BookingDTO::class, $result);
        $this->assertEquals('booking-uid', $result->uid);
        $this->assertEquals('ACCEPTED', $result->status);
    }    #[Test]
    public function it_validates_booking_data()
    {
        $this->expectException(CalcomValidationException::class);
        $this->expectExceptionMessage('Missing required field: responses');

        $this->client->createBooking([
            'start' => '2024-01-01T10:00:00Z',
            'eventTypeId' => 1
        ]);
    }    #[Test]
    public function it_handles_authentication_errors()
    {
        Http::fake([
            '*' => Http::response(['message' => 'Invalid API key'], 401)
        ]);

        $this->expectException(CalcomAuthenticationException::class);
        $this->expectExceptionMessage('Invalid API key');

        $this->client->getEventTypes();
    }    #[Test]
    public function it_handles_rate_limit_errors()
    {
        Http::fake([
            '*' => Http::response(
                ['message' => 'Rate limit exceeded'],
                429,
                ['Retry-After' => '60']
            )
        ]);

        try {
            $this->client->getEventTypes();
            $this->fail('Should have thrown CalcomRateLimitException');
        } catch (CalcomRateLimitException $e) {
            $this->assertEquals('Rate limit exceeded', $e->getMessage());
            $this->assertEquals(60, $e->getRetryAfter());
        }
    }    #[Test]
    public function it_handles_validation_errors()
    {
        Http::fake([
            '*' => Http::response([
                'message' => 'Validation failed',
                'errors' => [
                    'email' => ['Email is invalid'],
                    'start' => ['Start time must be in the future']
                ]
            ], 422)
        ]);

        try {
            $this->client->createBooking([]);
            $this->fail('Should have thrown CalcomValidationException');
        } catch (CalcomValidationException $e) {
            $this->assertEquals('Validation failed', $e->getMessage());
            $this->assertCount(2, $e->getErrors());
            $this->assertContains('email: Email is invalid', $e->getErrorMessages());
        }
    }    #[Test]
    public function it_reschedules_booking()
    {
        $mockResponse = [
            'data' => [
                'id' => 123,
                'uid' => 'booking-uid',
                'status' => 'ACCEPTED',
                'startTime' => '2024-01-02T10:00:00Z',
                'endTime' => '2024-01-02T10:30:00Z',
                'attendees' => []
            ]
        ];

        Http::fake([
            '*/bookings/*/reschedule' => Http::response($mockResponse, 200)
        ]);

        $result = $this->client->rescheduleBooking('booking-uid', [
            'start' => '2024-01-02T10:00:00Z'
        ]);

        $this->assertInstanceOf(BookingDTO::class, $result);
        $this->assertEquals('2024-01-02T10:00:00Z', $result->startTime->toIso8601String());
    }    #[Test]
    public function it_cancels_booking()
    {
        Http::fake([
            '*/bookings/*/cancel' => Http::response(['success' => true], 200)
        ]);

        $result = $this->client->cancelBooking('booking-uid', [
            'reason' => 'Customer request'
        ]);

        $this->assertTrue($result['success']);
    }    #[Test]
    public function it_invalidates_cache_after_booking_operations()
    {
        // Set up cache
        Cache::put('calcom_v2:slots:test', ['cached' => true], 60);

        Http::fake([
            '*/bookings' => Http::response(['data' => ['id' => 1]], 201)
        ]);

        $this->client->createBooking([
            'start' => '2024-01-01T10:00:00Z',
            'eventTypeId' => 1,
            'responses' => ['name' => 'Test', 'email' => 'test@test.com'],
            'metadata' => []
        ]);

        // Cache should be cleared
        $this->assertNull(Cache::get('calcom_v2:slots:test'));
    }    #[Test]
    public function it_performs_health_check()
    {
        Http::fake([
            '*/event-types*' => Http::response(['data' => []], 200)
        ]);

        $health = $this->client->healthCheck();

        $this->assertEquals('healthy', $health['status']);
        $this->assertArrayHasKey('response_time_ms', $health);
        $this->assertArrayHasKey('circuit_state', $health);
    }    #[Test]
    public function it_reports_unhealthy_on_api_failure()
    {
        Http::fake([
            '*' => Http::response([], 500)
        ]);

        $health = $this->client->healthCheck();

        $this->assertEquals('unhealthy', $health['status']);
        $this->assertArrayHasKey('error', $health);
    }    #[Test]
    public function it_provides_metrics()
    {
        $metrics = $this->client->getMetrics();

        $this->assertArrayHasKey('circuit_breaker', $metrics);
        $this->assertArrayHasKey('cache', $metrics);
        $this->assertArrayHasKey('api_version', $metrics);
        
        $this->assertEquals('2024-08-13', $metrics['api_version']);
        $this->assertEquals(300, $metrics['cache']['event_types_ttl']);
        $this->assertEquals(60, $metrics['cache']['slots_ttl']);
    }    #[Test]
    public function it_sanitizes_sensitive_data_in_logs()
    {
        Http::fake([
            '*' => Http::response(['data' => []], 201)
        ]);

        // This would normally be tested by checking logs
        // but for unit tests we ensure the method exists
        $reflection = new \ReflectionClass($this->client);
        $method = $reflection->getMethod('sanitizeOptions');
        $method->setAccessible(true);

        $options = [
            'json' => [
                'responses' => [
                    'name' => 'John Doe',
                    'email' => 'john@example.com',
                    'phone' => '+1234567890',
                    'notes' => 'Private notes'
                ]
            ]
        ];

        $sanitized = $method->invoke($this->client, $options);

        $this->assertEquals('John Doe', $sanitized['json']['responses']['name']);
        $this->assertEquals('[REDACTED]', $sanitized['json']['responses']['email']);
        $this->assertEquals('[REDACTED]', $sanitized['json']['responses']['phone']);
        $this->assertEquals('[REDACTED]', $sanitized['json']['responses']['notes']);
    }
}