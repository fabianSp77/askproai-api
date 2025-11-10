#!/usr/bin/env php
<?php

/**
 * Update Agent to V49
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo " Update Agent to V49\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "\n";

$retellApiKey = config('services.retellai.api_key');
$agentId = 'agent_45daa54928c5768b52ba3db736';
$baseUrl = rtrim(config('services.retellai.base_url', 'https://api.retellai.com'), '/');

// Get current agent
echo "🔍 Fetching current agent config...\n";
$response = Http::withHeaders([
    'Authorization' => "Bearer {$retellApiKey}",
])->get("{$baseUrl}/get-agent/{$agentId}");

if (!$response->successful()) {
    echo "❌ ERROR: Failed to fetch agent\n";
    exit(1);
}

$agent = $response->json();
$currentName = $agent['agent_name'] ?? 'Unknown';

echo "Current: {$currentName}\n\n";

// Update agent name
$newName = 'Friseur 1 Agent V49 - Proactive + Anti-Repetition HOTFIX (2025-11-05)';

echo "📤 Updating agent to V49...\n";

$updateResponse = Http::withHeaders([
    'Authorization' => "Bearer {$retellApiKey}",
    'Content-Type' => 'application/json'
])->patch("{$baseUrl}/update-agent/{$agentId}", [
    'agent_name' => $newName
]);

if (!$updateResponse->successful()) {
    echo "❌ ERROR: Failed to update agent\n";
    echo "Status: " . $updateResponse->status() . "\n";
    echo "Body: " . $updateResponse->body() . "\n";
    exit(1);
}

echo "✅ Agent updated successfully!\n\n";

// Verify
echo "🔍 Verifying update...\n";

$verifyResponse = Http::withHeaders([
    'Authorization' => "Bearer {$retellApiKey}",
])->get("{$baseUrl}/get-agent/{$agentId}");

if (!$verifyResponse->successful()) {
    echo "❌ ERROR: Failed to verify\n";
    exit(1);
}

$verifyAgent = $verifyResponse->json();

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
echo "Agent Details:\n";
echo "  ID: " . ($verifyAgent['agent_id'] ?? 'N/A') . "\n";
echo "  Name: " . ($verifyAgent['agent_name'] ?? 'N/A') . "\n";
echo "  Voice: " . ($verifyAgent['voice_id'] ?? 'N/A') . "\n";
echo "  Language: " . ($verifyAgent['response_engine']['llm_id'] ?? 'N/A') . "\n";
echo "\n";

$hasV49 = strpos($verifyAgent['agent_name'] ?? '', 'V49') !== false;

if ($hasV49) {
    echo "═══════════════════════════════════════════════════════════════\n";
    echo " ✅ Agent Update Complete!\n";
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "\n";
    echo "Status: Ready for testing\n";
    echo "Next: Test call to verify all fixes\n";
    echo "\n";
} else {
    echo "❌ Agent name does not contain V49\n";
    exit(1);
}
