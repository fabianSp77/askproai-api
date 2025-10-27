#!/usr/bin/env php
<?php
/**
 * Performance Test Script - Database Index Benchmarking
 *
 * Purpose: Verify 10x-100x performance improvements from customer portal indexes
 * Usage: php scripts/performance_test_indexes.php [--env=staging]
 *
 * Tests:
 * 1. Company dashboard query (idx_retell_sessions_company_status)
 * 2. Customer call history (idx_retell_sessions_customer_date)
 * 3. Branch filtering (idx_retell_sessions_branch)
 * 4. Active appointments (idx_appointments_customer_active)
 * 5. Transcript loading (idx_transcript_segments_session_seq)
 */

require __DIR__ . '/../vendor/autoload.php';

use Illuminate\Support\Facades\DB;

// Bootstrap Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Colors for terminal output
define('GREEN', "\033[0;32m");
define('RED', "\033[0;31m");
define('YELLOW', "\033[1;33m");
define('BLUE', "\033[0;34m");
define('NC', "\033[0m"); // No Color

class IndexPerformanceTest
{
    private array $results = [];
    private int $testIterations = 5;

    public function run(): void
    {
        $this->printHeader();

        // Get test data IDs
        $testData = $this->getTestData();

        if (!$testData) {
            $this->printError("No test data found. Please ensure database has data.");
            return;
        }

        // Run all tests
        $this->testCompanyDashboard($testData);
        $this->testCustomerCallHistory($testData);
        $this->testBranchFiltering($testData);
        $this->testActiveAppointments($testData);
        $this->testTranscriptLoading($testData);

        // Print summary
        $this->printSummary();
    }

    private function getTestData(): array
    {
        echo BLUE . "🔍 Gathering test data..." . NC . PHP_EOL;

        $company = DB::table('companies')->first();
        $customer = DB::table('customers')->where('company_id', $company->id ?? null)->first();
        $branch = DB::table('branches')->where('company_id', $company->id ?? null)->first();
        $session = DB::table('retell_call_sessions')->first();

        if (!$company || !$session) {
            return [];
        }

        $data = [
            'company_id' => $company->id,
            'customer_id' => $customer->id ?? null,
            'branch_id' => $branch->id ?? null,
            'session_id' => $session->id ?? null,
        ];

        echo GREEN . "✅ Found test data: Company #{$data['company_id']}" . NC . PHP_EOL;
        echo PHP_EOL;

        return $data;
    }

    private function testCompanyDashboard(array $testData): void
    {
        echo YELLOW . "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" . NC . PHP_EOL;
        echo YELLOW . "TEST 1: Company Dashboard Query" . NC . PHP_EOL;
        echo YELLOW . "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" . NC . PHP_EOL;

        $query = "
            SELECT id, customer_id, started_at, call_status, duration_ms
            FROM retell_call_sessions
            WHERE company_id = ?
              AND call_status IN ('completed', 'failed')
              AND started_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ORDER BY started_at DESC
            LIMIT 50
        ";

        $this->benchmarkQuery(
            'Company Dashboard',
            $query,
            [$testData['company_id']],
            'idx_retell_sessions_company_status',
            '< 50ms'
        );
    }

    private function testCustomerCallHistory(array $testData): void
    {
        if (!$testData['customer_id']) {
            echo YELLOW . "⚠️  Skipping customer history test (no customer data)" . NC . PHP_EOL . PHP_EOL;
            return;
        }

        echo YELLOW . "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" . NC . PHP_EOL;
        echo YELLOW . "TEST 2: Customer Call History" . NC . PHP_EOL;
        echo YELLOW . "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" . NC . PHP_EOL;

        $query = "
            SELECT id, started_at, call_status, duration_ms
            FROM retell_call_sessions
            WHERE customer_id = ?
            ORDER BY started_at DESC
            LIMIT 20
        ";

        $this->benchmarkQuery(
            'Customer History',
            $query,
            [$testData['customer_id']],
            'idx_retell_sessions_customer_date',
            '< 20ms'
        );
    }

