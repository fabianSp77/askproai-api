<?php

namespace Tests\Unit\Services\Webhook;

use App\Services\Webhook\WebhookDeduplicationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WebhookDeduplicationServiceTest extends TestCase
{
    protected WebhookDeduplicationService $service;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new WebhookDeduplicationService();
        
        // Clear any existing test data
        Redis::flushdb();
    }
    
    protected function tearDown(): void
    {
        Redis::flushdb();
        parent::tearDown();
    }
    
    #[Test]
    
    public function test_process_with_deduplication_allows_first_request()
    {
        $webhookId = 'test_webhook_123';
        $provider = 'retell';
        $processed = false;
        
        $result = $this->service->processWithDeduplication(
            $webhookId,
            $provider,
            function() use (&$processed) {
                $processed = true;
                return [
                    'success' => true,
                    'data' => ['message' => 'Processed successfully']
                ];
            }
        );
        
        $this->assertTrue($result['success']);
        $this->assertFalse($result['duplicate'] ?? false);
        $this->assertTrue($processed);
        $this->assertEquals('Processed successfully', $result['data']['message']);
    }
    
    #[Test]
    
    public function test_process_with_deduplication_blocks_duplicate_request()
    {
        $webhookId = 'test_webhook_456';
        $provider = 'retell';
        
        // First request
        $firstResult = $this->service->processWithDeduplication(
            $webhookId,
            $provider,
            function() {
                return ['success' => true, 'data' => 'first'];
            }
        );
        
        // Second request with same ID
        $secondProcessed = false;
        $secondResult = $this->service->processWithDeduplication(
            $webhookId,
            $provider,
            function() use (&$secondProcessed) {
                $secondProcessed = true;
                return ['success' => true, 'data' => 'second'];
            }
        );
        
        $this->assertTrue($firstResult['success']);
        $this->assertFalse($firstResult['duplicate'] ?? false);
        
        $this->assertTrue($secondResult['success']);
        $this->assertTrue($secondResult['duplicate']);
        $this->assertFalse($secondProcessed); // Processor should not have been called
        $this->assertEquals('Webhook already processed', $secondResult['message']);
    }
    
    #[Test]
    
    public function test_concurrent_requests_only_one_processes()
    {
        $webhookId = 'concurrent_test_' . time();
        $provider = 'retell';
        $processCount = 0;
        
        // Simulate concurrent requests
        $promises = [];
        for ($i = 0; $i < 5; $i++) {
            $promises[] = function() use ($webhookId, $provider, &$processCount) {
                return $this->service->processWithDeduplication(
                    $webhookId,
                    $provider,
                    function() use (&$processCount) {
                        $processCount++;
                        usleep(50000); // 50ms to simulate processing time
                        return ['success' => true, 'processed_at' => microtime(true)];
                    }
                );
            };
        }
        
        // Execute all "concurrent" requests
        $results = array_map(function($promise) {
            return $promise();
        }, $promises);
        
        // Only one should have actually processed
        $this->assertEquals(1, $processCount);
        
        // Count successful non-duplicate results
        $processedCount = 0;
        $duplicateCount = 0;
        
        foreach ($results as $result) {
            if ($result['success'] && !($result['duplicate'] ?? false)) {
                $processedCount++;
            } elseif ($result['duplicate'] ?? false) {
                $duplicateCount++;
            }
        }
        
        $this->assertEquals(1, $processedCount);
        $this->assertEquals(4, $duplicateCount);
    }
    
    #[Test]
    
    public function test_failed_processing_allows_retry()
    {
        $webhookId = 'retry_test_' . time();
        $provider = 'retell';
        $attemptCount = 0;
        
        // First attempt fails
        $firstResult = $this->service->processWithDeduplication(
            $webhookId,
            $provider,
            function() use (&$attemptCount) {
                $attemptCount++;
                throw new \Exception('Processing failed');
            }
        );
        
        $this->assertFalse($firstResult['success']);
        $this->assertEquals(1, $attemptCount);
        
        // Second attempt should be allowed
        $secondResult = $this->service->processWithDeduplication(
            $webhookId,
            $provider,
            function() use (&$attemptCount) {
                $attemptCount++;
                return ['success' => true, 'message' => 'Retry successful'];
            }
        );
        
        $this->assertTrue($secondResult['success']);
        $this->assertFalse($secondResult['duplicate'] ?? false);
        $this->assertEquals(2, $attemptCount);
    }
    
    #[Test]
    
    public function test_different_webhook_ids_process_independently()
    {
        $provider = 'retell';
        $results = [];
        
        // Process multiple different webhooks
        for ($i = 0; $i < 3; $i++) {
            $webhookId = 'independent_test_' . $i;
            $results[$i] = $this->service->processWithDeduplication(
                $webhookId,
                $provider,
                function() use ($i) {
                    return ['success' => true, 'index' => $i];
                }
            );
        }
        
        // All should process successfully
        foreach ($results as $i => $result) {
            $this->assertTrue($result['success']);
            $this->assertFalse($result['duplicate'] ?? false);
            $this->assertEquals($i, $result['index']);
        }
    }
    
    #[Test]
    
    public function test_is_processed_detects_completed_webhooks()
    {
        $webhookId = 'detection_test_' . time();
        $provider = 'retell';
        
        // Initially not processed
        $this->assertFalse($this->service->isProcessed($webhookId, $provider));
        
        // Process webhook
        $this->service->processWithDeduplication(
            $webhookId,
            $provider,
            function() {
                return ['success' => true];
            }
        );
        
        // Now should be detected as processed
        $this->assertTrue($this->service->isProcessed($webhookId, $provider));
    }
    
    #[Test]
    
    public function test_acquire_lock_prevents_concurrent_processing()
    {
        $webhookId = 'lock_test_' . time();
        $provider = 'retell';
        
        // First lock should succeed
        $lock1 = $this->service->acquireLock($webhookId, $provider);
        $this->assertTrue($lock1);
        
        // Second lock should fail
        $lock2 = $this->service->acquireLock($webhookId, $provider);
        $this->assertFalse($lock2);
        
        // Release first lock
        $this->service->releaseLock($webhookId, $provider);
        
        // Now lock should succeed again
        $lock3 = $this->service->acquireLock($webhookId, $provider);
        $this->assertTrue($lock3);
        
        // Cleanup
        $this->service->releaseLock($webhookId, $provider);
    }
    
    #[Test]
    
    public function test_mark_as_processed_stores_metadata()
    {
        $webhookId = 'metadata_test_' . time();
        $provider = 'retell';
        
        $this->service->markAsProcessedByIds($webhookId, $provider, true);
        
        $metadata = $this->service->getProcessedMetadata($webhookId, $provider);
        
        $this->assertNotNull($metadata);
        $this->assertArrayHasKey('processed_at', $metadata);
        $this->assertArrayHasKey('success', $metadata);
        $this->assertTrue($metadata['success']);
    }
    
    #[Test]
    
    public function test_cleanup_expired_locks()
    {
        $webhookId = 'cleanup_test_' . time();
        $provider = 'retell';
        
        // Manually create a lock without TTL (simulating a stale lock)
        $lockKey = 'webhook:processing:' . $provider . ':' . $webhookId;
        Redis::set($lockKey, 'stale_lock');
        
        // Cleanup should remove it
        $cleaned = $this->service->cleanupExpiredLocks();
        $this->assertGreaterThan(0, $cleaned);
        
        // Lock should now be acquirable
        $this->assertTrue($this->service->acquireLock($webhookId, $provider));
        $this->service->releaseLock($webhookId, $provider);
    }
    
    #[Test]
    
    public function test_high_load_simulation()
    {
        $totalRequests = 50;
        $uniqueWebhooks = 10;
        $processedCount = [];
        
        // Initialize counters
        for ($i = 0; $i < $uniqueWebhooks; $i++) {
            $processedCount[$i] = 0;
        }
        
        // Simulate high load with many duplicate requests
        $requests = [];
        for ($i = 0; $i < $totalRequests; $i++) {
            $webhookIndex = $i % $uniqueWebhooks;
            $webhookId = 'load_test_' . $webhookIndex;
            
            $requests[] = [
                'webhook_id' => $webhookId,
                'index' => $webhookIndex
            ];
        }
        
        // Shuffle to simulate random arrival
        shuffle($requests);
        
        // Process all requests
        foreach ($requests as $request) {
            $this->service->processWithDeduplication(
                $request['webhook_id'],
                'retell',
                function() use ($request, &$processedCount) {
                    $processedCount[$request['index']]++;
                    return ['success' => true];
                }
            );
        }
        
        // Each webhook should only be processed once
        foreach ($processedCount as $index => $count) {
            $this->assertEquals(1, $count, "Webhook $index was processed $count times instead of 1");
        }
    }
    
    #[Test]
    
    public function test_different_providers_isolated()
    {
        $webhookId = 'provider_test_' . time();
        
        // Process same webhook ID with different providers
        $retellResult = $this->service->processWithDeduplication(
            $webhookId,
            'retell',
            function() {
                return ['success' => true, 'provider' => 'retell'];
            }
        );
        
        $calcomResult = $this->service->processWithDeduplication(
            $webhookId,
            'calcom',
            function() {
                return ['success' => true, 'provider' => 'calcom'];
            }
        );
        
        // Both should process successfully
        $this->assertTrue($retellResult['success']);
        $this->assertFalse($retellResult['duplicate'] ?? false);
        $this->assertEquals('retell', $retellResult['provider']);
        
        $this->assertTrue($calcomResult['success']);
        $this->assertFalse($calcomResult['duplicate'] ?? false);
        $this->assertEquals('calcom', $calcomResult['provider']);
    }
}