<?php
// Bypass Filament login for testing
require_once __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);
$kernel->terminate($request, $response);

// Login demo user
$user = \App\Models\User::where('email', 'demo@askproai.de')->first();
if ($user) {
    Auth::login($user);
    session()->save();
    
    // Force session to persist
    $sessionKey = 'login_web_' . sha1('Illuminate\Auth\SessionGuard.web');
    session([$sessionKey => $user->id]);
    session()->save();
}

// Redirect to admin
header('Location: /admin');
exit;