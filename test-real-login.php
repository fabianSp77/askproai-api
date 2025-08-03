<?php
// Direct test without Laravel bootstrap
$url = 'https://api.askproai.de/business/login';

// Step 1: Get CSRF token
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, '/tmp/cookies.txt');
curl_setopt($ch, CURLOPT_COOKIEFILE, '/tmp/cookies.txt');
$response = curl_exec($ch);
$info = curl_getinfo($ch);
curl_close($ch);

echo "=== GET /business/login ===\n";
echo "Status: " . $info['http_code'] . "\n";

// Extract CSRF token
preg_match('/name="_token" value="([^"]+)"/', $response, $matches);
$csrfToken = $matches[1] ?? null;
echo "CSRF Token: " . ($csrfToken ? substr($csrfToken, 0, 20) . '...' : 'NOT FOUND') . "\n\n";

// Extract XSRF token from cookie
preg_match('/XSRF-TOKEN=([^;]+)/', $response, $xsrfMatches);
$xsrfToken = $xsrfMatches[1] ?? null;
echo "XSRF Cookie: " . ($xsrfToken ? substr($xsrfToken, 0, 20) . '...' : 'NOT FOUND') . "\n\n";

// Step 2: Login
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    '_token' => $csrfToken,
    'email' => 'demo@askproai.de',
    'password' => 'password'
]));
curl_setopt($ch, CURLOPT_COOKIEJAR, '/tmp/cookies.txt');
curl_setopt($ch, CURLOPT_COOKIEFILE, '/tmp/cookies.txt');
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/x-www-form-urlencoded',
    'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
    'X-Requested-With: XMLHttpRequest',
    'X-XSRF-TOKEN: ' . urldecode($xsrfToken ?? '')
]);

$response = curl_exec($ch);
$info = curl_getinfo($ch);
curl_close($ch);

echo "=== POST /business/login ===\n";
echo "Status: " . $info['http_code'] . "\n";
echo "Location: " . ($info['redirect_url'] ?? 'None') . "\n";

// Show headers
list($headers, $body) = explode("\r\n\r\n", $response, 2);
echo "\nResponse Headers:\n";
echo $headers . "\n";

// If 500 error, show body
if ($info['http_code'] == 500) {
    echo "\n=== 500 ERROR BODY ===\n";
    // Extract error title
    if (preg_match('/<title>([^<]+)<\/title>/', $body, $matches)) {
        echo "Title: " . $matches[1] . "\n";
    }
    if (preg_match('/class="title">([^<]+)</', $body, $matches)) {
        echo "Error: " . $matches[1] . "\n";
    }
    if (preg_match('/class="message[^"]*">([^<]+)</', $body, $matches)) {
        echo "Message: " . $matches[1] . "\n";
    }
    
    // Show first 500 chars of stripped content
    echo "\nContent preview:\n";
    echo substr(strip_tags($body), 0, 500) . "\n";
}

// Clean up
@unlink('/tmp/cookies.txt');