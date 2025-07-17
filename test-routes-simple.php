<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$kernel->handle($request);

echo "=== Admin API Routes Check ===\n\n";

$router = app('router');
$routes = $router->getRoutes();

$adminRoutes = [];
foreach ($routes as $route) {
    if (strpos($route->uri(), 'api/admin') !== false) {
        $adminRoutes[] = [
            'uri' => $route->uri(),
            'methods' => implode('|', $route->methods()),
            'action' => $route->getActionName()
        ];
    }
}

if (empty($adminRoutes)) {
    echo "❌ No admin API routes found!\n\n";
    
    // Try to manually include the routes
    echo "Attempting to manually load admin routes...\n";
    
    $adminRoutesFile = base_path('routes/api-admin.php');
    if (file_exists($adminRoutesFile)) {
        echo "✓ Found api-admin.php file\n";
        
        // Check if we can include it
        try {
            Route::prefix('api/admin')->group($adminRoutesFile);
            echo "✓ Routes loaded successfully\n";
            
            // Check again
            $routes = $router->getRoutes();
            foreach ($routes as $route) {
                if (strpos($route->uri(), 'api/admin') !== false) {
                    $adminRoutes[] = [
                        'uri' => $route->uri(),
                        'methods' => implode('|', $route->methods()),
                        'action' => $route->getActionName()
                    ];
                }
            }
        } catch (\Exception $e) {
            echo "❌ Error loading routes: " . $e->getMessage() . "\n";
        }
    } else {
        echo "❌ api-admin.php file not found!\n";
    }
}

echo "\nFound " . count($adminRoutes) . " admin routes:\n\n";
foreach (array_slice($adminRoutes, 0, 10) as $route) {
    echo $route['methods'] . " " . $route['uri'] . "\n";
    echo "   -> " . $route['action'] . "\n";
}

if (count($adminRoutes) > 10) {
    echo "\n... and " . (count($adminRoutes) - 10) . " more routes\n";
}

// Specifically check for auth/login
$loginRouteFound = false;
foreach ($adminRoutes as $route) {
    if ($route['uri'] === 'api/admin/auth/login' && strpos($route['methods'], 'POST') !== false) {
        $loginRouteFound = true;
        echo "\n✓ Login route found: " . $route['action'] . "\n";
        break;
    }
}

if (!$loginRouteFound) {
    echo "\n❌ Login route NOT found!\n";
}

$kernel->terminate($request, $response);