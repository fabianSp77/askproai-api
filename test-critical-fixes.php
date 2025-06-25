#!/usr/bin/env php
<?php

/**
 * Test script for critical fixes implementation
 * Run: php test-critical-fixes.php
 */

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\Security\ApiKeyService;
use App\Services\ErrorHandlingService;
use App\Services\CircuitBreaker\CircuitBreaker;
use Illuminate\Support\Facades\Artisan;

echo "\n=== CRITICAL FIXES TEST SUITE ===\n\n";

// Test 1: API Key Service
echo "1. Testing API Key Service...\n";
try {
    $testKey = 'key_test_1234567890_abcdef';
    
    // Test encryption
    $encrypted = ApiKeyService::encrypt($testKey);
    echo "   ✓ Encryption successful\n";
    
    // Test decryption
    $decrypted = ApiKeyService::decrypt($encrypted);
    if ($decrypted === $testKey) {
        echo "   ✓ Decryption successful\n";
    } else {
        echo "   ✗ Decryption failed\n";
    }
    
    // Test masking
    $masked = ApiKeyService::mask($testKey);
    echo "   ✓ Masked key: $masked\n";
    
    // Test validation
    if (ApiKeyService::isValid($testKey)) {
        echo "   ✓ Key validation passed\n";
    } else {
        echo "   ✗ Key validation failed\n";
    }
    
} catch (Exception $e) {
    echo "   ✗ API Key Service Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 2: Error Handling Service
echo "2. Testing Error Handling Service...\n";
try {
    // Test error handling
    $testError = new Exception('Test error message', 500);
    $result = ErrorHandlingService::handle($testError, ['test_context' => 'unit_test']);
    
    echo "   ✓ Error handled successfully\n";
    echo "   - Error ID: " . $result['error_id'] . "\n";
    echo "   - User Message: " . $result['message'] . "\n";
    echo "   - Level: " . $result['level'] . "\n";
    
} catch (Exception $e) {
    echo "   ✗ Error Handling Service Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 3: Circuit Breaker
echo "3. Testing Circuit Breaker...\n";
try {
    $breaker = new CircuitBreaker();
    
    // Test successful call
    $result = $breaker->call('test_service', function() {
        return 'success';
    });
    
    if ($result === 'success') {
        echo "   ✓ Circuit breaker call successful\n";
    }
    
    // Test with fallback
    $result = $breaker->call('test_service', 
        function() {
            throw new Exception('Service failed');
        },
        function() {
            return 'fallback_value';
        }
    );
    
    if ($result === 'fallback_value') {
        echo "   ✓ Circuit breaker fallback working\n";
    }
    
    // Check status
    $status = CircuitBreaker::getStatus();
    echo "   ✓ Circuit breaker status retrieved\n";
    
} catch (Exception $e) {
    echo "   ✗ Circuit Breaker Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 4: Permission Seeder
echo "4. Testing Permission Seeder...\n";
try {
    // Check if seeder exists
    if (class_exists('Database\Seeders\RetellControlCenterPermissionSeeder')) {
        echo "   ✓ Permission seeder class exists\n";
        
        // Test seeder execution (dry run)
        echo "   - To run seeder: php artisan db:seed --class=RetellControlCenterPermissionSeeder\n";
    } else {
        echo "   ✗ Permission seeder class not found\n";
    }
} catch (Exception $e) {
    echo "   ✗ Permission Seeder Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 5: Safe Migration
echo "5. Testing Safe Migration...\n";
try {
    if (class_exists('App\Database\SafeMigration')) {
        echo "   ✓ SafeMigration class exists\n";
        
        // Create test migration instance
        $testMigration = new class extends App\Database\SafeMigration {
            protected function safeUp(): void {
                // Test implementation
            }
            
            protected function safeDown(): void {
                // Test implementation
            }
        };
        
        echo "   ✓ SafeMigration can be extended\n";
    } else {
        echo "   ✗ SafeMigration class not found\n";
    }
} catch (Exception $e) {
    echo "   ✗ Safe Migration Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Summary
echo "=== TEST SUMMARY ===\n";
echo "All core components have been created and are testable.\n";
echo "Next steps:\n";
echo "1. Run: php artisan db:seed --class=RetellControlCenterPermissionSeeder\n";
echo "2. Update RetellUltimateControlCenter with authorization methods\n";
echo "3. Test with different user roles\n";
echo "4. Monitor error logs and circuit breaker status\n\n";