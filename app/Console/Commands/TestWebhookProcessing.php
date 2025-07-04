<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\ProcessRetellCallEndedJob;
use App\Services\Webhook\WebhookCompanyResolver;

class TestWebhookProcessing extends Command
{
    protected $signature = 'webhook:test {--sync : Process synchronously}';
    protected $description = 'Test webhook processing with company context';

    public function handle()
    {
        $this->info('Testing webhook processing...');
        
        // Test webhook payload
        $payload = [
            'event' => 'call_ended',
            'call' => [
                'call_id' => 'test_' . time(),
                'from_number' => '+491701234567',
                'to_number' => '+493083793369',
                'agent_id' => 'test_agent',
                'duration' => 120,
                'start_timestamp' => now()->subMinutes(2)->toIso8601String(),
                'end_timestamp' => now()->toIso8601String(),
                'call_status' => 'completed',
                'transcript' => 'Test call transcript',
                'summary' => 'Test call summary'
            ]
        ];
        
        // Resolve company
        $resolver = app(WebhookCompanyResolver::class);
        $companyId = $resolver->resolveFromWebhook($payload);
        
        $this->info("Resolved company ID: " . ($companyId ?? 'NULL'));
        
        // Create job with company context
        $job = new ProcessRetellCallEndedJob($payload);
        if ($companyId) {
            $job->setCompanyId($companyId);
        }
        
        if ($this->option('sync')) {
            // Process synchronously
            $this->info('Processing synchronously...');
            try {
                $job->handle();
                $this->info('✅ Job processed successfully!');
            } catch (\Exception $e) {
                $this->error('❌ Job failed: ' . $e->getMessage());
                $this->error($e->getTraceAsString());
            }
        } else {
            // Dispatch to queue
            dispatch($job)->onQueue('webhooks');
            $this->info('Job dispatched to queue. Check Horizon for results.');
        }
        
        return 0;
    }
}