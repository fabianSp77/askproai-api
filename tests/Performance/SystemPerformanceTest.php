<?php

namespace Tests\Performance;

use App\Models\Company;
use App\Models\Branch;
use App\Models\Service;
use App\Models\Staff;
use App\Models\Customer;
use App\Models\Appointment;
use App\Models\NotificationConfiguration;
use App\Models\PolicyConfiguration;
use App\Models\AppointmentModification;
use App\Models\AppointmentModificationStat;
use App\Services\Policies\AppointmentPolicyEngine;
use App\Services\Policies\PolicyConfigurationService;
use App\Services\Appointments\SmartAppointmentFinder;
use App\Services\Notifications\NotificationManager;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * System Performance Benchmarks
 *
 * Tests 5 critical performance metrics:
 * 1. Config Resolution: <50ms (cached), <200ms (uncached)
 * 2. Policy Check: <100ms
 * 3. Stats Query: <200ms
 * 4. Cal.com Mock Check: <2s
 * 5. Notification Send: <30s
 *
 * Each benchmark runs 100 iterations and reports average time
 */
class SystemPerformanceTest extends TestCase
{
    use DatabaseTransactions;

    protected const ITERATIONS = 100;
    protected const WARM_UP_ITERATIONS = 10;

    protected Company $company;
    protected Branch $branch;
    protected Service $service;
    protected Staff $staff;
    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test entities
        $this->company = Company::factory()->create();
        $this->branch = Branch::factory()->create(['company_id' => $this->company->id]);
        $this->service = Service::factory()->create([
            'company_id' => $this->company->id,
            'calcom_event_type_id' => 12345,
        ]);
        $this->staff = Staff::factory()->create(['branch_id' => $this->branch->id]);
        $this->customer = Customer::factory()->create(['company_id' => $this->company->id]);

