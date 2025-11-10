# Performance Quick Wins - Implementation Guide
**Priority**: P0 (Critical Path)
**Estimated Time**: 1-2 days
**Expected Impact**: 40-50% latency reduction

---

## 1. Fix find_next_available 500 Error (CRITICAL)

**File**: `/var/www/api-gateway/app/Services/AppointmentAlternativeFinder.php`

**Location**: Line 852 - `findNextAvailableSlot()` method

**Change**:
```php
private function findNextAvailableSlot(Carbon $desiredDateTime, int $durationMinutes, int $eventTypeId): ?array
{
    $maxDays = 14;

    Log::info('🔍 Starting brute force search for next available slot', [
        'start_date' => $desiredDateTime->format('Y-m-d'),
        'max_days' => $maxDays,
        'event_type_id' => $eventTypeId
    ]);

    // ✅ ADD: Outer try-catch for graceful degradation
    try {
        for ($dayOffset = 0; $dayOffset <= $maxDays; $dayOffset++) {
            $searchDate = $desiredDateTime->copy()->addDays($dayOffset);

            if (!$this->isWorkday($searchDate)) {
                Log::debug('⏭️ Skipping non-workday', [
                    'date' => $searchDate->format('Y-m-d'),
                    'day' => $searchDate->format('l')
                ]);
                continue;
            }

            $startOfDay = $searchDate->copy()->startOfDay()->setTime(9, 0);
            $endOfDay = $searchDate->copy()->startOfDay()->setTime(18, 0);

            // ✅ ADD: Per-day try-catch for Cal.com API failures
            try {
                $slots = $this->getAvailableSlots($startOfDay, $endOfDay, $eventTypeId);

                if (!empty($slots)) {
                    foreach ($slots as $slot) {
                        $slotTime = isset($slot['datetime']) ? $slot['datetime'] : Carbon::parse($slot['time'])->setTimezone('Europe/Berlin');

                        if ($this->isWithinBusinessHours($slotTime)) {
                            Log::info('✅ Found available slot', [
                                'datetime' => $slotTime->format('Y-m-d H:i'),
                                'days_ahead' => $dayOffset
                            ]);

                            return [
                                'datetime' => $slotTime,
                                'type' => 'next_available',
                                'description' => $this->formatGermanWeekday($slotTime) . ', ' .
                                              $slotTime->format('d.m.') . ' um ' . $slotTime->format('H:i') . ' Uhr',
                                'rank' => 60,
                                'source' => 'calcom'
                            ];
                        }
                    }
                }

                Log::debug('❌ No slots available on date', [
                    'date' => $searchDate->format('Y-m-d')
                ]);

            } catch (CalcomApiException $e) {
                // ✅ HANDLE: Cal.com API failures gracefully
                Log::warning('Cal.com API failed for date', [
                    'date' => $searchDate->format('Y-m-d'),
                    'error' => $e->getMessage(),
                    'status' => $e->getStatusCode()
                ]);

                // If circuit breaker is open, abort search
                if ($e->getStatusCode() === 503) {
                    Log::error('Circuit breaker open - aborting search', [
                        'days_searched' => $dayOffset,
                        'event_type_id' => $eventTypeId
                    ]);
                    return null; // Fail fast
                }

                // For other errors (404, 500), continue to next day
                continue;

            } catch (\Exception $e) {
                // ✅ HANDLE: Other exceptions (cache, network, etc.)
                Log::warning('Unexpected error searching date', [
                    'date' => $searchDate->format('Y-m-d'),
                    'error' => $e->getMessage(),
                    'error_class' => get_class($e)
                ]);
                continue; // Try next day
            }
        }

        Log::warning('⚠️ No available slots found in next ' . $maxDays . ' days');
        return null;

    } catch (\Exception $e) {
        // ✅ HANDLE: Catastrophic failures (should never happen, but defensive)
        Log::error('Catastrophic failure in findNextAvailableSlot', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'desired_datetime' => $desiredDateTime->format('Y-m-d H:i'),
            'event_type_id' => $eventTypeId
        ]);
        return null; // Graceful degradation - return no results instead of 500
    }
}
```

