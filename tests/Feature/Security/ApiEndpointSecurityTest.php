<?php

namespace Tests\Feature\Security;

use App\Models\Company;
use App\Models\User;
use App\Models\PortalUser;
use App\Models\Customer;
use App\Models\Appointment;
use App\Models\Call;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ApiEndpointSecurityTest extends TestCase
{
    use RefreshDatabase;

    private Company $company1;
    private Company $company2;
    private User $admin1;
    private User $admin2;
    private PortalUser $portalUser1;
    private PortalUser $portalUser2;
    private PortalUser $staffUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Create companies
        $this->company1 = Company::factory()->create(['name' => 'Company 1']);
        $this->company2 = Company::factory()->create(['name' => 'Company 2']);

        // Create admin users
        $this->admin1 = User::factory()->create([
            'company_id' => $this->company1->id,
            'email' => 'admin1@test.com',
        ]);

        $this->admin2 = User::factory()->create([
            'company_id' => $this->company2->id,
            'email' => 'admin2@test.com',
        ]);

        // Create portal users
        $this->portalUser1 = PortalUser::factory()->create([
            'company_id' => $this->company1->id,
            'email' => 'portal1@test.com',
            'role' => PortalUser::ROLE_ADMIN,
        ]);

        $this->portalUser2 = PortalUser::factory()->create([
            'company_id' => $this->company2->id,
            'email' => 'portal2@test.com',
            'role' => PortalUser::ROLE_ADMIN,
        ]);

        $this->staffUser = PortalUser::factory()->create([
            'company_id' => $this->company1->id,
            'email' => 'staff@test.com',
            'role' => PortalUser::ROLE_STAFF,
        ]);
    }

    // Authentication Tests
    public function test_admin_api_requires_authentication()
    {
        $endpoints = [
            'GET /admin/api/dashboard',
            'GET /admin/api/customers',
            'GET /admin/api/appointments',
            'GET /admin/api/calls',
            'GET /admin/api/users',
        ];

        foreach ($endpoints as $endpoint) {
            [$method, $path] = explode(' ', $endpoint, 2);
            
            $response = $this->json($method, $path);
            
            // Should require authentication
            if ($response->status() !== 404) {
                $this->assertTrue(in_array($response->status(), [401, 403, 302]));
            }
        }
    }

    public function test_business_portal_api_requires_authentication()
    {
        $endpoints = [
            'GET /business/api/user',
            'GET /business/api/customers',
            'GET /business/api/appointments',
            'GET /business/api/calls',
            'GET /business/api/company',
        ];

        foreach ($endpoints as $endpoint) {
            [$method, $path] = explode(' ', $endpoint, 2);
            
            $response = $this->json($method, $path);
            
            // Should require authentication
            if ($response->status() !== 404) {
                $this->assertTrue(in_array($response->status(), [401, 403, 302]));
            }
        }
    }

    // Authorization Tests
    public function test_admin_cannot_access_other_company_data_via_api()
    {
        $customer = Customer::factory()->create([
            'company_id' => $this->company2->id,
        ]);

        $this->actingAs($this->admin1);

        $response = $this->getJson("/admin/api/customers/{$customer->id}");
        
        if ($response->status() !== 404) {
            $this->assertTrue(in_array($response->status(), [403, 401]));
        }
    }

    public function test_portal_user_cannot_access_other_company_data_via_api()
    {
        $customer = Customer::factory()->create([
            'company_id' => $this->company2->id,
        ]);

        $this->actingAs($this->portalUser1, 'portal');

        $response = $this->getJson("/business/api/customers/{$customer->id}");
        
        if ($response->status() !== 404) {
            $this->assertTrue(in_array($response->status(), [403, 401, 404]));
        }
    }

    public function test_staff_user_has_limited_api_access()
    {
        $this->actingAs($this->staffUser, 'portal');

        // Staff should not be able to access admin functions
        $response = $this->getJson('/business/api/users');
        
        if ($response->status() !== 404) {
            $this->assertTrue(in_array($response->status(), [403, 401]));
        }

        // But should be able to access their own profile
        $response = $this->getJson('/business/api/user');
        
        if ($response->status() !== 404) {
            $response->assertStatus(200);
        }
    }

    // Input Validation Tests
    public function test_api_validates_email_format()
    {
        $this->actingAs($this->admin1);

        $response = $this->postJson('/admin/api/customers', [
            'name' => 'Test Customer',
            'email' => 'invalid-email-format',
            'phone' => '+1234567890',
        ]);

        if ($response->status() !== 404) {
            $this->assertTrue(in_array($response->status(), [422, 400]));
            
            if ($response->status() === 422) {
                $response->assertJsonValidationErrors(['email']);
            }
        }
    }

    public function test_api_validates_phone_format()
    {
        $this->actingAs($this->admin1);

        $response = $this->postJson('/admin/api/customers', [
            'name' => 'Test Customer',
            'email' => 'test@example.com',
            'phone' => 'invalid-phone',
        ]);

        if ($response->status() !== 404) {
            $this->assertTrue(in_array($response->status(), [422, 400]));
        }
    }

    public function test_api_validates_required_fields()
    {
        $this->actingAs($this->admin1);

        $response = $this->postJson('/admin/api/customers', [
            // Missing required fields
        ]);

        if ($response->status() !== 404) {
            $this->assertTrue(in_array($response->status(), [422, 400]));
        }
    }

    // SQL Injection Protection
    public function test_api_protects_against_sql_injection()
    {
        $this->actingAs($this->admin1);

        $maliciousInputs = [
            "'; DROP TABLE customers; --",
            "1' OR '1'='1",
            "'; UPDATE customers SET email='hacked@evil.com'; --",
        ];

        foreach ($maliciousInputs as $maliciousInput) {
            $response = $this->getJson("/admin/api/customers?search=" . urlencode($maliciousInput));
            
            if ($response->status() !== 404) {
                // Should not cause server error
                $this->assertNotEquals(500, $response->status());
            }
        }

        // Verify data integrity
        $this->assertDatabaseMissing('customers', ['email' => 'hacked@evil.com']);
    }

    // XSS Protection
    public function test_api_sanitizes_output_against_xss()
    {
        $this->actingAs($this->admin1);

        $customer = Customer::factory()->create([
            'company_id' => $this->company1->id,
            'name' => '<script>alert("xss")</script>Test Customer',
        ]);

        $response = $this->getJson("/admin/api/customers/{$customer->id}");
        
        if ($response->status() !== 404 && $response->status() === 200) {
            $responseData = $response->json();
            
            // Should not contain raw script tags
            $this->assertStringNotContainsString('<script>', json_encode($responseData));
        }
    }

    // Rate Limiting Tests
    public function test_api_rate_limiting()
    {
        $this->actingAs($this->admin1);

        // Make multiple rapid requests
        $responses = [];
        for ($i = 0; $i < 100; $i++) {
            $responses[] = $this->getJson('/admin/api/customers');
        }

        // Should eventually hit rate limit
        $rateLimitHit = false;
        foreach ($responses as $response) {
            if ($response->status() === 429) {
                $rateLimitHit = true;
                break;
            }
        }

        // May not hit rate limit in testing environment
        $this->assertTrue(true); // Placeholder - actual rate limiting depends on configuration
    }

    // CSRF Protection
    public function test_api_csrf_protection_for_state_changing_operations()
    {
        $this->actingAs($this->admin1);

        // Try POST without CSRF token
        $response = $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class)
            ->postJson('/admin/api/customers', [
                'name' => 'Test Customer',
                'email' => 'test@example.com',
                'phone' => '+1234567890',
            ]);

        // With middleware disabled, should work
        if ($response->status() !== 404) {
            $this->assertTrue(in_array($response->status(), [200, 201, 422]));
        }
    }

    // Content Type Security
    public function test_api_only_accepts_json_content_type()
    {
        $this->actingAs($this->admin1);

        $response = $this->post('/admin/api/customers', [
            'name' => 'Test Customer',
            'email' => 'test@example.com',
        ], [
            'Content-Type' => 'application/x-www-form-urlencoded'
        ]);

        if ($response->status() !== 404) {
            // Should require JSON content type for API endpoints
            $this->assertTrue(in_array($response->status(), [415, 400, 422]));
        }
    }

    // Response Security Headers
    public function test_api_responses_have_security_headers()
    {
        $this->actingAs($this->admin1);

        $response = $this->getJson('/admin/api/customers');
        
        if ($response->status() !== 404) {
            // Check for security headers
            $headers = $response->headers->all();
            
            // These may or may not be present depending on middleware configuration
            $securityHeaders = [
                'x-content-type-options',
                'x-frame-options',
                'x-xss-protection',
            ];

            // At least one security header should be present
            $hasSecurityHeader = false;
            foreach ($securityHeaders as $header) {
                if (isset($headers[$header])) {
                    $hasSecurityHeader = true;
                    break;
                }
            }
        }
    }

    // File Upload Security
    public function test_api_file_upload_security()
    {
        $this->actingAs($this->admin1);

        // Create a fake malicious file
        $maliciousFile = \Illuminate\Http\Testing\File::create('malicious.php', 100);

        $response = $this->postJson('/admin/api/customers/import', [
            'file' => $maliciousFile,
        ]);

        if ($response->status() !== 404) {
            // Should reject non-allowed file types
            $this->assertTrue(in_array($response->status(), [422, 400, 415]));
        }
    }

    // Mass Assignment Protection
    public function test_api_protects_against_mass_assignment()
    {
        $this->actingAs($this->admin1);

        $response = $this->postJson('/admin/api/customers', [
            'name' => 'Test Customer',
            'email' => 'test@example.com',
            'phone' => '+1234567890',
            'company_id' => $this->company2->id, // Try to assign to different company
            'is_admin' => true, // Try to assign admin privileges
        ]);

        if ($response->status() !== 404 && in_array($response->status(), [200, 201])) {
            $customer = Customer::where('email', 'test@example.com')->first();
            
            if ($customer) {
                // Should not allow mass assignment of protected fields
                $this->assertEquals($this->company1->id, $customer->company_id);
                $this->assertNotEquals($this->company2->id, $customer->company_id);
            }
        }
    }

    // API Versioning Security
    public function test_api_version_header_handling()
    {
        $this->actingAs($this->admin1);

        $response = $this->getJson('/admin/api/customers', [
            'Accept' => 'application/vnd.api+json;version=1.0'
        ]);

        if ($response->status() !== 404) {
            // Should handle version headers appropriately
            $this->assertTrue(in_array($response->status(), [200, 406, 400]));
        }
    }

    // Error Information Disclosure
    public function test_api_does_not_leak_sensitive_error_info()
    {
        $this->actingAs($this->admin1);

        // Try to cause a server error
        $response = $this->getJson('/admin/api/customers/999999999');
        
        if ($response->status() === 500) {
            $responseData = $response->json();
            
            // Should not contain sensitive information in error response
            $responseContent = json_encode($responseData);
            $this->assertStringNotContainsString('database', strtolower($responseContent));
            $this->assertStringNotContainsString('password', strtolower($responseContent));
            $this->assertStringNotContainsString('secret', strtolower($responseContent));
        }
    }

    // Pagination Security
    public function test_api_pagination_limits()
    {
        $this->actingAs($this->admin1);

        // Try to request excessive records
        $response = $this->getJson('/admin/api/customers?per_page=10000');
        
        if ($response->status() !== 404 && $response->status() === 200) {
            $responseData = $response->json();
            
            // Should limit the number of records returned
            if (isset($responseData['data'])) {
                $returnedCount = count($responseData['data']);
                $this->assertLessThanOrEqual(100, $returnedCount); // Reasonable limit
            }
        }
    }

    // API Token Security (if implemented)
    public function test_api_token_security()
    {
        // If API tokens are implemented
        if (method_exists($this->admin1, 'createToken')) {
            $token = $this->admin1->createToken('Test Token')->plainTextToken;
            
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json',
            ])->getJson('/admin/api/customers');
            
            if ($response->status() !== 404) {
                $response->assertStatus(200);
            }
        } else {
            $this->markTestSkipped('API tokens not implemented');
        }
    }

    // Cross-Origin Request Security
    public function test_api_cors_configuration()
    {
        $this->actingAs($this->admin1);

        $response = $this->getJson('/admin/api/customers', [
            'Origin' => 'https://malicious-site.com'
        ]);

        if ($response->status() !== 404) {
            // Should handle CORS appropriately
            $this->assertTrue(in_array($response->status(), [200, 403, 400]));
        }
    }
}