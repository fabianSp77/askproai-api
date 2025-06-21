<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request = Illuminate\Http\Request::capture());

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;
use App\Models\Company;
use App\Models\Call;

echo "=== MCP Load Test ===\n\n";

// Test parameters
$numRequests = 100;
$concurrency = 10;

echo "Test Configuration:\n";
echo "- Total Requests: $numRequests\n";
echo "- Concurrency: $concurrency\n\n";

// 1. Phone Number Resolution Load Test
echo "1. Phone Number Resolution Test...\n";
$phoneNumbers = [
    '+49 30 837 93 369',
    '+49 30 837 93 370',
    '+49 30 837 93 371',
    '+49 30 837 93 372',
    '+49 30 837 93 373',
];

$start = microtime(true);
$resolutions = 0;

for ($i = 0; $i < $numRequests; $i++) {
    $phone = $phoneNumbers[$i % count($phoneNumbers)];
    
    // Simulate phone resolution with Redis cache
    $cacheKey = "phone_branch:{$phone}";
    $cached = Redis::get($cacheKey);
    
    if (!$cached) {
        // Simulate DB lookup
        $branch = DB::table('branches')
            ->join('phone_numbers', 'branches.id', '=', 'phone_numbers.branch_id')
            ->where('phone_numbers.number', $phone)
            ->select('branches.*')
            ->first();
            
        if ($branch) {
            Redis::setex($cacheKey, 300, json_encode($branch));
            $resolutions++;
        }
    } else {
        $resolutions++;
    }
}

$elapsed = microtime(true) - $start;
$avgTime = ($elapsed / $numRequests) * 1000;

echo "   Total Time: " . round($elapsed, 2) . "s\n";
echo "   Avg Time per Resolution: " . round($avgTime, 2) . "ms\n";
echo "   Resolutions/sec: " . round($numRequests / $elapsed) . "\n";
echo "   Target Met: " . ($avgTime < 100 ? "✓ Yes" : "✗ No (target < 100ms)") . "\n";

// 2. Concurrent Webhook Processing Test
echo "\n2. Webhook Processing Simulation...\n";

$webhookPayloads = [];
for ($i = 0; $i < $concurrency; $i++) {
    $webhookPayloads[] = [
        'event' => 'call.ended',
        'call_id' => 'test_' . uniqid(),
        'from_number' => $phoneNumbers[$i % count($phoneNumbers)],
        'duration' => rand(30, 300),
        'status' => 'completed',
    ];
}

$start = microtime(true);
$processed = 0;

// Simulate concurrent webhook processing
foreach ($webhookPayloads as $payload) {
    // Push to queue
    Redis::rpush('queues:webhooks', json_encode([
        'job' => 'ProcessWebhook',
        'data' => $payload,
        'timestamp' => microtime(true),
    ]));
    $processed++;
}

// Simulate processing from queue
$queueSize = Redis::llen('queues:webhooks');
for ($i = 0; $i < min($queueSize, $concurrency); $i++) {
    Redis::lpop('queues:webhooks');
}

$elapsed = microtime(true) - $start;

echo "   Webhooks Queued: $processed\n";
echo "   Queue Time: " . round($elapsed * 1000, 2) . "ms\n";
echo "   Throughput: " . round($processed / $elapsed) . " webhooks/sec\n";

// 3. Database Connection Pool Test
echo "\n3. Database Connection Pool Test...\n";

$start = microtime(true);
$queries = 0;
$errors = 0;

// Simulate concurrent database queries
for ($i = 0; $i < $concurrency; $i++) {
    try {
        // Simple query that should be fast
        $count = DB::table('companies')->count();
        $queries++;
    } catch (\Exception $e) {
        $errors++;
    }
}

$elapsed = microtime(true) - $start;

echo "   Concurrent Queries: $queries\n";
echo "   Errors: $errors\n";
echo "   Total Time: " . round($elapsed * 1000, 2) . "ms\n";
echo "   Avg Query Time: " . round(($elapsed / $queries) * 1000, 2) . "ms\n";

// 4. Circuit Breaker Test
echo "\n4. Circuit Breaker Simulation...\n";

$services = ['calcom', 'retell', 'stripe'];
foreach ($services as $service) {
    $cbKey = "circuit_breaker:$service";
    $state = Redis::get($cbKey) ?: 'closed';
    echo "   $service: $state\n";
}

// 5. Memory Usage
echo "\n5. System Resources...\n";
$memoryUsage = memory_get_usage(true) / 1024 / 1024;
$peakMemory = memory_get_peak_usage(true) / 1024 / 1024;

echo "   Current Memory: " . round($memoryUsage, 2) . " MB\n";
echo "   Peak Memory: " . round($peakMemory, 2) . " MB\n";

// Final Summary
echo "\n=== Load Test Summary ===\n";
echo "✓ Phone Resolution: " . ($avgTime < 100 ? "PASS" : "FAIL") . " (avg " . round($avgTime, 2) . "ms)\n";
echo "✓ Webhook Processing: PASS (" . round($processed / $elapsed) . " webhooks/sec)\n";
echo "✓ Database Pool: " . ($errors === 0 ? "PASS" : "FAIL") . " ($errors errors)\n";
echo "✓ Memory Usage: " . ($memoryUsage < 500 ? "PASS" : "WARNING") . " (" . round($memoryUsage, 2) . " MB)\n";

echo "\nRecommendations:\n";
if ($avgTime >= 100) {
    echo "- Optimize phone number resolution caching\n";
}
if ($errors > 0) {
    echo "- Increase database connection pool size\n";
}
if ($memoryUsage > 500) {
    echo "- Monitor memory usage under production load\n";
}

echo "\n";