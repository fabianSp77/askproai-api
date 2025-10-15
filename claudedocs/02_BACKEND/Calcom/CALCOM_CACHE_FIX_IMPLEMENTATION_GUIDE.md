# IMPLEMENTATION GUIDE: Cal.com Cache Invalidation Fix

**Date:** 2025-10-11
**Ticket:** CALCOM-CACHE-001
**Estimated Time:** 4 hours
**Risk Level:** 🟢 Low

---

## QUICK START

```bash
# 1. Create feature branch
git checkout main
git pull origin main
git checkout -b fix/calcom-cache-invalidation

# 2. Apply fixes (see below)

# 3. Test
php artisan test --filter CalcomCache

# 4. Deploy
git add .
git commit -m "fix: Add cache invalidation to all Cal.com booking entry points"
git push origin fix/calcom-cache-invalidation
```

---

## FILE MODIFICATIONS

### File 1: CalcomService.php
**Location:** `/var/www/api-gateway/app/Services/CalcomService.php`
**Changes:** 3 additions

#### Change 1.1: Make invalidation method public
**Location:** Line 296
**Current:**
```php
private function clearAvailabilityCacheForEventType(int $eventTypeId): void
```

**Change to:**
```php
public function clearAvailabilityCacheForEventType(int $eventTypeId): void
```

**Reason:** Allow webhook controller to call this method

---

#### Change 1.2: Add AlternativeFinder cache clearing
**Location:** After line 310 (inside clearAvailabilityCacheForEventType)
**Add:**
```php
// Also clear AlternativeFinder cache layer (nested cache)
// Pattern: cal_slots_{companyId}_{branchId}_{eventTypeId}_{hourRange}
$this->clearAlternativeFinderCache($eventTypeId);

Log::info('Cleared availability cache (both layers)', [
    'event_type_id' => $eventTypeId,
    'layers' => ['calcom_service', 'alternative_finder']
]);
```

---

#### Change 1.3: Add helper method for Layer 2 cache
**Location:** After clearAvailabilityCacheForEventType() method (around line 311)
**Add:**
```php
/**
 * Clear AlternativeFinder cache layer (Layer 2)
 * Clears all cache keys matching pattern for this event type
 *
 * @param int $eventTypeId Cal.com event type ID
 */
private function clearAlternativeFinderCache(int $eventTypeId): void
{
    // Pattern: cal_slots_{company}_{branch}_{eventType}_{startHour}_{endHour}
    // We need to clear all variations of this pattern

    // Get all cache keys matching the pattern
    $pattern = "cal_slots_*_{$eventTypeId}_*";

    // OPTION A: Using Redis driver (most common)
    if (Cache::getStore() instanceof \Illuminate\Cache\RedisStore) {
        $redis = Cache::getStore()->connection();
        $prefix = Cache::getPrefix();
        $keys = $redis->keys($prefix . $pattern);

        foreach ($keys as $key) {
            // Remove prefix before passing to Cache::forget()
            $cacheKey = str_replace($prefix, '', $key);
            Cache::forget($cacheKey);
        }

        Log::debug('Cleared AlternativeFinder cache entries', [
            'pattern' => $pattern,
            'keys_cleared' => count($keys)
        ]);
    }

    // OPTION B: Fallback for other cache drivers
    // Clear common company/branch combinations (brute force)
    // This is less efficient but works with any cache driver
    else {
        $today = Carbon::today();
        $companies = [15]; // Add known company IDs
        $branches = [9, null]; // Add known branch IDs

        foreach ($companies as $companyId) {
            foreach ($branches as $branchId) {
                for ($day = 0; $day < 30; $day++) {
                    $date = $today->copy()->addDays($day);

                    // Clear hourly cache keys for business hours (9-18)
                    for ($hour = 9; $hour <= 17; $hour++) {
                        $cacheKey = sprintf(
                            'cal_slots_%d_%s_%d_%s-%02d_%s-%02d',
                            $companyId,
                            $branchId ?? 0,
                            $eventTypeId,
                            $date->format('Y-m-d'),
                            $hour,
                            $date->format('Y-m-d'),
                            $hour + 1
                        );
                        Cache::forget($cacheKey);
                    }
                }
            }
        }

        Log::debug('Cleared AlternativeFinder cache (fallback method)', [
            'event_type_id' => $eventTypeId
        ]);
    }
}
```

