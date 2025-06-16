<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;

class CleanupLegacyCode extends Command
{
    protected $signature = 'cleanup:legacy 
                            {--dry-run : Show what would be cleaned without actually doing it}
                            {--force : Force cleanup without confirmation}';
    
    protected $description = 'Clean up legacy code, old migrations, and technical debt';
    
    private array $stats = [
        'files_removed' => 0,
        'migrations_cleaned' => 0,
        'models_updated' => 0,
        'disk_space_freed' => 0,
    ];
    
    public function handle()
    {
        $this->info('🧹 Starting Legacy Code Cleanup...');
        
        if (!$this->option('force') && !$this->option('dry-run')) {
            if (!$this->confirm('This will remove legacy code and files. Continue?')) {
                return 0;
            }
        }
        
        // 1. Clean up disabled migrations
        $this->cleanupDisabledMigrations();
        
        // 2. Remove temporary model files
        $this->cleanupTempModelFiles();
        
        // 3. Clean up broken files
        $this->cleanupBrokenFiles();
        
        // 4. Remove duplicate resources
        $this->cleanupDuplicateResources();
        
        // 5. Clean up old log files
        $this->cleanupOldLogs();
        
        // 6. Show summary
        $this->showSummary();
        
        return 0;
    }
    
    private function cleanupDisabledMigrations(): void
    {
        $this->info('Cleaning up disabled migrations...');
        
        $disabledPath = database_path('migrations/_disabled');
        if (!File::isDirectory($disabledPath)) {
            $this->warn('No disabled migrations found.');
            return;
        }
        
        $files = File::files($disabledPath);
        foreach ($files as $file) {
            $size = $file->getSize();
            if ($this->shouldRemoveFile($file->getPathname())) {
                $this->stats['migrations_cleaned']++;
                $this->stats['disk_space_freed'] += $size;
            }
        }
        
        // Remove the directory if empty
        if ($this->option('dry-run')) {
            $this->info('Would remove empty _disabled directory');
        } else {
            if (count(File::files($disabledPath)) === 0) {
                File::deleteDirectory($disabledPath);
            }
        }
    }
    
    private function cleanupTempModelFiles(): void
    {
        $this->info('Cleaning up temporary model files...');
        
        $patterns = [
            'app/Models/*.temp',
            'app/Models/*.old',
            'app/Models/*.broken',
        ];
        
        foreach ($patterns as $pattern) {
            foreach (glob($pattern) as $file) {
                $size = filesize($file);
                if ($this->shouldRemoveFile($file)) {
                    $this->stats['models_updated']++;
                    $this->stats['disk_space_freed'] += $size;
                }
            }
        }
    }
    
    private function cleanupBrokenFiles(): void
    {
        $this->info('Cleaning up broken files...');
        
        $patterns = [
            'app/**/*.broken*',
            'app/**/*.BROKEN.*',
            'config/*.BROKEN.*',
            'database/**/*.BROKEN.*',
        ];
        
        foreach ($patterns as $pattern) {
            foreach (glob($pattern, GLOB_BRACE) as $file) {
                $size = filesize($file);
                if ($this->shouldRemoveFile($file)) {
                    $this->stats['files_removed']++;
                    $this->stats['disk_space_freed'] += $size;
                }
            }
        }
    }
    
    private function cleanupDuplicateResources(): void
    {
        $this->info('Cleaning up duplicate resources...');
        
        // Remove backup directories
        $backupDirs = [
            'app/Filament/Admin/Resources/StaffResource.backup',
            'storage/old-backups',
            'backups/v2_migration_20250601_134348',
        ];
        
        foreach ($backupDirs as $dir) {
            if (File::isDirectory($dir)) {
                $size = $this->getDirectorySize($dir);
                if ($this->option('dry-run')) {
                    $this->info("Would remove directory: $dir (Size: " . $this->formatBytes($size) . ")");
                } else {
                    File::deleteDirectory($dir);
                    $this->info("Removed directory: $dir");
                    $this->stats['disk_space_freed'] += $size;
                    $this->stats['files_removed']++;
                }
            }
        }
    }
    
    private function cleanupOldLogs(): void
    {
        $this->info('Cleaning up old log files...');
        
        $logPath = storage_path('logs');
        $oldLogs = File::glob($logPath . '/*.log.1');
        
        foreach ($oldLogs as $log) {
            // Keep logs from last 7 days
            if (filemtime($log) < strtotime('-7 days')) {
                $size = filesize($log);
                if ($this->shouldRemoveFile($log)) {
                    $this->stats['files_removed']++;
                    $this->stats['disk_space_freed'] += $size;
                }
            }
        }
    }
    
    private function shouldRemoveFile(string $file): bool
    {
        if ($this->option('dry-run')) {
            $this->info("Would remove: $file (" . $this->formatBytes(filesize($file)) . ")");
            return true;
        }
        
        File::delete($file);
        $this->info("Removed: $file");
        return true;
    }
    
    private function getDirectorySize(string $path): int
    {
        $size = 0;
        foreach (File::allFiles($path) as $file) {
            $size += $file->getSize();
        }
        return $size;
    }
    
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes > 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
    
    private function showSummary(): void
    {
        $this->newLine();
        $this->info('=== Cleanup Summary ===');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Files Removed', $this->stats['files_removed']],
                ['Migrations Cleaned', $this->stats['migrations_cleaned']],
                ['Models Updated', $this->stats['models_updated']],
                ['Disk Space Freed', $this->formatBytes($this->stats['disk_space_freed'])],
            ]
        );
        
        if ($this->option('dry-run')) {
            $this->warn('This was a dry run. No files were actually removed.');
        }
    }
}