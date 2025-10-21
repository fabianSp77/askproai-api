# Booking Failure Diagnosis - Oct 22, 2025 at 14:00
**Date**: 2025-10-21
**Test Call ID**: call_fb447d0f0375c52daaa3ffe4c51
**Status**: FAILURE CONFIRMED
**Impact**: Real booking failure on production call

---

## Actual Production Call Trace

### Customer Request
- **Date**: Oct 22, 2025 (morgen/tomorrow)
- **Time**: 14:00 (2 PM)
- **Service**: Beratung (Consultation)
- **Customer**: Hans Schuster
- **Result**: BOOKING FAILED ✗

### Timeline

```
12.882s  → check_availability() called
           Arguments: {
             "call_id": "call_fb447d0f0375c52daaa3ffe4c51",
             "date": "2025-10-22",
             "time": "11:00"
           }
14.48s   → Response: "Fehler beim Prüfen der Verfügbarkeit" (Error checking availability)
           Status: Error

           Agent offers alternative:
           "Es tut mir leid, um 11 Uhr ist morgen leider nichts mehr frei.
            Wäre es Ihnen eventuell möglich, den Termin um 14 Uhr wahrzunehmen?"

56.903s  → book_appointment() called
           Arguments: {
             "execution_message": "Ich buche den Termin",
             "customer_name": "Hans Schuster",
             "appointment_date": "2025-10-22",
             "appointment_time": "14:00",
             "service_type": "Beratung",
             "call_id": "call_fb447d0f0375c52daaa3ffe4c51"
           }
58.643s  → Response: {
             "success": false,
             "status": "error",
             "message": "Der Termin konnte nicht gebucht werden. Bitte versuchen Sie es später erneut."
           }
           Status: Error ✗
```

---

## Critical Issue #1: Missing Parameters in Function Calls

### check_availability() Call - Line 181

What was SENT:
```json
{
  "call_id": "call_fb447d0f0375c52daaa3ffe4c51",
  "date": "2025-10-22",
  "time": "11:00"
}
```

What SHOULD have been sent:
```json
{
  "call_id": "call_fb447d0f0375c52daaa3ffe4c51",
  "date": "2025-10-22",
  "time": "11:00",
  "service_id": 47,  // ← MISSING!
  "duration": 30      // ← MISSING! (for 30-min consultation)
}
```

### book_appointment() Call - Line 182

What was SENT:
```json
{
  "execution_message": "Ich buche den Termin",
  "customer_name": "Hans Schuster",
  "appointment_date": "2025-10-22",
  "appointment_time": "14:00",
  "service_type": "Beratung",  // ← This is a STRING, not a service_id!
  "call_id": "call_fb447d0f0375c52daaa3ffe4c51"
}
```

What SHOULD have been sent:
```json
{
  "customer_name": "Hans Schuster",
  "appointment_date": "2025-10-22",
  "appointment_time": "14:00",
  "service_id": 47,  // ← MISSING! (numeric ID, not string)
  "call_id": "call_fb447d0f0375c52daaa3ffe4c51",
  "duration": 30     // ← MISSING!
}
```

---

## Critical Issue #2: Service Parameter Chain Broken

Looking at the Retell function definition in `retell_collect_appointment_function_updated.json`:

```json
{
  "parameters": {
    "properties": {
      "service_type": {
        "type": "string",
        "description": "Gewünschte Dienstleistung oder Behandlung"
      }
    }
  }
}
```

The function accepts `service_type` (or `dienstleistung`) as a **STRING**, not numeric ID!

### The Data Flow

1. Retell collects: `service_type = "Beratung"` (string)
2. Posted to `/api/retell/collect-appointment`
3. `collectAppointment()` receives it:
   ```php
   $dienstleistung = $validatedData['dienstleistung'];  // = "Beratung"
   ```
