<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\MCP\MCPOrchestrator;
use App\Services\MCP\ExternalMCPManager;
use App\Services\Database\ConnectionPoolManager;
use Illuminate\Support\Facades\DB;

class MCPStatus extends Command
{
    protected $signature = 'mcp:status 
                            {--detailed : Show detailed information}
                            {--json : Output as JSON}';
    
    protected $description = 'Show comprehensive status of all MCP servers (internal and external)';

    protected MCPOrchestrator $orchestrator;
    protected ExternalMCPManager $externalMCPManager;
    protected ConnectionPoolManager $connectionPool;

    public function __construct(
        MCPOrchestrator $orchestrator,
        ExternalMCPManager $externalMCPManager,
        ConnectionPoolManager $connectionPool
    ) {
        parent::__construct();
        $this->orchestrator = $orchestrator;
        $this->externalMCPManager = $externalMCPManager;
        $this->connectionPool = $connectionPool;
    }

    public function handle()
    {
        $detailed = $this->option('detailed');
        $jsonOutput = $this->option('json');

        $status = $this->gatherStatus();

        if ($jsonOutput) {
            $this->line(json_encode($status, JSON_PRETTY_PRINT));
            return 0;
        }

        $this->displayStatus($status, $detailed);
        return 0;
    }

    protected function gatherStatus(): array
    {
        return [
            'timestamp' => now()->toIso8601String(),
            'internal_servers' => $this->getInternalServersStatus(),
            'external_servers' => $this->externalMCPManager->getStatus(),
            'system_health' => $this->orchestrator->healthCheck(),
            'metrics' => $this->orchestrator->getMetrics(),
            'connection_pools' => $this->connectionPool->getStats(),
        ];
    }

    protected function getInternalServersStatus(): array
    {
        $servers = [
            'webhook' => 'Webhook Processing & Orchestration',
            'calcom' => 'Cal.com Integration',
            'retell' => 'Retell.ai Phone Service',
            'database' => 'Database Operations',
            'queue' => 'Queue Management',
            'stripe' => 'Payment Processing',
            'knowledge' => 'Knowledge Base',
            'sentry' => 'Error Tracking',
            'appointment' => 'Appointment Management',
            'branch' => 'Branch Operations',
            'company' => 'Company Management',
            'customer' => 'Customer Operations',
        ];

        $status = [];
        $health = $this->orchestrator->healthCheck();

        foreach ($servers as $key => $description) {
            $status[$key] = [
                'description' => $description,
                'enabled' => config("mcp.services.{$key}.enabled", true),
                'status' => $health['services'][$key] ?? 'unknown',
                'endpoints' => $this->getEndpointsForService($key),
            ];
        }

        return $status;
    }

    protected function getEndpointsForService(string $service): array
    {
        $endpoints = [
            'webhook' => ['/api/mcp/webhook/test', '/api/mcp/webhook/stats'],
            'calcom' => ['/api/mcp/calcom/event-types', '/api/mcp/calcom/availability'],
            'retell' => ['/api/mcp/retell/agents', '/api/mcp/retell/calls'],
            'database' => ['/api/mcp/database/query', '/api/mcp/database/schema'],
            'queue' => ['/api/mcp/queue/overview', '/api/mcp/queue/failed'],
        ];

        return $endpoints[$service] ?? [];
    }

    protected function displayStatus(array $status, bool $detailed): void
    {
        $this->displayHeader();
        $this->displayInternalServers($status['internal_servers']);
        $this->displayExternalServers($status['external_servers']);
        $this->displaySystemHealth($status['system_health']);
        
        if ($detailed) {
            $this->displayMetrics($status['metrics']);
            $this->displayConnectionPools($status['connection_pools']);
        }

        $this->displaySummary($status);
    }

    protected function displayHeader(): void
    {
        $this->info('═══════════════════════════════════════════════════════════════════');
        $this->info('                    MCP COMPREHENSIVE STATUS                       ');
        $this->info('═══════════════════════════════════════════════════════════════════');
        $this->info('Time: ' . now()->format('Y-m-d H:i:s'));
        $this->line('');
    }

