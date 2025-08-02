<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminAuthTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test company
        $this->company = Company::factory()->create();

        // Create admin user
        $this->admin = User::factory()->create([
            'company_id' => $this->company->id,
            'email' => 'admin@test.com',
            'password' => Hash::make('password'),
        ]);
    }

    public function test_admin_can_access_login_page()
    {
        $response = $this->get('/admin/login');
        
        $response->assertStatus(200);
        $response->assertSee('Login');
    }

    public function test_admin_can_login_with_valid_credentials()
    {
        $response = $this->post('/admin/login', [
            'email' => 'admin@test.com',
            'password' => 'password',
        ]);

        $response->assertRedirect('/admin');
        $this->assertAuthenticatedAs($this->admin);
    }

    public function test_admin_cannot_login_with_invalid_credentials()
    {
        $response = $this->post('/admin/login', [
            'email' => 'admin@test.com',
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_admin_session_persists_after_login()
    {
        // Login
        $this->post('/admin/login', [
            'email' => 'admin@test.com',
            'password' => 'password',
        ]);

        // Session should persist on next request
        $response = $this->get('/admin');
        $response->assertStatus(200);
        $this->assertAuthenticatedAs($this->admin);
    }

    public function test_admin_can_logout()
    {
        $this->actingAs($this->admin);

        $response = $this->post('/admin/logout');

        $response->assertRedirect('/admin/login');
        $this->assertGuest();
    }

    public function test_unauthenticated_admin_redirects_to_login()
    {
        $response = $this->get('/admin');
        
        $response->assertRedirect('/admin/login');
    }

    public function test_admin_session_uses_redis_driver()
    {
        $this->assertEquals('redis', config('session.driver'));
    }

    public function test_admin_cookies_are_secure_in_production()
    {
        if (app()->environment('production')) {
            $this->assertTrue(config('session.secure'));
            $this->assertEquals('strict', config('session.same_site'));
        }
    }

    public function test_password_hash_not_stored_in_session()
    {
        $this->post('/admin/login', [
            'email' => 'admin@test.com',
            'password' => 'password',
        ]);

        $session = session()->all();
        
        // Ensure no password hashes in session
        foreach ($session as $key => $value) {
            $this->assertStringNotContainsString('password_hash', $key);
        }
    }

    public function test_concurrent_sessions_do_not_conflict()
    {
        // Create another admin user
        $admin2 = User::factory()->create([
            'company_id' => $this->company->id,
            'email' => 'admin2@test.com',
            'password' => Hash::make('password'),
        ]);

        // Login as first admin
        $this->post('/admin/login', [
            'email' => 'admin@test.com',
            'password' => 'password',
        ]);

        $firstSessionId = session()->getId();
        
        // Start new session
        $this->refreshApplication();

        // Login as second admin
        $this->post('/admin/login', [
            'email' => 'admin2@test.com',
            'password' => 'password',
        ]);

        $secondSessionId = session()->getId();

        // Sessions should be different
        $this->assertNotEquals($firstSessionId, $secondSessionId);
    }

    public function test_csrf_protection_is_active()
    {
        $response = $this->post('/admin/login', [
            'email' => 'admin@test.com',
            'password' => 'password',
        ], ['Accept' => 'application/json']);

        $response->assertStatus(419); // CSRF token mismatch
    }

    public function test_session_fixation_prevented_on_login()
    {
        // Get session ID before login
        $this->get('/admin/login');
        $sessionIdBeforeLogin = session()->getId();

        // Login
        $this->post('/admin/login', [
            'email' => 'admin@test.com',
            'password' => 'password',
        ]);

        $sessionIdAfterLogin = session()->getId();

        // Session ID should change after login (regenerated)
        $this->assertNotEquals($sessionIdBeforeLogin, $sessionIdAfterLogin);
    }
}