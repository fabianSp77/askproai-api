# Webhook und Queue Processing - Technische Spezifikation

## Executive Summary

Diese Spezifikation definiert die Implementierung einer robusten, atomaren Webhook-Verarbeitung mit Redis-basierter Deduplikation und asynchroner Queue-Verarbeitung für die AskProAI Platform. Die Lösung adressiert Race Conditions, garantiert Idempotenz und optimiert die Response-Zeiten für Webhook-Provider.

## 1. Atomic Webhook Deduplication

### 1.1 Aktuelle Situation

**Probleme:**
- Database-basierte Deduplikation mit potentiellen Race Conditions
- `lockForUpdate()` Ansatz skaliert nicht gut bei hohem Traffic
- Keine garantierte Atomarität bei concurrent Requests
- Performance-Impact durch Datenbank-Transaktionen

**Aktueller Flow:**
```php
// WebhookProcessor.php - Lines 97-117
DB::transaction(function () {
    $existing = WebhookEvent::where('idempotency_key', $idempotencyKey)
        ->lockForUpdate()
        ->first();
    // Potential race condition window
});
```

### 1.2 Redis SETNX-basierte Lösung

#### 1.2.1 Architektur

```
Webhook Request
      ↓
Signature Verification
      ↓
Generate Idempotency Key
      ↓
Redis SETNX Check ←─── Atomic Operation
      ↓
  Duplicate?
  Yes → Return cached response
  No  → Queue for processing
```

#### 1.2.2 Implementation Details

```php
namespace App\Services\Webhook;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Cache;

class AtomicWebhookDeduplicator
{
    private const PROCESSING_PREFIX = 'webhook:processing:';
    private const RESULT_PREFIX = 'webhook:result:';
    private const DEFAULT_TTL = 86400; // 24 hours
    private const PROCESSING_TTL = 300; // 5 minutes
    
    /**
     * Atomically check and mark webhook for processing
     * 
     * @return array ['is_duplicate' => bool, 'result' => mixed|null]
     */
    public function checkAndMark(string $idempotencyKey): array
    {
        $processingKey = self::PROCESSING_PREFIX . $idempotencyKey;
        $resultKey = self::RESULT_PREFIX . $idempotencyKey;
        
        // Check if result already exists (completed webhook)
        $cachedResult = Redis::get($resultKey);
        if ($cachedResult !== null) {
            return [
                'is_duplicate' => true,
                'result' => json_decode($cachedResult, true)
            ];
        }
        
        // Try to acquire processing lock atomically
        $acquired = Redis::set(
            $processingKey, 
            json_encode([
                'started_at' => now()->toIso8601String(),
                'pid' => getmypid(),
                'hostname' => gethostname()
            ]),
            'NX', // Only set if not exists
            'EX', // Set expiration
            self::PROCESSING_TTL
        );
        
        if (!$acquired) {
            // Another process is handling this webhook
            // Wait briefly and check for result
            usleep(100000); // 100ms
            
            $result = Redis::get($resultKey);
            if ($result) {
                return [
                    'is_duplicate' => true,
                    'result' => json_decode($result, true)
                ];
            }
            
            // Still processing
            return [
                'is_duplicate' => true,
                'result' => ['status' => 'processing']
            ];
        }
        
        return ['is_duplicate' => false, 'result' => null];
    }
    
    /**
     * Store processing result
     */
    public function storeResult(string $idempotencyKey, array $result): void
    {
        $resultKey = self::RESULT_PREFIX . $idempotencyKey;
        $processingKey = self::PROCESSING_PREFIX . $idempotencyKey;
        
        // Store result with TTL
        Redis::setex(
            $resultKey,
            self::DEFAULT_TTL,
            json_encode($result)
        );
        
        // Remove processing lock
        Redis::del($processingKey);
    }
    
    /**
     * Handle processing failure
     */
    public function markFailed(string $idempotencyKey): void
    {
        $processingKey = self::PROCESSING_PREFIX . $idempotencyKey;
        Redis::del($processingKey);
    }
}
```

### 1.3 Idempotency Key Generation Strategy

#### 1.3.1 Provider-Specific Keys

