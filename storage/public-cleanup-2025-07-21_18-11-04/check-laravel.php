<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Laravel Bootstrap Check ===\n\n";

// Check if autoload exists
if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
    die("ERROR: Vendor autoload not found. Run composer install.\n");
}

echo "✅ Vendor autoload found\n";

// Try to load autoload
require_once __DIR__ . '/../vendor/autoload.php';
echo "✅ Autoload loaded successfully\n";

// Check if bootstrap exists
if (!file_exists(__DIR__ . '/../bootstrap/app.php')) {
    die("ERROR: Bootstrap app.php not found.\n");
}

echo "✅ Bootstrap app.php found\n";

// Try to bootstrap Laravel
try {
    $app = require_once __DIR__ . '/../bootstrap/app.php';
    echo "✅ Laravel app created\n";
    
    // Create kernel
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    echo "✅ HTTP kernel created\n";
    
    // Create request
    $request = Illuminate\Http\Request::capture();
    echo "✅ Request captured\n";
    
    // Try to handle request
    $response = $kernel->handle($request);
    echo "✅ Request handled\n";
    
    // Check environment
    echo "\n--- Environment Info ---\n";
    echo "Environment: " . app()->environment() . "\n";
    echo "Debug Mode: " . (config('app.debug') ? 'ON' : 'OFF') . "\n";
    echo "URL: " . config('app.url') . "\n";
    echo "Timezone: " . config('app.timezone') . "\n";
    
    // Check database connection
    echo "\n--- Database Connection ---\n";
    try {
        $pdo = \DB::connection()->getPdo();
        echo "✅ Database connected\n";
        echo "Driver: " . \DB::connection()->getDriverName() . "\n";
    } catch (\Exception $e) {
        echo "❌ Database connection failed: " . $e->getMessage() . "\n";
    }
    
    // Check auth guards
    echo "\n--- Auth Guards ---\n";
    $guards = config('auth.guards');
    foreach ($guards as $name => $config) {
        echo "- $name: " . ($config['driver'] ?? 'not configured') . "\n";
    }
    
    // Check routes
    echo "\n--- Route Check ---\n";
    echo "Total routes: " . count(app('router')->getRoutes()) . "\n";
    echo "Admin route exists: " . (app('router')->has('filament.admin.pages.dashboard') ? 'YES' : 'NO') . "\n";
    
    // Check Filament
    echo "\n--- Filament Check ---\n";
    echo "Filament installed: " . (class_exists('Filament\Panel') ? 'YES' : 'NO') . "\n";
    
    echo "\n✅ All basic checks passed!\n";
    
} catch (\Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
}