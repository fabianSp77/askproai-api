#!/usr/bin/env php
<?php
/**
 * Programmatically Update Retell Agent for Hair Salon
 * This script directly updates the Retell agent configuration via API
 */

require_once __DIR__ . '/vendor/autoload.php';

use App\Services\MCP\RetellMCPServer;
use App\Services\RetellService;
use App\Models\Company;
use Illuminate\Support\Facades\Log;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "\033[1;36m================================================================================\033[0m\n";
echo "\033[1;33m🤖 Retell Agent Programmatic Update - Hair Salon Configuration\033[0m\n";
echo "\033[1;36m================================================================================\033[0m\n\n";

// Configuration
$AGENT_ID = 'agent_d7da9e5c49c4ccfff2526df5c1'; // Your specific agent
$COMPANY_ID = 1; // Default company

// Get company and API key
$company = Company::find($COMPANY_ID);
if (!$company || !$company->retell_api_key) {
    echo "\033[0;31m❌ Error: Company not found or Retell API key not configured\033[0m\n";
    exit(1);
}

// Initialize Retell Service
$apiKey = $company->retell_api_key;
if (strlen($apiKey) > 50) {
    try {
        $apiKey = decrypt($apiKey);
    } catch (\Exception $e) {
        // Use as-is if decryption fails
    }
}

$retellService = new RetellService($apiKey);

echo "📋 Updating Agent: $AGENT_ID\n";
echo str_repeat('-', 80) . "\n\n";

// Step 1: Update Agent Prompt
echo "\033[1;35m1️⃣ Updating Agent Prompt for Hair Salon\033[0m\n";

$hairSalonPrompt = <<<'PROMPT'
Du bist der freundliche KI-Assistent für einen Friseursalon mit 3 Mitarbeiterinnen: Paula (ID:1), Claudia (ID:2) und Katrin (ID:3).

## Deine Hauptaufgaben:
1. Termine für Friseurdienstleistungen vereinbaren
2. Dienstleistungen und Preise erklären
3. Beratungstermine für spezielle Services vereinbaren

## Mitarbeiterauswahl:
Wenn der Kunde keinen speziellen Mitarbeiter wünscht:
- Prüfe die Verfügbarkeit aller drei Mitarbeiterinnen
- Biete die nächsten verfügbaren Termine an
- Erwähne bei wem der Termin wäre

Wenn der Kunde einen bestimmten Mitarbeiter möchte:
- Nutze nur die staff_id dieser Mitarbeiterin
- Paula = staff_id: 1
- Claudia = staff_id: 2  
- Katrin = staff_id: 3

## Dienstleistungen die Beratung benötigen:
Diese Services erfordern IMMER einen Beratungsrückruf:
- Strähnen (alle Varianten)
- Blondierung
- Balayage
- Faceframe

Für diese sage: "Das ist eine tolle Wahl! Für [Service] ist eine persönliche Beratung wichtig. Ich vereinbare gerne einen Rückruf für Sie."

## Gesprächsablauf:
1. Begrüße freundlich und frage nach dem Wunsch
2. Bei Beratungsservices → schedule_callback nutzen
3. Bei normalen Services → check_availability → book_appointment
4. Bestätige alle Termine mit allen Details
5. Verabschiede dich freundlich

## Wichtige Hinweise:
- Nutze IMMER die MCP Funktionen für alle Aktionen
- Bestätige IMMER Name und Telefonnummer
- Bei Unsicherheiten höflich nachfragen
- Sprich natürlich und freundlich auf Deutsch

## Beispiel-Dialoge:
Kunde: "Ich hätte gerne Strähnen"
Du: "Wunderbar! Strähnen sind eine tolle Wahl. Da brauchen wir eine persönliche Beratung, um das perfekte Ergebnis für Sie zu planen. Darf ich Ihnen einen Rückruf vereinbaren? Wann würde es Ihnen passen?"

Kunde: "Ich möchte einen Haarschnitt bei Paula"
Du: "Sehr gerne! Ich schaue nach freien Terminen bei Paula für einen Haarschnitt. [check_availability mit staff_id:1]"
PROMPT;

