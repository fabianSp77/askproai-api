<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

// Boot the app
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    // Test if StripeTopupService can be instantiated
    $stripeService = app(\App\Services\StripeTopupService::class);
    echo "✅ StripeTopupService instantiated successfully\n";
    
    // Test if WorkingBillingController can be instantiated
    $controller = app(\App\Http\Controllers\Portal\WorkingBillingController::class);
    echo "✅ WorkingBillingController instantiated successfully\n";
    
    // Check if BalanceTransaction model works
    $transactionCount = \App\Models\BalanceTransaction::count();
    echo "✅ BalanceTransaction model works, count: $transactionCount\n";
    
    // Check if BalanceTopup model works
    $topupCount = \App\Models\BalanceTopup::count();
    echo "✅ BalanceTopup model works, count: $topupCount\n";
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "\nTrace:\n" . $e->getTraceAsString() . "\n";
}