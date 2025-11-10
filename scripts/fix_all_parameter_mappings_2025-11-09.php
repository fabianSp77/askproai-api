<?php
/**
 * Fix ALL parameter_mappings for ALL tools
 * Adds call_id template variable to every tool
 */

$apiKey = 'key_6ff998ba48e842092e04a5455d19';
$flowId = 'conversation_flow_a58405e3f67a';

echo "=== FIX ALL PARAMETER MAPPINGS ===\n\n";

// 1. Get current flow
echo "1. Fetching current flow...\n";
$ch = curl_init("https://api.retellai.com/get-conversation-flow/{$flowId}");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer {$apiKey}",
        "Content-Type: application/json"
    ]
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    die("❌ Failed to fetch flow. HTTP {$httpCode}\n");
}

$flow = json_decode($response, true);
echo "✅ Fetched Flow V{$flow['version']}\n\n";

// 2. Fix ALL tools
echo "2. Fixing parameter_mappings for ALL tools...\n";

$toolsToFix = [
    'get_current_context',
    'check_availability_v17',
    'start_booking',
    'confirm_booking'
];

$fixedCount = 0;
$alreadyCorrect = 0;

foreach ($flow['nodes'] as &$node) {
    if (isset($node['type']) && $node['type'] === 'tool_call') {
        $toolName = $node['tool_call']['tool_name'] ?? 'unknown';

        if (in_array($toolName, $toolsToFix)) {
            // Check current parameter_mapping
            $hasMapping = isset($node['tool_call']['parameter_mapping']['call_id']);
            $mappingValue = $hasMapping ? $node['tool_call']['parameter_mapping']['call_id'] : null;

            if ($mappingValue === '{{call_id}}') {
                echo "  ✅ {$toolName}: Already correct ({{call_id}})\n";
                $alreadyCorrect++;
            } else {
                echo "  🔧 {$toolName}: ";
                if ($hasMapping) {
                    echo "Fixing (was: {$mappingValue})\n";
                } else {
                    echo "Adding parameter_mapping\n";
                }

                // Set parameter_mapping
                if (!isset($node['tool_call']['parameter_mapping'])) {
                    $node['tool_call']['parameter_mapping'] = [];
                }
                $node['tool_call']['parameter_mapping']['call_id'] = '{{call_id}}';
                $fixedCount++;
            }
        }
    }
}
unset($node);

echo "\n📊 Summary:\n";
echo "  - Fixed: {$fixedCount} tools\n";
echo "  - Already correct: {$alreadyCorrect} tools\n";
echo "  - Total tools: " . ($fixedCount + $alreadyCorrect) . "\n\n";

if ($fixedCount === 0) {
    echo "✅ No changes needed - all parameter_mappings already correct!\n";
    exit(0);
}

// 3. Update flow
echo "3. Updating flow...\n";
$ch = curl_init("https://api.retellai.com/update-conversation-flow/{$flowId}");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer {$apiKey}",
        "Content-Type: application/json"
    ],
    CURLOPT_POSTFIELDS => json_encode($flow)
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    echo "❌ Failed to update flow. HTTP {$httpCode}\n";
    echo "Response: {$response}\n";
    exit(1);
}

$updated = json_decode($response, true);
$newVersion = $updated['version'] ?? 'unknown';
echo "✅ Flow updated to V{$newVersion}\n\n";

// 4. Publish flow
echo "4. Publishing flow V{$newVersion}...\n";
$ch = curl_init("https://api.retellai.com/publish-conversation-flow/{$flowId}");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer {$apiKey}",
        "Content-Type: application/json"
    ],
    CURLOPT_POSTFIELDS => json_encode([])
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    echo "⚠️ Auto-publish failed. HTTP {$httpCode}\n";
    echo "Response: {$response}\n";
    echo "\n⚠️ ACTION REQUIRED: Please publish manually in Retell Dashboard\n";
    echo "   1. Go to https://app.retellai.com/\n";
    echo "   2. Navigate to Conversation Flows\n";
    echo "   3. Find flow: {$flowId}\n";
    echo "   4. Click 'Publish' button\n\n";
} else {
    echo "✅ Flow V{$newVersion} published successfully!\n\n";
}

// 5. Verify
echo "5. Verifying changes...\n";
$ch = curl_init("https://api.retellai.com/get-conversation-flow/{$flowId}");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer {$apiKey}",
        "Content-Type: application/json"
    ]
]);

$response = curl_exec($ch);
$verifyFlow = json_decode($response, true);

echo "✅ Verification:\n";
echo "   Version: V{$verifyFlow['version']}\n";
echo "   Published: " . ($verifyFlow['is_published'] ? '✅ YES' : '❌ NO') . "\n\n";

if ($verifyFlow['is_published']) {
    echo "🎉 SUCCESS! All parameter_mappings fixed and published!\n\n";
    echo "Next: Make test call to verify call_id is no longer \"1\"\n";
} else {
    echo "⚠️ Not published yet - please publish manually\n";
}

echo "\n=== END FIX ===\n";
