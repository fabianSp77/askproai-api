<?php

namespace App\Jobs;

use App\Services\WebhookProcessor;
use App\Services\Webhook\EnhancedWebhookDeduplicationService;
use App\Services\Calcom\CalcomV2Service;
use App\Models\WebhookEvent;
use App\Models\Call;
use App\Models\Customer;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Appointment;
use App\Models\Staff;
use App\Models\Service;
use App\Models\PhoneNumber;
use App\Traits\CompanyAwareJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ProcessRetellWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, CompanyAwareJob;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 120;

    /**
     * Calculate the number of seconds to wait before retrying the job.
     *
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [10, 30, 90]; // 10s, 30s, 90s
    }

    /**
     * The webhook event to process
     */
    protected WebhookEvent $webhookEvent;

    /**
     * The correlation ID for tracing
     */
    protected string $correlationId;

    /**
     * Create a new job instance.
     */
    public function __construct(WebhookEvent $webhookEvent, string $correlationId)
    {
        $this->webhookEvent = $webhookEvent;
        $this->correlationId = $correlationId;
        
        // Set queue based on event type
        $eventType = $webhookEvent->event_type ?? 'unknown';
        
        // High priority events go to dedicated queue
        if (in_array($eventType, ['call_inbound', 'call_ended'])) {
            $this->onQueue('webhooks-high');
        } else {
            $this->onQueue('webhooks');
        }
    }

    /**
     * Execute the job.
     */
    public function handle(
        WebhookProcessor $webhookProcessor,
        EnhancedWebhookDeduplicationService $deduplicationService
    ): void {
        // Resolve company context first
        $payload = $this->webhookEvent->payload;
        $callData = $payload['call'] ?? $payload;
        
        // Try to resolve company from phone number
        if (isset($callData['to_number'])) {
            $phoneNumber = PhoneNumber::withoutGlobalScopes()
                ->where('number', $callData['to_number'])
                ->where('is_active', true)
                ->first();
                
            if ($phoneNumber && $phoneNumber->branch_id) {
                $branch = Branch::withoutGlobalScopes()->find($phoneNumber->branch_id);
                if ($branch) {
                    $this->companyId = $branch->company_id;
                }
            }
            
            if (!$this->companyId) {
                // Try direct branch lookup
                $branch = Branch::withoutGlobalScopes()
                    ->where('phone_number', $callData['to_number'])
                    ->where('is_active', true)
                    ->first();
                    
                if ($branch) {
                    $this->companyId = $branch->company_id;
                }
            }
        }
        
        // Apply company context
        $this->applyCompanyContext();
        
        Log::info('Processing Retell webhook job', [
            'webhook_event_id' => $this->webhookEvent->id,
            'event_type' => $this->webhookEvent->event_type,
            'correlation_id' => $this->correlationId,
            'company_id' => $this->companyId,
            'attempt' => $this->attempts()
        ]);

        try {
            // Double-check deduplication (belt and suspenders)
            $payload = $this->webhookEvent->payload;
            $eventType = $this->webhookEvent->event_type;
            
            // Process based on event type
            switch ($eventType) {
                case 'call_ended':
                    $this->processCallEnded($payload);
                    break;
                    
                case 'call_started':
                    $this->processCallStarted($payload);
                    break;
                    
                case 'call_analyzed':
                    $this->processCallAnalyzed($payload);
                    break;
                    
                case 'call_failed':
                    $this->processCallFailed($payload);
                    break;
                    
                default:
                    Log::warning('Unknown Retell event type', [
                        'event_type' => $eventType,
                        'correlation_id' => $this->correlationId
                    ]);
            }
            
            // Mark webhook as successfully processed
            $this->webhookEvent->update([
                'status' => 'completed',
                'processed_at' => now(),
                'attempts' => $this->attempts()
            ]);
            
            // Mark in deduplication service
            $request = request();
            $request->merge($payload);
            $deduplicationService->markAsCompleted('retell', $request, [
                'job_id' => $this->job->uuid(),
                'webhook_event_id' => $this->webhookEvent->id
            ]);
            
            Log::info('Retell webhook processed successfully', [
                'webhook_event_id' => $this->webhookEvent->id,
                'event_type' => $eventType,
                'correlation_id' => $this->correlationId
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to process Retell webhook job', [
                'webhook_event_id' => $this->webhookEvent->id,
                'error' => $e->getMessage(),
                'correlation_id' => $this->correlationId,
                'attempt' => $this->attempts(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Update webhook event with error
            $this->webhookEvent->update([
                'status' => 'failed',
                'error' => $e->getMessage(),
                'failed_at' => now(),
                'attempts' => $this->attempts()
            ]);
            
            // Mark as failed in deduplication service
            $request = request();
            $request->merge($payload ?? []);
            $deduplicationService->markAsFailed('retell', $request, $e->getMessage());
            
            // Clear company context
            $this->clearCompanyContext();
            
            // Re-throw to trigger retry logic
            throw $e;
        } finally {
            // Always clear company context
            $this->clearCompanyContext();
        }
    }

    /**
     * Process call_ended event
     */
    protected function processCallEnded(array $payload): void
    {
        $callId = $payload['call_id'] ?? null;
        
        if (!$callId) {
            throw new \Exception('Missing call_id in call_ended event');
        }
        
        Log::info('Processing call_ended event', [
            'call_id' => $callId,
            'correlation_id' => $this->correlationId
        ]);
        
        DB::transaction(function () use ($payload, $callId) {
            // Find or create call record
            $call = Call::where('retell_call_id', $callId)->first();
            
            if (!$call) {
                // Create new call record
                $call = $this->createCallFromPayload($payload);
            }
            
            // Update call with end data
            $callData = $payload['call'] ?? [];
            $call->update([
                'ended_at' => isset($callData['end_timestamp']) 
                    ? Carbon::createFromTimestampMs($callData['end_timestamp']) 
                    : now(),
                'duration_seconds' => $callData['call_duration'] ?? null,
                'recording_url' => $callData['recording_url'] ?? null,
                'public_log_url' => $callData['public_log_url'] ?? null,
                'disconnection_reason' => $callData['disconnection_reason'] ?? null,
                'status' => 'completed',
                'transcript' => $callData['transcript'] ?? null,
                'transcript_with_tools' => $callData['transcript_with_tools'] ?? null,
                'is_ai_speaking_at_disconnect' => $callData['is_ai_speaking_at_disconnect'] ?? false,
                'latency_p50' => $callData['latency']['p50'] ?? null,
                'latency_p95' => $callData['latency']['p95'] ?? null,
                'interruption_p50' => $callData['interruption']['p50'] ?? null,
                'response_time_p50' => $callData['response_time']['p50'] ?? null,
            ]);
            
            // Process dynamic variables for appointment booking
            $dynamicVars = $callData['retell_llm_dynamic_variables'] ?? [];
            
            if ($this->shouldCreateAppointment($dynamicVars)) {
                $this->createAppointmentFromCall($call, $dynamicVars);
            }
            
            // Update customer if needed
            if ($call->customer_id) {
                $customer = Customer::find($call->customer_id);
                if ($customer) {
                    $customer->update([
                        'last_contacted_at' => now(),
                        'total_calls' => DB::raw('total_calls + 1')
                    ]);
                }
            }
        });
    }

    /**
     * Process call_started event
     */
    protected function processCallStarted(array $payload): void
    {
        $callId = $payload['call_id'] ?? null;
        
        if (!$callId) {
            throw new \Exception('Missing call_id in call_started event');
        }
        
        Log::info('Processing call_started event', [
            'call_id' => $callId,
            'correlation_id' => $this->correlationId
        ]);
        
        // Create or update call record
        $callData = $payload['call'] ?? [];
        
        $call = Call::updateOrCreate(
            ['retell_call_id' => $callId],
            [
                'from_number' => $callData['from_number'] ?? null,
                'to_number' => $callData['to_number'] ?? null,
                'direction' => $callData['direction'] ?? 'inbound',
                'started_at' => isset($callData['start_timestamp']) 
                    ? Carbon::createFromTimestampMs($callData['start_timestamp']) 
                    : now(),
                'status' => 'in_progress',
                'agent_id' => $callData['agent_id'] ?? null,
                'metadata' => $callData['metadata'] ?? [],
                'correlation_id' => $this->correlationId,
            ]
        );
        
        // Try to identify company and customer
        $this->identifyCallContext($call);
    }

    /**
     * Process call_analyzed event
     */
    protected function processCallAnalyzed(array $payload): void
    {
        $callId = $payload['call_id'] ?? null;
        
        if (!$callId) {
            throw new \Exception('Missing call_id in call_analyzed event');
        }
        
        Log::info('Processing call_analyzed event', [
            'call_id' => $callId,
            'correlation_id' => $this->correlationId
        ]);
        
        $call = Call::where('retell_call_id', $callId)->first();
        
        if (!$call) {
            Log::warning('Call not found for call_analyzed event', [
                'call_id' => $callId
            ]);
            return;
        }
        
        // Update call with analysis data
        $analysisData = $payload['analysis'] ?? [];
        
        $call->update([
            'call_summary' => $analysisData['call_summary'] ?? null,
            'sentiment' => $analysisData['sentiment'] ?? null,
            'intent_detection' => $analysisData['intent'] ?? null,
            'key_points' => $analysisData['key_points'] ?? [],
            'action_items' => $analysisData['action_items'] ?? [],
            'analyzed_at' => now(),
        ]);
    }

    /**
     * Process call_failed event
     */
    protected function processCallFailed(array $payload): void
    {
        $callId = $payload['call_id'] ?? null;
        
        if (!$callId) {
            throw new \Exception('Missing call_id in call_failed event');
        }
        
        Log::warning('Processing call_failed event', [
            'call_id' => $callId,
            'error' => $payload['error'] ?? 'Unknown error',
            'correlation_id' => $this->correlationId
        ]);
        
        $call = Call::where('retell_call_id', $callId)->first();
        
        if ($call) {
            $call->update([
                'status' => 'failed',
                'error_message' => $payload['error'] ?? 'Unknown error',
                'failed_at' => now(),
            ]);
        }
    }

    /**
     * Create call record from payload
     */
    protected function createCallFromPayload(array $payload): Call
    {
        $callData = $payload['call'] ?? [];
        
        $call = Call::create([
            'retell_call_id' => $payload['call_id'],
            'from_number' => $callData['from_number'] ?? null,
            'to_number' => $callData['to_number'] ?? null,
            'direction' => $callData['direction'] ?? 'inbound',
            'started_at' => isset($callData['start_timestamp']) 
                ? Carbon::createFromTimestampMs($callData['start_timestamp']) 
                : now(),
            'agent_id' => $callData['agent_id'] ?? null,
            'metadata' => $callData['metadata'] ?? [],
            'correlation_id' => $this->correlationId,
            'status' => 'completed',
        ]);
        
        // Try to identify company and customer
        $this->identifyCallContext($call);
        
        return $call;
    }

    /**
     * Identify company and customer for the call
     */
    protected function identifyCallContext(Call $call): void
    {
        // Find branch by phone number
        $branch = Branch::where('phone_number', $call->to_number)
            ->where('is_active', true)
            ->first();
        
        if ($branch) {
            $call->branch_id = $branch->id;
            $call->company_id = $branch->company_id;
        } else {
            // Fallback to company phone number
            $company = Company::where('phone_number', $call->to_number)->first();
            if ($company) {
                $call->company_id = $company->id;
                // Use main branch
                $mainBranch = $company->branches()->where('is_main', true)->first();
                if ($mainBranch) {
                    $call->branch_id = $mainBranch->id;
                }
            }
        }
        
        // Find or create customer
        if ($call->from_number) {
            $customer = Customer::firstOrCreate(
                [
                    'phone' => $call->from_number,
                    'company_id' => $call->company_id
                ],
                [
                    'first_name' => 'Unknown',
                    'last_name' => 'Customer',
                    'source' => 'phone_call',
                    'created_via' => 'retell_webhook'
                ]
            );
            
            $call->customer_id = $customer->id;
        }
        
        $call->save();
    }

    /**
     * Check if appointment should be created from dynamic variables
     */
    protected function shouldCreateAppointment(array $dynamicVars): bool
    {
        // Check if booking was confirmed
        $bookingConfirmed = $dynamicVars['booking_confirmed'] ?? false;
        $hasDate = !empty($dynamicVars['datum']);
        $hasTime = !empty($dynamicVars['uhrzeit']);
        
        return $bookingConfirmed && $hasDate && $hasTime;
    }

    /**
     * Create appointment from call data
     */
    protected function createAppointmentFromCall(Call $call, array $dynamicVars): void
    {
        try {
            // Parse date and time
            $date = Carbon::parse($dynamicVars['datum']);
            $time = $dynamicVars['uhrzeit'];
            
            // Create datetime
            [$hours, $minutes] = explode(':', $time);
            $startTime = $date->copy()->setTime((int)$hours, (int)$minutes);
            
            // Get service and duration
            $service = null;
            $duration = 30; // Default duration
            
            if (!empty($dynamicVars['dienstleistung_id'])) {
                $service = Service::find($dynamicVars['dienstleistung_id']);
                if ($service) {
                    $duration = $service->duration ?? 30;
                }
            }
            
            $endTime = $startTime->copy()->addMinutes($duration);
            
            // Get staff member
            $staffId = $dynamicVars['mitarbeiter_id'] ?? null;
            
            // Create appointment
            $appointment = Appointment::create([
                'customer_id' => $call->customer_id,
                'branch_id' => $call->branch_id,
                'company_id' => $call->company_id,
                'staff_id' => $staffId,
                'service_id' => $service?->id,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'status' => 'scheduled',
                'notes' => "Automatisch gebucht über Telefon-KI\n" . 
                          "Kundenwunsch: " . ($dynamicVars['kundenwunsch'] ?? 'Nicht angegeben'),
                'source' => 'phone_ai',
                'call_id' => $call->id,
                'metadata' => [
                    'dynamic_variables' => $dynamicVars,
                    'call_id' => $call->retell_call_id,
                    'booked_via' => 'retell_ai'
                ]
            ]);
            
            // Update call with appointment reference
            $call->update(['appointment_id' => $appointment->id]);
            
            // Book in Cal.com if available
            if ($call->branch && $call->branch->calcom_event_type_id) {
                $this->bookInCalcom($appointment, $call->branch);
            }
            
            Log::info('Appointment created from call', [
                'appointment_id' => $appointment->id,
                'call_id' => $call->id,
                'correlation_id' => $this->correlationId
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to create appointment from call', [
                'call_id' => $call->id,
                'error' => $e->getMessage(),
                'dynamic_vars' => $dynamicVars,
                'correlation_id' => $this->correlationId
            ]);
            
            // Don't re-throw - we don't want to fail the entire webhook
        }
    }

    /**
     * Book appointment in Cal.com
     */
    protected function bookInCalcom(Appointment $appointment, Branch $branch): void
    {
        try {
            $company = $branch->company;
            $apiKey = $company->calcom_api_key ?? config('services.calcom.api_key');
            
            if (!$apiKey || !$branch->calcom_event_type_id) {
                Log::warning('Cal.com booking skipped - missing configuration', [
                    'branch_id' => $branch->id,
                    'has_api_key' => !empty($apiKey),
                    'has_event_type' => !empty($branch->calcom_event_type_id)
                ]);
                return;
            }
            
            $calcomService = new CalcomV2Service($apiKey);
            
            // Prepare booking data
            $bookingData = [
                'eventTypeId' => $branch->calcom_event_type_id,
                'start' => $appointment->start_time->toIso8601String(),
                'end' => $appointment->end_time->toIso8601String(),
                'name' => $appointment->customer->full_name,
                'email' => $appointment->customer->email ?? 'noreply@askproai.de',
                'phone' => $appointment->customer->phone,
                'notes' => $appointment->notes,
                'metadata' => [
                    'appointment_id' => $appointment->id,
                    'source' => 'phone_ai',
                    'branch_id' => $branch->id
                ]
            ];
            
            $result = $calcomService->createBooking($bookingData);
            
            if ($result['success']) {
                $appointment->update([
                    'calcom_booking_id' => $result['data']['id'],
                    'calcom_booking_uid' => $result['data']['uid'],
                    'external_id' => $result['data']['uid'],
                    'metadata' => array_merge($appointment->metadata ?? [], [
                        'calcom_booking' => $result['data']
                    ])
                ]);
                
                Log::info('Cal.com booking created', [
                    'appointment_id' => $appointment->id,
                    'calcom_booking_id' => $result['data']['id'],
                    'correlation_id' => $this->correlationId
                ]);
            } else {
                Log::error('Failed to create Cal.com booking', [
                    'appointment_id' => $appointment->id,
                    'error' => $result['message'] ?? 'Unknown error',
                    'correlation_id' => $this->correlationId
                ]);
            }
            
        } catch (\Exception $e) {
            Log::error('Exception creating Cal.com booking', [
                'appointment_id' => $appointment->id,
                'error' => $e->getMessage(),
                'correlation_id' => $this->correlationId
            ]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Retell webhook job failed permanently', [
            'webhook_event_id' => $this->webhookEvent->id,
            'error' => $exception->getMessage(),
            'correlation_id' => $this->correlationId,
            'attempts' => $this->attempts()
        ]);
        
        // Update webhook event
        $this->webhookEvent->update([
            'status' => 'failed_permanently',
            'error' => $exception->getMessage(),
            'failed_at' => now(),
            'attempts' => $this->attempts()
        ]);
        
        // Alert monitoring
        // TODO: Send alert to monitoring system
    }
}