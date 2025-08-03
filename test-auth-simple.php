<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

echo "=== SIMPLIFIED AUTH FLOW TEST ===\n\n";

// Step 1: Login
echo "1. POST to /business/login\n";
$loginRequest = Illuminate\Http\Request::create('/business/login', 'POST', [
    'email' => 'demo@askproai.de',
    'password' => 'password',
    '_token' => csrf_token(),
]);

$loginResponse = $kernel->handle($loginRequest);
echo "Login Status: " . $loginResponse->getStatusCode() . "\n";
echo "Login Location: " . ($loginResponse->headers->get('location') ?? 'none') . "\n";
echo "Session after login: " . session()->getId() . "\n";
echo "Auth check after login: " . (Auth::guard('portal')->check() ? 'YES' : 'NO') . "\n\n";

// Step 2: Access dashboard using the SAME session
echo "2. GET /business/dashboard (same session)\n";
$sessionId = session()->getId();
$dashboardRequest = Illuminate\Http\Request::create('/business/dashboard', 'GET');

// Manually copy session data to new request
$sessionData = session()->all();
foreach ($sessionData as $key => $value) {
    session()->put($key, $value);
}

$dashboardResponse = $kernel->handle($dashboardRequest);
echo "Dashboard Status: " . $dashboardResponse->getStatusCode() . "\n";
echo "Dashboard Location: " . ($dashboardResponse->headers->get('location') ?? 'none') . "\n";
echo "Auth check during dashboard: " . (Auth::guard('portal')->check() ? 'YES' : 'NO') . "\n";

// Check for redirect loop
$location = $dashboardResponse->headers->get('location');
if ($location && str_contains($location, '/business/login')) {
    echo "*** REDIRECT LOOP DETECTED ***\n";
    echo "Dashboard is redirecting back to login despite successful authentication\n";
}

echo "\nSession data:\n";
print_r(session()->all());

$app->terminate($loginRequest, $loginResponse);
