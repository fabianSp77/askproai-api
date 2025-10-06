<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PhoneNumberResource\Pages;
use App\Filament\Resources\PhoneNumberResource\RelationManagers;
use App\Models\PhoneNumber;
use App\Models\Company;
use App\Models\Branch;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Tabs;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Split;
use Filament\Infolists\Components\Grid;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use libphonenumber\PhoneNumberUtil;
use libphonenumber\PhoneNumberFormat;

class PhoneNumberResource extends Resource
{
    protected static ?string $model = PhoneNumber::class;

    protected static ?string $navigationIcon = 'heroicon-o-phone';

    protected static ?string $navigationLabel = 'Telefonnummern';

    protected static ?string $modelLabel = 'Telefonnummer';

    protected static ?string $pluralModelLabel = 'Telefonnummern';

    protected static ?string $navigationGroup = 'System';

    protected static ?int $navigationSort = 5;

    protected static ?string $recordTitleAttribute = 'number';

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
                Tabs::make('Telefonnummer-Verwaltung')
                    ->tabs([
                        Tabs\Tab::make('📞 Grunddaten')
                            ->schema([
                                Forms\Components\Section::make('Telefonnummer-Informationen')
                                    ->description('Grundlegende Informationen zur Telefonnummer')
                                    ->schema([
                                        Forms\Components\TextInput::make('number')
                                            ->label('Telefonnummer')
                                            ->required()
                                            ->tel()
                                            ->maxLength(255)
                                            ->placeholder('+49 30 12345678')
                                            ->helperText('Internationale Format mit Ländervorwahl')
                                            ->prefixIcon('heroicon-m-phone')
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                                if ($state) {
                                                    try {
                                                        $phoneUtil = PhoneNumberUtil::getInstance();
                                                        $numberProto = $phoneUtil->parse($state, 'DE');
                                                        $set('country_code', '+' . $numberProto->getCountryCode());
                                                    } catch (\Exception $e) {
                                                        // Ignore parsing errors
                                                    }
                                                }
                                            }),

                                        Forms\Components\Select::make('type')
                                            ->label('Typ')
                                            ->options([
                                                'direct' => '📱 Direktwahl',
                                                'hotline' => '📞 Hotline',
                                                'support' => '🆘 Support',
                                                'sales' => '💼 Vertrieb',
                                                'mobile' => '📲 Mobil',
                                                'fax' => '📠 Fax',
                                                'emergency' => '🚨 Notfall',
                                            ])
                                            ->default('direct')
                                            ->required()
                                            ->native(false),

                                        Forms\Components\TextInput::make('label')
                                            ->label('Bezeichnung')
                                            ->maxLength(255)
                                            ->placeholder('z.B. Hauptnummer, Support-Hotline')
                                            ->helperText('Kurze Beschreibung für diese Nummer'),

                                        Forms\Components\Textarea::make('description')
                                            ->label('Beschreibung')
                                            ->rows(3)
                                            ->maxLength(500)
                                            ->placeholder('Zusätzliche Informationen zur Telefonnummer')
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(2),

                                Forms\Components\Section::make('Zuordnung')
                                    ->description('Firma und Filiale zuordnen')
                                    ->schema([
                                        Forms\Components\Select::make('company_id')
                                            ->label('Firma')
                                            ->relationship('company', 'name')
                                            ->searchable()
                                            ->required()
                                            ->preload()
                                            ->createOptionForm([
                                                Forms\Components\TextInput::make('name')
                                                    ->label('Firmenname')
                                                    ->required(),
                                            ])
                                            ->reactive()
                                            ->afterStateUpdated(fn (callable $set) => $set('branch_id', null)),

                                        Forms\Components\Select::make('branch_id')
                                            ->label('Filiale')
                                            ->relationship('branch', 'name', function ($query, callable $get) {
                                                $companyId = $get('company_id');
                                                if ($companyId) {
                                                    return $query->where('company_id', $companyId);
                                                }
                                                return $query;
                                            })
                                            ->searchable()
                                            ->preload()
                                            ->placeholder('Optional - Filiale auswählen')
                                            ->helperText('Nur Filialen der ausgewählten Firma'),

                                        Forms\Components\Toggle::make('is_primary')
                                            ->label('Hauptnummer')
                                            ->helperText('Als primäre Kontaktnummer markieren')
                                            ->default(false),

                                        Forms\Components\Toggle::make('is_active')
                                            ->label('Aktiv')
                                            ->helperText('Nummer ist aktiv und erreichbar')
                                            ->default(true),
                                    ])
                                    ->columns(2),
                            ]),

                        Tabs\Tab::make('💬 Kommunikation')
                            ->schema([
                                Forms\Components\Section::make('Kommunikationskanäle')
                                    ->description('Verfügbare Kommunikationsmöglichkeiten')
                                    ->schema([
                                        Forms\Components\Toggle::make('voice_enabled')
                                            ->label('Sprachanrufe')
                                            ->helperText('Normale Telefonie aktiviert')
                                            ->default(true),

                                        Forms\Components\Toggle::make('sms_enabled')
                                            ->label('SMS')
                                            ->helperText('SMS-Empfang und -Versand möglich')
                                            ->default(false)
                                            ->reactive(),

                                        Forms\Components\Toggle::make('whatsapp_enabled')
                                            ->label('WhatsApp Business')
                                            ->helperText('WhatsApp Business API aktiviert')
                                            ->default(false)
                                            ->reactive(),

                                        Forms\Components\Toggle::make('mms_enabled')
                                            ->label('MMS')
                                            ->helperText('Multimedia-Nachrichten möglich')
                                            ->default(false),

                                        Forms\Components\TextInput::make('whatsapp_business_id')
                                            ->label('WhatsApp Business ID')
                                            ->maxLength(255)
                                            ->visible(fn (callable $get) => $get('whatsapp_enabled'))
                                            ->placeholder('WABA ID'),

                                        Forms\Components\TextInput::make('sms_sender_name')
                                            ->label('SMS Absendername')
                                            ->maxLength(11)
                                            ->visible(fn (callable $get) => $get('sms_enabled'))
                                            ->placeholder('Max. 11 Zeichen'),
                                    ])
                                    ->columns(2),

                                Forms\Components\Section::make('Anrufweiterleitung')
                                    ->description('Weiterleitungsoptionen konfigurieren')
                                    ->schema([
                                        Forms\Components\Toggle::make('forwarding_enabled')
                                            ->label('Weiterleitung aktiv')
                                            ->default(false)
                                            ->reactive(),

                                        Forms\Components\TextInput::make('forward_to')
                                            ->label('Weiterleiten an')
                                            ->tel()
                                            ->visible(fn (callable $get) => $get('forwarding_enabled'))
                                            ->placeholder('+49 30 98765432'),

                                        Forms\Components\Select::make('forwarding_type')
                                            ->label('Weiterleitungstyp')
                                            ->options([
                                                'always' => 'Immer',
                                                'busy' => 'Bei besetzt',
                                                'no_answer' => 'Bei keine Antwort',
                                                'unavailable' => 'Bei nicht erreichbar',
                                            ])
                                            ->visible(fn (callable $get) => $get('forwarding_enabled')),

                                        Forms\Components\TextInput::make('ring_timeout')
                                            ->label('Klingelzeit (Sekunden)')
                                            ->numeric()
                                            ->default(20)
                                            ->minValue(5)
                                            ->maxValue(60)
                                            ->visible(fn (callable $get) => $get('forwarding_enabled') && $get('forwarding_type') === 'no_answer'),
                                    ])
                                    ->columns(2)
                                    ->collapsed(),
                            ]),

                        Tabs\Tab::make('🤖 AI Integration')
                            ->schema([
                                Forms\Components\Section::make('Retell AI Konfiguration')
                                    ->description('KI-Agenten und Automatisierung')
                                    ->schema([
                                        Forms\Components\TextInput::make('retell_agent_id')
                                            ->label('Retell Agent ID')
                                            ->maxLength(255)
                                            ->placeholder('agent_xxxxxxxxxxxxx')
                                            ->helperText('Retell Agent ID für eingehende Anrufe'),

                                        Forms\Components\TextInput::make('retell_phone_id')
                                            ->label('Retell Phone ID')
                                            ->maxLength(255)
                                            ->placeholder('phone_xxxxxxxxxxxxx')
                                            ->helperText('Eindeutige ID von Retell AI'),

                                        Forms\Components\TextInput::make('retell_agent_version')
                                            ->label('Agent Version')
                                            ->maxLength(255)
                                            ->placeholder('v1.0.0')
                                            ->helperText('Version des verwendeten Agenten'),

                                        Forms\Components\TextInput::make('agent_id')
                                            ->label('Legacy Agent ID')
                                            ->maxLength(255)
                                            ->placeholder('Alte Agent-Referenz')
                                            ->helperText('Für Rückwärtskompatibilität'),

                                        Forms\Components\Select::make('ai_provider')
                                            ->label('KI-Anbieter')
                                            ->options([
                                                'retell' => '🤖 Retell AI',
                                                'twilio' => '📞 Twilio Autopilot',
                                                'dialogflow' => '🗣️ Google Dialogflow',
                                                'custom' => '⚙️ Eigene Lösung',
                                                'none' => '❌ Keine KI',
                                            ])
                                            ->default('none'),

                                        Forms\Components\Toggle::make('ai_greeting_enabled')
                                            ->label('KI-Begrüßung')
                                            ->helperText('Automatische Begrüßung durch KI')
                                            ->default(false),

                                        Forms\Components\Textarea::make('ai_greeting_text')
                                            ->label('Begrüßungstext')
                                            ->rows(3)
                                            ->visible(fn (callable $get) => $get('ai_greeting_enabled'))
                                            ->placeholder('Guten Tag, wie kann ich Ihnen helfen?'),

                                        Forms\Components\Select::make('ai_voice')
                                            ->label('KI-Stimme')
                                            ->options([
                                                'male_de' => '🎙️ Männlich (Deutsch)',
                                                'female_de' => '🎙️ Weiblich (Deutsch)',
                                                'neutral_de' => '🎙️ Neutral (Deutsch)',
                                                'male_en' => '🎙️ Male (English)',
                                                'female_en' => '🎙️ Female (English)',
                                            ])
                                            ->default('female_de'),
                                    ])
                                    ->columns(2),

                                Forms\Components\Section::make('Automatisierung')
                                    ->description('Automatische Workflows')
                                    ->schema([
                                        Forms\Components\Toggle::make('auto_answer')
                                            ->label('Automatische Annahme')
                                            ->helperText('Anrufe automatisch annehmen')
                                            ->default(false),

                                        Forms\Components\Toggle::make('call_recording')
                                            ->label('Anrufaufzeichnung')
                                            ->helperText('Alle Gespräche aufzeichnen')
                                            ->default(false),

                                        Forms\Components\Toggle::make('transcription_enabled')
                                            ->label('Transkription')
                                            ->helperText('Gespräche automatisch transkribieren')
                                            ->default(false),

                                        Forms\Components\Toggle::make('sentiment_analysis')
                                            ->label('Sentiment-Analyse')
                                            ->helperText('Stimmungsanalyse durchführen')
                                            ->default(false),
                                    ])
                                    ->columns(2)
                                    ->collapsed(),
                            ]),

                        Tabs\Tab::make('📊 Statistik')
                            ->schema([
                                Forms\Components\Section::make('Anrufstatistiken')
                                    ->description('Nutzungsstatistiken und Metriken')
                                    ->schema([
                                        Forms\Components\Placeholder::make('total_calls')
                                            ->label('Gesamtanrufe')
                                            ->content(fn (?PhoneNumber $record) => $record ? number_format($record->total_calls ?? 0, 0, ',', '.') : '0'),

                                        Forms\Components\Placeholder::make('inbound_calls')
                                            ->label('Eingehende Anrufe')
                                            ->content(fn (?PhoneNumber $record) => $record ? number_format($record->inbound_calls ?? 0, 0, ',', '.') : '0'),

                                        Forms\Components\Placeholder::make('outbound_calls')
                                            ->label('Ausgehende Anrufe')
                                            ->content(fn (?PhoneNumber $record) => $record ? number_format($record->outbound_calls ?? 0, 0, ',', '.') : '0'),

                                        Forms\Components\Placeholder::make('missed_calls')
                                            ->label('Verpasste Anrufe')
                                            ->content(fn (?PhoneNumber $record) => $record ? number_format($record->missed_calls ?? 0, 0, ',', '.') : '0'),

                                        Forms\Components\Placeholder::make('avg_call_duration')
                                            ->label('Ø Gesprächsdauer')
                                            ->content(fn (?PhoneNumber $record) => $record && $record->avg_call_duration
                                                ? gmdate('H:i:s', $record->avg_call_duration)
                                                : '00:00:00'),

                                        Forms\Components\Placeholder::make('total_duration')
                                            ->label('Gesamtdauer')
                                            ->content(fn (?PhoneNumber $record) => $record && $record->total_duration
                                                ? gmdate('H:i:s', $record->total_duration)
                                                : '00:00:00'),

                                        Forms\Components\Placeholder::make('last_call')
                                            ->label('Letzter Anruf')
                                            ->content(fn (?PhoneNumber $record) => $record && $record->last_call_at
                                                ? $record->last_call_at->format('d.m.Y H:i')
                                                : 'Noch nie'),

                                        Forms\Components\Placeholder::make('busiest_hour')
                                            ->label('Hauptgeschäftszeit')
                                            ->content(fn (?PhoneNumber $record) => $record && $record->busiest_hour !== null
                                                ? sprintf('%02d:00 - %02d:00', $record->busiest_hour, ($record->busiest_hour + 1) % 24)
                                                : 'Keine Daten'),
                                    ])
                                    ->columns(4),

                                Forms\Components\Section::make('Nachrichten-Statistiken')
                                    ->description('SMS und WhatsApp Metriken')
                                    ->schema([
                                        Forms\Components\Placeholder::make('total_sms')
                                            ->label('SMS gesamt')
                                            ->content(fn (?PhoneNumber $record) => $record ? number_format($record->total_sms ?? 0, 0, ',', '.') : '0'),

                                        Forms\Components\Placeholder::make('total_whatsapp')
                                            ->label('WhatsApp gesamt')
                                            ->content(fn (?PhoneNumber $record) => $record ? number_format($record->total_whatsapp ?? 0, 0, ',', '.') : '0'),

                                        Forms\Components\Placeholder::make('response_rate')
                                            ->label('Antwortrate')
                                            ->content(fn (?PhoneNumber $record) => $record && $record->response_rate !== null
                                                ? number_format($record->response_rate, 1, ',', '.') . '%'
                                                : 'Keine Daten'),

                                        Forms\Components\Placeholder::make('avg_response_time')
                                            ->label('Ø Antwortzeit')
                                            ->content(fn (?PhoneNumber $record) => $record && $record->avg_response_time
                                                ? $record->avg_response_time . ' Min.'
                                                : 'Keine Daten'),
                                    ])
                                    ->columns(4)
                                    ->collapsed(),
                            ]),

                        Tabs\Tab::make('⚙️ Erweitert')
                            ->schema([
                                Forms\Components\Section::make('Technische Details')
                                    ->description('Erweiterte Konfigurationen')
                                    ->schema([
                                        Forms\Components\TextInput::make('country_code')
                                            ->label('Ländervorwahl')
                                            ->maxLength(10)
                                            ->placeholder('+49')
                                            ->disabled(),

                                        Forms\Components\TextInput::make('carrier')
                                            ->label('Netzbetreiber')
                                            ->maxLength(255)
                                            ->placeholder('z.B. Telekom, Vodafone'),

                                        Forms\Components\Select::make('number_type')
                                            ->label('Nummerntyp')
                                            ->options([
                                                'landline' => '☎️ Festnetz',
                                                'mobile' => '📱 Mobil',
                                                'voip' => '🌐 VoIP',
                                                'toll_free' => '🆓 Gebührenfrei',
                                                'premium' => '💰 Premium',
                                                'shared' => '👥 Geteilt',
                                            ])
                                            ->default('landline'),

                                        Forms\Components\Select::make('protocol')
                                            ->label('Protokoll')
                                            ->options([
                                                'pstn' => 'PSTN (Analog)',
                                                'isdn' => 'ISDN',
                                                'sip' => 'SIP/VoIP',
                                                'webrtc' => 'WebRTC',
                                            ])
                                            ->default('sip'),
                                    ])
                                    ->columns(2),

                                Forms\Components\Section::make('Metadaten')
                                    ->description('Zusätzliche Informationen')
                                    ->schema([
                                        Forms\Components\KeyValue::make('metadata')
                                            ->label('Benutzerdefinierte Felder')
                                            ->keyLabel('Schlüssel')
                                            ->valueLabel('Wert')
                                            ->addActionLabel('Feld hinzufügen')
                                            ->deletable()
                                            ->reorderable()
                                            ->columnSpanFull(),

                                        Forms\Components\Textarea::make('notes')
                                            ->label('Interne Notizen')
                                            ->rows(4)
                                            ->maxLength(1000)
                                            ->placeholder('Notizen für interne Zwecke')
                                            ->columnSpanFull(),
                                    ]),

                                Forms\Components\Section::make('System-Informationen')
                                    ->description('Automatisch generierte Daten')
                                    ->schema([
                                        Forms\Components\Placeholder::make('id')
                                            ->label('ID')
                                            ->content(fn (?PhoneNumber $record) => $record?->id ?? '-'),

                                        Forms\Components\Placeholder::make('created_at')
                                            ->label('Erstellt am')
                                            ->content(fn (?PhoneNumber $record) => $record?->created_at?->format('d.m.Y H:i:s') ?? '-'),

                                        Forms\Components\Placeholder::make('updated_at')
                                            ->label('Aktualisiert am')
                                            ->content(fn (?PhoneNumber $record) => $record?->updated_at?->format('d.m.Y H:i:s') ?? '-'),

                                        Forms\Components\Placeholder::make('created_by')
                                            ->label('Erstellt von')
                                            ->content(fn (?PhoneNumber $record) => $record?->creator?->name ?? 'System'),
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

                Tables\Columns\TextColumn::make('number')
                    ->label('Telefonnummer')
                    ->searchable()
                    ->sortable()
                    
                    ->weight('bold')
                    ->icon('heroicon-m-phone')
                    ->formatStateUsing(function ($state, PhoneNumber $record) {
                        return $record->formatted_number ?? $state;
                    }),

                Tables\Columns\BadgeColumn::make('type')
                    ->label('Typ')
                    ->colors([
                        'primary' => 'direct',
                        'success' => 'hotline',
                        'warning' => 'support',
                        'info' => 'sales',
                        'secondary' => 'mobile',
                        'danger' => 'emergency',
                    ])
                    ->icons([
                        'direct' => 'heroicon-m-phone',
                        'hotline' => 'heroicon-m-phone-arrow-down-left',
                        'support' => 'heroicon-m-lifebuoy',
                        'sales' => 'heroicon-m-currency-euro',
                        'mobile' => 'heroicon-m-device-phone-mobile',
                        'emergency' => 'heroicon-m-exclamation-triangle',
                    ])
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'direct' => 'Direktwahl',
                        'hotline' => 'Hotline',
                        'support' => 'Support',
                        'sales' => 'Vertrieb',
                        'mobile' => 'Mobil',
                        'fax' => 'Fax',
                        'emergency' => 'Notfall',
                        default => ucfirst($state),
                    }),

                Tables\Columns\TextColumn::make('label')
                    ->label('Bezeichnung')
                    ->searchable()
                    ->limit(30)
                    ->tooltip(fn (PhoneNumber $record) => $record->label)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('company.name')
                    ->label('Firma')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-m-building-office'),

                Tables\Columns\TextColumn::make('branch.name')
                    ->label('Filiale')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Hauptsitz')
                    ->icon('heroicon-m-building-storefront')
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_primary')
                    ->label('Haupt')
                    ->boolean()
                    ->trueIcon('heroicon-o-star')
                    ->falseIcon('heroicon-o-minus')
                    ->trueColor('warning')
                    ->falseColor('gray')
                    ->tooltip(fn (bool $state) => $state ? 'Hauptnummer' : 'Nebennummer'),

                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Aktiv')
                    ->onIcon('heroicon-m-check-circle')
                    ->offIcon('heroicon-m-x-circle')
                    ->onColor('success')
                    ->offColor('danger'),

                Tables\Columns\IconColumn::make('communication_channels')
                    ->label('Kanäle')
                    ->state(function (PhoneNumber $record): array {
                        $channels = [];
                        if ($record->voice_enabled ?? true) $channels[] = 'voice';
                        if ($record->sms_enabled) $channels[] = 'sms';
                        if ($record->whatsapp_enabled) $channels[] = 'whatsapp';
                        return $channels;
                    })
                    ->icons([
                        'voice' => 'heroicon-m-phone',
                        'sms' => 'heroicon-m-chat-bubble-left',
                        'whatsapp' => 'heroicon-m-chat-bubble-left-right',
                    ])
                    ->colors([
                        'voice' => 'primary',
                        'sms' => 'warning',
                        'whatsapp' => 'success',
                    ]),

                Tables\Columns\TextColumn::make('retell_agent_id')
                    ->label('KI-Agent')
                    ->placeholder('Kein Agent')
                    ->icon('heroicon-m-cpu-chip')
                    ->toggleable(),

                Tables\Columns\BadgeColumn::make('total_calls')
                    ->label('Anrufe')
                    ->color('primary')
                    ->icon('heroicon-m-phone-arrow-up-right')
                    ->formatStateUsing(fn ($state) => number_format($state ?? 0, 0, ',', '.'))
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt')
                    ->dateTime('d.m.Y')
                    ->sortable()
                    ->since()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Aktualisiert')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->since()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Typ')
                    ->multiple()
                    ->options([
                        'direct' => '📱 Direktwahl',
                        'hotline' => '📞 Hotline',
                        'support' => '🆘 Support',
                        'sales' => '💼 Vertrieb',
                        'mobile' => '📲 Mobil',
                        'emergency' => '🚨 Notfall',
                    ]),

                Tables\Filters\SelectFilter::make('company_id')
                    ->label('Firma')
                    ->relationship('company', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('branch_id')
                    ->label('Filiale')
                    ->relationship('branch', 'name')
                    ->searchable(),

                Tables\Filters\Filter::make('primary_only')
                    ->label('Nur Hauptnummern')
                    ->query(fn ($query) => $query->where('is_primary', true))
                    ->toggle(),

                Tables\Filters\Filter::make('active_only')
                    ->label('Nur aktive')
                    ->query(fn ($query) => $query->where('is_active', true))
                    ->toggle()
                    ->default(),

                Tables\Filters\Filter::make('with_ai')
                    ->label('Mit KI-Agent')
                    ->query(fn ($query) => $query->whereNotNull('retell_agent_id'))
                    ->toggle(),

                Tables\Filters\Filter::make('sms_enabled')
                    ->label('SMS aktiviert')
                    ->query(fn ($query) => $query->where('sms_enabled', true))
                    ->toggle(),

                Tables\Filters\Filter::make('whatsapp_enabled')
                    ->label('WhatsApp aktiviert')
                    ->query(fn ($query) => $query->where('whatsapp_enabled', true))
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->iconButton()
                    ->tooltip('Details anzeigen'),

                Tables\Actions\EditAction::make()
                    ->iconButton()
                    ->tooltip('Bearbeiten'),

                Tables\Actions\Action::make('test_call')
                    ->label('Testanruf')
                    ->icon('heroicon-m-phone-arrow-up-right')
                    ->color('success')
                    ->tooltip('Testanruf durchführen')
                    ->requiresConfirmation()
                    ->modalHeading('Testanruf durchführen')
                    ->modalDescription(fn (PhoneNumber $record) => "Möchten Sie einen Testanruf an {$record->formatted_number} durchführen?")
                    ->modalSubmitActionLabel('Anrufen')
                    ->action(function (PhoneNumber $record) {
                        // Test call logic would go here
                        Notification::make()
                            ->title('Testanruf gestartet')
                            ->body("Testanruf an {$record->formatted_number} wurde initiiert.")
                            ->success()
                            ->send();
                    })
                    ->visible(fn (PhoneNumber $record) => $record->is_active),

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

                    Tables\Actions\BulkAction::make('assign_agent')
                        ->label('KI-Agent zuweisen')
                        ->icon('heroicon-m-cpu-chip')
                        ->form([
                            Forms\Components\Select::make('retell_agent_id')
                                ->label('KI-Agent')
                                ->placeholder('agent_xxxxxxxxxxxxx')
                                ->required()
                                ->searchable(),
                        ])
                        ->action(function ($records, array $data) {
                            $records->each->update(['retell_agent_id' => $data['retell_agent_id']]);
                            Notification::make()
                                ->title('Agent zugewiesen')
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
            ->emptyStateHeading('Keine Telefonnummern vorhanden')
            ->emptyStateDescription('Erstellen Sie Ihre erste Telefonnummer')
            ->emptyStateIcon('heroicon-o-phone')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Telefonnummer erstellen')
                    ->icon('heroicon-m-plus'),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Telefonnummer-Informationen')
                    ->schema([
                        Split::make([
                            Section::make([
                                TextEntry::make('formatted_number')
                                    ->label('Telefonnummer')
                                    
                                    ->weight('bold')
                                    ->size('lg'),

                                TextEntry::make('type')
                                    ->label('Typ')
                                    ->badge()
                                    ->color(fn (string $state): string => match($state) {
                                        'direct' => 'primary',
                                        'hotline' => 'success',
                                        'support' => 'warning',
                                        'emergency' => 'danger',
                                        default => 'secondary',
                                    }),

                                TextEntry::make('label')
                                    ->label('Bezeichnung')
                                    ->default('Keine Bezeichnung'),

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

                                TextEntry::make('branch.name')
                                    ->label('Filiale')
                                    ->placeholder('Hauptsitz')
                                    ->icon('heroicon-m-building-storefront'),

                                TextEntry::make('is_primary')
                                    ->label('Priorität')
                                    ->badge()
                                    ->color(fn (bool $state): string => $state ? 'warning' : 'gray')
                                    ->formatStateUsing(fn (bool $state): string => $state ? 'Hauptnummer' : 'Nebennummer'),
                            ]),
                        ])->from('md'),
                    ]),

                Section::make('Kommunikationskanäle')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('voice_enabled')
                                    ->label('Sprachanrufe')
                                    ->badge()
                                    ->color(fn ($state) => $state ? 'success' : 'gray')
                                    ->formatStateUsing(fn ($state) => $state ? 'Aktiviert' : 'Deaktiviert'),

                                TextEntry::make('sms_enabled')
                                    ->label('SMS')
                                    ->badge()
                                    ->color(fn (bool $state): string => $state ? 'success' : 'gray')
                                    ->formatStateUsing(fn (bool $state): string => $state ? 'Aktiviert' : 'Deaktiviert'),

                                TextEntry::make('whatsapp_enabled')
                                    ->label('WhatsApp')
                                    ->badge()
                                    ->color(fn (bool $state): string => $state ? 'success' : 'gray')
                                    ->formatStateUsing(fn (bool $state): string => $state ? 'Aktiviert' : 'Deaktiviert'),

                                TextEntry::make('mms_enabled')
                                    ->label('MMS')
                                    ->badge()
                                    ->color(fn ($state) => $state ? 'success' : 'gray')
                                    ->formatStateUsing(fn ($state) => $state ? 'Aktiviert' : 'Deaktiviert'),
                            ]),
                    ])
                    ->collapsible(),

                Section::make('KI-Integration')
                    ->schema([
                        TextEntry::make('retell_agent_id')
                            ->label('Retell Agent')
                            ->placeholder('Kein Agent zugewiesen')
                            ->icon('heroicon-m-cpu-chip'),

                        TextEntry::make('retell_phone_id')
                            ->label('Retell Phone ID')
                            
                            ->placeholder('Nicht konfiguriert'),

                        TextEntry::make('ai_provider')
                            ->label('KI-Anbieter')
                            ->badge()
                            ->default('none'),

                        TextEntry::make('ai_voice')
                            ->label('KI-Stimme')
                            ->default('Nicht konfiguriert'),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(),

                Section::make('Statistiken')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('total_calls')
                                    ->label('Gesamtanrufe')
                                    ->default(0)
                                    ->formatStateUsing(fn ($state) => number_format($state ?? 0, 0, ',', '.')),

                                TextEntry::make('last_call_at')
                                    ->label('Letzter Anruf')
                                    ->dateTime('d.m.Y H:i')
                                    ->placeholder('Noch nie'),

                                TextEntry::make('avg_call_duration')
                                    ->label('Ø Gesprächsdauer')
                                    ->formatStateUsing(fn ($state) => $state ? gmdate('H:i:s', $state) : '00:00:00'),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\CallsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPhoneNumbers::route('/'),
            'create' => Pages\CreatePhoneNumber::route('/create'),
            'view' => Pages\ViewPhoneNumber::route('/{record}'),
            'edit' => Pages\EditPhoneNumber::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['company', 'branch']);
    }
}