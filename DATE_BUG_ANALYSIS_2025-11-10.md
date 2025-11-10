# Date-Dependent Service Lookup Bug Analysis

**Date**: 2025-11-10, 17:50 Uhr
**Status**: 🔍 INVESTIGATION IN PROGRESS
**Critical Discovery**: Backend behavior differs for current vs. future dates

---

## Executive Summary

### What We Know For Sure ✅

1. **Alternative Selection Works**: Laravel logs PROVE E2E flow sends `"datetime": "2025-11-11 09:45"` (the alternative)
2. **Parameter Fix Works**: Single test with `service_name` succeeds
3. **Date-Dependent Failure**: Same service, same parameters, DIFFERENT DATE = different outcome

### The Bug Pattern 🐛

| Test | Date | service_name | Result |
|------|------|--------------|--------|
| Single Test | 2025-11-10 (TODAY) | "Herrenhaarschnitt" | ✅ SUCCESS |
| E2E Flow | 2025-11-11 (TOMORROW) | "Herrenhaarschnitt" | ❌ FAILED |

**Error Message**: "Dieser Service ist leider nicht verfügbar"

---

## Evidence

### From Laravel Logs (16:44:30-16:44:41)

**Single Test (16:44:33)** - SUCCESS:
```json
{
  "service_name": "[PII_REDACTED]",
  "datetime": "2025-11-10 10:00"
}
```

**E2E Flow (16:44:37)** - FAILED:
```json
{
  "service_name": "[PII_REDACTED]",
  "datetime": "2025-11-11 09:45"  // ← Alternative is being sent!
}
```

### Key Insight

The alternative selection logic IS working! The E2E flow correctly sends `09:45` (the alternative) instead of `10:00` (the unavailable time).

**But**: The backend rejects the booking with "Service nicht verfügbar" despite sending the correct alternative time.

---

## Code Analysis

### Service Lookup Logic

**File**: `app/Services/Retell/ServiceSelectionService.php`

**Method**: `findServiceByName()` (Lines 265-371)

The service lookup has 3 strategies:
1. **Exact Match**: Case-insensitive name/slug match
2. **Synonym Match**: Checks `service_synonyms` table
3. **Fuzzy Match**: Levenshtein distance (75% similarity)

**Critical Requirements** (Lines 273-275):
```php
Service::where('company_id', $companyId)
    ->where('is_active', true)           // ← MUST BE ACTIVE
    ->whereNotNull('calcom_event_type_id') // ← MUST HAVE CAL.COM EVENT TYPE
```

**Question**: Why do these conditions pass for TODAY but fail for TOMORROW?

---

## Possible Root Causes

### Hypothesis 1: Service Availability Check

**Theory**: Service lookup might check Cal.com availability and reject if no slots available

**Evidence**:
- Single test uses TODAY (likely has availability)
- E2E flow uses TOMORROW (specific time might be unavailable)

**Counterargument**:
- The code shows NO date-based filtering in `ServiceSelectionService`
- Service lookup is purely name-based, no availability check

**Status**: ❌ UNLIKELY

---

### Hypothesis 2: Date Parsing Issue

**Theory**: Date format or timezone conversion fails for future dates

**Evidence**:
- Both tests send format: "YYYY-MM-DD HH:MM"
- Backend parses with Carbon

**Code** (`RetellFunctionCallHandler.php:1846-1856`):
```php
try {
    $appointmentTime = Carbon::parse($datetime, config('app.timezone'));
} catch (\Exception $e) {
    Log::error('Invalid appointment datetime', [
        'datetime' => $datetime,
        'error' => $e->getMessage()
    ]);
    return $this->responseFormatter->error(
        'Ungültiges Datum oder Uhrzeit Format',
        ...
    );
}
```

**Status**: ⚠️ POSSIBLE (but should show different error message)

---

### Hypothesis 3: Missing Debug Logs

**Theory**: We need fresh test data with debug logging enabled

**Evidence**:
- Debug logging added at line 1913: `🔍 start_booking: STEP 4 - Service lookup started`
- User's test was at 16:44:37, BEFORE debug logging was added
- No STEP 4 logs visible in recent logs

