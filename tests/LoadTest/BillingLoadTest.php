<?php

namespace Tests\LoadTest;

/**
 * Billing System Load Testing Framework
 * 
 * This comprehensive load testing suite simulates high-volume production scenarios
 * to identify performance bottlenecks, memory leaks, and race conditions.
 * 
 * Usage:
 * php artisan test:load --scenario=billing --users=1000 --duration=60
 */

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Transaction;
use App\Models\BalanceTopup;
use App\Services\BillingChainService;
use App\Services\StripeCheckoutService;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\RequestException;

class BillingLoadTest
{
    private int $concurrentUsers;
    private int $testDuration;
    private array $metrics = [];
    private array $errors = [];
    private float $startTime;
    private Client $httpClient;
    private array $testUsers = [];
    private array $testTenants = [];
    private BillingChainService $billingService;
    
    // Performance thresholds
    private const MAX_RESPONSE_TIME_MS = 500;
    private const MAX_ERROR_RATE = 0.01; // 1%
    private const MIN_THROUGHPUT_RPS = 100; // Requests per second
    private const MAX_MEMORY_MB = 256;
    private const MAX_DB_CONNECTIONS = 100;
    
    public function __construct(int $concurrentUsers = 100, int $testDuration = 60)
    {
        $this->concurrentUsers = $concurrentUsers;
        $this->testDuration = $testDuration;
        $this->httpClient = new Client([
            'base_uri' => config('app.url'),
            'timeout' => 10,
            'verify' => false, // For testing only
        ]);
        $this->billingService = new BillingChainService();
    }
    
    /**
     * Run complete load test suite
     */
    public function runFullSuite(): array
    {
        $this->printHeader();
        
        // Setup test data
        $this->setupTestEnvironment();
        
        // Run test scenarios
        $scenarios = [
            'concurrent_topups' => 'Concurrent Topup Sessions',
            'billing_chain_stress' => 'Billing Chain Processing',
            'mixed_operations' => 'Mixed Operations',
            'webhook_bombardment' => 'Webhook Processing',
            'database_stress' => 'Database Operations',
            'cache_stress' => 'Cache Operations',
            'api_endpoints' => 'API Endpoint Stress',
            'auto_topup_triggers' => 'Auto-Topup Triggers',
        ];
        
        $results = [];
        
        foreach ($scenarios as $scenario => $description) {
            $this->printScenario($description);
            $results[$scenario] = $this->runScenario($scenario);
            $this->printScenarioResults($results[$scenario]);
            
            // Cool down between scenarios
            sleep(2);
        }
        
        // Cleanup
        $this->cleanup();
        
        // Print summary
        $this->printSummary($results);
        
        return $results;
    }
    
    /**
     * Setup test environment with data
     */
    private function setupTestEnvironment(): void
    {
        echo "Setting up test environment...\n";
        
        // Create test tenants hierarchy
        $platform = Tenant::firstOrCreate([
            'id' => 'loadtest-platform',
        ], [
            'name' => 'LoadTest Platform',
            'tenant_type' => 'platform',
            'balance_cents' => 0,
            'is_active' => true,
        ]);
        
        // Create test resellers
        for ($i = 1; $i <= 10; $i++) {
            $reseller = Tenant::firstOrCreate([
                'id' => "loadtest-reseller-{$i}",
            ], [
                'name' => "LoadTest Reseller {$i}",
                'tenant_type' => 'reseller',
                'parent_id' => $platform->id,
                'balance_cents' => 1000000, // 10,000€
                'settings' => [
                    'commission_rate' => 0.25,
                    'pricing' => [
                        'call_minutes' => 40,
                        'api_calls' => 15,
                        'appointments' => 150,
                    ],
                ],
                'is_active' => true,
            ]);
            
            // Create customers for each reseller
            for ($j = 1; $j <= ceil($this->concurrentUsers / 10); $j++) {
                $customer = Tenant::firstOrCreate([
                    'id' => "loadtest-customer-{$i}-{$j}",
                ], [
                    'name' => "LoadTest Customer {$i}-{$j}",
                    'tenant_type' => 'reseller_customer',
                    'parent_id' => $reseller->id,
                    'balance_cents' => rand(1000, 50000),
                    'settings' => [
                        'auto_topup' => [
                            'enabled' => rand(0, 1) == 1,
                            'threshold_cents' => 2000,
                            'amount_cents' => 5000,
                        ],
                    ],
                    'is_active' => true,
                ]);
                
                $this->testTenants[] = $customer;
                
                // Create user for API testing
                $user = User::firstOrCreate([
                    'email' => "loadtest-{$i}-{$j}@test.com",
                ], [
                    'name' => "LoadTest User {$i}-{$j}",
                    'tenant_id' => $customer->id,
                    'password' => bcrypt('password'),
                ]);
                
                $this->testUsers[] = $user;
            }
        }
        
        echo "Created " . count($this->testTenants) . " test tenants\n";
        echo "Created " . count($this->testUsers) . " test users\n";
    }
    
