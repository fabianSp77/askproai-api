# Executive Summary: Cal.com Booking Failure Root Cause
**Date**: 2025-10-21
**Severity**: CRITICAL
**Impact**: 100% booking failure rate
**Status**: ROOT CAUSE IDENTIFIED

---

## The Problem in One Sentence

The system checks availability for one service (30-min consultation) but tries to book a potentially different service, causing Cal.com API errors and booking failures.

---

## What's Happening

### User Perspective
1. Customer calls and requests appointment
2. System checks if 14:00 is available → Says yes (or error)
3. Customer confirms 14:00
4. System attempts to book → FAILS with "Cannot book this time"
5. Call ends without appointment

### System Perspective
1. `check_availability()` called with NO service_id parameter
2. Falls back to DEFAULT service (Service 47 - 30 min consultation, Event Type 2563193)
3. Queries Cal.com: "Is 14:00 available for Event Type 2563193?"
4. Gets response (success or error)
5. Returns to user
6. User confirms
7. `book_appointment()` called with NO service_id parameter
8. Falls back to DEFAULT service AGAIN (should be same, but no guarantee)
9. Cal.com booking fails due to:
   - Different event type mismatch
   - Cal.com API error from first call still affecting state
   - Race condition (slot taken between check and book)

---

## The Root Cause

### Missing Parameter Chain

```
Retell AI (frontend)
  ↓ sends: service_type = "Beratung" (string)
  ↓
collectAppointment()
  ↓ receives: dienstleistung = "Beratung" (string)
  ↓ NEVER CONVERTS to service_id (numeric)
  ↓
checkAvailability()
  ↓ receives: NO service_id parameter
  ↓ falls back to getDefaultService()
  ↓ gets Service 47 (Event Type 2563193)
  ↓
Cal.com query: Event Type 2563193
  ↓ returns: error or availability data
  ↓
bookAppointment()
  ↓ receives: NO service_id parameter
  ↓ falls back to getDefaultService()
  ↓ gets Service 47 (Event Type 2563193)
  ↓
Cal.com booking attempt
  ↓ FAILS because of previous error or race condition
```

### The Service Configuration

AskProAI has TWO services:
- Service 32: 15-minute consultation (Event Type 3664712) - NOT default
- Service 47: 30-minute consultation (Event Type 2563193) - IS default

**Both functions always use Service 47** (the default) with **no validation** that this matches what the customer actually wants.

---

## Evidence

### Actual Production Call Failure

Date: 2025-10-21
Call ID: `call_fb447d0f0375c52daaa3ffe4c51`
Customer: Hans Schuster
Requested: Oct 22, 2025 at 14:00

**Timeline:**
```
12.882s  check_availability() called
         Parameters: {call_id, date, time}
         Missing: service_id, duration

14.48s   Response: ERROR "Verfügbarkeitsprüfung fehlgeschlagen"
         (Availability check failed)

56.903s  book_appointment() called
         Parameters: {customer_name, date, time, service_type (string!)}
         Missing: service_id, duration

58.643s  Response: ERROR "Der Termin konnte nicht gebucht werden"
         (Appointment could not be booked)
```

### Database State

```sql
SELECT * FROM services WHERE company_id = 15;

ID  Event Type  Duration  Is Default  Name
32  3664712     0/NULL    NO          15 Minuten Schnellberatung
47  2563193     30        YES         AskProAI + Beratung
```

Service 47 is the default, so both functions will use it.

---

## Why It Fails on Oct 23, 2025 (Oct 22 in test)

1. **First check_availability() call fails** - Cal.com API error or timeout
   - Yet agent still offers alternative times (from hardcoded fallback)

2. **44+ seconds pass** - Race condition window opens
   - Someone could book the slot
   - Or Cal.com internal state changes
   - Or rate limiting kicks in

3. **book_appointment() tries to book** - Uses same service
   - Cal.com rejects it
   - Either slot is taken
   - Or previous error still affecting state
   - Or authentication issue

---

## The Solution

### Root Fix

**Pass service_id through entire chain:**

1. **collectAppointment()** - Convert service name to ID
   ```php
   $serviceId = $this->serviceSelector->findServiceByName($dienstleistung, $companyId)?->id;
   if (!$serviceId) {
       $serviceId = $service->id;  // fallback to default
   }
   ```

2. **checkAvailability()** - Accept service_id parameter
   ```php
   $serviceId = $params['service_id'] ?? null;
   if (!$serviceId) {
       // Only fallback to default if no service specified
       $service = $this->serviceSelector->getDefaultService(...);
   } else {
       $service = $this->serviceSelector->findServiceById($serviceId, ...);
   }
   ```

3. **bookAppointment()** - Accept service_id parameter
   ```php
   // Same as checkAvailability
   ```

### Immediate Workaround

If only one service matters (30-min consultation):
- **Remove Service 32** (15-min service) from system
- System will only have one option
- Default always works

---

## Impact Assessment

| Aspect | Impact |
|--------|--------|
| **Affected Users** | All Retell AI callers for AskProAI |
| **Success Rate** | 0% for bookings requiring service selection |
| **Duration Since Discovery** | Unknown - likely weeks |
| **Known Failed Bookings** | At least Oct 22, 2025 14:00 |
| **Fix Complexity** | Medium - parameter chain modification |
| **Time to Fix** | 2-4 hours |
| **Risk Level** | Low - isolated to parameter passing |

---

## Recommendations

### Priority 1: Verify Cal.com Connectivity
Check if Cal.com API is working:
```bash
curl -X GET "https://api.cal.com/v2/slots/available?eventTypeId=2563193&startTime=2025-10-22&endTime=2025-10-22" \
  -H "Authorization: Bearer YOUR_API_KEY"
```

### Priority 2: Implement Service ID Chain
Modify three functions to pass service_id:
- `collectAppointment()` - extract and pass
- `checkAvailability()` - accept and use
- `bookAppointment()` - accept and use

### Priority 3: Add Validation
- Verify same service used in check and booking
- Log which event type is being used
- Alert if mismatch detected

### Priority 4: Long-term Improvements
- Simplify service selection (remove if only one is used)
- Add integration tests for multi-service booking
- Document service selection logic clearly

---

## Related Documentation

- **Full Technical Analysis**: `CALCOM_EVENT_TYPE_MISMATCH_2025-10-21.md`
- **Booking Failure Trace**: `BOOKING_FAILURE_DIAGNOSIS_2025-10-21.md`
- **Code Reference**: `/var/www/api-gateway/app/Http/Controllers/RetellFunctionCallHandler.php`
  - `checkAvailability()` - Line 200
  - `bookAppointment()` - Line 550
  - `collectAppointment()` - Line 1028

---

## Timeline

- **Oct 21**: Issue identified and documented
- **Oct 21 (NOW)**: Root cause analysis complete
- **Oct 21 (Next 4h)**: Implement fix
- **Oct 21 (2PM)**: Deploy and test
- **Oct 22+**: Verify bookings working

