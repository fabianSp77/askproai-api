<?php

namespace App\Console\Commands;

use App\Jobs\CollectMetricsJob;
use Illuminate\Console\Command;

class CollectMetricsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'metrics:collect 
                            {--immediate : Run collection immediately without queueing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Collect and update system metrics for Prometheus monitoring';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting metrics collection...');

        if ($this->option('immediate')) {
            // Run immediately in the same process
            $job = new CollectMetricsJob;
            $job->handle(app(\App\Services\Monitoring\MetricsCollector::class));

            $this->info('Metrics collection completed successfully.');
        } else {
            // Queue the job
            CollectMetricsJob::dispatch();

            $this->info('Metrics collection job has been queued.');
        }

        return self::SUCCESS;
    }
}
