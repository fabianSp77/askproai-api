<?php
// Fresh login test
$email = 'demo@askproai.de';
$password = 'password';

echo "=== Business Portal Login Test ===\n\n";

// Step 1: Get CSRF token
$ch = curl_init('https://api.askproai.de/business/login');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, '/tmp/test-cookies.txt');
curl_setopt($ch, CURLOPT_COOKIEFILE, '/tmp/test-cookies.txt');
$html = curl_exec($ch);
curl_close($ch);

preg_match('/name="_token" value="([^"]+)"/', $html, $matches);
$csrfToken = $matches[1] ?? null;

echo "1. CSRF Token: " . ($csrfToken ? substr($csrfToken, 0, 20) . '...' : 'NOT FOUND') . "\n";

// Step 2: Perform login
$ch = curl_init('https://api.askproai.de/business/login');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    '_token' => $csrfToken,
    'email' => $email,
    'password' => $password
]));
curl_setopt($ch, CURLOPT_COOKIEJAR, '/tmp/test-cookies.txt');
curl_setopt($ch, CURLOPT_COOKIEFILE, '/tmp/test-cookies.txt');
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
curl_setopt($ch, CURLOPT_HEADER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "2. Login Response: HTTP $httpCode\n";

// Parse headers
list($headers, $body) = explode("\r\n\r\n", $response, 2);
if (strpos($headers, 'Location:') !== false) {
    preg_match('/Location: (.+)/', $headers, $locMatch);
    echo "   Redirect to: " . trim($locMatch[1] ?? 'none') . "\n";
}

// Check for error message
if (strpos($body, 'Die angegebenen Zugangsdaten sind ungültig') !== false) {
    echo "   ❌ Invalid credentials error\n";
} elseif ($httpCode == 302 || $httpCode == 303) {
    echo "   ✅ Login successful (redirect)\n";
} elseif ($httpCode == 419) {
    echo "   ❌ CSRF token mismatch\n";
}

// Step 3: Test authenticated access
$ch = curl_init('https://api.askproai.de/business/api/user');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, '/tmp/test-cookies.txt');
curl_setopt($ch, CURLOPT_COOKIEFILE, '/tmp/test-cookies.txt');
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
$apiResponse = curl_exec($ch);
$apiCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "3. API Test: HTTP $apiCode\n";
if ($apiCode == 200) {
    $data = json_decode($apiResponse, true);
    echo "   ✅ Authenticated as: " . ($data['email'] ?? 'unknown') . "\n";
} else {
    echo "   ❌ Not authenticated\n";
}

// Clean up
@unlink('/tmp/test-cookies.txt');