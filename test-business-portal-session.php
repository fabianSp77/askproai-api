<?php
// Test Business Portal Session Isolation

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Create a fake request
$request = Illuminate\Http\Request::create('/business/test', 'GET');
$app->instance('request', $request);

// Bootstrap the app
$kernel->bootstrap();

// Get session config
$sessionConfig = config('session');
$portalConfig = config('session_portal');

echo "=== Business Portal Session Configuration ===\n";
echo "Default Session Cookie: " . ($sessionConfig['cookie'] ?? 'N/A') . "\n";
echo "Portal Session Cookie: " . ($portalConfig['cookie'] ?? 'N/A') . "\n";
echo "Default Session Path: " . ($sessionConfig['path'] ?? 'N/A') . "\n";
echo "Portal Session Path: " . ($portalConfig['path'] ?? 'N/A') . "\n";
echo "Default Session Table: " . ($sessionConfig['table'] ?? 'N/A') . "\n";
echo "Portal Session Table: " . ($portalConfig['table'] ?? 'N/A') . "\n";

// Test middleware configuration
echo "\n=== Middleware Groups ===\n";
$groups = $kernel->getMiddlewareGroups();
if (isset($groups['business-portal'])) {
    echo "Business Portal Middleware Group: YES\n";
    foreach ($groups['business-portal'] as $middleware) {
        echo "  - $middleware\n";
    }
} else {
    echo "Business Portal Middleware Group: NO\n";
}

if (isset($groups['business-api'])) {
    echo "\nBusiness API Middleware Group: YES\n";
    foreach ($groups['business-api'] as $middleware) {
        echo "  - $middleware\n";
    }
} else {
    echo "\nBusiness API Middleware Group: NO\n";
}

// Check CSRF exceptions
echo "\n=== CSRF Token Exceptions ===\n";
$csrfMiddleware = new \App\Http\Middleware\VerifyCsrfToken(
    $app,
    $app->make(\Illuminate\Contracts\Encryption\Encrypter::class)
);
$reflection = new ReflectionClass($csrfMiddleware);
$property = $reflection->getProperty('except');
$property->setAccessible(true);
$exceptions = $property->getValue($csrfMiddleware);

$businessExceptions = array_filter($exceptions, function($path) {
    return strpos($path, 'business') !== false;
});

if (!empty($businessExceptions)) {
    echo "Business Portal CSRF Exceptions:\n";
    foreach ($businessExceptions as $exception) {
        echo "  - $exception\n";
    }
} else {
    echo "No Business Portal CSRF exceptions found!\n";
}

echo "\n✅ Configuration test complete.\n";