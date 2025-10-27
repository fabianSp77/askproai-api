# Retell AI - API Quick Reference

**Last Updated**: 2025-10-25
**Purpose**: Fast lookup for all Retell AI API endpoints with code examples

---

## 🔑 Authentication

All requests require Bearer token in header:

```php
use Illuminate\Support\Facades\Http;

$token = env('RETELL_TOKEN');

$response = Http::withHeaders([
    'Authorization' => "Bearer $token",
    'Content-Type' => 'application/json'
])->get('https://api.retellai.com/endpoint');
```

---

## 📞 Phone Number Management

### Get Phone Number
```php
$phone = '+493033081738';
$response = Http::withHeaders(['Authorization' => "Bearer $token"])
    ->get("https://api.retellai.com/get-phone-number/$phone");

$data = $response->json();
echo $data['inbound_agent_id'];  // Current agent assigned
```

### Update Phone Number
```php
$phone = '+493033081738';
$agentId = 'agent_773a5034bd8a7b7fb98cd4ab0c';

$response = Http::withHeaders([
    'Authorization' => "Bearer $token",
    'Content-Type' => 'application/json'
])->patch("https://api.retellai.com/update-phone-number/$phone", [
    'inbound_agent_id' => $agentId  // ✅ Correct parameter name!
]);

// ❌ Common mistake: Using 'agent_id' instead of 'inbound_agent_id'
```

### List Phone Numbers
```php
$response = Http::withHeaders(['Authorization' => "Bearer $token"])
    ->get('https://api.retellai.com/list-phone-numbers');

$phones = $response->json();
foreach ($phones as $phone) {
    echo "{$phone['phone_number']} → {$phone['inbound_agent_id']}\n";
}
```

---

## 🤖 Agent Management

### Create Agent (LLM-based) ⭐ RECOMMENDED

**CRITICAL**: Always verify voice_id first with `/list-voices`!

```php
// Step 1: Get available voices
$voices = Http::withHeaders(['Authorization' => "Bearer $token"])
    ->get('https://api.retellai.com/list-voices')
    ->json();

// Step 2: Find German voice
$voiceId = null;
foreach ($voices as $voice) {
    if (strpos($voice['voice_id'], 'Carola') !== false) {
        $voiceId = $voice['voice_id'];  // 11labs-Carola
        break;
    }
}

// Step 3: Create agent
$llmId = 'llm_36bd5fb31065787c13797e05a29a';

$response = Http::withHeaders([
    'Authorization' => "Bearer $token",
    'Content-Type' => 'application/json'
])->post('https://api.retellai.com/create-agent', [
    'agent_name' => 'My Agent',
    'response_engine' => [
        'type' => 'retell-llm',  // ✅ LLM-based for high success rate
        'llm_id' => $llmId
    ],
    'voice_id' => $voiceId,  // ✅ Verified voice
    'language' => 'de-DE',
    'enable_backchannel' => true,
    'responsiveness' => 1.0,
    'interruption_sensitivity' => 1,
    'webhook_url' => 'https://api.askproai.de/api/webhooks/retell'
]);

$agent = $response->json();
echo "Agent ID: {$agent['agent_id']}\n";
```

