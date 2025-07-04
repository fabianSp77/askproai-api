<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RetellAgentUpdateController extends Controller
{
    /**
     * Update agent configuration in real-time
     */
    public function updateAgent(Request $request, string $agentId)
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
            
            // Ensure agent ID has proper format
            if (!str_starts_with($agentId, 'agent_')) {
                $agentId = 'agent_' . $agentId;
            }
            
            // Get current agent data first
            $currentResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
            ])->get("https://api.retellai.com/get-agent/{$agentId}");
            
            if (!$currentResponse->successful()) {
                return response()->json(['error' => 'Failed to fetch current agent data'], 500);
            }
            
            $currentData = $currentResponse->json();
            
            // Prepare update data
            $updateData = $request->all();
            
            // Handle special fields that need to be updated in the LLM
            $llmFields = ['general_prompt', 'custom_functions', 'states', 'flow_structure'];
            $llmUpdates = [];
            $agentUpdates = [];
            
            foreach ($updateData as $field => $value) {
                if (in_array($field, $llmFields)) {
                    $llmUpdates[$field] = $value;
                } else {
                    $agentUpdates[$field] = $value;
                }
            }
            
            // Update agent fields if any
            if (!empty($agentUpdates)) {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                ])->patch("https://api.retellai.com/update-agent/{$agentId}", $agentUpdates);
                
                if (!$response->successful()) {
                    Log::error('Failed to update agent', [
                        'agent_id' => $agentId,
                        'status' => $response->status(),
                        'response' => $response->body()
                    ]);
                    return response()->json(['error' => 'Failed to update agent'], 500);
                }
            }
            
            // Update LLM if it's a retell-llm and we have LLM fields to update
            if (!empty($llmUpdates) && 
                isset($currentData['response_engine']['type']) && 
                $currentData['response_engine']['type'] === 'retell-llm' &&
                isset($currentData['response_engine']['llm_id'])) {
                
                $llmId = $currentData['response_engine']['llm_id'];
                
                // Get current LLM data
                $llmResponse = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                ])->get("https://api.retellai.com/get-retell-llm/{$llmId}");
                
                if ($llmResponse->successful()) {
                    $llmData = $llmResponse->json();
                    
                    // Merge updates
                    $llmData = array_merge($llmData, $llmUpdates);
                    
                    // Update LLM
                    $updateLlmResponse = Http::withHeaders([
                        'Authorization' => 'Bearer ' . $apiKey,
                    ])->patch("https://api.retellai.com/update-retell-llm/{$llmId}", $llmData);
                    
                    if (!$updateLlmResponse->successful()) {
                        Log::error('Failed to update LLM', [
                            'llm_id' => $llmId,
                            'status' => $updateLlmResponse->status(),
                            'response' => $updateLlmResponse->body()
                        ]);
                        return response()->json(['error' => 'Failed to update LLM configuration'], 500);
                    }
                }
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Agent updated successfully',
                'updated_fields' => array_keys($updateData)
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error updating agent: ' . $e->getMessage());
            return response()->json(['error' => 'Internal server error'], 500);
        }
    }
    
    /**
     * Publish a specific version of the agent
     */
    public function publishAgent(Request $request, string $agentId)
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
            
            // Ensure agent ID has proper format
            if (!str_starts_with($agentId, 'agent_')) {
                $agentId = 'agent_' . $agentId;
            }
            
            $version = $request->input('version');
            
            // Update agent to use this version
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
            ])->patch("https://api.retellai.com/update-agent/{$agentId}", [
                'version' => $version
            ]);
            
            if (!$response->successful()) {
                return response()->json(['error' => 'Failed to publish version'], 500);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Version published successfully',
                'published_version' => $version
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error publishing agent: ' . $e->getMessage());
            return response()->json(['error' => 'Internal server error'], 500);
        }
    }
}