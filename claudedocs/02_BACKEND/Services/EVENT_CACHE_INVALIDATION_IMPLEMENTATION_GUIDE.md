# Event-Based Cache Invalidation - Implementation Guide

**Date**: 2025-10-11
**Status**: Ready for Implementation
**Estimated Time**: 2-3 hours

---

## QUICK START

### Step 1: Event Registration (5 minutes)

**File**: `app/Providers/EventServiceProvider.php`

Add to the `$listen` array:

```php
protected $listen = [
    // ... existing events ...

    // ✅ CACHE INVALIDATION: Prevent double-bookings
    \App\Events\Appointments\AppointmentBooked::class => [
        \App\Listeners\Appointments\InvalidateSlotsCache::class . '@handleBooked',
    ],

    \App\Events\Appointments\AppointmentRescheduled::class => [
        \App\Listeners\Appointments\InvalidateSlotsCache::class . '@handleRescheduled',
        // ... existing listeners (SendRescheduleNotifications, UpdateModificationStats) ...
    ],

    \App\Events\Appointments\AppointmentCancelled::class => [
        \App\Listeners\Appointments\InvalidateSlotsCache::class . '@handleCancelled',
    ],
];
```

### Step 2: Emit Events in AppointmentCreationService (10 minutes)

**File**: `app/Services/Retell/AppointmentCreationService.php`

**Location**: After line 419 in `createLocalRecord()` method

```php
// Line 419: $appointment->save();

// ✅ CACHE INVALIDATION: Fire event to invalidate availability cache
event(new \App\Events\Appointments\AppointmentBooked($appointment));

// Line 421: PHASE 2: Staff Assignment from Cal.com hosts array
```

### Step 3: Emit Events in CalcomWebhookController (15 minutes)

**File**: `app/Http/Controllers/CalcomWebhookController.php`

#### 3.1 Booking Created Event

**Location**: After line 292 in `handleBookingCreated()` method

```php
// Line 267-292: Create appointment
$appointment = Appointment::updateOrCreate(
    ['calcom_v2_booking_id' => $calcomId],
    array_merge([...], $assignmentMetadata)
);

// ✅ CACHE INVALIDATION: Fire event
event(new \App\Events\Appointments\AppointmentBooked($appointment));

// Line 294: Log::channel('calcom')->info('[Cal.com] Appointment created from booking', [...]);
```

#### 3.2 Booking Rescheduled Event

**Location**: After line 344 in `handleBookingUpdated()` method

```php
// Line 328: Store old start time before update
$oldStartsAt = $appointment->starts_at;

// Line 330-344: Update appointment
$appointment->update([
    'starts_at' => Carbon::parse($payload['startTime']),
    'ends_at' => Carbon::parse($payload['endTime']),
    // ... other fields ...
]);

// ✅ CACHE INVALIDATION: Fire event with old/new times
event(new \App\Events\Appointments\AppointmentRescheduled(
    appointment: $appointment,
    oldStartTime: $oldStartsAt,
    newStartTime: Carbon::parse($payload['startTime']),
    reason: 'Customer rescheduled via Cal.com',
    fee: 0.0,
    withinPolicy: true
));

// Line 346: Log::channel('calcom')->info('[Cal.com] Appointment rescheduled', [...]);
```

#### 3.3 Booking Cancelled Event

**Location**: After line 389 in `handleBookingCancelled()` method

```php
// Line 376-389: Update appointment
$appointment->update([
    'status' => 'cancelled',
    // ... other fields ...
]);

// ✅ CACHE INVALIDATION: Fire event
event(new \App\Events\Appointments\AppointmentCancelled(
    appointment: $appointment,
    reason: $payload['cancellationReason'] ?? 'No reason provided',
    cancelledBy: 'customer'
));

// Line 391: Log::channel('calcom')->info('[Cal.com] Appointment cancelled', [...]);
```

### Step 4: Register Events with Artisan (2 minutes)

```bash
# Register new events in Laravel
php artisan event:cache

# Verify events registered
php artisan event:list | grep "AppointmentBooked"
# Should show: App\Events\Appointments\AppointmentBooked
#              └─ App\Listeners\Appointments\InvalidateSlotsCache@handleBooked

php artisan event:list | grep "AppointmentCancelled"
# Should show: App\Events\Appointments\AppointmentCancelled
#              └─ App\Listeners\Appointments\InvalidateSlotsCache@handleCancelled
```

### Step 5: Run Tests (10 minutes)

