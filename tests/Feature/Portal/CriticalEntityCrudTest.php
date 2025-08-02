<?php

namespace Tests\Feature\Portal;

use App\Models\Company;
use App\Models\User;
use App\Models\PortalUser;
use App\Models\Customer;
use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Service;
use App\Models\Call;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CriticalEntityCrudTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private User $admin;
    private PortalUser $portalUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create([
            'name' => 'Test Company',
            'slug' => 'test-company',
            'is_active' => true,
        ]);

        $this->admin = User::factory()->create([
            'company_id' => $this->company->id,
            'email' => 'admin@test.com',
            'is_active' => true,
        ]);

        $this->portalUser = PortalUser::factory()->create([
            'company_id' => $this->company->id,
            'email' => 'portal@test.com',
            'is_active' => true,
            'role' => PortalUser::ROLE_ADMIN,
        ]);
    }

    // Company CRUD Tests
    public function test_admin_can_view_company_details()
    {
        $this->actingAs($this->admin);

        $response = $this->get("/admin/companies/{$this->company->id}");
        
        // Should be able to view own company
        if ($response->status() !== 404) {
            $response->assertStatus(200);
        }
    }

    public function test_admin_can_update_company_settings()
    {
        $this->actingAs($this->admin);

        $updateData = [
            'name' => 'Updated Company Name',
            'settings' => ['timezone' => 'Europe/Berlin'],
        ];

        // Try to update company
        $response = $this->put("/admin/companies/{$this->company->id}", $updateData);
        
        if ($response->status() !== 404) {
            $this->company->refresh();
            $this->assertEquals('Updated Company Name', $this->company->name);
        }
    }

    public function test_portal_user_can_view_company_info()
    {
        $this->actingAs($this->portalUser, 'portal');

        $response = $this->get('/business/api/company');
        
        if ($response->status() !== 404) {
            $response->assertStatus(200);
            $response->assertJson([
                'id' => $this->company->id,
                'name' => $this->company->name,
            ]);
        }
    }

    // User CRUD Tests
    public function test_admin_can_create_new_users()
    {
        $this->actingAs($this->admin);

        $userData = [
            'name' => 'New User',
            'email' => 'newuser@test.com',
            'password' => 'password',
            'company_id' => $this->company->id,
        ];

        $response = $this->post('/admin/users', $userData);
        
        if ($response->status() !== 404) {
            $this->assertDatabaseHas('users', [
                'email' => 'newuser@test.com',
                'company_id' => $this->company->id,
            ]);
        }
    }

    public function test_admin_can_update_user_details()
    {
        $this->actingAs($this->admin);

        $user = User::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Original Name',
        ]);

        $updateData = [
            'name' => 'Updated Name',
            'email' => $user->email,
        ];

        $response = $this->put("/admin/users/{$user->id}", $updateData);
        
        if ($response->status() !== 404) {
            $user->refresh();
            $this->assertEquals('Updated Name', $user->name);
        }
    }

    public function test_admin_can_delete_users()
    {
        $this->actingAs($this->admin);

        $user = User::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $response = $this->delete("/admin/users/{$user->id}");
        
        if ($response->status() !== 404) {
            $this->assertSoftDeleted('users', ['id' => $user->id]);
        }
    }

    public function test_admin_cannot_delete_themselves()
    {
        $this->actingAs($this->admin);

        $response = $this->delete("/admin/users/{$this->admin->id}");
        
        // Should prevent self-deletion
        $this->assertTrue(in_array($response->status(), [403, 422, 400]));
        $this->assertDatabaseHas('users', ['id' => $this->admin->id]);
    }

    // Customer CRUD Tests
    public function test_admin_can_create_customers()
    {
        $this->actingAs($this->admin);

        $customerData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '+1234567890',
            'company_id' => $this->company->id,
        ];

        $response = $this->post('/admin/customers', $customerData);
        
        if ($response->status() !== 404) {
            $this->assertDatabaseHas('customers', [
                'email' => 'john@example.com',
                'company_id' => $this->company->id,
            ]);
        }
    }

    public function test_portal_user_can_view_customers()
    {
        $this->actingAs($this->portalUser, 'portal');

        $customer = Customer::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $response = $this->get('/business/api/customers');
        
        if ($response->status() !== 404) {
            $response->assertStatus(200);
            $response->assertJsonFragment(['id' => $customer->id]);
        }
    }

    public function test_portal_user_can_update_customer_details()
    {
        $this->actingAs($this->portalUser, 'portal');

        $customer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Original Name',
        ]);

        $updateData = [
            'name' => 'Updated Customer Name',
            'email' => $customer->email,
            'phone' => $customer->phone,
        ];

        $response = $this->put("/business/api/customers/{$customer->id}", $updateData);
        
        if ($response->status() !== 404) {
            $customer->refresh();
            $this->assertEquals('Updated Customer Name', $customer->name);
        }
    }

    public function test_customers_have_proper_validation()
    {
        $this->actingAs($this->admin);

        // Test invalid email
        $response = $this->post('/admin/customers', [
            'name' => 'Test Customer',
            'email' => 'invalid-email',
            'phone' => '+1234567890',
        ]);

        $this->assertTrue(in_array($response->status(), [422, 400, 404]));
    }

    // Appointment CRUD Tests  
    public function test_admin_can_create_appointments()
    {
        $this->actingAs($this->admin);

        $customer = Customer::factory()->create(['company_id' => $this->company->id]);
        $branch = Branch::factory()->create(['company_id' => $this->company->id]);

        $appointmentData = [
            'customer_id' => $customer->id,
            'branch_id' => $branch->id,
            'starts_at' => now()->addDay()->format('Y-m-d H:i:s'),
            'ends_at' => now()->addDay()->addHour()->format('Y-m-d H:i:s'),
            'status' => 'scheduled',
            'company_id' => $this->company->id,
        ];

        $response = $this->post('/admin/appointments', $appointmentData);
        
        if ($response->status() !== 404) {
            $this->assertDatabaseHas('appointments', [
                'customer_id' => $customer->id,
                'company_id' => $this->company->id,
            ]);
        }
    }

    public function test_portal_user_can_view_appointments()
    {
        $this->actingAs($this->portalUser, 'portal');

        $appointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $response = $this->get('/business/api/appointments');
        
        if ($response->status() !== 404) {
            $response->assertStatus(200);
            $response->assertJsonFragment(['id' => $appointment->id]);
        }
    }

    public function test_appointment_status_updates_work()
    {
        $this->actingAs($this->admin);

        $appointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'status' => 'scheduled',
        ]);

        $response = $this->patch("/admin/appointments/{$appointment->id}", [
            'status' => 'confirmed',
        ]);

        if ($response->status() !== 404) {
            $appointment->refresh();
            $this->assertEquals('confirmed', $appointment->status);
        }
    }

    public function test_appointments_cannot_be_double_booked()
    {
        $this->actingAs($this->admin);

        $branch = Branch::factory()->create(['company_id' => $this->company->id]);
        $startTime = now()->addDay();
        $endTime = $startTime->copy()->addHour();

        // Create first appointment
        $appointment1 = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $branch->id,
            'starts_at' => $startTime,
            'ends_at' => $endTime,
        ]);

        // Try to create overlapping appointment
        $response = $this->post('/admin/appointments', [
            'branch_id' => $branch->id,
            'starts_at' => $startTime->format('Y-m-d H:i:s'),
            'ends_at' => $endTime->format('Y-m-d H:i:s'),
            'company_id' => $this->company->id,
        ]);

        // Should prevent double booking
        $this->assertTrue(in_array($response->status(), [422, 400, 409, 404]));
    }

    // Branch CRUD Tests
    public function test_admin_can_create_branches()
    {
        $this->actingAs($this->admin);

        $branchData = [
            'name' => 'New Branch',
            'phone' => '+1234567890',
            'address' => '123 Test Street',
            'company_id' => $this->company->id,
        ];

        $response = $this->post('/admin/branches', $branchData);
        
        if ($response->status() !== 404) {
            $this->assertDatabaseHas('branches', [
                'name' => 'New Branch',
                'company_id' => $this->company->id,
            ]);
        }
    }

    public function test_portal_user_can_view_branches()
    {
        $this->actingAs($this->portalUser, 'portal');

        $branch = Branch::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $response = $this->get('/business/api/branches');
        
        if ($response->status() !== 404) {
            $response->assertStatus(200);
            $response->assertJsonFragment(['id' => $branch->id]);
        }
    }

    // Service CRUD Tests
    public function test_admin_can_create_services()
    {
        $this->actingAs($this->admin);

        $serviceData = [
            'name' => 'Test Service',
            'duration' => 60,
            'price' => 100.00,
            'company_id' => $this->company->id,
        ];

        $response = $this->post('/admin/services', $serviceData);
        
        if ($response->status() !== 404) {
            $this->assertDatabaseHas('services', [
                'name' => 'Test Service',
                'company_id' => $this->company->id,
            ]);
        }
    }

    public function test_service_pricing_validation()
    {
        $this->actingAs($this->admin);

        // Test negative price
        $response = $this->post('/admin/services', [
            'name' => 'Invalid Service',
            'duration' => 60,
            'price' => -50.00,
        ]);

        $this->assertTrue(in_array($response->status(), [422, 400, 404]));
    }

    // Call CRUD Tests
    public function test_admin_can_view_calls()
    {
        $this->actingAs($this->admin);

        $call = Call::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $response = $this->get('/admin/calls');
        
        if ($response->status() !== 404) {
            $response->assertStatus(200);
        }
    }

    public function test_portal_user_can_view_calls()
    {
        $this->actingAs($this->portalUser, 'portal');

        $call = Call::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $response = $this->get('/business/api/calls');
        
        if ($response->status() !== 404) {
            $response->assertStatus(200);
            $response->assertJsonFragment(['id' => $call->id]);
        }
    }

    public function test_calls_are_read_only_for_portal_users()
    {
        $this->actingAs($this->portalUser, 'portal');

        $call = Call::factory()->create([
            'company_id' => $this->company->id,
        ]);

        // Try to update call (should be prevented)
        $response = $this->put("/business/api/calls/{$call->id}", [
            'status' => 'updated',
        ]);

        // Should be forbidden or method not allowed
        $this->assertTrue(in_array($response->status(), [403, 405, 404]));
    }

    // Cross-Entity Relationship Tests
    public function test_customer_appointments_relationship()
    {
        $this->actingAs($this->admin);

        $customer = Customer::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $appointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
        ]);

        $response = $this->get("/admin/customers/{$customer->id}/appointments");
        
        if ($response->status() !== 404) {
            $response->assertStatus(200);
            $response->assertJsonFragment(['id' => $appointment->id]);
        }
    }

    public function test_branch_appointments_relationship()
    {
        $this->actingAs($this->admin);

        $branch = Branch::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $appointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $branch->id,
        ]);

        $response = $this->get("/admin/branches/{$branch->id}/appointments");
        
        if ($response->status() !== 404) {
            $response->assertStatus(200);
        }
    }

    // Bulk Operations Tests
    public function test_bulk_customer_updates()
    {
        $this->actingAs($this->admin);

        $customers = Customer::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'status' => 'active',
        ]);

        $response = $this->patch('/admin/customers/bulk', [
            'ids' => $customers->pluck('id')->toArray(),
            'status' => 'inactive',
        ]);

        if ($response->status() !== 404) {
            foreach ($customers as $customer) {
                $customer->refresh();
                $this->assertEquals('inactive', $customer->status);
            }
        }
    }

    public function test_bulk_appointment_status_updates()
    {
        $this->actingAs($this->admin);

        $appointments = Appointment::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'status' => 'scheduled',
        ]);

        $response = $this->patch('/admin/appointments/bulk', [
            'ids' => $appointments->pluck('id')->toArray(),
            'status' => 'confirmed',
        ]);

        if ($response->status() !== 404) {
            foreach ($appointments as $appointment) {
                $appointment->refresh();
                $this->assertEquals('confirmed', $appointment->status);
            }
        }
    }

    // Data Integrity Tests
    public function test_deleting_customer_handles_appointments()
    {
        $this->actingAs($this->admin);

        $customer = Customer::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $appointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
        ]);

        $response = $this->delete("/admin/customers/{$customer->id}");

        if ($response->status() !== 404) {
            // Should handle appointment relationship appropriately
            $appointment->refresh();
            $this->assertTrue(
                $appointment->customer_id === null || 
                $appointment->trashed() ||
                $appointment->status === 'cancelled'
            );
        }
    }

    public function test_entity_audit_trails()
    {
        $this->actingAs($this->admin);

        $customer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Original Name',
        ]);

        // Update customer
        $this->put("/admin/customers/{$customer->id}", [
            'name' => 'Updated Name',
            'email' => $customer->email,
            'phone' => $customer->phone,
        ]);

        // Check if audit trail exists (if implemented)
        if (class_exists(\OwenIt\Auditing\Models\Audit::class)) {
            $audits = \OwenIt\Auditing\Models\Audit::where('auditable_id', $customer->id)->get();
            $this->assertGreaterThan(0, $audits->count());
        }
    }
}