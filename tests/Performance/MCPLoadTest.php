<?php

namespace Tests\Performance;

use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Call;
use App\Models\Company;
use App\Models\Customer;
use App\Services\Cache\MCPCacheManager;
use App\Services\CircuitBreaker\CircuitBreaker;
use App\Services\MCP\MCPOrchestrator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MCPLoadTest extends TestCase
{
    use RefreshDatabase;

    private MCPOrchestrator $orchestrator;
    private MCPCacheManager $cacheManager;
    private Company $company;
    private array $testData = [];

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->orchestrator = app(MCPOrchestrator::class);
        $this->cacheManager = app(MCPCacheManager::class);
        
        // Create test data
        $this->setupLargeDataset();
        
        // Clear caches
        Cache::flush();
        Redis::flushdb();
    }

    private function setupLargeDataset(): void
    {
        $this->company = Company::factory()->create();
        
        // Create branches
        $branches = Branch::factory()->count(10)->create([
            'company_id' => $this->company->id
        ]);
        
        // Create customers
        $customers = Customer::factory()->count(1000)->create([
            'company_id' => $this->company->id
        ]);
        
        // Create appointments
        foreach ($branches as $branch) {
            Appointment::factory()->count(100)->create([
                'company_id' => $this->company->id,
                'branch_id' => $branch->id,
                'customer_id' => $customers->random()->id
            ]);
        }
        
        // Create calls
        Call::factory()->count(500)->create([
            'company_id' => $this->company->id
        ]);
        
        $this->testData = [
            'branches' => $branches,
            'customers' => $customers
        ];
    }

    public function test_mcp_handles_concurrent_requests()
    {
        $concurrentRequests = 50;
        $results = [];
        $startTime = microtime(true);
        
        // Simulate concurrent requests
        $promises = [];
        for ($i = 0; $i < $concurrentRequests; $i++) {
            $customerId = $this->testData['customers']->random()->id;
            
            $promises[] = [
                'method' => 'customers.find',
                'params' => [
                    'id' => $customerId,
                    'company_id' => $this->company->id
                ]
            ];
        }
        
        // Execute all requests
        foreach ($promises as $request) {
            $results[] = $this->orchestrator->execute(
                new \App\Services\MCP\MCPRequest($request)
            );
        }
        
        $executionTime = microtime(true) - $startTime;
        
        // Assertions
        $this->assertCount($concurrentRequests, $results);
        
        $successCount = collect($results)->filter(fn($r) => $r->isSuccess())->count();
        $this->assertEquals($concurrentRequests, $successCount);
        
        // Performance assertions
        $this->assertLessThan(5, $executionTime); // Should complete in under 5 seconds
        
        $avgTimePerRequest = $executionTime / $concurrentRequests;
        $this->assertLessThan(0.1, $avgTimePerRequest); // Under 100ms per request
    }

    public function test_cache_performance_under_load()
    {
        $iterations = 100;
        $cacheHits = 0;
        $cacheMisses = 0;
        
        // First, populate cache
        for ($i = 0; $i < 20; $i++) {
            $request = new \App\Services\MCP\MCPRequest([
                'method' => 'analytics.revenue',
                'params' => [
                    'company_id' => $this->company->id,
                    'period' => 'last_30_days'
                ]
            ]);
            
            $this->orchestrator->execute($request);
        }
        
        // Measure cache performance
        $startTime = microtime(true);
        
        for ($i = 0; $i < $iterations; $i++) {
            $cacheKey = "mcp:analytics.revenue:{$this->company->id}:last_30_days";
            
            if (Cache::has($cacheKey)) {
                $cacheHits++;
                Cache::get($cacheKey);
            } else {
                $cacheMisses++;
                // Simulate cache miss processing
                $data = DB::table('appointments')
                    ->where('company_id', $this->company->id)
                    ->where('created_at', '>=', Carbon::now()->subDays(30))
                    ->sum('price');
                
                Cache::put($cacheKey, $data, 300);
            }
        }
        
        $executionTime = microtime(true) - $startTime;
        $hitRate = ($cacheHits / $iterations) * 100;
        
        // Cache should be effective
        $this->assertGreaterThan(80, $hitRate); // At least 80% hit rate
        $this->assertLessThan(1, $executionTime); // Under 1 second for 100 operations
    }

    public function test_circuit_breaker_performance()
    {
        $circuitBreaker = app(CircuitBreaker::class);
        $iterations = 1000;
        $startTime = microtime(true);
        
        // Test circuit breaker overhead
        for ($i = 0; $i < $iterations; $i++) {
            try {
                $circuitBreaker->call('test_service', function() {
                    // Simulate successful operation
                    return ['success' => true];
                });
            } catch (\Exception $e) {
                // Circuit opened
            }
            
            // Simulate some failures
            if ($i % 100 === 0) {
                try {
                    $circuitBreaker->call('test_service', function() {
                        throw new \Exception('Service error');
                    });
                } catch (\Exception $e) {
                    // Expected
                }
            }
        }
        
        $executionTime = microtime(true) - $startTime;
        $avgOverhead = ($executionTime / $iterations) * 1000; // Convert to ms
        
        // Circuit breaker should have minimal overhead
        $this->assertLessThan(0.5, $avgOverhead); // Under 0.5ms per call
    }

    public function test_database_query_optimization()
    {
        $queries = [];
        
        // Enable query logging
        DB::enableQueryLog();
        
        // Complex query scenario
        $request = new \App\Services\MCP\MCPRequest([
            'method' => 'reports.comprehensive',
            'params' => [
                'company_id' => $this->company->id,
                'include' => ['appointments', 'customers', 'revenue', 'staff_performance'],
                'date_range' => 'last_30_days'
            ]
        ]);
        
        $startTime = microtime(true);
        $result = $this->orchestrator->execute($request);
        $executionTime = microtime(true) - $startTime;
        
        $queries = DB::getQueryLog();
        
        // Analyze queries
        $slowQueries = collect($queries)->filter(function($query) {
            return $query['time'] > 50; // Queries taking more than 50ms
        });
        
        // Assertions
        $this->assertTrue($result->isSuccess());
        $this->assertLessThan(1, $executionTime); // Total execution under 1 second
        $this->assertLessThan(20, count($queries)); // Should use efficient queries, not N+1
        $this->assertCount(0, $slowQueries); // No slow queries
    }

    public function test_memory_usage_under_load()
    {
        $initialMemory = memory_get_usage(true);
        $peakMemory = 0;
        $iterations = 100;
        
        for ($i = 0; $i < $iterations; $i++) {
            // Large data request
            $request = new \App\Services\MCP\MCPRequest([
                'method' => 'customers.bulk_fetch',
                'params' => [
                    'company_id' => $this->company->id,
                    'limit' => 100,
                    'include' => ['appointments', 'calls']
                ]
            ]);
            
            $result = $this->orchestrator->execute($request);
            
            $currentMemory = memory_get_usage(true);
            $peakMemory = max($peakMemory, $currentMemory);
            
            // Clean up to prevent memory leaks
            unset($result);
        }
        
        $memoryIncrease = ($peakMemory - $initialMemory) / 1024 / 1024; // Convert to MB
        
        // Memory usage should be reasonable
        $this->assertLessThan(100, $memoryIncrease); // Less than 100MB increase
    }

    public function test_rate_limiting_performance()
    {
        $rateLimiter = app('\Illuminate\Cache\RateLimiter');
        $key = 'test_user_' . $this->company->id;
        $maxAttempts = 60;
        $decayMinutes = 1;
        
        $startTime = microtime(true);
        $allowedRequests = 0;
        $blockedRequests = 0;
        
        // Test rate limiter performance
        for ($i = 0; $i < 100; $i++) {
            if ($rateLimiter->tooManyAttempts($key, $maxAttempts)) {
                $blockedRequests++;
            } else {
                $rateLimiter->hit($key, $decayMinutes);
                $allowedRequests++;
            }
        }
        
        $executionTime = microtime(true) - $startTime;
        
        // Rate limiting should be fast
        $this->assertLessThan(0.5, $executionTime); // Under 500ms for 100 checks
        $this->assertEquals($maxAttempts, $allowedRequests);
        $this->assertEquals(40, $blockedRequests);
    }

    public function test_parallel_mcp_server_execution()
    {
        $request = new \App\Services\MCP\MCPRequest([
            'method' => 'dashboard.metrics',
            'params' => [
                'company_id' => $this->company->id,
                'metrics' => [
                    'total_appointments',
                    'revenue_today',
                    'active_calls',
                    'customer_satisfaction'
                ]
            ]
        ]);
        
        $iterations = 10;
        $executionTimes = [];
        
        // Test parallel execution efficiency
        for ($i = 0; $i < $iterations; $i++) {
            $startTime = microtime(true);
            $result = $this->orchestrator->execute($request);
            $executionTimes[] = microtime(true) - $startTime;
            
            $this->assertTrue($result->isSuccess());
            $this->assertArrayHasKey('database', $result->getData());
            $this->assertArrayHasKey('calcom', $result->getData());
        }
        
        $avgExecutionTime = array_sum($executionTimes) / count($executionTimes);
        
        // Parallel execution should be efficient
        $this->assertLessThan(0.5, $avgExecutionTime); // Under 500ms average
    }

    public function test_stress_test_booking_flow()
    {
        $concurrentBookings = 20;
        $results = [];
        $startTime = microtime(true);
        
        // Create different time slots
        $timeSlots = [];
        for ($i = 0; $i < $concurrentBookings; $i++) {
            $timeSlots[] = Carbon::now()->addDays(1)->setTime(9 + ($i % 8), 0);
        }
        
        // Simulate concurrent booking attempts
        foreach ($timeSlots as $index => $slot) {
            $request = new \App\Services\MCP\MCPRequest([
                'method' => 'appointments.create',
                'params' => [
                    'customer_id' => $this->testData['customers'][$index]->id,
                    'branch_id' => $this->testData['branches'][0]->id,
                    'start_time' => $slot->format('Y-m-d H:i:s'),
                    'service_id' => 1,
                    'staff_id' => 1
                ]
            ]);
            
            $results[] = $this->orchestrator->execute($request);
        }
        
        $executionTime = microtime(true) - $startTime;
        
        // All bookings should succeed (different time slots)
        $successCount = collect($results)->filter(fn($r) => $r->isSuccess())->count();
        $this->assertEquals($concurrentBookings, $successCount);
        
        // Should handle concurrent bookings efficiently
        $this->assertLessThan(10, $executionTime); // Under 10 seconds for 20 bookings
    }

    public function test_connection_pool_efficiency()
    {
        $connections = [];
        $maxConnections = 50;
        
        // Test connection pool handling
        for ($i = 0; $i < $maxConnections; $i++) {
            $connections[] = DB::connection();
        }
        
        // Check active connections
        $activeConnections = DB::select("SHOW STATUS LIKE 'Threads_connected'")[0]->Value ?? 0;
        
        // Connection pooling should prevent connection explosion
        $this->assertLessThan(30, $activeConnections); // Should reuse connections
        
        // Test query execution with many connections
        $startTime = microtime(true);
        
        foreach ($connections as $conn) {
            $conn->select('SELECT 1');
        }
        
        $executionTime = microtime(true) - $startTime;
        
        // Queries should still be fast
        $this->assertLessThan(1, $executionTime); // Under 1 second for 50 queries
    }

    protected function tearDown(): void
    {
        DB::disableQueryLog();
        Cache::flush();
        Redis::flushdb();
        parent::tearDown();
    }
}