# Technical Integration Guide: Version System Mapping

**Document:** How the three version systems integrate with each other
**Updated:** 2025-10-23
**Audience:** Backend engineers, DevOps, debugging

---

## Data Flow: Call Received → Stored → Analyzed

```
1. INCOMING CALL (Retell)
   ├─ agent_id: agent_9a8202a740cd3120d96fcfda1e
   ├─ agent_version: 129                    ← Retell's counter
   ├─ agent_name: "...V126"                 ← Display name (not our V-system)
   └─ call_id: call_23fac465f81b8eaab34e5ec3818

2. FUNCTION CALL → OUR CODE (V17)
   ├─ URL Path: /api/retell/v17/check-availability
   │           ↑ Our code version marker
   ├─ Handler: checkAvailabilityV17()
   │           ↑ Our code version
   └─ Request contains: { "call": { ... agent_version: 129 ... } }

3. OUR HANDLER PROCESSES
   ├─ Extract: $callData['agent_version'] = 129
   ├─ Log: Log::info('🔍 V17: Check Availability...')
   │        ↑ Our code version in log
   └─ Execute: $this->collectAppointment($request)

4. WEBHOOK CALLBACK
   ├─ Retell sends: { "agent_version": 129 }
   ├─ Stored via: 'agent_version' => $callData['agent_version'] ?? null
   └─ Database: calls.agent_version = "129"

5. ANALYTICS/REPORTING
   ├─ Query: SELECT agent_version FROM calls WHERE...
   │   Result: agent_version = 129 (Retell's tracker)
   ├─ Code Review: Still says V17 (Our tracker)
   └─ Documentation: Both are tracked independently
```

---

## Code-to-Database Mapping

### Call Arrival Webhook Payload
```json
{
  "call": {
    "call_id": "call_23fac465f81b8eaab34e5ec3818",
    "call_type": "phone_call",
    "agent_id": "agent_9a8202a740cd3120d96fcfda1e",
    "agent_version": 129,          ← Retell's version
    "agent_name": "Online: Assistent für Fabian Spitzer Rechtliches/V126",
    "call_status": "ongoing",
    "start_timestamp": 1761110857282,
    "transcript": "Agent: Willkommen bei Ask Pro AI...\nUser: Ja, ich hätte gern...",
    ...
  }
}
```

### Laravel Data Mapping
```php
// File: /var/www/api-gateway/app/Services/RetellApiClient.php (line 241)

$syncData = [
    'retell_call_id' => $callData['call_id'],                    // "call_xxx"
    'retell_agent_id' => $callData['agent_id'],                  // "agent_xxx"
    'agent_version' => $callData['agent_version'] ?? null,       // 129 ← MAPPED HERE
    'call_status' => $callData['call_status'],                   // "ongoing"
    'transcript' => $callData['transcript'] ?? null,             // Full transcript
    // ... other fields ...
];

// Stored in database:
Call::create($syncData);
```

### Database Schema
```sql
-- Table: calls
CREATE TABLE calls (
    id INT PRIMARY KEY AUTO_INCREMENT,
    retell_call_id VARCHAR(255) UNIQUE,     -- "call_xxx"
    retell_agent_id VARCHAR(255),           -- "agent_xxx"
    agent_version VARCHAR(255),             -- "129" ← Stored here
    call_status VARCHAR(50),                -- "ongoing", "ended"
    transcript LONGTEXT,                    -- Full transcript
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    -- ... other columns ...
);
```

---

## Version Tracking During Call Lifecycle

### Stage 1: Call Initiated (Retell → Us)
```
Retell sends:
├─ agent_version: 24           ← First webhook has version 24
└─ agent_name: ".../V126"      ← Display name

Our code:
├─ Endpoint called: /v17/...   ← Still V17, unchanged
└─ No version increment
```

### Stage 2: During Call (Multiple Events)
```
Event: Function Call (parse_date)
├─ URL: /api/retell/v17/check-availability
├─ Handler logs: Log::info('🔍 V17: Check Availability...')
├─ Database: agent_version = 24 (unchanged from event 1)
└─ Our code: Still V17

Event: Another Function Call
├─ Same endpoint: /v17/check-availability
├─ Same handler: checkAvailabilityV17()
├─ Database: agent_version = 24 (consistent)
└─ Our code: Still V17
```

