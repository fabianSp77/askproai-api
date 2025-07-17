<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

echo "\n╔══════════════════════════════════════════════════════════════╗\n";
echo "║           COMPLETE AUTHENTICATION DIAGNOSIS                  ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

// 1. Environment Check
echo "1. ENVIRONMENT CONFIGURATION\n";
echo str_repeat("-", 60) . "\n";
echo "   APP_URL: " . env('APP_URL') . "\n";
echo "   APP_ENV: " . env('APP_ENV') . "\n";
echo "   SESSION_DRIVER: " . env('SESSION_DRIVER') . "\n";
echo "   SESSION_DOMAIN: " . env('SESSION_DOMAIN') . "\n";
echo "   SESSION_SECURE_COOKIE: " . (env('SESSION_SECURE_COOKIE') ? 'true' : 'false') . "\n";
echo "   SESSION_SAME_SITE: " . env('SESSION_SAME_SITE') . "\n";
echo "   SESSION_LIFETIME: " . env('SESSION_LIFETIME') . " minutes\n\n";

// 2. Configuration Check
echo "2. RUNTIME CONFIGURATION\n";
echo str_repeat("-", 60) . "\n";
echo "   session.domain: " . config('session.domain') . "\n";
echo "   session.driver: " . config('session.driver') . "\n";
echo "   session.cookie: " . config('session.cookie') . "\n";
echo "   session.secure: " . (config('session.secure') ? 'true' : 'false') . "\n";
echo "   session.same_site: " . config('session.same_site') . "\n";
echo "   auth.defaults.guard: " . config('auth.defaults.guard') . "\n\n";

// 3. Database Check
echo "3. DATABASE STATUS\n";
echo str_repeat("-", 60) . "\n";
try {
    $sessionCount = DB::table('sessions')->count();
    $portalSessionCount = DB::table('portal_sessions')->count();
    echo "   ✓ Database connection: OK\n";
    echo "   Sessions table: $sessionCount records\n";
    echo "   Portal sessions table: $portalSessionCount records\n";
} catch (\Exception $e) {
    echo "   ✗ Database error: " . $e->getMessage() . "\n";
}
echo "\n";

// 4. User Check
echo "4. USER ACCOUNTS\n";
echo str_repeat("-", 60) . "\n";

// Admin user
$adminUser = \App\Models\User::where('email', 'admin@askproai.de')->first();
if ($adminUser) {
    echo "   ✓ Admin user found: admin@askproai.de\n";
    $adminPasswordValid = Hash::check('demo123', $adminUser->password);
    echo "   Password 'demo123': " . ($adminPasswordValid ? '✓ Valid' : '✗ Invalid') . "\n";
} else {
    echo "   ✗ Admin user NOT found\n";
}

// Portal user
$portalUser = \App\Models\PortalUser::withoutGlobalScopes()
    ->where('email', 'demo@example.com')->first();
if ($portalUser) {
    echo "   ✓ Portal user found: demo@example.com\n";
    echo "   - Company ID: {$portalUser->company_id}\n";
    echo "   - Is active: " . ($portalUser->is_active ? 'Yes' : 'No') . "\n";
    $portalPasswordValid = Hash::check('demo123', $portalUser->password);
    echo "   Password 'demo123': " . ($portalPasswordValid ? '✓ Valid' : '✗ Invalid') . "\n";
} else {
    echo "   ✗ Portal user NOT found\n";
}
echo "\n";

// 5. Route Check
echo "5. ROUTE CONFIGURATION\n";
echo str_repeat("-", 60) . "\n";
$routes = [
    'admin.login' => 'Admin login page',
    'business.login' => 'Business login page',
    'business.login.post' => 'Business login POST',
    'business.dashboard' => 'Business dashboard',
];

foreach ($routes as $name => $description) {
    try {
        $url = route($name);
        echo "   ✓ $description: $url\n";
    } catch (\Exception $e) {
        echo "   ✗ $description: Route not found\n";
    }
}
echo "\n";

// 6. Middleware Check
echo "6. MIDDLEWARE CONFIGURATION\n";
echo str_repeat("-", 60) . "\n";
$middlewareGroups = config('app.middleware_groups', []);
if (isset($middlewareGroups['web'])) {
    echo "   Web middleware group: " . count($middlewareGroups['web']) . " middlewares\n";
}
if (isset($middlewareGroups['portal'])) {
    echo "   Portal middleware group: " . count($middlewareGroups['portal']) . " middlewares\n";
}
echo "\n";

// 7. Test Login Process
echo "7. TEST LOGIN PROCESS\n";
echo str_repeat("-", 60) . "\n";

// Test Portal Login
echo "   Testing Portal Login...\n";
try {
    $result = Auth::guard('portal')->attempt([
        'email' => 'demo@example.com',
        'password' => 'demo123'
    ]);
    
    if ($result) {
        echo "   ✓ Portal login successful\n";
        $user = Auth::guard('portal')->user();
        echo "   - Authenticated user ID: " . $user->id . "\n";
        echo "   - User name: " . $user->name . "\n";
        Auth::guard('portal')->logout();
    } else {
        echo "   ✗ Portal login failed\n";
    }
} catch (\Exception $e) {
    echo "   ✗ Portal login error: " . $e->getMessage() . "\n";
}

// Test Admin Login
echo "\n   Testing Admin Login...\n";
try {
    $result = Auth::guard('web')->attempt([
        'email' => 'admin@askproai.de',
        'password' => 'demo123'
    ]);
    
    if ($result) {
        echo "   ✓ Admin login successful\n";
        $user = Auth::guard('web')->user();
        echo "   - Authenticated user ID: " . $user->id . "\n";
        echo "   - User name: " . $user->name . "\n";
        Auth::guard('web')->logout();
    } else {
        echo "   ✗ Admin login failed\n";
    }
} catch (\Exception $e) {
    echo "   ✗ Admin login error: " . $e->getMessage() . "\n";
}
echo "\n";

// 8. Recommendations
echo "8. RECOMMENDATIONS\n";
echo str_repeat("-", 60) . "\n";

$issues = [];

if (config('session.domain') !== '.askproai.de') {
    $issues[] = "SESSION_DOMAIN should be '.askproai.de' (with leading dot)";
}

if (!$adminPasswordValid) {
    $issues[] = "Admin password needs to be reset to 'demo123'";
}

if (!$portalPasswordValid) {
    $issues[] = "Portal user password needs to be reset to 'demo123'";
}

if (empty($issues)) {
    echo "   ✓ All configurations look good!\n";
    echo "   \n";
    echo "   If login still doesn't work:\n";
    echo "   1. Clear browser cache and cookies\n";
    echo "   2. Try in incognito/private mode\n";
    echo "   3. Check browser console for JavaScript errors\n";
    echo "   4. Verify HTTPS certificate is valid\n";
} else {
    echo "   ⚠️  Issues found:\n";
    foreach ($issues as $issue) {
        echo "   - $issue\n";
    }
}

echo "\n╚══════════════════════════════════════════════════════════════╝\n\n";