<?php

/**
 * Get current general_prompt from Friseur 1 agent
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$retellApiKey = config('services.retellai.api_key');
$agentId = 'agent_f1ce85d06a84afb989dfbb16a9';

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => "https://api.retellai.com/get-agent/{$agentId}",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $retellApiKey
    ]
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    $agent = json_decode($response, true);

    echo "Agent Configuration for: {$agentId}\n";
    echo "=====================================\n\n";

    echo "Agent Name: " . ($agent['agent_name'] ?? 'N/A') . "\n";
    echo "Response ID: " . ($agent['response_engine']['llm_id'] ?? 'N/A') . "\n\n";

    echo "General Prompt:\n";
    echo "---------------\n";
    echo ($agent['general_prompt'] ?? 'NOT SET') . "\n\n";

    echo "Begin Message:\n";
    echo "---------------\n";
    echo ($agent['begin_message'] ?? 'NOT SET') . "\n\n";

    echo "Conversation Flow Nodes: " . (isset($agent['conversation_flow']['nodes']) ? count($agent['conversation_flow']['nodes']) : 0) . "\n";
    echo "Conversation Flow Tools: " . (isset($agent['conversation_flow']['tools']) ? count($agent['conversation_flow']['tools']) : 0) . "\n";

} else {
    echo "Error fetching agent: HTTP {$httpCode}\n";
    echo $response . "\n";
}
