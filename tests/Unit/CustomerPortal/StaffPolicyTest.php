<?php

namespace Tests\Unit\CustomerPortal;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Staff;
use App\Models\Company;
use App\Models\Branch;
use App\Models\User;
use App\Policies\StaffPolicy;
use Spatie\Permission\Models\Role;

/**
 * StaffPolicy Unit Tests
 *
 * Tests multi-level access control for Customer Portal Phase 1:
 * - Level 1: Admin bypass (super_admin can view all)
 * - Level 2: Company isolation (CRITICAL for multi-tenancy)
 * - Level 3: Branch isolation (company_manager sees only branch staff)
 * - Level 4: Self access (company_staff sees only own profile)
 */
class StaffPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected StaffPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new StaffPolicy();

        // Seed required roles
        $this->seedRoles();
    }

    private function seedRoles(): void
    {
        Role::firstOrCreate(['name' => 'super_admin']);
        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'company_owner']);
        Role::firstOrCreate(['name' => 'company_admin']);
        Role::firstOrCreate(['name' => 'company_manager']);
        Role::firstOrCreate(['name' => 'company_staff']);
        Role::firstOrCreate(['name' => 'staff']);
    }

    /**
     * Test: super_admin can view all staff (Level 1 bypass)
     *
     * Verifies that super_admin role bypasses all other checks.
     */
    public function test_super_admin_can_view_all_staff()
    {
        $company1 = Company::factory()->create();
        $company2 = Company::factory()->create();
        $staff1 = Staff::factory()->create(['company_id' => $company1->id]);
        $staff2 = Staff::factory()->create(['company_id' => $company2->id]);

        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');

        $this->assertTrue($this->policy->view($superAdmin, $staff1));
        $this->assertTrue($this->policy->view($superAdmin, $staff2));
        $this->assertTrue($this->policy->viewAny($superAdmin));
    }

    /**
     * Test: admin can view all staff (Level 1)
     *
     * Verifies that admin role can view staff from any company.
     */
    public function test_admin_can_view_all_staff()
    {
        $company1 = Company::factory()->create();
        $company2 = Company::factory()->create();
        $staff1 = Staff::factory()->create(['company_id' => $company1->id]);
        $staff2 = Staff::factory()->create(['company_id' => $company2->id]);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->assertTrue($this->policy->view($admin, $staff1));
        $this->assertTrue($this->policy->view($admin, $staff2));
    }

    /**
     * Test: company_owner can view all company staff (Level 2 + Level 5)
     *
     * Verifies that company_owner can view all staff within their company
     * but NOT staff from other companies (Level 2 company isolation).
     */
    public function test_company_owner_can_view_all_company_staff()
    {
        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();

        $branch1 = Branch::factory()->create(['company_id' => $company->id]);
        $branch2 = Branch::factory()->create(['company_id' => $company->id]);

        $staff1 = Staff::factory()->create(['company_id' => $company->id, 'branch_id' => $branch1->id]);
        $staff2 = Staff::factory()->create(['company_id' => $company->id, 'branch_id' => $branch2->id]);
        $otherStaff = Staff::factory()->create(['company_id' => $otherCompany->id]);

        $owner = User::factory()->create(['company_id' => $company->id]);
        $owner->assignRole('company_owner');

        // Owner can view all staff in their company
        $this->assertTrue($this->policy->view($owner, $staff1));
        $this->assertTrue($this->policy->view($owner, $staff2));

        // Owner CANNOT view staff from other companies (Level 2 isolation)
        $this->assertFalse($this->policy->view($owner, $otherStaff));
    }

    /**
     * Test: company_admin can view all company staff
     *
     * Verifies company_admin has same viewing rights as company_owner.
     */
    public function test_company_admin_can_view_all_company_staff()
    {
        $company = Company::factory()->create();
        $staff1 = Staff::factory()->create(['company_id' => $company->id]);
        $staff2 = Staff::factory()->create(['company_id' => $company->id]);

        $admin = User::factory()->create(['company_id' => $company->id]);
        $admin->assignRole('company_admin');

        $this->assertTrue($this->policy->view($admin, $staff1));
        $this->assertTrue($this->policy->view($admin, $staff2));
    }

    /**
     * Test: company_manager can view ONLY branch staff (Level 3)
     *
     * CRITICAL TEST: Verifies branch isolation for company_manager.
     * Manager should ONLY see staff in their assigned branch_id.
     */
    public function test_company_manager_can_view_only_branch_staff()
    {
        $company = Company::factory()->create();
        $assignedBranch = Branch::factory()->create(['company_id' => $company->id, 'name' => 'Assigned Branch']);
        $otherBranch = Branch::factory()->create(['company_id' => $company->id, 'name' => 'Other Branch']);

        $staffInBranch = Staff::factory()->create([
            'company_id' => $company->id,
            'branch_id' => $assignedBranch->id,
            'name' => 'Staff in assigned branch',
        ]);

        $staffInOtherBranch = Staff::factory()->create([
            'company_id' => $company->id,
            'branch_id' => $otherBranch->id,
            'name' => 'Staff in other branch',
        ]);

        $manager = User::factory()->create([
            'company_id' => $company->id,
            'branch_id' => $assignedBranch->id,
        ]);
        $manager->assignRole('company_manager');

        // Manager CAN view staff in their branch
        $this->assertTrue($this->policy->view($manager, $staffInBranch));

        // Manager CANNOT view staff in other branches (Level 3 branch isolation)
        $this->assertFalse($this->policy->view($manager, $staffInOtherBranch));
    }

    /**
     * Test: company_manager CANNOT view staff from other companies (Level 2)
     *
     * Verifies company isolation works for managers.
     */
    public function test_company_manager_cannot_view_other_company_staff()
    {
        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();

        $branch = Branch::factory()->create(['company_id' => $company->id]);
        $staffInCompany = Staff::factory()->create(['company_id' => $company->id, 'branch_id' => $branch->id]);
        $staffInOtherCompany = Staff::factory()->create(['company_id' => $otherCompany->id]);

        $manager = User::factory()->create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
        ]);
        $manager->assignRole('company_manager');

        // Manager can view staff in their company/branch
        $this->assertTrue($this->policy->view($manager, $staffInCompany));

        // Manager CANNOT view staff from other companies
        $this->assertFalse($this->policy->view($manager, $staffInOtherCompany));
    }

    /**
     * Test: company_staff can view ONLY own profile (Level 4)
     *
     * CRITICAL TEST: Verifies staff isolation.
     * Staff should ONLY see their own staff profile, not other staff.
     */
    public function test_company_staff_can_view_only_own_profile()
    {
        $company = Company::factory()->create();
        $branch = Branch::factory()->create(['company_id' => $company->id]);

        $ownStaff = Staff::factory()->create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'name' => 'Own Staff Profile',
        ]);

        $otherStaff = Staff::factory()->create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'name' => 'Other Staff Profile',
        ]);

        // User linked to their staff profile
        $staffUser = User::factory()->create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'staff_id' => $ownStaff->id, // Linked to staff profile
        ]);
        $staffUser->assignRole('company_staff');

        // Staff CAN view their own profile
        $this->assertTrue($this->policy->view($staffUser, $ownStaff));

        // Staff CANNOT view other staff profiles (Level 4 staff isolation)
        $this->assertFalse($this->policy->view($staffUser, $otherStaff));
    }

    /**
     * Test: company_staff CANNOT view other staff even in same branch
     *
     * Verifies strict staff isolation - even colleagues in same branch.
     */
    public function test_company_staff_cannot_view_colleagues()
    {
        $company = Company::factory()->create();
        $branch = Branch::factory()->create(['company_id' => $company->id]);

        $staff1 = Staff::factory()->create(['company_id' => $company->id, 'branch_id' => $branch->id]);
        $staff2 = Staff::factory()->create(['company_id' => $company->id, 'branch_id' => $branch->id]);

        $user1 = User::factory()->create([
            'company_id' => $company->id,
            'staff_id' => $staff1->id,
        ]);
        $user1->assignRole('company_staff');

        // User1 can view their own staff profile
        $this->assertTrue($this->policy->view($user1, $staff1));

        // User1 CANNOT view colleague's profile (staff2)
        $this->assertFalse($this->policy->view($user1, $staff2));
    }

    /**
     * Test: User without staff_id CANNOT view staff profiles
     *
     * Verifies that users without staff association are blocked
     * from viewing staff profiles.
     */
    public function test_user_without_staff_id_cannot_view_staff()
    {
        $company = Company::factory()->create();
        $staff = Staff::factory()->create(['company_id' => $company->id]);

        // User without staff_id
        $userWithoutStaff = User::factory()->create([
            'company_id' => $company->id,
            'staff_id' => null,
        ]);
        $userWithoutStaff->assignRole('company_staff');

        $this->assertFalse($this->policy->view($userWithoutStaff, $staff));
    }

    /**
     * Test: User without company_id CANNOT view any staff
     *
     * CRITICAL TEST: Verifies multi-tenancy security.
     */
    public function test_user_without_company_cannot_view_staff()
    {
        $company = Company::factory()->create();
        $staff = Staff::factory()->create(['company_id' => $company->id]);

        $userWithoutCompany = User::factory()->create(['company_id' => null]);
        $userWithoutCompany->assignRole('company_owner');

        $this->assertFalse($this->policy->view($userWithoutCompany, $staff));
    }

    /**
     * Test: viewAny permission for different roles
     *
     * Verifies that all customer portal roles can access staff list.
     */
    public function test_viewany_permission_for_customer_portal_roles()
    {
        $company = Company::factory()->create();
        $roles = ['company_owner', 'company_admin', 'company_manager'];

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
     * Test: 'staff' role (admin panel) can view own profile
     *
     * Backward compatibility: admin panel 'staff' role works same as 'company_staff'.
     */
    public function test_staff_role_can_view_own_profile()
    {
        $company = Company::factory()->create();
        $staff = Staff::factory()->create(['company_id' => $company->id]);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'staff_id' => $staff->id,
        ]);
        $user->assignRole('staff'); // Admin panel role

        $this->assertTrue($this->policy->view($user, $staff));
    }

    /**
     * Test: Phase 1 read-only restrictions (no create permission)
     *
     * Verifies customer portal roles CANNOT create staff in Phase 1.
     */
    public function test_customer_portal_roles_cannot_create_staff_in_phase_1()
    {
        $company = Company::factory()->create();
        $customerPortalRoles = ['company_owner', 'company_admin', 'company_manager', 'company_staff'];

        foreach ($customerPortalRoles as $roleName) {
            $user = User::factory()->create(['company_id' => $company->id]);
            $user->assignRole($roleName);

            $this->assertFalse(
                $this->policy->create($user),
                "Role {$roleName} should NOT be able to create staff in Phase 1"
            );
        }
    }

    /**
     * Test: admin panel roles CAN create staff
     *
     * Verifies admin panel roles maintain their create permissions.
     */
    public function test_admin_panel_roles_can_create_staff()
    {
        $company = Company::factory()->create();

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->assertTrue($this->policy->create($admin));

        $manager = User::factory()->create(['company_id' => $company->id]);
        $manager->assignRole('manager');
        $this->assertTrue($this->policy->create($manager));
    }

    /**
     * Test: Multi-level access control cascade
     *
     * Comprehensive test verifying all 4 levels work together.
     */
    public function test_multi_level_access_control_cascade()
    {
        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();

        $branch1 = Branch::factory()->create(['company_id' => $company->id]);
        $branch2 = Branch::factory()->create(['company_id' => $company->id]);

        $staff1 = Staff::factory()->create(['company_id' => $company->id, 'branch_id' => $branch1->id]);
        $staff2 = Staff::factory()->create(['company_id' => $company->id, 'branch_id' => $branch2->id]);
        $otherStaff = Staff::factory()->create(['company_id' => $otherCompany->id]);

        // Level 1: Admin bypass
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->assertTrue($this->policy->view($admin, $otherStaff));

        // Level 2: Company isolation
        $owner = User::factory()->create(['company_id' => $company->id]);
        $owner->assignRole('company_owner');
        $this->assertFalse($this->policy->view($owner, $otherStaff));

        // Level 3: Branch isolation for manager
        $manager = User::factory()->create([
            'company_id' => $company->id,
            'branch_id' => $branch1->id,
        ]);
        $manager->assignRole('company_manager');
        $this->assertTrue($this->policy->view($manager, $staff1));
        $this->assertFalse($this->policy->view($manager, $staff2));

        // Level 4: Staff self-access
        $staffUser = User::factory()->create([
            'company_id' => $company->id,
            'staff_id' => $staff1->id,
        ]);
        $staffUser->assignRole('company_staff');
        $this->assertTrue($this->policy->view($staffUser, $staff1));
        $this->assertFalse($this->policy->view($staffUser, $staff2));

        // Level 5: Owner can view all company staff
        $this->assertTrue($this->policy->view($owner, $staff1));
        $this->assertTrue($this->policy->view($owner, $staff2));
    }
}
