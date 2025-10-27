<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

$token = env('RETELL_TOKEN');
$agentId = 'agent_45daa54928c5768b52ba3db736';

echo "\n═══════════════════════════════════════════════════════════\n";
echo "🔍 CHECKING AGENT CONFIGURATION\n";
echo "═══════════════════════════════════════════════════════════\n\n";

echo "Agent ID: $agentId\n\n";

// Get agent configuration
$response = Http::withHeaders(['Authorization' => "Bearer $token"])
    ->get("https://api.retellai.com/get-agent/$agentId");

if (!$response->successful()) {
    echo "❌ Failed to get agent configuration\n";
    echo "Status: {$response->status()}\n";
    echo "Error: " . json_encode($response->json(), JSON_PRETTY_PRINT) . "\n";
    exit(1);
}

$agent = $response->json();

echo "📋 AGENT CONFIGURATION:\n\n";
echo "Agent Name: {$agent['agent_name']}\n";
echo "Agent ID: {$agent['agent_id']}\n\n";

echo "🔄 CONVERSATION FLOW:\n";
if (isset($agent['conversation_flow_id'])) {
    echo "  Flow ID: {$agent['conversation_flow_id']}\n";
} else {
    echo "  ❌ NO FLOW ASSIGNED!\n";
}

if (isset($agent['llm_id'])) {
    echo "  LLM ID: {$agent['llm_id']}\n";
}

echo "\n📊 FULL AGENT CONFIG:\n";
echo json_encode($agent, JSON_PRETTY_PRINT) . "\n\n";

// Also check the flow version
$flowId = 'conversation_flow_a58405e3f67a';
echo "═══════════════════════════════════════════════════════════\n";
echo "🔍 CHECKING FLOW CONFIGURATION\n";
echo "═══════════════════════════════════════════════════════════\n\n";

$flowResp = Http::withHeaders(['Authorization' => "Bearer $token"])
    ->get("https://api.retellai.com/get-conversation-flow/$flowId");

if (!$flowResp->successful()) {
    echo "❌ Failed to get flow configuration\n";
    echo "Status: {$flowResp->status()}\n";
    echo "Error: " . json_encode($flowResp->json(), JSON_PRETTY_PRINT) . "\n";
    exit(1);
}

$flow = $flowResp->json();

echo "📋 FLOW CONFIGURATION:\n\n";
echo "Flow ID: {$flow['conversation_flow_id']}\n";
echo "Version: {$flow['version']}\n";
echo "Created: {$flow['create_time']}\n";
echo "Updated: {$flow['last_modification_time']}\n\n";

echo "🔧 FLOW STRUCTURE:\n";
echo "  Nodes: " . count($flow['nodes']) . "\n";
echo "  Tools: " . count($flow['tools']) . "\n\n";

echo "📊 FIRST 3 NODES:\n";
for ($i = 0; $i < min(3, count($flow['nodes'])); $i++) {
    $node = $flow['nodes'][$i];
    echo "  " . ($i + 1) . ". {$node['name']} ({$node['id']})\n";
}

echo "\n═══════════════════════════════════════════════════════════\n";
echo "🎯 DIAGNOSIS:\n";
echo "═══════════════════════════════════════════════════════════\n\n";

// Check if agent has flow assigned
if (!isset($agent['conversation_flow_id'])) {
    echo "❌ PROBLEM: Agent has NO conversation flow assigned!\n";
    echo "   Solution: Need to assign flow to agent\n\n";
} else if ($agent['conversation_flow_id'] !== $flowId) {
    echo "⚠️  WARNING: Agent is using different flow!\n";
    echo "   Agent Flow ID: {$agent['conversation_flow_id']}\n";
    echo "   Expected Flow ID: $flowId\n\n";
} else {
    echo "✅ Agent is correctly assigned to flow: $flowId\n";
    echo "   But might be using cached version\n\n";
}

// Check node names to verify which version
$firstNodes = array_slice($flow['nodes'], 0, 3);
$nodeNames = array_map(fn($n) => $n['id'], $firstNodes);

echo "🔍 NODE VERIFICATION:\n";
if (in_array('intent_router', $nodeNames)) {
    echo "  ✅ V4 Flow detected (has intent_router node)\n";
} else if (in_array('node_collect_info', $nodeNames)) {
    echo "  ❌ V3 Flow detected (has node_collect_info node)\n";
} else {
    echo "  ⚠️  Unknown flow version\n";
}

echo "\n";
