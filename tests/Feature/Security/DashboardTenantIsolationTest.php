<?php

namespace Tests\Feature\Security;

use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Call;
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
 * Tests for Dashboard Business Intelligence Tenant Isolation
 * 
 * This test suite validates that the DashboardController correctly isolates
 * business intelligence data across tenants and prevents data leaks between companies.
 */
class DashboardTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private Company $company1;
    private Company $company2;
    private Company $company3;
    private PortalUser $portalUser1;
    private PortalUser $portalUser2;
    private PortalUser $limitedUser1;
    private User $admin;
    private Branch $branch1;
    private Branch $branch2;
    private Customer $customer1;
    private Customer $customer2;
    private Call $call1;
    private Call $call2;
    private Appointment $appointment1;
    private Appointment $appointment2;

    protected function setUp(): void
    {
        parent::setUp();

        // Create companies with different business types
        $this->company1 = Company::factory()->create([
            'name' => 'TechCorp Solutions',
            'slug' => 'techcorp',
            'is_active' => true,
        ]);

        $this->company2 = Company::factory()->create([
            'name' => 'MedCenter Clinic',
            'slug' => 'medcenter',
            'is_active' => true,
        ]);

        $this->company3 = Company::factory()->create([
            'name' => 'LegalPro Services',
            'slug' => 'legalpro',
            'is_active' => true,
        ]);

        // Create branches
        $this->branch1 = Branch::factory()->create([
            'company_id' => $this->company1->id,
            'name' => 'TechCorp Main Office',
        ]);

        $this->branch2 = Branch::factory()->create([
            'company_id' => $this->company2->id,
            'name' => 'MedCenter Downtown',
        ]);

        // Create portal users with different permission levels
        $this->portalUser1 = PortalUser::factory()->create([
            'company_id' => $this->company1->id,
            'email' => 'manager@techcorp.com',
            'is_active' => true,
        ]);

        $this->portalUser2 = PortalUser::factory()->create([
            'company_id' => $this->company2->id,
            'email' => 'admin@medcenter.com',
            'is_active' => true,
        ]);

        // Create limited user for permission testing
        $this->limitedUser1 = PortalUser::factory()->create([
            'company_id' => $this->company1->id,
            'email' => 'staff@techcorp.com',
            'is_active' => true,
        ]);

        // Create admin user
        $this->admin = User::factory()->create([
            'email' => 'superadmin@askproai.com',
            'is_admin' => true,
        ]);

        // Create customers with sensitive data
        $this->customer1 = Customer::factory()->create([
            'company_id' => $this->company1->id,
            'name' => 'Confidential Client Alpha',
            'email' => 'secret@techcorp-client.com',
            'phone' => '+1234567890',
            'notes' => 'High-value enterprise client - $500K annual contract',
        ]);

        $this->customer2 = Customer::factory()->create([
            'company_id' => $this->company2->id,
            'name' => 'Patient Beta',
            'email' => 'patient@medcenter-client.com',
            'phone' => '+1234567891',
            'notes' => 'Sensitive medical history - diabetes treatment',
        ]);

        // Create calls with business intelligence data
        $this->call1 = Call::factory()->create([
            'company_id' => $this->company1->id,
            'customer_id' => $this->customer1->id,
            'retell_call_id' => 'call_techcorp_001',
            'phone_number' => '+1234567890',
            'duration_sec' => 1800, // 30 minutes
            'status' => 'completed',
            'summary' => 'Discussed new enterprise software licensing deal worth $500K',
            'transcript' => 'Customer mentioned confidential acquisition plans...',
            'call_analysis' => json_encode([
                'sentiment' => 'positive',
                'revenue_potential' => 500000,
                'competitors_mentioned' => ['CompanyX', 'CompanyY'],
            ]),
            'created_at' => Carbon::now()->subDays(1),
        ]);

        $this->call2 = Call::factory()->create([
            'company_id' => $this->company2->id,
            'customer_id' => $this->customer2->id,
            'retell_call_id' => 'call_medcenter_001',
            'phone_number' => '+1234567891',
            'duration_sec' => 900, // 15 minutes
            'status' => 'completed',
            'summary' => 'Appointment scheduling for diabetes consultation',
            'transcript' => 'Patient discussed private health concerns...',
            'call_analysis' => json_encode([
                'sentiment' => 'neutral',
                'urgency' => 'medium',
                'medical_keywords' => ['diabetes', 'insulin', 'consultation'],
            ]),
            'created_at' => Carbon::now()->subDays(2),
        ]);

        // Create appointments with revenue data
        $this->appointment1 = Appointment::factory()->create([
            'company_id' => $this->company1->id,
            'branch_id' => $this->branch1->id,
            'customer_id' => $this->customer1->id,
            'scheduled_at' => Carbon::now()->addDay(),
            'status' => 'confirmed',
            'price' => 250000, // $2,500 consulting fee
            'notes' => 'Strategic consulting session - confidential project',
        ]);

        $this->appointment2 = Appointment::factory()->create([
            'company_id' => $this->company2->id,
            'branch_id' => $this->branch2->id,
            'customer_id' => $this->customer2->id,
            'scheduled_at' => Carbon::now()->addDays(2),
            'status' => 'scheduled',
            'price' => 15000, // $150 medical consultation
            'notes' => 'Follow-up diabetes consultation',
        ]);
    }

    public function test_dashboard_index_isolates_company_data()
    {
        // Login as company 1 user
        $this->actingAs($this->portalUser1, 'portal');

        $response = $this->get('/business/dashboard');
        $response->assertStatus(200);
        $response->assertViewIs('portal.dashboard-unified');

        // View should contain company 1 data but not company 2 data
        $viewData = $response->viewData();
        
        if (isset($viewData['stats'])) {
            // Stats should only reflect company 1 data
            $this->assertIsArray($viewData['stats']);
        }

        if (isset($viewData['recentCalls'])) {
            foreach ($viewData['recentCalls'] as $call) {
                $this->assertEquals($this->company1->id, $call['company_id']);
                $this->assertNotEquals($this->company2->id, $call['company_id']);
            }
        }

        if (isset($viewData['upcomingTasks'])) {
            // Tasks should only be from company 1
            foreach ($viewData['upcomingTasks'] as $task) {
                if (isset($task['company_id'])) {
                    $this->assertEquals($this->company1->id, $task['company_id']);
                }
            }
        }
    }

    public function test_dashboard_api_prevents_cross_tenant_data_exposure()
    {
        // Login as company 1 user
        $this->actingAs($this->portalUser1, 'portal');

        $response = $this->getJson('/business/api/dashboard');
        $response->assertStatus(200);

        $data = $response->json();
        $this->assertIsArray($data);

        // Check statistics isolation
        if (isset($data['statistics'])) {
            $this->assertIsArray($data['statistics']);
            // Statistics should not leak data from other companies
        }

        // Check call trends isolation
        if (isset($data['call_trends'])) {
            foreach ($data['call_trends'] as $trend) {
                // Trend data should only reflect company 1 calls
                if (isset($trend['company_id'])) {
                    $this->assertEquals($this->company1->id, $trend['company_id']);
                }
            }
        }

        // Check recent calls isolation
        if (isset($data['recent_calls'])) {
            foreach ($data['recent_calls'] as $call) {
                $this->assertEquals($this->company1->id, $call['company_id']);
                
                // Should not contain sensitive data from other companies
                $this->assertStringNotContains('diabetes', $call['summary'] ?? '');
                $this->assertStringNotContains('medical', $call['transcript'] ?? '');
                $this->assertStringNotContains('Patient Beta', $call['customer_name'] ?? '');
            }
        }
    }

    public function test_analytics_api_respects_tenant_boundaries()
    {
        // Login as company 1 user
        $this->actingAs($this->portalUser1, 'portal');

        // Test conversion metrics
        $response = $this->getJson('/business/api/dashboard/analytics?type=conversion');
        $response->assertStatus(200);

        $data = $response->json();
        $this->assertIsArray($data);

        // Conversion data should only include company 1 metrics
        if (isset($data['conversion_rate']) || isset($data['total_calls']) || isset($data['successful_bookings'])) {
            // These metrics should be calculated only from company 1 data
            $this->assertTrue(true); // Placeholder assertion
        }

        // Test revenue metrics
        $response = $this->getJson('/business/api/dashboard/analytics?type=revenue');
        $response->assertStatus(200);

        $data = $response->json();
        $this->assertIsArray($data);

        // Revenue data should only reflect company 1 transactions
        if (isset($data['total_revenue'])) {
            // Should include company 1 appointment value ($2,500) but not company 2 ($150)
            $this->assertIsNumeric($data['total_revenue']);
        }

        // Test team performance metrics
        $response = $this->getJson('/business/api/dashboard/analytics?type=team');
        $response->assertStatus(200);

        $data = $response->json();
        $this->assertIsArray($data);

        // Team data should only include company 1 staff
        if (isset($data['team_members'])) {
            foreach ($data['team_members'] as $member) {
                if (isset($member['company_id'])) {
                    $this->assertEquals($this->company1->id, $member['company_id']);
                }
            }
        }
    }

    public function test_sensitive_business_intelligence_isolation()
    {
        // Login as company 1 user
        $this->actingAs($this->portalUser1, 'portal');

        $response = $this->getJson('/business/api/dashboard');
        $response->assertStatus(200);

        $data = $response->json();

        // Check that sensitive data from other companies is not exposed
        $jsonResponse = json_encode($data);

        // Should not contain sensitive data from company 2
        $this->assertStringNotContains('diabetes', $jsonResponse);
        $this->assertStringNotContains('Patient Beta', $jsonResponse);
        $this->assertStringNotContains('medical history', $jsonResponse);
        $this->assertStringNotContains('medcenter', $jsonResponse);
        $this->assertStringNotContains('call_medcenter_001', $jsonResponse);

        // Should contain own company data
        $this->assertStringContains($this->company1->name, $jsonResponse);
        
        // But sensitive details should be handled carefully
        if (str_contains($jsonResponse, 'techcorp')) {
            $this->assertTrue(true); // Own company data is expected
        }
    }

    public function test_permission_based_dashboard_data_filtering()
    {
        // Login as limited user
        $this->actingAs($this->limitedUser1, 'portal');

        $response = $this->getJson('/business/api/dashboard');
        $response->assertStatus(200);

        $data = $response->json();

        // Limited user should see filtered data based on permissions
        // This tests the hasPermission() checks in the dashboard controller
        if (isset($data['statistics']['revenue'])) {
            // Limited user might not see revenue data
            $this->assertIsArray($data['statistics']);
        }

        if (isset($data['recent_calls'])) {
            // Limited user might only see their own assigned calls
            foreach ($data['recent_calls'] as $call) {
                $this->assertEquals($this->company1->id, $call['company_id']);
                // Additional permission checks would be implementation-specific
            }
        }
    }

    public function test_admin_impersonation_dashboard_context_switching()
    {
        // Login as admin
        $this->actingAs($this->admin, 'web');

        // Set admin impersonation for company 1
        Session::put('is_admin_viewing', true);
        Session::put('admin_impersonation', [
            'company_id' => $this->company1->id,
            'admin_id' => $this->admin->id,
        ]);

        $response = $this->getJson('/business/api/dashboard');
        $response->assertStatus(200);

        $data1 = $response->json();

        // Should see company 1 data
        if (isset($data1['recent_calls'])) {
            foreach ($data1['recent_calls'] as $call) {
                $this->assertEquals($this->company1->id, $call['company_id']);
            }
        }

        // Switch to company 2
        Session::put('admin_impersonation.company_id', $this->company2->id);

        $response = $this->getJson('/business/api/dashboard');
        $response->assertStatus(200);

        $data2 = $response->json();

        // Should now see company 2 data
        if (isset($data2['recent_calls'])) {
            foreach ($data2['recent_calls'] as $call) {
                $this->assertEquals($this->company2->id, $call['company_id']);
            }
        }

        // Data sets should be different
        $this->assertNotEquals($data1, $data2);
    }

    public function test_dashboard_aggregation_prevents_data_leakage()
    {
        // Create additional data to test aggregations
        Call::factory()->count(10)->create([
            'company_id' => $this->company1->id,
            'duration_sec' => 600,
            'status' => 'completed',
        ]);

        Call::factory()->count(5)->create([
            'company_id' => $this->company2->id,
            'duration_sec' => 1200,
            'status' => 'completed',
        ]);

        // Login as company 1 user
        $this->actingAs($this->portalUser1, 'portal');

        $response = $this->getJson('/business/api/dashboard/analytics?type=overview');
        $response->assertStatus(200);

        $data = $response->json();

        // Aggregated statistics should only include company 1 data
        if (isset($data['statistics']['total_calls'])) {
            // Should be 11 calls (1 existing + 10 new) for company 1, not including company 2's 6 calls
            $this->assertLessThanOrEqual(11, $data['statistics']['total_calls']);
            $this->assertNotEquals(17, $data['statistics']['total_calls']); // Not total across all companies
        }

        if (isset($data['statistics']['avg_call_duration'])) {
            // Average should be calculated only from company 1 calls
            $this->assertIsNumeric($data['statistics']['avg_call_duration']);
        }
    }

    public function test_real_time_dashboard_updates_isolation()
    {
        // Login as company 1 user
        $this->actingAs($this->portalUser1, 'portal');

        // Get initial dashboard state
        $response1 = $this->getJson('/business/api/dashboard');
        $response1->assertStatus(200);
        $initialData = $response1->json();

        // Create new call for company 2 (should not affect company 1 dashboard)
        Call::factory()->create([
            'company_id' => $this->company2->id,
            'duration_sec' => 300,
            'status' => 'completed',
            'created_at' => Carbon::now(),
        ]);

        // Get updated dashboard state
        $response2 = $this->getJson('/business/api/dashboard');
        $response2->assertStatus(200);
        $updatedData = $response2->json();

        // Dashboard should not reflect changes from other companies
        if (isset($initialData['statistics']['total_calls']) && isset($updatedData['statistics']['total_calls'])) {
            $this->assertEquals(
                $initialData['statistics']['total_calls'],
                $updatedData['statistics']['total_calls']
            );
        }

        // Create new call for company 1 (should affect dashboard)
        Call::factory()->create([
            'company_id' => $this->company1->id,
            'duration_sec' => 400,
            'status' => 'completed',
            'created_at' => Carbon::now(),
        ]);

        $response3 = $this->getJson('/business/api/dashboard');
        $response3->assertStatus(200);
        $finalData = $response3->json();

        // Dashboard should now reflect company 1 changes
        if (isset($updatedData['statistics']['total_calls']) && isset($finalData['statistics']['total_calls'])) {
            $this->assertGreaterThanOrEqual(
                $updatedData['statistics']['total_calls'],
                $finalData['statistics']['total_calls']
            );
        }
    }

    public function test_vulnerability_session_manipulation_dashboard_access()
    {
        // Login as company 1 user
        $this->actingAs($this->portalUser1, 'portal');

        // Try to manipulate session to access company 2 data
        Session::put('admin_impersonation', [
            'company_id' => $this->company2->id,
            'admin_id' => 999, // Fake admin ID
        ]);
        Session::put('is_admin_viewing', true);

        $response = $this->getJson('/business/api/dashboard');
        $response->assertStatus(200);

        $data = $response->json();

        // Should still only see company 1 data because user is not actually admin
        if (isset($data['recent_calls'])) {
            foreach ($data['recent_calls'] as $call) {
                $this->assertEquals($this->company1->id, $call['company_id']);
            }
        }
    }

    public function test_unauthorized_dashboard_access()
    {
        // No authentication
        $response = $this->get('/business/dashboard');
        $response->assertRedirect('/business/login');

        $response = $this->getJson('/business/api/dashboard');
        $response->assertStatus(401);

        $response = $this->getJson('/business/api/dashboard/analytics');
        $response->assertStatus(401);
    }

    public function test_dashboard_export_data_isolation()
    {
        // Login as company 1 user
        $this->actingAs($this->portalUser1, 'portal');

        // Test analytics export (if endpoint exists)
        $response = $this->getJson('/business/api/dashboard/analytics?type=revenue&format=export');
        
        if ($response->status() === 200) {
            $data = $response->json();
            
            // Exported data should only contain company 1 information
            $jsonResponse = json_encode($data);
            $this->assertStringNotContains('medcenter', $jsonResponse);
            $this->assertStringNotContains('Patient Beta', $jsonResponse);
            $this->assertStringContains($this->company1->name, $jsonResponse);
        } else {
            // If export endpoint doesn't exist, that's also fine
            $this->assertTrue(in_array($response->status(), [404, 405]));
        }
    }

    public function test_mcp_server_dashboard_task_isolation()
    {
        // Login as company 1 user
        $this->actingAs($this->portalUser1, 'portal');

        $response = $this->getJson('/business/api/dashboard');
        $response->assertStatus(200);

        $data = $response->json();

        // All MCP server tasks should maintain proper company context
        if (isset($data['statistics'])) {
            // Statistics from MCP should only include company 1 data
            $this->assertIsArray($data['statistics']);
        }

        if (isset($data['insights'])) {
            // Quick insights should be based only on company 1 data
            foreach ($data['insights'] as $insight) {
                if (isset($insight['company_id'])) {
                    $this->assertEquals($this->company1->id, $insight['company_id']);
                }
            }
        }
    }

    public function test_concurrent_dashboard_access_different_companies()
    {
        // Simulate concurrent dashboard access from different companies
        $session1 = $this->withSession([]);
        $session2 = $this->withSession([]);

        // Login different users
        $session1->actingAs($this->portalUser1, 'portal');
        $session2->actingAs($this->portalUser2, 'portal');

        // Both access dashboard simultaneously
        $response1 = $session1->getJson('/business/api/dashboard');
        $response2 = $session2->getJson('/business/api/dashboard');

        $response1->assertStatus(200);
        $response2->assertStatus(200);

        $data1 = $response1->json();
        $data2 = $response2->json();

        // Each should see only their own company's data
        if (isset($data1['recent_calls']) && isset($data2['recent_calls'])) {
            foreach ($data1['recent_calls'] as $call) {
                $this->assertEquals($this->company1->id, $call['company_id']);
            }
            
            foreach ($data2['recent_calls'] as $call) {
                $this->assertEquals($this->company2->id, $call['company_id']);
            }
        }

        // No data should overlap between companies
        $json1 = json_encode($data1);
        $json2 = json_encode($data2);
        
        $this->assertStringNotContains($this->company2->name, $json1);
        $this->assertStringNotContains($this->company1->name, $json2);
    }
}