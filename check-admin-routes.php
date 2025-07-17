<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Check if route exists
$router = app('router');
$routes = $router->getRoutes();

echo "Looking for admin-api routes:\n";
$found = false;
foreach ($routes as $route) {
    if (strpos($route->uri(), 'admin-api') !== false) {
        echo $route->methods()[0] . ' ' . $route->uri() . ' -> ' . $route->getActionName() . "\n";
        $found = true;
    }
}

if (!$found) {
    echo "No admin-api routes found!\n";
    
    // Check if the route file exists
    $routeFile = base_path('routes/api-admin.php');
    if (file_exists($routeFile)) {
        echo "Route file exists at: $routeFile\n";
    } else {
        echo "Route file NOT FOUND at: $routeFile\n";
    }
}