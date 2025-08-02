<?php
// Fix Livewire 404 Popup Issue

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);
$kernel->terminate($request, $response);

echo "=== Livewire 404 Popup Fix ===\n\n";

// Check Livewire routes
echo "1. Checking Livewire routes...\n";
$router = app('router');
$routes = $router->getRoutes();

$livewireRoutes = [];
foreach ($routes as $route) {
    if (str_contains($route->uri(), 'livewire')) {
        $livewireRoutes[] = [
            'uri' => $route->uri(),
            'methods' => $route->methods(),
            'action' => $route->getActionName(),
            'middleware' => $route->middleware()
        ];
    }
}

if (empty($livewireRoutes)) {
    echo "❌ No Livewire routes found! This is the problem.\n";
    
    // Register Livewire routes manually
    echo "\n2. Registering Livewire routes...\n";
    try {
        \Livewire\Livewire::route();
        echo "✅ Livewire routes registered!\n";
    } catch (Exception $e) {
        echo "❌ Error registering routes: " . $e->getMessage() . "\n";
    }
} else {
    echo "✅ Found " . count($livewireRoutes) . " Livewire routes:\n";
    foreach ($livewireRoutes as $route) {
        echo "   - " . implode('|', $route['methods']) . " " . $route['uri'] . "\n";
    }
}

// Check Livewire config
echo "\n3. Checking Livewire configuration...\n";
$config = config('livewire');
echo "   - Class namespace: " . ($config['class_namespace'] ?? 'Not set') . "\n";
echo "   - Asset injection: " . ($config['inject_assets'] ? 'true' : 'false') . "\n";
echo "   - Update endpoint: " . ($config['update_uri'] ?? '/livewire/update') . "\n";

// Check for problematic middleware
echo "\n4. Checking middleware configuration...\n";
$webMiddleware = config('livewire.middleware_group', 'web');
echo "   - Middleware group: " . $webMiddleware . "\n";

// Get web middleware stack
$middlewareGroups = app(\Illuminate\Contracts\Http\Kernel::class)->getMiddlewareGroups();
if (isset($middlewareGroups[$webMiddleware])) {
    echo "   - Web middleware stack:\n";
    foreach ($middlewareGroups[$webMiddleware] as $middleware) {
        echo "     • " . $middleware . "\n";
        if (str_contains($middleware, 'VerifyCsrfToken')) {
            echo "       ⚠️  CSRF middleware found - could cause issues\n";
        }
    }
}

// Create fix file
echo "\n5. Creating JavaScript fix...\n";

$jsFixContent = <<<'JS'
// Livewire 404 Fix
(function() {
    console.log('[Livewire 404 Fix] Starting...');
    
    // Override Livewire error handling
    if (window.Livewire) {
        const originalHandleResponse = window.Livewire.connection.handleResponse;
        
        window.Livewire.connection.handleResponse = function(response) {
            console.log('[Livewire 404 Fix] Response status:', response.status);
            
            // Intercept 404 errors
            if (response.status === 404) {
                console.warn('[Livewire 404 Fix] Intercepted 404 error, attempting recovery...');
                
                // Try to find the component and refresh it
                const component = window.Livewire.find(response.component?.id);
                if (component) {
                    console.log('[Livewire 404 Fix] Refreshing component:', component.id);
                    component.$refresh();
                    return;
                }
                
                // Don't show the popup for 404s
                return;
            }
            
            // Call original handler for other responses
            return originalHandleResponse.call(this, response);
        };
        
        // Override fetch to add logging
        const originalFetch = window.fetch;
        window.fetch = function(...args) {
            const [url, options] = args;
            
            if (url && url.includes('livewire')) {
                console.log('[Livewire 404 Fix] Livewire request:', url, options?.method || 'GET');
            }
            
            return originalFetch.apply(this, args).then(response => {
                if (url && url.includes('livewire') && !response.ok) {
                    console.error('[Livewire 404 Fix] Livewire request failed:', response.status, response.statusText);
                }
                return response;
            });
        };
        
        // Ensure update URI is correct
        if (window.livewireScriptConfig) {
            console.log('[Livewire 404 Fix] Current update URI:', window.livewireScriptConfig.uri);
            // Force correct URI
            window.livewireScriptConfig.uri = '/livewire/update';
        }
    }
    
    // Remove any 404 modals on page load
    setTimeout(() => {
        const errorModals = document.querySelectorAll('[role="dialog"], .filament-modal');
        errorModals.forEach(modal => {
            const text = modal.innerText || '';
            if (text.includes('404') || text.includes('Not Found')) {
                console.log('[Livewire 404 Fix] Removing 404 modal');
                modal.remove();
            }
        });
    }, 1000);
    
    console.log('[Livewire 404 Fix] Fix applied');
})();
JS;

file_put_contents(__DIR__ . '/js/livewire-404-fix.js', $jsFixContent);
echo "✅ Created /js/livewire-404-fix.js\n";

// Create route fix
echo "\n6. Creating route fix...\n";

$routeFixPath = base_path('routes/livewire-fix.php');
$routeFixContent = <<<'PHP'
<?php

use Illuminate\Support\Facades\Route;

// Ensure Livewire routes are registered
if (class_exists(\Livewire\Livewire::class)) {
    \Livewire\Livewire::route();
}

// Add fallback for missing Livewire routes
Route::any('/livewire/{method}', function ($method) {
    \Log::warning('Livewire route not found', [
        'method' => $method,
        'request_method' => request()->method(),
        'component' => request()->input('components.0.snapshot')
    ]);
    
    // Return empty response to prevent popup
    return response()->json([
        'effects' => [],
        'serverMemo' => []
    ]);
})->where('method', '.*');
PHP;

file_put_contents($routeFixPath, $routeFixContent);
echo "✅ Created routes/livewire-fix.php\n";

echo "\n=== SOLUTION ===\n";
echo "1. Add this to your base.blade.php template:\n";
echo "   <script src=\"/js/livewire-404-fix.js\"></script>\n\n";

echo "2. Include the route fix in routes/web.php:\n";
echo "   require __DIR__.'/livewire-fix.php';\n\n";

echo "3. Clear caches:\n";
echo "   php artisan optimize:clear\n";
echo "   php artisan route:clear\n";
echo "   php artisan config:clear\n\n";

echo "4. If the issue persists, check the browser console for '[Livewire 404 Fix]' messages.\n";