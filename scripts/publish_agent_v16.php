<?php

/**
 * Publish Agent V16 Using Correct API Endpoint
 */

$agentId = 'agent_45daa54928c5768b52ba3db736';
$apiKey = 'key_6ff998ba48e842092e04a5455d19';

echo "🚀 PUBLISHING AGENT V16\n";
echo str_repeat('=', 80) . "\n\n";

// Verify current status
$ch = curl_init("https://api.retellai.com/get-agent/{$agentId}");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer {$apiKey}",
    "Content-Type: application/json"
]);
$response = curl_exec($ch);
curl_close($ch);

$agent = json_decode($response, true);

echo "CURRENT STATUS:\n";
echo "   Agent Version: V{$agent['version']}\n";
echo "   Is Published: " . ($agent['is_published'] ? 'YES' : 'NO') . "\n";
echo "   Flow Version: V{$agent['response_engine']['version']}\n\n";

if ($agent['is_published']) {
    echo "✅ Agent is already published!\n";
    exit(0);
}

// Publish agent using correct endpoint
echo "Publishing Agent V{$agent['version']}...\n";

$ch = curl_init("https://api.retellai.com/publish-agent/{$agentId}");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer {$apiKey}",
    "Content-Type: application/json"
]);
// Empty body for POST
curl_setopt($ch, CURLOPT_POSTFIELDS, '{}');

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status: {$httpCode}\n";

if ($httpCode === 200) {
    $result = json_decode($response, true);
    echo "✅ Agent published successfully!\n\n";

    // Verify
    $ch = curl_init("https://api.retellai.com/get-agent/{$agentId}");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer {$apiKey}",
        "Content-Type: application/json"
    ]);
    $verifyResponse = curl_exec($ch);
    curl_close($ch);

    $verifiedAgent = json_decode($verifyResponse, true);

    echo "VERIFIED STATUS:\n";
    echo "   Agent Version: V{$verifiedAgent['version']}\n";
    echo "   Is Published: " . ($verifiedAgent['is_published'] ? '🟢 YES' : '🔴 NO') . "\n";
    echo "   Flow Version: V{$verifiedAgent['response_engine']['version']}\n\n";

    if ($verifiedAgent['is_published']) {
        echo str_repeat('=', 80) . "\n";
        echo "🎉 SUCCESS! Agent V{$verifiedAgent['version']} is LIVE!\n";
        echo str_repeat('=', 80) . "\n\n";

        echo "WHAT'S NEW IN V{$verifiedAgent['version']}:\n";
        echo "   ✅ Correct call_id syntax: {{call_id}} (not {{call.call_id}})\n";
        echo "   ✅ All 6 function nodes updated\n";
        echo "   ✅ Global prompt: 10 dynamic variables\n";
        echo "   ✅ Stornierung flow: State management\n";
        echo "   ✅ Verschiebung flow: State management\n\n";

        echo "READY FOR TESTING!\n\n";

        echo "TEST CALL:\n";
        echo "   Say: \"Herrenhaarschnitt morgen 16 Uhr, Hans Schuster\"\n\n";

        echo "EXPECTED:\n";
        echo "   ✅ Agent collects all data\n";
        echo "   ✅ Calls check_availability with call_id=\"call_xxx\"\n";
        echo "   ✅ Backend receives valid call context\n";
        echo "   ✅ NO MORE 'Call context not available' errors!\n\n";

        echo "MONITOR:\n";
        echo "   tail -f storage/logs/laravel.log | grep CANONICAL_CALL_ID\n\n";
    }

} else {
    echo "❌ Failed to publish agent\n";
    echo "Response: {$response}\n";
    exit(1);
}
