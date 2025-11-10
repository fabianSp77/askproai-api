#!/usr/bin/env php
<?php

/**
 * Check and Update Friseur 1 Agent
 *
 * FIX 2025-11-05: Actually update the agent via API, not just document it
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\RetellApiClient;
use Illuminate\Support\Facades\DB;

echo "╔═══════════════════════════════════════════════════════════════╗\n";
echo "║       FRISEUR 1 AGENT CHECK & UPDATE - 2025-11-05             ║\n";
echo "╚═══════════════════════════════════════════════════════════════╝\n\n";

// Step 1: Get Friseur 1 Branch
echo "Step 1: Finding Friseur 1 branch...\n";

$branch = DB::table('branches')
    ->join('companies', 'branches.company_id', '=', 'companies.id')
    ->where('companies.name', 'Friseur 1')
    ->select('branches.*', 'companies.name as company_name')
    ->first();

if (!$branch) {
    echo "❌ ERROR: Friseur 1 branch not found!\n";
    exit(1);
}

echo "✅ Found: {$branch->company_name} - {$branch->name}\n";
echo "   Branch ID: {$branch->id}\n";
echo "   Agent ID: {$branch->retell_agent_id}\n\n";

// Step 2: Get current agent config
echo "Step 2: Fetching current agent config from Retell...\n";

$client = new RetellApiClient();
$agent = $client->getAgent($branch->retell_agent_id);

if (!$agent) {
    echo "❌ ERROR: Failed to fetch agent from Retell API!\n";
    exit(1);
}

echo "✅ Agent fetched successfully\n";
echo "   Name: " . ($agent['agent_name'] ?? 'N/A') . "\n";
echo "   Voice: " . ($agent['voice_id'] ?? 'N/A') . "\n";
echo "   Last Modified: " . ($agent['last_modification_timestamp'] ?? 'N/A') . "\n\n";

// Step 3: Check current prompt
echo "Step 3: Analyzing current prompt...\n";

if (!isset($agent['general_prompt'])) {
    echo "⚠️  WARNING: No general_prompt found in agent config\n\n";
    $currentPrompt = '';
} else {
    $currentPrompt = $agent['general_prompt'];
    $promptLength = strlen($currentPrompt);
    echo "   Current Prompt Length: {$promptLength} characters\n";

    // Check what's in the prompt
    $hasServiceInfo = stripos($currentPrompt, 'Dienstleistungen') !== false ||
                      stripos($currentPrompt, 'services') !== false;
    $hasServiceList = stripos($currentPrompt, 'Herrenhaarschnitt') !== false ||
                      stripos($currentPrompt, 'Damenhaarschnitt') !== false;
    $hasHairdetox = stripos($currentPrompt, 'Hairdetox') !== false ||
                    stripos($currentPrompt, 'Hair Detox') !== false;
    $hasServiceQuestions = stripos($currentPrompt, 'SERVICE-FRAGEN') !== false;

    echo "   Has Service Info: " . ($hasServiceInfo ? '✅' : '❌') . "\n";
    echo "   Has Service List: " . ($hasServiceList ? '✅' : '❌') . "\n";
    echo "   Has Hairdetox: " . ($hasHairdetox ? '✅' : '❌') . "\n";
    echo "   Has Service-Questions Rule: " . ($hasServiceQuestions ? '✅' : '❌') . "\n\n";

    if ($hasServiceQuestions && $hasServiceList) {
        echo "✅ Agent already has the updated prompt!\n";
        echo "   Last modified: " . ($agent['last_modification_timestamp'] ?? 'unknown') . "\n\n";

        echo "First 500 chars of current prompt:\n";
        echo "─────────────────────────────────────\n";
        echo substr($currentPrompt, 0, 500) . "...\n";
        echo "─────────────────────────────────────\n\n";

        echo "✅ NO UPDATE NEEDED\n";
        exit(0);
    }
}

// Step 4: Create new prompt
echo "Step 4: Creating new Global Prompt...\n";

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

echo "✅ New prompt created ({strlen($newPrompt)} characters)\n\n";

// Step 5: Ask for confirmation
echo "═══════════════════════════════════════════════════════════════\n";
echo "READY TO UPDATE AGENT\n";
echo "═══════════════════════════════════════════════════════════════\n\n";
echo "This will update agent: {$branch->retell_agent_id}\n";
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

// Step 6: Update agent
echo "\nStep 6: Updating agent via Retell API...\n";

$updateData = [
    'general_prompt' => $newPrompt
];

$result = $client->updateAgent($branch->retell_agent_id, $updateData);

if (!$result) {
    echo "❌ ERROR: Failed to update agent!\n";
    echo "Check logs for details\n";
    exit(1);
}

echo "✅ Agent updated successfully!\n\n";

// Step 7: Verify update
echo "Step 7: Verifying update...\n";

$verifyAgent = $client->getAgent($branch->retell_agent_id);

if (!$verifyAgent) {
    echo "⚠️  WARNING: Could not verify update (fetch failed)\n";
} else {
    $newTimestamp = $verifyAgent['last_modification_timestamp'] ?? 'unknown';
    $oldTimestamp = $agent['last_modification_timestamp'] ?? 'unknown';

    if ($newTimestamp !== $oldTimestamp) {
        echo "✅ Timestamp changed: {$oldTimestamp} → {$newTimestamp}\n";
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
