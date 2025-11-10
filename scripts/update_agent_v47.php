#!/usr/bin/env php
<?php

/**
 * Update Agent to V47 - Create new version with updated name
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo " Update Agent to V47\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "\n";

$retellApiKey = config('services.retellai.api_key');
$agentId = 'agent_45daa54928c5768b52ba3db736';
$baseUrl = rtrim(config('services.retellai.base_url', 'https://api.retellai.com'), '/');

// Update agent with new name
echo "🚀 Updating agent to V47...\n\n";

$response = Http::withHeaders([
    'Authorization' => "Bearer {$retellApiKey}",
    'Content-Type' => 'application/json'
])->patch("{$baseUrl}/update-agent/{$agentId}", [
    'agent_name' => 'Friseur 1 Agent V47 - UX Fixes (2025-11-05)'
]);

if (!$response->successful()) {
    echo "❌ ERROR: Failed to update agent\n";
    echo "Status: " . $response->status() . "\n";
    echo "Body: " . $response->body() . "\n";
    exit(1);
}

$updatedAgent = $response->json();

echo "✅ Agent updated!\n\n";

echo "═══════════════════════════════════════════════════════════════\n";
echo " Agent V47 Status\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

echo "Agent ID: " . ($updatedAgent['agent_id'] ?? 'N/A') . "\n";
echo "Agent Name: " . ($updatedAgent['agent_name'] ?? 'N/A') . "\n";

if (isset($updatedAgent['response_engine']['conversation_flow_id'])) {
    echo "Conversation Flow ID: " . $updatedAgent['response_engine']['conversation_flow_id'] . "\n";
}

echo "\n";
echo "✅ Agent V47 erstellt und bereit zum Publishen!\n";
echo "\n";
