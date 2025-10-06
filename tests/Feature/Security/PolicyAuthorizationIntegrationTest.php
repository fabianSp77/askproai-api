<?php

namespace Tests\Feature\Security;

use App\Models\Company;
use App\Models\User;
use App\Models\Appointment;
use App\Models\Service;
use App\Models\Customer;
use App\Models\Staff;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Spatie\Permission\Models\Role;

/**
 * Policy Authorization Integration Test
 *
 * Validates that Laravel policies correctly enforce:
 * - Company-based authorization
 * - Role-based permissions (super_admin, admin, staff)
 * - Cross-tenant access prevention
 */
class PolicyAuthorizationIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private Company $companyA;
    private Company $companyB;
    private User $superAdmin;
    private User $adminA;
    private User $staffA;
    private User $staffB;
    private Appointment $appointmentA;
    private Appointment $appointmentB;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'web']);

        // Create companies
        $this->companyA = Company::factory()->create(['name' => 'Company A']);
        $this->companyB = Company::factory()->create(['name' => 'Company B']);

        // Create users with different roles
        $this->superAdmin = User::factory()->create([
            'company_id' => $this->companyA->id,
            'email' => 'superadmin@test.com',
        ]);
        $this->superAdmin->assignRole('super_admin');

        $this->adminA = User::factory()->create([
            'company_id' => $this->companyA->id,
            'email' => 'admin@companya.com',
        ]);
        $this->adminA->assignRole('admin');

        $this->staffA = User::factory()->create([
            'company_id' => $this->companyA->id,
            'email' => 'staff@companya.com',
        ]);
        $this->staffA->assignRole('staff');

        $this->staffB = User::factory()->create([
            'company_id' => $this->companyB->id,
            'email' => 'staff@companyb.com',
        ]);
        $this->staffB->assignRole('staff');

        // Create test data
        $serviceA = Service::factory()->create(['company_id' => $this->companyA->id]);
        $customerA = Customer::factory()->create(['company_id' => $this->companyA->id]);
        $staffModelA = Staff::factory()->create(['company_id' => $this->companyA->id]);

        $this->appointmentA = Appointment::factory()->create([
            'company_id' => $this->companyA->id,
            'service_id' => $serviceA->id,
            'customer_id' => $customerA->id,
            'staff_id' => $staffModelA->id,
        ]);

        $serviceB = Service::factory()->create(['company_id' => $this->companyB->id]);
        $customerB = Customer::factory()->create(['company_id' => $this->companyB->id]);
        $staffModelB = Staff::factory()->create(['company_id' => $this->companyB->id]);

        $this->appointmentB = Appointment::factory()->create([
            'company_id' => $this->companyB->id,
            'service_id' => $serviceB->id,
            'customer_id' => $customerB->id,
            'staff_id' => $staffModelB->id,
        ]);
    }

    /** @test */
    public function super_admin_can_force_delete_any_appointment()
    {
        $this->actingAs($this->superAdmin);

        // Super admin should be able to force delete appointments from any company
        $canForceDeleteA = $this->superAdmin->can('forceDelete', $this->appointmentA);
        $canForceDeleteB = $this->superAdmin->can('forceDelete', $this->appointmentB);

        $this->assertTrue($canForceDeleteA, 'Super admin should be able to forceDelete appointments from company A');
        $this->assertTrue($canForceDeleteB, 'Super admin should be able to forceDelete appointments from company B');
    }

    /** @test */
    public function admin_cannot_force_delete_appointments()
    {
        $this->actingAs($this->adminA);

        // Regular admin should NOT be able to force delete
        $canForceDelete = $this->adminA->can('forceDelete', $this->appointmentA);

        $this->assertFalse($canForceDelete, 'Regular admin should NOT be able to forceDelete appointments');
    }

    /** @test */
    public function staff_cannot_force_delete_appointments()
    {
        $this->actingAs($this->staffA);

        // Staff should NOT be able to force delete
        $canForceDelete = $this->staffA->can('forceDelete', $this->appointmentA);

        $this->assertFalse($canForceDelete, 'Staff should NOT be able to forceDelete appointments');
    }

    /** @test */
    public function admin_can_view_own_company_appointments()
    {
        $this->actingAs($this->adminA);

        // Admin should be able to view their company's appointments
        $canView = $this->adminA->can('view', $this->appointmentA);

        $this->assertTrue($canView, 'Admin should be able to view own company appointments');
    }

    /** @test */
    public function staff_cannot_view_other_company_appointments()
    {
        $this->actingAs($this->staffA);

        // Staff from Company A should NOT be able to view Company B's appointments
        $canView = $this->staffA->can('view', $this->appointmentB);

        $this->assertFalse($canView, 'Staff should NOT be able to view other company appointments');
    }

    /** @test */
    public function admin_can_update_own_company_appointments()
    {
        $this->actingAs($this->adminA);

        // Admin should be able to update their company's appointments
        $canUpdate = $this->adminA->can('update', $this->appointmentA);

        $this->assertTrue($canUpdate, 'Admin should be able to update own company appointments');
    }

    /** @test */
    public function staff_cannot_update_other_company_appointments()
    {
        $this->actingAs($this->staffA);

        // Staff from Company A should NOT be able to update Company B's appointments
        $canUpdate = $this->staffA->can('update', $this->appointmentB);

        $this->assertFalse($canUpdate, 'Staff should NOT be able to update other company appointments');
    }

    /** @test */
    public function admin_can_delete_own_company_appointments()
    {
        $this->actingAs($this->adminA);

        // Admin should be able to delete their company's appointments
        $canDelete = $this->adminA->can('delete', $this->appointmentA);

        $this->assertTrue($canDelete, 'Admin should be able to delete own company appointments');
    }

    /** @test */
    public function staff_cannot_delete_other_company_appointments()
    {
        $this->actingAs($this->staffA);

        // Staff from Company A should NOT be able to delete Company B's appointments
        $canDelete = $this->staffA->can('delete', $this->appointmentB);

        $this->assertFalse($canDelete, 'Staff should NOT be able to delete other company appointments');
    }

    /** @test */
    public function super_admin_bypasses_company_scope()
    {
        $this->actingAs($this->superAdmin);

        // Super admin should see appointments from ALL companies
        $appointments = Appointment::all();

        // Should contain both appointmentA and appointmentB
        $this->assertGreaterThanOrEqual(2, $appointments->count());
        $this->assertTrue($appointments->contains($this->appointmentA->id));
        $this->assertTrue($appointments->contains($this->appointmentB->id));
    }

    /** @test */
    public function admin_respects_company_scope()
    {
        $this->actingAs($this->adminA);

        // Admin should only see their company's appointments
        $appointments = Appointment::all();

        // Should only contain appointmentA, not appointmentB
        $this->assertTrue($appointments->contains($this->appointmentA->id));
        $this->assertFalse($appointments->contains($this->appointmentB->id));
    }

    /** @test */
    public function staff_respects_company_scope()
    {
        $this->actingAs($this->staffA);

        // Staff should only see their company's appointments
        $appointments = Appointment::all();

        // Should only contain appointmentA, not appointmentB
        $this->assertTrue($appointments->contains($this->appointmentA->id));
        $this->assertFalse($appointments->contains($this->appointmentB->id));
    }

    /** @test */
    public function policy_prevents_cross_company_access_via_api()
    {
        // Login as staff from Company A
        $this->actingAs($this->staffA);

        // Attempt to access Company B's appointment via API
        $response = $this->getJson("/api/appointments/{$this->appointmentB->id}");

        // Should return 404 (not found due to CompanyScope) or 403 (forbidden by policy)
        $this->assertContains($response->status(), [403, 404]);
    }

    /** @test */
    public function only_super_admin_has_global_access()
    {
        // Test each user type
        $testCases = [
            ['user' => $this->superAdmin, 'role' => 'super_admin', 'expectedGlobal' => true],
            ['user' => $this->adminA, 'role' => 'admin', 'expectedGlobal' => false],
            ['user' => $this->staffA, 'role' => 'staff', 'expectedGlobal' => false],
        ];

        foreach ($testCases as $testCase) {
            $this->actingAs($testCase['user']);

            $appointments = Appointment::all();
            $hasGlobalAccess = $appointments->contains($this->appointmentB->id);

            if ($testCase['expectedGlobal']) {
                $this->assertTrue($hasGlobalAccess, "{$testCase['role']} should have global access");
            } else {
                $this->assertFalse($hasGlobalAccess, "{$testCase['role']} should NOT have global access");
            }
        }
    }

    /** @test */
    public function policies_are_registered_and_functional()
    {
        $this->actingAs($this->adminA);

        // Verify that AppointmentPolicy exists and is being used
        $this->assertTrue(class_exists(\App\Policies\AppointmentPolicy::class), 'AppointmentPolicy should exist');

        // Test basic policy methods exist
        $policy = new \App\Policies\AppointmentPolicy();
        $this->assertTrue(method_exists($policy, 'view'), 'Policy should have view method');
        $this->assertTrue(method_exists($policy, 'update'), 'Policy should have update method');
        $this->assertTrue(method_exists($policy, 'delete'), 'Policy should have delete method');
        $this->assertTrue(method_exists($policy, 'forceDelete'), 'Policy should have forceDelete method');
    }
}
