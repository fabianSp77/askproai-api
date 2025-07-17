<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load Laravel
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

try {
    echo "<h1>Admin Portal Debug</h1>";
    
    // Check Filament
    if (class_exists(\Filament\FilamentServiceProvider::class)) {
        echo "<p>✅ Filament is installed</p>";
    } else {
        echo "<p>❌ Filament NOT found!</p>";
    }
    
    // Check Admin Panel Provider
    if (class_exists(\App\Providers\Filament\AdminPanelProvider::class)) {
        echo "<p>✅ AdminPanelProvider exists</p>";
    } else {
        echo "<p>❌ AdminPanelProvider NOT found!</p>";
    }
    
    // Test route
    echo "<h2>Testing /admin route...</h2>";
    
    $request = Illuminate\Http\Request::create('/admin', 'GET');
    $response = $kernel->handle($request);
    
    echo "<p>Response Status: " . $response->getStatusCode() . "</p>";
    
    if ($response->getStatusCode() == 500) {
        echo "<h3>Error Content:</h3>";
        echo "<pre>" . htmlspecialchars(substr($response->getContent(), 0, 1000)) . "</pre>";
    }
    
} catch (\Exception $e) {
    echo "<h2>Exception caught:</h2>";
    echo "<pre>" . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "</pre>";
}