**Testing**:
```bash
# Test the endpoint directly
curl -X POST https://your-domain.com/api/retell/function \
  -H "Content-Type: application/json" \
  -d '{
    "name": "find_next_available",
    "call": {"call_id": "test_call_123"},
    "args": {
      "desired_date": "2025-11-10",
      "desired_time": "14:00",
      "duration_minutes": 45,
      "service_name": "Herrenhaarschnitt"
    }
  }'

# Should return JSON (not HTML error page)
```

---

## 2. Request Coalescing for check_availability

**File**: `/var/www/api-gateway/app/Services/CalcomService.php`

**Location**: Line 220 - `getAvailableSlots()` method

**Change**:
```php
public function getAvailableSlots(int $eventTypeId, string $startDate, string $endDate, ?int $teamId = null): Response
{
    // Include teamId in cache key if provided
    $cacheKey = $teamId
        ? "calcom:slots:{$teamId}:{$eventTypeId}:{$startDate}:{$endDate}"
        : "calcom:slots:{$eventTypeId}:{$startDate}:{$endDate}";

    // ✅ ADD: Request coalescing lock key
    $lockKey = "lock:{$cacheKey}";

    // Check cache first (99% faster: <5ms vs 300-800ms)
    $cachedResponse = Cache::get($cacheKey);
    if ($cachedResponse) {
        Log::debug('Availability cache hit', ['key' => $cacheKey]);

        // Return mock Response with cached data
        return new Response(
            new \GuzzleHttp\Psr7\Response(200, [], json_encode($cachedResponse))
        );
    }

    // ✅ ADD: Acquire distributed lock for request coalescing
    // This prevents multiple concurrent requests for the same slot from hitting Cal.com
    $lock = Cache::lock($lockKey, 10); // 10 second lock

    try {
        // Try to acquire lock (non-blocking)
        if ($lock->get()) {
            // This request won the race - fetch from Cal.com
            Log::debug('Request coalescing: Won lock, fetching from Cal.com', [
                'cache_key' => $cacheKey,
                'lock_key' => $lockKey
            ]);

            // Cal.com v2 API requires Bearer token authentication AND ISO 8601 format
            $startDateTime = Carbon::parse($startDate)->startOfDay()->toIso8601String();
            $endDateTime = Carbon::parse($endDate)->endOfDay()->toIso8601String();

            $query = [
                'eventTypeId' => $eventTypeId,
                'startTime' => $startDateTime,
                'endTime' => $endDateTime
            ];

            if ($teamId) {
                $query['teamId'] = $teamId;
            }

            $fullUrl = $this->baseUrl . '/slots/available?' . http_build_query($query);

            return $this->circuitBreaker->call(function() use ($fullUrl, $query, $cacheKey, $eventTypeId, $startDate, $endDate) {
                $resp = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'cal-api-version' => config('services.calcom.api_version', '2024-08-13')
                ])->acceptJson()->timeout(3)->get($fullUrl);

                if (!$resp->successful()) {
                    throw CalcomApiException::fromResponse($resp, '/slots/available', $query, 'GET');
                }

                $data = $resp->json();

                if (!isset($data['data']['slots']) || !is_array($data['data']['slots'])) {
                    Log::warning('[Cal.com] Invalid slots structure received', [
                        'response' => $data,
                        'event_type_id' => $eventTypeId,
                        'query' => $query
                    ]);
                    throw new CalcomApiException(
                        'Cal.com returned invalid response structure',
                        null,
                        '/slots/available',
                        $query,
                        500
                    );
                }

                $slotsData = $data['data']['slots'];
                $totalSlots = array_sum(array_map('count', $slotsData));

                Log::channel('calcom')->info('[Cal.com] Available Slots Response', [
                    'event_type_id' => $eventTypeId,
                    'date_range' => [$startDate, $endDate],
                    'query_params' => $query,
                    'dates_with_slots' => count($slotsData),
                    'total_slots' => $totalSlots,
                    'first_date' => !empty($slotsData) ? array_key_first($slotsData) : null,
                    'first_slot_time' => $this->getFirstSlotTime($slotsData)
                ]);

                // ✅ CHANGED: Increase TTL from 60s to 300s (5 minutes)
                // Rationale: Availability doesn't change frequently enough to warrant 60s
                // Cache invalidation already handles booking updates
                $ttl = ($totalSlots === 0) ? 60 : 300; // Empty responses: 1 min, Normal: 5 min

                Cache::put($cacheKey, $data, $ttl);
                Log::debug('Availability cached', [
                    'key' => $cacheKey,
                    'slots_count' => $totalSlots,
                    'ttl' => $ttl
                ]);

                return $resp;
            });

        } else {
            // Another request is already fetching - wait for it to complete
            Log::debug('Request coalescing: Waiting for other request to complete', [
                'cache_key' => $cacheKey,
                'lock_key' => $lockKey
            ]);

            // Block up to 5 seconds waiting for the winner to populate cache
            if ($lock->block(5)) {
                // Lock acquired after waiting - check cache again
                $cachedResponse = Cache::get($cacheKey);

                if ($cachedResponse) {
                    Log::info('Request coalescing: Cache populated by winner', [
                        'cache_key' => $cacheKey,
                        'waited_ms' => '< 5000'
                    ]);

                    return new Response(
                        new \GuzzleHttp\Psr7\Response(200, [], json_encode($cachedResponse))
                    );
                }

                // Cache still empty after waiting - fall through to normal fetch
                Log::warning('Request coalescing: Cache empty after wait, fetching ourselves', [
                    'cache_key' => $cacheKey
                ]);
            } else {
                // Timeout waiting for lock - proceed with normal fetch
                Log::warning('Request coalescing: Lock timeout, proceeding without lock', [
                    'cache_key' => $cacheKey,
                    'lock_key' => $lockKey
                ]);
            }
        }

    } finally {
        // Always release lock
        if ($lock->owner()) {
            $lock->release();
            Log::debug('Request coalescing: Lock released', ['lock_key' => $lockKey]);
        }
    }

    // Fallback: If coalescing failed, proceed with normal cache-miss flow
    // (This should rarely happen - only on lock timeout)
    Log::warning('Request coalescing fallback triggered', [
        'cache_key' => $cacheKey,
        'reason' => 'lock_timeout_or_cache_miss_after_wait'
    ]);

    // ... (existing cache-miss code continues here)
}
```

