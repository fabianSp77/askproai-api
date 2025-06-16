<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use App\Models\Company;
use App\Models\Branch;
use App\Models\CalcomEventType;
use App\Services\CalcomSyncService;
use App\Services\EventTypeNameParser;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;

class EventTypeImportWizard extends Page implements HasForms
{
    use InteractsWithForms;
    
    protected static ?string $navigationIcon = 'heroicon-o-arrow-down-tray';
    protected static ?string $navigationGroup = 'Kalender & Events';
    protected static ?string $navigationLabel = 'Event-Type Import';
    protected static ?int $navigationSort = 30;
    protected static string $view = 'filament.admin.pages.event-type-import-wizard';
    protected static ?string $title = 'Event-Type Import Wizard';
    
    // Wizard Steps
    public int $currentStep = 1;
    
    // Form data
    public ?array $data = [];
    
    // Step 1: Auswahl
    public ?int $company_id = null;
    public ?string $branch_id = null;
    
    // Step 2: Preview
    public array $eventTypesPreview = [];
    public array $importSelections = [];
    
    // Step 3: Mapping
    public array $mappings = [];
    
    // Step 4: Confirmation
    public array $importSummary = [];
    
    private ?CalcomSyncService $calcomService = null;
    private ?EventTypeNameParser $nameParser = null;
    