```php
namespace App\Services\Webhook;

class IdempotencyKeyGenerator
{
    /**
     * Generate deterministic idempotency key
     */
    public static function generate(string $provider, array $payload): string
    {
        $keyData = match($provider) {
            'retell' => self::generateRetellKey($payload),
            'calcom' => self::generateCalcomKey($payload),
            'stripe' => self::generateStripeKey($payload),
            default => self::generateGenericKey($provider, $payload)
        };
        
        return hash('sha256', json_encode($keyData));
    }
    
    private static function generateRetellKey(array $payload): array
    {
        return [
            'provider' => 'retell',
            'event' => $payload['event'] ?? 'unknown',
            'call_id' => $payload['call']['call_id'] ?? $payload['call_id'] ?? '',
            // Include timestamp for call_ended to handle retries
            'timestamp' => $payload['event'] === 'call_ended' 
                ? ($payload['call']['end_timestamp'] ?? '') 
                : null
        ];
    }
    
    private static function generateCalcomKey(array $payload): array
    {
        return [
            'provider' => 'calcom',
            'trigger' => $payload['triggerEvent'] ?? '',
            'booking_uid' => $payload['payload']['uid'] ?? '',
            // Include reschedule UID if present
            'reschedule_uid' => $payload['payload']['rescheduleUid'] ?? null
        ];
    }
    
    private static function generateStripeKey(array $payload): array
    {
        // Stripe provides unique event IDs
        return [
            'provider' => 'stripe',
            'event_id' => $payload['id'] ?? '',
            'idempotency_key' => $payload['idempotency_key'] ?? null
        ];
    }
}
```

### 1.4 TTL Configuration

```php
namespace App\Services\Webhook;

class WebhookTTLConfig
{
    /**
     * Get TTL based on webhook type and result
     */
    public static function getTTL(string $provider, string $eventType, bool $success): int
    {
        // Successful webhooks - longer retention
        if ($success) {
            return match($provider) {
                'retell' => match($eventType) {
                    'call_ended' => 604800, // 7 days - important for analytics
                    'call_analyzed' => 259200, // 3 days
                    default => 86400 // 1 day
                },
                'calcom' => 172800, // 2 days - booking confirmations
                'stripe' => 2592000, // 30 days - financial records
                default => 86400 // 1 day
            };
        }
        
        // Failed webhooks - shorter retention for retries
        return 3600; // 1 hour
    }
    
    /**
     * Get processing lock TTL
     */
    public static function getProcessingTTL(string $provider): int
    {
        return match($provider) {
            'retell' => 300, // 5 minutes - external API calls
            'calcom' => 180, // 3 minutes
            'stripe' => 60,  // 1 minute - usually fast
            default => 120   // 2 minutes
        };
    }
}
```

### 1.5 Race Condition Prevention

```php
namespace App\Services\Webhook;

use Illuminate\Support\Facades\Redis;

class WebhookRaceConditionGuard
{
    /**
     * Distributed lock implementation for critical sections
     */
    public static function withLock(
        string $resource, 
        callable $callback, 
        int $timeout = 5
    ): mixed {
        $lockKey = "lock:{$resource}";
        $lockValue = Str::random(20);
        
        // Try to acquire lock with timeout
        $acquired = Redis::set($lockKey, $lockValue, 'NX', 'EX', $timeout);
        
        if (!$acquired) {
            throw new WebhookLockException("Could not acquire lock for {$resource}");
        }
        
        try {
            return $callback();
        } finally {
            // Only release if we own the lock (compare value)
            $script = "
                if redis.call('get', KEYS[1]) == ARGV[1] then
                    return redis.call('del', KEYS[1])
                else
                    return 0
                end
            ";
            
            Redis::eval($script, 1, $lockKey, $lockValue);
        }
    }
}
```

### 1.6 Performance Impact Analysis

**Current System:**
- Database query + lock: ~5-10ms
- Transaction overhead: ~2-3ms
- Total: ~7-13ms per duplicate check

**Redis SETNX System:**
- Redis SETNX: ~0.1-0.5ms
- Network RTT: ~0.5-1ms
- Total: ~0.6-1.5ms per duplicate check

**Improvements:**
- 85-90% reduction in deduplication latency
- No database locks = better concurrency
- Horizontal scalability with Redis Cluster

## 2. Webhook Queue Processing

### 2.1 Asynchronous Architecture

```
Webhook Endpoint (Synchronous)
      ↓
Minimal Validation
      ↓
Queue Job (Asynchronous)
      ↓
Full Processing
      ↓
Store Result in Redis
```

### 2.2 Job-Based Implementation

