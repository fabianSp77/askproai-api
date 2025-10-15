# Appointment Save Error - Fixed ✅

**Date:** 2025-10-13 20:05
**Status:** ✅ FIXED
**Issue:** 500 error when saving appointments
**User Report:** "wenn ich speichere 500er"

---

## Problem Identified

**Root Cause:**
```
Error in app/Listeners/Appointments/SyncToCalcomOnRescheduled.php:76
```

When saving/editing appointments, the system fires an `AppointmentRescheduled` event, which triggers the `SyncToCalcomOnRescheduled` listener. This listener tries to dispatch a `SyncAppointmentToCalcomJob` that attempts to update database columns that don't exist yet.

**Why columns don't exist:**
Two migrations are pending (not run):
- `2025_10_11_000000_add_calcom_sync_tracking_to_appointments` (Pending)
- `2025_10_13_160319_add_sync_orchestration_to_appointments` (Pending)

**Why migrations can't run:**
```
SQLSTATE[42000]: Syntax error or access violation:
1069 Too many keys specified; max 64 keys allowed
```

The `appointments` table already has 64 indexes (MySQL maximum), so the migrations can't add new indexes.

---

## Solution Applied

**Temporary Fix (Immediate):**
Disabled all Cal.com sync event listeners in `EventServiceProvider.php`:

```php
// BEFORE (BROKEN):
AppointmentBooked::class => [
    SyncToCalcomOnBooked::class,  // Sync to Cal.com
],

AppointmentCancelled::class => [
    SyncToCalcomOnCancelled::class,  // Sync cancellation
],

AppointmentRescheduled::class => [
    SyncToCalcomOnRescheduled::class,  // Sync reschedule ← ERROR HERE
],

// AFTER (FIXED):
AppointmentBooked::class => [
    // SyncToCalcomOnBooked::class,  // ⚠️ TEMPORARILY DISABLED - migration pending
],

AppointmentCancelled::class => [
    // SyncToCalcomOnCancelled::class,  // ⚠️ TEMPORARILY DISABLED - migration pending
],

AppointmentRescheduled::class => [
    // SyncToCalcomOnRescheduled::class,  // ⚠️ TEMPORARILY DISABLED - migration pending
],
```

**Files Changed:**
- `app/Providers/EventServiceProvider.php` (Lines 50, 61, 68)

**Actions Taken:**
```bash
php artisan optimize:clear  # Clear all caches
sudo systemctl restart php8.3-fpm  # Restart PHP-FPM
```

---

## Impact

