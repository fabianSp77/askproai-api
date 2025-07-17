# Queue & Horizon Documentation - AskProAI

## Table of Contents

1. [Architecture Overview](#architecture-overview)
2. [Queue Configuration](#queue-configuration)
3. [Laravel Horizon Setup](#laravel-horizon-setup)
4. [Job Types & Priorities](#job-types--priorities)
5. [Queue Workers & Supervisors](#queue-workers--supervisors)
6. [Failed Job Handling](#failed-job-handling)
7. [Monitoring & Metrics](#monitoring--metrics)
8. [Performance Tuning](#performance-tuning)
9. [Scaling Strategies](#scaling-strategies)
10. [Troubleshooting Guide](#troubleshooting-guide)
11. [Emergency Procedures](#emergency-procedures)
12. [Best Practices](#best-practices)

## Architecture Overview

### Queue System Components

```
┌─────────────────────┐
│   Application       │
│  (API/Webhooks)     │
└──────────┬──────────┘
           │ Dispatch Jobs
           ▼
┌─────────────────────┐
│      Redis          │ ◀── Queue Storage
│  (Queue Backend)    │
└──────────┬──────────┘
           │ Process Jobs
           ▼
┌─────────────────────┐
│   Laravel Horizon   │ ◀── Queue Manager
│   (Supervisors)     │
└──────────┬──────────┘
           │ Execute
           ▼
┌─────────────────────┐
│    Job Workers      │
│  (PHP Processes)    │
└─────────────────────┘
```

### Key Technologies

- **Queue Backend**: Redis (primary), Database (fallback)
- **Queue Manager**: Laravel Horizon
- **Monitoring**: Horizon Dashboard, Custom Commands
- **Job Processing**: Async workers with auto-scaling

## Queue Configuration

### Environment Variables

```bash
# Queue Connection
QUEUE_CONNECTION=redis

# Redis Configuration
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_CLIENT=phpredis

# Horizon Configuration
HORIZON_PREFIX=askproai_horizon
HORIZON_PATH=horizon
HORIZON_DOMAIN=null

# Queue Performance
QUEUE_RETRY_AFTER=90
QUEUE_BLOCK_FOR=null
```

### Queue Configuration File

```php
// config/queue.php
'redis' => [
    'driver' => 'redis',
    'connection' => 'default',
    'queue' => env('REDIS_QUEUE', 'default'),
    'retry_after' => 90,
    'block_for' => null,
    'after_commit' => false,
],
```

## Laravel Horizon Setup

### Installation & Configuration

```bash
# Install Horizon
composer require laravel/horizon

# Publish assets
php artisan horizon:install

# Start Horizon
php artisan horizon

# Horizon in production (via Supervisor)
sudo supervisorctl start horizon
```

### Horizon Dashboard Access

- **URL**: `https://api.askproai.de/horizon`
- **Authentication**: Admin-only access via Filament
- **Middleware**: `web` middleware group

### Horizon Configuration

```php
// config/horizon.php
'environments' => [
    'production' => [
        'supervisor-1' => [
            'maxProcesses' => 20,
            'minProcesses' => 5,
            'balanceMaxShift' => 5,
            'balanceCooldown' => 3,
        ],
        'webhooks' => [
            'maxProcesses' => 30,
            'minProcesses' => 10,
            'memory' => 256,
            'tries' => 3,
            'timeout' => 90,
        ],
        'emails' => [
            'maxProcesses' => 10,
            'minProcesses' => 2,
            'timeout' => 300,
        ],
    ],
],
```

## Job Types & Priorities

### Queue Names & Priority

1. **webhooks-high** (Priority: Highest)
   - Critical webhook processing
   - Real-time call events
   - Payment confirmations
   - Timeout: 120s
   - Max Processes: 20

2. **webhooks** (Priority: High)
   - Standard webhook processing
   - Call ended events
   - Appointment webhooks
   - Timeout: 90s
   - Max Processes: 30

3. **mcp-high** (Priority: High)
   - Critical MCP operations
   - Real-time integrations
   - Timeout: 60s
   - Max Processes: 25

4. **appointments** (Priority: Medium)
   - Appointment creation/updates
   - Calendar synchronization
   - Availability checks
   - Timeout: 120s
   - Max Processes: 15

5. **emails** (Priority: Medium)
   - Email notifications
   - Call summaries
   - Appointment confirmations
   - Timeout: 300s
   - Max Processes: 10

6. **default** (Priority: Low)
   - General background tasks
   - Data synchronization
   - Report generation
   - Timeout: 60s
   - Max Processes: 20

### Job Classes

```php
// High Priority Jobs
ProcessRetellCallEndedJob::class      // webhooks queue
ProcessStripeWebhookJob::class        // webhooks-high queue
SendCallSummaryEmailJob::class        // emails queue

// Medium Priority Jobs
SyncCalcomEventTypesJob::class        // appointments queue
SendAppointmentReminderJob::class     // emails queue

// Low Priority Jobs
WarmCacheJob::class                   // default queue
CollectMetricsJob::class              // default queue
```

## Queue Workers & Supervisors

### Supervisor Configuration

```ini
[program:horizon]
process_name=%(program_name)s
command=php /var/www/api-gateway/artisan horizon
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/api-gateway/storage/logs/horizon.log
stopwaitsecs=3600
```

### Worker Auto-Scaling

```php
// Horizon auto-scaling configuration
'webhooks' => [
    'connection' => 'redis',
    'queue' => ['webhooks'],
    'balance' => 'auto',
    'autoScalingStrategy' => 'time',
    'maxProcesses' => 30,
    'minProcesses' => 10,
    'balanceMaxShift' => 10,
    'balanceCooldown' => 1,
],
```

### Memory Management

```php
// Memory limits per supervisor
'memory' => 256,  // MB per worker
'memory_limit' => 128,  // MB for Horizon master

// Job-specific memory limits
public $timeout = 300;
public $failOnTimeout = true;
```

## Failed Job Handling

### Retry Configuration

```php
class ProcessRetellCallEndedJob implements ShouldQueue
{
    public $tries = 3;
    public $backoff = [10, 30, 60];  // Exponential backoff
    public $maxExceptions = 3;
    
    public function retryUntil()
    {
        return now()->addHours(24);
    }
}
```

### Failed Job Management

```bash
# View failed jobs
php artisan queue:failed

# Retry specific job
php artisan queue:retry 5e7b4c3d-5b9a-4f6d-9c0e-1a2b3c4d5e6f

# Retry all failed jobs
php artisan queue:retry all

# Delete failed job
php artisan queue:forget 5e7b4c3d-5b9a-4f6d-9c0e-1a2b3c4d5e6f

# Flush all failed jobs
php artisan queue:flush
```

### Failed Job Table

```sql
-- Check failed jobs
SELECT id, uuid, queue, failed_at, exception 
FROM failed_jobs 
ORDER BY failed_at DESC 
LIMIT 10;

-- Failed jobs by queue
SELECT queue, COUNT(*) as count 
FROM failed_jobs 
GROUP BY queue;
```

## Monitoring & Metrics

### Horizon Dashboard Metrics

1. **Jobs Per Minute**: Real-time throughput
2. **Recent Jobs**: Last 60 minutes history
3. **Failed Jobs**: Failure tracking
4. **Queue Sizes**: Current backlog
5. **Worker Status**: Active/Idle workers

### Custom Monitoring Commands

```bash
# Queue health check
php artisan queue:monitor

# Horizon status
php artisan horizon:status

# Queue statistics
php artisan horizon:stats
```

### Monitoring Implementation

```php
// app/Console/Commands/QueueMonitor.php
class QueueMonitor extends Command
{
    public function handle()
    {
        // Check failed jobs
        $failedJobs = DB::table('failed_jobs')->count();
        
        // Check queue size
        $queueSize = DB::table('jobs')->count();
        
        // Check stuck jobs
        $stuckJobs = DB::table('jobs')
            ->where('created_at', '<', now()->subHours(2))
            ->count();
            
        // Alert if issues detected
    }
}
```

### Prometheus Metrics

```php
// Available metrics
horizon_jobs_processed_total
horizon_jobs_failed_total
horizon_queue_size
horizon_worker_count
horizon_memory_usage
```

## Performance Tuning

### Redis Optimization

```bash
# Redis configuration for queues
maxmemory 2gb
maxmemory-policy allkeys-lru
timeout 0
tcp-keepalive 60
```

### Worker Optimization

```php
// Optimize worker performance
'maxProcesses' => 30,        // Scale based on load
'minProcesses' => 10,        // Always keep minimum
'balanceMaxShift' => 10,     // Aggressive scaling
'balanceCooldown' => 1,      // Fast response
'memory' => 256,             // Adequate memory
'timeout' => 90,             // Reasonable timeout
```

### Job Optimization Tips

1. **Chunk Large Operations**
   ```php
   User::chunk(1000, function ($users) {
       foreach ($users as $user) {
           ProcessUserJob::dispatch($user);
       }
   });
   ```

2. **Use Job Batching**
   ```php
   Bus::batch([
       new ProcessCallJob($call1),
       new ProcessCallJob($call2),
   ])->dispatch();
   ```

3. **Implement Rate Limiting**
   ```php
   Redis::throttle('key')->allow(10)->every(60)->then(function () {
       // Process job
   });
   ```

## Scaling Strategies

### Horizontal Scaling

```yaml
# Docker Compose scaling
services:
  horizon:
    image: askproai/app
    command: php artisan horizon
    deploy:
      replicas: 3
```

### Queue Partitioning

```php
// Partition by company
$queue = "webhooks-company-{$companyId}";
ProcessWebhookJob::dispatch($data)->onQueue($queue);

// Configure dedicated workers
'company-webhooks' => [
    'queue' => ['webhooks-company-*'],
    'maxProcesses' => 50,
],
```

### Priority Queue Pattern

```php
// High priority processing
ProcessUrgentJob::dispatch($data)
    ->onQueue('high-priority')
    ->beforeCommit();

// Bulk operations
BulkProcessJob::dispatch($data)
    ->onQueue('low-priority')
    ->delay(now()->addMinutes(5));
```

## Troubleshooting Guide

### Common Issues

#### 1. Jobs Not Processing

```bash
# Check Horizon status
php artisan horizon:status

# Check Redis connection
redis-cli ping

# Check worker processes
ps aux | grep horizon

# Restart Horizon
php artisan horizon:terminate
php artisan horizon
```

#### 2. High Memory Usage

```bash
# Check memory per worker
ps aux | grep "horizon:work" | awk '{print $6/1024 " MB"}'

# Restart workers with lower memory
php artisan horizon:terminate
php artisan config:cache
php artisan horizon
```

#### 3. Stuck Jobs

```sql
-- Find stuck jobs
SELECT * FROM jobs 
WHERE created_at < NOW() - INTERVAL 2 HOUR
AND reserved_at IS NULL;

-- Clear stuck jobs
DELETE FROM jobs 
WHERE created_at < NOW() - INTERVAL 24 HOUR;
```

#### 4. Failed Job Loop

```php
// Add circuit breaker
public function handle()
{
    $key = "job-attempts:{$this->id}";
    $attempts = Cache::increment($key);
    
    if ($attempts > 10) {
        $this->fail(new Exception('Too many attempts'));
        return;
    }
    
    // Process job
}
```

### Debug Commands

```bash
# Monitor queue in real-time
php artisan queue:listen --tries=1 -vvv

# Test specific job
php artisan tinker
>>> dispatch(new TestJob());

# Check Redis keys
redis-cli
> KEYS horizon:*
> LLEN queues:default
```

## Emergency Procedures

### 1. Queue Completely Stuck

```bash
# Emergency restart
sudo supervisorctl stop horizon
redis-cli FLUSHDB  # WARNING: Clears all Redis data
sudo supervisorctl start horizon

# Safer approach
php artisan horizon:pause
php artisan horizon:clear
php artisan horizon:continue
```

### 2. Memory Exhaustion

```bash
# Immediate relief
php artisan horizon:pause-supervisor webhooks
pkill -f "horizon:supervisor"
php artisan horizon:continue-supervisor webhooks

# Reduce workers
php artisan horizon:scale webhooks=5
```

### 3. Redis Connection Lost

```bash
# Fallback to database queue
php artisan config:set queue.default database
php artisan config:cache
php artisan queue:restart

# Monitor database queue
watch -n 1 'mysql -e "SELECT COUNT(*) FROM jobs"'
```

### 4. Mass Job Failure

```php
// Emergency job bypass
class EmergencyBypass
{
    public static function clearQueue($queue)
    {
        Redis::del("queues:{$queue}");
        
        DB::table('failed_jobs')
            ->where('queue', $queue)
            ->where('failed_at', '>', now()->subHour())
            ->delete();
    }
}
```

## Best Practices

### 1. Job Design

```php
// Good: Idempotent job
public function handle()
{
    $call = Call::find($this->callId);
    
    if (!$call || $call->processed_at) {
        return;  // Already processed
    }
    
    // Process call
    $call->update(['processed_at' => now()]);
}
```

### 2. Error Handling

```php
public function handle()
{
    try {
        // Main logic
    } catch (ApiException $e) {
        // Retry for API errors
        $this->release(60);
    } catch (Exception $e) {
        // Log and fail for other errors
        Log::error('Job failed', [
            'job' => class_basename($this),
            'error' => $e->getMessage(),
        ]);
        
        $this->fail($e);
    }
}
```

### 3. Monitoring Integration

```php
// Add to critical jobs
public function handle()
{
    $startTime = microtime(true);
    
    try {
        // Process job
        
        // Record success metric
        app('prometheus.queue')
            ->histogram('job_duration_seconds')
            ->observe(microtime(true) - $startTime);
            
    } catch (Exception $e) {
        // Record failure metric
        app('prometheus.queue')
            ->counter('job_failures_total')
            ->inc(['job' => class_basename($this)]);
            
        throw $e;
    }
}
```

### 4. Queue Maintenance

```bash
# Daily maintenance (add to cron)
0 2 * * * php /var/www/api-gateway/artisan horizon:snapshot
0 3 * * * php /var/www/api-gateway/artisan queue:prune-failed --hours=168
0 4 * * * php /var/www/api-gateway/artisan horizon:trim
```

### 5. Documentation

Always document:
- Queue purpose and priority
- Expected processing time
- Retry strategy
- Failure handling
- Dependencies

```php
/**
 * Process Retell webhook for call ended event
 * 
 * Queue: webhooks
 * Priority: High
 * Timeout: 90s
 * Retries: 3 with exponential backoff
 * 
 * Dependencies:
 * - Redis for caching
 * - Cal.com API for appointments
 * - Database for call records
 */
class ProcessRetellCallEndedJob implements ShouldQueue
{
    // Implementation
}
```

## Quick Reference

### Essential Commands

```bash
# Horizon Management
php artisan horizon              # Start Horizon
php artisan horizon:status       # Check status
php artisan horizon:pause        # Pause processing
php artisan horizon:continue     # Resume processing
php artisan horizon:terminate    # Graceful shutdown

# Queue Management
php artisan queue:failed         # List failed jobs
php artisan queue:retry all      # Retry all failed
php artisan queue:flush          # Delete all failed
php artisan queue:monitor        # Health check

# Scaling
php artisan horizon:scale webhooks=20  # Scale specific queue
php artisan horizon:scale              # Show current scale

# Monitoring
php artisan horizon:snapshot     # Capture metrics
php artisan horizon:metrics      # View metrics
```

### Performance Benchmarks

| Queue Type | Target Throughput | Max Latency | Success Rate |
|------------|------------------|-------------|--------------|
| webhooks-high | 500/min | 5s | 99.9% |
| webhooks | 1000/min | 30s | 99.5% |
| appointments | 300/min | 60s | 99.0% |
| emails | 200/min | 300s | 98.0% |
| default | 500/min | 120s | 95.0% |

### Health Indicators

🟢 **Healthy**
- Failed jobs < 10
- Queue size < 1000
- No jobs older than 2 hours
- Worker memory < 80%

🟡 **Warning**
- Failed jobs 10-50
- Queue size 1000-5000
- Jobs 2-6 hours old
- Worker memory 80-90%

🔴 **Critical**
- Failed jobs > 50
- Queue size > 5000
- Jobs > 6 hours old
- Worker memory > 90%