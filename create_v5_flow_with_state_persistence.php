<?php

/**
 * Create V5 Flow with State Persistence (Dynamic Variables)
 * Fixes UX #1: Redundant Data Collection
 */

echo "\n═══════════════════════════════════════════════════════════\n";
echo "🔧 CREATING V5 FLOW - State Persistence Fix\n";
echo "═══════════════════════════════════════════════════════════\n\n";

// Load V4 flow
echo "📖 Loading V4 flow...\n";
$v4Flow = json_decode(file_get_contents(__DIR__ . '/friseur1_conversation_flow_v4_complete.json'), true);

if (!$v4Flow) {
    die("❌ Failed to load V4 flow\n");
}

echo "✅ V4 loaded: " . count($v4Flow['nodes']) . " nodes\n\n";

// Step 1: Add Dynamic Variables
echo "🔧 Adding dynamic variables for state persistence...\n";
$v4Flow['dynamic_variables'] = [
    'customer_name' => '',
    'service_name' => '',
    'appointment_date' => '',
    'appointment_time' => '',
    'booking_confirmed' => 'false'
];
echo "✅ Dynamic variables added\n\n";

// Step 2: Update "Buchungsdaten sammeln" node
echo "🔧 Updating 'Buchungsdaten sammeln' node...\n";
foreach ($v4Flow['nodes'] as &$node) {
    if ($node['id'] === 'node_collect_booking_info') {
        $node['instruction']['text'] = "## WICHTIG: Prüfe bereits bekannte Daten!

**Bereits gesammelte Informationen:**
- Name: {{customer_name}}
- Service: {{service_name}}
- Datum: {{appointment_date}}
- Uhrzeit: {{appointment_time}}

**Deine Aufgabe:**
1. **ANALYSIERE den Transcript** - Was hat der Kunde bereits gesagt?
2. **PRÜFE die Variablen** - Welche sind noch leer?
3. **FRAGE NUR** nach fehlenden Daten!

**Fehlende Daten erkennen:**
- Wenn {{customer_name}} leer → Frage: \"Wie ist Ihr Name?\"
- Wenn {{service_name}} leer → Frage: \"Welche Dienstleistung möchten Sie?\" (Herrenhaarschnitt/Damenhaarschnitt/Färben)
- Wenn {{appointment_date}} leer → Frage: \"Für welchen Tag?\" (heute/morgen/DD.MM.YYYY)
- Wenn {{appointment_time}} leer → Frage: \"Um wie viel Uhr?\" (HH:MM)

**WENN Variable bereits gefüllt:**
- ✅ ÜBERSPRINGE die Frage komplett!
- Nutze den Wert aus der Variable

**Beispiel - User sagt alles:**
User: \"Herrenhaarschnitt für heute 15 Uhr, Hans Schuster\"
→ customer_name = \"Hans Schuster\"
→ service_name = \"Herrenhaarschnitt\"
→ appointment_date = \"heute\"
→ appointment_time = \"15:00\"
→ Antworte: \"Perfekt! Ich prüfe die Verfügbarkeit...\"
→ Transition zu func_check_availability

**Beispiel - User sagt teilweise:**
User: \"Ich möchte einen Herrenhaarschnitt\"
→ service_name = \"Herrenhaarschnitt\"
→ Frage NUR nach: Name, Datum, Uhrzeit

**AKZEPTIERE natürliche Eingaben:**
- \"heute\", \"morgen\", \"Montag\", \"nächsten Freitag\"
- \"15 Uhr\", \"halb drei\", \"14:30\"

**Transition:**
- Sobald ALLE 4 Variablen gefüllt → func_check_availability";

        echo "✅ Node 'Buchungsdaten sammeln' updated with variable-aware instructions\n";
    }
}
unset($node);

