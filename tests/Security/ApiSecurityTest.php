<?php

namespace Tests\Security;

use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Sanctum\Sanctum;

class ApiSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $company;
    protected $branch;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->company = Company::factory()->create();
        $this->branch = Branch::factory()->create(['company_id' => $this->company->id]);
        $this->user = User::factory()->create(['company_id' => $this->company->id]);
        
        // Clear rate limiter
        RateLimiter::clear('api');
    }

    /**
     * Test API rate limiting.
     */
    public function test_api_rate_limiting()
    {
        Sanctum::actingAs($this->user);
        
        // Make requests up to the limit
        for ($i = 0; $i < 60; $i++) {
            $response = $this->getJson('/api/dashboard');
            $response->assertStatus(200);
        }
        
        // Next request should be rate limited
        $response = $this->getJson('/api/dashboard');
        $response->assertStatus(429)
            ->assertHeader('X-RateLimit-Limit', '60')
            ->assertHeader('X-RateLimit-Remaining', '0')
            ->assertJsonStructure(['message']);
    }

    /**
     * Test API authentication requirement.
     */
    public function test_api_authentication_requirement()
    {
        // Test endpoints that require authentication
        $protectedEndpoints = [
            ['GET', '/api/dashboard'],
            ['GET', '/api/appointments'],
            ['POST', '/api/appointments'],
            ['GET', '/api/customers'],
            ['POST', '/api/customers'],
            ['GET', '/api/calls'],
            ['GET', '/api/settings'],
            ['PUT', '/api/settings'],
        ];
        
        foreach ($protectedEndpoints as [$method, $endpoint]) {
            $response = $this->json($method, $endpoint);
            
            $response->assertStatus(401)
                ->assertJson(['message' => 'Unauthenticated.']);
        }
    }

    /**
     * Test API authorization (company isolation).
     */
    public function test_api_authorization_company_isolation()
    {
        // Create another company with data
        $otherCompany = Company::factory()->create();
        $otherCustomer = Customer::factory()->create(['company_id' => $otherCompany->id]);
        
        // Create customer for our company
        $ourCustomer = Customer::factory()->create(['company_id' => $this->company->id]);
        
        Sanctum::actingAs($this->user);
        
        // Should not be able to access other company's customer
        $response = $this->getJson("/api/customers/{$otherCustomer->id}");
        $response->assertStatus(404);
        
        // Should be able to access own company's customer
        $response = $this->getJson("/api/customers/{$ourCustomer->id}");
        $response->assertStatus(200);
        
        // List should only show own company's customers
        $response = $this->getJson('/api/customers');
        $response->assertStatus(200);
        
        $customerIds = collect($response->json('data.customers'))->pluck('id');
        $this->assertContains($ourCustomer->id, $customerIds);
        $this->assertNotContains($otherCustomer->id, $customerIds);
    }

    /**
     * Test CORS headers.
     */
    public function test_cors_headers()
    {
        $allowedOrigin = config('cors.allowed_origins')[0] ?? '*';
        
        $response = $this->withHeaders([
            'Origin' => 'https://example.com',
            'Access-Control-Request-Method' => 'POST',
            'Access-Control-Request-Headers' => 'Content-Type',
        ])->options('/api/login');
        
        $response->assertStatus(200)
            ->assertHeader('Access-Control-Allow-Origin')
            ->assertHeader('Access-Control-Allow-Methods')
            ->assertHeader('Access-Control-Allow-Headers');
    }

    /**
     * Test API versioning.
     */
    public function test_api_versioning()
    {
        Sanctum::actingAs($this->user);
        
        // Test v1 endpoint
        $response = $this->getJson('/api/v1/dashboard');
        $response->assertStatus(200);
        
        // Test versioning via header
        $response = $this->withHeaders(['X-API-Version' => '1'])
            ->getJson('/api/dashboard');
        $response->assertStatus(200);
        
        // Test unsupported version
        $response = $this->withHeaders(['X-API-Version' => '999'])
            ->getJson('/api/dashboard');
        $response->assertStatus(400)
            ->assertJson(['message' => 'Unsupported API version']);
    }

    /**
     * Test API response structure consistency.
     */
    public function test_api_response_structure_consistency()
    {
        Sanctum::actingAs($this->user);
        
        // Success response
        $response = $this->getJson('/api/dashboard');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
                'message',
            ]);
        
        // Error response
        $response = $this->getJson('/api/customers/99999');
        $response->assertStatus(404)
            ->assertJsonStructure([
                'success',
                'message',
                'errors',
            ]);
        
        // Validation error response
        $response = $this->postJson('/api/customers', []);
        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors' => [],
            ]);
    }

    /**
     * Test API token abilities/scopes.
     */
    public function test_api_token_abilities()
    {
        // Create token with limited abilities
        $token = $this->user->createToken('limited-token', ['customers:read'])->plainTextToken;
        
        // Should be able to read customers
        $response = $this->withToken($token)->getJson('/api/customers');
        $response->assertStatus(200);
        
        // Should not be able to create customers
        $response = $this->withToken($token)->postJson('/api/customers', [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'phone' => '+1234567890',
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
        ]);
        $response->assertStatus(403);
    }

    /**
     * Test webhook signature verification.
     */
    public function test_webhook_signature_verification()
    {
        $payload = json_encode(['event' => 'test']);
        $secret = config('services.retell.webhook_secret');
        
        // Test with valid signature
        $validSignature = hash_hmac('sha256', $payload, $secret);
        $response = $this->postJson('/api/retell/webhook', json_decode($payload, true), [
            'x-retell-signature' => $validSignature,
        ]);
        $response->assertSuccessful();
        
        // Test with invalid signature
        $response = $this->postJson('/api/retell/webhook', json_decode($payload, true), [
            'x-retell-signature' => 'invalid-signature',
        ]);
        $response->assertStatus(401);
        
        // Test without signature
        $response = $this->postJson('/api/retell/webhook', json_decode($payload, true));
        $response->assertStatus(401);
    }

    /**
     * Test API input sanitization.
     */
    public function test_api_input_sanitization()
    {
        Sanctum::actingAs($this->user);
        
        // Test that HTML and scripts are sanitized
        $response = $this->postJson('/api/customers', [
            'first_name' => '<script>alert("test")</script>John',
            'last_name' => 'Doe<img src=x onerror=alert(1)>',
            'email' => 'test@example.com',
            'phone' => '+1234567890',
            'notes' => '<iframe src="javascript:alert(1)"></iframe>',
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
        ]);
        
        $response->assertStatus(201);
        
        $customer = Customer::find($response->json('data.customer.id'));
        $this->assertStringNotContainsString('<script>', $customer->first_name);
        $this->assertStringNotContainsString('<img', $customer->last_name);
        $this->assertStringNotContainsString('<iframe>', $customer->notes);
    }

    /**
     * Test API method restrictions.
     */
    public function test_api_method_restrictions()
    {
        Sanctum::actingAs($this->user);
        
        // Test that only allowed methods work
        $response = $this->json('PATCH', '/api/customers/1');
        $response->assertStatus(405)
            ->assertHeader('Allow');
        
        $response = $this->json('TRACE', '/api/dashboard');
        $response->assertStatus(405);
    }

    /**
     * Test API content type validation.
     */
    public function test_api_content_type_validation()
    {
        Sanctum::actingAs($this->user);
        
        // Test that API only accepts JSON
        $response = $this->post('/api/customers', [
            'first_name' => 'Test',
        ], [
            'Content-Type' => 'application/x-www-form-urlencoded',
        ]);
        
        $response->assertStatus(415)
            ->assertJson(['message' => 'Unsupported Media Type']);
    }

    /**
     * Test API pagination limits.
     */
    public function test_api_pagination_limits()
    {
        Sanctum::actingAs($this->user);
        
        // Create many customers
        Customer::factory()->count(150)->create(['company_id' => $this->company->id]);
        
        // Test max per_page limit
        $response = $this->getJson('/api/customers?per_page=1000');
        $response->assertStatus(200);
        
        $data = $response->json('data');
        $this->assertLessThanOrEqual(100, count($data['customers'])); // Max should be 100
        $this->assertEquals(100, $data['pagination']['per_page']);
    }

    /**
     * Test API field filtering.
     */
    public function test_api_field_filtering()
    {
        Sanctum::actingAs($this->user);
        
        Customer::factory()->create([
            'company_id' => $this->company->id,
            'ssn' => '123-45-6789', // Sensitive field
        ]);
        
        // Sensitive fields should not be exposed
        $response = $this->getJson('/api/customers');
        $response->assertStatus(200);
        
        $customer = $response->json('data.customers.0');
        $this->assertArrayNotHasKey('ssn', $customer);
        $this->assertArrayNotHasKey('password', $customer);
    }

    /**
     * Test API timeout handling.
     */
    public function test_api_timeout_handling()
    {
        Sanctum::actingAs($this->user);
        
        // This would need to be mocked in a real test
        // Testing that long-running requests are terminated
        $response = $this->withHeaders(['X-Test-Delay' => '65'])
            ->getJson('/api/dashboard');
        
        // Should timeout after 60 seconds
        $this->assertContains($response->status(), [408, 504]);
    }

    /**
     * Test API error information leakage.
     */
    public function test_api_error_information_leakage()
    {
        Sanctum::actingAs($this->user);
        
        // Force a database error
        $response = $this->getJson('/api/customers?sort=invalid_column');
        
        // Should not expose internal error details
        $response->assertStatus(400);
        $responseData = $response->json();
        
        $this->assertArrayNotHasKey('exception', $responseData);
        $this->assertArrayNotHasKey('trace', $responseData);
        $this->assertArrayNotHasKey('file', $responseData);
        $this->assertArrayNotHasKey('line', $responseData);
        
        // Should have generic error message
        $this->assertArrayHasKey('message', $responseData);
        $this->assertNotRegExp('/Column not found|SQL|Database/', $responseData['message']);
    }

    /**
     * Test API request ID tracking.
     */
    public function test_api_request_id_tracking()
    {
        Sanctum::actingAs($this->user);
        
        $response = $this->getJson('/api/dashboard');
        
        // Should include request ID for tracking
        $response->assertHeader('X-Request-ID');
        
        $requestId = $response->headers->get('X-Request-ID');
        $this->assertNotEmpty($requestId);
        $this->assertMatchesRegularExpression('/^[a-f0-9\-]{36}$/', $requestId);
    }

    /**
     * Test API security headers.
     */
    public function test_api_security_headers()
    {
        Sanctum::actingAs($this->user);
        
        $response = $this->getJson('/api/dashboard');
        
        // Check security headers
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('X-XSS-Protection', '1; mode=block');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Content-Security-Policy');
        
        // Should not expose server information
        $this->assertNull($response->headers->get('Server'));
        $this->assertNull($response->headers->get('X-Powered-By'));
    }
}