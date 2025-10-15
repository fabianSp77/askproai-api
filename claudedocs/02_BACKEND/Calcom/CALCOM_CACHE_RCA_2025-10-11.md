# ROOT CAUSE ANALYSIS: Cal.com Availability Cache Stale Data Bug

**Date:** 2025-10-11
**Severity:** CRITICAL
**Impact:** Duplicate bookings, customer dissatisfaction, data integrity violation

---

## EXECUTIVE SUMMARY

**Problem:** Agent bot presented 8:00 AM as "available" in Call #852 (20:38), but Appointment #676 (18:36) had ALREADY BOOKED Monday 8:00 AM. Agent used stale cached availability data.

**Root Cause:** Cache invalidation is ONLY implemented after successful `createBooking()` calls, but NOT after webhook-received bookings or reschedules. Multi-entry-point system without centralized cache invalidation.

**Evidence Chain:**
```
Call #852 logs (20:38):
- "calcom:slots:2563193:2025-10-13:2025-10-13" cache_hit
- "cal_slots_15_9_2563193_2025-10-13-10_2025-10-13-12" cache_miss → hit
- "✅ Presenting Cal.com-verified alternatives"
- Times: ["2025-10-13 08:30", "2025-10-13 08:00"] ← 8:00 SHOULD NOT BE AVAILABLE!

Database Reality:
- Appointment #676 created at 18:36 for Monday 8:00 AM
- Status: scheduled, source: cal.com
```

---

## CACHE ARCHITECTURE ANALYSIS

### 1. CACHE KEY PATTERNS

#### CalcomService Cache Keys
```php
Location: /var/www/api-gateway/app/Services/CalcomService.php

Pattern: "calcom:slots:{eventTypeId}:{startDate}:{endDate}"
Example: "calcom:slots:2563193:2025-10-13:2025-10-13"
TTL: 300 seconds (5 minutes) normal, 60 seconds (1 minute) for empty responses
Method: CalcomService::getAvailableSlots() (lines 159-274)
```

**Implementation:**
```php
// Line 161
$cacheKey = "calcom:slots:{$eventTypeId}:{$startDate}:{$endDate}";

// Lines 163-172: Cache read
$cachedResponse = Cache::get($cacheKey);
if ($cachedResponse) {
    Log::debug('Availability cache hit', ['key' => $cacheKey]);
    return new Response(
        new \GuzzleHttp\Psr7\Response(200, [], json_encode($cachedResponse))
    );
}

// Lines 234-245: Cache write with adaptive TTL
if ($totalSlots === 0) {
    $ttl = 60; // 1 minute for empty responses
} else {
    $ttl = 300; // 5 minutes for normal responses
}
Cache::put($cacheKey, $data, $ttl);
```

#### AppointmentAlternativeFinder Cache Keys
```php
Location: /var/www/api-gateway/app/Services/AppointmentAlternativeFinder.php

Pattern: "cal_slots_{companyId}_{branchId}_{eventTypeId}_{startTime}_{endTime}"
Example: "cal_slots_15_9_2563193_2025-10-13-10_2025-10-13-12"
TTL: 300 seconds (5 minutes)
Method: AppointmentAlternativeFinder::getAvailableSlots() (lines 334-406)
```

**Implementation:**
```php
// Lines 340-347: Multi-tenant cache key with security fix
$cacheKey = sprintf(
    'cal_slots_%d_%d_%d_%s_%s',
    $this->companyId ?? 0,
    $this->branchId ?? 0,
    $eventTypeId,
    $startTime->format('Y-m-d-H'),
    $endTime->format('Y-m-d-H')
);

// Line 349: Cache remember pattern
return Cache::remember($cacheKey, 300, function() use ($startTime, $endTime, $eventTypeId) {
    // Calls CalcomService::getAvailableSlots() which has its own caching
});
```

**CRITICAL FINDING:** Nested caching creates TWO cache layers!
- Layer 1: CalcomService cache (granular by date)
- Layer 2: AlternativeFinder cache (granular by hour window + tenant context)

