<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

// Test if the OptimizedDashboard class can be loaded
try {
    echo "Testing OptimizedDashboard...\n\n";
    
    // Check if class exists
    $className = 'App\\Filament\\Admin\\Pages\\OptimizedDashboard';
    if (!class_exists($className)) {
        die("ERROR: Class $className does not exist!\n");
    }
    echo "✓ Class exists\n";
    
    // Check if it extends the right parent
    $reflection = new ReflectionClass($className);
    $parent = $reflection->getParentClass();
    echo "✓ Parent class: " . ($parent ? $parent->getName() : 'None') . "\n";
    
    // Check if view exists
    $viewPath = resource_path('views/filament/admin/pages/optimized-dashboard.blade.php');
    if (!file_exists($viewPath)) {
        die("ERROR: View file does not exist at: $viewPath\n");
    }
    echo "✓ View file exists\n";
    
    // Try to instantiate
    echo "\nTrying to instantiate...\n";
    $page = new $className();
    echo "✓ Instantiation successful\n";
    
    // Check properties
    echo "\nPage properties:\n";
    echo "- Navigation Icon: " . ($page::getNavigationIcon() ?? 'null') . "\n";
    echo "- Navigation Label: " . ($page::getNavigationLabel() ?? 'null') . "\n";
    echo "- Title: " . ($page::getTitle() ?? 'null') . "\n";
    
    // Check if route is registered
    echo "\nChecking routes...\n";
    $routes = app('router')->getRoutes();
    $found = false;
    foreach ($routes as $route) {
        if (str_contains($route->uri(), 'optimized-dashboard')) {
            echo "✓ Route found: " . $route->uri() . "\n";
            $found = true;
        }
    }
    if (!$found) {
        echo "⚠ No route found for optimized-dashboard\n";
    }
    
    echo "\nAll checks passed!\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "\nStack trace:\n";
    echo $e->getTraceAsString();
}