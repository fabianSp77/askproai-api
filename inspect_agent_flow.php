#!/usr/bin/env php
<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

$token = env('RETELL_TOKEN');
$agentId = 'agent_2d467d84eb674e5b3f5815d81c';

echo "\n═══════════════════════════════════════════════════════════\n";
echo "🔍 AGENT FLOW STRUCTURE DEEP INSPECTION\n";
echo "═══════════════════════════════════════════════════════════\n\n";

// Get agent with version 0 (the one phone uses)
$response = Http::withHeaders([
    'Authorization' => "Bearer $token",
])->get("https://api.retellai.com/get-agent/$agentId?version=0");

if (!$response->successful()) {
    echo "❌ Failed to get agent\n";
    echo "HTTP {$response->status()}\n";
    exit(1);
}

$agent = $response->json();

echo "Agent: {$agent['agent_name']}\n";
echo "Version: {$agent['version']}\n";
echo "Published: " . ($agent['is_published'] ? "YES ✅" : "NO ❌") . "\n\n";

// Check response engine
if (isset($agent['response_engine'])) {
    $engine = $agent['response_engine'];
    echo "Response Engine:\n";
    echo "  Type: {$engine['type']}\n";

    if (isset($engine['conversation_flow_id'])) {
        echo "  Flow ID: {$engine['conversation_flow_id']}\n";
    }

    // If conversation_flow is embedded, check it
    if (isset($engine['conversation_flow'])) {
        $flow = $engine['conversation_flow'];

        echo "\nEmbedded Conversation Flow:\n";
        echo "  Tools: " . (isset($flow['tools']) ? count($flow['tools']) : 0) . "\n";
        echo "  Nodes: " . (isset($flow['nodes']) ? count($flow['nodes']) : 0) . "\n";
        echo "  Edges: " . (isset($flow['edges']) ? count($flow['edges']) : 0) . "\n";

        if (isset($flow['start_node_id'])) {
            echo "  Start Node: {$flow['start_node_id']}\n";
        }

        // Show first few tools
        if (isset($flow['tools']) && !empty($flow['tools'])) {
            echo "\nTools (first 3):\n";
            foreach (array_slice($flow['tools'], 0, 3) as $idx => $tool) {
                $num = $idx + 1;
                echo "  [$num] {$tool['name']}\n";
                echo "      Tool ID: {$tool['tool_id']}\n";
                echo "      URL: " . ($tool['url'] ?? 'MISSING') . "\n";
            }
        }

        // Show function nodes
        if (isset($flow['nodes'])) {
            $functionNodes = array_filter($flow['nodes'], fn($n) => ($n['type'] ?? '') === 'function');
            echo "\nFunction Nodes (" . count($functionNodes) . "):\n";
            foreach ($functionNodes as $node) {
                echo "  • {$node['id']}:\n";
                echo "      Tool ID: " . ($node['tool_id'] ?? 'MISSING') . "\n";
                echo "      Wait for Result: " . (($node['wait_for_result'] ?? false) ? 'YES ✅' : 'NO ❌') . "\n";
            }
        }

        // Check edges connecting to function nodes
        if (isset($flow['edges']) && isset($flow['nodes'])) {
            $functionNodeIds = array_map(fn($n) => $n['id'], array_filter($flow['nodes'], fn($n) => ($n['type'] ?? '') === 'function'));

            echo "\nEdges leading TO function nodes:\n";
            foreach ($flow['edges'] as $edge) {
                if (in_array($edge['to'], $functionNodeIds)) {
                    echo "  {$edge['from']} → {$edge['to']}";
                    if (isset($edge['condition'])) {
                        echo " [condition: " . substr($edge['condition'], 0, 50) . "...]";
                    }
                    echo "\n";
                }
            }
        }
    }
}

// Alternative: Check if flow is separate
if (isset($agent['response_engine']['conversation_flow_id']) && !isset($agent['response_engine']['conversation_flow'])) {
    echo "\n⚠️  Flow is REFERENCED by ID but NOT EMBEDDED in agent response!\n";
    echo "This might be why it's not working.\n";
    echo "Flow ID: {$agent['response_engine']['conversation_flow_id']}\n\n";

    echo "Attempting to fetch flow separately...\n";
    $flowId = $agent['response_engine']['conversation_flow_id'];

    $flowResponse = Http::withHeaders([
        'Authorization' => "Bearer $token",
    ])->get("https://api.retellai.com/get-conversation-flow/$flowId");

    if ($flowResponse->successful()) {
        $flow = $flowResponse->json();
        echo "✅ Flow fetched!\n";
        echo "  Tools: " . (isset($flow['tools']) ? count($flow['tools']) : 0) . "\n";
        echo "  Nodes: " . (isset($flow['nodes']) ? count($flow['nodes']) : 0) . "\n";

        file_put_contents(__DIR__ . '/current_agent_flow.json', json_encode($flow, JSON_PRETTY_PRINT));
        echo "\n✅ Flow saved to current_agent_flow.json\n";
    } else {
        echo "❌ Failed to fetch flow: HTTP {$flowResponse->status()}\n";
    }
}

echo "\n";
