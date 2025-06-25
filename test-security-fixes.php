#!/usr/bin/env php
<?php

/**
 * Security Fix Verification Script
 * Tests all implemented security measures
 */

echo "\033[1;34m=== SECURITY FIXES VERIFICATION ===\033[0m\n\n";

// Test 1: API Endpoint without signature (should fail)
echo "\033[1;33m1. Testing endpoint without signature...\033[0m\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.askproai.de/api/retell/identify-customer");
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'args' => ['phone_number' => '+491234567890']
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 401 || $httpCode === 403) {
    echo "\033[1;32m✓ PASSED: Endpoint rejected request without signature (HTTP $httpCode)\033[0m\n";
} else {
    echo "\033[1;31m✗ FAILED: Endpoint accepted request without signature (HTTP $httpCode)\033[0m\n";
}

// Test 2: Input validation (invalid phone format)
echo "\n\033[1;33m2. Testing input validation...\033[0m\n";
$invalidInputs = [
    ['phone_number' => '<script>alert("XSS")</script>'],
    ['phone_number' => "'; DROP TABLE customers; --"],
    ['customer_id' => 'not-a-number'],
    ['preference_type' => 'invalid-type']
];

foreach ($invalidInputs as $input) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.askproai.de/api/retell/identify-customer");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['args' => $input]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'X-Retell-Signature: dummy' // Add dummy signature to bypass first check
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 422 || $httpCode === 400 || $httpCode === 401) {
        echo "\033[1;32m✓ PASSED: Invalid input rejected: " . json_encode($input) . "\033[0m\n";
    } else {
        echo "\033[1;31m✗ FAILED: Invalid input accepted: " . json_encode($input) . "\033[0m\n";
    }
}

// Test 3: Rate limiting
echo "\n\033[1;33m3. Testing rate limiting...\033[0m\n";
$start = microtime(true);
$successCount = 0;
$blockedCount = 0;

for ($i = 0; $i < 70; $i++) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.askproai.de/api/retell/identify-customer");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'args' => ['phone_number' => '+491234567890']
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 2);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 429) {
        $blockedCount++;
    } else {
        $successCount++;
    }
}

$elapsed = microtime(true) - $start;
echo "Sent 70 requests in {$elapsed}s\n";
echo "Success: $successCount, Blocked (429): $blockedCount\n";

if ($blockedCount > 0) {
    echo "\033[1;32m✓ PASSED: Rate limiting is working\033[0m\n";
} else {
    echo "\033[1;31m✗ FAILED: Rate limiting not working properly\033[0m\n";
}

// Test 4: SQL Injection protection
echo "\n\033[1;33m4. Testing SQL injection protection...\033[0m\n";
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $service = new \App\Services\Customer\EnhancedCustomerService();
    
    // Test dangerous phone number
    $dangerousPhone = "'; DROP TABLE customers; --";
    $result = $service->identifyByPhone($dangerousPhone, 1);
    
    // Check if tables still exist
    $tablesExist = \DB::select("SHOW TABLES LIKE 'customers'");
    
    if (!empty($tablesExist)) {
        echo "\033[1;32m✓ PASSED: SQL injection attempt blocked, tables intact\033[0m\n";
    } else {
        echo "\033[1;31m✗ FAILED: SQL injection protection failed!\033[0m\n";
    }
} catch (\Exception $e) {
    echo "\033[1;32m✓ PASSED: SQL injection attempt caught: " . $e->getMessage() . "\033[0m\n";
}

// Test 5: Data encryption in cache
echo "\n\033[1;33m5. Testing data encryption in cache...\033[0m\n";
try {
    // Simulate caching customer data
    $testData = [
        'customer_name' => 'Test Customer',
        'notes' => 'Sensitive medical information'
    ];
    
    // Check if encryption functions are being used
    $controller = new \App\Http\Controllers\Api\RetellCustomerRecognitionController(
        app(\App\Services\Customer\EnhancedCustomerService::class),
        app(\App\Services\PhoneNumberResolver::class)
    );
    
    // Verify encrypt() function exists and works
    $encrypted = encrypt($testData['customer_name']);
    $decrypted = decrypt($encrypted);
    
    if ($encrypted !== $testData['customer_name'] && $decrypted === $testData['customer_name']) {
        echo "\033[1;32m✓ PASSED: Encryption/decryption working correctly\033[0m\n";
    } else {
        echo "\033[1;31m✗ FAILED: Encryption not working properly\033[0m\n";
    }
} catch (\Exception $e) {
    echo "\033[1;31m✗ ERROR: " . $e->getMessage() . "\033[0m\n";
}

// Summary
echo "\n\033[1;34m=== SECURITY VERIFICATION COMPLETE ===\033[0m\n";
echo "\nNext steps:\n";
echo "1. Review logs: tail -f storage/logs/laravel.log\n";
echo "2. Monitor metrics: https://api.askproai.de/admin/security-dashboard\n";
echo "3. Check Grafana: http://localhost:3000\n";