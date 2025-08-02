#!/usr/bin/env php
<?php
// Test business portal login with browser-like headers

$baseUrl = 'https://api.askproai.de';
$cookieFile = tempnam(sys_get_temp_dir(), 'cookie');

// Step 1: GET login page to get CSRF token
echo "=== Step 1: GET Login Page ===\n";
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $baseUrl . '/business/login',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_COOKIEJAR => $cookieFile,
    CURLOPT_COOKIEFILE => $cookieFile,
    CURLOPT_FOLLOWLOCATION => false,
    CURLOPT_HEADER => true,
    CURLOPT_HTTPHEADER => [
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        'Accept-Language: de-DE,de;q=0.9,en;q=0.8',
    ],
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$headers = substr($response, 0, $headerSize);
$body = substr($response, $headerSize);

echo "Status: $httpCode\n";

// Extract CSRF token from meta tag
preg_match('/<meta name="csrf-token" content="([^"]+)"/', $body, $matches);
$csrfToken = $matches[1] ?? null;
echo "CSRF Token: " . ($csrfToken ? substr($csrfToken, 0, 20) . '...' : 'NOT FOUND') . "\n";

// Extract CSRF token from hidden input field as fallback
if (!$csrfToken) {
    preg_match('/<input[^>]+name="_token"[^>]+value="([^"]+)"/', $body, $matches);
    $csrfToken = $matches[1] ?? null;
    echo "CSRF Token (from input): " . ($csrfToken ? substr($csrfToken, 0, 20) . '...' : 'NOT FOUND') . "\n";
}

// Step 2: POST login
echo "\n=== Step 2: POST Login ===\n";
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
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        'Accept-Language: de-DE,de;q=0.9,en;q=0.8',
        'Content-Type: application/x-www-form-urlencoded',
        'Referer: ' . $baseUrl . '/business/login',
        'Origin: ' . $baseUrl,
    ],
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$headers = substr($response, 0, $headerSize);
$body = substr($response, $headerSize);

echo "Status: $httpCode\n";
echo "Headers:\n";
echo $headers . "\n";

if ($httpCode >= 400) {
    echo "Error Response Body:\n";
    echo substr($body, 0, 1000) . "\n";
}

// Check if we got redirected
$redirectUrl = curl_getinfo($ch, CURLINFO_REDIRECT_URL);
if ($redirectUrl) {
    echo "Redirect to: $redirectUrl\n";
}

// Step 3: Follow redirect if successful
if ($httpCode == 302 || $httpCode == 301) {
    preg_match('/Location: (.+)/', $headers, $matches);
    $location = trim($matches[1] ?? '');
    echo "Location header: $location\n";
    
    // If relative URL, make it absolute
    if (strpos($location, 'http') !== 0) {
        $location = $baseUrl . $location;
    }
    
    echo "\n=== Step 3: Follow Redirect ===\n";
    curl_setopt_array($ch, [
        CURLOPT_URL => $location,
        CURLOPT_POST => false,
        CURLOPT_HTTPHEADER => [
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language: de-DE,de;q=0.9,en;q=0.8',
        ],
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    echo "Final Status: $httpCode\n";
    echo "Final URL: " . curl_getinfo($ch, CURLINFO_EFFECTIVE_URL) . "\n";
}

curl_close($ch);
unlink($cookieFile);