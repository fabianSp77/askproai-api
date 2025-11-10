<?php
/**
 * Fix Flow V103 - Booking UX Problems
 *
 * Fixes:
 * 1. Node "Buchungsdaten sammeln" - Don't say "Perfekt! Ich buche" BEFORE availability check
 * 2. Node "Ergebnis zeigen" - Only say "Perfekt" if actually available
 * 3. Prevent duplicate questions
 */

$apiKey = 'key_6ff998ba48e842092e04a5455d19';
$flowId = 'conversation_flow_a58405e3f67a';

echo "=== FIX FLOW V103 - BOOKING UX ===\n\n";

// Get current flow
echo "1. Fetching current flow...\n";
$ch = curl_init("https://api.retellai.com/get-conversation-flow/{$flowId}");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer {$apiKey}",
        "Content-Type: application/json"
    ]
]);

$response = curl_exec($ch);
$flow = json_decode($response, true);
curl_close($ch);

echo "   Current Version: V{$flow['version']}\n";
echo "   Published: " . ($flow['is_published'] ? 'YES' : 'NO') . "\n\n";

// Fix 1: node_collect_booking_info - Change instruction
echo "2. Fixing node_collect_booking_info...\n";
foreach ($flow['nodes'] as &$node) {
    if ($node['id'] === 'node_collect_booking_info') {
        $oldInstruction = $node['instruction']['text'] ?? 'N/A';

        // Change to simple "Einen Moment" message
        $node['instruction'] = [
            'type' => 'prompt',
            'text' => "WICHTIG: Prüfe welche Daten bereits bekannt sind!\n\n" .
                     "**Bereits extrahierte Variablen:**\n" .
                     "- Name: {{customer_name}}\n" .
                     "- Service: {{service_name}}\n" .
                     "- Datum: {{appointment_date}}\n" .
                     "- Uhrzeit: {{appointment_time}}\n\n" .
                     "**Deine Aufgabe:**\n" .
                     "1. PRÜFE welche Variablen bereits gefüllt sind\n" .
                     "2. Frage NUR nach FEHLENDEN Informationen\n" .
                     "3. Wenn ALLE 4 Variablen gefüllt sind:\n" .
                     "   → Sage: \"Einen Moment, ich prüfe die Verfügbarkeit...\"\n" .
                     "   → Transition SOFORT zu func_check_availability\n\n" .
                     "**NIEMALS sagen:**\n" .
                     "- \"Perfekt! Ich buche jetzt...\" ❌ (ERST nach availability check!)\n" .
                     "- Nach Daten fragen die bereits bekannt sind ❌\n\n" .
                     "**Beispiel - User hat alles gesagt:**\n" .
                     "User: \"Hans Schuster, Herrenhaarschnitt am Dienstag um 9 Uhr\"\n" .
                     "→ Alle Variablen gefüllt\n" .
                     "→ Sage: \"Einen Moment, ich prüfe die Verfügbarkeit...\"\n" .
                     "→ Transition zu func_check_availability"
        ];

        echo "   ✅ Fixed: node_collect_booking_info\n";
        echo "      Old: " . substr($oldInstruction, 0, 50) . "...\n";
        echo "      New: Einen Moment, ich prüfe die Verfügbarkeit...\n\n";
        break;
    }
}

// Fix 2: node_present_result - Clarify when to say "Perfekt"
echo "3. Fixing node_present_result...\n";
foreach ($flow['nodes'] as &$node) {
    if ($node['id'] === 'node_present_result') {
        $node['instruction'] = [
            'type' => 'prompt',
            'text' => "Zeige das Ergebnis der Verfügbarkeitsprüfung:\n\n" .
                     "**FALL 1: Exakter Wunschtermin VERFÜGBAR (Tool returned available:true):**\n" .
                     "\"Perfekt! Ihr Wunschtermin am {{appointment_date}} um {{appointment_time}} ist verfügbar. Ich buche jetzt für Sie...\"\n" .
                     "→ Transition SOFORT zu func_start_booking (KEINE Rückfrage!)\n\n" .
                     "**FALL 2: Wunschtermin NICHT verfügbar, aber Alternativen vorhanden:**\n" .
                     "\"Ihr Wunschtermin ist leider nicht verfügbar. Ich habe aber folgende Alternativen für Sie: [Nenne maximal 2-3 aus Tool-Response]. Welcher würde Ihnen passen?\"\n" .
                     "→ Warte auf Kundenauswahl\n" .
                     "→ Transition zu node_present_alternatives\n\n" .
                     "**FALL 3: Wunschtermin NICHT verfügbar UND keine Alternativen:**\n" .
                     "\"Leider ist {{appointment_date}} um {{appointment_time}} nicht verfügbar. Einen Moment, ich suche nach weiteren Möglichkeiten...\"\n" .
                     "→ Transition zu func_get_alternatives\n\n" .
                     "**KRITISCH:**\n" .
                     "- NUR bei available:true → \"Perfekt! Ich buche jetzt\"\n" .
                     "- Bei available:false → NIEMALS \"Perfekt\" sagen!\n" .
                     "- Datum OHNE Jahr (z.B. \"Montag, 11. November\" nicht \"11.11.2025\")"
        ];

        echo "   ✅ Fixed: node_present_result\n";
        echo "      Only says 'Perfekt! Ich buche' when available:true\n\n";
        break;
    }
}

