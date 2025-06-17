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
use App\Services\CalcomV2Service;
use App\Services\RetellV2Service;
use App\Services\Provisioning\RetellAgentProvisioner;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class QuickSetupWizard extends Page implements HasForms
{
    use InteractsWithForms;
    
    protected static ?string $navigationIcon = 'heroicon-o-sparkles';
    protected static ?string $navigationLabel = '🚀 Quick Setup (3 Min)';
    protected static ?string $title = '🚀 3-Minuten Setup Wizard';
    protected static ?string $navigationGroup = 'Einrichtung';
    protected static ?int $navigationSort = 1;
    
    protected static string $view = 'filament.admin.pages.quick-setup-wizard';
    
    // Form data
    public array $data = [];
    
    // Wizard state
    public int $currentStep = 1;
    public bool $setupComplete = false;
    
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
        // Check if already setup
        $company = Company::first();
        if ($company && $company->branches()->count() > 0) {
            $this->setupComplete = true;
        }
        
        $this->form->fill();
    }
    
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Wizard::make([
                    // Step 1: Firma & Branche
                    Wizard\Step::make('Firma anlegen')
                        ->description('Grundlegende Informationen (30 Sekunden)')
                        ->icon('heroicon-o-building-office')
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
                                
                            Section::make('Erste Filiale')
                                ->schema([
                                    TextInput::make('branch_name')
                                        ->label('Filialname')
                                        ->default('Hauptfiliale')
                                        ->required(),
                                        
                                    TextInput::make('branch_city')
                                        ->label('Stadt')
                                        ->required()
                                        ->placeholder('z.B. Berlin'),
                                        
                                    TextInput::make('branch_address')
                                        ->label('Adresse')
                                        ->placeholder('Straße und Hausnummer'),
                                        
                                    TextInput::make('branch_phone')
                                        ->label('Telefonnummer')
                                        ->tel()
                                        ->placeholder('+49 30 12345678')
                                        ->helperText('Diese Nummer wird für eingehende Anrufe verwendet'),
                                ]),
                        ]),
                        
                    // Step 2: Cal.com Verbindung
                    Wizard\Step::make('Kalender verbinden')
                        ->description('Cal.com Integration (60 Sekunden)')
                        ->icon('heroicon-o-calendar')
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
                                ->visible(fn($get) => $get('calcom_connection_type') === 'api_key')
                                ->helperText('Finden Sie unter cal.com/settings/developer/api-keys'),
                                
                            TextInput::make('calcom_team_slug')
                                ->label('Team Slug (optional)')
                                ->placeholder('mein-team')
                                ->helperText('Für Team-Kalender'),
                                
                            Toggle::make('import_event_types')
                                ->label('Event Types automatisch importieren')
                                ->default(true)
                                ->helperText('Importiert alle verfügbaren Termintypen'),
                        ]),
                        
                    // Step 3: KI-Telefon aktivieren
                    Wizard\Step::make('KI-Telefon einrichten')
                        ->description('Retell.ai Agent (60 Sekunden)')
                        ->icon('heroicon-o-phone')
                        ->schema([
                            Placeholder::make('retell_info')
                                ->content('Ihr KI-Assistent beantwortet Anrufe und bucht Termine.'),
                                
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
                        
                    // Step 4: Services & Zeiten
                    Wizard\Step::make('Services & Öffnungszeiten')
                        ->description('Finale Einstellungen (30 Sekunden)')
                        ->icon('heroicon-o-cog')
                        ->schema([
                            Placeholder::make('template_info')
                                ->content(fn($get) => $this->getTemplateInfo($get('industry'))),
                                
                            Toggle::make('use_template_services')
                                ->label('Branchen-Services verwenden')
                                ->default(true)
                                ->helperText('Vorkonfigurierte Services für Ihre Branche'),
                                
                            Toggle::make('use_template_hours')
                                ->label('Branchen-Öffnungszeiten verwenden')
                                ->default(true)
                                ->helperText('Typische Öffnungszeiten für Ihre Branche'),
                                
                            Section::make('Zusammenfassung')
                                ->schema([
                                    Placeholder::make('summary')
                                        ->content(fn($get) => $this->getSummary($get)),
                                ]),
                        ]),
                ])
                ->submitAction(
                    Action::make('complete_setup')
                        ->label('🚀 Setup abschließen')
                        ->action('completeSetup')
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
    
    protected function getSummary(array $data): string
    {
        $lines = [
            "✅ **Firma:** " . ($data['company_name'] ?? 'Nicht angegeben'),
            "✅ **Branche:** " . ($this->industryTemplates[$data['industry'] ?? '']['name'] ?? 'Nicht gewählt'),
            "✅ **Filiale:** " . ($data['branch_name'] ?? 'Hauptfiliale') . " in " . ($data['branch_city'] ?? 'Stadt'),
            "✅ **Kalender:** " . ($data['calcom_connection_type'] === 'oauth' ? 'OAuth verbunden' : 'API Key konfiguriert'),
            "✅ **KI-Stimme:** " . ($data['ai_voice'] ?? 'Sarah'),
            "✅ **Test-Anruf:** " . (($data['enable_test_call'] ?? true) ? 'Aktiviert' : 'Übersprungen'),
        ];
        
        return implode("\n", $lines);
    }
    
    public function completeSetup(): void
    {
        try {
            DB::beginTransaction();
            
            // 1. Create Company
            $company = Company::create([
                'name' => $this->data['company_name'],
                'industry' => $this->data['industry'],
                'settings' => [
                    'wizard_completed' => true,
                    'setup_date' => now(),
                    'template_used' => $this->data['industry']
                ]
            ]);
            
            // 2. Create Branch
            $branch = Branch::create([
                'company_id' => $company->id,
                'name' => $this->data['branch_name'],
                'city' => $this->data['branch_city'],
                'address' => $this->data['branch_address'] ?? null,
                'phone_number' => $this->data['branch_phone'] ?? null,
                'active' => true,
                'business_hours' => $this->getBusinessHours(),
            ]);
            
            // 3. Setup Cal.com
            if ($this->data['calcom_connection_type'] === 'api_key' && !empty($this->data['calcom_api_key'])) {
                $company->update([
                    'calcom_api_key' => encrypt($this->data['calcom_api_key']),
                    'calcom_team_slug' => $this->data['calcom_team_slug'] ?? null,
                ]);
                
                // Import event types if requested
                if ($this->data['import_event_types'] ?? true) {
                    $this->importCalcomEventTypes($company, $branch);
                }
            }
            
            // 4. Create Services
            if ($this->data['use_template_services'] ?? true) {
                $this->createTemplateServices($company, $this->data['industry']);
            }
            
            // 5. Setup Retell Agent
            $this->setupRetellAgent($branch);
            
            DB::commit();
            
            // Success!
            $this->setupComplete = true;
            
            Notification::make()
                ->title('🎉 Setup erfolgreich abgeschlossen!')
                ->body('Ihr System ist in weniger als 3 Minuten einsatzbereit!')
                ->success()
                ->persistent()
                ->actions([
                    \Filament\Notifications\Actions\Action::make('test_call')
                        ->label('Test-Anruf starten')
                        ->url(route('filament.admin.pages.test-call'))
                        ->openUrlInNewTab(),
                    \Filament\Notifications\Actions\Action::make('dashboard')
                        ->label('Zum Dashboard')
                        ->url(route('filament.admin.pages.dashboard')),
                ])
                ->send();
                
            Log::info('Quick Setup Wizard completed', [
                'company_id' => $company->id,
                'branch_id' => $branch->id,
                'duration' => now()->diffInSeconds($this->started_at ?? now()),
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Quick Setup Wizard failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            Notification::make()
                ->title('Setup fehlgeschlagen')
                ->body('Ein Fehler ist aufgetreten: ' . $e->getMessage())
                ->danger()
                ->send();
        }
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
}