**What We Need**:
```
[timestamp] 🔍 start_booking: STEP 4 - Service lookup started
  → pinned_service_id: null
  → service_id_param: null
  → service_name_param: "Herrenhaarschnitt"
  → appointment_time: "2025-11-11 09:45:00"

THEN EITHER:

[timestamp] ✅ start_booking: STEP 4 SUCCESS - Service lookup completed
  → service_id: 123
  → service_name: "Herrenhaarschnitt"

OR:

[timestamp] ❌ start_booking: Service lookup FAILED
  → service_found: no
  → Has cal.com event type: N/A
```

**Status**: ✅ **MOST LIKELY** - Need fresh test with debug logging

---

## Required Next Steps

### Step 1: Re-test with Debug Logging ⏳

**Action**: User needs to test again via `/docs/api-testing`

**What to Test**:
1. ✅ Single test `start_booking` (to verify SUCCESS case still works)
2. ✅ E2E Flow (to capture FAILED case with debug logs)

**What to Capture**:
- Complete Laravel log output for both tests
- Focus on lines containing: `start_booking: STEP 4`

### Step 2: Analyze Debug Output

**Look for**:
- Did service lookup START? (🔍 log)
- Did service lookup SUCCEED? (✅ log)
- Did service lookup FAIL? (❌ log with reason)

### Step 3: Fix Based on Findings

**If service lookup succeeds**:
→ Bug is AFTER service lookup (date validation, availability check, etc.)

**If service lookup fails**:
→ Bug is IN service lookup (date affects query, cache issue, etc.)

---

## Debug Commands

### Check Recent start_booking Logs
```bash
tail -200 storage/logs/laravel.log | grep "start_booking"
```

### Check STEP 4 Logs (after re-test)
```bash
tail -500 storage/logs/laravel.log | grep -A 15 "STEP 4"
```

### Check for Service Lookup Errors
```bash
tail -200 storage/logs/laravel.log | grep "Service lookup FAILED"
```

---

## Files Modified

### Debug Logging Added

**File**: `app/Http/Controllers/RetellFunctionCallHandler.php`

**Location**: Lines 1912-1994

**Changes**:
1. Line 1913-1922: STEP 4 START log
2. Line 1965-1982: STEP 4 FAILED log (enhanced with more context)
3. Line 1984-1994: STEP 4 SUCCESS log

**Commit**: fb708702 - "debug: add comprehensive logging"

---

## Success Criteria

### Phase 1: Identify Root Cause ⏳
- [ ] Re-test with debug logging
- [ ] Analyze STEP 4 logs
- [ ] Identify exact failure point

### Phase 2: Implement Fix 🔜
- [ ] Fix identified issue
- [ ] Verify single test still works
- [ ] Verify E2E flow now works

### Phase 3: Production Validation 🔜
- [ ] Test via phone call (+493033081738)
- [ ] Verify booking succeeds
- [ ] Confirm in database

---

## Timeline

| Time | Event | Status |
|------|-------|--------|
| 16:44:33 | Single test (TODAY) | ✅ SUCCESS |
| 16:44:37 | E2E flow (TOMORROW) | ❌ FAILED |
| 16:48:53 | Debug logging added (commit fb708702) | ✅ COMPLETE |
| 17:50 | Analysis document created | ✅ COMPLETE |
| Next | **Re-test needed** | ⏳ PENDING |

---

## Key Takeaways

1. ✅ **Alternative Selection Works**: Logs prove E2E flow uses `09:45` (alternative)
2. ✅ **Parameter Fix Works**: Single test succeeds with `service_name`
3. 🐛 **Date-Dependent Bug**: Backend accepts TODAY, rejects TOMORROW
4. 🔍 **Debug Logging Ready**: Need fresh test to see STEP 4 execution
5. ⏳ **Next Action**: User re-test required

---

**Created**: 2025-11-10, 17:50 Uhr
**Issue**: Date-dependent service lookup failure
**Status**: Investigation in progress, awaiting debug log output

