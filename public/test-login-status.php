<?php
// Test login and then check status
$baseUrl = 'https://api.askproai.de';
$cookieFile = '/tmp/test-session-' . uniqid() . '.txt';

echo "=== Testing Login and Session Status ===\n\n";

// Step 1: Get login page
echo "1. Getting login page...\n";
$ch = curl_init($baseUrl . '/business/login');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
$response = curl_exec($ch);
curl_close($ch);

// Extract CSRF token
preg_match('/name="_token"\s+value="([^"]+)"/', $response, $matches);
$csrfToken = $matches[1] ?? null;
echo "   CSRF Token obtained\n";

// Step 2: Login
echo "\n2. Logging in...\n";
$ch = curl_init($baseUrl . '/business/login');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    '_token' => $csrfToken,
    'email' => 'demo@askproai.de',
    'password' => 'password'
]));
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
$response = curl_exec($ch);
$info = curl_getinfo($ch);
curl_close($ch);

echo "   Login response: {$info['http_code']}\n";

// Step 3: Check auth status via API
echo "\n3. Checking auth status...\n";
$ch = curl_init($baseUrl . '/business/api/auth/check');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
$response = curl_exec($ch);
$info = curl_getinfo($ch);
curl_close($ch);

echo "   Auth check response: {$info['http_code']}\n";
if ($info['http_code'] == 200) {
    $data = json_decode($response, true);
    echo "   Response: " . json_encode($data) . "\n";
} else {
    echo "   Response: $response\n";
}

// Step 4: Try to access dashboard
echo "\n4. Trying to access dashboard...\n";
$ch = curl_init($baseUrl . '/business/dashboard');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
$response = curl_exec($ch);
$info = curl_getinfo($ch);
curl_close($ch);

echo "   Dashboard response: {$info['http_code']}\n";
if ($info['http_code'] == 302) {
    preg_match('/Location:\s*(.+)/', $response, $locationMatch);
    echo "   Redirected to: " . trim($locationMatch[1] ?? 'unknown') . "\n";
}

// Clean up
unlink($cookieFile);

echo "\n=== End of Test ===\n";