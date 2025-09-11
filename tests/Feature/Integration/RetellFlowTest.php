<?php

namespace Tests\Feature\Integration;

use Tests\TestCase;
use App\Models\Tenant;
use App\Models\Customer;
use App\Models\Call;
use App\Models\Appointment;
use App\Jobs\ProcessRetellCallJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

class RetellFlowTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private string $webhookSecret = 'test-webhook-secret';

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->tenant = Tenant::factory()->create();
        config([
            'services.retell.webhook_secret' => $this->webhookSecret,
            'services.calcom.base_url' => 'https://api.cal.com/v2',
            'services.calcom.api_key' => 'test-api-key'
        ]);
        
        Queue::fake();
    }

    /** @test */
    public function it_processes_complete_call_to_appointment_flow()
    {
        // Arrange
        Http::fake([
            'https://api.cal.com/v2/event-types/123' => Http::response([
                'event_type' => [
                    'id' => 123,
                    'length' => 60,
                    'title' => 'Consultation'
                ]
            ], 200),
            'https://api.cal.com/v2/bookings' => Http::response([
                'booking' => [
                    'id' => 789,
                    'uid' => 'booking-uid-789',
                    'startTime' => '2025-09-06T14:00:00Z',
                    'endTime' => '2025-09-06T15:00:00Z',
                    'status' => 'ACCEPTED'
                ]
            ], 201)
        ]);

        $webhookPayload = [
            'event' => 'call_ended',
            'data' => [
                'call_id' => 'retell_call_12345',
                'conversation_id' => 'conv_67890',
                'from_number' => '+491234567890',
                'to_number' => '+491234567891',
                'start_timestamp' => 1699123456,
                'end_timestamp' => 1699123556,
                'duration_sec' => 100,
                'call_successful' => true,
                'transcript' => 'Hello, my name is Sarah Johnson and my email is sarah@example.com. I would like to book an appointment for next Tuesday at 2 PM for a consultation.',
                'call_analysis' => [
                    'intent' => 'appointment_booking',
                    'sentiment' => 'positive',
                    'confidence' => 0.95,
                    'entities' => [
                        'customer_name' => 'Sarah Johnson',
                        'customer_email' => 'sarah@example.com',
                        'preferred_date' => '2025-09-06',
                        'preferred_time' => '14:00',
                        'service_type' => 'consultation'
                    ]
                ]
            ]
        ];

        $payloadJson = json_encode($webhookPayload);
        $signature = hash_hmac('sha256', $payloadJson, $this->webhookSecret);

        // Act - Process webhook
        $response = $this->withHeaders([
            'X-Retell-Signature' => $signature,
            'Content-Type' => 'application/json'
        ])->post('/api/webhooks/retell', $webhookPayload);

        // Process the job manually for integration test
        $job = new ProcessRetellCallJob($webhookPayload['data'], $this->tenant);
        $job->handle();

        // Assert webhook response
        $response->assertSuccessful();

        // Assert call was created
        $call = Call::where('call_id', 'retell_call_12345')->first();
        $this->assertNotNull($call);
        $this->assertEquals($this->tenant->id, $call->tenant_id);
        $this->assertEquals('appointment_booking', $call->analysis['intent']);
        $this->assertTrue($call->call_successful);

        // Assert customer was created or found
        $customer = Customer::where('email', 'sarah@example.com')->first();
        $this->assertNotNull($customer);
        $this->assertEquals('Sarah Johnson', $customer->name);
        $this->assertEquals('+491234567890', $customer->phone);
        $this->assertEquals($this->tenant->id, $customer->tenant_id);

        // Assert appointment was created
        $appointment = Appointment::where('customer_id', $customer->id)->first();
        $this->assertNotNull($appointment);
        $this->assertEquals($call->id, $appointment->call_id);
        $this->assertEquals('scheduled', $appointment->status);
        $this->assertEquals($this->tenant->id, $appointment->tenant_id);

        // Verify API calls were made
        Http::assertSentCount(2); // Event type + booking creation
    }

    /** @test */
    public function it_handles_information_request_call_without_booking()
    {
        // Arrange
        $webhookPayload = [
            'event' => 'call_ended',
            'data' => [
                'call_id' => 'retell_info_call_123',
                'conversation_id' => 'conv_info_456',
                'from_number' => '+491234567890',
                'to_number' => '+491234567891',
                'start_timestamp' => 1699123456,
                'end_timestamp' => 1699123556,
                'duration_sec' => 45,
                'call_successful' => true,
                'transcript' => 'Hello, what are your opening hours and what services do you offer?',
                'call_analysis' => [
                    'intent' => 'information_request',
                    'sentiment' => 'neutral',
                    'confidence' => 0.92,
                    'topics' => ['opening_hours', 'services']
                ]
            ]
        ];

        $payloadJson = json_encode($webhookPayload);
        $signature = hash_hmac('sha256', $payloadJson, $this->webhookSecret);

        // Act
        $response = $this->withHeaders([
            'X-Retell-Signature' => $signature,
            'Content-Type' => 'application/json'
        ])->post('/api/webhooks/retell', $webhookPayload);

        // Process job
        $job = new ProcessRetellCallJob($webhookPayload['data'], $this->tenant);
        $job->handle();

        // Assert
        $response->assertSuccessful();

        // Call should be created
        $call = Call::where('call_id', 'retell_info_call_123')->first();
        $this->assertNotNull($call);
        $this->assertEquals('information_request', $call->analysis['intent']);

        // No appointment should be created
        $appointmentCount = Appointment::where('tenant_id', $this->tenant->id)->count();
        $this->assertEquals(0, $appointmentCount);

        // No Cal.com API calls should be made
        Http::assertNothingSent();
    }

    /** @test */
    public function it_handles_failed_call_gracefully()
    {
        // Arrange
        $webhookPayload = [
            'event' => 'call_ended',
            'data' => [
                'call_id' => 'retell_failed_call_123',
                'conversation_id' => 'conv_failed_456',
                'from_number' => '+491234567890',
                'to_number' => '+491234567891',
                'start_timestamp' => 1699123456,
                'end_timestamp' => 1699123466, // Very short call
                'duration_sec' => 10,
                'call_successful' => false,
                'disconnect_reason' => 'caller_hung_up',
                'transcript' => 'Hello... [call ended]',
                'call_analysis' => [
                    'intent' => 'unknown',
                    'sentiment' => 'neutral',
                    'confidence' => 0.1
                ]
            ]
        ];

        $payloadJson = json_encode($webhookPayload);
        $signature = hash_hmac('sha256', $payloadJson, $this->webhookSecret);

        // Act
        $response = $this->withHeaders([
            'X-Retell-Signature' => $signature,
            'Content-Type' => 'application/json'
        ])->post('/api/webhooks/retell', $webhookPayload);

        // Process job
        $job = new ProcessRetellCallJob($webhookPayload['data'], $this->tenant);
        $job->handle();

        // Assert
        $response->assertSuccessful();

        // Call should be created
        $call = Call::where('call_id', 'retell_failed_call_123')->first();
        $this->assertNotNull($call);
        $this->assertFalse($call->call_successful);
        $this->assertEquals('caller_hung_up', $call->disconnect_reason);

        // No appointment should be created for failed calls
        $appointmentCount = Appointment::where('tenant_id', $this->tenant->id)->count();
        $this->assertEquals(0, $appointmentCount);
    }

    /** @test */
    public function it_updates_existing_customer_from_call_data()
    {
        // Arrange
        $existingCustomer = Customer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'phone' => '+491234567890',
            'name' => 'Old Name',
            'email' => 'old@example.com'
        ]);

        $webhookPayload = [
            'event' => 'call_ended',
            'data' => [
                'call_id' => 'retell_update_call_123',
                'conversation_id' => 'conv_update_456',
                'from_number' => '+491234567890', // Same phone number
                'to_number' => '+491234567891',
                'start_timestamp' => 1699123456,
                'end_timestamp' => 1699123556,
                'duration_sec' => 100,
                'call_successful' => true,
                'transcript' => 'Hi, this is John Updated, my email is updated@example.com',
                'call_analysis' => [
                    'intent' => 'information_request',
                    'sentiment' => 'positive',
                    'entities' => [
                        'customer_name' => 'John Updated',
                        'customer_email' => 'updated@example.com'
                    ]
                ]
            ]
        ];

        $payloadJson = json_encode($webhookPayload);
        $signature = hash_hmac('sha256', $payloadJson, $this->webhookSecret);

        // Act
        $response = $this->withHeaders([
            'X-Retell-Signature' => $signature,
            'Content-Type' => 'application/json'
        ])->post('/api/webhooks/retell', $webhookPayload);

        // Process job
        $job = new ProcessRetellCallJob($webhookPayload['data'], $this->tenant);
        $job->handle();

        // Assert
        $response->assertSuccessful();

        // Customer should be updated, not duplicated
        $customerCount = Customer::where('tenant_id', $this->tenant->id)->count();
        $this->assertEquals(1, $customerCount);

        $updatedCustomer = Customer::where('phone', '+491234567890')->first();
        $this->assertEquals('John Updated', $updatedCustomer->name);
        $this->assertEquals('updated@example.com', $updatedCustomer->email);
        $this->assertEquals($existingCustomer->id, $updatedCustomer->id);
    }

    /** @test */
    public function it_handles_cal_com_api_failure_during_booking()
    {
        // Arrange
        Http::fake([
            'https://api.cal.com/v2/event-types/123' => Http::response([
                'event_type' => [
                    'id' => 123,
                    'length' => 60,
                    'title' => 'Consultation'
                ]
            ], 200),
            'https://api.cal.com/v2/bookings' => Http::response([
                'error' => 'Booking slot not available'
            ], 422)
        ]);

        $webhookPayload = [
            'event' => 'call_ended',
            'data' => [
                'call_id' => 'retell_booking_fail_123',
                'conversation_id' => 'conv_fail_456',
                'from_number' => '+491234567890',
                'to_number' => '+491234567891',
                'start_timestamp' => 1699123456,
                'end_timestamp' => 1699123556,
                'duration_sec' => 100,
                'call_successful' => true,
                'transcript' => 'I would like to book an appointment',
                'call_analysis' => [
                    'intent' => 'appointment_booking',
                    'sentiment' => 'positive',
                    'entities' => [
                        'customer_name' => 'Test Customer',
                        'customer_email' => 'test@example.com'
                    ]
                ]
            ]
        ];

        $payloadJson = json_encode($webhookPayload);
        $signature = hash_hmac('sha256', $payloadJson, $this->webhookSecret);

        // Act
        $response = $this->withHeaders([
            'X-Retell-Signature' => $signature,
            'Content-Type' => 'application/json'
        ])->post('/api/webhooks/retell', $webhookPayload);

        // Process job (should handle Cal.com error gracefully)
        $job = new ProcessRetellCallJob($webhookPayload['data'], $this->tenant);
        $job->handle();

        // Assert
        $response->assertSuccessful();

        // Call should still be created
        $call = Call::where('call_id', 'retell_booking_fail_123')->first();
        $this->assertNotNull($call);
        $this->assertEquals('appointment_booking', $call->analysis['intent']);

        // Customer should still be created
        $customer = Customer::where('email', 'test@example.com')->first();
        $this->assertNotNull($customer);

        // No appointment should be created due to Cal.com failure
        $appointmentCount = Appointment::where('tenant_id', $this->tenant->id)->count();
        $this->assertEquals(0, $appointmentCount);

        // Call should be marked with booking failure in notes/analysis
        $this->assertArrayHasKey('booking_error', $call->fresh()->analysis);
    }

    /** @test */
    public function it_handles_multiple_concurrent_calls()
    {
        // Arrange
        $webhookPayloads = [];
        for ($i = 1; $i <= 5; $i++) {
            $webhookPayloads[] = [
                'event' => 'call_ended',
                'data' => [
                    'call_id' => "retell_concurrent_call_{$i}",
                    'conversation_id' => "conv_concurrent_{$i}",
                    'from_number' => "+4912345678{$i}0",
                    'to_number' => '+491234567891',
                    'start_timestamp' => 1699123456 + $i,
                    'end_timestamp' => 1699123556 + $i,
                    'duration_sec' => 60 + $i,
                    'call_successful' => true,
                    'transcript' => "This is concurrent call number {$i}",
                    'call_analysis' => [
                        'intent' => 'information_request',
                        'sentiment' => 'neutral'
                    ]
                ]
            ];
        }

        // Act - Send all webhooks
        foreach ($webhookPayloads as $payload) {
            $payloadJson = json_encode($payload);
            $signature = hash_hmac('sha256', $payloadJson, $this->webhookSecret);

            $response = $this->withHeaders([
                'X-Retell-Signature' => $signature,
                'Content-Type' => 'application/json'
            ])->post('/api/webhooks/retell', $payload);

            $response->assertSuccessful();

            // Process job
            $job = new ProcessRetellCallJob($payload['data'], $this->tenant);
            $job->handle();
        }

        // Assert
        $callCount = Call::where('tenant_id', $this->tenant->id)->count();
        $this->assertEquals(5, $callCount);

        // Each call should have unique call_id
        for ($i = 1; $i <= 5; $i++) {
            $this->assertDatabaseHas('calls', [
                'tenant_id' => $this->tenant->id,
                'call_id' => "retell_concurrent_call_{$i}",
                'from_number' => "+4912345678{$i}0"
            ]);
        }
    }

    /** @test */
    public function it_processes_call_with_complex_analysis_data()
    {
        // Arrange
        $webhookPayload = [
            'event' => 'call_ended',
            'data' => [
                'call_id' => 'retell_complex_call_123',
                'conversation_id' => 'conv_complex_456',
                'from_number' => '+491234567890',
                'to_number' => '+491234567891',
                'start_timestamp' => 1699123456,
                'end_timestamp' => 1699123556,
                'duration_sec' => 100,
                'call_successful' => true,
                'transcript' => 'Complex conversation with multiple intents and entities',
                'call_analysis' => [
                    'intent' => 'appointment_booking',
                    'sentiment' => 'positive',
                    'confidence' => 0.95,
                    'entities' => [
                        'customer_name' => 'Complex Customer',
                        'customer_email' => 'complex@example.com',
                        'preferred_date' => '2025-09-10',
                        'preferred_time' => '15:30',
                        'service_type' => 'consultation',
                        'urgency' => 'high',
                        'symptoms' => 'headache, dizziness'
                    ],
                    'topics' => ['health', 'appointment', 'urgent'],
                    'emotion_scores' => [
                        'anxiety' => 0.3,
                        'satisfaction' => 0.7,
                        'frustration' => 0.1
                    ],
                    'call_quality' => [
                        'audio_quality' => 0.95,
                        'transcript_confidence' => 0.98,
                        'background_noise' => 0.05
                    ]
                ]
            ]
        ];

        $payloadJson = json_encode($webhookPayload);
        $signature = hash_hmac('sha256', $payloadJson, $this->webhookSecret);

        // Act
        $response = $this->withHeaders([
            'X-Retell-Signature' => $signature,
            'Content-Type' => 'application/json'
        ])->post('/api/webhooks/retell', $webhookPayload);

        // Process job
        $job = new ProcessRetellCallJob($webhookPayload['data'], $this->tenant);
        $job->handle();

        // Assert
        $response->assertSuccessful();

        $call = Call::where('call_id', 'retell_complex_call_123')->first();
        $this->assertNotNull($call);
        
        // Verify complex analysis data is stored correctly
        $this->assertEquals('appointment_booking', $call->analysis['intent']);
        $this->assertEquals(0.95, $call->analysis['confidence']);
        $this->assertEquals('Complex Customer', $call->analysis['entities']['customer_name']);
        $this->assertEquals('high', $call->analysis['entities']['urgency']);
        $this->assertIsArray($call->analysis['topics']);
        $this->assertIsArray($call->analysis['emotion_scores']);
        $this->assertEquals(0.95, $call->analysis['call_quality']['audio_quality']);

        // Customer should include extracted information
        $customer = Customer::where('email', 'complex@example.com')->first();
        $this->assertNotNull($customer);
        $this->assertEquals('Complex Customer', $customer->name);
    }
}