<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class BackupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:backup
                            {--type=full : Backup type: full, database, files}
                            {--schedule=manual : Schedule type: daily, weekly, monthly, manual}
                            {--compress : Compress the backup to tar.gz}
                            {--cleanup : Remove old backups based on retention policy}
                            {--test : Test mode - verify backup process without full execution}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a comprehensive backup of the application (database, files, configs)';

    /**
     * Backup configuration
     */
    protected $backupRoot = '/var/www/backups';
    protected $retentionDays = [
        'daily' => 7,
        'weekly' => 28,
        'monthly' => 365,
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $type = $this->option('type');
        $schedule = $this->option('schedule');
        $compress = $this->option('compress');
        $cleanup = $this->option('cleanup');
        $testMode = $this->option('test');

        $this->info('🔐 API Gateway Backup System');
        $this->info('═══════════════════════════════════════════');

        if ($testMode) {
            $this->warn('Running in TEST MODE - no actual backup will be created');
            return $this->runTestMode();
        }

        // Start backup process
        $startTime = microtime(true);
        $timestamp = Carbon::now()->format('Ymd_His');
        $backupDir = "{$this->backupRoot}/{$schedule}-backup-{$timestamp}";

        try {
            // Create backup directory
            if (!is_dir($this->backupRoot)) {
                mkdir($this->backupRoot, 0755, true);
            }

            $this->info("📁 Creating backup: {$backupDir}");

            // Execute appropriate backup based on type
            $result = match($type) {
                'database' => $this->backupDatabase($backupDir, $timestamp),
                'files' => $this->backupFiles($backupDir, $timestamp),
                'full' => $this->backupFull($backupDir, $timestamp),
                default => throw new \Exception("Invalid backup type: {$type}")
            };

            if (!$result) {
                throw new \Exception('Backup process failed');
            }

            // Compress if requested
            if ($compress || $schedule !== 'manual') {
                $this->compressBackup($backupDir, $timestamp);
            }

            // Cleanup old backups if requested
            if ($cleanup) {
                $this->cleanupOldBackups($schedule);
            }

            // Calculate execution time
            $executionTime = round(microtime(true) - $startTime, 2);

            // Log success
            Log::info('Backup completed successfully', [
                'type' => $type,
                'schedule' => $schedule,
                'directory' => $backupDir,
                'execution_time' => $executionTime
            ]);

            $this->info("✅ Backup completed successfully in {$executionTime} seconds");
            $this->info("📍 Location: {$backupDir}");

            // Show backup size
            $size = $this->getDirectorySize($backupDir);
            $this->info("📊 Size: {$size}");

            return Command::SUCCESS;

        } catch (\Exception $e) {
            Log::error('Backup failed', [
                'error' => $e->getMessage(),
                'type' => $type,
                'schedule' => $schedule
            ]);

            $this->error('❌ Backup failed: ' . $e->getMessage());

            // Cleanup failed backup
            if (is_dir($backupDir)) {
                exec("rm -rf {$backupDir}");
            }

            return Command::FAILURE;
        }
    }

    /**
     * Run test mode to verify backup system
     */
    protected function runTestMode()
    {
        $this->info('🧪 Running backup system tests...');

        $tests = [
            'Database connection' => $this->testDatabaseConnection(),
            'Backup directory writable' => $this->testBackupDirectory(),
            'Required commands available' => $this->testRequiredCommands(),
            'Disk space sufficient' => $this->testDiskSpace(),
            'Backup script exists' => $this->testBackupScript(),
        ];

        $allPassed = true;
        foreach ($tests as $test => $result) {
            if ($result) {
                $this->info("  ✓ {$test}");
            } else {
                $this->error("  ✗ {$test}");
                $allPassed = false;
            }
        }

        if ($allPassed) {
            $this->info('✅ All tests passed! Backup system is ready.');
            return Command::SUCCESS;
        } else {
            $this->error('❌ Some tests failed. Please fix the issues before running backup.');
            return Command::FAILURE;
        }
    }

    /**
     * Backup database only
     */
    protected function backupDatabase($backupDir, $timestamp)
    {
        $this->info('💾 Backing up database...');

        // Use existing backup script for database
        $script = base_path('scripts/create-full-backup.sh');
        if (!file_exists($script)) {
            // Fallback to direct mysqldump
            mkdir("{$backupDir}/database", 0755, true);

            $dbName = config('database.connections.mysql.database');
            $dbUser = config('database.connections.mysql.username');
            $dbPass = config('database.connections.mysql.password');
            $dbHost = config('database.connections.mysql.host');

            $command = sprintf(
                'mysqldump -h %s -u %s %s --single-transaction --routines --triggers --events %s > %s/database/%s.sql 2>&1',
                escapeshellarg($dbHost),
                escapeshellarg($dbUser),
                $dbPass ? '-p' . escapeshellarg($dbPass) : '',
                escapeshellarg($dbName),
                escapeshellarg($backupDir),
                escapeshellarg($dbName)
            );

            exec($command, $output, $returnCode);

            if ($returnCode !== 0) {
                throw new \Exception('Database backup failed: ' . implode("\n", $output));
            }
        } else {
            // Use existing script
            exec("bash {$script} --database-only --output={$backupDir} 2>&1", $output, $returnCode);
            if ($returnCode !== 0) {
                throw new \Exception('Database backup script failed');
            }
        }

        $this->info('  ✓ Database backed up');
        return true;
    }

    /**
     * Backup files only
     */
    protected function backupFiles($backupDir, $timestamp)
    {
        $this->info('📁 Backing up application files...');

        mkdir("{$backupDir}/application", 0755, true);

        // Backup application files
        $excludes = [
            '--exclude=vendor',
            '--exclude=node_modules',
            '--exclude=storage/logs/*',
            '--exclude=storage/framework/cache/*',
            '--exclude=storage/framework/sessions/*',
            '--exclude=storage/framework/views/*',
            '--exclude=bootstrap/cache/*',
            '--exclude=.git',
        ];

        $command = sprintf(
            'rsync -av %s %s %s 2>&1',
            implode(' ', $excludes),
            base_path() . '/',
            "{$backupDir}/application/"
        );

        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            throw new \Exception('File backup failed');
        }

        // Backup composer.json and package.json for dependency reference
        copy(base_path('composer.json'), "{$backupDir}/application/composer.json");
        copy(base_path('composer.lock'), "{$backupDir}/application/composer.lock");
        if (file_exists(base_path('package.json'))) {
            copy(base_path('package.json'), "{$backupDir}/application/package.json");
        }

        $this->info('  ✓ Application files backed up');
        return true;
    }

    /**
     * Full backup using existing script
     */
    protected function backupFull($backupDir, $timestamp)
    {
        $this->info('🔄 Creating full system backup...');

        $script = base_path('scripts/create-full-backup.sh');

        if (!file_exists($script)) {
            // Create both database and files backup
            $this->backupDatabase($backupDir, $timestamp);
            $this->backupFiles($backupDir, $timestamp);

            // Also backup configs
            $this->backupConfigs($backupDir);

            return true;
        }

        // Execute the existing comprehensive backup script
        $command = "bash {$script} 2>&1";

        $this->info('  Executing backup script...');
        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            throw new \Exception('Backup script failed: ' . implode("\n", array_slice($output, -5)));
        }

        // The script creates its own directory, so we need to find it
        $latestBackup = $this->findLatestBackup();
        if ($latestBackup && $latestBackup !== $backupDir) {
            // Move to our expected location
            rename($latestBackup, $backupDir);
        }

        $this->info('  ✓ Full backup completed');
        return true;
    }

    /**
     * Backup configuration files
     */
    protected function backupConfigs($backupDir)
    {
        $this->info('⚙️  Backing up configurations...');

        mkdir("{$backupDir}/configs", 0755, true);

        // Backup .env file (secured)
        if (file_exists(base_path('.env'))) {
            copy(base_path('.env'), "{$backupDir}/configs/.env");
            chmod("{$backupDir}/configs/.env", 0600);
        }

        // Backup nginx configs if accessible
        if (is_readable('/etc/nginx/sites-available')) {
            exec("cp -r /etc/nginx/sites-available {$backupDir}/configs/nginx-sites 2>/dev/null");
        }

        // Create system info file
        $systemInfo = [
            'backup_date' => Carbon::now()->toIso8601String(),
            'laravel_version' => app()->version(),
            'php_version' => PHP_VERSION,
            'server' => php_uname(),
            'disk_usage' => disk_free_space('/') / 1024 / 1024 / 1024 . ' GB free',
        ];

        file_put_contents(
            "{$backupDir}/configs/system_info.json",
            json_encode($systemInfo, JSON_PRETTY_PRINT)
        );

        $this->info('  ✓ Configurations backed up');
    }

    /**
     * Compress backup directory
     */
    protected function compressBackup($backupDir, $timestamp)
    {
        $this->info('📦 Compressing backup...');

        $archiveName = basename($backupDir) . '.tar.gz';
        $archivePath = dirname($backupDir) . '/' . $archiveName;

        $command = sprintf(
            'cd %s && tar -czf %s %s 2>&1',
            escapeshellarg(dirname($backupDir)),
            escapeshellarg($archiveName),
            escapeshellarg(basename($backupDir))
        );

        exec($command, $output, $returnCode);

        if ($returnCode === 0) {
            // Remove uncompressed directory
            exec("rm -rf {$backupDir}");
            $this->info("  ✓ Compressed to: {$archivePath}");
        } else {
            $this->warn('  ⚠ Compression failed, keeping uncompressed backup');
        }
    }

    /**
     * Clean up old backups based on retention policy
     */
    protected function cleanupOldBackups($schedule)
    {
        $this->info('🧹 Cleaning up old backups...');

        $retentionDays = $this->retentionDays[$schedule] ?? 30;
        $cutoffDate = Carbon::now()->subDays($retentionDays);

        $backups = glob("{$this->backupRoot}/{$schedule}-backup-*");
        $removedCount = 0;

        foreach ($backups as $backup) {
            // Extract timestamp from filename
            if (preg_match('/(\d{8}_\d{6})/', basename($backup), $matches)) {
                $backupDate = Carbon::createFromFormat('Ymd_His', $matches[1]);

                if ($backupDate->lt($cutoffDate)) {
                    if (is_dir($backup)) {
                        exec("rm -rf {$backup}");
                    } else {
                        unlink($backup);
                    }
                    $removedCount++;
                }
            }
        }

        if ($removedCount > 0) {
            $this->info("  ✓ Removed {$removedCount} old backups");
        } else {
            $this->info('  ✓ No old backups to remove');
        }
    }

    /**
     * Find the latest backup directory
     */
    protected function findLatestBackup()
    {
        $backups = glob("{$this->backupRoot}/full-backup-*");
        if (empty($backups)) {
            return null;
        }

        // Sort by modification time
        usort($backups, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });

        return $backups[0];
    }

    /**
     * Get directory size in human-readable format
     */
    protected function getDirectorySize($dir)
    {
        $output = shell_exec("du -sh {$dir} 2>/dev/null");
        if ($output) {
            $parts = explode("\t", trim($output));
            return $parts[0] ?? 'Unknown';
        }
        return 'Unknown';
    }

    /**
     * Test database connection
     */
    protected function testDatabaseConnection()
    {
        try {
            \DB::connection()->getPdo();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Test backup directory is writable
     */
    protected function testBackupDirectory()
    {
        if (!is_dir($this->backupRoot)) {
            return mkdir($this->backupRoot, 0755, true);
        }
        return is_writable($this->backupRoot);
    }

    /**
     * Test required commands are available
     */
    protected function testRequiredCommands()
    {
        $commands = ['mysqldump', 'tar', 'rsync'];
        foreach ($commands as $command) {
            exec("which {$command} 2>/dev/null", $output, $returnCode);
            if ($returnCode !== 0) {
                return false;
            }
        }
        return true;
    }

    /**
     * Test disk space (need at least 1GB free)
     */
    protected function testDiskSpace()
    {
        $freeSpace = disk_free_space('/');
        return $freeSpace > (1024 * 1024 * 1024); // 1GB in bytes
    }

    /**
     * Test if backup script exists
     */
    protected function testBackupScript()
    {
        return file_exists(base_path('scripts/create-full-backup.sh'));
    }
}