<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\PhoneNumber;
use App\Models\RetellAgent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RetellTestCallController extends Controller
{
    /**
     * Initiate a test call for an agent
     */
    public function initiateTestCall(Request $request)
    {
        $request->validate([
            'agent_id' => 'required|string',
            'phone_number' => 'required|string',
            'test_duration' => 'nullable|integer|min:30|max:300', // seconds
        ]);
        
        try {
            // Get company and API key
            $company = Company::first();
            if (!$company || !$company->retell_api_key) {
                return response()->json(['error' => 'API key not configured'], 500);
            }
            
            $apiKey = $company->retell_api_key;
            if (strlen($apiKey) > 50) {
                try {
                    $apiKey = decrypt($apiKey);
                } catch (\Exception $e) {}
            }
            
            // Get agent's phone number
            $agent = RetellAgent::where('agent_id', $request->agent_id)
                ->with('phoneNumber')
                ->first();
                
            if (!$agent || !$agent->phoneNumber) {
                return response()->json(['error' => 'Agent or phone number not found'], 404);
            }
            
            // Prepare test call parameters
            $callParams = [
                'agent_id' => $request->agent_id,
                'from_number' => $agent->phoneNumber->phone_number,
                'to_number' => $request->phone_number,
                'metadata' => [
                    'test_call' => true,
                    'initiated_by' => auth()->user()->email ?? 'system',
                    'test_duration' => $request->test_duration ?? 60,
                ],
            ];
            
            // Make API call to Retell to initiate call
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->post('https://api.retellai.com/create-phone-call', $callParams);
            
            if ($response->successful()) {
                $callData = $response->json();
                
                // Log the test call
                Log::info('Test call initiated', [
                    'agent_id' => $request->agent_id,
                    'call_id' => $callData['call_id'] ?? null,
                    'to_number' => $request->phone_number,
                    'user' => auth()->user()->email ?? 'system',
                ]);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Test call initiated successfully',
                    'call_id' => $callData['call_id'] ?? null,
                    'status' => $callData['status'] ?? 'initiated',
                ]);
            } else {
                Log::error('Failed to initiate test call', [
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);
                
                return response()->json([
                    'error' => 'Failed to initiate test call',
                    'details' => $response->json()['message'] ?? 'Unknown error',
                ], $response->status());
            }
            
        } catch (\Exception $e) {
            Log::error('Error initiating test call: ' . $e->getMessage());
            return response()->json(['error' => 'Internal server error'], 500);
        }
    }
    
    /**
     * Get test call status
     */
    public function getTestCallStatus(Request $request, string $callId)
    {
        try {
            $company = Company::first();
            if (!$company || !$company->retell_api_key) {
                return response()->json(['error' => 'API key not configured'], 500);
            }
            
            $apiKey = $company->retell_api_key;
            if (strlen($apiKey) > 50) {
                try {
                    $apiKey = decrypt($apiKey);
                } catch (\Exception $e) {}
            }
            
            // Get call details from Retell
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
            ])->get("https://api.retellai.com/get-call/{$callId}");
            
            if ($response->successful()) {
                $callData = $response->json();
                
                return response()->json([
                    'call_id' => $callId,
                    'status' => $callData['status'] ?? 'unknown',
                    'duration' => $callData['duration'] ?? 0,
                    'start_time' => $callData['start_timestamp'] ?? null,
                    'end_time' => $callData['end_timestamp'] ?? null,
                    'recording_url' => $callData['recording_url'] ?? null,
                    'transcript' => $callData['transcript'] ?? null,
                ]);
            } else {
                return response()->json(['error' => 'Call not found'], 404);
            }
            
        } catch (\Exception $e) {
            Log::error('Error getting test call status: ' . $e->getMessage());
            return response()->json(['error' => 'Internal server error'], 500);
        }
    }
    
    /**
     * Get suggested test scenarios for an agent
     */
    public function getTestScenarios(Request $request, string $agentId)
    {
        try {
            // Get agent configuration to suggest relevant test scenarios
            $agent = RetellAgent::where('agent_id', $agentId)->first();
            
            if (!$agent) {
                return response()->json(['error' => 'Agent not found'], 404);
            }
            
            $scenarios = [
                [
                    'id' => 'basic_greeting',
                    'name' => 'Basic Greeting',
                    'description' => 'Test the agent\'s initial greeting and introduction',
                    'prompt' => 'Say hello and ask how the agent can help you',
                    'expected_behavior' => 'Agent should greet politely and explain their purpose',
                ],
                [
                    'id' => 'appointment_booking',
                    'name' => 'Appointment Booking',
                    'description' => 'Test the appointment booking flow',
                    'prompt' => 'Request to book an appointment for next week',
                    'expected_behavior' => 'Agent should ask for details and confirm availability',
                ],
                [
                    'id' => 'information_request',
                    'name' => 'Information Request',
                    'description' => 'Test how the agent handles information requests',
                    'prompt' => 'Ask about business hours and services',
                    'expected_behavior' => 'Agent should provide accurate information',
                ],
                [
                    'id' => 'objection_handling',
                    'name' => 'Objection Handling',
                    'description' => 'Test how the agent handles objections or concerns',
                    'prompt' => 'Express concerns about pricing or scheduling',
                    'expected_behavior' => 'Agent should address concerns professionally',
                ],
                [
                    'id' => 'edge_case',
                    'name' => 'Edge Case Handling',
                    'description' => 'Test unusual requests or edge cases',
                    'prompt' => 'Make an unusual request or ask off-topic questions',
                    'expected_behavior' => 'Agent should handle gracefully and redirect to main purpose',
                ],
            ];
            
            // If agent has custom functions, add specific test scenarios
            if (isset($agent->configuration['custom_functions']) && count($agent->configuration['custom_functions']) > 0) {
                foreach ($agent->configuration['custom_functions'] as $function) {
                    $scenarios[] = [
                        'id' => 'function_' . $function['name'],
                        'name' => 'Test Function: ' . $function['name'],
                        'description' => 'Test the ' . $function['name'] . ' function',
                        'prompt' => 'Trigger scenario that requires ' . $function['name'],
                        'expected_behavior' => $function['description'] ?? 'Function should execute correctly',
                    ];
                }
            }
            
            return response()->json([
                'agent_id' => $agentId,
                'scenarios' => $scenarios,
                'tips' => [
                    'Speak clearly and naturally',
                    'Test both positive and negative scenarios',
                    'Note any unexpected behaviors',
                    'Try interrupting the agent mid-sentence',
                    'Test the agent\'s ability to handle silence',
                ],
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error getting test scenarios: ' . $e->getMessage());
            return response()->json(['error' => 'Internal server error'], 500);
        }
    }
}