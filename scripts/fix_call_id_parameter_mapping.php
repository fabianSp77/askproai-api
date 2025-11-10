<?php

/**
 * Fix call_id Parameter Mapping in Conversation Flow
 *
 * ISSUE: {{call_id}} resolves to empty string
 * FIX: Change to {{call.call_id}} to access from call object
 * SOURCE: Retell documentation - system variables accessed via dot notation
 */

$flowId = 'conversation_flow_a58405e3f67a';
$apiKey = 'key_6ff998ba48e842092e04a5455d19';

echo "🔍 Fetching conversation flow V13...\n\n";

// GET current conversation flow
$ch = curl_init("https://api.retellai.com/get-conversation-flow/{$flowId}");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer {$apiKey}",
    "Content-Type: application/json"
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    die("❌ Failed to fetch flow (HTTP {$httpCode})\n{$response}\n");
}

$flow = json_decode($response, true);

echo "📋 Flow ID: {$flow['conversation_flow_id']}\n";
echo "📋 Version: {$flow['version']}\n";
echo "📋 Nodes: " . count($flow['nodes']) . "\n\n";

$updated = 0;
$functionNodes = [];

// Find all function nodes with call_id parameter mapping
foreach ($flow['nodes'] as $index => &$node) {
    if ($node['type'] === 'function' && isset($node['parameter_mapping'])) {

        $nodeName = $node['name'] ?? "Node {$index}";

        if (isset($node['parameter_mapping']['call_id'])) {
            $currentMapping = $node['parameter_mapping']['call_id'];

            echo "📦 {$nodeName}\n";
            echo "   Current: {$currentMapping}\n";

            // Fix the mapping
            if ($currentMapping !== '{{call.call_id}}') {
                $node['parameter_mapping']['call_id'] = '{{call.call_id}}';
                echo "   Updated: {{call.call_id}} ✅\n\n";
                $updated++;
                $functionNodes[] = $nodeName;
            } else {
                echo "   Already correct ✅\n\n";
            }
        } else {
            echo "⚠️  {$nodeName} - No call_id mapping found\n\n";
        }
    }
}

if ($updated === 0) {
    echo "✅ All parameter mappings are already correct!\n";
    exit(0);
}

echo "📊 Summary: {$updated} function nodes updated\n\n";
echo "📋 Updated nodes:\n";
foreach ($functionNodes as $nodeName) {
    echo "  ✅ {$nodeName}\n";
}
echo "\n";

// Update conversation flow
$updatePayload = [
    'nodes' => $flow['nodes']
];

echo "🚀 Updating conversation flow...\n\n";

$ch = curl_init("https://api.retellai.com/update-conversation-flow/{$flowId}");
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
    echo "✅ SUCCESS!\n";
    echo "📋 New Version: {$result['version']}\n";
    echo "📋 Is Published: " . ($result['is_published'] ? 'YES' : 'NO') . "\n\n";

    file_put_contents('/tmp/flow_call_id_fixed.json', json_encode($result, JSON_PRETTY_PRINT));
    echo "💾 Saved to: /tmp/flow_call_id_fixed.json\n\n";

    echo "🎯 Fixed Parameter Mapping:\n";
    echo "   OLD: {{call_id}} → Empty string ❌\n";
    echo "   NEW: {{call.call_id}} → Actual call ID ✅\n\n";

    if (!$result['is_published']) {
        echo "⚠️  Flow V{$result['version']} is DRAFT\n";
        echo "   Agent muss diese Version verwenden und publishen:\n";
        echo "   1. Run: php scripts/publish_agent_v13.php\n";
        echo "   2. OR manually publish in dashboard\n\n";
    } else {
        echo "✅ Flow is PUBLISHED and ready for testing!\n\n";
    }

    echo "🧪 Next Steps:\n";
    echo "1. Ensure Agent uses this flow version\n";
    echo "2. Publish Agent if needed\n";
    echo "3. Test call durchführen\n";
    echo "4. Verify call_id is populated: tail -f storage/logs/laravel.log | grep CANONICAL_CALL_ID\n";

    exit(0);
} else {
    echo "❌ ERROR: HTTP {$httpCode}\n";
    echo $response . "\n";
    exit(1);
}
