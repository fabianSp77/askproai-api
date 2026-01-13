# Root Cause Analysis: Queue/Worker Infrastructure

**Date:** 2025-12-23
**Analyst:** Claude Code (RCA Mode)
**Severity:** Medium (Non-Critical but High-Frequency Errors)
**Status:** ANALYZED - Recommendations Provided

---

## Executive Summary

Comprehensive analysis of the queue/worker infrastructure revealed several issues:
1. **Critical:** Horizon namespace errors occurring 2500+/day from Claude Code statusline script
2. **Medium:** File permission issues with root:root 600 on email templates
3. **Low:** Jobs using SerializesModels with complex models (potential serialization issues)
4. **Good:** Queue configuration is correct and workers are healthy

---

## 1. Horizon Namespace Errors

### Root Cause Identified

The "There are no commands defined in the horizon namespace" errors are caused by:

**Source:** `/root/.claude/statusline-command.sh`

```bash
# This line runs every ~4 seconds when Claude Code refreshes its statusline
if php artisan horizon:status 2>/dev/null | grep -q "running"; then
    HORIZON_STATUS="▶"
fi
```

**Evidence:**
- Process tree shows: `bash /root/.claude/statusline-command.sh -> php artisan horizon:status`
- Error frequency: ~2 errors per 4 seconds = 43,200 errors/day
- Telescope entries: `"occurrences": 2543` (and counting)

### Why This Happens

1. Laravel Horizon is NOT installed (not in composer.json)
2. The statusline script assumes Horizon is available
3. Each failed `horizon:status` call:
   - Throws `NamespaceNotFoundException`
   - Gets logged by Telescope
   - Pollutes the database with exception entries

### Is Horizon Installed?

**NO.** Verified in `composer.json`:
- `laravel/horizon` is NOT listed in `require` or `require-dev`
- Only Laravel Telescope (`laravel/telescope`) is installed

### Other Horizon References (Non-Active)

| Location | Status |
|----------|--------|
| `/var/www/api-gateway/tests/rollback-flags.sh:24` | Has `2>/dev/null \|\| true` - safe |
| `/var/www/api-gateway/deploy/go-live.sh:22` | Already commented out |
| Documentation files | Mentions only - not executed |
| Scripts using `grep -v "horizon"` | Filter horizon errors - safe |

---

## 2. Queue Configuration Analysis

### .env Configuration (Correct)

```env
QUEUE_CONNECTION=redis
CACHE_STORE=redis
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

### config/queue.php (Correct)

```php
'default' => env('QUEUE_CONNECTION', 'database'),
// Correctly falls back to 'database' if env not set

'redis' => [
    'driver' => 'redis',
    'connection' => env('REDIS_QUEUE_CONNECTION', 'default'),
    'queue' => env('REDIS_QUEUE', 'default'),
    'retry_after' => 90,
    'block_for' => null,
    'after_commit' => false,
],
```

### Supervisor Configuration (Healthy)

**Active Workers:**
```
calcom-sync-queue:calcom-sync-queue_00   RUNNING   pid 2218663
laravel-worker:laravel-worker_00         RUNNING   pid 2250483
laravel-worker:laravel-worker_01         RUNNING   pid 2250484
scheduler                                RUNNING   pid 2218664
```

**Queue Configuration:**
- `calcom-sync-queue`: Processes `calcom-sync,default` queues
- `laravel-worker`: Processes `cache,default` queues (2 workers)
- No Horizon references in supervisor configs

### Redis Status

```
Redis Queue Length: 0 (healthy - jobs being processed)
Redis: PONG (connected)
```

---

## 3. Jobs with SerializesModels - Serialization Risk Analysis

### High-Risk Jobs (Serializing Complex Models)

| Job | Trait | Model | Risk |
|-----|-------|-------|------|
| `ProcessCallRecordingJob` | SerializesModels | `ServiceCase` | **HIGH** - Full model serialized |
| `RefreshCallDataJob` | SerializesModels | `Call` | **MEDIUM** - Full model serialized |
| `SyncAppointmentToCalcomJob` | SerializesModels | Appointment via constructor | **MEDIUM** |

### Safe Pattern (Already Implemented)

`DeliverCaseOutputJob.php` uses the recommended pattern:
```php
/**
 * NOTE: Intentionally does NOT use SerializesModels trait to avoid
 * closure serialization issues with ServiceCase model relationships.
 * Instead, we serialize only the case_id and load fresh in handle().
 */
class DeliverCaseOutputJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;
    // NO SerializesModels!

    public function __construct(public int $caseId) {}

    public function handle(OutputHandlerFactory $factory): void
    {
        $this->case = ServiceCase::find($this->caseId);
        // ...
    }
}
```

### EnrichServiceCaseJob (Good Pattern)

Uses SerializesModels but only stores IDs:
```php
public function __construct(int $callId, string $retellCallId)
{
    $this->callId = $callId;
    $this->retellCallId = $retellCallId;
}
```

---

## 4. File Permission Issues

### Files with root:root 600 Permissions

These files are owned by root with permissions 600 (owner read/write only), which can cause issues for www-data:

| File | Impact |
|------|--------|
| `/var/www/api-gateway/app/Services/ServiceGateway/Traits/TemplateRendererTrait.php` | **HIGH** - PHP class may fail to load |
| `/var/www/api-gateway/resources/views/emails/service-cases/backup-notification.blade.php` | **HIGH** - Email rendering will fail |
| `/var/www/api-gateway/resources/views/emails/service-cases/it-support-notification.blade.php` | **HIGH** - Email rendering will fail |
| `/var/www/api-gateway/resources/views/emails/service-cases/notification-html.blade.php` | **HIGH** - Email rendering will fail |
| `/var/www/api-gateway/resources/views/emails/service-cases/notification-html-v2.blade.php` | **HIGH** - Email rendering will fail |
| `/var/www/api-gateway/resources/views/emails/service-cases/notification-text.blade.php` | **HIGH** - Email rendering will fail |
| `/var/www/api-gateway/resources/views/emails/service-cases/visionary-backup-html.blade.php` | **HIGH** - Email rendering will fail |
| `/var/www/api-gateway/resources/views/components/customer-portal/error-message.blade.php` | **MEDIUM** - Component may fail |

### Expected Permissions

Files should be:
- Owner: `www-data`
- Group: `www-data`
- Permissions: `644` (readable by all, writable by owner)

---

## 5. Recommendations

### Immediate Actions (Priority 1)

#### Fix Horizon Statusline Error

Update `/root/.claude/statusline-command.sh`:

```bash
#!/bin/bash
# Claude Code Status Line for AskProAI
# Shows real-time system status in Claude Code

cd /var/www/api-gateway 2>/dev/null
GIT_BRANCH=$(git branch --show-current 2>/dev/null || echo "main")

# Check Queue Worker status (Horizon is not installed)
QUEUE_STATUS="⏸"
if supervisorctl status laravel-worker:laravel-worker_00 2>/dev/null | grep -q RUNNING; then
    QUEUE_STATUS="▶"
fi

# Check Redis
REDIS_STATUS="✗"
if redis-cli ping 2>/dev/null | grep -q PONG; then
    REDIS_STATUS="✓"
fi

# Get error count from today's log (excluding horizon errors)
ERROR_COUNT=0
if [ -f "storage/logs/laravel.log" ]; then
    ERROR_COUNT=$(grep "$(date +%Y-%m-%d)" storage/logs/laravel.log 2>/dev/null | grep "ERROR" | grep -v "horizon" | wc -l || echo "0")
fi

# Memory usage
MEM_PERCENT=$(free | grep Mem | awk '{if($2>0) printf("%.0f", $3/$2 * 100.0); else print "0"}')

