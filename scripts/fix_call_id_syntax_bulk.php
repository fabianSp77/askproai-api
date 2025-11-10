<?php

/**
 * FIX: Change {{call.call_id}} → {{call_id}} (BULK UPDATE)
 */

$flowId = 'conversation_flow_a58405e3f67a';
$apiKey = 'key_6ff998ba48e842092e04a5455d19';

echo "🔧 FIXING call_id SYNTAX - BULK UPDATE\n";
echo str_repeat('=', 80) . "\n\n";

// Get current flow
$ch = curl_init("https://api.retellai.com/get-conversation-flow/{$flowId}");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer {$apiKey}",
    "Content-Type: application/json"
]);
$flowResponse = curl_exec($ch);
curl_close($ch);

$flow = json_decode($flowResponse, true);

echo "Current Flow: V{$flow['version']}\n\n";

// Update ALL nodes that have call_id parameter
$updated = 0;
foreach ($flow['nodes'] as &$node) {
    if ($node['type'] === 'function') {
        if (isset($node['parameter_mapping']['call_id'])) {
            if ($node['parameter_mapping']['call_id'] === '{{call.call_id}}') {
                echo "Updating: {$node['name']}\n";
                $node['parameter_mapping']['call_id'] = '{{call_id}}';
                $updated++;
            }
        }
    }
}
unset($node);

echo "\nUpdated {$updated} nodes\n\n";

// Send bulk update with ALL nodes
echo "Sending bulk update to Retell API...\n";

$payload = [
    'nodes' => $flow['nodes']
];

$ch = curl_init("https://api.retellai.com/update-conversation-flow/{$flowId}");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer {$apiKey}",
    "Content-Type: application/json"
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status: {$httpCode}\n";

if ($httpCode === 200) {
    echo "✅ Update successful\n\n";

    $updated = json_decode($response, true);
    echo "New Flow Version: V{$updated['version']}\n\n";

    // Verify
    echo "VERIFICATION:\n";
    echo str_repeat('-', 80) . "\n";

    $functionNodes = array_filter($updated['nodes'], fn($n) => $n['type'] === 'function');
    $allCorrect = true;

    foreach ($functionNodes as $node) {
        $callId = $node['parameter_mapping']['call_id'] ?? 'NOT SET';
        $icon = ($callId === '{{call_id}}') ? '✅' : '❌';
        echo "{$icon} {$node['name']}: {$callId}\n";

        if ($callId !== '{{call_id}}') {
            $allCorrect = false;
        }
    }

    echo "\n";

    if ($allCorrect) {
        echo "🎉 SUCCESS! All parameter mappings now use correct syntax!\n\n";

        echo "NEXT STEPS:\n";
        echo "1. This will auto-create V17\n";
        echo "2. Publish Flow V17\n";
        echo "3. Publish Agent (will use V17)\n";
        echo "4. Test call with correct call_id syntax\n\n";
    }

} else {
    echo "❌ Update failed\n";
    echo "Response: {$response}\n";
}
