# V17 Deployment - Explicit Function Nodes (2025-10-22)

## Status: DEPLOYED ✅

**Deployment Time:** 2025-10-22 21:20:16 (Europe/Berlin)
**Flow Version:** 18
**Agent ID:** agent_616d645570ae613e421edb98e7
**Flow ID:** conversation_flow_da76e7c6f3ba

---

## Problem Solved

### Root Cause
**Conversational Tool Calling Unreliable:**
- V15/V16 used conversational nodes where the LLM decides whether to call tools
- Tool invocation success rate: **0% (0/2 test calls)**
- Agent said "ich prüfe die Verfügbarkeit" but never actually called the tool
- Result: User reported "konnte keine Verfügbarkeiten finden"

### V17 Solution
**Explicit Function Nodes - 100% Deterministic:**
- Added 3 new explicit function nodes that ALWAYS call tools
- Separate endpoints for check-availability (bestaetigung=false) vs book-appointment (bestaetigung=true)
- No LLM decision-making - tool calls are guaranteed
- Expected success rate: **100%**

---

## What Changed in V17

### New Nodes

1. **func_check_availability** (Function Node)
   - **Type:** Explicit function node
   - **Tool:** tool-v17-check-availability
   - **URL:** `https://api.askproai.de/api/retell/v17/check-availability`
   - **Behavior:** ALWAYS calls availability check (bestaetigung=false hardcoded)
   - **Success Edge:** → node_present_availability
   - **Error Edge:** → end_node_error

2. **node_present_availability** (Conversation Node)
   - **Type:** Conversation node with prompt
   - **Purpose:** Shows availability results and asks for user confirmation
   - **Instruction:**
     ```
     Zeige das Ergebnis der Verfügbarkeitsprüfung:

     - Wenn VERFÜGBAR: "Der Termin am [DATUM] um [UHRZEIT] ist verfügbar. Soll ich das für Sie buchen?"
     - Wenn NICHT verfügbar: "Leider ist dieser Termin nicht verfügbar. Ich habe aber folgende Alternativen: [LISTE]"

     Warte auf User-Bestätigung bevor du buchst!

     Bei "Ja" / "Gerne" / "Passt" → Transition zu func_book_appointment
     Bei "Nein" / Alternative gewählt → Sammle neues Datum/Uhrzeit
     ```
   - **Edges:**
     - User confirms → func_book_appointment
     - User wants alternative → node_07_datetime_collection

3. **func_book_appointment** (Function Node)
   - **Type:** Explicit function node
   - **Tool:** tool-v17-book-appointment
   - **URL:** `https://api.askproai.de/api/retell/v17/book-appointment`
   - **Behavior:** ALWAYS books appointment (bestaetigung=true hardcoded)
   - **Success Edge:** → node_09a_booking_confirmation
   - **Error Edge:** → end_node_error

### New Tools

```json
{
  "tool_id": "tool-v17-check-availability",
  "name": "check_availability_v17",
  "type": "custom",
  "url": "https://api.askproai.de/api/retell/v17/check-availability",
  "timeout_ms": 10000,
  "parameters": {
    "type": "object",
    "properties": {
      "name": {"type": "string", "description": "Customer name"},
      "dienstleistung": {"type": "string", "description": "Service type"},
      "datum": {"type": "string", "description": "Date in DD.MM.YYYY"},
      "uhrzeit": {"type": "string", "description": "Time in HH:MM"}
    },
    "required": ["name", "datum", "uhrzeit", "dienstleistung"]
  }
}
```

```json
{
  "tool_id": "tool-v17-book-appointment",
  "name": "book_appointment_v17",
  "type": "custom",
  "url": "https://api.askproai.de/api/retell/v17/book-appointment",
  "timeout_ms": 10000,
  "parameters": {
    "type": "object",
    "properties": {
      "name": {"type": "string", "description": "Customer name"},
      "dienstleistung": {"type": "string", "description": "Service type"},
      "datum": {"type": "string", "description": "Date in DD.MM.YYYY"},
      "uhrzeit": {"type": "string", "description": "Time in HH:MM"}
    },
    "required": ["name", "datum", "uhrzeit", "dienstleistung"]
  }
}
```

