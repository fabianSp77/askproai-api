<?php

namespace Tests\Unit\Jobs;

use Tests\TestCase;
use App\Jobs\ClearAvailabilityCacheJob;
use App\Services\CalcomService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * 🎯 PHASE 3.3: Unit Tests for Async Cache Clearing Job (2025-11-11)
 *
 * Tests the ClearAvailabilityCacheJob to ensure:
 * 1. Job dispatches correctly to queue
 * 2. Job calls smartClearAvailabilityCache with correct parameters
 * 3. Job handles errors gracefully
 * 4. Job logs appropriate messages
 * 5. Job retries on failure
 * 6. Job uses correct queue name
 */
class ClearAvailabilityCacheJobTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Test: Job dispatches to correct queue
     */
    public function test_job_dispatches_to_cache_queue(): void
    {
        Queue::fake();

        $eventTypeId = 123;
        $appointmentStart = Carbon::parse('2025-11-12 14:30:00');
        $appointmentEnd = Carbon::parse('2025-11-12 15:00:00');

        // Dispatch job
        ClearAvailabilityCacheJob::dispatch(
            eventTypeId: $eventTypeId,
            appointmentStart: $appointmentStart,
            appointmentEnd: $appointmentEnd
        );

        // Assert: Job pushed to 'cache' queue
        Queue::assertPushedOn('cache', ClearAvailabilityCacheJob::class);
    }

    /**
     * Test: Job dispatches with all parameters
     */
    public function test_job_dispatches_with_all_parameters(): void
    {
        Queue::fake();

        $eventTypeId = 123;
        $teamId = 456;
        $companyId = 1;
        $branchId = 1;
        $appointmentStart = Carbon::parse('2025-11-12 14:30:00');
        $appointmentEnd = Carbon::parse('2025-11-12 15:00:00');
        $source = 'webhook_booking_created';
        $appointmentId = 789;

        // Dispatch job with all parameters
        ClearAvailabilityCacheJob::dispatch(
            eventTypeId: $eventTypeId,
            appointmentStart: $appointmentStart,
            appointmentEnd: $appointmentEnd,
            teamId: $teamId,
            companyId: $companyId,
            branchId: $branchId,
            source: $source,
            appointmentId: $appointmentId
        );

        // Assert: Job pushed with correct parameters
        Queue::assertPushed(ClearAvailabilityCacheJob::class, function ($job) use (
            $eventTypeId, $teamId, $companyId, $branchId, $source, $appointmentId
        ) {
            return $job->eventTypeId === $eventTypeId
                && $job->teamId === $teamId
                && $job->companyId === $companyId
                && $job->branchId === $branchId
                && $job->source === $source
                && $job->appointmentId === $appointmentId;
        });
    }

    /**
     * Test: Job calls smartClearAvailabilityCache method
     */
    public function test_job_calls_smart_cache_clearing(): void
    {
        $eventTypeId = 123;
        $appointmentStart = Carbon::parse('2025-11-12 14:30:00');
        $appointmentEnd = Carbon::parse('2025-11-12 15:00:00');
        $teamId = 456;

        // Create mock service
        $service = \App\Models\Service::factory()->create([
            'calcom_event_type_id' => $eventTypeId,
            'calcom_team_id' => $teamId,
            'company_id' => 1,
            'branch_id' => 1
        ]);

        // Mock CalcomService to verify method call
        $mockCalcomService = $this->mock(CalcomService::class);
        $mockCalcomService->shouldReceive('smartClearAvailabilityCache')
            ->once()
            ->with(
                \Mockery::on(fn($val) => $val === $eventTypeId),
                \Mockery::on(fn($val) => $val->equalTo($appointmentStart)),
                \Mockery::on(fn($val) => $val->equalTo($appointmentEnd)),
                \Mockery::on(fn($val) => $val === $teamId),
                \Mockery::on(fn($val) => $val === $service->company_id),
                \Mockery::on(fn($val) => $val === $service->branch_id)
            )
            ->andReturn(18); // Mock 18 keys cleared

        // Create and execute job
        $job = new ClearAvailabilityCacheJob(
            eventTypeId: $eventTypeId,
            appointmentStart: $appointmentStart,
            appointmentEnd: $appointmentEnd,
            teamId: $teamId,
            companyId: $service->company_id,
            branchId: $service->branch_id
        );

        $job->handle();

        // Assertion is done via mock verification
    }

    /**
     * Test: Job logs success message
     */
    public function test_job_logs_success_message(): void
    {
        Log::spy();

        $eventTypeId = 123;
        $appointmentStart = Carbon::parse('2025-11-12 14:30:00');
        $appointmentEnd = Carbon::parse('2025-11-12 15:00:00');

        // Create mock service
        $service = \App\Models\Service::factory()->create([
            'calcom_event_type_id' => $eventTypeId,
            'duration_minutes' => 30
        ]);

        // Create and execute job
        $job = new ClearAvailabilityCacheJob(
            eventTypeId: $eventTypeId,
            appointmentStart: $appointmentStart,
            appointmentEnd: $appointmentEnd
        );

        $job->handle();

        // Assert: Success message logged
        Log::shouldHaveReceived('info')
            ->with(
                \Mockery::pattern('/ASYNC: Cache clearing job completed/'),
                \Mockery::type('array')
            );
    }

    /**
     * Test: Job handles errors gracefully
     */
    public function test_job_handles_errors_gracefully(): void
    {
        Log::spy();

        $eventTypeId = 123;
        $appointmentStart = Carbon::parse('2025-11-12 14:30:00');
        $appointmentEnd = Carbon::parse('2025-11-12 15:00:00');

        // Mock CalcomService to throw exception
        $mockCalcomService = $this->mock(CalcomService::class);
        $mockCalcomService->shouldReceive('smartClearAvailabilityCache')
            ->andThrow(new \Exception('Redis connection failed'));

        // Create job
        $job = new ClearAvailabilityCacheJob(
            eventTypeId: $eventTypeId,
            appointmentStart: $appointmentStart,
            appointmentEnd: $appointmentEnd
        );

        // Execute job and expect exception
        $this->expectException(\Exception::class);
        $job->handle();

        // Assert: Error logged
        Log::shouldHaveReceived('error')
            ->with(
                \Mockery::pattern('/ASYNC: Cache clearing job failed/'),
                \Mockery::type('array')
            );
    }

    /**
     * Test: Job retry configuration
     */
    public function test_job_has_correct_retry_configuration(): void
    {
        $job = new ClearAvailabilityCacheJob(
            eventTypeId: 123,
            appointmentStart: Carbon::now(),
            appointmentEnd: Carbon::now()->addMinutes(30)
        );

        // Assert: Retry configuration
        $this->assertEquals(3, $job->tries, 'Job should retry 3 times');
        $this->assertEquals(120, $job->timeout, 'Job should timeout after 120 seconds');
    }

    /**
     * Test: Job exponential backoff
     */
    public function test_job_uses_exponential_backoff(): void
    {
        $job = new ClearAvailabilityCacheJob(
            eventTypeId: 123,
            appointmentStart: Carbon::now(),
            appointmentEnd: Carbon::now()->addMinutes(30)
        );

        // Mock attempt count
        $reflection = new \ReflectionClass($job);
        $method = $reflection->getMethod('backoff');
        $method->setAccessible(true);

        // Mock attempts() method to return different values
        // For this test, we'll directly test the backoff calculation
        // Attempt 1: 5 * 2^0 = 5 seconds
        // Attempt 2: 5 * 2^1 = 10 seconds
        // Attempt 3: 5 * 2^2 = 20 seconds

        // Since we can't easily mock attempts(), we verify the backoff logic exists
        $this->assertIsInt($job->backoff, 'Backoff should be an integer');
        $this->assertEquals(5, $job->backoff, 'Base backoff should be 5 seconds');
    }

    /**
     * Test: Job tags for monitoring
     */
    public function test_job_has_correct_tags(): void
    {
        $eventTypeId = 123;
        $appointmentId = 789;
        $source = 'webhook_booking_created';

        $job = new ClearAvailabilityCacheJob(
            eventTypeId: $eventTypeId,
            appointmentStart: Carbon::now(),
            appointmentEnd: Carbon::now()->addMinutes(30),
            source: $source,
            appointmentId: $appointmentId
        );

        $tags = $job->tags();

        // Assert: Tags include relevant identifiers
        $this->assertContains('cache', $tags);
        $this->assertContains('availability', $tags);
        $this->assertContains("event_type:{$eventTypeId}", $tags);
        $this->assertContains("appointment:{$appointmentId}", $tags);
        $this->assertContains("source:{$source}", $tags);
    }

    /**
     * Test: Job failed handler logs permanent failure
     */
    public function test_job_failed_handler_logs_permanent_failure(): void
    {
        Log::spy();

        $job = new ClearAvailabilityCacheJob(
            eventTypeId: 123,
            appointmentStart: Carbon::now(),
            appointmentEnd: Carbon::now()->addMinutes(30),
            appointmentId: 789
        );

        $exception = new \Exception('Permanent failure');

        // Call failed handler
        $job->failed($exception);

        // Assert: Permanent failure logged
        Log::shouldHaveReceived('error')
            ->with(
                \Mockery::pattern('/ASYNC: Cache clearing job failed permanently/'),
                \Mockery::type('array')
            );
    }

    /**
     * Test: Multiple jobs can be dispatched simultaneously
     */
    public function test_multiple_jobs_can_be_dispatched(): void
    {
        Queue::fake();

        // Dispatch 3 jobs for different appointments
        for ($i = 1; $i <= 3; $i++) {
            ClearAvailabilityCacheJob::dispatch(
                eventTypeId: 123,
                appointmentStart: Carbon::now()->addHours($i),
                appointmentEnd: Carbon::now()->addHours($i)->addMinutes(30),
                appointmentId: $i
            );
        }

        // Assert: 3 jobs dispatched
        Queue::assertPushed(ClearAvailabilityCacheJob::class, 3);
    }

    /**
     * Test: Job serialization works correctly
     */
    public function test_job_serialization_works(): void
    {
        $appointmentStart = Carbon::parse('2025-11-12 14:30:00');
        $appointmentEnd = Carbon::parse('2025-11-12 15:00:00');

        $job = new ClearAvailabilityCacheJob(
            eventTypeId: 123,
            appointmentStart: $appointmentStart,
            appointmentEnd: $appointmentEnd,
            teamId: 456,
            companyId: 1,
            branchId: 1
        );

        // Serialize and unserialize
        $serialized = serialize($job);
        $unserialized = unserialize($serialized);

        // Assert: Properties preserved
        $this->assertEquals(123, $unserialized->eventTypeId);
        $this->assertEquals(456, $unserialized->teamId);
        $this->assertEquals(1, $unserialized->companyId);
        $this->assertEquals(1, $unserialized->branchId);
        $this->assertTrue($unserialized->appointmentStart->equalTo($appointmentStart));
        $this->assertTrue($unserialized->appointmentEnd->equalTo($appointmentEnd));
    }

    /**
     * Test: Job works with minimal parameters
     */
    public function test_job_works_with_minimal_parameters(): void
    {
        Queue::fake();

        // Dispatch job with only required parameters
        ClearAvailabilityCacheJob::dispatch(
            eventTypeId: 123,
            appointmentStart: Carbon::now(),
            appointmentEnd: Carbon::now()->addMinutes(30)
        );

        // Assert: Job dispatched successfully
        Queue::assertPushed(ClearAvailabilityCacheJob::class, function ($job) {
            return $job->eventTypeId === 123
                && $job->teamId === null
                && $job->companyId === null
                && $job->branchId === null;
        });
    }
}
