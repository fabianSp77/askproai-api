<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

$token = env('RETELL_TOKEN');
$flowId = 'conversation_flow_a58405e3f67a';  // Existing V4 flow
$agentId = 'agent_45daa54928c5768b52ba3db736';  // Friseur 1 agent

echo "\n═══════════════════════════════════════════════════════════\n";
echo "🚀 DEPLOYING V4 CONVERSATION FLOW - UX FIXES\n";
echo "═══════════════════════════════════════════════════════════\n\n";

echo "📝 Loading flow from file...\n";
$flowData = json_decode(file_get_contents(__DIR__ . '/friseur1_conversation_flow_v4_complete.json'), true);

if (!$flowData) {
    echo "❌ Failed to load flow file\n";
    exit(1);
}

echo "✅ Flow loaded: " . count($flowData['nodes']) . " nodes, " . count($flowData['tools']) . " tools\n\n";

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
}

echo "\n═══════════════════════════════════════════════════════════\n";
echo "✅ DEPLOYMENT COMPLETE - V4 UX FIXES\n";
echo "═══════════════════════════════════════════════════════════\n\n";

echo "📋 Changes Deployed:\n";
echo "   1. ✅ 'heute' parsing - Agent accepts natural date expressions\n";
echo "   2. ✅ Reduced redundancy - Agent checks existing data before asking\n\n";

echo "🧪 Test Now:\n";
echo "   1. Call Retell phone number\n";
echo "   2. Say: 'Ich möchte einen Herrenhaarschnitt für heute 15 Uhr'\n";
echo "   3. Agent should ONLY ask for your name (not date/time/service again)\n";
echo "   4. Agent should understand 'heute' without asking for DD.MM.YYYY\n\n";
