<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Debugging Livewire Component Issue ===\n\n";

// 1. Check basic Livewire functionality
echo "1. Livewire Basic Check:\n";
echo "   - Livewire installed: " . (class_exists(\Livewire\Livewire::class) ? "✓" : "✗") . "\n";
echo "   - Version: " . \Composer\InstalledVersions::getVersion('livewire/livewire') . "\n\n";

// 2. Check if the update route is properly registered
echo "2. Livewire Update Route:\n";
$router = app('router');
$routes = $router->getRoutes();
$updateRoute = $routes->getByName('livewire.update');
if ($updateRoute) {
    echo "   - Route found: ✓\n";
    echo "   - Methods: " . implode(', ', $updateRoute->methods()) . "\n";
    echo "   - Action: " . $updateRoute->getActionName() . "\n";
    echo "   - Middleware: " . implode(', ', $updateRoute->middleware()) . "\n";
} else {
    echo "   - Route NOT found: ✗\n";
}
echo "\n";

// 3. Check Filament Livewire integration
echo "3. Filament Livewire Integration:\n";
$filamentInstalled = class_exists(\Filament\FilamentServiceProvider::class);
echo "   - Filament installed: " . ($filamentInstalled ? "✓" : "✗") . "\n";
if ($filamentInstalled) {
    echo "   - Filament version: " . \Composer\InstalledVersions::getVersion('filament/filament') . "\n";
}
echo "\n";

// 4. Test a simple Livewire component render
echo "4. Testing Simple Component Render:\n";
try {
    // Create a minimal test component
    $testComponent = new class extends \Livewire\Component {
        public function render() {
            return '<div>Test Component</div>';
        }
    };
    
    echo "   - Can create component instance: ✓\n";
    echo "   - Component class: " . get_class($testComponent) . "\n";
    
} catch (\Exception $e) {
    echo "   - Error creating component: " . $e->getMessage() . "\n";
}
echo "\n";

// 5. Check MLTrainingDashboardLivewire specific issues
echo "5. MLTrainingDashboardLivewire Check:\n";
$componentClass = 'App\\Filament\\Admin\\Pages\\MLTrainingDashboardLivewire';
if (class_exists($componentClass)) {
    echo "   - Class exists: ✓\n";
    
    // Check if it extends the right base class
    $reflection = new ReflectionClass($componentClass);
    $parent = $reflection->getParentClass();
    echo "   - Parent class: " . ($parent ? $parent->getName() : 'None') . "\n";
    
    // Check if it's a Filament page
    $isFilamentPage = $reflection->isSubclassOf(\Filament\Pages\Page::class);
    echo "   - Is Filament Page: " . ($isFilamentPage ? "✓" : "✗") . "\n";
    
    // Check required methods
    $requiredMethods = ['mount', 'startTraining', 'analyzeAllCalls', 'getHeaderActions'];
    foreach ($requiredMethods as $method) {
        $hasMethod = $reflection->hasMethod($method);
        echo "   - Has {$method}(): " . ($hasMethod ? "✓" : "✗") . "\n";
    }
} else {
    echo "   - Class does NOT exist: ✗\n";
}
echo "\n";

// 6. Check for common issues
echo "6. Common Issues Check:\n";

// Check session driver
$sessionDriver = config('session.driver');
echo "   - Session driver: {$sessionDriver}\n";
echo "   - Session path writable: " . (is_writable(storage_path('framework/sessions')) ? "✓" : "✗") . "\n";

// Check CSRF
echo "   - CSRF enabled: " . (config('app.env') !== 'testing' ? "✓" : "✗") . "\n";

// Check debug mode
echo "   - Debug mode: " . (config('app.debug') ? "ON" : "OFF") . "\n";

// Check for conflicting middleware
$webMiddleware = app('router')->getMiddlewareGroups()['web'] ?? [];
$hasConflicts = false;
foreach ($webMiddleware as $mw) {
    if (strpos($mw, 'ResponseWrapper') !== false || strpos($mw, 'EnsureProperResponseFormat') !== false) {
        $hasConflicts = true;
        echo "   - Conflicting middleware found: {$mw} ⚠️\n";
    }
}
if (!$hasConflicts) {
    echo "   - No conflicting middleware: ✓\n";
}

echo "\n=== Recommendations ===\n";
echo "Based on the checks above, here are potential issues:\n";
echo "1. If Livewire update route has wrong middleware, check AdminPanelProvider\n";
echo "2. If session path not writable, run: chmod -R 775 storage/framework/sessions\n";
echo "3. If conflicting middleware found, comment them out in AdminPanelProvider\n";
echo "4. Check browser console for JavaScript errors\n";
echo "5. Ensure CSRF token is being sent with requests\n";

echo "\n=== Debug Complete ===\n";