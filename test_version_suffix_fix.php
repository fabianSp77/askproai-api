<?php

/**
 * Test Version Suffix Fix
 *
 * Verifies that the version suffix stripping works correctly
 */

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║  Testing Version Suffix Fix                                ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

// Test cases
$testCases = [
    ['input' => 'book_appointment_v17', 'expected' => 'book_appointment'],
    ['input' => 'check_availability_v17', 'expected' => 'check_availability'],
    ['input' => 'book_appointment_v18', 'expected' => 'book_appointment'],
    ['input' => 'book_appointment_v123', 'expected' => 'book_appointment'],
    ['input' => 'book_appointment', 'expected' => 'book_appointment'],  // No suffix
    ['input' => 'check_customer', 'expected' => 'check_customer'],  // No suffix
    ['input' => 'query_appointment_v17', 'expected' => 'query_appointment'],
];

echo "=== Testing Regex Pattern ===\n\n";

$allPassed = true;

foreach ($testCases as $test) {
    $input = $test['input'];
    $expected = $test['expected'];

    // Apply the same regex as in the fix
    $result = preg_replace('/_v\d+$/', '', $input);

    $passed = $result === $expected;
    $status = $passed ? '✅' : '❌';

    echo "{$status} Input: '{$input}'\n";
    echo "   Expected: '{$expected}'\n";
    echo "   Got:      '{$result}'\n";

    if (!$passed) {
        echo "   ⚠️  MISMATCH!\n";
        $allPassed = false;
    }
    echo "\n";
}

echo "\n";
echo "╔════════════════════════════════════════════════════════════╗\n";
if ($allPassed) {
    echo "║  ✅ ALL TESTS PASSED!                                      ║\n";
} else {
    echo "║  ❌ SOME TESTS FAILED!                                     ║\n";
}
echo "╚════════════════════════════════════════════════════════════╝\n";

exit($allPassed ? 0 : 1);
