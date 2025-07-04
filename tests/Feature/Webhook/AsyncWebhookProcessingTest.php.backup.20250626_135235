<?php

namespace Tests\Feature\Webhook;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use App\Jobs\ProcessRetellWebhookJob;
use App\Models\WebhookEvent;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Call;
use App\Models\Customer;
use App\Services\WebhookProcessor;
use App\Services\Webhook\EnhancedWebhookDeduplicationService;

class AsyncWebhookProcessingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Clear Redis
        Redis::flushall();
        
        // Create test company and branch
        $this->company = Company::factory()->create([
            'phone_number' => '+49 30 12345678',
            'retell_api_key' => 'test-key',
            'calcom_api_key' => 'test-cal-key',
        ]);
        
        $this->branch = Branch::factory()->create([
            'company_id' => $this->company->id,
            'phone_number' => '+49 30 12345678',
            'is_active' => true,
            'calcom_event_type_id' => 123456,
        ]);
    }

    public function test_retell_webhook_is_queued_for_async_processing()
    {
        Queue::fake();
        
        $payload = [
            'event' => 'call_ended',
            'call_id' => 'test-call-123',
            'call' => [
                'from_number' => '+49 151 12345678',
                'to_number' => '+49 30 12345678',
                'direction' => 'inbound',
                'call_duration' => 120,
                'start_timestamp' => now()->subMinutes(2)->timestamp * 1000,
                'end_timestamp' => now()->timestamp * 1000,
                'retell_llm_dynamic_variables' => [
                    'booking_confirmed' => true,
                    'datum' => '2025-06-20',
                    'uhrzeit' => '14:30',
                    'kundenwunsch' => 'Haarschnitt',
                ],
            ],
        ];
        
        $response = $this->postJson('/api/retell/webhook', $payload, [
            'x-retell-signature' => $this->generateRetellSignature($payload),
        ]);
        
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Webhook processed successfully',
        ]);
        
        // Assert job was dispatched
        Queue::assertPushed(ProcessRetellWebhookJob::class, function ($job) {
            return $job->queue === 'webhooks-high';
        });
    }

    public function test_webhook_deduplication_works_with_async_processing()
    {
        Queue::fake();
        
        $payload = [
            'event' => 'call_ended',
            'call_id' => 'duplicate-test-123',
            'call' => [
                'from_number' => '+49 151 12345678',
                'to_number' => '+49 30 12345678',
                'call_duration' => 60,
            ],
        ];
        
        $signature = $this->generateRetellSignature($payload);
        
        // First request
        $response1 = $this->postJson('/api/retell/webhook', $payload, [
            'x-retell-signature' => $signature,
        ]);
        
        $response1->assertStatus(200);
        Queue::assertPushed(ProcessRetellWebhookJob::class, 1);
        
        // Second request (duplicate)
        $response2 = $this->postJson('/api/retell/webhook', $payload, [
            'x-retell-signature' => $signature,
        ]);
        
        $response2->assertStatus(200);
        $response2->assertJson([
            'success' => true,
            'message' => 'Webhook already processed',
            'duplicate' => true,
        ]);
        
        // Job should only be dispatched once
        Queue::assertPushed(ProcessRetellWebhookJob::class, 1);
    }

    public function test_high_priority_events_use_dedicated_queue()
    {
        Queue::fake();
        
        // Test call_inbound (high priority)
        $inboundPayload = [
            'event' => 'call_inbound',
            'call_id' => 'inbound-123',
            'call_inbound' => [
                'from_number' => '+49 151 12345678',
                'to_number' => '+49 30 12345678',
            ],
        ];
        
        $this->postJson('/api/retell/webhook', $inboundPayload, [
            'x-retell-signature' => $this->generateRetellSignature($inboundPayload),
        ]);
        
        Queue::assertPushed(ProcessRetellWebhookJob::class, function ($job) {
            return $job->queue === 'webhooks-high';
        });
        
        // Test call_started (normal priority)
        $startedPayload = [
            'event' => 'call_started',
            'call_id' => 'started-123',
            'call' => [
                'from_number' => '+49 151 12345678',
                'to_number' => '+49 30 12345678',
            ],
        ];
        
        $this->postJson('/api/retell/webhook', $startedPayload, [
            'x-retell-signature' => $this->generateRetellSignature($startedPayload),
        ]);
        
        Queue::assertPushed(ProcessRetellWebhookJob::class, function ($job) {
            return $job->queue === 'webhooks';
        });
    }

    public function test_job_processes_webhook_correctly()
    {
        // Create webhook event
        $webhookEvent = WebhookEvent::create([
            'provider' => WebhookEvent::PROVIDER_RETELL,
            'event_type' => 'call_ended',
            'idempotency_key' => 'test-key-123',
            'payload' => [
                'event' => 'call_ended',
                'call_id' => 'job-test-123',
                'call' => [
                    'from_number' => '+49 151 12345678',
                    'to_number' => '+49 30 12345678',
                    'direction' => 'inbound',
                    'call_duration' => 180,
                    'start_timestamp' => now()->subMinutes(3)->timestamp * 1000,
                    'end_timestamp' => now()->timestamp * 1000,
                    'transcript' => 'Test conversation transcript',
                    'retell_llm_dynamic_variables' => [
                        'booking_confirmed' => true,
                        'datum' => '2025-06-25',
                        'uhrzeit' => '10:00',
                        'kundenwunsch' => 'Beratungsgespräch',
                        'mitarbeiter_id' => null,
                        'dienstleistung_id' => null,
                    ],
                ],
            ],
            'headers' => [],
            'status' => 'pending',
        ]);
        
        // Create and dispatch job
        $job = new ProcessRetellWebhookJob($webhookEvent, 'test-correlation-123');
        
        // Execute job
        $job->handle(
            app(WebhookProcessor::class),
            app(EnhancedWebhookDeduplicationService::class)
        );
        
        // Assert webhook event was marked as completed
        $webhookEvent->refresh();
        $this->assertEquals('completed', $webhookEvent->status);
        $this->assertNotNull($webhookEvent->processed_at);
        
        // Assert call was created
        $call = Call::where('retell_call_id', 'job-test-123')->first();
        $this->assertNotNull($call);
        $this->assertEquals('+49 151 12345678', $call->from_number);
        $this->assertEquals('+49 30 12345678', $call->to_number);
        $this->assertEquals(180, $call->duration_seconds);
        $this->assertEquals('completed', $call->status);
        
        // Assert customer was created
        $customer = Customer::where('phone', '+49 151 12345678')->first();
        $this->assertNotNull($customer);
        $this->assertEquals($this->company->id, $customer->company_id);
        
        // Assert appointment was created
        $appointment = $call->appointment;
        $this->assertNotNull($appointment);
        $this->assertEquals('2025-06-25', $appointment->start_time->format('Y-m-d'));
        $this->assertEquals('10:00', $appointment->start_time->format('H:i'));
        $this->assertEquals($customer->id, $appointment->customer_id);
        $this->assertEquals($this->branch->id, $appointment->branch_id);
        $this->assertStringContainsString('Beratungsgespräch', $appointment->notes);
    }

    public function test_job_handles_failure_gracefully()
    {
        // Create webhook event with invalid data
        $webhookEvent = WebhookEvent::create([
            'provider' => WebhookEvent::PROVIDER_RETELL,
            'event_type' => 'call_ended',
            'idempotency_key' => 'test-fail-123',
            'payload' => [
                'event' => 'call_ended',
                // Missing call_id - will cause error
                'call' => [
                    'from_number' => '+49 151 12345678',
                ],
            ],
            'headers' => [],
            'status' => 'pending',
        ]);
        
        // Create job
        $job = new ProcessRetellWebhookJob($webhookEvent, 'test-correlation-fail');
        
        // Execute job and expect exception
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Missing call_id in call_ended event');
        
        $job->handle(
            app(WebhookProcessor::class),
            app(EnhancedWebhookDeduplicationService::class)
        );
        
        // Assert webhook event was marked as failed
        $webhookEvent->refresh();
        $this->assertEquals('failed', $webhookEvent->status);
        $this->assertNotNull($webhookEvent->error);
        $this->assertNotNull($webhookEvent->failed_at);
    }

    public function test_sync_processing_can_be_forced()
    {
        // Disable async processing
        config(['services.webhook.async.retell' => false]);
        
        Queue::fake();
        
        $payload = [
            'event' => 'call_started',
            'call_id' => 'sync-test-123',
            'call' => [
                'from_number' => '+49 151 12345678',
                'to_number' => '+49 30 12345678',
                'start_timestamp' => now()->timestamp * 1000,
            ],
        ];
        
        $response = $this->postJson('/api/retell/webhook', $payload, [
            'x-retell-signature' => $this->generateRetellSignature($payload),
        ]);
        
        $response->assertStatus(200);
        
        // No job should be queued
        Queue::assertNothingPushed();
        
        // Call should be created immediately
        $call = Call::where('retell_call_id', 'sync-test-123')->first();
        $this->assertNotNull($call);
        $this->assertEquals('in_progress', $call->status);
    }

    protected function generateRetellSignature($payload): string
    {
        $secret = config('services.retell.secret');
        return hash_hmac('sha256', json_encode($payload), $secret);
    }
}