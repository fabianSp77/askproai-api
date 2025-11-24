# ROOT CAUSE ANALYSIS: Call ID Regression - Agent V7 sends "call_001"

**Date**: 2025-11-23 21:51 CET
**Call ID**: call_910361f460a999429d37699b3ac
**Severity**: 🚨 CRITICAL - REGRESSION
**Impact**: Same call_id bug returned after Agent publish

---

## Executive Summary

**✅ SUCCESS**: Availability overlap fix deployed and working
**❌ REGRESSION**: Agent V7 sends `"call_id": "call_001"` instead of real call ID
**Impact**: `check_availability_v17` fails with "Call context not available"

**Root Cause**: Retell Agent V7 flow still has hardcoded placeholder `"call_001"` in parameter mappings

---

## Call Timeline

```
21:51:15 - Call starts (call_910361f460a999429d37699b3ac)
21:51:23 - get_current_context called
           Arguments: {"call_id": "call_001"}  ← PLACEHOLDER!
           Result: SUCCESS (fallback logic worked)
           Returned: "call_id": "call_910361f460a999429d37699b3ac"

User input: "Siegfried Reu, Dauerwelle, kommenden Mittwoch fünfzehn Uhr"

17.19s - check_availability_v17 called (ATTEMPT 1)
         Arguments: {
           "name": "Siegfried Reu",
           "datum": "2025-11-26",
           "dienstleistung": "Dauerwelle",
           "uhrzeit": "15:00",
           "call_id": "call_001",  ← WRONG!
           "execution_message": "Gerne, einen Moment, ich schaue kurz im Kalender nach..."
         }
18.46s - check_availability_v17 FAILED
         Error: "Call context not available"

43.38s - check_availability_v17 called (ATTEMPT 2 - Retell auto-retry)
         Arguments: {
           "call_id": "call_001"  ← STILL WRONG!
         }
44.59s - check_availability_v17 FAILED
         Error: "Call context not available"

46.02s - Agent: "Es tut mir leid, ich habe gerade Schwierigkeiten, den Kalender zu prüfen."

49.28s - Call ends (user hangup)
```

---

## Technical Details

### Agent Information

- **Agent ID**: agent_bcd2fe2aea6c8e38533b2269e1
- **Agent Version**: 7
- **Agent Name**: "Friseur 1 Agent V122 - UX Polish (Flow V81)"
- **Flow Version**: V81 (old flow, NOT our updated V3)

### Call ID Mismatch

**Expected**: `"call_id": "call_910361f460a999429d37699b3ac"`
**Actual**: `"call_id": "call_001"`

### Why get_current_context Worked

```php
// RetellFunctionCallHandler.php:133
$placeholders = ['dummy_call_id', 'None', 'current', 'current_call', 'call_1'];
```

**Problem**: `"call_001"` is NOT in the placeholders list!

We added `'call_1'` but this Agent sends `'call_001'` (with extra zero).

### Collected Variables

```json
{
  "customer_name": "Siegfried Reu",
  "predicted_service": null,
  "service_confidence": "0",
  "preferred_staff_id": null,
  "customer_found": "false",
  "customer_phone": "0151123456",
  "customer_email": null,
  "service_name": "Dauerwelle",
  "appointment_date": "kommenden Mittwoch",
  "appointment_time": "fünfzehn Uhr"
}
```

✅ All extraction working perfectly!
❌ But can't check availability due to call_id mismatch

---

## Root Cause: Agent V7 vs V5

### What Happened

1. We fixed the flow and published **Agent V5** (Flow V3)
2. User published **Agent V7** (Flow V81 - the OLD flow!)
3. Agent V7 still has the old parameter mapping with `"call_id": "call_001"`

### Evidence

**From logs**:
```json
{
  "agent_id": "agent_bcd2fe2aea6c8e38533b2269e1",
  "agent_version": 7,  ← NOT version 5 that we fixed!
  "agent_name": "Friseur 1 Agent V122 - UX Polish (Flow V81)"  ← OLD FLOW!
}
```

**Flow V81** is the old flow before we added `get_current_context` and fixed call_id mappings.

---

## Impact Assessment

### What Worked ✅

1. Date awareness: Agent knows correct date (2025-11-23, Sonntag)
2. Date calculation: "kommenden Mittwoch" → 2025-11-26 ✅
3. Time parsing: "fünfzehn Uhr" → 15:00 ✅
4. Service extraction: "Dauerwelle" ✅
5. Customer extraction: "Siegfried Reu" ✅
6. Phone extraction: "0151123456" ✅

