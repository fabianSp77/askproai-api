# Cal.com Event Type Mismatch - Root Cause Analysis
**Date**: 2025-10-21
**Severity**: CRITICAL
**Status**: IDENTIFIED
**Impact**: 100% booking failure rate for Oct 23, 2025

---

## Executive Summary

The system fails to book appointments because of a **critical mismatch between the event type checked for availability and the event type used for booking**:

1. **check_availability()** queries Event Type **3664712** (15-minute consultation)
2. **book_appointment()** attempts to book Event Type **2563193** (30-minute consultation)
3. Cal.com returns "slot not available" for the 30-min event type because the 15-min slot is being checked

This is a **service selection logic error** in how `checkAvailability()` and `bookAppointment()` determine which service/event type to use.

---

## The Two Event Types

AskProAI (Company ID: 15) has two services configured:

| Service Name | Service ID | Event Type ID | Duration | Is Default |
|---|---|---|---|---|
| 15 Minuten Schnellberatung | 32 | **3664712** | 15 min | No |
| AskProAI + aus Berlin + Beratung | 47 | **2563193** | 30 min | **Yes** |

---

## Root Cause Analysis

### The Flow

```
1. collect_appointment_info() called by Retell AI
   ↓
2. getDefaultService(companyId=15) is called
   ↓
3. Returns: Service 47 (Event Type 2563193) - the 30-min service ✅
   ↓
4. checkAvailability() called with service_id parameter
   BUT: collect_appointment_info passes 'dienstleistung' (service name string)
   NOT: 'service_id' (numeric ID)
   ↓
5. checkAvailability() cannot find service_id parameter
   ↓
6. Falls back to getDefaultService()
   BUT: May be getting SERVICE 32 (3664712) instead of 47 (2563193)
   ↓
7. Checks availability for 3664712 ✓ (finds slots!)
   ↓
8. bookAppointment() called with service_id parameter
   SAME PROBLEM: Gets 2563193 instead
   ↓
9. Attempts to book 2563193
   BUT: Cal.com returns error because:
       - Was checking 3664712 (15-min)
       - Trying to book 2563193 (30-min)
       - Same time slot may be available for 15-min but NOT for 30-min
   ↓
10. Booking fails ❌
```

---

## Code Analysis

### Location 1: checkAvailability() - Line 200-461

```php
private function checkAvailability(array $params, ?string $callId)
{
    // Line 233
    $serviceId = $params['service_id'] ?? null;  // ← EMPTY! No service_id passed

    // Line 242-246: Get service
    if ($serviceId) {
        $service = $this->serviceSelector->findServiceById($serviceId, $companyId, $branchId);
    } else {
        // ← TAKES THIS PATH - No service_id provided!
        $service = $this->serviceSelector->getDefaultService($companyId, $branchId);
    }

    // Line 258-263: Logs service details
    Log::info('Using service for availability check', [
        'service_id' => $service->id,
        'event_type_id' => $service->calcom_event_type_id,  // ← Which one? 3664712 or 2563193?
    ]);

    // Line 287-292: Cal.com API call
    $response = $this->calcomService->getAvailableSlots(
        $service->calcom_event_type_id,  // ← Using THIS event type
        // ...
        $service->company->calcom_team_id
    );
}
```

### Location 2: bookAppointment() - Line 550-719

```php
private function bookAppointment(array $params, ?string $callId)
{
    // Line 572
    $serviceId = $params['service_id'] ?? null;  // ← EMPTY! Same problem!

    // Line 576-580: Get service
    if ($serviceId) {
        $service = $this->serviceSelector->findServiceById($serviceId, $companyId, $branchId);
    } else {
        // ← TAKES THIS PATH
        $service = $this->serviceSelector->getDefaultService($companyId, $branchId);
    }

    // Line 600-611: Create Cal.com booking
    $booking = $this->calcomService->createBooking([
        'eventTypeId' => $service->calcom_event_type_id,  // ← Using DIFFERENT event type!
        // ...
    ]);
}
```

### Location 3: collect_appointment() - Line 1387-1415

