<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Services\RetellV2Service;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== CHECKING RETELL CUSTOM FUNCTION CONFIGURATION ===\n\n";

try {
    // Get API key
    $apiKey = env('DEFAULT_RETELL_API_KEY') ?? env('RETELL_TOKEN');
    $retellService = new RetellV2Service($apiKey);
    
    // Agent ID for "Assistent für Fabian Spitzer Rechtliches V33"
    $agentId = 'agent_9a8202a740cd3120d96fcfda1e';
    
    echo "Checking agent: $agentId\n\n";
    
    // Get agent details
    $agent = $retellService->getAgent($agentId);
    
    if ($agent) {
        echo "Agent Name: " . ($agent['agent_name'] ?? 'N/A') . "\n";
        echo "Language: " . ($agent['language'] ?? 'N/A') . "\n\n";
        
        // Check general_tools (custom functions)
        if (isset($agent['general_tools']) && is_array($agent['general_tools'])) {
            echo "Custom Functions Configured: " . count($agent['general_tools']) . "\n\n";
            
            foreach ($agent['general_tools'] as $index => $tool) {
                echo "Function " . ($index + 1) . ":\n";
                echo "  Type: " . ($tool['type'] ?? 'N/A') . "\n";
                
                if (isset($tool['function'])) {
                    $func = $tool['function'];
                    echo "  Name: " . ($func['name'] ?? 'N/A') . "\n";
                    echo "  Description: " . ($func['description'] ?? 'N/A') . "\n";
                    
                    if (isset($func['url'])) {
                        echo "  URL: " . $func['url'] . "\n";
                    }
                    
                    if (isset($func['parameters']) && is_array($func['parameters'])) {
                        echo "  Parameters:\n";
                        foreach ($func['parameters'] as $param => $config) {
                            echo "    - $param: " . ($config['type'] ?? 'unknown') . " " . 
                                 (isset($config['required']) && $config['required'] ? '(required)' : '(optional)') . "\n";
                            if (isset($config['description'])) {
                                echo "      Description: " . $config['description'] . "\n";
                            }
                        }
                    }
                }
                echo "\n";
            }
            
            // Check if collect_appointment_data is configured
            $hasCollectAppointment = false;
            foreach ($agent['general_tools'] as $tool) {
                if (isset($tool['function']['name']) && 
                    stripos($tool['function']['name'], 'collect') !== false &&
                    stripos($tool['function']['name'], 'appointment') !== false) {
                    $hasCollectAppointment = true;
                    break;
                }
            }
            
            if (!$hasCollectAppointment) {
                echo "⚠️  WARNING: No 'collect_appointment_data' function found!\n";
                echo "   This is why appointments aren't being booked.\n\n";
                
                echo "SOLUTION: Configure the agent with this custom function:\n";
                echo "   Name: collect_appointment_data\n";
                echo "   URL: https://api.askproai.de/api/retell/collect-appointment\n";
                echo "   Method: POST\n";
                echo "   Parameters:\n";
                echo "     - datum (required, string): Das Datum des Termins\n";
                echo "     - uhrzeit (required, string): Die Uhrzeit des Termins\n";
                echo "     - name (required, string): Name des Kunden\n";
                echo "     - telefonnummer (required, string): Telefonnummer des Kunden\n";
                echo "     - dienstleistung (required, string): Gewünschte Dienstleistung\n";
                echo "     - email (optional, string): E-Mail-Adresse\n";
                echo "     - mitarbeiter_wunsch (optional, string): Mitarbeiterwunsch\n";
                echo "     - kundenpraeferenzen (optional, string): Kundenpräferenzen\n";
            } else {
                echo "✓ collect_appointment_data function is configured\n";
            }
            
        } else {
            echo "⚠️  NO CUSTOM FUNCTIONS CONFIGURED!\n";
            echo "   This is why appointments aren't being booked.\n";
        }
        
        // Check prompt for instructions about using the function
        if (isset($agent['general_prompt'])) {
            echo "\n\nChecking if prompt mentions appointment collection:\n";
            $promptLower = strtolower($agent['general_prompt']);
            
            if (strpos($promptLower, 'collect_appointment') !== false) {
                echo "✓ Prompt mentions collect_appointment function\n";
            } else {
                echo "⚠️  Prompt doesn't mention collect_appointment function\n";
                echo "   The agent might not know when to use it!\n";
            }
        }
        
    } else {
        echo "Agent not found!\n";
    }
    
    // Test the endpoint
    echo "\n\n=== TESTING APPOINTMENT COLLECTION ENDPOINT ===\n";
    $testUrl = 'https://api.askproai.de/api/retell/collect-appointment/test';
    
    $ch = curl_init($testUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "Endpoint Test: $testUrl\n";
    echo "HTTP Code: $httpCode\n";
    
    if ($httpCode === 200) {
        $data = json_decode($response, true);
        echo "✓ Endpoint is working\n";
        echo "Expected fields:\n";
        foreach ($data['expected_fields'] ?? [] as $field => $rules) {
            echo "  - $field: $rules\n";
        }
    } else {
        echo "✗ Endpoint returned error code: $httpCode\n";
    }
    
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}