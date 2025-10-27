# API ENDPOINT TRACE - Call 24977a803e9bd80b586fca06259

**User Claim:** "Verfügbarkeitsprüfung ist nicht korrekt" & "Buchung schlägt NOCH IMMER fehl"

**Analysis Date:** 2025-10-25 20:30 CET
**System:** Retell V17 + Cal.com Composite Booking + V85 Race Condition Fix

---

## ENDPOINT 1: check_availability_v17

### Request @ 20:18:32
```json
{
  "name": "check_availability_v17",
  "args": {
    "name": "Hans Schuster",
    "datum": "morgen",
    "dienstleistung": "Herrenhaarschnitt",
    "uhrzeit": "10:00",
    "call_id": ""  // Empty, injected server-side
  }
}
```

### Processing Chain

**1. Parameter Injection (V17 Fix)**
```
call_id: "" → "call_24977a803e9bd80b586fca06259" ✅
bestaetigung: NOT_SET → false ✅
```

**2. DateTime Parsing**
```
Input: "morgen" + "10:00"
Parsed: 2025-10-26 10:00 ✅
Method: parseDateString() with intelligent date handling
```

**3. Company/Branch Resolution**
```
Call ID: call_24977a803e9bd80b586fca06259
Call Record: #754
Company: 1
Branch: 34c4d48e-4753-4715-9c30-c55843a943e8 ✅
```

**4. Service Selection**
```
Input: "Herrenhaarschnitt"
Strategy: Exact name match
Service ID: 42
Service Name: Herrenhaarschnitt
Event Type: 3672814 ✅
Action: Pinned to call session for future use
```

**5. Cal.com Availability Check**
```
Request: 2025-10-26 10:00 for Service #42
Available Slots: ["06:00", "08:30", "13:00", "15:30", "18:00"]
Result: ❌ 10:00 NOT in available slots
```

**6. Alternative Search**
```
Method: AppointmentAlternativeFinder
Workday Check: 2025-10-26 (Sunday) → NOT A WORKDAY
Auto-Adjustment: 2025-10-26 → 2025-10-27 (Monday)
Reason: "Requested date Sun, 26.10.2025 is not a workday"
```

**7. BookingNoticeValidator**
```
❌ NOT CALLED
Evidence: No log entry with "BookingNoticeValidator" or "booking notice"
Bug: V8 Fix not integrated into check_availability_v17 flow
```

**8. Alternatives Found**
```
Count: 2
Times: [
  "2025-10-27 08:30" (verified: true),
  "2025-10-27 06:00" (verified: true)
]
Verification: Cal.com-verified alternatives ✅
```

### Response @ 20:18:33
```json
{
  "success": false,
  "status": "unavailable",
  "message": "Der Termin am morgen um 10:00 ist leider nicht verfügbar. Ich kann Ihnen folgende Alternativen anbieten: am gleichen Tag, 08:30 Uhr oder am gleichen Tag, 06:00 Uhr. Welcher Termin würde Ihnen besser passen?",
  "alternatives": [
    {
      "time": "08:30",
      "date": "27.10.2025",
      "description": "am gleichen Tag, 08:30 Uhr",
      "verified": true
    },
    {
      "time": "06:00",
      "date": "27.10.2025",
      "description": "am gleichen Tag, 06:00 Uhr",
      "verified": true
    }
  ]
}
```

### CRITICAL ANALYSIS

**Why "unavailable" for "morgen 10:00"?**

1. ✅ **CORRECT**: User said "morgen" = Sunday 2025-10-26
2. ✅ **CORRECT**: Sundays are not working days for this salon
3. ✅ **CORRECT**: System auto-adjusted to Monday 2025-10-27
4. ❌ **MISLEADING**: Message says "am gleichen Tag" but means Monday, not Sunday
5. ❌ **BUG**: BookingNoticeValidator NOT called (V8 fix missing)

**System Behavior: CORRECT but messaging confusing**

---

## ENDPOINT 2: book_appointment_v17

### Request @ 20:18:52 (20 seconds later)
```json
{
  "name": "book_appointment_v17",
  "args": {
    "name": "Hans Schuster",
    "datum": "morgen",
    "dienstleistung": "Herrenhaarschnitt",
    "uhrzeit": "08:30",  // User selected first alternative
    "call_id": ""
  }
}
```

### Processing Chain

**1. Parameter Injection**
```
call_id: "" → "call_24977a803e9bd80b586fca06259" ✅
bestaetigung: NOT_SET → true ✅ (book_appointment defaults to true)
```

**2. DateTime Parsing**
```
Input: "morgen" + "08:30"
Parsed: 2025-10-26 08:30
Note: System keeps original "morgen" = 2025-10-26 (Sunday)
```

