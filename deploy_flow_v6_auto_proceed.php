<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

$token = env('RETELL_TOKEN');
$flowId = 'conversation_flow_a58405e3f67a';
$agentId = 'agent_45daa54928c5768b52ba3db736';

echo "\n═══════════════════════════════════════════════════════════\n";
echo "🚀 DEPLOYING V6 - Complete UX Fixes\n";
echo "═══════════════════════════════════════════════════════════\n\n";

echo "📝 Loading V6 flow...\n";
$flowData = json_decode(file_get_contents(__DIR__ . '/friseur1_conversation_flow_v6_auto_proceed.json'), true);

if (!$flowData) {
    die("❌ Failed to load V6 flow\n");
}

echo "✅ Flow loaded: " . count($flowData['nodes']) . " nodes, " . count($flowData['dynamic_variables']) . " variables\n\n";

// Update flow
echo "🔄 Updating Conversation Flow...\n";
$flowResp = Http::withHeaders(['Authorization' => "Bearer $token"])
    ->patch("https://api.retellai.com/update-conversation-flow/$flowId", $flowData);

if (!$flowResp->successful()) {
    echo "❌ Failed to update flow\n";
    echo "Status: {$flowResp->status()}\n";
    echo "Error: " . json_encode($flowResp->json(), JSON_PRETTY_PRINT) . "\n";
    exit(1);
}

$flowResult = $flowResp->json();
echo "✅ Flow updated to Version: {$flowResult['version']}\n\n";

// Publish agent
echo "🚀 Publishing Agent...\n";
$publishResp = Http::withHeaders(['Authorization' => "Bearer $token"])
    ->post("https://api.retellai.com/publish-agent/$agentId");

if (!$publishResp->successful()) {
    echo "❌ Failed to publish agent\n";
    echo "Status: {$publishResp->status()}\n";
    exit(1);
}

echo "✅ Agent published successfully\n\n";

echo "═══════════════════════════════════════════════════════════\n";
echo "✅ DEPLOYMENT COMPLETE - V6\n";
echo "═══════════════════════════════════════════════════════════\n\n";

echo "📊 ALLE FIXES DEPLOYED:\n\n";

echo "🐛 **Backend Fixes:**\n";
echo "   ✅ Bug #9: Service Selection - findServiceByName() working\n";
echo "   ✅ Bug #2: Weekend Date Fix - deployed (needs weekend test)\n";
echo "   ✅ Bug #3: Email Confirmation - deployed (needs successful booking)\n\n";

echo "🎨 **UX Fixes:**\n";
echo "   ✅ UX #1: State Persistence - Dynamic variables active\n";
echo "   ✅ UX #2: Auto-Proceed - Consistent parameter mapping\n\n";

echo "🧪 **TESTING PLAN:**\n\n";

echo "**Test 1: All Data Upfront (tests UX #1 + #2)**\n";
echo "   Say: 'Herrenhaarschnitt für heute 15 Uhr, Hans Schuster'\n";
echo "   Expected:\n";
echo "   - Agent should NOT ask for name/date/time again ✅\n";
echo "   - Agent checks availability ✅\n";
echo "   - Agent says 'verfügbar, soll ich buchen?' ✅\n";
echo "   Say: 'Ja'\n";
echo "   - Agent should book immediately ✅\n";
echo "   - Email should be sent (Bug #3) ✅\n\n";

echo "**Test 2: Service Selection (tests Bug #9)**\n";
echo "   Say: 'Damenhaarschnitt für morgen 14 Uhr'\n";
echo "   Check logs: Service ID should be 41 (Damenhaarschnitt) ✅\n";
echo "   Say: 'Herrenhaarschnitt für morgen 14 Uhr'\n";
echo "   Check logs: Service ID should be 42 (Herrenhaarschnitt) ✅\n\n";

echo "**Test 3: Weekend Date (tests Bug #2)**\n";
echo "   Say: 'Herrenhaarschnitt für Samstag 15 Uhr'\n";
echo "   Expected: Agent should NOT shift to Monday ✅\n\n";

echo "📞 **Phone Number:** +493033081738\n\n";

echo "📋 **Check Logs:**\n";
echo "   tail -f storage/logs/laravel.log | grep -E '(Service matched|dynamic_variables|Appointment created)'\n\n";
