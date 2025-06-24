<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Test if Livewire is properly registered
echo "Livewire version: " . \Livewire\Livewire::VERSION . "\n";

// Check if Livewire routes are registered
$router = app('router');
$routes = $router->getRoutes();
foreach ($routes as $route) {
    if (str_contains($route->uri(), 'livewire')) {
        echo "Livewire route: " . $route->methods()[0] . " " . $route->uri() . "\n";
    }
}

// Check session driver
echo "\nSession driver: " . config('session.driver') . "\n";
echo "Session path: " . session_save_path() . "\n";

// Check CSRF middleware
echo "\nCSRF enabled: " . (config('app.csrf_protection', true) ? 'Yes' : 'No') . "\n";
