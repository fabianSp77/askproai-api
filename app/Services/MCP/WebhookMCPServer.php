<?php

namespace App\Services\MCP;

use App\Models\Call;
use App\Models\Customer;
use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Company;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Services\MCP\DistributedTransactionManager;
use App\Services\Webhook\WebhookDeduplicationService;

class WebhookMCPServer
{
    protected CalcomMCPServer $calcomMCP;
    protected RetellMCPServer $retellMCP;
    protected DatabaseMCPServer $databaseMCP;
    protected QueueMCPServer $queueMCP;
    protected WebhookDeduplicationService $deduplication;
    
    public function __construct(
        CalcomMCPServer $calcomMCP,
        RetellMCPServer $retellMCP,
        DatabaseMCPServer $databaseMCP,
        QueueMCPServer $queueMCP,
        WebhookDeduplicationService $deduplication
    ) {
        $this->calcomMCP = $calcomMCP;
        $this->retellMCP = $retellMCP;
        $this->databaseMCP = $databaseMCP;
        $this->queueMCP = $queueMCP;
        $this->deduplication = $deduplication;
    }
    
    /**
     * Process Retell webhook using MCP services
     */
    public function processRetellWebhook(array $webhookData): array
    {
        try {
            // Handle the webhook data structure properly
            $payload = $webhookData['payload'] ?? $webhookData;
            $event = $payload['event'] ?? $webhookData['event'] ?? null;
            $callData = $payload['call'] ?? $payload;
            
            // Extract webhook ID for deduplication
            $webhookId = $callData['call_id'] ?? null;
            if (!$webhookId) {
                return [
                    'success' => false,
                    'message' => 'No call_id found in webhook data',
                    'processed' => false
                ];
            }
            
            // Check for duplicate using Redis-based deduplication
            $deduplicationResult = $this->deduplication->processWithDeduplication(
                $webhookId,
                'retell',
                function() use ($payload, $event, $callData) {
                    return $this->processWebhookInternal($payload, $event, $callData);
                }
            );
            
            return $deduplicationResult;
            
        } catch (\Exception $e) {
            Log::error('MCP Webhook processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'message' => 'Processing failed: ' . $e->getMessage(),
                'processed' => false
            ];
        }
    }
    
