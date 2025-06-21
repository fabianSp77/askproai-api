#!/usr/bin/env php
<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\MCP\StripeMCPServer;

try {
    echo "Testing Stripe MCP Server...\n\n";
    
    $stripeMCP = app(StripeMCPServer::class);
    
    // Test 1: Get payment overview without company ID
    echo "Test 1: Get payment overview without company ID\n";
    $result = $stripeMCP->getPaymentOverview([]);
    echo "Result: " . json_encode($result, JSON_PRETTY_PRINT) . "\n\n";
    
    // Test 2: Get payment overview with invalid company ID
    echo "Test 2: Get payment overview with invalid company ID\n";
    $result = $stripeMCP->getPaymentOverview(['company_id' => 999999]);
    echo "Result: " . json_encode($result, JSON_PRETTY_PRINT) . "\n\n";
    
    // Test 3: Get payment overview with valid company ID (assuming company ID 1 exists)
    echo "Test 3: Get payment overview with company ID 1\n";
    $result = $stripeMCP->getPaymentOverview(['company_id' => 1, 'period' => 'month']);
    echo "Result: " . json_encode($result, JSON_PRETTY_PRINT) . "\n\n";
    
    // Test 4: Test customer payments without ID
    echo "Test 4: Get customer payments without ID\n";
    $result = $stripeMCP->getCustomerPayments([]);
    echo "Result: " . json_encode($result, JSON_PRETTY_PRINT) . "\n\n";
    
    // Test 5: Test create invoice
    echo "Test 5: Create invoice without required params\n";
    $result = $stripeMCP->createInvoice([]);
    echo "Result: " . json_encode($result, JSON_PRETTY_PRINT) . "\n\n";
    
    echo "All tests completed!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}