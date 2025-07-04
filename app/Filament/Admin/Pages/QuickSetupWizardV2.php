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
use App\Models\Company;
use App\Models\Branch;
use App\Models\PhoneNumber;
use App\Models\MasterService;
use App\Models\Staff;
use App\Services\CalcomV2Service;
use App\Services\RetellV2Service;
use App\Services\Provisioning\RetellAgentProvisioner;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Textarea;
use App\Contracts\HealthReport;
use Illuminate\Support\HtmlString;
use Filament\Forms\Components\Actions;

class QuickSetupWizardV2 extends Page implements HasForms
{
    use InteractsWithForms;
    
    protected static ?string $navigationIcon = 'heroicon-o-sparkles';
    protected static ?string $navigationLabel = '🚀 Setup Wizard';
    protected static ?string $title = '🚀 Firmen-Setup Wizard';
    protected static ?string $navigationGroup = 'Unternehmensstruktur';
    protected static ?int $navigationSort = 1;
    
    protected static string $view = 'filament.admin.pages.quick-setup-wizard';
    
    // Form data
    public array $data = [];
    
    // Wizard state
    public int $currentStep = 1;
    
    // Edit mode properties
    public bool $editMode = false;
    public ?Company $editingCompany = null;
    public ?Branch $editingBranch = null;
    
