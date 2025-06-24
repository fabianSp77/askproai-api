# Queue Configuration

## Overview

AskProAI uses Laravel Horizon to manage Redis-based queues for processing webhooks, sending notifications, and handling background tasks. This guide covers queue configuration, job processing, and monitoring.

## Queue Drivers

### Redis Configuration
```php
// config/queue.php
return [
    'default' => env('QUEUE_CONNECTION', 'redis'),
    
    'connections' => [
        'redis' => [
            'driver' => 'redis',
            'connection' => 'queue',
            'queue' => env('REDIS_QUEUE', 'default'),
            'retry_after' => 90,
            'block_for' => 5,
            'after_commit' => false,
        ],
        
        'redis-long-running' => [
            'driver' => 'redis',
            'connection' => 'queue',
            'queue' => 'long-running',
            'retry_after' => 3600, // 1 hour
            'block_for' => 5,
        ],
        
        'sync' => [
            'driver' => 'sync',
        ],
        
        'database' => [
            'driver' => 'database',
            'table' => 'jobs',
            'queue' => 'default',
            'retry_after' => 90,
        ],
    ],
    
    'failed' => [
        'driver' => env('QUEUE_FAILED_DRIVER', 'database-uuids'),
        'database' => env('DB_CONNECTION', 'mysql'),
        'table' => 'failed_jobs',
    ],
];
```

## Laravel Horizon Configuration

### Horizon Setup
```php
// config/horizon.php
return [
    'domain' => env('HORIZON_DOMAIN'),
    'path' => env('HORIZON_PATH', 'horizon'),
    'middleware' => ['web', 'auth', 'admin'],
    
    'waits' => [
        'redis:default' => 60,
        'redis:high' => 60,
        'redis:low' => 60,
    ],
    
    'trim' => [
        'recent' => 60,          // Keep recent jobs for 60 minutes
        'pending' => 60,         // Keep pending jobs for 60 minutes
        'completed' => 60,       // Keep completed jobs for 60 minutes
        'recent_failed' => 1440, // Keep failed jobs for 24 hours
        'failed' => 10080,       // Keep all failed jobs for 7 days
        'monitored' => 10080,    // Keep monitored jobs for 7 days
    ],
    
    'metrics' => [
        'trim_snapshots' => [
            'job' => 24,         // 24 hours
            'queue' => 24,       // 24 hours
        ],
    ],
    
    'fast_termination' => false,
    
    'memory_limit' => env('HORIZON_MEMORY_LIMIT', 128),
    
    'environments' => [
        'production' => [
            'supervisor-1' => [
                'connection' => 'redis',
                'queue' => ['high', 'default'],
                'balance' => 'auto',
                'autoScalingStrategy' => 'time',
                'maxProcesses' => 10,
                'minProcesses' => 1,
                'balanceMaxShift' => 1,
                'balanceCooldown' => 3,
                'memory' => 128,
                'tries' => 3,
                'timeout' => 60,
                'nice' => 0,
            ],
            
            'webhooks' => [
                'connection' => 'redis',
                'queue' => ['webhooks'],
                'balance' => 'simple',
                'processes' => 5,
                'memory' => 128,
                'tries' => 3,
                'timeout' => 30,
            ],
            
            'notifications' => [
                'connection' => 'redis',
                'queue' => ['emails', 'sms'],
                'balance' => 'simple',
                'processes' => 3,
                'memory' => 128,
                'tries' => 3,
                'timeout' => 60,
            ],
            
            'long-running' => [
                'connection' => 'redis-long-running',
                'queue' => ['reports', 'imports'],
                'balance' => 'simple',
                'processes' => 2,
                'memory' => 256,
                'tries' => 1,
                'timeout' => 3600,
            ],
        ],
        
        'local' => [
            'supervisor-1' => [
                'maxProcesses' => 3,
                'balanceMaxShift' => 1,
                'balanceCooldown' => 3,
            ],
        ],
    ],
];
```

## Queue Priorities

### Queue Structure
```php
// app/Enums/QueuePriority.php
enum QueuePriority: string
{
    case CRITICAL = 'critical';    // Payment processing, urgent webhooks
    case HIGH = 'high';           // Appointment bookings, call processing
    case DEFAULT = 'default';     // General processing
    case LOW = 'low';            // Reports, analytics
    case WEBHOOKS = 'webhooks';   // External webhooks
    case EMAILS = 'emails';       // Email notifications
    case SMS = 'sms';            // SMS notifications
}
```

