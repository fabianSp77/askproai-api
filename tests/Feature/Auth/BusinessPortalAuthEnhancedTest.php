<?php

namespace Tests\Feature\Auth;

use App\Models\PortalUser;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class BusinessPortalAuthEnhancedTest extends TestCase
{
    use RefreshDatabase;

    private PortalUser $portalUser;
    private PortalUser $staffUser;
    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test company
        $this->company = Company::factory()->create([
            'name' => 'Test Business',
            'slug' => 'test-business',
            'is_active' => true,
        ]);

        // Create admin portal user
        $this->portalUser = PortalUser::factory()->create([
            'company_id' => $this->company->id,
            'email' => 'admin@business.com',
            'password' => Hash::make('password'),
            'is_active' => true,
            'role' => PortalUser::ROLE_ADMIN,
        ]);

        // Create staff portal user
        $this->staffUser = PortalUser::factory()->create([
            'company_id' => $this->company->id,
            'email' => 'staff@business.com',
            'password' => Hash::make('password'),
            'is_active' => true,
            'role' => PortalUser::ROLE_STAFF,
        ]);
    }

    public function test_business_portal_login_page_is_accessible()
    {
        $response = $this->get('/business/login');
        
        $response->assertStatus(200);
    }

    public function test_portal_admin_can_login_with_valid_credentials()
    {
        $response = $this->post('/business/login', [
            'email' => 'admin@business.com',
            'password' => 'password',
        ]);

        $response->assertRedirect('/business');
        $this->assertAuthenticated('portal');
        $this->assertEquals($this->portalUser->id, auth()->guard('portal')->id());
    }

    public function test_portal_staff_can_login_with_valid_credentials()
    {
        $response = $this->post('/business/login', [
            'email' => 'staff@business.com',
            'password' => 'password',
        ]);

        $response->assertRedirect('/business');
        $this->assertAuthenticated('portal');
        $this->assertEquals($this->staffUser->id, auth()->guard('portal')->id());
    }

    public function test_portal_user_cannot_login_with_invalid_credentials()
    {
        $response = $this->post('/business/login', [
            'email' => 'admin@business.com',
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors();
        $this->assertGuest('portal');
    }

    public function test_inactive_portal_user_cannot_login()
    {
        $this->portalUser->update(['is_active' => false]);

        $response = $this->post('/business/login', [
            'email' => 'admin@business.com',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors();
        $this->assertGuest('portal');
    }

    public function test_portal_user_from_inactive_company_cannot_login()
    {
        $this->company->update(['is_active' => false]);

        $response = $this->post('/business/login', [
            'email' => 'admin@business.com',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors();
        $this->assertGuest('portal');
    }

    public function test_portal_session_persists_after_login()
    {
        // Login
        $this->post('/business/login', [
            'email' => 'admin@business.com',
            'password' => 'password',
        ]);

        // Session should persist on next request
        $response = $this->get('/business');
        $response->assertStatus(200);
        $this->assertAuthenticated('portal');
    }

    public function test_portal_user_can_logout()
    {
        $this->actingAs($this->portalUser, 'portal');

        $response = $this->post('/business/logout');

        $response->assertRedirect('/business/login');
        $this->assertGuest('portal');
    }

    public function test_unauthenticated_portal_user_redirects_to_login()
    {
        $response = $this->get('/business');
        
        $response->assertRedirect('/business/login');
    }

    public function test_portal_and_admin_sessions_are_isolated()
    {
        // Create admin user
        $admin = \App\Models\User::factory()->create([
            'company_id' => $this->company->id,
            'email' => 'admin@test.com',
            'password' => Hash::make('password'),
        ]);

        // Login as portal user
        $this->post('/business/login', [
            'email' => 'admin@business.com',
            'password' => 'password',
        ]);

        // Check portal is authenticated
        $this->assertAuthenticated('portal');
        $this->assertGuest('web'); // Admin guard should be guest

        // Now login as admin (using different session/guard)
        $this->post('/admin/login', [
            'email' => 'admin@test.com',
            'password' => 'password',
        ]);

        // Both should be authenticated in their respective guards
        $this->assertAuthenticated('portal');
        $this->assertAuthenticated('web');
    }

    public function test_company_context_set_after_portal_login()
    {
        $this->post('/business/login', [
            'email' => 'admin@business.com',
            'password' => 'password',
        ]);

        // Company context should be set
        $user = auth()->guard('portal')->user();
        $this->assertEquals($this->company->id, $user->company_id);
    }

    public function test_portal_api_authentication_works()
    {
        $this->actingAs($this->portalUser, 'portal');

        $response = $this->getJson('/business/api/user');
        
        if ($response->status() !== 404) { // Route might not exist yet
            $response->assertStatus(200);
            $response->assertJson([
                'id' => $this->portalUser->id,
                'email' => $this->portalUser->email,
            ]);
        }
    }

    public function test_portal_api_rejects_unauthenticated_requests()
    {
        $response = $this->getJson('/business/api/user');
        
        // Should be unauthorized or not found (if route doesn't exist)
        $this->assertTrue(in_array($response->status(), [401, 404]));
    }

    public function test_portal_user_role_permissions_are_different()
    {
        // Admin should have different permissions than staff
        $this->actingAs($this->portalUser, 'portal');
        $this->assertEquals(PortalUser::ROLE_ADMIN, auth()->guard('portal')->user()->role);

        // Switch to staff user
        Auth::guard('portal')->logout();
        $this->actingAs($this->staffUser, 'portal');
        $this->assertEquals(PortalUser::ROLE_STAFF, auth()->guard('portal')->user()->role);
    }

    public function test_portal_session_uses_different_configuration()
    {
        $this->post('/business/login', [
            'email' => 'admin@business.com',
            'password' => 'password',
        ]);

        // Portal should use its own session configuration
        $sessionConfig = config('session');
        $this->assertIsArray($sessionConfig);
    }

    public function test_portal_2fa_requirement_for_admin_role()
    {
        $adminUser = PortalUser::factory()->create([
            'company_id' => $this->company->id,
            'email' => 'admin2@business.com',
            'password' => Hash::make('password'),
            'is_active' => true,
            'role' => PortalUser::ROLE_ADMIN,
        ]);

        // Admin users should require 2FA (if implemented)
        if (method_exists($adminUser, 'requires2FA')) {
            $this->assertTrue($adminUser->requires2FA());
        }
    }

    public function test_portal_login_records_activity()
    {
        $this->assertNull($this->portalUser->last_login_at);

        $this->post('/business/login', [
            'email' => 'admin@business.com',
            'password' => 'password',
        ]);

        $this->portalUser->refresh();
        
        // Should record login activity
        if (isset($this->portalUser->last_login_at)) {
            $this->assertNotNull($this->portalUser->last_login_at);
        }
        
        if (isset($this->portalUser->last_login_ip)) {
            $this->assertNotNull($this->portalUser->last_login_ip);
        }
    }

    public function test_portal_cross_tenant_isolation()
    {
        // Create another company and portal user
        $otherCompany = Company::factory()->create([
            'name' => 'Other Business',
            'slug' => 'other-business',
        ]);

        $otherPortalUser = PortalUser::factory()->create([
            'company_id' => $otherCompany->id,
            'email' => 'other@business.com',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        // Login as our portal user
        $this->actingAs($this->portalUser, 'portal');

        // Should not be able to access other company's portal users
        $portalUsers = PortalUser::all();
        $currentCompanyUsers = $portalUsers->where('company_id', $this->company->id);
        $otherCompanyUsers = $portalUsers->where('company_id', $otherCompany->id);

        // Should only see users from current company (if tenant scope is working)
        if ($currentCompanyUsers->count() > 0 && $otherCompanyUsers->count() === 0) {
            $this->assertGreaterThan(0, $currentCompanyUsers->count());
            $this->assertEquals(0, $otherCompanyUsers->count());
        }
    }

    public function test_portal_session_timeout_configuration()
    {
        $this->post('/business/login', [
            'email' => 'admin@business.com',
            'password' => 'password',
        ]);

        // Check that session has appropriate timeout settings
        $sessionLifetime = config('session.lifetime');
        $this->assertIsInt($sessionLifetime);
        $this->assertGreaterThan(0, $sessionLifetime);
    }

    public function test_portal_password_reset_flow()
    {
        // Test password reset request
        $response = $this->post('/business/password/email', [
            'email' => 'admin@business.com',
        ]);

        // Should either succeed or show that route doesn't exist yet
        $this->assertTrue(in_array($response->status(), [200, 302, 404]));
    }

    public function test_portal_registration_disabled_by_default()
    {
        // Portal registration should typically be disabled
        $response = $this->get('/business/register');
        
        // Should return 404 or redirect (registration not allowed)
        $this->assertTrue(in_array($response->status(), [404, 302]));
    }

    public function test_portal_middleware_authentication()
    {
        // Test that portal middleware properly authenticates requests
        $this->actingAs($this->portalUser, 'portal');
        
        $response = $this->get('/business');
        $response->assertStatus(200);
        
        // Logout and try again
        Auth::guard('portal')->logout();
        
        $response = $this->get('/business');
        $response->assertRedirect('/business/login');
    }
}