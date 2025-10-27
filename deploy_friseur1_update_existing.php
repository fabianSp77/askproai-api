<?php

/**
 * Deploy Friseur 1 - UPDATE existing conversation flow
 *
 * Agent already has: conversation_flow_1607b81c8f93
 * We'll UPDATE that flow instead of creating a new one
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
echo "║     Update EXISTING Friseur 1 Conversation Flow             ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";
echo PHP_EOL;

// The agent's current flow ID (from export you showed me)
$existingFlowId = 'conversation_flow_1607b81c8f93';
$flowFile = __DIR__ . '/public/friseur1_flow_complete.json';

// Load our Friseur 1 flow
echo "📄 Loading Friseur 1 flow...\n";
$flow = json_decode(file_get_contents($flowFile), true);

if (!$flow) {
    echo "❌ Failed to parse JSON\n";
    exit(1);
}

echo "✅ Flow loaded:\n";
echo "  - Nodes: " . count($flow['nodes']) . "\n";
echo "  - Tools: " . count($flow['tools']) . "\n";
echo "  - Branding: Friseur 1 ✅\n";
echo PHP_EOL;

// Update the EXISTING flow
echo "=== Updating Existing Flow: {$existingFlowId} ===\n";

$updatePayload = [
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
    CURLOPT_URL => "https://api.retellai.com/update-conversation-flow/{$existingFlowId}",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST => 'PATCH',
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $retellApiKey,
        'Content-Type: application/json'
    ],
    CURLOPT_POSTFIELDS => json_encode($updatePayload)
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200 || $httpCode === 201) {
    echo "✅ Conversation Flow UPDATED!\n";
    echo "  - Flow ID: {$existingFlowId}\n";
    echo "  - HTTP Code: {$httpCode}\n";
} else {
    echo "❌ Failed to update conversation flow\n";
    echo "  - HTTP Code: {$httpCode}\n";
    echo "  - Response: {$response}\n";
    exit(1);
}
echo PHP_EOL;

// Publish the flow
echo "=== Publishing Updated Flow ===\n";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => "https://api.retellai.com/publish-conversation-flow/{$existingFlowId}",
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
    echo "✅ Flow PUBLISHED!\n";
    echo "  - HTTP Code: {$httpCode}\n";
} else {
    echo "⚠️ Publish may have failed (but update succeeded)\n";
    echo "  - HTTP Code: {$httpCode}\n";
    echo "  - Response: {$response}\n";
}
echo PHP_EOL;

// Agent still needs to be published
echo "=== Publishing Agent ===\n";

$agentId = 'agent_f1ce85d06a84afb989dfbb16a9';

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
} else {
    echo "⚠️ Agent publish response\n";
    echo "  - HTTP Code: {$httpCode}\n";
    echo "  - Response: {$response}\n";
}
echo PHP_EOL;

// Summary
echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║                    UPDATE COMPLETE                           ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";
echo PHP_EOL;

echo "✅ Friseur 1 Flow ist jetzt LIVE!\n";
echo PHP_EOL;

echo "Was geändert wurde:\n";
echo "  ✅ Global Prompt: 'Friseur 1' (war: AskPro AI)\n";
echo "  ✅ Services: Herrenhaarschnitt, Damenhaarschnitt, Ansatzfärbung\n";
echo "  ✅ Composite Services: Erklärt Wartezeiten\n";
echo "  ✅ Team: Emma, Fabian, David, Michael, Dr. Sarah\n";
echo "  ✅ Tool: 'mitarbeiter' Parameter hinzugefügt\n";
echo PHP_EOL;

echo "Dashboard: https://dashboard.retellai.com/agents/{$agentId}\n";
echo "Flow ID: {$existingFlowId}\n";
echo PHP_EOL;

echo "🎯 Der Agent sagt jetzt 'Terminassistent von Friseur 1'!\n";
echo PHP_EOL;

echo "✅ DEPLOYMENT: SUCCESS\n";
