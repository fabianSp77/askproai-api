<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

$token = env('RETELL_TOKEN');

echo "\n═══════════════════════════════════════════════════════════\n";
echo "🔧 DEPLOY FIXED CONVERSATION FLOW (Simple with parameter_mapping)\n";
echo "═══════════════════════════════════════════════════════════\n\n";

// Step 1: Upload Fixed Flow (mit parameter_mapping, OHNE extract nodes)
echo "Step 1: Creating Fixed Conversation Flow...\n";
$flowData = json_decode(file_get_contents(__DIR__ . '/friseur1_minimal_booking_v2_fixed.json'), true);

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
file_put_contents(__DIR__ . '/flow_v2_fixed_id.txt', $newFlowId);

// Step 2: Get voice ID
echo "Step 2: Getting voice ID...\n";
$voiceId = trim(file_get_contents(__DIR__ . '/working_voice_id.txt'));
echo "✅ Voice: $voiceId\n\n";

// Step 3: Create NEW Agent
echo "Step 3: Creating NEW Agent with fixed flow...\n";
$agentConfig = [
    'agent_name' => 'Friseur1 Fixed V2 (parameter_mapping)',
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
file_put_contents(__DIR__ . '/agent_v2_fixed_id.txt', $newAgentId);

// Step 4: Publish
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

// Step 5: Update Phone
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

// Final Summary
echo "═══════════════════════════════════════════════════════════\n";
echo "🎉 DEPLOYMENT COMPLETE!\n";
echo "═══════════════════════════════════════════════════════════\n\n";
echo "Flow ID: $newFlowId\n";
echo "Agent ID: $newAgentId\n";
echo "Phone: $phone\n\n";

echo "✅ FIX APPLIED:\n";
echo "- ✅ parameter_mapping added to both function nodes\n";
echo "- ✅ Using {{user_name}}, {{user_datum}}, {{user_uhrzeit}}, {{user_dienstleistung}}\n";
echo "- ✅ Backend should now receive all parameters\n\n";

echo "📞 Ready for test call!\n";
echo "Call: $phone\n\n";
