<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use App\Models\Company;
use App\Models\Branch;
use App\Models\PhoneNumber;
use App\Services\MCP\RetellMCPServer;
use App\Services\Config\RetellConfigValidator;
use App\Services\RetellV2Service;
use App\Filament\Admin\Traits\HasLoadingStates;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\View;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Actions\Action;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;

class RetellAgentImportWizard extends Page implements HasForms
{
    use InteractsWithForms, HasLoadingStates;
    
    protected static ?string $navigationIcon = 'heroicon-o-phone-arrow-down-left';
    protected static ?string $navigationGroup = 'Einstellungen';
    protected static ?string $navigationLabel = 'Retell Agent Import';
    protected static ?int $navigationSort = 15;
    
    public static function shouldRegisterNavigation(): bool
    {
        return false; // Deaktiviert - Use RetellUltimateControlCenter instead
    }
    protected static string $view = 'filament.admin.pages.retell-agent-import-wizard';
    protected static ?string $title = 'Retell Agent Import & Konfiguration';
    
    // Wizard Steps
    public int $currentStep = 1;
    public int $totalSteps = 5;
    
    // Form data
    public ?array $data = [];
    
    // Step 1: Company Selection
    public ?int $company_id = null;
    
    // Step 2: Agent Discovery & Validation
    public array $agentsPreview = [];
    public array $validationResults = [];
    public array $importSelections = [];
    
    // Step 3: Phone Number Mapping
    public array $phoneNumbers = [];
    public array $phoneMappings = [];
    
    // Step 4: Branch Mapping & Prompt Edit
    public array $branchMappings = [];
    public array $promptEdits = [];
    
    // Step 5: Summary & Confirmation
    public array $importSummary = [];
    
    // Services
    private ?RetellMCPServer $mcpServer = null;
    private ?RetellConfigValidator $configValidator = null;
    
    public function mount(): void
    {
        // Initialize services
        try {
            $this->mcpServer = app(RetellMCPServer::class);
        } catch (\Exception $e) {
            Log::error('Failed to initialize RetellMCPServer', ['error' => $e->getMessage()]);
            $this->mcpServer = new RetellMCPServer();
        }
        
        // Initialize form data
        $user = auth()->user();
        $this->data = [
            'company_id' => $user->company_id ?? null,
        ];
        
        if ($user->company_id) {
            $this->company_id = $user->company_id;
        }
        
        $this->form->fill($this->data);
    }
    
    public function form(Form $form): Form
    {
        return $form
            ->schema($this->getFormSchema())
            ->statePath('data');
    }
    
    protected function getFormSchema(): array
    {
        return match($this->currentStep) {
            1 => $this->getStep1Schema(),
            2 => $this->getStep2Schema(),
            3 => $this->getStep3Schema(),
            4 => $this->getStep4Schema(),
            5 => $this->getStep5Schema(),
            default => []
        };
    }
    
    /**
     * Step 1: Company Selection
     */
    protected function getStep1Schema(): array
    {
        return [
            Section::make('Schritt 1: Unternehmen auswählen')
                ->description('Wählen Sie das Unternehmen für den Retell Agent Import aus.')
                ->schema([
                    Select::make('company_id')
                        ->label('Unternehmen')
                        ->options(function () {
                            $user = auth()->user();
                            
                            // Super admins see all companies with Retell API key
                            if ($user->hasRole('super_admin')) {
                                return Company::whereNotNull('retell_api_key')
                                    ->pluck('name', 'id');
                            }
                            
                            // Regular users only see their company
                            if ($user->company_id) {
                                return Company::where('id', $user->company_id)
                                    ->whereNotNull('retell_api_key')
                                    ->pluck('name', 'id');
                            }
                            
                            return [];
                        })
                        ->required()
                        ->reactive()
                        ->afterStateUpdated(fn ($state) => $this->company_id = $state)
                        ->helperText('Nur Unternehmen mit konfiguriertem Retell API Key werden angezeigt.'),
                ]),
        ];
    }
    
