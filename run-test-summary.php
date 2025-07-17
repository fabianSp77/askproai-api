#!/usr/bin/env php
<?php

// Run tests and provide summary
$start = microtime(true);
$totalTests = 0;
$passedTests = 0;
$failedTests = 0;
$errors = [];

// Get all test files
$testFiles = glob(__DIR__ . '/tests/**/*Test.php', GLOB_BRACE);
$testFiles = array_filter($testFiles, function($file) {
    return !str_contains($file, '/Traits/');
});

echo "Found " . count($testFiles) . " test files\n";
echo "Running tests...\n\n";

foreach ($testFiles as $testFile) {
    $className = str_replace([__DIR__ . '/', '.php', '/'], ['', '', '\\'], $testFile);
    
    // Run single test file
    $output = [];
    $returnCode = 0;
    exec("./vendor/bin/phpunit --no-coverage --no-configuration --bootstrap vendor/autoload.php $testFile 2>&1", $output, $returnCode);
    
    $outputStr = implode("\n", $output);
    
    // Parse results
    if (preg_match('/OK \((\d+) test/', $outputStr, $matches)) {
        $tests = intval($matches[1]);
        $totalTests += $tests;
        $passedTests += $tests;
        echo "✓ " . basename($testFile) . " - $tests tests passed\n";
    } elseif (preg_match('/Tests:\s*(\d+),\s*Assertions:\s*(\d+),\s*Failures:\s*(\d+)/', $outputStr, $matches)) {
        $tests = intval($matches[1]);
        $failures = intval($matches[3]);
        $totalTests += $tests;
        $failedTests += $failures;
        $passedTests += ($tests - $failures);
        echo "✗ " . basename($testFile) . " - $failures/$tests tests failed\n";
        
        // Extract first error
        if (preg_match('/1\) (.+?)\n(.+?)$/m', $outputStr, $errorMatch)) {
            $errors[] = basename($testFile) . ": " . $errorMatch[1];
        }
    } elseif (preg_match('/No tests found|Could not find/', $outputStr)) {
        echo "- " . basename($testFile) . " - No tests found\n";
    } elseif (preg_match('/Error:|Fatal error:|Parse error:/', $outputStr)) {
        echo "✗ " . basename($testFile) . " - Error in test file\n";
        $failedTests++;
        $totalTests++;
        
        if (preg_match('/(Error:.+?)$/m', $outputStr, $errorMatch)) {
            $errors[] = basename($testFile) . ": " . $errorMatch[1];
        }
    } else {
        echo "? " . basename($testFile) . " - Unknown result\n";
    }
    
    // Prevent memory issues
    if ($totalTests > 50) {
        echo "\n... stopping after 50 tests for quick overview ...\n";
        break;
    }
}

$duration = round(microtime(true) - $start, 2);

echo "\n========================================\n";
echo "TEST SUMMARY\n";
echo "========================================\n";
echo "Total Tests: $totalTests\n";
echo "Passed: $passedTests (" . ($totalTests > 0 ? round($passedTests/$totalTests*100, 1) : 0) . "%)\n";
echo "Failed: $failedTests\n";
echo "Duration: {$duration}s\n";

if (!empty($errors)) {
    echo "\nFirst few errors:\n";
    foreach (array_slice($errors, 0, 5) as $error) {
        echo "- $error\n";
    }
}

echo "\n";
exit($failedTests > 0 ? 1 : 0);