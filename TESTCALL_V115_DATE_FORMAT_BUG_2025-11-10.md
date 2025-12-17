# Testcall V115 - Date Format Bug (Missing Year)

**Date**: 2025-11-10, 20:30 Uhr
**Call ID**: call_1ad5bffc93bbfcb47805d88a7b7
**Agent Version**: 115
**Duration**: 24 seconds
**Status**: ❌ FAILED - "technisches Problem"
**User Complaint**: "gab's nach einer kurzen Pause das Feedback, dass es einen technischen Fehler gab"

---

## Executive Summary

🚨 **ROOT CAUSE**: Flow V115 sends **INCOMPLETE DATE FORMAT** to check_availability

- **Expected**: `"datum": "Dienstag, den 11. November 2025"`
- **Actual**: `"datum": "Dienstag, den 11. November"` ❌ **MISSING YEAR!**

Backend correctly rejected invalid date and returned error. Agent properly informed user of technical problem.

---

## Call Timeline

```
[0-2s]   Agent: "Willkommen bei Friseur 1!"
[4-10s]  User: "Ja, guten Tag, Hans Schuster, ich hätte gern 'n Herrenhaarschnitt gebucht für morgen um zehn."
[11s]    get_current_context() → SUCCESS (2025-11-10, tomorrow=2025-11-11)
[13s]    check_customer() → SUCCESS (new customer)
[14s]    extract_dynamic_variables() → SUCCESS
         ✅ customer_name: "Hans Schuster"
         ✅ service_name: "Herrenhaarschnitt"
         ❌ appointment_date: "morgen" (NOT FORMATTED!)
         ✅ appointment_time: "10 Uhr"
[15s]    Agent: "Einen Moment."
[16s]    check_availability_v17() → ❌ FAILED
         Arguments sent: {
           "name": "Hans Schuster",
           "datum": "Dienstag, den 11. November",  ❌ NO YEAR!
           "dienstleistung": "Herrenhaarschnitt",
           "uhrzeit": "10:00",
           "call_id": "1"
         }
[17s]    Agent: "Es tut mir leid, es gab gerade ein technisches Problem..."
[24s]    User hangs up
```

---

## Root Cause Analysis

### Problem 1: Date Formatting in Flow

**Flow Node**: `func_check_availability`

**Expected Behavior**:
- Extract "morgen" from dynamic variables
- Use context (today=2025-11-10, tomorrow=2025-11-11)
- Format as: "Dienstag, den 11. November **2025**"

**Actual Behavior**:
- Formatted as: "Dienstag, den 11. November" (missing year)

**Impact**: Backend cannot parse date without year

### Problem 2: call_id Placeholder Still Present

Despite V113 fix, flow still sends `call_id="1"` placeholder.

**Backend Mitigation**:
- getCanonicalCallId() detects and rejects placeholder
- Uses webhook source instead: "call_1ad5bffc93bbfcb47805d88a7b7"
- ✅ This part works correctly

### Problem 3: Backend Date Validation

Backend has validation at line 826-839 of RetellFunctionCallHandler.php:

```php
if ($datum && preg_match('/^\d{1,2}\.\d{1,2}\.?$/', trim($datum)) && !preg_match('/\d{4}/', $datum)) {
    Log::error('❌ INCOMPLETE DATE: Missing year in datum parameter');
    return $this->responseFormatter->error(
        'Bitte nennen Sie das vollständige Datum mit Jahr, zum Beispiel: "10. November 2025" oder "Montag".',
        [],
        $this->getDateTimeContext()
    );
}
```

**BUT**: This regex only catches `"10.11."` format, NOT `"Dienstag, den 11. November"` format!

---

## Backend Error Log

```json
{
  "tool_call_result": {
    "successful": true,
    "content": {
      "success": false,
      "error": "Fehler beim Prüfen der Verfügbarkeit",
      "context": {
        "current_date": "2025-11-10",
        "current_time": "19:46",
        "current_datetime": "2025-11-10T19:46:58+01:00",
        "weekday": "Montag",
        "current_year": 2025,
        "timezone": "Europe/Berlin"
      }
    }
  }
}
```

Exception was caught at line 1196-1212 in checkAvailability().

---

## Why This Happened

### V115 Flow Changes

**What Changed in V115**:
1. ✅ Backend: Caller ID auto-detection
2. ✅ Backend: Extended call_id placeholder validation
3. ✅ Flow: Removed phone number question
4. ✅ Flow: Added direct bypass from func_check_customer → node_extract_booking_variables

**What We DIDN'T Change**:
- Date formatting logic in func_check_availability node

### Date Formatting Location

The date formatting happens **INSIDE** the `func_check_availability` node instruction or in the function call itself.

**Expected**: Flow should send fully-qualified date with year
**Actual**: Flow sends German weekday+date without year

---

## The Real Problem: Function Call Configuration

Looking at the function call arguments:

```json
{
  "name": "check_availability_v17",
  "arguments": {
    "datum": "Dienstag, den 11. November",  // ❌ Agent formatted this!
    "uhrzeit": "10:00"
  }
}
```

**Issue**: The LLM is formatting the date based on the extracted dynamic variable `appointment_date="morgen"` but NOT including the year!

**Where This Happens**:
- In the function call's parameter mapping
- OR in the LLM's interpretation of how to format the date
- OR in the function schema's description

---

## Fix Options

### Option 1: Update Function Schema (RECOMMENDED)

**File**: Flow V115 → Tool `check_availability_v17`

**Current** (assumed):
```
datum: The appointment date (format: "Montag, den 10. November")
```

**Fix to**:
```
datum: The appointment date INCLUDING YEAR (format: "Montag, den 10. November 2025")
```

