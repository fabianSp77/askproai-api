<?php

/**
 * Update Retell Agent - Add call_id parameter to 4 tools
 *
 * FIX 2025-11-03: P1 Incident (call_bdcc364c) - Empty call_id Resolution
 * Task 0: Agent Config Fix (Automated via API)
 */

$agentId = 'agent_45daa54928c5768b52ba3db736';
$apiKey = 'key_6ff998ba48e842092e04a5455d19';

// Load current agent config from provided JSON
$agentConfigJson = file_get_contents('php://stdin');
$agentConfig = json_decode($agentConfigJson, true);

if (!$agentConfig) {
    die("❌ Error: Invalid JSON input\n");
}

echo "📋 Current Agent: {$agentConfig['agent_name']}\n";
echo "📋 Current Version: {$agentConfig['version']}\n";
echo "📋 Conversation Flow ID: {$agentConfig['conversationFlow']['conversation_flow_id']}\n\n";

// Tools that need call_id parameter
$toolsToUpdate = [
    'tool-v17-check-availability' => 'check_availability_v17',
    'tool-v17-book-appointment' => 'book_appointment_v17',
    'tool-cancel-appointment' => 'cancel_appointment_v4',
    'tool-reschedule-appointment' => 'reschedule_appointment_v4'
];

$updated = 0;

foreach ($agentConfig['conversationFlow']['tools'] as &$tool) {
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
        if (!in_array('call_id', $tool['parameters']['required'] ?? [])) {
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

// Update version metadata
$agentConfig['version_title'] = "V52 - P1 Fix: Added call_id to 4 tools (automated)";
$agentConfig['conversationFlow']['version'] = $agentConfig['version'] + 1;

// Prepare API request
$updatePayload = [
    'agent_name' => $agentConfig['agent_name'],
    'response_engine' => [
        'type' => 'conversation-flow',
        'conversation_flow' => $agentConfig['conversationFlow']
    ],
    'webhook_url' => $agentConfig['webhook_url'],
    'language' => $agentConfig['language'],
    'voice_id' => $agentConfig['voice_id'],
    'voice_temperature' => $agentConfig['voice_temperature'],
    'voice_speed' => $agentConfig['voice_speed'],
    'volume' => $agentConfig['volume'],
    'max_call_duration_ms' => $agentConfig['max_call_duration_ms'],
    'interruption_sensitivity' => $agentConfig['interruption_sensitivity'],
    'data_storage_setting' => $agentConfig['data_storage_setting']
];

echo "🚀 Updating agent via API...\n\n";

$ch = curl_init("https://api.retellai.com/update-agent/{$agentId}");
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
    echo "✅ SUCCESS! Agent updated\n";
    echo "📋 New Version: " . ($result['version'] ?? 'unknown') . "\n";
    echo "📋 Published: " . ($result['is_published'] ? 'YES' : 'NO (draft)') . "\n\n";

    // Save updated config for verification
    file_put_contents('/tmp/agent_updated.json', json_encode($result, JSON_PRETTY_PRINT));
    echo "💾 Updated config saved to: /tmp/agent_updated.json\n\n";

    echo "🎯 Next Steps:\n";
    echo "1. Publish the new version in Retell Dashboard\n";
    echo "2. Test call to verify call_id is populated\n";
    echo "3. Check Laravel logs for '✅ CANONICAL_CALL_ID: Resolved'\n";

    exit(0);
} else {
    echo "❌ ERROR: HTTP {$httpCode}\n";
    echo "Response: {$response}\n";
    exit(1);
}
