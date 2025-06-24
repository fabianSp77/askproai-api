<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Carbon\Carbon;

class RetellAppointmentCollectorController extends Controller
{
    /**
     * Collect appointment data from Retell.ai custom function
     * This endpoint is called during the conversation to collect structured data
     */
    public function collect(Request $request)
    {
        try {
            // Log incoming request for debugging
            Log::info('Retell collect_appointment_data function called', [
                'data' => $request->all(),
                'headers' => $request->headers->all()
            ]);
            
            // Data from Retell is nested under 'args'
            $data = $request->input('args', $request->all());
            
            // Extract phone number from call data if not provided
            if (empty($data['telefonnummer']) || $data['telefonnummer'] === 'caller_number') {
                // Try multiple sources for phone number
                $phoneNumber = null;
                
                // 1. Try from call object
                $callData = $request->input('call', []);
                if (!empty($callData['from_number']) && $callData['from_number'] !== 'unknown') {
                    $phoneNumber = $callData['from_number'];
                }
                
                // 2. Try from top-level request data
                if (empty($phoneNumber)) {
                    $phoneNumber = $request->input('from_number') ?? $request->input('caller_number');
                }
                
                // 3. Try from custom fields that Retell might send
                if (empty($phoneNumber)) {
                    $phoneNumber = $request->input('call_from_number') ?? $request->input('phone_number');
                }
                
                if (!empty($phoneNumber) && $phoneNumber !== 'unknown') {
                    $data['telefonnummer'] = $phoneNumber;
                    Log::info('Auto-populated phone number', [
                        'phone' => $phoneNumber,
                        'source' => 'auto-detected'
                    ]);
                } else {
                    // Phone number is unknown - agent should ask for it
                    Log::warning('Phone number unknown, agent should request it', [
                        'request_data' => $request->all()
                    ]);
                    // Don't set a default - let validation fail so agent knows to ask
                }
            }
            
            // Validate required fields using the nested data
            $validator = Validator::make($data, [
                'datum' => 'required|string',
                'uhrzeit' => 'required|string',
                'name' => 'required|string',
                'telefonnummer' => 'required|string',
                'dienstleistung' => 'required|string',
                'email' => 'nullable|string|email',
                'mitarbeiter_wunsch' => 'nullable|string',
                'kundenpraeferenzen' => 'nullable|string'
            ]);
            
            if ($validator->fails()) {
                Log::warning('Validation failed for collect_appointment_data', [
                    'errors' => $validator->errors()->toArray(),
                    'data' => $data,
                    'raw_request' => $request->all()
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Bitte geben Sie alle erforderlichen Informationen an',
                    'appointment_id' => null,
                    'reference_id' => null,
                    'confirmation_status' => 'error',
                    'next_steps' => 'Bitte nennen Sie mir die fehlenden Informationen'
                ], 200); // Return 200 to avoid Retell errors
            }
            
            $validated = $validator->validated();
            
            // Generate unique identifiers
            $appointmentId = 'APT-' . date('Y') . '-' . strtoupper(Str::random(6));
            $referenceId = 'REF-' . date('Y') . '-' . strtoupper(Str::random(6));
            
            // Extract call_id from headers or request
            $callId = $request->header('X-Retell-Call-Id') ?? 
                      $request->input('call_id') ?? 
                      'unknown-' . time();
            
            // Store appointment data temporarily (will be processed when call ends)
            $cacheKey = "retell_appointment_data:{$callId}";
            $appointmentData = array_merge($validated, [
                'appointment_id' => $appointmentId,
                'reference_id' => $referenceId,
                'collected_at' => now()->toIso8601String(),
                'call_id' => $callId,
                'status' => 'collected'
            ]);
            
            // Cache for 1 hour (calls shouldn't last that long)
            Cache::put($cacheKey, $appointmentData, 3600);
            
            Log::info('Appointment data collected and cached', [
                'call_id' => $callId,
                'reference_id' => $referenceId,
                'cache_key' => $cacheKey
            ]);
            
            // Prepare response with all expected variables
            $response = [
                'success' => true,
                'message' => 'Ihre Termindaten wurden erfolgreich erfasst',
                'appointment_id' => $appointmentId,
                'reference_id' => $referenceId,
                'confirmation_status' => 'pending',
                'next_steps' => $this->getNextStepsMessage($validated)
            ];
            
            return response()->json($response, 200);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Validation failed for collect_appointment_data', [
                'errors' => $e->errors()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Bitte geben Sie alle erforderlichen Informationen an',
                'appointment_id' => null,
                'reference_id' => null,
                'confirmation_status' => 'error',
                'next_steps' => 'Bitte nennen Sie mir die fehlenden Informationen'
            ], 200); // Return 200 to avoid Retell errors
            
        } catch (\Exception $e) {
            Log::error('Error in collect_appointment_data', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Es ist ein Fehler aufgetreten',
                'appointment_id' => null,
                'reference_id' => null,
                'confirmation_status' => 'error',
                'next_steps' => 'Bitte versuchen Sie es erneut oder rufen Sie später nochmal an'
            ], 200); // Return 200 to avoid Retell errors
        }
    }
    
    /**
     * Generate next steps message based on appointment data
     */
    private function getNextStepsMessage(array $data): string
    {
        $steps = [];
        
        // Email confirmation
        if (!empty($data['email'])) {
            $steps[] = "Sie erhalten eine Bestätigung per E-Mail an {$data['email']}";
        }
        
        // SMS confirmation (always for phone)
        $steps[] = "Eine SMS-Bestätigung wird an Ihre Telefonnummer gesendet";
        
        // Time-based message
        try {
            $appointmentTime = Carbon::parse($data['datum'] . ' ' . $data['uhrzeit']);
            if ($appointmentTime->isToday()) {
                $steps[] = "Wir freuen uns auf Ihren Besuch heute um {$data['uhrzeit']} Uhr";
            } elseif ($appointmentTime->isTomorrow()) {
                $steps[] = "Wir freuen uns auf Ihren Besuch morgen um {$data['uhrzeit']} Uhr";
            } else {
                $steps[] = "Wir freuen uns auf Ihren Besuch am {$data['datum']} um {$data['uhrzeit']} Uhr";
            }
        } catch (\Exception $e) {
            // Fallback if date parsing fails
            $steps[] = "Wir freuen uns auf Ihren Besuch";
        }
        
        return implode('. ', $steps);
    }
    
    /**
     * Test endpoint to verify the collector is working
     */
    public function test(Request $request)
    {
        return response()->json([
            'success' => true,
            'message' => 'Retell appointment collector is working',
            'timestamp' => now()->toIso8601String(),
            'expected_fields' => [
                'datum' => 'required|string',
                'uhrzeit' => 'required|string', 
                'name' => 'required|string',
                'telefonnummer' => 'required|string',
                'dienstleistung' => 'required|string',
                'email' => 'optional|string',
                'mitarbeiter_wunsch' => 'optional|string',
                'kundenpraeferenzen' => 'optional|string'
            ]
        ]);
    }
}