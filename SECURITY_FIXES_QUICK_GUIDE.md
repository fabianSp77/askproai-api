# 🚨 Cal.com Security Fixes - Quick Implementation Guide

**Priority:** CRITICAL
**Deadline:** 24-48 hours
**Risk if Not Fixed:** Service degradation, tenant data exposure, GDPR violations

---

## 🎯 Critical Fix #1: Job Parameter Validation (VULN-002)

**Risk Score:** 9.5/10 - CRITICAL
**File:** `/var/www/api-gateway/app/Jobs/ClearAvailabilityCacheJob.php`

### Current Vulnerability
```php
// ❌ VULNERABLE: No validation in constructor
public function __construct(
    int $eventTypeId,
    Carbon $appointmentStart,
    Carbon $appointmentEnd,
    ?int $teamId = null,
    ?int $companyId = null,
    ?int $branchId = null,
    ?string $source = null,
    ?int $appointmentId = null
) {
    $this->eventTypeId = $eventTypeId;  // Accepts ANY value!
    $this->appointmentStart = $appointmentStart;
    $this->appointmentEnd = $appointmentEnd;
    // ... rest of code
}
```

**Attack:** An attacker could dispatch a job with `eventTypeId: -1` or `companyId: 999999` to clear cache for wrong companies.

### Fix Implementation

