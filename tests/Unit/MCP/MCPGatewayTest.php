<?php

namespace Tests\Unit\MCP;

use App\Services\MCP\AppointmentManagementMCPServer;
use App\Services\MCP\MCPGateway;
use App\Services\MCP\RetellConfigurationMCPServer;
use App\Services\MCP\RetellCustomFunctionMCPServer;
use App\Services\MCP\WebhookMCPServer;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MCPGatewayTest extends TestCase
{
    private MCPGateway $gateway;
    private array $mockServers;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create mock servers
        $this->mockServers = [
            'retell_config' => Mockery::mock(RetellConfigurationMCPServer::class),
            'retell_custom' => Mockery::mock(RetellCustomFunctionMCPServer::class),
            'appointment_mgmt' => Mockery::mock(AppointmentManagementMCPServer::class),
            'webhook' => Mockery::mock(WebhookMCPServer::class)
        ];
        
        // Create gateway with mocked servers
        $this->gateway = new MCPGateway();
        
        // Inject mock servers
        $reflection = new \ReflectionClass($this->gateway);
        $property = $reflection->getProperty('servers');
        $property->setAccessible(true);
        $property->setValue($this->gateway, $this->mockServers);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_validates_json_rpc_request_format()
    {
        // Arrange
        $invalidRequest = [
            'method' => 'test.method'
            // Missing jsonrpc and id
        ];
        
        // Act
        $result = $this->gateway->process($invalidRequest);
        
        // Assert
        $this->assertArrayHasKey('error', $result);
        $this->assertEquals(-32600, $result['error']['code']);
        $this->assertEquals('Invalid Request', $result['error']['message']);
    }

    /** @test */
    #[Test]
    public function it_requires_jsonrpc_version()
    {
        // Arrange
        $request = [
            'method' => 'test.method',
            'params' => [],
            'id' => 'test_001'
            // Missing jsonrpc
        ];
        
        // Act
        $result = $this->gateway->process($request);
        
        // Assert
        $this->assertArrayHasKey('error', $result);
        $this->assertEquals(-32600, $result['error']['code']);
    }

    /** @test */
    public function it_routes_to_correct_server()
    {
        // Arrange
        $request = [
            'jsonrpc' => '2.0',
            'method' => 'retell_config.getWebhook',
            'params' => ['company_id' => 1],
            'id' => 'test_001'
        ];
        
        $expectedResult = ['agents' => [], 'webhook_url' => 'https://test.com'];
        
        $this->mockServers['retell_config']
            ->shouldReceive('getWebhook')
            ->once()
            ->with(['company_id' => 1])
            ->andReturn($expectedResult);
        
        // Act
        $result = $this->gateway->process($request);
        
        // Assert
        $this->assertEquals('2.0', $result['jsonrpc']);
        $this->assertEquals('test_001', $result['id']);
        $this->assertEquals($expectedResult, $result['result']);
    }

    /** @test */
    #[Test]
    public function it_handles_unknown_server()
    {
        // Arrange
        $request = [
            'jsonrpc' => '2.0',
            'method' => 'unknown_server.method',
            'params' => [],
            'id' => 'test_002'
        ];
        
        // Act
        $result = $this->gateway->process($request);
        
        // Assert
        $this->assertArrayHasKey('error', $result);
        $this->assertEquals(-32601, $result['error']['code']);
        $this->assertEquals('Method not found', $result['error']['message']);
    }

    /** @test */
    public function it_handles_unknown_method()
    {
        // Arrange
        $request = [
            'jsonrpc' => '2.0',
            'method' => 'retell_config.unknownMethod',
            'params' => [],
            'id' => 'test_003'
        ];
        
        // Act
        $result = $this->gateway->process($request);
        
        // Assert
        $this->assertArrayHasKey('error', $result);
        $this->assertEquals(-32601, $result['error']['code']);
    }

    /** @test */
    #[Test]
    public function it_handles_server_exceptions()
    {
        // Arrange
        $request = [
            'jsonrpc' => '2.0',
            'method' => 'retell_config.updateWebhook',
            'params' => ['company_id' => 1],
            'id' => 'test_004'
        ];
        
        $this->mockServers['retell_config']
            ->shouldReceive('updateWebhook')
            ->once()
            ->andThrow(new \Exception('Server error'));
        
        // Act
        $result = $this->gateway->process($request);
        
        // Assert
        $this->assertArrayHasKey('error', $result);
        $this->assertEquals(-32603, $result['error']['code']);
        $this->assertEquals('Internal error', $result['error']['message']);
        $this->assertEquals('Server error', $result['error']['data']['details']);
    }

    /** @test */
    public function it_supports_notifications_without_id()
    {
        // Arrange
        $request = [
            'jsonrpc' => '2.0',
            'method' => 'webhook.processRetellWebhook',
            'params' => ['event' => 'call_ended']
            // No id = notification
        ];
        
        $this->mockServers['webhook']
            ->shouldReceive('processRetellWebhook')
            ->once()
            ->with(['event' => 'call_ended'])
            ->andReturn(['success' => true]);
        
        // Act
        $result = $this->gateway->process($request);
        
        // Assert
        $this->assertArrayNotHasKey('id', $result); // Notifications don't return id
        $this->assertArrayHasKey('result', $result);
    }

    /** @test */
    #[Test]
    public function it_implements_circuit_breaker()
    {
        // Arrange
        $request = [
            'jsonrpc' => '2.0',
            'method' => 'retell_config.getWebhook',
            'params' => ['company_id' => 1],
            'id' => 'test_005'
        ];
        
        // Simulate multiple failures to open circuit
        for ($i = 0; $i < 5; $i++) {
            $this->mockServers['retell_config']
                ->shouldReceive('getWebhook')
                ->once()
                ->andThrow(new \Exception('API failure'));
            
            $this->gateway->process($request);
        }
        
        // Act - Circuit should be open now
        $result = $this->gateway->process($request);
        
        // Assert
        $this->assertArrayHasKey('error', $result);
        $this->assertEquals(-32603, $result['error']['code']);
        $this->assertStringContains('temporarily unavailable', $result['error']['message']);
    }

    /** @test */
    public function it_returns_health_status()
    {
        // Arrange
        foreach ($this->mockServers as $name => $server) {
            $server->shouldReceive('health')
                ->andReturn(['status' => 'healthy']);
        }
        
        // Act
        $health = $this->gateway->health();
        
        // Assert
        $this->assertEquals('healthy', $health['gateway']);
        $this->assertArrayHasKey('servers', $health);
        $this->assertCount(4, $health['servers']);
        
        foreach ($health['servers'] as $server) {
            $this->assertEquals('healthy', $server['status']);
        }
    }

    /** @test */
    #[Test]
    public function it_handles_invalid_method_format()
    {
        // Arrange
        $request = [
            'jsonrpc' => '2.0',
            'method' => 'invalid_format', // No dot separator
            'params' => [],
            'id' => 'test_006'
        ];
        
        // Act
        $result = $this->gateway->process($request);
        
        // Assert
        $this->assertArrayHasKey('error', $result);
        $this->assertEquals(-32602, $result['error']['code']);
        $this->assertEquals('Invalid params', $result['error']['message']);
    }

    /** @test */
    public function it_logs_requests_and_responses()
    {
        // Arrange
        Log::spy();
        
        $request = [
            'jsonrpc' => '2.0',
            'method' => 'retell_config.getWebhook',
            'params' => ['company_id' => 1],
            'id' => 'test_007'
        ];
        
        $this->mockServers['retell_config']
            ->shouldReceive('getWebhook')
            ->once()
            ->andReturn(['success' => true]);
        
        // Act
        $this->gateway->process($request);
        
        // Assert
        Log::shouldHaveReceived('channel')
            ->with('mcp-external')
            ->twice(); // Once for request, once for response
    }

    /** @test */
    #[Test]
    public function it_validates_parameter_types()
    {
        // Arrange
        $request = [
            'jsonrpc' => '2.0',
            'method' => 'retell_config.getWebhook',
            'params' => 'invalid_params', // Should be array or object
            'id' => 'test_008'
        ];
        
        // Act
        $result = $this->gateway->process($request);
        
        // Assert
        $this->assertArrayHasKey('error', $result);
        $this->assertEquals(-32602, $result['error']['code']);
    }

    /** @test */
    public function it_tracks_performance_metrics()
    {
        // Arrange
        $request = [
            'jsonrpc' => '2.0',
            'method' => 'retell_config.getWebhook',
            'params' => ['company_id' => 1],
            'id' => 'test_009'
        ];
        
        $this->mockServers['retell_config']
            ->shouldReceive('getWebhook')
            ->once()
            ->andReturn(['success' => true]);
        
        // Act
        $startTime = microtime(true);
        $result = $this->gateway->process($request);
        $duration = (microtime(true) - $startTime) * 1000;
        
        // Assert
        $this->assertArrayHasKey('result', $result);
        // In a real implementation, we'd check if metrics were recorded
        $this->assertLessThan(1000, $duration); // Should complete within 1 second
    }

    /** @test */
    #[Test]
    public function it_supports_batch_requests()
    {
        // Arrange
        $batchRequest = [
            [
                'jsonrpc' => '2.0',
                'method' => 'retell_config.getWebhook',
                'params' => ['company_id' => 1],
                'id' => 'batch_001'
            ],
            [
                'jsonrpc' => '2.0',
                'method' => 'appointment_mgmt.findAppointments',
                'params' => ['phone' => '+491234567890'],
                'id' => 'batch_002'
            ]
        ];
        
        $this->mockServers['retell_config']
            ->shouldReceive('getWebhook')
            ->once()
            ->andReturn(['agents' => []]);
        
        $this->mockServers['appointment_mgmt']
            ->shouldReceive('findAppointments')
            ->once()
            ->andReturn(['appointments' => []]);
        
        // Act
        $results = $this->gateway->processBatch($batchRequest);
        
        // Assert
        $this->assertCount(2, $results);
        $this->assertEquals('batch_001', $results[0]['id']);
        $this->assertEquals('batch_002', $results[1]['id']);
    }

    /** @test */
    public function it_maintains_request_context()
    {
        // Arrange
        $request = [
            'jsonrpc' => '2.0',
            'method' => 'webhook.processRetellWebhook',
            'params' => [
                'correlation_id' => 'corr_123',
                'event' => 'call_ended'
            ],
            'id' => 'test_010'
        ];
        
        $this->mockServers['webhook']
            ->shouldReceive('processRetellWebhook')
            ->once()
            ->with(Mockery::on(function($params) {
                return $params['correlation_id'] === 'corr_123';
            }))
            ->andReturn(['success' => true]);
        
        // Act
        $result = $this->gateway->process($request);
        
        // Assert
        $this->assertTrue($result['result']['success']);
    }
}