<?php
/**
 * Test Business Portal Login Redirect Issue
 */

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);
$kernel->terminate($request, $response);

echo "=== BUSINESS PORTAL LOGIN TEST ===\n\n";

// 1. Check Session Configuration
echo "1. Session Configuration:\n";
echo "   Driver: " . config('session.driver') . "\n";
echo "   Domain: " . config('session.domain') . "\n";
echo "   Path: " . config('session.path') . "\n";
echo "   Same Site: " . config('session.same_site') . "\n";
echo "   Secure: " . (config('session.secure') ? 'true' : 'false') . "\n";

// 2. Check Auth Guards
echo "\n2. Auth Guards:\n";
$guards = config('auth.guards');
foreach ($guards as $name => $config) {
    echo "   - {$name}: " . ($config['driver'] ?? 'not configured') . "\n";
}

// 3. Check Portal Users
echo "\n3. Portal Users:\n";
$portalUsers = \App\Models\PortalUser::count();
echo "   Total portal users: {$portalUsers}\n";

if ($portalUsers > 0) {
    $testUser = \App\Models\PortalUser::first();
    echo "   Test user: {$testUser->email}\n";
}

// 4. Test Login Flow
echo "\n4. Testing Login Flow:\n";

// Create test portal user if none exists
if ($portalUsers === 0) {
    echo "   Creating test portal user...\n";
    
    $testUser = \App\Models\PortalUser::create([
        'name' => 'Test Portal User',
        'email' => 'test@portal.de',
        'password' => bcrypt('password'),
        'company_id' => 1,
        'is_active' => true
    ]);
    
    echo "   ✅ Created: {$testUser->email} (password: password)\n";
}

// 5. Check Business Portal Routes
echo "\n5. Business Portal Routes:\n";

$routes = [
    '/business',
    '/business/login',
    '/business/dashboard',
    '/business/appointments'
];

foreach ($routes as $route) {
    try {
        $testRequest = \Illuminate\Http\Request::create($route, 'GET');
        $response = $kernel->handle($testRequest);
        
        echo "   {$route}: Status " . $response->getStatusCode();
        
        if ($response->getStatusCode() === 302) {
            $location = $response->headers->get('Location');
            echo " → Redirects to: {$location}";
        }
        
        echo "\n";
    } catch (Exception $e) {
        echo "   {$route}: ERROR - " . $e->getMessage() . "\n";
    }
}

// 6. Check Middleware Stack
echo "\n6. Middleware Analysis:\n";

$router = app('router');
$businessRoutes = collect($router->getRoutes()->getRoutes())
    ->filter(fn($route) => str_starts_with($route->uri(), 'business'));

foreach ($businessRoutes as $route) {
    $middleware = $route->middleware();
    echo "   " . $route->uri() . ":\n";
    foreach ($middleware as $m) {
        echo "     - {$m}\n";
    }
}

// 7. Session Isolation Check
echo "\n7. Session Isolation:\n";

$adminSession = 'askproai_admin_session';
$portalSession = 'askproai_portal_session';

echo "   Admin session cookie: {$adminSession}\n";
echo "   Portal session cookie: {$portalSession}\n";

// 8. CORS Check
echo "\n8. CORS Configuration:\n";
$corsConfig = config('cors');
if ($corsConfig) {
    echo "   Allowed origins: " . implode(', ', $corsConfig['allowed_origins'] ?? ['*']) . "\n";
    echo "   Supports credentials: " . ($corsConfig['supports_credentials'] ? 'true' : 'false') . "\n";
}

// 9. Diagnosis
echo "\n=== DIAGNOSIS ===\n";

$issues = [];

// Check for session conflicts
if (config('session.domain') === null) {
    $issues[] = "Session domain not set - may cause cookie conflicts";
}

// Check for missing middleware
$requiredMiddleware = ['web', 'portal.auth'];
// Add specific checks based on routes

// Check for CORS issues
if (!config('cors.supports_credentials')) {
    $issues[] = "CORS not configured to support credentials";
}

if (empty($issues)) {
    echo "✅ No obvious configuration issues found\n";
} else {
    echo "⚠️  Found potential issues:\n";
    foreach ($issues as $issue) {
        echo "   - {$issue}\n";
    }
}

echo "\n=== RECOMMENDATIONS ===\n";
echo "1. Ensure separate session cookies for admin/portal\n";
echo "2. Check browser DevTools Network tab for redirect loops\n";
echo "3. Verify JWT/Sanctum token handling in React app\n";
echo "4. Test with incognito mode to rule out cookie conflicts\n";