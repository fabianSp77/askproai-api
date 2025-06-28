<?php

namespace App\Services\Webhook;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WebhookDeduplicationService
{
    protected const KEY_PREFIX = 'webhook:processed:';
    protected const PROCESSING_PREFIX = 'webhook:processing:';
    protected const DEFAULT_TTL = 300; // 5 minutes
    protected const PROCESSING_TTL = 60; // 1 minute for processing state
    
    private array $ttlConfig = [
        'retell' => [
            'call.ended' => 600,      // 10 minutes
            'call.failed' => 300,     // 5 minutes
            'call.started' => 180,    // 3 minutes
            'call.analyzed' => 300,   // 5 minutes
            'default' => 300,         // 5 minutes
        ],
        'calcom' => [
            'booking.created' => 900,  // 15 minutes
            'booking.cancelled' => 600, // 10 minutes
            'booking.rescheduled' => 600, // 10 minutes
            'default' => 600,          // 10 minutes
        ],
        'stripe' => [
            'payment_intent.succeeded' => 1800, // 30 minutes
            'invoice.payment_succeeded' => 1800, // 30 minutes
            'charge.failed' => 900, // 15 minutes
            'default' => 900,                   // 15 minutes
        ],
    ];
    
    /**
     * Lua script for atomic check-and-set operation
     */
    private const LUA_CHECK_AND_SET = <<<'LUA'
        local processedKey = KEYS[1]
        local processingKey = KEYS[2]
        local ttl = tonumber(ARGV[1])
        local processingTtl = tonumber(ARGV[2])
        local timestamp = ARGV[3]
        
        -- Check if already processed
        if redis.call('EXISTS', processedKey) == 1 then
            return 0 -- Already processed
        end
        
        -- Check if currently processing
        if redis.call('EXISTS', processingKey) == 1 then
            return -1 -- Currently processing
        end
        
        -- Set processing flag
        redis.call('SETEX', processingKey, processingTtl, timestamp)
        return 1 -- Success, now processing
    LUA;
    
    /**
     * Check if webhook is a duplicate using atomic Lua script
     */
    public function isDuplicate(string $service, Request $request): bool
    {
        $idempotencyKey = $this->generateIdempotencyKey($service, $request);
        $processedKey = self::KEY_PREFIX . $idempotencyKey;
        $processingKey = self::PROCESSING_PREFIX . $idempotencyKey;
        $ttl = $this->getTTL($service, $request);
        
        // Execute Lua script atomically
        $result = Redis::eval(
            self::LUA_CHECK_AND_SET,
            2, // number of keys
            $processedKey,
            $processingKey,
            $ttl,
            self::PROCESSING_TTL,
            (string) now()->timestamp
        );
        
        if ($result === 0) {
            Log::info('Duplicate webhook detected (already processed)', [
                'service' => $service,
                'key' => $idempotencyKey,
            ]);
            return true;
        }
        
        if ($result === -1) {
            Log::info('Duplicate webhook detected (currently processing)', [
                'service' => $service,
                'key' => $idempotencyKey,
            ]);
            return true;
        }
        
        return false;
    }
    
    /**
     * Mark webhook as processed with atomic operation
     */
    public function markAsProcessed(string $service, Request $request, bool $success = true): void
    {
        $idempotencyKey = $this->generateIdempotencyKey($service, $request);
        $processedKey = self::KEY_PREFIX . $idempotencyKey;
        $processingKey = self::PROCESSING_PREFIX . $idempotencyKey;
        
        // Extend TTL for successful processing
        $ttl = $success 
            ? $this->getTTL($service, $request) * 2 
            : $this->getTTL($service, $request);
        
        $metadata = json_encode([
            'processed_at' => now()->toIso8601String(),
            'success' => $success,
            'server' => gethostname(),
            'ip' => request()->ip(),
        ]);
        
        // Use pipeline for atomic operations
        Redis::pipeline(function ($pipe) use ($processedKey, $processingKey, $ttl, $metadata) {
            $pipe->setex($processedKey, $ttl, $metadata);
            $pipe->del($processingKey);
        });
    }
    
    /**
     * Mark webhook as failed and allow retry
     */
    public function markAsFailed(string $service, Request $request, string $error = null): void
    {
        $idempotencyKey = $this->generateIdempotencyKey($service, $request);
        $processingKey = self::PROCESSING_PREFIX . $idempotencyKey;
        
        // Remove processing flag to allow retry
        Redis::del($processingKey);
        
        // Optional: Store failure info for monitoring
        $failureKey = 'webhook:failed:' . $idempotencyKey;
        $failureData = json_encode([
            'failed_at' => now()->toIso8601String(),
            'error' => $error,
            'server' => gethostname(),
        ]);
        
        Redis::setex($failureKey, 3600, $failureData); // Keep failure info for 1 hour
    }
    
