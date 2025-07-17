<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\PortalUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

echo "\n🔍 UMFASSENDE LOGIN-ANALYSE\n";
echo "===========================\n\n";

// 1. DATABASE CHECK
echo "1️⃣ DATABASE ANALYSIS\n";
echo "-------------------\n";

// Check database connection
try {
    DB::connection()->getPdo();
    echo "✅ Database connection: OK\n";
} catch (\Exception $e) {
    echo "❌ Database connection: FAILED - " . $e->getMessage() . "\n";
}

// Check portal_users table
$tableExists = DB::select("SHOW TABLES LIKE 'portal_users'");
echo "✅ Table 'portal_users' exists: " . (!empty($tableExists) ? "YES" : "NO") . "\n";

// Check table structure
$columns = DB::select("SHOW COLUMNS FROM portal_users");
echo "\nPortal Users Table Structure:\n";
foreach ($columns as $column) {
    echo "  - {$column->Field} ({$column->Type})\n";
}

// Check sessions table
$sessionsExists = DB::select("SHOW TABLES LIKE 'sessions'");
echo "\n✅ Table 'sessions' exists: " . (!empty($sessionsExists) ? "YES" : "NO") . "\n";

if (!empty($sessionsExists)) {
    $sessionCount = DB::table('sessions')->count();
    echo "  Active sessions: $sessionCount\n";
    
    // Check recent sessions
    $recentSessions = DB::table('sessions')
        ->where('last_activity', '>', time() - 3600)
        ->count();
    echo "  Recent sessions (last hour): $recentSessions\n";
}

// 2. USER CHECK
echo "\n2️⃣ USER ANALYSIS\n";
echo "----------------\n";

$testEmail = 'fabianspitzer@icloud.com';
$user = PortalUser::where('email', $testEmail)->first();

if ($user) {
    echo "✅ Test user found: {$user->email}\n";
    echo "  - ID: {$user->id}\n";
    echo "  - Active: " . ($user->is_active ? 'YES' : 'NO') . "\n";
    echo "  - Company ID: {$user->company_id}\n";
    echo "  - Created: {$user->created_at}\n";
    echo "  - Password Hash: " . substr($user->password, 0, 20) . "...\n";
    
    // Test password
    $testPassword = 'demo123';
    $hashCheck = Hash::check($testPassword, $user->password);
    echo "  - Password 'demo123' valid: " . ($hashCheck ? 'YES' : 'NO') . "\n";
    
    // Check company
    if ($user->company) {
        echo "  - Company: {$user->company->name}\n";
        echo "  - Company active: " . ($user->company->is_active ? 'YES' : 'NO') . "\n";
    }
} else {
    echo "❌ Test user NOT found: $testEmail\n";
}

// 3. ROUTE ANALYSIS
echo "\n3️⃣ ROUTE ANALYSIS\n";
echo "-----------------\n";

// Check login routes
$routes = Route::getRoutes();
$loginRoutes = [];
foreach ($routes as $route) {
    if (strpos($route->uri(), 'login') !== false || strpos($route->uri(), 'business') !== false) {
        $loginRoutes[] = [
            'uri' => $route->uri(),
            'methods' => implode('|', $route->methods()),
            'name' => $route->getName(),
            'action' => $route->getActionName(),
            'middleware' => implode(', ', $route->middleware())
        ];
    }
}

echo "Login-related routes:\n";
foreach ($loginRoutes as $route) {
    echo "  - [{$route['methods']}] {$route['uri']}\n";
    echo "    Name: {$route['name']}\n";
    echo "    Action: {$route['action']}\n";
    echo "    Middleware: {$route['middleware']}\n\n";
}

// 4. AUTH CONFIGURATION
echo "\n4️⃣ AUTH CONFIGURATION\n";
echo "--------------------\n";

$authConfig = config('auth');
echo "Default guard: " . $authConfig['defaults']['guard'] . "\n";
echo "Guards configured:\n";
foreach ($authConfig['guards'] as $name => $guard) {
    echo "  - $name: driver={$guard['driver']}, provider=" . ($guard['provider'] ?? 'none') . "\n";
}

echo "\nProviders configured:\n";
foreach ($authConfig['providers'] as $name => $provider) {
    echo "  - $name: driver={$provider['driver']}, model={$provider['model']}\n";
}

// 5. SESSION CONFIGURATION
echo "\n5️⃣ SESSION CONFIGURATION\n";
echo "-----------------------\n";

$sessionConfig = config('session');
echo "Driver: " . $sessionConfig['driver'] . "\n";
echo "Lifetime: " . $sessionConfig['lifetime'] . " minutes\n";
echo "Domain: " . $sessionConfig['domain'] . "\n";
echo "Path: " . $sessionConfig['path'] . "\n";
echo "Secure: " . ($sessionConfig['secure'] ? 'YES' : 'NO') . "\n";
echo "HTTP Only: " . ($sessionConfig['http_only'] ? 'YES' : 'NO') . "\n";
echo "Same Site: " . $sessionConfig['same_site'] . "\n";

