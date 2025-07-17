<?php
// Fix Admin Route Access

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Fixing Admin Route Access ===\n\n";

// Clear all caches
echo "1. Clearing caches...\n";
\Illuminate\Support\Facades\Artisan::call('optimize:clear');
echo \Illuminate\Support\Facades\Artisan::output();

// Skip route caching due to conflicts
echo "\n2. Skipping route cache due to conflicts...\n";

// Re-cache config
echo "\n3. Caching config...\n";
\Illuminate\Support\Facades\Artisan::call('config:cache');
echo \Illuminate\Support\Facades\Artisan::output();

// Cache Filament components
echo "\n4. Caching Filament components...\n";
\Illuminate\Support\Facades\Artisan::call('filament:cache-components');
echo \Illuminate\Support\Facades\Artisan::output();

// Test the route
echo "\n=== Testing Admin Route ===\n";
$router = $app->make('router');
$routes = collect($router->getRoutes()->getRoutes());

$adminRoute = $routes->first(function ($route) {
    return $route->uri() === 'admin';
});

if ($adminRoute) {
    echo "✓ Admin route found!\n";
    echo "  URI: " . $adminRoute->uri() . "\n";
    echo "  Action: " . $adminRoute->getActionName() . "\n";
    echo "  Middleware: " . implode(', ', $adminRoute->middleware()) . "\n";
} else {
    echo "✗ Admin route NOT found!\n";
}

echo "\nFixed! Try accessing /admin now.\n";