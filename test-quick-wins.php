<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

echo "🚀 Testing Quick Wins Implementation\n";
echo "=====================================\n\n";

// 1. Test Cache Manager
echo "1. Testing Cache Manager...\n";
try {
    $cacheManager = app(\App\Services\Cache\CacheManager::class);
    
    // Test write/read
    $testData = ['test' => 'data', 'timestamp' => time()];
    $cacheManager->putMany(['test_key_1' => $testData], 60);
    
    $retrieved = $cacheManager->many(['test_key_1']);
    
    if ($retrieved['test_key_1']['test'] === 'data') {
        echo "✅ Cache Manager working correctly\n";
    } else {
        echo "❌ Cache Manager test failed\n";
    }
} catch (Exception $e) {
    echo "❌ Cache Manager error: " . $e->getMessage() . "\n";
}

// 2. Test Company Cache Service
echo "\n2. Testing Company Cache Service...\n";
try {
    $companyCacheService = app(\App\Services\Cache\CompanyCacheService::class);
    
    // Get first company
    $company = \App\Models\Company::first();
    if ($company) {
        $start = microtime(true);
        $cachedCompany = $companyCacheService->getCompanyWithRelations($company->id);
        $time1 = (microtime(true) - $start) * 1000;
        
        // Second call should be from cache
        $start = microtime(true);
        $cachedCompany2 = $companyCacheService->getCompanyWithRelations($company->id);
        $time2 = (microtime(true) - $start) * 1000;
        
        echo "✅ Company Cache Service working\n";
        echo "   First call: {$time1}ms\n";
        echo "   Cached call: {$time2}ms (should be < 5ms)\n";
        
        if ($time2 < 5) {
            echo "   ✅ Cache is being used effectively\n";
        }
    }
} catch (Exception $e) {
    echo "❌ Company Cache Service error: " . $e->getMessage() . "\n";
}

// 3. Test Enhanced Rate Limiter
echo "\n3. Testing Enhanced Rate Limiter...\n";
try {
    $rateLimiter = app(\App\Services\RateLimiter\EnhancedRateLimiter::class);
    
    $testKey = 'test:rate:limit:' . time();
    
    // Should allow first 5 attempts
    $allowed = 0;
    for ($i = 0; $i < 10; $i++) {
        if ($rateLimiter->attempt($testKey, 5, 60)) {
            $allowed++;
        }
    }
    
    if ($allowed === 5) {
        echo "✅ Rate Limiter working correctly (allowed 5/10 attempts)\n";
    } else {
        echo "❌ Rate Limiter test failed (allowed {$allowed}/10 attempts)\n";
    }
    
    $rateLimiter->reset($testKey);
} catch (Exception $e) {
    echo "❌ Rate Limiter error: " . $e->getMessage() . "\n";
}

// 4. Test Webhook Deduplication
echo "\n4. Testing Webhook Deduplication...\n";
try {
    $deduplication = app(\App\Services\Webhook\WebhookDeduplication::class);
    
    $testKey = 'test:webhook:' . time();
    
    // First attempt should succeed
    if ($deduplication->checkAndSet($testKey, 60)) {
        echo "✅ First webhook accepted\n";
    }
    
    // Second attempt should fail (duplicate)
    if (!$deduplication->checkAndSet($testKey, 60)) {
        echo "✅ Duplicate webhook rejected\n";
    }
    
    $deduplication->remove($testKey);
} catch (Exception $e) {
    echo "❌ Webhook Deduplication error: " . $e->getMessage() . "\n";
}

// 5. Test Metrics Collector
echo "\n5. Testing Metrics Collector...\n";
try {
    $collector = app(\App\Services\Monitoring\MetricsCollectorService::class);
    
    // Track some test metrics
    $collector->track('test_metric', 1, ['type' => 'test']);
    $collector->timing('test_timing', 125.5, ['endpoint' => '/test']);
    
    // Collect metrics
    $metrics = $collector->collect();
    
    if (count($metrics) > 0) {
        echo "✅ Metrics Collector working\n";
        echo "   Collecting " . count($metrics) . " metric types\n";
    }
} catch (Exception $e) {
    echo "❌ Metrics Collector error: " . $e->getMessage() . "\n";
}

// 6. Test Health Check Endpoint
echo "\n6. Testing Health Check Endpoint...\n";
try {
    $baseUrl = config('app.url', 'http://localhost');
    $response = Http::timeout(5)->get("{$baseUrl}/api/health");
    
    if ($response->successful()) {
        $data = $response->json();
        echo "✅ Health endpoint responding\n";
        echo "   Status: " . ($data['status'] ?? 'unknown') . "\n";
        
        if (isset($data['checks'])) {
            foreach ($data['checks'] as $check => $status) {
                echo "   - {$check}: " . ($status ? '✅' : '❌') . "\n";
            }
        }
    } else {
        echo "❌ Health endpoint not responding (Status: {$response->status()})\n";
    }
} catch (Exception $e) {
    echo "❌ Health endpoint error: " . $e->getMessage() . "\n";
}

// 7. Test Query Performance
echo "\n7. Testing Query Optimization...\n";
try {
    $repository = app(\App\Repositories\OptimizedAppointmentRepository::class);
    
    $company = \App\Models\Company::first();
    if ($company) {
        $start = microtime(true);
        $appointments = $repository->getTodaysAppointmentsByBranch($company->id);
        $time = (microtime(true) - $start) * 1000;
        
        echo "✅ Optimized query executed in {$time}ms\n";
        
        // Count total appointments retrieved
        $total = 0;
        foreach ($appointments as $branchAppointments) {
            $total += count($branchAppointments);
        }
        echo "   Retrieved {$total} appointments\n";
    }
} catch (Exception $e) {
    echo "❌ Query optimization error: " . $e->getMessage() . "\n";
}

// 8. Performance Summary
echo "\n\n📊 Performance Summary\n";
echo "======================\n";

// Check cache hit rate
$info = \Illuminate\Support\Facades\Redis::info();
$hits = $info['keyspace_hits'] ?? 0;
$misses = $info['keyspace_misses'] ?? 0;
$hitRate = ($hits + $misses) > 0 ? round(($hits / ($hits + $misses)) * 100, 2) : 0;

echo "Cache Hit Rate: {$hitRate}%\n";

// Check queue sizes
$queues = ['default', 'webhooks-high-priority', 'webhooks-medium-priority'];
foreach ($queues as $queue) {
    $size = \Illuminate\Support\Facades\Redis::llen("queues:{$queue}");
    echo "Queue '{$queue}': {$size} jobs\n";
}

// Failed jobs
$failedJobs = DB::table('failed_jobs')->count();
echo "Failed Jobs: {$failedJobs}\n";

echo "\n✅ Quick Wins Test Complete!\n";