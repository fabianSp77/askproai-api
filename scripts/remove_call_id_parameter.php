<?php

/**
 * Remove call_id Parameter from Agent V16
 *
 * Since Conversation Flows cannot provide call_id as a dynamic variable,
 * and the backend now extracts it directly from the webhook root,
 * we should remove the call_id parameter mapping from all function nodes.
 */

$flowId = 'conversation_flow_a58405e3f67a';
$apiKey = 'key_6ff998ba48e842092e04a5455d19';

echo "🔧 REMOVING call_id PARAMETER FROM AGENT\n";
echo str_repeat('=', 80) . "\n\n";

// Get current flow
$ch = curl_init("https://api.retellai.com/get-conversation-flow/{$flowId}");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer {$apiKey}",
    "Content-Type: application/json"
]);
$response = curl_exec($ch);
curl_close($ch);

$flow = json_decode($response, true);

echo "Current Flow Version: V{$flow['version']}\n";
echo "Is Published: " . ($flow['is_published'] ? 'YES' : 'NO') . "\n\n";

echo "RATIONALE:\n";
echo str_repeat('-', 80) . "\n";
echo "1. ❌ Conversation Flows cannot access {{call_id}} dynamic variable\n";
echo "2. ✅ Backend now extracts call_id from webhook root level\n";
echo "3. ✅ Backend injects call_id into args before processing\n";
echo "4. ∴ call_id parameter mapping is not needed\n\n";

// Remove call_id from parameter_mapping in all function nodes
$updated = 0;
foreach ($flow['nodes'] as &$node) {
    if ($node['type'] === 'function') {
        if (isset($node['parameter_mapping']['call_id'])) {
            echo "Removing call_id from: {$node['name']}\n";
            unset($node['parameter_mapping']['call_id']);
            $updated++;
        }
    }
}

if ($updated === 0) {
    echo "✅ No changes needed - call_id already removed\n";
    exit(0);
}

echo "\n✅ Updated {$updated} function nodes\n\n";

// PATCH the flow
$payload = ['nodes' => $flow['nodes']];

$ch = curl_init("https://api.retellai.com/update-conversation-flow/{$flowId}");
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer {$apiKey}",
    "Content-Type: application/json"
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

$patchResponse = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    $updatedFlow = json_decode($patchResponse, true);
    echo "✅ PATCH successful!\n";
    echo "New Version: V{$updatedFlow['version']}\n\n";

    echo str_repeat('=', 80) . "\n";
    echo "NEXT STEP: Publish new version\n";
    echo str_repeat('=', 80) . "\n";
} else {
    echo "❌ PATCH failed!\n";
    echo "HTTP Code: {$httpCode}\n";
    echo "Response: {$patchResponse}\n";
    exit(1);
}