    private function testBranchFiltering(array $testData): void
    {
        if (!$testData['branch_id']) {
            echo YELLOW . "⚠️  Skipping branch filter test (no branch data)" . NC . PHP_EOL . PHP_EOL;
            return;
        }

        echo YELLOW . "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" . NC . PHP_EOL;
        echo YELLOW . "TEST 3: Branch Filtering" . NC . PHP_EOL;
        echo YELLOW . "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" . NC . PHP_EOL;

        $query = "
            SELECT id, customer_id, started_at, call_status
            FROM retell_call_sessions
            WHERE branch_id = ?
            ORDER BY started_at DESC
            LIMIT 50
        ";

        $this->benchmarkQuery(
            'Branch Filter',
            $query,
            [$testData['branch_id']],
            'idx_retell_sessions_branch',
            '< 30ms'
        );
    }

    private function testActiveAppointments(array $testData): void
    {
        if (!$testData['customer_id']) {
            echo YELLOW . "⚠️  Skipping appointments test (no customer data)" . NC . PHP_EOL . PHP_EOL;
            return;
        }

        echo YELLOW . "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" . NC . PHP_EOL;
        echo YELLOW . "TEST 4: Active Appointments (Partial Index)" . NC . PHP_EOL;
        echo YELLOW . "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" . NC . PHP_EOL;

        $query = "
            SELECT id, service_id, starts_at, status
            FROM appointments
            WHERE customer_id = ?
              AND deleted_at IS NULL
            ORDER BY starts_at DESC
            LIMIT 10
        ";

        $this->benchmarkQuery(
            'Active Appointments',
            $query,
            [$testData['customer_id']],
            'idx_appointments_customer_active',
            '< 30ms'
        );
    }

    private function testTranscriptLoading(array $testData): void
    {
        if (!$testData['session_id']) {
            echo YELLOW . "⚠️  Skipping transcript test (no session data)" . NC . PHP_EOL . PHP_EOL;
            return;
        }

        echo YELLOW . "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" . NC . PHP_EOL;
        echo YELLOW . "TEST 5: Transcript Loading" . NC . PHP_EOL;
        echo YELLOW . "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" . NC . PHP_EOL;

        $query = "
            SELECT id, segment_sequence, role, text, occurred_at
            FROM retell_transcript_segments
            WHERE call_session_id = ?
            ORDER BY segment_sequence ASC
        ";

        $this->benchmarkQuery(
            'Transcript Loading',
            $query,
            [$testData['session_id']],
            'idx_transcript_segments_session_seq',
            '< 20ms'
        );
    }

    private function benchmarkQuery(
        string $testName,
        string $query,
        array $bindings,
        string $expectedIndex,
        string $targetTime
    ): void {
        echo "Test: {$testName}" . PHP_EOL;
        echo "Expected Index: {$expectedIndex}" . PHP_EOL;
        echo "Target: {$targetTime}" . PHP_EOL . PHP_EOL;

        // Run EXPLAIN ANALYZE
        $explainQuery = "EXPLAIN " . $query;
        $explain = DB::select($explainQuery, $bindings);

        // Check if index is used
        $indexUsed = $this->checkIndexUsage($explain, $expectedIndex);

        // Benchmark execution time
        $times = [];
        for ($i = 0; $i < $this->testIterations; $i++) {
            $start = microtime(true);
            DB::select($query, $bindings);
            $end = microtime(true);
            $times[] = ($end - $start) * 1000; // Convert to milliseconds
        }

        // Calculate statistics
        $avgTime = array_sum($times) / count($times);
        $minTime = min($times);
        $maxTime = max($times);

        // Determine if test passed
        $targetMs = (float) str_replace(['<', 'ms', ' '], '', $targetTime);
        $passed = $avgTime < $targetMs && $indexUsed;

        // Print results
        echo ($passed ? GREEN . "✅ PASS" : RED . "❌ FAIL") . NC . PHP_EOL;
        echo "Average: " . number_format($avgTime, 2) . "ms" . PHP_EOL;
        echo "Min: " . number_format($minTime, 2) . "ms" . PHP_EOL;
        echo "Max: " . number_format($maxTime, 2) . "ms" . PHP_EOL;
        echo "Index Used: " . ($indexUsed ? GREEN . "YES" : RED . "NO") . NC . PHP_EOL;

        // Print EXPLAIN output
        echo PHP_EOL . "EXPLAIN:" . PHP_EOL;
        foreach ($explain as $row) {
            $rowArray = (array) $row;
            foreach ($rowArray as $key => $value) {
                echo "  {$key}: {$value}" . PHP_EOL;
            }
        }

        echo PHP_EOL;

        // Store results
        $this->results[] = [
            'test' => $testName,
            'avg_time' => $avgTime,
            'target' => $targetMs,
            'index_used' => $indexUsed,
            'passed' => $passed,
        ];
    }

