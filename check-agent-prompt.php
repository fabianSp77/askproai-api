<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Services\RetellV2Service;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== CHECKING AGENT PROMPT ===\n\n";

try {
    // Get API key
    $apiKey = env('DEFAULT_RETELL_API_KEY') ?? env('RETELL_TOKEN');
    $retellService = new RetellV2Service($apiKey);
    
    // Agent ID
    $agentId = 'agent_9a8202a740cd3120d96fcfda1e';
    
    // Get agent details
    $agent = $retellService->getAgent($agentId);
    
    if (!$agent) {
        throw new Exception("Agent not found!");
    }
    
    $llmId = $agent['response_engine']['llm_id'] ?? null;
    
    // Get LLM configuration
    $llmConfig = $retellService->getRetellLLM($llmId);
    
    $currentPrompt = $llmConfig['general_prompt'] ?? '';
    
    echo "Current Prompt Length: " . strlen($currentPrompt) . " characters\n\n";
    
    // Check if prompt mentions appointment booking
    $hasAppointmentInstructions = false;
    $keywords = ['collect_appointment_data', 'Terminbuchung', 'Termin buchen', 'appointment'];
    
    foreach ($keywords as $keyword) {
        if (stripos($currentPrompt, $keyword) !== false) {
            $hasAppointmentInstructions = true;
            echo "✅ Prompt mentions: $keyword\n";
        } else {
            echo "❌ Prompt does NOT mention: $keyword\n";
        }
    }
    
    echo "\n";
    
    if (!$hasAppointmentInstructions) {
        echo "⚠️  PROBLEM: Der Prompt enthält keine expliziten Anweisungen zur Terminbuchung!\n\n";
        echo "Das ist wahrscheinlich der Grund, warum die Terminbuchung nicht funktioniert.\n";
        echo "Der Agent weiß nicht, dass er die collect_appointment_data Function nutzen soll.\n\n";
        
        echo "LÖSUNG: Prompt erweitern mit Terminbuchungs-Anweisungen\n\n";
    } else {
        echo "✅ Der Prompt enthält bereits Anweisungen zur Terminbuchung.\n\n";
    }
    
    // Show relevant part of prompt if it exists
    echo "=== PROMPT PREVIEW (first 500 chars) ===\n";
    echo substr($currentPrompt, 0, 500) . "...\n\n";
    
    // Check for specific sections
    if (strpos($currentPrompt, '### TERMINBUCHUNG') !== false) {
        echo "✅ Found TERMINBUCHUNG section in prompt\n";
        
        // Extract and show that section
        $start = strpos($currentPrompt, '### TERMINBUCHUNG');
        $section = substr($currentPrompt, $start, 1000);
        echo "\n=== TERMINBUCHUNG SECTION ===\n";
        echo $section . "\n";
    }
    
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}