<?php

namespace App\Services\DatabaseProtection;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MigrationGuard
{
    /**
     * Verhindert das Löschen kritischer Tabellen
     */
    protected static $protectedTables = [
        'users',
        'companies',
        'branches',
        'customers',
        'appointments',
        'calls',
        'staff',
        'services',
        'calcom_event_types',
        'migrations',
    ];
    
    /**
     * Prüft Migration auf gefährliche Operationen
     */
    public static function validateMigration($migrationClass): array
    {
        $errors = [];
        $warnings = [];
        
        // Get migration SQL
        $migration = new $migrationClass();
        
        // Check for DROP TABLE on protected tables
        foreach (self::$protectedTables as $table) {
            if (method_exists($migration, 'up')) {
                $reflection = new \ReflectionMethod($migration, 'up');
                $content = file_get_contents($reflection->getFileName());
                
                if (preg_match("/DROP\s+TABLE\s+.*{$table}/i", $content)) {
                    $errors[] = "Attempting to DROP protected table: {$table}";
                }
                
                if (preg_match("/TRUNCATE\s+.*{$table}/i", $content)) {
                    $warnings[] = "Attempting to TRUNCATE table: {$table}";
                }
            }
        }
        
        return [
            'errors' => $errors,
            'warnings' => $warnings,
            'safe' => empty($errors)
        ];
    }
    
    /**
     * Erstellt automatisches Backup vor gefährlichen Operationen
     */
    public static function createSafetyBackup($reason = 'pre-migration')
    {
        $timestamp = date('Y-m-d_H-i-s');
        $filename = "safety_backup_{$reason}_{$timestamp}.sql";
        $filepath = storage_path("backups/safety/{$filename}");
        
        // Ensure directory exists
        if (!is_dir(dirname($filepath))) {
            mkdir(dirname($filepath), 0755, true);
        }
        
        $database = config('database.connections.mysql.database');
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');
        $host = config('database.connections.mysql.host');
        
        $command = sprintf(
            'mysqldump -h%s -u%s -p%s --single-transaction %s > %s 2>&1',
            escapeshellarg($host),
            escapeshellarg($username),
            escapeshellarg($password),
            escapeshellarg($database),
            escapeshellarg($filepath)
        );
        
        exec($command, $output, $return);
        
        if ($return === 0) {
            Log::info("Safety backup created: {$filepath}");
            return $filepath;
        } else {
            Log::error("Safety backup failed", ['output' => $output]);
            throw new \Exception("Failed to create safety backup");
        }
    }
    
    /**
     * Verhindert versehentliches Löschen von Daten
     */
    public static function protectTable($table)
    {
        // Add triggers to prevent accidental deletion
        DB::unprepared("
            CREATE TRIGGER IF NOT EXISTS protect_{$table}_delete
            BEFORE DELETE ON {$table}
            FOR EACH ROW
            BEGIN
                IF @allow_delete_{$table} IS NULL OR @allow_delete_{$table} != 1 THEN
                    SIGNAL SQLSTATE '45000' 
                    SET MESSAGE_TEXT = 'Deletion blocked by DatabaseProtection. Use @allow_delete_{$table} = 1 to override.';
                END IF;
            END;
        ");
    }
    
    /**
     * Entfernt Tabellenschutz temporär
     */
    public static function unprotectTable($table, callable $callback)
    {
        try {
            DB::statement("SET @allow_delete_{$table} = 1");
            $result = $callback();
            DB::statement("SET @allow_delete_{$table} = 0");
            return $result;
        } catch (\Exception $e) {
            DB::statement("SET @allow_delete_{$table} = 0");
            throw $e;
        }
    }
}