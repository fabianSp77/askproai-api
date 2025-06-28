<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms;
use Filament\Forms\Form;
use App\Models\Company;
use App\Models\Branch;
use App\Models\PhoneNumber;
use App\Models\CalcomEventType;
use App\Models\Service;
use App\Services\MCP\CalcomMCPServer;
use App\Services\MCP\RetellMCPServer;
use App\Services\MCP\KnowledgeMCPServer;
use App\Services\MCP\StripeMCPServer;
use App\Services\MCP\WebhookMCPServer;
use App\Services\EventTypeMatchingService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CompanyIntegrationPortal extends Page implements HasForms, HasActions
{
    use InteractsWithActions;
    use InteractsWithForms;
    
    protected static ?string $navigationIcon = 'heroicon-o-building-office';
    protected static ?string $navigationLabel = 'Company Integration Portal';
    protected static ?string $title = 'Company Integration Portal';
    protected static ?string $navigationGroup = 'Einstellungen';
    protected static ?int $navigationSort = 3;
    
    protected static string $view = 'filament.admin.pages.company-integration-portal-professional';
    
    public static function canAccess(): bool
    {
        return auth()->check();
    }
    
    // State properties
    public array $companies = [];
    public ?int $selectedCompanyId = null;
    public ?Company $selectedCompany = null;
    public array $integrationStatus = [];
    public array $testResults = [];
    public bool $isTestingAll = false;
    public array $phoneNumbers = [];
    public array $branches = [];
    public array $retellAgents = [];
    public array $phoneAgentMapping = [];
    public array $branchEventTypes = [];
    public array $availableEventTypes = [];
    public array $serviceMappings = [];
    
    // Ultimate UI state properties
    public array $branchEditStates = [];
    public array $branchNames = [];
    public array $branchAddresses = [];
    public array $branchEmails = [];
    public array $branchPhoneNumbers = [];
    public array $branchRetellAgentIds = [];
    public bool $showEventTypeModal = false;
    public ?string $currentBranchId = null;
    
    // Form properties for simple template compatibility
    public ?string $calcomApiKey = null;
    public ?string $calcomTeamSlug = null;
    public ?string $retellApiKey = null;
    public ?string $retellAgentId = null;
    
    // Service instances
    protected ?CalcomMCPServer $calcomService = null;
    protected ?RetellMCPServer $retellService = null;
    protected ?WebhookMCPServer $webhookService = null;
    protected ?KnowledgeMCPServer $knowledgeService = null;
    protected ?StripeMCPServer $stripeService = null;
    
    public function mount(): void
    {
        $this->loadCompanies();
        
        // Auto-select company for non-super-admins
        $user = auth()->user();
        if (!$user->hasRole('super_admin') && $user->company_id) {
            $this->selectCompany($user->company_id);
        }
    }
    
    protected function loadCompanies(): void
    {
        try {
            $user = auth()->user();
            
            $query = Company::query();
            
            if (!$user->hasRole('super_admin') && !$user->can('view_any_company')) {
                $query->where('id', $user->company_id);
            }
            
            $this->companies = $query
                ->with(['branches', 'phoneNumbers'])
                ->get()
                ->map(function ($company) {
                    return [
                        'id' => $company->id,
                        'name' => $company->name,
                        'slug' => $company->slug,
                        'is_active' => $company->is_active,
                        'branch_count' => $company->branches->count(),
                        'phone_count' => $company->phoneNumbers->count(),
                        'created_at' => $company->created_at->format('Y-m-d'),
                    ];
                })
                ->toArray();
        } catch (\Exception $e) {
            Log::error('Error loading companies in Integration Portal: ' . $e->getMessage());
            $this->companies = [];
            Notification::make()
                ->title('Fehler beim Laden der Unternehmen')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    public function selectCompany(int $companyId): void
    {
        $user = auth()->user();
        
        // Security check
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
            $this->loadRetellAgentDetails();
            $this->loadServiceMappings();
            $this->initializeFormProperties();
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
    
    // Service getters
    protected function getCalcomService(): CalcomMCPServer
    {
        if (!$this->calcomService) {
            $this->calcomService = app(CalcomMCPServer::class);
        }
        return $this->calcomService;
    }
    
    protected function getRetellService(): RetellMCPServer
    {
        if (!$this->retellService) {
            $this->retellService = app(RetellMCPServer::class);
        }
        return $this->retellService;
    }
    
    protected function getWebhookService(): WebhookMCPServer
    {
        if (!$this->webhookService) {
            $this->webhookService = app(WebhookMCPServer::class);
        }
        return $this->webhookService;
    }
    
    protected function getKnowledgeService(): KnowledgeMCPServer
    {
        if (!$this->knowledgeService) {
            $this->knowledgeService = app(KnowledgeMCPServer::class);
        }
        return $this->knowledgeService;
    }
    
    protected function getStripeService(): StripeMCPServer
    {
        if (!$this->stripeService) {
            $this->stripeService = app(StripeMCPServer::class);
        }
        return $this->stripeService;
    }
    
    // Integration checks
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
                    'formatted' => $this->formatPhoneNumber($phone->number),
                    'is_active' => $phone->is_active,
                    'is_primary' => $phone->is_primary,
                    'branch_id' => $phone->branch_id,
                    'branch_name' => $phone->branch?->name,
                    'retell_agent_id' => $phone->retell_agent_id,
                    'active_version' => $phone->retell_agent_version ?? 'current',
                ];
            })
            ->toArray();
    }
    
    protected function formatPhoneNumber($number): string
    {
        // Basic formatting for display
        $cleaned = preg_replace('/[^0-9+]/', '', $number);
        if (strlen($cleaned) > 10) {
            // Format as international number
            return preg_replace('/(\+\d{2})(\d{2})(\d{3})(\d+)/', '$1 $2 $3 $4', $cleaned);
        }
        return $number;
    }
    
    protected function loadBranches(): void
    {
        $this->branches = $this->selectedCompany->branches()
            ->with(['phoneNumbers', 'eventTypes', 'staff'])
            ->get()
            ->map(function ($branch) {
                $primaryEventType = null;
                if ($branch->calcom_event_type_id) {
                    $primaryEventType = CalcomEventType::find($branch->calcom_event_type_id);
                }
                
                return [
                    'id' => $branch->id,
                    'name' => $branch->name,
                    'address' => $branch->address,
                    'email' => $branch->email,
                    'city' => $branch->city,
                    'is_active' => $branch->is_active,
                    'is_main' => $branch->is_main,
                    'phone_count' => $branch->phoneNumbers->count(),
                    'event_type_count' => $branch->eventTypes->count(),
                    'staff_count' => $branch->staff->count(),
                    'has_calcom_event_type' => !empty($branch->calcom_event_type_id),
                    'calcom_event_type_id' => $branch->calcom_event_type_id,
                    'primary_event_type_name' => $primaryEventType?->name,
                    'has_phone' => $branch->phoneNumbers->count() > 0,
                    'phone_number' => $branch->phoneNumbers->first()?->number,
                    'retell_agent_id' => $branch->retell_agent_id,
                    'has_retell' => !empty($branch->retell_agent_id),
                    'uses_master_retell' => empty($branch->retell_agent_id) && !empty($this->selectedCompany->retell_agent_id),
                    'is_configured' => $branch->is_active && !empty($branch->calcom_event_type_id) && $branch->phoneNumbers->count() > 0,
                ];
            })
            ->toArray();
    }
    
    protected function loadRetellAgentDetails(): void
    {
        if (!$this->selectedCompany->retell_api_key) {
            $this->retellAgents = [];
            return;
        }
        
        try {
            $response = $this->getRetellService()->listAgents($this->selectedCompanyId);
            
            if (isset($response['agents'])) {
                $this->retellAgents = $response['agents'];
                
                // Build phone-agent mapping
                $this->phoneAgentMapping = [];
                foreach ($this->phoneNumbers as $phone) {
                    if ($phone['retell_agent_id']) {
                        $this->phoneAgentMapping[$phone['id']] = $phone['retell_agent_id'];
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Error loading Retell agents: ' . $e->getMessage());
            $this->retellAgents = [];
        }
    }
    
    protected function loadServiceMappings(): void
    {
        $this->serviceMappings = DB::table('service_event_type_mappings')
            ->leftJoin('services', 'service_event_type_mappings.service_id', '=', 'services.id')
            ->leftJoin('calcom_event_types', 'service_event_type_mappings.calcom_event_type_id', '=', 'calcom_event_types.id')
            ->leftJoin('branches', 'service_event_type_mappings.branch_id', '=', 'branches.id')
            ->where('service_event_type_mappings.company_id', $this->selectedCompanyId)
            ->select([
                'service_event_type_mappings.id',
                'services.name as service_name',
                'calcom_event_types.name as event_type_name',
                'branches.name as branch_name',
                'service_event_type_mappings.keywords',
                'service_event_type_mappings.is_active',
            ])
            ->get()
            ->toArray();
    }
    
    // Actions
    public function refreshData(): void
    {
        $this->loadCompanies();
        if ($this->selectedCompanyId) {
            $this->selectCompany($this->selectedCompanyId);
        }
        
        Notification::make()
            ->title('Daten aktualisiert')
            ->success()
            ->send();
    }
    
    public function testCalcomIntegration(): void
    {
        try {
            $result = $this->getCalcomService()->testConnection(['company_id' => $this->selectedCompanyId]);
            
            $this->testResults['calcom'] = [
                'success' => $result['success'],
                'message' => $result['message'],
                'tested_at' => now()->format('H:i:s'),
            ];
        } catch (\Exception $e) {
            $this->testResults['calcom'] = [
                'success' => false,
                'message' => 'Fehler: ' . $e->getMessage(),
                'tested_at' => now()->format('H:i:s'),
            ];
        }
    }
    
    public function testRetellIntegration(): void
    {
        try {
            $result = $this->getRetellService()->testConnection(['company_id' => $this->selectedCompanyId]);
            
            $this->testResults['retell'] = [
                'success' => $result['success'],
                'message' => $result['message'],
                'tested_at' => now()->format('H:i:s'),
            ];
        } catch (\Exception $e) {
            $this->testResults['retell'] = [
                'success' => false,
                'message' => 'Fehler: ' . $e->getMessage(),
                'tested_at' => now()->format('H:i:s'),
            ];
        }
    }
    
    public function testStripeIntegration(): void
    {
        try {
            $result = $this->getStripeService()->testConnection(['company_id' => $this->selectedCompanyId]);
            
            $this->testResults['stripe'] = [
                'success' => $result['success'],
                'message' => $result['message'],
                'tested_at' => now()->format('H:i:s'),
            ];
        } catch (\Exception $e) {
            $this->testResults['stripe'] = [
                'success' => false,
                'message' => 'Fehler: ' . $e->getMessage(),
                'tested_at' => now()->format('H:i:s'),
            ];
        }
    }
    
    public function testAllIntegrations(): void
    {
        $this->isTestingAll = true;
        
        if ($this->integrationStatus['calcom']['configured']) {
            $this->testCalcomIntegration();
        }
        
        if ($this->integrationStatus['retell']['configured']) {
            $this->testRetellIntegration();
        }
        
        if ($this->integrationStatus['stripe']['configured']) {
            $this->testStripeIntegration();
        }
        
        $this->isTestingAll = false;
        
        Notification::make()
            ->title('Alle Tests abgeschlossen')
            ->success()
            ->send();
    }
    
    public function syncCalcomEventTypes(): void
    {
        try {
            $result = $this->getCalcomService()->syncEventTypes(['company_id' => $this->selectedCompanyId]);
            
            Notification::make()
                ->title('Event-Typen synchronisiert')
                ->body($result['message'] ?? 'Synchronisation erfolgreich')
                ->success()
                ->send();
            
            $this->loadIntegrationStatus();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Synchronisation fehlgeschlagen')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    public function syncRetellAgents(): void
    {
        try {
            $this->loadRetellAgentDetails();
            
            Notification::make()
                ->title('Retell Agents synchronisiert')
                ->body(count($this->retellAgents) . ' Agents gefunden')
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Synchronisation fehlgeschlagen')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    public function importRetellCalls(): void
    {
        try {
            $result = $this->getRetellService()->importRecentCalls(['company_id' => $this->selectedCompanyId, 'limit' => 50]);
            
            Notification::make()
                ->title('Anrufe importiert')
                ->body($result['message'] ?? 'Import erfolgreich')
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Import fehlgeschlagen')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    // Configuration Actions
    public function saveCalcomApiKeyAction(): Action
    {
        return Action::make('saveCalcomApiKey')
            ->label('API Key speichern')
            ->form([
                Forms\Components\TextInput::make('api_key')
                    ->label('Cal.com API Key')
                    ->password()
                    ->autocomplete('new-password')
                    ->required()
                    ->placeholder('cal_live_xxxxxxxxxxxxxxxxx')
                    ->helperText('Erstellen Sie einen API Key in Cal.com unter Settings > Developer')
            ])
            ->action(function (array $data): void {
                $this->selectedCompany->update([
                    'calcom_api_key' => $data['api_key']
                ]);
                
                $this->loadIntegrationStatus();
                
                Notification::make()
                    ->title('API Key gespeichert')
                    ->success()
                    ->send();
            });
    }
    
    public function saveCalcomTeamSlugAction(): Action
    {
        return Action::make('saveCalcomTeamSlug')
            ->label('Team Slug speichern')
            ->form([
                Forms\Components\TextInput::make('team_slug')
                    ->label('Cal.com Team Slug')
                    ->required()
                    ->placeholder('mein-team')
                    ->helperText('Optional: Nur erforderlich, wenn Sie Teams in Cal.com verwenden')
            ])
            ->action(function (array $data): void {
                $this->selectedCompany->update([
                    'calcom_team_slug' => $data['team_slug']
                ]);
                
                $this->loadIntegrationStatus();
                
                Notification::make()
                    ->title('Team Slug gespeichert')
                    ->success()
                    ->send();
            });
    }
    
    public function saveRetellApiKeyAction(): Action
    {
        return Action::make('saveRetellApiKey')
            ->label('API Key speichern')
            ->form([
                Forms\Components\TextInput::make('api_key')
                    ->label('Retell.ai API Key')
                    ->password()
                    ->autocomplete('new-password')
                    ->required()
                    ->placeholder('key_xxxxxxxxxxxxxxxxx')
                    ->helperText('Erstellen Sie einen API Key in Retell.ai unter Settings > API Keys')
            ])
            ->action(function (array $data): void {
                $this->selectedCompany->update([
                    'retell_api_key' => $data['api_key']
                ]);
                
                $this->loadIntegrationStatus();
                
                Notification::make()
                    ->title('API Key gespeichert')
                    ->success()
                    ->send();
            });
    }
    
    public function saveRetellAgentIdAction(): Action
    {
        return Action::make('saveRetellAgentId')
            ->label('Agent ID speichern')
            ->form([
                Forms\Components\TextInput::make('agent_id')
                    ->label('Retell.ai Agent ID')
                    ->required()
                    ->placeholder('agent_xxxxxxxxxxxxxxxxx')
                    ->helperText('Die ID Ihres konfigurierten Agents in Retell.ai')
            ])
            ->action(function (array $data): void {
                $this->selectedCompany->update([
                    'retell_agent_id' => $data['agent_id']
                ]);
                
                $this->loadIntegrationStatus();
                
                Notification::make()
                    ->title('Agent ID gespeichert')
                    ->success()
                    ->send();
            });
    }
    
    public function openSetupWizard(): void
    {
        Notification::make()
            ->title('Setup Wizard')
            ->body('Der Setup Wizard wird in Kürze verfügbar sein.')
            ->info()
            ->send();
    }
    
    public function removeServiceMapping(int $mappingId): void
    {
        DB::table('service_event_type_mappings')
            ->where('id', $mappingId)
            ->where('company_id', $this->selectedCompanyId)
            ->delete();
        
        $this->loadServiceMappings();
        
        Notification::make()
            ->title('Zuordnung entfernt')
            ->success()
            ->send();
    }
    
    public function openServiceMappingModalAction(): Action
    {
        return Action::make('openServiceMappingModal')
            ->label('Neue Zuordnung')
            ->form([
                Forms\Components\Select::make('service_id')
                    ->label('Service')
                    ->options(Service::where('company_id', $this->selectedCompanyId)->pluck('name', 'id'))
                    ->required(),
                Forms\Components\Select::make('calcom_event_type_id')
                    ->label('Cal.com Event Type')
                    ->options(CalcomEventType::where('company_id', $this->selectedCompanyId)->pluck('name', 'id'))
                    ->required(),
                Forms\Components\Select::make('branch_id')
                    ->label('Filiale (optional)')
                    ->options(Branch::where('company_id', $this->selectedCompanyId)->pluck('name', 'id'))
                    ->placeholder('Alle Filialen'),
                Forms\Components\TagsInput::make('keywords')
                    ->label('Keywords (optional)')
                    ->placeholder('Fügen Sie Keywords hinzu und drücken Sie Enter')
                    ->helperText('Diese Keywords helfen bei der automatischen Zuordnung'),
            ])
            ->action(function (array $data): void {
                DB::table('service_event_type_mappings')->insert([
                    'company_id' => $this->selectedCompanyId,
                    'service_id' => $data['service_id'],
                    'calcom_event_type_id' => $data['calcom_event_type_id'],
                    'branch_id' => $data['branch_id'] ?? null,
                    'keywords' => json_encode($data['keywords'] ?? []),
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                
                $this->loadServiceMappings();
                
                Notification::make()
                    ->title('Zuordnung erstellt')
                    ->success()
                    ->send();
            });
    }
    
    // Simple form submission methods for the simple template
    public function saveCalcomConfig(): void
    {
        if (!$this->selectedCompany) return;
        
        $this->selectedCompany->update([
            'calcom_api_key' => $this->calcomApiKey,
            'calcom_team_slug' => $this->calcomTeamSlug,
        ]);
        
        $this->loadIntegrationStatus();
        
        Notification::make()
            ->title('Cal.com Konfiguration gespeichert')
            ->success()
            ->send();
    }
    
    public function saveRetellConfig(): void
    {
        if (!$this->selectedCompany) return;
        
        $this->selectedCompany->update([
            'retell_api_key' => $this->retellApiKey,
            'retell_agent_id' => $this->retellAgentId,
        ]);
        
        $this->loadIntegrationStatus();
        
        Notification::make()
            ->title('Retell.ai Konfiguration gespeichert')
            ->success()
            ->send();
    }
    
    // Ultimate UI Methods for Branch Management
    public function toggleBranchNameInput(int $branchId): void
    {
        $key = "name_{$branchId}";
        $this->branchEditStates[$key] = !($this->branchEditStates[$key] ?? false);
        
        if ($this->branchEditStates[$key]) {
            $branch = Branch::find($branchId);
            if ($branch) {
                $this->branchNames[$branchId] = $branch->name;
            }
        }
    }
    
    public function saveBranchName(int $branchId): void
    {
        $branch = Branch::find($branchId);
        if ($branch && isset($this->branchNames[$branchId])) {
            $branch->update(['name' => $this->branchNames[$branchId]]);
            $this->loadBranches();
            $this->branchEditStates["name_{$branchId}"] = false;
            
            Notification::make()
                ->title('Filialname aktualisiert')
                ->success()
                ->send();
        }
    }
    
    public function toggleBranchAddressInput(int $branchId): void
    {
        $key = "address_{$branchId}";
        $this->branchEditStates[$key] = !($this->branchEditStates[$key] ?? false);
        
        if ($this->branchEditStates[$key]) {
            $branch = Branch::find($branchId);
            if ($branch) {
                $this->branchAddresses[$branchId] = $branch->address;
            }
        }
    }
    
    public function saveBranchAddress(int $branchId): void
    {
        $branch = Branch::find($branchId);
        if ($branch && isset($this->branchAddresses[$branchId])) {
            $branch->update(['address' => $this->branchAddresses[$branchId]]);
            $this->loadBranches();
            $this->branchEditStates["address_{$branchId}"] = false;
            
            Notification::make()
                ->title('Adresse aktualisiert')
                ->success()
                ->send();
        }
    }
    
    public function toggleBranchEmailInput(int $branchId): void
    {
        $key = "email_{$branchId}";
        $this->branchEditStates[$key] = !($this->branchEditStates[$key] ?? false);
        
        if ($this->branchEditStates[$key]) {
            $branch = Branch::find($branchId);
            if ($branch) {
                $this->branchEmails[$branchId] = $branch->email;
            }
        }
    }
    
    public function saveBranchEmail(int $branchId): void
    {
        $branch = Branch::find($branchId);
        if ($branch && isset($this->branchEmails[$branchId])) {
            $branch->update(['email' => $this->branchEmails[$branchId]]);
            $this->loadBranches();
            $this->branchEditStates["email_{$branchId}"] = false;
            
            Notification::make()
                ->title('E-Mail aktualisiert')
                ->success()
                ->send();
        }
    }
    
    public function toggleBranchPhoneNumberInput(int $branchId): void
    {
        $key = "phone_{$branchId}";
        $this->branchEditStates[$key] = !($this->branchEditStates[$key] ?? false);
        
        if ($this->branchEditStates[$key]) {
            $branch = Branch::find($branchId);
            if ($branch && $branch->phoneNumbers->first()) {
                $this->branchPhoneNumbers[$branchId] = $branch->phoneNumbers->first()->number;
            }
        }
    }
    
    public function saveBranchPhoneNumber(int $branchId): void
    {
        // This would need more complex logic to handle phone number creation/update
        // For now, just close the editor
        $this->branchEditStates["phone_{$branchId}"] = false;
        
        Notification::make()
            ->title('Telefonnummer-Verwaltung')
            ->body('Bitte verwenden Sie die Telefonnummern-Verwaltung oben.')
            ->info()
            ->send();
    }
    
    public function toggleBranchRetellAgentInput(int $branchId): void
    {
        $key = "retell_{$branchId}";
        $this->branchEditStates[$key] = !($this->branchEditStates[$key] ?? false);
        
        if ($this->branchEditStates[$key]) {
            $branch = Branch::find($branchId);
            if ($branch) {
                $this->branchRetellAgentIds[$branchId] = $branch->retell_agent_id;
            }
        }
    }
    
    public function saveBranchRetellAgent(int $branchId): void
    {
        $branch = Branch::find($branchId);
        if ($branch) {
            $branch->update(['retell_agent_id' => $this->branchRetellAgentIds[$branchId] ?? null]);
            $this->loadBranches();
            $this->branchEditStates["retell_{$branchId}"] = false;
            
            Notification::make()
                ->title('Retell Agent aktualisiert')
                ->success()
                ->send();
        }
    }
    
    public function toggleBranchActiveState(int $branchId): void
    {
        $branch = Branch::find($branchId);
        if ($branch) {
            $branch->update(['is_active' => !$branch->is_active]);
            $this->loadBranches();
            
            Notification::make()
                ->title($branch->is_active ? 'Filiale aktiviert' : 'Filiale deaktiviert')
                ->success()
                ->send();
        }
    }
    
    public function deleteBranch(int $branchId): void
    {
        $branch = Branch::find($branchId);
        if ($branch && !$branch->is_main) {
            $branch->delete();
            $this->loadBranches();
            
            Notification::make()
                ->title('Filiale gelöscht')
                ->success()
                ->send();
        }
    }
    
    // Event Type Management
    public function manageBranchEventTypes(string $branchId): void
    {
        $this->currentBranchId = $branchId;
        $this->loadBranchEventTypes();
        $this->showEventTypeModal = true;
    }
    
    public function closeEventTypeModal(): void
    {
        $this->showEventTypeModal = false;
        $this->currentBranchId = null;
    }
    
    protected function loadBranchEventTypes(): void
    {
        if (!$this->currentBranchId) return;
        
        // Load event types for the current branch
        $branch = Branch::with('eventTypes')->find($this->currentBranchId);
        if ($branch) {
            $this->branchEventTypes[$this->currentBranchId] = $branch->eventTypes->map(function ($eventType) use ($branch) {
                return [
                    'id' => $eventType->id,
                    'name' => $eventType->name,
                    'calcom_id' => $eventType->calcom_id,
                    'duration' => $eventType->duration,
                    'is_primary' => $eventType->id === $branch->calcom_event_type_id,
                ];
            })->toArray();
            
            // Load available event types (not yet assigned to this branch)
            $assignedIds = $branch->eventTypes->pluck('id')->toArray();
            $this->availableEventTypes = CalcomEventType::where('company_id', $this->selectedCompanyId)
                ->whereNotIn('id', $assignedIds)
                ->get()
                ->map(function ($eventType) {
                    return [
                        'id' => $eventType->id,
                        'name' => $eventType->name,
                        'calcom_id' => $eventType->calcom_id,
                        'duration' => $eventType->duration,
                    ];
                })
                ->toArray();
        }
    }
    
    public function setPrimaryEventType(string $branchId, int $eventTypeId): void
    {
        $branch = Branch::find($branchId);
        if ($branch) {
            $branch->update(['calcom_event_type_id' => $eventTypeId]);
            $this->loadBranchEventTypes();
            
            Notification::make()
                ->title('Primärer Event Type festgelegt')
                ->success()
                ->send();
        }
    }
    
    public function addBranchEventType(string $branchId, int $eventTypeId): void
    {
        $branch = Branch::find($branchId);
        $eventType = CalcomEventType::find($eventTypeId);
        
        if ($branch && $eventType) {
            // Add to branch_event_types pivot table if it exists
            // For now, we'll just set it as the primary if none exists
            if (!$branch->calcom_event_type_id) {
                $branch->update(['calcom_event_type_id' => $eventTypeId]);
            }
            
            $this->loadBranchEventTypes();
            
            Notification::make()
                ->title('Event Type hinzugefügt')
                ->success()
                ->send();
        }
    }
    
    public function removeBranchEventType(string $branchId, int $eventTypeId): void
    {
        $branch = Branch::find($branchId);
        if ($branch && $branch->calcom_event_type_id == $eventTypeId) {
            $branch->update(['calcom_event_type_id' => null]);
            $this->loadBranchEventTypes();
            
            Notification::make()
                ->title('Event Type entfernt')
                ->success()
                ->send();
        }
    }
    
    // Phone Agent Mapping
    public function updatePhoneAgent(int $phoneId, ?string $agentId): void
    {
        $phone = PhoneNumber::find($phoneId);
        if ($phone) {
            $phone->update(['retell_agent_id' => $agentId ?: null]);
            $this->loadPhoneNumbers();
            
            Notification::make()
                ->title('Agent zugeordnet')
                ->success()
                ->send();
        }
    }
    
    public function showAgentDetails(string $agentId): void
    {
        // This could open a modal with agent details
        // For now, just show a notification
        $agent = collect($this->retellAgents)->firstWhere('agent_id', $agentId);
        if ($agent) {
            Notification::make()
                ->title($agent['agent_name'] ?? 'Agent Details')
                ->body('Voice: ' . ($agent['voice_id'] ?? 'Standard') . ' | Language: ' . ($agent['language'] ?? 'de-DE'))
                ->info()
                ->send();
        }
    }
    
    // Initialize form properties when company is selected
    protected function initializeFormProperties(): void
    {
        if ($this->selectedCompany) {
            $this->calcomApiKey = $this->selectedCompany->calcom_api_key;
            $this->calcomTeamSlug = $this->selectedCompany->calcom_team_slug;
            $this->retellApiKey = $this->selectedCompany->retell_api_key;
            $this->retellAgentId = $this->selectedCompany->retell_agent_id;
        }
    }
}