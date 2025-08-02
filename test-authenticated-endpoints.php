#!/usr/bin/env php
<?php

/**
 * Test authenticated endpoints with proper session handling
 */

require_once __DIR__ . '/vendor/autoload.php';

class AuthenticatedAPITester
{
    private string $baseUrl;
    private array $cookies = [];
    private ?string $csrfToken = null;
    
    public function __construct(string $baseUrl = 'https://api.askproai.de')
    {
        $this->baseUrl = rtrim($baseUrl, '/');
    }
    
    public function testWithAuth(): void
    {
        echo "🔐 Testing Business Portal API with Authentication\n";
        echo "====================================================\n\n";
        
        // Step 1: Get CSRF token
        $this->getCsrfToken();
        
        // Step 2: Test login
        $this->testLogin();
        
        // Step 3: Test authenticated endpoints
        $this->testAuthenticatedEndpoints();
        
        echo "\n✅ Authentication testing completed\n";
    }
    
    private function getCsrfToken(): void
    {
        echo "🔑 Getting CSRF token...\n";
        
        $response = $this->makeRequest('GET', '/business/login');
        
        if (isset($response['headers']['Set-Cookie'])) {
            $this->parseCookies($response['headers']['Set-Cookie']);
        }
        
        // Try to extract CSRF token from HTML or get via API
        $csrfResponse = $this->makeRequest('GET', '/api/csrf-token');
        if (isset($csrfResponse['data']['csrf_token'])) {
            $this->csrfToken = $csrfResponse['data']['csrf_token'];
            echo "   ✅ CSRF token obtained\n";
        } else {
            echo "   ⚠️  Could not get CSRF token\n";
        }
    }
    
    private function testLogin(): void
    {
        echo "\n🔓 Testing login...\n";
        
        // Try with demo credentials if available
        $credentials = [
            'email' => 'demo@askproai.de', // Common demo email
            'password' => 'demo123',
            '_token' => $this->csrfToken
        ];
        
        $response = $this->makeRequest('POST', '/business/api/auth/login', $credentials);
        
        echo "   Login response: " . $response['status'] . "\n";
        
        if ($response['status'] === 200) {
            echo "   ✅ Login successful\n";
            if (isset($response['headers']['Set-Cookie'])) {
                $this->parseCookies($response['headers']['Set-Cookie']);
            }
        } else {
            echo "   ⚠️  Login failed - testing with session cookies only\n";
        }
    }
    
    private function testAuthenticatedEndpoints(): void
    {
        echo "\n📊 Testing authenticated endpoints...\n";
        
        $endpoints = [
            'Dashboard Stats' => '/business/api/dashboard/stats',
            'Recent Calls' => '/business/api/dashboard/recent-calls',
            'Appointments' => '/business/api/appointments',
            'Calls' => '/business/api/calls',
            'Billing Data' => '/business/api/billing',
        ];
        
        foreach ($endpoints as $name => $endpoint) {
            $response = $this->makeRequest('GET', $endpoint);
            
            $status = $response['status'];
            $statusIcon = match(true) {
                $status === 200 => '✅',
                $status === 401 => '🔒',
                $status === 500 => '💥',
                default => '❓'
            };
            
            echo "   {$statusIcon} {$name}: {$status}\n";
            
            if ($status === 500) {
                echo "      Error: " . ($response['data']['message'] ?? 'Server Error') . "\n";
            }
        }
    }
    
    private function makeRequest(string $method, string $endpoint, array $data = []): array
    {
        $url = $this->baseUrl . $endpoint;
        
        $ch = curl_init();
        
        $headers = [
            'Accept: application/json',
            'User-Agent: AuthenticatedAPITester/1.0'
        ];
        
        if (!empty($data)) {
            $headers[] = 'Content-Type: application/json';
        }
        
        if (!empty($this->cookies)) {
            $cookieString = '';
            foreach ($this->cookies as $key => $value) {
                $cookieString .= $key . '=' . $value . '; ';
            }
            $headers[] = 'Cookie: ' . rtrim($cookieString, '; ');
        }
        
        if ($this->csrfToken) {
            $headers[] = 'X-CSRF-TOKEN: ' . $this->csrfToken;
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
        curl_close($ch);
        
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
}

// Run the test
$tester = new AuthenticatedAPITester();
$tester->testWithAuth();