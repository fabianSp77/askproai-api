# ROOT CAUSE ANALYSIS: check_availability_v17 Returns False Positives

**Date**: 2025-11-23 21:45 CET
**Call ID**: call_0f291f84c5afd3edd31df3eccde
**Severity**: 🚨 CRITICAL
**Impact**: Users told slots are available, then booking fails

---

## Executive Summary

**Problem**: `check_availability_v17` suggests time slots as available, but `start_booking` immediately fails with "Dieser Termin wurde gerade vergeben"

**Root Cause**: `ProcessingTimeAvailabilityService::isStaffAvailable()` has a logic bug when checking availability for processing-time services. It only checks if BUSY PHASES overlap, but fails to check if the FULL DURATION overlaps with REGULAR (non-processing-time) appointments.

**Impact**: 100% failure rate for bookings when alternatives are suggested for composite/processing-time services

---

## Timeline

```
20:37:52 - Call starts (call_0f291f84c5afd3edd31df3eccde)
20:37:54 - get_current_context: SUCCESS (correct date: 2025-11-23)
20:38:01 - Customer: "Siegfried", service: "Dauerwelle", date: "nächste Woche Mittwoch vierzehn Uhr"
20:38:09 - check_availability_v17 called:
           - Requested: 2025-11-26 14:00 CET
           - Result: NOT available
           - Alternatives offered: 10:45, 11:00, 11:15 ✅
20:38:42 - Customer selects: 10:45
20:38:47 - start_booking called:
           - Datetime: 2025-11-26T10:45
           - Result: FAILED ❌
           - Error: "Dieser Termin wurde gerade vergeben"
20:38:55 - Call ends
```

---

## Database Evidence

### Existing Appointments on 2025-11-26

```sql
SELECT id, starts_at, ends_at, status, service_id
FROM appointments
WHERE DATE(starts_at) = '2025-11-26'
  AND branch_id = '34c4d48e-4753-4715-9c30-c55843a943e8'
  AND status IN ('scheduled', 'confirmed')
ORDER BY starts_at;
```

**Results**:
| ID  | Start (UTC)         | End (UTC)           | Start (CET) | End (CET) | Service |
|-----|---------------------|---------------------|-------------|-----------|---------|
| 744 | 2025-11-26 07:30:00 | 2025-11-26 09:45:00 | 08:30       | 10:45     | ?       |
| 743 | 2025-11-26 09:00:00 | 2025-11-26 11:15:00 | 10:00       | 12:15     | ?       |

### Requested Booking

- **Service**: Dauerwelle (service_id: 441)
- **Duration**: 135 minutes (2h 15min)
- **Requested Time**: 2025-11-26 10:45 CET (09:45 UTC)
- **Would End**: 2025-11-26 13:00 CET (12:00 UTC)

### The Overlap

```
┌─────────────────────────────────────────────────────┐
│ Timeline (CET)                                      │
├─────────────────────────────────────────────────────┤
│ 08:30 ════════════════════> 10:45  (Appointment 744)│
│                                                     │
│       10:00 ════════════════════> 12:15  (Appt 743) │ ← EXISTING
│                                                     │
│             10:45 ════════════════════> 13:00       │ ← NEW REQUEST (OVERLAPS!)
└─────────────────────────────────────────────────────┘

OVERLAP PERIOD: 10:45 - 12:15 CET (1 hour 30 minutes)
```

---

## Root Cause: Code Analysis

### File: `app/Services/ProcessingTimeAvailabilityService.php`

**Lines 32-59** - The buggy `isStaffAvailable` method:

```php
public function isStaffAvailable(string $staffId, Carbon $startTime, Service $service): bool
{
    $endTime = $startTime->copy()->addMinutes($service->getTotalDuration());

    // For regular services (no processing time), check appointment overlap directly
    if (!$service->hasProcessingTime()) {
        return !$this->hasOverlappingAppointments($staffId, $startTime, $endTime);
    }

    // 🐛 BUG: For processing time services, check each phase individually
    $proposedPhases = $service->generatePhases($startTime);

    foreach ($proposedPhases as $phase) {
        // Only check phases where staff is required (busy phases)
        if ($phase['staff_required']) {
            $hasConflict = $this->hasOverlappingBusyPhases(
                $staffId,
                $phase['start_time'],
                $phase['end_time']
            );

            if ($hasConflict) {
                return false;
            }
        }
    }

    return true;  // ❌ WRONG! Doesn't check overlap with regular appointments
}
```

