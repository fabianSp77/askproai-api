<?php
// Debug login response more thoroughly
$baseUrl = 'https://api.askproai.de';
$cookieFile = '/tmp/debug-cookies-' . uniqid() . '.txt';

echo "=== Debug Login Response ===\n\n";

// Step 1: Get login page
$ch = curl_init($baseUrl . '/business/login');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
$response = curl_exec($ch);
curl_close($ch);

// Extract CSRF token
preg_match('/name="_token"\s+value="([^"]+)"/', $response, $matches);
$csrfToken = $matches[1] ?? null;

// Step 2: Submit login with verbose output
echo "Submitting login...\n";
$ch = curl_init($baseUrl . '/business/login');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    '_token' => $csrfToken,
    'email' => 'demo@askproai.de',
    'password' => 'password'
]));
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
curl_setopt($ch, CURLOPT_VERBOSE, true);
curl_setopt($ch, CURLOPT_STDERR, fopen('php://stdout', 'w'));

$response = curl_exec($ch);
$info = curl_getinfo($ch);
curl_close($ch);

echo "\n\nResponse Status: {$info['http_code']}\n";

// Parse headers and body
list($headers, $body) = explode("\r\n\r\n", $response, 2);

// Look for specific headers
if (preg_match('/^Location:\s*(.*)$/m', $headers, $match)) {
    echo "Location header: " . trim($match[1]) . "\n";
}

// Check cookies
echo "\nCookies after login:\n";
$cookies = file_get_contents($cookieFile);
echo $cookies;

// Clean up
unlink($cookieFile);