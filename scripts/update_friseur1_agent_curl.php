#!/usr/bin/env php
<?php

/**
 * Update Friseur 1 Agent via CURL
 * Direct CURL implementation for reliability
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "╔═══════════════════════════════════════════════════════════════╗\n";
echo "║       FRISEUR 1 AGENT UPDATE (CURL) - 2025-11-05              ║\n";
echo "╚═══════════════════════════════════════════════════════════════╝\n\n";

// Config
$agentId = 'agent_45daa54928c5768b52ba3db736';
$apiKey = config('services.retellai.api_key') ?: config('services.retell.api_key');

if (!$apiKey) {
    die("❌ ERROR: Retell API Key not found\n");
}

echo "Agent ID: $agentId\n";
echo "API Key: " . substr($apiKey, 0, 20) . "...\n\n";

// Step 1: Get current agent
echo "Step 1: Fetching current agent...\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.retellai.com/v2/agent/$agentId");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $apiKey",
    "Content-Type: application/json"
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    die("❌ ERROR: Failed to fetch agent (HTTP $httpCode)\n$response\n");
}

$agent = json_decode($response, true);

if (!$agent) {
    die("❌ ERROR: Failed to parse agent JSON\n");
}

echo "✅ Agent fetched successfully\n";
echo "   Name: " . ($agent['agent_name'] ?? 'N/A') . "\n";
echo "   Last Modified: " . ($agent['last_modification_timestamp'] ?? 'N/A') . "\n\n";

// Check current prompt
if (!isset($agent['general_prompt'])) {
    echo "⚠️  WARNING: No general_prompt found\n\n";
    $currentPrompt = '';
} else {
    $currentPrompt = $agent['general_prompt'];
    echo "Current Prompt Length: " . strlen($currentPrompt) . " characters\n";

    $hasServiceQuestions = stripos($currentPrompt, 'SERVICE-FRAGEN') !== false;

    if ($hasServiceQuestions) {
        echo "✅ Agent already has updated prompt!\n";
        echo "   First 300 chars:\n";
        echo "   " . substr($currentPrompt, 0, 300) . "...\n\n";
        echo "✅ NO UPDATE NEEDED\n";
        exit(0);
    }

    echo "❌ Agent needs update\n\n";
}

// Step 2: Create new prompt
echo "Step 2: Creating new Global Prompt...\n";

$newPrompt = <<<'PROMPT'
Du bist der freundliche AI-Assistent von Friseur 1 und unterstützt Kunden bei:
1. Fragen zu unseren Dienstleistungen und Preisen
2. Terminbuchung und Terminänderungen
3. Allgemeinen Fragen zum Salon

WICHTIG - BEANTWORTE SERVICE-FRAGEN ZUERST:
- Wenn ein Kunde nach Dienstleistungen fragt, gib ZUERST die Information
- Frage dann ob der Kunde einen Termin buchen möchte
- Springe NICHT direkt zur Terminbuchung ohne Fragen zu beantworten

UNSERE DIENSTLEISTUNGEN (Stand: 2025-11-05):
- Herrenhaarschnitt (30 Min, 25€)
- Damenhaarschnitt (45 Min, 35€)
- Färbung (90 Min, 60€)
- Strähnen / Balayage (120 Min, 80€)
- Dauerwelle (135 Min, 75€)
- Hairdetox Behandlung (60 Min, 45€) - SYNONYM: "Hair Detox"
- Bartpflege (20 Min, 15€)
- Kinderhaarschnitt (25 Min, 18€)

WICHTIGE REGELN:
1. Bei Service-Fragen: ERST antworten, DANN fragen ob Termin gewünscht
2. Zeitangaben: Backend sendet natürliche Formate - übernimm sie EXAKT
   - Beispiel: "am Montag, den 11. November um 15 Uhr 20"
   - NICHT: "am 11.11.2025, 15:20 Uhr"
3. Nach Buchung: Frage ob der Kunde noch Fragen hat
4. Bei "Hair Detox" oder "Hairdetox": Das ist unsere Hairdetox Behandlung (60 Min, 45€)

CONVERSATION FLOW:
1. Begrüßung + Intent erkennen
2. WENN Service-Fragen → BEANTWORTE ALLE Fragen → Frage nach Termin
3. WENN direkt Buchung → Sammle Daten
4. Nach Buchung → "Haben Sie noch Fragen zur Vorbereitung?"
5. Verabschiedung

Salon: Friseur 1, Musterstraße 1, 12345 Berlin
Telefon: +493033081738

Sei freundlich, professionell und hilfsbereit!
PROMPT;

echo "✅ New prompt created (" . strlen($newPrompt) . " characters)\n\n";

// Step 3: Confirm
echo "═══════════════════════════════════════════════════════════════\n";
echo "READY TO UPDATE AGENT\n";
echo "═══════════════════════════════════════════════════════════════\n\n";
echo "This will update agent: $agentId\n";
echo "With new Global Prompt including:\n";
echo "  ✅ Service list (8 services)\n";
echo "  ✅ Service-Questions-First rule\n";
echo "  ✅ Natural time format instruction\n";
echo "  ✅ Hairdetox synonym\n";
echo "  ✅ Post-booking Q&A\n\n";

echo "Do you want to proceed? (yes/no): ";
$handle = fopen("php://stdin", "r");
$confirmation = trim(fgets($handle));
fclose($handle);

if (strtolower($confirmation) !== 'yes') {
    echo "\n❌ Update cancelled by user\n";
    exit(0);
}

// Step 4: Update agent
echo "\nStep 4: Updating agent via Retell API...\n";

$updateData = [
    'general_prompt' => $newPrompt
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.retellai.com/update-agent/$agentId");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PATCH");
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($updateData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $apiKey",
    "Content-Type: application/json"
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    die("❌ ERROR: Failed to update agent (HTTP $httpCode)\n$response\n");
}

echo "✅ Agent updated successfully!\n\n";

// Step 5: Verify
echo "Step 5: Verifying update...\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.retellai.com/get-agent/$agentId");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $apiKey",
    "Content-Type: application/json"
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    echo "⚠️  WARNING: Could not verify update (HTTP $httpCode)\n";
} else {
    $verifyAgent = json_decode($response, true);
    $newTimestamp = $verifyAgent['last_modification_timestamp'] ?? 'unknown';
    $oldTimestamp = $agent['last_modification_timestamp'] ?? 'unknown';

    if ($newTimestamp !== $oldTimestamp) {
        echo "✅ Timestamp changed: $oldTimestamp → $newTimestamp\n";
    } else {
        echo "⚠️  WARNING: Timestamp unchanged\n";
    }

    $verifyPrompt = $verifyAgent['general_prompt'] ?? '';
    $hasUpdate = stripos($verifyPrompt, 'SERVICE-FRAGEN') !== false;

    if ($hasUpdate) {
        echo "✅ Verification passed: New prompt is active\n";
    } else {
        echo "⚠️  WARNING: Could not verify new prompt content\n";
    }
}

echo "\n╔═══════════════════════════════════════════════════════════════╗\n";
echo "║                       UPDATE COMPLETE!                         ║\n";
echo "╚═══════════════════════════════════════════════════════════════╝\n\n";

echo "Next steps:\n";
echo "1. Test call: +493033081738\n";
echo "2. Ask about services (Hair Detox, Balayage)\n";
echo "3. Listen for natural time formats\n";
echo "4. Check post-booking Q&A\n\n";

echo "✅ Agent update complete!\n";