### Stage 3: Call Ends (Retell → Us)
```
Retell sends:
├─ event: "call_ended"
├─ agent_version: 24           ← Same as event 1
└─ Full transcript + analysis

Our code:
├─ Processes: handleCallEnded()
├─ Stores: agent_version = 24 in database
└─ Code version: Still V17 (in comments only)
```

### Stage 4: Analytics
```
Database query:
┌─────────────────────────────────────────────┐
│ SELECT                                      │
│   agent_version,                            │
│   COUNT(*) as call_count                    │
│ FROM calls                                  │
│ WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 DAY)
│ GROUP BY agent_version;                     │
└─────────────────────────────────────────────┘

Result:
┌────────────────┬────────────┐
│ agent_version  │ call_count │
├────────────────┼────────────┤
│ 129            │ 45         │  ← Retell's tracking
│ 128            │ 12         │  ← Previous version
│ 117            │ 0          │  ← Broken version (P0)
└────────────────┴────────────┘

Note: Our code version (V17) is NOT in this table
      It's only in code comments:
      - RetellFunctionCallHandler.php line 4046: "🚀 V17:"
      - Log messages contain: "V17: Check Availability"
```

---

## Routes and Endpoints

### URL Structure Indicates Code Version
```
Endpoint: /api/retell/v17/check-availability
                        ↑
                   Our code version

File: routes/api.php
Route::post('/v17/check-availability',
    [\App\Http\Controllers\RetellFunctionCallHandler::class, 'checkAvailabilityV17'])
           ↑ Same version in class method name
```

### Conversation Flow → Code Mapping
```
Retell Conversation Flow:
┌─────────────────────────────────────────┐
│ Node: func_check_availability           │
├─────────────────────────────────────────┤
│ Type: Function Node (Explicit)          │
│ Tool: tool-v17-check-availability       │
│                   ↑                      │
│                  Version marker         │
│ URL: https://api.askproai.de/api/       │
│      retell/v17/check-availability      │
│                  ↑                       │
│            Our code version             │
│ Method: POST                            │
│ Timeout: 10000 ms                       │
└─────────────────────────────────────────┘
           ↓ Calls
┌─────────────────────────────────────────┐
│ Laravel Route (routes/api.php)          │
│                                         │
│ Route::post('/v17/check-availability',  │
│     [RetellFunctionCallHandler::class,  │
│      'checkAvailabilityV17'])           │
│      ↑                                   │
│    Version V17 embedded in method name  │
└─────────────────────────────────────────┘
           ↓ Routes to
┌─────────────────────────────────────────┐
│ Handler Method                          │
│ RetellFunctionCallHandler.php           │
│                                         │
│ public function checkAvailabilityV17()  │
│ {                                       │
│   // 🚀 V17: Check Availability         │
│   $request->merge(['bestaetigung' => false]);
│   return $this->collectAppointment($request);
│ }                                       │
└─────────────────────────────────────────┘
```

---

## Logging Pattern: Extracting Version Information

### Before: No Clear Version Tracking
```php
// Old style (implicit)
Log::info('Check availability called', ['call_id' => $callId]);
// → Can't tell if V15, V16, or V17 handled it
```

### After: Explicit Version Tracking
```php
// Current style (V17)
Log::info('🔍 V17: Check Availability (bestaetigung=false)', [
    'call_id' => $request->input('call.call_id'),
    'agent_version' => $request->input('call.agent_version'),  // From Retell
    'params' => $request->except(['call'])
]);

// Result in logs:
// "🔍 V17: Check Availability (bestaetigung=false)"
// {"call_id":"call_xxx","agent_version":129,"params":{...}}
//  ↑ Our code version       ↑ Retell's version

// Parsing logs:
// grep "🔍 V17:" logs/laravel.log | wc -l
// → Count of calls handled by V17
```

---

## Migration Path: V17 → V18

