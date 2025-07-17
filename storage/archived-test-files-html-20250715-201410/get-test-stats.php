<?php
// Quick script to get test stats
$html = file_get_contents('https://api.askproai.de/ultimate-portal-test-suite.php');

// Extract stats using regex
if (preg_match('/<div class="stat-number" id="total-tests">(\d+)<\/div>/', $html, $total)) {
    echo "Total Tests: " . $total[1] . "\n";
}
if (preg_match('/<div class="stat-number" id="passed-tests"[^>]*>(\d+)<\/div>/', $html, $passed)) {
    echo "Passed: " . $passed[1] . "\n";
}
if (preg_match('/<div class="stat-number" id="failed-tests"[^>]*>(\d+)<\/div>/', $html, $failed)) {
    echo "Failed: " . $failed[1] . "\n";
}
if (preg_match('/<div class="stat-number" id="success-rate">(\d+%)/', $html, $rate)) {
    echo "Success Rate: " . $rate[1] . "\n";
}

// Count test results
$passedCount = substr_count($html, 'class="test-result passed"');
$failedCount = substr_count($html, 'class="test-result failed"');

echo "\nDirect count:\n";
echo "Passed tests: $passedCount\n";
echo "Failed tests: $failedCount\n";
echo "Total: " . ($passedCount + $failedCount) . "\n";
echo "Success rate: " . round(($passedCount / ($passedCount + $failedCount)) * 100) . "%\n";