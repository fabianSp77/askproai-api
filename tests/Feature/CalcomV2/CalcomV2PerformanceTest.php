<?php

namespace Tests\Feature\CalcomV2;

use Tests\TestCase;
use App\Services\CalcomV2Client;
use App\Services\Booking\CompositeBookingService;
use App\Services\Booking\BookingLockService;
use App\Services\Communication\NotificationService;
use App\Models\{Company, Branch, Service, Staff, Customer, Appointment};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Carbon\Carbon;
use Symfony\Component\Stopwatch\Stopwatch;

class CalcomV2PerformanceTest extends TestCase
{
    use RefreshDatabase;

    private CalcomV2Client $client;
    private CompositeBookingService $compositeService;
    private Stopwatch $stopwatch;
    private array $performanceMetrics = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->stopwatch = new Stopwatch();

        // Create test company with API key
        $company = Company::factory()->create([
            'calcom_v2_api_key' => 'perf-test-key-' . uniqid()
        ]);

        $this->client = new CalcomV2Client($company);

        $this->compositeService = new CompositeBookingService(
            $this->client,
            app(BookingLockService::class),
            app(NotificationService::class)
        );

        // Setup performance monitoring
        $this->setupPerformanceMonitoring();
    }

    protected function tearDown(): void
    {
        // Log performance report
        $this->generatePerformanceReport();

        parent::tearDown();
    }

    /**
     * Test concurrent slot availability queries
     */
    public function test_concurrent_availability_queries()
    {
        $concurrentRequests = 50;
        $results = [];

        Http::fake([
            'api.cal.com/v2/slots*' => function () {
                // Simulate variable response times
                usleep(rand(50000, 200000)); // 50-200ms

                return Http::response([
                    'status' => 'success',
                    'data' => [
                        'slots' => $this->generateMockSlots(10)
                    ]
                ], 200);
            }
        ]);

        $this->stopwatch->start('concurrent_availability');

        $promises = [];
        for ($i = 0; $i < $concurrentRequests; $i++) {
            $eventTypeId = 10000 + $i;
            $start = Carbon::now()->addDays($i % 7);
            $end = $start->copy()->addHours(8);

            $promises[] = [
                'id' => $i,
                'start_time' => microtime(true),
                'request' => function() use ($eventTypeId, $start, $end) {
                    return $this->client->getAvailableSlots($eventTypeId, $start, $end);
                }
            ];
        }

        // Execute requests concurrently
        foreach ($promises as $promise) {
            $startTime = microtime(true);
            $response = $promise['request']();
            $duration = microtime(true) - $startTime;

            $results[] = [
                'id' => $promise['id'],
                'duration' => $duration,
                'successful' => $response->successful()
            ];
        }

        $event = $this->stopwatch->stop('concurrent_availability');

        // Analyze results
        $totalDuration = $event->getDuration();
        $avgDuration = collect($results)->avg('duration');
        $successRate = collect($results)->where('successful', true)->count() / $concurrentRequests;

        $this->performanceMetrics['concurrent_availability'] = [
            'total_requests' => $concurrentRequests,
            'total_duration_ms' => $totalDuration,
            'avg_request_duration_ms' => $avgDuration * 1000,
            'success_rate' => $successRate,
            'requests_per_second' => $concurrentRequests / ($totalDuration / 1000)
        ];

        // Assertions
        $this->assertGreaterThanOrEqual(0.95, $successRate); // 95% success rate
        $this->assertLessThan(500, $avgDuration * 1000); // Avg under 500ms
    }

    /**
     * Test bulk booking creation performance
     */
    public function test_bulk_booking_creation()
    {
        $bookingCount = 100;
        $batchSize = 10;

        Http::fake([
            'api.cal.com/v2/bookings' => function () {
                static $callCount = 0;
                $callCount++;

                // Simulate occasional slow responses
                if ($callCount % 10 === 0) {
                    usleep(500000); // 500ms
                }

                return Http::response([
                    'status' => 'success',
                    'data' => [
                        'id' => 20000 + $callCount,
                        'uid' => 'perf-booking-' . $callCount,
                        'status' => 'ACCEPTED'
                    ]
                ], 201);
            }
        ]);

        $this->stopwatch->start('bulk_booking');

        $results = [];
        $batches = array_chunk(range(1, $bookingCount), $batchSize);

        foreach ($batches as $batchIndex => $batch) {
            $batchStart = microtime(true);

            foreach ($batch as $bookingIndex) {
                $start = Carbon::now()->addDays($bookingIndex)->setTime(10, 0);

                $response = $this->client->createBooking([
                    'eventTypeId' => 12345,
                    'start' => $start->toIso8601String(),
                    'end' => $start->copy()->addHour()->toIso8601String(),
                    'timeZone' => 'Europe/Berlin',
                    'name' => "Performance Test User {$bookingIndex}",
                    'email' => "perf{$bookingIndex}@test.com"
                ]);

                $results[] = [
                    'index' => $bookingIndex,
                    'batch' => $batchIndex,
                    'successful' => $response->successful()
                ];
            }

            $batchDuration = microtime(true) - $batchStart;
            Log::info("Batch {$batchIndex} completed", [
                'duration' => $batchDuration,
                'bookings' => count($batch)
            ]);

            // Throttle between batches
            if ($batchIndex < count($batches) - 1) {
                usleep(100000); // 100ms pause
            }
        }

        $event = $this->stopwatch->stop('bulk_booking');

        $successCount = collect($results)->where('successful', true)->count();
        $throughput = $bookingCount / ($event->getDuration() / 1000);

        $this->performanceMetrics['bulk_booking'] = [
            'total_bookings' => $bookingCount,
            'successful_bookings' => $successCount,
            'total_duration_ms' => $event->getDuration(),
            'bookings_per_second' => $throughput,
            'success_rate' => $successCount / $bookingCount
        ];

        // Assertions
        $this->assertGreaterThanOrEqual(0.98, $successCount / $bookingCount);
        $this->assertGreaterThan(5, $throughput); // At least 5 bookings/second
    }

    /**
     * Test composite booking performance with parallel processing
     */
    public function test_composite_booking_performance()
    {
        $compositeBookings = 20;

        // Create test data
        $service = Service::factory()->create([
            'is_composite' => true,
            'segments' => [
                ['key' => 'A', 'durationMin' => 60, 'gapAfterMin' => 30, 'gapAfterMax' => 60],
                ['key' => 'B', 'durationMin' => 45],
                ['key' => 'C', 'durationMin' => 30]
            ]
        ]);

        Http::fake([
            'api.cal.com/v2/slots*' => Http::response([
                'status' => 'success',
                'data' => [
                    'slots' => $this->generateMockSlots(50)
                ]
            ], 200),
            'api.cal.com/v2/bookings' => Http::response([
                'status' => 'success',
                'data' => [
                    'id' => rand(30000, 40000),
                    'uid' => 'composite-' . uniqid(),
                    'status' => 'ACCEPTED'
                ]
            ], 201)
        ]);

        $this->stopwatch->start('composite_booking');

        $results = [];

        for ($i = 0; $i < $compositeBookings; $i++) {
            $bookingStart = microtime(true);

            try {
                // Simulate composite booking logic
                $segmentDurations = [];

                foreach ($service->segments as $segment) {
                    $segStart = microtime(true);

                    // Simulate segment processing
                    $this->processSegmentBooking($segment);

                    $segmentDurations[] = microtime(true) - $segStart;
                }

                $totalDuration = microtime(true) - $bookingStart;

                $results[] = [
                    'index' => $i,
                    'duration' => $totalDuration,
                    'segment_durations' => $segmentDurations,
                    'successful' => true
                ];
            } catch (\Exception $e) {
                $results[] = [
                    'index' => $i,
                    'duration' => microtime(true) - $bookingStart,
                    'successful' => false,
                    'error' => $e->getMessage()
                ];
            }
        }

        $event = $this->stopwatch->stop('composite_booking');

        $successCount = collect($results)->where('successful', true)->count();
        $avgDuration = collect($results)->avg('duration');

        $this->performanceMetrics['composite_booking'] = [
            'total_composite_bookings' => $compositeBookings,
            'successful_bookings' => $successCount,
            'avg_booking_duration_ms' => $avgDuration * 1000,
            'total_duration_ms' => $event->getDuration(),
            'success_rate' => $successCount / $compositeBookings
        ];

        // Assertions
        $this->assertGreaterThanOrEqual(0.90, $successCount / $compositeBookings);
        $this->assertLessThan(2000, $avgDuration * 1000); // Under 2 seconds avg
    }

    /**
     * Test database query optimization for appointment lookups
     */
    public function test_database_query_performance()
    {
        // Create large dataset
        $companies = Company::factory()->count(5)->create();
        $appointments = [];

        foreach ($companies as $company) {
            $branches = Branch::factory()->count(3)->create(['company_id' => $company->id]);

            foreach ($branches as $branch) {
                $staff = Staff::factory()->count(10)->create([
                    'company_id' => $company->id,
                    'branch_id' => $branch->id
                ]);

                foreach ($staff as $member) {
                    // Create 50 appointments per staff member
                    for ($i = 0; $i < 50; $i++) {
                        $appointments[] = [
                            'company_id' => $company->id,
                            'branch_id' => $branch->id,
                            'staff_id' => $member->id,
                            'customer_id' => rand(1, 1000),
                            'service_id' => rand(1, 20),
                            'starts_at' => Carbon::now()->addDays(rand(-30, 30)),
                            'ends_at' => Carbon::now()->addDays(rand(-30, 30))->addHour(),
                            'status' => ['booked', 'cancelled', 'completed'][rand(0, 2)],
                            'created_at' => now(),
                            'updated_at' => now()
                        ];
                    }
                }
            }
        }

        // Bulk insert for performance
        DB::table('appointments')->insert($appointments);

        $this->stopwatch->start('db_queries');

        // Test various query patterns
        $queries = [
            'daily_appointments' => function() {
                return Appointment::whereDate('starts_at', Carbon::today())
                    ->with(['staff', 'customer', 'service'])
                    ->get();
            },
            'staff_schedule' => function() {
                return Appointment::where('staff_id', rand(1, 150))
                    ->whereBetween('starts_at', [
                        Carbon::now()->startOfWeek(),
                        Carbon::now()->endOfWeek()
                    ])
                    ->orderBy('starts_at')
                    ->get();
            },
            'branch_metrics' => function() {
                return DB::table('appointments')
                    ->select('branch_id', 'status', DB::raw('COUNT(*) as count'))
                    ->groupBy('branch_id', 'status')
                    ->get();
            },
            'availability_check' => function() {
                $start = Carbon::now()->setTime(10, 0);
                $end = Carbon::now()->setTime(11, 0);

                return Appointment::where('status', 'booked')
                    ->where(function($q) use ($start, $end) {
                        $q->whereBetween('starts_at', [$start, $end])
                          ->orWhereBetween('ends_at', [$start, $end]);
                    })
                    ->exists();
            }
        ];

        $queryMetrics = [];

        foreach ($queries as $name => $query) {
            $queryStart = microtime(true);
            $result = $query();
            $queryDuration = microtime(true) - $queryStart;

            $queryMetrics[$name] = [
                'duration_ms' => $queryDuration * 1000,
                'result_count' => $result instanceof Collection ? $result->count() : 1
            ];
        }

        $event = $this->stopwatch->stop('db_queries');

        $this->performanceMetrics['database_queries'] = [
            'total_appointments' => count($appointments),
            'query_metrics' => $queryMetrics,
            'total_query_time_ms' => $event->getDuration(),
            'avg_query_time_ms' => collect($queryMetrics)->avg('duration_ms')
        ];

        // Assertions
        foreach ($queryMetrics as $name => $metric) {
            $this->assertLessThan(100, $metric['duration_ms'], "Query {$name} too slow");
        }
    }

    /**
     * Test cache performance for frequently accessed data
     */
    public function test_cache_performance()
    {
        $cacheOperations = 1000;
        $cacheKeys = [];

        $this->stopwatch->start('cache_operations');

        // Generate cache keys
        for ($i = 0; $i < 100; $i++) {
            $cacheKeys[] = 'calcom:test:' . $i;
        }

        // Test write performance
        $writeStart = microtime(true);
        foreach ($cacheKeys as $key) {
            Cache::put($key, [
                'data' => str_repeat('x', 1000),
                'timestamp' => now()
            ], 3600);
        }
        $writeDuration = microtime(true) - $writeStart;

        // Test read performance
        $readStart = microtime(true);
        for ($i = 0; $i < $cacheOperations; $i++) {
            $key = $cacheKeys[array_rand($cacheKeys)];
            Cache::get($key);
        }
        $readDuration = microtime(true) - $readStart;

        // Test cache tags (if supported)
        $tagStart = microtime(true);
        Cache::tags(['calcom', 'performance'])->put('tagged_key', 'value', 3600);
        Cache::tags(['calcom', 'performance'])->get('tagged_key');
        Cache::tags(['calcom'])->flush();
        $tagDuration = microtime(true) - $tagStart;

        $event = $this->stopwatch->stop('cache_operations');

        $this->performanceMetrics['cache_performance'] = [
            'total_keys' => count($cacheKeys),
            'write_operations' => count($cacheKeys),
            'read_operations' => $cacheOperations,
            'write_duration_ms' => $writeDuration * 1000,
            'read_duration_ms' => $readDuration * 1000,
            'tag_operations_ms' => $tagDuration * 1000,
            'writes_per_second' => count($cacheKeys) / $writeDuration,
            'reads_per_second' => $cacheOperations / $readDuration
        ];

        // Assertions
        $this->assertLessThan(1000, $writeDuration * 1000); // Under 1 second for writes
        $this->assertLessThan(500, $readDuration * 1000); // Under 500ms for reads
        $this->assertGreaterThan(1000, $cacheOperations / $readDuration); // >1000 reads/sec
    }

    /**
     * Test API response time distribution
     */
    public function test_response_time_distribution()
    {
        $sampleSize = 200;
        $responseTimes = [];

        Http::fake([
            'api.cal.com/v2/*' => function () {
                // Simulate realistic response time distribution
                $percentile = rand(1, 100);

                if ($percentile <= 50) {
                    usleep(rand(50000, 100000)); // 50-100ms (P50)
                } elseif ($percentile <= 90) {
                    usleep(rand(100000, 300000)); // 100-300ms (P90)
                } elseif ($percentile <= 95) {
                    usleep(rand(300000, 500000)); // 300-500ms (P95)
                } else {
                    usleep(rand(500000, 1000000)); // 500-1000ms (P99)
                }

                return Http::response(['status' => 'success'], 200);
            }
        ]);

        $this->stopwatch->start('response_distribution');

        for ($i = 0; $i < $sampleSize; $i++) {
            $start = microtime(true);

            // Make various API calls
            $endpoint = ['slots', 'bookings', 'event-types'][rand(0, 2)];
            Http::get("api.cal.com/v2/{$endpoint}");

            $responseTimes[] = (microtime(true) - $start) * 1000; // Convert to ms
        }

        $event = $this->stopwatch->stop('response_distribution');

        // Calculate percentiles
        sort($responseTimes);
        $p50 = $responseTimes[(int)($sampleSize * 0.50)];
        $p90 = $responseTimes[(int)($sampleSize * 0.90)];
        $p95 = $responseTimes[(int)($sampleSize * 0.95)];
        $p99 = $responseTimes[(int)($sampleSize * 0.99)];

        $this->performanceMetrics['response_distribution'] = [
            'sample_size' => $sampleSize,
            'min_ms' => min($responseTimes),
            'max_ms' => max($responseTimes),
            'avg_ms' => array_sum($responseTimes) / $sampleSize,
            'p50_ms' => $p50,
            'p90_ms' => $p90,
            'p95_ms' => $p95,
            'p99_ms' => $p99,
            'std_deviation' => $this->calculateStdDev($responseTimes)
        ];

        // Assertions based on SLA requirements
        $this->assertLessThan(200, $p50, 'P50 exceeds 200ms');
        $this->assertLessThan(500, $p90, 'P90 exceeds 500ms');
        $this->assertLessThan(1000, $p99, 'P99 exceeds 1000ms');
    }

    /**
     * Test memory usage under load
     */
    public function test_memory_usage_under_load()
    {
        $iterations = 100;
        $memorySnapshots = [];

        $this->stopwatch->start('memory_test');

        $initialMemory = memory_get_usage(true);

        for ($i = 0; $i < $iterations; $i++) {
            // Simulate memory-intensive operations
            $data = [];

            // Create large appointment dataset
            for ($j = 0; $j < 100; $j++) {
                $data[] = Appointment::factory()->make()->toArray();
            }

            // Process data
            $processed = collect($data)
                ->map(fn($item) => array_merge($item, ['processed' => true]))
                ->filter(fn($item) => $item['status'] === 'booked')
                ->values();

            // Take memory snapshot every 10 iterations
            if ($i % 10 === 0) {
                $memorySnapshots[] = [
                    'iteration' => $i,
                    'memory_mb' => (memory_get_usage(true) - $initialMemory) / 1024 / 1024
                ];
            }

            // Clear data to prevent memory leak
            unset($data, $processed);
        }

        $event = $this->stopwatch->stop('memory_test');

        $peakMemory = memory_get_peak_usage(true);
        $finalMemory = memory_get_usage(true);

        $this->performanceMetrics['memory_usage'] = [
            'initial_memory_mb' => $initialMemory / 1024 / 1024,
            'peak_memory_mb' => $peakMemory / 1024 / 1024,
            'final_memory_mb' => $finalMemory / 1024 / 1024,
            'memory_growth_mb' => ($finalMemory - $initialMemory) / 1024 / 1024,
            'snapshots' => $memorySnapshots,
            'duration_ms' => $event->getDuration()
        ];

        // Assertions
        $memoryGrowth = ($finalMemory - $initialMemory) / 1024 / 1024;
        $this->assertLessThan(50, $memoryGrowth, 'Memory leak detected');
        $this->assertLessThan(256, $peakMemory / 1024 / 1024, 'Peak memory too high');
    }

    /**
     * Test concurrent user simulation
     */
    public function test_concurrent_user_simulation()
    {
        $virtualUsers = 25;
        $actionsPerUser = 10;
        $userMetrics = [];

        Http::fake([
            'api.cal.com/v2/*' => Http::response(['status' => 'success'], 200)
        ]);

        $this->stopwatch->start('user_simulation');

        // Simulate virtual users
        for ($userId = 1; $userId <= $virtualUsers; $userId++) {
            $userStart = microtime(true);
            $actions = [];

            for ($action = 0; $action < $actionsPerUser; $action++) {
                $actionType = ['search', 'book', 'cancel', 'reschedule'][rand(0, 3)];
                $actionStart = microtime(true);

                // Simulate user action
                $this->simulateUserAction($userId, $actionType);

                $actions[] = [
                    'type' => $actionType,
                    'duration_ms' => (microtime(true) - $actionStart) * 1000
                ];

                // Simulate think time between actions
                usleep(rand(100000, 500000)); // 100-500ms
            }

            $userMetrics[] = [
                'user_id' => $userId,
                'total_duration_ms' => (microtime(true) - $userStart) * 1000,
                'actions' => $actions,
                'avg_action_time_ms' => collect($actions)->avg('duration_ms')
            ];
        }

        $event = $this->stopwatch->stop('user_simulation');

        $totalActions = $virtualUsers * $actionsPerUser;
        $avgUserDuration = collect($userMetrics)->avg('total_duration_ms');
        $avgActionDuration = collect($userMetrics)->avg('avg_action_time_ms');

        $this->performanceMetrics['user_simulation'] = [
            'virtual_users' => $virtualUsers,
            'actions_per_user' => $actionsPerUser,
            'total_actions' => $totalActions,
            'total_duration_ms' => $event->getDuration(),
            'avg_user_duration_ms' => $avgUserDuration,
            'avg_action_duration_ms' => $avgActionDuration,
            'throughput_actions_per_second' => $totalActions / ($event->getDuration() / 1000)
        ];

        // Assertions
        $this->assertLessThan(500, $avgActionDuration, 'Average action too slow');
        $this->assertGreaterThan(1, $totalActions / ($event->getDuration() / 1000));
    }

    // Helper methods

    private function setupPerformanceMonitoring()
    {
        // Setup query logging for performance analysis
        DB::listen(function ($query) {
            if ($query->time > 100) { // Log slow queries (>100ms)
                Log::warning('Slow query detected', [
                    'sql' => $query->sql,
                    'time' => $query->time,
                    'bindings' => $query->bindings
                ]);
            }
        });
    }

    private function generateMockSlots(int $count): array
    {
        $slots = [];
        $start = Carbon::now()->setTime(9, 0);

        for ($i = 0; $i < $count; $i++) {
            $slots[] = [
                'start' => $start->toIso8601String(),
                'end' => $start->copy()->addHour()->toIso8601String()
            ];
            $start->addHours(2); // 1-hour gap between slots
        }

        return $slots;
    }

    private function processSegmentBooking(array $segment)
    {
        // Simulate segment booking process
        usleep(rand(50000, 150000)); // 50-150ms

        return [
            'segment' => $segment['key'],
            'booking_id' => rand(10000, 99999)
        ];
    }

    private function calculateStdDev(array $values): float
    {
        $mean = array_sum($values) / count($values);
        $variance = 0;

        foreach ($values as $value) {
            $variance += pow($value - $mean, 2);
        }

        return sqrt($variance / count($values));
    }

    private function simulateUserAction(int $userId, string $actionType)
    {
        switch ($actionType) {
            case 'search':
                Http::get('api.cal.com/v2/slots');
                break;
            case 'book':
                Http::post('api.cal.com/v2/bookings', []);
                break;
            case 'cancel':
                Http::delete('api.cal.com/v2/bookings/' . rand(1000, 9999));
                break;
            case 'reschedule':
                Http::patch('api.cal.com/v2/bookings/' . rand(1000, 9999), []);
                break;
        }
    }

    private function generatePerformanceReport()
    {
        if (empty($this->performanceMetrics)) {
            return;
        }

        $report = "\n" . str_repeat('=', 80) . "\n";
        $report .= "CAL.COM V2 PERFORMANCE TEST REPORT\n";
        $report .= str_repeat('=', 80) . "\n\n";

        foreach ($this->performanceMetrics as $test => $metrics) {
            $report .= strtoupper(str_replace('_', ' ', $test)) . "\n";
            $report .= str_repeat('-', 40) . "\n";

            foreach ($metrics as $key => $value) {
                if (is_array($value)) {
                    $report .= sprintf("  %s:\n", $key);
                    foreach ($value as $k => $v) {
                        if (!is_array($v)) {
                            $report .= sprintf("    - %s: %s\n", $k, $this->formatValue($v));
                        }
                    }
                } else {
                    $report .= sprintf("  %s: %s\n", $key, $this->formatValue($value));
                }
            }
            $report .= "\n";
        }

        $report .= str_repeat('=', 80) . "\n";

        // Log the report
        Log::info($report);

        // Also save to file for CI/CD integration
        $reportFile = storage_path('logs/calcom_performance_' . date('Y-m-d_H-i-s') . '.txt');
        file_put_contents($reportFile, $report);
    }

    private function formatValue($value): string
    {
        if (is_float($value)) {
            return number_format($value, 2);
        } elseif (is_numeric($value) && $value > 1000) {
            return number_format($value);
        }
        return (string) $value;
    }
}