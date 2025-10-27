<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

$token = env('RETELL_TOKEN');

echo "\n═══════════════════════════════════════════════════════════\n";
echo "🚀 CREATE MINIMAL CONVERSATION FLOW AGENT\n";
echo "═══════════════════════════════════════════════════════════\n\n";

// Step 1: Upload Conversation Flow
echo "Step 1: Creating Conversation Flow...\n";
$flowData = json_decode(file_get_contents(__DIR__ . '/friseur1_minimal_booking_v1.json'), true);

$flowResp = Http::withHeaders([
    'Authorization' => "Bearer $token",
    'Content-Type' => 'application/json'
])->post('https://api.retellai.com/create-conversation-flow', $flowData);

if (!$flowResp->successful()) {
    echo "❌ Flow creation failed!\n";
    echo "Status: {$flowResp->status()}\n";
    echo "Body: {$flowResp->body()}\n";
    exit(1);
}

$flow = $flowResp->json();
$flowId = $flow['conversation_flow_id'];
echo "✅ Flow created: $flowId\n\n";
file_put_contents(__DIR__ . '/minimal_flow_id.txt', $flowId);

// Step 2: Get verified voice
echo "Step 2: Getting voice...\n";
$voiceId = trim(file_get_contents(__DIR__ . '/working_voice_id.txt'));
echo "✅ Voice: $voiceId\n\n";

// Step 3: Create Agent with Conversation Flow
echo "Step 3: Creating Agent with Conversation Flow...\n";
$agentConfig = [
    'agent_name' => 'Friseur1 Minimal Flow V1',
    'response_engine' => [
        'type' => 'conversation-flow',  // NOT retell-llm!
        'conversation_flow_id' => $flowId
    ],
    'voice_id' => $voiceId,
    'language' => 'de-DE',
    'enable_backchannel' => true,
    'responsiveness' => 1.0,
    'interruption_sensitivity' => 1,
    'reminder_trigger_ms' => 10000,
    'reminder_max_count' => 2,
    'max_call_duration_ms' => 1800000,
    'end_call_after_silence_ms' => 60000,
    'webhook_url' => 'https://api.askproai.de/api/webhooks/retell'
];

$agentResp = Http::withHeaders([
    'Authorization' => "Bearer $token",
    'Content-Type' => 'application/json'
])->post('https://api.retellai.com/create-agent', $agentConfig);

if (!$agentResp->successful()) {
    echo "❌ Agent creation failed!\n";
    echo "Status: {$agentResp->status()}\n";
    echo "Body: {$agentResp->body()}\n";
    exit(1);
}

$agent = $agentResp->json();
$agentId = $agent['agent_id'];
echo "✅ Agent created: $agentId\n\n";
file_put_contents(__DIR__ . '/minimal_agent_id.txt', $agentId);

// Step 4: Publish Agent
echo "Step 4: Publishing Agent...\n";
$publishResp = Http::withHeaders(['Authorization' => "Bearer $token"])
    ->post("https://api.retellai.com/publish-agent/$agentId");

if (!$publishResp->successful()) {
    echo "❌ Publish failed!\n";
    echo "Status: {$publishResp->status()}\n";
    echo "Body: {$publishResp->body()}\n";
    exit(1);
}
echo "✅ Agent published!\n\n";

// Step 5: Verify
echo "Step 5: Verifying Agent...\n";
$verifyResp = Http::withHeaders(['Authorization' => "Bearer $token"])
    ->get("https://api.retellai.com/get-agent/$agentId");

$verifiedAgent = $verifyResp->json();
echo "Agent Name: {$verifiedAgent['agent_name']}\n";
echo "Type: {$verifiedAgent['response_engine']['type']}\n";
echo "Flow ID: {$verifiedAgent['response_engine']['conversation_flow_id']}\n";
echo "Published: " . ($verifiedAgent['is_published'] ? 'Yes' : 'No') . "\n";
echo "Version: {$verifiedAgent['version']}\n\n";

// Step 6: Update Phone Number
echo "Step 6: Updating Phone Number...\n";
$phone = '+493033081738';
$phoneResp = Http::withHeaders([
    'Authorization' => "Bearer $token",
    'Content-Type' => 'application/json'
])->patch("https://api.retellai.com/update-phone-number/$phone", [
    'inbound_agent_id' => $agentId
]);

if (!$phoneResp->successful()) {
    echo "❌ Phone update failed!\n";
    echo "Status: {$phoneResp->status()}\n";
    echo "Body: {$phoneResp->body()}\n";
    exit(1);
}
echo "✅ Phone updated!\n\n";

// Step 7: Final Summary
echo "═══════════════════════════════════════════════════════════\n";
echo "🎉 DEPLOYMENT COMPLETE!\n";
echo "═══════════════════════════════════════════════════════════\n\n";
echo "Flow ID: $flowId\n";
echo "Agent ID: $agentId\n";
echo "Phone: $phone\n";
echo "Type: Conversation Flow (NOT LLM-based)\n";
echo "Nodes: 7 (minimal booking flow)\n\n";
echo "Ready for test call!\n";
echo "Call: $phone\n\n";
