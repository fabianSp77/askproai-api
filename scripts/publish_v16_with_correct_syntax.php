<?php

/**
 * Publish Flow V16 and Agent V16 with Correct call_id Syntax
 */

$agentId = 'agent_45daa54928c5768b52ba3db736';
$flowId = 'conversation_flow_a58405e3f67a';
$apiKey = 'key_6ff998ba48e842092e04a5455d19';

echo "🚀 PUBLISHING V16 WITH CORRECT call_id SYNTAX\n";
echo str_repeat('=', 80) . "\n\n";

// ============================================================================
// 1. VERIFY CURRENT STATUS
// ============================================================================

echo "1. VERIFYING CURRENT STATUS\n";
echo str_repeat('-', 80) . "\n";

// Check Flow
$ch = curl_init("https://api.retellai.com/get-conversation-flow/{$flowId}");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer {$apiKey}",
    "Content-Type: application/json"
]);
$flowResponse = curl_exec($ch);
curl_close($ch);

$flow = json_decode($flowResponse, true);

echo "Flow Version: V{$flow['version']}\n";
echo "Flow Published: " . ($flow['is_published'] ? 'YES' : 'NO') . "\n";

// Verify correct syntax
$functionNodes = array_filter($flow['nodes'], fn($n) => $n['type'] === 'function');
$correctSyntax = true;

foreach ($functionNodes as $node) {
    $callId = $node['parameter_mapping']['call_id'] ?? '';
    if ($callId !== '{{call_id}}') {
        $correctSyntax = false;
        echo "❌ {$node['name']}: {$callId}\n";
    }
}

if (!$correctSyntax) {
    echo "\n❌ ERROR: Not all nodes have correct syntax!\n";
    exit(1);
}

echo "✅ All nodes use correct syntax: {{call_id}}\n\n";

// Check Agent
$ch = curl_init("https://api.retellai.com/get-agent/{$agentId}");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer {$apiKey}",
    "Content-Type: application/json"
]);
$agentResponse = curl_exec($ch);
curl_close($ch);

$agent = json_decode($agentResponse, true);

echo "Agent Version: V{$agent['version']}\n";
echo "Agent Published: " . ($agent['is_published'] ? 'YES' : 'NO') . "\n";
echo "Agent using Flow: V{$agent['response_engine']['version']}\n\n";

// ============================================================================
// 2. PUBLISH FLOW
// ============================================================================

if (!$flow['is_published']) {
    echo "2. PUBLISHING FLOW V{$flow['version']}\n";
    echo str_repeat('-', 80) . "\n";

    $ch = curl_init("https://api.retellai.com/publish-conversation-flow/{$flowId}");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer {$apiKey}",
        "Content-Type: application/json"
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200) {
        echo "✅ Flow V{$flow['version']} published successfully\n\n";
    } else {
        echo "❌ Failed to publish flow (HTTP {$httpCode})\n";
        echo "Response: {$response}\n";
        exit(1);
    }
} else {
    echo "2. Flow V{$flow['version']} already published ✅\n\n";
}

// ============================================================================
// 3. PUBLISH AGENT
// ============================================================================

if (!$agent['is_published']) {
    echo "3. PUBLISHING AGENT V{$agent['version']}\n";
    echo str_repeat('-', 80) . "\n";

    $ch = curl_init("https://api.retellai.com/publish-agent/{$agentId}");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer {$apiKey}",
        "Content-Type: application/json"
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200) {
        echo "✅ Agent V{$agent['version']} published successfully\n\n";
    } else {
        echo "❌ Failed to publish agent (HTTP {$httpCode})\n";
        echo "Response: {$response}\n";
        exit(1);
    }
} else {
    echo "3. Agent V{$agent['version']} already published ✅\n\n";
}

// ============================================================================
// 4. FINAL VERIFICATION
// ============================================================================

echo str_repeat('=', 80) . "\n";
echo "FINAL VERIFICATION\n";
echo str_repeat('=', 80) . "\n\n";

// Re-fetch agent to confirm
$ch = curl_init("https://api.retellai.com/get-agent/{$agentId}");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer {$apiKey}",
    "Content-Type: application/json"
]);
$agentResponse = curl_exec($ch);
curl_close($ch);

$publishedAgent = json_decode($agentResponse, true);

echo "✅ Agent V{$publishedAgent['version']} is published: " . ($publishedAgent['is_published'] ? 'YES' : 'NO') . "\n";
echo "✅ Using Flow V{$publishedAgent['response_engine']['version']}\n";
echo "✅ All parameter mappings use: {{call_id}}\n\n";

echo str_repeat('=', 80) . "\n";
echo "🎉 SUCCESS! V{$publishedAgent['version']} IS LIVE WITH CORRECT SYNTAX!\n";
echo str_repeat('=', 80) . "\n\n";

echo "READY FOR TESTING!\n\n";

echo "TEST SCENARIO:\n";
echo "   Call your agent and say:\n";
echo "   \"Ich möchte einen Herrenhaarschnitt morgen um 16 Uhr buchen.\n";
echo "    Mein Name ist Hans Schuster.\"\n\n";

echo "EXPECTED BEHAVIOR:\n";
echo "   ✅ Agent collects: name, service, date, time\n";
echo "   ✅ Agent calls check_availability\n";
echo "   ✅ call_id parameter = \"call_xxx\" (NOT empty!)\n";
echo "   ✅ Backend successfully identifies call context\n";
echo "   ✅ Availability check succeeds\n";
echo "   ✅ Appointment booking flow completes\n\n";

echo "MONITOR LOGS:\n";
echo "   tail -f storage/logs/laravel.log | grep -E 'CANONICAL_CALL_ID|check_availability'\n\n";

echo "LOOK FOR:\n";
echo "   ✅ CANONICAL_CALL_ID: call_<real-id> (not empty, not 'call_1')\n";
echo "   ✅ Function call has call_id parameter populated\n";
echo "   ❌ NO MORE: 'Call context not available' errors\n\n";
