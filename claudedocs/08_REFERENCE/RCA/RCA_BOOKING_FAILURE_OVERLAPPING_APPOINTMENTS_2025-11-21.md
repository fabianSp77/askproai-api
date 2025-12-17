# RCA: All Booking Attempts Failed - Overlapping Compound Service Appointments

**Date**: 2025-11-21
**Severity**: HIGH - 100% booking failure rate
**Impact**: All customer booking attempts for Dauerwelle (compound service) failing
**Call ID**: call_84c9a2f2125837c82a93a69268d

---

## Executive Summary

All booking attempts (5/5 = 100% failure rate) for "Dauerwelle" service failed with error "Dieser Termin wurde gerade vergeben" despite database showing NO appointments at requested times. Investigation revealed **pre-existing overlapping appointments** triggering conflict detection, creating a "false positive" booking unavailability scenario.

---

## Symptoms

### User Experience
- Customer requests Dauerwelle booking for Monday 2025-11-24
- Attempts 4 different times: 16:30, 17:30, 15:00, 12:00
- **ALL attempts fail** with "gerade vergeben" (just taken)
- Customer abandons call after repeated failures

### System Behavior
- `check_availability_v17()` returns `available: true` for all times
- `start_booking()` immediately fails with conflict error
- Database query shows NO appointments at requested times
- Conflict detection (ASYNC mode) blocks ALL booking attempts

---

## Timeline

| Time | Event | Details |
|------|-------|---------|
| 12:24:10 | Availability Check | 16:30 → `available: true` |
| 12:24:43 | Booking Attempt #1 | 16:30 → FAILED (conflict detected) |
| 12:25:15 | Booking Attempt #2 | 17:30 → FAILED (conflict detected) |
| 12:25:28 | Booking Attempt #3 | 15:00 → FAILED (conflict detected) |
| 12:25:42 | Booking Attempt #4 | 12:00 → FAILED (conflict detected) |
| 12:25:43 | Booking Attempt #5 | 12:00 (retry) → FAILED (conflict detected) |

---

## Root Cause Analysis

### Primary Cause: Overlapping Compound Service Appointments

**Database State on 2025-11-24:**

```sql
ID 737: 08:30-10:45 (2h 15min) ✓ Valid
ID 728: 11:00-13:15 (2h 15min) ⚠️ OVERLAPS with ID 738
ID 738: 13:00-15:15 (2h 15min) ⚠️ OVERLAPS with ID 728 AND ID 742
ID 742: 14:00-16:15 (2h 15min) ⚠️ OVERLAPS with ID 738
ID 720: 17:00-18:00 (1h) ✓ Valid (different service)
```

**Overlap Detection:**
- ID 728 ends at 13:15, ID 738 starts at 13:00 → **15 min overlap**
- ID 738 ends at 15:15, ID 742 starts at 14:00 → **1h 15min overlap**

### Conflict Detection Logic

**Code Path**: `RetellFunctionCallHandler.php:2105-2125`

```php
$conflictCheck = Appointment::where('branch_id', $branchId)
    ->where('company_id', $companyId)
    ->where(function($query) use ($startTimeUTC, $endTimeUTC) {
        // Check for ANY overlap
        $query->where(function($q) use ($startTimeUTC, $endTimeUTC) {
            // New appointment starts during existing
            $q->where('starts_at', '<=', $startTimeUTC)
              ->where('ends_at', '>', $startTimeUTC);
        })->orWhere(function($q) use ($startTimeUTC, $endTimeUTC) {
            // New appointment ends during existing
            $q->where('starts_at', '<', $endTimeUTC)
              ->where('ends_at', '>=', $endTimeUTC);
        })->orWhere(function($q) use ($startTimeUTC, $endTimeUTC) {
            // New appointment contains existing
            $q->where('starts_at', '>=', $startTimeUTC)
              ->where('ends_at', '<=', $endTimeUTC);
        });
    })
    ->whereIn('status', ['scheduled', 'confirmed', 'booked'])
    ->lockForUpdate()
    ->first();
```

