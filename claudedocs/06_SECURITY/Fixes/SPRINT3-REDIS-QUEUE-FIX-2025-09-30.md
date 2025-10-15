# Redis Queue Configuration Fix

**Date**: 2025-09-30
**Priority**: MEDIUM
**Status**: ✅ COMPLETED
**Impact**: Queue system alignment and consistency

---

## Problem Description

### Issue Identified

**Configuration Mismatch**: The Laravel application had misaligned queue driver configuration:

| Component | Configuration | Status |
|-----------|--------------|--------|
| **`.env` file** | `QUEUE_CONNECTION=redis` | ✅ Correct |
| **Supervisor Worker** | `queue:work database` | ❌ Incorrect |
| **Redis Service** | Running on port 6379 | ✅ Available |

**Impact**:
- Queue jobs were being dispatched to Redis (per `.env` config)
- Worker was processing from database queue (per supervisor config)
- Jobs dispatched to Redis were not being processed
- Potential job loss and processing delays

**Root Cause**: Supervisor configuration file was manually configured with `database` driver instead of reading from application configuration.

---

## Investigation Details

### Configuration Analysis

**1. Queue Configuration** (`config/queue.php`):
```php
'default' => env('QUEUE_CONNECTION', 'database'),

'connections' => [
    'redis' => [
        'driver' => 'redis',
        'connection' => env('REDIS_QUEUE_CONNECTION', 'default'),
        'queue' => env('REDIS_QUEUE', 'default'),
        'retry_after' => (int) env('REDIS_QUEUE_RETRY_AFTER', 90),
        'block_for' => null,
        'after_commit' => false,
    ],
    // ...
]
```

**2. Environment Configuration** (`.env`):
```env
QUEUE_CONNECTION=redis
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

**3. Supervisor Configuration** (`/etc/supervisor/conf.d/calcom-sync-queue.conf`):

**BEFORE** (Incorrect):
```ini
[program:calcom-sync-queue]
process_name=%(program_name)s_%(process_num)02d
command=/usr/bin/php /var/www/api-gateway/artisan queue:work database --queue=calcom-sync,default --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/log/supervisor/calcom-sync-queue.log
stopwaitsecs=3600
```

**AFTER** (Correct):
```ini
[program:calcom-sync-queue]
process_name=%(program_name)s_%(process_num)02d
command=/usr/bin/php /var/www/api-gateway/artisan queue:work redis --queue=calcom-sync,default --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/log/supervisor/calcom-sync-queue.log
stopwaitsecs=3600
```

**Change**: `queue:work database` → `queue:work redis`

### System State Verification

**Redis Service Status**:
```bash
$ redis-cli ping
PONG  # ✅ Redis running and responsive
```

**Database Queue Table**:
```bash
$ php artisan tinker --execute="echo DB::table('jobs')->count();"
0  # ✅ No pending jobs in database queue
```

**Redis Queue Status**:
```bash
$ redis-cli LLEN "queues:default"
0  # ✅ No pending jobs in Redis queue
```

**Running Workers** (Before Fix):
```bash
$ ps aux | grep "queue:work"
www-data 2444202  ... queue:work database --sleep=3 --tries=3 --max-time=3600
```

**Running Workers** (After Fix):
```bash
$ ps aux | grep "queue:work"
www-data 2447888  ... queue:work redis --queue=calcom-sync,default --sleep=3 --tries=3 --max-time=3600
```

---

## Solution Implemented

### Step-by-Step Fix Process

**1. Update Supervisor Configuration**

Modified `/etc/supervisor/conf.d/calcom-sync-queue.conf`:
```diff
- command=/usr/bin/php /var/www/api-gateway/artisan queue:work database --queue=calcom-sync,default --sleep=3 --tries=3 --max-time=3600
+ command=/usr/bin/php /var/www/api-gateway/artisan queue:work redis --queue=calcom-sync,default --sleep=3 --tries=3 --max-time=3600
```

**2. Reload Supervisor Configuration**

```bash
$ supervisorctl reread
calcom-sync-queue: changed

