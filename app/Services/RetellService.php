<?php
// MARKED_FOR_DELETION - 2025-06-17


namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Services\Security\SensitiveDataMasker;

class RetellService
{
    private $apiKey;
    private $baseUrl = 'https://api.retellai.com';
    private SensitiveDataMasker $masker;

    public function __construct($apiKey = null)
    {
        // Use provided API key or fall back to config
        if ($apiKey) {
            $this->apiKey = $apiKey;
        } else {
            // Try multiple config keys for compatibility
            $this->apiKey = config('services.retell.api_key') 
                ?? config('services.retell.token');
        }
        
        // Use the base URL from config
        $baseUrl = config('services.retell.base', 'https://api.retellai.com');
        if ($baseUrl) {
            $this->baseUrl = rtrim($baseUrl, '/');
        }
        
        $this->masker = new SensitiveDataMasker();
    }

    /**
     * Alle Agenten abrufen
     */
    public function getAgents()
    {
        // Cache für 5 Minuten
        return Cache::remember('retell_agents', 300, function () {
            try {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                ])->get($this->baseUrl . '/list-agents');

                if ($response->successful()) {
                    return $response->json();
                }

                Log::error('Retell API Error beim Abrufen der Agenten', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);

                return [];
            } catch (\Exception $e) {
                Log::error('Retell API Exception', ['error' => $e->getMessage()]);
                return [];
            }
        });
    }

    /**
     * Einzelnen Agenten abrufen
     */
    public function getAgent($agentId)
    {
        return Cache::remember('retell_agent_' . $agentId, 300, function () use ($agentId) {
            try {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                ])->get($this->baseUrl . '/get-agent/' . $agentId);

                if ($response->successful()) {
                    return $response->json();
                }

                return null;
            } catch (\Exception $e) {
                Log::error('Retell API Exception', ['error' => $e->getMessage()]);
                return null;
            }
        });
    }

    /**
     * Update agent metadata
     */
    public function updateAgent($agentId, array $data)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/v2/update-agent', array_merge(['agent_id' => $agentId], $data));

            if ($response->successful()) {
                // Clear cache for this agent
                Cache::forget('retell_agent_' . $agentId);
                Cache::forget('retell_agents');
                
                Log::info('Retell agent updated successfully', [
                    'agent_id' => $agentId,
                    'data' => $data
                ]);
                
                return $response->json();
            }

            Log::error('Failed to update Retell agent', [
                'agent_id' => $agentId,
                'status' => $response->status(),
                'body' => $response->body(),
                'url' => $this->baseUrl . '/agents/' . $agentId,
                'api_key_length' => strlen($this->apiKey ?? ''),
                'data' => $data
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Retell API Exception during update', [
                'agent_id' => $agentId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Cache leeren
     */
    public function clearCache()
    {
        Cache::forget('retell_agents');
        // Einzelne Agent-Caches werden bei Bedarf geleert
    }
    
    /**
     * Build response for inbound calls with dynamic variables
     * 
     * @param string $agentId The agent ID to handle the call
     * @param string $fromNumber The caller's phone number
     * @param array $dynamicVariables Additional variables to pass to the agent
     * @return array
     */
    public static function buildInboundResponse($agentId, $fromNumber = null, $dynamicVariables = [])
    {
        $response = [
            'response' => [
                'agent_id' => $agentId
            ]
        ];
        
        // Add dynamic variables if provided
        if (!empty($dynamicVariables) || $fromNumber) {
            $response['response']['dynamic_variables'] = $dynamicVariables;
            
            if ($fromNumber) {
                $response['response']['dynamic_variables']['caller_number'] = $fromNumber;
            }
        }
        
        // Add metadata for tracking
        $response['response']['metadata'] = [
            'handled_by' => 'AskProAI',
            'timestamp' => now()->toIso8601String()
        ];
        
        Log::info('Built inbound response for Retell.ai', [
            'agent_id' => $agentId,
            'caller' => $fromNumber,
            'variables' => $dynamicVariables
        ]);
        
        return $response;
    }
}
