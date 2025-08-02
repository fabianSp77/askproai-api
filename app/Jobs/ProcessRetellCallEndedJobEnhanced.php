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

class ProcessRetellCallEndedJobEnhanced implements ShouldQueue
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
        Log::info('Processing Retell call_ended webhook (Enhanced)', [
            'call_id' => $this->data['call']['call_id'] ?? 'unknown',
            'event' => $this->data['event'] ?? 'unknown',
            'correlation_id' => $this->correlationId,
            'webhook_event_id' => $this->webhookEventId,
        ]);

        // Mark webhook event as processing
        if ($this->webhookEventId) {
            $webhookEvent = WebhookEvent::find($this->webhookEventId);
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

            // Set basic call data
            $call->retell_call_id = $callData['call_id'] ?? null;
            $call->call_id = $callData['call_id'] ?? null; // Ensure both fields are set
            $call->call_type = $callData['call_type'] ?? 'phone_call';
            $call->from_number = $callData['from_number'] ?? null;
            $call->to_number = $callData['to_number'] ?? null;
            $call->direction = $callData['direction'] ?? 'inbound';
            $call->call_status = $callData['call_status'] ?? 'ended';
            $call->agent_id = $callData['agent_id'] ?? null;
            $call->retell_agent_id = $callData['agent_id'] ?? null; // Set both fields
            $call->agent_version = $callData['agent_version'] ?? null;
            $call->metadata = $callData['metadata'] ?? [];
            $call->transcript = $callData['transcript'] ?? null;
            $call->transcript_object = $callData['transcript_object'] ?? null;
            $call->transcript_with_tools = $callData['transcript_with_tool_calls'] ?? null;
            $call->recording_url = $callData['recording_url'] ?? null;
            $call->public_log_url = $callData['public_log_url'] ?? null;
            $call->webhook_data = $this->data;
            $call->disconnection_reason = $callData['disconnection_reason'] ?? null;
            $call->session_outcome = $callData['session_outcome'] ?? null;
            $call->end_to_end_latency = $callData['end_to_end_latency'] ?? null;
            $call->cost = $callData['cost'] ?? null;
            
            // Handle timestamps
            if (isset($callData['start_timestamp'])) {
                $call->start_timestamp = \Carbon\Carbon::createFromTimestampMs($callData['start_timestamp']);
            }
            if (isset($callData['end_timestamp'])) {
                $call->end_timestamp = \Carbon\Carbon::createFromTimestampMs($callData['end_timestamp']);
            }
            
            // Handle duration - prefer call_length from analysis
            if (isset($callData['call_analysis']['call_length'])) {
                // Use the duration from Retell's analysis
                $call->duration_sec = (int)$callData['call_analysis']['call_length'];
            } elseif ($call->start_timestamp && $call->end_timestamp) {
                // Fallback: calculate from timestamps
                $duration = abs($call->end_timestamp->diffInSeconds($call->start_timestamp));
                $call->duration_sec = $duration;
            }
            
            // Store duration in milliseconds if available
            if (isset($callData['duration_ms'])) {
                $call->duration_ms = $callData['duration_ms'];
            } elseif (isset($callData['start_timestamp']) && isset($callData['end_timestamp'])) {
                $call->duration_ms = abs($callData['end_timestamp'] - $callData['start_timestamp']);
            }
            
            // Handle call analysis
            if (isset($callData['call_analysis'])) {
                $analysis = $callData['call_analysis'];
                $call->analysis = $analysis;
                
                // Extract summary
                if (isset($analysis['call_summary'])) {
                    $call->summary = $analysis['call_summary'];
                    $call->notes = $analysis['call_summary']; // Also save to notes
                }
                
                // Extract sentiment
                if (isset($analysis['user_sentiment'])) {
                    $call->sentiment = $analysis['user_sentiment'];
                }
            }
            
            // Handle latency metrics
            if (isset($callData['latency'])) {
                $call->latency_metrics = $callData['latency'];
            }
            
            // Handle dynamic variables with proper parsing
            if (isset($callData['retell_llm_dynamic_variables'])) {
                $dynamicVars = $callData['retell_llm_dynamic_variables'];
                $call->retell_dynamic_variables = $dynamicVars;
                
                // Parse and normalize dynamic variables
                $parsedVars = $this->parseDynamicVariables($dynamicVars);
                
                // Extract customer data
                if (!empty($parsedVars['name'])) {
                    $call->name = $parsedVars['name'];
                }
                
                if (!empty($parsedVars['email'])) {
                    $call->email = $parsedVars['email'];
                }
                
                if (!empty($parsedVars['phone_number'])) {
                    $call->phone_number = $parsedVars['phone_number'];
                } elseif (!empty($call->from_number)) {
                    $call->phone_number = $call->from_number;
                }
                
                // Extract appointment data
                if (!empty($parsedVars['datum_termin'])) {
                    $call->datum_termin = $parsedVars['datum_termin'];
                }
                
                if (!empty($parsedVars['uhrzeit_termin'])) {
                    $call->uhrzeit_termin = $parsedVars['uhrzeit_termin'];
                }
                
                if (!empty($parsedVars['dienstleistung'])) {
                    $call->dienstleistung = $parsedVars['dienstleistung'];
                }
                
                if (!empty($parsedVars['reason_for_visit'])) {
                    $call->reason_for_visit = $parsedVars['reason_for_visit'];
                }
                
                if (!empty($parsedVars['health_insurance_company'])) {
                    $call->health_insurance_company = $parsedVars['health_insurance_company'];
                }
                
                // Check if appointment was made
                $call->appointment_made = $this->checkAppointmentMade($parsedVars);
                
                // Log appointment data if found
                if ($call->appointment_made) {
                    Log::info('Appointment data found in webhook', [
                        'date' => $call->datum_termin,
                        'time' => $call->uhrzeit_termin,
                        'service' => $call->dienstleistung,
                        'customer' => $call->name,
                        'phone' => $call->phone_number
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
                'duration_sec' => $call->duration_sec,
                'appointment_made' => $call->appointment_made,
                'correlation_id' => $this->correlationId
            ]);
            
            // Mark webhook event as completed
            if ($this->webhookEventId) {
                $webhookEvent = WebhookEvent::find($this->webhookEventId);
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
            if ($call->appointment_made) {
                // Future: Dispatch appointment creation job
                Log::info('Appointment booking detected, ready for processing', [
                    'call_id' => $call->id,
                    'date' => $call->datum_termin,
                    'time' => $call->uhrzeit_termin,
                    'service' => $call->dienstleistung
                ]);
            }
            
        } catch (\Exception $e) {
            Log::error('Failed to process Retell call_ended webhook', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $this->data,
                'correlation_id' => $this->correlationId
            ]);
            
            // Mark webhook event as failed
            if ($this->webhookEventId) {
                $webhookEvent = WebhookEvent::find($this->webhookEventId);
                if ($webhookEvent) {
                    $webhookEvent->status = 'failed';
                    $webhookEvent->error_message = $e->getMessage();
                    $webhookEvent->save();
                }
            }
            
            throw $e;
        }
    }
    
    /**
     * Parse and normalize dynamic variables from Retell
     */
    private function parseDynamicVariables($dynamicVars): array
    {
        $normalized = [];
        
        foreach ($dynamicVars as $key => $value) {
            // Skip template variables
            if (is_string($value) && str_contains($value, '{{')) {
                continue;
            }
            
            // Remove leading underscores
            $cleanKey = ltrim($key, '_');
            
            // Replace double underscores with single
            $cleanKey = str_replace('__', '_', $cleanKey);
            
            // Map to our field names
            $fieldMap = [
                'datum_termin' => 'datum_termin',
                'uhrzeit_termin' => 'uhrzeit_termin',
                'appointment_date_time' => 'appointment_datetime',
                'patient_full_name' => 'name',
                'caller_full_name' => 'name',
                'name' => 'name',
                'telefonnummer_anrufer' => 'phone_number',
                'caller_phone' => 'phone_number',
                'reason_for_visit' => 'dienstleistung',
                'dienstleistung' => 'dienstleistung',
                'zusammenfassung_anruf' => 'summary',
                'information_anruf' => 'notes',
                'email' => 'email',
                'health_insurance_company' => 'health_insurance_company',
                'insurance_type' => 'health_insurance_company'
            ];
            
            if (isset($fieldMap[$cleanKey])) {
                $normalized[$fieldMap[$cleanKey]] = $value;
            } else {
                // Keep original key if no mapping
                $normalized[$cleanKey] = $value;
            }
        }
        
        return $normalized;
    }
    
    /**
     * Check if appointment was successfully made
     */
    private function checkAppointmentMade($parsedVars): bool
    {
        // Check for explicit appointment_made flag
        if (isset($parsedVars['appointment_made'])) {
            return filter_var($parsedVars['appointment_made'], FILTER_VALIDATE_BOOLEAN);
        }
        
        // Check if we have minimum required appointment data
        $hasDate = !empty($parsedVars['datum_termin']) || !empty($parsedVars['appointment_datetime']);
        $hasTime = !empty($parsedVars['uhrzeit_termin']) || !empty($parsedVars['appointment_datetime']);
        $hasService = !empty($parsedVars['dienstleistung']);
        $hasName = !empty($parsedVars['name']);
        
        return $hasDate && $hasTime && $hasService && $hasName;
    }
}