<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Company;
use App\Services\RetellV2Service;

echo "🔧 AUTOMATISCHE RETELL KONFIGURATION\n";
echo str_repeat('=', 50) . "\n\n";

// Get company and API key
$company = Company::find(1);
if (!$company || !$company->retell_api_key) {
    die("❌ Keine Company oder Retell API Key gefunden!\n");
}

$apiKey = decrypt($company->retell_api_key);
$service = new RetellV2Service($apiKey);

// Configuration values
$webhookUrl = 'https://api.askproai.de/api/retell/webhook';
$customFunctions = [
    [
        'name' => 'collect_appointment_data',
        'description' => 'Sammelt alle Terminbuchungsdaten vom Anrufer',
        'parameters' => [
            'type' => 'object',
            'properties' => [
                'telefonnummer' => [
                    'type' => 'string',
                    'description' => 'Telefonnummer des Anrufers (nur erfragen wenn nicht automatisch übermittelt)'
                ],
                'dienstleistung' => [
                    'type' => 'string', 
                    'description' => 'Gewünschte Dienstleistung (Haarschnitt, Färben, etc.)'
                ],
                'wunschtermin_datum' => [
                    'type' => 'string',
                    'description' => 'Gewünschtes Datum (Format: YYYY-MM-DD)'
                ],
                'wunschtermin_uhrzeit' => [
                    'type' => 'string',
                    'description' => 'Gewünschte Uhrzeit (Format: HH:MM)'
                ],
                'alternative_termine' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'datum' => ['type' => 'string'],
                            'uhrzeit' => ['type' => 'string']
                        ]
                    ],
                    'description' => 'Alternative Terminvorschläge falls Wunschtermin nicht verfügbar'
                ],
                'notizen' => [
                    'type' => 'string',
                    'description' => 'Zusätzliche Wünsche oder Anmerkungen'
                ]
            ],
            'required' => ['dienstleistung', 'wunschtermin_datum', 'wunschtermin_uhrzeit']
        ]
    ],
    [
        'name' => 'current_time_berlin',
        'description' => 'Gibt die aktuelle Zeit in Berlin zurück',
        'parameters' => [
            'type' => 'object',
            'properties' => [],
            'required' => []
        ]
    ]
];

echo "1. AGENTS KONFIGURIEREN\n";
echo str_repeat('-', 30) . "\n";

try {
    $agentsResult = $service->listAgents();
    $agents = $agentsResult['agents'] ?? [];
    
    echo "Gefundene Agents: " . count($agents) . "\n\n";
    
    foreach ($agents as $agent) {
        echo "📱 Agent: " . $agent['agent_name'] . "\n";
        
        $updateData = [
            'webhook_url' => $webhookUrl,
            'custom_functions' => $customFunctions,
            'enable_webhook' => true,
            'webhook_events' => ['call_started', 'call_ended', 'call_analyzed']
        ];
        
        try {
            $result = $service->updateAgent($agent['agent_id'], $updateData);
            echo "✅ Webhook URL: $webhookUrl\n";
            echo "✅ Custom Functions konfiguriert\n";
            echo "✅ Webhook Events aktiviert\n";
        } catch (\Exception $e) {
            echo "❌ Fehler: " . $e->getMessage() . "\n";
        }
        echo "\n";
    }
} catch (\Exception $e) {
    echo "❌ Fehler beim Abrufen der Agents: " . $e->getMessage() . "\n\n";
}

echo "\n2. PHONE NUMBERS KONFIGURIEREN\n";
echo str_repeat('-', 30) . "\n";

try {
    $phonesResult = $service->listPhoneNumbers();
    $phoneNumbers = $phonesResult['phone_numbers'] ?? [];
    
    echo "Gefundene Telefonnummern: " . count($phoneNumbers) . "\n\n";
    
    foreach ($phoneNumbers as $phone) {
        echo "☎️  Nummer: " . $phone['phone_number'] . "\n";
        
        $updateData = [
            'inbound_webhook_url' => $webhookUrl
        ];
        
        try {
            $result = $service->updatePhoneNumber($phone['phone_number'], $updateData);
            echo "✅ Webhook URL: $webhookUrl\n";
        } catch (\Exception $e) {
            echo "❌ Fehler: " . $e->getMessage() . "\n";
        }
        echo "\n";
    }
} catch (\Exception $e) {
    echo "❌ Fehler beim Abrufen der Telefonnummern: " . $e->getMessage() . "\n\n";
}

echo "\n3. WEBHOOK SECRET ÜBERPRÜFEN\n";
echo str_repeat('-', 30) . "\n";

echo "⚠️  WICHTIG: Der Webhook Secret muss in Retell.ai konfiguriert werden!\n";
echo "   1. Gehe zu: https://dashboard.retellai.com/api-keys\n";
echo "   2. Kopiere den 'Webhook Secret' (NICHT den API Key!)\n";
echo "   3. Trage ihn in die .env ein als: RETELL_WEBHOOK_SECRET=<secret>\n\n";

// Check current .env setting
$webhookSecret = env('RETELL_WEBHOOK_SECRET');
if ($webhookSecret && str_starts_with($webhookSecret, 'key_')) {
    if ($webhookSecret === env('RETELL_TOKEN')) {
        echo "❌ FEHLER: Webhook Secret ist identisch mit API Key!\n";
        echo "   Das sind zwei verschiedene Werte!\n\n";
    } else {
        echo "✅ Webhook Secret ist konfiguriert\n\n";
    }
} else {
    echo "❌ Webhook Secret nicht in .env gefunden\n\n";
}

echo "\n4. KONFIGURATION ZUSAMMENFASSUNG\n";
echo str_repeat('-', 30) . "\n";
echo "✅ Webhook URL: $webhookUrl\n";
echo "✅ Custom Functions:\n";
echo "   - collect_appointment_data\n";
echo "   - current_time_berlin\n";
echo "✅ Webhook Events: call_started, call_ended, call_analyzed\n\n";

echo "📞 NÄCHSTER SCHRITT: Mache einen Testanruf!\n";
echo "   Die Webhooks sollten jetzt funktionieren.\n\n";

// Quick test of webhook endpoint
echo "5. WEBHOOK ENDPOINT TEST\n";
echo str_repeat('-', 30) . "\n";

$ch = curl_init($webhookUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_NOBODY, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 405) {
    echo "✅ Webhook Endpoint erreichbar (405 = Method Not Allowed für GET ist OK)\n";
} elseif ($httpCode === 200) {
    echo "✅ Webhook Endpoint erreichbar\n";
} else {
    echo "⚠️  Webhook Endpoint Status: $httpCode\n";
}

echo "\n✨ AUTOMATISCHE KONFIGURATION ABGESCHLOSSEN!\n";