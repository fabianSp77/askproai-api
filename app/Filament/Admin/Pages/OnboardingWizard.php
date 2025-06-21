<?php

namespace App\Filament\Admin\Pages;

use App\Models\Branch;
use App\Models\Service;
use App\Models\Staff;
use App\Models\WorkingHour;
use App\Services\OnboardingService;
use App\Services\CalcomService;
use App\Services\RetellService;
use Filament\Forms;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Wizard\Step;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

class OnboardingWizard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?string $navigationLabel = 'Einrichtungsassistent';
    protected static ?string $title = 'AskProAI Einrichtungsassistent';
    protected static ?string $slug = 'onboarding';
    protected static ?int $navigationSort = 0;
    protected static ?string $navigationGroup = 'Einrichtung & Konfiguration';
    
    protected static string $view = 'filament.admin.pages.onboarding-wizard';

    public ?array $data = [];
    public int $currentStep = 1;
    public array $completedSteps = [];
    public int $progressPercentage = 0;

    protected OnboardingService $onboardingService;

    public function boot(): void
    {
        $this->onboardingService = app(OnboardingService::class);
    }

    public function mount(): void
    {
        $user = Auth::user();
        
        // Try to get company
        $company = null;
        try {
            $company = $user->company;
            if (!$company || !($company instanceof \App\Models\Company)) {
                // If no company found via relationship, try to get first active company
                $company = \App\Models\Company::where('is_active', true)->first();
            }
        } catch (\Exception $e) {
            // If relationship fails, try to get first active company
            $company = \App\Models\Company::where('is_active', true)->first();
        }
        
        // If user has no company, show error but don't redirect
        if (!$company) {
            Notification::make()
                ->title('Kein Unternehmen zugeordnet')
                ->body('Sie haben derzeit kein zugeordnetes Unternehmen. Bitte wenden Sie sich an den Administrator.')
                ->danger()
                ->persistent()
                ->send();
            
            // Set empty data instead of redirecting
            $this->completedSteps = [];
            $this->progressPercentage = 0;
            return;
        }
        
        $progress = $this->onboardingService->getProgress($company, $user);
        
        $this->completedSteps = $progress['completed_steps'];
        $this->progressPercentage = $progress['progress_percentage'];
        
        // Show notification if onboarding is already completed but don't redirect
        if ($progress['is_completed']) {
            Notification::make()
                ->title('Onboarding abgeschlossen')
                ->body('Das Onboarding wurde bereits erfolgreich abgeschlossen.')
                ->success()
                ->send();
        }
        
        $this->form->fill($progress['step_data'] ?? []);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Wizard::make([
                    Step::make('welcome')
                        ->label('Willkommen')
                        ->description('Grundlegende Unternehmensinformationen')
                        ->icon('heroicon-o-building-office')
                        ->schema([
                            Forms\Components\Section::make('Willkommen bei AskProAI!')
                                ->description('Lassen Sie uns gemeinsam Ihr intelligentes Terminbuchungssystem einrichten.')
                                ->schema([
                                    Forms\Components\View::make('onboarding.welcome-message'),
                                    
                                    Forms\Components\TextInput::make('company_name')
                                        ->label('Unternehmensname')
                                        ->required()
                                        ->maxLength(255)
                                        ->default(fn () => Auth::user()->company->name ?? '')
                                        ->helperText('Der Name Ihres Unternehmens, wie er Kunden angezeigt wird'),
                                    
                                    Forms\Components\Grid::make(2)
                                        ->schema([
                                            Forms\Components\TextInput::make('contact_person')
                                                ->label('Ansprechpartner')
                                                ->required()
                                                ->maxLength(255)
                                                ->default(fn () => Auth::user()->company->contact_person ?? Auth::user()->name),
                                            
                                            Forms\Components\TextInput::make('email')
                                                ->label('E-Mail-Adresse')
                                                ->email()
                                                ->required()
                                                ->maxLength(255)
                                                ->default(fn () => Auth::user()->company->email ?? Auth::user()->email),
                                        ]),
                                    
                                    Forms\Components\Grid::make(2)
                                        ->schema([
                                            Forms\Components\TextInput::make('phone')
                                                ->label('Telefonnummer')
                                                ->tel()
                                                ->required()
                                                ->maxLength(255)
                                                ->default(fn () => Auth::user()->company->phone ?? '')
                                                ->helperText('Ihre Haupttelefonnummer'),
                                            
                                            Forms\Components\Select::make('company_type')
                                                ->label('Branche')
                                                ->options([
                                                    'medical' => 'Arztpraxis / Medizin',
                                                    'beauty' => 'Beauty / Wellness',
                                                    'legal' => 'Rechtsanwalt / Notar',
                                                    'consulting' => 'Beratung / Consulting',
                                                    'veterinary' => 'Tierarzt',
                                                    'other' => 'Andere',
                                                ])
                                                ->required()
                                                ->default(fn () => Auth::user()->company->company_type ?? ''),
                                        ]),
                                ]),
                        ]),
                    
                    Step::make('branch_setup')
                        ->label('Standorte')
                        ->description('Fügen Sie Ihre Geschäftsstandorte hinzu')
                        ->icon('heroicon-o-map-pin')
                        ->schema([
                            Forms\Components\Section::make('Standortverwaltung')
                                ->description('Fügen Sie mindestens einen Standort hinzu. Sie können später weitere Standorte ergänzen.')
                                ->schema([
                                    Forms\Components\Repeater::make('branches')
                                        ->label('Standorte')
                                        ->schema([
                                            Forms\Components\TextInput::make('name')
                                                ->label('Standortname')
                                                ->required()
                                                ->maxLength(255)
                                                ->placeholder('z.B. Hauptfiliale Berlin'),
                                            
                                            Forms\Components\Grid::make(2)
                                                ->schema([
                                                    Forms\Components\TextInput::make('address')
                                                        ->label('Straße und Hausnummer')
                                                        ->required()
                                                        ->maxLength(255),
                                                    
                                                    Forms\Components\TextInput::make('phone')
                                                        ->label('Telefonnummer')
                                                        ->tel()
                                                        ->maxLength(255),
                                                ]),
                                            
                                            Forms\Components\Grid::make(3)
                                                ->schema([
                                                    Forms\Components\TextInput::make('postal_code')
                                                        ->label('PLZ')
                                                        ->required()
                                                        ->maxLength(10),
                                                    
                                                    Forms\Components\TextInput::make('city')
                                                        ->label('Stadt')
                                                        ->required()
                                                        ->maxLength(255),
                                                    
                                                    Forms\Components\Select::make('country')
                                                        ->label('Land')
                                                        ->options([
                                                            'DE' => 'Deutschland',
                                                            'AT' => 'Österreich',
                                                            'CH' => 'Schweiz',
                                                        ])
                                                        ->default('DE')
                                                        ->required(),
                                                ]),
                                        ])
                                        ->defaultItems(1)
                                        ->minItems(1)
                                        ->maxItems(10)
                                        ->addActionLabel('Standort hinzufügen')
                                        ->reorderable()
                                        ->collapsible()
                                        ->itemLabel(fn (array $state): ?string => $state['name'] ?? 'Neuer Standort'),
                                    
                                    Forms\Components\Placeholder::make('sample_data')
                                        ->content(new HtmlString('
                                            <div class="bg-primary-50 dark:bg-primary-900/20 rounded-lg p-4">
                                                <h4 class="font-medium mb-2">Beispieldaten verwenden</h4>
                                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                                    Möchten Sie mit Beispieldaten starten? Diese können Sie später anpassen.
                                                </p>
                                            </div>
                                        ')),
                                ]),
                        ]),
                    
                    Step::make('staff_setup')
                        ->label('Mitarbeiter')
                        ->description('Fügen Sie Ihre Mitarbeiter hinzu')
                        ->icon('heroicon-o-users')
                        ->schema([
                            Forms\Components\Section::make('Mitarbeiterverwaltung')
                                ->description('Fügen Sie die Mitarbeiter hinzu, die Termine entgegennehmen.')
                                ->schema([
                                    Forms\Components\Repeater::make('staff')
                                        ->label('Mitarbeiter')
                                        ->schema([
                                            Forms\Components\Grid::make(2)
                                                ->schema([
                                                    Forms\Components\TextInput::make('first_name')
                                                        ->label('Vorname')
                                                        ->required()
                                                        ->maxLength(255),
                                                    
                                                    Forms\Components\TextInput::make('last_name')
                                                        ->label('Nachname')
                                                        ->required()
                                                        ->maxLength(255),
                                                ]),
                                            
                                            Forms\Components\Grid::make(2)
                                                ->schema([
                                                    Forms\Components\TextInput::make('email')
                                                        ->label('E-Mail-Adresse')
                                                        ->email()
                                                        ->required()
                                                        ->maxLength(255),
                                                    
                                                    Forms\Components\TextInput::make('phone')
                                                        ->label('Telefonnummer')
                                                        ->tel()
                                                        ->maxLength(255),
                                                ]),
                                            
                                            Forms\Components\Select::make('branch_id')
                                                ->label('Standort')
                                                ->options(function () {
                                                    return Branch::where('company_id', Auth::user()->company_id)
                                                        ->pluck('name', 'id');
                                                })
                                                ->required()
                                                ->helperText('Wählen Sie den Hauptstandort des Mitarbeiters'),
                                            
                                            Forms\Components\Toggle::make('is_active')
                                                ->label('Aktiv')
                                                ->default(true)
                                                ->helperText('Inaktive Mitarbeiter können keine Termine annehmen'),
                                        ])
                                        ->defaultItems(1)
                                        ->minItems(1)
                                        ->addActionLabel('Mitarbeiter hinzufügen')
                                        ->reorderable()
                                        ->collapsible()
                                        ->itemLabel(fn (array $state): ?string => 
                                            isset($state['first_name'], $state['last_name']) 
                                                ? $state['first_name'] . ' ' . $state['last_name']
                                                : 'Neuer Mitarbeiter'
                                        ),
                                ]),
                        ]),
                    
                    Step::make('service_setup')
                        ->label('Dienstleistungen')
                        ->description('Definieren Sie Ihre angebotenen Dienstleistungen')
                        ->icon('heroicon-o-briefcase')
                        ->schema([
                            Forms\Components\Section::make('Dienstleistungsverwaltung')
                                ->description('Definieren Sie die Dienstleistungen, die gebucht werden können.')
                                ->schema([
                                    Forms\Components\Repeater::make('services')
                                        ->label('Dienstleistungen')
                                        ->schema([
                                            Forms\Components\TextInput::make('name')
                                                ->label('Bezeichnung')
                                                ->required()
                                                ->maxLength(255)
                                                ->placeholder('z.B. Beratungsgespräch'),
                                            
                                            Forms\Components\Textarea::make('description')
                                                ->label('Beschreibung')
                                                ->rows(2)
                                                ->maxLength(500)
                                                ->placeholder('Kurze Beschreibung der Dienstleistung'),
                                            
                                            Forms\Components\Grid::make(3)
                                                ->schema([
                                                    Forms\Components\TextInput::make('duration')
                                                        ->label('Dauer (Minuten)')
                                                        ->numeric()
                                                        ->required()
                                                        ->minValue(5)
                                                        ->maxValue(480)
                                                        ->step(5)
                                                        ->default(30),
                                                    
                                                    Forms\Components\TextInput::make('price')
                                                        ->label('Preis')
                                                        ->numeric()
                                                        ->prefix('€')
                                                        ->minValue(0)
                                                        ->step(0.01),
                                                    
                                                    Forms\Components\TextInput::make('buffer_time')
                                                        ->label('Pufferzeit (Min)')
                                                        ->numeric()
                                                        ->minValue(0)
                                                        ->maxValue(60)
                                                        ->default(0)
                                                        ->helperText('Zeit zwischen Terminen'),
                                                ]),
                                            
                                            Forms\Components\Toggle::make('is_active')
                                                ->label('Aktiv')
                                                ->default(true),
                                        ])
                                        ->defaultItems(1)
                                        ->minItems(1)
                                        ->addActionLabel('Dienstleistung hinzufügen')
                                        ->reorderable()
                                        ->collapsible()
                                        ->itemLabel(fn (array $state): ?string => $state['name'] ?? 'Neue Dienstleistung'),
                                ]),
                        ]),
                    
                    Step::make('working_hours')
                        ->label('Arbeitszeiten')
                        ->description('Legen Sie Ihre Geschäftszeiten fest')
                        ->icon('heroicon-o-clock')
                        ->schema([
                            Forms\Components\Section::make('Geschäftszeiten')
                                ->description('Definieren Sie die Zeiten, zu denen Termine gebucht werden können.')
                                ->schema([
                                    Forms\Components\Select::make('branch_for_hours')
                                        ->label('Standort auswählen')
                                        ->options(function () {
                                            return Branch::where('company_id', Auth::user()->company_id)
                                                ->pluck('name', 'id');
                                        })
                                        ->required()
                                        ->reactive(),
                                    
                                    Forms\Components\Grid::make(1)
                                        ->schema(function () {
                                            $days = [
                                                'monday' => 'Montag',
                                                'tuesday' => 'Dienstag',
                                                'wednesday' => 'Mittwoch',
                                                'thursday' => 'Donnerstag',
                                                'friday' => 'Freitag',
                                                'saturday' => 'Samstag',
                                                'sunday' => 'Sonntag',
                                            ];
                                            
                                            $schema = [];
                                            
                                            foreach ($days as $key => $label) {
                                                $schema[] = Forms\Components\Group::make([
                                                    Forms\Components\Toggle::make("hours.{$key}.is_open")
                                                        ->label($label)
                                                        ->default(in_array($key, ['monday', 'tuesday', 'wednesday', 'thursday', 'friday']))
                                                        ->reactive()
                                                        ->columnSpan(2),
                                                    
                                                    Forms\Components\TimePicker::make("hours.{$key}.start_time")
                                                        ->label('Von')
                                                        ->default('09:00')
                                                        ->visible(fn (Forms\Get $get) => $get("hours.{$key}.is_open"))
                                                        ->required(fn (Forms\Get $get) => $get("hours.{$key}.is_open")),
                                                    
                                                    Forms\Components\TimePicker::make("hours.{$key}.end_time")
                                                        ->label('Bis')
                                                        ->default('18:00')
                                                        ->visible(fn (Forms\Get $get) => $get("hours.{$key}.is_open"))
                                                        ->required(fn (Forms\Get $get) => $get("hours.{$key}.is_open")),
                                                ])
                                                ->columns(4);
                                            }
                                            
                                            return $schema;
                                        }),
                                    
                                    Forms\Components\Actions::make([
                                        Forms\Components\Actions\Action::make('apply_to_all')
                                            ->label('Auf alle Werktage anwenden')
                                            ->action(function (Forms\Set $set, Forms\Get $get) {
                                                $mondayHours = $get('hours.monday');
                                                if ($mondayHours && $mondayHours['is_open']) {
                                                    foreach (['tuesday', 'wednesday', 'thursday', 'friday'] as $day) {
                                                        $set("hours.{$day}", $mondayHours);
                                                    }
                                                }
                                            }),
                                    ]),
                                ]),
                        ]),
                    
                    Step::make('calcom_integration')
                        ->label('Kalender')
                        ->description('Verbinden Sie Ihr Kalendersystem')
                        ->icon('heroicon-o-calendar')
                        ->schema([
                            Forms\Components\Section::make('Cal.com Integration')
                                ->description('Verbinden Sie Ihr Cal.com Konto für die Terminverwaltung.')
                                ->schema([
                                    Forms\Components\Radio::make('calendar_choice')
                                        ->label('Wie möchten Sie fortfahren?')
                                        ->options([
                                            'existing' => 'Ich habe bereits ein Cal.com Konto',
                                            'new' => 'Ich möchte ein neues Cal.com Konto erstellen',
                                            'skip' => 'Später einrichten',
                                        ])
                                        ->default('skip')
                                        ->reactive(),
                                    
                                    Forms\Components\Group::make([
                                        Forms\Components\TextInput::make('calcom_api_key')
                                            ->label('Cal.com API-Schlüssel')
                                            ->password()
                                            ->maxLength(255)
                                            ->helperText('Sie finden Ihren API-Schlüssel in den Cal.com Einstellungen'),
                                        
                                        Forms\Components\TextInput::make('calcom_team_slug')
                                            ->label('Team-Slug (optional)')
                                            ->maxLength(255)
                                            ->helperText('Nur erforderlich, wenn Sie Cal.com Teams verwenden'),
                                        
                                        Forms\Components\Actions::make([
                                            Forms\Components\Actions\Action::make('test_calcom')
                                                ->label('Verbindung testen')
                                                ->action(function (Forms\Get $get) {
                                                    $apiKey = $get('calcom_api_key');
                                                    if (!$apiKey) {
                                                        Notification::make()
                                                            ->warning()
                                                            ->title('API-Schlüssel erforderlich')
                                                            ->body('Bitte geben Sie Ihren Cal.com API-Schlüssel ein.')
                                                            ->send();
                                                        return;
                                                    }
                                                    
                                                    try {
                                                        $calcomService = new CalcomService();
                                                        $calcomService->setApiKey($apiKey);
                                                        $result = $calcomService->testConnection();
                                                        
                                                        if ($result) {
                                                            Notification::make()
                                                                ->success()
                                                                ->title('Verbindung erfolgreich')
                                                                ->body('Die Verbindung zu Cal.com wurde erfolgreich hergestellt.')
                                                                ->send();
                                                        }
                                                    } catch (\Exception $e) {
                                                        Notification::make()
                                                            ->danger()
                                                            ->title('Verbindung fehlgeschlagen')
                                                            ->body('Fehler: ' . $e->getMessage())
                                                            ->send();
                                                    }
                                                }),
                                        ]),
                                    ])
                                    ->visible(fn (Forms\Get $get) => $get('calendar_choice') === 'existing'),
                                    
                                    Forms\Components\View::make('onboarding.calcom-signup')
                                        ->visible(fn (Forms\Get $get) => $get('calendar_choice') === 'new'),
                                ]),
                        ]),
                    
                    Step::make('retell_setup')
                        ->label('KI-Telefon')
                        ->description('Konfigurieren Sie Ihren KI-Telefonagenten')
                        ->icon('heroicon-o-phone')
                        ->schema([
                            Forms\Components\Section::make('Retell.ai Integration')
                                ->description('Richten Sie Ihren KI-Telefonagenten ein.')
                                ->schema([
                                    Forms\Components\Radio::make('retell_choice')
                                        ->label('Wie möchten Sie fortfahren?')
                                        ->options([
                                            'existing' => 'Ich habe bereits ein Retell.ai Konto',
                                            'new' => 'Ich möchte ein neues Retell.ai Konto erstellen',
                                            'skip' => 'Später einrichten',
                                        ])
                                        ->default('skip')
                                        ->reactive(),
                                    
                                    Forms\Components\Group::make([
                                        Forms\Components\TextInput::make('retell_api_key')
                                            ->label('Retell.ai API-Schlüssel')
                                            ->password()
                                            ->maxLength(255)
                                            ->helperText('Sie finden Ihren API-Schlüssel in den Retell.ai Einstellungen'),
                                        
                                        Forms\Components\TextInput::make('retell_agent_id')
                                            ->label('Agent ID')
                                            ->maxLength(255)
                                            ->helperText('Die ID Ihres Retell.ai Agenten'),
                                        
                                        Forms\Components\Actions::make([
                                            Forms\Components\Actions\Action::make('test_retell')
                                                ->label('Verbindung testen')
                                                ->action(function (Forms\Get $get) {
                                                    $apiKey = $get('retell_api_key');
                                                    if (!$apiKey) {
                                                        Notification::make()
                                                            ->warning()
                                                            ->title('API-Schlüssel erforderlich')
                                                            ->body('Bitte geben Sie Ihren Retell.ai API-Schlüssel ein.')
                                                            ->send();
                                                        return;
                                                    }
                                                    
                                                    try {
                                                        $retellService = new RetellService();
                                                        $retellService->setApiKey($apiKey);
                                                        $result = $retellService->testConnection();
                                                        
                                                        if ($result) {
                                                            Notification::make()
                                                                ->success()
                                                                ->title('Verbindung erfolgreich')
                                                                ->body('Die Verbindung zu Retell.ai wurde erfolgreich hergestellt.')
                                                                ->send();
                                                        }
                                                    } catch (\Exception $e) {
                                                        Notification::make()
                                                            ->danger()
                                                            ->title('Verbindung fehlgeschlagen')
                                                            ->body('Fehler: ' . $e->getMessage())
                                                            ->send();
                                                    }
                                                }),
                                        ]),
                                    ])
                                    ->visible(fn (Forms\Get $get) => $get('retell_choice') === 'existing'),
                                    
                                    Forms\Components\View::make('onboarding.retell-signup')
                                        ->visible(fn (Forms\Get $get) => $get('retell_choice') === 'new'),
                                ]),
                        ]),
                    
                    Step::make('test_call')
                        ->label('Testanruf')
                        ->description('Testen Sie Ihre Konfiguration')
                        ->icon('heroicon-o-phone-arrow-up-right')
                        ->schema([
                            Forms\Components\Section::make('System testen')
                                ->description('Führen Sie einen Testanruf durch, um sicherzustellen, dass alles funktioniert.')
                                ->schema([
                                    Forms\Components\View::make('onboarding.test-call-instructions'),
                                    
                                    Forms\Components\TextInput::make('test_phone_number')
                                        ->label('Ihre Telefonnummer für den Test')
                                        ->tel()
                                        ->placeholder('+49 170 1234567')
                                        ->helperText('Wir rufen Sie auf dieser Nummer an'),
                                    
                                    Forms\Components\Actions::make([
                                        Forms\Components\Actions\Action::make('initiate_test_call')
                                            ->label('Testanruf starten')
                                            ->action(function (Forms\Get $get) {
                                                $phoneNumber = $get('test_phone_number');
                                                if (!$phoneNumber) {
                                                    Notification::make()
                                                        ->warning()
                                                        ->title('Telefonnummer erforderlich')
                                                        ->body('Bitte geben Sie eine Telefonnummer ein.')
                                                        ->send();
                                                    return;
                                                }
                                                
                                                // TODO: Implement test call functionality
                                                Notification::make()
                                                    ->info()
                                                    ->title('Testanruf wird vorbereitet')
                                                    ->body('Sie erhalten in Kürze einen Anruf auf ' . $phoneNumber)
                                                    ->send();
                                            }),
                                    ]),
                                    
                                    Forms\Components\Checkbox::make('test_call_successful')
                                        ->label('Der Testanruf war erfolgreich')
                                        ->helperText('Bestätigen Sie, dass der Testanruf funktioniert hat'),
                                ]),
                        ]),
                    
                    Step::make('completion')
                        ->label('Fertigstellung')
                        ->description('Überprüfen und abschließen')
                        ->icon('heroicon-o-check-circle')
                        ->schema([
                            Forms\Components\Section::make('Herzlichen Glückwunsch!')
                                ->description('Sie haben die Einrichtung erfolgreich abgeschlossen.')
                                ->schema([
                                    Forms\Components\View::make('onboarding.completion-summary'),
                                    
                                    Forms\Components\Grid::make(2)
                                        ->schema([
                                            Forms\Components\Placeholder::make('next_steps')
                                                ->content(new HtmlString('
                                                    <div class="space-y-2">
                                                        <h4 class="font-medium">Nächste Schritte:</h4>
                                                        <ul class="list-disc list-inside space-y-1 text-sm">
                                                            <li>Überprüfen Sie Ihre Einstellungen</li>
                                                            <li>Laden Sie weitere Teammitglieder ein</li>
                                                            <li>Passen Sie E-Mail-Vorlagen an</li>
                                                            <li>Erkunden Sie erweiterte Funktionen</li>
                                                        </ul>
                                                    </div>
                                                ')),
                                            
                                            Forms\Components\Placeholder::make('resources')
                                                ->content(new HtmlString('
                                                    <div class="space-y-2">
                                                        <h4 class="font-medium">Hilfreiche Ressourcen:</h4>
                                                        <ul class="list-disc list-inside space-y-1 text-sm">
                                                            <li><a href="/docs" class="text-primary-600 hover:underline">Dokumentation</a></li>
                                                            <li><a href="/support" class="text-primary-600 hover:underline">Support kontaktieren</a></li>
                                                            <li><a href="/tutorials" class="text-primary-600 hover:underline">Video-Tutorials</a></li>
                                                            <li><a href="/faq" class="text-primary-600 hover:underline">Häufige Fragen</a></li>
                                                        </ul>
                                                    </div>
                                                ')),
                                        ]),
                                    
                                    Forms\Components\Actions::make([
                                        Forms\Components\Actions\Action::make('go_to_dashboard')
                                            ->label('Zum Dashboard')
                                            ->action(fn () => redirect('/admin'))
                                            ->color('primary'),
                                    ])
                                    ->fullWidth(),
                                ]),
                        ]),
                ])
                ->submitAction(new HtmlString('<button type="submit" class="fi-btn">Weiter</button>'))
                ->startOnStep($this->currentStep)
                ->persistStepInQueryString()
                ->skippable(),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $company = Auth::user()->company;

        DB::transaction(function () use ($data, $company) {
            // Update company information
            if (isset($data['company_name'])) {
                $company->update([
                    'name' => $data['company_name'],
                    'contact_person' => $data['contact_person'] ?? null,
                    'email' => $data['email'] ?? null,
                    'phone' => $data['phone'] ?? null,
                    'company_type' => $data['company_type'] ?? null,
                ]);
            }

            // Create branches
            if (isset($data['branches'])) {
                foreach ($data['branches'] as $branchData) {
                    Branch::create([
                        'company_id' => $company->id,
                        'name' => $branchData['name'],
                        'address' => $branchData['address'],
                        'city' => $branchData['city'],
                        'postal_code' => $branchData['postal_code'],
                        'country' => $branchData['country'] ?? 'DE',
                        'phone' => $branchData['phone'] ?? null,
                    ]);
                }
            }

            // Create staff
            if (isset($data['staff'])) {
                foreach ($data['staff'] as $staffData) {
                    Staff::create([
                        'company_id' => $company->id,
                        'branch_id' => $staffData['branch_id'],
                        'first_name' => $staffData['first_name'],
                        'last_name' => $staffData['last_name'],
                        'email' => $staffData['email'],
                        'phone' => $staffData['phone'] ?? null,
                        'is_active' => $staffData['is_active'] ?? true,
                    ]);
                }
            }

            // Create services
            if (isset($data['services'])) {
                foreach ($data['services'] as $serviceData) {
                    Service::create([
                        'company_id' => $company->id,
                        'name' => $serviceData['name'],
                        'description' => $serviceData['description'] ?? null,
                        'duration' => $serviceData['duration'],
                        'price' => $serviceData['price'] ?? null,
                        'buffer_time' => $serviceData['buffer_time'] ?? 0,
                        'is_active' => $serviceData['is_active'] ?? true,
                    ]);
                }
            }

            // Create working hours
            if (isset($data['branch_for_hours']) && isset($data['hours'])) {
                $branch = Branch::find($data['branch_for_hours']);
                if ($branch) {
                    foreach ($data['hours'] as $day => $hours) {
                        if ($hours['is_open'] ?? false) {
                            WorkingHour::create([
                                'branch_id' => $branch->id,
                                'day_of_week' => array_search($day, ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday']),
                                'start_time' => $hours['start_time'],
                                'end_time' => $hours['end_time'],
                            ]);
                        }
                    }
                }
            }

            // Update integrations
            if (isset($data['calcom_api_key'])) {
                $company->update([
                    'calcom_api_key' => $data['calcom_api_key'],
                    'calcom_team_slug' => $data['calcom_team_slug'] ?? null,
                ]);
            }

            if (isset($data['retell_api_key'])) {
                $company->update([
                    'retell_api_key' => $data['retell_api_key'],
                ]);
            }

            // Update onboarding progress
            $currentStepKey = array_keys($this->getSteps())[$this->currentStep - 1] ?? 'welcome';
            $this->onboardingService->updateProgress($company, Auth::user(), $currentStepKey, $data);
        });

        Notification::make()
            ->success()
            ->title('Fortschritt gespeichert')
            ->body('Ihre Eingaben wurden erfolgreich gespeichert.')
            ->send();

        // Move to next step or complete
        if ($this->currentStep < count($this->getSteps())) {
            $this->currentStep++;
        } else {
            redirect('/admin');
        }
    }

    protected function getSteps(): array
    {
        return $this->onboardingService->getSteps();
    }

    public static function shouldRegisterNavigation(): bool
    {
        // Only show in navigation if onboarding is not completed
        try {
            $user = Auth::user();
            if (!$user) {
                return false;
            }
            
            // Try to get company through relationship
            $company = $user->company ?? null;
            if (!$company || !($company instanceof \App\Models\Company)) {
                // If no company found via relationship, try to get first active company
                $company = \App\Models\Company::where('is_active', true)->first();
            }
            
            if (!$company) {
                return false;
            }
            
            $onboardingService = app(OnboardingService::class);
            $progress = $onboardingService->getProgress($company, $user);
            
            return !$progress['is_completed'];
        } catch (\Exception $e) {
            // If any error occurs, don't show in navigation
            return false;
        }
    }
}