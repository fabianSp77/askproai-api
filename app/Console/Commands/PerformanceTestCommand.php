<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Call;
use App\Services\MCP\AppointmentMCPServer;
use App\Services\MCP\CustomerMCPServer;
use App\Services\MCP\DashboardMCPServer;
use Carbon\Carbon;

class PerformanceTestCommand extends Command
{
    protected $signature = 'performance:test 
        {--scenario=all : Test scenario to run (all, database, api, mcp, cache)}
        {--iterations=100 : Number of iterations}
        {--concurrent=10 : Number of concurrent operations}
        {--report : Generate detailed report}';

    protected $description = 'Run performance tests on the business portal';

    protected $metrics = [];
    protected $startTime;
    protected $startMemory;

    public function handle()
    {
        $scenario = $this->option('scenario');
        $iterations = (int) $this->option('iterations');
        $concurrent = (int) $this->option('concurrent');

        $this->info("Running performance tests...");
        $this->info("Scenario: {$scenario}");
        $this->info("Iterations: {$iterations}");
        $this->info("Concurrent: {$concurrent}");
        $this->info("---");

        $this->startTime = microtime(true);
        $this->startMemory = memory_get_usage(true);

        switch ($scenario) {
            case 'database':
                $this->testDatabase($iterations);
                break;
            case 'api':
                $this->testAPI($iterations, $concurrent);
                break;
            case 'mcp':
                $this->testMCPServers($iterations);
                break;
            case 'cache':
                $this->testCache($iterations);
                break;
            case 'all':
            default:
                $this->testDatabase($iterations);
                $this->testAPI($iterations, $concurrent);
                $this->testMCPServers($iterations);
                $this->testCache($iterations);
                break;
        }

        $this->displayResults();

        if ($this->option('report')) {
            $this->generateReport();
        }

        return 0;
    }

    protected function testDatabase($iterations)
    {
        $this->info("\nTesting database performance...");
        
        // Test 1: Simple queries
        $this->measureOperation('DB: Simple SELECT', $iterations, function () {
            Customer::where('company_id', 1)->limit(100)->get();
        });

        // Test 2: Complex queries with joins
        $this->measureOperation('DB: Complex JOIN', $iterations, function () {
            Appointment::with(['customer', 'staff', 'service', 'branch'])
                ->where('company_id', 1)
                ->where('starts_at', '>=', Carbon::now())
                ->limit(50)
                ->get();
        });

        // Test 3: Aggregation queries
        $this->measureOperation('DB: Aggregation', $iterations, function () {
            DB::table('appointments')
                ->where('company_id', 1)
                ->where('starts_at', '>=', Carbon::now()->subDays(30))
                ->groupBy('status')
                ->selectRaw('status, COUNT(*) as count, AVG(duration) as avg_duration')
                ->get();
        });

        // Test 4: Write operations
        $this->measureOperation('DB: INSERT', $iterations / 10, function () {
            $customer = Customer::create([
                'first_name' => 'Test',
                'last_name' => 'User',
                'email' => 'test' . uniqid() . '@example.com',
                'phone' => '+1234567890',
                'company_id' => 1,
                'branch_id' => 1,
            ]);
            $customer->delete(); // Clean up
        });

        // Test 5: Full-text search
        $this->measureOperation('DB: Full-text search', $iterations, function () {
            Customer::where('company_id', 1)
                ->where(function ($query) {
                    $query->where('first_name', 'like', '%john%')
                          ->orWhere('last_name', 'like', '%john%')
                          ->orWhere('email', 'like', '%john%');
                })
                ->limit(20)
                ->get();
        });
    }

    protected function testAPI($iterations, $concurrent)
    {
        $this->info("\nTesting API performance...");
        
        $baseUrl = config('app.url');
        $token = $this->getTestToken();

        // Test 1: Dashboard endpoint
        $this->measureConcurrentOperation('API: Dashboard', $iterations, $concurrent, function () use ($baseUrl, $token) {
            Http::withToken($token)->get("{$baseUrl}/api/dashboard");
        });

        // Test 2: Appointments list with pagination
        $this->measureConcurrentOperation('API: Appointments List', $iterations, $concurrent, function () use ($baseUrl, $token) {
            Http::withToken($token)->get("{$baseUrl}/api/appointments?page=1&per_page=20");
        });

        // Test 3: Customer search
        $this->measureConcurrentOperation('API: Customer Search', $iterations, $concurrent, function () use ($baseUrl, $token) {
            Http::withToken($token)->get("{$baseUrl}/api/customers?search=john");
        });

        // Test 4: Create appointment
        $this->measureConcurrentOperation('API: Create Appointment', $iterations / 10, $concurrent, function () use ($baseUrl, $token) {
            Http::withToken($token)->post("{$baseUrl}/api/appointments", [
                'customer_id' => 1,
                'service_id' => 1,
                'staff_id' => 1,
                'branch_id' => 1,
                'starts_at' => Carbon::now()->addDays(1)->toDateTimeString(),
                'ends_at' => Carbon::now()->addDays(1)->addHour()->toDateTimeString(),
                'notes' => 'Performance test appointment',
            ]);
        });
    }

