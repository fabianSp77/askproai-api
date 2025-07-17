<?php

echo "\n🧪 Backend Test Continuation Script\n";
echo "=====================================\n\n";

// Check current test status
echo "📊 Current Test Status:\n";
$output = shell_exec('./vendor/bin/phpunit --list-tests 2>&1 | grep -E "Tests\\\\Unit|Tests\\\\Feature|Tests\\\\Integration|Tests\\\\E2E" | wc -l');
echo "Total available tests: " . trim($output) . "\n\n";

// Test categories to run
$testSuites = [
    'Unit Tests' => [
        'tests/Unit/Helpers',
        'tests/Unit/Utils', 
        'tests/Unit/Traits',
        'tests/Unit/Services/Validation',
        'tests/Unit/Services/Translation',
        'tests/Unit/Services/Cache',
        'tests/Unit/Models',
        'tests/Unit/Http/Middleware',
        'tests/Unit/Console/Commands'
    ],
    'Repository Tests' => [
        'tests/Unit/Repositories/AppointmentRepositoryTest.php',
        'tests/Unit/Repositories/CallRepositoryTest.php',
        'tests/Unit/Repositories/CustomerRepositoryTest.php',
        'tests/Unit/Repositories/StaffRepositoryTest.php',
        'tests/Unit/Repositories/ServiceRepositoryTest.php'
    ],
    'Integration Tests' => [
        'tests/Integration/Webhook',
        'tests/Integration/Services',
        'tests/Integration/API'
    ],
    'Feature Tests' => [
        'tests/Feature/API/AuthenticationTest.php',
        'tests/Feature/API/AppointmentTest.php',
        'tests/Feature/API/CallTest.php',
        'tests/Feature/Webhook/WebhookIntegrationTest.php'
    ]
];

// Function to run tests and collect results
function runTestSuite($name, $paths) {
    echo "\n🚀 Running $name...\n";
    echo str_repeat('-', 50) . "\n";
    
    $totalTests = 0;
    $totalPassed = 0;
    $totalFailed = 0;
    
    foreach ($paths as $path) {
        if (!file_exists($path) && !file_exists(dirname($path))) {
            echo "⚠️  Skipping $path (not found)\n";
            continue;
        }
        
        echo "Testing: $path\n";
        
        // Run tests with minimal output
        $command = "./vendor/bin/phpunit $path --no-coverage --colors=never 2>&1";
        $output = shell_exec($command);
        
        // Parse results
        if (preg_match('/OK \((\d+) test/', $output, $matches)) {
            $tests = (int)$matches[1];
            $totalTests += $tests;
            $totalPassed += $tests;
            echo "✅ $tests tests passed\n";
        } elseif (preg_match('/Tests: (\d+),.*Failures: (\d+)/', $output, $matches)) {
            $tests = (int)$matches[1];
            $failures = (int)$matches[2];
            $totalTests += $tests;
            $totalFailed += $failures;
            $totalPassed += ($tests - $failures);
            echo "❌ $failures of $tests tests failed\n";
        } elseif (preg_match('/No tests executed/', $output)) {
            echo "⚠️  No tests found\n";
        } else {
            echo "❓ Could not parse results\n";
            if (strpos($output, 'Error') !== false || strpos($output, 'Fatal') !== false) {
                echo "Error output: " . substr($output, 0, 200) . "...\n";
            }
        }
    }
    
    echo "\n📈 $name Summary:\n";
    echo "Total: $totalTests | Passed: $totalPassed | Failed: $totalFailed\n";
    
    return [
        'total' => $totalTests,
        'passed' => $totalPassed,
        'failed' => $totalFailed
    ];
}

// Quick fixes before running tests
echo "🔧 Applying quick fixes...\n";

// 1. Create missing directories
$directories = [
    'tests/Unit/Helpers',
    'tests/Unit/Utils',
    'tests/Unit/Traits',
    'tests/Unit/Services/Validation',
    'tests/Unit/Services/Translation',
    'tests/Unit/Services/Cache',
    'tests/Unit/Console/Commands',
    'tests/Integration/Webhook',
    'tests/Integration/Services',
    'tests/Integration/API'
];

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
        echo "📁 Created directory: $dir\n";
    }
}

// 2. Clear cache
echo "\n🧹 Clearing cache...\n";
shell_exec('php artisan cache:clear 2>&1');
shell_exec('php artisan config:clear 2>&1');
shell_exec('php artisan view:clear 2>&1');

// Run all test suites
$grandTotal = ['total' => 0, 'passed' => 0, 'failed' => 0];

foreach ($testSuites as $suiteName => $paths) {
    $results = runTestSuite($suiteName, $paths);
    $grandTotal['total'] += $results['total'];
    $grandTotal['passed'] += $results['passed'];
    $grandTotal['failed'] += $results['failed'];
}

// Final summary
echo "\n\n🏆 GRAND TOTAL SUMMARY\n";
echo "======================\n";
echo "Total Tests Run: {$grandTotal['total']}\n";
echo "Tests Passed: {$grandTotal['passed']} ✅\n";
echo "Tests Failed: {$grandTotal['failed']} ❌\n";
$percentage = $grandTotal['total'] > 0 ? round(($grandTotal['passed'] / $grandTotal['total']) * 100, 2) : 0;
echo "Success Rate: {$percentage}%\n\n";

// Save results
$timestamp = date('Y-m-d H:i:s');
$results = [
    'timestamp' => $timestamp,
    'total' => $grandTotal['total'],
    'passed' => $grandTotal['passed'],
    'failed' => $grandTotal['failed'],
    'percentage' => $percentage
];

file_put_contents('test-results-' . date('Y-m-d-His') . '.json', json_encode($results, JSON_PRETTY_PRINT));

// Compare with yesterday's results
echo "📊 Progress since yesterday:\n";
echo "Yesterday: 203 tests (31 → 203 = +555%)\n";
echo "Today: {$grandTotal['total']} tests\n";
if ($grandTotal['total'] > 203) {
    $growth = round((($grandTotal['total'] - 203) / 203) * 100, 2);
    echo "Growth: +{$growth}% 🚀\n";
} else {
    echo "Need to activate more tests!\n";
}

// Next steps
echo "\n📋 Next Steps:\n";
if ($grandTotal['failed'] > 0) {
    echo "1. Fix the {$grandTotal['failed']} failing tests\n";
    echo "2. Run: ./vendor/bin/phpunit --failed-only\n";
}
echo "3. Enable more test suites\n";
echo "4. Run code coverage: ./vendor/bin/phpunit --coverage-html coverage\n";
echo "5. Setup CI/CD pipeline\n";

echo "\n✅ Script completed!\n";