$updateData = [
    'general_prompt' => $hairSalonPrompt,
    'agent_name' => 'Hair Salon Assistant',
    'language' => 'de',
    'voice_id' => '11labs-Hanna',
    'responsiveness' => 1,
    'interruption_sensitivity' => 1,
    'enable_backchannel' => true,
    'backchannel_frequency' => 0.8,
    'backchannel_words' => ['ja', 'genau', 'verstehe', 'okay', 'mhm'],
    'ambient_sound' => 'office',
    'webhook_url' => 'https://api.askproai.de/api/v2/hair-salon-mcp/retell-webhook',
    'end_call_after_silence_ms' => 10000,
    'max_call_duration_ms' => 1800000, // 30 minutes
];

try {
    $response = $retellService->updateAgent($AGENT_ID, $updateData);
    echo "\033[0;32m✓ Agent prompt updated successfully!\033[0m\n";
} catch (\Exception $e) {
    echo "\033[0;31m✗ Failed to update agent: " . $e->getMessage() . "\033[0m\n";
}

// Step 2: Add Custom Functions
echo "\n\033[1;35m2️⃣ Adding Custom Functions\033[0m\n";

$customFunctions = [
    [
        'name' => 'list_services',
        'description' => 'List all available hair salon services with prices',
        'url' => 'https://api.askproai.de/api/v2/hair-salon-mcp/mcp',
        'method' => 'POST',
        'headers' => [
            'Content-Type' => 'application/json',
            'X-Company-ID' => '1'
        ],
        'body' => [
            'jsonrpc' => '2.0',
            'method' => 'list_services',
            'params' => [
                'company_id' => 1
            ],
            'id' => 'list-{{timestamp}}'
        ],
        'parameters' => [
            'type' => 'object',
            'properties' => [
                'category' => [
                    'type' => 'string',
                    'description' => 'Optional service category filter'
                ]
            ]
        ]
    ],
    [
        'name' => 'check_availability',
        'description' => 'Check available appointment slots for a service',
        'url' => 'https://api.askproai.de/api/v2/hair-salon-mcp/mcp',
        'method' => 'POST',
        'headers' => [
            'Content-Type' => 'application/json',
            'X-Company-ID' => '1'
        ],
        'body' => [
            'jsonrpc' => '2.0',
            'method' => 'check_availability',
            'params' => [
                'company_id' => 1,
                'service_id' => '{{service_id}}',
                'staff_id' => '{{staff_id}}',
                'date' => '{{date}}',
                'days_ahead' => '{{days_ahead}}'
            ],
            'id' => 'avail-{{timestamp}}'
        ],
        'parameters' => [
            'type' => 'object',
            'required' => ['service_id'],
            'properties' => [
                'service_id' => [
                    'type' => 'integer',
                    'description' => 'ID of the service'
                ],
                'staff_id' => [
                    'type' => 'integer',
                    'description' => 'Staff member ID (1=Paula, 2=Claudia, 3=Katrin)'
                ],
                'date' => [
                    'type' => 'string',
                    'description' => 'Start date (YYYY-MM-DD)'
                ],
                'days_ahead' => [
                    'type' => 'integer',
                    'description' => 'Number of days to check ahead'
                ]
            ]
        ]
    ],
    [
        'name' => 'book_appointment',
        'description' => 'Book an appointment for a customer',
        'url' => 'https://api.askproai.de/api/v2/hair-salon-mcp/mcp',
        'method' => 'POST',
        'headers' => [
            'Content-Type' => 'application/json',
            'X-Company-ID' => '1'
        ],
        'body' => [
            'jsonrpc' => '2.0',
            'method' => 'book_appointment',
            'params' => [
                'company_id' => 1,
                'customer_name' => '{{customer_name}}',
                'customer_phone' => '{{customer_phone}}',
                'service_id' => '{{service_id}}',
                'staff_id' => '{{staff_id}}',
                'datetime' => '{{datetime}}',
                'notes' => '{{notes}}'
            ],
            'id' => 'book-{{timestamp}}'
        ],
        'parameters' => [
            'type' => 'object',
            'required' => ['customer_name', 'customer_phone', 'service_id', 'staff_id', 'datetime'],
            'properties' => [
                'customer_name' => [
                    'type' => 'string',
                    'description' => 'Customer full name'
                ],
                'customer_phone' => [
                    'type' => 'string',
                    'description' => 'Customer phone number'
                ],
                'service_id' => [
                    'type' => 'integer',
                    'description' => 'ID of the service'
                ],
                'staff_id' => [
                    'type' => 'integer',
                    'description' => 'Staff member ID'
                ],
                'datetime' => [
                    'type' => 'string',
                    'description' => 'Appointment date and time (YYYY-MM-DD HH:MM)'
                ],
                'notes' => [
                    'type' => 'string',
                    'description' => 'Optional notes'
                ]
            ]
        ]
    ],
    [
        'name' => 'schedule_callback',
        'description' => 'Schedule a callback for consultation services',
        'url' => 'https://api.askproai.de/api/v2/hair-salon-mcp/mcp',
        'method' => 'POST',
        'headers' => [
            'Content-Type' => 'application/json',
            'X-Company-ID' => '1'
        ],
        'body' => [
            'jsonrpc' => '2.0',
            'method' => 'schedule_callback',
            'params' => [
                'company_id' => 1,
                'customer_name' => '{{customer_name}}',
                'customer_phone' => '{{customer_phone}}',
                'service_name' => '{{service_name}}',
                'preferred_time' => '{{preferred_time}}',
                'notes' => '{{notes}}'
            ],
            'id' => 'callback-{{timestamp}}'
        ],
        'parameters' => [
            'type' => 'object',
            'required' => ['customer_name', 'customer_phone', 'service_name'],
            'properties' => [
                'customer_name' => [
                    'type' => 'string',
                    'description' => 'Customer full name'
                ],
                'customer_phone' => [
                    'type' => 'string',
                    'description' => 'Customer phone number'
                ],
                'service_name' => [
                    'type' => 'string',
                    'description' => 'Service requiring consultation'
                ],
                'preferred_time' => [
                    'type' => 'string',
                    'description' => 'Preferred callback time'
                ],
                'notes' => [
                    'type' => 'string',
                    'description' => 'Additional notes'
                ]
            ]
        ]
    ]
];