// Step 3: Update transition condition
echo "🔧 Updating transition conditions...\n";
foreach ($v4Flow['nodes'] as &$node) {
    if ($node['id'] === 'node_collect_booking_info') {
        foreach ($node['edges'] as &$edge) {
            if ($edge['destination_node_id'] === 'func_check_availability') {
                $edge['transition_condition']['prompt'] =
                    "ALL variables are filled (not empty): {{customer_name}} AND {{service_name}} AND {{appointment_date}} AND {{appointment_time}}";
                echo "✅ Transition condition updated to check variables\n";
            }
        }
    }
}
unset($node, $edge);

// Step 4: Update global prompt
echo "🔧 Updating global prompt...\n";
$v4Flow['global_prompt'] = "# Friseur 1 - Intelligenter Terminassistent V5 (State Persistence)

## Deine Rolle
Du bist der Terminassistent von **Friseur 1**.
Sprich freundlich und natürlich auf Deutsch.

## WICHTIG: State Management

**Du hast Zugriff auf Dynamic Variables:**
- {{customer_name}} - Name des Kunden
- {{service_name}} - Gewünschter Service
- {{appointment_date}} - Gewünschtes Datum
- {{appointment_time}} - Gewünschte Uhrzeit
- {{booking_confirmed}} - Buchungsstatus

**IMMER ZUERST PRÜFEN:**
1. Was steht bereits in den Variablen?
2. Was hat der Kunde im Transcript gesagt?
3. NUR nach FEHLENDEN Daten fragen!

## Services
- Herrenhaarschnitt (30 Min, 25€)
- Damenhaarschnitt (45 Min, 35€)
- Färben (90 Min, 65€)

## Du kannst helfen bei:
1. **Neuen Termin buchen** - Sammle fehlende Daten und buche
2. **Termine anzeigen** - Zeige bestehende Termine
3. **Termin stornieren** - Finde und storniere Termin
4. **Termin verschieben** - Verschiebe auf neues Datum/Uhrzeit
5. **Services auflisten** - Zeige was wir anbieten

## NIEMALS
- ❌ Verfügbarkeit raten oder erfinden
- ❌ Termin ohne Bestätigung buchen
- ❌ Nach Daten fragen die bereits bekannt sind (in Variablen)
- ❌ Tools manuell aufrufen (macht der Flow!)";

echo "✅ Global prompt updated with state awareness\n\n";

// Save V5 flow
echo "💾 Saving V5 flow...\n";
$v5Json = json_encode($v4Flow, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
file_put_contents(__DIR__ . '/friseur1_conversation_flow_v5_state_persistence.json', $v5Json);

echo "✅ V5 flow saved to: friseur1_conversation_flow_v5_state_persistence.json\n\n";

// Summary
echo "═══════════════════════════════════════════════════════════\n";
echo "✅ V5 FLOW CREATED SUCCESSFULLY\n";
echo "═══════════════════════════════════════════════════════════\n\n";

echo "📋 Changes Made:\n";
echo "   1. ✅ Added 5 dynamic_variables for state management\n";
echo "   2. ✅ Updated 'Buchungsdaten sammeln' node to check variables first\n";
echo "   3. ✅ Updated transition condition to verify all variables filled\n";
echo "   4. ✅ Updated global_prompt with state awareness instructions\n\n";

echo "🎯 What This Fixes:\n";
echo "   - ✅ UX #1: No more redundant questioning\n";
echo "   - ✅ Agent remembers data across conversation\n";
echo "   - ✅ If user says everything upfront, agent proceeds immediately\n\n";

echo "📦 Next Steps:\n";
echo "   1. Deploy V5 flow to Retell\n";
echo "   2. Publish agent\n";
echo "   3. Make test call: 'Herrenhaarschnitt für heute 15 Uhr, Hans Schuster'\n";
echo "   4. Verify: Agent should NOT ask for name/date/time again!\n\n";

echo "🚀 Ready to deploy with: php deploy_flow_v5_state_persistence.php\n\n";
