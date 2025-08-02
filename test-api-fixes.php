#!/usr/bin/env php
<?php

echo "🔍 Testing Business Portal API Fixes...\n";
echo "=====================================\n";

// Test configuration
$baseUrl = 'https://api.askproai.de';
$apiEndpoints = [
    '/business/api/customers' => 'Customer List',
    '/business/api/stats' => 'Dashboard Stats',
    '/business/api/user' => 'Current User',
    '/business/api/appointments' => 'Appointments List'
];

// Color codes
$green = "\033[0;32m";
$red = "\033[0;31m";
$yellow = "\033[1;33m";
$reset = "\033[0m";

// First, let's check if routes are registered
echo "\n{$yellow}1. Checking Route Registration:{$reset}\n";
$routes = shell_exec("php artisan route:list | grep -E 'business/api/(customers|stats|user)' | head -10");
echo $routes ?: "No routes found (this might be normal if route cache is enabled)\n";

// Test each endpoint
echo "\n{$yellow}2. Testing API Endpoints (without auth):{$reset}\n";
foreach ($apiEndpoints as $endpoint => $name) {
    $url = $baseUrl . $endpoint;
    
    // Test with curl
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_NOBODY, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // Check status
    if ($httpCode == 401) {
        echo "{$green}✅ {$name}: 401 Unauthorized (Expected - Auth required){$reset}\n";
    } elseif ($httpCode == 404) {
        echo "{$red}❌ {$name}: 404 Not Found (Route not registered!){$reset}\n";
    } elseif ($httpCode == 500) {
        echo "{$red}❌ {$name}: 500 Server Error (Critical issue!){$reset}\n";
    } elseif ($httpCode == 419) {
        echo "{$red}❌ {$name}: 419 CSRF Token (CSRF not disabled!){$reset}\n";
    } else {
        echo "{$yellow}⚠️  {$name}: {$httpCode} (Unexpected status){$reset}\n";
    }
}

// Test CSRF exception
echo "\n{$yellow}3. Testing CSRF Exception:{$reset}\n";
$testData = json_encode(['test' => 'data']);
$ch = curl_init($baseUrl . '/business/api/customers');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $testData);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode == 419) {
    echo "{$red}❌ CSRF Protection still active on API routes!{$reset}\n";
} elseif ($httpCode == 401) {
    echo "{$green}✅ CSRF bypassed successfully (401 = Auth required){$reset}\n";
} elseif ($httpCode == 405) {
    echo "{$green}✅ CSRF bypassed (405 = Method not allowed - POST endpoint might not exist){$reset}\n";
} else {
    echo "{$yellow}⚠️  Unexpected response: {$httpCode}{$reset}\n";
}

// Summary
echo "\n{$yellow}4. Quick Fix Commands:{$reset}\n";
echo "If routes are missing, run:\n";
echo "  php artisan route:cache\n";
echo "\nIf CSRF is still blocking, run:\n";
echo "  php artisan config:cache\n";
echo "\nTo test with authentication:\n";
echo "  1. Login at: {$baseUrl}/business/login\n";
echo "  2. Copy session cookie\n";
echo "  3. Test with: curl -H 'Cookie: [your-session-cookie]' {$baseUrl}/business/api/customers\n";

echo "\n{$green}✅ Test completed!{$reset}\n";