    protected function displayInternalServers(array $servers): void
    {
        $this->info('INTERNAL MCP SERVERS (Laravel Services)');
        $this->line('──────────────────────────────────────');
        
        $rows = [];
        foreach ($servers as $name => $info) {
            $statusIcon = match($info['status']) {
                'healthy' => '<info>✓</info>',
                'unhealthy' => '<error>✗</error>',
                default => '<comment>?</comment>'
            };
            
            $rows[] = [
                ucfirst($name),
                $info['enabled'] ? '<info>Yes</info>' : '<comment>No</comment>',
                $statusIcon . ' ' . ucfirst($info['status']),
                \Str::limit($info['description'], 40),
            ];
        }

        $this->table(['Service', 'Enabled', 'Status', 'Description'], $rows);
    }

    protected function displayExternalServers(array $servers): void
    {
        $this->info('EXTERNAL MCP SERVERS (Node.js Tools)');
        $this->line('───────────────────────────────────');
        
        if (empty($servers)) {
            $this->line('No external MCP servers configured');
            return;
        }

        $rows = [];
        foreach ($servers as $name => $info) {
            $rows[] = [
                $name,
                $info['enabled'] ? '<info>✓</info>' : '<comment>✗</comment>',
                $info['running'] 
                    ? '<info>Running</info>' 
                    : ($info['enabled'] ? '<error>Stopped</error>' : '<comment>Disabled</comment>'),
                $info['pid'] ?? '-',
                \Str::limit($info['description'], 35),
            ];
        }

        $this->table(['Server', 'Enabled', 'Status', 'PID', 'Description'], $rows);
        
        // Check if any enabled servers are not running
        $stoppedServers = collect($servers)
            ->filter(fn($info) => $info['enabled'] && !$info['running'])
            ->keys();

        if ($stoppedServers->isNotEmpty()) {
            $this->warn('Some external servers are not running. Start with:');
            $this->line('  php artisan mcp:external start');
        }
    }

    protected function displaySystemHealth(array $health): void
    {
        $this->info('SYSTEM HEALTH');
        $this->line('────────────');
        
        $statusColor = $health['status'] === 'healthy' ? 'info' : 'error';
        $this->line("Overall Status: <{$statusColor}>" . strtoupper($health['status']) . "</>");
        $this->line('');
    }

    protected function displayMetrics(array $metrics): void
    {
        $this->info('PERFORMANCE METRICS');
        $this->line('─────────────────');
        
        $this->line("Total Requests: " . number_format($metrics['total_requests']));
        $this->line("Error Rate: " . $metrics['error_rate'] . '%');
        $this->line("Avg Latency: " . $metrics['avg_latency_ms'] . 'ms');
        $this->line("P99 Latency: " . $metrics['p99_latency_ms'] . 'ms');
        $this->line('');
    }

    protected function displayConnectionPools(array $pools): void
    {
        $this->info('CONNECTION POOLS');
        $this->line('───────────────');
        
        foreach ($pools as $pool => $stats) {
            if (is_array($stats) && isset($stats['active'])) {
                $this->line(sprintf(
                    "%s: Active: %d/%d (%.1f%% usage)",
                    $pool,
                    $stats['active'],
                    $stats['total'],
                    ($stats['active'] / max($stats['total'], 1)) * 100
                ));
            }
        }
        $this->line('');
    }

    protected function displaySummary(array $status): void
    {
        $internalHealthy = collect($status['internal_servers'])
            ->filter(fn($s) => $s['status'] === 'healthy')
            ->count();
        $internalTotal = count($status['internal_servers']);
        
        $externalRunning = collect($status['external_servers'])
            ->filter(fn($s) => $s['enabled'] && $s['running'])
            ->count();
        $externalEnabled = collect($status['external_servers'])
            ->filter(fn($s) => $s['enabled'])
            ->count();

        $this->info('SUMMARY');
        $this->line('──────');
        $this->line("Internal MCP Servers: {$internalHealthy}/{$internalTotal} healthy");
        $this->line("External MCP Servers: {$externalRunning}/{$externalEnabled} running");
        $this->line("System Status: " . ucfirst($status['system_health']['status']));
        
        if ($status['system_health']['status'] === 'healthy' && 
            $internalHealthy === $internalTotal && 
            $externalRunning === $externalEnabled) {
            $this->info('✓ All systems operational');
        } else {
            $this->warn('⚠ Some services need attention');
        }
    }
}