```php
namespace App\Jobs\Webhook;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use App\Services\Webhook\AtomicWebhookDeduplicator;

class ProcessWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public $tries = 3;
    public $maxExceptions = 2;
    public $timeout = 120;
    public $failOnTimeout = true;
    
    private string $provider;
    private array $payload;
    private string $idempotencyKey;
    private string $correlationId;
    
    public function __construct(
        string $provider,
        array $payload,
        string $idempotencyKey,
        string $correlationId
    ) {
        $this->provider = $provider;
        $this->payload = $payload;
        $this->idempotencyKey = $idempotencyKey;
        $this->correlationId = $correlationId;
        
        // Set queue based on provider priority
        $this->onQueue($this->determineQueue());
    }
    
    /**
     * Get middleware
     */
    public function middleware(): array
    {
        return [
            // Prevent duplicate job processing
            (new WithoutOverlapping($this->idempotencyKey))
                ->dontRelease()
                ->expireAfter(180),
        ];
    }
    
    /**
     * Execute the job
     */
    public function handle(
        AtomicWebhookDeduplicator $deduplicator,
        WebhookHandlerFactory $handlerFactory
    ): void {
        try {
            // Double-check deduplication
            $check = $deduplicator->checkAndMark($this->idempotencyKey);
            if ($check['is_duplicate']) {
                Log::info('Webhook already processed in queue', [
                    'idempotency_key' => $this->idempotencyKey
                ]);
                return;
            }
            
            // Get appropriate handler
            $handler = $handlerFactory->make($this->provider);
            
            // Process webhook
            $result = $handler->handle(
                $this->payload,
                $this->correlationId
            );
            
            // Store result
            $deduplicator->storeResult($this->idempotencyKey, [
                'success' => true,
                'result' => $result,
                'processed_at' => now()->toIso8601String(),
                'correlation_id' => $this->correlationId
            ]);
            
            // Dispatch follow-up jobs if needed
            $this->dispatchFollowUpJobs($result);
            
        } catch (\Exception $e) {
            $this->handleFailure($e, $deduplicator);
            throw $e; // Let Laravel handle retry
        }
    }
    
    /**
     * Determine queue based on provider and event type
     */
    private function determineQueue(): string
    {
        return match($this->provider) {
            'retell' => match($this->payload['event'] ?? '') {
                'call_inbound' => 'webhooks-critical', // Real-time response needed
                'call_ended' => 'webhooks-high',
                default => 'webhooks'
            },
            'calcom' => 'webhooks-high', // Booking confirmations
            'stripe' => 'webhooks-critical', // Financial transactions
            default => 'webhooks'
        };
    }
    
    /**
     * Calculate backoff for retries
     */
    public function backoff(): array
    {
        return [30, 120, 300]; // 30s, 2min, 5min
    }
    
    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Webhook job permanently failed', [
            'provider' => $this->provider,
            'idempotency_key' => $this->idempotencyKey,
            'correlation_id' => $this->correlationId,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);
        
        // Send to dead letter queue
        DeadLetterWebhook::create([
            'provider' => $this->provider,
            'payload' => $this->payload,
            'idempotency_key' => $this->idempotencyKey,
            'correlation_id' => $this->correlationId,
            'error' => $exception->getMessage(),
            'failed_at' => now()
        ]);
    }
}
```

### 2.3 Controller Refactoring

```php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Webhook\WebhookValidator;
use App\Services\Webhook\AtomicWebhookDeduplicator;
use App\Jobs\Webhook\ProcessWebhookJob;

class AsyncWebhookController extends Controller
{
    public function __construct(
        private WebhookValidator $validator,
        private AtomicWebhookDeduplicator $deduplicator,
        private IdempotencyKeyGenerator $keyGenerator
    ) {}
    
    /**
     * Handle incoming webhook - minimal sync processing
     */
    public function handle(Request $request, string $provider)
    {
        $startTime = microtime(true);
        $correlationId = Str::uuid()->toString();
        
        try {
            // 1. Basic validation (fast)
            $this->validator->validateRequest($request, $provider);
            
            // 2. Generate idempotency key
            $idempotencyKey = $this->keyGenerator->generate(
                $provider,
                $request->all()
            );
            
            // 3. Check for duplicate (Redis - fast)
            $check = $this->deduplicator->checkAndMark($idempotencyKey);
            
            if ($check['is_duplicate']) {
                return response()->json([
                    'success' => true,
                    'duplicate' => true,
                    'correlation_id' => $correlationId,
                    'cached_result' => $check['result']
                ], 200);
            }
            
            // 4. Queue for async processing
            ProcessWebhookJob::dispatch(
                $provider,
                $request->all(),
                $idempotencyKey,
                $correlationId
            );
            
            // 5. Return immediately
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);
            
            return response()->json([
                'success' => true,
                'queued' => true,
                'correlation_id' => $correlationId,
                'response_time_ms' => $responseTime
            ], 202); // 202 Accepted
            
        } catch (WebhookValidationException $e) {
            return response()->json([
                'error' => 'Validation failed',
                'message' => $e->getMessage()
            ], 400);
            
        } catch (\Exception $e) {
            Log::error('Webhook processing error', [
                'provider' => $provider,
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId
            ]);
            
            // Still accept to prevent provider retries
            return response()->json([
                'success' => true,
                'error' => app()->environment('production') ? null : $e->getMessage()
            ], 200);
        }
    }
}
```

