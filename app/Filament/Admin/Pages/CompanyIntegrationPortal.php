<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use App\Models\Company;
use App\Models\Branch;
use App\Models\PhoneNumber;
use App\Services\MCP\CalcomMCPServer;
use App\Services\MCP\RetellMCPServer;
use App\Services\MCP\KnowledgeMCPServer;
use App\Services\MCP\StripeMCPServer;
use App\Services\MCP\WebhookMCPServer;
use Illuminate\Support\Facades\Cache;
use Filament\Notifications\Notification;
use Livewire\Attributes\On;

class CompanyIntegrationPortal extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-building-office';
    protected static ?string $navigationLabel = 'Company Integration Portal';
    protected static ?string $title = 'Company Integration Portal';
    protected static ?string $navigationGroup = 'Einrichtung & Konfiguration';
    protected static ?int $navigationSort = 1;
    
    protected static string $view = 'filament.admin.pages.company-integration-portal';
    
    public static function canAccess(): bool
    {
        // Allow all authenticated users to access this page
        return auth()->check();
    }
    
    public array $companies = [];
    public ?int $selectedCompanyId = null;
    public ?Company $selectedCompany = null;
    public array $integrationStatus = [];
    public array $testResults = [];
    public bool $isTestingAll = false;
    public array $phoneNumbers = [];
    public array $branches = [];
    
    // Inline editing fields - Company level
    public ?string $calcomApiKey = null;
    public ?string $calcomTeamSlug = null;
    public ?string $retellApiKey = null;
    public ?string $retellAgentId = null;
    public bool $showCalcomApiKeyInput = false;
    public bool $showCalcomTeamSlugInput = false;
    public bool $showRetellApiKeyInput = false;
    public bool $showRetellAgentIdInput = false;
    
    // Inline editing fields - Branch level
    public array $branchEditStates = [];
    public array $branchPhoneNumbers = [];
    public array $branchCalcomEventTypes = [];
    public array $branchRetellAgentIds = [];
    public array $branchNames = [];
    public array $branchAddresses = [];
    public array $branchEmails = [];
    public array $branchActiveStates = [];
    
    // MCP Services - nullable to work with Livewire
    protected ?CalcomMCPServer $calcomService = null;
    protected ?RetellMCPServer $retellService = null;
    protected ?KnowledgeMCPServer $knowledgeService = null;
    protected ?StripeMCPServer $stripeService = null;
    protected ?WebhookMCPServer $webhookService = null;
    
    public function mount(): void
    {
        $this->initializeServices();
        $this->loadCompanies();
        
        // Auto-select first company if only one exists
        if (count($this->companies) === 1) {
            $this->selectCompany(array_key_first($this->companies));
        }
    }
    
    protected function initializeServices(): void
    {
        // Use Laravel's service container to properly inject dependencies
        $this->calcomService = app(CalcomMCPServer::class);
        $this->retellService = app(RetellMCPServer::class);
        $this->knowledgeService = app(KnowledgeMCPServer::class);
        $this->stripeService = app(StripeMCPServer::class);
        $this->webhookService = app(WebhookMCPServer::class);
    }
    
    public function loadCompanies(): void
    {
        $user = auth()->user();
        
        // If user is super admin, show all companies
        if ($user->hasRole('super_admin') || $user->can('view_any_company')) {
            $query = Company::with(['branches', 'phoneNumbers']);
        } else {
            // Otherwise, show only the user's company
            $query = Company::with(['branches', 'phoneNumbers'])
                ->where('id', $user->company_id);
        }
        
        $this->companies = $query->get()
            ->mapWithKeys(function ($company) {
                return [$company->id => [
                    'id' => $company->id,
                    'name' => $company->name,
                    'slug' => $company->slug,
                    'is_active' => $company->is_active,
                    'branch_count' => $company->branches->count(),
                    'phone_count' => $company->phoneNumbers->count(),
                    'created_at' => $company->created_at->format('Y-m-d'),
                ]];
            })
            ->toArray();
    }
    
    public function selectCompany(int $companyId): void
    {
        $user = auth()->user();
        
        // Security check: ensure user can access this company
        if (!$user->hasRole('super_admin') && !$user->can('view_any_company')) {
            if ($user->company_id !== $companyId) {
                Notification::make()
                    ->title('Zugriff verweigert')
                    ->body('Sie haben keinen Zugriff auf dieses Unternehmen.')
                    ->danger()
                    ->send();
                return;
            }
        }
        
        $this->selectedCompanyId = $companyId;
        $this->selectedCompany = Company::with(['branches.phoneNumbers', 'phoneNumbers'])->find($companyId);
        
        if ($this->selectedCompany) {
            $this->loadIntegrationStatus();
            $this->loadPhoneNumbers();
            $this->loadBranches();
        }
    }
    
    protected function loadIntegrationStatus(): void
    {
        $this->integrationStatus = [
            'calcom' => $this->checkCalcomIntegration(),
            'retell' => $this->checkRetellIntegration(),
            'stripe' => $this->checkStripeIntegration(),
            'knowledge' => $this->checkKnowledgeIntegration(),
            'webhooks' => $this->checkWebhookIntegration(),
        ];
    }
    
    /**
     * Get webhook service instance
     */
    protected function getWebhookService(): WebhookMCPServer
    {
        if (!$this->webhookService) {
            $this->webhookService = app(WebhookMCPServer::class);
        }
        return $this->webhookService;
    }
    
    /**
     * Get knowledge service instance
     */
    protected function getKnowledgeService(): KnowledgeMCPServer
    {
        if (!$this->knowledgeService) {
            $this->knowledgeService = app(KnowledgeMCPServer::class);
        }
        return $this->knowledgeService;
    }
    
    /**
     * Get calcom service instance
     */
    protected function getCalcomService(): CalcomMCPServer
    {
        if (!$this->calcomService) {
            $this->calcomService = app(CalcomMCPServer::class);
        }
        return $this->calcomService;
    }
    
    /**
     * Get retell service instance
     */
    protected function getRetellService(): RetellMCPServer
    {
        if (!$this->retellService) {
            $this->retellService = app(RetellMCPServer::class);
        }
        return $this->retellService;
    }
    
    /**
     * Get stripe service instance
     */
    protected function getStripeService(): StripeMCPServer
    {
        if (!$this->stripeService) {
            $this->stripeService = app(StripeMCPServer::class);
        }
        return $this->stripeService;
    }
    
    protected function checkCalcomIntegration(): array
    {
        $hasApiKey = !empty($this->selectedCompany->calcom_api_key);
        $hasTeamSlug = !empty($this->selectedCompany->calcom_team_slug);
        $eventTypeCount = $this->selectedCompany->eventTypes()->count();
        
        return [
            'configured' => $hasApiKey && $hasTeamSlug,
            'api_key' => $hasApiKey,
            'team_slug' => $hasTeamSlug,
            'event_types' => $eventTypeCount,
            'status' => $hasApiKey && $hasTeamSlug ? 'success' : 'warning',
            'message' => $hasApiKey && $hasTeamSlug 
                ? "Configured with {$eventTypeCount} event types" 
                : 'Not configured',
        ];
    }
    
    protected function checkRetellIntegration(): array
    {
        $hasApiKey = !empty($this->selectedCompany->retell_api_key);
        $hasAgentId = !empty($this->selectedCompany->retell_agent_id);
        $phoneCount = $this->selectedCompany->phoneNumbers()->count();
        
        return [
            'configured' => $hasApiKey && $hasAgentId,
            'api_key' => $hasApiKey,
            'agent_id' => $hasAgentId,
            'phone_numbers' => $phoneCount,
            'status' => $hasApiKey && $hasAgentId ? 'success' : 'warning',
            'message' => $hasApiKey && $hasAgentId 
                ? "Configured with {$phoneCount} phone numbers" 
                : 'Not configured',
        ];
    }
    
    protected function checkStripeIntegration(): array
    {
        $hasCustomerId = !empty($this->selectedCompany->stripe_customer_id);
        
        return [
            'configured' => $hasCustomerId,
            'customer_id' => $hasCustomerId,
            'status' => $hasCustomerId ? 'success' : 'info',
            'message' => $hasCustomerId ? 'Connected to Stripe' : 'Optional - Not configured',
        ];
    }
    
    protected function checkKnowledgeIntegration(): array
    {
        $documentCount = Cache::remember(
            "company.{$this->selectedCompanyId}.knowledge_count",
            300,
            fn() => $this->getKnowledgeService()->getDocumentCount(['company_id' => $this->selectedCompanyId])['count'] ?? 0
        );
        
        return [
            'configured' => $documentCount > 0,
            'document_count' => $documentCount,
            'status' => $documentCount > 0 ? 'success' : 'info',
            'message' => $documentCount > 0 
                ? "{$documentCount} knowledge documents" 
                : 'No knowledge base configured',
        ];
    }
    
    protected function checkWebhookIntegration(): array
    {
        $webhookStats = $this->getWebhookService()->getWebhookStats(['company_id' => $this->selectedCompanyId]);
        $recentWebhooks = $webhookStats['recent_count'] ?? 0;
        
        return [
            'configured' => $recentWebhooks > 0,
            'recent_webhooks' => $recentWebhooks,
            'status' => $recentWebhooks > 0 ? 'success' : 'warning',
            'message' => $recentWebhooks > 0 
                ? "{$recentWebhooks} webhooks in last 24h" 
                : 'No recent webhook activity',
        ];
    }
    
    protected function loadPhoneNumbers(): void
    {
        $this->phoneNumbers = PhoneNumber::where('company_id', $this->selectedCompanyId)
            ->with('branch')
            ->get()
            ->map(function ($phone) {
                return [
                    'id' => $phone->id,
                    'number' => $phone->number,
                    'formatted' => $phone->formatted_number,
                    'branch' => $phone->branch ? $phone->branch->name : 'Unassigned',
                    'is_primary' => $phone->is_primary,
                    'is_active' => $phone->is_active,
                ];
            })
            ->toArray();
    }
    
    protected function loadBranches(): void
    {
        $this->branches = Branch::where('company_id', $this->selectedCompanyId)
            ->with(['phoneNumbers', 'staff'])
            ->get()
            ->map(function ($branch) {
                $phoneNumber = $branch->phoneNumbers->first();
                return [
                    'id' => $branch->id,
                    'name' => $branch->name,
                    'address' => $branch->address,
                    'email' => $branch->email,
                    'is_active' => $branch->active,
                    'is_main' => $branch->is_main,
                    'phone_number' => $phoneNumber ? $phoneNumber->number : null,
                    'phone_count' => $branch->phoneNumbers->count(),
                    'staff_count' => $branch->staff->count(),
                    'calcom_event_type_id' => $branch->calcom_event_type_id,
                    'retell_agent_id' => $branch->retell_agent_id,
                    'has_phone' => $phoneNumber !== null,
                    'has_calcom' => !empty($branch->calcom_event_type_id),
                    'has_retell' => !empty($branch->retell_agent_id),
                    'has_calendar' => !empty($branch->calcom_event_type_id),
                    'is_configured' => $phoneNumber !== null && !empty($branch->calcom_event_type_id),
                    'uses_master_retell' => empty($branch->retell_agent_id) && !empty($this->selectedCompany->retell_agent_id),
                ];
            })
            ->toArray();
    }
    
    public function testCalcomIntegration(): void
    {
        $this->testResults['calcom'] = ['testing' => true];
        
        try {
            $result = $this->getCalcomService()->testConnection(['company_id' => $this->selectedCompanyId]);
            
            $this->testResults['calcom'] = [
                'success' => $result['connected'] ?? false,
                'message' => $result['message'] ?? 'Connection test completed',
                'details' => $result,
                'tested_at' => now()->format('H:i:s'),
            ];
            
            if ($result['connected'] ?? false) {
                Notification::make()
                    ->title('Cal.com Connection Successful')
                    ->body($result['message'] ?? 'API connection verified')
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title('Cal.com Connection Failed')
                    ->body($result['error'] ?? 'Unable to connect to Cal.com')
                    ->danger()
                    ->send();
            }
        } catch (\Exception $e) {
            $this->testResults['calcom'] = [
                'success' => false,
                'message' => 'Exception: ' . $e->getMessage(),
                'tested_at' => now()->format('H:i:s'),
            ];
            
            Notification::make()
                ->title('Cal.com Test Failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    public function testRetellIntegration(): void
    {
        $this->testResults['retell'] = ['testing' => true];
        
        try {
            $result = $this->getRetellService()->testConnection(['company_id' => $this->selectedCompanyId]);
            
            $this->testResults['retell'] = [
                'success' => $result['connected'] ?? false,
                'message' => $result['message'] ?? 'Connection test completed',
                'details' => $result,
                'tested_at' => now()->format('H:i:s'),
            ];
            
            if ($result['connected'] ?? false) {
                Notification::make()
                    ->title('Retell.ai Connection Successful')
                    ->body($result['message'] ?? 'API connection verified')
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title('Retell.ai Connection Failed')
                    ->body($result['error'] ?? 'Unable to connect to Retell.ai')
                    ->danger()
                    ->send();
            }
        } catch (\Exception $e) {
            $this->testResults['retell'] = [
                'success' => false,
                'message' => 'Exception: ' . $e->getMessage(),
                'tested_at' => now()->format('H:i:s'),
            ];
            
            Notification::make()
                ->title('Retell.ai Test Failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    public function testAllIntegrations(): void
    {
        $this->isTestingAll = true;
        $this->testResults = [];
        
        // Test each integration
        $this->testCalcomIntegration();
        $this->testRetellIntegration();
        
        // Test Stripe if configured
        if ($this->integrationStatus['stripe']['configured']) {
            $this->testStripeIntegration();
        }
        
        $this->isTestingAll = false;
        
        Notification::make()
            ->title('All Integration Tests Complete')
            ->body('Check the results for each service')
            ->success()
            ->send();
    }
    
    public function testStripeIntegration(): void
    {
        $this->testResults['stripe'] = ['testing' => true];
        
        try {
            $result = $this->getStripeService()->testConnection(['company_id' => $this->selectedCompanyId]);
            
            $this->testResults['stripe'] = [
                'success' => $result['connected'] ?? false,
                'message' => $result['message'] ?? 'Connection test completed',
                'details' => $result,
                'tested_at' => now()->format('H:i:s'),
            ];
            
            Notification::make()
                ->title($result['connected'] ? 'Stripe Connected' : 'Stripe Connection Failed')
                ->body($result['message'] ?? 'Test completed')
                ->send();
        } catch (\Exception $e) {
            $this->testResults['stripe'] = [
                'success' => false,
                'message' => 'Exception: ' . $e->getMessage(),
                'tested_at' => now()->format('H:i:s'),
            ];
        }
    }
    
    public function syncCalcomEventTypes(): void
    {
        try {
            $result = $this->getCalcomService()->syncEventTypes(['company_id' => $this->selectedCompanyId]);
            
            if ($result['success'] ?? false) {
                Notification::make()
                    ->title('Event Types Synced')
                    ->body("Synced {$result['synced_count']} event types")
                    ->success()
                    ->send();
                    
                // Reload integration status
                $this->loadIntegrationStatus();
            } else {
                Notification::make()
                    ->title('Sync Failed')
                    ->body($result['error'] ?? 'Unable to sync event types')
                    ->danger()
                    ->send();
            }
        } catch (\Exception $e) {
            Notification::make()
                ->title('Sync Error')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    public function importRetellCalls(): void
    {
        try {
            $result = $this->getRetellService()->importRecentCalls(['company_id' => $this->selectedCompanyId]);
            
            if ($result['success'] ?? false) {
                Notification::make()
                    ->title('Calls Imported')
                    ->body("Imported {$result['imported']} calls")
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title('Import Failed')
                    ->body($result['error'] ?? 'Unable to import calls')
                    ->danger()
                    ->send();
            }
        } catch (\Exception $e) {
            Notification::make()
                ->title('Import Error')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    public function openSetupWizard(): void
    {
        // Store the selected company ID in session for the wizard
        session(['setup_wizard_company_id' => $this->selectedCompanyId]);
        
        // Redirect to the company edit page instead, which has proper permissions
        $this->redirect(route('filament.admin.resources.companies.edit', $this->selectedCompanyId));
    }
    
    public function refreshData(): void
    {
        if ($this->selectedCompanyId) {
            $this->selectCompany($this->selectedCompanyId);
        }
        
        Notification::make()
            ->title('Data Refreshed')
            ->body('Integration status updated')
            ->success()
            ->send();
    }
    
    #[On('company-updated')]
    public function handleCompanyUpdated(): void
    {
        $this->refreshData();
    }
    
    // Inline editing methods
    public function toggleCalcomApiKeyInput(): void
    {
        $this->showCalcomApiKeyInput = !$this->showCalcomApiKeyInput;
        if ($this->showCalcomApiKeyInput && $this->selectedCompany) {
            $this->calcomApiKey = $this->selectedCompany->calcom_api_key;
        }
    }
    
    public function saveCalcomApiKey(): void
    {
        if (!$this->selectedCompany) return;
        
        try {
            // Validate API key with Cal.com
            $isValid = $this->getCalcomService()->validateApiKey(['api_key' => $this->calcomApiKey]);
            
            if ($isValid['valid'] ?? false) {
                $this->selectedCompany->calcom_api_key = $this->calcomApiKey;
                $this->selectedCompany->save();
                
                Notification::make()
                    ->title('API Key gespeichert')
                    ->body('Der Cal.com API Key wurde erfolgreich gespeichert und validiert.')
                    ->success()
                    ->send();
                    
                $this->showCalcomApiKeyInput = false;
                $this->loadIntegrationStatus();
            } else {
                Notification::make()
                    ->title('Ungültiger API Key')
                    ->body($isValid['error'] ?? 'Der API Key konnte nicht validiert werden.')
                    ->danger()
                    ->send();
            }
        } catch (\Exception $e) {
            Notification::make()
                ->title('Fehler beim Speichern')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    public function toggleCalcomTeamSlugInput(): void
    {
        $this->showCalcomTeamSlugInput = !$this->showCalcomTeamSlugInput;
        if ($this->showCalcomTeamSlugInput && $this->selectedCompany) {
            $this->calcomTeamSlug = $this->selectedCompany->calcom_team_slug;
        }
    }
    
    public function saveCalcomTeamSlug(): void
    {
        if (!$this->selectedCompany) return;
        
        try {
            $this->selectedCompany->calcom_team_slug = $this->calcomTeamSlug;
            $this->selectedCompany->save();
            
            Notification::make()
                ->title('Team Slug gespeichert')
                ->body('Der Cal.com Team Slug wurde erfolgreich gespeichert.')
                ->success()
                ->send();
                
            $this->showCalcomTeamSlugInput = false;
            $this->loadIntegrationStatus();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Fehler beim Speichern')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    public function toggleRetellApiKeyInput(): void
    {
        $this->showRetellApiKeyInput = !$this->showRetellApiKeyInput;
        if ($this->showRetellApiKeyInput && $this->selectedCompany) {
            $this->retellApiKey = $this->selectedCompany->retell_api_key;
        }
    }
    
    public function saveRetellApiKey(): void
    {
        if (!$this->selectedCompany) return;
        
        try {
            $this->selectedCompany->retell_api_key = $this->retellApiKey;
            $this->selectedCompany->save();
            
            Notification::make()
                ->title('Retell API Key gespeichert')
                ->body('Der Retell.ai API Key wurde erfolgreich gespeichert.')
                ->success()
                ->send();
                
            $this->showRetellApiKeyInput = false;
            $this->loadIntegrationStatus();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Fehler beim Speichern')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    public function toggleRetellAgentIdInput(): void
    {
        $this->showRetellAgentIdInput = !$this->showRetellAgentIdInput;
        if ($this->showRetellAgentIdInput && $this->selectedCompany) {
            $this->retellAgentId = $this->selectedCompany->retell_agent_id;
        }
    }
    
    public function saveRetellAgentId(): void
    {
        if (!$this->selectedCompany) return;
        
        try {
            $this->selectedCompany->retell_agent_id = $this->retellAgentId;
            $this->selectedCompany->save();
            
            Notification::make()
                ->title('Agent ID gespeichert')
                ->body('Die Retell.ai Agent ID wurde erfolgreich gespeichert.')
                ->success()
                ->send();
                
            $this->showRetellAgentIdInput = false;
            $this->loadIntegrationStatus();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Fehler beim Speichern')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    // Branch-level editing methods
    public function toggleBranchPhoneNumberInput(int $branchId): void
    {
        $key = "phone_{$branchId}";
        $this->branchEditStates[$key] = !($this->branchEditStates[$key] ?? false);
        
        if ($this->branchEditStates[$key]) {
            $branch = Branch::find($branchId);
            $this->branchPhoneNumbers[$branchId] = $branch->phoneNumbers->first()->number ?? '';
        }
    }
    
    public function saveBranchPhoneNumber(int $branchId): void
    {
        $branch = Branch::find($branchId);
        if (!$branch) return;
        
        try {
            $phoneNumber = $this->branchPhoneNumbers[$branchId] ?? '';
            
            // Check if phone number exists
            $existingPhone = PhoneNumber::where('number', $phoneNumber)
                ->where('branch_id', '!=', $branchId)
                ->first();
                
            if ($existingPhone) {
                Notification::make()
                    ->title('Telefonnummer bereits vergeben')
                    ->body("Diese Nummer ist bereits einer anderen Filiale zugeordnet.")
                    ->danger()
                    ->send();
                return;
            }
            
            // Update or create phone number
            $phone = $branch->phoneNumbers()->first();
            if ($phone) {
                $phone->update(['number' => $phoneNumber]);
            } else {
                $branch->phoneNumbers()->create([
                    'number' => $phoneNumber,
                    'company_id' => $branch->company_id,
                    'type' => 'main',
                    'is_active' => true,
                ]);
            }
            
            Notification::make()
                ->title('Telefonnummer gespeichert')
                ->body("Die Telefonnummer wurde erfolgreich aktualisiert.")
                ->success()
                ->send();
                
            $this->branchEditStates["phone_{$branchId}"] = false;
            $this->loadBranches();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Fehler beim Speichern')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    public function toggleBranchCalcomEventTypeInput(int $branchId): void
    {
        $key = "calcom_{$branchId}";
        $this->branchEditStates[$key] = !($this->branchEditStates[$key] ?? false);
        
        if ($this->branchEditStates[$key]) {
            $branch = Branch::find($branchId);
            $this->branchCalcomEventTypes[$branchId] = $branch->calcom_event_type_id ?? '';
        }
    }
    
    public function saveBranchCalcomEventType(int $branchId): void
    {
        $branch = Branch::find($branchId);
        if (!$branch) return;
        
        try {
            $branch->calcom_event_type_id = $this->branchCalcomEventTypes[$branchId] ?? null;
            $branch->save();
            
            Notification::make()
                ->title('Cal.com Event Type gespeichert')
                ->body("Der Event Type wurde erfolgreich aktualisiert.")
                ->success()
                ->send();
                
            $this->branchEditStates["calcom_{$branchId}"] = false;
            $this->loadBranches();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Fehler beim Speichern')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    public function toggleBranchRetellAgentInput(int $branchId): void
    {
        $key = "retell_{$branchId}";
        $this->branchEditStates[$key] = !($this->branchEditStates[$key] ?? false);
        
        if ($this->branchEditStates[$key]) {
            $branch = Branch::find($branchId);
            $this->branchRetellAgentIds[$branchId] = $branch->retell_agent_id ?? '';
        }
    }
    
    public function saveBranchRetellAgent(int $branchId): void
    {
        $branch = Branch::find($branchId);
        if (!$branch) return;
        
        try {
            $branch->retell_agent_id = $this->branchRetellAgentIds[$branchId] ?? null;
            $branch->save();
            
            Notification::make()
                ->title('Retell Agent ID gespeichert')
                ->body("Die Agent ID wurde erfolgreich aktualisiert.")
                ->success()
                ->send();
                
            $this->branchEditStates["retell_{$branchId}"] = false;
            $this->loadBranches();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Fehler beim Speichern')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    // Additional branch editing methods
    public function toggleBranchNameInput(int $branchId): void
    {
        $key = "name_{$branchId}";
        $this->branchEditStates[$key] = !($this->branchEditStates[$key] ?? false);
        
        if ($this->branchEditStates[$key]) {
            $branch = Branch::find($branchId);
            $this->branchNames[$branchId] = $branch->name ?? '';
        }
    }
    
    public function saveBranchName(int $branchId): void
    {
        $branch = Branch::find($branchId);
        if (!$branch) return;
        
        try {
            $branch->name = $this->branchNames[$branchId] ?? '';
            $branch->save();
            
            Notification::make()
                ->title('Name gespeichert')
                ->body("Der Filialname wurde erfolgreich aktualisiert.")
                ->success()
                ->send();
                
            $this->branchEditStates["name_{$branchId}"] = false;
            $this->loadBranches();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Fehler beim Speichern')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    public function toggleBranchAddressInput(int $branchId): void
    {
        $key = "address_{$branchId}";
        $this->branchEditStates[$key] = !($this->branchEditStates[$key] ?? false);
        
        if ($this->branchEditStates[$key]) {
            $branch = Branch::find($branchId);
            $this->branchAddresses[$branchId] = $branch->address ?? '';
        }
    }
    
    public function saveBranchAddress(int $branchId): void
    {
        $branch = Branch::find($branchId);
        if (!$branch) return;
        
        try {
            $branch->address = $this->branchAddresses[$branchId] ?? '';
            $branch->save();
            
            Notification::make()
                ->title('Adresse gespeichert')
                ->body("Die Adresse wurde erfolgreich aktualisiert.")
                ->success()
                ->send();
                
            $this->branchEditStates["address_{$branchId}"] = false;
            $this->loadBranches();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Fehler beim Speichern')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    public function toggleBranchEmailInput(int $branchId): void
    {
        $key = "email_{$branchId}";
        $this->branchEditStates[$key] = !($this->branchEditStates[$key] ?? false);
        
        if ($this->branchEditStates[$key]) {
            $branch = Branch::find($branchId);
            $this->branchEmails[$branchId] = $branch->email ?? '';
        }
    }
    
    public function saveBranchEmail(int $branchId): void
    {
        $branch = Branch::find($branchId);
        if (!$branch) return;
        
        try {
            $branch->email = $this->branchEmails[$branchId] ?? '';
            $branch->save();
            
            Notification::make()
                ->title('E-Mail gespeichert')
                ->body("Die E-Mail-Adresse wurde erfolgreich aktualisiert.")
                ->success()
                ->send();
                
            $this->branchEditStates["email_{$branchId}"] = false;
            $this->loadBranches();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Fehler beim Speichern')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    public function toggleBranchActiveState(int $branchId): void
    {
        $branch = Branch::find($branchId);
        if (!$branch) return;
        
        try {
            $branch->active = !$branch->active;
            $branch->save();
            
            Notification::make()
                ->title('Status geändert')
                ->body($branch->active ? "Filiale wurde aktiviert." : "Filiale wurde deaktiviert.")
                ->success()
                ->send();
                
            $this->loadBranches();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Fehler beim Speichern')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    public function deleteBranch(int $branchId): void
    {
        $branch = Branch::find($branchId);
        if (!$branch) return;
        
        // Check if this is the last branch
        $branchCount = Branch::where('company_id', $branch->company_id)->count();
        if ($branchCount <= 1) {
            Notification::make()
                ->title('Löschen nicht möglich')
                ->body("Die letzte Filiale kann nicht gelöscht werden.")
                ->warning()
                ->send();
            return;
        }
        
        try {
            $branch->delete();
            
            Notification::make()
                ->title('Filiale gelöscht')
                ->body("Die Filiale wurde erfolgreich gelöscht.")
                ->success()
                ->send();
                
            $this->loadBranches();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Fehler beim Löschen')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}