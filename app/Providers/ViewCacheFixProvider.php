<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\View\FileViewFinder;
use Illuminate\Support\Facades\View;

class ViewCacheFixProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Override view finder to handle missing cache files gracefully
        $this->app->bind('view.finder', function ($app) {
            return new FileViewFinder(
                $app['files'],
                $app['config']['view.paths'],
                ['blade.php', 'php', 'css', 'html']
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Ensure view cache directory exists
        $viewPath = storage_path('framework/views');
        if (!is_dir($viewPath)) {
            mkdir($viewPath, 0775, true);
            chown($viewPath, 'www-data');
            chgrp($viewPath, 'www-data');
        }

        // Clear stale view cache on each request in production
        if (app()->environment('production')) {
            $this->clearStaleViewCache();
        }

        // Add view composer to handle Filament/Livewire conflicts
        View::composer('*', function ($view) {
            // Ensure Livewire scripts are properly loaded
            if (!isset($view->livewireScripts)) {
                $view->with('livewireScripts', '');
            }
        });
    }

    /**
     * Clear stale view cache files
     */
    private function clearStaleViewCache(): void
    {
        $viewPath = storage_path('framework/views');
        $files = glob($viewPath . '/*.php');

        if ($files) {
            $now = time();
            foreach ($files as $file) {
                // Remove files older than 24 hours
                if ($now - filemtime($file) > 86400) {
                    @unlink($file);
                }
            }
        }
    }
}