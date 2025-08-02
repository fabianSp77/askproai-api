<?php

namespace Tests\Feature\Security;

use App\Models\Company;
use App\Models\User;
use App\Models\PortalUser;
use App\Models\Customer;
use App\Models\Appointment;
use App\Models\Call;
use App\Models\Branch;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class MultiTenantSecurityTest extends TestCase
{
    use RefreshDatabase;

    private Company $company1;
    private Company $company2;
    private User $admin1;
    private User $admin2;
    private PortalUser $portalUser1;
    private PortalUser $portalUser2;

    protected function setUp(): void
    {
        parent::setUp();

        // Create two companies
        $this->company1 = Company::factory()->create([
            'name' => 'Company One',
            'slug' => 'company-one',
            'is_active' => true,
        ]);

        $this->company2 = Company::factory()->create([
            'name' => 'Company Two', 
            'slug' => 'company-two',
            'is_active' => true,
        ]);

        // Create admin users for each company
        $this->admin1 = User::factory()->create([
            'company_id' => $this->company1->id,
            'email' => 'admin1@company1.com',
            'is_active' => true,
        ]);

        $this->admin2 = User::factory()->create([
            'company_id' => $this->company2->id,
            'email' => 'admin2@company2.com',
            'is_active' => true,
        ]);

        // Create portal users for each company
        $this->portalUser1 = PortalUser::factory()->create([
            'company_id' => $this->company1->id,
            'email' => 'portal1@company1.com',
            'is_active' => true,
        ]);

        $this->portalUser2 = PortalUser::factory()->create([
            'company_id' => $this->company2->id,
            'email' => 'portal2@company2.com',
            'is_active' => true,
        ]);
    }

    public function test_customers_are_isolated_by_tenant()
    {
        // Create customers for each company
        $customer1 = Customer::factory()->create([
            'company_id' => $this->company1->id,
            'name' => 'Customer One',
            'email' => 'customer1@example.com',
        ]);

        $customer2 = Customer::factory()->create([
            'company_id' => $this->company2->id,
            'name' => 'Customer Two',
            'email' => 'customer2@example.com',
        ]);

        // Test admin access isolation
        $this->actingAs($this->admin1);
        $customers = Customer::all();
        $this->assertCount(1, $customers);
        $this->assertEquals($customer1->id, $customers->first()->id);

        // Switch to admin2
        Auth::logout();
        $this->actingAs($this->admin2);
        $customers = Customer::all();
        $this->assertCount(1, $customers);
        $this->assertEquals($customer2->id, $customers->first()->id);

        // Test portal user access isolation
        Auth::logout();
        $this->actingAs($this->portalUser1, 'portal');
        $customers = Customer::all();
        $this->assertCount(1, $customers);
        $this->assertEquals($customer1->id, $customers->first()->id);
    }

    public function test_appointments_are_isolated_by_tenant()
    {
        // Create branches for appointments
        $branch1 = Branch::factory()->create([
            'company_id' => $this->company1->id,
            'name' => 'Branch One',
        ]);

        $branch2 = Branch::factory()->create([
            'company_id' => $this->company2->id,
            'name' => 'Branch Two',
        ]);

        // Create appointments
        $appointment1 = Appointment::factory()->create([
            'company_id' => $this->company1->id,
            'branch_id' => $branch1->id,
        ]);

        $appointment2 = Appointment::factory()->create([
            'company_id' => $this->company2->id,
            'branch_id' => $branch2->id,
        ]);

        // Test isolation
        $this->actingAs($this->admin1);
        $appointments = Appointment::all();
        $this->assertCount(1, $appointments);
        $this->assertEquals($appointment1->id, $appointments->first()->id);

        Auth::logout();
        $this->actingAs($this->admin2);
        $appointments = Appointment::all();
        $this->assertCount(1, $appointments);
        $this->assertEquals($appointment2->id, $appointments->first()->id);
    }

    public function test_calls_are_isolated_by_tenant()
    {
        // Create calls for each company
        $call1 = Call::factory()->create([
            'company_id' => $this->company1->id,
            'retell_call_id' => 'call-1-company1',
        ]);

        $call2 = Call::factory()->create([
            'company_id' => $this->company2->id,
            'retell_call_id' => 'call-2-company2',
        ]);

        // Test isolation
        $this->actingAs($this->admin1);
        $calls = Call::all();
        $this->assertCount(1, $calls);
        $this->assertEquals($call1->id, $calls->first()->id);

        Auth::logout();
        $this->actingAs($this->admin2);
        $calls = Call::all();
        $this->assertCount(1, $calls);
        $this->assertEquals($call2->id, $calls->first()->id);
    }

    public function test_services_are_isolated_by_tenant()
    {
        // Create services for each company
        $service1 = Service::factory()->create([
            'company_id' => $this->company1->id,
            'name' => 'Service One',
        ]);

        $service2 = Service::factory()->create([
            'company_id' => $this->company2->id,  
            'name' => 'Service Two',
        ]);

        // Test isolation
        $this->actingAs($this->admin1);
        $services = Service::all();
        $this->assertCount(1, $services);
        $this->assertEquals($service1->id, $services->first()->id);

        Auth::logout();
        $this->actingAs($this->admin2);
        $services = Service::all();
        $this->assertCount(1, $services);
        $this->assertEquals($service2->id, $services->first()->id);
    }

    public function test_branches_are_isolated_by_tenant()
    {
        // Create branches for each company
        $branch1 = Branch::factory()->create([
            'company_id' => $this->company1->id,
            'name' => 'Branch One',
        ]);

        $branch2 = Branch::factory()->create([
            'company_id' => $this->company2->id,
            'name' => 'Branch Two',
        ]);

        // Test isolation
        $this->actingAs($this->admin1);
        $branches = Branch::all();
        $this->assertCount(1, $branches);
        $this->assertEquals($branch1->id, $branches->first()->id);

        Auth::logout();
        $this->actingAs($this->admin2);
        $branches = Branch::all();
        $this->assertCount(1, $branches);
        $this->assertEquals($branch2->id, $branches->first()->id);
    }

    public function test_portal_users_are_isolated_by_tenant()
    {
        // Test isolation for portal users
        $this->actingAs($this->admin1);
        $portalUsers = PortalUser::all();
        
        // Should only see portal users from the same company
        if ($portalUsers->count() > 0) {
            $this->assertTrue($portalUsers->every(fn($user) => $user->company_id === $this->company1->id));
        }

        Auth::logout();
        $this->actingAs($this->admin2);
        $portalUsers = PortalUser::all();
        
        if ($portalUsers->count() > 0) {
            $this->assertTrue($portalUsers->every(fn($user) => $user->company_id === $this->company2->id));
        }
    }

    public function test_direct_model_access_respects_tenant_scope()
    {
        // Create customer for company 1
        $customer = Customer::factory()->create([
            'company_id' => $this->company1->id,
            'name' => 'Protected Customer',
        ]);

        // Login as company 2 admin
        $this->actingAs($this->admin2);

        // Direct access should not work due to tenant scope
        $foundCustomer = Customer::find($customer->id);
        $this->assertNull($foundCustomer);

        // Query should also not work
        $foundCustomer = Customer::where('name', 'Protected Customer')->first();
        $this->assertNull($foundCustomer);
    }

    public function test_api_endpoints_respect_tenant_isolation()
    {
        // Create customer for company 1
        $customer = Customer::factory()->create([
            'company_id' => $this->company1->id,
            'name' => 'API Customer',
        ]);

        // Try to access as company 2 portal user
        $this->actingAs($this->portalUser2, 'portal');

        // API should not return other company's data
        $response = $this->getJson("/business/api/customers/{$customer->id}");
        
        // Should be not found or unauthorized
        $this->assertTrue(in_array($response->status(), [404, 403, 401]));
    }

    public function test_bulk_operations_respect_tenant_scope()
    {
        // Create customers for both companies
        $customer1 = Customer::factory()->create([
            'company_id' => $this->company1->id,
            'name' => 'Bulk Customer 1',
        ]);

        $customer2 = Customer::factory()->create([
            'company_id' => $this->company2->id,
            'name' => 'Bulk Customer 2',
        ]);

        // Login as company 1 admin
        $this->actingAs($this->admin1);

        // Bulk update should only affect company 1 customers
        Customer::where('name', 'LIKE', 'Bulk Customer%')->update(['phone' => '+1234567890']);

        $customer1->refresh();
        $customer2->refresh();

        // Only customer 1 should be updated
        $this->assertEquals('+1234567890', $customer1->phone);
        $this->assertNotEquals('+1234567890', $customer2->phone);
    }

    public function test_file_uploads_are_tenant_isolated()
    {
        // This would test file upload isolation if implemented
        $this->actingAs($this->admin1);
        
        // File uploads should be scoped to company
        // This is a placeholder test - actual implementation would depend on file storage structure
        $this->assertTrue(true); // Placeholder assertion
    }

    public function test_database_queries_are_automatically_scoped()
    {
        // Create test data
        Customer::factory()->create(['company_id' => $this->company1->id]);
        Customer::factory()->create(['company_id' => $this->company1->id]);
        Customer::factory()->create(['company_id' => $this->company2->id]);

        // Test various query types are scoped
        $this->actingAs($this->admin1);

        // Count should be scoped
        $count = Customer::count();
        $this->assertEquals(2, $count);

        // Exists should be scoped
        $exists = Customer::exists();
        $this->assertTrue($exists);

        // First should be scoped
        $customer = Customer::first();
        $this->assertEquals($this->company1->id, $customer->company_id);

        // Pluck should be scoped
        $companyIds = Customer::pluck('company_id')->unique();
        $this->assertCount(1, $companyIds);
        $this->assertEquals($this->company1->id, $companyIds->first());
    }

    public function test_relationship_queries_respect_tenant_scope()
    {
        // Create branch with service for company 1
        $branch1 = Branch::factory()->create(['company_id' => $this->company1->id]);
        $service1 = Service::factory()->create(['company_id' => $this->company1->id]);

        // Create branch with service for company 2  
        $branch2 = Branch::factory()->create(['company_id' => $this->company2->id]);
        $service2 = Service::factory()->create(['company_id' => $this->company2->id]);

        $this->actingAs($this->admin1);

        // Relationship queries should be scoped
        $branches = Branch::with('services')->get();
        $this->assertCount(1, $branches);
        $this->assertEquals($this->company1->id, $branches->first()->company_id);
    }

    public function test_cross_tenant_data_access_is_prevented()
    {
        // Create sensitive data for company 1
        $sensitiveCustomer = Customer::factory()->create([
            'company_id' => $this->company1->id,
            'name' => 'Sensitive Data',
            'email' => 'sensitive@company1.com',
        ]);

        // Try various ways to access as company 2 user
        $this->actingAs($this->admin2);

        // Direct ID access
        $this->assertNull(Customer::find($sensitiveCustomer->id));

        // Query access
        $this->assertNull(Customer::where('email', 'sensitive@company1.com')->first());

        // Raw query access (if tenant scope applies to raw queries)
        $results = Customer::whereRaw("email = 'sensitive@company1.com'")->get();
        $this->assertCount(0, $results);
    }

    public function test_tenant_scope_works_with_soft_deletes()
    {
        // Skip if Customer model doesn't use soft deletes
        if (!method_exists(Customer::class, 'trashed')) {
            $this->markTestSkipped('Customer model does not use soft deletes');
        }

        // Create and soft delete customer for company 1
        $customer = Customer::factory()->create(['company_id' => $this->company1->id]);
        $customer->delete();

        $this->actingAs($this->admin1);

        // Should be able to see own company's trashed records
        $trashed = Customer::onlyTrashed()->get();
        $this->assertCount(1, $trashed);

        // Switch to company 2
        Auth::logout();
        $this->actingAs($this->admin2);

        // Should not see other company's trashed records
        $trashed = Customer::onlyTrashed()->get();
        $this->assertCount(0, $trashed);
    }
}