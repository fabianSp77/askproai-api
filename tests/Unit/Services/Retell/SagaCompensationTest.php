<?php

namespace Tests\Unit\Services\Retell;

use Tests\TestCase;
use App\Models\Call;
use App\Models\Customer;
use App\Models\Service;
use App\Models\Appointment;
use App\Services\Retell\AppointmentCreationService;
use App\Services\CalcomService;
use App\Jobs\OrphanedBookingCleanupJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Client\Response;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Mockery;

/**
 * SAGA Compensation Pattern Tests
 *
 * Tests for Phase 1 (P0-1): SAGA Pattern Implementation
 * Validates that Cal.com bookings are properly rolled back when DB save fails
 *
 * CRITICAL: These tests validate the fix for 67% booking failure rate
 */
class SagaCompensationTest extends TestCase
{
    use RefreshDatabase;

    private AppointmentCreationService $service;
    private CalcomService $calcomService;
    private Customer $customer;
    private Service $testService;
    private Call $call;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock Cal.com service
        $this->calcomService = Mockery::mock(CalcomService::class);
        $this->app->instance(CalcomService::class, $this->calcomService);

        // Create test data
        $this->customer = Customer::factory()->create([
            'name' => 'Test Customer',
            'email' => 'test@example.com',
            'phone' => '+491234567890',
            'company_id' => 15
        ]);

        $this->testService = Service::factory()->create([
            'name' => 'Test Service',
            'company_id' => 15,
            'calcom_event_type_id' => 123
        ]);

        $this->call = Call::factory()->create([
            'customer_id' => $this->customer->id,
            'company_id' => 15
        ]);

        // Get service instance
        $this->service = app(AppointmentCreationService::class);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test: DB save fails → SAGA compensation cancels Cal.com booking
     *
     * SCENARIO: Happy path - compensation successful
     */
    public function test_saga_compensation_cancels_calcom_booking_on_db_failure(): void
    {
        // ARRANGE: Mock successful Cal.com booking
        $this->calcomService->shouldReceive('createBooking')
            ->once()
            ->andReturn(new Response(new GuzzleResponse(200, [], json_encode([
                'data' => [
                    'id' => 'test_booking_123',
                    'start' => '2025-11-06T14:00:00+01:00',
                    'status' => 'accepted',
                    'createdAt' => now()->toIso8601String()
                ]
            ]))));

        // ARRANGE: Mock successful Cal.com cancellation (SAGA compensation)
        $this->calcomService->shouldReceive('cancelBooking')
            ->once()
            ->with('test_booking_123', Mockery::pattern('/Automatic rollback/'))
            ->andReturn(new Response(new GuzzleResponse(200, [], '{}')));

        // ARRANGE: Force DB constraint violation to simulate failure
        // We'll manually trigger the SAGA flow by testing createLocalRecord with invalid data
        $bookingDetails = [
            'starts_at' => '2025-11-06 14:00:00',
            'ends_at' => '2025-11-06 15:00:00',
            'service' => 'Test Service',
            'duration_minutes' => 60
        ];

        // ACT & ASSERT: DB save should fail, SAGA compensation should succeed
        $this->expectException(\Exception::class);

        // Temporarily make appointments table read-only to force failure
        DB::statement('LOCK TABLES appointments WRITE');

        try {
            $this->service->createLocalRecord(
                $this->customer,
                $this->testService,
                $bookingDetails,
                'test_booking_123', // Cal.com booking ID that needs rollback
                $this->call
            );
        } finally {
            DB::statement('UNLOCK TABLES');
        }

        // VERIFY: Cal.com cancellation was called (mocked expectation)
        $this->calcomService->shouldHaveReceived('cancelBooking');
    }

