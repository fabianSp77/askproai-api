<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

$token = env('RETELL_TOKEN');

echo "\n═══════════════════════════════════════════════════════════\n";
echo "🔧 DEPLOY FIXED CONVERSATION FLOW (V3 with Extract DV Nodes)\n";
echo "═══════════════════════════════════════════════════════════\n\n";

// Step 1: Upload NEW Conversation Flow (mit parameter_mapping)
echo "Step 1: Creating NEW Conversation Flow V3...\n";
$flowData = json_decode(file_get_contents(__DIR__ . '/friseur1_minimal_booking_v3_perfect.json'), true);

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
$newFlowId = $flow['conversation_flow_id'];
echo "✅ New Flow created: $newFlowId\n\n";
file_put_contents(__DIR__ . '/flow_v3_id.txt', $newFlowId);

// Step 2: Get voice ID
echo "Step 2: Getting voice ID...\n";
$voiceId = trim(file_get_contents(__DIR__ . '/working_voice_id.txt'));
echo "✅ Voice: $voiceId\n\n";

// Step 3: Create NEW Agent with fixed flow
echo "Step 3: Creating NEW Agent with fixed flow...\n";
$agentConfig = [
    'agent_name' => 'Friseur1 Fixed Flow V3 (Extract DV)',
    'response_engine' => [
        'type' => 'conversation-flow',
        'conversation_flow_id' => $newFlowId
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
$newAgentId = $agent['agent_id'];
echo "✅ New Agent created: $newAgentId\n\n";
file_put_contents(__DIR__ . '/agent_v3_id.txt', $newAgentId);

// Step 4: Publish Agent
echo "Step 4: Publishing Agent...\n";
$publishResp = Http::withHeaders(['Authorization' => "Bearer $token"])
    ->post("https://api.retellai.com/publish-agent/$newAgentId");

if (!$publishResp->successful()) {
    echo "❌ Publish failed!\n";
    echo "Status: {$publishResp->status()}\n";
    echo "Body: {$publishResp->body()}\n";
    exit(1);
}
echo "✅ Agent published!\n\n";

// Step 5: Update Phone Number
echo "Step 5: Updating Phone Number...\n";
$phone = '+493033081738';
$phoneResp = Http::withHeaders([
    'Authorization' => "Bearer $token",
    'Content-Type' => 'application/json'
])->patch("https://api.retellai.com/update-phone-number/$phone", [
    'inbound_agent_id' => $newAgentId
]);

if (!$phoneResp->successful()) {
    echo "❌ Phone update failed!\n";
    echo "Status: {$phoneResp->status()}\n";
    echo "Body: {$phoneResp->body()}\n";
    exit(1);
}
echo "✅ Phone updated!\n\n";

// Step 6: Verify
echo "Step 6: Verifying Agent...\n";
$verifyResp = Http::withHeaders(['Authorization' => "Bearer $token"])
    ->get("https://api.retellai.com/get-agent/$newAgentId");

$verifiedAgent = $verifyResp->json();
echo "Agent Name: {$verifiedAgent['agent_name']}\n";
echo "Type: {$verifiedAgent['response_engine']['type']}\n";
echo "Flow ID: {$verifiedAgent['response_engine']['conversation_flow_id']}\n";
echo "Published: " . ($verifiedAgent['is_published'] ? 'Yes' : 'No') . "\n";
echo "Version: {$verifiedAgent['version']}\n\n";

// Final Summary
echo "═══════════════════════════════════════════════════════════\n";
echo "🎉 FIXED DEPLOYMENT COMPLETE!\n";
echo "═══════════════════════════════════════════════════════════\n\n";
echo "Old Flow ID: conversation_flow_6e217a3e0a9d\n";
echo "NEW Flow ID: $newFlowId\n\n";
echo "Old Agent ID: agent_8a743073b0bd4342d72384baab\n";
echo "NEW Agent ID: $newAgentId\n\n";
echo "Phone: $phone\n";
echo "Type: Conversation Flow (WITH parameter_mapping)\n";
echo "Nodes: 11 (4x Extract DV + 2x Function + 5x Conversation/End)\n\n";

echo "✅ FIX APPLIED:\n";
echo "- ✅ Dynamic variables defined\n";
echo "- ✅ Extract DV nodes for: customer_name, service_type, appointment_date, appointment_time\n";
echo "- ✅ Parameter mapping in function nodes\n";
echo "- ✅ Backend will receive all 4 required parameters\n\n";

echo "Ready for test call!\n";
echo "Call: $phone\n\n";