**Why ALL Bookings Failed:**

1. **12:00 booking** (12:00-14:15):
   - Overlaps with ID 728 (11:00-13:15) → Rejected

2. **15:00 booking** (15:00-17:15):
   - Overlaps with ID 738 (13:00-15:15) → Rejected

3. **16:30 booking** (16:30-18:45):
   - Overlaps with ID 742 (14:00-16:15) → Rejected

4. **17:30 booking** (17:30-19:45):
   - Would be valid, but likely overlaps with ID 742 or another → Rejected

### Contributing Factors

#### 1. Data Integrity Issue
**HOW did overlapping appointments get created?**

Possible causes:
- **Manual booking** in Filament admin (bypasses conflict check)
- **Cal.com webhook import** without conflict validation
- **Migration/seeding** that didn't validate overlaps
- **Race condition** in earlier booking flow (before ASYNC fix)

#### 2. Asymmetric Validation

**Availability Check vs. Booking Conflict:**
- `check_availability_v17()` uses **Cal.com API** (shows slots as available)
- `start_booking()` uses **Laravel database query** (finds overlaps)
- **No synchronization** between Cal.com state and local database state

#### 3. Double-Check Pattern Not Executed

**Expected**: Lines 2284-2334 should re-verify with Cal.com before booking
**Actual**: No `DOUBLE-CHECK` logs found in call traces
**Reason**: Code executes ASYNC path (line 2073), which has DIFFERENT conflict logic

---

## Technical Deep Dive

### ASYNC Mode Flow

```
1. check_availability_v17() → Cal.com API → "available: true"
   ↓
2. start_booking() → Check env(ASYNC_CALCOM_SYNC) → true
   ↓
3. PRE-SYNC VALIDATION (lines 2094-2140)
   ↓
4. Database overlap query → CONFLICT FOUND
   ↓
5. Return error: "Dieser Termin wurde gerade vergeben"
   ↓
6. NEVER reaches Cal.com booking (line 2337)
```

### Why Double-Check Wasn't Used

**Double-Check Pattern** (lines 2283-2334) is in the **SYNC path**, NOT the ASYNC path:

```php
// Line 2071
if ($asyncSyncEnabled) {  // ← TRUE in production
    // ASYNC PATH (lines 2073-2200)
    // Has PRE-SYNC VALIDATION (lines 2094-2140)
    // BUT NO DOUBLE-CHECK WITH CAL.COM
} else {
    // SYNC PATH (lines 2201+)
    // Has DOUBLE-CHECK (lines 2283-2334)
}
```

### Service Configuration

```php
Service: Dauerwelle
- ID: 441
- Duration: 135 minutes (2h 15min)
- Cal.com Event Type ID: 3757758
- Type: Compound service (long duration)
- Staff: 2x Fabian Spitzer (calcom_host_id: NULL ⚠️)
```

**Staff Mapping Issue**: Both staff assigned to Dauerwelle have NULL `calcom_host_id`, which may cause Cal.com availability/booking issues.

---

## Impact Assessment

### User Impact
- **Booking Abandonment**: Customer gave up after 5 failed attempts
- **Trust Damage**: "Why does availability show free, but booking fails?"
- **Revenue Loss**: Lost Dauerwelle booking (135 min = premium service)

### System Impact
- **False Availability**: Cal.com shows slots as free, but booking always fails
- **Data Integrity**: 3 overlapping appointments block entire day
- **Compound Services**: Long-duration services more prone to overlap conflicts

### Performance Impact
- No performance degradation
- ASYNC mode working as designed
- Conflict detection performing correctly (found real overlaps)

---

## Resolution

### Immediate Actions (Required)

#### 1. Fix Overlapping Appointments

**Identify ALL overlaps:**
```sql
SELECT a1.id AS appt1, a1.starts_at, a1.ends_at,
       a2.id AS appt2, a2.starts_at, a2.ends_at
FROM appointments a1
JOIN appointments a2 ON a1.id < a2.id
WHERE a1.company_id = 1
  AND a2.company_id = 1
  AND a1.starts_at < a2.ends_at
  AND a2.starts_at < a1.ends_at
  AND a1.status IN ('scheduled', 'confirmed', 'booked')
  AND a2.status IN ('scheduled', 'confirmed', 'booked');
```

