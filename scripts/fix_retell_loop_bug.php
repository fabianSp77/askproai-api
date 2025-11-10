#!/usr/bin/env php
<?php

/**
 * Fix Retell Conversation Flow Loop Bug
 *
 * Problem: Node "Alternative bestätigen" points to "Verfügbarkeit prüfen" (creates loop)
 * Solution: Change edge destination to "Termin buchen"
 *
 * Usage: php scripts/fix_retell_loop_bug.php
 */

require __DIR__ . '/../vendor/autoload.php';

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🔧 Retell Conversation Flow Loop Bug Fix\n";
echo str_repeat("=", 50) . "\n\n";

// Configuration
$apiKey = config('services.retellai.api_key');
$baseUrl = rtrim(config('services.retellai.base_url'), '/');
$conversationFlowId = 'conversation_flow_a58405e3f67a';

if (!$apiKey || !$baseUrl) {
    echo "❌ ERROR: Retell API credentials not configured\n";
    exit(1);
}

echo "📡 Fetching current conversation flow...\n";
echo "   Flow ID: $conversationFlowId\n\n";

// Step 1: Get current conversation flow
try {
    $response = Http::withHeaders([
        'Authorization' => 'Bearer ' . $apiKey,
        'Content-Type' => 'application/json'
    ])->get("$baseUrl/get-conversation-flow/$conversationFlowId");

    if (!$response->successful()) {
        echo "❌ Failed to fetch conversation flow\n";
        echo "   Status: " . $response->status() . "\n";
        echo "   Error: " . $response->body() . "\n";
        exit(1);
    }

    $currentFlow = $response->json();
    echo "✅ Successfully fetched conversation flow\n";
    echo "   Version: " . ($currentFlow['version'] ?? 'unknown') . "\n";
    echo "   Nodes: " . count($currentFlow['nodes'] ?? []) . "\n\n";

} catch (\Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
    exit(1);
}

// Step 2: Find and fix the node
echo "🔍 Searching for node 'Alternative bestätigen' (node_confirm_alternative)...\n";

$nodes = $currentFlow['nodes'] ?? [];
$nodeFound = false;
$nodeBefore = null;

foreach ($nodes as $key => $node) {
    if ($node['id'] === 'node_confirm_alternative') {
        $nodeFound = true;
        $nodeBefore = json_encode($node, JSON_PRETTY_PRINT);

        echo "✅ Found node: " . $node['name'] . " (ID: " . $node['id'] . ")\n";
        echo "   Current edges:\n";

        foreach ($node['edges'] ?? [] as $edge) {
            echo "     - Destination: " . $edge['destination_node_id'];
            if ($edge['destination_node_id'] === 'func_check_availability') {
                echo " ❌ WRONG (causes loop!)\n";
            } else {
                echo "\n";
            }
        }

        // FIX: Change edge destination
        $nodes[$key]['edges'] = [
            [
                'destination_node_id' => 'func_book_appointment',  // ✅ FIXED
                'id' => 'edge_confirm_to_book',
                'transition_condition' => [
                    'type' => 'prompt',
                    'prompt' => 'Alternative confirmed'
                ]
            ]
        ];

        // BONUS: Update instruction text
        $nodes[$key]['instruction'] = [
            'type' => 'prompt',
            'text' => 'Sage: "Perfekt! Ich buche den Termin für {{selected_alternative_time}} Uhr..." und transition direkt zu book_appointment.'
        ];

        echo "\n✅ Fixed node edges:\n";
        echo "     - Destination: func_book_appointment ✅ CORRECT!\n\n";

        break;
    }
}

if (!$nodeFound) {
    echo "❌ Node 'node_confirm_alternative' not found!\n";
    exit(1);
}

// Step 3: Update timeout for all tools (10s → 15s)
echo "⏱️  Increasing tool timeouts from 10s to 15s...\n";

