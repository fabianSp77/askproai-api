<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Models\Transaction;
use App\Models\Tenant;
use Carbon\Carbon;

class RedisEventPublisher
{
    private string $redisConnection;
    private int $maxStreamLength = 10000;
    private array $streamConfig = [];
    
    // Event stream names
    const STREAM_USAGE = 'usage-events';
    const STREAM_TRANSACTIONS = 'transaction-events';
    const STREAM_PREDICTIONS = 'prediction-events';
    const STREAM_ALERTS = 'alert-events';
    const STREAM_AUDIT = 'audit-events';
    
    public function __construct(string $redisConnection = 'default')
    {
        $this->redisConnection = $redisConnection;
        $this->initializeStreams();
    }
    
    /**
     * Initialize stream configurations
     */
    private function initializeStreams(): void
    {
        $this->streamConfig = [
            self::STREAM_USAGE => ['maxlen' => 50000, 'ttl' => 86400], // 1 day
            self::STREAM_TRANSACTIONS => ['maxlen' => 10000, 'ttl' => 604800], // 7 days
            self::STREAM_PREDICTIONS => ['maxlen' => 5000, 'ttl' => 86400], // 1 day
            self::STREAM_ALERTS => ['maxlen' => 1000, 'ttl' => 2592000], // 30 days
            self::STREAM_AUDIT => ['maxlen' => 100000, 'ttl' => 2592000], // 30 days
        ];
    }
    
    /**
     * Publish usage event
     */
    public function publishUsageEvent(array $data): string
    {
        $event = [
            'tenant_id' => $data['tenant_id'] ?? null,
            'service' => $data['service'] ?? 'unknown',
            'usage_type' => $data['type'] ?? 'api_call',
            'quantity' => $data['quantity'] ?? 1,
            'cost_cents' => $data['cost_cents'] ?? 0,
            'metadata' => json_encode($data['metadata'] ?? []),
            'timestamp' => $data['timestamp'] ?? now()->toIso8601String(),
            'correlation_id' => $data['correlation_id'] ?? $this->generateCorrelationId(),
        ];
        
        return $this->publish(self::STREAM_USAGE, $event);
    }
    
    /**
     * Publish transaction event
     */
    public function publishTransactionEvent(Transaction $transaction): string
    {
        $event = [
            'id' => $transaction->id,
            'tenant_id' => $transaction->tenant_id,
            'type' => $transaction->type,
            'amount_cents' => $transaction->amount_cents,
            'balance_before' => $transaction->balance_before,
            'balance_after' => $transaction->balance_after,
            'status' => $transaction->status,
            'description' => $transaction->description,
            'metadata' => json_encode($transaction->metadata ?? []),
            'timestamp' => $transaction->created_at->toIso8601String(),
            'correlation_id' => $transaction->correlation_id ?? $this->generateCorrelationId(),
        ];
        
        $eventId = $this->publish(self::STREAM_TRANSACTIONS, $event);
        
        // Trigger downstream processing
        $this->triggerTransactionProcessing($transaction, $eventId);
        
        return $eventId;
    }
    
    /**
     * Publish prediction request event
     */
    public function publishPredictionRequest(string $tenantId, string $type, array $features): string
    {
        $event = [
            'tenant_id' => $tenantId,
            'prediction_type' => $type,
            'features' => json_encode($features),
            'request_id' => $this->generateRequestId(),
            'timestamp' => now()->toIso8601String(),
            'ttl' => 300, // 5 minutes to process
        ];
        
        return $this->publish(self::STREAM_PREDICTIONS, $event);
    }
    
    /**
     * Publish alert event
     */
    public function publishAlert(string $level, string $type, array $data): string
    {
        $event = [
            'level' => $level, // critical, high, medium, low
            'type' => $type,
            'tenant_id' => $data['tenant_id'] ?? null,
            'title' => $data['title'] ?? 'System Alert',
            'message' => $data['message'] ?? '',
            'metadata' => json_encode($data['metadata'] ?? []),
            'timestamp' => now()->toIso8601String(),
            'requires_action' => $data['requires_action'] ?? false,
            'auto_resolve' => $data['auto_resolve'] ?? false,
        ];
        
        $eventId = $this->publish(self::STREAM_ALERTS, $event);
        
        // For critical alerts, also send immediate notification
        if ($level === 'critical') {
            $this->sendCriticalAlertNotification($event);
        }
        
        return $eventId;
    }
    
