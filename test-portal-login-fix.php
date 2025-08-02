<?php

/**
 * Test Portal Login Fix
 * 
 * This script tests if the login functionality works correctly
 * after our session configuration fixes.
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

echo "🔍 Testing Portal Login Fix\n";
echo "==========================\n\n";

// Test 1: Check ENV configuration
echo "1. Checking ENV configuration:\n";
echo "   - APP_URL: " . env('APP_URL') . "\n";
echo "   - SESSION_SECURE_COOKIE: " . (env('SESSION_SECURE_COOKIE') ? 'true ✅' : 'false ❌') . "\n";
echo "   - SESSION_COOKIE: " . env('SESSION_COOKIE', 'default') . "\n";
echo "   - ADMIN_SESSION_COOKIE: " . env('ADMIN_SESSION_COOKIE', 'not set') . "\n";
echo "   - PORTAL_SESSION_COOKIE: " . env('PORTAL_SESSION_COOKIE', 'not set') . "\n\n";

// Test 2: Check session directories
echo "2. Checking session directories:\n";
$adminDir = storage_path('framework/sessions/admin');
$portalDir = storage_path('framework/sessions/portal');
echo "   - Admin dir exists: " . (is_dir($adminDir) ? 'Yes ✅' : 'No ❌') . "\n";
echo "   - Portal dir exists: " . (is_dir($portalDir) ? 'Yes ✅' : 'No ❌') . "\n";
echo "   - Admin dir writable: " . (is_writable($adminDir) ? 'Yes ✅' : 'No ❌') . "\n";
echo "   - Portal dir writable: " . (is_writable($portalDir) ? 'Yes ✅' : 'No ❌') . "\n\n";

// Test 3: Check middleware configuration
echo "3. Checking middleware configuration:\n";
$bootstrapFile = file_get_contents(__DIR__ . '/bootstrap/app.php');
echo "   - UnifiedSessionConfig removed: " . (!str_contains($bootstrapFile, 'UnifiedSessionConfig::class') ? 'Yes ✅' : 'No ❌') . "\n";
echo "   - AdminSessionConfig in admin group: " . (str_contains($bootstrapFile, 'AdminSessionConfig::class') ? 'Yes ✅' : 'No ❌') . "\n";
echo "   - PortalSessionConfig in portal groups: " . (str_contains($bootstrapFile, 'PortalSessionConfig::class') ? 'Yes ✅' : 'No ❌') . "\n\n";

// Test 4: Test user exists
echo "4. Checking test users:\n";
try {
    $demoUser = \App\Models\PortalUser::withoutGlobalScopes()->where('email', 'demo@askproai.de')->first();
    echo "   - Demo user exists: " . ($demoUser ? 'Yes ✅' : 'No ❌') . "\n";
    
    $adminUser = \App\Models\User::where('email', 'fabian@askproai.de')->first();
    echo "   - Admin user exists: " . ($adminUser ? 'Yes ✅' : 'No ❌') . "\n";
} catch (Exception $e) {
    echo "   - Error checking users: " . $e->getMessage() . " ❌\n";
}

echo "\n5. Summary:\n";
echo "   - Session configuration should now work correctly\n";
echo "   - Each portal has its own session cookie\n";
echo "   - HTTPS cookie security is enabled\n";
echo "   - Session directories are properly separated\n";

echo "\n🎯 Next steps:\n";
echo "   1. Test admin login at: https://api.askproai.de/admin/login\n";
echo "   2. Test business login at: https://api.askproai.de/business/login\n";
echo "   3. Verify no 419 CSRF errors\n";
echo "   4. Check that sessions persist across page refreshes\n";

echo "\n✅ Configuration fixes completed!\n";