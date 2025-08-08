<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\DB;

echo "=== DATABASE PERFORMANCE ANALYSIS ===\n\n";

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// 1. Analyze slow queries
echo "1. Analyzing slow queries...\n";
$slowQueries = DB::select("
    SELECT 
        QUERY_SAMPLE_TEXT as query,
        COUNT_STAR as executions,
        AVG_TIMER_WAIT/1000000000 as avg_time_ms,
        SUM_TIMER_WAIT/1000000000 as total_time_ms
    FROM performance_schema.events_statements_summary_by_digest
    WHERE SCHEMA_NAME = 'askproai_db'
    AND AVG_TIMER_WAIT/1000000000 > 100
    ORDER BY AVG_TIMER_WAIT DESC
    LIMIT 10
");

if (count($slowQueries) > 0) {
    echo "Found " . count($slowQueries) . " slow queries (>100ms):\n";
    foreach ($slowQueries as $query) {
        echo "\n   Query: " . substr($query->query, 0, 100) . "...\n";
        echo "   Avg Time: " . round($query->avg_time_ms, 2) . "ms\n";
        echo "   Executions: " . $query->executions . "\n";
    }
} else {
    echo "No slow queries found.\n";
}

// 2. Check missing indexes
echo "\n2. Checking for missing indexes...\n";

$tables = [
    'calls' => ['company_id', 'created_at', 'status', 'agent_id', 'call_id', 'customer_id'],
    'appointments' => ['company_id', 'cal_event_id', 'customer_id', 'staff_id', 'branch_id', 'service_id', 'start_time'],
    'customers' => ['company_id', 'phone', 'email', 'created_at'],
    'companies' => ['slug', 'status'],
    'branches' => ['company_id', 'status'],
    'services' => ['company_id', 'branch_id', 'status'],
    'staff' => ['company_id', 'branch_id', 'email', 'cal_user_id'],
    'webhook_calls' => ['company_id', 'created_at', 'status'],
    'invoices' => ['company_id', 'status', 'due_date'],
    'mcp_servers' => ['status', 'type'],
];

$missingIndexes = [];

foreach ($tables as $table => $columns) {
    foreach ($columns as $column) {
        // Check if index exists
        $indexCheck = DB::select("
            SELECT COUNT(*) as count
            FROM INFORMATION_SCHEMA.STATISTICS
            WHERE TABLE_SCHEMA = 'askproai_db'
            AND TABLE_NAME = ?
            AND COLUMN_NAME = ?
        ", [$table, $column]);
        
        if ($indexCheck[0]->count == 0) {
            $missingIndexes[] = [
                'table' => $table,
                'column' => $column
            ];
        }
    }
}

if (count($missingIndexes) > 0) {
    echo "Found " . count($missingIndexes) . " missing indexes:\n";
    foreach ($missingIndexes as $idx) {
        echo "   - {$idx['table']}.{$idx['column']}\n";
    }
} else {
    echo "All recommended indexes are present.\n";
}

// 3. Table statistics
echo "\n3. Table statistics:\n";
$tableStats = DB::select("
    SELECT 
        TABLE_NAME as table_name,
        TABLE_ROWS as row_count,
        ROUND(DATA_LENGTH/1024/1024, 2) as data_size_mb,
        ROUND(INDEX_LENGTH/1024/1024, 2) as index_size_mb
    FROM INFORMATION_SCHEMA.TABLES
    WHERE TABLE_SCHEMA = 'askproai_db'
    AND TABLE_TYPE = 'BASE TABLE'
    ORDER BY TABLE_ROWS DESC
    LIMIT 10
");

foreach ($tableStats as $stat) {
    echo "   {$stat->table_name}: {$stat->row_count} rows, {$stat->data_size_mb}MB data, {$stat->index_size_mb}MB indexes\n";
}

// 4. Most frequent queries
echo "\n4. Most frequent queries:\n";
$frequentQueries = DB::select("
    SELECT 
        DIGEST_TEXT as query,
        COUNT_STAR as executions,
        AVG_TIMER_WAIT/1000000000 as avg_time_ms
    FROM performance_schema.events_statements_summary_by_digest
    WHERE SCHEMA_NAME = 'askproai_db'
    ORDER BY COUNT_STAR DESC
    LIMIT 5
");

foreach ($frequentQueries as $query) {
    echo "\n   Query: " . substr($query->query, 0, 100) . "...\n";
    echo "   Executions: " . $query->executions . "\n";
    echo "   Avg Time: " . round($query->avg_time_ms, 2) . "ms\n";
}

// 5. Generate optimization script
echo "\n5. Generating optimization script...\n";
$optimizationScript = "-- Database Optimization Script\n";
$optimizationScript .= "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";

foreach ($missingIndexes as $idx) {
    $indexName = "idx_{$idx['table']}_{$idx['column']}";
    $optimizationScript .= "CREATE INDEX {$indexName} ON {$idx['table']} ({$idx['column']});\n";
}

// Common composite indexes for better performance
$compositeIndexes = [
    "CREATE INDEX idx_calls_company_created ON calls (company_id, created_at DESC);",
    "CREATE INDEX idx_appointments_company_start ON appointments (company_id, start_time);",
    "CREATE INDEX idx_customers_company_phone ON customers (company_id, phone);",
    "CREATE INDEX idx_webhook_calls_company_created ON webhook_calls (company_id, created_at DESC);",
];

$optimizationScript .= "\n-- Composite indexes for common queries\n";
foreach ($compositeIndexes as $idx) {
    $optimizationScript .= $idx . "\n";
}

file_put_contents('database-optimization-' . date('Ymd-His') . '.sql', $optimizationScript);
echo "✅ Optimization script saved.\n";

echo "\n=== RECOMMENDATIONS ===\n";
echo "1. Add missing indexes (script generated)\n";
echo "2. Consider partitioning large tables (calls, webhook_calls)\n";
echo "3. Implement query caching for frequent queries\n";
echo "4. Review and optimize slow queries\n";
echo "5. Regular ANALYZE TABLE for statistics update\n";
?>