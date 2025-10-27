<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

$token = env('RETELL_TOKEN');

echo "\n═══════════════════════════════════════════════════════════\n";
echo "🔍 CHECK RETELL API STATUS\n";
echo "═══════════════════════════════════════════════════════════\n\n";

// Check if gestern's agent still exists
echo "1. Check yesterday's agent (agent_2d467d84eb674e5b3f5815d81c):\n";
$resp = Http::withHeaders(['Authorization' => "Bearer $token"])
    ->get('https://api.retellai.com/get-agent/agent_2d467d84eb674e5b3f5815d81c');
    
if ($resp->successful()) {
    $agent = $resp->json();
    echo "   ✅ Agent exists\n";
    echo "   Name: {$agent['agent_name']}\n";
    echo "   Created (timestamp): {$agent['last_modification_timestamp']}\n";
} else {
    echo "   ❌ Agent not found\n";
}
echo "\n";

// Try different API endpoints
$endpoints = [
    'POST /create-agent',
    'POST /agents',
    'POST /v1/agent',
    'POST /v1/agents',
    'POST /v2/agent',
    'POST /api/agent',
];

echo "2. Testing potential agent creation endpoints:\n";
echo "────────────────────────────────────────────────────────\n";

foreach ($endpoints as $endpoint) {
    [$method, $path] = explode(' ', $endpoint);
    $url = 'https://api.retellai.com' . $path;
    
    $resp = Http::withHeaders([
        'Authorization' => "Bearer $token",
        'Content-Type' => 'application/json'
    ])->post($url, [
        'agent_name' => 'Test',
        'response_engine' => ['type' => 'retell-llm', 'llm_id' => 'llm_36bd5fb31065787c13797e05a29a'],
        'voice_id' => '11labs-Christopher'
    ]);
    
    $status = $resp->status();
    $indicator = $status === 404 ? '❌' : ($status < 300 ? '✅' : '⚠️');
    echo "   $indicator $endpoint → HTTP $status\n";
    
    if ($status < 300) {
        echo "      Response: " . substr($resp->body(), 0, 100) . "\n";
        break;
    }
}

echo "\n";

