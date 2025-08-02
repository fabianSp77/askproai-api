#!/usr/bin/env php
<?php
// Test if authentication persists after login

$baseUrl = 'https://api.askproai.de';
$cookieFile = tempnam(sys_get_temp_dir(), 'cookie');

// Step 1: Login
echo "=== Step 1: Login ===\n";
$ch = curl_init();

// Get login page
curl_setopt_array($ch, [
    CURLOPT_URL => $baseUrl . '/business/login',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_COOKIEJAR => $cookieFile,
    CURLOPT_COOKIEFILE => $cookieFile,
    CURLOPT_FOLLOWLOCATION => false,
    CURLOPT_HEADER => false,
]);

$response = curl_exec($ch);

// Extract CSRF token
preg_match('/<meta name="csrf-token" content="([^"]+)"/', $response, $matches);
$csrfToken = $matches[1] ?? null;

if (!$csrfToken) {
    preg_match('/<input[^>]+name="_token"[^>]+value="([^"]+)"/', $response, $matches);
    $csrfToken = $matches[1] ?? null;
}

// Post login
$postData = http_build_query([
    'email' => 'demo@askproai.de',
    'password' => 'password123',
    '_token' => $csrfToken,
]);

curl_setopt_array($ch, [
    CURLOPT_URL => $baseUrl . '/business/login',
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $postData,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/x-www-form-urlencoded',
        'Referer: ' . $baseUrl . '/business/login',
    ],
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
echo "Login response: $httpCode\n";

// Step 2: Check authentication
echo "\n=== Step 2: Check Auth Status ===\n";
curl_setopt_array($ch, [
    CURLOPT_URL => $baseUrl . '/business/api/auth/check',
    CURLOPT_POST => false,
    CURLOPT_HTTPHEADER => [
        'Accept: application/json',
        'X-Requested-With: XMLHttpRequest',
    ],
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
echo "Auth check response: $httpCode\n";
echo "Response: $response\n";

// Step 3: Try to access dashboard
echo "\n=== Step 3: Access Dashboard ===\n";
curl_setopt_array($ch, [
    CURLOPT_URL => $baseUrl . '/business/dashboard',
    CURLOPT_POST => false,
    CURLOPT_HEADER => true,
    CURLOPT_HTTPHEADER => [
        'Accept: text/html',
    ],
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$effectiveUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
echo "Dashboard response: $httpCode\n";
echo "Effective URL: $effectiveUrl\n";

curl_close($ch);
unlink($cookieFile);