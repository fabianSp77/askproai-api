<?php
/**
 * Check which flow version is published
 */

$apiKey = 'key_6ff998ba48e842092e04a5455d19';
$agentId = 'agent_45daa54928c5768b52ba3db736';

echo "=== CHECK PUBLISHED FLOW VERSION ===\n\n";

// Get agent details
$ch = curl_init("https://api.retellai.com/get-agent/{$agentId}");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer {$apiKey}",
        "Content-Type: application/json"
    ]
]);

$response = curl_exec($ch);
$agent = json_decode($response, true);
curl_close($ch);

echo "Agent: {$agent['agent_name']}\n";
echo "Agent Version: {$agent['version']}\n";
echo "Response Engine: {$agent['response_engine']['type']}\n";

if ($agent['response_engine']['type'] === 'conversation-flow') {
    $flowId = $agent['response_engine']['conversation_flow_id'];
    echo "Flow ID: {$flowId}\n";
    echo "Flow Version in Agent: " . ($agent['response_engine']['conversation_flow_version'] ?? 'NOT SET') . "\n\n";

    // Get flow details
    echo "Fetching flow details...\n";
    $ch = curl_init("https://api.retellai.com/get-conversation-flow/{$flowId}");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer {$apiKey}",
            "Content-Type: application/json"
        ]
    ]);

    $response = curl_exec($ch);
    $flow = json_decode($response, true);
    curl_close($ch);

    echo "Current Flow Version: V{$flow['version']}\n";
    echo "Published: " . ($flow['is_published'] ? 'YES' : 'NO') . "\n\n";

    // List all versions via agent list
    echo "Checking all agent versions...\n";
    $ch = curl_init("https://api.retellai.com/list-agents");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer {$apiKey}",
            "Content-Type: application/json"
        ]
    ]);

    $response = curl_exec($ch);
    $agents = json_decode($response, true);
    curl_close($ch);

    echo "\nAgent Versions:\n";
    foreach ($agents as $a) {
        if ($a['agent_id'] === $agentId && $a['version'] >= 99) {
            echo "  Version {$a['version']}: ";
            echo ($a['is_published'] ? '✅ Published' : '❌ NOT Published');

            if (isset($a['response_engine']['conversation_flow_version'])) {
                echo " (Flow V{$a['response_engine']['conversation_flow_version']})";
            }
            echo "\n";
        }
    }
}

echo "\n=== END CHECK ===\n";
