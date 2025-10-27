<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

$token = env('RETELL_TOKEN');
$llmId = trim(file_get_contents(__DIR__ . '/retell_llm_id.txt'));
$voiceId = trim(file_get_contents(__DIR__ . '/working_voice_id.txt'));

echo "\n═══════════════════════════════════════════════════════════\n";
echo "🚀 CREATE LLM AGENT - FINAL ATTEMPT\n";
echo "═══════════════════════════════════════════════════════════\n\n";

echo "LLM ID: $llmId\n";
echo "Voice ID: $voiceId (CORRECT - from existing agent)\n\n";

$agentConfig = [
    'agent_name' => 'Friseur1 AI (LLM-based FINAL)',
    'response_engine' => [
        'type' => 'retell-llm',
        'llm_id' => $llmId
    ],
    'voice_id' => $voiceId,
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

echo "Creating agent...\n\n";

$response = Http::withHeaders([
    'Authorization' => "Bearer $token",
    'Content-Type' => 'application/json'
])->post('https://api.retellai.com/create-agent', $agentConfig);

echo "Status: {$response->status()}\n\n";

if ($response->successful()) {
    $agent = $response->json();
    $agentId = $agent['agent_id'];
    
    echo "✅✅✅ SUCCESS! LLM AGENT CREATED! ✅✅✅\n\n";
    echo "═══════════════════════════════════════════════════════════\n";
    echo "Agent ID: $agentId\n";
    echo "Agent Name: {$agent['agent_name']}\n";
    echo "Response Engine: {$agent['response_engine']['type']}\n";
    echo "LLM ID: {$agent['response_engine']['llm_id']}\n";
    echo "Voice: {$agent['voice_id']}\n";
    echo "Language: {$agent['language']}\n";
    echo "Version: {$agent['version']}\n";
    echo "═══════════════════════════════════════════════════════════\n\n";
    
    file_put_contents(__DIR__ . '/llm_agent_id.txt', $agentId);
    
    echo "Next steps:\n";
    echo "1. Publish agent: php publish_llm_agent.php\n";
    echo "2. Update phone number to use this agent\n";
    echo "3. Make test call\n\n";
    
} else {
    echo "❌ Failed\n";
    echo "Response: {$response->body()}\n";
}

