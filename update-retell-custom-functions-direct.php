<?php

require_once __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;
use App\Models\Company;

$agentId = 'agent_9a8202a740cd3120d96fcfda1e';

$company = Company::first();
if (!$company || !$company->retell_api_key) {
    die("Error: No company with Retell API key found\n");
}

echo "Fetching current agent configuration...\n";

// Get the agent details
$agentResponse = Http::withHeaders([
    'Authorization' => 'Bearer ' . $company->retell_api_key,
])->get("https://api.retellai.com/get-agent/{$agentId}");

if (!$agentResponse->successful()) {
    die("Error fetching agent: " . $agentResponse->body() . "\n");
}

$agent = $agentResponse->json();
echo "Agent: " . $agent['agent_name'] . "\n";

// Check if it's using retell-llm
if (!isset($agent['response_engine']['llm_id'])) {
    die("Error: Agent is not using retell-llm\n");
}

$llmId = $agent['response_engine']['llm_id'];
echo "LLM ID: " . $llmId . "\n";

// Get current LLM configuration
$llmResponse = Http::withHeaders([
    'Authorization' => 'Bearer ' . $company->retell_api_key,
])->get("https://api.retellai.com/get-retell-llm/{$llmId}");

if (!$llmResponse->successful()) {
    die("Error fetching LLM: " . $llmResponse->body() . "\n");
}

$llmConfig = $llmResponse->json();
echo "Current LLM has " . count($llmConfig['general_tools'] ?? []) . " custom functions\n";

// Update the custom functions to use call_id
$newCustomFunctions = [
    [
        "type" => "end_call",
        "name" => "end_call",
        "description" => "Use this function to end the call."
    ],
    [
        "type" => "transfer_call",
        "name" => "transfer_call", 
        "description" => "Use this function to transfer the call. Be sure to introduce the transfer before you transfer the call.",
        "transfer_destination" => "+491604366218"
    ],
    [
        "type" => "custom",
        "name" => "check_availability_cal",
        "description" => "Überprüfe verfügbare Termine für einen Tag oder spezifische Zeit. Nutze diese Funktion IMMER bevor du einen Termin buchst.",
        "url" => "https://api.askproai.de/api/retell/check-availability",
        "speak_during_execution" => true,
        "speak_after_execution" => true,
        "parameters" => [
            "type" => "object",
            "properties" => [
                "date" => [
                    "type" => "string",
                    "description" => "Datum im Format YYYY-MM-DD oder relativ (heute, morgen)"
                ],
                "time" => [
                    "type" => "string", 
                    "description" => "Gewünschte Uhrzeit (optional, z.B. 14:00)"
                ],
                "service" => [
                    "type" => "string",
                    "description" => "Art der Dienstleistung (optional)"
                ]
            ],
            "required" => ["date"]
        ]
    ],
    [
        "type" => "custom",
        "name" => "book_appointment_cal",
        "description" => "Buche einen Termin nachdem die Verfügbarkeit geprüft wurde. NIEMALS nach Telefonnummer fragen!",
        "url" => "https://api.askproai.de/api/retell/book-appointment",
        "speak_during_execution" => true,
        "speak_after_execution" => true,
        "parameters" => [
            "type" => "object",
            "properties" => [
                "call_id" => [
                    "type" => "string",
                    "description" => "Die Call ID (IMMER {{call_id}} verwenden)"
                ],
                "date" => [
                    "type" => "string",
                    "description" => "Datum im Format YYYY-MM-DD"
                ],
                "time" => [
                    "type" => "string",
                    "description" => "Uhrzeit im Format HH:MM"
                ],
                "name" => [
                    "type" => "string",
                    "description" => "Name des Kunden"
                ],
                "email" => [
                    "type" => "string",
                    "description" => "E-Mail des Kunden (optional)"
                ],
                "notes" => [
                    "type" => "string",
                    "description" => "Zusätzliche Notizen (optional)"
                ],
                "service" => [
                    "type" => "string",
                    "description" => "Gewünschte Dienstleistung"
                ]
            ],
            "required" => ["call_id", "date", "time", "name", "service"]
        ]
    ],
    [
        "type" => "custom",
        "name" => "current_time_berlin",
        "description" => "Hole die aktuelle Uhrzeit in Berlin/Deutschland",
        "url" => "https://api.askproai.de/api/retell/current-time-berlin",
        "speak_during_execution" => false,
        "speak_after_execution" => false,
        "parameters" => [
            "type" => "object",
            "properties" => [],
            "required" => []
        ]
    ],
    [
        "type" => "custom",
        "name" => "collect_appointment_data",
        "description" => "Sammle alle Termindaten und buche den Termin. NIEMALS nach Telefonnummer fragen - verwende call_id!",
        "url" => "https://api.askproai.de/api/retell/collect-appointment",
        "speak_during_execution" => true,
        "speak_after_execution" => true,
        "parameters" => [
            "type" => "object",
            "properties" => [
                "call_id" => [
                    "type" => "string",
                    "description" => "Die Call ID (IMMER {{call_id}} verwenden)"
                ],
                "name" => [
                    "type" => "string",
                    "description" => "Name des Kunden"
                ],
                "datum" => [
                    "type" => "string",
                    "description" => "Datum (z.B. 'heute', 'morgen', '25.06.2025')"
                ],
                "uhrzeit" => [
                    "type" => "string",
                    "description" => "Uhrzeit im 24h Format (z.B. '14:00', '09:30')"
                ],
                "dienstleistung" => [
                    "type" => "string",
                    "description" => "Gewünschte Dienstleistung"
                ],
                "email" => [
                    "type" => "string",
                    "description" => "E-Mail für Bestätigung (optional)"
                ]
            ],
            "required" => ["call_id", "name", "datum", "uhrzeit"]
        ]
    ],
    [
        "type" => "custom",
        "name" => "check_customer",
        "description" => "Prüfe ob Kunde existiert basierend auf der Anrufer-Telefonnummer. IMMER zu Beginn aufrufen!",
        "url" => "https://api.askproai.de/api/retell/check-customer",
        "speak_during_execution" => false,
        "speak_after_execution" => false,
        "parameters" => [
            "type" => "object",
            "properties" => [
                "call_id" => [
                    "type" => "string",
                    "description" => "Die Call ID (IMMER {{call_id}} verwenden)"
                ]
            ],
            "required" => ["call_id"]
        ]
    ]
];