```php
<?php

namespace App\Jobs;

use App\Services\CalcomService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ClearAvailabilityCacheJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;
    public int $backoff = 5;

    public int $eventTypeId;
    public Carbon $appointmentStart;
    public Carbon $appointmentEnd;
    public ?int $teamId;
    public ?int $companyId;
    public ?int $branchId;
    public ?string $source;
    public ?int $appointmentId;

    public function __construct(
        int $eventTypeId,
        Carbon $appointmentStart,
        Carbon $appointmentEnd,
        ?int $teamId = null,
        ?int $companyId = null,
        ?int $branchId = null,
        ?string $source = null,
        ?int $appointmentId = null
    ) {
        // ✅ SECURITY FIX: Validate all parameters before queue serialization
        $validated = validator([
            'eventTypeId' => $eventTypeId,
            'appointmentStart' => $appointmentStart->toIso8601String(),
            'appointmentEnd' => $appointmentEnd->toIso8601String(),
            'teamId' => $teamId,
            'companyId' => $companyId,
            'branchId' => $branchId,
            'source' => $source,
            'appointmentId' => $appointmentId,
        ], [
            // Required fields
            'eventTypeId' => 'required|integer|min:1|exists:services,calcom_event_type_id',
            'appointmentStart' => 'required|date|after_or_equal:today|before:+1 year',
            'appointmentEnd' => 'required|date|after:appointmentStart|before:+1 year',

            // Optional fields
            'teamId' => 'nullable|integer|min:1',
            'companyId' => 'nullable|integer|min:1|exists:companies,id',
            'branchId' => 'nullable|integer|min:1|exists:branches,id',
            'source' => 'nullable|string|max:100',
            'appointmentId' => 'nullable|integer|min:1|exists:appointments,id',
        ], [
            'eventTypeId.exists' => 'Security: Event type does not exist in system',
            'companyId.exists' => 'Security: Company ID does not exist',
            'branchId.exists' => 'Security: Branch ID does not exist',
            'appointmentId.exists' => 'Security: Appointment ID does not exist',
        ])->validate();

        // ✅ CRITICAL: Verify tenant ownership (prevent cross-tenant attacks)
        if ($companyId && $eventTypeId) {
            $service = \App\Models\Service::where('calcom_event_type_id', $eventTypeId)
                ->where('company_id', $companyId)
                ->first();

            if (!$service) {
                Log::error('[Security] Job tenant validation failed during dispatch', [
                    'event_type_id' => $eventTypeId,
                    'company_id' => $companyId
                ]);

                throw new \InvalidArgumentException(
                    "Security violation: Event type {$eventTypeId} does not belong to company {$companyId}"
                );
            }
        }

        // Store validated parameters
        $this->eventTypeId = $eventTypeId;
        $this->appointmentStart = $appointmentStart;
        $this->appointmentEnd = $appointmentEnd;
        $this->teamId = $teamId;
        $this->companyId = $companyId;
        $this->branchId = $branchId;
        $this->source = $source;
        $this->appointmentId = $appointmentId;

        $this->onQueue('cache');
    }

    public function handle(): void
    {
        $startTime = microtime(true);

        try {
            // ✅ SECURITY FIX: Re-verify tenant ownership during execution
            // This protects against queue tampering between dispatch and execution
            if ($this->companyId && $this->eventTypeId) {
                $service = \App\Models\Service::where('calcom_event_type_id', $this->eventTypeId)
                    ->where('company_id', $this->companyId)
                    ->first();

                if (!$service) {
                    Log::error('[Security] Job tenant validation failed during execution', [
                        'event_type_id' => $this->eventTypeId,
                        'company_id' => $this->companyId,
                        'job_id' => $this->job->getJobId()
                    ]);

                    // Mark job as permanently failed (no retry)
                    $this->fail(new \Exception('Tenant isolation violation during job execution'));
                    return;
                }
            }

            Log::info('🔄 ASYNC: Starting cache clearing job', [
                'job_id' => $this->job->getJobId(),
                'event_type_id' => $this->eventTypeId,
                'appointment_id' => $this->appointmentId,
                'source' => $this->source,
                'attempt' => $this->attempts()
            ]);

            // Call the smart cache invalidation method
            $calcomService = app(CalcomService::class);
            $clearedKeys = $calcomService->smartClearAvailabilityCache(
                eventTypeId: $this->eventTypeId,
                appointmentStart: $this->appointmentStart,
                appointmentEnd: $this->appointmentEnd,
                teamId: $this->teamId,
                companyId: $this->companyId,
                branchId: $this->branchId
            );

            $duration = round((microtime(true) - $startTime) * 1000, 2);

            Log::info('✅ ASYNC: Cache clearing job completed', [
                'job_id' => $this->job->getJobId(),
                'event_type_id' => $this->eventTypeId,
                'appointment_id' => $this->appointmentId,
                'keys_cleared' => $clearedKeys,
                'duration_ms' => $duration,
                'optimization' => 'Phase 3 - Async cache clearing'
            ]);

        } catch (\Exception $e) {
            $duration = round((microtime(true) - $startTime) * 1000, 2);

            Log::error('❌ ASYNC: Cache clearing job failed', [
                'job_id' => $this->job?->getJobId(),
                'event_type_id' => $this->eventTypeId,
                'appointment_id' => $this->appointmentId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'duration_ms' => $duration,
                'attempt' => $this->attempts(),
                'max_tries' => $this->tries
            ]);

            // Report to error tracking system
            report($e);

            // Re-throw to trigger retry mechanism
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('🚨 ASYNC: Cache clearing job failed permanently', [
            'job_id' => $this->job?->getJobId(),
            'event_type_id' => $this->eventTypeId,
            'appointment_id' => $this->appointmentId,
            'source' => $this->source,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
            'impact' => 'Cache may be stale - manual clearing may be needed'
        ]);

        if (config('logging.channels.cache_alerts')) {
            Log::channel('cache_alerts')->critical('Async cache clearing failed permanently', [
                'job' => 'ClearAvailabilityCacheJob',
                'event_type_id' => $this->eventTypeId,
                'appointment_id' => $this->appointmentId,
                'exception' => $exception->getMessage()
            ]);
        }
    }

    public function backoff(): int
    {
        return $this->backoff * (2 ** ($this->attempts() - 1));
    }

    public function tags(): array
    {
        return [
            'cache',
            'availability',
            "event_type:{$this->eventTypeId}",
            $this->appointmentId ? "appointment:{$this->appointmentId}" : null,
            $this->source ? "source:{$this->source}" : null,
        ];
    }
}
```

### Testing the Fix

