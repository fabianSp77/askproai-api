<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Company;
use Filament\Notifications\Notification;
use Livewire\Attributes\Url;

class RetellAgentEditorUnified extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-pencil-square';
    protected static ?string $navigationLabel = 'Retell Agent Editor Unified';
    protected static ?string $title = 'Retell Agent Editor - Live Edit';
    protected static bool $shouldRegisterNavigation = false;
    
    #[Url(as: 'agent_id')]
    public ?string $agentId = null;
    
    #[Url(as: 'version')]
    public ?string $versionParam = null;
    
    public ?array $agent = null;
    public ?array $versions = [];
    public ?string $selectedVersion = null;
    public ?array $currentVersion = null;
    public ?string $publishedVersion = null;
    
    protected static string $view = 'filament.admin.pages.retell-agent-editor-unified';
    
    public function getTitle(): string 
    {
        return 'Agent Editor - ' . ($this->agent['agent_name'] ?? 'Unified');
    }
    
    public function mount(): void
    {
        if (!$this->agentId) {
            Notification::make()
                ->title('No agent ID provided')
                ->danger()
                ->send();
            
            $this->redirect('/admin/retell-ultimate-control-center');
            return;
        }
        
        $this->loadAgentData();
    }
    
    protected function loadAgentData(): void
    {
        try {
            $company = Company::first();
            if (!$company || !$company->retell_api_key) {
                throw new \Exception('Retell API key not configured');
            }
            
            $apiKey = $company->retell_api_key;
            if (strlen($apiKey) > 50) {
                try {
                    $apiKey = decrypt($apiKey);
                } catch (\Exception $e) {}
            }
            
            // Ensure agent ID has proper format
            $agentId = $this->agentId;
            if (!str_starts_with($agentId, 'agent_')) {
                $agentId = 'agent_' . $agentId;
            }
            
            // Get agent details
            $agentResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
            ])->get("https://api.retellai.com/get-agent/{$agentId}");
            
            if (!$agentResponse->successful()) {
                throw new \Exception('Failed to fetch agent: ' . $agentResponse->body());
            }
            
            $this->agent = $agentResponse->json();
            $this->currentVersion = $this->agent;
            
            // Get all versions
            $versionsResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
            ])->get("https://api.retellai.com/get-agent-versions/{$agentId}");
            
            if ($versionsResponse->successful()) {
                $this->versions = $versionsResponse->json() ?? [];
                
                // Sort versions by modification time (newest first)
                usort($this->versions, function($a, $b) {
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
            
            // If it's a retell-llm, fetch the LLM configuration
            if (isset($this->currentVersion['response_engine']['type']) && 
                $this->currentVersion['response_engine']['type'] === 'retell-llm' &&
                isset($this->currentVersion['response_engine']['llm_id'])) {
                
                $llmResponse = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                ])->get("https://api.retellai.com/get-retell-llm/{$this->currentVersion['response_engine']['llm_id']}");
                
                if ($llmResponse->successful()) {
                    $llmData = $llmResponse->json();
                    // Merge LLM data into current version for easier access
                    $this->currentVersion = array_merge($this->currentVersion, [
                        'general_prompt' => $llmData['general_prompt'] ?? '',
                        'custom_functions' => $llmData['custom_functions'] ?? [],
                        'states' => $llmData['states'] ?? [],
                    ]);
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
            
            $company = Company::first();
            if (!$company || !$company->retell_api_key) {
                return;
            }
            
            $apiKey = $company->retell_api_key;
            if (strlen($apiKey) > 50) {
                try {
                    $apiKey = decrypt($apiKey);
                } catch (\Exception $e) {}
            }
            
            $agentId = $this->agentId;
            if (!str_starts_with($agentId, 'agent_')) {
                $agentId = 'agent_' . $agentId;
            }
            
            // Get the full agent data with optional version parameter
            $url = "https://api.retellai.com/get-agent/{$agentId}";
            if ($version !== null && $version !== '') {
                $url .= "?version={$version}";
            }
            
            $versionResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
            ])->get($url);
            
            if ($versionResponse->successful()) {
                $this->currentVersion = $versionResponse->json();
                
                // If it's a retell-llm, fetch the LLM configuration
                if (isset($this->currentVersion['response_engine']['type']) && 
                    $this->currentVersion['response_engine']['type'] === 'retell-llm' &&
                    isset($this->currentVersion['response_engine']['llm_id'])) {
                    
                    $llmResponse = Http::withHeaders([
                        'Authorization' => 'Bearer ' . $apiKey,
                    ])->get("https://api.retellai.com/get-retell-llm/{$this->currentVersion['response_engine']['llm_id']}");
                    
                    if ($llmResponse->successful()) {
                        $llmData = $llmResponse->json();
                        $this->currentVersion = array_merge($this->currentVersion, [
                            'general_prompt' => $llmData['general_prompt'] ?? '',
                            'custom_functions' => $llmData['custom_functions'] ?? [],
                            'states' => $llmData['states'] ?? [],
                        ]);
                    }
                }
            }
            
        } catch (\Exception $e) {
            Log::error('Failed to select version: ' . $e->getMessage());
        }
    }
    
    protected function getViewData(): array
    {
        return [
            'agentId' => $this->agentId,
            'agent' => $this->agent,
            'versions' => $this->versions,
            'selectedVersion' => $this->selectedVersion,
            'currentVersion' => $this->currentVersion,
            'publishedVersion' => $this->publishedVersion,
        ];
    }
}