// Fix 3: node_collect_missing_data - Better instruction
echo "4. Fixing node_collect_missing_data...\n";
foreach ($flow['nodes'] as &$node) {
    if ($node['id'] === 'node_collect_missing_data') {
        $node['instruction'] = [
            'type' => 'prompt',
            'text' => "Der Buchungsversuch ist fehlgeschlagen weil der Kundenname fehlt.\n\n" .
                     "Frage: \"Darf ich noch Ihren vollständigen Namen haben?\"\n\n" .
                     "WICHTIG:\n" .
                     "- NUR nach Namen fragen (Telefon/Email sind optional)\n" .
                     "- Wenn User Name nennt → setze {{customer_name}}\n" .
                     "- Transition zu func_start_booking"
        ];

        echo "   ✅ Fixed: node_collect_missing_data\n\n";
        break;
    }
}

// Fix 4: node_collect_callback_info - Prevent duplicate questions
echo "5. Fixing node_collect_callback_info...\n";
foreach ($flow['nodes'] as &$node) {
    if ($node['id'] === 'node_collect_callback_info') {
        $node['instruction'] = [
            'type' => 'prompt',
            'text' => "**Bereits bekannt:**\n" .
                     "- Name: {{customer_name}}\n" .
                     "- Service: {{service_name}}\n" .
                     "- Phone: {{customer_phone}}\n\n" .
                     "**Setze callback_reason automatisch:**\n" .
                     "\"Termin für {{service_name}} buchen\"\n\n" .
                     "**Prüfe und frage NUR wenn fehlt:**\n" .
                     "1. Wenn {{customer_phone}} LEER:\n" .
                     "   → \"Unter welcher Nummer können wir Sie erreichen?\"\n" .
                     "2. (Optional) Bevorzugte Zeit:\n" .
                     "   → \"Gibt es eine bevorzugte Zeit für den Rückruf?\"\n\n" .
                     "**NIEMALS:**\n" .
                     "- Nach Name fragen wenn {{customer_name}} bereits gefüllt ❌\n" .
                     "- Nach Service fragen wenn {{service_name}} bereits gefüllt ❌\n\n" .
                     "**Transition:**\n" .
                     "Sobald Name + Phone vorhanden → func_request_callback"
        ];

        echo "   ✅ Fixed: node_collect_callback_info\n";
        echo "      Won't ask for name/service if already known\n\n";
        break;
    }
}

// Fix 5: Global prompt - Add anti-repetition rules
echo "6. Updating global_prompt...\n";
$flow['global_prompt'] .= "\n\n## ANTI-DUPLICATE-QUESTIONS (KRITISCH)\n" .
    "NIEMALS nach Daten fragen die bereits bekannt sind!\n\n" .
    "Wenn {{customer_name}} gefüllt → NICHT nochmal nach Name fragen\n" .
    "Wenn {{service_name}} gefüllt → NICHT nochmal nach Service fragen\n" .
    "Wenn {{appointment_date}} gefüllt → NICHT nochmal nach Datum fragen\n" .
    "Wenn {{appointment_time}} gefüllt → NICHT nochmal nach Zeit fragen\n\n" .
    "Bei Callback:\n" .
    "- Wenn {{customer_phone}} gefüllt → NICHT nochmal nach Telefon fragen\n" .
    "- callback_reason automatisch setzen: \"Termin für {{service_name}} buchen\"\n\n" .
    "VERSION: V103 (2025-11-09 No Duplicate Questions + UX Fix)";

echo "   ✅ Updated global_prompt with anti-duplicate rules\n\n";

// Update flow via API
echo "7. Updating flow via API...\n";
$ch = curl_init("https://api.retellai.com/update-conversation-flow/{$flowId}");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST => 'PATCH',
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer {$apiKey}",
        "Content-Type: application/json"
    ],
    CURLOPT_POSTFIELDS => json_encode($flow)
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    $result = json_decode($response, true);
    echo "   ✅ SUCCESS! Flow updated to V{$result['version']}\n\n";

    echo "=== CHANGES SUMMARY ===\n\n";
    echo "1. ✅ node_collect_booking_info: Now says 'Einen Moment, ich prüfe...'\n";
    echo "2. ✅ node_present_result: Only says 'Perfekt!' when available:true\n";
    echo "3. ✅ node_collect_missing_data: Better instruction\n";
    echo "4. ✅ node_collect_callback_info: Won't ask duplicate questions\n";
    echo "5. ✅ global_prompt: Added anti-duplicate rules\n\n";

    echo "🚨 NEXT STEP:\n";
    echo "   Du musst Flow V{$result['version']} im Dashboard publishen!\n";
    echo "   URL: https://dashboard.retellai.com/\n\n";

    // Save response to file
    file_put_contents(
        __DIR__ . '/../conversation_flow_v103_fixed.json',
        json_encode($result, JSON_PRETTY_PRINT)
    );
    echo "   📝 Saved to: conversation_flow_v103_fixed.json\n\n";

} else {
    echo "   ❌ FEHLER beim Update (HTTP {$httpCode})\n";
    echo "   Response: {$response}\n\n";
}

echo "=== END FIX ===\n";
