<?php

namespace Tests\Unit\Services\Retell;

use Tests\TestCase;
use App\Services\Retell\WebhookResponseService;

/**
 * Unit Tests for WebhookResponseService
 *
 * Verifies response formatting, HTTP status codes, and JSON structure
 */
class WebhookResponseServiceTest extends TestCase
{
    private WebhookResponseService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new WebhookResponseService();
    }

    /** @test */
    public function it_creates_success_response_with_data()
    {
        $data = ['user' => 'John', 'status' => 'active'];

        $response = $this->service->success($data);

        $this->assertEquals(200, $response->getStatusCode());

        $json = json_decode($response->getContent(), true);
        $this->assertTrue($json['success']);
        $this->assertEquals($data, $json['data']);
        $this->assertArrayNotHasKey('message', $json);
    }

    /** @test */
    public function it_creates_success_response_with_message()
    {
        $data = ['count' => 5];
        $message = 'Operation completed successfully';

        $response = $this->service->success($data, $message);

        $json = json_decode($response->getContent(), true);
        $this->assertTrue($json['success']);
        $this->assertEquals($data, $json['data']);
        $this->assertEquals($message, $json['message']);
    }

    /** @test */
    public function it_creates_error_response_always_with_http_200()
    {
        $message = 'Service nicht verfügbar';

        $response = $this->service->error($message);

        // IMPORTANT: Always HTTP 200 to not break Retell calls
        $this->assertEquals(200, $response->getStatusCode());

        $json = json_decode($response->getContent(), true);
        $this->assertFalse($json['success']);
        $this->assertEquals($message, $json['error']);
    }

    /** @test */
    public function it_creates_error_response_with_context_logging()
    {
        $message = 'Database error';
        $context = ['query' => 'SELECT * FROM users', 'code' => 500];

        $response = $this->service->error($message, $context);

        $this->assertEquals(200, $response->getStatusCode());
        $json = json_decode($response->getContent(), true);
        $this->assertFalse($json['success']);
        // Context is logged, not exposed in response
        $this->assertArrayNotHasKey('context', $json);
    }

    /** @test */
    public function it_creates_webhook_success_response()
    {
        $event = 'call_started';
        $data = ['call_id' => '12345', 'duration' => 0];

        $response = $this->service->webhookSuccess($event, $data);

        $this->assertEquals(200, $response->getStatusCode());

        $json = json_decode($response->getContent(), true);
        $this->assertTrue($json['success']);
        $this->assertEquals($event, $json['event']);
        $this->assertEquals($data, $json['data']);
        $this->assertStringContainsString('processed successfully', $json['message']);
    }

    /** @test */
    public function it_creates_webhook_success_without_data()
    {
        $event = 'call_ended';

        $response = $this->service->webhookSuccess($event);

        $json = json_decode($response->getContent(), true);
        $this->assertTrue($json['success']);
        $this->assertEquals($event, $json['event']);
        $this->assertArrayNotHasKey('data', $json);
    }

    /** @test */
    public function it_creates_validation_error_response()
    {
        $field = 'phone_number';
        $message = 'Phone number is required';

        $response = $this->service->validationError($field, $message);

        $this->assertEquals(400, $response->getStatusCode());

        $json = json_decode($response->getContent(), true);
        $this->assertFalse($json['success']);
        $this->assertEquals($message, $json['error']);
        $this->assertEquals($field, $json['field']);
        $this->assertEquals('validation_error', $json['type']);
    }

    /** @test */
    public function it_creates_not_found_response()
    {
        $resource = 'phone_number';
        $message = 'Phone number not registered in system';

        $response = $this->service->notFound($resource, $message);

        $this->assertEquals(404, $response->getStatusCode());

        $json = json_decode($response->getContent(), true);
        $this->assertFalse($json['success']);
        $this->assertEquals($message, $json['error']);
        $this->assertEquals($resource, $json['resource']);
        $this->assertEquals('not_found', $json['type']);
    }

    /** @test */
    public function it_creates_server_error_response()
    {
        $exception = new \Exception('Database connection failed');
        $context = ['database' => 'mysql', 'host' => 'localhost'];

        $response = $this->service->serverError($exception, $context);

        $this->assertEquals(500, $response->getStatusCode());

        $json = json_decode($response->getContent(), true);
        $this->assertFalse($json['success']);
        $this->assertEquals('Internal server error occurred', $json['error']);
        $this->assertEquals('server_error', $json['type']);
    }

    /** @test */
    public function it_includes_debug_info_in_server_error_when_debug_enabled()
    {
        config(['app.debug' => true]);

        $exception = new \Exception('Specific error details');
        $response = $this->service->serverError($exception);

        $json = json_decode($response->getContent(), true);
        $this->assertEquals('Specific error details', $json['debug']);
    }

    /** @test */
    public function it_hides_debug_info_in_server_error_when_debug_disabled()
    {
        config(['app.debug' => false]);

        $exception = new \Exception('Specific error details');
        $response = $this->service->serverError($exception);

        $json = json_decode($response->getContent(), true);
        $this->assertNull($json['debug']);
    }

    /** @test */
    public function it_creates_availability_response_with_slots()
    {
        $slots = ['09:00', '10:00', '11:00', '14:00'];
        $date = '2025-10-01';

        $response = $this->service->availability($slots, $date);

        $this->assertEquals(200, $response->getStatusCode());

        $json = json_decode($response->getContent(), true);
        $this->assertTrue($json['success']);
        $this->assertTrue($json['available']);
        $this->assertEquals($date, $json['date']);
        $this->assertEquals($slots, $json['slots']);
        $this->assertEquals(4, $json['count']);
        $this->assertStringContainsString('4 Termine verfügbar', $json['message']);
    }

    /** @test */
    public function it_creates_availability_response_without_slots()
    {
        $slots = [];
        $date = '2025-10-01';

        $response = $this->service->availability($slots, $date);

        $json = json_decode($response->getContent(), true);
        $this->assertTrue($json['success']);
        $this->assertFalse($json['available']);
        $this->assertEquals($date, $json['date']);
        $this->assertEquals([], $json['slots']);
        $this->assertEquals(0, $json['count']);
        $this->assertStringContainsString('Keine Termine verfügbar', $json['message']);
    }

    /** @test */
    public function it_creates_booking_confirmed_response()
    {
        $booking = [
            'id' => 123,
            'service_id' => 45,
            'time' => '2025-10-01 14:00:00',
            'customer_name' => 'John Doe',
            'service_name' => 'Beratung'
        ];

        $response = $this->service->bookingConfirmed($booking);

        $this->assertEquals(200, $response->getStatusCode());

        $json = json_decode($response->getContent(), true);
        $this->assertTrue($json['success']);
        $this->assertTrue($json['booked']);
        $this->assertEquals($booking, $json['booking']);
        $this->assertEquals('Termin erfolgreich gebucht', $json['message']);
        $this->assertTrue($json['confirmation']);
    }

    /** @test */
    public function it_creates_call_tracking_response_with_custom_data()
    {
        $callData = [
            'call_id' => 'retell_12345',
            'status' => 'ongoing'
        ];
        $customData = [
            'verfuegbare_termine_heute' => ['09:00', '10:00'],
            'verfuegbare_termine_morgen' => ['11:00', '14:00'],
            'naechster_freier_termin' => '09:00'
        ];

        $response = $this->service->callTracking($callData, $customData);

        $this->assertEquals(200, $response->getStatusCode());

        $json = json_decode($response->getContent(), true);
        $this->assertTrue($json['success']);
        $this->assertTrue($json['tracking']);
        $this->assertEquals('retell_12345', $json['call_id']);
        $this->assertEquals('ongoing', $json['status']);
        $this->assertEquals($customData, $json['custom_data']);
    }

    /** @test */
    public function it_creates_call_tracking_response_without_custom_data()
    {
        $callData = [
            'call_id' => 'retell_67890',
            'status' => 'completed'
        ];

        $response = $this->service->callTracking($callData);

        $json = json_decode($response->getContent(), true);
        $this->assertTrue($json['success']);
        $this->assertTrue($json['tracking']);
        $this->assertArrayNotHasKey('custom_data', $json);
    }

    /** @test */
    public function it_includes_response_data_in_call_tracking_when_provided()
    {
        $callData = [
            'call_id' => 'retell_11111',
            'status' => 'ongoing',
            'response_data' => [
                'available_appointments' => true,
                'booking_enabled' => true
            ]
        ];

        $response = $this->service->callTracking($callData);

        $json = json_decode($response->getContent(), true);
        $this->assertEquals($callData['response_data'], $json['response_data']);
    }

    /** @test */
    public function it_formats_event_name_in_webhook_success_message()
    {
        $response1 = $this->service->webhookSuccess('call_started');
        $json1 = json_decode($response1->getContent(), true);
        $this->assertStringContainsString('Call started', $json1['message']);

        $response2 = $this->service->webhookSuccess('call_ended');
        $json2 = json_decode($response2->getContent(), true);
        $this->assertStringContainsString('Call ended', $json2['message']);

        $response3 = $this->service->webhookSuccess('call_analyzed');
        $json3 = json_decode($response3->getContent(), true);
        $this->assertStringContainsString('Call analyzed', $json3['message']);
    }
}