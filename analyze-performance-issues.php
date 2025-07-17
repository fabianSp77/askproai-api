<?php
/**
 * Performance Analysis Script
 * Identifiziert Performance-Probleme nach Emergency Fix
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

echo "📊 Performance Analysis Report\n";
echo "============================\n\n";

// 1. Analyze Database Queries
echo "1. Database Query Analysis:\n";
echo "--------------------------\n";

try {
    // Enable query logging
    DB::enableQueryLog();
    
    // Run some typical queries
    $testQueries = [
        'Recent Calls' => function() {
            return DB::table('calls')
                ->where('created_at', '>', now()->subDays(7))
                ->limit(100)
                ->get();
        },
        'Active Appointments' => function() {
            return DB::table('appointments')
                ->where('status', 'scheduled')
                ->where('scheduled_at', '>', now())
                ->limit(50)
                ->get();
        },
        'Customer Search' => function() {
            return DB::table('customers')
                ->where('phone', 'LIKE', '%123456%')
                ->limit(10)
                ->get();
        }
    ];
    
    foreach ($testQueries as $name => $query) {
        $startTime = microtime(true);
        $result = $query();
        $duration = round((microtime(true) - $startTime) * 1000, 2);
        
        echo "   - $name: {$duration}ms";
        if ($duration > 100) {
            echo " ⚠️ SLOW";
        }
        echo "\n";
    }
    
    // Get query log
    $queries = DB::getQueryLog();
    $slowQueries = array_filter($queries, fn($q) => $q['time'] > 100);
    
    if (count($slowQueries) > 0) {
        echo "\n   ⚠️ Found " . count($slowQueries) . " slow queries (>100ms)\n";
    }
    
} catch (\Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}

// 2. Check Missing Indexes
echo "\n2. Missing Index Analysis:\n";
echo "--------------------------\n";

try {
    $tables = [
        'calls' => ['company_id', 'created_at', 'phone_number'],
        'appointments' => ['company_id', 'branch_id', 'status', 'scheduled_at'],
        'customers' => ['company_id', 'phone', 'email'],
        'staff' => ['company_id', 'branch_id'],
        'balance_topups' => ['company_id', 'created_at', 'status']
    ];
    
    foreach ($tables as $table => $columns) {
        echo "   Table: $table\n";
        
        // Get existing indexes
        $indexes = DB::select("SHOW INDEX FROM $table");
        $indexedColumns = array_column($indexes, 'Column_name');
        
        foreach ($columns as $column) {
            if (!in_array($column, $indexedColumns)) {
                echo "      ❌ Missing index on: $column\n";
            } else {
                echo "      ✅ Index exists on: $column\n";
            }
        }
    }
} catch (\Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}

// 3. Memory Usage Analysis
echo "\n3. Memory Usage Analysis:\n";
echo "------------------------\n";

$memoryUsage = [
    'Current' => round(memory_get_usage() / 1024 / 1024, 2),
    'Peak' => round(memory_get_peak_usage() / 1024 / 1024, 2),
    'Limit' => ini_get('memory_limit')
];

foreach ($memoryUsage as $type => $value) {
    echo "   - $type: {$value}MB\n";
}

// 4. Cache Performance
echo "\n4. Cache Performance:\n";
echo "--------------------\n";

try {
    // Test cache write
    $startTime = microtime(true);
    Cache::put('test_key', str_repeat('x', 1000), 60);
    $writeTime = round((microtime(true) - $startTime) * 1000, 2);
    
    // Test cache read
    $startTime = microtime(true);
    $value = Cache::get('test_key');
    $readTime = round((microtime(true) - $startTime) * 1000, 2);
    
    echo "   - Cache Write: {$writeTime}ms\n";
    echo "   - Cache Read: {$readTime}ms\n";
    
    if ($writeTime > 10 || $readTime > 5) {
        echo "   ⚠️ Cache performance is slow\n";
    }
    
    Cache::forget('test_key');
} catch (\Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}

// 5. N+1 Query Detection
echo "\n5. N+1 Query Pattern Detection:\n";
echo "------------------------------\n";

try {
    DB::flushQueryLog();
    DB::enableQueryLog();
    
    // Simulate potential N+1 scenario
    $companies = \App\Models\Company::limit(5)->get();
    foreach ($companies as $company) {
        $count = $company->calls()->count(); // This might cause N+1
    }
    
    $queries = DB::getQueryLog();
    $selectQueries = array_filter($queries, fn($q) => str_starts_with(strtolower($q['query']), 'select'));
    
    if (count($selectQueries) > count($companies) + 1) {
        echo "   ⚠️ Potential N+1 query detected!\n";
        echo "   - Expected queries: " . (count($companies) + 1) . "\n";
        echo "   - Actual queries: " . count($selectQueries) . "\n";
    } else {
        echo "   ✅ No N+1 queries detected in test\n";
    }
} catch (\Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}

// 6. Recommendations
echo "\n6. Performance Recommendations:\n";
echo "------------------------------\n";

$recommendations = [];

// Check if query cache is enabled
$queryCache = DB::select("SHOW VARIABLES LIKE 'query_cache_type'");
if (empty($queryCache) || $queryCache[0]->Value !== 'ON') {
    $recommendations[] = "Enable MySQL query cache";
}

// Check if opcache is enabled
if (!function_exists('opcache_get_status') || !opcache_get_status()) {
    $recommendations[] = "Enable PHP OPcache";
}

// Check Redis connection
try {
    $redis = Cache::store('redis')->getRedis();
    $redis->ping();
} catch (\Exception $e) {
    $recommendations[] = "Redis connection issues detected";
}

if (empty($recommendations)) {
    echo "   ✅ No immediate recommendations\n";
} else {
    foreach ($recommendations as $rec) {
        echo "   ⚠️ $rec\n";
    }
}

// 7. Generate SQL for missing indexes
echo "\n7. SQL Commands for Missing Indexes:\n";
echo "-----------------------------------\n";

$indexCommands = [
    "ALTER TABLE calls ADD INDEX idx_company_created (company_id, created_at);",
    "ALTER TABLE calls ADD INDEX idx_phone (phone_number);",
    "ALTER TABLE appointments ADD INDEX idx_branch_status (branch_id, status);",
    "ALTER TABLE appointments ADD INDEX idx_scheduled (scheduled_at);",
    "ALTER TABLE customers ADD INDEX idx_phone (phone);",
    "ALTER TABLE customers ADD INDEX idx_company_phone (company_id, phone);",
    "ALTER TABLE balance_topups ADD INDEX idx_company_created (company_id, created_at);"
];

foreach ($indexCommands as $cmd) {
    echo "$cmd\n";
}

echo "\n============================\n";
echo "✅ Performance analysis completed\n";
echo "\nNext steps:\n";
echo "1. Apply missing indexes (use SQL commands above)\n";
echo "2. Enable query logging in production briefly\n";
echo "3. Monitor slow query log\n";
echo "4. Consider implementing query result caching\n";