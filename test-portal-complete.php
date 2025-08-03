#!/usr/bin/env php
<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

echo "=== COMPREHENSIVE PORTAL LOGIN TEST ===\n\n";

$baseUrl = 'https://api.askproai.de';

// Step 1: Get login page and CSRF token
echo "1. GETTING LOGIN PAGE\n";
$loginPage = Http::withOptions(['verify' => false])->get($baseUrl . '/business/login');
$csrfToken = null;

if (preg_match('/<meta name="csrf-token" content="([^"]+)"/', $loginPage->body(), $matches)) {
    $csrfToken = $matches[1];
    echo "CSRF token found: " . substr($csrfToken, 0, 8) . "...\n";
} else {
    echo "❌ CSRF token not found!\n";
    exit(1);
}

// Get cookies from login page
$cookies = $loginPage->cookies();
$sessionCookie = null;
foreach ($cookies as $cookie) {
    if (str_contains($cookie->getName(), 'session')) {
        $sessionCookie = $cookie;
        echo "Session cookie: " . $cookie->getName() . " = " . substr($cookie->getValue(), 0, 8) . "...\n";
    }
}

echo "\n2. SUBMITTING LOGIN\n";

// Create a persistent HTTP client with cookies
$client = Http::withOptions([
    'verify' => false,
    'cookies' => true,
])->withCookies([
    $sessionCookie->getName() => $sessionCookie->getValue()
], $sessionCookie->getDomain());

// Submit login
$loginResponse = $client->asForm()->post($baseUrl . '/business/login', [
    'email' => 'demo@askproai.de',
    'password' => 'password',
    '_token' => $csrfToken
]);

echo "Login response status: " . $loginResponse->status() . "\n";

if ($loginResponse->status() === 302) {
    $location = $loginResponse->header('Location');
    echo "Redirect to: " . $location . "\n";
}

// Get updated cookies after login
$loginCookies = $loginResponse->cookies();
foreach ($loginCookies as $cookie) {
    if (str_contains($cookie->getName(), 'session')) {
        echo "Updated session cookie: " . $cookie->getName() . " = " . substr($cookie->getValue(), 0, 8) . "...\n";
    }
}

echo "\n3. CHECKING AUTH STATUS\n";

// Check auth status
$authCheck = $client->get($baseUrl . '/business/auth-test');
echo "Auth check response: " . $authCheck->body() . "\n";

$authData = json_decode($authCheck->body(), true);
if ($authData['portal_check']) {
    echo "✅ Authentication successful!\n";
    echo "User: " . json_encode($authData['portal_user']) . "\n";
} else {
    echo "❌ Authentication failed\n";
}

echo "\n4. ACCESSING DASHBOARD\n";

// Try to access dashboard
$dashboardResponse = $client->get($baseUrl . '/business/dashboard');
echo "Dashboard response status: " . $dashboardResponse->status() . "\n";

if ($dashboardResponse->status() === 200) {
    echo "✅ Dashboard loaded successfully!\n";
} elseif ($dashboardResponse->status() === 302) {
    echo "❌ Redirected to: " . $dashboardResponse->header('Location') . "\n";
}

echo "\n5. DIRECT ARTISAN TEST\n";

// Test direct authentication
\Illuminate\Support\Facades\Auth::guard('portal')->attempt([
    'email' => 'demo@askproai.de',
    'password' => 'password'
]);

if (\Illuminate\Support\Facades\Auth::guard('portal')->check()) {
    echo "✅ Direct auth successful\n";
    $user = \Illuminate\Support\Facades\Auth::guard('portal')->user();
    echo "User ID: " . $user->id . "\n";
    
    // Check session
    $guard = \Illuminate\Support\Facades\Auth::guard('portal');
    $sessionKey = $guard->getName();
    echo "Session key: " . $sessionKey . "\n";
    echo "Session has key: " . (session()->has($sessionKey) ? 'YES' : 'NO') . "\n";
    echo "Session value: " . session($sessionKey) . "\n";
} else {
    echo "❌ Direct auth failed\n";
}

echo "\n=== TEST COMPLETE ===\n";