<?php
require_once __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

echo "=== Portal Auth Fix Test ===\n\n";

// 1. Test session key generation
echo "1. Session Key Generation:\n";
$webGuard = auth()->guard('web');
$portalGuard = auth()->guard('portal');

echo "   Web Guard Key: " . $webGuard->getName() . "\n";
echo "   Portal Guard Key: " . $portalGuard->getName() . "\n";
echo "   Expected Portal Key: login_portal_" . sha1(\Illuminate\Auth\SessionGuard::class) . "\n\n";

// 2. Test portal login
echo "2. Testing Portal Login:\n";
auth()->guard('web')->logout();
auth()->guard('portal')->logout();
session()->flush();

$user = \App\Models\PortalUser::find(41); // demo user
if ($user) {
    auth()->guard('portal')->login($user);
    session()->save();
    
    echo "   Logged in user to portal guard\n";
    echo "   Portal auth check: " . (auth()->guard('portal')->check() ? 'YES' : 'NO') . "\n";
    echo "   Web auth check: " . (auth()->guard('web')->check() ? 'YES' : 'NO') . "\n";
    echo "   Portal user ID: " . (auth()->guard('portal')->id() ?? 'null') . "\n";
    echo "   Session keys: " . implode(', ', array_keys(session()->all())) . "\n\n";
    
    // Check for correct session key
    $expectedKey = 'login_portal_' . sha1(\Illuminate\Auth\SessionGuard::class);
    if (session()->has($expectedKey)) {
        echo "   ✅ Correct portal session key found!\n";
    } else {
        echo "   ❌ Portal session key not found\n";
    }
}

// 3. Test session persistence
echo "\n3. Testing Session Persistence:\n";
$sessionId = session()->getId();
echo "   Current session ID: $sessionId\n";

// Save and regenerate to simulate redirect
session()->save();
session()->regenerate(false);

echo "   New session ID: " . session()->getId() . "\n";
echo "   Portal still authenticated: " . (auth()->guard('portal')->check() ? 'YES' : 'NO') . "\n";

$kernel->terminate($request, $response);