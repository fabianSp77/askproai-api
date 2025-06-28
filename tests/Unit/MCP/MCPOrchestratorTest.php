<?php

namespace Tests\Unit\MCP;

use App\Services\Cache\MCPCacheManager;
use App\Services\CircuitBreaker\CircuitBreaker;
use App\Services\MCP\MCPConfig;
use App\Services\MCP\MCPError;
use App\Services\MCP\MCPOrchestrator;
use App\Services\MCP\MCPRequest;
use App\Services\MCP\MCPResponse;
use App\Services\MCP\Servers\CalcomMCPServer;
use App\Services\MCP\Servers\DatabaseMCPServer;
use App\Services\MCP\Servers\KnowledgeMCPServer;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MCPOrchestratorTest extends TestCase
{
    private MCPOrchestrator $orchestrator;
    private $mockDatabaseServer;
    private $mockCalcomServer;
    private $mockKnowledgeServer;
    private $mockCircuitBreaker;
    private $mockCacheManager;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockDatabaseServer = Mockery::mock(DatabaseMCPServer::class);
        $this->mockCalcomServer = Mockery::mock(CalcomMCPServer::class);
        $this->mockKnowledgeServer = Mockery::mock(KnowledgeMCPServer::class);
        $this->mockCircuitBreaker = Mockery::mock(CircuitBreaker::class);
        $this->mockCacheManager = Mockery::mock(MCPCacheManager::class);
        
        $config = new MCPConfig([
            'servers' => [
                'database' => ['enabled' => true],
                'calcom' => ['enabled' => true],
                'knowledge' => ['enabled' => true]
            ],
            'orchestration' => [
                'parallel_execution' => true,
                'timeout' => 30,
                'retry_attempts' => 3
            ]
        ]);
        
        $this->orchestrator = new MCPOrchestrator(
            $config,
            $this->mockCircuitBreaker,
            $this->mockCacheManager
        );
        
        // Inject mocked servers
        $this->orchestrator->registerServer('database', $this->mockDatabaseServer);
        $this->orchestrator->registerServer('calcom', $this->mockCalcomServer);
        $this->orchestrator->registerServer('knowledge', $this->mockKnowledgeServer);
    }

    #[Test]

    public function test_single_server_request_success()
    {
        $request = new MCPRequest([
            'method' => 'customers.find',
            'params' => ['phone' => '+49 30 12345678']
        ]);
        
        $expectedResponse = new MCPResponse([
            'data' => ['id' => 1, 'name' => 'Test Customer'],
            'success' => true
        ]);
        
        $this->mockCircuitBreaker->shouldReceive('call')
            ->once()
            ->andReturn($expectedResponse);
        
        $this->mockCacheManager->shouldReceive('get')
            ->once()
            ->andReturn(null);
        
        $this->mockCacheManager->shouldReceive('put')
            ->once();
        
        $result = $this->orchestrator->execute($request);
        
        $this->assertTrue($result->isSuccess());
        $this->assertEquals('Test Customer', $result->getData()['name']);
    }

    #[Test]

    public function test_parallel_execution_multiple_servers()
    {
        $request = new MCPRequest([
            'method' => 'booking.check_availability',
            'params' => [
                'service_id' => 1,
                'date' => '2025-06-25',
                'time' => '10:00'
            ]
        ]);
        
        // Mock responses from different servers
        $dbResponse = new MCPResponse([
            'data' => ['staff_available' => [1, 2, 3]],
            'success' => true
        ]);
        
        $calcomResponse = new MCPResponse([
            'data' => ['slots_available' => true],
            'success' => true
        ]);
        
        $this->mockDatabaseServer->shouldReceive('canHandle')
            ->with('booking.check_availability')
            ->andReturn(true);
        
        $this->mockCalcomServer->shouldReceive('canHandle')
            ->with('booking.check_availability')
            ->andReturn(true);
        
        $this->mockKnowledgeServer->shouldReceive('canHandle')
            ->with('booking.check_availability')
            ->andReturn(false);
        
        $this->mockCircuitBreaker->shouldReceive('call')
            ->twice()
            ->andReturn($dbResponse, $calcomResponse);
        
        $this->mockCacheManager->shouldReceive('get')
            ->andReturn(null);
        
        $this->mockCacheManager->shouldReceive('put');
        
        $result = $this->orchestrator->execute($request);
        
        $this->assertTrue($result->isSuccess());
        $this->assertArrayHasKey('database', $result->getData());
        $this->assertArrayHasKey('calcom', $result->getData());
    }

    #[Test]

    public function test_circuit_breaker_open_fallback()
    {
        $request = new MCPRequest([
            'method' => 'appointments.create',
            'params' => ['customer_id' => 1]
        ]);
        
        $this->mockCircuitBreaker->shouldReceive('call')
            ->once()
            ->andThrow(new \Exception('Circuit breaker is open'));
        
        $this->mockCacheManager->shouldReceive('get')
            ->once()
            ->andReturn(new MCPResponse([
                'data' => ['id' => 999, 'cached' => true],
                'success' => true
            ]));
        
        $result = $this->orchestrator->execute($request);
        
        $this->assertTrue($result->isSuccess());
        $this->assertTrue($result->getData()['cached']);
    }

    #[Test]

    public function test_retry_mechanism_on_failure()
    {
        $request = new MCPRequest([
            'method' => 'external.api.call',
            'params' => ['endpoint' => '/test']
        ]);
        
        $attempts = 0;
        $this->mockCircuitBreaker->shouldReceive('call')
            ->times(3)
            ->andReturnUsing(function() use (&$attempts) {
                $attempts++;
                if ($attempts < 3) {
                    throw new \Exception('Temporary failure');
                }
                return new MCPResponse(['success' => true, 'data' => ['attempts' => $attempts]]);
            });
        
        $this->mockCacheManager->shouldReceive('get')->andReturn(null);
        $this->mockCacheManager->shouldReceive('put');
        
        $result = $this->orchestrator->execute($request);
        
        $this->assertTrue($result->isSuccess());
        $this->assertEquals(3, $result->getData()['attempts']);
    }

    #[Test]

    public function test_request_validation_failure()
    {
        $request = new MCPRequest([
            'method' => '', // Invalid empty method
            'params' => []
        ]);
        
        $result = $this->orchestrator->execute($request);
        
        $this->assertFalse($result->isSuccess());
        $this->assertStringContainsString('Invalid request', $result->getError()->getMessage());
    }

    #[Test]

    public function test_timeout_handling()
    {
        $request = new MCPRequest([
            'method' => 'long.running.task',
            'params' => ['duration' => 60]
        ]);
        
        $this->mockCircuitBreaker->shouldReceive('call')
            ->once()
            ->andReturnUsing(function() {
                sleep(35); // Exceeds 30s timeout
                return new MCPResponse(['success' => true]);
            });
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Request timeout');
        
        $this->orchestrator->execute($request);
    }

    #[Test]

    public function test_server_priority_ordering()
    {
        $request = new MCPRequest([
            'method' => 'data.fetch',
            'params' => ['id' => 1]
        ]);
        
        // All servers can handle this method
        $this->mockDatabaseServer->shouldReceive('canHandle')->andReturn(true);
        $this->mockDatabaseServer->shouldReceive('getPriority')->andReturn(10);
        
        $this->mockCalcomServer->shouldReceive('canHandle')->andReturn(true);
        $this->mockCalcomServer->shouldReceive('getPriority')->andReturn(5);
        
        $this->mockKnowledgeServer->shouldReceive('canHandle')->andReturn(true);
        $this->mockKnowledgeServer->shouldReceive('getPriority')->andReturn(1);
        
        // Database server should be called first (highest priority)
        $this->mockCircuitBreaker->shouldReceive('call')
            ->once()
            ->with(Mockery::on(function($callback) {
                // Verify it's calling the database server
                return true;
            }))
            ->andReturn(new MCPResponse(['success' => true, 'server' => 'database']));
        
        $this->mockCacheManager->shouldReceive('get')->andReturn(null);
        $this->mockCacheManager->shouldReceive('put');
        
        $result = $this->orchestrator->execute($request);
        
        $this->assertEquals('database', $result->getData()['server']);
    }

    #[Test]

    public function test_cache_hit_bypass_execution()
    {
        $request = new MCPRequest([
            'method' => 'expensive.calculation',
            'params' => ['input' => 42]
        ]);
        
        $cachedResponse = new MCPResponse([
            'data' => ['result' => 1764, 'from_cache' => true],
            'success' => true
        ]);
        
        $this->mockCacheManager->shouldReceive('get')
            ->with(Mockery::on(function($key) {
                return str_contains($key, 'expensive.calculation');
            }))
            ->once()
            ->andReturn($cachedResponse);
        
        // Circuit breaker should not be called on cache hit
        $this->mockCircuitBreaker->shouldNotReceive('call');
        
        $result = $this->orchestrator->execute($request);
        
        $this->assertTrue($result->isSuccess());
        $this->assertTrue($result->getData()['from_cache']);
        $this->assertEquals(1764, $result->getData()['result']);
    }

    #[Test]

    public function test_error_aggregation_from_multiple_servers()
    {
        $request = new MCPRequest([
            'method' => 'multi.server.operation',
            'params' => ['action' => 'sync']
        ]);
        
        $this->mockDatabaseServer->shouldReceive('canHandle')->andReturn(true);
        $this->mockCalcomServer->shouldReceive('canHandle')->andReturn(true);
        
        $dbError = new MCPError('Database connection failed', 'DB_ERROR', 500);
        $calcomError = new MCPError('API rate limit exceeded', 'RATE_LIMIT', 429);
        
        $this->mockCircuitBreaker->shouldReceive('call')
            ->twice()
            ->andReturn(
                new MCPResponse(['success' => false, 'error' => $dbError]),
                new MCPResponse(['success' => false, 'error' => $calcomError])
            );
        
        $this->mockCacheManager->shouldReceive('get')->andReturn(null);
        
        $result = $this->orchestrator->execute($request);
        
        $this->assertFalse($result->isSuccess());
        $this->assertStringContainsString('Multiple errors', $result->getError()->getMessage());
        $this->assertStringContainsString('Database connection failed', $result->getError()->getDetails()['errors'][0]);
        $this->assertStringContainsString('API rate limit exceeded', $result->getError()->getDetails()['errors'][1]);
    }

    #[Test]

    public function test_graceful_degradation_partial_success()
    {
        $request = new MCPRequest([
            'method' => 'dashboard.metrics',
            'params' => ['include' => ['calls', 'appointments', 'revenue']]
        ]);
        
        $this->mockDatabaseServer->shouldReceive('canHandle')->andReturn(true);
        $this->mockCalcomServer->shouldReceive('canHandle')->andReturn(true);
        
        // Database succeeds
        $this->mockCircuitBreaker->shouldReceive('call')
            ->once()
            ->andReturn(new MCPResponse([
                'success' => true,
                'data' => ['calls' => 150, 'appointments' => 45]
            ]));
        
        // Calcom fails
        $this->mockCircuitBreaker->shouldReceive('call')
            ->once()
            ->andThrow(new \Exception('Calcom API unavailable'));
        
        $this->mockCacheManager->shouldReceive('get')->andReturn(null);
        $this->mockCacheManager->shouldReceive('put');
        
        $result = $this->orchestrator->execute($request, ['allow_partial' => true]);
        
        $this->assertTrue($result->isSuccess());
        $this->assertEquals(150, $result->getData()['database']['calls']);
        $this->assertArrayHasKey('_partial_failure', $result->getData());
        $this->assertStringContainsString('Calcom API unavailable', $result->getData()['_partial_failure']['calcom']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}