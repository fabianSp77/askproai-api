<?php

namespace App\Filament\Pages;

use App\Models\Company;
use App\Models\SystemSetting;
use App\Models\Branch;
use App\Models\Service;
use App\Models\Staff;
use Filament\Pages\Page;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\ViewField;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

/**
 * Settings Dashboard - Centralized Configuration Management
 *
 * Provides a unified interface for managing all company-wide settings
 * across 6 categories: Retell AI, Cal.com, OpenAI, Qdrant, Calendar, Policies
 *
 * Phase 3 Implementation
 */
class SettingsDashboard extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationLabel = 'Einstellungen';
    protected static ?string $title = 'Einstellungen Dashboard';
    protected static string $view = 'filament.pages.settings-dashboard';
    protected static ?int $navigationSort = 1;

    public ?int $selectedCompanyId = null;
    public ?array $data = [];

    /**
     * Authorization check
     */
    public static function canAccess(): bool
    {
        $user = Auth::guard('admin')->user();

        if (!$user) {
            return false;
        }

        // Super admin sees all companies
        if ($user->hasRole('super_admin')) {
            return true;
        }

        // Company admin and manager can see their own company settings
        return $user->hasAnyRole(['company_admin', 'manager']);
    }

    /**
     * Mount the page
     */
    public function mount(): void
    {
        $user = Auth::guard('admin')->user();

        // Set default company based on user role
        if ($user && $user->hasRole('super_admin')) {
            $this->selectedCompanyId = Company::first()?->id;
        } elseif ($user) {
            $this->selectedCompanyId = $user->company_id;
        }

        $this->loadSettings();
    }

    /**
     * Load settings from SystemSetting (key-value storage)
     * Note: Model automatically decrypts via getParsedValue() when is_encrypted=true
     */
    protected function loadSettings(): void
    {
        if (!$this->selectedCompanyId) {
            $this->data = [];
            return;
        }

        // Get all settings for this company
        $settingsQuery = SystemSetting::where('company_id', $this->selectedCompanyId)->get();

        $settings = [];
        foreach ($settingsQuery as $setting) {
            // Use getParsedValue() which handles decryption automatically
            $settings[$setting->key] = $setting->getParsedValue();
        }

        // FIX: Load from companies table as fallback when system_settings is empty
        // This handles cases where data exists in companies table but not in system_settings
        $company = Company::find($this->selectedCompanyId);
        if ($company) {
            // Retell AI - use company data if system_settings is empty
            if (empty($settings['retell_api_key']) && !empty($company->retell_api_key)) {
                $settings['retell_api_key'] = $company->retell_api_key;
            }
            if (empty($settings['retell_agent_id']) && !empty($company->retell_agent_id)) {
                $settings['retell_agent_id'] = $company->retell_agent_id;
            }

            // Cal.com - use company data if system_settings is empty
            if (empty($settings['calcom_api_key']) && !empty($company->calcom_api_key)) {
                $settings['calcom_api_key'] = $company->calcom_api_key;
            }
            if (empty($settings['calcom_event_type_id']) && !empty($company->calcom_event_type_id)) {
                $settings['calcom_event_type_id'] = $company->calcom_event_type_id;
            }
            if (empty($settings['calcom_team_id']) && !empty($company->calcom_team_id)) {
                $settings['calcom_team_id'] = $company->calcom_team_id;
            }
            if (empty($settings['calcom_team_slug']) && !empty($company->calcom_team_slug)) {
                $settings['calcom_team_slug'] = $company->calcom_team_slug;
            }

            // Note: OpenAI keys are stored in system_settings only, not in companies table
        }

        // Define defaults
        $defaults = [
            // Retell AI
            'retell_api_key' => null,
            'retell_agent_id' => null,
            'retell_test_mode' => false,

            // Cal.com
            'calcom_api_key' => null,
            'calcom_team_id' => null,
            'calcom_team_slug' => null,
            'calcom_event_type_id' => null,
            'calcom_availability_schedule_id' => null,

            // OpenAI
            'openai_api_key' => null,
            'openai_organization_id' => null,

            // Qdrant
            'qdrant_url' => config('services.qdrant.url', 'https://qdrant.askproai.de'),
            'qdrant_api_key' => null,
            'qdrant_collection_name' => 'ultrathink_crm',

            // Calendar
            'calendar_first_day_of_week' => 1,
            'calendar_default_view' => 'month',
            'calendar_time_format' => '24h',
            'calendar_timezone' => 'Europe/Berlin',
        ];

        // Merge settings with defaults
        $this->data = array_merge($defaults, $settings);

        // getParsedValue() already handles type casting, but we keep these for defaults
        $this->data['retell_test_mode'] = filter_var($this->data['retell_test_mode'], FILTER_VALIDATE_BOOLEAN);

        if (isset($this->data['calcom_event_type_id'])) {
            $this->data['calcom_event_type_id'] = (int) $this->data['calcom_event_type_id'];
        }
        if (isset($this->data['calcom_availability_schedule_id'])) {
            $this->data['calcom_availability_schedule_id'] = (int) $this->data['calcom_availability_schedule_id'];
        }
        if (isset($this->data['calendar_first_day_of_week'])) {
            $this->data['calendar_first_day_of_week'] = (int) $this->data['calendar_first_day_of_week'];
        }

        // Load Branches, Services, and Staff data directly from database
        // NOTE: Branches do NOT have calcom_event_type_id - they link to Services (which have event_type_ids)
        $this->data['branches'] = Branch::where('company_id', $this->selectedCompanyId)
            ->get()
            ->map(function ($branch) {
                return [
                    'id' => $branch->id,
                    'name' => $branch->name,
                    'city' => $branch->city,
                    'active' => $branch->active ?? true,
                    // calcom_event_type_id removed - branches link to services via pivot
                    'retell_agent_id' => $branch->retell_agent_id,
                    'phone_number' => $branch->phone_number,
                    'notification_email' => $branch->notification_email,
                ];
            })
            ->toArray();

        $this->data['services'] = Service::where('company_id', $this->selectedCompanyId)
            ->get()
            ->map(function ($service) {
                return [
                    'id' => $service->id,
                    'name' => $service->name,
                    'duration_minutes' => $service->duration_minutes,
                    'price' => $service->price,
                    'calcom_event_type_id' => $service->calcom_event_type_id,
                    'is_active' => $service->is_active ?? true,
                    'description' => $service->description,
                ];
            })
            ->toArray();

        $this->data['staff'] = Staff::where('company_id', $this->selectedCompanyId)
            ->get()
            ->map(function ($staff) {
                return [
                    'id' => $staff->id,
                    'name' => $staff->name,
                    'email' => $staff->email,
                    'position' => $staff->position,
                    'calcom_user_id' => $staff->calcom_user_id,
                    'is_active' => $staff->is_active ?? true,
                    'phone' => $staff->phone,
                ];
            })
            ->toArray();

        $this->form->fill($this->data);
    }

    /**
     * Form schema with tabs
     */
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Einstellungen')
                    ->tabs([
                        // Option A: Hybrid-Ansatz (Optimiert für Setup & tägliche Nutzung)
                        // 1. Status-Übersicht zuerst
                        $this->getSyncStatusTab(),

                        // 2-4. Business-Entitäten (Core)
                        $this->getBranchesTab(),
                        $this->getStaffTab(),
                        $this->getServicesTab(),

                        // 5-7. Haupt-Integrationen
                        $this->getCalcomTab(),
                        $this->getRetellAITab(),
                        $this->getCalendarTab(),

                        // 8-10. Konfiguration & Advanced
                        $this->getPoliciesTab(),
                        $this->getOpenAITab(),
                        $this->getQdrantTab(),
                    ])
                    ->contained(false)
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }

    /**
     * Retell AI Configuration Tab
     */
    protected function getRetellAITab(): Tabs\Tab
    {
        return Tabs\Tab::make('Retell AI')
            ->icon('heroicon-o-phone')
            ->schema([
                TextInput::make('retell_api_key')
                    ->label('API Key')
                    ->password()
                    ->revealable()
                    ->placeholder('sk_...')
                    ->helperText('Ihr Retell AI API-Schlüssel (verschlüsselt gespeichert)')
                    ->suffixAction(
                        \Filament\Forms\Components\Actions\Action::make('test_connection')
                            ->label('Testen')
                            ->icon('heroicon-o-arrow-path')
                            ->action('testRetellConnection')
                    ),

                TextInput::make('retell_agent_id')
                    ->label('Agent ID')
                    ->placeholder('agent_...')
                    ->helperText('Standard Retell Agent ID für neue Gespräche'),

                Toggle::make('retell_test_mode')
                    ->label('Testmodus')
                    ->helperText('Aktiviert den Testmodus für Retell AI (keine echten Anrufe)'),
            ]);
    }

    /**
     * Cal.com Configuration Tab
     */
    protected function getCalcomTab(): Tabs\Tab
    {
        return Tabs\Tab::make('Cal.com')
            ->icon('heroicon-o-calendar')
            ->schema([
                TextInput::make('calcom_api_key')
                    ->label('API Key')
                    ->password()
                    ->revealable()
                    ->placeholder('cal_...')
                    ->helperText('Ihr Cal.com API-Schlüssel (verschlüsselt gespeichert)')
                    ->suffixAction(
                        \Filament\Forms\Components\Actions\Action::make('test_connection')
                            ->label('Testen')
                            ->icon('heroicon-o-arrow-path')
                            ->action('testCalcomConnection')
                    ),

                Grid::make(2)->schema([
                    TextInput::make('calcom_team_id')
                        ->label('Team ID')
                        ->numeric()
                        ->helperText('Cal.com Team ID für diese Company'),

                    TextInput::make('calcom_team_slug')
                        ->label('Team Slug')
                        ->helperText('Cal.com Team Slug (z.B. "askproai")'),
                ]),

                TextInput::make('calcom_event_type_id')
                    ->label('Event Type ID (Standard)')
                    ->numeric()
                    ->helperText('Optional: Standard Event Type ID für Terminbuchungen'),

                TextInput::make('calcom_availability_schedule_id')
                    ->label('Availability Schedule ID')
                    ->numeric()
                    ->helperText('Verfügbarkeitsplan ID für Terminvergabe'),
            ]);
    }

    /**
     * OpenAI Configuration Tab
     */
    protected function getOpenAITab(): Tabs\Tab
    {
        return Tabs\Tab::make('OpenAI')
            ->icon('heroicon-o-sparkles')
            ->schema([
                TextInput::make('openai_api_key')
                    ->label('API Key')
                    ->password()
                    ->revealable()
                    ->placeholder('sk-...')
                    ->helperText('Ihr OpenAI API-Schlüssel (verschlüsselt gespeichert)')
                    ->suffixAction(
                        \Filament\Forms\Components\Actions\Action::make('test_connection')
                            ->label('Testen')
                            ->icon('heroicon-o-arrow-path')
                            ->action('testOpenAIConnection')
                    ),

                TextInput::make('openai_organization_id')
                    ->label('Organization ID')
                    ->placeholder('org-...')
                    ->helperText('Ihre OpenAI Organisation ID (optional)'),
            ]);
    }

    /**
     * Qdrant Configuration Tab
     */
    protected function getQdrantTab(): Tabs\Tab
    {
        return Tabs\Tab::make('Qdrant')
            ->icon('heroicon-o-circle-stack')
            ->schema([
                TextInput::make('qdrant_url')
                    ->label('Qdrant URL')
                    ->url()
                    ->placeholder('https://qdrant.example.com')
                    ->helperText('URL zu Ihrer Qdrant-Instanz'),

                TextInput::make('qdrant_api_key')
                    ->label('API Key')
                    ->password()
                    ->revealable()
                    ->helperText('Qdrant API-Schlüssel (verschlüsselt gespeichert)')
                    ->suffixAction(
                        \Filament\Forms\Components\Actions\Action::make('test_connection')
                            ->label('Testen')
                            ->icon('heroicon-o-arrow-path')
                            ->action('testQdrantConnection')
                    ),

                TextInput::make('qdrant_collection_name')
                    ->label('Collection Name')
                    ->placeholder('ultrathink_crm')
                    ->helperText('Name der Qdrant-Collection'),
            ]);
    }

    /**
     * Calendar Configuration Tab
     */
    protected function getCalendarTab(): Tabs\Tab
    {
        return Tabs\Tab::make('Kalender')
            ->icon('heroicon-o-calendar-days')
            ->schema([
                Select::make('calendar_first_day_of_week')
                    ->label('Erster Wochentag')
                    ->options([
                        0 => 'Sonntag',
                        1 => 'Montag',
                        6 => 'Samstag',
                    ])
                    ->default(1),

                Select::make('calendar_default_view')
                    ->label('Standard-Ansicht')
                    ->options([
                        'day' => 'Tag',
                        'week' => 'Woche',
                        'month' => 'Monat',
                    ])
                    ->default('month'),

                Select::make('calendar_time_format')
                    ->label('Zeitformat')
                    ->options([
                        '12h' => '12-Stunden (AM/PM)',
                        '24h' => '24-Stunden',
                    ])
                    ->default('24h'),

                Select::make('calendar_timezone')
                    ->label('Zeitzone')
                    ->options([
                        'Europe/Berlin' => 'Europa/Berlin (MEZ)',
                        'Europe/London' => 'Europa/London (GMT)',
                        'America/New_York' => 'Amerika/New York (EST)',
                        'America/Los_Angeles' => 'Amerika/Los Angeles (PST)',
                        'Asia/Tokyo' => 'Asien/Tokio (JST)',
                    ])
                    ->searchable()
                    ->default('Europe/Berlin'),
            ]);
    }

    /**
     * Policies Configuration Tab
     */
    protected function getPoliciesTab(): Tabs\Tab
    {
        return Tabs\Tab::make('Richtlinien')
            ->icon('heroicon-o-shield-check')
            ->schema([
                \Filament\Forms\Components\Placeholder::make('policies_info')
                    ->label('Information')
                    ->content('Richtlinien für Stornierung, Umbuchung und wiederkehrende Termine werden in der separaten PolicyConfiguration-Ressource verwaltet.')
                    ->columnSpanFull(),

                \Filament\Forms\Components\Actions::make([
                    \Filament\Forms\Components\Actions\Action::make('manage_policies')
                        ->label('Richtlinien verwalten →')
                        ->icon('heroicon-o-arrow-right')
                        ->url(fn() => route('filament.admin.resources.policy-configurations.index'))
                        ->color('primary'),
                ])
                ->alignCenter()
                ->columnSpanFull(),
            ]);
    }

    /**
     * Branches & Filialen Configuration Tab
     */
    protected function getBranchesTab(): Tabs\Tab
    {
        return Tabs\Tab::make('Filialen')
            ->icon('heroicon-o-building-storefront')
            ->schema([
                Section::make('Filialen & Standorte')
                    ->description('Verwalten Sie alle Filialen/Standorte für diese Company')
                    ->schema([
                        Repeater::make('branches')
                            ->label('')
                            ->schema([
                                Grid::make(3)->schema([
                                    TextInput::make('name')
                                        ->label('Filialname')
                                        ->required()
                                        ->placeholder('z.B. Hauptfiliale Berlin'),

                                    TextInput::make('city')
                                        ->label('Stadt')
                                        ->placeholder('z.B. Berlin'),

                                    Toggle::make('active')
                                        ->label('Aktiv')
                                        ->default(true),
                                ]),

                                // NOTE: Branches do NOT have calcom_event_type_id
                                // Services are linked to branches via branch_service pivot
                                // Each service has its own calcom_event_type_id

                                TextInput::make('retell_agent_id')
                                    ->label('Retell Agent ID')
                                    ->placeholder('agent_...')
                                    ->helperText('Spezifischer Retell Agent für diese Filiale')
                                    ->columnSpan(2),

                                TextInput::make('phone_number')
                                    ->label('Telefonnummer')
                                    ->tel()
                                    ->placeholder('+49 30 12345678'),

                                TextInput::make('notification_email')
                                    ->label('Benachrichtigungs-E-Mail')
                                    ->email()
                                    ->placeholder('filiale@beispiel.de'),
                            ])
                            ->defaultItems(0)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['name'] ?? 'Neue Filiale')
                            ->addActionLabel('Filiale hinzufügen')
                            ->reorderable()
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    /**
     * Services & Dienstleistungen Configuration Tab
     */
    protected function getServicesTab(): Tabs\Tab
    {
        return Tabs\Tab::make('Dienstleistungen')
            ->icon('heroicon-o-scissors')
            ->schema([
                Section::make('Dienstleistungen & Services')
                    ->description('Alle Dienstleistungen für diese Company')
                    ->schema([
                        Repeater::make('services')
                            ->label('')
                            ->schema([
                                Grid::make(3)->schema([
                                    TextInput::make('name')
                                        ->label('Servicename')
                                        ->required()
                                        ->placeholder('z.B. Herrenhaarschnitt'),

                                    TextInput::make('duration_minutes')
                                        ->label('Dauer (Min)')
                                        ->numeric()
                                        ->placeholder('30')
                                        ->suffix('min'),

                                    TextInput::make('price')
                                        ->label('Preis')
                                        ->numeric()
                                        ->prefix('€')
                                        ->placeholder('25.00'),
                                ]),

                                Grid::make(2)->schema([
                                    TextInput::make('calcom_event_type_id')
                                        ->label('Cal.com Event Type ID')
                                        ->numeric()
                                        ->placeholder('Event Type ID')
                                        ->helperText('Verknüpfung zu Cal.com Event Type'),

                                    Toggle::make('is_active')
                                        ->label('Aktiv')
                                        ->default(true),
                                ]),

                                TextInput::make('description')
                                    ->label('Beschreibung')
                                    ->placeholder('Kurze Beschreibung der Dienstleistung'),
                            ])
                            ->defaultItems(0)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['name'] ?? 'Neue Dienstleistung')
                            ->addActionLabel('Dienstleistung hinzufügen')
                            ->reorderable()
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    /**
     * Staff & Mitarbeiter Configuration Tab
     */
    protected function getStaffTab(): Tabs\Tab
    {
        return Tabs\Tab::make('Mitarbeiter')
            ->icon('heroicon-o-user-group')
            ->schema([
                Section::make('Mitarbeiter & Staff')
                    ->description('Alle Mitarbeiter für diese Company')
                    ->schema([
                        Repeater::make('staff')
                            ->label('')
                            ->schema([
                                Grid::make(3)->schema([
                                    TextInput::make('name')
                                        ->label('Name')
                                        ->required()
                                        ->placeholder('Max Mustermann'),

                                    TextInput::make('email')
                                        ->label('E-Mail')
                                        ->email()
                                        ->placeholder('max@beispiel.de'),

                                    TextInput::make('position')
                                        ->label('Position')
                                        ->placeholder('Friseur'),
                                ]),

                                Grid::make(2)->schema([
                                    TextInput::make('calcom_user_id')
                                        ->label('Cal.com User ID')
                                        ->numeric()
                                        ->placeholder('Cal.com User ID')
                                        ->helperText('Verknüpfung zu Cal.com User'),

                                    Toggle::make('is_active')
                                        ->label('Aktiv')
                                        ->default(true),
                                ]),

                                TextInput::make('phone')
                                    ->label('Telefon')
                                    ->tel()
                                    ->placeholder('+49 30 12345678'),
                            ])
                            ->defaultItems(0)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['name'] ?? 'Neuer Mitarbeiter')
                            ->addActionLabel('Mitarbeiter hinzufügen')
                            ->reorderable()
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    /**
     * Sync Status & Overview Tab - State-of-the-Art Design
     */
    protected function getSyncStatusTab(): Tabs\Tab
    {
        return Tabs\Tab::make('Sync-Status')
            ->icon('heroicon-o-chart-bar-square')
            ->schema([
                Section::make()
                    ->schema([
                        \Filament\Forms\Components\Placeholder::make('sync_dashboard')
                            ->label('')
                            ->content(function () {
                                if (!$this->selectedCompanyId) {
                                    return new HtmlString('<div class="text-center py-8 text-gray-500">Bitte wählen Sie eine Company aus.</div>');
                                }

                                return new HtmlString($this->renderSyncStatusDashboard());
                            })
                            ->columnSpanFull(),

                        \Filament\Forms\Components\Actions::make([
                            \Filament\Forms\Components\Actions\Action::make('refresh_sync_status')
                                ->label('Status aktualisieren')
                                ->icon('heroicon-o-arrow-path')
                                ->action(function () {
                                    $this->loadSettings();
                                    Notification::make()
                                        ->title('Sync-Status aktualisiert')
                                        ->success()
                                        ->send();
                                })
                                ->color('primary'),
                        ])
                        ->alignCenter()
                        ->columnSpanFull(),
                    ]),
            ]);
    }

    /**
     * Render State-of-the-Art Sync Status Dashboard
     */
    protected function renderSyncStatusDashboard(): string
    {
        $company = Company::find($this->selectedCompanyId);
        $branches = Branch::where('company_id', $this->selectedCompanyId)->get();
        $services = Service::where('company_id', $this->selectedCompanyId)->get();
        $staff = Staff::where('company_id', $this->selectedCompanyId)->get();

        // Calculate sync statistics
        // NOTE: Branches don't have event_type_id - they link to services via pivot
        // Count branches that have at least one active service
        $syncedBranches = $branches->filter(function($b) {
            return \DB::table('branch_service')
                ->where('branch_id', $b->id)
                ->where('is_active', true)
                ->exists();
        })->count();

        $syncedServices = $services->filter(fn($s) => !empty($s->calcom_event_type_id))->count();
        $syncedStaff = $staff->filter(fn($s) => !empty($s->calcom_user_id))->count();

        $totalBranches = $branches->count();
        $totalServices = $services->count();
        $totalStaff = $staff->count();

        // Calculate percentages
        $branchPercent = $totalBranches > 0 ? round(($syncedBranches / $totalBranches) * 100) : 0;
        $servicePercent = $totalServices > 0 ? round(($syncedServices / $totalServices) * 100) : 0;
        $staffPercent = $totalStaff > 0 ? round(($syncedStaff / $totalStaff) * 100) : 0;

        // FIX: Check retell_agent_id (not just api_key) and calcom_event_type_id
        $retellConfigured = !empty($company->retell_agent_id) || !empty($company->retell_api_key);
        $calcomConfigured = !empty($company->calcom_api_key) || !empty($company->calcom_event_type_id);

        // Status colors
        $branchColor = $branchPercent >= 80 ? 'success' : ($branchPercent >= 50 ? 'warning' : 'danger');
        $serviceColor = $servicePercent >= 80 ? 'success' : ($servicePercent >= 50 ? 'warning' : 'danger');
        $staffColor = $staffPercent >= 80 ? 'success' : ($staffPercent >= 50 ? 'warning' : 'danger');

        $retellBadge = $retellConfigured
            ? '<span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-medium bg-success-50 text-success-700 dark:bg-success-500/10 dark:text-success-400"><svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"></path></svg> Konfiguriert</span>'
            : '<span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-medium bg-danger-50 text-danger-700 dark:bg-danger-500/10 dark:text-danger-400"><svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd"></path></svg> Nicht konfiguriert</span>';

        $calcomBadge = $calcomConfigured
            ? '<span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-medium bg-success-50 text-success-700 dark:bg-success-500/10 dark:text-success-400"><svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"></path></svg> Konfiguriert</span>'
            : '<span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-medium bg-danger-50 text-danger-700 dark:bg-danger-500/10 dark:text-danger-400"><svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd"></path></svg> Nicht konfiguriert</span>';

        return <<<HTML
        <div class="space-y-6">
            <!-- Company Header -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                            {$company->name}
                        </h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            Synchronisierungs-Übersicht
                        </p>
                    </div>
                    <div class="text-right">
                        <div class="text-2xl font-bold text-gray-900 dark:text-white">
                            {$totalBranches}
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">
                            Filialen
                        </div>
                    </div>
                </div>
            </div>

            <!-- API Status Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Retell AI Card -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <div class="flex items-start justify-between">
                        <div>
                            <div class="flex items-center gap-2 mb-2">
                                <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"></path>
                                </svg>
                                <h4 class="text-sm font-semibold text-gray-900 dark:text-white">Retell AI</h4>
                            </div>
                            <div class="mt-3">
                                {$retellBadge}
                            </div>
                            {$this->renderAgentInfo($company)}
                        </div>
                    </div>
                </div>

                <!-- Cal.com Card -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <div class="flex items-start justify-between">
                        <div>
                            <div class="flex items-center gap-2 mb-2">
                                <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                                <h4 class="text-sm font-semibold text-gray-900 dark:text-white">Cal.com</h4>
                            </div>
                            <div class="mt-3">
                                {$calcomBadge}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Entity Sync Statistics -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Branches -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2">
                            <div class="p-2 bg-primary-50 dark:bg-primary-500/10 rounded-lg">
                                <svg class="w-5 h-5 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                </svg>
                            </div>
                            <div>
                                <h5 class="text-xs font-medium text-gray-500 dark:text-gray-400">Filialen</h5>
                                <p class="text-sm font-semibold text-gray-900 dark:text-white mt-0.5">
                                    {$syncedBranches} von {$totalBranches}
                                </p>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="text-lg font-bold text-gray-900 dark:text-white">{$branchPercent}%</span>
                        </div>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                        <div class="bg-{$branchColor}-600 h-2 rounded-full transition-all duration-300" style="width: {$branchPercent}%"></div>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">Mit aktiven Services</p>
                </div>

                <!-- Services -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2">
                            <div class="p-2 bg-primary-50 dark:bg-primary-500/10 rounded-lg">
                                <svg class="w-5 h-5 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.121 14.121L19 19m-7-7l7-7m-7 7l-2.879 2.879M12 12L9.121 9.121m0 5.758a3 3 0 10-4.243 4.243 3 3 0 004.243-4.243zm0-5.758a3 3 0 10-4.243-4.243 3 3 0 004.243 4.243z"></path>
                                </svg>
                            </div>
                            <div>
                                <h5 class="text-xs font-medium text-gray-500 dark:text-gray-400">Dienstleistungen</h5>
                                <p class="text-sm font-semibold text-gray-900 dark:text-white mt-0.5">
                                    {$syncedServices} von {$totalServices}
                                </p>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="text-lg font-bold text-gray-900 dark:text-white">{$servicePercent}%</span>
                        </div>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                        <div class="bg-{$serviceColor}-600 h-2 rounded-full transition-all duration-300" style="width: {$servicePercent}%"></div>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">Mit Cal.com verknüpft</p>
                </div>

                <!-- Staff -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2">
                            <div class="p-2 bg-primary-50 dark:bg-primary-500/10 rounded-lg">
                                <svg class="w-5 h-5 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                </svg>
                            </div>
                            <div>
                                <h5 class="text-xs font-medium text-gray-500 dark:text-gray-400">Mitarbeiter</h5>
                                <p class="text-sm font-semibold text-gray-900 dark:text-white mt-0.5">
                                    {$syncedStaff} von {$totalStaff}
                                </p>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="text-lg font-bold text-gray-900 dark:text-white">{$staffPercent}%</span>
                        </div>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                        <div class="bg-{$staffColor}-600 h-2 rounded-full transition-all duration-300" style="width: {$staffPercent}%"></div>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">Mit Cal.com verknüpft</p>
                </div>
            </div>
        </div>
        HTML;
    }

    /**
     * Render Retell Agent Info
     */
    protected function renderAgentInfo($company): string
    {
        if (empty($company->retell_agent_id)) {
            return '';
        }

        $agentId = $company->retell_agent_id;
        $shortId = substr($agentId, 0, 20) . '...';

        return <<<HTML
        <div class="mt-3 text-xs text-gray-500 dark:text-gray-400">
            <span class="font-mono">{$shortId}</span>
        </div>
        HTML;
    }

    /**
     * Header Actions - Removed, using inline submit button instead
     */

    /**
     * Save settings to SystemSetting (key-value storage)
     * Note: Model automatically encrypts via setValueAttribute() when is_encrypted=true
     */
    public function save(): void
    {
        $data = $this->form->getState();

        // Define which keys should be encrypted
        $encryptedKeys = [
            'retell_api_key',
            'calcom_api_key',
            'openai_api_key',
            'qdrant_api_key',
        ];

        // Define setting groups
        $groupMapping = [
            'retell_api_key' => 'retell_ai',
            'retell_agent_id' => 'retell_ai',
            'retell_test_mode' => 'retell_ai',
            'calcom_api_key' => 'calcom',
            'calcom_event_type_id' => 'calcom',
            'calcom_availability_schedule_id' => 'calcom',
            'openai_api_key' => 'openai',
            'openai_organization_id' => 'openai',
            'qdrant_url' => 'qdrant',
            'qdrant_api_key' => 'qdrant',
            'qdrant_collection_name' => 'qdrant',
            'calendar_first_day_of_week' => 'calendar',
            'calendar_default_view' => 'calendar',
            'calendar_time_format' => 'calendar',
            'calendar_timezone' => 'calendar',
        ];

        // Save each setting (SKIP arrays - those are handled separately)
        foreach ($data as $key => $value) {
            // Skip arrays (branches, services, staff) - they have their own save methods
            if (is_array($value)) {
                continue;
            }

            $isEncrypted = in_array($key, $encryptedKeys);

            // Model will automatically encrypt if is_encrypted=true via setValueAttribute()
            SystemSetting::updateOrCreate(
                [
                    'company_id' => $this->selectedCompanyId,
                    'key' => $key,
                ],
                [
                    'value' => $value,  // Pass plain value - model handles encryption
                    'group' => $groupMapping[$key] ?? 'general',
                    'is_encrypted' => $isEncrypted,
                    'updated_by' => auth()->id(),
                ]
            );
        }

        // Save Branches, Services, and Staff
        $this->saveBranches($data);
        $this->saveServices($data);
        $this->saveStaff($data);

        Notification::make()
            ->title('Einstellungen gespeichert')
            ->body('Alle Konfigurationen wurden erfolgreich aktualisiert.')
            ->success()
            ->send();
    }

    /**
     * Save Branches data
     */
    protected function saveBranches(array $data): void
    {
        if (!isset($data['branches'])) {
            return;
        }

        $submittedIds = [];

        foreach ($data['branches'] as $branchData) {
            if (isset($branchData['id'])) {
                // Update existing branch
                $branch = Branch::find($branchData['id']);
                if ($branch && $branch->company_id == $this->selectedCompanyId) {
                    $branch->update([
                        'name' => $branchData['name'] ?? $branch->name,
                        'city' => $branchData['city'] ?? $branch->city,
                        'active' => $branchData['active'] ?? true,
                        // NOTE: calcom_event_type_id removed - branches link to services via pivot
                        'retell_agent_id' => $branchData['retell_agent_id'] ?? null,
                        'phone_number' => $branchData['phone_number'] ?? null,
                        'notification_email' => $branchData['notification_email'] ?? null,
                    ]);
                    $submittedIds[] = $branch->id;
                }
            } else {
                // Create new branch (UUID is auto-generated by model)
                $branch = Branch::create([
                    'id' => (string) \Illuminate\Support\Str::uuid(), // Generate UUID for branch
                    'company_id' => $this->selectedCompanyId,
                    'name' => $branchData['name'],
                    'city' => $branchData['city'] ?? null,
                    'active' => $branchData['active'] ?? true,
                    // NOTE: calcom_event_type_id removed - branches link to services via pivot
                    'retell_agent_id' => $branchData['retell_agent_id'] ?? null,
                    'phone_number' => $branchData['phone_number'] ?? null,
                    'notification_email' => $branchData['notification_email'] ?? null,
                ]);
                $submittedIds[] = $branch->id;
            }
        }

        // Delete branches that were removed from the repeater
        Branch::where('company_id', $this->selectedCompanyId)
            ->whereNotIn('id', $submittedIds)
            ->delete();
    }

    /**
     * Save Services data
     */
    protected function saveServices(array $data): void
    {
        if (!isset($data['services'])) {
            return;
        }

        $submittedIds = [];

        foreach ($data['services'] as $serviceData) {
            if (isset($serviceData['id'])) {
                // Update existing service
                $service = Service::find($serviceData['id']);
                if ($service && $service->company_id == $this->selectedCompanyId) {
                    $service->update([
                        'name' => $serviceData['name'] ?? $service->name,
                        'duration_minutes' => $serviceData['duration_minutes'] ?? $service->duration_minutes,
                        'price' => $serviceData['price'] ?? $service->price,
                        'calcom_event_type_id' => $serviceData['calcom_event_type_id'] ?? null,
                        'is_active' => $serviceData['is_active'] ?? true,
                        'description' => $serviceData['description'] ?? null,
                    ]);
                    $submittedIds[] = $service->id;
                }
            } else {
                // Create new service
                $service = Service::create([
                    'company_id' => $this->selectedCompanyId,
                    'name' => $serviceData['name'],
                    'duration_minutes' => $serviceData['duration_minutes'] ?? 30,
                    'price' => $serviceData['price'] ?? 0,
                    'calcom_event_type_id' => $serviceData['calcom_event_type_id'] ?? null,
                    'is_active' => $serviceData['is_active'] ?? true,
                    'description' => $serviceData['description'] ?? null,
                ]);
                $submittedIds[] = $service->id;
            }
        }

        // Delete services that were removed from the repeater
        Service::where('company_id', $this->selectedCompanyId)
            ->whereNotIn('id', $submittedIds)
            ->delete();
    }

    /**
     * Save Staff data
     */
    protected function saveStaff(array $data): void
    {
        if (!isset($data['staff'])) {
            return;
        }

        $submittedIds = [];

        foreach ($data['staff'] as $staffData) {
            if (isset($staffData['id'])) {
                // Update existing staff
                $staff = Staff::find($staffData['id']);
                if ($staff && $staff->company_id == $this->selectedCompanyId) {
                    $staff->update([
                        'name' => $staffData['name'] ?? $staff->name,
                        'email' => $staffData['email'] ?? $staff->email,
                        // NOTE 2025-10-14: 'position' column does not exist in database table
                        // 'position' => $staffData['position'] ?? $staff->position,
                        'calcom_user_id' => $staffData['calcom_user_id'] ?? null,
                        'is_active' => $staffData['is_active'] ?? true,
                        'phone' => $staffData['phone'] ?? null,
                    ]);
                    $submittedIds[] = $staff->id;
                }
            } else {
                // Create new staff member (UUID is auto-generated by model)
                $staff = Staff::create([
                    'id' => (string) \Illuminate\Support\Str::uuid(), // Generate UUID for staff
                    'company_id' => $this->selectedCompanyId,
                    'name' => $staffData['name'],
                    'email' => $staffData['email'] ?? null,
                    // NOTE 2025-10-14: 'position' column does not exist in database table
                    // 'position' => $staffData['position'] ?? null,
                    'calcom_user_id' => $staffData['calcom_user_id'] ?? null,
                    'is_active' => $staffData['is_active'] ?? true,
                    'phone' => $staffData['phone'] ?? null,
                ]);
                $submittedIds[] = $staff->id;
            }
        }

        // Delete staff that were removed from the repeater
        Staff::where('company_id', $this->selectedCompanyId)
            ->whereNotIn('id', $submittedIds)
            ->delete();
    }

    /**
     * Test Retell AI connection
     */
    public function testRetellConnection(): void
    {
        $apiKey = $this->form->getState()['retell_api_key'] ?? null;

        if (!$apiKey) {
            Notification::make()
                ->title('API Key fehlt')
                ->danger()
                ->send();
            return;
        }

        // TODO: Implement actual Retell API test
        Notification::make()
            ->title('Retell AI Verbindung')
            ->body('Verbindungstest wird implementiert...')
            ->info()
            ->send();
    }

    /**
     * Test Cal.com connection
     */
    public function testCalcomConnection(): void
    {
        $apiKey = $this->form->getState()['calcom_api_key'] ?? null;

        if (!$apiKey) {
            Notification::make()
                ->title('API Key fehlt')
                ->danger()
                ->send();
            return;
        }

        // TODO: Implement actual Cal.com API test
        Notification::make()
            ->title('Cal.com Verbindung')
            ->body('Verbindungstest wird implementiert...')
            ->info()
            ->send();
    }

    /**
     * Test OpenAI connection
     */
    public function testOpenAIConnection(): void
    {
        $apiKey = $this->form->getState()['openai_api_key'] ?? null;

        if (!$apiKey) {
            Notification::make()
                ->title('API Key fehlt')
                ->danger()
                ->send();
            return;
        }

        // TODO: Implement actual OpenAI API test
        Notification::make()
            ->title('OpenAI Verbindung')
            ->body('Verbindungstest wird implementiert...')
            ->info()
            ->send();
    }

    /**
     * Test Qdrant connection
     */
    public function testQdrantConnection(): void
    {
        $url = $this->form->getState()['qdrant_url'] ?? null;
        $apiKey = $this->form->getState()['qdrant_api_key'] ?? null;

        if (!$url || !$apiKey) {
            Notification::make()
                ->title('URL oder API Key fehlt')
                ->danger()
                ->send();
            return;
        }

        // TODO: Implement actual Qdrant API test
        Notification::make()
            ->title('Qdrant Verbindung')
            ->body('Verbindungstest wird implementiert...')
            ->info()
            ->send();
    }

    /**
     * Get company selector options
     */
    public function getCompanyOptions(): array
    {
        $user = Auth::guard('admin')->user();

        if ($user && $user->hasRole('super_admin')) {
            return Company::pluck('name', 'id')->toArray();
        }

        if ($user && $user->company_id) {
            return [
                $user->company_id => Company::find($user->company_id)?->name ?? 'Unbekannt'
            ];
        }

        return [];
    }

    /**
     * Update company selection
     */
    public function updatedSelectedCompanyId(): void
    {
        $this->loadSettings();
    }
}
