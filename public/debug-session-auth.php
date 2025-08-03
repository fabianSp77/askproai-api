<?php
require_once __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

echo "=== Session and Auth Debug ===\n\n";

// Check all session data
echo "1. Session Data:\n";
echo "   Session ID: " . session()->getId() . "\n";
echo "   Session Name: " . session()->getName() . "\n";
echo "   All Keys: " . implode(', ', array_keys(session()->all())) . "\n\n";

// Check each guard
echo "2. Auth Guards:\n";
$guards = ['web', 'portal'];
foreach ($guards as $guardName) {
    $guard = auth()->guard($guardName);
    echo "   Guard '$guardName':\n";
    echo "     - Authenticated: " . ($guard->check() ? 'YES' : 'NO') . "\n";
    echo "     - Session Key: " . $guard->getName() . "\n";
    if ($guard->check()) {
        echo "     - User: " . $guard->user()->email . "\n";
    }
    echo "\n";
}

// Check session config
echo "3. Session Configuration:\n";
echo "   Default Guard: " . config('auth.defaults.guard') . "\n";
echo "   Session Driver: " . config('session.driver') . "\n";
echo "   Session Cookie: " . config('session.cookie') . "\n";
echo "   Portal Session Cookie: " . config('session_portal.cookie') . "\n\n";

// Check cookies
echo "4. Cookies:\n";
foreach ($_COOKIE as $name => $value) {
    if (strpos($name, 'session') !== false || strpos($name, 'XSRF') !== false) {
        echo "   $name: " . substr($value, 0, 40) . "...\n";
    }
}

// Clear all auth and try portal login
echo "\n5. Testing Portal Login:\n";
auth()->guard('web')->logout();
auth()->guard('portal')->logout();
session()->flush();

$user = \App\Models\PortalUser::find(41); // demo user
if ($user) {
    auth()->guard('portal')->login($user);
    session()->save();
    
    echo "   Logged in user to portal guard\n";
    echo "   Portal auth check: " . (auth()->guard('portal')->check() ? 'YES' : 'NO') . "\n";
    echo "   Session keys after login: " . implode(', ', array_keys(session()->all())) . "\n";
}

$kernel->terminate($request, $response);