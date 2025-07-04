<?php

namespace Tests\Integration\MCP;

use App\Services\MCP\MCPOrchestrator;
use App\Services\Webhook\WebhookProcessor;
use App\Services\Webhook\RetellWebhookHandler;
use App\Services\RetellService;
use App\Services\CalcomV2Service;
use App\Models\Call;
use App\Models\Customer;
use App\Models\Appointment;
use App\Models\Company;
use App\Models\Branch;
use App\Models\PhoneNumber;
use App\Jobs\ProcessRetellWebhookJob;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class WebhookFlowTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private Branch $branch;
    private PhoneNumber $phoneNumber;
    private MCPOrchestrator $orchestrator;
    private WebhookProcessor $webhookProcessor;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test company and branch
        $this->company = Company::factory()->create([
            'name' => 'Test Clinic',
            'retell_api_key' => 'test_retell_key',
            'calcom_api_key' => 'test_calcom_key'
        ]);
        
        $this->branch = Branch::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Main Office',
            'calcom_event_type_id' => 12345,
            'is_active' => true
        ]);
        
        $this->phoneNumber = PhoneNumber::factory()->create([
            'phone_number' => '+493012345678',
            'branch_id' => $this->branch->id,
            'is_active' => true
        ]);
        
        $this->orchestrator = app(MCPOrchestrator::class);
        $this->webhookProcessor = app(WebhookProcessor::class);
    }

    public function test_complete_retell_webhook_flow()
    {
        Queue::fake();
        
        $webhookPayload = [
            'event' => 'call_ended',
            'data' => [
                'call_id' => 'call_123',
                'agent_id' => 'agent_456',
                'to' => '+493012345678',
                'from' => '+491701234567',
                'duration' => 180,
                'transcript' => 'Ich möchte einen Termin am 25. Juni um 10 Uhr.',
                'summary' => 'Kunde möchte Termin am 25.06. um 10:00 Uhr',
                'custom_analysis' => [
                    'appointment_requested' => true,
                    'date' => '2025-06-25',
                    'time' => '10:00',
                    'service' => 'Beratung',
                    'customer_name' => 'Max Mustermann'
                ]
            ]
        ];
        
        // Mock external API calls
        Http::fake([
            'api.retellai.com/*' => Http::response(['success' => true], 200),
            'api.cal.com/*' => Http::response(['success' => true], 200)
        ]);
        
        // Process webhook
        $response = $this->postJson('/api/retell/webhook', $webhookPayload, [
            'x-retell-signature' => $this->generateValidSignature($webhookPayload)
        ]);
        
        $response->assertStatus(200);
        
        // Verify job was queued
        Queue::assertPushed(ProcessRetellWebhookJob::class, function ($job) use ($webhookPayload) {
            return $job->payload['data']['call_id'] === 'call_123';
        });
        
        // Process the job
        $job = new ProcessRetellWebhookJob($webhookPayload);
        $job->handle($this->webhookProcessor);
        
        // Verify call was created
        $call = Call::where('retell_call_id', 'call_123')->first();
        $this->assertNotNull($call);
        $this->assertEquals($this->company->id, $call->company_id);
        $this->assertEquals($this->branch->id, $call->branch_id);
        $this->assertEquals(180, $call->duration);
        
        // Verify customer was created/found
        $customer = Customer::where('phone', '+491701234567')->first();
        $this->assertNotNull($customer);
        $this->assertEquals('Max Mustermann', $customer->name);
        
        // Verify appointment was created
        $appointment = Appointment::where('call_id', $call->id)->first();
        $this->assertNotNull($appointment);
        $this->assertEquals('2025-06-25 10:00:00', $appointment->start_time);
        $this->assertEquals($customer->id, $appointment->customer_id);
    }

    public function test_webhook_deduplication()
    {
        Queue::fake();
        
        $webhookPayload = [
            'event' => 'call_ended',
            'data' => [
                'call_id' => 'duplicate_123',
                'to' => '+493012345678',
                'from' => '+491701234567'
            ]
        ];
        
        $signature = $this->generateValidSignature($webhookPayload);
        
        // First request
        $response1 = $this->postJson('/api/retell/webhook', $webhookPayload, [
            'x-retell-signature' => $signature
        ]);
        $response1->assertStatus(200);
        
        // Duplicate request with same call_id
        $response2 = $this->postJson('/api/retell/webhook', $webhookPayload, [
            'x-retell-signature' => $signature
        ]);
        $response2->assertStatus(200);
        
        // Should only queue one job
        Queue::assertPushed(ProcessRetellWebhookJob::class, 1);
    }

    public function test_webhook_signature_validation()
    {
        $webhookPayload = [
            'event' => 'call_ended',
            'data' => ['call_id' => 'test_123']
        ];
        
        // Invalid signature
        $response = $this->postJson('/api/retell/webhook', $webhookPayload, [
            'x-retell-signature' => 'invalid_signature'
        ]);
        
        $response->assertStatus(401);
        $response->assertJson(['error' => 'Invalid signature']);
    }

    public function test_webhook_handles_missing_phone_number()
    {
        Queue::fake();
        
        $webhookPayload = [
            'event' => 'call_ended',
            'data' => [
                'call_id' => 'orphan_123',
                'to' => '+499999999999', // Unknown number
                'from' => '+491701234567'
            ]
        ];
        
        $response = $this->postJson('/api/retell/webhook', $webhookPayload, [
            'x-retell-signature' => $this->generateValidSignature($webhookPayload)
        ]);
        
        $response->assertStatus(200);
        
        // Job should still be queued for logging
        Queue::assertPushed(ProcessRetellWebhookJob::class);
        
        // Process the job
        $job = new ProcessRetellWebhookJob($webhookPayload);
        $job->handle($this->webhookProcessor);
        
        // Call should be created but without branch association
        $call = Call::where('retell_call_id', 'orphan_123')->first();
        $this->assertNotNull($call);
        $this->assertNull($call->branch_id);
        $this->assertNull($call->company_id);
    }

    public function test_webhook_retry_on_failure()
    {
        Queue::fake();
        
        $webhookPayload = [
            'event' => 'call_ended',
            'data' => [
                'call_id' => 'retry_123',
                'to' => '+493012345678'
            ]
        ];
        
        // Mock external API failure
        Http::fake([
            'api.retellai.com/*' => Http::response(['error' => 'Server error'], 500)
        ]);
        
        $response = $this->postJson('/api/retell/webhook', $webhookPayload, [
            'x-retell-signature' => $this->generateValidSignature($webhookPayload)
        ]);
        
        $response->assertStatus(200); // Webhook returns 200 even if processing fails
        
        Queue::assertPushed(ProcessRetellWebhookJob::class, function ($job) {
            return $job->tries === 3; // Should have retry configured
        });
    }

    public function test_webhook_circuit_breaker_activation()
    {
        $webhookPayload = [
            'event' => 'call_ended',
            'data' => ['call_id' => 'test_123']
        ];
        
        // Simulate multiple failures to trigger circuit breaker
        for ($i = 0; $i < 5; $i++) {
            Cache::put("circuit_breaker.retell.failure.$i", true, 60);
        }
        
        $response = $this->postJson('/api/retell/webhook', $webhookPayload, [
            'x-retell-signature' => $this->generateValidSignature($webhookPayload)
        ]);
        
        $response->assertStatus(503); // Service unavailable when circuit is open
    }

    public function test_webhook_processes_multiple_events()
    {
        Queue::fake();
        
        $events = [
            ['event' => 'call_started', 'data' => ['call_id' => 'start_123']],
            ['event' => 'call_analyzed', 'data' => ['call_id' => 'analyze_123']],
            ['event' => 'call_ended', 'data' => ['call_id' => 'end_123']]
        ];
        
        foreach ($events as $payload) {
            $payload['data']['to'] = '+493012345678';
            
            $response = $this->postJson('/api/retell/webhook', $payload, [
                'x-retell-signature' => $this->generateValidSignature($payload)
            ]);
            
            $response->assertStatus(200);
        }
        
        // All events should be queued
        Queue::assertPushed(ProcessRetellWebhookJob::class, 3);
    }

    public function test_webhook_handles_malformed_payload()
    {
        $malformedPayloads = [
            [], // Empty payload
            ['event' => 'call_ended'], // Missing data
            ['data' => ['call_id' => '123']], // Missing event
            ['event' => 'unknown_event', 'data' => []] // Unknown event
        ];
        
        foreach ($malformedPayloads as $payload) {
            $response = $this->postJson('/api/retell/webhook', $payload, [
                'x-retell-signature' => $this->generateValidSignature($payload)
            ]);
            
            $response->assertStatus(422); // Unprocessable entity
        }
    }

    public function test_webhook_rate_limiting()
    {
        $webhookPayload = [
            'event' => 'call_ended',
            'data' => ['call_id' => 'rate_test']
        ];
        
        // Send many requests quickly
        for ($i = 0; $i < 100; $i++) {
            $response = $this->postJson('/api/retell/webhook', $webhookPayload, [
                'x-retell-signature' => $this->generateValidSignature($webhookPayload)
            ]);
            
            if ($i < 60) { // Rate limit is 60/minute
                $response->assertStatus(200);
            } else {
                $response->assertStatus(429); // Too many requests
            }
        }
    }

    public function test_webhook_correlation_id_tracking()
    {
        Queue::fake();
        Event::fake();
        
        $webhookPayload = [
            'event' => 'call_ended',
            'data' => [
                'call_id' => 'correlation_123',
                'to' => '+493012345678'
            ]
        ];
        
        $response = $this->postJson('/api/retell/webhook', $webhookPayload, [
            'x-retell-signature' => $this->generateValidSignature($webhookPayload),
            'X-Correlation-ID' => 'test-correlation-id-123'
        ]);
        
        $response->assertStatus(200);
        $response->assertHeader('X-Correlation-ID', 'test-correlation-id-123');
        
        // Verify correlation ID is passed to job
        Queue::assertPushed(ProcessRetellWebhookJob::class, function ($job) {
            return isset($job->correlationId) && $job->correlationId === 'test-correlation-id-123';
        });
    }

    private function generateValidSignature(array $payload): string
    {
        $secret = config('services.retell.webhook_secret', 'test_secret');
        $payloadString = json_encode($payload);
        return hash_hmac('sha256', $payloadString, $secret);
    }
}