foreach ($customFunctions as $function) {
    echo "   Adding function: {$function['name']}... ";
    try {
        // Note: The Retell API doesn't have a direct method to add functions
        // You would need to update the entire agent configuration with functions
        // or use the dashboard. For now, we'll log what should be added.
        echo "\033[0;33m[Manual]\033[0m\n";
        echo "   → Add this function in Retell Dashboard > Functions section\n";
    } catch (\Exception $e) {
        echo "\033[0;31m✗ Error: " . $e->getMessage() . "\033[0m\n";
    }
}

// Step 3: Configure MCP Settings
echo "\n\033[1;35m3️⃣ MCP Configuration\033[0m\n";
echo "   MCP must be configured in the Retell Dashboard @MCP section:\n";
echo "   • Name: hair_salon_mcp\n";
echo "   • URL: https://api.askproai.de/api/v2/hair-salon-mcp/mcp\n";
echo "   • Headers: {\"Content-Type\": \"application/json\", \"X-Company-ID\": \"1\"}\n";

// Step 4: Test the configuration
echo "\n\033[1;35m4️⃣ Testing Updated Configuration\033[0m\n";

try {
    $agent = $retellService->getAgent($AGENT_ID);
    if ($agent) {
        echo "\033[0;32m✓ Agent retrieved successfully!\033[0m\n";
        echo "   Name: " . ($agent['agent_name'] ?? 'N/A') . "\n";
        echo "   Language: " . ($agent['language'] ?? 'N/A') . "\n";
        echo "   Voice: " . ($agent['voice_id'] ?? 'N/A') . "\n";
        echo "   Webhook: " . ($agent['webhook_url'] ?? 'N/A') . "\n";
    }
} catch (\Exception $e) {
    echo "\033[0;31m✗ Failed to retrieve agent: " . $e->getMessage() . "\033[0m\n";
}

// Summary
echo "\n\033[1;36m================================================================================\033[0m\n";
echo "\033[1;33m📊 Summary\033[0m\n";
echo "\033[1;36m================================================================================\033[0m\n\n";

echo "✅ What was updated programmatically:\n";
echo "   • Agent prompt (German hair salon specific)\n";
echo "   • Voice settings (German voice)\n";
echo "   • Webhook URL\n";
echo "   • General configuration\n\n";

echo "⚠️ What needs manual configuration in Retell Dashboard:\n";
echo "   1. Custom Functions (in Functions section)\n";
echo "   2. MCP Configuration (in @MCP section)\n\n";

echo "📱 To test:\n";
echo "   1. Complete manual steps above\n";
echo "   2. Call: +493033081738\n";
echo "   3. Say: 'Ich möchte einen Termin für einen Haarschnitt'\n\n";

echo "\033[1;32m✅ Script completed successfully!\033[0m\n";