### Job Dispatching
```php
// High priority job
ProcessPayment::dispatch($payment)->onQueue(QueuePriority::CRITICAL);

// Normal priority
ProcessRetellWebhook::dispatch($webhookData)->onQueue(QueuePriority::WEBHOOKS);

// Low priority
GenerateMonthlyReport::dispatch($company)->onQueue(QueuePriority::LOW);

// With delay
SendAppointmentReminder::dispatch($appointment)
    ->onQueue(QueuePriority::EMAILS)
    ->delay(now()->addHours(24));
```

## Job Classes

### Base Job Class
```php
// app/Jobs/BaseJob.php
abstract class BaseJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public $tries = 3;
    public $timeout = 120;
    public $maxExceptions = 3;
    
    public function middleware()
    {
        return [
            new WithoutOverlapping($this->uniqueId()),
            new RateLimited('api-calls'),
            (new ThrottlesExceptions(3, 10))->backoff(5),
        ];
    }
    
    public function uniqueId(): string
    {
        return static::class;
    }
    
    public function failed(Throwable $exception): void
    {
        Log::error('Job failed', [
            'job' => static::class,
            'exception' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
        
        // Notify administrators
        $this->notifyFailure($exception);
    }
    
    protected function notifyFailure(Throwable $exception): void
    {
        Notification::route('mail', config('app.admin_email'))
            ->notify(new JobFailedNotification($this, $exception));
    }
}
```

### Webhook Processing Job
```php
// app/Jobs/ProcessRetellWebhook.php
class ProcessRetellWebhook extends BaseJob
{
    public $tries = 5;
    public $backoff = [10, 30, 60, 120, 300]; // Exponential backoff
    
    public function __construct(
        public array $webhookData,
        public string $webhookId
    ) {}
    
    public function uniqueId(): string
    {
        return "retell-webhook-{$this->webhookId}";
    }
    
    public function handle()
    {
        // Prevent duplicate processing
        if ($this->isDuplicate()) {
            Log::info('Duplicate webhook detected', ['webhook_id' => $this->webhookId]);
            return;
        }
        
        DB::transaction(function () {
            $handler = app(RetellWebhookHandler::class);
            
            match($this->webhookData['event_type']) {
                'call_started' => $handler->handleCallStarted($this->webhookData),
                'call_ended' => $handler->handleCallEnded($this->webhookData),
                'call_analyzed' => $handler->handleCallAnalyzed($this->webhookData),
                default => Log::warning('Unknown webhook event', $this->webhookData),
            };
            
            $this->markAsProcessed();
        });
    }
    
    protected function isDuplicate(): bool
    {
        return Cache::add("webhook:{$this->webhookId}", true, 3600) === false;
    }
    
    protected function markAsProcessed(): void
    {
        WebhookEvent::create([
            'provider' => 'retell',
            'event_id' => $this->webhookId,
            'event_type' => $this->webhookData['event_type'],
            'payload' => $this->webhookData,
            'processed_at' => now(),
        ]);
    }
}
```

### Notification Job
```php
// app/Jobs/SendAppointmentConfirmation.php
class SendAppointmentConfirmation extends BaseJob
{
    use InteractsWithQueue;
    
    public $queue = 'emails';
    
    public function __construct(
        public Appointment $appointment,
        public array $channels = ['email', 'sms']
    ) {}
    
    public function handle()
    {
        $customer = $this->appointment->customer;
        
        foreach ($this->channels as $channel) {
            match($channel) {
                'email' => $this->sendEmail($customer),
                'sms' => $this->sendSMS($customer),
                'whatsapp' => $this->sendWhatsApp($customer),
            };
        }
        
        $this->appointment->update([
            'confirmation_sent_at' => now(),
            'confirmation_channels' => $this->channels,
        ]);
    }
    
    protected function sendEmail(Customer $customer): void
    {
        if (!$customer->email) {
            return;
        }
        
        Mail::to($customer->email)->send(
            new AppointmentConfirmationMail($this->appointment)
        );
    }
    
    protected function sendSMS(Customer $customer): void
    {
        if (!$customer->phone || !$customer->sms_notifications) {
            return;
        }
        
        app(SMSService::class)->send(
            $customer->phone,
            $this->getSMSMessage()
        );
    }
}
```

