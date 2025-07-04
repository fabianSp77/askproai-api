#!/usr/bin/env php
<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "📱 Testing Phone Number Validation\n";
echo "=================================\n\n";

$testCases = [
    // Test numbers (should pass without validation)
    '+491604366218' => 'Test number - Hans Schuster',
    '+491234567890' => 'Test number - Generic',
    'anonymous' => 'Anonymous caller',
    
    // Valid German numbers (should be validated)
    '+4917612345678' => 'Valid German mobile',
    '017612345678' => 'German mobile without country code',
    '030123456' => 'German landline',
    
    // Invalid numbers (should fail for new customers)
    '123' => 'Too short',
    'not-a-number' => 'Invalid format',
];

foreach ($testCases as $phone => $description) {
    echo "Testing: $phone ($description)\n";
    
    try {
        // Test creating a new customer
        $customer = new \App\Models\Customer([
            'name' => 'Test Customer',
            'phone' => $phone,
            'company_id' => 1,
            'source' => 'test'
        ]);
        
        // Trigger validation
        $customer->save();
        
        echo "  ✅ Saved successfully\n";
        echo "  Stored as: {$customer->phone}\n";
        
        // Clean up
        $customer->delete();
        
    } catch (\Exception $e) {
        echo "  ❌ Failed: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
}

echo "✅ Phone validation test complete!\n";