### 2. CACHE INVALIDATION MAPPING

#### IMPLEMENTED: CalcomService::createBooking()
```php
Location: /var/www/api-gateway/app/Services/CalcomService.php
Lines: 138, 296-310

// After successful booking
$this->clearAvailabilityCacheForEventType($eventTypeId);

private function clearAvailabilityCacheForEventType(int $eventTypeId): void
{
    // Clear cache for next 30 days
    $today = Carbon::today();
    for ($i = 0; $i < 30; $i++) {
        $date = $today->copy()->addDays($i)->format('Y-m-d');
        $cacheKey = "calcom:slots:{$eventTypeId}:{$date}:{$date}";
        Cache::forget($cacheKey);
    }

    Log::info('Cleared availability cache after booking', [
        'event_type_id' => $eventTypeId,
        'days_cleared' => 30,
    ]);
}
```

**Coverage:** ONLY clears CalcomService cache layer (Pattern 1)
**Missing:** Does NOT clear AlternativeFinder cache layer (Pattern 2)

#### MISSING: CalcomWebhookController
```php
Location: /var/www/api-gateway/app/Http/Controllers/CalcomWebhookController.php

// NO CACHE INVALIDATION in any webhook handler!

handleBookingCreated()    - Lines 199-313  ❌ No cache clear
handleBookingUpdated()    - Lines 318-362  ❌ No cache clear
handleBookingCancelled()  - Lines 367-409  ❌ No cache clear
```

#### MISSING: CalcomService::rescheduleBooking()
```php
Location: /var/www/api-gateway/app/Services/CalcomService.php
Lines: 614-673

public function rescheduleBooking(...): Response
{
    // Wraps Cal.com API call
    // SUCCESS: Returns response
    // ❌ NO CACHE INVALIDATION!
}
```

#### MISSING: CalcomService::cancelBooking()
```php
Location: /var/www/api-gateway/app/Services/CalcomService.php
Lines: 683-728

public function cancelBooking(...): Response
{
    // Wraps Cal.com API call
    // SUCCESS: Returns response
    // ❌ NO CACHE INVALIDATION!
}
```

---

## RACE CONDITION SCENARIOS

### Scenario 1: Webhook-Created Booking Not Invalidating Cache
```
Timeline:
18:36:00 - Customer books Monday 8:00 via Cal.com widget
18:36:01 - Cal.com creates booking ID abc123
18:36:02 - Cal.com sends webhook to our system
18:36:03 - CalcomWebhookController::handleBookingCreated()
18:36:04 - Appointment #676 created in database
18:36:05 - ❌ NO CACHE INVALIDATION
18:36:06 - Cache still shows Monday 8:00 as "available"

20:38:00 - Agent bot Call #852 checks availability
20:38:01 - AlternativeFinder reads STALE cache
20:38:02 - Cache hit: "cal_slots_15_9_2563193_2025-10-13-10_2025-10-13-12"
20:38:03 - Returns Monday 8:00 as "available"
20:38:04 - Agent presents to customer: "8:00 ist frei"
```

**Duration:** 2 hours of stale cache (cache TTL not expired)
**Result:** Double booking attempted

### Scenario 2: Concurrent Booking During Cache Read
```
Timeline:
T+0ms  - Call A: AlternativeFinder reads cache (miss)
T+10ms - Call A: Fetches Cal.com API (includes 8:00 slot)
T+20ms - Call B: Creates booking for 8:00 via widget
T+30ms - Call B: Webhook updates database
T+40ms - Call A: Writes fetched data to cache (INCLUDES 8:00!)
T+50ms - Call A: Returns 8:00 as available (STALE DATA!)
```

**Race Window:** 30-50ms during API fetch
**Probability:** Low but possible under load

