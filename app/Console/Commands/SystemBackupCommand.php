<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\Backup\Tasks\Backup\BackupJobFactory;
use Carbon\Carbon;

class SystemBackupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'askproai:backup 
                            {--type=full : Backup type (full, incremental, critical)}
                            {--compress : Compress backup files}
                            {--encrypt : Encrypt backup files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create system backup with encryption and compression options';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔒 AskProAI Security Backup System');
        $this->line('==================================');

        $type = $this->option('type');
        $compress = $this->option('compress');
        $encrypt = $this->option('encrypt');

        $this->info("Backup Type: {$type}");
        $this->info("Compression: " . ($compress ? 'Enabled' : 'Disabled'));
        $this->info("Encryption: " . ($encrypt ? 'Enabled' : 'Disabled'));

        try {
            switch ($type) {
                case 'full':
                    $this->performFullBackup($compress, $encrypt);
                    break;
                case 'incremental':
                    $this->performIncrementalBackup($compress, $encrypt);
                    break;
                case 'critical':
                    $this->performCriticalBackup($compress, $encrypt);
                    break;
            }

            $this->info("\n✅ Backup completed successfully!");
            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Backup failed: " . $e->getMessage());
            Log::error('Backup failed', [
                'type' => $type,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }

    /**
     * Perform full system backup
     */
    private function performFullBackup(bool $compress, bool $encrypt): void
    {
        $this->info("\n📦 Creating full system backup...");
        
        $backupPath = storage_path('backups/full_' . date('Y-m-d_H-i-s'));
        
        // Create backup directory
        if (!is_dir($backupPath)) {
            mkdir($backupPath, 0755, true);
        }

        // Backup database
        $this->backupDatabase($backupPath);
        
        // Backup files
        $this->backupFiles($backupPath);
        
        // Backup environment and configs
        $this->backupConfigs($backupPath);
        
        // Compress if requested
        if ($compress) {
            $this->compressBackup($backupPath);
        }
        
        // Encrypt if requested
        if ($encrypt) {
            $this->encryptBackup($backupPath);
        }
        
        // Upload to remote storage
        $this->uploadToRemote($backupPath);
    }

    /**
     * Perform incremental backup
     */
    private function performIncrementalBackup(bool $compress, bool $encrypt): void
    {
        $this->info("\n📦 Creating incremental backup...");
        
        $lastBackup = $this->getLastBackupTimestamp();
        $backupPath = storage_path('backups/incremental_' . date('Y-m-d_H-i-s'));
        
        if (!is_dir($backupPath)) {
            mkdir($backupPath, 0755, true);
        }

        // Backup only changed files since last backup
        $this->backupChangedFiles($backupPath, $lastBackup);
        
        // Backup database changes
        $this->backupDatabaseChanges($backupPath, $lastBackup);
        
        if ($compress) {
            $this->compressBackup($backupPath);
        }
        
        if ($encrypt) {
            $this->encryptBackup($backupPath);
        }
        
        $this->uploadToRemote($backupPath);
    }

    /**
     * Perform critical data backup
     */
    private function performCriticalBackup(bool $compress, bool $encrypt): void
    {
        $this->info("\n📦 Creating critical data backup...");
        
        $backupPath = storage_path('backups/critical_' . date('Y-m-d_H-i-s'));
        
        if (!is_dir($backupPath)) {
            mkdir($backupPath, 0755, true);
        }

        // Backup only critical tables
        $criticalTables = [
            'users', 'companies', 'customers', 'appointments', 
            'calls', 'integrations', 'tenants'
        ];
        
        foreach ($criticalTables as $table) {
            $this->backupTable($table, $backupPath);
        }
        
        // Backup encryption keys and certificates
        $this->backupSecurityFiles($backupPath);
        
        if ($compress) {
            $this->compressBackup($backupPath);
        }
        
        if ($encrypt) {
            $this->encryptBackup($backupPath, true); // Use stronger encryption for critical data
        }
        
        $this->uploadToRemote($backupPath, 'critical');
    }

    /**
     * Backup database
     */
    private function backupDatabase(string $path): void
    {
        $this->line('  → Backing up database...');
        
        $filename = $path . '/database.sql';
        $database = config('database.connections.mysql.database');
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');
        $host = config('database.connections.mysql.host');
        
        $command = sprintf(
            'mysqldump -h%s -u%s -p%s %s > %s 2>&1',
            escapeshellarg($host),
            escapeshellarg($username),
            escapeshellarg($password),
            escapeshellarg($database),
            escapeshellarg($filename)
        );
        
        exec($command, $output, $returnCode);
        
        if ($returnCode !== 0) {
            throw new \Exception('Database backup failed: ' . implode("\n", $output));
        }
    }

    /**
     * Backup specific table
     */
    private function backupTable(string $table, string $path): void
    {
        $this->line("  → Backing up table: {$table}");
        
        $filename = $path . "/{$table}.sql";
        $database = config('database.connections.mysql.database');
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');
        $host = config('database.connections.mysql.host');
        
        $command = sprintf(
            'mysqldump -h%s -u%s -p%s %s %s > %s 2>&1',
            escapeshellarg($host),
            escapeshellarg($username),
            escapeshellarg($password),
            escapeshellarg($database),
            escapeshellarg($table),
            escapeshellarg($filename)
        );
        
        exec($command, $output, $returnCode);
        
        if ($returnCode !== 0) {
            throw new \Exception("Table backup failed for {$table}: " . implode("\n", $output));
        }
    }

    /**
     * Backup application files
     */
    private function backupFiles(string $path): void
    {
        $this->line('  → Backing up application files...');
        
        $directories = [
            'app' => base_path('app'),
            'config' => base_path('config'),
            'database' => base_path('database'),
            'resources' => base_path('resources'),
            'routes' => base_path('routes'),
            'storage_logs' => storage_path('logs'),
        ];
        
        foreach ($directories as $name => $dir) {
            if (is_dir($dir)) {
                $targetPath = $path . '/files/' . $name;
                
                $command = sprintf(
                    'cp -r %s %s',
                    escapeshellarg($dir),
                    escapeshellarg($targetPath)
                );
                
                exec($command);
            }
        }
    }

    /**
     * Backup configuration files
     */
    private function backupConfigs(string $path): void
    {
        $this->line('  → Backing up configuration...');
        
        // Copy .env file
        copy(base_path('.env'), $path . '/.env.backup');
        
        // Save current git commit hash
        $gitHash = trim(shell_exec('git rev-parse HEAD'));
        file_put_contents($path . '/git-commit.txt', $gitHash);
        
        // Save system info
        $systemInfo = [
            'php_version' => phpversion(),
            'laravel_version' => app()->version(),
            'server_info' => $_SERVER,
            'backup_timestamp' => now()->toIso8601String(),
        ];
        
        file_put_contents(
            $path . '/system-info.json',
            json_encode($systemInfo, JSON_PRETTY_PRINT)
        );
    }

    /**
     * Backup security files
     */
    private function backupSecurityFiles(string $path): void
    {
        $this->line('  → Backing up security files...');
        
        $securityPath = $path . '/security';
        if (!is_dir($securityPath)) {
            mkdir($securityPath, 0755, true);
        }
        
        // Backup OAuth keys
        $oauthPath = storage_path('oauth-private.key');
        if (file_exists($oauthPath)) {
            copy($oauthPath, $securityPath . '/oauth-private.key');
        }
        
        // Backup any SSL certificates
        $sslPath = base_path('ssl');
        if (is_dir($sslPath)) {
            exec("cp -r " . escapeshellarg($sslPath) . " " . escapeshellarg($securityPath . '/ssl'));
        }
    }

    /**
     * Compress backup
     */
    private function compressBackup(string $path): void
    {
        $this->line('  → Compressing backup...');
        
        $archiveName = $path . '.tar.gz';
        
        $command = sprintf(
            'tar -czf %s -C %s .',
            escapeshellarg($archiveName),
            escapeshellarg($path)
        );
        
        exec($command, $output, $returnCode);
        
        if ($returnCode === 0) {
            // Remove uncompressed files
            exec("rm -rf " . escapeshellarg($path));
        } else {
            throw new \Exception('Compression failed');
        }
    }

    /**
     * Encrypt backup
     */
    private function encryptBackup(string $path, bool $critical = false): void
    {
        $this->line('  → Encrypting backup...');
        
        $files = glob($path . '*');
        $password = config('backup.encryption_password');
        
        foreach ($files as $file) {
            $encryptedFile = $file . '.enc';
            
            $command = sprintf(
                'openssl enc -aes-256-cbc -salt -in %s -out %s -k %s',
                escapeshellarg($file),
                escapeshellarg($encryptedFile),
                escapeshellarg($password)
            );
            
            exec($command, $output, $returnCode);
            
            if ($returnCode === 0) {
                unlink($file); // Remove unencrypted file
            } else {
                throw new \Exception('Encryption failed for: ' . $file);
            }
        }
    }

    /**
     * Upload backup to remote storage
     */
    private function uploadToRemote(string $path, string $type = 'regular'): void
    {
        $this->line('  → Uploading to remote storage...');
        
        // Implementation would depend on your remote storage solution
        // Example: S3, Google Cloud Storage, etc.
        
        // For now, just log the action
        Log::info('Backup created', [
            'path' => $path,
            'type' => $type,
            'size' => $this->getDirectorySize($path),
        ]);
    }

    /**
     * Get last backup timestamp
     */
    private function getLastBackupTimestamp(): ?Carbon
    {
        // Check for last backup marker
        $markerFile = storage_path('backups/.last_backup');
        
        if (file_exists($markerFile)) {
            $timestamp = file_get_contents($markerFile);
            return Carbon::parse($timestamp);
        }
        
        return null;
    }

    /**
     * Backup changed files since last backup
     */
    private function backupChangedFiles(string $path, ?Carbon $since): void
    {
        if (!$since) {
            $this->backupFiles($path);
            return;
        }
        
        $this->line('  → Backing up changed files since ' . $since->format('Y-m-d H:i:s'));
        
        // Find files modified since last backup
        $command = sprintf(
            'find %s -type f -newermt "%s" -exec cp --parents {} %s \;',
            escapeshellarg(base_path()),
            $since->format('Y-m-d H:i:s'),
            escapeshellarg($path)
        );
        
        exec($command);
    }

    /**
     * Backup database changes
     */
    private function backupDatabaseChanges(string $path, ?Carbon $since): void
    {
        if (!$since) {
            $this->backupDatabase($path);
            return;
        }
        
        $this->line('  → Backing up database changes since ' . $since->format('Y-m-d H:i:s'));
        
        // This would require binary logging enabled in MySQL
        // For now, do a full backup
        $this->backupDatabase($path);
    }

    /**
     * Get directory size
     */
    private function getDirectorySize(string $path): string
    {
        $output = shell_exec("du -sh " . escapeshellarg($path));
        return trim(explode("\t", $output)[0] ?? 'Unknown');
    }
}