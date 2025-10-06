<?php

namespace Tests\Unit\Services\Retell;

use Tests\TestCase;
use App\Models\Call;
use App\Models\Customer;
use App\Models\Service;
use App\Models\Appointment;
use App\Models\Company;
use App\Models\Branch;
use App\Services\Retell\AppointmentCreationService;
use App\Services\Retell\CallLifecycleService;
use App\Services\Retell\ServiceSelectionService;
use App\Services\AppointmentAlternativeFinder;
use App\Services\NestedBookingManager;
use App\Services\CalcomService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;

/**
 * Duplicate Booking Prevention Tests
 *
 * Tests for 4-layer defense system against Cal.com idempotency duplicates:
 * - Layer 1: Booking freshness validation (30-second threshold)
 * - Layer 2: Metadata call_id validation
 * - Layer 3: Database duplicate check before insert
 * - Layer 4: Database UNIQUE constraint (integration test)
 */
class DuplicateBookingPreventionTest extends TestCase
{
    use RefreshDatabase;

    private AppointmentCreationService $service;
    private CallLifecycleService $callLifecycle;
    private ServiceSelectionService $serviceSelector;
    private AppointmentAlternativeFinder $alternativeFinder;
    private NestedBookingManager $nestedBookingManager;
    private CalcomService $calcomService;

    protected function setUp(): void
    {
        parent::setUp();

        // Create service dependencies
        $this->callLifecycle = $this->createMock(CallLifecycleService::class);
        $this->serviceSelector = $this->createMock(ServiceSelectionService::class);
        $this->alternativeFinder = $this->createMock(AppointmentAlternativeFinder::class);
        $this->nestedBookingManager = $this->createMock(NestedBookingManager::class);
        $this->calcomService = $this->createMock(CalcomService::class);

        // Create service instance
        $this->service = new AppointmentCreationService(
            $this->callLifecycle,
            $this->serviceSelector,
            $this->alternativeFinder,
            $this->nestedBookingManager,
            $this->calcomService
        );
    }

    // ==========================================
    // LAYER 1: Booking Freshness Validation Tests
    // ==========================================

    /** @test */
    public function layer1_accepts_fresh_booking_created_5_seconds_ago()
    {
        [$customer, $service, $call] = $this->setupTestData();

        // Mock fresh booking response (5 seconds old - FRESH)
        $freshBooking = $this->mockCalcomResponse(
            bookingId: 'booking_123',
            createdAt: now()->subSeconds(5),
            callId: $call->retell_call_id
        );

        $this->calcomService->expects($this->once())
            ->method('createBooking')
            ->willReturn($freshBooking);

        $result = $this->service->bookInCalcom(
            $customer,
            $service,
            Carbon::parse('2025-10-10 08:00:00'),
            45,
            $call
        );

        // Fresh booking should be ACCEPTED
        $this->assertNotNull($result);
        $this->assertEquals('booking_123', $result['booking_id']);
    }

    /** @test */
    public function layer1_accepts_booking_exactly_at_30_second_boundary()
    {
        [$customer, $service, $call] = $this->setupTestData();

        // Booking exactly 30 seconds old (boundary condition)
        $boundaryBooking = $this->mockCalcomResponse(
            bookingId: 'booking_boundary',
            createdAt: now()->subSeconds(30),
            callId: $call->retell_call_id
        );

        $this->calcomService->expects($this->once())
            ->method('createBooking')
            ->willReturn($boundaryBooking);

        $result = $this->service->bookInCalcom(
            $customer,
            $service,
            Carbon::parse('2025-10-10 08:00:00'),
            45,
            $call
        );

        // Exactly 30 seconds should still be accepted (< not <=)
        $this->assertNotNull($result);
    }

    /** @test */
    public function layer1_rejects_stale_booking_created_35_seconds_ago()
    {
        [$customer, $service, $call] = $this->setupTestData();

        // Mock stale booking response (35 seconds old - STALE)
        $staleBooking = $this->mockCalcomResponse(
            bookingId: 'stale_booking_456',
            createdAt: now()->subSeconds(35),
            callId: 'different_call_id' // Also has wrong call_id
        );

        $this->calcomService->expects($this->once())
            ->method('createBooking')
            ->willReturn($staleBooking);

        $result = $this->service->bookInCalcom(
            $customer,
            $service,
            Carbon::parse('2025-10-10 08:00:00'),
            45,
            $call
        );

        // Stale booking should be REJECTED
        $this->assertNull($result);
    }

