<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Service;
use App\Services\Database\ConnectionPoolManager;
use App\Services\Provisioning\ProvisioningValidator;
use App\Services\Provisioning\RetellAgentProvisioner;
use App\Services\Validation\PhoneNumberValidator;
use App\Services\Webhook\WebhookDeduplicationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CriticalFixesTest extends TestCase
{
    use RefreshDatabase;
    
    /**
     * Test Database Connection Pooling
     */
    #[Test]
    public function test_database_connection_pooling_works()
    {
        $manager = new ConnectionPoolManager();
        
        // Test getting a connection
        $connection1 = $manager->getConnection();
        $this->assertInstanceOf(\PDO::class, $connection1);
        
        // Test connection reuse
        $manager->releaseConnection($connection1);
        $connection2 = $manager->getConnection();
        
        // Should get the same connection from pool
        $this->assertSame($connection1, $connection2);
    }
    
    /**
     * Test Phone Validation with libphonenumber
     */
    #[Test]
    public function test_phone_validation_works()
    {
        $validator = new PhoneNumberValidator();
        
        // Test valid German number
        $result = $validator->validate('+49 30 12345678', 'DE');
        $this->assertTrue($result->isValid());
        $this->assertEquals('+493012345678', $result->getNormalizedNumber());
        
        // Test invalid number
        $result = $validator->validate('invalid', 'DE');
        $this->assertFalse($result->isValid());
        
        // Test normalization of different formats
        $result = $validator->validate('030 12345678', 'DE');
        $this->assertTrue($result->isValid());
        $this->assertEquals('+493012345678', $result->getNormalizedNumber());
    }
    
    /**
     * Test Webhook Deduplication with Redis
     */
    #[Test]
    public function test_webhook_deduplication_works()
    {
        $service = new WebhookDeduplicationService();
        
        // Create test request
        $request = new Request();
        $request->merge([
            'event' => 'call.ended',
            'call_id' => 'test-123'
        ]);
        
        // First request should not be duplicate
        $isDuplicate = $service->isDuplicate('retell', $request);
        $this->assertFalse($isDuplicate);
        
        // Same request should be duplicate
        $isDuplicate = $service->isDuplicate('retell', $request);
        $this->assertTrue($isDuplicate);
        
        // Different request should not be duplicate
        $request2 = new Request();
        $request2->merge([
            'event' => 'call.ended',
            'call_id' => 'test-456'
        ]);
        
        $isDuplicate = $service->isDuplicate('retell', $request2);
        $this->assertFalse($isDuplicate);
    }
    
    /**
     * Test Provisioning Validation
     */
    #[Test]
    public function test_provisioning_validation_works()
    {
        $validator = new ProvisioningValidator();
        
        // Create test company and branch
        $company = Company::factory()->create();
        $branch = Branch::factory()->create([
            'company_id' => $company->id,
            'phone_number' => null, // Missing phone
            'business_hours' => null, // Missing hours
            'calcom_event_type_id' => null, // Missing calendar
        ]);
        
        // Validate branch
        $result = $validator->validateBranch($branch);
        
        // Should not be valid
        $this->assertFalse($result->isValid());
        
        // Should have specific errors
        $errors = $result->getErrors();
        $errorCodes = array_column($errors, 'code');
        
        $this->assertContains('PHONE_NUMBER_MISSING', $errorCodes);
        $this->assertContains('NO_SERVICES', $errorCodes);
        $this->assertContains('WORKING_HOURS_MISSING', $errorCodes);
        $this->assertContains('CALCOM_EVENT_TYPE_MISSING', $errorCodes);
        
        // Should have recommendations
        $this->assertNotEmpty($result->getRecommendations());
    }
    
    /**
     * Test RetellAgentProvisioner with validation
     */
    #[Test]
    public function test_retell_agent_provisioner_validates_before_creation()
    {
        $provisioner = new RetellAgentProvisioner();
        
        // Create invalid branch
        $company = Company::factory()->create();
        $branch = Branch::factory()->create([
            'company_id' => $company->id,
            'phone_number' => null, // Invalid
        ]);
        
        // Try to create agent
        $result = $provisioner->createAgentForBranch($branch);
        
        // Should fail validation
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Validation failed', $result['error']);
        $this->assertNotEmpty($result['validation_errors']);
    }
    
    /**
     * Test SQLite compatibility in tests
     */
    #[Test]
    public function test_sqlite_migration_compatibility()
    {
        // This test runs with SQLite and should pass if migrations work
        $this->assertDatabaseHas('migrations', [
            'migration' => '2025_06_17_093617_fix_company_json_fields_defaults'
        ]);
        
        // Test that we can create a company with JSON fields
        $company = Company::factory()->create([
            'settings' => ['test' => 'value'],
            'metadata' => ['key' => 'data']
        ]);
        
        $this->assertIsArray($company->settings);
        $this->assertEquals('value', $company->settings['test']);
    }
}