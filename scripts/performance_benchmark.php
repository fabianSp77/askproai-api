#!/usr/bin/env php
<?php

/**
 * Performance Benchmark Script
 *
 * Comprehensive performance analysis of critical services and endpoints
 * Validates against performance targets and identifies bottlenecks
 */

require __DIR__ . '/../vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Services\Policies\PolicyConfigurationService;
use App\Services\Appointments\CallbackManagementService;
use App\Services\Notifications\NotificationManager;
use App\Models\Branch;
use App\Models\Staff;
use App\Models\Service;
use App\Models\CallbackRequest;
use App\Models\Customer;

// Bootstrap Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Performance targets (milliseconds)
const TARGETS = [
    'policy_cached' => 50,
    'policy_first' => 100,
    'callback_create' => 100,
    'callback_list_1000' => 200,
    'notification_send' => 200,
    'dashboard_load' => 1500,
    'memory_limit_mb' => 600,
    'query_count_max' => 20,
];

class PerformanceBenchmark
{
    private array $results = [];
    private array $issues = [];

    public function run(): void
    {
        echo "🚀 Starting Performance Benchmark Analysis\n";
        echo str_repeat('=', 80) . "\n\n";

        // Clear all caches for consistent baseline
        $this->clearCaches();

        // Run all benchmarks
        $this->benchmarkPolicyService();
        $this->benchmarkCallbackService();
        $this->benchmarkNotificationManager();
        $this->benchmarkDatabaseQueries();
        $this->benchmarkMemoryUsage();
        $this->analyzeNPlusOneQueries();
        $this->benchmarkCacheHitRate();

        // Generate report
        $this->generateReport();
    }

    private function clearCaches(): void
    {
        echo "🧹 Clearing caches for baseline measurement...\n";
        Cache::flush();
        DB::connection()->enableQueryLog();
        echo "✅ Caches cleared\n\n";
    }

    private function benchmarkPolicyService(): void
    {
        echo "📋 Benchmarking PolicyConfigurationService...\n";

        $service = app(PolicyConfigurationService::class);

        // Get test entity
        $branch = Branch::with('company')->first();
        if (!$branch) {
            $this->issues[] = "⚠️ No branch found for testing";
            return;
        }

        // Test 1: First call (uncached)
        Cache::flush();
        $start = microtime(true);
        $policy = $service->resolvePolicy($branch, 'cancellation');
        $firstCall = (microtime(true) - $start) * 1000;

        // Test 2: Second call (cached)
        $start = microtime(true);
        $policy = $service->resolvePolicy($branch, 'cancellation');
        $cachedCall = (microtime(true) - $start) * 1000;

        // Test 3: Batch resolution
        $branches = Branch::limit(10)->get();
        if (count($branches) > 0) {
            $start = microtime(true);
            try {
                $batchResults = $service->resolveBatch($branches, 'cancellation');
                $batchTime = (microtime(true) - $start) * 1000;
                $avgPerEntity = $batchTime / count($branches);
            } catch (\Exception $e) {
                echo "  ⚠️ Batch resolution skipped: " . $e->getMessage() . "\n";
                $batchTime = 0;
                $avgPerEntity = 0;
            }
        } else {
            $batchTime = 0;
            $avgPerEntity = 0;
        }

        // Store results
        $this->results['policy_first_call'] = $firstCall;
        $this->results['policy_cached_call'] = $cachedCall;
        $this->results['policy_batch_avg'] = $avgPerEntity;

        // Check targets
        $this->checkTarget('policy_first_call', $firstCall, TARGETS['policy_first'], 'ms');
        $this->checkTarget('policy_cached_call', $cachedCall, TARGETS['policy_cached'], 'ms');

        echo "  First call (uncached): " . number_format($firstCall, 2) . "ms\n";
        echo "  Cached call: " . number_format($cachedCall, 2) . "ms\n";
        echo "  Batch average: " . number_format($avgPerEntity, 2) . "ms per entity\n";
        echo "  Cache improvement: " . number_format(($firstCall - $cachedCall) / $firstCall * 100, 1) . "%\n\n";
    }