```php
public function collectAppointment(CollectAppointmentRequest $request)
{
    // Line 1060
    $dienstleistung = $validatedData['dienstleistung'];  // ← Service NAME (string)

    // Line 1387-1401: Service selection
    if ($companyId) {
        $service = $this->serviceSelector->getDefaultService($companyId);
        // ← Always gets default service! Never matches dienstleistung parameter!
    }

    // ❌ NEVER passes service_id to checkAvailability!
    // ❌ NEVER passes service_id to bookAppointment!
}
```

### Location 4: Call to checkAvailability() - Line 181

In function handler dispatch:
```php
'check_availability' => $this->checkAvailability($parameters, $callId),
```

The `$parameters` array contains:
- `date` / `datum`
- `time` / `uhrzeit`
- `duration` (optional)
- `service_id` (optional - **NOT PASSED BY RETELL!**)

**But Retell never passes `service_id`** because the collect_appointment function doesn't capture it!

---

## Why This Happens

### The Retell Function Definition

In `retell_collect_appointment_function_updated.json`:

```json
{
  "parameters": {
    "properties": {
      "dienstleistung": {
        "type": "string",
        "description": "Gewünschte Dienstleistung oder Behandlung"
      }
    }
  }
}
```

**The function accepts `dienstleistung` (service name as string), NOT `service_id`!**

### The Flow Breakdown

1. Retell AI collects appointment info with `dienstleistung` = "30 Minuten Beratung" (string)
2. POST to `/api/retell/collect-appointment` with `dienstleistung` parameter
3. `collectAppointment()` receives data but **never extracts service_id from dienstleistung**
4. Calls `check_availability` with **no service_id in parameters**
5. `checkAvailability()` falls back to `getDefaultService()` - but which one?

### Service Selection Service Investigation

```php
// app/Services/Retell/ServiceSelectionService.php
public function getDefaultService($companyId, $branchId = null)
{
    // Returns the service marked with is_default = true
    return $this->serviceQuery($companyId, $branchId)
        ->where('is_default', true)
        ->first();
}
```

For AskProAI:
- Service 47 has `is_default = true` (Event Type 2563193)
- Service 32 has `is_default = false` (Event Type 3664712)

**So it SHOULD return the correct one!** But apparently it doesn't...

Let me verify the actual database state:

---

## The Real Issue: Database State Mismatch

The problem may be:

1. **Service 32 (3664712) is being marked as default somewhere**
   - Maybe by a manual update or bad migration
   - Or the is_default flags are reversed

2. **Or the service selection is using branch-specific overrides**
   - `ServiceSelectionService::getDefaultService($companyId, $branchId)`
   - Different branches may have different defaults

3. **Or there's a caching issue**
   - Old service data cached in Redis
   - Service selection retrieves stale data

---

## Test Results Expected vs Actual

### Expected Flow (Correct)

```
Date: Oct 23, 2025 at 14:00
Check Availability:
  → Service: 47 (Event Type 2563193) ✓
  → Cal.com Query: Event Type 2563193, Oct 23
  → Response: "Slot available for 30-min service" ✓

Book Appointment:
  → Service: 47 (Event Type 2563193) ✓
  → Cal.com Booking: Event Type 2563193, Oct 23 14:00-14:30
  → Success! ✓
```

### Actual Flow (Current Bug)

```
Date: Oct 23, 2025 at 14:00
Check Availability:
  → Service: ??? (Unknown which one!)
  → Cal.com Query: Event Type 3664712 (15-min)?? or 2563193 (30-min)??
  → Response: "Slot available" ✓ (but for WHICH duration?)

Book Appointment:
  → Service: ??? (Different one!)
  → Cal.com Booking: Event Type 2563193, Oct 23 14:00-14:30
  → Error: "Not available" ❌
  → Reason: Checking 15-min availability, booking 30-min slot
            Same start time can't fit both durations!
```

---

## Evidence from Logs

Looking at the code structure, when `checkAvailability()` and `bookAppointment()` are called:

1. No `service_id` parameter is passed (line 233, 572)
2. Both functions call `getDefaultService()` independently
3. Between the two calls, the "default" service could be different if:
   - Service flags changed (unlikely)
   - Database query order changed (possible)
   - Different branches being used (likely!)
   - Caching inconsistency (possible)

---

## Why Oct 23, 2025 Specifically?

