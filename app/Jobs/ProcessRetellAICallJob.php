<?php

namespace App\Jobs;

use App\Models\RetellAICallCampaign;
use App\Models\Customer;
use App\Models\Call;
use App\Services\MCP\RetellAIBridgeMCPServerEnhanced;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ProcessRetellAICallJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected RetellAICallCampaign $campaign;
    protected array $customerIds;
    
    public $timeout = 120; // 2 minutes per job
    public $tries = 3;
    public $backoff = [30, 60, 120]; // Exponential backoff

    /**
     * Create a new job instance.
     */
    public function __construct(RetellAICallCampaign $campaign, array $customerIds)
    {
        $this->campaign = $campaign;
        $this->customerIds = $customerIds;
        $this->queue = 'campaigns';
    }

    /**
     * Execute the job to process calls for a batch of customers.
     */
    public function handle(RetellAIBridgeMCPServerEnhanced $bridgeServer): void
    {
        // Check if campaign is still active
        $this->campaign->refresh();
        if (in_array($this->campaign->status, ['paused', 'cancelled', 'completed'])) {
            Log::info('Campaign is no longer active, skipping batch', [
                'campaign_id' => $this->campaign->id,
                'status' => $this->campaign->status,
            ]);
            return;
        }

        $successCount = 0;
        $failureCount = 0;
        $results = [];

        // Process each customer in this batch
        foreach ($this->customerIds as $customerId) {
            // Check rate limit using sliding window
            $rateLimitKey = "campaign_rate_limit:{$this->campaign->company_id}";
            $callsInWindow = Cache::get($rateLimitKey, 0);
            $maxCallsPerMinute = config('retell-mcp.rate_limiting.campaigns.calls_per_minute', 30);
            
            if ($callsInWindow >= $maxCallsPerMinute) {
                // Sleep until rate limit window allows
                $sleepTime = 60 - (time() % 60); // Wait until next minute
                Log::info('Rate limit reached, sleeping', [
                    'campaign_id' => $this->campaign->id,
                    'sleep_seconds' => $sleepTime,
                ]);
                sleep($sleepTime);
                Cache::forget($rateLimitKey);
            }

            try {
                $customer = Customer::find($customerId);
                if (!$customer) {
                    Log::warning('Customer not found', [
                        'customer_id' => $customerId,
                        'campaign_id' => $this->campaign->id,
                    ]);
                    $failureCount++;
                    continue;
                }

                // Skip if no phone number
                if (empty($customer->phone)) {
                    Log::warning('Customer has no phone number', [
                        'customer_id' => $customerId,
                        'campaign_id' => $this->campaign->id,
                    ]);
                    $failureCount++;
                    continue;
                }

                // Check if customer was already called in this campaign
                $existingCall = Call::where('company_id', $this->campaign->company_id)
                    ->where('customer_id', $customerId)
                    ->where('metadata->campaign_id', $this->campaign->id)
                    ->exists();
                    
                if ($existingCall) {
                    Log::info('Customer already called in this campaign', [
                        'customer_id' => $customerId,
                        'campaign_id' => $this->campaign->id,
                    ]);
                    continue;
                }

                // Prepare call parameters
                $callParams = [
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
                            'company_name' => $this->campaign->company->name,
                        ]
                    ),
                ];

                // Add custom variables based on campaign type
                if ($this->campaign->target_type === 'inactive_customers') {
                    $lastAppointment = $customer->appointments()
                        ->orderBy('scheduled_at', 'desc')
                        ->first();
                    
                    if ($lastAppointment) {
                        $callParams['dynamic_variables']['last_appointment_date'] = $lastAppointment->scheduled_at->format('F j, Y');
                        $callParams['dynamic_variables']['days_since_last_visit'] = $lastAppointment->scheduled_at->diffInDays(now());
                    }
                }

                // Create the outbound call
                $result = $bridgeServer->createOutboundCall($callParams);
                
                // Update rate limit counter
                Cache::increment($rateLimitKey);
                Cache::expire($rateLimitKey, 60);
                
                // Track success
                $successCount++;
                $results[] = [
                    'customer_id' => $customerId,
                    'call_id' => $result['call_id'] ?? null,
                    'status' => 'initiated',
                ];
                
                Log::info('Campaign call initiated', [
                    'campaign_id' => $this->campaign->id,
                    'customer_id' => $customerId,
                    'call_id' => $result['call_id'] ?? null,
                ]);

                // Small delay between calls to prevent overwhelming the API
                usleep(config('retell-mcp.batch_processing.delay_between_calls_ms', 500) * 1000);
                
            } catch (\Exception $e) {
                $failureCount++;
                $results[] = [
                    'customer_id' => $customerId,
                    'error' => $e->getMessage(),
                    'status' => 'failed',
                ];
                
                Log::error('Failed to create campaign call', [
                    'campaign_id' => $this->campaign->id,
                    'customer_id' => $customerId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                // If it's a rate limit error, throw to retry the job
                if (str_contains($e->getMessage(), 'rate limit') || $e->getCode() === 429) {
                    throw $e;
                }
            }
        }

        // Update campaign counters atomically
        if ($successCount > 0) {
            $this->campaign->increment('calls_completed', $successCount);
        }
        if ($failureCount > 0) {
            $this->campaign->increment('calls_failed', $failureCount);
        }

        // Log batch results
        Log::info('Campaign batch completed', [
            'campaign_id' => $this->campaign->id,
            'batch_size' => count($this->customerIds),
            'success_count' => $successCount,
            'failure_count' => $failureCount,
            'results' => $results,
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Campaign call job failed', [
            'campaign_id' => $this->campaign->id,
            'customer_ids' => $this->customerIds,
            'error' => $exception->getMessage(),
        ]);

        // Increment failure counter for all customers in this batch
        $this->campaign->increment('calls_failed', count($this->customerIds));
    }

    /**
     * Determine the time at which the job should timeout.
     */
    public function retryUntil(): \DateTime
    {
        // Retry for up to 1 hour
        return now()->addHour();
    }
}