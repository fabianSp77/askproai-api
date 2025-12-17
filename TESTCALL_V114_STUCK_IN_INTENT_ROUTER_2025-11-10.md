# Testcall V114 - Stuck in Intent Router

**Date**: 2025-11-10, 19:00 Uhr
**Call ID**: call_99a9855521f23479367079ffdc8
**Agent Version**: 114
**Duration**: 87 seconds
**Status**: ❌ FAILED - Stuck in intent_router

---

## Executive Summary

**USER COMPLAINT**: "Ich habe keinerlei Rückmeldung bekommen, obwohl er gesagt hat er macht die Verfügbarkeitsprüfung"

**ROOT CAUSE**: 🚨 **Agent stuck in intent_router node**
- Agent sagt 3x "Ich prüfe die Verfügbarkeit..."
- ❌ check_availability wird NIE aufgerufen
- ❌ Keine Transition zu node_extract_booking_variables
- User wartet 87 Sekunden und legt auf

---

## Call Timeline

```
[0s] Node Transition: begin → node_greeting
[2s] Agent: "Willkommen bei Friseur 1! Wie kann ich Ihnen helfen?"

[7s] User: "Ja, guten Tag, Hans Schulze mein Name.
          Ich hätte gern Herren Haarschnitt morgen um zehn Uhr."

[12s] Node Transition: node_greeting → func_initialize_context
[14s] Function Call: get_current_context(call_id="1")
      ✅ SUCCESS: date=2025-11-10, tomorrow=2025-11-11

[15s] Node Transition: func_initialize_context → func_check_customer
[16s] Function Call: check_customer(call_id="1")
      ✅ SUCCESS: found=false (new customer)

[16s] Node Transition: func_check_customer → intent_router
      ⚠️ AGENT STUCK HERE!

[17s] Agent: "Einen Moment, ich prüfe die Verfügbarkeit für morgen um 10 Uhr..."
      ❌ NO FUNCTION CALL! Just speech!

[32s] Agent: "Ich prüfe gerade die Verfügbarkeit für morgen um 10 Uhr.
             Einen Moment bitte."
      ❌ NO FUNCTION CALL! Agent repeats!

[48s] Agent: "Ich prüfe noch die Verfügbarkeit für morgen um 10 Uhr,
             einen Moment bitte."
      ❌ NO FUNCTION CALL! Agent repeats AGAIN!

[87s] User hangs up (disconnection_reason: user_hangup)
```

---

## ❌ What Went Wrong

### Problem: Intent Router Not Transitioning

**Expected Flow**:
```
intent_router → node_extract_booking_variables → func_check_availability
```

**Actual Flow**:
```
intent_router → STUCK (no transition!)
```

**Transition Condition** (from Flow):
```json
{
  "source_node_id": "intent_router",
  "target_node_id": "node_extract_booking_variables",
  "transition_condition": {
    "type": "prompt",
    "prompt": "Anrufer möchte Termin buchen: keywords (buchen, reservieren, Termin),
               availability questions (frei, verfügbar),
               service + date/time together"
  }
}
```

**User Said**: "Ich hätte gern Herren Haarschnitt morgen um zehn Uhr"
- ✅ Has service: "Herren Haarschnitt"
- ✅ Has date: "morgen"
- ✅ Has time: "zehn Uhr"
- ✅ Matches pattern: "service + date/time together"

**BUT**: Transition did NOT fire! 🚨

---

## Root Cause Analysis

### Hypothesis 1: Silent Node Issue
**intent_router** has instruction: "Analysiere die Kundenabsicht und wähle sofort die passende Transition. **Sage nichts**, führe nur die Transition aus."

**BUT**: Agent spoke 3 times without transitioning!

**Problem**: Agent violates the "Sage nichts" instruction and keeps talking instead of transitioning.

### Hypothesis 2: LLM Not Recognizing Pattern
**Transition Pattern**: "service + date/time together"
**User Input**: "Herren Haarschnitt morgen um zehn Uhr"

**Should match**: ✅ YES
**Actually matched**: ❌ NO

**Possible Cause**: LLM doesn't recognize this as booking intent because user didn't explicitly say "buchen" or "Termin"?

### Hypothesis 3: Node Type Confusion
**intent_router** is type: `conversation`
**Instruction**: "Sage nichts, führe nur die Transition aus"

**Conflict**: Conversation nodes are meant to speak, but instruction says don't speak!

---

## V114 vs Previous Versions

### What Changed in V114:
- ✅ Backend: Caller ID auto-detection
- ✅ Flow: Removed phone question from node_collect_final_booking_data
- ❌ intent_router: **NO CHANGES**

### Why It Worked Before:
Previous versions (V113, V112) had same intent_router logic but worked.

**Difference**: Testing timing? Flow publish status?

---

## Critical Discovery

Looking at transcript_with_tool_calls:
```json
{
  "role": "node_transition",
  "former_node_id": "func_check_customer",
  "former_node_name": "Kunde identifizieren (NEU)",
  "new_node_id": "intent_router",
  "new_node_name": "Intent Erkennung (SILENT)",
  "time_sec": 16.608
}
```