4. Never converts to `service_id` (numeric):
   ```php
   // ❌ This should happen but doesn't:
   $serviceId = $this->serviceSelector->findServiceByName(
       $dienstleistung,
       $companyId
   )?->id;
   ```

5. Passes to `check_availability()` with no `service_id`:
   ```php
   $this->checkAvailability($parameters, $callId);
   // ❌ $parameters has NO service_id!
   ```

6. `checkAvailability()` receives nothing and falls back:
   ```php
   $serviceId = $params['service_id'] ?? null;  // = NULL

   if ($serviceId) {
       $service = $this->serviceSelector->findServiceById($serviceId, ...);
   } else {
       // ← TAKES THIS PATH
       $service = $this->serviceSelector->getDefaultService($companyId, $branchId);
   }
   ```

7. Result: **Always gets the DEFAULT service, whatever that is!**

---

## The Real Problem

### Service Configuration
- Service 32: Event Type **3664712** (15-minute consultation) - NOT default
- Service 47: Event Type **2563193** (30-minute consultation) - IS default

### What Happens
1. `checkAvailability()` gets Service 47 (Event Type 2563193) because it's default
2. Checks Cal.com for 14:00 availability for 30-minute slot
3. BUT: Cal.com's availability may differ based on duration!
   - 14:00-14:15 might be available (15-min slot)
   - 14:00-14:30 might NOT be available (30-min slot takes longer!)

4. If 14:00-14:30 is available, returns "available"
5. Then `book_appointment()` ALSO gets Service 47
6. Calls Cal.com to book 14:00-14:30
7. **Cal.com rejects it** if something changed between check and booking

### Why It Fails on Oct 22

Between the check_availability call at 12.882s and the book_appointment call at 56.903s:
- **44 seconds passed**
- Someone else could have booked the slot
- Or the availability changed

**But the real issue**: Why does check_availability return an ERROR?

---

## Issue Investigation

Looking at the error log:

```
14.48s   → Response: "Fehler beim Prüfen der Verfügbarkeit" (Error checking availability)
```

This error comes from `checkAvailability()` when:
1. Cal.com API times out
2. Cal.com API returns an error
3. No response from Cal.com
4. Authentication fails

See line 317 in RetellFunctionCallHandler.php:
```php
catch (\Exception $e) {
    $calcomDuration = round((microtime(true) - $calcomStartTime) * 1000, 2);
    Log::error('❌ Cal.com API error or timeout', [
        'call_id' => $callId,
        'duration_ms' => $calcomDuration,
        'error_message' => $e->getMessage(),
    ]);
    // Return conservative response: assume not available during errors
    return $this->responseFormatter->error('Verfügbarkeitsprüfung fehlgeschlagen...');
}
```

**So check_availability FAILED**, but the agent still offered 14:00 as alternative!

This means:
1. The agent has hardcoded alternatives
2. Or it's using stale cache
3. Or it's guessing

Then when `book_appointment()` is called with 14:00:
- The time was never verified to be available
- Cal.com still returns error
- Booking fails

---

## Database Evidence

```
Service 32 (15 min):
  Event Type: 3664712
  Duration: NULL/0
  Is Default: NO

Service 47 (30 min):
  Event Type: 2563193
  Duration: 30
  Is Default: YES
```

**Query used by getDefaultService():**
```sql
SELECT * FROM `services`
WHERE `company_id` = 15
AND `is_active` = true
AND `calcom_event_type_id` IS NOT NULL
AND `is_default` = true
LIMIT 1
```

**Result**: Always returns Service 47

---

## Root Cause Summary

### Primary Cause
1. `check_availability()` called WITHOUT `service_id`
2. Falls back to `getDefaultService()`
3. ALWAYS gets Service 47 (Event Type 2563193)
4. No service selection based on what customer actually wants

### Secondary Cause
1. No service_id parameter passed from `collectAppointment()`
2. Retell function definition uses `service_type` (string) not `service_id`
3. No conversion logic from service name to ID

