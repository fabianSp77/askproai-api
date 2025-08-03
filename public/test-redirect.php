<?php
// Simple redirect test

// Load Laravel
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Create a request
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

// Test auth
echo "=== Redirect Test ===\n";
echo "Request URL: " . $request->url() . "\n";
echo "Portal Auth Check: " . (Auth::guard('portal')->check() ? 'YES' : 'NO') . "\n";

// Test route generation
try {
    $loginUrl = route('business.login');
    $dashboardUrl = route('business.dashboard');
    
    echo "Login URL: $loginUrl\n";
    echo "Dashboard URL: $dashboardUrl\n";
    
    // Check if URLs are different
    if ($loginUrl === $dashboardUrl) {
        echo "ERROR: Login and Dashboard URLs are the same!\n";
    }
} catch (Exception $e) {
    echo "Route error: " . $e->getMessage() . "\n";
}

// Check middleware
$router = app('router');
$route = $router->getRoutes()->match($request);
if ($route) {
    echo "Current route: " . $route->getName() . "\n";
    echo "Middleware: " . implode(', ', $route->middleware()) . "\n";
}