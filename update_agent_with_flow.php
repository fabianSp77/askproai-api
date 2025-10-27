<?php

$apiKey = 'key_6ff998ba48e842092e04a5455d19';
$baseUrl = 'https://api.retellai.com';
$agentId = 'agent_616d645570ae613e421edb98e7';
$conversationFlowId = trim(file_get_contents('conversation_flow_id.txt'));

echo "=== UPDATING AGENT WITH CONVERSATION FLOW ===\n\n";
echo "Agent ID: $agentId\n";
echo "Conversation Flow ID: $conversationFlowId\n\n";

// Update the agent
$updateData = [
    'response_engine' => [
        'type' => 'conversation-flow',
        'conversation_flow_id' => $conversationFlowId,
        'version' => 0
    ]
];

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => "$baseUrl/update-agent/$agentId",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST => 'PATCH',
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json'
    ],
    CURLOPT_POSTFIELDS => json_encode($updateData)
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status: $httpCode\n\n";

if ($httpCode === 200) {
    echo "✅ AGENT UPDATED SUCCESSFULLY!\n\n";
    echo "Your agent now uses the conversation flow:\n";
    echo "https://dashboard.retellai.com/agents/$agentId\n\n";

    $result = json_decode($response, true);
    echo "Response:\n";
    print_r($result);
} else {
    echo "❌ ERROR!\n";
    echo "Response:\n";
    echo $response . "\n";
}
