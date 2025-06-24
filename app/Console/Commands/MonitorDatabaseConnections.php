<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MonitorDatabaseConnections extends Command
{
    protected $signature = 'db:monitor-connections {--interval=5}';
    protected $description = 'Monitor database connection usage';

    public function handle()
    {
        $interval = $this->option('interval');
        
        $this->info('Monitoring database connections (Press Ctrl+C to stop)...');
        
        while (true) {
            try {
                $stats = DB::select("
                    SELECT 
                        (SELECT COUNT(*) FROM information_schema.processlist) as current_connections,
                        (SELECT COUNT(*) FROM information_schema.processlist WHERE command = 'Sleep') as idle_connections,
                        (SELECT COUNT(*) FROM information_schema.processlist WHERE time > 60) as long_running,
                        (SELECT @@max_connections) as max_connections,
                        (SELECT @@wait_timeout) as wait_timeout
                ");
                
                $stat = $stats[0];
                
                $this->table(
                    ['Metric', 'Value'],
                    [
                        ['Current Connections', $stat->current_connections],
                        ['Idle Connections', $stat->idle_connections],
                        ['Long Running (>60s)', $stat->long_running],
                        ['Max Connections', $stat->max_connections],
                        ['Wait Timeout', $stat->wait_timeout . 's'],
                        ['Usage %', round(($stat->current_connections / $stat->max_connections) * 100, 2) . '%'],
                    ]
                );
                
                if ($stat->current_connections > ($stat->max_connections * 0.8)) {
                    $this->error('WARNING: Connection usage above 80%!');
                }
                
            } catch (\Exception $e) {
                $this->error('Error: ' . $e->getMessage());
            }
            
            sleep($interval);
            
            // Clear screen for next update
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                system('cls');
            } else {
                system('clear');
            }
        }
    }
}