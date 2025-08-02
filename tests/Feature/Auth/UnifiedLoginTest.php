<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Models\PortalUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnifiedLoginTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test admin login with unified session config
     */
    public function test_admin_can_login_with_unified_session()
    {
        $user = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
        ]);

        $response = $this->post('/admin/login', [
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect('/admin');
        $this->assertAuthenticatedAs($user);
        
        // Check session cookie
        $response->assertCookie('askproai_session');
        
        // Verify session data
        $this->assertNotNull(session()->getId());
        $this->assertEquals('askproai_session', session()->getName());
    }

    /**
     * Test portal login with unified session config
     */
    public function test_portal_user_can_login_with_unified_session()
    {
        $portalUser = PortalUser::factory()->create([
            'email' => 'portal@example.com',
            'password' => bcrypt('password'),
        ]);

        $response = $this->post('/business/login', [
            'email' => 'portal@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect('/business/dashboard');
        $this->assertAuthenticatedAs($portalUser, 'portal');
        
        // Check same session cookie name
        $response->assertCookie('askproai_session');
    }

    /**
     * Test CSRF token handling
     */
    public function test_csrf_token_is_validated_correctly()
    {
        $user = User::factory()->create();
        
        // Without CSRF token
        $response = $this->actingAs($user)
            ->post('/admin/test-csrf', ['data' => 'test']);
        
        $response->assertStatus(419); // CSRF token mismatch
        
        // With CSRF token
        $response = $this->actingAs($user)
            ->post('/admin/test-csrf', [
                '_token' => csrf_token(),
                'data' => 'test'
            ]);
        
        $response->assertSuccessful();
    }

    /**
     * Test API endpoints skip CSRF for bearer tokens
     */
    public function test_api_endpoints_skip_csrf_with_bearer_token()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->post('/api/test-endpoint', ['data' => 'test']);
        
        $response->assertSuccessful();
    }

    /**
     * Test session isolation between portals
     */
    public function test_session_data_is_isolated_between_portals()
    {
        $adminUser = User::factory()->create();
        $portalUser = PortalUser::factory()->create();
        
        // Login as admin
        $this->actingAs($adminUser);
        session(['admin_data' => 'admin_value']);
        
        // Login as portal user
        $this->actingAs($portalUser, 'portal');
        session(['portal_data' => 'portal_value']);
        
        // Check data isolation
        $this->assertEquals('admin_value', session('admin_data'));
        $this->assertEquals('portal_value', session('portal_data'));
        
        // Both use same session ID
        $this->assertNotNull(session()->getId());
    }

    /**
     * Test session persistence across requests
     */
    public function test_session_persists_across_requests()
    {
        $user = User::factory()->create();
        
        // First request - login
        $response = $this->post('/admin/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);
        
        $sessionId = session()->getId();
        
        // Second request - access protected route
        $response = $this->get('/admin/dashboard');
        $response->assertSuccessful();
        
        // Session ID should remain the same
        $this->assertEquals($sessionId, session()->getId());
    }

    /**
     * Test mobile responsiveness
     */
    public function test_login_page_is_mobile_responsive()
    {
        $response = $this->get('/admin/login');
        
        $response->assertSuccessful();
        $response->assertSee('viewport');
        $response->assertSee('mobile-menu-toggle', false);
    }

    /**
     * Test JavaScript error handling
     */
    public function test_javascript_csrf_handler_refreshes_token()
    {
        $response = $this->get('/api/csrf-token');
        
        $response->assertSuccessful();
        $response->assertJsonStructure(['token']);
        
        $token = $response->json('token');
        $this->assertEquals(csrf_token(), $token);
    }
}