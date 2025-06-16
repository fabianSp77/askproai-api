<?php

namespace App\Services\Integrations;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class RetellDeepIntegration
{
    private ?string $apiKey;
    private string $baseUrl = 'https://api.retellai.com/v1';

    public function __construct()
    {
        $this->apiKey = config('services.retell.api_key') ?? config('services.retell.default_api_key') ?? null;
    }

    public function getAgentFullDetails(string $agentId): array
    {
        return Cache::remember("retell_agent_{$agentId}", 300, function() use ($agentId) {
            try {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ])->get("{$this->baseUrl}/agents/{$agentId}");

                if (!$response->successful()) {
                    Log::error('Failed to fetch Retell agent', [
                        'agent_id' => $agentId,
                        'status' => $response->status(),
                        'body' => $response->body()
                    ]);
                    return [];
                }

                $agentData = $response->json();

                return [
                    'basic_info' => $agentData,
                    'service_mappings' => $this->extractServiceMappings($agentData),
                    'edit_url' => "https://app.retellai.com/agents/{$agentId}/edit",
                    'last_sync' => now()->toIso8601String()
                ];
            } catch (\Exception $e) {
                Log::error('Error fetching Retell agent details', [
                    'agent_id' => $agentId,
                    'error' => $e->getMessage()
                ]);
                return [];
            }
        });
    }

    public function syncAgentConfiguration(string $agentId, array $updates): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->patch("{$this->baseUrl}/agents/{$agentId}", [
                'webhook_url' => route('api.retell.webhook', ['branch' => $updates['branch_id']]),
                'metadata' => [
                    'askproai_branch_id' => $updates['branch_id'],
                    'askproai_company_id' => $updates['company_id'],
                    'last_sync' => now()->toIso8601String()
                ]
            ]);

            Cache::forget("retell_agent_{$agentId}");

            return [
                'success' => $response->successful(),
                'data' => $response->json(),
                'status' => $response->status()
            ];
        } catch (\Exception $e) {
            Log::error('Error syncing Retell agent', [
                'agent_id' => $agentId,
                'error' => $e->getMessage()
            ]);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    private function extractServiceMappings(array $agentData): array
    {
        $mappings = [];
        
        // Analyse des Prompts nach Service-Keywords
        $prompt = $agentData['prompt'] ?? '';
        
        // Beispiel-Pattern für Service-Erkennung
        $patterns = [
            'haarschnitt' => ['haarschnitt', 'haare schneiden', 'herrenschnitt'],
            'farben' => ['färben', 'farbe', 'coloration'],
            'styling' => ['styling', 'föhnen', 'frisur']
        ];

        foreach ($patterns as $service => $keywords) {
            foreach ($keywords as $keyword) {
                if (stripos($prompt, $keyword) !== false) {
                    $mappings[] = [
                        'detected_keyword' => $keyword,
                        'suggested_service' => $service,
                        'confidence' => 0.8
                    ];
                }
            }
        }

        return $mappings;
    }

    public function testAgentConnection(string $agentId): array
    {
        try {
            $details = $this->getAgentFullDetails($agentId);
            
            return [
                'status' => !empty($details['basic_info']) ? 'success' : 'error',
                'message' => !empty($details['basic_info']) 
                    ? 'Agent erfolgreich verbunden' 
                    : 'Agent konnte nicht geladen werden',
                'details' => $details
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Verbindungsfehler: ' . $e->getMessage()
            ];
        }
    }
}
