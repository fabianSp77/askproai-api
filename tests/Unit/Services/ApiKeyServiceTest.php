<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\ApiKeyService;
use App\Models\Tenant;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ApiKeyServiceTest extends TestCase
{
    use RefreshDatabase;

    private ApiKeyService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ApiKeyService();
    }

    /** @test */
    public function it_can_generate_api_key()
    {
        // Act
        $apiKey = $this->service->generate();

        // Assert
        $this->assertIsString($apiKey);
        $this->assertStringStartsWith('ask_', $apiKey);
        $this->assertEquals(36, strlen($apiKey)); // ask_ + 32 random chars
        $this->assertMatchesRegularExpression('/^ask_[a-zA-Z0-9]{32}$/', $apiKey);
    }

    /** @test */
    public function it_generates_unique_api_keys()
    {
        // Act
        $key1 = $this->service->generate();
        $key2 = $this->service->generate();
        $key3 = $this->service->generate();

        // Assert
        $this->assertNotEquals($key1, $key2);
        $this->assertNotEquals($key1, $key3);
        $this->assertNotEquals($key2, $key3);
    }

    /** @test */
    public function it_can_hash_api_key()
    {
        // Arrange
        $plainKey = 'ask_test123456789012345678901234';

        // Act
        $hashedKey = $this->service->hash($plainKey);

        // Assert
        $this->assertIsString($hashedKey);
        $this->assertNotEquals($plainKey, $hashedKey);
        $this->assertTrue(Hash::check($plainKey, $hashedKey));
    }

    /** @test */
    public function it_can_verify_api_key()
    {
        // Arrange
        $plainKey = 'ask_test123456789012345678901234';
        $hashedKey = $this->service->hash($plainKey);

        // Act & Assert
        $this->assertTrue($this->service->verify($plainKey, $hashedKey));
        $this->assertFalse($this->service->verify('wrong_key', $hashedKey));
        $this->assertFalse($this->service->verify('ask_wrong12345678901234567890123', $hashedKey));
    }

    /** @test */
    public function it_validates_api_key_format()
    {
        // Valid keys
        $this->assertTrue($this->service->isValidFormat('ask_' . str_repeat('a', 32)));
        $this->assertTrue($this->service->isValidFormat('ask_abcdef1234567890abcdef1234567890'));

        // Invalid keys
        $this->assertFalse($this->service->isValidFormat('invalid_key'));
        $this->assertFalse($this->service->isValidFormat('ask_short'));
        $this->assertFalse($this->service->isValidFormat('ask_' . str_repeat('a', 31))); // Too short
        $this->assertFalse($this->service->isValidFormat('ask_' . str_repeat('a', 33))); // Too long
        $this->assertFalse($this->service->isValidFormat('wrong_' . str_repeat('a', 32))); // Wrong prefix
        $this->assertFalse($this->service->isValidFormat('')); // Empty
        $this->assertFalse($this->service->isValidFormat('ask_123456789012345678901234567890@#')); // Invalid chars
    }

    /** @test */
    public function it_can_generate_and_store_api_key_for_tenant()
    {
        // Arrange
        $tenant = Tenant::factory()->create([
            'name' => 'Test Tenant',
            'api_key_hash' => null
        ]);

        // Act
        $plainKey = $this->service->generateForTenant($tenant);

        // Assert
        $this->assertIsString($plainKey);
        $this->assertStringStartsWith('ask_', $plainKey);
        
        $tenant->refresh();
        $this->assertNotNull($tenant->api_key_hash);
        $this->assertTrue($this->service->verify($plainKey, $tenant->api_key_hash));
    }

    /** @test */
    public function it_can_regenerate_api_key_for_tenant()
    {
        // Arrange
        $tenant = Tenant::factory()->create();
        $originalKey = $this->service->generateForTenant($tenant);
        $originalHash = $tenant->api_key_hash;

        // Act
        $newKey = $this->service->regenerateForTenant($tenant);

        // Assert
        $this->assertIsString($newKey);
        $this->assertStringStartsWith('ask_', $newKey);
        $this->assertNotEquals($originalKey, $newKey);
        
        $tenant->refresh();
        $this->assertNotEquals($originalHash, $tenant->api_key_hash);
        $this->assertTrue($this->service->verify($newKey, $tenant->api_key_hash));
        $this->assertFalse($this->service->verify($originalKey, $tenant->api_key_hash));
    }

    /** @test */
    public function it_can_find_tenant_by_api_key()
    {
        // Arrange
        $tenant1 = Tenant::factory()->create(['name' => 'Tenant 1']);
        $tenant2 = Tenant::factory()->create(['name' => 'Tenant 2']);
        
        $key1 = $this->service->generateForTenant($tenant1);
        $key2 = $this->service->generateForTenant($tenant2);

        // Act & Assert
        $foundTenant1 = $this->service->findTenantByApiKey($key1);
        $foundTenant2 = $this->service->findTenantByApiKey($key2);
        $notFound = $this->service->findTenantByApiKey('ask_invalid_key_123456789012345678');

        $this->assertNotNull($foundTenant1);
        $this->assertEquals($tenant1->id, $foundTenant1->id);
        
        $this->assertNotNull($foundTenant2);
        $this->assertEquals($tenant2->id, $foundTenant2->id);
        
        $this->assertNull($notFound);
    }

    /** @test */
    public function it_handles_malformed_api_keys_gracefully()
    {
        // Arrange
        $tenant = Tenant::factory()->create();
        $validKey = $this->service->generateForTenant($tenant);

        // Act & Assert
        $this->assertNull($this->service->findTenantByApiKey(''));
        $this->assertNull($this->service->findTenantByApiKey('invalid'));
        $this->assertNull($this->service->findTenantByApiKey('ask_')); // Too short
        $this->assertNull($this->service->findTenantByApiKey(null));
    }

    /** @test */
    public function it_can_revoke_api_key()
    {
        // Arrange
        $tenant = Tenant::factory()->create();
        $apiKey = $this->service->generateForTenant($tenant);
        
        // Verify key works initially
        $this->assertNotNull($this->service->findTenantByApiKey($apiKey));

        // Act
        $result = $this->service->revokeForTenant($tenant);

        // Assert
        $this->assertTrue($result);
        $tenant->refresh();
        $this->assertNull($tenant->api_key_hash);
        $this->assertNull($this->service->findTenantByApiKey($apiKey));
    }

    /** @test */
    public function it_handles_concurrent_api_key_generation()
    {
        // This test simulates concurrent requests trying to generate API keys
        // Arrange
        $tenant = Tenant::factory()->create();
        $keys = [];

        // Act - Generate multiple keys concurrently (simulated)
        for ($i = 0; $i < 5; $i++) {
            $keys[] = $this->service->generate();
        }

        // Assert - All keys should be unique
        $uniqueKeys = array_unique($keys);
        $this->assertCount(5, $uniqueKeys);
        
        foreach ($keys as $key) {
            $this->assertStringStartsWith('ask_', $key);
            $this->assertTrue($this->service->isValidFormat($key));
        }
    }

    /** @test */
    public function it_provides_api_key_metadata()
    {
        // Arrange
        $tenant = Tenant::factory()->create();
        $apiKey = $this->service->generateForTenant($tenant);

        // Act
        $metadata = $this->service->getKeyMetadata($tenant);

        // Assert
        $this->assertIsArray($metadata);
        $this->assertArrayHasKey('created_at', $metadata);
        $this->assertArrayHasKey('last_used', $metadata);
        $this->assertArrayHasKey('has_key', $metadata);
        $this->assertTrue($metadata['has_key']);
    }

    /** @test */
    public function it_can_update_last_used_timestamp()
    {
        // Arrange
        $tenant = Tenant::factory()->create();
        $apiKey = $this->service->generateForTenant($tenant);

        // Act
        $result = $this->service->updateLastUsed($tenant);

        // Assert
        $this->assertTrue($result);
        // In a real implementation, you might have a last_used_at field
        // This would test that the timestamp is updated
    }
}