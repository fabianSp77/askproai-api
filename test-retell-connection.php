#!/usr/bin/env php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\Http;
use App\Models\Company;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$company = Company::first();
if (!$company || !$company->retell_api_key) {
    echo "❌ No company or Retell API key found\n";
    exit(1);
}

$apiKey = decrypt($company->retell_api_key);
$agentId = $company->retell_agent_id;

echo "Testing Retell.ai API Connection\n";
echo "================================\n";
echo "Company: {$company->name}\n";
echo "Agent ID: {$agentId}\n";
echo "API Key: " . substr($apiKey, 0, 10) . "...\n\n";

// Test 1: List agents
echo "Test 1: List Agents\n";
echo "-------------------\n";

$response = Http::withHeaders([
    'Authorization' => 'Bearer ' . $apiKey,
])->get('https://api.retellai.com/list-agents');

echo "Status: " . $response->status() . "\n";

if ($response->successful()) {
    $agents = $response->json();
    echo "✅ Success! Found " . count($agents) . " agents\n";
    
    foreach ($agents as $agent) {
        echo "  - {$agent['agent_name']} (ID: {$agent['agent_id']})\n";
        if ($agent['agent_id'] === $agentId) {
            echo "    ✅ This is the configured agent!\n";
        }
    }
} else {
    echo "❌ Failed: " . $response->body() . "\n";
}

echo "\n";

// Test 2: Get specific agent
echo "Test 2: Get Specific Agent\n";
echo "--------------------------\n";

$response = Http::withHeaders([
    'Authorization' => 'Bearer ' . $apiKey,
])->get('https://api.retellai.com/get-agent/' . $agentId);

echo "Status: " . $response->status() . "\n";

if ($response->successful()) {
    $agent = $response->json();
    echo "✅ Success! Agent details:\n";
    echo "  Name: " . ($agent['agent_name'] ?? 'N/A') . "\n";
    echo "  ID: " . ($agent['agent_id'] ?? 'N/A') . "\n";
    echo "  LLM: " . ($agent['llm_websocket_url'] ?? 'Not configured') . "\n";
    echo "  Voice: " . ($agent['voice_id'] ?? 'N/A') . "\n";
} else {
    echo "❌ Failed: " . $response->body() . "\n";
}

echo "\n";

// Test 3: Get recent calls
echo "Test 3: Get Recent Calls\n";
echo "------------------------\n";

$response = Http::withHeaders([
    'Authorization' => 'Bearer ' . $apiKey,
])->get('https://api.retellai.com/list-calls?limit=10');

echo "Status: " . $response->status() . "\n";

if ($response->successful()) {
    $data = $response->json();
    $calls = $data['calls'] ?? [];
    echo "✅ Success! Found " . count($calls) . " recent calls\n";
    
    foreach (array_slice($calls, 0, 5) as $call) {
        echo "  - Call ID: {$call['call_id']}\n";
        echo "    Status: {$call['call_status']}\n";
        echo "    Agent: {$call['agent_id']}\n";
        echo "    Duration: " . round($call['duration_ms'] / 1000) . " seconds\n";
        echo "    Created: {$call['start_timestamp']}\n";
    }
} else {
    echo "❌ Failed: " . $response->body() . "\n";
}

echo "\n";
echo "Test complete!\n";