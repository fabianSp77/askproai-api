<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use App\Models\Customer;
use App\Models\RetellAICallCampaign;
use App\Services\MCP\RetellAIBridgeMCPServer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;

class RetellAIMCPIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test company and user
        $this->company = Company::factory()->create([
            'retell_agent_id' => 'test_agent_123',
            'retell_api_key' => encrypt('test_api_key'),
        ]);

        $this->user = User::factory()->create([
            'company_id' => $this->company->id,
        ]);
    }

    /** @test */
    public function it_can_initiate_outbound_call_via_api()
    {
        // Mock external MCP server response
        Http::fake([
            config('services.retell_mcp.url') . '/mcp/execute' => Http::response([
                'success' => true,
                'result' => [
                    'call_id' => 'retell_call_123',
                    'status' => 'initiated',
                ],
            ], 200),
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/mcp/retell/initiate-call', [
            'to_number' => '+49123456789',
            'agent_id' => 'test_agent_123',
            'purpose' => 'follow_up',
            'dynamic_variables' => [
                'customer_name' => 'John Doe',
            ],
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'status' => 'initiated',
            ]);

        $this->assertDatabaseHas('calls', [
            'company_id' => $this->company->id,
            'to_number' => '+49123456789',
            'direction' => 'outbound',
            'status' => 'initiated',
        ]);
    }

    /** @test */
    public function it_enforces_rate_limiting_for_outbound_calls()
    {
        Http::fake();
        Sanctum::actingAs($this->user);

        // Set a low rate limit for testing
        config(['retell-mcp.rate_limits.calls_per_minute' => 2]);
        config(['retell-mcp.rate_limits.per_company_multiplier' => 1]);

        // Make first call - should succeed
        $response = $this->postJson('/api/mcp/retell/initiate-call', [
            'to_number' => '+49123456789',
            'agent_id' => 'test_agent_123',
            'purpose' => 'test',
        ]);
        $response->assertStatus(201);

        // Make second call - should succeed
        $response = $this->postJson('/api/mcp/retell/initiate-call', [
            'to_number' => '+49123456789',
            'agent_id' => 'test_agent_123',
            'purpose' => 'test',
        ]);
        $response->assertStatus(201);

        // Make third call - should be rate limited
        $response = $this->postJson('/api/mcp/retell/initiate-call', [
            'to_number' => '+49123456789',
            'agent_id' => 'test_agent_123',
            'purpose' => 'test',
        ]);
        
        $response->assertStatus(429)
            ->assertHeader('Retry-After');
    }

    /** @test */
    public function it_can_create_and_start_call_campaign()
    {
        Queue::fake();
        Sanctum::actingAs($this->user);

        // Create test customers
        Customer::factory()->count(5)->create([
            'company_id' => $this->company->id,
            'phone' => '+49123456789',
        ]);

        // Create campaign
        $response = $this->postJson('/api/mcp/retell/campaign/create', [
            'name' => 'Test Campaign',
            'description' => 'Test campaign description',
            'agent_id' => 'test_agent_123',
            'target_type' => 'all_customers',
            'schedule_type' => 'immediate',
            'dynamic_variables' => [
                'greeting' => 'Hello',
            ],
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'total_targets' => 5,
                'status' => 'draft',
            ]);

        $campaignId = $response->json('campaign_id');

        // Start campaign
        $response = $this->postJson("/api/mcp/retell/campaign/{$campaignId}/start");
        
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'status' => 'running',
            ]);

        Queue::assertPushed(\App\Jobs\ProcessRetellAICampaignJob::class);
    }

    /** @test */
    public function it_validates_webhook_signature()
    {
        $payload = [
            'callId' => 'test_call_123',
            'params' => ['test' => 'data'],
            'timestamp' => now()->toISOString(),
        ];

        $secret = 'test_webhook_secret';
        config(['retell-mcp.security.webhook_secret' => $secret]);

        $timestamp = time();
        $payloadString = json_encode($payload);
        $signature = hash_hmac('sha256', $timestamp . '.' . $payloadString, $secret);

        // Valid signature should pass
        $response = $this->postJson('/api/mcp/retell/call-created', $payload, [
            'X-MCP-Signature' => $signature,
            'X-MCP-Timestamp' => $timestamp,
        ]);

        $response->assertStatus(200);

        // Invalid signature should fail
        $response = $this->postJson('/api/mcp/retell/call-created', $payload, [
            'X-MCP-Signature' => 'invalid_signature',
            'X-MCP-Timestamp' => $timestamp,
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function it_handles_circuit_breaker_when_mcp_server_is_down()
    {
        Sanctum::actingAs($this->user);

        // Simulate MCP server being down
        Http::fake([
            config('services.retell_mcp.url') . '/mcp/execute' => Http::response(null, 503),
        ]);

        // Make multiple failed requests to trigger circuit breaker
        for ($i = 0; $i < 6; $i++) {
            $response = $this->postJson('/api/mcp/retell/initiate-call', [
                'to_number' => '+49123456789',
                'agent_id' => 'test_agent_123',
                'purpose' => 'test',
            ]);
        }

        // Circuit breaker should now be open
        $response = $this->postJson('/api/mcp/retell/initiate-call', [
            'to_number' => '+49123456789',
            'agent_id' => 'test_agent_123',
            'purpose' => 'test',
        ]);

        $response->assertStatus(503)
            ->assertJsonFragment([
                'error' => 'Service temporarily unavailable',
            ]);
    }

    /** @test */
    public function it_can_test_voice_configuration()
    {
        Http::fake([
            config('services.retell_mcp.url') . '/mcp/execute' => Http::response([
                'success' => true,
                'result' => [
                    'call_id' => 'test_call_123',
                    'status' => 'initiated',
                ],
            ], 200),
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/mcp/retell/test-voice', [
            'agent_id' => 'test_agent_123',
            'test_number' => '+49123456789',
            'test_scenario' => 'greeting',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'test_mode' => true,
            ]);
    }

    /** @test */
    public function it_can_pause_and_resume_campaign()
    {
        Sanctum::actingAs($this->user);

        $campaign = RetellAICallCampaign::factory()->create([
            'company_id' => $this->company->id,
            'status' => 'running',
        ]);

        // Pause campaign
        $response = $this->postJson("/api/mcp/retell/campaign/{$campaign->id}/pause");
        
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'status' => 'paused',
            ]);

        $campaign->refresh();
        $this->assertEquals('paused', $campaign->status);

        // Resume campaign
        Queue::fake();
        
        $response = $this->postJson("/api/mcp/retell/campaign/{$campaign->id}/resume");
        
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'status' => 'running',
            ]);

        Queue::assertPushed(\App\Jobs\ProcessRetellAICampaignJob::class);
    }

    /** @test */
    public function it_returns_health_check_with_circuit_breaker_status()
    {
        Sanctum::actingAs($this->user);

        Http::fake([
            config('services.retell_mcp.url') . '/health' => Http::response([
                'status' => 'healthy',
                'service' => 'retell-mcp-server',
            ], 200),
        ]);

        $response = $this->getJson('/api/mcp/retell/health');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'bridge_status',
                'circuit_breaker' => [
                    'status',
                    'failures',
                ],
            ]);
    }
}