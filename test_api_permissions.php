<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

$token = env('RETELL_TOKEN');

echo "\n═══════════════════════════════════════════════════════════\n";
echo "🔍 TEST API TOKEN PERMISSIONS\n";
echo "═══════════════════════════════════════════════════════════\n\n";

echo "Testing different endpoints to identify permission issues...\n\n";

// Test 1: GET (Read) - sollte funktionieren
echo "1. GET /list-agents (READ operation):\n";
$resp1 = Http::withHeaders(['Authorization' => "Bearer $token"])
    ->get('https://api.retellai.com/list-agents');
echo "   Status: {$resp1->status()} " . ($resp1->successful() ? "✅" : "❌") . "\n\n";

// Test 2: GET specific agent (Read)
echo "2. GET /get-agent/agent_f1ce85d06a84afb989dfbb16a9 (READ operation):\n";
$resp2 = Http::withHeaders(['Authorization' => "Bearer $token"])
    ->get('https://api.retellai.com/get-agent/agent_f1ce85d06a84afb989dfbb16a9');
echo "   Status: {$resp2->status()} " . ($resp2->successful() ? "✅" : "❌") . "\n\n";

// Test 3: POST create-agent (Write)
echo "3. POST /create-agent (WRITE operation):\n";
$resp3 = Http::withHeaders([
    'Authorization' => "Bearer $token",
    'Content-Type' => 'application/json'
])->post('https://api.retellai.com/create-agent', [
    'response_engine' => [
        'type' => 'retell-llm',
        'llm_id' => 'llm_36bd5fb31065787c13797e05a29a',
        'version' => 0
    ],
    'voice_id' => '11labs-Christopher'
]);
echo "   Status: {$resp3->status()} " . ($resp3->successful() ? "✅" : "❌") . "\n";
echo "   Response: " . substr($resp3->body(), 0, 200) . "\n\n";

// Test 4: POST update-agent (Write)
echo "4. PATCH /update-agent/agent_f1ce85d06a84afb989dfbb16a9 (WRITE operation):\n";
$resp4 = Http::withHeaders([
    'Authorization' => "Bearer $token",
    'Content-Type' => 'application/json'
])->patch('https://api.retellai.com/update-agent/agent_f1ce85d06a84afb989dfbb16a9', [
    'agent_name' => 'Test Name Change'
]);
echo "   Status: {$resp4->status()} " . ($resp4->successful() ? "✅" : "❌") . "\n";
echo "   Response: " . substr($resp4->body(), 0, 200) . "\n\n";

echo "═══════════════════════════════════════════════════════════\n";
echo "DIAGNOSIS\n";
echo "═══════════════════════════════════════════════════════════\n\n";

if ($resp1->successful() && !$resp3->successful()) {
    echo "⚠️  API Token has READ permissions but NOT WRITE permissions\n";
    echo "Solution: Generate new API token with write access in Retell Dashboard\n\n";
} elseif (!$resp1->successful()) {
    echo "❌ API Token invalid or expired\n\n";
} else {
    echo "🤔 Something else is wrong - both read and write work\n\n";
}

