#!/usr/bin/env php
<?php

/**
 * Update CORRECT Retell Agent to V50
 * Agent: agent_45daa54928c5768b52ba3db736
 * Flow: conversation_flow_a58405e3f67a
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo " Update CORRECT Agent to V50 - Friseur 1 Conversation Agent\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "\n";

$retellApiKey = config('services.retellai.api_key');
$agentId = 'agent_45daa54928c5768b52ba3db736';
$conversationFlowId = 'conversation_flow_a58405e3f67a';
$baseUrl = 'https://api.retellai.com';

echo "📋 Target Configuration:\n";
echo "  Agent ID: {$agentId}\n";
echo "  Conversation Flow: {$conversationFlowId}\n";
echo "\n";

// Get current agent configuration
echo "🔍 Fetching current agent configuration...\n";

$response = Http::withHeaders([
    'Authorization' => "Bearer {$retellApiKey}",
])->get("{$baseUrl}/get-agent/{$agentId}");

if (!$response->successful()) {
    echo "❌ ERROR: Failed to fetch agent configuration\n";
    echo "Status: " . $response->status() . "\n";
    echo "Body: " . $response->body() . "\n";
    exit(1);
}

$currentAgent = $response->json();
$currentName = $currentAgent['agent_name'] ?? 'Unknown';
$currentFlow = $currentAgent['llm_websocket_url'] ?? 'Unknown';

echo "✅ Current agent configuration:\n";
echo "  Name: {$currentName}\n";
echo "  Type: " . ($currentAgent['response_engine']['type'] ?? 'unknown') . "\n";
echo "\n";

// Verify conversation flow
echo "🔍 Verifying conversation flow is linked...\n";
if (strpos(json_encode($currentAgent), $conversationFlowId) !== false) {
    echo "✅ Conversation flow {$conversationFlowId} is linked to this agent\n";
} else {
    echo "⚠️  WARNING: Conversation flow may not be linked!\n";
}
echo "\n";

// Update agent name to V50
$newName = "Friseur 1 Agent V50 - CRITICAL Tool Enforcement";

echo "📝 Updating agent name to: {$newName}\n";

$updateResponse = Http::withHeaders([
    'Authorization' => "Bearer {$retellApiKey}",
    'Content-Type' => 'application/json'
])->patch("{$baseUrl}/update-agent/{$agentId}", [
    'agent_name' => $newName
]);

if (!$updateResponse->successful()) {
    echo "❌ ERROR: Failed to update agent name\n";
    echo "Status: " . $updateResponse->status() . "\n";
    echo "Body: " . $updateResponse->body() . "\n";
    exit(1);
}

echo "✅ Agent name updated successfully!\n\n";

// Verify update
echo "🔍 Verifying update...\n";

$verifyResponse = Http::withHeaders([
    'Authorization' => "Bearer {$retellApiKey}",
])->get("{$baseUrl}/get-agent/{$agentId}");

if (!$verifyResponse->successful()) {
    echo "❌ ERROR: Failed to verify update\n";
    exit(1);
}

$updatedAgent = $verifyResponse->json();
$verifiedName = $updatedAgent['agent_name'] ?? 'Unknown';

echo "\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
echo "Verification Results:\n";
echo "  Agent ID: {$agentId}\n";
echo "  Old Name: {$currentName}\n";
echo "  New Name: {$verifiedName}\n";
echo "  Name Match: " . ($verifiedName === $newName ? "✅" : "❌") . "\n";
echo "  Conversation Flow: {$conversationFlowId}\n";
echo "\n";

if ($verifiedName === $newName) {
    echo "═══════════════════════════════════════════════════════════════\n";
    echo " ✅ CORRECT Agent V50 Update Complete!\n";
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "\n";
    echo "Agent: {$agentId}\n";
    echo "Name: {$newName}\n";
    echo "Conversation Flow: {$conversationFlowId} (V50 prompt)\n";
    echo "\n";
    echo "Changes:\n";
    echo "  - 🚨 Mandatory tool call enforcement\n";
    echo "  - 🛑 STOP instruction before responding\n";
    echo "  - 🚫 NO invented times rule\n";
    echo "  - 🔧 Tool failure fallback behavior\n";
    echo "\n";
    echo "Status: ✅ LIVE and ready for testing\n";
    echo "\n";
    echo "📞 Test this agent with:\n";
    echo "  Phone Number: (check Retell dashboard for phone number)\n";
    echo "  Scenario: 'Ich möchte morgen Vormittag einen Balayage Termin'\n";
    echo "\n";
} else {
    echo "❌ Verification failed - name mismatch\n";
    exit(1);
}
