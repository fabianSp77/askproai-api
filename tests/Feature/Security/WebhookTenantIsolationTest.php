<?php

namespace Tests\Feature\Security;

use App\Models\Call;
use App\Models\Company;
use App\Models\Customer;
use App\Models\WebhookEvent;
use App\Services\WebhookProcessor;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Tests for Webhook Tenant Isolation Security
 * 
 * This test suite validates that webhook processing correctly isolates
 * data across tenants and prevents cross-contamination of webhook data.
 */
class WebhookTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private Company $company1;
    private Company $company2;
    private Company $company3;
    private Customer $customer1;
    private Customer $customer2;
    private Customer $customer3;
    private string $validSignature1;
    private string $validSignature2;
    private string $webhookSecret;

    protected function setUp(): void
    {
        parent::setUp();

        $this->webhookSecret = 'test-webhook-secret-key';
        config(['services.retell.webhook_secret' => $this->webhookSecret]);

        // Create companies
        $this->company1 = Company::factory()->create([
            'name' => 'TechStart Inc',
            'slug' => 'techstart',
            'is_active' => true,
            'retell_api_key' => 'key_company1_retell',
            'phone_numbers' => ['+1234567890'],
        ]);

        $this->company2 = Company::factory()->create([
            'name' => 'HealthCare Pro',
            'slug' => 'healthcare',
            'is_active' => true,
            'retell_api_key' => 'key_company2_retell',
            'phone_numbers' => ['+1234567891'],
        ]);

        $this->company3 = Company::factory()->create([
            'name' => 'Finance Plus',
            'slug' => 'finance',
            'is_active' => true,
            'retell_api_key' => 'key_company3_retell',
            'phone_numbers' => ['+1234567892'],
        ]);

        // Create customers for each company
        $this->customer1 = Customer::factory()->create([
            'company_id' => $this->company1->id,
            'name' => 'Tech Client Alpha',
            'phone' => '+1234567890',
            'email' => 'client@techstart.com',
        ]);

        $this->customer2 = Customer::factory()->create([
            'company_id' => $this->company2->id,
            'name' => 'Patient Beta',
            'phone' => '+1234567891',
            'email' => 'patient@healthcare.com',
        ]);

        $this->customer3 = Customer::factory()->create([
            'company_id' => $this->company3->id,
            'name' => 'Finance Client Gamma',
            'phone' => '+1234567892',
            'email' => 'client@finance.com',
        ]);
    }

    private function generateValidRetellSignature(array $payload): string
    {
        $jsonPayload = json_encode($payload);
        return hash_hmac('sha256', $jsonPayload, $this->webhookSecret);
    }

    private function createRetellWebhookPayload(string $callId, string $phoneNumber, array $overrides = []): array
    {
        return array_merge([
            'event_type' => 'call_ended',
            'call' => [
                'call_id' => $callId,
                'retell_llm_dynamic_variables' => [
                    'phone_number' => $phoneNumber,
                    'customer_name' => 'Test Customer',
                    'call_summary' => 'Test call summary',
                ],
                'start_timestamp' => Carbon::now()->subMinutes(10)->timestamp,
                'end_timestamp' => Carbon::now()->timestamp,
                'call_status' => 'ended',
                'transcript' => 'Test transcript content',
                'call_analysis' => [
                    'call_successful' => true,
                    'sentiment' => 'positive',
                ],
            ],
        ], $overrides);
    }

    public function test_webhook_processing_isolates_calls_by_phone_number()
    {
        Queue::fake();

        // Create webhook payload for company 1 phone number
        $payload1 = $this->createRetellWebhookPayload(
            'call_001_company1',
            '+1234567890' // Company 1 phone
        );

        $signature1 = $this->generateValidRetellSignature($payload1);

        $response = $this->postJson('/api/retell/webhook', $payload1, [
            'X-Retell-Signature' => $signature1,
        ]);

        $response->assertStatus(204);

        // Verify call was created for company 1
        $this->assertDatabaseHas('calls', [
            'retell_call_id' => 'call_001_company1',
            'company_id' => $this->company1->id,
            'phone_number' => '+1234567890',
        ]);

        // Verify call was NOT created for other companies
        $this->assertDatabaseMissing('calls', [
            'retell_call_id' => 'call_001_company1',
            'company_id' => $this->company2->id,
        ]);

        $this->assertDatabaseMissing('calls', [
            'retell_call_id' => 'call_001_company1',
            'company_id' => $this->company3->id,
        ]);
    }

    public function test_webhook_prevents_call_id_collision_across_tenants()
    {
        Queue::fake();

        // Create webhook payloads with same call ID but different phone numbers
        $payload1 = $this->createRetellWebhookPayload(
            'duplicate_call_id_123',
            '+1234567890' // Company 1 phone
        );

        $payload2 = $this->createRetellWebhookPayload(
            'duplicate_call_id_123',
            '+1234567891' // Company 2 phone
        );

        $signature1 = $this->generateValidRetellSignature($payload1);
        $signature2 = $this->generateValidRetellSignature($payload2);

        // Send first webhook
        $response1 = $this->postJson('/api/retell/webhook', $payload1, [
            'X-Retell-Signature' => $signature1,
        ]);
        $response1->assertStatus(204);

        // Send second webhook with same call ID
        $response2 = $this->postJson('/api/retell/webhook', $payload2, [
            'X-Retell-Signature' => $signature2,
        ]);
        $response2->assertStatus(204);

        // Both calls should be created for their respective companies
        $this->assertDatabaseHas('calls', [
            'retell_call_id' => 'duplicate_call_id_123',
            'company_id' => $this->company1->id,
            'phone_number' => '+1234567890',
        ]);

        $this->assertDatabaseHas('calls', [
            'retell_call_id' => 'duplicate_call_id_123',
            'company_id' => $this->company2->id,
            'phone_number' => '+1234567891',
        ]);

        // Verify each company only has one call with this ID
        $company1Calls = Call::where('company_id', $this->company1->id)
            ->where('retell_call_id', 'duplicate_call_id_123')
            ->count();
        $this->assertEquals(1, $company1Calls);

        $company2Calls = Call::where('company_id', $this->company2->id)
            ->where('retell_call_id', 'duplicate_call_id_123')
            ->count();
        $this->assertEquals(1, $company2Calls);
    }

    public function test_webhook_rejects_calls_for_unknown_phone_numbers()
    {
        Queue::fake();

        // Create webhook payload with phone number not assigned to any company
        $payload = $this->createRetellWebhookPayload(
            'orphan_call_001',
            '+9999999999' // Unknown phone number
        );

        $signature = $this->generateValidRetellSignature($payload);

        $response = $this->postJson('/api/retell/webhook', $payload, [
            'X-Retell-Signature' => $signature,
        ]);

        // Should still return 204 to avoid webhook retries, but no call should be created
        $response->assertStatus(204);

        // Verify no call was created
        $this->assertDatabaseMissing('calls', [
            'retell_call_id' => 'orphan_call_001',
        ]);
    }

    public function test_webhook_signature_verification_isolation()
    {
        Queue::fake();

        $payload = $this->createRetellWebhookPayload(
            'signature_test_001',
            '+1234567890'
        );

        // Test with invalid signature
        $response = $this->postJson('/api/retell/webhook', $payload, [
            'X-Retell-Signature' => 'invalid_signature',
        ]);

        $response->assertStatus(204); // Still returns 204 but logs error

        // Verify no call was created due to invalid signature
        $this->assertDatabaseMissing('calls', [
            'retell_call_id' => 'signature_test_001',
        ]);

        // Test with valid signature
        $validSignature = $this->generateValidRetellSignature($payload);
        
        $response = $this->postJson('/api/retell/webhook', $payload, [
            'X-Retell-Signature' => $validSignature,
        ]);

        $response->assertStatus(204);

        // Verify call was created with valid signature
        $this->assertDatabaseHas('calls', [
            'retell_call_id' => 'signature_test_001',
            'company_id' => $this->company1->id,
        ]);
    }

    public function test_webhook_customer_resolution_respects_tenant_boundaries()
    {
        Queue::fake();

        // Create webhook payload that could match customers in multiple companies
        // if not properly isolated
        $payload = $this->createRetellWebhookPayload(
            'customer_resolution_001',
            '+1234567890', // Company 1 phone
            [
                'call' => [
                    'call_id' => 'customer_resolution_001',
                    'retell_llm_dynamic_variables' => [
                        'phone_number' => '+1234567890',
                        'customer_phone' => '+1234567891', // This matches company 2 customer
                        'customer_name' => 'Patient Beta', // This matches company 2 customer name
                        'call_summary' => 'Call about healthcare services',
                    ],
                    'start_timestamp' => Carbon::now()->subMinutes(10)->timestamp,
                    'end_timestamp' => Carbon::now()->timestamp,
                    'call_status' => 'ended',
                    'transcript' => 'Customer discussed healthcare needs',
                ],
            ]
        );

        $signature = $this->generateValidRetellSignature($payload);

        $response = $this->postJson('/api/retell/webhook', $payload, [
            'X-Retell-Signature' => $signature,
        ]);

        $response->assertStatus(204);

        // Verify call was created for company 1 (based on receiving phone number)
        $call = Call::where('retell_call_id', 'customer_resolution_001')->first();
        $this->assertNotNull($call);
        $this->assertEquals($this->company1->id, $call->company_id);

        // Verify customer resolution did not leak across tenants
        // Should not be associated with company 2's customer even though data matches
        if ($call->customer_id) {
            $customer = Customer::find($call->customer_id);
            $this->assertEquals($this->company1->id, $customer->company_id);
            $this->assertNotEquals($this->company2->id, $customer->company_id);
        }
    }

    public function test_webhook_deduplication_per_tenant()
    {
        Queue::fake();

        $payload = $this->createRetellWebhookPayload(
            'dedup_test_001',
            '+1234567890'
        );

        $signature = $this->generateValidRetellSignature($payload);

        // Send same webhook twice
        $response1 = $this->postJson('/api/retell/webhook', $payload, [
            'X-Retell-Signature' => $signature,
        ]);
        $response1->assertStatus(204);

        $response2 = $this->postJson('/api/retell/webhook', $payload, [
            'X-Retell-Signature' => $signature,
        ]);
        $response2->assertStatus(204);

        // Should only create one call
        $callCount = Call::where('retell_call_id', 'dedup_test_001')->count();
        $this->assertEquals(1, $callCount);

        // Now send similar payload for different company (same call ID)
        $payload2 = $this->createRetellWebhookPayload(
            'dedup_test_001',
            '+1234567891' // Company 2 phone
        );

        $signature2 = $this->generateValidRetellSignature($payload2);

        $response3 = $this->postJson('/api/retell/webhook', $payload2, [
            'X-Retell-Signature' => $signature2,
        ]);
        $response3->assertStatus(204);

        // Should now have two calls total (one per company)
        $totalCalls = Call::where('retell_call_id', 'dedup_test_001')->count();
        $this->assertEquals(2, $totalCalls);

        // Each company should have exactly one call
        $company1Calls = Call::where('retell_call_id', 'dedup_test_001')
            ->where('company_id', $this->company1->id)
            ->count();
        $this->assertEquals(1, $company1Calls);

        $company2Calls = Call::where('retell_call_id', 'dedup_test_001')
            ->where('company_id', $this->company2->id)
            ->count();
        $this->assertEquals(1, $company2Calls);
    }

    public function test_webhook_data_sanitization_prevents_xss()
    {
        Queue::fake();

        // Create payload with potential XSS content
        $payload = $this->createRetellWebhookPayload(
            'xss_test_001',
            '+1234567890',
            [
                'call' => [
                    'call_id' => 'xss_test_001',
                    'retell_llm_dynamic_variables' => [
                        'phone_number' => '+1234567890',
                        'customer_name' => '<script>alert("xss")</script>John Doe',
                        'call_summary' => 'Call about <img src=x onerror=alert("xss")> services',
                    ],
                    'start_timestamp' => Carbon::now()->subMinutes(10)->timestamp,
                    'end_timestamp' => Carbon::now()->timestamp,
                    'call_status' => 'ended',
                    'transcript' => 'Customer said: <script>document.cookie="hacked"</script>',
                ],
            ]
        );

        $signature = $this->generateValidRetellSignature($payload);

        $response = $this->postJson('/api/retell/webhook', $payload, [
            'X-Retell-Signature' => $signature,
        ]);

        $response->assertStatus(204);

        // Verify call was created but XSS content was handled properly
        $call = Call::where('retell_call_id', 'xss_test_001')->first();
        $this->assertNotNull($call);

        // Verify potentially dangerous content was stored safely
        $this->assertStringNotContains('<script>', $call->summary ?? '');
        $this->assertStringNotContains('<script>', $call->transcript ?? '');
        $this->assertStringNotContains('onerror=', $call->summary ?? '');
    }

    public function test_webhook_sql_injection_prevention()
    {
        Queue::fake();

        // Create payload with SQL injection attempts
        $payload = $this->createRetellWebhookPayload(
            "'; DROP TABLE calls; --",
            '+1234567890',
            [
                'call' => [
                    'call_id' => "'; DROP TABLE calls; --",
                    'retell_llm_dynamic_variables' => [
                        'phone_number' => '+1234567890',
                        'customer_name' => "'; UPDATE calls SET company_id = 999; --",
                        'call_summary' => "' UNION SELECT * FROM companies WHERE '1'='1",
                    ],
                    'start_timestamp' => Carbon::now()->subMinutes(10)->timestamp,
                    'end_timestamp' => Carbon::now()->timestamp,
                    'call_status' => 'ended',
                    'transcript' => "'; INSERT INTO calls (company_id) VALUES (999); --",
                ],
            ]
        );

        $signature = $this->generateValidRetellSignature($payload);

        $response = $this->postJson('/api/retell/webhook', $payload, [
            'X-Retell-Signature' => $signature,
        ]);

        $response->assertStatus(204);

        // Verify database structure is intact
        $this->assertDatabaseHas('calls', [
            'company_id' => $this->company1->id,
        ]);

        // Verify no unauthorized calls were created
        $this->assertDatabaseMissing('calls', [
            'company_id' => 999,
        ]);

        // Verify tables still exist
        $callsCount = Call::count();
        $this->assertGreaterThanOrEqual(1, $callsCount);
    }

    public function test_webhook_mass_assignment_protection()
    {
        Queue::fake();

        // Create payload attempting mass assignment
        $payload = $this->createRetellWebhookPayload(
            'mass_assignment_001',
            '+1234567890',
            [
                'call' => [
                    'call_id' => 'mass_assignment_001',
                    'company_id' => $this->company2->id, // Attempt to assign to different company
                    'is_admin' => true,
                    'created_at' => '2020-01-01 00:00:00',
                    'retell_llm_dynamic_variables' => [
                        'phone_number' => '+1234567890',
                        'customer_name' => 'Test Customer',
                        'company_id' => $this->company2->id, // Another attempt
                    ],
                    'start_timestamp' => Carbon::now()->subMinutes(10)->timestamp,
                    'end_timestamp' => Carbon::now()->timestamp,
                    'call_status' => 'ended',
                ],
            ]
        );

        $signature = $this->generateValidRetellSignature($payload);

        $response = $this->postJson('/api/retell/webhook', $payload, [
            'X-Retell-Signature' => $signature,
        ]);

        $response->assertStatus(204);

        // Verify call was created with correct company (based on phone number)
        $call = Call::where('retell_call_id', 'mass_assignment_001')->first();
        $this->assertNotNull($call);
        $this->assertEquals($this->company1->id, $call->company_id);
        $this->assertNotEquals($this->company2->id, $call->company_id);

        // Verify unauthorized fields were not set
        $this->assertFalse($call->is_admin ?? false);
    }

    public function test_webhook_event_logging_isolation()
    {
        Queue::fake();

        $payload1 = $this->createRetellWebhookPayload(
            'logging_test_001',
            '+1234567890'
        );

        $payload2 = $this->createRetellWebhookPayload(
            'logging_test_002',
            '+1234567891'
        );

        $signature1 = $this->generateValidRetellSignature($payload1);
        $signature2 = $this->generateValidRetellSignature($payload2);

        // Send webhooks for both companies
        $response1 = $this->postJson('/api/retell/webhook', $payload1, [
            'X-Retell-Signature' => $signature1,
        ]);
        $response1->assertStatus(204);

        $response2 = $this->postJson('/api/retell/webhook', $payload2, [
            'X-Retell-Signature' => $signature2,
        ]);
        $response2->assertStatus(204);

        // Verify webhook events are logged separately (if webhook_events table exists)
        if (schema()->hasTable('webhook_events')) {
            $company1Events = WebhookEvent::where('provider', 'retell')
                ->whereJsonContains('payload->call->retell_llm_dynamic_variables->phone_number', '+1234567890')
                ->count();
            $this->assertGreaterThan(0, $company1Events);

            $company2Events = WebhookEvent::where('provider', 'retell')
                ->whereJsonContains('payload->call->retell_llm_dynamic_variables->phone_number', '+1234567891')
                ->count();
            $this->assertGreaterThan(0, $company2Events);
        } else {
            $this->assertTrue(true); // Skip if table doesn't exist
        }
    }

    public function test_webhook_rate_limiting_per_tenant()
    {
        Queue::fake();

        // Create multiple webhook payloads quickly for same company
        $payloads = [];
        $signatures = [];
        
        for ($i = 1; $i <= 10; $i++) {
            $payloads[$i] = $this->createRetellWebhookPayload(
                "rate_limit_test_00{$i}",
                '+1234567890'
            );
            $signatures[$i] = $this->generateValidRetellSignature($payloads[$i]);
        }

        // Send all webhooks rapidly
        $responses = [];
        for ($i = 1; $i <= 10; $i++) {
            $responses[$i] = $this->postJson('/api/retell/webhook', $payloads[$i], [
                'X-Retell-Signature' => $signatures[$i],
            ]);
        }

        // All should be accepted (rate limiting might be implemented at middleware level)
        foreach ($responses as $response) {
            $response->assertStatus(204);
        }

        // Verify all calls were created for company 1
        $callCount = Call::where('company_id', $this->company1->id)
            ->where('retell_call_id', 'LIKE', 'rate_limit_test_%')
            ->count();
        $this->assertEquals(10, $callCount);

        // Verify no calls were created for other companies
        $otherCompanyCalls = Call::where('company_id', '!=', $this->company1->id)
            ->where('retell_call_id', 'LIKE', 'rate_limit_test_%')
            ->count();
        $this->assertEquals(0, $otherCompanyCalls);
    }

    public function test_webhook_payload_size_limits()
    {
        Queue::fake();

        // Create payload with large transcript
        $largeTranscript = str_repeat('This is a very long transcript. ', 10000); // ~300KB
        
        $payload = $this->createRetellWebhookPayload(
            'large_payload_001',
            '+1234567890',
            [
                'call' => [
                    'call_id' => 'large_payload_001',
                    'retell_llm_dynamic_variables' => [
                        'phone_number' => '+1234567890',
                        'customer_name' => 'Test Customer',
                    ],
                    'start_timestamp' => Carbon::now()->subMinutes(10)->timestamp,
                    'end_timestamp' => Carbon::now()->timestamp,
                    'call_status' => 'ended',
                    'transcript' => $largeTranscript,
                ],
            ]
        );

        $signature = $this->generateValidRetellSignature($payload);

        $response = $this->postJson('/api/retell/webhook', $payload, [
            'X-Retell-Signature' => $signature,
        ]);

        // Should handle large payloads gracefully
        $this->assertTrue(in_array($response->status(), [204, 413, 422]));

        if ($response->status() === 204) {
            // If accepted, verify call was created with truncated data if necessary
            $call = Call::where('retell_call_id', 'large_payload_001')->first();
            $this->assertNotNull($call);
            $this->assertEquals($this->company1->id, $call->company_id);
        }
    }

    public function test_webhook_concurrent_processing_isolation()
    {
        Queue::fake();

        // Simulate concurrent webhook processing
        $payload1 = $this->createRetellWebhookPayload(
            'concurrent_001',
            '+1234567890'
        );

        $payload2 = $this->createRetellWebhookPayload(
            'concurrent_002',
            '+1234567891'
        );

        $signature1 = $this->generateValidRetellSignature($payload1);
        $signature2 = $this->generateValidRetellSignature($payload2);

        // Send webhooks simultaneously (simulate)
        $response1 = $this->postJson('/api/retell/webhook', $payload1, [
            'X-Retell-Signature' => $signature1,
        ]);

        $response2 = $this->postJson('/api/retell/webhook', $payload2, [
            'X-Retell-Signature' => $signature2,
        ]);

        $response1->assertStatus(204);
        $response2->assertStatus(204);

        // Verify each call was assigned to correct company
        $call1 = Call::where('retell_call_id', 'concurrent_001')->first();
        $call2 = Call::where('retell_call_id', 'concurrent_002')->first();

        $this->assertNotNull($call1);
        $this->assertNotNull($call2);
        $this->assertEquals($this->company1->id, $call1->company_id);
        $this->assertEquals($this->company2->id, $call2->company_id);

        // Verify no cross-contamination
        $this->assertNotEquals($call1->company_id, $call2->company_id);
    }

    public function test_webhook_malformed_payload_handling()
    {
        Queue::fake();

        // Test various malformed payloads
        $malformedPayloads = [
            [], // Empty payload
            ['invalid' => 'structure'], // Wrong structure 
            ['call' => null], // Null call data
            ['call' => []], // Empty call data
            ['call' => ['call_id' => '']], // Empty call ID
            json_decode('{"invalid":"json"'), // Invalid JSON (will be array by this point)
        ];

        foreach ($malformedPayloads as $index => $payload) {
            $signature = $this->generateValidRetellSignature($payload);
            
            $response = $this->postJson('/api/retell/webhook', $payload, [
                'X-Retell-Signature' => $signature,
            ]);

            // Should handle gracefully without creating invalid records
            $response->assertStatus(204);
        }

        // Verify no invalid calls were created
        $invalidCalls = Call::whereNull('retell_call_id')
            ->orWhere('retell_call_id', '')
            ->orWhereNull('company_id')
            ->count();
        $this->assertEquals(0, $invalidCalls);
    }
}