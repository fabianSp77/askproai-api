<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

$token = env('RETELL_TOKEN');
$llmId = trim(file_get_contents(__DIR__ . '/retell_llm_id.txt'));

echo "\n═══════════════════════════════════════════════════════════\n";
echo "🔧 CREATE AGENT - CORRECT ENDPOINT\n";
echo "═══════════════════════════════════════════════════════════\n\n";

echo "LLM ID: $llmId\n";
echo "Endpoint: POST /agent (NOT /create-agent)\n\n";

$agentConfig = [
    'agent_name' => 'Friseur1 AI (LLM-based WORKING)',
    'response_engine' => [
        'type' => 'retell-llm',
        'llm_id' => $llmId,
        'version' => 0
    ],
    'voice_id' => '11labs-Christopher',
    'language' => 'de-DE',
    'enable_backchannel' => true,
    'responsiveness' => 1.0,
    'interruption_sensitivity' => 1,
    'enable_transcription_formatting' => false,
    'reminder_trigger_ms' => 10000,
    'reminder_max_count' => 2,
    'max_call_duration_ms' => 1800000,
    'end_call_after_silence_ms' => 60000,
    'webhook_url' => 'https://api.askproai.de/api/webhooks/retell'
];

echo "Creating agent with CORRECT endpoint...\n\n";

$response = Http::withHeaders([
    'Authorization' => "Bearer $token",
    'Content-Type' => 'application/json'
])->post('https://api.retellai.com/agent', $agentConfig);

echo "Status: {$response->status()}\n\n";

if ($response->successful()) {
    $agent = $response->json();
    $agentId = $agent['agent_id'];
    
    echo "✅ SUCCESS! Agent created!\n\n";
    echo "Agent ID: $agentId\n";
    echo "Agent Name: {$agent['agent_name']}\n";
    echo "Response Engine: {$agent['response_engine']['type']}\n";
    echo "LLM ID: {$agent['response_engine']['llm_id']}\n";
    echo "Voice: {$agent['voice_id']}\n";
    echo "Version: {$agent['version']}\n\n";
    
    file_put_contents(__DIR__ . '/llm_agent_id.txt', $agentId);
    
    echo "Saved to: llm_agent_id.txt\n\n";
    
    echo "═══════════════════════════════════════════════════════════\n";
    echo "✅ LLM-BASED AGENT SUCCESSFULLY CREATED!\n";
    echo "═══════════════════════════════════════════════════════════\n\n";
    
} else {
    echo "❌ Failed to create agent\n";
    echo "Response: {$response->body()}\n";
}

