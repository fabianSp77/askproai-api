<?php

/**
 * Fix CORRECT Conversation Flow - conversation_flow_a58405e3f67a
 *
 * FIX 2025-11-03: P1 Incident - Update the ACTUAL flow used by the agent
 */

$conversationFlowId = 'conversation_flow_a58405e3f67a'; // CORRECT ID from agent
$apiKey = 'key_6ff998ba48e842092e04a5455d19';

echo "🔍 Fetching CORRECT conversation flow (conversation_flow_a58405e3f67a)...\n\n";

// GET current conversation flow
$ch = curl_init("https://api.retellai.com/get-conversation-flow/{$conversationFlowId}");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer {$apiKey}",
    "Content-Type: application/json"
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    die("❌ ERROR: Failed to fetch conversation flow (HTTP {$httpCode})\nResponse: {$response}\n");
}

$conversationFlow = json_decode($response, true);

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
    // Fix: Convert empty arrays to objects for API compatibility
    if (isset($tool['headers']) && is_array($tool['headers']) && empty($tool['headers'])) {
        $tool['headers'] = new stdClass();
    }
    if (isset($tool['query_params']) && is_array($tool['query_params']) && empty($tool['query_params'])) {
        $tool['query_params'] = new stdClass();
    }
    if (isset($tool['response_variables']) && is_array($tool['response_variables']) && empty($tool['response_variables'])) {
        $tool['response_variables'] = new stdClass();
    }
    if (isset($tool['user_dtmf_options']) && is_array($tool['user_dtmf_options']) && empty($tool['user_dtmf_options'])) {
        $tool['user_dtmf_options'] = new stdClass();
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

echo "🚀 Updating CORRECT conversation flow via API...\n\n";

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
    file_put_contents('/tmp/correct_flow_updated.json', json_encode($result, JSON_PRETTY_PRINT));
    echo "💾 Updated flow saved to: /tmp/correct_flow_updated.json\n\n";

    echo "🎯 Next Steps:\n";
    echo "1. Publish the agent in Retell Dashboard (currently DRAFT)\n";
    echo "2. Test call to verify call_id is populated\n";
    echo "3. Check Laravel logs for '✅ CANONICAL_CALL_ID: Resolved'\n\n";

    echo "📋 Updated Tools:\n";
    foreach ($result['tools'] as $tool) {
        if (isset($toolsToUpdate[$tool['tool_id']])) {
            $hasCallId = isset($tool['parameters']['properties']['call_id']) ? '✅' : '❌';
            echo "  {$hasCallId} {$tool['name']}\n";
        }
    }

    exit(0);
} else {
    echo "❌ ERROR: HTTP {$httpCode}\n";
    echo "Response: {$response}\n";
    exit(1);
}
