<?php

namespace App\Console\Commands;

use App\Services\Billing\DunningService;
use Illuminate\Console\Command;

class ProcessDunningRetries extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dunning:process-retries 
                            {--dry-run : Show what would be processed without actually retrying}
                            {--company= : Process only for specific company ID}
                            {--limit=50 : Maximum number of retries to process}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process due payment retries for dunning processes';

    protected DunningService $dunningService;

    public function __construct(DunningService $dunningService)
    {
        parent::__construct();
        $this->dunningService = $dunningService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Processing dunning retries...');
        
        if ($this->option('dry-run')) {
            $this->warn('DRY RUN MODE - No actual retries will be processed');
            $this->showDueRetries();
            return 0;
        }
        
        $startTime = microtime(true);
        
        try {
            $processedCount = $this->dunningService->processDueRetries();
            
            $duration = round(microtime(true) - $startTime, 2);
            
            $this->info("✅ Processed {$processedCount} dunning retries in {$duration} seconds");
            
            // Show statistics
            $this->showStatistics();
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error('Error processing dunning retries: ' . $e->getMessage());
            return 1;
        }
    }
    
    /**
     * Show retries that are due
     */
    protected function showDueRetries(): void
    {
        $processes = \App\Models\DunningProcess::dueForRetry()
            ->with(['company', 'invoice'])
            ->limit($this->option('limit'))
            ->get();
        
        if ($processes->isEmpty()) {
            $this->info('No dunning processes are due for retry.');
            return;
        }
        
        $this->info("Found {$processes->count()} dunning processes due for retry:");
        
        $this->table(
            ['ID', 'Company', 'Invoice', 'Amount', 'Retry #', 'Next Retry', 'Days Overdue'],
            $processes->map(function ($process) {
                return [
                    $process->id,
                    $process->company->name,
                    $process->invoice->number ?? 'N/A',
                    $process->currency . ' ' . number_format($process->remaining_amount, 2),
                    $process->retry_count . '/' . $process->max_retries,
                    $process->next_retry_at->format('Y-m-d H:i'),
                    $process->getDaysSinceFailure() . ' days'
                ];
            })
        );
    }
    
    /**
     * Show dunning statistics
     */
    protected function showStatistics(): void
    {
        $stats = $this->dunningService->getStatistics();
        
        $this->newLine();
        $this->info('Dunning Statistics:');
        
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Processes', $stats['total_processes']],
                ['Active Processes', $stats['active_processes']],
                ['Resolved Processes', $stats['resolved_processes']],
                ['Failed Processes', $stats['failed_processes']],
                ['Recovery Rate', $stats['recovery_rate'] . '%'],
                ['Average Retry Count', $stats['average_retry_count']],
                ['Total Recovered', '€' . number_format($stats['total_recovered'], 2)],
                ['Total Outstanding', '€' . number_format($stats['total_outstanding'], 2)],
                ['Suspended Companies', $stats['companies_with_suspended_service']]
            ]
        );
    }
}