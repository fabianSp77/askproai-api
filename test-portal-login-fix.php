<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Bootstrap the application
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);
$kernel->terminate($request, $response);

echo "=== PORTAL LOGIN SESSION KEY FIX TEST ===\n\n";

// Step 1: Verify session key generation consistency
echo "1. CHECKING SESSION KEY GENERATION\n";

$guard = $app->make('auth')->guard('portal');
$expectedKey = $guard->getName();
$calculatedKey = 'login_portal_' . sha1(Illuminate\Auth\SessionGuard::class);

echo "Guard session key: $expectedKey\n";
echo "Calculated key: $calculatedKey\n";
echo "Keys match: " . ($expectedKey === $calculatedKey ? "YES ✅" : "NO ❌") . "\n\n";

// Step 2: Check all locations where session keys are referenced
echo "2. CHECKING MIDDLEWARE SESSION KEY USAGE\n";

$middlewareToCheck = [
    'SharePortalSession' => '\App\Http\Middleware\SharePortalSession',
    'FixPortalApiAuth' => '\App\Http\Middleware\FixPortalApiAuth',
    'ForcePortalSession' => '\App\Http\Middleware\ForcePortalSession',
    'PortalAuth' => '\App\Http\Middleware\PortalAuth',
];

foreach ($middlewareToCheck as $name => $class) {
    if (class_exists($class)) {
        echo "✅ $name middleware exists\n";
    } else {
        echo "❌ $name middleware missing\n";
    }
}

echo "\n3. SIMULATING LOGIN FLOW\n";

// Create a test request to login page
$loginGetRequest = Illuminate\Http\Request::create('/business/login', 'GET');
$loginGetResponse = $kernel->handle($loginGetRequest);
echo "Login page status: " . $loginGetResponse->getStatusCode() . "\n";

// Get CSRF token
$app->instance('request', $loginGetRequest);
$csrfToken = $app->make('session')->token();
echo "CSRF token generated: " . substr($csrfToken, 0, 8) . "...\n";

// Simulate login POST
echo "\n4. TESTING LOGIN POST\n";
$loginPostRequest = Illuminate\Http\Request::create('/business/login', 'POST', [
    'email' => 'demo@askproai.de',
    'password' => 'password',
    '_token' => $csrfToken,
]);

$loginPostRequest->headers->set('Accept', 'text/html');
$loginPostRequest->headers->set('Content-Type', 'application/x-www-form-urlencoded');
$loginPostRequest->setSession($app['session']);

try {
    $loginResponse = $kernel->handle($loginPostRequest);
    echo "Login response status: " . $loginResponse->getStatusCode() . "\n";
    
    if ($loginResponse->getStatusCode() === 302) {
        echo "Redirect location: " . $loginResponse->headers->get('location') . "\n";
    }
    
    // Check if user is authenticated
    $isAuthenticated = $app->make('auth')->guard('portal')->check();
    echo "User authenticated: " . ($isAuthenticated ? "YES ✅" : "NO ❌") . "\n";
    
    if ($isAuthenticated) {
        $user = $app->make('auth')->guard('portal')->user();
        echo "Authenticated user: " . $user->email . "\n";
        echo "User ID: " . $user->id . "\n";
        echo "Company ID: " . $user->company_id . "\n";
    }
    
} catch (Exception $e) {
    echo "Login error: " . $e->getMessage() . "\n";
}

echo "\n5. CHECKING SESSION DATA\n";
$session = $app->make('session');
$sessionData = $session->all();
echo "Session ID: " . $session->getId() . "\n";
echo "Session has portal auth key: " . (isset($sessionData[$expectedKey]) ? "YES ✅" : "NO ❌") . "\n";
echo "Session has portal_user_id: " . (isset($sessionData['portal_user_id']) ? "YES ✅" : "NO ❌") . "\n";
echo "Session has company_id: " . (isset($sessionData['company_id']) ? "YES ✅" : "NO ❌") . "\n";

// Show session keys
echo "\nAll session keys:\n";
foreach (array_keys($sessionData) as $key) {
    echo "  - $key\n";
}

echo "\n6. TESTING DASHBOARD ACCESS\n";
$dashboardRequest = Illuminate\Http\Request::create('/business/dashboard', 'GET');
$dashboardRequest->setSession($app['session']);

try {
    $dashboardResponse = $kernel->handle($dashboardRequest);
    echo "Dashboard response status: " . $dashboardResponse->getStatusCode() . "\n";
    
    if ($dashboardResponse->getStatusCode() === 302) {
        $location = $dashboardResponse->headers->get('location');
        echo "Redirect location: " . $location . "\n";
        
        if (str_contains($location, '/business/login')) {
            echo "⚠️  WARNING: Redirected back to login (possible session issue)\n";
        }
    } elseif ($dashboardResponse->getStatusCode() === 200) {
        echo "✅ Dashboard loaded successfully!\n";
    }
    
} catch (Exception $e) {
    echo "Dashboard error: " . $e->getMessage() . "\n";
}

echo "\n7. SESSION KEY VERIFICATION\n";
echo "Expected session key format: login_portal_" . sha1(Illuminate\Auth\SessionGuard::class) . "\n";
echo "Old incorrect format: login_portal_" . sha1(\App\Models\PortalUser::class) . "\n";

// Check if old keys exist
$oldKey = 'login_portal_' . sha1(\App\Models\PortalUser::class);
if (isset($sessionData[$oldKey])) {
    echo "⚠️  WARNING: Old session key still exists in session!\n";
}

echo "\n=== TEST COMPLETE ===\n";
echo "Summary:\n";
echo "- Session key consistency: " . ($expectedKey === $calculatedKey ? "PASS ✅" : "FAIL ❌") . "\n";
echo "- Authentication works: " . ($app->make('auth')->guard('portal')->check() ? "PASS ✅" : "FAIL ❌") . "\n";
echo "- Dashboard accessible: " . (isset($dashboardResponse) && $dashboardResponse->getStatusCode() !== 302 ? "PASS ✅" : "FAIL ❌") . "\n";

$app->terminate($loginPostRequest, $loginResponse ?? $loginGetResponse);