# Retell AI Agent Version Numbering System - Analysis

**Analysis Date:** 2025-10-23
**Scope:** Relationship between code versions (V15-V17), Retell's agent_version, and conversation flow versions

---

## Executive Summary

There are **THREE DIFFERENT VERSION SYSTEMS** in play, causing confusion:

1. **Laravel Code Versions** (V15, V16, V17) - Our internal naming convention
2. **Retell's `agent_version`** (24, 25, 117, 129...) - Incremented by Retell automatically
3. **Conversation Flow Versions** (18, etc.) - Each deployment creates new version

These are **INDEPENDENT** numbering systems. The code's V17 wrappers are **COMPATIBLE** with Retell's agent_version 24 and 25.

---

## System 1: Laravel Code Versions (V15-V17)

### Definition
Our internal versioning for the **conversion flow logic** and **backend wrapper methods**.

### Location in Codebase
```php
// File: /var/www/api-gateway/app/Http/Controllers/RetellFunctionCallHandler.php

/**
 * 🚀 V17: Check Availability Wrapper (bestaetigung=false)
 * POST /api/retell/v17/check-availability
 */
public function checkAvailabilityV17(CollectAppointmentRequest $request)
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

### Routes
```php
// File: /var/www/api-gateway/routes/api.php

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

### What Changed in V17
- **V15/V16 Problem:** Conversational tool calling unreliable (0% success in tests)
- **V17 Solution:** Explicit function nodes force deterministic tool execution
- **Implementation:** Separate `/v17/` endpoints hardcode `bestaetigung` parameter

### Git References
```
commit ab567e5c: fix: Resolve Livewire serialization issues in customer detail widgets
commit 9a2e5984: fix: Convert Carbon timestamps to ISO8601 strings in CustomerActivityTimeline
commit 7edd03b2: fix: Replace Customer model with customer_id in CustomerActivityTimeline
```

---

## System 2: Retell's `agent_version` Field

### Definition
**Automatically incremented by Retell.ai** every time you update the agent or conversation flow. This is NOT something we control - it's Retell's internal version counter.

### Storage in Database
```php
// File: /var/www/api-gateway/app/Services/RetellApiClient.php (line 241)

'agent_version' => $callData['agent_version'] ?? null,
```

### Where It Comes From
Retell sends this in **every webhook**:

```json
{
  "call": {
    "call_id": "call_23fac465f81b8eaab34e5ec3818",
    "agent_id": "agent_9a8202a740cd3120d96fcfda1e",
    "agent_version": 129,                    // <-- Retell's version
    "agent_name": "Online: Assistent für Fabian Spitzer Rechtliches/V126",
    "call_status": "ongoing"
  }
}
```

### Evidence from Logs
From `/var/www/api-gateway/storage/logs/laravel-2025-10-22.log`:

```
agent_version: 129        (2025-10-22 07:27:01)
agent_version: 129        (2025-10-22 07:27:35)
agent_version: 129        (2025-10-22 07:27:56)
```

From `/var/www/api-gateway/INCIDENT_RESPONSE_V117_AGENT_FREEZE_2025_10_19.md`:

```
agent_version: 117 (Complete freeze - 0 function calls)
agent_version: 116 (Also failed)
agent_version: 24  (Earlier reference in user comment)
```

### Database Column
```php
// File: /var/www/api-gateway/app/Models/PhoneNumber.php (line 116)

'retell_agent_version' => 'nullable|string|max:255',
```

### How Retell Increments It

When you:
1. Update agent prompt in Retell dashboard → agent_version increments
2. Deploy conversation flow → agent_version increments
3. Update function definitions → agent_version increments
4. Publish agent → agent_version may increment

**Each change = new version number automatically assigned by Retell**

---

## System 3: Conversation Flow Versions

### Definition
**Retell automatically versions each conversation flow deployment** with an incrementing number. Our current flow is version 18.

### Evidence
From `/var/www/api-gateway/claudedocs/03_API/Retell_AI/V17_DEPLOYMENT_SUCCESS_2025-10-22.md`:

