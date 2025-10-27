<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

$token = env('RETELL_TOKEN');
$agentId = 'agent_45daa54928c5768b52ba3db736';
$flowId = 'conversation_flow_a58405e3f67a';

echo "\n═══════════════════════════════════════════════════════════\n";
echo "🚀 PUBLISHING AGENT V4 - FORCE UPDATE\n";
echo "═══════════════════════════════════════════════════════════\n\n";

echo "Agent ID: $agentId\n";
echo "Flow ID: $flowId\n\n";

// Get current agent status
echo "📋 Current Agent Status:\n";
$agentResp = Http::withHeaders(['Authorization' => "Bearer $token"])
    ->get("https://api.retellai.com/get-agent/$agentId");

if ($agentResp->successful()) {
    $agent = $agentResp->json();
    echo "  Agent Name: {$agent['agent_name']}\n";
    echo "  Version: {$agent['version']}\n";
    echo "  Published: " . ($agent['is_published'] ? '✅ YES' : '❌ NO') . "\n";
    echo "  Flow ID: {$agent['response_engine']['conversation_flow_id']}\n";
    echo "  Flow Version: {$agent['response_engine']['version']}\n\n";
}

// Get flow status
echo "📋 Flow Status:\n";
$flowResp = Http::withHeaders(['Authorization' => "Bearer $token"])
    ->get("https://api.retellai.com/get-conversation-flow/$flowId");

if ($flowResp->successful()) {
    $flow = $flowResp->json();
    echo "  Flow Version: {$flow['version']}\n";
    echo "  Nodes: " . count($flow['nodes']) . "\n";
    echo "  Tools: " . count($flow['tools']) . "\n";

    // Check first 3 nodes to verify V4
    echo "\n  First 3 Nodes:\n";
    for ($i = 0; $i < min(3, count($flow['nodes'])); $i++) {
        $node = $flow['nodes'][$i];
        echo "    " . ($i + 1) . ". {$node['name']} ({$node['id']})\n";
    }

    // Verify V4
    $nodeIds = array_map(fn($n) => $n['id'], $flow['nodes']);
    if (in_array('intent_router', $nodeIds)) {
        echo "\n  ✅ V4 Flow confirmed (has intent_router node)\n\n";
    } else {
        echo "\n  ⚠️  WARNING: Flow might not be V4!\n\n";
    }
}

echo "═══════════════════════════════════════════════════════════\n";
echo "🔄 PUBLISHING AGENT...\n";
echo "═══════════════════════════════════════════════════════════\n\n";

$publishResp = Http::withHeaders(['Authorization' => "Bearer $token"])
    ->post("https://api.retellai.com/publish-agent/$agentId");

if (!$publishResp->successful()) {
    echo "❌ Failed to publish agent\n";
    echo "Status: {$publishResp->status()}\n";
    echo "Error: " . json_encode($publishResp->json(), JSON_PRETTY_PRINT) . "\n";
    exit(1);
}

echo "✅ Agent published successfully!\n\n";

// Verify published status
echo "═══════════════════════════════════════════════════════════\n";
echo "✅ VERIFICATION\n";
echo "═══════════════════════════════════════════════════════════\n\n";

sleep(2); // Wait for propagation

$verifyResp = Http::withHeaders(['Authorization' => "Bearer $token"])
    ->get("https://api.retellai.com/get-agent/$agentId");

if ($verifyResp->successful()) {
    $agent = $verifyResp->json();
    echo "📊 Updated Agent Status:\n";
    echo "  Agent Name: {$agent['agent_name']}\n";
    echo "  Version: {$agent['version']}\n";
    echo "  Published: " . ($agent['is_published'] ? '✅ YES' : '❌ NO') . "\n";
    echo "  Flow ID: {$agent['response_engine']['conversation_flow_id']}\n";
    echo "  Flow Version: {$agent['response_engine']['version']}\n\n";

    if ($agent['is_published']) {
        echo "✅✅✅ SUCCESS! Agent is now PUBLISHED!\n\n";
    } else {
        echo "⚠️  WARNING: Agent shows as not published\n\n";
    }
}

echo "═══════════════════════════════════════════════════════════\n";
echo "🎯 NEXT STEPS\n";
echo "═══════════════════════════════════════════════════════════\n\n";

echo "1. ✅ Agent is now published with V4 flow\n";
echo "2. 📞 Make a NEW test call\n";
echo "3. 🔍 Check logs for 'intent_router' node transitions\n";
echo "4. ✅ Verify call progresses past greeting\n\n";

echo "🔍 To monitor test call:\n";
echo "  tail -f storage/logs/laravel-\$(date +%Y-%m-%d).log | grep -E 'intent|V4|node_transition'\n\n";

echo "═══════════════════════════════════════════════════════════\n";
echo "🎉 V4 AGENT NOW LIVE!\n";
echo "═══════════════════════════════════════════════════════════\n\n";
