# E2E Flow Debugging Quick Start

**Goal**: Identify why start_booking fails with alternative times but succeeds with current time

---

## The Problem (Confirmed)

When E2E flow uses an **alternative time** from check_availability:
- Frontend sends: `start_booking` with `datetime: "2025-11-11 09:45"`
- Backend returns: `error: "Dieser Service ist leider nicht verfügbar"`

When **single test** uses **current time**:
- Frontend sends: `start_booking` with `datetime: "2025-11-10 10:00"`
- Backend returns: `status: "validating"` ✅ SUCCESS

---

## Debug Logging Implemented

### 1. Frontend Console Logging (Browser)

Added to `/var/www/api-gateway/resources/views/docs/api-testing.blade.php`

When you run the E2E test, open **DevTools → Console** and look for:

```javascript
🔍 [DEBUG] Availability data:
   available: false
   alternativesCount: 2
   alternatives: [{time: "2025-11-11 09:45", available: true}, ...]

✅ [DEBUG] Using alternative time: 2025-11-11 09:45

🔍 [DEBUG] start_booking payload:
   service_name: "Herrenhaarschnitt"
   datetime: "2025-11-11 09:45"
   customer_name: "Test Kunde"
   customer_phone: "+491234567890"

🔍 [DEBUG] start_booking response:
   success: false
   error: "Dieser Service ist leider nicht verfügbar"
```

---

### 2. Backend Structured Logging (Laravel)

Added to `/var/www/api-gateway/app/Http/Controllers/RetellFunctionCallHandler.php`

View logs with:
```bash
tail -f storage/logs/laravel.log | grep "start_booking\|STEP\|Service"
```

**Expected successful sequence**:

```
[16:40:00] 🔷 start_booking: Step 1 of 2-step booking flow
[16:40:00] 🔷 start_booking: STEP 1 - Get call context
[16:40:00] ✅ start_booking: STEP 1 SUCCESS - Context obtained
[16:40:00] 🔷 start_booking: STEP 2 - Parse datetime
[16:40:00] ✅ start_booking: STEP 2 SUCCESS - Datetime parsed
[16:40:00] 🔷 start_booking: STEP 3 - Extract customer data
[16:40:00] 🔍 start_booking: STEP 4 - Service lookup started
[16:40:00] 🔍 Looking up service by NAME
[16:40:00] 📊 Service lookup by name result
[16:40:00] ✅ start_booking: STEP 4 SUCCESS - Service lookup completed
[16:40:00] ✅ start_booking: Data validated and cached
```

**If you see an error**, look for the step where it fails:

```
[16:40:00] ❌ start_booking: Service lookup FAILED
```

This log will show:
- `service_found: yes/no`
- `has_calcom_event_type: yes/no/N/A`
- `appointment_datetime: 2025-11-11 09:45:00`
- `params`: all input parameters

---

## Testing Procedure

### Step 1: Clear Logs

```bash
> ./storage/logs/laravel.log
```

### Step 2: Start Log Tail

```bash
tail -f storage/logs/laravel.log | grep -E "start_booking|STEP|Service lookup"
```

### Step 3: Run E2E Test

1. Open browser DevTools (F12)
2. Go to Console tab
3. Navigate to: `/docs/api-testing` (or similar)
4. Click "Kompletten Flow testen"
5. Watch the console output

### Step 4: Analyze Results

**Check Frontend Console First**:
- Is `datetime: "2025-11-11 09:45"` being sent?
- Is the payload correct?

**Then Check Backend Logs**:
- Which STEP fails?
- What does the error log show?

---

## Key Fields to Check

### In Logs, Look For:

1. **Datetime Parsing** (STEP 2):
   - `params_datetime`: What format is being received?
   - `parsed_datetime`: Can it be parsed to valid Carbon date?

2. **Service Lookup** (STEP 4):
   - `service_name_param`: Is "Herrenhaarschnitt" being extracted?
   - `service_found: yes/no`: Was the service found?
   - `calcom_event_type_id`: Does the service have this?

3. **Error Details**:
   - `service_id`: What ID was looked up?
   - `company_id`, `branch_id`: Are these correct?
   - `appointment_datetime`: What datetime was used?

---

## Likely Root Causes (Priority Order)

### 1. DateTime Parsing Format Mismatch

**Theory**: `dateTimeParser->parseDateTime()` expects separate `appointment_date` + `appointment_time` fields, but start_booking sends combined `datetime` field.

**Evidence to Check**:
- STEP 2 log: Is `parsed_datetime` showing a valid date?
- Frontend payload: `datetime: "2025-11-11 09:45"` (should match this format)

**Fix if True**:
- Check `app/Services/Retell/DateTimeParser.php`
- Add support for combined datetime format
- Or restructure the datetime before calling parseDateTime

### 2. Service Name Not Extracted

**Theory**: `$serviceName` is empty because params doesn't have `service_name` key

**Evidence to Check**:
- STEP 3/4 logs: What's in `params_service_name`?
- Is it "Herrenhaarschnitt" or null/empty?

**Fix if True**:
- Pass `service_name` explicitly in frontend payload
- Or cache the service ID from check_availability

### 3. Service Lookup Returns Null

**Theory**: `findServiceByName()` works in single test but not in E2E flow

**Evidence to Check**:
- STEP 4 log: `service_found: yes` but `calcom_event_type_id: null`?
- Check database: Does service have `calcom_event_type_id` set?

**Fix if True**:
- Verify service record in database
- Check if company_id/branch_id are different

---

## Files to Monitor

**Backend Logs**:
```
/var/www/api-gateway/storage/logs/laravel.log
```

**Modified Code**:
```
/var/www/api-gateway/app/Http/Controllers/RetellFunctionCallHandler.php
/var/www/api-gateway/resources/views/docs/api-testing.blade.php
```

**Analysis Report**:
```
/var/www/api-gateway/DEBUG_E2E_FLOW_ROOT_CAUSE_2025-11-10.md
```

---

## Commands

### View Recent Logs
```bash
tail -100 storage/logs/laravel.log
```

### Find start_booking Entries
```bash
grep "start_booking" storage/logs/laravel.log | tail -20
```

### Find Service Lookup Failures
```bash
grep "Service lookup FAILED\|STEP 4" storage/logs/laravel.log | tail -20
```

### Real-time Monitoring
```bash
tail -f storage/logs/laravel.log | grep -E "start_booking|STEP|Service lookup|failed"
```

### Count Occurrences
```bash
grep -c "start_booking" storage/logs/laravel.log
```

---

## Next Actions

1. **Run test** and capture logs
2. **Identify failure point** from STEP numbers
3. **Review logs** for the exact error
4. **Implement fix** based on root cause
5. **Verify fix** with repeat test
6. **Document findings** in RCA file

---

**Created**: 2025-11-10 16:42 UTC
**Status**: Ready for Testing
**Commit**: `debug: add comprehensive logging to start_booking function`
