<?php

namespace Tests\Integration\Stripe;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\URL;
use App\Models\Customer;
use App\Models\Company;
use App\Services\CustomerPortalService;
use App\Notifications\CustomerMagicLinkNotification;
use App\Notifications\CustomerVerifyEmailNotification;
use Carbon\Carbon;

class CustomerPortalAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected CustomerPortalService $portalService;
    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->portalService = app(CustomerPortalService::class);
        
        // Create test company
        $this->company = Company::factory()->create([
            'portal_enabled' => true,
            'portal_settings' => [
                'allow_invoice_download' => true,
                'allow_payment_method_management' => true,
                'allow_subscription_management' => true,
                'require_email_verification' => true,
            ],
        ]);
    }

    /** @test */
    public function it_handles_complete_magic_link_authentication_flow()
    {
        Mail::fake();

        // Step 1: Customer requests magic link
        $customer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'email' => 'customer@example.com',
            'email_verified_at' => null,
        ]);

        $response = $this->postJson('/api/portal/auth/magic-link', [
            'email' => 'customer@example.com',
            'company_slug' => $this->company->slug,
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'message' => 'Magic link sent to your email',
            'requires_verification' => true,
        ]);

        // Verify magic link was sent
        Mail::assertSent(CustomerMagicLinkNotification::class, function ($notification) use ($customer) {
            return $notification->customer->id === $customer->id;
        });

        // Step 2: Extract token from notification
        $notification = new CustomerMagicLinkNotification($customer);
        $mailData = $notification->toMail($customer)->toArray();
        $magicLink = $mailData['actionUrl'];
        
        // Extract token from URL
        $urlParts = parse_url($magicLink);
        parse_str($urlParts['query'], $queryParams);
        $token = $queryParams['token'];

        // Verify token is stored in cache
        $cachedData = Cache::get('portal_auth_' . $token);
        $this->assertNotNull($cachedData);
        $this->assertEquals($customer->id, $cachedData['customer_id']);
        $this->assertEquals($this->company->id, $cachedData['company_id']);

        // Step 3: Verify magic link
        $response = $this->postJson('/api/portal/auth/verify-magic-link', [
            'token' => $token,
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'requires_email_verification' => true,
        ]);
        $response->assertJsonStructure([
            'access_token',
            'customer' => ['id', 'email', 'name'],
        ]);

        $accessToken = $response->json('access_token');

        // Step 4: Access protected portal endpoint
        $response = $this->withHeader('Authorization', 'Bearer ' . $accessToken)
            ->getJson('/api/portal/dashboard');

        $response->assertOk();
        $response->assertJson([
            'email_verification_required' => true,
        ]);

        // Step 5: Verify email
        Mail::fake(); // Reset mail fake
        
        $response = $this->withHeader('Authorization', 'Bearer ' . $accessToken)
            ->postJson('/api/portal/auth/resend-verification');

        $response->assertOk();

        Mail::assertSent(CustomerVerifyEmailNotification::class, function ($notification) use ($customer) {
            return $notification->customer->id === $customer->id;
        });

        // Extract verification URL
        $verificationNotification = new CustomerVerifyEmailNotification($customer);
        $verificationUrl = URL::temporarySignedRoute(
            'portal.verify-email',
            Carbon::now()->addMinutes(60),
            ['customer' => $customer->id]
        );

        // Verify email
        $response = $this->get($verificationUrl);
        $response->assertRedirect('/portal/dashboard');

        // Verify customer email is now verified
        $this->assertNotNull($customer->fresh()->email_verified_at);

        // Step 6: Access portal without restrictions
        $response = $this->withHeader('Authorization', 'Bearer ' . $accessToken)
            ->getJson('/api/portal/dashboard');

        $response->assertOk();
        $response->assertJsonMissing(['email_verification_required']);
        $response->assertJsonStructure([
            'customer',
            'recent_invoices',
            'subscription',
            'payment_methods',
            'upcoming_appointments',
        ]);
    }

    /** @test */
    public function it_handles_magic_link_expiration()
    {
        $customer = Customer::factory()->create([
            'company_id' => $this->company->id,
        ]);

        // Create expired token
        $token = $this->portalService->generateMagicLinkToken($customer);
        
        // Manually expire the cache
        Cache::forget('portal_auth_' . $token);

        $response = $this->postJson('/api/portal/auth/verify-magic-link', [
            'token' => $token,
        ]);

        $response->assertUnprocessable();
        $response->assertJson([
            'success' => false,
            'message' => 'Invalid or expired magic link',
        ]);
    }

    /** @test */
    public function it_prevents_cross_company_authentication()
    {
        Mail::fake();

        $otherCompany = Company::factory()->create([
            'portal_enabled' => true,
        ]);

        $customer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'email' => 'shared@example.com',
        ]);

        $otherCustomer = Customer::factory()->create([
            'company_id' => $otherCompany->id,
            'email' => 'shared@example.com',
        ]);

        // Request magic link for first company
        $response = $this->postJson('/api/portal/auth/magic-link', [
            'email' => 'shared@example.com',
            'company_slug' => $this->company->slug,
        ]);

        $response->assertOk();

        // Try to use same email for different company
        $response = $this->postJson('/api/portal/auth/magic-link', [
            'email' => 'shared@example.com',
            'company_slug' => $otherCompany->slug,
        ]);

        $response->assertOk();

        // Verify separate magic links were sent
        Mail::assertSent(CustomerMagicLinkNotification::class, 2);
        
        // Each customer should only access their company's data
        $notification1 = new CustomerMagicLinkNotification($customer);
        $token1 = $this->extractTokenFromNotification($notification1);

        $response = $this->postJson('/api/portal/auth/verify-magic-link', [
            'token' => $token1,
        ]);

        $response->assertOk();
        $accessToken1 = $response->json('access_token');

        // Verify can only access own company data
        $response = $this->withHeader('Authorization', 'Bearer ' . $accessToken1)
            ->getJson('/api/portal/invoices');

        $response->assertOk();
        $invoices = $response->json('data');
        
        foreach ($invoices as $invoice) {
            $this->assertEquals($this->company->id, $invoice['company_id']);
        }
    }

    /** @test */
    public function it_handles_rate_limiting_for_magic_link_requests()
    {
        $customer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'email' => 'rate@limited.com',
        ]);

        // Make 5 requests (assuming rate limit is 5 per minute)
        for ($i = 0; $i < 5; $i++) {
            $response = $this->postJson('/api/portal/auth/magic-link', [
                'email' => 'rate@limited.com',
                'company_slug' => $this->company->slug,
            ]);
            $response->assertOk();
        }

        // 6th request should be rate limited
        $response = $this->postJson('/api/portal/auth/magic-link', [
            'email' => 'rate@limited.com',
            'company_slug' => $this->company->slug,
        ]);

        $response->assertStatus(429); // Too Many Requests
        $response->assertJson([
            'message' => 'Too many requests. Please try again later.',
        ]);
    }

    /** @test */
    public function it_handles_customer_portal_permissions()
    {
        $customer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'email_verified_at' => now(),
        ]);

        $accessToken = $this->portalService->createAccessToken($customer);

        // Test various permission scenarios
        $permissionTests = [
            [
                'setting' => 'allow_invoice_download',
                'endpoint' => '/api/portal/invoices/123/download',
                'method' => 'get',
            ],
            [
                'setting' => 'allow_payment_method_management',
                'endpoint' => '/api/portal/payment-methods',
                'method' => 'post',
            ],
            [
                'setting' => 'allow_subscription_management',
                'endpoint' => '/api/portal/subscription/cancel',
                'method' => 'post',
            ],
        ];

        foreach ($permissionTests as $test) {
            // Disable permission
            $settings = $this->company->portal_settings;
            $settings[$test['setting']] = false;
            $this->company->update(['portal_settings' => $settings]);

            $response = $this->withHeader('Authorization', 'Bearer ' . $accessToken)
                ->{$test['method']}Json($test['endpoint']);

            $response->assertForbidden();
            $response->assertJson([
                'message' => 'This feature is not enabled for your account',
            ]);

            // Enable permission
            $settings[$test['setting']] = true;
            $this->company->update(['portal_settings' => $settings]);

            // Should now have access (would normally return 404 for non-existent resources)
            $response = $this->withHeader('Authorization', 'Bearer ' . $accessToken)
                ->{$test['method']}Json($test['endpoint']);

            $this->assertNotEquals(403, $response->status());
        }
    }

    /** @test */
    public function it_handles_portal_disabled_for_company()
    {
        $this->company->update(['portal_enabled' => false]);

        $customer = Customer::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $response = $this->postJson('/api/portal/auth/magic-link', [
            'email' => $customer->email,
            'company_slug' => $this->company->slug,
        ]);

        $response->assertForbidden();
        $response->assertJson([
            'success' => false,
            'message' => 'Customer portal is not enabled for this company',
        ]);
    }

    /** @test */
    public function it_tracks_customer_portal_activity()
    {
        $customer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'email_verified_at' => now(),
        ]);

        $accessToken = $this->portalService->createAccessToken($customer);

        // Make several portal requests
        $endpoints = [
            '/api/portal/dashboard',
            '/api/portal/invoices',
            '/api/portal/appointments',
        ];

        foreach ($endpoints as $endpoint) {
            $this->withHeader('Authorization', 'Bearer ' . $accessToken)
                ->getJson($endpoint)
                ->assertOk();
        }

        // Verify activity was tracked
        $customer = $customer->fresh();
        $this->assertNotNull($customer->last_portal_activity);
        $this->assertGreaterThanOrEqual(3, $customer->portal_access_count);

        // Check activity log
        $activities = $customer->activities()
            ->where('event', 'portal_access')
            ->get();
        
        $this->assertCount(3, $activities);
        foreach ($activities as $activity) {
            $this->assertArrayHasKey('endpoint', $activity->properties);
            $this->assertArrayHasKey('ip_address', $activity->properties);
            $this->assertArrayHasKey('user_agent', $activity->properties);
        }
    }

    /** @test */
    public function it_handles_customer_session_timeout()
    {
        $customer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'email_verified_at' => now(),
        ]);

        // Create token with short TTL for testing
        $shortTtlToken = $this->portalService->createAccessToken($customer, 1); // 1 minute

        // Initial request should work
        $response = $this->withHeader('Authorization', 'Bearer ' . $shortTtlToken)
            ->getJson('/api/portal/dashboard');
        $response->assertOk();

        // Simulate time passing
        Carbon::setTestNow(Carbon::now()->addMinutes(2));

        // Request after timeout should fail
        $response = $this->withHeader('Authorization', 'Bearer ' . $shortTtlToken)
            ->getJson('/api/portal/dashboard');
        
        $response->assertUnauthorized();
        $response->assertJson([
            'message' => 'Session expired. Please login again.',
        ]);

        Carbon::setTestNow(); // Reset time
    }

    /** @test */
    public function it_handles_customer_data_export_request()
    {
        $customer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'email_verified_at' => now(),
        ]);

        $accessToken = $this->portalService->createAccessToken($customer);

        $response = $this->withHeader('Authorization', 'Bearer ' . $accessToken)
            ->postJson('/api/portal/data-export');

        $response->assertAccepted(); // 202
        $response->assertJson([
            'success' => true,
            'message' => 'Your data export request has been received. You will receive an email when it\'s ready.',
        ]);

        // Verify export job was queued
        $this->assertDatabaseHas('customer_data_exports', [
            'customer_id' => $customer->id,
            'status' => 'pending',
        ]);
    }

    protected function extractTokenFromNotification($notification)
    {
        $mailData = $notification->toMail($notification->customer)->toArray();
        $magicLink = $mailData['actionUrl'];
        $urlParts = parse_url($magicLink);
        parse_str($urlParts['query'], $queryParams);
        return $queryParams['token'];
    }
}