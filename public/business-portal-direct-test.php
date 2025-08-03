<?php
// Direct PHP test to verify login works
require_once __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

// Test 1: Direct login using Laravel auth
echo "=== Direct Business Portal Login Test ===\n\n";

// Clear any existing sessions
$app['auth']->guard('web')->logout();
$app['auth']->guard('portal')->logout();
$app['session']->flush();

echo "1. Testing portal guard login:\n";
$user = \App\Models\PortalUser::find(41); // demo user
if ($user) {
    $app['auth']->guard('portal')->login($user);
    $app['session']->save();
    
    echo "   ✅ Logged in user to portal guard\n";
    echo "   Portal auth check: " . ($app['auth']->guard('portal')->check() ? 'YES' : 'NO') . "\n";
    echo "   Portal user email: " . $app['auth']->guard('portal')->user()->email . "\n";
    echo "   Session ID: " . $app['session']->getId() . "\n";
    echo "   Session keys: " . implode(', ', array_keys($app['session']->all())) . "\n\n";
} else {
    echo "   ❌ Demo user not found\n";
}

// Test 2: Check if session persists
echo "2. Testing session persistence:\n";
$sessionId = $app['session']->getId();
$app['session']->save();
$app['session']->regenerate(false); // Simulate redirect

echo "   New session ID: " . $app['session']->getId() . "\n";
echo "   Portal still authenticated: " . ($app['auth']->guard('portal')->check() ? 'YES' : 'NO') . "\n\n";

// Test 3: Check business portal route access
echo "3. Testing route access:\n";
$request2 = \Illuminate\Http\Request::create('/business/dashboard', 'GET');
$request2->setLaravelSession($app['session']);

try {
    $response2 = $kernel->handle($request2);
    echo "   Dashboard response: " . $response2->getStatusCode() . "\n";
    if ($response2->getStatusCode() === 302) {
        echo "   Redirect to: " . $response2->headers->get('location') . "\n";
    }
} catch (\Exception $e) {
    echo "   Error: " . $e->getMessage() . "\n";
}

// Test 4: Check API endpoint
echo "\n4. Testing API auth check:\n";
$request3 = \Illuminate\Http\Request::create('/business/api/auth/check', 'GET', [], [], [], [
    'HTTP_ACCEPT' => 'application/json',
    'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'
]);
$request3->setLaravelSession($app['session']);

try {
    $response3 = $kernel->handle($request3);
    echo "   API response: " . $response3->getStatusCode() . "\n";
    echo "   Content: " . $response3->getContent() . "\n";
} catch (\Exception $e) {
    echo "   Error: " . $e->getMessage() . "\n";
}

$kernel->terminate($request, $response);