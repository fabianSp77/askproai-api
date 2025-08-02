<?php
// Test Real Browser Scenario with Session

echo "<h1>Test Real Browser Scenario</h1>";
echo "<h2>Testing: https://api.askproai.de/admin/login</h2>";
echo "<pre>";
echo "Time: " . date('Y-m-d H:i:s') . "\n";
echo str_repeat('=', 80) . "\n\n";

// Test 1: Initial request (no cookies)
echo "Test 1: Initial Request (No Cookies)\n";
echo str_repeat('-', 40) . "\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.askproai.de/admin/login");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
curl_setopt($ch, CURLOPT_COOKIEJAR, '/tmp/test-cookies.txt');
curl_setopt($ch, CURLOPT_COOKIEFILE, '/tmp/test-cookies.txt');

// Real browser headers
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
    'Accept-Language: en-US,en;q=0.5',
    'Accept-Encoding: gzip, deflate, br',
    'DNT: 1',
    'Connection: keep-alive',
    'Upgrade-Insecure-Requests: 1',
    'Sec-Fetch-Dest: document',
    'Sec-Fetch-Mode: navigate',
    'Sec-Fetch-Site: none',
    'Sec-Fetch-User: ?1',
]);

curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:128.0) Gecko/20100101 Firefox/128.0');

$response = curl_exec($ch);
$info = curl_getinfo($ch);

echo "Response Code: " . $info['http_code'] . "\n";
echo "Content Type: " . ($info['content_type'] ?? 'N/A') . "\n";

if ($info['http_code'] == 500) {
    echo "\n❌ ERROR 500 DETECTED ON FIRST REQUEST\n";
    
    $headerSize = $info['header_size'];
    $body = substr($response, $headerSize);
    
    // Save error response
    $errorFile = '/var/www/api-gateway/storage/logs/browser-error-' . time() . '.html';
    file_put_contents($errorFile, $body);
    echo "Error response saved to: $errorFile\n";
    
    // Try to extract error
    if (preg_match('/<div[^>]*class="[^"]*message[^"]*"[^>]*>(.*?)<\/div>/s', $body, $matches)) {
        echo "\nError Message: " . trim(strip_tags($matches[1])) . "\n";
    }
} else {
    echo "✅ Page loaded successfully\n";
}

// Test 2: Second request (with cookies)
echo "\n\nTest 2: Second Request (With Session Cookie)\n";
echo str_repeat('-', 40) . "\n";

$response2 = curl_exec($ch);
$info2 = curl_getinfo($ch);

echo "Response Code: " . $info2['http_code'] . "\n";

if ($info2['http_code'] == 500 && $info['http_code'] == 200) {
    echo "\n⚠️  ERROR APPEARS ON SECOND REQUEST!\n";
    echo "This suggests a session handling issue.\n";
}

curl_close($ch);

// Test 3: Check what cookies were set
echo "\n\nTest 3: Cookies Analysis\n";
echo str_repeat('-', 40) . "\n";

if (file_exists('/tmp/test-cookies.txt')) {
    $cookies = file_get_contents('/tmp/test-cookies.txt');
    echo "Cookies set by server:\n";
    $lines = explode("\n", $cookies);
    foreach ($lines as $line) {
        if (strpos($line, 'askproai') !== false || strpos($line, 'XSRF') !== false) {
            $parts = explode("\t", $line);
            if (count($parts) >= 7) {
                echo "- Cookie: " . $parts[5] . "\n";
                echo "  Domain: " . $parts[0] . "\n";
                echo "  Path: " . $parts[2] . "\n";
                echo "  Secure: " . ($parts[3] === 'TRUE' ? 'Yes' : 'No') . "\n";
                echo "  HttpOnly: " . ($parts[4] !== '0' ? 'Yes' : 'No') . "\n";
                echo "\n";
            }
        }
    }
    unlink('/tmp/test-cookies.txt');
}

// Test 4: Check specific browser conditions
echo "\nTest 4: Browser-Specific Conditions\n";
echo str_repeat('-', 40) . "\n";

// Test with different Accept headers
$acceptHeaders = [
    'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
    'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
    '*/*',
];

foreach ($acceptHeaders as $accept) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.askproai.de/admin/login");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Accept: $accept"]);
    
    curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "Accept: " . substr($accept, 0, 50) . "... => Status: $code\n";
}

// Test 5: Memory and process limits
echo "\n\nTest 5: System Limits\n";
echo str_repeat('-', 40) . "\n";
echo "PHP Memory Limit: " . ini_get('memory_limit') . "\n";
echo "Max Execution Time: " . ini_get('max_execution_time') . "\n";
echo "Post Max Size: " . ini_get('post_max_size') . "\n";
echo "Upload Max Filesize: " . ini_get('upload_max_filesize') . "\n";

// Check opcache
if (function_exists('opcache_get_status')) {
    $opcache = opcache_get_status();
    if ($opcache) {
        echo "\nOPcache Status:\n";
        echo "- Enabled: " . ($opcache['opcache_enabled'] ? 'Yes' : 'No') . "\n";
        echo "- Memory Usage: " . round($opcache['memory_usage']['used_memory'] / 1024 / 1024, 2) . " MB\n";
        echo "- Hit Rate: " . round($opcache['opcache_statistics']['opcache_hit_rate'], 2) . "%\n";
    }
}

echo "\n\nSUMMARY:\n";
echo "If you're seeing a 500 error in the browser but not in these tests,\n";
echo "possible causes:\n";
echo "1. Browser-specific headers or cookies\n";
echo "2. JavaScript errors causing additional requests\n";
echo "3. Browser extensions interfering\n";
echo "4. Cached resources causing conflicts\n";
echo "\nRecommendations:\n";
echo "1. Clear ALL browser data for api.askproai.de\n";
echo "2. Try in a completely new incognito/private window\n";
echo "3. Disable all browser extensions\n";
echo "4. Check browser console for JavaScript errors\n";
echo "5. Try a different browser\n";

echo "</pre>";