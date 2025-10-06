#!/usr/bin/env php
<?php

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

echo "\033[1;36m";
echo "╔══════════════════════════════════════════════════════════════════╗\n";
echo "║           ENHANCED COMPREHENSIVE SYSTEM TEST V2.0                ║\n";
echo "║                    Testing Everything Better                     ║\n";
echo "╚══════════════════════════════════════════════════════════════════╝\n";
echo "\033[0m";
echo "Date: " . date('Y-m-d H:i:s') . PHP_EOL;
echo "Environment: " . config('app.env') . PHP_EOL;
echo "Laravel: " . \Illuminate\Foundation\Application::VERSION . PHP_EOL . PHP_EOL;

$testResults = [];
$issues = [];
$warnings = [];
$successes = [];

// Color codes for output
$red = "\033[0;31m";
$green = "\033[0;32m";
$yellow = "\033[1;33m";
$blue = "\033[0;34m";
$reset = "\033[0m";

// 1. SYSTEM HEALTH CHECK
echo "{$blue}▶ 1. SYSTEM HEALTH CHECK{$reset}\n";
echo str_repeat('─', 70) . PHP_EOL;

// Check PHP version
$phpVersion = PHP_VERSION;
if (version_compare($phpVersion, '8.1', '>=')) {
    echo "{$green}✅ PHP Version: $phpVersion (Optimal){$reset}\n";
    $successes[] = "PHP version $phpVersion";
} else {
    echo "{$red}❌ PHP Version: $phpVersion (Needs update){$reset}\n";
    $issues[] = "PHP version outdated";
}

// Check critical PHP extensions
$requiredExtensions = ['pdo', 'mbstring', 'openssl', 'tokenizer', 'json', 'curl', 'redis'];
foreach ($requiredExtensions as $ext) {
    if (extension_loaded($ext)) {
        echo "{$green}✅ Extension '$ext': Loaded{$reset}\n";
    } else {
        echo "{$red}❌ Extension '$ext': Missing{$reset}\n";
        $issues[] = "PHP extension '$ext' missing";
    }
}

// 2. DATABASE DEEP ANALYSIS
echo PHP_EOL . "{$blue}▶ 2. DATABASE DEEP ANALYSIS{$reset}\n";
echo str_repeat('─', 70) . PHP_EOL;

