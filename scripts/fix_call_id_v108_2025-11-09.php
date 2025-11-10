<?php
/**
 * Fix call_id hardcoded to "1" - V108
 *
 * Problem: All tool calls use {{call_id}} which resolves to "1"
 * This breaks session lookup in confirm_booking
 *
 * Solution: Remove call_id from parameter_mapping in tools
 * Backend will use canonical call_id from webhook context (call.call_id)
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$flowId = 'conversation_flow_a58405e3f67a';
$apiKey = config('services.retellai.api_key');

echo "=== FIX CALL_ID ISSUE - V108 ===\n\n";

// STEP 1: Get current flow
echo "1. Fetching current flow V107...\n";
$ch = curl_init("https://api.retellai.com/get-conversation-flow/{$flowId}");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer {$apiKey}",
        "Content-Type: application/json"
    ]
]);
$response = curl_exec($ch);
curl_close($ch);

$flow = json_decode($response, true);
$currentVersion = $flow['version'];
echo "   Current version: V{$currentVersion}\n\n";

// STEP 2: Remove call_id from all tool parameter_mappings
echo "2. Removing call_id from tool parameter_mappings...\n";
echo "   (Backend will use canonical call_id from webhook context)\n\n";

$toolsFixed = 0;

foreach ($flow['tools'] as &$tool) {
    if (isset($tool['parameter_mapping']['call_id'])) {
        echo "   Removing call_id from tool: {$tool['name']}\n";
        unset($tool['parameter_mapping']['call_id']);
        $toolsFixed++;
    }
}
unset($tool);

echo "   Total tools fixed: {$toolsFixed}\n\n";

// STEP 3: Remove call_id from node parameter_mappings
echo "3. Removing call_id from node parameter_mappings...\n";

$nodesFixed = 0;

foreach ($flow['nodes'] as &$node) {
    if (isset($node['parameter_mapping']['call_id'])) {
        echo "   Removing call_id from node: {$node['name']}\n";
        unset($node['parameter_mapping']['call_id']);
        $nodesFixed++;
    }
}
unset($node);

echo "   Total nodes fixed: {$nodesFixed}\n\n";

// STEP 4: Upload to API
echo "4. Uploading fixed flow...\n";

$payload = json_encode($flow, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

$ch = curl_init("https://api.retellai.com/update-conversation-flow/{$flowId}");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST => "PATCH",
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer {$apiKey}",
        "Content-Type: application/json"
    ],
    CURLOPT_POSTFIELDS => $payload
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    echo "   ❌ Failed to upload: HTTP {$httpCode}\n";
    echo "   Response: {$response}\n";
    exit(1);
}

$updatedFlow = json_decode($response, true);
$newVersion = $updatedFlow['version'];

echo "   ✅ Flow uploaded successfully!\n";
echo "   New version: V{$newVersion}\n\n";

// STEP 5: Verify
echo "5. Verifying fix...\n";

$ch = curl_init("https://api.retellai.com/get-conversation-flow/{$flowId}");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer {$apiKey}",
        "Content-Type: application/json"
    ]
]);
$response = curl_exec($ch);
curl_close($ch);

$verifiedFlow = json_decode($response, true);

// Check tools
$stillHasCallId = false;
foreach ($verifiedFlow['tools'] as $tool) {
    if (isset($tool['parameter_mapping']['call_id'])) {
        echo "   ❌ Tool {$tool['name']} still has call_id!\n";
        $stillHasCallId = true;
    }
}

// Check nodes
foreach ($verifiedFlow['nodes'] as $node) {
    if (isset($node['parameter_mapping']['call_id'])) {
        echo "   ❌ Node {$node['name']} still has call_id!\n";
        $stillHasCallId = true;
    }
}

if (!$stillHasCallId) {
    echo "   ✅ No tools/nodes have call_id parameter_mapping\n\n";
} else {
    echo "\n   ⚠️  Some tools/nodes still have call_id!\n\n";
}

echo "=== SUMMARY ===\n\n";
echo "✅ Flow updated: V{$currentVersion} → V{$newVersion}\n";
echo "✅ Removed call_id from {$toolsFixed} tools\n";
echo "✅ Removed call_id from {$nodesFixed} nodes\n";
echo "📌 Published: NO (User must publish manually)\n\n";

echo "=== HOW THIS FIXES THE ISSUE ===\n\n";
echo "Before:\n";
echo "  Tool calls: {{call_id}} → resolves to '1' ❌\n";
echo "  Backend: Looks for session under call_id='1' ❌\n";
echo "  Result: Session not found, booking fails\n\n";

echo "After:\n";
echo "  Tool calls: No call_id parameter\n";
echo "  Backend: Uses canonical call_id from webhook context ✅\n";
echo "  Result: Session found, booking succeeds\n\n";

echo "Backend extracts call_id from:\n";
echo "  \$request->input('call.call_id') - Canonical source from Retell\n\n";

echo "=== NEXT STEPS ===\n\n";
echo "1. Publish V{$newVersion} in Retell Dashboard\n";
echo "2. Make VOICE CALL test\n";
echo "3. Verify booking succeeds\n\n";

echo "=== END ===\n";
