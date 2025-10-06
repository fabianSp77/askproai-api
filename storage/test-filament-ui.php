#!/usr/bin/env php
<?php

/**
 * Comprehensive Filament UI Testing Script
 * Tests all 31 Filament Resources with HTTP requests
 */

$baseUrl = 'https://api.askproai.de';
$loginUrl = $baseUrl . '/admin/login';
$credentials = [
    'email' => 'admin@askproai.de',
    'password' => 'admin123',
];

// All 31 resources to test
$resources = [
    ['name' => 'Companies', 'path' => 'companies'],
    ['name' => 'Branches', 'path' => 'branches'],
    ['name' => 'Services', 'path' => 'services'],
    ['name' => 'Staff', 'path' => 'staff'],
    ['name' => 'Customers', 'path' => 'customers'],
    ['name' => 'Appointments', 'path' => 'appointments'],
    ['name' => 'Calls', 'path' => 'calls'],
    ['name' => 'CallbackRequests', 'path' => 'callback-requests', 'critical' => true],
    ['name' => 'PolicyConfigurations', 'path' => 'policy-configurations', 'critical' => true],
    ['name' => 'NotificationConfigurations', 'path' => 'notification-configurations'],
    ['name' => 'AppointmentModifications', 'path' => 'appointment-modifications'],
    ['name' => 'ActivityLog', 'path' => 'activity-log'],
    ['name' => 'BalanceBonusTier', 'path' => 'balance-bonus-tiers'],
    ['name' => 'BalanceTopup', 'path' => 'balance-topups'],
    ['name' => 'CurrencyExchangeRate', 'path' => 'currency-exchange-rates'],
    ['name' => 'CustomerNote', 'path' => 'customer-notes'],
    ['name' => 'Integration', 'path' => 'integrations'],
    ['name' => 'Invoice', 'path' => 'invoices'],
    ['name' => 'NotificationQueue', 'path' => 'notification-queues'],
    ['name' => 'NotificationTemplate', 'path' => 'notification-templates'],
    ['name' => 'Permission', 'path' => 'permissions'],
    ['name' => 'PhoneNumber', 'path' => 'phone-numbers'],
    ['name' => 'PlatformCost', 'path' => 'platform-costs'],
    ['name' => 'PricingPlan', 'path' => 'pricing-plans'],
    ['name' => 'RetellAgent', 'path' => 'retell-agents'],
    ['name' => 'Role', 'path' => 'roles'],
    ['name' => 'SystemSettings', 'path' => 'system-settings'],
    ['name' => 'Tenant', 'path' => 'tenants'],
    ['name' => 'Transaction', 'path' => 'transactions'],
    ['name' => 'User', 'path' => 'users'],
    ['name' => 'WorkingHour', 'path' => 'working-hours'],
];

// Critical bug verification URLs
$criticalTests = [
    ['bug' => 'Bug 1', 'name' => 'CallbackRequest #1', 'url' => '/admin/callback-requests/1'],
    ['bug' => 'Bug 2', 'name' => 'PolicyConfiguration #14', 'url' => '/admin/policy-configurations/14'],
    ['bug' => 'Bug 3', 'name' => 'Appointment #487 Edit', 'url' => '/admin/appointments/487/edit'],
];

$cookieFile = sys_get_temp_dir() . '/filament-test-cookies.txt';
$results = [];
$criticalResults = [];

echo "\n═══════════════════════════════════════════════════════════════\n";
echo "  COMPREHENSIVE FILAMENT UI TESTING\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// Step 1: Login
echo "🔐 Logging in to Filament admin panel...\n";
$ch = curl_init($loginUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_COOKIEJAR => $cookieFile,
    CURLOPT_COOKIEFILE => $cookieFile,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_VERBOSE => false,
]);

// Get CSRF token first
$loginPage = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if ($httpCode !== 200) {
    echo "❌ Failed to load login page (HTTP $httpCode)\n";
    exit(1);
}

// Extract CSRF token
preg_match('/<input[^>]*name="_token"[^>]*value="([^"]*)"/', $loginPage, $matches);
$csrfToken = $matches[1] ?? null;

