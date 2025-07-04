<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RetellAgentVersionController extends Controller
{
    /**
     * Get specific version data for an agent
     */
    public function getVersion(Request $request, string $agentId, string $version)
    {
        try {
            $company = Company::first();
            if (!$company || !$company->retell_api_key) {
                return response()->json(['error' => 'API key not configured'], 500);
            }
            
            $apiKey = $company->retell_api_key;
            // Decrypt if needed
            if (strlen($apiKey) > 50) {
                try {
                    $apiKey = decrypt($apiKey);
                } catch (\Exception $e) {}
            }
            
            $url = "https://api.retellai.com/get-agent/{$agentId}?version={$version}";
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
            ])->get($url);
            
            if ($response->successful()) {
                $data = $response->json();
                
                // If it's a retell-llm, fetch the LLM configuration
                if (isset($data['response_engine']['type']) && 
                    $data['response_engine']['type'] === 'retell-llm' &&
                    isset($data['response_engine']['llm_id'])) {
                    
                    $llmResponse = Http::withHeaders([
                        'Authorization' => 'Bearer ' . $apiKey,
                    ])->get("https://api.retellai.com/get-retell-llm/{$data['response_engine']['llm_id']}");
                    
                    if ($llmResponse->successful()) {
                        $data['llm_configuration'] = $llmResponse->json();
                    }
                }
                
                return response()->json($data);
            }
            
            return response()->json(['error' => 'Failed to fetch version'], 404);
            
        } catch (\Exception $e) {
            Log::error('Failed to get agent version: ' . $e->getMessage());
            return response()->json(['error' => 'Internal server error'], 500);
        }
    }
    
    /**
     * Compare two versions of an agent
     */
    public function compareVersions(Request $request, string $agentId)
    {
        $request->validate([
            'version1' => 'required|numeric',
            'version2' => 'required|numeric',
        ]);
        
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
            
            // Fetch both versions
            $version1Data = $this->fetchVersionData($agentId, $request->version1, $apiKey);
            $version2Data = $this->fetchVersionData($agentId, $request->version2, $apiKey);
            
            if (!$version1Data || !$version2Data) {
                return response()->json(['error' => 'Failed to fetch one or both versions'], 404);
            }
            
            // Generate diff
            $diff = $this->generateDiff($version1Data, $version2Data);
            
            return response()->json([
                'version1' => $request->version1,
                'version2' => $request->version2,
                'diff' => $diff
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to compare versions: ' . $e->getMessage());
            return response()->json(['error' => 'Internal server error'], 500);
        }
    }
    
    /**
     * Fetch version data
     */
    private function fetchVersionData(string $agentId, string $version, string $apiKey): ?array
    {
        $url = "https://api.retellai.com/get-agent/{$agentId}?version={$version}";
        
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
        ])->get($url);
        
        if ($response->successful()) {
            return $response->json();
        }
        
        return null;
    }
    
    /**
     * Generate diff between two versions
     */
    private function generateDiff(array $version1, array $version2): array
    {
        $diff = [];
        
        // Compare all fields
        $allKeys = array_unique(array_merge(array_keys($version1), array_keys($version2)));
        
        foreach ($allKeys as $key) {
            $value1 = $version1[$key] ?? null;
            $value2 = $version2[$key] ?? null;
            
            if ($value1 === $value2) {
                continue; // No change
            }
            
            if ($value1 === null) {
                // Added in version 2
                $diff[] = [
                    'field' => $key,
                    'type' => 'added',
                    'old' => null,
                    'new' => $value2
                ];
            } elseif ($value2 === null) {
                // Removed in version 2
                $diff[] = [
                    'field' => $key,
                    'type' => 'removed',
                    'old' => $value1,
                    'new' => null
                ];
            } else {
                // Changed
                $diff[] = [
                    'field' => $key,
                    'type' => 'changed',
                    'old' => $value1,
                    'new' => $value2
                ];
            }
        }
        
        return $diff;
    }
}