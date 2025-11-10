<?php

/**
 * Retell Function Performance Benchmark Script
 *
 * 🔧 PERFORMANCE FIX 2025-11-06: Benchmark script for measuring Phase 1 improvements
 *
 * Tests the 3 critical Retell functions:
 * - check_availability (baseline: 3.0s, target: 1.5s)
 * - get_alternatives (baseline: 1.7s, target: 1.2s)
 * - find_next_available (baseline: 500 ERROR, target: 900ms)
 *
 * Usage:
 *   php scripts/benchmark_retell_performance.php
 *
 * Output: Performance metrics with P50, P95, P99 percentiles
 */

require __DIR__ . '/../vendor/autoload.php';

use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

class RetellPerformanceBenchmark
{
    private string $baseUrl;
    private array $results = [];
    private int $warmupRuns = 2;
    private int $benchmarkRuns = 10;

    public function __construct()
    {
        $this->baseUrl = env('APP_URL', 'http://localhost');
    }

    public function run(): void
    {
        echo "\n";
        echo "╔═══════════════════════════════════════════════════════════╗\n";
        echo "║   Retell Function Performance Benchmark (Phase 1)        ║\n";
        echo "║   Testing Critical Bottlenecks: 3 Functions              ║\n";
        echo "╚═══════════════════════════════════════════════════════════╝\n";
        echo "\n";

        echo "Configuration:\n";
        echo "  Base URL: {$this->baseUrl}\n";
        echo "  Warmup runs: {$this->warmupRuns}\n";
        echo "  Benchmark runs: {$this->benchmarkRuns}\n";
        echo "\n";

        // Test 1: check_availability (Critical - 3.0s baseline)
        $this->benchmarkFunction(
            'check_availability',
            [
                'service_name' => 'Herrenhaarschnitt',
                'date' => Carbon::tomorrow()->format('Y-m-d'),
                'time' => '14:00',
            ],
            'CRITICAL: 3.0s → 1.5s target'
        );

        // Test 2: get_alternatives (Major - 1.7s baseline)
        $this->benchmarkFunction(
            'get_alternatives',
            [],
            'MAJOR: 1.7s → 1.2s target'
        );

        // Test 3: find_next_available (Critical - 500 ERROR → 900ms)
        $this->benchmarkFunction(
            'find_next_available',
            [],
            'CRITICAL: 500 ERROR → 900ms target'
        );

        // Print summary
        $this->printSummary();
    }

