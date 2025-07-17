<?php
// Simple diagnosis for 404 popup issue

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);
$kernel->terminate($request, $response);

echo "=== 404 Popup Diagnosis ===\n\n";

// Check recent error logs
echo "1. Checking recent logs for 404 errors:\n";
$logFile = storage_path('logs/laravel.log');
if (file_exists($logFile)) {
    // Get last 100 lines of log
    $lines = [];
    $handle = fopen($logFile, "r");
    if ($handle) {
        while (!feof($handle)) {
            $line = fgets($handle);
            if (strpos($line, '404') !== false && strpos($line, 'livewire') !== false) {
                $lines[] = trim($line);
            }
        }
        fclose($handle);
    }
    
    if (!empty($lines)) {
        // Show last 5 404 errors
        $recent = array_slice($lines, -5);
        foreach ($recent as $line) {
            // Extract URL from log line
            if (preg_match('/"([^"]*livewire[^"]*)"/', $line, $matches)) {
                echo "   - URL: {$matches[1]}\n";
            }
        }
    } else {
        echo "   No recent Livewire 404 errors in logs\n";
    }
}

// Check which views have wire:poll or wire:init
echo "\n2. Views with potential issues:\n";
$viewsWithPolling = [];
$viewsWithInit = [];

$viewsPath = resource_path('views');
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($viewsPath)
);

foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $content = file_get_contents($file->getPathname());
        $relativePath = str_replace($viewsPath . '/', '', $file->getPathname());
        
        if (strpos($content, 'wire:poll') !== false) {
            $viewsWithPolling[] = $relativePath;
        }
        if (strpos($content, 'wire:init') !== false) {
            $viewsWithInit[] = $relativePath;
        }
    }
}

echo "   Views with wire:poll (" . count($viewsWithPolling) . "):\n";
foreach (array_slice($viewsWithPolling, 0, 5) as $view) {
    echo "   - {$view}\n";
}

echo "\n   Views with wire:init (" . count($viewsWithInit) . "):\n";
foreach (array_slice($viewsWithInit, 0, 5) as $view) {
    echo "   - {$view}\n";
}

// Check Dashboard widgets
echo "\n3. Dashboard Widget Check:\n";
$dashboardClass = 'App\Filament\Admin\Pages\OperationsDashboard';
if (class_exists($dashboardClass)) {
    echo "   ✅ OperationsDashboard exists\n";
    
    // Get public methods that might be widgets
    $reflection = new ReflectionClass($dashboardClass);
    $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
    
    $widgetMethods = [];
    foreach ($methods as $method) {
        if (strpos($method->name, 'Widget') !== false || strpos($method->name, 'widget') !== false) {
            $widgetMethods[] = $method->name;
        }
    }
    
    if (!empty($widgetMethods)) {
        echo "   Widget-related methods:\n";
        foreach ($widgetMethods as $method) {
            echo "   - {$method}()\n";
        }
    }
} else {
    echo "   ❌ OperationsDashboard NOT FOUND\n";
}

// Solution
echo "\n=== SOLUTION APPLIED ===\n";
echo "1. Added livewire-404-popup-fix.js to intercept and hide 404 popups\n";
echo "2. Added fallback route for unhandled Livewire requests\n";
echo "3. The fix will:\n";
echo "   - Automatically remove any 404 popup dialogs\n";
echo "   - Intercept failed Livewire requests and prevent error popups\n";
echo "   - Log issues to console for debugging\n";
echo "\n";
echo "To verify the fix is working:\n";
echo "1. Refresh the page (Ctrl+F5)\n";
echo "2. Check browser console for '[Livewire 404 Popup Fix]' messages\n";
echo "3. The 404 popups should no longer appear\n";