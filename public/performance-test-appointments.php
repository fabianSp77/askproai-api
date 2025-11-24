<?php
/**
 * Performance Testing Script for Appointments Admin Page
 * Tests query performance with various data sizes
 */

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use App\Models\Appointment;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

function formatBytes($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, 2) . ' ' . $units[$pow];
}

function runPerformanceTest($testName, $query) {
    echo "\n=== $testName ===\n";

    // Memory before
    $memBefore = memory_get_usage();
    $peakMemBefore = memory_get_peak_usage();

    // Enable query logging
    DB::enableQueryLog();

    // Start timer
    $startTime = microtime(true);

    // Execute query
    $results = $query();

    // End timer
    $endTime = microtime(true);
    $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

    // Memory after
    $memAfter = memory_get_usage();
    $peakMemAfter = memory_get_peak_usage();
    $memUsed = $memAfter - $memBefore;

    // Get query logs
    $logs = DB::getQueryLog();
    DB::flushQueryLog();

    // Calculate results
    $totalQueries = count($logs);
    $totalQueryTime = array_sum(array_column($logs, 'time'));

    // Display results
    echo "Execution time: " . round($executionTime, 2) . "ms\n";
    echo "Total queries: $totalQueries\n";
    echo "Query time: " . round($totalQueryTime, 2) . "ms\n";
    echo "Memory used: " . formatBytes($memUsed) . "\n";
    echo "Peak memory: " . formatBytes($peakMemAfter) . "\n";

    if (is_countable($results)) {
        echo "Records returned: " . count($results) . "\n";
    }

    // Show individual queries
    if ($totalQueries <= 10) {
        echo "\nQuery breakdown:\n";
        foreach ($logs as $i => $log) {
            echo ($i + 1) . ". " . round($log['time'], 2) . "ms - " . substr($log['query'], 0, 100) . "...\n";
        }
    }

    return [
        'execution_time' => $executionTime,
        'total_queries' => $totalQueries,
        'query_time' => $totalQueryTime,
        'memory_used' => $memUsed,
        'peak_memory' => $peakMemAfter
    ];
}

// Current dataset size
echo "\n================================================\n";
echo "PERFORMANCE ANALYSIS - APPOINTMENTS ADMIN PAGE\n";
echo "================================================\n";

$totalAppointments = Appointment::count();
echo "\nCurrent database size: $totalAppointments appointments\n";

// Test 1: List page with eager loading
$test1 = runPerformanceTest("List Page Query (with eager loading)", function() {
    return Appointment::with(['company', 'branch', 'customer', 'staff', 'service'])
        ->orderBy('starts_at', 'desc')
        ->paginate(50);
});

// Test 2: List page without eager loading (N+1 problem)
$test2 = runPerformanceTest("List Page Query (WITHOUT eager loading - N+1)", function() {
    $appointments = Appointment::orderBy('starts_at', 'desc')
        ->limit(50)
        ->get();

    // Simulate accessing relationships (N+1)
    foreach ($appointments as $apt) {
        $customer = $apt->customer;
        $service = $apt->service;
        $staff = $apt->staff;
        $branch = $apt->branch;
    }

    return $appointments;
});

// Test 3: Filters and search
$test3 = runPerformanceTest("Filtered Query (status + date range + search)", function() {
    return Appointment::with(['company', 'branch', 'customer', 'staff', 'service'])
        ->where('status', 'confirmed')
        ->whereBetween('starts_at', [now()->startOfMonth(), now()->endOfMonth()])
        ->whereHas('customer', function($q) {
            $q->where('name', 'like', '%test%');
        })
        ->orderBy('starts_at', 'desc')
        ->paginate(50);
});

// Test 4: AppointmentStats widget
$test4 = runPerformanceTest("AppointmentStats Widget", function() {
    $today = today();
    $tomorrow = $today->copy()->addDay();
    $thisWeek = [now()->startOfWeek(), now()->endOfWeek()];
    $thisMonth = [now()->startOfMonth(), now()->endOfMonth()];

    return Appointment::selectRaw("
        COUNT(CASE WHEN DATE(starts_at) = ? THEN 1 END) as today_count,
        COUNT(CASE WHEN DATE(starts_at) = ? THEN 1 END) as tomorrow_count,
        COUNT(CASE WHEN starts_at BETWEEN ? AND ? THEN 1 END) as week_count,
        COUNT(CASE WHEN starts_at BETWEEN ? AND ? THEN 1 END) as month_count,
        COUNT(CASE WHEN status IN ('confirmed', 'accepted') AND DATE(starts_at) = ? THEN 1 END) as confirmed_today,
        COUNT(CASE WHEN status = 'cancelled' AND DATE(created_at) >= ? THEN 1 END) as cancelled_week
    ", [
        $today, $tomorrow,
        $thisWeek[0], $thisWeek[1],
        $thisMonth[0], $thisMonth[1],
        $today,
        now()->subWeek()
    ])->first();
});