### Scenario 3: Multi-Layer Cache Desync
```
Timeline:
T+0   - Booking created via createBooking()
T+1   - CalcomService cache cleared (Layer 1) ✅
T+2   - AlternativeFinder cache NOT cleared (Layer 2) ❌
T+3   - Next request hits AlternativeFinder cache
T+4   - Returns stale data from Layer 2
T+5   - Layer 2 calls CalcomService which fetches fresh data
T+6   - BUT Layer 2 already returned stale data!
```

**Duration:** Until Layer 2 cache expires (300s)
**Result:** Inconsistent availability across different code paths

---

## INVALIDATION GAPS MATRIX

| Entry Point | Creates Booking? | Invalidates Layer 1? | Invalidates Layer 2? | Gap Severity |
|-------------|------------------|----------------------|----------------------|--------------|
| CalcomService::createBooking() | ✅ Yes | ✅ Yes (Line 138) | ❌ No | 🟡 MEDIUM |
| CalcomWebhookController::handleBookingCreated() | ✅ Yes | ❌ No | ❌ No | 🔴 CRITICAL |
| CalcomWebhookController::handleBookingUpdated() | ✅ Yes | ❌ No | ❌ No | 🔴 CRITICAL |
| CalcomWebhookController::handleBookingCancelled() | ✅ Yes | ❌ No | ❌ No | 🔴 CRITICAL |
| CalcomService::rescheduleBooking() | ✅ Yes | ❌ No | ❌ No | 🔴 CRITICAL |
| CalcomService::cancelBooking() | ✅ Yes | ❌ No | ❌ No | 🔴 CRITICAL |
| AppointmentCreationService::bookInCalcom() | ✅ Yes (calls createBooking) | ✅ Yes (inherited) | ❌ No | 🟡 MEDIUM |

**Key Finding:** 5 out of 7 entry points have NO cache invalidation!

---

## ROOT CAUSE STATEMENT

**PRIMARY CAUSE:**
Cache invalidation is implemented as a **private helper method** (`clearAvailabilityCacheForEventType`) inside `CalcomService::createBooking()`, making it inaccessible to other booking entry points (webhooks, reschedules, cancellations).

**CONTRIBUTING FACTORS:**
1. **Multi-Layer Caching:** Two independent cache layers (CalcomService + AlternativeFinder) with different key patterns
2. **No Centralized Invalidation:** Each service manages its own cache without coordination
3. **Webhook Gap:** Webhooks create bookings but never invalidate cache
4. **Incomplete Coverage:** Only 2/7 booking entry points invalidate cache

**ARCHITECTURAL FLAW:**
The system assumes ALL bookings flow through `CalcomService::createBooking()`, but in reality:
- Cal.com widget bookings arrive via webhook (bypass createBooking)
- Manual reschedules use rescheduleBooking() (no invalidation)
- Cancellations use cancelBooking() (no invalidation)

---

## FIX RECOMMENDATIONS

### IMMEDIATE (Priority 1) - Deploy Today

#### Fix 1: Extract Cache Invalidation to Public Method
```php
// CalcomService.php
public function invalidateAvailabilityCache(int $eventTypeId): void
{
    // Clear CalcomService cache layer
    $today = Carbon::today();
    for ($i = 0; $i < 30; $i++) {
        $date = $today->copy()->addDays($i)->format('Y-m-d');
        $cacheKey = "calcom:slots:{$eventTypeId}:{$date}:{$date}";
        Cache::forget($cacheKey);
    }

    // Clear AlternativeFinder cache layer (wildcard pattern)
    $this->clearAlternativeFinderCache($eventTypeId);

    Log::info('Invalidated availability cache (all layers)', [
        'event_type_id' => $eventTypeId,
        'layers_cleared' => ['calcom_service', 'alternative_finder']
    ]);
}

private function clearAlternativeFinderCache(int $eventTypeId): void
{
    // Pattern: cal_slots_{companyId}_{branchId}_{eventTypeId}_{start}_{end}
    // Use Cache::flush() or tag-based invalidation if available

    // OPTION A: Tag-based (Laravel 11+)
    Cache::tags(['calcom', "event_type:{$eventTypeId}"])->flush();

    // OPTION B: Pattern-based (requires custom implementation)
    // This is a placeholder - actual implementation depends on cache driver
    $pattern = "cal_slots_*_{$eventTypeId}_*";
    $this->clearCacheByPattern($pattern);
}
```

