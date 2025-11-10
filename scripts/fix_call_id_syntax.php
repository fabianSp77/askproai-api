<?php

/**
 * FIX: Change {{call.call_id}} → {{call_id}}
 *
 * ROOT CAUSE: We used wrong syntax based on assumption
 * CORRECT SYNTAX: {{call_id}} (confirmed by Retell docs)
 */

$flowId = 'conversation_flow_a58405e3f67a';
$apiKey = 'key_6ff998ba48e842092e04a5455d19';

echo "🔧 FIXING call_id SYNTAX ERROR\n";
echo str_repeat('=', 80) . "\n\n";

echo "ROOT CAUSE ANALYSIS:\n";
echo "   ❌ We used: {{call.call_id}}\n";
echo "   ✅ Correct:  {{call_id}}\n";
echo "   📚 Source:   Retell Dynamic Variables documentation\n\n";

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

echo "Current Flow: V{$flow['version']}\n";
echo "Is Published: " . ($flow['is_published'] ? 'YES' : 'NO') . "\n\n";

// Find all function nodes with wrong syntax
$functionNodes = array_filter($flow['nodes'], fn($n) => $n['type'] === 'function');
$nodesToFix = [];

echo "NODES TO FIX:\n";
echo str_repeat('-', 80) . "\n";

foreach ($functionNodes as $node) {
    $mapping = $node['parameter_mapping'] ?? [];
    if (isset($mapping['call_id']) && $mapping['call_id'] === '{{call.call_id}}') {
        $nodesToFix[] = $node;
        echo "   ✅ {$node['name']}: {$node['id']}\n";
    }
}
echo "\n";

if (count($nodesToFix) === 0) {
    echo "✅ No nodes to fix - all already use correct syntax!\n";
    exit(0);
}

echo "APPLYING FIX TO " . count($nodesToFix) . " NODES...\n";
echo str_repeat('-', 80) . "\n\n";

// Fix each node
foreach ($nodesToFix as $node) {
    echo "Fixing: {$node['name']} ({$node['id']})\n";

    // Update parameter mapping
    $node['parameter_mapping']['call_id'] = '{{call_id}}';

    // Prepare PATCH payload for this specific node
    $payload = [
        'nodes' => [$node]
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

    if ($httpCode === 200) {
        echo "   ✅ Updated successfully\n";
    } else {
        echo "   ❌ Failed (HTTP {$httpCode})\n";
        echo "   Response: {$response}\n";
    }

    // Small delay to avoid rate limiting
    usleep(500000); // 0.5 seconds
}

echo "\n" . str_repeat('=', 80) . "\n";
echo "FIX COMPLETED\n";
echo str_repeat('=', 80) . "\n\n";

// Verify the fix
$ch = curl_init("https://api.retellai.com/get-conversation-flow/{$flowId}");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer {$apiKey}",
    "Content-Type: application/json"
]);
$flowResponse = curl_exec($ch);
curl_close($ch);

$updatedFlow = json_decode($flowResponse, true);

echo "VERIFICATION:\n";
echo "   Updated Flow Version: V{$updatedFlow['version']}\n\n";

echo "PARAMETER MAPPINGS AFTER FIX:\n";
echo str_repeat('-', 80) . "\n";

$functionNodes = array_filter($updatedFlow['nodes'], fn($n) => $n['type'] === 'function');
$allCorrect = true;

foreach ($functionNodes as $node) {
    $mapping = $node['parameter_mapping'] ?? [];
    $callId = $mapping['call_id'] ?? 'NOT SET';

    $icon = ($callId === '{{call_id}}') ? '✅' : '❌';
    echo "   {$icon} {$node['name']}: {$callId}\n";

    if ($callId !== '{{call_id}}') {
        $allCorrect = false;
    }
}
echo "\n";

if ($allCorrect) {
    echo "🎉 SUCCESS! All nodes now use correct syntax: {{call_id}}\n\n";

    echo "NEXT STEPS:\n";
    echo "1. Publish Flow V{$updatedFlow['version']}\n";
    echo "2. Publish Agent (will auto-update to use published flow)\n";
    echo "3. Test call to verify call_id is correctly transmitted\n\n";
} else {
    echo "❌ FAILED: Some nodes still have incorrect syntax\n";
}
