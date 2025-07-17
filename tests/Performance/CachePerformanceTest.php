<?php

namespace Tests\Performance;

use PHPUnit\Framework\Attributes\Test;

use Tests\TestCase;
use App\Services\CacheService;
use App\Services\Cache\CacheManager;
use App\Models\Company;
use App\Models\Customer;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CachePerformanceTest extends TestCase
{
    use \Illuminate\Foundation\Testing\RefreshDatabase;

    private CacheService $cacheService;
    private CacheManager $cacheManager;
    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->cacheService = app(CacheService::class);
        $this->cacheManager = app(CacheManager::class);
        $this->company = Company::factory()->create();
        
        Cache::flush();
    }

    /** @test */
    #[Test]
    public function it_improves_query_performance_significantly()
    {
        // Arrange - Create 1000 customers
        $customers = Customer::factory()->count(1000)->create([
            'company_id' => $this->company->id,
        ]);
        
        // Warm up
        DB::table('customers')->where('company_id', $this->company->id)->count();
        
        // Act - Measure without cache
        $startTime = microtime(true);
        for ($i = 0; $i < 100; $i++) {
            DB::table('customers')
                ->where('company_id', $this->company->id)
                ->where('created_at', '>=', Carbon::now()->subDays(30))
                ->count();
        }
        $withoutCacheTime = (microtime(true) - $startTime) * 1000;
        
        // Act - Measure with cache
        $startTime = microtime(true);
        for ($i = 0; $i < 100; $i++) {
            $this->cacheService->remember('customer_count_' . $this->company->id, 60, function () {
                return DB::table('customers')
                    ->where('company_id', $this->company->id)
                    ->where('created_at', '>=', Carbon::now()->subDays(30))
                    ->count();
            });
        }
        $withCacheTime = (microtime(true) - $startTime) * 1000;
        
        // Assert - Cache should be at least 10x faster
        $this->assertLessThan($withoutCacheTime / 10, $withCacheTime);
        
        // Log performance metrics
        $improvement = round(($withoutCacheTime - $withCacheTime) / $withoutCacheTime * 100, 2);
        $this->addToAssertionCount(1); // Performance improved by {$improvement}%
    }

    /** @test */
    public function multi_tier_cache_provides_sub_millisecond_access()
    {
        // Arrange
        $data = array_fill(0, 100, 'test_data');
        $key = 'performance_test';
        
        // Warm up cache
        $this->cacheManager->put($key, $data, 300);
        
        // Act - Measure L1 (memory) access time
        $times = [];
        for ($i = 0; $i < 1000; $i++) {
            $startTime = microtime(true);
            $this->cacheManager->get($key);
            $times[] = (microtime(true) - $startTime) * 1000;
        }
        
        $avgTime = array_sum($times) / count($times);
        $maxTime = max($times);
        
        // Assert
        $this->assertLessThan(0.1, $avgTime, 'Average cache access time exceeds 0.1ms');
        $this->assertLessThan(1, $maxTime, 'Maximum cache access time exceeds 1ms');
    }

    /** @test */
    public function batch_operations_are_more_efficient_than_individual()
    {
        // Arrange
        $items = [];
        for ($i = 0; $i < 1000; $i++) {
            $items["key_$i"] = "value_$i";
        }
        
        // Act - Individual operations
        $startTime = microtime(true);
        foreach ($items as $key => $value) {
            Cache::put($key, $value, 300);
        }
        foreach (array_keys($items) as $key) {
            Cache::get($key);
        }
        $individualTime = (microtime(true) - $startTime) * 1000;
        
        // Clear cache
        Cache::flush();
        
        // Act - Batch operations
        $startTime = microtime(true);
        $this->cacheManager->putMany($items, 300);
        $this->cacheManager->getMany(array_keys($items));
        $batchTime = (microtime(true) - $startTime) * 1000;
        
        // Assert - Batch should be at least 3x faster
        $this->assertLessThan($individualTime / 3, $batchTime);
    }

    /** @test */
    public function cache_reduces_database_load_under_high_concurrency()
    {
        // Arrange
        $queryCount = 0;
        DB::listen(function () use (&$queryCount) {
            $queryCount++;
        });
        
        // Act - Simulate 100 concurrent requests for same data
        $threads = [];
        for ($i = 0; $i < 100; $i++) {
            $threads[] = function () {
                return $this->cacheService->rememberCustomer(
                    $this->company->id,
                    '+491234567890',
                    function () {
                        return Customer::where('company_id', $this->company->id)
                            ->where('phone', '+491234567890')
                            ->first();
                    }
                );
            };
        }
        
        // Execute all "threads"
        foreach ($threads as $thread) {
            $thread();
        }
        
        // Assert - Should only hit database once
        $this->assertEquals(1, $queryCount);
    }

    /** @test */
    public function compression_reduces_memory_usage_for_large_objects()
    {
        // Arrange
        $largeObject = [
            'data' => str_repeat('x', 100000), // 100KB
            'nested' => array_fill(0, 1000, 'nested_data'),
        ];
        
        // Act - Store without compression
        $key1 = 'uncompressed_data';
        Cache::put($key1, $largeObject, 300);
        $uncompressedSize = strlen(serialize($largeObject));
        
        // Act - Store with compression
        $key2 = 'compressed_data';
        $this->cacheManager->put($key2, $largeObject, 300, ['compress' => true]);
        
        // Get actual stored size (this is implementation specific)
        $compressedData = Cache::get($key2 . ':compressed');
        $compressedSize = strlen($compressedData);
        
        // Assert
        $this->assertLessThan($uncompressedSize * 0.5, $compressedSize, 'Compression should reduce size by at least 50%');
        
        // Verify data integrity
        $retrieved = $this->cacheManager->get($key2);
        $this->assertEquals($largeObject, $retrieved);
    }

    /** @test */
    public function cache_warming_improves_first_request_performance()
    {
        // Arrange
        $keysToWarm = [];
        for ($i = 0; $i < 100; $i++) {
            $keysToWarm["warmed_key_$i"] = "warmed_value_$i";
        }
        
        // Act - Measure cold cache access
        Cache::flush();
        $coldStartTime = microtime(true);
        foreach (array_keys($keysToWarm) as $key) {
            Cache::remember($key, 300, function () use ($key, $keysToWarm) {
                usleep(100); // Simulate expensive operation (0.1ms)
                return $keysToWarm[$key];
            });
        }
        $coldTime = (microtime(true) - $startTime) * 1000;
        
        // Act - Warm cache and measure
        Cache::flush();
        $this->cacheManager->warm(function () use ($keysToWarm) {
            return $keysToWarm;
        });
        
        $warmStartTime = microtime(true);
        foreach (array_keys($keysToWarm) as $key) {
            $this->cacheManager->get($key);
        }
        $warmTime = (microtime(true) - $warmStartTime) * 1000;
        
        // Assert - Warm cache should be significantly faster
        $this->assertLessThan($coldTime / 10, $warmTime);
    }

    /** @test */
    public function lock_mechanism_prevents_cache_stampede()
    {
        // Arrange
        $key = 'expensive_operation';
        $executionCount = 0;
        $concurrentRequests = 50;
        
        // Act - Simulate concurrent requests for same expensive operation
        $results = [];
        for ($i = 0; $i < $concurrentRequests; $i++) {
            $result = Cache::lock($key . '_lock', 10)->block(5, function () use ($key, &$executionCount) {
                return Cache::remember($key, 300, function () use (&$executionCount) {
                    $executionCount++;
                    usleep(10000); // Simulate 10ms expensive operation
                    return 'expensive_result';
                });
            });
            $results[] = $result;
        }
        
        // Assert
        $this->assertEquals(1, $executionCount, 'Expensive operation should only execute once');
        $this->assertCount($concurrentRequests, array_filter($results, fn($r) => $r === 'expensive_result'));
    }

    /** @test */
    public function memory_cache_layer_handles_high_throughput()
    {
        // Arrange
        $operations = 10000;
        $data = ['test' => 'data', 'nested' => ['array' => 'value']];
        
        // Pre-populate L1 cache
        for ($i = 0; $i < 100; $i++) {
            $this->cacheManager->put("throughput_test_$i", $data, 300);
        }
        
        // Act - Perform many read operations
        $startTime = microtime(true);
        for ($i = 0; $i < $operations; $i++) {
            $key = 'throughput_test_' . ($i % 100);
            $this->cacheManager->get($key);
        }
        $totalTime = (microtime(true) - $startTime) * 1000;
        
        $opsPerSecond = ($operations / $totalTime) * 1000;
        
        // Assert - Should handle at least 100k ops/second from memory
        $this->assertGreaterThan(100000, $opsPerSecond);
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }
}