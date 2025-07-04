<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Database\PooledMySqlConnector;

class MonitorDbConnections extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:monitor-connections 
                            {--interval=5 : Monitoring interval in seconds}
                            {--watch : Keep monitoring continuously}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor database connection pool usage and statistics';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $interval = (int) $this->option('interval');
        $watch = $this->option('watch');
        
        do {
            $this->displayConnectionStats();
            
            if ($watch) {
                sleep($interval);
                $this->newLine();
            }
        } while ($watch);
        
        return Command::SUCCESS;
    }
    
    /**
     * Display current connection statistics
     */
    protected function displayConnectionStats(): void
    {
        $this->info('=== Database Connection Pool Status ===');
        $this->info(date('Y-m-d H:i:s'));
        $this->newLine();
        
        // Get pool statistics
        $poolStats = PooledMySqlConnector::getStats();
        
        // Display pool stats
        $this->table(
            ['Metric', 'Value'],
            [
                ['Active Connections', $poolStats['active_connections']],
                ['Available Connections', $poolStats['available_connections']],
                ['Total Connections', $poolStats['total_connections']],
                ['Max Pool Size', $poolStats['max_connections']],
                ['Wait Queue Size', $poolStats['wait_queue_size']],
                ['Total Requests', $poolStats['total_requests']],
                ['Pool Hits', $poolStats['pool_hits']],
                ['Pool Misses', $poolStats['pool_misses']],
                ['Hit Rate', sprintf('%.2f%%', $poolStats['hit_rate'] * 100)],
            ]
        );
        
        // Check MySQL process list
        try {
            $processes = DB::select("SHOW PROCESSLIST");
            $appProcesses = collect($processes)->filter(function ($process) {
                return str_contains($process->db ?? '', 'askproai');
            });
            
            $this->newLine();
            $this->info('MySQL Processes:');
            $this->table(
                ['Total', 'Application', 'Sleeping', 'Active'],
                [[
                    count($processes),
                    $appProcesses->count(),
                    $appProcesses->where('Command', 'Sleep')->count(),
                    $appProcesses->where('Command', '!=', 'Sleep')->count(),
                ]]
            );
            
            // Show long-running queries
            $longQueries = $appProcesses->filter(function ($process) {
                return $process->Time > 5 && $process->Command !== 'Sleep';
            });
            
            if ($longQueries->isNotEmpty()) {
                $this->newLine();
                $this->warn('Long-running queries detected:');
                foreach ($longQueries as $query) {
                    $this->line(sprintf(
                        "ID: %d, Time: %ds, State: %s, Query: %s",
                        $query->Id,
                        $query->Time,
                        $query->State ?? 'N/A',
                        substr($query->Info ?? 'N/A', 0, 80)
                    ));
                }
            }
            
        } catch (\Exception $e) {
            $this->error("Could not retrieve MySQL process list: " . $e->getMessage());
        }
        
        // Check for warnings
        $this->checkPoolHealth($poolStats);
    }
    
    /**
     * Check pool health and display warnings
     */
    protected function checkPoolHealth(array $stats): void
    {
        $warnings = [];
        
        // Check if pool is exhausted
        if ($stats['available_connections'] == 0) {
            $warnings[] = "Connection pool exhausted! All connections in use.";
        }
        
        // Check if near capacity
        elseif ($stats['available_connections'] < 5) {
            $warnings[] = "Connection pool nearly exhausted. Only {$stats['available_connections']} connections available.";
        }
        
        // Check wait queue
        if ($stats['wait_queue_size'] > 0) {
            $warnings[] = "Requests waiting for connections: {$stats['wait_queue_size']}";
        }
        
        // Check hit rate
        if ($stats['total_requests'] > 100 && $stats['hit_rate'] < 0.8) {
            $warnings[] = sprintf("Low pool hit rate: %.2f%% (consider increasing pool size)", $stats['hit_rate'] * 100);
        }
        
        // Display warnings
        if (!empty($warnings)) {
            $this->newLine();
            $this->warn('⚠️  Warnings:');
            foreach ($warnings as $warning) {
                $this->line("  • {$warning}");
            }
        } else {
            $this->newLine();
            $this->info('✅ Connection pool health: Good');
        }
    }
}