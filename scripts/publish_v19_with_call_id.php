<?php

/**
 * Publish V19 with Fixed call_id Mappings
 */

$apiKey = 'key_6ff998ba48e842092e04a5455d19';
$agentId = 'agent_45daa54928c5768b52ba3db736';

echo "📦 PUBLISHING V19 WITH call_id FIX\n";
echo str_repeat('=', 80) . "\n\n";

// Step 1: Get current draft
echo "1️⃣  Getting current agent draft...\n";
$ch = curl_init("https://api.retellai.com/get-agent/{$agentId}");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer {$apiKey}",
    "Content-Type: application/json"
]);
$agentResponse = curl_exec($ch);
curl_close($ch);

$agent = json_decode($agentResponse, true);
echo "   Current Draft: V{$agent['version']}\n";
echo "   Flow Version: V{$agent['response_engine']['version']}\n\n";

// Step 2: Publish
echo "2️⃣  Publishing agent...\n";

$publishPayload = json_encode([
    'version_title' => 'V19 - Fixed call_id parameter mappings'
]);

$ch = curl_init("https://api.retellai.com/publish-agent/{$agentId}");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $publishPayload);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer {$apiKey}",
    "Content-Type: application/json"
]);
$publishResponse = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    $published = json_decode($publishResponse, true);
    echo "   ✅ Published!\n";
    echo "   New Published Version: V{$published['version']}\n\n";

    // Step 3: Verify published version
    echo "3️⃣  Verifying published version...\n";

    // Get flow to verify call_id mappings
    $flowId = $published['response_engine']['conversation_flow_id'];
    $ch = curl_init("https://api.retellai.com/get-conversation-flow/{$flowId}");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer {$apiKey}",
        "Content-Type: application/json"
    ]);
    $flowResponse = curl_exec($ch);
    curl_close($ch);

    $flow = json_decode($flowResponse, true);

    $critical = ['Verfügbarkeit prüfen', 'Termin buchen'];
    $allGood = true;

    foreach ($flow['nodes'] as $node) {
        if ($node['type'] === 'function' && in_array($node['name'], $critical)) {
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
        echo "✅ SUCCESS! V{$published['version']} is published and ready!\n\n";
        echo "🎯 READY FOR TEST CALL\n\n";
        echo "To test:\n";
        echo "1. Call +49 30 33081738\n";
        echo "2. Request: \"Herrenhaarschnitt für morgen 09:00 Uhr\"\n";
        echo "3. Accept alternative time if offered\n";
        echo "4. Verify booking completes successfully\n";
    } else {
        echo "⚠️  WARNING: Published version has issues!\n";
    }

} else {
    echo "   ❌ Publish failed! HTTP {$httpCode}\n";
    echo "   Response: {$publishResponse}\n";
}
