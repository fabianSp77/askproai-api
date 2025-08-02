<?php

namespace Tests\Security;

use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;

class CsrfProtectionTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $company;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->company = Company::factory()->create();
        $this->user = User::factory()->create(['company_id' => $this->company->id]);
    }

    /**
     * Test CSRF token requirement for state-changing operations.
     */
    public function test_csrf_token_requirement()
    {
        $this->actingAs($this->user);
        
        // Test POST without CSRF token
        $response = $this->post('/appointments', [
            'customer_id' => 1,
            'service_id' => 1,
            'starts_at' => now()->addDay()->toDateTimeString(),
        ]);
        
        $response->assertStatus(419); // Token mismatch
    }

    /**
     * Test CSRF token validation.
     */
    public function test_csrf_token_validation()
    {
        $this->actingAs($this->user);
        
        // Get valid CSRF token
        $response = $this->get('/dashboard');
        $token = $this->getCsrfToken($response);
        
        // Test with valid token
        $response = $this->post('/appointments', [
            '_token' => $token,
            'customer_id' => 1,
            'service_id' => 1,
            'starts_at' => now()->addDay()->toDateTimeString(),
        ]);
        
        $this->assertNotEquals(419, $response->status());
        
        // Test with invalid token
        $response = $this->post('/appointments', [
            '_token' => 'invalid-token',
            'customer_id' => 1,
            'service_id' => 1,
            'starts_at' => now()->addDay()->toDateTimeString(),
        ]);
        
        $response->assertStatus(419);
    }

    /**
     * Test CSRF token rotation.
     */
    public function test_csrf_token_rotation()
    {
        $this->actingAs($this->user);
        
        // Get first token
        $response1 = $this->get('/dashboard');
        $token1 = $this->getCsrfToken($response1);
        
        // Login as different user (should rotate token)
        $user2 = User::factory()->create(['company_id' => $this->company->id]);
        $this->actingAs($user2);
        
        // Get second token
        $response2 = $this->get('/dashboard');
        $token2 = $this->getCsrfToken($response2);
        
        // Tokens should be different
        $this->assertNotEquals($token1, $token2);
    }

    /**
     * Test CSRF protection on AJAX requests.
     */
    public function test_csrf_protection_ajax_requests()
    {
        $this->actingAs($this->user);
        
        // Get CSRF token
        $response = $this->get('/dashboard');
        $token = $this->getCsrfToken($response);
        
        // Test AJAX with token in header
        $response = $this->postJson('/api/ajax/customers', [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
        ], [
            'X-CSRF-TOKEN' => $token,
            'X-Requested-With' => 'XMLHttpRequest',
        ]);
        
        $this->assertNotEquals(419, $response->status());
        
        // Test AJAX without token
        $response = $this->postJson('/api/ajax/customers', [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
        ], [
            'X-Requested-With' => 'XMLHttpRequest',
        ]);
        
        $response->assertStatus(419);
    }

    /**
     * Test double submit cookie pattern.
     */
    public function test_double_submit_cookie_pattern()
    {
        $this->actingAs($this->user);
        
        // Get page with CSRF cookie
        $response = $this->get('/dashboard');
        
        $cookies = $response->headers->getCookies();
        $csrfCookie = null;
        
        foreach ($cookies as $cookie) {
            if ($cookie->getName() === 'XSRF-TOKEN') {
                $csrfCookie = $cookie->getValue();
                break;
            }
        }
        
        $this->assertNotNull($csrfCookie);
        
        // Submit with matching header
        $response = $this->postJson('/api/ajax/customers', [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
        ], [
            'X-XSRF-TOKEN' => $csrfCookie,
            'X-Requested-With' => 'XMLHttpRequest',
        ]);
        
        $this->assertNotEquals(419, $response->status());
    }

    /**
     * Test CSRF token timeout.
     */
    public function test_csrf_token_timeout()
    {
        $this->actingAs($this->user);
        
        // Get CSRF token
        $response = $this->get('/dashboard');
        $token = $this->getCsrfToken($response);
        
        // Simulate session timeout
        Session::flush();
        
        // Try to use expired token
        $response = $this->post('/appointments', [
            '_token' => $token,
            'customer_id' => 1,
            'service_id' => 1,
            'starts_at' => now()->addDay()->toDateTimeString(),
        ]);
        
        $response->assertStatus(419);
    }

    /**
     * Test CSRF exemption for webhooks.
     */
    public function test_csrf_exemption_for_webhooks()
    {
        // Webhooks should be exempt from CSRF
        $response = $this->postJson('/api/retell/webhook', [
            'event' => 'call.ended',
            'call_id' => '12345',
        ], [
            'x-retell-signature' => 'valid-signature',
        ]);
        
        // Should not get CSRF error
        $this->assertNotEquals(419, $response->status());
    }

    /**
     * Test same-site cookie attribute.
     */
    public function test_same_site_cookie_attribute()
    {
        $response = $this->get('/dashboard');
        
        $cookies = $response->headers->getCookies();
        
        foreach ($cookies as $cookie) {
            if (in_array($cookie->getName(), ['XSRF-TOKEN', 'laravel_session'])) {
                // Should have SameSite attribute
                $sameSite = $cookie->getSameSite();
                $this->assertNotNull($sameSite);
                $this->assertContains(strtolower($sameSite), ['lax', 'strict']);
            }
        }
    }

    /**
     * Test CSRF protection on file uploads.
     */
    public function test_csrf_protection_file_uploads()
    {
        $this->actingAs($this->user);
        
        $file = \Illuminate\Http\Testing\File::fake()->image('avatar.jpg');
        
        // Without CSRF token
        $response = $this->post('/upload', [
            'file' => $file,
        ]);
        
        $response->assertStatus(419);
        
        // With CSRF token
        $response = $this->get('/dashboard');
        $token = $this->getCsrfToken($response);
        
        $response = $this->post('/upload', [
            '_token' => $token,
            'file' => $file,
        ]);
        
        $this->assertNotEquals(419, $response->status());
    }

    /**
     * Test CSRF token in meta tag.
     */
    public function test_csrf_token_in_meta_tag()
    {
        $this->actingAs($this->user);
        
        $response = $this->get('/dashboard');
        
        $response->assertSee('name="csrf-token"', false);
        $response->assertSee('content="', false);
        
        // Extract token from meta tag
        preg_match('/<meta name="csrf-token" content="([^"]+)"/', $response->content(), $matches);
        
        $this->assertCount(2, $matches);
        $this->assertNotEmpty($matches[1]);
    }

    /**
     * Test referrer validation.
     */
    public function test_referrer_validation()
    {
        $this->actingAs($this->user);
        
        $response = $this->get('/dashboard');
        $token = $this->getCsrfToken($response);
        
        // Test with valid referrer
        $response = $this->post('/appointments', [
            '_token' => $token,
            'customer_id' => 1,
            'service_id' => 1,
            'starts_at' => now()->addDay()->toDateTimeString(),
        ], [
            'HTTP_REFERER' => config('app.url') . '/dashboard',
        ]);
        
        $this->assertNotEquals(419, $response->status());
        
        // Test with suspicious referrer
        $response = $this->post('/appointments', [
            '_token' => $token,
            'customer_id' => 1,
            'service_id' => 1,
            'starts_at' => now()->addDay()->toDateTimeString(),
        ], [
            'HTTP_REFERER' => 'https://evil-site.com',
        ]);
        
        // Should still work with valid token, but logged as suspicious
        $this->assertNotEquals(419, $response->status());
    }

    /**
     * Test origin validation for CORS requests.
     */
    public function test_origin_validation_cors()
    {
        $this->actingAs($this->user);
        
        $response = $this->get('/dashboard');
        $token = $this->getCsrfToken($response);
        
        // Test with allowed origin
        $response = $this->postJson('/api/ajax/customers', [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
        ], [
            'X-CSRF-TOKEN' => $token,
            'X-Requested-With' => 'XMLHttpRequest',
            'Origin' => config('app.url'),
        ]);
        
        $this->assertNotEquals(419, $response->status());
        
        // Test with disallowed origin
        $response = $this->postJson('/api/ajax/customers', [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
        ], [
            'X-CSRF-TOKEN' => $token,
            'X-Requested-With' => 'XMLHttpRequest',
            'Origin' => 'https://malicious-site.com',
        ]);
        
        // Should be rejected by CORS, not CSRF
        $this->assertContains($response->status(), [403, 419]);
    }

    /**
     * Test token binding to session.
     */
    public function test_token_binding_to_session()
    {
        $user1 = $this->user;
        $user2 = User::factory()->create(['company_id' => $this->company->id]);
        
        // Get token as user 1
        $this->actingAs($user1);
        $response = $this->get('/dashboard');
        $token1 = $this->getCsrfToken($response);
        
        // Try to use user 1's token as user 2
        $this->actingAs($user2);
        
        $response = $this->post('/appointments', [
            '_token' => $token1,
            'customer_id' => 1,
            'service_id' => 1,
            'starts_at' => now()->addDay()->toDateTimeString(),
        ]);
        
        // Should fail - token is bound to different session
        $response->assertStatus(419);
    }

    /**
     * Helper method to extract CSRF token from response.
     */
    protected function getCsrfToken($response)
    {
        preg_match('/<meta name="csrf-token" content="([^"]+)"/', $response->content(), $matches);
        
        if (isset($matches[1])) {
            return $matches[1];
        }
        
        preg_match('/_token["\']?\s*[:=]\s*["\']([^"\']+)["\']/', $response->content(), $matches);
        
        return $matches[1] ?? null;
    }
}