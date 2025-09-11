<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\View\FileViewFinder;

class ViewServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // DISABLED: Auto-fix logic caused ownership issues when run as root
        // The view composer below was creating root-owned files when triggered
        // by cron jobs, causing cascading failures.
        // 
        // View::composer('*', function ($view) {
        //     // This logic has been disabled to prevent ownership issues
        //     // Laravel's built-in view compilation is sufficient
        // });
        
        // Monitor view cache directory
        $this->ensureViewCacheDirectory();
    }
    
    /**
     * Ensure view cache directory exists with proper permissions
     */
    protected function ensureViewCacheDirectory(): void
    {
        $viewPath = storage_path('framework/views');
        
        if (!is_dir($viewPath)) {
            mkdir($viewPath, 0775, true);
            chown($viewPath, 'www-data');
            chgrp($viewPath, 'www-data');
        }
        
        // Ensure .gitignore exists
        $gitignore = $viewPath . '/.gitignore';
        if (!file_exists($gitignore)) {
            file_put_contents($gitignore, "*\n!.gitignore\n");
        }
    }
}