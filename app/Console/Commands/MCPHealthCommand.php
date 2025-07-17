<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\MCP\MCPOrchestrator;
use Illuminate\Support\Facades\Cache;

class MCPHealthCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mcp:health {--json : Output as JSON}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check health status of all MCP servers';

    /**
     * Execute the console command.
     */
    public function handle(MCPOrchestrator $orchestrator): int
    {
        $this->info('🏥 Checking MCP Server Health...');
        $this->line('');
        
        $servers = config('mcp-servers.servers', []);
        $healthStatus = [];
        $totalServers = 0;
        $healthyServers = 0;
        
        foreach ($servers as $name => $config) {
            if (!$config['enabled']) continue;
            
            $totalServers++;
            $status = $this->checkServerHealth($name, $config);
            $healthStatus[$name] = $status;
            
            if ($status['healthy']) {
                $healthyServers++;
            }
            
            // Display status
            if (!$this->option('json')) {
                $icon = $status['healthy'] ? '✅' : '❌';
                $this->line("{$icon} {$name}: {$status['status']}");
                
                if (!empty($status['details'])) {
                    $this->line("   {$status['details']}");
                }
            }
        }
        
        // External servers
        $externalServers = config('mcp-external.external_servers', []);
        $externalHealthy = 0;
        $totalExternal = 0;
        
        if (!$this->option('json')) {
            $this->line('');
            $this->info('📡 External MCP Servers:');
        }
        
        foreach ($externalServers as $name => $config) {
            if (!$config['enabled']) continue;
            
            $totalExternal++;
            $isRunning = $this->checkExternalServer($name);
            
            if ($isRunning) {
                $externalHealthy++;
            }
            
            if (!$this->option('json')) {
                $icon = $isRunning ? '🟢' : '🔴';
                $status = $isRunning ? 'Running' : 'Stopped';
                $this->line("{$icon} {$name}: {$status}");
            }
        }
        
        // Summary
        if ($this->option('json')) {
            $this->line(json_encode([
                'internal' => [
                    'total' => $totalServers,
                    'healthy' => $healthyServers,
                    'servers' => $healthStatus
                ],
                'external' => [
                    'total' => $totalExternal,
                    'running' => $externalHealthy
                ],
                'overall_health' => ($healthyServers / $totalServers) * 100
            ], JSON_PRETTY_PRINT));
        } else {
            $this->line('');
            $this->info('📊 Summary:');
            $this->line("Internal Servers: {$healthyServers}/{$totalServers} healthy");
            $this->line("External Servers: {$externalHealthy}/{$totalExternal} running");
            
            $overallHealth = ($totalServers > 0) ? round(($healthyServers / $totalServers) * 100) : 0;
            $healthColor = $overallHealth >= 80 ? 'info' : ($overallHealth >= 50 ? 'comment' : 'error');
            
            $this->line('');
            $this->{$healthColor}("Overall Health: {$overallHealth}%");
        }
        
        // Cache health status
        Cache::put('mcp_health_status', [
            'internal_healthy' => $healthyServers,
            'internal_total' => $totalServers,
            'external_running' => $externalHealthy,
            'external_total' => $totalExternal,
            'overall_health' => $overallHealth ?? 0,
            'checked_at' => now()
        ], 300); // 5 minutes
        
        return ($overallHealth >= 50) ? 0 : 1;
    }
    
    /**
     * Check health of internal MCP server
     */
    protected function checkServerHealth(string $name, array $config): array
    {
        try {
            $serverClass = $config['class'];
            
            if (!class_exists($serverClass)) {
                return [
                    'healthy' => false,
                    'status' => 'Class not found',
                    'details' => "Class {$serverClass} does not exist"
                ];
            }
            
            // Try to instantiate
            $instance = app($serverClass);
            
            // Check for health check method
            if (method_exists($instance, 'healthCheck')) {
                $health = $instance->healthCheck();
                return [
                    'healthy' => $health['healthy'] ?? false,
                    'status' => $health['healthy'] ? 'Healthy' : 'Unhealthy',
                    'details' => $health['message'] ?? ''
                ];
            }
            
            // Check if we can get tools (basic health indicator)
            if (method_exists($instance, 'getTools')) {
                $tools = $instance->getTools();
                return [
                    'healthy' => true,
                    'status' => 'Active',
                    'details' => count($tools) . ' tools available'
                ];
            }
            
            return [
                'healthy' => true,
                'status' => 'Active',
                'details' => 'No health check available'
            ];
            
        } catch (\Exception $e) {
            return [
                'healthy' => false,
                'status' => 'Error',
                'details' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Check if external server is running
     */
    protected function checkExternalServer(string $name): bool
    {
        try {
            $result = \Illuminate\Support\Facades\Process::run("pgrep -f '{$name}'");
            return $result->successful() && !empty(trim($result->output()));
        } catch (\Exception $e) {
            return false;
        }
    }
}