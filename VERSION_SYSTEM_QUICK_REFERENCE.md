# Retell Version System - Quick Reference

## Three Independent Version Systems

### System 1: Laravel Code Versions (V15-V17)
```
WHO CONTROLS:    Us (AskPro development team)
UPDATES WHEN:    We modify RetellFunctionCallHandler.php
STORED IN:       Code comments (e.g., "🚀 V17: Check Availability Wrapper")
CURRENT VALUE:   V17
FORMAT:          V15, V16, V17, V18...

WHAT IT TRACKS:
- Backend logic changes
- Function handler updates
- API endpoint behavior

EXAMPLE USAGE:
/**
 * 🚀 V17: Check Availability Wrapper (bestaetigung=false)
 * POST /api/retell/v17/check-availability
 */
public function checkAvailabilityV17(Request $request)
```

### System 2: Retell's agent_version
```
WHO CONTROLS:    Retell.ai (automatically incremented)
UPDATES WHEN:    Agent or conversation flow is changed in Retell
STORED IN:       calls.agent_version (in database)
CURRENT VALUE:   129 (from recent call logs)
FORMAT:          24, 25, 117, 129... (sequential integers)

WHAT IT TRACKS:
- Every change to the agent configuration
- Flow deployments
- Prompt updates
- Function definition changes

ARRIVES VIA:
Webhook payload:
{
  "call": {
    "agent_version": 129
  }
}

DATABASE COLUMN:
$table->string('agent_version')->nullable();
```

### System 3: Conversation Flow Version
```
WHO CONTROLS:    Retell.ai (automatically incremented)
UPDATES WHEN:    Conversation flow is deployed/updated
STORED IN:       Retell API response
CURRENT VALUE:   18 (from 2025-10-22 deployment)
FORMAT:          Sequential integers for each deployment

WHAT IT TRACKS:
- Conversation flow deployment history
- Can be used to rollback to previous versions
- Not stored in our database currently
```

---

## Compatibility Matrix

```
┌─────────────────────────────────────────┐
│ Our Code Version V17                    │
├─────────────────────────────────────────┤
│ Endpoints:                              │
│ - /api/retell/v17/check-availability   │
│ - /api/retell/v17/book-appointment     │
│ Routes are hardcoded in Laravel         │
│ Location: routes/api.php                │
└─────────────────────────────────────────┘
          ↓ (Calls)
┌─────────────────────────────────────────┐
│ Retell Conversation Flow                │
│ ID: conversation_flow_da76e7c6f3ba      │
│ Version: 18                             │
├─────────────────────────────────────────┤
│ Contains Function Nodes:                │
│ - func_check_availability               │
│ - func_book_appointment                 │
│                                         │
│ These nodes are AGNOSTIC about:         │
│ - Our code version (V17)                │
│ - Retell's agent_version (129)          │
│ They just call the endpoints            │
└─────────────────────────────────────────┘
          ↓ (Webhook)
┌─────────────────────────────────────────┐
│ Call Data from Retell                   │
│ {                                       │
│   "call_id": "call_xxx",                │
│   "agent_version": 129,                 │
│   "agent_name": "...V126",              │
│   ...                                   │
│ }                                       │
│                                         │
│ agent_version IS INDEPENDENT            │
│ from our V17 code                       │
└─────────────────────────────────────────┘
```

---

## Timeline of Changes

### Oct 2025 - Code Development
```
Date       | Code Version | Retell agent_version | Flow Version | Event
-----------|--------------|----------------------|--------------|----------
Oct 16     | V15          | ?                    | ?            | Initial
Oct 18     | V16          | 46                   | ?            | Testing
Oct 19     | V??          | 117                  | ?            | P0 INCIDENT
Oct 22     | V17          | 129                  | 18           | FIXED
```

### Key Point
The dates match up, but **Version numbers don't correlate**:
- V17 code deployed on Oct 22
- Retell agent_version showed 129 on Oct 22
- These are **coincidentally close**, not causally related

---

## Where Each Version Appears

### In Code Comments
```php
// File: RetellFunctionCallHandler.php

/**
 * 🚀 V17: Check Availability Wrapper (bestaetigung=false)
 * ↑ This is OUR version system
 */
public function checkAvailabilityV17(CollectAppointmentRequest $request)
{
    Log::info('🔍 V17: Check Availability (bestaetigung=false)', [
        //         ↑ Internal documentation only
        'call_id' => $request->input('call.call_id'),
        'params' => $request->except(['call'])
    ]);
```

### In Logs (From Retell)
```
// From webhook
{
  "call": {
    "agent_id": "agent_9a8202a740cd3120d96fcfda1e",
    "agent_version": 129,  ← RETELL'S system
    "agent_name": "Online: Assistent für Fabian Spitzer Rechtliches/V126",
                                                                        ↑ NOT our V-system
  }
}
```

### In Agent Name Suffix
```
Agent Name: "Online: Assistent für Fabian Spitzer Rechtliches/V126"
                                                          ^^^^^^^
            This suffix is NEITHER our V17 nor Retell's agent_version!
            It's just documentation added to the agent name string.
```

