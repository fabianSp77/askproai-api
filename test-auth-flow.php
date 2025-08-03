<?php
require_once __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

echo "=== Testing Auth Flow Step by Step ===\n\n";

// Step 1: Clear any existing auth
auth()->guard('web')->logout();
auth()->guard('portal')->logout();
session()->flush();

echo "1. All sessions cleared\n";

// Step 2: Get the demo user
$user = \App\Models\PortalUser::withoutGlobalScope(\App\Scopes\CompanyScope::class)
    ->where('email', 'demo@askproai.de')
    ->first();

echo "2. User found: " . ($user ? 'YES' : 'NO') . "\n";

// Step 3: Login the user
auth()->guard('portal')->login($user);
session()->save();

echo "3. User logged in via guard\n";
echo "   - Auth check: " . (auth()->guard('portal')->check() ? 'YES' : 'NO') . "\n";
echo "   - User ID: " . auth()->guard('portal')->id() . "\n";
echo "   - Session ID: " . session()->getId() . "\n";

// Step 4: Check session keys
$sessionKey = 'login_portal_' . sha1(\App\Models\PortalUser::class);
echo "\n4. Session keys:\n";
echo "   - Expected auth key: $sessionKey\n";
echo "   - Has auth key: " . (session()->has($sessionKey) ? 'YES' : 'NO') . "\n";
echo "   - Auth key value: " . session($sessionKey) . "\n";
echo "   - All keys: " . implode(', ', array_keys(session()->all())) . "\n";

// Step 5: Test auth middleware
echo "\n5. Testing if PortalAuth middleware would pass:\n";
$middleware = new \App\Http\Middleware\PortalAuth();
$testRequest = \Illuminate\Http\Request::create('/business/dashboard');
$testRequest->setLaravelSession(session());

$passed = false;
$result = $middleware->handle($testRequest, function($req) use (&$passed) {
    $passed = true;
    return response('OK');
});

echo "   - Middleware passed: " . ($passed ? 'YES' : 'NO') . "\n";
if (!$passed && $result instanceof \Illuminate\Http\RedirectResponse) {
    echo "   - Redirect to: " . $result->getTargetUrl() . "\n";
}

// Step 6: Check what CustomSessionGuard stores
echo "\n6. Checking CustomSessionGuard behavior:\n";
$guard = auth()->guard('portal');
if ($guard instanceof \App\Auth\CustomSessionGuard) {
    echo "   - Using CustomSessionGuard: YES\n";
} else {
    echo "   - Using standard guard\n";
}

$kernel->terminate($request, $response);