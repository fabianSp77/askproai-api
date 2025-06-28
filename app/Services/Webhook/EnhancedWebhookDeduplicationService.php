<?php

namespace App\Services\Webhook;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\WebhookEvent;

class EnhancedWebhookDeduplicationService extends WebhookDeduplicationService
{
    /**
     * Enhanced Lua script for atomic check-set-and-complete operation
     * This script handles the complete lifecycle in one atomic operation
     */
    protected const LUA_COMPLETE_LIFECYCLE = <<<'LUA'
        local processedKey = KEYS[1]
        local processingKey = KEYS[2]
        local failedKey = KEYS[3]
        local operation = ARGV[1]  -- 'check', 'complete', 'fail'
        local ttl = tonumber(ARGV[2])
        local timestamp = ARGV[3]
        local metadata = ARGV[4]
        
        if operation == 'check' then
            -- Check if already processed
            if redis.call('EXISTS', processedKey) == 1 then
                local data = redis.call('GET', processedKey)
                return cjson.encode({status = 'duplicate_processed', data = data})
            end
            
            -- Check if currently processing
            if redis.call('EXISTS', processingKey) == 1 then
                local startTime = redis.call('GET', processingKey)
                local elapsed = tonumber(timestamp) - tonumber(startTime)
                -- If processing for more than 5 minutes, consider it stale
                if elapsed > 300 then
                    redis.call('DEL', processingKey)
                else
                    return cjson.encode({status = 'duplicate_processing', elapsed = elapsed})
                end
            end
            
            -- Check if recently failed (allow retry after 30 seconds)
            if redis.call('EXISTS', failedKey) == 1 then
                local failData = redis.call('GET', failedKey)
                local failTime = cjson.decode(failData).timestamp
                local elapsed = tonumber(timestamp) - tonumber(failTime)
                if elapsed < 30 then
                    return cjson.encode({status = 'recently_failed', wait = 30 - elapsed})
                end
            end
            
            -- Set processing flag
            redis.call('SETEX', processingKey, 300, timestamp) -- 5 min timeout
            return cjson.encode({status = 'ok'})
            
        elseif operation == 'complete' then
            -- Mark as completed
            redis.call('SETEX', processedKey, ttl, metadata)
            redis.call('DEL', processingKey)
            redis.call('DEL', failedKey)
            return cjson.encode({status = 'completed'})
            
        elseif operation == 'fail' then
            -- Mark as failed
            local failData = cjson.encode({timestamp = timestamp, error = metadata})
            redis.call('SETEX', failedKey, 300, failData) -- 5 min before retry
            redis.call('DEL', processingKey)
            return cjson.encode({status = 'failed'})
            
        else
            return cjson.encode({status = 'error', message = 'Invalid operation'})
        end
    LUA;

    /**
     * Check if webhook should be processed with enhanced logic
     * Returns detailed status information
     */
    public function checkAndStartProcessing(string $service, Request $request): array
    {
        $idempotencyKey = $this->generateIdempotencyKey($service, $request);
        $processedKey = self::KEY_PREFIX . $idempotencyKey;
        $processingKey = self::PROCESSING_PREFIX . $idempotencyKey;
        $failedKey = 'webhook:failed:' . $idempotencyKey;
        $ttl = $this->getTTL($service, $request);
        
        try {
            $result = Redis::eval(
                self::LUA_COMPLETE_LIFECYCLE,
                3,
                $processedKey,
                $processingKey,
                $failedKey,
                'check',
                $ttl,
                (string) now()->timestamp,
                ''
            );
            
            $decoded = json_decode($result, true);
            
            if ($decoded['status'] === 'ok') {
                Log::info('Webhook processing started', [
                    'service' => $service,
                    'key' => $idempotencyKey,
                ]);
                
                return [
                    'should_process' => true,
                    'status' => 'processing',
                    'idempotency_key' => $idempotencyKey
                ];
            }
            
            Log::info('Webhook rejected', [
                'service' => $service,
                'key' => $idempotencyKey,
                'status' => $decoded['status'],
                'details' => $decoded
            ]);
            
            return [
                'should_process' => false,
                'status' => $decoded['status'],
                'details' => $decoded,
                'idempotency_key' => $idempotencyKey
            ];
            
        } catch (\Exception $e) {
            Log::error('Webhook deduplication check failed', [
                'service' => $service,
                'error' => $e->getMessage()
            ]);
            
            // On error, allow processing but log the issue
            return [
                'should_process' => true,
                'status' => 'error',
                'error' => $e->getMessage(),
                'idempotency_key' => $idempotencyKey
            ];
        }
    }