    public function mount(): void
    {
        // Initialize services first
        try {
            $this->calcomService = app(CalcomSyncService::class);
            $this->nameParser = app(EventTypeNameParser::class);
        } catch (\Exception $e) {
            Log::error('Failed to initialize services in EventTypeImportWizard', [
                'error' => $e->getMessage()
            ]);
            
            // Create instances directly if service container fails
            $this->calcomService = new CalcomSyncService();
            $this->nameParser = new EventTypeNameParser();
        }
        
        // Initialize form data
        $this->data = [
            'company_id' => null,
            'branch_id' => null,
        ];
        
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
            default => []
        };
    }
    
    /**
     * Step 1: Company und Branch Auswahl
     */
    protected function getStep1Schema(): array
    {
        return [
            Section::make('Schritt 1: Unternehmen und Filiale auswählen')
                ->description('Wählen Sie das Unternehmen und die Zielfiliale für den Import aus.')
                ->schema([
                    Select::make('company_id')
                        ->label('Unternehmen')
                        ->options(Company::whereNotNull('calcom_api_key')->pluck('name', 'id'))
                        ->required()
                        ->searchable()
                        ->live()
                        ->afterStateUpdated(function ($state, callable $set) {
                            $this->company_id = $state;
                            $this->branch_id = null;
                            $set('branch_id', null);
                            $this->eventTypesPreview = [];
                        }),
                    
                    Select::make('branch_id')
                        ->label('Zielfiliale')
                        ->options(function (callable $get) {
                            $companyId = $get('company_id');
                            if (!$companyId) {
                                return [];
                            }
                            return Branch::where('company_id', $companyId)
                                ->where('active', true)
                                ->pluck('name', 'id');
                        })
                        ->required()
                        ->searchable()
                        ->disabled(fn (callable $get) => empty($get('company_id')))
                        ->helperText('Alle importierten Event-Types werden dieser Filiale zugeordnet')
                        ->live()
                        ->afterStateUpdated(function ($state) {
                            $this->branch_id = $state;
                        }),
                ])
        ];
    }
    
    /**
     * Step 2: Event-Type Preview
     */
    protected function getStep2Schema(): array
    {
        return [
            Section::make('Schritt 2: Event-Types Vorschau')
                ->description('Überprüfen Sie die gefundenen Event-Types und deren automatische Zuordnung.')
                ->schema([
                    // Dynamisch generierte Preview wird in der View gehandhabt
                ])
        ];
    }
    
    /**
     * Step 3: Mapping Korrektur
     */
    protected function getStep3Schema(): array
    {
        return [
            Section::make('Schritt 3: Zuordnungen überprüfen')
                ->description('Korrigieren Sie bei Bedarf die automatischen Zuordnungen.')
                ->schema([
                    Repeater::make('mappings')
                        ->label('Event-Type Zuordnungen')
                        ->schema([
                            TextInput::make('original_name')
                                ->label('Original Name')
                                ->disabled(),
                            
                            TextInput::make('service_name')
                                ->label('Service Name')
                                ->required(),
                            
                            Toggle::make('import')
                                ->label('Importieren')
                                ->default(true),
                        ])
                        ->disableItemCreation()
                        ->disableItemDeletion()
                ])
        ];
    }
    
    /**
     * Step 4: Import Bestätigung
     */
    protected function getStep4Schema(): array
    {
        return [
            Section::make('Schritt 4: Import-Zusammenfassung')
                ->description('Bestätigen Sie den Import der ausgewählten Event-Types.')
                ->schema([
                    // Zusammenfassung wird in der View angezeigt
                ])
        ];
    }
    
    /**
     * Navigations-Methoden
     */
    public function nextStep(): void
    {
        // Validierung je nach Step
        if ($this->currentStep === 1) {
            // Get form data
            $this->company_id = $this->data['company_id'] ?? null;
            $this->branch_id = $this->data['branch_id'] ?? null;
            
            if (!$this->company_id || !$this->branch_id) {
                Notification::make()
                    ->title('Bitte wählen Sie Unternehmen und Filiale aus')
                    ->danger()
                    ->send();
                return;
            }
            
            // Lade Event-Types von Cal.com
            $this->loadEventTypesPreview();
        }
        elseif ($this->currentStep === 2) {
            // Bereite Mappings vor
            $this->prepareMappings();
        }
        elseif ($this->currentStep === 3) {
            // Erstelle Import-Zusammenfassung
            $this->prepareImportSummary();
        }
        
        if ($this->currentStep < 4) {
            $this->currentStep++;
        }
    }
    
    public function previousStep(): void
    {
        if ($this->currentStep > 1) {
            $this->currentStep--;
        }
    }
    
    /**
     * Toggle all selections
     */
    public function toggleAllSelections(): void
    {
        $allSelected = count(array_filter($this->importSelections)) === count($this->eventTypesPreview);
        
        foreach ($this->eventTypesPreview as $index => $preview) {
            // Only toggle if not disabled (skip)
            if ($preview['suggested_action'] !== 'skip') {
                $this->importSelections[$index] = !$allSelected;
            }
        }
    }
    
    /**
     * Lade Event-Types von Cal.com und analysiere sie
     */
    private function loadEventTypesPreview(): void
    {
        try {
            $company = Company::find($this->company_id);
            $branch = Branch::find($this->branch_id);
            
            // Debug: Log API Key existence
            Log::info('Loading event types', [
                'company_id' => $this->company_id,
                'company_name' => $company->name,
                'has_api_key' => !empty($company->calcom_api_key),
                'branch_id' => $this->branch_id
            ]);
            
            // Hole Event-Types von Cal.com
            $response = $this->fetchEventTypesFromCalcom($company->calcom_api_key);
            
            // Debug: Log response
            Log::info('Cal.com API response', [
                'response' => $response,
                'has_event_types' => isset($response['event_types']),
                'event_types_count' => isset($response['event_types']) ? count($response['event_types']) : 0
            ]);
            
            // Check response format for v2 API
            $eventTypes = [];
            
            // v2 API returns data in a nested structure
            if (isset($response['data']['eventTypeGroups'])) {
                // Extract all event types from all groups
                foreach ($response['data']['eventTypeGroups'] as $group) {
                    if (isset($group['eventTypes']) && is_array($group['eventTypes'])) {
                        $eventTypes = array_merge($eventTypes, $group['eventTypes']);
                    }
                }
            } elseif (isset($response['event_types'])) {
                // Fallback for v1 format
                $eventTypes = $response['event_types'];
            } elseif (isset($response['data']) && is_array($response['data'])) {
                $eventTypes = $response['data'];
            }
            
            Log::info('Extracted event types', [
                'count' => count($eventTypes),
                'first_event' => !empty($eventTypes) ? $eventTypes[0] : null
            ]);
            
            if (empty($eventTypes)) {
                throw new \Exception('Keine Event-Types von Cal.com erhalten. Möglicherweise hat der API-Key keine Berechtigung.');
            }
            
            // Ensure nameParser is initialized
            if (!$this->nameParser) {
                $this->nameParser = app(EventTypeNameParser::class);
            }
            
            // Analysiere Event-Types mit dem Parser
            $this->eventTypesPreview = $this->nameParser->analyzeEventTypesForImport(
                $eventTypes,
                $branch
            );
            
            // Initialisiere Import-Auswahl
            // Bei 'manual' standardmäßig nicht ausgewählt, User muss aktiv auswählen
            foreach ($this->eventTypesPreview as $index => $preview) {
                $this->importSelections[$index] = $preview['suggested_action'] === 'import';
            }
            
            Log::info('Event types preview initialized', [
                'total' => count($this->eventTypesPreview),
                'selections' => $this->importSelections
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error loading event types', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            Notification::make()
                ->title('Fehler beim Laden der Event-Types')
                ->body($e->getMessage())
                ->danger()
                ->send();
                
            $this->currentStep = 1;
        }
    }
    
    /**
     * Bereite Mappings für manuelle Korrektur vor
     */
    private function prepareMappings(): void
    {
        $this->mappings = [];
        
        foreach ($this->eventTypesPreview as $index => $preview) {
            if ($this->importSelections[$index]) {
                $this->mappings[] = [
                    'index' => $index,
                    'original_name' => $preview['original']['title'] ?? $preview['original']['name'],
                    'service_name' => $preview['parsed']['service_name'] ?? $preview['original']['title'],
                    'import' => true,
                    'calcom_id' => $preview['original']['id'],
                    'duration' => $preview['original']['length'] ?? 30,
                ];
            }
        }
    }
    
    /**
     * Erstelle Import-Zusammenfassung
     */
    private function prepareImportSummary(): void
    {
        $branch = Branch::find($this->branch_id);
        
        $this->importSummary = [
            'company' => Company::find($this->company_id)->name,
            'branch' => $branch->name,
            'total_found' => count($this->eventTypesPreview),
            'total_selected' => count(array_filter($this->importSelections)),
            'total_mapped' => count(array_filter($this->mappings, fn($m) => $m['import'] ?? false)),
            'event_types' => []
        ];
        
        foreach ($this->mappings as $mapping) {
            if ($mapping['import'] ?? false) {
                // Ensure nameParser is initialized
                if (!$this->nameParser) {
                    $this->nameParser = app(EventTypeNameParser::class);
                }
                
                $this->importSummary['event_types'][] = [
                    'name' => $this->nameParser->generateEventTypeName($branch, $mapping['service_name']),
                    'service' => $mapping['service_name'],
                    'duration' => $mapping['duration']
                ];
            }
        }
    }
    
    /**
     * Führe den Import durch
     */
    public function executeImport(): void
    {
        DB::beginTransaction();
        
        try {
            $branch = Branch::find($this->branch_id);
            $company = Company::find($this->company_id);
            $imported = 0;
            $errors = [];
            
            // Erstelle Import-Log
            $importLog = DB::table('event_type_import_logs')->insertGetId([
                'company_id' => $this->company_id,
                'branch_id' => $this->branch_id,
                'user_id' => auth()->id(),
                'import_type' => 'manual',
                'total_found' => count($this->eventTypesPreview),
                'total_imported' => 0,
                'status' => 'processing',
                'started_at' => now(),
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            foreach ($this->mappings as $mapping) {
                if (!($mapping['import'] ?? false)) {
                    continue;
                }
                
                try {
                    // Finde den Original Event-Type
                    $originalEventType = null;
                    foreach ($this->eventTypesPreview as $preview) {
                        if ($preview['original']['id'] == $mapping['calcom_id']) {
                            $originalEventType = $preview['original'];
                            break;
                        }
                    }
                    
                    if (!$originalEventType) {
                        throw new \Exception('Original Event-Type nicht gefunden');
                    }
                    
                    // Erstelle oder update Event-Type
                    $eventType = CalcomEventType::updateOrCreate(
                        [
                            'branch_id' => $this->branch_id,
                            'calcom_event_type_id' => $originalEventType['id']
                        ],
                        [
                            'company_id' => $this->company_id,
                            'name' => $this->nameParser ? $this->nameParser->generateEventTypeName($branch, $mapping['service_name']) : "{$branch->name}-{$company->name}-{$mapping['service_name']}",
                            'slug' => Str::slug($mapping['service_name']),
                            'description' => $originalEventType['description'] ?? null,
                            'duration_minutes' => $originalEventType['length'] ?? 30,
                            'calcom_numeric_event_type_id' => $originalEventType['id'],
                            'is_team_event' => $originalEventType['schedulingType'] === 'COLLECTIVE',
                            'requires_confirmation' => $originalEventType['requiresConfirmation'] ?? false,
                            'booking_limits' => $originalEventType['bookingLimits'] ?? null,
                            'metadata' => [
                                'imported_at' => now(),
                                'imported_by' => auth()->id(),
                                'original_name' => $originalEventType['title']
                            ],
                            'is_active' => true,
                            'last_synced_at' => now()
                        ]
                    );
                    
                    $imported++;
                    
                } catch (\Exception $e) {
                    $errors[] = [
                        'event_type' => $mapping['original_name'],
                        'error' => $e->getMessage()
                    ];
                }
            }
            
            // Update Import-Log
            DB::table('event_type_import_logs')
                ->where('id', $importLog)
                ->update([
                    'total_imported' => $imported,
                    'total_errors' => count($errors),
                    'error_details' => json_encode($errors),
                    'status' => count($errors) > 0 ? 'completed' : 'completed',
                    'completed_at' => now(),
                    'updated_at' => now()
                ]);
            
            DB::commit();
            
            Notification::make()
                ->title('Import erfolgreich')
                ->body("{$imported} Event-Types wurden importiert.")
                ->success()
                ->send();
            
            // Zurück zum Anfang
            $this->reset();
            $this->currentStep = 1;
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Notification::make()
                ->title('Import fehlgeschlagen')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    /**
     * Helper: Hole Event-Types von Cal.com
     */
    private function fetchEventTypesFromCalcom($apiKey)
    {
        try {
            Log::info('Fetching event types from Cal.com', [
                'api_key_length' => strlen($apiKey),
                'api_key_prefix' => substr($apiKey, 0, 10) . '...'
            ]);
            
            // Use the v2 API endpoint that actually returns data
            // This works without the cal-api-version header
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->get('https://api.cal.com/v2/event-types');
            
            Log::info('Cal.com API raw response', [
                'status' => $response->status(),
                'successful' => $response->successful(),
                'headers' => $response->headers(),
                'body_preview' => substr($response->body(), 0, 500)
            ]);
            
            if ($response->successful()) {
                $json = $response->json();
                Log::info('Cal.com API parsed response', [
                    'is_array' => is_array($json),
                    'keys' => is_array($json) ? array_keys($json) : null,
                    'event_types_exists' => isset($json['event_types']),
                    'data_exists' => isset($json['data'])
                ]);
                return $json;
            }
            
            // Handle specific error cases
            $errorMessage = 'Cal.com API Fehler';
            $responseBody = $response->body();
            
            if ($response->status() === 403) {
                $errorMessage = 'Autorisierungsfehler: Die Cal.com API-Key ist ungültig oder hat nicht die erforderlichen Berechtigungen.';
            } elseif ($response->status() === 401) {
                $errorMessage = 'Authentifizierungsfehler: Die Cal.com API-Key ist ungültig.';
            } elseif ($response->status() === 404) {
                $errorMessage = 'API-Endpunkt nicht gefunden. Möglicherweise ist die Cal.com API-Version veraltet.';
            } else {
                try {
                    $error = json_decode($responseBody, true);
                    if (isset($error['message'])) {
                        $errorMessage = 'Cal.com Fehler: ' . $error['message'];
                    } elseif (isset($error['error'])) {
                        $errorMessage = 'Cal.com Fehler: ' . $error['error'];
                    }
                } catch (\Exception $e) {
                    // Keep default error message
                }
            }
            
            Log::error('Cal.com API request failed', [
                'status' => $response->status(),
                'body' => $responseBody,
                'error_message' => $errorMessage
            ]);
            
            throw new \Exception($errorMessage);
        } catch (\Exception $e) {
            Log::error('Exception fetching from Cal.com', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }
    
    /**
     * Computed Properties für View
     */
    #[Computed]
    public function canProceed(): bool
    {
        return match($this->currentStep) {
            1 => !empty($this->data['company_id'] ?? null) && !empty($this->data['branch_id'] ?? null),
            2 => count(array_filter($this->importSelections)) > 0,
            3 => count($this->mappings) > 0,
            4 => true,
            default => false
        };
    }
    
    #[Computed]
    public function stepTitle(): string
    {
        return match($this->currentStep) {
            1 => 'Unternehmen & Filiale',
            2 => 'Event-Types Vorschau',
            3 => 'Zuordnungen prüfen',
            4 => 'Import bestätigen',
            default => ''
        };
    }
}