### 2.4 Retry Logic mit Exponential Backoff

```php
namespace App\Services\Webhook;

class RetryStrategy
{
    /**
     * Calculate next retry delay with jitter
     */
    public static function calculateDelay(int $attempt, string $provider): int
    {
        $baseDelays = match($provider) {
            'retell' => [60, 300, 900, 3600], // 1min, 5min, 15min, 1hr
            'calcom' => [30, 120, 600],       // 30s, 2min, 10min
            'stripe' => [10, 60, 300],        // 10s, 1min, 5min
            default => [60, 300, 900]
        };
        
        $delay = $baseDelays[$attempt - 1] ?? end($baseDelays);
        
        // Add jitter (±20%) to prevent thundering herd
        $jitter = $delay * 0.2;
        $delay += rand(-$jitter, $jitter);
        
        return max(1, $delay);
    }
    
    /**
     * Determine if webhook should be retried
     */
    public static function shouldRetry(
        \Exception $exception,
        int $attempt,
        string $provider
    ): bool {
        // Never retry signature failures
        if ($exception instanceof WebhookSignatureException) {
            return false;
        }
        
        // Provider-specific retry limits
        $maxAttempts = match($provider) {
            'retell' => 4,
            'calcom' => 3,
            'stripe' => 3,
            default => 3
        };
        
        if ($attempt >= $maxAttempts) {
            return false;
        }
        
        // Retry on specific exceptions
        return match(true) {
            $exception instanceof ConnectionException => true,
            $exception instanceof TimeoutException => true,
            $exception instanceof RateLimitException => true,
            $exception instanceof ServerException => $exception->getCode() >= 500,
            default => false
        };
    }
}
```

### 2.5 Dead Letter Queue Handling

```php
namespace App\Services\Webhook;

use App\Models\DeadLetterWebhook;

class DeadLetterQueueManager
{
    /**
     * Process dead letter webhooks
     */
    public function processDeadLetters(int $limit = 100): array
    {
        $results = [
            'processed' => 0,
            'succeeded' => 0,
            'failed' => 0
        ];
        
        $deadLetters = DeadLetterWebhook::where('retry_count', '<', 3)
            ->where('next_retry_at', '<=', now())
            ->limit($limit)
            ->get();
        
        foreach ($deadLetters as $deadLetter) {
            try {
                // Reprocess webhook
                ProcessWebhookJob::dispatch(
                    $deadLetter->provider,
                    $deadLetter->payload,
                    $deadLetter->idempotency_key,
                    $deadLetter->correlation_id
                )->onQueue('webhooks-retry');
                
                $deadLetter->increment('retry_count');
                $deadLetter->update([
                    'last_retry_at' => now(),
                    'next_retry_at' => now()->addHours(
                        pow(2, $deadLetter->retry_count)
                    )
                ]);
                
                $results['succeeded']++;
                
            } catch (\Exception $e) {
                $deadLetter->update([
                    'last_error' => $e->getMessage(),
                    'permanently_failed' => $deadLetter->retry_count >= 3
                ]);
                
                $results['failed']++;
            }
            
            $results['processed']++;
        }
        
        return $results;
    }
    
    /**
     * Manual retry of specific webhook
     */
    public function manualRetry(int $deadLetterId): bool
    {
        $deadLetter = DeadLetterWebhook::findOrFail($deadLetterId);
        
        // Reset retry count for manual retry
        $deadLetter->update(['retry_count' => 0]);
        
        ProcessWebhookJob::dispatch(
            $deadLetter->provider,
            $deadLetter->payload,
            $deadLetter->idempotency_key,
            $deadLetter->correlation_id
        )->onQueue('webhooks-manual');
        
        return true;
    }
}
```

### 2.6 Timeout Protection

