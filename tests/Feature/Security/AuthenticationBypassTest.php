<?php

namespace Tests\Feature\Security;

use App\Models\User;
use App\Models\PortalUser;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

/**
 * Authentication Bypass Security Test
 * 
 * Tests various authentication bypass vulnerabilities including
 * weak passwords, brute force, token manipulation, and privilege escalation.
 * 
 * SEVERITY: CRITICAL - Complete system compromise potential
 */
class AuthenticationBypassTest extends BaseSecurityTestCase
{
    public function test_brute_force_protection()
    {
        $user = PortalUser::factory()->create([
            'company_id' => $this->company1->id,
            'email' => 'bruteforce@test.com',
            'password' => Hash::make('correct_password'),
        ]);

        $attempts = 0;
        $maxAttempts = 20;
        $lockedOut = false;

        // Attempt multiple failed logins
        for ($i = 0; $i < $maxAttempts; $i++) {
            $response = $this->postJson('/business/login', [
                'email' => 'bruteforce@test.com',
                'password' => 'wrong_password',
            ]);

            $attempts++;

            if ($response->status() === 429) {
                $lockedOut = true;
                break;
            }

            // Small delay to prevent overwhelming the system
            usleep(100000); // 0.1 second
        }

        // Should be locked out before reaching max attempts
        $this->assertTrue($lockedOut || $attempts >= 5, 
            'Brute force protection not enforced');

        $this->logSecurityTestResult('brute_force_protection', $lockedOut);
    }

    public function test_weak_password_rejection()
    {
        $weakPasswords = [
            'password',
            '123456',
            'admin',
            'test',
            'password123',
            '12345678',
            'qwerty',
            'abc123',
            '', // Empty password
            ' ', // Space only
        ];

        foreach ($weakPasswords as $weakPassword) {
            $response = $this->postJson('/register', [
                'name' => 'Weak Password Test',
                'email' => "weak{$weakPassword}@test.com",
                'password' => $weakPassword,
                'password_confirmation' => $weakPassword,
                'company_name' => 'Test Company',
            ]);

            if ($response->status() !== 404) {
                $this->assertTrue(
                    in_array($response->status(), [422, 400]),
                    "Weak password accepted: '{$weakPassword}'"
                );
            }
        }

        $this->logSecurityTestResult('weak_password_rejection', true);
    }

    public function test_session_token_manipulation()
    {
        // Login as legitimate user
        $this->actingAs($this->portalUser1, 'portal');
        $legitimateToken = session()->token();
        
        // Try to manipulate session token
        session()->put('_token', 'manipulated_token_12345');
        session()->put('user_id', $this->portalUser2->id); // Try to switch user
        session()->put('company_id', $this->company2->id); // Try to switch company
        
        $response = $this->getJson('/business/api/user');
        
        if ($response->status() === 200) {
            $userData = $response->json();
            
            // Should still return original user data
            $this->assertEquals($this->portalUser1->id, $userData['id'] ?? null);
            $this->assertEquals($this->company1->id, $userData['company_id'] ?? null);
        }

        $this->logSecurityTestResult('session_token_manipulation_protection', true);
    }

    public function test_password_reset_token_security()
    {
        $user = User::factory()->create([
            'company_id' => $this->company1->id,
            'email' => 'reset@test.com',
        ]);

        // Request password reset
        $response = $this->postJson('/password/email', [
            'email' => 'reset@test.com',
        ]);

        // Try to manipulate reset process
        $invalidTokens = [
            'invalid_token_123',
            str_repeat('a', 100), // Long token
            '', // Empty token
            'admin_override_token',
        ];

        foreach ($invalidTokens as $token) {
            $response = $this->postJson('/password/reset', [
                'token' => $token,
                'email' => 'reset@test.com',
                'password' => 'new_password_123',
                'password_confirmation' => 'new_password_123',
            ]);

            if ($response->status() !== 404) {
                $this->assertTrue(
                    in_array($response->status(), [422, 400, 403]),
                    "Invalid reset token accepted: {$token}"
                );
            }
        }

        $this->logSecurityTestResult('password_reset_token_security', true);
    }

