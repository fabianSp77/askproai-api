<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Services\RetellV2Service;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== CHECKING RETELL LLM CONFIGURATION ===\n\n";

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
    
    if (!$llmId) {
        throw new Exception("No LLM ID found!");
    }
    
    echo "Agent: " . $agent['agent_name'] . "\n";
    echo "LLM ID: $llmId\n\n";
    
    // Get LLM configuration
    $llmConfig = $retellService->getRetellLLM($llmId);
    
    if (!$llmConfig) {
        throw new Exception("LLM configuration not found!");
    }
    
    echo "Current general_tools:\n";
    echo json_encode($llmConfig['general_tools'] ?? [], JSON_PRETTY_PRINT) . "\n\n";
    
    // Check what's at index 2 and 3
    if (isset($llmConfig['general_tools'])) {
        foreach ($llmConfig['general_tools'] as $index => $tool) {
            echo "Tool $index:\n";
            echo "  Name: " . ($tool['name'] ?? 'N/A') . "\n";
            echo "  Type: " . ($tool['type'] ?? 'N/A') . "\n";
            
            // The error mentions allowed values for type
            if (isset($tool['type']) && $tool['type'] !== 'end_call') {
                echo "  ⚠️  Type '" . $tool['type'] . "' might not be valid\n";
            }
            echo "\n";
        }
    }
    
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}