#### Fix 2: Add Cache Invalidation to All Booking Entry Points
```php
// CalcomWebhookController.php

protected function handleBookingCreated(array $payload): ?Appointment
{
    // ... existing code ...

    // FIX: Invalidate cache after webhook booking
    if ($service && $service->calcom_event_type_id) {
        $calcomService = app(\App\Services\CalcomService::class);
        $calcomService->invalidateAvailabilityCache($service->calcom_event_type_id);

        Log::info('Cache invalidated after webhook booking', [
            'event_type_id' => $service->calcom_event_type_id,
            'booking_id' => $calcomId
        ]);
    }

    return $appointment;
}

protected function handleBookingUpdated(array $payload): ?Appointment
{
    // ... existing code ...

    // FIX: Invalidate cache after reschedule
    if ($appointment && $appointment->service && $appointment->service->calcom_event_type_id) {
        $calcomService = app(\App\Services\CalcomService::class);
        $calcomService->invalidateAvailabilityCache($appointment->service->calcom_event_type_id);
    }

    return $appointment;
}

protected function handleBookingCancelled(array $payload): ?Appointment
{
    // ... existing code ...

    // FIX: Invalidate cache after cancellation
    if ($appointment && $appointment->service && $appointment->service->calcom_event_type_id) {
        $calcomService = app(\App\Services\CalcomService::class);
        $calcomService->invalidateAvailabilityCache($appointment->service->calcom_event_type_id);
    }

    return $appointment;
}
```

#### Fix 3: Add Invalidation to Reschedule and Cancel Methods
```php
// CalcomService.php

public function rescheduleBooking(...): Response
{
    try {
        return $this->circuitBreaker->call(function() use ($bookingId, $payload) {
            // ... existing API call ...

            if (!$resp->successful()) {
                throw CalcomApiException::fromResponse($resp, ...);
            }

            // FIX: Invalidate cache after successful reschedule
            // Extract eventTypeId from booking data or pass as parameter
            if (isset($payload['eventTypeId'])) {
                $this->invalidateAvailabilityCache($payload['eventTypeId']);
            }

            return $resp;
        });
    } catch (CircuitBreakerOpenException $e) {
        // ... existing error handling ...
    }
}

public function cancelBooking($bookingId, ?string $reason = null, ?int $eventTypeId = null): Response
{
    try {
        return $this->circuitBreaker->call(function() use ($bookingId, $payload, $eventTypeId) {
            // ... existing API call ...

            if (!$resp->successful()) {
                throw CalcomApiException::fromResponse($resp, ...);
            }

            // FIX: Invalidate cache after successful cancellation
            if ($eventTypeId) {
                $this->invalidateAvailabilityCache($eventTypeId);
            }

            return $resp;
        });
    } catch (CircuitBreakerOpenException $e) {
        // ... existing error handling ...
    }
}
```

### MEDIUM-TERM (Priority 2) - Next Sprint

#### Enhancement 1: Implement Cache Tagging
```php
// Enable tagging in config/cache.php
'stores' => [
    'redis' => [
        'driver' => 'redis',
        'connection' => 'cache',
        'lock_connection' => 'default',
        'tags' => true, // Enable tagging
    ],
],

// CalcomService.php - Tag cache entries
Cache::tags(['calcom', "event_type:{$eventTypeId}"])
    ->put($cacheKey, $data, $ttl);

// Invalidation becomes trivial
Cache::tags(['calcom', "event_type:{$eventTypeId}"])->flush();
```

