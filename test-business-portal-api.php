#!/usr/bin/env php
<?php

/**
 * Comprehensive Business Portal API Testing Script
 * 
 * Tests all business portal API endpoints for:
 * - Authentication handling
 * - Response status codes
 * - Response data structure
 * - Error handling
 * - CORS headers
 * - Session persistence
 * - Rate limiting
 */

require_once __DIR__ . '/vendor/autoload.php';

class BusinessPortalAPITester
{
    private string $baseUrl;
    private array $testResults = [];
    private ?string $sessionToken = null;
    private ?string $sessionId = null;
    private array $cookies = [];
    private int $totalTests = 0;
    private int $passedTests = 0;
    private int $failedTests = 0;

    public function __construct(string $baseUrl = 'https://api.askproai.de')
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        echo "🚀 Business Portal API Testing Suite\n";
        echo "Base URL: {$this->baseUrl}\n";
        echo str_repeat('=', 60) . "\n\n";
    }

    public function runAllTests(): void
    {
        // Test categories
        $testCategories = [
            'Authentication Endpoints' => [
                'testAuthenticationEndpoints',
                'testLoginValidation',
                'testLogoutFunctionality',
                'testSessionPersistence'
            ],
            'Dashboard Data Endpoints' => [
                'testDashboardStats',
                'testRecentCalls',
                'testUpcomingAppointments'
            ],
            'Customer Management' => [
                'testCustomersAPI',
                'testCustomerDetails'
            ],
            'Appointment Management' => [
                'testAppointmentsAPI',
                'testAppointmentCRUD',
                'testAppointmentFilters',
                'testAvailableSlots'
            ],
            'Billing Endpoints' => [
                'testBillingData',
                'testBillingTransactions',
                'testBillingUsage',
                'testAutoTopup'
            ],
            'Settings Endpoints' => [
                'testSettingsAccess',
                'testProfileUpdate',
                'testPasswordUpdate',
                'test2FASettings'
            ],
            'Calls API' => [
                'testCallsAPI',
                'testCallDetails'
            ],
            'Security & Performance' => [
                'testCORSHeaders',
                'testRateLimiting',
                'testUnauthorizedAccess',
                'testCSRFProtection'
            ]
        ];

        foreach ($testCategories as $category => $tests) {
            $this->printSectionHeader($category);
            
            foreach ($tests as $testMethod) {
                if (method_exists($this, $testMethod)) {
                    $this->$testMethod();
                } else {
                    $this->logTest("Method {$testMethod} not found", 'SKIP', [], "Method not implemented");
                }
            }
            
            echo "\n";
        }

        $this->printSummary();
    }

    // ================================
    // Authentication Tests
    // ================================

    private function testAuthenticationEndpoints(): void
    {
        // Test login endpoint
        $loginData = [
            'email' => 'test@askproai.de',
            'password' => 'password123'
        ];

        $response = $this->makeRequest('POST', '/business/api/auth/login', $loginData);
        
        if ($response['status'] === 200 || $response['status'] === 422) {
            $this->logTest('Login endpoint accessibility', 'PASS', $response);
        } else {
            $this->logTest('Login endpoint accessibility', 'FAIL', $response);
        }

        // Test auth check endpoint
        $response = $this->makeRequest('GET', '/business/api/auth/check');
        $this->logTest('Auth check endpoint', 
            in_array($response['status'], [200, 401]) ? 'PASS' : 'FAIL', 
            $response
        );
    }

    private function testLoginValidation(): void
    {
        // Test with invalid credentials
        $invalidData = [
            'email' => 'invalid@test.com',
            'password' => 'wrongpassword'
        ];

        $response = $this->makeRequest('POST', '/business/api/auth/login', $invalidData);
        $this->logTest('Login with invalid credentials', 
            in_array($response['status'], [401, 422]) ? 'PASS' : 'FAIL', 
            $response
        );

        // Test with missing fields
        $response = $this->makeRequest('POST', '/business/api/auth/login', []);
        $this->logTest('Login with missing fields', 
            in_array($response['status'], [422, 400]) ? 'PASS' : 'FAIL', 
            $response
        );
    }

    private function testLogoutFunctionality(): void
    {
        $response = $this->makeRequest('POST', '/business/api/auth/logout');
        $this->logTest('Logout functionality', 
            in_array($response['status'], [200, 302, 401]) ? 'PASS' : 'FAIL', 
            $response
        );
    }

    private function testSessionPersistence(): void
    {
        // Try to maintain session across requests
        $response1 = $this->makeRequest('GET', '/business/api/auth/check');
        $sessionId1 = $this->extractSessionId($response1);

        $response2 = $this->makeRequest('GET', '/business/api/auth/check');
        $sessionId2 = $this->extractSessionId($response2);

        $this->logTest('Session persistence', 
            $sessionId1 === $sessionId2 ? 'PASS' : 'INFO', 
            ['session1' => $sessionId1, 'session2' => $sessionId2]
        );
    }

    // ================================
    // Dashboard Tests
    // ================================

    private function testDashboardStats(): void
    {
        $response = $this->makeRequest('GET', '/business/api/dashboard/stats');
        
        $isValid = in_array($response['status'], [200, 401]);
        if ($response['status'] === 200) {
            $isValid = $isValid && isset($response['data']);
        }

        $this->logTest('Dashboard stats endpoint', 
            $isValid ? 'PASS' : 'FAIL', 
            $response
        );
    }

    private function testRecentCalls(): void
    {
        $response = $this->makeRequest('GET', '/business/api/dashboard/recent-calls');
        
        $this->logTest('Recent calls endpoint', 
            in_array($response['status'], [200, 401]) ? 'PASS' : 'FAIL', 
            $response
        );
    }

    private function testUpcomingAppointments(): void
    {
        $response = $this->makeRequest('GET', '/business/api/dashboard/upcoming-appointments');
        
        $this->logTest('Upcoming appointments endpoint', 
            in_array($response['status'], [200, 401]) ? 'PASS' : 'FAIL', 
            $response
        );
    }

    // ================================
    // Customer Management Tests
    // ================================

    private function testCustomersAPI(): void
    {
        $response = $this->makeRequest('GET', '/business/api/customers');
        
        $this->logTest('Customers API endpoint', 
            in_array($response['status'], [200, 401]) ? 'PASS' : 'FAIL', 
            $response
        );
    }

    private function testCustomerDetails(): void
    {
        // Test with a dummy customer ID
        $response = $this->makeRequest('GET', '/business/api/customers/1');
        
        $this->logTest('Customer details endpoint', 
            in_array($response['status'], [200, 404, 401]) ? 'PASS' : 'FAIL', 
            $response
        );
    }

    // ================================
    // Appointment Management Tests
    // ================================

    private function testAppointmentsAPI(): void
    {
        $response = $this->makeRequest('GET', '/business/api/appointments');
        
        $this->logTest('Appointments API endpoint', 
            in_array($response['status'], [200, 401]) ? 'PASS' : 'FAIL', 
            $response
        );
    }

    private function testAppointmentCRUD(): void
    {
        // Test creating appointment
        $appointmentData = [
            'customer_name' => 'Test Customer',
            'service_id' => 1,
            'appointment_date' => date('Y-m-d H:i:s', strtotime('+1 day')),
            'notes' => 'Test appointment'
        ];

        $response = $this->makeRequest('POST', '/business/api/appointments', $appointmentData);
        $this->logTest('Create appointment', 
            in_array($response['status'], [201, 422, 401]) ? 'PASS' : 'FAIL', 
            $response
        );

        // Test updating appointment status
        $response = $this->makeRequest('POST', '/business/api/appointments/1/status', ['status' => 'confirmed']);
        $this->logTest('Update appointment status', 
            in_array($response['status'], [200, 404, 401]) ? 'PASS' : 'FAIL', 
            $response
        );
    }

    private function testAppointmentFilters(): void
    {
        $response = $this->makeRequest('GET', '/business/api/appointments/filters');
        
        $this->logTest('Appointment filters endpoint', 
            in_array($response['status'], [200, 401]) ? 'PASS' : 'FAIL', 
            $response
        );
    }

    private function testAvailableSlots(): void
    {
        $params = [
            'date' => date('Y-m-d', strtotime('+1 day')),
            'service_id' => 1
        ];

        $response = $this->makeRequest('GET', '/business/api/appointments/available-slots?' . http_build_query($params));
        
        $this->logTest('Available slots endpoint', 
            in_array($response['status'], [200, 401]) ? 'PASS' : 'FAIL', 
            $response
        );
    }

    // ================================
    // Billing Tests
    // ================================

    private function testBillingData(): void
    {
        $response = $this->makeRequest('GET', '/business/api/billing');
        
        $this->logTest('Billing data endpoint', 
            in_array($response['status'], [200, 401]) ? 'PASS' : 'FAIL', 
            $response
        );
    }

    private function testBillingTransactions(): void
    {
        $response = $this->makeRequest('GET', '/business/api/billing/transactions');
        
        $this->logTest('Billing transactions endpoint', 
            in_array($response['status'], [200, 401]) ? 'PASS' : 'FAIL', 
            $response
        );
    }

    private function testBillingUsage(): void
    {
        $response = $this->makeRequest('GET', '/business/api/billing/usage');
        
        $this->logTest('Billing usage endpoint', 
            in_array($response['status'], [200, 401]) ? 'PASS' : 'FAIL', 
            $response
        );
    }

    private function testAutoTopup(): void
    {
        $topupData = [
            'enabled' => true,
            'threshold' => 10.00,
            'amount' => 50.00
        ];

        $response = $this->makeRequest('POST', '/business/api/billing/auto-topup', $topupData);
        
        $this->logTest('Auto-topup configuration', 
            in_array($response['status'], [200, 422, 401]) ? 'PASS' : 'FAIL', 
            $response
        );
    }

    // ================================
    // Settings Tests
    // ================================

    private function testSettingsAccess(): void
    {
        $response = $this->makeRequest('GET', '/business/settings');
        
        $this->logTest('Settings page access', 
            in_array($response['status'], [200, 302, 401]) ? 'PASS' : 'FAIL', 
            $response
        );
    }

    private function testProfileUpdate(): void
    {
        $profileData = [
            'name' => 'Updated Name',
            'email' => 'updated@test.com'
        ];

        $response = $this->makeRequest('POST', '/business/settings/profile', $profileData);
        
        $this->logTest('Profile update', 
            in_array($response['status'], [200, 302, 422, 401]) ? 'PASS' : 'FAIL', 
            $response
        );
    }

    private function testPasswordUpdate(): void
    {
        $passwordData = [
            'current_password' => 'oldpassword',
            'password' => 'newpassword',
            'password_confirmation' => 'newpassword'
        ];

        $response = $this->makeRequest('POST', '/business/settings/password', $passwordData);
        
        $this->logTest('Password update', 
            in_array($response['status'], [200, 302, 422, 401]) ? 'PASS' : 'FAIL', 
            $response
        );
    }

    private function test2FASettings(): void
    {
        $response = $this->makeRequest('POST', '/business/settings/2fa/enable');
        
        $this->logTest('2FA enable endpoint', 
            in_array($response['status'], [200, 302, 422, 401]) ? 'PASS' : 'FAIL', 
            $response
        );
    }

    // ================================
    // Calls API Tests
    // ================================

    private function testCallsAPI(): void
    {
        $response = $this->makeRequest('GET', '/business/api/calls');
        
        $this->logTest('Calls API endpoint', 
            in_array($response['status'], [200, 401]) ? 'PASS' : 'FAIL', 
            $response
        );
    }

    private function testCallDetails(): void
    {
        $response = $this->makeRequest('GET', '/business/api/calls/1');
        
        $this->logTest('Call details endpoint', 
            in_array($response['status'], [200, 404, 401]) ? 'PASS' : 'FAIL', 
            $response
        );
    }

    // ================================
    // Security & Performance Tests
    // ================================

    private function testCORSHeaders(): void
    {
        $response = $this->makeRequest('OPTIONS', '/business/api/dashboard/stats');
        
        $hasCorsHeaders = isset($response['headers']['access-control-allow-origin']) ||
                         isset($response['headers']['Access-Control-Allow-Origin']);
        
        $this->logTest('CORS headers present', 
            $hasCorsHeaders ? 'PASS' : 'INFO', 
            ['headers' => array_keys($response['headers'])]
        );
    }

    private function testRateLimiting(): void
    {
        $responses = [];
        
        // Make multiple rapid requests
        for ($i = 0; $i < 5; $i++) {
            $responses[] = $this->makeRequest('GET', '/business/api/auth/check');
            usleep(100000); // 100ms delay
        }

        $hasRateLimit = false;
        foreach ($responses as $response) {
            if ($response['status'] === 429) {
                $hasRateLimit = true;
                break;
            }
        }

        $this->logTest('Rate limiting', 
            $hasRateLimit ? 'PASS' : 'INFO', 
            ['note' => 'Rate limiting may not be triggered with test volume']
        );
    }

    private function testUnauthorizedAccess(): void
    {
        // Test protected endpoint without authentication
        $response = $this->makeRequestWithoutCookies('GET', '/business/api/dashboard/stats');
        
        $this->logTest('Unauthorized access protection', 
            $response['status'] === 401 ? 'PASS' : 'FAIL', 
            $response
        );
    }

    private function testCSRFProtection(): void
    {
        // Test POST request without CSRF token
        $response = $this->makeRequestWithoutCookies('POST', '/business/api/appointments', []);
        
        $this->logTest('CSRF protection', 
            in_array($response['status'], [401, 419, 422]) ? 'PASS' : 'FAIL', 
            $response
        );
    }

    // ================================
    // Helper Methods
    // ================================

    private function makeRequest(string $method, string $endpoint, array $data = []): array
    {
        return $this->makeRequestInternal($method, $endpoint, $data, true);
    }

    private function makeRequestWithoutCookies(string $method, string $endpoint, array $data = []): array
    {
        return $this->makeRequestInternal($method, $endpoint, $data, false);
    }

    private function makeRequestInternal(string $method, string $endpoint, array $data = [], bool $includeCookies = true): array
    {
        $url = $this->baseUrl . $endpoint;
        
        $ch = curl_init();
        
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
            'User-Agent: BusinessPortalAPITester/1.0'
        ];

        if ($includeCookies && !empty($this->cookies)) {
            $cookieString = '';
            foreach ($this->cookies as $key => $value) {
                $cookieString .= $key . '=' . $value . '; ';
            }
            $headers[] = 'Cookie: ' . rtrim($cookieString, '; ');
        }

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        ]);

        if (!empty($data) && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return [
                'status' => 0,
                'error' => $error,
                'data' => null,
                'headers' => []
            ];
        }

        $headerString = substr($response, 0, $headerSize);
        $body = substr($response, $headerSize);

        // Parse headers
        $headers = [];
        foreach (explode("\r\n", $headerString) as $header) {
            if (strpos($header, ':') !== false) {
                [$key, $value] = explode(':', $header, 2);
                $headers[trim($key)] = trim($value);
            }
        }

        // Extract cookies for session persistence
        if (isset($headers['Set-Cookie'])) {
            $this->parseCookies($headers['Set-Cookie']);
        }

        $decodedBody = json_decode($body, true);

        return [
            'status' => $httpCode,
            'data' => $decodedBody,
            'headers' => $headers,
            'raw_body' => $body
        ];
    }

    private function parseCookies(string $setCookieHeader): void
    {
        $cookies = explode(',', $setCookieHeader);
        foreach ($cookies as $cookie) {
            if (strpos($cookie, '=') !== false) {
                [$name, $value] = explode('=', $cookie, 2);
                $name = trim($name);
                $value = trim(explode(';', $value)[0]);
                $this->cookies[$name] = $value;
            }
        }
    }

    private function extractSessionId(array $response): ?string
    {
        if (isset($response['headers']['Set-Cookie'])) {
            preg_match('/laravel_session=([^;]+)/', $response['headers']['Set-Cookie'], $matches);
            return $matches[1] ?? null;
        }
        return null;
    }

    private function logTest(string $testName, string $status, array $response, string $message = ''): void
    {
        $this->totalTests++;
        
        $statusIcon = match($status) {
            'PASS' => '✅',
            'FAIL' => '❌',
            'SKIP' => '⏭️',
            'INFO' => 'ℹ️',
            default => '❓'
        };

        $statusColor = match($status) {
            'PASS' => "\033[32m",
            'FAIL' => "\033[31m",
            'SKIP' => "\033[33m",
            'INFO' => "\033[36m", 
            default => "\033[0m"
        };

        echo sprintf("%s %s%-50s%s [%d]%s\n", 
            $statusIcon,
            $statusColor,
            $testName,
            "\033[0m",
            $response['status'] ?? 0,
            $message ? " - {$message}" : ''
        );

        if ($status === 'PASS') {
            $this->passedTests++;
        } elseif ($status === 'FAIL') {
            $this->failedTests++;
        }

        $this->testResults[] = [
            'test' => $testName,
            'status' => $status,
            'response_code' => $response['status'] ?? 0,
            'message' => $message,
            'response' => $response
        ];

        // Add delay to avoid overwhelming the server
        usleep(250000); // 250ms
    }

    private function printSectionHeader(string $section): void
    {
        echo "\n" . str_repeat('─', 60) . "\n";
        echo "🔍 Testing: {$section}\n";
        echo str_repeat('─', 60) . "\n";
    }

    private function printSummary(): void
    {
        echo "\n" . str_repeat('=', 60) . "\n";
        echo "📊 Test Summary\n";
        echo str_repeat('=', 60) . "\n";
        
        $successRate = $this->totalTests > 0 ? ($this->passedTests / $this->totalTests) * 100 : 0;
        
        echo sprintf("Total Tests: %d\n", $this->totalTests);
        echo sprintf("✅ Passed: %d\n", $this->passedTests);
        echo sprintf("❌ Failed: %d\n", $this->failedTests);
        echo sprintf("📈 Success Rate: %.1f%%\n", $successRate);

        // Summary of critical issues
        $criticalIssues = array_filter($this->testResults, fn($r) => $r['status'] === 'FAIL');
        
        if (!empty($criticalIssues)) {
            echo "\n🚨 Critical Issues Found:\n";
            foreach ($criticalIssues as $issue) {
                echo sprintf("   • %s (HTTP %d)\n", $issue['test'], $issue['response_code']);
            }
        }

        echo "\n💾 Full test results available in \$testResults property\n";
        echo str_repeat('=', 60) . "\n";
    }

    public function getTestResults(): array
    {
        return $this->testResults;
    }

    public function saveResultsToFile(string $filename = null): void
    {
        $filename = $filename ?? 'business_portal_api_test_results_' . date('Y-m-d_H-i-s') . '.json';
        
        $report = [
            'test_run' => [
                'timestamp' => date('c'),
                'base_url' => $this->baseUrl,
                'total_tests' => $this->totalTests,
                'passed_tests' => $this->passedTests,
                'failed_tests' => $this->failedTests,
                'success_rate' => $this->totalTests > 0 ? ($this->passedTests / $this->totalTests) * 100 : 0
            ],
            'results' => $this->testResults
        ];

        file_put_contents($filename, json_encode($report, JSON_PRETTY_PRINT));
        echo "\n💾 Results saved to: {$filename}\n";
    }
}

// Run the tests
$tester = new BusinessPortalAPITester();
$tester->runAllTests();
$tester->saveResultsToFile();

echo "\n🎯 Testing completed!\n";