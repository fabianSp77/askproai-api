<?php
/**
 * Navigation Fix Verification Script
 * Tests the fix for Issue #578 - Navigation Overlap
 */

echo "=== NAVIGATION FIX VERIFICATION ===\n";
echo "Timestamp: " . date('Y-m-d H:i:s') . "\n\n";

$baseUrl = 'https://api.askproai.de';
$testResults = [];

// Test 1: Check if navigation test page is accessible
echo "Test 1: Navigation Test Page Accessibility\n";
$testPageUrl = $baseUrl . '/navigation-test.html';
$response = @file_get_contents($testPageUrl);

if ($response && strpos($response, 'Navigation Fix for Issue #578') !== false) {
    echo "✅ Navigation test page is accessible\n";
    $testResults['test_page'] = 'pass';
} else {
    echo "❌ Navigation test page is not accessible\n";
    $testResults['test_page'] = 'fail';
}

// Test 2: Check if CSS fix file exists
echo "\nTest 2: CSS Fix File\n";
$cssFixUrl = $baseUrl . '/css/navigation-repair.css';
$cssResponse = @file_get_contents($cssFixUrl);

if ($cssResponse) {
    echo "✅ Navigation repair CSS is accessible\n";
    $testResults['css_file'] = 'pass';
    
    // Check if the CSS contains the Grid fix
    if (strpos($cssResponse, 'grid-template-columns: 16rem 1fr') !== false) {
        echo "✅ CSS contains CSS Grid fix (16rem sidebar)\n";
        $testResults['css_grid_fix'] = 'pass';
    } else {
        echo "❌ CSS Grid fix not found in CSS file\n";
        $testResults['css_grid_fix'] = 'fail';
    }
} else {
    echo "❌ Navigation repair CSS not found\n";
    $testResults['css_file'] = 'fail';
    $testResults['css_grid_fix'] = 'unknown';
}

// Test 3: Check if theme.css has been updated
echo "\nTest 3: Theme CSS File Update\n";
$themeCssPath = __DIR__ . '/resources/css/filament/admin/theme.css';

if (file_exists($themeCssPath)) {
    $themeCss = file_get_contents($themeCssPath);
    
    if (strpos($themeCss, 'grid-template-columns: 16rem 1fr !important') !== false) {
        echo "✅ Theme CSS contains CSS Grid fix\n";
        $testResults['theme_css_fix'] = 'pass';
    } else {
        echo "❌ CSS Grid fix not found in theme CSS\n";
        $testResults['theme_css_fix'] = 'fail';
    }
    
    // Check for specific fix comment
    if (strpos($themeCss, 'Issue #578') !== false) {
        echo "✅ Theme CSS contains Issue #578 fix marker\n";
        $testResults['fix_marker'] = 'pass';
    } else {
        echo "❌ Issue #578 fix marker not found\n";
        $testResults['fix_marker'] = 'fail';
    }
} else {
    echo "❌ Theme CSS file not found\n";
    $testResults['theme_css_fix'] = 'fail';
    $testResults['fix_marker'] = 'fail';
}

// Test 4: Check if compiled CSS includes the fix
echo "\nTest 4: Compiled CSS Check\n";
$publicCssPath = __DIR__ . '/public/build/assets';

if (is_dir($publicCssPath)) {
    $cssFiles = glob($publicCssPath . '/app-*.css');
    
    if (!empty($cssFiles)) {
        $latestCss = end($cssFiles);
        $compiledCss = file_get_contents($latestCss);
        
        if (strpos($compiledCss, 'grid-template-columns:16rem 1fr') !== false || 
            strpos($compiledCss, 'display:grid') !== false) {
            echo "✅ Compiled CSS contains grid layout\n";
            $testResults['compiled_css'] = 'pass';
        } else {
            echo "❌ Grid layout not found in compiled CSS\n";
            $testResults['compiled_css'] = 'fail';
        }
        echo "Checked file: " . basename($latestCss) . "\n";
    } else {
        echo "❌ No compiled CSS files found\n";
        $testResults['compiled_css'] = 'fail';
    }
} else {
    echo "❌ Public build directory not found\n";
    $testResults['compiled_css'] = 'fail';
}

