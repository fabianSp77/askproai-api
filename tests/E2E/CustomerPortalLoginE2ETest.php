<?php

namespace Tests\E2E;

use App\Mail\MagicLinkEmail;
use App\Mail\PasswordResetEmail;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Customer;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CustomerPortalLoginE2ETest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Branch $branch;
    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test company and branch
        $this->company = Company::factory()->create([
            'name' => 'Test Medical Practice',
            'settings' => [
                'customer_portal' => true,
                'allow_magic_link' => true,
                'portal_features' => [
                    'appointments' => true,
                    'invoices' => true,
                    'profile' => true,
                ],
            ],
        ]);

        $this->branch = Branch::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Main Location',
        ]);

        // Create test customer
        $this->customer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Test Customer',
            'email' => 'customer@example.com',
            'phone' => '+4915123456789',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
            'portal_access_enabled' => true,
        ]);
    }

    /** @test */

    #[Test]
    public function customer_can_login_with_email_and_password()
    {
        // Visit login page
        $response = $this->get('/customer/login');
        $response->assertStatus(200);
        $response->assertSee('Customer Login');
        $response->assertSee('Email Address');
        $response->assertSee('Password');

        // Attempt login with wrong credentials
        $response = $this->post('/customer/login', [
            'email' => 'customer@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertSessionHasErrors('email');
        $response->assertRedirect('/customer/login');

        // Login with correct credentials
        $response = $this->post('/customer/login', [
            'email' => 'customer@example.com',
            'password' => 'password123',
            'remember' => true,
        ]);

        $response->assertRedirect('/customer');
        $response->assertSessionHas('success', 'Welcome back!');
        
        // Verify authentication
        $this->assertAuthenticatedAs($this->customer, 'customer');

        // Access protected page
        $response = $this->get('/customer');
        $response->assertStatus(200);
        $response->assertSee('Welcome, Test Customer');
        $response->assertSee('Dashboard');

        // Verify remember token was set
        $this->assertNotNull($this->customer->fresh()->remember_token);
    }

    /** @test */

    #[Test]
    public function customer_can_login_with_magic_link()
    {
        Mail::fake();

        // Visit login page
        $response = $this->get('/customer/login');
        $response->assertStatus(200);
        $response->assertSee('Or login with magic link');

        // Request magic link
        $response = $this->post('/customer/magic-link', [
            'email' => 'customer@example.com',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Magic link sent to your email address.',
        ]);

        // Verify email was sent
        Mail::assertQueued(MagicLinkEmail::class, function ($mail) {
            return $mail->hasTo('customer@example.com') &&
                   $mail->customer->id === $this->customer->id;
        });

        // Get magic link token from cache
        $token = Cache::get('magic_link:customer@example.com');
        $this->assertNotNull($token);

        // Visit expired magic link
        Carbon::setTestNow(now()->addMinutes(16)); // Links expire after 15 minutes
        
        $response = $this->get("/customer/magic-link/{$token}");
        $response->assertRedirect('/customer/login');
        $response->assertSessionHas('error', 'This magic link has expired.');

        Carbon::setTestNow(); // Reset time

        // Request new magic link
        $response = $this->post('/customer/magic-link', [
            'email' => 'customer@example.com',
        ]);

        $newToken = Cache::get('magic_link:customer@example.com');

        // Visit valid magic link
        $response = $this->get("/customer/magic-link/{$newToken}");
        $response->assertRedirect('/customer');
        $response->assertSessionHas('success', 'Login successful!');

        // Verify authentication
        $this->assertAuthenticatedAs($this->customer, 'customer');

        // Verify token is removed from cache
        $this->assertNull(Cache::get('magic_link:customer@example.com'));

        // Verify magic link can't be reused
        $response = $this->get("/customer/magic-link/{$newToken}");
        $response->assertRedirect('/customer/login');
        $response->assertSessionHas('error', 'Invalid or expired magic link.');
    }

    /** @test */

    #[Test]
    public function customer_can_reset_password()
    {
        Mail::fake();

        // Visit forgot password page
        $response = $this->get('/customer/forgot-password');
        $response->assertStatus(200);
        $response->assertSee('Reset Password');
        $response->assertSee('Enter your email address');

        // Request password reset with non-existent email
        $response = $this->post('/customer/forgot-password', [
            'email' => 'nonexistent@example.com',
        ]);

        $response->assertSessionHasErrors('email');

        // Request password reset with valid email
        $response = $this->post('/customer/forgot-password', [
            'email' => 'customer@example.com',
        ]);

        $response->assertRedirect('/customer/login');
        $response->assertSessionHas('status', 'Password reset link sent to your email.');

        // Verify email was sent
        Mail::assertQueued(PasswordResetEmail::class, function ($mail) {
            return $mail->hasTo('customer@example.com');
        });

        // Get reset token from database
        $tokenRecord = \DB::table('password_resets')
            ->where('email', 'customer@example.com')
            ->first();
        
        $this->assertNotNull($tokenRecord);

        // Visit reset form with invalid token
        $response = $this->get('/customer/reset-password/invalid-token');
        $response->assertRedirect('/customer/forgot-password');
        $response->assertSessionHas('error', 'Invalid reset token.');

        // Visit reset form with valid token
        $response = $this->get("/customer/reset-password/{$tokenRecord->token}");
        $response->assertStatus(200);
        $response->assertSee('Reset Password');
        $response->assertSee('New Password');
        $response->assertSee('Confirm Password');

        // Submit password reset with validation errors
        $response = $this->post('/customer/reset-password', [
            'token' => $tokenRecord->token,
            'email' => 'customer@example.com',
            'password' => 'short',
            'password_confirmation' => 'different',
        ]);

        $response->assertSessionHasErrors(['password']);

        // Submit valid password reset
        $response = $this->post('/customer/reset-password', [
            'token' => $tokenRecord->token,
            'email' => 'customer@example.com',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertRedirect('/customer/login');
        $response->assertSessionHas('status', 'Password reset successful. You can now login.');

        // Verify password was changed
        $this->assertTrue(Hash::check('newpassword123', $this->customer->fresh()->password));

        // Verify token was deleted
        $this->assertNull(\DB::table('password_resets')
            ->where('email', 'customer@example.com')
            ->first());

        // Login with new password
        $response = $this->post('/customer/login', [
            'email' => 'customer@example.com',
            'password' => 'newpassword123',
        ]);

        $response->assertRedirect('/customer');
        $this->assertAuthenticatedAs($this->customer, 'customer');
    }

    /** @test */

    #[Test]
    public function disabled_customer_cannot_login()
    {
        // Disable portal access
        $this->customer->update([
            'portal_access_enabled' => false,
        ]);

        // Attempt login
        $response = $this->post('/customer/login', [
            'email' => 'customer@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/customer/login');
        $response->assertSessionHas('error', 'Your portal access has been disabled. Please contact support.');
        $this->assertGuest('customer');
    }

    /** @test */

    #[Test]
    public function unverified_email_requires_verification()
    {
        // Create unverified customer
        $unverifiedCustomer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'email' => 'unverified@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => null,
            'portal_access_enabled' => true,
        ]);

        // Login
        $response = $this->post('/customer/login', [
            'email' => 'unverified@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/customer/email/verify');
        $this->assertAuthenticatedAs($unverifiedCustomer, 'customer');

        // Try to access protected route
        $response = $this->get('/customer');
        $response->assertRedirect('/customer/email/verify');

        // Request verification email
        Mail::fake();
        
        $response = $this->post('/customer/email/verification-notification');
        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Verification link sent!',
        ]);

        Mail::assertQueued(\App\Mail\VerifyEmail::class);

        // Verify email
        $verificationUrl = URL::temporarySignedRoute(
            'portal.verification.verify',
            now()->addMinutes(60),
            [
                'id' => $unverifiedCustomer->id,
                'hash' => sha1($unverifiedCustomer->email),
            ]
        );

        $response = $this->get($verificationUrl);
        $response->assertRedirect('/customer');
        $response->assertSessionHas('verified', true);

        // Verify email was marked as verified
        $this->assertNotNull($unverifiedCustomer->fresh()->email_verified_at);

        // Can now access protected routes
        $response = $this->get('/customer');
        $response->assertStatus(200);
    }

    /** @test */

    #[Test]
    public function login_throttling_works_correctly()
    {
        // Make 5 failed login attempts
        for ($i = 0; $i < 5; $i++) {
            $response = $this->post('/customer/login', [
                'email' => 'customer@example.com',
                'password' => 'wrongpassword',
            ]);
            
            $response->assertSessionHasErrors('email');
        }

        // 6th attempt should be throttled
        $response = $this->post('/customer/login', [
            'email' => 'customer@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(429); // Too Many Requests
        $response->assertJson([
            'message' => 'Too many login attempts. Please try again in 60 seconds.',
        ]);

        // Wait for throttle to expire
        Carbon::setTestNow(now()->addSeconds(61));

        // Should be able to login now
        $response = $this->post('/customer/login', [
            'email' => 'customer@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/customer');
        $this->assertAuthenticatedAs($this->customer, 'customer');
    }

    /** @test */

    #[Test]
    public function customer_can_logout()
    {
        // Login first
        $this->actingAs($this->customer, 'customer');

        // Access protected page
        $response = $this->get('/customer');
        $response->assertStatus(200);

        // Logout
        $response = $this->post('/customer/logout');
        $response->assertRedirect('/customer/login');
        $response->assertSessionHas('message', 'You have been logged out.');

        // Verify logged out
        $this->assertGuest('customer');

        // Cannot access protected page
        $response = $this->get('/customer');
        $response->assertRedirect('/customer/login');
    }

    /** @test */

    #[Test]
    public function session_timeout_redirects_to_login()
    {
        // Login
        $this->actingAs($this->customer, 'customer');

        // Access page
        $response = $this->get('/customer');
        $response->assertStatus(200);

        // Simulate session expiry
        session()->flush();

        // Try to access protected page
        $response = $this->get('/customer');
        $response->assertRedirect('/customer/login');
        $response->assertSessionHas('message', 'Your session has expired. Please login again.');
    }

    /** @test */

    #[Test]
    public function customer_portal_respects_company_settings()
    {
        // Disable customer portal for company
        $this->company->update([
            'settings' => array_merge($this->company->settings, [
                'customer_portal' => false,
            ]),
        ]);

        // Try to access login page
        $response = $this->get('/customer/login');
        $response->assertStatus(403);
        $response->assertSee('Customer portal is not enabled for this company.');

        // Enable portal but disable magic links
        $this->company->update([
            'settings' => array_merge($this->company->settings, [
                'customer_portal' => true,
                'allow_magic_link' => false,
            ]),
        ]);

        // Login page should not show magic link option
        $response = $this->get('/customer/login');
        $response->assertStatus(200);
        $response->assertDontSee('Or login with magic link');

        // Magic link endpoint should return error
        $response = $this->post('/customer/magic-link', [
            'email' => 'customer@example.com',
        ]);

        $response->assertStatus(403);
        $response->assertJson([
            'error' => 'Magic link login is not enabled.',
        ]);
    }

    /** @test */

    #[Test]
    public function multi_tenant_isolation_in_login()
    {
        // Create another company and customer
        $otherCompany = Company::factory()->create([
            'name' => 'Other Company',
            'settings' => ['customer_portal' => true],
        ]);

        $otherCustomer = Customer::factory()->create([
            'company_id' => $otherCompany->id,
            'email' => 'customer@example.com', // Same email as first customer
            'password' => Hash::make('otherpassword'),
            'portal_access_enabled' => true,
        ]);

        // Set subdomain context for first company
        $this->withServerVariables([
            'HTTP_HOST' => $this->company->slug . '.askproai.test',
        ]);

        // Login should authenticate against first company's customer
        $response = $this->post('/customer/login', [
            'email' => 'customer@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/customer');
        $this->assertAuthenticatedAs($this->customer, 'customer');
        $this->assertNotEquals($otherCustomer->id, auth('customer')->id());

        // Logout
        $this->post('/customer/logout');

        // Switch to other company's subdomain
        $this->withServerVariables([
            'HTTP_HOST' => $otherCompany->slug . '.askproai.test',
        ]);

        // Login with other company's customer credentials
        $response = $this->post('/customer/login', [
            'email' => 'customer@example.com',
            'password' => 'otherpassword',
        ]);

        $response->assertRedirect('/customer');
        $this->assertAuthenticatedAs($otherCustomer, 'customer');
    }
}