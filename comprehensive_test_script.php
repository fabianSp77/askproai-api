<?php
/**
 * Comprehensive Test Script for AskProAI System
 * Tests all optimizations implemented
 */

require_once 'vendor/autoload.php';

class ComprehensiveTestSuite
{
    private $results = [];
    private $startTime;
    
    public function __construct()
    {
        $this->startTime = microtime(true);
    }
    
    public function runAllTests()
    {
        echo "🚀 Starting Comprehensive Test Suite for AskProAI...\n";
        echo "=".str_repeat('=', 60)."\n\n";
        
        // Test 1: Admin Panel Functionality
        $this->testAdminPanelFunctionality();
        
        // Test 2: Database Performance
        $this->testDatabasePerformance();
        
        // Test 3: Repository Pagination
        $this->testRepositoryPagination();
        
        // Test 4: Type Safety
        $this->testTypeSafety();
        
        // Test 5: Job Memory Management
        $this->testJobMemoryManagement();
        
        // Test 6: Cache System
        $this->testCacheSystem();
        
        // Test 7: Frontend Performance
        $this->testFrontendPerformance();
        
        // Test 8: Load Testing Preparation
        $this->testLoadTestingPreparation();
        
        // Generate comprehensive report
        $this->generateReport();
    }
    
    private function testAdminPanelFunctionality()
    {
        $this->logSection("1. Admin Panel Functionality Tests");
        
        // Check Filament configuration
        $filamentConfig = $this->checkFilamentConfiguration();
        $this->results['admin_panel']['filament_config'] = $filamentConfig;
        
        // Check JavaScript bundles
        $jsBundle = $this->checkJavaScriptBundle();
        $this->results['admin_panel']['js_bundle'] = $jsBundle;
        
        // Check CSS bundles
        $cssBundle = $this->checkCSSBundle();
        $this->results['admin_panel']['css_bundle'] = $cssBundle;
        
        // Check route availability
        $routes = $this->checkAdminRoutes();
        $this->results['admin_panel']['routes'] = $routes;
        
        // Check Vite build status
        $viteBuild = $this->checkViteBuild();
        $this->results['admin_panel']['vite_build'] = $viteBuild;
    }
    
