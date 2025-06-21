<?php

namespace Tests\E2E;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerPreference;
use App\Mail\EmailChangedNotification;
use App\Mail\PasswordChangedNotification;
use App\Events\CustomerProfileUpdated;
use App\Events\CustomerPreferencesUpdated;
use Carbon\Carbon;

class ProfileManagementE2ETest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('avatars');

        $this->company = Company::factory()->create([
            'name' => 'Wellness Center',
            'settings' => [
                'customer_portal' => true,
                'portal_features' => [
                    'profile' => true,
                    'preferences' => true,
                    'privacy' => true,
                ],
                'gdpr_compliant' => true,
            ],
        ]);

        $this->customer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Maria Schmidt',
            'email' => 'maria@example.com',
            'phone' => '+4915123456789',
            'password' => Hash::make('oldpassword123'),
            'email_verified_at' => now(),
            'portal_access_enabled' => true,
            'birthdate' => '1985-06-15',
            'address' => 'Current Street 123',
            'city' => 'Berlin',
            'postal_code' => '10115',
            'country' => 'Germany',
            'avatar' => null,
            'preferences' => [
                'language' => 'de',
                'timezone' => 'Europe/Berlin',
                'date_format' => 'd.m.Y',
                'time_format' => '24h',
            ],
            'communication_preferences' => [
                'email' => true,
                'sms' => false,
                'whatsapp' => false,
                'push' => true,
                'marketing' => false,
                'reminders' => true,
                'newsletter' => true,
            ],
        ]);

        $this->actingAs($this->customer, 'customer');
    }

    /** @test */
    public function customer_can_view_and_update_profile_information()
    {
        Event::fake();

        // Visit profile page
        $response = $this->get('/customer/profile');
        
        $response->assertStatus(200);
        $response->assertSee('My Profile');
        
        // Check current information is displayed
        $response->assertSee('Maria Schmidt');
        $response->assertSee('maria@example.com');
        $response->assertSee('+4915123456789');
        $response->assertSee('Current Street 123');
        $response->assertSee('June 15, 1985');
        
        // Profile sections
        $response->assertSee('Personal Information');
        $response->assertSee('Contact Details');
        $response->assertSee('Address');
        $response->assertSee('Security');
        $response->assertSee('Preferences');
        
        // Update profile information
        $response = $this->put('/customer/profile', [
            'name' => 'Maria Schmidt-Mueller',
            'phone' => '+4915198765432',
            'birthdate' => '1985-06-15',
            'address' => 'New Avenue 456',
            'city' => 'Munich',
            'postal_code' => '80331',
            'country' => 'Germany',
            'emergency_contact' => 'John Mueller',
            'emergency_phone' => '+4915111111111',
            'medical_notes' => 'Allergic to certain cosmetics',
        ]);
        
        $response->assertRedirect('/customer/profile');
        $response->assertSessionHas('success', 'Profile updated successfully!');
        
        // Verify profile was updated
        $this->customer->refresh();
        $this->assertEquals('Maria Schmidt-Mueller', $this->customer->name);
        $this->assertEquals('+4915198765432', $this->customer->phone);
        $this->assertEquals('New Avenue 456', $this->customer->address);
        $this->assertEquals('Munich', $this->customer->city);
        $this->assertEquals('80331', $this->customer->postal_code);
        
        // Verify event was fired
        Event::assertDispatched(CustomerProfileUpdated::class, function ($event) {
            return $event->customer->id === $this->customer->id &&
                   $event->changedFields === ['name', 'phone', 'address', 'city', 'postal_code'];
        });
        
        // Verify activity log
        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Customer::class,
            'subject_id' => $this->customer->id,
            'description' => 'profile_updated',
            'causer_id' => $this->customer->id,
        ]);
    }

    /** @test */
    public function customer_can_upload_and_manage_avatar()
    {
        // Upload avatar
        $file = UploadedFile::fake()->image('avatar.jpg', 300, 300);
        
        $response = $this->post('/customer/profile/avatar', [
            'avatar' => $file,
        ]);
        
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Avatar uploaded successfully!',
            'avatar_url' => Storage::url('avatars/' . $this->customer->id . '.jpg'),
        ]);
        
        // Verify file was stored
        Storage::disk('avatars')->assertExists($this->customer->id . '.jpg');
        
        // Verify customer record was updated
        $this->customer->refresh();
        $this->assertNotNull($this->customer->avatar);
        $this->assertStringContainsString('avatars/' . $this->customer->id, $this->customer->avatar);
        
        // View profile with avatar
        $response = $this->get('/customer/profile');
        $response->assertSee($this->customer->avatar);
        $response->assertSee('Change Avatar');
        $response->assertSee('Remove Avatar');
        
        // Remove avatar
        $response = $this->delete('/customer/profile/avatar');
        
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Avatar removed successfully!',
        ]);
        
        // Verify file was deleted
        Storage::disk('avatars')->assertMissing($this->customer->id . '.jpg');
        
        $this->customer->refresh();
        $this->assertNull($this->customer->avatar);
    }

    /** @test */
    public function customer_can_change_email_with_verification()
    {
        Mail::fake();
        Event::fake();

        // Request email change
        $response = $this->post('/customer/profile/email', [
            'new_email' => 'maria.new@example.com',
            'current_password' => 'oldpassword123',
        ]);
        
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Verification email sent to your new email address.',
        ]);
        
        // Verify pending email was saved
        $this->customer->refresh();
        $this->assertEquals('maria.new@example.com', $this->customer->pending_email);
        $this->assertEquals('maria@example.com', $this->customer->email); // Not changed yet
        
        // Verify email was sent
        Mail::assertQueued(\App\Mail\VerifyNewEmail::class, function ($mail) {
            return $mail->hasTo('maria.new@example.com');
        });
        
        // Get verification token
        $token = \DB::table('email_verifications')
            ->where('customer_id', $this->customer->id)
            ->first()->token;
        
        // Verify new email
        $response = $this->get("/customer/profile/email/verify/{$token}");
        
        $response->assertRedirect('/customer/profile');
        $response->assertSessionHas('success', 'Email address updated successfully!');
        
        // Verify email was changed
        $this->customer->refresh();
        $this->assertEquals('maria.new@example.com', $this->customer->email);
        $this->assertNull($this->customer->pending_email);
        $this->assertNotNull($this->customer->email_verified_at);
        
        // Verify notification was sent to old email
        Mail::assertQueued(EmailChangedNotification::class, function ($mail) {
            return $mail->hasTo('maria@example.com');
        });
        
        // Verify event
        Event::assertDispatched('customer.email.changed', function ($event, $data) {
            return $data[0]->id === $this->customer->id &&
                   $data[1] === 'maria@example.com' &&
                   $data[2] === 'maria.new@example.com';
        });
    }

    /** @test */
    public function customer_can_change_password()
    {
        Mail::fake();
        Event::fake();

        // Visit security settings
        $response = $this->get('/customer/profile/security');
        
        $response->assertStatus(200);
        $response->assertSee('Security Settings');
        $response->assertSee('Change Password');
        $response->assertSee('Two-Factor Authentication');
        $response->assertSee('Active Sessions');
        
        // Change password with validation errors
        $response = $this->put('/customer/profile/password', [
            'current_password' => 'wrongpassword',
            'password' => 'newpassword123',
            'password_confirmation' => 'differentpassword',
        ]);
        
        $response->assertSessionHasErrors(['current_password', 'password']);
        
        // Change password successfully
        $response = $this->put('/customer/profile/password', [
            'current_password' => 'oldpassword123',
            'password' => 'NewSecurePassword123!',
            'password_confirmation' => 'NewSecurePassword123!',
        ]);
        
        $response->assertRedirect('/customer/profile/security');
        $response->assertSessionHas('success', 'Password changed successfully!');
        
        // Verify password was changed
        $this->customer->refresh();
        $this->assertTrue(Hash::check('NewSecurePassword123!', $this->customer->password));
        
        // Verify notification was sent
        Mail::assertQueued(PasswordChangedNotification::class, function ($mail) {
            return $mail->hasTo($this->customer->email);
        });
        
        // Verify other sessions were logged out
        $this->assertDatabaseMissing('sessions', [
            'user_id' => $this->customer->id,
            'user_type' => 'customer',
        ]);
        
        // Verify event
        Event::assertDispatched('customer.password.changed');
        
        // Can login with new password
        $this->post('/customer/logout');
        
        $response = $this->post('/customer/login', [
            'email' => 'maria@example.com',
            'password' => 'NewSecurePassword123!',
        ]);
        
        $response->assertRedirect('/customer');
    }

    /** @test */
    public function customer_can_manage_communication_preferences()
    {
        Event::fake();

        // View preferences
        $response = $this->get('/customer/profile/preferences');
        
        $response->assertStatus(200);
        $response->assertSee('Communication Preferences');
        
        // Check current preferences
        $response->assertSee('checked', $response->content()); // Email is checked
        $response->assertDontSee('name="sms" checked'); // SMS is not checked
        
        // Update preferences
        $response = $this->put('/customer/profile/preferences', [
            'communication' => [
                'email' => true,
                'sms' => true,
                'whatsapp' => true,
                'push' => false,
                'marketing' => true,
                'reminders' => true,
                'newsletter' => false,
            ],
            'reminder_timing' => [
                'appointment_reminder' => 24, // 24 hours before
                'followup_reminder' => 48, // 48 hours after
            ],
            'quiet_hours' => [
                'enabled' => true,
                'start' => '21:00',
                'end' => '09:00',
            ],
        ]);
        
        $response->assertRedirect('/customer/profile/preferences');
        $response->assertSessionHas('success', 'Preferences updated successfully!');
        
        // Verify preferences were updated
        $this->customer->refresh();
        $this->assertTrue($this->customer->communication_preferences['sms']);
        $this->assertTrue($this->customer->communication_preferences['whatsapp']);
        $this->assertFalse($this->customer->communication_preferences['push']);
        $this->assertTrue($this->customer->communication_preferences['marketing']);
        $this->assertFalse($this->customer->communication_preferences['newsletter']);
        
        // Verify additional preferences
        $this->assertEquals(24, $this->customer->preferences['reminder_timing']['appointment_reminder']);
        $this->assertTrue($this->customer->preferences['quiet_hours']['enabled']);
        
        // Verify event
        Event::assertDispatched(CustomerPreferencesUpdated::class, function ($event) {
            return $event->customer->id === $this->customer->id &&
                   $event->changedPreferences === ['sms', 'whatsapp', 'push', 'marketing', 'newsletter'];
        });
    }

    /** @test */
    public function customer_can_setup_two_factor_authentication()
    {
        // Visit 2FA setup
        $response = $this->get('/customer/profile/security/2fa');
        
        $response->assertStatus(200);
        $response->assertSee('Set Up Two-Factor Authentication');
        $response->assertSee('Scan this QR code with your authenticator app');
        
        // Get secret from session
        $secret = session('2fa_secret');
        $this->assertNotNull($secret);
        
        // Verify with wrong code
        $response = $this->post('/customer/profile/security/2fa', [
            'code' => '000000',
        ]);
        
        $response->assertSessionHasErrors('code');
        
        // Mock valid TOTP code
        $validCode = $this->generateValidTOTP($secret);
        
        // Enable 2FA
        $response = $this->post('/customer/profile/security/2fa', [
            'code' => $validCode,
        ]);
        
        $response->assertRedirect('/customer/profile/security');
        $response->assertSessionHas('success', 'Two-factor authentication enabled successfully!');
        
        // Verify 2FA was enabled
        $this->customer->refresh();
        $this->assertTrue($this->customer->two_factor_enabled);
        $this->assertNotNull($this->customer->two_factor_secret);
        
        // Get recovery codes
        $response = $this->get('/customer/profile/security/2fa/recovery-codes');
        
        $response->assertStatus(200);
        $response->assertSee('Recovery Codes');
        $response->assertSee('Store these codes in a safe place');
        
        $recoveryCodes = $response->viewData('recoveryCodes');
        $this->assertCount(8, $recoveryCodes);
        
        // Logout and test 2FA login
        $this->post('/customer/logout');
        
        // Login with email/password
        $response = $this->post('/customer/login', [
            'email' => 'maria@example.com',
            'password' => 'oldpassword123',
        ]);
        
        $response->assertRedirect('/customer/login/2fa');
        
        // Submit 2FA code
        $response = $this->post('/customer/login/2fa', [
            'code' => $this->generateValidTOTP($this->customer->two_factor_secret),
        ]);
        
        $response->assertRedirect('/customer');
        $this->assertAuthenticatedAs($this->customer, 'customer');
    }

    /** @test */
    public function customer_can_manage_privacy_settings()
    {
        // View privacy settings
        $response = $this->get('/customer/profile/privacy');
        
        $response->assertStatus(200);
        $response->assertSee('Privacy Settings');
        $response->assertSee('Data Sharing');
        $response->assertSee('Profile Visibility');
        $response->assertSee('Data Retention');
        
        // Update privacy settings
        $response = $this->put('/customer/profile/privacy', [
            'data_sharing' => [
                'analytics' => false,
                'third_party' => false,
                'improvement' => true,
            ],
            'profile_visibility' => [
                'show_in_reviews' => false,
                'show_full_name' => false,
                'show_initials' => true,
            ],
            'data_retention' => [
                'delete_after_inactive' => 24, // months
                'auto_delete_messages' => 12, // months
            ],
        ]);
        
        $response->assertRedirect('/customer/profile/privacy');
        $response->assertSessionHas('success', 'Privacy settings updated successfully!');
        
        // Export personal data
        $response = $this->post('/customer/profile/privacy/export');
        
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Your data export has been queued. You will receive an email when it\'s ready.',
        ]);
        
        // Verify export job was queued
        $this->assertDatabaseHas('jobs', [
            'queue' => 'exports',
        ]);
        
        // Request account deletion
        $response = $this->get('/customer/profile/privacy/delete');
        
        $response->assertStatus(200);
        $response->assertSee('Delete Account');
        $response->assertSee('This action cannot be undone');
        $response->assertSee('All your data will be permanently deleted');
        
        // Confirm deletion
        $response = $this->delete('/customer/profile', [
            'password' => 'oldpassword123',
            'confirmation' => 'DELETE',
            'reason' => 'no_longer_needed',
            'feedback' => 'Moving to another city',
        ]);
        
        $response->assertRedirect('/customer/login');
        $response->assertSessionHas('message', 'Your account has been scheduled for deletion.');
        
        // Verify account was marked for deletion
        $this->customer->refresh();
        $this->assertNotNull($this->customer->deletion_requested_at);
        $this->assertEquals('pending_deletion', $this->customer->status);
        
        // Account should be soft deleted after grace period
        Carbon::setTestNow(now()->addDays(30));
        
        $this->artisan('customers:process-deletions')->assertSuccessful();
        
        $this->assertSoftDeleted('customers', [
            'id' => $this->customer->id,
        ]);
    }

    /** @test */
    public function customer_can_manage_connected_accounts()
    {
        // View connected accounts
        $response = $this->get('/customer/profile/connected-accounts');
        
        $response->assertStatus(200);
        $response->assertSee('Connected Accounts');
        $response->assertSee('Google');
        $response->assertSee('Facebook');
        $response->assertSee('Apple');
        
        // Connect Google account
        $response = $this->get('/customer/profile/connect/google');
        
        $response->assertRedirect();
        $this->assertStringContainsString('accounts.google.com', $response->headers->get('Location'));
        
        // Simulate OAuth callback
        $response = $this->get('/customer/profile/connect/google/callback', [
            'code' => 'test-auth-code',
            'state' => session('oauth_state'),
        ]);
        
        $response->assertRedirect('/customer/profile/connected-accounts');
        $response->assertSessionHas('success', 'Google account connected successfully!');
        
        // Verify connection was saved
        $this->assertDatabaseHas('customer_social_accounts', [
            'customer_id' => $this->customer->id,
            'provider' => 'google',
            'provider_id' => 'google-user-id',
        ]);
        
        // Disconnect account
        $response = $this->delete('/customer/profile/connect/google');
        
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Google account disconnected successfully!',
        ]);
        
        $this->assertDatabaseMissing('customer_social_accounts', [
            'customer_id' => $this->customer->id,
            'provider' => 'google',
        ]);
    }

    /** @test */
    public function customer_can_view_and_download_activity_log()
    {
        // Create some activity
        activity()
            ->performedOn($this->customer)
            ->causedBy($this->customer)
            ->withProperties(['ip' => '192.168.1.1'])
            ->log('logged_in');
        
        activity()
            ->performedOn($this->customer)
            ->causedBy($this->customer)
            ->withProperties(['fields' => ['name', 'phone']])
            ->log('profile_updated');
        
        // View activity log
        $response = $this->get('/customer/profile/activity');
        
        $response->assertStatus(200);
        $response->assertSee('Account Activity');
        $response->assertSee('Recent Activity');
        
        // Should see activities
        $response->assertSee('Logged in');
        $response->assertSee('Profile updated');
        $response->assertSee('192.168.1.1');
        
        // Filter by date
        $response = $this->get('/customer/profile/activity?from=' . now()->subWeek()->format('Y-m-d'));
        
        $response->assertStatus(200);
        $this->assertCount(2, $response->viewData('activities'));
        
        // Download activity log
        $response = $this->get('/customer/profile/activity/download');
        
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $response->assertHeader('Content-Disposition', 'attachment; filename="activity-log-' . now()->format('Y-m-d') . '.csv"');
        
        $csv = $response->getContent();
        $this->assertStringContainsString('Date,Activity,IP Address,Details', $csv);
        $this->assertStringContainsString('logged_in', $csv);
        $this->assertStringContainsString('profile_updated', $csv);
    }

    /** @test */
    public function profile_updates_sync_with_third_party_services()
    {
        Event::fake();

        // Update profile triggering sync
        $response = $this->put('/customer/profile', [
            'name' => 'Maria Schmidt-Updated',
            'phone' => '+4915199999999',
        ]);
        
        $response->assertRedirect('/customer/profile');
        
        // Verify sync events were dispatched
        Event::assertDispatched('customer.sync.needed', function ($event, $data) {
            return $data[0]->id === $this->customer->id &&
                   in_array('calcom', $data[1]) &&
                   in_array('retell', $data[1]);
        });
        
        // Check sync status
        $response = $this->get('/customer/profile/sync-status');
        
        $response->assertStatus(200);
        $response->assertJson([
            'calcom' => ['status' => 'syncing', 'last_sync' => null],
            'retell' => ['status' => 'syncing', 'last_sync' => null],
        ]);
    }

    /**
     * Generate valid TOTP code for testing
     */
    protected function generateValidTOTP($secret): string
    {
        // In real implementation, use a TOTP library
        // For testing, return a mock valid code
        return '123456';
    }
}