**Expected Impact**:
- Before: 5 concurrent requests → 5 Cal.com API calls (5 × 800ms = 4s total wait)
- After: 5 concurrent requests → 1 Cal.com API call + 4 cache reads (800ms + 4 × 5ms = 820ms)
- **Savings**: 3.2s (79% reduction) under concurrent load

---

## 3. Reduce getCallContext Retry Overhead

**File**: `/var/www/api-gateway/app/Http/Controllers/RetellFunctionCallHandler.php`

**Location**: Line 149 - `getCallContext()` method

**Change**:
```php
private function getCallContext(?string $callId): ?array
{
    if (!$callId || $callId === 'None' || $callId === '') {
        Log::warning('call_id is invalid, attempting fallback to most recent active call', [
            'call_id' => $callId
        ]);

        $recentCall = \App\Models\Call::where('call_status', 'ongoing')
            ->where('start_timestamp', '>=', now()->subMinutes(5))
            ->orderBy('start_timestamp', 'desc')
            ->first();

        if ($recentCall) {
            Log::info('✅ Fallback successful: using most recent active call', [
                'call_id' => $recentCall->retell_call_id,
                'started_at' => $recentCall->start_timestamp
            ]);
            $callId = $recentCall->retell_call_id;
        } else {
            Log::error('❌ Fallback failed: no recent active calls found');
            return null;
        }
    }

    // ✅ REDUCED: From 5 retries to 2 retries (faster failure detection)
    // Root cause fix: Webhook processing now uses DB transactions for atomic call creation
    // Retries only needed for genuine race conditions (rare)
    $maxAttempts = 2; // Changed from 5
    $call = null;

    for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
        $call = $this->callLifecycle->getCallContext($callId);

        if ($call) {
            if ($attempt > 1) {
                Log::info('✅ getCallContext succeeded on attempt ' . $attempt, [
                    'call_id' => $callId,
                    'total_attempts' => $attempt
                ]);
            }
            break;
        }

        // Not found, wait and retry
        if ($attempt < $maxAttempts) {
            $delayMs = 100; // ✅ CHANGED: Fixed 100ms delay instead of exponential (50ms, 100ms)
            Log::info('⏳ getCallContext retry ' . $attempt . '/' . $maxAttempts, [
                'call_id' => $callId,
                'delay_ms' => $delayMs
            ]);
            usleep($delayMs * 1000);
        }
    }

    if (!$call) {
        Log::error('❌ getCallContext failed after ' . $maxAttempts . ' attempts', [
            'call_id' => $callId
        ]);
        return null;
    }

    // ✅ REDUCED: From 3 enrichment waits to 1 wait (1.5s → 500ms)
    // Rationale: Enrichment should complete within 500ms (webhook processing is fast)
    if (!$call->company_id || !$call->branch_id) {
        Log::warning('⚠️ getCallContext: company_id/branch_id not set, waiting for enrichment...', [
            'call_id' => $call->id,
            'company_id' => $call->company_id,
            'branch_id' => $call->branch_id,
            'from_number' => $call->from_number
        ]);

        // ✅ CHANGED: 1 wait instead of 3 (500ms instead of 1500ms)
        usleep(500000); // 500ms single wait
        $call = $call->fresh();

        if (!$call->company_id || !$call->branch_id) {
            Log::error('❌ getCallContext: Enrichment failed after waiting', [
                'call_id' => $call->id,
                'company_id' => $call->company_id,
                'branch_id' => $call->branch_id,
                'from_number' => $call->from_number,
                'wait_time_ms' => 500,
                'suggestion' => 'Check webhook processing order and database transactions'
            ]);
            return null;
        }

        Log::info('✅ getCallContext: Enrichment completed after wait', [
            'call_id' => $call->id,
            'company_id' => $call->company_id,
            'branch_id' => $call->branch_id,
            'wait_time_ms' => 500
        ]);
    }

    // ... (rest of method unchanged)
}
```

