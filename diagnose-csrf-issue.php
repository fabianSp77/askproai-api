<?php
/**
 * CSRF/Page Expired Diagnose Script
 * Analysiert Session, Cookie und CSRF-Token Probleme
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);
$kernel->terminate($request, $response);

// Farben für Output
function info($msg) { echo "\033[36m[INFO]\033[0m $msg\n"; }
function success($msg) { echo "\033[32m[OK]\033[0m $msg\n"; }
function warning($msg) { echo "\033[33m[WARN]\033[0m $msg\n"; }
function error($msg) { echo "\033[31m[ERROR]\033[0m $msg\n"; }
function print_header($msg) { echo "\n\033[35m=== $msg ===\033[0m\n"; }

print_header("CSRF/Page Expired Diagnose");

// 1. Session-Konfiguration
print_header("Session-Konfiguration");
info("Default Session Config:");
echo "  Driver: " . config('session.driver') . "\n";
echo "  Cookie: " . config('session.cookie') . "\n";
echo "  Path: " . config('session.path') . "\n";
echo "  Domain: " . config('session.domain') . "\n";
echo "  Secure: " . (config('session.secure') ? 'Yes' : 'No') . "\n";
echo "  Same Site: " . config('session.same_site') . "\n";

info("\nAdmin Session Config:");
$adminConfig = config('session_admin');
if ($adminConfig) {
    echo "  Cookie: " . ($adminConfig['cookie'] ?? 'not set') . "\n";
    echo "  Path: " . ($adminConfig['path'] ?? 'not set') . "\n";
    echo "  Lifetime: " . ($adminConfig['lifetime'] ?? 'not set') . " minutes\n";
    echo "  Files: " . ($adminConfig['files'] ?? 'not set') . "\n";
    success("Admin session config exists");
} else {
    error("Admin session config not found!");
}

info("\nPortal Session Config:");
$portalConfig = config('session_portal');
if ($portalConfig) {
    echo "  Cookie: " . ($portalConfig['cookie'] ?? 'not set') . "\n";
    echo "  Path: " . ($portalConfig['path'] ?? 'not set') . "\n";
    echo "  Lifetime: " . ($portalConfig['lifetime'] ?? 'not set') . " minutes\n";
    echo "  Files: " . ($portalConfig['files'] ?? 'not set') . "\n";
    success("Portal session config exists");
} else {
    error("Portal session config not found!");
}

// 2. Session-Storage
print_header("Session Storage Check");
$sessionPaths = [
    'default' => storage_path('framework/sessions'),
    'admin' => storage_path('framework/sessions/admin'),
    'portal' => storage_path('framework/sessions/portal'),
];

foreach ($sessionPaths as $type => $path) {
    if (is_dir($path)) {
        $writable = is_writable($path);
        $fileCount = count(glob($path . '/*'));
        if ($writable) {
            success("$type session path exists and is writable: $path (Files: $fileCount)");
        } else {
            error("$type session path exists but NOT writable: $path");
        }
    } else {
        warning("$type session path does not exist: $path");
        // Try to create it
        if (mkdir($path, 0755, true)) {
            success("Created $type session path: $path");
        } else {
            error("Failed to create $type session path: $path");
        }
    }
}

// 3. Middleware Analyse
print_header("Middleware Analysis");

// Check global middleware
info("Global Middleware:");
$globalMiddleware = $app->make('Illuminate\Contracts\Http\Kernel')->getMiddleware();
foreach ($globalMiddleware as $middleware) {
    $name = is_object($middleware) ? get_class($middleware) : $middleware;
    if (stripos($name, 'csrf') !== false || stripos($name, 'session') !== false) {
        warning("  - $name");
    }
}

// Check web middleware group
info("\nWeb Middleware Group:");
$kernel = $app->make('Illuminate\Contracts\Http\Kernel');
$webGroup = $kernel->getMiddlewareGroups()['web'] ?? [];
foreach ($webGroup as $middleware) {
    $name = is_object($middleware) ? get_class($middleware) : $middleware;
    if (stripos($name, 'csrf') !== false || stripos($name, 'session') !== false || stripos($name, 'livewire') !== false) {
        warning("  - $name");
    }
}

// 4. CSRF Token Exclusions
print_header("CSRF Token Exclusions");
$csrfMiddleware = new \App\Http\Middleware\VerifyCsrfToken($app, $app['encrypter']);
$reflection = new ReflectionClass($csrfMiddleware);
$property = $reflection->getProperty('except');
$property->setAccessible(true);
$exceptions = $property->getValue($csrfMiddleware);

info("URLs excluded from CSRF:");
foreach ($exceptions as $pattern) {
    echo "  - $pattern\n";
    if ($pattern === 'admin/*' || $pattern === 'livewire/*') {
        success("    ✓ Critical pattern found");
    }
}

// 5. Livewire Configuration
print_header("Livewire Configuration");
info("Livewire Config:");
echo "  Inject Assets: " . (config('livewire.inject_assets') ? 'Yes' : 'No') . "\n";
echo "  Class Namespace: " . config('livewire.class_namespace') . "\n";

// 6. Cookie Conflict Check
print_header("Cookie Conflict Analysis");
$cookies = [
    'default' => config('session.cookie'),
    'admin' => config('session_admin.cookie'),
    'portal' => config('session_portal.cookie'),
];

$uniqueCookies = array_unique(array_filter($cookies));
if (count($uniqueCookies) === count(array_filter($cookies))) {
    success("All session cookies have unique names");
    foreach ($cookies as $type => $name) {
        if ($name) {
            info("  $type: $name");
        }
    }
} else {
    error("Cookie name conflict detected!");
    foreach ($cookies as $type => $name) {
        error("  $type: $name");
    }
}

// 7. Path Conflict Check
print_header("Path Conflict Analysis");
$paths = [
    'admin' => config('session_admin.path'),
    'portal' => config('session_portal.path'),
];

info("Session Paths:");
foreach ($paths as $type => $path) {
    echo "  $type: $path\n";
}

if ($paths['admin'] === '/admin' && $paths['portal'] === '/business') {
    success("Session paths are properly isolated");
} else {
    warning("Session paths may not be properly isolated");
}

// 8. Test Session Creation
print_header("Test Session Creation");
try {
    // Start a session
    session_start();
    $_SESSION['test_key'] = 'test_value';
    $token = bin2hex(random_bytes(32));
    $_SESSION['_token'] = $token;
    
    success("Session created successfully");
    info("  Session ID: " . session_id());
    info("  Token: " . substr($token, 0, 10) . "...");
    
    session_write_close();
} catch (Exception $e) {
    error("Failed to create session: " . $e->getMessage());
}

// 9. Database Sessions (if using database driver)
if (config('session.driver') === 'database') {
    print_header("Database Session Check");
    try {
        $sessionCount = DB::table('sessions')->count();
        $recentSessions = DB::table('sessions')
            ->where('last_activity', '>', time() - 3600)
            ->count();
        
        success("Database sessions active");
        info("  Total sessions: $sessionCount");
        info("  Recent sessions (last hour): $recentSessions");
    } catch (Exception $e) {
        error("Database session error: " . $e->getMessage());
    }
}

// 10. Recommendations
print_header("Recommendations");

$issues = [];

// Check if PortalSessionIsolation is disabled
if (strpos(file_get_contents(base_path('app/Http/Kernel.php')), '// TEMPORARILY DISABLED') !== false) {
    $issues[] = "PortalSessionIsolation middleware is disabled";
}

// Check if CSRF is disabled for admin
if (in_array('admin/*', $exceptions)) {
    $issues[] = "CSRF is completely disabled for admin routes";
}

// Check middleware order
if (array_search('StartSession', $webGroup) > array_search('VerifyCsrfToken', $webGroup)) {
    $issues[] = "StartSession should come before VerifyCsrfToken in middleware";
}

if (empty($issues)) {
    success("No critical issues found");
} else {
    error("Issues found:");
    foreach ($issues as $issue) {
        error("  - $issue");
    }
}

// 11. Solution
print_header("Proposed Solution");
info("To fix the CSRF/Page Expired issue:");
echo "1. Enable PortalSessionIsolation middleware\n";
echo "2. Ensure proper session cookie configuration\n";
echo "3. Fix middleware order in Kernel.php\n";
echo "4. Clear all caches and sessions\n";
echo "5. Regenerate autoload files\n";

info("\nCommands to run:");
echo "  php artisan config:clear\n";
echo "  php artisan cache:clear\n";
echo "  rm -rf storage/framework/sessions/*\n";
echo "  composer dump-autoload\n";
echo "  php artisan optimize\n";

echo "\n";