<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "=== DATABASE PERFORMANCE ANALYSIS (SIMPLE) ===\n\n";

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// 1. Check existing indexes
echo "1. Analyzing existing indexes...\n";

$tables = [
    'calls', 'appointments', 'customers', 'companies', 
    'branches', 'services', 'staff', 'webhook_calls', 
    'invoices', 'mcp_servers'
];

$indexReport = [];

foreach ($tables as $table) {
    if (!Schema::hasTable($table)) {
        continue;
    }
    
    $indexes = DB::select("SHOW INDEX FROM {$table}");
    
    $indexReport[$table] = [
        'total' => count($indexes),
        'columns' => []
    ];
    
    foreach ($indexes as $index) {
        $indexReport[$table]['columns'][] = $index->Column_name;
    }
}

echo "Index Summary:\n";
foreach ($indexReport as $table => $info) {
    echo "   {$table}: {$info['total']} indexes on columns: " . implode(', ', array_unique($info['columns'])) . "\n";
}

// 2. Table sizes and row counts
echo "\n2. Table statistics:\n";
$tableStats = DB::select("
    SELECT 
        TABLE_NAME as table_name,
        TABLE_ROWS as row_count,
        ROUND(DATA_LENGTH/1024/1024, 2) as data_size_mb,
        ROUND(INDEX_LENGTH/1024/1024, 2) as index_size_mb,
        ROUND((DATA_LENGTH + INDEX_LENGTH)/1024/1024, 2) as total_size_mb
    FROM INFORMATION_SCHEMA.TABLES
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_TYPE = 'BASE TABLE'
    ORDER BY TABLE_ROWS DESC
");

foreach ($tableStats as $stat) {
    if ($stat->row_count > 0) {
        echo "   {$stat->table_name}: " . number_format($stat->row_count) . " rows, {$stat->total_size_mb}MB total ({$stat->data_size_mb}MB data, {$stat->index_size_mb}MB indexes)\n";
    }
}

// 3. Identify missing indexes
echo "\n3. Recommended indexes (based on common query patterns):\n";

$recommendedIndexes = [
    'calls' => [
        ['company_id', 'created_at'],
        ['status'],
        ['call_id'],
        ['customer_id'],
        ['agent_id']
    ],
    'appointments' => [
        ['company_id', 'start_time'],
        ['cal_event_id'],
        ['customer_id'],
        ['staff_id'],
        ['branch_id'],
        ['service_id'],
        ['status']
    ],
    'customers' => [
        ['company_id', 'phone'],
        ['company_id', 'email'],
        ['phone'],
        ['email']
    ],
    'webhook_calls' => [
        ['company_id', 'created_at'],
        ['status'],
        ['webhook_id']
    ],
    'invoices' => [
        ['company_id', 'status'],
        ['due_date'],
        ['customer_id']
    ],
    'staff' => [
        ['company_id', 'branch_id'],
        ['email'],
        ['cal_user_id']
    ]
];

$createIndexStatements = [];

foreach ($recommendedIndexes as $table => $indexGroups) {
    if (!Schema::hasTable($table)) {
        continue;
    }
    
    echo "\n   Table: {$table}\n";
    
    foreach ($indexGroups as $columns) {
        $columnList = is_array($columns) ? $columns : [$columns];
        $indexName = 'idx_' . $table . '_' . implode('_', $columnList);
        
        // Check if index already exists
        $indexExists = false;
        if (isset($indexReport[$table])) {
            $existingIndexes = DB::select("SHOW INDEX FROM {$table}");
            foreach ($existingIndexes as $idx) {
                if ($idx->Key_name == $indexName) {
                    $indexExists = true;
                    break;
                }
            }
        }
        
        if (!$indexExists) {
            $columnsStr = implode(', ', $columnList);
            echo "      ❌ Missing: {$indexName} on ({$columnsStr})\n";
            $createIndexStatements[] = "CREATE INDEX {$indexName} ON {$table} ({$columnsStr});";
        } else {
            echo "      ✅ Exists: {$indexName}\n";
        }
    }
}

// 4. Generate optimization script
if (count($createIndexStatements) > 0) {
    echo "\n4. Generating optimization script...\n";
    $script = "-- Database Optimization Script\n";
    $script .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
    $script .= "-- Missing indexes: " . count($createIndexStatements) . "\n\n";
    
    foreach ($createIndexStatements as $stmt) {
        $script .= $stmt . "\n";
    }
    
    $script .= "\n-- Update table statistics\n";
    foreach ($tables as $table) {
        if (Schema::hasTable($table)) {
            $script .= "ANALYZE TABLE {$table};\n";
        }
    }
    
    $filename = 'database-optimization-' . date('Ymd-His') . '.sql';
    file_put_contents($filename, $script);
    echo "✅ Optimization script saved to: {$filename}\n";
}

// 5. Query optimization tips
echo "\n5. Query Optimization Tips:\n";
echo "   • Use EXPLAIN on slow queries to analyze execution plans\n";
echo "   • Add composite indexes for queries with multiple WHERE conditions\n";
echo "   • Consider partitioning tables with >1M rows (webhook_calls, calls)\n";
echo "   • Use query caching for frequently accessed data\n";
echo "   • Implement pagination for large result sets\n";
echo "   • Use eager loading to prevent N+1 queries in Eloquent\n";

// 6. Filament-specific optimizations
echo "\n6. Filament-specific optimizations:\n";
echo "   • Use ->modifyQueryUsing() to optimize table queries\n";
echo "   • Implement caching for stats widgets\n";
echo "   • Use ->searchable() only on indexed columns\n";
echo "   • Limit eager loading depth with ->with()\n";
echo "   • Use chunking for bulk operations\n";

echo "\n=== ANALYSIS COMPLETE ===\n";
?>