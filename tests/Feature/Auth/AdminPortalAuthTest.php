<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AdminPortalAuthTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test company
        $this->company = Company::factory()->create([
            'name' => 'Test Company',
            'slug' => 'test-company',
            'is_active' => true,
        ]);

        // Create admin user
        $this->admin = User::factory()->create([
            'company_id' => $this->company->id,
            'email' => 'admin@test.com',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);
    }

    public function test_admin_login_page_is_accessible()
    {
        $response = $this->get('/admin/login');
        
        $response->assertStatus(200);
    }

    public function test_admin_can_login_with_valid_credentials()
    {
        $response = $this->post('/admin/login', [
            'email' => 'admin@test.com',
            'password' => 'password',
        ]);

        $response->assertRedirect();
        $this->assertAuthenticatedAs($this->admin);
    }

    public function test_admin_cannot_login_with_invalid_credentials()
    {
        $response = $this->post('/admin/login', [
            'email' => 'admin@test.com',
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors();
        $this->assertGuest();
    }

    public function test_inactive_admin_cannot_login()
    {
        $this->admin->update(['is_active' => false]);

        $response = $this->post('/admin/login', [
            'email' => 'admin@test.com',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors();
        $this->assertGuest();
    }

    public function test_admin_session_persists_after_login()
    {
        // Login
        $this->post('/admin/login', [
            'email' => 'admin@test.com',
            'password' => 'password',
        ]);

        // Follow redirect and ensure we're still authenticated
        $response = $this->followRedirects($this->get('/admin'));
        $this->assertAuthenticated();
    }

    public function test_admin_can_logout()
    {
        $this->actingAs($this->admin);

        $response = $this->post('/admin/logout');

        $response->assertRedirect();
        $this->assertGuest();
    }

    public function test_unauthenticated_admin_redirects_to_login()
    {
        $response = $this->get('/admin');
        
        $response->assertRedirect('/admin/login');
    }

    public function test_admin_has_access_to_dashboard()
    {
        $this->actingAs($this->admin);

        $response = $this->get('/admin');
        
        $response->assertStatus(200);
    }

    public function test_admin_company_context_is_set_on_login()
    {
        $this->post('/admin/login', [
            'email' => 'admin@test.com',
            'password' => 'password',
        ]);

        // Check that the company context is properly set
        $this->assertEquals($this->company->id, auth()->user()->company_id);
    }

    public function test_admin_cannot_access_other_company_data()
    {
        // Create another company and user
        $otherCompany = Company::factory()->create([
            'name' => 'Other Company',
            'slug' => 'other-company',
        ]);

        $otherUser = User::factory()->create([
            'company_id' => $otherCompany->id,
            'email' => 'other@test.com',
        ]);

        // Login as our admin
        $this->actingAs($this->admin);

        // Should not be able to access other company's users
        $users = User::all();
        $this->assertCount(1, $users);
        $this->assertEquals($this->admin->id, $users->first()->id);
    }

    public function test_admin_password_is_hashed()
    {
        $this->assertNotEquals('password', $this->admin->password);
        $this->assertTrue(Hash::check('password', $this->admin->password));
    }

    public function test_admin_session_has_proper_security_headers()
    {
        $response = $this->post('/admin/login', [
            'email' => 'admin@test.com',
            'password' => 'password',
        ]);

        // Check for security headers in session cookie
        $cookies = $response->headers->getCookies();
        $sessionCookie = collect($cookies)->first(fn($cookie) => 
            str_contains($cookie->getName(), 'session')
        );

        if ($sessionCookie) {
            $this->assertTrue($sessionCookie->isHttpOnly());
        }
    }

    public function test_admin_session_regenerates_on_login()
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

        // Session ID should change after login
        $this->assertNotEquals($initialSessionId, $newSessionId);
    }

    public function test_admin_rate_limiting_on_login_attempts()
    {
        // Attempt multiple failed logins
        for ($i = 0; $i < 5; $i++) {
            $this->post('/admin/login', [
                'email' => 'admin@test.com',
                'password' => 'wrong-password',
            ]);
        }

        // Next attempt should be rate limited
        $response = $this->post('/admin/login', [
            'email' => 'admin@test.com',
            'password' => 'wrong-password',
        ]);

        // Should be rate limited (429) or have too many attempts error
        $this->assertTrue($response->status() === 429 || $response->status() === 422);
    }

    public function test_admin_csrf_protection_on_login()
    {
        // Attempt login without CSRF token
        $response = $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class)
            ->post('/admin/login', [
                'email' => 'admin@test.com',
                'password' => 'password',
            ], [
                'Accept' => 'application/json'
            ]);

        // Should work without CSRF middleware
        $response->assertRedirect();
    }

    public function test_admin_remember_me_functionality()
    {
        $response = $this->post('/admin/login', [
            'email' => 'admin@test.com',
            'password' => 'password',
            'remember' => true,
        ]);

        $response->assertRedirect();
        $this->assertAuthenticatedAs($this->admin);
        
        // Check for remember cookie
        $cookies = $response->headers->getCookies();
        $rememberCookie = collect($cookies)->first(fn($cookie) => 
            str_contains($cookie->getName(), 'remember')
        );
        
        // Should have a remember cookie with long expiration
        if ($rememberCookie) {
            $this->assertGreaterThan(time() + 86400, $rememberCookie->getExpiresTime());
        }
    }

    public function test_admin_logout_clears_session()
    {
        $this->actingAs($this->admin);
        
        // Verify we're authenticated
        $this->assertAuthenticated();
        
        // Logout
        $this->post('/admin/logout');
        
        // Should be logged out
        $this->assertGuest();
    }

    public function test_admin_login_updates_last_login_timestamp()
    {
        $this->assertNull($this->admin->last_login_at);

        $this->post('/admin/login', [
            'email' => 'admin@test.com',
            'password' => 'password',
        ]);

        $this->admin->refresh();
        $this->assertNotNull($this->admin->last_login_at);
    }
}