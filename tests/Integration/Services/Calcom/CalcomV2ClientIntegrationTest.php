<?php

namespace Tests\Integration\Services\Calcom;

use App\Services\Calcom\CalcomV2Client;
use App\Services\Calcom\DTOs\BookingDTO;
use App\Services\Calcom\DTOs\EventTypeDTO;
use App\Services\Calcom\DTOs\SlotDTO;
use App\Services\CircuitBreaker\CircuitBreaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

class CalcomV2ClientIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private CalcomV2Client $client;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Use test API key from config
        $apiKey = config('services.calcom.test_api_key', 'test_key');
        $this->client = new CalcomV2Client($apiKey);
        
        // Clear cache
        Cache::flush();
    }    #[Group('integration')]
    #[Test]
    public function it_handles_full_booking_workflow()
    {
        // Mock responses for full workflow
        $this->mockFullWorkflow();

        // 1. Get event types
        $eventTypes = $this->client->getEventTypes();
        $this->assertNotEmpty($eventTypes);
        
        $eventType = EventTypeDTO::fromArray($eventTypes[0]);
        $this->assertInstanceOf(EventTypeDTO::class, $eventType);

        // 2. Get available slots
        $startTime = now()->addDay()->startOfDay();
        $endTime = $startTime->copy()->endOfDay();
        
        $slots = $this->client->getAvailableSlots([
            'startTime' => $startTime->toIso8601String(),
            'endTime' => $endTime->toIso8601String(),
            'eventTypeId' => $eventType->id,
        ]);
        
        $this->assertNotEmpty($slots);
        $slot = SlotDTO::fromArray($slots[0]);
        $this->assertInstanceOf(SlotDTO::class, $slot);

        // 3. Create booking
        $bookingData = [
            'start' => $slot->start->toIso8601String(),
            'eventTypeId' => $eventType->id,
            'responses' => [
                'name' => 'Integration Test User',
                'email' => 'integration@test.com',
                'phone' => '+1234567890',
                'notes' => 'This is an integration test booking'
            ],
            'metadata' => [
                'source' => 'integration_test',
                'test_id' => uniqid()
            ],
            'timeZone' => 'America/New_York',
            'language' => 'en',
        ];

        $booking = $this->client->createBooking($bookingData);
        $this->assertInstanceOf(BookingDTO::class, $booking);
        $this->assertEquals('ACCEPTED', $booking->status);

        // 4. Get booking details
        $fetchedBooking = $this->client->getBooking($booking->uid);
        $this->assertEquals($booking->uid, $fetchedBooking->uid);

        // 5. Reschedule booking
        $newSlot = SlotDTO::fromArray($slots[1] ?? $slots[0]);
        $rescheduledBooking = $this->client->rescheduleBooking($booking->uid, [
            'start' => $newSlot->start->toIso8601String(),
            'reason' => 'Integration test reschedule'
        ]);
        
        $this->assertInstanceOf(BookingDTO::class, $rescheduledBooking);
        $this->assertNotEquals($booking->startTime, $rescheduledBooking->startTime);

        // 6. Cancel booking
        $cancellation = $this->client->cancelBooking($booking->uid, [
            'reason' => 'Integration test completed'
        ]);
        
        $this->assertArrayHasKey('success', $cancellation);
    }    #[Group('integration')]
    #[Test]
    public function it_handles_circuit_breaker_with_multiple_failures()
    {
        // Simulate API failures
        Http::fake([
            '*' => Http::sequence()
                ->push([], 500)
                ->push([], 500)
                ->push([], 500)
                ->push([], 500)
                ->push([], 500) // 5 failures should open circuit
        ]);

        $circuitBreaker = new CircuitBreaker();
        
        // Make requests that will fail
        for ($i = 0; $i < 5; $i++) {
            try {
                $this->client->getEventTypes();
            } catch (\Exception $e) {
                // Expected to fail
            }
        }

        // Circuit should now be open
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cal.com service temporarily unavailable');
        
        $this->client->getEventTypes();
    }    #[Group('integration')]
    #[Test]
    public function it_respects_cache_ttl_for_different_endpoints()
    {
        $this->mockCachedResponses();

        // Test event types cache (5 minutes)
        $eventTypes1 = $this->client->getEventTypes();
        $eventTypes2 = $this->client->getEventTypes();
        $this->assertEquals($eventTypes1, $eventTypes2);

        // Test slots cache (1 minute)
        $slotParams = [
            'startTime' => now()->toIso8601String(),
            'endTime' => now()->addDay()->toIso8601String(),
            'eventTypeId' => 1
        ];
        
        $slots1 = $this->client->getAvailableSlots($slotParams);
        $slots2 = $this->client->getAvailableSlots($slotParams);
        $this->assertEquals($slots1, $slots2);

        // Verify only 2 HTTP calls were made (not 4)
        Http::assertSentCount(2);
    }    #[Group('integration')]
    #[Test]
    public function it_handles_concurrent_bookings_with_locking()
    {
        $this->mockConcurrentBookingScenario();

        $slot = [
            'start' => now()->addDay()->setTime(10, 0)->toIso8601String(),
            'end' => now()->addDay()->setTime(10, 30)->toIso8601String(),
            'eventTypeId' => 1
        ];

        $bookingData = [
            'start' => $slot['start'],
            'eventTypeId' => $slot['eventTypeId'],
            'responses' => [
                'name' => 'User 1',
                'email' => 'user1@test.com'
            ],
            'metadata' => []
        ];

        // Simulate concurrent booking attempts
        $results = [];
        
        // First booking should succeed
        try {
            $booking1 = $this->client->createBooking($bookingData);
            $results[] = ['success' => true, 'booking' => $booking1];
        } catch (\Exception $e) {
            $results[] = ['success' => false, 'error' => $e->getMessage()];
        }

        // Second booking for same slot should fail
        $bookingData['responses']['name'] = 'User 2';
        $bookingData['responses']['email'] = 'user2@test.com';
        
        try {
            $booking2 = $this->client->createBooking($bookingData);
            $results[] = ['success' => true, 'booking' => $booking2];
        } catch (\Exception $e) {
            $results[] = ['success' => false, 'error' => $e->getMessage()];
        }

        // Verify only one booking succeeded
        $successCount = count(array_filter($results, fn($r) => $r['success']));
        $this->assertEquals(1, $successCount);
    }    #[Group('integration')]
    #[Test]
    public function it_handles_pagination_for_large_datasets()
    {
        $this->mockPaginatedResponses();

        // Get all bookings with pagination
        $allBookings = [];
        $page = 1;
        $limit = 50;
        
        do {
            $bookings = $this->client->getBookings([
                'page' => $page,
                'limit' => $limit
            ]);
            
            $allBookings = array_merge($allBookings, $bookings['data'] ?? []);
            $hasMore = count($bookings['data'] ?? []) === $limit;
            $page++;
            
        } while ($hasMore && $page <= 10); // Safety limit

        $this->assertGreaterThan(50, count($allBookings));
    }    #[Group('integration')]
    #[Test]
    public function it_handles_timezone_conversions_correctly()
    {
        $this->mockTimezoneResponses();

        // Request slots in different timezones
        $timezones = ['America/New_York', 'Europe/London', 'Asia/Tokyo'];
        $results = [];

        foreach ($timezones as $timezone) {
            $slots = $this->client->getAvailableSlots([
                'startTime' => now()->setTimezone($timezone)->startOfDay()->toIso8601String(),
                'endTime' => now()->setTimezone($timezone)->endOfDay()->toIso8601String(),
                'eventTypeId' => 1,
                'timeZone' => $timezone
            ]);
            
            $results[$timezone] = $slots;
        }

        // Verify slots are returned in requested timezones
        foreach ($results as $timezone => $slots) {
            if (!empty($slots)) {
                $slot = SlotDTO::fromArray($slots[0]);
                $this->assertEquals($timezone, $slot->start->timezone->getName());
            }
        }
    }

    /**
     * Helper method to mock full workflow responses
     */
    private function mockFullWorkflow(): void
    {
        Http::fake([
            '*/event-types*' => Http::response([
                'data' => [
                    [
                        'id' => 1,
                        'slug' => 'consultation',
                        'title' => 'Consultation',
                        'length' => 30,
                        'locations' => []
                    ]
                ]
            ], 200),
            
            '*/slots/available*' => Http::response([
                'data' => [
                    [
                        'start' => now()->addDay()->setTime(10, 0)->toIso8601String(),
                        'end' => now()->addDay()->setTime(10, 30)->toIso8601String(),
                        'eventTypeId' => 1
                    ],
                    [
                        'start' => now()->addDay()->setTime(11, 0)->toIso8601String(),
                        'end' => now()->addDay()->setTime(11, 30)->toIso8601String(),
                        'eventTypeId' => 1
                    ]
                ]
            ], 200),
            
            '*/bookings' => Http::sequence()
                ->push([
                    'data' => [
                        'id' => 123,
                        'uid' => 'test-booking-uid',
                        'title' => 'Consultation with Integration Test User',
                        'status' => 'ACCEPTED',
                        'startTime' => now()->addDay()->setTime(10, 0)->toIso8601String(),
                        'endTime' => now()->addDay()->setTime(10, 30)->toIso8601String(),
                        'attendees' => []
                    ]
                ], 201)
                ->push(['data' => []], 200),
            
            '*/bookings/test-booking-uid' => Http::response([
                'data' => [
                    'id' => 123,
                    'uid' => 'test-booking-uid',
                    'title' => 'Consultation with Integration Test User',
                    'status' => 'ACCEPTED',
                    'startTime' => now()->addDay()->setTime(10, 0)->toIso8601String(),
                    'endTime' => now()->addDay()->setTime(10, 30)->toIso8601String(),
                    'attendees' => []
                ]
            ], 200),
            
            '*/bookings/test-booking-uid/reschedule' => Http::response([
                'data' => [
                    'id' => 123,
                    'uid' => 'test-booking-uid',
                    'status' => 'ACCEPTED',
                    'startTime' => now()->addDay()->setTime(11, 0)->toIso8601String(),
                    'endTime' => now()->addDay()->setTime(11, 30)->toIso8601String(),
                    'attendees' => []
                ]
            ], 200),
            
            '*/bookings/test-booking-uid/cancel' => Http::response([
                'success' => true,
                'message' => 'Booking cancelled successfully'
            ], 200),
        ]);
    }

    /**
     * Helper for cached response mocking
     */
    private function mockCachedResponses(): void
    {
        Http::fake([
            '*/event-types*' => Http::response(['data' => [['id' => 1]]], 200),
            '*/slots/available*' => Http::response(['data' => [['start' => now()->toIso8601String()]]], 200),
        ]);
    }

    /**
     * Helper for concurrent booking scenario
     */
    private function mockConcurrentBookingScenario(): void
    {
        Http::fake([
            '*/bookings' => Http::sequence()
                ->push(['data' => ['id' => 1, 'uid' => 'booking-1']], 201)
                ->push(['message' => 'Slot no longer available'], 409),
        ]);
    }

    /**
     * Helper for paginated responses
     */
    private function mockPaginatedResponses(): void
    {
        $bookings = [];
        for ($i = 1; $i <= 150; $i++) {
            $bookings[] = ['id' => $i, 'uid' => "booking-{$i}"];
        }

        Http::fake(function ($request) use ($bookings) {
            $page = $request->data()['page'] ?? 1;
            $limit = $request->data()['limit'] ?? 50;
            $offset = ($page - 1) * $limit;
            
            return Http::response([
                'data' => array_slice($bookings, $offset, $limit),
                'meta' => [
                    'total' => count($bookings),
                    'page' => $page,
                    'limit' => $limit
                ]
            ], 200);
        });
    }

    /**
     * Helper for timezone responses
     */
    private function mockTimezoneResponses(): void
    {
        Http::fake(function ($request) {
            $timezone = $request->data()['timeZone'] ?? 'UTC';
            
            return Http::response([
                'data' => [
                    [
                        'start' => now()->setTimezone($timezone)->setTime(10, 0)->toIso8601String(),
                        'end' => now()->setTimezone($timezone)->setTime(10, 30)->toIso8601String(),
                        'eventTypeId' => 1
                    ]
                ]
            ], 200);
        });
    }
}