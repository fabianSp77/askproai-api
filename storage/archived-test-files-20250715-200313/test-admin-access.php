<?php
// Test Admin Access

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

// Check routes
$router = $app->make('router');
$routes = $router->getRoutes();

echo "=== Admin Routes ===\n";
foreach ($routes as $route) {
    $uri = $route->uri();
    if (strpos($uri, 'admin') !== false) {
        echo "URI: " . $uri . "\n";
        echo "Methods: " . implode(', ', $route->methods()) . "\n";
        echo "Action: " . $route->getActionName() . "\n";
        echo "---\n";
    }
}

// Check Filament
echo "\n=== Filament Check ===\n";
try {
    $panel = filament()->getPanel('admin');
    echo "Panel ID: " . $panel->getId() . "\n";
    echo "Panel Path: " . $panel->getPath() . "\n";
    
    // Check pages
    echo "\n=== Filament Pages ===\n";
    $pages = $panel->getPages();
    foreach ($pages as $page) {
        echo "Page: " . $page . "\n";
        if (method_exists($page, 'getSlug')) {
            echo "Slug: " . $page::getSlug() . "\n";
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

// Test specific routes
echo "\n=== Route Tests ===\n";
$testRoutes = ['/', '/admin', '/admin/login', '/admin/appointments'];
foreach ($testRoutes as $testRoute) {
    try {
        $request = Illuminate\Http\Request::create($testRoute);
        $response = $kernel->handle($request);
        echo "Route: " . $testRoute . " -> Status: " . $response->getStatusCode() . "\n";
    } catch (Exception $e) {
        echo "Route: " . $testRoute . " -> Error: " . $e->getMessage() . "\n";
    }
}