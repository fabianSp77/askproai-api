<?php

// Bootstrap Laravel
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

$retellBase = config('services.retell.base_url') ?? 'https://api.retellai.com';
$retellToken = config('services.retell.api_key') ?? config('services.retell.token');

echo "╔════════════════════════════════════════════════════════╗\n";
echo "║ Retell API Debug Information                           ║\n";
echo "╚════════════════════════════════════════════════════════╝\n\n";

echo "API Base: $retellBase\n";
echo "Token: " . substr($retellToken, 0, 10) . "...\n\n";

// Step 1: List all agents
echo "Step 1: Listing all existing agents...\n";
$response = Http::withHeaders([
    'Authorization' => "Bearer $retellToken",
    'Content-Type' => 'application/json'
])->get("$retellBase/list-agents");

echo "Status: " . $response->status() . "\n";
echo "Response:\n";
echo json_encode($response->json(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n\n";

// Step 2: Get agent by ID
$agentId = 'agent_b36ecd3927a81834b6d56ab07b';
echo "Step 2: Trying to fetch existing agent (ID: $agentId)...\n";
$response = Http::withHeaders([
    'Authorization' => "Bearer $retellToken",
    'Content-Type' => 'application/json'
])->get("$retellBase/agent/$agentId");

echo "Status: " . $response->status() . "\n";
echo "Response:\n";
echo json_encode($response->json(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n\n";

// Step 3: Attempt minimal agent creation
echo "Step 3: Testing minimal agent creation payload...\n";

$minimalPayload = [
    'agent_name' => 'Test Agent V127',
    'agent_prompt' => 'Du bist ein Buchungsassistent.',
    'language' => 'de'
];

echo "Payload:\n";
echo json_encode($minimalPayload, JSON_PRETTY_PRINT) . "\n";

$response = Http::withHeaders([
    'Authorization' => "Bearer $retellToken",
    'Content-Type' => 'application/json'
])->post("$retellBase/create-agent", $minimalPayload);

echo "Status: " . $response->status() . "\n";
echo "Response:\n";
echo json_encode($response->json(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n\n";

if ($response->status() === 201) {
    $agentData = $response->json();
    echo "✅ Agent created successfully!\n";
    echo "Agent ID: " . ($agentData['agent_id'] ?? 'N/A') . "\n";
} else {
    echo "❌ Failed to create agent.\n";
    if ($response->failed()) {
        echo "Error: " . $response->body() . "\n";
    }
}