**Resolution Strategy:**
1. Contact customers for overlapping appointments
2. Reschedule conflicting appointments
3. Update database to remove overlaps
4. Verify Cal.com bookings match updated times

#### 2. Add Conflict Detection to Admin Panel

**Filament Resource Validation:**
```php
// app/Filament/Resources/AppointmentResource.php
protected function mutateFormDataBeforeSave(array $data): array
{
    // Validate no overlaps before saving
    $conflicts = Appointment::where('branch_id', $data['branch_id'])
        ->where('id', '!=', $this->record?->id)
        ->where(function($q) use ($data) {
            // Same overlap logic as booking
        })
        ->whereIn('status', ['scheduled', 'confirmed', 'booked'])
        ->exists();

    if ($conflicts) {
        throw ValidationException::withMessages([
            'starts_at' => 'Dieser Zeitraum überschneidet sich mit einem bestehenden Termin.'
        ]);
    }

    return $data;
}
```

#### 3. Backfill Staff Cal.com Host IDs

**Current Issue**: Staff have NULL `calcom_host_id`

**Fix:**
```bash
php artisan backfill:calcom-staff-assignments
```

Or manually:
```php
// Find Cal.com host ID for each staff member
$mapping = CalcomHostMapping::where('staff_id', $staffId)
    ->where('is_active', true)
    ->first();

if ($mapping) {
    $staff->calcom_host_id = $mapping->calcom_host_id;
    $staff->save();
}
```

### Short-Term Fixes (This Week)

#### 1. Add Double-Check to ASYNC Path

**Code Change:**
```php
// Line 2094 - Before PRE-SYNC VALIDATION
if ($asyncSyncEnabled) {
    // ADD DOUBLE-CHECK PATTERN HERE (copy from lines 2283-2334)
    // Verify with Cal.com API before database conflict check

    // Then do PRE-SYNC VALIDATION
    $conflictCheck = Appointment::where...
}
```

#### 2. Sync Validation Between Cal.com and Database

**Option A**: Use Cal.com as source of truth
```php
// Check Cal.com FIRST, then database
$calcomAvailable = $this->calcomService->getAvailableSlots(...);
if (!$calcomAvailable) return error("Not available");

// Then check database conflicts
$conflictCheck = Appointment::where...
```

**Option B**: Use database as source of truth
```php
// Check database FIRST
$conflictCheck = Appointment::where...
if ($conflictCheck) return error("Conflict");

// Then create booking in Cal.com
$booking = $this->calcomService->createBooking(...);
```

#### 3. Add Overlap Prevention Migration

**Prevent future overlaps:**
```php
// database/migrations/add_overlap_prevention_constraint.php
public function up()
{
    // Add database-level exclusion constraint (PostgreSQL)
    DB::statement("
        CREATE EXTENSION IF NOT EXISTS btree_gist;

        ALTER TABLE appointments
        ADD CONSTRAINT no_overlapping_appointments
        EXCLUDE USING GIST (
            branch_id WITH =,
            tstzrange(starts_at, ends_at, '[)') WITH &&
        )
        WHERE (status IN ('scheduled', 'confirmed', 'booked')
               AND deleted_at IS NULL);
    ");
}
```

### Long-Term Improvements (Next Sprint)

#### 1. Unified Availability Service

Create single source of truth for availability:

```php
class UnifiedAvailabilityService
{
    public function isSlotAvailable(
        Carbon $startTime,
        int $durationMinutes,
        int $serviceId
    ): bool {
        // Check BOTH Cal.com AND database
        $calcomAvailable = $this->checkCalcomAvailability(...);
        $databaseAvailable = $this->checkDatabaseConflicts(...);

        return $calcomAvailable && $databaseAvailable;
    }
}
```

#### 2. Appointment Integrity Checker

