<?php

/**
 * Remove call_id Parameter from Tool Definitions and Mappings
 *
 * ROOT CAUSE: Retell does NOT provide {{call_id}} as a dynamic variable.
 * Only available: {{twilio-accountsid}}, {{twilio-callsid}}
 *
 * SOLUTION: Remove call_id from tool definitions and parameter mappings.
 * Backend will extract call_id from webhook context (call.call_id) instead.
 */

$apiKey = 'key_6ff998ba48e842092e04a5455d19';
$flowId = 'conversation_flow_a58405e3f67a';

echo "🔧 REMOVING call_id PARAMETER FROM FLOW\n";
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

// Step 2: Remove call_id from tool definitions
echo "2️⃣  Removing call_id from tool definitions...\n";
$toolsModified = 0;

foreach ($flow['tools'] as $key => $tool) {
    if (in_array($tool['name'], ['check_availability_v17', 'book_appointment_v17'])) {
        // Remove call_id from properties
        if (isset($tool['parameters']['properties']['call_id'])) {
            unset($flow['tools'][$key]['parameters']['properties']['call_id']);
            echo "   ✅ Removed call_id from {$tool['name']} properties\n";
            $toolsModified++;
        }

        // Remove call_id from required array
        if (isset($tool['parameters']['required'])) {
            $required = $tool['parameters']['required'];
            $filtered = array_values(array_filter($required, fn($r) => $r !== 'call_id'));
            $flow['tools'][$key]['parameters']['required'] = $filtered;

            if (count($filtered) !== count($required)) {
                echo "   ✅ Removed call_id from {$tool['name']} required fields\n";
            }
        }
    }
}

echo "   Total tools modified: {$toolsModified}\n\n";

// Step 3: Remove call_id from parameter mappings
echo "3️⃣  Removing call_id from parameter mappings...\n";
$nodesModified = 0;

foreach ($flow['nodes'] as $key => $node) {
    if ($node['type'] === 'function' && isset($node['parameter_mapping']['call_id'])) {
        unset($flow['nodes'][$key]['parameter_mapping']['call_id']);
        echo "   ✅ Removed call_id from {$node['name']} parameter mapping\n";
        $nodesModified++;
    }
}

echo "   Total nodes modified: {$nodesModified}\n\n";

// Step 4: Update flow
echo "4️⃣  Updating flow...\n";

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

    // Step 5: Verify
    echo "5️⃣  Verifying changes...\n";
    $ch = curl_init("https://api.retellai.com/get-conversation-flow/{$flowId}");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer {$apiKey}",
        "Content-Type: application/json"
    ]);
    $verifyResponse = curl_exec($ch);
    curl_close($ch);

    $verify = json_decode($verifyResponse, true);

    echo "\nTool Definitions:\n";
    foreach ($verify['tools'] as $tool) {
        if (in_array($tool['name'], ['check_availability_v17', 'book_appointment_v17'])) {
            $hasCallId = isset($tool['parameters']['properties']['call_id']);
            $icon = $hasCallId ? '❌' : '✅';
            echo "  {$icon} {$tool['name']}: call_id " . ($hasCallId ? 'PRESENT' : 'REMOVED') . "\n";
        }
    }

    echo "\nParameter Mappings:\n";
    foreach ($verify['nodes'] as $node) {
        if ($node['type'] === 'function' && in_array($node['name'], ['Verfügbarkeit prüfen', 'Termin buchen'])) {
            $hasCallId = isset($node['parameter_mapping']['call_id']);
            $icon = $hasCallId ? '❌' : '✅';
            echo "  {$icon} {$node['name']}: call_id " . ($hasCallId ? 'PRESENT' : 'REMOVED') . "\n";
        }
    }

    echo "\n";
    echo str_repeat('=', 80) . "\n";
    echo "✅ SUCCESS! call_id removed from flow!\n\n";
    echo "Backend will now extract call_id from webhook context:\n";
    echo "  \$request->input('call.call_id')\n\n";
    echo "Next Steps:\n";
    echo "1. Publish as V22: php scripts/publish_v22.php\n";
    echo "2. Run test call\n";
    echo "3. Verify availability check works\n";

} else {
    echo "   ❌ Update failed! HTTP {$httpCode}\n";
    echo "   Response: {$updateResponse}\n";
}
