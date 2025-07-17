<?php

echo "=== Admin API Debug Test ===\n\n";

// Test 1: Login
echo "1. Testing Login...\n";
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

echo "Login Response Code: $httpCode\n";
echo "Login Response: " . substr($response, 0, 200) . "\n\n";

if ($httpCode !== 200) {
    echo "Login failed!\n";
    exit(1);
}

$data = json_decode($response, true);
$token = $data['token'];
echo "Token received: " . substr($token, 0, 30) . "...\n\n";

// Test 2: Test auth/user endpoint
echo "2. Testing /auth/user endpoint...\n";
$ch = curl_init('https://api.askproai.de/api/admin/auth/user');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'Authorization: Bearer ' . $token
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Auth/User Response Code: $httpCode\n";
echo "Auth/User Response: " . substr($response, 0, 200) . "\n\n";

// Test 3: Test dashboard/stats with simple=true
echo "3. Testing /dashboard/stats?simple=true...\n";
$ch = curl_init('https://api.askproai.de/api/admin/dashboard/stats?simple=true');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'Authorization: Bearer ' . $token
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$responseHeaders = curl_getinfo($ch);
curl_close($ch);

echo "Dashboard Stats Response Code: $httpCode\n";
echo "Dashboard Stats Response: $response\n";
echo "Response Headers: " . print_r($responseHeaders, true) . "\n\n";

// Test 4: Direct database test
echo "4. Testing database connection...\n";
require_once '/var/www/api-gateway/vendor/autoload.php';
$app = require_once '/var/www/api-gateway/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Company;

$adminUser = User::where('email', 'admin@askproai.de')->first();
if ($adminUser) {
    echo "Admin user found: ID=" . $adminUser->id . ", Name=" . $adminUser->name . "\n";
    echo "Admin has tokens: " . $adminUser->tokens()->count() . "\n";
} else {
    echo "Admin user NOT found!\n";
}

$companyCount = Company::withoutGlobalScopes()->count();
echo "Total companies: $companyCount\n";

echo "\n✅ Debug test complete!\n";