### In Database
```sql
-- calls table
SELECT agent_version FROM calls WHERE id = 1;
→ 129

-- But our code version is still V17 (not stored in DB)
-- It's only in code comments
```

---

## The User's Comment Explained

**User said:** "V24 aktiv, V25 ist draft"

**What they probably saw:**
- Retell dashboard showing "Agent Version: 24"
- Perhaps a draft with "Agent Version: 25"

**What it meant:**
- Retell's agent_version 24 is active
- Retell's agent_version 25 is in draft

**What it did NOT mean:**
- Our code isn't at V24
- Our code is still at V17

---

## How Versions Change

### Our Code Version (V17 → V18)
```
1. You modify /app/Http/Controllers/RetellFunctionCallHandler.php
2. You add new method: public function checkAvailabilityV18()
3. You add route: Route::post('/v18/check-availability', ...)
4. You update documentation: "🚀 V18:"
5. Git commit: "feat: Add V18 improvements"
6. Deploy to production
7. Code version is now V18 in all comments/docs
```

### Retell's agent_version (129 → 130)
```
1. Someone logs into Retell dashboard
2. Edits agent prompt, conversation flow, or functions
3. Clicks "Save" or "Deploy"
4. Retell automatically increments: agent_version = 130
5. Next webhook shows: "agent_version": 130
6. Your Laravel code automatically picks it up (line 241 of RetellApiClient.php)
7. Next call stored in DB will have agent_version = 130
8. No action required from you
```

---

## Database Storage

### Where agent_version Is Stored
```php
// calls table
Schema::create('calls', function (Blueprint $table) {
    // ... other columns ...
    $table->string('agent_version')->nullable();
    //     ↑ Stores: "129", "130", etc. from Retell webhooks
});
```

### Where Our Code Version Is NOT Stored
```php
// NOT in database
// It's only in code comments like:
/**
 * 🚀 V17: Check Availability Wrapper
 */
public function checkAvailabilityV17() { ... }

// If you want to track it, you could add:
$table->string('code_version')->default('V17');
```

---

## Summary: Are V17 Wrappers Compatible with agent_version 24?

```
QUESTION: "Is our V17 code compatible with Retell's agent_version 24?"

ANSWER: YES, 100% compatible.

WHY:
- V17 defines endpoints: /v17/check-availability, /v17/book-appointment
- Conversation flow calls these endpoints via function nodes
- agent_version (24, 25, 129, etc.) is just metadata
- The endpoints don't care what agent_version is - they work the same
- agent_version is only used for tracking/logging, not for routing

ANALOGY:
V17 code is like a kitchen (the equipment)
agent_version is like a log book entry (just records what happened)
The kitchen works regardless of what you write in the log book
```

---

## Files to Check

```
Code Version V17:
  ✓ /var/www/api-gateway/app/Http/Controllers/RetellFunctionCallHandler.php (lines 4046-4088)
  ✓ /var/www/api-gateway/routes/api.php

agent_version Storage:
  ✓ /var/www/api-gateway/app/Services/RetellApiClient.php (line 241)
  ✓ /var/www/api-gateway/app/Models/Call.php (column definition)

Documentation:
  ✓ /var/www/api-gateway/claudedocs/03_API/Retell_AI/V17_DEPLOYMENT_SUCCESS_2025-10-22.md
  ✓ /var/www/api-gateway/claudedocs/03_API/Retell_AI/AGENT_IDS_REFERENZ.md
  ✓ /var/www/api-gateway/RETELL_VERSION_NUMBERING_ANALYSIS.md (detailed analysis)
```

---

## Quick Decision Tree

```
Q: I see "agent_version: 24" in a call
A: This is Retell's counter. Your code version (V17) is unrelated.

Q: Do I need to update code for agent_version 24?
A: No. The agent_version is automatic and informational only.

Q: When should I increment to V18?
A: When YOU modify the backend logic in RetellFunctionCallHandler.php

Q: Will my V17 code work with agent_version 100?
A: Yes. The agent_version only indicates how many times Retell was updated.

Q: What version number should I track?
A: Your code version (V17). agent_version will change automatically.

Q: Can I rollback agent_version?
A: Yes, via Retell dashboard using conversation flow versions.

Q: Can I rollback my code version (V17)?
A: Yes, via git to previous commit.
```

---

## Do You Need to Take Action?

```
✅ YES, understand these are separate systems
✅ YES, document which system you're discussing (code? agent? flow?)
✅ YES, consider renaming our V-system to avoid confusion
   (e.g., "APIv2.0", "BackendV17", "AskProV17")

❌ NO, don't try to sync the version numbers
❌ NO, don't change code because agent_version changed
❌ NO, don't wait for agent_version to match your code version
```

---

**Quick Ref Created:** 2025-10-23
**For Questions:** See RETELL_VERSION_NUMBERING_ANALYSIS.md