---

#### Change 1.4: Add invalidation to rescheduleBooking()
**Location:** Line 659 (after successful response check in rescheduleBooking)
**Find:**
```php
if (!$resp->successful()) {
    throw CalcomApiException::fromResponse($resp, "/bookings/{$bookingId}/reschedule", $payload, 'POST');
}

return $resp;
```

**Change to:**
```php
if (!$resp->successful()) {
    throw CalcomApiException::fromResponse($resp, "/bookings/{$bookingId}/reschedule", $payload, 'POST');
}

// FIX: Invalidate cache after successful reschedule
// Need to get eventTypeId - best to pass it as parameter
// For now, extract from response or lookup booking
$responseData = $resp->json();
if (isset($responseData['data']['eventTypeId'])) {
    $this->clearAvailabilityCacheForEventType($responseData['data']['eventTypeId']);

    Log::info('Cache invalidated after reschedule', [
        'booking_id' => $bookingId,
        'event_type_id' => $responseData['data']['eventTypeId']
    ]);
}

return $resp;
```

---

#### Change 1.5: Update rescheduleBooking() signature
**Location:** Line 614
**Find:**
```php
public function rescheduleBooking($bookingId, string $newDateTime, ?string $reason = null, ?string $timezone = null): Response
```

**Change to:**
```php
public function rescheduleBooking($bookingId, string $newDateTime, ?string $reason = null, ?string $timezone = null, ?int $eventTypeId = null): Response
```

**Then update the invalidation (replace Change 1.4):**
```php
if (!$resp->successful()) {
    throw CalcomApiException::fromResponse($resp, "/bookings/{$bookingId}/reschedule", $payload, 'POST');
}

// FIX: Invalidate cache after successful reschedule
if ($eventTypeId) {
    $this->clearAvailabilityCacheForEventType($eventTypeId);

    Log::info('Cache invalidated after reschedule', [
        'booking_id' => $bookingId,
        'event_type_id' => $eventTypeId
    ]);
}

return $resp;
```

---

#### Change 1.6: Add invalidation to cancelBooking()
**Location:** Line 712 (after successful response check in cancelBooking)
**Find:**
```php
if (!$resp->successful()) {
    throw CalcomApiException::fromResponse($resp, "/bookings/{$bookingId}/cancel", $payload, 'POST');
}

return $resp;
```

**Change to:**
```php
if (!$resp->successful()) {
    throw CalcomApiException::fromResponse($resp, "/bookings/{$bookingId}/cancel", $payload, 'POST');
}

// FIX: Invalidate cache after successful cancellation
// Extract eventTypeId from response or pass as parameter
$responseData = $resp->json();
if (isset($responseData['data']['eventTypeId'])) {
    $this->clearAvailabilityCacheForEventType($responseData['data']['eventTypeId']);

    Log::info('Cache invalidated after cancellation', [
        'booking_id' => $bookingId,
        'event_type_id' => $responseData['data']['eventTypeId']
    ]);
}

return $resp;
```

---

#### Change 1.7: Update cancelBooking() signature
**Location:** Line 683
**Find:**
```php
public function cancelBooking($bookingId, ?string $reason = null): Response
```

**Change to:**
```php
public function cancelBooking($bookingId, ?string $reason = null, ?int $eventTypeId = null): Response
```

**Then update the invalidation (replace Change 1.6):**
```php
if (!$resp->successful()) {
    throw CalcomApiException::fromResponse($resp, "/bookings/{$bookingId}/cancel", $payload, 'POST');
}

// FIX: Invalidate cache after successful cancellation
if ($eventTypeId) {
    $this->clearAvailabilityCacheForEventType($eventTypeId);

    Log::info('Cache invalidated after cancellation', [
        'booking_id' => $bookingId,
        'event_type_id' => $eventTypeId
    ]);
}

return $resp;
```

---

### File 2: CalcomWebhookController.php
**Location:** `/var/www/api-gateway/app/Http/Controllers/CalcomWebhookController.php`
**Changes:** 3 additions