    /**
     * Core publish method
     */
    private function publish(string $stream, array $data): string
    {
        try {
            $redis = Redis::connection($this->redisConnection);
            
            // Add event metadata
            $data['event_id'] = $this->generateEventId();
            $data['published_at'] = microtime(true);
            $data['publisher'] = config('app.name');
            $data['version'] = '1.0';
            
            // Publish to stream
            $eventId = $redis->xAdd(
                $stream,
                '*', // Auto-generate ID
                $data
            );
            
            // Trim stream to prevent unlimited growth
            $maxlen = $this->streamConfig[$stream]['maxlen'] ?? $this->maxStreamLength;
            $redis->xTrim($stream, $maxlen, true); // Approximate trimming for performance
            
            // Log event publication
            $this->logEvent($stream, $eventId, $data);
            
            return $eventId;
            
        } catch (\Exception $e) {
            Log::error("Failed to publish event to stream {$stream}", [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            
            // Store failed event for retry
            $this->storeFailedEvent($stream, $data, $e);
            
            throw $e;
        }
    }
    
    /**
     * Consume events from stream
     */
    public function consume(
        string $stream, 
        callable $handler, 
        string $consumerGroup = null,
        string $consumerName = null
    ): void {
        $redis = Redis::connection($this->redisConnection);
        $lastId = '0-0';
        
        // Create consumer group if specified
        if ($consumerGroup) {
            try {
                $redis->xGroup('CREATE', $stream, $consumerGroup, '0');
            } catch (\Exception $e) {
                // Group might already exist
                if (!str_contains($e->getMessage(), 'BUSYGROUP')) {
                    throw $e;
                }
            }
        }
        
        Log::info("Starting consumer for stream {$stream}", [
            'group' => $consumerGroup,
            'consumer' => $consumerName
        ]);
        
        while (true) {
            try {
                // Read from stream
                if ($consumerGroup) {
                    // Consumer group reading
                    $events = $redis->xReadGroup(
                        $consumerGroup,
                        $consumerName ?? 'consumer-' . getmypid(),
                        [$stream => '>'],
                        1000, // Count
                        1000  // Block for 1 second
                    );
                } else {
                    // Simple reading
                    $events = $redis->xRead(
                        [$stream => $lastId],
                        1000, // Count
                        1000  // Block for 1 second
                    );
                }
                
                // Process events
                foreach ($events[$stream] ?? [] as $id => $data) {
                    try {
                        $handler($id, $data);
                        
                        // Acknowledge if using consumer group
                        if ($consumerGroup) {
                            $redis->xAck($stream, $consumerGroup, $id);
                        }
                        
                        $lastId = $id;
                        
                    } catch (\Exception $e) {
                        Log::error("Error processing event {$id} from {$stream}", [
                            'error' => $e->getMessage(),
                            'data' => $data
                        ]);
                        
                        // Don't acknowledge on error for retry
                        if (!$consumerGroup) {
                            $lastId = $id; // Move past error in simple mode
                        }
                    }
                }
                
                // Check for pending messages (consumer group)
                if ($consumerGroup) {
                    $this->processPendingMessages($stream, $consumerGroup, $consumerName, $handler);
                }
                
            } catch (\Exception $e) {
                Log::error("Consumer error for stream {$stream}", [
                    'error' => $e->getMessage()
                ]);
                
                // Sleep before retry
                sleep(5);
            }
            
            // Allow graceful shutdown
            if ($this->shouldStopConsuming()) {
                break;
            }
        }
    }
    
    /**
     * Process pending messages in consumer group
     */
    private function processPendingMessages(
        string $stream,
        string $group,
        string $consumer,
        callable $handler
    ): void {
        $redis = Redis::connection($this->redisConnection);
        
        // Get pending messages older than 5 minutes
        $pending = $redis->xPending($stream, $group, '-', '+', 10);
        
        foreach ($pending as $message) {
            if ($message[1] > 300000) { // Older than 5 minutes (in ms)
                try {
                    // Claim and process
                    $claimed = $redis->xClaim(
                        $stream,
                        $group,
                        $consumer,
                        300000, // Min idle time
                        [$message[0]]
                    );
                    
                    foreach ($claimed as $id => $data) {
                        $handler($id, $data);
                        $redis->xAck($stream, $group, $id);
                    }
                    
                } catch (\Exception $e) {
                    Log::error("Failed to process pending message", [
                        'stream' => $stream,
                        'message_id' => $message[0],
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }
    }
    
    /**
     * Get stream info
     */
    public function getStreamInfo(string $stream): array
    {
        $redis = Redis::connection($this->redisConnection);
        
        $info = $redis->xInfo('STREAM', $stream);
        
        return [
            'length' => $info[1],
            'radix_tree_keys' => $info[3],
            'radix_tree_nodes' => $info[5],
            'last_generated_id' => $info[7],
            'first_entry' => $info[9],
            'last_entry' => $info[11],
        ];
    }
    
    /**
     * Get consumer group info
     */
    public function getConsumerGroupInfo(string $stream, string $group): array
    {
        $redis = Redis::connection($this->redisConnection);
        
        $groups = $redis->xInfo('GROUPS', $stream);
        
        foreach ($groups as $groupInfo) {
            if ($groupInfo[1] === $group) {
                return [
                    'name' => $groupInfo[1],
                    'consumers' => $groupInfo[3],
                    'pending' => $groupInfo[5],
                    'last_delivered_id' => $groupInfo[7],
                ];
            }
        }
        
        return [];
    }
    
    /**
     * Trigger downstream transaction processing
     */
    private function triggerTransactionProcessing(Transaction $transaction, string $eventId): void
    {
        // Check if ML prediction is needed
        if ($transaction->amount_cents > 10000) { // Over 100€
            $this->publishPredictionRequest(
                $transaction->tenant_id,
                'fraud_detection',
                [
                    'amount' => $transaction->amount_cents,
                    'type' => $transaction->type,
                    'time' => $transaction->created_at->hour,
                    'day_of_week' => $transaction->created_at->dayOfWeek,
                ]
            );
        }
        
        // Check for low balance alert
        if ($transaction->balance_after < 500 && $transaction->type === 'debit') {
            $this->publishAlert('medium', 'low_balance', [
                'tenant_id' => $transaction->tenant_id,
                'title' => 'Low Balance Warning',
                'message' => 'Balance is below 5€',
                'metadata' => [
                    'balance' => $transaction->balance_after,
                    'transaction_id' => $transaction->id,
                ],
                'requires_action' => true,
            ]);
        }
    }
    
    /**
     * Send critical alert notification
     */
    private function sendCriticalAlertNotification(array $event): void
    {
        // Implementation would send to Slack, email, SMS, etc.
        Log::critical("Critical alert: {$event['title']}", $event);
    }
    
    /**
     * Store failed event for retry
     */
    private function storeFailedEvent(string $stream, array $data, \Exception $error): void
    {
        Cache::put(
            "failed_event." . uniqid(),
            [
                'stream' => $stream,
                'data' => $data,
                'error' => $error->getMessage(),
                'timestamp' => now()->toIso8601String(),
            ],
            now()->addHours(24)
        );
    }
    
    /**
     * Log event for audit
     */
    private function logEvent(string $stream, string $eventId, array $data): void
    {
        if (config('app.debug')) {
            Log::debug("Event published", [
                'stream' => $stream,
                'event_id' => $eventId,
                'tenant_id' => $data['tenant_id'] ?? null,
            ]);
        }
    }
    
    /**
     * Check if consumer should stop
     */
    private function shouldStopConsuming(): bool
    {
        return Cache::has('stop_consumers') || app()->runningInConsole() === false;
    }
    
    /**
     * Helper methods for ID generation
     */
    private function generateEventId(): string
    {
        return uniqid('evt_', true);
    }
    
    private function generateCorrelationId(): string
    {
        return uniqid('cor_', true);
    }
    
    private function generateRequestId(): string
    {
        return uniqid('req_', true);
    }
}