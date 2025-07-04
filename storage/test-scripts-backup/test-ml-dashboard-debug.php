<?php

use App\Models\User;
use App\Filament\Admin\Pages\MLTrainingDashboardLivewire;
use Illuminate\Support\Facades\Auth;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    // Find a user
    $user = User::first();
    if (!$user) {
        echo "No users found in database\n";
        
        // Try to create a test user
        $user = User::create([
            'name' => 'Test Admin',
            'email' => 'test@askproai.de',
            'password' => bcrypt('password'),
            'tenant_id' => 1
        ]);
    }
    
    echo "Testing with user: " . $user->email . "\n";
    echo "User tenant_id: " . $user->tenant_id . "\n";
    
    // Login as user
    Auth::login($user);
    
    // Create page instance
    $page = new MLTrainingDashboardLivewire();
    
    echo "Mounting page...\n";
    $page->mount();
    echo "✓ Mount successful\n";
    
    echo "\nTraining stats:\n";
    print_r($page->trainingStats);
    
    echo "\nTesting startTraining method...\n";
    $page->startTraining(['require_audio' => true]);
    echo "✓ startTraining successful\n";
    
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}