<?php

echo "=== Admin API 403 Debug ===\n\n";

// Login first
$ch = curl_init('https://api.askproai.de/api/admin/auth/login');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'email' => 'admin@askproai.de',
    'password' => 'admin123'
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    echo "Login failed!\n";
    exit(1);
}

$data = json_decode($response, true);
$token = $data['token'];
echo "✅ Logged in with token: " . substr($token, 0, 30) . "...\n\n";

// Test companies endpoint with verbose output
echo "Testing /api/admin/companies endpoint...\n";
$ch = curl_init('https://api.askproai.de/api/admin/companies');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_VERBOSE, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'Authorization: Bearer ' . $token
]);

// Capture verbose output
$verbose = fopen('php://temp', 'w+');
curl_setopt($ch, CURLOPT_STDERR, $verbose);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$headers = substr($response, 0, $headerSize);
$body = substr($response, $headerSize);

curl_close($ch);

echo "\nHTTP Code: $httpCode\n";
echo "\nResponse Headers:\n$headers\n";
echo "\nResponse Body:\n$body\n";

// Test with Laravel directly
echo "\n\n=== Direct Laravel Test ===\n";
require_once '/var/www/api-gateway/vendor/autoload.php';
$app = require_once '/var/www/api-gateway/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Company;
use Illuminate\Support\Facades\Gate;

$adminUser = User::where('email', 'admin@askproai.de')->first();
if ($adminUser) {
    echo "Admin user found: " . $adminUser->name . "\n";
    
    // Check Gate permissions
    echo "\nGate checks:\n";
    echo "can('viewAny', Company::class): " . (Gate::forUser($adminUser)->allows('viewAny', Company::class) ? 'YES' : 'NO') . "\n";
    echo "can('view_any_company'): " . ($adminUser->can('view_any_company') ? 'YES' : 'NO') . "\n";
    
    // Direct model access
    echo "\nDirect model access:\n";
    $companies = Company::withoutGlobalScopes()->limit(5)->get();
    echo "Companies found: " . $companies->count() . "\n";
    foreach ($companies as $company) {
        echo "  - " . $company->name . " (ID: " . $company->id . ")\n";
    }
}

echo "\n✅ Debug complete!\n";