```php
namespace App\Jobs\Middleware;

use Illuminate\Support\Facades\Redis;

class WebhookTimeoutProtection
{
    private int $timeout;
    
    public function __construct(int $timeout = 120)
    {
        $this->timeout = $timeout;
    }
    
    public function handle($job, $next)
    {
        $timeoutKey = "webhook:timeout:{$job->idempotencyKey}";
        
        // Set timeout marker
        Redis::setex($timeoutKey, $this->timeout + 10, json_encode([
            'started_at' => now()->toIso8601String(),
            'job_id' => $job->job->getJobId(),
            'timeout' => $this->timeout
        ]));
        
        // Register timeout handler
        pcntl_signal(SIGALRM, function() use ($job, $timeoutKey) {
            Log::error('Webhook job timeout', [
                'idempotency_key' => $job->idempotencyKey,
                'provider' => $job->provider,
                'timeout' => $this->timeout
            ]);
            
            Redis::del($timeoutKey);
            
            // Force job to fail
            throw new WebhookTimeoutException(
                "Job exceeded timeout of {$this->timeout} seconds"
            );
        });
        
        // Set alarm
        pcntl_alarm($this->timeout);
        
        try {
            $response = $next($job);
            pcntl_alarm(0); // Cancel alarm
            Redis::del($timeoutKey);
            return $response;
        } catch (\Exception $e) {
            pcntl_alarm(0); // Cancel alarm
            Redis::del($timeoutKey);
            throw $e;
        }
    }
}
```

## 3. Queue Configuration

### 3.1 Laravel Horizon Setup

```php
// config/horizon.php
return [
    'environments' => [
        'production' => [
            'webhooks-critical' => [
                'connection' => 'redis',
                'queue' => ['webhooks-critical'],
                'balance' => 'auto',
                'autoScalingStrategy' => 'time',
                'maxProcesses' => 20,
                'minProcesses' => 5,
                'balanceMaxShift' => 5,
                'balanceCooldown' => 1,
                'memory' => 512,
                'timeout' => 60,
                'sleep' => 0.1,
                'maxJobs' => 1000,
                'maxTime' => 3600,
                'tries' => 1, // Critical = fail fast
            ],
            
            'webhooks-high' => [
                'connection' => 'redis',
                'queue' => ['webhooks-high'],
                'balance' => 'auto',
                'autoScalingStrategy' => 'time',
                'maxProcesses' => 15,
                'minProcesses' => 3,
                'balanceMaxShift' => 3,
                'balanceCooldown' => 2,
                'memory' => 384,
                'timeout' => 120,
                'sleep' => 0.5,
                'tries' => 3,
            ],
            
            'webhooks' => [
                'connection' => 'redis',
                'queue' => ['webhooks', 'default'],
                'balance' => 'auto',
                'autoScalingStrategy' => 'time',
                'maxProcesses' => 10,
                'minProcesses' => 2,
                'balanceMaxShift' => 2,
                'balanceCooldown' => 3,
                'memory' => 256,
                'timeout' => 180,
                'sleep' => 1,
                'tries' => 3,
            ],
            
            'webhooks-retry' => [
                'connection' => 'redis',
                'queue' => ['webhooks-retry'],
                'balance' => 'simple',
                'maxProcesses' => 5,
                'memory' => 256,
                'timeout' => 300,
                'sleep' => 5,
                'tries' => 3,
            ],
            
            'webhooks-manual' => [
                'connection' => 'redis',
                'queue' => ['webhooks-manual'],
                'balance' => 'simple',
                'maxProcesses' => 2,
                'memory' => 256,
                'timeout' => 600,
                'tries' => 1,
            ],
        ],
    ],
    
    'metrics' => [
        'trim_snapshots' => [
            'job' => 24 * 7, // Keep 1 week
            'queue' => 24 * 7,
        ],
    ],
];
```

### 3.2 Queue Priority Matrix

| Provider | Event Type | Queue | Priority | Timeout | Retries |
|----------|------------|-------|----------|---------|---------|
| Retell | call_inbound | webhooks-critical | Critical | 60s | 1 |
| Retell | call_ended | webhooks-high | High | 120s | 3 |
| Retell | call_analyzed | webhooks | Normal | 180s | 3 |
| Cal.com | BOOKING_CREATED | webhooks-high | High | 120s | 3 |
| Cal.com | BOOKING_CANCELLED | webhooks-high | High | 120s | 3 |
| Stripe | payment_intent.* | webhooks-critical | Critical | 60s | 3 |
| Default | - | webhooks | Normal | 180s | 3 |

### 3.3 Worker Configuration

```bash
# Supervisor configuration for production
[program:horizon]
process_name=%(program_name)s
command=php /var/www/api-gateway/artisan horizon
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/log/horizon.log
stopwaitsecs=3600

# Health check script
[program:horizon-health]
process_name=%(program_name)s
command=/usr/local/bin/horizon-health-check.sh
autostart=true
autorestart=true
startretries=0
exitcodes=0
```

### 3.4 Monitoring & Alerting

