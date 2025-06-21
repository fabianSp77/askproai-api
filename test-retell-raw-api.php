<?php
require __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\Http;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🔍 RETELL RAW API TEST\n";
echo "======================\n\n";

$apiKey = env('RETELL_TOKEN') ?? env('DEFAULT_RETELL_API_KEY');
$baseUrl = 'https://api.retellai.com';

if (!$apiKey) {
    echo "❌ No API key found!\n";
    exit(1);
}

echo "API Key: " . substr($apiKey, 0, 10) . "...\n";
echo "Base URL: $baseUrl\n\n";

// Test with curl command to verify
echo "1. Testing with raw curl command\n";
echo "---------------------------------\n";

$curlCommand = sprintf(
    'curl -X GET "%s/list-agents" -H "Authorization: Bearer %s" -H "Content-Type: application/json" -v',
    $baseUrl,
    $apiKey
);

echo "Command: curl -X GET \"$baseUrl/list-agents\" -H \"Authorization: Bearer [API_KEY]\" -v\n\n";

$output = shell_exec($curlCommand . ' 2>&1');
echo "Response:\n";
echo substr($output, 0, 1000) . "\n";

// Test different variations
echo "\n\n2. Testing different API variations\n";
echo "------------------------------------\n";

$endpoints = [
    ['method' => 'GET', 'path' => '/list-agents'],
    ['method' => 'POST', 'path' => '/list-agents'],
    ['method' => 'GET', 'path' => '/v1/list-agents'],
    ['method' => 'POST', 'path' => '/v1/list-agents'],
    ['method' => 'GET', 'path' => '/agents'],
    ['method' => 'GET', 'path' => '/v1/agents'],
];

foreach ($endpoints as $endpoint) {
    echo "\nTrying {$endpoint['method']} {$endpoint['path']}:\n";
    
    try {
        $request = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ]);
        
        if ($endpoint['method'] === 'GET') {
            $response = $request->get($baseUrl . $endpoint['path']);
        } else {
            $response = $request->post($baseUrl . $endpoint['path'], []);
        }
        
        echo "  Status: " . $response->status() . "\n";
        
        if ($response->successful()) {
            echo "  ✅ Success!\n";
            $body = $response->json();
            if (is_array($body)) {
                echo "  Response keys: " . implode(', ', array_keys($body)) . "\n";
            }
            break;
        } else {
            echo "  ❌ Failed\n";
            if ($response->status() !== 404) {
                echo "  Body: " . substr($response->body(), 0, 200) . "\n";
            }
        }
    } catch (\Exception $e) {
        echo "  Exception: " . $e->getMessage() . "\n";
    }
}

// Test with different authorization formats
echo "\n\n3. Testing authorization formats\n";
echo "---------------------------------\n";

$authFormats = [
    ['header' => 'Authorization', 'value' => 'Bearer ' . $apiKey],
    ['header' => 'Authorization', 'value' => $apiKey],
    ['header' => 'X-API-Key', 'value' => $apiKey],
    ['header' => 'api-key', 'value' => $apiKey],
];

foreach ($authFormats as $auth) {
    echo "\nTrying {$auth['header']}: " . substr($auth['value'], 0, 20) . "...\n";
    
    try {
        $response = Http::withHeaders([
            $auth['header'] => $auth['value'],
            'Content-Type' => 'application/json'
        ])->get($baseUrl . '/list-agents');
        
        echo "  Status: " . $response->status() . "\n";
        
        if ($response->successful()) {
            echo "  ✅ Success with this format!\n";
            break;
        }
    } catch (\Exception $e) {
        echo "  Exception: " . $e->getMessage() . "\n";
    }
}

echo "\n✅ Test completed\n";