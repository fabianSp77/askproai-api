<?php
require_once __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

echo "=== Session Conflict Debug ===\n\n";

// Check all sessions and guards
echo "1. Current Auth Guards Status:\n";
$guards = ['web', 'portal', 'admin'];
foreach ($guards as $guard) {
    if (config("auth.guards.$guard")) {
        $check = auth()->guard($guard)->check();
        $user = $check ? auth()->guard($guard)->user() : null;
        echo "   - $guard: " . ($check ? "AUTHENTICATED" : "not authenticated") . "\n";
        if ($user) {
            echo "     User: " . ($user->email ?? 'N/A') . " (ID: " . ($user->id ?? 'N/A') . ")\n";
        }
    }
}

echo "\n2. Session Configuration:\n";
echo "   - Main session cookie: " . config('session.cookie') . "\n";
echo "   - Portal session cookie: " . config('session_portal.cookie') . "\n";
echo "   - Session domain: " . config('session.domain') . "\n";
echo "   - Session driver: " . config('session.driver') . "\n";

echo "\n3. Current Session Data:\n";
$sessionKeys = array_keys(session()->all());
echo "   Session keys: " . implode(', ', $sessionKeys) . "\n";

// Check for specific auth keys
$authKeys = [
    'login_web_' . sha1(\App\Models\User::class),
    'login_portal_' . sha1(\App\Models\PortalUser::class),
    'portal_user_id',
    'company_id',
    '_token'
];

foreach ($authKeys as $key) {
    if (session()->has($key)) {
        $value = session($key);
        if (is_scalar($value)) {
            echo "   - $key: " . (strlen($value) > 50 ? substr($value, 0, 50) . '...' : $value) . "\n";
        } else {
            echo "   - $key: [" . gettype($value) . "]\n";
        }
    }
}

echo "\n4. Cookies Present:\n";
$cookies = $_COOKIE;
foreach ($cookies as $name => $value) {
    echo "   - $name: " . (strlen($value) > 50 ? substr($value, 0, 50) . '...' : $value) . "\n";
}

// Test if we can authenticate manually
echo "\n5. Manual Portal Authentication Test:\n";
$user = \App\Models\PortalUser::withoutGlobalScope(\App\Scopes\CompanyScope::class)
    ->where('email', 'demo@askproai.de')
    ->first();

if ($user) {
    echo "   User found: {$user->email}\n";
    
    // Try to login
    auth()->guard('portal')->logout(); // Clear any existing
    $attempt = auth()->guard('portal')->attempt([
        'email' => 'demo@askproai.de',
        'password' => 'password'
    ]);
    
    echo "   Login attempt: " . ($attempt ? "SUCCESS" : "FAILED") . "\n";
    
    if ($attempt) {
        echo "   Session ID after login: " . session()->getId() . "\n";
        echo "   Auth check after login: " . (auth()->guard('portal')->check() ? "YES" : "NO") . "\n";
        
        // Force session save
        session()->save();
        
        echo "   Session saved. Auth check again: " . (auth()->guard('portal')->check() ? "YES" : "NO") . "\n";
    }
}

$kernel->terminate($request, $response);