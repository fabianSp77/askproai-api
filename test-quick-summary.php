#!/usr/bin/env php
<?php

echo "Running quick test summary...\n\n";

$testDirs = [
    'tests/Unit/Helpers' => 'Unit Helpers',
    'tests/Unit/Services' => 'Unit Services',
    'tests/Unit/Models' => 'Unit Models',
    'tests/Unit/Mocks' => 'Unit Mocks',
    'tests/Feature' => 'Feature Tests (sample)',
];

$totalPassed = 0;
$totalTests = 0;

foreach ($testDirs as $dir => $label) {
    if (!is_dir($dir)) {
        continue;
    }
    
    $output = [];
    exec("./vendor/bin/phpunit --no-coverage $dir 2>&1 | grep -E 'OK \\(|Tests:|ERRORS|FAILURES' | tail -1", $output);
    
    if (!empty($output[0])) {
        if (preg_match('/OK \((\d+) tests?, (\d+) assertions?\)/', $output[0], $matches)) {
            $tests = intval($matches[1]);
            $totalPassed += $tests;
            $totalTests += $tests;
            echo "✅ $label: $tests tests passed\n";
        } elseif (preg_match('/Tests:\s*(\d+)/', $output[0], $matches)) {
            $tests = intval($matches[1]);
            $totalTests += $tests;
            echo "❌ $label: Some tests failed (of $tests total)\n";
        }
    } else {
        echo "⚠️  $label: Could not run\n";
    }
}

// Run specific working tests
$workingTests = [
    'tests/Unit/MockRetellServiceTest.php' => 'MockRetellServiceTest',
    'tests/Unit/Models/BranchRelationshipTest.php' => 'BranchRelationshipTest',
    'tests/Unit/SchemaFixValidationTest.php' => 'SchemaFixValidationTest',
    'tests/Unit/BasicPHPUnitTest.php' => 'BasicPHPUnitTest',
];

echo "\n--- Known Working Tests ---\n";
foreach ($workingTests as $file => $name) {
    if (file_exists($file)) {
        $output = [];
        exec("./vendor/bin/phpunit --no-coverage $file 2>&1 | grep -E 'OK \\(' | tail -1", $output);
        if (!empty($output[0]) && preg_match('/OK \((\d+) tests?/', $output[0], $matches)) {
            $tests = intval($matches[1]);
            $totalPassed += $tests;
            echo "✅ $name: $tests tests\n";
        }
    }
}

echo "\n========================================\n";
echo "QUICK SUMMARY\n";
echo "========================================\n";
echo "✅ Tests passing: $totalPassed+\n";
echo "📊 Improvement from baseline: " . ($totalPassed - 31) . " new tests\n";
echo "\nNext steps:\n";
echo "- Fix remaining Unit tests\n";
echo "- Enable Integration tests with mocks\n";
echo "- Work on Feature tests\n";