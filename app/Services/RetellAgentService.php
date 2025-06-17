<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class RetellAgentService
{
    private $apiKey;
    private $baseUrl = 'https://api.retellai.com/v1';

    public function __construct()
    {
        $this->apiKey = config('services.retell.api_key');
    }

    public function getAgentDetails($agentId)
    {
        return Cache::remember("retell_agent_{$agentId}", 300, function () use ($agentId) {
            try {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ])->get("{$this->baseUrl}/agents/{$agentId}");

                if ($response->successful()) {
                    $data = $response->json();
                    
                    // Zusätzliche Statistiken abrufen
                    $data['statistics'] = $this->getAgentStatistics($agentId);
                    
                    return $data;
                }
                
                Log::error('Failed to fetch agent details', [
                    'agent_id' => $agentId,
                    'response' => $response->body()
                ]);
                
                return null;
            } catch (\Exception $e) {
                Log::error('Error fetching agent details', [
                    'agent_id' => $agentId,
                    'error' => $e->getMessage()
                ]);
                return null;
            }
        });
    }

    public function getAgentStatistics($agentId, $days = 7)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->get("{$this->baseUrl}/agents/{$agentId}/calls", [
                'from_date' => now()->subDays($days)->toIso8601String(),
                'to_date' => now()->toIso8601String()
            ]);

            if ($response->successful()) {
                $calls = $response->json();
                
                return [
                    'total_calls' => count($calls),
                    'success_rate' => $this->calculateSuccessRate($calls),
                    'average_duration' => $this->calculateAverageDuration($calls),
                    'last_activity' => $this->getLastActivity($calls)
                ];
            }
        } catch (\Exception $e) {
            Log::error('Error fetching agent statistics', ['error' => $e->getMessage()]);
        }
        
        // Fallback-Werte
        return [
            'total_calls' => 0,
            'success_rate' => 0,
            'average_duration' => 0,
            'last_activity' => null
        ];
    }

    public function listAgents()
    {
        return Cache::remember('retell_agents_list', 600, function () {
            try {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ])->get("{$this->baseUrl}/agents");

                if ($response->successful()) {
                    return $response->json();
                }
                
                return [];
            } catch (\Exception $e) {
                Log::error('Error listing agents', ['error' => $e->getMessage()]);
                return [];
            }
        });
    }

    public function validateAgentConfiguration($agentId)
    {
        $agent = $this->getAgentDetails($agentId);
        
        if (!$agent) {
            return [
                'valid' => false,
                'errors' => ['Agent konnte nicht gefunden werden']
            ];
        }
        
        $errors = [];
        
        // Validierungsregeln
        if (empty($agent['webhook_url'])) {
            $errors[] = 'Webhook-URL ist nicht konfiguriert';
        }
        
        if (empty($agent['prompt'])) {
            $errors[] = 'Agent-Prompt ist nicht definiert';
        }
        
        if (!isset($agent['language']) || $agent['language'] !== 'de') {
            $errors[] = 'Agent ist nicht auf Deutsch konfiguriert';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'agent' => $agent
        ];
    }
    
    private function calculateSuccessRate($calls)
    {
        if (empty($calls)) return 0;
        
        $successful = array_filter($calls, function($call) {
            return $call['status'] === 'completed' && $call['call_successful'] === true;
        });
        
        return round((count($successful) / count($calls)) * 100, 2);
    }
    
    private function calculateAverageDuration($calls)
    {
        if (empty($calls)) return 0;
        
        $totalDuration = array_sum(array_column($calls, 'duration'));
        return round($totalDuration / count($calls));
    }
    
    private function getLastActivity($calls)
    {
        if (empty($calls)) return null;
        
        $latest = max(array_column($calls, 'created_at'));
        return $latest ? \Carbon\Carbon::parse($latest) : null;
    }
}
