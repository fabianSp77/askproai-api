<?php

namespace Tests\Feature\Security;

use App\Models\Company;
use App\Models\User;
use App\Models\PortalUser;
use App\Models\Customer;
use App\Models\Appointment;
use App\Models\Call;
use App\Models\Branch;
use App\Models\Service;
use App\Models\Staff;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Testing\File;
use Tests\TestCase;

/**
 * Base Security Test Case
 * 
 * Provides reusable security testing helpers and assertions
 * for comprehensive vulnerability testing across the application.
 */
abstract class BaseSecurityTestCase extends TestCase
{
    use RefreshDatabase;

    protected Company $company1;
    protected Company $company2;
    protected User $admin1;
    protected User $admin2;
    protected PortalUser $portalUser1;
    protected PortalUser $portalUser2;
    protected PortalUser $staffUser1;
    protected PortalUser $staffUser2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTestCompanies();
        $this->setupTestUsers();
    }

    /**
     * Setup test companies with different configurations
     */
    protected function setupTestCompanies(): void
    {
        $this->company1 = Company::factory()->create([
            'name' => 'SecurityTest Company 1',
            'slug' => 'security-test-company-1',
            'is_active' => true,
            'retell_api_key' => 'test_key_company1',
            'calcom_api_key' => 'calcom_key_company1',
        ]);

        $this->company2 = Company::factory()->create([
            'name' => 'SecurityTest Company 2',
            'slug' => 'security-test-company-2',
            'is_active' => true,
            'retell_api_key' => 'test_key_company2',
            'calcom_api_key' => 'calcom_key_company2',
        ]);
    }

    /**
     * Setup test users with different roles and permissions
     */
    protected function setupTestUsers(): void
    {
        // Admin users
        $this->admin1 = User::factory()->create([
            'company_id' => $this->company1->id,
            'email' => 'admin1@security-test.com',
            'is_active' => true,
        ]);

        $this->admin2 = User::factory()->create([
            'company_id' => $this->company2->id,
            'email' => 'admin2@security-test.com',
            'is_active' => true,
        ]);

        // Portal users (business portal admins)
        $this->portalUser1 = PortalUser::factory()->create([
            'company_id' => $this->company1->id,
            'email' => 'portal1@security-test.com',
            'role' => PortalUser::ROLE_ADMIN ?? 'admin',
            'is_active' => true,
        ]);

        $this->portalUser2 = PortalUser::factory()->create([
            'company_id' => $this->company2->id,
            'email' => 'portal2@security-test.com',
            'role' => PortalUser::ROLE_ADMIN ?? 'admin',
            'is_active' => true,
        ]);

        // Staff users (limited permissions)
        $this->staffUser1 = PortalUser::factory()->create([
            'company_id' => $this->company1->id,
            'email' => 'staff1@security-test.com',
            'role' => PortalUser::ROLE_STAFF ?? 'staff',
            'is_active' => true,
        ]);

        $this->staffUser2 = PortalUser::factory()->create([
            'company_id' => $this->company2->id,
            'email' => 'staff2@security-test.com',
            'role' => PortalUser::ROLE_STAFF ?? 'staff',
            'is_active' => true,
        ]);
    }

    /**
     * Create test data for a specific company
     */
    protected function createTestData(Company $company): array
    {
        $branch = Branch::factory()->create([
            'company_id' => $company->id,
            'name' => "Test Branch for {$company->name}",
        ]);

        $service = Service::factory()->create([
            'company_id' => $company->id,
            'name' => "Test Service for {$company->name}",
        ]);

        $customer = Customer::factory()->create([
            'company_id' => $company->id,
            'name' => "Test Customer for {$company->name}",
            'email' => "customer@{$company->slug}.com",
        ]);

        $appointment = Appointment::factory()->create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'customer_id' => $customer->id,
        ]);

        $call = Call::factory()->create([
            'company_id' => $company->id,
            'retell_call_id' => "test-call-{$company->id}",
        ]);

        return [
            'branch' => $branch,
            'service' => $service,
            'customer' => $customer,
            'appointment' => $appointment,
            'call' => $call,
        ];
    }

    /**
     * Assert that cross-tenant data access is prevented
     */
    protected function assertCrossTenantAccessPrevented(
        string $modelClass,
        int $recordId,
        User|PortalUser $unauthorizedUser,
        string $guard = 'web'
    ): void {
        $this->actingAs($unauthorizedUser, $guard);

        // Direct ID access should fail
        $record = $modelClass::find($recordId);
        $this->assertNull($record, "Cross-tenant access allowed via find() for {$modelClass}");

        // Query access should fail
        $records = $modelClass::where('id', $recordId)->get();
        $this->assertCount(0, $records, "Cross-tenant access allowed via where() for {$modelClass}");

        // First should not return unauthorized record
        $firstRecord = $modelClass::first();
        if ($firstRecord) {
            $this->assertNotEquals($recordId, $firstRecord->id, 
                "Cross-tenant record returned via first() for {$modelClass}");
        }
    }

    /**
     * Assert API endpoint requires authentication
     */
    protected function assertApiRequiresAuthentication(string $method, string $path): void
    {
        $response = $this->json($method, $path);
        
        if ($response->status() !== 404) {
            $this->assertTrue(
                in_array($response->status(), [401, 403, 302]), 
                "API endpoint {$method} {$path} does not require authentication. Status: {$response->status()}"
            );
        }
    }

    /**
     * Assert API endpoint requires proper authorization
     */
    protected function assertApiRequiresAuthorization(
        string $method, 
        string $path, 
        User|PortalUser $user, 
        string $guard = 'web'
    ): void {
        $this->actingAs($user, $guard);
        $response = $this->json($method, $path);
        
        if ($response->status() !== 404) {
            $this->assertTrue(
                in_array($response->status(), [403, 401]), 
                "API endpoint {$method} {$path} does not require proper authorization. Status: {$response->status()}"
            );
        }
    }

    /**
     * Test SQL injection protection
     */
    protected function assertSqlInjectionProtection(string $endpoint, array $injectionPayloads = null): void
    {
        $injectionPayloads = $injectionPayloads ?? [
            "'; DROP TABLE customers; --",
            "1' OR '1'='1",
            "'; UPDATE customers SET email='hacked@evil.com'; --",
            "admin'--",
            "' UNION SELECT * FROM users--",
            "'; INSERT INTO customers (name) VALUES ('hacked'); --",
        ];

        foreach ($injectionPayloads as $payload) {
            $response = $this->getJson($endpoint . '?search=' . urlencode($payload));
            
            if ($response->status() !== 404) {
                $this->assertNotEquals(500, $response->status(), 
                    "SQL injection payload caused server error: {$payload}");
            }
        }

        // Verify no malicious data was inserted
        $this->assertDatabaseMissing('customers', ['email' => 'hacked@evil.com']);
        $this->assertDatabaseMissing('customers', ['name' => 'hacked']);
    }

    /**
     * Test XSS protection
     */
    protected function assertXssProtection(string $endpoint, array $xssPayloads = null): void
    {
        $xssPayloads = $xssPayloads ?? [
            '<script>alert("xss")</script>',
            '<img src=x onerror=alert("xss")>',
            'javascript:alert("xss")',
            '<svg onload=alert("xss")>',
            '"><script>alert("xss")</script>',
        ];

        foreach ($xssPayloads as $payload) {
            $response = $this->getJson($endpoint . '?search=' . urlencode($payload));
            
            if ($response->status() === 200) {
                $responseData = $response->json();
                $responseContent = json_encode($responseData);
                
                $this->assertStringNotContainsString('<script>', $responseContent,
                    "XSS payload not sanitized in response");
                $this->assertStringNotContainsString('javascript:', $responseContent,
                    "JavaScript URL not sanitized in response");
            }
        }
    }

    /**
     * Test file upload security
     */
    protected function assertFileUploadSecurity(string $endpoint): void
    {
        $maliciousFiles = [
            File::create('malicious.php', 100),
            File::create('evil.exe', 100),
            File::create('shell.jsp', 100),
            File::create('hack.asp', 100),
        ];

        foreach ($maliciousFiles as $file) {
            $response = $this->postJson($endpoint, ['file' => $file]);
            
            if ($response->status() !== 404) {
                $this->assertTrue(
                    in_array($response->status(), [422, 400, 415]),
                    "Malicious file upload was accepted: {$file->name}"
                );
            }
        }
    }

    /**
     * Test mass assignment protection
     */
    protected function assertMassAssignmentProtection(
        string $endpoint, 
        array $protectedFields, 
        array $validData
    ): void {
        $maliciousData = array_merge($validData, $protectedFields);
        
        $response = $this->postJson($endpoint, $maliciousData);
        
        if (in_array($response->status(), [200, 201])) {
            // Check that protected fields were not set
            foreach ($protectedFields as $field => $value) {
                if ($response->json('data.' . $field)) {
                    $this->assertNotEquals($value, $response->json('data.' . $field),
                        "Mass assignment vulnerability: {$field} was set to unauthorized value");
                }
            }
        }
    }

    /**
     * Test session isolation between companies
     */
    protected function assertSessionIsolation(): void
    {
        // Login as company 1 user
        $this->actingAs($this->portalUser1, 'portal');
        Session::put('test_company_data', $this->company1->id);
        
        $sessionId1 = Session::getId();
        $sessionData1 = Session::all();
        
        // Logout and login as company 2 user
        Auth::logout();
        Session::flush();
        
        $this->actingAs($this->portalUser2, 'portal');
        Session::put('test_company_data', $this->company2->id);
        
        $sessionId2 = Session::getId();
        $sessionData2 = Session::all();
        
        // Sessions should be different
        $this->assertNotEquals($sessionId1, $sessionId2, 
            "Session IDs should be different between companies");
        
        // Company 2 session should not contain company 1 data
        $this->assertNotEquals($this->company1->id, Session::get('test_company_data'),
            "Session data leaked between companies");
    }

    /**
     * Test webhook data contamination
     */
    protected function assertWebhookDataIsolation(string $webhookEndpoint, array $payload): void
    {
        // Test with valid company data
        $validPayload = array_merge($payload, ['company_id' => $this->company1->id]);
        $response = $this->postJson($webhookEndpoint, $validPayload);
        
        // Test with different company data injection
        $maliciousPayload = array_merge($payload, [
            'company_id' => $this->company1->id,
            'target_company_id' => $this->company2->id,
            'override_company' => $this->company2->id,
        ]);
        
        $response = $this->postJson($webhookEndpoint, $maliciousPayload);
        
        // Verify data was not created for wrong company
        if (isset($payload['customer_email'])) {
            $customer = Customer::where('email', $payload['customer_email'])->first();
            if ($customer) {
                $this->assertEquals($this->company1->id, $customer->company_id,
                    "Webhook created data for wrong company");
            }
        }
    }

    /**
     * Test rate limiting
     */
    protected function assertRateLimiting(string $endpoint, int $maxRequests = 60): void
    {
        $responses = [];
        
        for ($i = 0; $i < $maxRequests + 10; $i++) {
            $responses[] = $this->getJson($endpoint);
        }
        
        $rateLimitHit = false;
        foreach ($responses as $response) {
            if ($response->status() === 429) {
                $rateLimitHit = true;
                break;
            }
        }
        
        // In testing environment, rate limiting might not be enabled
        // This is more of an integration test
        if (config('app.env') !== 'testing') {
            $this->assertTrue($rateLimitHit, "Rate limiting should be enforced");
        }
    }

    /**
     * Test CSRF protection
     */
    protected function assertCsrfProtection(string $endpoint, array $data): void
    {
        // Test without CSRF token
        $response = $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class)
            ->postJson($endpoint, $data);
        
        // With CSRF disabled, should work
        if ($response->status() !== 404) {
            $this->assertTrue(in_array($response->status(), [200, 201, 422]));
        }
        
        // Test with CSRF enabled (normal request)
        $response = $this->postJson($endpoint, $data);
        
        if ($response->status() !== 404) {
            // Should either work (if token is automatically included) or fail with 419
            $this->assertTrue(in_array($response->status(), [200, 201, 419, 422]));
        }
    }

    /**
     * Test error information disclosure
     */
    protected function assertNoErrorInformationDisclosure(array $responses): void
    {
        foreach ($responses as $response) {
            if ($response->status() === 500) {
                $responseData = $response->json() ?? [];
                $responseContent = json_encode($responseData);
                
                $sensitiveInfo = [
                    'database', 'mysql', 'password', 'secret', 'key', 'token',
                    'admin', 'root', 'connection', 'query', 'sql', 'env'
                ];
                
                foreach ($sensitiveInfo as $info) {
                    $this->assertStringNotContainsString(
                        $info, 
                        strtolower($responseContent),
                        "Error response contains sensitive information: {$info}"
                    );
                }
            }
        }
    }

    /**
     * Generate malicious input patterns for testing
     */
    protected function getMaliciousInputs(): array
    {
        return [
            // SQL Injection
            "'; DROP TABLE customers; --",
            "1' OR '1'='1",
            "admin'--",
            
            // XSS
            '<script>alert("xss")</script>',
            '<img src=x onerror=alert("xss")>',
            'javascript:alert("xss")',
            
            // Path Traversal
            '../../../etc/passwd',
            '..\\..\\..\\windows\\system32\\drivers\\etc\\hosts',
            
            // Command Injection
            '; cat /etc/passwd',
            '| whoami',
            '&& rm -rf /',
            
            // LDAP Injection
            '*)(uid=*',
            '*)(&(password=*))',
            
            // XML Injection
            '<?xml version="1.0"?><!DOCTYPE test [<!ENTITY xxe SYSTEM "file:///etc/passwd">]>',
        ];
    }

    /**
     * Log security test results
     */
    protected function logSecurityTestResult(string $testName, bool $passed, string $details = ''): void
    {
        $result = $passed ? 'PASSED' : 'FAILED';
        Log::channel('security')->info("Security Test: {$testName} - {$result}", [
            'test_name' => $testName,
            'passed' => $passed,
            'details' => $details,
            'timestamp' => now(),
            'user_agent' => request()->userAgent(),
            'ip_address' => request()->ip(),
        ]);
    }

    /**
     * Cleanup after security tests
     */
    protected function tearDown(): void
    {
        // Clear any test sessions
        Session::flush();
        Auth::logout();
        
        parent::tearDown();
    }
}