    /**
     * Run specific test scenario
     */
    private function runScenario(string $scenario): array
    {
        $this->startTime = microtime(true);
        $this->metrics = [
            'requests' => 0,
            'successful' => 0,
            'failed' => 0,
            'response_times' => [],
            'memory_usage' => [],
            'db_connections' => [],
            'errors' => [],
        ];
        
        switch ($scenario) {
            case 'concurrent_topups':
                return $this->testConcurrentTopups();
                
            case 'billing_chain_stress':
                return $this->testBillingChainStress();
                
            case 'mixed_operations':
                return $this->testMixedOperations();
                
            case 'webhook_bombardment':
                return $this->testWebhookBombardment();
                
            case 'database_stress':
                return $this->testDatabaseStress();
                
            case 'cache_stress':
                return $this->testCacheStress();
                
            case 'api_endpoints':
                return $this->testApiEndpoints();
                
            case 'auto_topup_triggers':
                return $this->testAutoTopupTriggers();
                
            default:
                return ['error' => 'Unknown scenario'];
        }
    }
    
    /**
     * Test concurrent topup sessions
     */
    private function testConcurrentTopups(): array
    {
        $requests = [];
        $endTime = time() + $this->testDuration;
        
        while (time() < $endTime) {
            // Create batch of concurrent requests
            for ($i = 0; $i < $this->concurrentUsers; $i++) {
                $tenant = $this->testTenants[array_rand($this->testTenants)];
                
                $requests[] = new Request('POST', '/api/billing/topup', [
                    'Authorization' => 'Bearer test-token',
                    'Content-Type' => 'application/json',
                    'Idempotency-Key' => uniqid('loadtest-'),
                ], json_encode([
                    'amount_cents' => rand(1000, 10000),
                    'payment_method' => 'card',
                    'tenant_id' => $tenant->id,
                ]));
            }
            
            // Execute batch
            $this->executeBatch($requests);
            $requests = [];
            
            // Record metrics
            $this->recordSystemMetrics();
        }
        
        return $this->calculateResults();
    }
    
    /**
     * Test billing chain under stress
     */
    private function testBillingChainStress(): array
    {
        $operations = 0;
        $endTime = time() + $this->testDuration;
        
        while (time() < $endTime) {
            $promises = [];
            
            // Create concurrent billing operations
            for ($i = 0; $i < $this->concurrentUsers; $i++) {
                $tenant = $this->testTenants[array_rand($this->testTenants)];
                
                $promises[] = function () use ($tenant) {
                    $startTime = microtime(true);
                    
                    try {
                        $result = $this->billingService->processBillingChain(
                            $tenant,
                            'call_minutes',
                            rand(1, 60)
                        );
                        
                        $this->metrics['successful']++;
                        $this->metrics['response_times'][] = (microtime(true) - $startTime) * 1000;
                        
                        return $result;
                    } catch (\Exception $e) {
                        $this->metrics['failed']++;
                        $this->metrics['errors'][] = $e->getMessage();
                        return ['error' => $e->getMessage()];
                    }
                };
            }
            
            // Execute all promises
            foreach ($promises as $promise) {
                $promise();
                $operations++;
            }
            
            $this->metrics['requests'] = $operations;
            $this->recordSystemMetrics();
        }
        
        return $this->calculateResults();
    }
    
