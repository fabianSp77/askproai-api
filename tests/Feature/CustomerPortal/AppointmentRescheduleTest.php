<?php

namespace Tests\Feature\CustomerPortal;

use App\Models\Appointment;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use App\Models\Staff;
use App\Models\Service;
use App\Services\CalcomV2Client;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

/**
 * Appointment Reschedule Feature Tests
 *
 * TEST COVERAGE:
 * - Authorization (multi-tenant, role-based)
 * - Validation (minimum notice, business rules)
 * - Optimistic locking (concurrent modification)
 * - Cal.com sync (success and failure scenarios)
 * - Audit trail (history tracking)
 * - Edge cases (past appointments, cancelled, etc.)
 *
 * MOCKING STRATEGY:
 * - Cal.com API calls mocked (avoid external dependencies)
 * - Circuit breaker disabled (predictable behavior)
 */
class AppointmentRescheduleTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private User $owner;
    private User $staff;
    private Staff $staffModel;
    private Service $service;
    private Appointment $appointment;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test company
        $this->company = Company::factory()->create([
            'is_pilot' => true, // Enable pilot access
        ]);

        // Create roles
        $ownerRole = Role::factory()->create(['name' => 'owner', 'level' => 100]);
        $staffRole = Role::factory()->create(['name' => 'company_staff', 'level' => 20]);

        // Create users
        $this->owner = User::factory()->create([
            'company_id' => $this->company->id,
        ]);
        $this->owner->roles()->attach($ownerRole);

        $this->staffModel = Staff::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $this->staff = User::factory()->create([
            'company_id' => $this->company->id,
            'staff_id' => $this->staffModel->id,
        ]);
        $this->staff->roles()->attach($staffRole);

        // Create service
        $this->service = Service::factory()->create([
            'company_id' => $this->company->id,
            'duration_minutes' => 60,
        ]);

        // Create appointment (tomorrow at 10am)
        $this->appointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'staff_id' => $this->staffModel->id,
            'service_id' => $this->service->id,
            'start_time' => Carbon::tomorrow()->setTime(10, 0),
            'duration_minutes' => 60,
            'status' => 'confirmed',
            'calcom_booking_id' => 'test_booking_123',
        ]);

        // Mock Cal.com client
        $this->mockCalcomClient();
    }

    // ==========================================
    // SUCCESS SCENARIOS
    // ==========================================

    /** @test */
    public function owner_can_reschedule_appointment(): void
    {
        $newTime = Carbon::tomorrow()->setTime(14, 0);

        $response = $this->actingAs($this->owner)
            ->postJson("/api/v1/customer-portal/appointments/{$this->appointment->id}/reschedule", [
                'new_start_time' => $newTime->toIso8601String(),
                'reason' => 'Customer requested time change',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'appointment' => [
                        'id' => $this->appointment->id,
                    ],
                ],
            ]);

        // Verify database update
        $this->appointment->refresh();
        $this->assertEquals($newTime->format('Y-m-d H:i:s'), $this->appointment->start_time->format('Y-m-d H:i:s'));
        $this->assertEquals(2, $this->appointment->version); // Version incremented

        // Verify audit log
        $this->assertDatabaseHas('appointment_audit_logs', [
            'appointment_id' => $this->appointment->id,
            'action' => 'rescheduled',
            'user_id' => $this->owner->id,
        ]);
    }

    /** @test */
    public function staff_can_reschedule_own_appointment(): void
    {
        $newTime = Carbon::tomorrow()->setTime(15, 0);

        $response = $this->actingAs($this->staff)
            ->postJson("/api/v1/customer-portal/appointments/{$this->appointment->id}/reschedule", [
                'new_start_time' => $newTime->toIso8601String(),
                'reason' => 'Staff requested change',
            ]);

        $response->assertStatus(200);
    }

    // ==========================================
    // AUTHORIZATION FAILURES
    // ==========================================

    /** @test */
    public function staff_cannot_reschedule_other_staff_appointment(): void
    {
        // Create another staff member
        $otherStaff = Staff::factory()->create(['company_id' => $this->company->id]);
        $otherUser = User::factory()->create([
            'company_id' => $this->company->id,
            'staff_id' => $otherStaff->id,
        ]);

        $newTime = Carbon::tomorrow()->setTime(16, 0);

        $response = $this->actingAs($otherUser)
            ->postJson("/api/v1/customer-portal/appointments/{$this->appointment->id}/reschedule", [
                'new_start_time' => $newTime->toIso8601String(),
            ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function user_cannot_reschedule_appointment_from_different_company(): void
    {
        // Create different company and user
        $otherCompany = Company::factory()->create(['is_pilot' => true]);
        $otherOwner = User::factory()->create(['company_id' => $otherCompany->id]);
        $otherOwner->roles()->attach(Role::factory()->create(['name' => 'owner']));

        $newTime = Carbon::tomorrow()->setTime(17, 0);

        $response = $this->actingAs($otherOwner)
            ->postJson("/api/v1/customer-portal/appointments/{$this->appointment->id}/reschedule", [
                'new_start_time' => $newTime->toIso8601String(),
            ]);

        $response->assertStatus(403);

        // Verify appointment unchanged
        $this->appointment->refresh();
        $this->assertEquals(Carbon::tomorrow()->setTime(10, 0)->format('Y-m-d H:i:s'),
                           $this->appointment->start_time->format('Y-m-d H:i:s'));
    }

    // ==========================================
    // VALIDATION FAILURES
    // ==========================================

    /** @test */
    public function cannot_reschedule_past_appointment(): void
    {
        $this->appointment->update([
            'start_time' => Carbon::yesterday(),
        ]);

        $newTime = Carbon::tomorrow()->setTime(10, 0);

        $response = $this->actingAs($this->owner)
            ->postJson("/api/v1/customer-portal/appointments/{$this->appointment->id}/reschedule", [
                'new_start_time' => $newTime->toIso8601String(),
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'error' => 'Cannot reschedule past appointments.',
            ]);
    }

    /** @test */
    public function cannot_reschedule_cancelled_appointment(): void
    {
        $this->appointment->update([
            'status' => 'cancelled',
        ]);

        $newTime = Carbon::tomorrow()->setTime(10, 0);

        $response = $this->actingAs($this->owner)
            ->postJson("/api/v1/customer-portal/appointments/{$this->appointment->id}/reschedule", [
                'new_start_time' => $newTime->toIso8601String(),
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'error' => 'Cannot reschedule cancelled appointments.',
            ]);
    }

    /** @test */
    public function cannot_reschedule_with_insufficient_notice(): void
    {
        // Set appointment to 12 hours from now (less than default 24h minimum)
        $this->appointment->update([
            'start_time' => Carbon::now()->addHours(12),
        ]);

        $newTime = Carbon::tomorrow()->setTime(10, 0);

        $response = $this->actingAs($this->owner)
            ->postJson("/api/v1/customer-portal/appointments/{$this->appointment->id}/reschedule", [
                'new_start_time' => $newTime->toIso8601String(),
            ]);

        $response->assertStatus(422)
            ->assertJsonFragment([
                'error' => 'Appointments must be rescheduled at least 24 hours in advance.',
            ]);
    }

    /** @test */
    public function cannot_reschedule_to_past_time(): void
    {
        $pastTime = Carbon::yesterday();

        $response = $this->actingAs($this->owner)
            ->postJson("/api/v1/customer-portal/appointments/{$this->appointment->id}/reschedule", [
                'new_start_time' => $pastTime->toIso8601String(),
            ]);

        $response->assertStatus(422);
    }

    // ==========================================
    // OPTIMISTIC LOCKING
    // ==========================================

    /** @test */
    public function concurrent_reschedule_fails_with_conflict(): void
    {
        // Simulate concurrent modification by updating version
        $this->appointment->update(['version' => 2]);

        $newTime = Carbon::tomorrow()->setTime(11, 0);

        $response = $this->actingAs($this->owner)
            ->postJson("/api/v1/customer-portal/appointments/{$this->appointment->id}/reschedule", [
                'new_start_time' => $newTime->toIso8601String(),
            ]);

        $response->assertStatus(409)
            ->assertJson([
                'success' => false,
                'error' => 'Appointment was modified by another user. Please refresh and try again.',
            ]);
    }

    // ==========================================
    // CAL.COM INTEGRATION
    // ==========================================

    /** @test */
    public function calcom_sync_failure_rolls_back_transaction(): void
    {
        // Mock Cal.com failure
        $this->instance(
            CalcomV2Client::class,
            Mockery::mock(CalcomV2Client::class, function ($mock) {
                $mock->shouldReceive('rescheduleBooking')
                    ->andThrow(new \Exception('Cal.com API unavailable'));
            })
        );

        $originalTime = $this->appointment->start_time->copy();
        $newTime = Carbon::tomorrow()->setTime(12, 0);

        $response = $this->actingAs($this->owner)
            ->postJson("/api/v1/customer-portal/appointments/{$this->appointment->id}/reschedule", [
                'new_start_time' => $newTime->toIso8601String(),
            ]);

        $response->assertStatus(503);

        // Verify appointment unchanged (rollback successful)
        $this->appointment->refresh();
        $this->assertEquals($originalTime->format('Y-m-d H:i:s'),
                           $this->appointment->start_time->format('Y-m-d H:i:s'));
        $this->assertEquals(1, $this->appointment->version); // Version unchanged
    }

    // ==========================================
    // HELPER METHODS
    // ==========================================

    private function mockCalcomClient(): void
    {
        $mock = Mockery::mock(CalcomV2Client::class);
        $mock->shouldReceive('rescheduleBooking')
            ->andReturn((object) ['id' => 'test_booking_updated']);

        $this->instance(CalcomV2Client::class, $mock);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
