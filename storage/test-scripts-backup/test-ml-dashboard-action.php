<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Testing ML Dashboard Action Directly ===\n\n";

try {
    // Login as admin user
    $user = User::where('email', 'fabian@askproai.de')->first();
    if (!$user) {
        $user = User::first();
    }
    
    if (!$user) {
        echo "No users found, creating test user...\n";
        $user = User::create([
            'name' => 'Test Admin',
            'email' => 'test@askproai.de',
            'password' => bcrypt('password'),
            'tenant_id' => 1
        ]);
    }
    
    Auth::login($user);
    echo "1. Logged in as: " . $user->email . "\n\n";
    
    // Create the page instance
    $page = new \App\Filament\Admin\Pages\MLTrainingDashboardLivewire();
    
    echo "2. Mounting page...\n";
    $page->mount();
    echo "   ✓ Mount successful\n\n";
    
    echo "3. Testing startTraining method directly...\n";
    try {
        $page->startTraining([
            'require_audio' => true,
            'exclude_test_calls' => true,
            'duration_filter' => 'min_30'
        ]);
        echo "   ✓ startTraining executed successfully\n";
    } catch (\Exception $e) {
        echo "   ✗ Error in startTraining: " . $e->getMessage() . "\n";
        echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n";
        echo "   Trace:\n";
        $trace = collect($e->getTrace())->take(5);
        foreach ($trace as $i => $frame) {
            echo "     #{$i} " . ($frame['file'] ?? 'unknown') . ":" . ($frame['line'] ?? '?') . " " . ($frame['function'] ?? '') . "\n";
        }
    }
    
    echo "\n4. Testing analyzeAllCalls method directly...\n";
    try {
        $page->analyzeAllCalls([
            'quick_filter' => 'unanalyzed_with_audio',
            'batch_size' => '5'
        ]);
        echo "   ✓ analyzeAllCalls executed successfully\n";
    } catch (\Exception $e) {
        echo "   ✗ Error in analyzeAllCalls: " . $e->getMessage() . "\n";
        echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    }
    
    echo "\n5. Checking Livewire component registration...\n";
    $componentName = 'filament.admin.pages.m-l-training-dashboard-livewire';
    echo "   - Component name: {$componentName}\n";
    echo "   - Component class: " . get_class($page) . "\n";
    
    // Check if it's a valid Livewire component
    if ($page instanceof \Livewire\Component) {
        echo "   ✓ Is a valid Livewire component\n";
    } else {
        echo "   ✗ NOT a Livewire component!\n";
    }
    
    // Check parent classes
    echo "\n6. Component inheritance chain:\n";
    $class = new ReflectionClass($page);
    $parent = $class;
    $i = 0;
    while ($parent && $i < 10) {
        echo "   " . str_repeat('  ', $i) . "└─ " . $parent->getName() . "\n";
        $parent = $parent->getParentClass();
        $i++;
    }
    
} catch (\Exception $e) {
    echo "\nERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== Test Complete ===\n";