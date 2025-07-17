<?php

namespace Tests\Unit\Services\Cache;

use Tests\TestCase;
use App\Services\Cache\CacheManager;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class CacheManagerTest extends TestCase
{
    private CacheManager $cacheManager;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->cacheManager = new CacheManager();
        
        // Clear all cache layers
        Cache::flush();
        Redis::flushall();
    }

    /** @test */
    public function it_implements_multi_tier_caching()
    {
        // Arrange
        $key = 'test_key';
        $value = ['data' => 'test_value'];
        
        // Act
        $this->cacheManager->put($key, $value, 300);
        
        // Assert - Value should be in L1 (memory)
        $l1Value = $this->cacheManager->getFromL1($key);
        $this->assertEquals($value, $l1Value);
        
        // Assert - Value should be in L2 (Redis)
        $l2Value = Cache::get($key);
        $this->assertEquals($value, $l2Value);
    }

    /** @test */
    public function it_retrieves_from_fastest_available_layer()
    {
        // Arrange
        $key = 'test_key';
        $value = 'test_value';
        
        // Put in L2 only
        Cache::put($key, $value, 300);
        
        // Act - First get should populate L1
        $result1 = $this->cacheManager->get($key);
        
        // Clear L2 to ensure second get comes from L1
        Cache::forget($key);
        $result2 = $this->cacheManager->get($key);
        
        // Assert
        $this->assertEquals($value, $result1);
        $this->assertEquals($value, $result2);
    }

    /** @test */
    public function it_handles_compression_for_large_data()
    {
        // Arrange
        $key = 'large_data';
        $largeData = str_repeat('x', 10000); // 10KB of data
        
        // Act
        $this->cacheManager->put($key, $largeData, 300, ['compress' => true]);
        $retrieved = $this->cacheManager->get($key);
        
        // Assert
        $this->assertEquals($largeData, $retrieved);
        
        // Verify compression occurred (compressed size should be smaller)
        $compressedSize = strlen(Cache::get($key . ':compressed'));
        $this->assertLessThan(10000, $compressedSize);
    }

    /** @test */
    public function it_supports_batch_operations()
    {
        // Arrange
        $items = [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 'value3',
        ];
        
        // Act - Batch put
        $this->cacheManager->putMany($items, 300);
        
        // Act - Batch get
        $retrieved = $this->cacheManager->getMany(array_keys($items));
        
        // Assert
        $this->assertEquals($items, $retrieved);
    }

    /** @test */
    public function it_invalidates_by_pattern()
    {
        // Arrange
        Cache::put('user:1:profile', 'profile1', 300);
        Cache::put('user:1:settings', 'settings1', 300);
        Cache::put('user:2:profile', 'profile2', 300);
        Cache::put('company:1:data', 'data1', 300);
        
        // Act - Invalidate all user:1:* keys
        $this->cacheManager->forgetByPattern('user:1:*');
        
        // Assert
        $this->assertNull(Cache::get('user:1:profile'));
        $this->assertNull(Cache::get('user:1:settings'));
        $this->assertEquals('profile2', Cache::get('user:2:profile'));
        $this->assertEquals('data1', Cache::get('company:1:data'));
    }

    /** @test */
    public function it_implements_cache_warming()
    {
        // Arrange
        $warmer = function () {
            return [
                'config:app' => ['name' => 'AskProAI'],
                'config:cache' => ['driver' => 'redis'],
            ];
        };
        
        // Act
        $this->cacheManager->warm($warmer);
        
        // Assert
        $this->assertEquals(['name' => 'AskProAI'], $this->cacheManager->get('config:app'));
        $this->assertEquals(['driver' => 'redis'], $this->cacheManager->get('config:cache'));
    }

    /** @test */
    public function it_provides_cache_statistics()
    {
        // Arrange
        $this->cacheManager->put('key1', 'value1', 300);
        $this->cacheManager->put('key2', 'value2', 300);
        
        // Some hits and misses
        $this->cacheManager->get('key1'); // Hit
        $this->cacheManager->get('key1'); // Hit
        $this->cacheManager->get('key3'); // Miss
        
        // Act
        $stats = $this->cacheManager->getStats();
        
        // Assert
        $this->assertArrayHasKey('hits', $stats);
        $this->assertArrayHasKey('misses', $stats);
        $this->assertArrayHasKey('hit_rate', $stats);
        $this->assertEquals(2, $stats['hits']);
        $this->assertEquals(1, $stats['misses']);
        $this->assertEquals(0.67, round($stats['hit_rate'], 2));
    }

    /** @test */
    public function it_supports_atomic_operations()
    {
        // Arrange
        $key = 'counter';
        $this->cacheManager->put($key, 0, 300);
        
        // Act - Increment
        $result1 = $this->cacheManager->increment($key);
        $result2 = $this->cacheManager->increment($key, 5);
        
        // Act - Decrement
        $result3 = $this->cacheManager->decrement($key, 2);
        
        // Assert
        $this->assertEquals(1, $result1);
        $this->assertEquals(6, $result2);
        $this->assertEquals(4, $result3);
        $this->assertEquals(4, $this->cacheManager->get($key));
    }

    /** @test */
    public function it_handles_cache_locks()
    {
        // Arrange
        $key = 'resource';
        $lockAcquired = false;
        
        // Act
        $lock = $this->cacheManager->lock($key, 10);
        
        if ($lock->get()) {
            $lockAcquired = true;
            
            // Try to acquire same lock (should fail)
            $secondLock = $this->cacheManager->lock($key, 10);
            $secondLockAcquired = $secondLock->get();
            
            $lock->release();
        }
        
        // Assert
        $this->assertTrue($lockAcquired);
        $this->assertFalse($secondLockAcquired);
    }

    /** @test */
    public function it_implements_remember_with_callback()
    {
        // Arrange
        $key = 'expensive_operation';
        $executionCount = 0;
        
        $callback = function () use (&$executionCount) {
            $executionCount++;
            return 'expensive_result';
        };
        
        // Act
        $result1 = $this->cacheManager->remember($key, 300, $callback);
        $result2 = $this->cacheManager->remember($key, 300, $callback);
        
        // Assert
        $this->assertEquals('expensive_result', $result1);
        $this->assertEquals('expensive_result', $result2);
        $this->assertEquals(1, $executionCount); // Callback executed only once
    }

    /** @test */
    public function it_supports_tagged_caching()
    {
        // Skip if cache driver doesn't support tags
        if (!Cache::supportsTags()) {
            $this->markTestSkipped('Cache driver does not support tags');
        }
        
        // Arrange
        $this->cacheManager->tags(['users', 'company:1'])->put('user:1', 'John', 300);
        $this->cacheManager->tags(['users', 'company:1'])->put('user:2', 'Jane', 300);
        $this->cacheManager->tags(['users', 'company:2'])->put('user:3', 'Bob', 300);
        
        // Act - Flush by tag
        $this->cacheManager->tags(['company:1'])->flush();
        
        // Assert
        $this->assertNull($this->cacheManager->tags(['users', 'company:1'])->get('user:1'));
        $this->assertNull($this->cacheManager->tags(['users', 'company:1'])->get('user:2'));
        $this->assertEquals('Bob', $this->cacheManager->tags(['users', 'company:2'])->get('user:3'));
    }

    protected function tearDown(): void
    {
        Cache::flush();
        Redis::flushall();
        parent::tearDown();
    }
}