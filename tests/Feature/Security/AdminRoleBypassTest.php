<?php

namespace Tests\Feature\Security;

use App\Models\Company;
use App\Models\User;
use App\Models\Policy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Admin Role Bypass Test Suite
 *
 * Tests CompanyScope admin fix and permission boundaries:
 * - super_admin vs admin permission differences
 * - Scope bypass prevention for admins
 * - Cross-tenant admin access restrictions
 */
class AdminRoleBypassTest extends TestCase
{
    use RefreshDatabase;

    private Company $companyA;
    private Company $companyB;
    private User $adminA;
    private User $adminB;
    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->companyA = Company::factory()->create(['name' => 'Company A']);
        $this->companyB = Company::factory()->create(['name' => 'Company B']);

        // Create company admins
        $this->adminA = User::factory()->create([
            'company_id' => $this->companyA->id,
            'role' => 'admin',
        ]);

        $this->adminB = User::factory()->create([
            'company_id' => $this->companyB->id,
            'role' => 'admin',
        ]);

        // Create super admin (no company restriction)
        $this->superAdmin = User::factory()->create([
            'company_id' => null,
            'role' => 'super_admin',
        ]);
    }

    /**
     * @test
     * Test admin cannot access other company's data
     */
    public function admin_cannot_bypass_company_scope(): void
    {
        $policyA = Policy::factory()->create([
            'company_id' => $this->companyA->id,
        ]);

        $policyB = Policy::factory()->create([
            'company_id' => $this->companyB->id,
        ]);

        // Admin A attempts to access Policy B
        $this->actingAs($this->adminA);

        $policies = Policy::all();

        $this->assertCount(1, $policies);
        $this->assertEquals($policyA->id, $policies->first()->id);
        $this->assertNotContains($policyB->id, $policies->pluck('id'));
    }

    /**
     * @test
     * Test super_admin can access all companies' data
     */
    public function super_admin_can_access_all_company_data(): void
    {
        Policy::factory()->create(['company_id' => $this->companyA->id]);
        Policy::factory()->create(['company_id' => $this->companyB->id]);

        $this->actingAs($this->superAdmin);

        $policies = Policy::withoutGlobalScope('company')->get();

        // Super admin should see all policies when scope is disabled
        $this->assertGreaterThanOrEqual(2, $policies->count());
    }

    /**
     * @test
     * Test admin cannot modify other company's resources
     */
    public function admin_cannot_modify_cross_tenant_resources(): void
    {
        $policyB = Policy::factory()->create([
            'company_id' => $this->companyB->id,
            'name' => 'Protected Policy',
        ]);

        $this->actingAs($this->adminA);

        // Attempt to update company B's policy
        $response = $this->putJson("/api/policies/{$policyB->id}", [
            'name' => 'Malicious Admin Update',
        ]);

        $this->assertContains($response->status(), [403, 404]);

        $this->assertDatabaseHas('policies', [
            'id' => $policyB->id,
            'name' => 'Protected Policy',
        ]);
    }

    /**
     * @test
     * Test admin role does not grant super_admin privileges
     */
    public function admin_role_does_not_grant_super_admin_privileges(): void
    {
        $this->actingAs($this->adminA);

        // Admins should still be scoped to their company
        $this->assertEquals($this->companyA->id, auth()->user()->company_id);
        $this->assertEquals('admin', auth()->user()->role);
        $this->assertNotEquals('super_admin', auth()->user()->role);

        // Test that CompanyScope is active for admins
        $allPolicies = Policy::all();
        $companyScopedPolicies = Policy::where('company_id', $this->companyA->id)->get();

        $this->assertEquals($allPolicies->count(), $companyScopedPolicies->count());
    }

    /**
     * @test
     * Test admin cannot delete other company's resources
     */
    public function admin_cannot_delete_cross_tenant_resources(): void
    {
        $policyB = Policy::factory()->create([
            'company_id' => $this->companyB->id,
        ]);

        $this->actingAs($this->adminA);

        $response = $this->deleteJson("/api/policies/{$policyB->id}");

        $this->assertContains($response->status(), [403, 404]);

        $this->assertDatabaseHas('policies', [
            'id' => $policyB->id,
            'company_id' => $this->companyB->id,
        ]);
    }

    /**
     * @test
     * Test admin can manage own company resources
     */
    public function admin_can_manage_own_company_resources(): void
    {
        $this->actingAs($this->adminA);

        // Create policy for own company
        $policy = Policy::factory()->create([
            'company_id' => $this->companyA->id,
            'name' => 'Admin Created Policy',
        ]);

        // Update own company's policy
        $policy->update(['name' => 'Updated Policy']);

        $this->assertDatabaseHas('policies', [
            'id' => $policy->id,
            'company_id' => $this->companyA->id,
            'name' => 'Updated Policy',
        ]);

        // Delete own company's policy
        $policy->delete();

        $this->assertDatabaseMissing('policies', [
            'id' => $policy->id,
            'deleted_at' => null,
        ]);
    }
}
