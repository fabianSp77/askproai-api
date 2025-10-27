<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

$token = env('RETELL_TOKEN');
$llmId = trim(file_get_contents(__DIR__ . '/retell_llm_id.txt'));

// Use the new agent we created previously
$agentId = 'agent_2d467d84eb674e5b3f5815d81c';

echo "\n═══════════════════════════════════════════════════════════\n";
echo "🔄 UPDATE AGENT TO USE RETELL-LLM\n";
echo "═══════════════════════════════════════════════════════════\n\n";

echo "Agent ID: $agentId\n";
echo "LLM ID: $llmId\n\n";

echo "Updating agent to use retell-llm...\n\n";

$response = Http::withHeaders([
    'Authorization' => "Bearer $token",
    'Content-Type' => 'application/json'
])->patch("https://api.retellai.com/update-agent/$agentId", [
    'response_engine' => [
        'type' => 'retell-llm',
        'llm_id' => $llmId
    ]
]);

echo "Status: {$response->status()}\n";

if ($response->successful()) {
    $agent = $response->json();
    echo "✅ Agent updated successfully!\n\n";
    echo "Agent ID: {$agent['agent_id']}\n";
    echo "Version: {$agent['version']}\n";
    echo "Response Engine Type: {$agent['response_engine']['type']}\n";
    if (isset($agent['response_engine']['llm_id'])) {
        echo "LLM ID: {$agent['response_engine']['llm_id']}\n";
    }
} else {
    echo "❌ Failed to update agent\n";
    echo "Response: {$response->body()}\n";
}

echo "\n";
