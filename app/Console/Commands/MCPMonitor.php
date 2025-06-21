<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\MCP\MCPOrchestrator;
use App\Services\Database\ConnectionPoolManager;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class MCPMonitor extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mcp:monitor 
                            {--interval=5 : Refresh interval in seconds}
                            {--metrics : Show detailed metrics}
                            {--connections : Show connection pool stats}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor MCP system performance and health';

    protected MCPOrchestrator $orchestrator;
    protected ConnectionPoolManager $connectionPool;
    
    public function __construct(
        MCPOrchestrator $orchestrator,
        ConnectionPoolManager $connectionPool
    ) {
        parent::__construct();
        $this->orchestrator = $orchestrator;
        $this->connectionPool = $connectionPool;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $interval = (int) $this->option('interval');
        $showMetrics = $this->option('metrics');
        $showConnections = $this->option('connections');
        
        $this->info('Starting MCP System Monitor...');
        $this->info('Press Ctrl+C to stop');
        $this->line('');
        
        while (true) {
            $this->displayDashboard($showMetrics, $showConnections);
            
            if ($interval > 0) {
                sleep($interval);
                // Clear screen (works on most terminals)
                $this->line("\033[2J\033[H");
            } else {
                break;
            }
        }
    }
    
    protected function displayDashboard(bool $showMetrics, bool $showConnections): void
    {
        $this->displayHeader();
        $this->displaySystemHealth();
        
        if ($showMetrics) {
            $this->displayDetailedMetrics();
        }
        
        if ($showConnections) {
            $this->displayConnectionPoolStats();
        }
        
        $this->displayActiveOperations();
        $this->displayQueueStatus();
        $this->displayRecentErrors();
    }
    
    protected function displayHeader(): void
    {
        $this->info('═══════════════════════════════════════════════════════════════════');
        $this->info('                    MCP SYSTEM MONITOR                             ');
        $this->info('═══════════════════════════════════════════════════════════════════');
        $this->info('Time: ' . now()->format('Y-m-d H:i:s'));
        $this->line('');
    }
    
    protected function displaySystemHealth(): void
    {
        $health = $this->orchestrator->healthCheck();
        
        $this->info('SYSTEM HEALTH');
        $this->line('─────────────');
        
        $statusColor = $health['status'] === 'healthy' ? 'info' : 'error';
        $this->line("Overall Status: <{$statusColor}>" . strtoupper($health['status']) . "</>");
        
        $this->table(
            ['Service', 'Status', 'Connection Pool'],
            collect($health['services'])->map(function ($status, $service) use ($health) {
                $poolStats = $health['connection_pool'][$service] ?? null;
                $poolInfo = $poolStats ? "{$poolStats['active']}/{$poolStats['total']}" : 'N/A';
                
                return [
                    $service,
                    $status === 'healthy' ? '<info>✓ Healthy</>' : '<error>✗ Unhealthy</>',
                    $poolInfo
                ];
            })->toArray()
        );
    }
    
    protected function displayDetailedMetrics(): void
    {
        $metrics = $this->orchestrator->getMetrics();
        
        $this->info('PERFORMANCE METRICS');
        $this->line('──────────────────');
        
        $this->line("Total Requests: " . number_format($metrics['total_requests']));
        $this->line("Total Errors: " . number_format($metrics['total_errors']));
        $this->line("Error Rate: " . $metrics['error_rate'] . '%');
        $this->line("Avg Latency: " . $metrics['avg_latency_ms'] . 'ms');
        $this->line("P99 Latency: " . $metrics['p99_latency_ms'] . 'ms');
        $this->line('');
        
        // Service-specific metrics
        $serviceMetrics = DB::table('mcp_metrics')
            ->select('service', DB::raw('COUNT(*) as count'), DB::raw('AVG(duration_ms) as avg_duration'))
            ->where('created_at', '>=', now()->subMinutes(5))
            ->groupBy('service')
            ->get();
        
        if ($serviceMetrics->count() > 0) {
            $this->table(
                ['Service', 'Requests (5m)', 'Avg Duration'],
                $serviceMetrics->map(function ($metric) {
                    return [
                        $metric->service,
                        number_format($metric->count),
                        round($metric->avg_duration, 2) . 'ms'
                    ];
                })->toArray()
            );
        }
    }
    
    protected function displayConnectionPoolStats(): void
    {
        $stats = $this->connectionPool->getStats();
        
        $this->info('CONNECTION POOL');
        $this->line('───────────────');
        
        foreach ($stats as $pool => $poolStats) {
            if (is_array($poolStats) && isset($poolStats['idle'])) {
                $this->line(sprintf(
                    "%s: Idle: %d, Active: %d, Total: %d",
                    $pool,
                    $poolStats['idle'],
                    $poolStats['active'],
                    $poolStats['total']
                ));
            }
        }
        $this->line('');
    }
    
    protected function displayActiveOperations(): void
    {
        $activeOps = Cache::get('mcp:active_operations', []);
        
        $this->info('ACTIVE OPERATIONS');
        $this->line('────────────────');
        
        if (empty($activeOps)) {
            $this->line('No active operations');
        } else {
            $this->table(
                ['Operation ID', 'Service', 'Started', 'Duration'],
                collect($activeOps)->map(function ($op) {
                    return [
                        substr($op['id'], 0, 8),
                        $op['service'],
                        $op['started_at'],
                        number_format(microtime(true) - $op['start_time'], 2) . 's'
                    ];
                })->toArray()
            );
        }
        $this->line('');
    }
    
    protected function displayQueueStatus(): void
    {
        try {
            $queueMCP = app(\App\Services\MCP\QueueMCPServer::class);
            $overview = $queueMCP->getOverview();
            
            $this->info('QUEUE STATUS');
            $this->line('────────────');
            
            $this->line("Horizon Status: " . $overview['horizon_status']);
            $this->line("Failed Jobs: " . number_format($overview['failed_jobs']));
            
            $this->table(
                ['Queue', 'Size', 'Status'],
                collect($overview['queues'])->map(function ($stats, $queue) {
                    $statusColor = $stats['status'] === 'normal' ? 'info' : 
                                  ($stats['status'] === 'medium' ? 'comment' : 'error');
                    return [
                        $queue,
                        number_format($stats['size']),
                        "<{$statusColor}>" . ucfirst($stats['status']) . "</>"
                    ];
                })->toArray()
            );
        } catch (\Exception $e) {
            $this->error('Unable to fetch queue status: ' . $e->getMessage());
        }
        $this->line('');
    }
    
    protected function displayRecentErrors(): void
    {
        $errors = DB::table('mcp_metrics')
            ->where('success', false)
            ->where('created_at', '>=', now()->subMinutes(5))
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
        
        $this->info('RECENT ERRORS (Last 5 minutes)');
        $this->line('─────────────────────────────');
        
        if ($errors->isEmpty()) {
            $this->line('<info>No errors in the last 5 minutes</info>');
        } else {
            foreach ($errors as $error) {
                $metadata = json_decode($error->metadata ?? '{}', true);
                $this->line(sprintf(
                    "[%s] %s - %s",
                    $error->created_at,
                    $error->service,
                    $metadata['error'] ?? 'Unknown error'
                ));
            }
        }
        $this->line('');
    }
}