# Type Mismatch Fix: UUID vs Integer

**Date:** 2025-10-01 08:07:55
**Severity:** 🔴 CRITICAL - Complete booking failure
**Status:** ✅ Fixed

## Problem Summary

Test call at 08:07:55 failed completely with TypeError when trying to create a call record during appointment booking. The agent experienced "technische Schwierigkeiten" and could not process the booking despite multiple attempts.

### Error Message
```
TypeError: App\Services\Retell\CallLifecycleService::createCall():
Argument #3 ($phoneNumberId) must be of type ?int, string given,
called in /var/www/api-gateway/app/Http/Controllers/RetellFunctionCallHandler.php on line 740
```

### Test Call Details (Call ID: 532)
- **User:** Hans Schulze
- **Service:** Beratung
- **Requested Date:** "heute" / "01.10.2025"
- **Phone Number Found:** ✅ `03513893-d962-4db0-858c-ea5b0e227e9a`
- **Company ID Found:** ✅ 15
- **Booking Result:** ❌ Complete failure - TypeError

## Root Cause Analysis

### Database Schema vs Type Hints Mismatch

**Database Structure:**
```sql
phone_numbers.id: char(36)  -- UUID
branches.id: char(36)        -- UUID
companies.id: bigint(20)     -- Integer ✓
calls.phone_number_id: char(36)  -- UUID
calls.branch_id: char(36)    -- UUID
calls.company_id: bigint(20) -- Integer ✓
```

**Method Signatures (BEFORE FIX):**
```php
public function createCall(
    array $callData,
    ?int $companyId = null,
    ?int $phoneNumberId = null,  // ❌ Expected int, got UUID string
    ?int $branchId = null         // ❌ Expected int, got UUID string
): Call
```

### Why This Failed

1. System successfully looked up PhoneNumber record
2. Retrieved `phone_number_id = "03513893-d962-4db0-858c-ea5b0e227e9a"` (UUID string)
3. Attempted to call `createCall($callData, 15, "03513893-...", null)`
4. **PHP Type System rejected call**: String passed where int expected
5. **Result:** TypeError thrown, call record not created, booking failed

## Solution Implemented

### Files Modified

1. **CallLifecycleInterface.php** (Lines 40-41, 47-48, 59-60, 68-69)
2. **CallLifecycleService.php** (Lines 69-70, 116-117)

### Type Hints Changed

**BEFORE:**
```php
// Interface
public function createCall(
    array $callData,
    ?int $companyId = null,
    ?int $phoneNumberId = null,  // ❌
    ?int $branchId = null         // ❌
): Call;

public function createTemporaryCall(
    string $fromNumber,
    string $toNumber,
    ?int $companyId = null,
    ?int $phoneNumberId = null,  // ❌
    ?int $branchId = null,        // ❌
    ?string $agentId = null
): Call;
```

**AFTER:**
```php
// Interface
public function createCall(
    array $callData,
    ?int $companyId = null,
    ?string $phoneNumberId = null,  // ✅ Now accepts UUID
    ?string $branchId = null         // ✅ Now accepts UUID
): Call;

public function createTemporaryCall(
    string $fromNumber,
    string $toNumber,
    ?int $companyId = null,
    ?string $phoneNumberId = null,  // ✅ Now accepts UUID
    ?string $branchId = null,        // ✅ Now accepts UUID
    ?string $agentId = null
): Call;
```

### Affected Call Sites (All Compatible)

All call sites already pass variables directly, so no changes needed:

1. **RetellFunctionCallHandler.php:740**
   ```php
   $call = $this->callLifecycle->createCall([...], $companyId, $phoneNumberId);
   // $phoneNumberId is already UUID string from database lookup
   ```

2. **RetellWebhookController.php:168**
   ```php
   $call = $this->callLifecycle->createTemporaryCall(
       $fromNumber, $toNumber, $companyId, $phoneNumberId, $branchId, $agentId
   );
   ```

3. **RetellWebhookController.php:362**
   ```php
   $call = $this->callLifecycle->createCall($callData, $companyId, $phoneNumberId, $branchId);
   ```

4. **RetellWebhookController.php:490**
   ```php
   $call = $this->callLifecycle->createCall($callData, 1); // Only companyId
   ```

## Verification

- ✅ PHP Syntax: No errors
- ✅ Type System: Now accepts UUID strings for phone_number_id and branch_id
- ✅ Database Schema: Matches actual table structure
- ✅ Existing Callers: All compatible (pass variables directly)

## Combined Fixes in This Session

### Fix 1: Date Parsing (Previous)
- ✅ `parseDateString()` method created
- ✅ Handles "heute", "01.10.2025", etc.
- ✅ Applied to 3 database write locations

### Fix 2: Type Mismatch (Current)
- ✅ Interface type hints corrected
- ✅ Service implementation type hints corrected
- ✅ Both methods updated: `createCall()` and `createTemporaryCall()`

## Expected Behavior After Fix

When the next test call is made:

1. ✅ Phone number lookup succeeds → Returns UUID string
2. ✅ `createCall()` accepts UUID string → Call record created
3. ✅ Date parsing works → "heute" → "2025-10-01"
4. ✅ Appointment booking proceeds → Cal.com API called
5. ✅ User receives confirmation

## Test Transcript Analysis

From Call ID 532 transcript, the agent tried multiple times:

```
Agent: "prüfe mal, ob wir heute noch einen freien Termin haben."
Agent: "Herr Schulze, sind Sie noch da? Ich habe gerade ein technisches Problem."
Agent: "Moment bitte, ich prüfe den 01.10.2025 für Sie."
Agent: "Herr Schulze, sind Sie noch am Apparat? Ich habe hier gerade technische Schwierigkeiten."
```

Each attempt triggered the TypeError, causing the agent to report "technical difficulties" to the user.

## Files Changed

1. `/var/www/api-gateway/app/Services/Retell/CallLifecycleInterface.php`
   - Lines 40-41: Updated PHPDoc
   - Lines 47-48: Changed type hints ?int → ?string
   - Lines 59-60: Updated PHPDoc
   - Lines 68-69: Changed type hints ?int → ?string

2. `/var/www/api-gateway/app/Services/Retell/CallLifecycleService.php`
   - Lines 69-70: Changed type hints ?int → ?string
   - Lines 116-117: Changed type hints ?int → ?string

## Related Issues

This was the second critical bug found today:
1. **First:** Date parsing issue (German dates not converted to MySQL format)
2. **Second:** Type mismatch (UUID strings rejected by int type hints)

Both are now resolved.