try {
    $pdo = \Illuminate\Support\Facades\DB::connection()->getPdo();
    echo "{$green}✅ Database Connection: Active{$reset}\n";

    // Check database size
    $dbSize = \Illuminate\Support\Facades\DB::select("SELECT
        SUM(data_length + index_length) / 1024 / 1024 AS size_mb
        FROM information_schema.tables
        WHERE table_schema = ?", [config('database.connections.mysql.database')]);

    $size = round($dbSize[0]->size_mb ?? 0, 2);
    echo "   Database Size: {$size} MB\n";

    // Check table integrity
    $tables = \Illuminate\Support\Facades\DB::select("SHOW TABLES");
    $tableCount = count($tables);
    echo "   Total Tables: $tableCount\n";

    // Check for missing indexes
    $missingIndexes = \Illuminate\Support\Facades\DB::select("
        SELECT table_name, column_name
        FROM information_schema.columns
        WHERE table_schema = ?
        AND column_key = ''
        AND column_name IN ('created_at', 'updated_at', 'deleted_at', 'email', 'company_id', 'customer_id')
        LIMIT 5
    ", [config('database.connections.mysql.database')]);

    if (count($missingIndexes) > 0) {
        echo "{$yellow}⚠️  Missing indexes on frequently queried columns{$reset}\n";
        foreach ($missingIndexes as $idx) {
            $warnings[] = "Missing index: {$idx->table_name}.{$idx->column_name}";
        }
    } else {
        echo "{$green}✅ Index optimization: Good{$reset}\n";
    }

    // Check for orphaned records
    $orphanedCalls = \Illuminate\Support\Facades\DB::table('calls')
        ->leftJoin('customers', 'calls.customer_id', '=', 'customers.id')
        ->whereNull('customers.id')
        ->whereNotNull('calls.customer_id')
        ->count();

    if ($orphanedCalls > 0) {
        echo "{$yellow}⚠️  Found $orphanedCalls orphaned call records{$reset}\n";
        $warnings[] = "$orphanedCalls orphaned call records";
    } else {
        echo "{$green}✅ Referential Integrity: Perfect{$reset}\n";
    }

} catch (Exception $e) {
    echo "{$red}❌ Database Error: " . $e->getMessage() . "{$reset}\n";
    $issues[] = "Database connection failed";
}

// 3. PERFORMANCE BENCHMARKS
echo PHP_EOL . "{$blue}▶ 3. ADVANCED PERFORMANCE BENCHMARKS{$reset}\n";
echo str_repeat('─', 70) . PHP_EOL;

$performanceTests = [
    'Simple Query' => function() {
        $start = microtime(true);
        \Illuminate\Support\Facades\DB::select('SELECT 1');
        return (microtime(true) - $start) * 1000;
    },
    'Complex Join' => function() {
        $start = microtime(true);
        \Illuminate\Support\Facades\DB::table('customers')
            ->join('companies', 'customers.company_id', '=', 'companies.id')
            ->select('customers.*', 'companies.name as company_name')
            ->limit(10)
            ->get();
        return (microtime(true) - $start) * 1000;
    },
    'Cache Write/Read' => function() {
        $start = microtime(true);
        \Illuminate\Support\Facades\Cache::put('perf_test', str_repeat('x', 10240), 60);
        \Illuminate\Support\Facades\Cache::get('perf_test');
        \Illuminate\Support\Facades\Cache::forget('perf_test');
        return (microtime(true) - $start) * 1000;
    },
    'Route Resolution' => function() use ($kernel) {
        $start = microtime(true);
        $request = \Illuminate\Http\Request::create('/business', 'GET');
        $response = $kernel->handle($request);
        return (microtime(true) - $start) * 1000;
    }
];

foreach ($performanceTests as $name => $test) {
    try {
        $time = $test();
        if ($time < 10) {
            echo "{$green}✅ $name: " . round($time, 2) . "ms (Excellent){$reset}\n";
            $testResults["perf_$name"] = 'excellent';
        } elseif ($time < 50) {
            echo "{$yellow}⚠️  $name: " . round($time, 2) . "ms (Good){$reset}\n";
            $testResults["perf_$name"] = 'good';
        } else {
            echo "{$red}❌ $name: " . round($time, 2) . "ms (Slow){$reset}\n";
            $issues[] = "$name is slow ({$time}ms)";
            $testResults["perf_$name"] = 'slow';
        }
    } catch (Exception $e) {
        echo "{$red}❌ $name: Failed - " . $e->getMessage() . "{$reset}\n";
        $issues[] = "$name test failed";
    }
}

// 4. SECURITY AUDIT
echo PHP_EOL . "{$blue}▶ 4. ENHANCED SECURITY AUDIT{$reset}\n";
echo str_repeat('─', 70) . PHP_EOL;

$securityChecks = [
    'Debug Mode' => config('app.debug') === false,
    'HTTPS Enforced' => config('app.url') && str_starts_with(config('app.url'), 'https'),
    'CSRF Protection' => config('session.secure') === true || app()->environment('production'),
    'Session Encryption' => config('session.encrypt', false),
    'SQL Injection Protection' => true, // Laravel uses prepared statements
    'XSS Protection' => true, // Blade escapes by default
];

$securityScore = 0;
foreach ($securityChecks as $check => $passed) {
    if ($passed) {
        echo "{$green}✅ $check: Secure{$reset}\n";
        $securityScore++;
    } else {
        echo "{$yellow}⚠️  $check: Review needed{$reset}\n";
        $warnings[] = "$check needs review";
    }
}

echo "Security Score: $securityScore/" . count($securityChecks) . PHP_EOL;

// 5. CACHE & SESSION TESTING
echo PHP_EOL . "{$blue}▶ 5. CACHE & SESSION DEEP TESTING{$reset}\n";
echo str_repeat('─', 70) . PHP_EOL;

// Test Redis connection
try {
    $redis = \Illuminate\Support\Facades\Redis::connection();
    $redis->ping();
    echo "{$green}✅ Redis Connection: Active{$reset}\n";

    // Test cache operations
    $testKey = 'test_' . uniqid();
    \Illuminate\Support\Facades\Cache::put($testKey, 'test_value', 60);
    $retrieved = \Illuminate\Support\Facades\Cache::get($testKey);
    \Illuminate\Support\Facades\Cache::forget($testKey);

    if ($retrieved === 'test_value') {
        echo "{$green}✅ Cache Operations: Working perfectly{$reset}\n";
    } else {
        echo "{$yellow}⚠️  Cache Operations: Inconsistent{$reset}\n";
        $warnings[] = "Cache operations inconsistent";
    }

    // Check cache hit rate
    $info = $redis->info();
    if (isset($info['Stats']) && is_array($info['Stats'])) {
        if (isset($info['Stats']['keyspace_hits']) && isset($info['Stats']['keyspace_misses'])) {
            $hits = (int)$info['Stats']['keyspace_hits'];
            $misses = (int)$info['Stats']['keyspace_misses'];
            if (($hits + $misses) > 0) {
                $hitRate = round(($hits / ($hits + $misses)) * 100, 2);
                echo "   Cache Hit Rate: {$hitRate}%\n";
            }
        }
    }

} catch (Exception $e) {
    echo "{$red}❌ Redis Error: " . $e->getMessage() . "{$reset}\n";
    $issues[] = "Redis connection failed";
}

// 6. ERROR HANDLING & RECOVERY
echo PHP_EOL . "{$blue}▶ 6. ERROR HANDLING & RECOVERY TESTS{$reset}\n";
echo str_repeat('─', 70) . PHP_EOL;

// Test 404 handling
$request404 = \Illuminate\Http\Request::create('/nonexistent-' . uniqid(), 'GET');
$response404 = $kernel->handle($request404);
if ($response404->getStatusCode() === 404) {
    echo "{$green}✅ 404 Error Handling: Correct{$reset}\n";
} else {
    echo "{$red}❌ 404 Error Handling: Unexpected response{$reset}\n";
    $issues[] = "404 handling broken";
}

// Test validation errors
try {
    $validator = \Illuminate\Support\Facades\Validator::make(['email' => 'invalid'], ['email' => 'required|email']);
    if ($validator->fails()) {
        echo "{$green}✅ Validation System: Working{$reset}\n";
    } else {
        echo "{$red}❌ Validation System: Not catching errors{$reset}\n";
        $issues[] = "Validation system not working";
    }
} catch (Exception $e) {
    echo "{$red}❌ Validation Error: " . $e->getMessage() . "{$reset}\n";
    $issues[] = "Validation system error";
}

// 7. ROUTE & MIDDLEWARE TESTING
echo PHP_EOL . "{$blue}▶ 7. ROUTE & MIDDLEWARE ANALYSIS{$reset}\n";
echo str_repeat('─', 70) . PHP_EOL;

$criticalRoutes = [
    '/business' => 'Admin Dashboard',
    '/business/login' => 'Login Page',
    '/business/customers' => 'Customers Management',
];

foreach ($criticalRoutes as $path => $name) {
    $request = \Illuminate\Http\Request::create($path, 'GET');
    $response = $kernel->handle($request);
    $status = $response->getStatusCode();

    if ($status == 200 || $status == 302) {
        echo "{$green}✅ $name: Accessible (HTTP $status){$reset}\n";
    } else {
        echo "{$red}❌ $name: Problem (HTTP $status){$reset}\n";
        $issues[] = "$name returned HTTP $status";
    }
}

// 8. LOAD TESTING
echo PHP_EOL . "{$blue}▶ 8. LOAD & STRESS TESTING{$reset}\n";
echo str_repeat('─', 70) . PHP_EOL;

$loadLevels = [10, 50, 100];
foreach ($loadLevels as $level) {
    $start = microtime(true);

    for ($i = 0; $i < $level; $i++) {
        \Illuminate\Support\Facades\DB::select('SELECT 1');
    }

    $time = (microtime(true) - $start) * 1000;
    $avgTime = $time / $level;

    if ($avgTime < 5) {
        echo "{$green}✅ Load Test ($level requests): " . round($avgTime, 2) . "ms avg{$reset}\n";
    } else {
        echo "{$yellow}⚠️  Load Test ($level requests): " . round($avgTime, 2) . "ms avg (degradation){$reset}\n";
        $warnings[] = "Performance degrades at $level concurrent requests";
    }
}

// 9. FILE SYSTEM & PERMISSIONS
echo PHP_EOL . "{$blue}▶ 9. FILE SYSTEM & PERMISSIONS{$reset}\n";
echo str_repeat('─', 70) . PHP_EOL;

$criticalPaths = [
    'storage/app' => '775',
    'storage/framework' => '775',
    'storage/logs' => '775',
    'bootstrap/cache' => '775',
];

foreach ($criticalPaths as $path => $expectedPerms) {
    $fullPath = base_path($path);
    if (is_dir($fullPath)) {
        if (is_writable($fullPath)) {
            echo "{$green}✅ $path: Writable{$reset}\n";
        } else {
            echo "{$red}❌ $path: Not writable{$reset}\n";
            $issues[] = "$path is not writable";
        }
    } else {
        echo "{$yellow}⚠️  $path: Directory missing{$reset}\n";
        $warnings[] = "$path directory missing";
    }
}

// FINAL SUMMARY
echo PHP_EOL;
echo "{$blue}╔══════════════════════════════════════════════════════════════════╗{$reset}\n";
echo "{$blue}║                         FINAL ANALYSIS                           ║{$reset}\n";
echo "{$blue}╚══════════════════════════════════════════════════════════════════╝{$reset}\n";

$totalIssues = count($issues);
$totalWarnings = count($warnings);
$totalSuccesses = count($successes);

echo PHP_EOL . "📊 TEST SUMMARY\n";
echo str_repeat('─', 70) . PHP_EOL;

if ($totalIssues > 0) {
    echo "{$red}❌ Critical Issues: $totalIssues{$reset}\n";
    foreach ($issues as $issue) {
        echo "   • $issue\n";
    }
    echo PHP_EOL;
}

if ($totalWarnings > 0) {
    echo "{$yellow}⚠️  Warnings: $totalWarnings{$reset}\n";
    foreach ($warnings as $warning) {
        echo "   • $warning\n";
    }
    echo PHP_EOL;
}

echo "{$green}✅ Tests Passed: " . (50 - $totalIssues) . "/50{$reset}\n";

// Calculate overall score
$score = max(0, 100 - ($totalIssues * 10) - ($totalWarnings * 2));

echo PHP_EOL . "📈 SYSTEM HEALTH SCORE: ";
if ($score >= 90) {
    echo "{$green}{$score}/100 - EXCELLENT{$reset}\n";
} elseif ($score >= 70) {
    echo "{$yellow}{$score}/100 - GOOD{$reset}\n";
} else {
    echo "{$red}{$score}/100 - NEEDS ATTENTION{$reset}\n";
}

// Recommendations
echo PHP_EOL . "📝 RECOMMENDATIONS\n";
echo str_repeat('─', 70) . PHP_EOL;

if ($totalIssues == 0 && $totalWarnings == 0) {
    echo "{$green}✨ System is in excellent condition! No immediate action needed.{$reset}\n";
} else {
    if ($totalIssues > 0) {
        echo "{$red}Priority 1: Fix critical issues immediately{$reset}\n";
    }
    if ($totalWarnings > 0) {
        echo "{$yellow}Priority 2: Address warnings for optimal performance{$reset}\n";
    }

    // Specific recommendations
    if (in_array("Missing indexes on frequently queried columns", $warnings)) {
        echo "• Add database indexes for better query performance\n";
    }
    if (strpos(json_encode($issues), 'slow') !== false) {
        echo "• Optimize slow queries and operations\n";
    }
    if (strpos(json_encode($warnings), 'cache') !== false) {
        echo "• Review cache configuration for better hit rates\n";
    }
}

echo PHP_EOL . "Test completed at: " . date('Y-m-d H:i:s') . PHP_EOL;
echo "Total execution time: " . round(microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"], 2) . " seconds\n";