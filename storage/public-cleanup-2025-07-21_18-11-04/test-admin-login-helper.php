<?php
// Admin login helper for testing
require_once __DIR__ . '/../vendor/autoload.php';

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

// Bootstrap Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Create request
$request = Illuminate\Http\Request::create('/admin', 'GET');
$kernel->handle($request);

// Start session
Session::start();

// Find and login admin user
$user = User::where('email', 'demo@askproai.de')->first();

if (!$user) {
    die('Demo user not found');
}

// Login the user
Auth::guard('web')->login($user);

// Set Filament session
session(['filament_admin_auth' => [
    'id' => $user->id,
    'name' => $user->name,
    'email' => $user->email
]]);

// Save session
Session::save();

// Get session ID
$sessionId = Session::getId();
$sessionName = config('session.cookie', 'askproai_admin_session');

// Set cookie
setcookie(
    $sessionName,
    $sessionId,
    time() + (120 * 60), // 2 hours
    '/',
    '.askproai.de',
    true, // secure
    true  // httponly
);

// Redirect to admin
header('Location: /admin/calls');
exit;