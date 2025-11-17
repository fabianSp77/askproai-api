#!/usr/bin/env php
<?php

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$apiKey = $_ENV['RETELLAI_API_KEY'] ?? $_ENV['RETELL_TOKEN'];
$baseUrl = 'https://api.retellai.com';
$agentId = 'agent_c1d8dea0445f375857a55ffd61';
$v109FlowId = 'conversation_flow_a58405e3f67a';

echo "\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "  UPDATE AGENT TO V109 - FIXED\n";
echo "═══════════════════════════════════════════════════════════\n\n";

echo "Agent ID: $agentId\n";
echo "Target Flow: $v109FlowId\n\n";

// Step 1: Get current agent configuration
echo "Step 1: Getting current agent configuration...\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "$baseUrl/get-agent/$agentId");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $apiKey"
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    echo "❌ Failed to fetch agent\n";
    echo "HTTP Status: $httpCode\n";
    exit(1);
}

$agent = json_decode($response, true);
echo "✅ Current configuration retrieved\n";
echo "   Current Flow: " . ($agent['response_engine']['conversation_flow_id'] ?? 'N/A') . "\n\n";

// Step 2: Update agent configuration
echo "Step 2: Updating agent to use V109 flow...\n";

// Prepare update payload
$updatePayload = [
    'agent_name' => 'Friseur 1 Agent V109 - Parameter Fix',  // Update name
    'response_engine' => [
        'type' => 'conversation-flow',
        'conversation_flow_id' => $v109FlowId  // Update to V109 flow
    ],
    'language' => $agent['language'] ?? 'de-DE',
    'voice_id' => $agent['voice_id'] ?? '11labs-Adrian',
    'max_call_duration_ms' => $agent['max_call_duration_ms'] ?? 3600000,
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "$baseUrl/update-agent/$agentId");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $apiKey",
    "Content-Type: application/json"
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($updatePayload));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    echo "❌ Failed to update agent\n";
    echo "HTTP Status: $httpCode\n";
    echo "Response: $response\n";
    exit(1);
}

$updatedAgent = json_decode($response, true);
echo "✅ Agent updated successfully\n";
echo "   New Flow: " . ($updatedAgent['response_engine']['conversation_flow_id'] ?? 'N/A') . "\n";
echo "   New Name: " . ($updatedAgent['agent_name'] ?? 'N/A') . "\n\n";

// Step 3: Publish the agent
echo "Step 3: Publishing agent...\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "$baseUrl/publish-agent/$agentId");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $apiKey",
    "Content-Type: application/json"
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    echo "❌ Failed to publish agent\n";
    echo "HTTP Status: $httpCode\n";
    echo "Response: $response\n";
    echo "\n⚠️  Agent was updated but NOT published!\n";
    echo "   Phone calls might still fail until published\n";
    exit(1);
}

echo "✅ Agent published successfully\n\n";

// Step 4: Verify the changes
echo "Step 4: Verifying changes...\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "$baseUrl/get-agent/$agentId");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $apiKey"
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    $verifyAgent = json_decode($response, true);

    echo "\n";
    echo "═══════════════════════════════════════════════════════════\n";
    echo "  VERIFICATION RESULTS\n";
    echo "═══════════════════════════════════════════════════════════\n\n";

    echo "Agent Name: " . ($verifyAgent['agent_name'] ?? 'N/A') . "\n";
    echo "Flow ID: " . ($verifyAgent['response_engine']['conversation_flow_id'] ?? 'N/A') . "\n";
    echo "Published: " . ($verifyAgent['is_published'] ? 'YES' : 'NO') . "\n\n";

    $hasCorrectFlow = isset($verifyAgent['response_engine']['conversation_flow_id']) &&
                     $verifyAgent['response_engine']['conversation_flow_id'] === $v109FlowId;
    $isPublished = $verifyAgent['is_published'] ?? false;

    if ($hasCorrectFlow && $isPublished) {
        echo "═══════════════════════════════════════════════════════════\n";
        echo "  ✅ SUCCESS! AGENT UPDATED AND PUBLISHED\n";
        echo "═══════════════════════════════════════════════════════════\n\n";

        echo "The agent is now using V109 with the parameter fix!\n\n";

        echo "Next steps:\n";
        echo "1. Make a test call to +493033081738\n";
        echo "2. Test: 'Herrenhaarschnitt morgen um 10 Uhr'\n";
        echo "3. Verify booking succeeds\n";

    } else {
        echo "⚠️  PARTIAL SUCCESS\n";
        if (!$hasCorrectFlow) {
            echo "  ❌ Flow ID is wrong\n";
        }
        if (!$isPublished) {
            echo "  ❌ Agent is not published\n";
        }
    }
}

echo "\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "  DONE\n";
echo "═══════════════════════════════════════════════════════════\n\n";
