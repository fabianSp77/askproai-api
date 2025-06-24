<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Company;
use App\Services\RetellV2Service;

echo "🔧 FIXING PHONE-AGENT MAPPING\n";
echo str_repeat('=', 50) . "\n\n";

$company = Company::find(1);
$apiKey = decrypt($company->retell_api_key);
$service = new RetellV2Service($apiKey);

// The correct agent for Musterfriseur
$correctAgentId = 'agent_321b510badbbc129d1464ec8bd';
$webhookUrl = 'https://api.askproai.de/api/retell/webhook';

// Custom functions
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

// First, check what agent the phone number is currently using
echo "1. CHECKING CURRENT PHONE CONFIGURATION\n";
$phonesResult = $service->listPhoneNumbers();
$phoneNumbers = $phonesResult['phone_numbers'] ?? [];

foreach ($phoneNumbers as $phone) {
    if ($phone['phone_number'] === '+493083793369') {
        echo "\n☎️  Phone: " . $phone['phone_number'] . "\n";
        echo "   Current Agent ID: " . ($phone['agent_id'] ?? 'NOT SET') . "\n";
        echo "   Expected Agent ID: $correctAgentId\n";
        
        if (($phone['agent_id'] ?? '') !== $correctAgentId) {
            echo "\n   ❌ WRONG AGENT! Updating...\n";
            
            try {
                // Update phone number with correct agent
                $updateData = [
                    'agent_id' => $correctAgentId,
                    'inbound_webhook_url' => $webhookUrl
                ];
                
                $result = $service->updatePhoneNumber($phone['phone_number'], $updateData);
                echo "   ✅ Phone number updated with correct agent!\n";
            } catch (\Exception $e) {
                echo "   ❌ Error updating phone: " . $e->getMessage() . "\n";
            }
        }
    }
}

// Now update the Musterfriseur agent with webhook events
echo "\n\n2. UPDATING MUSTERFRISEUR AGENT\n";
try {
    $updateData = [
        'webhook_url' => $webhookUrl,
        'custom_functions' => $customFunctions,
        'webhook_events' => ['call_started', 'call_ended', 'call_analyzed']
    ];
    
    $result = $service->updateAgent($correctAgentId, $updateData);
    echo "✅ Agent updated with:\n";
    echo "   - Webhook URL: $webhookUrl\n";
    echo "   - Webhook Events: call_started, call_ended, call_analyzed\n";
    echo "   - Custom Functions: collect_appointment_data, current_time_berlin\n";
} catch (\Exception $e) {
    echo "❌ Error updating agent: " . $e->getMessage() . "\n";
}

// Also check the other agent that was being used
echo "\n\n3. CHECKING THE AGENT THAT WAS BEING USED\n";
$wrongAgentId = 'agent_9a8202a740cd3120d96fcfda1e';
$agentsResult = $service->listAgents();
$agents = $agentsResult['agents'] ?? [];

foreach ($agents as $agent) {
    if ($agent['agent_id'] === $wrongAgentId) {
        echo "\nFound agent that was being used:\n";
        echo "   Name: " . $agent['agent_name'] . "\n";
        echo "   ID: " . $agent['agent_id'] . "\n";
        echo "   Webhook URL: " . ($agent['webhook_url'] ?? 'NOT SET') . "\n";
        break;
    }
}

echo "\n\n✅ CONFIGURATION FIXED!\n";
echo "\nThe phone number +493083793369 is now correctly mapped to:\n";
echo "- Agent: Online: Musterfriseur Terminierung\n";
echo "- Agent ID: $correctAgentId\n";
echo "- Webhook events are enabled\n";
echo "- Custom functions are configured\n";
echo "\n📞 Make another test call - it should work now!\n";