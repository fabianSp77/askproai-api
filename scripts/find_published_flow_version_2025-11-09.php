<?php
/**
 * Find which flow version is actually published
 * Since API only returns current version, we check via agent versions
 */

$apiKey = 'key_6ff998ba48e842092e04a5455d19';
$flowId = 'conversation_flow_a58405e3f67a';

echo "=== FINDING PUBLISHED FLOW VERSION ===\n\n";

// Get current flow
$ch = curl_init("https://api.retellai.com/get-conversation-flow/{$flowId}");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer {$apiKey}",
        "Content-Type: application/json"
    ]
]);

$response = curl_exec($ch);
$currentFlow = json_decode($response, true);
curl_close($ch);

echo "Current Flow:\n";
echo "  Version: V{$currentFlow['version']}\n";
echo "  Published: " . ($currentFlow['is_published'] ? 'YES ✅' : 'NO ❌') . "\n\n";

// Check one tool to see if current has correct mapping
$hasCorrectMapping = false;
foreach ($currentFlow['tools'] as $tool) {
    if ($tool['name'] === 'get_current_context') {
        $mapping = $tool['parameter_mapping']['call_id'] ?? 'MISSING';
        $hasCorrectMapping = ($mapping === '{{call_id}}');
        echo "Current Flow V{$currentFlow['version']} - Tool get_current_context:\n";
        echo "  parameter_mapping['call_id']: {$mapping}\n";
        echo "  Status: " . ($hasCorrectMapping ? '✅ KORREKT' : '❌ FALSCH/FEHLT') . "\n\n";
        break;
    }
}

// Now the key question: if current is not published, what IS published?
if (!$currentFlow['is_published']) {
    echo "⚠️  PROBLEM: Aktuelle Flow V{$currentFlow['version']} ist NICHT published!\n";
    echo "   Das bedeutet: Eine ÄLTERE Version ist published\n\n";

    // Unfortunately Retell API doesn't let us list old versions
    // We can only infer from when it was last modified
    echo "Wann wurde Flow zuletzt geupdated?\n";
    echo "  Last Modified: " . ($currentFlow['last_modification_timestamp'] ?? 'N/A') . "\n\n";

    // Try to publish current version
    echo "VERSUCH: Flow V{$currentFlow['version']} zu publishen...\n";
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

    if ($httpCode === 200) {
        echo "✅ SUCCESS! Flow V{$currentFlow['version']} wurde published!\n\n";
        echo "🎯 Jetzt sollte ein Testanruf funktionieren!\n";
    } else {
        echo "❌ FEHLER beim Publishen via API (HTTP {$httpCode})\n";
        echo "Response: {$response}\n\n";

        echo "🚨 DU MUSST MANUELL PUBLISHEN:\n";
        echo "   1. Gehe zu: https://dashboard.retellai.com/\n";
        echo "   2. Suche Agent: agent_45daa54928c5768b52ba3db736\n";
        echo "   3. Öffne Conversation Flow\n";
        echo "   4. Publishe Version {$currentFlow['version']}\n";
    }
} else {
    echo "✅ Current Flow V{$currentFlow['version']} IST published!\n\n";

    if ($hasCorrectMapping) {
        echo "✅ Und hat korrekte parameter_mappings!\n";
        echo "   Testanrufe sollten funktionieren!\n";
    } else {
        echo "❌ ABER: parameter_mappings sind FALSCH/FEHLEN!\n";
        echo "   Das sollte nicht passieren!\n";
    }
}

echo "\n=== END ===\n";
