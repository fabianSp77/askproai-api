<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Services\Database\ConnectionPoolManager;

class DatabasePerformanceMonitor extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:performance-monitor 
                            {--interval=5 : Monitoring interval in seconds}
                            {--duration=300 : Total monitoring duration in seconds}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor database performance metrics in real-time';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $interval = $this->option('interval');
        $duration = $this->option('duration');
        $endTime = time() + $duration;
        
        $this->info('Starting database performance monitoring...');
        $this->info("Interval: {$interval}s | Duration: {$duration}s");
        $this->line('');
        
        while (time() < $endTime) {
            $this->displayMetrics();
            sleep($interval);
        }
        
        $this->info('Monitoring completed.');
        return 0;
    }
    
    /**
     * Display performance metrics
     */
    private function displayMetrics()
    {
        $this->line('=== ' . date('Y-m-d H:i:s') . ' ===');
        
        // Connection pool stats
        $poolStats = ConnectionPoolManager::getStats();
        $this->table(
            ['Metric', 'Value'],
            [
                ['Active Connections', $poolStats['active_connections']],
                ['Idle Connections', $poolStats['idle_connections']],
                ['Total Connections', $poolStats['total_connections']],
                ['Failed Connections', $poolStats['failed_connections']],
                ['Recycled Connections', $poolStats['recycled_connections']],
            ]
        );
        
        // Current queries
        $this->displayCurrentQueries();
        
        // Slow queries
        $this->displaySlowQueries();
        
        // Table statistics
        $this->displayTableStats();
        
        $this->line('');
    }
    
    /**
     * Display currently running queries
     */
    private function displayCurrentQueries()
    {
        $queries = DB::select("
            SELECT 
                id,
                user,
                db,
                command,
                time,
                state,
                LEFT(info, 100) as query
            FROM information_schema.processlist
            WHERE command != 'Sleep'
            AND db = ?
            ORDER BY time DESC
            LIMIT 5
        ", [config('database.connections.mysql.database')]);
        
        if (count($queries) > 0) {
            $this->info('Current Queries:');
            $this->table(
                ['ID', 'User', 'Time', 'State', 'Query'],
                array_map(function ($q) {
                    return [
                        $q->id,
                        $q->user,
                        $q->time . 's',
                        $q->state,
                        substr($q->query, 0, 50) . '...'
                    ];
                }, $queries)
            );
        }
    }
    
    /**
     * Display slow queries from the last interval
     */
    private function displaySlowQueries()
    {
        try {
            $slowQueries = DB::select("
                SELECT 
                    query_time,
                    lock_time,
                    rows_sent,
                    rows_examined,
                    LEFT(sql_text, 100) as query
                FROM mysql.slow_log
                WHERE start_time >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
                ORDER BY query_time DESC
                LIMIT 5
            ");
            
            if (count($slowQueries) > 0) {
                $this->warn('Recent Slow Queries:');
                $this->table(
                    ['Query Time', 'Lock Time', 'Rows Sent', 'Rows Examined', 'Query'],
                    array_map(function ($q) {
                        return [
                            round($q->query_time, 2) . 's',
                            round($q->lock_time, 2) . 's',
                            $q->rows_sent,
                            $q->rows_examined,
                            substr($q->query, 0, 50) . '...'
                        ];
                    }, $slowQueries)
                );
            }
        } catch (\Exception $e) {
            // Slow query log might not be enabled
        }
    }
    
    /**
     * Display table statistics
     */
    private function displayTableStats()
    {
        $stats = DB::select("
            SELECT 
                table_name,
                table_rows,
                ROUND(data_length / 1024 / 1024, 2) as data_mb,
                ROUND(index_length / 1024 / 1024, 2) as index_mb,
                COUNT(DISTINCT index_name) as index_count
            FROM information_schema.tables t
            LEFT JOIN information_schema.statistics s 
                ON t.table_schema = s.table_schema 
                AND t.table_name = s.table_name
            WHERE t.table_schema = ?
            AND t.table_name IN ('appointments', 'calls', 'customers', 'staff', 'branches')
            GROUP BY t.table_name, t.table_rows, t.data_length, t.index_length
            ORDER BY table_rows DESC
        ", [config('database.connections.mysql.database')]);
        
        $this->info('Table Statistics:');
        $this->table(
            ['Table', 'Rows', 'Data (MB)', 'Index (MB)', 'Indexes'],
            array_map(function ($s) {
                return [
                    $s->table_name,
                    number_format($s->table_rows),
                    $s->data_mb,
                    $s->index_mb,
                    $s->index_count
                ];
            }, $stats)
        );
    }
}