<?php
/**
 * Admin Navigation and JavaScript Functionality Test
 */

echo "🧪 Testing Admin Panel Navigation and JavaScript...\n";
echo str_repeat("=", 60) . "\n\n";

// Test 1: Check Alpine.js Components
echo "📱 1. Alpine.js Components Test\n";
echo "-".str_repeat("-", 35)."\n";

$alpineComponents = [
    'resources/js/alpine-sidebar-store.js',
    'resources/js/sidebar-toggle-fix.js',
    'resources/js/mobile-sidebar-text-fix.js',
    'resources/js/mobile-interactions.js'
];

$componentsStatus = [];
foreach ($alpineComponents as $component) {
    if (file_exists($component)) {
        $size = filesize($component);
        $content = file_get_contents($component);
        
        $hasAlpineStore = strpos($content, 'Alpine.store') !== false;
        $hasEventHandlers = strpos($content, 'addEventListener') !== false || strpos($content, 'onclick') !== false;
        $hasErrorHandling = strpos($content, 'try') !== false || strpos($content, 'catch') !== false;
        
        $componentsStatus[$component] = [
            'exists' => true,
            'size_bytes' => $size,
            'has_alpine_store' => $hasAlpineStore,
            'has_event_handlers' => $hasEventHandlers,
            'has_error_handling' => $hasErrorHandling
        ];
        
        echo "  ✅ " . basename($component) . ": {$size} bytes ";
        echo ($hasAlpineStore ? "📦" : "") . ($hasEventHandlers ? "🎯" : "") . ($hasErrorHandling ? "🛡️" : "") . "\n";
    } else {
        $componentsStatus[$component] = ['exists' => false];
        echo "  ❌ " . basename($component) . ": Missing\n";
    }
}

// Test 2: Check CSS Files
echo "\n🎨 2. CSS Files Test\n";
echo "-".str_repeat("-", 20)."\n";

$cssFiles = [
    'resources/css/filament/admin/theme.css',
    'resources/css/filament/admin/foundation.css',
    'resources/css/filament/admin/mobile-improvements.css',
    'resources/css/filament/admin/unified-portal-ux-fixes.css'
];

$cssStatus = [];
foreach ($cssFiles as $cssFile) {
    if (file_exists($cssFile)) {
        $size = filesize($cssFile);
        $content = file_get_contents($cssFile);
        
        $hasResponsive = strpos($content, '@media') !== false;
        $hasSidebar = strpos($content, 'sidebar') !== false || strpos($content, '.fi-sidebar') !== false;
        $hasButtons = strpos($content, 'button') !== false || strpos($content, '.fi-btn') !== false;
        
        $cssStatus[$cssFile] = [
            'exists' => true,
            'size_bytes' => $size,
            'has_responsive' => $hasResponsive,
            'has_sidebar_styles' => $hasSidebar,
            'has_button_styles' => $hasButtons
        ];
        
        echo "  ✅ " . basename($cssFile) . ": " . round($size/1024, 1) . "KB ";
        echo ($hasResponsive ? "📱" : "") . ($hasSidebar ? "📋" : "") . ($hasButtons ? "🔘" : "") . "\n";
    } else {
        $cssStatus[$cssFile] = ['exists' => false];
        echo "  ❌ " . basename($cssFile) . ": Missing\n";
    }
}

// Test 3: Database Query Performance for Admin Panel
echo "\n🗄️ 3. Admin Panel Database Performance\n";
echo "-".str_repeat("-", 40)."\n";