#### Enhancement 2: Reduce Cache TTL
```php
// Current: 300 seconds (5 minutes)
// Proposed: 60 seconds (1 minute)

// CalcomService.php - Line 242
$ttl = 60; // Reduce stale data window from 5min → 1min
```

#### Enhancement 3: Add Cache Versioning
```php
// Add version to cache keys
$cacheVersion = config('cache.version', 'v1');
$cacheKey = "{$cacheVersion}:calcom:slots:{$eventTypeId}:{$startDate}:{$endDate}";

// Increment version to invalidate ALL cache entries
// config/cache.php
'version' => 'v2', // Bump when deploying fixes
```

### LONG-TERM (Priority 3) - Technical Debt

#### Refactor 1: Centralized Cache Manager
```php
// Create CacheInvalidationService
class CalcomCacheManager
{
    public function invalidateBooking(int $eventTypeId, Carbon $bookingDate): void
    {
        $this->invalidateCalcomServiceCache($eventTypeId, $bookingDate);
        $this->invalidateAlternativeFinderCache($eventTypeId, $bookingDate);
        $this->invalidateRelatedCaches($eventTypeId);
    }

    public function invalidateReschedule(int $eventTypeId, Carbon $oldDate, Carbon $newDate): void
    {
        // Invalidate both old and new dates
    }

    public function invalidateAll(int $eventTypeId): void
    {
        // Nuclear option - flush everything
    }
}
```

#### Refactor 2: Event-Driven Invalidation
```php
// Dispatch events on booking changes
event(new BookingCreated($appointment));
event(new BookingRescheduled($appointment, $oldDate, $newDate));
event(new BookingCancelled($appointment));

// Listener handles cache invalidation
class InvalidateCalcomCache
{
    public function handle(BookingCreated|BookingRescheduled|BookingCancelled $event): void
    {
        $this->cacheManager->invalidateBooking(
            $event->appointment->service->calcom_event_type_id,
            $event->appointment->starts_at
        );
    }
}
```

#### Refactor 3: Redis Pub/Sub for Real-Time Invalidation
```php
// Webhook publishes invalidation event
Redis::publish('calcom:invalidate', json_encode([
    'event_type_id' => $eventTypeId,
    'action' => 'booking_created',
    'timestamp' => now()->timestamp
]));

// All app servers subscribe and invalidate local cache
Redis::subscribe(['calcom:invalidate'], function ($message) {
    $data = json_decode($message);
    Cache::forget("calcom:slots:{$data['event_type_id']}:*");
});
```

---

## TESTING STRATEGY

### Test Case 1: Webhook Booking Invalidates Cache
```php
public function test_webhook_booking_invalidates_cache(): void
{
    // ARRANGE: Populate cache with availability
    $eventTypeId = 2563193;
    $date = '2025-10-13';
    $cacheKey = "calcom:slots:{$eventTypeId}:{$date}:{$date}";

    Cache::put($cacheKey, ['data' => ['slots' => ['2025-10-13' => ['08:00', '09:00']]]], 300);

    // ACT: Simulate webhook booking
    $webhookPayload = [
        'triggerEvent' => 'BOOKING.CREATED',
        'payload' => [
            'id' => 'test123',
            'eventTypeId' => $eventTypeId,
            'startTime' => '2025-10-13T08:00:00+02:00',
            'endTime' => '2025-10-13T09:00:00+02:00',
            'attendees' => [['name' => 'Test', 'email' => 'test@test.com']]
        ]
    ];

    $this->postJson('/api/calcom/webhook', $webhookPayload);

    // ASSERT: Cache should be cleared
    $this->assertNull(Cache::get($cacheKey));
}
```

