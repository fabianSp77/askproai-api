<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\MCP\ExternalMCPManager;

class MCPExternalServers extends Command
{
    protected $signature = 'mcp:external 
                            {action : start|stop|restart|status|health} 
                            {server? : Server name (optional)}';
    
    protected $description = 'Manage external MCP servers (sequential-thinking, postgres, effect-docs, taskmaster-ai)';

    protected ExternalMCPManager $manager;

    public function __construct(ExternalMCPManager $manager)
    {
        parent::__construct();
        $this->manager = $manager;
    }

    public function handle()
    {
        $action = $this->argument('action');
        $server = $this->argument('server');

        switch ($action) {
            case 'start':
                $this->handleStart($server);
                break;

            case 'stop':
                $this->handleStop($server);
                break;

            case 'restart':
                $this->handleRestart($server);
                break;

            case 'status':
                $this->handleStatus();
                break;

            case 'health':
                $this->handleHealth();
                break;

            default:
                $this->error("Invalid action. Use: start, stop, restart, status, or health");
                return 1;
        }

        return 0;
    }

    protected function handleStart(?string $server): void
    {
        if ($server) {
            $this->info("Starting {$server}...");
            
            if ($this->manager->startServer($server)) {
                $this->info("✓ Successfully started {$server}");
            } else {
                $this->error("✗ Failed to start {$server}");
            }
        } else {
            $this->info("Starting all enabled external MCP servers...");
            $results = $this->manager->startAll();
            
            foreach ($results as $name => $success) {
                if ($success) {
                    $this->info("✓ Started {$name}");
                } else {
                    $this->error("✗ Failed to start {$name}");
                }
            }
        }
    }

    protected function handleStop(?string $server): void
    {
        if ($server) {
            $this->info("Stopping {$server}...");
            
            if ($this->manager->stopServer($server)) {
                $this->info("✓ Successfully stopped {$server}");
            } else {
                $this->error("✗ Failed to stop {$server}");
            }
        } else {
            $this->info("Stopping all external MCP servers...");
            $this->manager->stopAll();
            $this->info("✓ All servers stopped");
        }
    }

    protected function handleRestart(?string $server): void
    {
        if ($server) {
            $this->info("Restarting {$server}...");
            
            if ($this->manager->restartServer($server)) {
                $this->info("✓ Successfully restarted {$server}");
            } else {
                $this->error("✗ Failed to restart {$server}");
            }
        } else {
            $this->error("Please specify a server to restart");
        }
    }

    protected function handleStatus(): void
    {
        $status = $this->manager->getStatus();
        
        if (empty($status)) {
            $this->warn("No external MCP servers configured");
            return;
        }

        $this->info("External MCP Server Status:");
        $this->newLine();

        $headers = ['Server', 'Enabled', 'Status', 'PID', 'Description'];
        $rows = [];

        foreach ($status as $name => $info) {
            $rows[] = [
                $name,
                $info['enabled'] ? '<fg=green>✓</>' : '<fg=red>✗</>',
                $info['running'] 
                    ? '<fg=green>Running</>' 
                    : ($info['enabled'] ? '<fg=yellow>Stopped</>' : '<fg=gray>Disabled</>'),
                $info['pid'] ?? '-',
                \Str::limit($info['description'], 40),
            ];
        }

        $this->table($headers, $rows);

        // Show instructions if any servers are not running
        $stoppedServers = collect($status)
            ->filter(fn($info) => $info['enabled'] && !$info['running'])
            ->keys();

        if ($stoppedServers->isNotEmpty()) {
            $this->newLine();
            $this->warn("Some enabled servers are not running. Start them with:");
            $this->line("  php artisan mcp:external start");
        }
    }

    protected function handleHealth(): void
    {
        $this->info("Running health check on external MCP servers...");
        $health = $this->manager->healthCheck();

        $this->newLine();
        $this->info("Health Check Results:");

        foreach ($health as $name => $status) {
            $icon = match($status) {
                'healthy' => '✓',
                'disabled' => '○',
                'stopped' => '⚠',
                'restarted' => '↻',
                'failed' => '✗',
                'failed_max_attempts' => '✗✗',
                default => '?'
            };

            $color = match($status) {
                'healthy', 'restarted' => 'green',
                'disabled' => 'gray',
                'stopped' => 'yellow',
                'failed', 'failed_max_attempts' => 'red',
                default => 'white'
            };

            $message = match($status) {
                'healthy' => 'Running normally',
                'disabled' => 'Disabled in configuration',
                'stopped' => 'Not running',
                'restarted' => 'Auto-restarted successfully',
                'failed' => 'Failed to start',
                'failed_max_attempts' => 'Failed to start (max attempts reached)',
                default => 'Unknown status'
            };

            $this->line("<fg={$color}>{$icon} {$name}: {$message}</>");
        }
    }
}