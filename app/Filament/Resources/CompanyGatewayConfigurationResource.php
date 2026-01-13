<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CompanyGatewayConfigurationResource\Pages;
use App\Models\CompanyGatewayConfiguration;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

/**
 * CompanyGatewayConfigurationResource
 *
 * Filament Admin resource for managing per-company gateway settings.
 * Enables multi-tenant configuration of Service Gateway features.
 *
 * Features:
 * - Gateway mode selection (appointment/service_desk/hybrid)
 * - Enrichment & delivery timing configuration
 * - Admin alerts (email + Slack)
 * - Service desk defaults
 *
 * @see \App\Models\CompanyGatewayConfiguration
 */
class CompanyGatewayConfigurationResource extends Resource
{
    protected static ?string $model = CompanyGatewayConfiguration::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationGroup = 'Service Gateway';
    protected static ?string $navigationLabel = 'Gateway Einstellungen';
    protected static ?string $modelLabel = 'Gateway Konfiguration';
    protected static ?string $pluralModelLabel = 'Gateway Konfigurationen';
    protected static ?int $navigationSort = 13;

    /**
     * Only show in navigation when Service Gateway is enabled.
     */
    public static function shouldRegisterNavigation(): bool
    {
        return config('gateway.mode_enabled', false);
    }

