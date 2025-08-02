#!/usr/bin/env php
<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);

// Create a request
$request = \Illuminate\Http\Request::create('/business/api/customers', 'GET');
$request->headers->set('Accept', 'application/json');

echo "🔍 Debugging Customer API Route...\n";
echo "=================================\n";

// Check if route exists
$router = $app->make('router');
$routes = $router->getRoutes();

echo "\n1. Checking if route is registered:\n";
$found = false;
foreach ($routes as $route) {
    if (str_contains($route->uri(), 'business/api/customers')) {
        echo "✅ Found route: " . $route->uri() . "\n";
        echo "   Methods: " . implode(', ', $route->methods()) . "\n";
        echo "   Action: " . $route->getActionName() . "\n";
        echo "   Name: " . ($route->getName() ?: 'unnamed') . "\n";
        echo "   Middleware: " . implode(', ', $route->middleware()) . "\n";
        $found = true;
    }
}

if (!$found) {
    echo "❌ Route not found!\n";
}

echo "\n2. Attempting to resolve controller:\n";
try {
    $controller = $app->make(\App\Http\Controllers\Portal\Api\CustomersApiController::class);
    echo "✅ Controller resolved successfully\n";
    
    // Check if index method exists
    if (method_exists($controller, 'index')) {
        echo "✅ index() method exists\n";
    } else {
        echo "❌ index() method missing!\n";
    }
} catch (\Exception $e) {
    echo "❌ Controller error: " . $e->getMessage() . "\n";
}

echo "\n3. Simulating request:\n";
try {
    // Try to handle the request
    $response = $kernel->handle($request);
    echo "Response Status: " . $response->getStatusCode() . "\n";
    
    if ($response->getStatusCode() == 500) {
        echo "❌ 500 Error detected!\n";
        
        // Try to get error details
        $content = $response->getContent();
        if (strpos($content, 'message') !== false) {
            $data = json_decode($content, true);
            if (isset($data['message'])) {
                echo "Error message: " . $data['message'] . "\n";
            }
        }
    }
} catch (\Exception $e) {
    echo "❌ Request handling error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n4. Checking error log:\n";
$logFile = storage_path('logs/laravel.log');
if (file_exists($logFile)) {
    $lastLines = `tail -100 "$logFile" | grep -A 20 -B 5 "CustomersApiController" | tail -50`;
    if ($lastLines) {
        echo "Recent errors:\n" . $lastLines . "\n";
    } else {
        echo "No recent errors found for CustomersApiController\n";
    }
}