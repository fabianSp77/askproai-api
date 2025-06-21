<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class AutoBackupDatabase extends Command
{
    protected $signature = 'askproai:backup
                            {--type=full : Backup type: full, incremental, or critical}
                            {--retention=30 : Days to keep backups}
                            {--compress : Compress backup file}
                            {--encrypt : Encrypt backup file}';

    protected $description = 'Automated database backup with retention policy';

    public function handle()
    {
        $type = $this->option('type');
        $retention = $this->option('retention');
        $compress = $this->option('compress');
        $encrypt = $this->option('encrypt');
        
        $this->info("Starting {$type} backup...");
        
        // Create backup directory
        $backupDir = storage_path('backups/database');
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }
        
        // Generate filename
        $timestamp = Carbon::now()->format('Y-m-d_H-i-s');
        $filename = "askproai_{$type}_{$timestamp}.sql";
        $filepath = "{$backupDir}/{$filename}";
        
        // Get database credentials
        $database = config('database.connections.mysql.database');
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');
        $host = config('database.connections.mysql.host');
        
        try {
            // Create backup based on type
            switch ($type) {
                case 'full':
                    $this->createFullBackup($filepath, $database, $username, $password, $host);
                    break;
                case 'incremental':
                    $this->createIncrementalBackup($filepath, $database, $username, $password, $host);
                    break;
                case 'critical':
                    $this->createCriticalBackup($filepath, $database, $username, $password, $host);
                    break;
            }
            
            // Compress if requested
            if ($compress) {
                $this->compressBackup($filepath);
                $filepath .= '.gz';
            }
            
            // Encrypt if requested
            if ($encrypt) {
                $this->encryptBackup($filepath);
                $filepath .= '.enc';
            }
            
            // Verify backup
            $this->verifyBackup($filepath);
            
            // Clean old backups
            $this->cleanOldBackups($backupDir, $retention);
            
            // Log success
            $this->logBackup($type, $filepath, 'success');
            
            $this->info("✅ Backup completed successfully: {$filepath}");
            
            // Send notification
            $this->sendNotification('success', $filepath);
            
        } catch (\Exception $e) {
            $this->error("❌ Backup failed: " . $e->getMessage());
            $this->logBackup($type, $filepath, 'failed', $e->getMessage());
            $this->sendNotification('failed', $filepath, $e->getMessage());
            return 1;
        }
        
        return 0;
    }
    
    private function createFullBackup($filepath, $database, $username, $password, $host)
    {
        $command = sprintf(
            'mysqldump -h%s -u%s -p%s --single-transaction --routines --triggers --events %s > %s 2>&1',
            escapeshellarg($host),
            escapeshellarg($username),
            escapeshellarg($password),
            escapeshellarg($database),
            escapeshellarg($filepath)
        );
        
        exec($command, $output, $return);
        
        if ($return !== 0) {
            throw new \Exception("mysqldump failed: " . implode("\n", $output));
        }
    }
    
    private function createIncrementalBackup($filepath, $database, $username, $password, $host)
    {
        // Get last backup timestamp
        $lastBackup = DB::table('backup_logs')
            ->where('type', 'incremental')
            ->where('status', 'success')
            ->orderBy('created_at', 'desc')
            ->first();
            
        $since = $lastBackup ? $lastBackup->created_at : Carbon::now()->subDay();
        
        // Check if tables exist first
        $existingTables = DB::select("SHOW TABLES");
        $tableNames = array_map(function($table) use ($database) {
            return $table->{"Tables_in_{$database}"};
        }, $existingTables);
        
        // Export only changed data from existing tables
        $tables = ['appointments', 'calls', 'customers', 'staff', 'services', 'companies', 'branches'];
        $actualTables = array_intersect($tables, $tableNames);
        
        $file = fopen($filepath, 'w');
        fwrite($file, "-- Incremental backup since {$since}\n");
        fwrite($file, "-- Generated at " . now() . "\n\n");
        
        $hasData = false;
        
        foreach ($actualTables as $table) {
            // Check if table has updated_at column
            $columns = DB::select("SHOW COLUMNS FROM `{$table}`");
            $columnNames = array_map(function($col) { return $col->Field; }, $columns);
            
            $query = DB::table($table);
            
            // Use appropriate date column
            if (in_array('updated_at', $columnNames)) {
                $query->where('updated_at', '>=', $since);
            } elseif (in_array('last_modified', $columnNames)) {
                $query->where('last_modified', '>=', $since);
            } elseif (in_array('created_at', $columnNames)) {
                $query->where('created_at', '>=', $since);
            } else {
                continue; // Skip tables without date columns
            }
            
            $rows = $query->get();
            
            if ($rows->count() > 0) {
                $hasData = true;
                fwrite($file, "\n-- Table: {$table} ({$rows->count()} rows)\n");
                fwrite($file, "DELETE FROM `{$table}` WHERE ");
                
                // Get primary key
                $primaryKey = 'id';
                foreach ($columns as $col) {
                    if ($col->Key === 'PRI') {
                        $primaryKey = $col->Field;
                        break;
                    }
                }
                
                // Write DELETE statements for updates
                $ids = $rows->pluck($primaryKey)->toArray();
                fwrite($file, "`{$primaryKey}` IN (" . implode(',', array_map(function($id) {
                    return is_numeric($id) ? $id : "'" . addslashes($id) . "'";
                }, $ids)) . ");\n");
                
                // Write INSERT statements
                foreach ($rows as $row) {
                    $rowArray = (array)$row;
                    $columns = array_keys($rowArray);
                    $values = array_map(function($value) {
                        if (is_null($value)) return 'NULL';
                        if (is_bool($value)) return $value ? '1' : '0';
                        if (is_numeric($value)) return $value;
                        return "'" . addslashes($value) . "'";
                    }, array_values($rowArray));
                    
                    $sql = "INSERT INTO `{$table}` (`" . implode('`, `', $columns) . "`) VALUES (" . implode(', ', $values) . ");\n";
                    fwrite($file, $sql);
                }
            }
        }
        
        if (!$hasData) {
            fwrite($file, "-- No changes since last backup\n");
        }
        
        fclose($file);
    }
    
    private function createCriticalBackup($filepath, $database, $username, $password, $host)
    {
        // Backup only critical tables
        $criticalTables = [
            'companies', 'branches', 'customers', 'appointments', 'calls',
            'staff', 'services', 'users', 'calcom_event_types'
        ];
        
        $command = sprintf(
            'mysqldump -h%s -u%s -p%s --single-transaction %s %s > %s 2>&1',
            escapeshellarg($host),
            escapeshellarg($username),
            escapeshellarg($password),
            escapeshellarg($database),
            implode(' ', $criticalTables),
            escapeshellarg($filepath)
        );
        
        exec($command, $output, $return);
        
        if ($return !== 0) {
            throw new \Exception("mysqldump failed: " . implode("\n", $output));
        }
    }
    
    private function compressBackup($filepath)
    {
        exec("gzip -9 {$filepath}", $output, $return);
        
        if ($return !== 0) {
            throw new \Exception("Compression failed");
        }
    }
    
    private function encryptBackup($filepath)
    {
        $key = config('app.key');
        $encrypted = $filepath . '.enc';
        
        exec("openssl enc -aes-256-cbc -salt -in {$filepath} -out {$encrypted} -k {$key}", $output, $return);
        
        if ($return !== 0) {
            throw new \Exception("Encryption failed");
        }
        
        unlink($filepath); // Remove unencrypted file
    }
    
    private function verifyBackup($filepath)
    {
        if (!file_exists($filepath)) {
            throw new \Exception("Backup file not found");
        }
        
        $size = filesize($filepath);
        if ($size < 1000) { // Less than 1KB is suspicious
            throw new \Exception("Backup file too small: {$size} bytes");
        }
        
        $this->info("Backup verified: " . round($size / 1024 / 1024, 2) . " MB");
    }
    
    private function cleanOldBackups($backupDir, $retention)
    {
        $cutoff = Carbon::now()->subDays($retention);
        
        $files = glob("{$backupDir}/askproai_*.sql*");
        $deleted = 0;
        
        foreach ($files as $file) {
            if (filemtime($file) < $cutoff->timestamp) {
                unlink($file);
                $deleted++;
            }
        }
        
        if ($deleted > 0) {
            $this->info("Cleaned {$deleted} old backup files");
        }
    }
    
    private function logBackup($type, $filepath, $status, $error = null)
    {
        DB::table('backup_logs')->insert([
            'type' => $type,
            'filepath' => $filepath,
            'size' => file_exists($filepath) ? filesize($filepath) : 0,
            'status' => $status,
            'error' => $error,
            'created_at' => now(),
        ]);
    }
    
    private function sendNotification($status, $filepath, $error = null)
    {
        // TODO: Implement email/webhook notification
        if ($status === 'failed') {
            \Log::critical('Database backup failed', [
                'filepath' => $filepath,
                'error' => $error
            ]);
        }
    }
}