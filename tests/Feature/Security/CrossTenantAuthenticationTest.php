<?php

namespace Tests\Feature\Security;

use App\Models\Customer;
use App\Models\Appointment;
use App\Models\Call;
use App\Models\Branch;
use App\Models\Service;
use Illuminate\Support\Facades\Auth;

/**
 * Cross-Tenant Authentication Security Test
 * 
 * Critical vulnerability tests to ensure complete tenant isolation
 * and prevent unauthorized cross-company data access.
 * 
 * SEVERITY: CRITICAL - Data breach potential
 */
class CrossTenantAuthenticationTest extends BaseSecurityTestCase
{
    public function test_admin_users_cannot_access_other_company_data()
    {
        $company1Data = $this->createTestData($this->company1);
        $company2Data = $this->createTestData($this->company2);

        // Test each model type for cross-tenant access
        $this->assertCrossTenantAccessPrevented(
            Customer::class,
            $company2Data['customer']->id,
            $this->admin1
        );

        $this->assertCrossTenantAccessPrevented(
            Appointment::class,
            $company2Data['appointment']->id,
            $this->admin1
        );

        $this->assertCrossTenantAccessPrevented(
            Call::class,
            $company2Data['call']->id,
            $this->admin1
        );

        $this->assertCrossTenantAccessPrevented(
            Branch::class,
            $company2Data['branch']->id,
            $this->admin1
        );

        $this->assertCrossTenantAccessPrevented(
            Service::class,
            $company2Data['service']->id,
            $this->admin1
        );

        $this->logSecurityTestResult('admin_cross_tenant_access', true);
    }

    public function test_portal_users_cannot_access_other_company_data()
    {
        $company1Data = $this->createTestData($this->company1);
        $company2Data = $this->createTestData($this->company2);

        // Test portal user isolation
        $this->assertCrossTenantAccessPrevented(
            Customer::class,
            $company2Data['customer']->id,
            $this->portalUser1,
            'portal'
        );

        $this->assertCrossTenantAccessPrevented(
            Appointment::class,
            $company2Data['appointment']->id,
            $this->portalUser1,
            'portal'
        );

        $this->logSecurityTestResult('portal_user_cross_tenant_access', true);
    }

    public function test_staff_users_cannot_access_other_company_data()
    {
        $company1Data = $this->createTestData($this->company1);
        $company2Data = $this->createTestData($this->company2);

        // Test staff user isolation (most restrictive)
        $this->assertCrossTenantAccessPrevented(
            Customer::class,
            $company2Data['customer']->id,
            $this->staffUser1,
            'portal'
        );

        $this->assertCrossTenantAccessPrevented(
            Appointment::class,
            $company2Data['appointment']->id,
            $this->staffUser1,
            'portal'
        );

        $this->logSecurityTestResult('staff_user_cross_tenant_access', true);
    }

    public function test_direct_model_queries_respect_tenant_scope()
    {
        // Create customers for both companies
        $customer1 = Customer::factory()->create([
            'company_id' => $this->company1->id,
            'email' => 'test1@company1.com',
            'name' => 'Company 1 Customer',
        ]);

        $customer2 = Customer::factory()->create([
            'company_id' => $this->company2->id,
            'email' => 'test2@company2.com',
            'name' => 'Company 2 Customer',
        ]);

        // Login as company 1 admin
        $this->actingAs($this->admin1);

        // Various query methods should only return company 1 data
        $allCustomers = Customer::all();
        $this->assertTrue($allCustomers->every(fn($c) => $c->company_id === $this->company1->id));

        $foundCustomer = Customer::where('name', 'Company 2 Customer')->first();
        $this->assertNull($foundCustomer, 'Cross-tenant data accessible via where()');

        $customerCount = Customer::count();
        $this->assertEquals(1, $customerCount, 'Count includes cross-tenant data');

        $customerExists = Customer::where('email', 'test2@company2.com')->exists();
        $this->assertFalse($customerExists, 'Cross-tenant data exists() returns true');

        $pluckedEmails = Customer::pluck('email');
        $this->assertNotContains('test2@company2.com', $pluckedEmails->toArray());

        $this->logSecurityTestResult('direct_model_queries_tenant_scope', true);
    }

    public function test_raw_database_queries_respect_tenant_scope()
    {
        // Create test data
        Customer::factory()->create([
            'company_id' => $this->company1->id,
            'email' => 'raw1@company1.com',
        ]);

        Customer::factory()->create([
            'company_id' => $this->company2->id,
            'email' => 'raw2@company2.com',
        ]);

        $this->actingAs($this->admin1);

        // Raw queries should still be scoped
        $rawResults = Customer::whereRaw("email LIKE '%@company2.com'")->get();
        $this->assertCount(0, $rawResults, 'Raw query bypassed tenant scope');

        $selectResults = Customer::selectRaw('email, company_id')->get();
        $this->assertTrue($selectResults->every(fn($c) => $c->company_id === $this->company1->id));

        $this->logSecurityTestResult('raw_queries_tenant_scope', true);
    }

    public function test_relationship_queries_respect_tenant_scope()
    {
        // Create complex relationship data
        $branch1 = Branch::factory()->create(['company_id' => $this->company1->id]);
        $service1 = Service::factory()->create(['company_id' => $this->company1->id]);
        
        $branch2 = Branch::factory()->create(['company_id' => $this->company2->id]);
        $service2 = Service::factory()->create(['company_id' => $this->company2->id]);

        $appointment1 = Appointment::factory()->create([
            'company_id' => $this->company1->id,
            'branch_id' => $branch1->id,
        ]);

        $appointment2 = Appointment::factory()->create([
            'company_id' => $this->company2->id,
            'branch_id' => $branch2->id,
        ]);

        $this->actingAs($this->admin1);

        // Relationship queries should be scoped
        $branches = Branch::with('appointments')->get();
        $this->assertCount(1, $branches);
        $this->assertEquals($this->company1->id, $branches->first()->company_id);

        // Nested relationship queries
        $appointments = Appointment::with('branch')->get();
        $this->assertCount(1, $appointments);
        $this->assertEquals($this->company1->id, $appointments->first()->branch->company_id);

        $this->logSecurityTestResult('relationship_queries_tenant_scope', true);
    }

