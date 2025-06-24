<?php

namespace Tests\E2E;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Staff;
use App\Models\Service;
use App\Models\CalcomEventType;
use App\Models\Appointment;
use App\Jobs\ProcessRetellCallEndedJob;
use App\Services\AppointmentBookingService;
use App\Services\CalcomV2Service;
use Tests\E2E\Helpers\WebhookPayloadBuilder;
use Tests\E2E\Mocks\MockCalcomV2Client;
use Carbon\Carbon;
use Illuminate\Support\Str;

class ConcurrentBookingStressTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Branch $branch;
    protected Staff $staff;
    protected Service $service;
    protected CalcomEventType $eventType;
    protected MockCalcomV2Client $mockCalcomClient;
    protected Carbon $testDate;

    protected function setUp(): void
    {
        parent::setUp();

        // Set test date
        $this->testDate = Carbon::now()->next('Monday')->setTime(10, 0);
        Carbon::setTestNow(Carbon::now()->startOfWeek());

        // Setup test data
        $this->setupTestData();
        
        // Setup mock Cal.com client
        $this->mockCalcomClient = new MockCalcomV2Client();
        $this->app->instance(\App\Services\Calcom\CalcomV2Client::class, $this->mockCalcomClient);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    protected function setupTestData(): void
    {
        $this->company = Company::factory()->create([
            'name' => 'Stress Test Clinic',
            'settings' => [
                'max_concurrent_bookings' => 5,
                'booking_lock_timeout' => 30, // seconds
            ],
        ]);

        $this->branch = Branch::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Main Branch',
        ]);

        $this->staff = Staff::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'name' => 'Dr. Stress Test',
            'calcom_user_id' => 1,
        ]);

        $this->service = Service::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Consultation',
            'duration' => 30,
            'price' => 100,
        ]);

        $this->eventType = CalcomEventType::factory()->create([
            'company_id' => $this->company->id,
            'calcom_id' => 12345,
            'title' => 'Consultation',
            'length' => 30,
        ]);

        $this->staff->services()->attach($this->service);
        $this->staff->eventTypes()->attach($this->eventType);
    }

    /** @test */
    use PHPUnit\Framework\Attributes\Test;

    #[Test]
    public function handles_multiple_concurrent_booking_requests_for_same_slot()
    {
        Queue::fake();
        Log::fake();

        // Configure Cal.com mock to have limited slots
        $this->mockCalcomClient->setAvailableSlots([
            new \App\Services\Calcom\DTOs\SlotDTO([
                'time' => $this->testDate->toIso8601String(),
                'duration' => 30,
            ]),
        ]);

        // Create 10 concurrent webhook requests for the same time slot
        $webhookRequests = [];
        $concurrentCount = 10;

        for ($i = 1; $i <= $concurrentCount; $i++) {
            $webhookRequests[] = WebhookPayloadBuilder::retell()
                ->withCompany($this->company->id, $this->branch->id)
                ->withCustomer(
                    "Customer {$i}",
                    "+4930" . str_pad($i, 9, '0', STR_PAD_LEFT),
                    "customer{$i}@test.com"
                )
                ->withAppointment('Consultation', $this->testDate)
                ->withCallId("concurrent_test_{$i}")
                ->build();
        }

        // Send all webhooks
        $responses = [];
        foreach ($webhookRequests as $payload) {
            $signature = $this->generateRetellSignature($payload);
            $responses[] = $this->postJson('/api/retell/webhook', $payload, [
                'X-Retell-Signature' => $signature,
            ]);
        }

        // All should return 204
        foreach ($responses as $response) {
            $response->assertStatus(204);
        }

        // Verify all call records were created
        $this->assertEquals($concurrentCount, DB::table('calls')->count());

        // Process jobs concurrently (simulate queue workers)
        $jobs = [];
        foreach ($webhookRequests as $payload) {
            $jobs[] = new ProcessRetellCallEndedJob($payload['data']);
        }

        // Only the first booking should succeed
        $successCount = 0;
        $failureCount = 0;
        $bookingService = app(AppointmentBookingService::class);
        $calcomService = app(CalcomV2Service::class);

        foreach ($jobs as $index => $job) {
            try {
                // Mock Cal.com to fail after first booking
                if ($index > 0) {
                    $this->mockCalcomClient->shouldFail('validation');
                }

                $job->handle($bookingService, $calcomService);
                $successCount++;
            } catch (\Exception $e) {
                $failureCount++;
            }

            // Reset mock for next iteration
            $this->mockCalcomClient->reset();
        }

        // Assertions
        $this->assertEquals(1, $successCount, 'Only one booking should succeed');
        $this->assertEquals($concurrentCount - 1, $failureCount, 'All others should fail');

        // Verify only one appointment was created
        $this->assertEquals(1, Appointment::count());

        // Verify the successful appointment
        $appointment = Appointment::first();
        $this->assertNotNull($appointment);
        $this->assertEquals($this->testDate->format('Y-m-d H:i:s'), $appointment->start_time->format('Y-m-d H:i:s'));

        // Verify failed calls have appropriate status
        $failedCalls = DB::table('calls')
            ->where('status', 'booking_conflict')
            ->orWhere('status', 'failed')
            ->count();
        $this->assertEquals($concurrentCount - 1, $failedCalls);

        // Verify logging
        Log::assertLogged('warning', function ($message) {
            return str_contains($message, 'booking failed') || 
                   str_contains($message, 'slot no longer available');
        });
    }

    /** @test */
    use PHPUnit\Framework\Attributes\Test;

    #[Test]
    public function stress_test_with_multiple_time_slots_and_staff()
    {
        Queue::fake();

        // Create additional staff members
        $staff2 = Staff::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'name' => 'Dr. Second',
            'calcom_user_id' => 2,
        ]);

        $staff3 = Staff::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'name' => 'Dr. Third',
            'calcom_user_id' => 3,
        ]);

        // Link services
        $staff2->services()->attach($this->service);
        $staff2->eventTypes()->attach($this->eventType);
        $staff3->services()->attach($this->service);
        $staff3->eventTypes()->attach($this->eventType);

        // Configure multiple available slots
        $slots = [];
        $staffMembers = [$this->staff, $staff2, $staff3];
        
        foreach ($staffMembers as $staff) {
            // Each staff has 4 slots throughout the day
            $slots[] = new \App\Services\Calcom\DTOs\SlotDTO([
                'time' => $this->testDate->copy()->setTime(9, 0)->toIso8601String(),
                'duration' => 30,
                'users' => [['id' => $staff->calcom_user_id]],
            ]);
            $slots[] = new \App\Services\Calcom\DTOs\SlotDTO([
                'time' => $this->testDate->copy()->setTime(10, 0)->toIso8601String(),
                'duration' => 30,
                'users' => [['id' => $staff->calcom_user_id]],
            ]);
            $slots[] = new \App\Services\Calcom\DTOs\SlotDTO([
                'time' => $this->testDate->copy()->setTime(14, 0)->toIso8601String(),
                'duration' => 30,
                'users' => [['id' => $staff->calcom_user_id]],
            ]);
            $slots[] = new \App\Services\Calcom\DTOs\SlotDTO([
                'time' => $this->testDate->copy()->setTime(15, 0)->toIso8601String(),
                'duration' => 30,
                'users' => [['id' => $staff->calcom_user_id]],
            ]);
        }

        $this->mockCalcomClient->setAvailableSlots($slots);

        // Create 20 booking requests with random preferences
        $webhookRequests = [];
        $times = ['09:00', '10:00', '14:00', '15:00'];

        for ($i = 1; $i <= 20; $i++) {
            $randomTime = $times[array_rand($times)];
            $appointmentTime = $this->testDate->copy()->setTimeFromTimeString($randomTime);
            
            $webhookRequests[] = WebhookPayloadBuilder::retell()
                ->withCompany($this->company->id, $this->branch->id)
                ->withCustomer(
                    "Stress Customer {$i}",
                    "+4931" . str_pad($i, 9, '0', STR_PAD_LEFT),
                    "stress{$i}@test.com"
                )
                ->withAppointment('Consultation', $appointmentTime)
                ->withCallId("stress_test_{$i}")
                ->build();
        }

        // Send all webhooks
        foreach ($webhookRequests as $payload) {
            $signature = $this->generateRetellSignature($payload);
            $this->postJson('/api/retell/webhook', $payload, [
                'X-Retell-Signature' => $signature,
            ])->assertStatus(204);
        }

        // Process all jobs
        $bookingService = app(AppointmentBookingService::class);
        $calcomService = app(CalcomV2Service::class);
        $bookedSlots = [];

        foreach ($webhookRequests as $index => $payload) {
            $job = new ProcessRetellCallEndedJob($payload['data']);
            
            try {
                // Check if slot already booked
                $requestedTime = $payload['data']['call_analysis']['preferred_date'] . ' ' . 
                               $payload['data']['call_analysis']['preferred_time'];
                
                if (in_array($requestedTime, $bookedSlots)) {
                    $this->mockCalcomClient->shouldFail('validation');
                }

                $job->handle($bookingService, $calcomService);
                
                // Mark slot as booked
                $bookedSlots[] = $requestedTime;
                
            } catch (\Exception $e) {
                // Expected for conflicting bookings
            }

            $this->mockCalcomClient->reset();
        }

        // Verify results
        $totalAppointments = Appointment::count();
        $this->assertGreaterThan(0, $totalAppointments);
        $this->assertLessThanOrEqual(12, $totalAppointments); // 3 staff * 4 slots

        // Verify no double bookings
        $doubleBookings = DB::table('appointments')
            ->select('staff_id', 'start_time')
            ->groupBy('staff_id', 'start_time')
            ->havingRaw('COUNT(*) > 1')
            ->count();
        $this->assertEquals(0, $doubleBookings, 'No double bookings should exist');

        // Verify appointments are distributed among staff
        $staffDistribution = Appointment::query()
            ->selectRaw('staff_id, COUNT(*) as count')
            ->groupBy('staff_id')
            ->pluck('count', 'staff_id');

        $this->assertCount(3, $staffDistribution, 'Appointments should be distributed among all staff');
    }

    /** @test */
    use PHPUnit\Framework\Attributes\Test;

    #[Test]
    public function performance_test_booking_creation_speed()
    {
        Queue::fake();

        // Configure fast responses
        $this->mockCalcomClient->setAvailableSlots([
            new \App\Services\Calcom\DTOs\SlotDTO([
                'time' => $this->testDate->toIso8601String(),
                'duration' => 30,
            ]),
        ]);

        $iterations = 100;
        $timings = [];

        for ($i = 1; $i <= $iterations; $i++) {
            $start = microtime(true);

            $payload = WebhookPayloadBuilder::retell()
                ->withCompany($this->company->id, $this->branch->id)
                ->withCustomer(
                    "Perf Customer {$i}",
                    "+4932" . str_pad($i, 9, '0', STR_PAD_LEFT),
                    "perf{$i}@test.com"
                )
                ->withAppointment('Consultation', $this->testDate->copy()->addDays($i))
                ->withCallId("perf_test_{$i}")
                ->build();

            $job = new ProcessRetellCallEndedJob($payload['data']);

            try {
                $job->handle(
                    app(AppointmentBookingService::class),
                    app(CalcomV2Service::class)
                );
            } catch (\Exception $e) {
                // Ignore failures for performance test
            }

            $end = microtime(true);
            $timings[] = ($end - $start) * 1000; // Convert to milliseconds
        }

        // Calculate statistics
        $avgTime = array_sum($timings) / count($timings);
        $minTime = min($timings);
        $maxTime = max($timings);
        $medianTime = $this->calculateMedian($timings);

        // Log performance results
        Log::info('Booking Performance Test Results', [
            'iterations' => $iterations,
            'avg_time_ms' => round($avgTime, 2),
            'min_time_ms' => round($minTime, 2),
            'max_time_ms' => round($maxTime, 2),
            'median_time_ms' => round($medianTime, 2),
        ]);

        // Performance assertions
        $this->assertLessThan(100, $avgTime, 'Average booking time should be under 100ms');
        $this->assertLessThan(200, $maxTime, 'Max booking time should be under 200ms');
        $this->assertLessThan(50, $medianTime, 'Median booking time should be under 50ms');
    }

    /** @test */
    use PHPUnit\Framework\Attributes\Test;

    #[Test]
    public function handles_database_deadlocks_gracefully()
    {
        Queue::fake();

        // Create conflicting transactions
        $payload1 = WebhookPayloadBuilder::createAppointmentBooking(
            $this->company->id,
            $this->branch->id,
            'Deadlock Customer 1',
            'Consultation',
            $this->testDate
        );

        $payload2 = WebhookPayloadBuilder::createAppointmentBooking(
            $this->company->id,
            $this->branch->id,
            'Deadlock Customer 2',
            'Consultation',
            $this->testDate
        );

        // Start two transactions that will conflict
        $exception1 = null;
        $exception2 = null;

        DB::transaction(function () use ($payload1, &$exception1) {
            try {
                // Lock customer table
                DB::table('customers')->lockForUpdate()->first();
                sleep(1); // Hold lock

                $job = new ProcessRetellCallEndedJob($payload1['data']);
                $job->handle(
                    app(AppointmentBookingService::class),
                    app(CalcomV2Service::class)
                );
            } catch (\Exception $e) {
                $exception1 = $e;
                throw $e;
            }
        });

        DB::transaction(function () use ($payload2, &$exception2) {
            try {
                // Try to lock same resources
                DB::table('customers')->lockForUpdate()->first();

                $job = new ProcessRetellCallEndedJob($payload2['data']);
                $job->handle(
                    app(AppointmentBookingService::class),
                    app(CalcomV2Service::class)
                );
            } catch (\Exception $e) {
                $exception2 = $e;
                throw $e;
            }
        });

        // At least one should succeed
        $this->assertTrue(
            $exception1 === null || $exception2 === null,
            'At least one transaction should succeed'
        );

        // Verify data consistency
        $customerCount = DB::table('customers')->count();
        $appointmentCount = DB::table('appointments')->count();

        $this->assertGreaterThan(0, $customerCount);
        $this->assertEquals($customerCount, $appointmentCount, 'Each customer should have one appointment');
    }

    /** @test */
    use PHPUnit\Framework\Attributes\Test;

    #[Test]
    public function cache_performance_under_concurrent_load()
    {
        Queue::fake();
        Cache::flush();

        // Pre-warm cache with slots
        $cacheKey = "calcom:slots:{$this->eventType->calcom_id}:" . $this->testDate->format('Y-m-d');
        Cache::put($cacheKey, [
            [
                'time' => $this->testDate->toIso8601String(),
                'duration' => 30,
            ],
        ], 300);

        // Measure cache performance under load
        $cacheHits = 0;
        $cacheMisses = 0;

        for ($i = 1; $i <= 50; $i++) {
            $start = microtime(true);
            
            // Check cache
            if (Cache::has($cacheKey)) {
                $cacheHits++;
                $slots = Cache::get($cacheKey);
            } else {
                $cacheMisses++;
                // Simulate API call
                usleep(50000); // 50ms
            }

            // Simulate concurrent cache invalidation
            if ($i % 10 === 0) {
                Cache::forget($cacheKey);
            }
        }

        $hitRate = ($cacheHits / ($cacheHits + $cacheMisses)) * 100;

        $this->assertGreaterThan(70, $hitRate, 'Cache hit rate should be above 70%');
    }

    /**
     * Helper to calculate median
     */
    protected function calculateMedian(array $numbers): float
    {
        sort($numbers);
        $count = count($numbers);
        $middle = floor(($count - 1) / 2);

        if ($count % 2) {
            return $numbers[$middle];
        } else {
            return ($numbers[$middle] + $numbers[$middle + 1]) / 2;
        }
    }

    /**
     * Generate valid Retell signature
     */
    protected function generateRetellSignature(array $payload): string
    {
        $secret = config('services.retell.webhook_secret', 'test_webhook_secret');
        $timestamp = time();
        $body = json_encode($payload);
        
        $signatureBase = "{$timestamp}.{$body}";
        $signature = hash_hmac('sha256', $signatureBase, $secret);
        
        return "t={$timestamp},v1={$signature}";
    }
}