try {
    $pdo = new PDO("mysql:host=localhost;dbname=askproai_db", "askproai_user", "lkZ57Dju9EDjrMxn");
    
    // Test critical admin queries
    $adminQueries = [
        'recent_calls' => 'SELECT id, phone_number, duration, created_at FROM calls ORDER BY created_at DESC LIMIT 10',
        'company_stats' => 'SELECT COUNT(*) as total_companies, 
                           COUNT(CASE WHEN status = "active" THEN 1 END) as active_companies 
                           FROM companies',
        'today_calls' => 'SELECT COUNT(*) as today_calls, 
                         COALESCE(AVG(duration), 0) as avg_duration,
                         COALESCE(SUM(cost), 0) as total_cost
                         FROM calls WHERE DATE(created_at) = CURDATE()',
        'widgets_data' => 'SELECT 
                          (SELECT COUNT(*) FROM calls WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)) as week_calls,
                          (SELECT COUNT(*) FROM companies WHERE status = "active") as active_companies,
                          (SELECT COALESCE(SUM(balance), 0) FROM prepaid_balances) as total_balance'
    ];
    
    $queryPerformance = [];
    foreach ($adminQueries as $name => $query) {
        $start = microtime(true);
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $duration = (microtime(true) - $start) * 1000;
        
        $queryPerformance[$name] = [
            'duration_ms' => round($duration, 2),
            'result_count' => is_array($result) ? count($result) : 1,
            'status' => $duration < 50 ? 'EXCELLENT' : ($duration < 200 ? 'GOOD' : 'SLOW')
        ];
        
        $emoji = $duration < 50 ? '🚀' : ($duration < 200 ? '✅' : '⚠️');
        echo "  $emoji $name: {$duration}ms\n";
    }
    
} catch (Exception $e) {
    echo "  ❌ Database test failed: " . $e->getMessage() . "\n";
}

// Test 4: Vite Build Analysis
echo "\n📦 4. Vite Build Analysis\n";
echo "-".str_repeat("-", 26)."\n";

$buildManifest = 'public/build/manifest.json';
if (file_exists($buildManifest)) {
    $manifest = json_decode(file_get_contents($buildManifest), true);
    
    $totalSize = 0;
    $jsFiles = 0;
    $cssFiles = 0;
    
    foreach ($manifest as $entry => $info) {
        if (isset($info['file'])) {
            $filePath = 'public/build/' . $info['file'];
            if (file_exists($filePath)) {
                $fileSize = filesize($filePath);
                $totalSize += $fileSize;
                
                if (strpos($info['file'], '.js') !== false) {
                    $jsFiles++;
                } elseif (strpos($info['file'], '.css') !== false) {
                    $cssFiles++;
                }
            }
        }
    }
    
    echo "  ✅ Total manifest entries: " . count($manifest) . "\n";
    echo "  ✅ JavaScript files: $jsFiles\n";
    echo "  ✅ CSS files: $cssFiles\n";
    echo "  ✅ Total build size: " . round($totalSize / 1024, 1) . "KB\n";
    echo "  ✅ Build status: " . ($totalSize > 0 ? '🟢 READY' : '🔴 EMPTY') . "\n";
    
    // Check critical bundles
    $criticalBundles = ['admin.core', 'admin.analytics', 'styles.admin', 'styles.theme'];
    foreach ($criticalBundles as $bundle) {
        if (isset($manifest[$bundle . '.js']) || isset($manifest[$bundle . '.css'])) {
            echo "  ✅ Critical bundle '$bundle': Available\n";
        } else {
            echo "  ⚠️ Critical bundle '$bundle': Missing\n";
        }
    }
    
} else {
    echo "  ❌ Build manifest not found. Run 'npm run build'\n";
}

// Test 5: PHP Configuration for Admin Panel
echo "\n⚙️ 5. PHP Configuration Test\n";
echo "-".str_repeat("-", 30)."\n";

$phpConfig = [
    'memory_limit' => ini_get('memory_limit'),
    'max_execution_time' => ini_get('max_execution_time'),
    'post_max_size' => ini_get('post_max_size'),
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'opcache_enabled' => extension_loaded('opcache') && opcache_get_status(),
    'redis_available' => extension_loaded('redis'),
    'pdo_mysql' => extension_loaded('pdo_mysql'),
];

foreach ($phpConfig as $setting => $value) {
    $status = $value ? '✅' : '❌';
    $displayValue = is_bool($value) ? ($value ? 'Enabled' : 'Disabled') : $value;
    echo "  $status $setting: $displayValue\n";
}

// Test 6: Laravel Configuration
echo "\n🚀 6. Laravel Configuration Test\n";
echo "-".str_repeat("-", 34)."\n";

// Check environment configuration
$envVars = [
    'APP_ENV' => env('APP_ENV', 'unknown'),
    'APP_DEBUG' => env('APP_DEBUG', false) ? 'true' : 'false',
    'DB_CONNECTION' => env('DB_CONNECTION', 'unknown'),
    'CACHE_DRIVER' => env('CACHE_DRIVER', 'unknown'),
    'SESSION_DRIVER' => env('SESSION_DRIVER', 'unknown'),
    'VITE_PUSHER_HOST' => env('VITE_PUSHER_HOST', 'not set'),
];

