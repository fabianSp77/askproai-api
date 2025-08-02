<?php

namespace App\Jobs;

use App\Models\RetellAICallCampaign;
use App\Models\Customer;
use App\Services\MCP\RetellAIBridgeMCPServer;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;

class ProcessRetellAICampaignBatchJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected RetellAICallCampaign $campaign;
    public $timeout = 300; // 5 minutes
    public $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(RetellAICallCampaign $campaign)
    {
        $this->campaign = $campaign;
        $this->queue = 'campaigns-batch';
    }

    /**
     * Execute the job to create batches for the campaign.
     */
    public function handle(): void
    {
        Log::info('Starting batch processing for campaign', [
            'campaign_id' => $this->campaign->id,
            'total_targets' => $this->campaign->total_targets,
        ]);

        try {
            // Get target customers
            $customers = $this->getTargetCustomers();
            $totalCustomers = $customers->count();
            
            // Update total targets
            $this->campaign->update(['total_targets' => $totalCustomers]);
            
            if ($totalCustomers === 0) {
                Log::warning('No customers found for campaign', [
                    'campaign_id' => $this->campaign->id,
                ]);
                
                $this->campaign->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                    'results' => ['message' => 'No customers matched the campaign criteria'],
                ]);
                
                return;
            }

            // Create batch jobs
            $batchSize = config('retell-mcp.batch_processing.chunk_size', 20);
            $jobs = [];
            
            foreach ($customers->chunk($batchSize) as $customerChunk) {
                $jobs[] = new ProcessRetellAICallJob(
                    $this->campaign,
                    $customerChunk->pluck('id')->toArray()
                );
            }

            // Dispatch batch with callbacks
            $batch = Bus::batch($jobs)
                ->name("Campaign: {$this->campaign->name}")
                ->allowFailures()
                ->onQueue('campaigns')
                ->progress(function ($batch) {
                    $this->updateCampaignProgress($batch);
                })
                ->then(function ($batch) {
                    $this->campaignCompleted($batch);
                })
                ->catch(function ($batch, \Throwable $e) {
                    $this->campaignFailed($batch, $e);
                })
                ->finally(function ($batch) {
                    $this->campaignFinished($batch);
                })
                ->dispatch();

            // Store batch ID in campaign
            $this->campaign->update([
                'metadata' => array_merge($this->campaign->metadata ?? [], [
                    'batch_id' => $batch->id,
                    'batch_total_jobs' => count($jobs),
                    'batch_size' => $batchSize,
                ]),
                'status' => 'running',
                'started_at' => now(),
            ]);

            Log::info('Campaign batch created successfully', [
                'campaign_id' => $this->campaign->id,
                'batch_id' => $batch->id,
                'total_jobs' => count($jobs),
                'batch_size' => $batchSize,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to create campaign batch', [
                'campaign_id' => $this->campaign->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->campaign->update([
                'status' => 'failed',
                'completed_at' => now(),
                'results' => [
                    'error' => $e->getMessage(),
                    'failed_at' => now()->toISOString(),
                ],
            ]);

            throw $e;
        }
    }

    /**
     * Get target customers based on campaign criteria
     */
    protected function getTargetCustomers()
    {
        $query = Customer::where('company_id', $this->campaign->company_id);
        
        switch ($this->campaign->target_type) {
            case 'inactive_customers':
                $inactiveDays = $this->campaign->target_criteria['inactive_days'] ?? 90;
                $query->whereDoesntHave('appointments', function ($q) use ($inactiveDays) {
                    $q->where('created_at', '>=', now()->subDays($inactiveDays));
                });
                break;
                
            case 'custom_list':
                if (isset($this->campaign->target_criteria['customer_ids'])) {
                    $query->whereIn('id', $this->campaign->target_criteria['customer_ids']);
                }
                break;
                
            case 'all_customers':
            default:
                // No additional filters
                break;
        }
        
        // Apply additional filters
        if (isset($this->campaign->target_criteria['has_phone']) && $this->campaign->target_criteria['has_phone']) {
            $query->whereNotNull('phone')
                  ->where('phone', '!=', '');
        }
        
        // Order by last interaction
        $query->orderBy('created_at', 'desc');
        
        return $query->get(['id', 'full_name', 'email', 'phone']);
    }

    /**
     * Update campaign progress based on batch progress
     */
    protected function updateCampaignProgress($batch): void
    {
        $progress = $batch->progress();
        
        // Update campaign progress
        $this->campaign->refresh();
        
        // Calculate metrics from batch
        $processedJobs = $batch->processedJobs();
        $failedJobs = $batch->failedJobs;
        
        $metadata = $this->campaign->metadata ?? [];
        $metadata['batch_progress'] = $progress;
        $metadata['batch_processed_jobs'] = $processedJobs;
        $metadata['batch_failed_jobs'] = $failedJobs;
        
        $this->campaign->update([
            'metadata' => $metadata,
        ]);
        
        Log::info('Campaign progress updated', [
            'campaign_id' => $this->campaign->id,
            'batch_id' => $batch->id,
            'progress' => $progress,
            'processed_jobs' => $processedJobs,
            'failed_jobs' => $failedJobs,
        ]);
    }

    /**
     * Handle campaign completion
     */
    protected function campaignCompleted($batch): void
    {
        $this->campaign->refresh();
        
        $this->campaign->update([
            'status' => 'completed',
            'completed_at' => now(),
            'results' => [
                'total_processed' => $batch->totalJobs,
                'successful_jobs' => $batch->totalJobs - $batch->failedJobs,
                'failed_jobs' => $batch->failedJobs,
                'success_rate' => $this->campaign->success_rate,
                'duration_minutes' => $this->campaign->started_at->diffInMinutes(now()),
                'batch_id' => $batch->id,
            ],
        ]);
        
        Log::info('Campaign completed successfully', [
            'campaign_id' => $this->campaign->id,
            'batch_id' => $batch->id,
            'success_rate' => $this->campaign->success_rate,
        ]);
    }

    /**
     * Handle campaign failure
     */
    protected function campaignFailed($batch, \Throwable $e): void
    {
        $this->campaign->refresh();
        
        $this->campaign->update([
            'status' => 'failed',
            'completed_at' => now(),
            'results' => [
                'error' => $e->getMessage(),
                'failed_at' => now()->toISOString(),
                'batch_id' => $batch->id,
                'processed_before_failure' => $batch->processedJobs(),
                'total_jobs' => $batch->totalJobs,
            ],
        ]);
        
        Log::error('Campaign batch failed', [
            'campaign_id' => $this->campaign->id,
            'batch_id' => $batch->id,
            'error' => $e->getMessage(),
        ]);
    }

    /**
     * Clean up after campaign finishes
     */
    protected function campaignFinished($batch): void
    {
        // Send notification or perform cleanup
        Log::info('Campaign batch finished', [
            'campaign_id' => $this->campaign->id,
            'batch_id' => $batch->id,
            'status' => $this->campaign->fresh()->status,
        ]);
    }
}