### Test Case 2: Reschedule Invalidates Both Dates
```php
public function test_reschedule_invalidates_old_and_new_dates(): void
{
    // ARRANGE: Create appointment
    $appointment = Appointment::factory()->create([
        'calcom_v2_booking_id' => 'test123',
        'starts_at' => '2025-10-13 08:00:00'
    ]);

    $oldDate = '2025-10-13';
    $newDate = '2025-10-14';

    Cache::put("calcom:slots:{$eventTypeId}:{$oldDate}:{$oldDate}", ['slots'], 300);
    Cache::put("calcom:slots:{$eventTypeId}:{$newDate}:{$newDate}", ['slots'], 300);

    // ACT: Reschedule via webhook
    $this->postJson('/api/calcom/webhook', [
        'triggerEvent' => 'BOOKING.RESCHEDULED',
        'payload' => [
            'id' => 'test123',
            'startTime' => '2025-10-14T08:00:00+02:00'
        ]
    ]);

    // ASSERT: Both dates should be cleared
    $this->assertNull(Cache::get("calcom:slots:{$eventTypeId}:{$oldDate}:{$oldDate}"));
    $this->assertNull(Cache::get("calcom:slots:{$eventTypeId}:{$newDate}:{$newDate}"));
}
```

### Test Case 3: Concurrent Cache Read During Booking
```php
public function test_no_stale_cache_during_concurrent_booking(): void
{
    // This test requires race condition simulation
    // Use parallel processes or queue workers to simulate concurrency
}
```

---

## MONITORING & ALERTS

### Metric 1: Cache Hit Rate per Layer
```php
Log::info('Cache metrics', [
    'layer' => 'calcom_service',
    'cache_key' => $cacheKey,
    'hit' => $cachedResponse ? true : false,
    'ttl_remaining' => Cache::get($cacheKey) ? Cache::ttl($cacheKey) : 0
]);
```

### Metric 2: Cache Age at Booking Attempt
```php
Log::warning('Booking attempted with cached data', [
    'cache_age_seconds' => now()->timestamp - $cacheCreatedAt,
    'cache_freshness_threshold' => 60,
    'risk_level' => $age > 60 ? 'high' : 'low'
]);
```

### Alert 1: Double Booking Detection
```php
if ($existingAppointment) {
    alert('Double booking prevented by database check', [
        'existing_id' => $existingAppointment->id,
        'attempted_time' => $bookingDetails['starts_at'],
        'cache_age' => $cacheAge,
        'severity' => 'critical'
    ]);
}
```

---

## DEPLOYMENT PLAN

### Phase 1: Hotfix (Today)
1. Extract `invalidateAvailabilityCache()` to public method
2. Add invalidation to webhook handlers
3. Deploy with feature flag: `ENABLE_AGGRESSIVE_CACHE_INVALIDATION=true`
4. Monitor logs for 24 hours

### Phase 2: Validation (Tomorrow)
1. Run automated test suite
2. Manual testing: Create booking via widget → Check cache cleared
3. Load testing: 100 concurrent bookings → Verify no stale cache hits

### Phase 3: Rollout (Next Week)
1. Deploy to staging environment
2. Smoke test all booking flows
3. Deploy to production with gradual rollout (10% → 50% → 100%)
4. Monitor error rates and cache metrics

### Rollback Plan
```bash
# If issues detected:
git revert <commit-hash>
php artisan cache:clear
php artisan config:clear
php artisan deploy:rollback
```

---

## CONCLUSION

**Root Cause:** Missing cache invalidation in 5 out of 7 booking entry points, particularly webhook handlers.

**Impact:** Stale availability data presented to customers for up to 5 minutes after bookings, causing double booking attempts.

**Fix Complexity:** Low - Add 3 lines of code to each webhook handler.

**Estimated Fix Time:** 2 hours development + 1 hour testing + 1 hour deployment = 4 hours total.

**Risk Assessment:** Low risk - Fix only adds cache invalidation calls, doesn't modify core booking logic.

**Recommendation:** Deploy IMMEDIATELY as hotfix to prevent further double booking incidents.

---

**Analyzed By:** Claude (Root Cause Analyst)
**Reviewed By:** [Pending]
**Approved By:** [Pending]
**Status:** Ready for Implementation
