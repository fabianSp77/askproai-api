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

// Keep existing functions but update collect_appointment_data to use call_id
$updatedFunctions = [];

foreach ($llmConfig['general_tools'] as $tool) {
    if ($tool['name'] === 'collect_appointment_data') {
        // Update this function to use call_id
        $tool['description'] = 'Sammle alle Termindaten und buche den Termin. NIEMALS nach Telefonnummer fragen!';
        
        // Update parameters to include call_id
        if (isset($tool['parameters']['properties'])) {
            // Add call_id as first parameter
            $newProperties = [
                'call_id' => [
                    'type' => 'string',
                    'description' => 'Die Call ID - verwende IMMER {{call_id}}'
                ]
            ];
            
            // Keep other properties but remove telefonnummer if it exists
            foreach ($tool['parameters']['properties'] as $key => $prop) {
                if ($key !== 'telefonnummer' && $key !== 'phone_number') {
                    $newProperties[$key] = $prop;
                }
            }
            
            $tool['parameters']['properties'] = $newProperties;
            
            // Update required to include call_id
            if (isset($tool['parameters']['required'])) {
                if (!in_array('call_id', $tool['parameters']['required'])) {
                    array_unshift($tool['parameters']['required'], 'call_id');
                }
                // Remove telefonnummer from required if it exists
                $tool['parameters']['required'] = array_filter($tool['parameters']['required'], function($req) {
                    return $req !== 'telefonnummer' && $req !== 'phone_number';
                });
            }
        }
    } elseif ($tool['name'] === 'check_customer') {
        // Update check_customer to use call_id
        $tool['description'] = 'Prüfe ob Kunde existiert. IMMER zu Beginn des Gesprächs aufrufen!';
        $tool['parameters'] = [
            'type' => 'object',
            'properties' => [
                'call_id' => [
                    'type' => 'string',
                    'description' => 'Die Call ID - verwende IMMER {{call_id}}'
                ]
            ],
            'required' => ['call_id']
        ];
    }
    
    $updatedFunctions[] = $tool;
}

// Add current_time_berlin if it doesn't exist
$hasCurrentTime = false;
foreach ($updatedFunctions as $func) {
    if ($func['name'] === 'current_time_berlin') {
        $hasCurrentTime = true;
        break;
    }
}

if (!$hasCurrentTime) {
    $updatedFunctions[] = [
        'type' => 'custom',
        'name' => 'current_time_berlin',
        'description' => 'Hole die aktuelle Uhrzeit in Berlin/Deutschland',
        'url' => 'https://api.askproai.de/api/retell/current-time-berlin',
        'speak_during_execution' => false,
        'speak_after_execution' => false,
        'parameters' => [
            'type' => 'object',
            'properties' => (object)[],
            'required' => []
        ]
    ];
}

// Update the prompt
$currentPrompt = $llmConfig['general_prompt'] ?? '';

// Add instruction if not already present
if (strpos($currentPrompt, 'NIEMALS nach der Telefonnummer fragen') === false) {
    // Find the line with "WICHTIGE ANWEISUNGEN" or similar
    $lines = explode("\n", $currentPrompt);
    $insertIndex = -1;
    
    foreach ($lines as $index => $line) {
        if (stripos($line, 'WICHTIGE ANWEISUNGEN') !== false) {
            // Insert after this line
            $insertIndex = $index + 1;
            break;
        }
    }
    
    if ($insertIndex > 0) {
        array_splice($lines, $insertIndex, 0, "- NIEMALS nach der Telefonnummer fragen - die Telefonnummer ist bereits über {{caller_phone_number}} verfügbar oder verwende call_id");
        $updatedPrompt = implode("\n", $lines);
    } else {
        // Add at the beginning
        $updatedPrompt = "WICHTIGE ANWEISUNGEN:\n- NIEMALS nach der Telefonnummer fragen - die Telefonnummer ist bereits über {{caller_phone_number}} verfügbar oder verwende call_id\n\n" . $currentPrompt;
    }
    echo "✅ Added phone number instruction to prompt\n";
} else {
    $updatedPrompt = $currentPrompt;
    echo "ℹ️  Prompt already contains phone number instruction\n";
}

// Prepare the update payload - only include what Retell expects
$updatePayload = [
    'general_prompt' => $updatedPrompt,
    'general_tools' => $updatedFunctions
];

echo "\nUpdating LLM configuration...\n";
echo "Updating " . count($updatedFunctions) . " functions\n";

// Update the LLM
$updateResponse = Http::withHeaders([
    'Authorization' => 'Bearer ' . $company->retell_api_key,
    'Content-Type' => 'application/json',
])->patch("https://api.retellai.com/update-retell-llm/{$llmId}", $updatePayload);

if ($updateResponse->successful()) {
    echo "✅ LLM configuration updated successfully!\n";
    
    // Verify the update
    echo "\nVerifying update...\n";
    $verifyResponse = Http::withHeaders([
        'Authorization' => 'Bearer ' . $company->retell_api_key,
    ])->get("https://api.retellai.com/get-retell-llm/{$llmId}");
    
    if ($verifyResponse->successful()) {
        $updatedLlm = $verifyResponse->json();
        $updatedTools = $updatedLlm['general_tools'] ?? [];
        
        echo "\nUpdated functions:\n";
        foreach ($updatedTools as $tool) {
            $hasCallId = isset($tool['parameters']['properties']['call_id']);
            echo "- " . $tool['name'] . ($hasCallId ? " ✅ (has call_id)" : "") . "\n";
        }
    }
    
    echo "\n✅ The agent is now configured to:\n";
    echo "  1. Never ask for phone numbers\n";
    echo "  2. Use call_id parameter in relevant functions\n";
    echo "  3. Phone number will be resolved from database\n";
} else {
    echo "❌ Failed to update LLM: " . $updateResponse->status() . "\n";
    echo "Response: " . $updateResponse->body() . "\n";
}

echo "\nDone!\n";