<?php

// Test Business Portal Login POST

$url = 'https://api.askproai.de/business/login';

// First, get the login page to get CSRF token
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$response = curl_exec($ch);
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$headers = substr($response, 0, $headerSize);
$body = substr($response, $headerSize);
curl_close($ch);

// Extract cookies
preg_match_all('/Set-Cookie: ([^;]+)/', $headers, $matches);
$cookies = implode('; ', array_map(function($cookie) {
    return trim($cookie);
}, $matches[1]));

// Extract CSRF token from HTML
preg_match('/<input[^>]+name=["\']_token["\'][^>]+value=["\']([^"\']+)/', $body, $tokenMatch);
$csrfToken = $tokenMatch[1] ?? '';

echo "=== Testing Business Portal Login POST ===\n\n";
echo "CSRF Token found: " . ($csrfToken ? 'Yes' : 'No') . "\n";
echo "Cookies: " . substr($cookies, 0, 50) . "...\n\n";

// Test POST with valid portal user
$postData = [
    '_token' => $csrfToken,
    'email' => 'demo@askproai.de',
    'password' => 'password',
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
curl_setopt($ch, CURLOPT_COOKIE, $cookies);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: text/html,application/xhtml+xml',
    'Content-Type: application/x-www-form-urlencoded',
    'Origin: https://api.askproai.de',
    'Referer: https://api.askproai.de/business/login',
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$responseHeaders = substr($response, 0, $headerSize);
$responseBody = substr($response, $headerSize);
curl_close($ch);

echo "HTTP Response Code: $httpCode\n";

if ($httpCode == 302) {
    preg_match('/Location: (.+)/', $responseHeaders, $locationMatch);
    $location = trim($locationMatch[1] ?? '');
    echo "Redirect to: $location\n";
    
    if (strpos($location, '/business') !== false && strpos($location, '/login') === false) {
        echo "✓ Login successful - redirecting to dashboard\n";
    } else {
        echo "✗ Login failed - redirecting back to login\n";
    }
} elseif ($httpCode == 500) {
    echo "✗ 500 Internal Server Error\n\n";
    echo "Response Headers:\n";
    echo $responseHeaders . "\n";
    
    // Try to extract error from response
    if (strpos($responseBody, 'Whoops') !== false || strpos($responseBody, 'Exception') !== false) {
        echo "\nError details found in response:\n";
        // Extract error message if visible
        preg_match('/<div class="exception-message[^>]*>([^<]+)</', $responseBody, $errorMatch);
        if ($errorMatch) {
            echo "Error: " . trim($errorMatch[1]) . "\n";
        }
    }
} else {
    echo "Unexpected response code\n";
}

// Check rate limit headers
if (preg_match('/X-RateLimit-Limit: (\d+)/', $responseHeaders, $limitMatch)) {
    echo "\nRate Limit Info:\n";
    echo "Limit: " . $limitMatch[1] . "\n";
    
    if (preg_match('/X-RateLimit-Remaining: (\d+)/', $responseHeaders, $remainingMatch)) {
        echo "Remaining: " . $remainingMatch[1] . "\n";
    }
}

echo "\n=== Test Complete ===\n";