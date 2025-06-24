<?php
/**
 * Livewire Diagnostic Script
 * Run this to check Livewire integration status
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

echo "<!DOCTYPE html>\n";
echo "<html><head><title>Livewire Diagnostic</title></head><body>\n";
echo "<h1>Livewire Integration Diagnostic</h1>\n";
echo "<pre>\n";

// Check Livewire installation
echo "1. Livewire Installation Check:\n";
echo "   - Livewire installed: " . (class_exists('\Livewire\Livewire') ? '✓ Yes' : '✗ No') . "\n";
if (class_exists('\Livewire\Livewire')) {
    echo "   - Livewire version: " . \Composer\InstalledVersions::getVersion('livewire/livewire') . "\n";
}

// Check Livewire service provider
echo "\n2. Service Provider Check:\n";
$providers = app()->getLoadedProviders();
$livewireProvider = false;
foreach ($providers as $provider => $status) {
    if (strpos($provider, 'Livewire') !== false) {
        echo "   - $provider: " . ($status ? '✓ Loaded' : '✗ Not Loaded') . "\n";
        $livewireProvider = true;
    }
}
if (!$livewireProvider) {
    echo "   - ⚠ No Livewire service provider found\n";
}

// Check Livewire routes
echo "\n3. Livewire Routes Check:\n";
$routes = app('router')->getRoutes();
$livewireRoutes = 0;
foreach ($routes as $route) {
    if (strpos($route->uri(), 'livewire') !== false) {
        echo "   - " . $route->methods()[0] . " /" . $route->uri() . "\n";
        $livewireRoutes++;
    }
}
echo "   Total Livewire routes: $livewireRoutes\n";

// Check Livewire components
echo "\n4. Registered Components:\n";
$componentsDir = app_path('Livewire');
if (is_dir($componentsDir)) {
    $components = glob($componentsDir . '/*.php');
    foreach ($components as $component) {
        $componentName = basename($component, '.php');
        echo "   - $componentName\n";
    }
} else {
    echo "   - ⚠ No Livewire directory found at app/Livewire\n";
}

// Check configuration
echo "\n5. Configuration Check:\n";
echo "   - Class namespace: " . config('livewire.class_namespace', 'Not set') . "\n";
echo "   - View path: " . config('livewire.view_path', 'Not set') . "\n";
echo "   - Layout: " . config('livewire.layout', 'Not set') . "\n";
echo "   - Inject assets: " . (config('livewire.inject_assets', false) ? '✓ Yes' : '✗ No') . "\n";

// Check session configuration
echo "\n6. Session Configuration:\n";
echo "   - Driver: " . config('session.driver') . "\n";
echo "   - Cookie name: " . config('session.cookie') . "\n";
echo "   - Same site: " . config('session.same_site') . "\n";
echo "   - Secure: " . (config('session.secure') ? 'Yes' : 'No') . "\n";

// Check middleware
echo "\n7. Middleware Check:\n";
$kernel = app(\Illuminate\Contracts\Http\Kernel::class);
$middleware = $kernel->getMiddleware();
foreach ($middleware as $m) {
    if (strpos($m, 'Livewire') !== false || strpos($m, 'livewire') !== false) {
        echo "   - $m\n";
    }
}

// Check published assets
echo "\n8. Published Assets:\n";
$assetsDir = public_path('vendor/livewire');
if (is_dir($assetsDir)) {
    $assets = glob($assetsDir . '/*');
    foreach ($assets as $asset) {
        echo "   - " . basename($asset) . " (" . filesize($asset) . " bytes)\n";
    }
} else {
    echo "   - ⚠ No published assets found\n";
}

echo "\n</pre>\n";
echo "</body></html>\n";

$kernel->terminate($request, $response);