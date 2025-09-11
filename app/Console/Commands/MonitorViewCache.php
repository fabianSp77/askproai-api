<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MonitorViewCache extends Command
{
    protected $signature = 'view:monitor {--fix : Automatically fix issues}';
    protected $description = 'Monitor and fix view cache issues';

    public function handle()
    {
        $viewPath = config('view.compiled');
        $issues = [];
        
        // Check if directory exists
        if (!File::isDirectory($viewPath)) {
            $issues[] = "View cache directory does not exist: $viewPath";
            if ($this->option('fix')) {
                File::makeDirectory($viewPath, 0775, true);
                $this->info("✓ Created view cache directory");
            }
        }
        
        // Check permissions
        if (File::isDirectory($viewPath) && !File::isWritable($viewPath)) {
            $issues[] = "View cache directory is not writable: $viewPath";
            if ($this->option('fix')) {
                exec("chown -R www-data:www-data $viewPath");
                exec("chmod -R 775 $viewPath");
                $this->info("✓ Fixed view cache permissions");
            }
        }
        
        // Check for orphaned files
        if (File::isDirectory($viewPath)) {
            $files = File::files($viewPath);
            $orphaned = 0;
            
            foreach ($files as $file) {
                $mtime = $file->getMTime();
                $age = time() - $mtime;
                
                // Files older than 7 days are considered orphaned
                if ($age > 604800) {
                    $orphaned++;
                    if ($this->option('fix')) {
                        File::delete($file);
                    }
                }
            }
            
            if ($orphaned > 0) {
                $issues[] = "Found $orphaned orphaned view cache files";
                if ($this->option('fix')) {
                    $this->info("✓ Removed $orphaned orphaned files");
                }
            }
        }
        
        // Check storage permissions
        $storagePath = storage_path();
        if (!File::isWritable($storagePath)) {
            $issues[] = "Storage directory is not writable: $storagePath";
            if ($this->option('fix')) {
                exec("chown -R www-data:www-data $storagePath");
                exec("chmod -R 775 $storagePath");
                $this->info("✓ Fixed storage permissions");
            }
        }
        
        // Report results
        if (empty($issues)) {
            $this->info('✓ View cache system is healthy');
            return 0;
        }
        
        $this->error('View cache issues detected:');
        foreach ($issues as $issue) {
            $this->line("  - $issue");
        }
        
        if (!$this->option('fix')) {
            $this->warn('Run with --fix option to automatically resolve these issues');
        }
        
        return 1;
    }
}