<?php
/**
 * Test Script: Verify Anonymous Caller Phone Number NULL Fix
 *
 * This script tests the fix for the critical production bug where
 * anonymous callers couldn't book appointments due to NULL phoneNumber
 * relationship in getCallContext().
 *
 * Run: php verify_anonymous_caller_fix.php
 */

echo "\n========================================\n";
echo "ANONYMOUS CALLER FIX VERIFICATION\n";
echo "========================================\n\n";

// Load Laravel
require_once __DIR__ . '/bootstrap/app.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
$request = \Illuminate\Http\Request::capture();
$kernel->handle($request);

use App\Models\Call;
use App\Http\Controllers\RetellFunctionCallHandler;
use Illuminate\Support\Facades\Log;

// Test 1: Find anonymous call from production
echo "TEST 1: Finding anonymous caller from production\n";
echo "-------------------------------------------\n";

$anonymousCall = Call::where('from_number', 'anonymous')
    ->orWhereNull('from_number')
    ->latest()
    ->first();

if (!$anonymousCall) {
    echo "✗ No anonymous calls found in database\n";
    echo "  (This is OK - creating test scenario)\n\n";

    // Create test call
    $testCall = Call::create([
        'retell_call_id' => 'test_' . uniqid(),
        'from_number' => 'anonymous',
        'to_number' => '+493033081738',
        'phone_number_id' => null,  // Key: NULL for anonymous
        'company_id' => 1,
        'branch_id' => 'test-branch',
        'status' => 'test',
        'call_status' => 'test'
    ]);
    $anonymousCall = $testCall;
    echo "✓ Created test anonymous call: {$testCall->id}\n\n";
} else {
    echo "✓ Found anonymous call from production\n";
    echo "  ID: {$anonymousCall->id}\n";
    echo "  Retell ID: {$anonymousCall->retell_call_id}\n";
    echo "  from_number: {$anonymousCall->from_number}\n";
    echo "  to_number: {$anonymousCall->to_number}\n";
    echo "  phone_number_id: " . ($anonymousCall->phone_number_id ?? 'NULL') . "\n";
    echo "  company_id: {$anonymousCall->company_id}\n\n";
}

// Test 2: Verify NULL phoneNumber relationship
echo "TEST 2: Verify NULL phoneNumber relationship\n";
echo "-------------------------------------------\n";

$phoneNumber = $anonymousCall->phoneNumber;
if ($phoneNumber === null) {
    echo "✓ phoneNumber relationship is NULL (expected for anonymous)\n\n";
} else {
    echo "✗ phoneNumber relationship is NOT NULL\n";
    echo "  This means the test scenario isn't right\n";
    exit(1);
}

// Test 3: Test the getCallContext fix
echo "TEST 3: Test getCallContext() method\n";
echo "-------------------------------------------\n";

try {
    // Create a reflection to access private method
    $reflection = new ReflectionClass('App\Http\Controllers\RetellFunctionCallHandler');
    $method = $reflection->getMethod('getCallContext');
    $method->setAccessible(true);

    // Create controller instance
    $container = app();
    $handler = $container->make(RetellFunctionCallHandler::class);

    // Call the method
    $context = $method->invoke($handler, $anonymousCall->retell_call_id);

    if ($context === null) {
        echo "✗ getCallContext returned NULL\n";
        echo "  This means the fix didn't work\n";
        exit(1);
    }

    echo "✓ getCallContext returned valid context\n";
    echo "  company_id: " . ($context['company_id'] ?? 'NULL') . "\n";
    echo "  branch_id: " . ($context['branch_id'] ?? 'NULL') . "\n";
    echo "  phone_number_id: " . ($context['phone_number_id'] ?? 'NULL') . "\n";
    echo "  call_id: {$context['call_id']}\n\n";

    // Test 4: Verify company_id is preserved
    echo "TEST 4: Verify company_id fallback works\n";
    echo "-------------------------------------------\n";

    if ($context['company_id'] === $anonymousCall->company_id) {
        echo "✓ company_id correctly taken from direct Call field\n";
        echo "  Expected: {$anonymousCall->company_id}\n";
        echo "  Got: {$context['company_id']}\n\n";
    } else {
        echo "✗ company_id mismatch\n";
        echo "  Expected: {$anonymousCall->company_id}\n";
        echo "  Got: {$context['company_id']}\n";
        exit(1);
    }

    // Test 5: Verify phone_number_id is NULL (since no relationship)
    echo "TEST 5: Verify phone_number_id NULL for anonymous\n";
    echo "-------------------------------------------\n";

    if ($context['phone_number_id'] === null) {
        echo "✓ phone_number_id correctly set to NULL for anonymous caller\n\n";
    } else {
        echo "✗ phone_number_id should be NULL for anonymous\n";
        echo "  Got: {$context['phone_number_id']}\n";
        exit(1);
    }

} catch (ReflectionException $e) {
    echo "✗ Could not call private method: {$e->getMessage()}\n";
    exit(1);
} catch (Exception $e) {
    echo "✗ Error testing getCallContext: {$e->getMessage()}\n";
    echo "  Trace: {$e->getTraceAsString()}\n";
    exit(1);
}

// Summary
echo "========================================\n";
echo "VERIFICATION COMPLETE: ALL TESTS PASSED\n";
echo "========================================\n";
echo "\nFix Status: READY FOR PRODUCTION\n";
echo "The anonymous caller issue is RESOLVED\n\n";

// Cleanup
if (isset($testCall)) {
    $testCall->delete();
    echo "Cleaned up test call\n";
}