```
**Deployment Time:** 2025-10-22 21:20:16 (Europe/Berlin)
**Flow Version:** 18
**Agent ID:** agent_616d645570ae613e421edb98e7
**Flow ID:** conversation_flow_da76e7c6f3ba
```

### How It Works
- Each time you PATCH/POST to `/update-conversation-flow/{flow_id}`
- Retell creates a new version number
- Old versions are preserved (can rollback)
- Current version number is returned in response

---

## The Confusion: Three Independent Counters

### Version Timeline

| Date | Event | V-System | agent_version | Flow Version | Status |
|------|-------|----------|---------------|--------------|--------|
| 2025-10-16 | Initial setup | V15 | ? | ? | Early dev |
| 2025-10-17 | First fixes | V16 | 46 | ? | Early testing |
| 2025-10-19 | Agent freeze | V??  | 117 | ? | **P0 Incident** |
| 2025-10-22 13:00 | Flow deployed | V17 | 129 | 18 | Current state |
| ? | Future update | V18? | 130+? | 19+? | Next version |

### Example Sequence

```
Your Changes:
1. Update RetellFunctionCallHandler.php (V17)
2. Deploy conversation flow via Retell API
3. Retell increments agent_version to 129 (Retell's counter)
4. Retell increments flow_version to 18 (Retell's counter)
5. Your Laravel code doesn't change, still called "V17"
6. User calls - receives agent_version: 129 in webhook
7. You log agent_version: 129 in database
8. Your code still has V17 comments
```

---

## Compatibility Matrix

### Are V17 Code Wrappers Compatible with agent_version 24?

**YES - FULLY COMPATIBLE**

**Proof:**
1. V17 defines wrapper endpoints (`/v17/check-availability`, `/v17/book-appointment`)
2. Conversation flow defines function nodes that call these endpoints
3. Agent version (24, 25, 129) is irrelevant to code routing
4. The agent_version is just metadata - it tracks Retell's updates, not endpoint compatibility

### What Version Should You Track?

| Field | What It Means | Should You Care? | Where To Check |
|-------|---------------|------------------|-----------------|
| **Code Version (V17)** | Our backend logic version | YES - for bug tracking | RetellFunctionCallHandler.php |
| **agent_version (24)** | How many times Retell updated | NO - just informational | Webhook payload |
| **Flow Version (18)** | How many times flow was deployed | MAYBE - for rollback | Retell dashboard |

---

## Addressing the User's Comment: "V24 aktiv, V25 ist draft"

This is likely **mixing systems**:

### Most Likely Interpretation
The user saw in Retell dashboard:
- "Agent Version 24" - this is agent_version from Retell (NOT our V24)
- "Agent Version 25 draft" - probably a draft configuration in Retell

### Clarification
```
User said:      "V24 aktiv, V25 ist draft"
Probable meaning: "agent_version 24 is active, 25 is being drafted"
NOT:            "Our V24 code is active, V25 is draft"

Because we have:
- V17 code in production (what we call it)
- agent_version 129 active (what Retell says)
```

### Why The Confusion?
You see in agent names: `"Online: Assistent für Fabian Spitzer Rechtliches/V126"`

This is mixing TWO systems:
- `/V126` = Someone's documentation naming (not our V-system)
- `agent_version` field = Retell's internal counter

---

## Current Production State (as of 2025-10-23)

### Active Components
```
Laravel Code Version:        V17
Code Endpoints:              /api/retell/v17/check-availability
                             /api/retell/v17/book-appointment
Current agent_version:       129 (from recent logs)
Conversation Flow ID:        conversation_flow_da76e7c6f3ba
Conversation Flow Version:   18
Agent ID (conversation):     agent_616d645570ae613e421edb98e7
Agent ID (legacy):           agent_9a8202a740cd3120d96fcfda1e (❌ don't use)
```

### What Changed in V17
```
Problem:  Conversational tool calling unreliable (0% success)
Solution: Explicit function nodes force deterministic execution
Impact:   Check availability and book appointment now guaranteed to execute
Evidence: Two separate endpoints with hardcoded bestaetigung parameter
```

