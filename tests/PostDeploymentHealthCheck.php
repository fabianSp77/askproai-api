<?php

namespace Tests\PostDeployment;

use PHPUnit\Framework\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

/**
 * Post-Deployment Health Check Suite
 * Runs after each phase to verify system is still functioning
 *
 * Usage:
 *   vendor/bin/phpunit tests/PostDeploymentHealthCheck.php
 */
class PostDeploymentHealthCheck extends TestCase
{
    private array $results = [];
    private array $errors = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->results = [
            'timestamp' => Carbon::now()->toIso8601String(),
            'phase' => $this->getPhaseFromEnvironment(),
            'checks' => [],
        ];
    }

    protected function tearDown(): void
    {
        // Generate report
        $this->generateHealthCheckReport();
        parent::tearDown();
    }

    /**
     * Health Check 1: Database Connectivity
     */
    public function test_database_connectivity(): void
    {
        try {
            $result = DB::select('SELECT 1');
            $this->assertTrue(true, 'Database connected');
            $this->recordCheck('database_connectivity', 'PASS', [
                'query_time' => DB::getQueryLog()[0]['time'] ?? 'N/A',
            ]);
        } catch (\Exception $e) {
            $this->fail("Database connection failed: {$e->getMessage()}");
            $this->recordCheck('database_connectivity', 'FAIL', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Health Check 2: Redis Connectivity
     */
    public function test_redis_connectivity(): void
    {
        try {
            Redis::ping();
            $this->assertTrue(true, 'Redis connected');
            $this->recordCheck('redis_connectivity', 'PASS');
        } catch (\Exception $e) {
            $this->fail("Redis connection failed: {$e->getMessage()}");
            $this->recordCheck('redis_connectivity', 'FAIL', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Health Check 3: Schema Verification
     */
    public function test_database_schema_integrity(): void
    {
        $requiredColumns = [
            'appointments' => ['id', 'customer_id', 'service_id', 'starts_at', 'ends_at', 'status'],
            'customers' => ['id', 'name', 'phone', 'email'],
            'calls' => ['id', 'retell_call_id', 'status'],
        ];

        foreach ($requiredColumns as $table => $columns) {
            $tableColumns = DB::getSchemaBuilder()->getColumnListing($table);

            foreach ($columns as $column) {
                $this->assertContains(
                    $column,
                    $tableColumns,
                    "Column {$column} missing from {$table} table"
                );
            }
        }

        $this->recordCheck('database_schema_integrity', 'PASS', [
            'tables_verified' => count($requiredColumns),
        ]);
    }

    /**
     * Health Check 4: Appointment Creation
     */
    public function test_appointment_creation_works(): void
    {
        try {
            // Create test appointment
            $customer = \App\Models\Customer::factory()->create();
            $service = \App\Models\Service::factory()->create();

            $appointment = \App\Models\Appointment::create([
                'customer_id' => $customer->id,
                'service_id' => $service->id,
                'company_id' => $customer->company_id,
                'starts_at' => now()->addDay(),
                'ends_at' => now()->addDay()->addMinutes(30),
                'status' => 'scheduled',
            ]);

            $this->assertNotNull($appointment->id);
            $this->assertEquals('scheduled', $appointment->status);

            $this->recordCheck('appointment_creation', 'PASS', [
                'appointment_id' => $appointment->id,
            ]);

            // Cleanup
            $appointment->forceDelete();

        } catch (\Exception $e) {
            $this->fail("Appointment creation failed: {$e->getMessage()}");
            $this->recordCheck('appointment_creation', 'FAIL', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Health Check 5: Cache Operations
     */
    public function test_cache_operations(): void
    {
        try {
            $testKey = 'health_check:' . uniqid();
            $testValue = 'test_value_' . time();

            // Test set
            Cache::put($testKey, $testValue, 60);

            // Test get
            $retrieved = Cache::get($testKey);
            $this->assertEquals($testValue, $retrieved);

            // Test forget
            Cache::forget($testKey);
            $this->assertNull(Cache::get($testKey));

            $this->recordCheck('cache_operations', 'PASS');

        } catch (\Exception $e) {
            $this->fail("Cache operations failed: {$e->getMessage()}");
            $this->recordCheck('cache_operations', 'FAIL', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Health Check 6: API Endpoints
     */
    public function test_api_health_endpoint(): void
    {
        try {
            $response = Http::get(config('app.url') . '/api/health');

            $this->assertTrue(
                $response->successful(),
                "API health endpoint returned: {$response->status()}"
            );

            $this->recordCheck('api_health_endpoint', 'PASS', [
                'status_code' => $response->status(),
            ]);

        } catch (\Exception $e) {
            $this->fail("API health check failed: {$e->getMessage()}");
            $this->recordCheck('api_health_endpoint', 'FAIL', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Health Check 7: Queue Status
     */
    public function test_queue_status(): void
    {
        try {
            $pendingJobs = DB::table('jobs')->count();
            $failedJobs = DB::table('failed_jobs')->count();

            $this->recordCheck('queue_status', 'PASS', [
                'pending_jobs' => $pendingJobs,
                'failed_jobs' => $failedJobs,
            ]);

        } catch (\Exception $e) {
            $this->fail("Queue status check failed: {$e->getMessage()}");
            $this->recordCheck('queue_status', 'FAIL', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Health Check 8: Log File Integrity
     */
    public function test_log_file_integrity(): void
    {
        try {
            $logFile = storage_path('logs/laravel.log');
            $this->assertFileExists($logFile, 'Laravel log file not found');

            // Check for recent errors (last 1 hour)
            $recentErrors = $this->getRecentErrors($logFile);

            $this->recordCheck('log_file_integrity', 'PASS', [
                'recent_errors_count' => count($recentErrors),
                'file_size_mb' => round(filesize($logFile) / (1024 * 1024), 2),
            ]);

        } catch (\Exception $e) {
            $this->fail("Log file check failed: {$e->getMessage()}");
            $this->recordCheck('log_file_integrity', 'FAIL', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Health Check 9: Data Consistency
     */
    public function test_data_consistency(): void
    {
        try {
            // Check for orphaned appointments (no customer)
            $orphaned = DB::table('appointments')
                ->whereNull('customer_id')
                ->count();

            $this->assertEquals(0, $orphaned, 'Found orphaned appointments');

            // Check for duplicate idempotency keys
            $duplicates = DB::table('appointments')
                ->select('idempotency_key')
                ->whereNotNull('idempotency_key')
                ->groupBy('idempotency_key')
                ->havingRaw('COUNT(*) > 1')
                ->count();

            $this->assertEquals(0, $duplicates, 'Found duplicate idempotency keys');

            $this->recordCheck('data_consistency', 'PASS', [
                'orphaned_appointments' => 0,
                'duplicate_idempotency_keys' => 0,
            ]);

        } catch (\Exception $e) {
            $this->fail("Data consistency check failed: {$e->getMessage()}");
            $this->recordCheck('data_consistency', 'FAIL', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Health Check 10: Performance Metrics
     */
    public function test_performance_metrics(): void
    {
        try {
            $startTime = microtime(true);

            // Query performance
            $appointments = \App\Models\Appointment::query()
                ->with(['customer', 'service', 'company'])
                ->limit(100)
                ->get();

            $queryTime = (microtime(true) - $startTime) * 1000; // ms

            $this->assertLessThan(
                1000,
                $queryTime,
                "Query took {$queryTime}ms (should be <1000ms)"
            );

            $this->recordCheck('performance_metrics', 'PASS', [
                'query_time_ms' => round($queryTime, 2),
                'records_fetched' => count($appointments),
            ]);

        } catch (\Exception $e) {
            $this->fail("Performance check failed: {$e->getMessage()}");
            $this->recordCheck('performance_metrics', 'FAIL', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Helper: Record check result
     */
    private function recordCheck(string $name, string $status, array $details = []): void
    {
        $this->results['checks'][$name] = [
            'status' => $status,
            'details' => $details,
            'timestamp' => Carbon::now()->toIso8601String(),
        ];
    }

    /**
     * Helper: Get recent errors from log
     */
    private function getRecentErrors(string $logFile, int $hoursBack = 1): array
    {
        $errors = [];
        $cutoff = Carbon::now()->subHours($hoursBack);

        $lines = file($logFile, FILE_IGNORE_NEW_LINES);
        foreach ($lines as $line) {
            if (strpos($line, 'ERROR') !== false || strpos($line, 'CRITICAL') !== false) {
                // Parse timestamp if possible
                if (preg_match('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/', $line, $matches)) {
                    $lineTime = Carbon::createFromFormat('Y-m-d H:i:s', $matches[1]);
                    if ($lineTime->greaterThanOrEqualTo($cutoff)) {
                        $errors[] = $line;
                    }
                }
            }
        }

        return $errors;
    }

    /**
     * Helper: Get phase from environment
     */
    private function getPhaseFromEnvironment(): string
    {
        return env('DEPLOYMENT_PHASE', 'unknown');
    }

    /**
     * Generate health check report
     */
    private function generateHealthCheckReport(): void
    {
        $reportPath = storage_path('reports/health_check_' . now()->format('YmdHis') . '.json');

        // Ensure directory exists
        if (!file_exists(dirname($reportPath))) {
            mkdir(dirname($reportPath), 0755, true);
        }

        // Count passes/failures
        $passed = collect($this->results['checks'])
            ->filter(fn($check) => $check['status'] === 'PASS')
            ->count();

        $failed = collect($this->results['checks'])
            ->filter(fn($check) => $check['status'] === 'FAIL')
            ->count();

        $this->results['summary'] = [
            'total_checks' => count($this->results['checks']),
            'passed' => $passed,
            'failed' => $failed,
            'success_rate' => round(($passed / count($this->results['checks'])) * 100, 2) . '%',
        ];

        // Write report
        file_put_contents($reportPath, json_encode($this->results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        echo "\n✅ Health Check Report: {$reportPath}\n";
        echo "Summary: {$passed}/{$this->results['summary']['total_checks']} checks passed\n\n";
    }
}
