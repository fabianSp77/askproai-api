<?php

namespace Tests\Unit\CustomerPortal;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Appointment;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Staff;
use App\Models\User;
use App\Models\Service;
use App\Models\Customer;
use App\Policies\AppointmentPolicy;
use Spatie\Permission\Models\Role;
use Carbon\Carbon;

/**
 * AppointmentPolicy Unit Tests
 *
 * Tests multi-level access control for Customer Portal Phase 1:
 * - Level 1: Admin bypass (super_admin can view all)
 * - Level 2: Company isolation (CRITICAL for multi-tenancy)
 * - Level 3: Branch isolation (company_manager sees only branch appointments)
 * - Level 4: Staff isolation (company_staff sees only their appointments)
 * - Level 5: Owner/Admin access (company_owner/company_admin see all company appointments)
 *
 * Phase 1: READ-ONLY (no create/update/delete for customer portal roles)
 */
class AppointmentPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected AppointmentPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new AppointmentPolicy();

        // Seed required roles
        $this->seedRoles();
    }

    private function seedRoles(): void
    {
        Role::firstOrCreate(['name' => 'super_admin']);
        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'manager']);
        Role::firstOrCreate(['name' => 'company_owner']);
        Role::firstOrCreate(['name' => 'company_admin']);
        Role::firstOrCreate(['name' => 'company_manager']);
        Role::firstOrCreate(['name' => 'company_staff']);
        Role::firstOrCreate(['name' => 'staff']);
    }

    /**
     * Test: super_admin can view all appointments (Level 1 bypass)
     *
     * Verifies super_admin bypasses all other checks.
     */
    public function test_super_admin_can_view_all_appointments()
    {
        $company1 = Company::factory()->create();
        $company2 = Company::factory()->create();
        $appointment1 = $this->createAppointment(['company_id' => $company1->id]);
        $appointment2 = $this->createAppointment(['company_id' => $company2->id]);

        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');

        $this->assertTrue($this->policy->view($superAdmin, $appointment1));
        $this->assertTrue($this->policy->view($superAdmin, $appointment2));
        $this->assertTrue($this->policy->viewAny($superAdmin));
    }

    /**
     * Test: admin can view all appointments (Level 1)
     *
     * Verifies admin role can view appointments from any company.
     */
    public function test_admin_can_view_all_appointments()
    {
        $company1 = Company::factory()->create();
        $company2 = Company::factory()->create();
        $appointment1 = $this->createAppointment(['company_id' => $company1->id]);
        $appointment2 = $this->createAppointment(['company_id' => $company2->id]);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->assertTrue($this->policy->view($admin, $appointment1));
        $this->assertTrue($this->policy->view($admin, $appointment2));
    }

    /**
     * Test: company_owner can view all company appointments (Level 2 + Level 5)
     *
     * Verifies company_owner can view all appointments in their company
     * but NOT appointments from other companies.
     */
    public function test_company_owner_can_view_all_company_appointments()
    {
        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();

        $appointment1 = $this->createAppointment(['company_id' => $company->id]);
        $appointment2 = $this->createAppointment(['company_id' => $company->id]);
        $otherAppointment = $this->createAppointment(['company_id' => $otherCompany->id]);

        $owner = User::factory()->create(['company_id' => $company->id]);
        $owner->assignRole('company_owner');

        // Owner can view all appointments in their company
        $this->assertTrue($this->policy->view($owner, $appointment1));
        $this->assertTrue($this->policy->view($owner, $appointment2));

        // Owner CANNOT view appointments from other companies (Level 2 isolation)
        $this->assertFalse($this->policy->view($owner, $otherAppointment));
    }

    /**
     * Test: company_admin can view all company appointments (Level 5)
     *
     * Verifies company_admin has same viewing rights as company_owner.
     */
    public function test_company_admin_can_view_all_company_appointments()
    {
        $company = Company::factory()->create();
        $appointment1 = $this->createAppointment(['company_id' => $company->id]);
        $appointment2 = $this->createAppointment(['company_id' => $company->id]);

        $admin = User::factory()->create(['company_id' => $company->id]);
        $admin->assignRole('company_admin');

        $this->assertTrue($this->policy->view($admin, $appointment1));
        $this->assertTrue($this->policy->view($admin, $appointment2));
    }

    /**
     * Test: company_manager can view ONLY branch appointments (Level 3)
     *
     * CRITICAL TEST: Verifies branch isolation for company_manager.
     * Manager should ONLY see appointments in their assigned branch_id.
     */
    public function test_company_manager_can_view_only_branch_appointments()
    {
        $company = Company::factory()->create();
        $assignedBranch = Branch::factory()->create(['company_id' => $company->id, 'name' => 'Assigned Branch']);
        $otherBranch = Branch::factory()->create(['company_id' => $company->id, 'name' => 'Other Branch']);

        $appointmentInBranch = $this->createAppointment([
            'company_id' => $company->id,
            'branch_id' => $assignedBranch->id,
        ]);

        $appointmentInOtherBranch = $this->createAppointment([
            'company_id' => $company->id,
            'branch_id' => $otherBranch->id,
        ]);

        $manager = User::factory()->create([
            'company_id' => $company->id,
            'branch_id' => $assignedBranch->id,
        ]);
        $manager->assignRole('company_manager');

        // Manager CAN view appointments in their branch
        $this->assertTrue($this->policy->view($manager, $appointmentInBranch));

        // Manager CANNOT view appointments in other branches (Level 3 branch isolation)
        $this->assertFalse($this->policy->view($manager, $appointmentInOtherBranch));
    }

    /**
     * Test: company_manager CANNOT view appointments from other companies (Level 2)
     *
     * Verifies company isolation works for managers.
     */
    public function test_company_manager_cannot_view_other_company_appointments()
    {
        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();

        $branch = Branch::factory()->create(['company_id' => $company->id]);
        $appointmentInCompany = $this->createAppointment([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
        ]);
        $appointmentInOtherCompany = $this->createAppointment(['company_id' => $otherCompany->id]);

        $manager = User::factory()->create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
        ]);
        $manager->assignRole('company_manager');

        // Manager can view appointments in their company/branch
        $this->assertTrue($this->policy->view($manager, $appointmentInCompany));

        // Manager CANNOT view appointments from other companies
        $this->assertFalse($this->policy->view($manager, $appointmentInOtherCompany));
    }

    /**
     * Test: company_staff can view ONLY their appointments (Level 4)
     *
     * CRITICAL TEST: Verifies staff isolation.
     * Staff should ONLY see appointments where staff_id matches their staff profile.
     */
    public function test_company_staff_can_view_only_their_appointments()
    {
        $company = Company::factory()->create();
        $branch = Branch::factory()->create(['company_id' => $company->id]);

        $staff1 = Staff::factory()->create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'name' => 'Staff 1',
        ]);

        $staff2 = Staff::factory()->create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'name' => 'Staff 2',
        ]);

        // Appointment assigned to staff1
        $ownAppointment = $this->createAppointment([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'staff_id' => $staff1->id,
        ]);

        // Appointment assigned to staff2
        $otherAppointment = $this->createAppointment([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'staff_id' => $staff2->id,
        ]);

        // User linked to staff1
        $staffUser = User::factory()->create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'staff_id' => $staff1->id,
        ]);
        $staffUser->assignRole('company_staff');

        // Staff CAN view their own appointments
        $this->assertTrue($this->policy->view($staffUser, $ownAppointment));

        // Staff CANNOT view other staff's appointments (Level 4 staff isolation)
        $this->assertFalse($this->policy->view($staffUser, $otherAppointment));
    }

    /**
     * Test: company_staff CANNOT view appointments without staff match
     *
     * Verifies strict staff isolation - even appointments in same branch.
     */
    public function test_company_staff_cannot_view_unassigned_appointments()
    {
        $company = Company::factory()->create();
        $branch = Branch::factory()->create(['company_id' => $company->id]);
        $staff = Staff::factory()->create(['company_id' => $company->id, 'branch_id' => $branch->id]);

        // Appointment with NO staff_id (unassigned)
        $unassignedAppointment = $this->createAppointment([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'staff_id' => null,
        ]);

        $staffUser = User::factory()->create([
            'company_id' => $company->id,
            'staff_id' => $staff->id,
        ]);
        $staffUser->assignRole('company_staff');

        // Staff CANNOT view unassigned appointments
        $this->assertFalse($this->policy->view($staffUser, $unassignedAppointment));
    }

    /**
     * Test: User without staff_id CANNOT view appointments
     *
     * Verifies users without staff association are blocked.
     */
    public function test_user_without_staff_id_cannot_view_staff_appointments()
    {
        $company = Company::factory()->create();
        $appointment = $this->createAppointment(['company_id' => $company->id]);

        $userWithoutStaff = User::factory()->create([
            'company_id' => $company->id,
            'staff_id' => null,
        ]);
        $userWithoutStaff->assignRole('company_staff');

        $this->assertFalse($this->policy->view($userWithoutStaff, $appointment));
    }

    /**
     * Test: User without company_id CANNOT view any appointments
     *
     * CRITICAL TEST: Verifies multi-tenancy security.
     */
    public function test_user_without_company_cannot_view_appointments()
    {
        $company = Company::factory()->create();
        $appointment = $this->createAppointment(['company_id' => $company->id]);

        $userWithoutCompany = User::factory()->create(['company_id' => null]);
        $userWithoutCompany->assignRole('company_owner');

        $this->assertFalse($this->policy->view($userWithoutCompany, $appointment));
    }

    /**
     * Test: viewAny permission for different roles
     *
     * Verifies all customer portal roles can access appointment list.
     */
    public function test_viewany_permission_for_customer_portal_roles()
    {
        $company = Company::factory()->create();
        $roles = ['company_owner', 'company_admin', 'company_manager', 'company_staff'];

        foreach ($roles as $roleName) {
            $user = User::factory()->create(['company_id' => $company->id]);
            $user->assignRole($roleName);

            $this->assertTrue(
                $this->policy->viewAny($user),
                "Role {$roleName} should have viewAny permission"
            );
        }
    }

    /**
     * Test: manager (admin panel) can view all company appointments
     *
     * Backward compatibility: admin panel 'manager' role has full company access.
     */
    public function test_manager_role_can_view_all_company_appointments()
    {
        $company = Company::factory()->create();
        $appointment1 = $this->createAppointment(['company_id' => $company->id]);
        $appointment2 = $this->createAppointment(['company_id' => $company->id]);

        $manager = User::factory()->create(['company_id' => $company->id]);
        $manager->assignRole('manager'); // Admin panel role

        $this->assertTrue($this->policy->view($manager, $appointment1));
        $this->assertTrue($this->policy->view($manager, $appointment2));
    }

    /**
     * Test: Phase 1 read-only - customer portal roles CANNOT create appointments
     *
     * CRITICAL TEST: Verifies Phase 1 read-only restrictions.
     */
    public function test_customer_portal_roles_cannot_create_appointments_in_phase_1()
    {
        $company = Company::factory()->create();
        $customerPortalRoles = ['company_owner', 'company_admin', 'company_manager', 'company_staff'];

        foreach ($customerPortalRoles as $roleName) {
            $user = User::factory()->create(['company_id' => $company->id]);
            $user->assignRole($roleName);

            $this->assertFalse(
                $this->policy->create($user),
                "Role {$roleName} should NOT be able to create appointments in Phase 1"
            );
        }
    }

    /**
     * Test: admin panel roles CAN create appointments
     *
     * Verifies admin panel roles maintain their create permissions.
     */
    public function test_admin_panel_roles_can_create_appointments()
    {
        $company = Company::factory()->create();

        // Admin can create
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->assertTrue($this->policy->create($admin));

        // Manager can create
        $manager = User::factory()->create(['company_id' => $company->id]);
        $manager->assignRole('manager');
        $this->assertTrue($this->policy->create($manager));

        // Staff can create
        $staff = User::factory()->create(['company_id' => $company->id]);
        $staff->assignRole('staff');
        $this->assertTrue($this->policy->create($staff));
    }

    /**
     * Test: Multi-level access control cascade
     *
     * Comprehensive test verifying all 5 levels work together correctly.
     */
    public function test_multi_level_access_control_cascade()
    {
        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();

        $branch1 = Branch::factory()->create(['company_id' => $company->id]);
        $branch2 = Branch::factory()->create(['company_id' => $company->id]);

        $staff1 = Staff::factory()->create(['company_id' => $company->id, 'branch_id' => $branch1->id]);

        $appointment1 = $this->createAppointment([
            'company_id' => $company->id,
            'branch_id' => $branch1->id,
            'staff_id' => $staff1->id,
        ]);
        $appointment2 = $this->createAppointment([
            'company_id' => $company->id,
            'branch_id' => $branch2->id,
        ]);
        $otherAppointment = $this->createAppointment(['company_id' => $otherCompany->id]);

        // Level 1: Admin bypass
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->assertTrue($this->policy->view($admin, $otherAppointment));

        // Level 2: Company isolation
        $owner = User::factory()->create(['company_id' => $company->id]);
        $owner->assignRole('company_owner');
        $this->assertFalse($this->policy->view($owner, $otherAppointment));

        // Level 3: Branch isolation for manager
        $manager = User::factory()->create([
            'company_id' => $company->id,
            'branch_id' => $branch1->id,
        ]);
        $manager->assignRole('company_manager');
        $this->assertTrue($this->policy->view($manager, $appointment1));
        $this->assertFalse($this->policy->view($manager, $appointment2));

        // Level 4: Staff isolation
        $staffUser = User::factory()->create([
            'company_id' => $company->id,
            'staff_id' => $staff1->id,
        ]);
        $staffUser->assignRole('company_staff');
        $this->assertTrue($this->policy->view($staffUser, $appointment1));

        // Level 5: Owner can view all company appointments
        $this->assertTrue($this->policy->view($owner, $appointment1));
        $this->assertTrue($this->policy->view($owner, $appointment2));
    }

    /**
     * Helper method to create appointment with necessary relationships
     */
    private function createAppointment(array $attributes = []): Appointment
    {
        $company = isset($attributes['company_id'])
            ? Company::find($attributes['company_id'])
            : Company::factory()->create();

        $branch = isset($attributes['branch_id'])
            ? Branch::find($attributes['branch_id'])
            : Branch::factory()->create(['company_id' => $company->id]);

        $staff = isset($attributes['staff_id'])
            ? Staff::find($attributes['staff_id'])
            : (isset($attributes['staff_id']) && $attributes['staff_id'] === null
                ? null
                : Staff::factory()->create(['company_id' => $company->id, 'branch_id' => $branch->id]));

        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $service = Service::factory()->create(['company_id' => $company->id]);

        return Appointment::factory()->create(array_merge([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'staff_id' => $staff?->id,
            'customer_id' => $customer->id,
            'service_id' => $service->id,
            'starts_at' => Carbon::now()->addDays(1),
            'ends_at' => Carbon::now()->addDays(1)->addHour(),
            'status' => 'confirmed',
        ], $attributes));
    }
}
