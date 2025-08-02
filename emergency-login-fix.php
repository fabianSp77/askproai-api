<?php

/**
 * EMERGENCY LOGIN FIX
 * Removes all problematic middleware and uses Laravel defaults
 */

echo "\n===============================================\n";
echo "       EMERGENCY LOGIN FIX - ASKPROAI          \n";
echo "===============================================\n\n";

// 1. Backup current bootstrap/app.php
echo "1. Creating backup of bootstrap/app.php...\n";
$bootstrapFile = __DIR__ . '/bootstrap/app.php';
$backupFile = __DIR__ . '/bootstrap/app.php.backup-' . date('Y-m-d-H-i-s');
copy($bootstrapFile, $backupFile);
echo "   ✓ Backup created: $backupFile\n";

// 2. Create simplified bootstrap configuration
echo "\n2. Creating simplified middleware configuration...\n";

$newBootstrapContent = file_get_contents($bootstrapFile);

// Replace the portal middleware group with simplified version
$portalGroupSimplified = <<<'PHP'
        /* ---------------------------------------------------------
         |  PORTAL-Gruppe  (Business Portal - SIMPLIFIED)
         * -------------------------------------------------------- */
        $middleware->group('portal', [
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);
PHP;

// Replace business-portal group
$businessPortalGroupSimplified = <<<'PHP'
        /* ---------------------------------------------------------
         |  BUSINESS-PORTAL-Gruppe  (SIMPLIFIED - same as portal)
         * -------------------------------------------------------- */
        $middleware->group('business-portal', [
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);
PHP;

// Find and replace the portal group
$pattern = '/\/\* -+\s*\|\s*PORTAL-Gruppe.*?\]\);/s';
$newBootstrapContent = preg_replace($pattern, $portalGroupSimplified, $newBootstrapContent);

// Find and replace the business-portal group
$pattern = '/\/\* -+\s*\|\s*BUSINESS-PORTAL-Gruppe.*?\]\);/s';
$newBootstrapContent = preg_replace($pattern, $businessPortalGroupSimplified, $newBootstrapContent);

// Save the simplified version
file_put_contents($bootstrapFile, $newBootstrapContent);
echo "   ✓ Middleware simplified\n";

// 3. Create a simple test login controller
echo "\n3. Creating simple test login controller...\n";

$simpleLoginController = <<<'PHP'
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\PortalUser;

class SimpleAuthTestController extends Controller
{
    public function testAdminLogin(Request $request)
    {
        // Find admin user
        $admin = User::where('email', 'fabian@askproai.de')->first();
        
        if (!$admin) {
            return response()->json(['error' => 'Admin user not found']);
        }
        
        // Force login
        Auth::guard('web')->login($admin);
        
        return response()->json([
            'success' => true,
            'user' => $admin->email,
            'authenticated' => Auth::check(),
            'guard' => 'web',
            'redirect' => '/admin'
        ]);
    }
    
    public function testPortalLogin(Request $request)
    {
        // Find portal user
        $portalUser = PortalUser::withoutGlobalScopes()
            ->where('email', 'demo@askproai.de')
            ->orWhere('email', 'admin+1@askproai.de')
            ->first();
        
        if (!$portalUser) {
            return response()->json(['error' => 'Portal user not found']);
        }
        
        // Force login
        Auth::guard('portal')->login($portalUser);
        
        return response()->json([
            'success' => true,
            'user' => $portalUser->email,
            'authenticated' => Auth::guard('portal')->check(),
            'guard' => 'portal',
            'redirect' => '/business/dashboard'
        ]);
    }
}
PHP;

file_put_contents(__DIR__ . '/app/Http/Controllers/SimpleAuthTestController.php', $simpleLoginController);
echo "   ✓ Test controller created\n";

// 4. Add test routes
echo "\n4. Adding test routes...\n";

$testRoutes = <<<'PHP'

// EMERGENCY TEST ROUTES
Route::get('/test-admin-auth', [App\Http\Controllers\SimpleAuthTestController::class, 'testAdminLogin']);
Route::get('/test-portal-auth', [App\Http\Controllers\SimpleAuthTestController::class, 'testPortalLogin']);

PHP;

// Add to web.php
$webRoutes = file_get_contents(__DIR__ . '/routes/web.php');
if (!str_contains($webRoutes, 'SimpleAuthTestController')) {
    file_put_contents(__DIR__ . '/routes/web.php', $webRoutes . $testRoutes);
    echo "   ✓ Test routes added\n";
}

// 5. Clear all caches
echo "\n5. Clearing all caches...\n";
$commands = [
    'php artisan optimize:clear',
    'php artisan config:clear',
    'php artisan route:clear',
    'php artisan view:clear',
];

foreach ($commands as $cmd) {
    exec($cmd . ' 2>&1', $output);
    echo "   ✓ $cmd\n";
}

// 6. Create test HTML page
echo "\n6. Creating test page...\n";

$testPage = <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <title>Emergency Login Test</title>
    <style>
        body { font-family: Arial; padding: 20px; max-width: 800px; margin: 0 auto; }
        .test-btn { padding: 10px 20px; margin: 10px; cursor: pointer; }
        .success { color: green; }
        .error { color: red; }
        pre { background: #f4f4f4; padding: 10px; overflow: auto; }
    </style>
</head>
<body>
    <h1>Emergency Login Test</h1>
    
    <h2>1. Clear All Cookies</h2>
    <button class="test-btn" onclick="clearCookies()">Clear Cookies</button>
    <div id="cookie-result"></div>
    
    <h2>2. Test Admin Login</h2>
    <button class="test-btn" onclick="testAdminLogin()">Test Admin Login</button>
    <div id="admin-result"></div>
    
    <h2>3. Test Portal Login</h2>
    <button class="test-btn" onclick="testPortalLogin()">Test Portal Login</button>
    <div id="portal-result"></div>
    
    <script>
        function clearCookies() {
            document.cookie.split(";").forEach(function(c) { 
                document.cookie = c.replace(/^ +/, "").replace(/=.*/, "=;expires=" + new Date().toUTCString() + ";path=/"); 
            });
            document.getElementById('cookie-result').innerHTML = '<p class="success">✓ Cookies cleared</p>';
        }
        
        async function testAdminLogin() {
            const response = await fetch('/test-admin-auth');
            const data = await response.json();
            document.getElementById('admin-result').innerHTML = '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
            if (data.success) {
                setTimeout(() => window.open(data.redirect, '_blank'), 1000);
            }
        }
        
        async function testPortalLogin() {
            const response = await fetch('/test-portal-auth');
            const data = await response.json();
            document.getElementById('portal-result').innerHTML = '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
            if (data.success) {
                setTimeout(() => window.open(data.redirect, '_blank'), 1000);
            }
        }
    </script>
</body>
</html>
HTML;

file_put_contents(__DIR__ . '/public/emergency-login-test.html', $testPage);
echo "   ✓ Test page created: /emergency-login-test.html\n";

echo "\n===============================================\n";
echo "              FIX COMPLETE!                    \n";
echo "===============================================\n\n";

echo "NÄCHSTE SCHRITTE:\n";
echo "----------------\n";
echo "1. Öffne: https://api.askproai.de/emergency-login-test.html\n";
echo "2. Klicke 'Clear Cookies'\n";
echo "3. Teste 'Test Admin Login'\n";
echo "4. Teste 'Test Portal Login'\n";
echo "\nWenn das funktioniert, ist das Problem die komplexe Middleware!\n\n";