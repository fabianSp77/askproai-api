<?php

namespace App\Services\Webhook;

use App\Models\Call;
use App\Models\Company;
use App\Models\Customer;
use App\Models\WebhookEvent;
use App\Services\PhoneNumberResolver;
use App\Services\RetellV2Service;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class RetellRealtimeService
{
    protected PhoneNumberResolver $phoneResolver;
    protected RetellV2Service $retellService;
    
    public function __construct()
    {
        $this->phoneResolver = app(PhoneNumberResolver::class);
        $this->retellService = app(RetellV2Service::class);
    }
    
    /**
     * Process incoming webhook immediately for real-time updates
     */
    public function processWebhookRealtime(array $payload): array
    {
        $eventType = $payload['event'] ?? $payload['event_type'] ?? null;
        $callId = $payload['call_id'] ?? $payload['data']['call_id'] ?? null;
        
        Log::info('RetellRealtimeService: Processing webhook', [
            'event_type' => $eventType,
            'call_id' => $callId
        ]);
        
        try {
            switch ($eventType) {
                case 'call_started':
                    return $this->handleCallStarted($payload);
                    
                case 'call_ended':
                    return $this->handleCallEnded($payload);
                    
                case 'call_analyzed':
                    return $this->handleCallAnalyzed($payload);
                    
                case 'call_inbound':
                    return $this->handleCallInbound($payload);
                    
                default:
                    Log::warning('Unknown Retell event type', ['event' => $eventType]);
                    return ['success' => false, 'message' => 'Unknown event type'];
            }
        } catch (\Exception $e) {
            Log::error('RetellRealtimeService: Error processing webhook', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Handle call started event - create/update call record immediately
     */
    protected function handleCallStarted(array $payload): array
    {
        $callData = $payload['data'] ?? $payload;
        $callId = $callData['call_id'];
        
        // Extract phone numbers
        $fromNumber = $callData['from_number'] ?? null;
        $toNumber = $callData['to_number'] ?? null;
        
        // Resolve company and branch
        $resolution = $this->resolveCompanyAndBranch($fromNumber, $toNumber);
        
        if (!$resolution['company']) {
            Log::error('Cannot resolve company for call', [
                'from' => $fromNumber,
                'to' => $toNumber
            ]);
            return ['success' => false, 'error' => 'Company not found'];
        }
        
        // Create or update call record
        $call = Call::updateOrCreate(
            ['call_id' => $callId],
            [
                'company_id' => $resolution['company']->id,
                'branch_id' => $resolution['branch']?->id,
                'retell_call_id' => $callId,
                'agent_id' => $callData['agent_id'] ?? null,
                'call_type' => $callData['call_type'] ?? 'inbound',
                'from_number' => $fromNumber,
                'to_number' => $toNumber,
                'direction' => $callData['direction'] ?? 'inbound',
                'call_status' => 'in_progress',
                'start_timestamp' => now(),
                'metadata' => $callData['metadata'] ?? [],
                'custom_analysis_data' => []
            ]
        );
        
        // Find or create customer
        if ($fromNumber) {
            $customer = $this->findOrCreateCustomer($resolution['company'], $fromNumber);
            if ($customer) {
                $call->customer_id = $customer->id;
                $call->save();
            }
        }
        
        // Broadcast real-time update
        $this->broadcastCallUpdate($call, 'started');
        
        // Cache for quick access
        Cache::put("active_call_{$callId}", $call->toArray(), 3600);
        
        return [
            'success' => true,
            'call_id' => $call->id,
            'message' => 'Call started and tracked'
        ];
    }
    
    /**
     * Handle call ended event - finalize call record and extract appointment
     */
    protected function handleCallEnded(array $payload): array
    {
        $callData = $payload['data'] ?? $payload;
        $callId = $callData['call_id'];
        
        // Get existing call or create new
        $call = Call::where('call_id', $callId)->first();
        
        if (!$call) {
            // Try to create from webhook data
            $result = $this->handleCallStarted($payload);
            if (!$result['success']) {
                return $result;
            }
            $call = Call::find($result['call_id']);
        }
        
        // Update call with final data
        $call->update([
            'call_status' => $callData['call_status'] ?? 'completed',
            'end_timestamp' => now(),
            'duration_sec' => $callData['duration'] ?? null,
            'recording_url' => $callData['recording_url'] ?? null,
            'transcript' => $callData['transcript'] ?? null,
            'transcript_object' => $callData['transcript_object'] ?? [],
            'summary' => $callData['call_summary'] ?? null,
            'custom_analysis_data' => $callData['call_analysis'] ?? [],
            'disconnection_reason' => $callData['disconnection_reason'] ?? null,
            'public_log_url' => $callData['public_log_url'] ?? null
        ]);
        
        // Extract and create appointment if booking data exists
        $appointmentData = $this->extractAppointmentData($callData);
        if ($appointmentData) {
            $this->createAppointmentFromCall($call, $appointmentData);
        }
        
        // Remove from active calls cache
        Cache::forget("active_call_{$callId}");
        
        // Broadcast real-time update
        $this->broadcastCallUpdate($call, 'ended');
        
        return [
            'success' => true,
            'call_id' => $call->id,
            'appointment_created' => !empty($appointmentData)
        ];
    }
    
    /**
     * Handle call analyzed event - update with analysis data
     */
    protected function handleCallAnalyzed(array $payload): array
    {
        $callData = $payload['data'] ?? $payload;
        $callId = $callData['call_id'];
        
        $call = Call::where('call_id', $callId)->first();
        if (!$call) {
            return ['success' => false, 'error' => 'Call not found'];
        }
        
        // Update with analysis data
        $call->update([
            'custom_analysis_data' => array_merge(
                $call->custom_analysis_data ?? [],
                $callData['analysis'] ?? []
            ),
            'summary' => $callData['summary'] ?? $call->summary
        ]);
        
        // Check if appointment data is now available
        $appointmentData = $this->extractAppointmentData($callData);
        if ($appointmentData && !$call->appointment_id) {
            $this->createAppointmentFromCall($call, $appointmentData);
        }
        
        return ['success' => true, 'call_id' => $call->id];
    }
    
    /**
     * Handle inbound call - can return dynamic variables
     */
    protected function handleCallInbound(array $payload): array
    {
        // This is called during an active call for dynamic responses
        $callData = $payload['data'] ?? $payload;
        
        // You can return dynamic variables here that Retell will use
        return [
            'success' => true,
            'variables' => [
                'company_name' => 'AskProAI',
                'available_slots' => $this->getAvailableSlots(),
                'next_available' => $this->getNextAvailableSlot()
            ]
        ];
    }
    
    /**
     * Resolve company and branch from phone numbers
     */
    protected function resolveCompanyAndBranch($fromNumber, $toNumber)
    {
        // Try to resolve by to_number (incoming call to our number)
        if ($toNumber) {
            $resolution = $this->phoneResolver->resolveByPhoneNumber($toNumber);
            if ($resolution['branch']) {
                return [
                    'company' => $resolution['branch']->company,
                    'branch' => $resolution['branch']
                ];
            }
        }
        
        // Fallback: get default company
        $company = Company::first();
        return [
            'company' => $company,
            'branch' => $company?->branches()->first()
        ];
    }
    
    /**
     * Find or create customer by phone number
     */
    protected function findOrCreateCustomer($company, $phoneNumber)
    {
        if (!$phoneNumber || !$company) {
            return null;
        }
        
        // Clean phone number
        $cleanPhone = preg_replace('/[^0-9+]/', '', $phoneNumber);
        $searchPhone = substr($cleanPhone, -10); // Last 10 digits
        
        return Customer::firstOrCreate(
            [
                'company_id' => $company->id,
                'phone' => $cleanPhone
            ],
            [
                'name' => 'Anrufer ' . substr($cleanPhone, -4),
                'source' => 'phone',
                'tags' => ['auto-created']
            ]
        );
    }
    
    /**
     * Extract appointment data from various webhook locations
     */
    protected function extractAppointmentData(array $callData): ?array
    {
        // Check multiple possible locations
        $locations = [
            $callData['appointment_data'] ?? null,
            $callData['custom_analysis_data']['appointment'] ?? null,
            $callData['variables']['appointment'] ?? null,
            $callData['metadata']['appointment'] ?? null,
            $callData['call_analysis']['extracted_info']['appointment'] ?? null
        ];
        
        foreach ($locations as $data) {
            if ($data && !empty($data)) {
                return $data;
            }
        }
        
        // Try to parse from transcript
        if (isset($callData['transcript'])) {
            return $this->parseAppointmentFromTranscript($callData['transcript']);
        }
        
        return null;
    }
    
    /**
     * Parse appointment details from transcript
     */
    protected function parseAppointmentFromTranscript($transcript): ?array
    {
        // This is a simplified version - in production use NLP
        if (stripos($transcript, 'termin') !== false || 
            stripos($transcript, 'appointment') !== false) {
            
            // Extract date/time patterns
            // TODO: Implement proper date/time extraction
            
            return [
                'detected' => true,
                'confidence' => 0.7,
                'source' => 'transcript_parsing'
            ];
        }
        
        return null;
    }
    
    /**
     * Create appointment from call data
     */
    protected function createAppointmentFromCall($call, $appointmentData)
    {
        // TODO: Implement appointment creation logic
        Log::info('Would create appointment', [
            'call_id' => $call->id,
            'appointment_data' => $appointmentData
        ]);
    }
    
    /**
     * Broadcast real-time call update
     */
    protected function broadcastCallUpdate($call, $event)
    {
        // TODO: Implement broadcasting (Pusher/WebSockets)
        Log::info('Broadcasting call update', [
            'call_id' => $call->id,
            'event' => $event
        ]);
        
        // For now, just update cache for polling
        Cache::put('latest_call_update', [
            'call_id' => $call->id,
            'event' => $event,
            'timestamp' => now()->toIso8601String()
        ], 60);
    }
    
    /**
     * Get available appointment slots
     */
    protected function getAvailableSlots()
    {
        // TODO: Integrate with calendar service
        return [
            'today' => ['14:00', '15:00', '16:00'],
            'tomorrow' => ['09:00', '10:00', '11:00', '14:00']
        ];
    }
    
    /**
     * Get next available appointment slot
     */
    protected function getNextAvailableSlot()
    {
        // TODO: Integrate with calendar service
        return 'Morgen um 9:00 Uhr';
    }
}