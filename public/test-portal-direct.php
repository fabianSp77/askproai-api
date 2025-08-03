<?php
// Direct login test - bypasses browser issues
session_start();

// Get CSRF token
$ch = curl_init('https://api.askproai.de/business/login');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, '/tmp/portal-test-cookies.txt');
curl_setopt($ch, CURLOPT_COOKIEFILE, '/tmp/portal-test-cookies.txt');
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$html = curl_exec($ch);
$info1 = curl_getinfo($ch);
curl_close($ch);

// Extract CSRF token
preg_match('/name="_token" value="([^"]+)"/', $html, $matches);
$csrfToken = $matches[1] ?? null;

echo "<h1>Business Portal Direct Test</h1>";
echo "<h2>Step 1: Get Login Page</h2>";
echo "<pre>";
echo "Status: " . $info1['http_code'] . "\n";
echo "CSRF Token: " . ($csrfToken ? substr($csrfToken, 0, 20) . '...' : 'NOT FOUND') . "\n";
echo "</pre>";

if (!$csrfToken) {
    echo "<p style='color: red'>Error: No CSRF token found!</p>";
    exit;
}

// Attempt login
$ch = curl_init('https://api.askproai.de/business/login');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    '_token' => $csrfToken,
    'email' => 'demo@askproai.de',
    'password' => 'password'
]));
curl_setopt($ch, CURLOPT_COOKIEJAR, '/tmp/portal-test-cookies.txt');
curl_setopt($ch, CURLOPT_COOKIEFILE, '/tmp/portal-test-cookies.txt');
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$response = curl_exec($ch);
$info2 = curl_getinfo($ch);
curl_close($ch);

// Parse response
list($headers, $body) = explode("\r\n\r\n", $response, 2);

echo "<h2>Step 2: Login Attempt</h2>";
echo "<pre>";
echo "Status: " . $info2['http_code'] . "\n";
echo "Headers:\n" . htmlspecialchars($headers) . "\n";
echo "</pre>";

if ($info2['http_code'] == 302 || $info2['http_code'] == 303) {
    echo "<p style='color: green'>✅ Login successful - redirected!</p>";
    preg_match('/Location: (.+)/', $headers, $locMatch);
    echo "<p>Redirect to: " . htmlspecialchars(trim($locMatch[1] ?? 'unknown')) . "</p>";
} elseif (strpos($body, 'Die angegebenen Zugangsdaten sind ungültig') !== false) {
    echo "<p style='color: red'>❌ Invalid credentials</p>";
} elseif ($info2['http_code'] == 419) {
    echo "<p style='color: red'>❌ CSRF token mismatch</p>";
} else {
    echo "<p style='color: red'>❌ Unknown error</p>";
}

// Test API access
$ch = curl_init('https://api.askproai.de/business/api/user');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, '/tmp/portal-test-cookies.txt');
curl_setopt($ch, CURLOPT_COOKIEFILE, '/tmp/portal-test-cookies.txt');
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$apiResponse = curl_exec($ch);
$info3 = curl_getinfo($ch);
curl_close($ch);

echo "<h2>Step 3: API Access Test</h2>";
echo "<pre>";
echo "Status: " . $info3['http_code'] . "\n";
if ($info3['http_code'] == 200) {
    $data = json_decode($apiResponse, true);
    echo "✅ Authenticated as: " . ($data['email'] ?? 'unknown') . "\n";
    echo "User data:\n" . json_encode($data, JSON_PRETTY_PRINT) . "\n";
} else {
    echo "❌ Not authenticated\n";
    echo "Response: " . htmlspecialchars(substr($apiResponse, 0, 200)) . "\n";
}
echo "</pre>";

// Clean up
@unlink('/tmp/portal-test-cookies.txt');