if (!$csrfToken) {
    echo "❌ Failed to extract CSRF token\n";
    exit(1);
}

// Perform login
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    '_token' => $csrfToken,
    'email' => $credentials['email'],
    'password' => $credentials['password'],
]));

$loginResponse = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200 && $httpCode !== 302) {
    echo "❌ Login failed (HTTP $httpCode)\n";
    exit(1);
}

echo "✅ Login successful\n\n";

// Step 2: Test CRITICAL bugs first
echo "🚨 CRITICAL BUG VERIFICATION (3 Fixed Bugs)\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

foreach ($criticalTests as $test) {
    echo "Testing {$test['bug']}: {$test['name']}...\n";
    $url = $baseUrl . $test['url'];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_COOKIEFILE => $cookieFile,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_NOBODY => true,
    ]);

    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $status = $httpCode === 200 ? '✅ FIXED' : '❌ STILL BROKEN';
    $criticalResults[] = [
        'bug' => $test['bug'],
        'name' => $test['name'],
        'url' => $test['url'],
        'status_code' => $httpCode,
        'result' => $status,
    ];

    echo "  → Status: $httpCode - $status\n";

    if ($httpCode !== 200) {
        echo "\n🚨 CRITICAL: {$test['bug']} is STILL BROKEN! Stopping test.\n";
        echo "URL: {$test['url']}\n";
        echo "Status Code: $httpCode\n\n";

        // Print critical results summary
        printCriticalSummary($criticalResults);
        exit(1);
    }

    usleep(500000); // 500ms delay between requests
}

echo "\n✅ All 3 critical bugs are FIXED!\n\n";

// Step 3: Test all 31 resources
echo "📋 TESTING ALL 31 FILAMENT RESOURCES\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$progressCount = 0;
$totalResources = count($resources);

foreach ($resources as $resource) {
    $progressCount++;
    $resourceName = $resource['name'];
    $resourcePath = $resource['path'];

    echo "[$progressCount/$totalResources] Testing {$resourceName}...\n";

    $resourceResult = [
        'name' => $resourceName,
        'path' => $resourcePath,
        'list_view' => testUrl($baseUrl . "/admin/{$resourcePath}", $cookieFile),
        'create_view' => testUrl($baseUrl . "/admin/{$resourcePath}/create", $cookieFile),
        'critical' => $resource['critical'] ?? false,
    ];

    // Try to get first record for detail/edit view
    $firstRecordId = getFirstRecordId($baseUrl . "/admin/{$resourcePath}", $cookieFile);
    if ($firstRecordId) {
        $resourceResult['detail_view'] = testUrl($baseUrl . "/admin/{$resourcePath}/{$firstRecordId}", $cookieFile);
        $resourceResult['edit_view'] = testUrl($baseUrl . "/admin/{$resourcePath}/{$firstRecordId}/edit", $cookieFile);
    }

    $results[] = $resourceResult;

    // Print quick status
    $listStatus = $resourceResult['list_view']['status'] === 200 ? '✅' : '❌';
    echo "  → List: $listStatus ({$resourceResult['list_view']['status']})\n";

    usleep(500000); // 500ms delay between resources
}

echo "\n";

// Step 4: Generate comprehensive report
generateReport($results, $criticalResults);

// Cleanup
if (file_exists($cookieFile)) {
    unlink($cookieFile);
}

echo "\n✅ Testing completed successfully!\n\n";

// ============================================================================
// Helper Functions
// ============================================================================

function testUrl($url, $cookieFile) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_COOKIEFILE => $cookieFile,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_NOBODY => true,
    ]);

    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    return [
        'status' => $httpCode,
        'success' => $httpCode === 200,
        'error' => $error ?: null,
    ];
}

function getFirstRecordId($listUrl, $cookieFile) {
    $ch = curl_init($listUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_COOKIEFILE => $cookieFile,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);

    $html = curl_exec($ch);
    curl_close($ch);

    // Try to extract first record ID from Filament table
    if (preg_match('/\/admin\/[^\/]+\/(\d+)(?:\/edit)?/', $html, $matches)) {
        return $matches[1];
    }

    return null;
}

