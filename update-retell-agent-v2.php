<?php

require_once __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\RetellV2Service;
use App\Models\Company;
use App\Models\RetellAgent;

$agentId = 'agent_9a8202a740cd3120d96fcfda1e';

// Get company with Retell API key
$company = Company::first();
if (!$company || !$company->retell_api_key) {
    die("Error: No company with Retell API key found\n");
}

$retellService = new RetellV2Service($company->retell_api_key);

// Get current agent configuration
echo "Fetching current agent configuration...\n";
$currentAgent = $retellService->getAgent($agentId);

if (!$currentAgent) {
    die("Error: Could not fetch agent configuration\n");
}

echo "Current agent: " . $currentAgent['agent_name'] . "\n";

// Prepare the new custom functions
$customFunctions = [
    [
        "name" => "end_call",
        "description" => "Das Gespräch höflich beenden",
        "url" => "https://api.askproai.de/api/retell/end-call",
        "speak_during_execution" => true,
        "speak_after_execution" => false,
        "properties" => [
            "parameters" => [
                "type" => "object",
                "properties" => [
                    "reason" => [
                        "type" => "string",
                        "description" => "Grund für Beendigung"
                    ]
                ]
            ]
        ]
    ],
    [
        "name" => "transfer_call",
        "description" => "Anruf an Mitarbeiter weiterleiten",
        "url" => "https://api.askproai.de/api/retell/transfer-call",
        "speak_during_execution" => true,
        "speak_after_execution" => false,
        "properties" => [
            "parameters" => [
                "type" => "object",
                "properties" => [
                    "number" => [
                        "type" => "string",
                        "description" => "Telefonnummer für Weiterleitung"
                    ]
                ],
                "required" => ["number"]
            ]
        ]
    ],
    [
        "name" => "current_time_berlin",
        "description" => "Aktuelle Zeit in Berlin abrufen",
        "url" => "https://api.askproai.de/api/retell/current-time-berlin",
        "speak_during_execution" => false,
        "speak_after_execution" => false,
        "properties" => [
            "parameters" => [
                "type" => "object",
                "properties" => []
            ]
        ]
    ],
    [
        "name" => "check_customer",
        "description" => "Prüfe ob Kunde existiert. IMMER am Gesprächsanfang aufrufen.",
        "url" => "https://api.askproai.de/api/retell/check-customer",
        "speak_during_execution" => false,
        "speak_after_execution" => false,
        "properties" => [
            "parameters" => [
                "type" => "object",
                "properties" => [
                    "call_id" => [
                        "type" => "string",
                        "description" => "Call ID (immer {{call_id}} verwenden)"
                    ]
                ],
                "required" => ["call_id"]
            ]
        ]
    ],
    [
        "name" => "check_availability",
        "description" => "Verfügbare Termine für ein Datum prüfen",
        "url" => "https://api.askproai.de/api/retell/check-availability",
        "speak_during_execution" => true,
        "speak_after_execution" => true,
        "properties" => [
            "parameters" => [
                "type" => "object",
                "properties" => [
                    "date" => [
                        "type" => "string",
                        "description" => "Datum (z.B. 'heute', 'morgen', '25.06.2025')"
                    ],
                    "time" => [
                        "type" => "string",
                        "description" => "Gewünschte Uhrzeit (optional, z.B. '09:00', '14:30')"
                    ]
                ],
                "required" => ["date"]
            ]
        ]
    ],
    [
        "name" => "collect_appointment_data",
        "description" => "Termin buchen mit allen gesammelten Daten. NIEMALS nach Telefonnummer fragen!",
        "url" => "https://api.askproai.de/api/retell/collect-appointment",
        "speak_during_execution" => true,
        "speak_after_execution" => true,
        "properties" => [
            "parameters" => [
                "type" => "object",
                "properties" => [
                    "call_id" => [
                        "type" => "string",
                        "description" => "Call ID (immer {{call_id}} verwenden)"
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
        ]
    ],
    [
        "name" => "cancel_appointment",
        "description" => "Termin stornieren",
        "url" => "https://api.askproai.de/api/retell/cancel-appointment",
        "speak_during_execution" => true,
        "speak_after_execution" => true,
        "properties" => [
            "parameters" => [
                "type" => "object",
                "properties" => [
                    "call_id" => [
                        "type" => "string",
                        "description" => "Call ID (immer {{call_id}} verwenden)"
                    ],
                    "appointment_date" => [
                        "type" => "string",
                        "description" => "Datum des zu stornierenden Termins"
                    ]
                ],
                "required" => ["call_id", "appointment_date"]
            ]
        ]
    ],
    [
        "name" => "reschedule_appointment",
        "description" => "Termin verschieben",
        "url" => "https://api.askproai.de/api/retell/reschedule-appointment",
        "speak_during_execution" => true,
        "speak_after_execution" => true,
        "properties" => [
            "parameters" => [
                "type" => "object",
                "properties" => [
                    "call_id" => [
                        "type" => "string",
                        "description" => "Call ID (immer {{call_id}} verwenden)"
                    ],
                    "old_date" => [
                        "type" => "string",
                        "description" => "Aktuelles Datum des Termins"
                    ],
                    "new_date" => [
                        "type" => "string",
                        "description" => "Neues gewünschtes Datum"
                    ],
                    "new_time" => [
                        "type" => "string",
                        "description" => "Neue gewünschte Uhrzeit"
                    ]
                ],
                "required" => ["call_id", "old_date", "new_date", "new_time"]
            ]
        ]
    ]
];

// Update the prompt - add instruction to never ask for phone number
$currentPrompt = $currentAgent['llm_configuration']['general_prompt'] ?? '';

// Check if the instruction is already there
if (strpos($currentPrompt, 'NIEMALS nach der Telefonnummer fragen') === false) {
    // Find the "WICHTIGE ANWEISUNGEN" section
    $promptParts = explode('WICHTIGE ANWEISUNGEN:', $currentPrompt);
    if (count($promptParts) > 1) {
        // Add the new instruction after "WICHTIGE ANWEISUNGEN:"
        $beforeInstructions = $promptParts[0] . 'WICHTIGE ANWEISUNGEN:';
        $afterInstructions = "\n- NIEMALS nach der Telefonnummer fragen - verwende immer {{caller_phone_number}} oder die call_id" . $promptParts[1];
        $updatedPrompt = $beforeInstructions . $afterInstructions;
    } else {
        // If no "WICHTIGE ANWEISUNGEN" found, add it at the beginning
        $updatedPrompt = "WICHTIGE ANWEISUNGEN:\n- NIEMALS nach der Telefonnummer fragen - verwende immer {{caller_phone_number}} oder die call_id\n\n" . $currentPrompt;
    }
    echo "✅ Added phone number instruction to prompt\n";
} else {
    $updatedPrompt = $currentPrompt;
    echo "ℹ️  Prompt already contains phone number instruction\n";
}

// Prepare the update configuration
$updateConfig = $currentAgent;
$updateConfig['llm_configuration']['general_prompt'] = $updatedPrompt;
$updateConfig['llm_configuration']['general_tools'] = $customFunctions;

// Ensure user_dtmf_options is an object if it exists
if (isset($updateConfig['user_dtmf_options']) && is_array($updateConfig['user_dtmf_options'])) {
    // Convert empty array to object
    if (empty($updateConfig['user_dtmf_options'])) {
        $updateConfig['user_dtmf_options'] = new \stdClass();
    }
} elseif (!isset($updateConfig['user_dtmf_options'])) {
    // Set default empty object
    $updateConfig['user_dtmf_options'] = new \stdClass();
}

// Remove fields that shouldn't be in the update
unset($updateConfig['agent_id']);
unset($updateConfig['created_at']);
unset($updateConfig['last_modification']);

echo "\nUpdating agent configuration...\n";
$result = $retellService->updateAgent($agentId, $updateConfig);

if ($result) {
    echo "✅ Agent updated successfully!\n";
    
    // Update local database
    $localAgent = RetellAgent::where('agent_id', $agentId)->first();
    if ($localAgent) {
        $localAgent->configuration = $result;
        $localAgent->save();
        echo "✅ Local database updated\n";
    }
    
    // Show summary
    echo "\nSummary:\n";
    echo "- Agent: " . $result['agent_name'] . "\n";
    echo "- Custom Functions: " . count($customFunctions) . "\n";
    echo "- Prompt updated: Yes\n";
    echo "\n✅ The agent is now configured to:\n";
    echo "  1. Never ask for phone numbers\n";
    echo "  2. Use call_id to resolve phone numbers from database\n";
    echo "  3. Handle appointment booking with proper error handling\n";
} else {
    echo "❌ Failed to update agent\n";
}

echo "\nDone!\n";