**Common 404 Error**: Invalid `voice_id` (e.g., using `11labs-Christopher` which doesn't exist)

### Create Agent (Flow-based)

⚠️ **NOT RECOMMENDED**: ~10% success rate with prompt-based transitions

```php
$response = Http::withHeaders([
    'Authorization' => "Bearer $token",
    'Content-Type' => 'application/json'
])->post('https://api.retellai.com/create-agent', [
    'agent_name' => 'Flow Agent',
    'response_engine' => [
        'type' => 'conversation-flow',
        'conversation_flow_id' => 'flow_abc123'
    ],
    'voice_id' => '11labs-Carola'
]);
```

### Get Agent
```php
$agentId = 'agent_773a5034bd8a7b7fb98cd4ab0c';

$response = Http::withHeaders(['Authorization' => "Bearer $token"])
    ->get("https://api.retellai.com/get-agent/$agentId");

$agent = $response->json();
echo "Published: " . ($agent['is_published'] ? 'Yes' : 'No') . "\n";
echo "Type: {$agent['response_engine']['type']}\n";
echo "Version: {$agent['version']}\n";
```

### Update Agent
```php
$agentId = 'agent_773a5034bd8a7b7fb98cd4ab0c';

$response = Http::withHeaders([
    'Authorization' => "Bearer $token",
    'Content-Type' => 'application/json'
])->patch("https://api.retellai.com/update-agent/$agentId", [
    'agent_name' => 'Updated Name',
    'interruption_sensitivity' => 0
]);

// ⚠️ Cannot update 'response_engine' on agents with version >= 1
```

### Publish Agent

**CRITICAL**: Agent must be published before it can receive calls!

```php
$agentId = 'agent_773a5034bd8a7b7fb98cd4ab0c';

$response = Http::withHeaders(['Authorization' => "Bearer $token"])
    ->post("https://api.retellai.com/publish-agent/$agentId");

if ($response->successful()) {
    echo "✅ Agent published!\n";

    // Verify
    $agent = Http::get("https://api.retellai.com/get-agent/$agentId")->json();
    echo "Published: " . ($agent['is_published'] ? 'Yes' : 'No') . "\n";
    echo "Version: {$agent['version']}\n";  // Increments on publish
}
```

### List Agents
```php
$response = Http::withHeaders(['Authorization' => "Bearer $token"])
    ->get('https://api.retellai.com/list-agents');

$agents = $response->json();
foreach ($agents as $agent) {
    $published = $agent['is_published'] ? '✅' : '❌';
    echo "{$published} {$agent['agent_id']} - {$agent['agent_name']}\n";
}
```

---

## 🧠 Retell LLM Management

### Create Retell LLM

```php
$response = Http::withHeaders([
    'Authorization' => "Bearer $token",
    'Content-Type' => 'application/json'
])->post('https://api.retellai.com/create-retell-llm', [
    'model' => 'gpt-4o-mini',
    'temperature' => 0.3,
    'general_prompt' => "Du bist Carola, die freundliche KI-Assistentin von Friseur 1.

SOBALD du Service, Datum und Uhrzeit hast → CALL check_availability_v17 mit:
{
  \"datum\": \"YYYY-MM-DD\",
  \"uhrzeit\": \"HH:MM\",
  \"dienstleistung\": \"service name\",
  \"bestaetigung\": false
}

Du MUSST diese Function aufrufen. RATE NIEMALS ob ein Termin frei ist.",

    'general_tools' => [
        [
            'type' => 'custom',
            'name' => 'check_availability_v17',
            'description' => 'Prüft Verfügbarkeit und bucht optional Termin',
            'url' => 'https://api.askproai.de/api/retell/check-availability',
            'speak_during_execution' => true,
            'speak_after_execution' => true,
            'execution_message_description' => 'Ich prüfe die Verfügbarkeit...',
            'parameters' => (object)[  // ✅ Cast to object for proper JSON
                'type' => 'object',
                'properties' => (object)[
                    'datum' => (object)[
                        'type' => 'string',
                        'description' => 'Datum im Format YYYY-MM-DD'
                    ],
                    'uhrzeit' => (object)[
                        'type' => 'string',
                        'description' => 'Uhrzeit im Format HH:MM (24h)'
                    ],
                    'dienstleistung' => (object)[
                        'type' => 'string',
                        'description' => 'Name der Dienstleistung'
                    ],
                    'bestaetigung' => (object)[
                        'type' => 'boolean',
                        'description' => 'false=nur prüfen, true=buchen'
                    ]
                ],
                'required' => ['datum', 'uhrzeit', 'dienstleistung', 'bestaetigung']
            ]
        ],
        // Add more tools...
    ]
]);

$llm = $response->json();
echo "LLM ID: {$llm['llm_id']}\n";
file_put_contents('retell_llm_id.txt', $llm['llm_id']);
```

**Key Details**:
- `(object)` cast required for PHP arrays to become JSON objects
- `speak_during_execution: true` for better UX
- Clear prompt with "SOBALD", "CALL", "MUSST" keywords

### Get Retell LLM
```php
$llmId = 'llm_36bd5fb31065787c13797e05a29a';

$response = Http::withHeaders(['Authorization' => "Bearer $token"])
    ->get("https://api.retellai.com/get-retell-llm/$llmId");

if ($response->status() === 404) {
    echo "❌ LLM doesn't exist!\n";
} else {
    $llm = $response->json();
    echo "Model: {$llm['model']}\n";
    echo "Tools: " . count($llm['general_tools']) . "\n";
}
```

### Update Retell LLM
```php
$llmId = 'llm_36bd5fb31065787c13797e05a29a';

$response = Http::withHeaders([
    'Authorization' => "Bearer $token",
    'Content-Type' => 'application/json'
])->patch("https://api.retellai.com/update-retell-llm/$llmId", [
    'temperature' => 0.5,
    'general_prompt' => 'Updated prompt...'
]);
```

### List Retell LLMs
```php
$response = Http::withHeaders(['Authorization' => "Bearer $token"])
    ->get('https://api.retellai.com/list-retell-llms');

$llms = $response->json();
foreach ($llms as $llm) {
    echo "{$llm['llm_id']} - {$llm['model']}\n";
}
```

---

## 🎤 Voice Management

### List Voices

**ALWAYS run this before creating an agent!**

```php
$response = Http::withHeaders(['Authorization' => "Bearer $token"])
    ->get('https://api.retellai.com/list-voices');

$voices = $response->json();

// Find German voices
foreach ($voices as $voice) {
    $id = $voice['voice_id'];
    $name = $voice['voice_name'] ?? 'N/A';

    if (strpos($id, '11labs') !== false && $voice['language'] === 'de-DE') {
        echo "✅ $id - $name\n";
    }
}
```

**Common voices**:
- ✅ `11labs-Carola` (German female)
- ❌ `11labs-Christopher` (doesn't exist - causes 404!)

---

## 📊 Call Management

### Get Call
```php
$callId = 'call_abc123';

$response = Http::withHeaders(['Authorization' => "Bearer $token"])
    ->get("https://api.retellai.com/get-call/$callId");

$call = $response->json();
echo "Agent: {$call['agent_id']}\n";
echo "Status: {$call['call_status']}\n";
echo "Duration: {$call['call_duration_ms']}ms\n";
```

### List Calls
```php
$response = Http::withHeaders(['Authorization' => "Bearer $token"])
    ->get('https://api.retellai.com/list-calls', [
        'limit' => 10,
        'sort_order' => 'descending'
    ]);

$calls = $response->json();
foreach ($calls as $call) {
    echo "{$call['call_id']} - {$call['call_status']}\n";
}
```

---

## 🔄 Conversation Flow Management

⚠️ **NOT RECOMMENDED**: Use LLM-based agents instead

### Create Conversation Flow
```php
$flowData = json_decode(file_get_contents('flow.json'), true);

$response = Http::withHeaders([
    'Authorization' => "Bearer $token",
    'Content-Type' => 'application/json'
])->post('https://api.retellai.com/create-conversation-flow', $flowData);

$flow = $response->json();
echo "Flow ID: {$flow['conversation_flow_id']}\n";
```

### Get Conversation Flow
```php
$flowId = 'flow_abc123';

$response = Http::withHeaders(['Authorization' => "Bearer $token"])
    ->get("https://api.retellai.com/get-conversation-flow/$flowId");

$flow = $response->json();
echo "Nodes: " . count($flow['nodes']) . "\n";
```

### Update Conversation Flow
```php
$flowId = 'flow_abc123';
$updatedFlow = json_decode(file_get_contents('updated_flow.json'), true);

$response = Http::withHeaders([
    'Authorization' => "Bearer $token",
    'Content-Type' => 'application/json'
])->patch("https://api.retellai.com/update-conversation-flow/$flowId", $updatedFlow);
```

---

## 🔧 Complete Workflow Example

### Creating & Deploying LLM-based Agent

```php
<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

$token = env('RETELL_TOKEN');

echo "Step 1: Get available voices...\n";
$voices = Http::withHeaders(['Authorization' => "Bearer $token"])
    ->get('https://api.retellai.com/list-voices')
    ->json();

$voiceId = null;
foreach ($voices as $voice) {
    if ($voice['voice_id'] === '11labs-Carola') {
        $voiceId = $voice['voice_id'];
        break;
    }
}

if (!$voiceId) {
    die("❌ Voice not found!\n");
}
echo "✅ Voice: $voiceId\n\n";

echo "Step 2: Create Retell LLM...\n";
$llmResp = Http::withHeaders([
    'Authorization' => "Bearer $token",
    'Content-Type' => 'application/json'
])->post('https://api.retellai.com/create-retell-llm', [
    'model' => 'gpt-4o-mini',
    'temperature' => 0.3,
    'general_prompt' => 'Your prompt here...',
    'general_tools' => [
        // Your tools here...
    ]
]);

if (!$llmResp->successful()) {
    die("❌ LLM creation failed: {$llmResp->body()}\n");
}

$llm = $llmResp->json();
echo "✅ LLM created: {$llm['llm_id']}\n\n";

echo "Step 3: Create Agent...\n";
$agentResp = Http::withHeaders([
    'Authorization' => "Bearer $token",
    'Content-Type' => 'application/json'
])->post('https://api.retellai.com/create-agent', [
    'agent_name' => 'My Agent',
    'response_engine' => [
        'type' => 'retell-llm',
        'llm_id' => $llm['llm_id']
    ],
    'voice_id' => $voiceId,
    'language' => 'de-DE',
    'webhook_url' => 'https://api.askproai.de/api/webhooks/retell'
]);

if (!$agentResp->successful()) {
    die("❌ Agent creation failed: {$agentResp->body()}\n");
}

$agent = $agentResp->json();
echo "✅ Agent created: {$agent['agent_id']}\n\n";

echo "Step 4: Publish Agent...\n";
$publishResp = Http::withHeaders(['Authorization' => "Bearer $token"])
    ->post("https://api.retellai.com/publish-agent/{$agent['agent_id']}");

if (!$publishResp->successful()) {
    die("❌ Publish failed: {$publishResp->body()}\n");
}
echo "✅ Agent published!\n\n";

echo "Step 5: Update Phone Number...\n";
$phone = '+493033081738';
$phoneResp = Http::withHeaders([
    'Authorization' => "Bearer $token",
    'Content-Type' => 'application/json'
])->patch("https://api.retellai.com/update-phone-number/$phone", [
    'inbound_agent_id' => $agent['agent_id']
]);

if (!$phoneResp->successful()) {
    die("❌ Phone update failed: {$phoneResp->body()}\n");
}
echo "✅ Phone updated!\n\n";

echo "═══════════════════════════════════════\n";
echo "🎉 DEPLOYMENT COMPLETE!\n";
echo "═══════════════════════════════════════\n";
echo "Agent ID: {$agent['agent_id']}\n";
echo "Phone: $phone\n";
echo "Ready for test calls!\n";
```

---

## ⚠️ Common Errors

### HTTP 404 on Create Agent
**Cause**: Invalid `voice_id` (90% of cases)
**Solution**: Always verify with `/list-voices` first

### Functions Not Called
**Cause**: Flow-based agent with prompt transitions
**Solution**: Use LLM-based agent instead

### Phone Not Updated
**Cause**: Using `agent_id` instead of `inbound_agent_id`
**Solution**: Use correct parameter name

### Agent Not Published
**Cause**: Forgot to call `/publish-agent`
**Solution**: Always publish before testing

---

## 📚 Related Documentation

- [RETELL_AGENT_CREATION_COMPLETE_GUIDE_2025.md](RETELL_AGENT_CREATION_COMPLETE_GUIDE_2025.md) - Full workflow
- [RETELL_TROUBLESHOOTING_GUIDE_2025.md](RETELL_TROUBLESHOOTING_GUIDE_2025.md) - Error solutions
- [00_MASTER_INDEX.md](00_MASTER_INDEX.md) - All documentation

---

**API Base URL**: `https://api.retellai.com`
**Authentication**: Bearer token in Authorization header
**Content-Type**: `application/json`
