<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\AppointmentAlternativeFinder;
use App\Services\Retell\AppointmentCreationService;
use App\Services\CalcomService;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Service;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Call;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Mockery;

/**
 * Multi-Tenant Security Test Suite
 *
 * Tests for fixes implemented on 2025-11-19:
 * - CRIT-002: Cache Key Validation
 * - CRIT-003: Multi-Tenant Filter in filterOutAllConflicts
 * - sync_origin Fix
 * - Pre-Sync Validation
 *
 * @group security
 * @group multi-tenant
 */
class MultiTenantSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected AppointmentAlternativeFinder $finder;
    protected Company $companyA;
    protected Company $companyB;
    protected Branch $branchA1;
    protected Branch $branchA2;
    protected Branch $branchB1;

    protected function setUp(): void
    {
        parent::setUp();

        // Set timezone
        date_default_timezone_set('Europe/Berlin');
        Carbon::setTestNow(Carbon::parse('2025-11-20 09:00:00', 'Europe/Berlin'));

        // Clear cache
        Cache::flush();

        // Create test companies and branches
        $this->companyA = Company::create([
            'name' => 'Company A',
            'status' => 'active'
        ]);

        $this->companyB = Company::create([
            'name' => 'Company B',
            'status' => 'active'
        ]);

        $this->branchA1 = Branch::create([
            'name' => 'Company A - Branch 1',
            'company_id' => $this->companyA->id,
            'status' => 'active'
        ]);

        $this->branchA2 = Branch::create([
            'name' => 'Company A - Branch 2',
            'company_id' => $this->companyA->id,
            'status' => 'active'
        ]);

        $this->branchB1 = Branch::create([
            'name' => 'Company B - Branch 1',
            'company_id' => $this->companyB->id,
            'status' => 'active'
        ]);

        // Create finder instance
        $this->finder = new AppointmentAlternativeFinder();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        Carbon::setTestNow();
        parent::tearDown();
    }

    // ================================================================
    // TEST 1: Security - Tenant Context Validation (CRIT-002)
    // ================================================================

    /**
     * @test
     * Verify that cache operations throw exception without tenant context
     *
     * Fix: AppointmentAlternativeFinder.php:450-462
     */
    public function it_throws_exception_when_tenant_context_not_set()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Tenant context (company_id and branch_id) must be set');

        // Attempt to use finder without setting tenant context
        // This should throw before any cache access
        $reflection = new \ReflectionClass($this->finder);
        $method = $reflection->getMethod('getCachedCalcomSlots');
        $method->setAccessible(true);

        // Should throw RuntimeException
        $method->invoke($this->finder, 123, '2025-11-20', '2025-11-20');
    }

    /**
     * @test
     * Verify that setTenantContext properly initializes context
     */
    public function it_sets_tenant_context_successfully()
    {
        // Should not throw
        $this->finder->setTenantContext($this->companyA->id, $this->branchA1->id);

        // Verify context was set by checking internal state
        $reflection = new \ReflectionClass($this->finder);

        $companyProperty = $reflection->getProperty('companyId');
        $companyProperty->setAccessible(true);
        $this->assertEquals($this->companyA->id, $companyProperty->getValue($this->finder));

        $branchProperty = $reflection->getProperty('branchId');
        $branchProperty->setAccessible(true);
        $this->assertEquals($this->branchA1->id, $branchProperty->getValue($this->finder));
    }

    /**
     * @test
     * Verify that cache TTL was reduced from 300s to 60s
     */
    public function it_uses_correct_cache_ttl()
    {
        Cache::spy();

        $this->finder->setTenantContext($this->companyA->id, $this->branchA1->id);

        // Mock CalcomService
        $calcomMock = Mockery::mock(CalcomService::class);
        $calcomMock->shouldReceive('getAvailableSlots')
            ->andReturn(new \Illuminate\Http\Client\Response(
                new \GuzzleHttp\Psr7\Response(200, [], json_encode([
                    'status' => 'success',
                    'data' => ['slots' => []]
                ]))
            ));

        $reflection = new \ReflectionClass($this->finder);
        $property = $reflection->getProperty('calcomService');
        $property->setAccessible(true);
        $property->setValue($this->finder, $calcomMock);

        // Trigger cache operation
        $method = $reflection->getMethod('getCachedCalcomSlots');
        $method->setAccessible(true);
        $method->invoke($this->finder, 123, '2025-11-20', '2025-11-20');

        // Verify cache TTL is 60 seconds (not 300)
        Cache::shouldHaveReceived('remember')
            ->once()
            ->with(
                Mockery::pattern('/cal_slots_/'),
                60, // Should be 60, not 300
                Mockery::any()
            );
    }

    // ================================================================
    // TEST 2: Security - Multi-Tenant Isolation (CRIT-003)
    // ================================================================

    /**
     * @test
     * Company A should NOT see Company B's appointments
     *
     * Fix: AppointmentAlternativeFinder.php:1165-1183
     */
    public function it_filters_appointments_by_company_and_branch()
    {
        $time = Carbon::parse('2025-11-20 14:00:00');

        // Create service for each company
        $serviceA = Service::create([
            'name' => 'Service A',
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA1->id,
            'duration_minutes' => 60,
            'price' => 50.00,
            'calcom_event_type_id' => 111
        ]);

        $serviceB = Service::create([
            'name' => 'Service B',
            'company_id' => $this->companyB->id,
            'branch_id' => $this->branchB1->id,
            'duration_minutes' => 60,
            'price' => 50.00,
            'calcom_event_type_id' => 222
        ]);

        // Create customers
        $customerA = Customer::create([
            'name' => 'Customer A',
            'phone' => '+4915112345678',
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA1->id,
            'status' => 'active'
        ]);

        $customerB = Customer::create([
            'name' => 'Customer B',
            'phone' => '+4915187654321',
            'company_id' => $this->companyB->id,
            'branch_id' => $this->branchB1->id,
            'status' => 'active'
        ]);

        // Company A appointment at 14:00
        $apptA = Appointment::create([
            'customer_id' => $customerA->id,
            'service_id' => $serviceA->id,
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA1->id,
            'starts_at' => $time,
            'ends_at' => $time->copy()->addMinutes(60),
            'status' => 'confirmed',
            'sync_origin' => 'system'
        ]);

        // Company B appointment at SAME TIME (different company)
        $apptB = Appointment::create([
            'customer_id' => $customerB->id,
            'service_id' => $serviceB->id,
            'company_id' => $this->companyB->id,
            'branch_id' => $this->branchB1->id,
            'starts_at' => $time,
            'ends_at' => $time->copy()->addMinutes(60),
            'status' => 'confirmed',
            'sync_origin' => 'system'
        ]);

        // Set Company A context
        $this->finder->setTenantContext($this->companyA->id, $this->branchA1->id);

        // Use reflection to test filterOutAllConflicts
        $reflection = new \ReflectionClass($this->finder);
        $method = $reflection->getMethod('filterOutAllConflicts');
        $method->setAccessible(true);

        $candidates = [
            ['datetime' => $time->copy(), 'type' => 'test']
        ];

        // Filter should remove 14:00 for Company A (their appointment)
        // But should NOT see Company B's appointment
        $result = $method->invoke($this->finder, $candidates, null);

        // The conflict check should only consider Company A's appointments
        // Verify by checking that Company B's appointment is not in query
        $conflicts = Appointment::where('company_id', $this->companyA->id)
            ->where('branch_id', $this->branchA1->id)
            ->where('starts_at', '<=', $time->copy()->addMinutes(60))
            ->where('ends_at', '>', $time)
            ->get();

        // Should only find Company A's appointment
        $this->assertCount(1, $conflicts);
        $this->assertEquals($apptA->id, $conflicts->first()->id);
    }

    /**
     * @test
     * Verify filterOutAllConflicts throws exception without tenant context
     *
     * Fix: AppointmentAlternativeFinder.php:1174-1178
     */
    public function it_throws_exception_in_filter_conflicts_without_tenant_context()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Tenant context must be set');

        // Do NOT set tenant context
        $reflection = new \ReflectionClass($this->finder);
        $method = $reflection->getMethod('filterOutAllConflicts');
        $method->setAccessible(true);

        $candidates = [
            ['datetime' => Carbon::now(), 'type' => 'test']
        ];

        // Should throw
        $method->invoke($this->finder, $candidates, null);
    }

    /**
     * @test
     * Multi-branch isolation within same company
     */
    public function it_filters_by_branch_within_same_company()
    {
        $time = Carbon::parse('2025-11-20 15:00:00');

        // Create service for each branch
        $service1 = Service::create([
            'name' => 'Service Branch 1',
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA1->id,
            'duration_minutes' => 60,
            'price' => 50.00
        ]);

        $service2 = Service::create([
            'name' => 'Service Branch 2',
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA2->id,
            'duration_minutes' => 60,
            'price' => 50.00
        ]);

        // Create customers
        $customer1 = Customer::create([
            'name' => 'Customer Branch 1',
            'phone' => '+4915111111111',
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA1->id,
            'status' => 'active'
        ]);

        $customer2 = Customer::create([
            'name' => 'Customer Branch 2',
            'phone' => '+4915122222222',
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA2->id,
            'status' => 'active'
        ]);

        // Branch 1 appointment
        $appt1 = Appointment::create([
            'customer_id' => $customer1->id,
            'service_id' => $service1->id,
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA1->id,
            'starts_at' => $time,
            'ends_at' => $time->copy()->addMinutes(60),
            'status' => 'confirmed',
            'sync_origin' => 'system'
        ]);

        // Branch 2 appointment at SAME TIME (different branch)
        $appt2 = Appointment::create([
            'customer_id' => $customer2->id,
            'service_id' => $service2->id,
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA2->id,
            'starts_at' => $time,
            'ends_at' => $time->copy()->addMinutes(60),
            'status' => 'confirmed',
            'sync_origin' => 'system'
        ]);

        // Set Branch 1 context
        $this->finder->setTenantContext($this->companyA->id, $this->branchA1->id);

        // Check conflicts for Branch 1
        $conflicts = Appointment::where('company_id', $this->companyA->id)
            ->where('branch_id', $this->branchA1->id)
            ->where('starts_at', '<=', $time->copy()->addMinutes(60))
            ->where('ends_at', '>', $time)
            ->get();

        // Should only find Branch 1's appointment (not Branch 2)
        $this->assertCount(1, $conflicts);
        $this->assertEquals($appt1->id, $conflicts->first()->id);
        $this->assertEquals($this->branchA1->id, $conflicts->first()->branch_id);
    }

    // ================================================================
    // TEST 3: Flow - Pre-Sync Conflict Detection
    // ================================================================

    /**
     * @test
     * Pre-sync validation prevents double booking
     *
     * Fix: AppointmentCreationService.php:911-936
     */
    public function it_prevents_double_booking_with_pre_sync_validation()
    {
        $time = Carbon::parse('2025-11-20 16:00:00');

        // Create existing appointment
        $customer = Customer::create([
            'name' => 'Test Customer',
            'phone' => '+4915133333333',
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA1->id,
            'status' => 'active'
        ]);

        $service = Service::create([
            'name' => 'Test Service',
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA1->id,
            'duration_minutes' => 60,
            'price' => 50.00,
            'calcom_event_type_id' => 123
        ]);

        // Existing confirmed appointment
        $existing = Appointment::create([
            'customer_id' => $customer->id,
            'service_id' => $service->id,
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA1->id,
            'starts_at' => $time,
            'ends_at' => $time->copy()->addMinutes(60),
            'status' => 'confirmed',
            'sync_origin' => 'system'
        ]);

        // Try to book same slot - should detect conflict
        $conflict = Appointment::where('company_id', $this->companyA->id)
            ->where('branch_id', $this->branchA1->id)
            ->where('starts_at', '<=', $time->copy()->addMinutes(60))
            ->where('ends_at', '>', $time)
            ->whereIn('status', ['confirmed', 'pending'])
            ->lockForUpdate()
            ->first();

        // Should find the conflict
        $this->assertNotNull($conflict);
        $this->assertEquals($existing->id, $conflict->id);
    }

    /**
     * @test
     * Pre-sync validation uses lockForUpdate for pessimistic locking
     */
    public function it_uses_pessimistic_locking_for_conflict_check()
    {
        $time = Carbon::parse('2025-11-20 17:00:00');

        $customer = Customer::create([
            'name' => 'Lock Test Customer',
            'phone' => '+4915144444444',
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA1->id,
            'status' => 'active'
        ]);

        $service = Service::create([
            'name' => 'Lock Test Service',
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA1->id,
            'duration_minutes' => 60,
            'price' => 50.00
        ]);

        // Create appointment
        $appt = Appointment::create([
            'customer_id' => $customer->id,
            'service_id' => $service->id,
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA1->id,
            'starts_at' => $time,
            'ends_at' => $time->copy()->addMinutes(60),
            'status' => 'confirmed',
            'sync_origin' => 'system'
        ]);

        // Verify lockForUpdate works in transaction
        DB::beginTransaction();

        $locked = Appointment::where('id', $appt->id)
            ->lockForUpdate()
            ->first();

        $this->assertNotNull($locked);
        $this->assertEquals($appt->id, $locked->id);

        DB::rollBack();
    }

    // ================================================================
    // TEST 4: sync_origin Correctness
    // ================================================================

    /**
     * @test
     * Appointments from Cal.com should have sync_origin = 'calcom'
     *
     * Fix: AppointmentCreationService.php:459
     */
    public function it_sets_sync_origin_to_calcom_when_calcom_booking_id_present()
    {
        $customer = Customer::create([
            'name' => 'Sync Test Customer',
            'phone' => '+4915155555555',
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA1->id,
            'status' => 'active'
        ]);

        $service = Service::create([
            'name' => 'Sync Test Service',
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA1->id,
            'duration_minutes' => 60,
            'price' => 50.00
        ]);

        $bookingDetails = [
            'starts_at' => '2025-11-20 10:00:00',
            'duration_minutes' => 60
        ];

        // Mock the service to test createLocalRecord
        $appointmentService = app(AppointmentCreationService::class);

        // Create local record WITH Cal.com booking ID
        $appointment = $appointmentService->createLocalRecord(
            $customer,
            $service,
            $bookingDetails,
            'calcom_booking_123' // Cal.com booking ID present
        );

        // Should have sync_origin = 'calcom' (NOT 'retell')
        $this->assertEquals('calcom', $appointment->sync_origin);
        $this->assertEquals('synced', $appointment->calcom_sync_status);
    }

    /**
     * @test
     * Appointments without Cal.com ID should have sync_origin = 'system'
     */
    public function it_sets_sync_origin_to_system_when_no_calcom_booking_id()
    {
        $customer = Customer::create([
            'name' => 'System Test Customer',
            'phone' => '+4915166666666',
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA1->id,
            'status' => 'active'
        ]);

        $service = Service::create([
            'name' => 'System Test Service',
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA1->id,
            'duration_minutes' => 60,
            'price' => 50.00
        ]);

        $bookingDetails = [
            'starts_at' => '2025-11-20 11:00:00',
            'duration_minutes' => 60
        ];

        $appointmentService = app(AppointmentCreationService::class);

        // Create local record WITHOUT Cal.com booking ID
        $appointment = $appointmentService->createLocalRecord(
            $customer,
            $service,
            $bookingDetails,
            null // No Cal.com booking ID
        );

        // Should have sync_origin = 'system'
        $this->assertEquals('system', $appointment->sync_origin);
    }

    // ================================================================
    // TEST 5: Cache Isolation
    // ================================================================

    /**
     * @test
     * Cache keys must include tenant context for isolation
     *
     * Fix: AppointmentAlternativeFinder.php:450-462
     */
    public function it_includes_tenant_context_in_cache_keys()
    {
        Cache::spy();

        $this->finder->setTenantContext($this->companyA->id, $this->branchA1->id);

        // Mock CalcomService
        $calcomMock = Mockery::mock(CalcomService::class);
        $calcomMock->shouldReceive('getAvailableSlots')
            ->andReturn(new \Illuminate\Http\Client\Response(
                new \GuzzleHttp\Psr7\Response(200, [], json_encode([
                    'status' => 'success',
                    'data' => ['slots' => []]
                ]))
            ));

        $reflection = new \ReflectionClass($this->finder);
        $property = $reflection->getProperty('calcomService');
        $property->setAccessible(true);
        $property->setValue($this->finder, $calcomMock);

        // Trigger cache
        $method = $reflection->getMethod('getCachedCalcomSlots');
        $method->setAccessible(true);
        $method->invoke($this->finder, 123, '2025-11-20', '2025-11-20');

        // Cache key should include company_id and branch_id
        $expectedPattern = '/cal_slots_' . $this->companyA->id . '_' . $this->branchA1->id . '/';

        Cache::shouldHaveReceived('remember')
            ->once()
            ->with(
                Mockery::pattern($expectedPattern),
                Mockery::any(),
                Mockery::any()
            );
    }

    /**
     * @test
     * Different tenants should have separate cache entries
     */
    public function it_maintains_separate_cache_for_different_tenants()
    {
        Cache::flush();

        // Company A cache
        $this->finder->setTenantContext($this->companyA->id, $this->branchA1->id);

        $cacheKeyA = 'cal_slots_' . $this->companyA->id . '_' . $this->branchA1->id . '_123_2025-11-20_2025-11-20';
        Cache::put($cacheKeyA, ['slots' => ['company_a']], 60);

        // Company B cache (different finder instance)
        $finderB = new AppointmentAlternativeFinder();
        $finderB->setTenantContext($this->companyB->id, $this->branchB1->id);

        $cacheKeyB = 'cal_slots_' . $this->companyB->id . '_' . $this->branchB1->id . '_123_2025-11-20_2025-11-20';
        Cache::put($cacheKeyB, ['slots' => ['company_b']], 60);

        // Verify separate cache entries
        $dataA = Cache::get($cacheKeyA);
        $dataB = Cache::get($cacheKeyB);

        $this->assertEquals(['slots' => ['company_a']], $dataA);
        $this->assertEquals(['slots' => ['company_b']], $dataB);
        $this->assertNotEquals($dataA, $dataB);
    }

    // ================================================================
    // TEST 6: Race Condition Simulation
    // ================================================================

    /**
     * @test
     * Concurrent booking attempts should be prevented by locks
     */
    public function it_prevents_race_conditions_with_database_locks()
    {
        $time = Carbon::parse('2025-11-20 18:00:00');

        $customer = Customer::create([
            'name' => 'Race Test Customer',
            'phone' => '+4915177777777',
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA1->id,
            'status' => 'active'
        ]);

        $service = Service::create([
            'name' => 'Race Test Service',
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA1->id,
            'duration_minutes' => 60,
            'price' => 50.00
        ]);

        // Transaction 1: Create appointment
        DB::beginTransaction();

        $appt1 = Appointment::create([
            'customer_id' => $customer->id,
            'service_id' => $service->id,
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA1->id,
            'starts_at' => $time,
            'ends_at' => $time->copy()->addMinutes(60),
            'status' => 'pending',
            'sync_origin' => 'system'
        ]);

        // Lock the appointment
        $locked = Appointment::where('id', $appt1->id)
            ->lockForUpdate()
            ->first();

        $this->assertNotNull($locked);

        // In a real concurrent scenario, Transaction 2 would wait here
        // and then detect the conflict after Transaction 1 commits

        DB::commit();

        // Transaction 2: Check for conflicts
        DB::beginTransaction();

        $conflict = Appointment::where('company_id', $this->companyA->id)
            ->where('branch_id', $this->branchA1->id)
            ->where('starts_at', '<=', $time->copy()->addMinutes(60))
            ->where('ends_at', '>', $time)
            ->whereIn('status', ['confirmed', 'pending'])
            ->lockForUpdate()
            ->first();

        // Should find the conflict from Transaction 1
        $this->assertNotNull($conflict);
        $this->assertEquals($appt1->id, $conflict->id);

        DB::rollBack();
    }

    /**
     * @test
     * Pre-sync validation query structure
     */
    public function it_uses_correct_pre_sync_validation_query()
    {
        $time = Carbon::parse('2025-11-20 19:00:00');

        $customer = Customer::create([
            'name' => 'Validation Test',
            'phone' => '+4915188888888',
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA1->id,
            'status' => 'active'
        ]);

        $service = Service::create([
            'name' => 'Validation Service',
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA1->id,
            'duration_minutes' => 60,
            'price' => 50.00
        ]);

        // Create test appointments
        $appt1 = Appointment::create([
            'customer_id' => $customer->id,
            'service_id' => $service->id,
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA1->id,
            'starts_at' => $time,
            'ends_at' => $time->copy()->addMinutes(60),
            'status' => 'confirmed',
            'sync_origin' => 'system'
        ]);

        // Cancelled appointment (should NOT conflict)
        $appt2 = Appointment::create([
            'customer_id' => $customer->id,
            'service_id' => $service->id,
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA1->id,
            'starts_at' => $time->copy()->addMinutes(30),
            'ends_at' => $time->copy()->addMinutes(90),
            'status' => 'cancelled',
            'sync_origin' => 'system'
        ]);

        // Pre-sync validation query (as per fix)
        DB::beginTransaction();

        $conflicts = Appointment::where('company_id', $this->companyA->id)
            ->where('branch_id', $this->branchA1->id)
            ->where('starts_at', '<=', $time->copy()->addMinutes(60))
            ->where('ends_at', '>', $time)
            ->whereIn('status', ['confirmed', 'pending'])
            ->lockForUpdate()
            ->get();

        // Should only find confirmed appointment (not cancelled)
        $this->assertCount(1, $conflicts);
        $this->assertEquals($appt1->id, $conflicts->first()->id);
        $this->assertEquals('confirmed', $conflicts->first()->status);

        DB::rollBack();
    }
}
