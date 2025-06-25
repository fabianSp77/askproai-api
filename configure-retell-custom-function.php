<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Services\RetellV2Service;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== RETELL CUSTOM FUNCTION CONFIGURATION GUIDE ===\n\n";

// Agent ID for "Assistent für Fabian Spitzer Rechtliches V33"
$agentId = 'agent_9a8202a740cd3120d96fcfda1e';

echo "Agent ID: $agentId\n";
echo "Agent Name: Assistent für Fabian Spitzer Rechtliches V33\n\n";

echo "WICHTIG: Die Custom Function muss manuell in der Retell.ai Oberfläche konfiguriert werden!\n\n";

echo "1. Gehe zu: https://app.retellai.com/\n";
echo "2. Navigiere zu 'Agents' und finde den Agent V33\n";
echo "3. Klicke auf 'Edit' oder 'Configure'\n";
echo "4. Scrolle zu 'Custom Functions' oder 'General Tools'\n";
echo "5. Füge eine neue Function hinzu mit folgenden Einstellungen:\n\n";

echo "=== CUSTOM FUNCTION CONFIGURATION ===\n\n";

echo "Function Name: collect_appointment_data\n";
echo "Description: Sammelt alle notwendigen Termindaten vom Anrufer\n";
echo "Type: remote_tool\n";
echo "Method: POST\n";
echo "URL: https://api.askproai.de/api/retell/collect-appointment\n\n";

echo "Headers:\n";
echo "  Content-Type: application/json\n\n";

echo "Parameters (Schema):\n";
$parameters = [
    'datum' => [
        'type' => 'string',
        'required' => true,
        'description' => 'Das Datum des gewünschten Termins (z.B. "morgen", "nächsten Montag", "15.03.2024")'
    ],
    'uhrzeit' => [
        'type' => 'string',
        'required' => true,
        'description' => 'Die gewünschte Uhrzeit (z.B. "10:00", "14:30", "nachmittags")'
    ],
    'name' => [
        'type' => 'string',
        'required' => true,
        'description' => 'Der vollständige Name des Kunden'
    ],
    'telefonnummer' => [
        'type' => 'string',
        'required' => true,
        'description' => 'Die Telefonnummer des Kunden (wird automatisch vom System gefüllt wenn möglich)'
    ],
    'dienstleistung' => [
        'type' => 'string',
        'required' => true,
        'description' => 'Die gewünschte Dienstleistung oder der Grund des Termins'
    ],
    'email' => [
        'type' => 'string',
        'required' => false,
        'description' => 'E-Mail-Adresse für die Terminbestätigung (optional)'
    ],
    'mitarbeiter_wunsch' => [
        'type' => 'string',
        'required' => false,
        'description' => 'Bevorzugter Mitarbeiter (optional)'
    ],
    'kundenpraeferenzen' => [
        'type' => 'string',
        'required' => false,
        'description' => 'Besondere Wünsche oder Präferenzen (optional)'
    ]
];

echo json_encode(['properties' => $parameters, 'required' => ['datum', 'uhrzeit', 'name', 'telefonnummer', 'dienstleistung']], JSON_PRETTY_PRINT) . "\n\n";

echo "=== WICHTIGE PROMPT-ERGÄNZUNG ===\n\n";
echo "Füge folgendes zum Agent Prompt hinzu:\n\n";

echo "---\n";
echo "TERMINBUCHUNG:\n";
echo "Wenn ein Kunde einen Termin buchen möchte:\n";
echo "1. Sammle ALLE erforderlichen Informationen:\n";
echo "   - Datum (frage nach dem gewünschten Tag)\n";
echo "   - Uhrzeit (frage nach der bevorzugten Zeit)\n";
echo "   - Name (frage nach dem vollständigen Namen)\n";
echo "   - Dienstleistung (was möchte der Kunde buchen?)\n";
echo "   - Optional: E-Mail-Adresse für die Bestätigung\n";
echo "   - Optional: Mitarbeiterwunsch\n";
echo "\n";
echo "2. Die Telefonnummer wird automatisch erfasst - du musst nicht danach fragen.\n";
echo "\n";
echo "3. Sobald du ALLE Pflichtinformationen hast, rufe die Funktion 'collect_appointment_data' auf.\n";
echo "\n";
echo "4. Bestätige dem Kunden die erfolgreiche Terminbuchung mit der Referenznummer.\n";
echo "---\n\n";

echo "=== TEST DER KONFIGURATION ===\n\n";
echo "Nach der Konfiguration:\n";
echo "1. Rufe +493083793369 an\n";
echo "2. Sage: 'Ich möchte einen Termin buchen'\n";
echo "3. Beantworte alle Fragen des Assistenten\n";
echo "4. Der Assistent sollte die collect_appointment_data Funktion aufrufen\n";
echo "5. Prüfe im Dashboard ob der Termin erfasst wurde\n\n";

// Check current configuration
try {
    $apiKey = env('DEFAULT_RETELL_API_KEY') ?? env('RETELL_TOKEN');
    $retellService = new RetellV2Service($apiKey);
    
    $agent = $retellService->getAgent($agentId);
    
    if ($agent && isset($agent['general_tools']) && count($agent['general_tools']) > 0) {
        echo "⚠️  ACHTUNG: Der Agent hat bereits " . count($agent['general_tools']) . " Custom Functions konfiguriert!\n";
        echo "   Bitte prüfe ob 'collect_appointment_data' bereits vorhanden ist.\n";
    } else {
        echo "✓ Der Agent hat noch keine Custom Functions - perfekt für die Konfiguration!\n";
    }
    
} catch (\Exception $e) {
    echo "Konnte Agent-Status nicht prüfen: " . $e->getMessage() . "\n";
}

echo "\n=== ALTERNATIVE: AUTOMATISCHE KONFIGURATION ===\n\n";
echo "Falls die Retell API die Konfiguration von Custom Functions unterstützt,\n";
echo "kann dies auch per API erfolgen. Prüfe die Retell API Dokumentation.\n\n";

echo "Endpoint wäre vermutlich:\n";
echo "PUT https://api.retellai.com/update-agent/{$agentId}\n";
echo "Mit dem general_tools Array im Body.\n";