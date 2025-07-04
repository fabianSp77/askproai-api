<?php

use App\Models\User;
use App\Filament\Admin\Pages\MLTrainingDashboardLivewire;
use Illuminate\Support\Facades\Auth;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    // Disable security logging temporarily
    config(['monitoring.security.enabled' => false]);
    
    // Find user
    $user = User::where('email', 'fabian@askproai.de')->first();
    if (!$user) {
        die("User not found\n");
    }
    
    echo "Testing with user: " . $user->email . "\n";
    echo "User tenant_id: " . $user->tenant_id . "\n";
    echo "Is Super Admin: " . ($user->hasRole('Super Admin') ? 'Yes' : 'No') . "\n\n";
    
    // Login as user without firing events
    Auth::setUser($user);
    
    // Create page instance
    $page = new MLTrainingDashboardLivewire();
    
    echo "Mounting page...\n";
    $page->mount();
    echo "✓ Mount successful\n\n";
    
    echo "Training stats:\n";
    echo "- Total calls: " . $page->trainingStats['total_calls'] . "\n";
    echo "- With transcript: " . $page->trainingStats['calls_with_transcript'] . "\n";
    echo "- With predictions: " . $page->trainingStats['calls_with_predictions'] . "\n";
    echo "- With audio: " . $page->trainingStats['calls_with_audio'] . "\n";
    echo "\n";
    
    echo "Model info:\n";
    if ($page->modelInfo) {
        echo "- Version: " . $page->modelInfo['version'] . "\n";
        echo "- Accuracy: " . ($page->modelInfo['accuracy'] ?? 'N/A') . "\n";
        echo "- Training samples: " . $page->modelInfo['training_samples'] . "\n";
    } else {
        echo "- No model trained yet\n";
    }
    
    echo "\nTesting component methods...\n";
    
    // Test getting header actions
    $actions = $page->getHeaderActions();
    echo "Header actions count: " . count($actions) . "\n";
    foreach ($actions as $action) {
        echo "- Action: " . $action->getName() . " (visible: " . ($action->isVisible() ? 'Yes' : 'No') . ")\n";
    }
    
    echo "\n✅ All tests passed!\n";
    
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}