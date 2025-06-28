<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Form;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use App\Filament\Admin\Traits\HasConsistentNavigation;
use App\Models\Company;
use App\Models\Branch;
use App\Models\PhoneNumber;
use App\Services\CalcomV2Service;
use App\Services\RetellV2Service;
use App\Services\Provisioning\RetellAgentProvisioner;
use App\Services\Setup\SetupOrchestrator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Hidden;
use App\Contracts\HealthReport;
use Illuminate\Support\HtmlString;

class QuickSetupWizard extends Page implements HasForms
{
    use InteractsWithForms;
    use HasConsistentNavigation;
    
    protected static ?string $navigationIcon = 'heroicon-o-rocket-launch';
    protected static ?string $navigationLabel = 'Schnell-Setup';
    protected static ?string $title = 'Schnell-Setup Assistent';
    protected static ?string $navigationGroup = 'Einrichtung';
    protected static ?int $navigationSort = 55;
    
    protected static string $view = 'filament.admin.pages.quick-setup-wizard';
    
    // Form data
    public array $data = [];
    
    // Wizard state
    public int $currentStep = 1;
    
    // Company instance (set after step 1)
    protected ?Company $company = null;
    
    // Edit mode properties
    public bool $editMode = false;
    public ?Company $editingCompany = null;
    public ?Branch $editingBranch = null;
    
    // Progress tracking
    protected string $currentProgressMessage = 'Setup wird vorbereitet...';
    protected int $currentProgressPercentage = 0;
    
    // Setup completion flag
    protected bool $setupComplete = false;
    
    // Track setup duration
    protected ?\Carbon\Carbon $started_at = null;
    
    // Industry Templates
    protected array $industryTemplates = [
        'medical' => [
            'name' => '🏥 Medizin & Gesundheit',
            'appointment_duration' => 30,
            'buffer_time' => 10,
            'reminder_hours' => 24,
            'working_hours' => ['09:00-18:00'],
            'services' => [
                'Erstberatung',
                'Behandlung',
                'Nachuntersuchung',
                'Notfalltermin'
            ],
            'greeting' => 'Guten Tag, Sie rufen in der Praxis [FIRMA] an. Wie kann ich Ihnen helfen?'
        ],
        'beauty' => [
            'name' => '💇 Beauty & Wellness',
            'appointment_duration' => 60,
            'buffer_time' => 15,
            'reminder_hours' => 48,
            'working_hours' => ['10:00-19:00'],
            'services' => [
                'Haarschnitt',
                'Färben',
                'Maniküre',
                'Massage'
            ],
            'greeting' => 'Willkommen bei [FIRMA]! Schön, dass Sie anrufen. Möchten Sie einen Termin vereinbaren?'
        ],
        'handwerk' => [
            'name' => '🔧 Handwerk & Service',
            'appointment_duration' => 120,
            'buffer_time' => 30,
            'reminder_hours' => 72,
            'working_hours' => ['08:00-17:00'],
            'services' => [
                'Kostenvoranschlag',
                'Reparatur',
                'Installation',
                'Wartung'
            ],
            'greeting' => 'Guten Tag, [FIRMA] am Apparat. Wie darf ich Ihnen behilflich sein?'
        ],
        'legal' => [
            'name' => '⚖️ Recht & Beratung',
            'appointment_duration' => 45,
            'buffer_time' => 15,
            'reminder_hours' => 24,
            'working_hours' => ['09:00-17:00'],
            'services' => [
                'Erstberatung',
                'Vertragsberatung',
                'Rechtsberatung',
                'Mediation'
            ],
            'greeting' => 'Kanzlei [FIRMA], guten Tag. Wie kann ich Ihnen weiterhelfen?'
        ]
    ];
    
    public function mount(): void
    {
        // Track when setup started
        $this->started_at = now();
        
        // Check if we should enter edit mode
        $companyId = request()->query('company');
        if ($companyId) {
            $this->loadCompanyForEditing($companyId);
        } else {
            // Check for existing companies to offer edit mode
            $this->checkForExistingCompanies();
        }
        
        $this->form->fill($this->data);
    }
    
    protected function checkForExistingCompanies(): void
    {
        $companies = Company::all();
        
        if ($companies->isNotEmpty()) {
            // Offer choice between new or edit
            $this->data['mode_selection'] = true;
            $this->data['existing_companies'] = $companies->pluck('name', 'id')->toArray();
        }
    }
    
    protected function loadCompanyForEditing(int $companyId): void
    {
        $this->editingCompany = Company::with(['branches', 'services', 'staff'])->find($companyId);
        
        if (!$this->editingCompany) {
            Notification::make()
                ->title('Firma nicht gefunden')
                ->danger()
                ->send();
            return;
        }
        
        $this->editMode = true;
        $this->editingBranch = $this->editingCompany->branches()->first();
        
        // Load existing data
        $this->data = [
            'company_id' => $this->editingCompany->id,
            'company_name' => $this->editingCompany->name,
            'industry' => $this->editingCompany->industry,
            'logo' => $this->editingCompany->logo,
            'calcom_api_key' => '', // Don't load the encrypted key into the form
            'calcom_team_slug' => $this->editingCompany->calcom_team_slug,
            'calcom_connection_type' => $this->editingCompany->settings['calcom_connection_type'] ?? 'api_key',
            'retell_api_key' => '', // Don't load the encrypted key into the form
            'retell_agent_id' => $this->editingCompany->retell_agent_id,
            'edit_mode' => true,
        ];
        
        // Load all branches into repeater format
        $this->data['branches'] = $this->editingCompany->branches->map(function($branch) {
            // Debug logging
            Log::info('Loading branch features', [
                'branch_id' => $branch->id,
                'features_raw' => $branch->features,
                'features_parsed' => $branch->features ?? []
            ]);
            
            return [
                'id' => $branch->id,
                'name' => $branch->name,
                'city' => $branch->city,
                'address' => $branch->address,
                'phone_number' => $branch->phone_number,
                'features' => $branch->features ?? [],
            ];
        })->toArray();
        
        // Load phone configuration
        $this->loadPhoneConfiguration();
        
        // Load services
        $this->loadServices();
        
        // Load staff
        $this->loadStaff();
        
        // Load other settings
        $this->loadAdditionalSettings();
    }
    
    protected function loadPhoneConfiguration(): void
    {
        if (!$this->editingCompany || !$this->editingBranch) return;
        
        // First check if branch has a phone number in its own field
        if (!empty($this->editingBranch->phone_number)) {
            $this->data['branch_phone'] = $this->editingBranch->phone_number;
        } else {
            // If not, check the phone_numbers table
            $directNumber = PhoneNumber::where('company_id', $this->editingCompany->id)
                ->where('branch_id', $this->editingBranch->id)
                ->where('type', 'direct')
                ->first();
            
            if ($directNumber) {
                $this->data['branch_phone'] = $directNumber->number;
            }
        }
        
        // Load phone numbers for all branches
        $branchPhoneNumbers = [];
        foreach ($this->editingCompany->branches as $branch) {
            $phoneNumber = PhoneNumber::where('company_id', $this->editingCompany->id)
                ->where('branch_id', $branch->id)
                ->where('type', 'direct')
                ->first();
                
            $branchPhoneNumbers[] = [
                'branch_id' => $branch->id,
                'branch_name' => $branch->name,
                'number' => $phoneNumber?->number ?? $branch->phone_number ?? '',
                'is_primary' => $phoneNumber?->is_primary ?? true,
                'sms_enabled' => $phoneNumber?->sms_enabled ?? false,
                'whatsapp_enabled' => $phoneNumber?->whatsapp_enabled ?? false,
            ];
        }
        
        $this->data['branch_phone_numbers'] = $branchPhoneNumbers;
        
        // Get hotline from company level (no specific branch)
        $hotline = PhoneNumber::where('company_id', $this->editingCompany->id)
            ->whereNull('branch_id')
            ->where('type', 'hotline')
            ->first();
        
        $this->data['use_hotline'] = $hotline !== null;
        $this->data['hotline_number'] = $hotline?->number;
        $this->data['routing_strategy'] = $hotline?->routing_config['strategy'] ?? 'voice_menu';
        
        // Determine phone strategy based on existing configuration
        if ($hotline && !empty($branchPhoneNumbers)) {
            $this->data['phone_strategy'] = 'mixed';
        } elseif ($hotline) {
            $this->data['phone_strategy'] = 'hotline';
        } else {
            $this->data['phone_strategy'] = 'direct';
        }
    }
    
    protected function loadServices(): void
    {
        if (!$this->editingCompany) return;
        
        $services = $this->editingCompany->services()->get();
        $this->data['services'] = $services->map(fn($s) => [
            'id' => $s->id,
            'name' => $s->name,
            'duration' => $s->duration,
            'buffer_time' => $s->buffer_time,
        ])->toArray();
        $this->data['use_template_services'] = false; // Don't use templates when editing
    }
    
    protected function loadStaff(): void
    {
        if (!$this->editingCompany) return;
        
        $staff = $this->editingCompany->staff()->get();
        $this->data['staff_members'] = $staff->map(fn($s) => [
            'id' => $s->id,
            'name' => $s->name,
            'email' => $s->email,
            'phone' => $s->phone,
            'languages' => $s->languages ?? [],
            'skills' => $s->skills ?? [],
            'experience_level' => $s->experience_level ?? 2,
            'certifications' => $s->certifications ?? [],
            'branch_id' => $s->branch_id,
        ])->toArray();
    }
    
