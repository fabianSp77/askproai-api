<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

echo "=== Testing Admin Auth API ===\n\n";

// Test 1: Check if routes are registered
echo "1. Checking API routes:\n";
$router = app('router');
$routes = $router->getRoutes();
$adminAuthRoute = null;

foreach ($routes as $route) {
    if ($route->uri() === 'api/admin/auth/login' && in_array('POST', $route->methods())) {
        $adminAuthRoute = $route;
        echo "   ✓ Found POST /api/admin/auth/login route\n";
        echo "   Controller: " . $route->getActionName() . "\n";
        echo "   Middleware: " . implode(', ', $route->middleware()) . "\n";
        break;
    }
}

if (!$adminAuthRoute) {
    echo "   ❌ Route /api/admin/auth/login NOT FOUND!\n";
}

// Test 2: Check AuthController
echo "\n2. Checking AuthController:\n";
$controllerClass = 'App\Http\Controllers\Admin\Api\AuthController';
if (class_exists($controllerClass)) {
    echo "   ✓ AuthController exists\n";
    $reflection = new ReflectionClass($controllerClass);
    $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
    echo "   Methods: ";
    foreach ($methods as $method) {
        if ($method->class === $controllerClass) {
            echo $method->name . " ";
        }
    }
    echo "\n";
} else {
    echo "   ❌ AuthController NOT FOUND\n";
}

// Test 3: Check User model methods
echo "\n3. Checking User model:\n";
$user = \App\Models\User::where('email', 'admin@askproai.de')->first();
if ($user) {
    echo "   ✓ User found\n";
    
    // Check if hasRole method exists
    if (method_exists($user, 'hasRole')) {
        echo "   ✓ hasRole method exists\n";
    } else {
        echo "   ❌ hasRole method NOT FOUND\n";
    }
    
    // Check if roles relationship exists
    try {
        $roles = $user->roles;
        echo "   ✓ roles relationship exists\n";
    } catch (\Exception $e) {
        echo "   ❌ roles relationship error: " . $e->getMessage() . "\n";
    }
}

// Test 4: Simulate login request
echo "\n4. Testing login directly:\n";
try {
    $authController = new $controllerClass();
    
    // Create a mock request
    $request = new \Illuminate\Http\Request([
        'email' => 'admin@askproai.de',
        'password' => 'admin123'
    ]);
    
    // Set the request in the container
    app()->instance('request', $request);
    
    $response = $authController->login($request);
    $content = json_decode($response->getContent(), true);
    
    echo "   Response status: " . $response->getStatusCode() . "\n";
    
    if ($response->getStatusCode() === 200) {
        echo "   ✓ Login successful!\n";
        echo "   Token: " . substr($content['token'] ?? 'N/A', 0, 20) . "...\n";
    } else {
        echo "   ❌ Login failed\n";
        echo "   Response: " . json_encode($content) . "\n";
    }
} catch (\Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "   Trace:\n" . $e->getTraceAsString() . "\n";
}

// Test 5: Check middleware issues
echo "\n5. Checking for middleware issues:\n";
$middlewareGroups = app(\Illuminate\Contracts\Http\Kernel::class)->getMiddlewareGroups();
if (isset($middlewareGroups['api'])) {
    echo "   API middleware group:\n";
    foreach ($middlewareGroups['api'] as $middleware) {
        echo "   - " . $middleware . "\n";
    }
}

$kernel->terminate($request, $response);