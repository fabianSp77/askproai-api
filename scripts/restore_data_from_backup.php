#!/usr/bin/env php
<?php

/**
 * Data Recovery Script for AskProAI
 * 
 * This script restores data from the June 17, 2025 backup that was accidentally
 * deleted by the cleanup_redundant_tables migration.
 * 
 * Usage: php restore_data_from_backup.php [--dry-run]
 */

require __DIR__.'/../vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$isDryRun = in_array('--dry-run', $argv);

echo "=== AskProAI Data Recovery Script ===\n";
echo "Backup date: 2025-06-17 03:05\n";
echo "Mode: " . ($isDryRun ? "DRY RUN" : "LIVE RESTORE") . "\n\n";

// Tables to restore in order (respecting foreign key dependencies)
$tablesToRestore = [
    'companies',
    'branches',
    'staff',
    'services',
    'customers',
    'calcom_event_types',
    'appointments',
    'calls',
    'calcom_bookings',
    'staff_services',
    'staff_event_types',
    'working_hours',
    'phone_numbers'
];

$backupFile = '/var/www/api-gateway/tmp/askproai_db_2025-06-17_03-05.sql';

if (!file_exists($backupFile)) {
    die("Error: Backup file not found at $backupFile\n");
}

echo "Reading backup file...\n";
$backupContent = file_get_contents($backupFile);

if (!$isDryRun) {
    echo "Disabling foreign key checks...\n";
    DB::statement('SET FOREIGN_KEY_CHECKS=0');
}

$restoredData = [];

foreach ($tablesToRestore as $table) {
    echo "\nProcessing table: $table\n";
    
    // Check if table exists
    if (!Schema::hasTable($table)) {
        echo "  WARNING: Table $table does not exist, skipping...\n";
        continue;
    }
    
    // Find INSERT statements for this table
    // Handle both single-line and multi-line INSERT statements
    $pattern = '/INSERT INTO `' . preg_quote($table, '/') . '` VALUES\s*\n?(.*?)(?=\n(?:INSERT INTO|CREATE TABLE|ALTER TABLE|DROP TABLE|LOCK TABLES|UNLOCK TABLES|--|\/\*|\z))/s';
    
    if (preg_match($pattern, $backupContent, $matches)) {
        $insertData = trim($matches[1]);
        
        if (empty($insertData)) {
            echo "  No data found for $table\n";
            continue;
        }
        
        // Count records
        $recordCount = substr_count($insertData, "\n(") + 1;
        echo "  Found $recordCount records to restore\n";
        
        if (!$isDryRun) {
            try {
                // Check current record count
                $currentCount = DB::table($table)->count();
                echo "  Current records in table: $currentCount\n";
                
                if ($currentCount > 0) {
                    echo "  WARNING: Table $table already has data. Skipping to avoid duplicates.\n";
                    echo "  Use --force-overwrite to truncate and restore (dangerous!)\n";
                    continue;
                }
                
                // Prepare the full INSERT statement
                $insertStatement = "INSERT INTO `$table` VALUES\n" . $insertData . ";";
                
                // Execute the INSERT
                DB::unprepared($insertStatement);
                
                // Verify restoration
                $newCount = DB::table($table)->count();
                echo "  ✓ Restored $newCount records successfully\n";
                $restoredData[$table] = $newCount;
                
            } catch (\Exception $e) {
                echo "  ERROR: Failed to restore $table: " . $e->getMessage() . "\n";
            }
        } else {
            $restoredData[$table] = $recordCount;
        }
    } else {
        echo "  No INSERT statement found for $table\n";
    }
}

if (!$isDryRun) {
    echo "\nRe-enabling foreign key checks...\n";
    DB::statement('SET FOREIGN_KEY_CHECKS=1');
}

echo "\n=== Recovery Summary ===\n";
foreach ($restoredData as $table => $count) {
    echo "$table: $count records " . ($isDryRun ? "would be restored" : "restored") . "\n";
}

if ($isDryRun) {
    echo "\nThis was a DRY RUN. No data was modified.\n";
    echo "Run without --dry-run to perform actual restoration.\n";
} else {
    echo "\nData restoration completed!\n";
    echo "\nIMPORTANT NEXT STEPS:\n";
    echo "1. Verify data integrity by checking key relationships\n";
    echo "2. Test the application functionality\n";
    echo "3. Create a new backup immediately\n";
    echo "4. Update migration to prevent future data loss\n";
}

echo "\n";