    public function test_bulk_operations_respect_tenant_scope()
    {
        // Create customers for both companies
        Customer::factory()->create([
            'company_id' => $this->company1->id,
            'phone' => '+49123456789',
            'name' => 'Bulk Test 1',
        ]);

        Customer::factory()->create([
            'company_id' => $this->company2->id,
            'phone' => '+49987654321',
            'name' => 'Bulk Test 2',
        ]);

        $this->actingAs($this->admin1);

        // Bulk update should only affect current tenant
        $affectedRows = Customer::where('name', 'LIKE', 'Bulk Test%')
            ->update(['phone' => '+49000000000']);

        $this->assertEquals(1, $affectedRows, 'Bulk update affected wrong tenant');

        // Verify only company 1 customer was updated
        $customer1 = Customer::where('name', 'Bulk Test 1')->first();
        $this->assertEquals('+49000000000', $customer1->phone);

        // Switch to company 2 and verify their data wasn't affected
        Auth::logout();
        $this->actingAs($this->admin2);

        $customer2 = Customer::where('name', 'Bulk Test 2')->first();
        $this->assertEquals('+49987654321', $customer2->phone);

        $this->logSecurityTestResult('bulk_operations_tenant_scope', true);
    }

    public function test_soft_delete_operations_respect_tenant_scope()
    {
        // Skip if models don't use soft deletes
        if (!method_exists(Customer::class, 'trashed')) {
            $this->markTestSkipped('Customer model does not use soft deletes');
        }

        // Create and soft delete customers
        $customer1 = Customer::factory()->create([
            'company_id' => $this->company1->id,
            'name' => 'Soft Delete Test 1',
        ]);

        $customer2 = Customer::factory()->create([
            'company_id' => $this->company2->id,
            'name' => 'Soft Delete Test 2',
        ]);

        $customer1->delete();
        $customer2->delete();

        // Test trashed records isolation
        $this->actingAs($this->admin1);

        $trashedCustomers = Customer::onlyTrashed()->get();
        $this->assertCount(1, $trashedCustomers);
        $this->assertEquals($this->company1->id, $trashedCustomers->first()->company_id);

        $withTrashedCustomers = Customer::withTrashed()->get();
        $this->assertTrue($withTrashedCustomers->every(fn($c) => $c->company_id === $this->company1->id));

        $this->logSecurityTestResult('soft_delete_tenant_scope', true);
    }

    public function test_aggregation_queries_respect_tenant_scope()
    {
        // Create different amounts of data for each company
        Customer::factory()->count(3)->create(['company_id' => $this->company1->id]);
        Customer::factory()->count(5)->create(['company_id' => $this->company2->id]);

        $this->actingAs($this->admin1);

        // Aggregation should only count current tenant's data
        $count = Customer::count();
        $this->assertEquals(3, $count);

        $max = Customer::max('id');
        $min = Customer::min('id');

        // All results should be from company 1
        $company1Customers = Customer::where('company_id', $this->company1->id)->get();
        $this->assertTrue($company1Customers->pluck('id')->contains($max));
        $this->assertTrue($company1Customers->pluck('id')->contains($min));

        $this->logSecurityTestResult('aggregation_queries_tenant_scope', true);
    }

    public function test_authentication_prevents_tenant_switching()
    {
        // Login as company 1 user
        $this->actingAs($this->admin1);
        
        // Attempt to manually set company context
        session(['company_id' => $this->company2->id]);
        request()->merge(['company_id' => $this->company2->id]);
        
        // Queries should still respect original tenant
        $customers = Customer::all();
        $this->assertTrue($customers->every(fn($c) => $c->company_id === $this->company1->id));

        $this->logSecurityTestResult('authentication_prevents_tenant_switching', true);
    }

    public function test_concurrent_authentication_sessions_are_isolated()
    {
        // Simulate concurrent sessions
        $this->actingAs($this->admin1);
        session(['test_data' => 'company1_session']);
        $session1Data = session()->all();

        // Start new session for company 2
        session()->flush();
        $this->actingAs($this->admin2);
        session(['test_data' => 'company2_session']);
        $session2Data = session()->all();

        // Sessions should be completely isolated
        $this->assertNotEquals($session1Data['test_data'] ?? null, $session2Data['test_data'] ?? null);
        
        // Company 2 session should not see company 1 data
        $customers = Customer::all();
        $this->assertTrue($customers->every(fn($c) => $c->company_id === $this->company2->id));

        $this->logSecurityTestResult('concurrent_sessions_isolated', true);
    }

    public function test_tenant_scope_survives_application_errors()
    {
        $customer1 = Customer::factory()->create(['company_id' => $this->company1->id]);
        $customer2 = Customer::factory()->create(['company_id' => $this->company2->id]);

        $this->actingAs($this->admin1);

        try {
            // Trigger an error while maintaining tenant context
            throw new \Exception('Test error');
        } catch (\Exception $e) {
            // Tenant scope should still work after error
            $customers = Customer::all();
            $this->assertCount(1, $customers);
            $this->assertEquals($this->company1->id, $customers->first()->company_id);
        }

        $this->logSecurityTestResult('tenant_scope_survives_errors', true);
    }
}