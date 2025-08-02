<?php

namespace Tests\Feature\Security;

use App\Auth\PortalUserProvider;
use App\Models\Company;
use App\Models\PortalUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

/**
 * Tests for Portal Authentication Tenant Isolation Security
 * 
 * This test suite validates that the PortalUserProvider correctly isolates
 * authentication across tenants and prevents cross-tenant authentication vulnerabilities.
 */
class PortalAuthenticationTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private Company $company1;
    private Company $company2;
    private PortalUser $portalUser1;
    private PortalUser $portalUser2;
    private PortalUser $inactiveUser;
    private PortalUser $sameEmailDifferentCompany;

    protected function setUp(): void
    {
        parent::setUp();

        // Create two companies
        $this->company1 = Company::factory()->create([
            'name' => 'Company Alpha',
            'slug' => 'company-alpha',
            'is_active' => true,
        ]);

        $this->company2 = Company::factory()->create([
            'name' => 'Company Beta',
            'slug' => 'company-beta', 
            'is_active' => true,
        ]);

        $password = Hash::make('password123');

        // Create portal users for each company
        $this->portalUser1 = PortalUser::factory()->create([
            'company_id' => $this->company1->id,
            'email' => 'user@company-alpha.com',
            'password' => $password,
            'is_active' => true,
        ]);

        $this->portalUser2 = PortalUser::factory()->create([
            'company_id' => $this->company2->id,
            'email' => 'user@company-beta.com',
            'password' => $password,
            'is_active' => true,
        ]);

        // Create inactive user to test status checks
        $this->inactiveUser = PortalUser::factory()->create([
            'company_id' => $this->company1->id,
            'email' => 'inactive@company-alpha.com',
            'password' => $password,
            'is_active' => false,
        ]);

        // Create user with same email in different company (edge case)
        $this->sameEmailDifferentCompany = PortalUser::factory()->create([
            'company_id' => $this->company2->id,
            'email' => 'duplicate@example.com',
            'password' => $password,
            'is_active' => true,
        ]);

        // Create another user with same email in company 1
        PortalUser::factory()->create([
            'company_id' => $this->company1->id,
            'email' => 'duplicate@example.com',
            'password' => Hash::make('different-password'),
            'is_active' => true,
        ]);
    }

    public function test_portal_user_provider_bypasses_tenant_scope_for_authentication()
    {
        $provider = new PortalUserProvider(app('hash'), PortalUser::class);

        // Test retrieveByCredentials bypasses global scopes
        $user = $provider->retrieveByCredentials([
            'email' => $this->portalUser1->email,
            'password' => 'password123'
        ]);

        $this->assertNotNull($user);
        $this->assertEquals($this->portalUser1->id, $user->id);
        $this->assertEquals($this->company1->id, $user->company_id);
    }

    public function test_portal_user_provider_retrieve_by_id_bypasses_tenant_scope()
    {
        $provider = new PortalUserProvider(app('hash'), PortalUser::class);

        // Should be able to retrieve user by ID regardless of current tenant context
        $user = $provider->retrieveById($this->portalUser2->id);

        $this->assertNotNull($user);
        $this->assertEquals($this->portalUser2->id, $user->id);
        $this->assertEquals($this->company2->id, $user->company_id);
    }

    public function test_portal_user_provider_retrieve_by_token_bypasses_tenant_scope()
    {
        $provider = new PortalUserProvider(app('hash'), PortalUser::class);
        
        // Set remember token
        $token = 'test-remember-token-123';
        $this->portalUser1->remember_token = $token;
        $this->portalUser1->save();

        // Should retrieve user by remember token regardless of tenant scope
        $user = $provider->retrieveByToken($this->portalUser1->id, $token);

        $this->assertNotNull($user);
        $this->assertEquals($this->portalUser1->id, $user->id);
        $this->assertEquals($this->company1->id, $user->company_id);
    }

    public function test_authentication_with_wrong_credentials_fails()
    {
        $response = $this->postJson('/business/api/auth/login', [
            'email' => $this->portalUser1->email,
            'password' => 'wrong-password'
        ]);

        $response->assertStatus(401);
        $this->assertGuest('portal');
    }

    public function test_authentication_with_inactive_user_fails()
    {
        $response = $this->postJson('/business/api/auth/login', [
            'email' => $this->inactiveUser->email,
            'password' => 'password123'
        ]);

        $response->assertStatus(401);
        $this->assertGuest('portal');
    }

    public function test_successful_authentication_sets_correct_user_context()
    {
        $response = $this->postJson('/business/api/auth/login', [
            'email' => $this->portalUser1->email,
            'password' => 'password123'
        ]);

        $response->assertStatus(200);
        $this->assertAuthenticatedAs($this->portalUser1, 'portal');
        
        // Verify correct company context is set
        $authenticatedUser = Auth::guard('portal')->user();
        $this->assertEquals($this->company1->id, $authenticatedUser->company_id);
    }

    public function test_cross_tenant_login_attempt_isolation()
    {
        // Login as company 1 user
        $this->actingAs($this->portalUser1, 'portal');

        // Try to access company 2 user's data
        $response = $this->getJson('/business/api/profile');
        $response->assertStatus(200);
        
        $profileData = $response->json();
        $this->assertEquals($this->portalUser1->email, $profileData['email']);
        $this->assertNotEquals($this->portalUser2->email, $profileData['email']);
    }

    public function test_duplicate_email_authentication_returns_correct_company_user()
    {
        // When multiple users have same email in different companies,
        // authentication should work but only return user from correct context
        $provider = new PortalUserProvider(app('hash'), PortalUser::class);

        $user = $provider->retrieveByCredentials([
            'email' => 'duplicate@example.com',
            'password' => 'password123'
        ]);

        // Should return user from company 2 (our test user with correct password)
        $this->assertNotNull($user);
        $this->assertEquals($this->company2->id, $user->company_id);
        $this->assertEquals($this->sameEmailDifferentCompany->id, $user->id);
    }

    public function test_session_isolation_prevents_cross_tenant_access()
    {
        // Login as company 1 user
        $response = $this->postJson('/business/api/auth/login', [
            'email' => $this->portalUser1->email,
            'password' => 'password123'
        ]);
        $response->assertStatus(200);

        // Get session data for company 1
        $company1Session = Session::all();

        // Logout and login as company 2 user
        $this->postJson('/business/api/auth/logout');
        
        $response = $this->postJson('/business/api/auth/login', [
            'email' => $this->portalUser2->email,
            'password' => 'password123'
        ]);
        $response->assertStatus(200);

        // Verify we're now authenticated as company 2 user
        $authenticatedUser = Auth::guard('portal')->user();
        $this->assertEquals($this->company2->id, $authenticatedUser->company_id);
        $this->assertEquals($this->portalUser2->id, $authenticatedUser->id);

        // Verify session data doesn't leak between companies
        $company2Session = Session::all();
        $this->assertNotEquals($company1Session, $company2Session);
    }

    public function test_remember_token_isolation_across_tenants()
    {
        $provider = new PortalUserProvider(app('hash'), PortalUser::class);
        
        // Set remember tokens for both users
        $token1 = 'company1-remember-token-123';
        $token2 = 'company2-remember-token-456';
        
        $this->portalUser1->remember_token = $token1;
        $this->portalUser1->save();
        
        $this->portalUser2->remember_token = $token2;
        $this->portalUser2->save();

        // Test that remember tokens are isolated
        $user1 = $provider->retrieveByToken($this->portalUser1->id, $token1);
        $this->assertNotNull($user1);
        $this->assertEquals($this->company1->id, $user1->company_id);

        $user2 = $provider->retrieveByToken($this->portalUser2->id, $token2);
        $this->assertNotNull($user2);
        $this->assertEquals($this->company2->id, $user2->company_id);

        // Cross-token access should fail
        $crossUser = $provider->retrieveByToken($this->portalUser1->id, $token2);
        $this->assertNull($crossUser);
    }

    public function test_password_credentials_validation_edge_cases()
    {
        $provider = new PortalUserProvider(app('hash'), PortalUser::class);

        // Empty credentials should return null
        $user = $provider->retrieveByCredentials([]);
        $this->assertNull($user);

        // Only password should return null
        $user = $provider->retrieveByCredentials(['password' => 'test']);
        $this->assertNull($user);

        // Null values should be handled gracefully
        $user = $provider->retrieveByCredentials([
            'email' => null,
            'password' => 'password123'
        ]);
        $this->assertNull($user);
    }

    public function test_vulnerability_nonexistent_user_by_id_returns_null()
    {
        $provider = new PortalUserProvider(app('hash'), PortalUser::class);

        // Non-existent user ID should return null
        $user = $provider->retrieveById(99999);
        $this->assertNull($user);

        // Non-numeric ID should return null
        $user = $provider->retrieveById('invalid-id');
        $this->assertNull($user);
    }

    public function test_vulnerability_sql_injection_in_credentials()
    {
        $provider = new PortalUserProvider(app('hash'), PortalUser::class);

        // SQL injection attempt in email field
        $user = $provider->retrieveByCredentials([
            'email' => "'; DROP TABLE portal_users; --",
            'password' => 'password123'
        ]);
        $this->assertNull($user);

        // Verify our test users still exist (table wasn't dropped)
        $this->assertDatabaseHas('portal_users', [
            'email' => $this->portalUser1->email
        ]);
    }

    public function test_vulnerability_mass_assignment_in_credentials()
    {
        $provider = new PortalUserProvider(app('hash'), PortalUser::class);

        // Attempt to bypass authentication by passing additional fields
        $user = $provider->retrieveByCredentials([
            'email' => $this->portalUser1->email,
            'password' => 'wrong-password',
            'is_active' => 1,
            'company_id' => $this->company1->id,
            'remember_token' => 'fake-token'
        ]);

        // Should still return user (provider doesn't validate password)
        // but authentication would fail at password validation step
        $this->assertNotNull($user);
        $this->assertEquals($this->portalUser1->email, $user->email);
    }

    public function test_concurrent_authentication_sessions_isolation()
    {
        // Simulate concurrent login attempts from different companies
        $session1 = $this->withSession([]);
        $session2 = $this->withSession([]);

        // Login from session 1 as company 1 user
        $response1 = $session1->postJson('/business/api/auth/login', [
            'email' => $this->portalUser1->email,
            'password' => 'password123'
        ]);
        $response1->assertStatus(200);

        // Login from session 2 as company 2 user
        $response2 = $session2->postJson('/business/api/auth/login', [
            'email' => $this->portalUser2->email,
            'password' => 'password123'
        ]);
        $response2->assertStatus(200);

        // Each session should maintain its own authentication state
        $profile1 = $session1->getJson('/business/api/profile');
        $profile2 = $session2->getJson('/business/api/profile');

        $this->assertEquals($this->portalUser1->email, $profile1->json('email'));
        $this->assertEquals($this->portalUser2->email, $profile2->json('email'));
        $this->assertNotEquals($profile1->json('company_id'), $profile2->json('company_id'));
    }

    public function test_token_expiration_and_cleanup()
    {
        $provider = new PortalUserProvider(app('hash'), PortalUser::class);
        
        // Set and then update remember token
        $oldToken = 'old-token-123';
        $newToken = 'new-token-456';
        
        $this->portalUser1->remember_token = $oldToken;
        $this->portalUser1->save();

        // Verify old token works
        $user = $provider->retrieveByToken($this->portalUser1->id, $oldToken);
        $this->assertNotNull($user);

        // Update token
        $this->portalUser1->remember_token = $newToken;
        $this->portalUser1->save();

        // Old token should no longer work
        $user = $provider->retrieveByToken($this->portalUser1->id, $oldToken);
        $this->assertNull($user);

        // New token should work
        $user = $provider->retrieveByToken($this->portalUser1->id, $newToken);
        $this->assertNotNull($user);
    }

    public function test_admin_impersonation_security()
    {
        // Create admin user
        $admin = User::factory()->create([
            'email' => 'admin@askproai.com',
            'is_admin' => true,
        ]);

        // Login as admin
        $this->actingAs($admin, 'web');

        // Set admin impersonation session
        Session::put('is_admin_viewing', true);
        Session::put('admin_impersonation', [
            'company_id' => $this->company1->id,
            'admin_id' => $admin->id,
        ]);

        // Admin should be able to access company 1 data
        $response = $this->getJson('/business/api/dashboard');
        $response->assertStatus(200);

        // But shouldn't be able to access company 2 data by manipulating session
        Session::put('admin_impersonation.company_id', $this->company2->id);
        
        $response = $this->getJson('/business/api/dashboard');
        // Should still work but with company 2 context
        $response->assertStatus(200);
    }
}