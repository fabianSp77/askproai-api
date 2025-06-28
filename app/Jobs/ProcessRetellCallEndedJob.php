<?php

namespace App\Jobs;

use App\Models\Call;
use App\Models\RetellWebhook;
use App\Models\WebhookEvent;
use App\Services\PhoneNumberResolver;
use App\Services\CurrencyConverter;
use App\Services\AppointmentBookingService;
use App\Traits\CompanyAwareJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ProcessRetellCallEndedJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, CompanyAwareJob;

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
        // Apply company context from trait
        $this->applyCompanyContext();
        
        // Enhanced logging to debug data structure
        Log::info('Processing Retell call_ended webhook', [
            'call_id' => $this->data['call']['call_id'] ?? 'unknown',
            'event' => $this->data['event'] ?? 'unknown',
            'correlation_id' => $this->correlationId,
            'webhook_event_id' => $this->webhookEventId,
            'company_id' => $this->companyId,
            'has_call_data' => isset($this->data['call']),
            'has_retell_llm_dynamic_variables' => isset($this->data['call']['retell_llm_dynamic_variables']),
            'dynamic_vars_keys' => isset($this->data['call']['retell_llm_dynamic_variables']) 
                ? array_keys($this->data['call']['retell_llm_dynamic_variables']) 
                : [],
            'custom_fields' => array_filter(array_keys($this->data['call'] ?? []), fn($k) => strpos($k, '_') === 0)
        ]);

        // Mark webhook event as processing
        if ($this->webhookEventId) {
            $webhookEvent = WebhookEvent::find($this->webhookEventId);
            $webhookEvent?->markAsProcessing();
        }

        try {
            // If company context not set by trait, resolve it
            if (!$this->companyId) {
                // Resolve company context first to set tenant scope
                $callData = $this->data['call'] ?? $this->data;
                $resolver = new PhoneNumberResolver();
                $resolved = $resolver->resolveFromWebhook($callData);
                
                if ($resolved['company_id']) {
                    // Set the company context for this job
                    $this->companyId = $resolved['company_id'];
                    $this->applyCompanyContext();
                    Log::info('Set company context for webhook processing', [
                        'company_id' => $resolved['company_id'],
                        'resolution_method' => $resolved['resolution_method'] ?? 'unknown'
                    ]);
                } else {
                    Log::warning('Could not resolve company context from webhook', [
                        'call_id' => $callData['call_id'] ?? 'unknown'
                    ]);
                }
            }
            
            // Store raw webhook data
            $this->storeWebhookRecord();
            
            // Process call data
            $call = $this->processCallData();
            
            // Extract and store advanced metrics
            $this->processAdvancedMetrics($call);
            
            // Process transcript if available
            if (!empty($this->data['call']['transcript_object'])) {
                $this->processTranscript($call);
            }
            
            // Process appointment booking if appointment data is present
            $this->processAppointmentBooking($call);
            
            // Dispatch sentiment analysis job
            if ($call->transcript || $call->webhook_data) {
                AnalyzeCallSentimentJob::dispatch($call)->delay(now()->addSeconds(5));
                Log::info('Dispatched sentiment analysis job', [
                    'call_id' => $call->id,
                    'has_transcript' => !empty($call->transcript)
                ]);
            }
            
            // Update call status
            $call->update(['call_status' => 'analyzed']);
            
            Log::info('Successfully processed Retell call_ended webhook', [
                'call_id' => $call->id,
                'retell_call_id' => $call->retell_call_id,
                'correlation_id' => $this->correlationId
            ]);
            
            // Mark webhook event as completed
            if ($this->webhookEventId) {
                $webhookEvent = WebhookEvent::find($this->webhookEventId);
                $webhookEvent?->markAsCompleted();
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
                $webhookEvent?->markAsFailed($e->getMessage());
            }
            
            // Clear company context
            $this->clearCompanyContext();
            
            throw $e;
        } finally {
            // Always clear company context
            $this->clearCompanyContext();
        }
    }
    
    protected function storeWebhookRecord(): void
    {
        RetellWebhook::create([
            'event_type' => $this->data['event'] ?? 'call_ended',
            'payload' => $this->data,
            'provider' => 'retell',
            'processed_at' => now()
        ]);
    }
    
    protected function processCallData(): Call
    {
        $callData = $this->data['call'] ?? $this->data;
        $callId = $callData['call_id'] ?? 'retell_' . uniqid();
        
        // Resolve branch and company
        $resolver = new PhoneNumberResolver();
        $resolved = $resolver->resolveFromWebhook($callData);
        
        // Extract all available fields
        $attributes = [
            // Basic identification
            'retell_call_id' => $callId,
            'call_id' => $callId,
            'agent_id' => $callData['agent_id'] ?? null,
            
            // Phone numbers
            'from_number' => $callData['from_number'] ?? $callData['from'] ?? null,
            'to_number' => $callData['to_number'] ?? $callData['to'] ?? null,
            
            // Call details
            'call_type' => $callData['call_type'] ?? 'phone_call',
            'direction' => $callData['direction'] ?? 'inbound',
            'call_status' => $callData['call_status'] ?? 'ended',
            'disconnection_reason' => $callData['disconnection_reason'] ?? null,
            
            // Timestamps (handle different formats)
            'start_timestamp' => isset($callData['start_timestamp']) 
                ? $this->parseTimestamp($callData['start_timestamp']) 
                : null,
            'end_timestamp' => isset($callData['end_timestamp']) 
                ? $this->parseTimestamp($callData['end_timestamp']) 
                : null,
            
            // Duration
            'duration_sec' => $callData['duration'] ?? 
                (isset($callData['duration_ms']) ? round($callData['duration_ms'] / 1000) : 0),
            'duration_minutes' => isset($callData['duration']) 
                ? round($callData['duration'] / 60, 2) 
                : (isset($callData['duration_ms']) ? round($callData['duration_ms'] / 60000, 2) : 0),
            
            // Content
            'transcript' => $callData['transcript'] ?? null,
            'transcript_object' => $callData['transcript_object'] ?? null,
            'transcript_with_tools' => $callData['transcript_with_tool_calls'] ?? null,
            'summary' => $callData['summary'] ?? null,
            
            // URLs
            'audio_url' => $callData['recording_url'] ?? $callData['audio_url'] ?? null,
            'public_log_url' => $callData['public_log_url'] ?? null,
            
            // Costs
            'cost' => $callData['call_cost']['total_cost'] ?? $callData['cost'] ?? null,
            'cost_cents' => isset($callData['call_cost']['total_cost']) 
                ? intval($callData['call_cost']['total_cost'] * 100) 
                : null,
            
            // Metadata
            'metadata' => $callData['metadata'] ?? [],
            'retell_llm_dynamic_variables' => $callData['retell_llm_dynamic_variables'] ?? [],
            
            // Privacy
            'opt_out_sensitive_data' => $callData['opt_out_sensitive_data_storage'] ?? false,
            
            // Raw data
            'raw_data' => $callData,
            'webhook_data' => $this->data,
            
            // Multi-tenant
            'company_id' => $resolved['company_id'] ?? null,
            'branch_id' => $resolved['branch_id'] ?? null,
            
            // Additional Retell fields
            'agent_version' => $callData['agent_version'] ?? null,
            'cost' => isset($callData['call_cost']) 
                ? CurrencyConverter::convertRetellCostToEuros($callData['call_cost'])
                : null,
            'retell_cost' => isset($callData['call_cost']) 
                ? (is_array($callData['call_cost']) 
                    ? ($callData['call_cost']['combined_cost'] ?? $callData['call_cost']['total_cost'] ?? null) / 100
                    : $callData['call_cost'] / 100)
                : null,
            'custom_sip_headers' => $callData['custom_sip_headers'] ?? null,
        ];
        
        // Store custom fields in details
        $details = [];
        foreach ($callData as $key => $value) {
            if (strpos($key, '_') === 0) {
                $details[$key] = $value;
            }
        }
        if (!empty($details)) {
            $attributes['details'] = $details;
        }
        
        // Create or update call (tenant context already set in handle method)
        return Call::updateOrCreate(
            ['retell_call_id' => $callId],
            $attributes
        );
    }
    
    protected function processAdvancedMetrics(Call $call): void
    {
        $callData = $this->data['call'] ?? $this->data;
        $analysis = $call->analysis ?? [];
        
        // Latency metrics
        if (isset($callData['latency'])) {
            $analysis['latency'] = $callData['latency'];
        }
        
        // Cost breakdown (convert cents to euros)
        if (isset($callData['call_cost'])) {
            $analysis['cost_breakdown'] = CurrencyConverter::formatCostBreakdown($callData['call_cost']);
        }
        
        // LLM usage
        if (isset($callData['llm_token_usage'])) {
            $analysis['llm_usage'] = $callData['llm_token_usage'];
        }
        
        // Sentiment analysis
        if (isset($callData['user_sentiment'])) {
            $analysis['sentiment'] = $callData['user_sentiment'];
        }
        
        // Intent detection
        if (isset($callData['detected_intent'])) {
            $analysis['intent'] = $callData['detected_intent'];
        }
        
        // Extract entities from custom fields
        $entities = [];
        foreach ($callData as $key => $value) {
            if (strpos($key, '_') === 0 && !empty($value)) {
                $entityName = str_replace('_', '', $key);
                $entities[$entityName] = $value;
            }
        }
        if (!empty($entities)) {
            $analysis['entities'] = $entities;
        }
        
        $call->update(['analysis' => $analysis]);
    }
    
    protected function processTranscript(Call $call): void
    {
        $callData = $this->data['call'] ?? $this->data;
        
        if (isset($callData['transcript_object'])) {
            // Store structured transcript
            $call->update([
                'transcript_object' => $callData['transcript_object']
            ]);
            
            // Analyze conversation flow
            $this->analyzeConversationFlow($call, $callData['transcript_object']);
        }
        
        if (isset($callData['transcript_with_tool_calls'])) {
            // Store tool calls
            $analysis = $call->analysis ?? [];
            $analysis['tool_calls'] = $callData['transcript_with_tool_calls'];
            $call->update(['analysis' => $analysis]);
        }
    }
    
    protected function analyzeConversationFlow(Call $call, array $transcriptObject): void
    {
        $analysis = $call->analysis ?? [];
        
        // Count turns
        $userTurns = 0;
        $agentTurns = 0;
        $totalWords = 0;
        
        foreach ($transcriptObject as $turn) {
            if ($turn['role'] === 'user') {
                $userTurns++;
            } elseif ($turn['role'] === 'agent') {
                $agentTurns++;
            }
            
            $totalWords += str_word_count($turn['content'] ?? '');
        }
        
        $analysis['conversation_metrics'] = [
            'user_turns' => $userTurns,
            'agent_turns' => $agentTurns,
            'total_turns' => count($transcriptObject),
            'total_words' => $totalWords,
            'avg_words_per_turn' => count($transcriptObject) > 0 
                ? round($totalWords / count($transcriptObject), 1) 
                : 0
        ];
        
        $call->update(['analysis' => $analysis]);
    }
    
    protected function processAppointmentBooking(Call $call): void
    {
        try {
            $callData = $this->data['call'] ?? $this->data;
            
            // Enhanced logging for debugging appointment data extraction
            Log::info('Attempting to extract appointment data', [
                'call_id' => $call->id,
                'retell_call_id' => $call->retell_call_id,
                'has_retell_llm_dynamic_variables' => isset($callData['retell_llm_dynamic_variables']),
                'retell_llm_keys' => isset($callData['retell_llm_dynamic_variables']) 
                    ? array_keys($callData['retell_llm_dynamic_variables']) 
                    : [],
                'transcript_length' => strlen($callData['transcript'] ?? ''),
                'has_transcript_object' => isset($callData['transcript_object'])
            ]);
            
            // FIRST: Check for cached appointment data from collect_appointment_data function
            $cacheKey = "retell_appointment_data:{$call->retell_call_id}";
            $cachedAppointmentData = Cache::get($cacheKey);
            
            if ($cachedAppointmentData) {
                Log::info('Found cached appointment data from collect_appointment_data', [
                    'call_id' => $call->retell_call_id,
                    'data' => $cachedAppointmentData
                ]);
                
                // Use cached data directly
                $this->createAppointmentFromData($call, $cachedAppointmentData);
                
                // Clear cache after use
                Cache::forget($cacheKey);
                return;
            }
            
            // Check if appointment data exists from collect_appointment_data function
            $appointmentFields = ['datum', 'name', 'telefonnummer', 'dienstleistung', 'uhrzeit', 'email', 'kundenpraeferenzen', 'mitarbeiter_wunsch', 'verfuegbarkeit_pruefen', 'alternative_termine_gewuenscht'];
            $hasAppointmentData = false;
            $appointmentData = [];
            
            // First check in retell_llm_dynamic_variables
            if (isset($callData['retell_llm_dynamic_variables'])) {
                Log::info('Found retell_llm_dynamic_variables', [
                    'data' => $callData['retell_llm_dynamic_variables']
                ]);
                
                foreach ($appointmentFields as $field) {
                    if (isset($callData['retell_llm_dynamic_variables'][$field])) {
                        $appointmentData[$field] = $callData['retell_llm_dynamic_variables'][$field];
                        $hasAppointmentData = true;
                    }
                }
            }
            
            // Also check in custom fields (with _ prefix)
            foreach ($callData as $key => $value) {
                if (strpos($key, '_') === 0) {
                    $fieldName = substr($key, 1); // Remove _ prefix
                    if (in_array($fieldName, $appointmentFields) && !empty($value)) {
                        $appointmentData[$fieldName] = $value;
                        $hasAppointmentData = true;
                    }
                }
            }
            
            // Also check in details if already stored
            if ($call->details) {
                foreach ($appointmentFields as $field) {
                    $prefixedField = '_' . $field;
                    if (isset($call->details[$prefixedField]) && !isset($appointmentData[$field])) {
                        $appointmentData[$field] = $call->details[$prefixedField];
                        $hasAppointmentData = true;
                    }
                }
            }
            
            // CRITICAL FIX: Check cache for appointment data from custom function
            $callId = $call->retell_call_id ?? $callData['call_id'] ?? null;
            if ($callId) {
                $cacheKey = "retell_appointment_data:{$callId}";
                $cachedData = Cache::get($cacheKey);
                
                if ($cachedData) {
                    Log::info('Retrieved cached appointment data from custom function', [
                        'call_id' => $callId,
                        'cache_key' => $cacheKey,
                        'cached_data' => $cachedData
                    ]);
                    
                    // Merge cached data with existing data (cached data takes precedence)
                    $appointmentData = array_merge($appointmentData, $cachedData);
                    $hasAppointmentData = true;
                    
                    // Clear cache after retrieval
                    Cache::forget($cacheKey);
                } else {
                    Log::debug('No cached appointment data found', [
                        'call_id' => $callId,
                        'cache_key' => $cacheKey
                    ]);
                }
            }
            
            if (!$hasAppointmentData || empty($appointmentData['datum']) || empty($appointmentData['uhrzeit'])) {
                Log::warning('No appointment data found or incomplete data in call', [
                    'call_id' => $call->id,
                    'retell_call_id' => $call->retell_call_id,
                    'found_data' => $appointmentData,
                    'has_appointment_data' => $hasAppointmentData,
                    'missing_fields' => array_diff(['datum', 'uhrzeit'], array_keys($appointmentData)),
                    'webhook_data_structure' => array_keys($callData)
                ]);
                
                // Try to extract appointment intent from transcript
                if (!empty($callData['transcript'])) {
                    $this->analyzeTranscriptForAppointment($call, $callData['transcript']);
                }
                
                return;
            }
            
            Log::info('Processing appointment booking from call', [
                'call_id' => $call->id,
                'appointment_data' => $appointmentData
            ]);
            
            // Use AppointmentBookingService to create the appointment
            $bookingService = new AppointmentBookingService();
            $result = $bookingService->bookFromPhoneCall($call, $appointmentData);
            
            if ($result['success']) {
                Log::info('Appointment successfully booked from call', [
                    'call_id' => $call->id,
                    'appointment_id' => $result['appointment']->id ?? null
                ]);
                
                // Update call with appointment reference
                $call->update([
                    'appointment_id' => $result['appointment']->id,
                    'metadata' => array_merge($call->metadata ?? [], [
                        'appointment_booked' => true,
                        'appointment_booking_result' => $result
                    ])
                ]);
            } else {
                Log::warning('Failed to book appointment from call', [
                    'call_id' => $call->id,
                    'error' => $result['message'] ?? 'Unknown error',
                    'appointment_data' => $appointmentData
                ]);
            }
            
        } catch (\Exception $e) {
            Log::error('Error processing appointment booking', [
                'call_id' => $call->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
    
    /**
     * Create appointment from cached collector data
     */
    protected function createAppointmentFromData(Call $call, array $appointmentData): void
    {
        try {
            // Log the appointment creation attempt
            Log::info('Creating appointment from cached collector data', [
                'call_id' => $call->id,
                'appointment_data' => $appointmentData
            ]);
            
            // Prepare data for AppointmentBookingService
            $bookingData = [
                'datum' => $appointmentData['datum'],
                'uhrzeit' => $appointmentData['uhrzeit'],
                'name' => $appointmentData['name'],
                'telefonnummer' => $appointmentData['telefonnummer'],
                'dienstleistung' => $appointmentData['dienstleistung'],
                'email' => $appointmentData['email'] ?? null,
                'notizen' => $appointmentData['kundenpraeferenzen'] ?? null,
                'mitarbeiter_wunsch' => $appointmentData['mitarbeiter_wunsch'] ?? null,
                'reference_id' => $appointmentData['reference_id'] ?? null,
                'appointment_id' => $appointmentData['appointment_id'] ?? null,
            ];
            
            // Use AppointmentBookingService to create the appointment
            $bookingService = new AppointmentBookingService();
            $result = $bookingService->bookFromPhoneCall($call, $bookingData);
            
            if ($result['success']) {
                Log::info('Appointment successfully created from cached data', [
                    'call_id' => $call->id,
                    'appointment_id' => $result['appointment']->id ?? null
                ]);
                
                // Update call with appointment reference
                $call->update([
                    'appointment_id' => $result['appointment']->id,
                    'metadata' => array_merge($call->metadata ?? [], [
                        'appointment_booked' => true,
                        'appointment_booking_result' => $result,
                        'used_cached_collector_data' => true
                    ])
                ]);
            } else {
                Log::error('Failed to create appointment from cached data', [
                    'call_id' => $call->id,
                    'error' => $result['message'] ?? 'Unknown error',
                    'result' => $result
                ]);
            }
            
        } catch (\Exception $e) {
            Log::error('Exception creating appointment from cached data', [
                'call_id' => $call->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
    
    /**
     * Parse timestamp from various formats
     */
    protected function parseTimestamp($timestamp)
    {
        if (!$timestamp) {
            return null;
        }
        
        // If it's already a Carbon instance
        if ($timestamp instanceof \Carbon\Carbon) {
            return $timestamp;
        }
        
        // If it's numeric (Unix timestamp in seconds or milliseconds)
        if (is_numeric($timestamp)) {
            // Check if it's milliseconds (larger than reasonable seconds timestamp)
            if ($timestamp > 9999999999) {
                return \Carbon\Carbon::createFromTimestampMs($timestamp);
            }
            return \Carbon\Carbon::createFromTimestamp($timestamp);
        }
        
        // Try to parse as string (ISO 8601 or other formats)
        try {
            return \Carbon\Carbon::parse($timestamp);
        } catch (\Exception $e) {
            Log::warning('Failed to parse timestamp', [
                'timestamp' => $timestamp,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
    
    /**
     * Analyze transcript for appointment intent when structured data is missing
     */
    protected function analyzeTranscriptForAppointment(Call $call, string $transcript): void
    {
        try {
            // Look for appointment-related keywords
            $appointmentKeywords = ['termin', 'buchen', 'vereinbaren', 'uhrzeit', 'datum', 'morgen', 'montag', 'dienstag', 'mittwoch', 'donnerstag', 'freitag'];
            $foundKeywords = [];
            
            foreach ($appointmentKeywords as $keyword) {
                if (stripos($transcript, $keyword) !== false) {
                    $foundKeywords[] = $keyword;
                }
            }
            
            if (!empty($foundKeywords)) {
                Log::warning('Appointment intent detected in transcript but no structured data', [
                    'call_id' => $call->id,
                    'retell_call_id' => $call->retell_call_id,
                    'found_keywords' => $foundKeywords,
                    'transcript_preview' => substr($transcript, 0, 500)
                ]);
                
                // Update call metadata to indicate appointment intent
                $metadata = $call->metadata ?? [];
                $metadata['appointment_intent_detected'] = true;
                $metadata['appointment_keywords'] = $foundKeywords;
                $metadata['requires_manual_review'] = true;
                $call->update(['metadata' => $metadata]);
            }
        } catch (\Exception $e) {
            Log::error('Error analyzing transcript for appointment', [
                'error' => $e->getMessage()
            ]);
        }
    }
}