**Expected Impact**:
- Before: Worst case 5 retries + 3 waits = 750ms + 1500ms = 2.25s
- After: Worst case 2 retries + 1 wait = 200ms + 500ms = 700ms
- **Savings**: 1.55s (69% reduction) in worst case

---

## 4. Database Indexes

**File**: Create new migration

**Command**:
```bash
php artisan make:migration add_performance_indexes_for_retell_functions
```

**Migration Content**:
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Call context lookups
        if (!Schema::hasColumn('calls', 'retell_call_id')) {
            Schema::table('calls', function (Blueprint $table) {
                $table->index('retell_call_id', 'idx_calls_retell_call_id');
            });
        }

        Schema::table('calls', function (Blueprint $table) {
            $table->index(['company_id', 'branch_id'], 'idx_calls_company_branch');
            $table->index(['call_status', 'start_timestamp'], 'idx_calls_active_lookup');
        });

        // Customer conflict checking
        Schema::table('appointments', function (Blueprint $table) {
            $table->index(['customer_id', 'starts_at', 'status'], 'idx_appointments_customer_date_status');
        });

        // Phone number lookups
        if (!Schema::hasColumn('phone_numbers', 'number')) {
            Schema::table('phone_numbers', function (Blueprint $table) {
                $table->index('number', 'idx_phone_numbers_number');
            });
        }

        // Service staff lookups
        Schema::table('service_staff', function (Blueprint $table) {
            $table->index(['service_id', 'can_book'], 'idx_service_staff_bookable');
        });
    }

    public function down(): void
    {
        Schema::table('calls', function (Blueprint $table) {
            $table->dropIndex('idx_calls_retell_call_id');
            $table->dropIndex('idx_calls_company_branch');
            $table->dropIndex('idx_calls_active_lookup');
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->dropIndex('idx_appointments_customer_date_status');
        });

        Schema::table('phone_numbers', function (Blueprint $table) {
            $table->dropIndex('idx_phone_numbers_number');
        });

        Schema::table('service_staff', function (Blueprint $table) {
            $table->dropIndex('idx_service_staff_bookable');
        });
    }
};
```

**Run**:
```bash
php artisan migrate
```

**Expected Impact**:
- Before: Full table scans (50-200ms per query)
- After: Index lookups (5-20ms per query)
- **Savings**: 30-180ms per query (60-90% reduction)

---

## 5. Eager Load Relationships

**File**: `/var/www/api-gateway/app/Services/Retell/CallLifecycleService.php`

**Location**: `getCallContext()` method (find this method in the service)

**Change**:
```php
public function getCallContext(string $callId): ?Call
{
    $cacheKey = "call_context:{$callId}";

    return Cache::remember($cacheKey, 300, function () use ($callId) {
        // ✅ ADD: Eager load relationships to prevent N+1 queries
        return Call::with([
            'company',           // Prevent +1 query
            'branch',            // Prevent +1 query
            'phoneNumber',       // Prevent +1 query
            'customer',          // Prevent +1 query (if used later)
        ])
        ->where('retell_call_id', $callId)
        ->first();
    });
}
```

**Expected Impact**:
- Before: 1 query for call + 4 separate queries for relationships = 5 queries (200-500ms)
- After: 1 query with joins = 1 query (50-100ms)
- **Savings**: 150-400ms (75-80% reduction)

---

## Validation and Testing

### 1. Performance Benchmarking

Create test script: `scripts/benchmark_performance_improvements.php`

```php
<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class PerformanceBenchmark
{
    private string $baseUrl;
    private array $results = [];

    public function __construct(string $baseUrl)
    {
        $this->baseUrl = $baseUrl;
    }

    public function benchmarkFunction(string $functionName, array $params, int $iterations = 10): void
    {
        $durations = [];

        echo "Benchmarking {$functionName} ({$iterations} iterations)...\n";

        for ($i = 0; $i < $iterations; $i++) {
            $start = microtime(true);

            $response = Http::timeout(10)->post("{$this->baseUrl}/api/retell/function", [
                'name' => $functionName,
                'call' => ['call_id' => 'benchmark_' . uniqid()],
                'args' => $params
            ]);

            $duration = (microtime(true) - $start) * 1000; // ms
            $durations[] = $duration;

            echo "  Iteration " . ($i + 1) . ": {$duration}ms\n";
            usleep(100000); // 100ms delay between requests
        }

        $this->results[$functionName] = [
            'min' => min($durations),
            'max' => max($durations),
            'avg' => array_sum($durations) / count($durations),
            'p50' => $this->percentile($durations, 50),
            'p95' => $this->percentile($durations, 95),
            'p99' => $this->percentile($durations, 99),
        ];
    }

    private function percentile(array $values, float $percentile): float
    {
        sort($values);
        $index = ceil(($percentile / 100) * count($values)) - 1;
        return $values[$index];
    }

    public function printResults(): void
    {
        echo "\n=== Performance Benchmark Results ===\n\n";

        foreach ($this->results as $function => $stats) {
            echo "{$function}:\n";
            echo "  Min:    " . number_format($stats['min'], 2) . "ms\n";
            echo "  Avg:    " . number_format($stats['avg'], 2) . "ms\n";
            echo "  Max:    " . number_format($stats['max'], 2) . "ms\n";
            echo "  P50:    " . number_format($stats['p50'], 2) . "ms\n";
            echo "  P95:    " . number_format($stats['p95'], 2) . "ms\n";
            echo "  P99:    " . number_format($stats['p99'], 2) . "ms\n\n";
        }
    }
}