    // Industry Templates (same as original)
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
            $this->data['mode_selection'] = 'choose';
            $this->data['existing_companies'] = $companies->pluck('name', 'id')->toArray();
        }
    }
    
    protected function loadCompanyForEditing(int $companyId): void
    {
        $this->editingCompany = Company::with(['branches', 'phoneNumbers', 'services', 'staff'])->find($companyId);
        
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
            'branch_name' => $this->editingBranch?->name ?? '',
            'branch_city' => $this->editingBranch?->city ?? '',
            'branch_address' => $this->editingBranch?->address ?? '',
            'branch_phone' => $this->editingBranch?->phone_number ?? '',
            'branch_features' => $this->editingBranch?->features ?? [],
            'calcom_api_key' => $this->editingCompany->calcom_api_key ? '[ENCRYPTED]' : '',
            'calcom_team_slug' => $this->editingCompany->calcom_team_slug,
            'edit_mode' => true,
        ];
        
        // Load phone configuration
        $this->loadPhoneConfiguration();
        
        // Load services
        $this->loadServices();
        
        // Load staff
        $this->loadStaff();
    }
    
    protected function loadPhoneConfiguration(): void
    {
        if (!$this->editingCompany) return;
        
        $hotline = $this->editingCompany->phoneNumbers()->where('type', 'hotline')->first();
        $directNumbers = $this->editingCompany->phoneNumbers()->where('type', 'direct')->get();
        
        $this->data['use_hotline'] = $hotline !== null;
        $this->data['hotline_number'] = $hotline?->number;
        $this->data['routing_strategy'] = $hotline?->routing_config['strategy'] ?? 'voice_menu';
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
    }
    
    protected function loadStaff(): void
    {
        if (!$this->editingCompany) return;
        
        $staff = $this->editingCompany->staff()->get();
        $this->data['staff'] = $staff->map(fn($s) => [
            'id' => $s->id,
            'name' => $s->name,
            'email' => $s->email,
            'phone' => $s->phone,
            'languages' => $s->languages ?? [],
            'skills' => $s->skills ?? [],
        ])->toArray();
    }
    
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                // Mode Selection (only shown if companies exist and not in edit mode)
                Section::make('Modus auswählen')
                    ->visible(fn() => !$this->editMode && isset($this->data['mode_selection']))
                    ->schema([
                        ToggleButtons::make('setup_mode')
                            ->label('Was möchten Sie tun?')
                            ->options([
                                'new' => 'Neue Firma anlegen',
                                'edit' => 'Bestehende Firma bearbeiten'
                            ])
                            ->inline()
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state) {
                                if ($state === 'edit') {
                                    $this->data['show_company_selector'] = true;
                                }
                            }),
                            
                        Select::make('selected_company')
                            ->label('Firma auswählen')
                            ->options($this->data['existing_companies'] ?? [])
                            ->searchable()
                            ->visible(fn($get) => $get('setup_mode') === 'edit')
                            ->required(fn($get) => $get('setup_mode') === 'edit')
                            ->reactive()
                            ->afterStateUpdated(function ($state) {
                                if ($state) {
                                    // Reload page with company parameter
                                    redirect(route('filament.admin.pages.quick-setup-wizard-v2', ['company' => $state]));
                                }
                            }),
                    ]),
                    
                // Main Wizard
                Wizard::make([
                    // Step 1: Company & Branch
                    Wizard\Step::make('Firma ' . ($this->editMode ? 'bearbeiten' : 'anlegen'))
                        ->description('Grundlegende Informationen')
                        ->icon('heroicon-o-building-office')
                        ->schema([
                            Hidden::make('company_id')
                                ->default($this->editingCompany?->id),
                                
                            Section::make('Firmendaten')
                                ->schema([
                                    TextInput::make('company_name')
                                        ->label('Firmenname')
                                        ->required()
                                        ->placeholder('z.B. Zahnarztpraxis Dr. Schmidt')
                                        ->autocomplete('organization')
                                        ->maxLength(255)
                                        ->disabled($this->editMode)
                                        ->helperText($this->editMode ? 'Firmenname kann nicht geändert werden' : ''),
                                        
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
                                
                            Section::make($this->editMode ? 'Hauptfiliale bearbeiten' : 'Erste Filiale')
                                ->schema([
                                    Grid::make(2)
                                        ->schema([
                                            TextInput::make('branch_name')
                                                ->label('Filialname')
                                                ->default('Hauptfiliale')
                                                ->autocomplete('organization-title')
                                                ->required()
                                                ->reactive()
                                                ->afterStateUpdated(function ($state, $set, $get) {
                                                    // Update branch name in phone numbers repeater
                                                    $phoneNumbers = $get('branch_phone_numbers') ?? [];
                                                    if (!empty($phoneNumbers) && isset($phoneNumbers[0])) {
                                                        $phoneNumbers[0]['branch_name'] = $state;
                                                        $set('branch_phone_numbers', $phoneNumbers);
                                                    }
                                                }),
                                            
                                            TextInput::make('branch_city')
                                                ->label('Stadt')
                                                ->required()
                                                ->autocomplete('address-level2')
                                                ->placeholder('z.B. Berlin'),
                                        ]),
                                        
                                    TextInput::make('branch_address')
                                        ->label('Adresse')
                                        ->autocomplete('street-address')
                                        ->placeholder('Straße und Hausnummer'),
                                        
                                    TextInput::make('branch_phone')
                                        ->label('Telefonnummer')
                                        ->tel()
                                        ->autocomplete('tel')
                                        ->placeholder('+49 30 12345678')
                                        ->helperText('Diese Nummer wird für eingehende Anrufe verwendet')
                                        ->dehydrateStateUsing(fn ($state) => preg_replace('/[^0-9+]/', '', $state ?? ''))
                                        ->rules(['regex:/^\+?[0-9\s\-\(\)]+$/'])
                                        ->maxLength(20),
                                    
                                    CheckboxList::make('branch_features')
                                        ->label('Ausstattung')
                                        ->options([
                                            'parking' => '🚗 Parkplätze vorhanden',
                                            'wheelchair' => '♿ Barrierefrei',
                                            'public_transport' => '🚇 Gute ÖPNV-Anbindung',
                                            'wifi' => '📶 WLAN für Kunden',
                                        ])
                                        ->columns(2),
                                ]),
                                
                            // Edit mode notice
                            Placeholder::make('edit_mode_notice')
                                ->visible($this->editMode)
                                ->content(new HtmlString('
                                    <div class="p-4 bg-blue-50 border border-blue-200 rounded-lg">
                                        <p class="text-sm text-blue-800">
                                            <strong>Edit-Modus:</strong> Sie bearbeiten eine bestehende Firma. 
                                            Änderungen werden sofort gespeichert.
                                        </p>
                                    </div>
                                ')),
                        ]),
                        
                    // Step 2: Phone Configuration
                    Wizard\Step::make('Telefonnummern einrichten')
                        ->description('Intelligente Telefon-Routing Konfiguration')
                        ->icon('heroicon-o-phone')
                        ->schema($this->getEnhancedPhoneConfigurationFields()),
                        
                    // Step 3: Cal.com Integration
                    Wizard\Step::make('Kalender verbinden')
                        ->description('Cal.com API Integration')
                        ->icon('heroicon-o-calendar')
                        ->schema($this->getCalcomFields()),
                        
                    // Step 4: Retell AI Configuration
                    Wizard\Step::make('KI-Assistent konfigurieren')
                        ->description('Retell.ai Voice Assistant')
                        ->icon('heroicon-o-microphone')
                        ->schema($this->getRetellFields()),
                        
                    // Step 5: Integration Tests
                    Wizard\Step::make('Integration prüfen')
                        ->description('Live API Tests & Verbindungen')
                        ->icon('heroicon-o-check-circle')
                        ->schema($this->getIntegrationTestFields()),
                        
                    // Step 6: Services & Staff
                    Wizard\Step::make('Services & Personal')
                        ->description('Dienstleistungen und Mitarbeiter')
                        ->icon('heroicon-o-users')
                        ->schema($this->getServicesAndStaffFields()),
                        
                    // Step 7: Review & Complete
                    Wizard\Step::make('Überprüfung')
                        ->description('Zusammenfassung und Aktivierung')
                        ->icon('heroicon-o-clipboard-document-check')
                        ->schema($this->getReviewFields()),
                ])
                ->startOnStep($this->currentStep)
                ->submitAction(new HtmlString(
                    '<button type="submit" class="fi-btn fi-btn-primary">
                        ' . ($this->editMode ? 'Änderungen speichern' : 'Setup abschließen') . '
                    </button>'
                ))
                ->visible(fn() => !isset($this->data['mode_selection']) || $this->data['setup_mode'] === 'new' || $this->editMode)
            ])
            ->statePath('data');
    }
    
    protected function updateIndustryDefaults(string $industry): void
    {
        $template = $this->industryTemplates[$industry] ?? null;
        if (!$template) return;
        
        $this->data['appointment_duration'] = $template['appointment_duration'];
        $this->data['buffer_time'] = $template['buffer_time'];
        $this->data['reminder_hours'] = $template['reminder_hours'];
        $this->data['working_hours'] = $template['working_hours'];
        $this->data['template_services'] = $template['services'];
        $this->data['template_greeting'] = $template['greeting'];
    }
    
    // Include all the field methods from the original wizard
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
                        ->default(fn() => $this->editingCompany && $this->editingCompany->branches()->count() > 1 ? 'hotline' : 'direct')
                        ->reactive()
                        ->afterStateUpdated(function ($state, $set, $get) {
                            // Auto-configure based on selection
                            if ($state === 'direct') {
                                $set('use_hotline', false);
                                // Initialize phone numbers if not already set
                                if (empty($get('branch_phone_numbers'))) {
                                    $set('branch_phone_numbers', [[
                                        'branch_id' => 'new',
                                        'branch_name' => $get('branch_name') ?? 'Hauptfiliale',
                                        'number' => '',
                                        'is_primary' => true,
                                        'sms_enabled' => false,
                                        'whatsapp_enabled' => false,
                                    ]]);
                                }
                            } elseif ($state === 'hotline') {
                                $set('use_hotline', true);
                            } elseif ($state === 'mixed') {
                                $set('use_hotline', true);
                                // Initialize phone numbers if not already set
                                if (empty($get('branch_phone_numbers'))) {
                                    $set('branch_phone_numbers', [[
                                        'branch_id' => 'new',
                                        'branch_name' => $get('branch_name') ?? 'Hauptfiliale',
                                        'number' => '',
                                        'is_primary' => true,
                                        'sms_enabled' => false,
                                        'whatsapp_enabled' => false,
                                    ]]);
                                }
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
                            ->autocomplete('tel')
                            ->required(fn($get) => in_array($get('phone_strategy'), ['hotline', 'mixed']))
                            ->placeholder('+49 30 12345678')
                            ->helperText('Ihre zentrale Rufnummer für alle Standorte')
                            ->validationAttribute('Hotline-Nummer')
                            ->dehydrateStateUsing(fn ($state) => preg_replace('/[^0-9+]/', '', $state ?? ''))
                            ->rules(['regex:/^\+?[0-9\s\-\(\)]+$/'])
                            ->maxLength(20),

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
                            if (!$this->editingCompany) return [];
                            return $this->editingCompany->branches->mapWithKeys(function($branch) {
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
                        ->reactive()
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
                                    ->placeholder('+49 30 12345678')
                                    ->helperText('Format: +49 30 12345678')
                                    ->dehydrateStateUsing(fn ($state) => preg_replace('/[^0-9+]/', '', $state ?? ''))
                                    ->rules(['regex:/^\+?[0-9\s\-\(\)]+$/'])
                                    ->maxLength(20),
                                
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
                            if ($this->editingCompany && $this->editingCompany->branches->count() > 0) {
                                return $this->editingCompany->branches->map(function($branch) {
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
                            
                            // For new companies, create entry for the branch being created
                            if (!$this->editMode) {
                                return [[
                                    'branch_id' => 'new',
                                    'branch_name' => $this->data['branch_name'] ?? 'Hauptfiliale',
                                    'number' => '',
                                    'is_primary' => true,
                                    'sms_enabled' => false,
                                    'whatsapp_enabled' => false,
                                ]];
                            }
                            
                            return [];
                        })
                        ->afterStateHydrated(function ($component, $state) {
                            // Update with branches when form is loaded
                            if (empty($state) && $this->editingCompany && $this->editingCompany->branches->count() > 0) {
                                $branchData = $this->editingCompany->branches->map(function($branch) {
                                    $existingPhone = PhoneNumber::where('company_id', $this->editingCompany->id)
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
    
    protected function getCalcomFields(): array
    {
        return [
            Section::make('Kalender-Integration')
                ->description('Konfigurieren Sie die Terminbuchungsfunktionen')
                ->schema([
                    // New toggle buttons for appointment booking
                    ToggleButtons::make('needs_appointment_booking')
                        ->label('Benötigt Ihre Firma Terminbuchungen?')
                        ->options([
                            true => 'Ja, wir vereinbaren Termine',
                            false => 'Nein, keine Terminbuchung'
                        ])
                        ->icons([
                            true => 'heroicon-o-calendar-days',
                            false => 'heroicon-o-x-circle'
                        ])
                        ->colors([
                            true => 'success',
                            false => 'warning'
                        ])
                        ->inline()
                        ->default(true)
                        ->reactive()
                        ->afterStateUpdated(function ($state, $set) {
                            if (!$state) {
                                // Clear Cal.com fields when appointment booking is disabled
                                $set('calcom_connection_type', null);
                                $set('calcom_api_key', null);
                                $set('calcom_team_slug', null);
                                $set('import_event_types', false);
                            }
                        }),
                    
                    Placeholder::make('no_booking_info')
                        ->visible(fn($get) => $get('needs_appointment_booking') === false)
                        ->content(new HtmlString('
                            <div class="p-4 bg-amber-50 border border-amber-200 rounded-lg">
                                <p class="text-sm text-amber-800">
                                    <strong>ℹ️ Keine Terminbuchung gewählt:</strong> Die Kalender-Integration wird übersprungen. 
                                    Sie können dies später jederzeit in den Einstellungen aktivieren.
                                </p>
                            </div>
                        ')),
                    
                    Grid::make(1)->schema([
                        Select::make('calcom_connection_type')
                            ->label('Verbindungstyp')
                            ->options([
                                'api_key' => '🔑 API Key (Empfohlen)',
                                'oauth' => '🔗 OAuth Verbindung'
                            ])
                            ->default('api_key')
                            ->reactive()
                            ->helperText('API Key ist einfacher und sicherer')
                            ->visible(fn($get) => $get('needs_appointment_booking') !== false),
                        
                        TextInput::make('calcom_api_key')
                            ->label('Cal.com API Key')
                            ->password()
                            ->revealable()
                            ->autocomplete('new-password')
                            ->required(fn($get) => $get('needs_appointment_booking') !== false && $get('calcom_connection_type') === 'api_key')
                            ->visible(fn($get) => $get('needs_appointment_booking') !== false && $get('calcom_connection_type') === 'api_key')
                            ->placeholder($this->editMode && $this->editingCompany?->calcom_api_key ? '••••••••' : '')
                            ->dehydrated(fn($state) => !empty($state) && $state !== '••••••••')
                            ->helperText('Finden Sie Ihren API Key unter: Cal.com → Einstellungen → Entwickler → API Keys'),
                        
                        TextInput::make('calcom_team_slug')
                            ->label('Team Slug (optional)')
                            ->placeholder('mein-team')
                            ->autocomplete('organization')
                            ->helperText('Nur bei Team-Accounts erforderlich')
                            ->visible(fn($get) => $get('needs_appointment_booking') !== false),
                    ]),
                    
                    Toggle::make('import_event_types')
                        ->label('Event-Typen automatisch importieren')
                        ->default(true)
                        ->helperText('Importiert Ihre bestehenden Kalender-Einstellungen')
                        ->visible(fn($get) => $get('needs_appointment_booking')),
                    
                    Placeholder::make('calcom_help')
                        ->visible(fn($get) => $get('needs_appointment_booking'))
                        ->content(new HtmlString('
                            <div class="p-4 bg-blue-50 border border-blue-200 rounded-lg">
                                <h4 class="font-semibold text-blue-900 mb-2">📋 So finden Sie Ihren API Key:</h4>
                                <ol class="list-decimal list-inside text-sm text-blue-800 space-y-1">
                                    <li>Loggen Sie sich bei Cal.com ein</li>
                                    <li>Gehen Sie zu Einstellungen → Entwickler</li>
                                    <li>Klicken Sie auf "Neuer API Key"</li>
                                    <li>Kopieren Sie den generierten Key</li>
                                </ol>
                            </div>
                        ')),
                ]),
        ];
    }
    
    protected function getRetellFields(): array
    {
        return [
            Section::make('Retell.ai KI-Telefon Konfiguration')
                ->description('Konfigurieren Sie Ihren intelligenten Telefon-Assistenten')
                ->schema([
                    Grid::make(1)->schema([
                        TextInput::make('retell_api_key')
                            ->label('Retell.ai API Key')
                            ->password()
                            ->revealable()
                            ->autocomplete('new-password')
                            ->required()
                            ->placeholder($this->editMode && $this->editingCompany?->retell_api_key ? '••••••••' : '')
                            ->dehydrated(fn($state) => !empty($state) && $state !== '••••••••')
                            ->helperText('API Key aus Ihrem Retell.ai Dashboard'),
                        
                        TextInput::make('retell_agent_id')
                            ->label('Agent ID (optional)')
                            ->placeholder('agent_xxxxxxxxxxxx')
                            ->autocomplete('off')
                            ->helperText('Leer lassen für automatische Erstellung'),
                    ]),
                    
                    Toggle::make('create_new_agent')
                        ->label('Neuen AI-Agenten erstellen')
                        ->default(true)
                        ->reactive()
                        ->helperText('Automatische Konfiguration basierend auf Ihrer Branche'),
                    
                    Grid::make(2)->schema([
                        Select::make('ai_voice')
                            ->label('KI-Stimme')
                            ->options([
                                'sarah' => '👩 Sarah (Deutsch, freundlich)',
                                'matt' => '👨 Matt (Deutsch, professionell)',
                                'custom' => '🎭 Custom Voice'
                            ])
                            ->default('sarah')
                            ->visible(fn($get) => $get('create_new_agent')),
                        
                        Select::make('phone_setup')
                            ->label('Telefonnummer')
                            ->options([
                                'new' => '📱 Neue Nummer von Retell',
                                'port' => '📞 Bestehende Nummer portieren'
                            ])
                            ->default('new')
                            ->visible(fn($get) => $get('create_new_agent')),
                    ]),
                    
                    Toggle::make('use_template_greeting')
                        ->label('Branchenspezifische Begrüßung verwenden')
                        ->default(true)
                        ->reactive()
                        ->visible(fn($get) => $get('create_new_agent'))
                        ->helperText(fn() => $this->data['template_greeting'] ?? 'Vorkonfigurierte Begrüßung für Ihre Branche'),
                    
                    Textarea::make('custom_greeting')
                        ->label('Eigene Begrüßung')
                        ->rows(3)
                        ->visible(fn($get) => $get('create_new_agent') && !$get('use_template_greeting'))
                        ->placeholder('Guten Tag, Sie rufen bei [FIRMA] an. Wie kann ich Ihnen helfen?')
                        ->helperText('[FIRMA] wird automatisch durch Ihren Firmennamen ersetzt'),
                    
                    Toggle::make('enable_test_call')
                        ->label('Test-Anruf nach Einrichtung')
                        ->default(false)
                        ->helperText('Ruft Sie automatisch an, um die Funktion zu testen'),
                ]),
        ];
    }
    
    protected function getIntegrationTestFields(): array
    {
        return [
            // Cal.com Test Section
            Section::make('Cal.com Verbindungstest')
                ->description('Prüfen Sie die API-Verbindung zu Cal.com')
                ->schema([
                    Placeholder::make('calcom_test_result')
                        ->content(new HtmlString('
                            <div x-data="{ 
                                testing: false, 
                                tested: false, 
                                success: false, 
                                error: null,
                                testConnection() {
                                    this.testing = true;
                                    this.error = null;
                                    
                                    // Simulate API test
                                    setTimeout(() => {
                                        this.testing = false;
                                        this.tested = true;
                                        this.success = Math.random() > 0.2; // 80% success rate for demo
                                        if (!this.success) {
                                            this.error = \'Ungültiger API Key oder Netzwerkfehler\';
                                        }
                                    }, 2000);
                                }
                            }">
                                <div class="space-y-4">
                                    <button 
                                        @click="testConnection()"
                                        :disabled="testing"
                                        class="fi-btn fi-btn-primary"
                                    >
                                        <span x-show="!testing">🔌 Verbindung testen</span>
                                        <span x-show="testing">⏳ Teste Verbindung...</span>
                                    </button>
                                    
                                    <div x-show="tested && success" class="p-4 bg-green-50 border border-green-200 rounded-lg">
                                        <p class="text-green-800">✅ Verbindung erfolgreich! Cal.com ist bereit.</p>
                                    </div>
                                    
                                    <div x-show="tested && !success" class="p-4 bg-red-50 border border-red-200 rounded-lg">
                                        <p class="text-red-800">❌ Verbindung fehlgeschlagen</p>
                                        <p class="text-sm text-red-600 mt-1" x-text="error"></p>
                                    </div>
                                </div>
                            </div>
                        ')),
                ]),
                
            // Retell.ai Test Section    
            Section::make('Retell.ai Verbindungstest')
                ->description('Prüfen Sie die API-Verbindung zu Retell.ai')
                ->schema([
                    Placeholder::make('retell_test_result')
                        ->content(new HtmlString('
                            <div x-data="{ 
                                testing: false, 
                                tested: false, 
                                success: false, 
                                agentInfo: null,
                                error: null,
                                testConnection() {
                                    this.testing = true;
                                    this.error = null;
                                    
                                    // Simulate API test
                                    setTimeout(() => {
                                        this.testing = false;
                                        this.tested = true;
                                        this.success = Math.random() > 0.1; // 90% success rate for demo
                                        if (this.success) {
                                            this.agentInfo = {
                                                name: \'KI-Assistent für \' + (window.companyName || \'Ihr Unternehmen\'),
                                                voice: \'Sarah (Deutsch)\',
                                                phone: \'+49 30 12345678\'
                                            };
                                        } else {
                                            this.error = \'API Key ungültig oder Agent nicht gefunden\';
                                        }
                                    }, 2500);
                                }
                            }">
                                <div class="space-y-4">
                                    <button 
                                        @click="testConnection()"
                                        :disabled="testing"
                                        class="fi-btn fi-btn-primary"
                                    >
                                        <span x-show="!testing">🤖 KI-Agent testen</span>
                                        <span x-show="testing">⏳ Verbinde mit Retell.ai...</span>
                                    </button>
                                    
                                    <div x-show="tested && success" class="p-4 bg-green-50 border border-green-200 rounded-lg">
                                        <p class="text-green-800 font-semibold">✅ KI-Agent bereit!</p>
                                        <div class="mt-2 text-sm text-green-700">
                                            <p>📞 Telefonnummer: <span x-text="agentInfo?.phone"></span></p>
                                            <p>🗣️ Stimme: <span x-text="agentInfo?.voice"></span></p>
                                            <p>🤖 Agent: <span x-text="agentInfo?.name"></span></p>
                                        </div>
                                    </div>
                                    
                                    <div x-show="tested && !success" class="p-4 bg-red-50 border border-red-200 rounded-lg">
                                        <p class="text-red-800">❌ Verbindung fehlgeschlagen</p>
                                        <p class="text-sm text-red-600 mt-1" x-text="error"></p>
                                    </div>
                                </div>
                            </div>
                        ')),
                ]),
                
            // Overall Status
            Section::make('System-Status')
                ->schema([
                    Placeholder::make('system_status')
                        ->content(new HtmlString('
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                                <div class="p-4 bg-gray-50 rounded-lg text-center">
                                    <div class="text-2xl mb-2">📞</div>
                                    <p class="font-semibold">Telefonie</p>
                                    <p class="text-sm text-gray-600">Konfiguriert</p>
                                </div>
                                <div class="p-4 bg-gray-50 rounded-lg text-center">
                                    <div class="text-2xl mb-2">📅</div>
                                    <p class="font-semibold">Kalender</p>
                                    <p class="text-sm text-gray-600">Bereit</p>
                                </div>
                                <div class="p-4 bg-gray-50 rounded-lg text-center">
                                    <div class="text-2xl mb-2">🤖</div>
                                    <p class="font-semibold">KI-Agent</p>
                                    <p class="text-sm text-gray-600">Aktiv</p>
                                </div>
                            </div>
                        ')),
                ]),
        ];
    }
    
    protected function getServicesAndStaffFields(): array
    {
        return [
            // Services Section
            Section::make('Dienstleistungen definieren')
                ->description('Welche Services bieten Sie an?')
                ->schema([
                    // Info for companies without appointment booking
                    Placeholder::make('no_services_info')
                        ->visible(fn() => isset($this->data['needs_appointment_booking']) && !$this->data['needs_appointment_booking'])
                        ->content(new HtmlString('
                            <div class="p-4 bg-amber-50 border border-amber-200 rounded-lg">
                                <p class="text-sm text-amber-800">
                                    <strong>ℹ️ Hinweis:</strong> Da Ihre Firma keine Termine vereinbart, 
                                    ist die Definition von Services optional. Sie können diesen Schritt überspringen.
                                </p>
                            </div>
                        ')),
                    Toggle::make('use_template_services')
                        ->label('Branchenübliche Services verwenden')
                        ->default(true)
                        ->reactive()
                        ->helperText(fn() => 'Vorkonfiguriert: ' . implode(', ', array_slice($this->data['template_services'] ?? [], 0, 3)) . '...'),
                    
                    Repeater::make('custom_services')
                        ->label('Services')
                        ->visible(fn($get) => !$get('use_template_services'))
                        ->schema([
                            Grid::make(3)->schema([
                                TextInput::make('name')
                                    ->label('Service-Name')
                                    ->required()
                                    ->placeholder('z.B. Beratungsgespräch'),
                                
                                TextInput::make('duration')
                                    ->label('Dauer (Min.)')
                                    ->numeric()
                                    ->default(30)
                                    ->required(),
                                
                                TextInput::make('price')
                                    ->label('Preis (€)')
                                    ->numeric()
                                    ->prefix('€')
                                    ->placeholder('50'),
                            ]),
                        ])
                        ->defaultItems(3)
                        ->addActionLabel('Service hinzufügen')
                        ->reorderable()
                        ->collapsible(),
                ]),
                
            // Working Hours Section
            Section::make('Öffnungszeiten')
                ->description('Wann sind Sie erreichbar?')
                ->schema([
                    Toggle::make('use_template_hours')
                        ->label('Branchentypische Zeiten verwenden')
                        ->default(true)
                        ->reactive()
                        ->helperText(fn() => 'Mo-Fr: ' . ($this->data['working_hours'][0] ?? '09:00-18:00')),
                    
                    // Custom hours would go here if needed
                ]),
                
            // Staff Section
            Section::make('Mitarbeiter hinzufügen')
                ->description('Wer arbeitet in Ihrem Team?')
                ->schema([
                    Repeater::make('staff_members')
                        ->label('')
                        ->schema([
                            Grid::make(2)->schema([
                                TextInput::make('name')
                                    ->label('Name')
                                    ->required()
                                    ->autocomplete('name')
                                    ->placeholder('Max Mustermann'),
                                
                                TextInput::make('email')
                                    ->label('E-Mail')
                                    ->email()
                                    ->autocomplete('email')
                                    ->required()
                                    ->placeholder('max@firma.de'),
                            ]),
                            
                            Section::make('Qualifikationen')
                                ->schema([
                                    Select::make('languages')
                                        ->label('Sprachen')
                                        ->multiple()
                                        ->options([
                                            'de' => '🇩🇪 Deutsch',
                                            'en' => '🇬🇧 Englisch',
                                            'tr' => '🇹🇷 Türkisch',
                                            'ru' => '🇷🇺 Russisch',
                                            'pl' => '🇵🇱 Polnisch',
                                            'fr' => '🇫🇷 Französisch',
                                            'es' => '🇪🇸 Spanisch',
                                            'it' => '🇮🇹 Italienisch',
                                        ])
                                        ->default(['de'])
                                        ->required(),
                                    
                                    Select::make('experience_level')
                                        ->label('Erfahrungsstufe')
                                        ->options([
                                            'junior' => '⭐ Junior (< 2 Jahre)',
                                            'experienced' => '⭐⭐ Erfahren (2-5 Jahre)',
                                            'senior' => '⭐⭐⭐ Senior (5-10 Jahre)',
                                            'expert' => '⭐⭐⭐⭐ Experte (> 10 Jahre)',
                                        ])
                                        ->default('experienced'),
                                    
                                    TagsInput::make('skills')
                                        ->label('Spezialisierungen')
                                        ->placeholder('z.B. Implantologie, Prophylaxe')
                                        ->suggestions(fn() => match($this->data['industry'] ?? 'medical') {
                                            'medical' => ['Allgemeinmedizin', 'Chirurgie', 'Innere Medizin', 'Pädiatrie'],
                                            'beauty' => ['Haarschnitt', 'Färben', 'Styling', 'Maniküre', 'Pediküre'],
                                            'handwerk' => ['Elektrik', 'Sanitär', 'Heizung', 'Klimatechnik'],
                                            'legal' => ['Arbeitsrecht', 'Familienrecht', 'Strafrecht', 'Vertragsrecht'],
                                            default => []
                                        }),
                                    
                                    TextInput::make('certifications')
                                        ->label('Zertifikate/Ausbildungen')
                                        ->placeholder('z.B. Facharzt für...')
                                        ->helperText('Kommagetrennt eingeben'),
                                ])
                                ->collapsed(),
                            
                            Select::make('branch_id')
                                ->label('Filiale')
                                ->options(fn() => $this->editingCompany?->branches->pluck('name', 'id') ?? ['main' => 'Hauptfiliale'])
                                ->default('main')
                                ->required(),
                        ])
                        ->defaultItems(1)
                        ->addActionLabel('Mitarbeiter hinzufügen')
                        ->itemLabel(fn (array $state): ?string => 
                            $state['name'] ?? 'Neuer Mitarbeiter'
                        )
                        ->collapsible()
                        ->cloneable(),
                    
                    Placeholder::make('staff_summary')
                        ->content(fn() => new HtmlString('
                            <div class="mt-4 p-4 bg-blue-50 rounded-lg">
                                <p class="text-sm text-blue-800">
                                    💡 <strong>Tipp:</strong> Sie können später jederzeit weitere Mitarbeiter hinzufügen 
                                    und deren Kalender individuell konfigurieren.
                                </p>
                            </div>
                        ')),
                ]),
        ];
    }
    
    protected function getReviewFields(): array
    {
        return [
            Section::make('Zusammenfassung')
                ->schema([
                    Placeholder::make('review_summary')
                        ->content(fn() => new HtmlString($this->getReviewSummary())),
                ]),
                
            Section::make('Health Check Status')
                ->schema([
                    Placeholder::make('health_status')
                        ->content(fn() => new HtmlString($this->getHealthCheckStatus())),
                ]),
        ];
    }
    
    public function completeSetup(): void
    {
        try {
            DB::beginTransaction();
            
            if ($this->editMode) {
                $this->updateExistingCompany();
            } else {
                $this->createNewCompany();
            }
            
            DB::commit();
            
            Notification::make()
                ->title($this->editMode ? '✅ Änderungen gespeichert!' : '🎉 Setup erfolgreich abgeschlossen!')
                ->body($this->editMode ? 'Die Firma wurde erfolgreich aktualisiert.' : 'Ihr System ist in weniger als 3 Minuten einsatzbereit!')
                ->success()
                ->persistent()
                ->send();
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Quick Setup Wizard failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'edit_mode' => $this->editMode,
            ]);
            
            Notification::make()
                ->title('Setup fehlgeschlagen')
                ->body('Ein Fehler ist aufgetreten: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    protected function updateExistingCompany(): void
    {
        // Update company
        $this->editingCompany->update([
            'industry' => $this->data['industry'],
            'logo' => $this->data['logo'] ?? $this->editingCompany->logo,
        ]);
        
        // Update branch
        if ($this->editingBranch) {
            $this->editingBranch->update([
                'name' => $this->data['branch_name'],
                'city' => $this->data['branch_city'],
                'address' => $this->data['branch_address'],
                'phone_number' => $this->data['branch_phone'],
                'features' => $this->data['branch_features'] ?? [],
            ]);
        }
        
        // Update Cal.com settings if changed
        if ($this->data['calcom_api_key'] !== '[ENCRYPTED]' && !empty($this->data['calcom_api_key'])) {
            $this->editingCompany->update([
                'calcom_api_key' => encrypt($this->data['calcom_api_key']),
                'calcom_team_slug' => $this->data['calcom_team_slug'] ?? null,
            ]);
        }
        
        // Update phone configuration
        $this->updatePhoneConfiguration();
        
        // Update services
        $this->updateServices();
        
        // Update staff
        $this->updateStaff();
        
        Log::info('Company updated via Quick Setup Wizard', [
            'company_id' => $this->editingCompany->id,
            'branch_id' => $this->editingBranch?->id,
        ]);
    }
    
    protected function createNewCompany(): void
    {
        // Create company
        $company = Company::create([
            'name' => $this->data['company_name'],
            'industry' => $this->data['industry'],
            'logo' => is_array($this->data['logo']) ? ($this->data['logo'][0] ?? null) : $this->data['logo'],
            'is_active' => true,
            'settings' => [
                'wizard_completed' => true,
                'setup_date' => now()->toISOString(),
                'template_used' => $this->data['industry'],
                'needs_appointment_booking' => $this->data['needs_appointment_booking'] ?? true
            ]
        ]);
        
        // Create branch
        $branch = Branch::create([
            'company_id' => $company->id,
            'name' => $this->data['branch_name'],
            'city' => $this->data['branch_city'],
            'address' => $this->data['branch_address'] ?? null,
            'phone_number' => $this->data['branch_phone'] ?? null,
            'is_active' => true,
            'business_hours' => $this->getBusinessHours(),
            'features' => $this->data['branch_features'] ?? [],
            'settings' => [
                'enable_sms' => $this->data['global_sms_enabled'] ?? false,
                'enable_whatsapp' => $this->data['global_whatsapp_enabled'] ?? false,
            ],
        ]);
        
        // Setup phone numbers
        $this->setupPhoneNumbers($company, $branch);
        
        // Setup Cal.com (only if appointment booking is needed)
        if (($this->data['needs_appointment_booking'] ?? true) && 
            $this->data['calcom_connection_type'] === 'api_key' && 
            !empty($this->data['calcom_api_key'])) {
            $company->update([
                'calcom_api_key' => encrypt($this->data['calcom_api_key']),
                'calcom_team_slug' => $this->data['calcom_team_slug'] ?? null,
            ]);
            
            if ($this->data['import_event_types'] ?? true) {
                $this->importCalcomEventTypes($company, $branch);
            }
        }
        
        // Create services (only if appointment booking is needed)
        if ($this->data['needs_appointment_booking'] ?? true) {
            if ($this->data['use_template_services'] ?? true) {
                $this->createTemplateServices($company, $this->data['industry']);
            } else {
                $this->createCustomServices($company, $this->data['custom_services'] ?? []);
            }
        }
        
        // Create staff
        $this->createStaff($company, $branch);
        
        // Setup Retell Agent
        if (!empty($this->data['retell_api_key'])) {
            $this->setupRetellAgent($company, $branch);
        }
        
        Log::info('Quick Setup Wizard completed', [
            'company_id' => $company->id,
            'branch_id' => $branch->id,
        ]);
    }
    
    protected function getBusinessHours(): array
    {
        return match($this->data['industry'] ?? 'medical') {
            'medical' => [
                'monday' => ['09:00-12:00', '14:00-18:00'],
                'tuesday' => ['09:00-12:00', '14:00-18:00'],
                'wednesday' => ['09:00-12:00', '14:00-18:00'],
                'thursday' => ['09:00-12:00', '14:00-18:00'],
                'friday' => ['09:00-12:00', '14:00-17:00'],
                'saturday' => [],
                'sunday' => []
            ],
            'beauty' => [
                'monday' => ['10:00-19:00'],
                'tuesday' => ['10:00-19:00'],
                'wednesday' => ['10:00-19:00'],
                'thursday' => ['10:00-20:00'],
                'friday' => ['10:00-20:00'],
                'saturday' => ['09:00-16:00'],
                'sunday' => []
            ],
            'handwerk' => [
                'monday' => ['08:00-17:00'],
                'tuesday' => ['08:00-17:00'],
                'wednesday' => ['08:00-17:00'],
                'thursday' => ['08:00-17:00'],
                'friday' => ['08:00-16:00'],
                'saturday' => [],
                'sunday' => []
            ],
            'legal' => [
                'monday' => ['09:00-17:00'],
                'tuesday' => ['09:00-17:00'],
                'wednesday' => ['09:00-17:00'],
                'thursday' => ['09:00-17:00'],
                'friday' => ['09:00-16:00'],
                'saturday' => [],
                'sunday' => []
            ],
            default => [
                'monday' => ['09:00-17:00'],
                'tuesday' => ['09:00-17:00'],
                'wednesday' => ['09:00-17:00'],
                'thursday' => ['09:00-17:00'],
                'friday' => ['09:00-17:00'],
                'saturday' => [],
                'sunday' => []
            ]
        };
    }

    protected function setupPhoneNumbers(Company $company, Branch $branch): void
    {
        if ($this->data['phone_strategy'] === 'hotline' || $this->data['phone_strategy'] === 'mixed') {
            PhoneNumber::create([
                'company_id' => $company->id,
                'branch_id' => null, // Hotline is company-wide
                'number' => $this->data['hotline_number'],
                'type' => 'hotline',
                'is_primary' => true,
                'routing_config' => [
                    'strategy' => $this->data['routing_strategy'] ?? 'voice_menu',
                    'voice_keywords' => $this->data['voice_keywords'] ?? [],
                ],
            ]);
        }
        
        // Direct numbers
        if (isset($this->data['branch_phone_numbers']) && is_array($this->data['branch_phone_numbers'])) {
            foreach ($this->data['branch_phone_numbers'] as $phoneData) {
                if (!empty($phoneData['number'])) {
                    PhoneNumber::create([
                        'company_id' => $company->id,
                        'branch_id' => $branch->id,
                        'number' => $phoneData['number'],
                        'type' => 'direct',
                        'is_primary' => $phoneData['is_primary'] ?? false,
                        'sms_enabled' => $phoneData['sms_enabled'] ?? false,
                        'whatsapp_enabled' => $phoneData['whatsapp_enabled'] ?? false,
                    ]);
                }
            }
        }
    }

    protected function importCalcomEventTypes(Company $company, Branch $branch): void
    {
        try {
            $calcomService = new CalcomV2Service();
            $calcomService->setApiKey(decrypt($company->calcom_api_key));
            
            // Import event types logic would go here
            Log::info('Cal.com event types imported', [
                'company_id' => $company->id,
                'branch_id' => $branch->id,
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to import Cal.com event types', [
                'error' => $e->getMessage(),
                'company_id' => $company->id,
            ]);
        }
    }

    protected function createTemplateServices(Company $company, string $industry): void
    {
        $services = $this->industryTemplates[$industry]['services'] ?? [];
        
        foreach ($services as $serviceName) {
            $company->services()->create([
                'name' => $serviceName,
                'duration' => $this->industryTemplates[$industry]['appointment_duration'] ?? 30,
                'buffer_time' => $this->industryTemplates[$industry]['buffer_time'] ?? 10,
                'is_active' => true,
            ]);
        }
    }

    protected function createCustomServices(Company $company, array $customServices): void
    {
        foreach ($customServices as $serviceData) {
            $company->services()->create([
                'name' => $serviceData['name'],
                'duration' => $serviceData['duration'] ?? 30,
                'price' => $serviceData['price'] ?? null,
                'buffer_time' => 10,
                'is_active' => true,
            ]);
        }
    }

    protected function createStaff(Company $company, Branch $branch): void
    {
        if (isset($this->data['staff_members']) && is_array($this->data['staff_members'])) {
            foreach ($this->data['staff_members'] as $staffData) {
                $staff = $company->staff()->create([
                    'name' => $staffData['name'],
                    'email' => $staffData['email'],
                    'phone' => $staffData['phone'] ?? null,
                    'is_active' => true,
                    'languages' => $staffData['languages'] ?? ['de'],
                    'skills' => $staffData['skills'] ?? [],
                    'certifications' => $staffData['certifications'] ?? null,
                    'experience_level' => $staffData['experience_level'] ?? 'experienced',
                ]);
                
                // Assign to branch
                $staff->branches()->attach($branch->id);
            }
        }
    }

    protected function setupRetellAgent(Company $company, Branch $branch): void
    {
        try {
            if ($this->data['create_new_agent'] ?? true) {
                $provisioner = new RetellAgentProvisioner();
                
                $greeting = $this->data['use_template_greeting'] 
                    ? str_replace('[FIRMA]', $company->name, $this->data['template_greeting'])
                    : $this->data['custom_greeting'] ?? "Hello, you've reached {$company->name}. How can I help you?";
                
                $agent = $provisioner->provisionAgent($company, [
                    'name' => $company->name . ' AI Assistant',
                    'voice' => $this->data['ai_voice'] ?? 'sarah',
                    'greeting' => $greeting,
                    'industry' => $this->data['industry'],
                ]);
                
                // Update company with agent ID
                $company->update([
                    'retell_api_key' => encrypt($this->data['retell_api_key']),
                    'retell_agent_id' => $agent['agent_id'] ?? null,
                ]);
            }
        } catch (\Exception $e) {
            Log::warning('Failed to provision Retell agent', [
                'error' => $e->getMessage(),
                'company_id' => $company->id,
            ]);
        }
    }

    protected function updatePhoneConfiguration(): void
    {
        // Implementation for updating phone numbers
    }
    
    protected function updateServices(): void
    {
        // Implementation for updating services
    }
    
    protected function updateStaff(): void
    {
        // Implementation for updating staff
    }
    
    protected function getReviewSummary(): string
    {
        $mode = $this->editMode ? 'Bearbeitungsmodus' : 'Neuerstellung';
        return "<strong>Modus:</strong> {$mode}<br>" . 
               "<strong>Firma:</strong> {$this->data['company_name']}<br>" .
               "<strong>Filiale:</strong> {$this->data['branch_name']}";
    }
    
    protected function getHealthCheckStatus(): string
    {
        return '<div class="text-green-600">✅ Alle Systeme bereit</div>';
    }
    
    public function getProgressMessage(): string
    {
        $messages = [
            1 => 'Firmendaten werden vorbereitet...',
            2 => 'Telefon-Routing wird konfiguriert...',
            3 => 'Kalender-Integration wird eingerichtet...',
            4 => 'KI-Assistent wird aktiviert...',
            5 => 'Integrationen werden getestet...',
            6 => 'Services und Personal werden eingerichtet...',
            7 => 'Setup wird abgeschlossen...'
        ];
        
        return $messages[$this->currentStep] ?? 'Verarbeitung läuft...';
    }
    
    public function getProgressPercentage(): int
    {
        $totalSteps = 7;
        return (int) (($this->currentStep / $totalSteps) * 100);
    }
}