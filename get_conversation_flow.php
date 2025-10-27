<?php

$apiKey = 'key_6ff998ba48e842092e04a5455d19';
$baseUrl = 'https://api.retellai.com';
$flowId = 'conversation_flow_da76e7c6f3ba';

echo "=== RETRIEVING CONVERSATION FLOW ===\n\n";
echo "Flow ID: $flowId\n\n";

// Get the conversation flow
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => "$baseUrl/get-conversation-flow/$flowId",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json'
    ]
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status: $httpCode\n\n";

if ($httpCode === 200) {
    $flow = json_decode($response, true);

    echo "✅ FLOW RETRIEVED!\n\n";
    echo "Statistics:\n";
    echo "  - Nodes: " . count($flow['nodes']) . "\n";
    echo "  - Tools: " . (isset($flow['tools']) ? count($flow['tools']) : 0) . "\n";
    echo "  - Version: " . ($flow['version'] ?? 'N/A') . "\n\n";

    // Save the flow
    file_put_contents(
        'public/existing_conversation_flow.json',
        json_encode($flow, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
    );

    echo "Flow saved to: public/existing_conversation_flow.json\n\n";

    // List nodes
    echo "Nodes:\n";
    foreach ($flow['nodes'] as $node) {
        $type = $node['type'] ?? 'unknown';
        $id = $node['id'] ?? 'no-id';
        echo "  - $id (type: $type)\n";
    }

    // List tools
    if (isset($flow['tools']) && count($flow['tools']) > 0) {
        echo "\nTools:\n";
        foreach ($flow['tools'] as $tool) {
            echo "  - {$tool['name']} (ID: {$tool['tool_id']})\n";
        }
    }

} else {
    echo "❌ ERROR!\n";
    echo "Response:\n";
    echo $response . "\n";
}