// Test 5: Simple HTTP test to admin (without authentication)
echo "\nTest 5: Admin Panel HTTP Response\n";
$adminUrl = $baseUrl . '/admin';
$context = stream_context_create([
    'http' => [
        'timeout' => 10,
        'user_agent' => 'Navigation Fix Tester'
    ]
]);

$adminResponse = @file_get_contents($adminUrl, false, $context);

if ($adminResponse) {
    echo "✅ Admin panel is responding\n";
    $testResults['admin_response'] = 'pass';
    
    // Check if it contains Filament classes
    if (strpos($adminResponse, 'fi-sidebar') !== false || 
        strpos($adminResponse, 'fi-main') !== false) {
        echo "✅ Response contains Filament layout classes\n";
        $testResults['filament_classes'] = 'pass';
    } else {
        echo "❓ Filament layout classes not found (might be login page)\n";
        $testResults['filament_classes'] = 'unknown';
    }
} else {
    echo "❌ Admin panel not responding\n";
    $testResults['admin_response'] = 'fail';
    $testResults['filament_classes'] = 'fail';
}

// Calculate overall verdict
$passedTests = array_count_values($testResults)['pass'] ?? 0;
$totalTests = count($testResults);
$failedTests = array_count_values($testResults)['fail'] ?? 0;

echo "\n" . str_repeat("=", 50) . "\n";
echo "VERIFICATION SUMMARY\n";
echo str_repeat("=", 50) . "\n";

echo "Tests passed: $passedTests/$totalTests\n";
echo "Tests failed: $failedTests\n";

if ($passedTests >= 4) {
    echo "🎉 VERDICT: FIX IS LIKELY WORKING\n";
    $overallStatus = 'WORKING';
} elseif ($passedTests >= 2) {
    echo "⚠️ VERDICT: FIX IS PARTIALLY WORKING\n";
    $overallStatus = 'PARTIAL';
} else {
    echo "❌ VERDICT: FIX IS NOT WORKING\n";
    $overallStatus = 'BROKEN';
}

// Detailed results
echo "\nDetailed Results:\n";
foreach ($testResults as $test => $result) {
    $icon = $result === 'pass' ? '✅' : ($result === 'fail' ? '❌' : '❓');
    echo "  $icon $test: $result\n";
}

// Save report
$report = [
    'timestamp' => date('c'),
    'overall_status' => $overallStatus,
    'tests_passed' => $passedTests,
    'tests_total' => $totalTests,
    'results' => $testResults,
    'recommendations' => []
];

if ($overallStatus === 'WORKING') {
    $report['recommendations'][] = 'Manual browser testing recommended for final verification';
    $report['recommendations'][] = 'Test navigation clicking functionality';
} elseif ($overallStatus === 'PARTIAL') {
    $report['recommendations'][] = 'Check if CSS compilation needs to be run: npm run build';
    $report['recommendations'][] = 'Verify Filament theme configuration';
    $report['recommendations'][] = 'Manual browser testing essential';
} else {
    $report['recommendations'][] = 'Re-apply navigation fix';
    $report['recommendations'][] = 'Check CSS file permissions';
    $report['recommendations'][] = 'Run npm run build to compile assets';
}

$reportFile = __DIR__ . '/public/screenshots/navigation-verification-' . date('Y-m-d-H-i-s') . '.json';
file_put_contents($reportFile, json_encode($report, JSON_PRETTY_PRINT));

echo "\n📄 Report saved: " . basename($reportFile) . "\n";
echo "\n🔗 Quick Links:\n";
echo "  Navigation Test Page: $baseUrl/navigation-test.html\n";
echo "  Admin Panel: $baseUrl/admin\n";

if ($overallStatus === 'WORKING') {
    echo "\n🎯 RECOMMENDED NEXT STEPS:\n";
    echo "1. Open $baseUrl/admin in browser\n";
    echo "2. Login with admin credentials\n";
    echo "3. Verify sidebar is visible on LEFT (not overlapping)\n";
    echo "4. Test navigation links are clickable\n";
    echo "5. Check mobile responsive behavior\n";
}

echo "\n";
?>