    /**
     * Get processed webhook metadata if exists
     */
    public function getProcessedMetadata($serviceOrWebhookId, $requestOrProvider = null): ?array
    {
        // Handle both signatures
        if ($requestOrProvider instanceof Request) {
            // Original signature: (string $service, Request $request)
            $idempotencyKey = $this->generateIdempotencyKey($serviceOrWebhookId, $requestOrProvider);
            $processedKey = self::KEY_PREFIX . $idempotencyKey;
        } else {
            // New signature: (string $webhookId, string $provider)
            $processedKey = self::KEY_PREFIX . $requestOrProvider . ':' . $serviceOrWebhookId;
        }
        
        $data = Redis::get($processedKey);
        
        if ($data) {
            // Try to decode as JSON first (new format)
            $decoded = json_decode($data, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
            
            // Fallback for timestamp-only format
            return [
                'processed_at' => date('c', (int)$data),
                'success' => true,
                'legacy' => true
            ];
        }
        
        return null;
    }
    
    /**
     * Check if webhook is currently being processed
     */
    public function isProcessing(string $service, Request $request): bool
    {
        $idempotencyKey = $this->generateIdempotencyKey($service, $request);
        $processingKey = self::PROCESSING_PREFIX . $idempotencyKey;
        
        return Redis::exists($processingKey);
    }
    
    /**
     * Generate unique idempotency key for the webhook
     */
    protected function generateIdempotencyKey(string $service, Request $request): string
    {
        $payload = $request->all();
        
        return match($service) {
            'retell' => $this->generateRetellKey($payload),
            'calcom' => $this->generateCalcomKey($payload),
            'stripe' => $this->generateStripeKey($request),
            default => $this->generateDefaultKey($service, $payload),
        };
    }
    
    /**
     * Generate key for Retell webhooks
     */
    protected function generateRetellKey(array $payload): string
    {
        // Use call_id as primary identifier
        $callId = $payload['call_id'] ?? $payload['data']['call_id'] ?? '';
        $eventType = $payload['event'] ?? $payload['event_type'] ?? 'unknown';
        
        if (empty($callId)) {
            // Fallback to timestamp + phone number
            $timestamp = $payload['timestamp'] ?? time();
            $phone = $payload['from_number'] ?? $payload['to_number'] ?? 'unknown';
            return "retell:{$eventType}:{$phone}:{$timestamp}";
        }
        
        return "retell:{$eventType}:{$callId}";
    }
    
    /**
     * Generate key for Cal.com webhooks
     */
    protected function generateCalcomKey(array $payload): string
    {
        // Use booking UID or event ID
        $bookingUid = $payload['payload']['uid'] ?? 
                      $payload['data']['uid'] ?? 
                      $payload['uid'] ?? '';
                      
        $eventType = $payload['triggerEvent'] ?? 
                     $payload['event'] ?? 
                     'unknown';
        
        if (empty($bookingUid)) {
            // Fallback to timestamp + organizer
            $timestamp = $payload['createdAt'] ?? time();
            $organizer = $payload['payload']['organizer']['email'] ?? 'unknown';
            return "calcom:{$eventType}:{$organizer}:{$timestamp}";
        }
        
        return "calcom:{$eventType}:{$bookingUid}";
    }
    
    /**
     * Generate key for Stripe webhooks
     */
    private function generateStripeKey(Request $request): string
    {
        // Stripe sends idempotency key in header
        $stripeIdempotency = $request->header('Stripe-Idempotency-Key');
        if ($stripeIdempotency) {
            return "stripe:header:{$stripeIdempotency}";
        }
        
        // Fallback to event ID
        $payload = $request->all();
        $eventId = $payload['id'] ?? '';
        
        if (empty($eventId)) {
            // Last resort: use request signature
            $signature = $request->header('Stripe-Signature', '');
            return "stripe:signature:" . substr(md5($signature), 0, 16);
        }
        
        return "stripe:event:{$eventId}";
    }
    
    /**
     * Generate default key using payload hash
     */
    private function generateDefaultKey(string $service, array $payload): string
    {
        // Sort payload for consistent hashing
        ksort($payload);
        $hash = md5(json_encode($payload));
        
        return "{$service}:default:{$hash}";
    }
    
    /**
     * Get TTL for specific service and event type
     */
    protected function getTTL(string $service, Request $request): int
    {
        $eventType = $this->extractEventType($service, $request);
        
        return $this->ttlConfig[$service][$eventType] 
            ?? $this->ttlConfig[$service]['default'] 
            ?? self::DEFAULT_TTL;
    }
    
    /**
     * Extract event type from request
     */
    private function extractEventType(string $service, Request $request): string
    {
        $payload = $request->all();
        
        return match($service) {
            'retell' => $payload['event'] ?? $payload['event_type'] ?? 'unknown',
            'calcom' => $payload['triggerEvent'] ?? $payload['event'] ?? 'unknown',
            'stripe' => $payload['type'] ?? 'unknown',
            default => 'unknown'
        };
    }
    
    /**
     * Clear deduplication cache for testing
     */
    public function clearCache(): void
    {
        $patterns = [
            self::KEY_PREFIX . '*',
            self::PROCESSING_PREFIX . '*',
            'webhook:failed:*'
        ];
        
        foreach ($patterns as $pattern) {
            $keys = Redis::keys($pattern);
            if (!empty($keys)) {
                Redis::del($keys);
            }
        }
    }
    
    /**
     * Get cache statistics
     */
    public function getStats(): array
    {
        $stats = [
            'total_processed' => 0,
            'total_processing' => 0,
            'total_failed' => 0,
            'by_service' => [],
        ];
        
        // Count processed
        $processedKeys = Redis::keys(self::KEY_PREFIX . '*');
        $stats['total_processed'] = count($processedKeys);
        
        // Count processing
        $processingKeys = Redis::keys(self::PROCESSING_PREFIX . '*');
        $stats['total_processing'] = count($processingKeys);
        
        // Count failed
        $failedKeys = Redis::keys('webhook:failed:*');
        $stats['total_failed'] = count($failedKeys);
        
        // Group by service
        foreach ($processedKeys as $key) {
            if (preg_match('/webhook:processed:(\w+):/', $key, $matches)) {
                $service = $matches[1];
                $stats['by_service'][$service] = ($stats['by_service'][$service] ?? 0) + 1;
            }
        }
        
        return $stats;
    }
    
    /**
     * Clean up stale processing flags (for maintenance)
     */
    public function cleanupStaleProcessingFlags(): int
    {
        $cleaned = 0;
        $processingKeys = Redis::keys(self::PROCESSING_PREFIX . '*');
        
        foreach ($processingKeys as $key) {
            $ttl = Redis::ttl($key);
            // If no TTL set or expired, remove it
            if ($ttl === -1 || $ttl === -2) {
                Redis::del($key);
                $cleaned++;
            }
        }
        
        Log::info("Cleaned up {$cleaned} stale processing flags");
        return $cleaned;
    }
    
    /**
     * Process webhook with deduplication
     */
    public function processWithDeduplication(string $webhookId, string $provider, callable $processor): array
    {
        // Check if already processed
        if ($this->isProcessed($webhookId, $provider)) {
            return [
                'success' => true,
                'duplicate' => true,
                'message' => 'Webhook already processed'
            ];
        }
        
        // Try to acquire lock
        if (!$this->acquireLock($webhookId, $provider)) {
            return [
                'success' => false,
                'duplicate' => true,
                'message' => 'Webhook is already being processed by another worker'
            ];
        }
        
        try {
            // Process the webhook
            $result = $processor();
            
            // Mark as processed
            $this->markAsProcessedByIds($webhookId, $provider, true);
            
            // Return the processor result with success flag
            return array_merge($result, [
                'success' => true,
                'duplicate' => false
            ]);
            
        } catch (\Exception $e) {
            // Release lock on failure
            $this->releaseLock($webhookId, $provider);
            
            // Log the error
            Log::error('Webhook processing failed', [
                'webhook_id' => $webhookId,
                'provider' => $provider,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'duplicate' => false,
                'message' => 'Processing failed: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Check if webhook was already processed
     */
    public function isProcessed(string $webhookId, string $provider): bool
    {
        $processedKey = self::KEY_PREFIX . $provider . ':' . $webhookId;
        return Redis::exists($processedKey);
    }
    
    /**
     * Acquire processing lock
     */
    public function acquireLock(string $webhookId, string $provider): bool
    {
        $lockKey = self::PROCESSING_PREFIX . $provider . ':' . $webhookId;
        
        // Use SETNX for atomic lock acquisition
        $acquired = Redis::setnx($lockKey, now()->timestamp);
        
        if ($acquired) {
            // Set expiration
            Redis::expire($lockKey, self::PROCESSING_TTL);
        }
        
        return $acquired;
    }
    
    /**
     * Release processing lock
     */
    public function releaseLock(string $webhookId, string $provider): void
    {
        $lockKey = self::PROCESSING_PREFIX . $provider . ':' . $webhookId;
        Redis::del($lockKey);
    }
    
    /**
     * Mark webhook as processed by IDs
     */
    public function markAsProcessedByIds(string $webhookId, string $provider, bool $success = true): void
    {
        $processedKey = self::KEY_PREFIX . $provider . ':' . $webhookId;
        $processingKey = self::PROCESSING_PREFIX . $provider . ':' . $webhookId;
        
        // Extend TTL for successful processing
        $ttl = $success ? 600 : 300; // 10 minutes for success, 5 for failure
        
        $metadata = json_encode([
            'processed_at' => now()->toIso8601String(),
            'success' => $success,
            'server' => gethostname(),
        ]);
        
        // Use pipeline for atomic operations
        Redis::pipeline(function ($pipe) use ($processedKey, $processingKey, $ttl, $metadata) {
            $pipe->setex($processedKey, $ttl, $metadata);
            $pipe->del($processingKey);
        });
    }
    
    
    /**
     * Clean up expired locks (alias for cleanupStaleProcessingFlags)
     */
    public function cleanupExpiredLocks(): int
    {
        return $this->cleanupStaleProcessingFlags();
    }
}