#!/usr/bin/env php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

echo "=== Updating Retell Agent to V85 Prompt ===\n\n";

$retellApiKey = env('RETELL_TOKEN', 'key_6ff998ba48e842092e04a5455d19');
$retellBaseUrl = env('RETELL_BASE', 'https://api.retellai.com');
$agentId = trim(file_get_contents('/tmp/retell_agent_id.txt'));

echo "Agent ID: {$agentId}\n";

// Load V85 Prompt
$promptFile = __DIR__ . '/../RETELL_PROMPT_V85_RACE_CONDITION_ANREDE_FIX.txt';
if (!file_exists($promptFile)) {
    echo "❌ V85 prompt file not found: {$promptFile}\n";
    exit(1);
}

$promptContent = file_get_contents($promptFile);
echo "✅ V85 Prompt loaded (" . strlen($promptContent) . " characters)\n\n";

// First, get current agent configuration
echo "📥 Fetching current agent configuration...\n";
$currentAgent = Http::withHeaders([
    'Authorization' => 'Bearer ' . $retellApiKey,
    'Content-Type' => 'application/json',
])->get("{$retellBaseUrl}/get-agent/{$agentId}");

if (!$currentAgent->successful()) {
    echo "❌ Failed to fetch agent: " . $currentAgent->status() . "\n";
    echo "Response: " . $currentAgent->body() . "\n";
    exit(1);
}

$agentData = $currentAgent->json();
echo "✅ Current agent fetched: " . ($agentData['agent_name'] ?? 'unknown') . "\n";
echo "   Current prompt length: " . strlen($agentData['general_prompt'] ?? '') . " chars\n\n";

// Update agent with V84 prompt
echo "📤 Updating agent with V84 prompt...\n";

$updateData = [
    'general_prompt' => $promptContent,
    'agent_name' => $agentData['agent_name'] ?? 'AskProAI Agent',
];

try {
    $response = Http::withHeaders([
        'Authorization' => 'Bearer ' . $retellApiKey,
        'Content-Type' => 'application/json',
    ])->patch("{$retellBaseUrl}/update-agent/{$agentId}", $updateData);

    if ($response->successful()) {
        echo "✅ Agent updated successfully!\n\n";

        $updated = $response->json();
        echo "Updated Agent Details:\n";
        echo "  ID: " . ($updated['agent_id'] ?? $agentId) . "\n";
        echo "  Name: " . ($updated['agent_name'] ?? 'unknown') . "\n";
        echo "  Prompt Length: " . strlen($updated['general_prompt'] ?? '') . " chars\n";
        echo "  Modified: " . ($updated['last_modification_timestamp'] ?? 'now') . "\n";

        echo "\n✅ V85 DEPLOYMENT COMPLETE!\n";
        echo "\nV85 Critical Fixes:\n";
        echo "✅ Backend double-check prevents race conditions\n";
        echo "✅ Greeting formality rules (no 'Herr/Frau' + first name)\n";
        echo "✅ Name confirmation pattern kept (works perfectly!)\n";
        echo "\nNext Steps:\n";
        echo "1. Test race condition scenario (slot taken during call)\n";
        echo "2. Test greeting formality with different name types\n";
        echo "3. Monitor logs: tail -f storage/logs/laravel.log | grep 'V85\\|race_condition'\n";
        echo "4. Validate alternatives offered when slot taken\n";

    } else {
        echo "❌ Update failed: " . $response->status() . "\n";
        echo "Response: " . $response->body() . "\n";
        exit(1);
    }

} catch (\Exception $e) {
    echo "❌ Exception during update: " . $e->getMessage() . "\n";
    exit(1);
}