#### Change 2.1: Add invalidation to handleBookingCreated()
**Location:** Line 301 (before return statement)
**Find:**
```php
Log::channel('calcom')->info('[Cal.com] Appointment created from booking', [
    'appointment_id' => $appointment->id,
    'calcom_id' => $calcomId,
    'customer' => $customer->name,
    'staff_id' => $staffId,
    'assignment_model' => $assignmentMetadata['assignment_model_used'] ?? 'none',
    'time' => $startTime->format('Y-m-d H:i')
]);

return $appointment;
```

**Change to:**
```php
Log::channel('calcom')->info('[Cal.com] Appointment created from booking', [
    'appointment_id' => $appointment->id,
    'calcom_id' => $calcomId,
    'customer' => $customer->name,
    'staff_id' => $staffId,
    'assignment_model' => $assignmentMetadata['assignment_model_used'] ?? 'none',
    'time' => $startTime->format('Y-m-d H:i')
]);

// FIX: Invalidate cache after webhook booking
if ($service && $service->calcom_event_type_id) {
    $calcomService = app(\App\Services\CalcomService::class);
    $calcomService->clearAvailabilityCacheForEventType($service->calcom_event_type_id);

    Log::channel('calcom')->info('[Cal.com] Cache invalidated after webhook booking', [
        'event_type_id' => $service->calcom_event_type_id,
        'booking_id' => $calcomId,
        'appointment_id' => $appointment->id
    ]);
}

return $appointment;
```

---

#### Change 2.2: Add invalidation to handleBookingUpdated()
**Location:** Line 349 (before return statement)
**Find:**
```php
Log::channel('calcom')->info('[Cal.com] Appointment rescheduled', [
    'appointment_id' => $appointment->id,
    'new_time' => $payload['startTime']
]);
return $appointment;
```

**Change to:**
```php
Log::channel('calcom')->info('[Cal.com] Appointment rescheduled', [
    'appointment_id' => $appointment->id,
    'new_time' => $payload['startTime']
]);

// FIX: Invalidate cache after reschedule webhook
if ($appointment && $appointment->service && $appointment->service->calcom_event_type_id) {
    $calcomService = app(\App\Services\CalcomService::class);
    $calcomService->clearAvailabilityCacheForEventType($appointment->service->calcom_event_type_id);

    Log::channel('calcom')->info('[Cal.com] Cache invalidated after reschedule webhook', [
        'event_type_id' => $appointment->service->calcom_event_type_id,
        'appointment_id' => $appointment->id
    ]);
}

return $appointment;
```

---

#### Change 2.3: Add invalidation to handleBookingCancelled()
**Location:** Line 394 (before return statement)
**Find:**
```php
Log::channel('calcom')->info('[Cal.com] Appointment cancelled', [
    'appointment_id' => $appointment->id,
    'reason' => $payload['cancellationReason'] ?? null
]);
return $appointment;
```

**Change to:**
```php
Log::channel('calcom')->info('[Cal.com] Appointment cancelled', [
    'appointment_id' => $appointment->id,
    'reason' => $payload['cancellationReason'] ?? null
]);

// FIX: Invalidate cache after cancellation webhook
if ($appointment && $appointment->service && $appointment->service->calcom_event_type_id) {
    $calcomService = app(\App\Services\CalcomService::class);
    $calcomService->clearAvailabilityCacheForEventType($appointment->service->calcom_event_type_id);

    Log::channel('calcom')->info('[Cal.com] Cache invalidated after cancellation webhook', [
        'event_type_id' => $appointment->service->calcom_event_type_id,
        'appointment_id' => $appointment->id
    ]);
}

return $appointment;
```

---

## TESTING SCRIPT

