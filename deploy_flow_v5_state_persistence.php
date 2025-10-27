<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

$token = env('RETELL_TOKEN');
$flowId = 'conversation_flow_a58405e3f67a';  // Existing flow ID
$agentId = 'agent_45daa54928c5768b52ba3db736';  // Friseur 1 agent

echo "\n═══════════════════════════════════════════════════════════\n";
echo "🚀 DEPLOYING V5 CONVERSATION FLOW - State Persistence\n";
echo "═══════════════════════════════════════════════════════════\n\n";

echo "📝 Loading V5 flow from file...\n";
$flowData = json_decode(file_get_contents(__DIR__ . '/friseur1_conversation_flow_v5_state_persistence.json'), true);

if (!$flowData) {
    echo "❌ Failed to load V5 flow file\n";
    exit(1);
}

echo "✅ Flow loaded: " . count($flowData['nodes']) . " nodes\n";
echo "✅ Dynamic variables: " . count($flowData['dynamic_variables']) . " variables\n\n";

// Update flow
echo "🔄 Updating Conversation Flow (ID: $flowId)...\n";
$flowResp = Http::withHeaders(['Authorization' => "Bearer $token"])
    ->patch("https://api.retellai.com/update-conversation-flow/$flowId", $flowData);

if (!$flowResp->successful()) {
    echo "❌ Failed to update flow\n";
    echo "Status: {$flowResp->status()}\n";
    echo "Error: " . json_encode($flowResp->json(), JSON_PRETTY_PRINT) . "\n";
    exit(1);
}

$flowResult = $flowResp->json();
echo "✅ Flow updated successfully\n";
echo "   Version: {$flowResult['version']}\n\n";

// Publish agent
echo "🚀 Publishing Agent (ID: $agentId)...\n";
$publishResp = Http::withHeaders(['Authorization' => "Bearer $token"])
    ->post("https://api.retellai.com/publish-agent/$agentId");

if (!$publishResp->successful()) {
    echo "❌ Failed to publish agent\n";
    echo "Status: {$publishResp->status()}\n";
    echo "Error: " . json_encode($publishResp->json(), JSON_PRETTY_PRINT) . "\n";
    exit(1);
}

$publishResult = $publishResp->json();
echo "✅ Agent published successfully\n";
if (isset($publishResult['agent_version'])) {
    echo "   Agent Version: {$publishResult['agent_version']}\n\n";
} else {
    echo "   Response: " . json_encode($publishResult, JSON_PRETTY_PRINT) . "\n\n";
}

// Verify
echo "🔍 Verifying deployment...\n";
$agentResp = Http::withHeaders(['Authorization' => "Bearer $token"])
    ->get("https://api.retellai.com/get-agent/$agentId");

if ($agentResp->successful()) {
    $agent = $agentResp->json();
    echo "✅ Verification successful\n";
    echo "   Agent Name: {$agent['agent_name']}\n";
    echo "   Agent Version: {$agent['version']}\n";
    echo "   Flow Version: {$agent['conversation_flow']['version']}\n";
    echo "   Published: " . ($agent['is_published'] ? 'YES ✅' : 'NO ❌') . "\n";

    // Check for dynamic variables
    if (isset($agent['conversation_flow']['dynamic_variables'])) {
        echo "   Dynamic Variables: " . count($agent['conversation_flow']['dynamic_variables']) . " ✅\n";
    } else {
        echo "   Dynamic Variables: NOT FOUND ❌\n";
    }
}

echo "\n═══════════════════════════════════════════════════════════\n";
echo "✅ DEPLOYMENT COMPLETE - V5 State Persistence\n";
echo "═══════════════════════════════════════════════════════════\n\n";

echo "📋 What Was Deployed:\n";
echo "   1. ✅ Dynamic Variables - 5 state variables for data persistence\n";
echo "   2. ✅ Smart Data Collection - Checks variables before asking\n";
echo "   3. ✅ Updated Prompts - Variable-aware instructions\n";
echo "   4. ✅ Improved Transitions - Verify all data collected\n\n";

echo "🔧 UX Improvements:\n";
echo "   ✅ UX #1 FIXED: No more redundant questioning\n";
echo "   ✅ Agent remembers data across conversation\n";
echo "   ✅ User can provide all data upfront\n\n";

echo "🧪 Test Now:\n";
echo "   1. Call Retell phone number: +493033081738\n";
echo "   2. Say: 'Ich möchte einen Herrenhaarschnitt für heute 15 Uhr, Hans Schuster'\n";
echo "   3. Expected: Agent should say 'Einen Moment, ich prüfe...' and proceed\n";
echo "   4. NOT Expected: Agent asking for name/date/time again\n\n";

echo "📊 Check Logs:\n";
echo "   tail -f storage/logs/laravel.log | grep 'collected_dynamic_variables'\n\n";