    /**
     * Test mixed operations simultaneously
     */
    private function testMixedOperations(): array
    {
        $endTime = time() + $this->testDuration;
        $operations = ['topup', 'usage', 'balance_check', 'transactions', 'auto_topup'];
        
        while (time() < $endTime) {
            $batch = [];
            
            for ($i = 0; $i < $this->concurrentUsers; $i++) {
                $operation = $operations[array_rand($operations)];
                $tenant = $this->testTenants[array_rand($this->testTenants)];
                
                switch ($operation) {
                    case 'topup':
                        $batch[] = new Request('POST', '/api/billing/topup', [
                            'Content-Type' => 'application/json',
                        ], json_encode([
                            'amount_cents' => rand(1000, 10000),
                            'tenant_id' => $tenant->id,
                        ]));
                        break;
                        
                    case 'usage':
                        $this->billingService->processBillingChain(
                            $tenant,
                            'api_calls',
                            rand(10, 100)
                        );
                        break;
                        
                    case 'balance_check':
                        $batch[] = new Request('GET', "/api/billing/balance?tenant_id={$tenant->id}");
                        break;
                        
                    case 'transactions':
                        $batch[] = new Request('GET', "/api/billing/transactions?tenant_id={$tenant->id}&limit=50");
                        break;
                        
                    case 'auto_topup':
                        $batch[] = new Request('POST', '/api/billing/check-auto-topup', [
                            'Content-Type' => 'application/json',
                        ], json_encode(['tenant_id' => $tenant->id]));
                        break;
                }
            }
            
            if (!empty($batch)) {
                $this->executeBatch($batch);
            }
            
            $this->recordSystemMetrics();
        }
        
        return $this->calculateResults();
    }
    
    /**
     * Test webhook processing under load
     */
    private function testWebhookBombardment(): array
    {
        $endTime = time() + $this->testDuration;
        
        while (time() < $endTime) {
            $batch = [];
            
            // Create webhook payloads
            for ($i = 0; $i < $this->concurrentUsers; $i++) {
                $payload = [
                    'type' => 'checkout.session.completed',
                    'data' => [
                        'object' => [
                            'id' => 'cs_loadtest_' . uniqid(),
                            'payment_intent' => 'pi_loadtest_' . uniqid(),
                            'amount_total' => rand(1000, 10000),
                            'customer' => 'cus_loadtest_' . uniqid(),
                        ],
                    ],
                ];
                
                $batch[] = new Request('POST', '/webhooks/stripe', [
                    'Content-Type' => 'application/json',
                    'Stripe-Signature' => 'test_signature',
                ], json_encode($payload));
            }
            
            $this->executeBatch($batch);
            $this->recordSystemMetrics();
        }
        
        return $this->calculateResults();
    }
    
