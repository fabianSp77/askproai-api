<?php

/**
 * Deploy Friseur 1 Flow V24 - ROBUST TRANSITION FIX (FIXED VERSION)
 *
 * Properly rebuilds the nodes array with modified edges
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
echo "║   🚀 V24: ROBUST TRANSITION FIX (FIXED)                     ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";
echo PHP_EOL;

$agentId = 'agent_f1ce85d06a84afb989dfbb16a9';

// Get current flow
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

echo "✅ Flow ID: {$flowId}\n\n";

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

// Apply fixes by rebuilding nodes array
echo "=== Applying V24 Fixes ===\n";

$newNodes = [];

foreach ($flow['nodes'] as $node) {
    // FIX 1: Intent Node
    if (($node['id'] ?? null) === 'node_04_intent_enhanced') {
        echo "  🔧 Fixing: node_04_intent_enhanced\n";

        $newEdges = [];
        foreach ($node['edges'] ?? [] as $edge) {
            if (($edge['id'] ?? null) === 'edge_07a') {
                echo "     🔧 Simplifying edge_07a\n";
                $edge['transition_condition'] = [
                    'type' => 'prompt',
                    'prompt' => 'User wants to book a new appointment. Intent is clear from their message.'
                ];
                echo "     ✅ New prompt: 'User wants to book a new appointment'\n";
            }
            $newEdges[] = $edge;
        }
        $node['edges'] = $newEdges;
    }

    // FIX 2: DateTime Collection
    if (($node['id'] ?? null) === 'node_07_datetime_collection') {
        echo "  🔧 Fixing: node_07_datetime_collection\n";

        $newEdges = [];
        foreach ($node['edges'] ?? [] as $edge) {
            if (($edge['id'] ?? null) === 'edge_11') {
                echo "     🔧 Simplifying edge_11\n";
                $edge['transition_condition'] = [
                    'type' => 'prompt',
                    'prompt' => 'Customer provided date and time. Ready to check availability.'
                ];
                echo "     ✅ New prompt: 'Customer provided date and time'\n";
            }
            $newEdges[] = $edge;
        }
        $node['edges'] = $newEdges;
    }

    // FIX 3: Service Selection
    if (($node['id'] ?? null) === 'node_06_service_selection') {
        echo "  🔧 Fixing: node_06_service_selection\n";

        $newEdges = [];
        foreach ($node['edges'] ?? [] as $edge) {
            if (($edge['id'] ?? null) === 'edge_10') {
                echo "     🔧 Simplifying edge_10\n";
                $edge['transition_condition'] = [
                    'type' => 'prompt',
                    'prompt' => 'Customer mentioned service or confirmed service choice.'
                ];
                echo "     ✅ Simplified service transition\n";
            }
            $newEdges[] = $edge;
        }
        $node['edges'] = $newEdges;
    }

    $newNodes[] = $node;
}

$flow['nodes'] = $newNodes;

echo "\n✅ All fixes applied to flow structure\n\n";

// Deploy
echo "=== Deploying to Retell ===\n";

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

echo "✅ Flow updated: HTTP {$httpCode}\n\n";

sleep(3);

// Publish
echo "=== Publishing Agent ===\n";

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
echo "=== Verification ===\n";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => "https://api.retellai.com/get-conversation-flow/{$flowId}",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $retellApiKey]
]);
$flowResponse = curl_exec($ch);
curl_close($ch);

$verifyFlow = json_decode($flowResponse, true);

$intentOk = false;
$datetimeOk = false;
$serviceOk = false;

foreach ($verifyFlow['nodes'] as $node) {
    if (($node['id'] ?? null) === 'node_04_intent_enhanced') {
        foreach ($node['edges'] ?? [] as $edge) {
            if (($edge['id'] ?? null) === 'edge_07a') {
                $prompt = $edge['transition_condition']['prompt'] ?? '';
                $intentOk = strpos($prompt, 'User wants to book a new appointment') !== false;
                echo "Intent Edge: " . ($intentOk ? "✅" : "❌") . " " . substr($prompt, 0, 50) . "...\n";
            }
        }
    }

    if (($node['id'] ?? null) === 'node_07_datetime_collection') {
        foreach ($node['edges'] ?? [] as $edge) {
            if (($edge['id'] ?? null) === 'edge_11') {
                $prompt = $edge['transition_condition']['prompt'] ?? '';
                $datetimeOk = strpos($prompt, 'Customer provided date and time') !== false;
                echo "DateTime Edge: " . ($datetimeOk ? "✅" : "❌") . " " . substr($prompt, 0, 50) . "...\n";
            }
        }
    }

    if (($node['id'] ?? null) === 'node_06_service_selection') {
        foreach ($node['edges'] ?? [] as $edge) {
            if (($edge['id'] ?? null) === 'edge_10') {
                $prompt = $edge['transition_condition']['prompt'] ?? '';
                $serviceOk = strpos($prompt, 'Customer mentioned service') !== false;
                echo "Service Edge: " . ($serviceOk ? "✅" : "❌") . " " . substr($prompt, 0, 50) . "...\n";
            }
        }
    }
}

echo "\n";

if ($intentOk && $datetimeOk && $serviceOk) {
    echo "╔══════════════════════════════════════════════════════════════╗\n";
    echo "║              🎉 V24 DEPLOYED SUCCESSFULLY! 🎉                ║\n";
    echo "╚══════════════════════════════════════════════════════════════╝\n\n";

    echo "📋 CHANGES IN V24:\n";
    echo "  ✅ Intent: 'User wants to book' (was: complex keyword list)\n";
    echo "  ✅ DateTime: 'Customer provided date and time' (was: ALL required data...)\n";
    echo "  ✅ Service: 'Customer mentioned service' (simplified)\n\n";

    echo "🧪 TEST NOW:\n";
    echo "  Call: +493033081738\n";
    echo "  Say: 'Ich möchte morgen 14 Uhr einen Herrenhaarschnitt'\n";
    echo "  Expected: Functions WILL be called!\n\n";
} else {
    echo "❌ VERIFICATION FAILED - Changes not applied!\n";
}
