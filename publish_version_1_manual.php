#!/usr/bin/env php
<?php

/**
 * SIMPLEST FIX: Just publish Version 1 which already has tools
 *
 * We know:
 * - Version 0: Published ✅, 7 Tools, 34 Nodes
 * - Version 1: Published ✅, 7 Tools, 34 Nodes
 * - Version 2: Draft, 7 Tools, 34 Nodes
 *
 * So Version 1 is ALREADY published and HAS tools!
 *
 * Maybe the issue is just that we need to TEST again?
 * Or maybe Version 1 has a DIFFERENT flow than Version 0?
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

$token = env('RETELL_TOKEN');
$agentId = 'agent_2d467d84eb674e5b3f5815d81c';

echo "\n═══════════════════════════════════════════════════════════\n";
echo "🔍 COMPARING VERSION 0 vs VERSION 1 FLOWS\n";
echo "═══════════════════════════════════════════════════════════\n\n";

// Get Version 0
echo "Fetching Version 0...\n";
$v0Response = Http::withHeaders([
    'Authorization' => "Bearer $token",
])->get("https://api.retellai.com/get-agent/$agentId?version=0");

$v0 = $v0Response->json();

// Get Version 1
echo "Fetching Version 1...\n";
$v1Response = Http::withHeaders([
    'Authorization' => "Bearer $token",
])->get("https://api.retellai.com/get-agent/$agentId?version=1");

$v1 = $v1Response->json();

echo "\n";
echo "Version 0:\n";
echo "───────────────────────────────────────────────────────────\n";
echo "  Published: " . ($v0['is_published'] ? 'YES' : 'NO') . "\n";
echo "  Flow ID: " . ($v0['response_engine']['conversation_flow_id'] ?? 'N/A') . "\n\n";

echo "Version 1:\n";
echo "───────────────────────────────────────────────────────────\n";
echo "  Published: " . ($v1['is_published'] ? 'YES' : 'NO') . "\n";
echo "  Flow ID: " . ($v1['response_engine']['conversation_flow_id'] ?? 'N/A') . "\n\n";

// Are they using the SAME flow?
$v0FlowId = $v0['response_engine']['conversation_flow_id'] ?? null;
$v1FlowId = $v1['response_engine']['conversation_flow_id'] ?? null;

if ($v0FlowId === $v1FlowId) {
    echo "✅ Both versions use THE SAME conversation flow!\n";
    echo "   Flow ID: $v0FlowId\n\n";

    echo "This means:\n";
    echo "  - The flow IS correct (has 7 tools, 34 nodes)\n";
    echo "  - The problem is in how the flow EXECUTES\n";
    echo "  - We need to fix the flow itself, not the agent\n\n";

    echo "ACTION:\n";
    echo "  1. Create a NEW conversation flow with fixed transitions\n";
    echo "  2. Update agent to use new flow\n";
    echo "  3. Test\n\n";
} else {
    echo "⚠️  Versions use DIFFERENT flows!\n";
    echo "   V0 Flow: $v0FlowId\n";
    echo "   V1 Flow: $v1FlowId\n\n";

    echo "This might explain the issue!\n";
    echo "Let me fetch both flows to compare...\n\n";
}

// Get flow details
if ($v0FlowId) {
    echo "Fetching V0 Flow...\n";
    $flowResponse = Http::withHeaders([
        'Authorization' => "Bearer $token",
    ])->get("https://api.retellai.com/get-conversation-flow/$v0FlowId");

    if ($flowResponse->successful()) {
        $flow = $flowResponse->json();
        echo "V0 Flow Version: {$flow['version']}\n";
        echo "V0 Flow Tools: " . count($flow['tools']) . "\n";
        echo "V0 Flow Nodes: " . count($flow['nodes']) . "\n\n";
    }
}

echo "═══════════════════════════════════════════════════════════\n";
echo "RECOMMENDATION\n";
echo "═══════════════════════════════════════════════════════════\n\n";

echo "The flow structure is the problem (6 prompt-based transitions).\n\n";

echo "QUICKEST FIX:\n";
echo "  1. Test call AGAIN to see if it was temporary\n";
echo "  2. If still fails, we need to modify the flow\n\n";

echo "PROPER FIX:\n";
echo "  1. Create new flow with simpler transitions\n";
echo "  2. Use conversation-type agent instead of flow-based\n";
echo "  3. Let LLM decide when to call functions naturally\n\n";

echo "Would you like to:\n";
echo "  A) Make another test call first (maybe it works now?)\n";
echo "  B) Switch to conversation-type agent (recommended)\n";
echo "  C) Try to fix existing flow transitions\n\n";
