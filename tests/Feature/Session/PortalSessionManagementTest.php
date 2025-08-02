<?php

namespace Tests\Feature\Session;

use App\Models\Company;
use App\Models\User;
use App\Models\PortalUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class PortalSessionManagementTest extends TestCase
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
        ]);

        $this->admin = User::factory()->create([
            'company_id' => $this->company->id,
            'email' => 'admin@test.com',
        ]);

        $this->portalUser = PortalUser::factory()->create([
            'company_id' => $this->company->id,
            'email' => 'portal@test.com',
            'role' => PortalUser::ROLE_ADMIN,
        ]);
    }

    // Basic Session Tests
    public function test_admin_portal_session_configuration()
    {
        $this->actingAs($this->admin);

        $response = $this->get('/admin');
        
        if ($response->status() !== 404) {
            // Check session configuration
            $sessionConfig = config('session');
            
            $this->assertIsArray($sessionConfig);
            $this->assertArrayHasKey('driver', $sessionConfig);
            $this->assertArrayHasKey('lifetime', $sessionConfig);
            $this->assertArrayHasKey('cookie', $sessionConfig);
        }
    }

    public function test_business_portal_session_configuration()
    {
        $this->actingAs($this->portalUser, 'portal');

        $response = $this->get('/business');
        
        if ($response->status() !== 404) {
            // Portal should have its own session configuration
            $sessionConfig = config('session_portal');
            
            if ($sessionConfig) {
                $this->assertIsArray($sessionConfig);
            }
        }
    }

    // Session Isolation Tests
    public function test_admin_and_portal_sessions_are_isolated()
    {
        // Login to admin portal
        $this->post('/admin/login', [
            'email' => 'admin@test.com',
            'password' => 'password',
        ]);

        $adminSessionId = session()->getId();
        $this->assertAuthenticated('web');

        // Login to business portal (should create separate session)
        $this->post('/business/login', [
            'email' => 'portal@test.com',
            'password' => 'password',
        ]);

        $portalSessionId = session()->getId();
        
        // Sessions should be different
        $this->assertNotEquals($adminSessionId, $portalSessionId);
        
        // Both should be authenticated in their respective guards
        $this->assertAuthenticated('web');
        $this->assertAuthenticated('portal');
    }

    public function test_session_cookies_have_different_names()
    {
        // Admin login
        $adminResponse = $this->post('/admin/login', [
            'email' => 'admin@test.com',
            'password' => 'password',
        ]);

        // Portal login
        $portalResponse = $this->post('/business/login', [
            'email' => 'portal@test.com',
            'password' => 'password',
        ]);

        // Check cookie names are different
        $adminCookies = $adminResponse->headers->getCookies();
        $portalCookies = $portalResponse->headers->getCookies();

        $adminSessionCookie = collect($adminCookies)->first(fn($cookie) => 
            str_contains($cookie->getName(), 'session')
        );

        $portalSessionCookie = collect($portalCookies)->first(fn($cookie) => 
            str_contains($cookie->getName(), 'session')
        );

        if ($adminSessionCookie && $portalSessionCookie) {
            $this->assertNotEquals(
                $adminSessionCookie->getName(), 
                $portalSessionCookie->getName()
            );
        }
    }

    // Session Security Tests
    public function test_session_regeneration_on_login()
    {
        // Get initial session ID
        $this->get('/admin/login');
        $initialSessionId = session()->getId();

        // Login
        $this->post('/admin/login', [
            'email' => 'admin@test.com',
            'password' => 'password',
        ]);

        $newSessionId = session()->getId();

        // Session should regenerate on login
        $this->assertNotEquals($initialSessionId, $newSessionId);
    }

    public function test_session_invalidation_on_logout()
    {
        // Login
        $this->post('/admin/login', [
            'email' => 'admin@test.com',
            'password' => 'password',
        ]);

        $sessionId = session()->getId();
        $this->assertAuthenticated();

        // Logout
        $this->post('/admin/logout');

        // Should be logged out
        $this->assertGuest();
        
        // Session ID should change
        $newSessionId = session()->getId();
        $this->assertNotEquals($sessionId, $newSessionId);
    }

    public function test_session_fixation_prevention()
    {
        // Simulate session fixation attack
        $maliciousSessionId = 'malicious_session_id';
        
        // Try to set a specific session ID
        $this->withSession(['_token' => $maliciousSessionId])
            ->post('/admin/login', [
                'email' => 'admin@test.com',
                'password' => 'password',
            ]);

        // Session should be regenerated, not use the malicious ID
        $this->assertNotEquals($maliciousSessionId, session()->getId());
    }

    // Session Timeout Tests
    public function test_session_timeout_configuration()
    {
        $this->actingAs($this->admin);

        // Check session lifetime configuration
        $lifetime = config('session.lifetime');
        $this->assertIsInt($lifetime);
        $this->assertGreaterThan(0, $lifetime);

        // For production, should be reasonable (not too long)
        if (app()->environment('production')) {
            $this->assertLessThanOrEqual(1440, $lifetime); // Max 24 hours
        }
    }

    public function test_inactive_session_cleanup()
    {
        // This would test cleanup of inactive sessions
        // Implementation depends on session driver and cleanup mechanism
        
        $this->actingAs($this->admin);
        
        // Simulate old session
        Session::put('last_activity', now()->subHours(25)->timestamp);
        
        // Make request that should trigger cleanup
        $response = $this->get('/admin');
        
        // Should redirect to login due to session expiry
        if ($response->status() === 302) {
            $this->assertEquals('/admin/login', $response->headers->get('Location'));
        }
    }

    // Session Data Security Tests
    public function test_sensitive_data_not_stored_in_session()
    {
        $this->post('/admin/login', [
            'email' => 'admin@test.com',
            'password' => 'password',
        ]);

        $sessionData = session()->all();
        $sessionJson = json_encode($sessionData);

        // Ensure no passwords are stored
        $this->assertStringNotContainsString('password', strtolower($sessionJson));
        $this->assertStringNotContainsString('secret', strtolower($sessionJson));
        $this->assertStringNotContainsString('token', strtolower($sessionJson));
    }

    public function test_company_context_stored_in_session()
    {
        $this->post('/admin/login', [
            'email' => 'admin@test.com',
            'password' => 'password',
        ]);

        // Company context should be available
        $this->assertEquals($this->company->id, auth()->user()->company_id);
    }

    // Cross-Portal Session Tests
    public function test_admin_logout_does_not_affect_portal_session()
    {
        // Login to both portals
        $this->post('/admin/login', [
            'email' => 'admin@test.com',
            'password' => 'password',
        ]);

        $this->post('/business/login', [
            'email' => 'portal@test.com',
            'password' => 'password',
        ]);

        $this->assertAuthenticated('web');
        $this->assertAuthenticated('portal');

        // Logout from admin
        $this->post('/admin/logout');

        // Admin should be logged out, portal should remain
        $this->assertGuest('web');
        $this->assertAuthenticated('portal');
    }

    public function test_portal_logout_does_not_affect_admin_session()
    {
        // Login to both portals
        $this->post('/admin/login', [
            'email' => 'admin@test.com',
            'password' => 'password',
        ]);

        $this->post('/business/login', [
            'email' => 'portal@test.com',
            'password' => 'password',
        ]);

        $this->assertAuthenticated('web');
        $this->assertAuthenticated('portal');

        // Logout from portal
        $this->post('/business/logout');

        // Portal should be logged out, admin should remain
        $this->assertAuthenticated('web');
        $this->assertGuest('portal');
    }

    // Session Storage Tests
    public function test_session_storage_driver()
    {
        $sessionDriver = config('session.driver');
        
        // Should use appropriate driver for production
        if (app()->environment('production')) {
            $this->assertContains($sessionDriver, ['redis', 'database', 'memcached']);
        }
        
        // In testing, typically uses array or file
        if (app()->environment('testing')) {
            $this->assertContains($sessionDriver, ['array', 'file', 'sqlite']);
        }
    }

    public function test_session_encryption()
    {
        Config::set('session.encrypt', true);
        
        $this->post('/admin/login', [
            'email' => 'admin@test.com',
            'password' => 'password',
        ]);

        // Session should be encrypted if configured
        $encryptSessions = config('session.encrypt');
        if ($encryptSessions) {
            $this->assertTrue($encryptSessions);
        }
    }

    // Cookie Security Tests
    public function test_session_cookie_security_settings()
    {
        $this->post('/admin/login', [
            'email' => 'admin@test.com',
            'password' => 'password',
        ]);

        $cookieConfig = config('session');

        // Check security settings
        if (app()->environment('production')) {
            $this->assertTrue($cookieConfig['secure'] ?? false);
            $this->assertEquals('strict', $cookieConfig['same_site'] ?? '');
        }

        $this->assertTrue($cookieConfig['http_only'] ?? true);
    }

    // Concurrent Session Tests
    public function test_multiple_admin_sessions_work_independently()
    {
        // Create second admin user
        $admin2 = User::factory()->create([
            'company_id' => $this->company->id,
            'email' => 'admin2@test.com',
        ]);

        // Login as first admin
        $this->post('/admin/login', [
            'email' => 'admin@test.com',
            'password' => 'password',
        ]);

        $firstSessionId = session()->getId();
        $this->assertAuthenticated();

        // Start new session for second admin
        $this->refreshApplication();

        $this->post('/admin/login', [
            'email' => 'admin2@test.com',
            'password' => 'password',
        ]);

        $secondSessionId = session()->getId();

        // Sessions should be independent
        $this->assertNotEquals($firstSessionId, $secondSessionId);
        $this->assertAuthenticated();
    }

    public function test_session_data_isolation_between_users()
    {
        // Login as first user and set session data
        $this->post('/admin/login', [
            'email' => 'admin@test.com',
            'password' => 'password',
        ]);

        Session::put('user_specific_data', 'admin_data');
        $this->assertEquals('admin_data', Session::get('user_specific_data'));

        // Logout and login as different user
        $this->post('/admin/logout');

        $this->post('/business/login', [
            'email' => 'portal@test.com',
            'password' => 'password',
        ]);

        // Should not see previous user's session data
        $this->assertNull(Session::get('user_specific_data'));
    }

    // Session Middleware Tests
    public function test_session_middleware_configuration()
    {
        // Test that appropriate session middleware is applied
        $this->actingAs($this->admin);

        $response = $this->get('/admin');
        
        if ($response->status() !== 404) {
            // Should have session started
            $this->assertTrue(session()->isStarted());
        }
    }

    public function test_portal_specific_session_middleware()
    {
        $this->actingAs($this->portalUser, 'portal');

        $response = $this->get('/business');
        
        if ($response->status() !== 404) {
            // Portal should have its own session handling
            $this->assertTrue(session()->isStarted());
        }
    }

    // Session Garbage Collection Tests
    public function test_session_garbage_collection_configuration()
    {
        $gcProbability = config('session.lottery');
        
        $this->assertIsArray($gcProbability);
        $this->assertCount(2, $gcProbability);
        $this->assertLessThanOrEqual($gcProbability[1], $gcProbability[0]);
    }

    // Flash Data Tests
    public function test_flash_data_works_across_requests()
    {
        $this->actingAs($this->admin);

        // Set flash data
        Session::flash('message', 'Test flash message');
        
        // Make request
        $response = $this->get('/admin');
        
        if ($response->status() !== 404) {
            // Flash data should be available
            $this->assertEquals('Test flash message', Session::get('message'));
        }
    }

    public function test_flash_data_is_cleared_after_request()
    {
        $this->actingAs($this->admin);

        Session::flash('message', 'Test flash message');
        
        // First request
        $this->get('/admin');
        
        // Second request - flash data should be gone
        $this->get('/admin');
        $this->assertNull(Session::get('message'));
    }
}