```php
namespace App\Services\Monitoring;

class WebhookQueueMonitor
{
    /**
     * Monitor queue health metrics
     */
    public function checkHealth(): array
    {
        $metrics = [
            'queues' => [],
            'failed_jobs' => DB::table('failed_jobs')->count(),
            'dead_letters' => DeadLetterWebhook::where('permanently_failed', false)->count(),
            'redis_memory' => $this->getRedisMemoryUsage(),
            'oldest_job' => $this->getOldestJobAge(),
        ];
        
        $queues = [
            'webhooks-critical',
            'webhooks-high', 
            'webhooks',
            'webhooks-retry',
            'webhooks-manual'
        ];
        
        foreach ($queues as $queue) {
            $metrics['queues'][$queue] = [
                'size' => Redis::llen("queues:{$queue}"),
                'processing' => Redis::zcard("horizon:{$queue}:processing"),
                'delayed' => Redis::zcard("horizon:{$queue}:delayed"),
                'reserved' => Redis::zcard("horizon:{$queue}:reserved"),
            ];
        }
        
        return $metrics;
    }
    
    /**
     * Alert on queue issues
     */
    public function checkAlerts(): array
    {
        $alerts = [];
        
        // Check queue sizes
        $queueSizes = $this->checkHealth()['queues'];
        
        foreach ($queueSizes as $queue => $stats) {
            // Critical queue should never back up
            if ($queue === 'webhooks-critical' && $stats['size'] > 100) {
                $alerts[] = [
                    'level' => 'critical',
                    'queue' => $queue,
                    'message' => "Critical queue backlog: {$stats['size']} jobs",
                    'action' => 'Scale workers immediately'
                ];
            }
            
            // High priority queue threshold
            if ($queue === 'webhooks-high' && $stats['size'] > 500) {
                $alerts[] = [
                    'level' => 'warning',
                    'queue' => $queue,
                    'message' => "High priority queue backlog: {$stats['size']} jobs",
                    'action' => 'Monitor and consider scaling'
                ];
            }
            
            // Check for stuck jobs
            if ($stats['processing'] > 0 && $stats['reserved'] > 10) {
                $alerts[] = [
                    'level' => 'warning',
                    'queue' => $queue,
                    'message' => "Possible stuck jobs: {$stats['reserved']} reserved",
                    'action' => 'Check worker health'
                ];
            }
        }
        
        // Check failed jobs
        $failedCount = DB::table('failed_jobs')
            ->where('failed_at', '>', now()->subHour())
            ->count();
            
        if ($failedCount > 10) {
            $alerts[] = [
                'level' => 'warning',
                'message' => "{$failedCount} jobs failed in last hour",
                'action' => 'Review failure reasons'
            ];
        }
        
        return $alerts;
    }
}
```

### 3.5 Performance Metrics

```php
namespace App\Services\Monitoring;

use Prometheus\CollectorRegistry;
use Prometheus\Storage\Redis;

class WebhookMetricsCollector
{
    private CollectorRegistry $registry;
    
    public function __construct()
    {
        $this->registry = new CollectorRegistry(new Redis());
    }
    
    /**
     * Record webhook processing metrics
     */
    public function recordWebhookProcessed(
        string $provider,
        string $eventType,
        bool $success,
        float $duration,
        bool $wasDuplicate = false
    ): void {
        // Counter for total webhooks
        $counter = $this->registry->getOrRegisterCounter(
            'askproai',
            'webhooks_total',
            'Total webhooks processed',
            ['provider', 'event_type', 'status', 'duplicate']
        );
        
        $counter->incBy(
            1,
            [
                $provider,
                $eventType,
                $success ? 'success' : 'failure',
                $wasDuplicate ? 'true' : 'false'
            ]
        );
        
        // Histogram for processing duration
        if (!$wasDuplicate) {
            $histogram = $this->registry->getOrRegisterHistogram(
                'askproai',
                'webhook_duration_seconds',
                'Webhook processing duration',
                ['provider', 'event_type'],
                [0.01, 0.05, 0.1, 0.5, 1, 2, 5, 10]
            );
            
            $histogram->observe(
                $duration,
                [$provider, $eventType]
            );
        }
        
        // Gauge for queue sizes
        $gauge = $this->registry->getOrRegisterGauge(
            'askproai',
            'webhook_queue_size',
            'Current webhook queue size',
            ['queue']
        );
        
        foreach (['critical', 'high', 'normal', 'retry'] as $priority) {
            $queueName = "webhooks" . ($priority !== 'normal' ? "-{$priority}" : '');
            $size = Redis::llen("queues:{$queueName}");
            $gauge->set($size, [$queueName]);
        }
    }
    
    /**
     * Export metrics for Prometheus
     */
    public function renderMetrics(): string
    {
        $renderer = new \Prometheus\RenderTextFormat();
        return $renderer->render($this->registry->getMetricFamilySamples());
    }
}
```

