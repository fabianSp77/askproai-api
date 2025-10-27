# Code Locations for V4 Agent Critical Bugs

## Bug #1: Hardcoded call_id="1"

### Primary Location
**File**: `/var/www/api-gateway/app/Http/Controllers/RetellFunctionCallHandler.php`

**What to Look For**:
- Function that injects `call_id` into webhook arguments
- Currently hardcoded to "1"
- Should extract from `$webhook->call->call_id` or similar

**Expected Code Pattern** (BROKEN):
```php
// CURRENT (BROKEN):
$args['call_id'] = '1';  // ← THIS IS THE BUG

// SHOULD BE:
$args['call_id'] = $webhook->call->call_id;  // e.g., "call_4fe3efe8beada329a8270b3e8a2"
```

**Related Files**:
- `app/Http/Controllers/Api/RetellApiController.php` - Webhook receiver
- `app/Models/RetellCallSession.php` - Call data model

### How to Verify Fix
```bash
1. Make test call
2. Search logs for check_availability_v17 and book_appointment_v17
3. Verify call_id parameter matches actual call ID (e.g., call_4fe3efe8beada329a8270b3e8a2)
4. NOT "1"
```

---

## Bug #2: Availability Check Returns Wrong Date

### Primary Location
**File**: `/var/www/api-gateway/app/Services/Retell/AppointmentCreationService.php`

**What to Look For**:
- `checkAvailability()` method
- How it processes the `datum` parameter
- How it calls the availability service
- Date parsing and formatting logic

**Expected Code Pattern**:
```php
public function checkAvailability($datum, $dienstleistung, $uhrzeit)
{
    // $datum = "25.10.2025"
    // Should query availability for THIS date
    // Should NOT shift to different date (like 2025-10-27)

    $result = $this->availabilityService->getAvailability(
        $datum,  // ← Verify this is passed correctly
        $dienstleistung,
        $uhrzeit
    );

    // Verify result date matches request date
    return $result;
}
```

### Secondary Locations
**File**: `/var/www/api-gateway/app/Services/Appointments/WeeklyAvailabilityService.php`

**What to Look For**:
- Date parsing logic
- Cal.com API integration
- Date range calculations
- Timezone handling

**File**: `/var/www/api-gateway/app/Services/Retell/DateTimeParser.php`

**What to Look For**:
- How "25.10.2025" string is converted to DateTime
- Any date arithmetic that might add 2 days
- Timezone conversions

### How to Verify Fix
```bash
1. Add debug logging: Log input datum and output date from availability query
2. Make test call requesting 2025-10-25 at 15:00
3. Check logs:
   - Input: datum="25.10.2025"
   - Output: availability results for 2025-10-25 (NOT 2025-10-27)
4. Verify alternatives are for requested date (or no alternatives if available)
```

---

## Bug #3: Booking Confirmation Missing

### Primary Location
**File**: `/var/www/api-gateway/app/Http/Controllers/Api/RetellApiController.php`

**What to Look For**:
- `book-appointment` endpoint handler
- How it processes the booking response
- Whether it logs success/failure
- Whether it sends confirmation to user

**Expected Code Pattern**:
```php
public function bookAppointment(Request $request)
{
    // 1. Receive booking request
    $bookingData = $request->input('args');

    // 2. Create appointment in Cal.com or local DB
    $result = $this->bookingService->createAppointment($bookingData);

    // 3. Log result
    Log::info('Appointment booked', ['result' => $result, 'call_id' => $bookingData['call_id']]);

    // 4. Return confirmation to agent
    return response()->json([
        'success' => true,
        'message' => 'Termin erfolgreich gebucht!',
        'appointment_id' => $result->id
    ]);

    // 5. Send email confirmation (should happen in job/listener)
}
```

### Secondary Locations
**File**: `/var/www/api-gateway/app/Services/Booking/CompositeBookingService.php`

**What to Look For**:
- Cal.com booking integration
- Email sending logic
- Return value from booking

**File**: `app/Jobs/` or `app/Listeners/`

**What to Look For**:
- Email sending after successful booking
- Event listeners for appointment creation
- Job queue for async email

### How to Verify Fix
```bash
1. Make test call and complete booking flow
2. Check logs for:
   - "Appointment booked" message
   - Success/failure indication
   - Email sending log
3. Verify:
   - Appointment created in Cal.com
   - Email sent to customer
   - Confirmation shown in UI
```

---

## Bug #4 (UX): Repetitive Data Collection

### Location
**File**: The Retell conversation flow JSON

**Where to Find**:
- Likely in `/var/www/api-gateway/public/friseur1_flow_*.json`
- Or in Retell dashboard agent configuration
- Or in database table storing conversation flows

**What to Look For**:
- Two separate data collection nodes
- One at start of conversation
- One after first response
- Not preserving collected data

**Pattern**:
```
Initial Inference from User Input:
  - Extract: name="Hans Schuster", service="Herrenhaarschnitt", time="15:00"

Then Flow Goes To:
  - "Buchungsdaten sammeln" (Collect Booking Data) NODE
    - Asks: "Wie ist Ihr Name?" (ignores already-provided name)
    - Asks: "Welches Datum möchten Sie?" (ignores "heute")
    - Asks: "Um wie viel Uhr?" (ignores already-said time)
```

