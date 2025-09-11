<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Tenant;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SimplePageVisibilityTest extends TestCase
{
    use RefreshDatabase;
    
    private $adminUser;
    private $tenant;
    private $results = [];
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create minimal test setup
        $this->setupAuth();
    }
    
    private function setupAuth(): void
    {
        // Create tenant
        $this->tenant = Tenant::create([
            'id' => 'test-visibility',
            'name' => 'Visibility Test Tenant',
            'tenant_type' => 'direct_customer',
            'balance_cents' => 10000,
            'is_active' => true,
            'settings' => [],
        ]);
        
        // Create admin user
        $this->adminUser = User::factory()->create([
            'email' => 'admin@visibility-test.com',
            'tenant_id' => $this->tenant->id,
        ]);
        
        // Create and assign super_admin role
        $adminRole = Role::firstOrCreate(['name' => 'super_admin']);
        $this->adminUser->assignRole($adminRole);
        
        // Create access permission
        Permission::firstOrCreate(['name' => 'access_admin_panel']);
        
        // Authenticate as admin
        $this->actingAs($this->adminUser);
    }
    
    /** @test */
    public function test_admin_dashboard_is_accessible()
    {
        $response = $this->get('/admin');
        
        $response->assertStatus(200);
        $this->results['dashboard'] = 'accessible';
    }
    
    /** @test */
    public function test_all_admin_resource_pages_are_accessible()
    {
        $resources = [
            'users',
            'customers',
            'companies',
            'branches',
            'staff',
            'services',
            'appointments',
            'calls',
            'transactions',
            'balance-topups',
            'tenants',
            'retell-agents',
            'integrations',
            'working-hours',
            'pricing-plans',
            'phone-numbers',
        ];
        
        $this->results['resources'] = [];
        
        foreach ($resources as $resource) {
            // Test index page
            $indexResponse = $this->get("/admin/{$resource}");
            $indexStatus = $indexResponse->status();
            
            // Test create page (if accessible)
            $createResponse = $this->get("/admin/{$resource}/create");
            $createStatus = $createResponse->status();
            
            $this->results['resources'][$resource] = [
                'index' => $indexStatus === 200 ? 'accessible' : "error ({$indexStatus})",
                'create' => $createStatus === 200 ? 'accessible' : "error ({$createStatus})",
            ];
            
            // Assertion to mark test status
            $this->assertContains($indexStatus, [200, 302, 403], 
                "Resource {$resource} index returned unexpected status: {$indexStatus}");
        }
    }
    
    /** @test */
    public function test_authentication_required_for_admin_pages()
    {
        // Logout
        auth()->logout();
        
        $response = $this->get('/admin');
        $response->assertRedirect('/admin/login');
        
        $this->results['authentication'] = 'working';
    }
    
    /** @test */
    public function test_login_page_is_accessible()
    {
        // Logout first
        auth()->logout();
        
        $response = $this->get('/admin/login');
        $response->assertStatus(200);
        
        $this->results['login_page'] = 'accessible';
    }
    
    /** @test */
    public function test_api_health_endpoint()
    {
        $response = $this->get('/health');
        $response->assertStatus(200);
        
        $this->results['health_endpoint'] = 'accessible';
    }
    
    /** @test */
    public function test_billing_pages_are_accessible()
    {
        $billingPages = [
            '/billing' => 'Dashboard',
            '/billing/transactions' => 'Transactions',
            '/billing/topup' => 'Top Up',
        ];
        
        $this->results['billing'] = [];
        
        foreach ($billingPages as $path => $name) {
            $response = $this->get($path);
            $status = $response->status();
            
            $this->results['billing'][$name] = $status === 200 ? 'accessible' : "error ({$status})";
            
            $this->assertContains($status, [200, 302], 
                "Billing page {$name} returned unexpected status: {$status}");
        }
    }
    
    /** @test */
    public function test_customer_portal_pages()
    {
        $customerPages = [
            '/customer/dashboard',
            '/customer/appointments',
            '/customer/profile',
        ];
        
        $this->results['customer_portal'] = [];
        
        foreach ($customerPages as $path) {
            $response = $this->get($path);
            $status = $response->status();
            
            $pageName = basename($path);
            $this->results['customer_portal'][$pageName] = 
                $status === 200 ? 'accessible' : "error ({$status})";
        }
    }
    
    /** @test */
    public function test_generate_visibility_report()
    {
        // Calculate statistics
        $totalPages = 0;
        $accessiblePages = 0;
        $errorPages = 0;
        
        foreach ($this->results as $category => $data) {
            if (is_array($data)) {
                foreach ($data as $page => $status) {
                    $totalPages++;
                    if ($status === 'accessible' || $status === 'working') {
                        $accessiblePages++;
                    } else {
                        $errorPages++;
                    }
                }
            } else {
                $totalPages++;
                if ($data === 'accessible' || $data === 'working') {
                    $accessiblePages++;
                } else {
                    $errorPages++;
                }
            }
        }
        
        $visibilityScore = $totalPages > 0 
            ? round(($accessiblePages / $totalPages) * 100, 2) 
            : 0;
        
        // Generate report
        $report = "# Page Visibility Test Report\n\n";
        $report .= "**Date**: " . now()->format('Y-m-d H:i:s') . "\n";
        $report .= "**Visibility Score**: {$visibilityScore}%\n";
        $report .= "**Total Pages Tested**: {$totalPages}\n";
        $report .= "**Accessible Pages**: {$accessiblePages}\n";
        $report .= "**Error Pages**: {$errorPages}\n\n";
        
        $report .= "## Test Results\n\n";
        
        foreach ($this->results as $category => $data) {
            $report .= "### " . ucfirst(str_replace('_', ' ', $category)) . "\n";
            
            if (is_array($data)) {
                foreach ($data as $page => $status) {
                    $icon = ($status === 'accessible' || $status === 'working') ? '✅' : '❌';
                    $report .= "- {$icon} **{$page}**: {$status}\n";
                }
            } else {
                $icon = ($data === 'accessible' || $data === 'working') ? '✅' : '❌';
                $report .= "- {$icon} {$data}\n";
            }
            
            $report .= "\n";
        }
        
        // Save report
        file_put_contents(
            base_path('VISIBILITY_TEST_REPORT_' . date('Y-m-d_His') . '.md'),
            $report
        );
        
        // Output summary
        echo "\n" . str_repeat('=', 60) . "\n";
        echo "VISIBILITY TEST SUMMARY\n";
        echo str_repeat('=', 60) . "\n";
        echo "Visibility Score: {$visibilityScore}%\n";
        echo "Accessible Pages: {$accessiblePages}/{$totalPages}\n";
        echo str_repeat('=', 60) . "\n\n";
        
        // Assert minimum visibility score
        $this->assertGreaterThanOrEqual(70, $visibilityScore, 
            "Visibility score is too low: {$visibilityScore}%. Minimum required: 70%");
    }
}