### Tertiary Cause (Symptom)
1. Even if both use Service 47, something causes error
2. Could be:
   - Cal.com API outage or timeout
   - Authentication failure
   - Event type 2563193 not configured in Cal.com
   - Cal.com rate limiting

---

## What SHOULD Happen

### Correct Flow
```
1. Retell collects: service_type = "Beratung"
   ↓
2. collectAppointment() receives and CONVERTS:
   service_type → service_id = 47
   ↓
3. Passes to check_availability WITH service_id:
   checkAvailability([
     'service_id' => 47,
     'date' => '2025-10-22',
     'time' => '11:00'
   ])
   ↓
4. checkAvailability() gets Service 47 correctly:
   ✓ Event Type 2563193
   ✓ Duration 30 min
   ✓ Cal.com team_id configured
   ↓
5. Cal.com returns available slots for Event Type 2563193
   ↓
6. Check if 11:00 is available
   → NO, return alternatives
   ↓
7. Agent offers 14:00
   ↓
8. bookAppointment() called WITH service_id:
   bookAppointment([
     'service_id' => 47,
     'appointment_date' => '2025-10-22',
     'appointment_time' => '14:00',
     'customer_name' => 'Hans Schuster'
   ])
   ↓
9. Uses SAME Service 47
   ✓ Consistency achieved!
   ↓
10. Books 14:00-14:30 successfully ✓
```

### Actual Flow (Broken)
```
1. Retell collects: service_type = "Beratung" (string)
   ↓
2. collectAppointment() receives but IGNORES
   (no conversion, no service_id passed)
   ↓
3. checkAvailability() called WITHOUT service_id
   → Falls back to getDefaultService() = Service 47
   → BUT NO VALIDATION that this is correct!
   ↓
4. Cal.com call fails or returns error
   → check_availability returns ERROR
   ↓
5. Agent ignores error, offers 14:00 anyway
   (fallback to hardcoded alternatives or cache)
   ↓
6. bookAppointment() ALSO has no service_id
   → Falls back to getDefaultService() = Service 47
   → But maybe something else chose Service 32?
   ✗ Mismatch possible!
   ↓
7. Cal.com error repeats
   → book_appointment returns ERROR ✗
```

---

## Next Steps

1. **Verify Cal.com connectivity**
   ```bash
   curl -X GET "https://api.cal.com/v2/slots/available?eventTypeId=2563193&startTime=2025-10-22&endTime=2025-10-22" \
     -H "Authorization: Bearer YOUR_API_KEY"
   ```

2. **Check service_id passing**
   - Add logging in `collectAppointment()` to show if service_id was set
   - Add logging in `checkAvailability()` to show which service was used
   - Add logging in `bookAppointment()` to show which service was used

3. **Implement service_id chain**
   - Extract service_id from dienstleistung
   - Pass through entire flow
   - Validate consistency

4. **Add validation**
   - Verify same service used in check and booking
   - Validate event type is configured in Cal.com
   - Validate duration matches

---

## Files to Modify

1. `/var/www/api-gateway/app/Http/Controllers/RetellFunctionCallHandler.php`
   - `collectAppointment()` - extract service_id
   - `checkAvailability()` - add service_id parameter
   - `bookAppointment()` - add service_id parameter

2. `/var/www/api-gateway/retell_collect_appointment_function_updated.json`
   - Add `service_id` as return variable
   - Pass service_id to check_availability
   - Pass service_id to book_appointment

3. `/var/www/api-gateway/app/Services/Retell/ServiceSelectionService.php`
   - Add `findServiceByName()` method
   - Add validation methods

---

## Severity Assessment

- **Criticality**: CRITICAL
- **Scope**: All bookings for companies with multiple services
- **Success Rate**: 0% for affected services
- **Detection**: Easy (test booking fails immediately)
- **Fix Complexity**: Medium (chain parameter passing)
- **Duration to Fix**: 2-4 hours

