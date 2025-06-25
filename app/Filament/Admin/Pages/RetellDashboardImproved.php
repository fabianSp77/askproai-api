<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use App\Services\RetellV2Service;
use App\Models\Company;
use Illuminate\Support\Collection;

class RetellDashboardImproved extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-phone';
    protected static ?string $navigationGroup = 'System';
    protected static ?string $navigationLabel = 'Retell Configuration v2';
    protected static ?int $navigationSort = 9;
    
    protected static string $view = 'filament.admin.pages.retell-dashboard-improved';
    
    public $agents = [];
    public $groupedAgents = [];
    public $phoneNumbers = [];
    public $error = null;
    public $activeAgentIds = [];
    
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
            
            // Get active agent IDs from phone numbers
            foreach ($this->phoneNumbers as $phone) {
                $agentId = $phone['agent_id'] ?? $phone['inbound_agent_id'] ?? null;
                if ($agentId) {
                    $this->activeAgentIds[] = $agentId;
                }
            }
            
            // Group agents by base name
            $this->groupAgents();
            
        } catch (\Exception $e) {
            $this->error = 'Error: ' . $e->getMessage();
        }
    }
    
    private function groupAgents(): void
    {
        $grouped = [];
        
        foreach ($this->agents as $agent) {
            $name = $agent['agent_name'];
            $agentId = $agent['agent_id'];
            
            // Extract base name and version
            $baseName = $name;
            $version = null;
            
            // Check for version patterns like /V33, /V32, etc.
            if (preg_match('/^(.+?)\/V(\d+)$/', $name, $matches)) {
                $baseName = trim($matches[1]);
                $version = 'V' . $matches[2];
            }
            // Check for version patterns at the end without slash
            elseif (preg_match('/^(.+?)\s+V(\d+)$/', $name, $matches)) {
                $baseName = trim($matches[1]);
                $version = 'V' . $matches[2];
            }
            
            // Initialize group if not exists
            if (!isset($grouped[$baseName])) {
                $grouped[$baseName] = [
                    'base_name' => $baseName,
                    'versions' => [],
                    'active_version' => null,
                    'total_versions' => 0,
                    'has_webhook' => false,
                    'has_events' => false,
                    'has_functions' => false
                ];
            }
            
            // Add version info
            $versionInfo = [
                'version' => $version ?: 'Default',
                'agent_id' => $agentId,
                'agent_name' => $name,
                'is_active' => in_array($agentId, $this->activeAgentIds),
                'webhook_url' => $agent['webhook_url'] ?? null,
                'webhook_events' => $agent['webhook_events'] ?? [],
                'custom_functions' => $agent['custom_functions'] ?? []
            ];
            
            // Update group stats
            if ($versionInfo['is_active']) {
                $grouped[$baseName]['active_version'] = $version ?: 'Default';
                $grouped[$baseName]['has_webhook'] = !empty($versionInfo['webhook_url']);
                $grouped[$baseName]['has_events'] = !empty($versionInfo['webhook_events']);
                $grouped[$baseName]['has_functions'] = !empty($versionInfo['custom_functions']);
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
                // Then by version number (newest first)
                return strcmp($b['version'], $a['version']);
            });
        }
        
        $this->groupedAgents = $grouped;
    }
}