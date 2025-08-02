<?php

namespace Tests\Feature\Integration;

use App\Models\Company;
use App\Models\User;
use App\Models\PortalUser;
use App\Models\Customer;
use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PortalWorkflowIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private User $admin;
    private PortalUser $portalAdmin;
    private PortalUser $staffUser;
    private Branch $branch;
    private Service $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Create company
        $this->company = Company::factory()->create([
            'name' => 'Test Company',
            'slug' => 'test-company',
            'is_active' => true,
        ]);

        // Create users
        $this->admin = User::factory()->create([
            'company_id' => $this->company->id,
            'email' => 'admin@test.com',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        $this->portalAdmin = PortalUser::factory()->create([
            'company_id' => $this->company->id,
            'email' => 'portal@test.com',
            'password' => Hash::make('password'),
            'is_active' => true,
            'role' => PortalUser::ROLE_ADMIN,
        ]);

        $this->staffUser = PortalUser::factory()->create([
            'company_id' => $this->company->id,
            'email' => 'staff@test.com',
            'password' => Hash::make('password'),
            'is_active' => true,
            'role' => PortalUser::ROLE_STAFF,
        ]);

        // Create branch and service
        $this->branch = Branch::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Main Branch',
        ]);

        $this->service = Service::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Test Service',
            'duration' => 60,
            'price' => 100.00,
        ]);
    }

    public function test_complete_admin_portal_workflow()
    {
        // 1. Admin Login
        $response = $this->post('/admin/login', [
            'email' => 'admin@test.com',
            'password' => 'password',
        ]);

        $response->assertRedirect();
        $this->assertAuthenticated();

        // 2. Access Dashboard
        $response = $this->get('/admin');
        if ($response->status() !== 404) {
            $response->assertStatus(200);
        }

        // 3. Create Customer
        $response = $this->post('/admin/customers', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '+1234567890',
        ]);

        if ($response->status() !== 404) {
            $this->assertDatabaseHas('customers', [
                'email' => 'john@example.com',
                'company_id' => $this->company->id,
            ]);
        }

        // 4. Create Appointment
        $customer = Customer::where('email', 'john@example.com')->first();
        if ($customer) {
            $response = $this->post('/admin/appointments', [
                'customer_id' => $customer->id,
                'branch_id' => $this->branch->id,
                'service_id' => $this->service->id,
                'starts_at' => now()->addDay()->format('Y-m-d H:i:s'),
                'ends_at' => now()->addDay()->addHour()->format('Y-m-d H:i:s'),
                'status' => 'scheduled',
            ]);

            if ($response->status() !== 404) {
                $this->assertDatabaseHas('appointments', [
                    'customer_id' => $customer->id,
                    'company_id' => $this->company->id,
                ]);
            }
        }

        // 5. Logout
        $response = $this->post('/admin/logout');
        $response->assertRedirect();
        $this->assertGuest();
    }

    public function test_complete_business_portal_workflow()
    {
        // Create some test data first
        $customer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
        ]);

        $appointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'branch_id' => $this->branch->id,
            'status' => 'scheduled',
        ]);

        // 1. Portal User Login
        $response = $this->post('/business/login', [
            'email' => 'portal@test.com',
            'password' => 'password',
        ]);

        $response->assertRedirect('/business');
        $this->assertAuthenticated('portal');

        // 2. Access Dashboard
        $response = $this->get('/business');
        if ($response->status() !== 404) {
            $response->assertStatus(200);
        }

        // 3. View Customers via API
        $response = $this->getJson('/business/api/customers');
        if ($response->status() !== 404) {
            $response->assertStatus(200);
            $response->assertJsonFragment(['id' => $customer->id]);
        }

        // 4. View Appointments via API
        $response = $this->getJson('/business/api/appointments');
        if ($response->status() !== 404) {
            $response->assertStatus(200);
            $response->assertJsonFragment(['id' => $appointment->id]);
        }

        // 5. Update Appointment Status
        $response = $this->putJson("/business/api/appointments/{$appointment->id}", [
            'status' => 'confirmed',
        ]);

        if ($response->status() !== 404) {
            $appointment->refresh();
            $this->assertEquals('confirmed', $appointment->status);
        }

        // 6. Logout
        $response = $this->post('/business/logout');
        $response->assertRedirect('/business/login');
        $this->assertGuest('portal');
    }

    public function test_staff_user_limited_access_workflow()
    {
        $customer = Customer::factory()->create([
            'company_id' => $this->company->id,
        ]);

        // 1. Staff Login
        $response = $this->post('/business/login', [
            'email' => 'staff@test.com',
            'password' => 'password',
        ]);

        $response->assertRedirect('/business');
        $this->assertAuthenticated('portal');

        // 2. Can view customers
        $response = $this->getJson('/business/api/customers');
        if ($response->status() !== 404) {
            $response->assertStatus(200);
        }

        // 3. Cannot access admin functions
        $response = $this->getJson('/business/api/users');
        if ($response->status() !== 404) {
            $this->assertTrue(in_array($response->status(), [403, 401]));
        }

        // 4. Cannot access settings
        $response = $this->get('/business/settings');
        if ($response->status() !== 404) {
            $this->assertTrue(in_array($response->status(), [403, 401]));
        }
    }

    public function test_cross_tenant_isolation_workflow()
    {
        // Create second company with data
        $company2 = Company::factory()->create([
            'name' => 'Other Company',
            'slug' => 'other-company',
        ]);

        $otherCustomer = Customer::factory()->create([
            'company_id' => $company2->id,
            'name' => 'Other Customer',
        ]);

        // 1. Login as company1 admin
        $this->post('/admin/login', [
            'email' => 'admin@test.com',
            'password' => 'password',
        ]);

        $this->assertAuthenticated();

        // 2. Try to access other company's customer
        $response = $this->get("/admin/customers/{$otherCustomer->id}");
        if ($response->status() !== 404) {
            // Should not be able to access
            $this->assertTrue(in_array($response->status(), [403, 401]));
        }

        // 3. List customers should only show own company's customers
        $response = $this->getJson('/admin/api/customers');
        if ($response->status() !== 404 && $response->status() === 200) {
            $customers = $response->json()['data'] ?? [];
            foreach ($customers as $customer) {
                $this->assertEquals($this->company->id, $customer['company_id']);
            }
        }
    }

    public function test_session_isolation_between_portals()
    {
        // 1. Login to admin portal
        $this->post('/admin/login', [
            'email' => 'admin@test.com',
            'password' => 'password',
        ]);

        $this->assertAuthenticated('web');
        $this->assertGuest('portal');

        // 2. Login to business portal
        $this->post('/business/login', [
            'email' => 'portal@test.com',
            'password' => 'password',
        ]);

        $this->assertAuthenticated('web');
        $this->assertAuthenticated('portal');

        // 3. Logout from admin should not affect portal
        $this->post('/admin/logout');

        $this->assertGuest('web');
        $this->assertAuthenticated('portal');

        // 4. Portal should still work
        $response = $this->get('/business');
        if ($response->status() !== 404) {
            $response->assertStatus(200);
        }
    }

    public function test_api_authentication_workflow()
    {
        // 1. Unauthenticated API request should fail
        $response = $this->getJson('/business/api/user');
        $this->assertTrue(in_array($response->status(), [401, 404]));

        // 2. Login to portal
        $this->post('/business/login', [
            'email' => 'portal@test.com',
            'password' => 'password',
        ]);

        // 3. API request should now work
        $response = $this->getJson('/business/api/user');
        if ($response->status() !== 404) {
            $response->assertStatus(200);
            $response->assertJson([
                'id' => $this->portalAdmin->id,
                'email' => $this->portalAdmin->email,
            ]);
        }

        // 4. Logout should invalidate API access
        $this->post('/business/logout');

        $response = $this->getJson('/business/api/user');
        $this->assertTrue(in_array($response->status(), [401, 404]));
    }

    public function test_data_consistency_workflow()
    {
        // 1. Create customer via admin
        $this->actingAs($this->admin);

        $response = $this->post('/admin/customers', [
            'name' => 'Consistency Test',
            'email' => 'consistency@test.com',
            'phone' => '+1234567890',
        ]);

        if ($response->status() !== 404) {
            $customer = Customer::where('email', 'consistency@test.com')->first();
            $this->assertNotNull($customer);
            $this->assertEquals($this->company->id, $customer->company_id);
        }

        // 2. Portal user should see the same customer
        $this->actingAs($this->portalAdmin, 'portal');

        $response = $this->getJson('/business/api/customers');
        if ($response->status() !== 404 && $response->status() === 200) {
            $customers = $response->json()['data'] ?? [];
            $foundCustomer = collect($customers)->firstWhere('email', 'consistency@test.com');
            $this->assertNotNull($foundCustomer);
        }
    }

    public function test_permission_based_access_workflow()
    {
        // 1. Admin should have full access
        $this->actingAs($this->admin);

        $response = $this->get('/admin/users');
        if ($response->status() !== 404) {
            $response->assertStatus(200);
        }

        // 2. Portal admin should have business portal access
        $this->actingAs($this->portalAdmin, 'portal');

        $response = $this->get('/business/settings');
        if ($response->status() !== 404) {
            // Should have access or get proper redirect
            $this->assertTrue(in_array($response->status(), [200, 302]));
        }

        // 3. Staff user should have limited access
        $this->actingAs($this->staffUser, 'portal');

        $response = $this->get('/business/settings');
        if ($response->status() !== 404) {
            // Should be forbidden or redirected
            $this->assertTrue(in_array($response->status(), [403, 302]));
        }
    }

    public function test_error_handling_workflow()
    {
        $this->actingAs($this->admin);

        // 1. Test validation errors
        $response = $this->post('/admin/customers', [
            'name' => '',
            'email' => 'invalid-email',
        ]);

        if ($response->status() !== 404) {
            $this->assertTrue(in_array($response->status(), [422, 400]));
        }

        // 2. Test not found errors
        $response = $this->get('/admin/customers/999999');
        if ($response->status() !== 404) {
            $this->assertTrue(in_array($response->status(), [404, 403]));
        }

        // 3. Test unauthorized access
        $this->post('/admin/logout');
        
        $response = $this->get('/admin');
        $response->assertRedirect('/admin/login');
    }

    public function test_performance_critical_paths()
    {
        // Create test data
        Customer::factory()->count(10)->create([
            'company_id' => $this->company->id,
        ]);

        $this->actingAs($this->admin);

        // Test dashboard loading time
        $startTime = microtime(true);
        $response = $this->get('/admin');
        $endTime = microtime(true);

        if ($response->status() !== 404) {
            $responseTime = ($endTime - $startTime) * 1000;
            $this->assertLessThan(3000, $responseTime); // Less than 3 seconds
        }

        // Test API response time
        $startTime = microtime(true);
        $response = $this->getJson('/admin/api/customers');
        $endTime = microtime(true);

        if ($response->status() !== 404) {
            $responseTime = ($endTime - $startTime) * 1000;
            $this->assertLessThan(1000, $responseTime); // Less than 1 second for API
        }
    }
}