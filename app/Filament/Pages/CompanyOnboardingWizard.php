<?php

namespace App\Filament\Pages;

use App\Models\Company;
use App\Models\CompanyFeeSchedule;
use App\Models\CompanyGatewayConfiguration;
use App\Models\PricingPlan;
use App\Models\ServiceCaseCategory;
use App\Models\ServiceOutputConfiguration;
use App\Models\WebhookPreset;
use App\Services\Billing\FeeService;
use App\Services\Retell\RetellProvisioningService;
use App\Services\RetellApiClient;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Wizard\Step;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

/**
 * CompanyOnboardingWizard
 *
 * 6-Step wizard for setting up a new company with Service Gateway.
 * Creates all necessary configurations in a single guided flow.
 *
 * Steps:
 * 1. Company Basics (name, email, timezone)
 * 2. Gateway Configuration (mode, enrichment)
 * 3. Service Categories (template or custom)
 * 4. Output Configuration (email/webhook/hybrid)
 * 5. Integration Keys (Cal.com, Retell - optional)
 * 6. Review & Activate
 */
class CompanyOnboardingWizard extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';
    protected static ?string $navigationLabel = 'Neues Unternehmen';
    protected static ?string $title = 'Unternehmen einrichten';
    protected static ?string $navigationGroup = 'Service Gateway';
    protected static ?int $navigationSort = 50;

    protected static string $view = 'filament.pages.company-onboarding-wizard';

    public ?array $data = [];

    // Created entities for review step
    public ?Company $createdCompany = null;
    public ?CompanyGatewayConfiguration $createdGatewayConfig = null;
    public ?ServiceOutputConfiguration $createdOutputConfig = null;
    public int $createdCategoriesCount = 0;
    public ?string $provisionedAgentId = null;

    public function mount(): void
    {
        $this->form->fill([
            'timezone' => 'Europe/Berlin',
            'gateway_enabled' => true,
            'gateway_mode' => 'service_desk',
            'enrichment_enabled' => true,
            'alerts_enabled' => true,
            'categories_template' => 'thomas',
            'output_type' => 'email',
            'confidence_threshold' => 0.65,
            'delivery_initial_delay_seconds' => 90,
            'enrichment_timeout_seconds' => 180,
            // Retell defaults
            'retell_enabled' => false,
            'retell_provision_agent' => false,
            'retell_voice_id' => '11labs-Adrian',
            'retell_language' => 'de-DE',
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Wizard::make([
                    $this->getWelcomeStep(),
                    $this->getCompanyBasicsStep(),
                    $this->getGatewayConfigStep(),
                    $this->getCategoriesStep(),
                    $this->getOutputConfigStep(),
                    $this->getRetellStep(),
                    $this->getReviewStep(),
                ])
                    ->submitAction(new HtmlString('
                        <button type="submit" class="fi-btn fi-btn-size-md fi-btn-color-primary gap-1.5 px-3 py-2 text-sm font-semibold shadow-sm rounded-lg bg-primary-600 text-white hover:bg-primary-500 focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:bg-primary-500 dark:hover:bg-primary-400">
                            <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Unternehmen erstellen
                        </button>
                    '))
                    ->persistStepInQueryString(),
            ])
            ->statePath('data');
    }

    /**
     * Step 1: Welcome
     */
    private function getWelcomeStep(): Step
    {
        return Step::make('Willkommen')
            ->icon('heroicon-o-sparkles')
            ->description('Übersicht')
            ->schema([
                Placeholder::make('intro')
                    ->label('')
                    ->content(new HtmlString('
                        <div class="space-y-6">
                            <div class="flex items-center gap-4">
                                <div class="p-3 bg-primary-100 dark:bg-primary-900/30 rounded-full">
                                    <svg class="w-8 h-8 text-primary-600 dark:text-primary-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008z" />
                                    </svg>
                                </div>
                                <div>
                                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">Neues Unternehmen einrichten</h2>
                                    <p class="text-gray-500 dark:text-gray-400">Service Gateway Onboarding in 6 Schritten</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                                    <h4 class="font-semibold mb-2 flex items-center gap-2">
                                        <span class="w-6 h-6 rounded-full bg-primary-600 text-white text-xs flex items-center justify-center">1</span>
                                        Unternehmensdaten
                                    </h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">Name, E-Mail, Zeitzone</p>
                                </div>
                                <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                                    <h4 class="font-semibold mb-2 flex items-center gap-2">
                                        <span class="w-6 h-6 rounded-full bg-primary-600 text-white text-xs flex items-center justify-center">2</span>
                                        Gateway Konfiguration
                                    </h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">Modus, Enrichment, Alerts</p>
                                </div>
                                <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                                    <h4 class="font-semibold mb-2 flex items-center gap-2">
                                        <span class="w-6 h-6 rounded-full bg-primary-600 text-white text-xs flex items-center justify-center">3</span>
                                        Service Kategorien
                                    </h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">IT-Support Kategorien einrichten</p>
                                </div>
                                <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                                    <h4 class="font-semibold mb-2 flex items-center gap-2">
                                        <span class="w-6 h-6 rounded-full bg-primary-600 text-white text-xs flex items-center justify-center">4</span>
                                        Output Konfiguration
                                    </h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">E-Mail, Webhook oder beides</p>
                                </div>
                                <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                                    <h4 class="font-semibold mb-2 flex items-center gap-2">
                                        <span class="w-6 h-6 rounded-full bg-primary-600 text-white text-xs flex items-center justify-center">5</span>
                                        Retell AI
                                    </h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">Voice Agent (optional)</p>
                                </div>
                                <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                                    <h4 class="font-semibold mb-2 flex items-center gap-2">
                                        <span class="w-6 h-6 rounded-full bg-primary-600 text-white text-xs flex items-center justify-center">6</span>
                                        Zusammenfassung
                                    </h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">Überprüfen & Aktivieren</p>
                                </div>
                            </div>

                            <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                                <p class="text-sm text-blue-800 dark:text-blue-200">
                                    <strong>Geschätzte Zeit:</strong> 3-5 Minuten
                                </p>
                            </div>
                        </div>
                    ')),
            ]);
    }

    /**
     * Step 2: Company Basics
     */
    private function getCompanyBasicsStep(): Step
    {
        return Step::make('Unternehmen')
            ->icon('heroicon-o-building-office')
            ->description('Stammdaten')
            ->schema([
                Section::make('Unternehmensdaten')
                    ->description('Grundlegende Informationen zum Unternehmen')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('company_name')
                                ->label('Unternehmensname')
                                ->placeholder('Musterfirma GmbH')
                                ->required()
                                ->maxLength(255)
                                ->unique(Company::class, 'name'),

                            TextInput::make('company_email')
                                ->label('Haupt-E-Mail')
                                ->email()
                                ->placeholder('info@musterfirma.de')
                                ->required()
                                ->maxLength(255),
                        ]),

                        Grid::make(2)->schema([
                            TextInput::make('company_phone')
                                ->label('Telefon')
                                ->tel()
                                ->placeholder('+49 123 456789'),

                            Select::make('timezone')
                                ->label('Zeitzone')
                                ->options([
                                    'Europe/Berlin' => 'Berlin (Europe/Berlin)',
                                    'Europe/Vienna' => 'Wien (Europe/Vienna)',
                                    'Europe/Zurich' => 'Zürich (Europe/Zurich)',
                                    'Europe/London' => 'London (Europe/London)',
                                    'UTC' => 'UTC',
                                ])
                                ->default('Europe/Berlin')
                                ->required(),
                        ]),

                        TextInput::make('company_address')
                            ->label('Adresse')
                            ->placeholder('Musterstraße 123, 12345 Musterstadt'),

                        TextInput::make('company_website')
                            ->label('Website')
                            ->url()
                            ->placeholder('https://www.musterfirma.de'),
                    ]),

                Section::make('Preisplan & Abrechnung')
                    ->description('Tarif und Einrichtungsgebühren für das Unternehmen')
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('pricing_plan_id')
                                ->label('Preisplan')
                                ->options(PricingPlan::pluck('name', 'id'))
                                ->searchable()
                                ->preload()
                                ->placeholder('Standard-Tarif')
                                ->helperText('Optional: Preisplan mit individuellem Minutenpreis und Setup-Fee')
                                ->reactive(),

                            Placeholder::make('setup_fee_display')
                                ->label('Einrichtungsgebühr')
                                ->content(function (Get $get) {
                                    $planId = $get('pricing_plan_id');
                                    if (!$planId) {
                                        return new HtmlString('<span class="text-gray-500">Kein Preisplan gewählt</span>');
                                    }
                                    $plan = PricingPlan::find($planId);
                                    if (!$plan) {
                                        return new HtmlString('<span class="text-gray-500">—</span>');
                                    }
                                    $fee = $plan->setup_fee ?? 0;
                                    if ($fee > 0) {
                                        return new HtmlString(
                                            '<span class="text-warning-600 font-semibold">' .
                                            number_format($fee, 2, ',', '.') . ' EUR' .
                                            '</span> <span class="text-xs text-gray-500">(wird nach Erstellung berechnet)</span>'
                                        );
                                    }
                                    return new HtmlString('<span class="text-success-600">Keine Einrichtungsgebühr</span>');
                                }),
                        ]),
                    ])->collapsed(),
            ]);
    }

    /**
     * Step 3: Gateway Configuration
     */
    private function getGatewayConfigStep(): Step
    {
        return Step::make('Gateway')
            ->icon('heroicon-o-cog-6-tooth')
            ->description('Konfiguration')
            ->schema([
                Section::make('Gateway Modus')
                    ->description('Wie sollen eingehende Anrufe verarbeitet werden?')
                    ->schema([
                        Toggle::make('gateway_enabled')
                            ->label('Service Gateway aktivieren')
                            ->helperText('Aktiviert die automatische Ticketerstellung')
                            ->default(true)
                            ->live(),

                        Radio::make('gateway_mode')
                            ->label('Modus')
                            ->options([
                                'appointment' => 'Terminbuchung - Fokus auf Terminvereinbarung',
                                'service_desk' => 'Service Desk - Fokus auf Ticketerstellung',
                                'hybrid' => 'Hybrid - KI erkennt automatisch den Intent',
                            ])
                            ->default('service_desk')
                            ->visible(fn (Get $get) => $get('gateway_enabled')),
                    ]),

                Section::make('Enrichment & Delivery')
                    ->description('Datenanreicherung vor dem Versand')
                    ->schema([
                        Toggle::make('enrichment_enabled')
                            ->label('2-Phase Delivery aktivieren')
                            ->helperText('Wartet auf Customer-Matching und Audio-Download vor Versand')
                            ->default(true)
                            ->live(),

                        Grid::make(2)->schema([
                            TextInput::make('delivery_initial_delay_seconds')
                                ->label('Initiale Verzögerung (Sek.)')
                                ->numeric()
                                ->default(90)
                                ->minValue(0)
                                ->maxValue(300)
                                ->visible(fn (Get $get) => $get('enrichment_enabled')),

                            TextInput::make('enrichment_timeout_seconds')
                                ->label('Timeout (Sek.)')
                                ->numeric()
                                ->default(180)
                                ->minValue(30)
                                ->maxValue(600)
                                ->visible(fn (Get $get) => $get('enrichment_enabled')),
                        ]),
                    ])
                    ->visible(fn (Get $get) => $get('gateway_enabled'))
                    ->collapsible(),

                Section::make('Admin Alerts')
                    ->description('Benachrichtigungen bei Fehlern')
                    ->schema([
                        Toggle::make('alerts_enabled')
                            ->label('Alerts aktivieren')
                            ->helperText('Sendet E-Mail bei permanenten Delivery-Fehlern')
                            ->default(true)
                            ->live(),

                        TextInput::make('admin_email')
                            ->label('Admin E-Mail(s)')
                            ->placeholder('admin@musterfirma.de, support@musterfirma.de')
                            ->helperText('Komma-getrennt für mehrere Empfänger')
                            ->visible(fn (Get $get) => $get('alerts_enabled')),

                        TextInput::make('slack_webhook')
                            ->label('Slack Webhook (optional)')
                            ->url()
                            ->placeholder('https://hooks.slack.com/services/...')
                            ->visible(fn (Get $get) => $get('alerts_enabled')),
                    ])
                    ->visible(fn (Get $get) => $get('gateway_enabled'))
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    /**
     * Step 4: Categories
     */
    private function getCategoriesStep(): Step
    {
        return Step::make('Kategorien')
            ->icon('heroicon-o-tag')
            ->description('Service Kategorien')
            ->schema([
                Section::make('Kategorie-Vorlage')
                    ->description('Wähle eine Vorlage oder erstelle eigene Kategorien')
                    ->schema([
                        Radio::make('categories_template')
                            ->label('Vorlage')
                            ->options([
                                'thomas' => 'IT-Systemhaus Standard (empfohlen)',
                                'minimal' => 'Minimal (nur Basis-Kategorien)',
                                'none' => 'Keine Kategorien erstellen',
                            ])
                            ->default('thomas')
                            ->descriptions([
                                'thomas' => 'Netzwerk, Server, M365, Security, VoIP, Allgemein - 6 Hauptkategorien mit Unterkategorien',
                                'minimal' => 'Incident, Request, Inquiry - 3 Basis-Kategorien',
                                'none' => 'Später manuell anlegen',
                            ])
                            ->live(),

                        Placeholder::make('thomas_preview')
                            ->label('Vorschau: IT-Systemhaus Standard')
                            ->content(new HtmlString('
                                <div class="grid grid-cols-2 md:grid-cols-3 gap-2 text-sm">
                                    <div class="p-2 bg-blue-50 dark:bg-blue-900/20 rounded">🌐 Netzwerk & Konnektivität</div>
                                    <div class="p-2 bg-purple-50 dark:bg-purple-900/20 rounded">🖥️ Server & Virtualisierung</div>
                                    <div class="p-2 bg-orange-50 dark:bg-orange-900/20 rounded">☁️ Microsoft 365</div>
                                    <div class="p-2 bg-red-50 dark:bg-red-900/20 rounded">🔒 Security</div>
                                    <div class="p-2 bg-green-50 dark:bg-green-900/20 rounded">📞 VoIP & Telefonie</div>
                                    <div class="p-2 bg-gray-50 dark:bg-gray-700 rounded">📋 Allgemein</div>
                                </div>
                            '))
                            ->visible(fn (Get $get) => $get('categories_template') === 'thomas'),
                    ]),

                Section::make('Intent-Erkennung')
                    ->description('Konfiguration für automatische Kategorisierung')
                    ->schema([
                        TextInput::make('confidence_threshold')
                            ->label('Konfidenz-Schwelle')
                            ->numeric()
                            ->default(0.65)
                            ->minValue(0.3)
                            ->maxValue(0.95)
                            ->step(0.05)
                            ->helperText('Minimum-Konfidenz (0.3-0.95) für automatische Kategorie-Zuweisung'),
                    ])
                    ->visible(fn (Get $get) => $get('categories_template') !== 'none')
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    /**
     * Step 5: Output Configuration
     */
    private function getOutputConfigStep(): Step
    {
        return Step::make('Output')
            ->icon('heroicon-o-paper-airplane')
            ->description('Benachrichtigungen')
            ->schema([
                Section::make('Output-Typ')
                    ->description('Wie sollen Cases übermittelt werden?')
                    ->schema([
                        Radio::make('output_type')
                            ->label('Ausgabe-Methode')
                            ->options([
                                'email' => 'E-Mail',
                                'webhook' => 'Webhook (Jira, ServiceNow, etc.)',
                                'hybrid' => 'Hybrid (E-Mail + Webhook)',
                            ])
                            ->default('email')
                            ->descriptions([
                                'email' => 'Cases werden per E-Mail an das Team gesendet',
                                'webhook' => 'Cases werden an ein Ticket-System gesendet',
                                'hybrid' => 'Webhook als primär, E-Mail als Fallback',
                            ])
                            ->live(),
                    ]),

                Section::make('E-Mail Konfiguration')
                    ->description('Empfänger für Case-Benachrichtigungen')
                    ->schema([
                        TagsInput::make('email_recipients')
                            ->label('Empfänger')
                            ->placeholder('E-Mail hinzufügen...')
                            ->helperText('E-Mail-Adressen eingeben und mit Enter bestätigen')
                            ->splitKeys(['Tab', ',', ' '])
                            ->required(fn (Get $get) => in_array($get('output_type'), ['email', 'hybrid'])),

                        TextInput::make('email_subject_template')
                            ->label('Betreff-Vorlage')
                            ->placeholder('[{{case.priority}}] {{case.subject}}')
                            ->default('[{{case.priority}}] {{case.subject}}')
                            ->helperText('Variablen: {{case.subject}}, {{case.priority}}, {{case.type}}'),
                    ])
                    ->visible(fn (Get $get) => in_array($get('output_type'), ['email', 'hybrid'])),

                Section::make('Webhook Konfiguration')
                    ->description('Verbindung zu externem Ticket-System')
                    ->schema([
                        Select::make('webhook_preset_id')
                            ->label('Preset wählen')
                            ->options(fn () => WebhookPreset::where('is_active', true)
                                ->orderBy('name')
                                ->pluck('name', 'id'))
                            ->placeholder('Preset auswählen...')
                            ->helperText('Vorkonfigurierte Templates für gängige Systeme')
                            ->searchable(),

                        TextInput::make('webhook_url')
                            ->label('Webhook URL')
                            ->url()
                            ->placeholder('https://api.example.com/webhook')
                            ->required(fn (Get $get) => in_array($get('output_type'), ['webhook', 'hybrid'])),

                        TextInput::make('webhook_secret')
                            ->label('Webhook Secret')
                            ->password()
                            ->revealable()
                            ->helperText('HMAC-SHA256 Secret für Signatur-Validierung (optional)'),
                    ])
                    ->visible(fn (Get $get) => in_array($get('output_type'), ['webhook', 'hybrid'])),
            ]);
    }

    /**
     * Step 6: Retell AI (Optional)
     */
    private function getRetellStep(): Step
    {
        // Check if Retell is configured
        $retellConfigured = !empty(config('services.retellai.api_key'));
        $templateAgentId = config('services.retellai.template_agent_id');

        return Step::make('Retell AI')
            ->icon('heroicon-o-phone-arrow-up-right')
            ->description('Voice Agent (optional)')
            ->schema([
                Section::make('Retell AI Integration')
                    ->description($retellConfigured
                        ? 'Konfiguriere den Voice Agent für dieses Unternehmen'
                        : 'Retell AI ist nicht konfiguriert. API-Key in .env setzen.')
                    ->schema([
                        Placeholder::make('retell_info')
                            ->label('')
                            ->content(new HtmlString('
                                <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                                    <div class="flex items-center gap-3">
                                        <div class="p-2 bg-blue-100 dark:bg-blue-800 rounded-full">
                                            <svg class="w-5 h-5 text-blue-600 dark:text-blue-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <h4 class="font-semibold text-blue-800 dark:text-blue-200">Retell AI Voice Agent</h4>
                                            <p class="text-sm text-blue-700 dark:text-blue-300">Automatische Anrufbearbeitung mit KI-gestütztem Sprachassistenten</p>
                                        </div>
                                    </div>
                                </div>
                            '))
                            ->visible($retellConfigured),

                        Toggle::make('retell_enabled')
                            ->label('Retell AI aktivieren')
                            ->helperText('Aktiviert die Voice Agent Integration für dieses Unternehmen')
                            ->default(false)
                            ->live()
                            ->disabled(!$retellConfigured),

                        Toggle::make('retell_provision_agent')
                            ->label('Neuen Agent automatisch erstellen')
                            ->helperText($templateAgentId
                                ? 'Klont den Template-Agent und konfiguriert ihn für dieses Unternehmen'
                                : 'Erstellt einen neuen Agent mit Standard-Konfiguration')
                            ->default(false)
                            ->visible(fn (Get $get) => $get('retell_enabled'))
                            ->disabled(!$retellConfigured),
                    ])
                    ->visible($retellConfigured),

                Section::make('Agent Konfiguration')
                    ->description('Voice-Einstellungen für den Agent')
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('retell_voice_id')
                                ->label('Stimme')
                                ->options([
                                    '11labs-Adrian' => 'Adrian (männlich, professionell)',
                                    '11labs-Aria' => 'Aria (weiblich, freundlich)',
                                    '11labs-Roger' => 'Roger (männlich, warm)',
                                    '11labs-Sarah' => 'Sarah (weiblich, natürlich)',
                                    '11labs-Laura' => 'Laura (weiblich, klar)',
                                    'openai-alloy' => 'Alloy (neutral)',
                                    'openai-echo' => 'Echo (männlich)',
                                    'openai-fable' => 'Fable (neutral)',
                                    'openai-onyx' => 'Onyx (tief, männlich)',
                                    'openai-nova' => 'Nova (weiblich)',
                                ])
                                ->default('11labs-Adrian')
                                ->searchable(),

                            Select::make('retell_language')
                                ->label('Sprache')
                                ->options([
                                    'de-DE' => 'Deutsch (Deutschland)',
                                    'de-AT' => 'Deutsch (Österreich)',
                                    'de-CH' => 'Deutsch (Schweiz)',
                                    'en-US' => 'English (US)',
                                    'en-GB' => 'English (UK)',
                                ])
                                ->default('de-DE'),
                        ]),

                        TextInput::make('retell_max_call_duration')
                            ->label('Max. Anrufdauer (Minuten)')
                            ->numeric()
                            ->default(10)
                            ->minValue(1)
                            ->maxValue(60)
                            ->helperText('Maximale Dauer eines Anrufs'),
                    ])
                    ->visible(fn (Get $get) => $get('retell_enabled') && $get('retell_provision_agent'))
                    ->collapsible(),

                Placeholder::make('retell_not_configured')
                    ->label('')
                    ->content(new HtmlString('
                        <div class="p-4 bg-amber-50 dark:bg-amber-900/20 rounded-lg border border-amber-200 dark:border-amber-800">
                            <div class="flex items-center gap-3">
                                <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                                </svg>
                                <div>
                                    <h4 class="font-semibold text-amber-800 dark:text-amber-200">Retell AI nicht konfiguriert</h4>
                                    <p class="text-sm text-amber-700 dark:text-amber-300">
                                        Setze RETELLAI_API_KEY in der .env-Datei, um Voice Agents nutzen zu können.
                                        Du kannst diesen Schritt überspringen und später konfigurieren.
                                    </p>
                                </div>
                            </div>
                        </div>
                    '))
                    ->visible(!$retellConfigured),
            ]);
    }

    /**
     * Step 7: Review
     */
    private function getReviewStep(): Step
    {
        return Step::make('Zusammenfassung')
            ->icon('heroicon-o-check-circle')
            ->description('Überprüfen')
            ->schema([
                Placeholder::make('review')
                    ->label('')
                    ->content(function (Get $get): HtmlString {
                        $companyName = $get('company_name') ?: '(nicht angegeben)';
                        $email = $get('company_email') ?: '(nicht angegeben)';
                        $gatewayEnabled = $get('gateway_enabled') ? '✅ Aktiviert' : '❌ Deaktiviert';
                        $mode = match($get('gateway_mode')) {
                            'appointment' => 'Terminbuchung',
                            'service_desk' => 'Service Desk',
                            'hybrid' => 'Hybrid',
                            default => '-',
                        };
                        $categories = match($get('categories_template')) {
                            'thomas' => 'IT-Systemhaus Standard (6 Kategorien)',
                            'minimal' => 'Minimal (3 Kategorien)',
                            'none' => 'Keine',
                            default => '-',
                        };
                        $output = match($get('output_type')) {
                            'email' => 'E-Mail',
                            'webhook' => 'Webhook',
                            'hybrid' => 'Hybrid (E-Mail + Webhook)',
                            default => '-',
                        };
                        $recipients = is_array($get('email_recipients')) ? implode(', ', $get('email_recipients')) : '-';
                        $retellEnabled = $get('retell_enabled') ? '✅ Aktiviert' : '❌ Deaktiviert';
                        $retellProvision = $get('retell_provision_agent') ? 'Agent wird erstellt' : 'Manuell später';
                        $retellVoice = $get('retell_voice_id') ?? '-';

                        return new HtmlString("
                            <div class='space-y-4'>
                                <div class='p-4 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800'>
                                    <h3 class='font-semibold text-green-800 dark:text-green-200 mb-2'>Bereit zur Erstellung</h3>
                                    <p class='text-sm text-green-700 dark:text-green-300'>Überprüfe die Einstellungen und klicke auf \"Unternehmen erstellen\".</p>
                                </div>

                                <div class='grid gap-4'>
                                    <div class='p-4 bg-gray-50 dark:bg-gray-800 rounded-lg'>
                                        <h4 class='font-semibold mb-2'>Unternehmen</h4>
                                        <dl class='grid grid-cols-2 gap-2 text-sm'>
                                            <dt class='text-gray-500'>Name:</dt>
                                            <dd class='font-medium'>{$companyName}</dd>
                                            <dt class='text-gray-500'>E-Mail:</dt>
                                            <dd>{$email}</dd>
                                        </dl>
                                    </div>

                                    <div class='p-4 bg-gray-50 dark:bg-gray-800 rounded-lg'>
                                        <h4 class='font-semibold mb-2'>Gateway</h4>
                                        <dl class='grid grid-cols-2 gap-2 text-sm'>
                                            <dt class='text-gray-500'>Status:</dt>
                                            <dd>{$gatewayEnabled}</dd>
                                            <dt class='text-gray-500'>Modus:</dt>
                                            <dd>{$mode}</dd>
                                        </dl>
                                    </div>

                                    <div class='p-4 bg-gray-50 dark:bg-gray-800 rounded-lg'>
                                        <h4 class='font-semibold mb-2'>Kategorien & Output</h4>
                                        <dl class='grid grid-cols-2 gap-2 text-sm'>
                                            <dt class='text-gray-500'>Kategorien:</dt>
                                            <dd>{$categories}</dd>
                                            <dt class='text-gray-500'>Output-Typ:</dt>
                                            <dd>{$output}</dd>
                                            <dt class='text-gray-500'>Empfänger:</dt>
                                            <dd class='truncate' title='{$recipients}'>{$recipients}</dd>
                                        </dl>
                                    </div>

                                    <div class='p-4 bg-gray-50 dark:bg-gray-800 rounded-lg'>
                                        <h4 class='font-semibold mb-2'>Retell AI</h4>
                                        <dl class='grid grid-cols-2 gap-2 text-sm'>
                                            <dt class='text-gray-500'>Status:</dt>
                                            <dd>{$retellEnabled}</dd>
                                            <dt class='text-gray-500'>Provisioning:</dt>
                                            <dd>{$retellProvision}</dd>
                                            <dt class='text-gray-500'>Stimme:</dt>
                                            <dd>{$retellVoice}</dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        ");
                    }),
            ]);
    }

    /**
     * Create all entities on form submission
     */
    public function create(): void
    {
        $data = $this->form->getState();

        try {
            DB::transaction(function () use ($data) {
                // 1. Create Company
                $this->createdCompany = Company::create([
                    'name' => $data['company_name'],
                    'email' => $data['company_email'],
                    'phone' => $data['company_phone'] ?? null,
                    'timezone' => $data['timezone'],
                    'address' => $data['company_address'] ?? null,
                    'website' => $data['company_website'] ?? null,
                    'pricing_plan_id' => $data['pricing_plan_id'] ?? null,
                    'is_active' => true,
                ]);

                // 2. Create Gateway Configuration
                if ($data['gateway_enabled']) {
                    $this->createdGatewayConfig = CompanyGatewayConfiguration::create([
                        'company_id' => $this->createdCompany->id,
                        'gateway_enabled' => true,
                        'gateway_mode' => $data['gateway_mode'],
                        'hybrid_fallback_mode' => $data['gateway_mode'] === 'hybrid' ? 'service_desk' : 'appointment',
                        'enrichment_enabled' => $data['enrichment_enabled'] ?? false,
                        'delivery_initial_delay_seconds' => $data['delivery_initial_delay_seconds'] ?? 90,
                        'enrichment_timeout_seconds' => $data['enrichment_timeout_seconds'] ?? 180,
                        'audio_in_webhook' => false,
                        'audio_url_ttl_minutes' => 60,
                        'admin_email' => $data['admin_email'] ?? null,
                        'alerts_enabled' => $data['alerts_enabled'] ?? true,
                        'slack_webhook' => $data['slack_webhook'] ?? null,
                        'intent_confidence_threshold' => $data['confidence_threshold'] ?? 0.65,
                        'default_case_type' => 'incident',
                        'default_priority' => 3,
                    ]);
                }

                // 3. Create Output Configuration
                $this->createdOutputConfig = ServiceOutputConfiguration::create([
                    'company_id' => $this->createdCompany->id,
                    'name' => $data['company_name'] . ' - Standard Output',
                    'output_type' => $data['output_type'],
                    'email_recipients' => $data['email_recipients'] ?? [],
                    'email_subject_template' => $data['email_subject_template'] ?? '[{{case.priority}}] {{case.subject}}',
                    'webhook_url' => $data['webhook_url'] ?? null,
                    'webhook_preset_id' => $data['webhook_preset_id'] ?? null,
                    'webhook_secret' => $data['webhook_secret'] ?? null,
                    'webhook_enabled' => in_array($data['output_type'], ['webhook', 'hybrid']),
                    'is_active' => true,
                ]);

                // 4. Create Categories
                if ($data['categories_template'] !== 'none') {
                    $this->createdCategoriesCount = $this->seedCategories(
                        $this->createdCompany,
                        $this->createdOutputConfig,
                        $data['categories_template'],
                        $data['confidence_threshold'] ?? 0.65
                    );
                }

                // 5. Create CompanyFeeSchedule (billing configuration)
                CompanyFeeSchedule::create([
                    'company_id' => $this->createdCompany->id,
                    'billing_mode' => CompanyFeeSchedule::BILLING_MODE_PER_SECOND,
                    'setup_fee' => $this->createdCompany->pricingPlan?->setup_fee ?? 0,
                ]);

                // 6. Clear caches
                Cache::forget("company_gateway_config:{$this->createdCompany->id}");
            });

            // 7. Charge Setup Fee (outside transaction - creates own transaction)
            $setupFeeTransaction = null;
            if ($this->createdCompany->pricingPlan?->setup_fee > 0) {
                $setupFeeTransaction = app(FeeService::class)->chargeSetupFee($this->createdCompany);
            }

            // 8. Provision Retell Agent (outside transaction - external API call)
            if (($data['retell_enabled'] ?? false) && ($data['retell_provision_agent'] ?? false)) {
                $this->provisionRetellAgent($data);
            }

            // Build success message
            $successMessage = "{$this->createdCompany->name} wurde mit {$this->createdCategoriesCount} Kategorien eingerichtet.";
            if ($setupFeeTransaction) {
                $fee = $this->createdCompany->pricingPlan->setup_fee;
                $successMessage .= " Einrichtungsgebühr: " . number_format($fee, 2, ',', '.') . " EUR.";
            }

            Notification::make()
                ->title('Unternehmen erfolgreich erstellt!')
                ->body($successMessage)
                ->success()
                ->send();

            $this->redirect(route('filament.admin.resources.companies.edit', ['record' => $this->createdCompany]));

        } catch (\Exception $e) {
            Notification::make()
                ->title('Fehler beim Erstellen')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Provision Retell Agent for the new company
     */
    private function provisionRetellAgent(array $data): void
    {
        try {
            $provisioningService = app(RetellProvisioningService::class);

            $result = $provisioningService->provisionForCompany($this->createdCompany, [
                'voice_id' => $data['retell_voice_id'] ?? '11labs-Adrian',
                'language' => $data['retell_language'] ?? 'de-DE',
                'max_call_duration_ms' => (($data['retell_max_call_duration'] ?? 10) * 60 * 1000),
                'clone_template' => true,
            ]);

            if ($result['success']) {
                $this->provisionedAgentId = $result['agent_id'];

                Notification::make()
                    ->title('Retell Agent erstellt')
                    ->body("Agent ID: {$result['agent_id']}")
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title('Retell Provisioning fehlgeschlagen')
                    ->body($result['message'])
                    ->warning()
                    ->send();
            }
        } catch (\Exception $e) {
            Notification::make()
                ->title('Retell Provisioning Fehler')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Seed categories based on template
     */
    private function seedCategories(
        Company $company,
        ServiceOutputConfiguration $outputConfig,
        string $template,
        float $confidenceThreshold
    ): int {
        $categories = match($template) {
            'thomas' => $this->getThomasCategories(),
            'minimal' => $this->getMinimalCategories(),
            default => [],
        };

        $count = 0;
        foreach ($categories as $index => $category) {
            ServiceCaseCategory::create([
                'company_id' => $company->id,
                'name' => $category['name'],
                'slug' => Str::slug($category['name']),
                'description' => $category['description'] ?? null,
                'intent_keywords' => $category['keywords'] ?? [],
                'confidence_threshold' => $category['confidence'] ?? $confidenceThreshold,
                'default_case_type' => $category['case_type'] ?? 'incident',
                'default_priority' => $category['priority'] ?? 'normal',
                'output_configuration_id' => $outputConfig->id,
                'is_active' => true,
                'sort_order' => ($index + 1) * 10,
            ]);
            $count++;
        }

        return $count;
    }

    /**
     * IT-Systemhaus Standard Categories
     */
    private function getThomasCategories(): array
    {
        return [
            [
                'name' => 'Netzwerk & Konnektivität',
                'description' => 'Internet, VPN, WLAN, Firewall, Netzwerkprobleme',
                'keywords' => ['netzwerk', 'internet', 'vpn', 'wlan', 'wifi', 'verbindung', 'firewall', 'langsam', 'ping'],
                'confidence' => 0.55,
                'case_type' => 'incident',
                'priority' => 'high',
            ],
            [
                'name' => 'Server & Virtualisierung',
                'description' => 'Server, VDI, Citrix, Terminal Server, Hyper-V, VMware',
                'keywords' => ['server', 'vdi', 'citrix', 'terminal', 'virtuell', 'hyper-v', 'vmware', 'langsam', 'abgestürzt'],
                'confidence' => 0.60,
                'case_type' => 'incident',
                'priority' => 'critical',
            ],
            [
                'name' => 'Microsoft 365',
                'description' => 'Outlook, Teams, SharePoint, OneDrive, Exchange',
                'keywords' => ['outlook', 'teams', 'sharepoint', 'onedrive', 'office', 'microsoft', 'email', 'kalender', 'meeting'],
                'confidence' => 0.50,
                'case_type' => 'incident',
                'priority' => 'normal',
            ],
            [
                'name' => 'Security & E-Mail-Sicherheit',
                'description' => 'Spam, Phishing, Virus, Passwort, Berechtigung',
                'keywords' => ['spam', 'phishing', 'virus', 'sicherheit', 'passwort', 'gehackt', 'berechtigung', 'zugang', 'gesperrt'],
                'confidence' => 0.65,
                'case_type' => 'incident',
                'priority' => 'critical',
            ],
            [
                'name' => 'VoIP & Telefonie',
                'description' => 'Telefon, Headset, Softphone, Anruf, Voicemail',
                'keywords' => ['telefon', 'telefonie', 'voip', 'headset', 'anruf', 'voicemail', 'rufnummer', 'weiterleitung'],
                'confidence' => 0.55,
                'case_type' => 'incident',
                'priority' => 'high',
            ],
            [
                'name' => 'Allgemein',
                'description' => 'Sonstige Anfragen und allgemeine Probleme',
                'keywords' => ['sonstig', 'allgemein', 'frage', 'hilfe', 'support', 'problem', 'funktioniert nicht'],
                'confidence' => 0.40,
                'case_type' => 'inquiry',
                'priority' => 'normal',
            ],
        ];
    }

    /**
     * Minimal Categories
     */
    private function getMinimalCategories(): array
    {
        return [
            [
                'name' => 'Incident',
                'description' => 'Technische Störungen und Probleme',
                'keywords' => ['problem', 'fehler', 'funktioniert nicht', 'kaputt', 'geht nicht', 'störung'],
                'confidence' => 0.50,
                'case_type' => 'incident',
                'priority' => 'normal',
            ],
            [
                'name' => 'Request',
                'description' => 'Anfragen und Bestellungen',
                'keywords' => ['anfrage', 'bestellen', 'neu', 'einrichten', 'möchte', 'brauche'],
                'confidence' => 0.50,
                'case_type' => 'request',
                'priority' => 'low',
            ],
            [
                'name' => 'Inquiry',
                'description' => 'Fragen und Informationsanfragen',
                'keywords' => ['frage', 'wie', 'warum', 'information', 'erklären', 'hilfe'],
                'confidence' => 0.45,
                'case_type' => 'inquiry',
                'priority' => 'low',
            ],
        ];
    }
}
