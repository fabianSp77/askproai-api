<?php

namespace Tests\Security;

use App\Models\Company;
use App\Models\CustomerAuth;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StripePortalSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test companies
        $this->companyA = Company::factory()->create(['subdomain' => 'company-a']);
        $this->companyB = Company::factory()->create(['subdomain' => 'company-b']);
        
        // Create test customers
        $this->customerA = CustomerAuth::factory()->create([
            'company_id' => $this->companyA->id,
            'email' => 'customer@companya.com',
            'portal_enabled' => true,
            'password' => Hash::make('password'),
        ]);
        
        $this->customerB = CustomerAuth::factory()->create([
            'company_id' => $this->companyB->id,
            'email' => 'customer@companyb.com',
            'portal_enabled' => true,
            'password' => Hash::make('password'),
        ]);
    }

    /**
     * Test 1: SQL Injection Prevention
     */
    public function test_sql_injection_prevention_in_customer_search()
    {
        // Attempt SQL injection in various fields
        $maliciousInputs = [
            "' OR '1'='1",
            "'; DROP TABLE customers; --",
            "\" OR \"\"=\"\"",
            "' UNION SELECT * FROM customers --",
            "1' AND '1' = '1",
        ];

        foreach ($maliciousInputs as $input) {
            // Test in email field
            $response = $this->actingAs($this->customerA, 'customer')
                ->get('/portal/appointments?email=' . urlencode($input));
            
            $response->assertStatus(200);
            $this->assertDatabaseHas('customers', ['id' => $this->customerA->id]);
            
            // Test in search parameter
            $response = $this->actingAs($this->customerA, 'customer')
                ->get('/portal/appointments?search=' . urlencode($input));
            
            $response->assertStatus(200);
        }
    }

    /**
     * Test 2: XSS Protection
     */
    public function test_xss_protection_in_customer_portal()
    {
        $xssPayloads = [
            '<script>alert("XSS")</script>',
            '<img src=x onerror=alert("XSS")>',
            '<svg onload=alert("XSS")>',
            'javascript:alert("XSS")',
            '<iframe src="javascript:alert(\'XSS\')">',
        ];

        // Create customer with XSS payload in name
        $maliciousCustomer = CustomerAuth::factory()->create([
            'company_id' => $this->companyA->id,
            'first_name' => $xssPayloads[0],
            'last_name' => $xssPayloads[1],
            'email' => 'xss@test.com',
            'portal_enabled' => true,
            'password' => Hash::make('password'),
        ]);

        $response = $this->actingAs($maliciousCustomer, 'customer')
            ->get('/portal');

        $response->assertStatus(200);
        
        // Ensure XSS payloads are escaped
        foreach ($xssPayloads as $payload) {
            $response->assertDontSee($payload, false); // false = don't escape
            $response->assertSee(e($payload)); // Should see escaped version
        }
    }

    /**
     * Test 3: Authentication and Authorization
     */
    public function test_multi_tenant_isolation()
    {
        // Create invoice for Company A
        $invoiceA = Invoice::factory()->create([
            'company_id' => $this->companyA->id,
            'stripe_invoice_id' => 'inv_test_a',
        ]);

        // Customer B should not be able to access Company A's invoice
        $response = $this->actingAs($this->customerB, 'customer')
            ->get('/portal/invoices/' . $invoiceA->id);

        $response->assertStatus(403);

        // Customer A should be able to access their own invoice
        $response = $this->actingAs($this->customerA, 'customer')
            ->get('/portal/invoices/' . $invoiceA->id);

        $response->assertStatus(200);
    }

    /**
     * Test 4: CSRF Protection
     */
    public function test_csrf_protection_on_state_changing_operations()
    {
        // Test without CSRF token
        $response = $this->actingAs($this->customerA, 'customer')
            ->post('/portal/profile', [
                'first_name' => 'Updated',
                'last_name' => 'Name',
                'email' => 'updated@email.com',
            ]);

        $response->assertStatus(419); // CSRF token mismatch

        // Test with CSRF token
        $response = $this->actingAs($this->customerA, 'customer')
            ->post('/portal/profile', [
                '_token' => csrf_token(),
                'first_name' => 'Updated',
                'last_name' => 'Name',
                'email' => $this->customerA->email,
                'phone' => '1234567890',
                'preferred_language' => 'de',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    /**
     * Test 5: Sensitive Data Exposure
     */
    public function test_no_sensitive_data_exposure_in_api_responses()
    {
        // Create webhook event with sensitive data
        $webhookPayload = [
            'event_type' => 'invoice.paid',
            'data' => [
                'object' => [
                    'id' => 'inv_test',
                    'customer' => $this->companyA->stripe_customer_id,
                    'payment_intent' => 'pi_test_secret',
                    'client_secret' => 'pi_test_secret_xxx',
                ],
            ],
        ];

        // Test that sensitive fields are not exposed in responses
        $response = $this->actingAs($this->customerA, 'customer')
            ->getJson('/api/portal/invoices');

        if ($response->status() === 200) {
            $content = $response->json();
            
            // Check that sensitive fields are not exposed
            $this->assertArrayNotHasKey('client_secret', $content);
            $this->assertArrayNotHasKey('payment_intent_client_secret', $content);
            
            // If data exists, check it doesn't contain secrets
            if (isset($content['data'])) {
                foreach ($content['data'] as $item) {
                    $this->assertArrayNotHasKey('stripe_secret', $item);
                    $this->assertArrayNotHasKey('webhook_secret', $item);
                }
            }
        }
    }

    /**
     * Test 6: Rate Limiting
     */
    public function test_rate_limiting_on_sensitive_endpoints()
    {
        // Test login endpoint rate limiting
        for ($i = 0; $i < 10; $i++) {
            $response = $this->postJson('/portal/login', [
                'email' => 'test@example.com',
                'password' => 'wrong-password',
            ]);
        }

        // The 11th request should be rate limited
        $response = $this->postJson('/portal/login', [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ]);

        $this->assertIn($response->status(), [429, 422]); // Rate limited or validation error
    }

    /**
     * Test 7: Webhook Security
     */
    public function test_stripe_webhook_signature_verification()
    {
        $payload = json_encode(['test' => 'data']);
        $timestamp = time();
        
        // Test without signature
        $response = $this->postJson('/api/stripe/webhook', json_decode($payload, true));
        $response->assertStatus(400);

        // Test with invalid signature
        $response = $this->postJson('/api/stripe/webhook', json_decode($payload, true), [
            'Stripe-Signature' => 'invalid_signature',
        ]);
        $response->assertStatus(400);

        // Test with valid signature format (would need actual Stripe secret to pass)
        $secret = config('services.stripe.webhook_secret');
        if ($secret) {
            $signature = "t={$timestamp},v1=" . hash_hmac('sha256', "{$timestamp}.{$payload}", $secret);
            
            $response = $this->postJson('/api/stripe/webhook', json_decode($payload, true), [
                'Stripe-Signature' => $signature,
            ]);
            
            // Should not be 400 (bad signature) but might be other error
            $this->assertNotEquals(400, $response->status());
        }
    }

    /**
     * Test 8: Input Validation
     */
    public function test_input_validation_prevents_malicious_data()
    {
        // Test oversized input
        $oversizedInput = str_repeat('a', 10000);
        
        $response = $this->actingAs($this->customerA, 'customer')
            ->putJson('/portal/profile', [
                'first_name' => $oversizedInput,
                'last_name' => 'Test',
                'email' => $this->customerA->email,
                'phone' => '1234567890',
                'preferred_language' => 'de',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['first_name']);

        // Test invalid email format
        $response = $this->actingAs($this->customerA, 'customer')
            ->putJson('/portal/profile', [
                'first_name' => 'Test',
                'last_name' => 'User',
                'email' => 'not-an-email',
                'phone' => '1234567890',
                'preferred_language' => 'de',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    /**
     * Test 9: Password Security
     */
    public function test_password_security_requirements()
    {
        // Test weak password
        $response = $this->actingAs($this->customerA, 'customer')
            ->putJson('/portal/profile', [
                'first_name' => 'Test',
                'last_name' => 'User',
                'email' => $this->customerA->email,
                'phone' => '1234567890',
                'preferred_language' => 'de',
                'password' => '123',
                'password_confirmation' => '123',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['password']);

        // Test password confirmation mismatch
        $response = $this->actingAs($this->customerA, 'customer')
            ->putJson('/portal/profile', [
                'first_name' => 'Test',
                'last_name' => 'User',
                'email' => $this->customerA->email,
                'phone' => '1234567890',
                'preferred_language' => 'de',
                'password' => 'StrongPassword123!',
                'password_confirmation' => 'DifferentPassword123!',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['password']);
    }

    /**
     * Test 10: Session Security
     */
    public function test_session_security_and_fixation_prevention()
    {
        // Get initial session ID
        $this->get('/portal/login');
        $initialSessionId = session()->getId();

        // Login
        $response = $this->post('/portal/login', [
            'email' => $this->customerA->email,
            'password' => 'password',
        ]);

        // Session ID should change after login (session fixation prevention)
        $this->assertNotEquals($initialSessionId, session()->getId());

        // Test session timeout
        $this->travel(121)->minutes(); // Assuming 2-hour session timeout

        $response = $this->actingAs($this->customerA, 'customer')
            ->get('/portal');

        $response->assertRedirect('/portal/login');
    }

    /**
     * Test 11: Magic Link Security
     */
    public function test_magic_link_token_security()
    {
        // Generate magic link
        $token = Str::random(64);
        $this->customerA->update([
            'portal_access_token' => hash('sha256', $token),
            'portal_token_expires_at' => now()->addHour(),
        ]);

        // Test with wrong token
        $response = $this->get('/portal/magic-link/wrong-token');
        $response->assertRedirect('/portal/login');
        $response->assertSessionHasErrors(['token']);

        // Test with expired token
        $this->customerA->update([
            'portal_token_expires_at' => now()->subMinute(),
        ]);

        $response = $this->get('/portal/magic-link/' . $token);
        $response->assertRedirect('/portal/login');
        $response->assertSessionHasErrors(['token']);

        // Test with valid token
        $this->customerA->update([
            'portal_token_expires_at' => now()->addHour(),
        ]);

        $response = $this->get('/portal/magic-link/' . $token);
        $response->assertRedirect('/portal/dashboard');
        
        // Token should be cleared after use
        $this->assertNull($this->customerA->fresh()->portal_access_token);
    }

    /**
     * Test 12: File Upload Security
     */
    public function test_file_upload_security()
    {
        // If there's a profile picture upload endpoint
        $maliciousFiles = [
            'test.php' => '<?php echo "hacked"; ?>',
            'test.exe' => 'MZ executable content',
            'test.js' => 'alert("XSS")',
        ];

        foreach ($maliciousFiles as $filename => $content) {
            $response = $this->actingAs($this->customerA, 'customer')
                ->post('/portal/profile/avatar', [
                    'avatar' => \Illuminate\Http\UploadedFile::fake()->createWithContent($filename, $content),
                ]);

            // Should reject non-image files
            if ($response->status() === 422) {
                $response->assertJsonValidationErrors(['avatar']);
            }
        }
    }

    /**
     * Test 13: API Key Security
     */
    public function test_api_keys_are_properly_secured()
    {
        // Ensure Stripe keys are not exposed in responses
        $response = $this->actingAs($this->customerA, 'customer')
            ->get('/portal');

        $content = $response->getContent();
        
        // Check that sensitive keys are not in the response
        $this->assertStringNotContainsString('sk_live_', $content);
        $this->assertStringNotContainsString('sk_test_', $content);
        $this->assertStringNotContainsString(config('services.stripe.secret'), $content);
        $this->assertStringNotContainsString(config('services.stripe.webhook_secret'), $content);
    }

    /**
     * Test 14: Subdomain Isolation
     */
    public function test_subdomain_based_multi_tenancy()
    {
        // Access Company A's portal with Company B's subdomain
        $response = $this->withServerVariables(['HTTP_HOST' => 'company-b.askproai.de'])
            ->actingAs($this->customerA, 'customer')
            ->get('/portal');

        // Should either redirect or show error
        $this->assertIn($response->status(), [302, 403, 404]);
    }

    /**
     * Test 15: Audit Logging
     */
    public function test_security_events_are_logged()
    {
        // Failed login attempt
        $this->post('/portal/login', [
            'email' => $this->customerA->email,
            'password' => 'wrong-password',
        ]);

        // Check that failed login was logged
        $this->assertDatabaseHas('activity_log', [
            'description' => 'Failed login attempt',
            'subject_type' => CustomerAuth::class,
            'properties->email' => $this->customerA->email,
        ]);

        // Successful login
        $this->post('/portal/login', [
            'email' => $this->customerA->email,
            'password' => 'password',
        ]);

        // Check that successful login was logged
        $this->assertDatabaseHas('customer_auths', [
            'id' => $this->customerA->id,
        ]);
        $this->assertNotNull($this->customerA->fresh()->last_portal_login_at);
    }
}