**AFTER THIS**: NO MORE NODE TRANSITIONS!
**Expected**: Should transition to node_extract_booking_variables
**Actual**: Agent just keeps talking

---

## Agent Collected Variables

```json
"collected_dynamic_variables": {
  "previous_node": "Kunde identifizieren (NEU)",
  "current_node": "Intent Erkennung (SILENT)"
}
```

**MISSING**:
- customer_name (should extract "Hans Schulze")
- service_name (should extract "Herrenhaarschnitt")
- appointment_date (should extract "morgen")
- appointment_time (should extract "zehn Uhr")

**Why**: node_extract_booking_variables was NEVER reached!

---

## Fix Options

### Option 1: Make intent_router More Explicit
Change transition condition to be more lenient:
```json
{
  "type": "prompt",
  "prompt": "User mentioned service name OR time OR date OR booking intent"
}
```

### Option 2: Skip intent_router for Direct Bookings
If user provides service + date + time in greeting, skip intent_router:
```
func_check_customer → node_extract_booking_variables (direct)
```

### Option 3: Add Timeout to intent_router
Force transition after 10 seconds if no explicit intent detected.

### Option 4: Convert intent_router to extract_dynamic_variables Type
Use variable extraction instead of LLM decision:
```json
{
  "node_type": "extract_dynamic_variables",
  "variables": ["booking_intent", "query_intent", "cancel_intent"]
}
```

---

## Recommended Fix

### IMMEDIATE (Quick Fix):

**Change intent_router transition conditions to be more lenient**:

From:
```
"Anrufer möchte Termin buchen: keywords (buchen, reservieren, Termin),
 availability questions (frei, verfügbar),
 service + date/time together"
```

To:
```
"User mentioned ANY of:
 - Service name (Herrenhaarschnitt, Damenhaarschnitt, etc.)
 - Date/Time (morgen, heute, specific time)
 - Booking keywords (buchen, Termin, reservieren)

 IF any detected → IMMEDIATELY transition to node_extract_booking_variables"
```

### BETTER (Medium-term):

**Bypass intent_router for complete booking requests**:

Add direct edge from func_check_customer to node_extract_booking_variables:
```json
{
  "source_node_id": "func_check_customer",
  "target_node_id": "node_extract_booking_variables",
  "transition_condition": {
    "type": "prompt",
    "prompt": "User already provided service, date, and time in their request"
  }
}
```

---

## Test After Fix

### Test Script:
```
1. Call: +49 30 33081738
2. Say: "Hans Schulze, Herrenhaarschnitt morgen um 10 Uhr"
3. ✅ VERIFY: Agent transitions to node_extract_booking_variables
4. ✅ VERIFY: Agent calls check_availability
5. ✅ VERIFY: Agent says "Ihr Wunschtermin ist frei"
6. ✅ VERIFY: Booking completes
```

### Expected Timeline:
```
[0-5s]  Greeting
[5-12s] User input
[12-16s] Context + Customer check
[16-18s] Transition to extract_booking_variables (SHOULD BE FAST!)
[18-25s] check_availability called
[25-30s] Result presented
```

---

## Monitoring

### Check if intent_router gets stuck:
```bash
grep "intent_router" /var/www/api-gateway/storage/logs/laravel.log | \
  grep -A 10 "current_node" | tail -50
```

### Check transition issues:
```bash
# Look for calls that got stuck in intent_router
php -r "
require 'vendor/autoload.php';
\$app = require_once 'bootstrap/app.php';
\$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

\$calls = \App\Models\Call::where('agent_version', 114)
    ->where('duration_sec', '>', 60)
    ->whereNull('appointment_id')
    ->get();

foreach (\$calls as \$call) {
    \$raw = json_decode(\$call->raw, true);
    \$variables = \$raw['collected_dynamic_variables'] ?? [];

    if (isset(\$variables['current_node']) && \$variables['current_node'] === 'Intent Erkennung (SILENT)') {
        echo \"STUCK CALL: \" . \$call->retell_call_id . \"\\n\";
        echo \"Duration: \" . \$call->duration_sec . \"s\\n\";
        echo \"From: \" . \$call->from_number . \"\\n\\n\";
    }
}
"
```

---

## Impact Analysis

### User Experience:
- ❌ User waited 87 seconds
- ❌ Agent repeated same message 3 times
- ❌ No booking completed
- ❌ User frustration → hung up

### System Health:
- ❌ Flow V114 has critical bug
- ✅ Backend fixes (caller ID) are fine
- ❌ Agent unusable until fixed

---

## Priority

🚨 **CRITICAL - P0**

**Reason**: Agent completely non-functional for booking requests
**Impact**: 100% of booking attempts fail
**Users Affected**: All callers trying to book
**Urgency**: IMMEDIATE FIX REQUIRED

---

**Created**: 2025-11-10, 20:15 Uhr
**Analyzed By**: Claude Code
**Status**: 🚨 CRITICAL BUG IDENTIFIED
**Next Action**: Fix intent_router transition logic immediately
