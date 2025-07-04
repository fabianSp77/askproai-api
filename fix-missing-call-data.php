<?php

/**
 * Fix Missing Call Data - Enhanced Webhook Processing
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Call;
use App\Services\RetellV2Service;
use App\Models\Company;

echo "\n=== Fix Missing Call Data ===\n\n";

// Get company and Retell service
$company = Company::first();
if (!$company || !$company->retell_api_key) {
    echo "❌ No company with Retell API key found\n";
    exit(1);
}

try {
    $apiKey = is_string($company->retell_api_key) && strpos($company->retell_api_key, 'key_') === 0 
        ? $company->retell_api_key 
        : decrypt($company->retell_api_key);
        
    $retellService = new RetellV2Service($apiKey);
} catch (\Exception $e) {
    echo "❌ Could not decrypt API key: " . $e->getMessage() . "\n";
    exit(1);
}

echo "1. Fetching recent calls from Retell API...\n";
$response = $retellService->listCalls(50);

if (!isset($response['calls'])) {
    echo "❌ No calls returned from API\n";
    exit(1);
}

$apiCalls = $response['calls'];
echo "Found " . count($apiCalls) . " calls from API\n\n";

$updated = 0;
$analyzed = 0;

foreach ($apiCalls as $apiCall) {
    $callId = $apiCall['call_id'];
    
    // Find local call
    $localCall = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
        ->where(function($q) use ($callId) {
            $q->where('call_id', $callId)
              ->orWhere('retell_call_id', $callId);
        })
        ->first();
        
    if (!$localCall) {
        echo "⚠️  Call $callId not found locally\n";
        continue;
    }
    
    $analyzed++;
    $updates = [];
    
    // Check and update missing fields
    
    // 1. Duration
    if (empty($localCall->duration_sec) && isset($apiCall['call_analysis']['call_length'])) {
        $localCall->duration_sec = (int)$apiCall['call_analysis']['call_length'];
        $updates[] = 'duration_sec';
    }
    
    // 2. Summary
    if (empty($localCall->summary) && isset($apiCall['call_analysis']['call_summary'])) {
        $localCall->summary = $apiCall['call_analysis']['call_summary'];
        $updates[] = 'summary';
    }
    
    // 3. Public Log URL
    if (empty($localCall->public_log_url) && isset($apiCall['public_log_url'])) {
        $localCall->public_log_url = $apiCall['public_log_url'];
        $updates[] = 'public_log_url';
    }
    
    // 4. Transcript Object
    if (empty($localCall->transcript_object) && isset($apiCall['transcript_object'])) {
        $localCall->transcript_object = $apiCall['transcript_object'];
        $updates[] = 'transcript_object';
    }
    
    // 5. Transcript with Tools
    if (empty($localCall->transcript_with_tools) && isset($apiCall['transcript_with_tool_calls'])) {
        $localCall->transcript_with_tools = $apiCall['transcript_with_tool_calls'];
        $updates[] = 'transcript_with_tools';
    }
    
    // 6. Cost
    if (empty($localCall->cost) && isset($apiCall['cost'])) {
        $localCall->cost = $apiCall['cost'];
        $updates[] = 'cost';
    }
    
    // 7. Latency Metrics
    if (empty($localCall->latency_metrics) && isset($apiCall['latency'])) {
        $localCall->latency_metrics = $apiCall['latency'];
        $updates[] = 'latency_metrics';
    }
    
    // 8. Dynamic Variables
    if (empty($localCall->retell_dynamic_variables) && isset($apiCall['retell_llm_dynamic_variables'])) {
        $localCall->retell_dynamic_variables = $apiCall['retell_llm_dynamic_variables'];
        $updates[] = 'retell_dynamic_variables';
        
        // Extract customer data from dynamic variables
        $dynamicVars = $apiCall['retell_llm_dynamic_variables'];
        
        if (isset($dynamicVars['customer_name']) && empty($localCall->name)) {
            $localCall->name = $dynamicVars['customer_name'];
            $updates[] = 'name';
        }
        
        if (isset($dynamicVars['customer_email']) && empty($localCall->email)) {
            $localCall->email = $dynamicVars['customer_email'];
            $updates[] = 'email';
        }
        
        if (isset($dynamicVars['customer_phone']) && empty($localCall->phone_number)) {
            $localCall->phone_number = $dynamicVars['customer_phone'] ?? $apiCall['from_number'];
            $updates[] = 'phone_number';
        }
        
        // Extract appointment data
        if (isset($dynamicVars['appointment_date']) && empty($localCall->datum_termin)) {
            $localCall->datum_termin = $dynamicVars['appointment_date'];
            $updates[] = 'datum_termin';
        }
        
        if (isset($dynamicVars['appointment_time']) && empty($localCall->uhrzeit_termin)) {
            $localCall->uhrzeit_termin = $dynamicVars['appointment_time'];
            $updates[] = 'uhrzeit_termin';
        }
        
        if (isset($dynamicVars['service_type']) && empty($localCall->dienstleistung)) {
            $localCall->dienstleistung = $dynamicVars['service_type'];
            $updates[] = 'dienstleistung';
        }
    }
    
    // 9. Metadata
    if (empty($localCall->metadata) && isset($apiCall['metadata'])) {
        $localCall->metadata = $apiCall['metadata'];
        $updates[] = 'metadata';
    }
    
    // 10. Webhook Data (store complete API response)
    if (empty($localCall->webhook_data)) {
        $localCall->webhook_data = ['event' => 'call_ended', 'call' => $apiCall];
        $updates[] = 'webhook_data';
    }
    
    // 11. Disconnection Reason (ensure it's set)
    if (empty($localCall->disconnection_reason) && isset($apiCall['disconnection_reason'])) {
        $localCall->disconnection_reason = $apiCall['disconnection_reason'];
        $updates[] = 'disconnection_reason';
    }
    
    // 12. Sentiment (from analysis)
    if (empty($localCall->sentiment) && isset($apiCall['call_analysis']['user_sentiment'])) {
        $localCall->sentiment = $apiCall['call_analysis']['user_sentiment'];
        $updates[] = 'sentiment';
    }
    
    // 13. Call ID (ensure both fields are set)
    if (empty($localCall->call_id)) {
        $localCall->call_id = $callId;
        $updates[] = 'call_id';
    }
    
    // 14. Agent ID fields (ensure both are set)
    if (!empty($apiCall['agent_id'])) {
        if (empty($localCall->agent_id)) {
            $localCall->agent_id = $apiCall['agent_id'];
            $updates[] = 'agent_id';
        }
        if (empty($localCall->retell_agent_id)) {
            $localCall->retell_agent_id = $apiCall['agent_id'];
            $updates[] = 'retell_agent_id';
        }
    }
    
    if (!empty($updates)) {
        $localCall->save();
        $updated++;
        echo "✅ Updated call $callId with: " . implode(', ', $updates) . "\n";
    } else {
        echo "   Call $callId already complete\n";
    }
}

echo "\n=== Summary ===\n";
echo "Analyzed: $analyzed calls\n";
echo "Updated: $updated calls\n";

// Re-run completeness check
echo "\n2. Re-checking data completeness...\n";
$recentCalls = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->orderBy('created_at', 'desc')
    ->take(5)
    ->get();

$criticalFields = [
    'duration_sec' => 'Duration',
    'summary' => 'Summary',
    'public_log_url' => 'Public URL',
    'transcript_object' => 'Transcript Object',
    'cost' => 'Cost',
    'retell_dynamic_variables' => 'Dynamic Variables',
    'webhook_data' => 'Webhook Data'
];

foreach ($recentCalls as $call) {
    $missing = [];
    foreach ($criticalFields as $field => $name) {
        if (empty($call->$field)) {
            $missing[] = $name;
        }
    }
    
    if (empty($missing)) {
        echo "✅ Call {$call->call_id}: Complete\n";
    } else {
        echo "⚠️  Call {$call->call_id}: Missing " . implode(', ', $missing) . "\n";
    }
}

echo "\n✅ Fix Complete\n";