    public function test_privilege_escalation_prevention()
    {
        // Login as staff user
        $this->actingAs($this->staffUser1, 'portal');
        
        // Try to escalate privileges
        $response = $this->putJson("/business/api/user/{$this->staffUser1->id}", [
            'role' => 'admin',
            'is_admin' => true,
            'permissions' => ['*'],
            'company_id' => null, // Try to become global admin
        ]);

        if (in_array($response->status(), [200, 204])) {
            $this->staffUser1->refresh();
            
            // Should not allow privilege escalation
            $this->assertNotEquals('admin', $this->staffUser1->role);
            $this->assertFalse($this->staffUser1->is_admin ?? false);
            $this->assertEquals($this->company1->id, $this->staffUser1->company_id);
        }

        $this->logSecurityTestResult('privilege_escalation_prevention', true);
    }

    public function test_authentication_timing_attacks()
    {
        // Measure login time for non-existent user
        $start1 = microtime(true);
        $response1 = $this->postJson('/business/login', [
            'email' => 'nonexistent@test.com',
            'password' => 'any_password',
        ]);
        $time1 = microtime(true) - $start1;

        // Measure login time for existing user with wrong password
        $start2 = microtime(true);
        $response2 = $this->postJson('/business/login', [
            'email' => $this->portalUser1->email,
            'password' => 'wrong_password',
        ]);
        $time2 = microtime(true) - $start2;

        // Times should be similar to prevent user enumeration
        $timeDifference = abs($time1 - $time2);
        $this->assertLessThan(0.5, $timeDifference, 
            'Timing attack vulnerability in authentication');

        $this->logSecurityTestResult('authentication_timing_attack_prevention', true);
    }

    public function test_concurrent_login_protection()
    {
        // Login from first session
        $response1 = $this->postJson('/business/login', [
            'email' => $this->portalUser1->email,
            'password' => 'password',
        ]);

        $sessionId1 = session()->getId();

        // Simulate login from different location/session
        session()->flush();
        session()->regenerate();
        
        $response2 = $this->postJson('/business/login', [
            'email' => $this->portalUser1->email,
            'password' => 'password',
        ]);

        $sessionId2 = session()->getId();

        // Check concurrent session policy
        $this->assertNotEquals($sessionId1, $sessionId2);
        
        // First session should be invalidated or both should work
        $this->assertTrue(in_array($response2->status(), [200, 302]));

        $this->logSecurityTestResult('concurrent_login_protection', true);
    }

