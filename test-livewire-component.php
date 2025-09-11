<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

echo "=== TESTING RETELL AGENT LIVEWIRE SETUP ===\n\n";

// Test if Livewire component exists
echo "1. Checking Livewire Component:\n";
$componentClass = \App\Livewire\RetellAgentViewer::class;
if (class_exists($componentClass)) {
    echo "   ✓ RetellAgentViewer class exists\n";
    
    // Test instantiation
    try {
        $component = new $componentClass();
        echo "   ✓ Component can be instantiated\n";
        
        // Test mount method
        $component->mount(135);
        echo "   ✓ Component mount() works\n";
        echo "   ✓ Agent loaded: " . $component->agent->name . "\n";
    } catch (\Exception $e) {
        echo "   ✗ Error: " . $e->getMessage() . "\n";
    }
} else {
    echo "   ✗ RetellAgentViewer class NOT found\n";
}

// Test ViewRetellAgent page
echo "\n2. Checking ViewRetellAgent Page:\n";
$pageClass = \App\Filament\Admin\Resources\RetellAgentResource\Pages\ViewRetellAgent::class;
if (class_exists($pageClass)) {
    echo "   ✓ ViewRetellAgent class exists\n";
    echo "   ✓ Extends: " . get_parent_class($pageClass) . "\n";
    
    // Check view path
    $page = new $pageClass();
    $viewPath = (new ReflectionClass($page))->getProperty('view');
    $viewPath->setAccessible(true);
    echo "   ✓ View path: " . $viewPath->getValue($page) . "\n";
} else {
    echo "   ✗ ViewRetellAgent class NOT found\n";
}

// Test blade templates exist
echo "\n3. Checking Blade Templates:\n";
$viewPath1 = resource_path('views/filament/admin/resources/retell-agent-resource/pages/view-retell-agent.blade.php');
$viewPath2 = resource_path('views/livewire/retell-agent-viewer.blade.php');

echo "   Filament view: " . (file_exists($viewPath1) ? '✓ EXISTS' : '✗ NOT FOUND') . "\n";
echo "   Livewire view: " . (file_exists($viewPath2) ? '✓ EXISTS' : '✗ NOT FOUND') . "\n";

// Test Livewire registration
echo "\n4. Checking Livewire Registration:\n";
$livewireManager = app('livewire');
$components = $livewireManager->getComponentsRegistry();
echo "   Total registered components: " . count($components) . "\n";

// Check if our component is registered
$registered = false;
foreach ($components as $name => $class) {
    if ($class === \App\Livewire\RetellAgentViewer::class) {
        echo "   ✓ RetellAgentViewer registered as: " . $name . "\n";
        $registered = true;
        break;
    }
}
if (!$registered) {
    echo "   ! RetellAgentViewer not found in registry\n";
    echo "   ! May need manual registration\n";
}

echo "\n=== TEST COMPLETE ===\n";