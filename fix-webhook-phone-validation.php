<?php

// Quick fix for webhook phone validation issue

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Customer;
use App\Http\Controllers\RetellEnhancedWebhookController;

echo "=== Fixing Phone Validation Issue ===\n\n";

// 1. Check Customer model for validation
echo "1. Checking Customer model validation rules...\n";
$customer = new Customer();
$reflection = new ReflectionClass($customer);

// Check if there's a booted method with validation
if ($reflection->hasMethod('booted')) {
    echo "Found booted method in Customer model\n";
}

// 2. Try to create a customer with phone number
echo "\n2. Testing customer creation with phone number...\n";
try {
    $testCustomer = new Customer();
    $testCustomer->company_id = 85;
    $testCustomer->phone = '+491234567890';
    $testCustomer->name = 'Test Customer';
    $testCustomer->created_via = 'test';
    
    // Don't save, just validate
    $rules = $testCustomer->getRules ?? [];
    echo "Customer validation rules: " . json_encode($rules) . "\n";
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

// 3. Test with a simpler approach - update the webhook controller
echo "\n3. Checking RetellEnhancedWebhookController...\n";

$file = file_get_contents(__DIR__ . '/app/Http/Controllers/RetellEnhancedWebhookController.php');

// Find the line where phone is set
if (strpos($file, '$customer->phone = $call->from_number;') !== false) {
    echo "Found phone assignment in controller\n";
    echo "The issue is likely in Customer model validation\n";
    
    // Suggest fix
    echo "\nSuggested fix:\n";
    echo "Replace: \$customer->phone = \$call->from_number;\n";
    echo "With: \$customer->phone = preg_replace('/[^0-9+]/', '', \$call->from_number);\n";
}