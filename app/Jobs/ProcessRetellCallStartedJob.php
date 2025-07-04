<?php

namespace App\Jobs;

use App\Models\Call;
use App\Models\Company;
use App\Events\CallCreated;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ProcessRetellCallStartedJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected array $callData;
    protected int $companyId;

    /**
     * Create a new job instance.
     */
    public function __construct(array $callData, int $companyId)
    {
        $this->callData = $callData;
        $this->companyId = $companyId;
        $this->queue = 'webhooks';
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Resolve full context including branch
        $phoneResolver = app(\App\Services\PhoneNumberResolver::class);
        $context = $phoneResolver->resolveFromWebhook([
            'to' => $this->callData['to_number'] ?? null,
            'from' => $this->callData['from_number'] ?? null,
            'agent_id' => $this->callData['agent_id'] ?? null,
            'metadata' => $this->callData['metadata'] ?? []
        ]);
        
        // Use resolved company_id if available, otherwise use the one from constructor
        $companyId = $context['company_id'] ?? $this->companyId;
        $branchId = $context['branch_id'] ?? null;
        
        Log::info('ProcessRetellCallStartedJob: Starting', [
            'call_id' => $this->callData['call_id'] ?? null,
            'company_id' => $companyId,
            'branch_id' => $branchId,
            'resolution_method' => $context['resolution_method'] ?? 'unknown'
        ]);

        try {
            // Create or update call record for in-progress call
            $call = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
                ->updateOrCreate(
                    [
                        'retell_call_id' => $this->callData['call_id']
                    ],
                    [
                        'company_id' => $companyId,
                        'branch_id' => $branchId,
                        'call_id' => $this->callData['call_id'],
                        'agent_id' => $this->callData['agent_id'] ?? null,
                        'retell_agent_id' => $this->callData['agent_id'] ?? null,
                        'call_type' => $this->callData['call_type'] ?? 'inbound',
                        'from_number' => $this->callData['from_number'] ?? null,
                        'to_number' => $this->callData['to_number'] ?? null,
                        'direction' => $this->callData['direction'] ?? 'inbound',
                        'call_status' => 'in_progress',
                        'start_timestamp' => isset($this->callData['start_timestamp']) 
                            ? Carbon::createFromTimestampMs($this->callData['start_timestamp'])->addHours(2)
                            : now(),
                        'metadata' => $this->callData['metadata'] ?? [],
                        'webhook_data' => $this->callData,
                        // Leave end_timestamp null for in-progress calls
                        'end_timestamp' => null,
                        'duration_sec' => 0,
                        'session_outcome' => 'In Progress'
                    ]
                );

            Log::info('ProcessRetellCallStartedJob: Call record created/updated', [
                'id' => $call->id,
                'retell_call_id' => $call->retell_call_id,
                'status' => $call->call_status
            ]);

            // Broadcast call created event for real-time updates
            event(new CallCreated($call));

            // Set company context for any subsequent operations
            app()->instance('company', Company::find($companyId));

        } catch (\Exception $e) {
            Log::error('ProcessRetellCallStartedJob: Failed to process call', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'call_data' => $this->callData
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessRetellCallStartedJob: Job failed', [
            'error' => $exception->getMessage(),
            'call_id' => $this->callData['call_id'] ?? null,
            'company_id' => $this->companyId
        ]);
    }
}