    /** @test */
    public function layer1_rejects_booking_created_2_minutes_ago()
    {
        [$customer, $service, $call] = $this->setupTestData();

        // Very old booking (2 minutes = 120 seconds)
        $veryStaleBooking = $this->mockCalcomResponse(
            bookingId: 'very_old_booking',
            createdAt: now()->subMinutes(2),
            callId: 'old_call_id'
        );

        $this->calcomService->expects($this->once())
            ->method('createBooking')
            ->willReturn($veryStaleBooking);

        $result = $this->service->bookInCalcom(
            $customer,
            $service,
            Carbon::parse('2025-10-10 08:00:00'),
            45,
            $call
        );

        $this->assertNull($result);
    }

    /** @test */
    public function layer1_handles_missing_created_at_timestamp_gracefully()
    {
        [$customer, $service, $call] = $this->setupTestData();

        // Response without createdAt timestamp
        $bookingWithoutTimestamp = [
            'status' => 200,
            'data' => [
                'id' => 'booking_no_timestamp',
                // Missing: createdAt
                'metadata' => ['call_id' => $call->retell_call_id]
            ]
        ];

        $this->calcomService->expects($this->once())
            ->method('createBooking')
            ->willReturn($bookingWithoutTimestamp);

        $result = $this->service->bookInCalcom(
            $customer,
            $service,
            Carbon::parse('2025-10-10 08:00:00'),
            45,
            $call
        );

        // Should not crash, but also might not validate properly
        // Implementation decision: accept when timestamp missing (no validation possible)
        $this->assertNotNull($result);
    }

    // ==========================================
    // LAYER 2: Metadata call_id Validation Tests
    // ==========================================

    /** @test */
    public function layer2_accepts_booking_with_matching_call_id()
    {
        [$customer, $service, $call] = $this->setupTestData();

        // Fresh booking with MATCHING call_id
        $matchingCallIdBooking = $this->mockCalcomResponse(
            bookingId: 'booking_match',
            createdAt: now()->subSeconds(5),
            callId: $call->retell_call_id // MATCHES current call
        );

        $this->calcomService->expects($this->once())
            ->method('createBooking')
            ->willReturn($matchingCallIdBooking);

        $result = $this->service->bookInCalcom(
            $customer,
            $service,
            Carbon::parse('2025-10-10 08:00:00'),
            45,
            $call
        );

        $this->assertNotNull($result);
        $this->assertEquals('booking_match', $result['booking_id']);
    }

    /** @test */
    public function layer2_rejects_booking_with_mismatched_call_id()
    {
        [$customer, $service, $call] = $this->setupTestData();

        // Fresh booking but WRONG call_id
        $mismatchedCallIdBooking = $this->mockCalcomResponse(
            bookingId: 'booking_mismatch',
            createdAt: now()->subSeconds(5), // Fresh timing (passes Layer 1)
            callId: 'wrong_call_id_xyz' // MISMATCHED call_id
        );

        $this->calcomService->expects($this->once())
            ->method('createBooking')
            ->willReturn($mismatchedCallIdBooking);

        $result = $this->service->bookInCalcom(
            $customer,
            $service,
            Carbon::parse('2025-10-10 08:00:00'),
            45,
            $call
        );

        // Should be REJECTED by Layer 2 (call_id mismatch)
        $this->assertNull($result);
    }

    /** @test */
    public function layer2_handles_missing_metadata_call_id_gracefully()
    {
        [$customer, $service, $call] = $this->setupTestData();

        // Booking without metadata.call_id
        $bookingWithoutMetadata = [
            'status' => 200,
            'data' => [
                'id' => 'booking_no_metadata',
                'createdAt' => now()->subSeconds(5)->toIso8601String(),
                // Missing: metadata.call_id
            ]
        ];

        $this->calcomService->expects($this->once())
            ->method('createBooking')
            ->willReturn($bookingWithoutMetadata);

        $result = $this->service->bookInCalcom(
            $customer,
            $service,
            Carbon::parse('2025-10-10 08:00:00'),
            45,
            $call
        );

        // Should not crash, implementation accepts when metadata missing
        $this->assertNotNull($result);
    }

