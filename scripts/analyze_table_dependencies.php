<?php
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "=== ANALYZING TABLE DEPENDENCIES ===\n\n";

// Tables we want to keep
$coreTables = [
    'companies', 'branches', 'phone_numbers',
    'users', 'staff', 'customers',
    'appointments', 'calls',
    'services', 'staff_services', 'staff_event_types', 'working_hours',
    'calcom_event_types', 'calcom_bookings', 'calcom_sync_logs',
    'migrations', 'jobs', 'failed_jobs', 'cache', 'cache_locks',
    'permissions', 'roles', 'role_has_permissions', 'model_has_roles', 'model_has_permissions',
    'invoices', 'billing_periods', 'company_pricing', 'branch_pricing_overrides'
];

// Check foreign key dependencies
echo "Checking foreign key dependencies...\n\n";

$allTables = DB::select('SHOW TABLES');
$tableField = 'Tables_in_' . env('DB_DATABASE', 'askproai_db');

foreach ($allTables as $table) {
    $tableName = $table->$tableField;
    
    // Skip if it's a core table
    if (in_array($tableName, $coreTables)) {
        continue;
    }
    
    // Check if any core table has a foreign key to this table
    $hasDependency = false;
    
    foreach ($coreTables as $coreTable) {
        if (!Schema::hasTable($coreTable)) {
            continue;
        }
        
        try {
            $foreignKeys = DB::select("
                SELECT 
                    CONSTRAINT_NAME,
                    COLUMN_NAME,
                    REFERENCED_TABLE_NAME,
                    REFERENCED_COLUMN_NAME
                FROM 
                    INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                WHERE 
                    TABLE_SCHEMA = ? 
                    AND TABLE_NAME = ?
                    AND REFERENCED_TABLE_NAME = ?
            ", [env('DB_DATABASE'), $coreTable, $tableName]);
            
            if (!empty($foreignKeys)) {
                echo "⚠️  WARNING: Core table '{$coreTable}' has foreign key to '{$tableName}'\n";
                foreach ($foreignKeys as $fk) {
                    echo "   - {$fk->COLUMN_NAME} → {$fk->REFERENCED_TABLE_NAME}.{$fk->REFERENCED_COLUMN_NAME}\n";
                }
                $hasDependency = true;
            }
        } catch (\Exception $e) {
            // Ignore errors
        }
    }
    
    if ($hasDependency) {
        echo "   ❌ Cannot drop '{$tableName}' - has dependencies\n\n";
    }
}

// Check for data in tables to be dropped
echo "\n=== CHECKING DATA IN TABLES TO DROP ===\n\n";

$tablesToDrop = array_diff(
    array_map(function($t) use ($tableField) { return $t->$tableField; }, $allTables),
    $coreTables
);

foreach ($tablesToDrop as $table) {
    try {
        $count = DB::table($table)->count();
        if ($count > 0) {
            echo "📊 Table '{$table}' has {$count} records\n";
            
            // Special checks for important data
            if ($table === 'staff_event_type_assignments' || $table === 'unified_event_types') {
                echo "   ⚠️  Contains event type mappings - verify data is migrated\n";
            }
        }
    } catch (\Exception $e) {
        echo "❌ Could not check table '{$table}': " . $e->getMessage() . "\n";
    }
}

echo "\n=== SUMMARY ===\n";
echo "Total tables: " . count($allTables) . "\n";
echo "Core tables to keep: " . count($coreTables) . "\n";
echo "Tables to drop: " . count($tablesToDrop) . "\n";