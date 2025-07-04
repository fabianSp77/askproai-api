<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Database\PooledMySqlConnector;

class DatabasePoolStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:pool-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display database connection pool statistics';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $stats = PooledMySqlConnector::getStats();
        
        $this->info('Database Connection Pool Status');
        $this->info('===============================');
        
        $this->table(
            ['Metric', 'Value'],
            [
                ['Connections Created', $stats['connections_created']],
                ['Connections Reused', $stats['connections_reused']],
                ['Connections Failed', $stats['connections_failed']],
                ['Pool Hits', $stats['pool_hits']],
                ['Pool Misses', $stats['pool_misses']],
                ['Current Pool Size', $stats['pool_size']],
                ['Pool Keys', $stats['pool_keys']],
            ]
        );
        
        // Calculate efficiency
        $totalRequests = $stats['pool_hits'] + $stats['pool_misses'];
        if ($totalRequests > 0) {
            $hitRate = ($stats['pool_hits'] / $totalRequests) * 100;
            $this->info(sprintf('Pool Hit Rate: %.2f%%', $hitRate));
        }
        
        // Show configuration
        $this->info('');
        $this->info('Configuration:');
        $this->info('Min Connections: ' . config('database.connections.mysql.pool.min_connections', 5));
        $this->info('Max Connections: ' . config('database.connections.mysql.pool.max_connections', 50));
        
        return Command::SUCCESS;
    }
}