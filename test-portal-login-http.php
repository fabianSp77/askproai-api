#!/usr/bin/env php
<?php

// Test the actual HTTP login flow

$baseUrl = 'https://api.askproai.de';
$cookieFile = tempnam(sys_get_temp_dir(), 'portal_login_test_');

echo "=== PORTAL LOGIN HTTP TEST ===\n\n";

// Function to make HTTP request
function makeRequest($url, $method = 'GET', $data = null, $cookieFile = null) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false); // Don't follow redirects automatically
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    
    if ($cookieFile) {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    }
    
    if ($method === 'POST' && $data) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    
    $headers = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);
    
    curl_close($ch);
    
    // Try different patterns for location header
    $location = null;
    if (preg_match('/^location:\s*(.+)$/mi', $headers, $matches)) {
        $location = trim($matches[1]);
    }
    
    return [
        'code' => $httpCode,
        'headers' => $headers,
        'body' => $body,
        'location' => $location
    ];
}

// Step 1: Get login page
echo "1. GETTING LOGIN PAGE\n";
$loginPageResponse = makeRequest($baseUrl . '/business/login', 'GET', null, $cookieFile);
echo "Response code: " . $loginPageResponse['code'] . "\n";

// Extract CSRF token
preg_match('/<meta name="csrf-token" content="([^"]+)"/', $loginPageResponse['body'], $csrfMatches);
$csrfToken = $csrfMatches[1] ?? null;

if ($csrfToken) {
    echo "CSRF token found: " . substr($csrfToken, 0, 8) . "...\n";
} else {
    echo "❌ CSRF token not found!\n";
    exit(1);
}

// Extract session cookie - check both possible names
preg_match('/askproai_session=([^;]+)/', $loginPageResponse['headers'], $sessionMatches1);
preg_match('/askproai_portal_session=([^;]+)/', $loginPageResponse['headers'], $sessionMatches2);
$sessionCookie = $sessionMatches1[1] ?? $sessionMatches2[1] ?? null;
echo "Session cookie: " . ($sessionCookie ? substr($sessionCookie, 0, 8) . "..." : "NOT FOUND") . "\n";

// Debug: Show all cookies
echo "All cookies in response:\n";
preg_match_all('/Set-Cookie: ([^;]+)/', $loginPageResponse['headers'], $allCookies);
foreach ($allCookies[1] ?? [] as $cookie) {
    echo "  - " . $cookie . "\n";
}

echo "\n2. SUBMITTING LOGIN FORM\n";

// Submit login form
$loginData = [
    'email' => 'demo@askproai.de',
    'password' => 'password',
    '_token' => $csrfToken
];

$loginResponse = makeRequest($baseUrl . '/business/login', 'POST', $loginData, $cookieFile);
echo "Response code: " . $loginResponse['code'] . "\n";

if ($loginResponse['code'] === 302) {
    echo "Redirect location: " . ($loginResponse['location'] ?: "EMPTY") . "\n";
    
    // Check for redirect loop
    $redirectCount = 0;
    $currentUrl = $loginResponse['location'];
    $visitedUrls = [];
    
    echo "\n3. FOLLOWING REDIRECTS\n";
    
    while ($redirectCount < 10 && $currentUrl) {
        if (in_array($currentUrl, $visitedUrls)) {
            echo "❌ REDIRECT LOOP DETECTED!\n";
            echo "Loop path: " . implode(' -> ', $visitedUrls) . " -> " . $currentUrl . "\n";
            break;
        }
        
        $visitedUrls[] = $currentUrl;
        
        // Make it absolute URL if relative
        if (strpos($currentUrl, 'http') !== 0) {
            $currentUrl = $baseUrl . $currentUrl;
        }
        
        echo "Following redirect #" . ($redirectCount + 1) . ": " . $currentUrl . "\n";
        
        $redirectResponse = makeRequest($currentUrl, 'GET', null, $cookieFile);
        echo "  Response code: " . $redirectResponse['code'] . "\n";
        
        if ($redirectResponse['code'] === 302 || $redirectResponse['code'] === 301) {
            $currentUrl = $redirectResponse['location'];
            $redirectCount++;
        } else {
            // Final destination reached
            echo "\n4. FINAL DESTINATION\n";
            echo "URL: " . $currentUrl . "\n";
            echo "Status: " . $redirectResponse['code'] . "\n";
            
            // Check if we're on the dashboard
            if (strpos($currentUrl, '/business/dashboard') !== false) {
                echo "✅ Successfully reached dashboard!\n";
            } elseif (strpos($currentUrl, '/business/login') !== false) {
                echo "❌ Redirected back to login page\n";
            } else {
                echo "⚠️  Ended up at unexpected location\n";
            }
            
            break;
        }
    }
    
    if ($redirectCount >= 10) {
        echo "❌ Too many redirects (exceeded limit of 10)\n";
    }
    
} elseif ($loginResponse['code'] === 200) {
    echo "⚠️  Got 200 response (stayed on login page)\n";
    
    // Check for error messages
    if (strpos($loginResponse['body'], 'Die angegebenen Zugangsdaten sind ungültig') !== false) {
        echo "Error: Invalid credentials message shown\n";
    }
} else {
    echo "❌ Unexpected response code: " . $loginResponse['code'] . "\n";
}

// Cleanup
unlink($cookieFile);

echo "\n=== TEST COMPLETE ===\n";