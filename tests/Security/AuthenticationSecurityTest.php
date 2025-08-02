<?php

namespace Tests\Security;

use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

class AuthenticationSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Clear rate limiter
        RateLimiter::clear('login');
    }

    /**
     * Test brute force protection.
     */
    public function test_brute_force_protection()
    {
        $email = 'test@example.com';
        
        // Attempt login 5 times with wrong password
        for ($i = 0; $i < 5; $i++) {
            $response = $this->postJson('/api/login', [
                'email' => $email,
                'password' => 'wrong-password',
            ]);
            
            if ($i < 4) {
                $response->assertStatus(422);
            }
        }
        
        // 6th attempt should be rate limited
        $response = $this->postJson('/api/login', [
            'email' => $email,
            'password' => 'wrong-password',
        ]);
        
        $response->assertStatus(429)
            ->assertJson([
                'message' => 'Too many login attempts. Please try again later.',
            ]);
    }

    /**
     * Test password complexity requirements.
     */
    public function test_password_complexity_requirements()
    {
        $company = Company::factory()->create();
        
        // Test weak passwords
        $weakPasswords = [
            '12345678',           // Simple numbers
            'password',           // Common word
            'Password',           // No numbers
            'Pass123',            // Too short
            'password123',        // No uppercase
            'PASSWORD123',        // No lowercase
            'Password',           // No numbers
        ];
        
        foreach ($weakPasswords as $password) {
            $response = $this->postJson('/api/register', [
                'name' => 'Test User',
                'email' => 'test' . uniqid() . '@example.com',
                'password' => $password,
                'password_confirmation' => $password,
                'company_id' => $company->id,
            ]);
            
            $response->assertStatus(422)
                ->assertJsonValidationErrors(['password']);
        }
        
        // Test strong password
        $response = $this->postJson('/api/register', [
            'name' => 'Test User',
            'email' => 'strong@example.com',
            'password' => 'StrongP@ssw0rd123!',
            'password_confirmation' => 'StrongP@ssw0rd123!',
            'company_id' => $company->id,
        ]);
        
        $response->assertStatus(201);
    }

    /**
     * Test session security.
     */
    public function test_session_security()
    {
        $user = User::factory()->create();
        
        // Login
        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);
        
        $token = $response->json('data.token');
        
        // Verify session invalidation on logout
        $this->withToken($token)
            ->postJson('/api/logout')
            ->assertSuccessful();
        
        // Try to use the same token
        $this->withToken($token)
            ->getJson('/api/user')
            ->assertUnauthorized();
    }

    /**
     * Test concurrent session handling.
     */
    public function test_concurrent_session_handling()
    {
        $user = User::factory()->create();
        
        // First login
        $response1 = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);
        
        $token1 = $response1->json('data.token');
        
        // Second login (different device/browser)
        $response2 = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
            'device_name' => 'mobile',
        ]);
        
        $token2 = $response2->json('data.token');
        
        // Both tokens should work (unless single session is enforced)
        $this->withToken($token1)
            ->getJson('/api/user')
            ->assertSuccessful();
            
        $this->withToken($token2)
            ->getJson('/api/user')
            ->assertSuccessful();
    }

    /**
     * Test password reset token security.
     */
    public function test_password_reset_token_security()
    {
        $user = User::factory()->create();
        
        // Request password reset
        $response = $this->postJson('/api/password/email', [
            'email' => $user->email,
        ]);
        
        $response->assertSuccessful();
        
        // Simulate expired token (would need to mock time)
        $expiredToken = 'expired-token-123';
        
        $response = $this->postJson('/api/password/reset', [
            'email' => $user->email,
            'token' => $expiredToken,
            'password' => 'NewP@ssw0rd123!',
            'password_confirmation' => 'NewP@ssw0rd123!',
        ]);
        
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test account lockout after failed attempts.
     */
    public function test_account_lockout_after_failed_attempts()
    {
        $user = User::factory()->create([
            'failed_login_attempts' => 0,
            'locked_until' => null,
        ]);
        
        // Simulate multiple failed login attempts
        for ($i = 0; $i < 10; $i++) {
            $response = $this->postJson('/api/login', [
                'email' => $user->email,
                'password' => 'wrong-password',
            ]);
        }
        
        // Account should be locked
        $user->refresh();
        $this->assertNotNull($user->locked_until);
        $this->assertTrue($user->locked_until->isFuture());
        
        // Attempt with correct password should still fail
        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);
        
        $response->assertStatus(423)
            ->assertJson([
                'message' => 'Account is locked due to multiple failed login attempts.',
            ]);
    }

    /**
     * Test secure password storage.
     */
    public function test_secure_password_storage()
    {
        $plainPassword = 'SecureP@ssw0rd123!';
        
        $user = User::factory()->create([
            'password' => Hash::make($plainPassword),
        ]);
        
        // Verify password is hashed
        $this->assertNotEquals($plainPassword, $user->password);
        $this->assertTrue(Hash::check($plainPassword, $user->password));
        
        // Verify bcrypt is used (60 character hash)
        $this->assertEquals(60, strlen($user->password));
        $this->assertStringStartsWith('$2y$', $user->password);
    }

    /**
     * Test authentication token expiration.
     */
    public function test_authentication_token_expiration()
    {
        $user = User::factory()->create();
        
        // Create token with short expiration
        $token = $user->createToken('test-token', ['*'], now()->addMinute())->plainTextToken;
        
        // Token should work immediately
        $this->withToken($token)
            ->getJson('/api/user')
            ->assertSuccessful();
        
        // Simulate time passing (would need to mock time)
        $this->travel(2)->minutes();
        
        // Token should be expired
        $this->withToken($token)
            ->getJson('/api/user')
            ->assertUnauthorized();
    }

    /**
     * Test protection against timing attacks.
     */
    public function test_protection_against_timing_attacks()
    {
        $existingUser = User::factory()->create();
        $nonExistentEmail = 'nonexistent@example.com';
        
        // Measure response time for existing user
        $start1 = microtime(true);
        $this->postJson('/api/login', [
            'email' => $existingUser->email,
            'password' => 'wrong-password',
        ]);
        $time1 = microtime(true) - $start1;
        
        // Measure response time for non-existent user
        $start2 = microtime(true);
        $this->postJson('/api/login', [
            'email' => $nonExistentEmail,
            'password' => 'wrong-password',
        ]);
        $time2 = microtime(true) - $start2;
        
        // Response times should be similar (within 50ms)
        $this->assertLessThan(0.05, abs($time1 - $time2));
    }

    /**
     * Test secure cookie attributes.
     */
    public function test_secure_cookie_attributes()
    {
        $user = User::factory()->create();
        
        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);
        
        $response->assertSuccessful();
        
        // Check session cookie attributes
        $cookies = $response->headers->getCookies();
        
        foreach ($cookies as $cookie) {
            if ($cookie->getName() === config('session.cookie')) {
                $this->assertTrue($cookie->isHttpOnly());
                $this->assertTrue($cookie->isSecure() || app()->environment('local'));
                $this->assertEquals('lax', strtolower($cookie->getSameSite() ?? 'lax'));
            }
        }
    }

    /**
     * Test prevention of user enumeration.
     */
    public function test_prevention_of_user_enumeration()
    {
        $existingUser = User::factory()->create();
        
        // Password reset for existing user
        $response1 = $this->postJson('/api/password/email', [
            'email' => $existingUser->email,
        ]);
        
        // Password reset for non-existent user
        $response2 = $this->postJson('/api/password/email', [
            'email' => 'nonexistent@example.com',
        ]);
        
        // Both should return the same response
        $this->assertEquals($response1->status(), $response2->status());
        $this->assertEquals($response1->json(), $response2->json());
    }

    /**
     * Test secure headers in authentication responses.
     */
    public function test_secure_headers_in_authentication_responses()
    {
        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);
        
        // Check security headers
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('X-XSS-Protection', '1; mode=block');
        
        // Should not expose sensitive headers
        $this->assertNull($response->headers->get('X-Powered-By'));
        $this->assertNull($response->headers->get('Server'));
    }
}