$ supervisorctl update
calcom-sync-queue: stopped
calcom-sync-queue: updated process group
```

**3. Start Updated Worker**

```bash
$ supervisorctl start calcom-sync-queue:*
# Worker started successfully

$ supervisorctl status calcom-sync-queue:*
calcom-sync-queue:calcom-sync-queue_00   RUNNING   pid 2447888, uptime 0:08:13
```

**4. Stop Stale Database Worker**

An old manually-started database worker was still running:
```bash
$ kill 2444202  # Old database worker (PID 2444202)
```

**5. Verification**

Final verification showed only the correct Redis worker running:
```bash
$ ps aux | grep "queue:work" | grep -v grep
www-data 2447888  ... queue:work redis --queue=calcom-sync,default ...
```

---

## Results

### Configuration Alignment

| Component | Before Fix | After Fix | Status |
|-----------|-----------|-----------|--------|
| `.env` | `redis` | `redis` | ✅ Consistent |
| Supervisor | `database` | `redis` | ✅ Aligned |
| Worker Process | `database` | `redis` | ✅ Correct |
| Redis Service | Running | Running | ✅ Available |

### Benefits

1. **✅ Queue Consistency**: Jobs dispatched to Redis are now processed by Redis worker
2. **✅ Configuration Clarity**: All components now use the same queue driver
3. **✅ Auto-Recovery**: Supervisor ensures worker auto-restarts on failure
4. **✅ Performance**: Redis queues are faster than database queues
5. **✅ Scalability**: Redis supports multiple queue workers more efficiently

### No Data Loss

- ✅ No jobs were pending in either queue during the fix
- ✅ No active job processing was interrupted
- ✅ All future jobs will be correctly processed

---

## Testing & Verification

### Queue Worker Health Check

**Command**:
```bash
supervisorctl status calcom-sync-queue:*
```

**Expected Output**:
```
calcom-sync-queue:calcom-sync-queue_00   RUNNING   pid XXXXX, uptime X:XX:XX
```

### Queue Connection Test

**Command**:
```bash
php artisan queue:work redis --once
```

**Expected**: Worker starts and listens to Redis queue without errors.

### Dispatch Test Job

**Create Test Job**:
```php
// app/Jobs/TestRedisQueue.php
<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class TestRedisQueue implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        Log::info('✅ Redis queue test job executed successfully', [
            'timestamp' => now()->toDateTimeString(),
            'queue' => $this->queue,
        ]);
    }
}
```

**Dispatch Test**:
```bash
php artisan tinker --execute="
\App\Jobs\TestRedisQueue::dispatch();
echo 'Test job dispatched to Redis queue';
"
```

**Verify**:
```bash
# Check worker logs
tail -f /var/log/supervisor/calcom-sync-queue.log

# Check application logs
tail -f storage/logs/laravel.log | grep "Redis queue test"
```

**Expected**: Job is processed within seconds and success message appears in logs.

---

## Monitoring & Maintenance

### Daily Health Checks

**1. Worker Status**:
```bash
supervisorctl status calcom-sync-queue:*
```

Should show `RUNNING` status.

**2. Queue Metrics**:
```bash
# Check pending jobs in Redis
redis-cli LLEN "queues:default"
redis-cli LLEN "queues:calcom-sync"

# Check failed jobs
php artisan queue:failed
```

**3. Worker Logs**:
```bash
tail -100 /var/log/supervisor/calcom-sync-queue.log
```

Look for errors or exceptions.

### Performance Monitoring

**Queue Metrics to Track**:
- Pending jobs count
- Processing time per job
- Failed job rate
- Worker memory usage
- Queue throughput (jobs/minute)

**Recommended Tools**:
- Laravel Horizon (for advanced Redis queue monitoring)
- Prometheus + Grafana (for metrics visualization)
- Sentry (for error tracking)

### Troubleshooting

**Problem**: Worker not processing jobs

**Solution**:
```bash
# Restart worker
supervisorctl restart calcom-sync-queue:*

