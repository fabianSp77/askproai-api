<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Call;
use App\Models\Company;
use App\Models\Customer;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class RetellDebugController extends Controller
{
    /**
     * Debug webhook endpoint - no signature verification
     */
    public function debugWebhook(Request $request)
    {
        Log::info('DEBUG: Retell webhook received', [
            'headers' => $request->headers->all(),
            'body' => $request->all(),
            'ip' => $request->ip()
        ]);
        
        $event = $request->input('event');
        $callData = $request->input('call');
        
        if (!$callData || !isset($callData['call_id'])) {
            // Still return 200 to acknowledge receipt
            Log::error('Invalid call data received', ['body' => $request->all()]);
            return response()->json(['status' => 'acknowledged', 'error' => 'Invalid call data'], 200);
        }
        
        // Get the first company (or determine from phone number)
        $company = Company::first();
        if (!$company) {
            Log::error('No company found for webhook processing');
            return response()->json(['status' => 'acknowledged', 'error' => 'No company found'], 200);
        }
        
        // Bypass tenant scope
        app()->bind('current_company_id', function () use ($company) {
            return $company->id;
        });
        
        try {
            // Check if call already exists
            $existingCall = Call::where('call_id', $callData['call_id'])
                ->orWhere('retell_call_id', $callData['call_id'])
                ->first();
            
            if ($existingCall) {
                Log::info('Call already exists', ['call_id' => $callData['call_id']]);
                return response()->json([
                    'success' => true,
                    'message' => 'Call already processed',
                    'call_id' => $existingCall->id
                ], 200);
            }
            
            // Create new call record
            $call = Call::create([
                'company_id' => $company->id,
                'call_id' => $callData['call_id'],
                'retell_call_id' => $callData['call_id'],
                'agent_id' => $callData['agent_id'] ?? null,
                'from_number' => $callData['from_number'] ?? null,
                'to_number' => $callData['to_number'] ?? null,
                'direction' => $callData['call_type'] ?? 'inbound',
                'status' => $callData['call_status'] ?? 'completed',
                'start_timestamp' => isset($callData['start_timestamp']) 
                    ? Carbon::createFromTimestampMs($callData['start_timestamp']) 
                    : now(),
                'end_timestamp' => isset($callData['end_timestamp']) 
                    ? Carbon::createFromTimestampMs($callData['end_timestamp']) 
                    : now(),
                'duration_sec' => isset($callData['duration_ms']) 
                    ? round($callData['duration_ms'] / 1000) 
                    : 0,
                'cost' => isset($callData['cost']) ? $callData['cost'] / 100 : 0,
                'transcript' => $callData['transcript'] ?? null,
                'summary' => $callData['call_analysis']['call_summary'] ?? null,
                'sentiment' => $callData['call_analysis']['sentiment'] ?? null,
                'analysis' => $callData['call_analysis'] ?? null,
                'transcript_object' => $callData['transcript_object'] ?? null,
                'audio_url' => $callData['recording_url'] ?? null,
                'public_log_url' => $callData['public_log_url'] ?? null,
            ]);
            
            // Extract customer information if available
            if (isset($callData['call_analysis']['custom_analysis_data'])) {
                $customData = $callData['call_analysis']['custom_analysis_data'];
                
                if (isset($customData['_name'])) {
                    $call->extracted_name = $customData['_name'];
                }
                if (isset($customData['_email'])) {
                    $call->extracted_email = $customData['_email'];
                }
                if (isset($customData['_datum__termin'])) {
                    $call->extracted_date = $customData['_datum__termin'];
                }
                if (isset($customData['_uhrzeit__termin'])) {
                    $call->extracted_time = $customData['_uhrzeit__termin'];
                }
                
                $call->save();
            }
            
            // Try to create/find customer
            if ($callData['from_number']) {
                $customer = Customer::firstOrCreate(
                    ['phone' => $callData['from_number']],
                    [
                        'company_id' => $company->id,
                        'name' => $call->extracted_name ?? 'Unknown',
                        'email' => $call->extracted_email,
                    ]
                );
                
                $call->customer_id = $customer->id;
                $call->save();
            }
            
            Log::info('Call successfully saved', [
                'call_id' => $call->id,
                'retell_call_id' => $call->retell_call_id,
                'from' => $call->from_number,
                'name' => $call->extracted_name
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Call processed successfully',
                'call_id' => $call->id,
                'extracted_name' => $call->extracted_name
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('Error processing debug webhook', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'call_id' => $callData['call_id'] ?? null
            ]);
            
            // Always return 200 to acknowledge receipt
            return response()->json([
                'status' => 'acknowledged_with_error',
                'error' => 'Processing failed',
                'message' => $e->getMessage(),
                'call_id' => $callData['call_id'] ?? null
            ], 200);
        }
    }
}