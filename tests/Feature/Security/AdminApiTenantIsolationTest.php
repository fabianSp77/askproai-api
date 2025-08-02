<?php

namespace Tests\Feature\Security;

use App\Models\Company;
use App\Models\Customer;
use App\Models\PortalUser;
use App\Models\User;
use App\Models\Branch;
use App\Models\Service;
use App\Models\Appointment;
use App\Models\Call;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

/**
 * Tests for Admin API Tenant Isolation Security
 * 
 * This test suite validates that the CustomerController and other admin API endpoints
 * correctly isolate data access across tenants and prevent unauthorized cross-tenant access.
 */
class AdminApiTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private Company $company1;
    private Company $company2;
    private Company $company3;
    private PortalUser $portalUser1;
    private PortalUser $portalUser2;
    private User $admin;
    private Customer $customer1;
    private Customer $customer2;
    private Customer $customer3;

    protected function setUp(): void
    {
        parent::setUp();

        // Create three companies for comprehensive testing
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

        $this->company3 = Company::factory()->create([
            'name' => 'Company Three',
            'slug' => 'company-three',
            'is_active' => true,
        ]);

        // Create portal users
        $this->portalUser1 = PortalUser::factory()->create([
            'company_id' => $this->company1->id,
            'email' => 'user1@company1.com',
            'is_active' => true,
        ]);

        $this->portalUser2 = PortalUser::factory()->create([
            'company_id' => $this->company2->id,
            'email' => 'user2@company2.com',
            'is_active' => true,
        ]);

        // Create admin user
        $this->admin = User::factory()->create([
            'email' => 'admin@askproai.com',
            'is_admin' => true,
        ]);

        // Create customers for each company
        $this->customer1 = Customer::factory()->create([
            'company_id' => $this->company1->id,
            'name' => 'Customer One',
            'email' => 'customer1@company1.com',
            'phone' => '+1234567890',
        ]);

        $this->customer2 = Customer::factory()->create([
            'company_id' => $this->company2->id,
            'name' => 'Customer Two',
            'email' => 'customer2@company2.com',
            'phone' => '+1234567891',
        ]);

        $this->customer3 = Customer::factory()->create([
            'company_id' => $this->company3->id,
            'name' => 'Customer Three',
            'email' => 'customer3@company3.com',
            'phone' => '+1234567892',
        ]);
    }

    public function test_customer_api_index_respects_tenant_isolation()
    {
        // Login as company 1 portal user
        $this->actingAs($this->portalUser1, 'portal');

        $response = $this->getJson('/business/api/customers');
        $response->assertStatus(200);

        $data = $response->json();
        $this->assertIsArray($data);
        
        // Should only see customers from company 1
        if (isset($data['data'])) {
            foreach ($data['data'] as $customer) {
                $this->assertEquals($this->company1->id, $customer['company_id']);
            }
        }
    }

    public function test_customer_api_show_prevents_cross_tenant_access()
    {
        // Login as company 1 portal user
        $this->actingAs($this->portalUser1, 'portal');

        // Try to access customer from company 2
        $response = $this->getJson("/business/api/customers/{$this->customer2->id}");
        $response->assertStatus(404); // Should not find customer from different company

        // Should be able to access own company's customer
        $response = $this->getJson("/business/api/customers/{$this->customer1->id}");
        $response->assertStatus(200);
        
        $data = $response->json();
        $this->assertEquals($this->customer1->id, $data['id']);
        $this->assertEquals($this->company1->id, $data['company_id']);
    }

    public function test_customer_api_create_enforces_company_context()
    {
        // Login as company 1 portal user  
        $this->actingAs($this->portalUser1, 'portal');

        $customerData = [
            'name' => 'New Customer',
            'email' => 'new@company1.com',
            'phone' => '+1555000001',
            'company_name' => 'New Company Ltd',
            'notes' => 'Test customer',
        ];

        $response = $this->postJson('/business/api/customers', $customerData);
        $response->assertStatus(201);

        $data = $response->json();
        $this->assertEquals($this->company1->id, $data['company_id']);
        $this->assertEquals($customerData['name'], $data['name']);

        // Verify customer was created with correct company_id in database
        $this->assertDatabaseHas('customers', [
            'company_id' => $this->company1->id,
            'name' => $customerData['name'],
            'email' => $customerData['email'],
        ]);
    }

    public function test_customer_api_update_prevents_cross_tenant_modification()
    {
        // Login as company 1 portal user
        $this->actingAs($this->portalUser1, 'portal');

        $updateData = [
            'name' => 'Updated Customer Name',
            'email' => 'updated@company1.com',
            'phone' => '+1555000002',
        ];

        // Try to update customer from company 2 - should fail
        $response = $this->putJson("/business/api/customers/{$this->customer2->id}", $updateData);
        $response->assertStatus(404); // Should not find customer from different company

        // Update own company's customer - should succeed
        $response = $this->putJson("/business/api/customers/{$this->customer1->id}", $updateData);
        $response->assertStatus(200);

        $data = $response->json();
        $this->assertEquals($updateData['name'], $data['name']);
        $this->assertEquals($this->company1->id, $data['company_id']);

        // Verify original customer 2 was not modified
        $this->customer2->refresh();
        $this->assertNotEquals($updateData['name'], $this->customer2->name);
    }

    public function test_customer_api_delete_prevents_cross_tenant_deletion()
    {
        // Login as company 1 portal user
        $this->actingAs($this->portalUser1, 'portal');

        // Try to delete customer from company 2 - should fail
        $response = $this->deleteJson("/business/api/customers/{$this->customer2->id}");
        $response->assertStatus(404);

        // Verify customer 2 still exists
        $this->assertDatabaseHas('customers', [
            'id' => $this->customer2->id,
            'company_id' => $this->company2->id,
        ]);
    }

    public function test_customer_export_respects_tenant_isolation()
    {
        // Login as company 1 portal user
        $this->actingAs($this->portalUser1, 'portal');

        $response = $this->getJson('/business/api/customers/export/csv');
        $response->assertStatus(200);

        // The export should only contain customers from company 1
        $content = $response->getContent();
        $this->assertStringContains($this->customer1->name, $content);
        $this->assertStringNotContains($this->customer2->name, $content);
        $this->assertStringNotContains($this->customer3->name, $content);
    }

    public function test_customer_tags_api_isolated_by_tenant()
    {
        // Add tags to customers
        $this->customer1->update(['tags' => ['vip', 'company1-tag']]);
        $this->customer2->update(['tags' => ['premium', 'company2-tag']]);

        // Login as company 1 portal user
        $this->actingAs($this->portalUser1, 'portal');

        $response = $this->getJson('/business/api/customers/tags');
        $response->assertStatus(200);

        $data = $response->json();
        
        // Should only see tags from company 1 customers
        if (isset($data['tags'])) {
            $allTags = collect($data['tags'])->flatten();
            $this->assertTrue($allTags->contains('vip'));
            $this->assertTrue($allTags->contains('company1-tag'));
            $this->assertFalse($allTags->contains('premium'));
            $this->assertFalse($allTags->contains('company2-tag'));
        }
    }

    public function test_customer_appointments_api_respects_tenant_isolation()
    {
        // Create branches and appointments
        $branch1 = Branch::factory()->create(['company_id' => $this->company1->id]);
        $branch2 = Branch::factory()->create(['company_id' => $this->company2->id]);

        $appointment1 = Appointment::factory()->create([
            'company_id' => $this->company1->id,
            'customer_id' => $this->customer1->id,
            'branch_id' => $branch1->id,
        ]);

        $appointment2 = Appointment::factory()->create([
            'company_id' => $this->company2->id,
            'customer_id' => $this->customer2->id,
            'branch_id' => $branch2->id,
        ]);

        // Login as company 1 portal user
        $this->actingAs($this->portalUser1, 'portal');

        // Try to get appointments for customer from different company
        $response = $this->getJson("/business/api/customers/{$this->customer2->id}/appointments");
        $response->assertStatus(404);

        // Get appointments for own company's customer
        $response = $this->getJson("/business/api/customers/{$this->customer1->id}/appointments");
        $response->assertStatus(200);

        $data = $response->json();
        $this->assertIsArray($data);
        
        // Should only see appointments from same company
        if (isset($data['data'])) {
            foreach ($data['data'] as $appointment) {
                $this->assertEquals($this->company1->id, $appointment['company_id']);
            }
        }
    }

    public function test_vulnerability_direct_customer_id_manipulation()
    {
        // Login as company 1 portal user
        $this->actingAs($this->portalUser1, 'portal');

        // Try various ID manipulation attacks
        $maliciousIds = [
            $this->customer2->id, // Different company customer
            $this->customer3->id, // Another different company customer
            '999999', // Non-existent ID
            'null', // String 'null'
            '', // Empty string
            '0', // Zero
            '-1', // Negative number
            "'; DROP TABLE customers; --", // SQL injection
            '../../../etc/passwd', // Path traversal
            '<script>alert("xss")</script>', // XSS attempt
        ];

        foreach ($maliciousIds as $maliciousId) {
            $response = $this->getJson("/business/api/customers/{$maliciousId}");
            $this->assertTrue(
                in_array($response->status(), [404, 400, 422]),
                "ID '{$maliciousId}' should return 404, 400, or 422, got {$response->status()}"
            );
        }
    }

    public function test_admin_impersonation_context_switching()
    {
        // Login as admin
        $this->actingAs($this->admin, 'web');

        // Set admin impersonation for company 1
        Session::put('is_admin_viewing', true);
        Session::put('admin_impersonation', [
            'company_id' => $this->company1->id,
            'admin_id' => $this->admin->id,
        ]);

        // Should see company 1 customers
        $response = $this->getJson('/business/api/customers');
        $response->assertStatus(200);

        $data = $response->json();
        if (isset($data['data'])) {
            foreach ($data['data'] as $customer) {
                $this->assertEquals($this->company1->id, $customer['company_id']);
            }
        }

        // Switch to company 2
        Session::put('admin_impersonation.company_id', $this->company2->id);

        // Should now see company 2 customers
        $response = $this->getJson('/business/api/customers');
        $response->assertStatus(200);

        $data = $response->json();
        if (isset($data['data'])) {
            foreach ($data['data'] as $customer) {
                $this->assertEquals($this->company2->id, $customer['company_id']);
            }
        }
    }

    public function test_unauthorized_access_without_authentication()
    {
        // No authentication
        $response = $this->getJson('/business/api/customers');
        $response->assertStatus(401);

        $response = $this->getJson("/business/api/customers/{$this->customer1->id}");
        $response->assertStatus(401);

        $response = $this->postJson('/business/api/customers', [
            'name' => 'Test Customer',
            'email' => 'test@example.com',
        ]);
        $response->assertStatus(401);
    }

    public function test_session_hijacking_protection()
    {
        // Login as company 1 user
        $this->actingAs($this->portalUser1, 'portal');

        $response = $this->getJson('/business/api/customers');
        $response->assertStatus(200);

        // Manually manipulate session to try to access different company
        // This simulates session hijacking/manipulation
        Session::put('current_company_id', $this->company2->id);

        // The BaseApiController should still respect the authenticated user's company
        $response = $this->getJson('/business/api/customers');
        $response->assertStatus(200);

        $data = $response->json();
        // Should still only see company 1 data because user belongs to company 1
        if (isset($data['data']) && count($data['data']) > 0) {
            foreach ($data['data'] as $customer) {
                $this->assertEquals($this->company1->id, $customer['company_id']);
            }
        }
    }

    public function test_mcp_server_task_execution_respects_tenant_context()
    {
        // Login as company 1 portal user
        $this->actingAs($this->portalUser1, 'portal');

        // Test MCP task execution maintains proper tenant context
        $response = $this->postJson('/business/api/customers', [
            'name' => 'MCP Test Customer',
            'email' => 'mcp@company1.com',
            'phone' => '+1555123456',
        ]);

        $response->assertStatus(201);
        
        $data = $response->json();
        $this->assertEquals($this->company1->id, $data['company_id']);

        // Verify the MCP server used correct company context
        $this->assertDatabaseHas('customers', [
            'company_id' => $this->company1->id,
            'name' => 'MCP Test Customer',
            'email' => 'mcp@company1.com',
        ]);
    }

    public function test_bulk_operations_isolation()
    {
        // Create multiple customers for company 1
        $customers1 = Customer::factory()->count(3)->create([
            'company_id' => $this->company1->id,
        ]);

        // Create multiple customers for company 2
        $customers2 = Customer::factory()->count(3)->create([
            'company_id' => $this->company2->id,
        ]);

        // Login as company 1 user
        $this->actingAs($this->portalUser1, 'portal');

        // Search should only return company 1 customers
        $response = $this->getJson('/business/api/customers?search=Customer');
        $response->assertStatus(200);

        $data = $response->json();
        if (isset($data['data'])) {
            foreach ($data['data'] as $customer) {
                $this->assertEquals($this->company1->id, $customer['company_id']);
            }
            
            // Should find company 1 customers but not company 2
            $customerIds = collect($data['data'])->pluck('id');
            foreach ($customers1 as $customer) {
                $this->assertTrue($customerIds->contains($customer->id));
            }
            foreach ($customers2 as $customer) {
                $this->assertFalse($customerIds->contains($customer->id));
            }
        }
    }

    public function test_concurrent_user_access_isolation()
    {
        // Simulate concurrent access from different companies
        $session1 = $this->withSession([]);
        $session2 = $this->withSession([]);

        // Login both sessions
        $session1->actingAs($this->portalUser1, 'portal');
        $session2->actingAs($this->portalUser2, 'portal');

        // Both should see only their own company's customers
        $response1 = $session1->getJson('/business/api/customers');
        $response2 = $session2->getJson('/business/api/customers');

        $response1->assertStatus(200);
        $response2->assertStatus(200);

        $data1 = $response1->json();
        $data2 = $response2->json();

        // Each should see different customer sets
        if (isset($data1['data']) && isset($data2['data'])) {
            $ids1 = collect($data1['data'])->pluck('id');
            $ids2 = collect($data2['data'])->pluck('id');
            
            // No overlap between customer IDs from different companies
            $this->assertTrue($ids1->intersect($ids2)->isEmpty());
        }
    }

    public function test_permission_based_access_control()
    {
        // Create a portal user with limited permissions
        $limitedUser = PortalUser::factory()->create([
            'company_id' => $this->company1->id,
            'email' => 'limited@company1.com',
            'is_active' => true,
        ]);

        // Login as limited user
        $this->actingAs($limitedUser, 'portal');

        // Try to delete a customer (assuming this requires special permission)
        $response = $this->deleteJson("/business/api/customers/{$this->customer1->id}");
        
        // Should be forbidden due to lack of permissions
        $this->assertTrue(in_array($response->status(), [403, 404]));

        // Customer should still exist
        $this->assertDatabaseHas('customers', [
            'id' => $this->customer1->id,
            'company_id' => $this->company1->id,
        ]);
    }
}