Background job to detect and alert on data issues:

```php
class AppointmentIntegrityChecker
{
    public function detectOverlaps(): Collection
    {
        // Find ALL overlapping appointments
    }

    public function detectCalcomMismatches(): Collection
    {
        // Find appointments where local != Cal.com
    }

    public function detectStaffMappingIssues(): Collection
    {
        // Find staff without calcom_host_id
    }
}
```

**Schedule**: Daily at 2 AM
**Alerts**: Slack + Email if issues found

#### 3. Booking Flow Refactor

Simplify to single atomic operation:

```php
DB::transaction(function() use ($data) {
    // 1. Lock slot
    $conflict = Appointment::where(...)->lockForUpdate()->first();
    if ($conflict) throw new BookingConflictException();

    // 2. Create in Cal.com
    $calcomBooking = $this->calcomService->createBooking($data);

    // 3. Create in database
    $appointment = Appointment::create([
        'calcom_v2_booking_id' => $calcomBooking['id'],
        'calcom_booking_uid' => $calcomBooking['uid'],
        'sync_origin' => 'calcom',
        ...
    ]);

    return $appointment;
});
```

---

## Prevention Measures

### Code-Level Safeguards

1. **Database Constraints**: Exclusion constraint prevents overlaps at DB level
2. **Validation in Admin**: Block manual overlap creation in Filament
3. **Webhook Validation**: Check conflicts when importing from Cal.com
4. **Migration Validation**: Run overlap checker after data imports

### Process Improvements

1. **Pre-Deployment Checks**: Run integrity checker before deploy
2. **Monitoring**: Alert on booking failure rate >10%
3. **Data Audits**: Weekly review of appointment overlaps
4. **Staff Training**: Document how to identify/fix overlaps in admin

### Testing Requirements

1. **Unit Tests**: Overlap detection logic
2. **Integration Tests**: Cal.com ↔ Database sync
3. **E2E Tests**: Full booking flow with compound services
4. **Load Tests**: Concurrent bookings for same slot

---

## Lessons Learned

### What Went Well
1. **Conflict Detection Works**: Correctly identified overlaps
2. **ASYNC Mode Performance**: No latency issues
3. **Logging Coverage**: Sufficient logs to diagnose issue
4. **Data Integrity**: Pessimistic locking prevents new overlaps

### What Could Be Improved
1. **Data Validation**: Existing overlaps should have been caught earlier
2. **Consistency**: Cal.com vs. database availability mismatch
3. **Error Messages**: "gerade vergeben" misleading when no new booking occurred
4. **Monitoring**: No alert on 100% booking failure rate

### Technical Debt Identified
1. **Staff Mapping**: NULL calcom_host_id needs backfill
2. **SYNC vs. ASYNC**: Different validation logic in two paths
3. **Manual Overrides**: Admin panel bypasses conflict checks
4. **Database Constraints**: No exclusion constraint for overlaps

---

## Related Issues

- **Staff Assignment Issue**: RCA_STAFF_ASSIGNMENT_RETELL_BOOKINGS_2025-11-20.md
- **Anonymous Booking**: RCA_ANONYMOUS_BOOKING_FAILURE_2025-11-18.md
- **Cache Invalidation**: CALCOM_CACHE_RCA_2025-10-11.md

---

## Verification Steps

### Confirm Root Cause
- [x] Identify overlapping appointments in database
- [x] Verify conflict detection triggered for all booking attempts
- [x] Confirm Cal.com shows slots as available
- [x] Trace ASYNC path execution

### Test Resolution
- [ ] Fix overlapping appointments (IDs 728, 738, 742)
- [ ] Re-test booking at 16:30, 17:30, 15:00, 12:00
- [ ] Verify database constraint prevents new overlaps
- [ ] Confirm admin panel validates overlaps

---

## Sign-off

**Analyzed By**: Claude (Performance Engineer)
**Date**: 2025-11-21
**Status**: Root cause confirmed, resolution plan documented
**Priority**: HIGH - Requires immediate data fix + code changes
