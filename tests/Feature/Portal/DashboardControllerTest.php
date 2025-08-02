<?php

namespace Tests\Feature\Portal;

use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Call;
use App\Models\Appointment;
use App\Traits\UsesMCPServers;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Auth;
use Mockery;

class DashboardControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected Company $company;
    protected User $user;
    protected User $adminUser;
    protected Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test company with realistic data
        $this->company = Company::factory()->create([
            'name' => 'Test Medical Practice',
            'settings' => [
                'appointment_booking_enabled' => true,
                'billing_enabled' => true,
                'multi_branch' => false
            ]
        ]);

        // Create test branch
        $this->branch = Branch::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Main Office',
            'is_active' => true
        ]);

        // Create regular user
        $this->user = User::factory()->create([
            'company_id' => $this->company->id,
            'email' => 'user@testpractice.com',
            'name' => 'Test User',
            'permissions' => [
                'dashboard.view' => true,
                'calls.view_own' => true,
                'appointments.view' => true,
                'billing.view' => false
            ]
        ]);

        // Create admin user with full permissions
        $this->adminUser = User::factory()->create([
            'company_id' => $this->company->id,
            'email' => 'admin@testpractice.com',
            'name' => 'Admin User',
            'permissions' => [
                'dashboard.view' => true,
                'calls.view_all' => true,
                'appointments.view' => true,
                'billing.view' => true,
                'analytics.view_team' => true
            ]
        ]);

        // Create test data
        $this->createTestData();
        
        // Mock MCP services
        $this->mockMCPServices();
    }

    protected function createTestData(): void
    {
        // Create calls for testing
        Call::factory()->count(5)->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'status' => 'completed',
            'call_ended_at' => now()->subHours(2)
        ]);

        Call::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'status' => 'in_progress',
            'call_started_at' => now()->subMinutes(30)
        ]);

        // Create appointments for testing
        Appointment::factory()->count(8)->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'status' => 'confirmed',
            'scheduled_at' => now()->addDays(1)
        ]);

        Appointment::factory()->count(2)->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'status' => 'completed',
            'scheduled_at' => now()->subDay()
        ]);
    }

    protected function mockMCPServices(): void
    {
        // Mock the UsesMCPServers trait executeMCPTask method
        $this->mock('alias:' . UsesMCPServers::class, function ($mock) {
            $mock->shouldReceive('executeMCPTask')
                ->with('getDashboardStatistics', Mockery::any())
                ->andReturn([
                    'success' => true,
                    'result' => [
                        'data' => [
                            'total_calls' => 8,
                            'completed_calls' => 5,
                            'active_calls' => 3,
                            'total_appointments' => 10,
                            'confirmed_appointments' => 8,
                            'revenue_today' => 1250.00,
                            'conversion_rate' => 62.5
                        ]
                    ]
                ]);

            $mock->shouldReceive('executeMCPTask')
                ->with('getRecentCalls', Mockery::any())
                ->andReturn([
                    'success' => true,
                    'result' => [
                        'data' => [
                            [
                                'id' => 'call_123',
                                'customer_phone' => '+49123456789',
                                'status' => 'completed',
                                'duration' => 180,
                                'created_at' => now()->subHours(1)->toISOString()
                            ],
                            [
                                'id' => 'call_124',
                                'customer_phone' => '+49987654321',
                                'status' => 'in_progress',
                                'duration' => 0,
                                'created_at' => now()->subMinutes(15)->toISOString()
                            ]
                        ]
                    ]
                ]);

            $mock->shouldReceive('executeMCPTask')
                ->with('getUpcomingTasks', Mockery::any())
                ->andReturn([
                    'success' => true,
                    'result' => [
                        'data' => [
                            [
                                'id' => 'task_1',
                                'title' => 'Follow up with patient',
                                'due_at' => now()->addHours(2)->toISOString(),
                                'priority' => 'high'
                            ]
                        ]
                    ]
                ]);

            $mock->shouldReceive('executeMCPTask')
                ->with('getTeamPerformance', Mockery::any())
                ->andReturn([
                    'success' => true,
                    'result' => [
                        'data' => [
                            'team_calls_handled' => 45,
                            'average_call_duration' => 165,
                            'team_conversion_rate' => 68.2,
                            'top_performer' => 'Dr. Smith'
                        ]
                    ]
                ]);
        });
    }

    /** @test */
    public function dashboard_index_requires_authentication()
    {
        $response = $this->get('/business/dashboard');
        
        $response->assertRedirect('/business/login');
    }

    /** @test */
    public function authenticated_user_can_access_dashboard()
    {
        $response = $this->actingAs($this->user, 'portal')
            ->get('/business/dashboard');

        $response->assertStatus(200);
        $response->assertViewIs('portal.dashboard-unified');
        $response->assertViewHas(['user', 'stats', 'recentCalls', 'upcomingTasks']);
    }

    /** @test */
    public function dashboard_loads_with_correct_statistics()
    {
        $response = $this->actingAs($this->adminUser, 'portal')
            ->get('/business/dashboard');

        $response->assertStatus(200);
        
        // Verify stats are passed to view
        $stats = $response->viewData('stats');
        $this->assertArrayHasKey('total_calls', $stats);
        $this->assertArrayHasKey('completed_calls', $stats);
        $this->assertArrayHasKey('total_appointments', $stats);
        $this->assertEquals(8, $stats['total_calls']);
        $this->assertEquals(5, $stats['completed_calls']);
    }

    /** @test */
    public function dashboard_shows_recent_calls()
    {
        $response = $this->actingAs($this->user, 'portal')
            ->get('/business/dashboard');

        $response->assertStatus(200);
        
        $recentCalls = $response->viewData('recentCalls');
        $this->assertIsArray($recentCalls);
        $this->assertCount(2, $recentCalls);
        $this->assertEquals('call_123', $recentCalls[0]['id']);
        $this->assertEquals('+49123456789', $recentCalls[0]['customer_phone']);
    }

    /** @test */
    public function dashboard_shows_team_performance_for_admin_users()
    {
        $response = $this->actingAs($this->adminUser, 'portal')
            ->get('/business/dashboard');

        $response->assertStatus(200);
        
        $teamPerformance = $response->viewData('teamPerformance');
        $this->assertIsArray($teamPerformance);
        $this->assertArrayHasKey('team_calls_handled', $teamPerformance);
        $this->assertEquals(45, $teamPerformance['team_calls_handled']);
    }

    /** @test */
    public function dashboard_hides_team_performance_for_regular_users()
    {
        $response = $this->actingAs($this->user, 'portal')
            ->get('/business/dashboard');

        $response->assertStatus(200);
        
        $teamPerformance = $response->viewData('teamPerformance');
        $this->assertNull($teamPerformance);
    }

    /** @test */
    public function api_dashboard_endpoint_requires_authentication()
    {
        $response = $this->getJson('/business/api/dashboard');
        
        $response->assertStatus(401);
    }

    /** @test */
    public function api_dashboard_returns_structured_data()
    {
        $response = $this->actingAs($this->user, 'portal')
            ->getJson('/business/api/dashboard');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'statistics' => [
                'total_calls',
                'completed_calls',
                'active_calls',
                'total_appointments'
            ],
            'call_trends',
            'insights',
            'recent_calls'
        ]);
    }

    /** @test */
    public function api_dashboard_respects_user_permissions()
    {
        // User without billing permissions shouldn't see revenue data
        $response = $this->actingAs($this->user, 'portal')
            ->getJson('/business/api/dashboard');

        $response->assertStatus(200);
        
        $statistics = $response->json('statistics');
        $this->assertArrayNotHasKey('revenue_today', $statistics);
    }

    /** @test */
    public function api_analytics_endpoint_handles_different_types()
    {
        $testCases = [
            ['type' => 'conversion', 'expected_structure' => ['conversion_rate', 'total_calls']],
            ['type' => 'revenue', 'expected_structure' => ['total_revenue', 'period']],
            ['type' => 'team', 'expected_structure' => ['team_calls_handled', 'average_call_duration']],
            ['type' => 'overview', 'expected_structure' => ['statistics', 'trends']]
        ];

        foreach ($testCases as $testCase) {
            $response = $this->actingAs($this->adminUser, 'portal')
                ->getJson('/business/api/dashboard/analytics?type=' . $testCase['type']);

            $response->assertStatus(200);
            
            if ($testCase['type'] === 'overview') {
                $response->assertJsonStructure($testCase['expected_structure']);
            } else {
                $data = $response->json();
                $this->assertIsArray($data);
            }
        }
    }

    /** @test */
    public function admin_viewing_mode_works_correctly()
    {
        // Simulate admin impersonation session
        session([
            'is_admin_viewing' => true,
            'admin_impersonation' => [
                'company_id' => $this->company->id,
                'admin_user_id' => 1
            ]
        ]);

        $response = $this->get('/business/dashboard');

        $response->assertStatus(200);
        $response->assertViewIs('portal.dashboard-unified');
        
        // Verify admin user object is created
        $user = $response->viewData('user');
        $this->assertEquals('admin', $user->id);
        $this->assertEquals($this->company->id, $user->company_id);
        $this->assertTrue($user->canViewBilling());
    }

    /** @test */
    public function admin_viewing_mode_fails_with_invalid_company()
    {
        session([
            'is_admin_viewing' => true,
            'admin_impersonation' => [
                'company_id' => 99999 // Non-existent company
            ]
        ]);

        $response = $this->get('/business/dashboard');

        $response->assertStatus(404);
    }

    /** @test */
    public function admin_viewing_mode_fails_without_company_id()
    {
        session([
            'is_admin_viewing' => true,
            'admin_impersonation' => [] // Missing company_id
        ]);

        $response = $this->get('/business/dashboard');

        $response->assertStatus(403);
    }

    /** @test */
    public function dashboard_performance_is_acceptable()
    {
        $startTime = microtime(true);
        
        $response = $this->actingAs($this->user, 'portal')
            ->get('/business/dashboard');
        
        $endTime = microtime(true);
        $responseTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

        $response->assertStatus(200);
        
        // Dashboard should load within 1 second (1000ms)
        $this->assertLessThan(1000, $responseTime, "Dashboard took {$responseTime}ms to load, which exceeds the 1000ms target");
    }

    /** @test */
    public function dashboard_handles_mcp_service_failures_gracefully()
    {
        // Mock MCP service failure
        $this->mock('alias:' . UsesMCPServers::class, function ($mock) {
            $mock->shouldReceive('executeMCPTask')
                ->andReturn([
                    'success' => false,
                    'error' => 'MCP service unavailable'
                ]);
        });

        $response = $this->actingAs($this->user, 'portal')
            ->get('/business/dashboard');

        $response->assertStatus(200);
        
        // Should still load with empty data arrays
        $stats = $response->viewData('stats');
        $this->assertIsArray($stats);
        $this->assertEmpty($stats);
    }

    /** @test */
    public function api_dashboard_handles_no_company_context()
    {
        // Create user without company context
        $orphanUser = User::factory()->create(['company_id' => null]);

        $response = $this->actingAs($orphanUser, 'portal')
            ->getJson('/business/api/dashboard');

        $response->assertStatus(403);
        $response->assertJson(['error' => 'No company context']);
    }

    /** @test */
    public function dashboard_caches_expensive_operations()
    {
        // First request
        $response1 = $this->actingAs($this->user, 'portal')
            ->get('/business/dashboard');
        
        // Second request should be faster due to caching
        $startTime = microtime(true);
        $response2 = $this->actingAs($this->user, 'portal')
            ->get('/business/dashboard');
        $endTime = microtime(true);
        
        $responseTime = ($endTime - $startTime) * 1000;
        
        $response1->assertStatus(200);
        $response2->assertStatus(200);
        
        // Cached request should be significantly faster
        $this->assertLessThan(500, $responseTime, "Cached dashboard request took {$responseTime}ms, caching may not be working");
    }

    /** @test */
    public function dashboard_data_is_tenant_isolated()
    {
        // Create another company and user
        $otherCompany = Company::factory()->create();
        $otherUser = User::factory()->create(['company_id' => $otherCompany->id]);
        
        // Create calls for the other company
        Call::factory()->count(3)->create([
            'company_id' => $otherCompany->id,
            'status' => 'completed'
        ]);

        $response = $this->actingAs($this->user, 'portal')
            ->getJson('/business/api/dashboard');

        $response->assertStatus(200);
        
        // Should only see data from own company
        $statistics = $response->json('statistics');
        $this->assertEquals(8, $statistics['total_calls']); // Only our test company's calls
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}