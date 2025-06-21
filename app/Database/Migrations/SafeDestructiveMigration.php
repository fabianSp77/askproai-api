<?php

namespace App\Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Base class for migrations that perform destructive operations
 * 
 * This class enforces safety checks before allowing DROP operations
 */
abstract class SafeDestructiveMigration extends Migration
{
    /**
     * Tables that will be dropped by this migration
     */
    abstract protected function getTablesToDrop(): array;
    
    /**
     * Run the migrations with safety checks
     */
    public function up(): void
    {
        $tables = $this->getTablesToDrop();
        
        if (empty($tables)) {
            throw new \Exception('No tables specified for dropping');
        }
        
        // Perform safety checks
        $this->performSafetyChecks($tables);
        
        // Create backup metadata
        $this->createBackupMetadata($tables);
        
        // Execute the actual migration
        $this->executeDestructiveOperation();
    }
    
    /**
     * The actual destructive operation to perform
     */
    abstract protected function executeDestructiveOperation(): void;
    
    /**
     * Perform safety checks before allowing destruction
     */
    protected function performSafetyChecks(array $tables): void
    {
        // Check if we're in production
        if (app()->environment('production')) {
            $this->requireBackupConfirmation();
        }
        
        // Log table statistics before dropping
        $this->logTableStatistics($tables);
        
        // Check for recent activity
        $this->checkRecentActivity($tables);
    }
    
    /**
     * Require backup confirmation in production
     */
    protected function requireBackupConfirmation(): void
    {
        $backupDir = '/var/backups/mysql/';
        $dbName = config('database.connections.mysql.database');
        $pattern = $backupDir . $dbName . '_' . date('Y-m-d') . '*.sql.gz';
        $todaysBackups = glob($pattern);
        
        if (empty($todaysBackups)) {
            throw new \Exception(
                "SAFETY VIOLATION: No backup found for today. " .
                "Please create a backup before running destructive migrations.\n" .
                "Run: mysqldump -u root -p {$dbName} | gzip > {$backupDir}{$dbName}_" . date('Y-m-d_H-i-s') . ".sql.gz"
            );
        }
        
        $latestBackup = end($todaysBackups);
        $backupAge = time() - filemtime($latestBackup);
        
        if ($backupAge > 3600) { // Older than 1 hour
            throw new \Exception(
                "SAFETY VIOLATION: Latest backup is older than 1 hour. " .
                "Please create a fresh backup before running destructive migrations."
            );
        }
        
        echo "\n✓ Recent backup found: " . basename($latestBackup) . "\n";
    }
    
    /**
     * Log statistics about tables before dropping
     */
    protected function logTableStatistics(array $tables): void
    {
        echo "\n=== Table Statistics Before Dropping ===\n";
        
        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                try {
                    $count = DB::table($table)->count();
                    $size = DB::select("
                        SELECT 
                            ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb
                        FROM information_schema.TABLES 
                        WHERE table_schema = ? AND table_name = ?
                    ", [config('database.connections.mysql.database'), $table])[0]->size_mb ?? 0;
                    
                    echo sprintf(
                        "Table: %-30s | Records: %8d | Size: %6.2f MB\n",
                        $table,
                        $count,
                        $size
                    );
                    
                    if ($count > 0) {
                        echo "  ⚠️  WARNING: Table contains data!\n";
                    }
                } catch (\Exception $e) {
                    echo "Table: {$table} - Error reading statistics: " . $e->getMessage() . "\n";
                }
            } else {
                echo "Table: {$table} - Does not exist\n";
            }
        }
        
        echo "\n";
    }
    
    /**
     * Check for recent activity in tables
     */
    protected function checkRecentActivity(array $tables): void
    {
        $activeTables = [];
        
        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                $columns = Schema::getColumnListing($table);
                
                // Check for recent updates
                if (in_array('updated_at', $columns)) {
                    try {
                        $recentCount = DB::table($table)
                            ->where('updated_at', '>=', now()->subDays(7))
                            ->count();
                            
                        if ($recentCount > 0) {
                            $activeTables[] = "{$table} ({$recentCount} records updated in last 7 days)";
                        }
                    } catch (\Exception $e) {
                        // Ignore errors
                    }
                }
            }
        }
        
        if (!empty($activeTables)) {
            echo "\n⚠️  WARNING: The following tables have recent activity:\n";
            foreach ($activeTables as $active) {
                echo "   - {$active}\n";
            }
            echo "\n";
            
            if (app()->runningInConsole()) {
                $confirm = readline("Are you SURE you want to drop these active tables? Type 'DROP TABLES' to confirm: ");
                if ($confirm !== 'DROP TABLES') {
                    throw new \Exception('Migration aborted: User did not confirm dropping active tables');
                }
            }
        }
    }
    
    /**
     * Create backup metadata for recovery purposes
     */
    protected function createBackupMetadata(array $tables): void
    {
        $metadata = [
            'migration' => get_class($this),
            'timestamp' => now()->toIso8601String(),
            'environment' => app()->environment(),
            'tables_dropped' => $tables,
            'executed_by' => get_current_user(),
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
        ];
        
        $metadataFile = storage_path('app/migrations/destructive_' . date('Y-m-d_H-i-s') . '.json');
        
        if (!is_dir(dirname($metadataFile))) {
            mkdir(dirname($metadataFile), 0755, true);
        }
        
        file_put_contents($metadataFile, json_encode($metadata, JSON_PRETTY_PRINT));
        
        echo "✓ Metadata saved to: {$metadataFile}\n";
    }
    
    /**
     * Default down method that warns about irreversibility
     */
    public function down(): void
    {
        $tables = $this->getTablesToDrop();
        
        echo "\n⚠️  WARNING: This migration dropped the following tables:\n";
        foreach ($tables as $table) {
            echo "   - {$table}\n";
        }
        echo "\nTo restore these tables, you must restore from a backup.\n";
        echo "Check storage/app/migrations/ for metadata about this migration.\n";
    }
}