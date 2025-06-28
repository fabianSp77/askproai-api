<?php

namespace Tests\Unit\MCP;

use App\Models\Company;
use App\Models\RetellConfiguration;
use App\Services\MCP\RetellConfigurationMCPServer;
use App\Services\RetellV2Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RetellConfigurationMCPServerTest extends TestCase
{
    use RefreshDatabase;

    private RetellConfigurationMCPServer $server;
    private $mockRetellService;
    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->company = Company::factory()->create([
            'retell_api_key' => 'test_api_key_123'
        ]);
        
        $this->mockRetellService = Mockery::mock(RetellV2Service::class);
        
        $this->server = new RetellConfigurationMCPServer();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_can_get_webhook_configuration()
    {
        // Arrange
        $this->app->instance(RetellV2Service::class, $this->mockRetellService);
        
        $mockAgents = [
            [
                'agent_id' => 'agent_123',
                'agent_name' => 'Test Agent',
                'webhook_url' => 'https://example.com/webhook',
                'last_updated_timestamp' => 1719156000
            ]
        ];
        
        $this->mockRetellService
            ->shouldReceive('getAgents')
            ->once()
            ->andReturn($mockAgents);
        
        // Act
        $result = $this->server->getWebhook(['company_id' => $this->company->id]);
        
        // Assert
        $this->assertArrayHasKey('agents', $result);
        $this->assertArrayHasKey('webhook_url', $result);
        $this->assertEquals($mockAgents, $result['agents']);
        $this->assertStringContains('/api/mcp/retell/custom-function', $result['webhook_url']);
    }

    /** @test */
    #[Test]
    public function it_validates_webhook_update_parameters()
    {
        // Arrange
        $params = [
            'company_id' => $this->company->id,
            'webhook_url' => 'https://api.askproai.de/api/mcp/retell/custom-function',
            'events' => ['call_started', 'call_ended'],
            'agent_ids' => ['agent_123']
        ];
        
        // Act
        $validation = $this->invokePrivateMethod($this->server, 'validateWebhookUpdate', [$params]);
        
        // Assert
        $this->assertTrue($validation['valid']);
        $this->assertNull($validation['error']);
    }

    /** @test */
    public function it_rejects_invalid_webhook_url()
    {
        // Arrange
        $params = [
            'company_id' => $this->company->id,
            'webhook_url' => 'not-a-valid-url',
            'events' => ['call_ended'],
            'agent_ids' => ['agent_123']
        ];
        
        // Act
        $validation = $this->invokePrivateMethod($this->server, 'validateWebhookUpdate', [$params]);
        
        // Assert
        $this->assertFalse($validation['valid']);
        $this->assertStringContains('Invalid webhook URL', $validation['error']);
    }

    /** @test */
    #[Test]
    public function it_can_update_webhook_configuration()
    {
        // Arrange
        $this->app->instance(RetellV2Service::class, $this->mockRetellService);
        
        $params = [
            'company_id' => $this->company->id,
            'webhook_url' => 'https://api.askproai.de/api/mcp/retell/custom-function',
            'events' => ['call_started', 'call_ended', 'call_analyzed'],
            'agent_ids' => ['agent_123', 'agent_456']
        ];
        
        $this->mockRetellService
            ->shouldReceive('updateAgent')
            ->twice()
            ->andReturn(['success' => true]);
        
        // Act
        $result = $this->server->updateWebhook($params);
        
        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals(2, $result['updated_count']);
    }

    /** @test */
    public function it_handles_webhook_update_failures_gracefully()
    {
        // Arrange
        $this->app->instance(RetellV2Service::class, $this->mockRetellService);
        
        $params = [
            'company_id' => $this->company->id,
            'webhook_url' => 'https://api.askproai.de/api/mcp/retell/custom-function',
            'events' => ['call_ended'],
            'agent_ids' => ['agent_123']
        ];
        
        $this->mockRetellService
            ->shouldReceive('updateAgent')
            ->once()
            ->andThrow(new \Exception('API Error'));
        
        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('API Error');
        
        $this->server->updateWebhook($params);
    }

    /** @test */
    #[Test]
    public function it_can_test_webhook_endpoint()
    {
        // Arrange
        $params = [
            'company_id' => $this->company->id,
            'webhook_url' => 'https://api.askproai.de/api/mcp/retell/custom-function',
            'test_payload' => [
                'event' => 'call_ended',
                'call' => [
                    'call_id' => 'test_123',
                    'from_number' => '+491234567890',
                    'to_number' => '+493083793369'
                ]
            ]
        ];
        
        // Mock HTTP response
        $this->mockHttpClient([
            'status' => 200,
            'body' => json_encode(['success' => true])
        ]);
        
        // Act
        $result = $this->server->testWebhook($params);
        
        // Assert
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('response', $result);
        $this->assertEquals(200, $result['response']['status_code']);
    }

    /** @test */
    public function it_can_deploy_custom_functions()
    {
        // Arrange
        $this->app->instance(RetellV2Service::class, $this->mockRetellService);
        
        $params = [
            'company_id' => $this->company->id,
            'agent_ids' => ['agent_123']
        ];
        
        $this->mockRetellService
            ->shouldReceive('updateAgent')
            ->once()
            ->with('agent_123', Mockery::on(function ($data) {
                return isset($data['custom_functions']) && 
                       count($data['custom_functions']) === 3;
            }))
            ->andReturn(['success' => true]);
        
        // Act
        $result = $this->server->deployCustomFunctions($params);
        
        // Assert
        $this->assertCount(1, $result['deployed']);
        $this->assertTrue($result['deployed'][0]['success']);
        $this->assertEquals(['collect_appointment_information', 'change_appointment_details', 'cancel_appointment'], 
                          $result['deployed'][0]['functions']);
    }

    /** @test */
    #[Test]
    public function it_generates_agent_prompt_template()
    {
        // Arrange
        $branch = \App\Models\Branch::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Berlin Office',
            'timezone' => 'Europe/Berlin'
        ]);
        
        $params = [
            'company_id' => $this->company->id,
            'branch_id' => $branch->id,
            'language' => 'de'
        ];
        
        // Act
        $result = $this->server->getAgentPromptTemplate($params);
        
        // Assert
        $this->assertArrayHasKey('template', $result);
        $this->assertArrayHasKey('variables', $result);
        $this->assertArrayHasKey('rendered', $result);
        $this->assertStringContains('{{company_name}}', $result['template']);
        $this->assertEquals($this->company->name, $result['variables']['company_name']);
        $this->assertStringContains($this->company->name, $result['rendered']);
    }

    /** @test */
    public function it_stores_configuration_in_database()
    {
        // Arrange
        $params = [
            'company_id' => $this->company->id,
            'agent_id' => 'agent_123',
            'webhook_url' => 'https://api.askproai.de/webhook',
            'custom_functions' => ['function1', 'function2']
        ];
        
        // Act
        $config = $this->invokePrivateMethod($this->server, 'storeConfiguration', [$params]);
        
        // Assert
        $this->assertInstanceOf(RetellConfiguration::class, $config);
        $this->assertEquals($params['agent_id'], $config->agent_id);
        $this->assertEquals($params['webhook_url'], $config->webhook_url);
        $this->assertCount(2, $config->custom_functions);
    }

    /** @test */
    #[Test]
    public function it_validates_required_events()
    {
        // Arrange
        $params = [
            'company_id' => $this->company->id,
            'webhook_url' => 'https://api.askproai.de/webhook',
            'events' => ['call_started'], // Missing required events
            'agent_ids' => ['agent_123']
        ];
        
        // Act
        $validation = $this->invokePrivateMethod($this->server, 'validateWebhookUpdate', [$params]);
        
        // Assert
        $this->assertFalse($validation['valid']);
        $this->assertStringContains('Missing required events', $validation['error']);
    }

    /** @test */
    public function it_caches_agent_configurations()
    {
        // Arrange
        Cache::spy();
        $this->app->instance(RetellV2Service::class, $this->mockRetellService);
        
        $mockAgents = [['agent_id' => 'agent_123']];
        $this->mockRetellService
            ->shouldReceive('getAgents')
            ->once()
            ->andReturn($mockAgents);
        
        // Act
        $this->server->getWebhook(['company_id' => $this->company->id]);
        
        // Assert
        Cache::shouldHaveReceived('remember')
            ->once()
            ->with(
                "mcp:retell:agents:{$this->company->id}",
                600,
                Mockery::any()
            );
    }

    /**
     * Helper method to invoke private methods for testing
     */
    private function invokePrivateMethod($object, string $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        
        return $method->invokeArgs($object, $parameters);
    }

    /**
     * Mock HTTP client for webhook testing
     */
    private function mockHttpClient(array $response)
    {
        \Illuminate\Support\Facades\Http::fake([
            '*' => \Illuminate\Support\Facades\Http::response(
                $response['body'] ?? null,
                $response['status'] ?? 200,
                $response['headers'] ?? []
            )
        ]);
    }
}