    /**
     * Internal webhook processing logic (called by deduplication service)
     */
    protected function processWebhookInternal(array $payload, ?string $event, array $callData): array
    {
        try {
            // Create webhook event record
            $webhookEvent = new \App\Models\WebhookEvent();
            $webhookEvent->provider = 'retell';
            $webhookEvent->type = 'webhook';
            $webhookEvent->source = 'mcp';
            $webhookEvent->event = $event;
            $webhookEvent->payload = $payload;
            $webhookEvent->correlation_id = $callData['call_id'] ?? Str::uuid()->toString();
            $webhookEvent->status = 'processing';
            $webhookEvent->save();
            
            Log::info('[MCP WebhookServer] Processing webhook', [
                'event' => $event,
                'call_id' => $callData['call_id'] ?? null,
                'webhook_event_id' => $webhookEvent->id
            ]);
            
            // Merge root-level dynamic variables into call data if present
            if (isset($payload['retell_llm_dynamic_variables']) && !isset($callData['retell_llm_dynamic_variables'])) {
                $callData['retell_llm_dynamic_variables'] = $payload['retell_llm_dynamic_variables'];
            }
            
            // Process both call_ended and call_analyzed events
            if (!in_array($event, ['call_ended', 'call_analyzed']) || empty($callData['call_id'])) {
                // Mark as processed (but skipped)
                if (isset($webhookEvent)) {
                    $webhookEvent->processed_at = now();
                    $webhookEvent->status = 'skipped';
                    $webhookEvent->notes = 'Event not relevant for processing';
                    $webhookEvent->save();
                }
                
                return [
                    'success' => true,
                    'message' => 'Event not relevant for processing',
                    'processed' => false,
                    'webhook_event_id' => $webhookEvent->id ?? null
                ];
            }
            
            // 1. Use Database MCP to check if call already exists
            $existingCall = $this->databaseMCP->query([
                'query' => "SELECT id FROM calls WHERE retell_call_id = ? LIMIT 1",
                'bindings' => [$callData['call_id']]
            ]);
            
            if (!empty($existingCall['data'])) {
                // For call_analyzed events, we still need to check if appointment should be created
                if ($event === 'call_analyzed') {
                    $call = Call::withoutGlobalScopes()->find($existingCall['data'][0]->id);
                    
                    // Resolve phone number for branch info
                    $phoneResolution = $this->resolvePhoneNumber($callData['to_number'] ?? $callData['to'] ?? null);
                    
                    // Check if appointment should be created
                    $appointmentData = null;
                    $shouldCreate = $this->shouldCreateAppointment($callData);
                    
                    Log::info('MCP: Appointment creation check for existing call', [
                        'should_create' => $shouldCreate,
                        'call_id' => $call->id,
                        'event' => $event
                    ]);
                    
                    if ($shouldCreate && $phoneResolution['success']) {
                        Log::info('MCP creating appointment for analyzed call');
                        $appointmentData = $this->createAppointmentViaMCP(
                            $call,
                            $callData,
                            $phoneResolution
                        );
                    }
                    
                    // Mark webhook event as processed
                    if (isset($webhookEvent)) {
                        $webhookEvent->processed_at = now();
                        $webhookEvent->status = 'completed';
                        $webhookEvent->notes = $appointmentData ? 'Appointment created' : 'Call analyzed without appointment';
                        $webhookEvent->save();
                    }
                    
                    return [
                        'success' => true,
                        'message' => 'Call analyzed and processed',
                        'call_id' => $call->id,
                        'appointment_created' => !is_null($appointmentData),
                        'appointment_data' => $appointmentData,
                        'processed' => true,
                        'webhook_event_id' => $webhookEvent->id ?? null
                    ];
                }
                
                // For other events, mark as already processed
                if (isset($webhookEvent)) {
                    $webhookEvent->processed_at = now();
                    $webhookEvent->status = 'duplicate';
                    $webhookEvent->notes = 'Call already processed';
                    $webhookEvent->save();
                }
                
                return [
                    'success' => true,
                    'message' => 'Call already processed',
                    'call_id' => $existingCall['data'][0]->id,
                    'processed' => false,
                    'webhook_event_id' => $webhookEvent->id ?? null
                ];
            }
            
            // Don't use transactions when inside MCP context
            // DB::beginTransaction();
            
            try {
                // 2. Resolve phone number to branch/company
                $phoneResolution = $this->resolvePhoneNumber($callData['to_number'] ?? $callData['to'] ?? null);
                
                if (!$phoneResolution['success']) {
                    throw new \Exception('Could not resolve phone number to branch');
                }
                
                // 3. Create or find customer
                $customer = $this->findOrCreateCustomer(
                    $callData['from_number'] ?? null,
                    $phoneResolution['company_id'],
                    $callData
                );
                
                // 4. Save call record
                $call = $this->saveCallRecord($callData, $phoneResolution, $customer);
                
                // 5. Check if appointment should be created
                $appointmentData = null;
                Log::info('MCP checking if appointment should be created', [
                    'should_create' => $this->shouldCreateAppointment($callData),
                    'dynamic_vars' => $callData['retell_llm_dynamic_variables'] ?? []
                ]);
                
                $shouldCreate = $this->shouldCreateAppointment($callData);
                Log::info('MCP: Appointment creation check', [
                    'should_create' => $shouldCreate,
                    'dynamic_vars' => $callData['retell_llm_dynamic_variables'] ?? [],
                    'call_id' => $call->id
                ]);
                
                if ($shouldCreate) {
                    Log::info('MCP creating appointment via Cal.com');
                    $appointmentData = $this->createAppointmentViaMCP(
                        $call,
                        $callData,
                        $phoneResolution
                    );
                } else {
                    Log::debug('MCP: Should not create appointment', [
                        'call_id' => $call->id
                    ]);
                }
                
                // DB::commit();
                
                // Mark webhook event as processed
                if (isset($webhookEvent)) {
                    $webhookEvent->processed_at = now();
                    $webhookEvent->status = 'completed';
                    $webhookEvent->notes = $appointmentData ? 'New call with appointment created' : 'New call processed without appointment';
                    $webhookEvent->save();
                }
                
                return [
                    'success' => true,
                    'message' => 'Webhook processed successfully',
                    'call_id' => $call->id,
                    'customer_id' => $customer->id,
                    'appointment_created' => !is_null($appointmentData),
                    'appointment_data' => $appointmentData,
                    'processed' => true,
                    'webhook_event_id' => $webhookEvent->id ?? null
                ];
            } catch (\Exception $e) {
                // Rollback if needed
                // DB::rollback();
                throw $e; // Re-throw to outer catch
            }
                
        } catch (\Exception $e) {
            // Mark webhook event as failed
            if (isset($webhookEvent)) {
                $webhookEvent->status = 'failed';
                $webhookEvent->error = $e->getMessage();
                $webhookEvent->notes = 'Processing failed: ' . $e->getMessage();
                $webhookEvent->save();
            }
            
            // Re-throw to let deduplication service handle it
            throw $e;
        }
    }
    