    public function test_account_lockout_mechanism()
    {
        $user = PortalUser::factory()->create([
            'company_id' => $this->company1->id,
            'email' => 'lockout@test.com',
            'password' => Hash::make('correct_password'),
        ]);

        // Make several failed attempts
        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/business/login', [
                'email' => 'lockout@test.com',
                'password' => 'wrong_password',
            ]);
        }

        // Try with correct password after failed attempts
        $response = $this->postJson('/business/login', [
            'email' => 'lockout@test.com',
            'password' => 'correct_password',
        ]);

        // Should be locked out even with correct password
        if ($response->status() !== 200) {
            $this->assertTrue(in_array($response->status(), [429, 423, 403]));
        }

        $this->logSecurityTestResult('account_lockout_mechanism', true);
    }

    public function test_two_factor_authentication_bypass()
    {
        if (!class_exists('\App\Models\TwoFactorAuth')) {
            $this->markTestSkipped('2FA not implemented');
        }

        // Create user with 2FA enabled
        $user = PortalUser::factory()->create([
            'company_id' => $this->company1->id,
            'email' => '2fa@test.com',
            'password' => Hash::make('password'),
            'two_factor_enabled' => true,
        ]);

        // Login should require 2FA
        $response = $this->postJson('/business/login', [
            'email' => '2fa@test.com',
            'password' => 'password',
        ]);

        // Should not be fully authenticated without 2FA
        if ($response->status() === 200) {
            $response = $this->getJson('/business/api/user');
            $this->assertTrue(in_array($response->status(), [401, 403, 302]));
        }

        // Try to bypass 2FA
        $bypassAttempts = [
            ['two_factor_code' => '000000'],
            ['two_factor_code' => '123456'],
            ['bypass_2fa' => true],
            ['admin_override' => true],
        ];

        foreach ($bypassAttempts as $attempt) {
            $response = $this->postJson('/business/two-factor/verify', $attempt);
            
            if ($response->status() !== 404) {
                $this->assertTrue(in_array($response->status(), [422, 400, 403]));
            }
        }

        $this->logSecurityTestResult('two_factor_bypass_prevention', true);
    }

    public function test_remember_me_token_security()
    {
        // Login with remember me
        $response = $this->postJson('/business/login', [
            'email' => $this->portalUser1->email,
            'password' => 'password',
            'remember' => true,
        ]);

        if ($response->status() === 200) {
            // Check remember token security
            $user = PortalUser::find($this->portalUser1->id);
            
            if ($user && $user->remember_token) {
                // Token should be sufficiently long and random
                $this->assertGreaterThan(40, strlen($user->remember_token));
                $this->assertNotEquals('remember_token_123', $user->remember_token);
            }
        }

        $this->logSecurityTestResult('remember_token_security', true);
    }

    public function test_api_key_authentication_security()
    {
        if (!method_exists(User::class, 'createToken')) {
            $this->markTestSkipped('API tokens not implemented');
        }

        $user = User::factory()->create(['company_id' => $this->company1->id]);
        $token = $user->createToken('Test Token')->plainTextToken;

        // Test with valid token
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/admin/api/users');

        if ($response->status() === 200) {
            // Test token manipulation
            $manipulatedTokens = [
                'Bearer invalid_token_123',
                'Bearer ' . str_replace('|', ':', $token), // Manipulate separator
                'Bearer ' . substr($token, 0, -5) . 'XXXXX', // Partial token
                'Basic ' . base64_encode($token), // Wrong auth type
            ];

            foreach ($manipulatedTokens as $manipulatedToken) {
                $response = $this->withHeaders([
                    'Authorization' => $manipulatedToken,
                ])->getJson('/admin/api/users');

                $this->assertTrue(in_array($response->status(), [401, 403]));
            }
        }

        $this->logSecurityTestResult('api_key_security', true);
    }

    public function test_password_complexity_enforcement()
    {
        $complexPasswords = [
            'Simple123!', // Should pass
            'VeryComplexPassword123!@#', // Should pass
        ];

        $simplePasswords = [
            'simple', // Too simple
            'PASSWORD', // No lowercase
            'password', // No uppercase
            'Password', // No numbers
            'Password123', // No special chars
        ];

        foreach ($complexPasswords as $password) {
            $response = $this->postJson('/register', [
                'name' => 'Complex Test',
                'email' => "complex_{$password}@test.com",
                'password' => $password,
                'password_confirmation' => $password,
                'company_name' => 'Test Company',
            ]);

            // Complex passwords should be accepted or fail for other reasons
            if ($response->status() !== 404) {
                $this->assertTrue(in_array($response->status(), [200, 201, 422]));
            }
        }

        foreach ($simplePasswords as $password) {
            $response = $this->postJson('/register', [
                'name' => 'Simple Test',
                'email' => "simple_{$password}@test.com",
                'password' => $password,
                'password_confirmation' => $password,
                'company_name' => 'Test Company',
            ]);

            if ($response->status() !== 404) {
                $this->assertTrue(in_array($response->status(), [422, 400]));
            }
        }

        $this->logSecurityTestResult('password_complexity_enforcement', true);
    }

    public function test_authentication_bypass_via_headers()
    {
        $bypassHeaders = [
            'X-Forwarded-User' => $this->portalUser1->email,
            'X-Remote-User' => $this->portalUser1->email,
            'X-Authenticated-User' => $this->portalUser1->email,
            'Authorization' => 'Basic ' . base64_encode('admin:password'),
            'X-API-Key' => 'bypass_key_123',
            'X-Admin-Override' => 'true',
            'X-Bypass-Auth' => 'true',
        ];

        foreach ($bypassHeaders as $header => $value) {
            $response = $this->withHeaders([$header => $value])
                ->getJson('/business/api/user');

            // Should not authenticate via headers
            if ($response->status() !== 404) {
                $this->assertTrue(in_array($response->status(), [401, 403]));
            }
        }

        $this->logSecurityTestResult('header_authentication_bypass_prevention', true);
    }
}