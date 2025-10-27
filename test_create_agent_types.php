<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

$token = env('RETELL_TOKEN');

echo "\n═══════════════════════════════════════════════════════════\n";
echo "🧪 TEST CREATE-AGENT MIT VERSCHIEDENEN TYPES\n";
echo "═══════════════════════════════════════════════════════════\n\n";

// Test 1: conversation-flow type
echo "Test 1: Create agent mit conversation-flow type\n";
echo "────────────────────────────────────────────────────────\n";
$resp1 = Http::withHeaders([
    'Authorization' => "Bearer $token",
    'Content-Type' => 'application/json'
])->post('https://api.retellai.com/create-agent', [
    'agent_name' => 'Test Flow Agent',
    'response_engine' => [
        'type' => 'conversation-flow',
        'conversation_flow_id' => 'conversation_flow_134a15784642'
    ],
    'voice_id' => '11labs-Christopher'
]);
echo "Status: {$resp1->status()}\n";
if ($resp1->successful()) {
    $agent = $resp1->json();
    echo "✅ SUCCESS! Agent ID: {$agent['agent_id']}\n";
    
    // Cleanup - delete the test agent
    Http::withHeaders(['Authorization' => "Bearer $token"])
        ->delete("https://api.retellai.com/delete-agent/{$agent['agent_id']}");
    echo "   (Deleted test agent)\n";
} else {
    echo "❌ FAILED: " . substr($resp1->body(), 0, 150) . "\n";
}
echo "\n";

// Test 2: retell-llm type
echo "Test 2: Create agent mit retell-llm type\n";
echo "────────────────────────────────────────────────────────\n";
$resp2 = Http::withHeaders([
    'Authorization' => "Bearer $token",
    'Content-Type' => 'application/json'
])->post('https://api.retellai.com/create-agent', [
    'agent_name' => 'Test LLM Agent',
    'response_engine' => [
        'type' => 'retell-llm',
        'llm_id' => 'llm_36bd5fb31065787c13797e05a29a',
        'version' => 0
    ],
    'voice_id' => '11labs-Christopher'
]);
echo "Status: {$resp2->status()}\n";
if ($resp2->successful()) {
    $agent = $resp2->json();
    echo "✅ SUCCESS! Agent ID: {$agent['agent_id']}\n";
} else {
    echo "❌ FAILED: " . substr($resp2->body(), 0, 150) . "\n";
}
echo "\n";

echo "═══════════════════════════════════════════════════════════\n";
echo "ERGEBNIS\n";
echo "═══════════════════════════════════════════════════════════\n\n";

if ($resp1->successful() && !$resp2->successful()) {
    echo "⚠️  /create-agent funktioniert NUR für conversation-flow, NICHT für retell-llm\n";
    echo "→ retell-llm agents müssen via Dashboard erstellt werden\n\n";
} elseif ($resp2->successful()) {
    echo "✅ retell-llm agent creation funktioniert!\n\n";
} else {
    echo "❌ Beide failed - API problem\n\n";
}

