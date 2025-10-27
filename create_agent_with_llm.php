#!/usr/bin/env php
<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

$token = env('RETELL_TOKEN');
$llmId = trim(file_get_contents(__DIR__ . '/retell_llm_id.txt'));

echo "\n═══════════════════════════════════════════════════════════\n";
echo "🤖 CREATE AGENT WITH LLM\n";
echo "═══════════════════════════════════════════════════════════\n\n";

echo "LLM ID: $llmId\n\n";

$agentConfig = [
    'agent_name' => 'Friseur1 AI (LLM-based ROBUST)',
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

echo "Creating agent...\n";
echo "Endpoint: https://api.retellai.com/create-agent\n";
echo "Payload:\n";
echo json_encode($agentConfig, JSON_PRETTY_PRINT) . "\n\n";

$response = Http::withHeaders([
    'Authorization' => "Bearer $token",
    'Content-Type' => 'application/json'
])->post('https://api.retellai.com/create-agent', $agentConfig);

if ($response->successful()) {
    $agent = $response->json();
    $agentId = $agent['agent_id'];

    echo "✅ AGENT CREATED!\n\n";
    echo "Agent ID: $agentId\n";
    echo "Agent Name: {$agent['agent_name']}\n";
    echo "Response Engine: {$agent['response_engine']['type']}\n";
    echo "LLM ID: {$agent['response_engine']['llm_id']}\n\n";

    file_put_contents(__DIR__ . '/llm_based_agent_id.txt', $agentId);

    // Publish
    echo "Publishing agent...\n";
    $publishResponse = Http::withHeaders([
        'Authorization' => "Bearer $token",
    ])->post("https://api.retellai.com/publish-agent/$agentId");

    if ($publishResponse->successful()) {
        echo "✅ AGENT PUBLISHED!\n\n";
    } else {
        echo "⚠️  Publish: HTTP {$publishResponse->status()}\n\n";
    }

    echo "═══════════════════════════════════════════════════════════\n";
    echo "✅ READY TO SWITCH!\n";
    echo "═══════════════════════════════════════════════════════════\n\n";

    echo "LLM ID: $llmId\n";
    echo "Agent ID: $agentId\n\n";

    echo "NEXT: Update phone number to this agent\n";
    echo "  Old Agent: agent_2d467d84eb674e5b3f5815d81c (flow-based)\n";
    echo "  New Agent: $agentId (llm-based)\n\n";

} else {
    echo "❌ Failed to create agent\n";
    echo "HTTP {$response->status()}\n";
    echo "Response: {$response->body()}\n";
    exit(1);
}
