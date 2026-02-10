<?php

$apiKey = getenv('RETELL_TOKEN') ?: die("ERROR: RETELL_TOKEN not set\n");
$baseUrl = 'https://api.retellai.com';
$flowId = 'conversation_flow_da76e7c6f3ba';

echo "=== UPDATING CONVERSATION FLOW ===\n\n";
echo "Flow ID: $flowId\n";

// Load our complete flow data
$flowData = json_decode(file_get_contents('public/askproai_conversation_flow_import.json'), true);

echo "New Flow Data:\n";
echo "  - Nodes: " . count($flowData['nodes']) . "\n";
echo "  - Tools: " . count($flowData['tools']) . "\n\n";

// Update the conversation flow
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => "$baseUrl/update-conversation-flow/$flowId",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST => 'PATCH',
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json'
    ],
    CURLOPT_POSTFIELDS => json_encode($flowData)
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status: $httpCode\n\n";

if ($httpCode === 200) {
    $result = json_decode($response, true);
    echo "✅ CONVERSATION FLOW UPDATED SUCCESSFULLY!\n\n";
    echo "Flow ID: {$result['conversation_flow_id']}\n";
    echo "Version: {$result['version']}\n\n";
    echo "View in dashboard:\n";
    echo "https://dashboard.retellai.com/conversation-flow/$flowId\n";
} else {
    echo "❌ ERROR!\n";
    echo "Response:\n";
    echo $response . "\n\n";

    // Pretty print the error
    $error = json_decode($response, true);
    if ($error) {
        echo "Parsed Error:\n";
        print_r($error);
    }
}