    /**
     * Step 2: Agent Discovery & Validation
     */
    protected function getStep2Schema(): array
    {
        return [
            Section::make('Schritt 2: Agents entdecken und validieren')
                ->description('Überprüfung der konfigurierten Retell Agents und deren Webhook-Konfiguration.')
                ->schema([
                    View::make('filament.components.retell-agents-preview')
                        ->viewData([
                            'agents' => $this->agentsPreview,
                            'validationResults' => $this->validationResults,
                        ]),
                ]),
        ];
    }
    
    /**
     * Step 3: Phone Number Sync
     */
    protected function getStep3Schema(): array
    {
        return [
            Section::make('Schritt 3: Telefonnummern synchronisieren')
                ->description('Zuordnung der Retell Telefonnummern zu Filialen.')
                ->schema([
                    Repeater::make('phoneMappings')
                        ->label('Telefonnummer-Zuordnungen')
                        ->schema([
                            TextInput::make('phone_number')
                                ->label('Telefonnummer')
                                ->disabled(),
                            
                            TextInput::make('agent_name')
                                ->label('Agent')
                                ->disabled(),
                            
                            Select::make('branch_id')
                                ->label('Filiale')
                                ->options(
                                    Branch::where('company_id', $this->company_id)
                                        ->pluck('name', 'id')
                                )
                                ->required(),
                            
                            Toggle::make('is_primary')
                                ->label('Primäre Nummer')
                                ->default(false),
                        ])
                        ->defaultItems(0)
                        ->disableItemCreation()
                        ->disableItemDeletion(),
                ]),
        ];
    }
    
    /**
     * Step 4: Branch Mapping & Prompt Editing
     */
    protected function getStep4Schema(): array
    {
        return [
            Section::make('Schritt 4: Agent-Konfiguration anpassen')
                ->description('Passen Sie die Agent-Prompts an und ordnen Sie Agents den Filialen zu.')
                ->schema([
                    Repeater::make('branchMappings')
                        ->label('Agent-Zuordnungen')
                        ->schema([
                            Grid::make(2)
                                ->schema([
                                    TextInput::make('agent_name')
                                        ->label('Agent Name')
                                        ->disabled(),
                                    
                                    Select::make('branch_id')
                                        ->label('Filiale')
                                        ->options(
                                            Branch::where('company_id', $this->company_id)
                                                ->pluck('name', 'id')
                                        )
                                        ->required(),
                                ]),
                            
                            Textarea::make('prompt')
                                ->label('Agent Prompt')
                                ->rows(10)
                                ->helperText('Passen Sie den Prompt an Ihre Bedürfnisse an.')
                                ->required(),
                            
                            Toggle::make('auto_fix_config')
                                ->label('Konfigurationsfehler automatisch beheben')
                                ->default(true)
                                ->helperText('Webhook URLs und Events werden automatisch korrigiert.'),
                        ])
                        ->defaultItems(0)
                        ->disableItemCreation()
                        ->disableItemDeletion(),
                ]),
        ];
    }
    
    /**
     * Step 5: Summary & Confirmation
     */
    protected function getStep5Schema(): array
    {
        return [
            Section::make('Schritt 5: Zusammenfassung')
                ->description('Überprüfen Sie die Änderungen vor dem Import.')
                ->schema([
                    View::make('filament.components.retell-import-summary')
                        ->viewData([
                            'summary' => $this->importSummary,
                        ]),
                ]),
        ];
    }
    
    /**
     * Navigation Methods
     */
    public function nextStep(): void
    {
        $this->validate();
        
        // Process step-specific logic
        match($this->currentStep) {
            1 => $this->processStep1(),
            2 => $this->processStep2(),
            3 => $this->processStep3(),
            4 => $this->processStep4(),
            default => null
        };
        
        if ($this->currentStep < $this->totalSteps) {
            $this->currentStep++;
            $this->form->fill($this->data);
        }
    }
    
    public function previousStep(): void
    {
        if ($this->currentStep > 1) {
            $this->currentStep--;
            $this->form->fill($this->data);
        }
    }
    
    /**
     * Step Processing Methods
     */
    protected function processStep1(): void
    {
        $this->withLoading(
            fn() => $this->discoverAgents(),
            'Retell Agents werden geladen...'
        );
    }
    
    protected function processStep2(): void
    {
        // Save selected agents for import
        $this->data['selected_agents'] = array_keys(
            array_filter($this->importSelections)
        );
    }
    
