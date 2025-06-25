<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Services\RetellV2Service;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== FIXING AGENT PROMPT - TELEFONNUMMER ISSUE ===\n\n";

try {
    $apiKey = env('DEFAULT_RETELL_API_KEY') ?? env('RETELL_TOKEN');
    $retellService = new RetellV2Service($apiKey);
    
    $agentId = 'agent_9a8202a740cd3120d96fcfda1e';
    $agent = $retellService->getAgent($agentId);
    $llmId = $agent['response_engine']['llm_id'] ?? null;
    
    echo "Agent: " . $agent['agent_name'] . "\n";
    echo "LLM ID: $llmId\n\n";
    
    // Get current LLM config
    $llmConfig = $retellService->getRetellLLM($llmId);
    $currentPrompt = $llmConfig['general_prompt'] ?? '';
    
    echo "PROBLEM IDENTIFIED:\n";
    echo "The prompt says to ONLY include telefonnummer when manually asked.\n";
    echo "But the collect_appointment_data function REQUIRES telefonnummer!\n\n";
    
    // Fix the prompt
    $updatedPrompt = $currentPrompt;
    
    // Find and replace the problematic section
    $problematicSection = '### Bei der collect_appointment_data Funktion:
  - Wenn Telefonnummer vom System erfasst wurde (Normalfall):
  ```json
  {
    "datum": "24.06.2025",
    "uhrzeit": "16:30",
    "name": "Hans Schuster",
    "dienstleistung": "Beratung",
    "email": "hans@beispiel.de"
  }
  - Wenn Telefonnummer manuell erfragt werden musste (Ausnahmefall):
  {
    "datum": "24.06.2025",
    "uhrzeit": "16:30",
    "name": "Hans Schuster",
    "telefonnummer": "+49 30 12345678",
    "dienstleistung": "Beratung",
    "email": "hans@beispiel.de"
  }';
  
    $correctedSection = '### Bei der collect_appointment_data Funktion:
  - WICHTIG: Das Feld "telefonnummer" ist IMMER erforderlich!
  - Verwende die vom System erfasste Nummer oder frage danach
  - Beispiel für den Funktionsaufruf:
  ```json
  {
    "datum": "24.06.2025",
    "uhrzeit": "16:30",
    "name": "Hans Schuster",
    "telefonnummer": "+49 30 12345678",
    "dienstleistung": "Beratung",
    "email": "hans@beispiel.de"
  }
  ```
  - Die Telefonnummer wird meist automatisch vom System bereitgestellt
  - Falls nicht verfügbar, frage: "Unter welcher Nummer kann ich Sie erreichen?"';
    
    // Replace the section
    if (strpos($updatedPrompt, '### Bei der collect_appointment_data Funktion:') !== false) {
        // Find the exact section to replace
        $startPos = strpos($updatedPrompt, '### Bei der collect_appointment_data Funktion:');
        $endPos = strpos($updatedPrompt, 'E-Mail (für Bestätigung):', $startPos);
        
        if ($endPos !== false) {
            $beforeSection = substr($updatedPrompt, 0, $startPos);
            $afterSection = substr($updatedPrompt, $endPos);
            $updatedPrompt = $beforeSection . $correctedSection . "\n\n  " . $afterSection;
            
            echo "✅ Found and will replace the problematic section\n\n";
        }
    }
    
    // Also update the instruction about collect_appointment_data parameters
    $updatedPrompt = str_replace(
        'Nutze nach Erhalt aller   Informationen die Funktion `collect_appointment_data`.',
        'Nutze nach Erhalt aller Informationen die Funktion `collect_appointment_data`. WICHTIG: Das Feld "telefonnummer" ist IMMER erforderlich - verwende die vom System bereitgestellte Nummer oder die manuell erfragte.',
        $updatedPrompt
    );
    
    // Update the LLM
    echo "Updating LLM prompt...\n";
    $updateData = [
        'general_prompt' => $updatedPrompt
    ];
    
    $result = $retellService->updateRetellLLM($llmId, $updateData);
    
    if ($result) {
        echo "\n✅ SUCCESS! Agent prompt has been updated.\n\n";
        
        echo "KEY CHANGES:\n";
        echo "1. telefonnummer is now ALWAYS included in collect_appointment_data\n";
        echo "2. Agent will use system-provided number or ask for it\n";
        echo "3. Function call will now include all required fields\n\n";
        
        echo "NEXT STEPS:\n";
        echo "1. Make a test call to +493083793369\n";
        echo "2. Say: 'Ich möchte einen Termin buchen'\n";
        echo "3. Provide the appointment details\n";
        echo "4. The booking should now work correctly!\n";
        
    } else {
        echo "\n❌ ERROR: Failed to update prompt.\n";
    }
    
} catch (\Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}