    /** @test */
    public function layer2_rejects_real_duplicate_scenario_from_bug_report()
    {
        [$customer, $service, $call] = $this->setupTestData();

        // Recreate exact scenario from bug: Call 688 receives Call 687's booking
        $oldCallId = 'call_927bf219b2cc20cd24dc97c9f0b'; // Call 687
        $currentCallId = 'call_39d2ade6f4fc16c51110ca49cdf'; // Call 688
        $call->retell_call_id = $currentCallId;
        $call->save();

        $bugScenarioBooking = $this->mockCalcomResponse(
            bookingId: '8Fxv4pCqnb1Jva1w9wn5wX', // Real booking ID from bug
            createdAt: now()->subMinutes(35), // Call 687 was 35 min ago
            callId: $oldCallId // Wrong call_id!
        );

        $this->calcomService->expects($this->once())
            ->method('createBooking')
            ->willReturn($bugScenarioBooking);

        $result = $this->service->bookInCalcom(
            $customer,
            $service,
            Carbon::parse('2025-10-10 08:00:00'),
            45,
            $call
        );

        // BOTH Layer 1 (stale) AND Layer 2 (call_id mismatch) should reject
        $this->assertNull($result);
    }

    // ==========================================
    // LAYER 3: Database Duplicate Check Tests
    // ==========================================

    /** @test */
    public function layer3_creates_new_appointment_when_booking_id_is_unique()
    {
        [$customer, $service, $call] = $this->setupTestData();

        $bookingDetails = [
            'starts_at' => '2025-10-10 08:00:00',
            'ends_at' => '2025-10-10 08:45:00',
            'duration_minutes' => 45
        ];

        // No existing appointment with this booking ID
        $appointment = $this->service->createLocalRecord(
            $customer,
            $service,
            $bookingDetails,
            'unique_booking_123',
            $call
        );

        $this->assertNotNull($appointment);
        $this->assertEquals('unique_booking_123', $appointment->calcom_v2_booking_id);
        $this->assertEquals($customer->id, $appointment->customer_id);
    }

    /** @test */
    public function layer3_returns_existing_appointment_when_booking_id_already_exists()
    {
        [$customer, $service, $call] = $this->setupTestData();

        // Create EXISTING appointment with booking ID
        $existingAppointment = Appointment::create([
            'customer_id' => $customer->id,
            'service_id' => $service->id,
            'branch_id' => $customer->branch_id,
            'tenant_id' => $customer->tenant_id ?? 1,
            'starts_at' => '2025-10-10 08:00:00',
            'ends_at' => '2025-10-10 08:45:00',
            'call_id' => $call->id,
            'status' => 'scheduled',
            'calcom_v2_booking_id' => 'duplicate_booking_789', // DUPLICATE!
            'source' => 'retell_webhook'
        ]);

        $bookingDetails = [
            'starts_at' => '2025-10-10 08:00:00',
            'ends_at' => '2025-10-10 08:45:00',
            'duration_minutes' => 45
        ];

        // Attempt to create with SAME booking ID
        $result = $this->service->createLocalRecord(
            $customer,
            $service,
            $bookingDetails,
            'duplicate_booking_789', // Same as existing!
            $call
        );

        // Should return EXISTING appointment, not create new one
        $this->assertEquals($existingAppointment->id, $result->id);
        $this->assertEquals('duplicate_booking_789', $result->calcom_v2_booking_id);

        // Verify only ONE appointment exists with this booking ID
        $count = Appointment::where('calcom_v2_booking_id', 'duplicate_booking_789')->count();
        $this->assertEquals(1, $count);
    }