    /**
     * Resolve phone number to branch and company
     */
    protected function resolvePhoneNumber(?string $phoneNumber): array
    {
        if (!$phoneNumber) {
            return ['success' => false, 'message' => 'No phone number provided'];
        }
        
        // Log the phone number we're looking for
        Log::info('MCP resolving phone number', ['phone' => $phoneNumber]);
        
        // Use Database MCP to find phone number mapping
        // FIXED: Use 'number' column, not 'phone_number'
        $result = $this->databaseMCP->query([
            'query' => "SELECT pn.*, b.id as branch_id_correct, b.company_id, b.name as branch_name, b.calcom_event_type_id 
             FROM phone_numbers pn
             JOIN branches b ON pn.branch_id = b.id
             WHERE pn.number = ? AND pn.is_active = 1 AND b.is_active = 1
             LIMIT 1",
            'bindings' => [$phoneNumber]
        ]);
        
        // If not found, try without the + prefix
        if (empty($result['data']) && strpos($phoneNumber, '+') === 0) {
            $phoneWithoutPlus = substr($phoneNumber, 1);
            Log::info('MCP trying without + prefix', ['phone' => $phoneWithoutPlus]);
            
            $result = $this->databaseMCP->query([
                'query' => "SELECT pn.*, b.id as branch_id_correct, b.company_id, b.name as branch_name, b.calcom_event_type_id 
                 FROM phone_numbers pn
                 JOIN branches b ON pn.branch_id = b.id
                 WHERE pn.number = ? AND pn.is_active = 1 AND b.is_active = 1
                 LIMIT 1",
                'bindings' => [$phoneWithoutPlus]
            ]);
        }
        
        if (!empty($result['data'])) {
            $data = $result['data'][0];
            return [
                'success' => true,
                'branch_id' => $data->branch_id_correct, // Use the corrected branch_id
                'company_id' => $data->company_id,
                'branch_name' => $data->branch_name,
                'calcom_event_type_id' => $data->calcom_event_type_id
            ];
        }
        
        // Fallback: Check branch direct phone numbers
        $result = $this->databaseMCP->query([
            'query' => "SELECT id, company_id, name, calcom_event_type_id 
             FROM branches 
             WHERE phone_number = ? AND is_active = 1
             LIMIT 1",
            'bindings' => [$phoneNumber]
        ]);
        
        if (!empty($result['data'])) {
            $data = $result['data'][0];
            return [
                'success' => true,
                'branch_id' => $data->id,
                'company_id' => $data->company_id,
                'branch_name' => $data->name,
                'calcom_event_type_id' => $data->calcom_event_type_id
            ];
        }
        
        return ['success' => false, 'message' => 'Phone number not found'];
    }
    