    protected function testMCPServers($iterations)
    {
        $this->info("\nTesting MCP server performance...");
        
        // Test AppointmentMCPServer
        $appointmentMCP = app(AppointmentMCPServer::class);
        
        $this->measureOperation('MCP: Get Appointments', $iterations, function () use ($appointmentMCP) {
            $appointmentMCP->executeTool('getAppointments', [
                'page' => 1,
                'per_page' => 20,
                'status' => 'scheduled',
            ]);
        });

        $this->measureOperation('MCP: Check Availability', $iterations, function () use ($appointmentMCP) {
            $appointmentMCP->executeTool('checkAvailability', [
                'staff_id' => 1,
                'service_id' => 1,
                'date' => '2025-08-15',
            ]);
        });

        // Test CustomerMCPServer
        $customerMCP = app(CustomerMCPServer::class);
        
        $this->measureOperation('MCP: Search Customers', $iterations, function () use ($customerMCP) {
            $customerMCP->executeTool('searchCustomers', [
                'query' => 'john',
                'limit' => 10,
            ]);
        });

        // Test DashboardMCPServer
        $dashboardMCP = app(DashboardMCPServer::class);
        
        $this->measureOperation('MCP: Dashboard Stats', $iterations, function () use ($dashboardMCP) {
            $dashboardMCP->executeTool('getDashboardStats', [
                'period' => 'last_30_days',
            ]);
        });
    }

    protected function testCache($iterations)
    {
        $this->info("\nTesting cache performance...");
        
        // Test 1: Cache write
        $this->measureOperation('Cache: Write', $iterations, function () {
            $key = 'perf_test_' . uniqid();
            Cache::put($key, ['test' => 'data', 'timestamp' => now()], 60);
            Cache::forget($key); // Clean up
        });

        // Test 2: Cache read
        $testKey = 'perf_test_read';
        Cache::put($testKey, ['test' => 'data', 'array' => range(1, 100)], 3600);
        
        $this->measureOperation('Cache: Read', $iterations, function () use ($testKey) {
            Cache::get($testKey);
        });

        // Test 3: Cache remember
        $this->measureOperation('Cache: Remember', $iterations, function () {
            Cache::remember('perf_test_remember_' . random_int(1, 10), 60, function () {
                return DB::table('appointments')->count();
            });
        });

        // Test 4: Cache tags (if Redis)
        if (Cache::getStore() instanceof \Illuminate\Cache\RedisStore) {
            $this->measureOperation('Cache: Tagged operations', $iterations, function () {
                Cache::tags(['appointments', 'company_1'])->put(
                    'tagged_test_' . uniqid(),
                    ['data' => 'test'],
                    60
                );
            });
        }

        // Clean up
        Cache::forget($testKey);
    }

    protected function measureOperation($name, $iterations, $callback)
    {
        $times = [];
        $memoryUsages = [];
        $errors = 0;

        $this->output->write("  {$name}: ");
        $bar = $this->output->createProgressBar($iterations);

        for ($i = 0; $i < $iterations; $i++) {
            $startTime = microtime(true);
            $startMemory = memory_get_usage(true);

            try {
                $callback();
            } catch (\Exception $e) {
                $errors++;
                Log::error("Performance test error: {$name}", [
                    'error' => $e->getMessage(),
                    'iteration' => $i,
                ]);
            }

            $times[] = (microtime(true) - $startTime) * 1000; // Convert to ms
            $memoryUsages[] = memory_get_usage(true) - $startMemory;

            $bar->advance();
        }

        $bar->finish();
        $this->line('');

        $this->recordMetrics($name, $times, $memoryUsages, $errors);
    }

    protected function measureConcurrentOperation($name, $iterations, $concurrent, $callback)
    {
        $times = [];
        $errors = 0;

        $this->output->write("  {$name} (concurrent: {$concurrent}): ");
        $bar = $this->output->createProgressBar($iterations);

        $chunks = array_chunk(range(1, $iterations), $concurrent);

        foreach ($chunks as $chunk) {
            $promises = [];
            $batchStartTime = microtime(true);

            foreach ($chunk as $i) {
                try {
                    $callback();
                } catch (\Exception $e) {
                    $errors++;
                }
                $bar->advance();
            }

            $batchTime = (microtime(true) - $batchStartTime) * 1000;
            foreach ($chunk as $i) {
                $times[] = $batchTime / count($chunk);
            }
        }

        $bar->finish();
        $this->line('');

        $this->recordMetrics($name, $times, [], $errors);
    }

