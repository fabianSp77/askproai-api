<?php

namespace Tests\Unit\CustomerPortal;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use App\Policies\BranchPolicy;
use Spatie\Permission\Models\Role;

/**
 * BranchPolicy Unit Tests
 *
 * Tests multi-level access control for Customer Portal Phase 1:
 * - Level 1: Admin bypass (super_admin can view all)
 * - Level 2: Company isolation (CRITICAL for multi-tenancy)
 * - Level 3: Branch isolation (company_manager sees only their branch)
 * - Level 4: Owner/Admin access (company_owner/company_admin see all company branches)
 */
class BranchPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected BranchPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new BranchPolicy();

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
    }

    /**
     * Test: super_admin can view all branches (Level 1 bypass)
     *
     * Verifies that super_admin role bypasses all other checks
     * and can view branches from any company.
     */
    public function test_super_admin_can_view_all_branches()
    {
        // Create two companies with branches
        $company1 = Company::factory()->create();
        $company2 = Company::factory()->create();
        $branch1 = Branch::factory()->create(['company_id' => $company1->id]);
        $branch2 = Branch::factory()->create(['company_id' => $company2->id]);

        // Create super_admin user
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');

        // Super admin should be able to view any branch
        $this->assertTrue($this->policy->view($superAdmin, $branch1));
        $this->assertTrue($this->policy->view($superAdmin, $branch2));
        $this->assertTrue($this->policy->viewAny($superAdmin));
    }

    /**
     * Test: admin can view all branches (Level 1)
     *
     * Verifies that admin role can view branches from any company.
     */
    public function test_admin_can_view_all_branches()
    {
        $company1 = Company::factory()->create();
        $company2 = Company::factory()->create();
        $branch1 = Branch::factory()->create(['company_id' => $company1->id]);
        $branch2 = Branch::factory()->create(['company_id' => $company2->id]);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->assertTrue($this->policy->view($admin, $branch1));
        $this->assertTrue($this->policy->view($admin, $branch2));
    }

    /**
     * Test: company_owner can view all company branches (Level 4)
     *
     * Verifies that company_owner can view all branches within their company
     * but NOT branches from other companies (Level 2 company isolation).
     */
    public function test_company_owner_can_view_all_company_branches()
    {
        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();

        $branch1 = Branch::factory()->create(['company_id' => $company->id, 'name' => 'Branch 1']);
        $branch2 = Branch::factory()->create(['company_id' => $company->id, 'name' => 'Branch 2']);
        $otherBranch = Branch::factory()->create(['company_id' => $otherCompany->id]);

        $owner = User::factory()->create(['company_id' => $company->id]);
        $owner->assignRole('company_owner');

        // Owner can view all branches in their company
        $this->assertTrue($this->policy->view($owner, $branch1));
        $this->assertTrue($this->policy->view($owner, $branch2));

        // Owner CANNOT view branches from other companies (Level 2 isolation)
        $this->assertFalse($this->policy->view($owner, $otherBranch));
    }

    /**
     * Test: company_admin can view all company branches (Level 4)
     *
     * Verifies that company_admin has same branch viewing rights as company_owner.
     */
    public function test_company_admin_can_view_all_company_branches()
    {
        $company = Company::factory()->create();
        $branch1 = Branch::factory()->create(['company_id' => $company->id]);
        $branch2 = Branch::factory()->create(['company_id' => $company->id]);

        $admin = User::factory()->create(['company_id' => $company->id]);
        $admin->assignRole('company_admin');

        $this->assertTrue($this->policy->view($admin, $branch1));
        $this->assertTrue($this->policy->view($admin, $branch2));
    }

    /**
     * Test: company_manager can view ONLY assigned branch (Level 3)
     *
     * CRITICAL TEST: Verifies branch isolation for company_manager.
     * Manager should ONLY see their assigned branch_id, not other branches
     * even within the same company.
     */
    public function test_company_manager_can_view_only_assigned_branch()
    {
        $company = Company::factory()->create();
        $assignedBranch = Branch::factory()->create(['company_id' => $company->id, 'name' => 'Assigned Branch']);
        $otherBranch = Branch::factory()->create(['company_id' => $company->id, 'name' => 'Other Branch']);

        $manager = User::factory()->create([
            'company_id' => $company->id,
            'branch_id' => $assignedBranch->id, // Manager is assigned to specific branch
        ]);
        $manager->assignRole('company_manager');

        // Manager CAN view assigned branch
        $this->assertTrue($this->policy->view($manager, $assignedBranch));

        // Manager CANNOT view other branches (Level 3 branch isolation)
        $this->assertFalse($this->policy->view($manager, $otherBranch));
    }

    /**
     * Test: company_manager CANNOT view other company branches (Level 2)
     *
     * Verifies company isolation works even for managers.
     */
    public function test_company_manager_cannot_view_other_company_branches()
    {
        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();

        $assignedBranch = Branch::factory()->create(['company_id' => $company->id]);
        $otherCompanyBranch = Branch::factory()->create(['company_id' => $otherCompany->id]);

        $manager = User::factory()->create([
            'company_id' => $company->id,
            'branch_id' => $assignedBranch->id,
        ]);
        $manager->assignRole('company_manager');

        // Manager can view their assigned branch
        $this->assertTrue($this->policy->view($manager, $assignedBranch));

        // Manager CANNOT view branches from other companies
        $this->assertFalse($this->policy->view($manager, $otherCompanyBranch));
    }

    /**
     * Test: User without company_id CANNOT view any branches (Level 2 isolation)
     *
     * CRITICAL TEST: Verifies that users without company association
     * are blocked from viewing any branches (multi-tenancy security).
     */
    public function test_user_without_company_cannot_view_branches()
    {
        $company = Company::factory()->create();
        $branch = Branch::factory()->create(['company_id' => $company->id]);

        // User with NO company_id (should be blocked)
        $userWithoutCompany = User::factory()->create(['company_id' => null]);
        $userWithoutCompany->assignRole('company_owner');

        $this->assertFalse($this->policy->view($userWithoutCompany, $branch));
    }

    /**
     * Test: viewAny permission for different roles
     *
     * Verifies that all customer portal roles can access the branch list,
     * but actual viewing is controlled by view() policy.
     */
    public function test_viewany_permission_for_customer_portal_roles()
    {
        $company = Company::factory()->create();

        // Test all customer portal roles
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
     * Test: company_manager without branch_id can view all company branches
     *
     * Backward compatibility: Managers without branch assignment
     * can view all company branches (fallback behavior).
     */
    public function test_company_manager_without_branch_id_can_view_all_company_branches()
    {
        $company = Company::factory()->create();
        $branch1 = Branch::factory()->create(['company_id' => $company->id]);
        $branch2 = Branch::factory()->create(['company_id' => $company->id]);

        // Manager WITHOUT branch_id assignment
        $manager = User::factory()->create([
            'company_id' => $company->id,
            'branch_id' => null, // No branch assigned
        ]);
        $manager->assignRole('company_manager');

        // Should be able to view all company branches (backward compatibility)
        $this->assertTrue($this->policy->view($manager, $branch1));
        $this->assertTrue($this->policy->view($manager, $branch2));
    }

    /**
     * Test: Phase 1 read-only restrictions (no create permission)
     *
     * Verifies that customer portal roles CANNOT create branches in Phase 1.
     * Only admin panel roles can create.
     */
    public function test_customer_portal_roles_cannot_create_branches_in_phase_1()
    {
        $company = Company::factory()->create();

        $customerPortalRoles = ['company_owner', 'company_admin', 'company_manager', 'company_staff'];

        foreach ($customerPortalRoles as $roleName) {
            $user = User::factory()->create(['company_id' => $company->id]);
            $user->assignRole($roleName);

            $this->assertFalse(
                $this->policy->create($user),
                "Role {$roleName} should NOT be able to create branches in Phase 1"
            );
        }
    }

    /**
     * Test: admin panel roles CAN create branches
     *
     * Verifies that admin panel roles maintain their create permissions.
     */
    public function test_admin_panel_roles_can_create_branches()
    {
        $company = Company::factory()->create();

        // Admin can create
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->assertTrue($this->policy->create($admin));

        // Manager (admin panel role) can create
        $manager = User::factory()->create(['company_id' => $company->id]);
        $manager->assignRole('manager');
        $this->assertTrue($this->policy->create($manager));
    }

    /**
     * Test: Multi-level access control cascade
     *
     * Comprehensive test verifying all 4 levels work together correctly.
     */
    public function test_multi_level_access_control_cascade()
    {
        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();

        $branch1 = Branch::factory()->create(['company_id' => $company->id]);
        $branch2 = Branch::factory()->create(['company_id' => $company->id]);
        $otherBranch = Branch::factory()->create(['company_id' => $otherCompany->id]);

        // Level 1: Admin bypass
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->assertTrue($this->policy->view($admin, $otherBranch));

        // Level 2: Company isolation blocks access
        $owner = User::factory()->create(['company_id' => $company->id]);
        $owner->assignRole('company_owner');
        $this->assertFalse($this->policy->view($owner, $otherBranch));

        // Level 3: Branch isolation for manager
        $manager = User::factory()->create([
            'company_id' => $company->id,
            'branch_id' => $branch1->id,
        ]);
        $manager->assignRole('company_manager');
        $this->assertTrue($this->policy->view($manager, $branch1));
        $this->assertFalse($this->policy->view($manager, $branch2));

        // Level 4: Owner can view all company branches
        $this->assertTrue($this->policy->view($owner, $branch1));
        $this->assertTrue($this->policy->view($owner, $branch2));
    }
}
