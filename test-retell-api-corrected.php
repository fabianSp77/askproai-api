<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request = Illuminate\Http\Request::capture());

use App\Models\Company;
use App\Services\RetellV2Service;
use Illuminate\Support\Facades\Http;

echo "TESTING CORRECTED RETELL API ENDPOINTS\n";
echo str_repeat('=', 50) . "\n\n";

// Get company
$company = Company::find(1);
$apiKey = $company->retell_api_key ? decrypt($company->retell_api_key) : config('services.retell.api_key');

if (!$apiKey) {
    echo "❌ No API key found\n";
    exit(1);
}

echo "✅ API Key found: " . substr($apiKey, 0, 10) . "...\n";
echo "Base URL: " . config('services.retell.base_url') . "\n\n";

// Test 1: List agents with GET
echo "1. Testing GET /list-agents...\n";
try {
    $url = 'https://api.retellai.com/list-agents';
    echo "   URL: $url\n";
    
    $response = Http::withToken($apiKey)
        ->get($url);
    
    echo "   Status: " . $response->status() . "\n";
    
    if ($response->successful()) {
        $data = $response->json();
        
        if (is_array($data)) {
            echo "   ✅ Success! Found " . count($data) . " agents\n";
            
            if (!empty($data)) {
                $firstAgent = $data[0];
                echo "   First agent:\n";
                echo "     - Name: " . ($firstAgent['agent_name'] ?? 'N/A') . "\n";
                echo "     - ID: " . ($firstAgent['agent_id'] ?? 'N/A') . "\n";
                echo "     - Voice: " . ($firstAgent['voice_id'] ?? 'N/A') . "\n";
            }
        }
    } else {
        echo "   ❌ Failed: " . $response->body() . "\n";
    }
} catch (\Exception $e) {
    echo "   ❌ Exception: " . $e->getMessage() . "\n";
}

// Test 2: Test with RetellV2Service
echo "\n2. Testing with updated RetellV2Service...\n";
try {
    $service = new RetellV2Service($apiKey);
    echo "   Service initialized\n";
    
    $result = $service->listAgents();
    
    if (isset($result['agents'])) {
        $agentCount = count($result['agents']);
        echo "   ✅ Success! Found $agentCount agents (wrapped in 'agents' key)\n";
        
        if ($agentCount > 0) {
            $firstAgent = $result['agents'][0];
            echo "   First agent: " . $firstAgent['agent_name'] . " (ID: " . $firstAgent['agent_id'] . ")\n";
        }
    } else {
        echo "   ❌ Unexpected result structure: " . json_encode(array_keys($result)) . "\n";
    }
} catch (\Exception $e) {
    echo "   ❌ Exception: " . $e->getMessage() . "\n";
}

// Test 3: List phone numbers
echo "\n3. Testing GET /list-phone-numbers...\n";
try {
    $url = 'https://api.retellai.com/list-phone-numbers';
    echo "   URL: $url\n";
    
    $response = Http::withToken($apiKey)
        ->get($url);
    
    echo "   Status: " . $response->status() . "\n";
    
    if ($response->successful()) {
        $data = $response->json();
        
        if (is_array($data)) {
            echo "   ✅ Success! Response received\n";
            
            // Check if it's wrapped or direct array
            $phoneNumbers = isset($data['phone_numbers']) ? $data['phone_numbers'] : $data;
            echo "   Found " . count($phoneNumbers) . " phone numbers\n";
            
            if (!empty($phoneNumbers)) {
                $firstPhone = $phoneNumbers[0];
                if (is_array($firstPhone)) {
                    echo "   First phone: " . ($firstPhone['phone_number'] ?? 'N/A') . "\n";
                    echo "   Type: " . ($firstPhone['phone_number_type'] ?? 'N/A') . "\n";
                }
            }
        }
    } else {
        echo "   ❌ Failed: " . $response->body() . "\n";
    }
} catch (\Exception $e) {
    echo "   ❌ Exception: " . $e->getMessage() . "\n";
}

// Test 4: Test phone numbers with service
echo "\n4. Testing phone numbers with RetellV2Service...\n";
try {
    $service = new RetellV2Service($apiKey);
    $result = $service->listPhoneNumbers();
    
    if (isset($result['phone_numbers'])) {
        $phoneCount = count($result['phone_numbers']);
        echo "   ✅ Success! Found $phoneCount phone numbers\n";
    } else {
        echo "   ❌ Unexpected result: " . json_encode($result) . "\n";
    }
} catch (\Exception $e) {
    echo "   ❌ Exception: " . $e->getMessage() . "\n";
}

echo "\n✅ API ENDPOINT TEST COMPLETE\n";