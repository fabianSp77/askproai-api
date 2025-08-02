<?php

namespace Tests\Unit\Services\MCP;

use Tests\TestCase;
use App\Services\MCP\RetellAIBridgeMCPServer;
use App\Services\RetellMCPServer;
use App\Services\PhoneNumberResolver;
use App\Models\Company;
use App\Models\Customer;
use App\Models\RetellAICallCampaign;
use App\Exceptions\MCPException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Mockery;

class RetellAIBridgeMCPServerTest extends TestCase
{
    protected RetellAIBridgeMCPServer $bridgeServer;
    protected $mockRetellMCPServer;
    protected $mockPhoneResolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockRetellMCPServer = Mockery::mock(RetellMCPServer::class);
        $this->mockPhoneResolver = Mockery::mock(PhoneNumberResolver::class);

        $this->bridgeServer = new RetellAIBridgeMCPServer(
            $this->mockRetellMCPServer,
            $this->mockPhoneResolver
        );
    }

    /** @test */
    public function it_normalizes_phone_numbers_correctly()
    {
        $testCases = [
            '+49123456789' => '+49123456789',        // Already normalized
            '0123456789' => '+49123456789',          // German number without country code
            '49123456789' => '+49123456789',         // Missing + prefix
            '0049123456789' => '+49123456789',       // International format
            '+1234567890' => '+1234567890',          // Non-German number
        ];

        foreach ($testCases as $input => $expected) {
            $reflection = new \ReflectionClass($this->bridgeServer);
            $method = $reflection->getMethod('normalizePhoneNumber');
            $method->setAccessible(true);

            $result = $method->invoke($this->bridgeServer, $input);
            
            $this->assertEquals($expected, $result, "Failed to normalize: {$input}");
        }
    }

    /** @test */
    public function it_validates_required_parameters()
    {
        $this->expectException(MCPException::class);
        $this->expectExceptionMessage('Missing required parameter: to_number');

        Http::fake();

        $company = Company::factory()->make(['id' => 1]);
        
        $this->bridgeServer->createOutboundCall([
            'company_id' => $company->id,
            'agent_id' => 'test_agent',
            // Missing 'to_number'
        ]);
    }

    /** @test */
    public function it_builds_dynamic_variables_for_appointment_reminder()
    {
        $customer = Customer::factory()->make(['id' => 1]);
        $company = Company::factory()->make(['id' => 1, 'name' => 'Test Company']);

        auth()->login(new \App\Models\User(['company' => $company]));

        // Create mock CallInitiatorWidget to test dynamic variables
        $widget = new \App\Livewire\CallInitiatorWidget();
        $widget->customerId = $customer->id;
        $widget->purpose = 'appointment_reminder';

        $reflection = new \ReflectionClass($widget);
        $method = $reflection->getMethod('buildDynamicVariables');
        $method->setAccessible(true);

        $variables = $method->invoke($widget);

        $this->assertArrayHasKey('company_name', $variables);
        $this->assertArrayHasKey('current_date', $variables);
        $this->assertArrayHasKey('current_time', $variables);
        $this->assertEquals('Test Company', $variables['company_name']);
    }

    /** @test */
    public function it_creates_call_record_with_correct_data()
    {
        Http::fake([
            '*' => Http::response([
                'success' => true,
                'result' => [
                    'call_id' => 'retell_123',
                    'status' => 'initiated',
                ],
            ], 200),
        ]);

        $company = Company::factory()->create([
            'retell_agent_id' => 'agent_123',
        ]);

        $result = $this->bridgeServer->createOutboundCall([
            'company_id' => $company->id,
            'to_number' => '+49123456789',
            'agent_id' => 'agent_123',
            'purpose' => 'test',
        ]);

        $this->assertTrue($result['success']);
        $this->assertNotNull($result['call_id']);
        $this->assertEquals('initiated', $result['status']);

        $this->assertDatabaseHas('calls', [
            'company_id' => $company->id,
            'retell_call_id' => 'retell_123',
            'direction' => 'outbound',
            'status' => 'initiated',
        ]);
    }

    /** @test */
    public function it_handles_campaign_target_filtering()
    {
        $company = Company::factory()->create();
        
        // Create customers with different activity levels
        $activeCustomer = Customer::factory()->create([
            'company_id' => $company->id,
            'phone' => '+49111111111',
        ]);
        
        $inactiveCustomer = Customer::factory()->create([
            'company_id' => $company->id,
            'phone' => '+49222222222',
        ]);

        // Create appointment for active customer
        \App\Models\Appointment::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $activeCustomer->id,
            'created_at' => now()->subDays(30),
        ]);

        // Create appointment for inactive customer (older than 90 days)
        \App\Models\Appointment::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $inactiveCustomer->id,
            'created_at' => now()->subDays(120),
        ]);

        $campaign = RetellAICallCampaign::factory()->create([
            'company_id' => $company->id,
            'target_type' => 'inactive_customers',
            'target_criteria' => ['inactive_days' => 90],
        ]);

        $reflection = new \ReflectionClass($this->bridgeServer);
        $method = $reflection->getMethod('getTargetCustomers');
        $method->setAccessible(true);

        $targets = $method->invoke($this->bridgeServer, $campaign);

        $this->assertEquals(1, $targets->count());
        $this->assertEquals($inactiveCustomer->id, $targets->first()->id);
    }

    /** @test */
    public function it_caches_external_mcp_responses()
    {
        Cache::flush();

        Http::fake([
            '*' => Http::sequence()
                ->push(['success' => true, 'tools' => ['tool1', 'tool2']], 200)
                ->push(['success' => false], 500), // Second call would fail
        ]);

        // First call should hit the API
        $result1 = $this->bridgeServer->getAvailableTools();
        $this->assertTrue($result1['success']);
        $this->assertEquals(['tool1', 'tool2'], $result1['tools']);

        // Second call should use cache and not fail
        $result2 = $this->bridgeServer->getAvailableTools();
        $this->assertTrue($result2['success']);
        $this->assertEquals(['tool1', 'tool2'], $result2['tools']);

        // Verify only one HTTP request was made
        Http::assertSentCount(1);
    }

    /** @test */
    public function it_handles_webhook_creation_for_outbound_calls()
    {
        $callData = [
            'call_id' => 'test_123',
            'retell_call_id' => 'retell_123',
            'to_number' => '+49123456789',
            'metadata' => ['test' => 'data'],
        ];

        // Test webhook would be called with correct data
        Http::fake();

        $company = Company::factory()->create();
        
        $this->bridgeServer->createOutboundCall([
            'company_id' => $company->id,
            'to_number' => '+49123456789',
            'agent_id' => 'agent_123',
        ]);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/mcp/execute') &&
                   $request->hasHeader('Content-Type', 'application/json') &&
                   $request->hasHeader('X-Request-ID') &&
                   $request->hasHeader('X-Correlation-ID');
        });
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}