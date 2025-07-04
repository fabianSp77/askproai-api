<?php

use Livewire\Livewire;
use Livewire\Mechanisms\ComponentRegistry;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Testing Livewire Component Resolution ===\n\n";

// Test 1: Check if Livewire can resolve the component
echo "1. Component Resolution Test:\n";
$componentName = 'filament.admin.pages.m-l-training-dashboard-livewire';

try {
    // Try to get the component class
    $componentRegistry = app(ComponentRegistry::class);
    
    // Get the class name for the component
    $className = $componentRegistry->getClass($componentName);
    
    echo "   - Component name: {$componentName}\n";
    echo "   - Resolved class: {$className}\n";
    echo "   - Class exists: " . (class_exists($className) ? "✓" : "✗") . "\n";
    
} catch (\Exception $e) {
    echo "   - Error resolving component: " . $e->getMessage() . "\n";
    
    // Try alternative approach
    echo "\n2. Alternative Resolution:\n";
    
    // List all registered components
    try {
        // In Livewire v3, components are registered differently
        // Let's check the actual component class directly
        $componentClass = 'App\\Filament\\Admin\\Pages\\MLTrainingDashboardLivewire';
        
        if (class_exists($componentClass)) {
            echo "   - Component class exists directly: ✓\n";
            
            // Check if it's properly registered with Filament
            $filamentPage = new $componentClass();
            echo "   - Can instantiate: ✓\n";
            echo "   - Is Livewire component: " . ($filamentPage instanceof \Livewire\Component ? "✓" : "✗") . "\n";
            echo "   - Is Filament page: " . ($filamentPage instanceof \Filament\Pages\Page ? "✓" : "✗") . "\n";
            
            // Try to get the component's Livewire name
            $possibleNames = [
                'filament.admin.pages.m-l-training-dashboard-livewire',
                'filament.admin.pages.ml-training-dashboard-livewire',
                'admin.pages.m-l-training-dashboard-livewire',
                'ml-training-dashboard-livewire',
            ];
            
            echo "\n3. Testing possible component names:\n";
            foreach ($possibleNames as $name) {
                try {
                    $testRegistry = app(ComponentRegistry::class);
                    $resolvedClass = $testRegistry->getClass($name);
                    echo "   - {$name}: " . ($resolvedClass === $componentClass ? "✓ MATCHES!" : "✗ ({$resolvedClass})") . "\n";
                } catch (\Exception $e) {
                    echo "   - {$name}: ✗ Not registered\n";
                }
            }
        } else {
            echo "   - Component class does NOT exist!\n";
        }
    } catch (\Exception $e) {
        echo "   - Error: " . $e->getMessage() . "\n";
    }
}

echo "\n4. Checking Filament registration:\n";
try {
    $adminPanel = filament()->getPanel('admin');
    if ($adminPanel) {
        $pages = $adminPanel->getPages();
        foreach ($pages as $pageClass) {
            if (strpos($pageClass, 'MLTraining') !== false) {
                echo "   - Found: {$pageClass}\n";
                
                // Check if page has a specific Livewire component name
                if (method_exists($pageClass, 'getName')) {
                    $page = new $pageClass();
                    echo "     * Component name: " . $page->getName() . "\n";
                }
            }
        }
    }
} catch (\Exception $e) {
    echo "   - Error checking Filament: " . $e->getMessage() . "\n";
}

echo "\n=== Component Resolution Summary ===\n";
echo "The issue might be:\n";
echo "1. Component name mismatch between frontend and backend\n";
echo "2. Component not properly registered with Livewire\n";
echo "3. Filament using a different naming convention\n";
echo "4. Missing authentication/session when resolving component\n";

echo "\n=== Test Complete ===\n";