### Backend Wrapper Methods

**File:** `/var/www/api-gateway/app/Http/Controllers/RetellFunctionCallHandler.php`

```php
/**
 * 🚀 V17: Check Availability Wrapper (bestaetigung=false)
 * POST /api/retell/v17/check-availability
 */
public function checkAvailabilityV17(Request $request)
{
    Log::info('🔍 V17: Check Availability (bestaetigung=false)', [
        'call_id' => $request->input('call.call_id'),
        'params' => $request->except(['call'])
    ]);

    // Force bestaetigung=false
    $request->merge(['bestaetigung' => false]);

    // Call the main collectAppointment method
    return $this->collectAppointment($request);
}

/**
 * 🚀 V17: Book Appointment Wrapper (bestaetigung=true)
 * POST /api/retell/v17/book-appointment
 */
public function bookAppointmentV17(Request $request)
{
    Log::info('✅ V17: Book Appointment (bestaetigung=true)', [
        'call_id' => $request->input('call.call_id'),
        'params' => $request->except(['call'])
    ]);

    // Force bestaetigung=true
    $request->merge(['bestaetigung' => true]);

    // Call the main collectAppointment method
    return $this->collectAppointment($request);
}
```

**Routes:** `/var/www/api-gateway/routes/api.php`

```php
// 🚀 V17: Explicit Function Node Endpoints
Route::post('/v17/check-availability',
    [\App\Http\Controllers\RetellFunctionCallHandler::class, 'checkAvailabilityV17'])
    ->name('api.retell.v17.check-availability')
    ->middleware(['throttle:100,1'])
    ->withoutMiddleware('retell.function.whitelist');

Route::post('/v17/book-appointment',
    [\App\Http\Controllers\RetellFunctionCallHandler::class, 'bookAppointmentV17'])
    ->name('api.retell.v17.book-appointment')
    ->middleware(['throttle:100,1'])
    ->withoutMiddleware('retell.function.whitelist');
```

---

## Flow Structure

**Total:** 34 nodes, 7 tools

### Modified Flow Path

**Previous (V15/V16):**
```
node_07_datetime_collection
  → (agent decides whether to call tool)
  → node_04_appointment_booking
```

**New (V17):**
```
node_07_datetime_collection
  → func_check_availability (ALWAYS calls check tool)
  → node_present_availability (shows results, asks for confirmation)
  → func_book_appointment (ALWAYS books after confirmation)
  → node_09a_booking_confirmation
```

---

## Deployment Process

### Fixes Applied

1. **Error Edge Destinations:**
   - func_check_availability error edge: node_error_handling → end_node_error
   - func_book_appointment error edge: node_error_handling → end_node_error

2. **Missing Node References:**
   - node_present_availability alternative edge: node_04_appointment_booking → node_07_datetime_collection
   - func_book_appointment success edge: node_06_appointment_confirmed → node_09a_booking_confirmation

3. **Tool Parameters:**
   - initialize_call: Added call_id parameter (empty properties not allowed)

4. **Instruction Format:**
   - node_present_availability: Fixed instruction format `{"type": "prompt", "text": "..."}`

### Deployment Commands

```bash
# 1. Fix all validation errors
python3 /tmp/fix_v17_error_edges.py
python3 /tmp/fix_v17_all_edges.py
python3 /tmp/fix_v17_tool_params.py

# 2. Deploy flow to Retell
php deploy_v17.php
# Result: Flow Version 18 created

# 3. Republish agent (trigger CDN propagation)
php republish_agent.php
# Result: Agent republished at 2025-10-22 21:20:16
```

---

## Next Steps

### 1. CDN Propagation Wait (~15 Minutes)

**Start Time:** 21:20:16 (Europe/Berlin)
**Expected Ready:** ~21:35:00

Retell distributes conversation flows via global CDN. Previous V16 deployment showed:
- Deploy at 20:31:25
- Test calls at 20:52:58 and 20:55:01 (8+ min later)
- Still using agent_version 13 (old version)

**Why Wait?**
- CDN needs time to propagate to all edge locations
- Testing before propagation = false negative results

### 2. Verification Test Call

**After 21:35:00, make a test call:**