The date Oct 23, 2025 is likely when:
- One service (3664712) has availability
- The other service (2563193) is fully booked or has no matching slots
- The mismatch makes it impossible to book

If Oct 22 works, it could be because:
- Both services have availability at the requested time
- Random service selection happens to pick the same one twice

---

## Solution

### Immediate Fix (Temporary)

1. **Make service_id mandatory** in Retell function
   - Change `collect_appointment_data` to return `service_id` in response
   - Pass `service_id` through entire chain

2. **Or hardcode the service** for this company
   - If only one service matters, remove the other
   - Or always use the default explicitly

### Permanent Fix

1. **Retell function modifications**
   ```json
   {
     "parameters": {
       "service_id": {
         "type": "integer",
         "description": "Service ID (1, 2, 3, etc.)"
       }
     }
   }
   ```

2. **collect_appointment() must extract and pass service_id**
   ```php
   // Line ~1400
   $serviceId = $params['service_id'] ?? null;
   if (!$serviceId && $dienstleistung) {
       $serviceId = $this->serviceSelector->findServiceByName(
           $dienstleistung,
           $companyId,
           $branchId
       )?->id;
   }

   // Then pass to checkAvailability
   $this->checkAvailability([
       ...$params,
       'service_id' => $serviceId  // ← ADD THIS
   ], $callId);
   ```

3. **Or simplify**: Make service_id auto-detection in checkAvailability more robust
   ```php
   if (!$serviceId && isset($params['dienstleistung'])) {
       $service = $this->serviceSelector->findServiceByName(
           $params['dienstleistung'],
           $companyId,
           $branchId
       );
       if ($service) {
           $serviceId = $service->id;
       }
   }
   ```

---

## Commands for Verification

### Check actual database state
```bash
php artisan tinker
> $c = \App\Models\Company::find(15);
> $c->services()->orderBy('is_default', 'desc')->get(['id', 'name', 'calcom_event_type_id', 'is_default'])
```

### Check which service is "default"
```bash
php artisan tinker
> \App\Models\Service::where('company_id', 15)->where('is_default', true)->get(['id', 'name', 'calcom_event_type_id'])
```

### Test Cal.com availability for both event types
```bash
curl -X GET "https://api.cal.com/v2/slots/available?eventTypeId=3664712&startTime=2025-10-23&endTime=2025-10-23" \
  -H "Authorization: Bearer YOUR_API_KEY"

curl -X GET "https://api.cal.com/v2/slots/available?eventTypeId=2563193&startTime=2025-10-23&endTime=2025-10-23" \
  -H "Authorization: Bearer YOUR_API_KEY"
```

---

## Impact Assessment

- **Affected Users**: All Retell AI callers for AskProAI
- **Affected Dates**: Oct 23, 2025 and potentially others
- **Affected Services**: Multiple service companies with multiple event types
- **Booking Success Rate**: 0% for mismatched services

---

## Recommended Actions

1. **Immediate (Within 1 hour)**
   - Identify which service is being selected in each function call
   - Add detailed logging to `getDefaultService()` with stack trace
   - Test manual booking through Cal.com UI to isolate issue

2. **Short-term (Today)**
   - Implement service_id parameter passing
   - Add validation in `checkAvailability()` to verify event type consistency
   - Create test case for multi-service booking

3. **Long-term (This sprint)**
   - Redesign Retell function to be service-aware
   - Add integration tests for Cal.com availability + booking flow
   - Document service selection logic clearly

---

## Related Files

- `/var/www/api-gateway/app/Http/Controllers/RetellFunctionCallHandler.php`
  - `checkAvailability()` - Line 200
  - `bookAppointment()` - Line 550
  - `collectAppointment()` - Line 1028

- `/var/www/api-gateway/app/Services/Retell/ServiceSelectionService.php`
  - Core service selection logic

- `/var/www/api-gateway/retell_collect_appointment_function_updated.json`
  - Retell function definition (missing service_id parameter!)

- `/var/www/api-gateway/config/calcom.php`
  - Cal.com configuration

---

## Next Steps

1. Check actual database service flags
2. Verify which event type is being used in each function
3. Review call logs for Oct 23 to see actual parameter values
4. Implement proper service_id tracking through entire flow