    /** @test */
    public function layer3_prevents_cross_customer_duplicate()
    {
        // Setup: Two different customers, same booking attempt
        $company = Company::create(['name' => 'Test Company', 'status' => 'active']);
        $branch = Branch::create(['name' => 'Main Branch', 'company_id' => $company->id, 'status' => 'active']);

        $customer1 = Customer::create([
            'name' => 'Customer One',
            'phone' => '+491111111111',
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'status' => 'active'
        ]);

        $customer2 = Customer::create([
            'name' => 'Customer Two',
            'phone' => '+492222222222',
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'status' => 'active'
        ]);

        $service = Service::create([
            'name' => 'Test Service',
            'company_id' => $company->id,
            'duration_minutes' => 45,
            'price' => 50.00,
            'calcom_event_type_id' => 123
        ]);

        $call1 = Call::create([
            'retell_call_id' => 'call_customer_1',
            'from_number' => '+491111111111',
            'to_number' => '+499876543210',
            'company_id' => $company->id,
            'customer_id' => $customer1->id,
            'status' => 'completed'
        ]);

        $call2 = Call::create([
            'retell_call_id' => 'call_customer_2',
            'from_number' => '+492222222222',
            'to_number' => '+499876543210',
            'company_id' => $company->id,
            'customer_id' => $customer2->id,
            'status' => 'completed'
        ]);

        $bookingDetails = [
            'starts_at' => '2025-10-10 08:00:00',
            'ends_at' => '2025-10-10 08:45:00',
            'duration_minutes' => 45
        ];

        // Customer 1 creates appointment first
        $appointment1 = $this->service->createLocalRecord(
            $customer1,
            $service,
            $bookingDetails,
            'shared_booking_id',
            $call1
        );

        // Customer 2 attempts with SAME booking ID (idempotency scenario)
        $appointment2 = $this->service->createLocalRecord(
            $customer2,
            $service,
            $bookingDetails,
            'shared_booking_id', // Same booking ID!
            $call2
        );

        // Should return Customer 1's appointment (first one created)
        $this->assertEquals($appointment1->id, $appointment2->id);
        $this->assertEquals($customer1->id, $appointment2->customer_id);

        // Only ONE appointment should exist
        $count = Appointment::where('calcom_v2_booking_id', 'shared_booking_id')->count();
        $this->assertEquals(1, $count);
    }

    // ==========================================
    // LAYER 4: Database UNIQUE Constraint Test
    // ==========================================

    /** @test */
    public function layer4_database_constraint_prevents_duplicate_insert()
    {
        [$customer, $service, $call] = $this->setupTestData();

        // Create first appointment
        $appointment1 = Appointment::create([
            'customer_id' => $customer->id,
            'service_id' => $service->id,
            'branch_id' => $customer->branch_id,
            'tenant_id' => $customer->tenant_id ?? 1,
            'starts_at' => '2025-10-10 08:00:00',
            'ends_at' => '2025-10-10 08:45:00',
            'call_id' => $call->id,
            'status' => 'scheduled',
            'calcom_v2_booking_id' => 'constraint_test_booking',
            'source' => 'retell_webhook'
        ]);

        $this->assertNotNull($appointment1);

        // Attempt to create second appointment with SAME booking ID
        // This should throw database constraint violation
        $this->expectException(\Illuminate\Database\QueryException::class);
        $this->expectExceptionMessage('Duplicate entry');

        Appointment::create([
            'customer_id' => $customer->id,
            'service_id' => $service->id,
            'branch_id' => $customer->branch_id,
            'tenant_id' => $customer->tenant_id ?? 1,
            'starts_at' => '2025-10-10 09:00:00', // Different time
            'ends_at' => '2025-10-10 09:45:00',
            'call_id' => $call->id,
            'status' => 'scheduled',
            'calcom_v2_booking_id' => 'constraint_test_booking', // SAME booking ID
            'source' => 'retell_webhook'
        ]);

        // Test should fail at the above line due to constraint
    }

    // ==========================================
    // Multi-Layer Integration Tests
    // ==========================================