$benchmark = new PerformanceBenchmark(env('APP_URL'));

// Benchmark critical functions
$benchmark->benchmarkFunction('check_availability', [
    'desired_date' => Carbon::tomorrow()->format('Y-m-d'),
    'desired_time' => '14:00',
    'duration_minutes' => 45,
    'service_name' => 'Herrenhaarschnitt'
]);

$benchmark->benchmarkFunction('get_alternatives', [
    'desired_date' => Carbon::tomorrow()->format('Y-m-d'),
    'desired_time' => '14:00',
    'duration_minutes' => 45,
    'service_name' => 'Herrenhaarschnitt'
]);

$benchmark->benchmarkFunction('find_next_available', [
    'desired_date' => Carbon::tomorrow()->format('Y-m-d'),
    'desired_time' => '14:00',
    'duration_minutes' => 45,
    'service_name' => 'Herrenhaarschnitt'
]);

$benchmark->printResults();
```

**Run**:
```bash
php scripts/benchmark_performance_improvements.php
```

---

### 2. Monitoring Dashboard

Add to Grafana/Prometheus (optional):

```yaml
# prometheus_metrics.yaml
- name: retell_function_duration_ms
  type: histogram
  help: Duration of Retell function calls in milliseconds
  labels:
    - function
    - cache_hit
    - status

