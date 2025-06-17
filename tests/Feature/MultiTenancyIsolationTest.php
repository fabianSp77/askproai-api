<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Company;
use App\Models\User;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Branch;
use App\Models\Service;
use App\Models\Staff;
use App\Models\Call;
use App\Models\AdditionalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;

class MultiTenancyIsolationTest extends TestCase
{
    use RefreshDatabase;
    
    protected Company $company1;
    protected Company $company2;
    protected User $user1;
    protected User $user2;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles if they don't exist
        Role::firstOrCreate(['name' => 'super_admin']);
        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'user']);
        
        // Create two companies
        $this->company1 = Company::create([
            'name' => 'Company 1',
            'slug' => 'company-1',
            'is_active' => true,
            'settings' => [],
            'calcom_metadata' => [],
        ]);
        
        $this->company2 = Company::create([
            'name' => 'Company 2', 
            'slug' => 'company-2',
            'is_active' => true,
            'settings' => [],
            'calcom_metadata' => [],
        ]);
        
        // Create users for each company
        $this->user1 = User::create([
            'name' => 'User 1',
            'email' => 'user1@company1.com',
            'password' => bcrypt('password'),
            'company_id' => $this->company1->id,
        ]);
        $this->user1->assignRole('admin');
        
        $this->user2 = User::create([
            'name' => 'User 2',
            'email' => 'user2@company2.com',
            'password' => bcrypt('password'),
            'company_id' => $this->company2->id,
        ]);
        $this->user2->assignRole('admin');
    }
    
    public function test_customers_are_isolated_by_company()
    {
        // Create customers for each company
        $customer1 = Customer::create([
            'company_id' => $this->company1->id,
            'name' => 'Customer for Company 1',
            'email' => 'customer1@example.com',
            'phone' => '+1234567890',
        ]);
        
        $customer2 = Customer::create([
            'company_id' => $this->company2->id,
            'name' => 'Customer for Company 2',
            'email' => 'customer2@example.com',
            'phone' => '+0987654321',
        ]);
        
        // Set company context for user1
        app()->bind('current_company_id', fn() => $this->company1->id);
        Auth::login($this->user1);
        
        // User1 should only see their company's customers
        $customers = Customer::all();
        $this->assertCount(1, $customers);
        $this->assertEquals($customer1->id, $customers->first()->id);
        
        // Switch to user2 context
        app()->bind('current_company_id', fn() => $this->company2->id);
        Auth::login($this->user2);
        
        // User2 should only see their company's customers
        $customers = Customer::all();
        $this->assertCount(1, $customers);
        $this->assertEquals($customer2->id, $customers->first()->id);
    }
    
    public function test_appointments_are_isolated_by_company()
    {
        // Create branches for testing
        $branch1 = Branch::create([
            'company_id' => $this->company1->id,
            'name' => 'Branch 1',
            'phone' => '+1111111111',
        ]);
        
        $branch2 = Branch::create([
            'company_id' => $this->company2->id,
            'name' => 'Branch 2',
            'phone' => '+2222222222',
        ]);
        
        // Create appointments
        $appointment1 = Appointment::create([
            'company_id' => $this->company1->id,
            'branch_id' => $branch1->id,
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addHour(),
            'status' => 'scheduled',
        ]);
        
        $appointment2 = Appointment::create([
            'company_id' => $this->company2->id,
            'branch_id' => $branch2->id,
            'starts_at' => now()->addDays(2),
            'ends_at' => now()->addDays(2)->addHour(),
            'status' => 'scheduled',
        ]);
        
        // Test isolation for company1
        app()->bind('current_company_id', fn() => $this->company1->id);
        Auth::login($this->user1);
        
        $appointments = Appointment::all();
        $this->assertCount(1, $appointments);
        $this->assertEquals($appointment1->id, $appointments->first()->id);
        
        // Test isolation for company2
        app()->bind('current_company_id', fn() => $this->company2->id);
        Auth::login($this->user2);
        
        $appointments = Appointment::all();
        $this->assertCount(1, $appointments);
        $this->assertEquals($appointment2->id, $appointments->first()->id);
    }
    
    public function test_services_are_isolated_by_company()
    {
        // Create services
        $service1 = Service::create([
            'company_id' => $this->company1->id,
            'name' => 'Service 1',
            'duration' => 60,
            'price' => 100,
        ]);
        
        $service2 = Service::create([
            'company_id' => $this->company2->id,
            'name' => 'Service 2',
            'duration' => 90,
            'price' => 150,
        ]);
        
        // Test isolation
        app()->bind('current_company_id', fn() => $this->company1->id);
        $services = Service::all();
        $this->assertCount(1, $services);
        $this->assertEquals($service1->id, $services->first()->id);
        
        app()->bind('current_company_id', fn() => $this->company2->id);
        $services = Service::all();
        $this->assertCount(1, $services);
        $this->assertEquals($service2->id, $services->first()->id);
    }
    
    public function test_calls_are_isolated_by_company()
    {
        // Create calls
        $call1 = Call::create([
            'company_id' => $this->company1->id,
            'retell_call_id' => 'call-1',
            'from_number' => '+1234567890',
            'to_number' => '+1111111111',
            'status' => 'completed',
            'duration' => 120,
        ]);
        
        $call2 = Call::create([
            'company_id' => $this->company2->id,
            'retell_call_id' => 'call-2',
            'from_number' => '+0987654321',
            'to_number' => '+2222222222',
            'status' => 'completed',
            'duration' => 180,
        ]);
        
        // Test isolation
        app()->bind('current_company_id', fn() => $this->company1->id);
        $calls = Call::all();
        $this->assertCount(1, $calls);
        $this->assertEquals($call1->id, $calls->first()->id);
        
        app()->bind('current_company_id', fn() => $this->company2->id);
        $calls = Call::all();
        $this->assertCount(1, $calls);
        $this->assertEquals($call2->id, $calls->first()->id);
    }
    
    public function test_additional_services_show_platform_and_company_specific()
    {
        // Create platform-wide service (no company_id)
        $platformService = AdditionalService::withoutGlobalScopes()->create([
            'company_id' => null,
            'name' => 'Platform Service',
            'type' => 'recurring',
            'price' => 50,
            'unit' => 'month',
            'is_active' => true,
        ]);
        
        // Create company-specific services
        $companyService1 = AdditionalService::create([
            'company_id' => $this->company1->id,
            'name' => 'Company 1 Service',
            'type' => 'one_time',
            'price' => 100,
            'unit' => 'each',
            'is_active' => true,
        ]);
        
        $companyService2 = AdditionalService::create([
            'company_id' => $this->company2->id,
            'name' => 'Company 2 Service',
            'type' => 'one_time',
            'price' => 200,
            'unit' => 'each',
            'is_active' => true,
        ]);
        
        // Company 1 should see platform service + their own
        app()->bind('current_company_id', fn() => $this->company1->id);
        $services = AdditionalService::all();
        $this->assertCount(2, $services);
        $this->assertTrue($services->contains('id', $platformService->id));
        $this->assertTrue($services->contains('id', $companyService1->id));
        $this->assertFalse($services->contains('id', $companyService2->id));
        
        // Company 2 should see platform service + their own
        app()->bind('current_company_id', fn() => $this->company2->id);
        $services = AdditionalService::all();
        $this->assertCount(2, $services);
        $this->assertTrue($services->contains('id', $platformService->id));
        $this->assertTrue($services->contains('id', $companyService2->id));
        $this->assertFalse($services->contains('id', $companyService1->id));
    }
    
    public function test_cross_tenant_access_is_prevented()
    {
        // Create customer for company 1
        $customer = Customer::create([
            'company_id' => $this->company1->id,
            'name' => 'Private Customer',
            'email' => 'private@example.com',
            'phone' => '+1234567890',
        ]);
        
        // Try to access from company 2 context
        app()->bind('current_company_id', fn() => $this->company2->id);
        Auth::login($this->user2);
        
        // Should not find the customer
        $found = Customer::find($customer->id);
        $this->assertNull($found);
        
        // Direct query should also not work
        $found = Customer::where('id', $customer->id)->first();
        $this->assertNull($found);
    }
    
    public function test_super_admin_can_access_all_tenants()
    {
        // Create super admin user
        $superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@askproai.com',
            'password' => bcrypt('password'),
        ]);
        $superAdmin->assignRole('super_admin');
        
        // Create data for both companies
        Customer::create([
            'company_id' => $this->company1->id,
            'name' => 'Customer 1',
            'email' => 'c1@example.com',
            'phone' => '+1111111111',
        ]);
        
        Customer::create([
            'company_id' => $this->company2->id,
            'name' => 'Customer 2',
            'email' => 'c2@example.com',
            'phone' => '+2222222222',
        ]);
        
        // Login as super admin
        Auth::login($superAdmin);
        
        // Should see all customers
        $customers = Customer::all();
        $this->assertCount(2, $customers);
    }
}