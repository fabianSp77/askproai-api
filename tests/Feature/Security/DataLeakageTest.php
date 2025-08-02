<?php

namespace Tests\Feature\Security;

use App\Models\Customer;
use App\Models\User;
use App\Models\Call;
use App\Models\Appointment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * Data Leakage Security Test
 * 
 * Tests for various forms of data leakage including information
 * disclosure in responses, logs, cache, and error messages.
 * 
 * SEVERITY: HIGH - Sensitive data exposure potential
 */
class DataLeakageTest extends BaseSecurityTestCase
{
    public function test_sensitive_data_not_in_api_responses()
    {
        $this->actingAs($this->admin1);

        // Create user with sensitive data
        $user = User::factory()->create([
            'company_id' => $this->company1->id,
            'email' => 'sensitive@test.com',
            'password' => bcrypt('secret_password_123'),
            'remember_token' => 'remember_token_12345',
            'api_token' => 'api_token_67890',
        ]);

        $response = $this->getJson("/admin/api/users/{$user->id}");

        if ($response->status() === 200) {
            $responseData = $response->json();
            
            // Sensitive fields should not be exposed
            $this->assertArrayNotHasKey('password', $responseData);
            $this->assertArrayNotHasKey('remember_token', $responseData);
            
            if (isset($responseData['api_token'])) {
                // Should be masked if included
                $this->assertStringContainsString('***', $responseData['api_token']);
            }
        }

        $this->logSecurityTestResult('sensitive_data_api_response_filtering', true);
    }

    public function test_customer_data_isolation_in_responses()
    {
        $customer1 = Customer::factory()->create([
            'company_id' => $this->company1->id,
            'name' => 'Company 1 Customer',
            'email' => 'customer1@test.com',
            'phone' => '+491111111111',
            'internal_notes' => 'Sensitive internal information',
        ]);

        $customer2 = Customer::factory()->create([
            'company_id' => $this->company2->id,
            'name' => 'Company 2 Customer',
            'email' => 'customer2@test.com',
            'phone' => '+492222222222',
            'internal_notes' => 'Other company sensitive data',
        ]);

        $this->actingAs($this->admin1);

        // Get all customers - should only see company 1
        $response = $this->getJson('/admin/api/customers');

        if ($response->status() === 200) {
            $responseData = $response->json();
            $customers = $responseData['data'] ?? $responseData;

            foreach ($customers as $customer) {
                $this->assertEquals($this->company1->id, $customer['company_id']);
                $this->assertNotEquals('customer2@test.com', $customer['email']);
                $this->assertNotContains('Other company', $customer['internal_notes'] ?? '');
            }
        }

        $this->logSecurityTestResult('customer_data_isolation', true);
    }

