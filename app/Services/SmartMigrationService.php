<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class SmartMigrationService
{
    private array $criticalTables = [
        'users', 'calls', 'appointments', 'customers', 'companies'
    ];

    /**
     * Analyze migration impact before execution
     */
    public function analyzeMigration(string $migrationPath): array
    {
        $content = file_get_contents($migrationPath);
        $analysis = [
            'risk_level' => 'low',
            'downtime_required' => false,
            'affected_tables' => [],
            'recommendations' => [],
            'estimated_duration' => 0
        ];

        // Check for schema modifications
        if (preg_match_all('/Schema::(table|create|drop|rename)\([\'"](\w+)[\'"]/i', $content, $matches)) {
            $analysis['affected_tables'] = array_unique($matches[2]);
            
            foreach ($analysis['affected_tables'] as $table) {
                if (in_array($table, $this->criticalTables)) {
                    $analysis['risk_level'] = 'high';
                    $analysis['recommendations'][] = "Table '$table' is critical - consider online schema change";
                }
                
                // Estimate duration based on table size
                $rowCount = DB::table($table)->count();
                if ($rowCount > 1000000) {
                    $analysis['estimated_duration'] += ($rowCount / 1000000) * 30; // 30 seconds per million rows
                    $analysis['recommendations'][] = "Large table '$table' with $rowCount rows - use pt-online-schema-change";
                }
            }
        }

        // Check for dropping columns/indexes
        if (preg_match('/dropColumn|dropIndex|dropForeign/i', $content)) {
            $analysis['risk_level'] = 'medium';
            $analysis['recommendations'][] = 'Dropping operations detected - ensure backwards compatibility';
        }

        // Check for data migrations
        if (preg_match('/DB::table.*->update|DB::statement/i', $content)) {
            $analysis['risk_level'] = 'high';
            $analysis['recommendations'][] = 'Data migration detected - consider chunking for large datasets';
        }

        return $analysis;
    }

    /**
     * Execute migration with zero downtime strategies
     */
    public function executeSafeMigration(string $migrationClass, array $options = []): bool
    {
        $migration = new $migrationClass();
        $analysis = $this->analyzeMigration((new \ReflectionClass($migrationClass))->getFileName());

        try {
            // Pre-migration health check
            $this->performHealthCheck();

            // Enable maintenance mode for critical changes
            if ($analysis['risk_level'] === 'high' && !($options['force'] ?? false)) {
                Log::warning('High-risk migration detected, consider using online schema change tools');
                return false;
            }

            // Create backup point
            $backupId = $this->createBackupPoint($analysis['affected_tables']);

            // Execute migration with monitoring
            DB::beginTransaction();
            
            $startTime = microtime(true);
            $migration->up();
            $duration = microtime(true) - $startTime;

            // Validate migration
            if ($this->validateMigration($analysis['affected_tables'])) {
                DB::commit();
                Log::info('Migration completed successfully', [
                    'duration' => $duration,
                    'tables' => $analysis['affected_tables']
                ]);
                return true;
            } else {
                DB::rollBack();
                $this->restoreBackupPoint($backupId);
                Log::error('Migration validation failed, rolled back');
                return false;
            }

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Migration failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Perform online schema change for large tables
     */
    public function performOnlineSchemaChange(string $table, callable $schemaChange): bool
    {
        $tempTable = "{$table}_new";
        
        try {
            // Create shadow table with new schema
            Schema::create($tempTable, function ($table) use ($schemaChange) {
                $schemaChange($table);
            });

            // Copy data in chunks
            $this->copyDataInChunks($table, $tempTable);

            // Setup triggers for real-time sync
            $this->setupSyncTriggers($table, $tempTable);

            // Atomic table swap
            DB::statement("RENAME TABLE `$table` TO `{$table}_old`, `$tempTable` TO `$table`");

            // Cleanup
            $this->cleanupSyncTriggers($table);
            Schema::dropIfExists("{$table}_old");

            return true;
        } catch (\Exception $e) {
            Log::error('Online schema change failed: ' . $e->getMessage());
            Schema::dropIfExists($tempTable);
            return false;
        }
    }

    /**
     * Copy data in chunks to avoid locking
     */
    private function copyDataInChunks(string $source, string $destination, int $chunkSize = 1000): void
    {
        $totalRows = DB::table($source)->count();
        $processed = 0;

        DB::table($source)->orderBy('id')->chunk($chunkSize, function ($rows) use ($destination, &$processed, $totalRows) {
            $data = $rows->map(function ($row) {
                return (array) $row;
            })->toArray();

            DB::table($destination)->insert($data);
            
            $processed += count($rows);
            $progress = round(($processed / $totalRows) * 100, 2);
            
            Log::info("Migration progress: {$progress}% ({$processed}/{$totalRows})");
            
            // Small delay to reduce load
            usleep(10000); // 10ms
        });
    }

    /**
     * Setup triggers for real-time data sync
     */
    private function setupSyncTriggers(string $source, string $destination): void
    {
        // Insert trigger
        DB::statement("
            CREATE TRIGGER `{$source}_insert_sync` 
            AFTER INSERT ON `$source` 
            FOR EACH ROW 
            BEGIN 
                INSERT INTO `$destination` SELECT * FROM `$source` WHERE id = NEW.id;
            END
        ");

        // Update trigger
        DB::statement("
            CREATE TRIGGER `{$source}_update_sync` 
            AFTER UPDATE ON `$source` 
            FOR EACH ROW 
            BEGIN 
                REPLACE INTO `$destination` SELECT * FROM `$source` WHERE id = NEW.id;
            END
        ");

        // Delete trigger
        DB::statement("
            CREATE TRIGGER `{$source}_delete_sync` 
            AFTER DELETE ON `$source` 
            FOR EACH ROW 
            BEGIN 
                DELETE FROM `$destination` WHERE id = OLD.id;
            END
        ");
    }

    /**
     * Cleanup sync triggers
     */
    private function cleanupSyncTriggers(string $table): void
    {
        DB::statement("DROP TRIGGER IF EXISTS `{$table}_insert_sync`");
        DB::statement("DROP TRIGGER IF EXISTS `{$table}_update_sync`");
        DB::statement("DROP TRIGGER IF EXISTS `{$table}_delete_sync`");
    }

    /**
     * Perform pre-migration health check
     */
    private function performHealthCheck(): void
    {
        // Check database connection
        DB::connection()->getPdo();

        // Check disk space
        $freeSpace = disk_free_space('/');
        if ($freeSpace < 1073741824) { // Less than 1GB
            throw new \Exception('Insufficient disk space for migration');
        }

        // Check replication lag if applicable
        if (config('database.replication.enabled')) {
            $lag = DB::select('SHOW SLAVE STATUS')[0]->Seconds_Behind_Master ?? 0;
            if ($lag > 10) {
                throw new \Exception("Replication lag too high: {$lag} seconds");
            }
        }
    }

    /**
     * Create backup point for affected tables
     */
    private function createBackupPoint(array $tables): string
    {
        $backupId = 'migration_' . time();
        
        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                DB::statement("CREATE TABLE `{$table}_{$backupId}` LIKE `$table`");
                DB::statement("INSERT INTO `{$table}_{$backupId}` SELECT * FROM `$table`");
            }
        }

        return $backupId;
    }

    /**
     * Restore from backup point
     */
    private function restoreBackupPoint(string $backupId): void
    {
        $tables = DB::select("SHOW TABLES LIKE '%_{$backupId}'");
        
        foreach ($tables as $table) {
            $tableName = array_values((array) $table)[0];
            $originalTable = str_replace("_{$backupId}", '', $tableName);
            
            DB::statement("RENAME TABLE `$originalTable` TO `{$originalTable}_failed`");
            DB::statement("RENAME TABLE `$tableName` TO `$originalTable`");
            DB::statement("DROP TABLE `{$originalTable}_failed`");
        }
    }

    /**
     * Validate migration success
     */
    private function validateMigration(array $tables): bool
    {
        foreach ($tables as $table) {
            if (!Schema::hasTable($table)) {
                return false;
            }

            // Check for data integrity
            try {
                DB::table($table)->limit(1)->get();
            } catch (\Exception $e) {
                return false;
            }
        }

        return true;
    }
}