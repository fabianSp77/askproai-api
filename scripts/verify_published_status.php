<?php

/**
 * Verify Agent V14 is Published
 */

$agentId = 'agent_45daa54928c5768b52ba3db736';
$apiKey = 'key_6ff998ba48e842092e04a5455d19';

echo "🎉 VERIFYING PUBLISHED STATUS\n";
echo str_repeat('=', 80) . "\n\n";

// Check Agent
$ch = curl_init("https://api.retellai.com/get-agent/{$agentId}");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer {$apiKey}",
    "Content-Type: application/json"
]);
$response = curl_exec($ch);
curl_close($ch);

$agent = json_decode($response, true);

echo "📋 AGENT STATUS\n";
echo str_repeat('-', 80) . "\n";
echo "Agent ID: {$agent['agent_id']}\n";
echo "Agent Name: {$agent['agent_name']}\n";
echo "Agent Version: V{$agent['version']}\n";
echo "Flow Version: V{$agent['response_engine']['version']}\n";
echo "Is Published: " . ($agent['is_published'] ? '🟢 YES' : '🔴 NO') . "\n";
echo "Last Modified: " . date('Y-m-d H:i:s', intval($agent['last_modification_timestamp'] / 1000)) . "\n\n";

if ($agent['is_published'] && $agent['response_engine']['version'] == 14) {
    echo "✅ ✅ ✅ PERFEKT! Agent V14 ist LIVE!\n\n";

    echo "🎯 NÄCHSTER SCHRITT: TEST-CALLS\n";
    echo str_repeat('=', 80) . "\n\n";

    echo "Führen Sie JETZT diese 3 Test-Calls durch:\n\n";

    echo "Test 1: BUCHUNG (sollte weiterhin funktionieren)\n";
    echo "   Sagen Sie: 'Herrenhaarschnitt morgen 16 Uhr, Hans Schuster'\n";
    echo "   Erwartet:\n";
    echo "     ✅ Verfügbarkeit wird geprüft\n";
    echo "     ✅ call_id = echte Call-ID\n";
    echo "     ✅ Termin wird gebucht\n\n";

    echo "Test 2: STORNIERUNG (sollte JETZT funktionieren)\n";
    echo "   Sagen Sie: 'Ich möchte meinen Termin morgen 14 Uhr stornieren'\n";
    echo "   Erwartet:\n";
    echo "     ✅ Agent fragt nach Datum/Uhrzeit (falls nicht erkannt)\n";
    echo "     ✅ cancel_datum und cancel_uhrzeit werden gesammelt\n";
    echo "     ✅ Termin wird storniert\n";
    echo "     ✅ KEINE 'Call context not available' Fehler\n\n";

    echo "Test 3: VERSCHIEBUNG (sollte JETZT funktionieren)\n";
    echo "   Sagen Sie: 'Morgen 14 Uhr auf Donnerstag 16 Uhr verschieben'\n";
    echo "   Erwartet:\n";
    echo "     ✅ Agent sammelt alte + neue Termin-Daten\n";
    echo "     ✅ Alle 4 Variables (old_datum, old_uhrzeit, new_datum, new_uhrzeit)\n";
    echo "     ✅ Termin wird verschoben\n";
    echo "     ✅ KEINE Fehler\n\n";

    echo "📊 Logs überwachen:\n";
    echo "   tail -f storage/logs/laravel.log | grep -E 'CANONICAL_CALL_ID|check_availability|cancel_appointment|reschedule_appointment'\n\n";

    echo "🎯 Bei Erfolg:\n";
    echo "   - P1 Incident vollständig behoben ✅\n";
    echo "   - Alle 3 Flows funktionieren (Buchung, Stornierung, Verschiebung)\n";
    echo "   - call_id Parameter wird korrekt übertragen\n";
    echo "   - State Management verhindert redundante Fragen\n\n";

    exit(0);

} else if (!$agent['is_published']) {
    echo "❌ FEHLER: Agent ist immer noch NICHT published!\n";
    echo "   Bitte prüfen Sie das Dashboard.\n\n";
    exit(1);

} else if ($agent['response_engine']['version'] != 14) {
    echo "⚠️  WARNUNG: Agent ist published, nutzt aber Flow V{$agent['response_engine']['version']} statt V14\n";
    echo "   Agent muss auf Flow V14 geupdated werden.\n\n";
    exit(1);
}
