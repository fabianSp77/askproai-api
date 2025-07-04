<?php

namespace App\Console\Commands;

use App\Jobs\ProcessWebhookJob;
use App\Models\WebhookEvent;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class RetryFailedWebhooks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'webhooks:retry-failed 
                            {--provider= : Filter by provider (stripe, retell, calcom)}
                            {--event-type= : Filter by event type}
                            {--since= : Retry webhooks failed since (e.g., "1 hour ago")}
                            {--webhook-id= : Retry specific webhook by ID}
                            {--dry-run : Show what would be retried without actually retrying}
                            {--limit=100 : Maximum number of webhooks to retry}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Retry failed webhook events';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $query = WebhookEvent::where('status', WebhookEvent::STATUS_FAILED);
        
        // Apply filters
        if ($provider = $this->option('provider')) {
            $query->where('provider', $provider);
        }
        
        if ($eventType = $this->option('event-type')) {
            $query->where('event_type', $eventType);
        }
        
        if ($since = $this->option('since')) {
            $query->where('updated_at', '>=', now()->parse($since));
        }
        
        if ($webhookId = $this->option('webhook-id')) {
            $query->where('id', $webhookId);
        }
        
        $limit = (int) $this->option('limit');
        $query->limit($limit);
        
        // Order by most recent first
        $query->orderBy('created_at', 'desc');
        
        $webhooks = $query->get();
        
        if ($webhooks->isEmpty()) {
            $this->info('No failed webhooks found matching the criteria.');
            return 0;
        }
        
        $this->info("Found {$webhooks->count()} failed webhook(s) to retry:");
        
        // Display table of webhooks to retry
        $this->table(
            ['ID', 'Provider', 'Event Type', 'Company', 'Failed At', 'Retry Count', 'Error'],
            $webhooks->map(function ($webhook) {
                return [
                    $webhook->id,
                    $webhook->provider,
                    $webhook->event_type,
                    $webhook->company_id ?? 'N/A',
                    $webhook->updated_at->diffForHumans(),
                    $webhook->retry_count,
                    Str::limit($webhook->error_message, 50)
                ];
            })
        );
        
        if ($this->option('dry-run')) {
            $this->warn('Dry run mode - no webhooks will be retried.');
            return 0;
        }
        
        if (!$this->confirm('Do you want to retry these webhooks?')) {
            $this->info('Retry cancelled.');
            return 0;
        }
        
        $this->info('Retrying webhooks...');
        $bar = $this->output->createProgressBar($webhooks->count());
        $bar->start();
        
        $successCount = 0;
        $failureCount = 0;
        
        foreach ($webhooks as $webhook) {
            try {
                // Reset webhook status
                $webhook->update([
                    'status' => WebhookEvent::STATUS_PENDING,
                    'error_message' => null
                ]);
                
                // Generate new correlation ID for retry
                $correlationId = Str::uuid()->toString();
                
                // Dispatch job to retry
                dispatch(new ProcessWebhookJob($webhook, $correlationId));
                
                $this->info(" Queued webhook {$webhook->id} for retry (correlation: {$correlationId})");
                $successCount++;
                
            } catch (\Exception $e) {
                $this->error(" Failed to queue webhook {$webhook->id}: {$e->getMessage()}");
                $failureCount++;
            }
            
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine();
        
        $this->info("Retry complete!");
        $this->info("Successfully queued: {$successCount}");
        
        if ($failureCount > 0) {
            $this->error("Failed to queue: {$failureCount}");
        }
        
        // Show statistics
        $this->newLine();
        $this->info("You can monitor the retry progress with:");
        $this->line("php artisan webhooks:status --status=processing");
        
        return 0;
    }
}