<?php

echo "=== TESTING CORS HEADERS ===\n\n";

// Test CORS headers with curl
$ch = curl_init();

// Set up the request
curl_setopt($ch, CURLOPT_URL, "https://api.askproai.de/business/api/calls/232/send-summary");
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "OPTIONS");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Origin: https://askproai.de',
    'Access-Control-Request-Method: POST',
    'Access-Control-Request-Headers: X-CSRF-TOKEN, Content-Type'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

// Split headers and body
$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$headers = substr($response, 0, $header_size);
$body = substr($response, $header_size);

curl_close($ch);

echo "1. OPTIONS Request (Preflight):\n";
echo "   Status: $httpCode\n\n";

echo "2. CORS Headers:\n";
$headerLines = explode("\n", $headers);
foreach ($headerLines as $line) {
    if (stripos($line, 'access-control') !== false) {
        echo "   $line\n";
    }
}

echo "\n3. Critical Checks:\n";
$hasAllowOrigin = false;
$hasAllowCredentials = false;
$hasAllowHeaders = false;

foreach ($headerLines as $line) {
    if (stripos($line, 'access-control-allow-origin:') !== false) {
        $hasAllowOrigin = true;
        echo "   ✅ Allow-Origin: " . trim(substr($line, strpos($line, ':') + 1)) . "\n";
    }
    if (stripos($line, 'access-control-allow-credentials:') !== false) {
        $hasAllowCredentials = true;
        $value = trim(substr($line, strpos($line, ':') + 1));
        echo "   " . ($value === 'true' ? '✅' : '❌') . " Allow-Credentials: $value\n";
    }
    if (stripos($line, 'access-control-allow-headers:') !== false) {
        $hasAllowHeaders = true;
        echo "   ✅ Allow-Headers found\n";
    }
}

if (!$hasAllowOrigin) {
    echo "   ❌ Access-Control-Allow-Origin header missing!\n";
}
if (!$hasAllowCredentials) {
    echo "   ❌ Access-Control-Allow-Credentials header missing!\n";
}
if (!$hasAllowHeaders) {
    echo "   ❌ Access-Control-Allow-Headers header missing!\n";
}

echo "\n4. Expected vs Actual:\n";
echo "   Expected Origin: https://askproai.de\n";
echo "   Expected Credentials: true\n";
echo "   Expected Methods: POST\n";

echo "\n=== RESULT ===\n";
if ($httpCode == 200 && $hasAllowOrigin && $hasAllowCredentials) {
    echo "✅ CORS is properly configured!\n";
} else {
    echo "❌ CORS is NOT properly configured!\n";
    echo "Please restart PHP-FPM: sudo systemctl restart php8.3-fpm\n";
}