// 6. MIDDLEWARE CHECK
echo "\n6️⃣ MIDDLEWARE ANALYSIS\n";
echo "---------------------\n";

// Check if required middleware exists
$middlewareClasses = [
    'App\Http\Middleware\PortalAuthenticate',
    'App\Http\Middleware\VerifyCsrfToken',
    'Illuminate\Session\Middleware\StartSession',
    'App\Http\Middleware\FixStartSession', // Should NOT exist
];

foreach ($middlewareClasses as $class) {
    $exists = class_exists($class);
    echo "Middleware $class: " . ($exists ? 'EXISTS' : 'NOT FOUND') . "\n";
}

// 7. LOGIN CONTROLLER CHECK
echo "\n7️⃣ LOGIN CONTROLLER ANALYSIS\n";
echo "---------------------------\n";

$controllerPath = app_path('Http/Controllers/Portal/Auth/LoginController.php');
if (file_exists($controllerPath)) {
    echo "✅ LoginController exists\n";
    $content = file_get_contents($controllerPath);
    
    // Check for login method
    if (strpos($content, 'public function login') !== false) {
        echo "✅ login() method exists\n";
    }
    
    // Check for Auth usage
    if (strpos($content, "Auth::guard('portal')") !== false) {
        echo "✅ Uses portal guard\n";
    }
    
    // Check for Hash usage
    if (strpos($content, 'Hash::check') !== false) {
        echo "✅ Uses Hash::check for password\n";
    }
}

// 8. CSRF TOKEN CHECK
echo "\n8️⃣ CSRF TOKEN ANALYSIS\n";
echo "---------------------\n";

$csrfMiddleware = app_path('Http/Middleware/VerifyCsrfToken.php');
if (file_exists($csrfMiddleware)) {
    $content = file_get_contents($csrfMiddleware);
    preg_match('/protected \$except = \[(.*?)\];/s', $content, $matches);
    if ($matches) {
        echo "CSRF exceptions:\n";
        $exceptions = $matches[1];
        echo $exceptions . "\n";
    }
}

// 9. TEST LOGIN ATTEMPT
echo "\n9️⃣ TEST LOGIN ATTEMPT\n";
echo "--------------------\n";

if ($user) {
    // Test with Auth facade
    $attempt = Auth::guard('portal')->attempt([
        'email' => $testEmail,
        'password' => 'demo123'
    ]);
    
    echo "Auth::guard('portal')->attempt(): " . ($attempt ? 'SUCCESS' : 'FAILED') . "\n";
    
    if (!$attempt) {
        // Try manual login
        $validated = Hash::check('demo123', $user->password);
        if ($validated) {
            Auth::guard('portal')->login($user);
            $check = Auth::guard('portal')->check();
            echo "Manual login: " . ($check ? 'SUCCESS' : 'FAILED') . "\n";
        }
    }
    
    // Check session
    echo "Session ID: " . session()->getId() . "\n";
    echo "Session driver: " . session()->getDefaultDriver() . "\n";
}

// 10. ENVIRONMENT CHECK
echo "\n🔟 ENVIRONMENT CHECK\n";
echo "-------------------\n";

$envVars = [
    'APP_URL',
    'SESSION_DRIVER',
    'SESSION_LIFETIME',
    'SESSION_DOMAIN',
    'SESSION_SECURE_COOKIE',
    'SANCTUM_STATEFUL_DOMAINS',
    'SESSION_COOKIE',
    'CACHE_DRIVER'
];

foreach ($envVars as $var) {
    $value = env($var, 'NOT SET');
    echo "$var: $value\n";
}

// 11. SQL QUERY TEST
echo "\n1️⃣1️⃣ SQL QUERY TEST\n";
echo "------------------\n";

// Direct SQL test
$result = DB::select("
    SELECT id, email, password, is_active, company_id 
    FROM portal_users 
    WHERE email = ?
", [$testEmail]);

if (!empty($result)) {
    echo "✅ Direct SQL query successful\n";
    echo "  Found user: " . $result[0]->email . "\n";
} else {
    echo "❌ No user found with direct SQL\n";
}

// 12. Check for common issues
echo "\n1️⃣2️⃣ COMMON ISSUES CHECK\n";
echo "-----------------------\n";

// Check bootstrap/app.php for issues
$bootstrapPath = base_path('bootstrap/app.php');
$bootstrapContent = file_get_contents($bootstrapPath);

if (strpos($bootstrapContent, 'FixStartSession') !== false) {
    echo "⚠️  WARNING: FixStartSession still referenced in bootstrap/app.php!\n";
} else {
    echo "✅ No FixStartSession reference found\n";
}

// Check for multiple session starts
$sessionStartCount = substr_count($bootstrapContent, 'StartSession');
echo "StartSession references: $sessionStartCount\n";

echo "\n✅ Analysis complete!\n";