<?php
// Test Filament Login URL
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Testing Filament Login URL ===\n\n";

try {
    // Check if Filament is loaded
    if (class_exists('Filament\Facades\Filament')) {
        echo "✓ Filament loaded\n";
        
        // Try to get the login URL
        try {
            $loginUrl = \Filament\Facades\Filament::getLoginUrl();
            echo "✓ Login URL: $loginUrl\n";
        } catch (Exception $e) {
            echo "✗ Error getting login URL: " . $e->getMessage() . "\n";
            echo "  File: " . $e->getFile() . ":" . $e->getLine() . "\n";
        }
        
        // Check current panel
        try {
            $panel = \Filament\Facades\Filament::getCurrentPanel();
            if ($panel) {
                echo "✓ Current panel: " . $panel->getId() . "\n";
                echo "  Path: " . $panel->getPath() . "\n";
            } else {
                echo "✗ No current panel\n";
            }
        } catch (Exception $e) {
            echo "✗ Error getting current panel: " . $e->getMessage() . "\n";
        }
        
        // Get panel by ID
        try {
            $adminPanel = \Filament\Facades\Filament::getPanel('admin');
            if ($adminPanel) {
                echo "✓ Admin panel found\n";
                echo "  Has login: " . ($adminPanel->hasLogin() ? 'Yes' : 'No') . "\n";
                
                // Try to get login route
                $loginRouteName = 'filament.admin.auth.login';
                if (\Route::has($loginRouteName)) {
                    echo "✓ Login route exists: " . route($loginRouteName) . "\n";
                } else {
                    echo "✗ Login route not found\n";
                }
            }
        } catch (Exception $e) {
            echo "✗ Error getting admin panel: " . $e->getMessage() . "\n";
        }
        
    } else {
        echo "✗ Filament not loaded\n";
    }
    
    // Check routes
    echo "\n=== Checking Routes ===\n";
    $routes = [
        'filament.admin.auth.login',
        'filament.admin.pages.dashboard',
        'admin.login',
        'login'
    ];
    
    foreach ($routes as $routeName) {
        if (\Route::has($routeName)) {
            $route = \Route::getRoutes()->getByName($routeName);
            echo "✓ $routeName -> " . $route->uri() . "\n";
        } else {
            echo "✗ $routeName not found\n";
        }
    }
    
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "\nStack Trace:\n";
    echo $e->getTraceAsString() . "\n";
}

echo "\n=== Test Complete ===\n";