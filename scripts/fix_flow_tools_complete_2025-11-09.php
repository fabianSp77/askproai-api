<?php
/**
 * Fix all tools in Conversation Flow V99
 * Add parameter_mapping to all tools with call_id parameter
 */

$apiKey = 'key_6ff998ba48e842092e04a5455d19';
$flowId = 'conversation_flow_a58405e3f67a';

echo "=== FIX CONVERSATION FLOW TOOLS ===\n\n";

// 1. Get flow
echo "1. Fetching flow...\n";
$ch = curl_init("https://api.retellai.com/get-conversation-flow/{$flowId}");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer {$apiKey}",
        "Content-Type: application/json"
    ]
]);

$response = curl_exec($ch);
$flow = json_decode($response, true);
curl_close($ch);

echo "✅ Flow V{$flow['version']}\n";
echo "   Tools: " . count($flow['tools']) . "\n\n";

// 2. Fix tools
echo "2. Fixing tools...\n";

$fixedCount = 0;
$alreadyCorrect = 0;

foreach ($flow['tools'] as &$tool) {
    $name = $tool['name'] ?? 'unknown';
    $hasCallId = isset($tool['parameters']['properties']['call_id']);

    if (!$hasCallId) {
        echo "  ⏭️  {$name}: No call_id parameter\n";
        continue;
    }

    $hasMapping = isset($tool['parameter_mapping']['call_id']);
    $mappingValue = $hasMapping ? $tool['parameter_mapping']['call_id'] : null;

    if ($mappingValue === '{{call_id}}') {
        echo "  ✅ {$name}: Already correct\n";
        $alreadyCorrect++;
    } else {
        echo "  🔧 {$name}: Adding parameter_mapping\n";
        if (!isset($tool['parameter_mapping'])) {
            $tool['parameter_mapping'] = [];
        }
        $tool['parameter_mapping']['call_id'] = '{{call_id}}';
        $fixedCount++;
    }
}
unset($tool);

echo "\n📊 Summary:\n";
echo "  - Fixed: {$fixedCount}\n";
echo "  - Already correct: {$alreadyCorrect}\n\n";

if ($fixedCount === 0) {
    echo "✅ No changes needed!\n";
    exit(0);
}

// 3. Update flow
echo "3. Updating flow...\n";
$ch = curl_init("https://api.retellai.com/update-conversation-flow/{$flowId}");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST => 'PATCH',
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
    echo "❌ Failed to update. HTTP {$httpCode}\n";
    echo "Response: {$response}\n";
    exit(1);
}

$updated = json_decode($response, true);
$newVersion = $updated['version'] ?? 'unknown';
echo "✅ Updated to V{$newVersion}\n\n";

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
    echo "⚠️ Publish failed. HTTP {$httpCode}\n";
    echo "Response: {$response}\n\n";
} else {
    echo "✅ Flow V{$newVersion} published!\n\n";
}

// 5. Verify
echo "5. Verifying...\n";
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
curl_close($ch);

echo "✅ Verification:\n";
echo "   Version: V{$verifyFlow['version']}\n";
echo "   Published: " . ($verifyFlow['is_published'] ? '✅ YES' : '❌ NO') . "\n\n";

// Check all tools
echo "   Tools with parameter_mapping:\n";
foreach ($verifyFlow['tools'] as $tool) {
    if (isset($tool['parameters']['properties']['call_id'])) {
        $mapping = $tool['parameter_mapping']['call_id'] ?? 'MISSING';
        $status = $mapping === '{{call_id}}' ? '✅' : '❌';
        echo "   {$status} {$tool['name']}: {$mapping}\n";
    }
}

echo "\n🎉 FIX COMPLETE!\n";
echo "\n=== END FIX ===\n";
