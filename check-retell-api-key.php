<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request = Illuminate\Http\Request::capture());

use App\Models\Company;

echo "CHECKING RETELL API KEY CONFIGURATION\n";
echo str_repeat('=', 50) . "\n\n";

// Check company
$company = Company::find(1);
echo "Company: " . $company->name . "\n";
echo "Company ID: " . $company->id . "\n";

// Check API key from company
if ($company->retell_api_key) {
    try {
        $decrypted = decrypt($company->retell_api_key);
        echo "\nCompany Retell API Key:\n";
        echo "  - Encrypted: Yes\n";
        echo "  - Decrypted starts with: " . substr($decrypted, 0, 15) . "...\n";
        echo "  - Length: " . strlen($decrypted) . " characters\n";
        echo "  - Format: " . (str_starts_with($decrypted, 'key_') ? 'Valid (key_xxx)' : 'Invalid format') . "\n";
    } catch (\Exception $e) {
        echo "\nCompany Retell API Key:\n";
        echo "  - Encrypted: No (or decryption failed)\n";
        echo "  - Raw value starts with: " . substr($company->retell_api_key, 0, 15) . "...\n";
        echo "  - Length: " . strlen($company->retell_api_key) . " characters\n";
    }
} else {
    echo "\nCompany Retell API Key: NOT SET\n";
}

// Check ENV configuration
echo "\nEnvironment Configuration:\n";

$envKeys = [
    'RETELL_TOKEN' => env('RETELL_TOKEN'),
    'RETELL_API_KEY' => env('RETELL_API_KEY'),
    'DEFAULT_RETELL_API_KEY' => env('DEFAULT_RETELL_API_KEY'),
    'RETELL_WEBHOOK_SECRET' => env('RETELL_WEBHOOK_SECRET'),
    'RETELL_BASE_URL' => env('RETELL_BASE_URL'),
    'RETELL_BASE' => env('RETELL_BASE')
];

foreach ($envKeys as $key => $value) {
    if ($value) {
        if (str_contains($key, 'SECRET') || str_contains($key, 'KEY') || str_contains($key, 'TOKEN')) {
            echo "  - $key: " . substr($value, 0, 15) . "... (length: " . strlen($value) . ")\n";
        } else {
            echo "  - $key: $value\n";
        }
    } else {
        echo "  - $key: NOT SET\n";
    }
}

// Check config values
echo "\nConfig Values:\n";
echo "  - services.retell.api_key: " . (config('services.retell.api_key') ? substr(config('services.retell.api_key'), 0, 15) . '...' : 'NOT SET') . "\n";
echo "  - services.retell.token: " . (config('services.retell.token') ? substr(config('services.retell.token'), 0, 15) . '...' : 'NOT SET') . "\n";
echo "  - services.retell.base_url: " . config('services.retell.base_url') . "\n";
echo "  - services.retell.webhook_secret: " . (config('services.retell.webhook_secret') ? 'SET' : 'NOT SET') . "\n";

// Try a simple test with different headers
echo "\nTesting API with different authentication methods...\n\n";

$apiKey = $company->retell_api_key ? decrypt($company->retell_api_key) : config('services.retell.api_key');

if (!$apiKey) {
    echo "❌ No API key available for testing\n";
    exit(1);
}

// Test 1: Basic request to check if API is reachable
echo "1. Testing API reachability (no auth)...\n";
$response = \Illuminate\Support\Facades\Http::timeout(5)
    ->get('https://api.retellai.com/');
echo "   Status: " . $response->status() . "\n";
echo "   Response: " . substr($response->body(), 0, 100) . "\n";

// Test 2: Test with Bearer token (correct format)
echo "\n2. Testing with Bearer token...\n";
$response = \Illuminate\Support\Facades\Http::timeout(5)
    ->withHeaders([
        'Authorization' => 'Bearer ' . $apiKey,
        'Accept' => 'application/json',
        'Content-Type' => 'application/json'
    ])
    ->get('https://api.retellai.com/list-agents');
echo "   Status: " . $response->status() . "\n";
if ($response->status() !== 200) {
    echo "   Response: " . $response->body() . "\n";
}

// Test 3: Check if it's a test vs production key issue
echo "\n3. Checking key type...\n";
if (str_starts_with($apiKey, 'key_test_')) {
    echo "   ⚠️  This appears to be a TEST API key\n";
} elseif (str_starts_with($apiKey, 'key_live_')) {
    echo "   ✅ This appears to be a LIVE API key\n";
} elseif (str_starts_with($apiKey, 'key_')) {
    echo "   ❓ API key format recognized but type unclear\n";
} else {
    echo "   ❌ Unrecognized API key format\n";
}

echo "\nDONE.\n";