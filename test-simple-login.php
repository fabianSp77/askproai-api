<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Hash;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

echo "Simple Login Test\n";
echo str_repeat("=", 50) . "\n\n";

// Test with curl commands
$tests = [
    [
        'name' => 'Business Portal Login',
        'url' => 'https://api.askproai.de/business/login',
        'email' => 'demo@example.com',
        'password' => 'demo123'
    ],
    [
        'name' => 'Admin Portal Login',
        'url' => 'https://api.askproai.de/admin/login',
        'email' => 'admin@askproai.de',
        'password' => 'demo123'
    ]
];

foreach ($tests as $test) {
    echo "Testing: {$test['name']}\n";
    echo "URL: {$test['url']}\n";
    
    // Step 1: Get login page and extract CSRF token
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $test['url']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, '/tmp/cookies.txt');
    curl_setopt($ch, CURLOPT_COOKIEFILE, '/tmp/cookies.txt');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if ($httpCode !== 200) {
        echo "✗ Failed to load login page (HTTP $httpCode)\n\n";
        continue;
    }
    
    // Extract CSRF token
    preg_match('/<meta name="csrf-token" content="([^"]+)"/', $response, $matches);
    $csrfToken = $matches[1] ?? null;
    
    if (!$csrfToken) {
        // Try to find it in form
        preg_match('/<input[^>]+name="_token"[^>]+value="([^"]+)"/', $response, $matches);
        $csrfToken = $matches[1] ?? null;
    }
    
    if (!$csrfToken) {
        echo "✗ No CSRF token found\n\n";
        continue;
    }
    
    echo "✓ CSRF token found\n";
    
    // Step 2: Submit login
    $postData = http_build_query([
        'email' => $test['email'],
        'password' => $test['password'],
        '_token' => $csrfToken
    ]);
    
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($ch, CURLOPT_HEADER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $redirectUrl = curl_getinfo($ch, CURLINFO_REDIRECT_URL);
    
    echo "Response code: $httpCode\n";
    
    if ($httpCode === 302) {
        echo "✓ Login successful (redirect to: " . ($redirectUrl ?: 'unknown') . ")\n";
    } elseif ($httpCode === 200 || $httpCode === 422) {
        // Check for error messages in response
        if (strpos($response, 'ungültig') !== false || strpos($response, 'invalid') !== false) {
            echo "✗ Login failed - invalid credentials\n";
        } else {
            echo "✗ Login failed - returned to login page\n";
        }
    } else {
        echo "✗ Unexpected response code\n";
    }
    
    curl_close($ch);
    echo "\n";
}

// Clean up
@unlink('/tmp/cookies.txt');

echo str_repeat("=", 50) . "\n";