<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BackupDatabaseData extends Command
{
    protected $signature = 'data:backup
                            {--tables=* : Specific tables to backup}
                            {--format=sql : Format: sql or json}';

    protected $description = 'Create a backup of database data before cleanup';

    public function handle()
    {
        $timestamp = now()->format('Y-m-d_H-i-s');
        $backupDir = storage_path('backups');

        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $this->info('🔒 Creating Data Backup');
        $this->info('Timestamp: ' . $timestamp);
        $this->info(str_repeat('=', 50));

        $tables = [
            'customers',
            'calls',
            'appointments',
            'services',
            'retell_agents',
            'phone_numbers',
            'companies',
            'branches',
        ];

        $format = $this->option('format');
        $backupFile = $backupDir . '/backup_' . $timestamp . '.' . $format;

        if ($format === 'sql') {
            $this->createSqlBackup($backupFile, $tables);
        } else {
            $this->createJsonBackup($backupFile, $tables);
        }

        $this->info('');
        $this->info('✅ Backup created successfully!');
        $this->info('Location: ' . $backupFile);
        $this->info('Size: ' . $this->formatBytes(filesize($backupFile)));

        return 0;
    }

    private function createSqlBackup(string $file, array $tables): void
    {
        $sql = "-- Database Backup: " . now() . "\n\n";

        foreach ($tables as $table) {
            if (!DB::getSchemaBuilder()->hasTable($table)) {
                $this->warn("Table $table does not exist, skipping...");
                continue;
            }

            $this->info("Backing up table: $table");

            // Get all records
            $records = DB::table($table)->get();
            $count = $records->count();

            if ($count > 0) {
                $sql .= "-- Table: $table ($count records)\n";
                $sql .= "DELETE FROM `$table`;\n";

                foreach ($records as $record) {
                    $columns = array_keys((array) $record);
                    $values = array_map(function($value) {
                        if ($value === null) {
                            return 'NULL';
                        }
                        return "'" . addslashes($value) . "'";
                    }, (array) $record);

                    $sql .= "INSERT INTO `$table` (`" . implode('`, `', $columns) . "`) VALUES (" . implode(', ', $values) . ");\n";
                }

                $sql .= "\n";
            }

            $this->info("  → $count records backed up");
        }

        file_put_contents($file, $sql);
    }

    private function createJsonBackup(string $file, array $tables): void
    {
        $backup = [
            'timestamp' => now()->toIso8601String(),
            'tables' => []
        ];

        foreach ($tables as $table) {
            if (!DB::getSchemaBuilder()->hasTable($table)) {
                $this->warn("Table $table does not exist, skipping...");
                continue;
            }

            $this->info("Backing up table: $table");

            $records = DB::table($table)->get();
            $backup['tables'][$table] = [
                'count' => $records->count(),
                'data' => $records->toArray()
            ];

            $this->info("  → {$records->count()} records backed up");
        }

        file_put_contents($file, json_encode($backup, JSON_PRETTY_PRINT));
    }

    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }
}