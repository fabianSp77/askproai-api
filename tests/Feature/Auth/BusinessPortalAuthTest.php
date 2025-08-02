<?php

namespace Tests\Feature\Auth;

use App\Models\PortalUser;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class BusinessPortalAuthTest extends TestCase
{
    use RefreshDatabase;

    private PortalUser $portalUser;
    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test company
        $this->company = Company::factory()->create();

        // Create portal user
        $this->portalUser = PortalUser::factory()->create([
            'company_id' => $this->company->id,
            'email' => 'portal@test.com',
            'password' => Hash::make('password'),
            'is_active' => true,
            'role' => PortalUser::ROLE_ADMIN,
        ]);
    }

    public function test_portal_user_can_access_login_page()
    {
        $response = $this->get('/business/login');
        
        $response->assertStatus(200);
        $response->assertSee('Login');
    }

    public function test_portal_user_can_login_with_valid_credentials()
    {
        $response = $this->post('/business/login', [
            'email' => 'portal@test.com',
            'password' => 'password',
        ]);

        $response->assertRedirect('/business');
        $this->assertAuthenticated('portal');
        $this->assertEquals($this->portalUser->id, auth()->guard('portal')->id());
    }

    public function test_portal_user_cannot_login_with_invalid_credentials()
    {
        $response = $this->post('/business/login', [
            'email' => 'portal@test.com',
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors();
        $this->assertGuest('portal');
    }

    public function test_inactive_portal_user_cannot_login()
    {
        $this->portalUser->update(['is_active' => false]);

        $response = $this->post('/business/login', [
            'email' => 'portal@test.com',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors();
        $this->assertGuest('portal');
    }

    public function test_portal_session_persists_after_login()
    {
        // Login
        $this->post('/business/login', [
            'email' => 'portal@test.com',
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
            'email' => 'portal@test.com',
            'password' => 'password',
        ]);

        // Check portal is authenticated
        $this->assertAuthenticated('portal');
        $this->assertGuest('web'); // Admin guard should be guest

        // Now login as admin
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
            'email' => 'portal@test.com',
            'password' => 'password',
        ]);

        // Company context should be set
        $this->assertEquals($this->company->id, app('current_company_id'));
        $this->assertEquals('portal_auth', app('company_context_source'));
    }

    public function test_portal_api_authentication_works()
    {
        $this->actingAs($this->portalUser, 'portal');

        $response = $this->getJson('/business/api/user');
        
        $response->assertStatus(200);
        $response->assertJson([
            'id' => $this->portalUser->id,
            'email' => $this->portalUser->email,
        ]);
    }

    public function test_portal_api_rejects_unauthenticated_requests()
    {
        $response = $this->getJson('/business/api/user');
        
        $response->assertStatus(401);
        $response->assertJson(['message' => 'Unauthenticated.']);
    }

    public function test_portal_user_permissions_are_enforced()
    {
        // Create staff user with limited permissions
        $staffUser = PortalUser::factory()->create([
            'company_id' => $this->company->id,
            'email' => 'staff@test.com',
            'password' => Hash::make('password'),
            'is_active' => true,
            'role' => PortalUser::ROLE_STAFF,
        ]);

        $this->actingAs($staffUser, 'portal');

        // Check permissions
        $this->assertFalse($staffUser->hasPermission('billing.manage'));
        $this->assertTrue($staffUser->hasPermission('calls.view_own'));
    }

    public function test_portal_session_uses_different_cookie_name()
    {
        $this->post('/business/login', [
            'email' => 'portal@test.com',
            'password' => 'password',
        ]);

        // Portal should use its own session cookie
        $cookies = $this->response->headers->getCookies();
        $hasCookie = false;
        
        foreach ($cookies as $cookie) {
            if (str_contains($cookie->getName(), 'askproai_session')) {
                $hasCookie = true;
                break;
            }
        }
        
        $this->assertTrue($hasCookie);
    }

    public function test_portal_2fa_requirement_for_admin_role()
    {
        $adminUser = PortalUser::factory()->create([
            'company_id' => $this->company->id,
            'email' => 'admin2@test.com',
            'password' => Hash::make('password'),
            'is_active' => true,
            'role' => PortalUser::ROLE_ADMIN,
        ]);

        $this->assertTrue($adminUser->requires2FA());
    }

    public function test_portal_login_records_last_login_info()
    {
        $this->assertNull($this->portalUser->last_login_at);

        $this->post('/business/login', [
            'email' => 'portal@test.com',
            'password' => 'password',
        ]);

        $this->portalUser->refresh();
        
        $this->assertNotNull($this->portalUser->last_login_at);
        $this->assertNotNull($this->portalUser->last_login_ip);
    }
}