<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Test authentication flow step by step
echo "=== BUSINESS PORTAL AUTHENTICATION FLOW ANALYSIS ===\n\n";

// Step 1: Check login form access
echo "1. TESTING LOGIN FORM ACCESS\n";
$request = Illuminate\Http\Request::create('/business/login', 'GET');
$response = $kernel->handle($request);
echo "Status: " . $response->getStatusCode() . "\n";
echo "Location header: " . ($response->headers->get('location') ?? 'none') . "\n";
echo "Session ID: " . session()->getId() . "\n\n";

// Step 2: Test POST login
echo "2. TESTING LOGIN POST\n";
$loginRequest = Illuminate\Http\Request::create('/business/login', 'POST', [
    'email' => 'demo@askproai.de',
    'password' => 'password',
    '_token' => csrf_token(),
]);

// Add required headers
$loginRequest->headers->set('Accept', 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8');
$loginRequest->headers->set('Content-Type', 'application/x-www-form-urlencoded');

echo "Login request URL: " . $loginRequest->fullUrl() . "\n";
echo "Login request method: " . $loginRequest->method() . "\n";
echo "CSRF Token: " . csrf_token() . "\n";

try {
    $loginResponse = $kernel->handle($loginRequest);
    echo "Login response status: " . $loginResponse->getStatusCode() . "\n";
    echo "Login response location: " . ($loginResponse->headers->get('location') ?? 'none') . "\n";
    
    // Check session state after login
    echo "Auth guard check: " . (Auth::guard('portal')->check() ? 'YES' : 'NO') . "\n";
    echo "Session user ID: " . session('portal_user_id') . "\n";
    echo "Company ID: " . session('company_id') . "\n";
    
} catch (Exception $e) {
    echo "Login failed with exception: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n3. TESTING DASHBOARD ACCESS AFTER LOGIN\n";

// Step 3: Test dashboard access
$dashboardRequest = Illuminate\Http\Request::create('/business/dashboard', 'GET');
$dashboardRequest->setSession(app('session'));

try {
    $dashboardResponse = $kernel->handle($dashboardRequest);
    echo "Dashboard response status: " . $dashboardResponse->getStatusCode() . "\n";
    echo "Dashboard response location: " . ($dashboardResponse->headers->get('location') ?? 'none') . "\n";
    
    // Check for redirect loop pattern
    $location = $dashboardResponse->headers->get('location');
    if ($location && str_contains($location, '/business/login')) {
        echo "*** REDIRECT LOOP DETECTED: Dashboard -> Login ***\n";
    }
    
} catch (Exception $e) {
    echo "Dashboard access failed: " . $e->getMessage() . "\n";
}

echo "\n4. SESSION STATE ANALYSIS\n";
echo "Current session data:\n";
print_r(session()->all());

echo "\n5. AUTH GUARD STATE\n";
echo "Portal guard check: " . (Auth::guard('portal')->check() ? 'YES' : 'NO') . "\n";
echo "Portal guard user: " . (Auth::guard('portal')->user() ? Auth::guard('portal')->user()->email : 'null') . "\n";

$app->terminate($request, $response);