// Test 5: Complex aggregation (no indexes)
$test5 = runPerformanceTest("Complex Aggregation Query", function() {
    return DB::table('appointments')
        ->select(
            DB::raw('DATE(starts_at) as date'),
            DB::raw('COUNT(*) as total'),
            DB::raw('SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed'),
            DB::raw('SUM(CASE WHEN status = "cancelled" THEN 1 ELSE 0 END) as cancelled')
        )
        ->whereBetween('starts_at', [now()->subDays(30), now()])
        ->groupBy('date')
        ->orderBy('date', 'desc')
        ->get();
});

// Test 6: Relationship counting (for badges)
$test6 = runPerformanceTest("Navigation Badge Count", function() {
    // This is what happens in getNavigationBadge()
    return Appointment::whereNotNull('starts_at')->count();
});

// Extrapolation analysis
echo "\n\n=== SCALABILITY ANALYSIS ===\n";
echo "Current dataset: $totalAppointments appointments\n";
echo "List page query time: " . round($test1['query_time'], 2) . "ms\n";

// Linear extrapolation (simplified)
$scalingFactor = $test1['query_time'] / max($totalAppointments, 1);
$projected1k = $scalingFactor * 1000;
$projected10k = $scalingFactor * 10000;
$projected100k = $scalingFactor * 100000;

echo "\nProjected query times (linear scaling):\n";
echo "- 1,000 appointments: " . round($projected1k, 2) . "ms\n";
echo "- 10,000 appointments: " . round($projected10k, 2) . "ms\n";
echo "- 100,000 appointments: " . round($projected100k, 2) . "ms\n";

// Identify bottlenecks
echo "\n\n=== BOTTLENECK ANALYSIS ===\n";

$n1Overhead = $test2['query_time'] - $test1['query_time'];
echo "1. N+1 Query Problem: +" . round($n1Overhead, 2) . "ms overhead\n";

if ($test3['query_time'] > $test1['query_time'] * 2) {
    echo "2. Filter Performance: whereHas() is slow (" . round($test3['query_time'], 2) . "ms)\n";
}

if ($test4['query_time'] > 10) {
    echo "3. Stats Widget: Aggregation query is slow (" . round($test4['query_time'], 2) . "ms)\n";
}

if ($test5['query_time'] > 20) {
    echo "4. Complex Aggregation: Group by without indexes (" . round($test5['query_time'], 2) . "ms)\n";
}

// Check existing indexes
$indexes = DB::select("SHOW INDEXES FROM appointments");
$indexedColumns = array_unique(array_column($indexes, 'Column_name'));

echo "\n\n=== INDEX COVERAGE ===\n";
echo "Total indexes: " . count($indexes) . "\n";
echo "Indexed columns: " . implode(', ', array_slice($indexedColumns, 0, 10)) . "...\n";

// Missing indexes analysis
$requiredIndexes = [
    'company_id + starts_at + status' => 'For filtered queries',
    'customer_id + starts_at' => 'For customer history',
    'status + created_at' => 'For recent cancellations',
    'starts_at + ends_at' => 'For availability checks'
];

echo "\n=== RECOMMENDED INDEXES ===\n";
foreach ($requiredIndexes as $index => $reason) {
    echo "- $index: $reason\n";
}

echo "\n\n=== OPTIMIZATION RECOMMENDATIONS ===\n";
echo "1. CRITICAL: Enable query caching for stats widgets (5min TTL)\n";
echo "2. HIGH: Add composite indexes for common filter combinations\n";
echo "3. HIGH: Implement cursor-based pagination for large datasets\n";
echo "4. MEDIUM: Use database views for complex aggregations\n";
echo "5. MEDIUM: Implement read replicas for reporting queries\n";
echo "6. LOW: Consider table partitioning at >100k records\n";

echo "\n================================================\n";
echo "Performance test completed successfully.\n";
echo "================================================\n\n";