<?php
/**
 * List all flow versions and their publish status
 */

$apiKey = 'key_6ff998ba48e842092e04a5455d19';
$flowId = 'conversation_flow_a58405e3f67a';

echo "=== LIST ALL FLOW VERSIONS ===\n\n";

// Unfortunately Retell API doesn't have an endpoint to list all versions
// We have to list all agents and see which flow versions they reference

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

$agentId = 'agent_45daa54928c5768b52ba3db736';
$flowVersions = [];

echo "Agent versions and their flow versions:\n\n";
foreach ($agents as $agent) {
    if ($agent['agent_id'] !== $agentId) continue;
    if ($agent['version'] < 95) continue; // Only recent versions

    $agentVer = $agent['version'];
    $published = $agent['is_published'] ? '✅' : '❌';
    $flowVer = 'N/A';

    if (isset($agent['response_engine']['conversation_flow_version'])) {
        $flowVer = "V{$agent['response_engine']['conversation_flow_version']}";
    } elseif (isset($agent['response_engine']['conversation_flow_id'])) {
        $flowVer = 'NOT SET';
    }

    echo "Agent V{$agentVer}: {$published} Published | Flow: {$flowVer}\n";

    if ($flowVer !== 'N/A' && $flowVer !== 'NOT SET') {
        $flowVersions[$flowVer] = isset($flowVersions[$flowVer]) ? $flowVersions[$flowVer] : [];
        if ($agent['is_published']) {
            $flowVersions[$flowVer][] = "Agent V{$agentVer} (published)";
        }
    }
}

echo "\n=== Flow Versions Usage ===\n\n";
foreach ($flowVersions as $ver => $agents) {
    echo "{$ver}:\n";
    foreach ($agents as $a) {
        echo "  - Used by: {$a}\n";
    }
}

// Get current flow
echo "\n=== Current Flow ===\n";
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

echo "Current Version: V{$flow['version']}\n";
echo "Published: " . ($flow['is_published'] ? 'YES' : 'NO') . "\n";

echo "\n=== END LIST ===\n";
