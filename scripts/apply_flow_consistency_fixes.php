<?php

/**
 * Apply Flow V14 Consistency Fixes
 *
 * Fixes:
 * 1. Global Prompt: Add 6 new variables, remove 1 unused
 * 2. Stornierung Node: Add state management (nach Buchungs-Muster)
 * 3. Verschiebung Node: Add state management (nach Buchungs-Muster)
 */

$flowId = 'conversation_flow_a58405e3f67a';
$apiKey = 'key_6ff998ba48e842092e04a5455d19';

echo "🔧 APPLYING FLOW CONSISTENCY FIXES\n";
echo str_repeat("=", 80) . "\n\n";

// ============================================================================
// STEP 1: Load Current Flow
// ============================================================================

echo "📥 Step 1: Loading Flow V14...\n";

$ch = curl_init("https://api.retellai.com/get-conversation-flow/{$flowId}");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer {$apiKey}",
    "Content-Type: application/json"
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    die("❌ Failed to load flow: HTTP {$httpCode}\n{$response}\n");
}

$flow = json_decode($response, true);

echo "✅ Loaded Flow V{$flow['version']}\n";
echo "   Nodes: " . count($flow['nodes']) . "\n";
echo "   Tools: " . count($flow['tools']) . "\n\n";

// Backup
file_put_contents('/tmp/flow_v14_backup.json', json_encode($flow, JSON_PRETTY_PRINT));
echo "💾 Backup saved: /tmp/flow_v14_backup.json\n\n";

// ============================================================================
// STEP 2: Fix Global Prompt
// ============================================================================

echo "📝 Step 2: Fixing Global Prompt...\n";

$oldPrompt = $flow['global_prompt'];

// Find the Dynamic Variables section and replace it
$newVariablesSection = "**Du hast Zugriff auf Dynamic Variables:**
- {{customer_name}} - Name des Kunden
- {{service_name}} - Gewünschter Service
- {{appointment_date}} - Gewünschtes Datum
- {{appointment_time}} - Gewünschte Uhrzeit
- {{cancel_datum}} - Datum für Stornierung
- {{cancel_uhrzeit}} - Uhrzeit für Stornierung
- {{old_datum}} - Alter Termin Datum für Verschiebung
- {{old_uhrzeit}} - Alter Termin Uhrzeit für Verschiebung
- {{new_datum}} - Neuer Termin Datum für Verschiebung
- {{new_uhrzeit}} - Neuer Termin Uhrzeit für Verschiebung";

// Replace the variables section
$newPrompt = preg_replace(
    '/\*\*Du hast Zugriff auf Dynamic Variables:\*\*.*?(?=\n\n\*\*IMMER ZUERST)/s',
    $newVariablesSection,
    $oldPrompt
);

$flow['global_prompt'] = $newPrompt;

echo "✅ Global Prompt updated:\n";
echo "   - Added: cancel_datum, cancel_uhrzeit\n";
echo "   - Added: old_datum, old_uhrzeit, new_datum, new_uhrzeit\n";
echo "   - Removed: booking_confirmed\n\n";

// ============================================================================
// STEP 3: Fix Stornierung Node
// ============================================================================

echo "📝 Step 3: Fixing Stornierung Node...\n";

$stornierungInstruction = "## WICHTIG: Prüfe bereits bekannte Daten!

**Bereits gesammelte Informationen:**
- Datum für Stornierung: {{cancel_datum}}
- Uhrzeit für Stornierung: {{cancel_uhrzeit}}

**Deine Aufgabe:**
1. **ANALYSIERE den Transcript** - Welchen Termin möchte der Kunde stornieren?
2. **PRÜFE die Variablen** - Welche sind noch leer?
3. **FRAGE NUR** nach fehlenden Daten!

**Fehlende Daten erkennen:**
- Wenn {{cancel_datum}} leer → Frage: \"Für welchen Tag möchten Sie stornieren?\" (heute/morgen/DD.MM.YYYY)
- Wenn {{cancel_uhrzeit}} leer → Frage: \"Um welche Uhrzeit war der Termin?\" (HH:MM)

**WENN Variable bereits gefüllt:**
- ✅ ÜBERSPRINGE die Frage komplett!
- Nutze den Wert aus der Variable

**Beispiel - User sagt alles:**
User: \"Ich möchte meinen Termin morgen um 14 Uhr stornieren\"
→ cancel_datum = \"morgen\"
→ cancel_uhrzeit = \"14:00\"
→ Antworte: \"Verstanden. Einen Moment, ich storniere Ihren Termin...\"
→ Transition zu func_cancel_appointment

