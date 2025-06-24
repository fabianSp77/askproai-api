<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Test phone resolution
$phoneNumber = '+493012345681';

echo "Testing phone resolution for: $phoneNumber\n";

try {
    $resolver = app(\App\Services\PhoneNumberResolver::class);
    
    // Test without company context (should work after our fixes)
    $result = $resolver->resolve($phoneNumber);
    
    echo "Resolution successful!\n";
    echo "Company ID: " . $result['company_id'] . "\n";
    echo "Company Name: " . $result['company_name'] . "\n";
    echo "Branch ID: " . $result['branch_id'] . "\n";
    echo "Branch Name: " . $result['branch_name'] . "\n";
    echo "Phone ID: " . $result['phone_id'] . "\n";
    echo "Agent ID: " . ($result['agent_id'] ?? 'Not set') . "\n";
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}