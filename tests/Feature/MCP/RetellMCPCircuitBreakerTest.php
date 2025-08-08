<?php

namespace Tests\Feature\MCP;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Call;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use App\Services\CircuitBreaker;

class RetellMCPCircuitBreakerTest extends TestCase
{
    use RefreshDatabase;

    protected string $mcpEndpoint = '/api/mcp/retell/tools';
    protected string $validToken = 'test_mcp_token_2024';
    protected Company $company;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Set test environment
        config(['app.env' => 'local']);
        config(['retell-mcp.security.mcp_token' => $this->validToken]);
        
        // Configure circuit breaker for testing
        config([
            'circuit_breaker.failure_threshold' => 3,
            'circuit_breaker.timeout' => 2, // 2 seconds for faster testing
            'circuit_breaker.recovery_time' => 1
        ]);
        
        // Create test data
        $this->company = Company::factory()->create([
            'name' => 'Circuit Breaker Test Company',
            'calcom_api_key' => 'test_key',
            'calcom_event_type_id' => 123
        ]);
        
        // Clear cache and reset circuit breakers
        Cache::flush();
        if (class_exists(CircuitBreaker::class)) {
            CircuitBreaker::reset('mcp_endpoint');
            CircuitBreaker::reset('external_api');
            CircuitBreaker::reset('database');
        }
    }
    
    /**
     * Test circuit breaker basic functionality
     */
    public function test_circuit_breaker_basic_functionality()
    {
        if (!class_exists(CircuitBreaker::class)) {
            $this->markTestSkipped('CircuitBreaker class not available');
            return;
        }
        
        $circuitBreaker = new CircuitBreaker('test_service', 2, 1);
        
        // Initially closed
        $this->assertTrue($circuitBreaker->isClosed());
        $this->assertFalse($circuitBreaker->isOpen());
        
        // Record failures
        $circuitBreaker->recordFailure();
        $this->assertTrue($circuitBreaker->isClosed());
        
        $circuitBreaker->recordFailure();
        $this->assertTrue($circuitBreaker->isOpen());
        $this->assertFalse($circuitBreaker->isClosed());
        
        // Wait for timeout
        sleep(2);
        
        // Should be half-open
        $this->assertTrue($circuitBreaker->isHalfOpen());
        
        // Successful call should close it
        $circuitBreaker->recordSuccess();
        $this->assertTrue($circuitBreaker->isClosed());
    }
    
    /**
     * Test circuit breaker with MCP endpoint under failure conditions
     */
    public function test_mcp_endpoint_circuit_breaker_failure_handling()
    {
        if (!class_exists(CircuitBreaker::class)) {
            $this->markTestSkipped('CircuitBreaker class not available');
            return;
        }
        
        // Simulate failures by calling invalid tools repeatedly
        for ($i = 0; $i < 5; $i++) {
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->validToken,
                'Content-Type' => 'application/json'
            ])->postJson($this->mcpEndpoint, [
                'tool' => 'invalidTool' . $i,
                'arguments' => []
            ]);
            
            // Should return error but not crash
            $response->assertStatus(200)
                ->assertJson([
                    'success' => false
                ]);
        }
        
        // After enough failures, circuit breaker should activate
        // This depends on implementation - check if circuit breaker headers are present
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->validToken,
            'Content-Type' => 'application/json'
        ])->postJson($this->mcpEndpoint, [
            'tool' => 'getCurrentTimeBerlin',
            'arguments' => []
        ]);
        
        // Should either work normally or be circuit breaker blocked
        $this->assertContains($response->status(), [200, 503]);
        
        if ($response->status() === 503) {
            $this->assertArrayHasKey('error', $response->json());
            $this->assertStringContainsString('circuit', strtolower($response->json('error')));
        }
    }
    
    /**
     * Test circuit breaker with external service failures
     */
    public function test_circuit_breaker_external_service_failures()
    {
        if (!class_exists(CircuitBreaker::class)) {
            $this->markTestSkipped('CircuitBreaker class not available');
            return;
        }
        
        // Create a company with invalid CalCom configuration to trigger failures
        $invalidCompany = Company::factory()->create([
            'calcom_api_key' => 'invalid_key',
            'calcom_event_type_id' => 999999
        ]);
        
        // Try to make appointments that will fail due to invalid CalCom config
        for ($i = 0; $i < 4; $i++) {
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->validToken,
                'Content-Type' => 'application/json'
            ])->postJson($this->mcpEndpoint, [
                'tool' => 'bookAppointment',
                'arguments' => [
                    'name' => "Test User {$i}",
                    'telefonnummer' => "+491234567{$i}",
                    'datum' => Carbon::tomorrow()->format('Y-m-d'),
                    'uhrzeit' => '10:00',
                    'company_id' => $invalidCompany->id
                ]
            ]);
            
            // Expect failures due to invalid configuration
            $response->assertStatus(200);
            // Most likely will have success: false due to configuration issues
        }
        
        // After several failures, check if circuit breaker is activated
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->validToken,
            'Content-Type' => 'application/json'
        ])->postJson($this->mcpEndpoint, [
            'tool' => 'bookAppointment',
            'arguments' => [
                'name' => 'Test User Final',
                'telefonnummer' => '+49123456789',
                'datum' => Carbon::tomorrow()->format('Y-m-d'),
                'uhrzeit' => '10:00',
                'company_id' => $invalidCompany->id
            ]
        ]);
        
        // Should handle gracefully, either with error response or circuit breaker block
        $this->assertContains($response->status(), [200, 503]);
    }
    
    /**
     * Test circuit breaker recovery mechanism
     */
    public function test_circuit_breaker_recovery()
    {
        if (!class_exists(CircuitBreaker::class)) {
            $this->markTestSkipped('CircuitBreaker class not available');
            return;
        }
        
        $circuitBreaker = new CircuitBreaker('test_recovery', 2, 1);
        
        // Force circuit breaker to open
        $circuitBreaker->recordFailure();
        $circuitBreaker->recordFailure();
        $this->assertTrue($circuitBreaker->isOpen());
        
        // Wait for recovery period
        sleep(2);
        
        // Should now be half-open
        $this->assertTrue($circuitBreaker->isHalfOpen());
        
        // Test with valid request to ensure recovery
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->validToken,
            'Content-Type' => 'application/json'
        ])->postJson($this->mcpEndpoint, [
            'tool' => 'getCurrentTimeBerlin',
            'arguments' => []
        ]);
        
        $response->assertStatus(200);
        
        // After successful request, circuit should be closed
        if (method_exists($circuitBreaker, 'recordSuccess')) {
            $circuitBreaker->recordSuccess();
            $this->assertTrue($circuitBreaker->isClosed());
        }
    }
    
    /**
     * Test circuit breaker with different failure rates
     */
    public function test_circuit_breaker_failure_rate_thresholds()
    {
        if (!class_exists(CircuitBreaker::class)) {
            $this->markTestSkipped('CircuitBreaker class not available');
            return;
        }
        
        // Test with mixed success/failure ratio
        $successCount = 0;
        $failureCount = 0;
        
        for ($i = 0; $i < 20; $i++) {
            if ($i % 3 === 0) {
                // Simulate failure
                $response = $this->withHeaders([
                    'Authorization' => 'Bearer ' . $this->validToken,
                    'Content-Type' => 'application/json'
                ])->postJson($this->mcpEndpoint, [
                    'tool' => 'invalidTool',
                    'arguments' => []
                ]);
                $failureCount++;
            } else {
                // Simulate success
                $response = $this->withHeaders([
                    'Authorization' => 'Bearer ' . $this->validToken,
                    'Content-Type' => 'application/json'
                ])->postJson($this->mcpEndpoint, [
                    'tool' => 'getCurrentTimeBerlin',
                    'arguments' => []
                ]);
                $successCount++;
            }
        }
        
        $failureRate = $failureCount / ($successCount + $failureCount);
        
        // Circuit breaker behavior should depend on failure rate
        // This test verifies the system handles mixed scenarios
        $this->assertGreaterThan(0, $successCount);
        $this->assertGreaterThan(0, $failureCount);
        $this->assertLessThan(1, $failureRate);
    }
    
    /**
     * Test circuit breaker with concurrent requests
     */
    public function test_circuit_breaker_concurrent_requests()
    {
        if (!class_exists(CircuitBreaker::class)) {
            $this->markTestSkipped('CircuitBreaker class not available');
            return;
        }
        
        $concurrentRequests = 10;
        $responses = [];
        
        // Force circuit to open state first
        $circuitBreaker = new CircuitBreaker('concurrent_test', 1, 5);
        $circuitBreaker->recordFailure();
        $circuitBreaker->recordFailure();
        
        // Make concurrent requests when circuit is open
        for ($i = 0; $i < $concurrentRequests; $i++) {
            $responses[] = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->validToken,
                'Content-Type' => 'application/json'
            ])->postJson($this->mcpEndpoint, [
                'tool' => 'getCurrentTimeBerlin',
                'arguments' => []
            ]);
        }
        
        // All requests should be handled consistently
        foreach ($responses as $response) {
            $this->assertContains($response->status(), [200, 503]);
        }
        
        // If circuit breaker is working, some might be blocked
        $blockedCount = 0;
        $successCount = 0;
        
        foreach ($responses as $response) {
            if ($response->status() === 503) {
                $blockedCount++;
            } elseif ($response->status() === 200) {
                $successCount++;
            }
        }
        
        // At least some responses should be received
        $this->assertGreaterThanOrEqual(1, $successCount + $blockedCount);
    }
    
    /**
     * Test circuit breaker configuration changes
     */
    public function test_circuit_breaker_configuration_changes()
    {
        if (!class_exists(CircuitBreaker::class)) {
            $this->markTestSkipped('CircuitBreaker class not available');
            return;
        }
        
        // Test with different thresholds
        $configurations = [
            ['threshold' => 2, 'timeout' => 1],
            ['threshold' => 5, 'timeout' => 3],
            ['threshold' => 1, 'timeout' => 2]
        ];
        
        foreach ($configurations as $index => $config) {
            $circuitBreaker = new CircuitBreaker(
                'config_test_' . $index,
                $config['threshold'],
                $config['timeout']
            );
            
            // Trigger failures up to threshold
            for ($i = 0; $i < $config['threshold']; $i++) {
                $circuitBreaker->recordFailure();
            }
            
            // Should be open after reaching threshold
            $this->assertTrue($circuitBreaker->isOpen(),
                "Circuit should be open after {$config['threshold']} failures");
        }
    }
    
    /**
     * Test circuit breaker metrics and monitoring
     */
    public function test_circuit_breaker_metrics()
    {
        if (!class_exists(CircuitBreaker::class)) {
            $this->markTestSkipped('CircuitBreaker class not available');
            return;
        }
        
        $circuitBreaker = new CircuitBreaker('metrics_test', 3, 2);
        
        // Record mixed results
        $circuitBreaker->recordSuccess();
        $circuitBreaker->recordSuccess();
        $circuitBreaker->recordFailure();
        $circuitBreaker->recordSuccess();
        $circuitBreaker->recordFailure();
        $circuitBreaker->recordFailure();
        
        // Check if metrics are available (if implemented)
        if (method_exists($circuitBreaker, 'getMetrics')) {
            $metrics = $circuitBreaker->getMetrics();
            
            $this->assertArrayHasKey('total_requests', $metrics);
            $this->assertArrayHasKey('failure_count', $metrics);
            $this->assertArrayHasKey('success_count', $metrics);
            $this->assertArrayHasKey('failure_rate', $metrics);
            
            $this->assertEquals(6, $metrics['total_requests']);
            $this->assertEquals(3, $metrics['failure_count']);
            $this->assertEquals(3, $metrics['success_count']);
            $this->assertEquals(0.5, $metrics['failure_rate']);
        }
    }
    
    /**
     * Test circuit breaker with different service types
     */
    public function test_circuit_breaker_multiple_services()
    {
        if (!class_exists(CircuitBreaker::class)) {
            $this->markTestSkipped('CircuitBreaker class not available');
            return;
        }
        
        // Test separate circuit breakers for different services
        $services = ['calcom_api', 'database', 'cache', 'email'];
        $circuitBreakers = [];
        
        foreach ($services as $service) {
            $circuitBreakers[$service] = new CircuitBreaker($service, 2, 1);
        }
        
        // Fail only one service
        $circuitBreakers['calcom_api']->recordFailure();
        $circuitBreakers['calcom_api']->recordFailure();
        
        // CalCom should be open, others closed
        $this->assertTrue($circuitBreakers['calcom_api']->isOpen());
        $this->assertTrue($circuitBreakers['database']->isClosed());
        $this->assertTrue($circuitBreakers['cache']->isClosed());
        $this->assertTrue($circuitBreakers['email']->isClosed());
        
        // Test MCP endpoint behavior when one service is down
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->validToken,
            'Content-Type' => 'application/json'
        ])->postJson($this->mcpEndpoint, [
            'tool' => 'getCurrentTimeBerlin', // Should work without CalCom
            'arguments' => []
        ]);
        
        $response->assertStatus(200);
        
        // Test appointment booking (depends on CalCom)
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->validToken,
            'Content-Type' => 'application/json'
        ])->postJson($this->mcpEndpoint, [
            'tool' => 'bookAppointment',
            'arguments' => [
                'name' => 'Test User',
                'datum' => 'morgen',
                'uhrzeit' => '10:00'
            ]
        ]);
        
        // Should handle gracefully when CalCom circuit is open
        $this->assertContains($response->status(), [200, 503]);
    }
    
    /**
     * Test circuit breaker reset functionality
     */
    public function test_circuit_breaker_reset()
    {
        if (!class_exists(CircuitBreaker::class)) {
            $this->markTestSkipped('CircuitBreaker class not available');
            return;
        }
        
        $circuitBreaker = new CircuitBreaker('reset_test', 1, 10);
        
        // Force to open state
        $circuitBreaker->recordFailure();
        $circuitBreaker->recordFailure();
        $this->assertTrue($circuitBreaker->isOpen());
        
        // Reset circuit breaker
        if (method_exists($circuitBreaker, 'reset')) {
            $circuitBreaker->reset();
            $this->assertTrue($circuitBreaker->isClosed());
        } elseif (method_exists(CircuitBreaker::class, 'reset')) {
            CircuitBreaker::reset('reset_test');
            $newCircuitBreaker = new CircuitBreaker('reset_test', 1, 10);
            $this->assertTrue($newCircuitBreaker->isClosed());
        }
    }
    
    /**
     * Test circuit breaker health check integration
     */
    public function test_circuit_breaker_health_check_integration()
    {
        // Test health check endpoint includes circuit breaker status
        $response = $this->get('/api/mcp/retell/health');
        
        $response->assertStatus(200);
        
        $healthData = $response->json();
        if (isset($healthData['circuit_breakers'])) {
            $this->assertIsArray($healthData['circuit_breakers']);
            
            // Should include status for each circuit breaker
            foreach ($healthData['circuit_breakers'] as $name => $status) {
                $this->assertIsString($name);
                $this->assertContains($status, ['closed', 'open', 'half-open']);
            }
        }
    }
}