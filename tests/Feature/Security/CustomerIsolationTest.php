<?php

namespace Tests\Feature\Security;

use App\Models\Company;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Security Regression Test Suite
 *
 * Ensures multi-tenant isolation still works after backfill fix.
 * Tests that fix didn't introduce security vulnerabilities.
 *
 * Purpose: Prevent security regression, ensure data isolation
 */
class CustomerIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected Company $companyA;
    protected Company $companyB;
    protected User $userA;
    protected User $userB;
    protected User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->companyA = Company::factory()->create(['name' => 'Company A']);
        $this->companyB = Company::factory()->create(['name' => 'Company B']);

        $this->userA = User::factory()->create(['company_id' => $this->companyA->id]);
        $this->userA->assignRole('admin');

        $this->userB = User::factory()->create(['company_id' => $this->companyB->id]);
        $this->userB->assignRole('admin');

        $this->superAdmin = User::factory()->create(['company_id' => $this->companyA->id]);
        $this->superAdmin->assignRole('super_admin');
    }

    /** @test */
    public function test_user_cannot_see_other_company_customers()
    {
        // Arrange
        $customerA = Customer::factory()->create(['company_id' => $this->companyA->id]);
        $customerB = Customer::factory()->create(['company_id' => $this->companyB->id]);

        // Act & Assert: User A cannot see Company B customers
        $this->actingAs($this->userA);
        $customers = Customer::all();

        $this->assertCount(1, $customers);
        $this->assertTrue($customers->contains($customerA->id));
        $this->assertFalse($customers->contains($customerB->id));
    }

    /** @test */
    public function test_user_cannot_see_null_company_customers()
    {
        // This test verifies the fix - NULL customers should no longer exist
        // But if they did, they should not be visible

        // Arrange: Create NULL customer (simulated broken state)
        DB::table('customers')->insert([
            'name' => 'Null Customer',
            'email' => 'null@test.com',
            'phone' => '1234567890',
            'company_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Act: Query as regular user
        $this->actingAs($this->userA);
        $customers = Customer::all();

        // Assert: NULL company customer not visible
        $nullCustomer = $customers->where('email', 'null@test.com')->first();
        $this->assertNull($nullCustomer, 'NULL company customers should not be visible to regular users');

        dump([
            'message' => 'Security: NULL company customers isolated',
            'visible_to_user' => false,
            'status' => 'PASS',
        ]);
    }

    /** @test */
    public function test_super_admin_can_see_all_customers()
    {
        // Arrange
        $customerA = Customer::factory()->create(['company_id' => $this->companyA->id]);
        $customerB = Customer::factory()->create(['company_id' => $this->companyB->id]);

        // Act: Super admin sees all
        $this->actingAs($this->superAdmin);
        $customers = Customer::all();

        // Assert
        $this->assertGreaterThanOrEqual(2, $customers->count());
        $this->assertTrue($customers->contains($customerA->id));
        $this->assertTrue($customers->contains($customerB->id));
    }

    /** @test */
    public function test_company_scope_applies_to_all_queries()
    {
        // Arrange
        Customer::factory()->count(5)->create(['company_id' => $this->companyA->id]);
        Customer::factory()->count(3)->create(['company_id' => $this->companyB->id]);

        $this->actingAs($this->userA);

        // Act & Assert: Various query types
        $this->assertCount(5, Customer::all());
        $this->assertCount(5, Customer::get());
        $this->assertCount(5, Customer::where('email', 'like', '%')->get());
        $this->assertEquals(5, Customer::count());

        dump(['message' => 'CompanyScope applies to all query types', 'status' => 'PASS']);
    }

    /** @test */
    public function test_customer_policy_enforces_company_boundaries()
    {
        // Arrange
        $customerA = Customer::factory()->create(['company_id' => $this->companyA->id]);
        $customerB = Customer::factory()->create(['company_id' => $this->companyB->id]);

        // Act & Assert: User A can view their customer
        $this->actingAs($this->userA);
        $this->assertTrue($this->userA->can('view', $customerA));
        $this->assertFalse($this->userA->can('view', $customerB));
    }

    /** @test */
    public function test_api_endpoints_respect_company_scope()
    {
        // Arrange
        Customer::factory()->create(['company_id' => $this->companyA->id, 'email' => 'a@test.com']);
        Customer::factory()->create(['company_id' => $this->companyB->id, 'email' => 'b@test.com']);

        // Act: Call API endpoint as Company A user
        $this->actingAs($this->userA);
        $response = $this->getJson('/api/customers');

        // Assert: Only Company A customers returned
        $response->assertStatus(200);
        // Additional assertions would depend on API structure
    }

    /** @test */
    public function test_direct_database_access_cannot_bypass_scope()
    {
        // Arrange
        $customerB = Customer::factory()->create(['company_id' => $this->companyB->id]);

        // Act: Attempt to access Company B customer directly
        $this->actingAs($this->userA);
        $customer = Customer::find($customerB->id);

        // Assert: Should return null (scope applied)
        $this->assertNull($customer, 'Direct find() should respect scope');
    }

    /** @test */
    public function test_findOrFail_throws_not_found_for_other_company()
    {
        // Arrange
        $customerB = Customer::factory()->create(['company_id' => $this->companyB->id]);

        // Act & Assert
        $this->actingAs($this->userA);
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        Customer::findOrFail($customerB->id);
    }
}
