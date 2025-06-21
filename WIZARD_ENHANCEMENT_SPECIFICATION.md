# Wizard Enhancement Specification

## 1. Phone Configuration Enhancement (Step 2)

### Current State
- Basic phone configuration exists
- Limited to single hotline setup
- No multi-number management

### Enhanced Implementation

```php
// Enhanced Phone Configuration Schema
protected function getPhoneConfigurationFields(): array
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
                    ->default('direct')
                    ->reactive()
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
                TextInput::make('hotline_number')
                    ->label('Zentrale Hotline-Nummer')
                    ->tel()
                    ->required()
                    ->prefix('+49')
                    ->helperText('Ihre zentrale Rufnummer für alle Standorte')
                    ->rules(['phone:DE']),

                Select::make('routing_strategy')
                    ->label('Routing-Strategie')
                    ->options([
                        'voice_menu' => 'Sprachmenü (Kunde sagt Filialname)',
                        'business_hours' => 'Nach Öffnungszeiten',
                        'load_balanced' => 'Gleichmäßige Verteilung',
                        'geographic' => 'Nach Anrufer-Region (Premium)'
                    ])
                    ->default('voice_menu')
                    ->reactive()
                    ->helperText('Wie sollen Anrufe verteilt werden?'),

                // Voice Menu Keywords (conditional)
                KeyValue::make('voice_keywords')
                    ->label('Sprachmenü-Konfiguration')
                    ->visible(fn($get) => $get('routing_strategy') === 'voice_menu')
                    ->keyLabel('Filiale')
                    ->valueLabel('Schlüsselwörter')
                    ->default(function() {
                        return $this->company->branches->mapWithKeys(function($branch) {
                            return [$branch->name => $branch->city . ', ' . $branch->name];
                        })->toArray();
                    })
                    ->helperText('Welche Wörter führen zu welcher Filiale?'),
            ]),

        // Direct Numbers Configuration
        Section::make('Direkte Durchwahlnummern')
            ->visible(fn($get) => in_array($get('phone_strategy'), ['direct', 'mixed']))
            ->schema([
                Repeater::make('branch_phone_numbers')
                    ->label('')
                    ->relationship('phoneNumbers')
                    ->schema([
                        Grid::make(3)->schema([
                            Select::make('branch_id')
                                ->label('Filiale')
                                ->options($this->company->branches->pluck('name', 'id'))
                                ->required()
                                ->reactive(),
                            
                            TextInput::make('number')
                                ->label('Telefonnummer')
                                ->tel()
                                ->required()
                                ->prefix('+49')
                                ->rules(['phone:DE'])
                                ->default(fn($get) => $this->getBranchPhone($get('branch_id'))),
                            
                            ToggleButtons::make('is_primary')
                                ->label('Primär?')
                                ->boolean()
                                ->inline()
                                ->default(true),
                        ]),
                        
                        Grid::make(2)->schema([
                            Toggle::make('sms_enabled')
                                ->label('SMS-Empfang')
                                ->default(false),
                            
                            Toggle::make('whatsapp_enabled')
                                ->label('WhatsApp Business')
                                ->default(false),
                        ]),
                    ])
                    ->defaultItems($this->company->branches->count())
                    ->collapsible()
                    ->cloneable(),
            ]),

        // Validation Rules
        Hidden::make('phone_validation')
            ->afterStateUpdated(function($state, $get, $set) {
                $this->validatePhoneConfiguration($get);
            }),
    ];
}
```

## 2. Staff Skills Enhancement (Step 5)

### Current State
- Basic staff creation with name and email
- Languages field exists but not fully utilized
- No skills or certifications UI

### Enhanced Implementation