### Step 1: Add V18 Code
```php
// RetellFunctionCallHandler.php

/**
 * 🚀 V18: Check Availability Wrapper - IMPROVED VERSION
 * POST /api/retell/v18/check-availability
 *
 * Changes from V17:
 * - Better error handling
 * - Faster response time
 * - Enhanced logging
 */
public function checkAvailabilityV18(CollectAppointmentRequest $request)
{
    Log::info('🔍 V18: Check Availability (bestaetigung=false)', [
        'call_id' => $request->input('call.call_id'),
        'params' => $request->except(['call'])
    ]);

    $request->merge(['bestaetigung' => false]);
    return $this->collectAppointment($request);
}
```

### Step 2: Add V18 Routes
```php
// routes/api.php

Route::post('/v18/check-availability',
    [\App\Http\Controllers\RetellFunctionCallHandler::class, 'checkAvailabilityV18'])
    ->name('api.retell.v18.check-availability')
    ->middleware(['throttle:100,1'])
    ->withoutMiddleware('retell.function.whitelist');
```

### Step 3: Test Both Versions in Parallel
```
Conversation Flow has two function nodes:
├─ func_check_availability_v17 → /v17/check-availability
└─ func_check_availability_v18 → /v18/check-availability  (new)

A/B test or migrate percentage of calls:
├─ 90% traffic → /v17/
└─ 10% traffic → /v18/ (new)

Monitor metrics:
├─ Success rate: v17 vs v18
├─ Response time: v17 vs v18
├─ Error rate: v17 vs v18
└─ Appointment created: v17 vs v18
```

### Step 4: Migrate Conversation Flow to V18
```
Retell updates:
├─ Modify func_check_availability node: /v18/check-availability
├─ Deploy conversation flow → Retell increments flow_version
├─ Retell auto-increments agent_version
└─ Next call shows agent_version: 130 (or higher)

Our code:
├─ Still contains V17 code (not deleted)
├─ Code version: V18 (in new method comments)
├─ Both endpoints available: /v17/ and /v18/
└─ Traffic now goes to V18

Database:
├─ Old calls: agent_version: 129, code_version: V17 (if tracked)
└─ New calls: agent_version: 130, code_version: V18 (if tracked)
```

### Step 5: Retire V17
```
After V18 stable for 2 weeks:
├─ Remove V17 routes from routes/api.php
├─ Remove V17 methods from RetellFunctionCallHandler.php
├─ Git commit: "Retire: Remove V17 after V18 stabilized"
└─ Keep V17 in git history for rollback if needed

NOTE: agent_version still tracks all versions
      Because Retell sends it for every call
      You can still analyze old calls with agent_version: 129
```

---

## Querying and Reporting

### Report: Which Code Version Handled Each Call?
```php
// NOT DIRECTLY POSSIBLE
// agent_version tells us Retell's updates, not our code version

// Workaround: Add code_version tracking
Schema::table('calls', function (Blueprint $table) {
    $table->string('code_version')->nullable()->comment('Our internal version (V17, V18, etc)');
});

// Then in handler:
Call::create([
    'agent_version' => 129,  // From Retell
    'code_version' => 'V17', // From our code
    // ... other fields ...
]);

// Query:
SELECT code_version, agent_version, COUNT(*) as count
FROM calls
GROUP BY code_version, agent_version;

// Result:
┌──────────────┬────────────────┬───────┐
│ code_version │ agent_version  │ count │
├──────────────┼────────────────┼───────┤
│ V17          │ 129            │ 45    │
│ V16          │ 128            │ 23    │
│ V15          │ 46             │ 12    │
└──────────────┴────────────────┴───────┘
```

### Report: Success Rate by Retell Version
```sql
-- This works: Retell versions are in DB
SELECT
    agent_version,
    COUNT(*) as total_calls,
    SUM(CASE WHEN appointment_made = 1 THEN 1 ELSE 0 END) as successful_bookings,
    ROUND(100 * SUM(CASE WHEN appointment_made = 1 THEN 1 ELSE 0 END) / COUNT(*), 2) as success_rate
FROM calls
WHERE created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
GROUP BY agent_version
ORDER BY agent_version DESC;

Result:
┌────────────────┬──────────────┬──────────────────┬──────────────┐
│ agent_version  │ total_calls  │ successful_bookings │ success_rate │
├────────────────┼──────────────┼──────────────────┼──────────────┤
│ 129            │ 245          │ 128              │ 52.24%       │
│ 128            │ 89           │ 35               │ 39.33%       │
│ 117            │ 23           │ 0                │ 0.00%        │ ← P0 incident
└────────────────┴──────────────┴──────────────────┴──────────────┘
```

