<?php

/**
 * Deploy Friseur 1 Flow V33 - CORRECT RETELL ARCHITECTURE
 *
 * ROOT CAUSE FINAL: I was using Function Nodes INCORRECTLY!
 *
 * According to Retell documentation:
 * - Function nodes are "not intended for having a conversation"
 * - speak_during_execution is for simple status: "Let me check that"
 * - NOT for complex data collection, greeting, intent recognition!
 *
 * CORRECT ARCHITECTURE:
 * 1. Conversation Node (BEFORE) - Greeting, Intent, Data Collection
 * 2. Function Node - Simple tool execution only
 * 3. Conversation Node (AFTER) - Result announcement
 *
 * This version implements the CORRECT Retell pattern.
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
echo "║   🚀 V33: CORRECT RETELL ARCHITECTURE                       ║\n";
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

// Apply V33 CORRECT ARCHITECTURE Fix
echo "=== STEP 2: Applying V33 CORRECT ARCHITECTURE ===\n";

$newNodes = [];

foreach ($flow['nodes'] as $node) {
    // FIX 1: Initialize → Greeting Conversation Node
    if (($node['id'] ?? null) === 'func_00_initialize') {
        echo "  🔧 Fixing: func_00_initialize\n";
        echo "     Reverting to: initialize → conversation node (CORRECT!)\n";

        $newEdges = [];
        foreach ($node['edges'] ?? [] as $edge) {
            if (($edge['id'] ?? null) === 'edge_00') {
                // Go to conversation node first (Retell best practice!)
                $edge['destination_node_id'] = 'node_02_customer_routing';
                $edge['transition_condition'] = [
                    'type' => 'prompt',
                    'prompt' => 'Initialization complete'
                ];
            }
            $newEdges[] = $edge;
        }
        $node['edges'] = $newEdges;
    }

    // FIX 2: func_check_availability - SIMPLE instruction (Retell best practice!)
    if (($node['id'] ?? null) === 'func_check_availability') {
        echo "  🔧 Fixing: func_check_availability\n";
        echo "     Removing: Greeting, Intent, Data Collection logic\n";
        echo "     Setting: Simple function execution only\n";

        $node['speak_during_execution'] = true;
        $node['wait_for_result'] = true;

        // CORRECT instruction per Retell docs
        $node['instruction'] = [
            'type' => 'prompt',
            'text' =>
                "Check appointment availability using the collected customer data.\n\n" .
                "While checking, say: 'Einen Moment bitte, ich prüfe die Verfügbarkeit...'\n\n" .
                "The check_availability_v17 function will be called automatically with the parameters:\n" .
                "- name\n" .
                "- datum (DD.MM.YYYY)\n" .
                "- uhrzeit (HH:MM)\n" .
                "- dienstleistung\n" .
                "- bestaetigung: false\n\n" .
                "Wait for the result before transitioning to the next node."
        ];

        echo "     ✅ Simplified instruction (Retell compliant)\n";
    }

    // FIX 3: Make Intent node transition more reliably
    if (($node['id'] ?? null) === 'node_04_intent_enhanced') {
        echo "  🔧 Fixing: node_04_intent_enhanced\n";

        $newEdges = [];
        foreach ($node['edges'] ?? [] as $edge) {
            if (($edge['id'] ?? null) === 'edge_07a') {
                echo "     Ensuring: edge_07a → node_06_service_selection (standard flow)\n";

                // Go to Service Selection first (standard flow)
                $edge['destination_node_id'] = 'node_06_service_selection';
                $edge['transition_condition'] = [
                    'type' => 'prompt',
                    'prompt' => 'Customer wants to book an appointment'
                ];
            }
            $newEdges[] = $edge;
        }
        $node['edges'] = $newEdges;
    }

    $newNodes[] = $node;
}

$flow['nodes'] = $newNodes;

echo "\n✅ All fixes applied - using CORRECT Retell architecture\n\n";

// Deploy
echo "=== STEP 3: Deploying V33 to Retell ===\n";

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
    echo substr($response, 0, 1000) . "\n";
    exit(1);
}

echo "✅ Flow updated: HTTP {$httpCode}\n\n";

sleep(3);

// Publish
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
    exit(1);
}

echo "✅ Agent published!\n\n";

sleep(3);

// Verify
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

$correctArchitectureOk = false;
$simpleFunctionInstructionOk = false;

foreach ($verifyFlow['nodes'] as $node) {
    // Check initialize goes to conversation node
    if (($node['id'] ?? null) === 'func_00_initialize') {
        foreach ($node['edges'] ?? [] as $edge) {
            if (($edge['id'] ?? null) === 'edge_00') {
                $target = $edge['destination_node_id'] ?? 'N/A';
                $correctArchitectureOk = ($target === 'node_02_customer_routing');
                echo "Initialize → $target " . ($correctArchitectureOk ? "✅" : "❌") . "\n";
            }
        }
    }

    // Check func_check_availability has simple instruction
    if (($node['id'] ?? null) === 'func_check_availability') {
        $instr = $node['instruction']['text'] ?? '';
        $simpleFunctionInstructionOk = (
            strlen($instr) < 500 &&
            stripos($instr, 'GREET') === false &&
            stripos($instr, 'IDENTIFY') === false &&
            stripos($instr, 'Collect ALL') === false
        );
        echo "Function instruction simple: " . ($simpleFunctionInstructionOk ? "✅" : "❌") . "\n";
        echo "  Length: " . strlen($instr) . " chars\n";
    }
}

echo "\n";

if ($correctArchitectureOk && $simpleFunctionInstructionOk) {
    echo "╔══════════════════════════════════════════════════════════════╗\n";
    echo "║        🎉 V33 CORRECT ARCHITECTURE DEPLOYED! 🎉             ║\n";
    echo "╚══════════════════════════════════════════════════════════════╝\n\n";

    echo "📋 CHANGES IN V33:\n";
    echo "  ✅ initialize → conversation node (CORRECT Retell pattern)\n";
    echo "  ✅ func_check_availability: Simple instruction only\n";
    echo "  ✅ Removed: Greeting, Intent, Data Collection from function\n";
    echo "  ✅ Following Retell best practices\n\n";

    echo "💡 CORRECT FLOW:\n";
    echo "  1. initialize (function)\n";
    echo "  2. → Conversation nodes (greeting, intent, data collection)\n";
    echo "  3. → func_check_availability (simple tool execution)\n";
    echo "  4. → Conversation node (result announcement)\n\n";

    echo "🧪 TEST NOW:\n";
    echo "  Call: +493033081738\n";
    echo "  This should now work correctly!\n\n";
} else {
    echo "❌ VERIFICATION FAILED\n";
}
