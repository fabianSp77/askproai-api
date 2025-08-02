<?php

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

// Test the business login route
echo "<h1>Business Portal Login Debug</h1>";

try {
    // Check if route exists
    $route = app('router')->getRoutes()->match(
        app('request')->create('/business/login', 'GET')
    );
    
    echo "<p>✓ Route exists: " . $route->getName() . "</p>";
    echo "<p>Controller: " . $route->getActionName() . "</p>";
    
    // Check controller
    $controllerClass = $route->getControllerClass();
    if (class_exists($controllerClass)) {
        echo "<p>✓ Controller class exists: $controllerClass</p>";
        
        // Check method
        $method = $route->getActionMethod();
        if (method_exists($controllerClass, $method)) {
            echo "<p>✓ Method exists: $method</p>";
        } else {
            echo "<p>✗ Method missing: $method</p>";
        }
    } else {
        echo "<p>✗ Controller class missing: $controllerClass</p>";
    }
    
    // Check middleware
    $middleware = $route->middleware();
    echo "<h3>Middleware:</h3><ul>";
    foreach ($middleware as $m) {
        echo "<li>$m</li>";
    }
    echo "</ul>";
    
    // Test rendering the login form
    echo "<h3>Testing Login Form Render:</h3>";
    
    $controller = app($controllerClass);
    $response = $controller->$method();
    
    if ($response instanceof \Illuminate\View\View) {
        echo "<p>✓ View returned successfully</p>";
        echo "<p>View name: " . $response->getName() . "</p>";
        
        // Check if view file exists
        $viewPath = $response->getPath();
        if (file_exists($viewPath)) {
            echo "<p>✓ View file exists: " . basename($viewPath) . "</p>";
        } else {
            echo "<p>✗ View file missing: $viewPath</p>";
        }
    } else {
        echo "<p>Response type: " . get_class($response) . "</p>";
    }
    
} catch (\Exception $e) {
    echo "<h3 style='color: red;'>Error occurred:</h3>";
    echo "<pre style='background: #f5f5f5; padding: 10px;'>";
    echo "Exception: " . get_class($e) . "\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n\n";
    echo "Stack trace:\n";
    echo $e->getTraceAsString();
    echo "</pre>";
}

// Check session configuration
echo "<h3>Session Configuration:</h3>";
echo "<pre>";
print_r([
    'driver' => config('session.driver'),
    'lifetime' => config('session.lifetime'),
    'domain' => config('session.domain'),
    'secure' => config('session.secure'),
    'same_site' => config('session.same_site'),
]);
echo "</pre>";

// Check auth guards
echo "<h3>Auth Guards:</h3>";
echo "<pre>";
$guards = config('auth.guards');
foreach ($guards as $name => $config) {
    echo "Guard '$name': driver = {$config['driver']}, provider = {$config['provider']}\n";
}
echo "</pre>";