### Test 1: Unit Test for Cache Invalidation
**Create:** `/var/www/api-gateway/tests/Feature/CalcomCacheInvalidationTest.php`

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\CalcomService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CalcomCacheInvalidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_webhook_booking_invalidates_cache(): void
    {
        // ARRANGE: Populate cache with availability
        $eventTypeId = 2563193;
        $date = '2025-10-13';
        $cacheKey = "calcom:slots:{$eventTypeId}:{$date}:{$date}";

        Cache::put($cacheKey, [
            'data' => [
                'slots' => [
                    '2025-10-13' => [
                        ['time' => '2025-10-13T08:00:00+02:00'],
                        ['time' => '2025-10-13T09:00:00+02:00']
                    ]
                ]
            ]
        ], 300);

        $this->assertNotNull(Cache::get($cacheKey), 'Cache should be populated');

        // ACT: Simulate webhook booking
        $webhookPayload = [
            'triggerEvent' => 'BOOKING.CREATED',
            'payload' => [
                'id' => 'test-booking-123',
                'eventTypeId' => $eventTypeId,
                'startTime' => '2025-10-13T08:00:00+02:00',
                'endTime' => '2025-10-13T09:00:00+02:00',
                'attendees' => [
                    [
                        'name' => 'Test Customer',
                        'email' => 'test@example.com'
                    ]
                ]
            ]
        ];

        $response = $this->postJson('/api/calcom/webhook', $webhookPayload);

        // ASSERT: Cache should be cleared
        $response->assertStatus(200);
        $this->assertNull(Cache::get($cacheKey), 'Cache should be cleared after webhook');
    }

    public function test_reschedule_invalidates_cache(): void
    {
        // Similar test for reschedule webhook
        // ... (implementation similar to above)
    }

    public function test_cancellation_invalidates_cache(): void
    {
        // Similar test for cancellation webhook
        // ... (implementation similar to above)
    }

    public function test_layer2_cache_also_cleared(): void
    {
        // Test that AlternativeFinder cache is also cleared
        $eventTypeId = 2563193;
        $companyId = 15;
        $branchId = 9;

        // Populate Layer 2 cache
        $layer2Key = "cal_slots_{$companyId}_{$branchId}_{$eventTypeId}_2025-10-13-10_2025-10-13-12";
        Cache::put($layer2Key, ['slots' => ['08:00', '09:00']], 300);

        $this->assertNotNull(Cache::get($layer2Key), 'Layer 2 cache should be populated');

        // Trigger invalidation
        $calcomService = app(CalcomService::class);
        $calcomService->clearAvailabilityCacheForEventType($eventTypeId);

        // Assert Layer 2 cleared
        $this->assertNull(Cache::get($layer2Key), 'Layer 2 cache should be cleared');
    }
}
```

---

### Test 2: Manual Testing Checklist

```bash
# 1. Check Redis before test
redis-cli KEYS "*calcom*"
redis-cli KEYS "*cal_slots*"
# Should show cached keys

# 2. Create booking via Cal.com widget
# - Go to Cal.com booking URL
# - Book a test appointment
# - Wait for webhook to arrive (~2 seconds)

# 3. Check logs
tail -f storage/logs/calcom.log | grep "Cache invalidated"
# Should see:
# [Cal.com] Cache invalidated after webhook booking

# 4. Check Redis after booking
redis-cli KEYS "*calcom*"
redis-cli KEYS "*cal_slots*"
# Should show FEWER keys (today's date cleared)

# 5. Test availability check
curl -X POST http://localhost/api/retell/function-call \
  -H "Content-Type: application/json" \
  -d '{
    "function_name": "get_alternatives",
    "call_id": "test123",
    "parameters": {
      "datum": "2025-10-13",
      "uhrzeit": "08:00"
    }
  }'
# Should NOT show 8:00 if already booked

# 6. Test reschedule
# - Use Cal.com to reschedule appointment
# - Check logs for cache invalidation
# - Verify both old and new dates cleared

# 7. Test cancellation
# - Cancel appointment via Cal.com
# - Check logs for cache invalidation
# - Verify cache cleared
```

---

## DEPLOYMENT CHECKLIST

```
□ Code Changes
  □ CalcomService.php modified (7 changes)
  □ CalcomWebhookController.php modified (3 changes)
  □ Test file created

□ Testing
  □ Unit tests pass
  □ Manual webhook test passes
  □ Reschedule test passes
  □ Cancellation test passes
  □ Load test passes (optional)

□ Code Review
  □ PR created
  □ Code reviewed by team
  □ Security review passed
  □ Performance review passed

□ Staging Deployment
  □ Deploy to staging environment
  □ Smoke test all booking flows
  □ Monitor logs for errors
  □ Verify cache invalidation in logs

□ Production Deployment
  □ Create deployment ticket
  □ Schedule maintenance window (if needed)
  □ Deploy to production
  □ Monitor error rates (15 minutes)
  □ Monitor cache metrics (1 hour)
  □ Verify no regressions (24 hours)