    /**
     * Test database operations under stress
     */
    private function testDatabaseStress(): array
    {
        $endTime = time() + $this->testDuration;
        
        while (time() < $endTime) {
            $operations = [];
            
            for ($i = 0; $i < $this->concurrentUsers; $i++) {
                $operations[] = function () {
                    $startTime = microtime(true);
                    
                    try {
                        // Complex query with joins
                        $result = DB::select("
                            SELECT 
                                t.id,
                                t.balance_cents,
                                COUNT(tr.id) as transaction_count,
                                SUM(tr.amount_cents) as total_spent,
                                AVG(tr.amount_cents) as avg_transaction
                            FROM tenants t
                            LEFT JOIN transactions tr ON t.id = tr.tenant_id
                            WHERE t.tenant_type = 'reseller_customer'
                            GROUP BY t.id, t.balance_cents
                            HAVING COUNT(tr.id) > 0
                            ORDER BY total_spent DESC
                            LIMIT 100
                        ");
                        
                        $this->metrics['successful']++;
                        $this->metrics['response_times'][] = (microtime(true) - $startTime) * 1000;
                        
                        return $result;
                    } catch (\Exception $e) {
                        $this->metrics['failed']++;
                        $this->metrics['errors'][] = $e->getMessage();
                        return null;
                    }
                };
            }
            
            // Execute all operations
            foreach ($operations as $operation) {
                $operation();
                $this->metrics['requests']++;
            }
            
            $this->recordSystemMetrics();
        }
        
        return $this->calculateResults();
    }
    
    /**
     * Test cache operations under stress
     */
    private function testCacheStress(): array
    {
        $endTime = time() + $this->testDuration;
        $cacheKeys = [];
        
        // Pre-populate cache keys
        for ($i = 0; $i < 1000; $i++) {
            $cacheKeys[] = "loadtest:key:{$i}";
        }
        
        while (time() < $endTime) {
            $operations = [];
            
            for ($i = 0; $i < $this->concurrentUsers; $i++) {
                $operation = rand(0, 2); // 0: read, 1: write, 2: delete
                $key = $cacheKeys[array_rand($cacheKeys)];
                
                $operations[] = function () use ($operation, $key) {
                    $startTime = microtime(true);
                    
                    try {
                        switch ($operation) {
                            case 0: // Read
                                Cache::get($key);
                                break;
                            case 1: // Write
                                Cache::put($key, ['data' => str_repeat('x', 1024)], 60);
                                break;
                            case 2: // Delete
                                Cache::forget($key);
                                break;
                        }
                        
                        $this->metrics['successful']++;
                        $this->metrics['response_times'][] = (microtime(true) - $startTime) * 1000;
                    } catch (\Exception $e) {
                        $this->metrics['failed']++;
                        $this->metrics['errors'][] = $e->getMessage();
                    }
                };
            }
            
            foreach ($operations as $operation) {
                $operation();
                $this->metrics['requests']++;
            }
            
            $this->recordSystemMetrics();
        }
        
        return $this->calculateResults();
    }
    
    /**
     * Test API endpoints under load
     */
    private function testApiEndpoints(): array
    {
        $endpoints = [
            ['GET', '/api/billing/balance'],
            ['GET', '/api/billing/transactions'],
            ['GET', '/api/billing/topups'],
            ['POST', '/api/billing/topup'],
            ['PUT', '/api/billing/auto-topup'],
            ['POST', '/api/billing/check-auto-topup'],
            ['GET', '/api/billing/transactions/export'],
        ];
        
        $endTime = time() + $this->testDuration;
        
        while (time() < $endTime) {
            $batch = [];
            
            for ($i = 0; $i < $this->concurrentUsers; $i++) {
                $endpoint = $endpoints[array_rand($endpoints)];
                $tenant = $this->testTenants[array_rand($this->testTenants)];
                
                $headers = [
                    'Authorization' => 'Bearer test-token',
                    'Content-Type' => 'application/json',
                ];
                
                $body = null;
                if ($endpoint[0] === 'POST' || $endpoint[0] === 'PUT') {
                    $body = json_encode([
                        'tenant_id' => $tenant->id,
                        'amount_cents' => rand(1000, 10000),
                        'enabled' => true,
                    ]);
                }
                
                $batch[] = new Request(
                    $endpoint[0],
                    $endpoint[1] . "?tenant_id={$tenant->id}",
                    $headers,
                    $body
                );
            }
            
            $this->executeBatch($batch);
            $this->recordSystemMetrics();
        }
        
        return $this->calculateResults();
    }
    
    /**
     * Test auto-topup trigger scenarios
     */
    private function testAutoTopupTriggers(): array
    {
        $endTime = time() + $this->testDuration;
        
        while (time() < $endTime) {
            $operations = [];
            
            // Set random tenants to low balance
            foreach ($this->testTenants as $tenant) {
                if (rand(0, 10) > 7) { // 30% chance
                    $tenant->update(['balance_cents' => rand(100, 1900)]); // Below threshold
                }
            }
            
            // Trigger auto-topup checks
            for ($i = 0; $i < $this->concurrentUsers; $i++) {
                $tenant = $this->testTenants[array_rand($this->testTenants)];
                
                $operations[] = function () use ($tenant) {
                    $startTime = microtime(true);
                    
                    try {
                        // Simulate auto-topup check
                        if ($tenant->balance_cents < 2000 && 
                            ($tenant->settings['auto_topup']['enabled'] ?? false)) {
                            
                            // Process auto-topup
                            $topup = BalanceTopup::create([
                                'tenant_id' => $tenant->id,
                                'amount_cents' => 5000,
                                'is_auto_topup' => true,
                                'status' => 'pending',
                            ]);
                            
                            // Simulate processing
                            usleep(rand(10000, 50000)); // 10-50ms
                            
                            $topup->update(['status' => 'completed']);
                            $tenant->increment('balance_cents', 5000);
                        }
                        
                        $this->metrics['successful']++;
                        $this->metrics['response_times'][] = (microtime(true) - $startTime) * 1000;
                    } catch (\Exception $e) {
                        $this->metrics['failed']++;
                        $this->metrics['errors'][] = $e->getMessage();
                    }
                };
            }
            
            foreach ($operations as $operation) {
                $operation();
                $this->metrics['requests']++;
            }
            
            $this->recordSystemMetrics();
        }
        
        return $this->calculateResults();
    }
    
    /**
     * Execute batch of HTTP requests
     */
    private function executeBatch(array $requests): void
    {
        $pool = new Pool($this->httpClient, $requests, [
            'concurrency' => min(50, count($requests)),
            'fulfilled' => function ($response, $index) {
                $this->metrics['successful']++;
                $this->metrics['requests']++;
            },
            'rejected' => function (RequestException $reason, $index) {
                $this->metrics['failed']++;
                $this->metrics['requests']++;
                $this->metrics['errors'][] = $reason->getMessage();
            },
        ]);
        
        $promise = $pool->promise();
        $promise->wait();
    }
    
    /**
     * Record system metrics
     */
    private function recordSystemMetrics(): void
    {
        // Memory usage
        $this->metrics['memory_usage'][] = memory_get_usage(true) / 1024 / 1024; // MB
        
        // Database connections
        try {
            $connections = DB::select("SHOW STATUS LIKE 'Threads_connected'")[0]->Value ?? 0;
            $this->metrics['db_connections'][] = (int) $connections;
        } catch (\Exception $e) {
            // Ignore
        }
    }
    
    /**
     * Calculate test results
     */
    private function calculateResults(): array
    {
        $duration = microtime(true) - $this->startTime;
        
        $avgResponseTime = !empty($this->metrics['response_times']) 
            ? array_sum($this->metrics['response_times']) / count($this->metrics['response_times'])
            : 0;
        
        $maxResponseTime = !empty($this->metrics['response_times'])
            ? max($this->metrics['response_times'])
            : 0;
        
        $minResponseTime = !empty($this->metrics['response_times'])
            ? min($this->metrics['response_times'])
            : 0;
        
        $p95ResponseTime = $this->calculatePercentile($this->metrics['response_times'], 95);
        $p99ResponseTime = $this->calculatePercentile($this->metrics['response_times'], 99);
        
        $throughput = $this->metrics['requests'] / $duration;
        $errorRate = $this->metrics['requests'] > 0 
            ? $this->metrics['failed'] / $this->metrics['requests']
            : 0;
        
        $avgMemory = !empty($this->metrics['memory_usage'])
            ? array_sum($this->metrics['memory_usage']) / count($this->metrics['memory_usage'])
            : 0;
        
        $maxMemory = !empty($this->metrics['memory_usage'])
            ? max($this->metrics['memory_usage'])
            : 0;
        
        $avgDbConnections = !empty($this->metrics['db_connections'])
            ? array_sum($this->metrics['db_connections']) / count($this->metrics['db_connections'])
            : 0;
        
        $maxDbConnections = !empty($this->metrics['db_connections'])
            ? max($this->metrics['db_connections'])
            : 0;
        
        return [
            'duration' => round($duration, 2),
            'total_requests' => $this->metrics['requests'],
            'successful' => $this->metrics['successful'],
            'failed' => $this->metrics['failed'],
            'throughput_rps' => round($throughput, 2),
            'error_rate' => round($errorRate * 100, 2),
            'response_times' => [
                'avg' => round($avgResponseTime, 2),
                'min' => round($minResponseTime, 2),
                'max' => round($maxResponseTime, 2),
                'p95' => round($p95ResponseTime, 2),
                'p99' => round($p99ResponseTime, 2),
            ],
            'memory' => [
                'avg_mb' => round($avgMemory, 2),
                'max_mb' => round($maxMemory, 2),
            ],
            'database' => [
                'avg_connections' => round($avgDbConnections, 2),
                'max_connections' => round($maxDbConnections, 2),
            ],
            'passed' => $this->evaluateResults($throughput, $errorRate, $avgResponseTime, $maxMemory),
        ];
    }
    
    /**
     * Calculate percentile
     */
    private function calculatePercentile(array $data, int $percentile): float
    {
        if (empty($data)) {
            return 0;
        }
        
        sort($data);
        $index = ceil(($percentile / 100) * count($data)) - 1;
        return $data[$index] ?? 0;
    }
    
    /**
     * Evaluate if results meet thresholds
     */
    private function evaluateResults(
        float $throughput,
        float $errorRate,
        float $avgResponseTime,
        float $maxMemory
    ): bool {
        return $throughput >= self::MIN_THROUGHPUT_RPS
            && $errorRate <= self::MAX_ERROR_RATE
            && $avgResponseTime <= self::MAX_RESPONSE_TIME_MS
            && $maxMemory <= self::MAX_MEMORY_MB;
    }
    
    /**
     * Cleanup test data
     */
    private function cleanup(): void
    {
        echo "\nCleaning up test data...\n";
        
        // Delete test transactions
        Transaction::whereIn('tenant_id', array_column($this->testTenants, 'id'))->delete();
        
        // Delete test topups
        BalanceTopup::whereIn('tenant_id', array_column($this->testTenants, 'id'))->delete();
        
        // Delete test users
        User::whereIn('id', array_column($this->testUsers, 'id'))->delete();
        
        // Delete test tenants
        Tenant::where('id', 'like', 'loadtest-%')->delete();
        
        // Clear cache
        Cache::flush();
        
        echo "Cleanup complete\n";
    }
    
    /**
     * Print test header
     */
    private function printHeader(): void
    {
        echo "\n";
        echo "╔════════════════════════════════════════════════════════════╗\n";
        echo "║            BILLING SYSTEM LOAD TEST SUITE                 ║\n";
        echo "║                                                            ║\n";
        echo "║  Concurrent Users: {$this->concurrentUsers}                                      ║\n";
        echo "║  Test Duration: {$this->testDuration} seconds                              ║\n";
        echo "╚════════════════════════════════════════════════════════════╝\n";
        echo "\n";
    }
    
    /**
     * Print scenario header
     */
    private function printScenario(string $description): void
    {
        echo "\n▶ Testing: {$description}\n";
        echo str_repeat("─", 60) . "\n";
    }
    
    /**
     * Print scenario results
     */
    private function printScenarioResults(array $results): void
    {
        $status = $results['passed'] ? "✅ PASSED" : "❌ FAILED";
        
        echo "  Status: {$status}\n";
        echo "  Throughput: {$results['throughput_rps']} req/s\n";
        echo "  Error Rate: {$results['error_rate']}%\n";
        echo "  Avg Response: {$results['response_times']['avg']}ms\n";
        echo "  P95 Response: {$results['response_times']['p95']}ms\n";
        echo "  P99 Response: {$results['response_times']['p99']}ms\n";
        echo "  Max Memory: {$results['memory']['max_mb']}MB\n";
        echo "  Max DB Connections: {$results['database']['max_connections']}\n";
    }
    
    /**
     * Print final summary
     */
    private function printSummary(array $allResults): void
    {
        echo "\n";
        echo "═══════════════════════════════════════════════════════════════\n";
        echo "                        LOAD TEST SUMMARY                       \n";
        echo "═══════════════════════════════════════════════════════════════\n\n";
        
        $totalPassed = 0;
        $totalScenarios = count($allResults);
        
        foreach ($allResults as $scenario => $results) {
            if ($results['passed']) {
                $totalPassed++;
            }
        }
        
        $passRate = ($totalPassed / $totalScenarios) * 100;
        
        echo "Total Scenarios: {$totalScenarios}\n";
        echo "Passed: {$totalPassed}\n";
        echo "Failed: " . ($totalScenarios - $totalPassed) . "\n";
        echo "Pass Rate: " . round($passRate, 2) . "%\n\n";
        
        if ($passRate == 100) {
            echo "🎉 SYSTEM PASSED ALL LOAD TESTS! 🎉\n";
            echo "The billing system can handle production load.\n";
        } elseif ($passRate >= 80) {
            echo "⚠️  SYSTEM PASSED MOST TESTS\n";
            echo "Review failed scenarios before production deployment.\n";
        } else {
            echo "❌ SYSTEM FAILED LOAD TESTING\n";
            echo "Significant performance issues detected. Do not deploy.\n";
        }
        
        echo "\n";
    }
}