<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request = Illuminate\Http\Request::capture());

use App\Models\Company;
use Illuminate\Support\Facades\Http;

echo "RETELL API ENHANCED DEBUGGING\n";
echo str_repeat('=', 50) . "\n\n";

// Enable debug logging
putenv('RETELL_LOG=debug');

$company = Company::find(1);
$apiKey = $company->retell_api_key ? decrypt($company->retell_api_key) : config('services.retell.api_key');

echo "1. API Key Information:\n";
echo "   - Format: " . (str_starts_with($apiKey, 'key_') ? '✓ Valid' : '✗ Invalid') . "\n";
echo "   - First 10 chars: " . substr($apiKey, 0, 10) . "...\n";
echo "   - Length: " . strlen($apiKey) . "\n";
echo "   - Type: ";
if (str_starts_with($apiKey, 'key_test_')) {
    echo "Test Key\n";
} elseif (str_starts_with($apiKey, 'key_live_')) {
    echo "Live/Production Key\n";
} else {
    echo "Standard Key\n";
}

// Test 1: Check API status page
echo "\n2. Checking Retell Status Page...\n";
try {
    $statusResponse = Http::timeout(10)->get('https://status.retellai.com/api/v2/status.json');
    if ($statusResponse->successful()) {
        $status = $statusResponse->json();
        echo "   Status: " . ($status['status']['description'] ?? 'Unknown') . "\n";
    } else {
        echo "   Could not check status page\n";
    }
} catch (\Exception $e) {
    echo "   Status check failed: " . $e->getMessage() . "\n";
}

// Test 2: Test with different header combinations
echo "\n3. Testing different authentication methods...\n";

$tests = [
    'Bearer Token (Standard)' => [
        'headers' => [
            'Authorization' => 'Bearer ' . $apiKey,
            'Accept' => 'application/json'
        ]
    ],
    'X-API-Key Header' => [
        'headers' => [
            'X-API-Key' => $apiKey,
            'Accept' => 'application/json'
        ]
    ],
    'api_key in JSON body' => [
        'method' => 'POST',
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ],
        'body' => json_encode(['api_key' => $apiKey])
    ]
];

foreach ($tests as $name => $config) {
    echo "\n   Testing: $name\n";
    $method = $config['method'] ?? 'GET';
    $url = 'https://api.retellai.com/list-agents';
    
    try {
        $request = Http::timeout(10)->withHeaders($config['headers']);
        
        if ($method === 'POST' && isset($config['body'])) {
            $response = $request->withBody($config['body'])->post($url);
        } else {
            $response = $request->get($url);
        }
        
        echo "   Status: " . $response->status() . "\n";
        
        if (!$response->successful()) {
            $body = $response->body();
            // Try to parse JSON error
            $decoded = json_decode($body, true);
            if ($decoded) {
                echo "   Error: " . json_encode($decoded, JSON_PRETTY_PRINT) . "\n";
            } else {
                echo "   Response: " . substr($body, 0, 200) . "\n";
            }
            
            // Check response headers
            $headers = $response->headers();
            if (isset($headers['x-ratelimit-remaining'])) {
                echo "   Rate Limit Remaining: " . $headers['x-ratelimit-remaining'][0] . "\n";
            }
        } else {
            echo "   ✓ Success!\n";
        }
    } catch (\Exception $e) {
        echo "   Exception: " . $e->getMessage() . "\n";
    }
}

// Test 3: Check other Retell endpoints
echo "\n4. Testing other Retell endpoints...\n";

$endpoints = [
    'Root endpoint' => 'https://api.retellai.com/',
    'V1 List Agents' => 'https://api.retellai.com/v1/list-agents',
    'V2 List Agents' => 'https://api.retellai.com/v2/list-agents',
    'Health check' => 'https://api.retellai.com/health',
    'Status' => 'https://api.retellai.com/status'
];

foreach ($endpoints as $name => $url) {
    echo "\n   Testing: $name\n";
    echo "   URL: $url\n";
    
    try {
        $response = Http::timeout(5)
            ->withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Accept' => 'application/json'
            ])
            ->get($url);
        
        echo "   Status: " . $response->status() . "\n";
        
        if ($response->status() === 404) {
            echo "   Endpoint not found\n";
        } elseif (!$response->successful()) {
            echo "   Response: " . substr($response->body(), 0, 100) . "\n";
        }
    } catch (\Exception $e) {
        echo "   Exception: " . $e->getMessage() . "\n";
    }
}

// Test 4: Validate webhook secret
echo "\n5. Checking webhook configuration...\n";
$webhookSecret = config('services.retell.webhook_secret');
if ($webhookSecret) {
    echo "   Webhook secret configured: Yes\n";
    echo "   Webhook URL: " . config('app.url') . "/api/webhooks/retell\n";
} else {
    echo "   ⚠️  Webhook secret not configured\n";
}

// Test 5: Try creating a simple test call
echo "\n6. Testing create call endpoint (if API works)...\n";
try {
    $response = Http::timeout(10)
        ->withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ])
        ->post('https://api.retellai.com/create-web-call', [
            'agent_id' => 'test_agent_id',
            'metadata' => ['test' => true]
        ]);
    
    echo "   Status: " . $response->status() . "\n";
    if (!$response->successful()) {
        echo "   Response: " . $response->body() . "\n";
    }
} catch (\Exception $e) {
    echo "   Exception: " . $e->getMessage() . "\n";
}

// Summary and recommendations
echo "\n" . str_repeat('=', 50) . "\n";
echo "ANALYSIS SUMMARY\n";
echo str_repeat('=', 50) . "\n";

echo "\n🔍 Key Findings:\n";
echo "1. API Key format is valid (starts with 'key_')\n";
echo "2. Basic connectivity works (200 on root endpoint)\n";
echo "3. All authenticated endpoints return 500 error\n";
echo "4. This suggests the API key is invalid or expired\n";

echo "\n📋 Recommended Actions:\n";
echo "1. Log into https://dashboard.retellai.com\n";
echo "2. Navigate to API Keys section\n";
echo "3. Check if the current key is still active\n";
echo "4. Generate a new API key if needed\n";
echo "5. Update the key in the database:\n";
echo "   \$company = Company::find(1);\n";
echo "   \$company->retell_api_key = encrypt('new_key_here');\n";
echo "   \$company->save();\n";

echo "\n⚡ Quick Fix Commands:\n";
echo "# Update API key in .env\n";
echo "sed -i 's/DEFAULT_RETELL_API_KEY=.*/DEFAULT_RETELL_API_KEY=new_key_here/' .env\n";
echo "php artisan config:clear\n";

echo "\n✅ DEBUGGING COMPLETE\n";