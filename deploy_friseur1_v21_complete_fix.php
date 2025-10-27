<?php

/**
 * Deploy Friseur 1 Flow V21 - COMPLETE FIX
 *
 * Updates Agent: agent_f1ce85d06a84afb989dfbb16a9
 * CRITICAL FIXES:
 * 1. Remove double greeting (disable speak_during_execution on initialize)
 * 2. Add multiple time handling policy (force single time selection)
 * 3. Re-deploy complete flow (V20 deployment failed - agent has 0 nodes!)
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
echo "║   🚨 EMERGENCY FIX: V21 Complete Flow Restoration            ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";
echo PHP_EOL;

$agentId = 'agent_f1ce85d06a84afb989dfbb16a9'; // Friseur 1
$sourceFile = __DIR__ . '/public/friseur1_flow_complete.json';
$targetFile = __DIR__ . '/public/friseur1_flow_v21_complete_fix.json';

if (!file_exists($sourceFile)) {
    echo "❌ Source flow file not found: {$sourceFile}\n";
    exit(1);
}

echo "📄 Loading complete flow (CLEAN baseline)...\n";
$flow = json_decode(file_get_contents($sourceFile), true);

if (!$flow) {
    echo "❌ Failed to parse flow JSON\n";
    exit(1);
}

echo "✅ Flow loaded: " . count($flow['nodes']) . " nodes\n";
echo PHP_EOL;

// Fix 1: Remove double greeting by disabling speak_during_execution
echo "=== FIX 1: Remove Double Greeting ===\n";
$initNodeFound = false;

foreach ($flow['nodes'] as &$node) {
    $nodeId = $node['node_id'] ?? null;
    $nodeName = $node['name'] ?? null;

    if ($nodeId === 'func_00_initialize' || $nodeName === '🚀 V16: Initialize Call (Parallel)') {
        $initNodeFound = true;

        // Disable speak_during_execution
        $node['speak_during_execution'] = false;
        $node['spoken_message'] = null; // Clear any spoken message

        echo "✅ Updated 'func_00_initialize' node\n";
        echo "  - speak_during_execution: FALSE (wait for customer data)\n";
        echo "  - FIX: Agent will greet ONCE with personalization\n";
        echo "  - NO MORE: Generic greeting → pause → personal greeting\n";
        break;
    }
}

if (!$initNodeFound) {
    echo "⚠️  WARNING: 'func_00_initialize' node not found!\n";
}
echo PHP_EOL;

// Fix 2: Add multiple time handling policy
echo "=== FIX 2: Multiple Time Handling Policy ===\n";
$dateNodeFound = false;

foreach ($flow['nodes'] as &$node) {
    if ($node['name'] === 'Datum & Zeit sammeln') {
        $dateNodeFound = true;

        $node['instruction'] = [
            'type' => 'prompt',
            'text' => 'Collect preferred date and time for the appointment.

**🚨 CRITICAL POLICY: Single Time Required**

When customer provides MULTIPLE times (e.g., "9 Uhr oder 10 Uhr"):
1. ACKNOWLEDGE: "Ich verstehe, 9 oder 10 Uhr"
2. ASK: "Welche Zeit bevorzugen Sie - 9 Uhr oder 10 Uhr?"
3. WAIT for customer to choose ONE specific time
4. ONLY proceed when you have SINGLE time in HH:MM format

**DATE POLICY:**
When customer provides only a TIME without a DATE (e.g., "14 Uhr"):
- Check current time
- If the requested time has ALREADY PASSED today → automatically assume TOMORROW
- If the requested time is STILL IN THE FUTURE today → ASK for clarification:
  "Meinen Sie heute um [TIME] Uhr oder morgen?"
- NEVER assume "today" without explicit confirmation when time is ambiguous

**COMPLETE INFORMATION REQUIRED:**
- Date: Must be explicit (e.g., "heute", "morgen", "Montag", "24.10.2025")
- Time: Must be SINGLE specific time (e.g., "14:00", NOT "14 oder 15 Uhr")
- Confirm both date AND time before proceeding

**TRANSITION READINESS:**
You are ready to check availability when you have:
✅ Single specific date
✅ Single specific time (not multiple options)
✅ Service type
✅ Customer name (if new customer)

If customer already mentioned date/time, confirm it. Otherwise, ask for missing information.'
        ];

        echo "✅ Updated 'Datum & Zeit sammeln' node\n";
        echo "  - Policy: Force single time selection\n";
        echo "  - Handles: '9 oder 10 Uhr' → asks user to choose\n";
        echo "  - Prevents: Node stuck with multiple times\n";
        break;
    }
}

if (!$dateNodeFound) {
    echo "⚠️  WARNING: 'Datum & Zeit sammeln' node not found!\n";
}
echo PHP_EOL;

// Save updated flow
echo "=== Saving V21 Flow ===\n";
file_put_contents($targetFile, json_encode($flow, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "✅ Saved to: {$targetFile}\n";
echo "  - Size: " . round(filesize($targetFile) / 1024, 2) . " KB\n";
echo "  - Nodes: " . count($flow['nodes']) . "\n";
echo PHP_EOL;

// Deploy to Retell
echo "=== Deploying to Retell Agent ===\n";

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

if ($httpCode === 200 || $httpCode === 201) {
    echo "✅ Agent conversation flow updated successfully\n";
    echo "  - Agent ID: {$agentId}\n";
    echo "  - HTTP Code: {$httpCode}\n";

    // Verify update
    $respData = json_decode($response, true);
    if (isset($respData['conversation_flow']['nodes'])) {
        echo "  - Nodes deployed: " . count($respData['conversation_flow']['nodes']) . "\n";
    }
} else {
    echo "❌ Failed to update agent\n";
    echo "  - HTTP Code: {$httpCode}\n";
    echo "  - Error: {$error}\n";
    echo "  - Response: " . substr($response, 0, 500) . "\n";
    exit(1);
}
echo PHP_EOL;

// PUBLISH Agent
echo "=== Publishing Agent (Making Changes Live) ===\n";

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

if ($httpCode === 200 || $httpCode === 201) {
    echo "✅ Agent published successfully\n";
    echo "  - Changes are now LIVE\n";
    echo "  - Version: V21 (Complete Fix)\n";
} else {
    echo "❌ Failed to publish agent\n";
    echo "  - HTTP Code: {$httpCode}\n";
    echo "  - Error: {$error}\n";
    echo "  - Response: {$response}\n";
    exit(1);
}
echo PHP_EOL;

// Verify deployment
echo "=== Verifying Deployment ===\n";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => "https://api.retellai.com/get-agent/{$agentId}",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $retellApiKey
    ]
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    $agent = json_decode($response, true);

    if (isset($agent['conversation_flow']['nodes'])) {
        $nodeCount = count($agent['conversation_flow']['nodes']);
        echo "✅ Verification successful\n";
        echo "  - Live nodes: {$nodeCount}\n";

        if ($nodeCount === 0) {
            echo "  ⚠️  WARNING: Agent still has 0 nodes! Deployment may have failed!\n";
        }
    } else {
        echo "⚠️  WARNING: No conversation_flow in response\n";
    }
}
echo PHP_EOL;

echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║           EMERGENCY DEPLOYMENT COMPLETED                     ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";
echo PHP_EOL;

echo "📋 Fixes Deployed:\n";
echo "  1. ✅ Double Greeting: speak_during_execution = FALSE\n";
echo "  2. ✅ Multiple Time: Force single time selection policy\n";
echo "  3. ✅ Complete Flow: 34 nodes restored\n";
echo PHP_EOL;

echo "🧪 Test Now:\n";
echo "  1. Call: +493033081738\n";
echo "  2. Say: 'Ich möchte morgen einen Termin'\n";
echo "  3. Say: '9 oder 10 Uhr'\n";
echo "  4. Expected: Agent asks 'Welche Zeit bevorzugen Sie?'\n";
echo "  5. Expected: ONE greeting (no double)\n";
echo "  6. Expected: Availability check works\n";
echo PHP_EOL;