**Beispiel - User sagt teilweise:**
User: \"Ich möchte einen Termin stornieren\"
→ Frage: \"Für welchen Tag möchten Sie stornieren?\"
→ User: \"Morgen\"
→ cancel_datum = \"morgen\"
→ Frage: \"Um welche Uhrzeit war der Termin?\"

**AKZEPTIERE natürliche Eingaben:**
- \"heute\", \"morgen\", \"Montag\", \"nächsten Freitag\"
- \"14 Uhr\", \"halb drei\", \"14:30\"

**Transition:**
- Sobald BEIDE Variablen gefüllt ({{cancel_datum}} AND {{cancel_uhrzeit}}) → func_cancel_appointment";

// Find and update the node
$stornierungUpdated = false;
foreach ($flow['nodes'] as &$node) {
    if ($node['id'] === 'node_collect_cancel_info') {
        $node['instruction'] = [
            'type' => 'prompt',
            'text' => $stornierungInstruction
        ];

        // Update edge condition
        if (isset($node['edges'][0])) {
            $node['edges'][0]['transition_condition'] = [
                'type' => 'prompt',
                'prompt' => 'ALL variables filled: {{cancel_datum}} AND {{cancel_uhrzeit}}'
            ];
        }

        $stornierungUpdated = true;
        echo "✅ Stornierung Node updated:\n";
        echo "   - Added state management\n";
        echo "   - Added skip logic for filled variables\n";
        echo "   - Updated transition condition\n\n";
        break;
    }
}

if (!$stornierungUpdated) {
    echo "⚠️  Warning: Stornierung node not found\n\n";
}

// ============================================================================
// STEP 4: Fix Verschiebung Node
// ============================================================================

echo "📝 Step 4: Fixing Verschiebung Node...\n";

$verschiebungInstruction = "## WICHTIG: Prüfe bereits bekannte Daten!

**Bereits gesammelte Informationen:**
- Alter Termin Datum: {{old_datum}}
- Alter Termin Uhrzeit: {{old_uhrzeit}}
- Neuer Termin Datum: {{new_datum}}
- Neuer Termin Uhrzeit: {{new_uhrzeit}}

**Deine Aufgabe:**
1. **ANALYSIERE den Transcript** - Welchen Termin möchte der Kunde verschieben und auf wann?
2. **PRÜFE die Variablen** - Welche sind noch leer?
3. **FRAGE NUR** nach fehlenden Daten!

**Fehlende Daten erkennen:**
- Wenn {{old_datum}} leer → Frage: \"Welcher Termin soll verschoben werden? An welchem Tag?\" (heute/morgen/DD.MM.YYYY)
- Wenn {{old_uhrzeit}} leer → Frage: \"Um welche Uhrzeit war der Termin?\" (HH:MM)
- Wenn {{new_datum}} leer → Frage: \"Auf welchen Tag möchten Sie verschieben?\" (heute/morgen/DD.MM.YYYY)
- Wenn {{new_uhrzeit}} leer → Frage: \"Um welche Uhrzeit?\" (HH:MM)

**WENN Variable bereits gefüllt:**
- ✅ ÜBERSPRINGE die Frage komplett!
- Nutze den Wert aus der Variable

**Beispiel - User sagt alles:**
User: \"Ich möchte meinen Termin morgen 14 Uhr auf Donnerstag 16 Uhr verschieben\"
→ old_datum = \"morgen\"
→ old_uhrzeit = \"14:00\"
→ new_datum = \"Donnerstag\"
→ new_uhrzeit = \"16:00\"
→ Antworte: \"Perfekt! Einen Moment, ich verschiebe den Termin...\"
→ Transition zu func_reschedule_appointment

**Beispiel - User sagt teilweise:**
User: \"Ich möchte meinen Termin verschieben\"
→ Frage: \"Welcher Termin soll verschoben werden? An welchem Tag?\"
→ User: \"Morgen 14 Uhr\"
→ old_datum = \"morgen\", old_uhrzeit = \"14:00\"
→ Frage: \"Auf welchen Tag möchten Sie verschieben?\"

**AKZEPTIERE natürliche Eingaben:**
- \"heute\", \"morgen\", \"Montag\", \"nächsten Freitag\"
- \"14 Uhr\", \"halb drei\", \"14:30\"

**Transition:**
- Sobald ALLE 4 Variablen gefüllt ({{old_datum}} AND {{old_uhrzeit}} AND {{new_datum}} AND {{new_uhrzeit}}) → func_reschedule_appointment";

