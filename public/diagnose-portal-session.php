<?php
/**
 * Diagnose Portal Session Cookie Issues
 */

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Initialize the application
$response = $kernel->handle(
    $request = Illuminate\Http\Request::create('/')
);

echo "=== PORTAL SESSION DIAGNOSE ===\n\n";

// 1. Session Konfiguration
echo "1. Session Konfiguration:\n";
$sessionConfig = config('session');
$portalSessionConfig = config('session_portal');

echo "   Standard Session:\n";
echo "   - Driver: " . $sessionConfig['driver'] . "\n";
echo "   - Cookie: " . $sessionConfig['cookie'] . "\n";
echo "   - Domain: " . ($sessionConfig['domain'] ?? 'null') . "\n";
echo "   - Secure: " . ($sessionConfig['secure'] ? 'true' : 'false') . "\n";
echo "   - Path: " . $sessionConfig['path'] . "\n";

echo "\n   Portal Session:\n";
if ($portalSessionConfig) {
    echo "   - Driver: " . $portalSessionConfig['driver'] . "\n";
    echo "   - Cookie: " . $portalSessionConfig['cookie'] . "\n";
    echo "   - Domain: " . ($portalSessionConfig['domain'] ?? 'null') . "\n";
    echo "   - Secure: " . ($portalSessionConfig['secure'] ? 'true' : 'false') . "\n";
    echo "   - Path: " . $portalSessionConfig['path'] . "\n";
    echo "   - Files: " . $portalSessionConfig['files'] . "\n";
} else {
    echo "   - NICHT KONFIGURIERT!\n";
}

// 2. Session Service Provider Check
echo "\n2. Session Service Provider:\n";
$providers = config('app.providers');
$sessionProviders = array_filter($providers, function($p) {
    return stripos($p, 'session') !== false || stripos($p, 'portal') !== false;
});
foreach ($sessionProviders as $provider) {
    echo "   - " . $provider . "\n";
}

// 3. Middleware Check
echo "\n3. Business Portal Middleware:\n";
$kernel = app()->make(\Illuminate\Contracts\Http\Kernel::class);
$middleware = $kernel->getMiddlewareGroups();
if (isset($middleware['business-portal'])) {
    foreach ($middleware['business-portal'] as $mw) {
        echo "   - " . $mw . "\n";
        if (stripos($mw, 'session') !== false) {
            echo "     ^ SESSION RELATED\n";
        }
    }
}

// 4. Session Directory Check
echo "\n4. Session Directories:\n";
$sessionPath = storage_path('framework/sessions');
$portalSessionPath = storage_path('framework/sessions/portal');

echo "   - Standard: " . $sessionPath . " - ";
if (is_dir($sessionPath) && is_writable($sessionPath)) {
    echo "✓ EXISTS & WRITABLE\n";
} else {
    echo "✗ PROBLEM\n";
}

echo "   - Portal: " . $portalSessionPath . " - ";
if (is_dir($portalSessionPath) && is_writable($portalSessionPath)) {
    echo "✓ EXISTS & WRITABLE\n";
} else {
    echo "✗ PROBLEM\n";
    // Try to create it
    if (!is_dir($portalSessionPath)) {
        if (@mkdir($portalSessionPath, 0755, true)) {
            echo "     → Directory created successfully\n";
        } else {
            echo "     → Failed to create directory\n";
        }
    }
}

// 5. Test Session Start
echo "\n5. Test Session Start:\n";
try {
    // Simulate a request to business portal
    $request = \Illuminate\Http\Request::create(
        '/business/login',
        'GET',
        [],
        [],
        [],
        ['HTTP_HOST' => 'api.askproai.de', 'HTTPS' => 'on']
    );
    
    $response = $kernel->handle($request);
    
    echo "   - Response Status: " . $response->getStatusCode() . "\n";
    
    // Check Set-Cookie headers
    $cookies = $response->headers->getCookies();
    echo "   - Cookies Set: " . count($cookies) . "\n";
    foreach ($cookies as $cookie) {
        echo "     - " . $cookie->getName() . " (Domain: " . $cookie->getDomain() . ", Secure: " . ($cookie->isSecure() ? 'Yes' : 'No') . ")\n";
    }
    
    // Check session
    echo "\n   - Session ID: " . session()->getId() . "\n";
    echo "   - Session Driver: " . session()->getDefaultDriver() . "\n";
    
} catch (\Exception $e) {
    echo "   - ERROR: " . $e->getMessage() . "\n";
}

// 6. Route Registration Check
echo "\n6. Route Registration:\n";
$routes = \Route::getRoutes();
$businessRoutes = 0;
foreach ($routes as $route) {
    if (strpos($route->uri(), 'business/') === 0) {
        $businessRoutes++;
    }
}
echo "   - Business Portal Routes: " . $businessRoutes . "\n";

// 7. Auth Guard Check
echo "\n7. Auth Guards:\n";
$guards = config('auth.guards');
if (isset($guards['portal'])) {
    echo "   - Portal Guard: ✓\n";
    echo "     - Driver: " . $guards['portal']['driver'] . "\n";
    echo "     - Provider: " . $guards['portal']['provider'] . "\n";
} else {
    echo "   - Portal Guard: ✗ NOT CONFIGURED\n";
}

echo "\n=== DIAGNOSE ABGESCHLOSSEN ===\n";