## 4. Implementation Roadmap

### Phase 1: Redis Infrastructure (Tag: v1.0.0-redis)
1. Implement `AtomicWebhookDeduplicator` class
2. Add Redis health checks
3. Create migration script for existing webhook data
4. Deploy Redis Cluster configuration

### Phase 2: Async Queue System (Tag: v1.1.0-async)
1. Create `ProcessWebhookJob` and related jobs
2. Refactor controllers for async processing
3. Implement retry strategies
4. Configure Horizon supervisors

### Phase 3: Monitoring & Optimization (Tag: v1.2.0-monitoring)
1. Deploy Prometheus metrics
2. Create Grafana dashboards
3. Implement alerting rules
4. Performance tuning based on metrics

### Phase 4: Production Rollout (Tag: v2.0.0-production)
1. Canary deployment (10% traffic)
2. Monitor metrics and adjust
3. Full rollout
4. Documentation and training

## 5. Testing Strategy

### 5.1 Unit Tests

```php
class AtomicWebhookDeduplicatorTest extends TestCase
{
    public function test_prevents_duplicate_processing()
    {
        $deduplicator = new AtomicWebhookDeduplicator();
        $key = 'test-webhook-123';
        
        // First request
        $result1 = $deduplicator->checkAndMark($key);
        $this->assertFalse($result1['is_duplicate']);
        
        // Duplicate request
        $result2 = $deduplicator->checkAndMark($key);
        $this->assertTrue($result2['is_duplicate']);
    }
    
    public function test_handles_concurrent_requests()
    {
        // Simulate concurrent requests using processes
        $results = [];
        $processes = [];
        
        for ($i = 0; $i < 10; $i++) {
            $process = new Process(['php', 'artisan', 'test:webhook-race']);
            $process->start();
            $processes[] = $process;
        }
        
        foreach ($processes as $process) {
            $process->wait();
            $results[] = json_decode($process->getOutput(), true);
        }
        
        // Only one should succeed
        $successful = array_filter($results, fn($r) => !$r['is_duplicate']);
        $this->assertCount(1, $successful);
    }
}
```

### 5.2 Integration Tests

```php
class WebhookQueueIntegrationTest extends TestCase
{
    public function test_webhook_queued_and_processed()
    {
        Queue::fake();
        
        $response = $this->postJson('/api/webhook/retell', [
            'event' => 'call_ended',
            'call_id' => 'test-123'
        ]);
        
        $response->assertStatus(202);
        $response->assertJson(['queued' => true]);
        
        Queue::assertPushed(ProcessWebhookJob::class, function ($job) {
            return $job->provider === 'retell';
        });
    }
    
    public function test_dead_letter_queue_retry()
    {
        // Create failed webhook
        $deadLetter = DeadLetterWebhook::factory()->create();
        
        // Retry
        $manager = new DeadLetterQueueManager();
        $result = $manager->manualRetry($deadLetter->id);
        
        $this->assertTrue($result);
        Queue::assertPushed(ProcessWebhookJob::class);
    }
}
```

### 5.3 Load Testing

```bash
# Locust load test configuration
from locust import HttpUser, task, between
import uuid
import json

class WebhookLoadTest(HttpUser):
    wait_time = between(0.1, 0.5)
    
    @task(3)
    def send_duplicate_webhook(self):
        # Same call_id = duplicate
        payload = {
            "event": "call_ended",
            "call_id": "load-test-duplicate",
            "timestamp": 1234567890
        }
        
        self.client.post(
            "/api/webhook/retell",
            json=payload,
            headers={"x-correlation-id": str(uuid.uuid4())}
        )
    
    @task(7)
    def send_unique_webhook(self):
        # Unique call_id each time
        payload = {
            "event": "call_ended", 
            "call_id": f"load-test-{uuid.uuid4()}",
            "timestamp": int(time.time())
        }
        
        self.client.post(
            "/api/webhook/retell",
            json=payload,
            headers={"x-correlation-id": str(uuid.uuid4())}
        )

# Run: locust -f webhook_load_test.py --host=https://api.askproai.de
```

## 6. Migration Strategy

### 6.1 Data Migration Script

