<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Traits\HasLoadingStates;
use App\Helpers\SafeQueryHelper;
use App\Models\Branch;
use App\Models\CalcomEventType;
use App\Models\Company;
use App\Models\Staff;
use App\Services\CalcomSyncService;
use App\Services\EventTypeNameParser;
use App\Services\SmartEventTypeNameParser;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;

class EventTypeImportWizard extends Page implements HasForms
{
    use HasLoadingStates;
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-down-tray';

    protected static ?string $navigationGroup = 'Einrichtung';

    protected static ?string $navigationLabel = 'Event-Type Import';

    protected static ?int $navigationSort = 60;

    protected static string $view = 'filament.admin.pages.event-type-import-wizard';

    protected static ?string $title = 'Event-Type Import Wizard';

    // Wizard Steps
    public int $currentStep = 1;

    public int $totalSteps = 5; // Added staff mapping step

    // Form data
    public ?array $data = [];

    // Step 1: Auswahl
    public ?int $company_id = null;

    public ?string $branch_id = null;

    // Step 2: Preview
    public array $eventTypesPreview = [];

    public array $importSelections = [];

    public string $searchQuery = '';

    public string $filterTeam = 'all';

    // Step 3: Mapping
    public array $mappings = [];

    // Step 4: Staff Mapping (NEU)
    public array $calcomUsers = [];

    public array $staffMappings = [];

    // Step 5: Confirmation
    public array $importSummary = [];

    private ?CalcomSyncService $calcomService = null;

    private ?EventTypeNameParser $nameParser = null;

    private ?SmartEventTypeNameParser $smartNameParser = null;

    public function mount(): void
    {
        // Initialize services first
        try {
            $this->calcomService = app(CalcomSyncService::class);
            $this->nameParser = app(EventTypeNameParser::class);
            $this->smartNameParser = app(SmartEventTypeNameParser::class);
        } catch (\Exception $e) {
            Log::error('Failed to initialize services in EventTypeImportWizard', [
                'error' => $e->getMessage(),
            ]);

            // Create instances directly if service container fails
            $this->calcomService = new CalcomSyncService;
            $this->nameParser = new EventTypeNameParser;
            $this->smartNameParser = new SmartEventTypeNameParser;
        }

        // Initialize form data
        $user = auth()->user();
        $this->data = [
            'company_id' => $user->company_id ?? null,
            'branch_id' => null,
        ];

        // Set company_id if user has one
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
        return match ($this->currentStep) {
            1 => $this->getStep1Schema(),
            2 => $this->getStep2Schema(),
            3 => $this->getStep3Schema(),
            4 => $this->getStep4Schema(),
            5 => $this->getStep5Schema(),
            default => []
        };
    }

