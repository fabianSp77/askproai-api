<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

use Illuminate\Support\Facades\Auth;

// Login
$user = \App\Models\User::where('email', 'admin@askproai.de')->first();
Auth::login($user);

// Test Livewire component directly
echo "=== Testing Calls Page Livewire Component ===\n\n";

// 1. Check if we can create the component
try {
    $component = app()->make(\App\Filament\Admin\Resources\CallResource\Pages\ListCalls::class);
    echo "✅ Component created successfully\n";
    
    // Set required properties
    if (method_exists($component, 'bootIfNotBooted')) {
        $component->bootIfNotBooted();
    }
    
    echo "Component class: " . get_class($component) . "\n";
    echo "Is Livewire component: " . ($component instanceof \Livewire\Component ? 'YES' : 'NO') . "\n";
    
} catch (\Exception $e) {
    echo "❌ Error creating component: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

// 2. Test Livewire manager
echo "\n=== Livewire Manager ===\n";
$livewire = app('livewire');
echo "Livewire instance: " . get_class($livewire) . "\n";

// 3. Check registered components
echo "\n=== Registered Components ===\n";
$components = $livewire->getComponentAliases();
$filamentComponents = array_filter($components, function($class) {
    return str_contains($class, 'Filament');
});

echo "Total components: " . count($components) . "\n";
echo "Filament components: " . count($filamentComponents) . "\n";

// Look for our component
$callsComponent = array_search(\App\Filament\Admin\Resources\CallResource\Pages\ListCalls::class, $components);
if ($callsComponent) {
    echo "✅ ListCalls registered as: " . $callsComponent . "\n";
} else {
    echo "❌ ListCalls NOT registered\n";
    
    // Try to find similar
    $similar = array_filter($components, function($class) {
        return str_contains($class, 'CallResource') || str_contains($class, 'ListCalls');
    });
    
    if (!empty($similar)) {
        echo "Similar components found:\n";
        foreach ($similar as $alias => $class) {
            echo "  - $alias => $class\n";
        }
    }
}

// 4. Test rendering
echo "\n=== Test Rendering ===\n";
try {
    // Create a mock request for the calls page
    $pageRequest = \Illuminate\Http\Request::create('/admin/calls', 'GET');
    $pageRequest->setUserResolver(function() use ($user) {
        return $user;
    });
    
    // Try to handle it
    $router = app('router');
    $routes = $router->getRoutes();
    $route = $routes->match($pageRequest);
    
    if ($route) {
        echo "✅ Route found: " . $route->uri() . "\n";
        echo "Action: " . $route->getActionName() . "\n";
    } else {
        echo "❌ No route found for /admin/calls\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Routing error: " . $e->getMessage() . "\n";
}

echo "\n=== Done ===\n";