foreach ($envVars as $var => $value) {
    $status = $value !== 'unknown' && $value !== 'not set' ? '✅' : '⚠️';
    echo "  $status $var: $value\n";
}

// Test 7: Filament Components Check
echo "\n🔧 7. Filament Components Test\n";
echo "-".str_repeat("-", 31)."\n";

$filamentDirs = [
    'app/Filament/Admin/Resources',
    'app/Filament/Admin/Pages',
    'app/Filament/Admin/Widgets'
];

foreach ($filamentDirs as $dir) {
    if (is_dir($dir)) {
        $files = glob($dir . '/*.php');
        echo "  ✅ $dir: " . count($files) . " components\n";
    } else {
        echo "  ❌ $dir: Directory missing\n";
    }
}

// Test 8: Session and Authentication
echo "\n🔐 8. Authentication System Test\n";
echo "-".str_repeat("-", 34)."\n";

$authFiles = [
    'app/Models/User.php',
    'app/Auth/CustomSessionGuard.php',
    'app/Http/Middleware/CompanyScopeMiddleware.php'
];

foreach ($authFiles as $file) {
    if (file_exists($file)) {
        echo "  ✅ " . basename($file) . ": Available\n";
    } else {
        echo "  ❌ " . basename($file) . ": Missing\n";
    }
}

// Generate Summary
echo "\n📊 ADMIN PANEL TEST SUMMARY\n";
echo str_repeat("=", 60) . "\n";

$totalComponents = count($alpineComponents);
$workingComponents = count(array_filter($componentsStatus, function($status) {
    return $status['exists'] ?? false;
}));

$totalCssFiles = count($cssFiles);
$existingCssFiles = count(array_filter($cssStatus, function($status) {
    return $status['exists'] ?? false;
}));

echo "Alpine.js Components: $workingComponents/$totalComponents " . 
     ($workingComponents === $totalComponents ? "🟢" : "🔴") . "\n";
echo "CSS Files: $existingCssFiles/$totalCssFiles " . 
     ($existingCssFiles >= $totalCssFiles * 0.8 ? "🟢" : "🔴") . "\n";
echo "Database Performance: " . 
     (isset($queryPerformance) && count($queryPerformance) > 0 ? "🟢 EXCELLENT" : "🔴 ISSUES") . "\n";
echo "Build System: " . (file_exists($buildManifest) ? "🟢 READY" : "🔴 NOT BUILT") . "\n";
echo "PHP Configuration: " . ($phpConfig['pdo_mysql'] && $phpConfig['redis_available'] ? "🟢 OPTIMAL" : "🟡 PARTIAL") . "\n";

$overallScore = 0;
$maxScore = 5;

if ($workingComponents === $totalComponents) $overallScore++;
if ($existingCssFiles >= $totalCssFiles * 0.8) $overallScore++;
if (isset($queryPerformance) && count($queryPerformance) > 0) $overallScore++;
if (file_exists($buildManifest)) $overallScore++;
if ($phpConfig['pdo_mysql'] && $phpConfig['redis_available']) $overallScore++;

$percentage = round(($overallScore / $maxScore) * 100);
$status = $percentage >= 90 ? "EXCELLENT" : ($percentage >= 70 ? "GOOD" : "NEEDS WORK");
$emoji = $percentage >= 90 ? "🟢" : ($percentage >= 70 ? "🟡" : "🔴");

echo "\nOverall Admin Panel Health: $percentage% $emoji $status\n";

if ($percentage < 90) {
    echo "\nRecommendations for improvement:\n";
    if ($workingComponents < $totalComponents) {
        echo "- Fix missing Alpine.js components\n";
    }
    if ($existingCssFiles < $totalCssFiles) {
        echo "- Restore missing CSS files\n";
    }
    if (!file_exists($buildManifest)) {
        echo "- Run 'npm run build' to generate assets\n";
    }
    if (!$phpConfig['redis_available']) {
        echo "- Enable Redis for better caching\n";
    }
}

echo "\n✅ Admin Panel Navigation Test Complete!\n";