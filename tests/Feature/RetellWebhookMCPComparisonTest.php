<?php

namespace Tests\Feature;

use App\Http\Controllers\RetellWebhookController;
use App\Http\Controllers\RetellWebhookMCPController;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Customer;
use App\Models\PhoneNumber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RetellWebhookMCPComparisonTest extends TestCase
{
    use RefreshDatabase;

    protected $company;
    protected $branch;
    protected $phoneNumber;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test data
        $this->company = Company::factory()->create([
            'name' => 'Test Company',
            'is_active' => true,
            'retell_api_key' => 'test_key',
            'calcom_api_key' => 'test_calcom_key'
        ]);

        $this->branch = Branch::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Test Branch',
            'is_active' => true,
            'calcom_event_type_id' => 12345,
            'retell_agent_id' => 'agent_123'
        ]);

        $this->phoneNumber = PhoneNumber::create([
            'phone_number' => '+4930123456789',
            'branch_id' => $this->branch->id,
            'is_active' => true,
            'type' => 'main'
        ]);

        // Mock webhook signature verification
        $this->withoutMiddleware(\App\Http\Middleware\VerifyRetellSignature::class);
    }

    /**
     * Test that both controllers handle call_ended events identically
     */
    #[Test]
    public function test_call_ended_webhook_produces_same_result()
    {
        $webhookPayload = [
            'event' => 'call_ended',
            'call' => [
                'call_id' => '550e8400-e29b-41d4-a716-446655440000',
                'agent_id' => 'agent_123',
                'call_type' => 'inbound',
                'call_status' => 'ended',
                'from_number' => '+4930987654321',
                'to_number' => '+4930123456789',
                'start_timestamp' => 1703001600,
                'end_timestamp' => 1703001900,
                'duration' => 300,
                'recording_url' => 'https://example.com/recording.mp3',
                'transcript' => 'Test transcript',
                'call_summary' => 'Customer inquired about appointment',
                'retell_llm_dynamic_variables' => [
                    'datum' => '2024-12-25',
                    'uhrzeit' => '14:00',
                    'name' => 'Max Mustermann',
                    'telefon' => '+4930987654321',
                    'email' => 'max@example.com',
                    'dienstleistung' => 'Haircut',
                    'notizen' => 'First time customer'
                ]
            ]
        ];

        // Test old controller
        $oldResponse = $this->postJson('/api/retell/webhook', $webhookPayload);
        $oldResponseData = $oldResponse->json();

        // Test new MCP controller
        $mcpResponse = $this->postJson('/api/mcp/retell/webhook', $webhookPayload);
        $mcpResponseData = $mcpResponse->json();

        // Both should return success
        $this->assertEquals(200, $oldResponse->status());
        $this->assertEquals(200, $mcpResponse->status());

        // Both should have success flag
        $this->assertTrue($oldResponseData['success']);
        $this->assertTrue($mcpResponseData['success']);

        // Both should have correlation_id
        $this->assertArrayHasKey('correlation_id', $oldResponseData);
        $this->assertArrayHasKey('correlation_id', $mcpResponseData);

        // Verify same data was created
        $this->assertDatabaseHas('calls', [
            'retell_call_id' => '550e8400-e29b-41d4-a716-446655440000',
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id
        ]);
    }

    /**
     * Test inbound call handling
     */
    #[Test]
    public function test_inbound_call_produces_same_agent_response()
    {
        $inboundPayload = [
            'event' => 'call_inbound',
            'call_inbound' => [
                'from_number' => '+4930987654321',
                'to_number' => '+4930123456789'
            ],
            'call_id' => '550e8400-e29b-41d4-a716-446655440001'
        ];

        // Test old controller
        $oldResponse = $this->postJson('/api/retell/webhook', $inboundPayload);
        $oldResponseData = $oldResponse->json();

        // Test new MCP controller
        $mcpResponse = $this->postJson('/api/mcp/retell/webhook', $inboundPayload);
        $mcpResponseData = $mcpResponse->json();

        // Both should return 200
        $this->assertEquals(200, $oldResponse->status());
        $this->assertEquals(200, $mcpResponse->status());

        // Both should return same agent_id
        $this->assertEquals(
            $oldResponseData['response']['agent_id'],
            $mcpResponseData['response']['agent_id']
        );

        // Both should include company name
        $this->assertEquals(
            $oldResponseData['response']['dynamic_variables']['company_name'],
            $mcpResponseData['response']['dynamic_variables']['company_name']
        );
    }

    /**
     * Test availability check request
     */
    #[Test]
    public function test_availability_check_request_handling()
    {
        // Mock CalcomV2Service
        $this->mock(\App\Services\CalcomV2Service::class, function ($mock) {
            $mock->shouldReceive('checkAvailability')
                ->andReturn([
                    'success' => true,
                    'data' => [
                        'slots' => [
                            '2024-12-25T10:00:00',
                            '2024-12-25T11:00:00',
                            '2024-12-25T14:00:00'
                        ]
                    ]
                ]);
        });

        $availabilityPayload = [
            'event' => 'call_inbound',
            'call_inbound' => [
                'from_number' => '+4930987654321',
                'to_number' => '+4930123456789'
            ],
            'call_id' => '550e8400-e29b-41d4-a716-446655440002',
            'dynamic_variables' => [
                'check_availability' => true,
                'requested_date' => '2024-12-25',
                'event_type_id' => 12345
            ]
        ];

        // Test both controllers
        $oldResponse = $this->postJson('/api/retell/webhook', $availabilityPayload);
        $mcpResponse = $this->postJson('/api/mcp/retell/webhook', $availabilityPayload);

        $oldData = $oldResponse->json();
        $mcpData = $mcpResponse->json();

        // Both should indicate availability was checked
        $this->assertTrue($oldData['response']['dynamic_variables']['availability_checked'] ?? false);
        $this->assertTrue($mcpData['response']['dynamic_variables']['availability_checked'] ?? false);

        // Both should return slots count
        $this->assertArrayHasKey('slots_count', $oldData['response']['dynamic_variables']);
        $this->assertArrayHasKey('slots_count', $mcpData['response']['dynamic_variables']);
    }

    /**
     * Test error handling consistency
     */
    #[Test]
    public function test_error_handling_consistency()
    {
        // Test with missing required fields
        $invalidPayload = [
            'event' => 'call_ended',
            'call' => [
                // Missing call_id
                'agent_id' => 'agent_123'
            ]
        ];

        $oldResponse = $this->postJson('/api/retell/webhook', $invalidPayload);
        $mcpResponse = $this->postJson('/api/mcp/retell/webhook', $invalidPayload);

        // Both should handle validation error gracefully
        $this->assertContains($oldResponse->status(), [200, 422]);
        $this->assertContains($mcpResponse->status(), [200, 422]);
    }

    /**
     * Test rate limiting behavior
     */
    #[Test]
    public function test_rate_limiting_consistency()
    {
        Config::set('rate_limiter.webhook_limit', 2);

        $payload = [
            'event' => 'call_ended',
            'call' => [
                'call_id' => '550e8400-e29b-41d4-a716-446655440003',
                'agent_id' => 'agent_123'
            ]
        ];

        // Make requests to exhaust rate limit
        for ($i = 0; $i < 3; $i++) {
            $oldResponse = $this->postJson('/api/retell/webhook', $payload);
            $mcpResponse = $this->postJson('/api/mcp/retell/webhook', $payload);
        }

        // Both should return 429 on rate limit exceeded
        $this->assertEquals(429, $oldResponse->status());
        $this->assertEquals(429, $mcpResponse->status());
    }

    /**
     * Test duplicate webhook handling
     */
    #[Test]
    public function test_duplicate_webhook_handling()
    {
        $payload = [
            'event' => 'call_ended',
            'call' => [
                'call_id' => '550e8400-e29b-41d4-a716-446655440004',
                'agent_id' => 'agent_123',
                'from_number' => '+4930987654321',
                'to_number' => '+4930123456789'
            ]
        ];

        // First request
        $this->postJson('/api/retell/webhook', $payload);
        
        // Duplicate request
        $oldDuplicate = $this->postJson('/api/retell/webhook', $payload);
        $mcpDuplicate = $this->postJson('/api/mcp/retell/webhook', $payload);

        // Both should handle duplicates gracefully
        $this->assertEquals(200, $oldDuplicate->status());
        $this->assertEquals(200, $mcpDuplicate->status());

        // Check for duplicate flag
        $oldData = $oldDuplicate->json();
        $mcpData = $mcpDuplicate->json();

        $this->assertTrue($oldData['duplicate'] ?? $oldData['success']);
        $this->assertTrue($mcpData['duplicate'] ?? $mcpData['success']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}