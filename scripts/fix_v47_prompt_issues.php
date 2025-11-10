#!/usr/bin/env php
<?php

/**
 * Fix V47 Prompt Issues
 *
 * FIXES:
 * 1. Entferne Preise/Dauer aus Service-Disambiguierung Beispiel
 * 2. Entferne Beispielzeiten (14:00, 16:30, 18:00) aus Proaktive Terminvorschläge
 * 3. Füge Tool-Call Enforcement hinzu
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo " Fix V47 Prompt Issues\n";
echo " Time: " . Carbon::now('Europe/Berlin')->format('Y-m-d H:i:s') . "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "\n";

$retellApiKey = config('services.retellai.api_key');
$conversationFlowId = 'conversation_flow_a58405e3f67a';
$baseUrl = rtrim(config('services.retellai.base_url', 'https://api.retellai.com'), '/');

// STEP 1: Get current prompt
echo "🔍 Step 1: Fetching current conversation flow...\n";

$response = Http::withHeaders([
    'Authorization' => "Bearer {$retellApiKey}",
    'Content-Type' => 'application/json'
])->get("{$baseUrl}/get-conversation-flow/{$conversationFlowId}");

if (!$response->successful()) {
    echo "❌ ERROR: Failed to fetch conversation flow\n";
    exit(1);
}

$flow = $response->json();
$currentPrompt = $flow['global_prompt'] ?? '';

echo "✅ Current prompt fetched\n";
echo "📋 Current Length: " . strlen($currentPrompt) . " characters\n";
echo "\n";

// STEP 2: Apply fixes
echo "📝 Step 2: Applying fixes...\n\n";

$updatedPrompt = $currentPrompt;

// FIX 1: Service-Disambiguierung - Preise/Dauer entfernen
echo "🔧 Fix 1: Entferne Preise/Dauer aus Service-Disambiguierung Beispiel\n";

$oldServiceExample = '- ✅ RICHTIG: "Gerne! Möchten Sie einen Herrenhaarschnitt (32€, 55 Min) oder Damenhaarschnitt (45€, 45 Min)?"';
$newServiceExample = '- ✅ RICHTIG: "Gerne! Möchten Sie einen Herrenhaarschnitt oder Damenhaarschnitt?"';

if (strpos($updatedPrompt, $oldServiceExample) !== false) {
    $updatedPrompt = str_replace($oldServiceExample, $newServiceExample, $updatedPrompt);
    echo "   ✅ Service-Disambiguierung Beispiel aktualisiert\n";
} else {
    echo "   ⚠️  Alter Text nicht gefunden\n";
}

// Add wichtig notice after the example
$afterServiceExample = $newServiceExample . "\n- ❌ FALSCH: Einfach annehmen es ist Herrenhaarschnitt oder Damenhaarschnitt";
$newServiceExampleWithNotice = $newServiceExample . "\n\n**⚠️ WICHTIG:** Preise und Dauer NUR auf explizite Nachfrage nennen!\n- Kunde fragt: \"Was kostet ein Herrenhaarschnitt?\" → Dann nenne Preis (32€)\n- Kunde fragt: \"Wie lange dauert das?\" → Dann nenne Dauer (55 Min)\n- Sonst: NUR Service-Namen nennen!\n\n- ❌ FALSCH: Einfach annehmen es ist Herrenhaarschnitt oder Damenhaarschnitt";

$updatedPrompt = str_replace($afterServiceExample, $newServiceExampleWithNotice, $updatedPrompt);
echo "   ✅ Preis-Notice hinzugefügt\n";

echo "\n";

// FIX 2: Proaktive Terminvorschläge - Beispielzeiten entfernen
echo "🔧 Fix 2: Entferne Beispielzeiten + füge Tool-Enforcement hinzu\n";

$oldStepThree = '**Schritt 3: Zeige verfügbare Zeiten**
- Liste 3-5 verfügbare Slots
- Natürliche Sprache: "um 14:00, 16:30 und 18:00 Uhr"
- Frage: "Welche Zeit würde Ihnen passen?"';

$newStepThree = '**Schritt 3: Zeige verfügbare Zeiten AUS DER TOOL RESPONSE**
- ⚠️ KRITISCH: Zeige NUR Zeiten die check_availability zurückgegeben hat!
- ❌ NIEMALS eigene Zeiten erfinden oder aus Beispielen kopieren!
- Liste 3-5 verfügbare Slots aus der Tool Response
- Natürliche Sprache: "um [Zeit1], [Zeit2] und [Zeit3] Uhr"
- Frage: "Welche Zeit würde Ihnen passen?"';

if (strpos($updatedPrompt, $oldStepThree) !== false) {
    $updatedPrompt = str_replace($oldStepThree, $newStepThree, $updatedPrompt);
    echo "   ✅ Schritt 3 aktualisiert (Beispielzeiten entfernt)\n";
} else {
    echo "   ⚠️  Schritt 3 Text nicht gefunden\n";
}

echo "\n";

// FIX 3: Add Tool-Call Enforcement section at the beginning
echo "🔧 Fix 3: Füge Tool-Call Enforcement Sektion hinzu\n";

$toolEnforcement = <<<'EOD'

## ⚠️ PFLICHT: Tool Calls für Verfügbarkeit

**NIEMALS Verfügbarkeit erfinden!**

Wenn Kunde nach freien Terminen fragt:
1. ✅ DU MUSST check_availability CALLEN
2. ✅ Auf Tool Response warten
3. ✅ NUR Zeiten aus Response nennen
4. ❌ NIEMALS eigene Zeiten erfinden
5. ❌ NIEMALS Beispielzeiten aus diesem Prompt verwenden

**Das Tool gibt dir die ECHTEN verfügbaren Zeiten zurück - verwende NUR diese!**

**Beispiel RICHTIGES Verhalten:**
```
User: "Was ist heute frei?"
→ Du callst: check_availability(service="Herrenhaarschnitt", datum="heute")
→ Tool antwortet: ["19:00", "19:30", "20:00"]
→ Du sagst: "Für Herrenhaarschnitt haben wir heute um 19:00, 19:30 und 20:00 Uhr frei."
```

**Beispiel FALSCHES Verhalten:**
```
User: "Was ist heute frei?"
→ Du sagst: "Um 14:00, 16:30 und 18:00 Uhr" ❌ OHNE Tool zu callen!
```

EOD;

// Insert after "KRITISCH: Proaktive Terminvorschläge" header
$proactiveHeader = "## ⚠️ KRITISCH: Proaktive Terminvorschläge\n";
if (strpos($updatedPrompt, $proactiveHeader) !== false) {
    $updatedPrompt = str_replace(
        $proactiveHeader,
        $toolEnforcement . "\n" . $proactiveHeader,
        $updatedPrompt
    );
    echo "   ✅ Tool-Call Enforcement Sektion hinzugefügt\n";
} else {
    echo "   ⚠️  Proaktive Terminvorschläge Header nicht gefunden\n";
}

echo "\n";
echo "📊 Changes Summary:\n";
echo "   - Original Length: " . strlen($currentPrompt) . " characters\n";
echo "   - New Length: " . strlen($updatedPrompt) . " characters\n";
echo "   - Difference: " . (strlen($updatedPrompt) - strlen($currentPrompt)) . " characters\n";
echo "\n";

// STEP 3: Update conversation flow
echo "🚀 Step 3: Updating conversation flow via API...\n";

$updatePayload = [
    'global_prompt' => $updatedPrompt
];

$response = Http::withHeaders([
    'Authorization' => "Bearer {$retellApiKey}",
    'Content-Type' => 'application/json'
])->patch("{$baseUrl}/update-conversation-flow/{$conversationFlowId}", $updatePayload);

if (!$response->successful()) {
    echo "❌ ERROR: Failed to update conversation flow (HTTP {$response->status()})\n";
    echo "Response: {$response->body()}\n";
    exit(1);
}

$result = $response->json();
echo "✅ Conversation flow updated!\n";
echo "\n";

// STEP 4: Verify fixes
echo "🔍 Step 4: Verifying fixes...\n\n";

$verifyResponse = Http::withHeaders([
    'Authorization' => "Bearer {$retellApiKey}",
    'Content-Type' => 'application/json'
])->get("{$baseUrl}/get-conversation-flow/{$conversationFlowId}");

$verifyFlow = $verifyResponse->json();
$verifyPrompt = $verifyFlow['global_prompt'] ?? '';

$checks = [
    'Service ohne Preise' => strpos($verifyPrompt, 'Herrenhaarschnitt oder Damenhaarschnitt?"') !== false,
    'Preis-Notice vorhanden' => strpos($verifyPrompt, 'Preise und Dauer NUR auf explizite Nachfrage') !== false,
    'Keine Beispielzeiten (14:00)' => strpos($verifyPrompt, 'um 14:00, 16:30 und 18:00 Uhr') === false,
    'Tool-Enforcement vorhanden' => strpos($verifyPrompt, 'PFLICHT: Tool Calls für Verfügbarkeit') !== false,
    'Check_availability Pflicht' => strpos($verifyPrompt, 'DU MUSST check_availability CALLEN') !== false,
];

foreach ($checks as $checkName => $result) {
    echo ($result ? '✅' : '❌') . " {$checkName}\n";
}

echo "\n";

$passedChecks = count(array_filter($checks));
$totalChecks = count($checks);

if ($passedChecks === $totalChecks) {
    echo "═══════════════════════════════════════════════════════════════\n";
    echo " ✅ SUCCESS - V47 Fixes Applied\n";
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "\n";
    echo "🎯 Fixes Applied:\n";
    echo "\n";
    echo "1. ✅ Preise/Dauer aus Service-Disambiguierung Beispiel entfernt\n";
    echo "   - Nur noch: 'Herrenhaarschnitt oder Damenhaarschnitt'\n";
    echo "   - Preis-Notice hinzugefügt\n";
    echo "\n";
    echo "2. ✅ Beispielzeiten (14:00, 16:30, 18:00) entfernt\n";
    echo "   - Keine konkreten Beispielzeiten mehr\n";
    echo "   - Warnung gegen Erfinden von Zeiten hinzugefügt\n";
    echo "\n";
    echo "3. ✅ Tool-Call Enforcement hinzugefügt\n";
    echo "   - Explizite Anweisung: check_availability MUSS gecallt werden\n";
    echo "   - Beispiele für richtiges und falsches Verhalten\n";
    echo "\n";
    echo "📞 Next Steps:\n";
    echo "1. Agent muss neu publiziert werden → Draft V47\n";
    echo "2. Test Call mit allen 3 Szenarien:\n";
    echo "   a) 'Haarschnitt buchen' → Keine Preise/Dauer\n";
    echo "   b) 'Was ist heute frei?' → Agent callt check_availability\n";
    echo "   c) 'Was kostet Herrenhaarschnitt?' → Dann Preis nennen\n";
    echo "\n";
} else {
    echo "⚠️  Some checks failed ({$passedChecks}/{$totalChecks} passed)\n";
    exit(1);
}
