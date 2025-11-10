<?php

/**
 * Apply Flow Fixes Step by Step
 *
 * Strategy: Update in smaller chunks to avoid API timeout
 * 1. Global Prompt only
 * 2. Nodes only
 */

$flowId = 'conversation_flow_a58405e3f67a';
$apiKey = 'key_6ff998ba48e842092e04a5455d19';

echo "🔧 STEP-BY-STEP FLOW FIXES\n";
echo str_repeat("=", 80) . "\n\n";

// ============================================================================
// Load Flow
// ============================================================================

echo "📥 Loading Flow...\n";

$ch = curl_init("https://api.retellai.com/get-conversation-flow/{$flowId}");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 120); // 2 minute timeout
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer {$apiKey}",
    "Content-Type: application/json"
]);
$response = curl_exec($ch);
curl_close($ch);

$flow = json_decode($response, true);
echo "✅ Loaded Flow V{$flow['version']}\n\n";

// ============================================================================
// STEP 1: Update Global Prompt Only
// ============================================================================

echo "📝 STEP 1: Updating Global Prompt...\n";

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

$newPrompt = preg_replace(
    '/\*\*Du hast Zugriff auf Dynamic Variables:\*\*.*?(?=\n\n\*\*IMMER ZUERST)/s',
    $newVariablesSection,
    $flow['global_prompt']
);

$payload1 = ['global_prompt' => $newPrompt];

$ch = curl_init("https://api.retellai.com/update-conversation-flow/{$flowId}");
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PATCH");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 120);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer {$apiKey}",
    "Content-Type: application/json"
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload1));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200 || $httpCode === 201) {
    $result = json_decode($response, true);
    echo "✅ Global Prompt updated → V{$result['version']}\n\n";

    // Reload flow for next step
    $flow = $result;

} else {
    die("❌ ERROR: HTTP {$httpCode}\n{$response}\n");
}

// ============================================================================
// STEP 2: Update Stornierung Node
// ============================================================================

echo "📝 STEP 2: Updating Stornierung Node...\n";

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

**Transition:**
- Sobald BEIDE Variablen gefüllt ({{cancel_datum}} AND {{cancel_uhrzeit}}) → func_cancel_appointment";

foreach ($flow['nodes'] as &$node) {
    if ($node['id'] === 'node_collect_cancel_info') {
        $node['instruction'] = [
            'type' => 'prompt',
            'text' => $stornierungInstruction
        ];

        if (isset($node['edges'][0])) {
            $node['edges'][0]['transition_condition'] = [
                'type' => 'prompt',
                'prompt' => 'ALL variables filled: {{cancel_datum}} AND {{cancel_uhrzeit}}'
            ];
        }
        break;
    }
}

$payload2 = ['nodes' => $flow['nodes']];

$ch = curl_init("https://api.retellai.com/update-conversation-flow/{$flowId}");
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PATCH");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 120);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer {$apiKey}",
    "Content-Type: application/json"
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload2));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200 || $httpCode === 201) {
    $result = json_decode($response, true);
    echo "✅ Stornierung Node updated → V{$result['version']}\n\n";
    $flow = $result;
} else {
    die("❌ ERROR: HTTP {$httpCode}\n{$response}\n");
}

// ============================================================================
// STEP 3: Update Verschiebung Node
// ============================================================================

echo "📝 STEP 3: Updating Verschiebung Node...\n";

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

**Transition:**
- Sobald ALLE 4 Variablen gefüllt ({{old_datum}} AND {{old_uhrzeit}} AND {{new_datum}} AND {{new_uhrzeit}}) → func_reschedule_appointment";

foreach ($flow['nodes'] as &$node) {
    if ($node['id'] === 'node_collect_reschedule_info') {
        $node['instruction'] = [
            'type' => 'prompt',
            'text' => $verschiebungInstruction
        ];

        if (isset($node['edges'][0])) {
            $node['edges'][0]['transition_condition'] = [
                'type' => 'prompt',
                'prompt' => 'ALL variables filled: {{old_datum}} AND {{old_uhrzeit}} AND {{new_datum}} AND {{new_uhrzeit}}'
            ];
        }
        break;
    }
}

$payload3 = ['nodes' => $flow['nodes']];

$ch = curl_init("https://api.retellai.com/update-conversation-flow/{$flowId}");
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PATCH");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 120);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer {$apiKey}",
    "Content-Type: application/json"
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload3));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200 || $httpCode === 201) {
    $result = json_decode($response, true);
    echo "✅ Verschiebung Node updated → V{$result['version']}\n\n";

    file_put_contents('/tmp/flow_final.json', json_encode($result, JSON_PRETTY_PRINT));

    echo str_repeat("=", 80) . "\n";
    echo "🎉 ALL FIXES APPLIED SUCCESSFULLY!\n";
    echo str_repeat("=", 80) . "\n\n";

    echo "📌 Final Flow Version: V{$result['version']}\n";
    echo "📌 Is Published: " . ($result['is_published'] ? "YES" : "NO") . "\n\n";

    echo "✅ Fix 1: Global Prompt - 6 variables added\n";
    echo "✅ Fix 2: Stornierung Node - State management added\n";
    echo "✅ Fix 3: Verschiebung Node - State management added\n\n";

    echo "🎯 NEXT: Update Agent to use V{$result['version']}\n";

    exit(0);

} else {
    die("❌ ERROR: HTTP {$httpCode}\n{$response}\n");
}
