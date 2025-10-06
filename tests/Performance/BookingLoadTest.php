<?php

namespace Tests\Performance;

use Tests\TestCase;
use App\Models\Service;
use App\Models\Branch;
use App\Models\Staff;
use App\Models\Company;
use App\Models\WorkingHour;
use App\Services\Booking\AvailabilityService;
use App\Services\Booking\BookingLockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BookingLoadTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Branch $branch;
    protected array $services = [];
    protected array $staff = [];

    protected function setUp(): void
    {
        parent::setUp();

        // Clear caches
        Cache::flush();

        // Create test data at scale
        $this->company = Company::factory()->create();
        $this->branch = Branch::factory()->create(['company_id' => $this->company->id]);

        // Create 10 services
        for ($i = 0; $i < 10; $i++) {
            $this->services[] = Service::factory()->create([
                'company_id' => $this->company->id,
                'branch_id' => $this->branch->id,
                'is_active' => true,
                'is_online' => true,
                'duration_minutes' => rand(30, 120),
                'price' => rand(20, 200),
            ]);
        }

        // Create 20 staff members
        for ($i = 0; $i < 20; $i++) {
            $staff = Staff::factory()->create([
                'company_id' => $this->company->id,
                'branch_id' => $this->branch->id,
                'is_active' => true,
            ]);

            // Attach random services
            $staff->services()->attach(
                $this->services[array_rand($this->services)]->id
            );

            // Create working hours
            for ($day = 0; $day < 7; $day++) {
                WorkingHour::create([
                    'staff_id' => $staff->id,
                    'company_id' => $this->company->id,
                    'branch_id' => $this->branch->id,
                    'day_of_week' => $day,
                    'start' => '09:00',
                    'end' => '18:00',
                    'is_active' => true,
                ]);
            }

            $this->staff[] = $staff;
        }
    }

    /**
     * Test 100 concurrent booking requests
     */
    public function test100ConcurrentBookingRequests()
    {
        $startTime = microtime(true);
        $requests = [];
        $tomorrow = Carbon::tomorrow();

        // Prepare 100 booking requests
        for ($i = 0; $i < 100; $i++) {
            $service = $this->services[array_rand($this->services)];
            $hour = rand(9, 16);
            $minute = rand(0, 3) * 15;

            $requests[] = [
                'service_id' => $service->id,
                'branch_id' => $this->branch->id,
                'customer' => [
                    'name' => "Test Customer $i",
                    'email' => "customer$i@test.com",
                    'phone' => "+4912345678$i",
                ],
                'timeZone' => 'Europe/Berlin',
                'start' => $tomorrow->copy()->setTime($hour, $minute)->format('Y-m-d\TH:i:s'),
                'source' => 'api',
            ];
        }

        // Execute requests
        $responses = [];
        foreach ($requests as $request) {
            $response = $this->postJson('/api/v2/bookings', $request);
            $responses[] = $response->status();
        }

        $duration = microtime(true) - $startTime;

        // Assertions
        $this->assertLessThan(10, $duration, 'Processing 100 requests should take less than 10 seconds');

        $successCount = count(array_filter($responses, fn($status) => $status === 201));
        $this->assertGreaterThan(50, $successCount, 'At least 50% of bookings should succeed');

        // Log performance metrics
        $this->logPerformanceMetric('100_concurrent_bookings', [
            'duration' => $duration,
            'requests' => 100,
            'success_rate' => $successCount / 100,
            'avg_time_per_request' => $duration / 100,
        ]);
    }

    /**
     * Test availability calculation performance
     */
    public function testAvailabilityCalculationUnder50ms()
    {
        $availabilityService = app(AvailabilityService::class);
        $measurements = [];

        // Run 100 availability checks
        for ($i = 0; $i < 100; $i++) {
            $service = $this->services[array_rand($this->services)];
            $date = Carbon::today()->addDays(rand(1, 30));

            $start = microtime(true);

            $slots = $availabilityService->getAvailableSlots(
                $service->id,
                $this->branch->id,
                $date
            );

            $duration = (microtime(true) - $start) * 1000; // Convert to ms
            $measurements[] = $duration;
        }

        // Calculate statistics
        $avgDuration = array_sum($measurements) / count($measurements);
        $maxDuration = max($measurements);
        $minDuration = min($measurements);

        // Assertions
        $this->assertLessThan(50, $avgDuration, 'Average availability calculation should be under 50ms');
        $this->assertLessThan(100, $maxDuration, 'Max availability calculation should be under 100ms');

        $this->logPerformanceMetric('availability_calculation', [
            'avg_duration_ms' => $avgDuration,
            'max_duration_ms' => $maxDuration,
            'min_duration_ms' => $minDuration,
            'samples' => count($measurements),
        ]);
    }

    /**
     * Test slot locking mechanism under load
     */
    public function testSlotLockingMechanism()
    {
        $lockService = app(BookingLockService::class);
        $tomorrow = Carbon::tomorrow()->setTime(10, 0);
        $endTime = $tomorrow->copy()->addHour();

        $successfulLocks = 0;
        $failedLocks = 0;

        // Try to acquire same slot 50 times concurrently
        for ($i = 0; $i < 50; $i++) {
            $lock = $lockService->acquireStaffLock(
                (string) $this->staff[0]->id,
                $tomorrow,
                $endTime
            );

            if ($lock) {
                $successfulLocks++;
                // Simulate some work
                usleep(10000); // 10ms
                $lock->release();
            } else {
                $failedLocks++;
            }
        }

        // Only first lock should succeed in rapid succession
        $this->assertEquals(50, $successfulLocks, 'All locks should eventually succeed after release');

        $this->logPerformanceMetric('slot_locking', [
            'successful_locks' => $successfulLocks,
            'failed_locks' => $failedLocks,
            'total_attempts' => 50,
        ]);
    }

    /**
     * Test cache warmup strategy
     */
    public function testCacheWarmupStrategy()
    {
        // Clear cache first
        Cache::flush();

        $startTime = microtime(true);

        // First request (cold cache)
        $availabilityService = app(AvailabilityService::class);
        $coldStart = microtime(true);
        $coldSlots = $availabilityService->getAvailableSlots(
            $this->services[0]->id,
            $this->branch->id,
            Carbon::tomorrow()
        );
        $coldDuration = (microtime(true) - $coldStart) * 1000;

        // Second request (warm cache)
        $warmStart = microtime(true);
        $warmSlots = $availabilityService->getAvailableSlots(
            $this->services[0]->id,
            $this->branch->id,
            Carbon::tomorrow()
        );
        $warmDuration = (microtime(true) - $warmStart) * 1000;

        // Cache should significantly improve performance
        $this->assertLessThan($coldDuration * 0.5, $warmDuration, 'Warm cache should be at least 50% faster');

        $this->logPerformanceMetric('cache_performance', [
            'cold_duration_ms' => $coldDuration,
            'warm_duration_ms' => $warmDuration,
            'improvement_ratio' => $coldDuration / $warmDuration,
        ]);
    }

    /**
     * Test database query performance
     */
    public function testDatabaseQueryPerformance()
    {
        // Enable query logging
        DB::enableQueryLog();

        $availabilityService = app(AvailabilityService::class);
        $availabilityService->getAvailableSlots(
            $this->services[0]->id,
            $this->branch->id,
            Carbon::tomorrow()
        );

        $queries = DB::getQueryLog();
        $totalQueries = count($queries);
        $totalQueryTime = array_sum(array_column($queries, 'time'));

        // Assertions
        $this->assertLessThan(10, $totalQueries, 'Should execute less than 10 queries');
        $this->assertLessThan(50, $totalQueryTime, 'Total query time should be under 50ms');

        $this->logPerformanceMetric('database_queries', [
            'total_queries' => $totalQueries,
            'total_time_ms' => $totalQueryTime,
            'avg_time_per_query' => $totalQueryTime / max(1, $totalQueries),
        ]);
    }

    /**
     * Test memory usage
     */
    public function testMemoryUsage()
    {
        $initialMemory = memory_get_usage(true);

        // Create 1000 appointments in memory
        $appointments = [];
        for ($i = 0; $i < 1000; $i++) {
            $appointments[] = [
                'id' => $i,
                'service_id' => $this->services[0]->id,
                'staff_id' => $this->staff[0]->id,
                'starts_at' => Carbon::tomorrow()->addMinutes($i * 30),
                'ends_at' => Carbon::tomorrow()->addMinutes(($i * 30) + 30),
            ];
        }

        $peakMemory = memory_get_peak_usage(true);
        $memoryUsed = ($peakMemory - $initialMemory) / 1024 / 1024; // Convert to MB

        // Should use less than 50MB for 1000 appointments
        $this->assertLessThan(50, $memoryUsed, 'Memory usage should be under 50MB for 1000 appointments');

        $this->logPerformanceMetric('memory_usage', [
            'initial_memory_mb' => $initialMemory / 1024 / 1024,
            'peak_memory_mb' => $peakMemory / 1024 / 1024,
            'used_memory_mb' => $memoryUsed,
            'appointments_count' => 1000,
        ]);
    }

    /**
     * Test availability grid generation for month
     */
    public function testMonthlyAvailabilityGridPerformance()
    {
        $availabilityService = app(AvailabilityService::class);

        $startTime = microtime(true);

        $heatmap = $availabilityService->getAvailabilityHeatmap(
            $this->branch->id,
            Carbon::now()->startOfMonth()
        );

        $duration = (microtime(true) - $startTime) * 1000;

        // Should generate monthly heatmap in under 500ms
        $this->assertLessThan(500, $duration);
        $this->assertCount(Carbon::now()->daysInMonth, $heatmap);

        $this->logPerformanceMetric('monthly_heatmap', [
            'duration_ms' => $duration,
            'days_processed' => count($heatmap),
        ]);
    }

    /**
     * Test concurrent availability checks
     */
    public function testConcurrentAvailabilityChecks()
    {
        $availabilityService = app(AvailabilityService::class);
        $promises = [];

        $startTime = microtime(true);

        // Simulate 50 concurrent availability checks
        for ($i = 0; $i < 50; $i++) {
            $service = $this->services[array_rand($this->services)];
            $date = Carbon::today()->addDays(rand(1, 7));

            $slots = $availabilityService->getAvailableSlots(
                $service->id,
                $this->branch->id,
                $date
            );

            $this->assertIsObject($slots);
        }

        $duration = microtime(true) - $startTime;

        // 50 concurrent checks should complete in under 2 seconds
        $this->assertLessThan(2, $duration);

        $this->logPerformanceMetric('concurrent_availability', [
            'duration_s' => $duration,
            'concurrent_checks' => 50,
            'avg_time_per_check' => ($duration / 50) * 1000, // ms
        ]);
    }

    /**
     * Log performance metrics for analysis
     */
    protected function logPerformanceMetric(string $test, array $metrics)
    {
        $logFile = storage_path('logs/performance-' . date('Y-m-d') . '.log');
        $logEntry = [
            'timestamp' => now()->toIso8601String(),
            'test' => $test,
            'metrics' => $metrics,
        ];

        file_put_contents(
            $logFile,
            json_encode($logEntry) . PHP_EOL,
            FILE_APPEND | LOCK_EX
        );

        // Output to console for immediate feedback
        echo "\n[PERFORMANCE] $test: " . json_encode($metrics, JSON_PRETTY_PRINT) . "\n";
    }
}