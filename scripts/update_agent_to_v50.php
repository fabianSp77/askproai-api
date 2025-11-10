#!/usr/bin/env php
<?php

/**
 * Update Retell Agent to V50
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo " Update Agent to V50 - CRITICAL Tool Enforcement\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "\n";

$retellApiKey = config('services.retellai.api_key');
$agentId = config('services.retellai.agent_id');
$baseUrl = rtrim(config('services.retellai.base_url', 'https://api.retell.ai'), '/');

// Get current agent configuration
echo "🔍 Fetching current agent configuration...\n";

// Try different endpoint formats
$endpoints = [
    "{$baseUrl}/get-agent/{$agentId}",
    "{$baseUrl}/agent/{$agentId}",
    "{$baseUrl}/v2/get-agent/{$agentId}",
];

$response = null;
foreach ($endpoints as $endpoint) {
    echo "  Trying: {$endpoint}...\n";
    $testResponse = Http::withHeaders([
        'Authorization' => "Bearer {$retellApiKey}",
    ])->get($endpoint);

    if ($testResponse->successful()) {
        $response = $testResponse;
        echo "  ✅ Success!\n";
        break;
    } else {
        echo "  ❌ Failed (Status: " . $testResponse->status() . ")\n";
    }
}

if (!$response) {
    echo "\n❌ ERROR: Could not fetch agent configuration from any endpoint\n";
    exit(1);
}

if (!$response->successful()) {
    echo "❌ ERROR: Failed to fetch agent configuration\n";
    echo "Status: " . $response->status() . "\n";
    echo "Body: " . $response->body() . "\n";
    exit(1);
}

$currentAgent = $response->json();
$currentName = $currentAgent['agent_name'] ?? 'Unknown';

echo "✅ Current agent name: {$currentName}\n\n";

// Update agent name to V50
$newName = "Friseur 1 Agent V50 - CRITICAL Tool Enforcement";

echo "📝 Updating agent name to: {$newName}\n";

// Try different update endpoint formats
$updateEndpoints = [
    "{$baseUrl}/update-agent/{$agentId}",
    "{$baseUrl}/agent/{$agentId}",
];

$updateResponse = null;
foreach ($updateEndpoints as $endpoint) {
    echo "  Trying: {$endpoint}...\n";
    $testUpdate = Http::withHeaders([
        'Authorization' => "Bearer {$retellApiKey}",
        'Content-Type' => 'application/json'
    ])->patch($endpoint, [
        'agent_name' => $newName
    ]);

    if ($testUpdate->successful()) {
        $updateResponse = $testUpdate;
        echo "  ✅ Success!\n";
        break;
    } else {
        echo "  ❌ Failed (Status: " . $testUpdate->status() . ")\n";
    }
}

if (!$updateResponse) {
    echo "\n❌ ERROR: Could not update agent name on any endpoint\n";
    exit(1);
}

echo "✅ Agent name updated successfully!\n\n";

// Verify update
echo "🔍 Verifying update...\n";

// Use the same endpoint that worked before
$verifyResponse = null;
foreach ($endpoints as $endpoint) {
    $testVerify = Http::withHeaders([
        'Authorization' => "Bearer {$retellApiKey}",
    ])->get($endpoint);

    if ($testVerify->successful()) {
        $verifyResponse = $testVerify;
        break;
    }
}

if (!$verifyResponse) {
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
echo "\n";

if ($verifiedName === $newName) {
    echo "═══════════════════════════════════════════════════════════════\n";
    echo " ✅ Agent V50 Update Complete!\n";
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "\n";
    echo "Agent: {$agentId}\n";
    echo "Name: {$newName}\n";
    echo "Conversation Flow: conversation_flow_a58405e3f67a (V50 prompt)\n";
    echo "\n";
    echo "Changes:\n";
    echo "  - 🚨 Mandatory tool call enforcement\n";
    echo "  - 🛑 STOP instruction before responding\n";
    echo "  - 🚫 NO invented times rule\n";
    echo "  - 🔧 Tool failure fallback behavior\n";
    echo "\n";
    echo "Status: ✅ LIVE and ready for testing\n";
    echo "\n";
} else {
    echo "❌ Verification failed - name mismatch\n";
    exit(1);
}