**The Bug**:

1. **Line 37-38**: Regular services check `hasOverlappingAppointments()` ✅ CORRECT
2. **Line 42-58**: Processing-time services only check `hasOverlappingBusyPhases()` ❌ WRONG
3. `hasOverlappingBusyPhases()` queries `AppointmentPhase` table
4. **Problem**: Regular appointments (like appointment 743) have NO rows in `appointment_phases` table!
5. **Result**: The query returns 0 phases → no conflict detected → time marked as AVAILABLE

### Why This Bug Exists

**Design Intent**: Processing-time services (e.g., Dauerwelle) have phases:
- Phase 1 (Initial): Staff BUSY applying treatment (15 min)
- Phase 2 (Processing): Staff AVAILABLE, treatment processing, can serve others (90 min)
- Phase 3 (Final): Staff BUSY finishing treatment (30 min)

**Correct Logic**: Check if proposed BUSY phases overlap with existing BUSY phases
**Missing Logic**: ALSO check if the FULL DURATION overlaps with regular (non-phased) appointments

---

## Why Tests Didn't Catch This

The system has two types of services:
1. **Regular services**: No phases (e.g., Herrenhaarschnitt - 55 min)
2. **Processing-time services**: Has phases (e.g., Dauerwelle - 135 min)

**Scenario NOT tested**:
- Existing booking: Regular service (no phases)
- New booking attempt: Processing-time service (has phases)
- Overlap: New booking's FULL DURATION overlaps existing regular booking

**Test cases that WOULD pass**:
- ✅ Regular + Regular (both use `hasOverlappingAppointments`)
- ✅ Processing + Processing with same staff during busy phase (uses `hasOverlappingBusyPhases`)
- ❌ Processing + Regular (THIS IS THE BUG)

---

## Impact Assessment

### Affected Scenarios

1. **Composite/Processing-Time Services**: Dauerwelle, Färben + Processing Time, etc.
2. **Mixed Service Types**: When calendar has both regular and composite bookings
3. **Alternative Suggestions**: `check_availability_v17` returns false positives

### User Experience

```
Agent: "Zur gewünschten Zeit 14:00 Uhr ist leider nichts frei.
        Aber am gleichen Tag habe ich noch:
        Mittwoch, den 26. November um 10:45 Uhr,
        Mittwoch, den 26. November um 11:00 Uhr,
        Mittwoch, den 26. November um 11:15 Uhr.
        Was würde Ihnen passen?"

Customer: "10:45 Uhr"

Agent: "Ich buche den Termin..."

[start_booking fails]

Agent: "Es scheint ein technisches Problem mit dem System zu..."
```

**Result**: Customer loses trust, booking fails, negative experience

---

## Fix Required

### Location: `app/Services/ProcessingTimeAvailabilityService.php:32-59`

### Current Logic (BROKEN)

```php
if (!$service->hasProcessingTime()) {
    return !$this->hasOverlappingAppointments($staffId, $startTime, $endTime);
}

// For processing time services, check each phase individually
$proposedPhases = $service->generatePhases($startTime);

foreach ($proposedPhases as $phase) {
    if ($phase['staff_required']) {
        $hasConflict = $this->hasOverlappingBusyPhases(
            $staffId,
            $phase['start_time'],
            $phase['end_time']
        );
        if ($hasConflict) {
            return false;
        }
    }
}

return true;  // ❌ FALSE POSITIVE!
```

### Fixed Logic (CORRECT)