    protected function processStep3(): void
    {
        // Save phone mappings
        $this->data['phone_mappings'] = $this->phoneMappings;
    }
    
    protected function processStep4(): void
    {
        // Save branch mappings and prompts
        $this->data['branch_mappings'] = $this->branchMappings;
        $this->prepareImportSummary();
    }
    
    /**
     * Agent Discovery
     */
    protected function discoverAgents(): void
    {
        try {
            // Get agents with phone numbers
            $result = $this->mcpServer->getAgentsWithPhoneNumbers([
                'company_id' => $this->company_id
            ]);
            
            if (isset($result['error'])) {
                Notification::make()
                    ->title('Fehler beim Laden der Agents')
                    ->body($result['error'])
                    ->danger()
                    ->send();
                return;
            }
            
            $this->agentsPreview = $result['agents'] ?? [];
            
            // Initialize validator
            $company = Company::find($this->company_id);
            $retellService = new RetellV2Service(decrypt($company->retell_api_key));
            $this->configValidator = new RetellConfigValidator($retellService);
            
            // Validate each agent
            foreach ($this->agentsPreview as $agent) {
                $agentId = $agent['agent_id'];
                
                // Validate configuration
                $validation = $this->configValidator->validateAgentConfiguration($agentId);
                
                $this->validationResults[$agentId] = [
                    'valid' => $validation->isValid(),
                    'issues' => $validation->getIssues(),
                    'warnings' => $validation->getWarnings(),
                    'auto_fixable' => count($validation->getAutoFixableIssues())
                ];
                
                // Pre-select agents for import
                $this->importSelections[$agentId] = true;
            }
            
            // Prepare phone mappings for next step
            $this->preparePhoneMappings();
            
        } catch (\Exception $e) {
            Log::error('Failed to discover Retell agents', [
                'company_id' => $this->company_id,
                'error' => $e->getMessage()
            ]);
            
            Notification::make()
                ->title('Fehler')
                ->body('Agents konnten nicht geladen werden: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    /**
     * Prepare phone mappings
     */
    protected function preparePhoneMappings(): void
    {
        $this->phoneMappings = [];
        
        foreach ($this->agentsPreview as $agent) {
            if (!isset($this->importSelections[$agent['agent_id']]) || 
                !$this->importSelections[$agent['agent_id']]) {
                continue;
            }
            
            foreach ($agent['phone_numbers'] ?? [] as $phone) {
                $this->phoneMappings[] = [
                    'phone_number' => $phone['phone_number'],
                    'phone_id' => $phone['phone_number_id'],
                    'agent_id' => $agent['agent_id'],
                    'agent_name' => $agent['agent_name'],
                    'branch_id' => $agent['branch']['id'] ?? null,
                    'is_primary' => false,
                ];
            }
        }
        
        // Also prepare branch mappings
        $this->prepareBranchMappings();
    }
    
    /**
     * Prepare branch mappings
     */
    protected function prepareBranchMappings(): void
    {
        $this->branchMappings = [];
        
        foreach ($this->agentsPreview as $agent) {
            if (!isset($this->importSelections[$agent['agent_id']]) || 
                !$this->importSelections[$agent['agent_id']]) {
                continue;
            }
            
            $this->branchMappings[] = [
                'agent_id' => $agent['agent_id'],
                'agent_name' => $agent['agent_name'],
                'branch_id' => $agent['branch']['id'] ?? null,
                'prompt' => $agent['prompt'] ?? '',
                'auto_fix_config' => true,
            ];
        }
    }
    
    /**
     * Prepare import summary
     */
    protected function prepareImportSummary(): void
    {
        $this->importSummary = [
            'total_agents' => count($this->data['selected_agents'] ?? []),
            'phone_numbers' => count($this->data['phone_mappings'] ?? []),
            'branches_mapped' => count(array_filter(
                $this->branchMappings, 
                fn($m) => !empty($m['branch_id'])
            )),
            'auto_fixes' => count(array_filter(
                $this->branchMappings,
                fn($m) => $m['auto_fix_config']
            )),
            'details' => $this->branchMappings,
        ];
    }
    
    /**
     * Final Import
     */
    public function import(): void
    {
        $this->withLoading(
            fn() => $this->performImport(),
            'Import wird durchgeführt...'
        );
    }
    
    protected function performImport(): void
    {
        DB::beginTransaction();
        
        try {
            $successCount = 0;
            $errorCount = 0;
            $errors = [];
            
            foreach ($this->branchMappings as $mapping) {
                try {
                    $agentId = $mapping['agent_id'];
                    $branchId = $mapping['branch_id'];
                    
                    // Update branch with agent ID
                    if ($branchId) {
                        $branch = Branch::find($branchId);
                        if ($branch) {
                            $branch->update(['retell_agent_id' => $agentId]);
                        }
                    }
                    
                    // Update phone numbers
                    foreach ($this->phoneMappings as $phoneMapping) {
                        if ($phoneMapping['agent_id'] === $agentId && $phoneMapping['branch_id']) {
                            PhoneNumber::updateOrCreate(
                                ['number' => $phoneMapping['phone_number']],
                                [
                                    'company_id' => $this->company_id,
                                    'branch_id' => $phoneMapping['branch_id'],
                                    'retell_phone_id' => $phoneMapping['phone_id'],
                                    'retell_agent_id' => $agentId,
                                    'is_primary' => $phoneMapping['is_primary'],
                                    'type' => 'retell',
                                    'is_active' => true,
                                ]
                            );
                        }
                    }
                    
                    // Update agent prompt if changed
                    if (!empty($mapping['prompt'])) {
                        $this->mcpServer->updateAgentPrompt([
                            'agent_id' => $agentId,
                            'prompt' => $mapping['prompt'],
                            'company_id' => $this->company_id,
                            'validate' => false, // Skip validation for now
                        ]);
                    }
                    
                    // Auto-fix configuration if requested
                    if ($mapping['auto_fix_config']) {
                        $this->mcpServer->validateAndFixAgentConfig([
                            'agent_id' => $agentId,
                            'company_id' => $this->company_id,
                            'auto_fix' => true,
                        ]);
                    }
                    
                    $successCount++;
                    
                } catch (\Exception $e) {
                    $errorCount++;
                    $errors[] = "Agent {$mapping['agent_name']}: " . $e->getMessage();
                    Log::error('Failed to import Retell agent', [
                        'agent_id' => $agentId ?? null,
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            DB::commit();
            
            // Clear caches
            Cache::forget("mcp:retell:agents_with_phones:{$this->company_id}");
            
            // Show results
            if ($successCount > 0) {
                Notification::make()
                    ->title('Import erfolgreich')
                    ->body("{$successCount} Agents erfolgreich importiert.")
                    ->success()
                    ->send();
            }
            
            if ($errorCount > 0) {
                Notification::make()
                    ->title('Import teilweise fehlgeschlagen')
                    ->body("{$errorCount} Fehler aufgetreten: " . implode(', ', $errors))
                    ->warning()
                    ->send();
            }
            
            // Redirect to branch list
            $this->redirect(route('filament.admin.resources.branches.index'));
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Retell agent import failed', [
                'company_id' => $this->company_id,
                'error' => $e->getMessage()
            ]);
            
            Notification::make()
                ->title('Import fehlgeschlagen')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    /**
     * Actions
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('testWebhook')
                ->label('Webhook testen')
                ->icon('heroicon-o-signal')
                ->action(function () {
                    $result = $this->mcpServer->testWebhookEndpoint([]);
                    
                    if ($result['success']) {
                        Notification::make()
                            ->title('Webhook erreichbar')
                            ->body("Response Zeit: {$result['response_time_ms']}ms")
                            ->success()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Webhook nicht erreichbar')
                            ->body($result['error'] ?? 'Unbekannter Fehler')
                            ->danger()
                            ->send();
                    }
                })
                ->visible(fn () => $this->currentStep === 2),
        ];
    }
    
    /**
     * Validation
     */
    public function validate($rules = null, $messages = [], $attributes = []): array
    {
        $stepRules = match($this->currentStep) {
            1 => ['data.company_id' => 'required|exists:companies,id'],
            default => []
        };
        
        if (!empty($stepRules)) {
            return $this->form->validate($stepRules);
        }
        
        return [];
    }
}