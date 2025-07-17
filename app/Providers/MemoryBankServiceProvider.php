<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\MemoryBankAutomationService;
use App\Services\MCP\MemoryBankMCPServer;

class MemoryBankServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register MemoryBankMCPServer as singleton
        $this->app->singleton(MemoryBankMCPServer::class, function ($app) {
            return new MemoryBankMCPServer();
        });

        // Register MemoryBankAutomationService as singleton
        $this->app->singleton(MemoryBankAutomationService::class, function ($app) {
            return new MemoryBankAutomationService(
                $app->make(MemoryBankMCPServer::class)
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Create storage directories if they don't exist
        $storagePaths = [
            storage_path('app/mcp/memory-bank'),
            storage_path('app/exports')
        ];

        foreach ($storagePaths as $path) {
            if (!is_dir($path)) {
                mkdir($path, 0755, true);
            }
        }
    }
}