<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Company;
use Filament\Notifications\Notification;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;

class RetellAgentEditor extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationLabel = 'Retell Agent Editor';
    protected static ?string $title = 'Retell Agent Editor';
    protected static bool $shouldRegisterNavigation = false;
    
    #[Url(as: 'agent_id')]
    public ?string $agentId = null;
    
    #[Url(as: 'version')]
    public ?string $versionParam = null;
    
    public ?array $agent = null;
    public ?array $versions = [];
    public ?string $selectedVersion = null;
    public ?array $selectedVersionData = null;
    public ?string $publishedVersion = null;
    public bool $isActive = false;
    
    protected static string $view = 'filament.admin.pages.retell-agent-editor-enhanced';
    
    public function mount(): void
    {
        // agentId is automatically populated from URL via #[Url] attribute
        
        if (!$this->agentId) {
            Notification::make()
                ->title('No agent ID provided')
                ->danger()
                ->send();
            
            $this->redirect('/admin/retell-ultimate-control-center');
        } else {
            $this->loadAgentData();
        }
    }
    
    protected function loadAgentData(): void
    {
        try {
            $company = Company::first();
            if (!$company || !$company->retell_api_key) {
                throw new \Exception('Retell API key not configured');
            }
            
            // Get agent details
            $agentResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . $company->retell_api_key,
            ])->get("https://api.retellai.com/get-agent/{$this->agentId}");
            
            if (!$agentResponse->successful()) {
                throw new \Exception('Failed to fetch agent: ' . $agentResponse->body());
            }
            
            $this->agent = $agentResponse->json();
            // The current agent data shows the latest version, not necessarily the published one
            // We'll determine the published version from the versions list
            
            // Check if agent is active in local database
            $localAgent = \App\Models\RetellAgent::where('agent_id', $this->agentId)->first();
            $this->isActive = $localAgent ? $localAgent->is_active : false;
            
            // Get all versions
            $versionsResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . $company->retell_api_key,
            ])->get("https://api.retellai.com/get-agent-versions/{$this->agentId}");
            
            if ($versionsResponse->successful()) {
                // The API returns an array directly, not wrapped in an object
                $this->versions = $versionsResponse->json() ?? [];
                
                // Sort versions by modification time (newest first)
                usort($this->versions, function($a, $b) {
                    // last_modification_timestamp is in milliseconds
                    $timestampA = $a['last_modification_timestamp'] ?? 0;
                    $timestampB = $b['last_modification_timestamp'] ?? 0;
                    return $timestampB - $timestampA;
                });
                
                // Find the published version
                foreach ($this->versions as $v) {
                    if (isset($v['is_published']) && $v['is_published']) {
                        $this->publishedVersion = $v['version'];
                        break;
                    }
                }
                
                // Select version from URL param or first (latest) version by default
                if ($this->versionParam && is_numeric($this->versionParam)) {
                    $this->selectVersion($this->versionParam);
                } elseif (!empty($this->versions)) {
                    $this->selectVersion($this->versions[0]['version']);
                }
            }
            
        } catch (\Exception $e) {
            Log::error('Failed to load agent data: ' . $e->getMessage());
            Notification::make()
                ->title('Failed to load agent data')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    public function selectVersion(string $version): void
    {
        try {
            $this->selectedVersion = $version;
            
            // Find the version data
            foreach ($this->versions as $v) {
                if ($v['version'] === $version) {
                    $this->selectedVersionData = $v;
                    break;
                }
            }
            
            // Always load full version details since version list doesn't include all fields
            $company = Company::first();
            
            // Get the full agent data with optional version parameter
            $url = "https://api.retellai.com/get-agent/{$this->agentId}";
            if ($version !== null && $version !== '') {
                $url .= "?version={$version}";
            }
            
            $versionResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . $company->retell_api_key,
            ])->get($url);
            
            if ($versionResponse->successful()) {
                $fullData = $versionResponse->json();
                // Use the full data as the selected version data
                $this->selectedVersionData = $fullData;
                
                // If it's a retell-llm, fetch the LLM configuration
                if (isset($fullData['response_engine']['type']) && 
                    $fullData['response_engine']['type'] === 'retell-llm' &&
                    isset($fullData['response_engine']['llm_id'])) {
                    
                    $llmResponse = Http::withHeaders([
                        'Authorization' => 'Bearer ' . $company->retell_api_key,
                    ])->get("https://api.retellai.com/get-retell-llm/{$fullData['response_engine']['llm_id']}");
                    
                    if ($llmResponse->successful()) {
                        $this->selectedVersionData['llm_configuration'] = $llmResponse->json();
                    }
                }
            } else {
                Log::error('Failed to load full agent version details', [
                    'agent_id' => $this->agentId,
                    'version' => $version,
                    'status' => $versionResponse->status(),
                    'body' => substr($versionResponse->body(), 0, 500)
                ]);
            }
            
        } catch (\Exception $e) {
            Log::error('Failed to select version: ' . $e->getMessage());
            Notification::make()
                ->title('Failed to load version')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    public function publishVersion(): void
    {
        try {
            $company = Company::first();
            if (!$company || !$company->retell_api_key) {
                throw new \Exception('Retell API key not configured');
            }
            
            $version = $this->selectedVersion;
            
            // Update agent to use this version
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $company->retell_api_key,
            ])->patch("https://api.retellai.com/update-agent/{$this->agentId}", [
                'version' => $version
            ]);
            
            if (!$response->successful()) {
                throw new \Exception('Failed to publish version: ' . $response->body());
            }
            
            $this->publishedVersion = $version;
            $this->loadAgentData(); // Reload to get fresh data
            
            Notification::make()
                ->title('Version published successfully')
                ->body("Version {$version} is now the active version")
                ->success()
                ->send();
                
        } catch (\Exception $e) {
            Log::error('Failed to publish version: ' . $e->getMessage());
            Notification::make()
                ->title('Failed to publish version')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    public function activateAgent()
    {
        try {
            // Find the agent in local database
            $agent = \App\Models\RetellAgent::where('agent_id', $this->agentId)->first();
            if (!$agent) {
                // Create agent in database if it doesn't exist
                $agent = new \App\Models\RetellAgent();
                $agent->agent_id = $this->agentId;
                $agent->company_id = Company::first()->id;
                $agent->name = $this->agent['agent_name'] ?? 'Unknown Agent';
                $agent->configuration = $this->agent;
            }
            
            // Update agent status
            $agent->is_active = true;
            $agent->save();
            
            $this->isActive = true;
            
            Notification::make()
                ->title('Agent activated')
                ->body('The agent has been activated successfully')
                ->success()
                ->send();
                
        } catch (\Exception $e) {
            Log::error('Failed to activate agent: ' . $e->getMessage());
            Notification::make()
                ->title('Failed to activate agent')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    public function deactivateAgent()
    {
        try {
            // Find the agent in local database
            $agent = \App\Models\RetellAgent::where('agent_id', $this->agentId)->first();
            if ($agent) {
                $agent->is_active = false;
                $agent->save();
            }
            
            $this->isActive = false;
            
            Notification::make()
                ->title('Agent deactivated')
                ->body('The agent has been deactivated successfully')
                ->warning()
                ->send();
                
        } catch (\Exception $e) {
            Log::error('Failed to deactivate agent: ' . $e->getMessage());
            Notification::make()
                ->title('Failed to deactivate agent')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    public function exportConfiguration()
    {
        return $this->exportAgent();
    }
    
    public function exportAgent()
    {
        if (!$this->selectedVersionData) {
            Notification::make()
                ->title('No version selected')
                ->danger()
                ->send();
            return;
        }
        
        $filename = "retell-agent-{$this->agentId}-v{$this->selectedVersion}-" . date('Y-m-d-His') . ".json";
        $content = json_encode($this->selectedVersionData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
        return response()->streamDownload(function () use ($content) {
            echo $content;
        }, $filename, [
            'Content-Type' => 'application/json',
        ]);
    }
    
    protected function getViewData(): array
    {
        return [
            'agentId' => $this->agentId,
            'agent' => $this->agent,
            'versions' => $this->versions,
            'selectedVersion' => $this->selectedVersion,
            'selectedVersionData' => $this->selectedVersionData,
            'isActive' => $this->isActive,
            'publishedVersion' => $this->publishedVersion,
        ];
    }
    
    /**
     * Get version data for comparison
     */
    public function getVersionData(string $version): ?array
    {
        try {
            $company = Company::first();
            if (!$company || !$company->retell_api_key) {
                return null;
            }
            
            $url = "https://api.retellai.com/get-agent/{$this->agentId}?version={$version}";
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $company->retell_api_key,
            ])->get($url);
            
            if ($response->successful()) {
                return $response->json();
            }
            
        } catch (\Exception $e) {
            Log::error('Failed to get version data for comparison: ' . $e->getMessage());
        }
        
        return null;
    }
}