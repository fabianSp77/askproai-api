#!/usr/bin/env php
<?php

// Quick test runner
$testDirs = [
    'tests/Unit' => 'Unit Tests',
    'tests/Feature' => 'Feature Tests',
    'tests/Integration' => 'Integration Tests',
    'tests/E2E' => 'E2E Tests',
];

$results = [];
$totalPassed = 0;
$totalFailed = 0;

foreach ($testDirs as $dir => $label) {
    if (!is_dir($dir)) continue;
    
    echo "\n=== Running $label ===\n";
    
    $output = [];
    $returnCode = 0;
    exec("./vendor/bin/phpunit --no-coverage $dir 2>&1", $output, $returnCode);
    
    $outputStr = implode("\n", $output);
    
    // Parse results
    if (preg_match('/OK \((\d+) tests?, (\d+) assertions?\)/', $outputStr, $matches)) {
        $tests = intval($matches[1]);
        $results[$label] = ['passed' => $tests, 'failed' => 0];
        $totalPassed += $tests;
        echo "✅ $label: $tests tests passed\n";
    } elseif (preg_match('/Tests:\s*(\d+),.*?(?:Failures:\s*(\d+))?(?:,\s*Errors:\s*(\d+))?/', $outputStr, $matches)) {
        $tests = intval($matches[1]);
        $failures = intval($matches[2] ?? 0);
        $errors = intval($matches[3] ?? 0);
        $failed = $failures + $errors;
        $passed = $tests - $failed;
        
        $results[$label] = ['passed' => $passed, 'failed' => $failed];
        $totalPassed += $passed;
        $totalFailed += $failed;
        
        echo "⚠️  $label: $passed passed, $failed failed (of $tests total)\n";
    } else {
        echo "❌ $label: Could not parse results\n";
        $results[$label] = ['passed' => 0, 'failed' => 0];
    }
}

$total = $totalPassed + $totalFailed;

echo "\n========================================\n";
echo "SUMMARY\n";
echo "========================================\n";
echo "Total Tests: $total\n";
echo "✅ Passed: $totalPassed (" . ($total > 0 ? round($totalPassed/$total*100, 1) : 0) . "%)\n";
echo "❌ Failed: $totalFailed\n";
echo "\nBreakdown:\n";
foreach ($results as $label => $result) {
    echo "- $label: {$result['passed']} passed, {$result['failed']} failed\n";
}