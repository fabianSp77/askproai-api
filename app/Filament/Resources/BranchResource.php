<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BranchResource\Pages;
use App\Filament\Resources\BranchResource\RelationManagers;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Staff;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use App\Models\PolicyConfiguration;
use Illuminate\Support\HtmlString;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Filament\Notifications\Notification;

class BranchResource extends Resource
{
    protected static ?string $model = Branch::class;

    /**
     * Resource re-enabled 2025-11-05 - branches table fully restored with 50 columns
     * Database includes: phone_number, address, retell_agent_id, calendar_mode, etc.
     * Super Admin can now view and manage all branches across all companies
     *
     * FIX 2025-11-05 (second fix): Changed auth()->guard('admin') to auth()
     *
     * Reason: AdminPanelProvider uses authGuard('web'), but this method
     * was checking auth()->guard('admin')->user() which is always NULL
     * in Filament navigation context → Resource not visible!
     *
     * Solution: Use auth()->user() which respects the panel's configured guard
     */
    public static function canViewAny(): bool
    {
        $user = auth()->user();
        return $user && $user->can('viewAny', static::getModel());
    }

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';
    protected static ?string $navigationGroup = 'Stammdaten';
    protected static ?int $navigationSort = 5;
    protected static ?string $navigationLabel = 'Filialen';
    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationBadge(): ?string
    {
        return null; // EMERGENCY: Disabled to prevent memory exhaustion
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return null; // EMERGENCY: Disabled to prevent memory exhaustion
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Optimized to 3 logical tabs for branch management
                Tabs::make('Branch Details')
                    ->tabs([
                        // Tab 1: Essential Branch Information
                        Tabs\Tab::make('Filiale')
                            ->icon('heroicon-m-building-storefront')
                            ->schema([
                                Section::make('Grundinformationen')
                                    ->schema([
                                        Grid::make(2)->schema([
                                            Forms\Components\Select::make('company_id')
                                                ->label('Unternehmen')
                                                ->relationship('company', 'name')
                                                ->required()
                                                ->searchable()
                                                ->preload(),

                                            Forms\Components\TextInput::make('name')
                                                ->label('Filialname')
                                                ->required()
                                                ->maxLength(255)
                                                ->placeholder('Filiale Musterstadt'),
                                        ]),

                                        Grid::make(3)->schema([
                                            Forms\Components\TextInput::make('slug')
                                                ->label('URL Slug')
                                                ->maxLength(255)
                                                ->placeholder('musterstadt'),

                                            Forms\Components\Toggle::make('is_active')
                                                ->label('Aktiv')
                                                ->default(true),

                                            Forms\Components\Toggle::make('accepts_walkins')
                                                ->label('Walk-Ins akzeptiert')
                                                ->default(true),
                                        ]),

                                        Grid::make(2)->schema([
                                            Forms\Components\TextInput::make('phone_number')
                                                ->label('Telefon')
                                                ->tel()
                                                ->maxLength(255),

                                            Forms\Components\TextInput::make('notification_email')
                                                ->label('Benachrichtigungs-E-Mail')
                                                ->email()
                                                ->maxLength(255),
                                        ]),

                                        Forms\Components\TextInput::make('address')
                                            ->label('Adresse')
                                            ->maxLength(255),

                                        Grid::make(3)->schema([
                                            Forms\Components\TextInput::make('city')
                                                ->label('Stadt')
                                                ->maxLength(255),

                                            Forms\Components\TextInput::make('postal_code')
                                                ->label('PLZ')
                                                ->maxLength(10),

                                            Forms\Components\TextInput::make('country')
                                                ->label('Land')
                                                ->default('Deutschland')
                                                ->maxLength(255),
                                        ]),

                                        Forms\Components\TextInput::make('website')
                                            ->label('Website')
                                            ->url()
                                            ->maxLength(255),
                                    ]),
                            ]),

                        // Tab 2: Operations & Services
                        Tabs\Tab::make('Betrieb')
                            ->icon('heroicon-m-clock')
                            ->schema([
                                Section::make('Betriebsinformationen')
                                    ->schema([
                                        Grid::make(2)->schema([
                                            Forms\Components\TextInput::make('service_radius_km')
                                                ->label('Service-Radius (km)')
                                                ->numeric()
                                                ->suffix('km')
                                                ->default(0),

                                            Forms\Components\Select::make('calendar_mode')
                                                ->label('Kalender-Vererbung')
                                                ->options([
                                                    'inherit' => '📋 Von Unternehmen übernehmen',
                                                    'override' => '✏️ Eigene Einstellung',
                                                ])
                                                ->default('inherit')
                                                ->required()
                                                ->native(false)
                                                ->helperText('Bestimmt ob Filiale eigene Kalendereinstellungen nutzt'),
                                        ]),

                                        Grid::make(2)->schema([
                                            Forms\Components\Toggle::make('parking_available')
                                                ->label('Parkplätze verfügbar')
                                                ->default(false),

                                            /**
                                             * DISABLED: active column doesn't exist in Sept 21 database backup
                                             * TODO: Re-enable when database is fully restored
                                             */
                                            // Forms\Components\Toggle::make('active')
                                            //     ->label('Betriebsbereit')
                                            //     ->default(true),
                                        ]),

                                        Forms\Components\Textarea::make('business_hours')
                                            ->label('Öffnungszeiten')
                                            ->rows(4)
                                            ->placeholder('Mo-Fr: 08:00-18:00\nSa: 09:00-16:00\nSo: Geschlossen'),

                                        Forms\Components\Textarea::make('features')
                                            ->label('Besonderheiten')
                                            ->rows(3)
                                            ->placeholder('WLAN, Klimaanlage, Barrierefreiheit...'),

                                        Forms\Components\Textarea::make('public_transport_access')
                                            ->label('ÖPNV Anbindung')
                                            ->rows(2)
                                            ->placeholder('U-Bahn Linie 3, Bus 42...'),
                                    ]),
                            ]),

                        // Tab 3: Policies
                        Tabs\Tab::make('Richtlinien')
                            ->icon('heroicon-m-shield-check')
                            ->schema([
                                static::getPolicySection('cancellation', 'Stornierungsrichtlinie'),
                                static::getPolicySection('reschedule', 'Umbuchungsrichtlinie'),
                                static::getPolicySection('recurring', 'Wiederholungsrichtlinie'),
                            ]),

                        // Tab 4: Integration & Settings (Admin only)
                        Tabs\Tab::make('Integration')
                            ->icon('heroicon-m-cog')
                            ->visible(fn () => auth()->user() && (auth()->user()->hasRole('admin') || auth()->user()->hasRole('Admin') || auth()->user()->hasRole('super_admin') || auth()->user()->hasRole('Super Admin')))
                            ->schema([
                                Section::make('Integration & Einstellungen')
                                    ->schema([
                                        Grid::make(1)->schema([
                                            Forms\Components\Select::make('calendar_mode')
                                                ->label('Kalender-Vererbung')
                                                ->options([
                                                    'inherit' => '📋 Von Unternehmen übernehmen',
                                                    'override' => '✏️ Eigene Einstellung',
                                                ])
                                                ->default('inherit')
                                                ->helperText('Nutzt Einstellungen vom Unternehmen oder eigene'),
                                        ]),

                                        Forms\Components\Placeholder::make('integration_info')
                                            ->label('Integration Status')
                                            ->content(function (?Branch $record) {
                                                if (!$record) return 'Neue Filiale - noch keine Integrationen';

                                                $company = $record->company;
                                                $info = [];

                                                if ($company?->retell_api_key) {
                                                    $info[] = '✅ Retell (über Unternehmen)';
                                                }
                                                if ($company?->calcom_api_key) {
                                                    $info[] = '✅ Cal.com API (über Unternehmen)';
                                                }

                                                return empty($info)
                                                    ? '⚠️ Keine Integrationen konfiguriert'
                                                    : implode(' • ', $info);
                                            }),

                                        Forms\Components\Placeholder::make('calcom_architecture_info')
                                            ->label('Cal.com Architektur')
                                            ->helperText('Team ID auf Company-Level. Services mit Event Type IDs pro Filiale.')
                                            ->content(function (?Branch $record) {
                                                if (!$record) return 'Neue Filiale - Team ID wird von Company übernommen';

                                                $company = $record->company;
                                                $info = [];

                                                // Company Team ID
                                                if ($company?->calcom_team_id) {
                                                    $info[] = "🏢 Company Team ID: " . $company->calcom_team_id;
                                                } else {
                                                    $info[] = "⚠️ Company hat keine Team ID konfiguriert";
                                                }

                                                // Aktive Services mit Event Type IDs
                                                $activeServices = $record->activeServices()
                                                    ->whereNotNull('calcom_event_type_id')
                                                    ->get();

                                                if ($activeServices->count() > 0) {
                                                    $info[] = "\n📋 Aktive Services mit Event Type IDs:";
                                                    foreach ($activeServices as $service) {
                                                        $info[] = "  • {$service->name} → Event Type: {$service->calcom_event_type_id}";
                                                    }
                                                } else {
                                                    $info[] = "\n⚠️ Keine Services mit Event Type IDs aktiv";
                                                }

                                                $info[] = "\n💡 Services verwalten Sie unter dem 'Services' Tab";

                                                return implode("\n", $info);
                                            }),

                                        Grid::make(2)->schema([
                                            Forms\Components\Toggle::make('include_transcript_in_summary')
                                                ->label('Transkript in Summary')
                                                ->helperText('Überschreibt Unternehmenseinstellung'),

                                            Forms\Components\Toggle::make('include_csv_export')
                                                ->label('CSV Export einschließen')
                                                ->helperText('Überschreibt Unternehmenseinstellung'),
                                        ]),

                                        Forms\Components\TextInput::make('uuid')
                                            ->label('UUID')
                                            ->disabled()
                                            ->dehydrated(false),
                                    ]),
                            ]),

                        // Tab 5: Retell Agent Configuration
                        Tabs\Tab::make('Retell Agent')
                            ->icon('heroicon-m-microphone')
                            ->visible(fn () => auth()->user() && (auth()->user()->hasRole('admin') || auth()->user()->hasRole('Admin') || auth()->user()->hasRole('super_admin') || auth()->user()->hasRole('Super Admin')))
                            ->schema([
                                Section::make('Retell AI Agent Konfiguration')
                                    ->description('Verwalten Sie das Prompt und die Funktionen für den Retell AI Agenten dieser Filiale')
                                    ->schema([
                                        Grid::make(1)->schema([
                                            Forms\Components\Select::make('retell_template')
                                                ->label('Template auswählen')
                                                ->options([
                                                    'dynamic-service-selection-v127' => '🎯 Dynamic Service Selection (V127)',
                                                    'basic-appointment-booking' => '📅 Basic Appointment Booking',
                                                    'information-only' => 'ℹ️ Information Only',
                                                ])
                                                ->placeholder('Wählen Sie ein Template...')
                                                ->helperText('Das Template wird als Basis für die Konfiguration verwendet')
                                                ->dehydrated(false),
                                        ]),

                                        Forms\Components\Placeholder::make('retell_info')
                                            ->label('Agent Status')
                                            ->content(function (?Branch $record) {
                                                if (!$record) {
                                                    return view('filament.components.retell-no-branch');
                                                }

                                                $activePrompt = $record->retellAgentPrompts()
                                                    ->where('is_active', true)
                                                    ->first();

                                                if (!$activePrompt) {
                                                    return view('filament.components.retell-no-config');
                                                }

                                                return view('filament.components.retell-agent-info', [
                                                    'prompt' => $activePrompt,
                                                ]);
                                            }),

                                        Forms\Components\Actions::make([
                                            Forms\Components\Actions\Action::make('deploy_from_template')
                                                ->label('Aus Template deployen')
                                                ->action(function (Branch $record, Forms\Get $get) {
                                                    $template = $get('retell_template');
                                                    if (!$template) {
                                                        Notification::make()
                                                            ->title('Template erforderlich')
                                                            ->body('Bitte wählen Sie ein Template aus')
                                                            ->danger()
                                                            ->send();
                                                        return;
                                                    }

                                                    try {
                                                        $service = new \App\Services\Retell\RetellAgentManagementService();
                                                        $templateService = new \App\Services\Retell\RetellPromptTemplateService();

                                                        $promptVersion = $templateService->applyTemplateToBranch(
                                                            $record->id,
                                                            $template
                                                        );

                                                        $result = $service->deployPromptVersion($promptVersion, auth()->user());

                                                        if ($result['success']) {
                                                            Notification::make()
                                                                ->title('Erfolgreich deployed')
                                                                ->body('Agent-Version ' . $result['retell_version'] . ' ist jetzt aktiv')
                                                                ->success()
                                                                ->send();
                                                        } else {
                                                            Notification::make()
                                                                ->title('Deployment fehlgeschlagen')
                                                                ->body(implode(', ', $result['errors'] ?? []))
                                                                ->danger()
                                                                ->send();
                                                        }
                                                    } catch (\Exception $e) {
                                                        Notification::make()
                                                            ->title('Fehler')
                                                            ->body($e->getMessage())
                                                            ->danger()
                                                            ->send();
                                                    }
                                                })
                                                ->icon('heroicon-m-rocket-launch'),
                                        ]),

                                        // LIVE AGENT STATUS SECTION
                                        Section::make('📡 Live Agent Status')
                                            ->visible(fn (?Branch $record) => (bool) $record)
                                            ->description('Echtzeit-Daten vom Retell API Server')
                                            ->collapsible()
                                            ->schema([
                                                Forms\Components\Placeholder::make('live_agent_sync_status')
                                                    ->label('Sync Status')
                                                    ->content(function (?Branch $record) {
                                                        if (!$record) return 'Keine Daten';

                                                        try {
                                                            $service = new \App\Services\Retell\RetellAgentManagementService();
                                                            $syncStatus = $service->checkSync($record);

                                                            $html = '<div style="padding: 1rem; border-radius: 0.5rem; background-color: #f9fafb;">';

                                                            // Sync Status Badge
                                                            if ($syncStatus['in_sync']) {
                                                                $html .= '<div style="margin-bottom: 1rem;"><span style="display: inline-flex; align-items: center; padding: 0.5rem 1rem; background-color: #10b981; color: white; border-radius: 0.5rem; font-weight: 600;">✅ Synchronisiert</span></div>';
                                                            } else {
                                                                $statusColor = $syncStatus['status'] === 'error' ? '#ef4444' : '#f59e0b';
                                                                $statusEmoji = $syncStatus['status'] === 'error' ? '🚨' : '⚠️';
                                                                $html .= '<div style="margin-bottom: 1rem;"><span style="display: inline-flex; align-items: center; padding: 0.5rem 1rem; background-color: ' . $statusColor . '; color: white; border-radius: 0.5rem; font-weight: 600;">' . $statusEmoji . ' Nicht synchronisiert</span></div>';
                                                            }

                                                            // Message
                                                            $html .= '<p style="margin-bottom: 1rem; color: #6b7280;">' . $syncStatus['message'] . '</p>';

                                                            // Comparison Table
                                                            if ($syncStatus['local'] || $syncStatus['live']) {
                                                                $html .= '<table style="width: 100%; border-collapse: collapse; margin-top: 1rem;">';
                                                                $html .= '<thead><tr style="background-color: #e5e7eb;">';
                                                                $html .= '<th style="padding: 0.5rem; text-align: left; font-weight: 600;">Eigenschaft</th>';
                                                                $html .= '<th style="padding: 0.5rem; text-align: left; font-weight: 600;">Lokal (DB)</th>';
                                                                $html .= '<th style="padding: 0.5rem; text-align: left; font-weight: 600;">Live (Retell API)</th>';
                                                                $html .= '</tr></thead><tbody>';

                                                                // Agent ID
                                                                $html .= '<tr style="border-bottom: 1px solid #e5e7eb;">';
                                                                $html .= '<td style="padding: 0.5rem; font-weight: 500;">Agent ID</td>';
                                                                $html .= '<td style="padding: 0.5rem; font-family: monospace; font-size: 0.875rem;">' . ($syncStatus['local']['agent_id'] ?? 'N/A') . '</td>';
                                                                $html .= '<td style="padding: 0.5rem; font-family: monospace; font-size: 0.875rem;">' . ($syncStatus['live']['agent_id'] ?? 'N/A') . '</td>';
                                                                $html .= '</tr>';

                                                                // Agent Name
                                                                $html .= '<tr style="border-bottom: 1px solid #e5e7eb;">';
                                                                $html .= '<td style="padding: 0.5rem; font-weight: 500;">Agent Name</td>';
                                                                $html .= '<td style="padding: 0.5rem;">' . ($syncStatus['local']['agent_name'] ?? 'N/A') . '</td>';
                                                                $html .= '<td style="padding: 0.5rem;">' . ($syncStatus['live']['agent_name'] ?? 'N/A') . '</td>';
                                                                $html .= '</tr>';

                                                                // Prompt Length
                                                                $html .= '<tr style="border-bottom: 1px solid #e5e7eb;">';
                                                                $html .= '<td style="padding: 0.5rem; font-weight: 500;">Prompt Länge</td>';
                                                                $html .= '<td style="padding: 0.5rem;">' . ($syncStatus['local']['prompt_length'] ?? 'N/A') . ' Zeichen</td>';
                                                                $html .= '<td style="padding: 0.5rem;">' . ($syncStatus['live']['prompt_length'] ?? 'N/A') . ' Zeichen</td>';
                                                                $html .= '</tr>';

                                                                // Functions Count
                                                                $html .= '<tr style="border-bottom: 1px solid #e5e7eb;">';
                                                                $html .= '<td style="padding: 0.5rem; font-weight: 500;">Functions Anzahl</td>';
                                                                $html .= '<td style="padding: 0.5rem;">' . ($syncStatus['local']['functions_count'] ?? 'N/A') . '</td>';
                                                                $html .= '<td style="padding: 0.5rem;">' . ($syncStatus['live']['functions_count'] ?? 'N/A') . '</td>';
                                                                $html .= '</tr>';

                                                                // Published Status
                                                                $html .= '<tr>';
                                                                $html .= '<td style="padding: 0.5rem; font-weight: 500;">Veröffentlicht</td>';
                                                                $html .= '<td style="padding: 0.5rem;">' . (($syncStatus['local']['is_published'] ?? false) ? '✅ Ja' : '❌ Nein') . '</td>';
                                                                $html .= '<td style="padding: 0.5rem;">' . (($syncStatus['live']['is_published'] ?? false) ? '✅ Ja' : '❌ Nein') . '</td>';
                                                                $html .= '</tr>';

                                                                $html .= '</tbody></table>';
                                                            }

                                                            $html .= '</div>';

                                                            return new \Illuminate\Support\HtmlString($html);

                                                        } catch (\Exception $e) {
                                                            return '🚨 Fehler beim Abrufen des Live-Status: ' . $e->getMessage();
                                                        }
                                                    })
                                                    ->extraAttributes(['class' => 'live-agent-status']),

                                                Forms\Components\Actions::make([
                                                    Forms\Components\Actions\Action::make('refresh_live_status')
                                                        ->label('Live-Daten von Retell laden')
                                                        ->action(function () {
                                                            // This will trigger a page refresh to reload the live data
                                                            Notification::make()
                                                                ->title('Live-Daten aktualisiert')
                                                                ->body('Die Echtzeit-Daten vom Retell API wurden neu geladen')
                                                                ->success()
                                                                ->send();
                                                        })
                                                        ->icon('heroicon-m-arrow-path')
                                                        ->color('primary'),

                                                    Forms\Components\Actions\Action::make('load_live_to_editor')
                                                        ->label('Live-Daten in Editor laden')
                                                        ->action(function (Branch $record, Forms\Set $set) {
                                                            if (!$record) return;

                                                            try {
                                                                $service = new \App\Services\Retell\RetellAgentManagementService();
                                                                $liveAgent = $service->getLiveAgent();

                                                                if ($liveAgent) {
                                                                    // Load live prompt into editor
                                                                    $set('retell_prompt_content', $liveAgent['agent_prompt'] ?? '');

                                                                    Notification::make()
                                                                        ->title('Live-Prompt geladen')
                                                                        ->body('Das Prompt vom veröffentlichten Agent wurde in den Editor geladen')
                                                                        ->success()
                                                                        ->send();
                                                                } else {
                                                                    Notification::make()
                                                                        ->title('Kein veröffentlichter Agent')
                                                                        ->body('Es konnte kein veröffentlichter Agent auf Retell API gefunden werden')
                                                                        ->warning()
                                                                        ->send();
                                                                }
                                                            } catch (\Exception $e) {
                                                                Notification::make()
                                                                    ->title('Fehler')
                                                                    ->body($e->getMessage())
                                                                    ->danger()
                                                                    ->send();
                                                            }
                                                        })
                                                        ->icon('heroicon-m-arrow-down-tray')
                                                        ->color('success'),
                                                ]),
                                            ]),

                                        // PROMPT EDITOR SECTION
                                        Section::make('🎤 Prompt Editor')
                                            ->visible(fn (?Branch $record) => (bool) $record)
                                            ->description('Bearbeiten Sie die Ansprache des Agenten - lädt automatisch den aktuellen Live-Prompt')
                                            ->schema([
                                                Forms\Components\Textarea::make('retell_prompt_content')
                                                    ->label('Agent Ansprache (Prompt)')
                                                    ->rows(15)
                                                    ->maxLength(10000)
                                                    ->helperText('Max 10.000 Zeichen. Lädt automatisch den aktuellen Live-Prompt vom Retell API.')
                                                    ->dehydrated(false)
                                                    ->afterStateHydrated(function (Forms\Set $set, ?Branch $record) {
                                                        if (!$record) return;

                                                        // 1. Versuche lokalen aktiven Prompt zu laden
                                                        $activePrompt = $record->retellAgentPrompts()
                                                            ->where('is_active', true)
                                                            ->first();

                                                        if ($activePrompt && $activePrompt->prompt_content) {
                                                            $set('retell_prompt_content', $activePrompt->prompt_content);
                                                            return;
                                                        }

                                                        // 2. Wenn kein lokaler Prompt, lade Live-Prompt von Retell API
                                                        try {
                                                            $service = new \App\Services\Retell\RetellAgentManagementService();
                                                            $liveAgent = $service->getLiveAgent();

                                                            if ($liveAgent && isset($liveAgent['agent_prompt'])) {
                                                                $set('retell_prompt_content', $liveAgent['agent_prompt']);
                                                            }
                                                        } catch (\Exception $e) {
                                                            // Stiller Fehler - wird nicht angezeigt
                                                            \Illuminate\Support\Facades\Log::warning('Could not load live prompt', [
                                                                'branch_id' => $record->id,
                                                                'error' => $e->getMessage()
                                                            ]);
                                                        }
                                                    }),

                                                Forms\Components\Placeholder::make('prompt_status')
                                                    ->label('Status')
                                                    ->content(fn (Forms\Get $get) => '✅ ' . strlen($get('retell_prompt_content') ?? '') . ' / 10.000 Zeichen'),

                                                Forms\Components\Actions::make([
                                                    Forms\Components\Actions\Action::make('update_prompt')
                                                        ->label('Prompt speichern & deployen')
                                                        ->action(function (Branch $record, Forms\Get $get) {
                                                            if (!$record) return;

                                                            $newPromptContent = $get('retell_prompt_content');
                                                            if (!$newPromptContent) {
                                                                Notification::make()
                                                                    ->title('Fehler')
                                                                    ->body('Prompt-Inhalt ist erforderlich')
                                                                    ->danger()
                                                                    ->send();
                                                                return;
                                                            }

                                                            try {
                                                                $service = new \App\Services\Retell\RetellAgentManagementService();
                                                                $activePrompt = $record->retellAgentPrompts()
                                                                    ->where('is_active', true)
                                                                    ->first();

                                                                if ($activePrompt) {
                                                                    // Update existing prompt
                                                                    $result = $service->updatePromptContent($activePrompt, $newPromptContent, auth()->user());
                                                                } else {
                                                                    // Create new prompt from live agent
                                                                    $liveAgent = $service->getLiveAgent();
                                                                    $functionsConfig = $liveAgent['functions'] ?? [];

                                                                    // Create new version
                                                                    $newVersion = \App\Models\RetellAgentPrompt::create([
                                                                        'branch_id' => $record->id,
                                                                        'version' => \App\Models\RetellAgentPrompt::getNextVersionForBranch($record->id),
                                                                        'prompt_content' => $newPromptContent,
                                                                        'functions_config' => $functionsConfig,
                                                                        'is_active' => false,
                                                                        'is_template' => false,
                                                                        'validation_status' => 'pending',
                                                                        'deployment_notes' => 'Erstellt vom Live-Agent',
                                                                    ]);

                                                                    $result = $service->deployPromptVersion($newVersion, auth()->user());
                                                                }

                                                                if ($result['success']) {
                                                                    Notification::make()
                                                                        ->title('✅ Erfolgreich gespeichert')
                                                                        ->body('Der Prompt wurde deployed und ist jetzt live')
                                                                        ->success()
                                                                        ->send();
                                                                } else {
                                                                    Notification::make()
                                                                        ->title('❌ Deployment fehlgeschlagen')
                                                                        ->body(implode(', ', $result['errors'] ?? [$result['message'] ?? 'Unbekannter Fehler']))
                                                                        ->danger()
                                                                        ->send();
                                                                }
                                                            } catch (\Exception $e) {
                                                                Notification::make()
                                                                    ->title('Fehler')
                                                                    ->body($e->getMessage())
                                                                    ->danger()
                                                                    ->send();
                                                            }
                                                        })
                                                        ->icon('heroicon-m-arrow-up')
                                                        ->color('success'),
                                                ]),
                                            ]),

                                        // FUNCTIONS MANAGER SECTION
                                        Section::make('⚙️ Functions Manager')
                                            ->visible(fn (?Branch $record) => (bool) $record)
                                            ->description('Verwalten Sie die Funktionen des Agenten - lädt automatisch die aktuellen Live-Functions')
                                            ->schema([
                                                Forms\Components\Placeholder::make('functions_list')
                                                    ->label('Aktive Funktionen')
                                                    ->content(function (?Branch $record) {
                                                        if (!$record) return '—';

                                                        // 1. Versuche lokale Functions zu laden
                                                        $activePrompt = $record->retellAgentPrompts()
                                                            ->where('is_active', true)
                                                            ->first();

                                                        $functions = null;
                                                        $source = 'lokal';

                                                        if ($activePrompt && $activePrompt->functions_config) {
                                                            $functions = $activePrompt->functions_config;
                                                        } else {
                                                            // 2. Lade Live Functions von Retell API
                                                            try {
                                                                $service = new \App\Services\Retell\RetellAgentManagementService();
                                                                $liveAgent = $service->getLiveAgent();
                                                                if ($liveAgent && isset($liveAgent['functions'])) {
                                                                    $functions = $liveAgent['functions'];
                                                                    $source = 'live (Retell API)';
                                                                }
                                                            } catch (\Exception $e) {
                                                                return '⚠️ Keine Funktionen gefunden';
                                                            }
                                                        }

                                                        if (!$functions || empty($functions)) {
                                                            return '⚠️ Keine Funktionen konfiguriert';
                                                        }

                                                        $html = '<div class="mb-2 text-xs text-gray-500">Quelle: ' . $source . '</div>';
                                                        $html .= '<div class="space-y-2">';
                                                        foreach ($functions as $func) {
                                                            $html .= '<div class="flex items-start gap-3 p-3 bg-blue-50 border border-blue-200 rounded">';
                                                            $html .= '<div>';
                                                            $html .= '<p class="font-semibold text-blue-900">' . ($func['name'] ?? 'Unnamed') . '</p>';
                                                            $html .= '<p class="text-sm text-blue-700">' . ($func['description'] ?? 'Keine Beschreibung') . '</p>';
                                                            $html .= '</div>';
                                                            $html .= '</div>';
                                                        }
                                                        $html .= '</div>';

                                                        return new \Illuminate\Support\HtmlString($html);
                                                    }),

                                                Section::make('➕ Neue Custom Function')
                                                    ->collapsible()
                                                    ->collapsed()
                                                    ->schema([
                                                        Forms\Components\TextInput::make('custom_function_name')
                                                            ->label('Function Name')
                                                            ->placeholder('z.B. check_availability_custom')
                                                            ->required()
                                                            ->helperText('Nur Kleinbuchstaben, Zahlen und Unterstrich')
                                                            ->dehydrated(false),

                                                        Forms\Components\Textarea::make('custom_function_description')
                                                            ->label('Beschreibung')
                                                            ->placeholder('Was macht diese Funktion?')
                                                            ->required()
                                                            ->dehydrated(false),

                                                        Forms\Components\Textarea::make('custom_function_parameters')
                                                            ->label('Parameters (JSON)')
                                                            ->placeholder('{"type":"object","properties":{},"required":[]}')
                                                            ->rows(6)
                                                            ->required()
                                                            ->hint('Gültige JSON-Struktur erforderlich')
                                                            ->dehydrated(false),

                                                        Forms\Components\Actions::make([
                                                            Forms\Components\Actions\Action::make('add_custom_function')
                                                                ->label('Custom Function hinzufügen')
                                                                ->action(function (Branch $record, Forms\Get $get) {
                                                                    if (!$record) return;

                                                                    $name = $get('custom_function_name');
                                                                    $description = $get('custom_function_description');
                                                                    $parametersJson = $get('custom_function_parameters');

                                                                    if (!$name || !$description || !$parametersJson) {
                                                                        Notification::make()
                                                                            ->title('Fehler')
                                                                            ->body('Alle Felder sind erforderlich')
                                                                            ->danger()
                                                                            ->send();
                                                                        return;
                                                                    }

                                                                    try {
                                                                        $parameters = json_decode($parametersJson, true);
                                                                        if (!$parameters) {
                                                                            Notification::make()
                                                                                ->title('Fehler')
                                                                                ->body('Parameters JSON ist nicht gültig')
                                                                                ->danger()
                                                                                ->send();
                                                                            return;
                                                                        }

                                                                        $activePrompt = $record->retellAgentPrompts()
                                                                            ->where('is_active', true)
                                                                            ->first();

                                                                        if (!$activePrompt) {
                                                                            Notification::make()
                                                                                ->title('Fehler')
                                                                                ->body('Kein aktiver Agent konfiguriert')
                                                                                ->danger()
                                                                                ->send();
                                                                            return;
                                                                        }

                                                                        $customFunction = [
                                                                            'name' => $name,
                                                                            'description' => $description,
                                                                            'parameters' => $parameters,
                                                                        ];

                                                                        $service = new \App\Services\Retell\RetellAgentManagementService();
                                                                        $result = $service->addCustomFunction($activePrompt, $customFunction, auth()->user());

                                                                        if ($result['success']) {
                                                                            Notification::make()
                                                                                ->title('✅ Custom Function hinzugefügt')
                                                                                ->body('Neue Version ' . ($activePrompt->version + 1) . ' ist jetzt aktiv')
                                                                                ->success()
                                                                                ->send();
                                                                        } else {
                                                                            Notification::make()
                                                                                ->title('❌ Fehler beim Hinzufügen')
                                                                                ->body(implode(', ', $result['errors'] ?? [$result['message'] ?? 'Unbekannter Fehler']))
                                                                                ->danger()
                                                                                ->send();
                                                                        }
                                                                    } catch (\Exception $e) {
                                                                        Notification::make()
                                                                            ->title('Fehler')
                                                                            ->body($e->getMessage())
                                                                            ->danger()
                                                                            ->send();
                                                                    }
                                                                })
                                                                ->icon('heroicon-m-plus'),
                                                        ]),
                                                    ]),
                                            ]),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            // Performance: Eager load relationships and count aggregates
            ->modifyQueryUsing(fn (Builder $query) =>
                $query->with(['company'])
                    ->withCount(['staff' => fn ($q) => $q->where('is_active', true)])
            )
            // Optimized to 9 essential columns with rich visual information
            ->columns([
                // Branch name with company context
                Tables\Columns\TextColumn::make('name')
                    ->label('Filiale')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn ($record) =>
                        $record->company?->name .
                        ($record->city ? ' • ' . $record->city : '')
                    )
                    ->icon('heroicon-m-building-storefront'),

                // Location information
                Tables\Columns\TextColumn::make('location')
                    ->label('Standort')
                    ->getStateUsing(fn ($record) =>
                        trim(
                            ($record->address ? $record->address . ', ' : '') .
                            ($record->postal_code ? $record->postal_code . ' ' : '') .
                            ($record->city ?: '')
                        )
                    )
                    ->searchable(['address', 'city', 'postal_code'])
                    
                    ->limit(40)
                    ->tooltip(fn ($record) => $record->address . ', ' . $record->postal_code . ' ' . $record->city),

                // Contact information
                Tables\Columns\TextColumn::make('contact')
                    ->label('Kontakt')
                    ->getStateUsing(fn ($record) =>
                        ($record->phone_number ?: '') .
                        ($record->phone_number && $record->notification_email ? ' • ' : '') .
                        ($record->notification_email ?: '')
                    )
                    ->searchable(['phone_number', 'notification_email'])
                    
                    ->icon('heroicon-m-phone'),

                // Operational status with visual indicators
                Tables\Columns\TextColumn::make('operational_status')
                    ->badge()
                    ->label('Betrieb')
                    ->getStateUsing(fn ($record) =>
                        $record->is_active ? 'operational' : 'closed' // active column doesn't exist in Sept 21 backup
                    )
                    ->colors([
                        'success' => 'operational',
                        'warning' => 'limited',
                        'danger' => 'closed',
                    ])
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'operational' => '🟢 Betriebsbereit',
                        'limited' => '🟡 Eingeschränkt',
                        'closed' => '🔴 Geschlossen',
                        default => $state,
                    }),

                // Service capabilities
                Tables\Columns\TextColumn::make('capabilities')
                    ->label('Services')
                    ->getStateUsing(fn ($record) =>
                        ($record->accepts_walkins ? '🚶 Walk-In' : '') .
                        ($record->accepts_walkins && $record->parking_available ? ' • ' : '') .
                        ($record->parking_available ? '🅿️ Parking' : '') .
                        ($record->service_radius_km > 0 ? ' • 📍 ' . $record->service_radius_km . 'km' : '')
                    )
                    ->badge()
                    ->color('info'),

                // Staff count with quick link
                Tables\Columns\TextColumn::make('staff_count')
                    ->label('Personal')
                    ->badge()
                    ->formatStateUsing(fn ($state) => '👥 ' . $state . ' Mitarbeiter')
                    ->color(fn ($state) => $state > 5 ? 'success' : ($state > 0 ? 'warning' : 'danger'))
                    ->sortable(),

                // Calendar mode
                Tables\Columns\TextColumn::make('calendar_mode')
                    ->label('Kalender')
                    ->badge()
                    ->colors([
                        'info' => 'individual',
                        'success' => 'shared',
                        'warning' => 'hybrid',
                    ])
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'individual' => '👤 Individuell',
                        'shared' => '👥 Gemeinsam',
                        'hybrid' => '🔄 Hybrid',
                        default => $state,
                    })
                    ->toggleable(),

                // Integration status
                Tables\Columns\IconColumn::make('integration_status')
                    ->label('Integration')
                    ->getStateUsing(fn ($record) =>
                        $record->calcom_event_type_id && $record->company?->retell_api_key
                    )
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->tooltip(fn ($record) =>
                        'Cal.com: ' . ($record->calcom_event_type_id ? '✅' : '❌') .
                        ' • Retell: ' . ($record->company?->retell_api_key ? '✅' : '❌')
                    )
                    ->toggleable(isToggledHiddenByDefault: true),

                // Last updated (hidden by default)
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Aktualisiert')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            // Smart business filters for branch management
            ->filters([
                SelectFilter::make('company_id')
                    ->label('Unternehmen')
                    ->relationship('company', 'name')
                    ->searchable()
                    ->preload(),

                Filter::make('operational')
                    ->label('Betriebsbereit')
                    ->query(fn (Builder $query): Builder =>
                        $query->where('is_active', true) // active column doesn't exist in Sept 21 backup
                    )
                    ->default(),

                Filter::make('accepts_walkins')
                    ->label('Walk-Ins möglich')
                    ->query(fn (Builder $query): Builder =>
                        $query->where('accepts_walkins', true)
                    ),

                Filter::make('has_parking')
                    ->label('Mit Parkplatz')
                    ->query(fn (Builder $query): Builder =>
                        $query->where('parking_available', true)
                    ),

                SelectFilter::make('calendar_mode')
                    ->label('Kalender Modus')
                    ->options([
                        'individual' => 'Individuell',
                        'shared' => 'Gemeinsam',
                        'hybrid' => 'Hybrid',
                    ]),

                Filter::make('has_staff')
                    ->label('Mit Personal')
                    ->query(fn (Builder $query): Builder =>
                        $query->has('staff')
                    ),

                /**
                 * DISABLED: city column doesn't exist in Sept 21 database backup
                 * TODO: Re-enable when database is fully restored
                 */
                // SelectFilter::make('city')
                //     ->label('Stadt')
                //     ->options(fn () => Branch::distinct()->pluck('city', 'city')->filter())
                //     ->searchable(),
            ])
            ->filtersLayout(Tables\Enums\FiltersLayout::AboveContent)
            ->filtersFormColumns(3)
            // Quick actions for branch management
            ->actions([
                Tables\Actions\ActionGroup::make([
                    // Staff management
                    Tables\Actions\Action::make('manageStaff')
                        ->label('Personal verwalten')
                        ->icon('heroicon-m-user-group')
                        ->color('info')
                        ->url(fn ($record) => route('filament.admin.resources.staff.index', [
                            'tableFilters' => ['branch_id' => ['value' => $record->id]]
                        ])),

                    // Quick appointment booking
                    Tables\Actions\Action::make('bookAppointment')
                        ->label('Termin buchen')
                        ->icon('heroicon-m-calendar')
                        ->color('success')
                        ->url(fn ($record) => route('filament.admin.resources.appointments.create', [
                            'branch_id' => $record->id
                        ])),

                    // Operating hours update
                    Tables\Actions\Action::make('updateHours')
                        ->label('Öffnungszeiten')
                        ->icon('heroicon-m-clock')
                        ->color('warning')
                        ->form([
                            Forms\Components\Textarea::make('business_hours')
                                ->label('Öffnungszeiten')
                                ->default(fn ($record) => $record->business_hours)
                                ->rows(6)
                                ->placeholder('Mo-Fr: 08:00-18:00\nSa: 09:00-16:00\nSo: Geschlossen'),
                            Forms\Components\Toggle::make('is_active')
                                ->label('Filiale aktiv')
                                ->default(fn ($record) => $record->is_active),
                            Forms\Components\Toggle::make('accepts_walkins')
                                ->label('Walk-Ins akzeptiert')
                                ->default(fn ($record) => $record->accepts_walkins),
                        ])
                        ->action(function ($record, array $data) {
                            $record->update($data);

                            Notification::make()
                                ->title('Öffnungszeiten aktualisiert')
                                ->body('Die Betriebsinformationen wurden erfolgreich geändert.')
                                ->success()
                                ->send();
                        }),

                    // Integration status check
                    Tables\Actions\Action::make('checkIntegration')
                        ->label('Integration prüfen')
                        ->icon('heroicon-m-cog')
                        ->color('gray')
                        ->action(function ($record) {
                            $integrations = [];
                            if ($record->calcom_event_type_id) $integrations[] = 'Cal.com';
                            if ($record->company?->retell_api_key) $integrations[] = 'Retell';

                            $message = empty($integrations)
                                ? 'Keine Integrationen konfiguriert.'
                                : 'Aktive Integrationen: ' . implode(', ', $integrations);

                            Notification::make()
                                ->title('Integration Status')
                                ->body($message)
                                ->info()
                                ->send();
                        }),

                    // Toggle operational status
                    Tables\Actions\Action::make('toggleStatus')
                        ->label('Status umschalten')
                        ->icon('heroicon-m-power')
                        ->color(fn ($record) => $record->is_active ? 'danger' : 'success')
                        ->action(function ($record) {
                            $newStatus = !$record->is_active;
                            $record->update(['is_active' => $newStatus]);

                            Notification::make()
                                ->title('Status geändert')
                                ->body('Filiale ist jetzt ' . ($newStatus ? 'aktiv' : 'inaktiv'))
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation(),

                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                ]),
            ])
            // Bulk operations for branch management
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('bulkStatusUpdate')
                        ->label('Status aktualisieren')
                        ->icon('heroicon-m-power')
                        ->color('warning')
                        ->form([
                            Forms\Components\Toggle::make('is_active')
                                ->label('Filiale aktiv'),
                            Forms\Components\Toggle::make('accepts_walkins')
                                ->label('Walk-Ins akzeptiert'),
                            Forms\Components\Toggle::make('parking_available')
                                ->label('Parkplätze verfügbar'),
                        ])
                        ->action(function ($records, array $data) {
                            $updates = array_filter($data, fn ($value) => $value !== null);
                            $records->each->update($updates);

                            Notification::make()
                                ->title('Massen-Update durchgeführt')
                                ->body(count($records) . ' Filialen wurden aktualisiert.')
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation(),

                    Tables\Actions\BulkAction::make('bulkHoursUpdate')
                        ->label('Öffnungszeiten setzen')
                        ->icon('heroicon-m-clock')
                        ->color('info')
                        ->form([
                            Forms\Components\Textarea::make('business_hours')
                                ->label('Standard Öffnungszeiten')
                                ->required()
                                ->rows(4)
                                ->placeholder('Mo-Fr: 08:00-18:00\nSa: 09:00-16:00\nSo: Geschlossen'),
                        ])
                        ->action(function ($records, array $data) {
                            $records->each->update($data);

                            Notification::make()
                                ->title('Öffnungszeiten aktualisiert')
                                ->body(count($records) . ' Filialen haben neue Öffnungszeiten.')
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation(),

                    Tables\Actions\ExportBulkAction::make()
                        ->label('Exportieren'),

                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            // Performance optimizations
            ->defaultPaginationPageOption(25)
            ->poll('60s')
            ->deferLoading()
            ->persistFiltersInSession()
            ->persistSortInSession()
            ->striped();
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ServicesRelationManager::class,
            RelationManagers\StaffRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBranches::route('/'),
            'create' => Pages\CreateBranch::route('/create'),
            'view' => Pages\ViewBranch::route('/{record}'),
            'edit' => Pages\EditBranch::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['company'])
            ->withCount(['staff' => fn ($q) => $q->where('is_active', true)]);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'city', 'address', 'phone_number', 'notification_email'];
    }

    /**
     * Generate policy configuration section for form
     */
    protected static function getPolicySection(string $policyType, string $label): Section
    {
        return Section::make($label)
            ->icon(match($policyType) {
                'cancellation' => 'heroicon-m-x-circle',
                'reschedule' => 'heroicon-m-arrow-path',
                'recurring' => 'heroicon-m-arrow-path-rounded-square',
                default => 'heroicon-m-shield-check',
            })
            ->description('Konfigurieren Sie die Richtlinien für ' . strtolower($label))
            ->schema([
                Forms\Components\Toggle::make("override_{$policyType}")
                    ->label('Überschreiben')
                    ->helperText('Aktivieren Sie diese Option, um eigene Richtlinien festzulegen')
                    ->reactive()
                    ->afterStateUpdated(function (Set $set, $state, $record) use ($policyType) {
                        if (!$state && $record) {
                            // Remove policy configuration when override is disabled
                            PolicyConfiguration::where('configurable_type', Branch::class)
                                ->where('configurable_id', $record->id)
                                ->where('policy_type', $policyType)
                                ->delete();
                        }
                    })
                    ->dehydrated(false)
                    ->default(function ($record) use ($policyType) {
                        if (!$record) return false;
                        return PolicyConfiguration::where('configurable_type', Branch::class)
                            ->where('configurable_id', $record->id)
                            ->where('policy_type', $policyType)
                            ->exists();
                    }),

                Forms\Components\KeyValue::make("policy_config_{$policyType}")
                    ->label('Konfiguration')
                    ->keyLabel('Schlüssel')
                    ->valueLabel('Wert')
                    ->addActionLabel('Eigenschaft hinzufügen')
                    ->reorderable()
                    ->visible(fn (Get $get) => $get("override_{$policyType}") === true)
                    ->default(function ($record) use ($policyType) {
                        if (!$record) return [];
                        $policy = PolicyConfiguration::where('configurable_type', Branch::class)
                            ->where('configurable_id', $record->id)
                            ->where('policy_type', $policyType)
                            ->first();
                        return $policy?->config ?? [];
                    })
                    ->dehydrated(false),

                Forms\Components\Placeholder::make("inherited_{$policyType}")
                    ->label('Geerbt von')
                    ->content(function ($record) use ($policyType) {
                        if (!$record) {
                            return new HtmlString('<span class="text-gray-500">Neue Filiale - noch keine Vererbung</span>');
                        }

                        $hasOverride = PolicyConfiguration::where('configurable_type', Branch::class)
                            ->where('configurable_id', $record->id)
                            ->where('policy_type', $policyType)
                            ->exists();

                        if ($hasOverride) {
                            return new HtmlString('<span class="text-primary-600 font-medium">Eigene Konfiguration aktiv</span>');
                        }

                        // Get inherited config from company
                        $inheritedConfig = static::getInheritedPolicyConfig($record, $policyType);
                        if ($inheritedConfig) {
                            $configDisplay = json_encode($inheritedConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                            return new HtmlString(
                                '<div class="text-sm">
                                    <p class="text-gray-700 font-medium mb-2">Geerbt von Unternehmen</p>
                                    <pre class="bg-gray-100 p-2 rounded text-xs overflow-auto max-h-32">' .
                                    htmlspecialchars($configDisplay) .
                                    '</pre>
                                </div>'
                            );
                        }

                        return new HtmlString('<span class="text-gray-500">Systemstandardwerte (keine Konfiguration beim Unternehmen)</span>');
                    })
                    ->visible(fn (Get $get) => $get("override_{$policyType}") !== true),

                Forms\Components\Placeholder::make("hierarchy_info_{$policyType}")
                    ->label('Hierarchie')
                    ->content(new HtmlString(
                        '<div class="text-sm text-gray-600">
                            <p class="font-medium mb-1">Vererbungsreihenfolge:</p>
                            <ol class="list-decimal list-inside space-y-1">
                                <li>Unternehmen</li>
                                <li><strong>Filiale</strong> (aktuelle Ebene)</li>
                                <li>Dienstleistung (erbt von Filiale)</li>
                            </ol>
                        </div>'
                    ))
                    ->columnSpanFull(),
            ])
            ->collapsible()
            ->collapsed();
    }

    /**
     * Get inherited policy configuration for a record
     */
    protected static function getInheritedPolicyConfig($record, string $policyType): ?array
    {
        if (!$record || !$record->company_id) return null;

        // Check if company has a policy configuration
        $companyPolicy = PolicyConfiguration::where('configurable_type', Company::class)
            ->where('configurable_id', $record->company_id)
            ->where('policy_type', $policyType)
            ->first();

        return $companyPolicy?->config;
    }

    /**
     * Save policy configuration for a record
     */
    protected static function savePolicyConfiguration($record, array $data): void
    {
        foreach (['cancellation', 'reschedule', 'recurring'] as $policyType) {
            $overrideKey = "override_{$policyType}";
            $configKey = "policy_config_{$policyType}";

            if (isset($data[$overrideKey]) && $data[$overrideKey]) {
                // Create or update policy configuration
                $config = $data[$configKey] ?? [];

                // Get parent policy ID if exists
                $parentPolicy = PolicyConfiguration::where('configurable_type', Company::class)
                    ->where('configurable_id', $record->company_id)
                    ->where('policy_type', $policyType)
                    ->first();

                PolicyConfiguration::updateOrCreate(
                    [
                        'configurable_type' => Branch::class,
                        'configurable_id' => $record->id,
                        'policy_type' => $policyType,
                    ],
                    [
                        'config' => $config,
                        'is_override' => $parentPolicy ? true : false,
                        'overrides_id' => $parentPolicy?->id,
                    ]
                );
            } else {
                // Remove policy configuration if override is disabled
                PolicyConfiguration::where('configurable_type', Branch::class)
                    ->where('configurable_id', $record->id)
                    ->where('policy_type', $policyType)
                    ->delete();
            }
        }
    }
}