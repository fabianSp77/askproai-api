<?php

use Illuminate\Support\Facades\Route;
use Livewire\Livewire;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== ML Training Dashboard Livewire Debug ===\n\n";

// Check if Livewire is properly installed
echo "1. Livewire Installation Check:\n";
echo "   - Livewire class exists: " . (class_exists(\Livewire\Livewire::class) ? "✓" : "✗") . "\n";
echo "   - Livewire version: " . (class_exists(\Livewire\Livewire::class) ? \Composer\InstalledVersions::getVersion('livewire/livewire') : 'N/A') . "\n\n";

// Check if the component is registered
echo "2. Component Registration:\n";
$componentClass = 'App\\Filament\\Admin\\Pages\\MLTrainingDashboardLivewire';
try {
    // In Livewire v3, check if component can be resolved
    $componentName = 'filament.admin.pages.m-l-training-dashboard-livewire';
    
    // Check if the class exists
    if (class_exists($componentClass)) {
        echo "   - Component class exists: ✓\n";
        echo "   - Component class: {$componentClass}\n";
        
        // Check if it's a valid Livewire component
        $reflection = new ReflectionClass($componentClass);
        $isLivewireComponent = $reflection->isSubclassOf(\Livewire\Component::class) || 
                               $reflection->isSubclassOf(\Filament\Pages\Page::class);
        echo "   - Is valid Livewire/Filament component: " . ($isLivewireComponent ? "✓" : "✗") . "\n";
        
        // Try to create an instance
        try {
            $instance = new $componentClass();
            echo "   - Can instantiate: ✓\n";
            echo "   - Expected component name: {$componentName}\n";
        } catch (\Exception $e) {
            echo "   - Cannot instantiate: " . $e->getMessage() . "\n";
        }
    } else {
        echo "   - Component class does NOT exist!\n";
    }
} catch (\Exception $e) {
    echo "   - Error checking components: " . $e->getMessage() . "\n";
}
echo "\n";

// Check routes
echo "3. Livewire Routes:\n";
$routes = Route::getRoutes();
foreach ($routes as $route) {
    if (strpos($route->uri(), 'livewire') !== false) {
        echo "   - {$route->methods()[0]} {$route->uri()} => " . ($route->getActionName() ?? 'Closure') . "\n";
    }
}
echo "\n";

// Check middleware
echo "4. Middleware Check:\n";
$webMiddleware = app('router')->getMiddlewareGroups()['web'] ?? [];
echo "   - Web middleware group:\n";
foreach ($webMiddleware as $middleware) {
    echo "     * {$middleware}\n";
}
echo "\n";

// Check session configuration
echo "5. Session Configuration:\n";
echo "   - Driver: " . config('session.driver') . "\n";
echo "   - Domain: " . config('session.domain') . "\n";
echo "   - Path: " . config('session.path') . "\n";
echo "   - Secure: " . (config('session.secure') ? 'Yes' : 'No') . "\n";
echo "   - Same Site: " . config('session.same_site') . "\n\n";

// Check CSRF token
echo "6. CSRF Token:\n";
echo "   - Token Key: " . config('session.token') . "\n";
echo "   - Can generate token: " . (function_exists('csrf_token') ? 'Yes' : 'No') . "\n\n";

// Check if Filament is handling the page correctly
echo "7. Filament Page Check:\n";
$adminPanel = filament()->getPanel('admin');
if ($adminPanel) {
    $pages = $adminPanel->getPages();
    foreach ($pages as $page) {
        if (strpos($page, 'MLTraining') !== false) {
            echo "   - Found page: {$page}\n";
            $pageInstance = new $page();
            echo "     * Has mount: " . (method_exists($pageInstance, 'mount') ? 'Yes' : 'No') . "\n";
            echo "     * View: " . ($pageInstance::getView() ?? 'N/A') . "\n";
        }
    }
} else {
    echo "   - Admin panel not found!\n";
}
echo "\n";

// Check for any error handlers that might interfere
echo "8. Error Handlers:\n";
$errorHandlers = error_get_last();
if ($errorHandlers) {
    echo "   - Last error: " . json_encode($errorHandlers) . "\n";
} else {
    echo "   - No recent errors\n";
}

echo "\n=== Debug Complete ===\n";