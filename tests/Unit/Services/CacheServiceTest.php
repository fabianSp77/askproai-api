<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\CacheService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CacheServiceTest extends TestCase
{
    use RefreshDatabase;

    private CacheService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CacheService();
        Cache::flush();
    }

    /** @test */
    public function it_can_cache_and_retrieve_data()
    {
        // Arrange
        $key = 'test_key';
        $value = ['data' => 'test_value', 'timestamp' => now()];
        $ttl = 3600;

        // Act
        $this->service->put($key, $value, $ttl);
        $retrieved = $this->service->get($key);

        // Assert
        $this->assertEquals($value, $retrieved);
        $this->assertTrue(Cache::has($key));
    }

    /** @test */
    public function it_returns_default_when_key_not_found()
    {
        // Arrange
        $key = 'non_existent_key';
        $default = 'default_value';

        // Act
        $result = $this->service->get($key, $default);

        // Assert
        $this->assertEquals($default, $result);
    }

    /** @test */
    public function it_can_remember_data_with_callback()
    {
        // Arrange
        $key = 'callback_key';
        $expectedValue = 'computed_value';
        $ttl = 1800;

        // Act
        $result = $this->service->remember($key, $ttl, function () use ($expectedValue) {
            return $expectedValue;
        });

        // Assert
        $this->assertEquals($expectedValue, $result);
        $this->assertTrue(Cache::has($key));
        $this->assertEquals($expectedValue, $this->service->get($key));
    }

    /** @test */
    public function it_does_not_call_callback_if_key_exists()
    {
        // Arrange
        $key = 'existing_key';
        $existingValue = 'existing_value';
        $callbackCalled = false;

        $this->service->put($key, $existingValue, 3600);

        // Act
        $result = $this->service->remember($key, 3600, function () use (&$callbackCalled) {
            $callbackCalled = true;
            return 'should_not_be_called';
        });

        // Assert
        $this->assertEquals($existingValue, $result);
        $this->assertFalse($callbackCalled);
    }

    /** @test */
    public function it_can_forget_cache_keys()
    {
        // Arrange
        $key = 'key_to_forget';
        $value = 'test_value';

        $this->service->put($key, $value, 3600);
        $this->assertTrue(Cache::has($key));

        // Act
        $result = $this->service->forget($key);

        // Assert
        $this->assertTrue($result);
        $this->assertFalse(Cache::has($key));
        $this->assertNull($this->service->get($key));
    }

    /** @test */
    public function it_can_flush_all_cache()
    {
        // Arrange
        $this->service->put('key1', 'value1', 3600);
        $this->service->put('key2', 'value2', 3600);
        
        $this->assertTrue(Cache::has('key1'));
        $this->assertTrue(Cache::has('key2'));

        // Act
        $result = $this->service->flush();

        // Assert
        $this->assertTrue($result);
        $this->assertFalse(Cache::has('key1'));
        $this->assertFalse(Cache::has('key2'));
    }

    /** @test */
    public function it_handles_cache_tags()
    {
        // Arrange
        $tags = ['tenant_123', 'calls'];
        $key = 'tagged_key';
        $value = 'tagged_value';

        // Act
        $this->service->tags($tags)->put($key, $value, 3600);

        // Assert
        $this->assertEquals($value, $this->service->tags($tags)->get($key));
    }

    /** @test */
    public function it_can_flush_tagged_cache()
    {
        // Arrange
        $tags = ['tenant_123'];
        $key1 = 'tagged_key_1';
        $key2 = 'tagged_key_2';
        $untaggedKey = 'untagged_key';

        $this->service->tags($tags)->put($key1, 'value1', 3600);
        $this->service->tags($tags)->put($key2, 'value2', 3600);
        $this->service->put($untaggedKey, 'untagged_value', 3600);

        // Act
        $result = $this->service->flushTags($tags);

        // Assert
        $this->assertTrue($result);
        $this->assertNull($this->service->tags($tags)->get($key1));
        $this->assertNull($this->service->tags($tags)->get($key2));
        $this->assertEquals('untagged_value', $this->service->get($untaggedKey));
    }

    /** @test */
    public function it_generates_tenant_specific_cache_keys()
    {
        // Arrange
        $tenantId = 'tenant_123';
        $baseKey = 'user_data';

        // Act
        $tenantKey = $this->service->tenantKey($tenantId, $baseKey);

        // Assert
        $this->assertEquals('tenant_123:user_data', $tenantKey);
    }

    /** @test */
    public function it_can_increment_counter()
    {
        // Arrange
        $key = 'counter_key';

        // Act
        $first = $this->service->increment($key);
        $second = $this->service->increment($key, 5);

        // Assert
        $this->assertEquals(1, $first);
        $this->assertEquals(6, $second);
        $this->assertEquals(6, $this->service->get($key));
    }

    /** @test */
    public function it_can_decrement_counter()
    {
        // Arrange
        $key = 'decrement_key';
        $this->service->put($key, 10, 3600);

        // Act
        $first = $this->service->decrement($key);
        $second = $this->service->decrement($key, 3);

        // Assert
        $this->assertEquals(9, $first);
        $this->assertEquals(6, $second);
        $this->assertEquals(6, $this->service->get($key));
    }

    /** @test */
    public function it_handles_cache_serialization_correctly()
    {
        // Arrange
        $key = 'serialization_key';
        $complexData = [
            'array' => [1, 2, 3],
            'object' => (object) ['prop' => 'value'],
            'nested' => [
                'level1' => [
                    'level2' => 'deep_value'
                ]
            ]
        ];

        // Act
        $this->service->put($key, $complexData, 3600);
        $retrieved = $this->service->get($key);

        // Assert
        $this->assertEquals($complexData, $retrieved);
        $this->assertIsArray($retrieved['array']);
        $this->assertIsObject($retrieved['object']);
    }

    /** @test */
    public function it_handles_cache_expiration()
    {
        // Arrange
        $key = 'expiring_key';
        $value = 'expiring_value';
        $shortTtl = 1; // 1 second

        // Act
        $this->service->put($key, $value, $shortTtl);
        $this->assertEquals($value, $this->service->get($key));

        sleep(2); // Wait for expiration

        // Assert
        $this->assertNull($this->service->get($key));
    }

    /** @test */
    public function it_provides_cache_statistics()
    {
        // Arrange
        $this->service->put('stats_key_1', 'value1', 3600);
        $this->service->put('stats_key_2', 'value2', 3600);
        $this->service->get('stats_key_1'); // Hit
        $this->service->get('non_existent'); // Miss

        // Act
        $stats = $this->service->getStats();

        // Assert
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('keys_count', $stats);
        $this->assertArrayHasKey('memory_usage', $stats);
    }
}