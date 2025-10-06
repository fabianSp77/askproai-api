<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TenantResource\Pages;
use App\Models\Tenant;
use App\Models\Company;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Tabs;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Split;
use Filament\Infolists\Components\Grid;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Carbon\Carbon;

class TenantResource extends Resource
{
    protected static ?string $model = Tenant::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationLabel = 'Mandanten';

    protected static ?string $modelLabel = 'Mandant';

    protected static ?string $pluralModelLabel = 'Mandanten';

    protected static ?string $navigationGroup = 'System';

    protected static ?int $navigationSort = 7;

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
                Tabs::make('Mandanten-Verwaltung')
                    ->tabs([
                        Tabs\Tab::make('🏢 Grunddaten')
                            ->schema([
                                Forms\Components\Section::make('Mandanten-Informationen')
                                    ->description('Grundlegende Einstellungen des Mandanten')
                                    ->schema([
                                        Forms\Components\TextInput::make('name')
                                            ->label('Mandantenname')
                                            ->required()
                                            ->maxLength(255)
                                            ->placeholder('z.B. Hauptmandant, Niederlassung Berlin')
                                            ->helperText('Eindeutiger Name für diesen Mandanten')
                                            ->prefixIcon('heroicon-m-building-office-2')
                                            ->reactive()
                                            ->afterStateUpdated(fn ($state, callable $set) =>
                                                $set('slug', Str::slug($state))
                                            ),

                                        Forms\Components\TextInput::make('slug')
                                            ->label('Slug')
                                            ->required()
                                            ->unique(ignoreRecord: true)
                                            ->maxLength(255)
                                            ->helperText('URL-freundlicher Name (automatisch generiert)')
                                            ->disabled(fn (?Tenant $record) => $record !== null),

                                        Forms\Components\TextInput::make('domain')
                                            ->label('Domain')
                                            ->maxLength(255)
                                            ->placeholder('subdomain.example.com')
                                            ->helperText('Primäre Domain für diesen Mandanten')
                                            ->url()
                                            ->suffixIcon('heroicon-m-globe-alt'),

                                        Forms\Components\Select::make('type')
                                            ->label('Mandantentyp')
                                            ->options([
                                                'master' => '👑 Hauptmandant',
                                                'standard' => '🏢 Standard',
                                                'trial' => '🧪 Testversion',
                                                'demo' => '🎮 Demo',
                                                'partner' => '🤝 Partner',
                                                'reseller' => '💼 Mandant',
                                            ])
                                            ->default('standard')
                                            ->required()
                                            ->native(false),

                                        Forms\Components\Textarea::make('description')
                                            ->label('Beschreibung')
                                            ->rows(3)
                                            ->maxLength(1000)
                                            ->placeholder('Beschreiben Sie diesen Mandanten')
                                            ->columnSpanFull(),

                                        Forms\Components\Toggle::make('is_active')
                                            ->label('Aktiv')
                                            ->helperText('Mandant ist aktiviert und nutzbar')
                                            ->default(true)
                                            ->inline(),

                                        Forms\Components\Toggle::make('is_verified')
                                            ->label('Verifiziert')
                                            ->helperText('Mandant wurde überprüft und bestätigt')
                                            ->default(false)
                                            ->inline(),
                                    ])
                                    ->columns(2),

                                Forms\Components\Section::make('Kontaktdaten')
                                    ->description('Kontaktinformationen für diesen Mandanten')
                                    ->schema([
                                        Forms\Components\TextInput::make('contact_name')
                                            ->label('Ansprechpartner')
                                            ->maxLength(255)
                                            ->placeholder('Max Mustermann')
                                            ->prefixIcon('heroicon-m-user'),

                                        Forms\Components\TextInput::make('contact_email')
                                            ->label('E-Mail')
                                            ->email()
                                            ->maxLength(255)
                                            ->placeholder('kontakt@example.com')
                                            ->prefixIcon('heroicon-m-envelope'),

                                        Forms\Components\TextInput::make('contact_phone')
                                            ->label('Telefon')
                                            ->tel()
                                            ->maxLength(255)
                                            ->placeholder('+49 30 12345678')
                                            ->prefixIcon('heroicon-m-phone'),

                                        Forms\Components\Select::make('timezone')
                                            ->label('Zeitzone')
                                            ->options([
                                                'Europe/Berlin' => '🇩🇪 Berlin (UTC+1)',
                                                'Europe/London' => '🇬🇧 London (UTC+0)',
                                                'Europe/Paris' => '🇫🇷 Paris (UTC+1)',
                                                'America/New_York' => '🇺🇸 New York (UTC-5)',
                                                'Asia/Tokyo' => '🇯🇵 Tokyo (UTC+9)',
                                            ])
                                            ->default('Europe/Berlin')
                                            ->searchable(),
                                    ])
                                    ->columns(2)
                                    ->collapsed(),
                            ]),

                        Tabs\Tab::make('💰 Abrechnung')
                            ->schema([
                                Forms\Components\Section::make('Kontostatus')
                                    ->description('Finanzieller Status des Mandanten')
                                    ->schema([
                                        Forms\Components\TextInput::make('balance_cents')
                                            ->label('Guthaben (Cent)')
                                            ->numeric()
                                            ->default(0)
                                            ->minValue(-1000000)
                                            ->maxValue(100000000)
                                            ->helperText('Aktuelles Guthaben in Cent')
                                            ->prefixIcon('heroicon-m-currency-euro')
                                            ->reactive()
                                            ->afterStateUpdated(fn ($state, callable $set) =>
                                                $set('balance_display', '€ ' . number_format($state / 100, 2, ',', '.'))
                                            ),

                                        Forms\Components\Placeholder::make('balance_display')
                                            ->label('Guthaben (EUR)')
                                            ->content(fn (?Tenant $record) => $record
                                                ? '€ ' . number_format($record->balance_cents / 100, 2, ',', '.')
                                                : '€ 0,00'),

                                        Forms\Components\TextInput::make('credit_limit')
                                            ->label('Kreditlimit (EUR)')
                                            ->numeric()
                                            ->default(0)
                                            ->minValue(0)
                                            ->maxValue(100000)
                                            ->helperText('Maximaler Kreditrahmen')
                                            ->prefix('€'),

                                        Forms\Components\Select::make('billing_cycle')
                                            ->label('Abrechnungszyklus')
                                            ->options([
                                                'monthly' => '📅 Monatlich',
                                                'quarterly' => '📊 Quartalsweise',
                                                'yearly' => '📆 Jährlich',
                                                'prepaid' => '💳 Prepaid',
                                                'postpaid' => '📬 Postpaid',
                                            ])
                                            ->default('monthly'),

                                        Forms\Components\DatePicker::make('billing_start_date')
                                            ->label('Abrechnungsbeginn')
                                            ->default(now())
                                            ->displayFormat('d.m.Y'),

                                        Forms\Components\DatePicker::make('next_billing_date')
                                            ->label('Nächste Abrechnung')
                                            ->displayFormat('d.m.Y')
                                            ->afterOrEqual('billing_start_date'),

                                        Forms\Components\Toggle::make('auto_recharge')
                                            ->label('Automatische Aufladung')
                                            ->helperText('Guthaben automatisch aufladen bei Unterschreitung')
                                            ->default(false),

                                        Forms\Components\TextInput::make('auto_recharge_amount')
                                            ->label('Aufladebetrag (EUR)')
                                            ->numeric()
                                            ->default(100)
                                            ->minValue(10)
                                            ->maxValue(10000)
                                            ->visible(fn (callable $get) => $get('auto_recharge'))
                                            ->prefix('€'),
                                    ])
                                    ->columns(2),

                                Forms\Components\Section::make('Preismodell')
                                    ->description('Tarif und Preisgestaltung')
                                    ->schema([
                                        Forms\Components\Select::make('pricing_plan')
                                            ->label('Tarif')
                                            ->options([
                                                'starter' => '🚀 Starter (€49/Monat)',
                                                'professional' => '💼 Professional (€149/Monat)',
                                                'business' => '🏢 Business (€399/Monat)',
                                                'enterprise' => '🏭 Enterprise (Individuell)',
                                                'custom' => '⚙️ Individuell',
                                            ])
                                            ->default('starter')
                                            ->required(),

                                        Forms\Components\TextInput::make('monthly_fee')
                                            ->label('Monatliche Grundgebühr (EUR)')
                                            ->numeric()
                                            ->default(0)
                                            ->minValue(0)
                                            ->prefix('€'),

                                        Forms\Components\TextInput::make('per_minute_rate')
                                            ->label('Minutenpreis (Cent)')
                                            ->numeric()
                                            ->default(5)
                                            ->minValue(0)
                                            ->maxValue(100)
                                            ->helperText('Preis pro Gesprächsminute in Cent'),

                                        Forms\Components\TextInput::make('discount_percentage')
                                            ->label('Rabatt (%)')
                                            ->numeric()
                                            ->default(0)
                                            ->minValue(0)
                                            ->maxValue(100)
                                            ->suffix('%'),
                                    ])
                                    ->columns(2)
                                    ->collapsed(),
                            ]),

                        Tabs\Tab::make('🔗 Integrationen')
                            ->schema([
                                Forms\Components\Section::make('API-Konfiguration')
                                    ->description('API-Zugangsdaten und Sicherheit')
                                    ->schema([
                                        Forms\Components\TextInput::make('api_key')
                                            ->label('API-Schlüssel')
                                            ->password()
                                            ->maxLength(255)
                                            ->revealable()
                                            
                                            ->helperText('Geheimer API-Schlüssel für diesen Mandanten')
                                            ->default(fn () => Str::random(32))
                                            ->suffixAction(
                                                Forms\Components\Actions\Action::make('regenerate')
                                                    ->label('Neu generieren')
                                                    ->icon('heroicon-m-arrow-path')
                                                    ->requiresConfirmation()
                                                    ->action(fn (callable $set) => $set('api_key', Str::random(32)))
                                            ),

                                        Forms\Components\TextInput::make('api_secret')
                                            ->label('API-Secret')
                                            ->password()
                                            ->maxLength(255)
                                            ->revealable()
                                            ->helperText('Zusätzliches Geheimnis für erweiterte Sicherheit')
                                            ->default(fn () => Str::random(64)),

                                        Forms\Components\TagsInput::make('allowed_ips')
                                            ->label('Erlaubte IPs')
                                            ->placeholder('IP-Adresse eingeben')
                                            ->helperText('Whitelist für API-Zugriffe (leer = alle erlaubt)')
                                            ->suggestions([
                                                '127.0.0.1',
                                                '::1',
                                            ]),

                                        Forms\Components\TextInput::make('webhook_url')
                                            ->label('Webhook URL')
                                            ->url()
                                            ->maxLength(500)
                                            ->placeholder('https://api.example.com/webhook')
                                            ->helperText('URL für Event-Benachrichtigungen')
                                            ->suffixIcon('heroicon-m-arrow-top-right-on-square'),

                                        Forms\Components\Select::make('webhook_events')
                                            ->label('Webhook Events')
                                            ->multiple()
                                            ->options([
                                                'call.started' => '📞 Anruf gestartet',
                                                'call.ended' => '📵 Anruf beendet',
                                                'agent.created' => '🤖 Agent erstellt',
                                                'payment.received' => '💰 Zahlung erhalten',
                                                'balance.low' => '⚠️ Niedriger Kontostand',
                                            ])
                                            ->helperText('Events, die Webhooks auslösen'),
                                    ])
                                    ->columns(1),

                                Forms\Components\Section::make('Cal.com Integration')
                                    ->description('Kalenderbuchungs-Integration')
                                    ->schema([
                                        Forms\Components\TextInput::make('calcom_team_slug')
                                            ->label('Cal.com Team Slug')
                                            ->maxLength(255)
                                            ->placeholder('mein-team')
                                            ->helperText('Team-Identifier bei Cal.com'),

                                        Forms\Components\TextInput::make('calcom_api_key')
                                            ->label('Cal.com API Key')
                                            ->password()
                                            ->maxLength(255)
                                            ->revealable()
                                            ->helperText('API-Schlüssel für Cal.com'),

                                        Forms\Components\Toggle::make('calcom_enabled')
                                            ->label('Cal.com aktiviert')
                                            ->helperText('Kalenderbuchungen über Cal.com ermöglichen')
                                            ->default(false),

                                        Forms\Components\TextInput::make('calcom_default_duration')
                                            ->label('Standard-Termindauer (Min.)')
                                            ->numeric()
                                            ->default(30)
                                            ->minValue(15)
                                            ->maxValue(480)
                                            ->visible(fn (callable $get) => $get('calcom_enabled')),
                                    ])
                                    ->columns(2)
                                    ->collapsed(),

                                Forms\Components\Section::make('Weitere Integrationen')
                                    ->description('Zusätzliche Service-Verbindungen')
                                    ->schema([
                                        Forms\Components\KeyValue::make('integrations')
                                            ->label('Service-Integrationen')
                                            ->keyLabel('Service')
                                            ->valueLabel('Konfiguration')
                                            ->addActionLabel('Integration hinzufügen')
                                            ->helperText('Konfigurationen für externe Services')
                                            ->columnSpanFull(),
                                    ])
                                    ->collapsed(),
                            ]),

                        Tabs\Tab::make('🚦 Limits & Quota')
                            ->schema([
                                Forms\Components\Section::make('Nutzungslimits')
                                    ->description('Beschränkungen und Kontingente')
                                    ->schema([
                                        Forms\Components\TextInput::make('max_users')
                                            ->label('Max. Benutzer')
                                            ->numeric()
                                            ->default(10)
                                            ->minValue(1)
                                            ->maxValue(10000)
                                            ->helperText('Maximale Anzahl von Benutzern'),

                                        Forms\Components\TextInput::make('max_agents')
                                            ->label('Max. KI-Agenten')
                                            ->numeric()
                                            ->default(5)
                                            ->minValue(0)
                                            ->maxValue(1000)
                                            ->helperText('Maximale Anzahl von KI-Agenten'),

                                        Forms\Components\TextInput::make('max_phone_numbers')
                                            ->label('Max. Telefonnummern')
                                            ->numeric()
                                            ->default(10)
                                            ->minValue(0)
                                            ->maxValue(1000)
                                            ->helperText('Maximale Anzahl von Telefonnummern'),

                                        Forms\Components\TextInput::make('max_concurrent_calls')
                                            ->label('Max. gleichzeitige Anrufe')
                                            ->numeric()
                                            ->default(50)
                                            ->minValue(1)
                                            ->maxValue(10000)
                                            ->helperText('Maximale Anzahl gleichzeitiger Gespräche'),

                                        Forms\Components\TextInput::make('monthly_minutes_limit')
                                            ->label('Monatliche Minuten')
                                            ->numeric()
                                            ->default(10000)
                                            ->minValue(0)
                                            ->maxValue(1000000)
                                            ->helperText('Inkludierte Gesprächsminuten pro Monat'),

                                        Forms\Components\TextInput::make('storage_limit_gb')
                                            ->label('Speicherplatz (GB)')
                                            ->numeric()
                                            ->default(10)
                                            ->minValue(1)
                                            ->maxValue(1000)
                                            ->helperText('Maximaler Speicherplatz in GB'),

                                        Forms\Components\Toggle::make('unlimited_minutes')
                                            ->label('Unbegrenzte Minuten')
                                            ->helperText('Keine Beschränkung der Gesprächsminuten')
                                            ->default(false),

                                        Forms\Components\Toggle::make('unlimited_storage')
                                            ->label('Unbegrenzter Speicher')
                                            ->helperText('Keine Speicherplatzbeschränkung')
                                            ->default(false),
                                    ])
                                    ->columns(2),

                                Forms\Components\Section::make('Aktuelle Nutzung')
                                    ->description('Aktuelle Verbrauchswerte')
                                    ->schema([
                                        Forms\Components\Placeholder::make('current_users')
                                            ->label('Aktuelle Benutzer')
                                            ->content(fn (?Tenant $record) => $record
                                                ? $record->users()->count() . ' / ' . ($record->max_users ?? 'unbegrenzt')
                                                : '0'),

                                        Forms\Components\Placeholder::make('current_agents')
                                            ->label('Aktuelle KI-Agenten')
                                            ->content(fn (?Tenant $record) => $record
                                                ? $record->agents()->count() . ' / ' . ($record->max_agents ?? 'unbegrenzt')
                                                : '0'),

                                        Forms\Components\Placeholder::make('current_phone_numbers')
                                            ->label('Aktuelle Telefonnummern')
                                            ->content(fn (?Tenant $record) => $record
                                                ? $record->phoneNumbers()->count() . ' / ' . ($record->max_phone_numbers ?? 'unbegrenzt')
                                                : '0'),

                                        Forms\Components\Placeholder::make('minutes_used_this_month')
                                            ->label('Minuten diesen Monat')
                                            ->content(fn (?Tenant $record) => $record
                                                ? number_format($record->minutes_used_this_month ?? 0, 0, ',', '.') . ' / ' . number_format($record->monthly_minutes_limit ?? 0, 0, ',', '.')
                                                : '0'),

                                        Forms\Components\Placeholder::make('storage_used')
                                            ->label('Speicher verwendet')
                                            ->content(fn (?Tenant $record) => $record
                                                ? number_format($record->storage_used_mb / 1024, 2, ',', '.') . ' GB / ' . ($record->storage_limit_gb ?? 'unbegrenzt') . ' GB'
                                                : '0 GB'),

                                        Forms\Components\Placeholder::make('quota_warning')
                                            ->label('Quota-Warnung')
                                            ->content(fn (?Tenant $record) => $record && $record->isNearQuotaLimit()
                                                ? '⚠️ Kontingent fast erreicht!'
                                                : '✅ Ausreichend Kontingent verfügbar'),
                                    ])
                                    ->columns(3),
                            ]),

                        Tabs\Tab::make('⚙️ Einstellungen')
                            ->schema([
                                Forms\Components\Section::make('Feature-Flags')
                                    ->description('Aktivierte Funktionen für diesen Mandanten')
                                    ->schema([
                                        Forms\Components\CheckboxList::make('features')
                                            ->label('Verfügbare Features')
                                            ->options([
                                                'ai_agents' => '🤖 KI-Agenten',
                                                'call_recording' => '🎙️ Anrufaufzeichnung',
                                                'transcription' => '📝 Transkription',
                                                'sentiment_analysis' => '😊 Sentiment-Analyse',
                                                'advanced_routing' => '🔀 Erweiterte Weiterleitung',
                                                'custom_branding' => '🎨 Eigenes Branding',
                                                'api_access' => '🔌 API-Zugang',
                                                'webhook_support' => '🪝 Webhook-Unterstützung',
                                                'white_label' => '🏷️ White Label',
                                                'priority_support' => '⚡ Prioritäts-Support',
                                                'sla_guarantee' => '📊 SLA-Garantie',
                                                'data_export' => '📤 Datenexport',
                                            ])
                                            ->columns(2),
                                    ]),

                                Forms\Components\Section::make('Benutzerdefinierte Einstellungen')
                                    ->description('Spezifische Konfigurationen')
                                    ->schema([
                                        Forms\Components\KeyValue::make('settings')
                                            ->label('Einstellungen')
                                            ->keyLabel('Schlüssel')
                                            ->valueLabel('Wert')
                                            ->addActionLabel('Einstellung hinzufügen')
                                            ->columnSpanFull(),

                                        Forms\Components\KeyValue::make('metadata')
                                            ->label('Metadaten')
                                            ->keyLabel('Schlüssel')
                                            ->valueLabel('Wert')
                                            ->addActionLabel('Metadatum hinzufügen')
                                            ->helperText('Zusätzliche Informationen')
                                            ->columnSpanFull(),
                                    ]),

                                Forms\Components\Section::make('Compliance & Datenschutz')
                                    ->description('Regulatorische Einstellungen')
                                    ->schema([
                                        Forms\Components\Select::make('data_location')
                                            ->label('Datenspeicherort')
                                            ->options([
                                                'eu' => '🇪🇺 EU (DSGVO)',
                                                'de' => '🇩🇪 Deutschland',
                                                'ch' => '🇨🇭 Schweiz',
                                                'us' => '🇺🇸 USA',
                                            ])
                                            ->default('de')
                                            ->required(),

                                        Forms\Components\Toggle::make('gdpr_compliant')
                                            ->label('DSGVO-konform')
                                            ->helperText('Datenschutz nach DSGVO')
                                            ->default(true),

                                        Forms\Components\DateTimePicker::make('dpa_signed_at')
                                            ->label('AVV unterschrieben am')
                                            ->displayFormat('d.m.Y H:i'),

                                        Forms\Components\TextInput::make('data_retention_days')
                                            ->label('Datenaufbewahrung (Tage)')
                                            ->numeric()
                                            ->default(90)
                                            ->minValue(30)
                                            ->maxValue(3650)
                                            ->helperText('Wie lange werden Daten aufbewahrt'),
                                    ])
                                    ->columns(2)
                                    ->collapsed(),
                            ]),

                        Tabs\Tab::make('📊 Statistik')
                            ->schema([
                                Forms\Components\Section::make('Nutzungsstatistiken')
                                    ->description('Übersicht über die Nutzung')
                                    ->schema([
                                        Forms\Components\Placeholder::make('total_calls')
                                            ->label('Gesamtanrufe')
                                            ->content(fn (?Tenant $record) => $record
                                                ? number_format($record->total_calls ?? 0, 0, ',', '.')
                                                : '0'),

                                        Forms\Components\Placeholder::make('total_minutes')
                                            ->label('Gesamtminuten')
                                            ->content(fn (?Tenant $record) => $record
                                                ? number_format($record->total_minutes ?? 0, 0, ',', '.')
                                                : '0'),

                                        Forms\Components\Placeholder::make('total_cost')
                                            ->label('Gesamtkosten')
                                            ->content(fn (?Tenant $record) => $record
                                                ? '€ ' . number_format($record->total_cost ?? 0, 2, ',', '.')
                                                : '€ 0,00'),

                                        Forms\Components\Placeholder::make('avg_call_duration')
                                            ->label('Ø Anrufdauer')
                                            ->content(fn (?Tenant $record) => $record && $record->avg_call_duration
                                                ? gmdate('i:s', $record->avg_call_duration)
                                                : '00:00'),

                                        Forms\Components\Placeholder::make('last_activity')
                                            ->label('Letzte Aktivität')
                                            ->content(fn (?Tenant $record) => $record && $record->last_activity_at
                                                ? $record->last_activity_at->format('d.m.Y H:i')
                                                : 'Noch keine'),

                                        Forms\Components\Placeholder::make('created_at')
                                            ->label('Erstellt am')
                                            ->content(fn (?Tenant $record) => $record
                                                ? $record->created_at->format('d.m.Y H:i')
                                                : '-'),

                                        Forms\Components\Placeholder::make('user_growth')
                                            ->label('Benutzerwachstum')
                                            ->content(fn (?Tenant $record) => $record && $record->getUserGrowthPercentage()
                                                ? ($record->getUserGrowthPercentage() > 0 ? '📈 ' : '📉 ') .
                                                  number_format(abs($record->getUserGrowthPercentage()), 1, ',', '.') . '% diesen Monat'
                                                : 'Keine Daten'),

                                        Forms\Components\Placeholder::make('health_score')
                                            ->label('Gesundheitswert')
                                            ->content(fn (?Tenant $record) => $record
                                                ? $record->getHealthScore() . ' / 100'
                                                : '100 / 100'),
                                    ])
                                    ->columns(4),

                                Forms\Components\Section::make('Monatliche Übersicht')
                                    ->description('Statistiken für den aktuellen Monat')
                                    ->schema([
                                        Forms\Components\Placeholder::make('monthly_calls')
                                            ->label('Anrufe diesen Monat')
                                            ->content(fn (?Tenant $record) => $record
                                                ? number_format($record->getMonthlyCallCount(), 0, ',', '.')
                                                : '0'),

                                        Forms\Components\Placeholder::make('monthly_revenue')
                                            ->label('Umsatz diesen Monat')
                                            ->content(fn (?Tenant $record) => $record
                                                ? '€ ' . number_format($record->getMonthlyRevenue(), 2, ',', '.')
                                                : '€ 0,00'),

                                        Forms\Components\Placeholder::make('monthly_costs')
                                            ->label('Kosten diesen Monat')
                                            ->content(fn (?Tenant $record) => $record
                                                ? '€ ' . number_format($record->getMonthlyCosts(), 2, ',', '.')
                                                : '€ 0,00'),

                                        Forms\Components\Placeholder::make('monthly_margin')
                                            ->label('Marge diesen Monat')
                                            ->content(fn (?Tenant $record) => $record
                                                ? number_format($record->getMonthlyMargin(), 1, ',', '.') . '%'
                                                : '0%'),
                                    ])
                                    ->columns(4)
                                    ->collapsed(),
                            ]),
                    ])
                    ->columnSpanFull()
                    ->persistTabInQueryString(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('name')
                    ->label('Mandantenname')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->icon('heroicon-m-building-office-2')
                    ->description(fn ($record) => $record->slug),

                Tables\Columns\BadgeColumn::make('type')
                    ->label('Typ')
                    ->colors([
                        'warning' => 'master',
                        'primary' => 'standard',
                        'info' => 'trial',
                        'secondary' => 'demo',
                        'success' => 'partner',
                    ])
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'master' => '👑 Hauptmandant',
                        'standard' => '🏢 Standard',
                        'trial' => '🧪 Testversion',
                        'demo' => '🎮 Demo',
                        'partner' => '🤝 Partner',
                        'reseller' => '💼 Mandant',
                        default => ucfirst($state),
                    }),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->getStateUsing(function (Tenant $record) {
                        if (!$record->is_active) {
                            return 'Inaktiv';
                        }
                        if ($record->balance_cents < 0) {
                            return 'Überzogen';
                        }
                        if ($record->balance_cents < 1000) {
                            return 'Niedrig';
                        }
                        return 'Aktiv';
                    })
                    ->colors([
                        'success' => fn ($state) => $state === 'Aktiv',
                        'warning' => fn ($state) => $state === 'Niedrig',
                        'danger' => fn ($state) => in_array($state, ['Überzogen', 'Inaktiv']),
                    ])
                    ->icons([
                        'Aktiv' => 'heroicon-m-check-circle',
                        'Niedrig' => 'heroicon-m-exclamation-triangle',
                        'Überzogen' => 'heroicon-m-x-circle',
                        'Inaktiv' => 'heroicon-m-pause',
                    ]),

                Tables\Columns\TextColumn::make('balance_cents')
                    ->label('Guthaben')
                    ->formatStateUsing(fn ($state) => '€ ' . number_format($state / 100, 2, ',', '.'))
                    ->color(fn ($state) => $state < 0 ? 'danger' : ($state < 1000 ? 'warning' : 'success'))
                    ->sortable()
                    ->icon('heroicon-m-currency-euro'),

                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Aktiv')
                    ->onIcon('heroicon-m-check-circle')
                    ->offIcon('heroicon-m-x-circle')
                    ->onColor('success')
                    ->offColor('danger'),

                Tables\Columns\IconColumn::make('is_verified')
                    ->label('Verifiziert')
                    ->boolean()
                    ->trueIcon('heroicon-o-shield-check')
                    ->falseIcon('heroicon-o-shield-exclamation')
                    ->trueColor('success')
                    ->falseColor('warning')
                    ->tooltip(fn (bool $state) => $state ? 'Verifiziert' : 'Nicht verifiziert'),

                Tables\Columns\TextColumn::make('users_count')
                    ->label('Benutzer')
                    ->counts('users')
                    ->badge()
                    ->color('info')
                    ->icon('heroicon-m-users')
                    ->sortable(),

                Tables\Columns\TextColumn::make('pricing_plan')
                    ->label('Tarif')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'starter' => '🚀 Starter',
                        'professional' => '💼 Professional',
                        'business' => '🏢 Business',
                        'enterprise' => '🏭 Enterprise',
                        'custom' => '⚙️ Individuell',
                        default => ucfirst($state),
                    })
                    ->color('primary'),

                Tables\Columns\TextColumn::make('calcom_team_slug')
                    ->label('Cal.com Team')
                    ->searchable()
                    ->toggleable()
                    ->icon('heroicon-m-calendar'),

                Tables\Columns\TextColumn::make('last_activity_at')
                    ->label('Letzte Aktivität')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->since()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt')
                    ->dateTime('d.m.Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Typ')
                    ->multiple()
                    ->options([
                        'master' => '👑 Hauptmandant',
                        'standard' => '🏢 Standard',
                        'trial' => '🧪 Testversion',
                        'demo' => '🎮 Demo',
                        'partner' => '🤝 Partner',
                        'reseller' => '💼 Mandant',
                    ]),

                Tables\Filters\SelectFilter::make('pricing_plan')
                    ->label('Tarif')
                    ->options([
                        'starter' => '🚀 Starter',
                        'professional' => '💼 Professional',
                        'business' => '🏢 Business',
                        'enterprise' => '🏭 Enterprise',
                        'custom' => '⚙️ Individuell',
                    ]),

                Tables\Filters\Filter::make('active_only')
                    ->label('Nur aktive')
                    ->query(fn ($query) => $query->where('is_active', true))
                    ->toggle()
                    ->default(),

                Tables\Filters\Filter::make('verified_only')
                    ->label('Nur verifizierte')
                    ->query(fn ($query) => $query->where('is_verified', true))
                    ->toggle(),

                Tables\Filters\Filter::make('low_balance')
                    ->label('Niedriges Guthaben')
                    ->query(fn ($query) => $query->where('balance_cents', '<', 1000))
                    ->toggle(),

                Tables\Filters\Filter::make('negative_balance')
                    ->label('Negativer Saldo')
                    ->query(fn ($query) => $query->where('balance_cents', '<', 0))
                    ->toggle(),

                Tables\Filters\Filter::make('with_calcom')
                    ->label('Mit Cal.com')
                    ->query(fn ($query) => $query->whereNotNull('calcom_team_slug'))
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->iconButton()
                    ->tooltip('Details anzeigen'),

                Tables\Actions\EditAction::make()
                    ->iconButton()
                    ->tooltip('Bearbeiten'),

                Tables\Actions\Action::make('impersonate')
                    ->label('Impersonieren')
                    ->icon('heroicon-m-user-circle')
                    ->color('warning')
                    ->tooltip('Als Mandant anmelden')
                    ->requiresConfirmation()
                    ->modalHeading('Mandant impersonieren')
                    ->modalDescription(fn (Tenant $record) => "Als Mandant '{$record->name}' anmelden?")
                    ->modalSubmitActionLabel('Impersonieren')
                    ->action(function (Tenant $record) {
                        // Impersonation logic would go here
                        session()->put('impersonated_tenant_id', $record->id);

                        Notification::make()
                            ->title('Mandant impersoniert')
                            ->body("Sie sind jetzt als '{$record->name}' angemeldet.")
                            ->warning()
                            ->persistent()
                            ->send();
                    })
                    ->visible(fn () => auth()->user()->hasRole('super-admin')),

                Tables\Actions\Action::make('add_balance')
                    ->label('Aufladen')
                    ->icon('heroicon-m-plus-circle')
                    ->color('success')
                    ->tooltip('Guthaben aufladen')
                    ->form([
                        Forms\Components\TextInput::make('amount')
                            ->label('Betrag (EUR)')
                            ->numeric()
                            ->required()
                            ->minValue(10)
                            ->maxValue(10000)
                            ->default(100)
                            ->prefix('€'),
                    ])
                    ->action(function (Tenant $record, array $data) {
                        $record->balance_cents += $data['amount'] * 100;
                        $record->save();

                        Notification::make()
                            ->title('Guthaben aufgeladen')
                            ->body("€ {$data['amount']} wurden dem Mandanten gutgeschrieben.")
                            ->success()
                            ->send();
                    }),

                Tables\Actions\DeleteAction::make()
                    ->iconButton()
                    ->tooltip('Löschen')
                    ->visible(fn (Tenant $record) => $record->type !== 'master'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Aktivieren')
                        ->icon('heroicon-m-check')
                        ->color('success')
                        ->action(fn ($records) => $records->each->update(['is_active' => true]))
                        ->deselectRecordsAfterCompletion()
                        ->requiresConfirmation(),

                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deaktivieren')
                        ->icon('heroicon-m-x-mark')
                        ->color('warning')
                        ->action(fn ($records) => $records->each->update(['is_active' => false]))
                        ->deselectRecordsAfterCompletion()
                        ->requiresConfirmation(),

                    Tables\Actions\BulkAction::make('verify')
                        ->label('Verifizieren')
                        ->icon('heroicon-m-shield-check')
                        ->color('success')
                        ->action(fn ($records) => $records->each->update(['is_verified' => true]))
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('export')
                        ->label('Exportieren')
                        ->icon('heroicon-m-arrow-down-tray')
                        ->color('info')
                        ->action(function ($records) {
                            $csv = "ID,Name,Slug,Typ,Guthaben,Status,Erstellt\n";

                            foreach ($records as $record) {
                                $csv .= sprintf(
                                    "%s,%s,%s,%s,%s,%s,%s\n",
                                    $record->id,
                                    $record->name,
                                    $record->slug,
                                    $record->type,
                                    number_format($record->balance_cents / 100, 2, '.', ''),
                                    $record->is_active ? 'Aktiv' : 'Inaktiv',
                                    $record->created_at->format('Y-m-d H:i:s')
                                );
                            }

                            return response()->streamDownload(function () use ($csv) {
                                echo $csv;
                            }, 'mandanten-export-' . now()->format('Y-m-d-His') . '.csv');
                        })
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()->hasRole('super-admin')),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('30s')
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->emptyStateHeading('Keine Mandanten vorhanden')
            ->emptyStateDescription('Erstellen Sie Ihren ersten Mandanten')
            ->emptyStateIcon('heroicon-o-building-office-2')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Mandant erstellen')
                    ->icon('heroicon-m-plus'),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Mandanten-Informationen')
                    ->schema([
                        Split::make([
                            Section::make([
                                TextEntry::make('name')
                                    ->label('Mandantenname')
                                    ->weight('bold')
                                    ->size('lg'),

                                TextEntry::make('slug')
                                    ->label('Slug')
                                    
                                    ->icon('heroicon-m-link'),

                                TextEntry::make('type')
                                    ->label('Typ')
                                    ->badge()
                                    ->formatStateUsing(fn (string $state): string => match($state) {
                                        'master' => '👑 Hauptmandant',
                                        'standard' => '🏢 Standard',
                                        'trial' => '🧪 Testversion',
                                        'demo' => '🎮 Demo',
                                        'partner' => '🤝 Partner',
                                        'reseller' => '💼 Mandant',
                                        default => ucfirst($state),
                                    }),

                                TextEntry::make('is_active')
                                    ->label('Status')
                                    ->badge()
                                    ->color(fn (bool $state): string => $state ? 'success' : 'danger')
                                    ->formatStateUsing(fn (bool $state): string => $state ? 'Aktiv' : 'Inaktiv'),
                            ])->grow(false),

                            Section::make([
                                TextEntry::make('balance_cents')
                                    ->label('Guthaben')
                                    ->formatStateUsing(fn ($state) => '€ ' . number_format($state / 100, 2, ',', '.'))
                                    ->color(fn ($state) => $state < 0 ? 'danger' : ($state < 1000 ? 'warning' : 'success'))
                                    ->weight('bold'),

                                TextEntry::make('pricing_plan')
                                    ->label('Tarif')
                                    ->formatStateUsing(fn (string $state): string => match($state) {
                                        'starter' => '🚀 Starter',
                                        'professional' => '💼 Professional',
                                        'business' => '🏢 Business',
                                        'enterprise' => '🏭 Enterprise',
                                        'custom' => '⚙️ Individuell',
                                        default => ucfirst($state),
                                    }),

                                TextEntry::make('is_verified')
                                    ->label('Verifizierung')
                                    ->badge()
                                    ->color(fn (bool $state): string => $state ? 'success' : 'warning')
                                    ->formatStateUsing(fn (bool $state): string => $state ? 'Verifiziert' : 'Nicht verifiziert'),

                                TextEntry::make('last_activity_at')
                                    ->label('Letzte Aktivität')
                                    ->dateTime('d.m.Y H:i')
                                    ->since(),
                            ]),
                        ])->from('md'),
                    ]),

                Section::make('API & Integration')
                    ->schema([
                        TextEntry::make('api_key')
                            ->label('API-Schlüssel')
                            
                            ->fontFamily('mono')
                            ->formatStateUsing(fn ($state) => $state ? substr($state, 0, 8) . '...' . substr($state, -8) : '-'),

                        TextEntry::make('calcom_team_slug')
                            ->label('Cal.com Team')
                            ->placeholder('Nicht konfiguriert')
                            ->icon('heroicon-m-calendar'),

                        TextEntry::make('webhook_url')
                            ->label('Webhook URL')
                            ->placeholder('Nicht konfiguriert')
                            ,

                        TextEntry::make('data_location')
                            ->label('Datenspeicherort')
                            ->badge()
                            ->formatStateUsing(fn ($state): string => match($state) {
                                'eu' => '🇪🇺 EU',
                                'de' => '🇩🇪 Deutschland',
                                'ch' => '🇨🇭 Schweiz',
                                'us' => '🇺🇸 USA',
                                default => $state ?? 'Unbekannt',
                            }),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Section::make('Limits & Nutzung')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('users_count')
                                    ->label('Benutzer')
                                    ->state(fn (Tenant $record) =>
                                        $record->users()->count() . ' / ' . ($record->max_users ?? 'unbegrenzt')),

                                TextEntry::make('max_agents')
                                    ->label('Max. Agenten')
                                    ->default('unbegrenzt'),

                                TextEntry::make('monthly_minutes_limit')
                                    ->label('Monatliche Minuten')
                                    ->formatStateUsing(fn ($state) =>
                                        $state ? number_format($state, 0, ',', '.') : 'unbegrenzt'),

                                TextEntry::make('total_calls')
                                    ->label('Gesamtanrufe')
                                    ->default(0)
                                    ->formatStateUsing(fn ($state) => number_format($state ?? 0, 0, ',', '.')),

                                TextEntry::make('total_minutes')
                                    ->label('Gesamtminuten')
                                    ->default(0)
                                    ->formatStateUsing(fn ($state) => number_format($state ?? 0, 0, ',', '.')),

                                TextEntry::make('storage_limit_gb')
                                    ->label('Speicher (GB)')
                                    ->formatStateUsing(fn ($state) =>
                                        $state ? $state . ' GB' : 'unbegrenzt'),
                            ]),
                    ])
                    ->collapsible(),

                Section::make('Einstellungen')
                    ->schema([
                        KeyValueEntry::make('settings')
                            ->label('Benutzerdefinierte Einstellungen')
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // RelationManagers could be added here
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTenants::route('/'),
            'create' => Pages\CreateTenant::route('/create'),
            'view' => Pages\ViewTenant::route('/{record}'),
            'edit' => Pages\EditTenant::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withCount(['users']);
    }
}