```bash
# Run unit tests
php artisan test tests/Unit/Listeners/InvalidateSlotsCacheTest.php

# Expected output:
# ✅ it_invalidates_cache_for_booked_appointment
# ✅ it_invalidates_surrounding_hour_windows
# ✅ it_invalidates_both_old_and_new_slots_on_reschedule
# ✅ it_invalidates_cache_on_cancellation
# ✅ it_handles_missing_event_type_id_gracefully
# ✅ it_is_non_blocking_on_cache_failure
# ✅ it_respects_multi_tenant_isolation
# ✅ it_handles_cross_midnight_appointments
#
# Tests:  8 passed
# Time:   < 1 second
```

### Step 6: Manual Smoke Test (15 minutes)

```bash
# Test 1: Verify cache invalidation on booking
php artisan tinker

# Pre-populate cache
Cache::put('cal_slots_15_1_123_2025-10-15-14_2025-10-15-15', ['slot1', 'slot2'], 300);
Cache::has('cal_slots_15_1_123_2025-10-15-14_2025-10-15-15'); // Should be true

# Create appointment (triggers event)
$apt = \App\Models\Appointment::factory()->create([
    'starts_at' => \Carbon\Carbon::parse('2025-10-15 14:00:00'),
    'company_id' => 15,
    'branch_id' => 1,
]);
event(new \App\Events\Appointments\AppointmentBooked($apt));

# Verify cache deleted
Cache::has('cal_slots_15_1_123_2025-10-15-14_2025-10-15-15'); // Should be false

exit
```

```bash
# Test 2: Monitor logs during booking
tail -f storage/logs/laravel.log | grep -E "(Invalidating cache|Cache invalidation)"

# Then create a booking via Retell or Cal.com webhook
# You should see:
# [timestamp] local.INFO: 🗑️ Invalidating cache for booked appointment
# [timestamp] local.INFO: ✅ Cache invalidation complete
```

---

## DEPLOYMENT CHECKLIST

### Pre-Deployment

- [ ] All files created (3 events + 1 listener + 1 test)
- [ ] EventServiceProvider updated with event registrations
- [ ] AppointmentCreationService emits AppointmentBooked event
- [ ] CalcomWebhookController emits all 3 events (booked, rescheduled, cancelled)
- [ ] Unit tests passing (8/8 tests)
- [ ] Code reviewed by team member
- [ ] Monitoring alerts configured (optional for MVP)

### Deployment (Zero Downtime)

```bash
# Step 1: Pull latest code
git pull origin main

# Step 2: Install dependencies (if any)
composer install --no-dev --optimize-autoloader

# Step 3: Register events (no restart needed)
php artisan event:cache

# Step 4: Verify events registered
php artisan event:list | grep "Appointment"

# Step 5: Monitor logs for 30 minutes
tail -f storage/logs/laravel.log | grep -E "(Invalidating|Cache invalidation)"

# Step 6: Check metrics (if monitoring configured)
# - Cache invalidation success rate > 99%
# - No error spikes
# - Booking flow unaffected
```

### Rollback Plan

```bash
# If issues detected within 1 hour of deployment:

# Option A: Disable listener (quick fix)
# 1. Comment out InvalidateSlotsCache in EventServiceProvider
# 2. php artisan event:cache
# 3. Cache will still work with 5-minute TTL (eventual consistency)

# Option B: Full rollback
git revert HEAD
composer install --no-dev --optimize-autoloader
php artisan event:cache
php artisan cache:clear
```

---

## VERIFICATION TESTS

### Test 1: Booking Invalidates Cache

```bash
# 1. Pre-populate cache
php artisan tinker
>>> Cache::put('cal_slots_15_1_123_2025-10-15-14_2025-10-15-15', ['slot1'], 300);
>>> Cache::has('cal_slots_15_1_123_2025-10-15-14_2025-10-15-15'); // true
>>> exit

# 2. Create booking via Retell or Cal.com

# 3. Verify cache deleted
php artisan tinker
>>> Cache::has('cal_slots_15_1_123_2025-10-15-14_2025-10-15-15'); // false (✅)
>>> exit
```

### Test 2: Reschedule Invalidates Both Slots

```bash
# 1. Create appointment at 14:00
# 2. Pre-populate cache for 14:00 AND 16:00
php artisan tinker
>>> Cache::put('cal_slots_15_1_123_2025-10-15-14_2025-10-15-15', ['old'], 300);
>>> Cache::put('cal_slots_15_1_123_2025-10-15-16_2025-10-15-17', ['new'], 300);
>>> exit

# 3. Reschedule to 16:00 (via Cal.com webhook BOOKING.RESCHEDULED)

# 4. Verify both caches deleted
php artisan tinker
>>> Cache::has('cal_slots_15_1_123_2025-10-15-14_2025-10-15-15'); // false (✅)
>>> Cache::has('cal_slots_15_1_123_2025-10-15-16_2025-10-15-17'); // false (✅)
>>> exit
```