```php
// Enhanced Staff Configuration
protected function getEnhancedStaffFields(): array
{
    return [
        Repeater::make('staff')
            ->label('Mitarbeiter')
            ->relationship()
            ->schema([
                // Basic Information
                Section::make('Grunddaten')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Name')
                            ->required(),
                        
                        TextInput::make('email')
                            ->label('E-Mail')
                            ->email()
                            ->required(),
                        
                        Select::make('branch_id')
                            ->label('Hauptfiliale')
                            ->options($this->company->branches->pluck('name', 'id'))
                            ->required(),
                        
                        FileUpload::make('avatar')
                            ->label('Profilbild')
                            ->image()
                            ->avatar(),
                    ]),

                // Qualifications
                Section::make('Qualifikationen')
                    ->columns(2)
                    ->schema([
                        // Languages with flags
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
                                'zh' => '🇨🇳 Chinesisch',
                            ])
                            ->default(['de'])
                            ->columns(2)
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
                            ->suggestions(fn() => $this->getIndustrySkills())
                            ->placeholder('Tippen Sie für Vorschläge...')
                            ->reorderable()
                            ->helperText('Relevante Fähigkeiten für Kunden-Matching'),

                        // Service Assignments
                        Select::make('services')
                            ->label('Angebotene Services')
                            ->multiple()
                            ->relationship('services', 'name')
                            ->preload()
                            ->searchable(),
                    ]),

                // Certifications
                Section::make('Zertifikate & Ausbildungen')
                    ->collapsed()
                    ->schema([
                        Repeater::make('certifications')
                            ->label('')
                            ->simple(
                                Grid::make(3)->schema([
                                    TextInput::make('name')
                                        ->label('Zertifikat')
                                        ->placeholder('z.B. Meisterbrief'),
                                    
                                    TextInput::make('issuer')
                                        ->label('Aussteller')
                                        ->placeholder('z.B. HWK Berlin'),
                                    
                                    DatePicker::make('valid_until')
                                        ->label('Gültig bis')
                                        ->minDate(now()),
                                ])
                            )
                            ->defaultItems(0)
                            ->maxItems(5)
                            ->addActionLabel('Zertifikat hinzufügen'),
                    ]),

                // Working Hours (optional)
                Section::make('Arbeitszeiten')
                    ->collapsed()
                    ->schema([
                        Toggle::make('use_branch_hours')
                            ->label('Filialzeiten übernehmen')
                            ->default(true)
                            ->reactive(),

                        // Custom hours if not using branch hours
                        Fieldset::make('custom_hours')
                            ->label('Individuelle Arbeitszeiten')
                            ->visible(fn($get) => !$get('use_branch_hours'))
                            ->schema([
                                // Working hours configuration
                                $this->getWorkingHoursSchema(),
                            ]),
                    ]),
            ])
            ->defaultItems(1)
            ->collapsible()
            ->cloneable()
            ->reorderable()
            ->grid(1),
    ];
}
```

## 3. Review Step with Traffic Light System

### New Review Step Implementation

```php
// New Review Step
Wizard\Step::make('review')
    ->label('Überprüfung & Fertigstellung')
    ->description('Kontrollieren Sie Ihre Konfiguration')
    ->icon('heroicon-o-shield-check')
    ->schema([
        // Status Overview
        Section::make('Konfigurations-Status')
            ->schema([
                ViewField::make('status_overview')
                    ->view('filament.admin.wizard.status-overview'),
            ]),

        // Detailed Check Results
        Section::make('Detail-Prüfung')
            ->schema([
                // Company & Branches
                CheckResult::make('company_check')
                    ->label('Unternehmen & Filialen')
                    ->check(fn() => $this->validateCompanySetup()),

                // Phone Configuration
                CheckResult::make('phone_check')
                    ->label('Telefonnummern')
                    ->check(fn() => $this->validatePhoneSetup()),

                // Cal.com Integration
                CheckResult::make('calcom_check')
                    ->label('Kalender-Integration')
                    ->check(fn() => $this->validateCalcomIntegration())
                    ->critical(),

                // Retell.ai Integration
                CheckResult::make('retell_check')
                    ->label('KI-Telefon')
                    ->check(fn() => $this->validateRetellIntegration())
                    ->critical(),

                // Staff & Services
                CheckResult::make('staff_check')
                    ->label('Mitarbeiter & Services')
                    ->check(fn() => $this->validateStaffSetup()),
            ]),

        // Actions based on status
        Section::make('Nächste Schritte')
            ->schema([
                Actions::make([
                    Action::make('run_test')
                        ->label('System-Test durchführen')
                        ->icon('heroicon-o-play')
                        ->action(fn() => $this->runEndToEndTest())
                        ->visible(fn() => $this->canRunTest()),

                    Action::make('complete_setup')
                        ->label('Setup abschließen')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(fn() => $this->completeSetup())
                        ->visible(fn() => $this->allChecksPass())
                        ->requiresConfirmation()
                        ->modalHeading('Setup abschließen?')
                        ->modalDescription('Sind Sie sicher, dass Sie das Setup abschließen möchten?'),
                ]),
            ]),
    ]),
```