// Find and update the node
$verschiebungUpdated = false;
foreach ($flow['nodes'] as &$node) {
    if ($node['id'] === 'node_collect_reschedule_info') {
        $node['instruction'] = [
            'type' => 'prompt',
            'text' => $verschiebungInstruction
        ];

        // Update edge condition
        if (isset($node['edges'][0])) {
            $node['edges'][0]['transition_condition'] = [
                'type' => 'prompt',
                'prompt' => 'ALL variables filled: {{old_datum}} AND {{old_uhrzeit}} AND {{new_datum}} AND {{new_uhrzeit}}'
            ];
        }

        $verschiebungUpdated = true;
        echo "✅ Verschiebung Node updated:\n";
        echo "   - Added state management\n";
        echo "   - Added skip logic for filled variables\n";
        echo "   - Updated transition condition\n\n";
        break;
    }
}

if (!$verschiebungUpdated) {
    echo "⚠️  Warning: Verschiebung node not found\n\n";
}

// ============================================================================
// STEP 5: Prepare Update Payload
// ============================================================================

echo "📦 Step 5: Preparing update payload...\n";

$updatePayload = [
    'global_prompt' => $flow['global_prompt'],
    'nodes' => $flow['nodes']
];

// Save preview
file_put_contents('/tmp/flow_v15_preview.json', json_encode($updatePayload, JSON_PRETTY_PRINT));
echo "💾 Preview saved: /tmp/flow_v15_preview.json\n\n";

// ============================================================================
// STEP 6: Apply Update via API
// ============================================================================

echo "🚀 Step 6: Applying updates to Retell API...\n\n";

$ch = curl_init("https://api.retellai.com/update-conversation-flow/{$flowId}");
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

    echo "✅ ✅ ✅ SUCCESS! Flow updated to V{$result['version']}\n\n";

    // Save final version
    file_put_contents('/tmp/flow_v15_final.json', json_encode($result, JSON_PRETTY_PRINT));
    echo "💾 Final version saved: /tmp/flow_v15_final.json\n\n";

    echo str_repeat("=", 80) . "\n";
    echo "📋 APPLIED CHANGES SUMMARY\n";
    echo str_repeat("=", 80) . "\n\n";

    echo "✅ Fix 1: Global Prompt\n";
    echo "   - Added 6 new variables for Stornierung/Verschiebung\n";
    echo "   - Removed unused 'booking_confirmed'\n\n";

    echo "✅ Fix 2: Stornierung Node (node_collect_cancel_info)\n";
    echo "   - Added state management (checks existing variables)\n";
    echo "   - Added skip logic (no double-asking)\n";
    echo "   - Updated transition condition\n\n";

    echo "✅ Fix 3: Verschiebung Node (node_collect_reschedule_info)\n";
    echo "   - Added state management (checks existing variables)\n";
    echo "   - Added skip logic (no double-asking)\n";
    echo "   - Updated transition condition\n\n";

    echo str_repeat("=", 80) . "\n";
    echo "🎯 NEXT STEPS\n";
    echo str_repeat("=", 80) . "\n\n";

    echo "1. ✅ Flow V{$result['version']} created\n";
    echo "2. ⏳ Update Agent to use V{$result['version']}\n";
    echo "3. ⏳ Publish Agent\n";
    echo "4. ⏳ Test all 3 flows:\n";
    echo "   - Buchung: \"Herrenhaarschnitt morgen 16 Uhr, Hans Schuster\"\n";
    echo "   - Stornierung: \"Ich möchte meinen Termin morgen 14 Uhr stornieren\"\n";
    echo "   - Verschiebung: \"Morgen 14 Uhr auf Donnerstag 16 Uhr verschieben\"\n\n";

    echo "📌 Flow Version: {$result['version']}\n";
    echo "📌 Is Published: " . ($result['is_published'] ? "YES" : "NO (DRAFT)") . "\n\n";

    if (!$result['is_published']) {
        echo "⚠️  Flow is DRAFT - Agent update will use this version but needs publish for production\n";
    }

    exit(0);

} else {
    echo "❌ ERROR: HTTP {$httpCode}\n";
    echo "Response: {$response}\n\n";

    echo "🔍 Debugging Info:\n";
    echo "   - Check /tmp/flow_v15_preview.json for payload\n";
    echo "   - Check /tmp/flow_v14_backup.json for original\n";
    echo "   - Payload size: " . strlen(json_encode($updatePayload)) . " bytes\n";

    exit(1);
}
