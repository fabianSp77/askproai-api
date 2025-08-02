<?php

namespace App\Jobs;

use App\Models\RetellAICallCampaign;
use App\Services\MCP\RetellAIBridgeMCPServer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessRetellAICampaignJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected RetellAICallCampaign $campaign;
    
    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The maximum number of unhandled exceptions to allow before failing.
     *
     * @var int
     */
    public $maxExceptions = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(RetellAICallCampaign $campaign)
    {
        $this->campaign = $campaign;
    }

    /**
     * Execute the job.
     */
    public function handle(RetellAIBridgeMCPServer $bridgeServer): void
    {
        Log::info('Processing Retell AI campaign', [
            'campaign_id' => $this->campaign->id,
            'total_targets' => $this->campaign->total_targets,
        ]);

        try {
            // Get target customers
            $customers = $this->getTargetCustomers();
            
            // Process in batches to avoid memory issues
            $batchSize = 10;
            $processed = 0;
            
            foreach ($customers->chunk($batchSize) as $batch) {
                foreach ($batch as $customer) {
                    // Skip if no phone number
                    if (!$customer->phone) {
                        Log::warning('Skipping customer without phone', [
                            'customer_id' => $customer->id,
                            'campaign_id' => $this->campaign->id,
                        ]);
                        continue;
                    }
                    
                    try {
                        // Create outbound call
                        $result = $bridgeServer->createOutboundCall([
                            'company_id' => $this->campaign->company_id,
                            'to_number' => $customer->phone,
                            'agent_id' => $this->campaign->agent_id,
                            'campaign_id' => $this->campaign->id,
                            'customer_id' => $customer->id,
                            'purpose' => 'campaign_call',
                            'dynamic_variables' => array_merge(
                                $this->campaign->dynamic_variables ?? [],
                                [
                                    'customer_name' => $customer->full_name,
                                    'customer_email' => $customer->email,
                                    'campaign_name' => $this->campaign->name,
                                ]
                            ),
                        ]);
                        
                        // Increment completed counter
                        $this->campaign->increment('calls_completed');
                        
                    } catch (\Exception $e) {
                        Log::error('Failed to create campaign call', [
                            'campaign_id' => $this->campaign->id,
                            'customer_id' => $customer->id,
                            'error' => $e->getMessage(),
                        ]);
                        
                        // Increment failed counter
                        $this->campaign->increment('calls_failed');
                    }
                    
                    $processed++;
                    
                    // Add delay between calls to avoid overwhelming the system
                    if ($processed % 5 === 0) {
                        sleep(2); // 2 second delay every 5 calls
                    }
                }
                
                // Check if campaign was paused
                $this->campaign->refresh();
                if ($this->campaign->status === 'paused') {
                    Log::info('Campaign paused, stopping processing', [
                        'campaign_id' => $this->campaign->id,
                        'processed' => $processed,
                    ]);
                    return;
                }
            }
            
            // Mark campaign as completed
            $this->campaign->update([
                'status' => 'completed',
                'completed_at' => now(),
                'results' => [
                    'total_processed' => $processed,
                    'success_rate' => $this->campaign->success_rate,
                    'duration_minutes' => $this->campaign->started_at->diffInMinutes(now()),
                ],
            ]);
            
            Log::info('Campaign completed successfully', [
                'campaign_id' => $this->campaign->id,
                'total_processed' => $processed,
                'success_rate' => $this->campaign->success_rate,
            ]);
            
        } catch (\Exception $e) {
            Log::error('Campaign processing failed', [
                'campaign_id' => $this->campaign->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            // Mark campaign as failed
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
        $query = $this->campaign->company->customers();
        
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
            $query->whereNotNull('phone');
        }
        
        return $query->get();
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Campaign job failed completely', [
            'campaign_id' => $this->campaign->id,
            'error' => $exception->getMessage(),
        ]);
        
        // Update campaign status
        $this->campaign->update([
            'status' => 'failed',
            'completed_at' => now(),
            'results' => [
                'error' => $exception->getMessage(),
                'failed_at' => now()->toISOString(),
            ],
        ]);
    }
}