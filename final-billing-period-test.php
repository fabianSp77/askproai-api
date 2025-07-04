<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Custom exception handler
set_exception_handler(function($e) {
    echo "\n!!! EXCEPTION CAUGHT !!!\n";
    echo "Type: " . get_class($e) . "\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n\n";
    
    // Look for specific Filament errors
    if (str_contains($e->getMessage(), 'Filament') || str_contains($e->getFile(), 'filament')) {
        echo "This appears to be a Filament-related error.\n";
    }
    
    echo "Stack trace (first 5 frames):\n";
    $trace = $e->getTrace();
    foreach (array_slice($trace, 0, 5) as $i => $frame) {
        echo "#{$i} ";
        if (isset($frame['file'])) {
            echo basename($frame['file']) . ":" . $frame['line'];
        }
        if (isset($frame['class']) && isset($frame['function'])) {
            echo " " . $frame['class'] . "::" . $frame['function'] . "()";
        } elseif (isset($frame['function'])) {
            echo " " . $frame['function'] . "()";
        }
        echo "\n";
    }
    
    exit(1);
});

// Set auth
$user = \App\Models\User::where('email', 'admin@askproai.de')->first();
if ($user) {
    auth()->login($user);
    if ($user->company_id) {
        app()->instance('current_company_id', $user->company_id);
    }
}

echo "Testing BillingPeriod ID 2 access...\n\n";

try {
    // 1. Load record
    echo "1. Loading BillingPeriod record:\n";
    $billingPeriod = \App\Models\BillingPeriod::withoutGlobalScopes()->find(2);
    if (!$billingPeriod) {
        throw new Exception("BillingPeriod ID 2 not found!");
    }
    echo "   ✓ Record loaded (ID: {$billingPeriod->id})\n";
    
    // 2. Check relationships one by one
    echo "\n2. Testing relationships:\n";
    
    // Company
    try {
        $company = $billingPeriod->company;
        echo "   ✓ company() - " . ($company ? "OK (ID: {$company->id})" : "NULL") . "\n";
    } catch (\Exception $e) {
        echo "   ✗ company() - ERROR: " . $e->getMessage() . "\n";
    }
    
    // Branch
    try {
        $branch = $billingPeriod->branch;
        echo "   ✓ branch() - " . ($branch ? "OK (ID: {$branch->id})" : "NULL") . "\n";
    } catch (\Exception $e) {
        echo "   ✗ branch() - ERROR: " . $e->getMessage() . "\n";
    }
    
    // Subscription
    try {
        $subscription = $billingPeriod->subscription;
        echo "   ✓ subscription() - " . ($subscription ? "OK (ID: {$subscription->id})" : "NULL") . "\n";
    } catch (\Exception $e) {
        echo "   ✗ subscription() - ERROR: " . $e->getMessage() . "\n";
    }
    
    // Invoice
    try {
        $invoice = $billingPeriod->invoice;
        echo "   ✓ invoice() - " . ($invoice ? "OK (ID: {$invoice->id})" : "NULL") . "\n";
    } catch (\Exception $e) {
        echo "   ✗ invoice() - ERROR: " . $e->getMessage() . "\n";
    }
    
    // Calls (with query)
    try {
        $callsQuery = $billingPeriod->calls();
        echo "   ✓ calls() - Query created\n";
        
        $callsCount = $callsQuery->count();
        echo "   ✓ calls()->count() - {$callsCount} calls\n";
    } catch (\Exception $e) {
        echo "   ✗ calls() - ERROR: " . $e->getMessage() . "\n";
    }
    
    // 3. Test resource registration
    echo "\n3. Testing Filament resource:\n";
    
    $resourceClass = \App\Filament\Admin\Resources\BillingPeriodResource::class;
    if (class_exists($resourceClass)) {
        echo "   ✓ Resource class exists\n";
        
        // Check if it's registered
        try {
            $model = $resourceClass::getModel();
            echo "   ✓ Model class: " . $model . "\n";
            
            $slug = $resourceClass::getSlug();
            echo "   ✓ Resource slug: " . $slug . "\n";
        } catch (\Exception $e) {
            echo "   ✗ Resource methods error: " . $e->getMessage() . "\n";
        }
    } else {
        echo "   ✗ Resource class not found\n";
    }
    
    // 4. Check for permission issues
    echo "\n4. Checking permissions:\n";
    if (method_exists($user, 'can')) {
        $permissions = ['view', 'update', 'delete'];
        foreach ($permissions as $permission) {
            $can = $user->can($permission, $billingPeriod);
            echo "   " . ($can ? "✓" : "✗") . " Can {$permission} billing period\n";
        }
    } else {
        echo "   ⚠ No permission checks available\n";
    }
    
    echo "\n✅ All tests completed successfully!\n";
    
} catch (\Throwable $e) {
    // Will be caught by exception handler above
    throw $e;
}