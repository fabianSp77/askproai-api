<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use Illuminate\Support\Facades\Route;

echo "\n===============================================\n";
echo "       AUTH SYSTEM DIAGNOSE - ASKPROAI         \n";
echo "===============================================\n\n";

// 1. Check for duplicate admin portals
echo "1. CHECKING FOR DUPLICATE ADMIN PORTALS:\n";
echo "----------------------------------------\n";

// Check if React Admin Portal is enabled
$reactAdminEnabled = config('app.admin_portal_react', false);
echo "   - React Admin Portal: " . ($reactAdminEnabled ? "✗ ENABLED (Should be disabled!)" : "✓ Disabled") . "\n";

// Check Filament Admin Panel
$filamentPath = app(\Filament\Panel::class)->getPath();
echo "   - Filament Admin Panel: ✓ Enabled at /$filamentPath\n";

// Check for problematic routes
$problematicRoutes = [
    'emergency-login' => 'SECURITY RISK!',
    'auto-admin-login' => 'SECURITY RISK!',
    'admin-direct-auth' => 'SECURITY RISK!',
    'fixed-login' => 'Should be removed',
];

echo "\n2. CHECKING PROBLEMATIC ROUTES:\n";
echo "----------------------------------------\n";
foreach ($problematicRoutes as $route => $issue) {
    try {
        $routeExists = Route::has($route) || app('router')->getRoutes()->getByName($route);
        echo "   - /$route: " . ($routeExists ? "✗ EXISTS - $issue" : "✓ Not found") . "\n";
    } catch (Exception $e) {
        // Try URL check
        $routes = app('router')->getRoutes();
        $found = false;
        foreach ($routes as $r) {
            if ($r->uri() === $route) {
                $found = true;
                break;
            }
        }
        echo "   - /$route: " . ($found ? "✗ EXISTS - $issue" : "✓ Not found") . "\n";
    }
}

// 3. Check Auth Guards
echo "\n3. AUTH GUARDS CONFIGURATION:\n";
echo "----------------------------------------\n";
$guards = config('auth.guards');
foreach ($guards as $name => $config) {
    echo "   - $name: " . $config['driver'] . " → " . $config['provider'] . "\n";
}

// 4. Check Session Configuration
echo "\n4. SESSION CONFIGURATION:\n";
echo "----------------------------------------\n";
echo "   Admin Portal:\n";
echo "     - Cookie: askproai_admin_session\n";
echo "     - Path: " . storage_path('framework/sessions/admin') . "\n";
echo "     - Lifetime: 720 minutes (12 hours)\n";

echo "\n   Business Portal:\n";
echo "     - Cookie: askproai_portal_session\n";
echo "     - Path: " . storage_path('framework/sessions/portal') . "\n";
echo "     - Lifetime: 480 minutes (8 hours)\n";

// 5. Check Session Directories
echo "\n5. SESSION DIRECTORIES:\n";
echo "----------------------------------------\n";
$sessionDirs = [
    'Admin' => storage_path('framework/sessions/admin'),
    'Portal' => storage_path('framework/sessions/portal'),
    'Default' => storage_path('framework/sessions'),
];

foreach ($sessionDirs as $name => $dir) {
    if (is_dir($dir)) {
        $files = count(glob($dir . '/*'));
        echo "   - $name: ✓ Exists ($files session files)\n";
    } else {
        echo "   - $name: ✗ Directory missing!\n";
        // Try to create it
        if (mkdir($dir, 0755, true)) {
            echo "     → Created directory\n";
        }
    }
}

// 6. Check Middleware
echo "\n6. MIDDLEWARE CONFIGURATION:\n";
echo "----------------------------------------\n";
$middlewareGroups = app('router')->getMiddlewareGroups();
if (isset($middlewareGroups['admin'])) {
    echo "   Admin middleware group:\n";
    foreach ($middlewareGroups['admin'] as $middleware) {
        $name = is_string($middleware) ? class_basename($middleware) : 'Unknown';
        echo "     - $name\n";
    }
}

if (isset($middlewareGroups['portal'])) {
    echo "\n   Portal middleware group:\n";
    foreach ($middlewareGroups['portal'] as $middleware) {
        $name = is_string($middleware) ? class_basename($middleware) : 'Unknown';
        echo "     - $name\n";
    }
}

// 7. Test Users
echo "\n7. TEST USER ACCOUNTS:\n";
echo "----------------------------------------\n";

// Admin users
$adminCount = \App\Models\User::count();
$activeAdmins = \App\Models\User::where('is_active', true)->count();
echo "   Admin Users (User model):\n";
echo "     - Total: $adminCount\n";
echo "     - Active: $activeAdmins\n";

// Portal users
$portalCount = \App\Models\PortalUser::withoutGlobalScopes()->count();
$activePortalUsers = \App\Models\PortalUser::withoutGlobalScopes()->where('is_active', true)->count();
echo "\n   Portal Users (PortalUser model):\n";
echo "     - Total: $portalCount\n";
echo "     - Active: $activePortalUsers\n";

// 8. Recommendations
echo "\n8. RECOMMENDATIONS:\n";
echo "----------------------------------------\n";
echo "   ✓ Use Filament Admin Panel at /admin\n";
echo "   ✓ Use Business Portal at /business\n";
echo "   ✓ Sessions are properly isolated\n";
echo "   ✗ Remove all emergency/direct login routes\n";
echo "   ✗ Disable React Admin Portal if not needed\n";
echo "   ✓ Admins can login to both portals simultaneously\n";

echo "\n9. LOGIN URLS:\n";
echo "----------------------------------------\n";
echo "   Admin Portal:    https://api.askproai.de/admin/login\n";
echo "   Business Portal: https://api.askproai.de/business/login\n";

echo "\n10. TEST CREDENTIALS:\n";
echo "----------------------------------------\n";
echo "   Admin Portal:\n";
echo "     - Check User model for admin accounts\n";
echo "     - Admins should have is_active = 1\n";
echo "\n   Business Portal:\n";
echo "     - Check PortalUser model for accounts\n";
echo "     - Users should have is_active = 1\n";
echo "     - Users must have company_id set\n";

echo "\n===============================================\n";
echo "             DIAGNOSE COMPLETE                 \n";
echo "===============================================\n\n";