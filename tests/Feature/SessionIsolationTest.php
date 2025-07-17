<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\PortalUser;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;

class SessionIsolationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that admin and portal sessions use different cookies
     */
    public function test_admin_and_portal_use_different_session_cookies()
    {
        // Test admin session cookie
        Config::set('session', config('session_admin'));
        $adminCookie = config('session.cookie');
        $this->assertEquals('askproai_admin_session', $adminCookie);
        
        // Test portal session cookie
        Config::set('session', config('session_portal'));
        $portalCookie = config('session.cookie');
        $this->assertEquals('askproai_portal_session', $portalCookie);
        
        // Ensure they are different
        $this->assertNotEquals($adminCookie, $portalCookie);
    }

    /**
     * Test that admin and portal sessions are stored separately
     */
    public function test_admin_and_portal_sessions_are_isolated()
    {
        // Create test data
        $company = Company::factory()->create();
        $adminUser = User::factory()->create();
        $adminUser->assignRole('Super Admin');
        $portalUser = PortalUser::factory()->create(['company_id' => $company->id]);
        
        // Test admin login
        $response = $this->post('/admin/login', [
            'email' => $adminUser->email,
            'password' => 'password',
        ]);
        
        // Check admin session cookie is set
        $response->assertCookie('askproai_admin_session');
        
        // Test portal login
        $response = $this->post('/business/login', [
            'email' => $portalUser->email,
            'password' => 'password',
        ]);
        
        // Check portal session cookie is set
        $response->assertCookie('askproai_portal_session');
    }

    /**
     * Test that sessions don't interfere with each other
     */
    public function test_admin_session_does_not_affect_portal_session()
    {
        $company = Company::factory()->create();
        $adminUser = User::factory()->create();
        $adminUser->assignRole('Super Admin');
        $portalUser = PortalUser::factory()->create(['company_id' => $company->id]);
        
        // Login as admin
        $this->actingAs($adminUser, 'web');
        
        // Access admin panel
        $response = $this->get('/admin');
        $response->assertStatus(200);
        
        // Now try to access portal - should not be authenticated
        $response = $this->get('/business/dashboard');
        $response->assertRedirect('/business/login');
        
        // Login as portal user
        $this->actingAs($portalUser, 'portal');
        
        // Access portal
        $response = $this->get('/business/dashboard');
        $response->assertStatus(200);
        
        // Admin should still be logged in
        $this->assertAuthenticatedAs($adminUser, 'web');
        $this->assertAuthenticatedAs($portalUser, 'portal');
    }

    /**
     * Test TenantScope uses correct company context
     */
    public function test_tenant_scope_uses_correct_guard_context()
    {
        $company1 = Company::factory()->create();
        $company2 = Company::factory()->create();
        
        $portalUser1 = PortalUser::factory()->create(['company_id' => $company1->id]);
        $portalUser2 = PortalUser::factory()->create(['company_id' => $company2->id]);
        
        // Login as portal user 1
        $this->actingAs($portalUser1, 'portal');
        
        // Should only see company 1 data
        app()->instance('current_company_id', null); // Clear any cached value
        $this->assertEquals($company1->id, $this->getTenantCompanyId());
        
        // Login as portal user 2
        $this->actingAs($portalUser2, 'portal');
        
        // Should only see company 2 data
        app()->instance('current_company_id', null); // Clear any cached value
        $this->assertEquals($company2->id, $this->getTenantCompanyId());
    }
    
    /**
     * Helper to get company ID from TenantScope
     */
    private function getTenantCompanyId()
    {
        $reflection = new \ReflectionClass(\App\Scopes\TenantScope::class);
        $method = $reflection->getMethod('getCurrentCompanyId');
        $method->setAccessible(true);
        
        $tenantScope = new \App\Scopes\TenantScope();
        return $method->invoke($tenantScope);
    }
}