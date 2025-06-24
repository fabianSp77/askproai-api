#!/usr/bin/env php
<?php

/**
 * Update Retell.ai Agent Prompt with optimized version
 * This script updates the agent to:
 * 1. NOT ask for phone numbers (auto-captured)
 * 2. Better handle email addresses
 * 3. Proper error handling
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Company;

$optimizedPrompt = <<<'PROMPT'
Du bist ein freundlicher Telefonassistent für AskProAI. Deine Aufgabe ist es, Terminbuchungen entgegenzunehmen.

WICHTIGE REGELN:

1. **Telefonnummer NICHT erfragen** - Die Telefonnummer des Anrufers wird automatisch erfasst. Frage NIEMALS nach der Telefonnummer!

2. **Email-Adressen korrekt erfassen**:
   - Buchstabiere Email-Adressen zurück zur Bestätigung
   - Bei "at" oder "@" verwende das @-Zeichen
   - Beispiel: "fabian at askpro punkt ai" → "fabian@askpro.ai"
   - Bei Unklarheiten nachfragen und buchstabieren lassen

3. **Pflichtinformationen sammeln**:
   - Name des Kunden
   - Gewünschte Dienstleistung  
   - Datum (mindestens heute)
   - Uhrzeit (mindestens 2 Stunden in der Zukunft)
   - Email-Adresse (optional aber empfohlen für Bestätigung)

4. **Bei Fehlern oder fehlenden Informationen**:
   - Frage freundlich nach den fehlenden Informationen
   - Wiederhole wichtige Daten zur Bestätigung
   - Bei Email-Fehlern: Lass den Kunden buchstabieren

5. **Vor der Buchung**:
   - Fasse ALLE Daten nochmal zusammen
   - Warte auf explizite Bestätigung ("Ja", "Korrekt", "Stimmt")
   - Erst NACH Bestätigung die collect_appointment_data Funktion aufrufen

6. **Nach der Buchung**:
   - Bestätige die erfolgreiche Buchung
   - Erwähne dass eine Bestätigung per Email kommt (falls Email angegeben)
   - Verabschiede dich freundlich

WICHTIG: Verwende beim Funktionsaufruf IMMER "caller_number" für das Feld telefonnummer!
PROMPT;

echo "Retell.ai Agent Prompt Optimizer\n";
echo "================================\n\n";

// Get companies with Retell API keys
$companies = Company::whereNotNull('retell_api_key')->get();

if ($companies->isEmpty()) {
    echo "No companies with Retell API keys found.\n";
    exit(1);
}

echo "Found {$companies->count()} companies with Retell integration.\n\n";

foreach ($companies as $company) {
    echo "Company: {$company->name}\n";
    
    if (!$company->retell_agent_id) {
        echo "  ⚠️  No Retell Agent ID configured\n\n";
        continue;
    }
    
    echo "  Agent ID: {$company->retell_agent_id}\n";
    echo "  ℹ️  Please update the agent prompt in Retell.ai dashboard\n";
    echo "  📋 Prompt saved to: retell-prompts/{$company->id}-prompt.txt\n\n";
    
    // Save prompt to file
    $dir = base_path('retell-prompts');
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    
    file_put_contents(
        "{$dir}/{$company->id}-prompt.txt",
        $optimizedPrompt . "\n\n" .
        "Company: {$company->name}\n" .
        "Agent ID: {$company->retell_agent_id}\n"
    );
}

echo "\nCustom Function Configuration:\n";
echo "=============================\n";
echo "Name: collect_appointment_data\n";
echo "URL: https://api.askproai.de/api/retell/collect-appointment\n";
echo "Method: POST\n";
echo "Headers: Content-Type: application/json\n\n";

echo "Parameter Mapping:\n";
echo "- datum: Terminsdatum (z.B. '24.06.2025')\n";
echo "- uhrzeit: Uhrzeit (z.B. '16:30')\n";
echo "- name: Kundenname\n";
echo "- telefonnummer: IMMER 'caller_number' verwenden!\n";
echo "- dienstleistung: Gewünschte Leistung\n";
echo "- email: Email-Adresse (optional)\n\n";

echo "✅ Done! Please update the agents in Retell.ai dashboard.\n";