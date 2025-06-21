<?php
require_once __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

$apiKey = config('services.retell.api_key') ?? config('services.retell.token');

echo "Testing CORRECT Retell v2 API endpoints...\n\n";
echo "API Key: " . substr($apiKey, 0, 15) . "...\n\n";

// Test 1: List agents (v2)
echo "1. Testing POST /v2/list-agents:\n";
$response1 = Http::withHeaders([
    'Authorization' => 'Bearer ' . $apiKey,
    'Content-Type' => 'application/json',
])->post('https://api.retellai.com/v2/list-agents', []);

echo "   Status: " . $response1->status() . "\n";
if ($response1->successful()) {
    $data = $response1->json();
    echo "   Success! Found " . count($data) . " agents\n";
    if (!empty($data)) {
        echo "   First agent ID: " . ($data[0]['agent_id'] ?? 'N/A') . "\n";
    }
} else {
    echo "   Error: " . $response1->body() . "\n";
}

// Test 2: List calls (v2)
echo "\n2. Testing POST /v2/list-calls:\n";
$response2 = Http::withHeaders([
    'Authorization' => 'Bearer ' . $apiKey,
    'Content-Type' => 'application/json',
])->post('https://api.retellai.com/v2/list-calls', [
    'limit' => 5,
    'sort_order' => 'descending'
]);

echo "   Status: " . $response2->status() . "\n";
if ($response2->successful()) {
    $data = $response2->json();
    echo "   Success! Response structure:\n";
    echo "   - Keys: " . implode(', ', array_keys($data)) . "\n";
    if (isset($data['calls'])) {
        echo "   - Found " . count($data['calls']) . " calls\n";
    }
} else {
    echo "   Error: " . $response2->body() . "\n";
}

// Test 3: Get single agent
echo "\n3. Testing GET /v2/get-agent/{id}:\n";
// First get an agent ID
if ($response1->successful() && !empty($response1->json())) {
    $agentId = $response1->json()[0]['agent_id'] ?? null;
    if ($agentId) {
        $response3 = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
        ])->get("https://api.retellai.com/v2/get-agent/{$agentId}");
        
        echo "   Status: " . $response3->status() . "\n";
        if ($response3->successful()) {
            echo "   Success! Agent name: " . ($response3->json()['agent_name'] ?? 'N/A') . "\n";
        }
    } else {
        echo "   Skipped - no agent ID available\n";
    }
} else {
    echo "   Skipped - list-agents failed\n";
}

// Test 4: Check if v1 endpoints still work
echo "\n4. Testing v1 endpoints for comparison:\n";
$response4 = Http::withHeaders([
    'Authorization' => 'Bearer ' . $apiKey,
])->get('https://api.retellai.com/list-agents');
echo "   GET /list-agents (v1): " . $response4->status() . "\n";

echo "\n\nConclusion:\n";
if ($response1->status() === 200 && $response2->status() === 200) {
    echo "✅ Retell v2 API is working correctly!\n";
    echo "   - Use POST method for list endpoints\n";
    echo "   - Include /v2/ in the path\n";
    echo "   - Add Content-Type: application/json header\n";
} elseif ($response1->status() === 500 || $response2->status() === 500) {
    echo "⚠️  API returns 500 - server error on Retell's side\n";
} else {
    echo "❌ API authentication or configuration issue\n";
}