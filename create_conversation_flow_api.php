<?php

$apiKey = 'key_6ff998ba48e842092e04a5455d19';
$baseUrl = 'https://api.retellai.com';

// Load the conversation flow
$flowData = json_decode(file_get_contents('public/askproai_conversation_flow_import.json'), true);

echo "=== CREATING CONVERSATION FLOW VIA API ===\n\n";
echo "Flow has:\n";
echo "  - Nodes: " . count($flowData['nodes']) . "\n";
echo "  - Tools: " . count($flowData['tools']) . "\n\n";

// Create the conversation flow
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => "$baseUrl/create-conversation-flow",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
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

if ($httpCode === 201 || $httpCode === 200) {
    $result = json_decode($response, true);
    echo "✅ SUCCESS!\n";
    echo "Conversation Flow ID: " . $result['conversation_flow_id'] . "\n";
    echo "Version: " . $result['version'] . "\n\n";

    // Save the ID for later use
    file_put_contents('conversation_flow_id.txt', $result['conversation_flow_id']);

    echo "Now update agent agent_616d645570ae613e421edb98e7 to use this flow.\n";
} else {
    echo "❌ ERROR!\n";
    echo "Response:\n";
    echo $response . "\n";

    // Pretty print the error
    $error = json_decode($response, true);
    if ($error) {
        echo "\nParsed Error:\n";
        print_r($error);
    }
}