    /**
     * Step 1: Company und Branch Auswahl.
     */
    protected function getStep1Schema(): array
    {
        return [
            Section::make('Schritt 1: Unternehmen und Filiale auswählen')
                ->description('Wählen Sie das Unternehmen und die Zielfiliale für den Import aus.')
                ->schema([
                    Select::make('company_id')
                        ->label('Unternehmen')
                        ->options(function () {
                            $user = auth()->user();

                            // Super admins can see all companies with Cal.com API key
                            if ($user->hasRole('super_admin')) {
                                return Company::whereNotNull('calcom_api_key')->pluck('name', 'id');
                            }

                            // Regular users only see their own company if it has an API key
                            if ($user->company_id) {
                                return Company::where('id', $user->company_id)
                                    ->whereNotNull('calcom_api_key')
                                    ->pluck('name', 'id');
                            }

                            return [];
                        })
                        ->required()
                        ->searchable()
                        ->live()
                        ->disabled(fn () => ! auth()->user()?->hasRole('super_admin') && auth()->user()?->company_id !== null)
                        ->helperText(function () {
                            $user = auth()->user();
                            if (! $user?->hasRole('super_admin') && $user?->company_id) {
                                $company = Company::find($user->company_id);
                                if ($company && ! $company->calcom_api_key) {
                                    return 'Ihr Unternehmen hat noch keinen Cal.com API-Key konfiguriert.';
                                }

                                return 'Event-Types werden für Ihr Unternehmen importiert.';
                            }

                            return 'Wählen Sie das Unternehmen aus, für das Event-Types importiert werden sollen.';
                        })
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
                            if (! $companyId) {
                                return [];
                            }

                            try {
                                $branches = Branch::withoutGlobalScopes()
                                    ->where('company_id', $companyId)
                                    ->where('is_active', true)
                                    ->orderBy('name')
                                    ->get()
                                    ->pluck('name', 'id')
                                    ->toArray();

                                return $branches;
                            } catch (\Exception $e) {
                                Log::error('Error loading branches', [
                                    'company_id' => $companyId,
                                    'error' => $e->getMessage(),
                                ]);

                                return [];
                            }
                        })
                        ->required()
                        ->searchable()
                        ->disabled(fn (callable $get) => empty($get('company_id')))
                        ->helperText('Alle importierten Event-Types werden dieser Filiale zugeordnet')
                        ->live()
                        ->afterStateUpdated(function ($state) {
                            $this->branch_id = $state;
                        })
                        ->dehydrated(),
                ]),
        ];
    }

    /**
     * Step 2: Event-Type Preview.
     */
    protected function getStep2Schema(): array
    {
        return [
            Section::make('Schritt 2: Event-Types Vorschau')
                ->description('Überprüfen Sie die gefundenen Event-Types und deren automatische Zuordnung.')
                ->schema([
                    // Dynamisch generierte Preview wird in der View gehandhabt
                ]),
        ];
    }

    /**
     * Step 3: Mapping Korrektur.
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
                        ->disableItemDeletion(),
                ]),
        ];
    }

    /**
     * Step 4: Staff Mapping (NEU).
     */
    protected function getStep4Schema(): array
    {
        return [
            Section::make('Schritt 4: Mitarbeiter-Zuordnung')
                ->description('Ordnen Sie Cal.com Benutzer zu Ihren Mitarbeitern zu.')
                ->schema([
                    Repeater::make('staffMappings')
                        ->label('Mitarbeiter-Zuordnungen')
                        ->schema([
                            TextInput::make('calcom_user_name')
                                ->label('Cal.com Benutzer')
                                ->disabled()
                                ->helperText('Name aus Cal.com'),

                            Select::make('staff_id')
                                ->label('Zugeordneter Mitarbeiter')
                                ->options(function () {
                                    if (! $this->company_id) {
                                        return [];
                                    }

                                    return Staff::where('company_id', $this->company_id)
                                        ->where('active', true)
                                        ->orderBy('name')
                                        ->pluck('name', 'id');
                                })
                                ->searchable()
                                ->placeholder('Kein Mitarbeiter zuordnen')
                                ->helperText('Lassen Sie leer, wenn dieser Cal.com Benutzer keinem Mitarbeiter entspricht'),

                            Toggle::make('create_new')
                                ->label('Neuen Mitarbeiter erstellen')
                                ->reactive()
                                ->helperText('Erstellt automatisch einen neuen Mitarbeiter mit diesem Namen')
                                ->afterStateUpdated(function ($state, callable $set, $get) {
                                    if ($state) {
                                        $set('staff_id', null);
                                    }
                                }),
                        ])
                        ->disableItemCreation()
                        ->disableItemDeletion()
                        ->defaultItems(0),
                ]),
        ];
    }

    /**
     * Step 5: Import Bestätigung.
     */
    protected function getStep5Schema(): array
    {
        return [
            Section::make('Schritt 5: Import-Zusammenfassung')
                ->description('Bestätigen Sie den Import der ausgewählten Event-Types.')
                ->schema([
                    // Zusammenfassung wird in der View angezeigt
                ]),
        ];
    }

    /**
     * Navigations-Methoden.
     */
    public function nextStep(): void
    {
        // Validierung je nach Step
        if ($this->currentStep === 1) {
            // Get form data
            $this->company_id = $this->data['company_id'] ?? null;
            $this->branch_id = $this->data['branch_id'] ?? null;

            if (! $this->company_id || ! $this->branch_id) {
                Notification::make()
                    ->title('Bitte wählen Sie Unternehmen und Filiale aus')
                    ->danger()
                    ->send();

                return;
            }

            // Lade Event-Types von Cal.com
            $this->loadEventTypesPreview();
        } elseif ($this->currentStep === 2) {
            // Bereite Mappings vor
            $this->prepareMappings();
        } elseif ($this->currentStep === 3) {
            // Lade Cal.com Benutzer für Staff Mapping
            $this->loadCalcomUsers();
        } elseif ($this->currentStep === 4) {
            // Erstelle Import-Zusammenfassung
            $this->prepareImportSummary();
        }

        if ($this->currentStep < 5) {
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
     * Toggle all selections.
     */
    public function toggleAllSelections(): void
    {
        $allSelected = count(array_filter($this->importSelections)) === count($this->eventTypesPreview);

        foreach ($this->eventTypesPreview as $index => $preview) {
            // Only toggle if not disabled (skip)
            if ($preview['suggested_action'] !== 'skip') {
                $this->importSelections[$index] = ! $allSelected;
            }
        }
    }

    /**
     * Lade Event-Types von Cal.com und analysiere sie.
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
                'has_api_key' => ! empty($company->calcom_api_key),
                'branch_id' => $this->branch_id,
            ]);

            // Hole Event-Types von Cal.com
            // Decrypt the API key before using it
            $decryptedApiKey = $company->calcom_api_key ? decrypt($company->calcom_api_key) : null;
            if (! $decryptedApiKey) {
                throw new \Exception('Cal.com API-Key fehlt oder konnte nicht entschlüsselt werden.');
            }
            $response = $this->fetchEventTypesFromCalcom($decryptedApiKey);

            // Debug: Log response
            Log::info('Cal.com API response', [
                'response' => $response,
                'has_event_types' => isset($response['event_types']),
                'event_types_count' => isset($response['event_types']) ? count($response['event_types']) : 0,
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
                'first_event' => ! empty($eventTypes) ? $eventTypes[0] : null,
            ]);

            if (empty($eventTypes)) {
                throw new \Exception('Keine Event-Types von Cal.com erhalten. Möglicherweise hat der API-Key keine Berechtigung.');
            }

            // Ensure parsers are initialized
            if (! $this->nameParser) {
                $this->nameParser = app(EventTypeNameParser::class);
            }
            if (! $this->smartNameParser) {
                $this->smartNameParser = app(SmartEventTypeNameParser::class);
            }

            // Analysiere Event-Types mit dem Smart Parser für bessere Namen
            $smartAnalysis = $this->smartNameParser->analyzeEventTypesForImport(
                $eventTypes,
                $branch
            );

            // Fallback auf alten Parser für Kompatibilität
            $oldAnalysis = $this->nameParser->analyzeEventTypesForImport(
                $eventTypes,
                $branch
            );

            // Kombiniere die Ergebnisse - nutze Smart Parser Namen
            $this->eventTypesPreview = [];
            foreach ($smartAnalysis as $index => $smartResult) {
                $oldResult = $oldAnalysis[$index] ?? [];

                // Keep the old analysis but enhance with smart naming
                $mergedResult = $oldResult;
                $mergedResult['original_name'] = $smartResult['original_name'];
                $mergedResult['extracted_service'] = $smartResult['extracted_service'];
                $mergedResult['suggested_name'] = $smartResult['recommended_name'];
                $mergedResult['name_options'] = $smartResult['suggested_names'];

                // Don't override suggested_action if it's already set
                if (! isset($mergedResult['suggested_action'])) {
                    $mergedResult['suggested_action'] = 'import';
                }

                $this->eventTypesPreview[] = $mergedResult;
            }

            // Initialisiere Import-Auswahl - NICHT alle auswählen!
            // Intelligente Vorauswahl basierend auf verschiedenen Kriterien
            foreach ($this->eventTypesPreview as $index => $preview) {
                // Standardmäßig nicht ausgewählt
                $shouldSelect = false;

                // Wähle nur aus wenn:
                // 1. Der Name zur Filiale passt
                if ($preview['matches_branch'] ?? false) {
                    $shouldSelect = true;
                }

                // 2. NICHT wenn es ein Test-Event ist
                $originalName = strtolower($preview['original_name'] ?? '');
                if (strpos($originalName, 'test') !== false ||
                    strpos($originalName, 'demo') !== false ||
                    strpos($originalName, 'example') !== false) {
                    $shouldSelect = false;
                }

                // 3. NICHT wenn es inaktiv ist
                if (! ($preview['original']['active'] ?? true)) {
                    $shouldSelect = false;
                }

                $this->importSelections[$index] = $shouldSelect;
            }

            Log::info('Event types preview initialized', [
                'total' => count($this->eventTypesPreview),
                'selections' => $this->importSelections,
            ]);
        } catch (\Exception $e) {
            Log::error('Error loading event types', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
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
     * Bereite Mappings für manuelle Korrektur vor.
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
     * Lade Cal.com Benutzer für Staff Mapping.
     */
    private function loadCalcomUsers(): void
    {
        try {
            // Extract unique users from selected event types
            $uniqueUsers = [];

            foreach ($this->eventTypesPreview as $index => $preview) {
                if (! $this->importSelections[$index]) {
                    continue;
                }

                // Check for users in the event type
                $eventType = $preview['original'];

                // Team events might have multiple users
                if (isset($eventType['users']) && is_array($eventType['users'])) {
                    foreach ($eventType['users'] as $user) {
                        $userId = $user['id'] ?? null;
                        if ($userId && ! isset($uniqueUsers[$userId])) {
                            $uniqueUsers[$userId] = [
                                'id' => $userId,
                                'name' => $user['name'] ?? $user['username'] ?? 'Unknown User',
                                'email' => $user['email'] ?? null,
                            ];
                        }
                    }
                }

                // Single user events
                if (isset($eventType['user'])) {
                    $user = $eventType['user'];
                    $userId = $user['id'] ?? null;
                    if ($userId && ! isset($uniqueUsers[$userId])) {
                        $uniqueUsers[$userId] = [
                            'id' => $userId,
                            'name' => $user['name'] ?? $user['username'] ?? 'Unknown User',
                            'email' => $user['email'] ?? null,
                        ];
                    }
                }

                // Owner information
                if (isset($eventType['owner'])) {
                    $owner = $eventType['owner'];
                    $ownerId = $owner['id'] ?? null;
                    if ($ownerId && ! isset($uniqueUsers[$ownerId])) {
                        $uniqueUsers[$ownerId] = [
                            'id' => $ownerId,
                            'name' => $owner['name'] ?? $owner['username'] ?? 'Unknown Owner',
                            'email' => $owner['email'] ?? null,
                        ];
                    }
                }
            }

            // Convert to array and prepare staff mappings
            $this->calcomUsers = array_values($uniqueUsers);
            $this->staffMappings = [];

            foreach ($this->calcomUsers as $user) {
                // Try to find existing staff by email or name
                $existingStaff = null;

                if ($user['email']) {
                    $existingStaff = Staff::where('company_id', $this->company_id)
                        ->where('email', $user['email'])
                        ->first();
                }

                if (! $existingStaff && $user['name']) {
                    $existingStaff = Staff::where('company_id', $this->company_id)
                        ->where(function ($q) use ($user) {
                            SafeQueryHelper::whereLike($q, 'name', $user['name']);
                        })
                        ->first();
                }

                $this->staffMappings[] = [
                    'calcom_user_id' => $user['id'],
                    'calcom_user_name' => $user['name'] . ($user['email'] ? ' (' . $user['email'] . ')' : ''),
                    'staff_id' => $existingStaff?->id,
                    'create_new' => ! $existingStaff,
                ];
            }
        } catch (\Exception $e) {
            Log::error('Error loading Cal.com users', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Initialize empty if error
            $this->calcomUsers = [];
            $this->staffMappings = [];
        }
    }

    /**
     * Erstelle Import-Zusammenfassung.
     */
    private function prepareImportSummary(): void
    {
        $branch = Branch::find($this->branch_id);

        $this->importSummary = [
            'company' => Company::find($this->company_id)->name,
            'branch' => $branch->name,
            'total_found' => count($this->eventTypesPreview),
            'total_selected' => count(array_filter($this->importSelections)),
            'total_mapped' => count(array_filter($this->mappings, fn ($m) => $m['import'] ?? false)),
            'event_types' => [],
        ];

        foreach ($this->mappings as $mapping) {
            if ($mapping['import'] ?? false) {
                // Ensure nameParser is initialized
                if (! $this->nameParser) {
                    $this->nameParser = app(EventTypeNameParser::class);
                }

                $this->importSummary['event_types'][] = [
                    'name' => $this->nameParser->generateEventTypeName($branch, $mapping['service_name']),
                    'service' => $mapping['service_name'],
                    'duration' => $mapping['duration'],
                ];
            }
        }
    }

    /**
     * Führe den Import durch.
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
                'updated_at' => now(),
            ]);

            foreach ($this->mappings as $mapping) {
                if (! ($mapping['import'] ?? false)) {
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

                    if (! $originalEventType) {
                        throw new \Exception('Original Event-Type nicht gefunden');
                    }

                    // Erstelle oder update Event-Type
                    $eventType = CalcomEventType::updateOrCreate(
                        [
                            'branch_id' => $this->branch_id,
                            'calcom_numeric_event_type_id' => $originalEventType['id'],
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
                                'original_name' => $originalEventType['title'],
                            ],
                            'is_active' => true,
                            'last_synced_at' => now(),
                        ]
                    );

                    // Handle staff assignments
                    $this->assignStaffToEventType($eventType, $originalEventType);

                    $imported++;
                } catch (\Exception $e) {
                    $errors[] = [
                        'event_type' => $mapping['original_name'],
                        'error' => $e->getMessage(),
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
                    'updated_at' => now(),
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
     * Helper: Hole Event-Types von Cal.com.
     */
    private function fetchEventTypesFromCalcom($apiKey)
    {
        try {
            Log::info('Fetching event types from Cal.com', [
                'api_key_length' => strlen($apiKey),
                'api_key_prefix' => str_starts_with($apiKey, 'cal_') ? 'cal_***' : 'unknown_format',
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
                'headers' => method_exists($response, 'headers') ? $response->headers() : 'no headers method',
                'body_preview' => substr($response->body(), 0, 500),
            ]);

            if ($response->successful()) {
                $json = $response->json();
                Log::info('Cal.com API parsed response', [
                    'is_array' => is_array($json),
                    'keys' => is_array($json) ? array_keys($json) : null,
                    'event_types_exists' => isset($json['event_types']),
                    'data_exists' => isset($json['data']),
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
                'error_message' => $errorMessage,
            ]);

            throw new \Exception($errorMessage);
        } catch (\Exception $e) {
            Log::error('Exception fetching from Cal.com', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return null;
        }
    }

    /**
     * Computed Properties für View.
     */
    #[Computed]
    public function canProceed(): bool
    {
        return match ($this->currentStep) {
            1 => ! empty($this->data['company_id'] ?? null) && ! empty($this->data['branch_id'] ?? null),
            2 => count(array_filter($this->importSelections)) > 0,
            3 => count($this->mappings) > 0,
            4 => true, // Staff mapping is optional
            5 => true,
            default => false
        };
    }

    #[Computed]
    public function stepTitle(): string
    {
        return match ($this->currentStep) {
            1 => 'Unternehmen & Filiale',
            2 => 'Event-Types Vorschau',
            3 => 'Zuordnungen prüfen',
            4 => 'Mitarbeiter zuordnen',
            5 => 'Import bestätigen',
            default => ''
        };
    }

    // Select all event types
    public function selectAll(): void
    {
        foreach ($this->importSelections as $index => $selected) {
            $this->importSelections[$index] = true;
        }
    }

    // Deselect all event types
    public function deselectAll(): void
    {
        foreach ($this->importSelections as $index => $selected) {
            $this->importSelections[$index] = false;
        }
    }

    // Smart selection based on criteria
    public function selectSmart(): void
    {
        foreach ($this->eventTypesPreview as $index => $preview) {
            $shouldSelect = false;

            // Intelligente Auswahl-Logik
            if ($preview['matches_branch'] ?? false) {
                $shouldSelect = true;
            }

            $originalName = strtolower($preview['original_name'] ?? '');
            if (strpos($originalName, 'test') !== false ||
                strpos($originalName, 'demo') !== false) {
                $shouldSelect = false;
            }

            if (! ($preview['original']['active'] ?? true)) {
                $shouldSelect = false;
            }

            $this->importSelections[$index] = $shouldSelect;
        }
    }

    // Get filtered event types based on search and team filter
    public function getFilteredEventTypes(): array
    {
        $filtered = $this->eventTypesPreview;

        // Suchfilter
        if (! empty($this->searchQuery)) {
            $filtered = array_filter($filtered, function ($preview) {
                $searchLower = strtolower($this->searchQuery);
                $inName = strpos(strtolower($preview['original_name'] ?? ''), $searchLower) !== false;
                $inService = strpos(strtolower($preview['extracted_service'] ?? ''), $searchLower) !== false;

                return $inName || $inService;
            });
        }

        // Team-Filter
        if ($this->filterTeam !== 'all') {
            $filtered = array_filter($filtered, function ($preview) {
                $teamId = $preview['original']['team']['id'] ?? 'no-team';

                return $teamId == $this->filterTeam;
            });
        }

        // Preserve array keys
        return $filtered;
    }

    // Get unique teams from event types
    public function getUniqueTeams(): array
    {
        $teams = [];

        foreach ($this->eventTypesPreview as $preview) {
            if (isset($preview['original']['team'])) {
                $team = $preview['original']['team'];
                $teams[$team['id'] ?? 'unknown'] = $team['name'] ?? 'Unbekanntes Team';
            }
        }

        return $teams;
    }

    /**
     * Assign staff to event type based on mappings.
     */
    private function assignStaffToEventType(CalcomEventType $eventType, array $originalEventType): void
    {
        try {
            // Clear existing assignments
            DB::table('staff_event_types')
                ->where('event_type_id', $eventType->id)
                ->delete();

            // Get all Cal.com users associated with this event type
            $calcomUserIds = [];

            if (isset($originalEventType['users']) && is_array($originalEventType['users'])) {
                foreach ($originalEventType['users'] as $user) {
                    if (isset($user['id'])) {
                        $calcomUserIds[] = $user['id'];
                    }
                }
            }

            if (isset($originalEventType['user']['id'])) {
                $calcomUserIds[] = $originalEventType['user']['id'];
            }

            if (isset($originalEventType['owner']['id'])) {
                $calcomUserIds[] = $originalEventType['owner']['id'];
            }

            // Make unique
            $calcomUserIds = array_unique($calcomUserIds);

            // Process each Cal.com user
            foreach ($calcomUserIds as $calcomUserId) {
                // Find the mapping for this Cal.com user
                $mapping = collect($this->staffMappings)->firstWhere('calcom_user_id', $calcomUserId);

                if (! $mapping) {
                    continue;
                }

                $staffId = null;

                // Create new staff if requested
                if ($mapping['create_new'] ?? false) {
                    // Find the Cal.com user info
                    $calcomUser = collect($this->calcomUsers)->firstWhere('id', $calcomUserId);

                    if ($calcomUser) {
                        $staff = Staff::create([
                            'company_id' => $this->company_id,
                            'branch_id' => $this->branch_id,
                            'name' => $calcomUser['name'],
                            'email' => $calcomUser['email'] ?? null,
                            'active' => true,
                            'can_book_appointments' => true,
                            'calcom_user_id' => $calcomUserId,
                        ]);
                        $staffId = $staff->id;
                    }
                } elseif ($mapping['staff_id'] ?? null) {
                    $staffId = $mapping['staff_id'];
                }

                // Create the assignment
                if ($staffId) {
                    DB::table('staff_event_types')->insert([
                        'staff_id' => $staffId,
                        'event_type_id' => $eventType->id,
                        'calcom_user_id' => $calcomUserId,
                        'is_primary' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Error assigning staff to event type', [
                'event_type_id' => $eventType->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
