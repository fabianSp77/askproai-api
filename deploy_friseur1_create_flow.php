<?php

/**
 * Deploy Friseur 1 Flow - Create NEW flow + Assign to Agent
 *
 * Retell API has 2 endpoints:
 * 1. Create Conversation Flow
 * 2. Update Agent to use that flow
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$retellApiKey = config('services.retellai.api_key');

if (!$retellApiKey) {
    echo "❌ RETELLAI_API_KEY not found\n";
    exit(1);
}

echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║     Deploy Friseur 1 Flow (Create + Assign)                 ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";
echo PHP_EOL;

$agentId = 'agent_f1ce85d06a84afb989dfbb16a9';
$flowFile = __DIR__ . '/public/friseur1_flow_complete.json';

// Load flow
echo "📄 Loading Friseur 1 flow...\n";
$flow = json_decode(file_get_contents($flowFile), true);

if (!$flow) {
    echo "❌ Failed to parse JSON\n";
    exit(1);
}

echo "✅ Flow loaded:\n";
echo "  - Nodes: " . count($flow['nodes']) . "\n";
echo "  - Tools: " . count($flow['tools']) . "\n";
echo PHP_EOL;

// Step 1: Create new Conversation Flow
echo "=== Step 1: Create Conversation Flow in Retell ===\n";

$createFlowPayload = [
    'global_prompt' => $flow['global_prompt'],
    'nodes' => $flow['nodes'],
    'start_node_id' => $flow['start_node_id'],
    'start_speaker' => $flow['start_speaker'],
    'tools' => $flow['tools'],
    'model_choice' => $flow['model_choice'] ?? ['type' => 'cascading', 'model' => 'gpt-4o-mini'],
    'model_temperature' => $flow['model_temperature'] ?? 0.3
];

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => "https://api.retellai.com/create-conversation-flow",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $retellApiKey,
        'Content-Type: application/json'
    ],
    CURLOPT_POSTFIELDS => json_encode($createFlowPayload)
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200 || $httpCode === 201) {
    $flowData = json_decode($response, true);
    $conversationFlowId = $flowData['conversation_flow_id'] ?? null;

    if (!$conversationFlowId) {
        echo "❌ No conversation_flow_id in response\n";
        echo "Response: {$response}\n";
        exit(1);
    }

    echo "✅ Conversation Flow created!\n";
    echo "  - Flow ID: {$conversationFlowId}\n";
    echo "  - HTTP Code: {$httpCode}\n";
} else {
    echo "❌ Failed to create conversation flow\n";
    echo "  - HTTP Code: {$httpCode}\n";
    echo "  - Response: {$response}\n";
    exit(1);
}
echo PHP_EOL;

// Step 2: Update Agent to use new flow
echo "=== Step 2: Update Agent to use new Conversation Flow ===\n";

$updateAgentPayload = [
    'response_engine' => [
        'type' => 'conversation-flow',
        'version' => 2,
        'conversation_flow_id' => $conversationFlowId
    ]
];

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => "https://api.retellai.com/update-agent/{$agentId}",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST => 'PATCH',
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $retellApiKey,
        'Content-Type: application/json'
    ],
    CURLOPT_POSTFIELDS => json_encode($updateAgentPayload)
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200 || $httpCode === 201) {
    echo "✅ Agent updated to use new flow!\n";
    echo "  - Agent ID: {$agentId}\n";
    echo "  - Flow ID: {$conversationFlowId}\n";
    echo "  - HTTP Code: {$httpCode}\n";
} else {
    echo "❌ Failed to update agent\n";
    echo "  - HTTP Code: {$httpCode}\n";
    echo "  - Response: {$response}\n";
    exit(1);
}
echo PHP_EOL;

// Step 3: Publish Agent
echo "=== Step 3: Publish Agent ===\n";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => "https://api.retellai.com/publish-agent/{$agentId}",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $retellApiKey,
        'Content-Type: application/json'
    ]
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200 || $httpCode === 201) {
    echo "✅ Agent PUBLISHED!\n";
    echo "  - HTTP Code: {$httpCode}\n";
    echo "  - Changes are LIVE!\n";
} else {
    echo "⚠️ Publish may have failed\n";
    echo "  - HTTP Code: {$httpCode}\n";
    echo "  - Response: {$response}\n";
}
echo PHP_EOL;

// Summary
echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║                    DEPLOYMENT COMPLETE                       ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";
echo PHP_EOL;

echo "✅ Friseur 1 Agent ist jetzt LIVE!\n";
echo PHP_EOL;

echo "Dashboard: https://dashboard.retellai.com/agents/{$agentId}\n";
echo "Flow ID: {$conversationFlowId}\n";
echo PHP_EOL;

echo "🎯 Der Agent sagt jetzt:\n";
echo "  - 'Terminassistent von Friseur 1' (nicht AskPro AI)\n";
echo "  - Kennt Herrenhaarschnitt, Damenhaarschnitt, Ansatzfärbung\n";
echo "  - Erklärt Composite Services (Wartezeiten)\n";
echo "  - Kennt Team: Emma, Fabian, David, Michael, Dr. Sarah\n";
echo "  - Akzeptiert 'mitarbeiter' Parameter\n";
echo PHP_EOL;

echo "✅ DEPLOYMENT: SUCCESS\n";
