<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

echo "Filament Login Diagnostic Test\n";
echo "==============================\n\n";

// Test 1: Check user and password
echo "1. USER & PASSWORD CHECK\n";
echo "------------------------\n";
$user = User::where('email', 'fabian@askproai.de')->first();
if ($user) {
    echo "✅ User found: {$user->name} (ID: {$user->id})\n";
    $passwordOk = Hash::check('Qwe421as1!1', $user->password);
    echo ($passwordOk ? "✅" : "❌") . " Password verification: " . ($passwordOk ? "PASSED" : "FAILED") . "\n";
} else {
    echo "❌ User not found!\n";
}

// Test 2: Check auth configuration
echo "\n2. AUTH CONFIGURATION\n";
echo "---------------------\n";
echo "Default guard: " . config('auth.defaults.guard') . "\n";
echo "Web guard driver: " . config('auth.guards.web.driver') . "\n";
echo "Web guard provider: " . config('auth.guards.web.provider') . "\n";
echo "User provider model: " . config('auth.providers.users.model') . "\n";

// Test 3: Check session configuration
echo "\n3. SESSION CONFIGURATION\n";
echo "------------------------\n";
echo "Session driver: " . config('session.driver') . "\n";
echo "Session table: " . config('session.table') . "\n";
echo "Session lifetime: " . config('session.lifetime') . " minutes\n";
echo "Session domain: " . (config('session.domain') ?: '(not set)') . "\n";
echo "Session path: " . config('session.path') . "\n";
echo "Secure cookies: " . (config('session.secure') ? 'YES' : 'NO') . "\n";
echo "HTTP only: " . (config('session.http_only') ? 'YES' : 'NO') . "\n";

// Test 4: Check database connection
echo "\n4. DATABASE CHECK\n";
echo "-----------------\n";
try {
    $sessionCount = DB::table('sessions')->count();
    echo "✅ Sessions table accessible, contains {$sessionCount} records\n";
} catch (\Exception $e) {
    echo "❌ Sessions table error: " . $e->getMessage() . "\n";
}

// Test 5: Check Filament configuration
echo "\n5. FILAMENT CONFIGURATION\n";
echo "-------------------------\n";
try {
    $panel = \Filament\Facades\Filament::getPanel('admin');
    echo "✅ Admin panel found\n";
    echo "Panel ID: " . $panel->getId() . "\n";
    echo "Panel path: " . $panel->getPath() . "\n";
    echo "Login enabled: " . ($panel->hasLogin() ? 'YES' : 'NO') . "\n";
    
    if ($user && method_exists($user, 'canAccessPanel')) {
        $canAccess = $user->canAccessPanel($panel);
        echo "User can access panel: " . ($canAccess ? 'YES' : 'NO') . "\n";
    }
} catch (\Exception $e) {
    echo "❌ Filament error: " . $e->getMessage() . "\n";
}

// Test 6: Check routes
echo "\n6. ROUTE CHECK\n";
echo "--------------\n";
$loginRoute = Route::getRoutes()->getByName('filament.admin.auth.login');
if ($loginRoute) {
    echo "✅ Login route exists: " . $loginRoute->uri() . "\n";
    echo "Action: " . $loginRoute->getActionName() . "\n";
} else {
    echo "❌ Login route not found\n";
}

// Test 7: Check middleware
echo "\n7. MIDDLEWARE CHECK\n";
echo "-------------------\n";
$webMiddleware = app('router')->getMiddlewareGroups()['web'] ?? [];
echo "Web middleware group:\n";
foreach ($webMiddleware as $middleware) {
    echo "  - " . (is_string($middleware) ? $middleware : get_class($middleware)) . "\n";
}

// Test 8: Check custom middleware
echo "\nCustom middleware:\n";
$customMiddleware = [
    'App\Http\Middleware\ResponseWrapper',
    'App\Http\Middleware\EnsureProperResponseFormat',
    'App\Overrides\CustomStartSession'
];
foreach ($customMiddleware as $class) {
    if (class_exists($class)) {
        echo "  ✅ {$class} exists\n";
    } else {
        echo "  ❌ {$class} NOT FOUND\n";
    }
}

// Test 9: Test actual authentication
echo "\n8. AUTHENTICATION TEST\n";
echo "----------------------\n";
$attempt = Auth::attempt(['email' => 'fabian@askproai.de', 'password' => 'Qwe421as1!1']);
if ($attempt) {
    echo "✅ Authentication successful!\n";
    echo "Authenticated user: " . Auth::user()->name . "\n";
    Auth::logout();
} else {
    echo "❌ Authentication failed\n";
}

// Test 10: Check for common issues
echo "\n9. COMMON ISSUES CHECK\n";
echo "----------------------\n";

// Check CSRF token
if (function_exists('csrf_token')) {
    echo "✅ CSRF token available\n";
} else {
    echo "❌ CSRF token function not available\n";
}

// Check app key
if (config('app.key')) {
    echo "✅ App key is set\n";
} else {
    echo "❌ App key is NOT set!\n";
}

// Summary
echo "\n==============================\n";
echo "SUMMARY: ";
if ($passwordOk && $attempt) {
    echo "✅ Login should work! If it still fails, check browser console for JavaScript errors.\n";
} else {
    echo "❌ There are issues that need to be fixed.\n";
}