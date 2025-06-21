<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request = Illuminate\Http\Request::capture());

use App\Models\Company;
use Illuminate\Support\Facades\Http;

echo "TESTING RETELL API WITH DIFFERENT AUTH HEADERS\n";
echo str_repeat('=', 50) . "\n\n";

// Get API key
$company = Company::find(1);
$apiKey = $company->retell_api_key ? decrypt($company->retell_api_key) : config('services.retell.api_key');

if (!$apiKey) {
    echo "❌ No API key found\n";
    exit(1);
}

echo "✅ API Key found: " . substr($apiKey, 0, 10) . "...\n";
echo "Key format check:\n";
echo "  - Starts with 'key_': " . (str_starts_with($apiKey, 'key_') ? 'Yes' : 'No') . "\n";
echo "  - Length: " . strlen($apiKey) . " characters\n\n";

$url = 'https://api.retellai.com/list-agents';

// Test different auth header formats
$authFormats = [
    'Bearer Token' => ['Authorization' => 'Bearer ' . $apiKey],
    'API Key Header' => ['api_key' => $apiKey],
    'X-API-Key' => ['X-API-Key' => $apiKey],
    'Retell-Api-Key' => ['Retell-Api-Key' => $apiKey],
    'api-key lowercase' => ['api-key' => $apiKey],
];

foreach ($authFormats as $name => $headers) {
    echo "Testing with $name...\n";
    
    try {
        $response = Http::withHeaders($headers)
            ->timeout(10)
            ->get($url);
        
        echo "  Status: " . $response->status() . "\n";
        
        if ($response->successful()) {
            echo "  ✅ SUCCESS! This auth format works!\n";
            $data = $response->json();
            if (is_array($data)) {
                echo "  Response: " . count($data) . " agents found\n";
            }
            break;
        } else {
            $body = $response->body();
            if (strlen($body) < 200) {
                echo "  Response: $body\n";
            } else {
                echo "  Response: " . substr($body, 0, 100) . "...\n";
            }
        }
    } catch (\Exception $e) {
        echo "  ❌ Exception: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
}

// Also test with the original RetellService to see what it does
echo "Testing with RetellService (v1)...\n";
try {
    $service = new \App\Services\RetellService($apiKey);
    $agents = $service->getAgents();
    
    if (is_array($agents) && !empty($agents)) {
        echo "  ✅ RetellService works! Found " . count($agents) . " agents\n";
    } else {
        echo "  ❌ RetellService returned empty result\n";
    }
} catch (\Exception $e) {
    echo "  ❌ Exception: " . $e->getMessage() . "\n";
}

echo "\nDone.\n";