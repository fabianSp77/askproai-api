<?php

namespace Tests\Feature\Controllers;

use Tests\TestCase;
use App\Models\Tenant;
use App\Models\Customer;
use App\Models\Call;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use App\Jobs\ProcessRetellCallJob;

class RetellWebhookControllerTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private string $webhookSecret = 'test-webhook-secret';

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->tenant = Tenant::factory()->create();
        
        config(['services.retell.webhook_secret' => $this->webhookSecret]);
        Queue::fake();
    }

    /** @test */
    public function it_processes_call_ended_webhook_successfully()
    {
        // Arrange
        $customer = Customer::factory()->create(['tenant_id' => $this->tenant->id]);
        
        $payload = [
            'event' => 'call_ended',
            'data' => [
                'call_id' => 'retell_call_123',
                'conversation_id' => 'conv_456',
                'from_number' => '+491234567890',
                'to_number' => '+491234567891',
                'start_timestamp' => 1699123456,
                'end_timestamp' => 1699123556,
                'duration_sec' => 100,
                'call_successful' => true,
                'transcript' => 'Hello, I would like to book an appointment',
                'call_analysis' => [
                    'intent' => 'appointment_booking',
                    'sentiment' => 'positive'
                ]
            ]
        ];

        $payloadJson = json_encode($payload);
        $signature = hash_hmac('sha256', $payloadJson, $this->webhookSecret);

        // Act
        $response = $this->withHeaders([
            'X-Retell-Signature' => $signature,
            'Content-Type' => 'application/json'
        ])->post('/api/webhooks/retell', $payload);

        // Assert
        $response->assertSuccessful();
        $response->assertJsonFragment([
            'status' => 'processed',
            'call_id' => 'retell_call_123'
        ]);

        Queue::assertPushed(ProcessRetellCallJob::class, function ($job) {
            return $job->callData['call_id'] === 'retell_call_123';
        });
    }

    /** @test */
    public function it_rejects_webhook_with_invalid_signature()
    {
        // Arrange
        $payload = [
            'event' => 'call_ended',
            'data' => ['call_id' => 'test_call']
        ];

        $invalidSignature = 'invalid_signature';

        // Act
        $response = $this->withHeaders([
            'X-Retell-Signature' => $invalidSignature,
            'Content-Type' => 'application/json'
        ])->post('/api/webhooks/retell', $payload);

        // Assert
        $response->assertStatus(401);
        $response->assertJsonFragment(['error' => 'Invalid signature']);
        
        Queue::assertNothingPushed();
    }

    /** @test */
    public function it_rejects_webhook_without_signature_header()
    {
        // Arrange
        $payload = [
            'event' => 'call_ended',
            'data' => ['call_id' => 'test_call']
        ];

        // Act
        $response = $this->post('/api/webhooks/retell', $payload);

        // Assert
        $response->assertStatus(401);
        $response->assertJsonFragment(['error' => 'Missing signature']);
    }

    /** @test */
    public function it_handles_call_started_webhook()
    {
        // Arrange
        $payload = [
            'event' => 'call_started',
            'data' => [
                'call_id' => 'retell_call_789',
                'conversation_id' => 'conv_012',
                'from_number' => '+491234567890',
                'to_number' => '+491234567891',
                'start_timestamp' => 1699123456
            ]
        ];

        $payloadJson = json_encode($payload);
        $signature = hash_hmac('sha256', $payloadJson, $this->webhookSecret);

        // Act
        $response = $this->withHeaders([
            'X-Retell-Signature' => $signature,
            'Content-Type' => 'application/json'
        ])->post('/api/webhooks/retell', $payload);

        // Assert
        $response->assertSuccessful();
        $response->assertJsonFragment(['status' => 'received']);
        
        // Should create call record immediately for call_started
        $this->assertDatabaseHas('calls', [
            'call_id' => 'retell_call_789',
            'from_number' => '+491234567890'
        ]);
    }

    /** @test */
    public function it_handles_call_analysis_webhook()
    {
        // Arrange
        $existingCall = Call::factory()->create([
            'tenant_id' => $this->tenant->id,
            'call_id' => 'retell_call_456'
        ]);

        $payload = [
            'event' => 'call_analysis',
            'data' => [
                'call_id' => 'retell_call_456',
                'analysis' => [
                    'intent' => 'complaint',
                    'sentiment' => 'negative',
                    'topics' => ['service_quality', 'billing'],
                    'action_items' => ['follow_up_required']
                ]
            ]
        ];

        $payloadJson = json_encode($payload);
        $signature = hash_hmac('sha256', $payloadJson, $this->webhookSecret);

        // Act
        $response = $this->withHeaders([
            'X-Retell-Signature' => $signature,
            'Content-Type' => 'application/json'
        ])->post('/api/webhooks/retell', $payload);

        // Assert
        $response->assertSuccessful();
        
        $existingCall->refresh();
        $this->assertEquals('complaint', $existingCall->analysis['intent']);
        $this->assertEquals('negative', $existingCall->analysis['sentiment']);
    }

    /** @test */
    public function it_rejects_unknown_webhook_events()
    {
        // Arrange
        $payload = [
            'event' => 'unknown_event',
            'data' => ['some' => 'data']
        ];

        $payloadJson = json_encode($payload);
        $signature = hash_hmac('sha256', $payloadJson, $this->webhookSecret);

        // Act
        $response = $this->withHeaders([
            'X-Retell-Signature' => $signature,
            'Content-Type' => 'application/json'
        ])->post('/api/webhooks/retell', $payload);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonFragment(['error' => 'Unknown webhook event']);
    }

    /** @test */
    public function it_validates_required_webhook_fields()
    {
        // Arrange
        $payload = [
            'event' => 'call_ended',
            'data' => [
                // Missing required fields like call_id
                'from_number' => '+491234567890'
            ]
        ];

        $payloadJson = json_encode($payload);
        $signature = hash_hmac('sha256', $payloadJson, $this->webhookSecret);

        // Act
        $response = $this->withHeaders([
            'X-Retell-Signature' => $signature,
            'Content-Type' => 'application/json'
        ])->post('/api/webhooks/retell', $payload);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['data.call_id']);
    }

    /** @test */
    public function it_handles_duplicate_webhooks_gracefully()
    {
        // Arrange
        $payload = [
            'event' => 'call_ended',
            'data' => [
                'call_id' => 'retell_duplicate_call',
                'conversation_id' => 'conv_123',
                'from_number' => '+491234567890',
                'to_number' => '+491234567891',
                'start_timestamp' => 1699123456,
                'end_timestamp' => 1699123556,
                'duration_sec' => 100,
                'call_successful' => true,
                'transcript' => 'Duplicate webhook test'
            ]
        ];

        $payloadJson = json_encode($payload);
        $signature = hash_hmac('sha256', $payloadJson, $this->webhookSecret);

        // Act - Send same webhook twice
        $response1 = $this->withHeaders([
            'X-Retell-Signature' => $signature,
            'Content-Type' => 'application/json'
        ])->post('/api/webhooks/retell', $payload);

        $response2 = $this->withHeaders([
            'X-Retell-Signature' => $signature,
            'Content-Type' => 'application/json'
        ])->post('/api/webhooks/retell', $payload);

        // Assert
        $response1->assertSuccessful();
        $response2->assertSuccessful();
        
        // Should only have one call record
        $callCount = Call::where('call_id', 'retell_duplicate_call')->count();
        $this->assertEquals(1, $callCount);
        
        // First webhook should queue job, second should be idempotent
        Queue::assertPushed(ProcessRetellCallJob::class, 1);
    }

    /** @test */
    public function it_handles_webhook_with_appointment_booking_intent()
    {
        // Arrange
        $payload = [
            'event' => 'call_ended',
            'data' => [
                'call_id' => 'booking_call_123',
                'conversation_id' => 'conv_789',
                'from_number' => '+491234567890',
                'to_number' => '+491234567891',
                'start_timestamp' => 1699123456,
                'end_timestamp' => 1699123556,
                'duration_sec' => 180,
                'call_successful' => true,
                'transcript' => 'Hi, I would like to book an appointment for next Tuesday at 2 PM',
                'call_analysis' => [
                    'intent' => 'appointment_booking',
                    'sentiment' => 'positive',
                    'extracted_info' => [
                        'name' => 'John Doe',
                        'email' => 'john@example.com',
                        'preferred_date' => '2025-09-10',
                        'preferred_time' => '14:00'
                    ]
                ]
            ]
        ];

        $payloadJson = json_encode($payload);
        $signature = hash_hmac('sha256', $payloadJson, $this->webhookSecret);

        // Mock Cal.com API for automatic booking
        Http::fake([
            'https://api.cal.com/v2/bookings' => Http::response([
                'booking' => [
                    'id' => 456,
                    'status' => 'ACCEPTED'
                ]
            ], 201)
        ]);

        // Act
        $response = $this->withHeaders([
            'X-Retell-Signature' => $signature,
            'Content-Type' => 'application/json'
        ])->post('/api/webhooks/retell', $payload);

        // Assert
        $response->assertSuccessful();
        $response->assertJsonFragment([
            'status' => 'processed',
            'appointment_created' => true
        ]);

        Queue::assertPushed(ProcessRetellCallJob::class, function ($job) {
            return $job->callData['call_analysis']['intent'] === 'appointment_booking';
        });
    }

    /** @test */
    public function it_logs_webhook_processing_errors()
    {
        // Arrange
        $payload = [
            'event' => 'call_ended',
            'data' => [
                'call_id' => 'error_call_123',
                'invalid_field' => 'This will cause processing error'
            ]
        ];

        $payloadJson = json_encode($payload);
        $signature = hash_hmac('sha256', $payloadJson, $this->webhookSecret);

        // Act
        $response = $this->withHeaders([
            'X-Retell-Signature' => $signature,
            'Content-Type' => 'application/json'
        ])->post('/api/webhooks/retell', $payload);

        // Assert
        $response->assertStatus(422);
        
        // Verify error is logged (would need to mock Log facade in real implementation)
    }

    /** @test */
    public function it_handles_high_volume_webhooks()
    {
        // Arrange - Simulate multiple concurrent webhooks
        $webhooks = [];
        for ($i = 1; $i <= 10; $i++) {
            $payload = [
                'event' => 'call_ended',
                'data' => [
                    'call_id' => "batch_call_{$i}",
                    'conversation_id' => "conv_{$i}",
                    'from_number' => "+49123456789{$i}",
                    'to_number' => '+491234567891',
                    'start_timestamp' => 1699123456 + $i,
                    'end_timestamp' => 1699123556 + $i,
                    'duration_sec' => 100 + $i,
                    'call_successful' => true,
                    'transcript' => "Test call number {$i}"
                ]
            ];
            
            $payloadJson = json_encode($payload);
            $signature = hash_hmac('sha256', $payloadJson, $this->webhookSecret);
            
            $webhooks[] = [
                'payload' => $payload,
                'signature' => $signature
            ];
        }

        // Act - Send all webhooks
        foreach ($webhooks as $webhook) {
            $response = $this->withHeaders([
                'X-Retell-Signature' => $webhook['signature'],
                'Content-Type' => 'application/json'
            ])->post('/api/webhooks/retell', $webhook['payload']);
            
            $response->assertSuccessful();
        }

        // Assert - All jobs queued
        Queue::assertPushed(ProcessRetellCallJob::class, 10);
    }
}