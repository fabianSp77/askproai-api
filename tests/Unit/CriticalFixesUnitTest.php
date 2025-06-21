<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\Validation\PhoneNumberValidator;
use App\Services\Webhook\WebhookDeduplicationService;
use App\Services\Provisioning\ProvisioningValidator;
use Illuminate\Http\Request;

class CriticalFixesUnitTest extends TestCase
{
    /**
     * Test Phone Validation
     */
    public function test_phone_validation()
    {
        $validator = new PhoneNumberValidator();
        
        // Test valid German number
        $result = $validator->validate('+49 30 12345678', 'DE');
        $this->assertTrue($result['valid']);
        $this->assertEquals('+493012345678', $result['normalized']);
        
        // Test invalid number format (throws exception during sanitization)
        try {
            $result = $validator->validate('invalid', 'DE');
            $this->fail('Expected InvalidArgumentException was not thrown');
        } catch (\InvalidArgumentException $e) {
            $this->assertEquals('Invalid phone number format', $e->getMessage());
        }
        
        // Test invalid but properly formatted number
        $result = $validator->validate('+49 1234', 'DE');
        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('error', $result);
    }
    
    /**
     * Test Webhook Deduplication Service initialization
     */
    public function test_webhook_deduplication_service_creation()
    {
        $service = new WebhookDeduplicationService();
        $this->assertInstanceOf(WebhookDeduplicationService::class, $service);
    }
    
    /**
     * Test Provisioning Validator initialization
     */
    public function test_provisioning_validator_creation()
    {
        $validator = new ProvisioningValidator();
        $this->assertInstanceOf(ProvisioningValidator::class, $validator);
    }
}