$tools = $currentFlow['tools'] ?? [];
foreach ($tools as $key => $tool) {
    if (isset($tool['timeout_ms']) && $tool['timeout_ms'] == 10000) {
        $tools[$key]['timeout_ms'] = 15000;
        echo "   ✅ Updated: " . ($tool['name'] ?? 'tool-' . $key) . " (10s → 15s)\n";
    }
}
echo "\n";

// Step 4: Prepare update payload
$updatePayload = [
    'nodes' => $nodes,
    'tools' => $tools,
    'global_prompt' => $currentFlow['global_prompt'] ?? null,
    'start_node_id' => $currentFlow['start_node_id'] ?? 'node_greeting',
    'start_speaker' => $currentFlow['start_speaker'] ?? 'agent',
    'model_choice' => $currentFlow['model_choice'] ?? null,
    'model_temperature' => $currentFlow['model_temperature'] ?? 0.3,
];

// Remove null values
$updatePayload = array_filter($updatePayload, fn($v) => $v !== null);

echo "📤 Sending update to Retell API...\n";
echo "   Payload size: " . strlen(json_encode($updatePayload)) . " bytes\n\n";

// Step 5: Update conversation flow via API
try {
    $response = Http::withHeaders([
        'Authorization' => 'Bearer ' . $apiKey,
        'Content-Type' => 'application/json'
    ])->patch(
        "$baseUrl/update-conversation-flow/$conversationFlowId",
        $updatePayload
    );

    if (!$response->successful()) {
        echo "❌ Failed to update conversation flow\n";
        echo "   Status: " . $response->status() . "\n";
        echo "   Error: " . $response->body() . "\n";
        exit(1);
    }

    $updatedFlow = $response->json();

    echo "✅ SUCCESS! Conversation flow updated!\n\n";
    echo str_repeat("=", 50) . "\n";
    echo "📊 SUMMARY\n";
    echo str_repeat("=", 50) . "\n";
    echo "Flow ID: $conversationFlowId\n";
    echo "New Version: " . ($updatedFlow['version'] ?? 'unknown') . "\n";
    echo "\n";
    echo "🔧 Changes Applied:\n";
    echo "1. ✅ Fixed Loop Bug:\n";
    echo "   Node 'Alternative bestätigen'\n";
    echo "   Edge: func_check_availability → func_book_appointment\n";
    echo "\n";
    echo "2. ✅ Increased Timeouts:\n";
    echo "   All tools: 10s → 15s\n";
    echo "\n";
    echo "3. ✅ Updated Instruction:\n";
    echo "   'ich prüfe...' → 'ich buche...'\n";
    echo "\n";
    echo str_repeat("=", 50) . "\n";
    echo "\n";
    echo "🎯 NEXT STEPS:\n";
    echo "1. Test the agent in Retell Dashboard\n";
    echo "2. Say: 'Herrenhaarschnitt morgen 10 Uhr'\n";
    echo "3. When offered alternatives, choose one\n";
    echo "4. ✅ Expected: Agent books directly (NO loop!)\n";
    echo "\n";
    echo "📝 Detailed log saved to: storage/logs/laravel.log\n";
    echo "\n";

    // Log to Laravel
    Log::info('🎉 Retell Conversation Flow Loop Bug Fixed!', [
        'flow_id' => $conversationFlowId,
        'old_version' => $currentFlow['version'] ?? 'unknown',
        'new_version' => $updatedFlow['version'] ?? 'unknown',
        'changes' => [
            'loop_bug_fixed' => true,
            'timeouts_increased' => true,
            'instruction_updated' => true
        ],
        'node_before' => $nodeBefore,
        'node_after' => json_encode($nodes[array_search('node_confirm_alternative', array_column($nodes, 'id'))], JSON_PRETTY_PRINT)
    ]);

} catch (\Exception $e) {
    echo "❌ Exception during update: " . $e->getMessage() . "\n";
    exit(1);
}

echo "🎉 All done! Agent is ready for testing!\n";
exit(0);