---

## Troubleshooting Version Mismatches

### Scenario 1: "We deployed V18 code, but calls still show old behavior"
```
Diagnosis:
├─ Check: Code version in handler comments (V18 present?) ✓
├─ Check: Route registered in routes/api.php (/v18/...)? ✓
├─ Check: Conversation flow updated to call /v18/...? ❌ PROBLEM
└─ Fix: Update Retell conversation flow to point to /v18/ endpoint

Evidence:
- Code change: ✓ RetellFunctionCallHandler.php has checkAvailabilityV18()
- Route: ✓ routes/api.php has Route::post('/v18/check-availability', ...)
- Flow: ❌ Still calling /v17/check-availability

Action:
1. Log into Retell dashboard
2. Edit func_check_availability node
3. Change URL from /v17/... to /v18/...
4. Deploy conversation flow
5. Wait for CDN propagation (~15 min)
6. Make test call
7. Check logs: Should show "🚀 V18:" messages
```

### Scenario 2: "agent_version hasn't incremented after Retell update"
```
Diagnosis:
Retell auto-increments agent_version when you:
├─ Update agent prompt
├─ Update conversation flow
├─ Update function definitions
├─ Publish agent

If it hasn't incremented:
├─ Check: Did you actually SAVE the changes? (vs just previewing)
├─ Check: Did you DEPLOY/PUBLISH? (vs leaving in edit mode)
├─ Check: Confirm in Retell dashboard that changes are live
└─ Wait: Maybe testing with cached old agent version

Evidence:
- Old behavior still observed
- agent_version in logs: Still showing 129 (not 130)
- Conversation flow tests: Old version still running

Action:
1. Log into Retell dashboard
2. Make a test change (e.g., edit agent name)
3. Save and Deploy
4. Make test call immediately
5. Check logs: agent_version should now be 130+
6. If not: Clear browser cache, try from different network
```

### Scenario 3: "Calls using different code versions simultaneously"
```
Diagnosis:
This is EXPECTED and OK during migration.

Scenario:
├─ V17 code running with 90% traffic
├─ V18 code running with 10% traffic
├─ Both endpoints in production
└─ A/B testing metrics

Database:
SELECT code_version, COUNT(*)
FROM calls
WHERE created_at > NOW() - INTERVAL 1 HOUR
GROUP BY code_version;

Result:
┌──────────────┬───────┐
│ code_version │ count │
├──────────────┼───────┤
│ V17          │ 54    │ (90%)
│ V18          │ 6     │ (10%)
└──────────────┴───────┘

This is FINE. Controlled rollout.

However, agent_version will ALSO vary:
┌────────────────┬───────┐
│ agent_version  │ count │
├────────────────┼───────┤
│ 129            │ 60    │ (100%)
└────────────────┴───────┘

Expected: Same agent_version for all calls in this hour
Reason: We haven't updated Retell flow yet (still pointing to /v17/)
Next step: When V18 is stable, update flow to /v18/
Then: agent_version will increment to 130 (automatic)
```

---

## Summary: Version Integration Checklist

- [ ] Understand V17 is our code version (in comments/logs)
- [ ] Understand agent_version is Retell's tracking (in webhooks/DB)
- [ ] Understand flow_version is deployment tracking (in Retell API)
- [ ] Add logging that includes both our version and agent_version
- [ ] (Optional) Add code_version column to calls table for tracking
- [ ] Document in README which version system you're discussing
- [ ] In PRs, mention: "V17 code changes" (not "V17 agent update")
- [ ] In Retell updates, mention: "agent_version incremented" (not code version)
- [ ] Monitor both metrics independently in dashboards

---

**Guide Created:** 2025-10-23
**For:** Technical team, debugging, integration work
**Review with:** DevOps, Backend leads before deployment
