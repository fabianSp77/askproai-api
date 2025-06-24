<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Company;
use App\Services\RetellV2Service;

echo "🔍 DIAGNOSING LAST CALL ISSUE\n";
echo str_repeat('=', 50) . "\n\n";

$company = Company::find(1);
$apiKey = decrypt($company->retell_api_key);
$service = new RetellV2Service($apiKey);

// Get the most recent call
echo "1. FETCHING MOST RECENT CALL\n";
try {
    $calls = $service->listCalls(1);
    
    if (empty($calls)) {
        echo "❌ No calls found!\n";
        exit;
    }
    
    $lastCall = $calls[0];
    $callId = $lastCall['call_id'];
    
    echo "Call ID: " . $callId . "\n";
    echo "Start Time: " . date('Y-m-d H:i:s', $lastCall['start_timestamp'] / 1000) . "\n";
    echo "Duration: " . ($lastCall['end_timestamp'] - $lastCall['start_timestamp']) / 1000 . " seconds\n";
    echo "From: " . ($lastCall['from_number'] ?? 'Unknown') . "\n";
    echo "To: " . ($lastCall['to_number'] ?? 'Unknown') . "\n";
    echo "Agent ID: " . ($lastCall['agent_id'] ?? 'Unknown') . "\n";
    echo "Status: " . $lastCall['call_status'] . "\n";
    
    // Get full call details
    echo "\n2. FETCHING FULL CALL DETAILS\n";
    $fullCall = $service->getCall($callId);
    
    // Check custom function calls
    echo "\n3. ANALYZING CUSTOM FUNCTION CALLS\n";
    $customFunctionCalls = [];
    
    if (isset($fullCall['transcript_object'])) {
        foreach ($fullCall['transcript_object'] as $entry) {
            if (isset($entry['tool_calls'])) {
                foreach ($entry['tool_calls'] as $toolCall) {
                    $functionName = $toolCall['function']['name'] ?? 'unknown';
                    $customFunctionCalls[] = [
                        'name' => $functionName,
                        'arguments' => $toolCall['function']['arguments'] ?? [],
                        'result' => $toolCall['result'] ?? null
                    ];
                    
                    echo "\n📞 Function Called: " . $functionName . "\n";
                    echo "   Arguments: " . json_encode($toolCall['function']['arguments'] ?? [], JSON_PRETTY_PRINT) . "\n";
                    
                    if (isset($toolCall['result'])) {
                        echo "   Result: " . json_encode($toolCall['result'], JSON_PRETTY_PRINT) . "\n";
                    } else {
                        echo "   Result: ❌ No result returned\n";
                    }
                }
            }
        }
    }
    
    if (empty($customFunctionCalls)) {
        echo "❌ No custom function calls found!\n";
    }
    
    // Check webhook configuration of the agent
    echo "\n4. CHECKING AGENT CONFIGURATION\n";
    $agentId = $lastCall['agent_id'] ?? null;
    
    if ($agentId) {
        $agents = $service->listAgents();
        $agentFound = false;
        
        foreach ($agents['agents'] ?? [] as $agent) {
            if ($agent['agent_id'] === $agentId) {
                $agentFound = true;
                echo "\nAgent: " . $agent['agent_name'] . "\n";
                echo "Webhook URL: " . ($agent['webhook_url'] ?? 'NOT SET') . "\n";
                echo "Webhook Events: " . json_encode($agent['webhook_events'] ?? []) . "\n";
                
                if (isset($agent['custom_functions'])) {
                    echo "\nCustom Functions Configured:\n";
                    foreach ($agent['custom_functions'] as $func) {
                        echo "   - " . $func['name'] . " → " . ($func['url'] ?? 'NO URL') . "\n";
                    }
                } else {
                    echo "❌ No custom functions configured!\n";
                }
                break;
            }
        }
        
        if (!$agentFound) {
            echo "❌ Agent not found in list!\n";
        }
    }
    
    // Check if webhooks were sent
    echo "\n5. WEBHOOK STATUS\n";
    if (isset($fullCall['webhook_tools'])) {
        echo "Webhook tools: " . json_encode($fullCall['webhook_tools']) . "\n";
    } else {
        echo "No webhook information in call data\n";
    }
    
    // Check our database
    echo "\n6. CHECKING OUR DATABASE\n";
    $dbCall = \App\Models\Call::where('retell_call_id', $callId)->first();
    
    if ($dbCall) {
        echo "✅ Call found in database\n";
        echo "   Company ID: " . $dbCall->company_id . "\n";
        echo "   Created: " . $dbCall->created_at . "\n";
    } else {
        echo "❌ Call NOT found in our database!\n";
        echo "   This means webhooks are not being received\n";
    }
    
    // Summary
    echo "\n\n📋 DIAGNOSIS SUMMARY\n";
    echo str_repeat('-', 30) . "\n";
    
    $issues = [];
    
    if (empty($customFunctionCalls)) {
        $issues[] = "No custom functions were called during the conversation";
    }
    
    if (!$dbCall) {
        $issues[] = "Webhooks are not being received (call not in database)";
    }
    
    if (empty($issues)) {
        echo "✅ No obvious issues found\n";
    } else {
        echo "❌ Issues found:\n";
        foreach ($issues as $issue) {
            echo "   - " . $issue . "\n";
        }
    }
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}