### What Failed ❌

1. check_availability_v17: "Call context not available" (2x attempts)
2. No booking created (availability check failed)
3. User experience: "Schwierigkeiten, den Kalender zu prüfen"

---

## Why This Is a Regression

### Timeline of Fixes

1. **2025-11-23 20:00**: Found date hallucination bug
2. **2025-11-23 20:10**: Updated flow via API → **Agent V3** (added get_current_context)
3. **2025-11-23 20:15**: User published → **Agent V5**
4. **2025-11-23 20:30**: Found call_id mismatch ("call_1")
5. **2025-11-23 20:35**: Added `'call_1'` to placeholders
6. **2025-11-23 21:00**: User tested → SUCCESS with Agent V5
7. **2025-11-23 21:50**: User published again → **Agent V7** (REVERTED TO OLD FLOW!)

### The Problem

Retell has multiple flows, and the user selected the WRONG flow when publishing Agent V7.

**Flow V3** (our fix): Has `get_current_context`, uses real call_id
**Flow V81** (old): NO `get_current_context`, uses `"call_001"` placeholder

---

## Solutions

### Solution 1: Add "call_001" to Placeholders (QUICK FIX)

**File**: `app/Http/Controllers/RetellFunctionCallHandler.php:133`

**Current**:
```php
$placeholders = ['dummy_call_id', 'None', 'current', 'current_call', 'call_1'];
```

**Add**:
```php
$placeholders = ['dummy_call_id', 'None', 'current', 'current_call', 'call_1', 'call_001'];
```

**Pros**: Immediate workaround, handles both `call_1` and `call_001`
**Cons**: Doesn't fix root cause in Retell flow

---

### Solution 2: Fix Retell Flow Again (PERMANENT FIX)

**Action**: Update Flow V81 via API to add `get_current_context` node

**Problem**: Flow V81 might be the user's preferred flow with other UX improvements

**Recommendation**:
1. Apply Quick Fix (Solution 1) immediately
2. Ask user which flow version to use going forward
3. Update the correct flow version with all fixes

---

### Solution 3: Use Latest Agent Version (USER ACTION)

**Issue**: User published the wrong agent version

**Action**: User should publish Agent V5 (or update Agent V7 to use Flow V3)

---

## Immediate Fix

Let me apply Solution 1 immediately:

```php
// Add 'call_001' to placeholders
$placeholders = ['dummy_call_id', 'None', 'current', 'current_call', 'call_1', 'call_001'];
```

This will make BOTH agent versions work:
- Agent V5 (Flow V3): sends `call_1` ✅ (already handled)
- Agent V7 (Flow V81): sends `call_001` ✅ (will be handled)

---

## Verification Steps

1. ✅ Add `'call_001'` to placeholders
2. ✅ Reload PHP-FPM
3. 🧪 Request new test call with Agent V7
4. 📊 Verify check_availability_v17 works
5. 📝 Document results

---

## Recommendations

### For User

1. **Decide on Flow Version**: Which flow do you want to use?
   - Flow V3 (our fix with get_current_context)
   - Flow V81 (your UX polish version)

2. **Consolidate Flows**: Merge the best of both flows into one master flow

3. **Publish Correct Version**: Ensure the published agent uses the correct flow

### For Us

1. ✅ Add `'call_001'` to placeholders (handles all variations)
2. Consider regex pattern: `'call_\d+'` to handle any future variations
3. Add validation: log warning when placeholder is detected

---

## Performance Metrics

### Latency (GOOD)
- LLM P50: 716.5ms ✅
- TTS P50: 306ms ✅
- E2E P50: 1481ms ⚠️ (higher due to failed attempts)

### Cost
- Duration: 49 seconds
- Total cost: €0.06 (7.93 cents USD)
- Cost per minute: €0.07

---

## Next Steps

1. ✅ Apply quick fix (`call_001` placeholder)
2. 🧪 Test with Agent V7
3. 📞 Ask user about preferred flow version
4. 🔧 Update correct flow with all fixes
5. 📊 Verify end-to-end booking works

---

**Status**: 🔧 QUICK FIX READY
**Priority**: 🚨 CRITICAL - Deploy immediately
**Agent Impact**: Both V5 and V7 will work after fix
