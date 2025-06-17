<?php
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "=== PREPARING DATABASE FOR CLEANUP ===\n\n";

// 1. Fix calls.kunde_id dependency
echo "1. Fixing calls.kunde_id dependency...\n";
if (Schema::hasColumn('calls', 'kunde_id')) {
    try {
        // First remove the foreign key constraint
        $foreignKeys = DB::select("
            SELECT CONSTRAINT_NAME 
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
            WHERE TABLE_NAME = 'calls' 
            AND COLUMN_NAME = 'kunde_id' 
            AND REFERENCED_TABLE_NAME IS NOT NULL
            AND TABLE_SCHEMA = ?
        ", [env('DB_DATABASE')]);
        
        foreach ($foreignKeys as $fk) {
            DB::statement("ALTER TABLE calls DROP FOREIGN KEY {$fk->CONSTRAINT_NAME}");
            echo "   ✓ Dropped foreign key: {$fk->CONSTRAINT_NAME}\n";
        }
        
        // Drop the column
        DB::statement("ALTER TABLE calls DROP COLUMN kunde_id");
        echo "   ✓ Dropped kunde_id column from calls\n";
    } catch (\Exception $e) {
        echo "   ✗ Error: " . $e->getMessage() . "\n";
    }
} else {
    echo "   ℹ️  kunde_id column not found in calls\n";
}

// 2. Fix tenant_id dependencies
echo "\n2. Fixing tenant_id dependencies...\n";
$tablesWithTenantId = ['staff', 'customers', 'appointments', 'calls'];

foreach ($tablesWithTenantId as $table) {
    if (Schema::hasColumn($table, 'tenant_id')) {
        try {
            // Remove foreign key
            $foreignKeys = DB::select("
                SELECT CONSTRAINT_NAME 
                FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                WHERE TABLE_NAME = ? 
                AND COLUMN_NAME = 'tenant_id' 
                AND REFERENCED_TABLE_NAME IS NOT NULL
                AND TABLE_SCHEMA = ?
            ", [$table, env('DB_DATABASE')]);
            
            foreach ($foreignKeys as $fk) {
                DB::statement("ALTER TABLE {$table} DROP FOREIGN KEY {$fk->CONSTRAINT_NAME}");
                echo "   ✓ Dropped foreign key from {$table}: {$fk->CONSTRAINT_NAME}\n";
            }
            
            // Set to NULL where needed
            DB::statement("UPDATE {$table} SET tenant_id = NULL WHERE tenant_id IS NOT NULL");
            echo "   ✓ Nullified tenant_id in {$table}\n";
            
        } catch (\Exception $e) {
            echo "   ✗ Error with {$table}: " . $e->getMessage() . "\n";
        }
    }
}

// 3. Fix user_statuses dependency
echo "\n3. Fixing user_statuses dependency...\n";
if (Schema::hasColumn('users', 'status_id')) {
    try {
        // Remove foreign key
        $foreignKeys = DB::select("
            SELECT CONSTRAINT_NAME 
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
            WHERE TABLE_NAME = 'users' 
            AND COLUMN_NAME = 'status_id' 
            AND REFERENCED_TABLE_NAME IS NOT NULL
            AND TABLE_SCHEMA = ?
        ", [env('DB_DATABASE')]);
        
        foreach ($foreignKeys as $fk) {
            DB::statement("ALTER TABLE users DROP FOREIGN KEY {$fk->CONSTRAINT_NAME}");
            echo "   ✓ Dropped foreign key: {$fk->CONSTRAINT_NAME}\n";
        }
        
        // Set default status
        DB::statement("UPDATE users SET status_id = 1 WHERE status_id IS NOT NULL");
        echo "   ✓ Set default status_id in users\n";
        
    } catch (\Exception $e) {
        echo "   ✗ Error: " . $e->getMessage() . "\n";
    }
}

// 4. Backup important data
echo "\n4. Backing up important data...\n";

// Backup retell_webhooks
try {
    $webhookCount = DB::table('retell_webhooks')->count();
    if ($webhookCount > 0) {
        // Export to JSON file
        $webhooks = DB::table('retell_webhooks')->get();
        $backupPath = __DIR__ . '/../backups/2025-06-17/retell_webhooks_backup.json';
        file_put_contents($backupPath, json_encode($webhooks, JSON_PRETTY_PRINT));
        echo "   ✓ Backed up {$webhookCount} webhook records to {$backupPath}\n";
    }
} catch (\Exception $e) {
    echo "   ✗ Could not backup webhooks: " . $e->getMessage() . "\n";
}

// 5. Check remaining foreign keys to tables we want to drop
echo "\n5. Checking remaining dependencies...\n";

$tablesToDrop = [
    'reservation_accessories', 'reservation_color_rules', 'reservation_files',
    'agents', 'kunden', 'tenants', 'user_statuses'
];

foreach ($tablesToDrop as $dropTable) {
    if (!Schema::hasTable($dropTable)) {
        continue;
    }
    
    $dependencies = DB::select("
        SELECT 
            TABLE_NAME,
            COLUMN_NAME,
            CONSTRAINT_NAME
        FROM 
            INFORMATION_SCHEMA.KEY_COLUMN_USAGE
        WHERE 
            REFERENCED_TABLE_NAME = ?
            AND TABLE_SCHEMA = ?
    ", [$dropTable, env('DB_DATABASE')]);
    
    if (!empty($dependencies)) {
        echo "\n   ⚠️  Table '{$dropTable}' still has dependencies:\n";
        foreach ($dependencies as $dep) {
            echo "      - {$dep->TABLE_NAME}.{$dep->COLUMN_NAME}\n";
        }
    } else {
        echo "   ✅ Table '{$dropTable}' has no dependencies\n";
    }
}

echo "\n=== PREPARATION COMPLETE ===\n";
echo "You can now run the cleanup migration safely.\n";