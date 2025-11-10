#!/usr/bin/env php
<?php

/**
 * Add UX Improvements to Global Prompt
 *
 * FIXES:
 * - P1: Service Disambiguierung (Herren vs. Damen Haarschnitt)
 * - P0: Proaktive Terminvorschläge bei offener Verfügbarkeitsanfrage
 *
 * CREATED: 2025-11-05
 * BASED ON: Test Call call_3aa2c23a5f45c874a674b59106c
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo " Add UX Improvements to Global Prompt\n";
echo " Flow ID: conversation_flow_a58405e3f67a\n";
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

// STEP 2: Prepare new sections
echo "📝 Step 2: Preparing UX improvement sections...\n";

$serviceDisambiguation = <<<'EOD'

## ⚠️ KRITISCH: Service-Disambiguierung

**Bei mehrdeutigen Service-Anfragen IMMER nachfragen:**

### Services die Klärung brauchen:

**"Haarschnitt" oder "Schnitt":**
- Kunde sagt: "Ich möchte einen Haarschnitt"
- ✅ RICHTIG: "Gerne! Möchten Sie einen Herrenhaarschnitt (32€, 55 Min) oder Damenhaarschnitt (45€, 45 Min)?"
- ❌ FALSCH: Einfach annehmen es ist Herrenhaarschnitt oder Damenhaarschnitt

**"Föhnen" oder "Styling":**
- Kunde sagt: "Ich brauche Föhnen"
- ✅ RICHTIG: "Föhnen & Styling für Damen (32€, 30 Min) oder Herren (20€, 20 Min)?"
- ❌ FALSCH: Ohne Nachfrage einen davon wählen

### Eindeutige Services (KEINE Nachfrage nötig):
- "Herrenhaarschnitt" → eindeutig
- "Damenhaarschnitt" → eindeutig
- "Balayage", "Färben", "Dauerwelle", "Hairdetox" → alle eindeutig

**WICHTIG:**
1. ✅ Nur bei mehrdeutigen Keywords nachfragen
2. ✅ Dem Kunden beide Optionen mit Preis und Dauer zeigen
3. ✅ Warte auf Auswahl bevor du weitermachst
4. ❌ NIEMALS einfach einen Service annehmen wenn es mehrere Möglichkeiten gibt

**Beispiel-Dialog:**
```
User: "Ich möchte einen Termin für heute für Haarschnitt"
Agent: "Gerne! Möchten Sie einen Herrenhaarschnitt (32€, 55 Min) oder
        Damenhaarschnitt (45€, 45 Min)?"
User: "Herrenhaarschnitt"
Agent: "Perfekt! Wann möchten Sie heute kommen?"
```

EOD;

$proactiveAvailability = <<<'EOD'

## ⚠️ KRITISCH: Proaktive Terminvorschläge

**Wenn Kunde nach Verfügbarkeit fragt OHNE konkrete Uhrzeit zu nennen:**

### Trigger-Phrases (erkenne diese!):
- "Was haben Sie heute noch frei?"
- "Wann haben Sie noch Termine?"
- "Welche Zeiten sind verfügbar?"
- "Haben Sie heute/morgen noch was frei?"
- "Wann kann ich kommen?"
- "Was ist noch möglich?"

### RICHTIGES Verhalten:

**Schritt 1: Erkenne offene Verfügbarkeitsanfrage**
- Kunde fragt nach Verfügbarkeit
- Kunde nennt KEINE konkrete Uhrzeit
- → Das ist dein Signal für proaktive Vorschläge!

**Schritt 2: Rufe check_availability auf**
- Mit Datum (z.B. "heute", "morgen", "Freitag")
- Mit Service (wenn schon bekannt)
- OHNE Uhrzeit (das ist der Schlüssel!)

**Schritt 3: Zeige verfügbare Zeiten**
- Liste 3-5 verfügbare Slots
- Natürliche Sprache: "um 14:00, 16:30 und 18:00 Uhr"
- Frage: "Welche Zeit würde Ihnen passen?"

**Schritt 4: Buche gewählte Zeit**
- Kunde wählt eine Zeit
- Buche diese Zeit
- Fertig!

### Beispiel-Dialog:
```
User: "Haben Sie heute noch was frei für Damenhaarschnitt?"
Agent: [ruft check_availability(service=Damenhaarschnitt, datum=heute) auf]
Agent: "Ja! Für Damenhaarschnitt haben wir heute noch um 14:00, 16:30 und 18:00 Uhr frei.
        Welche Zeit würde Ihnen am besten passen?"
User: "16:30 passt"
Agent: [bucht 16:30]
Agent: "Perfekt! Ihr Termin für Damenhaarschnitt heute um 16:30 ist gebucht."
```

### NIEMALS:
❌ "Um wie viel Uhr möchten Sie kommen?" wenn Kunde nach Verfügbarkeit fragt
❌ Den Kunden zwingen eine Zeit zu nennen BEVOR du Verfügbarkeit checkst
❌ Die Verfügbarkeitsfrage ignorieren und einfach nach Uhrzeit fragen
❌ Mehr als 3x nach Uhrzeit fragen wenn Kunde nach Verfügbarkeit fragt

### Unterschied verstehen:

**Fall A: Kunde nennt Uhrzeit**
```
User: "Ich möchte um 16:00 kommen"
→ Check ob 16:00 verfügbar ist
→ Wenn nicht: Alternativen anbieten
```

**Fall B: Kunde fragt nach Verfügbarkeit**
```
User: "Was ist noch frei?"
→ SOFORT verfügbare Zeiten zeigen (3-5 Optionen)
→ Kunde wählt
→ Diese Zeit buchen
```

**WICHTIG:** Fall B ist das häufige Szenario! Kunden wissen oft NICHT wann sie kommen wollen und brauchen Optionen!

EOD;

// STEP 3: Insert new sections after date context
echo "🔧 Step 3: Inserting new sections into prompt...\n";

// Find position after date context block
$lines = explode("\n", $currentPrompt);
$insertPosition = 0;

for ($i = 0; $i < count($lines); $i++) {
    // Insert after the date examples (after "Irgendein Datum in 2023")
    if (strpos($lines[$i], 'Irgendein Datum in 2023') !== false) {
        $insertPosition = $i + 1;
        break;
    }
}

if ($insertPosition === 0) {
    echo "❌ ERROR: Could not find insertion point\n";
    exit(1);
}

// Insert new sections
$newSections = explode("\n", $serviceDisambiguation . $proactiveAvailability);
array_splice($lines, $insertPosition, 0, $newSections);
$updatedPrompt = implode("\n", $lines);

echo "✅ New sections added\n";
echo "📋 New Length: " . strlen($updatedPrompt) . " characters\n";
echo "📊 Added: " . (strlen($updatedPrompt) - strlen($currentPrompt)) . " characters\n";
echo "\n";

// STEP 4: Update conversation flow
echo "🚀 Step 4: Updating conversation flow via API...\n";

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
echo "✅ SUCCESS! Conversation flow updated\n";
echo "📋 New Version: " . ($result['version'] ?? 'unknown') . "\n";
echo "\n";

// STEP 5: Verify sections were added
echo "🔍 Step 5: Verifying new sections...\n";

$verifyResponse = Http::withHeaders([
    'Authorization' => "Bearer {$retellApiKey}",
    'Content-Type' => 'application/json'
])->get("{$baseUrl}/get-conversation-flow/{$conversationFlowId}");

$verifyFlow = $verifyResponse->json();
$verifyPrompt = $verifyFlow['global_prompt'] ?? '';

$checks = [
    'Service-Disambiguierung Header' => strpos($verifyPrompt, 'KRITISCH: Service-Disambiguierung') !== false,
    'Herrenhaarschnitt vs Damenhaarschnitt' => strpos($verifyPrompt, 'Herrenhaarschnitt (32€, 55 Min) oder Damenhaarschnitt') !== false,
    'Proaktive Terminvorschläge Header' => strpos($verifyPrompt, 'KRITISCH: Proaktive Terminvorschläge') !== false,
    'Trigger-Phrases Section' => strpos($verifyPrompt, 'Trigger-Phrases') !== false,
    'check_availability Instruction' => strpos($verifyPrompt, 'ruft check_availability') !== false,
];

foreach ($checks as $checkName => $result) {
    echo ($result ? '✅' : '❌') . " {$checkName}\n";
}

echo "\n";

$passedChecks = count(array_filter($checks));
$totalChecks = count($checks);

if ($passedChecks === $totalChecks) {
    echo "═══════════════════════════════════════════════════════════════\n";
    echo " ✅ SUCCESS - UX Improvements Added\n";
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "\n";
    echo "🎯 Fixes Applied:\n";
    echo "\n";
    echo "1. ✅ Service-Disambiguierung Rules\n";
    echo "   - Agent fragt jetzt nach Herren vs. Damen bei 'Haarschnitt'\n";
    echo "   - Zeigt Preise und Dauer für beide Optionen\n";
    echo "   - Wartet auf Kundenauswahl\n";
    echo "\n";
    echo "2. ✅ Proaktive Terminvorschläge Rules\n";
    echo "   - Agent erkennt offene Verfügbarkeitsanfragen\n";
    echo "   - Ruft check_availability auf (ohne Uhrzeit)\n";
    echo "   - Zeigt 3-5 verfügbare Zeiten\n";
    echo "   - Lässt Kunde wählen\n";
    echo "\n";
    echo "🎯 Conversation Flow:\n";
    echo "   - Version: " . ($result['version'] ?? 'unknown') . "\n";
    echo "   - Prompt Length: " . strlen($verifyPrompt) . " characters\n";
    echo "\n";
    echo "📞 Next Steps:\n";
    echo "1. Agent muss neu publiziert werden (Draft → Live)\n";
    echo "2. Test Call mit beiden Szenarien:\n";
    echo "   a) 'Haarschnitt buchen' → prüfe Herren/Damen Frage\n";
    echo "   b) 'Was ist heute noch frei?' → prüfe Terminvorschläge\n";
    echo "\n";
} else {
    echo "⚠️  Some checks failed ({$passedChecks}/{$totalChecks} passed)\n";
    exit(1);
}
