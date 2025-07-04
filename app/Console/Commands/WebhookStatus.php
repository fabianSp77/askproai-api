<?php

namespace App\Console\Commands;

use App\Models\WebhookEvent;
use App\Services\Webhooks\WebhookEventLogger;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class WebhookStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'webhooks:status 
                            {--provider= : Filter by provider (stripe, retell, calcom)}
                            {--status= : Filter by status (pending, processing, completed, failed)}
                            {--since= : Show webhooks since (e.g., "1 hour ago")}
                            {--stats : Show statistics only}
                            {--watch : Continuously monitor webhooks}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor webhook processing status and statistics';

    protected WebhookEventLogger $logger;

    public function __construct(WebhookEventLogger $logger)
    {
        parent::__construct();
        $this->logger = $logger;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->option('watch')) {
            $this->watchWebhooks();
            return 0;
        }
        
        if ($this->option('stats')) {
            $this->showStatistics();
            return 0;
        }
        
        $this->showWebhooks();
        return 0;
    }
    
    /**
     * Show webhook list
     */
    protected function showWebhooks(): void
    {
        $query = WebhookEvent::query();
        
        // Apply filters
        if ($provider = $this->option('provider')) {
            $query->where('provider', $provider);
        }
        
        if ($status = $this->option('status')) {
            $query->where('status', $status);
        }
        
        if ($since = $this->option('since')) {
            $query->where('created_at', '>=', now()->parse($since));
        }
        
        // Order by most recent first
        $query->orderBy('created_at', 'desc')->limit(50);
        
        $webhooks = $query->get();
        
        if ($webhooks->isEmpty()) {
            $this->info('No webhooks found matching the criteria.');
            return;
        }
        
        $this->info("Recent webhooks ({$webhooks->count()} shown, newest first):");
        
        $this->table(
            ['ID', 'Provider', 'Event Type', 'Status', 'Company', 'Created', 'Retry', 'Error'],
            $webhooks->map(function ($webhook) {
                return [
                    $webhook->id,
                    $webhook->provider,
                    Str::limit($webhook->event_type, 25),
                    $this->formatStatus($webhook->status),
                    $webhook->company_id ?? 'N/A',
                    $webhook->created_at->diffForHumans(),
                    $webhook->retry_count > 0 ? $webhook->retry_count : '-',
                    $webhook->error_message ? Str::limit($webhook->error_message, 30) : '-'
                ];
            })
        );
    }
    
    /**
     * Show statistics
     */
    protected function showStatistics(): void
    {
        $since = $this->option('since') ? now()->parse($this->option('since')) : now()->subDay();
        $provider = $this->option('provider');
        
        $stats = $this->logger->getStatistics($provider, $since);
        
        $this->info("Webhook Statistics");
        $this->info("Period: Since " . $since->format('Y-m-d H:i:s'));
        if ($provider) {
            $this->info("Provider: " . $provider);
        }
        $this->newLine();
        
        // Overall stats
        $this->info("Overall Statistics:");
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Webhooks', $stats['total']],
                ['Success Rate', $stats['success_rate'] ?? 'N/A'],
                ['Failed Count', $stats['failed_count']],
                ['Duplicates Prevented', $stats['duplicates_prevented']],
                ['Average Retry Count', round($stats['average_retry_count'] ?? 0, 2)]
            ]
        );
        
        // Status breakdown
        $this->newLine();
        $this->info("By Status:");
        $this->table(
            ['Status', 'Count', 'Percentage'],
            collect($stats['by_status'])->map(function ($count, $status) use ($stats) {
                $percentage = $stats['total'] > 0 ? round($count / $stats['total'] * 100, 1) : 0;
                return [
                    $this->formatStatus($status),
                    $count,
                    $percentage . '%'
                ];
            })->toArray()
        );
        
        // Provider breakdown
        if (!$provider && !empty($stats['by_provider'])) {
            $this->newLine();
            $this->info("By Provider:");
            $this->table(
                ['Provider', 'Count', 'Percentage'],
                collect($stats['by_provider'])->map(function ($count, $provider) use ($stats) {
                    $percentage = $stats['total'] > 0 ? round($count / $stats['total'] * 100, 1) : 0;
                    return [
                        $provider,
                        $count,
                        $percentage . '%'
                    ];
                })->toArray()
            );
        }
        
        // Top event types
        if (!empty($stats['by_event_type'])) {
            $this->newLine();
            $this->info("Top Event Types:");
            $this->table(
                ['Event Type', 'Count'],
                collect($stats['by_event_type'])->map(function ($count, $eventType) {
                    return [$eventType, $count];
                })->take(10)->toArray()
            );
        }
    }
    
    /**
     * Watch webhooks in real-time
     */
    protected function watchWebhooks(): void
    {
        $this->info('Monitoring webhooks... (Press Ctrl+C to stop)');
        
        $lastId = WebhookEvent::max('id') ?? 0;
        
        while (true) {
            $newWebhooks = WebhookEvent::where('id', '>', $lastId)
                ->orderBy('id')
                ->get();
            
            foreach ($newWebhooks as $webhook) {
                $this->line(sprintf(
                    '[%s] %s %s %s - %s (Company: %s)',
                    $webhook->created_at->format('H:i:s'),
                    strtoupper($webhook->provider),
                    $webhook->event_type,
                    $this->formatStatus($webhook->status),
                    $webhook->correlation_id ?? 'N/A',
                    $webhook->company_id ?? 'N/A'
                ));
                
                if ($webhook->error_message) {
                    $this->error('  Error: ' . Str::limit($webhook->error_message, 100));
                }
                
                $lastId = $webhook->id;
            }
            
            sleep(2); // Check every 2 seconds
        }
    }
    
    /**
     * Format status for display
     */
    protected function formatStatus(string $status): string
    {
        return match ($status) {
            WebhookEvent::STATUS_PENDING => '<fg=yellow>PENDING</>',
            WebhookEvent::STATUS_PROCESSING => '<fg=blue>PROCESSING</>',
            WebhookEvent::STATUS_COMPLETED => '<fg=green>COMPLETED</>',
            WebhookEvent::STATUS_FAILED => '<fg=red>FAILED</>',
            WebhookEvent::STATUS_DUPLICATE => '<fg=gray>DUPLICATE</>',
            default => $status
        };
    }
}