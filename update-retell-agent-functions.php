<?php

/**
 * Update Retell Agent Custom Functions
 * This script updates the agent's custom functions to use call_id for phone number resolution
 */

require_once __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;
use App\Models\Company;

$agentId = 'agent_9a8202a740cd3120d96fcfda1e';

// Get company with Retell API key
$company = Company::first();
if (!$company || !$company->retell_api_key) {
    die("Error: No company with Retell API key found\n");
}

$apiKey = $company->retell_api_key;

// First, get the current agent configuration
echo "Fetching current agent configuration...\n";
$response = Http::withHeaders([
    'Authorization' => 'Bearer ' . $apiKey,
])->get("https://api.retellai.com/get-agent/{$agentId}");

if (!$response->successful()) {
    die("Error fetching agent: " . $response->body() . "\n");
}

$agentData = $response->json();
echo "Current agent version: " . ($agentData['version_id'] ?? 'unknown') . "\n";

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
$currentPrompt = $agentData['llm_configuration']['general_prompt'] ?? '';

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
} else {
    $updatedPrompt = $currentPrompt;
    echo "Prompt already contains phone number instruction\n";
}

// Prepare the update payload
$updateData = [
    'agent_id' => $agentId,
    'llm_configuration' => [
        'general_prompt' => $updatedPrompt,
        'general_tools' => $customFunctions,
        // Keep other llm_configuration settings
        'model' => $agentData['llm_configuration']['model'] ?? 'gpt-4',
        'temperature' => $agentData['llm_configuration']['temperature'] ?? 0.7,
        'max_tokens' => $agentData['llm_configuration']['max_tokens'] ?? 4096,
    ],
    // Keep all other agent settings
    'agent_name' => $agentData['agent_name'],
    'voice_id' => $agentData['voice_id'],
    'language' => $agentData['language'],
    'interruption_sensitivity' => $agentData['interruption_sensitivity'] ?? 0.5,
    'enable_backchannel' => $agentData['enable_backchannel'] ?? true,
    'boosted_keywords' => $agentData['boosted_keywords'] ?? [],
    'webhook_url' => $agentData['webhook_url'],
    'end_call_after_silence_ms' => $agentData['end_call_after_silence_ms'] ?? 10000,
];

echo "\nUpdating agent configuration...\n";

// Create a new version with the updates
$response = Http::withHeaders([
    'Authorization' => 'Bearer ' . $apiKey,
    'Content-Type' => 'application/json',
])->patch("https://api.retellai.com/update-agent/{$agentId}", $updateData);

if ($response->successful()) {
    $result = $response->json();
    echo "✅ Agent updated successfully!\n";
    echo "New version: " . ($result['version_id'] ?? 'unknown') . "\n";
    
    // Now publish the new version
    echo "\nPublishing new version...\n";
    $versionId = $result['version_id'] ?? null;
    
    if ($versionId) {
        $publishResponse = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
        ])->post("https://api.retellai.com/publish-agent-version/{$agentId}", [
            'version_id' => $versionId
        ]);
        
        if ($publishResponse->successful()) {
            echo "✅ Version published successfully!\n";
        } else {
            echo "❌ Failed to publish version: " . $publishResponse->body() . "\n";
        }
    }
} else {
    echo "❌ Failed to update agent: " . $response->body() . "\n";
}

echo "\nDone!\n";