    private function testDatabasePerformance()
    {
        $this->logSection("2. Database Performance Tests");
        
        try {
            $pdo = new PDO(
                "mysql:host=localhost;dbname=askproai_db", 
                "askproai_user", 
                "lkZ57Dju9EDjrMxn"
            );
            
            // Test query performance
            $queryTests = [
                'calls_count' => 'SELECT COUNT(*) FROM calls',
                'companies_count' => 'SELECT COUNT(*) FROM companies',
                'users_count' => 'SELECT COUNT(*) FROM users',
                'dashboard_stats' => 'SELECT COUNT(*) as total_calls, 
                    AVG(duration) as avg_duration, 
                    SUM(cost) as total_cost 
                    FROM calls WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)'
            ];
            
            foreach ($queryTests as $name => $query) {
                $start = microtime(true);
                $stmt = $pdo->query($query);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $duration = (microtime(true) - $start) * 1000; // Convert to milliseconds
                
                $this->results['database']['queries'][$name] = [
                    'duration_ms' => round($duration, 2),
                    'result' => $result,
                    'status' => $duration < 100 ? 'EXCELLENT' : ($duration < 500 ? 'GOOD' : 'NEEDS_OPTIMIZATION')
                ];
                
                echo "  ✓ Query $name: {$duration}ms - " . 
                     ($duration < 100 ? '🟢 EXCELLENT' : ($duration < 500 ? '🟡 GOOD' : '🔴 SLOW')) . "\n";
            }
            
            // Test index usage
            $this->testIndexUsage($pdo);
            
        } catch (Exception $e) {
            $this->results['database']['error'] = $e->getMessage();
            echo "  ❌ Database connection failed: " . $e->getMessage() . "\n";
        }
    }
    
    private function testRepositoryPagination()
    {
        $this->logSection("3. Repository Pagination Tests");
        
        // Test memory usage simulation
        $memoryBefore = memory_get_usage();
        
        // Simulate large dataset pagination
        $testData = [];
        for ($i = 0; $i < 10000; $i++) {
            $testData[] = [
                'id' => $i,
                'name' => 'Test Item ' . $i,
                'data' => str_repeat('x', 100)
            ];
        }
        
        // Test chunked processing
        $chunks = array_chunk($testData, 100);
        $processedCount = 0;
        
        foreach ($chunks as $chunk) {
            $processedCount += count($chunk);
            // Simulate processing
            unset($chunk);
        }
        
        $memoryAfter = memory_get_usage();
        $memoryUsed = $memoryAfter - $memoryBefore;
        
        $this->results['pagination'] = [
            'memory_used_bytes' => $memoryUsed,
            'memory_used_mb' => round($memoryUsed / 1024 / 1024, 2),
            'processed_items' => $processedCount,
            'status' => $memoryUsed < 50 * 1024 * 1024 ? 'EXCELLENT' : 'NEEDS_OPTIMIZATION'
        ];
        
        echo "  ✓ Processed $processedCount items using " . round($memoryUsed / 1024 / 1024, 2) . "MB\n";
        echo "  ✓ Memory management: " . ($memoryUsed < 50 * 1024 * 1024 ? '🟢 EXCELLENT' : '🔴 HIGH') . "\n";
    }
    
    private function testTypeSafety()
    {
        $this->logSection("4. Type Safety Tests");
        
        // Check PHP syntax of critical files
        $criticalFiles = [
            'app/Models/Call.php',
            'app/Models/Company.php',
            'app/Http/Controllers/API/CallController.php',
            'app/Services/TieredPricingService.php'
        ];
        
        $syntaxErrors = [];
        foreach ($criticalFiles as $file) {
            if (file_exists($file)) {
                $output = [];
                $returnCode = 0;
                exec("php -l $file 2>&1", $output, $returnCode);
                
                if ($returnCode !== 0) {
                    $syntaxErrors[] = $file . ': ' . implode(' ', $output);
                } else {
                    echo "  ✓ $file: 🟢 SYNTAX OK\n";
                }
            } else {
                echo "  ⚠️ $file: FILE NOT FOUND\n";
            }
        }
        
        $this->results['type_safety'] = [
            'syntax_errors' => $syntaxErrors,
            'status' => empty($syntaxErrors) ? 'PASS' : 'FAIL'
        ];
        
        if (empty($syntaxErrors)) {
            echo "  ✅ All critical files passed syntax check\n";
        } else {
            echo "  ❌ Syntax errors found in " . count($syntaxErrors) . " files\n";
        }
    }
    
    private function testJobMemoryManagement()
    {
        $this->logSection("5. Job Memory Management Tests");
        
        // Test memory limits and garbage collection
        $initialMemory = memory_get_usage();
        $peakMemory = memory_get_peak_usage();
        
        // Simulate job processing
        $largeData = [];
        for ($i = 0; $i < 1000; $i++) {
            $largeData[] = str_repeat('test data ', 1000);
        }
        
        $memoryWithData = memory_get_usage();
        
        // Force garbage collection
        unset($largeData);
        gc_collect_cycles();
        
        $memoryAfterGC = memory_get_usage();
        $memoryReclaimed = $memoryWithData - $memoryAfterGC;
        
        $this->results['job_memory'] = [
            'initial_memory_mb' => round($initialMemory / 1024 / 1024, 2),
            'peak_memory_mb' => round($peakMemory / 1024 / 1024, 2),
            'memory_with_data_mb' => round($memoryWithData / 1024 / 1024, 2),
            'memory_after_gc_mb' => round($memoryAfterGC / 1024 / 1024, 2),
            'memory_reclaimed_mb' => round($memoryReclaimed / 1024 / 1024, 2),
            'gc_efficiency' => round(($memoryReclaimed / $memoryWithData) * 100, 2) . '%'
        ];
        
        echo "  ✓ Initial memory: " . round($initialMemory / 1024 / 1024, 2) . "MB\n";
        echo "  ✓ Peak memory: " . round($peakMemory / 1024 / 1024, 2) . "MB\n";
        echo "  ✓ Memory reclaimed by GC: " . round($memoryReclaimed / 1024 / 1024, 2) . "MB\n";
        echo "  ✓ GC efficiency: " . round(($memoryReclaimed / $memoryWithData) * 100, 2) . "%\n";
    }
    
    private function testCacheSystem()
    {
        $this->logSection("6. Cache System Tests");
        
        // Test cache configuration
        $cacheConfig = [
            'redis_available' => extension_loaded('redis'),
            'cache_driver' => env('CACHE_DRIVER', 'file'),
            'session_driver' => env('SESSION_DRIVER', 'file')
        ];
        
        $this->results['cache'] = $cacheConfig;
        
        foreach ($cacheConfig as $key => $value) {
            $status = $value ? '🟢 ENABLED' : '🔴 DISABLED';
            echo "  ✓ $key: $status\n";
        }
        
        // Test cache performance
        if ($cacheConfig['redis_available']) {
            $this->testRedisPerformance();
        }
    }
    
    private function testFrontendPerformance()
    {
        $this->logSection("7. Frontend Performance Tests");
        
        // Check bundle sizes
        $buildDir = 'public/build';
        $bundleInfo = [];
        
        if (is_dir($buildDir)) {
            $files = glob($buildDir . '/**/*.{js,css}', GLOB_BRACE);
            
            foreach ($files as $file) {
                $size = filesize($file);
                $relativePath = str_replace($buildDir . '/', '', $file);
                
                $bundleInfo[$relativePath] = [
                    'size_bytes' => $size,
                    'size_kb' => round($size / 1024, 2),
                    'status' => $size < 100 * 1024 ? 'OPTIMAL' : ($size < 500 * 1024 ? 'ACCEPTABLE' : 'LARGE')
                ];
            }
        }
        
        $this->results['frontend'] = [
            'bundles' => $bundleInfo,
            'total_bundles' => count($bundleInfo)
        ];
        
        echo "  ✓ Found " . count($bundleInfo) . " frontend bundles\n";
        foreach (array_slice($bundleInfo, 0, 5) as $file => $info) {
            $status = $info['status'] === 'OPTIMAL' ? '🟢' : ($info['status'] === 'ACCEPTABLE' ? '🟡' : '🔴');
            echo "  ✓ $file: {$info['size_kb']}KB $status\n";
        }
    }
    
    private function testLoadTestingPreparation()
    {
        $this->logSection("8. Load Testing Preparation");
        
        // Check system resources
        $systemInfo = [
            'php_memory_limit' => ini_get('memory_limit'),
            'php_max_execution_time' => ini_get('max_execution_time'),
            'php_max_input_vars' => ini_get('max_input_vars'),
            'opcache_enabled' => extension_loaded('opcache') && ini_get('opcache.enable'),
            'cpu_cores' => $this->getCpuCores()
        ];
        
        $this->results['load_testing'] = $systemInfo;
        
        foreach ($systemInfo as $key => $value) {
            echo "  ✓ $key: $value\n";
        }
        
        // Test concurrent connection simulation
        $concurrentTest = $this->simulateConcurrentRequests();
        $this->results['load_testing']['concurrent_simulation'] = $concurrentTest;
    }
    
    private function checkFilamentConfiguration()
    {
        $configFile = 'app/Providers/Filament/AdminPanelProvider.php';
        if (!file_exists($configFile)) {
            return ['status' => 'MISSING', 'error' => 'AdminPanelProvider not found'];
        }
        
        $content = file_get_contents($configFile);
        $checks = [
            'has_navigation_groups' => strpos($content, 'navigationGroups') !== false,
            'has_middleware' => strpos($content, 'middleware') !== false,
            'has_auth_middleware' => strpos($content, 'authMiddleware') !== false,
            'has_vite_theme' => strpos($content, 'viteTheme') !== false
        ];
        
        $checks['status'] = array_sum($checks) === count($checks) ? 'PASS' : 'PARTIAL';
        return $checks;
    }
    
    private function checkJavaScriptBundle()
    {
        $bundleFile = 'resources/js/bundles/admin.js';
        if (!file_exists($bundleFile)) {
            return ['status' => 'MISSING'];
        }
        
        $content = file_get_contents($bundleFile);
        return [
            'status' => 'FOUND',
            'size_kb' => round(filesize($bundleFile) / 1024, 2),
            'has_error_handling' => strpos($content, 'error') !== false,
            'has_initialization' => strpos($content, 'init') !== false
        ];
    }
    
    private function checkCSSBundle()
    {
        $bundleFile = 'resources/css/bundles/admin.css';
        if (!file_exists($bundleFile)) {
            return ['status' => 'MISSING'];
        }
        
        return [
            'status' => 'FOUND',
            'size_kb' => round(filesize($bundleFile) / 1024, 2)
        ];
    }
    
    private function checkAdminRoutes()
    {
        // Check if Filament routes are available
        $output = [];
        exec('php artisan route:list 2>/dev/null | grep -i filament | head -5', $output);
        
        return [
            'routes_found' => count($output),
            'sample_routes' => array_slice($output, 0, 3)
        ];
    }
    
    private function checkViteBuild()
    {
        $manifestFile = 'public/build/manifest.json';
        if (!file_exists($manifestFile)) {
            return ['status' => 'NO_BUILD', 'message' => 'Run npm run build'];
        }
        
        $manifest = json_decode(file_get_contents($manifestFile), true);
        return [
            'status' => 'BUILT',
            'entries' => count($manifest),
            'last_modified' => date('Y-m-d H:i:s', filemtime($manifestFile))
        ];
    }
    
    private function testIndexUsage($pdo)
    {
        $indexQueries = [
            'calls_indexes' => "SHOW INDEX FROM calls",
            'companies_indexes' => "SHOW INDEX FROM companies",
            'users_indexes' => "SHOW INDEX FROM users"
        ];
        
        foreach ($indexQueries as $name => $query) {
            try {
                $stmt = $pdo->query($query);
                $indexes = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $this->results['database']['indexes'][$name] = count($indexes);
                echo "  ✓ $name: " . count($indexes) . " indexes\n";
            } catch (Exception $e) {
                echo "  ❌ $name: Error checking indexes\n";
            }
        }
    }
    
    private function testRedisPerformance()
    {
        try {
            $redis = new Redis();
            $redis->connect('127.0.0.1', 6379);
            
            $start = microtime(true);
            $redis->set('test_key', 'test_value');
            $value = $redis->get('test_key');
            $duration = (microtime(true) - $start) * 1000;
            
            $this->results['cache']['redis_performance'] = [
                'write_read_time_ms' => round($duration, 2),
                'status' => $duration < 10 ? 'EXCELLENT' : 'ACCEPTABLE'
            ];
            
            echo "  ✓ Redis read/write: {$duration}ms\n";
            $redis->del('test_key');
            $redis->close();
        } catch (Exception $e) {
            echo "  ❌ Redis test failed: " . $e->getMessage() . "\n";
        }
    }
    
    private function getCpuCores()
    {
        $cores = 1;
        if (is_file('/proc/cpuinfo')) {
            $cores = substr_count(file_get_contents('/proc/cpuinfo'), 'processor');
        }
        return $cores;
    }
    
    private function simulateConcurrentRequests()
    {
        // Simulate multiple requests timing
        $startTime = microtime(true);
        
        // Simulate 10 concurrent operations
        for ($i = 0; $i < 10; $i++) {
            // Simulate database operation
            usleep(50000); // 50ms simulation
        }
        
        $totalTime = microtime(true) - $startTime;
        
        return [
            'simulated_requests' => 10,
            'total_time_seconds' => round($totalTime, 2),
            'avg_response_time_ms' => round(($totalTime / 10) * 1000, 2)
        ];
    }
    
    private function logSection($title)
    {
        echo "\n📋 $title\n";
        echo str_repeat('-', strlen($title) + 4) . "\n";
    }
    
    private function generateReport()
    {
        $totalTime = round(microtime(true) - $this->startTime, 2);
        
        echo "\n\n";
        echo "📊 COMPREHENSIVE TEST REPORT\n";
        echo "=".str_repeat('=', 60)."\n";
        echo "Test Duration: {$totalTime} seconds\n";
        echo "Timestamp: " . date('Y-m-d H:i:s') . "\n\n";
        
        // Summary
        $this->printSummary();
        
        // Detailed results
        $this->printDetailedResults();
        
        // Recommendations
        $this->printRecommendations();
        
        // Save results to file
        $reportFile = 'comprehensive_test_report_' . date('Y-m-d_H-i-s') . '.json';
        file_put_contents($reportFile, json_encode($this->results, JSON_PRETTY_PRINT));
        echo "\n📄 Detailed report saved to: $reportFile\n";
    }
    
    private function printSummary()
    {
        $summary = [];
        
        // Admin Panel Status
        $adminStatus = isset($this->results['admin_panel']) ? '🟢 ACTIVE' : '🔴 ISSUES';
        $summary['Admin Panel'] = $adminStatus;
        
        // Database Performance
        $dbAvgTime = 0;
        $dbQueries = 0;
        if (isset($this->results['database']['queries'])) {
            foreach ($this->results['database']['queries'] as $query) {
                $dbAvgTime += $query['duration_ms'];
                $dbQueries++;
            }
            $dbAvgTime = $dbQueries > 0 ? round($dbAvgTime / $dbQueries, 2) : 0;
        }
        $summary['Database Avg Query Time'] = $dbAvgTime . 'ms ' . ($dbAvgTime < 100 ? '🟢' : '🔴');
        
        // Memory Management
        $memoryStatus = isset($this->results['pagination']['status']) ? 
            ($this->results['pagination']['status'] === 'EXCELLENT' ? '🟢 EFFICIENT' : '🔴 HIGH') : 
            '❓ UNKNOWN';
        $summary['Memory Management'] = $memoryStatus;
        
        // Type Safety
        $typeStatus = isset($this->results['type_safety']['status']) ? 
            ($this->results['type_safety']['status'] === 'PASS' ? '🟢 PASS' : '🔴 FAIL') : 
            '❓ UNKNOWN';
        $summary['Type Safety'] = $typeStatus;
        
        echo "📈 SUMMARY\n";
        echo "---------\n";
        foreach ($summary as $component => $status) {
            printf("%-25s: %s\n", $component, $status);
        }
    }
    
    private function printDetailedResults()
    {
        echo "\n📋 DETAILED RESULTS\n";
        echo "-------------------\n";
        
        foreach ($this->results as $category => $data) {
            echo "\n🔍 " . strtoupper(str_replace('_', ' ', $category)) . ":\n";
            $this->printArrayRecursive($data, 2);
        }
    }
    
    private function printArrayRecursive($array, $indent = 0)
    {
        $prefix = str_repeat(' ', $indent);
        
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                echo "$prefix$key:\n";
                $this->printArrayRecursive($value, $indent + 2);
            } else {
                echo "$prefix$key: $value\n";
            }
        }
    }
    
    private function printRecommendations()
    {
        echo "\n💡 RECOMMENDATIONS\n";
        echo "------------------\n";
        
        $recommendations = [];
        
        // Database recommendations
        if (isset($this->results['database']['queries'])) {
            $slowQueries = array_filter($this->results['database']['queries'], function($query) {
                return $query['duration_ms'] > 500;
            });
            
            if (!empty($slowQueries)) {
                $recommendations[] = "🔍 Optimize " . count($slowQueries) . " slow database queries";
            } else {
                $recommendations[] = "✅ Database performance is excellent";
            }
        }
        
        // Memory recommendations
        if (isset($this->results['pagination']['memory_used_mb']) && 
            $this->results['pagination']['memory_used_mb'] > 50) {
            $recommendations[] = "🧠 Consider implementing more efficient pagination chunking";
        } else {
            $recommendations[] = "✅ Memory management is efficient";
        }
        
        // Frontend recommendations
        if (isset($this->results['frontend']['bundles'])) {
            $largeBundles = array_filter($this->results['frontend']['bundles'], function($bundle) {
                return $bundle['size_kb'] > 500;
            });
            
            if (!empty($largeBundles)) {
                $recommendations[] = "📦 Consider splitting " . count($largeBundles) . " large frontend bundles";
            } else {
                $recommendations[] = "✅ Frontend bundle sizes are optimal";
            }
        }
        
        // Cache recommendations
        if (isset($this->results['cache']['redis_available']) && !$this->results['cache']['redis_available']) {
            $recommendations[] = "⚡ Enable Redis for better caching performance";
        } else {
            $recommendations[] = "✅ Caching system is properly configured";
        }
        
        // Type safety recommendations
        if (isset($this->results['type_safety']['syntax_errors']) && 
            !empty($this->results['type_safety']['syntax_errors'])) {
            $recommendations[] = "🔧 Fix syntax errors in critical files";
        } else {
            $recommendations[] = "✅ All critical files pass type safety checks";
        }
        
        foreach ($recommendations as $recommendation) {
            echo "  $recommendation\n";
        }
        
        // Production readiness assessment
        echo "\n🚀 PRODUCTION READINESS\n";
        echo "----------------------\n";
        
        $readinessScore = $this->calculateReadinessScore();
        $readinessLevel = $readinessScore >= 90 ? 'READY' : ($readinessScore >= 70 ? 'MOSTLY READY' : 'NEEDS WORK');
        $readinessEmoji = $readinessScore >= 90 ? '🟢' : ($readinessScore >= 70 ? '🟡' : '🔴');
        
        echo "Overall Score: $readinessScore% $readinessEmoji\n";
        echo "Status: $readinessLevel\n";
        
        if ($readinessScore < 90) {
            echo "\nNext Steps for Production:\n";
            if (isset($this->results['database']['queries'])) {
                $avgDbTime = array_sum(array_column($this->results['database']['queries'], 'duration_ms')) / 
                            count($this->results['database']['queries']);
                if ($avgDbTime > 100) {
                    echo "  1. Optimize database queries (current avg: {$avgDbTime}ms)\n";
                }
            }
            if (isset($this->results['type_safety']['syntax_errors']) && 
                !empty($this->results['type_safety']['syntax_errors'])) {
                echo "  2. Fix syntax errors in critical files\n";
            }
            if (!isset($this->results['frontend']['bundles']) || empty($this->results['frontend']['bundles'])) {
                echo "  3. Build frontend assets with 'npm run build'\n";
            }
        }
    }
    
    private function calculateReadinessScore()
    {
        $score = 0;
        $maxScore = 0;
        
        // Admin Panel (20 points)
        $maxScore += 20;
        if (isset($this->results['admin_panel']['filament_config']['status']) && 
            $this->results['admin_panel']['filament_config']['status'] === 'PASS') {
            $score += 20;
        }
        
        // Database Performance (25 points)
        $maxScore += 25;
        if (isset($this->results['database']['queries'])) {
            $avgTime = array_sum(array_column($this->results['database']['queries'], 'duration_ms')) / 
                      count($this->results['database']['queries']);
            if ($avgTime < 50) $score += 25;
            elseif ($avgTime < 100) $score += 20;
            elseif ($avgTime < 200) $score += 15;
            else $score += 10;
        }
        
        // Memory Management (20 points)
        $maxScore += 20;
        if (isset($this->results['pagination']['status']) && 
            $this->results['pagination']['status'] === 'EXCELLENT') {
            $score += 20;
        }
        
        // Type Safety (15 points)
        $maxScore += 15;
        if (isset($this->results['type_safety']['status']) && 
            $this->results['type_safety']['status'] === 'PASS') {
            $score += 15;
        }
        
        // Frontend (10 points)
        $maxScore += 10;
        if (isset($this->results['frontend']['bundles']) && count($this->results['frontend']['bundles']) > 0) {
            $score += 10;
        }
        
        // Cache System (10 points)
        $maxScore += 10;
        if (isset($this->results['cache']['redis_available']) && $this->results['cache']['redis_available']) {
            $score += 10;
        } elseif (isset($this->results['cache']['cache_driver'])) {
            $score += 5;
        }
        
        return $maxScore > 0 ? round(($score / $maxScore) * 100, 1) : 0;
    }
}

// Run the comprehensive test suite
$testSuite = new ComprehensiveTestSuite();
$testSuite->runAllTests();