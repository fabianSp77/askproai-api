#!/usr/bin/env php
<?php

/**
 * Simple Page Visibility Checker
 * Tests which pages are accessible without using PHPUnit to avoid segfaults
 */

// Colors for output
$GREEN = "\033[0;32m";
$RED = "\033[0;31m";
$YELLOW = "\033[0;33m";
$RESET = "\033[0m";

$baseUrl = 'https://api.askproai.de';
$results = [];
$totalTests = 0;
$passedTests = 0;

echo "\n{$YELLOW}========================================{$RESET}\n";
echo "{$YELLOW}   PAGE VISIBILITY CHECK{$RESET}\n";
echo "{$YELLOW}========================================{$RESET}\n\n";

// Function to test a URL
function testUrl($url, $expectedStatus = 200, $auth = false) {
    global $GREEN, $RED, $RESET, $totalTests, $passedTests;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_NOBODY, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    
    if ($auth) {
        // Add auth cookie if needed (simplified for testing)
        curl_setopt($ch, CURLOPT_COOKIE, "laravel_session=test_session");
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $totalTests++;
    
    // Check if response is as expected
    $passed = false;
    if (is_array($expectedStatus)) {
        $passed = in_array($httpCode, $expectedStatus);
    } else {
        $passed = ($httpCode == $expectedStatus);
    }
    
    if ($passed) {
        $passedTests++;
        echo "{$GREEN}✓{$RESET} ";
    } else {
        echo "{$RED}✗{$RESET} ";
    }
    
    $statusText = $httpCode ?: 'ERROR';
    echo "{$url} [{$statusText}]\n";
    
    return [
        'url' => $url,
        'status' => $httpCode,
        'passed' => $passed
    ];
}

// Test public pages
echo "\n{$YELLOW}Testing Public Pages:{$RESET}\n";
echo str_repeat('-', 40) . "\n";

$results['public'] = [];
$results['public'][] = testUrl($baseUrl . '/', 200);
$results['public'][] = testUrl($baseUrl . '/health', 200);
$results['public'][] = testUrl($baseUrl . '/admin/login', 200);

// Test admin pages (should redirect to login without auth)
echo "\n{$YELLOW}Testing Admin Pages (No Auth):{$RESET}\n";
echo str_repeat('-', 40) . "\n";

$results['admin_noauth'] = [];
$results['admin_noauth'][] = testUrl($baseUrl . '/admin', [302, 301]);
$results['admin_noauth'][] = testUrl($baseUrl . '/admin/users', [302, 301]);
$results['admin_noauth'][] = testUrl($baseUrl . '/admin/customers', [302, 301]);

// Test admin resources
echo "\n{$YELLOW}Testing Admin Resources:{$RESET}\n";
echo str_repeat('-', 40) . "\n";

$resources = [
    'users',
    'customers',
    'companies',
    'branches',
    'staff',
    'services',
    'appointments',
    'calls',
    'transactions',
    'balance-topups',
    'tenants',
    'retell-agents',
    'integrations',
    'working-hours',
    'pricing-plans',
    'phone-numbers',
];

$results['resources'] = [];
foreach ($resources as $resource) {
    // These should redirect to login without auth
    $results['resources'][] = testUrl($baseUrl . "/admin/{$resource}", [302, 301, 404]);
}

// Test API endpoints
echo "\n{$YELLOW}Testing API Endpoints:{$RESET}\n";
echo str_repeat('-', 40) . "\n";

$results['api'] = [];
$results['api'][] = testUrl($baseUrl . '/api/health', [200, 404]);

// Test billing pages
echo "\n{$YELLOW}Testing Billing Pages:{$RESET}\n";
echo str_repeat('-', 40) . "\n";

$results['billing'] = [];
$results['billing'][] = testUrl($baseUrl . '/billing', [302, 301]);
$results['billing'][] = testUrl($baseUrl . '/billing/transactions', [302, 301]);
$results['billing'][] = testUrl($baseUrl . '/billing/topup', [302, 301]);

// Test customer portal
echo "\n{$YELLOW}Testing Customer Portal:{$RESET}\n";
echo str_repeat('-', 40) . "\n";

$results['customer'] = [];
$results['customer'][] = testUrl($baseUrl . '/customer/dashboard', [302, 301, 404]);
$results['customer'][] = testUrl($baseUrl . '/customer/appointments', [302, 301, 404]);
$results['customer'][] = testUrl($baseUrl . '/customer/profile', [302, 301, 404]);

// Generate summary
echo "\n{$YELLOW}========================================{$RESET}\n";
echo "{$YELLOW}   TEST SUMMARY{$RESET}\n";
echo "{$YELLOW}========================================{$RESET}\n\n";

$percentage = round(($passedTests / $totalTests) * 100, 2);
$color = $percentage >= 70 ? $GREEN : ($percentage >= 50 ? $YELLOW : $RED);

echo "Total Tests: {$totalTests}\n";
echo "Passed: {$GREEN}{$passedTests}{$RESET}\n";
echo "Failed: {$RED}" . ($totalTests - $passedTests) . "{$RESET}\n";
echo "Success Rate: {$color}{$percentage}%{$RESET}\n\n";

// Category breakdown
foreach ($results as $category => $tests) {
    $catPassed = array_filter($tests, fn($t) => $t['passed']);
    $catTotal = count($tests);
    $catPassedCount = count($catPassed);
    
    $catColor = $catPassedCount == $catTotal ? $GREEN : 
                ($catPassedCount > 0 ? $YELLOW : $RED);
    
    echo str_pad(ucfirst(str_replace('_', ' ', $category)) . ':', 20) . 
         "{$catColor}{$catPassedCount}/{$catTotal}{$RESET}\n";
}

// Generate report file
$report = "# Page Visibility Report\n\n";
$report .= "**Date**: " . date('Y-m-d H:i:s') . "\n";
$report .= "**Success Rate**: {$percentage}%\n";
$report .= "**Total Pages Tested**: {$totalTests}\n";
$report .= "**Passed**: {$passedTests}\n";
$report .= "**Failed**: " . ($totalTests - $passedTests) . "\n\n";

$report .= "## Detailed Results\n\n";

foreach ($results as $category => $tests) {
    $report .= "### " . ucfirst(str_replace('_', ' ', $category)) . "\n\n";
    $report .= "| URL | Status | Result |\n";
    $report .= "|-----|--------|--------|\n";
    
    foreach ($tests as $test) {
        $icon = $test['passed'] ? '✅' : '❌';
        $report .= "| {$test['url']} | {$test['status']} | {$icon} |\n";
    }
    
    $report .= "\n";
}

$reportFile = __DIR__ . '/../VISIBILITY_REPORT_' . date('Y-m-d_His') . '.md';
file_put_contents($reportFile, $report);

echo "\n{$YELLOW}Report saved to:{$RESET} " . basename($reportFile) . "\n\n";

// Return exit code based on success rate
exit($percentage >= 70 ? 0 : 1);