# Check Redis connection
redis-cli ping

# Check application logs
tail -f storage/logs/laravel.log
```

**Problem**: Jobs failing consistently

**Solution**:
```bash
# Check failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all

# Clear failed jobs after investigation
php artisan queue:flush
```

**Problem**: Worker consuming too much memory

**Solution**:
```ini
# Update supervisor config to restart worker periodically
[program:calcom-sync-queue]
# Add memory limit
command=/usr/bin/php /var/www/api-gateway/artisan queue:work redis --queue=calcom-sync,default --sleep=3 --tries=3 --max-time=3600 --memory=512

# Supervisor will restart worker if it exceeds runtime
```

---

## Rollback Procedure

### If Issues Arise

**1. Revert Supervisor Configuration**

Edit `/etc/supervisor/conf.d/calcom-sync-queue.conf`:
```ini
command=/usr/bin/php /var/www/api-gateway/artisan queue:work database --queue=calcom-sync,default --sleep=3 --tries=3 --max-time=3600
```

**2. Reload Supervisor**
```bash
supervisorctl reread
supervisorctl update
supervisorctl start calcom-sync-queue:*
```

**3. Update Application Configuration**

Edit `.env`:
```env
QUEUE_CONNECTION=database
```

**4. Clear Configuration Cache**
```bash
php artisan config:clear
php artisan cache:clear
```

**5. Verify**
```bash
ps aux | grep "queue:work"
# Should show: queue:work database
```

### Rollback Verification

- ✅ Worker running with database driver
- ✅ Jobs being processed from database queue
- ✅ No errors in logs
- ✅ Application functioning normally

---

## Best Practices

### Queue Configuration

1. **Environment-Driven**: Always use `.env` for queue driver configuration
2. **Supervisor Alignment**: Ensure supervisor configs match application configuration
3. **Single Source of Truth**: `.env` should be the authoritative configuration source
4. **Documentation**: Keep supervisor configs documented and version-controlled

### Worker Management

1. **Graceful Restarts**: Use `supervisorctl restart` instead of `kill`
2. **Memory Limits**: Set `--memory` flag to prevent memory leaks
3. **Max Runtime**: Use `--max-time` to periodically restart workers
4. **Multiple Workers**: Scale horizontally with `numprocs` in supervisor config

### Monitoring

1. **Failed Job Tracking**: Regularly check `queue:failed`
2. **Worker Health**: Monitor supervisor status
3. **Performance Metrics**: Track job processing times
4. **Alerting**: Set up alerts for worker failures

---

## Related Documentation

- [Laravel Queue Documentation](https://laravel.com/docs/11.x/queues)
- [Supervisor Documentation](http://supervisord.org/)
- [Redis Documentation](https://redis.io/documentation)
- [Laravel Horizon](https://laravel.com/docs/11.x/horizon) (recommended for advanced Redis queue management)

---

## Summary

**Problem**: Queue driver mismatch between `.env` (redis) and supervisor config (database)

**Solution**: Updated supervisor configuration to use Redis, reloaded supervisor, stopped stale worker

**Result**:
- ✅ Configuration aligned across all components
- ✅ Redis queue worker running correctly
- ✅ Jobs will be processed from Redis queue
- ✅ Better performance and scalability

**Impact**: LOW (no jobs were pending during fix)

**Risk**: MINIMAL (rollback procedure documented)

**Status**: ✅ COMPLETED AND VERIFIED

---

## Change Log

| Date | Change | Author |
|------|--------|--------|
| 2025-09-30 | Fixed queue driver mismatch: database → redis | Claude (Sprint 3) |
| 2025-09-30 | Updated supervisor config and restarted worker | Claude (Sprint 3) |
| 2025-09-30 | Documented fix and verification procedures | Claude (Sprint 3) |

---

**Document Version**: 1.0
**Last Updated**: 2025-09-30
**Reviewed By**: Sprint 3 Implementation
**Next Review**: After 7 days of production monitoring