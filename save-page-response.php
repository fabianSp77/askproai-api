<?php

$ch = curl_init();
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_COOKIEJAR, '/tmp/test-cookies.txt');
curl_setopt($ch, CURLOPT_COOKIEFILE, '/tmp/test-cookies.txt');

// First get login page for CSRF
curl_setopt($ch, CURLOPT_URL, "https://api.askproai.de/admin/login");
$loginPage = curl_exec($ch);

// Extract CSRF token
preg_match('/name="csrf-token" content="([^"]+)"/', $loginPage, $matches);
$csrfToken = $matches[1] ?? '';

if ($csrfToken) {
    // Login
    curl_setopt($ch, CURLOPT_URL, "https://api.askproai.de/admin/login");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'email' => 'admin@askproai.de',
        'password' => 'IJ=cT@@zL6e+YV',
        '_token' => $csrfToken
    ]));
    curl_exec($ch);
    
    // Get calls page
    curl_setopt($ch, CURLOPT_URL, "https://api.askproai.de/admin/calls");
    curl_setopt($ch, CURLOPT_POST, false);
    curl_setopt($ch, CURLOPT_HTTPGET, true);
    $response = curl_exec($ch);
    
    // Save full response to file
    file_put_contents('/tmp/calls-page-response.html', $response);
    echo "Response saved to /tmp/calls-page-response.html\n";
    
    // Check structure
    echo "\nAnalyzing response structure:\n";
    echo "- Total size: " . strlen($response) . " bytes\n";
    echo "- HTML tags: " . substr_count($response, '<') . "\n";
    echo "- Style tags: " . substr_count($response, '<style') . "\n";
    echo "- Script tags: " . substr_count($response, '<script') . "\n";
    echo "- Div tags: " . substr_count($response, '<div') . "\n";
    echo "- Table tags: " . substr_count($response, '<table') . "\n";
    
    // Check DOCTYPE
    if (strpos($response, '<!DOCTYPE') === 0) {
        echo "- Has DOCTYPE at beginning: YES\n";
    } else {
        echo "- Has DOCTYPE at beginning: NO\n";
        echo "- First 100 chars: " . substr($response, 0, 100) . "\n";
    }
}

curl_close($ch);