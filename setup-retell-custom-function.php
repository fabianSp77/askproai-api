<?php

use App\Services\MCP\RetellMCPServer;
use Illuminate\Support\Facades\Http;

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=================================\n";
echo "Retell.ai Custom Function Setup\n";
echo "=================================\n\n";

// Step 1: Define the appointment collection function
$customFunction = [
    "name" => "collect_appointment_data",
    "description" => "Sammelt alle notwendigen Informationen für eine Terminbuchung",
    "parameters" => [
        "type" => "object",
        "properties" => [
            "datum" => [
                "type" => "string",
                "description" => "Gewünschtes Datum im Format DD.MM.YYYY"
            ],
            "uhrzeit" => [
                "type" => "string", 
                "description" => "Gewünschte Uhrzeit im Format HH:MM"
            ],
            "dienstleistung" => [
                "type" => "string",
                "description" => "Gewünschte Dienstleistung oder Service"
            ],
            "name" => [
                "type" => "string",
                "description" => "Name des Kunden"
            ],
            "telefonnummer" => [
                "type" => "string",
                "description" => "Telefonnummer des Kunden"
            ],
            "email" => [
                "type" => "string",
                "description" => "E-Mail-Adresse des Kunden (optional)"
            ],
            "notizen" => [
                "type" => "string",
                "description" => "Zusätzliche Notizen oder Wünsche"
            ],
            "booking_confirmed" => [
                "type" => "boolean",
                "description" => "Wurde die Buchung bestätigt?"
            ]
        ],
        "required" => ["datum", "uhrzeit", "name", "telefonnummer", "booking_confirmed"]
    ]
];

echo "1. Custom Function Definition:\n";
echo json_encode($customFunction, JSON_PRETTY_PRINT) . "\n\n";

// Step 2: Create the webhook endpoint for the custom function
$webhookEndpoint = <<<'PHP'
<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;

// Add to routes/api.php
Route::post('/retell/custom-function', function (Request $request) {
    Log::info('Retell custom function called', [
        'function' => $request->input('function_name'),
        'args' => $request->input('function_args'),
        'call_id' => $request->input('call_id')
    ]);
    
    $functionName = $request->input('function_name');
    $args = $request->input('function_args', []);
    
    if ($functionName === 'collect_appointment_data') {
        // Store the appointment data in call metadata
        $callId = $request->input('call_id');
        
        // Validate required fields
        $required = ['datum', 'uhrzeit', 'name', 'telefonnummer'];
        foreach ($required as $field) {
            if (empty($args[$field])) {
                return response()->json([
                    'success' => false,
                    'message' => "Bitte geben Sie $field an"
                ]);
            }
        }
        
        // Store in dynamic variables for webhook processing
        return response()->json([
            'success' => true,
            'message' => 'Termindaten erfolgreich erfasst',
            'appointment_data' => $args,
            'dynamic_variables' => [
                'appointment_datum' => $args['datum'],
                'appointment_uhrzeit' => $args['uhrzeit'],
                'appointment_name' => $args['name'],
                'appointment_telefon' => $args['telefonnummer'],
                'appointment_dienstleistung' => $args['dienstleistung'] ?? '',
                'appointment_email' => $args['email'] ?? '',
                'appointment_notizen' => $args['notizen'] ?? '',
                'appointment_confirmed' => $args['booking_confirmed'] ?? false
            ]
        ]);
    }
    
    return response()->json([
        'success' => false,
        'message' => 'Unknown function: ' . $functionName
    ]);
})->middleware('verify.retell.signature');
PHP;

echo "2. Webhook Endpoint Code (add to routes/api.php):\n";
echo $webhookEndpoint . "\n\n";

// Step 3: Update Retell agent prompt
$agentPrompt = <<<'PROMPT'
Du bist ein freundlicher Terminbuchungsassistent für [COMPANY_NAME].

DEINE HAUPTAUFGABE:
Sammle alle notwendigen Informationen für eine Terminbuchung und verwende die Funktion "collect_appointment_data".

ABLAUF:
1. Begrüße den Anrufer freundlich
2. Frage nach dem gewünschten Service/Dienstleistung
3. Frage nach dem gewünschten Datum
4. Frage nach der gewünschten Uhrzeit
5. Frage nach dem Namen
6. Bestätige die Telefonnummer (bereits aus dem Anruf bekannt)
7. Frage optional nach E-Mail für Bestätigung
8. Fasse alle Daten zusammen und bestätige
9. Rufe die Funktion collect_appointment_data mit allen gesammelten Daten auf

WICHTIG:
- Verwende IMMER die collect_appointment_data Funktion am Ende
- Setze booking_confirmed auf true, wenn der Kunde den Termin bestätigt
- Datum im Format DD.MM.YYYY (z.B. 25.06.2025)
- Uhrzeit im Format HH:MM (z.B. 14:30)

BEISPIEL-DIALOG:
Kunde: "Ich möchte einen Termin buchen"
Du: "Gerne helfe ich Ihnen bei der Terminbuchung. Für welche Dienstleistung möchten Sie einen Termin?"
Kunde: "Für einen Haarschnitt"
Du: "Perfekt, einen Haarschnitt. An welchem Tag hätten Sie gerne den Termin?"
[... Dialog fortsetzten und am Ende collect_appointment_data aufrufen]
PROMPT;

echo "3. Agent Prompt (update in Retell dashboard):\n";
echo $agentPrompt . "\n\n";

// Step 4: Test configuration
echo "4. Testing current Retell configuration...\n";

$apiKey = env('DEFAULT_RETELL_API_KEY', config('services.retell.api_key'));
if (!$apiKey) {
    echo "❌ ERROR: No Retell API key found in configuration!\n";
    exit(1);
}

try {
    // Get current agent configuration
    $response = Http::withHeaders([
        'Authorization' => 'Bearer ' . $apiKey,
    ])->get('https://api.retellai.com/v1/agents');
    
    if ($response->successful()) {
        $agents = $response->json();
        echo "✅ Found " . count($agents) . " Retell agents\n";
        
        foreach ($agents as $agent) {
            echo "\nAgent: " . ($agent['name'] ?? 'Unknown') . "\n";
            echo "ID: " . $agent['agent_id'] . "\n";
            echo "Has custom functions: " . (empty($agent['functions']) ? 'NO' : 'YES') . "\n";
        }
    } else {
        echo "❌ Failed to retrieve agents: " . $response->body() . "\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Error connecting to Retell API: " . $e->getMessage() . "\n";
}

echo "\n=================================\n";
echo "Next Steps:\n";
echo "=================================\n";
echo "1. Add the custom function to your Retell agent in the dashboard\n";
echo "2. Update the agent prompt with the provided template\n";
echo "3. Add the webhook endpoint to routes/api.php\n";
echo "4. Test with a phone call\n";
echo "\nCustom Function JSON saved to: retell-custom-function.json\n";

// Save the custom function JSON for easy copy/paste
file_put_contents(__DIR__ . '/retell-custom-function.json', json_encode($customFunction, JSON_PRETTY_PRINT));