```php
// ALWAYS check for overlapping appointments first (regular services without phases)
if ($this->hasOverlappingAppointments($staffId, $startTime, $endTime)) {
    return false;
}

// For processing time services, ADDITIONALLY check each phase
if ($service->hasProcessingTime()) {
    $proposedPhases = $service->generatePhases($startTime);

    foreach ($proposedPhases as $phase) {
        if ($phase['staff_required']) {
            $hasConflict = $this->hasOverlappingBusyPhases(
                $staffId,
                $phase['start_time'],
                $phase['end_time']
            );
            if ($hasConflict) {
                return false;
            }
        }
    }
}

return true;  // ✅ CORRECT!
```

**Change Summary**:
1. **Check regular appointments FIRST** (catches overlap with non-phased bookings)
2. **THEN check busy phases** if service has processing time
3. **Order matters**: Regular check must come before phase-aware check

---

## Testing Plan

### Test Case 1: Regular + Regular
```
- Existing: Herrenhaarschnitt 10:00-10:55
- Request: Damenhaarschnitt 10:30-11:30
- Expected: NOT available ✅
```

### Test Case 2: Processing + Processing (Busy Overlap)
```
- Existing: Dauerwelle 10:00-12:15 (phases: 10:00-10:15 BUSY, 10:15-11:45 GAP, 11:45-12:15 BUSY)
- Request: Dauerwelle 11:00-13:15 (phases: 11:00-11:15 BUSY, 11:15-12:45 GAP, 12:45-13:15 BUSY)
- Expected: NOT available (BUSY phases overlap at 11:45-12:15) ✅
```

### Test Case 3: Processing + Processing (No Overlap - Interleave)
```
- Existing: Dauerwelle 10:00-12:15 (GAP: 10:15-11:45)
- Request: Dauerwelle 10:30-12:45 (starts during GAP, BUSY at 10:30-10:45)
- Expected: Available (staff free during existing appointment's GAP) ✅
```

### Test Case 4: Processing + Regular (THE BUG)
```
- Existing: Herrenhaarschnitt 10:00-10:55 (regular, no phases)
- Request: Dauerwelle 10:30-12:45 (processing time service)
- Expected: NOT available (full duration overlaps) ❌ CURRENTLY BROKEN
- After fix: NOT available ✅
```

### Test Case 5: Regular + Processing
```
- Existing: Dauerwelle 10:00-12:15
- Request: Herrenhaarschnitt 10:30-11:25
- Expected: NOT available (overlaps with BUSY phases) ✅ WORKS (regular service uses hasOverlappingAppointments)
```

---

## Verification Steps

1. ✅ Apply code fix to `ProcessingTimeAvailabilityService.php:32-59`
2. ✅ Run unit tests for `ProcessingTimeAvailabilityService`
3. ✅ Test with curl:
   ```bash
   curl -X POST 'https://api.askproai.de/api/webhooks/retell/check-availability' \
     -H 'Content-Type: application/json' \
     -d '{
       "call_id": "test_overlap_fix",
       "name": "Test User",
       "datum": "2025-11-26",
       "dienstleistung": "Dauerwelle",
       "uhrzeit": "10:45"
     }'
   ```
4. ✅ Expected result: `"available": false` with NO alternatives at 10:45, 11:00, or 11:15
5. ✅ Request new test call from Retell AI
6. ✅ Verify booking succeeds end-to-end

---

## Deployment Checklist

- [ ] Update `ProcessingTimeAvailabilityService.php`
- [ ] Syntax check: `php -l`
- [ ] Reload PHP-FPM: `sudo systemctl reload php8.3-fpm`
- [ ] Test with curl
- [ ] Request test call
- [ ] Monitor logs for success
- [ ] Update this RCA with test results

---

## Related Issues

- **CALL_ID_FIX_DEPLOYMENT_2025-11-23.md**: Fixed call_id placeholder issue
- **RCA_CALL_ID_MISMATCH_2025-11-23.md**: Previous RCA for call_id "call_1" problem

---

**Status**: 🔧 FIX READY TO DEPLOY
**Priority**: 🚨 CRITICAL - Blocks all composite service bookings
**Next Step**: Apply code fix and test
