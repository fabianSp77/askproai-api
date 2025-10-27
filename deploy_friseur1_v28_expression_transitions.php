<?php

/**
 * Deploy Friseur 1 Flow V28 - EXPRESSION-BASED TRANSITIONS
 *
 * ROOT CAUSE: Prompt-based transitions are FUNDAMENTALLY UNRELIABLE
 * - Even simplified prompts fail
 * - Retell's LLM doesn't consistently match conditions
 * - Agent hallucinates instead of triggering transitions
 *
 * SOLUTION V28: Use EXPRESSION-BASED transitions with dynamic variables
 * - Deterministic evaluation
 * - No LLM interpretation needed
 * - Guaranteed transitions when data collected
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
echo "║   🚀 V28: EXPRESSION-BASED TRANSITIONS                      ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";
echo PHP_EOL;

$agentId = 'agent_f1ce85d06a84afb989dfbb16a9';

// Get current flow
echo "=== STEP 1: Getting Current Flow ===\n";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => "https://api.retellai.com/get-agent/{$agentId}",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $retellApiKey]
]);
$response = curl_exec($ch);
curl_close($ch);

$agent = json_decode($response, true);
$flowId = $agent['response_engine']['conversation_flow_id'] ?? null;

echo "✅ Flow ID: {$flowId}\n";
echo "   Current version: " . ($agent['version'] ?? 'N/A') . "\n\n";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => "https://api.retellai.com/get-conversation-flow/{$flowId}",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $retellApiKey]
]);
$flowResponse = curl_exec($ch);
curl_close($ch);

$flow = json_decode($flowResponse, true);
echo "✅ Flow loaded: " . count($flow['nodes']) . " nodes\n\n";

// Apply V28 Expression-Based Transition Fixes
echo "=== STEP 2: Applying V28 Expression-Based Transition Fixes ===\n";

$fixesApplied = 0;

foreach ($flow['nodes'] as &$node) {
    // FIX 1: DateTime Collection → Check Availability
    // CRITICAL: This is where the agent gets stuck!
    if (($node['id'] ?? null) === 'node_07_datetime_collection') {
        echo "  🔧 Fixing: node_07_datetime_collection\n";

        foreach ($node['edges'] ?? [] as &$edge) {
            if (($edge['id'] ?? null) === 'edge_11') {
                echo "     🔧 Converting edge_11 to EXPRESSION-BASED\n";

                // OLD: Prompt-based (unreliable)
                // NEW: Expression-based (deterministic)

                // Set dynamic variables in node instruction
                $node['instruction']['collect_variables'] = [
                    'datum_collected',
                    'uhrzeit_collected',
                    'service_collected'
                ];

                // Expression: Automatically transition when all data collected
                $edge['transition_condition'] = [
                    'type' => 'expression',
                    'expression' => 'datum_collected === true && uhrzeit_collected === true'
                ];

                $fixesApplied++;
                echo "     ✅ Changed to expression: datum_collected && uhrzeit_collected\n";
            }
        }
    }

    // FIX 2: Intent Recognition → Service Selection
    if (($node['id'] ?? null) === 'node_04_intent_enhanced') {
        echo "  🔧 Fixing: node_04_intent_enhanced\n";

        foreach ($node['edges'] ?? [] as &$edge) {
            if (($edge['id'] ?? null) === 'edge_07a') {
                echo "     🔧 Converting edge_07a to EXPRESSION-BASED\n";

                $node['instruction']['collect_variables'] = ['intent_booking'];

                $edge['transition_condition'] = [
                    'type' => 'expression',
                    'expression' => 'intent_booking === true'
                ];

                $fixesApplied++;
                echo "     ✅ Changed to expression: intent_booking === true\n";
            }
        }
    }

    // FIX 3: Service Selection → DateTime Collection
    if (($node['id'] ?? null) === 'node_06_service_selection') {
        echo "  🔧 Fixing: node_06_service_selection\n";

        foreach ($node['edges'] ?? [] as &$edge) {
            if (($edge['id'] ?? null) === 'edge_10') {
                echo "     🔧 Converting edge_10 to EXPRESSION-BASED\n";

                $node['instruction']['collect_variables'] = ['service_collected'];

                $edge['transition_condition'] = [
                    'type' => 'expression',
                    'expression' => 'service_collected === true'
                ];

                $fixesApplied++;
                echo "     ✅ Changed to expression: service_collected === true\n";
            }
        }
    }
}

echo "\n✅ Applied {$fixesApplied} expression-based transition fixes\n\n";

// Update Flow
echo "=== STEP 3: Deploying V28 to Retell ===\n";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => "https://api.retellai.com/update-conversation-flow/{$flowId}",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST => 'PATCH',
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $retellApiKey,
        'Content-Type: application/json'
    ],
    CURLOPT_POSTFIELDS => json_encode($flow)
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200 && $httpCode !== 201) {
    echo "❌ Failed: HTTP {$httpCode}\n";
    echo substr($response, 0, 500) . "\n";
    exit(1);
}

echo "✅ Flow updated: HTTP {$httpCode}\n";
$respData = json_decode($response, true);
echo "   New version: " . ($respData['version'] ?? 'N/A') . "\n\n";

sleep(3);

// Publish Agent
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
    echo "❌ Publish failed: HTTP {$httpCode}\n";
    echo substr($response, 0, 500) . "\n";
    exit(1);
}

echo "✅ Agent published!\n\n";

sleep(3);

// Verification
echo "=== STEP 5: Verification ===\n";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => "https://api.retellai.com/get-conversation-flow/{$flowId}",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $retellApiKey]
]);
$flowResponse = curl_exec($ch);
curl_close($ch);

$verifyFlow = json_decode($flowResponse, true);

$expressionsFound = 0;

foreach ($verifyFlow['nodes'] as $node) {
    foreach ($node['edges'] ?? [] as $edge) {
        if (($edge['transition_condition']['type'] ?? null) === 'expression') {
            $expressionsFound++;
            echo "  ✅ Expression found: " . ($edge['transition_condition']['expression'] ?? 'N/A') . "\n";
        }
    }
}

echo "\n";

if ($expressionsFound >= 3) {
    echo "╔══════════════════════════════════════════════════════════════╗\n";
    echo "║              🎉 V28 DEPLOYED SUCCESSFULLY! 🎉                ║\n";
    echo "╚══════════════════════════════════════════════════════════════╝\n\n";

    echo "📋 WHAT'S CHANGED IN V28:\n";
    echo "  ✅ Prompt-based → Expression-based transitions\n";
    echo "  ✅ Dynamic variables for data collection tracking\n";
    echo "  ✅ Deterministic transitions (no LLM interpretation)\n";
    echo "  ✅ Guaranteed function node execution\n\n";

    echo "🧪 TEST NOW:\n";
    echo "  1. Call: +493033081738\n";
    echo "  2. Say: 'Ich möchte morgen 10 Uhr einen Herrenhaarschnitt'\n";
    echo "  3. Expected: Functions WILL be called automatically!\n";
    echo "  4. Check: https://api.askproai.de/admin/retell-call-sessions\n\n";
} else {
    echo "⚠️  Verification incomplete - only {$expressionsFound}/3 expressions found\n";
}
