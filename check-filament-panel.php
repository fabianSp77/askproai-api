<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(\Illuminate\Http\Request::capture());

// Check Filament admin panel
$panel = \Filament\Facades\Filament::getPanel('admin');
echo "Panel Configuration:\n";
echo "- Path: " . $panel->getPath() . "\n";
echo "- Login path: " . $panel->getLoginRouteSlug() . "\n";
echo "- Auth guard: " . $panel->getAuthGuard() . "\n\n";

// Check middleware
$middleware = $panel->getMiddleware();
echo "Panel Middleware:\n";
foreach ($middleware as $mw) {
    echo "- " . $mw . "\n";
}

// Check if calls resource is registered
echo "\nCall-related Resources:\n";
$resources = $panel->getResources();
foreach ($resources as $resource) {
    if (stripos($resource, 'Call') !== false) {
        echo "- " . $resource . "\n";
        
        // Check if it has pages
        $pages = $resource::getPages();
        foreach ($pages as $name => $page) {
            echo "  Page: " . $name . "\n";
        }
    }
}

// Check session configuration
echo "\nSession Configuration:\n";
echo "- Driver: " . config('session.driver') . "\n";
echo "- Cookie: " . config('session.cookie') . "\n";
echo "- Domain: " . config('session.domain') . "\n";
echo "- Secure: " . (config('session.secure') ? 'YES' : 'NO') . "\n";
echo "- SameSite: " . config('session.same_site') . "\n";