## 4. Health Check Interface

```php
namespace App\Contracts;

interface IntegrationHealthCheck
{
    /**
     * Run the health check for a specific company
     */
    public function check(Company $company): HealthCheckResult;

    /**
     * Get the service name
     */
    public function getName(): string;

    /**
     * Get the check priority (higher = more important)
     */
    public function getPriority(): int;

    /**
     * Whether this check is critical for operation
     */
    public function isCritical(): bool;

    /**
     * Get suggested fixes for common issues
     */
    public function getSuggestedFixes(array $issues): array;

    /**
     * Run automatic fixes if possible
     */
    public function attemptAutoFix(Company $company, array $issues): bool;
}
```

## 5. Status Tracking Schema

```php
// Migration for enhanced status tracking
Schema::table('companies', function (Blueprint $table) {
    $table->enum('onboarding_status', ['draft', 'pending', 'completed'])->default('draft');
    $table->json('onboarding_checks')->nullable();
    $table->timestamp('last_health_check')->nullable();
    $table->json('integration_status')->nullable();
});

// Company Model additions
protected $casts = [
    'onboarding_checks' => 'array',
    'integration_status' => 'array',
];

public function getHealthStatusAttribute(): string
{
    $checks = $this->onboarding_checks ?? [];
    
    if (empty($checks)) {
        return 'unknown';
    }
    
    $critical_failures = collect($checks)
        ->filter(fn($check) => $check['critical'] && !$check['passed'])
        ->count();
    
    if ($critical_failures > 0) {
        return 'unhealthy';
    }
    
    $failures = collect($checks)
        ->filter(fn($check) => !$check['passed'])
        ->count();
    
    if ($failures > 0) {
        return 'degraded';
    }
    
    return 'healthy';
}
```

## 6. Wizard Completion Logic

```php
protected function completeSetup(): void
{
    DB::transaction(function () {
        // Update company status
        $this->company->update([
            'onboarding_status' => 'completed',
            'onboarding_checks' => $this->getAllCheckResults(),
            'settings' => array_merge($this->company->settings ?? [], [
                'wizard_completed' => true,
                'wizard_completed_at' => now(),
                'wizard_version' => '2.0',
            ]),
        ]);

        // Create phone numbers
        $this->createPhoneNumbers();

        // Update staff with skills
        $this->updateStaffSkills();

        // Schedule first health check
        HealthCheckJob::dispatch($this->company)->delay(now()->addMinutes(5));

        // Send completion notification
        $this->company->notify(new SetupCompletedNotification());

        // Log activity
        activity()
            ->performedOn($this->company)
            ->log('Completed setup wizard');
    });

    // Redirect to dashboard
    $this->redirect(route('filament.admin.pages.dashboard'));
}
```

## Implementation Timeline

### Day 1-2: Wizard UI Enhancement
- Enhance phone configuration step
- Implement staff skills UI
- Add review step with status checks

### Day 3-4: Health Check System
- Create IntegrationHealthCheck interface
- Implement RetellHealthCheck
- Implement CalcomHealthCheck
- Implement PhoneRoutingHealthCheck

### Day 5: Integration & Testing
- Connect health checks to wizard
- Implement admin badge
- Create scheduled health check command

### Day 6-7: Templates & Tests
- Create prompt templates
- Implement Dusk E2E tests
- Performance monitoring

### Day 8: Documentation & Deployment
- Update documentation
- Final testing
- Production deployment preparation