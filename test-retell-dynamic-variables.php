<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\Log;
use App\Services\RetellV2Service;
use App\Services\Webhooks\RetellWebhookHandler;

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "\n=== Testing Retell Dynamic Variables ===\n\n";

// Test 1: Check webhook handler dynamic variables
echo "1. Testing Webhook Handler Dynamic Variables:\n";
echo str_repeat('-', 50) . "\n";

try {
    // Simulate inbound call data
    $callData = [
        'from_number' => '+49 176 12345678',
        'to_number' => '+49 30 837 93 369',
        'direction' => 'inbound',
        'call_id' => 'test_call_' . time()
    ];
    
    // Get company
    $company = \App\Models\Company::withoutGlobalScope(\App\Scopes\TenantScope::class)->first();
    
    if (!$company) {
        throw new Exception("No company found in database");
    }
    
    // Build response with dynamic variables (simulating what webhook handler does)
    $response = [
        'agent_id' => $company->retell_agent_id ?? config('services.retell.default_agent_id'),
        'dynamic_variables' => [
            'company_name' => $company->name ?? 'AskProAI',
            'caller_number' => $callData['from_number'] ?? '',
            'caller_phone_number' => $callData['from_number'] ?? '',
            'current_time_berlin' => now()->setTimezone('Europe/Berlin')->format('Y-m-d H:i:s'),
            'current_date' => now()->setTimezone('Europe/Berlin')->format('Y-m-d'),
            'current_time' => now()->setTimezone('Europe/Berlin')->format('H:i'),
            'weekday' => now()->setTimezone('Europe/Berlin')->locale('de')->dayName,
            'correlation_id' => 'test_correlation_' . time()
        ]
    ];
    
    echo "✅ Dynamic Variables Generated:\n";
    foreach ($response['dynamic_variables'] as $key => $value) {
        echo "   - $key: $value\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 2: Test collect_appointment_data with dynamic variables
echo "2. Testing collect_appointment_data Function:\n";
echo str_repeat('-', 50) . "\n";

try {
    // Simulate request data that would come from Retell
    $requestData = [
        'args' => [
            'datum' => 'morgen',
            'uhrzeit' => '15:00',
            'name' => 'Test Kunde',
            'telefonnummer' => 'caller_phone_number', // Using variable placeholder
            'dienstleistung' => 'Beratung',
            'email' => 'test@example.com'
        ],
        'call' => [
            'from_number' => '+49 176 98765432',
            'retell_llm_dynamic_variables' => [
                'caller_phone_number' => '+49 176 98765432',
                'current_time_berlin' => now()->setTimezone('Europe/Berlin')->format('Y-m-d H:i:s')
            ]
        ]
    ];
    
    echo "📞 Test Request Data:\n";
    echo "   - Telefonnummer in args: " . $requestData['args']['telefonnummer'] . "\n";
    echo "   - From number in call: " . $requestData['call']['from_number'] . "\n";
    echo "   - Dynamic variable caller_phone_number: " . $requestData['call']['retell_llm_dynamic_variables']['caller_phone_number'] . "\n";
    
    // Test the controller logic
    $controller = new \App\Http\Controllers\Api\RetellAppointmentCollectorController();
    
    // Create a mock request
    $request = new \Illuminate\Http\Request();
    $request->merge($requestData);
    
    // Call the collect method
    $response = $controller->collect($request);
    $responseData = json_decode($response->getContent(), true);
    
    if ($responseData['success']) {
        echo "\n✅ Success! Appointment data would be collected\n";
        echo "   - Reference ID: " . ($responseData['reference_id'] ?? 'N/A') . "\n";
        echo "   - Next Steps: " . ($responseData['next_steps'] ?? 'N/A') . "\n";
    } else {
        echo "\n❌ Failed: " . $responseData['message'] . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n";

// Test 3: Check current agent configuration
echo "3. Checking Current Agent Configuration:\n";
echo str_repeat('-', 50) . "\n";

try {
    $retellService = new RetellV2Service();
    
    // Get first active agent
    $agent = \App\Models\RetellAgent::where('is_active', true)->first();
    
    if (!$agent) {
        echo "⚠️  No active agent found in database\n";
    } else {
        echo "📋 Agent: " . $agent->name . " (ID: " . $agent->agent_id . ")\n";
        
        // Get agent details from Retell API
        $agentDetails = $retellService->getAgent($agent->agent_id);
        
        if ($agentDetails && isset($agentDetails['response_engine']['llm_id'])) {
            $llmId = $agentDetails['response_engine']['llm_id'];
            echo "   - LLM ID: $llmId\n";
            
            // Get LLM configuration
            $llmConfig = $retellService->getRetellLLM($llmId);
            
            if ($llmConfig) {
                echo "   - Model: " . ($llmConfig['model'] ?? 'N/A') . "\n";
                echo "   - Functions: " . count($llmConfig['general_tools'] ?? []) . "\n";
                
                // Check if collect_appointment_data exists
                $hasCollectFunction = false;
                foreach (($llmConfig['general_tools'] ?? []) as $tool) {
                    if ($tool['name'] === 'collect_appointment_data') {
                        $hasCollectFunction = true;
                        break;
                    }
                }
                
                echo "   - Has collect_appointment_data: " . ($hasCollectFunction ? '✅ Yes' : '❌ No') . "\n";
                
                // Check prompt for variable usage
                $prompt = $llmConfig['general_prompt'] ?? '';
                $usesCallerPhone = strpos($prompt, '{{caller_phone_number}}') !== false || 
                                   strpos($prompt, '{{caller_number}}') !== false;
                $usesCurrentTime = strpos($prompt, '{{current_time_berlin}}') !== false ||
                                   strpos($prompt, '{{current_date}}') !== false;
                
                echo "   - Prompt uses caller_phone_number: " . ($usesCallerPhone ? '✅ Yes' : '❌ No') . "\n";
                echo "   - Prompt uses current_time_berlin: " . ($usesCurrentTime ? '✅ Yes' : '❌ No') . "\n";
            }
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error checking agent: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 4: Recommendations
echo "4. Recommendations:\n";
echo str_repeat('-', 50) . "\n";

echo "📝 To fix the phone number issue:\n";
echo "   1. Update agent prompt to use {{caller_phone_number}} variable\n";
echo "   2. Modify collect_appointment_data parameters to accept 'caller_phone_number'\n";
echo "   3. Test with actual phone call to verify variables are passed\n\n";

echo "📅 To fix the date issue:\n";
echo "   1. Update agent prompt to include {{current_time_berlin}} variable\n";
echo "   2. Use this for relative date calculations (e.g., 'morgen' = tomorrow)\n";
echo "   3. Include date parsing logic in the prompt\n\n";

echo "Done!\n\n";