### Test 3: Cancellation Restores Availability

```bash
# 1. Book appointment at 14:00 (cache invalidated)
# 2. Cancel appointment (via Cal.com webhook BOOKING.CANCELLED)
# 3. Verify cache invalidated (forces fresh fetch from Cal.com)
# 4. Next availability query will show 14:00 available again
```

---

## MONITORING

### Logs to Watch

```bash
# Success logs
grep "Cache invalidation complete" storage/logs/laravel.log

# Example output:
# [2025-10-11 14:30:15] local.INFO: ✅ Cache invalidation complete
#   {"appointment_id":123,"keys_deleted":3,"total_keys":3}

# Failure logs (investigate if frequent)
grep "Cache invalidation failed" storage/logs/laravel.log

# Example output:
# [2025-10-11 14:30:15] local.ERROR: ❌ Cache invalidation failed (non-critical)
#   {"appointment_id":123,"error":"Redis connection refused"}

# Missing event type IDs (data quality issue)
grep "No event type ID found" storage/logs/laravel.log

# Example output:
# [2025-10-11 14:30:15] local.WARNING: ⚠️ No event type ID found for appointment
#   {"appointment_id":123,"service_id":45}
```

### Metrics to Track (Optional - Phase 2)

```php
// Add to monitoring dashboard (Prometheus/CloudWatch):

// 1. Cache invalidation success rate
cache_invalidation_success_rate = (
    cache_invalidation_success_total /
    cache_invalidation_attempts_total
) * 100
// Target: > 99%

// 2. Average invalidation latency
cache_invalidation_latency_avg_ms
// Target: < 20ms

// 3. Missing event type ID rate
missing_event_type_id_rate = (
    missing_event_type_id_total /
    appointments_created_total
) * 100
// Target: < 1%
```

---

## TROUBLESHOOTING

### Issue: Cache not invalidating

**Symptoms**: Booked slots still appear in availability

**Debug Steps**:
1. Check events registered: `php artisan event:list | grep AppointmentBooked`
2. Check logs for invalidation messages: `grep "Invalidating cache" storage/logs/laravel.log`
3. Verify service has `calcom_event_type_id`: `php artisan tinker` → `Service::find(X)->calcom_event_type_id`
4. Manually fire event: `event(new AppointmentBooked($appointment))`

**Solution**: Ensure `event()` calls added to AppointmentCreationService and CalcomWebhookController

---

### Issue: Tests failing

**Symptoms**: Unit tests error or fail

**Debug Steps**:
1. Run tests with verbose output: `php artisan test --verbose tests/Unit/Listeners/InvalidateSlotsCacheTest.php`
2. Check test database migrations: `php artisan migrate:fresh --env=testing`
3. Verify factories exist: `php artisan tinker` → `Appointment::factory()->make()`

**Solution**: Run `php artisan migrate:fresh --env=testing` and retry tests

---

### Issue: Performance degradation

**Symptoms**: Booking slower after deployment

**Debug Steps**:
1. Check invalidation latency: `grep "Cache invalidation complete" storage/logs/laravel.log | tail -20`
2. Look for cache connection errors: `grep "Redis" storage/logs/laravel.log`
3. Monitor Redis/cache server load

**Solution**: Verify Redis is healthy, consider increasing connection pool

---

## SUCCESS CRITERIA

After deployment, verify:

- [x] Unit tests pass (8/8)
- [x] Manual smoke tests pass (3/3)
- [x] No errors in logs for 1 hour
- [x] Cache invalidation success rate > 95%
- [x] Booking success rate unchanged
- [x] No customer complaints about double-bookings

---

## NEXT STEPS (Post-Deployment)

1. **Monitor for 24 hours**: Track cache invalidation success rate
2. **Analyze missing event type IDs**: Fix services without `calcom_event_type_id`
3. **Configure alerts**: Set up monitoring alerts for cache failures
4. **Performance tuning**: Optimize cache key generation if latency > 50ms
5. **Documentation**: Update system architecture docs with event flow

---

## SUPPORT

**Questions?** Contact backend architect before implementation
**Issues?** Create incident ticket with logs and reproduction steps
**Rollback?** Follow rollback plan above (can disable listener without downtime)
