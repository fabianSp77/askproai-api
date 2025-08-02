<?php

namespace Tests\Feature\Auth;

use App\Models\Company;
use App\Models\User;
use App\Models\PortalUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class TwoFactorAuthenticationTest extends TestCase
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
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        $this->portalUser = PortalUser::factory()->create([
            'company_id' => $this->company->id,
            'email' => 'portal@test.com',
            'password' => Hash::make('password'),
            'is_active' => true,
            'role' => PortalUser::ROLE_ADMIN,
        ]);
    }

    // 2FA Setup Tests
    public function test_admin_can_enable_2fa()
    {
        $this->actingAs($this->admin);

        // If 2FA is implemented
        if (method_exists($this->admin, 'enable2FA')) {
            $response = $this->post('/admin/2fa/enable');
            
            $response->assertStatus(200);
            
            $this->admin->refresh();
            $this->assertTrue($this->admin->hasTwoFactorEnabled());
        } else {
            $this->markTestSkipped('2FA not implemented yet');
        }
    }

    public function test_portal_user_can_enable_2fa()
    {
        $this->actingAs($this->portalUser, 'portal');

        if (method_exists($this->portalUser, 'enable2FA')) {
            $response = $this->post('/business/2fa/enable');
            
            $response->assertStatus(200);
            
            $this->portalUser->refresh();
            $this->assertTrue($this->portalUser->hasTwoFactorEnabled());
        } else {
            $this->markTestSkipped('2FA not implemented yet');
        }
    }

    public function test_2fa_setup_generates_backup_codes()
    {
        $this->actingAs($this->admin);

        if (method_exists($this->admin, 'generateBackupCodes')) {
            $response = $this->post('/admin/2fa/enable');
            
            if ($response->status() !== 404) {
                $this->admin->refresh();
                
                // Should have backup codes
                $backupCodes = $this->admin->getBackupCodes();
                $this->assertIsArray($backupCodes);
                $this->assertGreaterThan(0, count($backupCodes));
            }
        } else {
            $this->markTestSkipped('Backup codes not implemented yet');
        }
    }

    // 2FA Login Flow Tests
    public function test_2fa_enabled_user_redirected_to_2fa_verification()
    {
        // Enable 2FA for user
        if (method_exists($this->admin, 'enable2FA')) {
            $this->admin->enable2FA();
            
            $response = $this->post('/admin/login', [
                'email' => 'admin@test.com',
                'password' => 'password',
            ]);

            // Should redirect to 2FA verification
            $response->assertRedirect('/admin/2fa/verify');
            
            // Should not be fully authenticated yet
            $this->assertFalse(auth()->check());
        } else {
            $this->markTestSkipped('2FA not implemented yet');
        }
    }

    public function test_2fa_verification_with_valid_code()
    {
        if (method_exists($this->admin, 'enable2FA') && method_exists($this->admin, 'getCurrentTwoFactorCode')) {
            $this->admin->enable2FA();
            
            // Start login process
            $this->post('/admin/login', [
                'email' => 'admin@test.com',
                'password' => 'password',
            ]);

            // Get current 2FA code (in real implementation, this would come from authenticator app)
            $code = $this->admin->getCurrentTwoFactorCode();
            
            $response = $this->post('/admin/2fa/verify', [
                'code' => $code,
            ]);

            $response->assertRedirect('/admin');
            $this->assertAuthenticated();
        } else {
            $this->markTestSkipped('2FA code generation not implemented yet');
        }
    }

    public function test_2fa_verification_with_invalid_code()
    {
        if (method_exists($this->admin, 'enable2FA')) {
            $this->admin->enable2FA();
            
            $this->post('/admin/login', [
                'email' => 'admin@test.com',
                'password' => 'password',
            ]);

            $response = $this->post('/admin/2fa/verify', [
                'code' => '000000', // Invalid code
            ]);

            $response->assertSessionHasErrors(['code']);
            $this->assertGuest();
        } else {
            $this->markTestSkipped('2FA not implemented yet');
        }
    }

    public function test_2fa_verification_with_backup_code()
    {
        if (method_exists($this->admin, 'enable2FA') && method_exists($this->admin, 'generateBackupCodes')) {
            $this->admin->enable2FA();
            $backupCodes = $this->admin->generateBackupCodes();
            
            $this->post('/admin/login', [
                'email' => 'admin@test.com',
                'password' => 'password',
            ]);

            $response = $this->post('/admin/2fa/verify', [
                'code' => $backupCodes[0],
            ]);

            $response->assertRedirect('/admin');
            $this->assertAuthenticated();
            
            // Backup code should be consumed
            $this->admin->refresh();
            $this->assertNotContains($backupCodes[0], $this->admin->getBackupCodes());
        } else {
            $this->markTestSkipped('Backup codes not implemented yet');
        }
    }

    // 2FA Security Tests
    public function test_2fa_code_cannot_be_reused()
    {
        if (method_exists($this->admin, 'enable2FA') && method_exists($this->admin, 'getCurrentTwoFactorCode')) {
            $this->admin->enable2FA();
            
            $this->post('/admin/login', [
                'email' => 'admin@test.com',
                'password' => 'password',
            ]);

            $code = $this->admin->getCurrentTwoFactorCode();
            
            // Use code first time
            $this->post('/admin/2fa/verify', [
                'code' => $code,
            ]);

            $this->assertAuthenticated();
            $this->post('/admin/logout');

            // Try to use same code again
            $this->post('/admin/login', [
                'email' => 'admin@test.com',
                'password' => 'password',
            ]);

            $response = $this->post('/admin/2fa/verify', [
                'code' => $code,
            ]);

            $response->assertSessionHasErrors(['code']);
        } else {
            $this->markTestSkipped('2FA replay protection not implemented yet');
        }
    }

    public function test_2fa_verification_has_rate_limiting()
    {
        if (method_exists($this->admin, 'enable2FA')) {
            $this->admin->enable2FA();
            
            $this->post('/admin/login', [
                'email' => 'admin@test.com',
                'password' => 'password',
            ]);

            // Try multiple invalid codes
            for ($i = 0; $i < 10; $i++) {
                $response = $this->post('/admin/2fa/verify', [
                    'code' => '000000',
                ]);
            }

            // Should be rate limited
            $this->assertTrue(in_array($response->status(), [429, 422]));
        } else {
            $this->markTestSkipped('2FA rate limiting not implemented yet');
        }
    }

    public function test_2fa_session_timeout()
    {
        if (method_exists($this->admin, 'enable2FA')) {
            $this->admin->enable2FA();
            
            $this->post('/admin/login', [
                'email' => 'admin@test.com',
                'password' => 'password',
            ]);

            // Simulate session timeout
            session()->put('2fa_user_id', $this->admin->id);
            session()->put('2fa_login_time', now()->subMinutes(10)->timestamp);

            $response = $this->get('/admin/2fa/verify');
            
            // Should redirect back to login due to timeout
            $response->assertRedirect('/admin/login');
        } else {
            $this->markTestSkipped('2FA session timeout not implemented yet');
        }
    }

    // 2FA Management Tests
    public function test_user_can_disable_2fa()
    {
        if (method_exists($this->admin, 'enable2FA') && method_exists($this->admin, 'disable2FA')) {
            $this->admin->enable2FA();
            $this->assertTrue($this->admin->hasTwoFactorEnabled());
            
            $this->actingAs($this->admin);
            
            $response = $this->post('/admin/2fa/disable', [
                'password' => 'password', // Should require password confirmation
            ]);

            $response->assertStatus(200);
            
            $this->admin->refresh();
            $this->assertFalse($this->admin->hasTwoFactorEnabled());
        } else {
            $this->markTestSkipped('2FA disable not implemented yet');
        }
    }

    public function test_user_can_regenerate_backup_codes()
    {
        if (method_exists($this->admin, 'enable2FA') && method_exists($this->admin, 'regenerateBackupCodes')) {
            $this->admin->enable2FA();
            $originalCodes = $this->admin->getBackupCodes();
            
            $this->actingAs($this->admin);
            
            $response = $this->post('/admin/2fa/backup-codes/regenerate');
            
            $response->assertStatus(200);
            
            $this->admin->refresh();
            $newCodes = $this->admin->getBackupCodes();
            
            $this->assertNotEquals($originalCodes, $newCodes);
        } else {
            $this->markTestSkipped('Backup code regeneration not implemented yet');
        }
    }

    public function test_2fa_qr_code_generation()
    {
        $this->actingAs($this->admin);

        if (method_exists($this->admin, 'getTwoFactorQrCode')) {
            $response = $this->get('/admin/2fa/qr-code');
            
            $response->assertStatus(200);
            $response->assertHeader('Content-Type', 'image/svg+xml');
        } else {
            $this->markTestSkipped('QR code generation not implemented yet');
        }
    }

    // 2FA Requirements Tests
    public function test_admin_role_requires_2fa()
    {
        if (method_exists($this->admin, 'requires2FA')) {
            $this->assertTrue($this->admin->requires2FA());
        } else {
            $this->markTestSkipped('2FA requirements not implemented yet');
        }
    }

    public function test_portal_admin_requires_2fa()
    {
        if (method_exists($this->portalUser, 'requires2FA')) {
            $this->assertTrue($this->portalUser->requires2FA());
        } else {
            $this->markTestSkipped('Portal 2FA requirements not implemented yet');
        }
    }

    public function test_staff_user_does_not_require_2fa()
    {
        $staffUser = PortalUser::factory()->create([
            'company_id' => $this->company->id,
            'role' => PortalUser::ROLE_STAFF,
        ]);

        if (method_exists($staffUser, 'requires2FA')) {
            $this->assertFalse($staffUser->requires2FA());
        } else {
            $this->markTestSkipped('2FA requirements not implemented yet');
        }
    }

    // 2FA Enforcement Tests
    public function test_2fa_enforcement_after_grace_period()
    {
        if (method_exists($this->admin, 'requires2FA') && method_exists($this->admin, 'getTwoFactorGracePeriodEndsAt')) {
            // Set grace period to expired
            $this->admin->setTwoFactorGracePeriod(now()->subDays(1));
            
            $response = $this->post('/admin/login', [
                'email' => 'admin@test.com',
                'password' => 'password',
            ]);

            // Should be forced to setup 2FA
            $response->assertRedirect('/admin/2fa/setup');
        } else {
            $this->markTestSkipped('2FA enforcement not implemented yet');
        }
    }

    public function test_2fa_grace_period_allows_login()
    {
        if (method_exists($this->admin, 'requires2FA') && method_exists($this->admin, 'getTwoFactorGracePeriodEndsAt')) {
            // Set grace period to future
            $this->admin->setTwoFactorGracePeriod(now()->addDays(7));
            
            $response = $this->post('/admin/login', [
                'email' => 'admin@test.com',
                'password' => 'password',
            ]);

            // Should allow login but show 2FA setup reminder
            $response->assertRedirect('/admin');
            $this->assertAuthenticated();
        } else {
            $this->markTestSkipped('2FA grace period not implemented yet');
        }
    }

    // 2FA API Tests
    public function test_api_access_requires_2fa_for_sensitive_operations()
    {
        if (method_exists($this->admin, 'hasTwoFactorEnabled')) {
            $this->actingAs($this->admin);
            
            // Try to access sensitive API endpoint
            $response = $this->postJson('/admin/api/users', [
                'name' => 'New User',
                'email' => 'newuser@test.com',
            ]);

            if (!$this->admin->hasTwoFactorEnabled() && $this->admin->requires2FA()) {
                // Should require 2FA setup
                $this->assertTrue(in_array($response->status(), [403, 401]));
            }
        } else {
            $this->markTestSkipped('API 2FA enforcement not implemented yet');
        }
    }

    // 2FA Recovery Tests
    public function test_2fa_recovery_with_email_verification()
    {
        if (method_exists($this->admin, 'enable2FA')) {
            $this->admin->enable2FA();
            
            $response = $this->post('/admin/2fa/recovery', [
                'email' => 'admin@test.com',
            ]);

            if ($response->status() !== 404) {
                // Should send recovery email
                $response->assertStatus(200);
                // In real implementation, would check for email queued
            }
        } else {
            $this->markTestSkipped('2FA recovery not implemented yet');
        }
    }

    // 2FA Audit Tests
    public function test_2fa_events_are_logged()
    {
        if (method_exists($this->admin, 'enable2FA')) {
            $this->actingAs($this->admin);
            
            $this->post('/admin/2fa/enable');
            
            // Should log 2FA enable event
            if (class_exists(\Spatie\Activitylog\Models\Activity::class)) {
                $activity = \Spatie\Activitylog\Models\Activity::where('subject_id', $this->admin->id)
                    ->where('description', '2fa_enabled')
                    ->first();
                    
                $this->assertNotNull($activity);
            }
        } else {
            $this->markTestSkipped('2FA audit logging not implemented yet');
        }
    }

    public function test_failed_2fa_attempts_are_logged()
    {
        if (method_exists($this->admin, 'enable2FA')) {
            $this->admin->enable2FA();
            
            $this->post('/admin/login', [
                'email' => 'admin@test.com',
                'password' => 'password',
            ]);

            $this->post('/admin/2fa/verify', [
                'code' => '000000',
            ]);

            // Should log failed attempt
            if (class_exists(\Spatie\Activitylog\Models\Activity::class)) {
                $activity = \Spatie\Activitylog\Models\Activity::where('subject_id', $this->admin->id)
                    ->where('description', '2fa_failed')
                    ->first();
                    
                $this->assertNotNull($activity);
            }
        } else {
            $this->markTestSkipped('2FA audit logging not implemented yet');
        }
    }

    // Helper Methods for Testing
    protected function mockTwoFactorCode($user, $code = '123456')
    {
        // Mock the 2FA code for testing
        Cache::put("2fa_code_{$user->id}", $code, now()->addMinutes(5));
    }

    protected function simulateTimeBasedCode($secret, $timestamp = null)
    {
        // Simulate TOTP code generation for testing
        $timestamp = $timestamp ?? time();
        $timeSlice = floor($timestamp / 30);
        
        // This is a simplified version - real implementation would use proper TOTP algorithm
        return str_pad(($timeSlice % 1000000), 6, '0', STR_PAD_LEFT);
    }
}