    protected function loadAdditionalSettings(): void
    {
        if (!$this->editingCompany) return;
        
        $settings = $this->editingCompany->settings ?? [];
        
        // KI-Telefon Settings
        $this->data['phone_setup'] = $settings['phone_setup'] ?? 'new';
        $this->data['ai_voice'] = $settings['ai_voice'] ?? 'sarah';
        $this->data['use_template_greeting'] = $settings['use_template_greeting'] ?? true;
        $this->data['custom_greeting'] = $settings['custom_greeting'] ?? '';
        $this->data['enable_test_call'] = $settings['enable_test_call'] ?? false;
        $this->data['create_new_agent'] = $settings['create_new_agent'] ?? false;
        
        // Additional communication settings
        $this->data['global_sms_enabled'] = $settings['global_sms_enabled'] ?? false;
        $this->data['global_whatsapp_enabled'] = $settings['global_whatsapp_enabled'] ?? false;
        
        // Import settings
        $this->data['import_event_types'] = $settings['import_event_types'] ?? true;
        
        // Working hours settings
        $this->data['use_template_hours'] = $settings['use_template_hours'] ?? true;
        
        // Staff members
        if (!empty($settings['staff_members'])) {
            $this->data['staff_members'] = $settings['staff_members'];
        }
        
        // Post setup actions
        $this->data['post_setup_actions'] = $settings['post_setup_actions'] ?? [];
        
        // Retell Agent ID (loaded from company, not settings)
        if ($this->editingCompany->retell_agent_id) {
            $this->data['retell_agent_id'] = $this->editingCompany->retell_agent_id;
        }
    }
    
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Wizard::make([
                    // Step 0: Mode Selection (if existing companies)
                    ...$this->getModeSelectionStep(),
                    
                    // Step 1: Firma & Branche
                    Wizard\Step::make($this->editMode ? 'Firma bearbeiten' : 'Firma anlegen')
                        ->description('Grundlegende Informationen (30 Sekunden)')
                        ->icon('heroicon-o-building-office')
                        ->afterValidation(function () {
                            $this->saveStep1Data();
                        })
                        ->schema([
                            Section::make('Firmendaten')
                                ->schema([
                                    TextInput::make('company_name')
                                        ->label('Firmenname')
                                        ->required()
                                        ->placeholder('z.B. Zahnarztpraxis Dr. Schmidt')
                                        ->maxLength(255),
                                        
                                    Select::make('industry')
                                        ->label('Branche')
                                        ->options(collect($this->industryTemplates)->mapWithKeys(fn($t, $k) => [$k => $t['name']]))
                                        ->required()
                                        ->reactive()
                                        ->afterStateUpdated(fn($state) => $this->updateIndustryDefaults($state)),
                                        
                                    FileUpload::make('logo')
                                        ->label('Logo (optional)')
                                        ->image()
                                        ->directory('logos')
                                        ->maxSize(2048),
                                ]),
                                
                            Section::make('Filialen')
                                ->description('Sie können eine oder mehrere Filialen anlegen')
                                ->schema([
                                    Repeater::make('branches')
                                        ->label('Filialen')
                                        ->schema([
                                            Hidden::make('id'),
                                            Grid::make(2)
                                                ->schema([
                                                    TextInput::make('name')
                                                        ->label('Filialname')
                                                        ->required()
                                                        ->placeholder('z.B. Hauptfiliale'),
                                                    
                                                    TextInput::make('city')
                                                        ->label('Stadt')
                                                        ->required()
                                                        ->placeholder('z.B. Berlin'),
                                                ]),
                                                
                                            TextInput::make('address')
                                                ->label('Adresse')
                                                ->placeholder('Straße und Hausnummer'),
                                                
                                            TextInput::make('phone_number')
                                                ->label('Telefonnummer')
                                                ->tel()
                                                ->placeholder('+49 30 12345678')
                                                ->helperText('Diese Nummer wird für eingehende Anrufe verwendet')
                                                ->rules(['regex:/^\+49\s?[0-9\s\-\/\(\)]{5,20}$/'])
                                                ->validationAttribute('Telefonnummer'),
                                            
                                            CheckboxList::make('features')
                                                ->label('Ausstattung')
                                                ->options([
                                                    'parking' => '🚗 Parkplätze vorhanden',
                                                    'wheelchair' => '♿ Barrierefrei',
                                                    'public_transport' => '🚇 Gute ÖPNV-Anbindung',
                                                    'wifi' => '📶 WLAN für Kunden',
                                                ])
                                                ->columns(2),
                                        ])
                                        ->defaultItems(1)
                                        ->addActionLabel('Weitere Filiale hinzufügen')
                                        ->collapsible()
                                        ->collapsed(false)
                                        ->itemLabel(fn (array $state): ?string => 
                                            $state['name'] ?? 'Neue Filiale'
                                        )
                                        ->grid(1)
                                        ->reactive()
                                        ->afterStateUpdated(function ($state, $set) {
                                            // Update branch_phone_numbers when branches change
                                            if (is_array($state)) {
                                                $phoneNumbers = [];
                                                foreach ($state as $index => $branch) {
                                                    if (!empty($branch['name'])) {
                                                        $phoneNumbers[] = [
                                                            'branch_id' => $index,
                                                            'branch_name' => $branch['name'],
                                                            'number' => '',
                                                            'is_primary' => true,
                                                            'sms_enabled' => false,
                                                            'whatsapp_enabled' => false,
                                                        ];
                                                    }
                                                }
                                                $set('branch_phone_numbers', $phoneNumbers);
                                            }
                                        }),
                                ]),
                        ]),
                        
                    // Step 2: Phone Configuration (ENHANCED)
                    Wizard\Step::make('Telefonnummern einrichten')
                        ->description('Intelligente Telefon-Routing Konfiguration')
                        ->icon('heroicon-o-phone')
                        ->afterValidation(function () {
                            $this->saveStep2Data();
                        })
                        ->schema($this->getEnhancedPhoneConfigurationFields()),
                        
                        /*
                        OLD SCHEMA - replaced by enhanced version
                        ->schema([
                            Section::make('Telefon-Setup')
                                ->schema([
                                    Toggle::make('use_hotline')
                                        ->label('Zentrale Hotline verwenden?')
                                        ->helperText('Empfohlen bei mehreren Standorten')
                                        ->default(false)
                                        ->reactive()
                                        ->afterStateUpdated(function ($state, $set) {
                                            if (!$state) {
                                                $set('routing_strategy', null);
                                            }
                                        }),
                                    
                                    Grid::make(2)
                                        ->schema([
                                            TextInput::make('hotline_number')
                                                ->label('Hotline Nummer')
                                                ->tel()
                                                ->placeholder('+49 30 11223344')
                                                ->visible(fn($get) => $get('use_hotline'))
                                                ->required(fn($get) => $get('use_hotline')),
                                            
                                            Select::make('routing_strategy')
                                                ->label('Routing Strategie')
                                                ->options([
                                                    'voice_menu' => '🗣️ Sprachauswahl (Kunde nennt Filialname)',
                                                    'business_hours' => '🕐 Nach Öffnungszeiten',
                                                    'load_balanced' => '⚖️ Gleichmäßige Verteilung'
                                                ])
                                                ->default('voice_menu')
                                                ->visible(fn($get) => $get('use_hotline'))
                                                ->required(fn($get) => $get('use_hotline'))
                                                ->helperText('Wie sollen Anrufe verteilt werden?'),
                                        ]),
                                    
                                    Placeholder::make('direct_info')
                                        ->content('Die Filialnummer haben Sie bereits angegeben. Diese wird automatisch zugeordnet.')
                                        ->visible(fn($get) => !$get('use_hotline')),
                                ]),
                            
                            Section::make('Erweiterte Einstellungen')
                                ->collapsed()
                                ->schema([
                                    Toggle::make('enable_sms')
                                        ->label('SMS-Benachrichtigungen aktivieren')
                                        ->default(false)
                                        ->helperText('Termine per SMS bestätigen'),
                                    
                                    Toggle::make('enable_whatsapp')
                                        ->label('WhatsApp Integration')
                                        ->default(false)
                                        ->helperText('Termine über WhatsApp verwalten'),
                                ]),
                        ]),
                        */
                        
                    // Step 3: Cal.com Verbindung
                    Wizard\Step::make('Kalender verbinden')
                        ->description('Cal.com Integration (60 Sekunden)')
                        ->icon('heroicon-o-calendar')
                        ->afterValidation(function () {
                            $this->saveStep3Data();
                        })
                        ->schema([
                            Placeholder::make('calcom_info')
                                ->content('Verbinden Sie Ihren Cal.com Account für die Terminverwaltung.'),
                                
                            Select::make('calcom_connection_type')
                                ->label('Verbindungsart')
                                ->options([
                                    'oauth' => '🔐 OAuth (Empfohlen)',
                                    'api_key' => '🔑 API Key'
                                ])
                                ->default('oauth')
                                ->reactive(),
                                
                            TextInput::make('calcom_api_key')
                                ->label('Cal.com API Key')
                                ->password()
                                ->autocomplete('new-password')
                                ->visible(fn($get) => $get('calcom_connection_type') === 'api_key')
                                ->helperText(fn() => $this->editMode && $this->editingCompany && $this->editingCompany->calcom_api_key 
                                    ? 'API Key ist gespeichert. Leer lassen um den bestehenden Key zu behalten.' 
                                    : 'Finden Sie unter cal.com/settings/developer/api-keys')
                                ->placeholder(fn() => $this->editMode && $this->editingCompany && $this->editingCompany->calcom_api_key 
                                    ? '••••••••••••••••' 
                                    : '')
                                ->dehydrated(fn($state) => !empty($state)),
                                
                            TextInput::make('calcom_team_slug')
                                ->label('Team Slug (optional)')
                                ->placeholder('mein-team')
                                ->helperText('Für Team-Kalender'),
                                
                            Toggle::make('import_event_types')
                                ->label('Event Types automatisch importieren')
                                ->default(true)
                                ->helperText('Importiert alle verfügbaren Termintypen'),
                        ]),
                        
                    // Step 4: KI-Telefon aktivieren
                    Wizard\Step::make('KI-Telefon einrichten')
                        ->description('Retell.ai Agent (60 Sekunden)')
                        ->icon('heroicon-o-microphone')
                        ->afterValidation(function () {
                            $this->saveStep4Data();
                        })
                        ->schema([
                            Placeholder::make('retell_info')
                                ->content('Ihr KI-Assistent beantwortet Anrufe und bucht Termine.'),
                                
                            Section::make('Retell.ai Konfiguration')
                                ->schema([
                                    TextInput::make('retell_api_key')
                                        ->label('Retell.ai API Key')
                                        ->password()
                                        ->autocomplete('new-password')
                                        ->helperText(fn() => $this->editMode && $this->editingCompany && $this->editingCompany->retell_api_key 
                                            ? 'API Key ist gespeichert. Leer lassen um den bestehenden Key zu behalten.' 
                                            : 'Finden Sie unter dashboard.retellai.com/api-keys')
                                        ->placeholder(fn() => $this->editMode && $this->editingCompany && $this->editingCompany->retell_api_key 
                                            ? '••••••••••••••••' 
                                            : '')
                                        ->dehydrated(fn($state) => !empty($state)),
                                    
                                    TextInput::make('retell_agent_id')
                                        ->label('Retell Agent ID')
                                        ->helperText('Ihre Agent ID von Retell.ai (z.B. agent_xxxxx)')
                                        ->placeholder('agent_xxxxx'),
                                        
                                    Toggle::make('create_new_agent')
                                        ->label('Neuen Agent automatisch erstellen')
                                        ->default(false)
                                        ->helperText('Erstellt automatisch einen neuen Agent mit optimalen Einstellungen')
                                        ->reactive()
                                        ->afterStateUpdated(function ($state, $set) {
                                            if ($state) {
                                                $set('retell_agent_id', '');
                                            }
                                        }),
                                ]),
                                
                            Select::make('phone_setup')
                                ->label('Telefonnummer')
                                ->options([
                                    'new' => '📱 Neue Nummer von Retell.ai',
                                    'existing' => '☎️ Bestehende Nummer portieren'
                                ])
                                ->default('new')
                                ->reactive(),
                                
                            Select::make('ai_voice')
                                ->label('KI-Stimme')
                                ->options([
                                    'sarah' => '👩 Sarah (Freundlich, Weiblich)',
                                    'matt' => '👨 Matt (Professionell, Männlich)',
                                    'custom' => '🎙️ Eigene Aufnahme'
                                ])
                                ->default('sarah'),
                                
                            Toggle::make('use_template_greeting')
                                ->label('Branchen-Vorlage für Begrüßung verwenden')
                                ->default(true)
                                ->reactive(),
                                
                            TextInput::make('custom_greeting')
                                ->label('Begrüßungstext')
                                ->visible(fn($get) => !$get('use_template_greeting'))
                                ->placeholder('Guten Tag, hier ist...'),
                                
                            Toggle::make('enable_test_call')
                                ->label('Test-Anruf nach Einrichtung')
                                ->default(true)
                                ->helperText('Empfohlen: Testen Sie Ihren KI-Assistenten'),
                        ]),
                        
                    // Step 5: Integration Check (NEW)
                    Wizard\Step::make('Integration überprüfen')
                        ->description('Live-Verbindungstest')
                        ->icon('heroicon-o-wifi')
                        ->schema($this->getIntegrationCheckFields()),
                        
                    // Step 6: Services & Mitarbeiter (ENHANCED)
                    Wizard\Step::make('Services & Mitarbeiter')
                        ->description('Team & Dienstleistungen konfigurieren')
                        ->icon('heroicon-o-user-group')
                        ->afterValidation(function () {
                            $this->saveStep6Data();
                        })
                        ->schema($this->getEnhancedStaffAndServicesFields()),
                        
                    // Step 7: Review & Health Check (NEW)
                    Wizard\Step::make('Überprüfung & Fertigstellung')
                        ->description('System-Status überprüfen')
                        ->icon('heroicon-o-shield-check')
                        ->schema($this->getReviewAndHealthCheckFields()),
                ])
                ->submitAction(
                    Action::make('complete_setup')
                        ->label($this->editMode ? '💾 Änderungen speichern' : '🚀 Setup abschließen')
                        ->action('completeSetup')
                        ->disabled(fn() => !$this->canCompleteSetup())
                ),
            ])
            ->statePath('data');
    }
    
    protected function updateIndustryDefaults(string $industry): void
    {
        if (!isset($this->industryTemplates[$industry])) {
            return;
        }
        
        $template = $this->industryTemplates[$industry];
        
        // Update greeting if using template
        if ($this->data['use_template_greeting'] ?? true) {
            $this->data['custom_greeting'] = str_replace(
                '[FIRMA]', 
                $this->data['company_name'] ?? 'Ihre Firma',
                $template['greeting']
            );
        }
    }
    
    protected function getTemplateInfo(?string $industry): string
    {
        if (!$industry || !isset($this->industryTemplates[$industry])) {
            return 'Wählen Sie eine Branche für automatische Voreinstellungen.';
        }
        
        $template = $this->industryTemplates[$industry];
        
        return sprintf(
            "**Ihre Branchen-Vorlage:**\n- Termin-Dauer: %d Min\n- Pufferzeit: %d Min\n- Services: %s\n- Öffnungszeiten: %s",
            $template['appointment_duration'],
            $template['buffer_time'],
            implode(', ', array_slice($template['services'], 0, 3)) . '...',
            $template['working_hours'][0]
        );
    }
    
    protected function getSummaryFromGet($get): string
    {
        $companyName = $get('company_name') ?? 'Nicht angegeben';
        $industry = $get('industry');
        $industryName = isset($this->industryTemplates[$industry]) ? $this->industryTemplates[$industry]['name'] : 'Nicht gewählt';
        $branchName = $get('branch_name') ?? 'Hauptfiliale';
        $branchCity = $get('branch_city') ?? 'Stadt';
        $connectionType = $get('calcom_connection_type') ?? 'api_key';
        $aiVoice = $get('ai_voice') ?? 'sarah';
        $enableTestCall = $get('enable_test_call') ?? true;
        
        $lines = [
            "✅ **Firma:** " . $companyName,
            "✅ **Branche:** " . $industryName,
            "✅ **Filiale:** " . $branchName . " in " . $branchCity,
            "✅ **Kalender:** " . ($connectionType === 'oauth' ? 'OAuth verbunden' : 'API Key konfiguriert'),
            "✅ **KI-Stimme:** " . ucfirst($aiVoice),
            "✅ **Test-Anruf:** " . ($enableTestCall ? 'Aktiviert' : 'Übersprungen'),
        ];
        
        return implode("\n", $lines);
    }
    
    /**
     * Test Cal.com connection
     */
    public function testCalcomConnection(): array
    {
        try {
            $apiKey = $this->data['calcom_api_key'] ?? null;
            
            // If in edit mode and no new API key provided, use the existing one
            if ($this->editMode && $this->editingCompany && (empty($apiKey) || $apiKey === '[ENCRYPTED]')) {
                $apiKey = $this->editingCompany->calcom_api_key ? decrypt($this->editingCompany->calcom_api_key) : null;
            }
            
            if (!$apiKey) {
                return [
                    'success' => false,
                    'error' => 'Kein API Key vorhanden. Bitte geben Sie Ihren Cal.com API Key ein.'
                ];
            }
            
            // Test with CalcomV2Service - instantiate with API key
            $calcomService = new \App\Services\CalcomV2Service($apiKey);
            
            // Try to fetch user info as a test
            $response = $calcomService->getMe();
            
            if ($response['success'] && isset($response['data'])) {
                $userData = $response['data']['data'] ?? $response['data'];
                return [
                    'success' => true,
                    'user' => [
                        'name' => $userData['name'] ?? $userData['username'] ?? 'Unknown',
                        'email' => $userData['email'] ?? 'Unknown'
                    ]
                ];
            }
            
            // Check if it's an authentication error
            if (isset($response['error']) && strpos($response['error'], 'Invalid API Key') !== false) {
                return [
                    'success' => false,
                    'error' => 'Ungültiger API Key. Bitte überprüfen Sie Ihren Cal.com API Key.'
                ];
            }
            
            return [
                'success' => false,
                'error' => 'Verbindung fehlgeschlagen: ' . ($response['error'] ?? 'Unbekannter Fehler')
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Fehler: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Test Retell.ai connection
     */
    public function testRetellConnection(): array
    {
        try {
            // Use API key from form data or existing company
            $apiKey = $this->data['retell_api_key'] ?? null;
            
            // If in edit mode and no new API key provided, use the existing one
            if ($this->editMode && $this->editingCompany && (empty($apiKey) || $apiKey === '')) {
                $apiKey = $this->editingCompany->retell_api_key ? decrypt($this->editingCompany->retell_api_key) : null;
            }
            
            if (!$apiKey) {
                return [
                    'success' => false,
                    'error' => 'Kein Retell.ai API Key konfiguriert.'
                ];
            }
            
            // Get the configured agent ID
            $agentId = $this->data['retell_agent_id'] ?? $this->editingCompany->retell_agent_id ?? null;
            
            $retellService = new \App\Services\RetellService($apiKey);
            
            // Clear cache for fresh test
            Cache::forget('retell_agents');
            Cache::forget('retell_agent_' . $agentId);
            
            // If we have a specific agent ID, try to get it directly
            $specificAgent = null;
            if ($agentId) {
                try {
                    $specificAgent = $retellService->getAgent($agentId);
                    Log::info('Retell API - Specific Agent Test', [
                        'agent_id' => $agentId,
                        'agent_found' => !empty($specificAgent)
                    ]);
                } catch (\Exception $e) {
                    Log::error('Error fetching specific agent', [
                        'agent_id' => $agentId,
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            // Try to get all agents as a test
            $agents = $retellService->getAgents();
            
            // Debug logging
            Log::info('Retell API Test', [
                'agent_id_configured' => $agentId,
                'agents_response' => $agents,
                'is_array' => is_array($agents),
                'specific_agent' => $specificAgent
            ]);
            
            // Check if we got a valid response (even if empty)
            if (is_array($agents) || $specificAgent) {
                // Look for the configured agent
                $configuredAgent = null;
                
                // First check if we found it via direct API call
                if ($specificAgent && !empty($specificAgent)) {
                    $configuredAgent = $specificAgent;
                } elseif ($agentId && is_array($agents)) {
                    // Otherwise look in the agents list
                    foreach ($agents as $agent) {
                        if (($agent['agent_id'] ?? '') === $agentId) {
                            $configuredAgent = $agent;
                            break;
                        }
                    }
                }
                
                // If we have a configured agent, show it
                if ($configuredAgent) {
                    return [
                        'success' => true,
                        'agent' => [
                            'agent_name' => $configuredAgent['agent_name'] ?? 'Configured Agent',
                            'voice_id' => $configuredAgent['voice_id'] ?? 'sarah',
                            'agent_count' => count($agents),
                            'agent_id' => $configuredAgent['agent_id'] ?? $agentId
                        ]
                    ];
                }
                
                // If we have agents but none match the configured ID
                if (!empty($agents) && isset($agents[0])) {
                    $firstAgent = $agents[0];
                    
                    return [
                        'success' => true,
                        'agent' => [
                            'agent_name' => $agentId ? "Agent ID nicht gefunden: $agentId" : $firstAgent['agent_name'] ?? 'Configured Agent',
                            'voice_id' => $firstAgent['voice_id'] ?? 'sarah',
                            'agent_count' => count($agents)
                        ]
                    ];
                }
                
                // No agents found but agent ID is configured
                if (empty($agents) && $agentId) {
                    return [
                        'success' => true,
                        'agent' => [
                            'agent_name' => 'Agent konfiguriert (nicht in Liste)',
                            'agent_id' => $agentId,
                            'agent_count' => 0
                        ]
                    ];
                }
                
                // No agents configured yet, but connection works
                return [
                    'success' => true,
                    'agent' => [
                        'agent_name' => 'Noch kein Agent konfiguriert',
                        'voice_id' => 'Wird automatisch erstellt',
                        'agent_count' => 0
                    ]
                ];
            }
            
            // If response is not an array, something went wrong
            return [
                'success' => false,
                'error' => 'Ungültige Antwort von Retell.ai API'
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Fehler: ' . $e->getMessage()
            ];
        }
    }
    
    public function completeSetup(): void
    {
        try {
            // Use the new SetupOrchestrator for cleaner code
            $orchestrator = new SetupOrchestrator();
            
            // Set progress callback
            $orchestrator->onProgress(function (int $percentage, string $message) {
                $this->updateProgress($message, $percentage);
            });
            
            // Execute setup
            $result = $orchestrator->execute($this->data);
            
            if ($result['success']) {
                $this->setupComplete = true;
                
                Notification::make()
                    ->title('✅ Setup erfolgreich abgeschlossen!')
                    ->body('Ihr KI-Telefon-System ist jetzt einsatzbereit.')
                    ->success()
                    ->send();
                
                // Redirect to dashboard
                $this->redirect('/admin');
            }
            
        } catch (\Exception $e) {
            $this->handleSetupError($e);
        }
    }
    
    // Legacy completeSetup method for backwards compatibility
    public function completeSetupLegacy(): void
    {
        try {
            DB::beginTransaction();
            
            $this->updateProgress('Setup wird gestartet...', 10);
            
            // Handle both create and update modes
            if ($this->editMode && $this->editingCompany) {
                // Update existing company
                $company = $this->editingCompany;
                $company->update([
                    'name' => $this->data['company_name'],
                    'industry' => $this->data['industry'],
                    'settings' => array_merge($company->settings ?? [], [
                        'wizard_completed' => true,
                        'last_update_date' => now(),
                        'template_used' => $this->data['industry']
                    ])
                ]);
            } else {
                // Create new company
                $company = Company::create([
                    'name' => $this->data['company_name'],
                    'industry' => $this->data['industry'],
                    'settings' => [
                        'wizard_completed' => true,
                        'setup_date' => now(),
                        'template_used' => $this->data['industry']
                    ]
                ]);
            }
            
            $this->updateProgress('Filialen werden eingerichtet...', 20);
            
            // 2. Handle Branches (Multiple)
            $branches = [];
            $firstBranch = null;
            
            if ($this->editMode && $this->editingBranch) {
                // In edit mode, update the existing branch
                // For now, we only support editing the first branch
                $branch = $this->editingBranch;
                
                // Get branch data from repeater or fall back to old format
                $branchData = $this->data['branches'][0] ?? [
                    'name' => $this->data['branch_name'] ?? $branch->name,
                    'city' => $this->data['branch_city'] ?? $branch->city,
                    'address' => $this->data['branch_address'] ?? $branch->address,
                    'phone_number' => $this->data['branch_phone'] ?? $branch->phone_number,
                    'features' => $this->data['branch_features'] ?? $branch->features,
                ];
                
                $branch->update([
                    'name' => $branchData['name'],
                    'city' => $branchData['city'],
                    'address' => $branchData['address'] ?? null,
                    'phone_number' => $branchData['phone_number'] ?? null,
                    'is_active' => true,
                    'business_hours' => $this->getBusinessHours(),
                    'features' => $branchData['features'] ?? [],
                    'settings' => array_merge($branch->settings ?? [], [
                        'enable_sms' => $this->data['enable_sms'] ?? false,
                        'enable_whatsapp' => $this->data['enable_whatsapp'] ?? false,
                    ]),
                ]);
                
                $branches[] = $branch;
                $firstBranch = $branch;
            } else {
                // Create new branches from repeater
                $branchesData = $this->data['branches'] ?? [];
                
                // Fallback for old single branch format
                if (empty($branchesData) && !empty($this->data['branch_name'])) {
                    $branchesData = [[
                        'name' => $this->data['branch_name'],
                        'city' => $this->data['branch_city'],
                        'address' => $this->data['branch_address'] ?? null,
                        'phone_number' => $this->data['branch_phone'] ?? null,
                        'features' => $this->data['branch_features'] ?? [],
                    ]];
                }
                
                // Prepare branch data for bulk insert
                $businessHours = $this->getBusinessHours();
                $branchSettings = [
                    'enable_sms' => $this->data['enable_sms'] ?? false,
                    'enable_whatsapp' => $this->data['enable_whatsapp'] ?? false,
                ];
                
                $branchModels = collect($branchesData)->map(function($branchData) use ($company, $businessHours, $branchSettings) {
                    return new Branch([
                        'company_id' => $company->id,
                        'name' => $branchData['name'],
                        'city' => $branchData['city'],
                        'address' => $branchData['address'] ?? null,
                        'phone_number' => $branchData['phone_number'] ?? null,
                        'is_active' => true,
                        'business_hours' => $businessHours,
                        'features' => $branchData['features'] ?? [],
                        'settings' => $branchSettings,
                    ]);
                });
                
                // Bulk save all branches at once
                $company->branches()->saveMany($branchModels);
                
                $branches = $branchModels->all();
                $firstBranch = $branches[0] ?? null;
            }
            
            // Use first branch for subsequent operations that expect a single branch
            $branch = $firstBranch;
            
            $this->updateProgress('Telefonnummern werden konfiguriert...', 30);
            
            // 3. Setup Phone Numbers for all branches
            foreach ($branches as $branch) {
                $this->setupPhoneNumbers($company, $branch);
            }
            
            $this->updateProgress('Cal.com Integration wird eingerichtet...', 40);
            
            // 4. Setup Cal.com
            if ($this->data['calcom_connection_type'] === 'api_key') {
                // Only update API key if it's not the placeholder and not empty
                $updateData = [];
                
                if (!empty($this->data['calcom_api_key']) && $this->data['calcom_api_key'] !== '[ENCRYPTED]') {
                    $updateData['calcom_api_key'] = encrypt($this->data['calcom_api_key']);
                }
                
                // Always update team slug
                $updateData['calcom_team_slug'] = $this->data['calcom_team_slug'] ?? null;
                
                if (!empty($updateData)) {
                    $company->update($updateData);
                }
                
                // Import event types if requested
                if ($this->data['import_event_types'] ?? true) {
                    $this->importCalcomEventTypes($company, $branch);
                }
            }
            
            $this->updateProgress('Dienstleistungen werden erstellt...', 60);
            
            // 5. Create Services
            if ($this->data['use_template_services'] ?? true) {
                $this->createTemplateServices($company, $this->data['industry']);
            }
            
            $this->updateProgress('Mitarbeiter werden angelegt...', 70);
            
            // 6. Create first staff member if provided
            $this->createFirstStaff($company, $branch);
            
            $this->updateProgress('KI-Agent wird konfiguriert...', 85);
            
            // 7. Setup Retell Agent
            $this->setupRetellAgent($branch);
            
            DB::commit();
            
            $this->updateProgress('Setup wird abgeschlossen...', 95);
            
            // Success!
            $this->setupComplete = true;
            
            $notificationTitle = $this->editMode 
                ? '✅ Firma erfolgreich aktualisiert!' 
                : '🎉 Setup erfolgreich abgeschlossen!';
            
            $notificationBody = $this->editMode
                ? 'Die Änderungen wurden erfolgreich gespeichert.'
                : 'Ihr System ist in weniger als 3 Minuten einsatzbereit!';
            
            Notification::make()
                ->title($notificationTitle)
                ->body($notificationBody)
                ->success()
                ->persistent()
                ->actions([
                    \Filament\Notifications\Actions\Action::make('add_branch')
                        ->label('Weitere Filiale hinzufügen')
                        ->url('/admin/branches/create?company=' . $company->id)
                        ->color('primary')
                        ->icon('heroicon-o-plus'),
                    \Filament\Notifications\Actions\Action::make('test_call')
                        ->label('Test-Anruf starten')
                        ->url('#')
                        ->openUrlInNewTab(),
                    \Filament\Notifications\Actions\Action::make('dashboard')
                        ->label('Zum Dashboard')
                        ->url('/admin'),
                ])
                ->send();
                
            Log::info('Quick Setup Wizard completed', [
                'company_id' => $company->id,
                'branch_id' => $branch->id,
                'duration' => now()->diffInSeconds($this->started_at ?? now()),
            ]);
            
        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollBack();
            
            Log::error('Database error in Quick Setup Wizard', [
                'error' => $e->getMessage(),
                'sql' => $e->getSql(),
            ]);
            
            Notification::make()
                ->title('Datenbankfehler')
                ->body('Es gab ein Problem beim Speichern der Daten. Bitte versuchen Sie es erneut.')
                ->danger()
                ->send();
                
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            
            Notification::make()
                ->title('Validierungsfehler')
                ->body($e->getMessage())
                ->danger()
                ->send();
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Quick Setup Wizard failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id(),
                'data' => $this->data,
            ]);
            
            Notification::make()
                ->title('Setup fehlgeschlagen')
                ->body('Ein unerwarteter Fehler ist aufgetreten. Bitte kontaktieren Sie den Support.')
                ->danger()
                ->send();
        }
    }
    
    /**
     * Handle setup errors
     */
    protected function handleSetupError(\Exception $e): void
    {
        if ($e instanceof \Illuminate\Database\QueryException) {
            Log::error('Quick Setup Wizard - Database error', [
                'error' => $e->getMessage(),
                'sql' => $e->getSql(),
            ]);
            
            Notification::make()
                ->title('Datenbankfehler')
                ->body('Es gab ein Problem beim Speichern der Daten. Bitte versuchen Sie es erneut.')
                ->danger()
                ->send();
                
        } elseif ($e instanceof \Illuminate\Validation\ValidationException) {
            Notification::make()
                ->title('Validierungsfehler')
                ->body($e->getMessage())
                ->danger()
                ->send();
                
        } else {
            Log::error('Quick Setup Wizard failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id(),  
                'data' => $this->data,
            ]);
            
            Notification::make()
                ->title('Setup fehlgeschlagen')
                ->body('Ein unerwarteter Fehler ist aufgetreten. Bitte kontaktieren Sie den Support.')
                ->danger()
                ->send();
        }
    }
    
    /**
     * Get progress message for loading indicator
     */
    public function getProgressMessage(): string
    {
        return property_exists($this, 'currentProgressMessage') 
            ? $this->currentProgressMessage 
            : 'Setup wird vorbereitet...';
    }
    
    /**
     * Get progress percentage for loading bar
     */
    public function getProgressPercentage(): int
    {
        return property_exists($this, 'currentProgressPercentage') 
            ? $this->currentProgressPercentage 
            : 0;
    }
    
    protected function getBusinessHours(): array
    {
        if (!($this->data['use_template_hours'] ?? true)) {
            return [];
        }
        
        $template = $this->industryTemplates[$this->data['industry']] ?? null;
        if (!$template) {
            return [];
        }
        
        $hours = [];
        $timeRange = explode('-', $template['working_hours'][0]);
        
        foreach (['monday', 'tuesday', 'wednesday', 'thursday', 'friday'] as $day) {
            $hours[$day] = [
                'isOpen' => true,
                'openTime' => $timeRange[0],
                'closeTime' => $timeRange[1],
            ];
        }
        
        $hours['saturday'] = ['isOpen' => false];
        $hours['sunday'] = ['isOpen' => false];
        
        return $hours;
    }
    
    protected function importCalcomEventTypes(Company $company, Branch $branch): void
    {
        try {
            $calcom = app(CalcomV2Service::class);
            $calcom->setApiKey(decrypt($company->calcom_api_key));
            
            $eventTypes = $calcom->getEventTypes();
            
            foreach ($eventTypes as $eventType) {
                \App\Models\CalcomEventType::updateOrCreate(
                    [
                        'calcom_id' => $eventType['id'],
                        'company_id' => $company->id,
                    ],
                    [
                        'name' => $eventType['title'],
                        'slug' => $eventType['slug'],
                        'duration' => $eventType['length'],
                        'is_active' => true,
                    ]
                );
            }
            
            // Set first event type as default for branch
            $firstEventType = \App\Models\CalcomEventType::where('company_id', $company->id)->first();
            if ($firstEventType) {
                $branch->update(['calcom_event_type_id' => $firstEventType->calcom_id]);
            }
            
        } catch (\Exception $e) {
            Log::warning('Could not import Cal.com event types', ['error' => $e->getMessage()]);
        }
    }
    
    protected function createTemplateServices(Company $company, string $industry): void
    {
        $template = $this->industryTemplates[$industry] ?? null;
        if (!$template) {
            return;
        }
        
        if ($this->editMode) {
            // In edit mode, only add services that don't exist yet
            $existingServices = $company->services()->pluck('name')->toArray();
            
            foreach ($template['services'] as $serviceName) {
                if (!in_array($serviceName, $existingServices)) {
                    \App\Models\Service::create([
                        'company_id' => $company->id,
                        'name' => $serviceName,
                        'duration' => $template['appointment_duration'],
                        'buffer_time' => $template['buffer_time'],
                        'is_active' => true,
                    ]);
                }
            }
        } else {
            // Create all template services for new company
            foreach ($template['services'] as $serviceName) {
                \App\Models\Service::create([
                    'company_id' => $company->id,
                    'name' => $serviceName,
                    'duration' => $template['appointment_duration'],
                    'buffer_time' => $template['buffer_time'],
                    'is_active' => true,
                ]);
            }
        }
    }
    
    protected function setupRetellAgent(Branch $branch): void
    {
        try {
            // First ensure the company has a Retell API key
            $company = $branch->company;
            if (!$company->retell_api_key) {
                // Use default API key if available
                $defaultKey = config('services.retell.api_key');
                if ($defaultKey) {
                    $company->update(['retell_api_key' => encrypt($defaultKey)]);
                }
            }
            
            // Prepare branch with necessary data for agent provisioning
            $greeting = $this->data['use_template_greeting'] ?? true 
                ? str_replace('[FIRMA]', $company->name, $this->industryTemplates[$this->data['industry']]['greeting'])
                : $this->data['custom_greeting'] ?? null;
                
            $voiceMapping = [
                'sarah' => 'de-DE-KatjaNeural',
                'matt' => 'de-DE-FlorianNeural',
                'custom' => 'de-DE-KatjaNeural' // Default for now
            ];
            
            $branch->update([
                'settings' => [
                    'voice_id' => $voiceMapping[$this->data['ai_voice'] ?? 'sarah'],
                    'custom_prompt_instructions' => $greeting,
                    'industry' => $this->data['industry'],
                ]
            ]);
            
            // Use the provisioner to create the agent
            $provisioner = new RetellAgentProvisioner();
            $result = $provisioner->createAgentForBranch($branch);
            
            if ($result['success']) {
                Log::info('Retell agent created successfully', [
                    'branch_id' => $branch->id,
                    'agent_id' => $result['agent_id'],
                ]);
                
                // Schedule test call if requested
                if ($this->data['enable_test_call'] ?? true) {
                    // Store flag for test call page
                    session(['pending_test_call' => $branch->id]);
                }
            } else {
                Log::warning('Retell agent creation failed', [
                    'branch_id' => $branch->id,
                    'error' => $result['error'],
                ]);
                
                // Mark as pending for manual setup
                $branch->update([
                    'retell_agent_status' => 'pending',
                    'settings' => array_merge($branch->settings ?? [], [
                        'setup_error' => $result['error'],
                    ]),
                ]);
            }
            
        } catch (\Exception $e) {
            Log::error('Failed to setup Retell agent', [
                'branch_id' => $branch->id,
                'error' => $e->getMessage(),
            ]);
            
            // Mark as pending for manual setup
            $branch->update([
                'retell_agent_status' => 'pending',
                'settings' => array_merge($branch->settings ?? [], [
                    'setup_error' => $e->getMessage(),
                ]),
            ]);
        }
    }
    
    protected function setupPhoneNumbers(Company $company, Branch $branch): void
    {
        try {
            // Handle phone strategy (direct numbers per branch or mixed)
            $phoneStrategy = $this->data['phone_strategy'] ?? 'direct';
            
            if ($phoneStrategy === 'direct' || $phoneStrategy === 'mixed') {
                // Handle direct numbers from branch_phone_numbers array
                if (!empty($this->data['branch_phone_numbers'])) {
                    foreach ($this->data['branch_phone_numbers'] as $phoneData) {
                        // Match branch by ID or by name
                        $matchesBranch = false;
                        if (isset($phoneData['branch_id']) && $phoneData['branch_id'] == $branch->id) {
                            $matchesBranch = true;
                        } elseif (isset($phoneData['branch_name']) && $phoneData['branch_name'] == $branch->name) {
                            $matchesBranch = true;
                        }
                        
                        if ($matchesBranch && !empty($phoneData['number'])) {
                            if ($this->editMode) {
                                // Update or create phone number
                                PhoneNumber::updateOrCreate(
                                    [
                                        'company_id' => $company->id,
                                        'branch_id' => $branch->id,
                                        'type' => 'direct',
                                    ],
                                    [
                                        'number' => $phoneData['number'],
                                        'is_primary' => $phoneData['is_primary'] ?? true,
                                        'sms_enabled' => $phoneData['sms_enabled'] ?? false,
                                        'whatsapp_enabled' => $phoneData['whatsapp_enabled'] ?? false,
                                        'active' => true,
                                        'description' => "Direktwahl {$branch->name}",
                                    ]
                                );
                            } else {
                                PhoneNumber::create([
                                    'company_id' => $company->id,
                                    'branch_id' => $branch->id,
                                    'number' => $phoneData['number'],
                                    'type' => 'direct',
                                    'is_primary' => $phoneData['is_primary'] ?? true,
                                    'sms_enabled' => $phoneData['sms_enabled'] ?? false,
                                    'whatsapp_enabled' => $phoneData['whatsapp_enabled'] ?? false,
                                    'active' => true,
                                    'description' => "Direktwahl {$branch->name}",
                                ]);
                            }
                        }
                    }
                }
                
                // Fallback to old single branch_phone field for backwards compatibility
                elseif (!empty($this->data['branch_phone'])) {
                    if ($this->editMode) {
                        // Update or create phone number
                        PhoneNumber::updateOrCreate(
                            [
                                'company_id' => $company->id,
                                'branch_id' => $branch->id,
                                'type' => 'direct',
                            ],
                            [
                                'number' => $this->data['branch_phone'],
                                'active' => true,
                                'description' => "Direktwahl {$branch->name}",
                            ]
                        );
                    } else {
                        PhoneNumber::create([
                            'company_id' => $company->id,
                            'branch_id' => $branch->id,
                            'number' => $this->data['branch_phone'],
                            'type' => 'direct',
                            'active' => true,
                            'description' => "Direktwahl {$branch->name}",
                        ]);
                    }
                }
            }
            
            // Handle hotline
            if ($this->data['use_hotline'] ?? false) {
                $routingConfig = [
                    'strategy' => $this->data['routing_strategy'] ?? 'voice_menu',
                ];
                
                // For voice menu, prepare branch options
                if ($routingConfig['strategy'] === 'voice_menu') {
                    $routingConfig['menu_options'] = [
                        [
                            'branch_id' => $branch->id,
                            'keywords' => [$branch->name, $branch->city, 'hauptfiliale'],
                        ]
                    ];
                }
                
                if ($this->editMode) {
                    // Update or create hotline
                    PhoneNumber::updateOrCreate(
                        [
                            'company_id' => $company->id,
                            'type' => 'hotline',
                        ],
                        [
                            'number' => $this->data['hotline_number'],
                            'routing_config' => $routingConfig,
                            'active' => true,
                            'description' => "Zentrale Hotline {$company->name}",
                        ]
                    );
                } else {
                    PhoneNumber::create([
                        'company_id' => $company->id,
                        'branch_id' => null, // Hotline has no specific branch
                        'number' => $this->data['hotline_number'],
                        'type' => 'hotline',
                        'routing_config' => $routingConfig,
                        'active' => true,
                        'description' => "Zentrale Hotline {$company->name}",
                    ]);
                }
            } elseif ($this->editMode) {
                // Delete hotline if it was disabled
                PhoneNumber::where('company_id', $company->id)
                    ->where('type', 'hotline')
                    ->delete();
            }
            
            Log::info('Phone numbers configured', [
                'company_id' => $company->id,
                'branch_id' => $branch->id,
                'has_hotline' => $this->data['use_hotline'] ?? false,
            ]);
            
        } catch (\Exception $e) {
            Log::warning('Failed to setup phone numbers', [
                'error' => $e->getMessage(),
                'company_id' => $company->id,
            ]);
            // Non-critical, continue setup
        }
    }
    
    protected function createFirstStaff(Company $company, Branch $branch): void
    {
        if (empty($this->data['first_staff_name'])) {
            return; // Optional, skip if not provided
        }
        
        try {
            $staff = \App\Models\Staff::create([
                'company_id' => $company->id,
                'home_branch_id' => $branch->id,
                'name' => $this->data['first_staff_name'],
                'email' => $this->data['first_staff_email'] ?? null,
                'languages' => $this->data['first_staff_languages'] ?? ['de'],
                'active' => true,
                'is_bookable' => true,
                'calendar_mode' => 'inherit',
                'experience_level' => 2, // Default to medium
            ]);
            
            // Attach to branch
            $staff->branches()->attach($branch->id);
            
            // Attach all services
            $services = \App\Models\Service::where('company_id', $company->id)->pluck('id');
            if ($services->isNotEmpty()) {
                $staff->services()->attach($services);
            }
            
            Log::info('First staff member created', [
                'staff_id' => $staff->id,
                'company_id' => $company->id,
            ]);
            
        } catch (\Exception $e) {
            Log::warning('Failed to create first staff member', [
                'error' => $e->getMessage(),
                'company_id' => $company->id,
            ]);
            // Non-critical, continue
        }
    }
    
    /**
     * Get enhanced phone configuration fields for the wizard
     */
    protected function getEnhancedPhoneConfigurationFields(): array
    {
        return [
            Section::make('Telefonnummern-Strategie')
                ->description('Wie sollen Kunden Ihre Filialen erreichen?')
                ->schema([
                    // Strategy Selection
                    ToggleButtons::make('phone_strategy')
                        ->label('Telefon-Strategie')
                        ->options([
                            'direct' => 'Direkte Durchwahl pro Filiale',
                            'hotline' => 'Zentrale Hotline mit Menü',
                            'mixed' => 'Kombination (Hotline + Durchwahlen)'
                        ])
                        ->default(fn() => $this->company && $this->company->branches()->count() > 1 ? 'hotline' : 'direct')
                        ->reactive()
                        ->afterStateUpdated(function ($state, $set) {
                            // Auto-configure based on selection
                            if ($state === 'direct') {
                                $set('use_hotline', false);
                            } elseif ($state === 'hotline') {
                                $set('use_hotline', true);
                            }
                        })
                        ->helperText(fn($state) => match($state) {
                            'direct' => 'Jede Filiale hat ihre eigene Telefonnummer',
                            'hotline' => 'Eine zentrale Nummer für alle Filialen',
                            'mixed' => 'Zentrale Hotline + optionale Direktnummern',
                            default => ''
                        }),
                ]),

            // Hotline Configuration (conditional)
            Section::make('Hotline-Konfiguration')
                ->visible(fn($get) => in_array($get('phone_strategy'), ['hotline', 'mixed']))
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('hotline_number')
                            ->label('Zentrale Hotline-Nummer')
                            ->tel()
                            ->required(fn($get) => in_array($get('phone_strategy'), ['hotline', 'mixed']))
                            ->prefix('+49')
                            ->placeholder('30 12345678')
                            ->helperText('Ihre zentrale Rufnummer für alle Standorte')
                            ->validationAttribute('Hotline-Nummer'),

                        Select::make('routing_strategy')
                            ->label('Routing-Strategie')
                            ->options([
                                'voice_menu' => '🗣️ Sprachmenü (Kunde sagt Filialname)',
                                'business_hours' => '🕐 Nach Öffnungszeiten',
                                'load_balanced' => '⚖️ Gleichmäßige Verteilung',
                                'geographic' => '📍 Nach Anrufer-Region (Premium)'
                            ])
                            ->default('voice_menu')
                            ->reactive()
                            ->required(fn($get) => in_array($get('phone_strategy'), ['hotline', 'mixed']))
                            ->helperText('Wie sollen Anrufe verteilt werden?'),
                    ]),

                    // Voice Menu Keywords (conditional)
                    KeyValue::make('voice_keywords')
                        ->label('Sprachmenü-Konfiguration')
                        ->visible(fn($get) => $get('routing_strategy') === 'voice_menu')
                        ->keyLabel('Filiale')
                        ->valueLabel('Schlüsselwörter (kommagetrennt)')
                        ->default(function() {
                            if (!$this->company) return [];
                            return $this->company->branches->mapWithKeys(function($branch) {
                                return [$branch->name => $branch->city . ', ' . $branch->name];
                            })->toArray();
                        })
                        ->helperText('Welche Wörter führen zu welcher Filiale? z.B. "Berlin, Hauptfiliale"')
                        ->reorderable(false)
                        ->addable(false)
                        ->deletable(false),
                ]),

            // Direct Numbers Configuration
            Section::make('Direkte Durchwahlnummern')
                ->visible(fn($get) => in_array($get('phone_strategy'), ['direct', 'mixed']))
                ->schema([
                    Repeater::make('branch_phone_numbers')
                        ->label('')
                        ->schema([
                            Grid::make(3)->schema([
                                Hidden::make('branch_id'),
                                
                                TextInput::make('branch_name')
                                    ->label('Filiale')
                                    ->disabled()
                                    ->dehydrated(false),
                                
                                TextInput::make('number')
                                    ->label('Telefonnummer')
                                    ->tel()
                                    ->required()
                                    ->prefix('+49')
                                    ->placeholder('30 12345678')
                                    ->helperText('Format: +49 30 12345678'),
                                
                                ToggleButtons::make('is_primary')
                                    ->label('Haupt-Nr.')
                                    ->boolean('Ja', 'Nein')
                                    ->inline()
                                    ->default(true),
                            ]),
                            
                            Grid::make(2)->schema([
                                Toggle::make('sms_enabled')
                                    ->label('SMS-Empfang aktivieren')
                                    ->default(false)
                                    ->helperText('Für SMS-Terminbestätigungen'),
                                
                                Toggle::make('whatsapp_enabled')
                                    ->label('WhatsApp Business')
                                    ->default(false)
                                    ->helperText('Für WhatsApp-Kommunikation'),
                            ]),
                        ])
                        ->default(function() {
                            // Initialize with branch data when branches exist
                            if ($this->company && $this->company->branches->count() > 0) {
                                return $this->company->branches->map(function($branch) {
                                    return [
                                        'branch_id' => $branch->id,
                                        'branch_name' => $branch->name,
                                        'number' => '',
                                        'is_primary' => true,
                                        'sms_enabled' => false,
                                        'whatsapp_enabled' => false,
                                    ];
                                })->toArray();
                            }
                            return [];
                        })
                        ->afterStateHydrated(function ($component, $state) {
                            // Update with branches when form is loaded
                            if (empty($state) && $this->company && $this->company->branches->count() > 0) {
                                $branchData = $this->company->branches->map(function($branch) {
                                    $existingPhone = PhoneNumber::where('company_id', $this->company->id)
                                        ->where('branch_id', $branch->id)
                                        ->where('type', 'direct')
                                        ->first();
                                    
                                    return [
                                        'branch_id' => $branch->id,
                                        'branch_name' => $branch->name,
                                        'number' => $existingPhone?->number ?? '',
                                        'is_primary' => $existingPhone?->is_primary ?? true,
                                        'sms_enabled' => $existingPhone?->sms_enabled ?? false,
                                        'whatsapp_enabled' => $existingPhone?->whatsapp_enabled ?? false,
                                    ];
                                })->toArray();
                                
                                $component->state($branchData);
                            }
                        })
                        ->collapsible()
                        ->collapsed(false)
                        ->itemLabel(fn (array $state): ?string => 
                            $state['branch_name'] ?? 'Filiale'
                        )
                        ->disableItemCreation()
                        ->disableItemDeletion()
                        ->disableItemMovement(),
                ]),

            // SMS/WhatsApp Global Settings
            Section::make('Zusätzliche Kommunikationskanäle')
                ->collapsed()
                ->schema([
                    Grid::make(2)->schema([
                        Toggle::make('global_sms_enabled')
                            ->label('SMS-Service aktivieren')
                            ->default(false)
                            ->helperText('Automatische SMS-Bestätigungen und Erinnerungen'),
                        
                        Toggle::make('global_whatsapp_enabled')
                            ->label('WhatsApp Business API')
                            ->default(false)
                            ->helperText('WhatsApp-Integration für Kundenkommunikation'),
                    ]),
                ]),
        ];
    }
    
    /**
     * Get enhanced staff and services configuration fields
     */
    protected function getEnhancedStaffAndServicesFields(): array
    {
        return [
            Placeholder::make('template_info')
                ->content(fn($get) => $this->getTemplateInfo($get('industry'))),
                
            // Service Configuration
            Section::make('Dienstleistungen')
                ->description('Konfigurieren Sie Ihre angebotenen Services')
                ->schema([
                    Toggle::make('use_template_services')
                        ->label('Branchen-Services verwenden')
                        ->default(true)
                        ->reactive()
                        ->helperText('Vorkonfigurierte Services für Ihre Branche'),
                    
                    Repeater::make('custom_services')
                        ->label('Services anpassen')
                        ->visible(fn($get) => !$get('use_template_services'))
                        ->schema([
                            Grid::make(3)->schema([
                                TextInput::make('name')
                                    ->label('Service-Name')
                                    ->required(),
                                
                                TextInput::make('duration')
                                    ->label('Dauer (Min.)')
                                    ->numeric()
                                    ->default(30)
                                    ->required(),
                                
                                TextInput::make('price')
                                    ->label('Preis (€)')
                                    ->numeric()
                                    ->prefix('€'),
                            ]),
                        ])
                        ->defaultItems(3)
                        ->collapsible(),
                ]),
            
            // Working Hours
            Section::make('Öffnungszeiten')
                ->description('Standard-Öffnungszeiten für alle Filialen')
                ->schema([
                    Toggle::make('use_template_hours')
                        ->label('Branchen-Öffnungszeiten verwenden')
                        ->default(true)
                        ->helperText('Typische Öffnungszeiten für Ihre Branche'),
                ]),
            
            // Staff Configuration (Enhanced)
            Section::make('Mitarbeiter-Konfiguration')
                ->description('Fügen Sie Ihre ersten Mitarbeiter hinzu')
                ->schema([
                    Repeater::make('staff_members')
                        ->label('Mitarbeiter')
                        ->schema([
                            // Basic Information
                            Grid::make(2)->schema([
                                TextInput::make('name')
                                    ->label('Name')
                                    ->required()
                                    ->placeholder('z.B. Anna Schmidt'),
                                
                                TextInput::make('email')
                                    ->label('E-Mail')
                                    ->email()
                                    ->required()
                                    ->placeholder('anna@beispiel.de'),
                            ]),
                            
                            // Qualifications
                            Section::make('Qualifikationen')
                                ->columns(2)
                                ->schema([
                                    // Languages with visual flags
                                    CheckboxList::make('languages')
                                        ->label('Sprachen')
                                        ->options([
                                            'de' => '🇩🇪 Deutsch',
                                            'en' => '🇬🇧 Englisch',
                                            'tr' => '🇹🇷 Türkisch',
                                            'ar' => '🇸🇦 Arabisch',
                                            'fr' => '🇫🇷 Französisch',
                                            'es' => '🇪🇸 Spanisch',
                                            'it' => '🇮🇹 Italienisch',
                                            'ru' => '🇷🇺 Russisch',
                                            'pl' => '🇵🇱 Polnisch',
                                        ])
                                        ->default(['de'])
                                        ->columns(3)
                                        ->bulkToggleable(),

                                    // Experience Level
                                    Select::make('experience_level')
                                        ->label('Erfahrungsstufe')
                                        ->options([
                                            1 => '⭐ Junior (< 2 Jahre)',
                                            2 => '⭐⭐ Mittel (2-5 Jahre)',
                                            3 => '⭐⭐⭐ Senior (5-10 Jahre)',
                                            4 => '⭐⭐⭐⭐ Expert (> 10 Jahre)',
                                        ])
                                        ->default(2),

                                    // Dynamic Skills based on industry
                                    TagsInput::make('skills')
                                        ->label('Fähigkeiten')
                                        ->suggestions(fn() => $this->getIndustrySkillSuggestions())
                                        ->placeholder('Tippen Sie für Vorschläge...')
                                        ->reorderable()
                                        ->splitKeys(['Tab', ',', ';'])
                                        ->helperText('Relevante Fähigkeiten für Kunden-Matching'),

                                    // Certifications
                                    TagsInput::make('certifications')
                                        ->label('Zertifikate')
                                        ->placeholder('z.B. Meisterbrief, Erste Hilfe')
                                        ->suggestions(fn() => $this->getIndustryCertificationSuggestions())
                                        ->helperText('Relevante Ausbildungen und Zertifikate'),
                                ]),
                            
                            // Branch Assignment
                            Select::make('branch_id')
                                ->label('Filialzuordnung')
                                ->options(fn() => $this->company?->branches->pluck('name', 'id') ?? [])
                                ->default(fn() => $this->company?->branches->first()?->id)
                                ->helperText('Haupt-Arbeitsort des Mitarbeiters'),
                        ])
                        ->defaultItems(1)
                        ->collapsible()
                        ->cloneable()
                        ->reorderable()
                        ->itemLabel(fn (array $state): ?string => 
                            isset($state['name']) ? $state['name'] : 'Neuer Mitarbeiter'
                        )
                        ->addActionLabel('Weiteren Mitarbeiter hinzufügen'),
                ]),
                
            // Summary
            Section::make('Zusammenfassung')
                ->schema([
                    Placeholder::make('summary')
                        ->content(fn($get) => $this->getSummaryFromGet($get)),
                ]),
        ];
    }
    
    /**
     * Get industry-specific skill suggestions
     */
    protected function getIndustrySkillSuggestions(): array
    {
        $industry = $this->data['industry'] ?? 'generic';
        
        $suggestions = [
            'salon' => [
                'Damenhaarschnitt', 'Herrenhaarschnitt', 'Kinderhaarschnitt',
                'Färben', 'Strähnen', 'Dauerwelle', 'Glättung',
                'Hochsteckfrisuren', 'Haarverdünnung', 'Extensions',
                'Beratung', 'Kopfmassage', 'Bartpflege'
            ],
            'fitness' => [
                'Personal Training', 'Ernährungsberatung', 'Functional Training',
                'Krafttraining', 'Cardio', 'Yoga', 'Pilates',
                'Gruppenkurse', 'Reha-Sport', 'Rückenschule',
                'TRX', 'CrossFit', 'HIIT', 'Mobility'
            ],
            'medical' => [
                'Erstuntersuchung', 'Nachsorge', 'Ultraschall',
                'Blutentnahme', 'Impfungen', 'Wundversorgung',
                'EKG', 'Langzeit-EKG', 'Blutdruckmessung',
                'Diabetesberatung', 'Prävention', 'Notfallmedizin'
            ],
            'generic' => [
                'Beratung', 'Kundenbetreuung', 'Termine vereinbaren',
                'Dokumentation', 'Abrechnung', 'Qualitätssicherung',
                'Teamarbeit', 'Projektmanagement', 'Kommunikation'
            ],
        ];
        
        return $suggestions[$industry] ?? $suggestions['generic'];
    }
    
    /**
     * Get industry-specific certification suggestions
     */
    protected function getIndustryCertificationSuggestions(): array
    {
        $industry = $this->data['industry'] ?? 'generic';
        
        $suggestions = [
            'salon' => [
                'Friseurmeister', 'Colorist-Zertifikat', 'Visagist',
                'Haarverdünnung-Spezialist', 'Extensions-Zertifikat'
            ],
            'fitness' => [
                'Fitnesstrainer B-Lizenz', 'Fitnesstrainer A-Lizenz',
                'Personal Trainer', 'Ernährungsberater', 'Reha-Trainer',
                'Yoga-Lehrer', 'Pilates-Instructor'
            ],
            'medical' => [
                'Facharzt', 'Notfallmedizin', 'Hygienebeauftragter',
                'Strahlenschutz', 'Reanimationstraining'
            ],
            'generic' => [
                'Erste Hilfe', 'Brandschutzhelfer', 'Datenschutzbeauftragter',
                'Projektmanagement', 'Qualitätsmanagement'
            ],
        ];
        
        return $suggestions[$industry] ?? $suggestions['generic'];
    }
    
    /**
     * Get integration check fields for live API testing
     */
    protected function getIntegrationCheckFields(): array
    {
        return [
            Section::make('Cal.com Verbindung')
                ->description('Live API-Test mit Ihren Zugangsdaten')
                ->schema([
                    Placeholder::make('calcom_test_status')
                        ->content(function () {
                            return new HtmlString(
                                '<div 
                                    x-data="{ 
                                        testing: false, 
                                        status: null,
                                        error: null,
                                        testCalcom() {
                                            this.testing = true;
                                            this.status = null;
                                            this.error = null;
                                            
                                            $wire.testCalcomConnection().then(result => {
                                                this.testing = false;
                                                this.status = result.success ? \'success\' : \'error\';
                                                this.error = result.error || null;
                                            });
                                        }
                                    }" 
                                    class="space-y-4"
                                >
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <h4 class="text-sm font-medium">API-Verbindung</h4>
                                            <p class="text-xs text-gray-500">Testet die Verbindung zu Cal.com</p>
                                        </div>
                                        <button 
                                            @click="testCalcom()" 
                                            :disabled="testing"
                                            type="button"
                                            class="inline-flex items-center px-3 py-2 text-sm font-medium text-white bg-primary-600 rounded-md hover:bg-primary-700 disabled:opacity-50"
                                        >
                                            <span x-show="!testing">Test starten</span>
                                            <span x-show="testing" class="flex items-center">
                                                <svg class="w-4 h-4 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                                </svg>
                                                Teste...
                                            </span>
                                        </button>
                                    </div>
                                    
                                    <div x-show="status === \'success\'" class="p-3 bg-green-50 border border-green-200 rounded-lg">
                                        <p class="text-sm text-green-800">✅ Verbindung erfolgreich!</p>
                                    </div>
                                    
                                    <div x-show="status === \'error\'" class="p-3 bg-red-50 border border-red-200 rounded-lg">
                                        <p class="text-sm text-red-800">❌ Verbindung fehlgeschlagen</p>
                                        <p x-text="error" class="mt-1 text-xs text-red-600"></p>
                                    </div>
                                </div>'
                            );
                        }),
                ]),
                
            Section::make('Retell.ai Verbindung')
                ->description('Live API-Test mit Ihrem Retell Account')
                ->schema([
                    Placeholder::make('retell_test_status')
                        ->content(function () {
                            return new HtmlString(
                                '<div 
                                    x-data="{ 
                                        testing: false, 
                                        status: null,
                                        error: null,
                                        agentInfo: null,
                                        testRetell() {
                                            this.testing = true;
                                            this.status = null;
                                            this.error = null;
                                            this.agentInfo = null;
                                            
                                            $wire.testRetellConnection().then(result => {
                                                this.testing = false;
                                                this.status = result.success ? \'success\' : \'error\';
                                                this.error = result.error || null;
                                                this.agentInfo = result.agent || null;
                                            });
                                        }
                                    }" 
                                    class="space-y-4"
                                >
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <h4 class="text-sm font-medium">API-Verbindung & Agent</h4>
                                            <p class="text-xs text-gray-500">Testet die Verbindung zu Retell.ai</p>
                                        </div>
                                        <button 
                                            @click="testRetell()" 
                                            :disabled="testing"
                                            type="button"
                                            class="inline-flex items-center px-3 py-2 text-sm font-medium text-white bg-primary-600 rounded-md hover:bg-primary-700 disabled:opacity-50"
                                        >
                                            <span x-show="!testing">Test starten</span>
                                            <span x-show="testing" class="flex items-center">
                                                <svg class="w-4 h-4 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                                </svg>
                                                Teste...
                                            </span>
                                        </button>
                                    </div>
                                    
                                    <div x-show="status === \'success\'" class="p-3 bg-green-50 border border-green-200 rounded-lg">
                                        <p class="text-sm text-green-800">✅ Verbindung erfolgreich!</p>
                                        <div x-show="agentInfo" class="mt-2 text-xs text-green-700">
                                            <p>Agent: <span x-text="agentInfo?.agent_name"></span></p>
                                            <p x-show="agentInfo?.agent_id">Agent ID: <span x-text="agentInfo?.agent_id"></span></p>
                                            <p x-show="agentInfo?.voice_id !== \'Wird automatisch erstellt\'">Voice: <span x-text="agentInfo?.voice_id"></span></p>
                                            <p x-show="agentInfo?.agent_count !== undefined">Konfigurierte Agents: <span x-text="agentInfo?.agent_count"></span></p>
                                        </div>
                                    </div>
                                    
                                    <div x-show="status === \'error\'" class="p-3 bg-red-50 border border-red-200 rounded-lg">
                                        <p class="text-sm text-red-800">❌ Verbindung fehlgeschlagen</p>
                                        <p x-text="error" class="mt-1 text-xs text-red-600"></p>
                                    </div>
                                </div>'
                            );
                        }),
                ]),
                
            Section::make('Zusammenfassung')
                ->schema([
                    Placeholder::make('integration_summary')
                        ->content(function () {
                            return new HtmlString(
                                '<div class="p-4 bg-gray-50 rounded-lg">
                                    <h4 class="text-sm font-medium mb-2">Integrations-Status</h4>
                                    <div class="space-y-2 text-sm">
                                        <div class="flex items-center justify-between">
                                            <span>Cal.com</span>
                                            <span class="integration-status" data-service="calcom">⏳ Nicht getestet</span>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <span>Retell.ai</span>
                                            <span class="integration-status" data-service="retell">⏳ Nicht getestet</span>
                                        </div>
                                    </div>
                                    <div class="mt-4 p-3 bg-blue-50 border border-blue-200 rounded">
                                        <p class="text-xs text-blue-800">
                                            💡 Tipp: Testen Sie beide Verbindungen, bevor Sie fortfahren. 
                                            Falls ein Test fehlschlägt, überprüfen Sie Ihre API-Keys im vorherigen Schritt.
                                        </p>
                                    </div>
                                </div>'
                            );
                        }),
                ]),
        ];
    }
    
    /**
     * Get review and health check fields for final wizard step
     */
    protected function getReviewAndHealthCheckFields(): array
    {
        return [
            // Configuration Summary
            Section::make('Konfigurationsübersicht')
                ->description('Überprüfen Sie Ihre Einstellungen')
                ->schema([
                    Placeholder::make('config_summary')
                        ->content(fn($get) => $this->getConfigurationSummary($get)),
                ]),
                
            // Health Check Status with Traffic Light System
            Section::make('System-Status')
                ->description('Live-Überprüfung Ihrer Integrationen')
                ->schema([
                    // Live Health Check Component
                    Placeholder::make('health_check_status')
                        ->content(fn() => $this->renderHealthCheckStatus())
                        ->reactive(),
                        
                    // Refresh Info
                    Placeholder::make('refresh_info')
                        ->content('💡 Die Überprüfung läuft automatisch. Bei Bedarf können Sie die Seite neu laden.')
                        ->helperText('Probleme werden automatisch erkannt und Lösungsvorschläge angezeigt.'),
                ]),
                
            // Detailed Issues (if any)
            Section::make('Details')
                ->description('Gefundene Probleme und Lösungsvorschläge')
                ->visible(fn() => $this->hasHealthIssues())
                ->collapsible()
                ->collapsed()
                ->schema([
                    Placeholder::make('issue_details')
                        ->content(fn() => $this->renderIssueDetails()),
                ]),
                
            // Final Actions
            Section::make('Nächste Schritte')
                ->schema([
                    CheckboxList::make('post_setup_actions')
                        ->label('Nach der Einrichtung')
                        ->options([
                            'test_call' => '📞 Test-Anruf durchführen',
                            'send_welcome_email' => '📧 Willkommens-E-Mail senden',
                            'create_sample_appointments' => '📅 Beispiel-Termine erstellen',
                            'tutorial_mode' => '🎓 Tutorial-Modus aktivieren',
                        ])
                        ->default(['test_call', 'send_welcome_email'])
                        ->columns(2),
                ]),
        ];
    }
    
    /**
     * Get configuration summary for review
     */
    protected function getConfigurationSummary($get): string
    {
        $lines = [];
        
        // Company Info
        $lines[] = "### 🏢 Firmendaten";
        $lines[] = "- **Name:** " . ($get('company_name') ?? 'Nicht angegeben');
        $lines[] = "- **Branche:** " . ($this->industryTemplates[$get('industry')]['name'] ?? 'Nicht gewählt');
        $lines[] = "- **Filiale:** " . ($get('branch_name') ?? 'Hauptfiliale') . " in " . ($get('branch_city') ?? 'Stadt');
        $lines[] = "";
        
        // Phone Configuration
        $lines[] = "### 📞 Telefon-Konfiguration";
        $phoneStrategy = $get('phone_strategy') ?? 'direct';
        $lines[] = "- **Strategie:** " . match($phoneStrategy) {
            'direct' => 'Direkte Durchwahl',
            'hotline' => 'Zentrale Hotline',
            'mixed' => 'Kombination'
        };
        if ($phoneStrategy !== 'direct') {
            $lines[] = "- **Hotline:** " . ($get('hotline_number') ?? 'Nicht konfiguriert');
        }
        $lines[] = "";
        
        // Integrations
        $lines[] = "### 🔗 Integrationen";
        $lines[] = "- **Cal.com:** " . (($get('calcom_connection_type') === 'oauth') ? 'OAuth verbunden' : 'API Key konfiguriert');
        $lines[] = "- **Retell.ai:** " . ($get('ai_voice') ?? 'Nicht konfiguriert');
        $lines[] = "";
        
        // Staff
        $staffCount = count($get('staff_members') ?? []);
        $lines[] = "### 👥 Team";
        $lines[] = "- **Mitarbeiter:** " . $staffCount . " konfiguriert";
        
        return implode("\n", $lines);
    }
    
    /**
     * Render health check status with traffic light system
     */
    protected function renderHealthCheckStatus(): string
    {
        if (!$this->company) {
            // For wizard context, create temporary company object from form data
            $this->company = $this->createTemporaryCompany();
        }
        
        $healthService = app(\App\Services\HealthCheckService::class);
        $healthService->setCompany($this->company);
        
        try {
            $report = $healthService->runAll();
            
            $html = '<div class="space-y-4">';
            
            // Overall Status
            $overallIcon = match($report->status) {
                'healthy' => '🟢',
                'degraded' => '🟡',
                'unhealthy' => '🔴',
                default => '⚪'
            };
            
            $html .= sprintf(
                '<div class="text-2xl font-bold">%s Gesamt-Status: %s</div>',
                $overallIcon,
                $this->getStatusLabel($report->status)
            );
            
            // Individual Checks
            $html .= '<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">';
            
            foreach ($report->checks as $checkName => $result) {
                $icon = match($result->status) {
                    'healthy' => '🟢',
                    'degraded' => '🟡',
                    'unhealthy' => '🔴',
                    default => '⚪'
                };
                
                $bgColor = match($result->status) {
                    'healthy' => 'bg-green-50 dark:bg-green-900/20',
                    'degraded' => 'bg-yellow-50 dark:bg-yellow-900/20',
                    'unhealthy' => 'bg-red-50 dark:bg-red-900/20',
                    default => 'bg-gray-50 dark:bg-gray-900/20'
                };
                
                $borderColor = match($result->status) {
                    'healthy' => 'border-green-200 dark:border-green-800',
                    'degraded' => 'border-yellow-200 dark:border-yellow-800',
                    'unhealthy' => 'border-red-200 dark:border-red-800',
                    default => 'border-gray-200 dark:border-gray-800'
                };
                
                $html .= sprintf(
                    '<div class="p-4 rounded-lg border %s %s">
                        <div class="flex items-center justify-between">
                            <span class="font-medium">%s</span>
                            <span class="text-2xl">%s</span>
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400 mt-2">%s</div>
                        <div class="text-xs text-gray-500 mt-1">%.0fms</div>
                    </div>',
                    $bgColor,
                    $borderColor,
                    $checkName,
                    $icon,
                    Str::limit($result->message, 60),
                    $result->responseTime
                );
            }
            
            $html .= '</div>';
            
            // Store results for other methods
            $this->healthCheckResults = $report;
            
            $html .= '</div>';
            
            return $html;
            
        } catch (\Exception $e) {
            return '<div class="text-red-600">⚠️ Health Check konnte nicht durchgeführt werden: ' . $e->getMessage() . '</div>';
        }
    }
    
    /**
     * Get status label in German
     */
    protected function getStatusLabel(string $status): string
    {
        return match($status) {
            'healthy' => 'Alles funktioniert',
            'degraded' => 'Teilweise Probleme',
            'unhealthy' => 'Kritische Probleme',
            default => 'Unbekannt'
        };
    }
    
    /**
     * Check if there are health issues
     */
    protected function hasHealthIssues(): bool
    {
        if (!isset($this->healthCheckResults)) {
            return false;
        }
        
        return $this->healthCheckResults->status !== 'healthy';
    }
    
    /**
     * Check if there are fixable issues
     */
    protected function hasFixableIssues(): bool
    {
        if (!isset($this->healthCheckResults)) {
            return false;
        }
        
        // Check if any issues have auto-fix capability
        foreach ($this->healthCheckResults->checks as $result) {
            if (!empty($result->issues)) {
                return true; // Simplified - in reality would check if fixes available
            }
        }
        
        return false;
    }
    
    /**
     * Render detailed issue information
     */
    protected function renderIssueDetails(): string
    {
        if (!isset($this->healthCheckResults)) {
            return '';
        }
        
        $html = '<div class="space-y-4">';
        
        foreach ($this->healthCheckResults->checks as $checkName => $result) {
            if ($result->status === 'healthy') {
                continue;
            }
            
            $html .= sprintf('<div class="border-l-4 border-yellow-400 pl-4">');
            $html .= sprintf('<h4 class="font-semibold text-lg mb-2">%s</h4>', $checkName);
            
            // Issues
            if (!empty($result->issues)) {
                $html .= '<div class="mb-2"><strong>Probleme:</strong></div>';
                $html .= '<ul class="list-disc list-inside space-y-1 text-sm">';
                foreach ($result->issues as $issue) {
                    $html .= sprintf('<li>%s</li>', $issue);
                }
                $html .= '</ul>';
            }
            
            // Suggestions
            if (!empty($result->suggestions)) {
                $html .= '<div class="mt-3 mb-2"><strong>Lösungsvorschläge:</strong></div>';
                $html .= '<ul class="list-disc list-inside space-y-1 text-sm text-gray-600">';
                foreach ($result->suggestions as $suggestion) {
                    $html .= sprintf('<li>%s</li>', $suggestion);
                }
                $html .= '</ul>';
            }
            
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Check if setup can be completed
     */
    protected function canCompleteSetup(): bool
    {
        if (!isset($this->healthCheckResults)) {
            return true; // Allow completion if no check has been run
        }
        
        // Only block if there are critical failures
        return empty($this->healthCheckResults->criticalFailures);
    }
    
    /**
     * Run health check action
     */
    public function runHealthCheck(): void
    {
        $this->dispatch('$refresh');
        
        Notification::make()
            ->title('Health Check läuft...')
            ->info()
            ->duration(2000)
            ->send();
    }
    
    /**
     * Attempt auto-fix action
     */
    public function attemptAutoFix(): void
    {
        if (!$this->company) {
            $this->company = $this->createTemporaryCompany();
        }
        
        $healthService = app(\App\Services\HealthCheckService::class);
        $healthService->setCompany($this->company);
        
        try {
            $fixes = $healthService->attemptAutoFix();
            
            $successCount = collect($fixes)->filter(fn($fix) => $fix['success'] ?? false)->count();
            $totalCount = count($fixes);
            
            if ($successCount > 0) {
                Notification::make()
                    ->title('Auto-Fix erfolgreich')
                    ->body(sprintf('%d von %d Problemen wurden behoben.', $successCount, $totalCount))
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title('Auto-Fix fehlgeschlagen')
                    ->body('Die Probleme konnten nicht automatisch behoben werden.')
                    ->warning()
                    ->send();
            }
            
            // Refresh the health check
            $this->dispatch('$refresh');
            
        } catch (\Exception $e) {
            Notification::make()
                ->title('Fehler beim Auto-Fix')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    /**
     * Create temporary company object from form data
     */
    protected function createTemporaryCompany(): Company
    {
        $company = new Company();
        $company->id = 0; // Temporary ID
        $company->name = $this->data['company_name'] ?? 'Temp Company';
        $company->industry = $this->data['industry'] ?? 'generic';
        
        // Set API keys if provided
        if (!empty($this->data['calcom_api_key'])) {
            $company->calcom_api_key = encrypt($this->data['calcom_api_key']);
        }
        
        if (!empty($this->data['retell_api_key'])) {
            $company->retell_api_key = encrypt($this->data['retell_api_key']);
        }
        
        // Add mock branches for health check
        $branch = new Branch();
        $branch->id = 0;
        $branch->name = $this->data['branch_name'] ?? 'Hauptfiliale';
        $branch->is_active = true;
        
        $company->setRelation('branches', collect([$branch]));
        
        return $company;
    }
    
    // Property to store health check results
    protected ?HealthReport $healthCheckResults = null;
    
    /**
     * Update progress during setup
     */
    protected function updateProgress(string $message, int $percentage): void
    {
        $this->currentProgressMessage = $message;
        $this->currentProgressPercentage = $percentage;
        
        // Force UI update
        $this->dispatch('progress-updated');
    }
    
    /**
     * Get mode selection step for choosing between new or edit
     */
    protected function getModeSelectionStep(): array
    {
        // Optimized query - only load what we need
        $companies = Company::select('id', 'name')
            ->where('is_active', true)
            ->orderBy('name')
            ->limit(100)
            ->get();
        
        if ($companies->isEmpty() || $this->editMode) {
            // Skip mode selection if no companies exist or already in edit mode
            return [];
        }
        
        return [
            Wizard\Step::make('Modus auswählen')
                ->description('Neue Firma oder bestehende bearbeiten?')
                ->icon('heroicon-o-cursor-arrow-rays')
                ->schema([
                    Section::make('Was möchten Sie tun?')
                        ->schema([
                            ToggleButtons::make('setup_mode')
                                ->label('Was möchten Sie tun?')
                                ->options([
                                    'new' => 'Neue Firma anlegen',
                                    'edit' => 'Bestehende Firma bearbeiten'
                                ])
                                ->default('new')
                                ->reactive()
                                ->icons([
                                    'new' => 'heroicon-o-plus-circle',
                                    'edit' => 'heroicon-o-pencil-square'
                                ])
                                ->inline(),
                                
                            Placeholder::make('mode_description')
                                ->content(fn($get) => match($get('setup_mode')) {
                                    'new' => 'Erstellen Sie eine komplett neue Firma mit allen Einstellungen.',
                                    'edit' => 'Bearbeiten Sie eine vorhandene Firma und deren Konfiguration.',
                                    default => ''
                                }),
                                
                            Select::make('existing_company_id')
                                ->label('Firma auswählen')
                                ->options($companies->pluck('name', 'id'))
                                ->searchable()
                                ->placeholder('Wählen Sie eine Firma...')
                                ->visible(fn($get) => $get('setup_mode') === 'edit')
                                ->reactive()
                                ->afterStateUpdated(function($state, $livewire) {
                                    if ($state) {
                                        // Use Livewire's redirect method
                                        $livewire->redirect(static::getUrl(['company' => $state]));
                                    }
                                })
                                ->helperText('Wählen Sie die Firma, die Sie bearbeiten möchten'),
                                
                            Placeholder::make('company_preview')
                                ->visible(fn($get) => $get('setup_mode') === 'edit' && $get('existing_company_id'))
                                ->content(function($get) {
                                    $companyId = $get('existing_company_id');
                                    if (!$companyId) return '';
                                    
                                    $company = Company::with('branches')->find($companyId);
                                    if (!$company) return '';
                                    
                                    $branchCount = $company->branches->count();
                                    $firstBranch = $company->branches->first();
                                    
                                    return new HtmlString(sprintf(
                                        '<div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                            <h4 class="font-semibold mb-2">%s</h4>
                                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                                Branche: %s<br>
                                                Standorte: %d<br>
                                                Hauptstandort: %s
                                            </p>
                                        </div>',
                                        $company->name,
                                        $company->industry ?? 'Nicht angegeben',
                                        $branchCount,
                                        $firstBranch ? $firstBranch->name . ' (' . $firstBranch->city . ')' : 'Keine'
                                    ));
                                }),
                        ]),
                ])
        ];
    }
    
    /**
     * Save Step 1: Company and Branches
     */
    protected function saveStep1Data(): void
    {
        try {
            DB::beginTransaction();
            
            // Create or update company
            if ($this->editMode && $this->editingCompany) {
                $this->editingCompany->update([
                    'name' => $this->data['company_name'],
                    'industry' => $this->data['industry'],
                    'logo' => $this->data['logo'] ?? null,
                ]);
                $company = $this->editingCompany;
            } else {
                $company = Company::create([
                    'name' => $this->data['company_name'],
                    'industry' => $this->data['industry'],
                    'logo' => $this->data['logo'] ?? null,
                    'is_active' => true,
                ]);
                $this->editingCompany = $company;
                $this->editMode = true;
            }
            
            // Save branches
            if (!empty($this->data['branches'])) {
                // Track processed branch IDs to handle deletions
                $processedBranchIds = [];
                
                foreach ($this->data['branches'] as $index => $branchData) {
                    if (!empty($branchData['name'])) {
                        if (!empty($branchData['id'])) {
                            $branch = Branch::where('id', $branchData['id'])
                                ->where('company_id', $company->id)
                                ->first();
                            
                            if ($branch) {
                                // Update existing branch
                                $updateData = [
                                    'name' => $branchData['name'],
                                    'city' => $branchData['city'],
                                    'address' => $branchData['address'] ?? null,
                                    'phone_number' => $branchData['phone_number'] ?? null,
                                    'features' => $branchData['features'] ?? [],
                                ];
                                
                                // Log the features being saved
                                Log::info('Updating branch features', [
                                    'branch_id' => $branch->id,
                                    'features' => $updateData['features']
                                ]);
                                
                                $branch->update($updateData);
                                $processedBranchIds[] = $branch->id;
                            }
                        } else {
                            // Create new branch only if it doesn't have an ID
                            $branch = Branch::create([
                                'company_id' => $company->id,
                                'name' => $branchData['name'],
                                'city' => $branchData['city'],
                                'address' => $branchData['address'] ?? null,
                                'phone_number' => $branchData['phone_number'] ?? null,
                                'features' => $branchData['features'] ?? [],
                                'is_active' => true,
                            ]);
                            $processedBranchIds[] = $branch->id;
                        }
                    }
                }
                
                // Delete branches that were removed from the form
                if ($this->editMode && !empty($processedBranchIds)) {
                    Branch::where('company_id', $company->id)
                        ->whereNotIn('id', $processedBranchIds)
                        ->delete();
                }
            }
            
            DB::commit();
            
            Notification::make()
                ->title('Schritt 1 gespeichert')
                ->body('Firmendaten und Filialen wurden gespeichert.')
                ->success()
                ->send();
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error saving step 1 data', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            Notification::make()
                ->title('Fehler beim Speichern')
                ->body('Die Daten konnten nicht gespeichert werden: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    /**
     * Save Step 2: Phone Configuration
     */
    protected function saveStep2Data(): void
    {
        if (!$this->editingCompany) {
            return;
        }
        
        try {
            DB::beginTransaction();
            
            // Save phone numbers for each branch
            $branches = $this->editingCompany->branches;
            
            foreach ($branches as $branch) {
                $this->setupPhoneNumbers($this->editingCompany, $branch);
            }
            
            DB::commit();
            
            Notification::make()
                ->title('Schritt 2 gespeichert')
                ->body('Telefonnummern wurden konfiguriert.')
                ->success()
                ->send();
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error saving step 2 data', [
                'error' => $e->getMessage()
            ]);
            
            Notification::make()
                ->title('Fehler beim Speichern')
                ->body('Die Telefonnummern konnten nicht gespeichert werden.')
                ->danger()
                ->send();
        }
    }
    
    /**
     * Save Step 3: Cal.com Configuration
     */
    protected function saveStep3Data(): void
    {
        if (!$this->editingCompany) {
            return;
        }
        
        try {
            $updateData = [];
            
            // Save connection type in company settings
            $settings = $this->editingCompany->settings ?? [];
            $settings['calcom_connection_type'] = $this->data['calcom_connection_type'] ?? 'api_key';
            $updateData['settings'] = $settings;
            
            // Only update API key if it's not the placeholder and not empty
            if (!empty($this->data['calcom_api_key']) && $this->data['calcom_api_key'] !== '[ENCRYPTED]') {
                $updateData['calcom_api_key'] = encrypt($this->data['calcom_api_key']);
            }
            
            // Always update team slug
            $updateData['calcom_team_slug'] = $this->data['calcom_team_slug'] ?? null;
            
            // Always update the company with all data
            $this->editingCompany->update($updateData);
            
            Notification::make()
                ->title('Schritt 3 gespeichert')
                ->body('Cal.com Konfiguration wurde gespeichert.')
                ->success()
                ->send();
                
        } catch (\Exception $e) {
            Log::error('Error saving step 3 data', [
                'error' => $e->getMessage()
            ]);
            
            Notification::make()
                ->title('Fehler beim Speichern')
                ->body('Die Cal.com Konfiguration konnte nicht gespeichert werden.')
                ->danger()
                ->send();
        }
    }
    
    /**
     * Save Step 4: Retell.ai Configuration
     */
    protected function saveStep4Data(): void
    {
        if (!$this->editingCompany) {
            return;
        }
        
        try {
            // Save all AI and communication preferences
            $settings = $this->editingCompany->settings ?? [];
            
            // AI voice settings
            $settings['phone_setup'] = $this->data['phone_setup'] ?? 'new';
            $settings['ai_voice'] = $this->data['ai_voice'] ?? 'sarah';
            $settings['use_template_greeting'] = $this->data['use_template_greeting'] ?? true;
            $settings['enable_test_call'] = $this->data['enable_test_call'] ?? false;
            
            if (!($this->data['use_template_greeting'] ?? true)) {
                $settings['custom_greeting'] = $this->data['custom_greeting'] ?? '';
            }
            
            // Communication settings
            $settings['global_sms_enabled'] = $this->data['global_sms_enabled'] ?? false;
            $settings['global_whatsapp_enabled'] = $this->data['global_whatsapp_enabled'] ?? false;
            
            // Import settings
            $settings['import_event_types'] = $this->data['import_event_types'] ?? true;
            $settings['create_new_agent'] = $this->data['create_new_agent'] ?? false;
            
            // Update company with settings and Retell configuration
            $updateData = ['settings' => $settings];
            
            // Save Retell API key if provided
            if (!empty($this->data['retell_api_key'])) {
                $updateData['retell_api_key'] = encrypt($this->data['retell_api_key']);
            }
            
            // Save Retell Agent ID
            if (!empty($this->data['retell_agent_id'])) {
                $updateData['retell_agent_id'] = $this->data['retell_agent_id'];
            }
            
            $this->editingCompany->update($updateData);
            
            Notification::make()
                ->title('Schritt 4 gespeichert')
                ->body('KI-Telefon Einstellungen wurden gespeichert.')
                ->success()
                ->send();
                
        } catch (\Exception $e) {
            Log::error('Error saving step 4 data', [
                'error' => $e->getMessage()
            ]);
            
            Notification::make()
                ->title('Fehler beim Speichern')
                ->body('Die KI-Telefon Einstellungen konnten nicht gespeichert werden.')
                ->danger()
                ->send();
        }
    }
    
    /**
     * Save Step 6: Services & Staff
     */
    protected function saveStep6Data(): void
    {
        if (!$this->editingCompany) {
            return;
        }
        
        try {
            DB::beginTransaction();
            
            // Save services if not using templates
            if (!($this->data['use_template_services'] ?? true) && !empty($this->data['custom_services'])) {
                foreach ($this->data['custom_services'] as $serviceData) {
                    if (!empty($serviceData['name'])) {
                        \App\Models\Service::create([
                            'company_id' => $this->editingCompany->id,
                            'name' => $serviceData['name'],
                            'duration' => $serviceData['duration'] ?? 30,
                            'price' => $serviceData['price'] ?? 0,
                            'is_active' => true,
                        ]);
                    }
                }
            }
            
            // Save staff members
            if (!empty($this->data['staff_members'])) {
                foreach ($this->data['staff_members'] as $staffData) {
                    if (!empty($staffData['name']) && !empty($staffData['email'])) {
                        \App\Models\Staff::create([
                            'company_id' => $this->editingCompany->id,
                            'branch_id' => $staffData['branch_id'] ?? $this->editingCompany->branches->first()?->id,
                            'name' => $staffData['name'],
                            'email' => $staffData['email'],
                            'languages' => $staffData['languages'] ?? ['de'],
                            'experience_level' => $staffData['experience_level'] ?? 2,
                            'skills' => $staffData['skills'] ?? [],
                            'certifications' => $staffData['certifications'] ?? [],
                            'is_active' => true,
                        ]);
                    }
                }
            }
            
            // Save additional settings
            $settings = $this->editingCompany->settings ?? [];
            $settings['use_template_services'] = $this->data['use_template_services'] ?? true;
            $settings['use_template_hours'] = $this->data['use_template_hours'] ?? true;
            $settings['post_setup_actions'] = $this->data['post_setup_actions'] ?? [];
            
            $this->editingCompany->update(['settings' => $settings]);
            
            DB::commit();
            
            Notification::make()
                ->title('Schritt 6 gespeichert')
                ->body('Services und Mitarbeiter wurden gespeichert.')
                ->success()
                ->send();
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error saving step 6 data', [
                'error' => $e->getMessage()
            ]);
            
            Notification::make()
                ->title('Fehler beim Speichern')
                ->body('Die Services und Mitarbeiter konnten nicht gespeichert werden.')
                ->danger()
                ->send();
        }
    }
}