<?php
require __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\Http;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🔍 RETELL API V2 TEST\n";
echo "======================\n\n";

$apiKey = env('RETELL_TOKEN') ?? env('DEFAULT_RETELL_API_KEY');
$baseUrl = env('RETELL_BASE', 'https://api.retellai.com');

if (!$apiKey) {
    echo "❌ No API key found!\n";
    exit(1);
}

echo "API Key: " . substr($apiKey, 0, 10) . "...\n";
echo "Base URL: $baseUrl\n\n";

// Test 1: List agents with correct endpoint
echo "1. Testing list-agents endpoint\n";
echo "-------------------------------\n";

try {
    $response = Http::withHeaders([
        'Authorization' => 'Bearer ' . $apiKey,
        'Content-Type' => 'application/json'
    ])->post($baseUrl . '/list-agents', []);
    
    echo "Status: " . $response->status() . "\n";
    
    if ($response->successful()) {
        $data = $response->json();
        if (isset($data['agents'])) {
            echo "✅ Found " . count($data['agents']) . " agents\n\n";
            foreach ($data['agents'] as $index => $agent) {
                echo "Agent " . ($index + 1) . ":\n";
                echo "  - ID: " . ($agent['agent_id'] ?? 'N/A') . "\n";
                echo "  - Name: " . ($agent['agent_name'] ?? 'N/A') . "\n";
                echo "  - Voice: " . ($agent['voice_id'] ?? 'N/A') . "\n";
                echo "  - Created: " . ($agent['created_at'] ?? 'N/A') . "\n\n";
            }
        } else {
            echo "No agents found in response\n";
            echo "Response: " . json_encode($data, JSON_PRETTY_PRINT) . "\n";
        }
    } else {
        echo "❌ Request failed\n";
        echo "Body: " . $response->body() . "\n";
    }
} catch (\Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}

// Test 2: List calls
echo "\n2. Testing list-calls endpoint\n";
echo "--------------------------------\n";

try {
    $response = Http::withHeaders([
        'Authorization' => 'Bearer ' . $apiKey,
        'Content-Type' => 'application/json'
    ])->post($baseUrl . '/list-calls', [
        'limit' => 10,
        'sort_order' => 'descending'
    ]);
    
    echo "Status: " . $response->status() . "\n";
    
    if ($response->successful()) {
        $data = $response->json();
        if (isset($data['calls'])) {
            echo "✅ Found " . count($data['calls']) . " calls\n\n";
            foreach ($data['calls'] as $index => $call) {
                echo "Call " . ($index + 1) . ":\n";
                echo "  - Call ID: " . ($call['call_id'] ?? 'N/A') . "\n";
                echo "  - From: " . ($call['from_number'] ?? 'N/A') . "\n";
                echo "  - To: " . ($call['to_number'] ?? 'N/A') . "\n";
                echo "  - Status: " . ($call['call_status'] ?? 'N/A') . "\n";
                echo "  - Duration: " . ($call['duration'] ?? 0) . " seconds\n";
                echo "  - Created: " . ($call['created_at'] ?? 'N/A') . "\n";
                
                if (isset($call['metadata']) && is_array($call['metadata'])) {
                    echo "  - Metadata: " . json_encode($call['metadata']) . "\n";
                }
                
                echo "\n";
                
                if ($index >= 4) {
                    echo "... (showing first 5 calls)\n";
                    break;
                }
            }
        } else {
            echo "No calls found in response\n";
            echo "Response: " . json_encode($data, JSON_PRETTY_PRINT) . "\n";
        }
    } else {
        echo "❌ Request failed\n";
        echo "Body: " . $response->body() . "\n";
    }
} catch (\Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}

// Test 3: Get specific agent
echo "\n3. Testing get-agent endpoint\n";
echo "-------------------------------\n";

$testAgentId = 'agent_9a8202a740cd3120d96fcfda1e'; // From the branch config

try {
    $response = Http::withHeaders([
        'Authorization' => 'Bearer ' . $apiKey,
        'Content-Type' => 'application/json'
    ])->post($baseUrl . '/get-agent', [
        'agent_id' => $testAgentId
    ]);
    
    echo "Status: " . $response->status() . "\n";
    
    if ($response->successful()) {
        $agent = $response->json();
        echo "✅ Agent details:\n";
        echo "  - ID: " . ($agent['agent_id'] ?? 'N/A') . "\n";
        echo "  - Name: " . ($agent['agent_name'] ?? 'N/A') . "\n";
        echo "  - Voice: " . ($agent['voice_id'] ?? 'N/A') . "\n";
        
        if (isset($agent['response_engine'])) {
            echo "  - Response Engine: " . ($agent['response_engine']['type'] ?? 'N/A') . "\n";
        }
        
        if (isset($agent['metadata'])) {
            echo "  - Metadata: " . json_encode($agent['metadata']) . "\n";
        }
    } else {
        echo "❌ Request failed\n";
        echo "Body: " . $response->body() . "\n";
    }
} catch (\Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}

echo "\n✅ Test completed\n";