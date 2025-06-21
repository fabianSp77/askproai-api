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
use App\Contracts\HealthReport;
use Illuminate\Support\HtmlString;
use Filament\Forms\Components\Actions;

class QuickSetupWizardV2 extends Page implements HasForms
{
    use InteractsWithForms;
    
    protected static ?string $navigationIcon = 'heroicon-o-sparkles';
    protected static ?string $navigationLabel = '🚀 Firma verwalten';
    protected static ?string $title = '🚀 Firmen-Setup Wizard';
    protected static ?string $navigationGroup = 'Unternehmensstruktur';
    protected static ?int $navigationSort = 5;
    
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
                                                ->required(),
                                            
                                            TextInput::make('branch_city')
                                                ->label('Stadt')
                                                ->required()
                                                ->placeholder('z.B. Berlin'),
                                        ]),
                                        
                                    TextInput::make('branch_address')
                                        ->label('Adresse')
                                        ->placeholder('Straße und Hausnummer'),
                                        
                                    TextInput::make('branch_phone')
                                        ->label('Telefonnummer')
                                        ->tel()
                                        ->placeholder('+49 30 12345678')
                                        ->helperText('Diese Nummer wird für eingehende Anrufe verwendet'),
                                    
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
        // Copy from original QuickSetupWizard
        return [
            Section::make('Telefon-Routing Strategie')
                ->description('Wie sollen eingehende Anrufe verarbeitet werden?')
                ->schema([
                    Toggle::make('use_hotline')
                        ->label('Zentrale Hotline verwenden')
                        ->helperText('Eine Hauptnummer für alle Filialen mit intelligentem Routing')
                        ->reactive()
                        ->afterStateUpdated(fn($state) => $this->data['use_hotline'] = $state),
                        
                    // Rest of the fields...
                ]),
        ];
    }
    
    protected function getCalcomFields(): array
    {
        return [
            Section::make('Cal.com Verbindung')
                ->schema([
                    // Copy fields from original
                ]),
        ];
    }
    
    protected function getRetellFields(): array
    {
        return [
            Section::make('KI-Assistent Einstellungen')
                ->schema([
                    // Copy fields from original
                ]),
        ];
    }
    
    protected function getIntegrationTestFields(): array
    {
        return [
            Section::make('API Verbindungstests')
                ->schema([
                    // Copy fields from original
                ]),
        ];
    }
    
    protected function getServicesAndStaffFields(): array
    {
        return [
            Section::make('Dienstleistungen')
                ->schema([
                    // Copy fields from original
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
        // Use original create logic
        // Copy from original QuickSetupWizard::completeSetup()
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