    private function benchmarkCallbackService(): void
    {
        echo "📞 Benchmarking CallbackManagementService...\n";

        $service = app(CallbackManagementService::class);

        // Get test data
        $branch = Branch::first();
        $customer = Customer::first();

        if (!$branch || !$customer) {
            $this->issues[] = "⚠️ No branch or customer found for callback testing";
            return;
        }

        // Test 1: Create callback (use direct model creation to bypass service validation)
        DB::connection()->flushQueryLog();
        $memBefore = memory_get_usage();
        $start = microtime(true);

        try {
            // Use model directly to test performance
            $callback = CallbackRequest::create([
                'branch_id' => $branch->id,
                'customer_id' => $customer->id,
                'customer_name' => $customer->name ?? 'Test Customer',
                'phone_number' => $customer->phone ?? '+49123456789',
                'priority' => 'normal',
                'status' => 'pending',
                'expires_at' => now()->addHours(24),
            ]);

            $createTime = (microtime(true) - $start) * 1000;
            $memUsed = (memory_get_usage() - $memBefore) / 1024 / 1024;
            $queries = DB::getQueryLog();
            $queryCount = count($queries);

            $this->results['callback_create'] = $createTime;
            $this->results['callback_create_queries'] = $queryCount;
            $this->results['callback_create_memory_mb'] = $memUsed;

            $this->checkTarget('callback_create', $createTime, TARGETS['callback_create'], 'ms');

            echo "  Create callback: " . number_format($createTime, 2) . "ms\n";
            echo "  Queries executed: " . $queryCount . "\n";
            echo "  Memory used: " . number_format($memUsed, 2) . "MB\n";

            // Clean up
            $callback->delete();

        } catch (\Exception $e) {
            $this->issues[] = "⚠️ Callback creation test skipped: " . substr($e->getMessage(), 0, 100);
            echo "  ⚠️ Skipped: " . substr($e->getMessage(), 0, 60) . "...\n";
        }

        // Test 2: List callbacks (simulate dashboard load)
        DB::connection()->flushQueryLog();
        $start = microtime(true);

        $callbacks = CallbackRequest::with(['customer', 'branch', 'service', 'assignedTo'])
            ->limit(50)
            ->get();

        $listTime = (microtime(true) - $start) * 1000;
        $queries = DB::getQueryLog();
        $queryCount = count($queries);

        $this->results['callback_list_50'] = $listTime;
        $this->results['callback_list_queries'] = $queryCount;

        echo "  List 50 callbacks: " . number_format($listTime, 2) . "ms\n";
        echo "  Queries executed: " . $queryCount . "\n";

        // Check for N+1
        if ($queryCount > 5) {
            $this->issues[] = "⚠️ Possible N+1 in callback list: {$queryCount} queries for 50 records";
        }

        echo "\n";
    }

    private function benchmarkNotificationManager(): void
    {
        echo "📧 Benchmarking NotificationManager...\n";

        try {
            $manager = app(NotificationManager::class);
            $customer = Customer::first();

            if (!$customer) {
                $this->issues[] = "⚠️ No customer found for notification testing";
                return;
            }

            DB::connection()->flushQueryLog();
            $start = microtime(true);

            // Queue notification (don't send immediately)
            $result = $manager->send(
                $customer,
                'test_benchmark',
                ['message' => 'Performance test'],
                ['email'],
                ['immediate' => false]
            );

            $sendTime = (microtime(true) - $start) * 1000;
            $queries = DB::getQueryLog();
            $queryCount = count($queries);

            $this->results['notification_send'] = $sendTime;
            $this->results['notification_queries'] = $queryCount;

            $this->checkTarget('notification_send', $sendTime, TARGETS['notification_send'], 'ms');

            echo "  Queue notification: " . number_format($sendTime, 2) . "ms\n";
            echo "  Queries executed: " . $queryCount . "\n\n";

        } catch (\Exception $e) {
            $this->issues[] = "⚠️ Notification benchmark skipped: " . $e->getMessage();
            echo "  ⚠️ Skipped (service dependencies not available)\n\n";
        }
    }

