<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\CalcomService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CalcomServiceTest extends TestCase
{
    use RefreshDatabase;

    private CalcomService $service;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock configuration
        Config::set([
            'services.calcom.base_url' => 'https://api.cal.com/v2',
            'services.calcom.api_key' => 'test-api-key',
            'services.calcom.event_type_id' => 123,
            'services.calcom.max_retries' => 3,
            'services.calcom.timezone' => 'Europe/Berlin',
            'services.calcom.language' => 'de'
        ]);

        $this->service = new CalcomService();
    }

    /** @test */
    public function it_can_fetch_event_type_successfully()
    {
        // Arrange
        $eventTypeId = 123;
        $expectedResponse = [
            'event_type' => [
                'id' => $eventTypeId,
                'title' => 'Test Event',
                'length' => 30
            ]
        ];

        Http::fake([
            "https://api.cal.com/v2/event-types/{$eventTypeId}" => Http::response($expectedResponse, 200)
        ]);

        // Act
        $result = $this->service->getEventType($eventTypeId);

        // Assert
        $this->assertEquals($expectedResponse, $result);
        Http::assertSent(function ($request) use ($eventTypeId) {
            return $request->url() === "https://api.cal.com/v2/event-types/{$eventTypeId}" &&
                   $request->method() === 'GET' &&
                   $request->hasHeader('Authorization', 'Bearer test-api-key');
        });
    }

    /** @test */
    public function it_throws_exception_when_event_type_not_found()
    {
        // Arrange
        $eventTypeId = 999;
        Http::fake([
            "https://api.cal.com/v2/event-types/{$eventTypeId}" => Http::response(['error' => 'Not found'], 404)
        ]);

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed to fetch event type');
        
        $this->service->getEventType($eventTypeId);
    }

    /** @test */
    public function it_can_create_booking_from_call_data()
    {
        // Arrange
        $requestData = [
            'attendee_name' => 'John Doe',
            'attendee_email' => 'john@example.com',
            'start_time' => '2025-09-05T10:00:00Z',
            'notes' => 'Test booking'
        ];

        $eventTypeResponse = [
            'event_type' => [
                'id' => 123,
                'length' => 30
            ]
        ];

        $bookingResponse = [
            'booking' => [
                'id' => 456,
                'uid' => 'booking-uid-123',
                'startTime' => '2025-09-05T10:00:00Z',
                'endTime' => '2025-09-05T10:30:00Z',
                'status' => 'ACCEPTED'
            ]
        ];

        Http::fake([
            'https://api.cal.com/v2/event-types/123' => Http::response($eventTypeResponse, 200),
            'https://api.cal.com/v2/bookings' => Http::response($bookingResponse, 201)
        ]);

        // Act
        $result = $this->service->createBookingFromCall($requestData);

        // Assert
        $this->assertEquals($bookingResponse, $result);
        Http::assertSentCount(2);
    }

    /** @test */
    public function it_handles_api_timeout_with_retry()
    {
        // Arrange
        $eventTypeId = 123;
        
        Http::fake([
            "https://api.cal.com/v2/event-types/{$eventTypeId}" => Http::sequence()
                ->push('', 408) // Timeout
                ->push('', 408) // Timeout
                ->push(['event_type' => ['id' => $eventTypeId]], 200) // Success on third try
        ]);

        // Act
        $result = $this->service->getEventType($eventTypeId);

        // Assert
        $this->assertArrayHasKey('event_type', $result);
        $this->assertEquals($eventTypeId, $result['event_type']['id']);
        Http::assertSentCount(3);
    }

    /** @test */
    public function it_validates_required_booking_fields()
    {
        // Arrange
        $incompleteData = [
            'attendee_name' => 'John Doe',
            // Missing email and start_time
        ];

        // Act & Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required booking fields');
        
        $this->service->createBookingFromCall($incompleteData);
    }

    /** @test */
    public function it_can_cancel_booking()
    {
        // Arrange
        $bookingId = 456;
        
        Http::fake([
            "https://api.cal.com/v2/bookings/{$bookingId}" => Http::response(['status' => 'cancelled'], 200)
        ]);

        // Act
        $result = $this->service->cancelBooking($bookingId, 'Test cancellation');

        // Assert
        $this->assertTrue($result);
        Http::assertSent(function ($request) use ($bookingId) {
            return $request->url() === "https://api.cal.com/v2/bookings/{$bookingId}" &&
                   $request->method() === 'DELETE';
        });
    }

    /** @test */
    public function it_can_get_available_slots()
    {
        // Arrange
        $date = '2025-09-05';
        $eventTypeId = 123;
        $slotsResponse = [
            'slots' => [
                '2025-09-05T09:00:00Z',
                '2025-09-05T10:00:00Z',
                '2025-09-05T11:00:00Z'
            ]
        ];

        Http::fake([
            "https://api.cal.com/v2/slots*" => Http::response($slotsResponse, 200)
        ]);

        // Act
        $result = $this->service->getAvailableSlots($eventTypeId, $date);

        // Assert
        $this->assertEquals($slotsResponse, $result);
        $this->assertCount(3, $result['slots']);
    }

    /** @test */
    public function it_handles_rate_limiting_gracefully()
    {
        // Arrange
        $eventTypeId = 123;
        
        Http::fake([
            "https://api.cal.com/v2/event-types/{$eventTypeId}" => Http::sequence()
                ->push('', 429, ['Retry-After' => '1']) // Rate limited
                ->push(['event_type' => ['id' => $eventTypeId]], 200) // Success after retry
        ]);

        // Act
        $result = $this->service->getEventType($eventTypeId);

        // Assert
        $this->assertArrayHasKey('event_type', $result);
        Http::assertSentCount(2);
    }
}