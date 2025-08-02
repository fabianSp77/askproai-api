<?php

namespace Tests\Security;

use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Appointment;
use Illuminate\Foundation\Testing\RefreshDatabase;

class InputValidationSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $company;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->company = Company::factory()->create();
        $this->user = User::factory()->create(['company_id' => $this->company->id]);
        $this->actingAs($this->user);
    }

    /**
     * Test XSS protection in user input.
     */
    public function test_xss_protection_in_user_input()
    {
        $xssPayloads = [
            '<script>alert("XSS")</script>',
            '<img src=x onerror=alert("XSS")>',
            '<svg onload=alert("XSS")>',
            '<iframe src=javascript:alert("XSS")>',
            '<input onfocus=alert("XSS") autofocus>',
            '<select onfocus=alert("XSS") autofocus>',
            '<textarea onfocus=alert("XSS") autofocus>',
            '<button onclick=alert("XSS")>Click</button>',
            '<form action="javascript:alert(\'XSS\')">',
            '<a href="javascript:alert(\'XSS\')">Link</a>',
            '"><script>alert("XSS")</script>',
            '\';alert(String.fromCharCode(88,83,83))//\';alert(String.fromCharCode(88,83,83))//',
            'javascript:/*--></title></style></textarea></script></xmp><svg/onload=\'+/"/+/onmouseover=1/+/[*/[]/+alert(1)//>',
        ];

        foreach ($xssPayloads as $payload) {
            $response = $this->postJson('/api/customers', [
                'first_name' => $payload,
                'last_name' => $payload,
                'email' => 'test' . uniqid() . '@example.com',
                'phone' => '+1234567890',
                'notes' => $payload,
                'company_id' => $this->company->id,
                'branch_id' => 1,
            ]);

            $response->assertStatus(201);
            
            $customer = Customer::find($response->json('data.customer.id'));
            
            // Verify XSS payloads are escaped
            $this->assertNotEquals($payload, $customer->first_name);
            $this->assertNotEquals($payload, $customer->last_name);
            $this->assertNotEquals($payload, $customer->notes);
            
            // Verify HTML entities are encoded
            $this->assertStringNotContainsString('<script>', $customer->first_name);
            $this->assertStringNotContainsString('javascript:', $customer->notes);
        }
    }

    /**
     * Test SQL injection protection.
     */
    public function test_sql_injection_protection()
    {
        $sqlPayloads = [
            "1' OR '1'='1",
            "1'; DROP TABLE customers; --",
            "1' UNION SELECT * FROM users --",
            "admin'--",
            "admin' #",
            "admin'/*",
            "' or 1=1--",
            "' or 1=1#",
            "' or 1=1/*",
            "') or '1'='1--",
            "') or ('1'='1--",
            "1' AND SLEEP(5)--",
            "1' AND BENCHMARK(1000000,MD5('test'))--",
        ];

        foreach ($sqlPayloads as $payload) {
            // Test in search parameter
            $response = $this->getJson("/api/customers?search={$payload}");
            $response->assertStatus(200);
            
            // Test in filter parameter
            $response = $this->getJson("/api/appointments?customer_name={$payload}");
            $response->assertStatus(200);
            
            // Test in POST data
            $response = $this->postJson('/api/customers', [
                'first_name' => $payload,
                'last_name' => 'Test',
                'email' => 'test' . uniqid() . '@example.com',
                'phone' => $payload,
                'company_id' => $this->company->id,
                'branch_id' => 1,
            ]);
            
            // Should either succeed or fail validation, but not cause SQL error
            $this->assertContains($response->status(), [201, 422]);
        }
        
        // Verify database is still intact
        $this->assertDatabaseHas('companies', ['id' => $this->company->id]);
    }

    /**
     * Test path traversal protection.
     */
    public function test_path_traversal_protection()
    {
        $pathTraversalPayloads = [
            '../../../etc/passwd',
            '..\\..\\..\\windows\\system32\\config\\sam',
            '....//....//....//etc/passwd',
            '%2e%2e%2f%2e%2e%2f%2e%2e%2fetc%2fpasswd',
            '..%252f..%252f..%252fetc%252fpasswd',
            '..%c0%af..%c0%af..%c0%afetc%c0%afpasswd',
            'C:\\..\\..\\..\\..\\..\\..\\..\\..\\windows\\system32\\drivers\\etc\\hosts',
            '/var/www/../../etc/passwd',
        ];

        foreach ($pathTraversalPayloads as $payload) {
            // Test file upload with malicious filename
            $response = $this->postJson('/api/upload', [
                'file' => $payload,
                'type' => 'document',
            ]);
            
            // Should be rejected
            $this->assertContains($response->status(), [400, 422]);
            
            // Test in API parameters
            $response = $this->getJson("/api/files?path={$payload}");
            $this->assertContains($response->status(), [400, 403, 404]);
        }
    }

    /**
     * Test command injection protection.
     */
    public function test_command_injection_protection()
    {
        $commandPayloads = [
            '; cat /etc/passwd',
            '| cat /etc/passwd',
            '`cat /etc/passwd`',
            '$(cat /etc/passwd)',
            '; ls -la',
            '&& whoami',
            '|| ping -c 10 127.0.0.1',
            '\n/bin/ls -la',
            '%0a/bin/ls -la',
        ];

        foreach ($commandPayloads as $payload) {
            $response = $this->postJson('/api/appointments', [
                'customer_id' => Customer::factory()->create(['company_id' => $this->company->id])->id,
                'service_id' => 1,
                'staff_id' => 1,
                'branch_id' => 1,
                'starts_at' => now()->addDay()->toDateTimeString(),
                'ends_at' => now()->addDay()->addHour()->toDateTimeString(),
                'notes' => $payload,
            ]);
            
            // Should either succeed or fail validation, but not execute commands
            $this->assertContains($response->status(), [201, 422]);
        }
    }

    /**
     * Test LDAP injection protection.
     */
    public function test_ldap_injection_protection()
    {
        $ldapPayloads = [
            '*',
            '*)(&',
            '*)(uid=*',
            '*)(|(uid=*',
            '*))(|(uid=*',
            'admin)(&(password=*)',
            'admin)(!(&(password=*)',
        ];

        foreach ($ldapPayloads as $payload) {
            $response = $this->postJson('/api/login', [
                'email' => $payload . '@example.com',
                'password' => $payload,
            ]);
            
            // Should fail authentication, not cause LDAP error
            $this->assertContains($response->status(), [422, 401]);
        }
    }

    /**
     * Test XML injection protection.
     */
    public function test_xml_injection_protection()
    {
        $xmlPayloads = [
            '<?xml version="1.0"?><!DOCTYPE foo [<!ENTITY xxe SYSTEM "file:///etc/passwd">]><foo>&xxe;</foo>',
            '<?xml version="1.0"?><!DOCTYPE foo [<!ENTITY xxe SYSTEM "http://evil.com/xxe">]><foo>&xxe;</foo>',
            '<![CDATA[<script>alert("XSS")</script>]]>',
        ];

        foreach ($xmlPayloads as $payload) {
            $response = $this->postJson('/api/import', [
                'data' => $payload,
                'format' => 'xml',
            ]);
            
            // Should be rejected
            $this->assertContains($response->status(), [400, 422]);
        }
    }

    /**
     * Test NoSQL injection protection.
     */
    public function test_nosql_injection_protection()
    {
        $nosqlPayloads = [
            ['$ne' => null],
            ['$gt' => ''],
            ['$regex' => '.*'],
            ['$where' => 'this.password.match(/.*/)'],
        ];

        foreach ($nosqlPayloads as $payload) {
            $response = $this->postJson('/api/search', [
                'query' => $payload,
                'filters' => $payload,
            ]);
            
            // Should handle safely
            $this->assertContains($response->status(), [200, 400, 422]);
        }
    }

    /**
     * Test input length validation.
     */
    public function test_input_length_validation()
    {
        // Test extremely long input
        $longString = str_repeat('A', 10000);
        
        $response = $this->postJson('/api/customers', [
            'first_name' => $longString,
            'last_name' => $longString,
            'email' => $longString . '@example.com',
            'phone' => $longString,
            'notes' => $longString,
            'company_id' => $this->company->id,
            'branch_id' => 1,
        ]);
        
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['first_name', 'last_name', 'email', 'phone']);
    }

    /**
     * Test special character handling.
     */
    public function test_special_character_handling()
    {
        $specialChars = [
            'Test & Company',
            'O\'Brien',
            'José García',
            'Müller GmbH',
            '株式会社テスト',
            'Test <> Company',
            'Test "Quote" Company',
            'Test\nNewline\nCompany',
            'Test\tTab\tCompany',
            'Test\0Null\0Company',
        ];

        foreach ($specialChars as $chars) {
            $response = $this->postJson('/api/customers', [
                'first_name' => $chars,
                'last_name' => 'Test',
                'email' => 'test' . uniqid() . '@example.com',
                'phone' => '+1234567890',
                'company_id' => $this->company->id,
                'branch_id' => 1,
            ]);
            
            $response->assertStatus(201);
            
            $customer = Customer::find($response->json('data.customer.id'));
            
            // Verify special characters are preserved safely
            $this->assertNotNull($customer->first_name);
        }
    }

    /**
     * Test file upload validation.
     */
    public function test_file_upload_validation()
    {
        // Test dangerous file extensions
        $dangerousExtensions = [
            'test.php',
            'test.exe',
            'test.sh',
            'test.bat',
            'test.cmd',
            'test.com',
            'test.scr',
            'test.vbs',
            'test.js',
            'test.jar',
            'test.jsp',
            'test.asp',
        ];

        foreach ($dangerousExtensions as $filename) {
            $response = $this->postJson('/api/upload', [
                'file' => \Illuminate\Http\Testing\File::fake()->create($filename, 100),
            ]);
            
            // Should be rejected
            $response->assertStatus(422)
                ->assertJsonValidationErrors(['file']);
        }
        
        // Test allowed extensions
        $allowedExtensions = [
            'document.pdf',
            'image.jpg',
            'image.png',
            'document.docx',
        ];

        foreach ($allowedExtensions as $filename) {
            $response = $this->postJson('/api/upload', [
                'file' => \Illuminate\Http\Testing\File::fake()->create($filename, 100),
            ]);
            
            // Should be accepted or fail for other reasons
            $this->assertNotEquals(422, $response->status());
        }
    }

    /**
     * Test JSON injection protection.
     */
    public function test_json_injection_protection()
    {
        $jsonPayloads = [
            '{"__proto__":{"isAdmin":true}}',
            '{"constructor":{"prototype":{"isAdmin":true}}}',
            '{"toString":{"__proto__":{"isAdmin":true}}}',
        ];

        foreach ($jsonPayloads as $payload) {
            $response = $this->postJson('/api/data', [
                'data' => json_decode($payload, true),
            ]);
            
            // Should handle safely
            $this->assertContains($response->status(), [200, 400, 422]);
        }
    }

    /**
     * Test integer overflow protection.
     */
    public function test_integer_overflow_protection()
    {
        $overflowValues = [
            PHP_INT_MAX + 1,
            '99999999999999999999999999999999',
            '-99999999999999999999999999999999',
            '2147483648', // Max 32-bit int + 1
        ];

        foreach ($overflowValues as $value) {
            $response = $this->postJson('/api/appointments', [
                'customer_id' => $value,
                'service_id' => $value,
                'staff_id' => $value,
                'branch_id' => $value,
                'duration' => $value,
                'starts_at' => now()->addDay()->toDateTimeString(),
                'ends_at' => now()->addDay()->addHour()->toDateTimeString(),
            ]);
            
            // Should fail validation
            $response->assertStatus(422);
        }
    }

    /**
     * Test Unicode and encoding attacks.
     */
    public function test_unicode_and_encoding_attacks()
    {
        $unicodePayloads = [
            "\xC0\xAF", // Overlong encoding of '/'
            "\xE0\x80\xAF", // Overlong encoding of '/'
            "\xF0\x80\x80\xAF", // Overlong encoding of '/'
            "test\x00null", // Null byte injection
            "test\r\nSet-Cookie: admin=true", // CRLF injection
            "\u202E\u0065\u0078\u0065\u002E\u0067\u006E\u0070", // Right-to-left override
        ];

        foreach ($unicodePayloads as $payload) {
            $response = $this->postJson('/api/customers', [
                'first_name' => $payload,
                'last_name' => 'Test',
                'email' => 'test' . uniqid() . '@example.com',
                'phone' => '+1234567890',
                'company_id' => $this->company->id,
                'branch_id' => 1,
            ]);
            
            // Should handle safely
            $this->assertContains($response->status(), [201, 422]);
        }
    }
}