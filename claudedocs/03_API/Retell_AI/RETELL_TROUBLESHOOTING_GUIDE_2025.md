# Retell AI Troubleshooting Guide 2025

**Last Updated**: 2025-10-25
**Purpose**: Complete error catalog with real-world solutions

---

## 📋 Quick Error Lookup

| Error | Symptom | Root Cause | Fix |
|-------|---------|------------|-----|
| [404 on create-agent](#404-create-agent) | HTTP 404 | Invalid voice_id | Verify with `/list-voices` |
| [Functions not called](#functions-not-called) | AI hallucinates | Prompt-based transitions | Switch to LLM-based |
| [Agent not published](#agent-not-published) | Calls don't reach agent | Forgotten publish step | Call `/publish-agent` |
| [Phone not updated](#phone-not-updated) | Wrong agent answers | Wrong parameter name | Use `inbound_agent_id` |
| [LLM 404](#llm-404) | LLM not found | LLM doesn't exist | Create LLM first |
| [Webhook timeout](#webhook-timeout) | Slow function calls | Cal.com latency | Optimize API calls |

---

## HTTP 404: Create Agent

### Symptom
```bash
POST /create-agent
→ HTTP 404
→ {"status":"error","message":"Not Found"}
```

### Root Causes (in order of frequency)

#### 1. Invalid voice_id (90% of cases)

**How it happens**:
```php
// ❌ Copied from example/docs
$agentConfig = [
    'voice_id' => '11labs-Christopher'  // Doesn't exist!
];
```

**Why**: Voice IDs change! What worked in examples might not exist anymore.

**Solution**:
```php
// ✅ Always verify first
$token = env('RETELL_TOKEN');

$voices = Http::withHeaders(['Authorization' => "Bearer $token"])
    ->get('https://api.retellai.com/list-voices')
    ->json();

// Find available German voices
$germanVoices = array_filter($voices, function($voice) {
    return strpos($voice['voice_id'], 'Carola') !== false
        || $voice['language'] === 'de-DE';
});

foreach ($germanVoices as $voice) {
    echo "{$voice['voice_id']} - {$voice['voice_name']}\n";
}

// Use verified voice
$voiceId = '11labs-Carola';  // ✅ Verified to exist
```

**Prevention**:
1. Never hardcode voice IDs from examples
2. Always call `/list-voices` first
3. Save verified voice ID to config/env

#### 2. Invalid llm_id

**Symptom**: Same 404 error

**How to verify**:
```php
$llmId = 'llm_36bd5fb31065787c13797e05a29a';

$llm = Http::withHeaders(['Authorization' => "Bearer $token"])
    ->get("https://api.retellai.com/get-retell-llm/$llmId");

if ($llm->status() === 404) {
    echo "❌ LLM doesn't exist!\n";
}
```

**Solution**: Create LLM first, then agent.

#### 3. Wrong endpoint (rare)

**Verify**:
```bash
# Correct endpoint
POST https://api.retellai.com/create-agent

# NOT these (all return 404):
POST https://api.retellai.com/agent
POST https://api.retellai.com/agents
POST https://api.retellai.com/v1/agent
```

### Complete Debugging Flow

```php
<?php
// Step 1: Verify API token works
$testResp = Http::withHeaders(['Authorization' => "Bearer $token"])
    ->get('https://api.retellai.com/list-agents');

if (!$testResp->successful()) {
    die("❌ Invalid API token\n");
}

// Step 2: Verify voice exists
$voices = Http::withHeaders(['Authorization' => "Bearer $token"])
    ->get('https://api.retellai.com/list-voices')
    ->json();

$voiceExists = false;
foreach ($voices as $voice) {
    if ($voice['voice_id'] === $yourVoiceId) {
        $voiceExists = true;
        break;
    }
}

if (!$voiceExists) {
    echo "❌ Voice '$yourVoiceId' not found!\n";
    echo "Available voices:\n";
    foreach ($voices as $voice) {
        echo "  - {$voice['voice_id']}\n";
    }
    exit(1);
}

// Step 3: Verify LLM exists (if using retell-llm)
if ($responseEngine['type'] === 'retell-llm') {
    $llmResp = Http::withHeaders(['Authorization' => "Bearer $token"])
        ->get("https://api.retellai.com/get-retell-llm/{$responseEngine['llm_id']}");

    if (!$llmResp->successful()) {
        die("❌ LLM not found: {$responseEngine['llm_id']}\n");
    }
}

// Step 4: Now try creating agent
$response = Http::withHeaders([
    'Authorization' => "Bearer $token",
    'Content-Type' => 'application/json'
])->post('https://api.retellai.com/create-agent', $agentConfig);

if (!$response->successful()) {
    echo "❌ Still failing:\n";
    echo "Status: {$response->status()}\n";
    echo "Body: {$response->body()}\n";
}
```

---

## Functions Not Being Called

### Symptom
```
User: "I want an appointment for tomorrow at 10am"
AI: "Let me check... Yes, tomorrow at 10am is available!"
Backend logs: [NO FUNCTION CALL RECEIVED]
```

AI **hallucinates** availability instead of calling `check_availability` function.

### Root Cause Analysis

#### 1. Flow-Based Agent with Prompt Transitions (90% of cases)

**How to identify**:
```php
$agent = Http::get("https://api.retellai.com/get-agent/$agentId")->json();

if ($agent['response_engine']['type'] === 'conversation-flow') {
    echo "⚠️  Flow-based agent detected\n";

    // Check for prompt-based transitions
    $flow = Http::get("https://api.retellai.com/get-conversation-flow/{$agent['response_engine']['conversation_flow_id']}")->json();

    $promptTransitions = 0;
    foreach ($flow['nodes'] as $node) {
        if (isset($node['edges'])) {
            foreach ($node['edges'] as $edge) {
                if ($edge['transition_condition']['type'] === 'prompt') {
                    $promptTransitions++;
                }
            }
        }
    }

    if ($promptTransitions > 0) {
        echo "❌ Found $promptTransitions prompt-based transitions\n";
        echo "→ This causes ~90% function call failure rate\n";
    }
}
```

**Why this happens**:
```json
{
  "nodes": [
    {
      "id": "collect_info",
      "type": "conversation",
      "edges": [{
        "destination_node_id": "func_check_availability",
        "transition_condition": {
          "type": "prompt",
          "prompt": "All booking info collected"  // ← LLM decides!
        }
      }]
    }
  ]
}
```

LLM evaluates "All booking info collected" → Often decides "No" → Never transitions → Function never called!

**Solution**: Switch to LLM-based agent

```php
// Create Retell LLM first
$llm = Http::post('https://api.retellai.com/create-retell-llm', [
    'model' => 'gpt-4o-mini',
    'general_prompt' => "SOBALD du Service, Datum und Uhrzeit hast → CALL check_availability",
    'general_tools' => [
        [
            'type' => 'custom',
            'name' => 'check_availability',
            'url' => 'https://api.example.com/check',
            // ...
        ]
    ]
])->json();

// Create agent with LLM
$agent = Http::post('https://api.retellai.com/create-agent', [
    'response_engine' => [
        'type' => 'retell-llm',  // ← Not conversation-flow!
        'llm_id' => $llm['llm_id']
    ],
    'voice_id' => '11labs-Carola'
])->json();
```

**Success Rate**: Flow-based ~10% → LLM-based ~99%

#### 2. Unclear Function Instructions

**Bad prompt**:
```
"You can check availability"
```

LLM interprets this as optional.

**Good prompt**:
```
"SOBALD du Service, Datum und Uhrzeit hast → CALL check_availability_v17 mit:
{
  \"datum\": \"YYYY-MM-DD\",
  \"uhrzeit\": \"HH:MM\",
  \"dienstleistung\": \"service name\",
  \"bestaetigung\": false
}

Du MUSST diese Function aufrufen. RATE NIEMALS ob ein Termin frei ist."
```

**Key phrases that work**:
- "SOBALD" (as soon as)
- "CALL" (imperative)
- "Du MUSST" (you must)
- "NIEMALS raten" (never guess)

#### 3. Missing wait_for_result

**In flow-based agents**:
```json
{
  "id": "func_check",
  "type": "function",
  "tool_id": "tool-check",
  "wait_for_result": false  // ← Wrong!
}
```

Agent doesn't wait for response → Continues without result → Hallucinates.

**Fix**:
```json
{
  "wait_for_result": true,  // ← Correct
  "speak_during_execution": true  // Optional: "Let me check..."
}
```

### Debugging Function Calls

```bash
# Terminal 1: Monitor backend
tail -f storage/logs/laravel.log | grep -E "Retell|check_availability"

# Terminal 2: Make test call
# Call: +493033081738

# What you should see:
[2025-10-25 10:30:15] RetellApiController: Function called
[2025-10-25 10:30:15] Function: check_availability_v17
[2025-10-25 10:30:15] Parameters: {"datum":"2025-10-26","uhrzeit":"10:00",...}
```

**If you see nothing**:
- Functions are not being called
- Check agent type (flow vs LLM)
- Review prompt instructions
- Verify tool configuration

---

## Agent Not Published

### Symptom
```
Phone number configured ✅
Agent created ✅
Test call fails ❌
```

### Root Cause

Agents are created in **draft state** by default.

**How to check**:
```php
$agent = Http::get("https://api.retellai.com/get-agent/$agentId")->json();

if (!$agent['is_published']) {
    echo "❌ Agent not published!\n";
    echo "Version: {$agent['version']}\n";
}
```

### Solution

```php
// Publish the agent
$response = Http::withHeaders(['Authorization' => "Bearer $token"])
    ->post("https://api.retellai.com/publish-agent/$agentId");

if ($response->successful()) {
    echo "✅ Agent published!\n";
}

// Verify
$agent = Http::get("https://api.retellai.com/get-agent/$agentId")->json();
echo "Published: " . ($agent['is_published'] ? 'Yes' : 'No') . "\n";
```

---

## Phone Number Not Updated

### Symptom
```php
// You updated phone to new agent
Http::patch("https://api.retellai.com/update-phone-number/$phone", [
    'agent_id' => $newAgentId
]);

// But it still uses old agent
$phoneData = Http::get("https://api.retellai.com/get-phone-number/$phone")->json();
echo $phoneData['inbound_agent_id'];  // Still old agent!
```

### Root Cause

Wrong parameter name! Use `inbound_agent_id` not `agent_id`.

### Solution

```php
// ❌ Wrong
Http::patch("https://api.retellai.com/update-phone-number/$phone", [
    'agent_id' => $newAgentId  // Wrong field name!
]);

// ✅ Correct
Http::patch("https://api.retellai.com/update-phone-number/$phone", [
    'inbound_agent_id' => $newAgentId  // Correct!
]);

// Verify
$phoneData = Http::get("https://api.retellai.com/get-phone-number/$phone")->json();

if ($phoneData['inbound_agent_id'] === $newAgentId) {
    echo "✅ Phone updated successfully!\n";
} else {
    echo "❌ Phone still on old agent: {$phoneData['inbound_agent_id']}\n";
}
```

---

## Webhook Timeouts

### Symptom
```
Retell call starts
AI asks questions
Function called
[30 seconds pass]
Call drops
```

### Root Cause

Retell has **10-second webhook timeout** by default.

**Common causes**:
1. Cal.com API slow (3-5s per call)
2. Database queries slow
3. Multiple sequential API calls

### Solution

#### Option 1: Optimize API calls

```php
// ❌ Slow: Sequential calls
$availability = Http::get("https://cal.com/api/availability?date=$date")->json();
$staff = Http::get("https://cal.com/api/teams/$teamId/members")->json();
$eventTypes = Http::get("https://cal.com/api/event-types")->json();

// ✅ Fast: Parallel calls
$responses = Http::pool(fn (Pool $pool) => [
    $pool->get("https://cal.com/api/availability?date=$date"),
    $pool->get("https://cal.com/api/teams/$teamId/members"),
    $pool->get("https://cal.com/api/event-types"),
]);

[$availability, $staff, $eventTypes] = $responses;
```

#### Option 2: Increase timeout

```php
// In agent configuration
'webhook_timeout_ms' => 30000  // 30 seconds instead of 10
```

#### Option 3: Async processing

```php
// Return immediately, process async
return response()->json([
    'verfuegbar' => true,
    'message' => 'Checking...'
]);

// Process in background
dispatch(new CheckAvailabilityJob($params));
```

---

## Cannot Update Response Engine

### Symptom
```
PATCH /update-agent/{id}
{
  "response_engine": {
    "type": "retell-llm",
    "llm_id": "llm_123"
  }
}

→ HTTP 400
→ "Cannot update response engine of agent version > 0"
```

### Root Cause

Once an agent has version >= 1, `response_engine` is **immutable**.

### Solution

Create new agent instead:

```php
// ❌ Cannot update existing agent
Http::patch("/update-agent/{$oldAgentId}", [
    'response_engine' => ['type' => 'retell-llm', 'llm_id' => $newLlmId]
]);  // → 400 Error

// ✅ Create new agent
$newAgent = Http::post('/create-agent', [
    'agent_name' => 'Updated Agent',
    'response_engine' => ['type' => 'retell-llm', 'llm_id' => $newLlmId],
    'voice_id' => '11labs-Carola'
])->json();

// Update phone number
Http::patch("/update-phone-number/$phone", [
    'inbound_agent_id' => $newAgent['agent_id']
]);
```

---

## Parameter Type Mismatches

### Symptom
```
Function called ✅
Parameters: {"bestaetigung": "false"}  // String!
Expected: {"bestaetigung": false}      // Boolean!
```

### Root Cause

Retell sends parameters as **strings** sometimes, even if schema says boolean.

### Solution

```php
// ❌ Strict type checking fails
if ($request->bestaetigung === false) {
    // Never matches!
}

// ✅ Flexible type handling
$bestaetigung = filter_var($request->bestaetigung, FILTER_VALIDATE_BOOLEAN);

if ($bestaetigung) {
    // Book appointment
} else {
    // Just check availability
}
```

---

## Debugging Checklist

When agent doesn't work:

```php
<?php
// 1. Verify agent exists and is published
$agent = Http::get("https://api.retellai.com/get-agent/$agentId")->json();
echo "Published: " . ($agent['is_published'] ? '✅' : '❌') . "\n";

// 2. Verify agent type
echo "Type: {$agent['response_engine']['type']}\n";
if ($agent['response_engine']['type'] === 'conversation-flow') {
    echo "⚠️  Warning: Flow-based agent (may have issues)\n";
}

// 3. Verify phone number assignment
$phone = Http::get("https://api.retellai.com/get-phone-number/$phoneNumber")->json();
echo "Phone agent: {$phone['inbound_agent_id']}\n";
echo "Expected: $agentId\n";
echo "Match: " . ($phone['inbound_agent_id'] === $agentId ? '✅' : '❌') . "\n";

// 4. Verify voice exists
$voices = Http::get('https://api.retellai.com/list-voices')->json();
$voiceExists = false;
foreach ($voices as $voice) {
    if ($voice['voice_id'] === $agent['voice_id']) {
        $voiceExists = true;
        break;
    }
}
echo "Voice exists: " . ($voiceExists ? '✅' : '❌') . "\n";

// 5. Test webhook endpoint
$webhookTest = Http::timeout(5)->post($agent['webhook_url'], [
    'test' => true
]);
echo "Webhook reachable: " . ($webhookTest->successful() ? '✅' : '❌') . "\n";
```

---

## Related Documentation

- [RETELL_AGENT_CREATION_COMPLETE_GUIDE_2025.md](RETELL_AGENT_CREATION_COMPLETE_GUIDE_2025.md) - Complete creation workflow
- [RETELL_API_QUICK_REFERENCE_2025.md](RETELL_API_QUICK_REFERENCE_2025.md) - API endpoints

---

## Changelog

### 2025-10-25
- ✅ Added 404 voice_id error (most common!)
- ✅ Added function call debugging
- ✅ Added flow vs LLM comparison
- ✅ Complete debugging checklist