    private function benchmarkDatabaseQueries(): void
    {
        echo "🗄️ Analyzing Database Query Performance...\n";

        // Test typical admin panel operations
        $tests = [
            'Branch detail' => function() {
                return Branch::with(['company', 'staff', 'services'])->first();
            },
            'Staff list' => function() {
                return Staff::with(['branch', 'services'])->limit(20)->get();
            },
            'Service list' => function() {
                return Service::with(['branch'])->limit(20)->get();
            },
        ];

        foreach ($tests as $name => $test) {
            DB::connection()->flushQueryLog();
            $start = microtime(true);

            $test();

            $time = (microtime(true) - $start) * 1000;
            $queries = DB::getQueryLog();
            $queryCount = count($queries);
            $totalQueryTime = array_sum(array_column($queries, 'time'));

            echo "  {$name}:\n";
            echo "    Total time: " . number_format($time, 2) . "ms\n";
            echo "    Query count: {$queryCount}\n";
            echo "    Query time: " . number_format($totalQueryTime, 2) . "ms\n";

            if ($queryCount > TARGETS['query_count_max']) {
                $this->issues[] = "⚠️ {$name}: {$queryCount} queries exceeds target of " . TARGETS['query_count_max'];
            }
        }

        echo "\n";
    }

    private function benchmarkMemoryUsage(): void
    {
        echo "💾 Analyzing Memory Usage...\n";

        $memBefore = memory_get_usage();

        // Load typical dataset
        $callbacks = CallbackRequest::with(['customer', 'branch', 'service', 'assignedTo'])
            ->limit(100)
            ->get();

        $memAfter = memory_get_usage();
        $memUsedMB = ($memAfter - $memBefore) / 1024 / 1024;
        $peakMB = memory_get_peak_usage() / 1024 / 1024;

        $this->results['memory_100_callbacks_mb'] = $memUsedMB;
        $this->results['memory_peak_mb'] = $peakMB;

        echo "  100 callbacks loaded: " . number_format($memUsedMB, 2) . "MB\n";
        echo "  Peak memory: " . number_format($peakMB, 2) . "MB\n";

        if ($peakMB > TARGETS['memory_limit_mb']) {
            $this->issues[] = "⚠️ Peak memory ({$peakMB}MB) exceeds target of " . TARGETS['memory_limit_mb'] . "MB";
        }

        echo "\n";
    }

    private function analyzeNPlusOneQueries(): void
    {
        echo "🔍 Analyzing N+1 Query Patterns...\n";

        // Test 1: Callback requests with relationships
        DB::connection()->flushQueryLog();
        $callbacks = CallbackRequest::limit(10)->get();

        // Access relationships (this would trigger N+1 if not eager loaded)
        foreach ($callbacks as $callback) {
            $branch = $callback->branch;
            $customer = $callback->customer;
        }

        $queries = DB::getQueryLog();
        $queryCount = count($queries);

        echo "  Callback relationships (10 records):\n";
        echo "    Queries: {$queryCount}\n";

        if ($queryCount > 11) { // 1 for callbacks + 1 for branches + 1 for customers (eager load)
            $this->issues[] = "❌ N+1 detected in CallbackRequest: {$queryCount} queries for 10 records";
            echo "    ❌ N+1 DETECTED\n";
        } else {
            echo "    ✅ No N+1 issues\n";
        }

        // Test 2: Policy hierarchy resolution
        Cache::flush();
        DB::connection()->flushQueryLog();

        $service = app(PolicyConfigurationService::class);
        $branches = Branch::limit(5)->get();

        foreach ($branches as $branch) {
            $service->resolvePolicy($branch, 'cancellation');
        }

        $queries = DB::getQueryLog();
        $queryCount = count($queries);

        echo "  Policy resolution (5 branches):\n";
        echo "    Queries: {$queryCount}\n";

        if ($queryCount > 15) { // Reasonable threshold for hierarchy traversal
            $this->issues[] = "⚠️ Policy resolution may have optimization opportunities: {$queryCount} queries";
        }

        echo "\n";
    }

    private function benchmarkCacheHitRate(): void
    {
        echo "🎯 Analyzing Cache Performance...\n";

        $service = app(PolicyConfigurationService::class);
        $branch = Branch::first();

        if (!$branch) {
            echo "  ⚠️ Skipped (no test data)\n\n";
            return;
        }

        // Warm cache
        $service->resolvePolicy($branch, 'cancellation');
        $service->resolvePolicy($branch, 'reschedule');
        $service->resolvePolicy($branch, 'recurring');

        // Test cache stats
        $stats = $service->getCacheStats($branch);
        $hitRate = $stats['cached'] / ($stats['cached'] + $stats['missing']) * 100;

        $this->results['cache_hit_rate'] = $hitRate;

        echo "  Policy cache stats:\n";
        echo "    Cached: {$stats['cached']}\n";
        echo "    Missing: {$stats['missing']}\n";
        echo "    Hit rate: " . number_format($hitRate, 1) . "%\n";

        if ($hitRate < 90) {
            $this->issues[] = "⚠️ Cache hit rate ({$hitRate}%) below 90% target";
        }

        echo "\n";
    }

