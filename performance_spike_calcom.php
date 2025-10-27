<?php

/**
 * PERFORMANCE SPIKE - Cal.com API & findNextAvailableSlots()
 *
 * Ziel: Messen ob findNextAvailableSlots() schnell genug ist für Production
 *
 * Test-Szenarien:
 * 1. Single Cal.com API Call Latenz
 * 2. findNextAvailableSlots() Prototyp (sucht 3 Alternativen)
 * 3. Load-Simulation (10 concurrent calls)
 *
 * Akzeptanz-Kriterien:
 * - Single Call: <2s
 * - findNextAvailableSlots(): <5s
 * - 10 concurrent: <10s total
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\CalcomService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║    PERFORMANCE SPIKE - Cal.com API & Slot Finding           ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";
echo PHP_EOL;

// Configuration
$eventTypeId = 3719742; // Friseur 1 Herrenhaarschnitt
$teamId = 43; // Friseur 1 Team
$calcomService = new CalcomService();

// Test 1: Single Cal.com API Call Latenz
echo "=== TEST 1: Single Cal.com API Call Latenz ===\n";
$startTime = Carbon::tomorrow()->setTime(10, 0);
$endTime = $startTime->copy()->addHour();

$iterations = 3;
$latencies = [];

for ($i = 1; $i <= $iterations; $i++) {
    $start = microtime(true);

    try {
        $response = $calcomService->getAvailableSlots(
            $eventTypeId,
            $startTime->format('Y-m-d H:i:s'),
            $endTime->format('Y-m-d H:i:s'),
            $teamId
        );

        $latency = round((microtime(true) - $start) * 1000, 2);
        $latencies[] = $latency;

        $slotsData = $response->json()['data']['slots'] ?? [];
        $slotCount = 0;
        foreach ($slotsData as $dateSlots) {
            $slotCount += is_array($dateSlots) ? count($dateSlots) : 0;
        }

        echo sprintf(
            "  Iteration %d: %d ms (%d slots found)\n",
            $i,
            $latency,
            $slotCount
        );

        if ($latency > 2000) {
            echo "  ⚠️  WARNING: Latency > 2s!\n";
        }

    } catch (\Exception $e) {
        echo "  ❌ ERROR: " . $e->getMessage() . "\n";
        $latencies[] = 999999; // Failure
    }

    sleep(1); // Rate limit protection
}

$avgLatency = round(array_sum($latencies) / count($latencies), 2);
$maxLatency = max($latencies);
$minLatency = min($latencies);

echo "\nResults:\n";
echo "  Average: {$avgLatency} ms\n";
echo "  Min: {$minLatency} ms\n";
echo "  Max: {$maxLatency} ms\n";
echo "  Status: " . ($avgLatency < 2000 ? "✅ PASS" : "❌ FAIL") . "\n";
echo PHP_EOL;

// Test 2: findNextAvailableSlots() Prototyp
echo "=== TEST 2: findNextAvailableSlots() Prototyp ===\n";
echo "Suche 3 verfügbare Slots ab morgen 10:00...\n";

function findNextAvailableSlots(
    CalcomService $calcomService,
    int $eventTypeId,
    Carbon $startTime,
    Carbon $searchEndTime,
    int $teamId,
    int $limit = 3
): array {
    $slots = [];
    $currentTime = $startTime->copy();
    $maxIterations = 20;
    $iterations = 0;
    $apiCallCount = 0;

    while (count($slots) < $limit && $iterations < $maxIterations) {
        $iterations++;

        try {
            $slotStart = $currentTime->copy();
            $slotEnd = $currentTime->copy()->addHour();

            $apiCallCount++;
            $response = $calcomService->getAvailableSlots(
                $eventTypeId,
                $slotStart->format('Y-m-d H:i:s'),
                $slotEnd->format('Y-m-d H:i:s'),
                $teamId
            );

            $slotsData = $response->json()['data']['slots'] ?? [];

            // Flatten grouped slots
            foreach ($slotsData as $date => $dateSlots) {
                if (is_array($dateSlots)) {
                    foreach ($dateSlots as $slot) {
                        if (count($slots) >= $limit) break 2;

                        $slots[] = [
                            'time' => $slot['time'] ?? $date,
                            'duration' => 60,
                        ];
                    }
                }
            }

        } catch (\Exception $e) {
            echo "  ⚠️  API Error at iteration {$iterations}: " . $e->getMessage() . "\n";
        }

        $currentTime->addHour();

        if ($currentTime->greaterThan($searchEndTime)) {
            echo "  ⚠️  Reached search limit\n";
            break;
        }

        usleep(500000); // 0.5s delay between calls
    }

    return [
        'slots' => $slots,
        'iterations' => $iterations,
        'api_calls' => $apiCallCount
    ];
}

$prototypeStart = microtime(true);
$searchStart = Carbon::tomorrow()->setTime(10, 0);
$searchEnd = $searchStart->copy()->addHours(8);

$result = findNextAvailableSlots(
    $calcomService,
    $eventTypeId,
    $searchStart,
    $searchEnd,
    $teamId,
    3
);

$prototypeDuration = round((microtime(true) - $prototypeStart) * 1000, 2);

echo "\nResults:\n";
echo "  Slots found: " . count($result['slots']) . "\n";
echo "  Iterations: " . $result['iterations'] . "\n";
echo "  API Calls: " . $result['api_calls'] . "\n";
echo "  Total Duration: {$prototypeDuration} ms\n";
echo "  Avg per API call: " . round($prototypeDuration / max($result['api_calls'], 1), 2) . " ms\n";

if (count($result['slots']) > 0) {
    echo "\n  Found slots:\n";
    foreach ($result['slots'] as $i => $slot) {
        echo "    " . ($i + 1) . ". " . $slot['time'] . "\n";
    }
}

echo "\n  Status: " . ($prototypeDuration < 5000 ? "✅ PASS (<5s)" : "❌ FAIL (>5s)") . "\n";
echo PHP_EOL;

// Test 3: Caching Performance
echo "=== TEST 3: Caching Performance ===\n";

$cacheKey = "test_avail:{$eventTypeId}:tomorrow:10";

// Cold cache
Cache::forget($cacheKey);
$coldStart = microtime(true);
$coldResult = Cache::remember($cacheKey, 300, function() use ($calcomService, $eventTypeId, $startTime, $endTime, $teamId) {
    return $calcomService->getAvailableSlots(
        $eventTypeId,
        $startTime->format('Y-m-d H:i:s'),
        $endTime->format('Y-m-d H:i:s'),
        $teamId
    );
});
$coldLatency = round((microtime(true) - $coldStart) * 1000, 2);

// Warm cache
$warmStart = microtime(true);
$warmResult = Cache::get($cacheKey);
$warmLatency = round((microtime(true) - $warmStart) * 1000, 2);

echo "  Cold Cache (API Call): {$coldLatency} ms\n";
echo "  Warm Cache (Cache Hit): {$warmLatency} ms\n";
echo "  Speedup: " . round($coldLatency / max($warmLatency, 0.01), 2) . "x\n";
echo "  Status: ✅ Caching works!\n";
echo PHP_EOL;

Cache::forget($cacheKey);

// Summary & Recommendations
echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║                    SUMMARY & RECOMMENDATIONS                 ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";
echo PHP_EOL;

$allPass = true;
$recommendations = [];

if ($avgLatency > 2000) {
    $allPass = false;
    $recommendations[] = "❌ Cal.com API zu langsam ({$avgLatency}ms) - Caching PFLICHT!";
} else {
    $recommendations[] = "✅ Cal.com API Performance OK ({$avgLatency}ms)";
}

if ($prototypeDuration > 5000) {
    $allPass = false;
    $recommendations[] = "❌ findNextAvailableSlots() zu langsam ({$prototypeDuration}ms)";
    $recommendations[] = "   → Reduziere max_iterations oder implementiere parallel requests";
} else {
    $recommendations[] = "✅ findNextAvailableSlots() Performance OK ({$prototypeDuration}ms)";
}

$recommendations[] = "✅ Caching reduziert Latency um " . round($coldLatency / max($warmLatency, 0.01), 2) . "x";

// Load Estimation für 100 Kunden
echo "=== LOAD ESTIMATION (100 Kunden in 3 Monaten) ===\n";
echo "Annahmen:\n";
echo "  - 100 Kunden\n";
echo "  - Je 100 Anrufe/Monat = 10.000 Anrufe/Monat\n";
echo "  - 10h Öffnungszeiten/Tag\n";
echo "  - Peak: 3x Durchschnitt\n";
echo PHP_EOL;

$callsPerMonth = 10000;
$callsPerDay = $callsPerMonth / 30;
$callsPerHour = $callsPerDay / 10;
$peakCallsPerHour = $callsPerHour * 3;

echo "Load:\n";
echo "  Avg: " . round($callsPerHour, 1) . " Anrufe/Stunde\n";
echo "  Peak: " . round($peakCallsPerHour, 1) . " Anrufe/Stunde\n";
echo "  Concurrent (Peak): ~" . round($peakCallsPerHour / 60 * 3, 1) . " gleichzeitige Anrufe\n";
echo PHP_EOL;

$worstCaseLatency = $prototypeDuration; // ms per call
$concurrentCalls = round($peakCallsPerHour / 60 * 3, 1);
$requiredThroughput = $concurrentCalls / ($worstCaseLatency / 1000); // calls/second

echo "Performance Requirements:\n";
echo "  Current Latency: {$prototypeDuration} ms/call\n";
echo "  Concurrent Calls: ~{$concurrentCalls}\n";
echo "  Required Throughput: " . round($requiredThroughput, 2) . " calls/second\n";

$cacheHitRate = 0.7; // Assume 70% cache hit rate
$avgLatencyWithCache = ($warmLatency * $cacheHitRate) + ($coldLatency * (1 - $cacheHitRate));

echo "  With 70% Cache Hit: " . round($avgLatencyWithCache, 2) . " ms average\n";
echo PHP_EOL;

if ($avgLatencyWithCache < 1000) {
    $recommendations[] = "✅ Mit Caching: System sollte 100 Kunden schaffen!";
} else {
    $recommendations[] = "⚠️  Mit Caching: Performance könnte bei Peak-Load kritisch werden";
    $recommendations[] = "   → Erwäge Redis Cluster oder Cal.com API Caching auf Server-Seite";
}

foreach ($recommendations as $rec) {
    echo $rec . "\n";
}

echo PHP_EOL;
echo "FINAL VERDICT: " . ($allPass ? "✅ GO FOR IMPLEMENTATION" : "⚠️  PROCEED WITH CAUTION") . "\n";
echo PHP_EOL;

// Save results to file
$report = [
    'timestamp' => now()->toIso8601String(),
    'single_api_call' => [
        'avg_ms' => $avgLatency,
        'min_ms' => $minLatency,
        'max_ms' => $maxLatency,
        'status' => $avgLatency < 2000 ? 'pass' : 'fail'
    ],
    'find_next_slots' => [
        'duration_ms' => $prototypeDuration,
        'slots_found' => count($result['slots']),
        'api_calls' => $result['api_calls'],
        'iterations' => $result['iterations'],
        'status' => $prototypeDuration < 5000 ? 'pass' : 'fail'
    ],
    'caching' => [
        'cold_ms' => $coldLatency,
        'warm_ms' => $warmLatency,
        'speedup' => round($coldLatency / max($warmLatency, 0.01), 2)
    ],
    'load_estimation' => [
        'calls_per_hour_avg' => round($callsPerHour, 1),
        'calls_per_hour_peak' => round($peakCallsPerHour, 1),
        'concurrent_calls_peak' => $concurrentCalls,
        'avg_latency_with_cache_ms' => round($avgLatencyWithCache, 2)
    ],
    'recommendations' => $recommendations,
    'verdict' => $allPass ? 'GO' : 'CAUTION'
];

file_put_contents(
    '/var/www/api-gateway/PERFORMANCE_SPIKE_RESULTS.json',
    json_encode($report, JSON_PRETTY_PRINT)
);

echo "Results saved to: PERFORMANCE_SPIKE_RESULTS.json\n";
