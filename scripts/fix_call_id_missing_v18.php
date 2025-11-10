<?php

/**
 * Fix Missing call_id Parameter Mappings in Retell Flow
 *
 * Root Cause: Function nodes don't have call_id in parameter_mapping,
 * causing backend functions to receive empty call_id values.
 */

$apiKey = 'key_6ff998ba48e842092e04a5455d19';
$flowId = 'conversation_flow_a58405e3f67a';

echo "🔧 FIXING MISSING call_id PARAMETER MAPPINGS\n";
echo str_repeat('=', 80) . "\n\n";

// Step 1: Get current flow
echo "1️⃣  Fetching current flow...\n";
$ch = curl_init("https://api.retellai.com/get-conversation-flow/{$flowId}");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer {$apiKey}",
    "Content-Type: application/json"
]);
$flowResponse = curl_exec($ch);
curl_close($ch);

$flow = json_decode($flowResponse, true);
echo "   ✅ Current Flow Version: V{$flow['version']}\n\n";

// Step 2: Fix function nodes
echo "2️⃣  Fixing function nodes...\n";
$fixed = 0;

foreach ($flow['nodes'] as $key => $node) {
    if ($node['type'] === 'function') {
        // Add call_id to parameter_mapping
        if (!isset($flow['nodes'][$key]['parameter_mapping'])) {
            $flow['nodes'][$key]['parameter_mapping'] = [];
        }

        $before = json_encode($flow['nodes'][$key]['parameter_mapping']);
        $flow['nodes'][$key]['parameter_mapping']['call_id'] = '{{call_id}}';
        $after = json_encode($flow['nodes'][$key]['parameter_mapping']);

        if ($before !== $after) {
            echo "   ✅ Fixed: {$node['name']}\n";
            $fixed++;
        }
    }
}

echo "\n   Total Fixed: {$fixed} nodes\n\n";

// Step 3: Update flow
echo "3️⃣  Updating flow...\n";

$updatePayload = json_encode($flow);

$ch = curl_init("https://api.retellai.com/update-conversation-flow/{$flowId}");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PATCH");
curl_setopt($ch, CURLOPT_POSTFIELDS, $updatePayload);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer {$apiKey}",
    "Content-Type: application/json"
]);
$updateResponse = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    $updated = json_decode($updateResponse, true);
    echo "   ✅ Flow updated to V{$updated['version']}\n\n";

    // Step 4: Verify
    echo "4️⃣  Verifying fix...\n";
    $ch = curl_init("https://api.retellai.com/get-conversation-flow/{$flowId}");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer {$apiKey}",
        "Content-Type: application/json"
    ]);
    $verifyResponse = curl_exec($ch);
    curl_close($ch);

    $verify = json_decode($verifyResponse, true);

    $allGood = true;
    foreach ($verify['nodes'] as $node) {
        if ($node['type'] === 'function') {
            $hasCallId = isset($node['parameter_mapping']['call_id']);
            $correctValue = $hasCallId && $node['parameter_mapping']['call_id'] === '{{call_id}}';

            $icon = $correctValue ? '✅' : '❌';
            echo "   {$icon} {$node['name']}: ";
            echo $hasCallId ? $node['parameter_mapping']['call_id'] : 'MISSING';
            echo "\n";

            if (!$correctValue) {
                $allGood = false;
            }
        }
    }

    echo "\n";
    echo str_repeat('=', 80) . "\n";

    if ($allGood) {
        echo "✅ SUCCESS! All function nodes have correct call_id mappings!\n\n";
        echo "Next Steps:\n";
        echo "1. Test the flow in Retell dashboard\n";
        echo "2. Publish as new agent version\n";
        echo "3. Run test call\n";
    } else {
        echo "⚠️  WARNING: Some nodes still have issues!\n";
    }

} else {
    echo "   ❌ Update failed! HTTP {$httpCode}\n";
    echo "   Response: {$updateResponse}\n";
}
