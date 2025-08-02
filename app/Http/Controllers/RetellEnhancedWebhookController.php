<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Call;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Branch;
use App\Scopes\TenantScope;
use App\Models\Appointment;
use App\Services\PhoneNumberResolver;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RetellEnhancedWebhookController extends Controller
{
    protected $phoneResolver;
    
    public function __construct(PhoneNumberResolver $phoneResolver)
    {
        $this->phoneResolver = $phoneResolver;
    }
    
    /**
     * Enhanced webhook handler with proper multi-tenancy support
     */
    public function handle(Request $request)
    {
        // Log all incoming webhook data
        Log::info('ENHANCED: Retell webhook received', [
            'headers' => $request->headers->all(),
            'body' => $request->all(),
            'ip' => $request->ip()
        ]);
        
        $event = $request->input('event');
        $callData = $request->input('call', []);
        
        // Validate required data
        if (!$callData || !isset($callData['call_id'])) {
            return response()->json(['status' => 'acknowledged', 'error' => 'Invalid call data'], 200);
        }
        
        // Only process call_ended events
        if ($event !== 'call_ended') {
            return response()->json(['status' => 'acknowledged', 'message' => 'Event not processed'], 200);
        }
        
        DB::beginTransaction();
        
        try {
            // Resolve branch and company from webhook data
            $resolution = $this->phoneResolver->resolveFromWebhook($callData);
            
            Log::info('Phone number resolution result', $resolution);
            
            // Get company
            $companyId = $resolution['company_id'];
            if (!$companyId) {
                // Fallback to first company
                $company = Company::first();
                if (!$company) {
                    throw new \Exception('No company found for webhook processing');
                }
                $companyId = $company->id;
            }
            
            // Check if call already exists
            $existingCall = Call::where('retell_call_id', $callData['call_id'])
                ->first();
            
            if ($existingCall) {
                DB::rollback();
                return response()->json([
                    'success' => true,
                    'message' => 'Call already processed',
                    'call_id' => $existingCall->id
                ], 200);
            }
            
            // Create call record without tenant scope
            $call = new Call();
            $call->company_id = $companyId;
            $call->branch_id = $resolution['branch_id'];
            $call->retell_call_id = $callData['call_id'];
            $call->call_id = $callData['call_id'];
            $call->agent_id = $callData['agent_id'] ?? null;
            $call->from_number = $callData['from_number'] ?? null;
            $call->to_number = $callData['to_number'] ?? null;
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
            
            // Transcript and URLs
            $call->transcript = $callData['transcript'] ?? null;
            $call->audio_url = $callData['recording_url'] ?? null;
            $call->public_log_url = $callData['public_log_url'] ?? null;
            
            // Extract data from custom analysis
            if (isset($callData['call_analysis']['custom_analysis_data'])) {
                $customData = $callData['call_analysis']['custom_analysis_data'];
                $call->extracted_name = $customData['_name'] ?? null;
                $call->extracted_email = $customData['_email'] ?? null;
                $call->extracted_date = $customData['_datum__termin'] ?? null;
                $call->extracted_time = $customData['_uhrzeit__termin'] ?? null;
            }
            
            // Also check dynamic variables
            $dynamicVars = $callData['retell_llm_dynamic_variables'] ?? [];
            
            // Store dynamic variables in database
            $call->retell_dynamic_variables = json_encode($dynamicVars);
            
            if (!$call->extracted_name && isset($dynamicVars['name'])) {
                $call->extracted_name = $dynamicVars['name'];
            }
            if (!$call->extracted_date && isset($dynamicVars['datum'])) {
                $call->extracted_date = $dynamicVars['datum'];
            }
            if (!$call->extracted_time && isset($dynamicVars['uhrzeit'])) {
                $call->extracted_time = $dynamicVars['uhrzeit'];
            }
            
            // Summary and sentiment
            $call->summary = $callData['call_analysis']['call_summary'] ?? null;
            $call->sentiment = $callData['call_analysis']['sentiment'] ?? null;
            
            // Store full analysis
            $call->analysis = $callData['call_analysis'] ?? null;
            
            // Save call first to get ID
            $call->save();
            
            // Create/find customer if phone number exists
            if ($call->from_number) {
                $customer = Customer::where('phone', $call->from_number)
                    ->where('company_id', $companyId)
                    ->first();
                
                if (!$customer) {
                    // Create customer directly via DB to avoid phone validation issues
                    $customerId = DB::table('customers')->insertGetId([
                        'company_id' => $companyId,
                        'phone' => $call->from_number,
                        'name' => $call->extracted_name ?? 'Unknown Customer',
                        'email' => $call->extracted_email,
                        'created_via' => 'phone_call',
                        'preferred_branch_id' => $resolution['branch_id'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                    
                    $customer = Customer::find($customerId);
                }
                
                $call->customer_id = $customer->id;
                $call->save();
            }
            
            // Create appointment if booking was confirmed
            $appointmentCreated = false;
            Log::info('Checking appointment creation conditions', [
                'booking_confirmed' => $dynamicVars['booking_confirmed'] ?? null,
                'datum' => $dynamicVars['datum'] ?? null,
                'uhrzeit' => $dynamicVars['uhrzeit'] ?? null,
                'call_id' => $call->id
            ]);
            
            $bookingConfirmed = isset($dynamicVars['booking_confirmed']) && 
                              ($dynamicVars['booking_confirmed'] === true || 
                               $dynamicVars['booking_confirmed'] === 'true' ||
                               $dynamicVars['booking_confirmed'] === '1');
                               
            if ($bookingConfirmed && 
                !empty($dynamicVars['datum']) && 
                !empty($dynamicVars['uhrzeit'])) {
                
                try {
                    $appointmentData = $this->createAppointmentFromCall($call, $dynamicVars, $resolution);
                    if ($appointmentData) {
                        $appointmentCreated = true;
                        $call->appointment_id = $appointmentData['appointment_id'];
                        $call->save();
                    }
                } catch (\Exception $e) {
                    Log::error('Failed to create appointment from webhook', [
                        'error' => $e->getMessage(),
                        'call_id' => $call->id
                    ]);
                }
            }
            
            DB::commit();
            
            Log::info('Call successfully processed', [
                'call_id' => $call->id,
                'customer_id' => $call->customer_id,
                'branch_id' => $call->branch_id,
                'appointment_created' => $appointmentCreated,
                'resolution_method' => $resolution['resolution_method'],
                'confidence' => $resolution['confidence']
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Call processed successfully',
                'call_id' => $call->id,
                'customer_id' => $call->customer_id,
                'branch_id' => $call->branch_id,
                'appointment_created' => $appointmentCreated,
                'resolution' => [
                    'method' => $resolution['resolution_method'],
                    'confidence' => $resolution['confidence']
                ]
            ], 200);
            
        } catch (\Exception $e) {
            DB::rollback();
            
            Log::error('Enhanced webhook processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'call_id' => $callData['call_id'] ?? null
            ]);
            
            // Always return 200 to acknowledge receipt
            return response()->json([
                'status' => 'acknowledged_with_error',
                'error' => 'Processing failed',
                'message' => $e->getMessage()
            ], 200);
        }
    }
    
    /**
     * Create appointment from call data
     */
    protected function createAppointmentFromCall(Call $call, array $dynamicVars, array $resolution): ?array
    {
        try {
            // Parse date and time
            $date = Carbon::parse($dynamicVars['datum']);
            $time = $dynamicVars['uhrzeit'];
            
            // Parse time format
            if (strpos($time, ':') !== false) {
                [$hours, $minutes] = explode(':', $time);
            } else {
                $hours = $time;
                $minutes = 0;
            }
            
            $startTime = $date->copy()->setTime((int)$hours, (int)$minutes);
            $endTime = $startTime->copy()->addMinutes(30); // Default 30 min duration
            
            // Get branch for Cal.com booking
            $branch = Branch::find($resolution['branch_id'] ?? $call->branch_id);
                
            if (!$branch || !$branch->calcom_event_type_id) {
                Log::error('Branch missing or no Cal.com event type configured', [
                    'branch_id' => $resolution['branch_id'] ?? $call->branch_id
                ]);
                return null;
            }
            
            // Get customer data
            $customer = Customer::find($call->customer_id);
                
            if (!$customer) {
                Log::error('Customer not found for appointment', [
                    'customer_id' => $call->customer_id
                ]);
                return null;
            }
            
            // Book in Cal.com first
            try {
                $calcomService = new \App\Services\CalcomV2Service();
                $calcomBooking = $calcomService->bookAppointment(
                    $branch->calcom_event_type_id,
                    $startTime->toIso8601String(),
                    $endTime->toIso8601String(),
                    [
                        'name' => $customer->name ?: 'Kunde',
                        'email' => $customer->email ?: 'kunde@example.com',
                        'phone' => $customer->phone ?: '+491234567890',
                        'timeZone' => 'Europe/Berlin'
                    ],
                    "Gebucht über Telefon-KI\nService: " . ($dynamicVars['dienstleistung'] ?? 'Nicht angegeben')
                );
                
                if (!$calcomBooking) {
                    Log::error('Cal.com booking failed');
                    return null;
                }
                
                Log::info('Cal.com booking created', [
                    'booking_id' => $calcomBooking['id'] ?? null,
                    'uid' => $calcomBooking['uid'] ?? null
                ]);
            } catch (\Exception $e) {
                Log::error('Cal.com booking exception', [
                    'error' => $e->getMessage()
                ]);
                return null;
            }
            
            // Create local appointment record
            $appointment = new Appointment();
            $appointment->company_id = $call->company_id;
            $appointment->branch_id = $branch->id;
            $appointment->customer_id = $call->customer_id;
            $appointment->call_id = $call->id;
            $appointment->starts_at = $startTime;
            $appointment->ends_at = $endTime;
            $appointment->status = 'scheduled';
            $appointment->calcom_booking_id = $calcomBooking['id'] ?? null;
            $appointment->calcom_booking_uid = $calcomBooking['uid'] ?? null;
            $appointment->notes = "Gebucht über Telefon-KI\n" . 
                "Service: " . ($dynamicVars['dienstleistung'] ?? 'Nicht angegeben') . "\n" .
                "Wunsch: " . ($dynamicVars['kundenwunsch'] ?? '');
            
            $appointment->save();
            
            Log::info('Appointment created from webhook', [
                'appointment_id' => $appointment->id,
                'call_id' => $call->id,
                'branch_id' => $appointment->branch_id,
                'start_time' => $startTime->toDateTimeString(),
                'service' => $dynamicVars['dienstleistung'] ?? null
            ]);
            
            return [
                'appointment_id' => $appointment->id,
                'start_time' => $startTime->toDateTimeString()
            ];
            
        } catch (\Exception $e) {
            Log::error('Failed to create appointment', [
                'error' => $e->getMessage(),
                'call_id' => $call->id,
                'dynamic_vars' => $dynamicVars
            ]);
            
            return null;
        }
    }
}