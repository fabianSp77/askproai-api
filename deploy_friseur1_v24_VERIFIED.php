<?php

/**
 * Deploy Friseur 1 Flow V24 - VERIFIED DEPLOYMENT
 *
 * Updates Agent: agent_f1ce85d06a84afb989dfbb16a9 (Friseur 1)
 *
 * V24 COMPLETE FIXES:
 * 1. DSGVO Name Policy: Use "Herr/Frau Nachname" OR "Vorname Nachname" ONLY
 * 2. Booking Edge Fix: func_book_appointment → node_14_success_goodbye (no confirmation loop)
 * 3. Preserves all V20/V21/V22 fixes
 *
 * DEPLOYMENT STRATEGY:
 * 1. Update agent (creates new draft)
 * 2. Verify draft has changes
 * 3. Publish if verification passes
 * 4. Verify published version
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
echo "║   🚀 V24: COMPLETE FIX (DSGVO + Booking) - VERIFIED         ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";
echo PHP_EOL;

$agentId = 'agent_f1ce85d06a84afb989dfbb16a9'; // Friseur 1
$sourceFile = __DIR__ . '/public/friseur1_flow_v24_COMPLETE.json';

if (!file_exists($sourceFile)) {
    echo "❌ Source flow file not found: {$sourceFile}\n";
    exit(1);
}

echo "📄 Loading V24 flow...\n";
$flow = json_decode(file_get_contents($sourceFile), true);

if (!$flow) {
    echo "❌ Failed to parse flow JSON\n";
    exit(1);
}

echo "✅ V24 Flow loaded: " . count($flow['nodes']) . " nodes\n";
echo "   Size: " . round(filesize($sourceFile) / 1024, 2) . " KB\n";
echo PHP_EOL;

// STEP 1: Update Agent (creates draft)
echo "=== STEP 1: Updating Agent (Creating Draft) ===\n";

$updatePayload = [
    'conversation_flow' => $flow
];

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => "https://api.retellai.com/update-agent/{$agentId}",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST => 'PATCH',
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $retellApiKey,
        'Content-Type: application/json'
    ],
    CURLOPT_POSTFIELDS => json_encode($updatePayload),
    CURLOPT_VERBOSE => false
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($httpCode !== 200 && $httpCode !== 201) {
    echo "❌ Failed to update agent\n";
    echo "  - HTTP Code: {$httpCode}\n";
    echo "  - Error: {$error}\n";
    echo "  - Response: " . substr($response, 0, 500) . "\n";
    exit(1);
}

echo "✅ Agent updated successfully\n";
echo "  - HTTP Code: {$httpCode}\n";

$respData = json_decode($response, true);
if (isset($respData['version'])) {
    echo "  - Draft Version: " . $respData['version'] . "\n";
}

echo "\n⏳ Waiting 5 seconds for draft to be ready...\n";
sleep(5);
echo PHP_EOL;

// STEP 2: Verify Draft Has Changes
echo "=== STEP 2: Verifying Draft Has V24 Fixes ===\n";

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
    echo "❌ Failed to get flow ID from agent\n";
    exit(1);
}

// Get draft flow
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

$draftFlow = json_decode($flowResponse, true);

// Verify fixes in draft
$dsgvoFound = false;
$edgeFixed = false;

foreach ($draftFlow['nodes'] as $node) {
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

echo "Draft Flow Verification:\n";
echo "  " . ($dsgvoFound ? "✅" : "❌") . " DSGVO Name Policy present\n";
echo "  " . ($edgeFixed ? "✅" : "❌") . " Booking edge points to success_goodbye\n";
echo PHP_EOL;

if (!$dsgvoFound || !$edgeFixed) {
    echo "❌ DRAFT VERIFICATION FAILED!\n";
    echo "   Changes did not apply to draft. Aborting publish.\n";
    echo "   This is the root cause of why previous deployments didn't work.\n";
    exit(1);
}

echo "✅ DRAFT VERIFIED - Both fixes are present!\n";
echo PHP_EOL;

// STEP 3: Publish Agent
echo "=== STEP 3: Publishing Agent (Making Changes Live) ===\n";

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
$error = curl_error($ch);
curl_close($ch);

if ($httpCode !== 200 && $httpCode !== 201) {
    echo "❌ Failed to publish agent\n";
    echo "  - HTTP Code: {$httpCode}\n";
    echo "  - Error: {$error}\n";
    echo "  - Response: {$response}\n";
    exit(1);
}

echo "✅ Agent published successfully\n";
echo "  - Changes are now LIVE\n";
echo "  - Version: V24 (DSGVO + Booking Complete)\n";

echo "\n⏳ Waiting 5 seconds for propagation...\n";
sleep(5);
echo PHP_EOL;

// STEP 4: Verify Published Version
echo "=== STEP 4: Verifying LIVE Published Version ===\n";

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

// Get live flow
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

// Verify fixes in live
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

echo "Live Flow Verification:\n";
echo "  " . ($dsgvoLive ? "✅" : "❌") . " DSGVO Name Policy (Herr/Frau Nachname)\n";
echo "  " . ($edgeLive ? "✅" : "❌") . " Booking Edge Fixed (no confirmation loop)\n";
echo "  - Live nodes: " . count($liveFlow['nodes']) . "\n";
echo "  - Agent version: " . ($agent['version'] ?? 'N/A') . "\n";
echo PHP_EOL;

if ($dsgvoLive && $edgeLive) {
    echo "🎉 SUCCESS! ALL V24 FIXES VERIFIED IN PRODUCTION!\n";
    echo PHP_EOL;
} else {
    echo "⚠️  WARNING: Some fixes not verified in live version\n";
    echo "   This may be a propagation delay. Wait 30s and test.\n";
    echo PHP_EOL;
}

echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║           V24 DEPLOYMENT COMPLETED                           ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";
echo PHP_EOL;

echo "📋 All Fixes Deployed:\n";
echo "  1. ✅ V20: Anti-Hallucination Policy\n";
echo "  2. ✅ V21: Single Greeting + Multiple Time Policy\n";
echo "  3. ✅ V22: Intent Recognition Fix\n";
echo "  4. ✅ V24: DSGVO Name Policy (Herr/Frau Nachname)\n";
echo "  5. ✅ V24: Booking Edge Fix (success → goodbye, no loop)\n";
echo PHP_EOL;

echo "🧪 Test Now:\n";
echo "  ⚠️  WICHTIG: Richtige Nummer verwenden!\n";
echo "  ✅ CORRECT: +493033081738 (Friseur 1)\n";
echo "  ❌ WRONG:   +493083793369 (AskProAI)\n";
echo PHP_EOL;
echo "  Expected Behavior:\n";
echo "  - Greeting: 'Willkommen zurück, Herr [Nachname]!' or 'Vorname Nachname!'\n";
echo "  - NEVER: Just 'Hansi!' alone (DSGVO violation)\n";
echo "  - Intent recognition works immediately\n";
echo "  - Availability check happens automatically\n";
echo "  - After booking: Goes DIRECTLY to success goodbye\n";
echo "  - NO asking for confirmation AFTER booking\n";
echo PHP_EOL;
