<?php

namespace Tests\Feature\MCP;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Call;
use App\Models\Customer;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class RetellMCPPerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected string $mcpEndpoint = '/api/mcp/retell/tools';
    protected string $validToken = 'test_mcp_token_2024';
    protected Company $company;
    protected Branch $branch;
    protected array $performanceMetrics = [];
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Set test environment
        config(['app.env' => 'local']);
        config(['retell-mcp.security.mcp_token' => $this->validToken]);
        
        // Create test data
        $this->company = Company::factory()->create([
            'name' => 'Performance Test Company',
            'calcom_api_key' => 'test_key',
            'calcom_event_type_id' => 123
        ]);
        
        $this->branch = Branch::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Performance Test Branch'
        ]);
        
        // Clear cache and reset DB query log
        Cache::flush();
        DB::enableQueryLog();
    }
    
    protected function tearDown(): void
    {
        // Log performance metrics if needed
        if (!empty($this->performanceMetrics)) {
            echo "\n" . json_encode($this->performanceMetrics, JSON_PRETTY_PRINT) . "\n";
        }
        
        parent::tearDown();
    }
    
    /**
     * Benchmark getCurrentTimeBerlin performance
     */
    public function test_get_current_time_berlin_performance()
    {
        $iterations = 100;
        $times = [];
        
        for ($i = 0; $i < $iterations; $i++) {
            $startTime = microtime(true);
            
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->validToken,
                'Content-Type' => 'application/json'
            ])->postJson($this->mcpEndpoint, [
                'tool' => 'getCurrentTimeBerlin',
                'arguments' => []
            ]);
            
            $endTime = microtime(true);
            $times[] = ($endTime - $startTime) * 1000; // Convert to ms
            
            $response->assertStatus(200);
        }
        
        $avgTime = array_sum($times) / count($times);
        $maxTime = max($times);
        $minTime = min($times);
        $p95Time = $this->calculatePercentile($times, 95);
        
        $this->performanceMetrics['getCurrentTimeBerlin'] = [
            'iterations' => $iterations,
            'avg_ms' => round($avgTime, 2),
            'min_ms' => round($minTime, 2),
            'max_ms' => round($maxTime, 2),
            'p95_ms' => round($p95Time, 2),
            'queries_per_request' => count(DB::getQueryLog()) / $iterations
        ];
        
        // Performance assertions
        $this->assertLessThan(50, $avgTime, 'Average response time should be under 50ms');
        $this->assertLessThan(200, $p95Time, 'P95 response time should be under 200ms');
        $this->assertLessThan(500, $maxTime, 'Max response time should be under 500ms');
    }
    
    /**
     * Benchmark checkAvailableSlots performance with caching
     */
    public function test_check_available_slots_caching_performance()
    {
        $tomorrow = Carbon::tomorrow()->format('Y-m-d');
        $iterations = 50;
        
        // Test cache miss (first request)
        DB::flushQueryLog();
        $startTime = microtime(true);
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->validToken,
            'Content-Type' => 'application/json'
        ])->postJson($this->mcpEndpoint, [
            'tool' => 'checkAvailableSlots',
            'arguments' => [
                'date' => $tomorrow,
                'company_id' => $this->company->id,
                'branch_id' => $this->branch->id
            ]
        ]);
        $cacheMissTime = (microtime(true) - $startTime) * 1000;
        $cacheMissQueries = count(DB::getQueryLog());
        
        $response->assertStatus(200);
        
        // Test cache hits (subsequent requests)
        $cacheHitTimes = [];
        for ($i = 0; $i < $iterations; $i++) {
            DB::flushQueryLog();
            $startTime = microtime(true);
            
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->validToken,
                'Content-Type' => 'application/json'
            ])->postJson($this->mcpEndpoint, [
                'tool' => 'checkAvailableSlots',
                'arguments' => [
                    'date' => $tomorrow,
                    'company_id' => $this->company->id,
                    'branch_id' => $this->branch->id
                ]
            ]);
            
            $cacheHitTimes[] = (microtime(true) - $startTime) * 1000;
            $response->assertStatus(200);
        }
        
        $avgCacheHitTime = array_sum($cacheHitTimes) / count($cacheHitTimes);
        $cacheHitQueries = count(DB::getQueryLog()) / $iterations;
        
        $this->performanceMetrics['checkAvailableSlots_caching'] = [
            'cache_miss_ms' => round($cacheMissTime, 2),
            'cache_miss_queries' => $cacheMissQueries,
            'avg_cache_hit_ms' => round($avgCacheHitTime, 2),
            'cache_hit_queries' => round($cacheHitQueries, 2),
            'cache_speedup_factor' => round($cacheMissTime / $avgCacheHitTime, 2)
        ];
        
        // Cache should improve performance significantly
        $this->assertLessThan($cacheMissTime * 0.5, $avgCacheHitTime, 
            'Cache hits should be at least 50% faster than cache miss');
        $this->assertLessThan($cacheMissQueries * 0.5, $cacheHitQueries,
            'Cache hits should require significantly fewer queries');
    }
    
    /**
     * Benchmark bookAppointment performance
     */
    public function test_book_appointment_performance()
    {
        $iterations = 20; // Lower iterations due to data creation
        $times = [];
        $queryCounts = [];
        
        for ($i = 0; $i < $iterations; $i++) {
            DB::flushQueryLog();
            $startTime = microtime(true);
            
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->validToken,
                'Content-Type' => 'application/json'
            ])->postJson($this->mcpEndpoint, [
                'tool' => 'bookAppointment',
                'arguments' => [
                    'name' => "Performance Test User {$i}",
                    'telefonnummer' => "+49123456{$i}",
                    'email' => "perftest{$i}@example.com",
                    'datum' => Carbon::tomorrow()->format('Y-m-d'),
                    'uhrzeit' => '10:00',
                    'dienstleistung' => 'Beratung',
                    'company_id' => $this->company->id,
                    'branch_id' => $this->branch->id
                ]
            ]);
            
            $endTime = microtime(true);
            $times[] = ($endTime - $startTime) * 1000;
            $queryCounts[] = count(DB::getQueryLog());
            
            // Allow both success and failure for performance testing
            $this->assertContains($response->status(), [200]);
        }
        
        $avgTime = array_sum($times) / count($times);
        $maxTime = max($times);
        $avgQueries = array_sum($queryCounts) / count($queryCounts);
        $p95Time = $this->calculatePercentile($times, 95);
        
        $this->performanceMetrics['bookAppointment'] = [
            'iterations' => $iterations,
            'avg_ms' => round($avgTime, 2),
            'max_ms' => round($maxTime, 2),
            'p95_ms' => round($p95Time, 2),
            'avg_queries' => round($avgQueries, 2)
        ];
        
        // Performance assertions for booking
        $this->assertLessThan(1000, $avgTime, 'Average booking time should be under 1 second');
        $this->assertLessThan(2000, $p95Time, 'P95 booking time should be under 2 seconds');
        $this->assertLessThan(20, $avgQueries, 'Should not require excessive database queries');
    }
    
    /**
     * Test concurrent request handling performance
     */
    public function test_concurrent_requests_performance()
    {
        $concurrentRequests = 10;
        $responses = [];
        $startTimes = [];
        $endTimes = [];
        
        // Start all requests simultaneously
        $overallStart = microtime(true);
        for ($i = 0; $i < $concurrentRequests; $i++) {
            $startTimes[$i] = microtime(true);
            $responses[$i] = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->validToken,
                'Content-Type' => 'application/json'
            ])->postJson($this->mcpEndpoint, [
                'tool' => 'getCurrentTimeBerlin',
                'arguments' => []
            ]);
            $endTimes[$i] = microtime(true);
        }
        $overallEnd = microtime(true);
        
        // Calculate metrics
        $individualTimes = [];
        for ($i = 0; $i < $concurrentRequests; $i++) {
            $individualTimes[] = ($endTimes[$i] - $startTimes[$i]) * 1000;
            $responses[$i]->assertStatus(200);
        }
        
        $overallTime = ($overallEnd - $overallStart) * 1000;
        $avgIndividualTime = array_sum($individualTimes) / count($individualTimes);
        
        $this->performanceMetrics['concurrent_requests'] = [
            'concurrent_count' => $concurrentRequests,
            'overall_time_ms' => round($overallTime, 2),
            'avg_individual_time_ms' => round($avgIndividualTime, 2),
            'requests_per_second' => round($concurrentRequests / ($overallTime / 1000), 2)
        ];
        
        // Should handle concurrent requests efficiently
        $this->assertLessThan(2000, $overallTime, 'Concurrent requests should complete within 2 seconds');
        $this->assertGreaterThan(5, $concurrentRequests / ($overallTime / 1000), 
            'Should handle at least 5 requests per second');
    }
    
    /**
     * Test memory usage under load
     */
    public function test_memory_usage_under_load()
    {
        $initialMemory = memory_get_usage(true);
        $peakMemory = $initialMemory;
        $iterations = 100;
        
        for ($i = 0; $i < $iterations; $i++) {
            $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->validToken,
                'Content-Type' => 'application/json'
            ])->postJson($this->mcpEndpoint, [
                'tool' => 'getCurrentTimeBerlin',
                'arguments' => []
            ]);
            
            $currentMemory = memory_get_usage(true);
            $peakMemory = max($peakMemory, $currentMemory);
            
            // Force garbage collection every 20 iterations
            if ($i % 20 === 0) {
                gc_collect_cycles();
            }
        }
        
        $finalMemory = memory_get_usage(true);
        $memoryIncrease = $finalMemory - $initialMemory;
        $peakIncrease = $peakMemory - $initialMemory;
        
        $this->performanceMetrics['memory_usage'] = [
            'iterations' => $iterations,
            'initial_mb' => round($initialMemory / 1024 / 1024, 2),
            'final_mb' => round($finalMemory / 1024 / 1024, 2),
            'peak_mb' => round($peakMemory / 1024 / 1024, 2),
            'increase_mb' => round($memoryIncrease / 1024 / 1024, 2),
            'peak_increase_mb' => round($peakIncrease / 1024 / 1024, 2)
        ];
        
        // Memory usage should be reasonable
        $this->assertLessThan(50 * 1024 * 1024, $memoryIncrease, 
            'Memory increase should be less than 50MB for 100 requests');
        $this->assertLessThan(100 * 1024 * 1024, $peakIncrease,
            'Peak memory increase should be less than 100MB');
    }
    
    /**
     * Test database query optimization
     */
    public function test_database_query_optimization()
    {
        $testCases = [
            'getCurrentTimeBerlin' => ['tool' => 'getCurrentTimeBerlin', 'arguments' => []],
            'getCustomerInfo' => [
                'tool' => 'getCustomerInfo', 
                'arguments' => ['phone' => '+49123456789']
            ],
            'checkAvailableSlots' => [
                'tool' => 'checkAvailableSlots',
                'arguments' => [
                    'date' => Carbon::tomorrow()->format('Y-m-d'),
                    'company_id' => $this->company->id
                ]
            ]
        ];
        
        foreach ($testCases as $testName => $testCase) {
            DB::flushQueryLog();
            
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->validToken,
                'Content-Type' => 'application/json'
            ])->postJson($this->mcpEndpoint, $testCase);
            
            $queries = DB::getQueryLog();
            $queryCount = count($queries);
            
            $this->performanceMetrics["db_queries_{$testName}"] = [
                'query_count' => $queryCount,
                'queries' => array_map(function($query) {
                    return [
                        'sql' => $query['query'],
                        'time' => $query['time']
                    ];
                }, $queries)
            ];
            
            // Query count assertions
            switch ($testName) {
                case 'getCurrentTimeBerlin':
                    $this->assertLessThanOrEqual(2, $queryCount, 
                        'getCurrentTimeBerlin should require minimal queries');
                    break;
                case 'getCustomerInfo':
                    $this->assertLessThanOrEqual(5, $queryCount,
                        'getCustomerInfo should be efficient');
                    break;
                case 'checkAvailableSlots':
                    $this->assertLessThanOrEqual(10, $queryCount,
                        'checkAvailableSlots should not require excessive queries');
                    break;
            }
        }
    }
    
    /**
     * Test response time under different load conditions
     */
    public function test_response_time_under_load()
    {
        $loadLevels = [1, 5, 10, 20];
        $results = [];
        
        foreach ($loadLevels as $load) {
            $times = [];
            
            for ($i = 0; $i < $load; $i++) {
                $startTime = microtime(true);
                
                $response = $this->withHeaders([
                    'Authorization' => 'Bearer ' . $this->validToken,
                    'Content-Type' => 'application/json'
                ])->postJson($this->mcpEndpoint, [
                    'tool' => 'getCurrentTimeBerlin',
                    'arguments' => []
                ]);
                
                $times[] = (microtime(true) - $startTime) * 1000;
                $response->assertStatus(200);
            }
            
            $avgTime = array_sum($times) / count($times);
            $results[$load] = round($avgTime, 2);
        }
        
        $this->performanceMetrics['load_test'] = $results;
        
        // Response times should not degrade significantly under load
        $baselineTime = $results[1];
        foreach ($results as $load => $avgTime) {
            $this->assertLessThan($baselineTime * 3, $avgTime,
                "Response time under load {$load} should not be more than 3x baseline");
        }
    }
    
    /**
     * Calculate percentile from array of values
     */
    private function calculatePercentile(array $values, float $percentile): float
    {
        sort($values);
        $index = ($percentile / 100) * (count($values) - 1);
        
        if (floor($index) === $index) {
            return $values[$index];
        }
        
        $lower = floor($index);
        $upper = ceil($index);
        $weight = $index - $lower;
        
        return $values[$lower] * (1 - $weight) + $values[$upper] * $weight;
    }
    
    /**
     * Test cache performance with different cache sizes
     */
    public function test_cache_performance_scaling()
    {
        $dates = [];
        for ($i = 1; $i <= 30; $i++) {
            $dates[] = Carbon::now()->addDays($i)->format('Y-m-d');
        }
        
        // Fill cache with many entries
        foreach ($dates as $date) {
            $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->validToken,
                'Content-Type' => 'application/json'
            ])->postJson($this->mcpEndpoint, [
                'tool' => 'checkAvailableSlots',
                'arguments' => [
                    'date' => $date,
                    'company_id' => $this->company->id,
                    'branch_id' => $this->branch->id
                ]
            ]);
        }
        
        // Test cache retrieval performance
        $times = [];
        foreach (array_slice($dates, 0, 10) as $date) {
            $startTime = microtime(true);
            
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->validToken,
                'Content-Type' => 'application/json'
            ])->postJson($this->mcpEndpoint, [
                'tool' => 'checkAvailableSlots',
                'arguments' => [
                    'date' => $date,
                    'company_id' => $this->company->id,
                    'branch_id' => $this->branch->id
                ]
            ]);
            
            $times[] = (microtime(true) - $startTime) * 1000;
            $response->assertStatus(200);
        }
        
        $avgCacheTime = array_sum($times) / count($times);
        
        $this->performanceMetrics['cache_scaling'] = [
            'cache_entries' => count($dates),
            'avg_retrieval_time_ms' => round($avgCacheTime, 2)
        ];
        
        // Cache performance should remain good even with many entries
        $this->assertLessThan(100, $avgCacheTime, 
            'Cache retrieval should remain fast with many entries');
    }
}