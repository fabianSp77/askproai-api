<?php

namespace App\Services\MCP;

use App\Models\Call;
use App\Models\Customer;
use App\Models\Appointment;
use App\Models\Branch;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Services\MCP\DistributedTransactionManager;

class WebhookMCPServer
{
    protected CalcomMCPServer $calcomMCP;
    protected RetellMCPServer $retellMCP;
    protected DatabaseMCPServer $databaseMCP;
    protected QueueMCPServer $queueMCP;
    
    public function __construct(
        CalcomMCPServer $calcomMCP,
        RetellMCPServer $retellMCP,
        DatabaseMCPServer $databaseMCP,
        QueueMCPServer $queueMCP
    ) {
        $this->calcomMCP = $calcomMCP;
        $this->retellMCP = $retellMCP;
        $this->databaseMCP = $databaseMCP;
        $this->queueMCP = $queueMCP;
    }
    
    /**
     * Process Retell webhook using MCP services
     */
    public function processRetellWebhook(array $webhookData): array
    {
        try {
            $event = $webhookData['event'] ?? null;
            $callData = $webhookData['call'] ?? [];
            
            // Merge root-level dynamic variables into call data if present
            if (isset($webhookData['retell_llm_dynamic_variables']) && !isset($callData['retell_llm_dynamic_variables'])) {
                $callData['retell_llm_dynamic_variables'] = $webhookData['retell_llm_dynamic_variables'];
            }
            
            if ($event !== 'call_ended' || empty($callData['call_id'])) {
                return [
                    'success' => true,
                    'message' => 'Event not relevant for processing',
                    'processed' => false
                ];
            }
            
            // 1. Use Database MCP to check if call already exists
            $existingCall = $this->databaseMCP->query([
                'query' => "SELECT id FROM calls WHERE retell_call_id = ? LIMIT 1",
                'bindings' => [$callData['call_id']]
            ]);
            
            if (!empty($existingCall['data'])) {
                return [
                    'success' => true,
                    'message' => 'Call already processed',
                    'call_id' => $existingCall['data'][0]->id,
                    'processed' => false
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
                    error_log('MCP: Should create appointment = YES');
                    Log::info('MCP creating appointment via Cal.com');
                    $appointmentData = $this->createAppointmentViaMCP(
                        $call,
                        $callData,
                        $phoneResolution
                    );
                } else {
                    error_log('MCP: Should create appointment = NO');
                }
                
                // DB::commit();
                
                return [
                    'success' => true,
                    'message' => 'Webhook processed successfully',
                    'call_id' => $call->id,
                    'customer_id' => $customer->id,
                    'appointment_created' => !is_null($appointmentData),
                    'appointment_data' => $appointmentData,
                    'processed' => true
                ];
                
            } catch (\Exception $e) {
                // DB::rollback();
                throw $e;
            }
            
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
            error_log('MCP: Starting createAppointmentViaMCP');
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
                error_log('MCP: ERROR - No Cal.com event type for branch ' . $phoneResolution['branch_id']);
                return null;
            }
            
            // Parse date and time
            $date = Carbon::parse($dynamicVars['datum']);
            $time = $dynamicVars['uhrzeit'];
            
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
            
            // Prepare booking data for Cal.com MCP
            $bookingData = [
                'company_id' => $phoneResolution['company_id'],
                'event_type_id' => $phoneResolution['calcom_event_type_id'],
                'start' => $startTime->toIso8601String(),
                'end' => $endTime->toIso8601String(),
                'name' => $customer->name ?: 'Kunde',
                'email' => $customer->email ?: 'kunde@example.com',
                'phone' => $customer->phone ?: '+491234567890',
                'notes' => "Gebucht über Telefon-KI (MCP)\nService: " . ($dynamicVars['dienstleistung'] ?? 'Nicht angegeben'),
                'metadata' => [
                    'call_id' => $call->id,
                    'source' => 'mcp_webhook'
                ]
            ];
            
            // Use Cal.com MCP to create booking
            error_log('MCP: Calling Cal.com MCP with data: ' . json_encode($bookingData));
            Log::info('MCP: Calling Cal.com MCP createBooking', $bookingData);
            
            $calcomResult = $this->calcomMCP->createBooking($bookingData);
            
            error_log('MCP: Cal.com result: ' . json_encode($calcomResult));
            Log::info('MCP: Cal.com createBooking result', $calcomResult);
            
            if (!$calcomResult['success']) {
                Log::error('Cal.com MCP booking failed', [
                    'error' => $calcomResult['error'] ?? 'Unknown error',
                    'call_id' => $call->id
                ]);
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
            error_log('MCP: Exception in createAppointmentViaMCP: ' . $e->getMessage());
            error_log('MCP: Exception trace: ' . $e->getTraceAsString());
            Log::error('MCP Appointment creation failed', [
                'error' => $e->getMessage(),
                'call_id' => $call->id
            ]);
            return null;
        }
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