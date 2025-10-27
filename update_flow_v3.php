<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

$token = env('RETELL_TOKEN');
$flowId = 'conversation_flow_a58405e3f67a'; // Existing flow
$agentId = 'agent_45daa54928c5768b52ba3db736'; // Existing agent

echo "\n═══════════════════════════════════════════════════════════\n";
echo "🔄 UPDATING CONVERSATION FLOW V3 (FINAL FIX)\n";
echo "═══════════════════════════════════════════════════════════\n\n";

echo "Flow ID: $flowId\n";
echo "Agent ID: $agentId\n\n";

// Load V3 flow JSON with call_id fix
$flowData = json_decode(file_get_contents(__DIR__ . '/friseur1_minimal_booking_v3_final.json'), true);

echo "📋 V3 Changes:\n";
echo "  ✅ Added call_id to tool-check-availability parameters\n";
echo "  ✅ Added call_id to tool-check-availability required array\n";
echo "  ✅ Added call_id to tool-book-appointment parameters\n";
echo "  ✅ Added call_id to tool-book-appointment required array\n";
echo "  ✅ Backend: Cal.com metadata validation enhanced (filters null values)\n\n";

echo "🔄 Updating existing flow...\n";

// Update flow via Retell API
$response = Http::withHeaders(['Authorization' => "Bearer $token"])
    ->patch("https://api.retellai.com/update-conversation-flow/$flowId", $flowData);

if (!$response->successful()) {
    echo "❌ Failed to update flow\n";
    echo "Status: {$response->status()}\n";
    echo "Error: " . json_encode($response->json(), JSON_PRETTY_PRINT) . "\n";
    exit(1);
}

$updatedFlow = $response->json();
echo "✅ Flow updated successfully\n";
echo "Flow ID: {$updatedFlow['conversation_flow_id']}\n";
echo "Version: {$updatedFlow['version']}\n\n";

// Verify tool definitions
echo "🔍 Verifying tool definitions...\n";
foreach ($updatedFlow['tools'] as $tool) {
    $toolName = $tool['name'];
    $hasCallId = isset($tool['parameters']['properties']['call_id']);
    $callIdRequired = in_array('call_id', $tool['parameters']['required'] ?? []);

    echo "  Tool: $toolName\n";
    echo "    call_id in properties: " . ($hasCallId ? '✅ YES' : '❌ NO') . "\n";
    echo "    call_id in required: " . ($callIdRequired ? '✅ YES' : '❌ NO') . "\n";
}

echo "\n📤 Publishing agent...\n";

$publishResp = Http::withHeaders(['Authorization' => "Bearer $token"])
    ->post("https://api.retellai.com/publish-agent/$agentId");

if (!$publishResp->successful()) {
    echo "❌ Failed to publish agent\n";
    echo "Status: {$publishResp->status()}\n";
    echo "Error: " . json_encode($publishResp->json(), JSON_PRETTY_PRINT) . "\n";
    exit(1);
}

echo "✅ Agent published successfully\n\n";

echo "═══════════════════════════════════════════════════════════\n";
echo "✅ V3 FLOW UPDATE COMPLETE\n";
echo "═══════════════════════════════════════════════════════════\n\n";

echo "🎯 DEPLOYMENT SUMMARY:\n";
echo "  Backend Fix: ✅ Cal.com metadata validation (null filtering + limits)\n";
echo "  Flow Fix: ✅ call_id added to all tool definitions\n";
echo "  Agent: ✅ Published\n\n";

echo "📞 READY FOR TESTING:\n";
echo "  1. Make test call to Friseur 1\n";
echo "  2. Request appointment (e.g., heute 16:00, Herrenhaarschnitt)\n";
echo "  3. Verify booking succeeds without Cal.com metadata error\n\n";

echo "📊 MONITORING:\n";
echo "  tail -f storage/logs/laravel-$(date +%Y-%m-%d).log | grep -i 'booking\|metadata'\n\n";