    /**
     * Scope query to current user's company (multi-tenancy).
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        // Super admins can see all, regular users only their company
        $user = auth()->user();
        if ($user && !$user->hasRole('super_admin')) {
            $query->where('company_id', $user->company_id);
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            // Status Panel at top
            Forms\Components\Placeholder::make('status_panel')
                ->label('')
                ->content(function (?CompanyGatewayConfiguration $record, Forms\Get $get): HtmlString {
                    return self::buildStatusPanel($record, $get);
                })
                ->columnSpanFull(),

            Forms\Components\Tabs::make('Gateway Konfiguration')
                ->tabs([
                    self::getBasicSettingsTab(),
                    self::getEnrichmentTab(),
                    self::getAlertsTab(),
                    self::getServiceDeskTab(),
                ])
                ->columnSpanFull()
                ->persistTabInQueryString(),
        ]);
    }

    /**
     * Tab 1: Basic Gateway Settings
     */
    private static function getBasicSettingsTab(): Forms\Components\Tabs\Tab
    {
        return Forms\Components\Tabs\Tab::make('Grundeinstellungen')
            ->icon('heroicon-m-cog')
            ->schema([
                Forms\Components\Hidden::make('company_id')
                    ->default(fn () => auth()->user()?->company_id)
                    ->dehydrated(),

                Forms\Components\Section::make('Gateway Modus')
                    ->description('Definiert wie eingehende Anrufe verarbeitet werden')
                    ->icon('heroicon-o-signal')
                    ->schema([
                        Forms\Components\Toggle::make('gateway_enabled')
                            ->label('Gateway aktiviert')
                            ->helperText('Aktiviert das Service Gateway für diese Firma')
                            ->default(false)
                            ->live()
                            ->columnSpanFull(),

                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\Select::make('gateway_mode')
                                ->label('Modus')
                                ->options([
                                    'appointment' => 'Terminbuchung',
                                    'service_desk' => 'Service Desk',
                                    'hybrid' => 'Hybrid (Automatische Erkennung)',
                                ])
                                ->default('appointment')
                                ->required()
                                ->live()
                                ->helperText('Appointment = Terminbuchung, Service Desk = Ticketerstellung, Hybrid = KI-basierte Erkennung'),

                            Forms\Components\Select::make('hybrid_fallback_mode')
                                ->label('Fallback Modus')
                                ->options([
                                    'appointment' => 'Terminbuchung',
                                    'service_desk' => 'Service Desk',
                                ])
                                ->default('appointment')
                                ->visible(fn (Forms\Get $get) => $get('gateway_mode') === 'hybrid')
                                ->helperText('Wird verwendet wenn der Intent nicht erkannt werden kann'),
                        ]),
                    ])
                    ->columns(1),
            ]);
    }

    /**
     * Tab 2: Enrichment & Delivery Settings
     */
    private static function getEnrichmentTab(): Forms\Components\Tabs\Tab
    {
        return Forms\Components\Tabs\Tab::make('Enrichment & Delivery')
            ->icon('heroicon-m-clock')
            ->schema([
                Forms\Components\Section::make('2-Phase Delivery')
                    ->description('Wartet auf Datenanreicherung bevor Output gesendet wird')
                    ->icon('heroicon-o-arrow-path')
                    ->schema([
                        Forms\Components\Toggle::make('enrichment_enabled')
                            ->label('Enrichment aktivieren')
                            ->helperText('Aktiviert 2-Phase Delivery: Wartet auf Customer-Matching und Audio-Download')
                            ->default(false)
                            ->live(),

                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('delivery_initial_delay_seconds')
                                ->label('Initiale Verzögerung (Sekunden)')
                                ->numeric()
                                ->default(90)
                                ->minValue(0)
                                ->maxValue(3600)
                                ->helperText('Wartezeit vor dem ersten Delivery-Versuch')
                                ->visible(fn (Forms\Get $get) => $get('enrichment_enabled')),

                            Forms\Components\TextInput::make('enrichment_timeout_seconds')
                                ->label('Timeout (Sekunden)')
                                ->numeric()
                                ->default(180)
                                ->minValue(30)
                                ->maxValue(600)
                                ->helperText('Maximale Wartezeit für Enrichment (dann partial delivery)')
                                ->visible(fn (Forms\Get $get) => $get('enrichment_enabled')),
                        ]),
                    ])
                    ->collapsible(),

                Forms\Components\Section::make('Audio Konfiguration')
                    ->description('Einstellungen für Audio-URLs in Webhooks')
                    ->icon('heroicon-o-musical-note')
                    ->schema([
                        Forms\Components\Toggle::make('audio_in_webhook')
                            ->label('Audio-URL in Webhooks')
                            ->helperText('Fügt eine signierte Audio-URL zum Webhook-Payload hinzu')
                            ->default(false)
                            ->live(),

                        Forms\Components\TextInput::make('audio_url_ttl_minutes')
                            ->label('Audio-URL Gültigkeit (Minuten)')
                            ->numeric()
                            ->default(60)
                            ->minValue(5)
                            ->maxValue(1440)
                            ->helperText('Wie lange die signierte Audio-URL gültig ist')
                            ->visible(fn (Forms\Get $get) => $get('audio_in_webhook')),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    /**
     * Tab 3: Admin Alerts Configuration
     */
    private static function getAlertsTab(): Forms\Components\Tabs\Tab
    {
        return Forms\Components\Tabs\Tab::make('Alerts')
            ->icon('heroicon-m-bell-alert')
            ->badge(fn (?CompanyGatewayConfiguration $record) =>
                $record?->alerts_enabled && $record?->admin_email ? '✓' : null
            )
            ->schema([
                Forms\Components\Section::make('Admin-Benachrichtigungen')
                    ->description('Benachrichtigungen bei Delivery-Fehlern nach allen Retries')
                    ->icon('heroicon-o-envelope')
                    ->schema([
                        Forms\Components\Toggle::make('alerts_enabled')
                            ->label('Alerts aktiviert')
                            ->helperText('Sendet Benachrichtigungen wenn Deliveries permanent fehlschlagen')
                            ->default(true)
                            ->live(),

                        Forms\Components\TextInput::make('admin_email')
                            ->label('Admin E-Mail(s)')
                            ->placeholder('admin@example.com, support@example.com')
                            ->helperText('Komma-getrennte Liste von E-Mail-Adressen')
                            ->maxLength(500)
                            ->visible(fn (Forms\Get $get) => $get('alerts_enabled')),

                        Forms\Components\TextInput::make('slack_webhook')
                            ->label('Slack Webhook URL')
                            ->placeholder('https://hooks.slack.com/services/...')
                            ->url()
                            ->helperText('Optional: Slack-Kanal für kritische Alerts')
                            ->maxLength(500)
                            ->visible(fn (Forms\Get $get) => $get('alerts_enabled')),
                    ]),
            ]);
    }

    /**
     * Tab 4: Service Desk Defaults
     */
    private static function getServiceDeskTab(): Forms\Components\Tabs\Tab
    {
        return Forms\Components\Tabs\Tab::make('Service Desk')
            ->icon('heroicon-m-ticket')
            ->schema([
                Forms\Components\Section::make('Standard-Werte')
                    ->description('Voreinstellungen für neue Service Cases')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->schema([
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\Select::make('default_case_type')
                                ->label('Standard Case-Typ')
                                ->options([
                                    'incident' => 'Incident (Störung)',
                                    'request' => 'Request (Anfrage)',
                                    'problem' => 'Problem (Ursachenanalyse)',
                                ])
                                ->default('incident')
                                ->helperText('Typ für neue Cases wenn nicht erkannt'),

                            Forms\Components\Select::make('default_priority')
                                ->label('Standard Priorität')
                                ->options([
                                    1 => '1 - Kritisch',
                                    2 => '2 - Hoch',
                                    3 => '3 - Mittel',
                                    4 => '4 - Niedrig',
                                ])
                                ->placeholder('Keine Vorgabe')
                                ->helperText('Priorität wenn nicht explizit gesetzt'),
                        ]),
                    ]),

                Forms\Components\Section::make('Intent-Erkennung')
                    ->description('Konfiguration für Hybrid-Modus')
                    ->icon('heroicon-o-cpu-chip')
                    ->schema([
                        Forms\Components\TextInput::make('intent_confidence_threshold')
                            ->label('Konfidenz-Schwelle')
                            ->numeric()
                            ->default(0.75)
                            ->minValue(0)
                            ->maxValue(1)
                            ->step(0.05)
                            ->helperText('Minimum-Konfidenz (0.00-1.00) für Intent-Klassifizierung. Bei niedrigerer Konfidenz wird Fallback verwendet.'),
                    ])
                    ->visible(fn (Forms\Get $get, ?CompanyGatewayConfiguration $record) =>
                        ($get('gateway_mode') ?? $record?->gateway_mode) === 'hybrid'
                    )
                    ->collapsible(),
            ]);
    }

    /**
     * Build the status panel HTML
     */
    private static function buildStatusPanel(?CompanyGatewayConfiguration $record, Forms\Get $get): HtmlString
    {
        $gatewayEnabled = $get('gateway_enabled') ?? $record?->gateway_enabled ?? false;
        $mode = $get('gateway_mode') ?? $record?->gateway_mode ?? 'appointment';
        $enrichmentEnabled = $get('enrichment_enabled') ?? $record?->enrichment_enabled ?? false;
        $alertsEnabled = $get('alerts_enabled') ?? $record?->alerts_enabled ?? true;
        $adminEmail = $get('admin_email') ?? $record?->admin_email ?? null;

        $modeLabels = [
            'appointment' => 'Terminbuchung',
            'service_desk' => 'Service Desk',
            'hybrid' => 'Hybrid',
        ];

        $statusColor = $gatewayEnabled ? 'green' : 'gray';
        $modeColor = match($mode) {
            'hybrid' => 'purple',
            'service_desk' => 'blue',
            default => 'emerald',
        };

        $alertsConfigured = $alertsEnabled && !empty($adminEmail);

        return new HtmlString("
            <div class=\"p-4 rounded-lg bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700\">
                <div class=\"flex flex-wrap gap-4\">
                    <div class=\"flex items-center gap-2\">
                        <span class=\"w-3 h-3 rounded-full bg-{$statusColor}-500\"></span>
                        <span class=\"text-sm font-medium text-gray-700 dark:text-gray-300\">
                            Gateway: " . ($gatewayEnabled ? 'Aktiv' : 'Inaktiv') . "
                        </span>
                    </div>
                    <div class=\"flex items-center gap-2\">
                        <span class=\"px-2 py-0.5 text-xs font-semibold rounded bg-{$modeColor}-100 text-{$modeColor}-800 dark:bg-{$modeColor}-900 dark:text-{$modeColor}-200\">
                            {$modeLabels[$mode]}
                        </span>
                    </div>
                    <div class=\"flex items-center gap-2\">
                        <span class=\"text-sm text-gray-600 dark:text-gray-400\">
                            Enrichment: " . ($enrichmentEnabled ? '✓' : '–') . "
                        </span>
                    </div>
                    <div class=\"flex items-center gap-2\">
                        <span class=\"text-sm text-gray-600 dark:text-gray-400\">
                            Alerts: " . ($alertsConfigured ? '✓ Konfiguriert' : '⚠ Nicht konfiguriert') . "
                        </span>
                    </div>
                </div>
            </div>
        ");
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('company.name')
                    ->label('Unternehmen')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\IconColumn::make('gateway_enabled')
                    ->label('Aktiv')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray'),

                Tables\Columns\TextColumn::make('gateway_mode')
                    ->label('Modus')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'appointment' => 'Termin',
                        'service_desk' => 'Service Desk',
                        'hybrid' => 'Hybrid',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'appointment' => 'success',
                        'service_desk' => 'info',
                        'hybrid' => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\IconColumn::make('enrichment_enabled')
                    ->label('Enrichment')
                    ->boolean()
                    ->trueIcon('heroicon-o-check')
                    ->falseIcon('heroicon-o-minus')
                    ->trueColor('success')
                    ->falseColor('gray'),

                Tables\Columns\IconColumn::make('alerts_enabled')
                    ->label('Alerts')
                    ->boolean()
                    ->trueIcon('heroicon-o-bell')
                    ->falseIcon('heroicon-o-bell-slash')
                    ->trueColor('warning')
                    ->falseColor('gray'),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Aktualisiert')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('gateway_enabled')
                    ->label('Gateway Status')
                    ->placeholder('Alle')
                    ->trueLabel('Aktiviert')
                    ->falseLabel('Deaktiviert'),

                Tables\Filters\SelectFilter::make('gateway_mode')
                    ->label('Modus')
                    ->options([
                        'appointment' => 'Terminbuchung',
                        'service_desk' => 'Service Desk',
                        'hybrid' => 'Hybrid',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('updated_at', 'desc')
            ->emptyStateHeading('Keine Gateway-Konfigurationen')
            ->emptyStateDescription('Erstelle eine Konfiguration um das Service Gateway für eine Firma zu aktivieren.')
            ->emptyStateIcon('heroicon-o-cog-6-tooth');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCompanyGatewayConfigurations::route('/'),
            'create' => Pages\CreateCompanyGatewayConfiguration::route('/create'),
            'edit' => Pages\EditCompanyGatewayConfiguration::route('/{record}/edit'),
        ];
    }
}