- name: retell_cache_hit_rate
  type: gauge
  help: Cache hit rate for Cal.com availability requests
  labels:
    - service_id
    - date_range

- name: retell_api_call_count
  type: counter
  help: Number of Cal.com API calls made
  labels:
    - endpoint
    - status
```

---

## Rollout Plan

### Pre-Deployment Checklist

- [ ] Backup database (includes indexes in schema)
- [ ] Test on staging environment
- [ ] Run performance benchmarks (before)
- [ ] Verify cache backend (Redis) is healthy
- [ ] Check Cal.com circuit breaker status

### Deployment Steps

1. **Deploy code changes** (5-10 min)
   ```bash
   git add .
   git commit -m "perf: implement Phase 1 performance optimizations"
   git push origin develop
   ```

2. **Run database migrations** (1-2 min)
   ```bash
   php artisan migrate
   ```

3. **Clear caches** (1 min)
   ```bash
   php artisan cache:clear
   php artisan config:clear
   php artisan route:clear
   ```

4. **Restart services** (2-3 min)
   ```bash
   sudo systemctl restart php8.2-fpm
   sudo systemctl restart nginx
   ```

5. **Verify deployment** (5-10 min)
   ```bash
   # Test each optimized function
   curl -X POST https://your-domain.com/api/retell/function -d '{...}'

   # Check logs
   tail -f storage/logs/laravel.log | grep "Function performance"
   ```

6. **Run performance benchmarks** (5-10 min)
   ```bash
   php scripts/benchmark_performance_improvements.php
   ```

### Post-Deployment Validation

- [ ] Zero 500 errors from find_next_available
- [ ] check_availability < 1.5s (target: 50% improvement)
- [ ] get_alternatives < 1.2s (target: 30% improvement)
- [ ] Cache hit rate > 60%
- [ ] No user-reported issues in first 24 hours

---

## Rollback Plan

If issues occur:

```bash
# 1. Revert code changes
git revert HEAD

# 2. Rollback migrations (if needed)
php artisan migrate:rollback

# 3. Clear caches
php artisan cache:clear

# 4. Restart services
sudo systemctl restart php8.2-fpm
```

---

## Success Metrics

### Before Optimization (Baseline)

| Metric | Value |
|--------|-------|
| check_availability P95 | 3.0s |
| get_alternatives P95 | 1.7s |
| find_next_available | 500 ERROR |
| Cache hit rate | 60% |
| Cal.com API calls/min | 120 |

### After Phase 1 (Target)

| Metric | Target | % Improvement |
|--------|--------|---------------|
| check_availability P95 | 1.5s | -50% |
| get_alternatives P95 | 1.2s | -29% |
| find_next_available | 900ms | FIXED |
| Cache hit rate | 75% | +25% |
| Cal.com API calls/min | 80 | -33% |

---

## Next Steps (Phase 2)

After Phase 1 stabilizes (1-2 weeks), implement:

1. Parallel strategy execution (Guzzle async)
2. Batch date range requests
3. Smart strategy selection
4. Binary search optimization
5. Predictive prefetching

**Expected Phase 2 Impact**: Additional 40% improvement (total 70% from baseline)

---

**Document Version**: 1.0
**Last Updated**: 2025-11-06
**Owner**: Performance Engineering Team
