<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

// Test API endpoints
echo "Testing Admin API Endpoints:\n";
echo "============================\n\n";

// Test 1: Companies endpoint without auth
$testRequest = \Illuminate\Http\Request::create('/api/admin/companies', 'GET');
$testResponse = $kernel->handle($testRequest);
echo "1. GET /api/admin/companies (no auth):\n";
echo "   Status: " . $testResponse->getStatusCode() . "\n";
echo "   Content: " . substr($testResponse->getContent(), 0, 200) . "...\n\n";

// Test 2: Check if route exists
$router = app('router');
$routes = $router->getRoutes();
echo "2. Admin API Routes:\n";
foreach ($routes as $route) {
    if (strpos($route->uri(), 'api/admin') !== false) {
        echo "   - " . $route->methods()[0] . " " . $route->uri() . "\n";
    }
}

// Test 3: Check middleware
echo "\n3. Route Middleware:\n";
$route = $router->getRoutes()->match($testRequest);
if ($route) {
    echo "   Route found: " . $route->uri() . "\n";
    echo "   Middleware: " . implode(', ', $route->middleware()) . "\n";
    echo "   Controller: " . $route->getActionName() . "\n";
}

// Test 4: Check if CompanyController exists
echo "\n4. Controller Check:\n";
$controllerClass = 'App\Http\Controllers\Admin\Api\CompanyController';
if (class_exists($controllerClass)) {
    echo "   ✓ CompanyController exists\n";
    $controller = new $controllerClass();
    echo "   Methods: " . implode(', ', get_class_methods($controller)) . "\n";
} else {
    echo "   ✗ CompanyController NOT FOUND\n";
}

// Test 5: Direct controller call
echo "\n5. Direct Controller Test:\n";
try {
    $controller = app($controllerClass);
    $request = new \Illuminate\Http\Request();
    $response = $controller->index($request);
    echo "   ✓ Controller->index() callable\n";
    echo "   Response type: " . get_class($response) . "\n";
} catch (\Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

$kernel->terminate($request, $response);