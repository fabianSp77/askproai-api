<?php
/**
 * Direct Admin Test - Force login and go directly to admin
 */

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

use Illuminate\Support\Facades\Auth;

// Get user
$user = \App\Models\User::where('email', 'demo@askproai.de')->first();

if (!$user) {
    die('Demo user not found!');
}

// Force login
Auth::logout();
session()->flush();
session()->regenerate();

Auth::login($user, true);
session()->save();

// Check if it worked
if (Auth::check()) {
    echo '<h1>✅ Login Successful!</h1>';
    echo '<p>Logged in as: ' . Auth::user()->email . '</p>';
    echo '<p>Session ID: ' . session()->getId() . '</p>';
    echo '<p>Redirecting to admin in 2 seconds...</p>';
    echo '<meta http-equiv="refresh" content="2;url=/admin">';
} else {
    echo '<h1>❌ Login Failed!</h1>';
    echo '<p>Auth::check() returned false</p>';
}

// Debug info
echo '<hr>';
echo '<h2>Debug Info:</h2>';
echo '<pre>';
echo 'Session Driver: ' . config('session.driver') . "\n";
echo 'Session Cookie: ' . config('session.cookie') . "\n";
echo 'Cookie Present: ' . (isset($_COOKIE[config('session.cookie')]) ? 'Yes' : 'No') . "\n";
echo 'Session Data: ' . print_r(session()->all(), true);
echo '</pre>';
?>