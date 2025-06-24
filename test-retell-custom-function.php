#!/usr/bin/env php
<?php
/**
 * Test Retell Custom Functions directly
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\MCP\RetellCustomFunctionMCPServer;
use App\Models\Company;
use App\Models\Branch;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

echo "\n========================================\n";
echo "RETELL CUSTOM FUNCTION TEST\n";
echo "========================================\n";

// Get test company
$company = Company::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->where('is_active', true)
    ->first();

if (!$company) {
    echo "❌ No active company found!\n";
    exit(1);
}

// Set tenant context
app()->instance('current_company', $company);
app()->bind('tenant.company_id', function() use ($company) {
    return $company->id;
});

$branch = Branch::withoutGlobalScope(\App\Scopes\TenantScope::class)
    ->where('company_id', $company->id)
    ->where('phone_number', '+49 30 837 93 369')
    ->first();

echo "Using Company: {$company->name} (ID: {$company->id})\n";
echo "Using Branch: " . ($branch ? $branch->name : 'None') . "\n";

// Test configuration
$testCallId = 'test_' . Str::uuid();
$testPhoneNumber = '+49 30 837 93 369';
$customerPhone = '+49 151 12345678';

echo "\nTest Configuration:\n";
echo "- Call ID: $testCallId\n";
echo "- Company Phone: $testPhoneNumber\n";
echo "- Customer Phone: $customerPhone\n";

// Initialize custom function server
$customFunctionServer = app(RetellCustomFunctionMCPServer::class);

// Test 1: collect_appointment
echo "\n[TEST 1] Testing collect_appointment function...\n";

$appointmentData = [
    'call_id' => $testCallId,
    'caller_number' => $customerPhone,
    'to_number' => $testPhoneNumber,
    'name' => 'Max Mustermann',
    'telefonnummer' => $customerPhone,
    'dienstleistung' => 'Beratungsgespräch',
    'datum' => 'morgen',
    'uhrzeit' => '14:00',
    'notizen' => 'Erstberatung gewünscht'
];

try {
    $result = $customFunctionServer->collect_appointment($appointmentData);
    
    echo "Result:\n";
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    
    if ($result['success']) {
        echo "✅ collect_appointment succeeded\n";
        
        // Check cache
        $cacheKey = "retell:appointment:{$testCallId}";
        $cachedData = Cache::get($cacheKey);
        
        if ($cachedData) {
            echo "✅ Data found in cache\n";
            echo "Cache content:\n";
            echo json_encode($cachedData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
        } else {
            echo "❌ Data NOT found in cache!\n";
        }
    } else {
        echo "❌ collect_appointment failed: " . ($result['error'] ?? 'Unknown error') . "\n";
    }
} catch (\Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}

// Test 2: check_availability
echo "\n[TEST 2] Testing check_availability function...\n";

$availabilityData = [
    'caller_number' => $customerPhone,
    'to_number' => $testPhoneNumber,
    'datum' => 'morgen',
    'dienstleistung' => 'Beratungsgespräch'
];

try {
    $result = $customFunctionServer->check_availability($availabilityData);
    
    echo "Result:\n";
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    
    if ($result['success']) {
        echo "✅ check_availability succeeded\n";
        if ($result['available']) {
            echo "✅ Slots available: " . $result['available_times'] . "\n";
        } else {
            echo "⚠️  No slots available\n";
        }
    } else {
        echo "❌ check_availability failed: " . ($result['error'] ?? 'Unknown error') . "\n";
    }
} catch (\Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}

// Test 3: Health check
echo "\n[TEST 3] Testing health check...\n";

try {
    $result = $customFunctionServer->health();
    echo "Health check result:\n";
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
} catch (\Exception $e) {
    echo "❌ Health check failed: " . $e->getMessage() . "\n";
}

// Cleanup
if (isset($testCallId)) {
    $cacheKey = "retell:appointment:{$testCallId}";
    Cache::forget($cacheKey);
    echo "\n✅ Cache cleaned up\n";
}

echo "\n========================================\n";
echo "TEST COMPLETED\n";
echo "========================================\n\n";