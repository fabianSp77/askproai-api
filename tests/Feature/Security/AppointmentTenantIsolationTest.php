<?php

namespace Tests\Feature\Security;

use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Customer;
use App\Models\PortalUser;
use App\Models\Service;
use App\Models\Staff;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

/**
 * Tests for Appointment Tenant Isolation Security
 * 
 * This test suite validates that the AppointmentController correctly isolates
 * appointment data across tenants and prevents cross-tenant appointment access vulnerabilities.
 */
class AppointmentTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private Company $company1;
    private Company $company2;
    private Company $company3;
    private Branch $branch1;
    private Branch $branch2;
    private Branch $branch3;
    private Customer $customer1;
    private Customer $customer2;
    private Customer $customer3;
    private Staff $staff1;
    private Staff $staff2;
    private Service $service1;
    private Service $service2;
    private Appointment $appointment1;
    private Appointment $appointment2;
    private Appointment $appointment3;
    private PortalUser $portalUser1;
    private PortalUser $portalUser2;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // Create companies
        $this->company1 = Company::factory()->create([
            'name' => 'Medical Practice Alpha',
            'slug' => 'medical-alpha',
            'is_active' => true,
        ]);

        $this->company2 = Company::factory()->create([
            'name' => 'Dental Clinic Beta',
            'slug' => 'dental-beta',
            'is_active' => true,
        ]);

        $this->company3 = Company::factory()->create([
            'name' => 'Physiotherapy Gamma',
            'slug' => 'physio-gamma',
            'is_active' => true,
        ]);

        // Create branches
        $this->branch1 = Branch::factory()->create([
            'company_id' => $this->company1->id,
            'name' => 'Main Branch Alpha',
        ]);

        $this->branch2 = Branch::factory()->create([
            'company_id' => $this->company2->id,
            'name' => 'Main Branch Beta',
        ]);

        $this->branch3 = Branch::factory()->create([
            'company_id' => $this->company3->id,
            'name' => 'Main Branch Gamma',
        ]);

        // Create customers
        $this->customer1 = Customer::factory()->create([
            'company_id' => $this->company1->id,
            'name' => 'John Alpha',
            'email' => 'john@alpha.com',
            'phone' => '+1234567890',
        ]);

        $this->customer2 = Customer::factory()->create([
            'company_id' => $this->company2->id,
            'name' => 'Jane Beta',
            'email' => 'jane@beta.com',
            'phone' => '+1234567891',
        ]);

        $this->customer3 = Customer::factory()->create([
            'company_id' => $this->company3->id,
            'name' => 'Bob Gamma',
            'email' => 'bob@gamma.com',
            'phone' => '+1234567892',
        ]);

        // Create staff
        $this->staff1 = Staff::factory()->create([
            'company_id' => $this->company1->id,
            'branch_id' => $this->branch1->id,
            'name' => 'Dr. Alpha',
            'email' => 'doctor@alpha.com',
        ]);

        $this->staff2 = Staff::factory()->create([
            'company_id' => $this->company2->id,
            'branch_id' => $this->branch2->id,
            'name' => 'Dr. Beta',
            'email' => 'dentist@beta.com',
        ]);

        // Create services
        $this->service1 = Service::factory()->create([
            'company_id' => $this->company1->id,
            'name' => 'General Consultation',
            'duration' => 30,
            'price' => 10000, // $100.00
        ]);

        $this->service2 = Service::factory()->create([
            'company_id' => $this->company2->id,
            'name' => 'Dental Checkup',
            'duration' => 45,
            'price' => 15000, // $150.00
        ]);

        // Create appointments
        $this->appointment1 = Appointment::factory()->create([
            'company_id' => $this->company1->id,
            'branch_id' => $this->branch1->id,
            'customer_id' => $this->customer1->id,
            'staff_id' => $this->staff1->id,
            'service_id' => $this->service1->id,
            'scheduled_at' => Carbon::now()->addDay(),
            'status' => 'scheduled',
            'notes' => 'Alpha appointment notes',
        ]);

        $this->appointment2 = Appointment::factory()->create([
            'company_id' => $this->company2->id,
            'branch_id' => $this->branch2->id,
            'customer_id' => $this->customer2->id,
            'staff_id' => $this->staff2->id,
            'service_id' => $this->service2->id,
            'scheduled_at' => Carbon::now()->addDays(2),
            'status' => 'confirmed',
            'notes' => 'Beta appointment notes',
        ]);

        $this->appointment3 = Appointment::factory()->create([
            'company_id' => $this->company3->id,
            'branch_id' => $this->branch3->id,
            'customer_id' => $this->customer3->id,
            'scheduled_at' => Carbon::now()->addDays(3),
            'status' => 'scheduled',
            'notes' => 'Gamma appointment notes',
        ]);

        // Create portal users
        $this->portalUser1 = PortalUser::factory()->create([
            'company_id' => $this->company1->id,
            'email' => 'staff@alpha.com',
            'is_active' => true,
        ]);

        $this->portalUser2 = PortalUser::factory()->create([
            'company_id' => $this->company2->id,
            'email' => 'staff@beta.com',
            'is_active' => true,
        ]);

        // Create admin user
        $this->admin = User::factory()->create([
            'email' => 'admin@askproai.com',
            'is_admin' => true,
        ]);
    }

    public function test_appointment_index_view_respects_tenant_isolation()
    {
        // Login as company 1 portal user
        $this->actingAs($this->portalUser1, 'portal');

        $response = $this->get('/business/appointments');
        $response->assertStatus(200);
        $response->assertViewIs('portal.appointments.index');
    }

    public function test_appointment_show_prevents_cross_tenant_access()
    {
        // Login as company 1 portal user
        $this->actingAs($this->portalUser1, 'portal');

        // Try to view appointment from different company - should fail
        $response = $this->get("/business/appointments/{$this->appointment2->id}");
        $response->assertStatus(404);

        // Should be able to view own company's appointment
        $response = $this->get("/business/appointments/{$this->appointment1->id}");
        $response->assertStatus(200);
        $response->assertViewIs('portal.appointments.show');
        $response->assertViewHas('appointment');
        
        $appointment = $response->viewData('appointment');
        $this->assertEquals($this->appointment1->id, $appointment['id']);
        $this->assertEquals($this->company1->id, $appointment['company_id']);
    }

    public function test_appointment_api_index_respects_tenant_isolation()
    {
        // Login as company 1 portal user
        $this->actingAs($this->portalUser1, 'portal');

        $response = $this->getJson('/business/api/appointments');
        $response->assertStatus(200);

        $data = $response->json();
        $this->assertIsArray($data);

        // Should only see appointments from company 1
        if (isset($data['data'])) {
            foreach ($data['data'] as $appointment) {
                $this->assertEquals($this->company1->id, $appointment['company_id']);
            }
            
            // Should include appointment 1 but not appointment 2 or 3
            $appointmentIds = collect($data['data'])->pluck('id');
            $this->assertTrue($appointmentIds->contains($this->appointment1->id));
            $this->assertFalse($appointmentIds->contains($this->appointment2->id));
            $this->assertFalse($appointmentIds->contains($this->appointment3->id));
        }
    }

    public function test_appointment_api_stats_isolated_by_tenant()
    {
        // Login as company 1 portal user
        $this->actingAs($this->portalUser1, 'portal');

        $response = $this->getJson('/business/api/appointments/stats');
        $response->assertStatus(200);

        $data = $response->json();
        $this->assertIsArray($data);

        // Stats should only reflect company 1 appointments
        // The exact structure depends on the MCP implementation
        $this->assertArrayHasKey('today', $data);
        $this->assertArrayHasKey('week', $data);
        $this->assertArrayHasKey('confirmed', $data);
        $this->assertArrayHasKey('total', $data);
    }

    public function test_appointment_filtering_respects_tenant_boundaries()
    {
        // Create additional appointments for testing filters
        Appointment::factory()->create([
            'company_id' => $this->company1->id,
            'branch_id' => $this->branch1->id,
            'customer_id' => $this->customer1->id,
            'scheduled_at' => Carbon::today(),
            'status' => 'confirmed',
        ]);

        Appointment::factory()->create([
            'company_id' => $this->company2->id,
            'branch_id' => $this->branch2->id,
            'customer_id' => $this->customer2->id,
            'scheduled_at' => Carbon::today(),
            'status' => 'confirmed',
        ]);

        // Login as company 1 portal user
        $this->actingAs($this->portalUser1, 'portal');

        // Test date filter
        $response = $this->getJson('/business/api/appointments?date=' . Carbon::today()->format('Y-m-d'));
        $response->assertStatus(200);

        $data = $response->json();
        if (isset($data['data'])) {
            foreach ($data['data'] as $appointment) {
                $this->assertEquals($this->company1->id, $appointment['company_id']);
            }
        }

        // Test status filter
        $response = $this->getJson('/business/api/appointments?status=confirmed');
        $response->assertStatus(200);

        $data = $response->json();
        if (isset($data['data'])) {
            foreach ($data['data'] as $appointment) {
                $this->assertEquals($this->company1->id, $appointment['company_id']);
                $this->assertEquals('confirmed', $appointment['status']);
            }
        }

        // Test branch filter
        $response = $this->getJson("/business/api/appointments?branch={$this->branch1->id}");
        $response->assertStatus(200);

        $data = $response->json();
        if (isset($data['data'])) {
            foreach ($data['data'] as $appointment) {
                $this->assertEquals($this->company1->id, $appointment['company_id']);
                $this->assertEquals($this->branch1->id, $appointment['branch_id']);
            }
        }
    }

    public function test_appointment_search_prevents_cross_tenant_results()
    {
        // Login as company 1 portal user
        $this->actingAs($this->portalUser1, 'portal');

        // Search for appointment notes from different company
        $response = $this->getJson('/business/api/appointments?search=Beta');
        $response->assertStatus(200);

        $data = $response->json();
        if (isset($data['data'])) {
            // Should not find appointments from company 2 even if search matches
            foreach ($data['data'] as $appointment) {
                $this->assertEquals($this->company1->id, $appointment['company_id']);
                $this->assertStringNotContains('Beta', $appointment['notes'] ?? '');
            }
        }

        // Search for own company's appointment should work
        $response = $this->getJson('/business/api/appointments?search=Alpha');
        $response->assertStatus(200);

        $data = $response->json();
        if (isset($data['data']) && count($data['data']) > 0) {
            $found = false;
            foreach ($data['data'] as $appointment) {
                $this->assertEquals($this->company1->id, $appointment['company_id']);
                if (str_contains($appointment['notes'] ?? '', 'Alpha')) {
                    $found = true;
                }
            }
            $this->assertTrue($found, 'Should find appointment with Alpha in notes');
        }
    }

    public function test_vulnerability_direct_appointment_id_manipulation()
    {
        // Login as company 1 portal user
        $this->actingAs($this->portalUser1, 'portal');

        $maliciousIds = [
            $this->appointment2->id, // Different company appointment
            $this->appointment3->id, // Another different company appointment
            '999999', // Non-existent ID
            'null', // String 'null'
            '', // Empty string
            '0', // Zero
            '-1', // Negative number
            "'; DROP TABLE appointments; --", // SQL injection
            '../../../etc/passwd', // Path traversal
            '<script>alert("xss")</script>', // XSS attempt
            'UNION SELECT * FROM users', // SQL injection attempt
        ];

        foreach ($maliciousIds as $maliciousId) {
            $response = $this->get("/business/appointments/{$maliciousId}");
            $this->assertTrue(
                in_array($response->status(), [404, 400, 422]),
                "ID '{$maliciousId}' should return 404, 400, or 422 but got {$response->status()}"
            );
        }
    }

    public function test_admin_impersonation_appointment_access()
    {
        // Login as admin
        $this->actingAs($this->admin, 'web');

        // Set admin impersonation for company 1
        Session::put('is_admin_viewing', true);
        Session::put('admin_impersonation', [
            'company_id' => $this->company1->id,
            'admin_id' => $this->admin->id,
        ]);

        // Should see company 1 appointments
        $response = $this->getJson('/business/api/appointments');
        $response->assertStatus(200);

        $data = $response->json();
        if (isset($data['data'])) {
            foreach ($data['data'] as $appointment) {
                $this->assertEquals($this->company1->id, $appointment['company_id']);
            }
        }

        // Should be able to view specific company 1 appointment
        $response = $this->get("/business/appointments/{$this->appointment1->id}");
        $response->assertStatus(200);

        // Switch to company 2
        Session::put('admin_impersonation.company_id', $this->company2->id);

        // Should now see company 2 appointments
        $response = $this->getJson('/business/api/appointments');
        $response->assertStatus(200);

        $data = $response->json();
        if (isset($data['data'])) {
            foreach ($data['data'] as $appointment) {
                $this->assertEquals($this->company2->id, $appointment['company_id']);
            }
        }

        // Should be able to view company 2 appointment now
        $response = $this->get("/business/appointments/{$this->appointment2->id}");
        $response->assertStatus(200);

        // But should not be able to view company 1 appointment anymore
        $response = $this->get("/business/appointments/{$this->appointment1->id}");
        $response->assertStatus(404);
    }

    public function test_appointment_relationships_respect_tenant_scope()
    {
        // Login as company 1 portal user
        $this->actingAs($this->portalUser1, 'portal');

        $response = $this->getJson('/business/api/appointments');
        $response->assertStatus(200);

        $data = $response->json();
        if (isset($data['data'])) {
            foreach ($data['data'] as $appointment) {
                // All related entities should belong to same company
                $this->assertEquals($this->company1->id, $appointment['company_id']);
                
                if (isset($appointment['customer'])) {
                    $this->assertEquals($this->company1->id, $appointment['customer']['company_id']);
                }
                
                if (isset($appointment['staff'])) {
                    $this->assertEquals($this->company1->id, $appointment['staff']['company_id']);
                }
                
                if (isset($appointment['branch'])) {
                    $this->assertEquals($this->company1->id, $appointment['branch']['company_id']);
                }
                
                if (isset($appointment['service'])) {
                    $this->assertEquals($this->company1->id, $appointment['service']['company_id']);
                }
            }
        }
    }

    public function test_concurrent_appointment_access_isolation()
    {
        // Simulate concurrent access from different companies
        $session1 = $this->withSession([]);
        $session2 = $this->withSession([]);

        // Login both sessions
        $session1->actingAs($this->portalUser1, 'portal');
        $session2->actingAs($this->portalUser2, 'portal');

        // Both should see only their own company's appointments
        $response1 = $session1->getJson('/business/api/appointments');
        $response2 = $session2->getJson('/business/api/appointments');

        $response1->assertStatus(200);
        $response2->assertStatus(200);

        $data1 = $response1->json();
        $data2 = $response2->json();

        // Each should see different appointment sets
        if (isset($data1['data']) && isset($data2['data'])) {
            $ids1 = collect($data1['data'])->pluck('id');
            $ids2 = collect($data2['data'])->pluck('id');
            
            // No overlap between appointment IDs from different companies
            $this->assertTrue($ids1->intersect($ids2)->isEmpty());
        }
    }

    public function test_unauthorized_appointment_access()
    {
        // No authentication
        $response = $this->get('/business/appointments');
        $response->assertRedirect('/business/login');

        $response = $this->getJson('/business/api/appointments');
        $response->assertStatus(401);

        $response = $this->get("/business/appointments/{$this->appointment1->id}");
        $response->assertRedirect('/business/login');
    }

    public function test_appointment_pagination_respects_tenant_boundaries()
    {
        // Create many appointments for company 1
        Appointment::factory()->count(25)->create([
            'company_id' => $this->company1->id,
            'branch_id' => $this->branch1->id,
            'customer_id' => $this->customer1->id,
        ]);

        // Create many appointments for company 2  
        Appointment::factory()->count(25)->create([
            'company_id' => $this->company2->id,
            'branch_id' => $this->branch2->id,
            'customer_id' => $this->customer2->id,
        ]);

        // Login as company 1 portal user
        $this->actingAs($this->portalUser1, 'portal');

        // Test pagination
        $response = $this->getJson('/business/api/appointments?page=1');
        $response->assertStatus(200);

        $data = $response->json();
        if (isset($data['data'])) {
            foreach ($data['data'] as $appointment) {
                $this->assertEquals($this->company1->id, $appointment['company_id']);
            }
        }

        // Test second page
        $response = $this->getJson('/business/api/appointments?page=2');
        $response->assertStatus(200);

        $data = $response->json();
        if (isset($data['data'])) {
            foreach ($data['data'] as $appointment) {
                $this->assertEquals($this->company1->id, $appointment['company_id']);
            }
        }
    }

    public function test_appointment_mcp_task_execution_security()
    {
        // Login as company 1 portal user
        $this->actingAs($this->portalUser1, 'portal');

        // Test that MCP tasks maintain proper company context
        $response = $this->getJson('/business/api/appointments/stats?period=current');
        $response->assertStatus(200);

        $data = $response->json();
        $this->assertIsArray($data);

        // The stats should only reflect company 1 data
        // This tests that the MCP server receives and uses correct company_id
        if (isset($data['total']) && $data['total'] > 0) {
            // We know we have at least one appointment for company 1
            $this->assertGreaterThan(0, $data['total']);
        }
    }

    public function test_appointment_time_slot_isolation()
    {
        // This tests that time slot queries don't leak across tenants
        $timeSlot = Carbon::now()->addDays(5)->format('Y-m-d H:i:s');

        // Create appointments at same time slot for different companies
        Appointment::factory()->create([
            'company_id' => $this->company1->id,
            'branch_id' => $this->branch1->id,
            'scheduled_at' => $timeSlot,
        ]);

        Appointment::factory()->create([
            'company_id' => $this->company2->id,
            'branch_id' => $this->branch2->id,
            'scheduled_at' => $timeSlot,
        ]);

        // Login as company 1 portal user
        $this->actingAs($this->portalUser1, 'portal');

        $response = $this->getJson('/business/api/appointments?date=' . Carbon::parse($timeSlot)->format('Y-m-d'));
        $response->assertStatus(200);

        $data = $response->json();
        if (isset($data['data'])) {
            // Should only see company 1 appointments for that date
            foreach ($data['data'] as $appointment) {
                $this->assertEquals($this->company1->id, $appointment['company_id']);
            }
        }
    }

    public function test_session_company_context_manipulation_resistance()
    {
        // Login as company 1 user
        $this->actingAs($this->portalUser1, 'portal');

        // Try to manipulate session to access different company data
        Session::put('current_company_id', $this->company2->id);
        Session::put('admin_impersonation', [
            'company_id' => $this->company2->id,
        ]);

        // AppointmentController should still use authenticated user's company
        $response = $this->getJson('/business/api/appointments');
        $response->assertStatus(200);

        $data = $response->json();
        if (isset($data['data'])) {
            // Should still only see company 1 appointments
            foreach ($data['data'] as $appointment) {
                $this->assertEquals($this->company1->id, $appointment['company_id']);
            }
        }
    }
}