    /**
     * Mark webhook as successfully processed
     */
    public function markAsCompleted(string $service, Request $request, array $metadata = []): void
    {
        $idempotencyKey = $this->generateIdempotencyKey($service, $request);
        $processedKey = self::KEY_PREFIX . $idempotencyKey;
        $processingKey = self::PROCESSING_PREFIX . $idempotencyKey;
        $failedKey = 'webhook:failed:' . $idempotencyKey;
        $ttl = $this->getTTL($service, $request) * 2; // Double TTL for completed
        
        $metadata = array_merge($metadata, [
            'completed_at' => now()->toIso8601String(),
            'server' => gethostname(),
            'ip' => request()->ip(),
        ]);
        
        try {
            Redis::eval(
                self::LUA_COMPLETE_LIFECYCLE,
                3,
                $processedKey,
                $processingKey,
                $failedKey,
                'complete',
                $ttl,
                (string) now()->timestamp,
                json_encode($metadata)
            );
            
            Log::info('Webhook marked as completed', [
                'service' => $service,
                'key' => $idempotencyKey,
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to mark webhook as completed', [
                'service' => $service,
                'key' => $idempotencyKey,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Mark webhook as failed with retry logic
     */
    public function markAsFailed(string $service, Request $request, string $error = null): void
    {
        $idempotencyKey = $this->generateIdempotencyKey($service, $request);
        $processedKey = self::KEY_PREFIX . $idempotencyKey;
        $processingKey = self::PROCESSING_PREFIX . $idempotencyKey;
        $failedKey = 'webhook:failed:' . $idempotencyKey;
        $ttl = 300; // 5 minutes before retry allowed
        
        try {
            Redis::eval(
                self::LUA_COMPLETE_LIFECYCLE,
                3,
                $processedKey,
                $processingKey,
                $failedKey,
                'fail',
                $ttl,
                (string) now()->timestamp,
                $error ?? 'Unknown error'
            );
            
            Log::warning('Webhook marked as failed', [
                'service' => $service,
                'key' => $idempotencyKey,
                'error' => $error
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to mark webhook as failed', [
                'service' => $service,
                'key' => $idempotencyKey,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Process webhook with complete deduplication lifecycle
     * This method combines Redis and database operations atomically
     */
    public function processWithDeduplication(
        string $webhookId,
        string $provider,
        callable $processor
    ): array {
        // For compatibility, we'll use the provider as service
        $service = $provider;
        // Create a dummy request for backward compatibility
        $request = request();
        // Step 1: Check and start processing
        $checkResult = $this->checkAndStartProcessing($service, $request);
        
        if (!$checkResult['should_process']) {
            return [
                'success' => false,
                'duplicate' => true,
                'status' => $checkResult['status'],
                'details' => $checkResult['details'] ?? null
            ];
        }
        
        $idempotencyKey = $checkResult['idempotency_key'];
        
        try {
            // Step 2: Record in database with transaction
            $webhookEvent = DB::transaction(function () use ($service, $request, $idempotencyKey) {
                // Double-check in database (belt and suspenders)
                $existing = WebhookEvent::where('idempotency_key', $idempotencyKey)
                    ->lockForUpdate()
                    ->first();
                
                if ($existing) {
                    throw new \Exception('Duplicate found in database');
                }
                
                return WebhookEvent::create([
                    'provider' => $service,
                    'idempotency_key' => $idempotencyKey,
                    'event_type' => $this->extractEventType($service, $request),
                    'payload' => $request->all(),
                    'headers' => $request->headers->all(),
                    'status' => 'processing',
                    'processed_at' => now(),
                ]);
            });
            
            // Step 3: Process the webhook
            $result = $processor($webhookEvent);
            
            // Step 4: Mark as completed
            $this->markAsCompleted($service, $request, [
                'webhook_event_id' => $webhookEvent->id,
                'result' => $result
            ]);
            
            // Update database record
            $webhookEvent->update([
                'status' => 'completed',
                'response' => $result,
                'completed_at' => now()
            ]);
            
            return [
                'success' => true,
                'duplicate' => false,
                'webhook_event_id' => $webhookEvent->id,
                'result' => $result
            ];
            
        } catch (\Exception $e) {
            // Mark as failed
            $this->markAsFailed($service, $request, $e->getMessage());
            
            // Update database record if exists
            if (isset($webhookEvent)) {
                $webhookEvent->update([
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                    'failed_at' => now()
                ]);
            }
            
            // If it's a duplicate exception, handle gracefully
            if (str_contains($e->getMessage(), 'Duplicate found')) {
                return [
                    'success' => false,
                    'duplicate' => true,
                    'status' => 'duplicate_database',
                    'error' => 'Found in database after Redis check'
                ];
            }
            
            throw $e;
        }
    }

    /**
     * Get comprehensive statistics including lifecycle states
     */
    public function getEnhancedStats(): array
    {
        $baseStats = parent::getStats();
        
        // Add lifecycle-specific stats
        $stats = array_merge($baseStats, [
            'lifecycle_states' => [
                'processing_expired' => 0,
                'retry_pending' => 0,
                'average_processing_time' => 0,
            ],
            'health_score' => 100
        ]);
        
        // Check for expired processing flags
        $processingKeys = Redis::keys(self::PROCESSING_PREFIX . '*');
        $now = now()->timestamp;
        $totalProcessingTime = 0;
        $processedCount = 0;
        
        foreach ($processingKeys as $key) {
            $startTime = Redis::get($key);
            if ($startTime) {
                $elapsed = $now - intval($startTime);
                if ($elapsed > 300) { // 5 minutes
                    $stats['lifecycle_states']['processing_expired']++;
                }
                $totalProcessingTime += $elapsed;
                $processedCount++;
            }
        }
        
        if ($processedCount > 0) {
            $stats['lifecycle_states']['average_processing_time'] = 
                round($totalProcessingTime / $processedCount, 2);
        }
        
        // Count retry-pending webhooks
        $failedKeys = Redis::keys('webhook:failed:*');
        foreach ($failedKeys as $key) {
            $data = Redis::get($key);
            if ($data) {
                $decoded = json_decode($data, true);
                if (isset($decoded['timestamp'])) {
                    $elapsed = $now - intval($decoded['timestamp']);
                    if ($elapsed < 300) { // Still in retry window
                        $stats['lifecycle_states']['retry_pending']++;
                    }
                }
            }
        }
        
        // Calculate health score
        $totalWebhooks = $stats['total_processed'] + $stats['total_processing'] + $stats['total_failed'];
        if ($totalWebhooks > 0) {
            $successRate = ($stats['total_processed'] / $totalWebhooks) * 100;
            $stuckRate = ($stats['lifecycle_states']['processing_expired'] / max(1, $stats['total_processing'])) * 100;
            
            $stats['health_score'] = max(0, min(100, 
                $successRate - $stuckRate - ($stats['total_failed'] / $totalWebhooks * 50)
            ));
        }
        
        return $stats;
    }
}