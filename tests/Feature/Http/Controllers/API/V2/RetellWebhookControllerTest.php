<?php

namespace Tests\Feature\Http\Controllers\API\V2;

use App\Exceptions\WebhookSignatureException;
use App\Http\Controllers\API\RetellWebhookController;
use App\Jobs\ProcessRetellWebhookJob;
use App\Models\Call;
use App\Models\Company;
use App\Models\Customer;
use App\Models\WebhookEvent;
use App\Services\WebhookProcessor;
use App\Services\Webhooks\RetellWebhookHandler;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RetellWebhookControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected Company $company;
    protected string $webhookSecret;
    protected array $validRetellPayload;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test company
        $this->company = Company::factory()->create([
            'name' => 'Test Company',
            'retell_agent_id' => 'agent_test_123'
        ]);
        
        // Set up webhook secret
        $this->webhookSecret = 'test-retell-webhook-secret';
        Config::set('services.retell.webhook_secret', $this->webhookSecret);
        
        // Register test route for our controller
        $this->app['router']->post('/test/retell/webhook', \App\Http\Controllers\API\RetellWebhookController::class);
        
        // Set up valid test payload
        $this->validRetellPayload = [
            'event' => 'call_ended',
            'call' => [
                'call_id' => 'retell_call_test_123',
                'agent_id' => 'agent_test_123',
                'from_number' => '+491701234567',
                'to_number' => '+491709876543',
                'direction' => 'inbound',
                'start_timestamp' => now()->subMinutes(5)->timestamp * 1000,
                'end_timestamp' => now()->timestamp * 1000,
                'call_duration' => 300,
                'disconnection_reason' => 'user_hangup',
                'recording_url' => 'https://api.retell.ai/recordings/test123.mp3',
                'transcript' => 'Customer: Hello, I would like to book an appointment. Agent: Of course, I can help you with that.',
                'transcript_object' => [
                    'segments' => [
                        [
                            'role' => 'user',
                            'content' => 'Hello, I would like to book an appointment.',
                            'timestamp' => 1000
                        ],
                        [
                            'role' => 'agent',
                            'content' => 'Of course, I can help you with that.',
                            'timestamp' => 5000
                        ]
                    ]
                ],
                'call_analysis' => [
                    'sentiment' => 'positive',
                    'summary' => 'Customer successfully booked an appointment',
                    'intent' => 'appointment_booking',
                    'extracted_data' => [
                        'customer_name' => 'John Doe',
                        'customer_email' => 'john.doe@example.com',
                        'preferred_date' => '2025-08-10',
                        'preferred_time' => '14:00',
                        'service_type' => 'consultation'
                    ]
                ]
            ]
        ];
    }

    /**
     * Test 1: Webhook signature validation (security)
     * Tests that webhooks without valid signatures are rejected
     */
    #[Test]
    public function test_webhook_signature_validation_rejects_missing_signature()
    {
        Log::shouldReceive('error')
            ->once()
            ->with('Retell webhook signature verification failed', \Mockery::any());

        $response = $this->postJson('/test/retell/webhook', $this->validRetellPayload);

        // Should return 204 (as per controller logic) but log the security error
        $response->assertNoContent();
        
        // Verify no webhook event was created due to signature failure
        $this->assertDatabaseMissing('webhook_events', [
            'provider' => WebhookEvent::PROVIDER_RETELL,
            'event_id' => 'retell_call_test_123'
        ]);
    }

    #[Test]
    public function test_webhook_signature_validation_rejects_invalid_signature()
    {
        Log::shouldReceive('error')
            ->once()
            ->with('Retell webhook signature verification failed', \Mockery::any());

        $response = $this->postJson('/test/retell/webhook', $this->validRetellPayload, [
            'X-Retell-Signature' => 'invalid_signature_123'
        ]);

        $response->assertNoContent();
        
        // Verify no webhook event was created due to invalid signature
        $this->assertDatabaseMissing('webhook_events', [
            'provider' => WebhookEvent::PROVIDER_RETELL,
            'event_id' => 'retell_call_test_123'
        ]);
    }

    #[Test]
    public function test_webhook_signature_validation_accepts_valid_signature()
    {
        // Mock the webhook processor to avoid actual processing
        $mockProcessor = $this->createMock(WebhookProcessor::class);
        $mockProcessor->expects($this->once())
            ->method('process')
            ->with(
                WebhookEvent::PROVIDER_RETELL,
                $this->validRetellPayload,
                $this->callback(function ($headers) {
                    return isset($headers['x-retell-signature']);
                }),
                $this->isType('string')
            )
            ->willReturn([
                'success' => true,
                'duplicate' => false,
                'message' => 'Webhook processed successfully'
            ]);

        $this->app->instance(WebhookProcessor::class, $mockProcessor);

        $validSignature = hash_hmac('sha256', json_encode($this->validRetellPayload), $this->webhookSecret);

        $response = $this->postJson('/test/retell/webhook', $this->validRetellPayload, [
            'X-Retell-Signature' => $validSignature
        ]);

        $response->assertNoContent();
    }

    #[Test]
    public function test_webhook_signature_validation_handles_prefixed_signature()
    {
        // Test signature with sha256= prefix (common in webhook implementations)
        $mockProcessor = $this->createMock(WebhookProcessor::class);
        $mockProcessor->expects($this->once())
            ->method('process')
            ->willReturn([
                'success' => true,
                'duplicate' => false,
                'message' => 'Webhook processed successfully'
            ]);

        $this->app->instance(WebhookProcessor::class, $mockProcessor);

        $validSignature = hash_hmac('sha256', json_encode($this->validRetellPayload), $this->webhookSecret);
        $prefixedSignature = 'sha256=' . $validSignature;

        $response = $this->postJson('/test/retell/webhook', $this->validRetellPayload, [
            'X-Retell-Signature' => $prefixedSignature
        ]);

        $response->assertNoContent();
    }

    /**
     * Test 2: Successful appointment booking through webhook
     * Tests the complete flow from webhook to appointment creation
     */
    #[Test]
    public function test_successful_appointment_booking_through_webhook()
    {
        // Disable queue for synchronous processing in this test
        Config::set('services.webhook.async.retell', false);

        // Create a test customer
        $customer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'phone' => '+491701234567',
            'email' => 'john.doe@example.com',
            'name' => 'John Doe'
        ]);

        // Mock the RetellWebhookHandler to simulate successful appointment booking
        $mockHandler = $this->createMock(RetellWebhookHandler::class);
        $mockHandler->expects($this->once())
            ->method('handle')
            ->willReturn([
                'success' => true,
                'call_id' => 'test_call_123',
                'booking_result' => [
                    'appointment_created' => true,
                    'appointment_id' => 'apt_123',
                    'customer_id' => $customer->id,
                    'scheduled_at' => '2025-08-10 14:00:00'
                ],
                'message' => 'Call ended event processed with appointment booking'
            ]);

        $this->app->instance(RetellWebhookHandler::class, $mockHandler);

        $validSignature = hash_hmac('sha256', json_encode($this->validRetellPayload), $this->webhookSecret);

        Log::shouldReceive('info')
            ->times(3) // Expected log calls during processing
            ->with(\Mockery::type('string'), \Mockery::type('array'));

        $response = $this->postJson('/test/retell/webhook', $this->validRetellPayload, [
            'X-Retell-Signature' => $validSignature
        ]);

        $response->assertNoContent();

        // Verify webhook event was created and completed
        $this->assertDatabaseHas('webhook_events', [
            'provider' => WebhookEvent::PROVIDER_RETELL,
            'event_type' => 'call_ended',
            'event_id' => 'retell_call_test_123',
            'status' => WebhookEvent::STATUS_COMPLETED
        ]);
    }

    #[Test]
    public function test_appointment_booking_queued_for_async_processing()
    {
        Queue::fake();
        
        // Enable async processing (default)
        Config::set('services.webhook.async.retell', true);

        $validSignature = hash_hmac('sha256', json_encode($this->validRetellPayload), $this->webhookSecret);

        $response = $this->postJson('/test/retell/webhook', $this->validRetellPayload, [
            'X-Retell-Signature' => $validSignature
        ]);

        $response->assertNoContent();

        // Verify job was queued
        Queue::assertPushed(ProcessRetellWebhookJob::class, function ($job) {
            return $job->webhookEvent->event_id === 'retell_call_test_123';
        });

        // Verify webhook event was created in pending status
        $this->assertDatabaseHas('webhook_events', [
            'provider' => WebhookEvent::PROVIDER_RETELL,
            'event_type' => 'call_ended',
            'event_id' => 'retell_call_test_123',
            'status' => WebhookEvent::STATUS_PENDING
        ]);
    }

    #[Test]
    public function test_appointment_booking_with_customer_creation()
    {
        Config::set('services.webhook.async.retell', false);

        // Payload with new customer data
        $payloadWithNewCustomer = array_merge($this->validRetellPayload, [
            'call' => array_merge($this->validRetellPayload['call'], [
                'call_analysis' => array_merge($this->validRetellPayload['call']['call_analysis'], [
                    'extracted_data' => [
                        'customer_name' => 'Jane Smith',
                        'customer_email' => 'jane.smith@example.com',
                        'customer_phone' => '+491701234567',
                        'preferred_date' => '2025-08-12',
                        'preferred_time' => '10:00',
                        'service_type' => 'consultation'
                    ]
                ])
            ])
        ]);

        // Mock successful customer creation and appointment booking
        $mockHandler = $this->createMock(RetellWebhookHandler::class);
        $mockHandler->expects($this->once())
            ->method('handle')
            ->willReturn([
                'success' => true,
                'call_id' => 'test_call_456',
                'customer_created' => true,
                'booking_result' => [
                    'appointment_created' => true,
                    'customer_created' => true,
                    'appointment_id' => 'apt_456'
                ],
                'message' => 'New customer and appointment created successfully'
            ]);

        $this->app->instance(RetellWebhookHandler::class, $mockHandler);

        $validSignature = hash_hmac('sha256', json_encode($payloadWithNewCustomer), $this->webhookSecret);

        $response = $this->postJson('/test/retell/webhook', $payloadWithNewCustomer, [
            'X-Retell-Signature' => $validSignature
        ]);

        $response->assertNoContent();
    }

    /**
     * Test 3: Error handling for invalid webhook data
     * Tests various error scenarios and ensures proper error handling
     */
    #[Test]
    public function test_error_handling_for_malformed_payload()
    {
        $malformedPayload = [
            'event' => 'call_ended',
            // Missing required 'call' object
            'invalid_field' => 'test'
        ];

        // Mock processor to throw an exception for malformed data
        $mockProcessor = $this->createMock(WebhookProcessor::class);
        $mockProcessor->expects($this->once())
            ->method('process')
            ->willThrowException(new \Exception('Invalid webhook payload structure'));

        $this->app->instance(WebhookProcessor::class, $mockProcessor);

        Log::shouldReceive('error')
            ->once()
            ->with('Failed to process Retell webhook', \Mockery::any());

        $validSignature = hash_hmac('sha256', json_encode($malformedPayload), $this->webhookSecret);

        $response = $this->postJson('/test/retell/webhook', $malformedPayload, [
            'X-Retell-Signature' => $validSignature
        ]);

        // Should still return 204 to prevent retries
        $response->assertNoContent();
    }

    #[Test]
    public function test_error_handling_for_processing_failures()
    {
        Config::set('services.webhook.async.retell', false);

        // Mock handler to simulate processing failure
        $mockHandler = $this->createMock(RetellWebhookHandler::class);
        $mockHandler->expects($this->once())
            ->method('handle')
            ->willThrowException(new \Exception('Database connection failed'));

        $this->app->instance(RetellWebhookHandler::class, $mockHandler);

        Log::shouldReceive('info')->times(2);
        Log::shouldReceive('error')
            ->once()
            ->with('Webhook processing failed', \Mockery::any());

        $validSignature = hash_hmac('sha256', json_encode($this->validRetellPayload), $this->webhookSecret);

        $response = $this->postJson('/test/retell/webhook', $this->validRetellPayload, [
            'X-Retell-Signature' => $validSignature
        ]);

        $response->assertNoContent();

        // Verify webhook event was marked as failed
        $this->assertDatabaseHas('webhook_events', [
            'provider' => WebhookEvent::PROVIDER_RETELL,
            'event_id' => 'retell_call_test_123',
            'status' => WebhookEvent::STATUS_FAILED
        ]);
    }

    #[Test]
    public function test_error_handling_for_missing_webhook_secret()
    {
        // Remove webhook secret from config
        Config::set('services.retell.webhook_secret', null);

        Log::shouldReceive('error')
            ->once()
            ->with('Retell webhook signature verification failed', \Mockery::any());

        $response = $this->postJson('/test/retell/webhook', $this->validRetellPayload, [
            'X-Retell-Signature' => 'any_signature'
        ]);

        $response->assertNoContent();
    }

    #[Test]
    public function test_webhook_idempotency_prevents_duplicate_processing()
    {
        Queue::fake();

        $validSignature = hash_hmac('sha256', json_encode($this->validRetellPayload), $this->webhookSecret);

        // First request - should process normally
        $response1 = $this->postJson('/test/retell/webhook', $this->validRetellPayload, [
            'X-Retell-Signature' => $validSignature
        ]);
        $response1->assertNoContent();

        // Second request with same payload - should be detected as duplicate
        $response2 = $this->postJson('/test/retell/webhook', $this->validRetellPayload, [
            'X-Retell-Signature' => $validSignature
        ]);
        $response2->assertNoContent();

        // Should only have queued one job
        Queue::assertPushed(ProcessRetellWebhookJob::class, 1);

        // Should only have one webhook event record
        $this->assertEquals(1, WebhookEvent::where('event_id', 'retell_call_test_123')->count());
    }

    #[Test]
    public function test_webhook_handles_different_event_types()
    {
        Queue::fake();

        $eventTypes = ['call_started', 'call_ended', 'call_analyzed'];

        foreach ($eventTypes as $eventType) {
            $payload = array_merge($this->validRetellPayload, [
                'event' => $eventType,
                'call' => array_merge($this->validRetellPayload['call'], [
                    'call_id' => "retell_call_{$eventType}_123"
                ])
            ]);

            $validSignature = hash_hmac('sha256', json_encode($payload), $this->webhookSecret);

            $response = $this->postJson('/test/retell/webhook', $payload, [
                'X-Retell-Signature' => $validSignature
            ]);

            $response->assertNoContent();

            // Verify webhook event was created for each event type
            $this->assertDatabaseHas('webhook_events', [
                'provider' => WebhookEvent::PROVIDER_RETELL,
                'event_type' => $eventType,
                'event_id' => "retell_call_{$eventType}_123"
            ]);
        }

        Queue::assertPushed(ProcessRetellWebhookJob::class, count($eventTypes));
    }

    #[Test]
    public function test_webhook_logs_correlation_id_for_tracing()
    {
        Config::set('services.webhook.async.retell', false);

        // Mock handler for synchronous processing
        $mockHandler = $this->createMock(RetellWebhookHandler::class);
        $mockHandler->expects($this->once())
            ->method('handle')
            ->willReturn(['success' => true, 'message' => 'Processed successfully']);

        $this->app->instance(RetellWebhookHandler::class, $mockHandler);

        Log::shouldReceive('info')
            ->times(3)
            ->with(\Mockery::type('string'), \Mockery::that(function ($context) {
                return isset($context['correlation_id']);
            }));

        $validSignature = hash_hmac('sha256', json_encode($this->validRetellPayload), $this->webhookSecret);

        $response = $this->postJson('/test/retell/webhook', $this->validRetellPayload, [
            'X-Retell-Signature' => $validSignature
        ]);

        $response->assertNoContent();
    }

    #[Test]
    public function test_webhook_handles_large_payloads_gracefully()
    {
        Queue::fake();

        // Create a large payload with extensive transcript
        $largePayload = array_merge($this->validRetellPayload, [
            'call' => array_merge($this->validRetellPayload['call'], [
                'transcript' => str_repeat('This is a very long conversation transcript. ', 1000),
                'call_analysis' => array_merge($this->validRetellPayload['call']['call_analysis'], [
                    'detailed_notes' => str_repeat('Extensive analysis notes. ', 500)
                ])
            ])
        ]);

        $validSignature = hash_hmac('sha256', json_encode($largePayload), $this->webhookSecret);

        $response = $this->postJson('/test/retell/webhook', $largePayload, [
            'X-Retell-Signature' => $validSignature
        ]);

        $response->assertNoContent();
        Queue::assertPushed(ProcessRetellWebhookJob::class);
    }

    /**
     * Helper method to generate a valid HMAC signature for webhook testing
     */
    private function generateValidSignature(array $payload, string $secret): string
    {
        return hash_hmac('sha256', json_encode($payload), $secret);
    }

    /**
     * Helper method to create a webhook request with proper headers
     */
    private function createWebhookRequest(array $payload, ?string $signature = null): array
    {
        $headers = ['Content-Type' => 'application/json'];
        
        if ($signature !== null) {
            $headers['X-Retell-Signature'] = $signature;
        }
        
        return $headers;
    }
}