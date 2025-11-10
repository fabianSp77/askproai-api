<?php
/**
 * Check Agent + Flow Published Status
 * Comprehensive verification
 */

$apiKey = 'key_6ff998ba48e842092e04a5455d19';
$agentId = 'agent_9a8202a740cd3120d96fcfda1e';
$flowId = 'conversation_flow_a58405e3f67a';

echo "=== COMPREHENSIVE PUBLISH STATUS CHECK ===\n\n";

// 1. Check Agent Status
echo "1. Checking Agent Status...\n";
$ch = curl_init("https://api.retellai.com/get-agent/{$agentId}");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer {$apiKey}",
        "Content-Type: application/json"
    ]
]);

$agentResponse = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    echo "❌ Failed to fetch agent. HTTP {$httpCode}\n\n";
} else {
    $agent = json_decode($agentResponse, true);

    echo "Agent ID: {$agent['agent_id']}\n";
    echo "Agent Name: " . ($agent['agent_name'] ?? 'N/A') . "\n";
    echo "Response Model: " . ($agent['response_engine']['llm_model'] ?? 'N/A') . "\n";

    // Check conversation flow reference
    if (isset($agent['conversation_flow_id'])) {
        echo "Conversation Flow ID: {$agent['conversation_flow_id']}\n";
        if ($agent['conversation_flow_id'] === $flowId) {
            echo "✅ Agent uses correct flow: {$flowId}\n";
        } else {
            echo "❌ WARNING: Agent uses different flow: {$agent['conversation_flow_id']}\n";
        }
    }

    echo "\n";
}

// 2. Check Flow Status
echo "2. Checking Flow Status...\n";
$ch = curl_init("https://api.retellai.com/get-conversation-flow/{$flowId}");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer {$apiKey}",
        "Content-Type: application/json"
    ]
]);

$flowResponse = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    echo "❌ Failed to fetch flow. HTTP {$httpCode}\n\n";
} else {
    $flow = json_decode($flowResponse, true);

    echo "Flow ID: {$flow['conversation_flow_id']}\n";
    echo "Flow Version: {$flow['version']}\n";
    echo "Flow Published: " . ($flow['is_published'] ? '✅ YES' : '❌ NO') . "\n";

    if (!$flow['is_published']) {
        echo "\n⚠️ CRITICAL: Flow is NOT published!\n";
        echo "   This means the Agent is NOT using this version yet.\n";
        echo "   You MUST click 'Publish' in the Retell Dashboard!\n";
    }

    echo "\n";
}

// 3. List all versions of the flow
echo "3. Checking all Flow versions...\n";
$ch = curl_init("https://api.retellai.com/list-conversation-flows");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer {$apiKey}",
        "Content-Type: application/json"
    ]
]);

$listResponse = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    echo "❌ Failed to list flows. HTTP {$httpCode}\n\n";
} else {
    $flowsList = json_decode($listResponse, true);

    // Find our flow
    $ourFlow = null;
    foreach ($flowsList as $f) {
        if ($f['conversation_flow_id'] === $flowId) {
            $ourFlow = $f;
            break;
        }
    }

    if ($ourFlow) {
        echo "Found flow in list:\n";
        echo "  ID: {$ourFlow['conversation_flow_id']}\n";
        echo "  Version: " . ($ourFlow['version'] ?? 'N/A') . "\n";
        echo "  Published: " . (($ourFlow['is_published'] ?? false) ? '✅ YES' : '❌ NO') . "\n";
        echo "  Last Modified: " . ($ourFlow['last_modification_timestamp'] ?? 'N/A') . "\n";
    } else {
        echo "❌ Flow not found in list\n";
    }

    echo "\n";
}

// 4. Final Summary
echo "=== SUMMARY ===\n\n";

$flowPublished = $flow['is_published'] ?? false;
$flowVersion = $flow['version'] ?? 'unknown';

if ($flowPublished) {
    echo "✅ Flow V{$flowVersion} IS PUBLISHED - Ready for testing!\n\n";
    echo "Next: Make test call and verify:\n";
    echo "1. Agent does NOT read instructions aloud\n";
    echo "2. Smart 3-case availability flow works\n";
} else {
    echo "❌ Flow V{$flowVersion} IS NOT PUBLISHED!\n\n";
    echo "ACTION REQUIRED:\n";
    echo "1. Go to https://app.retellai.com/\n";
    echo "2. Navigate to Conversation Flows\n";
    echo "3. Find flow: {$flowId}\n";
    echo "4. Click the 'Publish' button\n";
    echo "5. Verify status changes to 'Published'\n";
    echo "6. Then run this script again to verify\n";
}

echo "\n=== END CHECK ===\n";