function generateReport($results, $criticalResults) {
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "  COMPREHENSIVE TEST REPORT\n";
    echo "═══════════════════════════════════════════════════════════════\n\n";

    // Summary statistics
    $totalResources = count($results);
    $totalTests = 0;
    $successfulTests = 0;
    $failedTests = 0;
    $status200Count = 0;
    $statusNon200Count = 0;

    foreach ($results as $result) {
        foreach (['list_view', 'create_view', 'detail_view', 'edit_view'] as $view) {
            if (isset($result[$view])) {
                $totalTests++;
                if ($result[$view]['success']) {
                    $successfulTests++;
                    $status200Count++;
                } else {
                    $failedTests++;
                    $statusNon200Count++;
                }
            }
        }
    }

    echo "📊 TEST SUMMARY:\n";
    echo "  • Total Resources Tested: $totalResources/31\n";
    echo "  • Total HTTP Requests: $totalTests\n";
    echo "  • Status Codes: {$status200Count}×200, {$statusNon200Count}×non-200\n";
    echo "  • Success Rate: " . round(($successfulTests / $totalTests) * 100, 2) . "%\n";
    echo "  • Console Errors: N/A (HTTP testing mode)\n\n";

    // Critical bug verification
    echo "🚨 CRITICAL BUG VERIFICATION:\n";
    foreach ($criticalResults as $critical) {
        echo "  • {$critical['bug']} ({$critical['name']}): {$critical['result']} (HTTP {$critical['status_code']})\n";
    }
    echo "\n";

    // Per-resource results
    echo "📋 PER-RESOURCE RESULTS:\n\n";
    foreach ($results as $result) {
        $criticalTag = isset($result['critical']) && $result['critical'] ? ' [CRITICAL]' : '';
        echo "Resource: {$result['name']}$criticalTag\n";

        $listStatus = $result['list_view']['success'] ? '✅' : '❌';
        echo "  - List view: $listStatus (HTTP {$result['list_view']['status']})\n";

        if (isset($result['create_view'])) {
            $createStatus = $result['create_view']['success'] ? '✅' : '❌';
            echo "  - Create view: $createStatus (HTTP {$result['create_view']['status']})\n";
        }

        if (isset($result['detail_view'])) {
            $detailStatus = $result['detail_view']['success'] ? '✅' : '❌';
            echo "  - Detail view: $detailStatus (HTTP {$result['detail_view']['status']})\n";
        }

        if (isset($result['edit_view'])) {
            $editStatus = $result['edit_view']['success'] ? '✅' : '❌';
            echo "  - Edit view: $editStatus (HTTP {$result['edit_view']['status']})\n";
        }

        echo "\n";
    }

    // Failures list
    $failures = [];
    foreach ($results as $result) {
        foreach (['list_view', 'create_view', 'detail_view', 'edit_view'] as $view) {
            if (isset($result[$view]) && !$result[$view]['success']) {
                $viewName = ucfirst(str_replace('_', ' ', $view));
                $failures[] = [
                    'resource' => $result['name'],
                    'view' => $viewName,
                    'status' => $result[$view]['status'],
                    'error' => $result[$view]['error'],
                ];
            }
        }
    }

    if (count($failures) > 0) {
        echo "❌ FAILURES LIST (" . count($failures) . " failures):\n\n";
        foreach ($failures as $failure) {
            echo "  • Resource: {$failure['resource']}\n";
            echo "    View: {$failure['view']}\n";
            echo "    Status Code: {$failure['status']}\n";
            if ($failure['error']) {
                echo "    Error: {$failure['error']}\n";
            }
            echo "\n";
        }
    } else {
        echo "✅ NO FAILURES - All tests passed!\n\n";
    }
}

function printCriticalSummary($criticalResults) {
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "  CRITICAL BUG VERIFICATION SUMMARY\n";
    echo "═══════════════════════════════════════════════════════════════\n\n";

    foreach ($criticalResults as $critical) {
        echo "{$critical['bug']} ({$critical['name']}): {$critical['result']}\n";
        echo "  URL: {$critical['url']}\n";
        echo "  Status Code: {$critical['status_code']}\n\n";
    }
}
