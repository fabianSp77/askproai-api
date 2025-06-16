<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class CleanupBackupFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cleanup:backup-files 
                            {--dry-run : Show what would be deleted without actually deleting}
                            {--force : Force deletion without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up backup files (.bak, .backup, .old) from the codebase';

    /**
     * Patterns to match backup files
     */
    private array $backupPatterns = [
        '*.bak',
        '*.bak.*',
        '*.backup',
        '*.backup.*',
        '*.old',
        '*.orig',
        '*~',
        '*.tmp',
        '*.temp',
    ];

    /**
     * Directories to scan
     */
    private array $directories = [
        'app',
        'config',
        'database',
        'resources',
        'routes',
        'tests',
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🧹 Scanning for backup files...');
        
        $backupFiles = $this->findBackupFiles();
        
        if (empty($backupFiles)) {
            $this->info('✅ No backup files found!');
            return 0;
        }
        
        $this->warn('Found ' . count($backupFiles) . ' backup files:');
        $this->displayFiles($backupFiles);
        
        if ($this->option('dry-run')) {
            $this->info("\n🔍 Dry run mode - no files were deleted");
            return 0;
        }
        
        if (!$this->option('force') && !$this->confirm('Do you want to delete these files?')) {
            $this->info('Operation cancelled');
            return 0;
        }
        
        $this->deleteFiles($backupFiles);
        
        return 0;
    }

    /**
     * Find all backup files
     */
    private function findBackupFiles(): array
    {
        $files = [];
        
        foreach ($this->directories as $directory) {
            $path = base_path($directory);
            
            if (!is_dir($path)) {
                continue;
            }
            
            foreach ($this->backupPatterns as $pattern) {
                $found = File::glob($path . '/**/' . $pattern);
                $files = array_merge($files, $found);
            }
        }
        
        // Remove duplicates and sort
        $files = array_unique($files);
        sort($files);
        
        return $files;
    }

    /**
     * Display files in a table
     */
    private function displayFiles(array $files): void
    {
        $tableData = [];
        $totalSize = 0;
        
        foreach ($files as $file) {
            $size = filesize($file);
            $totalSize += $size;
            
            $tableData[] = [
                'file' => str_replace(base_path() . '/', '', $file),
                'size' => $this->formatBytes($size),
                'modified' => date('Y-m-d H:i:s', filemtime($file)),
            ];
        }
        
        $this->table(['File', 'Size', 'Last Modified'], $tableData);
        
        $this->info('Total size: ' . $this->formatBytes($totalSize));
    }

    /**
     * Delete files
     */
    private function deleteFiles(array $files): void
    {
        $this->info("\n🗑️  Deleting backup files...");
        
        $progressBar = $this->output->createProgressBar(count($files));
        $progressBar->start();
        
        $deleted = 0;
        $failed = [];
        
        foreach ($files as $file) {
            try {
                if (File::delete($file)) {
                    $deleted++;
                } else {
                    $failed[] = $file;
                }
            } catch (\Exception $e) {
                $failed[] = $file;
            }
            
            $progressBar->advance();
        }
        
        $progressBar->finish();
        $this->newLine(2);
        
        $this->info("✅ Deleted {$deleted} files");
        
        if (!empty($failed)) {
            $this->error('Failed to delete ' . count($failed) . ' files:');
            foreach ($failed as $file) {
                $this->error('  - ' . str_replace(base_path() . '/', '', $file));
            }
        }
    }

    /**
     * Format bytes to human readable
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}