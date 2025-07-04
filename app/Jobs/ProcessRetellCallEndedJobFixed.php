<?php

namespace App\Jobs;

use App\Models\Call;
use App\Models\Company;
use App\Models\WebhookEvent;
use App\Services\PhoneNumberResolver;
use App\Services\AppointmentBookingService;
use App\Jobs\AnalyzeCallSentimentJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Scopes\TenantScope;

class ProcessRetellCallEndedJobFixed implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [10, 30, 60];
    
    protected array $data;
    protected ?int $webhookEventId = null;
    protected ?string $correlationId = null;
    
    public function __construct(array $data, ?int $webhookEventId = null, ?string $correlationId = null)
    {
        $this->data = $data;
        $this->webhookEventId = $webhookEventId;
        $this->correlationId = $correlationId ?? \Illuminate\Support\Str::uuid()->toString();
        $this->queue = 'webhooks';
    }

    public function handle()
    {
        Log::info('Processing Retell call_ended webhook (Fixed)', [
            'call_id' => $this->data['call']['call_id'] ?? 'unknown',
            'event' => $this->data['event'] ?? 'unknown',
            'correlation_id' => $this->correlationId,
            'webhook_event_id' => $this->webhookEventId,
        ]);

        // Mark webhook event as processing
        if ($this->webhookEventId) {
            $webhookEvent = WebhookEvent::withoutGlobalScope(TenantScope::class)->find($this->webhookEventId);
            if ($webhookEvent) {
                $webhookEvent->status = 'processing';
                $webhookEvent->save();
            }
        }

        try {
            // Get the first company as fallback (temporary solution)
            $company = Company::withoutGlobalScope(TenantScope::class)->first();
            if (!$company) {
                throw new \Exception('No company found in system');
            }

            // Extract call data
            $callData = $this->data['call'] ?? $this->data;
            
            // Check if call already exists
            $existingCall = Call::withoutGlobalScope(TenantScope::class)
                ->where('retell_call_id', $callData['call_id'])
                ->first();
                
            if ($existingCall) {
                Log::info('Call already exists, updating', [
                    'call_id' => $existingCall->id,
                    'retell_call_id' => $callData['call_id']
                ]);
                
                // Update existing call
                $call = $existingCall;
            } else {
                // Create new call
                $call = new Call();
                $call->company_id = $company->id; // Set company ID explicitly
            }

            // Set call data
            $call->retell_call_id = $callData['call_id'] ?? null;
            $call->call_type = $callData['call_type'] ?? 'inbound';
            $call->from_number = $callData['from_number'] ?? null;
            $call->to_number = $callData['to_number'] ?? null;
            $call->direction = $callData['direction'] ?? 'inbound';
            $call->call_status = $callData['call_status'] ?? 'ended';
            $call->agent_id = $callData['agent_id'] ?? null;
            $call->metadata = $callData['metadata'] ?? [];
            $call->transcript = $callData['transcript'] ?? null;
            $call->transcript_object = $callData['transcript_object'] ?? null;
            $call->recording_url = $callData['recording_url'] ?? null;
            $call->public_log_url = $callData['public_log_url'] ?? null;
            $call->webhook_data = $this->data;
            
            // Handle timestamps
            if (isset($callData['start_timestamp'])) {
                $call->start_timestamp = \Carbon\Carbon::createFromTimestampMs($callData['start_timestamp']);
            }
            if (isset($callData['end_timestamp'])) {
                $call->end_timestamp = \Carbon\Carbon::createFromTimestampMs($callData['end_timestamp']);
                
                // Calculate duration
                if ($call->start_timestamp && $call->end_timestamp) {
                    $duration = $call->end_timestamp->diffInSeconds($call->start_timestamp);
                    // Ensure positive duration
                    $call->duration_sec = abs($duration);
                }
            }
            
            // Handle call analysis
            if (isset($callData['call_analysis'])) {
                $analysis = $callData['call_analysis'];
                $call->analysis = $analysis;
                
                // Extract summary
                if (isset($analysis['call_summary'])) {
                    $call->notes = $analysis['call_summary'];
                }
            }
            
            // Handle dynamic variables
            if (isset($callData['retell_llm_dynamic_variables'])) {
                $dynamicVars = $callData['retell_llm_dynamic_variables'];
                $call->retell_dynamic_variables = $dynamicVars;
                
                // Check for appointment data
                if (isset($dynamicVars['appointment_date']) || isset($dynamicVars['appointment_time'])) {
                    Log::info('Appointment data found in webhook', [
                        'date' => $dynamicVars['appointment_date'] ?? null,
                        'time' => $dynamicVars['appointment_time'] ?? null,
                        'service' => $dynamicVars['service_type'] ?? null,
                        'customer_name' => $dynamicVars['customer_name'] ?? null
                    ]);
                }
            }
            
            // Save the call without tenant scope
            $saved = $call->saveQuietly();
            
            if (!$saved) {
                throw new \Exception('Failed to save call record');
            }
            
            Log::info('Successfully processed Retell call_ended webhook', [
                'call_id' => $call->id,
                'retell_call_id' => $call->retell_call_id,
                'correlation_id' => $this->correlationId
            ]);
            
            // Mark webhook event as completed
            if ($this->webhookEventId) {
                $webhookEvent = WebhookEvent::withoutGlobalScope(TenantScope::class)->find($this->webhookEventId);
                if ($webhookEvent) {
                    $webhookEvent->status = 'completed';
                    $webhookEvent->save();
                }
            }
            
            // Dispatch sentiment analysis job if transcript exists
            if ($call->transcript) {
                Log::info('Dispatching sentiment analysis job for call', [
                    'call_id' => $call->id,
                    'retell_call_id' => $call->retell_call_id
                ]);
                
                dispatch(new AnalyzeCallSentimentJob($call->id));
            }
            
            // TODO: Process appointment booking if appointment data is present
            
        } catch (\Exception $e) {
            Log::error('Failed to process Retell call_ended webhook', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $this->data,
                'correlation_id' => $this->correlationId
            ]);
            
            // Mark webhook event as failed
            if ($this->webhookEventId) {
                $webhookEvent = WebhookEvent::withoutGlobalScope(TenantScope::class)->find($this->webhookEventId);
                if ($webhookEvent) {
                    $webhookEvent->status = 'failed';
                    $webhookEvent->error_message = $e->getMessage();
                    $webhookEvent->save();
                }
            }
            
            throw $e;
        }
    }
}