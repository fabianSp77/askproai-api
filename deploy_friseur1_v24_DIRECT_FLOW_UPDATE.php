<?php

/**
 * Deploy Friseur 1 Flow V24 - DIRECT FLOW UPDATE
 *
 * ROOT CAUSE FOUND:
 * - Updating agent with conversation_flow payload doesn't actually update the flow
 * - API returns success but changes don't apply
 *
 * NEW APPROACH:
 * - Update conversation flow DIRECTLY via PATCH /update-conversation-flow/{flowId}
 * - Then publish the agent
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$retellApiKey = config('services.retellai.api_key');

if (!$retellApiKey) {
    echo "❌ RETELLAI_API_KEY not found in environment\n";
    exit(1);
}

echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║   🚀 V24: DIRECT FLOW UPDATE (FIX ROOT CAUSE)              ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";
echo PHP_EOL;

$agentId = 'agent_f1ce85d06a84afb989dfbb16a9';
$sourceFile = __DIR__ . '/public/friseur1_flow_v24_COMPLETE.json';

echo "📄 Loading V24 flow...\n";
$flow = json_decode(file_get_contents($sourceFile), true);

echo "✅ V24 Flow loaded: " . count($flow['nodes']) . " nodes\n";
echo PHP_EOL;

// STEP 1: Get current flow ID from agent
echo "=== STEP 1: Getting Flow ID from Agent ===\n";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => "https://api.retellai.com/get-agent/{$agentId}",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $retellApiKey
    ]
]);

$response = curl_exec($ch);
curl_close($ch);

$agent = json_decode($response, true);
$flowId = $agent['response_engine']['conversation_flow_id'] ?? null;

if (!$flowId) {
    echo "❌ Failed to get flow ID\n";
    exit(1);
}

echo "✅ Flow ID: {$flowId}\n";
echo "   Agent version: " . ($agent['version'] ?? 'N/A') . "\n";
echo PHP_EOL;

// STEP 2: Update conversation flow DIRECTLY
echo "=== STEP 2: Updating Conversation Flow Directly ===\n";

$updatePayload = $flow; // Send the entire flow as the payload

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => "https://api.retellai.com/update-conversation-flow/{$flowId}",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST => 'PATCH',
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $retellApiKey,
        'Content-Type: application/json'
    ],
    CURLOPT_POSTFIELDS => json_encode($updatePayload)
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($httpCode !== 200 && $httpCode !== 201) {
    echo "❌ Failed to update conversation flow\n";
    echo "  - HTTP Code: {$httpCode}\n";
    echo "  - Error: {$error}\n";
    echo "  - Response: " . substr($response, 0, 500) . "\n";
    exit(1);
}

echo "✅ Conversation flow updated successfully\n";
echo "  - HTTP Code: {$httpCode}\n";

$respData = json_decode($response, true);
if (isset($respData['version'])) {
    echo "  - Flow Version: " . $respData['version'] . "\n";
}

echo "\n⏳ Waiting 5 seconds for update to propagate...\n";
sleep(5);
echo PHP_EOL;

// STEP 3: Verify flow has changes
echo "=== STEP 3: Verifying Flow Has V24 Fixes ===\n";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => "https://api.retellai.com/get-conversation-flow/{$flowId}",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $retellApiKey
    ]
]);

$flowResponse = curl_exec($ch);
curl_close($ch);

$updatedFlow = json_decode($flowResponse, true);

$dsgvoFound = false;
$edgeFixed = false;

foreach ($updatedFlow['nodes'] as $node) {
    if (($node['id'] ?? null) === 'func_00_initialize') {
        $text = $node['instruction']['text'] ?? '';
        $dsgvoFound = strpos($text, 'DSGVO NAME POLICY') !== false;
    }

    if (($node['id'] ?? null) === 'func_book_appointment') {
        foreach ($node['edges'] ?? [] as $edge) {
            if (($edge['id'] ?? null) === 'edge_booking_success') {
                $edgeFixed = ($edge['destination_node_id'] ?? null) === 'node_14_success_goodbye';
            }
        }
    }
}

echo "Flow Verification:\n";
echo "  " . ($dsgvoFound ? "✅" : "❌") . " DSGVO Name Policy present\n";
echo "  " . ($edgeFixed ? "✅" : "❌") . " Booking edge points to success_goodbye\n";
echo PHP_EOL;

if (!$dsgvoFound || !$edgeFixed) {
    echo "❌ FLOW VERIFICATION FAILED!\n";
    echo "   Changes did not apply. Aborting.\n";
    exit(1);
}

echo "✅ FLOW VERIFIED - Both fixes are present!\n";
echo PHP_EOL;

// STEP 4: Publish Agent
echo "=== STEP 4: Publishing Agent ===\n";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => "https://api.retellai.com/publish-agent/{$agentId}",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $retellApiKey,
        'Content-Type: application/json'
    ]
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200 && $httpCode !== 201) {
    echo "❌ Failed to publish agent\n";
    echo "  - HTTP Code: {$httpCode}\n";
    echo "  - Response: {$response}\n";
    exit(1);
}

echo "✅ Agent published successfully\n";
echo "  - Changes are now LIVE\n";

echo "\n⏳ Waiting 5 seconds for propagation...\n";
sleep(5);
echo PHP_EOL;

// STEP 5: Final verification
echo "=== STEP 5: Final LIVE Verification ===\n";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => "https://api.retellai.com/get-agent/{$agentId}",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $retellApiKey
    ]
]);

$response = curl_exec($ch);
curl_close($ch);

$agent = json_decode($response, true);

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => "https://api.retellai.com/get-conversation-flow/{$flowId}",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $retellApiKey
    ]
]);

$flowResponse = curl_exec($ch);
curl_close($ch);

$liveFlow = json_decode($flowResponse, true);

$dsgvoLive = false;
$edgeLive = false;

foreach ($liveFlow['nodes'] as $node) {
    if (($node['id'] ?? null) === 'func_00_initialize') {
        $text = $node['instruction']['text'] ?? '';
        $dsgvoLive = strpos($text, 'DSGVO NAME POLICY') !== false;
    }

    if (($node['id'] ?? null) === 'func_book_appointment') {
        foreach ($node['edges'] ?? [] as $edge) {
            if (($edge['id'] ?? null) === 'edge_booking_success') {
                $edgeLive = ($edge['destination_node_id'] ?? null) === 'node_14_success_goodbye';
            }
        }
    }
}

echo "LIVE Verification:\n";
echo "  " . ($dsgvoLive ? "✅" : "❌") . " DSGVO Name Policy\n";
echo "  " . ($edgeLive ? "✅" : "❌") . " Booking Edge Fixed\n";
echo "  - Agent version: " . ($agent['version'] ?? 'N/A') . "\n";
echo "  - Nodes: " . count($liveFlow['nodes']) . "\n";
echo PHP_EOL;

if ($dsgvoLive && $edgeLive) {
    echo "🎉 SUCCESS! V24 VERIFIED LIVE IN PRODUCTION!\n";
} else {
    echo "⚠️  WARNING: Verification incomplete\n";
}

echo PHP_EOL;
echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║           V24 DEPLOYMENT COMPLETED                           ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";
echo PHP_EOL;

echo "🧪 CRITICAL: Use the CORRECT phone number!\n";
echo "  ✅ FRISEUR 1: +493033081738\n";
echo "  ❌ NOT THIS:  +493083793369 (AskProAI)\n";
echo PHP_EOL;
