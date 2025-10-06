<?php

namespace Tests\Feature\CalcomV2;

use Tests\TestCase;
use Tests\Mocks\CalcomV2MockServer;
use App\Services\CalcomV2Client;
use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CalcomV2ClientTest extends TestCase
{
    use RefreshDatabase;

    private CalcomV2Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        // Configure Cal.com settings
        config([
            'services.calcom.api_key' => 'test_api_key',
            'services.calcom.api_version' => '2024-08-13',
        ]);

        // Initialize mock server
        CalcomV2MockServer::setUp();

        // Create client instance
        $this->client = new CalcomV2Client();
    }

    protected function tearDown(): void
    {
        CalcomV2MockServer::reset();
        parent::tearDown();
    }

    /**
     * Test getting available slots - success scenario
     */
    public function test_get_available_slots_success()
    {
        $start = Carbon::tomorrow()->setTime(9, 0);
        $end = Carbon::tomorrow()->setTime(18, 0);

        $response = $this->client->getAvailableSlots(123, $start, $end);

        $this->assertTrue($response->successful());
        $this->assertEquals(200, $response->status());

        $data = $response->json();
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('slots', $data['data']);
        $this->assertNotEmpty($data['data']['slots']);

        // Verify slots structure
        $firstSlot = $data['data']['slots'][0];
        $this->assertArrayHasKey('start', $firstSlot);
        $this->assertArrayHasKey('end', $firstSlot);
    }

    /**
     * Test getting available slots - no availability
     */
    public function test_get_available_slots_no_availability()
    {
        CalcomV2MockServer::setScenario('no_availability');

        $start = Carbon::tomorrow()->setTime(9, 0);
        $end = Carbon::tomorrow()->setTime(18, 0);

        $response = $this->client->getAvailableSlots(123, $start, $end);

        $this->assertTrue($response->successful());
        $this->assertEmpty($response->json('data.slots'));
    }

    /**
     * Test getting available slots - error handling
     */
    public function test_get_available_slots_error()
    {
        CalcomV2MockServer::setScenario('error');

        $start = Carbon::tomorrow()->setTime(9, 0);
        $end = Carbon::tomorrow()->setTime(18, 0);

        $response = $this->client->getAvailableSlots(123, $start, $end);

        $this->assertFalse($response->successful());
        $this->assertEquals(500, $response->status());
    }

    /**
     * Test creating a booking - success scenario
     */
    public function test_create_booking_success()
    {
        $bookingData = [
            'eventTypeId' => 123,
            'start' => Carbon::tomorrow()->setTime(10, 0)->toIso8601String(),
            'end' => Carbon::tomorrow()->setTime(11, 0)->toIso8601String(),
            'timeZone' => 'Europe/Berlin',
            'name' => 'Test Customer',
            'email' => 'test@example.com',
            'metadata' => ['test_key' => 'test_value']
        ];

        $response = $this->client->createBooking($bookingData);

        $this->assertTrue($response->successful());
        $this->assertEquals(201, $response->status());

        $data = $response->json('data');
        $this->assertNotNull($data);
        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('uid', $data);
        $this->assertEquals('ACCEPTED', $data['status']);
        $this->assertEquals($bookingData['eventTypeId'], $data['eventTypeId']);
    }

    /**
     * Test creating a booking - conflict scenario
     */
    public function test_create_booking_conflict()
    {
        CalcomV2MockServer::addScenario('createBooking', 'conflict');

        $bookingData = [
            'eventTypeId' => 123,
            'start' => Carbon::tomorrow()->setTime(10, 0)->toIso8601String(),
            'end' => Carbon::tomorrow()->setTime(11, 0)->toIso8601String(),
            'timeZone' => 'Europe/Berlin',
            'name' => 'Test Customer',
            'email' => 'test@example.com'
        ];

        $response = $this->client->createBooking($bookingData);

        $this->assertFalse($response->successful());
        $this->assertEquals(409, $response->status());
        $this->assertEquals('SLOT_UNAVAILABLE', $response->json('code'));
    }

    /**
     * Test rescheduling a booking - success
     */
    public function test_reschedule_booking_success()
    {
        // First create a booking
        $createData = [
            'eventTypeId' => 123,
            'start' => Carbon::tomorrow()->setTime(10, 0)->toIso8601String(),
            'end' => Carbon::tomorrow()->setTime(11, 0)->toIso8601String(),
            'timeZone' => 'Europe/Berlin',
            'name' => 'Test Customer',
            'email' => 'test@example.com'
        ];

        $createResponse = $this->client->createBooking($createData);
        $bookingId = $createResponse->json('data.id');

        // Now reschedule it
        $rescheduleData = [
            'start' => Carbon::tomorrow()->setTime(14, 0)->toIso8601String(),
            'end' => Carbon::tomorrow()->setTime(15, 0)->toIso8601String(),
            'timeZone' => 'Europe/Berlin',
            'reason' => 'Customer requested new time'
        ];

        $response = $this->client->rescheduleBooking($bookingId, $rescheduleData);

        $this->assertTrue($response->successful());
        $this->assertEquals(200, $response->status());

        $data = $response->json('data');
        $this->assertTrue($data['rescheduled']);
        $this->assertEquals($rescheduleData['start'], $data['start']);
        $this->assertEquals($rescheduleData['end'], $data['end']);
    }

    /**
     * Test rescheduling a booking - not found
     */
    public function test_reschedule_booking_not_found()
    {
        CalcomV2MockServer::addScenario('rescheduleBooking', 'not_found');

        $rescheduleData = [
            'start' => Carbon::tomorrow()->setTime(14, 0)->toIso8601String(),
            'end' => Carbon::tomorrow()->setTime(15, 0)->toIso8601String(),
            'timeZone' => 'Europe/Berlin'
        ];

        $response = $this->client->rescheduleBooking(99999, $rescheduleData);

        $this->assertFalse($response->successful());
        $this->assertEquals(404, $response->status());
    }

    /**
     * Test cancelling a booking - success
     */
    public function test_cancel_booking_success()
    {
        // First create a booking
        $createData = [
            'eventTypeId' => 123,
            'start' => Carbon::tomorrow()->setTime(10, 0)->toIso8601String(),
            'end' => Carbon::tomorrow()->setTime(11, 0)->toIso8601String(),
            'timeZone' => 'Europe/Berlin',
            'name' => 'Test Customer',
            'email' => 'test@example.com'
        ];

        $createResponse = $this->client->createBooking($createData);
        $bookingId = $createResponse->json('data.id');

        // Now cancel it
        $response = $this->client->cancelBooking($bookingId, 'Customer changed plans');

        $this->assertTrue($response->successful());
        $this->assertEquals(200, $response->status());
    }

    /**
     * Test cancelling a booking - not found
     */
    public function test_cancel_booking_not_found()
    {
        CalcomV2MockServer::addScenario('cancelBooking', 'not_found');

        $response = $this->client->cancelBooking(99999, 'Test cancellation');

        $this->assertFalse($response->successful());
        $this->assertEquals(404, $response->status());
    }

    /**
     * Test reserving a slot - success
     */
    public function test_reserve_slot_success()
    {
        $response = $this->client->reserveSlot(
            123,
            Carbon::tomorrow()->setTime(10, 0)->toIso8601String(),
            Carbon::tomorrow()->setTime(11, 0)->toIso8601String()
        );

        $this->assertTrue($response->successful());
        $this->assertEquals(201, $response->status());

        $data = $response->json('data');
        $this->assertArrayHasKey('reservationId', $data);
        $this->assertArrayHasKey('expiresAt', $data);
    }

    /**
     * Test reserving a slot - unavailable
     */
    public function test_reserve_slot_unavailable()
    {
        CalcomV2MockServer::addScenario('reserveSlot', 'unavailable');

        $response = $this->client->reserveSlot(
            123,
            Carbon::tomorrow()->setTime(10, 0)->toIso8601String(),
            Carbon::tomorrow()->setTime(11, 0)->toIso8601String()
        );

        $this->assertFalse($response->successful());
        $this->assertEquals(409, $response->status());
        $this->assertEquals('SLOT_UNAVAILABLE', $response->json('code'));
    }

    /**
     * Test releasing a slot reservation
     */
    public function test_release_slot_success()
    {
        // First reserve a slot
        $reserveResponse = $this->client->reserveSlot(
            123,
            Carbon::tomorrow()->setTime(10, 0)->toIso8601String(),
            Carbon::tomorrow()->setTime(11, 0)->toIso8601String()
        );

        $reservationId = $reserveResponse->json('data.reservationId');

        // Now release it
        $response = $this->client->releaseSlot($reservationId);

        $this->assertTrue($response->successful());
        $this->assertEquals(200, $response->status());
    }

    /**
     * Test creating an event type
     */
    public function test_create_event_type_success()
    {
        $eventTypeData = [
            'name' => 'Test Service',
            'description' => 'Test service description',
            'duration' => 60
        ];

        $response = $this->client->createEventType($eventTypeData);

        $this->assertTrue($response->successful());
        $this->assertEquals(201, $response->status());

        $data = $response->json('data');
        $this->assertArrayHasKey('id', $data);
        $this->assertEquals('Test Service', $data['title']);
        $this->assertEquals(60, $data['lengthInMinutes']);
        $this->assertTrue($data['hidden']); // Should be hidden by default
    }

    /**
     * Test getting event types
     */
    public function test_get_event_types_success()
    {
        // First create an event type
        $this->client->createEventType([
            'name' => 'Test Service',
            'duration' => 60
        ]);

        $response = $this->client->getEventTypes();

        $this->assertTrue($response->successful());
        $this->assertEquals(200, $response->status());
        $this->assertIsArray($response->json('data'));
    }

    /**
     * Test registering a webhook
     */
    public function test_register_webhook_success()
    {
        $response = $this->client->registerWebhook(
            'https://example.com/webhook',
            ['booking.created', 'booking.cancelled']
        );

        $this->assertTrue($response->successful());
        $this->assertEquals(201, $response->status());

        $data = $response->json('data');
        $this->assertArrayHasKey('id', $data);
        $this->assertEquals('https://example.com/webhook', $data['subscriberUrl']);
        $this->assertTrue($data['active']);
    }

    /**
     * Test using company-specific API key
     */
    public function test_company_specific_api_key()
    {
        $company = Company::factory()->create([
            'calcom_v2_api_key' => 'company_specific_key'
        ]);

        $clientWithCompany = new CalcomV2Client($company);

        // Test that requests still work with company-specific key
        $response = $clientWithCompany->getEventTypes();

        $this->assertTrue($response->successful());
        $this->assertEquals(200, $response->status());
    }

    /**
     * Test timezone handling in requests
     */
    public function test_timezone_handling()
    {
        $timezones = ['Europe/Berlin', 'America/New_York', 'Asia/Tokyo'];

        foreach ($timezones as $timezone) {
            $response = $this->client->getAvailableSlots(
                123,
                Carbon::now()->setTimezone($timezone),
                Carbon::now()->setTimezone($timezone)->addDay(),
                $timezone
            );

            $this->assertTrue($response->successful());
        }
    }

    /**
     * Test date format handling
     */
    public function test_date_format_handling()
    {
        $start = Carbon::parse('2025-01-15 10:00:00');
        $end = Carbon::parse('2025-01-15 11:00:00');

        $bookingData = [
            'eventTypeId' => 123,
            'start' => $start->toIso8601String(),
            'end' => $end->toIso8601String(),
            'timeZone' => 'Europe/Berlin',
            'name' => 'Test Customer',
            'email' => 'test@example.com'
        ];

        $response = $this->client->createBooking($bookingData);

        $this->assertTrue($response->successful());

        $createdStart = $response->json('data.start');
        $this->assertStringContainsString('2025-01-15', $createdStart);
    }
}