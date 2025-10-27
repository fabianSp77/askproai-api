<?php

namespace Tests\Unit\CustomerPortal;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Customer;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Staff;
use App\Models\User;
use App\Policies\CustomerPolicy;
use Spatie\Permission\Models\Role;

/**
 * CustomerPolicy Unit Tests
 *
 * Tests multi-level access control for Customer Portal Phase 1:
 * - Level 1: Admin bypass (super_admin can view all)
 * - Level 2: Company isolation (CRITICAL for multi-tenancy)
 * - Level 3: Branch isolation (company_manager sees only branch customers)
 * - Level 4: Staff isolation (company_staff sees only assigned customers)
 *
 * IMPORTANT: Tests VULN-005 fix - preferred_staff_id points to staff.id, NOT user.id
 */
class CustomerPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected CustomerPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new CustomerPolicy();

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
     * Test: super_admin can view all customers (Level 1 bypass)
     *
     * Verifies super_admin bypasses all other checks.
     */
    public function test_super_admin_can_view_all_customers()
    {
        $company1 = Company::factory()->create();
        $company2 = Company::factory()->create();
        $customer1 = Customer::factory()->create(['company_id' => $company1->id]);
        $customer2 = Customer::factory()->create(['company_id' => $company2->id]);

        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');

        $this->assertTrue($this->policy->view($superAdmin, $customer1));
        $this->assertTrue($this->policy->view($superAdmin, $customer2));
        $this->assertTrue($this->policy->viewAny($superAdmin));
    }

    /**
     * Test: admin can view all customers (Level 1)
     *
     * Verifies admin role can view customers from any company.
     */
    public function test_admin_can_view_all_customers()
    {
        $company1 = Company::factory()->create();
        $company2 = Company::factory()->create();
        $customer1 = Customer::factory()->create(['company_id' => $company1->id]);
        $customer2 = Customer::factory()->create(['company_id' => $company2->id]);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->assertTrue($this->policy->view($admin, $customer1));
        $this->assertTrue($this->policy->view($admin, $customer2));
    }

    /**
     * Test: company_owner can view all company customers (Level 2 + Level 5)
     *
     * Verifies company_owner can view all customers in their company
     * but NOT customers from other companies.
     */
    public function test_company_owner_can_view_all_company_customers()
    {
        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();

        $customer1 = Customer::factory()->create(['company_id' => $company->id]);
        $customer2 = Customer::factory()->create(['company_id' => $company->id]);
        $otherCustomer = Customer::factory()->create(['company_id' => $otherCompany->id]);

        $owner = User::factory()->create(['company_id' => $company->id]);
        $owner->assignRole('company_owner');

        // Owner can view all customers in their company
        $this->assertTrue($this->policy->view($owner, $customer1));
        $this->assertTrue($this->policy->view($owner, $customer2));

        // Owner CANNOT view customers from other companies (Level 2 isolation)
        $this->assertFalse($this->policy->view($owner, $otherCustomer));
    }

    /**
     * Test: company_admin can view all company customers
     *
     * Verifies company_admin has same viewing rights as company_owner.
     */
    public function test_company_admin_can_view_all_company_customers()
    {
        $company = Company::factory()->create();
        $customer1 = Customer::factory()->create(['company_id' => $company->id]);
        $customer2 = Customer::factory()->create(['company_id' => $company->id]);

        $admin = User::factory()->create(['company_id' => $company->id]);
        $admin->assignRole('company_admin');

        $this->assertTrue($this->policy->view($admin, $customer1));
        $this->assertTrue($this->policy->view($admin, $customer2));
    }

    /**
     * Test: company_manager can view ONLY branch customers (Level 3)
     *
     * CRITICAL TEST: Verifies branch isolation for company_manager.
     * Manager should ONLY see customers from their assigned branch_id.
     */
    public function test_company_manager_can_view_only_branch_customers()
    {
        $company = Company::factory()->create();
        $assignedBranch = Branch::factory()->create(['company_id' => $company->id, 'name' => 'Assigned Branch']);
        $otherBranch = Branch::factory()->create(['company_id' => $company->id, 'name' => 'Other Branch']);

        $customerInBranch = Customer::factory()->create([
            'company_id' => $company->id,
            'branch_id' => $assignedBranch->id,
            'name' => 'Customer in assigned branch',
        ]);

        $customerInOtherBranch = Customer::factory()->create([
            'company_id' => $company->id,
            'branch_id' => $otherBranch->id,
            'name' => 'Customer in other branch',
        ]);

        $manager = User::factory()->create([
            'company_id' => $company->id,
            'branch_id' => $assignedBranch->id,
        ]);
        $manager->assignRole('company_manager');

        // Manager CAN view customers in their branch
        $this->assertTrue($this->policy->view($manager, $customerInBranch));

        // Manager CANNOT view customers in other branches (Level 3 branch isolation)
        $this->assertFalse($this->policy->view($manager, $customerInOtherBranch));
    }

    /**
     * Test: company_manager CANNOT view customers from other companies (Level 2)
     *
     * Verifies company isolation works for managers.
     */
    public function test_company_manager_cannot_view_other_company_customers()
    {
        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();

        $branch = Branch::factory()->create(['company_id' => $company->id]);
        $customerInCompany = Customer::factory()->create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
        ]);
        $customerInOtherCompany = Customer::factory()->create(['company_id' => $otherCompany->id]);

        $manager = User::factory()->create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
        ]);
        $manager->assignRole('company_manager');

        // Manager can view customers in their company/branch
        $this->assertTrue($this->policy->view($manager, $customerInCompany));

        // Manager CANNOT view customers from other companies
        $this->assertFalse($this->policy->view($manager, $customerInOtherCompany));
    }

    /**
     * Test: company_staff can view ONLY assigned customers (Level 4)
     *
     * CRITICAL TEST: Verifies staff isolation.
     * Staff should ONLY see customers where preferred_staff_id === user.staff_id.
     *
     * VULN-005 FIX: preferred_staff_id points to staff.id, NOT user.id!
     */
    public function test_company_staff_can_view_only_assigned_customers()
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

        // Customer assigned to staff1
        $assignedCustomer = Customer::factory()->create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'preferred_staff_id' => $staff1->id, // Assigned to staff1 (staff.id)
            'name' => 'Assigned Customer',
        ]);

        // Customer assigned to staff2
        $otherCustomer = Customer::factory()->create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'preferred_staff_id' => $staff2->id, // Assigned to staff2
            'name' => 'Other Customer',
        ]);

        // User linked to staff1
        $staffUser = User::factory()->create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'staff_id' => $staff1->id, // Linked to staff1
        ]);
        $staffUser->assignRole('company_staff');

        // Staff CAN view assigned customers (where preferred_staff_id = staff1.id)
        $this->assertTrue($this->policy->view($staffUser, $assignedCustomer));

        // Staff CANNOT view other customers (Level 4 staff isolation)
        $this->assertFalse($this->policy->view($staffUser, $otherCustomer));
    }

    /**
     * Test: VULN-005 fix - preferred_staff_id matches staff.id, NOT user.id
     *
     * CRITICAL SECURITY TEST: Ensures the policy correctly checks
     * user.staff_id === customer.preferred_staff_id (NOT user.id).
     */
    public function test_vuln_005_fix_preferred_staff_id_uses_staff_id_not_user_id()
    {
        $company = Company::factory()->create();
        $branch = Branch::factory()->create(['company_id' => $company->id]);

        $staff = Staff::factory()->create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
        ]);

        // Customer with preferred_staff_id pointing to staff.id
        $customer = Customer::factory()->create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'preferred_staff_id' => $staff->id, // Points to staff.id (CORRECT)
        ]);

        // User linked to staff
        $staffUser = User::factory()->create([
            'company_id' => $company->id,
            'staff_id' => $staff->id, // User linked to staff
        ]);
        $staffUser->assignRole('company_staff');

        // Should match: user.staff_id ($staff->id) === customer.preferred_staff_id ($staff->id)
        $this->assertTrue($this->policy->view($staffUser, $customer));

        // Create customer with preferred_staff_id = user.id (WRONG - should NOT work)
        $wrongCustomer = Customer::factory()->create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'preferred_staff_id' => $staffUser->id, // WRONG: points to user.id instead of staff.id
        ]);

        // Should NOT match because preferred_staff_id != staff.id
        $this->assertFalse($this->policy->view($staffUser, $wrongCustomer));
    }

    /**
     * Test: company_staff CANNOT view customers without preferred_staff_id match
     *
     * Verifies strict staff isolation.
     */
    public function test_company_staff_cannot_view_unassigned_customers()
    {
        $company = Company::factory()->create();
        $branch = Branch::factory()->create(['company_id' => $company->id]);

        $staff = Staff::factory()->create(['company_id' => $company->id, 'branch_id' => $branch->id]);

        // Customer with NO preferred_staff_id (unassigned)
        $unassignedCustomer = Customer::factory()->create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'preferred_staff_id' => null,
        ]);

        $staffUser = User::factory()->create([
            'company_id' => $company->id,
            'staff_id' => $staff->id,
        ]);
        $staffUser->assignRole('company_staff');

        // Staff CANNOT view unassigned customers
        $this->assertFalse($this->policy->view($staffUser, $unassignedCustomer));
    }

    /**
     * Test: User without staff_id CANNOT view customers
     *
     * Verifies users without staff association are blocked.
     */
    public function test_user_without_staff_id_cannot_view_customers()
    {
        $company = Company::factory()->create();
        $customer = Customer::factory()->create(['company_id' => $company->id]);

        $userWithoutStaff = User::factory()->create([
            'company_id' => $company->id,
            'staff_id' => null,
        ]);
        $userWithoutStaff->assignRole('company_staff');

        $this->assertFalse($this->policy->view($userWithoutStaff, $customer));
    }

    /**
     * Test: User without company_id CANNOT view any customers
     *
     * CRITICAL TEST: Verifies multi-tenancy security.
     */
    public function test_user_without_company_cannot_view_customers()
    {
        $company = Company::factory()->create();
        $customer = Customer::factory()->create(['company_id' => $company->id]);

        $userWithoutCompany = User::factory()->create(['company_id' => null]);
        $userWithoutCompany->assignRole('company_owner');

        $this->assertFalse($this->policy->view($userWithoutCompany, $customer));
    }

    /**
     * Test: viewAny permission for different roles
     *
     * Verifies all customer portal roles can access customer list.
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
     * Test: 'staff' role (admin panel) works same as 'company_staff'
     *
     * Backward compatibility test.
     */
    public function test_staff_role_can_view_assigned_customers()
    {
        $company = Company::factory()->create();
        $branch = Branch::factory()->create(['company_id' => $company->id]);
        $staff = Staff::factory()->create(['company_id' => $company->id, 'branch_id' => $branch->id]);

        $customer = Customer::factory()->create([
            'company_id' => $company->id,
            'preferred_staff_id' => $staff->id,
        ]);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'staff_id' => $staff->id,
        ]);
        $user->assignRole('staff'); // Admin panel role

        $this->assertTrue($this->policy->view($user, $customer));
    }

    /**
     * Test: Phase 1 read-only restrictions (no create permission)
     *
     * Verifies customer portal roles CANNOT create customers in Phase 1.
     */
    public function test_customer_portal_roles_cannot_create_customers_in_phase_1()
    {
        $company = Company::factory()->create();
        $customerPortalRoles = ['company_owner', 'company_admin'];

        foreach ($customerPortalRoles as $roleName) {
            $user = User::factory()->create(['company_id' => $company->id]);
            $user->assignRole($roleName);

            $this->assertFalse(
                $this->policy->create($user),
                "Role {$roleName} should NOT be able to create customers in Phase 1"
            );
        }
    }

    /**
     * Test: admin panel roles CAN create customers
     *
     * Verifies admin panel roles maintain their create permissions.
     */
    public function test_admin_panel_roles_can_create_customers()
    {
        $company = Company::factory()->create();

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->assertTrue($this->policy->create($admin));

        $manager = User::factory()->create(['company_id' => $company->id]);
        $manager->assignRole('manager');
        $this->assertTrue($this->policy->create($manager));

        $staff = User::factory()->create(['company_id' => $company->id]);
        $staff->assignRole('staff');
        $this->assertTrue($this->policy->create($staff));
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

        $customer1 = Customer::factory()->create([
            'company_id' => $company->id,
            'branch_id' => $branch1->id,
            'preferred_staff_id' => $staff1->id,
        ]);
        $customer2 = Customer::factory()->create([
            'company_id' => $company->id,
            'branch_id' => $branch2->id,
        ]);
        $otherCustomer = Customer::factory()->create(['company_id' => $otherCompany->id]);

        // Level 1: Admin bypass
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->assertTrue($this->policy->view($admin, $otherCustomer));

        // Level 2: Company isolation
        $owner = User::factory()->create(['company_id' => $company->id]);
        $owner->assignRole('company_owner');
        $this->assertFalse($this->policy->view($owner, $otherCustomer));

        // Level 3: Branch isolation for manager
        $manager = User::factory()->create([
            'company_id' => $company->id,
            'branch_id' => $branch1->id,
        ]);
        $manager->assignRole('company_manager');
        $this->assertTrue($this->policy->view($manager, $customer1));
        $this->assertFalse($this->policy->view($manager, $customer2));

        // Level 4: Staff isolation
        $staffUser = User::factory()->create([
            'company_id' => $company->id,
            'staff_id' => $staff1->id,
        ]);
        $staffUser->assignRole('company_staff');
        $this->assertTrue($this->policy->view($staffUser, $customer1));

        // Level 5: Owner can view all company customers
        $this->assertTrue($this->policy->view($owner, $customer1));
        $this->assertTrue($this->policy->view($owner, $customer2));
    }
}
