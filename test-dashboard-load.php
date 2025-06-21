<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Filament\Admin\Widgets\SystemHealthMonitor;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

echo "Testing Dashboard Load\n";
echo "=====================\n\n";

// Login as user
$user = User::where('email', 'fabian@askproai.de')->first();
if (!$user) {
    echo "❌ User not found\n";
    exit(1);
}

Auth::login($user);
echo "✅ Logged in as: {$user->email}\n";

// Test SystemHealthMonitor widget
try {
    $widget = new SystemHealthMonitor();
    
    // Initialize the widget
    $widget->mount();
    
    echo "✅ SystemHealthMonitor widget loaded successfully\n";
    
    // Check services
    $services = $widget->services;
    echo "\nServices Status:\n";
    foreach ($services as $key => $service) {
        echo "- {$service['name']}: {$service['status']}\n";
    }
    
    echo "\nOverall Status: {$widget->overallStatus}\n";
    
} catch (\Exception $e) {
    echo "❌ Error loading widget: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
}

Auth::logout();