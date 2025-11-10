<?php

/**
 * Update Retell Conversation Flow - Add call_id parameter to 4 tools
 *
 * FIX 2025-11-03: P1 Incident (call_bdcc364c) - Empty call_id Resolution
 * Task 0: Conversation Flow Fix (Automated via API)
 */

$conversationFlowId = 'conversation_flow_1607b81c8f93';
$apiKey = 'key_6ff998ba48e842092e04a5455d19';

// Load current agent config from provided JSON
$agentConfigJson = file_get_contents('php://stdin');
$agentConfig = json_decode($agentConfigJson, true);

if (!$agentConfig || !isset($agentConfig['conversationFlow'])) {
    die("❌ Error: Invalid JSON input or missing conversationFlow\n");
}

$conversationFlow = $agentConfig['conversationFlow'];

echo "📋 Conversation Flow ID: {$conversationFlow['conversation_flow_id']}\n";
echo "📋 Current Version: {$conversationFlow['version']}\n";
echo "📋 Tools Count: " . count($conversationFlow['tools']) . "\n\n";

// Tools that need call_id parameter
$toolsToUpdate = [
    'tool-v17-check-availability' => 'check_availability_v17',
    'tool-v17-book-appointment' => 'book_appointment_v17',
    'tool-cancel-appointment' => 'cancel_appointment_v4',
    'tool-reschedule-appointment' => 'reschedule_appointment_v4'
];

$updated = 0;

foreach ($conversationFlow['tools'] as &$tool) {
    // Fix: Ensure headers and query_params are objects, not arrays
    if (isset($tool['headers']) && is_array($tool['headers']) && empty($tool['headers'])) {
        $tool['headers'] = new stdClass();
    }
    if (isset($tool['query_params']) && is_array($tool['query_params']) && empty($tool['query_params'])) {
        $tool['query_params'] = new stdClass();
    }
    if (isset($tool['response_variables']) && is_array($tool['response_variables']) && empty($tool['response_variables'])) {
        $tool['response_variables'] = new stdClass();
    }

    if (isset($toolsToUpdate[$tool['tool_id']])) {
        $toolName = $toolsToUpdate[$tool['tool_id']];

        // Check if call_id already exists
        if (isset($tool['parameters']['properties']['call_id'])) {
            echo "✅ {$toolName}: call_id already exists\n";
            continue;
        }

        // Add call_id parameter
        $tool['parameters']['properties']['call_id'] = [
            'type' => 'string',
            'description' => 'Unique Retell call identifier for tracking and debugging'
        ];

        // Add to required array if not present
        if (!isset($tool['parameters']['required'])) {
            $tool['parameters']['required'] = [];
        }
        if (!in_array('call_id', $tool['parameters']['required'])) {
            $tool['parameters']['required'][] = 'call_id';
        }

        echo "✨ {$toolName}: call_id parameter ADDED\n";
        $updated++;
    }
}

echo "\n📊 Summary: {$updated} tools updated\n\n";

if ($updated === 0) {
    echo "ℹ️  No updates needed - all tools already have call_id parameter\n";
    exit(0);
}

// Prepare API request - send only the tools array
$updatePayload = [
    'tools' => $conversationFlow['tools']
];

echo "🚀 Updating conversation flow via API...\n\n";

$ch = curl_init("https://api.retellai.com/update-conversation-flow/{$conversationFlowId}");
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PATCH");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer {$apiKey}",
    "Content-Type: application/json"
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($updatePayload));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200 || $httpCode === 201) {
    $result = json_decode($response, true);
    echo "✅ SUCCESS! Conversation flow updated\n";
    echo "📋 New Version: " . ($result['version'] ?? 'unknown') . "\n\n";

    // Save updated config for verification
    file_put_contents('/tmp/conversation_flow_updated.json', json_encode($result, JSON_PRETTY_PRINT));
    echo "💾 Updated flow saved to: /tmp/conversation_flow_updated.json\n\n";

    echo "🎯 Next Steps:\n";
    echo "1. The agent will automatically use the updated conversation flow\n";
    echo "2. Test call to verify call_id is populated\n";
    echo "3. Check Laravel logs for '✅ CANONICAL_CALL_ID: Resolved'\n";

    exit(0);
} else {
    echo "❌ ERROR: HTTP {$httpCode}\n";
    echo "Response: {$response}\n";
    exit(1);
}