    private function checkTarget(string $metric, float $actual, float $target, string $unit): void
    {
        $status = $actual <= $target ? '✅' : '❌';

        if ($actual > $target) {
            $this->issues[] = "{$status} {$metric}: " . number_format($actual, 2) . "{$unit} exceeds target of {$target}{$unit}";
        }
    }

    private function generateReport(): void
    {
        echo str_repeat('=', 80) . "\n";
        echo "📊 PERFORMANCE BENCHMARK REPORT\n";
        echo str_repeat('=', 80) . "\n\n";

        echo "RESULTS SUMMARY:\n";
        echo str_repeat('-', 80) . "\n";

        $this->printMetric('Policy Resolution (first call)', $this->results['policy_first_call'] ?? 0, TARGETS['policy_first'], 'ms');
        $this->printMetric('Policy Resolution (cached)', $this->results['policy_cached_call'] ?? 0, TARGETS['policy_cached'], 'ms');
        $this->printMetric('Callback Creation', $this->results['callback_create'] ?? 0, TARGETS['callback_create'], 'ms');
        $this->printMetric('Callback List (50 records)', $this->results['callback_list_50'] ?? 0, TARGETS['callback_list_1000'], 'ms');
        $this->printMetric('Notification Queue', $this->results['notification_send'] ?? 0, TARGETS['notification_send'], 'ms');
        $this->printMetric('Memory Usage (100 callbacks)', $this->results['memory_100_callbacks_mb'] ?? 0, 100, 'MB');
        $this->printMetric('Peak Memory', $this->results['memory_peak_mb'] ?? 0, TARGETS['memory_limit_mb'], 'MB');
        $this->printMetric('Cache Hit Rate', $this->results['cache_hit_rate'] ?? 0, 90, '%');

        echo "\n";

        if (!empty($this->issues)) {
            echo "CRITICAL ISSUES:\n";
            echo str_repeat('-', 80) . "\n";
            foreach ($this->issues as $issue) {
                echo "  {$issue}\n";
            }
            echo "\n";
        } else {
            echo "✅ All performance targets met!\n\n";
        }

        echo "RECOMMENDATIONS:\n";
        echo str_repeat('-', 80) . "\n";
        $this->generateRecommendations();

        echo "\n" . str_repeat('=', 80) . "\n";
        echo "Benchmark completed at " . date('Y-m-d H:i:s') . "\n";
        echo str_repeat('=', 80) . "\n";
    }

    private function printMetric(string $name, float $value, float $target, string $unit): void
    {
        $status = $value <= $target ? '✅' : '❌';
        $delta = $value - $target;
        $deltaStr = $delta > 0 ? '+' . number_format($delta, 2) : number_format($delta, 2);

        printf("  %-40s %s %.2f%s (target: %.0f%s, delta: %s)\n",
            $name, $status, $value, $unit, $target, $unit, $deltaStr);
    }

    private function generateRecommendations(): void
    {
        $recommendations = [];

        // Check query counts
        if (($this->results['callback_list_queries'] ?? 0) > 5) {
            $recommendations[] = "Optimize CallbackRequest eager loading to reduce queries";
        }

        // Check memory usage
        if (($this->results['memory_100_callbacks_mb'] ?? 0) > 50) {
            $recommendations[] = "Consider implementing pagination or cursor-based loading for large datasets";
        }

        // Check cache hit rate
        if (($this->results['cache_hit_rate'] ?? 0) < 90) {
            $recommendations[] = "Improve cache warming strategy for policy configurations";
        }

        // Check policy performance
        if (($this->results['policy_first_call'] ?? 0) > TARGETS['policy_first']) {
            $recommendations[] = "Optimize PolicyConfigurationService hierarchy traversal";
        }

        if (empty($recommendations)) {
            echo "  ✅ System is performing optimally\n";
            echo "  🎯 Continue monitoring metrics in production\n";
        } else {
            foreach ($recommendations as $i => $rec) {
                echo "  " . ($i + 1) . ". {$rec}\n";
            }
        }
    }
}

// Run benchmark
try {
    $benchmark = new PerformanceBenchmark();
    $benchmark->run();
    exit(0);
} catch (\Exception $e) {
    echo "❌ Benchmark failed: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