## Job Batching

### Batch Processing
```php
// app/Jobs/ProcessMonthlyBilling.php
class ProcessMonthlyBilling extends BaseJob
{
    public function handle()
    {
        $companies = Company::active()->get();
        
        $batch = Bus::batch([])
            ->name('Monthly Billing - ' . now()->format('Y-m'))
            ->allowFailures()
            ->onQueue('billing')
            ->dispatch();
        
        foreach ($companies as $company) {
            $batch->add(new ProcessCompanyBilling($company));
        }
        
        return $batch;
    }
}

// Monitor batch progress
class BillingBatchMonitor
{
    public function checkProgress(string $batchId)
    {
        $batch = Bus::findBatch($batchId);
        
        return [
            'id' => $batch->id,
            'name' => $batch->name,
            'total_jobs' => $batch->totalJobs,
            'pending_jobs' => $batch->pendingJobs,
            'failed_jobs' => $batch->failedJobs,
            'progress' => $batch->progress(),
            'finished' => $batch->finished(),
            'cancelled' => $batch->cancelled(),
        ];
    }
}
```

## Job Chains

### Sequential Processing
```php
// app/Jobs/ProcessAppointmentBooking.php
class ProcessAppointmentBooking
{
    public function handle()
    {
        Bus::chain([
            new ValidateBookingData($this->data),
            new CheckAvailability($this->data),
            new CreateAppointment($this->data),
            new CreateCalcomBooking($this->data),
            new SendConfirmations($this->data),
            new UpdateStatistics($this->data),
        ])
        ->onQueue('bookings')
        ->onConnection('redis')
        ->catch(function (Throwable $e) {
            Log::error('Booking chain failed', [
                'error' => $e->getMessage(),
                'data' => $this->data,
            ]);
            
            // Rollback actions
            $this->rollbackBooking();
        })
        ->dispatch();
    }
}
```

## Rate Limiting

### Job Rate Limiting
```php
// app/Providers/AppServiceProvider.php
public function boot()
{
    RateLimiter::for('api-calls', function ($job) {
        return Limit::perMinute(60)->by($job->company_id);
    });
    
    RateLimiter::for('emails', function ($job) {
        return Limit::perHour(100)->by($job->customer_id);
    });
    
    RateLimiter::for('sms', function ($job) {
        return Limit::perDay(50)->by($job->customer_id);
    });
}

// Usage in job
public function middleware()
{
    return [
        new RateLimited('api-calls'),
    ];
}
```

## Queue Monitoring

### Health Checks
```php
// app/Services/Queue/QueueHealthCheck.php
class QueueHealthCheck
{
    public function check(): array
    {
        $queues = ['default', 'high', 'low', 'webhooks', 'emails'];
        $health = [];
        
        foreach ($queues as $queue) {
            $size = Redis::llen("queues:{$queue}");
            $failed = Redis::llen("queues:{$queue}:failed");
            
            $health[$queue] = [
                'size' => $size,
                'failed' => $failed,
                'status' => $this->getStatus($size),
                'workers' => $this->getWorkerCount($queue),
            ];
        }
        
        return $health;
    }
    
    protected function getStatus(int $size): string
    {
        return match(true) {
            $size === 0 => 'idle',
            $size < 100 => 'healthy',
            $size < 1000 => 'busy',
            default => 'overloaded',
        };
    }
    
    protected function getWorkerCount(string $queue): int
    {
        return collect(app('horizon')->supervisors())
            ->filter(fn($supervisor) => in_array($queue, $supervisor->options['queue']))
            ->sum('processes.total');
    }
}
```

### Queue Metrics
```php
// app/Console/Commands/QueueMetrics.php
class QueueMetrics extends Command
{
    protected $signature = 'queue:metrics';
    
    public function handle()
    {
        $metrics = [
            'job_throughput' => $this->getJobThroughput(),
            'average_wait_time' => $this->getAverageWaitTime(),
            'failure_rate' => $this->getFailureRate(),
            'queue_sizes' => $this->getQueueSizes(),
        ];
        
        $this->table(
            ['Metric', 'Value'],
            collect($metrics)->map(fn($value, $key) => [$key, $value])->toArray()
        );
    }
    
    protected function getJobThroughput(): string
    {
        $processed = Redis::get('horizon:measured_jobs');
        $time = Redis::get('horizon:measured_time') ?? 1;
        
        return round($processed / $time * 60) . ' jobs/minute';
    }
}
```

