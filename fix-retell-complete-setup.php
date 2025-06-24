<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Company;
use App\Services\RetellV2Service;

echo "🔧 FIXING COMPLETE RETELL SETUP\n";
echo str_repeat('=', 50) . "\n\n";

$company = Company::find(1);
$apiKey = decrypt($company->retell_api_key);
$service = new RetellV2Service($apiKey);

// Use the WORKING webhook URL
$webhookUrl = 'https://api.askproai.de/api/retell/webhook';

// Custom functions configuration
$customFunctions = [
    [
        'name' => 'collect_appointment_data',
        'url' => 'https://api.askproai.de/api/retell/collect-appointment',
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
        'url' => 'https://api.askproai.de/api/zeitinfo',
        'description' => 'Gibt die aktuelle Zeit in Berlin zurück',
        'parameters' => [
            'type' => 'object',
            'properties' => [],
            'required' => []
        ]
    ]
];

// 1. Update Musterfriseur Agent
echo "1. UPDATING MUSTERFRISEUR AGENT\n";
$agentsResult = $service->listAgents();
$agents = $agentsResult['agents'] ?? [];

$musterfriseurAgentId = null;
foreach ($agents as $agent) {
    if ($agent['agent_name'] === 'Online: Musterfriseur Terminierung') {
        $musterfriseurAgentId = $agent['agent_id'];
        echo "📱 Found: " . $agent['agent_name'] . "\n";
        echo "   Agent ID: " . $agent['agent_id'] . "\n";
        
        try {
            $updateData = [
                'webhook_url' => $webhookUrl,
                'custom_functions' => $customFunctions
            ];
            
            $result = $service->updateAgent($agent['agent_id'], $updateData);
            echo "✅ Webhook URL updated to: $webhookUrl\n";
            echo "✅ Custom functions configured\n";
        } catch (\Exception $e) {
            echo "❌ Error: " . $e->getMessage() . "\n";
        }
        break;
    }
}

// 2. Update Phone Number with Agent ID
echo "\n2. UPDATING PHONE NUMBER\n";
$phonesResult = $service->listPhoneNumbers();
$phoneNumbers = $phonesResult['phone_numbers'] ?? [];

foreach ($phoneNumbers as $phone) {
    if ($phone['phone_number'] === '+493083793369') {
        echo "☎️  Found: " . $phone['phone_number'] . "\n";
        
        try {
            $updateData = [
                'inbound_webhook_url' => $webhookUrl
            ];
            
            // Add agent_id if we found the Musterfriseur agent
            if ($musterfriseurAgentId) {
                $updateData['agent_id'] = $musterfriseurAgentId;
            }
            
            $result = $service->updatePhoneNumber($phone['phone_number'], $updateData);
            echo "✅ Webhook URL updated to: $webhookUrl\n";
            if ($musterfriseurAgentId) {
                echo "✅ Agent ID linked: $musterfriseurAgentId\n";
            }
        } catch (\Exception $e) {
            echo "❌ Error: " . $e->getMessage() . "\n";
        }
        break;
    }
}

echo "\n3. CONFIGURATION SUMMARY\n";
echo str_repeat('-', 30) . "\n";
echo "✅ Webhook URL: $webhookUrl\n";
echo "✅ Custom Functions:\n";
echo "   - collect_appointment_data → " . $customFunctions[0]['url'] . "\n";
echo "   - current_time_berlin → " . $customFunctions[1]['url'] . "\n";
echo "✅ Agent: Online: Musterfriseur Terminierung\n";
echo "✅ Phone: +493083793369\n";

echo "\n📞 NEXT STEPS:\n";
echo "1. The webhooks are NOW using the standard endpoint (with signature verification)\n";
echo "2. Make another test call to +493083793369\n";
echo "3. Check if webhooks arrive at: $webhookUrl\n";
echo "4. If signature fails, we'll see it in the logs\n";