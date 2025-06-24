#!/usr/bin/env php
<?php

/**
 * Generate Retell Custom Function Configuration
 * This script creates the JSON configuration for the collect_appointment_data function
 */

echo "\n=== RETELL CUSTOM FUNCTION GENERATOR ===\n\n";

// Define the custom function
$customFunction = [
    "name" => "collect_appointment_data",
    "description" => "Sammelt alle notwendigen Informationen für eine Terminbuchung vom Anrufer. Diese Funktion sollte aufgerufen werden, sobald alle Termininformationen gesammelt wurden.",
    "parameters" => [
        [
            "name" => "datum",
            "type" => "string",
            "description" => "Das gewünschte Datum für den Termin im Format TT.MM.JJJJ (z.B. 25.06.2025)",
            "required" => true
        ],
        [
            "name" => "uhrzeit",
            "type" => "string", 
            "description" => "Die gewünschte Uhrzeit im Format HH:MM (z.B. 14:30)",
            "required" => true
        ],
        [
            "name" => "name",
            "type" => "string",
            "description" => "Der vollständige Name des Kunden",
            "required" => true
        ],
        [
            "name" => "telefonnummer",
            "type" => "string",
            "description" => "Die Telefonnummer des Kunden für Rückrufe",
            "required" => true
        ],
        [
            "name" => "dienstleistung",
            "type" => "string",
            "description" => "Die gewünschte Dienstleistung (z.B. Haarschnitt, Beratung, etc.)",
            "required" => true
        ],
        [
            "name" => "email",
            "type" => "string",
            "description" => "Die E-Mail-Adresse des Kunden für die Terminbestätigung (optional)",
            "required" => false
        ],
        [
            "name" => "mitarbeiter_wunsch",
            "type" => "string",
            "description" => "Bevorzugter Mitarbeiter, falls der Kunde einen Wunsch hat (optional)",
            "required" => false
        ],
        [
            "name" => "kundenpraeferenzen",
            "type" => "string",
            "description" => "Zusätzliche Wünsche oder Anmerkungen des Kunden (optional)",
            "required" => false
        ]
    ],
    "returns" => [
        "type" => "object",
        "description" => "Bestätigung der gesammelten Daten"
    ]
];

// Agent Prompt Template
$agentPrompt = "Du bist ein freundlicher Assistent für [FIRMENNAME]. Deine Hauptaufgabe ist es, Anrufer bei der Terminbuchung zu unterstützen.

WICHTIGE ANWEISUNGEN FÜR TERMINBUCHUNGEN:

1. Sammle ALLE notwendigen Informationen:
   - Gewünschtes Datum (frage nach einem konkreten Datum)
   - Gewünschte Uhrzeit (frage nach einer konkreten Uhrzeit)
   - Name des Kunden
   - Telefonnummer (bestätige die Nummer vom Anrufer)
   - Gewünschte Dienstleistung
   - Optional: E-Mail-Adresse
   - Optional: Mitarbeiterwunsch
   - Optional: Besondere Wünsche

2. NACHDEM du alle Pflichtinformationen gesammelt hast, verwende IMMER die Funktion 'collect_appointment_data' mit den gesammelten Daten.

3. Bestätige dem Kunden die eingegebenen Daten und informiere ihn, dass er eine Bestätigung per E-Mail erhält (falls E-Mail angegeben).

Beispiel-Dialog:
Kunde: 'Ich möchte einen Termin für einen Haarschnitt buchen.'
Du: 'Gerne helfe ich Ihnen bei der Terminbuchung für einen Haarschnitt. An welchem Tag hätten Sie denn gerne einen Termin?'
Kunde: 'Am Freitag.'
Du: 'An welchem Freitag genau? Zum Beispiel der 28. Juni?'
Kunde: 'Ja, am 28. Juni.'
Du: 'Perfekt, am 28. Juni. Um welche Uhrzeit würde es Ihnen passen?'
[... sammle weitere Informationen ...]

WICHTIG: Rufe die Funktion collect_appointment_data NUR auf, wenn du ALLE Pflichtfelder hast!";

// Output the configuration
echo "CUSTOM FUNCTION CONFIGURATION:\n";
echo str_repeat('=', 60) . "\n\n";
echo json_encode($customFunction, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

echo "\n\n";
echo "AGENT PROMPT TEMPLATE:\n";
echo str_repeat('=', 60) . "\n\n";
echo $agentPrompt;

echo "\n\n";
echo "INTEGRATION STEPS:\n";
echo str_repeat('=', 60) . "\n";
echo "
1. Log in to Retell.ai Dashboard
2. Navigate to your agent configuration
3. Add the custom function above to 'Custom Functions'
4. Update the agent prompt with the template above
5. Set webhook URL to: https://api.askproai.de/api/retell/webhook
6. Enable webhook events: call_started, call_ended, call_analyzed
7. Save and test with a phone call

TESTING CHECKLIST:
[ ] Custom function added
[ ] Agent prompt updated
[ ] Webhook URL configured
[ ] Webhook events enabled
[ ] Test call successful
[ ] Check logs: tail -f storage/logs/laravel.log | grep -i retell
";

echo "\n\n";

// Save to file for easy copying
$configFile = __DIR__ . '/retell-custom-function-config.json';
file_put_contents($configFile, json_encode($customFunction, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "✅ Configuration saved to: $configFile\n";

$promptFile = __DIR__ . '/retell-agent-prompt.txt';
file_put_contents($promptFile, $agentPrompt);
echo "✅ Prompt template saved to: $promptFile\n";

echo "\n";