    /**
     * Find or create customer
     */
    protected function findOrCreateCustomer(?string $phoneNumber, int $companyId, array $callData): Customer
    {
        if (!$phoneNumber) {
            throw new \Exception('Customer phone number required');
        }
        
        // Extract customer data from call
        $dynamicVars = $callData['retell_llm_dynamic_variables'] ?? [];
        $customData = $callData['call_analysis']['custom_analysis_data'] ?? [];
        
        $customerName = $dynamicVars['name'] ?? 
                       $customData['_name'] ?? 
                       'Kunde';
                       
        $customerEmail = $customData['_email'] ?? null;
        
        // Check if customer exists
        $result = $this->databaseMCP->query([
            'query' => "SELECT id FROM customers WHERE phone = ? AND company_id = ? LIMIT 1",
            'bindings' => [$phoneNumber, $companyId]
        ]);
        
        if (!empty($result['data'])) {
            return Customer::withoutGlobalScope(\App\Scopes\TenantScope::class)->find($result['data'][0]->id);
        }
        
        // Create new customer (bypass validation via DB)
        $customerId = DB::table('customers')->insertGetId([
            'company_id' => $companyId,
            'phone' => $phoneNumber,
            'name' => $customerName,
            'email' => $customerEmail,
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        return Customer::withoutGlobalScope(\App\Scopes\TenantScope::class)->find($customerId);
    }
    
    /**
     * Save call record
     */
    protected function saveCallRecord(array $callData, array $phoneResolution, Customer $customer): Call
    {
        $dynamicVars = $callData['retell_llm_dynamic_variables'] ?? [];
        $customData = $callData['call_analysis']['custom_analysis_data'] ?? [];
        
        // Create call without global scopes
        $call = Call::withoutGlobalScopes()->newModelInstance();
        $call->company_id = $phoneResolution['company_id'];
        $call->branch_id = $phoneResolution['branch_id'];
        $call->customer_id = $customer->id;
        $call->retell_call_id = $callData['call_id'];
        $call->call_id = $callData['call_id'];
        $call->agent_id = $callData['agent_id'] ?? null;
        $call->from_number = $callData['from_number'] ?? null;
        $call->to_number = $callData['to_number'] ?? $callData['to'] ?? null;
        $call->direction = $callData['call_type'] ?? 'inbound';
        $call->call_status = $callData['call_status'] ?? 'completed';
        
        // Timestamps
        if (isset($callData['start_timestamp'])) {
            $call->start_timestamp = Carbon::createFromTimestampMs($callData['start_timestamp']);
        }
        if (isset($callData['end_timestamp'])) {
            $call->end_timestamp = Carbon::createFromTimestampMs($callData['end_timestamp']);
        }
        
        // Duration and cost
        $call->duration_sec = isset($callData['duration_ms']) ? round($callData['duration_ms'] / 1000) : 0;
        $call->duration_minutes = $call->duration_sec > 0 ? round($call->duration_sec / 60, 2) : 0;
        $call->cost = isset($callData['cost']) ? $callData['cost'] / 100 : 0;
        
        // Transcript and analysis
        $call->transcript = $callData['transcript'] ?? null;
        $call->summary = $callData['call_analysis']['call_summary'] ?? null;
        $call->sentiment = $callData['call_analysis']['sentiment'] ?? null;
        $call->analysis = json_encode($callData['call_analysis'] ?? []);
        
        // Dynamic variables
        $call->retell_dynamic_variables = json_encode($dynamicVars);
        $call->webhook_data = json_encode($callData);
        
        // Extracted data
        $call->extracted_name = $dynamicVars['name'] ?? $customData['_name'] ?? null;
        $call->extracted_date = $dynamicVars['datum'] ?? $customData['_datum__termin'] ?? null;
        $call->extracted_time = $dynamicVars['uhrzeit'] ?? $customData['_uhrzeit__termin'] ?? null;
        // $call->extracted_email = $customData['_email'] ?? null; // Column doesn't exist
        
        // URLs
        $call->audio_url = $callData['recording_url'] ?? null;
        $call->public_log_url = $callData['public_log_url'] ?? null;
        
        // Bypass model events by using unguarded save
        Call::unguard();
        $call->saveQuietly();
        Call::reguard();
        
        return $call;
    }
    
    /**
     * Check if appointment should be created
     */
    protected function shouldCreateAppointment(array $callData): bool
    {
        $dynamicVars = $callData['retell_llm_dynamic_variables'] ?? [];
        
        // Check if call was analyzed (for call_analyzed events)
        // Note: event is at the root level, not in callData
        if (isset($callData['call_analysis']) && 
            isset($callData['call_analysis']['call_successful']) && 
            $callData['call_analysis']['call_successful'] === true) {
            $analysisData = $callData['call_analysis']['custom_analysis_data'] ?? [];
            
            // Check if we have appointment data in custom analysis
            $hasAppointmentData = isset($analysisData['_datum__termin']) && 
                                  isset($analysisData['_uhrzeit__termin']);
            
            // Also check for tool calls that indicate appointment creation
            $hasToolCall = false;
            if (isset($callData['transcript_with_tool_calls'])) {
                foreach ($callData['transcript_with_tool_calls'] as $entry) {
                    if (isset($entry['name']) && $entry['name'] === 'collect_appointment_data') {
                        $hasToolCall = true;
                        break;
                    }
                }
            }
            
            Log::info('MCP shouldCreateAppointment check (call_analyzed)', [
                'has_appointment_data' => $hasAppointmentData,
                'has_tool_call' => $hasToolCall,
                'datum_termin' => $analysisData['_datum__termin'] ?? 'not set',
                'uhrzeit_termin' => $analysisData['_uhrzeit__termin'] ?? 'not set',
                'call_successful' => $callData['call_analysis']['call_successful'] ?? false
            ]);
            
            return ($hasAppointmentData || $hasToolCall) && 
                   ($callData['call_analysis']['call_successful'] ?? false);
        }
        
        // Original check for dynamic variables (for backward compatibility)
        // Check booking_confirmed with more flexible type handling
        $bookingConfirmed = false;
        if (isset($dynamicVars['booking_confirmed'])) {
            $value = $dynamicVars['booking_confirmed'];
            // Handle various truthy values
            $bookingConfirmed = 
                $value === true || 
                $value === 'true' || 
                $value === '1' || 
                $value === 1 ||
                (is_string($value) && strtolower($value) === 'yes');
        }
        
        // Debug logging
        Log::info('MCP shouldCreateAppointment check', [
            'booking_confirmed' => $dynamicVars['booking_confirmed'] ?? 'not set',
            'booking_confirmed_type' => gettype($dynamicVars['booking_confirmed'] ?? null),
            'booking_confirmed_check' => $bookingConfirmed,
            'datum' => $dynamicVars['datum'] ?? 'not set',
            'uhrzeit' => $dynamicVars['uhrzeit'] ?? 'not set',
            'all_checks_pass' => $bookingConfirmed && !empty($dynamicVars['datum']) && !empty($dynamicVars['uhrzeit'])
        ]);
                           
        return $bookingConfirmed && 
               !empty($dynamicVars['datum']) && 
               !empty($dynamicVars['uhrzeit']);
    }
    
    /**
     * Create appointment using Cal.com MCP
     */
    protected function createAppointmentViaMCP(Call $call, array $callData, array $phoneResolution): ?array
    {
        try {
            Log::info('MCP: createAppointmentViaMCP called', [
                'call_id' => $call->id,
                'phone_resolution' => $phoneResolution
            ]);
            
            $dynamicVars = $callData['retell_llm_dynamic_variables'] ?? [];
            
            // Check if we have Cal.com event type
            if (empty($phoneResolution['calcom_event_type_id'])) {
                Log::error('MCP: No Cal.com event type configured for branch', [
                    'branch_id' => $phoneResolution['branch_id']
                ]);
                return null;
            }
            
            // Extract appointment data from either dynamic vars or custom analysis
            $appointmentDate = null;
            $appointmentTime = null;
            $customerName = null;
            $serviceDescription = null;
            
            // First check if this is from a call with analysis data
            if (isset($callData['call_analysis'])) {
                $analysisData = $callData['call_analysis']['custom_analysis_data'] ?? [];
                
                // Extract date from _datum__termin (format: 23062023)
                if (isset($analysisData['_datum__termin'])) {
                    $dateStr = (string)$analysisData['_datum__termin'];
                    if (strlen($dateStr) === 8) {
                        $day = substr($dateStr, 0, 2);
                        $month = substr($dateStr, 2, 2);
                        $year = substr($dateStr, 4, 4);
                        $appointmentDate = "$year-$month-$day";
                    }
                }
                
                // Extract time from _uhrzeit__termin (format: 14 for 14:00)
                if (isset($analysisData['_uhrzeit__termin'])) {
                    $appointmentTime = sprintf('%02d:00', $analysisData['_uhrzeit__termin']);
                }
                
                // Extract customer name
                $customerName = $analysisData['_name'] ?? null;
                
                // Extract tool call data if available
                if (isset($callData['transcript_with_tool_calls'])) {
                    foreach ($callData['transcript_with_tool_calls'] as $entry) {
                        if (isset($entry['name']) && $entry['name'] === 'collect_appointment_data' && isset($entry['arguments'])) {
                            $args = is_string($entry['arguments']) ? json_decode($entry['arguments'], true) : $entry['arguments'];
                            if ($args) {
                                // Use tool call data as primary source if available
                                if (!empty($args['datum'])) {
                                    // Parse German date format (e.g., "23.06.")
                                    $dateParts = explode('.', $args['datum']);
                                    if (count($dateParts) >= 2) {
                                        $day = str_pad($dateParts[0], 2, '0', STR_PAD_LEFT);
                                        $month = str_pad($dateParts[1], 2, '0', STR_PAD_LEFT);
                                        $year = date('Y');
                                        $appointmentDate = "$year-$month-$day";
                                    }
                                }
                                if (!empty($args['uhrzeit'])) {
                                    $appointmentTime = $args['uhrzeit'];
                                }
                                if (!empty($args['name'])) {
                                    $customerName = $args['name'];
                                }
                                if (!empty($args['dienstleistung'])) {
                                    $serviceDescription = $args['dienstleistung'];
                                }
                            }
                        }
                    }
                }
            }
            
            // Fallback to dynamic vars if not found
            if (!$appointmentDate && !empty($dynamicVars['datum'])) {
                $appointmentDate = $dynamicVars['datum'];
            }
            if (!$appointmentTime && !empty($dynamicVars['uhrzeit'])) {
                $appointmentTime = $dynamicVars['uhrzeit'];
            }
            
            // Parse date and time
            if (!$appointmentDate || !$appointmentTime) {
                Log::error('MCP: Missing appointment date or time', [
                    'date' => $appointmentDate,
                    'time' => $appointmentTime
                ]);
                return null;
            }
            
            $date = Carbon::parse($appointmentDate);
            $time = $appointmentTime;
            
            if (strpos($time, ':') !== false) {
                [$hours, $minutes] = explode(':', $time);
            } else {
                $hours = $time;
                $minutes = 0;
            }
            
            $startTime = $date->copy()->setTime((int)$hours, (int)$minutes);
            $endTime = $startTime->copy()->addMinutes(30);
            
            // Get customer
            $customer = Customer::withoutGlobalScope(\App\Scopes\TenantScope::class)->find($call->customer_id);
            
            Log::info('MCP: Preparing booking data', [
                'customer_name' => $customer->name,
                'start_time' => $startTime->toIso8601String(),
                'event_type_id' => $phoneResolution['calcom_event_type_id']
            ]);
            
            // Update customer name if we have it from the call
            if ($customerName && $customer) {
                $customer->first_name = $customerName;
                $customer->save();
            }
            
            // Prepare booking data for Cal.com MCP
            $bookingData = [
                'company_id' => $phoneResolution['company_id'],
                'event_type_id' => $phoneResolution['calcom_event_type_id'],
                'start' => $startTime->toIso8601String(),
                'end' => $endTime->toIso8601String(),
                'name' => $customerName ?: $customer->name ?: 'Kunde',
                'email' => $customer->email ?: 'kunde@example.com',
                'phone' => $customer->phone ?: '+491234567890',
                'notes' => "Gebucht über Telefon-KI (MCP)\nService: " . ($serviceDescription ?: $dynamicVars['dienstleistung'] ?? 'Nicht angegeben'),
                'metadata' => [
                    'call_id' => $call->id,
                    'source' => 'mcp_webhook'
                ]
            ];
            
            // Set company context for tenant scope
            app()->instance('current_company_id', $phoneResolution['company_id']);
            
            // Use Cal.com MCP to create booking
            Log::info('MCP: Calling Cal.com MCP createBooking', $bookingData);
            
            try {
                $calcomResult = $this->calcomMCP->createBooking($bookingData);
                
                Log::info('MCP: Cal.com createBooking result', $calcomResult);
            } finally {
                // Always remove company context after use
                app()->forgetInstance('current_company_id');
            }
            
            if (!$calcomResult['success']) {
                Log::error('Cal.com MCP booking failed', [
                    'error' => $calcomResult['error'] ?? 'Unknown error',
                    'call_id' => $call->id
                ]);
                
                // Handle error based on company settings
                $this->handleBookingError($call, $phoneResolution, $bookingData, $calcomResult['error'] ?? 'Unknown error');
                
                return null;
            }
            
            // Create local appointment record
            $appointment = Appointment::withoutGlobalScopes()->newModelInstance();
            $appointment->company_id = $call->company_id;
            $appointment->branch_id = $call->branch_id;
            $appointment->customer_id = $call->customer_id;
            $appointment->call_id = $call->id;
            $appointment->starts_at = $startTime;
            $appointment->ends_at = $endTime;
            $appointment->status = 'scheduled';
            $appointment->calcom_booking_id = $calcomResult['booking']['id'] ?? null;
            // Remove calcom_booking_uid as column doesn't exist
            // $appointment->calcom_booking_uid = $calcomResult['booking']['uid'] ?? null;
            $appointment->notes = $bookingData['notes'];
            
            // Bypass model events
            Appointment::unguard();
            $appointment->saveQuietly();
            Appointment::reguard();
            
            // Update call with appointment ID
            $call->appointment_id = $appointment->id;
            $call->save();
            
            Log::info('MCP Appointment created successfully', [
                'appointment_id' => $appointment->id,
                'calcom_booking_id' => $appointment->calcom_booking_id,
                'call_id' => $call->id
            ]);
            
            return [
                'appointment_id' => $appointment->id,
                'calcom_booking_id' => $appointment->calcom_booking_id,
                'start_time' => $startTime->toDateTimeString()
            ];
            
        } catch (\Exception $e) {
            Log::error('MCP Appointment creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'call_id' => $call->id
            ]);
            return null;
        }
    }
    
    /**
     * Handle booking error based on company settings
     */
    protected function handleBookingError(Call $call, array $phoneResolution, array $bookingData, string $error): void
    {
        $company = Company::find($phoneResolution['company_id']);
        if (!$company) {
            return;
        }
        
        $errorMode = $company->settings['error_handling']['mode'] ?? 'callback';
        
        switch ($errorMode) {
            case 'callback':
                // Create callback request
                $callbackService = app(\App\Services\CallbackService::class);
                $callback = $callbackService->createFromFailedBooking(
                    $call,
                    'calcom_error',
                    $bookingData,
                    $error
                );
                
                // Update Retell conversation if still active
                $this->updateRetellConversation($call->retell_call_id, [
                    'message' => $company->settings['error_handling']['callback_message'] ?? 
                        'Ich verstehe Ihren Wunsch. Ein Mitarbeiter wird sich schnellstmöglich bei Ihnen melden, um einen passenden Termin zu finden.',
                    'action' => 'promise_callback'
                ]);
                break;
                
            case 'forward':
                // Check if during business hours
                $forwardNumber = $company->settings['error_handling']['forward_number'] ?? null;
                if ($forwardNumber && $this->isDuringBusinessHours($company)) {
                    $this->initiateCallTransfer($call->retell_call_id, $forwardNumber);
                } else {
                    // Fall back to callback
                    $this->handleBookingError($call, $phoneResolution, $bookingData, $error);
                }
                break;
                
            case 'inform_only':
                $this->updateRetellConversation($call->retell_call_id, [
                    'message' => $company->settings['error_handling']['inform_message'] ?? 
                        'Es tut mir leid, momentan kann ich keinen Termin buchen. Bitte versuchen Sie es später erneut oder rufen Sie während unserer Geschäftszeiten an.',
                    'action' => 'end_call_politely'
                ]);
                break;
        }
    }
    
    /**
     * Update Retell conversation (placeholder - needs Retell API implementation)
     */
    protected function updateRetellConversation(string $callId, array $data): void
    {
        // TODO: Implement Retell API call to update conversation
        Log::info('Would update Retell conversation', [
            'call_id' => $callId,
            'data' => $data
        ]);
    }
    
    /**
     * Initiate call transfer (placeholder - needs Retell API implementation)
     */
    protected function initiateCallTransfer(string $callId, string $transferNumber): void
    {
        // TODO: Implement Retell API call to transfer call
        Log::info('Would initiate call transfer', [
            'call_id' => $callId,
            'transfer_to' => $transferNumber
        ]);
    }
    
    /**
     * Check if current time is during business hours
     */
    protected function isDuringBusinessHours(Company $company): bool
    {
        $now = now();
        $dayOfWeek = strtolower($now->format('l'));
        
        $workingHours = $company->settings['callback_handling']['working_hours'] ?? [
            'monday' => ['start' => '09:00', 'end' => '18:00'],
            'tuesday' => ['start' => '09:00', 'end' => '18:00'],
            'wednesday' => ['start' => '09:00', 'end' => '18:00'],
            'thursday' => ['start' => '09:00', 'end' => '18:00'],
            'friday' => ['start' => '09:00', 'end' => '17:00'],
            'saturday' => null,
            'sunday' => null
        ];
        
        $todayHours = $workingHours[$dayOfWeek] ?? null;
        
        if (!$todayHours) {
            return false;
        }
        
        $start = Carbon::createFromTimeString($todayHours['start']);
        $end = Carbon::createFromTimeString($todayHours['end']);
        
        return $now->between($start, $end);
    }
    
    /**
     * Generate fallback email for customer
     */
    protected function generateFallbackEmail(Customer $customer, Company $company): string
    {
        $pattern = $company->settings['fallback_values']['email_template'] ?? 'noreply@{domain}';
        
        // Extract domain from company website or email
        $domain = 'example.com';
        if ($company->website) {
            $parsed = parse_url($company->website);
            $domain = $parsed['host'] ?? $domain;
            $domain = str_replace('www.', '', $domain);
        } elseif ($company->email) {
            $parts = explode('@', $company->email);
            $domain = $parts[1] ?? $domain;
        }
        
        // Replace placeholders
        $email = str_replace('{domain}', $domain, $pattern);
        $email = str_replace('{name}', Str::slug($customer->first_name ?? 'kunde'), $email);
        $email = str_replace('{id}', $customer->id, $email);
        
        return $email;
    }
    
    /**
     * Get webhook processing statistics
     */
    public function getWebhookStats(array $params = []): array
    {
        $companyId = $params['company_id'] ?? null;
        $days = $params['days'] ?? 1;
        
        $query = "
            SELECT 
                COUNT(*) as total_webhooks,
                SUM(CASE WHEN appointment_id IS NOT NULL THEN 1 ELSE 0 END) as webhooks_with_appointments,
                AVG(duration_sec) as avg_call_duration,
                SUM(cost) as total_cost
            FROM calls
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
        ";
        
        $queryParams = [$days];
        
        if ($companyId) {
            $query .= " AND company_id = ?";
            $queryParams[] = $companyId;
        }
        
        $stats = $this->databaseMCP->query(['query' => $query, 'bindings' => $queryParams]);
        
        $result = $stats['data'][0] ?? new \stdClass();
        
        return [
            'period_days' => $days,
            'recent_count' => $result->total_webhooks ?? 0,
            'total_webhooks' => $result->total_webhooks ?? 0,
            'webhooks_with_appointments' => $result->webhooks_with_appointments ?? 0,
            'appointment_rate' => isset($result->total_webhooks) && $result->total_webhooks > 0 
                ? round(($result->webhooks_with_appointments / $result->total_webhooks) * 100, 2) 
                : 0,
            'avg_call_duration_seconds' => round($result->avg_call_duration ?? 0),
            'total_cost' => round($result->total_cost ?? 0, 2)
        ];
    }
}