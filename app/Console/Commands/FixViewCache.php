<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class FixViewCache extends Command
{
    protected $signature = 'view:fix-cache';
    protected $description = 'Fix persistent view cache issues';

    public function handle()
    {
        $this->info('Fixing view cache issues...');
        
        // Clear all view cache
        $viewPath = storage_path('framework/views');
        
        if (File::exists($viewPath)) {
            File::cleanDirectory($viewPath);
            $this->info('Cleared view cache directory');
        }
        
        // Clear bootstrap cache
        $bootstrapPath = base_path('bootstrap/cache');
        foreach (glob($bootstrapPath . '/*.php') as $file) {
            unlink($file);
        }
        $this->info('Cleared bootstrap cache');
        
        // Clear all Laravel caches
        $this->call('optimize:clear');
        $this->call('config:cache');
        
        // Ensure proper permissions
        exec('chown -R www-data:www-data ' . storage_path());
        exec('chmod -R 775 ' . $viewPath);
        
        $this->info('View cache fixed successfully!');
        
        return 0;
    }
}