echo "🚀 AskProAI | 🌿 $GIT_BRANCH | Queue: $QUEUE_STATUS | Redis: $REDIS_STATUS | Errors: $ERROR_COUNT | Mem: ${MEM_PERCENT}%"
```

#### Fix File Permissions

```bash
# Fix email templates
sudo chown www-data:www-data /var/www/api-gateway/resources/views/emails/service-cases/*.blade.php
sudo chmod 644 /var/www/api-gateway/resources/views/emails/service-cases/*.blade.php

# Fix customer portal component
sudo chown www-data:www-data /var/www/api-gateway/resources/views/components/customer-portal/*.blade.php
sudo chmod 644 /var/www/api-gateway/resources/views/components/customer-portal/*.blade.php

# Fix ServiceGateway Trait
sudo chown www-data:www-data /var/www/api-gateway/app/Services/ServiceGateway/Traits/*.php
sudo chmod 644 /var/www/api-gateway/app/Services/ServiceGateway/Traits/*.php
```

### Medium-Term Actions (Priority 2)

#### Refactor High-Risk Jobs

Refactor `ProcessCallRecordingJob` to not serialize the full model:

```php
// Current (risky):
public function __construct(ServiceCase $case)
{
    $this->case = $case;
}

// Recommended:
public function __construct(int $caseId)
{
    $this->caseId = $caseId;
}

public function handle(AudioStorageService $audioService): void
{
    $this->case = ServiceCase::find($this->caseId);
    // ...
}
```

### Long-Term Actions (Priority 3)

#### Consider Installing Horizon (Optional)

If queue monitoring dashboard is desired:
```bash
composer require laravel/horizon
php artisan horizon:install
php artisan migrate
```

Benefits:
- Queue monitoring dashboard at `/horizon`
- Failed job retry UI
- Queue metrics and statistics
- Job tagging (already implemented in jobs)

However, current supervisor-based setup is working fine for production.

---

## 6. Summary of Issues Found

| Issue | Severity | Status | Impact |
|-------|----------|--------|--------|
| Horizon statusline errors | Medium | ROOT CAUSE FOUND | 43,200 errors/day, log pollution |
| File permissions (root:600) | High | IDENTIFIED | Email rendering failures possible |
| ProcessCallRecordingJob serialization | Low | DOCUMENTED | Potential queue issues |
| Queue configuration | N/A | HEALTHY | Working correctly |
| Supervisor workers | N/A | HEALTHY | All running |
| Redis connection | N/A | HEALTHY | Connected |

---

## 7. Files Referenced

### Configuration Files
- `/var/www/api-gateway/.env` - Environment configuration
- `/var/www/api-gateway/config/queue.php` - Queue configuration
- `/var/www/api-gateway/composer.json` - Dependencies (Horizon NOT installed)
- `/etc/supervisor/conf.d/*.conf` - Worker configuration

### Scripts Checked
- `/root/.claude/statusline-command.sh` - **ROOT CAUSE** of Horizon errors
- `/var/www/api-gateway/tests/rollback-flags.sh` - Has horizon reference (safe)
- `/var/www/api-gateway/deploy/go-live.sh` - Horizon removed
- `/var/www/api-gateway/scripts/monitor-production.sh` - Filters horizon errors

### Jobs Analyzed
- `/var/www/api-gateway/app/Jobs/ServiceGateway/ProcessCallRecordingJob.php`
- `/var/www/api-gateway/app/Jobs/ServiceGateway/EnrichServiceCaseJob.php`
- `/var/www/api-gateway/app/Jobs/ServiceGateway/DeliverCaseOutputJob.php`
- `/var/www/api-gateway/app/Jobs/RefreshCallDataJob.php`
- `/var/www/api-gateway/app/Jobs/SyncAppointmentToCalcomJob.php`

---

**End of Report**

*Generated: 2025-12-23 by Claude Code RCA Mode*