```bash
# Test 1: Valid parameters (should succeed)
php artisan tinker
>>> \App\Jobs\ClearAvailabilityCacheJob::dispatch(
...     eventTypeId: 1234567,  // Valid event type ID from services table
...     appointmentStart: now(),
...     appointmentEnd: now()->addMinutes(30),
...     teamId: 34209,
...     companyId: 1,
...     branchId: 1
... );
>>> # Should dispatch successfully

# Test 2: Invalid event type (should throw exception)
>>> \App\Jobs\ClearAvailabilityCacheJob::dispatch(
...     eventTypeId: 999999,  // Non-existent
...     appointmentStart: now(),
...     appointmentEnd: now()->addMinutes(30)
... );
>>> # Should throw validation exception

# Test 3: Tenant mismatch (should throw exception)
>>> \App\Jobs\ClearAvailabilityCacheJob::dispatch(
...     eventTypeId: 1234567,  // Belongs to company 1
...     appointmentStart: now(),
...     appointmentEnd: now()->addMinutes(30),
...     companyId: 999  // Different company
... );
>>> # Should throw tenant violation exception
```

---

## 🎯 Critical Fix #2: Rate Limiter Race Condition (VULN-004)

**Risk Score:** 7.5/10 - HIGH
**File:** `/var/www/api-gateway/app/Services/CalcomApiRateLimiter.php`

### Current Vulnerability
```php
// ❌ VULNERABLE: Non-atomic check and increment
public function canMakeRequest(): bool {
    $count = Cache::get($key . ':' . $minute, 0);
    if ($count >= self::MAX_REQUESTS_PER_MINUTE) {
        return false;
    }
    return true;  // Race window here!
}

public function incrementRequestCount(): void {
    Cache::increment($key);  // Incremented separately!
}
```

