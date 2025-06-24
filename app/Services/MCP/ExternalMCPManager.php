<?php

namespace App\Services\MCP;

use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ExternalMCPManager
{
    protected array $config;
    protected array $runningServers = [];
    protected string $logChannel;

    public function __construct()
    {
        $this->config = config('mcp-external.external_servers', []);
        // Use default 'stack' channel to avoid config cache issues
        $this->logChannel = 'stack';
    }

    /**
     * Start an external MCP server
     */
    public function startServer(string $name): bool
    {
        if (!isset($this->config[$name]) || !$this->config[$name]['enabled']) {
            $this->log("Server {$name} is not enabled or configured", 'warning');
            return false;
        }

        if ($this->isServerRunning($name)) {
            $this->log("Server {$name} is already running", 'info');
            return true;
        }

        $serverConfig = $this->config[$name];
        
        try {
            $command = array_merge([$serverConfig['command']], $serverConfig['args']);
            
            $process = Process::command($command)
                ->timeout($serverConfig['timeout'] ?? 30);

            if (isset($serverConfig['env'])) {
                $process->env($serverConfig['env']);
            }

            $process->start();

            $this->runningServers[$name] = $process;
            Cache::put("mcp:external:{$name}:pid", $process->getPid(), now()->addHours(24));
            
            $this->log("Started external MCP server: {$name}", 'info');
            return true;
        } catch (\Exception $e) {
            $this->log("Failed to start external MCP server {$name}: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Stop an external MCP server
     */
    public function stopServer(string $name): bool
    {
        if (!isset($this->runningServers[$name])) {
            // Try to find by cached PID
            $pid = Cache::get("mcp:external:{$name}:pid");
            if (!$pid) {
                $this->log("Server {$name} is not running", 'info');
                return false;
            }
            
            // Kill process by PID
            try {
                Process::run("kill -TERM {$pid}");
                Cache::forget("mcp:external:{$name}:pid");
                $this->log("Stopped external MCP server: {$name} (PID: {$pid})", 'info');
                return true;
            } catch (\Exception $e) {
                $this->log("Failed to stop server {$name}: " . $e->getMessage(), 'error');
                return false;
            }
        }

        try {
            $this->runningServers[$name]->stop();
            unset($this->runningServers[$name]);
            Cache::forget("mcp:external:{$name}:pid");
            
            $this->log("Stopped external MCP server: {$name}", 'info');
            return true;
        } catch (\Exception $e) {
            $this->log("Failed to stop external MCP server {$name}: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Stop all running servers
     */
    public function stopAll(): void
    {
        foreach (array_keys($this->config) as $name) {
            $this->stopServer($name);
        }
    }

    /**
     * Check if a server is running
     */
    public function isServerRunning(string $name): bool
    {
        if (isset($this->runningServers[$name])) {
            return $this->runningServers[$name]->running();
        }

        // Check by cached PID
        $pid = Cache::get("mcp:external:{$name}:pid");
        if ($pid) {
            try {
                $result = Process::run("ps -p {$pid}");
                return $result->successful();
            } catch (\Exception $e) {
                return false;
            }
        }

        return false;
    }

    /**
     * Get status of all configured servers
     */
    public function getStatus(): array
    {
        $status = [];
        
        foreach ($this->config as $name => $config) {
            $status[$name] = [
                'enabled' => $config['enabled'],
                'running' => $this->isServerRunning($name),
                'description' => $config['description'] ?? '',
                'pid' => Cache::get("mcp:external:{$name}:pid"),
            ];
        }
        
        return $status;
    }

    /**
     * Restart a server
     */
    public function restartServer(string $name): bool
    {
        $this->stopServer($name);
        sleep(1); // Brief pause before restart
        return $this->startServer($name);
    }

    /**
     * Start all enabled servers
     */
    public function startAll(): array
    {
        $results = [];
        
        foreach ($this->config as $name => $config) {
            if ($config['enabled']) {
                $results[$name] = $this->startServer($name);
            }
        }
        
        return $results;
    }

    /**
     * Health check for all running servers
     */
    public function healthCheck(): array
    {
        $health = [];
        
        foreach ($this->config as $name => $config) {
            if (!$config['enabled']) {
                $health[$name] = 'disabled';
                continue;
            }

            if ($this->isServerRunning($name)) {
                $health[$name] = 'healthy';
            } else {
                $health[$name] = 'stopped';
                
                // Auto-restart if configured
                if (config('mcp-external.management.restart_on_failure', true)) {
                    $attempts = Cache::get("mcp:external:{$name}:restart_attempts", 0);
                    $maxAttempts = config('mcp-external.management.max_restart_attempts', 3);
                    
                    if ($attempts < $maxAttempts) {
                        $this->log("Auto-restarting {$name} (attempt " . ($attempts + 1) . "/{$maxAttempts})", 'warning');
                        
                        if ($this->startServer($name)) {
                            Cache::forget("mcp:external:{$name}:restart_attempts");
                            $health[$name] = 'restarted';
                        } else {
                            Cache::increment("mcp:external:{$name}:restart_attempts");
                            $health[$name] = 'failed';
                        }
                    } else {
                        $health[$name] = 'failed_max_attempts';
                    }
                }
            }
        }
        
        return $health;
    }

    /**
     * Log message to configured channel
     */
    protected function log(string $message, string $level = 'info'): void
    {
        try {
            // Validate log level
            if (!in_array($level, ['emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug'])) {
                $level = 'info';
            }
            
            // Try to use configured channel
            if ($this->logChannel && $this->logChannel !== 'stack') {
                Log::channel($this->logChannel)->$level("[ExternalMCP] " . $message);
            } else {
                // Use default logging
                Log::$level("[ExternalMCP] " . $message);
            }
        } catch (\Exception $e) {
            // Ultimate fallback - write to error log directly
            error_log("[ExternalMCP] {$level}: {$message}");
        }
    }

    /**
     * Destructor - ensure all servers are stopped
     */
    public function __destruct()
    {
        // Disable destructor cleanup to prevent 500 errors
        // Servers should be explicitly stopped via commands or lifecycle hooks
        return;
        
        // Original code disabled:
        // try {
        //     if (app()->runningInConsole() && !app()->runningUnitTests()) {
        //         $this->stopAll();
        //     }
        // } catch (\Exception $e) {
        //     // Ignore errors during shutdown
        // }
    }
}