```php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\WebhookEvent;
use Illuminate\Support\Facades\Redis;

class MigrateWebhookDeduplication extends Command
{
    protected $signature = 'webhooks:migrate-deduplication 
        {--batch=1000 : Number of records per batch}
        {--dry-run : Run without making changes}';
    
    protected $description = 'Migrate existing webhook data to Redis deduplication';
    
    public function handle()
    {
        $this->info('Starting webhook deduplication migration...');
        
        $dryRun = $this->option('dry-run');
        $batchSize = $this->option('batch');
        $processed = 0;
        $migrated = 0;
        
        WebhookEvent::where('status', 'completed')
            ->where('created_at', '>', now()->subDays(7))
            ->chunk($batchSize, function ($webhooks) use (&$processed, &$migrated, $dryRun) {
                foreach ($webhooks as $webhook) {
                    $processed++;
                    
                    $resultKey = "webhook:result:{$webhook->idempotency_key}";
                    
                    if (!$dryRun) {
                        // Calculate remaining TTL
                        $age = $webhook->created_at->diffInSeconds(now());
                        $ttl = max(86400 - $age, 3600); // At least 1 hour
                        
                        Redis::setex($resultKey, $ttl, json_encode([
                            'success' => true,
                            'result' => $webhook->result ?? [],
                            'processed_at' => $webhook->processed_at->toIso8601String(),
                            'migrated' => true
                        ]));
                        
                        $migrated++;
                    }
                    
                    if ($processed % 100 === 0) {
                        $this->info("Processed: {$processed}, Migrated: {$migrated}");
                    }
                }
            });
        
        $this->info("Migration complete! Processed: {$processed}, Migrated: {$migrated}");
    }
}
```

### 6.2 Rollback Plan

```php
class WebhookRollbackManager
{
    /**
     * Switch between old and new deduplication systems
     */
    public static function useRedisDeduplication(bool $enabled = true): void
    {
        Cache::put('webhook:use_redis_dedup', $enabled, now()->addDays(30));
        
        Log::info('Webhook deduplication switched', [
            'redis_dedup_enabled' => $enabled
        ]);
    }
    
    /**
     * Check which system to use
     */
    public static function shouldUseRedis(): bool
    {
        return Cache::get('webhook:use_redis_dedup', true);
    }
}
```

## 7. Sicherheitsüberlegungen

### 7.1 Redis Security

```yaml
# Redis ACL Configuration
user webhook-service +@read +@write +@list +@set +@string ~webhook:* on >strong-password

# Limit memory usage
maxmemory 2gb
maxmemory-policy allkeys-lru

# Enable persistence for critical data
save 900 1
save 300 10
save 60 10000
```

### 7.2 Rate Limiting per Provider

```php
class WebhookRateLimiter
{
    public static function attempt(string $provider, string $identifier): bool
    {
        $limits = [
            'retell' => ['limit' => 1000, 'window' => 60],
            'calcom' => ['limit' => 500, 'window' => 60],
            'stripe' => ['limit' => 2000, 'window' => 60],
        ];
        
        $config = $limits[$provider] ?? ['limit' => 100, 'window' => 60];
        $key = "rate_limit:{$provider}:{$identifier}";
        
        $current = Redis::incr($key);
        
        if ($current === 1) {
            Redis::expire($key, $config['window']);
        }
        
        return $current <= $config['limit'];
    }
}
```

## 8. Wartung & Betrieb

### 8.1 Wartungsskripte

```bash
#!/bin/bash
# webhook-maintenance.sh

# Clear old deduplication keys
redis-cli --scan --pattern "webhook:result:*" | while read key; do
    ttl=$(redis-cli ttl "$key")
    if [ "$ttl" -lt 0 ]; then
        redis-cli del "$key"
    fi
done

# Archive old dead letters
php artisan webhooks:archive-dead-letters --days=30

# Generate health report
php artisan webhooks:health-report --email=ops@askproai.de
```

### 8.2 Monitoring Dashboard

```php
// Filament Dashboard Page
namespace App\Filament\Pages;

class WebhookQueueDashboard extends Page
{
    protected static string $view = 'filament.pages.webhook-queue-dashboard';
    
    public function getStats(): array
    {
        $monitor = new WebhookQueueMonitor();
        $metrics = new WebhookMetricsCollector();
        
        return [
            'health' => $monitor->checkHealth(),
            'alerts' => $monitor->checkAlerts(),
            'processing_rate' => $this->getProcessingRate(),
            'error_rate' => $this->getErrorRate(),
            'duplicate_rate' => $this->getDuplicateRate(),
        ];
    }
}
```

Diese Spezifikation definiert eine robuste, skalierbare Lösung für Webhook-Verarbeitung mit atomarer Deduplikation und optimaler Queue-Performance.