<?php
require __DIR__ . '/vendor/autoload.php';

use App\Models\Company;
use App\Services\RetellV2Service;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🔍 RETELL CALL RETRIEVAL TEST\n";
echo "================================\n\n";

// Get the first company
$company = Company::first();
if (!$company) {
    echo "❌ No company found in database\n";
    exit(1);
}

echo "Company: {$company->name}\n";

// Get API key
$apiKey = null;
if ($company->retell_api_key) {
    try {
        $apiKey = decrypt($company->retell_api_key);
    } catch (\Exception $e) {
        $apiKey = $company->retell_api_key;
    }
}

if (!$apiKey) {
    $apiKey = env('RETELL_TOKEN') ?? config('services.retell.api_key');
}

if (!$apiKey) {
    echo "❌ No Retell API key found!\n";
    exit(1);
}

echo "API Key: " . substr($apiKey, 0, 10) . "...\n\n";

// Initialize Retell service
$retell = new RetellV2Service($apiKey);

// Test 1: List recent calls
echo "📞 FETCHING RECENT CALLS\n";
echo "------------------------\n";

try {
    $response = $retell->listCalls(100); // Get last 100 calls
    
    if (isset($response['calls'])) {
        $calls = $response['calls'];
        echo "Found " . count($calls) . " calls\n\n";
        
        foreach ($calls as $index => $call) {
            echo "Call #" . ($index + 1) . ":\n";
            echo "  - Call ID: " . ($call['call_id'] ?? 'N/A') . "\n";
            echo "  - From: " . ($call['from_number'] ?? 'N/A') . "\n";
            echo "  - To: " . ($call['to_number'] ?? 'N/A') . "\n";
            echo "  - Status: " . ($call['call_status'] ?? 'N/A') . "\n";
            echo "  - Duration: " . ($call['duration'] ?? 0) . " seconds\n";
            echo "  - Start time: " . ($call['start_timestamp'] ?? 'N/A') . "\n";
            echo "  - End time: " . ($call['end_timestamp'] ?? 'N/A') . "\n";
            echo "  - Agent ID: " . ($call['agent_id'] ?? 'N/A') . "\n";
            
            if (isset($call['metadata'])) {
                echo "  - Metadata: " . json_encode($call['metadata']) . "\n";
            }
            
            echo "\n";
            
            if ($index >= 4) {
                echo "... (showing first 5 calls)\n\n";
                break;
            }
        }
        
        // Check database
        echo "\n🗄️  DATABASE CHECK\n";
        echo "-------------------\n";
        $dbCallCount = \App\Models\Call::count();
        echo "Calls in database: $dbCallCount\n";
        
        if ($dbCallCount == 0 && count($calls) > 0) {
            echo "⚠️  WARNING: Retell has calls but database is empty!\n";
            echo "   This suggests webhooks are not being received.\n";
        }
        
    } else {
        echo "No calls found or unexpected response structure\n";
        echo "Response: " . json_encode($response) . "\n";
    }
} catch (\Exception $e) {
    echo "❌ Error fetching calls: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

// Test 2: List agents to verify connection
echo "\n\n🤖 LISTING AGENTS\n";
echo "-----------------\n";

try {
    $agents = $retell->listAgents();
    
    if (isset($agents['agents'])) {
        echo "Found " . count($agents['agents']) . " agents\n";
        foreach ($agents['agents'] as $agent) {
            echo "  - {$agent['agent_name']} (ID: {$agent['agent_id']})\n";
        }
    }
} catch (\Exception $e) {
    echo "❌ Error listing agents: " . $e->getMessage() . "\n";
}

echo "\n✅ Test completed\n";