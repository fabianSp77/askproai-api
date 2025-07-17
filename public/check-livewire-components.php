<?php
// Check which Livewire components are causing 404 errors

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);
$kernel->terminate($request, $response);

echo "=== Livewire Component Check ===\n\n";

// Get all registered Livewire components
echo "1. Registered Livewire Components:\n";
$components = \Livewire\Livewire::getComponents();
foreach ($components as $alias => $class) {
    echo "   - {$alias} => {$class}\n";
}

// Check specific pages that have issues
echo "\n2. Checking problematic pages:\n";

$pages = [
    'App\Filament\Admin\Pages\OperationsDashboard',
    'App\Filament\Admin\Resources\CallResource',
    'App\Filament\Admin\Resources\AppointmentResource',
];

foreach ($pages as $pageClass) {
    if (class_exists($pageClass)) {
        echo "\n   ✅ {$pageClass} exists\n";
        
        // Check if it has any widgets
        if (method_exists($pageClass, 'getWidgets')) {
            try {
                $widgets = $pageClass::getWidgets();
                echo "      Widgets: " . count($widgets) . "\n";
                foreach ($widgets as $widget) {
                    echo "      - {$widget}\n";
                    if (!class_exists($widget)) {
                        echo "        ❌ Widget class not found!\n";
                    }
                }
            } catch (Exception $e) {
                echo "      ❌ Error getting widgets: " . $e->getMessage() . "\n";
            }
        }
    } else {
        echo "\n   ❌ {$pageClass} NOT FOUND\n";
    }
}

// Check recent logs for 404 errors
echo "\n3. Recent 404 errors in logs:\n";
$logFile = storage_path('logs/laravel.log');
if (file_exists($logFile)) {
    $logs = file_get_contents($logFile);
    preg_match_all('/404.*livewire.*?"([^"]+)"/', $logs, $matches);
    
    if (!empty($matches[1])) {
        $unique404s = array_unique($matches[1]);
        foreach ($unique404s as $url) {
            echo "   - {$url}\n";
        }
    } else {
        echo "   No recent 404 errors found in logs\n";
    }
}

// Check for common issues
echo "\n4. Common Issues Check:\n";

// Check if Livewire assets are published
$livewireJs = public_path('vendor/livewire/livewire.js');
if (file_exists($livewireJs)) {
    echo "   ✅ Livewire assets published\n";
} else {
    echo "   ❌ Livewire assets NOT published! Run: php artisan livewire:publish --assets\n";
}

// Check Livewire configuration
$updateUri = config('livewire.update_uri', '/livewire/update');
echo "   - Livewire update URI: {$updateUri}\n";

// Check if there are any polling components
echo "\n5. Components with polling:\n";
$viewsPath = resource_path('views');
$pollingFiles = [];
exec("grep -r 'wire:poll' {$viewsPath} 2>/dev/null", $pollingFiles);

if (!empty($pollingFiles)) {
    foreach ($pollingFiles as $file) {
        if (preg_match('/([^:]+):.*wire:poll\.?(\d+)?/', $file, $matches)) {
            $filename = str_replace($viewsPath . '/', '', $matches[1]);
            $interval = $matches[2] ?? 'default';
            echo "   - {$filename} (interval: {$interval}s)\n";
        }
    }
} else {
    echo "   No polling components found\n";
}

echo "\n=== RECOMMENDATIONS ===\n";
echo "1. If you see 404 errors for specific components, check if they exist\n";
echo "2. If Livewire assets are not published, run: php artisan livewire:publish --assets\n";
echo "3. Check browser Network tab to see exactly which URLs return 404\n";
echo "4. Polling components might be causing repeated 404 errors\n";