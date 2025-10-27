<?php

$apiKey = 'key_6ff998ba48e842092e04a5455d19';
$baseUrl = 'https://api.retellai.com';
$flowId = 'conversation_flow_da76e7c6f3ba';

echo "=== DEPLOYING V17 TO RETELL ===\n\n";
echo "Flow ID: $flowId\n";

// Load V17 flow data
$flowData = json_decode(file_get_contents('public/askproai_state_of_the_art_flow_2025_V17.json'), true);

echo "V17 Flow Data:\n";
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
    echo "✅ V17 DEPLOYED SUCCESSFULLY!\n\n";
    echo "Flow ID: {$result['conversation_flow_id']}\n";
    echo "Version: {$result['version']}\n\n";
    echo "View in dashboard:\n";
    echo "https://dashboard.retellai.com/conversation-flow/$flowId\n";
} else {
    echo "❌ DEPLOYMENT FAILED!\n";
    echo "Response:\n";
    echo $response . "\n\n";

    // Pretty print the error
    $error = json_decode($response, true);
    if ($error) {
        echo "Parsed Error:\n";
        print_r($error);
    }
}
