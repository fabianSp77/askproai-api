<?php

/**
 * Publish Agent V13 with Flow V13
 *
 * This will make the corrected flow (V13) active for live calls.
 */

$agentId = 'agent_45daa54928c5768b52ba3db736';
$flowId = 'conversation_flow_a58405e3f67a';
$apiKey = 'key_6ff998ba48e842092e04a5455d19';

echo "🚀 Publishing Agent + Flow V13...\n\n";

// Step 1: Get current agent configuration
$ch = curl_init("https://api.retellai.com/get-agent/{$agentId}");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer {$apiKey}",
    "Content-Type: application/json"
]);
$response = curl_exec($ch);
curl_close($ch);

$agent = json_decode($response, true);

echo "📋 Current Agent:\n";
echo "   Version: {$agent['version']}\n";
echo "   Flow Version: {$agent['response_engine']['version']}\n";
echo "   Is Published: " . ($agent['is_published'] ? 'YES' : 'NO') . "\n\n";

// Step 2: Update agent to explicitly use Flow V13 and trigger publish
$updatePayload = [
    'agent_name' => $agent['agent_name'],
    'response_engine' => [
        'type' => 'conversation-flow',
        'conversation_flow_id' => $flowId,
        'version' => 13  // Explicitly request V13
    ],
    'webhook_url' => $agent['webhook_url'],
    'language' => $agent['language'],
    'voice_id' => $agent['voice_id'],
    'voice_temperature' => $agent['voice_temperature'] ?? 0.02,
    'voice_speed' => $agent['voice_speed'] ?? 1,
    'volume' => $agent['volume'] ?? 1,
    'max_call_duration_ms' => $agent['max_call_duration_ms'] ?? 1800000,
    'interruption_sensitivity' => $agent['interruption_sensitivity'] ?? 1,
    'responsiveness' => $agent['responsiveness'] ?? 1,
    'enable_backchannel' => $agent['enable_backchannel'] ?? true,
    'data_storage_setting' => $agent['data_storage_setting'] ?? 'everything',
    'end_call_after_silence_ms' => $agent['end_call_after_silence_ms'] ?? 60000,
    'reminder_trigger_ms' => $agent['reminder_trigger_ms'] ?? 10000,
    'reminder_max_count' => $agent['reminder_max_count'] ?? 2
];

echo "🔄 Updating agent to use Flow V13...\n\n";

$ch = curl_init("https://api.retellai.com/update-agent/{$agentId}");
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PATCH");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer {$apiKey}",
    "Content-Type: application/json"
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($updatePayload));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200 || $httpCode === 201) {
    $result = json_decode($response, true);

    echo "✅ Agent aktualisiert!\n";
    echo "   New Version: {$result['version']}\n";
    echo "   Flow Version: {$result['response_engine']['version']}\n";
    echo "   Is Published: " . ($result['is_published'] ? 'YES ✅' : 'NO ❌') . "\n";
    echo "   Last Modified: " . date('Y-m-d H:i:s', intval($result['last_modification_timestamp'] / 1000)) . "\n\n";

    if ($result['is_published']) {
        echo "🎉 🎉 🎉 SUCCESS! Agent ist jetzt LIVE mit Flow V13!\n\n";

        echo "🎯 NÄCHSTER SCHRITT:\n";
        echo "   Machen Sie JETZT einen Test-Call!\n\n";

        echo "Test-Szenario:\n";
        echo '   "Herrenhaarschnitt morgen 16 Uhr, Hans Schuster"' . "\n\n";

        echo "Erwartetes Ergebnis:\n";
        echo "   ✅ Verfügbarkeit wird geprüft\n";
        echo "   ✅ Keine Fehler mehr\n";
        echo "   ✅ call_id = echte Call-ID (nicht \"call_1\")\n\n";

        echo "Laravel Logs monitoren:\n";
        echo "   tail -f storage/logs/laravel.log | grep CANONICAL_CALL_ID\n";

    } else {
        echo "⚠️  Agent wurde aktualisiert, ist aber noch DRAFT.\n";
        echo "   Sie müssen im Dashboard auf PUBLISH klicken.\n";
        echo "   https://dashboard.retellai.com/agents/{$agentId}\n";
    }

} else {
    echo "❌ ERROR: HTTP {$httpCode}\n";
    echo "Response: {$response}\n";
}
