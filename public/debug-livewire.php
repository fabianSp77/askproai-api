<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Enable debug mode temporarily
config(['app.debug' => true]);
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Test Livewire
try {
    echo "PHP Memory Limit: " . ini_get('memory_limit') . "\n";
    echo "Session Driver: " . config('session.driver') . "\n";
    
    // Check if Livewire component manager is available
    $livewire = app('livewire');
    echo "Livewire loaded: Yes\n";
    
    // Try to resolve login component
    $componentClass = $livewire->getClass('filament.admin.auth.login');
    echo "Login component class: " . ($componentClass ?? 'NOT FOUND') . "\n";
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