### Fix Implementation

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class CalcomApiRateLimiter
{
    private const MAX_REQUESTS_PER_MINUTE = 120;
    private const CACHE_KEY = 'calcom_api_rate_limit';
    private const LOCK_KEY = 'calcom_api_rate_limit_lock';

    /**
     * ✅ SECURITY FIX: Atomic check-and-increment using Lua script
     * Prevents race condition at minute boundaries
     */
    public function canMakeRequestAndIncrement(): bool
    {
        $now = now();
        $minute = $now->format('Y-m-d-H-i');
        $key = config('cache.prefix') . self::CACHE_KEY . ':' . $minute;

        // Lua script ensures atomicity (no race condition)
        $luaScript = <<<'LUA'
local key = KEYS[1]
local limit = tonumber(ARGV[1])
local ttl = tonumber(ARGV[2])

-- Increment counter
local current = redis.call('INCR', key)

-- Set TTL on first increment
if current == 1 then
    redis.call('EXPIRE', key, ttl)
end

-- Check if over limit
if current > limit then
    -- Decrement back (undo)
    redis.call('DECR', key)
    return 0  -- Rate limit exceeded
end

return 1  -- Request allowed
LUA;

        try {
            $result = Redis::eval($luaScript, 1, $key, self::MAX_REQUESTS_PER_MINUTE, 120);

            if ($result === 0) {
                Log::channel('calcom')->warning('[Cal.com] Rate limit enforced (atomic)', [
                    'minute' => $minute,
                    'limit' => self::MAX_REQUESTS_PER_MINUTE
                ]);
                return false;
            }

            // Log every 10th request for monitoring
            if (((int) $result) % 10 === 0) {
                Log::channel('calcom')->debug('[Cal.com] API requests this minute', [
                    'count' => $result,
                    'minute' => $minute,
                    'limit' => self::MAX_REQUESTS_PER_MINUTE
                ]);
            }

            return true;

        } catch (\Exception $e) {
            // If Redis fails, fail open (allow request) but log error
            Log::error('[Cal.com] Rate limiter Redis error (failing open)', [
                'error' => $e->getMessage()
            ]);
            return true;
        }
    }

    /**
     * @deprecated Use canMakeRequestAndIncrement() instead
     */
    public function canMakeRequest(): bool
    {
        Log::warning('[Deprecated] canMakeRequest() called - use canMakeRequestAndIncrement()');

        $now = now();
        $minute = $now->format('Y-m-d-H-i');
        $key = self::CACHE_KEY . ':' . $minute;

        $count = Cache::get($key, 0);

        if ($count >= self::MAX_REQUESTS_PER_MINUTE) {
            Log::channel('calcom')->warning('[Cal.com] Rate limit reached', [
                'count' => $count,
                'minute' => $minute,
                'limit' => self::MAX_REQUESTS_PER_MINUTE
            ]);
            return false;
        }

        return true;
    }

    /**
     * @deprecated Increment is now handled by canMakeRequestAndIncrement()
     */
    public function incrementRequestCount(): void
    {
        Log::warning('[Deprecated] incrementRequestCount() called - use canMakeRequestAndIncrement()');

        $now = now();
        $minute = $now->format('Y-m-d-H-i');
        $key = self::CACHE_KEY . ':' . $minute;

        Cache::increment($key);
        \Illuminate\Support\Facades\Redis::expire(config('cache.prefix') . $key, 120);

        $count = Cache::get($key);

        if ($count % 10 === 0) {
            Log::channel('calcom')->debug('[Cal.com] API requests this minute', [
                'count' => $count,
                'minute' => $minute,
                'limit' => self::MAX_REQUESTS_PER_MINUTE
            ]);
        }
    }

    /**
     * Wait until we can make a request
     */
    public function waitForAvailability(): void
    {
        $attempts = 0;
        while (!$this->canMakeRequestAndIncrement() && $attempts < 60) {
            sleep(1);
            $attempts++;
        }

        if ($attempts >= 60) {
            Log::warning('[Cal.com] Rate limiter wait timeout after 60 seconds');
        }
    }

    /**
     * Get remaining requests for current minute
     */
    public function getRemainingRequests(): int
    {
        $now = now();
        $minute = $now->format('Y-m-d-H-i');
        $key = self::CACHE_KEY . ':' . $minute;

        $count = Cache::get($key, 0);

        return max(0, self::MAX_REQUESTS_PER_MINUTE - $count);
    }

    /**
     * Reset rate limit (for testing)
     */
    public function reset(): void
    {
        $now = now();
        $minute = $now->format('Y-m-d-H-i');
        $key = self::CACHE_KEY . ':' . $minute;

        Cache::forget($key);
    }
}
```

### Update CalcomService to Use New Method

```php
// File: app/Services/CalcomService.php

// OLD CODE (lines 232-235):
if (!$this->rateLimiter->canMakeRequest()) {
    Log::warning('Cal.com rate limit reached, waiting for availability');
    $this->rateLimiter->waitForAvailability();
}

// NEW CODE:
// ✅ SECURITY FIX: Use atomic check-and-increment
if (!$this->rateLimiter->canMakeRequestAndIncrement()) {
    Log::warning('Cal.com rate limit reached, waiting for availability');
    // Wait and retry (already incremented atomically on next call)
    $this->rateLimiter->waitForAvailability();
    // No need for separate increment - it's already handled!
}

// Remove old increment calls (lines 249, 434):
// $this->rateLimiter->incrementRequestCount();  // ← DELETE THIS LINE
```

---

## 🎯 Critical Fix #3: Log Sanitization (VULN-005)

**Risk Score:** 7.0/10 - HIGH (GDPR violation)
**File:** `/var/www/api-gateway/app/Services/CalcomService.php`

### Changes Required

```php
// Add import at top of file
use App\Helpers\LogSanitizer;

// Line 229: BEFORE
Log::channel('calcom')->debug('[Cal.com V2] Sende createBooking Payload:', $payload);

// Line 229: AFTER
Log::channel('calcom')->debug('[Cal.com V2] Sende createBooking Payload:',
    LogSanitizer::sanitize($payload)
);

// Line 251-254: BEFORE
Log::channel('calcom')->debug('[Cal.com V2] Booking Response:', [
    'status' => $resp->status(),
    'body'   => $resp->json() ?? $resp->body(),
]);

