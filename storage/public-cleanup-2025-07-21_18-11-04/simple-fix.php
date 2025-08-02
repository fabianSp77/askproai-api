<?php
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request = Illuminate\Http\Request::capture());

// Get demo user
$user = \App\Models\User::where('email', 'demo@askproai.de')->first();
if (!$user) die('User not found');

// Get the correct session key dynamically
$guard = Auth::guard('web');
$getName = new ReflectionMethod($guard, 'getName');
$getName->setAccessible(true);
$sessionKey = $getName->invoke($guard);

// Set session manually
$session = app('session.store');
$session->put($sessionKey, $user->id);
$session->put('password_hash_web', $user->password);
$session->save();

// Also set on guard
$guard->setUser($user);

// Redirect
header('Location: /admin');
exit;
?>