    protected function recordMetrics($name, $times, $memoryUsages, $errors)
    {
        $this->metrics[$name] = [
            'count' => count($times),
            'errors' => $errors,
            'min_time' => min($times),
            'max_time' => max($times),
            'avg_time' => array_sum($times) / count($times),
            'median_time' => $this->calculateMedian($times),
            'p95_time' => $this->calculatePercentile($times, 95),
            'p99_time' => $this->calculatePercentile($times, 99),
            'total_time' => array_sum($times),
        ];

        if (!empty($memoryUsages)) {
            $this->metrics[$name]['avg_memory'] = array_sum($memoryUsages) / count($memoryUsages);
            $this->metrics[$name]['max_memory'] = max($memoryUsages);
        }
    }

    protected function calculateMedian($array)
    {
        sort($array);
        $count = count($array);
        $middle = floor(($count - 1) / 2);

        if ($count % 2) {
            return $array[$middle];
        } else {
            return ($array[$middle] + $array[$middle + 1]) / 2;
        }
    }

    protected function calculatePercentile($array, $percentile)
    {
        sort($array);
        $index = ceil(($percentile / 100) * count($array)) - 1;
        return $array[$index];
    }

    protected function displayResults()
    {
        $this->info("\n" . str_repeat('=', 80));
        $this->info("PERFORMANCE TEST RESULTS");
        $this->info(str_repeat('=', 80));

        $totalDuration = round((microtime(true) - $this->startTime), 2);
        $totalMemory = round((memory_get_peak_usage(true) - $this->startMemory) / 1024 / 1024, 2);

        $this->info("Total test duration: {$totalDuration}s");
        $this->info("Peak memory usage: {$totalMemory}MB");
        $this->info('');

        $headers = ['Operation', 'Count', 'Errors', 'Min (ms)', 'Avg (ms)', 'P95 (ms)', 'P99 (ms)', 'Max (ms)'];
        $rows = [];

        foreach ($this->metrics as $name => $metrics) {
            $rows[] = [
                $name,
                $metrics['count'],
                $metrics['errors'],
                round($metrics['min_time'], 2),
                round($metrics['avg_time'], 2),
                round($metrics['p95_time'], 2),
                round($metrics['p99_time'], 2),
                round($metrics['max_time'], 2),
            ];
        }

        $this->table($headers, $rows);

        // Display warnings
        $this->checkPerformanceThresholds();
    }

    protected function checkPerformanceThresholds()
    {
        $warnings = [];
        $thresholds = [
            'API: Dashboard' => ['p95' => 500, 'p99' => 1000],
            'API: Appointments List' => ['p95' => 300, 'p99' => 500],
            'DB: Simple SELECT' => ['p95' => 50, 'p99' => 100],
            'Cache: Read' => ['p95' => 5, 'p99' => 10],
        ];

        foreach ($thresholds as $operation => $limits) {
            if (isset($this->metrics[$operation])) {
                $metrics = $this->metrics[$operation];
                
                if ($metrics['p95_time'] > $limits['p95']) {
                    $warnings[] = "{$operation}: P95 ({$metrics['p95_time']}ms) exceeds threshold ({$limits['p95']}ms)";
                }
                
                if ($metrics['p99_time'] > $limits['p99']) {
                    $warnings[] = "{$operation}: P99 ({$metrics['p99_time']}ms) exceeds threshold ({$limits['p99']}ms)";
                }
            }
        }

        if (!empty($warnings)) {
            $this->warn("\nPerformance Warnings:");
            foreach ($warnings as $warning) {
                $this->warn("  - {$warning}");
            }
        }
    }

    protected function generateReport()
    {
        $report = [
            'timestamp' => now()->toIso8601String(),
            'duration' => round((microtime(true) - $this->startTime), 2),
            'memory_peak' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
            'metrics' => $this->metrics,
            'environment' => [
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
                'database' => config('database.default'),
                'cache_driver' => config('cache.default'),
                'queue_driver' => config('queue.default'),
            ],
        ];

        $filename = storage_path('performance-reports/performance-test-' . now()->format('Y-m-d-His') . '.json');
        
        if (!is_dir(dirname($filename))) {
            mkdir(dirname($filename), 0755, true);
        }

        file_put_contents($filename, json_encode($report, JSON_PRETTY_PRINT));

        $this->info("\nReport saved to: {$filename}");
    }

    protected function getTestToken()
    {
        // In a real scenario, you would authenticate and get a token
        // For testing, we'll use a pre-configured test token or create a test user
        return env('PERFORMANCE_TEST_TOKEN', 'test-token');
    }
}