    /** @test */
    public function all_layers_work_together_to_prevent_duplicate()
    {
        [$customer, $service, $call] = $this->setupTestData();

        // Scenario: Cal.com returns stale booking with wrong call_id
        $staleBookingResponse = $this->mockCalcomResponse(
            bookingId: 'multi_layer_test',
            createdAt: now()->subSeconds(60), // STALE (Layer 1 fails)
            callId: 'wrong_call_id' // MISMATCH (Layer 2 fails)
        );

        $this->calcomService->expects($this->once())
            ->method('createBooking')
            ->willReturn($staleBookingResponse);

        // Also pre-create existing appointment (Layer 3 would catch)
        Appointment::create([
            'customer_id' => $customer->id,
            'service_id' => $service->id,
            'branch_id' => $customer->branch_id,
            'tenant_id' => $customer->tenant_id ?? 1,
            'starts_at' => '2025-10-10 08:00:00',
            'ends_at' => '2025-10-10 08:45:00',
            'status' => 'scheduled',
            'calcom_v2_booking_id' => 'multi_layer_test',
            'source' => 'retell_webhook'
        ]);

        $result = $this->service->bookInCalcom(
            $customer,
            $service,
            Carbon::parse('2025-10-10 08:00:00'),
            45,
            $call
        );

        // Should be rejected by Layer 1 or Layer 2 (both fail)
        $this->assertNull($result);
    }

    /** @test */
    public function fresh_booking_with_correct_call_id_passes_all_layers()
    {
        [$customer, $service, $call] = $this->setupTestData();

        // Perfect scenario: fresh, correct call_id, unique booking ID
        $validBooking = $this->mockCalcomResponse(
            bookingId: 'perfect_booking',
            createdAt: now()->subSeconds(2), // FRESH
            callId: $call->retell_call_id // CORRECT
        );

        $this->calcomService->expects($this->once())
            ->method('createBooking')
            ->willReturn($validBooking);

        $result = $this->service->bookInCalcom(
            $customer,
            $service,
            Carbon::parse('2025-10-10 08:00:00'),
            45,
            $call
        );

        // Should PASS all layers
        $this->assertNotNull($result);
        $this->assertEquals('perfect_booking', $result['booking_id']);

        // Now create appointment (Layer 3 check)
        $bookingDetails = [
            'starts_at' => '2025-10-10 08:00:00',
            'ends_at' => '2025-10-10 08:45:00',
            'duration_minutes' => 45
        ];

        $appointment = $this->service->createLocalRecord(
            $customer,
            $service,
            $bookingDetails,
            'perfect_booking',
            $call
        );

        // Should create successfully
        $this->assertNotNull($appointment);
        $this->assertEquals('perfect_booking', $appointment->calcom_v2_booking_id);
    }

    // ==========================================
    // Helper Methods
    // ==========================================

    /**
     * Setup standard test data (company, branch, customer, service, call)
     */
    private function setupTestData(): array
    {
        $company = Company::create(['name' => 'Test Company', 'status' => 'active']);
        $branch = Branch::create(['name' => 'Main Branch', 'company_id' => $company->id, 'status' => 'active']);

        $customer = Customer::create([
            'name' => 'Test Customer',
            'phone' => '+491234567890',
            'email' => 'test@example.com',
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'status' => 'active'
        ]);

        $service = Service::create([
            'name' => 'Test Service',
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'duration_minutes' => 45,
            'price' => 50.00,
            'calcom_event_type_id' => 123
        ]);

        $call = Call::create([
            'retell_call_id' => 'test_call_' . uniqid(),
            'from_number' => '+491234567890',
            'to_number' => '+499876543210',
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'customer_id' => $customer->id,
            'status' => 'completed'
        ]);

        return [$customer, $service, $call];
    }

    /**
     * Mock Cal.com API response with specific parameters
     */
    private function mockCalcomResponse(
        string $bookingId,
        Carbon $createdAt,
        string $callId
    ): array {
        return [
            'status' => 200,
            'data' => [
                'id' => $bookingId,
                'createdAt' => $createdAt->toIso8601String(),
                'metadata' => [
                    'call_id' => $callId,
                    'service' => 'Test Service'
                ],
                'attendees' => [
                    [
                        'name' => 'Test Customer',
                        'email' => 'test@example.com'
                    ]
                ],
                'hosts' => [
                    [
                        'id' => 123,
                        'name' => 'Test Host'
                    ]
                ]
            ]
        ];
    }
}
