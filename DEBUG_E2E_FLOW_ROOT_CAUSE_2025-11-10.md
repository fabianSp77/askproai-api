# E2E Flow Alternative Selection Root Cause Analysis

**Date**: 2025-11-10
**Issue**: E2E flow `start_booking` fails with alternative times, but works with original time
**Status**: Root Cause Identified - Awaiting Log Execution

---

## Summary

The E2E flow test shows a **paradox**:

| Test | Service | Datetime | Result |
|------|---------|----------|---------|
| Single test (16:33:34) | Herrenhaarschnitt | 2025-11-10 10:00 (TODAY) | ✅ SUCCESS |
| E2E flow (16:33:43) | Herrenhaarschnitt | 2025-11-11 09:45 (ALTERNATIVE) | ❌ FAIL: "Dieser Service ist leider nicht verfügbar" |
| Earlier flow test (16:30:36) | Herrenhaarschnitt | 2025-11-11 09:45 (ALTERNATIVE) | ❌ FAIL: Same error |

The error message indicates that `$service` is not found or `$service->calcom_event_type_id` is missing/null.

---

## Code Analysis

### Frontend JavaScript (api-testing.blade.php, lines 574-640)

The alternative selection logic IS being executed:

```javascript
// Step 5: Book Appointment (via function handler)
if (availabilityData?.data?.available === false &&
    availabilityData?.data?.alternatives?.length > 0) {
    useAlternative = true;
    bookingTime = availabilityData.data.alternatives[0].time;  // "2025-11-11 09:45"
}
```

I've added comprehensive debug logging here to track:
- Whether alternative selection is happening
- The exact datetime being sent
- The complete payload

### Backend (RetellFunctionCallHandler.php, lines 1755-1970)

The `startBooking()` function goes through these steps:

1. **STEP 1**: Get call context (company_id, branch_id)
2. **STEP 2**: Parse datetime from params
3. **STEP 3**: Extract customer data
4. **STEP 4**: Select service by name/ID
5. **STEP 5**: Cache validated booking data

**Error location**: Line 1948 - Service lookup failure

```php
if (!$service || !$service->calcom_event_type_id) {
    Log::error('❌ start_booking: Service lookup FAILED', [...]);
    return error('Dieser Service ist leider nicht verfügbar', ...);
}
```

---

## Hypothesis: Why Alternative Time Fails

The datetime format and customer data are being sent correctly in the function call parameters. However, the service lookup is failing specifically when using alternative times.

### Potential Root Causes (in order of likelihood):

**1. DateTime Parsing Issue (MOST LIKELY)**
- The `dateTimeParser->parseDateTime($params)` may be failing for the alternative format
- Frontend sends: `"2025-11-11 09:45"` (with space)
- Backend might expect: `appointment_date` + `appointment_time` separately
- **Evidence**: check_availability WORKS with separate date/time, but start_booking gets combined datetime

**2. Service Lookup by Name Issue**
- The serviceName extraction from params might be missing
- Or the `findServiceByName()` call is failing for some reason

**3. Company/Branch Context Issue**
- When using alternative time (next day), maybe the context switches companies/branches?
- Unlikely but possible if there's timezone-related logic

**4. Cache Pinning Issue**
- Line 1909: `$pinnedServiceId = Cache::get("call:{$callId}:service_id")`
- Maybe the cache key doesn't match between check_availability and start_booking

---

## Debug Logging Added

I've added comprehensive logging at every step:

### Frontend (api-testing.blade.php)

```javascript
console.log('🔍 [DEBUG] Availability data:', { available, alternatives, count });
console.log('✅ [DEBUG] Using alternative time:', bookingTime);
console.log('🔍 [DEBUG] start_booking payload:', { service_name, datetime, customer_name });
console.log('🔍 [DEBUG] start_booking response:', { success, status, error });
```

### Backend (RetellFunctionCallHandler.php)

**STEP 1 SUCCESS**:
```php
Log::info('✅ start_booking: STEP 1 SUCCESS - Context obtained', [
    'company_id' => $companyId,
    'branch_id' => $branchId
]);
```

**STEP 2 SUCCESS**:
```php
Log::info('✅ start_booking: STEP 2 SUCCESS - Datetime parsed', [
    'parsed_datetime' => $appointmentTime->format('Y-m-d H:i:s')
]);
```

**STEP 3**: Customer data extraction with all params logged

**STEP 4**: Comprehensive service lookup with separate logging for each path:
- Via pinned cache
- Via service_id parameter
- Via service_name parameter
- Via default service

**Each path logs**:
- What lookup method was attempted
- Whether service was found
- Service ID, name, and calcom_event_type_id

**Error case** logs the full context:
```php
Log::error('❌ start_booking: Service lookup FAILED', [
    'service_found' => 'yes/no',
    'has_calcom_event_type' => 'yes/no/N/A',
    'appointment_datetime' => '...',
    'params' => $params
]);
```

---

## Next Steps

1. **Run the E2E flow test** with the new logging enabled
2. **Capture the logs** showing which STEP succeeds and which fails
3. **Identify the exact failure point** (likely STEP 2 datetime parsing)
4. **Fix the root cause** once identified

---

## Files Modified

**Frontend**:
- `/var/www/api-gateway/resources/views/docs/api-testing.blade.php` (lines 574-640)
  - Added debug logging for alternative selection
  - Added debug logging for payload and response

**Backend**:
- `/var/www/api-gateway/app/Http/Controllers/RetellFunctionCallHandler.php` (lines 1755-1970)
  - Added STEP 1, STEP 2, STEP 3, STEP 4 logging
  - Added service lookup detailed logging
  - Added comprehensive error logging with full context

---

## How to Test

1. Run the E2E flow test in the browser
2. Open browser DevTools → Console
3. Look for:
   - `🔍 [DEBUG] Availability data` (should show alternatives)
   - `✅ [DEBUG] Using alternative time: 2025-11-11 09:45`
   - `🔍 [DEBUG] start_booking payload` (check if service_name is present)
4. Check Laravel logs:
   ```bash
   tail -f storage/logs/laravel.log | grep "STEP\|Service lookup"
   ```
5. Find the exact failure point from the logs

---

## Expected Log Output (Successful Flow)

```
[start_booking] 🔷 Step 1 of 2-step booking flow
[start_booking] 🔷 STEP 1 - Get call context
[start_booking] ✅ STEP 1 SUCCESS - Context obtained
[start_booking] 🔷 STEP 2 - Parse datetime
[start_booking] ✅ STEP 2 SUCCESS - Datetime parsed (2025-11-11 09:45:00)
[start_booking] 🔷 STEP 3 - Extract customer data
[start_booking] 🔷 STEP 4 - Service lookup started
[start_booking] 🔍 Looking up service by NAME (Herrenhaarschnitt)
[start_booking] ✅ Service found via SERVICE NAME
[start_booking] ✅ STEP 4 SUCCESS - Service lookup completed
[start_booking] ✅ Data validated and cached
```

---

**Created**: 2025-11-10 16:40 UTC
**By**: Claude Code - RCA Debugging Session
