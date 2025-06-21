<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request = Illuminate\Http\Request::capture());

use App\Models\Company;
use Illuminate\Support\Facades\Http;

echo "TESTING RETELL API ENDPOINTS AND METHODS\n";
echo str_repeat('=', 50) . "\n\n";

// Get API key
$company = Company::find(1);
$apiKey = $company->retell_api_key ? decrypt($company->retell_api_key) : config('services.retell.api_key');

if (!$apiKey) {
    echo "❌ No API key found\n";
    exit(1);
}

echo "Testing different API versions and methods...\n\n";

// Test endpoints
$endpoints = [
    // V2 endpoints
    ['method' => 'POST', 'url' => 'https://api.retellai.com/v2/list-agents'],
    ['method' => 'GET', 'url' => 'https://api.retellai.com/v2/list-agents'],
    ['method' => 'GET', 'url' => 'https://api.retellai.com/v2/agents'],
    
    // V1 endpoints
    ['method' => 'POST', 'url' => 'https://api.retellai.com/list-agents'],
    ['method' => 'GET', 'url' => 'https://api.retellai.com/list-agents'],
    ['method' => 'GET', 'url' => 'https://api.retellai.com/agents'],
    
    // Check API docs endpoint
    ['method' => 'GET', 'url' => 'https://api.retellai.com/'],
    ['method' => 'GET', 'url' => 'https://api.retellai.com/v2'],
];

foreach ($endpoints as $endpoint) {
    echo "Testing {$endpoint['method']} {$endpoint['url']}...\n";
    
    try {
        $request = Http::withToken($apiKey)
            ->withHeaders(['Accept' => 'application/json']);
            
        if ($endpoint['method'] === 'GET') {
            $response = $request->get($endpoint['url']);
        } else {
            $response = $request->post($endpoint['url'], []);
        }
        
        echo "   Status: " . $response->status() . "\n";
        
        if ($response->successful()) {
            $body = $response->body();
            $json = $response->json();
            
            if (is_array($json)) {
                echo "   ✅ Success! Response type: " . gettype($json) . "\n";
                
                // Check for agents array
                if (isset($json['agents'])) {
                    echo "   Found 'agents' key with " . count($json['agents']) . " items\n";
                }
                
                // Show keys
                $keys = array_keys($json);
                echo "   Response keys: " . implode(', ', array_slice($keys, 0, 5)) . "\n";
            } else {
                echo "   Response (first 200 chars): " . substr($body, 0, 200) . "\n";
            }
        } else {
            $body = $response->body();
            if (strlen($body) < 300) {
                echo "   ❌ Failed: $body\n";
            } else {
                echo "   ❌ Failed with status " . $response->status() . "\n";
            }
        }
    } catch (\Exception $e) {
        echo "   ❌ Exception: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
}

echo "Done.\n";