// Line 251-254: AFTER
Log::channel('calcom')->debug('[Cal.com V2] Booking Response:', [
    'status' => $resp->status(),
    'body'   => LogSanitizer::sanitize($resp->json() ?? $resp->body()),
]);

// Apply same pattern to lines: 479, 575, 972, 984, 1019, 1032, 1052
```

### Production Config Update

```php
// File: config/logging.php

'calcom' => [
    'driver' => 'daily',
    'path' => storage_path('logs/calcom.log'),
    'level' => env('LOG_LEVEL', 'debug'),  // ← CHANGE THIS
    'days' => 14,
],

// Change to:
'calcom' => [
    'driver' => 'daily',
    'path' => storage_path('logs/calcom.log'),
    'level' => env('APP_ENV') === 'production' ? 'info' : 'debug',  // ✅ FIXED
    'days' => 14,
],
```

---

## 🧪 Testing Checklist

### Test 1: Job Parameter Validation
```bash
# Should SUCCEED
php artisan tinker
>>> \App\Jobs\ClearAvailabilityCacheJob::dispatch(
...     eventTypeId: 1234567,  # Valid from services table
...     appointmentStart: now(),
...     appointmentEnd: now()->addMinutes(30),
...     teamId: 34209,
...     companyId: 1
... );

# Should FAIL with validation error
>>> \App\Jobs\ClearAvailabilityCacheJob::dispatch(
...     eventTypeId: -1,
...     appointmentStart: now(),
...     appointmentEnd: now()->addMinutes(30)
... );

# Should FAIL with tenant violation
>>> \App\Jobs\ClearAvailabilityCacheJob::dispatch(
...     eventTypeId: 1234567,  # Belongs to company 1
...     appointmentStart: now(),
...     appointmentEnd: now()->addMinutes(30),
...     companyId: 999  # Wrong company
... );
```

### Test 2: Rate Limiter Race Condition
```bash
# Stress test with concurrent requests
php artisan tinker
>>> $rateLimiter = new \App\Services\CalcomApiRateLimiter();
>>> for ($i = 0; $i < 150; $i++) {
...     $rateLimiter->canMakeRequestAndIncrement();
... }
>>> echo $rateLimiter->getRemainingRequests();  # Should be 0 (not negative!)
```

### Test 3: Log Sanitization
```bash
# Check logs for PII
tail -f storage/logs/calcom.log | grep -E 'email|phone|name'
# Should show redacted values like [REDACTED EMAIL] instead of real emails
```

---

## 📊 Deployment Steps

### 1. Pre-Deployment
```bash
# Backup database
php artisan backup:run

# Run tests
php artisan test --filter=Calcom

# Check queue health
php artisan queue:monitor cache,default --max=1000
```

### 2. Deploy Fixes
```bash
# Pull changes
git pull origin main

# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan view:clear

# Run migrations (if any)
php artisan migrate

# Restart queue workers
php artisan queue:restart
```

### 3. Post-Deployment Verification
```bash
# Check logs for errors
tail -f storage/logs/laravel.log

# Monitor queue jobs
php artisan queue:monitor cache --max=100

# Test webhook endpoint
curl -X POST https://your-domain.com/api/calcom/webhook \
  -H "X-Cal-Signature-256: test" \
  -H "Content-Type: application/json" \
  -d '{"triggerEvent":"PING"}'
```

---

## 🚨 Rollback Plan

If issues occur after deployment:

```bash
# 1. Revert code changes
git revert HEAD
git push origin main

# 2. Redeploy previous version
./deploy.sh

# 3. Clear failed jobs
php artisan queue:flush

# 4. Monitor for recovery
tail -f storage/logs/laravel.log
```

---

## 📞 Support

**Security Issues:** security@askpro.ai
**Deployment Support:** devops@askpro.ai
**On-Call:** See confluence/on-call-schedule

---

**Last Updated:** 2025-11-11
**Version:** 1.0
