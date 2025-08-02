<?php
// Test Login with Browser Simulation

echo "<h1>Test Login with Browser Simulation</h1>";
echo "<pre>";
echo "Time: " . date('Y-m-d H:i:s') . "\n";
echo str_repeat('=', 80) . "\n\n";

// First check PHP-FPM socket
echo "Checking PHP-FPM Socket:\n";
$socket = '/run/php/php8.3-fpm.sock';
if (file_exists($socket)) {
    echo "✅ Socket exists: $socket\n";
    $perms = substr(sprintf('%o', fileperms($socket)), -4);
    echo "  Permissions: $perms\n";
} else {
    echo "❌ Socket not found: $socket\n";
}
echo "\n";

// Simulate a browser request
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.askproai.de/admin/login");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_NOBODY, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

// Set browser headers
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
    'Accept-Language: en-US,en;q=0.5',
    'Accept-Encoding: gzip, deflate, br',
    'Connection: keep-alive',
    'Upgrade-Insecure-Requests: 1',
    'Sec-Fetch-Dest: document',
    'Sec-Fetch-Mode: navigate',
    'Sec-Fetch-Site: none',
    'Sec-Fetch-User: ?1',
    'Cache-Control: max-age=0',
]);

// Set user agent
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36');

echo "Making request to: https://api.askproai.de/admin/login\n";
echo str_repeat('-', 40) . "\n";

$startTime = microtime(true);
$response = curl_exec($ch);
$endTime = microtime(true);
$duration = round(($endTime - $startTime) * 1000, 2);

$info = curl_getinfo($ch);
$error = curl_error($ch);
curl_close($ch);

echo "Request completed in: {$duration}ms\n\n";

if ($error) {
    echo "❌ CURL Error: $error\n\n";
} else {
    echo "Response Info:\n";
    echo "- HTTP Code: " . $info['http_code'] . "\n";
    echo "- Content Type: " . ($info['content_type'] ?? 'N/A') . "\n";
    echo "- Total Time: " . round($info['total_time'] * 1000, 2) . "ms\n";
    echo "- Size: " . $info['size_download'] . " bytes\n";
    echo "\n";
    
    // Split headers and body
    $headerSize = $info['header_size'];
    $headers = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);
    
    echo "Response Headers:\n";
    echo $headers;
    echo "\n";
    
    if ($info['http_code'] == 500) {
        echo "❌ 500 ERROR DETECTED\n\n";
        
        // Try to extract error from body
        if (preg_match('/<title>(.*?)<\/title>/i', $body, $matches)) {
            echo "Page Title: " . strip_tags($matches[1]) . "\n";
        }
        
        if (preg_match('/<h1[^>]*>(.*?)<\/h1>/i', $body, $matches)) {
            echo "Error Title: " . strip_tags($matches[1]) . "\n";
        }
        
        if (preg_match('/<div[^>]*class="[^"]*message[^"]*"[^>]*>(.*?)<\/div>/s', $body, $matches)) {
            echo "Error Message: " . strip_tags($matches[1]) . "\n\n";
        }
        
        // Look for Laravel error details
        if (strpos($body, 'Whoops') !== false || strpos($body, 'exception') !== false) {
            echo "Laravel Error Page Detected\n";
            
            // Save full response
            $filename = '/var/www/api-gateway/storage/logs/browser-login-error-' . time() . '.html';
            file_put_contents($filename, $body);
            echo "Full error page saved to: $filename\n";
        }
    } elseif ($info['http_code'] == 200) {
        echo "✅ Login page loaded successfully\n";
        
        // Check for Filament/Livewire
        if (strpos($body, 'wire:') !== false) {
            echo "- Livewire detected\n";
        }
        if (strpos($body, 'filament') !== false) {
            echo "- Filament detected\n";
        }
    }
}

// Check system resources
echo "\nSystem Resources:\n";
echo "- Memory Usage: " . round(memory_get_usage() / 1024 / 1024, 2) . " MB\n";
echo "- Memory Peak: " . round(memory_get_peak_usage() / 1024 / 1024, 2) . " MB\n";

// Check disk space
$diskFree = disk_free_space('/');
$diskTotal = disk_total_space('/');
$diskUsedPercent = round((($diskTotal - $diskFree) / $diskTotal) * 100, 2);
echo "- Disk Usage: {$diskUsedPercent}% (" . round($diskFree / 1024 / 1024 / 1024, 2) . " GB free)\n";

echo "</pre>";