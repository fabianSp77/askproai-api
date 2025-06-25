<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Services\RetellV2Service;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== FINDING APPOINTMENT INSTRUCTIONS IN PROMPT ===\n\n";

try {
    $apiKey = env('DEFAULT_RETELL_API_KEY') ?? env('RETELL_TOKEN');
    $retellService = new RetellV2Service($apiKey);
    
    $agentId = 'agent_9a8202a740cd3120d96fcfda1e';
    $agent = $retellService->getAgent($agentId);
    $llmId = $agent['response_engine']['llm_id'] ?? null;
    $llmConfig = $retellService->getRetellLLM($llmId);
    
    $prompt = $llmConfig['general_prompt'] ?? '';
    
    // Search for appointment-related sections
    $searchTerms = [
        'collect_appointment_data',
        'Terminbuchung',
        'Termin vereinbaren',
        'appointment',
        'datum',
        'uhrzeit',
        'name',
        'dienstleistung'
    ];
    
    foreach ($searchTerms as $term) {
        $pos = stripos($prompt, $term);
        if ($pos !== false) {
            echo "\nFound '$term' at position $pos:\n";
            echo "---\n";
            // Show context around the term
            $start = max(0, $pos - 200);
            $end = min(strlen($prompt), $pos + 500);
            $context = substr($prompt, $start, $end - $start);
            
            // Highlight the term
            $context = str_ireplace($term, "**$term**", $context);
            echo $context . "\n";
            echo "---\n";
        }
    }
    
    // Check for specific function call instruction pattern
    if (preg_match('/collect_appointment_data.*?(?=\n\n|$)/is', $prompt, $matches)) {
        echo "\n\n=== COLLECT_APPOINTMENT_DATA INSTRUCTIONS ===\n";
        echo $matches[0] . "\n";
    }
    
    // Look for numbered instructions about appointments
    if (preg_match('/\d+\.\s*.*?(Termin|appointment|collect_appointment).*?(?=\n\d+\.|$)/is', $prompt, $matches)) {
        echo "\n\n=== NUMBERED APPOINTMENT INSTRUCTIONS ===\n";
        echo $matches[0] . "\n";
    }
    
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}