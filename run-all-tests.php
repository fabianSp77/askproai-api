#!/usr/bin/env php
<?php

echo "🚀 Starting Complete Test Suite Execution\n";
echo "========================================\n\n";

$testFiles = shell_exec("find tests -name '*Test.php' -type f | sort");
$tests = array_filter(explode("\n", $testFiles));
$totalTests = count($tests);

$results = [
    'passed' => [],
    'failed' => [],
    'errors' => []
];

$startTime = microtime(true);

foreach ($tests as $index => $testFile) {
    $progress = ($index + 1) . "/$totalTests";
    echo "[$progress] Testing: $testFile\n";
    
    // Run the test
    $output = shell_exec("php artisan test $testFile 2>&1");
    
    // Check result
    if (strpos($output, 'PASS') !== false && strpos($output, 'FAIL') === false) {
        $results['passed'][] = $testFile;
        echo "✅ PASSED\n";
    } elseif (strpos($output, 'FAIL') !== false) {
        $results['failed'][] = $testFile;
        echo "❌ FAILED\n";
        
        // Extract error summary
        preg_match('/Tests:\s+(\d+)\s+failed/', $output, $matches);
        if ($matches) {
            echo "   Failed: {$matches[1]} tests\n";
        }
        
        // Save detailed log
        file_put_contents("tests/test-results/" . basename($testFile) . ".log", $output);
    } else {
        $results['errors'][] = $testFile;
        echo "⚠️  ERROR\n";
        file_put_contents("tests/test-results/" . basename($testFile) . ".error.log", $output);
    }
    
    echo "\n";
}

$endTime = microtime(true);
$duration = round($endTime - $startTime, 2);

// Summary
echo "\n🏁 Test Execution Complete!\n";
echo "==========================\n";
echo "Total Tests: $totalTests\n";
echo "✅ Passed: " . count($results['passed']) . "\n";
echo "❌ Failed: " . count($results['failed']) . "\n";
echo "⚠️  Errors: " . count($results['errors']) . "\n";
echo "⏱️  Duration: {$duration}s\n\n";

// Save summary
$summary = [
    'timestamp' => date('Y-m-d H:i:s'),
    'total' => $totalTests,
    'passed' => count($results['passed']),
    'failed' => count($results['failed']),
    'errors' => count($results['errors']),
    'duration' => $duration,
    'results' => $results
];

file_put_contents('tests/test-results/summary.json', json_encode($summary, JSON_PRETTY_PRINT));

// List failed tests for quick reference
if (!empty($results['failed'])) {
    echo "Failed Tests:\n";
    foreach ($results['failed'] as $test) {
        echo "- $test\n";
    }
}

echo "\nDetailed logs saved in: tests/test-results/\n";