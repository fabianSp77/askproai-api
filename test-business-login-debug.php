<?php
// Debug business portal login
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

// Bootstrap the application
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

echo "=== Business Portal Login Debug ===\n\n";

// Test 1: Check if demo user exists
$user = \App\Models\PortalUser::withoutGlobalScope(\App\Scopes\CompanyScope::class)
    ->where('email', 'demo@askproai.de')
    ->first();

echo "1. Demo user check:\n";
echo "   Found: " . ($user ? 'YES' : 'NO') . "\n";
if ($user) {
    echo "   ID: " . $user->id . "\n";
    echo "   Company ID: " . $user->company_id . "\n";
    echo "   Active: " . ($user->is_active ? 'YES' : 'NO') . "\n";
    echo "   Password valid: " . (\Hash::check('password', $user->password) ? 'YES' : 'NO') . "\n";
}

// Test 2: Check session configuration
echo "\n2. Session configuration:\n";
echo "   Driver: " . config('session.driver') . "\n";
echo "   Cookie name: " . config('session.cookie') . "\n";
echo "   Domain: " . config('session.domain') . "\n";
echo "   Same site: " . config('session.same_site') . "\n";

// Test 3: Check portal-specific config
echo "\n3. Portal session config:\n";
echo "   Portal cookie: " . env('PORTAL_SESSION_COOKIE', 'askproai_portal_session') . "\n";

// Test 4: Check guards
echo "\n4. Auth guards:\n";
$guards = config('auth.guards');
echo "   Portal guard exists: " . (isset($guards['portal']) ? 'YES' : 'NO') . "\n";
if (isset($guards['portal'])) {
    echo "   Driver: " . $guards['portal']['driver'] . "\n";
    echo "   Provider: " . $guards['portal']['provider'] . "\n";
}

// Test 5: Check provider
echo "\n5. Auth providers:\n";
$providers = config('auth.providers');
echo "   Portal users provider exists: " . (isset($providers['portal_users']) ? 'YES' : 'NO') . "\n";
if (isset($providers['portal_users'])) {
    echo "   Driver: " . $providers['portal_users']['driver'] . "\n";
    echo "   Model: " . $providers['portal_users']['model'] . "\n";
}

// Test 6: Try actual authentication
echo "\n6. Authentication test:\n";
$attempt = auth()->guard('portal')->attempt([
    'email' => 'demo@askproai.de',
    'password' => 'password'
]);
echo "   Auth attempt: " . ($attempt ? 'SUCCESS' : 'FAILED') . "\n";
if ($attempt) {
    echo "   User ID: " . auth()->guard('portal')->id() . "\n";
    echo "   Session ID: " . session()->getId() . "\n";
}