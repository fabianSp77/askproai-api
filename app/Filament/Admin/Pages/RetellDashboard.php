<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use App\Services\RetellV2Service;
use App\Models\Company;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\On;

class RetellDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-phone';
    protected static ?string $navigationGroup = 'System';
    protected static ?string $navigationLabel = 'Retell Configuration';
    protected static ?int $navigationSort = 10;
    
    protected static string $view = 'filament.admin.pages.retell-dashboard';
    
    // Service instance
    protected ?RetellV2Service $service = null;
    
    // Data properties
    public array $agents = [];
    public ?array $selectedAgent = null;
    public array $phoneNumbers = [];
    public ?string $error = null;
    public ?string $successMessage = null;
    
    // UI State
    public bool $loading = false;
    
    public function mount(): void
    {
        $this->initializeService();
        
        if ($this->service) {
            $this->loadAgents();
            $this->loadPhoneNumbers();
        }
    }
    
    protected function initializeService(): void
    {
        try {
            $user = auth()->user();
            if (!$user || !$user->company_id) {
                $this->error = 'No company found for user';
                return;
            }
            
            $company = Company::find($user->company_id);
            if (!$company) {
                $this->error = 'Company not found';
                return;
            }
            
            if (!$company->retell_api_key) {
                $this->error = 'No Retell API key configured. Please configure it in company settings.';
                return;
            }
            
            $apiKey = $company->retell_api_key;
            if (strlen($apiKey) > 50) {
                $apiKey = decrypt($apiKey);
            }
            
            $this->service = new RetellV2Service($apiKey);
        } catch (\Exception $e) {
            $this->error = 'Error initializing service: ' . $e->getMessage();
        }
    }
    
    public function loadAgents(): void
    {
        if (!$this->service) {
            return;
        }
        
        $this->loading = true;
        
        try {
            $result = $this->service->listAgents();
            $this->agents = $result['agents'] ?? [];
            $this->error = null;
        } catch (\Exception $e) {
            $this->error = 'Failed to load agents: ' . $e->getMessage();
            $this->agents = [];
        } finally {
            $this->loading = false;
        }
    }
    
    public function loadPhoneNumbers(): void
    {
        if (!$this->service) {
            return;
        }
        
        try {
            $result = $this->service->listPhoneNumbers();
            $this->phoneNumbers = $result['phone_numbers'] ?? [];
        } catch (\Exception $e) {
            $this->error = 'Failed to load phone numbers: ' . $e->getMessage();
            $this->phoneNumbers = [];
        }
    }
    
    public function selectAgent(string $agentId): void
    {
        $this->selectedAgent = collect($this->agents)->firstWhere('agent_id', $agentId);
        
        if (!$this->selectedAgent) {
            $this->error = 'Agent not found';
        }
    }
    
    public function refresh(): void
    {
        $this->successMessage = null;
        $this->error = null;
        $this->loadAgents();
        $this->loadPhoneNumbers();
        $this->successMessage = 'Data refreshed successfully';
    }
    
    #[On('retell-updated')]
    public function handleRetellUpdate(): void
    {
        $this->refresh();
    }
}