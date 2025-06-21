<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request = Illuminate\Http\Request::capture());

use App\Models\Company;
use Illuminate\Support\Facades\Http;

echo "TESTING RETELL V1 API\n";
echo str_repeat('=', 50) . "\n\n";

// Get API key
$company = Company::find(1);
$apiKey = $company->retell_api_key ? decrypt($company->retell_api_key) : config('services.retell.api_key');

if (!$apiKey) {
    echo "❌ No API key found\n";
    exit(1);
}

echo "✅ API Key found: " . substr($apiKey, 0, 10) . "...\n\n";

// Test 1: List agents (v1 style)
echo "1. Testing GET /list-agents...\n";
try {
    $response = Http::withToken($apiKey)
        ->get('https://api.retellai.com/list-agents');
    
    echo "   Status: " . $response->status() . "\n";
    
    if ($response->successful()) {
        $data = $response->json();
        
        if (is_array($data)) {
            echo "   ✅ Success! Found " . count($data) . " agents\n";
            
            foreach (array_slice($data, 0, 3) as $index => $agent) {
                echo "   Agent " . ($index + 1) . ":\n";
                echo "     - Name: " . ($agent['agent_name'] ?? 'N/A') . "\n";
                echo "     - ID: " . ($agent['agent_id'] ?? 'N/A') . "\n";
            }
        } else {
            echo "   Response type: " . gettype($data) . "\n";
        }
    } else {
        echo "   ❌ Failed: " . $response->body() . "\n";
    }
} catch (\Exception $e) {
    echo "   ❌ Exception: " . $e->getMessage() . "\n";
}

// Test 2: List phone numbers (v1 style)
echo "\n2. Testing GET /list-phone-numbers...\n";
try {
    $response = Http::withToken($apiKey)
        ->get('https://api.retellai.com/list-phone-numbers');
    
    echo "   Status: " . $response->status() . "\n";
    
    if ($response->successful()) {
        $data = $response->json();
        
        if (is_array($data)) {
            echo "   ✅ Success! Response received\n";
            
            // Check if it's a direct array or has a phone_numbers key
            $phoneNumbers = isset($data['phone_numbers']) ? $data['phone_numbers'] : $data;
            echo "   Found " . count($phoneNumbers) . " phone numbers\n";
            
            foreach (array_slice($phoneNumbers, 0, 3) as $index => $phone) {
                if (is_array($phone)) {
                    echo "   Phone " . ($index + 1) . ": " . ($phone['phone_number'] ?? json_encode($phone)) . "\n";
                }
            }
        }
    } else {
        echo "   ❌ Failed: " . $response->body() . "\n";
    }
} catch (\Exception $e) {
    echo "   ❌ Exception: " . $e->getMessage() . "\n";
}

// Test 3: List recent calls
echo "\n3. Testing GET /list-calls...\n";
try {
    $response = Http::withToken($apiKey)
        ->get('https://api.retellai.com/list-calls?limit=5');
    
    echo "   Status: " . $response->status() . "\n";
    
    if ($response->successful()) {
        $data = $response->json();
        
        if (is_array($data)) {
            echo "   ✅ Success! Found " . count($data) . " calls\n";
        }
    } else {
        echo "   ❌ Failed: " . $response->body() . "\n";
    }
} catch (\Exception $e) {
    echo "   ❌ Exception: " . $e->getMessage() . "\n";
}

echo "\n✅ V1 API TEST COMPLETE\n";