// Update the prompt to add instruction
$currentPrompt = $llmConfig['general_prompt'] ?? '';

// Add instruction if not already present
if (strpos($currentPrompt, 'NIEMALS nach der Telefonnummer fragen') === false) {
    // Find a good place to insert the instruction
    $lines = explode("\n", $currentPrompt);
    $insertIndex = -1;
    
    // Look for "WICHTIGE" or similar section
    foreach ($lines as $index => $line) {
        if (stripos($line, 'WICHTIGE') !== false || stripos($line, 'ANWEISUNG') !== false) {
            $insertIndex = $index + 1;
            break;
        }
    }
    
    if ($insertIndex > 0) {
        array_splice($lines, $insertIndex, 0, "- NIEMALS nach der Telefonnummer fragen - verwende immer die call_id aus {{call_id}}");
        $updatedPrompt = implode("\n", $lines);
    } else {
        // Add at the beginning if no suitable section found
        $updatedPrompt = "WICHTIGE ANWEISUNGEN:\n- NIEMALS nach der Telefonnummer fragen - verwende immer die call_id aus {{call_id}}\n\n" . $currentPrompt;
    }
} else {
    $updatedPrompt = $currentPrompt;
    echo "Prompt already contains phone number instruction\n";
}

// Prepare the update payload
$updatePayload = [
    'general_prompt' => $updatedPrompt,
    'general_tools' => $newCustomFunctions,
    // Keep other settings
    'model' => $llmConfig['model'] ?? 'gpt-4',
    'temperature' => $llmConfig['temperature'] ?? 0.7,
    'tools' => $llmConfig['tools'] ?? [],
    'knowledge_base' => $llmConfig['knowledge_base'] ?? null
];

echo "\nUpdating LLM configuration...\n";

// Update the LLM
$updateResponse = Http::withHeaders([
    'Authorization' => 'Bearer ' . $company->retell_api_key,
    'Content-Type' => 'application/json',
])->patch("https://api.retellai.com/update-retell-llm/{$llmId}", $updatePayload);

if ($updateResponse->successful()) {
    echo "✅ LLM configuration updated successfully!\n";
    echo "\nChanges made:\n";
    echo "- Added phone number instruction to prompt\n";
    echo "- Updated " . count($newCustomFunctions) . " custom functions\n";
    echo "- All functions now use call_id parameter\n";
    echo "\n✅ The agent is now configured to:\n";
    echo "  1. Never ask for phone numbers\n";
    echo "  2. Use call_id to resolve phone numbers\n";
    echo "  3. Handle appointments correctly\n";
} else {
    echo "❌ Failed to update LLM: " . $updateResponse->status() . "\n";
    echo "Response: " . $updateResponse->body() . "\n";
}

echo "\nDone!\n";