        // Fake HTTP for Cal.com
        Http::fake();
    }

    /**
     * BENCHMARK 1: NotificationConfiguration Hierarchical Resolution
     *
     * Target: <50ms (cached), <200ms (cold)
     * Iterations: 100
     */
    public function test_notification_config_resolution_performance()
    {
        // Setup: Create hierarchical notification configs
        NotificationConfiguration::create([
            'configurable_type' => Company::class,
            'configurable_id' => $this->company->id,
            'event_type' => 'appointment_reminder',
            'channel' => 'email',
            'fallback_channel' => 'sms',
            'is_enabled' => true,
            'retry_count' => 3,
            'retry_delay_minutes' => 5,
        ]);

        NotificationConfiguration::create([
            'configurable_type' => Branch::class,
            'configurable_id' => $this->branch->id,
            'event_type' => 'appointment_reminder',
            'channel' => 'sms',
            'fallback_channel' => 'whatsapp',
            'is_enabled' => true,
        ]);

        NotificationConfiguration::create([
            'configurable_type' => Staff::class,
            'configurable_id' => $this->staff->id,
            'event_type' => 'appointment_reminder',
            'channel' => 'whatsapp',
            'is_enabled' => true,
        ]);

        // Warm-up queries
        $this->warmUp(function () {
            NotificationConfiguration::forEntity($this->staff)
                ->byEvent('appointment_reminder')
                ->enabled()
                ->first();
        });

        // BENCHMARK: Cold (uncached) resolution
        Cache::flush();
        $coldTimes = $this->benchmark(function () {
            Cache::flush(); // Ensure each iteration is cold
            $config = NotificationConfiguration::forEntity($this->staff)
                ->byEvent('appointment_reminder')
                ->enabled()
                ->first();
            return $config;
        }, self::ITERATIONS);

        // BENCHMARK: Cached resolution
        $cachedTimes = $this->benchmark(function () {
            $cacheKey = "notification_config:{$this->staff->id}:appointment_reminder";
            return Cache::remember($cacheKey, 300, function () {
                return NotificationConfiguration::forEntity($this->staff)
                    ->byEvent('appointment_reminder')
                    ->enabled()
                    ->first();
            });
        }, self::ITERATIONS);

        // ASSERT: Performance targets met
        $coldAvg = array_sum($coldTimes) / count($coldTimes);
        $cachedAvg = array_sum($cachedTimes) / count($cachedTimes);

        $this->assertLessThan(200, $coldAvg, "Cold config resolution took {$coldAvg}ms (target: <200ms)");
        $this->assertLessThan(50, $cachedAvg, "Cached config resolution took {$cachedAvg}ms (target: <50ms)");

        // REPORT: Performance metrics
        $this->logPerformance('Config Resolution (Cold)', $coldTimes, 200);
        $this->logPerformance('Config Resolution (Cached)', $cachedTimes, 50);
    }

    /**
     * BENCHMARK 2: PolicyConfiguration Resolution and Validation
     *
     * Target: <100ms per policy check
     * Iterations: 100
     */
    public function test_policy_check_performance()
    {
        // Setup: Create policy configuration
        PolicyConfiguration::create([
            'configurable_type' => Company::class,
            'configurable_id' => $this->company->id,
            'policy_type' => 'cancellation',
            'is_enabled' => true,
            'config' => [
                'hours_before' => 24,
                'max_cancellations_per_month' => 3,
                'fee_tiers' => [
                    ['hours' => 24, 'fee' => 0],
                    ['hours' => 12, 'fee' => 10],
                    ['hours' => 0, 'fee' => 25],
                ],
            ],
        ]);

        // Create appointment for policy checking
        $appointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'service_id' => $this->service->id,
            'branch_id' => $this->branch->id,
            'starts_at' => Carbon::now()->addHours(48),
            'status' => 'confirmed',
        ]);

        $policyEngine = app(AppointmentPolicyEngine::class);

        // Warm-up
        $this->warmUp(function () use ($policyEngine, $appointment) {
            $policyEngine->canCancel($appointment);
        });

        // BENCHMARK: Policy check performance
        $times = $this->benchmark(function () use ($policyEngine, $appointment) {
            return $policyEngine->canCancel($appointment);
        }, self::ITERATIONS);

        // ASSERT: Performance target met
        $avg = array_sum($times) / count($times);
        $this->assertLessThan(100, $avg, "Policy check took {$avg}ms (target: <100ms)");

        // REPORT
        $this->logPerformance('Policy Check', $times, 100);
    }

    /**
     * BENCHMARK 3: Stats Query Performance (AppointmentModificationStat)
     *
     * Target: <200ms for 100 customer records
     * Iterations: 100
     */
    public function test_stats_query_performance()
    {
        // Setup: Create 100 customers with modification stats
        $customers = Customer::factory(100)->create(['company_id' => $this->company->id]);

        foreach ($customers as $customer) {
            // Create materialized stat record
            AppointmentModificationStat::create([
                'customer_id' => $customer->id,
                'period' => 'month',
                'period_start' => Carbon::now()->startOfMonth(),
                'period_end' => Carbon::now()->endOfMonth(),
                'cancel_count' => rand(0, 5),
                'reschedule_count' => rand(0, 3),
                'total_modifications' => rand(0, 8),
                'last_modification_at' => Carbon::now()->subDays(rand(1, 30)),
            ]);
        }

        // Warm-up
        $this->warmUp(function () use ($customers) {
            $customerId = $customers->random()->id;
            AppointmentModificationStat::where('customer_id', $customerId)
                ->where('period', 'month')
                ->first();
        });

        // BENCHMARK: Stats query with materialized view
        $times = $this->benchmark(function () use ($customers) {
            $customerId = $customers->random()->id;
            return AppointmentModificationStat::where('customer_id', $customerId)
                ->where('period', 'month')
                ->where('period_start', '<=', now())
                ->where('period_end', '>=', now())
                ->first();
        }, self::ITERATIONS);

        // ASSERT: Performance target met
        $avg = array_sum($times) / count($times);
        $this->assertLessThan(200, $avg, "Stats query took {$avg}ms (target: <200ms)");

        // REPORT
        $this->logPerformance('Stats Query (Materialized)', $times, 200);

        // BENCHMARK: Compare with real-time COUNT (without materialized view)
        $realTimeTimes = $this->benchmark(function () use ($customers) {
            $customerId = $customers->random()->id;
            return AppointmentModification::where('customer_id', $customerId)
                ->where('created_at', '>=', Carbon::now()->startOfMonth())
                ->where('created_at', '<=', Carbon::now()->endOfMonth())
                ->selectRaw('
                    COUNT(CASE WHEN modification_type = "cancel" THEN 1 END) as cancel_count,
                    COUNT(CASE WHEN modification_type = "reschedule" THEN 1 END) as reschedule_count,
                    COUNT(*) as total_modifications
                ')
                ->first();
        }, 20); // Fewer iterations due to slowness

        $realTimeAvg = array_sum($realTimeTimes) / count($realTimeTimes);

        // REPORT: Speedup from materialized view
        $speedup = $realTimeAvg / $avg;
        $this->logPerformance('Stats Query (Real-time COUNT)', $realTimeTimes, null);
        echo "\n✅ Materialized view speedup: {$speedup}x faster\n";
    }

    /**
     * BENCHMARK 4: Cal.com API Mock Check (SmartAppointmentFinder)
     *
     * Target: <2000ms (2s) with cache
     * Iterations: 100
     */
    public function test_calcom_mock_check_performance()
    {
        // Setup: Mock Cal.com API
        Http::fake([
            '*/slots/available*' => Http::response([
                'data' => [
                    'slots' => [
                        '2025-10-05T09:00:00Z',
                        '2025-10-05T10:00:00Z',
                        '2025-10-05T14:00:00Z',
                        '2025-10-05T15:00:00Z',
                        '2025-10-05T16:00:00Z',
                    ],
                ],
            ], 200),
        ]);

        $finder = new SmartAppointmentFinder($this->company);

        // Warm-up
        $this->warmUp(function () use ($finder) {
            $finder->findNextAvailable(
                $this->service,
                Carbon::parse('2025-10-05'),
                7
            );
        });

        // BENCHMARK: Cold (first call, no cache)
        Cache::flush();
        $coldTimes = $this->benchmark(function () use ($finder) {
            Cache::flush();
            return $finder->findNextAvailable(
                $this->service,
                Carbon::parse('2025-10-05'),
                7
            );
        }, 50); // Fewer iterations due to HTTP overhead

        // BENCHMARK: Cached (45s TTL)
        Cache::flush();
        $cachedTimes = $this->benchmark(function () use ($finder) {
            // First call populates cache, subsequent use cache
            return $finder->findNextAvailable(
                $this->service,
                Carbon::parse('2025-10-05'),
                7
            );
        }, self::ITERATIONS);

        // ASSERT: Performance targets
        $coldAvg = array_sum($coldTimes) / count($coldTimes);
        $cachedAvg = array_sum($cachedTimes) / count($cachedTimes);

        $this->assertLessThan(5000, $coldAvg, "Cal.com cold call took {$coldAvg}ms (target: <5000ms)");
        $this->assertLessThan(2000, $cachedAvg, "Cal.com cached call took {$cachedAvg}ms (target: <2000ms)");

        // REPORT
        $this->logPerformance('Cal.com Check (Cold)', $coldTimes, 5000);
        $this->logPerformance('Cal.com Check (Cached)', $cachedTimes, 2000);
    }

    /**
     * BENCHMARK 5: Notification Send Performance (Queue Dispatch)
     *
     * Target: <30,000ms (30s) for batch of 100 notifications
     * Iterations: 10 batches
     */
    public function test_notification_send_performance()
    {
        // Setup: Mock notification manager dependencies
        $templateEngine = $this->createMock(\App\Services\Notifications\TemplateEngine::class);
        $optimizer = $this->createMock(\App\Services\Notifications\DeliveryOptimizer::class);
        $analytics = $this->createMock(\App\Services\Notifications\AnalyticsTracker::class);

        $optimizer->method('getOptimalSendTime')->willReturn(now());

        $manager = new NotificationManager($templateEngine, $optimizer, $analytics);

        // Create notification configuration
        NotificationConfiguration::create([
            'configurable_type' => Company::class,
            'configurable_id' => $this->company->id,
            'event_type' => 'appointment_reminder',
            'channel' => 'email',
            'is_enabled' => true,
        ]);

        // Create 100 customers
        $customers = Customer::factory(100)->create(['company_id' => $this->company->id]);

        // Warm-up
        $this->warmUp(function () use ($manager, $customers) {
            $customer = $customers->random();
            $manager->send(
                $customer,
                'appointment_reminder',
                ['message' => 'Test'],
                ['email'],
                ['immediate' => false]
            );
        });

        // BENCHMARK: Batch notification dispatch (100 notifications)
        $batchTimes = $this->benchmark(function () use ($manager, $customers) {
            $start = microtime(true);

            foreach ($customers as $customer) {
                $manager->send(
                    $customer,
                    'appointment_reminder',
                    ['message' => 'Your appointment is tomorrow'],
                    ['email'],
                    ['immediate' => false] // Queue for async processing
                );
            }

            return (microtime(true) - $start) * 1000; // ms
        }, 10); // 10 batches

        // ASSERT: Performance target
        $avg = array_sum($batchTimes) / count($batchTimes);
        $this->assertLessThan(30000, $avg, "Batch notification send took {$avg}ms (target: <30000ms)");

        // Calculate per-notification average
        $perNotificationAvg = $avg / 100;

        // REPORT
        $this->logPerformance('Notification Batch (100)', $batchTimes, 30000);
        echo "\n📊 Per-notification average: {$perNotificationAvg}ms\n";
    }

    /**
     * Warm-up function to stabilize benchmarks
     */
    protected function warmUp(callable $operation): void
    {
        for ($i = 0; $i < self::WARM_UP_ITERATIONS; $i++) {
            $operation();
        }
    }

    /**
     * Benchmark a function over N iterations
     *
     * @return array Array of execution times in milliseconds
     */
    protected function benchmark(callable $operation, int $iterations): array
    {
        $times = [];

        for ($i = 0; $i < $iterations; $i++) {
            $start = microtime(true);
            $operation();
            $end = microtime(true);

            $times[] = ($end - $start) * 1000; // Convert to milliseconds
        }

        return $times;
    }

    /**
     * Log performance metrics
     */
    protected function logPerformance(string $name, array $times, ?float $target): void
    {
        $avg = array_sum($times) / count($times);
        $min = min($times);
        $max = max($times);
        $p95 = $this->percentile($times, 95);
        $p99 = $this->percentile($times, 99);

        $status = $target && $avg < $target ? '✅' : ($target ? '❌' : '📊');

        echo "\n{$status} {$name}:\n";
        echo "   Average: " . number_format($avg, 2) . "ms";
        if ($target) {
            echo " (target: <{$target}ms)";
        }
        echo "\n";
        echo "   Min: " . number_format($min, 2) . "ms\n";
        echo "   Max: " . number_format($max, 2) . "ms\n";
        echo "   P95: " . number_format($p95, 2) . "ms\n";
        echo "   P99: " . number_format($p99, 2) . "ms\n";
        echo "   Iterations: " . count($times) . "\n";
    }

    /**
     * Calculate percentile
     */
    protected function percentile(array $values, float $percentile): float
    {
        sort($values);
        $index = ceil(($percentile / 100) * count($values)) - 1;
        return $values[$index] ?? 0;
    }
}
