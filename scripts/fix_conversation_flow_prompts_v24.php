<?php

/**
 * Fix Conversation Flow Prompts - V24
 *
 * ROOT CAUSE: Prompts don't analyze user's latest message BEFORE checking variables
 * SOLUTION: Instruct agent to extract info from transcript first, avoid redundant questions
 */

$apiKey = 'key_6ff998ba48e842092e04a5455d19';
$flowId = 'conversation_flow_a58405e3f67a';

echo "🔧 FIXING CONVERSATION FLOW PROMPTS\n";
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
echo "   ✅ Current Version: V{$flow['version']}\n\n";

// Step 2: Fix node_collect_booking_info prompt
echo "2️⃣  Fixing 'Buchungsdaten sammeln' prompt...\n";

$improvedCollectPrompt = <<<'PROMPT'
## SCHRITT 1: ANALYSIERE USER'S AKTUELLE AUSSAGE

**Prüfe ZUERST was der User GERADE gesagt hat:**
- Lies die letzte User-Nachricht im Transcript
- Extrahiere ALLE vorhandenen Informationen
- Setze diese Informationen in die Variablen

**Beispiele für Extraktion:**
User: "Herrenhaarschnitt für morgen 9 Uhr, Schuster"
→ Erkenne: Name="Schuster", Service="Herrenhaarschnitt", Datum="morgen", Zeit="9 Uhr"

User: "Um 06:55"
→ Erkenne: Zeit="06:55"

## SCHRITT 2: PRÜFE BEREITS GESETZTE VARIABLEN

**Bereits gesammelte Informationen:**
- Name: {{customer_name}}
- Service: {{service_name}}
- Datum: {{appointment_date}}
- Uhrzeit: {{appointment_time}}

## SCHRITT 3: FRAGE NUR NACH FEHLENDEN DATEN

**NUR wenn eine Variable WIRKLICH leer ist:**
- Wenn {{customer_name}} leer → "Wie ist Ihr Name?"
- Wenn {{service_name}} leer → "Welche Dienstleistung möchten Sie?" (Herrenhaarschnitt/Damenhaarschnitt/Färben)
- Wenn {{appointment_date}} leer → "Für welchen Tag?" (heute/morgen/DD.MM.YYYY)
- Wenn {{appointment_time}} leer → "Um wie viel Uhr?" (HH:MM)

**NIEMALS redundante Fragen:**
❌ "Ist es morgen, wie Sie gesagt haben?" (wenn User schon "morgen" sagte)
❌ "Sie haben gesagt, um neun Uhr, richtig?" (wenn User schon "neun Uhr" sagte)
✅ Nutze die Info direkt!

## SCHRITT 4: TRANSITION

**Sobald ALLE 4 Variablen gefüllt sind:**
→ Sage: "Perfekt! Einen Moment, ich prüfe die Verfügbarkeit..."
→ Transition zu func_check_availability

**Akzeptiere natürliche Eingaben:**
- "heute", "morgen", "Montag", "nächsten Freitag" → als Datum
- "15 Uhr", "halb drei", "14:30", "neun" → als Uhrzeit
PROMPT;

foreach ($flow['nodes'] as $key => $node) {
    if ($node['id'] === 'node_collect_booking_info') {
        $flow['nodes'][$key]['instruction']['text'] = $improvedCollectPrompt;
        echo "   ✅ Updated node_collect_booking_info instruction\n\n";
        break;
    }
}

// Step 3: Fix node_present_result prompt
echo "3️⃣  Fixing 'Ergebnis zeigen' prompt...\n";

$improvedPresentPrompt = <<<'PROMPT'
Zeige das Ergebnis der Verfügbarkeitsprüfung:

**WENN VERFÜGBAR:**
"Der Termin am {{appointment_date}} um {{appointment_time}} für {{service_name}} ist verfügbar. Soll ich den Termin für Sie buchen?"

**WENN NICHT VERFÜGBAR mit ALTERNATIVEN:**
Präsentiere die Alternativen EINMAL klar und knapp.
Beispiel: "Leider ist {{appointment_date}} um {{appointment_time}} nicht verfügbar. Ich habe jedoch folgende Alternativen für Sie: [Liste]. Welcher Termin würde Ihnen besser passen?"

**WICHTIG - Wenn User Alternative wählt:**
- User sagt z.B. "Um 06:55" oder "Den ersten Termin"
- ✅ AKZEPTIERE SOFORT - keine erneute Bestätigung!
- ✅ UPDATE {{appointment_time}} mit der neuen Zeit
- ✅ Sage einfach: "Einen Moment, ich prüfe die Verfügbarkeit..."
- ✅ Transition direkt zurück zu func_check_availability

**NUR wenn User explizit buchen möchte:**
- "Ja", "Gerne", "Buchen Sie", "Passt" → func_book_appointment

**KEINE redundanten Bestätigungen wie:**
❌ "Also, um das klarzustellen: Sie möchten den Termin..."
❌ "Ist das richtig?"
✅ Vertraue dem User - wenn er eine Zeit nennt, nutze sie!
PROMPT;

foreach ($flow['nodes'] as $key => $node) {
    if ($node['id'] === 'node_present_result') {
        $flow['nodes'][$key]['instruction']['text'] = $improvedPresentPrompt;
        echo "   ✅ Updated node_present_result instruction\n\n";
        break;
    }
}

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

    echo str_repeat('=', 80) . "\n";
    echo "✅ SUCCESS! Conversation flow prompts fixed!\n\n";

    echo "IMPROVEMENTS:\n";
    echo "1. ✅ Agent now analyzes user's LATEST message FIRST\n";
    echo "2. ✅ Extracts all information from user's statement\n";
    echo "3. ✅ Only asks for TRULY missing data\n";
    echo "4. ✅ No redundant confirmations when user selects alternative\n";
    echo "5. ✅ Natural conversation flow\n\n";

    echo "NEXT STEPS:\n";
    echo "1. Publish as V24: php scripts/publish_agent_v24.php\n";
    echo "2. Run test call\n";
    echo "3. Verify natural conversation without redundant questions\n";

} else {
    echo "   ❌ Update failed! HTTP {$httpCode}\n";
    echo "   Response: {$updateResponse}\n";
}
