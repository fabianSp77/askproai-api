<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\QueryMonitor;

class EnableQueryMonitoring extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'query:monitor {--threshold=1000 : Slow query threshold in milliseconds}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Enable query monitoring for performance analysis';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $threshold = (int) $this->option('threshold');
        
        $this->info('Enabling query monitoring...');
        $this->info("Slow query threshold set to: {$threshold}ms");
        
        $queryMonitor = new QueryMonitor();
        $queryMonitor->setSlowQueryThreshold($threshold);
        $queryMonitor->enable();
        
        $this->info('Query monitoring enabled successfully!');
        $this->info('Queries will be monitored until the process ends.');
        $this->info('Press Ctrl+C to stop monitoring.');
        
        // Keep the process running
        while (true) {
            sleep(1);
        }
    }
}