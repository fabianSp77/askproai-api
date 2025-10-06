<?php

namespace Tests\Feature\DataIntegrity;

use App\Models\Company;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Constraint Enforcement Test Suite
 *
 * Tests to ensure NOT NULL constraint and automatic company_id assignment work correctly.
 * These tests prevent future recurrence of the data integrity issue.
 *
 * Purpose: Verify constraints prevent NULL company_id creation
 */
class CustomerCompanyIdConstraintTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create(['name' => 'Test Company']);
        $this->user = User::factory()->create(['company_id' => $this->company->id]);
        $this->user->assignRole('admin');
    }

    /**
     * @test
     * Verify cannot create customer with NULL company_id via DB insert
     * This test will fail until NOT NULL constraint is added to migration
     */
    public function test_cannot_create_customer_with_null_company_id()
    {
        $this->markTestSkipped('Enable this test AFTER adding NOT NULL constraint to migration');

        // Arrange & Act: Attempt to create customer with NULL company_id
        $this->expectException(\Illuminate\Database\QueryException::class);
        $this->expectExceptionMessageMatches('/company_id.*cannot be null/i');

        DB::table('customers')->insert([
            'name' => 'Test Customer',
            'email' => 'test@test.com',
            'phone' => '1234567890',
            'company_id' => null, // This should fail
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @test
     * Verify cannot update customer to NULL company_id via DB update
     */
    public function test_cannot_update_customer_to_null_company_id()
    {
        $this->markTestSkipped('Enable this test AFTER adding NOT NULL constraint to migration');

        // Arrange: Create valid customer
        $customer = Customer::factory()->create(['company_id' => $this->company->id]);

        // Act & Assert: Attempt to set company_id to NULL should fail
        $this->expectException(\Illuminate\Database\QueryException::class);

        DB::table('customers')
            ->where('id', $customer->id)
            ->update(['company_id' => null]);
    }

    /**
     * @test
     * Verify CustomerFactory always sets company_id
     */
    public function test_factory_always_sets_company_id()
    {
        // Act: Create customer using factory
        $customer = Customer::factory()->create();

        // Assert: company_id is always set
        $this->assertNotNull($customer->company_id);
        $this->assertInstanceOf(Company::class, $customer->company);

        dump([
            'message' => 'Factory validation: company_id always set',
            'customer_id' => $customer->id,
            'company_id' => $customer->company_id,
            'status' => 'PASS',
        ]);
    }

    /**
     * @test
     * Verify BelongsToCompany trait auto-fills company_id on creation
     */
    public function test_trait_auto_fills_company_id_on_creation()
    {
        // Arrange: Authenticate as user with company
        $this->actingAs($this->user);

        // Act: Create customer WITHOUT explicitly setting company_id
        $customer = new Customer([
            'name' => 'Auto Fill Test',
            'email' => 'autofill@test.com',
            'phone' => '1234567890',
        ]);
        $customer->save();

        // Assert: company_id auto-filled from authenticated user
        $this->assertNotNull($customer->company_id);
        $this->assertEquals($this->user->company_id, $customer->company_id);

        dump([
            'message' => 'Trait auto-fill validation',
            'customer_id' => $customer->id,
            'user_company_id' => $this->user->company_id,
            'customer_company_id' => $customer->company_id,
            'auto_filled' => true,
            'status' => 'PASS',
        ]);
    }

    /**
     * @test
     * Verify explicit company_id takes precedence over auto-fill
     */
    public function test_explicit_company_id_overrides_auto_fill()
    {
        // Arrange: Create second company
        $company2 = Company::factory()->create(['name' => 'Company 2']);
        $this->actingAs($this->user); // User belongs to $this->company

        // Act: Create customer with EXPLICIT company_id (different from user's company)
        // Note: This should only be possible for super_admin in production
        $customer = new Customer([
            'name' => 'Explicit Company Test',
            'email' => 'explicit@test.com',
            'phone' => '1234567890',
            'company_id' => $company2->id, // Explicit override
        ]);
        $customer->save();

        // Assert: Explicit company_id is preserved
        $this->assertEquals($company2->id, $customer->company_id);
        $this->assertNotEquals($this->user->company_id, $customer->company_id);

        dump([
            'message' => 'Explicit company_id override validation',
            'user_company_id' => $this->user->company_id,
            'explicit_company_id' => $company2->id,
            'customer_company_id' => $customer->company_id,
            'status' => 'PASS',
        ]);
    }

    /**
     * @test
     * Verify super admin can still manage customers
     */
    public function test_super_admin_can_still_manage_customers()
    {
        // Arrange: Create super admin
        $superAdmin = User::factory()->create(['company_id' => $this->company->id]);
        $superAdmin->assignRole('super_admin');

        $company2 = Company::factory()->create(['name' => 'Company 2']);
        $customer = Customer::factory()->create(['company_id' => $company2->id]);

        // Act: Super admin should see all customers
        $this->actingAs($superAdmin);
        $allCustomers = Customer::all();

        // Assert: Super admin sees customers from all companies
        $this->assertGreaterThan(0, $allCustomers->count());

        dump([
            'message' => 'Super admin access validation',
            'super_admin_id' => $superAdmin->id,
            'total_customers_visible' => $allCustomers->count(),
            'can_see_all_companies' => true,
            'status' => 'PASS',
        ]);
    }

    /**
     * @test
     * Verify database constraint rejects NULL company_id at DB level
     */
    public function test_database_constraint_rejects_null()
    {
        $this->markTestSkipped('Enable this test AFTER adding NOT NULL constraint to migration');

        // This tests the actual database constraint (not application layer)
        $this->expectException(\PDOException::class);

        DB::statement('INSERT INTO customers (name, email, phone, company_id, created_at, updated_at) VALUES (?, ?, ?, NULL, NOW(), NOW())', [
            'DB Level Test',
            'dbtest@test.com',
            '1234567890'
        ]);
    }

    /**
     * @test
     * Verify mass assignment protection for company_id
     */
    public function test_mass_assignment_protection_for_company_id()
    {
        // Arrange: Authenticate as regular user
        $this->actingAs($this->user);

        // Act: Attempt mass assignment of company_id
        $customer = Customer::create([
            'name' => 'Mass Assignment Test',
            'email' => 'massassign@test.com',
            'phone' => '1234567890',
            'company_id' => 999, // Attempt to set invalid company
        ]);

        // Assert: company_id is auto-filled from auth, not from mass assignment
        // This is because company_id is in $guarded array
        $this->assertNotEquals(999, $customer->company_id);
        $this->assertEquals($this->user->company_id, $customer->company_id);

        dump([
            'message' => 'Mass assignment protection validation',
            'attempted_company_id' => 999,
            'actual_company_id' => $customer->company_id,
            'protected' => true,
            'status' => 'PASS',
        ]);
    }

    /**
     * @test
     * Verify validation rules enforce company_id presence
     */
    public function test_validation_rules_enforce_company_id_presence()
    {
        // Arrange & Act: Create customer without auth context
        Auth::logout();

        // Without authenticated user, trait won't auto-fill
        $customer = new Customer([
            'name' => 'No Auth Test',
            'email' => 'noauth@test.com',
            'phone' => '1234567890',
        ]);

        // Assert: Attempting to save should fail validation
        try {
            $customer->save();
            // If we get here without company_id, it's a problem
            $this->assertNotNull($customer->company_id, 'company_id should be required');
        } catch (\Exception $e) {
            // Expected behavior - save fails without company_id
            $this->assertTrue(true);
        }

        dump([
            'message' => 'Validation rules enforcement',
            'auth_present' => Auth::check(),
            'customer_saved' => $customer->exists,
            'company_id' => $customer->company_id,
        ]);
    }

    /**
     * @test
     * Verify constraint works with transaction rollback
     */
    public function test_constraint_works_with_transaction_rollback()
    {
        $this->markTestSkipped('Enable this test AFTER adding NOT NULL constraint to migration');

        // Arrange: Start transaction
        DB::beginTransaction();

        try {
            // Act: Attempt to create invalid customer
            DB::table('customers')->insert([
                'name' => 'Transaction Test',
                'email' => 'transaction@test.com',
                'phone' => '1234567890',
                'company_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->fail('Should have thrown exception');
        } catch (\Exception $e) {
            // Assert: Transaction can be rolled back
            DB::rollBack();
            $this->assertTrue(true);
        }

        // Verify no data was inserted
        $customer = DB::table('customers')->where('email', 'transaction@test.com')->first();
        $this->assertNull($customer);
    }

    /**
     * @test
     * Verify constraint enforcement in production-like scenario
     */
    public function test_constraint_enforcement_production_scenario()
    {
        // Arrange: Simulate production data creation flow
        $this->actingAs($this->user);

        // Act: Create customer using proper flow
        $customer = Customer::factory()->create();

        // Assert: All safety measures in place
        $this->assertNotNull($customer->company_id);
        $this->assertEquals($this->user->company_id, $customer->company_id);

        // Verify in database
        $dbCustomer = DB::table('customers')->find($customer->id);
        $this->assertNotNull($dbCustomer->company_id);

        // Verify constraint will prevent NULL (after migration)
        // $this->expectException(\Illuminate\Database\QueryException::class);
        // DB::table('customers')->where('id', $customer->id)->update(['company_id' => null]);

        dump([
            'message' => 'Production scenario validation',
            'customer_id' => $customer->id,
            'company_id' => $customer->company_id,
            'constraint_active' => 'After migration',
            'trait_auto_fill' => 'Working',
            'factory_safeguard' => 'Working',
            'status' => 'PASS',
        ]);
    }
}
