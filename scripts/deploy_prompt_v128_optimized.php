<?php
/**
 * Deploy Retell Agent Prompt V128 - Optimiert
 *
 * Änderungen gegenüber V127:
 * 1. Name-Skip für Bestandskunden (Agent fragt nicht mehr nach bekanntem Namen)
 * 2. Intelligente Alternativen-Kommunikation (Vormittag→Abend Hinweis)
 * 3. Vollständige Buchungsbestätigung mit allen Details
 * 4. Verbesserte Filler-Phrases
 * 5. Stille-Handling mit Auto-Hangup
 *
 * Usage: php scripts/deploy_prompt_v128_optimized.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$apiKey = $_ENV['RETELLAI_API_KEY'] ?? $_ENV['RETELL_TOKEN'] ?? null;
$baseUrl = rtrim($_ENV['RETELLAI_BASE_URL'] ?? $_ENV['RETELL_BASE_URL'] ?? 'https://api.retell.ai', '/');

if (!$apiKey) {
    die("❌ RETELLAI_API_KEY not configured in .env\n");
}

// The actual conversation flow ID from the agent config
$conversationFlowId = 'conversation_flow_ea64387d34e4';

echo "🚀 Deploying Retell Agent Prompt V128 - Optimized\n";
echo "=================================================\n\n";

// Step 1: Get current conversation flow
echo "📥 Step 1: Fetching current conversation flow...\n";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => "$baseUrl/v2/get-conversation-flow/$conversationFlowId",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer $apiKey",
        "Content-Type: application/json"
    ]
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    die("❌ Failed to fetch conversation flow (HTTP $httpCode): $response\n");
}

$flowData = json_decode($response, true);
$currentPrompt = $flowData['global_prompt'] ?? '';

echo "✅ Current flow fetched\n";
echo "   Current prompt length: " . strlen($currentPrompt) . " chars\n\n";

// Step 2: Build the V128 optimized prompt additions
echo "📝 Step 2: Building V128 optimizations...\n";

$v128Additions = <<<'PROMPT'

## V128 KRITISCHE OPTIMIERUNGEN (2025-12-14)

### REGEL 1: NAME-SKIP FÜR BESTANDSKUNDEN
```
WENN customer_found = true UND customer.name ist vorhanden:
  → FRAGE NIEMALS nach dem Namen!
  → Verwende {{customer.name}} direkt für die Buchung
  → Der Kunde erwartet, dass du ihn kennst!
```

**BEISPIEL:**
```
[customer_found=true, customer.name="Hans Schuster"]
❌ FALSCH: "Alles klar, wie darf ich Sie noch nennen?"
✅ RICHTIG: "Alles klar, ich buche den Termin für Hans Schuster."
```

### REGEL 2: ZEIT-SHIFT KOMMUNIKATION
```
WENN Kunde "Vormittag" wollte UND nur Abend-Termine verfügbar:
  → Erwähne explizit, dass Vormittag ausgebucht ist
  → Frage ob nächster Tag Vormittag gewünscht oder heute Abend OK
```

**BEISPIEL:**
```
Kunde: "Dienstag Vormittag"
[Nur 20:45 und 21:40 verfügbar]
❌ FALSCH: "Ich kann Ihnen 20:45 oder 21:40 anbieten."
✅ RICHTIG: "Vormittags ist Dienstag leider ausgebucht.
            Soll ich am Mittwoch Vormittag schauen,
            oder würde heute Abend passen?
            Heute hätte ich noch 20:45 oder 21:40 frei."
```

### REGEL 3: VOLLSTÄNDIGE BUCHUNGSBESTÄTIGUNG
```
Bei jeder Buchung IMMER bestätigen mit:
  - Service-Name
  - Dauer in Minuten
  - Wochentag + Datum
  - Uhrzeit
  - Kundenname
```

**BEISPIEL:**
```
✅ "Perfekt! Ihr Termin für Herrenhaarschnitt (45 Minuten)
    am Dienstag, den 15. Dezember um 20:45 Uhr
    ist für Hans Schuster gebucht.
    Kann ich sonst noch etwas für Sie tun?"
```

### REGEL 4: VOLLSTÄNDIGE FILLER-PHRASES
```
Vor jedem API-Call einen VOLLSTÄNDIGEN Satz sprechen:
  ✅ "Einen Moment bitte, ich prüfe die Verfügbarkeit für Sie."
  ✅ "Ich schaue kurz nach freien Terminen."
  ✅ "Moment, ich trage das für Sie ein."

  ❌ NIEMALS abgehackt: "Ich schaue" [Pause] "Die Wunschzeit..."
```

### REGEL 5: STILLE-HANDLING
```
WENN Kunde > 20 Sekunden nicht antwortet:
  → "Sind Sie noch da? Ich helfe Ihnen gerne weiter."

WENN danach nochmal > 20 Sekunden Stille:
  → "Falls Sie gerade beschäftigt sind, rufen Sie gerne wieder an. Auf Wiederhören!"
  → Gespräch beenden

NIEMALS endlos wiederholen!
```

PROMPT;

// Step 3: Check if V128 rules already exist
if (strpos($currentPrompt, 'V128 KRITISCHE OPTIMIERUNGEN') !== false) {
    echo "⚠️  V128 rules already present in prompt. Skipping update.\n";
    echo "   To force update, remove V128 section from current prompt first.\n";
    exit(0);
}

// Step 4: Prepend V128 rules to existing prompt
$newPrompt = $v128Additions . "\n\n" . $currentPrompt;

echo "✅ New prompt built\n";
echo "   New prompt length: " . strlen($newPrompt) . " chars\n";
echo "   Added: " . strlen($v128Additions) . " chars of V128 rules\n\n";

// Step 5: Update the conversation flow
echo "📤 Step 3: Updating conversation flow with V128 prompt...\n";

$updatePayload = [
    'global_prompt' => $newPrompt
];

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => "$baseUrl/v2/update-conversation-flow/$conversationFlowId",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST => 'PATCH',
    CURLOPT_POSTFIELDS => json_encode($updatePayload),
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer $apiKey",
        "Content-Type: application/json"
    ]
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    die("❌ Failed to update conversation flow (HTTP $httpCode): $response\n");
}

echo "✅ Conversation flow updated successfully!\n\n";

// Step 6: Verify the update
echo "🔍 Step 4: Verifying update...\n";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => "$baseUrl/v2/get-conversation-flow/$conversationFlowId",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer $apiKey",
        "Content-Type: application/json"
    ]
]);

$response = curl_exec($ch);
$verifyData = json_decode($response, true);
curl_close($ch);

$verifiedPrompt = $verifyData['global_prompt'] ?? '';
if (strpos($verifiedPrompt, 'V128 KRITISCHE OPTIMIERUNGEN') !== false) {
    echo "✅ V128 rules verified in live prompt!\n";
} else {
    echo "⚠️  V128 rules NOT found in verified prompt. Please check manually.\n";
}

echo "\n";
echo "=================================================\n";
echo "🎉 DEPLOYMENT COMPLETE - V128 Optimized\n";
echo "=================================================\n";
echo "\n";
echo "Änderungen:\n";
echo "  1. ✅ Name-Skip für Bestandskunden\n";
echo "  2. ✅ Zeit-Shift Kommunikation (Vormittag→Abend)\n";
echo "  3. ✅ Vollständige Buchungsbestätigung\n";
echo "  4. ✅ Vollständige Filler-Phrases\n";
echo "  5. ✅ Stille-Handling mit Auto-Hangup\n";
echo "\n";
echo "Nächste Schritte:\n";
echo "  - Testanruf durchführen und Transcript prüfen\n";
echo "  - Log-Monitoring: tail -f storage/logs/calcom-*.log\n";