**Fix**:
- Consolidate data collection
- Add state checks before asking
- Skip questions if data already provided

---

## Bug #5 (UX): "heute" Not Parsed

### Location
**File**: `/var/www/api-gateway/app/Services/Retell/DateTimeParser.php`

**What to Look For**:
- German relative date parsing
- "heute" = today
- "morgen" = tomorrow
- Relative date to absolute date conversion

**Current Limitation**:
```php
// Current behavior (WRONG):
parseDate("heute") → Error or null

// Should be:
parseDate("heute") → "25.10.2025" (today's date)
parseDate("morgen") → "26.10.2025" (tomorrow's date)
```

**Fix**:
```php
public function parseDate($input)
{
    // Handle German relative dates
    if ($input === 'heute') {
        return now()->format('d.m.Y');
    }
    if ($input === 'morgen') {
        return now()->addDay()->format('d.m.Y');
    }

    // Handle standard format
    return $input;  // "25.10.2025" already in right format
}
```

---

## Testing Checklist

### Critical Bugs (Must Fix Before Production)

- [ ] **Bug #1 Fix**: Verify call_id is NOT "1" in function logs
  - Check RetellFunctionCallHandler.php
  - Run test call
  - Inspect logs for actual call_id in parameters

- [ ] **Bug #2 Fix**: Verify date parameter is correct in availability response
  - Check AppointmentCreationService.php
  - Request availability for 25.10.2025
  - Verify response has results for 25.10.2025 (not 2025-10-27)

- [ ] **Bug #3 Fix**: Verify booking confirmation is sent
  - Check RetellApiController.php
  - Complete booking flow
  - Verify email sent
  - Verify log shows success

### UX Improvements (Should Fix Before Production)

- [ ] **Bug #4 Fix**: Data collection not asked twice
  - Count how many times agent asks "Wie ist Ihr Name?"
  - Should be asked max 1 time

- [ ] **Bug #5 Fix**: "heute" is understood
  - Request "heute 15:00"
  - Verify agent converts to 25.10.2025 15:00
  - No need for DD.MM.YYYY format request

---

## Files Changed in This Bug

### Likely Culprits
1. `app/Http/Controllers/RetellFunctionCallHandler.php` - Parameter injection
2. `app/Services/Retell/AppointmentCreationService.php` - Availability query
3. `app/Http/Controllers/Api/RetellApiController.php` - Booking handler
4. `app/Services/Retell/DateTimeParser.php` - Date parsing
5. Retell flow JSON configuration - Conversation design

### Lines to Search For
```bash
# Find hardcoded "1" values:
grep -r "call_id.*=.*['\"]1['\"]" app/

# Find availability checks:
grep -r "check_availability" app/

# Find date handling:
grep -r "datum.*2025" app/

# Find booking handlers:
grep -r "book-appointment\|bookAppointment" app/
```

---

## Git Commands to Help

### See Recent Changes
```bash
git log --oneline -20 -- app/Http/Controllers/RetellFunctionCallHandler.php
git log --oneline -20 -- app/Services/Retell/AppointmentCreationService.php
git log --oneline -20 -- app/Http/Controllers/Api/RetellApiController.php
```

### See What Changed in Last Commit
```bash
git show --name-status
git diff HEAD~1 HEAD -- app/Http/Controllers/RetellFunctionCallHandler.php
```

### See Diffs for Specific Files
```bash
git diff -- app/Http/Controllers/RetellFunctionCallHandler.php
git status app/Http/Controllers/RetellFunctionCallHandler.php
```

---

## Log Files to Check

### Real-time Logs
```bash
tail -f storage/logs/laravel.log | grep "check_availability\|book_appointment\|call_id"
```

### Search Specific Call
```bash
grep "call_4fe3efe8beada329a8270b3e8a2" storage/logs/laravel.log
```

### Search for Errors
```bash
grep "ERROR\|Exception\|Error" storage/logs/laravel.log | tail -20
```

---

## Database Queries to Verify

### Check Booking Was Recorded
```sql
SELECT * FROM appointments
WHERE call_id = 'call_4fe3efe8beada329a8270b3e8a2'
LIMIT 10;

-- Or if buggy, check:
SELECT * FROM appointments
WHERE call_id = '1'
ORDER BY created_at DESC
LIMIT 5;
```

### Check Call Session
```sql
SELECT * FROM retell_call_sessions
WHERE retell_call_id = 'call_4fe3efe8beada329a8270b3e8a2';
```

### Check Function Traces
```sql
SELECT * FROM retell_function_traces
WHERE retell_call_id = 'call_4fe3efe8beada329a8270b3e8a2'
ORDER BY created_at;
```

---

## Next Steps

1. **Verify the bugs exist** - Add debug logging and make test call
2. **Locate exact code** - Use grep and git log to find problem locations
3. **Understand the flow** - Read through code path for each bug
4. **Fix the bugs** - Make precise, minimal changes
5. **Test the fixes** - Use testing checklist above
6. **Deploy carefully** - Don't push directly to production without testing

For detailed analysis, see: `/var/www/api-gateway/TESTCALL_RCA_COMPLETE_V4_2025-10-25.md`
