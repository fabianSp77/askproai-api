<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RetellAgentResource\Pages;
use App\Models\RetellAgent;
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
use Filament\Infolists\Components\Split;
use Filament\Infolists\Components\Grid;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;

class RetellAgentResource extends Resource
{
    protected static ?string $model = RetellAgent::class;

    protected static ?string $navigationIcon = 'heroicon-o-cpu-chip';

    protected static ?string $navigationLabel = 'KI-Agenten';

    protected static ?string $modelLabel = 'KI-Agent';

    protected static ?string $pluralModelLabel = 'KI-Agenten';

    protected static ?string $navigationGroup = 'System';

    protected static ?int $navigationSort = 6;

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
                Tabs::make('KI-Agent Verwaltung')
                    ->tabs([
                        Tabs\Tab::make('🤖 Grunddaten')
                            ->schema([
                                Forms\Components\Section::make('Agent-Informationen')
                                    ->description('Grundlegende Einstellungen des KI-Agenten')
                                    ->schema([
                                        Forms\Components\TextInput::make('name')
                                            ->label('Agent-Name')
                                            ->required()
                                            ->maxLength(255)
                                            ->placeholder('z.B. Support Agent, Sales Assistant')
                                            ->helperText('Eindeutiger Name für diesen Agenten')
                                            ->prefixIcon('heroicon-m-identification')
                                            ->reactive()
                                            ->afterStateUpdated(fn ($state, callable $set) =>
                                                $set('slug', \Illuminate\Support\Str::slug($state))
                                            ),

                                        Forms\Components\TextInput::make('slug')
                                            ->label('Slug')
                                            ->maxLength(255)
                                            ->disabled()
                                            ->dehydrated()
                                            ->unique(ignoreRecord: true),

                                        Forms\Components\TextInput::make('agent_id')
                                            ->label('Retell Agent ID')
                                            ->required()
                                            ->maxLength(255)
                                            ->placeholder('agent_xxxxxxxxxxxxx')
                                            ->helperText('Eindeutige ID von Retell AI')
                                            
                                            ->prefixIcon('heroicon-m-key'),

                                        Forms\Components\TextInput::make('version')
                                            ->label('Version')
                                            ->maxLength(20)
                                            ->placeholder('v1.0.0')
                                            ->helperText('Versionsnummer des Agenten')
                                            ->default('v1.0.0'),

                                        Forms\Components\Select::make('company_id')
                                            ->label('Firma')
                                            ->relationship('company', 'name')
                                            ->required()
                                            ->searchable()
                                            ->preload()
                                            ->createOptionForm([
                                                Forms\Components\TextInput::make('name')
                                                    ->label('Firmenname')
                                                    ->required(),
                                            ])
                                            ->helperText('Firma, der dieser Agent zugeordnet ist'),

                                        Forms\Components\Select::make('type')
                                            ->label('Agent-Typ')
                                            ->options([
                                                'support' => '🆘 Support',
                                                'sales' => '💼 Vertrieb',
                                                'booking' => '📅 Terminbuchung',
                                                'survey' => '📊 Umfrage',
                                                'receptionist' => '👋 Empfang',
                                                'technical' => '🔧 Technisch',
                                                'general' => '💬 Allgemein',
                                            ])
                                            ->default('general')
                                            ->required()
                                            ->native(false),

                                        Forms\Components\Textarea::make('description')
                                            ->label('Beschreibung')
                                            ->rows(3)
                                            ->maxLength(1000)
                                            ->placeholder('Beschreiben Sie die Hauptfunktionen dieses Agenten')
                                            ->columnSpanFull(),

                                        Forms\Components\Toggle::make('is_active')
                                            ->label('Aktiv')
                                            ->helperText('Agent ist aktiviert und kann Anrufe entgegennehmen')
                                            ->default(true)
                                            ->inline(),

                                        Forms\Components\Toggle::make('is_production')
                                            ->label('Produktiv')
                                            ->helperText('Agent ist im Produktionsmodus (nicht Test)')
                                            ->default(false)
                                            ->inline(),
                                    ])
                                    ->columns(2),

                                Forms\Components\Section::make('Einsatzbereich')
                                    ->description('Wo und wie wird dieser Agent eingesetzt')
                                    ->schema([
                                        Forms\Components\Select::make('language')
                                            ->label('Primäre Sprache')
                                            ->options([
                                                'de' => '🇩🇪 Deutsch',
                                                'en' => '🇬🇧 Englisch',
                                                'fr' => '🇫🇷 Französisch',
                                                'es' => '🇪🇸 Spanisch',
                                                'it' => '🇮🇹 Italienisch',
                                                'multi' => '🌍 Mehrsprachig',
                                            ])
                                            ->default('de')
                                            ->required(),

                                        Forms\Components\Select::make('availability')
                                            ->label('Verfügbarkeit')
                                            ->options([
                                                '24/7' => '🔄 24/7 Durchgehend',
                                                'business' => '💼 Geschäftszeiten',
                                                'extended' => '🌅 Erweiterte Zeiten',
                                                'custom' => '⚙️ Benutzerdefiniert',
                                            ])
                                            ->default('business'),

                                        Forms\Components\TextInput::make('max_concurrent_calls')
                                            ->label('Max. gleichzeitige Anrufe')
                                            ->numeric()
                                            ->minValue(1)
                                            ->maxValue(100)
                                            ->default(10)
                                            ->helperText('Maximale Anzahl gleichzeitiger Gespräche'),

                                        Forms\Components\TextInput::make('priority')
                                            ->label('Priorität')
                                            ->numeric()
                                            ->minValue(1)
                                            ->maxValue(100)
                                            ->default(50)
                                            ->helperText('Höhere Werte = höhere Priorität bei Routing'),
                                    ])
                                    ->columns(2),
                            ]),

                        Tabs\Tab::make('🧠 Persönlichkeit')
                            ->schema([
                                Forms\Components\Section::make('Agent-Persönlichkeit')
                                    ->description('Definieren Sie die Persönlichkeit und das Verhalten')
                                    ->schema([
                                        Forms\Components\TextInput::make('persona_name')
                                            ->label('Persona-Name')
                                            ->maxLength(255)
                                            ->placeholder('z.B. Maria, Max, Alex')
                                            ->helperText('Name, mit dem sich der Agent vorstellt'),

                                        Forms\Components\Select::make('voice_type')
                                            ->label('Stimmtyp')
                                            ->options([
                                                'male_professional' => '👔 Männlich Professionell',
                                                'female_professional' => '👩‍💼 Weiblich Professionell',
                                                'male_friendly' => '😊 Männlich Freundlich',
                                                'female_friendly' => '😊 Weiblich Freundlich',
                                                'neutral' => '🎭 Neutral',
                                                'young' => '🎈 Jung & Dynamisch',
                                                'mature' => '🎩 Reif & Erfahren',
                                            ])
                                            ->default('female_professional'),

                                        Forms\Components\Select::make('personality')
                                            ->label('Persönlichkeitstyp')
                                            ->options([
                                                'professional' => '💼 Professionell',
                                                'friendly' => '😊 Freundlich',
                                                'enthusiastic' => '🎉 Enthusiastisch',
                                                'empathetic' => '💝 Empathisch',
                                                'efficient' => '⚡ Effizient',
                                                'casual' => '😎 Locker',
                                                'formal' => '🎩 Formell',
                                            ])
                                            ->default('professional'),

                                        Forms\Components\Select::make('communication_style')
                                            ->label('Kommunikationsstil')
                                            ->options([
                                                'concise' => '📝 Prägnant',
                                                'detailed' => '📚 Ausführlich',
                                                'conversational' => '💬 Gesprächig',
                                                'directive' => '➡️ Direktiv',
                                                'consultative' => '🤝 Beratend',
                                            ])
                                            ->default('conversational'),

                                        Forms\Components\Textarea::make('greeting_message')
                                            ->label('Begrüßungsnachricht')
                                            ->rows(3)
                                            ->placeholder('Guten Tag, mein Name ist [Name]. Wie kann ich Ihnen heute helfen?')
                                            ->helperText('Erste Nachricht beim Anrufbeginn'),

                                        Forms\Components\Textarea::make('closing_message')
                                            ->label('Abschlussnachricht')
                                            ->rows(3)
                                            ->placeholder('Vielen Dank für Ihren Anruf. Einen schönen Tag noch!')
                                            ->helperText('Nachricht beim Gesprächsende'),

                                        Forms\Components\KeyValue::make('personality_traits')
                                            ->label('Persönlichkeitsmerkmale')
                                            ->keyLabel('Merkmal')
                                            ->valueLabel('Beschreibung')
                                            ->addActionLabel('Merkmal hinzufügen')
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(2),

                                Forms\Components\Section::make('Spracheinstellungen')
                                    ->description('Feinabstimmung der Sprachausgabe')
                                    ->schema([
                                        Forms\Components\TextInput::make('speech_rate')
                                            ->label('Sprechgeschwindigkeit')
                                            ->numeric()
                                            ->minValue(0.5)
                                            ->maxValue(2.0)
                                            ->step(0.1)
                                            ->default(1.0)
                                            ->helperText('1.0 = normal, <1 = langsamer, >1 = schneller'),

                                        Forms\Components\TextInput::make('pitch')
                                            ->label('Tonhöhe')
                                            ->numeric()
                                            ->minValue(-20)
                                            ->maxValue(20)
                                            ->default(0)
                                            ->helperText('0 = normal, negativ = tiefer, positiv = höher'),

                                        Forms\Components\TextInput::make('pause_duration')
                                            ->label('Pausendauer (ms)')
                                            ->numeric()
                                            ->minValue(100)
                                            ->maxValue(2000)
                                            ->default(500)
                                            ->helperText('Pause zwischen Sätzen in Millisekunden'),

                                        Forms\Components\Toggle::make('use_fillers')
                                            ->label('Füllwörter verwenden')
                                            ->helperText('Natürliche Füllwörter wie "ähm", "also"')
                                            ->default(false),
                                    ])
                                    ->columns(2)
                                    ->collapsed(),
                            ]),

                        Tabs\Tab::make('⚙️ Konfiguration')
                            ->schema([
                                Forms\Components\Section::make('Retell AI Einstellungen')
                                    ->description('Technische Konfiguration für Retell AI')
                                    ->schema([
                                        Forms\Components\TextInput::make('webhook_url')
                                            ->label('Webhook URL')
                                            ->url()
                                            ->maxLength(500)
                                            ->placeholder('https://api.example.com/webhook/retell')
                                            ->helperText('URL für Retell Callbacks')
                                            ->suffixIcon('heroicon-m-arrow-top-right-on-square'),

                                        Forms\Components\TextInput::make('api_key')
                                            ->label('API Schlüssel')
                                            ->password()
                                            ->maxLength(255)
                                            ->revealable()
                                            ->helperText('Verschlüsselter API-Schlüssel'),

                                        Forms\Components\Select::make('model')
                                            ->label('KI-Modell')
                                            ->options([
                                                'gpt-4' => 'GPT-4 (Beste Qualität)',
                                                'gpt-3.5-turbo' => 'GPT-3.5 Turbo (Schnell)',
                                                'claude-3' => 'Claude 3 (Anthropic)',
                                                'custom' => 'Benutzerdefiniert',
                                            ])
                                            ->default('gpt-4'),

                                        Forms\Components\TextInput::make('temperature')
                                            ->label('Temperatur')
                                            ->numeric()
                                            ->minValue(0)
                                            ->maxValue(2)
                                            ->step(0.1)
                                            ->default(0.7)
                                            ->helperText('Kreativität: 0 = deterministisch, 2 = sehr kreativ'),

                                        Forms\Components\TextInput::make('max_tokens')
                                            ->label('Max. Tokens')
                                            ->numeric()
                                            ->minValue(50)
                                            ->maxValue(4000)
                                            ->default(500)
                                            ->helperText('Maximale Antwortlänge in Tokens'),

                                        Forms\Components\TextInput::make('timeout_seconds')
                                            ->label('Timeout (Sekunden)')
                                            ->numeric()
                                            ->minValue(10)
                                            ->maxValue(300)
                                            ->default(30)
                                            ->helperText('Maximale Antwortzeit'),
                                    ])
                                    ->columns(2),

                                Forms\Components\Section::make('Erweiterte Einstellungen')
                                    ->description('Zusätzliche Konfigurationsoptionen')
                                    ->schema([
                                        Forms\Components\Textarea::make('system_prompt')
                                            ->label('System-Prompt')
                                            ->rows(5)
                                            ->placeholder('Du bist ein freundlicher Kundenservice-Agent...')
                                            ->helperText('Basis-Instruktionen für den Agenten')
                                            ->columnSpanFull(),

                                        Forms\Components\KeyValue::make('settings')
                                            ->label('Zusätzliche Einstellungen')
                                            ->keyLabel('Parameter')
                                            ->valueLabel('Wert')
                                            ->addActionLabel('Einstellung hinzufügen')
                                            ->columnSpanFull(),

                                        Forms\Components\KeyValue::make('integrations')
                                            ->label('Integrationen')
                                            ->keyLabel('Service')
                                            ->valueLabel('Konfiguration')
                                            ->helperText('Externe Service-Integrationen')
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        Tabs\Tab::make('📋 Fähigkeiten')
                            ->schema([
                                Forms\Components\Section::make('Agent-Fähigkeiten')
                                    ->description('Was kann dieser Agent tun?')
                                    ->schema([
                                        Forms\Components\CheckboxList::make('capabilities')
                                            ->label('Grundfähigkeiten')
                                            ->options([
                                                'appointment_booking' => '📅 Termine buchen',
                                                'information_lookup' => '🔍 Informationen nachschlagen',
                                                'order_tracking' => '📦 Bestellungen verfolgen',
                                                'technical_support' => '🔧 Technischer Support',
                                                'sales_inquiry' => '💰 Verkaufsanfragen',
                                                'complaint_handling' => '😤 Beschwerdemanagement',
                                                'survey_collection' => '📊 Umfragen durchführen',
                                                'payment_processing' => '💳 Zahlungen verarbeiten',
                                                'account_management' => '👤 Kontoverwaltung',
                                                'emergency_routing' => '🚨 Notfall-Weiterleitung',
                                            ])
                                            ->columns(2),

                                        Forms\Components\Toggle::make('can_transfer_calls')
                                            ->label('Anrufe weiterleiten')
                                            ->helperText('Agent kann Anrufe an Menschen weiterleiten')
                                            ->default(true),

                                        Forms\Components\Toggle::make('can_send_sms')
                                            ->label('SMS versenden')
                                            ->helperText('Agent kann SMS-Nachrichten versenden')
                                            ->default(false),

                                        Forms\Components\Toggle::make('can_send_email')
                                            ->label('E-Mails versenden')
                                            ->helperText('Agent kann E-Mail-Zusammenfassungen senden')
                                            ->default(false),

                                        Forms\Components\Toggle::make('can_access_calendar')
                                            ->label('Kalenderzugriff')
                                            ->helperText('Agent kann auf Kalender zugreifen')
                                            ->default(false),

                                        Forms\Components\Toggle::make('can_access_crm')
                                            ->label('CRM-Zugriff')
                                            ->helperText('Agent kann auf CRM-Daten zugreifen')
                                            ->default(false),

                                        Forms\Components\Toggle::make('can_process_payments')
                                            ->label('Zahlungen verarbeiten')
                                            ->helperText('Agent kann Zahlungen entgegennehmen')
                                            ->default(false),
                                    ])
                                    ->columns(2),

                                Forms\Components\Section::make('Wissensquellen')
                                    ->description('Datenquellen für den Agenten')
                                    ->schema([
                                        Forms\Components\Repeater::make('knowledge_sources')
                                            ->label('Wissensbasen')
                                            ->schema([
                                                Forms\Components\Select::make('type')
                                                    ->label('Typ')
                                                    ->options([
                                                        'faq' => '❓ FAQ',
                                                        'product_catalog' => '📦 Produktkatalog',
                                                        'price_list' => '💰 Preisliste',
                                                        'documentation' => '📚 Dokumentation',
                                                        'api' => '🔌 API',
                                                        'database' => '🗄️ Datenbank',
                                                    ])
                                                    ->required(),

                                                Forms\Components\TextInput::make('name')
                                                    ->label('Name')
                                                    ->required(),

                                                Forms\Components\TextInput::make('source')
                                                    ->label('Quelle/URL')
                                                    ->required(),

                                                Forms\Components\Toggle::make('is_active')
                                                    ->label('Aktiv')
                                                    ->default(true),
                                            ])
                                            ->columns(4)
                                            ->defaultItems(0)
                                            ->addActionLabel('Wissensquelle hinzufügen')
                                            ->collapsed()
                                            ->collapsible(),
                                    ]),
                            ]),

                        Tabs\Tab::make('📊 Statistik')
                            ->schema([
                                Forms\Components\Section::make('Nutzungsstatistiken')
                                    ->description('Performance und Nutzungsdaten')
                                    ->schema([
                                        Forms\Components\Placeholder::make('total_calls')
                                            ->label('Gesamtanrufe')
                                            ->content(fn (?RetellAgent $record) => $record ? number_format($record->total_calls ?? 0, 0, ',', '.') : '0'),

                                        Forms\Components\Placeholder::make('successful_calls')
                                            ->label('Erfolgreiche Anrufe')
                                            ->content(fn (?RetellAgent $record) => $record ? number_format($record->successful_calls ?? 0, 0, ',', '.') : '0'),

                                        Forms\Components\Placeholder::make('failed_calls')
                                            ->label('Fehlgeschlagene Anrufe')
                                            ->content(fn (?RetellAgent $record) => $record ? number_format($record->failed_calls ?? 0, 0, ',', '.') : '0'),

                                        Forms\Components\Placeholder::make('avg_call_duration')
                                            ->label('Ø Gesprächsdauer')
                                            ->content(fn (?RetellAgent $record) => $record && $record->avg_call_duration
                                                ? gmdate('i:s', $record->avg_call_duration)
                                                : '00:00'),

                                        Forms\Components\Placeholder::make('satisfaction_score')
                                            ->label('Zufriedenheit')
                                            ->content(fn (?RetellAgent $record) => $record && $record->satisfaction_score
                                                ? number_format($record->satisfaction_score, 1, ',', '.') . ' / 5.0'
                                                : 'Keine Daten'),

                                        Forms\Components\Placeholder::make('resolution_rate')
                                            ->label('Lösungsquote')
                                            ->content(fn (?RetellAgent $record) => $record && $record->resolution_rate
                                                ? number_format($record->resolution_rate, 1, ',', '.') . '%'
                                                : 'Keine Daten'),

                                        Forms\Components\Placeholder::make('last_call')
                                            ->label('Letzter Anruf')
                                            ->content(fn (?RetellAgent $record) => $record && $record->last_call_at
                                                ? $record->last_call_at->format('d.m.Y H:i')
                                                : 'Noch nie'),

                                        Forms\Components\Placeholder::make('uptime')
                                            ->label('Verfügbarkeit')
                                            ->content(fn (?RetellAgent $record) => $record && $record->uptime_percentage
                                                ? number_format($record->uptime_percentage, 2, ',', '.') . '%'
                                                : '100%'),
                                    ])
                                    ->columns(4),

                                Forms\Components\Section::make('Kosten')
                                    ->description('Kostenübersicht')
                                    ->schema([
                                        Forms\Components\Placeholder::make('total_cost')
                                            ->label('Gesamtkosten')
                                            ->content(fn (?RetellAgent $record) => $record && $record->total_cost
                                                ? '€ ' . number_format($record->total_cost, 2, ',', '.')
                                                : '€ 0,00'),

                                        Forms\Components\Placeholder::make('cost_per_call')
                                            ->label('Kosten pro Anruf')
                                            ->content(fn (?RetellAgent $record) => $record && $record->cost_per_call
                                                ? '€ ' . number_format($record->cost_per_call, 2, ',', '.')
                                                : '€ 0,00'),

                                        Forms\Components\Placeholder::make('minutes_used')
                                            ->label('Minuten verbraucht')
                                            ->content(fn (?RetellAgent $record) => $record ? number_format($record->minutes_used ?? 0, 0, ',', '.') : '0'),

                                        Forms\Components\Placeholder::make('tokens_used')
                                            ->label('Tokens verbraucht')
                                            ->content(fn (?RetellAgent $record) => $record ? number_format($record->tokens_used ?? 0, 0, ',', '.') : '0'),
                                    ])
                                    ->columns(4),
                            ]),

                        Tabs\Tab::make('🔧 Erweitert')
                            ->schema([
                                Forms\Components\Section::make('Debug & Testing')
                                    ->description('Entwickler-Optionen')
                                    ->schema([
                                        Forms\Components\Toggle::make('debug_mode')
                                            ->label('Debug-Modus')
                                            ->helperText('Erweiterte Protokollierung aktivieren')
                                            ->default(false),

                                        Forms\Components\Toggle::make('test_mode')
                                            ->label('Test-Modus')
                                            ->helperText('Agent im Testmodus (keine echten Aktionen)')
                                            ->default(false),

                                        Forms\Components\TextInput::make('test_phone_number')
                                            ->label('Test-Telefonnummer')
                                            ->tel()
                                            ->placeholder('+49 30 12345678')
                                            ->helperText('Nummer für Testanrufe'),

                                        Forms\Components\Select::make('log_level')
                                            ->label('Log-Level')
                                            ->options([
                                                'error' => '❌ Nur Fehler',
                                                'warning' => '⚠️ Warnungen & Fehler',
                                                'info' => 'ℹ️ Info & höher',
                                                'debug' => '🐛 Alles (Debug)',
                                            ])
                                            ->default('info'),
                                    ])
                                    ->columns(2),

                                Forms\Components\Section::make('Metadaten')
                                    ->description('Zusätzliche Informationen')
                                    ->schema([
                                        Forms\Components\KeyValue::make('metadata')
                                            ->label('Benutzerdefinierte Metadaten')
                                            ->keyLabel('Schlüssel')
                                            ->valueLabel('Wert')
                                            ->addActionLabel('Metadatum hinzufügen')
                                            ->columnSpanFull(),

                                        Forms\Components\Textarea::make('notes')
                                            ->label('Interne Notizen')
                                            ->rows(4)
                                            ->maxLength(2000)
                                            ->placeholder('Notizen für Entwickler und Administratoren')
                                            ->columnSpanFull(),
                                    ]),

                                Forms\Components\Section::make('System-Informationen')
                                    ->description('Automatisch generierte Daten')
                                    ->schema([
                                        Forms\Components\Placeholder::make('id')
                                            ->label('ID')
                                            ->content(fn (?RetellAgent $record) => $record?->id ?? '-'),

                                        Forms\Components\Placeholder::make('created_at')
                                            ->label('Erstellt am')
                                            ->content(fn (?RetellAgent $record) => $record?->created_at?->format('d.m.Y H:i:s') ?? '-'),

                                        Forms\Components\Placeholder::make('updated_at')
                                            ->label('Aktualisiert am')
                                            ->content(fn (?RetellAgent $record) => $record?->updated_at?->format('d.m.Y H:i:s') ?? '-'),

                                        Forms\Components\Placeholder::make('last_sync')
                                            ->label('Letzte Synchronisation')
                                            ->content(fn (?RetellAgent $record) => $record && $record->last_sync_at
                                                ? $record->last_sync_at->format('d.m.Y H:i:s')
                                                : 'Noch nie'),
                                    ])
                                    ->columns(2)
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
                    ->label('Agent-Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->icon('heroicon-m-cpu-chip')
                    ->description(fn ($record) => $record->agent_id),

                Tables\Columns\BadgeColumn::make('type')
                    ->label('Typ')
                    ->colors([
                        'primary' => 'support',
                        'success' => 'sales',
                        'warning' => 'booking',
                        'info' => 'survey',
                        'secondary' => 'general',
                    ])
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'support' => '🆘 Support',
                        'sales' => '💼 Vertrieb',
                        'booking' => '📅 Terminbuchung',
                        'survey' => '📊 Umfrage',
                        'receptionist' => '👋 Empfang',
                        'technical' => '🔧 Technisch',
                        'general' => '💬 Allgemein',
                        default => ucfirst($state),
                    }),

                Tables\Columns\TextColumn::make('company.name')
                    ->label('Firma')
                    ->sortable()
                    ->searchable()
                    ->icon('heroicon-m-building-office'),

                Tables\Columns\BadgeColumn::make('status_badge')
                    ->label('Status')
                    ->getStateUsing(function ($record) {
                        if (!$record->is_active) {
                            return 'Inaktiv';
                        }
                        if ($record->test_mode) {
                            return 'Test';
                        }
                        if ($record->is_production) {
                            return 'Produktiv';
                        }
                        return 'Entwicklung';
                    })
                    ->colors([
                        'success' => fn ($state) => $state === 'Produktiv',
                        'warning' => fn ($state) => $state === 'Test',
                        'info' => fn ($state) => $state === 'Entwicklung',
                        'danger' => fn ($state) => $state === 'Inaktiv',
                    ])
                    ->icons([
                        'Produktiv' => 'heroicon-m-check-circle',
                        'Test' => 'heroicon-m-beaker',
                        'Entwicklung' => 'heroicon-m-code-bracket',
                        'Inaktiv' => 'heroicon-m-x-circle',
                    ]),

                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Aktiv')
                    ->onIcon('heroicon-m-check-circle')
                    ->offIcon('heroicon-m-x-circle')
                    ->onColor('success')
                    ->offColor('danger'),

                Tables\Columns\BadgeColumn::make('language')
                    ->label('Sprache')
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'de' => '🇩🇪 DE',
                        'en' => '🇬🇧 EN',
                        'fr' => '🇫🇷 FR',
                        'es' => '🇪🇸 ES',
                        'it' => '🇮🇹 IT',
                        'multi' => '🌍 Multi',
                        default => strtoupper($state),
                    })
                    ->color('primary'),

                Tables\Columns\TextColumn::make('voice_type')
                    ->label('Stimme')
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'male_professional' => '👔 Männlich',
                        'female_professional' => '👩‍💼 Weiblich',
                        'neutral' => '🎭 Neutral',
                        default => 'Standard',
                    })
                    ->toggleable(),

                Tables\Columns\TextColumn::make('total_calls')
                    ->label('Anrufe')
                    ->formatStateUsing(fn ($state) => number_format($state ?? 0, 0, ',', '.'))
                    ->badge()
                    ->color('info')
                    ->icon('heroicon-m-phone')
                    ->sortable(),

                Tables\Columns\TextColumn::make('satisfaction_score')
                    ->label('Bewertung')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 1, ',', '.') . '⭐' : '-')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('last_call_at')
                    ->label('Letzter Anruf')
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
                        'support' => '🆘 Support',
                        'sales' => '💼 Vertrieb',
                        'booking' => '📅 Terminbuchung',
                        'survey' => '📊 Umfrage',
                        'receptionist' => '👋 Empfang',
                        'technical' => '🔧 Technisch',
                        'general' => '💬 Allgemein',
                    ]),

                Tables\Filters\SelectFilter::make('company_id')
                    ->label('Firma')
                    ->relationship('company', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('language')
                    ->label('Sprache')
                    ->options([
                        'de' => '🇩🇪 Deutsch',
                        'en' => '🇬🇧 Englisch',
                        'fr' => '🇫🇷 Französisch',
                        'es' => '🇪🇸 Spanisch',
                        'multi' => '🌍 Mehrsprachig',
                    ]),

                Tables\Filters\Filter::make('active_only')
                    ->label('Nur aktive')
                    ->query(fn ($query) => $query->where('is_active', true))
                    ->toggle()
                    ->default(),

                Tables\Filters\Filter::make('production_only')
                    ->label('Nur produktiv')
                    ->query(fn ($query) => $query->where('is_production', true))
                    ->toggle(),

                Tables\Filters\Filter::make('test_mode')
                    ->label('Test-Modus')
                    ->query(fn ($query) => $query->where('test_mode', true))
                    ->toggle(),

                Tables\Filters\Filter::make('with_calls')
                    ->label('Mit Anrufen')
                    ->query(fn ($query) => $query->where('total_calls', '>', 0))
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->iconButton()
                    ->tooltip('Details anzeigen'),

                Tables\Actions\EditAction::make()
                    ->iconButton()
                    ->tooltip('Bearbeiten'),

                Tables\Actions\Action::make('test')
                    ->label('Test')
                    ->icon('heroicon-m-play')
                    ->color('success')
                    ->tooltip('Agent testen')
                    ->modalHeading('Agent testen')
                    ->modalDescription(fn (RetellAgent $record) => "Testanruf mit Agent '{$record->name}' durchführen?")
                    ->modalSubmitActionLabel('Testanruf starten')
                    ->action(function (RetellAgent $record) {
                        // Test logic would go here
                        Notification::make()
                            ->title('Test gestartet')
                            ->body("Testanruf mit Agent '{$record->name}' wurde initiiert.")
                            ->success()
                            ->send();
                    })
                    ->visible(fn (RetellAgent $record) => $record->is_active),

                Tables\Actions\Action::make('sync')
                    ->label('Sync')
                    ->icon('heroicon-m-arrow-path')
                    ->color('info')
                    ->tooltip('Mit Retell synchronisieren')
                    ->action(function (RetellAgent $record) {
                        // Sync logic would go here
                        $record->update(['last_sync_at' => now()]);

                        Notification::make()
                            ->title('Synchronisation abgeschlossen')
                            ->body("Agent '{$record->name}' wurde synchronisiert.")
                            ->success()
                            ->send();
                    }),

                Tables\Actions\DeleteAction::make()
                    ->iconButton()
                    ->tooltip('Löschen'),
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

                    Tables\Actions\BulkAction::make('set_production')
                        ->label('Auf produktiv setzen')
                        ->icon('heroicon-m-rocket-launch')
                        ->color('success')
                        ->action(fn ($records) => $records->each->update([
                            'is_production' => true,
                            'test_mode' => false,
                        ]))
                        ->deselectRecordsAfterCompletion()
                        ->requiresConfirmation()
                        ->modalHeading('Auf produktiv setzen')
                        ->modalDescription('Die ausgewählten Agenten werden in den Produktivmodus versetzt.'),

                    Tables\Actions\BulkAction::make('sync_all')
                        ->label('Alle synchronisieren')
                        ->icon('heroicon-m-arrow-path')
                        ->color('info')
                        ->action(function ($records) {
                            $records->each->update(['last_sync_at' => now()]);
                            Notification::make()
                                ->title('Synchronisation abgeschlossen')
                                ->body(count($records) . ' Agenten wurden synchronisiert.')
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('30s')
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->emptyStateHeading('Keine KI-Agenten vorhanden')
            ->emptyStateDescription('Erstellen Sie Ihren ersten KI-Agenten')
            ->emptyStateIcon('heroicon-o-cpu-chip')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('KI-Agent erstellen')
                    ->icon('heroicon-m-plus'),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Agent-Informationen')
                    ->schema([
                        Split::make([
                            Section::make([
                                TextEntry::make('name')
                                    ->label('Agent-Name')
                                    ->weight('bold')
                                    ->size('lg'),

                                TextEntry::make('agent_id')
                                    ->label('Retell Agent ID')
                                    
                                    ->icon('heroicon-m-key'),

                                TextEntry::make('type')
                                    ->label('Typ')
                                    ->badge()
                                    ->formatStateUsing(fn (string $state): string => match($state) {
                                        'support' => '🆘 Support',
                                        'sales' => '💼 Vertrieb',
                                        'booking' => '📅 Terminbuchung',
                                        'general' => '💬 Allgemein',
                                        default => ucfirst($state),
                                    }),

                                TextEntry::make('is_active')
                                    ->label('Status')
                                    ->badge()
                                    ->color(fn (bool $state): string => $state ? 'success' : 'danger')
                                    ->formatStateUsing(fn (bool $state): string => $state ? 'Aktiv' : 'Inaktiv'),
                            ])->grow(false),

                            Section::make([
                                TextEntry::make('company.name')
                                    ->label('Firma')
                                    ->icon('heroicon-m-building-office'),

                                TextEntry::make('language')
                                    ->label('Sprache')
                                    ->formatStateUsing(fn (string $state): string => match($state) {
                                        'de' => '🇩🇪 Deutsch',
                                        'en' => '🇬🇧 Englisch',
                                        'multi' => '🌍 Mehrsprachig',
                                        default => $state,
                                    }),

                                TextEntry::make('voice_type')
                                    ->label('Stimme')
                                    ->formatStateUsing(fn ($state): string => match($state) {
                                        'male_professional' => '👔 Männlich Professionell',
                                        'female_professional' => '👩‍💼 Weiblich Professionell',
                                        'neutral' => '🎭 Neutral',
                                        default => 'Standard',
                                    }),

                                TextEntry::make('personality')
                                    ->label('Persönlichkeit')
                                    ->default('Professional'),
                            ]),
                        ])->from('md'),
                    ]),

                Section::make('Statistiken')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('total_calls')
                                    ->label('Gesamtanrufe')
                                    ->default(0)
                                    ->formatStateUsing(fn ($state) => number_format($state ?? 0, 0, ',', '.')),

                                TextEntry::make('successful_calls')
                                    ->label('Erfolgreiche')
                                    ->default(0)
                                    ->formatStateUsing(fn ($state) => number_format($state ?? 0, 0, ',', '.')),

                                TextEntry::make('avg_call_duration')
                                    ->label('Ø Dauer')
                                    ->formatStateUsing(fn ($state) => $state ? gmdate('i:s', $state) : '00:00'),

                                TextEntry::make('satisfaction_score')
                                    ->label('Zufriedenheit')
                                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 1, ',', '.') . ' / 5.0' : 'Keine Daten'),
                            ]),
                    ])
                    ->collapsible(),

                Section::make('Konfiguration')
                    ->schema([
                        TextEntry::make('model')
                            ->label('KI-Modell')
                            ->default('GPT-4'),

                        TextEntry::make('temperature')
                            ->label('Temperatur')
                            ->default('0.7'),

                        TextEntry::make('max_concurrent_calls')
                            ->label('Max. gleichzeitige Anrufe')
                            ->default('10'),

                        TextEntry::make('webhook_url')
                            ->label('Webhook URL')
                            
                            ->placeholder('Nicht konfiguriert'),

                        KeyValueEntry::make('settings')
                            ->label('Zusätzliche Einstellungen')
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
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
            'index' => Pages\ListRetellAgents::route('/'),
            'create' => Pages\CreateRetellAgent::route('/create'),
            'view' => Pages\ViewRetellAgent::route('/{record}'),
            'edit' => Pages\EditRetellAgent::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['company']);
    }
}