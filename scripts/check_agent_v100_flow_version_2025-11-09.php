<?php
/**
 * Check which flow version Agent V100 uses
 */

$apiKey = 'key_6ff998ba48e842092e04a5455d19';

echo "=== CHECK AGENT V100 FLOW VERSION ===\n\n";

// List all agents to find V100
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

$agentV100 = null;
foreach ($agents as $agent) {
    if ($agent['agent_id'] === 'agent_45daa54928c5768b52ba3db736' && $agent['version'] === 100) {
        $agentV100 = $agent;
        break;
    }
}

if (!$agentV100) {
    die("❌ Agent V100 not found!\n");
}

echo "Agent V100 Details:\n";
echo "  Name: {$agentV100['agent_name']}\n";
echo "  Published: " . ($agentV100['is_published'] ? 'YES' : 'NO') . "\n";
echo "  Response Engine: {$agentV100['response_engine']['type']}\n";

if ($agentV100['response_engine']['type'] === 'conversation-flow') {
    $flowId = $agentV100['response_engine']['conversation_flow_id'];
    $flowVersion = $agentV100['response_engine']['conversation_flow_version'] ?? 'NOT SET';

    echo "  Flow ID: {$flowId}\n";
    echo "  Flow Version: {$flowVersion}\n\n";

    // Get that specific flow version details
    if ($flowVersion !== 'NOT SET' && is_numeric($flowVersion)) {
        echo "Fetching Flow V{$flowVersion} details...\n";

        // Unfortunately Retell doesn't have API to get specific version
        // So we get current and check
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

        if ($flow['version'] == $flowVersion) {
            echo "\n✅ Agent V100 uses CURRENT Flow V{$flowVersion}\n\n";

            // Check tool parameter_mapping
            foreach ($flow['tools'] as $tool) {
                if ($tool['name'] === 'get_current_context') {
                    echo "Tool: get_current_context\n";
                    $mapping = $tool['parameter_mapping']['call_id'] ?? 'MISSING';
                    echo "  parameter_mapping['call_id']: {$mapping}\n";

                    if ($mapping === '{{call_id}}') {
                        echo "  ✅ CORRECT\n";
                    } else {
                        echo "  ❌ WRONG (should be {{call_id}})\n";
                    }
                    break;
                }
            }
        } else {
            echo "\n⚠️  Agent V100 references Flow V{$flowVersion} but current is V{$flow['version']}\n";
            echo "Cannot fetch old flow version via API\n";
        }
    }
}

echo "\n=== END CHECK ===\n";
