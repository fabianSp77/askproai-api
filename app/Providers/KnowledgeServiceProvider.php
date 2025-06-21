<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\KnowledgeBaseService;
use App\Services\FileWatcherService;

class KnowledgeServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register KnowledgeBaseService as singleton
        $this->app->singleton(KnowledgeBaseService::class, function ($app) {
            return new KnowledgeBaseService();
        });
        
        // Register FileWatcherService as singleton
        $this->app->singleton(FileWatcherService::class, function ($app) {
            return new FileWatcherService($app->make(KnowledgeBaseService::class));
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Load configuration
        $this->mergeConfigFrom(
            __DIR__.'/../../config/knowledge.php', 'knowledge'
        );
        
        // Schedule file watcher if enabled
        if (config('knowledge.auto_index.enabled', false)) {
            $this->app->booted(function () {
                $schedule = $this->app->make(\Illuminate\Console\Scheduling\Schedule::class);
                $schedule->call(function () {
                    $watcher = app(FileWatcherService::class);
                    $watcher->checkForChanges();
                    $watcher->setLastCheck();
                })->everyMinute()->name('knowledge:watch')->withoutOverlapping();
            });
        }
    }
}