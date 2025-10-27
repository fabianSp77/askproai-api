<?php

$apiKey = 'key_6ff998ba48e842092e04a5455d19';
$baseUrl = 'https://api.retellai.com';
$agentId = 'agent_616d645570ae613e421edb98e7';

echo "=== PUBLISHING AGENT WITH V17 ===\n\n";
echo "Agent ID: $agentId\n";

// Update the agent to publish the conversation flow
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => "$baseUrl/update-agent/$agentId",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST => 'PATCH',
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json'
    ],
    CURLOPT_POSTFIELDS => json_encode([])  // Empty PATCH to trigger republish
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status: $httpCode\n\n";

if ($httpCode === 200) {
    $result = json_decode($response, true);
    echo "✅ AGENT PUBLISHED SUCCESSFULLY!\n\n";
    echo "Agent ID: {$result['agent_id']}\n";
    echo "Agent Version: {$result['agent_version']}\n";
    echo "Last Update: {$result['last_modification_timestamp']}\n\n";
    echo "🚀 V17 is now LIVE!\n";
    echo "⏳ Wait 15 minutes for CDN propagation before testing\n";
} else {
    echo "❌ PUBLISH FAILED!\n";
    echo "Response:\n";
    echo $response . "\n\n";

    // Pretty print the error
    $error = json_decode($response, true);
    if ($error) {
        echo "Parsed Error:\n";
        print_r($error);
    }
}
