<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Prüfe RetellWebhookController ===\n\n";

// 1. Prüfe ob Controller existiert
$controllerPath = app_path('Http/Controllers/RetellWebhookController.php');
if (file_exists($controllerPath)) {
    echo "✅ RetellWebhookController.php existiert\n";
    
    // Zeige die processWebhook Methode
    $content = file_get_contents($controllerPath);
    
    // Suche nach der processWebhook Methode
    if (strpos($content, 'processWebhook') !== false) {
        echo "✅ processWebhook Methode gefunden\n\n";
        
        // Extrahiere relevante Teile
        if (strpos($content, '_datum__termin') !== false) {
            echo "✅ Controller verarbeitet _datum__termin\n";
        } else {
            echo "❌ Controller verarbeitet NICHT _datum__termin\n";
        }
        
        if (strpos($content, 'Appointment::create') !== false || strpos($content, 'new Appointment') !== false) {
            echo "✅ Controller erstellt Appointments\n";
        } else {
            echo "❌ Controller erstellt KEINE Appointments\n";
        }
    }
} else {
    echo "❌ RetellWebhookController.php nicht gefunden!\n";
}

// 2. Prüfe Route
echo "\n2. Prüfe Route:\n";
$routes = app('router')->getRoutes();
foreach ($routes as $route) {
    if (strpos($route->uri(), 'webhooks/retell') !== false) {
        echo "✅ Route gefunden: " . $route->methods()[0] . " " . $route->uri() . "\n";
        echo "   Controller: " . $route->getActionName() . "\n";
    }
}

echo "\n";
