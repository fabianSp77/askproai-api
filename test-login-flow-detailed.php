<?php
// Test the full login flow with detailed debugging
$baseUrl = 'https://api.askproai.de';
$cookieFile = '/tmp/test-cookies-' . uniqid() . '.txt';

echo "=== Detailed Business Portal Login Test ===\n\n";

// Step 1: Get login page
echo "1. GET /business/login\n";
$ch = curl_init($baseUrl . '/business/login');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
$response = curl_exec($ch);
$info = curl_getinfo($ch);
curl_close($ch);

echo "   Status: {$info['http_code']}\n";

// Extract CSRF token
preg_match('/name="_token"\s+value="([^"]+)"/', $response, $matches);
$csrfToken = $matches[1] ?? null;
echo "   CSRF Token: " . ($csrfToken ? substr($csrfToken, 0, 20) . '...' : 'NOT FOUND') . "\n";

// Extract session cookie
preg_match('/askproai_portal_session=([^;]+)/', $response, $sessionMatches);
$sessionCookie = $sessionMatches[1] ?? null;
echo "   Portal Session Cookie: " . ($sessionCookie ? substr($sessionCookie, 0, 20) . '...' : 'NOT FOUND') . "\n\n";

// Step 2: Submit login
echo "2. POST /business/login\n";
$postData = http_build_query([
    '_token' => $csrfToken,
    'email' => 'demo@askproai.de',
    'password' => 'password'
]);

$ch = curl_init($baseUrl . '/business/login');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/x-www-form-urlencoded',
    'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8'
]);

$response = curl_exec($ch);
$info = curl_getinfo($ch);
curl_close($ch);

echo "   Status: {$info['http_code']}\n";

// Parse response headers
list($headers, $body) = explode("\r\n\r\n", $response, 2);
$headerLines = explode("\r\n", $headers);

echo "   Response Headers:\n";
foreach ($headerLines as $line) {
    if (stripos($line, 'Location:') !== false || stripos($line, 'Set-Cookie:') !== false) {
        echo "     $line\n";
    }
}

// Check redirect location
if ($info['http_code'] == 302) {
    preg_match('/Location:\s*(.+)/', $headers, $locationMatch);
    $location = trim($locationMatch[1] ?? '');
    echo "\n   Redirect to: $location\n";
    
    if (strpos($location, '/dashboard') !== false) {
        echo "   ✓ SUCCESS: Redirected to dashboard\n";
    } elseif (strpos($location, '/login') !== false) {
        echo "   ✗ FAILED: Redirected back to login\n";
        
        // Follow redirect to get error message
        echo "\n3. Following redirect to get error message...\n";
        $ch = curl_init($location);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        $errorResponse = curl_exec($ch);
        curl_close($ch);
        
        // Look for error messages
        if (preg_match('/<div[^>]*class="[^"]*alert[^"]*"[^>]*>(.*?)<\/div>/s', $errorResponse, $errorMatch)) {
            echo "   Error message found: " . strip_tags(trim($errorMatch[1])) . "\n";
        }
        
        // Look for validation errors
        if (preg_match('/<span[^>]*class="[^"]*invalid-feedback[^"]*"[^>]*>(.*?)<\/span>/s', $errorResponse, $validationMatch)) {
            echo "   Validation error: " . strip_tags(trim($validationMatch[1])) . "\n";
        }
    }
}

// Step 4: Check session cookies
echo "\n4. Checking cookies after login:\n";
$cookies = file_get_contents($cookieFile);
$cookieLines = explode("\n", $cookies);
foreach ($cookieLines as $line) {
    if (strpos($line, 'askproai') !== false || strpos($line, 'XSRF') !== false) {
        $parts = preg_split('/\s+/', $line);
        if (count($parts) >= 7) {
            echo "   {$parts[5]}: " . substr($parts[6], 0, 30) . "...\n";
        }
    }
}

// Clean up
unlink($cookieFile);

echo "\n=== End of Test ===\n";