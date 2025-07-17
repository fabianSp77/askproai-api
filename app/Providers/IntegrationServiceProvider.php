<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\GitHubNotionIntegrationService;
use App\Services\MCP\GitHubMCPServer;
use App\Services\MCP\NotionMCPServer;
use App\Services\MemoryBankAutomationService;

class IntegrationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register GitHub-Notion Integration Service
        $this->app->singleton(GitHubNotionIntegrationService::class, function ($app) {
            return new GitHubNotionIntegrationService(
                $app->make(GitHubMCPServer::class),
                $app->make(NotionMCPServer::class),
                $app->make(MemoryBankAutomationService::class)
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Add GitHub-Notion sync to scheduler if enabled
        if (config('integrations.github_notion.auto_sync', false)) {
            $this->app->booted(function () {
                $schedule = $this->app->make(\Illuminate\Console\Scheduling\Schedule::class);
                
                // Sync GitHub issues every 15 minutes
                $schedule->command('github-notion:sync issues --auto')
                    ->everyFifteenMinutes()
                    ->withoutOverlapping()
                    ->runInBackground()
                    ->appendOutputTo(storage_path('logs/github-notion-sync.log'));
                
                // Sync PRs every 30 minutes
                $schedule->command('github-notion:sync prs --auto')
                    ->everyThirtyMinutes()
                    ->withoutOverlapping()
                    ->runInBackground()
                    ->appendOutputTo(storage_path('logs/github-notion-sync.log'));
                
                // Sync releases daily
                $schedule->command('github-notion:sync releases --auto')
                    ->daily()
                    ->at('09:00')
                    ->withoutOverlapping()
                    ->runInBackground()
                    ->appendOutputTo(storage_path('logs/github-notion-sync.log'));
            });
        }
    }
}