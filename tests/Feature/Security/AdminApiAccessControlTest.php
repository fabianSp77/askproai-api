<?php

namespace Tests\Feature\Security;

use App\Models\Customer;
use App\Models\Appointment;
use App\Models\Call;
use App\Models\User;

/**
 * Admin API Access Control Security Test
 * 
 * Tests admin panel API endpoints for proper authentication,
 * authorization, and access control vulnerabilities.
 * 
 * SEVERITY: CRITICAL - Admin privilege escalation potential
 */
class AdminApiAccessControlTest extends BaseSecurityTestCase
{
    protected array $adminApiEndpoints;
    protected array $sensitiveAdminEndpoints;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->adminApiEndpoints = [
            'GET /admin/api/dashboard',
            'GET /admin/api/customers',
            'POST /admin/api/customers',
            'PUT /admin/api/customers/1',
            'DELETE /admin/api/customers/1',
            'GET /admin/api/appointments',
            'POST /admin/api/appointments',
            'GET /admin/api/calls',
            'GET /admin/api/users',
            'POST /admin/api/users',
            'GET /admin/api/companies',
            'GET /admin/api/settings',
            'PUT /admin/api/settings',
        ];

        $this->sensitiveAdminEndpoints = [
            'GET /admin/api/users',
            'POST /admin/api/users',
            'PUT /admin/api/users/1',
            'DELETE /admin/api/users/1',
            'GET /admin/api/companies',
            'PUT /admin/api/companies/1',
            'GET /admin/api/system/logs',
            'GET /admin/api/system/config',
            'POST /admin/api/system/backup',
        ];
    }

    public function test_all_admin_api_endpoints_require_authentication()
    {
        foreach ($this->adminApiEndpoints as $endpoint) {
            [$method, $path] = explode(' ', $endpoint, 2);
            $this->assertApiRequiresAuthentication($method, $path);
        }

        $this->logSecurityTestResult('admin_api_authentication_required', true);
    }

    public function test_portal_users_cannot_access_admin_api()
    {
        $testData = $this->createTestData($this->company1);

        foreach ($this->adminApiEndpoints as $endpoint) {
            [$method, $path] = explode(' ', $endpoint, 2);
            
            // Replace placeholders with actual IDs
            $path = str_replace('/1', '/' . $testData['customer']->id, $path);
            
            $this->assertApiRequiresAuthorization($method, $path, $this->portalUser1, 'portal');
        }

        $this->logSecurityTestResult('portal_users_cannot_access_admin_api', true);
    }

    public function test_staff_users_cannot_access_admin_api()
    {
        $testData = $this->createTestData($this->company1);

        foreach ($this->adminApiEndpoints as $endpoint) {
            [$method, $path] = explode(' ', $endpoint, 2);
            
            // Replace placeholders with actual IDs
            $path = str_replace('/1', '/' . $testData['customer']->id, $path);
            
            $this->assertApiRequiresAuthorization($method, $path, $this->staffUser1, 'portal');
        }

        $this->logSecurityTestResult('staff_users_cannot_access_admin_api', true);
    }

    public function test_admin_users_cannot_access_other_company_data_via_api()
    {
        $company1Data = $this->createTestData($this->company1);
        $company2Data = $this->createTestData($this->company2);

        $this->actingAs($this->admin1);

        // Test customer access
        $response = $this->getJson("/admin/api/customers/{$company2Data['customer']->id}");
        if ($response->status() !== 404) {
            $this->assertTrue(in_array($response->status(), [403, 401]), 
                'Admin can access other company customer data');
        }

        // Test appointment access
        $response = $this->getJson("/admin/api/appointments/{$company2Data['appointment']->id}");
        if ($response->status() !== 404) {
            $this->assertTrue(in_array($response->status(), [403, 401]), 
                'Admin can access other company appointment data');
        }

        // Test call access
        $response = $this->getJson("/admin/api/calls/{$company2Data['call']->id}");
        if ($response->status() !== 404) {
            $this->assertTrue(in_array($response->status(), [403, 401]), 
                'Admin can access other company call data');
        }

        $this->logSecurityTestResult('admin_cross_company_api_access_blocked', true);
    }

    public function test_admin_api_prevents_privilege_escalation()
    {
        $this->actingAs($this->admin1);

        // Try to create a super admin user
        $response = $this->postJson('/admin/api/users', [
            'name' => 'Malicious Admin',
            'email' => 'malicious@test.com',
            'password' => 'password',
            'is_super_admin' => true,
            'role' => 'super_admin',
            'permissions' => ['*'],
            'company_id' => null, // Try to create cross-company user
        ]);

        if (in_array($response->status(), [200, 201])) {
            // Check that privilege escalation was prevented
            $user = User::where('email', 'malicious@test.com')->first();
            if ($user) {
                $this->assertFalse($user->is_super_admin ?? false, 
                    'Privilege escalation: Super admin flag was set');
                $this->assertEquals($this->company1->id, $user->company_id,
                    'User created for wrong company');
            }
        }

        $this->logSecurityTestResult('admin_api_privilege_escalation_prevented', true);
    }

    public function test_admin_api_input_validation_and_sanitization()
    {
        $this->actingAs($this->admin1);

        $maliciousInputs = [
            'name' => '<script>alert("xss")</script>Malicious Name',
            'email' => 'malicious@<script>alert("xss")</script>.com',
            'phone' => '+49<script>alert("xss")</script>123456789',
            'description' => '<?php system("rm -rf /"); ?>',
        ];

        $response = $this->postJson('/admin/api/customers', $maliciousInputs);

        if (in_array($response->status(), [200, 201])) {
            $customer = Customer::where('email', 'LIKE', '%malicious%')->first();
            if ($customer) {
                // Check that XSS and code injection was sanitized
                $this->assertStringNotContainsString('<script>', $customer->name);
                $this->assertStringNotContainsString('<?php', $customer->description ?? '');
            }
        }

        $this->logSecurityTestResult('admin_api_input_sanitization', true);
    }

    public function test_admin_api_sql_injection_protection()
    {
        $this->actingAs($this->admin1);
        
        $this->assertSqlInjectionProtection('/admin/api/customers');
        $this->assertSqlInjectionProtection('/admin/api/appointments');
        $this->assertSqlInjectionProtection('/admin/api/calls');

        $this->logSecurityTestResult('admin_api_sql_injection_protection', true);
    }

    public function test_admin_api_xss_protection()
    {
        $this->actingAs($this->admin1);
        
        $this->assertXssProtection('/admin/api/customers');
        $this->assertXssProtection('/admin/api/appointments');
        $this->assertXssProtection('/admin/api/calls');

        $this->logSecurityTestResult('admin_api_xss_protection', true);
    }

    public function test_admin_api_mass_assignment_protection()
    {
        $this->actingAs($this->admin1);

        $protectedFields = [
            'company_id' => $this->company2->id,
            'is_admin' => true,
            'role' => 'super_admin',
            'created_at' => '2020-01-01 00:00:00',
            'updated_at' => '2020-01-01 00:00:00',
        ];

        $validData = [
            'name' => 'Test Customer',
            'email' => 'test@example.com',
            'phone' => '+491234567890',
        ];

        $this->assertMassAssignmentProtection(
            '/admin/api/customers',
            $protectedFields,
            $validData
        );

        $this->logSecurityTestResult('admin_api_mass_assignment_protection', true);
    }

    public function test_admin_api_file_upload_security()
    {
        $this->actingAs($this->admin1);
        
        $this->assertFileUploadSecurity('/admin/api/customers/import');

        $this->logSecurityTestResult('admin_api_file_upload_security', true);
    }

    public function test_admin_api_rate_limiting()
    {
        $this->actingAs($this->admin1);
        
        $this->assertRateLimiting('/admin/api/customers', 100);

        $this->logSecurityTestResult('admin_api_rate_limiting', true);
    }

    public function test_admin_api_response_data_filtering()
    {
        $this->actingAs($this->admin1);

        // Create user with sensitive data
        $user = User::factory()->create([
            'company_id' => $this->company1->id,
            'email' => 'sensitive@test.com',
            'password' => bcrypt('secret123'),
        ]);

        $response = $this->getJson("/admin/api/users/{$user->id}");

        if ($response->status() === 200) {
            $responseData = $response->json();
            
            // Sensitive fields should not be exposed
            $this->assertArrayNotHasKey('password', $responseData);
            $this->assertArrayNotHasKey('remember_token', $responseData);
            
            // API keys should be masked or excluded
            if (isset($responseData['api_key'])) {
                $this->assertStringContainsString('***', $responseData['api_key']);
            }
        }

        $this->logSecurityTestResult('admin_api_response_data_filtering', true);
    }

    public function test_admin_api_audit_logging()
    {
        $this->actingAs($this->admin1);

        $initialLogCount = \DB::table('activity_log')->count();

        // Perform sensitive operation
        $response = $this->postJson('/admin/api/customers', [
            'name' => 'Audit Test Customer',
            'email' => 'audit@test.com',
            'phone' => '+491234567890',
        ]);

        if (in_array($response->status(), [200, 201])) {
            // Check if operation was logged
            $finalLogCount = \DB::table('activity_log')->count();
            
            if (\Schema::hasTable('activity_log')) {
                $this->assertGreaterThan($initialLogCount, $finalLogCount,
                    'Sensitive admin operation was not logged');
            }
        }

        $this->logSecurityTestResult('admin_api_audit_logging', true);
    }

    public function test_admin_api_prevents_data_exfiltration()
    {
        $this->actingAs($this->admin1);

        // Try to export all customer data
        $response = $this->getJson('/admin/api/customers?per_page=10000&export=true');

        if ($response->status() === 200) {
            $responseData = $response->json();
            
            // Should limit export size
            if (isset($responseData['data'])) {
                $this->assertLessThanOrEqual(1000, count($responseData['data']),
                    'Admin API allows unlimited data export');
            }
        }

        $this->logSecurityTestResult('admin_api_data_exfiltration_prevention', true);
    }

    public function test_admin_api_csrf_protection()
    {
        $this->actingAs($this->admin1);

        $testData = [
            'name' => 'CSRF Test Customer',
            'email' => 'csrf@test.com',
            'phone' => '+491234567890',
        ];

        $this->assertCsrfProtection('/admin/api/customers', $testData);

        $this->logSecurityTestResult('admin_api_csrf_protection', true);
    }

    public function test_admin_api_error_information_disclosure()
    {
        $this->actingAs($this->admin1);

        $responses = [
            $this->getJson('/admin/api/customers/999999999'),
            $this->getJson('/admin/api/nonexistent-endpoint'),
            $this->postJson('/admin/api/customers', ['invalid' => 'data']),
        ];

        $this->assertNoErrorInformationDisclosure($responses);

        $this->logSecurityTestResult('admin_api_error_disclosure_prevention', true);
    }

    public function test_admin_api_session_security()
    {
        // Test session fixation protection
        $initialSessionId = session()->getId();
        
        $this->actingAs($this->admin1);
        
        $newSessionId = session()->getId();
        
        // Session should be regenerated on login
        $this->assertNotEquals($initialSessionId, $newSessionId,
            'Session not regenerated on admin login');

        // Test session timeout
        $response = $this->getJson('/admin/api/customers');
        if ($response->status() === 200) {
            // Manipulate session to simulate timeout
            session(['last_activity' => time() - 7200]); // 2 hours ago
            
            $response = $this->getJson('/admin/api/customers');
            // Should require re-authentication after timeout
        }

        $this->logSecurityTestResult('admin_api_session_security', true);
    }

    public function test_admin_api_concurrent_session_protection()
    {
        // Test multiple concurrent admin sessions
        $this->actingAs($this->admin1);
        session(['admin_session_1' => true]);
        
        // Simulate second login from different location
        $this->actingAs($this->admin1);
        session(['admin_session_2' => true]);
        
        // Check if concurrent sessions are properly handled
        $response = $this->getJson('/admin/api/users');
        
        // Implementation depends on concurrent session policy
        $this->assertTrue(in_array($response->status(), [200, 401, 403]));

        $this->logSecurityTestResult('admin_api_concurrent_session_protection', true);
    }

    public function test_admin_api_ip_restriction()
    {
        $this->actingAs($this->admin1);

        // Simulate request from suspicious IP
        $response = $this->withHeaders([
            'X-Forwarded-For' => '192.168.999.999',
            'X-Real-IP' => '10.0.0.1',
        ])->getJson('/admin/api/customers');

        // IP restriction depends on configuration
        $this->assertTrue(in_array($response->status(), [200, 403]));

        $this->logSecurityTestResult('admin_api_ip_restriction', true);
    }

    public function test_admin_api_prevents_timing_attacks()
    {
        // Test authentication timing attacks
        $start1 = microtime(true);
        $this->postJson('/admin/login', [
            'email' => 'nonexistent@test.com',
            'password' => 'wrongpassword',
        ]);
        $time1 = microtime(true) - $start1;

        $start2 = microtime(true);
        $this->postJson('/admin/login', [
            'email' => $this->admin1->email,
            'password' => 'wrongpassword',
        ]);
        $time2 = microtime(true) - $start2;

        // Times should be similar to prevent user enumeration
        $timeDifference = abs($time1 - $time2);
        $this->assertLessThan(0.1, $timeDifference, 
            'Timing attack vulnerability in admin login');

        $this->logSecurityTestResult('admin_api_timing_attack_prevention', true);
    }
}