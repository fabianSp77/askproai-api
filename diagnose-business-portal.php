<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/bootstrap/app.php';

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\PortalUser;
use App\Models\Company;

// Bootstrap the Laravel application
$app = new Illuminate\Foundation\Application(
    realpath(__DIR__)
);

$app->singleton(
    Illuminate\Contracts\Http\Kernel::class,
    App\Http\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "\n=== BUSINESS PORTAL DIAGNOSE ===\n\n";

// 1. Check Portal Users
echo "1. Portal Users Check:\n";
$portalUsers = PortalUser::withoutGlobalScopes()->get();
echo "   Total Portal Users: " . $portalUsers->count() . "\n";
foreach ($portalUsers as $user) {
    echo "   - {$user->email} (Company: {$user->company_id}, Active: " . ($user->is_active ? 'Yes' : 'No') . ")\n";
}

// 2. Check Companies
echo "\n2. Companies Check:\n";
$companies = Company::withoutGlobalScopes()->get();
echo "   Total Companies: " . $companies->count() . "\n";
foreach ($companies as $company) {
    echo "   - {$company->name} (ID: {$company->id}, Active: " . ($company->is_active ? 'Yes' : 'No') . ")\n";
}

// 3. Check Routes
echo "\n3. Route Check:\n";
$routes = [
    '/business/login' => 'Login Page',
    '/business/api/dashboard' => 'Dashboard API',
    '/business/api/check-auth' => 'Auth Check API',
    '/business/api/session-debug' => 'Session Debug API',
    '/business' => 'Main Dashboard'
];

foreach ($routes as $route => $name) {
    $url = url($route);
    echo "   - {$name}: {$url}\n";
}

// 4. Check Session Configuration
echo "\n4. Session Configuration:\n";
echo "   Driver: " . config('session.driver') . "\n";
echo "   Lifetime: " . config('session.lifetime') . " minutes\n";
echo "   Domain: " . config('session.domain') . "\n";
echo "   Path: " . config('session.path') . "\n";
echo "   Secure: " . (config('session.secure') ? 'Yes' : 'No') . "\n";

// 5. Check Authentication Guards
echo "\n5. Authentication Guards:\n";
echo "   Portal Guard Driver: " . config('auth.guards.portal.driver') . "\n";
echo "   Portal Guard Provider: " . config('auth.guards.portal.provider') . "\n";
echo "   Portal Provider Model: " . config('auth.providers.portal_users.model') . "\n";

// 6. Test Portal User Login
echo "\n6. Test Portal User Login:\n";
$testUser = PortalUser::withoutGlobalScopes()->where('email', 'demo@example.com')->first();
if ($testUser) {
    echo "   Found test user: {$testUser->email}\n";
    echo "   Password hash exists: " . (!empty($testUser->password) ? 'Yes' : 'No') . "\n";
    echo "   Company exists: " . ($testUser->company ? 'Yes' : 'No') . "\n";
    if ($testUser->company) {
        echo "   Company: {$testUser->company->name} (Active: " . ($testUser->company->is_active ? 'Yes' : 'No') . ")\n";
    }
} else {
    echo "   No test user found (demo@example.com)\n";
}

// 7. Check Database Tables
echo "\n7. Database Tables Check:\n";
$tables = [
    'portal_users' => 'Portal Users Table',
    'companies' => 'Companies Table',
    'calls' => 'Calls Table',
    'appointments' => 'Appointments Table',
    'call_portal_data' => 'Call Portal Data Table'
];

foreach ($tables as $table => $name) {
    $exists = DB::getSchemaBuilder()->hasTable($table);
    echo "   - {$name}: " . ($exists ? 'Exists' : 'Missing') . "\n";
    if ($exists) {
        $count = DB::table($table)->count();
        echo "     Records: {$count}\n";
    }
}

// 8. Check Middleware Configuration
echo "\n8. Middleware Check:\n";
$middlewareGroups = app('router')->getMiddlewareGroups();
if (isset($middlewareGroups['web'])) {
    echo "   Web middleware group exists\n";
}
$routeMiddleware = app('router')->getMiddleware();
$portalMiddleware = ['portal.auth', 'portal.auth.api', 'portal.permission'];
foreach ($portalMiddleware as $middleware) {
    echo "   - {$middleware}: " . (isset($routeMiddleware[$middleware]) ? 'Registered' : 'Missing') . "\n";
}

// 9. Check API Response
echo "\n9. Test API Response:\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, url('/business/api/session-debug-open'));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_NOBODY, false);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "   Session Debug API Response: HTTP {$httpCode}\n";

// 10. Check React Build Files
echo "\n10. React Build Files:\n";
$buildFiles = [
    'public/build/manifest.json' => 'Vite Manifest',
    'public/build/assets' => 'Assets Directory'
];

foreach ($buildFiles as $file => $name) {
    $exists = file_exists(base_path($file));
    echo "   - {$name}: " . ($exists ? 'Exists' : 'Missing') . "\n";
}

// 11. Create test portal user if needed
echo "\n11. Creating/Updating Test User:\n";
$testEmail = 'test-portal@example.com';
$testUser = PortalUser::withoutGlobalScopes()->where('email', $testEmail)->first();

if (!$testUser) {
    // Get first active company
    $company = Company::withoutGlobalScopes()->where('is_active', true)->first();
    if ($company) {
        $testUser = PortalUser::withoutGlobalScopes()->create([
            'name' => 'Test Portal User',
            'email' => $testEmail,
            'password' => bcrypt('password123'),
            'company_id' => $company->id,
            'is_active' => true,
            'role' => 'owner'
        ]);
        echo "   Created test user: {$testEmail} (password: password123)\n";
        echo "   Company: {$company->name}\n";
    } else {
        echo "   No active company found - cannot create test user\n";
    }
} else {
    echo "   Test user already exists: {$testEmail}\n";
    // Update password
    $testUser->password = bcrypt('password123');
    $testUser->is_active = true;
    $testUser->save();
    echo "   Updated password to: password123\n";
}

echo "\n=== DIAGNOSIS COMPLETE ===\n";
echo "\nPotential Issues Found:\n";

// Summary of issues
$issues = [];

if ($portalUsers->count() == 0) {
    $issues[] = "No portal users exist - users cannot login";
}

if ($companies->where('is_active', true)->count() == 0) {
    $issues[] = "No active companies - users cannot access portal";
}

if (!file_exists(base_path('public/build/manifest.json'))) {
    $issues[] = "React build files missing - run 'npm run build'";
}

$inactiveUsers = $portalUsers->where('is_active', false)->count();
if ($inactiveUsers > 0) {
    $issues[] = "{$inactiveUsers} inactive portal users found";
}

if (empty($issues)) {
    echo "✓ No major issues found\n";
} else {
    foreach ($issues as $issue) {
        echo "✗ {$issue}\n";
    }
}

echo "\nNext Steps:\n";
echo "1. Try logging in with test-portal@example.com / password123\n";
echo "2. Check browser console for JavaScript errors\n";
echo "3. Check Laravel logs: tail -f storage/logs/laravel.log\n";
echo "4. Test API directly: curl " . url('/business/api/check-auth') . "\n";