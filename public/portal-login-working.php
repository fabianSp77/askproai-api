<?php
// Working portal login solution
require_once __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

// Boot the application
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();

// Create a proper request context
$request->setLaravelSession($app['session.store']);
$response = $kernel->handle($request);

// Now we have proper Laravel context
echo "=== Portal Login Fix ===\n\n";

// Clear any existing sessions
$app['auth']->guard('web')->logout();
$app['auth']->guard('portal')->logout();
$app['session']->flush();
$app['session']->forget('errors');
$app['session']->forget('_old_input');
$app['session']->forget('_flash');

// Find the demo user
$user = \App\Models\PortalUser::withoutGlobalScopes()
    ->where('email', 'demo@askproai.de')
    ->where('is_active', 1)
    ->first();

if (!$user) {
    die("Error: Demo user not found!");
}

echo "Found user: " . $user->email . " (ID: " . $user->id . ")\n";
echo "Logging in...\n";

// Login the user using the portal guard
$app['auth']->guard('portal')->login($user);

// Save the session
$app['session']->save();

echo "Login successful!\n";
echo "Session ID: " . $app['session']->getId() . "\n";
echo "Authenticated: " . ($app['auth']->guard('portal')->check() ? 'YES' : 'NO') . "\n";

// Regenerate session for security
$app['session']->regenerate();

// Set a success message
$app['session']->flash('success', 'Successfully logged in as demo@askproai.de');

// Save session again
$app['session']->save();

// Terminate the kernel properly
$kernel->terminate($request, $response);

// Redirect to dashboard
header('Location: /business/dashboard');
exit;