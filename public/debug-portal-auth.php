<?php
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

use Illuminate\Support\Facades\Auth;

echo "<h1>Portal Auth Debug</h1>";

// Check portal auth
$portalAuth = Auth::guard('portal')->check();
$portalUser = Auth::guard('portal')->user();

echo "<h2>Portal Authentication Status</h2>";
echo "<p>Authenticated: " . ($portalAuth ? "✅ YES" : "❌ NO") . "</p>";

if ($portalUser) {
    echo "<p>User ID: " . $portalUser->id . "</p>";
    echo "<p>Email: " . $portalUser->email . "</p>";
    echo "<p>Company ID: " . $portalUser->company_id . "</p>";
}

echo "<h2>Session Information</h2>";
echo "<p>Session ID: " . session()->getId() . "</p>";
echo "<p>Session Name: " . session()->getName() . "</p>";
echo "<p>Portal User ID in Session: " . session('portal_user_id') . "</p>";
echo "<p>Company ID in Session: " . session('company_id') . "</p>";

// Check session key
$sessionKey = 'login_portal_' . sha1(\Illuminate\Auth\SessionGuard::class);
echo "<p>Session Auth Key: " . $sessionKey . "</p>";
echo "<p>Session Has Auth Key: " . (session()->has($sessionKey) ? "YES" : "NO") . "</p>";
if (session()->has($sessionKey)) {
    echo "<p>Session Auth Value: " . session($sessionKey) . "</p>";
}

echo "<h2>All Session Data</h2>";
echo "<pre>";
print_r(session()->all());
echo "</pre>";

echo "<h2>Cookies</h2>";
echo "<pre>";
foreach ($_COOKIE as $name => $value) {
    echo "$name = " . substr($value, 0, 40) . "...\n";
}
echo "</pre>";

echo "<h2>Test Links</h2>";
echo '<p><a href="/business/dashboard">Go to Dashboard</a></p>';
echo '<p><a href="/business/login">Go to Login</a></p>';

if ($portalAuth) {
    echo '<form method="POST" action="/business/logout" style="display: inline;">';
    echo '<input type="hidden" name="_token" value="' . csrf_token() . '">';
    echo '<button type="submit">Logout</button>';
    echo '</form>';
}
?>