## Failed Job Handling

### Retry Strategy
```php
// app/Jobs/RetryableJob.php
abstract class RetryableJob extends BaseJob
{
    public $tries = 5;
    public $backoff = [30, 60, 180, 600, 3600]; // Progressive backoff
    
    public function retryUntil()
    {
        return now()->addDay();
    }
    
    public function shouldRetry(Throwable $exception): bool
    {
        // Don't retry validation errors
        if ($exception instanceof ValidationException) {
            return false;
        }
        
        // Don't retry if external service is down
        if ($exception instanceof ServiceUnavailableException) {
            return $this->attempts() < 2;
        }
        
        return true;
    }
}
```

### Failed Job Processing
```php
// app/Console/Commands/ProcessFailedJobs.php
class ProcessFailedJobs extends Command
{
    protected $signature = 'queue:retry-failed {--queue=} {--limit=10}';
    
    public function handle()
    {
        $queue = $this->option('queue');
        $limit = $this->option('limit');
        
        $query = DB::table('failed_jobs')
            ->orderBy('failed_at', 'desc')
            ->limit($limit);
        
        if ($queue) {
            $query->where('queue', $queue);
        }
        
        $failed = $query->get();
        
        foreach ($failed as $job) {
            if ($this->shouldRetry($job)) {
                $this->retry($job->uuid);
                $this->info("Retried job: {$job->uuid}");
            }
        }
    }
    
    protected function shouldRetry($job): bool
    {
        $payload = json_decode($job->payload, true);
        
        // Check if job has permanent failure
        if (str_contains($job->exception, 'ValidationException')) {
            return false;
        }
        
        // Check retry limit
        $attempts = $payload['attempts'] ?? 0;
        return $attempts < 10;
    }
}
```

## Queue Performance

### Optimization Tips
```php
// config/queue-optimization.php
return [
    // Chunk large datasets
    'chunk_size' => 100,
    
    // Batch database queries
    'batch_inserts' => true,
    
    // Use job batching for bulk operations
    'use_batches' => true,
    
    // Cache frequently accessed data
    'cache_ttl' => 300,
    
    // Limit concurrent jobs per user/company
    'concurrency_limits' => [
        'per_company' => 10,
        'per_user' => 5,
    ],
];
```

### Memory Management
```php
// app/Jobs/MemoryEfficientJob.php
abstract class MemoryEfficientJob extends BaseJob
{
    protected function processLargeDataset($query)
    {
        $query->chunk(100, function ($items) {
            foreach ($items as $item) {
                $this->processItem($item);
            }
            
            // Clear entity manager to free memory
            if (app()->bound('em')) {
                app('em')->clear();
            }
        });
    }
    
    public function handle()
    {
        ini_set('memory_limit', '256M');
        
        $this->processData();
        
        // Force garbage collection
        gc_collect_cycles();
    }
}
```

## Testing Queues

### Queue Testing
```php
// tests/Feature/Jobs/ProcessRetellWebhookTest.php
class ProcessRetellWebhookTest extends TestCase
{
    public function test_webhook_processing()
    {
        Queue::fake();
        
        // Trigger webhook
        $response = $this->postJson('/api/retell/webhook', [
            'event_type' => 'call_ended',
            'call_id' => 'test_123',
        ]);
        
        $response->assertStatus(200);
        
        // Assert job was dispatched
        Queue::assertPushed(ProcessRetellWebhook::class, function ($job) {
            return $job->webhookData['call_id'] === 'test_123';
        });
        
        // Process the job
        Queue::assertPushed(ProcessRetellWebhook::class, 1);
    }
    
    public function test_job_failure_handling()
    {
        Queue::fake();
        
        $job = new ProcessRetellWebhook(['invalid' => 'data'], 'test_id');
        
        try {
            $job->handle();
        } catch (\Exception $e) {
            $job->failed($e);
        }
        
        // Assert failure was handled
        Notification::assertSentTo(
            new AnonymousNotifiable,
            JobFailedNotification::class
        );
    }
}
```

## Related Documentation
- [Job Processing](../features/background-jobs.md)
- [Webhook Processing](../api/webhooks.md)
- [Performance Optimization](../operations/performance.md)
- [Monitoring](../operations/monitoring.md)