#!/usr/bin/env php
<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== LIVEWIRE LOGIN FIX DIAGNOSTIC ===\n";
echo "Time: " . date('Y-m-d H:i:s') . "\n\n";

// 1. Check PHP Memory
echo "1. PHP Memory Settings:\n";
echo "   - Memory Limit: " . ini_get('memory_limit') . "\n";
echo "   - Max Execution Time: " . ini_get('max_execution_time') . "s\n";
echo "   - Current Usage: " . round(memory_get_usage() / 1024 / 1024, 2) . "MB\n";
echo "   - Peak Usage: " . round(memory_get_peak_usage() / 1024 / 1024, 2) . "MB\n\n";

// 2. Check Livewire
echo "2. Livewire Status:\n";
try {
    $livewire = app('livewire');
    echo "   ✓ Livewire Service Loaded\n";
    
    // Check Livewire version
    $version = \Composer\InstalledVersions::getVersion('livewire/livewire');
    echo "   - Version: $version\n";
    
    // Check Livewire routes
    $routes = app('router')->getRoutes();
    $livewireRoutes = 0;
    foreach ($routes as $route) {
        if (str_contains($route->uri(), 'livewire')) {
            $livewireRoutes++;
        }
    }
    echo "   - Livewire Routes: $livewireRoutes\n";
} catch (\Exception $e) {
    echo "   ✗ Livewire Error: " . $e->getMessage() . "\n";
}
echo "\n";

// 3. Check Filament
echo "3. Filament Status:\n";
try {
    $filament = app('filament');
    echo "   ✓ Filament Service Loaded\n";
    
    // Get version
    $version = \Composer\InstalledVersions::getVersion('filament/filament');
    echo "   - Version: $version\n";
    
    // Check panels
    $panels = \Filament\Facades\Filament::getPanels();
    echo "   - Panels: " . count($panels) . "\n";
    foreach ($panels as $id => $panel) {
        echo "     • $id: " . get_class($panel) . "\n";
    }
} catch (\Exception $e) {
    echo "   ✗ Filament Error: " . $e->getMessage() . "\n";
}
echo "\n";

// 4. Check Session
echo "4. Session Configuration:\n";
echo "   - Driver: " . config('session.driver') . "\n";
echo "   - Domain: " . config('session.domain') . "\n";
echo "   - Secure: " . (config('session.secure') ? 'Yes' : 'No') . "\n";
echo "   - HTTP Only: " . (config('session.http_only') ? 'Yes' : 'No') . "\n";
echo "   - Same Site: " . config('session.same_site') . "\n\n";

// 5. Check CSRF
echo "5. CSRF Protection:\n";
try {
    $token = csrf_token();
    echo "   ✓ CSRF Token Generated: " . substr($token, 0, 20) . "...\n";
} catch (\Exception $e) {
    echo "   ✗ CSRF Error: " . $e->getMessage() . "\n";
}
echo "\n";

// 6. Check Middleware
echo "6. Global Middleware:\n";
$kernel = app(\Illuminate\Contracts\Http\Kernel::class);
$middleware = $kernel->getMiddleware();
foreach ($middleware as $mw) {
    echo "   - " . (is_string($mw) ? $mw : get_class($mw)) . "\n";
}
echo "\n";

// 7. Test Livewire Component Discovery
echo "7. Livewire Component Discovery:\n";
try {
    $manifest = app(\Livewire\Mechanisms\ComponentRegistry::class);
    $components = $manifest->all();
    echo "   - Total Components: " . count($components) . "\n";
    
    // Show first 5 components
    $shown = 0;
    foreach ($components as $alias => $class) {
        if ($shown++ < 5) {
            echo "     • $alias => $class\n";
        }
    }
    if (count($components) > 5) {
        echo "     ... and " . (count($components) - 5) . " more\n";
    }
} catch (\Exception $e) {
    echo "   ✗ Component Discovery Error: " . $e->getMessage() . "\n";
}
echo "\n";

// 8. Check Recent Errors
echo "8. Recent Laravel Errors:\n";
$logFile = storage_path('logs/laravel.log');
if (file_exists($logFile)) {
    $lines = file($logFile);
    $errors = [];
    $pattern = '/\[' . date('Y-m-d') . '.*ERROR\]/';
    
    foreach ($lines as $line) {
        if (preg_match($pattern, $line)) {
            $errors[] = trim($line);
        }
    }
    
    if (empty($errors)) {
        echo "   ✓ No errors today\n";
    } else {
        echo "   ✗ Found " . count($errors) . " errors today\n";
        // Show last 3 errors
        $recent = array_slice($errors, -3);
        foreach ($recent as $error) {
            echo "     - " . substr($error, 0, 100) . "...\n";
        }
    }
} else {
    echo "   - Log file not found\n";
}
echo "\n";

// 9. Memory Intensive Components Check
echo "9. Checking Memory-Intensive Components:\n";
$checkClasses = [
    'App\Filament\Admin\Resources\CustomerResource',
    'App\Filament\Admin\Resources\AppointmentResource',
    'App\Filament\Admin\Resources\CallResource',
];

foreach ($checkClasses as $class) {
    if (class_exists($class)) {
        $before = memory_get_usage();
        try {
            $instance = new $class();
            $after = memory_get_usage();
            $used = ($after - $before) / 1024 / 1024;
            echo "   - $class: " . round($used, 2) . "MB\n";
        } catch (\Exception $e) {
            echo "   - $class: Error - " . $e->getMessage() . "\n";
        }
    }
}
echo "\n";

// 10. Recommendations
echo "10. Recommendations:\n";
$memLimit = ini_get('memory_limit');
$memLimitMB = (int)str_replace('M', '', $memLimit);

if ($memLimitMB < 512) {
    echo "   ⚠️  Increase memory_limit to at least 512M (currently $memLimit)\n";
}

if ($livewireRoutes < 2) {
    echo "   ⚠️  Livewire routes might not be registered properly\n";
}

if (config('app.debug') === true) {
    echo "   ⚠️  Debug mode is ON - disable for production\n";
}

echo "\n=== END DIAGNOSTIC ===\n";