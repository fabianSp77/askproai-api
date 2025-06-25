<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Services\RetellV2Service;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== VERIFYING AGENT AND PHONE CONFIGURATION ===\n\n";

try {
    $apiKey = env('DEFAULT_RETELL_API_KEY') ?? env('RETELL_TOKEN');
    $retellService = new RetellV2Service($apiKey);
    
    // The phone number we're testing with
    $testPhoneNumber = '+493083793369';
    echo "Test Phone Number: $testPhoneNumber\n\n";
    
    // 1. Check which agent is assigned to this phone number
    echo "1. CHECKING PHONE NUMBER CONFIGURATION:\n";
    $phoneNumbers = $retellService->listPhoneNumbers();
    
    $phoneConfig = null;
    foreach ($phoneNumbers['phone_numbers'] ?? [] as $phone) {
        if ($phone['phone_number'] === $testPhoneNumber) {
            $phoneConfig = $phone;
            break;
        }
    }
    
    if (!$phoneConfig) {
        echo "❌ Phone number $testPhoneNumber NOT FOUND in Retell!\n";
        return;
    }
    
    echo "✓ Phone number found!\n";
    echo "  - Status: " . ($phoneConfig['status'] ?? 'unknown') . "\n";
    echo "  - Inbound Agent ID: " . ($phoneConfig['inbound_agent_id'] ?? 'NONE') . "\n";
    
    $activeAgentId = $phoneConfig['inbound_agent_id'] ?? null;
    
    if (!$activeAgentId) {
        echo "❌ No agent assigned to this phone number!\n";
        return;
    }
    
    // 2. Get the active agent details
    echo "\n2. CHECKING ACTIVE AGENT:\n";
    $activeAgent = $retellService->getAgent($activeAgentId);
    
    if (!$activeAgent) {
        echo "❌ Could not fetch agent details for ID: $activeAgentId\n";
        return;
    }
    
    echo "✓ Active Agent Details:\n";
    echo "  - Agent ID: $activeAgentId\n";
    echo "  - Agent Name: " . ($activeAgent['agent_name'] ?? 'N/A') . "\n";
    echo "  - Response Engine Type: " . ($activeAgent['response_engine']['type'] ?? 'N/A') . "\n";
    
    // Check if this is the V33 agent
    $isV33 = stripos($activeAgent['agent_name'], 'V33') !== false;
    echo "  - Is this V33? " . ($isV33 ? "YES ✓" : "NO ❌") . "\n";
    
    if ($activeAgent['response_engine']['type'] === 'retell-llm') {
        $activeLlmId = $activeAgent['response_engine']['llm_id'] ?? null;
        echo "  - LLM ID: $activeLlmId\n";
        
        // 3. Check the LLM configuration
        echo "\n3. CHECKING LLM CONFIGURATION:\n";
        $llmConfig = $retellService->getRetellLLM($activeLlmId);
        
        if ($llmConfig) {
            // Check for custom functions
            $hasCollectFunction = false;
            $collectFunctionConfig = null;
            
            foreach ($llmConfig['general_tools'] ?? [] as $tool) {
                if ($tool['name'] === 'collect_appointment_data') {
                    $hasCollectFunction = true;
                    $collectFunctionConfig = $tool;
                    break;
                }
            }
            
            echo "  - Has collect_appointment_data? " . ($hasCollectFunction ? "YES ✓" : "NO ❌") . "\n";
            
            if ($hasCollectFunction) {
                echo "\n  FUNCTION CONFIGURATION:\n";
                echo "    - Type: " . ($collectFunctionConfig['type'] ?? 'N/A') . "\n";
                echo "    - URL: " . ($collectFunctionConfig['url'] ?? 'N/A') . "\n";
                echo "    - Method: " . ($collectFunctionConfig['method'] ?? 'N/A') . "\n";
                
                // Check required parameters
                $requiredParams = $collectFunctionConfig['parameters']['required'] ?? [];
                echo "    - Required fields: " . implode(', ', $requiredParams) . "\n";
                
                if (in_array('telefonnummer', $requiredParams)) {
                    echo "    - ✓ telefonnummer is required\n";
                } else {
                    echo "    - ❌ telefonnummer is NOT required!\n";
                }
            }
            
            // Check prompt for telefonnummer instructions
            echo "\n  PROMPT ANALYSIS:\n";
            $prompt = $llmConfig['general_prompt'] ?? '';
            
            // Check for the corrected section
            if (strpos($prompt, 'WICHTIG: Das Feld "telefonnummer" ist IMMER erforderlich!') !== false) {
                echo "    - ✓ Prompt has CORRECTED telefonnummer instructions\n";
            } else {
                echo "    - ❌ Prompt still has OLD telefonnummer instructions\n";
            }
            
            // Check when prompt was last modified
            if (strpos($prompt, 'telefonnummer ist IMMER erforderlich') !== false) {
                echo "    - ✓ Fix has been applied to this LLM\n";
            }
        }
    }
    
    // 4. Compare with the agent we modified
    echo "\n4. COMPARING WITH MODIFIED AGENT:\n";
    $modifiedAgentId = 'agent_9a8202a740cd3120d96fcfda1e';
    echo "  - Agent ID we modified: $modifiedAgentId\n";
    echo "  - Active agent ID: $activeAgentId\n";
    echo "  - Same agent? " . ($modifiedAgentId === $activeAgentId ? "YES ✓" : "NO ❌") . "\n";
    
    if ($modifiedAgentId !== $activeAgentId) {
        echo "\n⚠️  WARNING: We modified a different agent!\n";
        echo "   The phone number is using a different agent version.\n";
        
        // Check the modified agent too
        echo "\n5. CHECKING THE AGENT WE MODIFIED:\n";
        $modifiedAgent = $retellService->getAgent($modifiedAgentId);
        if ($modifiedAgent) {
            echo "  - Name: " . ($modifiedAgent['agent_name'] ?? 'N/A') . "\n";
            echo "  - This agent is NOT active on the test phone number!\n";
        }
    }
    
    // Summary
    echo "\n=== SUMMARY ===\n";
    if ($modifiedAgentId === $activeAgentId && $hasCollectFunction ?? false) {
        echo "✅ Configuration is CORRECT!\n";
        echo "   - Phone $testPhoneNumber is using the right agent\n";
        echo "   - Agent has collect_appointment_data function\n";
        echo "   - Prompt has been updated\n";
        echo "\n   Ready for testing!\n";
    } else {
        echo "❌ Configuration MISMATCH!\n";
        if ($modifiedAgentId !== $activeAgentId) {
            echo "   - We modified agent: $modifiedAgentId\n";
            echo "   - But phone uses: $activeAgentId\n";
            echo "\n   SOLUTION: Either:\n";
            echo "   1. Update the phone number to use agent $modifiedAgentId\n";
            echo "   2. Apply the same fixes to agent $activeAgentId\n";
        }
    }
    
} catch (\Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
}