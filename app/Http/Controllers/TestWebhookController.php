<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Call;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Branch;
use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class TestWebhookController extends Controller
{
    /**
     * Test webhook endpoint - FOR DEVELOPMENT ONLY
     */
    public function test(Request $request)
    {
        Log::warning('TEST WEBHOOK RECEIVED - NO SIGNATURE VERIFICATION', [
            'ip' => $request->ip(),
            'event' => $request->input('event')
        ]);
        
        $event = $request->input('event');
        $callData = $request->input('call', []);
        
        if ($event !== 'call_ended' || empty($callData['call_id'])) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid webhook data'
            ], 400);
        }
        
        DB::beginTransaction();
        
        try {
            // Check if call exists
            $existingCall = Call::where('retell_call_id', $callData['call_id'])->first();
            
            if ($existingCall) {
                DB::rollback();
                return response()->json([
                    'success' => true,
                    'message' => 'Call already processed',
                    'call_id' => $existingCall->id
                ]);
            }
            
            // Find company/branch by phone number
            $toNumber = $callData['to_number'] ?? null;
            $company = null;
            $branch = null;
            
            if ($toNumber) {
                $branch = Branch::where('phone_number', $toNumber)
                    ->where('is_active', true)
                    ->first();
                    
                if ($branch) {
                    $company = $branch->company;
                } else {
                    $company = Company::where('phone_number', $toNumber)->first();
                }
            }
            
            // Fallback to first company
            if (!$company) {
                $company = Company::first();
            }
            
            // Create call record without scope
            $call = new Call();
            $call->forceFill(['company_id' => $company->id]);
            $call->branch_id = $branch ? $branch->id : null;
            $call->retell_call_id = $callData['call_id'];
            $call->call_id = $callData['call_id'];
            $call->agent_id = $callData['agent_id'] ?? null;
            $call->from_number = $callData['from_number'] ?? null;
            $call->to_number = $toNumber;
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
            $call->cost = isset($callData['cost']) ? $callData['cost'] / 100 : 0;
            
            // Transcript and analysis
            $call->transcript = $callData['transcript'] ?? null;
            $call->audio_url = $callData['recording_url'] ?? null;
            $call->public_log_url = $callData['public_log_url'] ?? null;
            
            // Extract data from analysis
            if (isset($callData['call_analysis']['custom_analysis_data'])) {
                $customData = $callData['call_analysis']['custom_analysis_data'];
                $call->extracted_name = $customData['_name'] ?? null;
                $call->extracted_email = $customData['_email'] ?? null;
                $call->extracted_date = $customData['_datum__termin'] ?? null;
                $call->extracted_time = $customData['_uhrzeit__termin'] ?? null;
                $call->summary = $callData['call_analysis']['call_summary'] ?? null;
            }
            
            // Also check dynamic variables
            $dynamicVars = $callData['retell_llm_dynamic_variables'] ?? [];
            if (!$call->extracted_name && isset($dynamicVars['name'])) {
                $call->extracted_name = $dynamicVars['name'];
            }
            if (!$call->extracted_date && isset($dynamicVars['datum'])) {
                $call->extracted_date = $dynamicVars['datum'];
            }
            if (!$call->extracted_time && isset($dynamicVars['uhrzeit'])) {
                $call->extracted_time = $dynamicVars['uhrzeit'];
            }
            
            $call->save();
            
            // Create/find customer
            if ($call->from_number) {
                $customer = Customer::withoutGlobalScope(\App\Scopes\TenantScope::class)
                    ->where('phone', $call->from_number)
                    ->where('company_id', $company->id)
                    ->first();
                    
                if (!$customer) {
                    $customer = new Customer();
                    $customer->forceFill([
                        'company_id' => $company->id,
                        'phone' => $call->from_number,
                        'name' => $call->extracted_name ?? 'Unknown Customer',
                        'email' => $call->extracted_email,
                        'created_via' => 'phone_call'
                    ]);
                    $customer->save();
                }
                
                $call->customer_id = $customer->id;
                $call->save();
            }
            
            // Create appointment if booking was confirmed
            $appointmentCreated = false;
            if (!empty($dynamicVars['booking_confirmed']) && 
                !empty($dynamicVars['datum']) && 
                !empty($dynamicVars['uhrzeit'])) {
                
                try {
                    $date = Carbon::parse($dynamicVars['datum']);
                    $time = $dynamicVars['uhrzeit'];
                    
                    // Parse time
                    if (strpos($time, ':') !== false) {
                        [$hours, $minutes] = explode(':', $time);
                    } else {
                        $hours = $time;
                        $minutes = 0;
                    }
                    
                    $startTime = $date->copy()->setTime((int)$hours, (int)$minutes);
                    $endTime = $startTime->copy()->addMinutes(30);
                    
                    $appointment = new Appointment();
                    $appointment->forceFill([
                        'customer_id' => $call->customer_id,
                        'branch_id' => $call->branch_id,
                        'company_id' => $call->company_id,
                        'starts_at' => $startTime,
                        'ends_at' => $endTime,
                        'status' => 'scheduled',
                        'notes' => "Gebucht über Telefon-KI\n" . 
                                  "Service: " . ($dynamicVars['dienstleistung'] ?? 'Nicht angegeben') . "\n" .
                                  "Wunsch: " . ($dynamicVars['kundenwunsch'] ?? ''),
                        'call_id' => $call->id
                    ]);
                    $appointment->save();
                    
                    $call->appointment_id = $appointment->id;
                    $call->save();
                    
                    $appointmentCreated = true;
                    
                    Log::info('Appointment created from test webhook', [
                        'appointment_id' => $appointment->id,
                        'call_id' => $call->id,
                        'customer_name' => $call->extracted_name,
                        'date' => $dynamicVars['datum'],
                        'time' => $dynamicVars['uhrzeit']
                    ]);
                    
                } catch (\Exception $e) {
                    Log::error('Failed to create appointment', [
                        'error' => $e->getMessage(),
                        'call_id' => $call->id
                    ]);
                }
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Webhook processed successfully',
                'call_id' => $call->id,
                'customer_id' => $call->customer_id,
                'appointment_created' => $appointmentCreated,
                'extracted_data' => [
                    'name' => $call->extracted_name,
                    'date' => $call->extracted_date,
                    'time' => $call->extracted_time
                ]
            ]);
            
        } catch (\Exception $e) {
            DB::rollback();
            
            Log::error('Test webhook processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Processing failed: ' . $e->getMessage()
            ], 500);
        }
    }
}