**3. Service Selection**
```
Source: Pinned from check_availability session
Service ID: 42 (from cache) ✅
Event Type: 3672814 ✅
```

**4. First Availability Check**
```
Time: 20:18:52
Request: 2025-10-26 08:30
Cal.com Response: SLOT FOUND "2025-10-26T08:30:00.000Z"
exactTimeAvailable: true ✅
```

**5. Booking Decision**
```
shouldBook: true
exactTimeAvailable: true
confirmBooking: true
bestaetigung: true (injected) ✅
Entering booking block...
```

**6. V85 Double-Check (Race Condition Prevention)**
```
Time: 20:18:52
Purpose: "Prevent race condition from initial check to booking"
Request: 2025-10-26 08:30
Result: ✅ "Slot STILL available - proceeding with booking"
```

**7. Cal.com Booking Attempt**
```
Time: 20:18:54 (2 seconds later)
Method: POST /bookings
HTTP Status: 400 Bad Request ❌

Error Response:
{
  "code": "BadRequestException",
  "message": "One of the hosts either already has booking at this time or is not available",
  "details": {
    "message": "One of the hosts either already has booking at this time or is not available",
    "error": "Bad Request",
    "statusCode": 400
  }
}
```

**8. Failsafe Callback Creation**
```
Attempt: Create callback request for manual follow-up
Error: "Phone number must be in E.164 format"
from_number: "anonymous" (invalid for callback)
Result: ❌ Failsafe also failed
```

### Response @ 20:18:54
```json
{
  "success": false,
  "status": "error",
  "message": "Es tut mir leid, aber es ist ein unerwarteter Fehler aufgetreten."
}
```

**Agent Says:** "Es tut mir leid, aber es ist ein unerwarteter Fehler aufgetreten."

### ROOT CAUSE ANALYSIS

**Timeline:**
```
20:18:32 → check_availability: 2025-10-26 NOT workday, adjusted to 2025-10-27
20:18:52 → book_appointment: Tries 2025-10-26 08:30 (SUNDAY!)
20:18:52 → First check: Slot exists (Cal.com returns Sunday slots)
20:18:52 → V85 double-check: Slot still there
20:18:54 → Cal.com booking: REJECTED (host not available)
```

**THE BUG: Date Mismatch Between Check & Booking**

1. **check_availability_v17:**
   - User says "morgen" (2025-10-26 Sunday)
   - System recognizes Sunday is NOT workday
   - Auto-adjusts to Monday 2025-10-27
   - Returns alternatives for MONDAY

2. **book_appointment_v17:**
   - User selects "08:30" (from alternatives)
   - System uses ORIGINAL "morgen" = 2025-10-26 (SUNDAY!)
   - Cal.com returns available slot (Sunday exists in calendar)
   - But booking fails: "host not available"

**WHY DOES THIS HAPPEN?**

```php
// check_availability adjusts date:
"morgen" → 2025-10-26 → NOT workday → 2025-10-27 ✅

// book_appointment does NOT adjust:
"morgen" → 2025-10-26 → books Sunday ❌
```

**V85 Double-Check Passed But Booking Failed:**
- V85 checks if slot EXISTS (it does, on Sunday)
- But doesn't check if host is AVAILABLE on that slot
- Cal.com calendar shows slots but staff unavailable on Sundays

---

## FINDINGS SUMMARY

### ✅ WORKING CORRECTLY

1. **Parameter Injection (V17):** call_id and bestaetigung correctly injected
2. **Service Selection:** Exact match, session pinning works
3. **Call Context Resolution:** Company/branch lookup successful
4. **Alternative Finder:** Workday detection and auto-adjustment
5. **V85 Race Condition Fix:** Double-check executed (but checks wrong thing)

### ❌ CRITICAL BUGS

**BUG #1: Date Adjustment Not Carried Forward**
```
check_availability: morgen → 2025-10-27 (adjusted)
book_appointment:   morgen → 2025-10-26 (NOT adjusted)

IMPACT: User books wrong date, Cal.com rejects
FIX NEEDED: Carry forward adjusted date in conversation state
```

**BUG #2: BookingNoticeValidator Not Integrated**
```
Evidence: No log entry for BookingNoticeValidator in check_availability
Expected: Validate requested time against minimum notice period
Impact: False positive "available" for times too soon
Status: V8 fix created but NOT integrated
```

**BUG #3: Confusing Alternative Messaging**
```
Message: "am gleichen Tag, 08:30 Uhr"
Reality: Monday (next day), not "same day"
Impact: User confusion about which day is being booked
```

