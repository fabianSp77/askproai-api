<?php

namespace Tests\Integration\MCP;

use App\Services\CircuitBreaker\CircuitBreaker;
use App\Services\CircuitBreaker\CircuitState;
use App\Services\MCP\MCPOrchestrator;
use App\Services\CalcomV2Service;
use App\Services\RetellService;
use Tests\TestCase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CircuitBreakerTest extends TestCase
{
    private CircuitBreaker $circuitBreaker;
    private string $testService = 'test_api';

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->circuitBreaker = new CircuitBreaker();
        
        // Clear any existing circuit state
        Cache::forget("circuit_breaker.{$this->testService}.state");
        Cache::forget("circuit_breaker.{$this->testService}.failures");
        Cache::forget("circuit_breaker.{$this->testService}.last_failure");
    }

    public function test_circuit_starts_closed()
    {
        $state = $this->circuitBreaker->getState($this->testService);
        
        $this->assertEquals(CircuitState::CLOSED, $state);
        $this->assertEquals(0, $this->circuitBreaker->getFailureCount($this->testService));
    }

    public function test_circuit_opens_after_threshold_failures()
    {
        $failureThreshold = config('circuit-breaker.failure_threshold', 5);
        
        // Simulate failures
        for ($i = 0; $i < $failureThreshold; $i++) {
            try {
                $this->circuitBreaker->call($this->testService, function() {
                    throw new \Exception('Service failure');
                });
            } catch (\Exception $e) {
                // Expected
            }
        }
        
        // Circuit should now be open
        $state = $this->circuitBreaker->getState($this->testService);
        $this->assertEquals(CircuitState::OPEN, $state);
        
        // Further calls should fail immediately
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Circuit breaker is OPEN');
        
        $this->circuitBreaker->call($this->testService, function() {
            return 'Should not execute';
        });
    }

    public function test_circuit_transitions_to_half_open()
    {
        // Open the circuit
        $this->openCircuit();
        
        // Wait for timeout period
        $timeout = config('circuit-breaker.timeout', 60);
        Carbon::setTestNow(Carbon::now()->addSeconds($timeout + 1));
        
        // Circuit should be half-open
        $state = $this->circuitBreaker->getState($this->testService);
        $this->assertEquals(CircuitState::HALF_OPEN, $state);
        
        // Test request should be allowed
        $result = $this->circuitBreaker->call($this->testService, function() {
            return 'Success';
        });
        
        $this->assertEquals('Success', $result);
        
        // Circuit should close after successful call
        $state = $this->circuitBreaker->getState($this->testService);
        $this->assertEquals(CircuitState::CLOSED, $state);
    }

    public function test_circuit_reopens_on_half_open_failure()
    {
        // Open the circuit
        $this->openCircuit();
        
        // Move to half-open
        Carbon::setTestNow(Carbon::now()->addSeconds(61));
        
        // Fail the test request
        try {
            $this->circuitBreaker->call($this->testService, function() {
                throw new \Exception('Still failing');
            });
        } catch (\Exception $e) {
            // Expected
        }
        
        // Circuit should be open again
        $state = $this->circuitBreaker->getState($this->testService);
        $this->assertEquals(CircuitState::OPEN, $state);
    }

    public function test_circuit_breaker_with_calcom_service()
    {
        // Mock Cal.com API failures
        Http::fake([
            'api.cal.com/*' => Http::sequence()
                ->push(null, 500) // Server error
                ->push(null, 500)
                ->push(null, 500)
                ->push(null, 500)
                ->push(null, 500)
                ->push(['success' => true], 200) // Recovery
        ]);
        
        $calcomService = app(CalcomV2Service::class);
        $failures = 0;
        
        // Make requests until circuit opens
        for ($i = 0; $i < 6; $i++) {
            try {
                $calcomService->getAvailableSlots([
                    'eventTypeId' => 12345,
                    'dateFrom' => '2025-06-25',
                    'dateTo' => '2025-06-25'
                ]);
            } catch (\Exception $e) {
                $failures++;
            }
        }
        
        $this->assertEquals(5, $failures);
        
        // Next call should fail immediately (circuit open)
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Circuit breaker is OPEN');
        
        $calcomService->getAvailableSlots([
            'eventTypeId' => 12345,
            'dateFrom' => '2025-06-25',
            'dateTo' => '2025-06-25'
        ]);
    }

    public function test_circuit_breaker_with_retell_service()
    {
        // Mock Retell API failures
        Http::fake([
            'api.retellai.com/*' => Http::sequence()
                ->push(null, 503) // Service unavailable
                ->push(null, 503)
                ->push(null, 503)
                ->push(null, 503)
                ->push(null, 503)
        ]);
        
        $retellService = app(RetellService::class);
        
        // Open circuit with failures
        for ($i = 0; $i < 5; $i++) {
            try {
                $retellService->getCall('test_call_id');
            } catch (\Exception $e) {
                // Expected
            }
        }
        
        // Check circuit state for Retell service
        $state = $this->circuitBreaker->getState('retell');
        $this->assertEquals(CircuitState::OPEN, $state);
    }

    public function test_circuit_breaker_fallback_mechanism()
    {
        // Open the circuit
        $this->openCircuit();
        
        // Test with fallback
        $result = $this->circuitBreaker->callWithFallback(
            $this->testService,
            function() {
                throw new \Exception('Should not execute');
            },
            function() {
                return 'Fallback response';
            }
        );
        
        $this->assertEquals('Fallback response', $result);
    }

    public function test_circuit_breaker_metrics()
    {
        // Generate some activity
        for ($i = 0; $i < 3; $i++) {
            $this->circuitBreaker->call($this->testService, function() {
                return 'Success';
            });
        }
        
        for ($i = 0; $i < 2; $i++) {
            try {
                $this->circuitBreaker->call($this->testService, function() {
                    throw new \Exception('Failure');
                });
            } catch (\Exception $e) {
                // Expected
            }
        }
        
        $metrics = $this->circuitBreaker->getMetrics($this->testService);
        
        $this->assertEquals(5, $metrics['total_calls']);
        $this->assertEquals(3, $metrics['successful_calls']);
        $this->assertEquals(2, $metrics['failed_calls']);
        $this->assertEquals(60, $metrics['success_rate']);
        $this->assertEquals(CircuitState::CLOSED, $metrics['current_state']);
    }

    public function test_circuit_breaker_different_services_isolated()
    {
        $service1 = 'api_1';
        $service2 = 'api_2';
        
        // Open circuit for service1
        for ($i = 0; $i < 5; $i++) {
            try {
                $this->circuitBreaker->call($service1, function() {
                    throw new \Exception('Service 1 failure');
                });
            } catch (\Exception $e) {
                // Expected
            }
        }
        
        // Service1 should be open
        $this->assertEquals(CircuitState::OPEN, $this->circuitBreaker->getState($service1));
        
        // Service2 should still be closed
        $this->assertEquals(CircuitState::CLOSED, $this->circuitBreaker->getState($service2));
        
        // Service2 should work normally
        $result = $this->circuitBreaker->call($service2, function() {
            return 'Service 2 works';
        });
        
        $this->assertEquals('Service 2 works', $result);
    }

    public function test_circuit_breaker_with_custom_configuration()
    {
        $customService = 'custom_api';
        
        // Set custom configuration
        config([
            "circuit-breaker.services.{$customService}" => [
                'failure_threshold' => 2,
                'timeout' => 30,
                'success_threshold' => 1
            ]
        ]);
        
        // Should open after 2 failures
        for ($i = 0; $i < 2; $i++) {
            try {
                $this->circuitBreaker->call($customService, function() {
                    throw new \Exception('Failure');
                });
            } catch (\Exception $e) {
                // Expected
            }
        }
        
        $this->assertEquals(CircuitState::OPEN, $this->circuitBreaker->getState($customService));
        
        // Should transition to half-open after 30 seconds
        Carbon::setTestNow(Carbon::now()->addSeconds(31));
        $this->assertEquals(CircuitState::HALF_OPEN, $this->circuitBreaker->getState($customService));
    }

    public function test_circuit_breaker_reset_functionality()
    {
        // Open the circuit
        $this->openCircuit();
        $this->assertEquals(CircuitState::OPEN, $this->circuitBreaker->getState($this->testService));
        
        // Reset the circuit
        $this->circuitBreaker->reset($this->testService);
        
        // Should be closed again
        $this->assertEquals(CircuitState::CLOSED, $this->circuitBreaker->getState($this->testService));
        $this->assertEquals(0, $this->circuitBreaker->getFailureCount($this->testService));
    }

    public function test_circuit_breaker_concurrent_requests()
    {
        $results = [];
        $exceptions = [];
        
        // Simulate concurrent requests during circuit opening
        for ($i = 0; $i < 10; $i++) {
            try {
                $results[] = $this->circuitBreaker->call($this->testService, function() use ($i) {
                    if ($i < 5) {
                        throw new \Exception('Failure');
                    }
                    return 'Success';
                });
            } catch (\Exception $e) {
                $exceptions[] = $e->getMessage();
            }
        }
        
        // Should have 5 failures and then circuit open exceptions
        $this->assertCount(5, array_filter($exceptions, fn($msg) => $msg === 'Failure'));
        $this->assertGreaterThan(0, count(array_filter($exceptions, fn($msg) => str_contains($msg, 'Circuit breaker is OPEN'))));
    }

    public function test_mcp_orchestrator_handles_circuit_breaker()
    {
        $orchestrator = app(MCPOrchestrator::class);
        
        // Mock external service failures
        Http::fake([
            '*' => Http::response(null, 500)
        ]);
        
        // Make requests until circuits open
        $results = [];
        for ($i = 0; $i < 10; $i++) {
            $request = new \App\Services\MCP\MCPRequest([
                'method' => 'external.api.call',
                'params' => ['endpoint' => '/test']
            ]);
            
            $results[] = $orchestrator->execute($request);
        }
        
        // Later requests should use fallback/cache
        $lastResults = array_slice($results, -3);
        foreach ($lastResults as $result) {
            if ($result->isSuccess()) {
                $this->assertArrayHasKey('from_cache', $result->getData());
            }
        }
    }

    private function openCircuit(): void
    {
        for ($i = 0; $i < 5; $i++) {
            try {
                $this->circuitBreaker->call($this->testService, function() {
                    throw new \Exception('Failure');
                });
            } catch (\Exception $e) {
                // Expected
            }
        }
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow(null);
        Cache::forget("circuit_breaker.{$this->testService}.state");
        Cache::forget("circuit_breaker.{$this->testService}.failures");
        Cache::forget("circuit_breaker.{$this->testService}.last_failure");
        parent::tearDown();
    }
}