□ Post-Deployment
  □ Update documentation
  □ Notify team of deployment
  □ Close ticket
  □ Schedule follow-up review
```

---

## ROLLBACK PROCEDURE

```bash
# If issues detected after deployment:

# 1. Check error logs
tail -f storage/logs/laravel.log | grep "ERROR"

# 2. Check cache errors
tail -f storage/logs/calcom.log | grep "Cache"

# 3. If errors found, rollback:
git revert <commit-hash>
git push origin main

# 4. Clear application cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# 5. Restart services
php artisan queue:restart
sudo systemctl restart php8.2-fpm

# 6. Verify rollback successful
curl http://localhost/health
# Should return 200 OK

# 7. Notify team
# - Rollback completed
# - Incident ticket created
# - Root cause investigation scheduled
```

---

## MONITORING QUERIES

```bash
# Check cache invalidation rate
grep "Cache invalidated" storage/logs/calcom.log | wc -l

# Check for stale cache warnings
grep "STALE CACHE" storage/logs/calcom.log

# Check Redis memory usage
redis-cli INFO memory | grep used_memory_human

# Check cache key count
redis-cli DBSIZE

# Check specific cache keys
redis-cli KEYS "*calcom*" | wc -l
redis-cli KEYS "*cal_slots*" | wc -l

# Monitor error rate
grep "ERROR" storage/logs/laravel.log | tail -20

# Check webhook processing time
grep "Webhook processed" storage/logs/calcom.log | \
  awk '{print $NF}' | \
  awk '{ sum += $1; n++ } END { if (n > 0) print sum / n; }'
```

---

## COMMON ISSUES & SOLUTIONS

### Issue 1: Cache keys not being cleared
**Symptom:** Keys still present in Redis after booking
**Cause:** Cache driver might not support pattern matching
**Solution:** Check cache driver configuration, use fallback method

### Issue 2: Performance degradation
**Symptom:** Slow webhook processing after deployment
**Cause:** Clearing too many cache keys
**Solution:** Optimize cache clearing pattern, use cache tags

### Issue 3: Redis connection errors
**Symptom:** "Connection refused" errors in logs
**Cause:** Redis server down or misconfigured
**Solution:** Check Redis service status, verify connection settings

### Issue 4: Webhooks not triggering invalidation
**Symptom:** Cache still stale after widget bookings
**Cause:** Service lookup failing (no calcom_event_type_id)
**Solution:** Verify service configuration, check webhook payload

---

## VERIFICATION COMMANDS

```bash
# Verify fix is deployed
git log --oneline -1
# Should show: fix: Add cache invalidation...

# Verify code changes
git diff HEAD~1 app/Services/CalcomService.php | grep "clearAvailability"
# Should show new invalidation calls

# Verify tests exist
ls -la tests/Feature/CalcomCacheInvalidationTest.php

# Verify tests pass
php artisan test --filter CalcomCacheInvalidation

# Verify cache clearing works
php artisan tinker
>>> $service = new \App\Services\CalcomService();
>>> $service->clearAvailabilityCacheForEventType(2563193);
>>> exit
redis-cli KEYS "*2563193*"
# Should show no keys
```

---

## SUCCESS CRITERIA

```
✅ All unit tests pass
✅ All manual tests pass
✅ No errors in logs after 1 hour
✅ Cache invalidation logged for every booking
✅ No stale cache detections in 24 hours
✅ Performance overhead <5ms per booking
✅ Zero double booking incidents
✅ Cache hit rate remains >80%
```

---

## CONTACTS

**Developer:** See team Slack channel
**Code Review:** Tag @backend-team
**Deployment:** Use #deployments channel
**Incidents:** Create ticket in JIRA

**Documentation:**
- Full RCA: `claudedocs/CALCOM_CACHE_RCA_2025-10-11.md`
- Visual Flow: `claudedocs/CALCOM_CACHE_VISUAL_FLOW.md`
- Executive Summary: `claudedocs/CALCOM_CACHE_RCA_EXECUTIVE_SUMMARY.md`

---

**Status:** Ready for implementation
**Last Updated:** 2025-10-11
**Version:** 1.0