    /**
     * Test: SAGA compensation fails → OrphanedBookingCleanupJob dispatched
     *
     * SCENARIO: Worst case - compensation also fails, need async retry
     */
    public function test_orphaned_booking_cleanup_job_dispatched_when_saga_fails(): void
    {
        Queue::fake();

        // ARRANGE: Mock successful Cal.com booking
        $this->calcomService->shouldReceive('createBooking')
            ->once()
            ->andReturn(new Response(new GuzzleResponse(200, [], json_encode([
                'data' => [
                    'id' => 'orphaned_booking_456',
                    'start' => '2025-11-06T14:00:00+01:00',
                    'status' => 'accepted',
                    'createdAt' => now()->toIso8601String()
                ]
            ]))));

        // ARRANGE: Mock FAILED Cal.com cancellation (SAGA compensation fails!)
        $this->calcomService->shouldReceive('cancelBooking')
            ->once()
            ->andReturn(new Response(new GuzzleResponse(500, [], json_encode([
                'error' => 'Cal.com server error'
            ]))));

        // ARRANGE: Force DB failure
        $bookingDetails = [
            'starts_at' => '2025-11-06 14:00:00',
            'ends_at' => '2025-11-06 15:00:00',
            'service' => 'Test Service',
            'duration_minutes' => 60
        ];

        // ACT & ASSERT: Should throw exception and dispatch cleanup job
        $this->expectException(\Exception::class);

        DB::statement('LOCK TABLES appointments WRITE');

        try {
            $this->service->createLocalRecord(
                $this->customer,
                $this->testService,
                $bookingDetails,
                'orphaned_booking_456',
                $this->call
            );
        } finally {
            DB::statement('UNLOCK TABLES');
        }

        // VERIFY: OrphanedBookingCleanupJob was dispatched
        Queue::assertPushed(OrphanedBookingCleanupJob::class, function ($job) {
            return $job->calcomBookingId === 'orphaned_booking_456'
                && $job->metadata['failure_reason'] === 'saga_compensation_failed';
        });
    }

    /**
     * Test: SAGA compensation exception → OrphanedBookingCleanupJob dispatched
     *
     * SCENARIO: Cancellation throws exception (network error, timeout)
     */
    public function test_orphaned_booking_cleanup_job_dispatched_on_compensation_exception(): void
    {
        Queue::fake();

        // ARRANGE: Mock successful Cal.com booking
        $this->calcomService->shouldReceive('createBooking')
            ->once()
            ->andReturn(new Response(new GuzzleResponse(200, [], json_encode([
                'data' => [
                    'id' => 'exception_booking_789',
                    'start' => '2025-11-06T14:00:00+01:00',
                    'status' => 'accepted',
                    'createdAt' => now()->toIso8601String()
                ]
            ]))));

        // ARRANGE: Mock Cal.com cancellation throwing exception
        $this->calcomService->shouldReceive('cancelBooking')
            ->once()
            ->andThrow(new \Exception('Network timeout during cancellation'));

        // ARRANGE: Force DB failure
        $bookingDetails = [
            'starts_at' => '2025-11-06 14:00:00',
            'ends_at' => '2025-11-06 15:00:00',
            'service' => 'Test Service',
            'duration_minutes' => 60
        ];

        // ACT & ASSERT
        $this->expectException(\Exception::class);

        DB::statement('LOCK TABLES appointments WRITE');

        try {
            $this->service->createLocalRecord(
                $this->customer,
                $this->testService,
                $bookingDetails,
                'exception_booking_789',
                $this->call
            );
        } finally {
            DB::statement('UNLOCK TABLES');
        }

        // VERIFY: OrphanedBookingCleanupJob was dispatched with exception details
        Queue::assertPushed(OrphanedBookingCleanupJob::class, function ($job) {
            return $job->calcomBookingId === 'exception_booking_789'
                && $job->metadata['failure_reason'] === 'saga_compensation_exception'
                && isset($job->metadata['cancel_exception']);
        });
    }

