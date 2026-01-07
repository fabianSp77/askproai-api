<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServiceOutputConfigurationResource\Pages;
use App\Filament\Resources\ServiceOutputConfigurationResource\RelationManagers;
use App\Models\ServiceOutputConfiguration;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class ServiceOutputConfigurationResource extends Resource
{
    protected static ?string $model = ServiceOutputConfiguration::class;
    protected static ?string $navigationIcon = 'heroicon-o-paper-airplane';
    protected static ?string $navigationGroup = 'Service Gateway';
    protected static ?string $navigationLabel = 'Output Konfigurationen';
    protected static ?string $modelLabel = 'Output Konfiguration';
    protected static ?string $pluralModelLabel = 'Output Konfigurationen';
    protected static ?int $navigationSort = 13;

    /**
     * Only show in navigation when Service Gateway is enabled.
     * @see config/gateway.php 'mode_enabled'
     */
    public static function shouldRegisterNavigation(): bool
    {
        return config('gateway.mode_enabled', false);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // ═══════════════════════════════════════════════════════════════
                // STATUS-PANEL: Zeigt den aktuellen Konfigurations-Status
                // ═══════════════════════════════════════════════════════════════
                Forms\Components\Placeholder::make('config_status')
                    ->hiddenLabel()
                    ->content(function (Forms\Get $get, $record) {
                        // Hole die aktuellen Werte aus dem Formular ODER aus dem Record
                        $outputType = $get('output_type') ?? $record?->output_type ?? 'email';
                        $isActive = $get('is_active') ?? $record?->is_active ?? true;

                        // E-Mail Status
                        $emailEnabled = in_array($outputType, ['email', 'hybrid']);
                        $recipients = $get('email_recipients') ?? $record?->email_recipients ?? [];
                        $recipientCount = is_array($recipients) ? count($recipients) : 0;
                        $includeSummary = $get('include_summary') ?? $record?->include_summary ?? true;
                        $includeTranscript = $get('include_transcript') ?? $record?->include_transcript ?? true;
                        $audioOption = $get('email_audio_option') ?? $record?->email_audio_option ?? 'none';

                        // Webhook Status
                        $webhookEnabled = in_array($outputType, ['webhook', 'hybrid']);
                        $webhookUrl = $get('webhook_url') ?? $record?->webhook_url ?? '';
                        $webhookActive = $get('webhook_enabled') ?? $record?->webhook_enabled ?? true;
                        $webhookTranscript = $get('webhook_include_transcript') ?? $record?->webhook_include_transcript ?? false;

                        // Timing Status
                        $waitForEnrichment = $get('wait_for_enrichment') ?? $record?->wait_for_enrichment ?? false;
                        $enrichmentTimeout = $get('enrichment_timeout_seconds') ?? $record?->enrichment_timeout_seconds ?? 180;

                        // Status-Berechnung E-Mail
                        $emailStatus = 'inactive';
                        $emailMessage = 'Nicht aktiviert';
                        $emailIcon = 'minus';
                        if ($emailEnabled) {
                            if ($recipientCount > 0) {
                                $emailStatus = 'success';
                                $features = [];
                                $features[] = $recipientCount . ' Empfanger';
                                if ($includeSummary) $features[] = 'Zusammenfassung';
                                if ($includeTranscript) $features[] = 'Transkript';
                                if ($audioOption !== 'none') $features[] = 'Audio';
                                $emailMessage = implode(' · ', $features);
                                $emailIcon = 'check';
                            } else {
                                $emailStatus = 'warning';
                                $emailMessage = 'Keine Empfanger konfiguriert';
                                $emailIcon = 'warning';
                            }
                        }

                        // Status-Berechnung Webhook
                        $webhookStatus = 'inactive';
                        $webhookMessage = 'Nicht aktiviert';
                        $webhookIcon = 'minus';
                        if ($webhookEnabled) {
                            if (!empty($webhookUrl)) {
                                if ($webhookActive) {
                                    $webhookStatus = 'success';
                                    $features = ['URL gesetzt', 'Aktiv'];
                                    if ($webhookTranscript) $features[] = 'Transkript';
                                    $webhookMessage = implode(' · ', $features);
                                    $webhookIcon = 'check';
                                } else {
                                    $webhookStatus = 'warning';
                                    $webhookMessage = 'URL gesetzt · Pausiert';
                                    $webhookIcon = 'warning';
                                }
                            } else {
                                $webhookStatus = 'danger';
                                $webhookMessage = 'URL fehlt!';
                                $webhookIcon = 'x';
                            }
                        }

                        // Timing Status
                        $timingMessage = $waitForEnrichment
                            ? "Wartet auf Enrichment ({$enrichmentTimeout}s Timeout)"
                            : 'Sofortige Zustellung';
                        $timingIcon = $waitForEnrichment ? 'clock' : 'bolt';

                        // Status-Farben
                        $colors = [
                            'success' => 'text-green-500',
                            'warning' => 'text-amber-500',
                            'danger' => 'text-red-500',
                            'inactive' => 'text-gray-400',
                        ];

                        // Verbesserte Dark Mode Farben mit höherem Kontrast
                        $bgColors = [
                            'success' => 'bg-green-50 dark:bg-green-950/60 border-green-300 dark:border-green-700',
                            'warning' => 'bg-amber-50 dark:bg-amber-950/60 border-amber-300 dark:border-amber-700',
                            'danger' => 'bg-red-50 dark:bg-red-950/60 border-red-300 dark:border-red-700',
                            'inactive' => 'bg-gray-100 dark:bg-gray-800 border-gray-300 dark:border-gray-600',
                        ];

                        // Icons mit Dark Mode Farben für bessere Sichtbarkeit
                        $icons = [
                            'check' => '<svg class="w-5 h-5 flex-shrink-0 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>',
                            'warning' => '<svg class="w-5 h-5 flex-shrink-0 text-amber-600 dark:text-amber-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>',
                            'x' => '<svg class="w-5 h-5 flex-shrink-0 text-red-600 dark:text-red-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path></svg>',
                            'minus' => '<svg class="w-5 h-5 flex-shrink-0 text-gray-500 dark:text-gray-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM7 9a1 1 0 000 2h6a1 1 0 100-2H7z" clip-rule="evenodd"></path></svg>',
                            'clock' => '<svg class="w-5 h-5 flex-shrink-0 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path></svg>',
                            'bolt' => '<svg class="w-5 h-5 flex-shrink-0 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd"></path></svg>',
                        ];

                        $deactivatedBanner = '';
                        if (!$isActive) {
                            $deactivatedBanner = '<div class="mb-3 px-3 py-2 rounded-lg bg-red-100 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 text-sm font-medium flex items-center gap-2">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" clip-rule="evenodd"></path></svg>
                                Konfiguration ist deaktiviert - keine Benachrichtigungen werden gesendet
                            </div>';
                        }

                        return new HtmlString('
                            <div class="rounded-xl border border-gray-200 dark:border-gray-600 overflow-hidden shadow-sm">
                                <div class="px-4 py-3 bg-gray-100 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-600">
                                    <h3 class="text-sm font-semibold text-gray-800 dark:text-white flex items-center gap-2">
                                        <svg class="w-5 h-5 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                                        </svg>
                                        Konfigurations-Status
                                    </h3>
                                </div>
                                <div class="p-4 bg-white dark:bg-gray-900">
                                    ' . $deactivatedBanner . '
                                    <div class="space-y-3">
                                        <div class="flex items-center gap-4 p-3 rounded-lg ' . $bgColors[$emailStatus] . ' border">
                                            ' . $icons[$emailIcon] . '
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center gap-2 flex-wrap">
                                                    <span class="text-sm font-semibold text-gray-800 dark:text-white">E-Mail</span>
                                                    ' . ($emailEnabled ? '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-blue-200 text-blue-900 dark:bg-blue-800 dark:text-blue-100">' . ($outputType === 'hybrid' ? 'Hybrid' : 'Aktiv') . '</span>' : '') . '
                                                </div>
                                                <p class="text-sm text-gray-700 dark:text-gray-200 mt-0.5">' . $emailMessage . '</p>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-4 p-3 rounded-lg ' . $bgColors[$webhookStatus] . ' border">
                                            ' . $icons[$webhookIcon] . '
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center gap-2 flex-wrap">
                                                    <span class="text-sm font-semibold text-gray-800 dark:text-white">Webhook</span>
                                                    ' . ($webhookEnabled ? '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-amber-200 text-amber-900 dark:bg-amber-800 dark:text-amber-100">' . ($outputType === 'hybrid' ? 'Hybrid' : 'Aktiv') . '</span>' : '') . '
                                                </div>
                                                <p class="text-sm text-gray-700 dark:text-gray-200 mt-0.5">' . $webhookMessage . '</p>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-4 p-3 rounded-lg bg-blue-50 dark:bg-blue-950/60 border border-blue-300 dark:border-blue-700">
                                            ' . $icons[$timingIcon] . '
                                            <div class="flex-1 min-w-0">
                                                <span class="text-sm font-semibold text-gray-800 dark:text-white">Timing</span>
                                                <p class="text-sm text-gray-700 dark:text-gray-200 mt-0.5">' . $timingMessage . '</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        ');
                    })
                    ->columnSpanFull(),

                Forms\Components\Tabs::make('Output Konfiguration')
                    ->tabs([
                        // ═══════════════════════════════════════════════════════════════
                        // TAB 1: GRUNDEINSTELLUNGEN (Settings)
                        // ═══════════════════════════════════════════════════════════════
                        Forms\Components\Tabs\Tab::make('Einstellungen')
                            ->icon('heroicon-o-cog-6-tooth')
                            ->schema([
                                Forms\Components\Section::make()
                                    ->schema([
                                        Forms\Components\TextInput::make('name')
                                            ->label('Name')
                                            ->required()
                                            ->maxLength(255)
                                            ->placeholder('z.B. IT-Support E-Mail, Helpdesk Webhook')
                                            ->helperText('Eindeutiger Name zur Identifikation')
                                            ->columnSpan(2),

                                        Forms\Components\Select::make('output_type')
                                            ->label('Ausgabekanal')
                                            ->options([
                                                ServiceOutputConfiguration::TYPE_EMAIL => 'E-Mail',
                                                ServiceOutputConfiguration::TYPE_WEBHOOK => 'Webhook',
                                                ServiceOutputConfiguration::TYPE_HYBRID => 'Beides (E-Mail + Webhook)',
                                            ])
                                            ->required()
                                            ->default(ServiceOutputConfiguration::TYPE_EMAIL)
                                            ->live()
                                            ->helperText(fn (Forms\Get $get) => match ($get('output_type')) {
                                                ServiceOutputConfiguration::TYPE_EMAIL => 'Nur E-Mail-Benachrichtigung',
                                                ServiceOutputConfiguration::TYPE_WEBHOOK => 'Nur Webhook an externes System',
                                                ServiceOutputConfiguration::TYPE_HYBRID => 'Parallele Zustellung via E-Mail UND Webhook',
                                                default => 'Wähle einen Ausgabekanal',
                                            }),

                                        Forms\Components\Toggle::make('is_active')
                                            ->label('Aktiv')
                                            ->default(true)
                                            ->live()
                                            ->helperText('Wenn deaktiviert: Diese Konfiguration wird nicht verwendet, keine E-Mails/Webhooks werden gesendet'),

                                        Forms\Components\Toggle::make('retry_on_failure')
                                            ->label('Automatische Wiederholung')
                                            ->default(true)
                                            ->helperText('Bei Zustellfehlern wird bis zu 3x automatisch wiederholt (empfohlen: An)'),
                                    ])
                                    ->columns(2),

                                // Quick Info Panel basierend auf Output Type
                                Forms\Components\Section::make('Kanalübersicht')
                                    ->description(fn (Forms\Get $get) => match ($get('output_type')) {
                                        ServiceOutputConfiguration::TYPE_EMAIL => 'E-Mail-Benachrichtigung: Konfiguriere Empfänger und Inhalte im E-Mail-Tab',
                                        ServiceOutputConfiguration::TYPE_WEBHOOK => 'Webhook-Integration: Konfiguriere URL und Payload im Webhook-Tab',
                                        ServiceOutputConfiguration::TYPE_HYBRID => 'Dual-Channel: Konfiguriere beide Kanäle in den jeweiligen Tabs',
                                        default => '',
                                    })
                                    ->schema([
                                        Forms\Components\Placeholder::make('channel_info')
                                            ->content(fn (Forms\Get $get) => new HtmlString(
                                                match ($get('output_type')) {
                                                    ServiceOutputConfiguration::TYPE_EMAIL => '
                                                        <div class="flex items-center gap-2 text-blue-600 dark:text-blue-400">
                                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                                            <span>E-Mail-Zustellung aktiv</span>
                                                        </div>
                                                    ',
                                                    ServiceOutputConfiguration::TYPE_WEBHOOK => '
                                                        <div class="flex items-center gap-2 text-amber-600 dark:text-amber-400">
                                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path></svg>
                                                            <span>Webhook-Zustellung aktiv</span>
                                                        </div>
                                                    ',
                                                    ServiceOutputConfiguration::TYPE_HYBRID => '
                                                        <div class="flex items-center gap-4">
                                                            <div class="flex items-center gap-2 text-blue-600 dark:text-blue-400">
                                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                                                <span>E-Mail</span>
                                                            </div>
                                                            <span class="text-gray-400">+</span>
                                                            <div class="flex items-center gap-2 text-amber-600 dark:text-amber-400">
                                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path></svg>
                                                                <span>Webhook</span>
                                                            </div>
                                                        </div>
                                                    ',
                                                    default => '<span class="text-gray-500">Wähle einen Ausgabekanal</span>',
                                                }
                                            ))
                                            ->hiddenLabel(),
                                    ])
                                    ->collapsible()
                                    ->collapsed(),
                            ]),

                        // ═══════════════════════════════════════════════════════════════
                        // TAB 2: E-MAIL KONFIGURATION
                        // ═══════════════════════════════════════════════════════════════
                        Forms\Components\Tabs\Tab::make('E-Mail')
                            ->icon('heroicon-o-envelope')
                            ->badge(fn (Forms\Get $get) => in_array($get('output_type'), [
                                ServiceOutputConfiguration::TYPE_EMAIL,
                                ServiceOutputConfiguration::TYPE_HYBRID,
                            ]) ? null : 'Inaktiv')
                            ->badgeColor('gray')
                            ->schema([
                                // Inactive Notice
                                Forms\Components\Placeholder::make('email_inactive_notice')
                                    ->content(new HtmlString('
                                        <div class="rounded-lg bg-gray-100 dark:bg-gray-800 p-4 text-gray-600 dark:text-gray-400">
                                            <div class="flex items-center gap-2">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                <span>E-Mail ist für diesen Ausgabekanal nicht aktiviert. Wechsle zu "E-Mail" oder "Beides" in den Einstellungen.</span>
                                            </div>
                                        </div>
                                    '))
                                    ->visible(fn (Forms\Get $get): bool => !in_array($get('output_type'), [
                                        ServiceOutputConfiguration::TYPE_EMAIL,
                                        ServiceOutputConfiguration::TYPE_HYBRID,
                                    ])),

                                // ═══════════════════════════════════════════════════════════════
                                // INFO-BANNER: E-Mail = Interne Teams (nicht Kunden!)
                                // ═══════════════════════════════════════════════════════════════
                                Forms\Components\Placeholder::make('email_purpose_info')
                                    ->content(new HtmlString('
                                        <div class="rounded-lg bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 p-4">
                                            <div class="flex items-start gap-3">
                                                <div class="flex-shrink-0">
                                                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                    </svg>
                                                </div>
                                                <div class="flex-1">
                                                    <h4 class="text-sm font-semibold text-blue-800 dark:text-blue-200 mb-1">📧 E-Mail = Interne Teams</h4>
                                                    <p class="text-sm text-blue-700 dark:text-blue-300 mb-2">
                                                        E-Mails werden an <strong>IHRE eigenen Mitarbeiter</strong> gesendet (nicht an Kunden!):
                                                    </p>
                                                    <ul class="text-sm text-blue-700 dark:text-blue-300 list-disc list-inside space-y-1">
                                                        <li>IT-Support Team (z.B. support@firma.de)</li>
                                                        <li>Helpdesk Mitarbeiter</li>
                                                        <li>Admins & Techniker</li>
                                                    </ul>
                                                    <p class="text-xs text-blue-600 dark:text-blue-400 mt-2">
                                                        💡 <strong>Tipp:</strong> Für externe Systeme (Jira, ServiceNow, Zendesk) nutzen Sie den <strong>Webhook-Tab</strong>.
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    '))
                                    ->columnSpanFull()
                                    ->visible(fn (Forms\Get $get): bool => in_array($get('output_type'), [
                                        ServiceOutputConfiguration::TYPE_EMAIL,
                                        ServiceOutputConfiguration::TYPE_HYBRID,
                                    ])),

                                // Email Template Type Selection (replaces magic string detection)
                                Forms\Components\Section::make('E-Mail Template')
                                    ->description('Art und Format der E-Mail-Benachrichtigung')
                                    ->icon('heroicon-o-document-text')
                                    ->schema([
                                        Forms\Components\Select::make('email_template_type')
                                            ->label('Template Typ')
                                            ->options([
                                                'standard' => '📋 Standard - Für Team-Benachrichtigungen',
                                                'technical' => '🔬 Technisch - Für Archivierung & Monitoring',
                                                'admin' => '🛠️ IT-Support - Für Helpdesk-Teams',
                                                'custom' => '⚙️ Custom - Eigenes Template',
                                            ])
                                            ->default('standard')
                                            ->required()
                                            ->live()
                                            ->helperText(fn (Forms\Get $get) => match ($get('email_template_type')) {
                                                'standard' => '✅ Einfache, lesbare Benachrichtigung. Ideal für: Helpdesk, Support-Team, Admins',
                                                'technical' => '📦 Vollständiges Transkript + JSON-Anhang. Ideal für: Automatische Archivierung, Daten-Backup',
                                                'admin' => '🔧 Strukturierte Ticket-Info + JSON + Admin-Link. Ideal für: IT-Systemhaus, technisches Support-Team',
                                                'custom' => '✏️ Definiere dein eigenes Template-Format im Abschnitt unten',
                                                default => 'Wähle ein Template-Format für deine Benachrichtigungen',
                                            }),

                                        Forms\Components\Placeholder::make('template_preview')
                                            ->label('Template Info')
                                            ->content(fn (Forms\Get $get) => new HtmlString(match ($get('email_template_type')) {
                                                'technical' => '
                                                    <div class="rounded-lg bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 p-3 text-sm">
                                                        <div class="font-medium text-blue-800 dark:text-blue-200">Technisches Template</div>
                                                        <ul class="mt-1 text-blue-700 dark:text-blue-300 list-disc list-inside">
                                                            <li>Vollständiges Transkript im E-Mail-Body</li>
                                                            <li>KI-Zusammenfassung enthalten</li>
                                                            <li>JSON-Datei als Anhang</li>
                                                            <li>Geeignet für: Visionary Data, Backup-Services</li>
                                                        </ul>
                                                    </div>',
                                                'admin' => '
                                                    <div class="rounded-lg bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 p-3 text-sm">
                                                        <div class="font-medium text-amber-800 dark:text-amber-200">Admin/IT-Support Template</div>
                                                        <ul class="mt-1 text-amber-700 dark:text-amber-300 list-disc list-inside">
                                                            <li>Strukturierte Ticket-Informationen</li>
                                                            <li>JSON-Anhang mit allen Daten</li>
                                                            <li>Admin-Link zum Ticket</li>
                                                            <li>Geeignet für: IT-Systemhaus, Helpdesk</li>
                                                        </ul>
                                                    </div>',
                                                'custom' => '
                                                    <div class="rounded-lg bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 p-3 text-sm">
                                                        <div class="font-medium text-purple-800 dark:text-purple-200">Custom Template</div>
                                                        <p class="mt-1 text-purple-700 dark:text-purple-300">Definiere dein eigenes Template im Abschnitt "Custom Template" unten.</p>
                                                    </div>',
                                                default => '',
                                            }))
                                            ->visible(fn (Forms\Get $get) => in_array($get('email_template_type'), ['technical', 'admin', 'custom'])),

                                        // Preview Button - opens modal with rendered email
                                        Forms\Components\Actions::make([
                                            Forms\Components\Actions\Action::make('preview_email_template')
                                                ->label('Vorschau anzeigen')
                                                ->icon('heroicon-o-eye')
                                                ->color('info')
                                                ->size('sm')
                                                ->modalHeading('E-Mail Vorschau')
                                                ->modalDescription('So sieht die E-Mail mit den aktuellen Einstellungen aus.')
                                                ->modalWidth('6xl')
                                                ->modalContent(fn (Forms\Get $get) => view('filament.forms.components.email-preview-modal', [
                                                    'templateType' => $get('email_template_type') ?? 'standard',
                                                    'includeTranscript' => (bool) $get('include_transcript'),
                                                    'includeSummary' => (bool) $get('include_summary'),
                                                    'audioOption' => $get('email_audio_option') ?? 'none',
                                                    'showAdminLink' => (bool) $get('email_show_admin_link'),
                                                    'customSubject' => $get('email_subject_template') ?? '',
                                                    'customBody' => $get('email_body_template') ?? '',
                                                ]))
                                                ->modalSubmitAction(false)
                                                ->modalCancelActionLabel('Schließen'),
                                        ])
                                        ->columnSpanFull(),
                                    ])
                                    ->collapsible()
                                    ->visible(fn (Forms\Get $get): bool => in_array($get('output_type'), [
                                        ServiceOutputConfiguration::TYPE_EMAIL,
                                        ServiceOutputConfiguration::TYPE_HYBRID,
                                    ])),

                                // Email Configuration Section
                                Forms\Components\Section::make('Empfänger')
                                    ->description('Wer soll benachrichtigt werden?')
                                    ->icon('heroicon-o-users')
                                    ->schema([
                                        // Hilfe-Text für Empfänger-Verwaltung
                                        Forms\Components\Placeholder::make('recipient_help')
                                            ->content(new HtmlString('
                                                <div class="rounded-lg bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-3 mb-2">
                                                    <p class="text-sm text-gray-700 dark:text-gray-300 mb-2">
                                                        <strong>💡 So funktionierts:</strong>
                                                    </p>
                                                    <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1 list-disc list-inside">
                                                        <li><strong>Mehrere Empfänger:</strong> Alle aktiven E-Mails erhalten die Benachrichtigung gleichzeitig</li>
                                                        <li><strong>Pausieren (Aktiv-Toggle aus):</strong> Temporär deaktivieren ohne zu löschen - <em>ideal für Tests!</em></li>
                                                        <li><strong>Test-Tipp:</strong> Pausiere alle außer einer Test-E-Mail, um nur dort zu empfangen</li>
                                                    </ul>
                                                </div>
                                            '))
                                            ->hiddenLabel()
                                            ->columnSpanFull(),

                                        // Repeater: Each email with active/paused toggle
                                        Forms\Components\Repeater::make('recipient_entries')
                                            ->label('E-Mail Empfänger')
                                            ->schema([
                                                Forms\Components\TextInput::make('email')
                                                    ->label('E-Mail')
                                                    ->email()
                                                    ->required()
                                                    ->placeholder('empfaenger@example.de')
                                                    ->columnSpan(2),
                                                Forms\Components\Toggle::make('is_active')
                                                    ->label('Aktiv')
                                                    ->default(true)
                                                    ->inline(false)
                                                    ->onColor('success')
                                                    ->offColor('warning')
                                                    ->helperText('Aus = Pausiert')
                                                    ->columnSpan(1),
                                            ])
                                            ->columns(3)
                                            ->itemLabel(fn (array $state): ?string =>
                                                ($state['email'] ?? 'Neue E-Mail') .
                                                (($state['is_active'] ?? true) ? '' : ' ⏸️ pausiert')
                                            )
                                            ->collapsible()
                                            ->cloneable()
                                            ->reorderable()
                                            ->addActionLabel('+ E-Mail hinzufügen')
                                            ->defaultItems(0)
                                            ->live()
                                            ->columnSpanFull(),

                                        // Status Summary
                                        Forms\Components\Placeholder::make('recipient_status_summary')
                                            ->hiddenLabel()
                                            ->content(function (Forms\Get $get) {
                                                $entries = $get('recipient_entries') ?? [];
                                                $total = count($entries);
                                                $active = count(array_filter($entries, fn($e) => $e['is_active'] ?? true));
                                                $paused = $total - $active;

                                                if ($total === 0) {
                                                    return new HtmlString('
                                                        <div class="text-sm text-gray-500 dark:text-gray-400 italic">
                                                            Keine Empfänger konfiguriert - fügen Sie mindestens eine E-Mail hinzu
                                                        </div>
                                                    ');
                                                }

                                                if ($active === 0) {
                                                    return new HtmlString('
                                                        <div class="rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 p-3">
                                                            <div class="flex items-center gap-2 text-red-700 dark:text-red-300">
                                                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                                                                <span class="font-medium">⚠️ Keine aktiven Empfänger!</span>
                                                            </div>
                                                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">Alle ' . $total . ' Empfänger sind pausiert - es werden keine E-Mails zugestellt.</p>
                                                        </div>
                                                    ');
                                                }

                                                if ($paused > 0) {
                                                    return new HtmlString('
                                                        <div class="rounded-lg bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 p-3">
                                                            <div class="flex items-center gap-2 text-amber-700 dark:text-amber-300">
                                                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path></svg>
                                                                <span class="font-medium">Test-Modus: ' . $active . ' von ' . $total . ' aktiv</span>
                                                            </div>
                                                            <p class="mt-1 text-sm text-amber-600 dark:text-amber-400">' . $paused . ' Empfänger pausiert (erhalten keine E-Mails)</p>
                                                        </div>
                                                    ');
                                                }

                                                return new HtmlString('
                                                    <div class="rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 p-3">
                                                        <div class="flex items-center gap-2 text-green-700 dark:text-green-300">
                                                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                                                            <span class="font-medium">✓ Alle ' . $total . ' Empfänger aktiv</span>
                                                        </div>
                                                    </div>
                                                ');
                                            })
                                            ->columnSpanFull(),

                                        Forms\Components\TagsInput::make('fallback_emails')
                                            ->label('Backup-Empfänger (Optional)')
                                            ->placeholder('Optional: Backup E-Mail-Adressen')
                                            ->helperText('Nur wenn die primären E-Mails nicht zugestellt werden können')
                                            ->splitKeys(['Tab', ',', ' '])
                                            ->columnSpanFull(),
                                    ])
                                    ->collapsible()
                                    ->visible(fn (Forms\Get $get): bool => in_array($get('output_type'), [
                                        ServiceOutputConfiguration::TYPE_EMAIL,
                                        ServiceOutputConfiguration::TYPE_HYBRID,
                                    ])),

                                // Email Content Section
                                Forms\Components\Section::make('E-Mail Inhalte')
                                    ->description('Was soll in der E-Mail enthalten sein?')
                                    ->icon('heroicon-o-document-text')
                                    ->schema([
                                        Forms\Components\Grid::make(3)
                                            ->schema([
                                                Forms\Components\Toggle::make('include_summary')
                                                    ->label('KI-Zusammenfassung')
                                                    ->default(true)
                                                    ->live()
                                                    ->helperText('Kurze automatische Zusammenfassung des Gesprachs'),

                                                Forms\Components\Toggle::make('include_transcript')
                                                    ->label('Gesprachsprotokoll')
                                                    ->default(true)
                                                    ->live()
                                                    ->helperText('Wort-fur-Wort Protokoll "Agent: ... Anrufer: ..."'),

                                                Forms\Components\Toggle::make('email_show_admin_link')
                                                    ->label('Admin-Link')
                                                    ->default(false)
                                                    ->helperText('Fugt einen "Ticket bearbeiten" Button in die E-Mail ein'),
                                            ]),

                                        Forms\Components\Select::make('email_audio_option')
                                            ->label('Audio-Aufnahme')
                                            ->options([
                                                'none' => '🚫 Nicht einbinden',
                                                'link' => '🔗 Als Download-Link (empfohlen)',
                                                'attachment' => '📎 Als Anhang (max. 10 MB)',
                                            ])
                                            ->default('none')
                                            ->live()
                                            ->helperText(fn (Forms\Get $get) => match ($get('email_audio_option')) {
                                                'link' => '✅ Download-Button in der E-Mail. Link ist 24 Stunden gültig. Sicher & platzsparend!',
                                                'attachment' => '⚠️ MP3 direkt angehängt. Bei Aufnahmen >10 MB wird automatisch auf Link umgestellt.',
                                                default => 'Audio wird nicht in der E-Mail eingebunden. Aktiviere "Auf Enrichment warten" im Erweitert-Tab für Audio-Daten.',
                                            })
                                            ->columnSpan(2),
                                    ])
                                    ->columns(2)
                                    ->collapsible()
                                    ->visible(fn (Forms\Get $get): bool => in_array($get('output_type'), [
                                        ServiceOutputConfiguration::TYPE_EMAIL,
                                        ServiceOutputConfiguration::TYPE_HYBRID,
                                    ])),

                                // Custom Template Section
                                Forms\Components\Section::make('Custom Template')
                                    ->description('Optionales eigenes E-Mail-Template')
                                    ->icon('heroicon-o-code-bracket')
                                    ->schema([
                                        Forms\Components\ViewField::make('email_template_variables')
                                            ->view('filament.forms.components.template-variables')
                                            ->viewData(['label' => 'Verfügbare Variablen (klicken zum Kopieren)'])
                                            ->columnSpanFull(),

                                        Forms\Components\Textarea::make('email_body_template')
                                            ->label('Custom Template')
                                            ->rows(8)
                                            ->placeholder('Leer lassen für Standard-Template. Oder eigenes HTML/Text mit {{variablen}} eingeben.')
                                            ->helperText('Wenn leer, wird das automatische Standard-Template verwendet')
                                            ->columnSpanFull(),
                                    ])
                                    ->collapsible()
                                    ->collapsed()
                                    ->visible(fn (Forms\Get $get): bool => in_array($get('output_type'), [
                                        ServiceOutputConfiguration::TYPE_EMAIL,
                                        ServiceOutputConfiguration::TYPE_HYBRID,
                                    ])),
                            ]),

                        // ═══════════════════════════════════════════════════════════════
                        // TAB 3: WEBHOOK KONFIGURATION
                        // ═══════════════════════════════════════════════════════════════
                        Forms\Components\Tabs\Tab::make('Webhook')
                            ->icon('heroicon-o-link')
                            ->badge(fn (Forms\Get $get) => in_array($get('output_type'), [
                                ServiceOutputConfiguration::TYPE_WEBHOOK,
                                ServiceOutputConfiguration::TYPE_HYBRID,
                            ]) ? null : 'Inaktiv')
                            ->badgeColor('gray')
                            ->schema([
                                // Inactive Notice
                                Forms\Components\Placeholder::make('webhook_inactive_notice')
                                    ->content(new HtmlString('
                                        <div class="rounded-lg bg-gray-100 dark:bg-gray-800 p-4 text-gray-600 dark:text-gray-400">
                                            <div class="flex items-center gap-2">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                <span>Webhook ist für diesen Ausgabekanal nicht aktiviert. Wechsle zu "Webhook" oder "Beides" in den Einstellungen.</span>
                                            </div>
                                        </div>
                                    '))
                                    ->visible(fn (Forms\Get $get): bool => !in_array($get('output_type'), [
                                        ServiceOutputConfiguration::TYPE_WEBHOOK,
                                        ServiceOutputConfiguration::TYPE_HYBRID,
                                    ])),

                                // Webhook Purpose Info Banner
                                Forms\Components\Placeholder::make('webhook_purpose_info')
                                    ->content(new HtmlString('
                                        <div class="rounded-lg bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 p-4">
                                            <div class="flex items-start gap-3">
                                                <div class="flex-shrink-0">
                                                    <svg class="w-6 h-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                                                    </svg>
                                                </div>
                                                <div class="flex-1">
                                                    <h4 class="text-sm font-semibold text-amber-800 dark:text-amber-200 mb-1">🔗 Webhook = Externe Systeme</h4>
                                                    <p class="text-sm text-amber-700 dark:text-amber-300 mb-2">
                                                        Webhooks senden Ticket-Daten automatisch an <strong>externe Tools & Ticketsysteme</strong>:
                                                    </p>
                                                    <div class="flex flex-wrap gap-2 mt-2">
                                                        <span class="px-2 py-0.5 rounded text-xs font-medium bg-amber-100 dark:bg-amber-900 text-amber-800 dark:text-amber-200">Jira</span>
                                                        <span class="px-2 py-0.5 rounded text-xs font-medium bg-amber-100 dark:bg-amber-900 text-amber-800 dark:text-amber-200">ServiceNow</span>
                                                        <span class="px-2 py-0.5 rounded text-xs font-medium bg-amber-100 dark:bg-amber-900 text-amber-800 dark:text-amber-200">OTRS</span>
                                                        <span class="px-2 py-0.5 rounded text-xs font-medium bg-amber-100 dark:bg-amber-900 text-amber-800 dark:text-amber-200">Zendesk</span>
                                                        <span class="px-2 py-0.5 rounded text-xs font-medium bg-amber-100 dark:bg-amber-900 text-amber-800 dark:text-amber-200">Slack</span>
                                                        <span class="px-2 py-0.5 rounded text-xs font-medium bg-amber-100 dark:bg-amber-900 text-amber-800 dark:text-amber-200">MS Teams</span>
                                                        <span class="px-2 py-0.5 rounded text-xs font-medium bg-amber-100 dark:bg-amber-900 text-amber-800 dark:text-amber-200">n8n</span>
                                                        <span class="px-2 py-0.5 rounded text-xs font-medium bg-amber-100 dark:bg-amber-900 text-amber-800 dark:text-amber-200">Zapier</span>
                                                    </div>
                                                    <p class="text-xs text-amber-600 dark:text-amber-400 mt-2">
                                                        💡 <strong>Tipp:</strong> Nutze ein <strong>Preset</strong> für schnelle Einrichtung oder konfiguriere manuell.
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    '))
                                    ->columnSpanFull()
                                    ->visible(fn (Forms\Get $get): bool => in_array($get('output_type'), [
                                        ServiceOutputConfiguration::TYPE_WEBHOOK,
                                        ServiceOutputConfiguration::TYPE_HYBRID,
                                    ])),

                                // ═══════════════════════════════════════════════════════════════
                                // WEBHOOK PRESET SELECTOR - Vorkonfigurierte Templates
                                // ═══════════════════════════════════════════════════════════════
                                Forms\Components\Section::make('Webhook Preset')
                                    ->description('Nutze vorkonfigurierte Templates für gängige Systeme')
                                    ->icon('heroicon-o-document-duplicate')
                                    ->schema([
                                        Forms\Components\Select::make('webhook_preset_id')
                                            ->label('Preset Template')
                                            ->relationship(
                                                'webhookPreset',
                                                'name',
                                                fn ($query, $record) => $query
                                                    ->where(function ($q) use ($record) {
                                                        $companyId = $record?->company_id ?? auth()->user()?->company_id;
                                                        if ($companyId) {
                                                            // System presets + Company presets
                                                            $q->whereNull('company_id')
                                                              ->orWhere('company_id', $companyId);
                                                        } else {
                                                            // Only system presets
                                                            $q->whereNull('company_id');
                                                        }
                                                    })
                                                    ->where('is_active', true)
                                                    ->orderBy('is_system', 'desc') // System first
                                                    ->orderBy('target_system')
                                                    ->orderBy('name')
                                            )
                                            ->getOptionLabelFromRecordUsing(fn ($record) =>
                                                ($record->is_system ? '🌐 ' : '🏢 ') .
                                                $record->target_system_label . ' - ' . $record->name
                                            )
                                            ->placeholder('Kein Preset (Custom Template)')
                                            ->searchable()
                                            ->preload()
                                            ->live()
                                            ->helperText(fn (Forms\Get $get) =>
                                                $get('webhook_preset_id')
                                                    ? 'Das Preset-Template wird als Basis verwendet. Du kannst es im Payload-Tab anpassen.'
                                                    : 'Wähle ein Preset für schnelle Einrichtung oder erstelle ein eigenes Template.'
                                            ),

                                        // Preset Info Panel
                                        Forms\Components\Placeholder::make('preset_info')
                                            ->label('Preset Details')
                                            ->content(function (Forms\Get $get, $record) {
                                                $presetId = $get('webhook_preset_id') ?? $record?->webhook_preset_id;
                                                if (!$presetId) {
                                                    return new HtmlString('
                                                        <div class="text-sm text-gray-500 dark:text-gray-400">
                                                            Kein Preset ausgewählt. Konfiguriere URL und Payload manuell.
                                                        </div>
                                                    ');
                                                }

                                                $preset = \App\Models\WebhookPreset::find($presetId);
                                                if (!$preset) {
                                                    return new HtmlString('<div class="text-sm text-gray-500">Preset nicht gefunden</div>');
                                                }

                                                $variables = $preset->extractVariables();
                                                $required = $preset->getRequiredVariables();
                                                $authLabel = match ($preset->auth_type) {
                                                    'hmac' => 'HMAC-SHA256 Signatur',
                                                    'bearer' => 'Bearer Token',
                                                    'basic' => 'Basic Auth',
                                                    'api_key' => 'API Key',
                                                    default => 'Keine',
                                                };

                                                $requiredBadges = '';
                                                foreach ($required as $var) {
                                                    $requiredBadges .= '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200 mr-1">' . e($var) . '*</span>';
                                                }

                                                $optionalVars = array_diff($variables, $required);
                                                $optionalBadges = '';
                                                foreach (array_slice($optionalVars, 0, 5) as $var) {
                                                    $optionalBadges .= '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300 mr-1">' . e($var) . '</span>';
                                                }
                                                if (count($optionalVars) > 5) {
                                                    $optionalBadges .= '<span class="text-xs text-gray-500">+' . (count($optionalVars) - 5) . ' weitere</span>';
                                                }

                                                return new HtmlString('
                                                    <div class="rounded-lg bg-blue-50 dark:bg-blue-950/50 border border-blue-200 dark:border-blue-800 p-4">
                                                        <div class="flex items-center gap-3 mb-3">
                                                            <div class="flex-shrink-0">
                                                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-primary-100 text-primary-800 dark:bg-primary-900 dark:text-primary-200">
                                                                    ' . e($preset->target_system_label) . '
                                                                </span>
                                                            </div>
                                                            <div class="flex-1">
                                                                <p class="text-sm font-medium text-gray-900 dark:text-white">' . e($preset->name) . '</p>
                                                                ' . ($preset->description ? '<p class="text-xs text-gray-600 dark:text-gray-400">' . e($preset->description) . '</p>' : '') . '
                                                            </div>
                                                        </div>
                                                        <div class="space-y-2 text-sm">
                                                            <div class="flex items-center gap-2">
                                                                <span class="text-gray-600 dark:text-gray-400 w-24">Auth:</span>
                                                                <span class="font-medium text-gray-900 dark:text-white">' . e($authLabel) . '</span>
                                                            </div>
                                                            ' . ($requiredBadges ? '
                                                            <div class="flex items-start gap-2">
                                                                <span class="text-gray-600 dark:text-gray-400 w-24">Pflicht:</span>
                                                                <div class="flex-1 flex flex-wrap gap-1">' . $requiredBadges . '</div>
                                                            </div>' : '') . '
                                                            ' . ($optionalBadges ? '
                                                            <div class="flex items-start gap-2">
                                                                <span class="text-gray-600 dark:text-gray-400 w-24">Optional:</span>
                                                                <div class="flex-1 flex flex-wrap gap-1">' . $optionalBadges . '</div>
                                                            </div>' : '') . '
                                                            ' . ($preset->documentation_url ? '
                                                            <div class="mt-2 pt-2 border-t border-blue-200 dark:border-blue-700">
                                                                <a href="' . e($preset->documentation_url) . '" target="_blank" class="inline-flex items-center gap-1 text-primary-600 hover:text-primary-700 dark:text-primary-400">
                                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                                                                    Dokumentation
                                                                </a>
                                                            </div>' : '') . '
                                                        </div>
                                                    </div>
                                                ');
                                            })
                                            ->visible(fn (Forms\Get $get, $record) =>
                                                ($get('webhook_preset_id') ?? $record?->webhook_preset_id) !== null
                                            ),
                                    ])
                                    ->collapsible()
                                    ->visible(fn (Forms\Get $get): bool => in_array($get('output_type'), [
                                        ServiceOutputConfiguration::TYPE_WEBHOOK,
                                        ServiceOutputConfiguration::TYPE_HYBRID,
                                    ])),

                                // Webhook Status Toggle
                                Forms\Components\Section::make('Webhook Status')
                                    ->schema([
                                        Forms\Components\Toggle::make('webhook_enabled')
                                            ->label('Webhook aktiviert')
                                            ->default(true)
                                            ->helperText('Webhook an/aus schalten - URL und andere Einstellungen bleiben erhalten')
                                            ->live(),

                                        Forms\Components\Toggle::make('webhook_include_transcript')
                                            ->label('Transcript im Payload')
                                            ->default(false)
                                            ->live()
                                            ->helperText('Das vollstandige Gesprachsprotokoll wird im JSON-Payload mitgesendet'),
                                    ])
                                    ->columns(2)
                                    ->visible(fn (Forms\Get $get): bool => in_array($get('output_type'), [
                                        ServiceOutputConfiguration::TYPE_WEBHOOK,
                                        ServiceOutputConfiguration::TYPE_HYBRID,
                                    ])),

                                // Webhook Endpoint Section
                                Forms\Components\Section::make('Endpoint')
                                    ->description('Ziel-URL und Authentifizierung')
                                    ->icon('heroicon-o-globe-alt')
                                    ->schema([
                                        Forms\Components\TextInput::make('webhook_url')
                                            ->label('Webhook URL')
                                            ->url()
                                            ->required(fn (Forms\Get $get): bool => in_array($get('output_type'), [
                                                ServiceOutputConfiguration::TYPE_WEBHOOK,
                                                ServiceOutputConfiguration::TYPE_HYBRID,
                                            ]))
                                            ->maxLength(2048)
                                            ->placeholder('https://api.example.com/webhooks/tickets')
                                            ->helperText('Hierhin werden die Ticket-Daten gesendet (HTTP POST mit JSON-Body)')
                                            ->prefixIcon('heroicon-o-link')
                                            ->live(onBlur: true)
                                            ->columnSpanFull(),

                                        Forms\Components\TextInput::make('webhook_secret')
                                            ->label('HMAC Secret (Optional)')
                                            ->password()
                                            ->revealable()
                                            ->maxLength(255)
                                            ->helperText('Sicherheitsfeature: Wenn gesetzt, wird jeder Request signiert (Header: X-AskPro-Signature)')
                                            ->prefixIcon('heroicon-o-key')
                                            ->columnSpanFull(),

                                        Forms\Components\KeyValue::make('webhook_headers')
                                            ->label('Custom HTTP Headers')
                                            ->keyLabel('Header')
                                            ->valueLabel('Wert')
                                            ->addActionLabel('Header hinzufügen')
                                            ->helperText('z.B. Authorization: Bearer xxx, X-API-Key: xxx')
                                            ->columnSpanFull(),
                                    ])
                                    ->collapsible()
                                    ->visible(fn (Forms\Get $get): bool => in_array($get('output_type'), [
                                        ServiceOutputConfiguration::TYPE_WEBHOOK,
                                        ServiceOutputConfiguration::TYPE_HYBRID,
                                    ])),

                                // Webhook Payload Section
                                Forms\Components\Section::make('Payload Template')
                                    ->description('JSON-Struktur für den Webhook-Body')
                                    ->icon('heroicon-o-code-bracket-square')
                                    ->schema([
                                        Forms\Components\ViewField::make('webhook_template_help')
                                            ->view('filament.forms.components.webhook-template-help')
                                            ->columnSpanFull(),

                                        Forms\Components\Textarea::make('webhook_payload_template')
                                            ->label('Payload Template (JSON)')
                                            ->rows(12)
                                            ->placeholder('Leer lassen für Standard-Payload, oder eigenes JSON-Template eingeben')
                                            ->helperText('Leer = automatischer Standard-Payload mit allen Ticket-Daten')
                                            ->columnSpanFull()
                                            ->rules([
                                                fn () => function (string $attribute, $value, $fail) {
                                                    if (empty($value)) {
                                                        return; // Empty is allowed (uses default)
                                                    }
                                                    // Remove template variables for JSON validation
                                                    $testJson = preg_replace('/\{\{[^}]+\}\}/', '"placeholder"', $value);
                                                    json_decode($testJson);
                                                    if (json_last_error() !== JSON_ERROR_NONE) {
                                                        $fail('Das Payload-Template ist kein gültiges JSON: ' . json_last_error_msg());
                                                    }
                                                },
                                            ]),
                                    ])
                                    ->collapsible()
                                    ->visible(fn (Forms\Get $get): bool => in_array($get('output_type'), [
                                        ServiceOutputConfiguration::TYPE_WEBHOOK,
                                        ServiceOutputConfiguration::TYPE_HYBRID,
                                    ])),
                            ]),

                        // ═══════════════════════════════════════════════════════════════
                        // TAB 4: ERWEITERTE EINSTELLUNGEN (Advanced)
                        // ═══════════════════════════════════════════════════════════════
                        Forms\Components\Tabs\Tab::make('Erweitert')
                            ->icon('heroicon-o-adjustments-horizontal')
                            ->schema([
                                // Delivery Gate Section
                                Forms\Components\Section::make('Delivery Gate')
                                    ->description('Steuerung der 2-Phase Delivery: Warten auf Enrichment (Transkript, Audio) vor Zustellung')
                                    ->icon('heroicon-o-clock')
                                    ->schema([
                                        // Decision Helper - IMMER sichtbar
                                        Forms\Components\Placeholder::make('delivery_gate_decision')
                                            ->content(new HtmlString('
                                                <div class="grid md:grid-cols-2 gap-3 mb-2">
                                                    <div class="rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 p-3">
                                                        <div class="font-medium text-green-700 dark:text-green-300 mb-2 flex items-center gap-2">
                                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                                                            Sofortige Zustellung
                                                        </div>
                                                        <ul class="text-sm text-green-700 dark:text-green-300 space-y-1">
                                                            <li>✅ Schnell: 5-10 Sekunden</li>
                                                            <li>✅ Einfach & zuverlässig</li>
                                                            <li>⚠️ Ohne Transkript & Audio</li>
                                                        </ul>
                                                        <p class="text-xs text-green-600 dark:text-green-400 mt-2">
                                                            <strong>Ideal für:</strong> Schnelle Alerts, Echtzeit-Benachrichtigungen
                                                        </p>
                                                    </div>
                                                    <div class="rounded-lg bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 p-3">
                                                        <div class="font-medium text-blue-700 dark:text-blue-300 mb-2 flex items-center gap-2">
                                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                            Mit Enrichment-Warten
                                                        </div>
                                                        <ul class="text-sm text-blue-700 dark:text-blue-300 space-y-1">
                                                            <li>✅ Vollständig: Mit Transkript</li>
                                                            <li>✅ Mit Audio-Aufnahme</li>
                                                            <li>⚠️ Langsamer: 30-90 Sekunden</li>
                                                        </ul>
                                                        <p class="text-xs text-blue-600 dark:text-blue-400 mt-2">
                                                            <strong>Ideal für:</strong> Dokumentation, Archivierung, Qualitätssicherung
                                                        </p>
                                                    </div>
                                                </div>
                                            '))
                                            ->hiddenLabel()
                                            ->columnSpanFull(),

                                        Forms\Components\Toggle::make('wait_for_enrichment')
                                            ->label('Auf Transkript & Audio warten')
                                            ->default(false)
                                            ->helperText('Erst senden wenn Aufnahme & Transkript fertig sind (~30-90 Sekunden nach Anruf)')
                                            ->live()
                                            ->columnSpanFull(),

                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('enrichment_timeout_seconds')
                                                    ->label('Max. Wartezeit')
                                                    ->numeric()
                                                    ->default(180)
                                                    ->minValue(30)
                                                    ->maxValue(600)
                                                    ->step(30)
                                                    ->helperText('Falls Transkript/Audio nicht rechtzeitig fertig: Nach dieser Zeit trotzdem senden')
                                                    ->suffix('Sekunden')
                                                    ->visible(fn (Forms\Get $get): bool => $get('wait_for_enrichment') === true),

                                                Forms\Components\TextInput::make('audio_url_ttl_minutes')
                                                    ->label('Audio-Link Gultigkeit')
                                                    ->numeric()
                                                    ->default(60)
                                                    ->minValue(15)
                                                    ->maxValue(1440)
                                                    ->step(15)
                                                    ->helperText('So lange kann der Download-Link angeklickt werden (danach abgelaufen)')
                                                    ->suffix('Minuten'),
                                            ]),

                                        Forms\Components\Placeholder::make('delivery_gate_info')
                                            ->content(new HtmlString('
                                                <div class="rounded-lg bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 p-4 text-sm">
                                                    <div class="font-medium text-blue-800 dark:text-blue-200 mb-2">So funktioniert der Delivery Gate:</div>
                                                    <ol class="list-decimal list-inside space-y-1 text-blue-700 dark:text-blue-300">
                                                        <li>Service Case wird erstellt (sofort nach Anruf-Ende)</li>
                                                        <li>System wartet auf Transkript und Audio-Verarbeitung</li>
                                                        <li>Nach Enrichment ODER Timeout wird zugestellt</li>
                                                        <li>Bei Timeout: Zustellung mit verfügbaren Daten</li>
                                                    </ol>
                                                </div>
                                            '))
                                            ->columnSpanFull()
                                            ->visible(fn (Forms\Get $get): bool => $get('wait_for_enrichment') === true),
                                    ])
                                    ->collapsible(),

                                // Usage Info Section
                                Forms\Components\Section::make('Verwendung')
                                    ->description('Wo wird diese Konfiguration verwendet?')
                                    ->icon('heroicon-o-information-circle')
                                    ->schema([
                                        Forms\Components\Placeholder::make('categories_info')
                                            ->label('Verknupfte Kategorien')
                                            ->content(function ($record) {
                                                if (!$record) {
                                                    return new HtmlString('<span class="text-gray-500">Wird nach dem Speichern angezeigt</span>');
                                                }
                                                $categories = $record->categories;
                                                if ($categories->isEmpty()) {
                                                    return new HtmlString('<span class="text-gray-500">Keine Kategorien verwenden diese Konfiguration</span>');
                                                }
                                                $badges = $categories->map(fn ($cat) =>
                                                    '<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-primary-100 text-primary-800 dark:bg-primary-900 dark:text-primary-200">' . e($cat->name) . '</span>'
                                                )->join(' ');
                                                return new HtmlString($badges);
                                            }),
                                    ])
                                    ->collapsible()
                                    ->collapsed(),

                                // Was passiert? Vorschau-Panel
                                Forms\Components\Section::make('Was passiert bei einem neuen Ticket?')
                                    ->description('Vorschau des konfigurierten Ablaufs')
                                    ->icon('heroicon-o-play')
                                    ->schema([
                                        Forms\Components\Placeholder::make('workflow_preview')
                                            ->hiddenLabel()
                                            ->content(function (Forms\Get $get) {
                                                $outputType = $get('output_type') ?? 'email';
                                                $isActive = $get('is_active') ?? true;
                                                $waitForEnrichment = $get('wait_for_enrichment') ?? false;
                                                $enrichmentTimeout = $get('enrichment_timeout_seconds') ?? 180;

                                                $recipients = $get('email_recipients') ?? [];
                                                $recipientCount = is_array($recipients) ? count($recipients) : 0;

                                                $webhookUrl = $get('webhook_url') ?? '';
                                                $webhookEnabled = $get('webhook_enabled') ?? true;

                                                if (!$isActive) {
                                                    return new HtmlString('
                                                        <div class="rounded-lg bg-gray-100 dark:bg-gray-800 p-4 text-gray-600 dark:text-gray-400">
                                                            <div class="flex items-center gap-2">
                                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path>
                                                                </svg>
                                                                <span class="font-medium">Konfiguration ist deaktiviert</span>
                                                            </div>
                                                            <p class="mt-2 text-sm">Aktiviere die Konfiguration in den Einstellungen, um Benachrichtigungen zu senden.</p>
                                                        </div>
                                                    ');
                                                }

                                                $steps = [];

                                                // Step 1: Timing
                                                if ($waitForEnrichment) {
                                                    $steps[] = '<div class="flex items-start gap-3">
                                                        <div class="flex-shrink-0 w-6 h-6 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center justify-center text-xs font-medium text-blue-600 dark:text-blue-400">1</div>
                                                        <div>
                                                            <p class="font-medium text-gray-900 dark:text-gray-100">Warten auf Transkript & Audio</p>
                                                            <p class="text-sm text-gray-500 dark:text-gray-400">Max. ' . $enrichmentTimeout . ' Sekunden nach Anruf-Ende</p>
                                                        </div>
                                                    </div>';
                                                } else {
                                                    $steps[] = '<div class="flex items-start gap-3">
                                                        <div class="flex-shrink-0 w-6 h-6 rounded-full bg-green-100 dark:bg-green-900 flex items-center justify-center text-xs font-medium text-green-600 dark:text-green-400">1</div>
                                                        <div>
                                                            <p class="font-medium text-gray-900 dark:text-gray-100">Sofortige Zustellung</p>
                                                            <p class="text-sm text-gray-500 dark:text-gray-400">Direkt nach Anruf-Ende (ohne auf Transkript zu warten)</p>
                                                        </div>
                                                    </div>';
                                                }

                                                // Step 2+: Email
                                                $stepNum = 2;
                                                if (in_array($outputType, ['email', 'hybrid'])) {
                                                    if ($recipientCount > 0) {
                                                        $recipientList = implode(', ', array_slice($recipients, 0, 2));
                                                        $more = $recipientCount > 2 ? ' +' . ($recipientCount - 2) . ' weitere' : '';
                                                        $steps[] = '<div class="flex items-start gap-3">
                                                            <div class="flex-shrink-0 w-6 h-6 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center justify-center text-xs font-medium text-blue-600 dark:text-blue-400">' . $stepNum . '</div>
                                                            <div>
                                                                <p class="font-medium text-gray-900 dark:text-gray-100">E-Mail senden</p>
                                                                <p class="text-sm text-gray-500 dark:text-gray-400">An: ' . e($recipientList) . e($more) . '</p>
                                                            </div>
                                                        </div>';
                                                    } else {
                                                        $steps[] = '<div class="flex items-start gap-3">
                                                            <div class="flex-shrink-0 w-6 h-6 rounded-full bg-red-100 dark:bg-red-900 flex items-center justify-center text-xs font-medium text-red-600 dark:text-red-400">' . $stepNum . '</div>
                                                            <div>
                                                                <p class="font-medium text-red-600 dark:text-red-400">E-Mail: Keine Empfanger!</p>
                                                                <p class="text-sm text-gray-500 dark:text-gray-400">Bitte E-Mail-Adressen im E-Mail Tab konfigurieren</p>
                                                            </div>
                                                        </div>';
                                                    }
                                                    $stepNum++;
                                                }

                                                // Step 3+: Webhook
                                                if (in_array($outputType, ['webhook', 'hybrid'])) {
                                                    if (!empty($webhookUrl) && $webhookEnabled) {
                                                        $shortUrl = strlen($webhookUrl) > 40 ? substr($webhookUrl, 0, 40) . '...' : $webhookUrl;
                                                        $steps[] = '<div class="flex items-start gap-3">
                                                            <div class="flex-shrink-0 w-6 h-6 rounded-full bg-amber-100 dark:bg-amber-900 flex items-center justify-center text-xs font-medium text-amber-600 dark:text-amber-400">' . $stepNum . '</div>
                                                            <div>
                                                                <p class="font-medium text-gray-900 dark:text-gray-100">Webhook POST</p>
                                                                <p class="text-sm text-gray-500 dark:text-gray-400 font-mono">' . e($shortUrl) . '</p>
                                                            </div>
                                                        </div>';
                                                    } elseif (!$webhookEnabled) {
                                                        $steps[] = '<div class="flex items-start gap-3">
                                                            <div class="flex-shrink-0 w-6 h-6 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center text-xs font-medium text-gray-500">' . $stepNum . '</div>
                                                            <div>
                                                                <p class="font-medium text-gray-500">Webhook pausiert</p>
                                                                <p class="text-sm text-gray-400">Webhook ist deaktiviert</p>
                                                            </div>
                                                        </div>';
                                                    } else {
                                                        $steps[] = '<div class="flex items-start gap-3">
                                                            <div class="flex-shrink-0 w-6 h-6 rounded-full bg-red-100 dark:bg-red-900 flex items-center justify-center text-xs font-medium text-red-600 dark:text-red-400">' . $stepNum . '</div>
                                                            <div>
                                                                <p class="font-medium text-red-600 dark:text-red-400">Webhook: URL fehlt!</p>
                                                                <p class="text-sm text-gray-500 dark:text-gray-400">Bitte URL im Webhook Tab konfigurieren</p>
                                                            </div>
                                                        </div>';
                                                    }
                                                }

                                                return new HtmlString('
                                                    <div class="space-y-4">
                                                        ' . implode("\n", $steps) . '
                                                    </div>
                                                ');
                                            }),
                                    ])
                                    ->collapsible(),
                            ]),
                    ])
                    ->persistTabInQueryString()
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('company.name')
                    ->label('Firma')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->description(function ($record) {
                        $all = $record->email_recipients ?? [];
                        $muted = $record->muted_recipients ?? [];
                        $active = array_diff($all, $muted);

                        if (empty($active)) {
                            if (!empty($muted)) {
                                return '⚠️ Alle ' . count($muted) . ' Empfänger pausiert';
                            }
                            return null;
                        }

                        $display = implode(', ', array_slice($active, 0, 2));
                        $more = count($active) > 2 ? ' +' . (count($active) - 2) : '';
                        $mutedNote = count($muted) > 0 ? ' (' . count($muted) . ' pausiert)' : '';

                        return $display . $more . $mutedNote;
                    }),
                Tables\Columns\BadgeColumn::make('output_type')
                    ->label('Kanal')
                    ->colors([
                        'info' => ServiceOutputConfiguration::TYPE_EMAIL,
                        'warning' => ServiceOutputConfiguration::TYPE_WEBHOOK,
                        'success' => ServiceOutputConfiguration::TYPE_HYBRID,
                    ])
                    ->icons([
                        'heroicon-o-envelope' => ServiceOutputConfiguration::TYPE_EMAIL,
                        'heroicon-o-link' => ServiceOutputConfiguration::TYPE_WEBHOOK,
                        'heroicon-o-arrows-right-left' => ServiceOutputConfiguration::TYPE_HYBRID,
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        ServiceOutputConfiguration::TYPE_EMAIL => 'E-Mail',
                        ServiceOutputConfiguration::TYPE_WEBHOOK => 'Webhook',
                        ServiceOutputConfiguration::TYPE_HYBRID => 'Hybrid',
                        default => $state,
                    }),
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Aktiv')
                    ->sortable(),
                Tables\Columns\IconColumn::make('webhook_enabled')
                    ->label('WH')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->tooltip('Webhook Status')
                    ->visible(fn ($record) => $record?->sendsWebhook() ?? false),
                Tables\Columns\TextColumn::make('categories_count')
                    ->label('Kategorien')
                    ->counts('categories')
                    ->sortable()
                    ->badge()
                    ->color('info'),
                Tables\Columns\IconColumn::make('wait_for_enrichment')
                    ->label('Gate')
                    ->boolean()
                    ->trueIcon('heroicon-o-clock')
                    ->falseIcon('heroicon-o-bolt')
                    ->trueColor('warning')
                    ->falseColor('gray')
                    ->tooltip(fn ($record) => $record?->wait_for_enrichment
                        ? 'Wartet auf Enrichment (' . ($record->enrichment_timeout_seconds ?? 180) . 's)'
                        : 'Sofortige Zustellung'
                    )
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name', 'asc')
            ->filters([
                Tables\Filters\SelectFilter::make('company_id')
                    ->label('Firma')
                    ->relationship('company', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('output_type')
                    ->label('Ausgabekanal')
                    ->options([
                        ServiceOutputConfiguration::TYPE_EMAIL => 'E-Mail',
                        ServiceOutputConfiguration::TYPE_WEBHOOK => 'Webhook',
                        ServiceOutputConfiguration::TYPE_HYBRID => 'Hybrid',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status')
                    ->placeholder('Alle')
                    ->trueLabel('Nur aktive')
                    ->falseLabel('Nur inaktive'),
                Tables\Filters\TernaryFilter::make('wait_for_enrichment')
                    ->label('Delivery Gate')
                    ->placeholder('Alle')
                    ->trueLabel('Mit Enrichment-Warten')
                    ->falseLabel('Sofortige Zustellung'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation()
                    ->before(function (Tables\Actions\DeleteAction $action, ServiceOutputConfiguration $record) {
                        if ($record->categories()->exists()) {
                            $count = $record->categories()->count();
                            \Filament\Notifications\Notification::make()
                                ->title('Löschen nicht möglich')
                                ->body("Diese Konfiguration wird von {$count} Kategorie(n) verwendet. Entferne zuerst die Zuordnungen.")
                                ->danger()
                                ->persistent()
                                ->send();
                            $action->cancel();
                        }
                    }),
                Tables\Actions\Action::make('toggle_active')
                    ->label(fn (ServiceOutputConfiguration $record) => $record->is_active ? 'Deaktivieren' : 'Aktivieren')
                    ->icon(fn (ServiceOutputConfiguration $record) => $record->is_active ? 'heroicon-o-pause' : 'heroicon-o-play')
                    ->color(fn (ServiceOutputConfiguration $record) => $record->is_active ? 'warning' : 'success')
                    ->requiresConfirmation()
                    ->action(fn (ServiceOutputConfiguration $record) => $record->update(['is_active' => !$record->is_active])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Aktivieren')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(fn ($records) => $records->each->update(['is_active' => true])),
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deaktivieren')
                        ->icon('heroicon-o-x-circle')
                        ->color('warning')
                        ->action(fn ($records) => $records->each->update(['is_active' => false])),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\DeliveryLogsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServiceOutputConfigurations::route('/'),
            'create' => Pages\CreateServiceOutputConfiguration::route('/create'),
            'edit' => Pages\EditServiceOutputConfiguration::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::active()->count();
    }

    /**
     * Eager load company relationship for performance.
     */
    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with('company');
    }
}