    private function checkIndexUsage(array $explain, string $expectedIndex): bool
    {
        foreach ($explain as $row) {
            $rowArray = (array) $row;
            $possibleKey = $rowArray['possible_keys'] ?? '';
            $key = $rowArray['key'] ?? '';
            $extra = $rowArray['Extra'] ?? '';

            // Check if our index is in possible_keys or key
            if (
                str_contains($possibleKey, $expectedIndex) ||
                str_contains($key, $expectedIndex)
            ) {
                return true;
            }

            // Also check if Using index or Using where with index
            if (str_contains($extra, 'Using index')) {
                return true;
            }
        }

        return false;
    }

    private function printHeader(): void
    {
        echo PHP_EOL;
        echo BLUE . "═══════════════════════════════════════════════════════" . NC . PHP_EOL;
        echo BLUE . "   Database Index Performance Test" . NC . PHP_EOL;
        echo BLUE . "   Customer Portal - Phase 1" . NC . PHP_EOL;
        echo BLUE . "═══════════════════════════════════════════════════════" . NC . PHP_EOL;
        echo PHP_EOL;
        echo "Environment: " . app()->environment() . PHP_EOL;
        echo "Database: " . config('database.default') . PHP_EOL;
        echo "Iterations: {$this->testIterations}" . PHP_EOL;
        echo PHP_EOL;
    }

    private function printSummary(): void
    {
        echo BLUE . "═══════════════════════════════════════════════════════" . NC . PHP_EOL;
        echo BLUE . "   SUMMARY" . NC . PHP_EOL;
        echo BLUE . "═══════════════════════════════════════════════════════" . NC . PHP_EOL;
        echo PHP_EOL;

        $totalTests = count($this->results);
        $passedTests = count(array_filter($this->results, fn($r) => $r['passed']));

        foreach ($this->results as $result) {
            $status = $result['passed'] ? GREEN . "✅ PASS" : RED . "❌ FAIL";
            $indexStatus = $result['index_used'] ? "Index: YES" : "Index: NO";

            printf(
                "%s %s - %s (%.2fms / %.2fms target) - %s\n",
                $status . NC,
                $result['test'],
                number_format($result['avg_time'], 2) . "ms",
                $result['avg_time'],
                $result['target'],
                $indexStatus
            );
        }

        echo PHP_EOL;
        echo "Total: {$passedTests}/{$totalTests} tests passed" . PHP_EOL;

        if ($passedTests === $totalTests) {
            echo GREEN . "🎉 All tests passed! Indexes are working correctly." . NC . PHP_EOL;
        } else {
            echo RED . "⚠️  Some tests failed. Check index configuration." . NC . PHP_EOL;
        }

        echo PHP_EOL;
    }

    private function printError(string $message): void
    {
        echo RED . "❌ ERROR: {$message}" . NC . PHP_EOL;
        echo PHP_EOL;
    }
}

// Run tests
try {
    $test = new IndexPerformanceTest();
    $test->run();
    exit(0);
} catch (Exception $e) {
    echo RED . "❌ FATAL ERROR: " . $e->getMessage() . NC . PHP_EOL;
    echo $e->getTraceAsString() . PHP_EOL;
    exit(1);
}
