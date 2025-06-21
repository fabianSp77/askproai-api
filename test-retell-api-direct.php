<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request = Illuminate\Http\Request::capture());

use App\Models\Company;
use App\Services\RetellV2Service;
use Illuminate\Support\Facades\Http;

echo "TESTING RETELL API DIRECT CONNECTION\n";
echo str_repeat('=', 50) . "\n\n";

// Get company
$company = Company::find(1);
$apiKey = $company->retell_api_key ? decrypt($company->retell_api_key) : config('services.retell.api_key');

if (!$apiKey) {
    echo "❌ No API key found\n";
    exit(1);
}

echo "✅ API Key found\n";
echo "Base URL: " . config('services.retell.base_url') . "\n\n";

// Test direct HTTP call
echo "1. Testing direct HTTP call to list agents...\n";
try {
    $url = 'https://api.retellai.com/v2/list-agents';
    echo "   URL: $url\n";
    
    $response = Http::withToken($apiKey)
        ->post($url, []);
    
    echo "   Status: " . $response->status() . "\n";
    
    if ($response->successful()) {
        $data = $response->json();
        $agentCount = count($data['agents'] ?? []);
        echo "   ✅ Success! Found $agentCount agents\n";
        
        if ($agentCount > 0) {
            echo "   First agent: " . $data['agents'][0]['agent_name'] . "\n";
        }
    } else {
        echo "   ❌ Failed: " . $response->body() . "\n";
    }
} catch (\Exception $e) {
    echo "   ❌ Exception: " . $e->getMessage() . "\n";
}

// Test with RetellV2Service
echo "\n2. Testing with RetellV2Service...\n";
try {
    $service = new RetellV2Service($apiKey);
    echo "   Service initialized\n";
    
    $result = $service->listAgents();
    
    if (isset($result['agents'])) {
        $agentCount = count($result['agents']);
        echo "   ✅ Success! Found $agentCount agents\n";
    } else {
        echo "   ❌ Unexpected result: " . json_encode($result) . "\n";
    }
} catch (\Exception $e) {
    echo "   ❌ Exception: " . $e->getMessage() . "\n";
    echo "   Stack trace:\n";
    echo $e->getTraceAsString() . "\n";
}

// Test phone numbers
echo "\n3. Testing phone numbers endpoint...\n";
try {
    $url = 'https://api.retellai.com/v2/list-phone-numbers';
    echo "   URL: $url\n";
    
    $response = Http::withToken($apiKey)
        ->post($url, []);
    
    echo "   Status: " . $response->status() . "\n";
    
    if ($response->successful()) {
        $data = $response->json();
        $phoneCount = count($data['phone_numbers'] ?? []);
        echo "   ✅ Success! Found $phoneCount phone numbers\n";
    } else {
        echo "   ❌ Failed: " . $response->body() . "\n";
    }
} catch (\Exception $e) {
    echo "   ❌ Exception: " . $e->getMessage() . "\n";
}

echo "\nDone.\n";