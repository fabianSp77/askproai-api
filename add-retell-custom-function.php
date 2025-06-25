<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Services\RetellV2Service;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== ADDING CUSTOM FUNCTION TO RETELL AGENT ===\n\n";

try {
    // Get API key
    $apiKey = env('DEFAULT_RETELL_API_KEY') ?? env('RETELL_TOKEN');
    $retellService = new RetellV2Service($apiKey);
    
    // Agent ID for "Assistent für Fabian Spitzer Rechtliches V33"
    $agentId = 'agent_9a8202a740cd3120d96fcfda1e';
    
    echo "Step 1: Getting agent details...\n";
    $agent = $retellService->getAgent($agentId);
    
    if (!$agent) {
        throw new Exception("Agent not found!");
    }
    
    echo "Agent Name: " . ($agent['agent_name'] ?? 'N/A') . "\n";
    
    // Check if agent uses retell-llm
    if (!isset($agent['response_engine']['type']) || $agent['response_engine']['type'] !== 'retell-llm') {
        throw new Exception("Agent doesn't use retell-llm response engine!");
    }
    
    $llmId = $agent['response_engine']['llm_id'] ?? null;
    
    if (!$llmId) {
        throw new Exception("No LLM ID found in agent configuration!");
    }
    
    echo "LLM ID: $llmId\n\n";
    
    echo "Step 2: Getting current LLM configuration...\n";
    $llmConfig = $retellService->getRetellLLM($llmId);
    
    if (!$llmConfig) {
        throw new Exception("LLM configuration not found!");
    }
    
    // Prepare the custom function
    $customFunction = [
        'type' => 'remote_tool',
        'name' => 'collect_appointment_data',
        'description' => 'Sammelt alle notwendigen Termindaten vom Anrufer für die Terminbuchung',
        'url' => 'https://api.askproai.de/api/retell/collect-appointment',
        'method' => 'POST',
        'headers' => [
            'Content-Type' => 'application/json'
        ],
        'speak_during_execution' => true,
        'speak_after_execution' => true,
        'execution_message_description' => 'Einen Moment bitte, ich verarbeite Ihre Termindaten...',
        'parameters' => [
            'type' => 'object',
            'properties' => [
                'datum' => [
                    'type' => 'string',
                    'description' => 'Das Datum des gewünschten Termins (z.B. "morgen", "nächsten Montag", "15.03.2024")'
                ],
                'uhrzeit' => [
                    'type' => 'string',
                    'description' => 'Die gewünschte Uhrzeit (z.B. "10:00", "14:30", "nachmittags")'
                ],
                'name' => [
                    'type' => 'string',
                    'description' => 'Der vollständige Name des Kunden'
                ],
                'telefonnummer' => [
                    'type' => 'string',
                    'description' => 'Die Telefonnummer des Kunden (wird automatisch vom System gefüllt wenn möglich)'
                ],
                'dienstleistung' => [
                    'type' => 'string',
                    'description' => 'Die gewünschte Dienstleistung oder der Grund des Termins'
                ],
                'email' => [
                    'type' => 'string',
                    'description' => 'E-Mail-Adresse für die Terminbestätigung (optional)'
                ],
                'mitarbeiter_wunsch' => [
                    'type' => 'string',
                    'description' => 'Bevorzugter Mitarbeiter (optional)'
                ],
                'kundenpraeferenzen' => [
                    'type' => 'string',
                    'description' => 'Besondere Wünsche oder Präferenzen (optional)'
                ]
            ],
            'required' => ['datum', 'uhrzeit', 'name', 'telefonnummer', 'dienstleistung']
        ]
    ];
    
    // Get current general_tools or initialize empty array
    $currentTools = $llmConfig['general_tools'] ?? [];
    
    // Check if function already exists
    $functionExists = false;
    foreach ($currentTools as $index => $tool) {
        if (isset($tool['name']) && $tool['name'] === 'collect_appointment_data') {
            echo "Function already exists at index $index. Updating...\n";
            $currentTools[$index] = $customFunction;
            $functionExists = true;
            break;
        }
    }
    
    if (!$functionExists) {
        echo "Adding new function...\n";
        $currentTools[] = $customFunction;
    }
    
    // Prepare update data
    $updateData = [
        'general_tools' => $currentTools
    ];
    
    // Also update the general prompt if needed
    $currentPrompt = $llmConfig['general_prompt'] ?? '';
    
    if (strpos($currentPrompt, 'collect_appointment_data') === false) {
        echo "\nUpdating prompt to include appointment booking instructions...\n";
        
        $appointmentInstructions = "\n\n### TERMINBUCHUNG:\nWenn ein Kunde einen Termin buchen möchte:\n1. Sammle ALLE erforderlichen Informationen:\n   - Datum (frage nach dem gewünschten Tag)\n   - Uhrzeit (frage nach der bevorzugten Zeit)\n   - Name (frage nach dem vollständigen Namen)\n   - Dienstleistung (was möchte der Kunde buchen?)\n   - Optional: E-Mail-Adresse für die Bestätigung\n   - Optional: Mitarbeiterwunsch\n\n2. Die Telefonnummer wird automatisch erfasst - du musst nicht danach fragen.\n\n3. Sobald du ALLE Pflichtinformationen hast, rufe die Funktion 'collect_appointment_data' auf.\n\n4. Nach erfolgreichem Aufruf der Funktion:\n   - Bestätige dem Kunden die erfolgreiche Terminbuchung\n   - Nenne die Referenznummer aus der Antwort\n   - Erwähne, dass eine Bestätigung per SMS/E-Mail folgt";
        
        $updateData['general_prompt'] = $currentPrompt . $appointmentInstructions;
    }
    
    echo "\nStep 3: Updating LLM configuration...\n";
    $result = $retellService->updateRetellLLM($llmId, $updateData);
    
    if ($result) {
        echo "\n✅ SUCCESS! Custom function has been added to the agent.\n";
        echo "\nVerifying the update...\n";
        
        // Verify the update
        $updatedLLM = $retellService->getRetellLLM($llmId);
        $toolCount = count($updatedLLM['general_tools'] ?? []);
        
        echo "Total custom functions now: $toolCount\n";
        
        // Check if our function is there
        $verified = false;
        foreach ($updatedLLM['general_tools'] ?? [] as $tool) {
            if ($tool['name'] === 'collect_appointment_data') {
                $verified = true;
                break;
            }
        }
        
        if ($verified) {
            echo "✅ Verified: collect_appointment_data function is configured!\n";
        } else {
            echo "⚠️  Warning: Function may not have been saved properly.\n";
        }
        
        echo "\n🎉 Die Custom Function wurde erfolgreich hinzugefügt!\n";
        echo "\nNächste Schritte:\n";
        echo "1. Führe einen Testanruf durch: +493083793369\n";
        echo "2. Sage: 'Ich möchte einen Termin buchen'\n";
        echo "3. Beantworte alle Fragen des Assistenten\n";
        echo "4. Der Assistent sollte jetzt die Terminbuchung durchführen können!\n";
        
    } else {
        echo "\n❌ ERROR: Failed to update LLM configuration.\n";
    }
    
} catch (\Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}