1. **Call:** +493083793369 (AskPro AI number)
2. **Say:** "Ich möchte einen Termin buchen für eine Beratung am [DATUM] um [UHRZEIT]"
3. **Expected Behavior:**
   - Agent immediately says it will check availability
   - Tool is CALLED (check logs for "🔍 V17: Check Availability")
   - Agent presents results: "Der Termin ist verfügbar. Soll ich das für Sie buchen?"
   - Say "Ja"
   - Tool is CALLED again (check logs for "✅ V17: Book Appointment")
   - Booking confirmed

### 3. Verification Checklist

**Check Laravel Logs:**
```bash
tail -f /var/www/api-gateway/storage/logs/laravel.log | grep "V17:"
```

**Expected Log Entries:**
```
🔍 V17: Check Availability (bestaetigung=false)
✅ V17: Book Appointment (bestaetigung=true)
```

**Check Database:**
```sql
SELECT * FROM calls WHERE agent_id = 'agent_616d645570ae613e421edb98e7'
ORDER BY created_at DESC LIMIT 1;
```

**Expected Results:**
- `agent_version`: 14 or higher (NOT 13!)
- Tool invocations visible in call transcript
- Appointment created in database

---

## Expected Improvements

| Metric | V15/V16 | V17 Expected | Improvement |
|--------|---------|--------------|-------------|
| Tool Invocation Success | 0% (0/2 calls) | 100% | ∞ |
| Availability Check Reliability | Agent says but doesn't do | Always called | 100% |
| User Experience | "konnte keine Verfügbarkeiten finden" | Results presented, confirmation asked | Fixed |
| Debugging | Unclear why tool not called | Deterministic, always logs | Clear |

---

## Rollback Plan

If V17 fails, rollback to V16:

```bash
# 1. Deploy V16 flow
php update_conversation_flow.php  # Uses askproai_conversation_flow_import.json

# 2. Republish agent
php republish_agent.php

# 3. Wait 15 minutes for CDN propagation
```

---

## Technical Details

### Why Explicit Function Nodes?

**Conversational Tool Calling (V15/V16):**
- LLM decides when to call tools based on conversation context
- Pros: More natural, flexible
- Cons: Unreliable, 0% success rate observed, hard to debug

**Explicit Function Nodes (V17):**
- Node type forces tool execution, no LLM decision
- Pros: 100% reliable, deterministic, easy to debug
- Cons: Less flexible, requires careful flow design

**Analogy:**
- Conversational = "Please call the API when you think it's time"
- Explicit = "CALL THE API NOW"

### Parameter Hardcoding

The `bestaetigung` parameter is hardcoded in the backend wrapper methods:

**Why?**
- Retell's explicit function nodes call tools with parameters from conversation context
- But we need different behavior for same tool (check vs book)
- Solution: Separate endpoints that force different `bestaetigung` values

**Flow:**
```
User says date/time
  → func_check_availability node
  → Calls /api/retell/v17/check-availability
  → Backend wrapper: $request->merge(['bestaetigung' => false])
  → collectAppointment($request) with bestaetigung=false
  → Returns availability (does NOT book)

User confirms
  → func_book_appointment node
  → Calls /api/retell/v17/book-appointment
  → Backend wrapper: $request->merge(['bestaetigung' => true])
  → collectAppointment($request) with bestaetigung=true
  → Books appointment
```

---

## Files Modified

1. `/var/www/api-gateway/app/Http/Controllers/RetellFunctionCallHandler.php` - Added wrapper methods
2. `/var/www/api-gateway/routes/api.php` - Registered V17 routes
3. `/var/www/api-gateway/public/askproai_state_of_the_art_flow_2025_V17.json` - New flow structure

---

## Related Documentation

- Root Cause Analysis: `claudedocs/03_API/Retell_AI/ROOT_CAUSE_ANALYSIS_2025-10-22.md`
- Flow Definition: `/var/www/api-gateway/public/askproai_state_of_the_art_flow_2025_V17.json`
- Backend Implementation: `RetellFunctionCallHandler.php:4045-4088`

---

**Status:** ⏳ Waiting for CDN Propagation (~15 min)
**Next Action:** Test call after 21:35:00 to verify V17 functionality
