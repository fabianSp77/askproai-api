#!/usr/bin/env php
<?php

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

echo "=== STRESS TEST ===" . PHP_EOL;
echo "Date: " . date('Y-m-d H:i:s') . PHP_EOL;
echo PHP_EOL;

// 1. Database Stress Test
echo "1. DATABASE STRESS TEST" . PHP_EOL;

$iterations = 100;
$start = microtime(true);

for ($i = 0; $i < $iterations; $i++) {
    \DB::select('SELECT 1');
}

$dbTime = (microtime(true) - $start) * 1000;
$dbAvg = $dbTime / $iterations;

echo "   $iterations queries in " . round($dbTime, 2) . "ms" . PHP_EOL;
echo "   Average: " . round($dbAvg, 2) . "ms per query" . PHP_EOL;

// 2. Model Loading Stress
echo PHP_EOL . "2. MODEL LOADING STRESS TEST" . PHP_EOL;

$start = microtime(true);

for ($i = 0; $i < 50; $i++) {
    \App\Models\Customer::limit(10)->get();
}

$modelTime = (microtime(true) - $start) * 1000;
$modelAvg = $modelTime / 50;

echo "   50 model loads in " . round($modelTime, 2) . "ms" . PHP_EOL;
echo "   Average: " . round($modelAvg, 2) . "ms per load" . PHP_EOL;

// 3. Cache Stress Test
echo PHP_EOL . "3. CACHE STRESS TEST (Redis)" . PHP_EOL;

$cacheOps = 1000;
$start = microtime(true);

for ($i = 0; $i < $cacheOps; $i++) {
    $key = 'stress_test_' . $i;
    \Cache::put($key, 'test_value_' . $i, 60);
    \Cache::get($key);
    \Cache::forget($key);
}

$cacheTime = (microtime(true) - $start) * 1000;
$cacheAvg = $cacheTime / ($cacheOps * 3); // 3 operations per iteration

echo "   $cacheOps cache cycles (3000 ops) in " . round($cacheTime, 2) . "ms" . PHP_EOL;
echo "   Average: " . round($cacheAvg, 2) . "ms per operation" . PHP_EOL;

// 4. Concurrent Database Connections
echo PHP_EOL . "4. CONCURRENT CONNECTION TEST" . PHP_EOL;

$concurrent = 20;
$connections = [];
$start = microtime(true);

try {
    for ($i = 0; $i < $concurrent; $i++) {
        // Simulate concurrent connections by rapidly creating queries
        \DB::select('SELECT COUNT(*) FROM customers');
    }

    $concurrentTime = (microtime(true) - $start) * 1000;
    echo "   $concurrent concurrent connections: " . round($concurrentTime, 2) . "ms" . PHP_EOL;
    echo "   Average: " . round($concurrentTime / $concurrent, 2) . "ms per connection" . PHP_EOL;
} catch (Exception $e) {
    echo "   ❌ Concurrent connection test failed: " . $e->getMessage() . PHP_EOL;
}

// 5. Memory Stress Test
echo PHP_EOL . "5. MEMORY STRESS TEST" . PHP_EOL;

$startMem = memory_get_usage(true) / 1024 / 1024;

// Load large dataset
$allCustomers = \App\Models\Customer::all();
$allCalls = \App\Models\Call::all();
$allAppointments = \App\Models\Appointment::all();

$endMem = memory_get_usage(true) / 1024 / 1024;
$peakMem = memory_get_peak_usage(true) / 1024 / 1024;

echo "   Start memory: " . round($startMem, 2) . " MB" . PHP_EOL;
echo "   After loading all records: " . round($endMem, 2) . " MB" . PHP_EOL;
echo "   Peak memory: " . round($peakMem, 2) . " MB" . PHP_EOL;
echo "   Memory increase: " . round($endMem - $startMem, 2) . " MB" . PHP_EOL;

// 6. Complex Query Stress
echo PHP_EOL . "6. COMPLEX QUERY STRESS TEST" . PHP_EOL;

$start = microtime(true);

for ($i = 0; $i < 10; $i++) {
    \App\Models\Customer::with(['calls', 'appointments'])
        ->whereHas('calls', function($q) {
            $q->where('created_at', '>', now()->subDays(30));
        })
        ->limit(5)
        ->get();
}

$complexTime = (microtime(true) - $start) * 1000;

echo "   10 complex queries with relationships: " . round($complexTime, 2) . "ms" . PHP_EOL;
echo "   Average: " . round($complexTime / 10, 2) . "ms per query" . PHP_EOL;

// 7. Write Operation Stress
echo PHP_EOL . "7. WRITE OPERATION STRESS TEST" . PHP_EOL;

$writeOps = 50;
$start = microtime(true);
$createdIds = [];

try {
    for ($i = 0; $i < $writeOps; $i++) {
        $customer = \App\Models\Customer::create([
            'name' => 'Stress Test ' . uniqid(),
            'email' => 'stress' . uniqid() . '@test.com',
            'phone' => '+49' . rand(1000000000, 9999999999),
            'created_at' => now(),
            'updated_at' => now()
        ]);
        $createdIds[] = $customer->id;
    }

    $writeTime = (microtime(true) - $start) * 1000;

    echo "   $writeOps write operations in " . round($writeTime, 2) . "ms" . PHP_EOL;
    echo "   Average: " . round($writeTime / $writeOps, 2) . "ms per write" . PHP_EOL;

    // Cleanup
    \App\Models\Customer::whereIn('id', $createdIds)->delete();
    echo "   ✅ Test data cleaned up" . PHP_EOL;

} catch (Exception $e) {
    echo "   ❌ Write test failed: " . $e->getMessage() . PHP_EOL;
}

// Summary
echo PHP_EOL . "=== STRESS TEST SUMMARY ===" . PHP_EOL;

$metrics = [
    'Database queries' => $dbAvg,
    'Model loading' => $modelAvg,
    'Cache operations' => $cacheAvg,
    'Complex queries' => ($complexTime / 10),
    'Write operations' => ($writeTime ?? 0) / max(1, $writeOps)
];

$totalScore = 0;
$metricCount = 0;

foreach ($metrics as $name => $avgTime) {
    // Score based on response time (target < 10ms for excellent)
    if ($avgTime < 10) {
        $score = 100;
        $rating = "EXCELLENT";
    } elseif ($avgTime < 50) {
        $score = 80;
        $rating = "GOOD";
    } elseif ($avgTime < 100) {
        $score = 60;
        $rating = "ACCEPTABLE";
    } else {
        $score = 40;
        $rating = "NEEDS OPTIMIZATION";
    }

    echo "$name: " . round($avgTime, 2) . "ms - $rating ($score/100)" . PHP_EOL;
    $totalScore += $score;
    $metricCount++;
}

$overallScore = round($totalScore / $metricCount);
echo PHP_EOL . "OVERALL STRESS TEST SCORE: $overallScore/100" . PHP_EOL;

if ($overallScore >= 80) {
    echo "Status: ✅ EXCELLENT PERFORMANCE UNDER STRESS" . PHP_EOL;
} elseif ($overallScore >= 60) {
    echo "Status: ⚠️ GOOD PERFORMANCE WITH ROOM FOR IMPROVEMENT" . PHP_EOL;
} else {
    echo "Status: ❌ PERFORMANCE OPTIMIZATION NEEDED" . PHP_EOL;
}

echo PHP_EOL . "=== STRESS TEST COMPLETE ===" . PHP_EOL;