**BUG #4: V85 Checks Existence, Not Availability**
```
Current: Checks if slot EXISTS in calendar
Needed: Check if slot is AVAILABLE (staff free)
Impact: False positive for slots with unavailable staff
```

**BUG #5: Anonymous Caller Failsafe**
```
Error: Cannot create callback for "anonymous" number
Impact: No manual follow-up when booking fails
Status: Known issue from earlier fixes
```

---

## RECOMMENDED FIXES

### Priority 1: Date Adjustment Persistence

**Problem:** check_availability adjusts date, book_appointment doesn't use it

**Solution:**
```php
// In check_availability_v17:
if ($dateWasAdjusted) {
    Cache::put(
        "call:{$callId}:adjusted_date",
        $adjustedDate->toDateString(),
        now()->addMinutes(10)
    );
}

// In book_appointment_v17:
$adjustedDate = Cache::get("call:{$callId}:adjusted_date");
if ($adjustedDate) {
    $appointmentDateTime = Carbon::parse($adjustedDate . ' ' . $uhrzeit);
}
```

### Priority 2: Integrate BookingNoticeValidator

**File:** `/var/www/api-gateway/app/Http/Controllers/RetellFunctionCallHandler.php`

**Location:** After datetime parsing in check_availability_v17

```php
// Add after line where $requestedDateTime is created
$validator = new \App\Services\Booking\BookingNoticeValidator();
$validation = $validator->validateBookingNotice($requestedDateTime, $service, $branchId);

if (!$validation['valid']) {
    $alternatives = $validator->suggestAlternatives($requestedDateTime, $service, $branchId);
    return response()->json([
        'success' => false,
        'status' => 'too_soon',
        'message' => $validator->formatErrorMessage($validation, $alternatives),
        'alternatives' => $alternatives
    ]);
}
```

### Priority 3: Fix V85 Double-Check

**Current:** Checks if slot exists
**Needed:** Check if slot is bookable (staff available)

```php
// In V85 double-check:
$availability = $this->calcomService->checkSlotBookability([
    'eventTypeId' => $eventTypeId,
    'startTime' => $requestedDateTime->toIso8601String(),
    'checkStaffAvailability' => true  // ← NEW
]);

if (!$availability['bookable']) {
    Log::warning('⚠️ V85: Slot exists but not bookable (staff unavailable)', [
        'reason' => $availability['reason']
    ]);
    return error('Slot unavailable');
}
```

### Priority 4: Fix Alternative Messaging

```php
// In alternative description:
$isNextDay = !$suggestionTime->isSameDay($requestedTime);
$dayDescriptor = $isNextDay ? 'am nächsten Tag' : 'am gleichen Tag';
```

---

## VERIFICATION CHECKLIST

- [ ] BookingNoticeValidator integrated into check_availability_v17
- [ ] Date adjustment persisted via Cache (10min TTL)
- [ ] book_appointment reads adjusted date from cache
- [ ] V85 checks staff availability, not just slot existence
- [ ] Alternative messages distinguish same day vs next day
- [ ] Test with "morgen" on Sunday → should book Monday
- [ ] Test with time violating minimum notice → should reject
- [ ] Test double booking → V85 should prevent

---

## ANSWER TO USER CLAIM

**Claim 1: "Verfügbarkeitsprüfung ist nicht korrekt"**

❌ **PARTIALLY FALSE:** Availability check IS correct - correctly identified Sunday 2025-10-26 as non-workday and offered Monday alternatives.

✅ **PARTIALLY TRUE:** Messaging is confusing ("am gleichen Tag" when it's actually next day).

❌ **BUG CONFIRMED:** BookingNoticeValidator NOT integrated, could give false positives for times too soon.

**Claim 2: "Buchung schlägt NOCH IMMER fehl"**

✅ **TRUE - ROOT CAUSE FOUND:**

Date adjustment from check_availability (Sunday → Monday) was NOT carried forward to book_appointment. System tried to book Sunday 2025-10-26 08:30, which exists in calendar but staff unavailable.

**The Fix:**
- Persist adjusted date in cache
- Read from cache in booking
- Or better: Use conversation variables to pass adjusted date

---

## FILES TO MODIFY

1. `/var/www/api-gateway/app/Http/Controllers/RetellFunctionCallHandler.php`
   - Integrate BookingNoticeValidator
   - Add date adjustment persistence
   - Fix V85 double-check

2. `/var/www/api-gateway/app/Services/AppointmentAlternativeFinder.php`
   - Fix alternative messaging ("am nächsten Tag")

3. `/var/www/api-gateway/app/Services/CalcomService.php`
   - Add `checkStaffAvailability` parameter to slot check

---

**Generated:** 2025-10-25 20:30 CET
**By:** Backend System Architect (API Endpoint Trace Analysis)