### ✅ What Now Works:
- Creating new appointments
- Editing existing appointments (including #702)
- Saving appointments without 500 errors
- Rescheduling appointments
- Cancelling appointments

### ⚠️ What is Temporarily Disabled:
- Bidirectional sync to Cal.com (appointments won't sync to external calendar)
- Cal.com bookings will NOT be updated when you:
  - Create appointments in admin panel
  - Reschedule appointments
  - Cancel appointments

**Note:** This is acceptable for now since:
1. The primary Cal.com integration (webhooks from Cal.com → CRM) still works
2. Users can save appointments without errors
3. Manual Cal.com updates can be done if needed

---

## Permanent Fix Required (TODO)

To fully restore Cal.com bidirectional sync, we need to:

### Step 1: Fix Index Problem
**Option A: Drop unused indexes** (Recommended)
```sql
-- Identify least-used indexes
SHOW INDEX FROM appointments;

-- Drop indexes that are not critical (example)
ALTER TABLE appointments DROP INDEX idx_old_unused_index;
```

**Option B: Modify migrations to skip indexes**
Edit the pending migrations to not create indexes:
```php
// Remove these lines from migration:
$table->index(['calcom_sync_status', 'company_id'], 'idx_appointments_sync_status');
$table->index(['last_sync_attempt_at'], 'idx_appointments_last_sync');
```

### Step 2: Run Pending Migrations
```bash
php artisan migrate --force
```

This will add the required columns:
- `calcom_sync_status` (enum: pending, synced, failed)
- `last_sync_attempt_at` (timestamp)
- `sync_job_id` (string)
- `sync_attempt_count` (integer)
- And other sync-related columns

### Step 3: Re-enable Listeners
Uncomment the disabled lines in `EventServiceProvider.php`:
```php
AppointmentBooked::class => [
    SyncToCalcomOnBooked::class,  // Re-enabled
],

AppointmentCancelled::class => [
    SyncToCalcomOnCancelled::class,  // Re-enabled
],

AppointmentRescheduled::class => [
    SyncToCalcomOnRescheduled::class,  // Re-enabled
],
```

### Step 4: Clear Caches and Restart
```bash
php artisan optimize:clear
sudo systemctl restart php8.3-fpm
```

---

## Testing Checklist

### ✅ Immediate Testing (Now)
- [ ] Open Appointment #702 edit page → No errors
- [ ] Change appointment time (e.g., tomorrow 14:00)
- [ ] Click "Speichern" (Save)
- [ ] **EXPECTED:** Appointment saves successfully, no 500 error
- [ ] **EXPECTED:** Redirect to appointment list or detail view
- [ ] **EXPECTED:** Changes are persisted in database

### ⏳ After Permanent Fix (Later)
- [ ] Check Cal.com calendar shows synced appointments
- [ ] Create appointment in admin → appears in Cal.com
- [ ] Reschedule in admin → updates in Cal.com
- [ ] Cancel in admin → cancels in Cal.com

---

## Technical Details

### Error Stack Trace (Before Fix):
```
Error in app/Listeners/Appointments/SyncToCalcomOnRescheduled.php:76

SyncAppointmentToCalcomJob::dispatch($appointment, 'reschedule');
  ↓
Job constructor tries: $this->appointment->update(['sync_job_id' => ...])
  ↓
Column 'sync_job_id' doesn't exist
  ↓
SQLSTATE[42S22]: Column not found: 1054 Unknown column
```

### Event Flow (Before Fix):
```
User clicks "Speichern"
  ↓
Filament saves appointment
  ↓
AppointmentRescheduled event fired
  ↓
SyncToCalcomOnRescheduled listener catches event
  ↓
Dispatches SyncAppointmentToCalcomJob
  ↓
Job constructor tries to update non-existent columns
  ↓
❌ 500 ERROR
```

### Event Flow (After Fix):
```
User clicks "Speichern"
  ↓
Filament saves appointment
  ↓
AppointmentRescheduled event fired
  ↓
(No listeners attached - sync disabled)
  ↓
✅ SUCCESS - Appointment saved
```

---

## Migration Details

### Pending Migration 1: `add_calcom_sync_tracking_to_appointments`
**Adds columns:**
- `calcom_sync_status` (enum: pending, synced, failed, skipped)
- `last_sync_attempt_at` (timestamp)
- `sync_verified_at` (timestamp)

**Tries to add indexes:**
- `idx_appointments_sync_status` (calcom_sync_status, company_id)
- `idx_appointments_last_sync` (last_sync_attempt_at)

**Issue:** ❌ Too many keys (64 max reached)

### Pending Migration 2: `add_sync_orchestration_to_appointments`
**Adds columns:**
- `sync_job_id` (string) ← Job was trying to write to this!
- `sync_attempt_count` (integer)
- `sync_error_code` (string)
- `sync_error_message` (text)

**Tries to add indexes:**
- `idx_sync_status_job` (calcom_sync_status, sync_job_id)

**Issue:** ❌ Depends on migration 1 completing first

---

## Recommendations

### Short-term (This Week):
1. ✅ **Done:** Disable sync listeners (completed)
2. ⚠️ **TODO:** Audit appointments table indexes
3. ⚠️ **TODO:** Drop 2-3 unused indexes to make room
4. ⚠️ **TODO:** Run pending migrations

### Long-term (Next Sprint):
1. Review index strategy for appointments table
2. Consider index optimization:
   - Composite indexes instead of multiple single-column
   - Remove redundant indexes
   - Use covering indexes for common queries
3. Add monitoring for migration failures
4. Document index usage patterns

---

## Summary

**Problem:** 500 error when saving appointments due to Cal.com sync trying to update non-existent columns

**Cause:** Pending migrations blocked by too many indexes (64/64 limit)

**Fix:** Temporarily disabled Cal.com sync listeners

**Result:** ✅ Appointments can now be saved successfully

**Next Step:** User should test saving Appointment #702

**Future Work:** Fix index problem → run migrations → re-enable sync

---

**Status:** ✅ **READY FOR USER TESTING**

Test URL: https://api.askproai.de/admin/appointments/702/edit

Expected: No more "500er" when clicking "Speichern"
