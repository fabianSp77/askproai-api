<?php
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

use Illuminate\Support\Facades\Auth;

echo "<h1>Portal Redirect Loop Debug</h1>";

// 1. Check Auth State
echo "<h2>1. Auth State</h2>";
$portalCheck = Auth::guard('portal')->check();
$portalUser = Auth::guard('portal')->user();
echo "<p>Portal Auth Check: " . ($portalCheck ? "✅ TRUE" : "❌ FALSE") . "</p>";
if ($portalUser) {
    echo "<p>User: {$portalUser->email} (ID: {$portalUser->id})</p>";
}

// 2. Session Data
echo "<h2>2. Session Data</h2>";
echo "<p>Session ID: " . session()->getId() . "</p>";
echo "<p>Session Driver: " . session()->getDefaultDriver() . "</p>";

// Check for auth session key
$sessionKey = 'login_portal_' . sha1(\Illuminate\Auth\SessionGuard::class);
echo "<p>Session Key: $sessionKey</p>";
echo "<p>Has Session Key: " . (session()->has($sessionKey) ? "YES" : "NO") . "</p>";
if (session()->has($sessionKey)) {
    echo "<p>Session User ID: " . session($sessionKey) . "</p>";
}

// 3. Route Info
echo "<h2>3. Routes</h2>";
echo "<p>Login Route: " . route('business.login') . "</p>";
echo "<p>Dashboard Route: " . route('business.dashboard') . "</p>";

// 4. Middleware Check
echo "<h2>4. Dashboard Route Middleware</h2>";
$route = app('router')->getRoutes()->match(
    app('request')->create('/business/dashboard', 'GET')
);
echo "<p>Middleware: " . implode(', ', $route->middleware()) . "</p>";

// 5. All Session Data
echo "<h2>5. All Session Data</h2>";
echo "<pre>";
print_r(session()->all());
echo "</pre>";

// 6. Test Auth Persistence
echo "<h2>6. Test Actions</h2>";
if (!$portalCheck) {
    echo '<p><a href="/business/login">Go to Login</a></p>';
    
    // Try to manually set auth
    $demoUser = \App\Models\PortalUser::where('email', 'demo@askproai.de')->first();
    if ($demoUser) {
        echo '<form method="POST" action="/business/login" style="margin-top: 20px;">';
        echo '<input type="hidden" name="_token" value="' . csrf_token() . '">';
        echo '<input type="hidden" name="email" value="demo@askproai.de">';
        echo '<input type="hidden" name="password" value="password">';
        echo '<button type="submit">Test Login as Demo User</button>';
        echo '</form>';
    }
} else {
    echo '<p>You are logged in!</p>';
    echo '<p><a href="/business/dashboard">Go to Dashboard</a></p>';
    echo '<form method="POST" action="/business/logout" style="display: inline;">';
    echo '<input type="hidden" name="_token" value="' . csrf_token() . '">';
    echo '<button type="submit">Logout</button>';
    echo '</form>';
}

// 7. Check Cookies
echo "<h2>7. Cookies</h2>";
echo "<pre>";
foreach ($_COOKIE as $name => $value) {
    echo "$name = " . substr($value, 0, 40) . "...\n";
}
echo "</pre>";
?>