    /**
     * Test: SQL error diagnostics work correctly
     *
     * Validates that different SQL error codes are properly diagnosed
     */
    public function test_sql_error_diagnostics(): void
    {
        // This test validates the diagnoseSqlError() method
        // We'll test by checking log output for specific error patterns

        Log::shouldReceive('error')
            ->once()
            ->withArgs(function ($message, $context) {
                // Verify log contains diagnostic information
                return $message === '❌ Failed to save appointment record to database'
                    && isset($context['diagnosis'])
                    && isset($context['error_class']);
            });

        // ARRANGE: Force duplicate key error (error code 1062)
        // Create appointment with same calcom_booking_id
        Appointment::factory()->create([
            'customer_id' => $this->customer->id,
            'service_id' => $this->testService->id,
            'company_id' => 15,
            'calcom_v2_booking_id' => 'duplicate_booking_id',
            'starts_at' => '2025-11-06 14:00:00',
            'ends_at' => '2025-11-06 15:00:00',
            'status' => 'scheduled'
        ]);

        // Mock Cal.com cancellation for SAGA compensation
        $this->calcomService->shouldReceive('cancelBooking')
            ->once()
            ->andReturn(new Response(new GuzzleResponse(200, [], '{}')));

        // ACT: Try to create duplicate
        $this->expectException(\Exception::class);

        $bookingDetails = [
            'starts_at' => '2025-11-06 14:00:00',
            'ends_at' => '2025-11-06 15:00:00',
            'service' => 'Test Service',
            'duration_minutes' => 60
        ];

        $this->service->createLocalRecord(
            $this->customer,
            $this->testService,
            $bookingDetails,
            'duplicate_booking_id', // Same ID → should trigger duplicate error
            $this->call
        );

        // Log assertion happens via shouldReceive mock
    }

    /**
     * Test: No SAGA compensation when no Cal.com booking ID
     *
     * SCENARIO: Local-only appointment (no Cal.com integration)
     */
    public function test_no_saga_compensation_when_no_calcom_booking_id(): void
    {
        // ARRANGE: No Cal.com booking ID
        $bookingDetails = [
            'starts_at' => '2025-11-06 14:00:00',
            'ends_at' => '2025-11-06 15:00:00',
            'service' => 'Test Service',
            'duration_minutes' => 60
        ];

        // Cal.com cancellation should NOT be called
        $this->calcomService->shouldNotReceive('cancelBooking');

        // ACT & ASSERT: Force DB failure
        $this->expectException(\Exception::class);

        DB::statement('LOCK TABLES appointments WRITE');

        try {
            $this->service->createLocalRecord(
                $this->customer,
                $this->testService,
                $bookingDetails,
                null, // No Cal.com booking ID
                $this->call
            );
        } finally {
            DB::statement('UNLOCK TABLES');
        }

        // VERIFY: No Cal.com cancellation attempted
        $this->calcomService->shouldNotHaveReceived('cancelBooking');
    }

    /**
     * Test: Successful booking flow (no SAGA needed)
     *
     * SCENARIO: Happy path - both Cal.com and DB succeed
     */
    public function test_successful_booking_no_saga_compensation_needed(): void
    {
        // ARRANGE: Mock successful Cal.com booking
        $this->calcomService->shouldReceive('createBooking')
            ->never(); // Not testing full flow here, just createLocalRecord

        // ARRANGE: Normal booking details
        $bookingDetails = [
            'starts_at' => '2025-11-06 14:00:00',
            'ends_at' => '2025-11-06 15:00:00',
            'service' => 'Test Service',
            'duration_minutes' => 60
        ];

        // ACT: Create appointment (should succeed)
        $appointment = $this->service->createLocalRecord(
            $this->customer,
            $this->testService,
            $bookingDetails,
            'successful_booking_xyz',
            $this->call
        );

        // ASSERT: Appointment created successfully
        $this->assertInstanceOf(Appointment::class, $appointment);
        $this->assertEquals('successful_booking_xyz', $appointment->calcom_v2_booking_id);
        $this->assertEquals('scheduled', $appointment->status);

        // VERIFY: No cancellation attempted (success path)
        $this->calcomService->shouldNotHaveReceived('cancelBooking');
    }
}
