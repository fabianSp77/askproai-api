<?php
// Test business portal login directly

$url = 'https://api.askproai.de';
$cookieFile = tempnam(sys_get_temp_dir(), 'portal_test_');

echo "Testing Business Portal Login...\n\n";

// 1. Get login page
$ch = curl_init($url . '/business/login');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
$response = curl_exec($ch);
curl_close($ch);

// Extract CSRF
preg_match('/name="_token"\s+value="([^"]+)"/', $response, $matches);
$csrf = $matches[1] ?? null;

// 2. Login
$ch = curl_init($url . '/business/login');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    '_token' => $csrf,
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

echo "Login response: {$info['http_code']}\n";

// 3. Check auth
$ch = curl_init($url . '/business/api/auth/check');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
$response = curl_exec($ch);
$info = curl_getinfo($ch);
curl_close($ch);

echo "Auth check: {$info['http_code']}\n";
if ($info['http_code'] == 200) {
    $data = json_decode($response, true);
    echo "Authenticated: " . ($data['authenticated'] ?? 'unknown') . "\n";
} else {
    echo "Response: $response\n";
}

unlink($cookieFile);