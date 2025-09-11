<?php
// Test authenticated admin access with real credentials
$cookieJar = tempnam(sys_get_temp_dir(), 'cookies');

// Login
$loginData = [
    'email' => 'admin@askproai.de',
    'password' => 'password',
    '_token' => '' // Will be extracted from form
];

// First, get the login form to extract CSRF token
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => 'https://api.askproai.de/admin/login',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_COOKIEJAR => $cookieJar,
    CURLOPT_COOKIEFILE => $cookieJar,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
]);

$loginPage = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

echo "Login page HTTP code: $httpCode\n";

// Extract CSRF token
if (preg_match('/<meta name="csrf-token" content="([^"]+)"/', $loginPage, $matches)) {
    $loginData['_token'] = $matches[1];
    echo "CSRF token extracted: " . $matches[1] . "\n";
} elseif (preg_match('/<input[^>]*name="_token"[^>]*value="([^"]+)"/', $loginPage, $matches)) {
    $loginData['_token'] = $matches[1];
    echo "CSRF token from input: " . $matches[1] . "\n";
} else {
    echo "No CSRF token found in login page\n";
}

// Attempt login
curl_setopt_array($ch, [
    CURLOPT_URL => 'https://api.askproai.de/admin/login',
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query($loginData),
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/x-www-form-urlencoded',
        'X-CSRF-TOKEN: ' . $loginData['_token']
    ]
]);

$loginResponse = curl_exec($ch);
$loginHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

echo "Login response HTTP code: $loginHttpCode\n";

// Now try to access the dashboard
curl_setopt_array($ch, [
    CURLOPT_URL => 'https://api.askproai.de/admin/dashboard',
    CURLOPT_POST => false,
    CURLOPT_POSTFIELDS => null,
    CURLOPT_HTTPHEADER => []
]);

$dashboardResponse = curl_exec($ch);
$dashboardHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

echo "Dashboard HTTP code: $dashboardHttpCode\n";

// Check if we're still seeing login page or actual dashboard
if (strpos($dashboardResponse, 'Melden Sie sich an') !== false) {
    echo "STILL ON LOGIN PAGE - Authentication failed\n";
} elseif (strpos($dashboardResponse, 'Dashboard') !== false) {
    echo "SUCCESS - Reached dashboard\n";
    
    // Check for navigation elements
    if (strpos($dashboardResponse, 'sidebar') !== false) {
        echo "✓ Sidebar detected in HTML\n";
    }
    if (strpos($dashboardResponse, 'navigation') !== false) {
        echo "✓ Navigation detected in HTML\n";
    }
    
    // Check for specific menu items
    $menuItems = ['Termine', 'Anrufe', 'Kunden', 'Unternehmen'];
    foreach ($menuItems as $item) {
        if (strpos($dashboardResponse, $item) !== false) {
            echo "✓ Menu item '$item' found\n";
        } else {
            echo "✗ Menu item '$item' missing\n";
        }
    }
} else {
    echo "UNKNOWN STATE - Not login page, not dashboard\n";
    echo "Response length: " . strlen($dashboardResponse) . " chars\n";
}

curl_close($ch);
unlink($cookieJar);
