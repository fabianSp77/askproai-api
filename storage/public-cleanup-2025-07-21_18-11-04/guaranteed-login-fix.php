<?php
/**
 * Guaranteed Login Fix
 * 
 * This bypasses all complexity and directly sets the session
 */

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request = Illuminate\Http\Request::capture());

use Illuminate\Support\Facades\Auth;

// Get user
$user = \App\Models\User::where('email', 'demo@askproai.de')->first();
if (!$user) {
    die('Demo user not found!');
}

// Clear any existing auth
Auth::logout();
$session = app('session.store');
$session->flush();
$session->regenerate();

// Get the correct session key for our CustomSessionGuard
$guard = Auth::guard('web');
$reflection = new ReflectionMethod($guard, 'getName');
$reflection->setAccessible(true);
$sessionKey = $reflection->invoke($guard);

// Login using Auth facade
Auth::login($user, true);

// ALSO manually ensure session has the data
$session->put($sessionKey, $user->id);
$session->put('password_hash_web', $user->password);

// Force save multiple times to ensure it sticks
$session->save();
$session->save();

// Set the user on the guard directly
$guard->setUser($user);

// One more save for good measure
$session->save();

// Verify it worked
if (Auth::check()) {
    echo '<h1 style="color: green;">✅ Login Successful!</h1>';
    echo '<p>Logged in as: ' . Auth::user()->email . '</p>';
    echo '<p>Redirecting to admin panel in 2 seconds...</p>';
    echo '<script>setTimeout(() => window.location.href = "/admin", 2000);</script>';
} else {
    echo '<h1 style="color: red;">❌ Login Failed</h1>';
    echo '<p>Debug info:</p>';
    echo '<pre>';
    echo 'Session Key: ' . $sessionKey . "\n";
    echo 'Session ID: ' . $session->getId() . "\n";
    echo 'Session Data: ' . print_r($session->all(), true);
    echo '</pre>';
}
?>