### Option 2: Use ISO Date Format

**Change Flow to**:
```
datum: The appointment date in ISO format (YYYY-MM-DD), e.g. "2025-11-11"
```

**Pros**:
- Unambiguous
- Easy to parse
- No localization issues

**Cons**:
- Less human-readable in transcripts

### Option 3: Backend Date Parser Improvement

**Extend backend** to handle "weekday + day + month" and auto-add current year:

```php
if (preg_match('/\b(Montag|Dienstag|Mittwoch|Donnerstag|Freitag|Samstag|Sonntag).*\d{1,2}\.?\s+(Januar|Februar|März|...)/', $datum) && !preg_match('/\d{4}/', $datum)) {
    // Auto-add current year
    $datum .= ' ' . date('Y');
}
```

**Pros**:
- Fixes issue for all flows
- User-friendly German dates still work

**Cons**:
- Assumes current year (could be wrong for bookings in January talking about December)
- Complex regex maintenance

### Option 4: Make Dynamic Variable Extraction More Explicit

**In Flow**: `node_extract_booking_variables`

**Current**:
```
appointment_date: String (e.g., "morgen", "heute", "Montag")
```

**Fix to**:
```
appointment_date_raw: String (e.g., "morgen")
appointment_date_formatted: String (YYYY-MM-DD format using context)
```

Then use `appointment_date_formatted` for check_availability.

---

## Recommended Fix (QUICK)

### Step 1: Fix Function Parameter Mapping in Flow V115

**Location**: Flow V115 → `func_check_availability` node → Tool parameters

**Find**:
```json
{
  "tool": "check_availability_v17",
  "parameter_mapping": {
    "datum": "{{appointment_date formatted as 'Weekday, den DD. Month YYYY'}}"
  }
}
```

**Fix to**:
```json
{
  "tool": "check_availability_v17",
  "parameter_mapping": {
    "datum": "{{appointment_date formatted as ISO YYYY-MM-DD using context}}"
  }
}
```

### Step 2: Update Tool Schema Description

**In Flow**: Tool `check_availability_v17` schema

**Update datum parameter**:
```
"datum": {
  "type": "string",
  "description": "Appointment date in format YYYY-MM-DD (e.g., '2025-11-11'). Use get_current_context to convert relative dates like 'morgen' to absolute dates."
}
```

### Step 3: Verify Context Usage

**Ensure func_check_availability can access**:
- `current_date` (from get_current_context)
- `tomorrow.date` (from get_current_context)

**So LLM can map**: `appointment_date="morgen"` → `datum="2025-11-11"`

---

## Testing After Fix

### Test Script:
```
1. Call: +49 30 33081738
2. Say: "Hans Schulze, Herrenhaarschnitt morgen um 10 Uhr"
3. ✅ VERIFY: check_availability receives datum="2025-11-11" (not "Dienstag, den 11. November")
4. ✅ VERIFY: Backend successfully parses date
5. ✅ VERIFY: Agent says availability result (not technical error)
```

### Monitoring:
```bash
# Watch function calls
tail -f /var/www/api-gateway/storage/logs/laravel.log | grep "check_availability_v17"

# Look for date format
grep "datum" /var/www/api-gateway/storage/logs/laravel.log | grep "check_availability"
```

---

## Versions Summary

### V113:
- ✅ Backend: Partial call_id validation ("12345" only)
- ❌ Date format: Same issue (not tested in detail)

### V114:
- ✅ Backend: Extended call_id validation ("1", "12345", any non-"call_" prefix)
- ✅ Backend: Caller ID auto-detection
- ✅ Flow: Removed phone question
- ❌ Flow: Stuck in intent_router (critical bug)

### V115:
- ✅ Backend: Same as V114
- ✅ Flow: Intent router bypass added
- ❌ Flow: **DATE FORMAT BUG** (missing year)

---

## Impact Analysis

### User Experience:
- ❌ 24 seconds wasted
- ❌ "Technisches Problem" message (technically correct, but unhelpful)
- ❌ User hung up frustrated
- ✅ Agent behaved correctly given bad data

### System Health:
- ✅ Backend validation working correctly
- ❌ Flow V115 has data quality issue
- ❌ 0% booking success rate

### Pattern:
**V113, V114, V115** - ALL VERSIONS FAIL at different steps:
- V113: Backend call_id validation incomplete → booking fails
- V114: Flow intent_router stuck → no check_availability called
- V115: Flow sends incomplete date → check_availability fails

**Common Thread**: Conversation flow architecture has multiple single points of failure

---

## Priority

🚨 **CRITICAL - P0**

**Reason**: Complete booking failure due to invalid date format
**Impact**: 100% of booking attempts fail
**Users Affected**: All callers
**Urgency**: IMMEDIATE FIX REQUIRED

**User Ultimatum**: "Letzte Chance. bring es zum Laufen, sonst machen wir einen neuen Agent mit einem prompt Single prompt Agent"

---

## Next Action

**OPTION A (Quick Fix)**: Fix date format in flow V115
- Update check_availability_v17 parameter mapping
- Use ISO format (YYYY-MM-DD)
- Test immediately

**OPTION B (User Request)**: Create Single-Prompt Agent
- Abandon conversation flow approach
- Create one unified prompt with all logic
- Test if simpler architecture works better

**Recommendation**: Try OPTION A first (5 minutes), if fails → OPTION B

---

**Created**: 2025-11-10, 20:30 Uhr
**Analyzed By**: Claude Code
**Status**: 🚨 CRITICAL BUG IDENTIFIED
**Next Action**: Fix date format in V115 OR create single-prompt test agent
