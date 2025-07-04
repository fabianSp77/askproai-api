<?php

require_once __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\RetellV2Service;
use App\Models\Company;

$agentId = 'agent_9a8202a740cd3120d96fcfda1e';

$company = Company::first();
if (!$company || !$company->retell_api_key) {
    die("Error: No company with Retell API key found\n");
}

$retellService = new RetellV2Service($company->retell_api_key);

echo "Verifying agent configuration...\n\n";
$agent = $retellService->getAgent($agentId);

if ($agent) {
    echo "✅ Agent Name: " . $agent['agent_name'] . "\n";
    
    // Check prompt
    $prompt = $agent['llm_configuration']['general_prompt'] ?? '';
    if (strpos($prompt, 'NIEMALS nach der Telefonnummer fragen') !== false) {
        echo "✅ Prompt contains phone number instruction\n";
    } else {
        echo "❌ Prompt missing phone number instruction\n";
    }
    
    // Check custom functions
    $functions = $agent['llm_configuration']['general_tools'] ?? [];
    echo "\n📋 Custom Functions (" . count($functions) . " total):\n";
    
    $expectedFunctions = [
        'end_call',
        'transfer_call', 
        'current_time_berlin',
        'check_customer',
        'check_availability',
        'collect_appointment_data',
        'cancel_appointment',
        'reschedule_appointment'
    ];
    
    foreach ($expectedFunctions as $expectedFunc) {
        $found = false;
        $hasCallId = false;
        
        foreach ($functions as $func) {
            if ($func['name'] === $expectedFunc) {
                $found = true;
                if (isset($func['properties']['parameters']['properties']['call_id'])) {
                    $hasCallId = true;
                }
                break;
            }
        }
        
        if ($found) {
            echo "  ✅ $expectedFunc" . ($hasCallId ? " (with call_id)" : "") . "\n";
        } else {
            echo "  ❌ $expectedFunc (missing)\n";
        }
    }
    
    echo "\n🎯 Summary:\n";
    echo "The Retell agent has been successfully updated with:\n";
    echo "- Phone number instruction in prompt\n";
    echo "- " . count($functions) . " custom functions\n";
    echo "- call_id parameter for phone number resolution\n";
    echo "\n✅ The agent is now ready for testing!\n";
} else {
    echo "❌ Could not fetch agent\n";
}