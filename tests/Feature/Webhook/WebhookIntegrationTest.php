<?php

namespace Tests\Feature\Webhook;

use App\Events\WebhookReceived;
use App\Jobs\ProcessCalcomWebhookJob;
use App\Jobs\ProcessRetellCallEndedJob;
use App\Jobs\ProcessStripeWebhookJob;
use App\Models\Branch;
use App\Models\CalcomEventType;
use App\Models\Company;
use App\Models\Service;
use App\Models\Staff;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WebhookIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test data
        $this->company = Company::factory()->create([
            'retell_api_key' => 'test_retell_key',
            'calcom_api_key' => 'test_calcom_key',
            'stripe_customer_id' => 'cus_test123'
        ]);
        
        $this->branch = Branch::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Test Branch',
            'phone' => '+4915252525252'
        ]);
        
        // Set webhook secrets
        config(['services.retell.webhook_secret' => 'test_secret']);
        config(['services.calcom.webhook_secret' => 'test_secret']);
        config(['services.stripe.webhook_secret' => 'test_secret']);
    }

    /** @test */
    public function retell_webhook_processes_call_ended_event()
    {
        Queue::fake();
        Event::fake();
        
        $payload = [
            'event' => 'call_ended',
            'call' => [
                'call_id' => 'test_call_123',
                'agent_id' => 'test_agent',
                'from_number' => '+491234567890',
                'to_number' => '+4915252525252',
                'duration' => 120,
                'transcript' => 'Test conversation transcript',
                'call_status' => 'ended',
                'start_timestamp' => now()->subMinutes(2)->timestamp * 1000,
                'end_timestamp' => now()->timestamp * 1000,
                'metadata' => [
                    'branch_id' => $this->branch->id
                ]
            ]
        ];
        
        $signature = $this->generateWebhookSignature($payload, 'test_secret');
        
        $response = $this->postJson('/api/retell/webhook', $payload, [
            'X-Retell-Signature' => $signature
        ]);
        
        $response->assertStatus(204);
        
        Queue::assertPushed(ProcessRetellCallEndedJob::class, function ($job) use ($payload) {
            return $job->payload['call']['call_id'] === 'test_call_123';
        });
        
        Event::assertDispatched(WebhookReceived::class, function ($event) {
            return $event->provider === 'retell' && $event->type === 'call_ended';
        });
    }

    /** @test */
    #[Test]
    public function calcom_webhook_processes_booking_created()
    {
        Queue::fake();
        Event::fake();
        
        $eventType = CalcomEventType::factory()->create([
            'calcom_event_id' => 123456,
            'title' => 'Test Service',
            'company_id' => $this->company->id
        ]);
        
        $payload = [
            'triggerEvent' => 'BOOKING_CREATED',
            'payload' => [
                'uid' => 'booking_123',
                'title' => 'Test Booking',
                'startTime' => now()->addDay()->toIso8601String(),
                'endTime' => now()->addDay()->addHour()->toIso8601String(),
                'attendees' => [
                    [
                        'name' => 'Max Mustermann',
                        'email' => 'max@example.com',
                        'timeZone' => 'Europe/Berlin'
                    ]
                ],
                'eventTypeId' => 123456,
                'location' => [
                    'type' => 'integrations:daily'
                ],
                'metadata' => [
                    'branch_id' => $this->branch->id
                ]
            ]
        ];
        
        $signature = $this->generateWebhookSignature($payload, 'test_secret');
        
        $response = $this->postJson('/api/calcom/webhook', $payload, [
            'X-Cal-Signature-256' => $signature
        ]);
        
        $response->assertOk();
        
        Queue::assertPushed(ProcessCalcomWebhookJob::class, function ($job) use ($payload) {
            return $job->payload['payload']['uid'] === 'booking_123';
        });
        
        Event::assertDispatched(WebhookReceived::class, function ($event) {
            return $event->provider === 'calcom' && $event->type === 'BOOKING_CREATED';
        });
    }

    /** @test */
    public function stripe_webhook_processes_payment_intent_succeeded()
    {
        Queue::fake();
        Event::fake();
        
        $payload = [
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => 'pi_test123',
                    'amount' => 5000,
                    'currency' => 'eur',
                    'status' => 'succeeded',
                    'customer' => 'cus_test123',
                    'metadata' => [
                        'company_id' => $this->company->id,
                        'type' => 'appointment_payment'
                    ]
                ]
            ]
        ];
        
        $signature = $this->generateStripeWebhookSignature($payload);
        
        $response = $this->postJson('/api/stripe/webhook', $payload, [
            'Stripe-Signature' => $signature
        ]);
        
        $response->assertOk();
        
        Queue::assertPushed(ProcessStripeWebhookJob::class, function ($job) use ($payload) {
            return $job->eventType === 'payment_intent.succeeded';
        });
        
        Event::assertDispatched(WebhookReceived::class, function ($event) {
            return $event->provider === 'stripe' && $event->type === 'payment_intent.succeeded';
        });
    }

    /** @test */
    #[Test]
    public function webhook_with_invalid_signature_is_rejected()
    {
        Queue::fake();
        Event::fake();
        
        $payload = [
            'event' => 'call_ended',
            'call' => ['call_id' => 'test']
        ];
        
        $response = $this->postJson('/api/retell/webhook', $payload, [
            'X-Retell-Signature' => 'invalid_signature'
        ]);
        
        $response->assertStatus(401);
        Queue::assertNothingPushed();
        Event::assertNotDispatched(WebhookReceived::class);
    }

    /** @test */
    public function webhook_deduplication_prevents_duplicate_processing()
    {
        Queue::fake();
        
        $payload = [
            'event' => 'call_ended',
            'call' => [
                'call_id' => 'duplicate_test_123',
                'agent_id' => 'test_agent',
                'from_number' => '+491234567890',
                'to_number' => '+4915252525252',
                'duration' => 60
            ]
        ];
        
        $signature = $this->generateWebhookSignature($payload, 'test_secret');
        $headers = ['X-Retell-Signature' => $signature];
        
        // First request
        $response1 = $this->postJson('/api/retell/webhook', $payload, $headers);
        $response1->assertStatus(204);
        
        // Second request with same payload
        $response2 = $this->postJson('/api/retell/webhook', $payload, $headers);
        $response2->assertStatus(204);
        
        // Should only be pushed once
        Queue::assertPushed(ProcessRetellCallEndedJob::class, 1);
    }

    /** @test */
    #[Test]
    public function unified_webhook_handler_routes_correctly()
    {
        Queue::fake();
        
        // Test Retell webhook through unified handler
        $retellPayload = [
            'event' => 'call_started',
            'call' => ['call_id' => 'unified_test']
        ];
        
        $signature = $this->generateWebhookSignature($retellPayload, 'test_secret');
        
        $response = $this->postJson('/api/webhook', $retellPayload, [
            'X-Retell-Signature' => $signature
        ]);
        
        $response->assertOk();
        $response->assertJson(['message' => 'Webhook processed successfully']);
    }

    /** @test */
    public function webhook_health_endpoint_returns_status()
    {
        $response = $this->getJson('/api/webhook/health');
        
        $response->assertOk();
        $response->assertJsonStructure([
            'status',
            'providers',
            'last_received',
            'total_processed'
        ]);
    }

    /** @test */
    #[Test]
    public function webhook_handles_malformed_json_gracefully()
    {
        $response = $this->postJson('/api/retell/webhook', 'invalid json', [
            'X-Retell-Signature' => 'test'
        ]);
        
        $response->assertStatus(422);
        $response->assertJsonStructure(['message', 'errors']);
    }

    /** @test */
    public function webhook_respects_rate_limiting()
    {
        Queue::fake();
        
        $payload = ['event' => 'test', 'data' => []];
        $signature = $this->generateWebhookSignature($payload, 'test_secret');
        $headers = ['X-Retell-Signature' => $signature];
        
        // Make 100 requests (rate limit)
        for ($i = 0; $i < 100; $i++) {
            $payload['data']['counter'] = $i;
            $response = $this->postJson('/api/retell/webhook', $payload, $headers);
            $response->assertStatus(204);
        }
        
        // 101st request should be rate limited
        $payload['data']['counter'] = 101;
        $response = $this->postJson('/api/retell/webhook', $payload, $headers);
        $response->assertStatus(429); // Too Many Requests
    }

    /** @test */
    #[Test]
    public function webhook_correlation_id_tracks_through_system()
    {
        Queue::fake();
        
        $correlationId = 'test-correlation-123';
        
        $payload = [
            'event' => 'call_ended',
            'call' => ['call_id' => 'correlation_test']
        ];
        
        $signature = $this->generateWebhookSignature($payload, 'test_secret');
        
        $response = $this->postJson('/api/retell/webhook', $payload, [
            'X-Retell-Signature' => $signature,
            'X-Correlation-ID' => $correlationId
        ]);
        
        $response->assertStatus(204);
        $response->assertHeader('X-Correlation-ID', $correlationId);
        
        Queue::assertPushed(ProcessRetellCallEndedJob::class, function ($job) use ($correlationId) {
            return $job->correlationId === $correlationId;
        });
    }

    /**
     * Helper method to generate webhook signature
     */
    private function generateWebhookSignature(array $payload, string $secret): string
    {
        $payloadString = json_encode($payload);
        return hash_hmac('sha256', $payloadString, $secret);
    }

    /**
     * Helper method to generate Stripe webhook signature
     */
    private function generateStripeWebhookSignature(array $payload): string
    {
        $timestamp = time();
        $payloadString = json_encode($payload);
        $signedPayload = "{$timestamp}.{$payloadString}";
        $signature = hash_hmac('sha256', $signedPayload, 'test_secret');
        
        return "t={$timestamp},v1={$signature}";
    }
}