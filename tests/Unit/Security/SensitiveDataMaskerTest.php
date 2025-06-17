<?php

namespace Tests\Unit\Security;

use Tests\TestCase;
use App\Services\Security\SensitiveDataMasker;

class SensitiveDataMaskerTest extends TestCase
{
    private SensitiveDataMasker $masker;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->masker = new SensitiveDataMasker();
    }
    
    /** @test */
    public function it_masks_api_keys_in_arrays()
    {
        $data = [
            'api_key' => 'sk_test_12345678901234567890',
            'name' => 'Test User',
            'apiKey' => 'key_abcdefghijklmnop',
            'other' => 'normal data'
        ];
        
        $masked = $this->masker->mask($data);
        
        $this->assertStringStartsWith('sk_', $masked['api_key']);
        $this->assertStringEndsWith('890', $masked['api_key']);
        $this->assertStringContainsString('***', $masked['api_key']);
        $this->assertStringStartsWith('key', $masked['apiKey']);
        $this->assertStringEndsWith('nop', $masked['apiKey']);
        $this->assertStringContainsString('***', $masked['apiKey']);
        $this->assertEquals('Test User', $masked['name']);
        $this->assertEquals('normal data', $masked['other']);
    }
    
    /** @test */
    public function it_masks_nested_sensitive_data()
    {
        $data = [
            'user' => [
                'name' => 'John Doe',
                'credentials' => [
                    'token' => 'tok_1234567890abcdef',
                    'secret' => 'sec_xyz123',
                    'password' => 'mypassword123'
                ]
            ],
            'config' => [
                'webhook_secret' => 'whsec_abcdefghijklmnop'
            ]
        ];
        
        $masked = $this->masker->mask($data);
        
        $this->assertEquals('John Doe', $masked['user']['name']);
        $this->assertStringStartsWith('tok', $masked['user']['credentials']['token']);
        $this->assertStringEndsWith('def', $masked['user']['credentials']['token']);
        $this->assertStringStartsWith('sec', $masked['user']['credentials']['secret']);
        $this->assertStringEndsWith('23', $masked['user']['credentials']['secret']);
        $this->assertStringStartsWith('myp', $masked['user']['credentials']['password']);
        $this->assertStringEndsWith('123', $masked['user']['credentials']['password']);
        $this->assertStringStartsWith('whs', $masked['config']['webhook_secret']);
        $this->assertStringEndsWith('nop', $masked['config']['webhook_secret']);
    }
    
    /** @test */
    public function it_masks_api_keys_in_urls()
    {
        $url = 'https://api.example.com/v1/endpoint?api_key=sk_test_123456789&user=test';
        
        $masked = $this->masker->mask($url);
        
        $this->assertStringContainsString('api_key=***MASKED***', $masked);
        $this->assertStringContainsString('user=test', $masked);
    }
    
    /** @test */
    public function it_masks_bearer_tokens()
    {
        $header = 'Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIn0.dozjgNryP4J3jVmNHl0w5N_XgL0n3I9PlFUP0THsR8U';
        
        $masked = $this->masker->mask($header);
        
        $this->assertEquals('Bearer ***MASKED***', $masked);
    }
    
    /** @test */
    public function it_masks_headers_correctly()
    {
        $headers = [
            'Authorization' => ['Bearer token123'],
            'X-API-Key' => ['secret-key-123'],
            'Content-Type' => ['application/json'],
            'User-Agent' => ['Mozilla/5.0']
        ];
        
        $masked = $this->masker->maskHeaders($headers);
        
        $this->assertEquals(['***MASKED***'], $masked['Authorization']);
        $this->assertEquals(['***MASKED***'], $masked['X-API-Key']);
        $this->assertEquals(['application/json'], $masked['Content-Type']);
        $this->assertEquals(['Mozilla/5.0'], $masked['User-Agent']);
    }
    
    /** @test */
    public function it_masks_exception_data()
    {
        $exception = new \Exception('Failed to connect with api_key=sk_test_123456789');
        
        $masked = $this->masker->maskException($exception);
        
        $this->assertStringContainsString('sk_test_***MASKED***', $masked['message']);
        $this->assertArrayHasKey('trace', $masked);
    }
    
    /** @test */
    public function it_handles_empty_and_null_values()
    {
        $data = [
            'api_key' => '',
            'token' => null,
            'secret' => '    ',
            'password' => 'abc'
        ];
        
        $masked = $this->masker->mask($data);
        
        $this->assertEquals('(empty)', $masked['api_key']);
        $this->assertEquals('(empty)', $masked['token']);
        $this->assertEquals('***MASKED***', $masked['secret']);
        $this->assertEquals('***MASKED***', $masked['password']);
    }
    
    /** @test */
    public function it_masks_calcom_and_retell_specific_keys()
    {
        $data = [
            'calcom_api_key' => 'cal_live_1234567890abcdef',
            'retell_api_key' => 'key_retell_xyz123',
            'RETELL_TOKEN' => 'tok_retell_secret',
            'DEFAULT_CALCOM_API_KEY' => 'cal_test_key'
        ];
        
        $masked = $this->masker->mask($data);
        
        $this->assertStringStartsWith('cal', $masked['calcom_api_key']);
        $this->assertStringEndsWith('def', $masked['calcom_api_key']);
        $this->assertStringContainsString('***', $masked['calcom_api_key']);
        $this->assertStringStartsWith('key', $masked['retell_api_key']);
        $this->assertStringEndsWith('23', $masked['retell_api_key']);
        $this->assertStringStartsWith('tok', $masked['RETELL_TOKEN']);
        $this->assertStringEndsWith('ret', $masked['RETELL_TOKEN']);
        $this->assertStringStartsWith('cal', $masked['DEFAULT_CALCOM_API_KEY']);
        $this->assertStringEndsWith('key', $masked['DEFAULT_CALCOM_API_KEY']);
    }
    
    /** @test */
    public function it_masks_key_patterns_in_strings()
    {
        $text = 'Connection failed with key_abc123def456 and sk_test_789xyz';
        
        $masked = $this->masker->mask($text);
        
        $this->assertStringContainsString('key_***MASKED***', $masked);
        $this->assertStringContainsString('sk_test_***MASKED***', $masked);
    }
}