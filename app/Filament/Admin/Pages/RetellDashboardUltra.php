<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use App\Services\RetellV2Service;
use App\Models\Company;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class RetellDashboardUltra extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-sparkles';
    protected static ?string $navigationGroup = 'System';
    protected static ?string $navigationLabel = 'Retell Ultra Dashboard';
    protected static ?int $navigationSort = 8;
    
    protected static string $view = 'filament.admin.pages.retell-dashboard-ultra';
    
    public $agents = [];
    public $groupedAgents = [];
    public $phoneNumbers = [];
    public $llmConfigs = [];
    public $error = null;
    public $loadingLLMs = false;
    
    public function mount(): void
    {
        try {
            $user = auth()->user();
            if (!$user || !$user->company_id) {
                $this->error = 'No company found for user';
                return;
            }
            
            $company = Company::find($user->company_id);
            if (!$company || !$company->retell_api_key) {
                $this->error = 'No Retell API key configured for your company';
                return;
            }
            
            $apiKey = $company->retell_api_key;
            if (strlen($apiKey) > 50) {
                $apiKey = decrypt($apiKey);
            }
            
            $service = new RetellV2Service($apiKey);
            
            // Load agents
            $agentsResult = $service->listAgents();
            $this->agents = collect($agentsResult['agents'] ?? []);
            
            // Load phone numbers  
            $phonesResult = $service->listPhoneNumbers();
            $this->phoneNumbers = $phonesResult['phone_numbers'] ?? [];
            
            // Load LLM configurations for agents
            $this->loadLLMConfigurations($service);
            
            // Group agents by base name with enhanced data
            $this->groupAgentsEnhanced();
            
        } catch (\Exception $e) {
            $this->error = 'Error: ' . $e->getMessage();
        }
    }
    
    private function loadLLMConfigurations(RetellV2Service $service): void
    {
        $this->llmConfigs = [];
        
        foreach ($this->agents as $agent) {
            // Check if agent uses retell-llm
            if (isset($agent['response_engine']['type']) && 
                $agent['response_engine']['type'] === 'retell-llm' &&
                isset($agent['response_engine']['llm_id'])) {
                
                $llmId = $agent['response_engine']['llm_id'];
                
                // Cache LLM data to avoid duplicate API calls
                $cacheKey = "retell_llm_{$llmId}";
                $llmData = Cache::remember($cacheKey, 300, function() use ($service, $llmId) {
                    return $service->getRetellLLM($llmId);
                });
                
                if ($llmData) {
                    $this->llmConfigs[$agent['agent_id']] = $llmData;
                }
            }
        }
    }
    
    private function groupAgentsEnhanced(): void
    {
        $grouped = [];
        $phoneAgentMap = [];
        
        // Build a map of which agents are assigned to which phone numbers
        foreach ($this->phoneNumbers as $phone) {
            $agentId = $phone['agent_id'] ?? ($phone['inbound_agent_id'] ?? null);
            if ($agentId) {
                if (!isset($phoneAgentMap[$agentId])) {
                    $phoneAgentMap[$agentId] = [];
                }
                $phoneAgentMap[$agentId][] = $phone;
            }
        }
        
        foreach ($this->agents as $agent) {
            $name = $agent['agent_name'];
            $agentId = $agent['agent_id'];
            
            // Extract base name and version
            $baseName = $name;
            $version = null;
            
            // Match patterns like "Name/V33" or "Name V33"
            if (preg_match('/^(.+?)[\s\/]+V(\d+)$/i', $name, $matches)) {
                $baseName = trim($matches[1]);
                $version = 'V' . $matches[2];
            }
            
            // Initialize group if not exists
            if (!isset($grouped[$baseName])) {
                $grouped[$baseName] = [
                    'base_name' => $baseName,
                    'versions' => [],
                    'active_versions' => [], // Multiple versions can be active on different numbers
                    'total_versions' => 0
                ];
            }
            
            // Get LLM data for this agent
            $llmData = $this->llmConfigs[$agentId] ?? null;
            $assignedPhones = $phoneAgentMap[$agentId] ?? [];
            
            // Build version info with complete data
            $versionInfo = [
                'version' => $version ?: 'Default',
                'agent_id' => $agentId,
                'agent_name' => $name,
                'is_active' => !empty($assignedPhones),
                'assigned_phones' => $assignedPhones,
                'last_modified' => $agent['last_modification'] ?? null,
                
                // Basic agent info
                'voice_id' => $agent['voice_id'] ?? null,
                'response_engine' => $agent['response_engine']['type'] ?? null,
                'llm_id' => $agent['response_engine']['llm_id'] ?? null,
                
                // Webhook info (from phone numbers)
                'webhook_urls' => array_unique(array_filter(array_map(function($phone) {
                    return $phone['inbound_webhook_url'] ?? null;
                }, $assignedPhones))),
                
                // LLM Configuration
                'llm_model' => $llmData['model'] ?? null,
                'prompt' => $llmData['general_prompt'] ?? null,
                'custom_functions' => $llmData['general_tools'] ?? [],
                'temperature' => $llmData['temperature'] ?? null,
                'max_tokens' => $llmData['max_tokens'] ?? null,
                
                // Settings
                'boosted_keywords' => $agent['boosted_keywords'] ?? [],
                'pronunciation_guide' => $agent['pronunciation_guide'] ?? []
            ];
            
            // Track active versions
            if ($versionInfo['is_active']) {
                $grouped[$baseName]['active_versions'][] = $version ?: 'Default';
            }
            
            $grouped[$baseName]['versions'][] = $versionInfo;
            $grouped[$baseName]['total_versions']++;
        }
        
        // Sort versions within each group
        foreach ($grouped as &$group) {
            usort($group['versions'], function($a, $b) {
                // Active versions first
                if ($a['is_active'] !== $b['is_active']) {
                    return $a['is_active'] ? -1 : 1;
                }
                // Extract version numbers and sort newest first
                preg_match('/V(\d+)$/i', $a['version'], $matchesA);
                preg_match('/V(\d+)$/i', $b['version'], $matchesB);
                
                $versionA = isset($matchesA[1]) ? intval($matchesA[1]) : 0;
                $versionB = isset($matchesB[1]) ? intval($matchesB[1]) : 0;
                
                // Sort by version number descending (newest first)
                return $versionB - $versionA;
            });
            
            // Make active versions unique
            $group['active_versions'] = array_unique($group['active_versions']);
        }
        
        $this->groupedAgents = $grouped;
    }
    
    public function refreshLLMData(): void
    {
        Cache::flush(); // Clear LLM cache
        $this->mount(); // Reload all data
    }
}