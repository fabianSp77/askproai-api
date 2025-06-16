<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Route;

echo "=== Webhook URLs ===\n\n";

$routes = Route::getRoutes();
foreach ($routes as $route) {
    if (strpos($route->uri(), 'webhook') !== false || strpos($route->uri(), 'retell') !== false) {
        echo "Method: " . implode('|', $route->methods()) . "\n";
        echo "URI: " . $route->uri() . "\n";
        echo "Action: " . $route->getActionName() . "\n";
        echo "---\n";
    }
}
