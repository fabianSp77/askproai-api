<?php

namespace Tests\Feature\API\V2;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use App\Models\Company;
use App\Models\Tenant;
use App\Models\Customer;
use App\Models\Call;
use App\Models\Appointment;
use App\Models\CalcomEventType;
use App\Jobs\ProcessRetellCallJob;
use App\Jobs\ProcessCalcomWebhookJob;
use Carbon\Carbon;

class WebhookApiTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected Tenant $tenant;
    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create tenant and company
        $this->tenant = Tenant::factory()->create([
            'api_key' => 'test-tenant-api-key',
            'calcom_team_slug' => 'test-team'
        ]);
        
        $this->company = Company::factory()->create(['tenant_id' => $this->tenant->id]);
        
        // Set up webhook signatures in config
        config(['services.retell.webhook_secret' => 'test-retell-secret']);
        config(['services.calcom.webhook_secret' => 'test-calcom-secret']);
    }

    /**
     * Generate a valid webhook signature
     */
    protected function generateWebhookSignature(string $payload, string $secret): string
    {
        return hash_hmac('sha256', $payload, $secret);
    }

    /**
     * Test Retell webhook without signature is rejected
     */
    public function test_retell_webhook_without_signature_is_rejected()
    {
        $response = $this->postJson('/api/v2/public/webhooks/retell', [
            'call_id' => 'test-call-123'
        ]);
        
        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Invalid signature'
            ]);
    }

    /**
     * Test Retell webhook with invalid signature is rejected
     */
    public function test_retell_webhook_with_invalid_signature_is_rejected()
    {
        $payload = [
            'call_id' => 'test-call-123',
            'status' => 'completed'
        ];
        
        $response = $this->postJson(
            '/api/v2/public/webhooks/retell',
            $payload,
            ['X-Retell-Signature' => 'invalid-signature']
        );
        
        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Invalid signature'
            ]);
    }

    /**
     * Test successful Retell webhook for completed call
     */
    public function test_retell_webhook_processes_completed_call()
    {
        Queue::fake();
        
        $payload = [
            'call_id' => 'retell_call_123',
            'status' => 'completed',
            'phone_number' => '+49 170 1234567',
            'duration' => 180,
            'transcript' => 'Customer: I would like to book an appointment. Agent: Sure, I can help you with that.',
            'user_sentiment' => 'positive',
            'call_successful' => true,
            'agent_id' => 'agent_123',
            'metadata' => [
                'customer_name' => 'Max Mustermann',
                'customer_email' => 'max@example.com',
                'appointment_date' => '2025-06-20',
                'appointment_time' => '14:30',
                'service' => 'Consultation'
            ],
            'analysis' => [
                'summary' => 'Customer called to book an appointment for consultation',
                'intent' => 'appointment_booking',
                'satisfaction_score' => 0.9
            ]
        ];
        
        $jsonPayload = json_encode($payload);
        $signature = $this->generateWebhookSignature($jsonPayload, 'test-retell-secret');
        
        $response = $this->postJson(
            '/api/v2/public/webhooks/retell',
            $payload,
            [
                'X-Retell-Signature' => $signature,
                'Content-Type' => 'application/json'
            ]
        );
        
        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Webhook processed successfully'
            ]);
        
        // Verify job was dispatched
        Queue::assertPushed(ProcessRetellCallJob::class, function ($job) use ($payload) {
            return $job->payload['call_id'] === 'retell_call_123';
        });
    }

    /**
     * Test Retell webhook validation
     */
    public function test_retell_webhook_validates_required_fields()
    {
        $payload = [
            'status' => 'completed'
            // Missing call_id
        ];
        
        $jsonPayload = json_encode($payload);
        $signature = $this->generateWebhookSignature($jsonPayload, 'test-retell-secret');
        
        $response = $this->postJson(
            '/api/v2/public/webhooks/retell',
            $payload,
            ['X-Retell-Signature' => $signature]
        );
        
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['call_id']);
    }

    /**
     * Test Retell webhook for different call statuses
     */
    public function test_retell_webhook_handles_different_call_statuses()
    {
        Queue::fake();
        
        $statuses = ['completed', 'failed', 'no-answer', 'busy', 'cancelled'];
        
        foreach ($statuses as $status) {
            $payload = [
                'call_id' => "retell_call_{$status}",
                'status' => $status,
                'phone_number' => '+49 170 1234567',
                'duration' => $status === 'completed' ? 120 : 0,
                'disconnect_reason' => $status === 'failed' ? 'NETWORK_ERROR' : null
            ];
            
            $jsonPayload = json_encode($payload);
            $signature = $this->generateWebhookSignature($jsonPayload, 'test-retell-secret');
            
            $response = $this->postJson(
                '/api/v2/public/webhooks/retell',
                $payload,
                ['X-Retell-Signature' => $signature]
            );
            
            $response->assertStatus(200);
        }
        
        Queue::assertPushed(ProcessRetellCallJob::class, count($statuses));
    }

    /**
     * Test Cal.com webhook without signature is rejected
     */
    public function test_calcom_webhook_without_signature_is_rejected()
    {
        $response = $this->postJson('/api/v2/public/webhooks/calcom', [
            'triggerEvent' => 'BOOKING_CREATED'
        ]);
        
        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Invalid signature'
            ]);
    }

    /**
     * Test successful Cal.com booking created webhook
     */
    public function test_calcom_webhook_processes_booking_created()
    {
        Queue::fake();
        
        // Create event type
        $eventType = CalcomEventType::factory()->create([
            'company_id' => $this->company->id,
            'calcom_id' => 123456
        ]);
        
        $payload = [
            'triggerEvent' => 'BOOKING_CREATED',
            'createdAt' => now()->toIso8601String(),
            'payload' => [
                'type' => 'BOOKING_CREATED',
                'title' => 'Consultation with John Doe',
                'description' => 'Initial consultation',
                'startTime' => now()->addDay()->setHour(14)->setMinute(0)->toIso8601String(),
                'endTime' => now()->addDay()->setHour(15)->setMinute(0)->toIso8601String(),
                'organizer' => [
                    'id' => 789,
                    'name' => 'Dr. Smith',
                    'email' => 'dr.smith@clinic.com',
                    'timeZone' => 'Europe/Berlin'
                ],
                'attendees' => [
                    [
                        'email' => 'john.doe@example.com',
                        'name' => 'John Doe',
                        'timeZone' => 'Europe/Berlin',
                        'language' => 'de'
                    ]
                ],
                'eventTypeId' => 123456,
                'uid' => 'booking_uid_123',
                'location' => 'Online',
                'destinationCalendar' => [
                    'id' => 456,
                    'integration' => 'google_calendar'
                ],
                'metadata' => [
                    'phone' => '+49 170 9876543'
                ]
            ]
        ];
        
        $jsonPayload = json_encode($payload);
        $signature = $this->generateWebhookSignature($jsonPayload, 'test-calcom-secret');
        
        $response = $this->postJson(
            '/api/v2/public/webhooks/calcom',
            $payload,
            [
                'X-Cal-Signature-256' => $signature,
                'Content-Type' => 'application/json'
            ]
        );
        
        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Webhook processed successfully'
            ]);
        
        // Verify job was dispatched
        Queue::assertPushed(ProcessCalcomWebhookJob::class);
    }

    /**
     * Test Cal.com webhook for different trigger events
     */
    public function test_calcom_webhook_handles_different_trigger_events()
    {
        Queue::fake();
        
        $events = [
            'BOOKING_CREATED',
            'BOOKING_RESCHEDULED',
            'BOOKING_CANCELLED',
            'BOOKING_REJECTED',
            'BOOKING_REQUESTED',
            'BOOKING_PAYMENT_INITIATED'
        ];
        
        foreach ($events as $event) {
            $payload = [
                'triggerEvent' => $event,
                'createdAt' => now()->toIso8601String(),
                'payload' => [
                    'type' => $event,
                    'uid' => "booking_{$event}_123",
                    'title' => 'Test Booking',
                    'startTime' => now()->addDay()->toIso8601String(),
                    'endTime' => now()->addDay()->addHour()->toIso8601String(),
                    'eventTypeId' => 123456
                ]
            ];
            
            if ($event === 'BOOKING_CANCELLED') {
                $payload['payload']['cancelledBy'] = 'john.doe@example.com';
                $payload['payload']['cancellationReason'] = 'Customer request';
            }
            
            $jsonPayload = json_encode($payload);
            $signature = $this->generateWebhookSignature($jsonPayload, 'test-calcom-secret');
            
            $response = $this->postJson(
                '/api/v2/public/webhooks/calcom',
                $payload,
                ['X-Cal-Signature-256' => $signature]
            );
            
            $response->assertStatus(200);
        }
        
        Queue::assertPushed(ProcessCalcomWebhookJob::class, count($events));
    }

    /**
     * Test webhook rate limiting
     */
    public function test_webhook_rate_limiting_is_enforced()
    {
        // Clear rate limiter
        RateLimiter::clear('webhook:retell');
        
        $payload = [
            'call_id' => 'test-call',
            'status' => 'completed'
        ];
        
        $jsonPayload = json_encode($payload);
        $signature = $this->generateWebhookSignature($jsonPayload, 'test-retell-secret');
        
        // Make requests up to the limit (assuming 100 per minute for public endpoints)
        for ($i = 0; $i < 100; $i++) {
            $this->postJson(
                '/api/v2/public/webhooks/retell',
                $payload,
                ['X-Retell-Signature' => $signature]
            )->assertStatus(200);
        }
        
        // Next request should be rate limited
        $response = $this->postJson(
            '/api/v2/public/webhooks/retell',
            $payload,
            ['X-Retell-Signature' => $signature]
        );
        
        $response->assertStatus(429)
            ->assertHeader('X-RateLimit-Limit')
            ->assertHeader('X-RateLimit-Remaining');
    }

    /**
     * Test webhook creates customer from call data
     */
    public function test_retell_webhook_creates_customer_from_call()
    {
        $payload = [
            'call_id' => 'retell_call_new_customer',
            'status' => 'completed',
            'phone_number' => '+49 170 5551234',
            'duration' => 240,
            'transcript' => 'Customer conversation...',
            'metadata' => [
                'customer_name' => 'New Customer',
                'customer_email' => 'new.customer@example.com',
                'customer_phone' => '+49 170 5551234'
            ]
        ];
        
        $jsonPayload = json_encode($payload);
        $signature = $this->generateWebhookSignature($jsonPayload, 'test-retell-secret');
        
        $response = $this->postJson(
            '/api/v2/public/webhooks/retell',
            $payload,
            ['X-Retell-Signature' => $signature]
        );
        
        $response->assertStatus(200);
        
        // Verify customer was created
        $this->assertDatabaseHas('customers', [
            'phone' => '+49 170 5551234',
            'email' => 'new.customer@example.com'
        ]);
    }

    /**
     * Test webhook creates appointment from call data
     */
    public function test_retell_webhook_creates_appointment_from_call()
    {
        $customer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'phone' => '+49 170 7778888'
        ]);
        
        $payload = [
            'call_id' => 'retell_call_appointment',
            'status' => 'completed',
            'phone_number' => '+49 170 7778888',
            'duration' => 300,
            'call_successful' => true,
            'metadata' => [
                'appointment_date' => now()->addDays(3)->format('Y-m-d'),
                'appointment_time' => '15:00',
                'service_id' => 123,
                'staff_id' => 456,
                'branch_id' => 789
            ]
        ];
        
        $jsonPayload = json_encode($payload);
        $signature = $this->generateWebhookSignature($jsonPayload, 'test-retell-secret');
        
        $response = $this->postJson(
            '/api/v2/public/webhooks/retell',
            $payload,
            ['X-Retell-Signature' => $signature]
        );
        
        $response->assertStatus(200);
        
        // Verify appointment was created
        $this->assertDatabaseHas('appointments', [
            'customer_id' => $customer->id,
            'status' => 'scheduled'
        ]);
    }

    /**
     * Test webhook idempotency
     */
    public function test_webhook_handles_duplicate_requests_idempotently()
    {
        $payload = [
            'call_id' => 'retell_call_idempotent',
            'status' => 'completed',
            'phone_number' => '+49 170 9999999',
            'duration' => 120
        ];
        
        $jsonPayload = json_encode($payload);
        $signature = $this->generateWebhookSignature($jsonPayload, 'test-retell-secret');
        
        // Send the same webhook multiple times
        for ($i = 0; $i < 3; $i++) {
            $response = $this->postJson(
                '/api/v2/public/webhooks/retell',
                $payload,
                ['X-Retell-Signature' => $signature]
            );
            
            $response->assertStatus(200);
        }
        
        // Verify only one call record was created
        $this->assertEquals(1, Call::where('retell_call_id', 'retell_call_idempotent')->count());
    }

    /**
     * Test webhook error handling
     */
    public function test_webhook_handles_processing_errors_gracefully()
    {
        // Simulate an error by sending invalid data that will cause processing to fail
        $payload = [
            'call_id' => 'retell_call_error',
            'status' => 'completed',
            'phone_number' => 'invalid-phone', // Invalid phone format
            'metadata' => [
                'appointment_date' => 'invalid-date' // Invalid date format
            ]
        ];
        
        $jsonPayload = json_encode($payload);
        $signature = $this->generateWebhookSignature($jsonPayload, 'test-retell-secret');
        
        Log::shouldReceive('error')->once();
        
        $response = $this->postJson(
            '/api/v2/public/webhooks/retell',
            $payload,
            ['X-Retell-Signature' => $signature]
        );
        
        // Should still return 200 to prevent webhook retries
        $response->assertStatus(200);
    }

    /**
     * Test webhook tenant identification
     */
    public function test_webhook_identifies_tenant_from_api_key()
    {
        // Create another tenant
        $otherTenant = Tenant::factory()->create(['api_key' => 'other-tenant-key']);
        $otherCompany = Company::factory()->create(['tenant_id' => $otherTenant->id]);
        
        $payload = [
            'call_id' => 'retell_call_tenant',
            'status' => 'completed',
            'phone_number' => '+49 170 1112222',
            'duration' => 90,
            'api_key' => 'other-tenant-key' // Tenant identification
        ];
        
        $jsonPayload = json_encode($payload);
        $signature = $this->generateWebhookSignature($jsonPayload, 'test-retell-secret');
        
        $response = $this->postJson(
            '/api/v2/public/webhooks/retell',
            $payload,
            ['X-Retell-Signature' => $signature]
        );
        
        $response->assertStatus(200);
        
        // Verify call was created for correct tenant
        $call = Call::where('retell_call_id', 'retell_call_tenant')->first();
        $this->assertNotNull($call);
        $this->assertEquals($otherCompany->id, $call->company_id);
    }

    /**
     * Test webhook with large payload
     */
    public function test_webhook_handles_large_payloads()
    {
        // Create a large transcript
        $largeTranscript = str_repeat('This is a very long conversation. ', 1000);
        
        $payload = [
            'call_id' => 'retell_call_large',
            'status' => 'completed',
            'phone_number' => '+49 170 3334444',
            'duration' => 1800,
            'transcript' => $largeTranscript,
            'metadata' => [
                'notes' => str_repeat('Additional notes. ', 500)
            ]
        ];
        
        $jsonPayload = json_encode($payload);
        $signature = $this->generateWebhookSignature($jsonPayload, 'test-retell-secret');
        
        $response = $this->postJson(
            '/api/v2/public/webhooks/retell',
            $payload,
            ['X-Retell-Signature' => $signature]
        );
        
        $response->assertStatus(200);
    }

    /**
     * Test webhook logging
     */
    public function test_webhook_logs_requests_and_responses()
    {
        Log::shouldReceive('info')
            ->with('Webhook received', \Mockery::any())
            ->once();
        
        Log::shouldReceive('info')
            ->with('Webhook processed', \Mockery::any())
            ->once();
        
        $payload = [
            'call_id' => 'retell_call_logging',
            'status' => 'completed',
            'phone_number' => '+49 170 5556666'
        ];
        
        $jsonPayload = json_encode($payload);
        $signature = $this->generateWebhookSignature($jsonPayload, 'test-retell-secret');
        
        $response = $this->postJson(
            '/api/v2/public/webhooks/retell',
            $payload,
            ['X-Retell-Signature' => $signature]
        );
        
        $response->assertStatus(200);
    }

    /**
     * Test webhook response time
     */
    public function test_webhook_responds_quickly()
    {
        $payload = [
            'call_id' => 'retell_call_performance',
            'status' => 'completed',
            'phone_number' => '+49 170 7778888'
        ];
        
        $jsonPayload = json_encode($payload);
        $signature = $this->generateWebhookSignature($jsonPayload, 'test-retell-secret');
        
        $startTime = microtime(true);
        
        $response = $this->postJson(
            '/api/v2/public/webhooks/retell',
            $payload,
            ['X-Retell-Signature' => $signature]
        );
        
        $endTime = microtime(true);
        $responseTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
        
        $response->assertStatus(200);
        
        // Response should be under 100ms since we're queueing the job
        $this->assertLessThan(100, $responseTime);
    }
}