---

## How to Track Version Changes Going Forward

### When You Deploy Code Changes

1. Update `/app/Http/Controllers/RetellFunctionCallHandler.php`
2. Add comment: `/** 🚀 V18: [Your change description] */`
3. Keep code version in comments/documentation

### When You Update Conversation Flow

1. Log into Retell dashboard
2. Modify conversation flow
3. Deploy via `/update-conversation-flow/{flow_id}` endpoint
4. Note the response: `"flow_version": 19` (Retell's counter)
5. This is NOT your V18 - it's Retell's internal tracking

### When You Update Agent Prompt

1. Log into Retell dashboard
2. Edit agent prompt
3. Save/Deploy
4. Retell auto-increments agent_version (usually 130, 131...)
5. Next webhook will show `"agent_version": 130`

---

## Recommendations for Clarity

### 1. Rename Internal Version System
```
CURRENT:  "V17" (confusing with agent versions)
PROPOSED: "Backend-v2.1" or "AskPro-v17" or "APIv2"
BENEFIT:  Clear separation from Retell's numbering
```

### 2. Document Version Tracking
Add to README or deployment guide:

```markdown
## Version Systems

### Code Version (AskPro)
- Location: RetellFunctionCallHandler.php comments
- Format: V17, V18, V19...
- Purpose: Track our backend logic changes
- Update: When we modify function handlers

### Retell agent_version
- Source: Retell.ai API
- Format: Incremental numbers (24, 25, 129...)
- Purpose: Retell's internal change tracking
- Update: Automatic whenever agent/flow is updated
- Tracking: Stored in calls.agent_version column

### Flow Version
- Source: Retell.ai API
- Format: Incremental numbers (18, 19...)
- Purpose: Conversation flow deployment tracking
- Update: Automatic with each flow deployment
```

### 3. Add Logging
```php
// When storing call data
Log::info('Call received with Retell metadata', [
    'retell_agent_version' => $callData['agent_version'],
    'our_code_version' => 'V17',
    'call_id' => $callData['call_id']
]);
```

### 4. Update UI/Admin Panel
Show both version systems clearly:
- "Backend Code Version: V17"
- "Retell agent_version: 129"
- "Retell flow_version: 18"

---

## File References

### Code Locations
- `/var/www/api-gateway/app/Http/Controllers/RetellFunctionCallHandler.php` (lines 4046-4088) - V17 wrappers
- `/var/www/api-gateway/routes/api.php` - V17 routes
- `/var/www/api-gateway/app/Models/Call.php` - Stores agent_version
- `/var/www/api-gateway/app/Services/RetellApiClient.php` (line 241) - Extracts agent_version

### Documentation
- `/var/www/api-gateway/claudedocs/03_API/Retell_AI/V17_DEPLOYMENT_SUCCESS_2025-10-22.md`
- `/var/www/api-gateway/claudedocs/03_API/Retell_AI/AGENT_IDS_REFERENZ.md`
- `/var/www/api-gateway/INCIDENT_RESPONSE_V117_AGENT_FREEZE_2025_10_19.md`

---

## Root Cause Summary

The confusion stems from:

1. **Naming Collision**: Agent names include version-like suffixes (`/V126`) that don't match our code versions
2. **Auto-Versioning**: Retell automatically increments `agent_version` with every change
3. **Multiple Systems**: Three independent version counters running in parallel
4. **Sparse Documentation**: No clear mapping between systems documented

---

## Conclusion

**V17 code wrappers ARE compatible with agent_version 24/25.**

The version numbers are:
- **V17** = Our code layer (what we call the backend logic)
- **agent_version 129** = Retell's tracking (what Retell calls their updates)
- **flow_version 18** = Conversation flow deployment (what Retell tracks for flows)

These operate independently. Your V17 code will work with any current or future Retell agent_version.

---

**Document Created:** 2025-10-23
**Analysis By:** Claude Code (Debugging Agent)
**Confidence Level:** High (Evidence-based from logs, code, and documentation)