    private function benchmarkFunction(string $functionName, array $args, string $description): void
    {
        echo "─────────────────────────────────────────────────────────────\n";
        echo "Testing: {$functionName}\n";
        echo "Goal: {$description}\n";
        echo "─────────────────────────────────────────────────────────────\n";

        $durations = [];
        $errors = [];

        // Warmup runs (not counted in results)
        echo "Warming up ({$this->warmupRuns} runs)... ";
        for ($i = 0; $i < $this->warmupRuns; $i++) {
            try {
                $this->callFunction($functionName, $args);
                echo ".";
            } catch (\Exception $e) {
                echo "E";
            }
        }
        echo " Done\n";

        // Actual benchmark runs
        echo "Benchmarking ({$this->benchmarkRuns} runs):\n";

        for ($i = 0; $i < $this->benchmarkRuns; $i++) {
            $start = microtime(true);
            $success = false;
            $error = null;

            try {
                $response = $this->callFunction($functionName, $args);
                $duration = (microtime(true) - $start) * 1000; // Convert to ms
                $durations[] = $duration;
                $success = true;

                echo sprintf("  Run %2d: %7.2fms ✓\n", $i + 1, $duration);

            } catch (\Exception $e) {
                $duration = (microtime(true) - $start) * 1000;
                $error = $e->getMessage();
                $errors[] = [
                    'run' => $i + 1,
                    'duration' => $duration,
                    'error' => $error
                ];

                echo sprintf("  Run %2d: %7.2fms ✗ (ERROR: %s)\n", $i + 1, $duration, substr($error, 0, 50));
            }

            // Small delay between requests to avoid rate limiting
            usleep(100000); // 100ms
        }

        // Calculate statistics
        if (!empty($durations)) {
            $this->results[$functionName] = [
                'durations' => $durations,
                'errors' => $errors,
                'min' => min($durations),
                'max' => max($durations),
                'avg' => array_sum($durations) / count($durations),
                'p50' => $this->percentile($durations, 50),
                'p95' => $this->percentile($durations, 95),
                'p99' => $this->percentile($durations, 99),
                'success_rate' => (count($durations) / $this->benchmarkRuns) * 100,
                'description' => $description
            ];

            echo "\nResults:\n";
            echo sprintf("  Min:     %7.2fms\n", $this->results[$functionName]['min']);
            echo sprintf("  Avg:     %7.2fms\n", $this->results[$functionName]['avg']);
            echo sprintf("  Max:     %7.2fms\n", $this->results[$functionName]['max']);
            echo sprintf("  P50:     %7.2fms\n", $this->results[$functionName]['p50']);
            echo sprintf("  P95:     %7.2fms\n", $this->results[$functionName]['p95']);
            echo sprintf("  P99:     %7.2fms\n", $this->results[$functionName]['p99']);
            echo sprintf("  Success: %6.1f%%\n", $this->results[$functionName]['success_rate']);
        } else {
            $this->results[$functionName] = [
                'errors' => $errors,
                'success_rate' => 0,
                'description' => $description
            ];
            echo "\n❌ All runs failed! Check error log above.\n";
        }

        echo "\n";
    }

    private function callFunction(string $functionName, array $args): array
    {
        $response = Http::timeout(15)
            ->post("{$this->baseUrl}/api/retell/function", [
                'name' => $functionName,
                'call' => ['call_id' => 'benchmark_' . uniqid()],
                'args' => $args
            ]);

        if ($response->failed()) {
            throw new \RuntimeException("HTTP {$response->status()}: " . $response->body());
        }

        return $response->json();
    }

    private function percentile(array $values, float $percentile): float
    {
        sort($values);
        $index = ceil(($percentile / 100) * count($values)) - 1;
        return $values[max(0, $index)];
    }

    private function printSummary(): void
    {
        echo "╔═══════════════════════════════════════════════════════════╗\n";
        echo "║                  BENCHMARK SUMMARY                        ║\n";
        echo "╚═══════════════════════════════════════════════════════════╝\n";
        echo "\n";

        echo sprintf("%-25s %8s %8s %8s %10s\n", "Function", "P50", "P95", "Avg", "Success");
        echo str_repeat("─", 70) . "\n";

        foreach ($this->results as $function => $stats) {
            if (isset($stats['p50'])) {
                echo sprintf(
                    "%-25s %7.0fms %7.0fms %7.0fms %9.1f%%\n",
                    $function,
                    $stats['p50'],
                    $stats['p95'],
                    $stats['avg'],
                    $stats['success_rate']
                );
            } else {
                echo sprintf(
                    "%-25s %7s %7s %7s %9.1f%%\n",
                    $function,
                    "FAIL",
                    "FAIL",
                    "FAIL",
                    $stats['success_rate']
                );
            }
        }

        echo "\n";
        echo "Target Goals (Phase 1):\n";
        foreach ($this->results as $function => $stats) {
            echo "  {$function}: {$stats['description']}\n";
        }

        echo "\n";
        echo "✅ Benchmark Complete!\n";
        echo "\n";
        echo "Next Steps:\n";
        echo "1. Compare these results with baseline (from test report)\n";
        echo "2. If targets not met, review implementation or run Phase 2\n";
        echo "3. Monitor production metrics for real-world validation\n";
        echo "\n";
    }
}

// Run benchmark
$benchmark = new RetellPerformanceBenchmark();
$benchmark->run();