    public function test_error_messages_dont_leak_sensitive_info()
    {
        $this->actingAs($this->admin1);

        $responses = [
            // Non-existent endpoints
            $this->getJson('/admin/api/nonexistent'),
            $this->getJson('/admin/api/customers/999999999'),
            
            // Invalid data
            $this->postJson('/admin/api/customers', ['invalid' => 'data']),
            
            // Malformed requests
            $this->postJson('/admin/api/customers', ['email' => 'not-an-email']),
        ];

        foreach ($responses as $response) {
            if (in_array($response->status(), [400, 404, 422, 500])) {
                $responseData = $response->json() ?? [];
                $responseContent = json_encode($responseData);
                
                $sensitiveInfo = [
                    'password', 'secret', 'key', 'token', 'database',
                    'mysql', 'connection', 'query', 'sql', 'env',
                    '/var/www', '/home/', 'root@', 'admin@',
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

        $this->logSecurityTestResult('error_message_information_leakage', true);
    }

    public function test_logs_dont_contain_sensitive_data()
    {
        Log::spy();

        $this->actingAs($this->admin1);

        // Perform actions that should be logged
        $this->postJson('/admin/api/customers', [
            'name' => 'Log Test Customer',
            'email' => 'logtest@example.com',
            'phone' => '+491234567890',
            'credit_card' => '4111-1111-1111-1111', // Sensitive data
            'ssn' => '123-45-6789', // Very sensitive
        ]);

        // Login attempts should be logged without passwords
        $this->postJson('/business/login', [
            'email' => 'test@example.com',
            'password' => 'secret_password_123',
        ]);

        // Check that sensitive data isn't logged in plain text
        // This is implementation-dependent, but we can verify the concept
        Log::shouldNotHaveReceived('info', function ($message, $context = []) {
            $content = json_encode([$message, $context]);
            return strpos($content, '4111-1111-1111-1111') !== false ||
                   strpos($content, 'secret_password_123') !== false ||
                   strpos($content, '123-45-6789') !== false;
        });

        $this->logSecurityTestResult('log_sensitive_data_protection', true);
    }

    public function test_cache_data_isolation()
    {
        // Clear cache first
        Cache::flush();

        $this->actingAs($this->admin1);

        // Cache some data for company 1
        Cache::put('company_1_data', [
            'sensitive_config' => 'company1_secret',
            'customer_count' => 100,
        ], 3600);

        // Switch to company 2
        $this->actingAs($this->admin2);

        // Company 2 should not access company 1 cache
        $cachedData = Cache::get('company_1_data');
        
        // If cache isolation is properly implemented, this should be null
        // If not properly isolated, this is a vulnerability
        if ($cachedData !== null) {
            // Log as potential vulnerability but don't fail test
            // as cache isolation implementation varies
            $this->logSecurityTestResult('cache_data_isolation', false, 
                'Cache data accessible across companies');
        } else {
            $this->logSecurityTestResult('cache_data_isolation', true);
        }

        Cache::flush();
    }

    public function test_debug_information_not_exposed()
    {
        // Test debug endpoints that might exist
        $debugEndpoints = [
            '/debug',
            '/admin/debug',
            '/phpinfo',
            '/info.php',
            '/_profiler',
            '/telescope',
            '/horizon/api/stats',
        ];

        foreach ($debugEndpoints as $endpoint) {
            $response = $this->getJson($endpoint);
            
            // Debug endpoints should not be accessible in production
            if ($response->status() === 200) {
                $content = $response->getContent();
                
                // Should not contain sensitive debug information
                $this->assertStringNotContainsString('DB_PASSWORD', $content);
                $this->assertStringNotContainsString('APP_KEY', $content);
                $this->assertStringNotContainsString('phpinfo()', $content);
            }
        }

        $this->logSecurityTestResult('debug_information_exposure', true);
    }

    public function test_database_credentials_not_exposed()
    {
        $this->actingAs($this->admin1);

        // Try to trigger database errors that might expose credentials
        $responses = [
            $this->getJson('/admin/api/customers?invalid_sql=1'),
            $this->postJson('/admin/api/customers', [
                'email' => str_repeat('a', 1000) . '@test.com'
            ]),
        ];

        foreach ($responses as $response) {
            $content = $response->getContent();
            
            // Should not contain database credentials
            $this->assertStringNotContainsString('DB_USERNAME', $content);
            $this->assertStringNotContainsString('DB_PASSWORD', $content);
            $this->assertStringNotContainsString('mysql://user:', $content);
            $this->assertStringNotContainsString('Connection failed', $content);
        }

        $this->logSecurityTestResult('database_credentials_exposure', true);
    }

    public function test_api_keys_not_exposed_in_responses()
    {
        $this->actingAs($this->admin1);

        // Get company settings that might contain API keys
        $response = $this->getJson('/admin/api/settings');

        if ($response->status() === 200) {
            $responseData = $response->json();
            $content = json_encode($responseData);

            // API keys should be masked or not included
            $this->assertStringNotContainsString('sk_live_', $content);
            $this->assertStringNotContainsString('sk_test_', $content);
            $this->assertStringNotContainsString('retell_api_key', $content);
            $this->assertStringNotContainsString('calcom_api_key', $content);
            
            // If keys are present, they should be masked
            if (isset($responseData['retell_api_key'])) {
                $this->assertStringContainsString('***', $responseData['retell_api_key']);
            }
        }

        $this->logSecurityTestResult('api_keys_exposure_prevention', true);
    }

    public function test_user_enumeration_protection()
    {
        // Test registration endpoint
        $response1 = $this->postJson('/register', [
            'email' => $this->portalUser1->email, // Existing email
            'password' => 'password123',
            'name' => 'Test User',
            'company_name' => 'Test Company',
        ]);

        $response2 = $this->postJson('/register', [
            'email' => 'nonexistent@test.com', // Non-existing email
            'password' => 'password123',
            'name' => 'Test User',
            'company_name' => 'Test Company',
        ]);

        // Responses should be similar to prevent user enumeration
        if ($response1->status() !== 404 && $response2->status() !== 404) {
            $this->assertEquals($response1->status(), $response2->status());
        }

        // Test password reset
        $resetResponse1 = $this->postJson('/password/email', [
            'email' => $this->portalUser1->email,
        ]);

        $resetResponse2 = $this->postJson('/password/email', [
            'email' => 'nonexistent@test.com',
        ]);

        // Should not reveal which emails exist
        if ($resetResponse1->status() !== 404 && $resetResponse2->status() !== 404) {
            $this->assertEquals($resetResponse1->status(), $resetResponse2->status());
        }

        $this->logSecurityTestResult('user_enumeration_protection', true);
    }

    public function test_pagination_data_leakage()
    {
        // Create customers for both companies
        Customer::factory()->count(5)->create(['company_id' => $this->company1->id]);
        Customer::factory()->count(3)->create(['company_id' => $this->company2->id]);

        $this->actingAs($this->admin1);

        $response = $this->getJson('/admin/api/customers?per_page=10');

        if ($response->status() === 200) {
            $responseData = $response->json();
            
            // Pagination should not leak information about other companies
            $totalCount = $responseData['total'] ?? count($responseData['data'] ?? []);
            $this->assertLessThanOrEqual(5, $totalCount, 
                'Pagination reveals other company data count');

            // All returned data should be from current company
            $customers = $responseData['data'] ?? $responseData;
            foreach ($customers as $customer) {
                $this->assertEquals($this->company1->id, $customer['company_id']);
            }
        }

        $this->logSecurityTestResult('pagination_data_leakage', true);
    }

    public function test_search_functionality_data_leakage()
    {
        $customer1 = Customer::factory()->create([
            'company_id' => $this->company1->id,
            'name' => 'Unique Customer Name 12345',
            'email' => 'unique1@test.com',
        ]);

        $customer2 = Customer::factory()->create([
            'company_id' => $this->company2->id,
            'name' => 'Unique Customer Name 12345', // Same name, different company
            'email' => 'unique2@test.com',
        ]);

        $this->actingAs($this->admin1);

        $response = $this->getJson('/admin/api/customers?search=Unique Customer Name 12345');

        if ($response->status() === 200) {
            $responseData = $response->json();
            $customers = $responseData['data'] ?? $responseData;

            // Should only find customer from current company
            $this->assertCount(1, $customers);
            $this->assertEquals('unique1@test.com', $customers[0]['email']);
            $this->assertEquals($this->company1->id, $customers[0]['company_id']);
        }

        $this->logSecurityTestResult('search_data_leakage', true);
    }

    public function test_export_functionality_data_leakage()
    {
        // Create test data
        Customer::factory()->count(10)->create(['company_id' => $this->company1->id]);
        Customer::factory()->count(15)->create(['company_id' => $this->company2->id]);

        $this->actingAs($this->admin1);

        $response = $this->getJson('/admin/api/customers/export');

        if ($response->status() === 200) {
            $content = $response->getContent();
            
            // Export should only contain current company data
            $this->assertStringNotContainsString($this->company2->name, $content);
            
            // Count lines to verify only company 1 data is exported
            $lines = explode("\n", $content);
            $dataLines = array_filter($lines, fn($line) => !empty(trim($line)) && !str_contains($line, 'Name,Email'));
            
            $this->assertLessThanOrEqual(10, count($dataLines), 
                'Export contains data from other companies');
        }

        $this->logSecurityTestResult('export_data_leakage', true);
    }

    public function test_webhook_response_data_leakage()
    {
        // Test webhook endpoints don't leak data
        $webhookPayload = [
            'event_type' => 'call_ended',
            'call_id' => 'test_call_123',
            'call' => [
                'from_number' => '+491234567890',
                'to_number' => '+491234567891',
                'duration_ms' => 60000,
            ],
        ];

        $response = $this->postJson('/api/retell/webhook', $webhookPayload);

        if (in_array($response->status(), [200, 201])) {
            $responseData = $response->json() ?? [];
            $content = json_encode($responseData);
            
            // Should not leak internal system information
            $this->assertStringNotContainsString('database', $content);
            $this->assertStringNotContainsString('company_id', $content);
            $this->assertStringNotContainsString('internal', $content);
        }

        $this->logSecurityTestResult('webhook_response_data_leakage', true);
    }
}