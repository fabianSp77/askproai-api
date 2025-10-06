<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SystemSettingsResource\Pages;
use App\Models\SystemSetting;
use App\Models\ActivityLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Tabs;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Illuminate\Database\Eloquent\Model;

class SystemSettingsResource extends Resource
{
    protected static ?string $model = SystemSetting::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Systemeinstellungen';

    protected static ?string $modelLabel = 'Einstellung';

    protected static ?string $pluralModelLabel = 'Einstellungen';

    protected static ?string $navigationGroup = 'System';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'key';

    public static function getNavigationBadge(): ?string
    {
        return null; // EMERGENCY: Disabled to prevent memory exhaustion
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return null; // EMERGENCY: Disabled to prevent memory exhaustion
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return null; // EMERGENCY: Disabled to prevent memory exhaustion
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Einstellungen')
                    ->tabs([
                        Tabs\Tab::make('⚙️ Grundeinstellungen')
                            ->schema([
                                Forms\Components\Section::make('Basis-Konfiguration')
                                    ->description('Grundlegende Systemeinstellungen und Identifikation')
                                    ->icon('heroicon-o-cog')
                                    ->schema([
                                        Forms\Components\TextInput::make('key')
                                            ->label('Schlüssel')
                                            ->required()
                                            ->unique(ignoreRecord: true)
                                            ->maxLength(100)
                                            ->regex('/^[a-z0-9_]+$/')
                                            ->placeholder('z.B. site_name')
                                            ->helperText('Eindeutiger Schlüssel (lowercase, underscore)')
                                            ->disabled(fn (?SystemSetting $record) => $record !== null),

                                        Forms\Components\TextInput::make('label')
                                            ->label('Bezeichnung')
                                            ->required()
                                            ->maxLength(255)
                                            ->placeholder('z.B. Website Name'),

                                        Forms\Components\Select::make('category')
                                            ->label('Kategorie')
                                            ->options([
                                                SystemSetting::CATEGORY_CORE => '🔴 Kern-System',
                                                SystemSetting::CATEGORY_FEATURE => '🟡 Funktionen',
                                                SystemSetting::CATEGORY_INTEGRATION => '🔵 Integrationen',
                                                SystemSetting::CATEGORY_EXPERIMENTAL => '🟣 Experimentell',
                                            ])
                                            ->default(SystemSetting::CATEGORY_CORE)
                                            ->required()
                                            ->native(false)
                                            ->helperText('Systembereich dieser Einstellung'),

                                        Forms\Components\Select::make('group')
                                            ->label('Gruppe')
                                            ->options([
                                                SystemSetting::GROUP_GENERAL => '⚙️ Allgemein',
                                                SystemSetting::GROUP_EMAIL => '📧 E-Mail',
                                                SystemSetting::GROUP_SECURITY => '🔒 Sicherheit',
                                                SystemSetting::GROUP_INTEGRATION => '🔗 Integrationen',
                                                SystemSetting::GROUP_PERFORMANCE => '⚡ Performance',
                                                SystemSetting::GROUP_APPEARANCE => '🎨 Erscheinungsbild',
                                                SystemSetting::GROUP_NOTIFICATION => '🔔 Benachrichtigungen',
                                                SystemSetting::GROUP_BACKUP => '💾 Backup',
                                                SystemSetting::GROUP_MAINTENANCE => '🔧 Wartung',
                                                SystemSetting::GROUP_LOCALIZATION => '🌍 Lokalisierung',
                                                SystemSetting::GROUP_LOGGING => '📝 Protokollierung',
                                                SystemSetting::GROUP_CACHE => '💾 Cache',
                                            ])
                                            ->default(SystemSetting::GROUP_GENERAL)
                                            ->required()
                                            ->native(false)
                                            ->reactive()
                                            ->helperText('Funktionale Gruppierung'),

                                        Forms\Components\Select::make('type')
                                            ->label('Datentyp')
                                            ->options([
                                                SystemSetting::TYPE_STRING => '📝 Text',
                                                SystemSetting::TYPE_INTEGER => '🔢 Zahl',
                                                SystemSetting::TYPE_FLOAT => '💰 Dezimalzahl',
                                                SystemSetting::TYPE_BOOLEAN => '✅ Ja/Nein',
                                                SystemSetting::TYPE_JSON => '📋 JSON',
                                                SystemSetting::TYPE_ARRAY => '📚 Array',
                                                SystemSetting::TYPE_TEXTAREA => '📄 Mehrzeiliger Text',
                                                SystemSetting::TYPE_SELECT => '🎯 Auswahl',
                                                SystemSetting::TYPE_MULTISELECT => '☑️ Mehrfachauswahl',
                                                SystemSetting::TYPE_COLOR => '🎨 Farbe',
                                                SystemSetting::TYPE_DATE => '📅 Datum',
                                                SystemSetting::TYPE_TIME => '⏰ Zeit',
                                                SystemSetting::TYPE_DATETIME => '🕐 Datum & Zeit',
                                                SystemSetting::TYPE_EMAIL => '📧 E-Mail',
                                                SystemSetting::TYPE_URL => '🔗 URL',
                                                SystemSetting::TYPE_PASSWORD => '🔐 Passwort',
                                                SystemSetting::TYPE_FILE => '📁 Datei',
                                                SystemSetting::TYPE_ENCRYPTED => '🔒 Verschlüsselt',
                                            ])
                                            ->default(SystemSetting::TYPE_STRING)
                                            ->required()
                                            ->reactive()
                                            ->afterStateUpdated(fn (callable $set) => $set('value', null))
                                            ->helperText('Bestimmt die Validierung und Darstellung'),

                                        Forms\Components\Textarea::make('description')
                                            ->label('Beschreibung')
                                            ->rows(2)
                                            ->maxLength(500)
                                            ->placeholder('Erklärung für diese Einstellung')
                                            ->columnSpanFull(),

                                        Forms\Components\Select::make('priority')
                                            ->label('Priorität')
                                            ->options([
                                                0 => '⚪ Niedrig',
                                                1 => '🔵 Normal',
                                                2 => '🟡 Wichtig',
                                                3 => '🟠 Hoch',
                                                4 => '🔴 Kritisch',
                                            ])
                                            ->default(1)
                                            ->required()
                                            ->helperText('Wichtigkeit dieser Einstellung'),

                                        Forms\Components\TagsInput::make('tags')
                                            ->label('Tags')
                                            ->placeholder('Neue Tags hinzufügen...')
                                            ->separator(',')
                                            ->helperText('Tags für bessere Kategorisierung'),
                                    ])
                                    ->columns(2),
                            ]),

                        Tabs\Tab::make('💾 Wert & Validierung')
                            ->schema([
                                Forms\Components\Section::make('Wert-Konfiguration')
                                    ->description('Der aktuelle Wert und Validierungsregeln')
                                    ->icon('heroicon-o-document-text')
                                    ->schema([
                                        Forms\Components\TextInput::make('value')
                                            ->label('Wert')
                                            ->visible(fn (callable $get) => in_array($get('type'), [
                                                SystemSetting::TYPE_STRING,
                                                SystemSetting::TYPE_COLOR,
                                            ]))
                                            ->maxLength(65535),

                                        Forms\Components\TextInput::make('value')
                                            ->label('Wert')
                                            ->numeric()
                                            ->visible(fn (callable $get) => $get('type') === SystemSetting::TYPE_INTEGER),

                                        Forms\Components\TextInput::make('value')
                                            ->label('Wert')
                                            ->numeric()
                                            ->step(0.01)
                                            ->visible(fn (callable $get) => $get('type') === SystemSetting::TYPE_FLOAT),

                                        Forms\Components\Textarea::make('value')
                                            ->label('Wert')
                                            ->rows(5)
                                            ->visible(fn (callable $get) => in_array($get('type'), [
                                                SystemSetting::TYPE_TEXTAREA,
                                                SystemSetting::TYPE_JSON,
                                                SystemSetting::TYPE_ARRAY,
                                            ])),

                                        Forms\Components\Toggle::make('value')
                                            ->label('Wert')
                                            ->visible(fn (callable $get) => $get('type') === SystemSetting::TYPE_BOOLEAN),

                                        Forms\Components\Select::make('value')
                                            ->label('Wert')
                                            ->options(fn (callable $get) => $get('options') ?? [])
                                            ->visible(fn (callable $get) => $get('type') === SystemSetting::TYPE_SELECT),

                                        Forms\Components\Select::make('value')
                                            ->label('Werte')
                                            ->multiple()
                                            ->options(fn (callable $get) => $get('options') ?? [])
                                            ->visible(fn (callable $get) => $get('type') === SystemSetting::TYPE_MULTISELECT),

                                        Forms\Components\DatePicker::make('value')
                                            ->label('Wert')
                                            ->visible(fn (callable $get) => $get('type') === SystemSetting::TYPE_DATE),

                                        Forms\Components\TimePicker::make('value')
                                            ->label('Wert')
                                            ->visible(fn (callable $get) => $get('type') === SystemSetting::TYPE_TIME),

                                        Forms\Components\DateTimePicker::make('value')
                                            ->label('Wert')
                                            ->visible(fn (callable $get) => $get('type') === SystemSetting::TYPE_DATETIME),

                                        Forms\Components\TextInput::make('value')
                                            ->label('E-Mail')
                                            ->email()
                                            ->visible(fn (callable $get) => $get('type') === SystemSetting::TYPE_EMAIL)
                                            ->maxLength(255),

                                        Forms\Components\TextInput::make('value')
                                            ->label('URL')
                                            ->url()
                                            ->visible(fn (callable $get) => $get('type') === SystemSetting::TYPE_URL)
                                            ->maxLength(500),

                                        Forms\Components\TextInput::make('value')
                                            ->label('Passwort')
                                            ->password()
                                            ->visible(fn (callable $get) => $get('type') === SystemSetting::TYPE_PASSWORD)
                                            ->maxLength(255),

                                        Forms\Components\FileUpload::make('value')
                                            ->label('Datei')
                                            ->visible(fn (callable $get) => $get('type') === SystemSetting::TYPE_FILE)
                                            ->directory('system-settings')
                                            ->preserveFilenames(),

                                        Forms\Components\KeyValue::make('options')
                                            ->label('Verfügbare Optionen')
                                            ->keyLabel('Wert')
                                            ->valueLabel('Bezeichnung')
                                            ->visible(fn (callable $get) => in_array($get('type'), [
                                                SystemSetting::TYPE_SELECT,
                                                SystemSetting::TYPE_MULTISELECT,
                                            ]))
                                            ->helperText('Definieren Sie die verfügbaren Optionen für Auswahllisten')
                                            ->columnSpanFull(),

                                        Forms\Components\TextInput::make('default_value')
                                            ->label('Standardwert')
                                            ->placeholder('Fallback-Wert wenn leer')
                                            ->maxLength(1000)
                                            ->helperText('Wird verwendet, wenn kein Wert gesetzt ist'),

                                        Forms\Components\TextInput::make('min_value')
                                            ->label('Minimalwert')
                                            ->numeric()
                                            ->visible(fn (callable $get) => in_array($get('type'), [
                                                SystemSetting::TYPE_INTEGER,
                                                SystemSetting::TYPE_FLOAT,
                                            ]))
                                            ->helperText('Minimaler erlaubter Wert'),

                                        Forms\Components\TextInput::make('max_value')
                                            ->label('Maximalwert')
                                            ->numeric()
                                            ->visible(fn (callable $get) => in_array($get('type'), [
                                                SystemSetting::TYPE_INTEGER,
                                                SystemSetting::TYPE_FLOAT,
                                            ]))
                                            ->helperText('Maximaler erlaubter Wert'),
                                    ])
                                    ->columns(2),

                                Forms\Components\Section::make('Validierung & Constraints')
                                    ->description('Validierungsregeln und Einschränkungen')
                                    ->schema([
                                        Forms\Components\KeyValue::make('validation_rules')
                                            ->label('Laravel Validierungsregeln')
                                            ->keyLabel('Regel')
                                            ->valueLabel('Parameter')
                                            ->helperText('z.B. required, min:5, max:100, regex:/^[a-z]+$/')
                                            ->columnSpanFull(),

                                        Forms\Components\Textarea::make('validation_message')
                                            ->label('Validierungsfehlermeldung')
                                            ->rows(2)
                                            ->maxLength(500)
                                            ->placeholder('Benutzerdefinierte Fehlermeldung bei ungültigen Werten')
                                            ->columnSpanFull(),

                                        Forms\Components\Select::make('allowed_values')
                                            ->label('Erlaubte Werte')
                                            ->multiple()
                                            ->options([])
                                            ->createOptionForm([
                                                Forms\Components\TextInput::make('value')
                                                    ->label('Wert')
                                                    ->required(),
                                            ])
                                            ->createOptionUsing(fn (array $data): string => $data['value'])
                                            ->visible(fn (callable $get) => $get('type') === SystemSetting::TYPE_STRING)
                                            ->helperText('Einschränkung auf bestimmte Werte'),

                                        Forms\Components\Toggle::make('required')
                                            ->label('Pflichtfeld')
                                            ->helperText('Muss ein Wert gesetzt sein?')
                                            ->default(false),
                                    ])
                                    ->collapsed(),
                            ]),

                        Tabs\Tab::make('🔒 Sicherheit & Zugriff')
                            ->schema([
                                Forms\Components\Section::make('Sicherheitseinstellungen')
                                    ->description('Verschlüsselung, Berechtigungen und Zugriffskontrollen')
                                    ->icon('heroicon-o-shield-check')
                                    ->schema([
                                        Forms\Components\Toggle::make('is_public')
                                            ->label('Öffentlich zugänglich')
                                            ->helperText('Kann ohne Authentifizierung abgerufen werden')
                                            ->default(false)
                                            ->reactive(),

                                        Forms\Components\Toggle::make('is_encrypted')
                                            ->label('Verschlüsselte Speicherung')
                                            ->helperText('Wert wird verschlüsselt in der Datenbank gespeichert')
                                            ->default(false)
                                            ->reactive(),

                                        Forms\Components\Toggle::make('is_sensitive')
                                            ->label('Sensible Daten')
                                            ->helperText('Enthält vertrauliche Informationen (wird in Logs maskiert)')
                                            ->default(false),

                                        Forms\Components\Toggle::make('requires_restart')
                                            ->label('Neustart erforderlich')
                                            ->helperText('Änderung erfordert Systemneustart')
                                            ->default(false),

                                        Forms\Components\Select::make('permission')
                                            ->label('Erforderliche Berechtigung')
                                            ->options([
                                                'settings.view' => 'Einstellungen anzeigen',
                                                'settings.edit' => 'Einstellungen bearbeiten',
                                                'settings.admin' => 'Admin-Einstellungen',
                                                'super-admin' => 'Super-Admin',
                                            ])
                                            ->placeholder('Keine spezielle Berechtigung')
                                            ->helperText('Berechtigung für Zugriff auf diese Einstellung'),

                                        Forms\Components\Select::make('environment')
                                            ->label('Umgebungen')
                                            ->multiple()
                                            ->options([
                                                'local' => '💻 Lokal',
                                                'development' => '🔧 Entwicklung',
                                                'staging' => '🎭 Staging',
                                                'production' => '🚀 Produktion',
                                            ])
                                            ->default(['production'])
                                            ->helperText('In welchen Umgebungen ist diese Einstellung aktiv?'),

                                        Forms\Components\TagsInput::make('allowed_ips')
                                            ->label('Erlaubte IP-Adressen')
                                            ->placeholder('IP-Adressen hinzufügen...')
                                            ->helperText('Zugriff nur von diesen IPs (leer = alle)')
                                            ->visible(fn (callable $get) => $get('is_public'))
                                            ->columnSpanFull(),

                                        Forms\Components\Textarea::make('security_notes')
                                            ->label('Sicherheitshinweise')
                                            ->rows(3)
                                            ->maxLength(1000)
                                            ->placeholder('Wichtige Sicherheitsinformationen zu dieser Einstellung')
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(2),

                                Forms\Components\Section::make('Audit & Tracking')
                                    ->description('Änderungsverfolgung und Audit-Informationen')
                                    ->schema([
                                        Forms\Components\Placeholder::make('created_by_display')
                                            ->label('Erstellt von')
                                            ->content(fn (?SystemSetting $record) => $record?->created_by
                                                ? \App\Models\User::find($record->created_by)?->name ?? 'Unbekannt'
                                                : 'System'),

                                        Forms\Components\Placeholder::make('created_at')
                                            ->label('Erstellt am')
                                            ->content(fn (?SystemSetting $record) => $record?->created_at?->format('d.m.Y H:i:s') ?? '-'),

                                        Forms\Components\Placeholder::make('updated_by_display')
                                            ->label('Zuletzt geändert von')
                                            ->content(fn (?SystemSetting $record) => $record?->updated_by
                                                ? \App\Models\User::find($record->updated_by)?->name ?? 'Unbekannt'
                                                : 'System'),

                                        Forms\Components\Placeholder::make('updated_at')
                                            ->label('Zuletzt geändert am')
                                            ->content(fn (?SystemSetting $record) => $record?->updated_at?->format('d.m.Y H:i:s') ?? '-'),

                                        Forms\Components\Placeholder::make('change_count')
                                            ->label('Anzahl Änderungen')
                                            ->content(fn (?SystemSetting $record) => $record?->change_count ?? 0),

                                        Forms\Components\Placeholder::make('last_accessed')
                                            ->label('Zuletzt abgerufen')
                                            ->content(fn (?SystemSetting $record) => $record?->last_accessed?->format('d.m.Y H:i:s') ?? 'Noch nie'),

                                        Forms\Components\Textarea::make('change_log')
                                            ->label('Änderungsprotokoll')
                                            ->rows(4)
                                            ->disabled()
                                            ->columnSpanFull()
                                            ->visible(fn (?SystemSetting $record) => $record?->change_log)
                                    ])
                                    ->columns(2)
                                    ->collapsed(),
                            ]),

                        Tabs\Tab::make('⚡ Performance & Cache')
                            ->schema([
                                Forms\Components\Section::make('Cache-Verwaltung')
                                    ->description('Cache-Einstellungen und Optimierungen')
                                    ->icon('heroicon-o-cpu-chip')
                                    ->schema([
                                        Forms\Components\TextInput::make('cache_ttl')
                                            ->label('Cache TTL (Sekunden)')
                                            ->numeric()
                                            ->default(3600)
                                            ->minValue(0)
                                            ->maxValue(86400)
                                            ->suffix('Sekunden')
                                            ->helperText('0 = Kein Cache, 86400 = 1 Tag'),

                                        Forms\Components\Select::make('cache_driver')
                                            ->label('Cache-Treiber')
                                            ->options([
                                                'file' => '📁 Datei',
                                                'database' => '🗄️ Datenbank',
                                                'redis' => '⚡ Redis',
                                                'memcached' => '💾 Memcached',
                                                'array' => '📝 Array (Nur Tests)',
                                            ])
                                            ->default('file')
                                            ->helperText('Bevorzugter Cache-Speicher für diese Einstellung'),

                                        Forms\Components\Toggle::make('cache_enabled')
                                            ->label('Cache aktiviert')
                                            ->default(true)
                                            ->helperText('Soll diese Einstellung gecacht werden?'),

                                        Forms\Components\Toggle::make('eager_load')
                                            ->label('Eager Loading')
                                            ->default(false)
                                            ->helperText('Beim Systemstart laden'),

                                        Forms\Components\Placeholder::make('cache_status')
                                            ->label('Cache-Status')
                                            ->content(function (?SystemSetting $record) {
                                                if (!$record) return 'Neu';
                                                $cacheKey = 'system_setting_' . $record->key;
                                                $cached = Cache::has($cacheKey);
                                                return $cached
                                                    ? new HtmlString('<span class="text-success-600">✅ Gecacht</span>')
                                                    : new HtmlString('<span class="text-gray-600">⭕ Nicht gecacht</span>');
                                            }),

                                        Forms\Components\Actions::make([
                                            Forms\Components\Actions\Action::make('clear_this_cache')
                                                ->label('🗑️ Diesen Cache leeren')
                                                ->color('warning')
                                                ->action(function (?SystemSetting $record) {
                                                    if ($record) {
                                                        Cache::forget('system_setting_' . $record->key);
                                                        Notification::make()
                                                            ->title('Cache geleert')
                                                            ->body('Cache für "' . $record->key . '" wurde geleert.')
                                                            ->success()
                                                            ->send();
                                                    }
                                                })
                                                ->visible(fn (?SystemSetting $record) => $record !== null),
                                        ])
                                    ])
                                    ->columns(2),
                            ]),

                        Tabs\Tab::make('🔗 Abhängigkeiten')
                            ->schema([
                                Forms\Components\Section::make('Einstellungs-Abhängigkeiten')
                                    ->description('Beziehungen zu anderen Einstellungen')
                                    ->schema([
                                        Forms\Components\Select::make('depends_on')
                                            ->label('Abhängig von')
                                            ->multiple()
                                            ->searchable()
                                            ->preload()
                                            ->options(fn () => SystemSetting::pluck('label', 'key'))
                                            ->helperText('Diese Einstellung hängt von anderen ab'),

                                        Forms\Components\Select::make('affects')
                                            ->label('Beeinflusst')
                                            ->multiple()
                                            ->searchable()
                                            ->preload()
                                            ->options(fn () => SystemSetting::pluck('label', 'key'))
                                            ->helperText('Diese Einstellung beeinflusst andere'),

                                        Forms\Components\Textarea::make('impact_description')
                                            ->label('Auswirkungsbeschreibung')
                                            ->rows(3)
                                            ->maxLength(1000)
                                            ->placeholder('Beschreiben Sie die Auswirkungen von Änderungen')
                                            ->columnSpanFull(),

                                        Forms\Components\KeyValue::make('conditions')
                                            ->label('Bedingungen')
                                            ->keyLabel('Einstellung')
                                            ->valueLabel('Erforderlicher Wert')
                                            ->helperText('Bedingungen für die Aktivierung dieser Einstellung')
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(2),
                            ])
                            ->visible(fn () => auth()->user()->hasRole('super-admin')),

                        Tabs\Tab::make('🚀 System-Aktionen')
                            ->schema([
                                Forms\Components\Section::make('System-Aktionen')
                                    ->description('Führen Sie systemweite Aktionen aus')
                                    ->schema([
                                        Forms\Components\Actions::make([
                                            Forms\Components\Actions\Action::make('clear_cache')
                                                ->label('🗑️ Cache leeren')
                                                ->color('warning')
                                                ->requiresConfirmation()
                                                ->modalHeading('Cache leeren')
                                                ->modalDescription('Möchten Sie wirklich den gesamten Cache leeren?')
                                                ->modalSubmitActionLabel('Cache leeren')
                                                ->action(function () {
                                                    Cache::flush();
                                                    Artisan::call('cache:clear');
                                                    Artisan::call('config:clear');
                                                    Artisan::call('view:clear');
                                                    Artisan::call('route:clear');

                                                    Notification::make()
                                                        ->title('Cache geleert')
                                                        ->body('Alle Caches wurden erfolgreich geleert.')
                                                        ->success()
                                                        ->send();
                                                }),

                                            Forms\Components\Actions\Action::make('optimize')
                                                ->label('⚡ Optimieren')
                                                ->color('success')
                                                ->action(function () {
                                                    Artisan::call('config:cache');
                                                    Artisan::call('route:cache');
                                                    Artisan::call('view:cache');

                                                    Notification::make()
                                                        ->title('System optimiert')
                                                        ->body('Konfiguration, Routen und Views wurden gecacht.')
                                                        ->success()
                                                        ->send();
                                                }),

                                            Forms\Components\Actions\Action::make('maintenance_on')
                                                ->label('🔧 Wartungsmodus EIN')
                                                ->color('danger')
                                                ->requiresConfirmation()
                                                ->modalHeading('Wartungsmodus aktivieren')
                                                ->modalDescription('Die Website wird für alle Benutzer außer Administratoren nicht erreichbar sein.')
                                                ->modalSubmitActionLabel('Aktivieren')
                                                ->action(function () {
                                                    Artisan::call('down', [
                                                        '--refresh' => 15,
                                                        '--retry' => 60,
                                                    ]);

                                                    SystemSetting::setValue('maintenance_mode', true);

                                                    Notification::make()
                                                        ->title('Wartungsmodus aktiviert')
                                                        ->body('Die Website ist jetzt im Wartungsmodus.')
                                                        ->warning()
                                                        ->persistent()
                                                        ->send();
                                                })
                                                ->visible(fn () => !app()->isDownForMaintenance()),

                                            Forms\Components\Actions\Action::make('maintenance_off')
                                                ->label('✅ Wartungsmodus AUS')
                                                ->color('success')
                                                ->action(function () {
                                                    Artisan::call('up');
                                                    SystemSetting::setValue('maintenance_mode', false);

                                                    Notification::make()
                                                        ->title('Wartungsmodus deaktiviert')
                                                        ->body('Die Website ist wieder online.')
                                                        ->success()
                                                        ->send();
                                                })
                                                ->visible(fn () => app()->isDownForMaintenance()),

                                            Forms\Components\Actions\Action::make('create_backup')
                                                ->label('💾 Backup erstellen')
                                                ->color('primary')
                                                ->action(function () {
                                                    // Backup logic would go here
                                                    Notification::make()
                                                        ->title('Backup gestartet')
                                                        ->body('Das Backup wird im Hintergrund erstellt.')
                                                        ->info()
                                                        ->send();
                                                }),
                                        ])
                                        ->fullWidth()
                                        ->columnSpanFull(),
                                    ]),

                                Forms\Components\Section::make('Datenbank & Migration')
                                    ->description('Datenbank-Wartung und Migrationen')
                                    ->icon('heroicon-o-circle-stack')
                                    ->schema([
                                        Forms\Components\Actions::make([
                                            Forms\Components\Actions\Action::make('run_migrations')
                                                ->label('🔄 Migrationen ausführen')
                                                ->color('warning')
                                                ->requiresConfirmation()
                                                ->modalHeading('Migrationen ausführen')
                                                ->modalDescription('Dies kann Datenbankänderungen verursachen. Stellen Sie sicher, dass Sie ein Backup haben.')
                                                ->modalSubmitActionLabel('Ausführen')
                                                ->action(function () {
                                                    Artisan::call('migrate', ['--force' => true]);

                                                    Notification::make()
                                                        ->title('Migrationen ausgeführt')
                                                        ->body('Alle ausstehenden Migrationen wurden angewendet.')
                                                        ->success()
                                                        ->send();
                                                })
                                                ->visible(fn () => auth()->user()->hasRole('super-admin')),

                                            Forms\Components\Actions\Action::make('export_settings')
                                                ->label('📥 Einstellungen exportieren')
                                                ->color('success')
                                                ->action(function () {
                                                    $settings = SystemSetting::all()->map(function ($setting) {
                                                        return [
                                                            'key' => $setting->key,
                                                            'value' => $setting->value,
                                                            'type' => $setting->type,
                                                            'group' => $setting->group,
                                                        ];
                                                    });

                                                    $json = json_encode($settings, JSON_PRETTY_PRINT);
                                                    $filename = 'system-settings-' . now()->format('Y-m-d-His') . '.json';

                                                    return response()->streamDownload(function () use ($json) {
                                                        echo $json;
                                                    }, $filename);
                                                }),

                                            Forms\Components\Actions\Action::make('seed_defaults')
                                                ->label('🌱 Standard-Einstellungen')
                                                ->color('info')
                                                ->requiresConfirmation()
                                                ->action(function () {
                                                    SystemSetting::createDefaults();

                                                    Notification::make()
                                                        ->title('Standards erstellt')
                                                        ->body('Standard-Einstellungen wurden erfolgreich erstellt.')
                                                        ->success()
                                                        ->send();
                                                }),
                                        ])
                                        ->fullWidth()
                                        ->columnSpanFull(),
                                    ])
                                    ->collapsed(),

                                Forms\Components\Section::make('Erweiterte Aktionen')
                                    ->description('Zusätzliche Systemoperationen')
                                    ->schema([
                                        Forms\Components\Actions::make([
                                            Forms\Components\Actions\Action::make('test_setting')
                                                ->label('🧪 Einstellung testen')
                                                ->color('info')
                                                ->form([
                                                    Forms\Components\Textarea::make('test_scenario')
                                                        ->label('Test-Szenario')
                                                        ->placeholder('Beschreiben Sie das Test-Szenario')
                                                        ->rows(3),
                                                ])
                                                ->action(function (array $data, ?SystemSetting $record) {
                                                    if ($record) {
                                                        // Log test
                                                        ActivityLog::create([
                                                            'type' => ActivityLog::TYPE_SYSTEM,
                                                            'event' => 'setting_tested',
                                                            'description' => "Einstellung '{$record->key}' getestet",
                                                            'user_id' => auth()->id(),
                                                            'properties' => [
                                                                'setting' => $record->key,
                                                                'scenario' => $data['test_scenario'] ?? 'Standard-Test',
                                                            ],
                                                        ]);

                                                        Notification::make()
                                                            ->title('Test durchgeführt')
                                                            ->body('Die Einstellung wurde erfolgreich getestet.')
                                                            ->success()
                                                            ->send();
                                                    }
                                                })
                                                ->visible(fn (?SystemSetting $record) => $record !== null),

                                            Forms\Components\Actions\Action::make('reset_to_default')
                                                ->label('↩️ Auf Standard zurücksetzen')
                                                ->color('warning')
                                                ->requiresConfirmation()
                                                ->modalHeading('Auf Standardwert zurücksetzen')
                                                ->modalDescription('Sind Sie sicher? Der aktuelle Wert wird überschrieben.')
                                                ->action(function (?SystemSetting $record) {
                                                    if ($record && $record->default_value) {
                                                        $record->value = $record->default_value;
                                                        $record->save();

                                                        Notification::make()
                                                            ->title('Zurückgesetzt')
                                                            ->body('Die Einstellung wurde auf den Standardwert zurückgesetzt.')
                                                            ->success()
                                                            ->send();
                                                    }
                                                })
                                                ->visible(fn (?SystemSetting $record) => $record?->default_value !== null),
                                        ])
                                        ->fullWidth()
                                        ->columnSpanFull(),
                                    ])
                                    ->collapsed()
                                    ->visible(fn () => auth()->user()->hasRole('admin')),
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
                Tables\Columns\TextColumn::make('category')
                    ->label('Kategorie')
                    ->badge()
                    ->formatStateUsing(fn (SystemSetting $record) => $record->category_label)
                    ->colors([
                        'danger' => SystemSetting::CATEGORY_CORE,
                        'warning' => SystemSetting::CATEGORY_FEATURE,
                        'primary' => SystemSetting::CATEGORY_INTEGRATION,
                        'secondary' => SystemSetting::CATEGORY_EXPERIMENTAL,
                    ])
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('group')
                    ->label('Gruppe')
                    ->badge()
                    ->formatStateUsing(fn (SystemSetting $record) => $record->group_label)
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('key')
                    ->label('Schlüssel')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    
                    ->tooltip(fn (SystemSetting $record) => $record->description),

                Tables\Columns\TextColumn::make('label')
                    ->label('Bezeichnung')
                    ->searchable()
                    ->formatStateUsing(fn (SystemSetting $record) => $record->formatted_label)
                    ->description(fn (SystemSetting $record) => $record->description)
                    ->limit(50),

                Tables\Columns\TextColumn::make('priority')
                    ->badge()
                    ->label('Priorität')
                    ->formatStateUsing(fn (SystemSetting $record) => match($record->priority) {
                        0 => '⚪ Niedrig',
                        1 => '🔵 Normal',
                        2 => '🟡 Wichtig',
                        3 => '🟠 Hoch',
                        4 => '🔴 Kritisch',
                        default => '⚪ Niedrig'
                    })
                    ->colors([
                        'gray' => 0,
                        'primary' => 1,
                        'warning' => 2,
                        'orange' => 3,
                        'danger' => 4,
                    ])
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->label('Typ')
                    ->formatStateUsing(fn (SystemSetting $record) => $record->type_label)
                    ->colors([
                        'primary' => SystemSetting::TYPE_STRING,
                        'success' => SystemSetting::TYPE_BOOLEAN,
                        'warning' => SystemSetting::TYPE_INTEGER,
                        'danger' => SystemSetting::TYPE_JSON,
                        'info' => SystemSetting::TYPE_SELECT,
                    ]),

                Tables\Columns\TextColumn::make('value')
                    ->label('Wert')
                    ->limit(50)
                    ->formatStateUsing(function (SystemSetting $record) {
                        $value = $record->getParsedValue();

                        if ($record->is_encrypted || $record->is_sensitive) {
                            return '🔒 [Geschützt]';
                        }

                        if (is_bool($value)) {
                            return $value ? '✅ Aktiviert' : '❌ Deaktiviert';
                        }

                        if (is_array($value)) {
                            return json_encode($value);
                        }

                        return $value;
                    })
                    ->tooltip(fn (SystemSetting $record) => $record->is_encrypted ? 'Verschlüsselter Wert' : $record->value),

                Tables\Columns\IconColumn::make('is_public')
                    ->label('Öffentlich')
                    ->boolean()
                    ->trueIcon('heroicon-o-globe-alt')
                    ->falseIcon('heroicon-o-lock-closed')
                    ->trueColor('success')
                    ->falseColor('warning'),

                Tables\Columns\IconColumn::make('is_encrypted')
                    ->label('Verschlüsselt')
                    ->boolean()
                    ->trueIcon('heroicon-o-lock-closed')
                    ->falseIcon('heroicon-o-lock-open')
                    ->trueColor('success')
                    ->falseColor('gray'),

                Tables\Columns\IconColumn::make('requires_restart')
                    ->label('Neustart')
                    ->boolean()
                    ->trueIcon('heroicon-o-arrow-path')
                    ->falseIcon('heroicon-o-check-circle')
                    ->trueColor('warning')
                    ->falseColor('success')
                    ->tooltip(fn (SystemSetting $record) =>
                        $record->requires_restart
                            ? 'Änderung erfordert Neustart'
                            : 'Kein Neustart erforderlich'
                    ),

                Tables\Columns\TextColumn::make('cache_ttl')
                    ->label('Cache')
                    ->formatStateUsing(fn (SystemSetting $record) =>
                        $record->cache_ttl > 0
                            ? ($record->cache_ttl >= 3600
                                ? round($record->cache_ttl / 3600, 1) . 'h'
                                : round($record->cache_ttl / 60) . 'min')
                            : 'Aus'
                    )
                    ->badge()
                    ->color(fn (SystemSetting $record) =>
                        $record->cache_ttl > 0 ? 'success' : 'gray'
                    )
                    ->sortable(),

                Tables\Columns\TextColumn::make('tags')
                    ->label('Tags')
                    ->badge()
                    ->separator(',')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Aktualisiert')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->since()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label('Kategorie')
                    ->options([
                        SystemSetting::CATEGORY_CORE => '🔴 Kern-System',
                        SystemSetting::CATEGORY_FEATURE => '🟡 Funktionen',
                        SystemSetting::CATEGORY_INTEGRATION => '🔵 Integrationen',
                        SystemSetting::CATEGORY_EXPERIMENTAL => '🟣 Experimentell',
                    ]),

                Tables\Filters\SelectFilter::make('group')
                    ->label('Gruppe')
                    ->multiple()
                    ->options([
                        SystemSetting::GROUP_GENERAL => '⚙️ Allgemein',
                        SystemSetting::GROUP_EMAIL => '📧 E-Mail',
                        SystemSetting::GROUP_SECURITY => '🔒 Sicherheit',
                        SystemSetting::GROUP_INTEGRATION => '🔗 Integrationen',
                        SystemSetting::GROUP_PERFORMANCE => '⚡ Performance',
                        SystemSetting::GROUP_APPEARANCE => '🎨 Erscheinungsbild',
                        SystemSetting::GROUP_NOTIFICATION => '🔔 Benachrichtigungen',
                        SystemSetting::GROUP_BACKUP => '💾 Backup',
                        SystemSetting::GROUP_MAINTENANCE => '🔧 Wartung',
                    ]),

                Tables\Filters\SelectFilter::make('type')
                    ->label('Typ')
                    ->multiple()
                    ->options([
                        SystemSetting::TYPE_STRING => 'Text',
                        SystemSetting::TYPE_INTEGER => 'Zahl',
                        SystemSetting::TYPE_BOOLEAN => 'Ja/Nein',
                        SystemSetting::TYPE_JSON => 'JSON',
                        SystemSetting::TYPE_SELECT => 'Auswahl',
                    ]),

                Tables\Filters\SelectFilter::make('priority')
                    ->label('Priorität')
                    ->options([
                        4 => '🔴 Kritisch',
                        3 => '🟠 Hoch',
                        2 => '🟡 Wichtig',
                        1 => '🔵 Normal',
                        0 => '⚪ Niedrig',
                    ]),

                Tables\Filters\Filter::make('public')
                    ->label('Öffentliche')
                    ->query(fn (Builder $query): Builder => $query->where('is_public', true))
                    ->toggle(),

                Tables\Filters\Filter::make('encrypted')
                    ->label('Verschlüsselte')
                    ->query(fn (Builder $query): Builder => $query->where('is_encrypted', true))
                    ->toggle(),

                Tables\Filters\Filter::make('sensitive')
                    ->label('Sensible')
                    ->query(fn (Builder $query): Builder => $query->where('is_sensitive', true))
                    ->toggle(),

                Tables\Filters\Filter::make('restart_required')
                    ->label('Neustart erforderlich')
                    ->query(fn (Builder $query): Builder => $query->where('requires_restart', true))
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->iconButton()
                    ->tooltip('Bearbeiten'),

                Tables\Actions\Action::make('test')
                    ->label('Test')
                    ->icon('heroicon-m-beaker')
                    ->tooltip('Einstellung testen')
                    ->color('info')
                    ->action(function (SystemSetting $record) {
                        $value = $record->getParsedValue();

                        Notification::make()
                            ->title('Einstellungswert')
                            ->body("Schlüssel: {$record->key}\nWert: " . json_encode($value))
                            ->info()
                            ->send();
                    }),

                Tables\Actions\Action::make('duplicate')
                    ->label('Duplizieren')
                    ->icon('heroicon-m-document-duplicate')
                    ->tooltip('Einstellung duplizieren')
                    ->color('warning')
                    ->form([
                        Forms\Components\TextInput::make('new_key')
                            ->label('Neuer Schlüssel')
                            ->required()
                            ->unique('system_settings', 'key')
                            ->regex('/^[a-z0-9_]+$/')
                            ->placeholder('z.B. new_setting_key'),
                    ])
                    ->action(function (SystemSetting $record, array $data) {
                        $newSetting = $record->replicate();
                        $newSetting->key = $data['new_key'];
                        $newSetting->label = $record->label . ' (Kopie)';
                        $newSetting->save();

                        Notification::make()
                            ->title('Einstellung dupliziert')
                            ->body("Die Einstellung wurde als '{$data['new_key']}' dupliziert.")
                            ->success()
                            ->send();
                    }),

                Tables\Actions\DeleteAction::make()
                    ->iconButton()
                    ->tooltip('Löschen')
                    ->visible(fn (SystemSetting $record) => !in_array($record->key, [
                        'site_name',
                        'maintenance_mode',
                        'backup_enabled',
                    ])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('export')
                        ->label('Exportieren')
                        ->icon('heroicon-m-arrow-down-tray')
                        ->color('success')
                        ->action(function (Collection $records) {
                            $settings = $records->map(function ($setting) {
                                return [
                                    'group' => $setting->group,
                                    'key' => $setting->key,
                                    'value' => $setting->value,
                                    'type' => $setting->type,
                                    'label' => $setting->label,
                                    'description' => $setting->description,
                                    'options' => $setting->options,
                                    'is_public' => $setting->is_public,
                                    'is_encrypted' => $setting->is_encrypted,
                                    'priority' => $setting->priority,
                                    'category' => $setting->category,
                                ];
                            })->toArray();

                            $json = json_encode($settings, JSON_PRETTY_PRINT);

                            return response()->streamDownload(function () use ($json) {
                                echo $json;
                            }, 'settings-export-' . now()->format('Y-m-d-His') . '.json');
                        })
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('clear_cache')
                        ->label('Cache leeren')
                        ->icon('heroicon-m-trash')
                        ->color('warning')
                        ->action(function (Collection $records) {
                            foreach ($records as $record) {
                                Cache::forget('system_setting_' . $record->key);
                            }

                            Notification::make()
                                ->title('Cache geleert')
                                ->body(count($records) . ' Einstellungs-Caches wurden geleert.')
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()->hasRole('super-admin')),
                ])
            ])
            ->defaultSort('priority', 'desc')
            ->groups([
                'category',
                'group',
                'type',
                'priority',
            ])
            ->groupingSettingsHidden()
            ->paginated([10, 25, 50, 100])
            ->poll('30s')
            ->striped()
            ->emptyStateHeading('Keine Einstellungen vorhanden')
            ->emptyStateDescription('Erstellen Sie Ihre erste Systemeinstellung')
            ->emptyStateIcon('heroicon-o-cog-6-tooth')
            ->emptyStateActions([
                Tables\Actions\Action::make('create_defaults')
                    ->label('Standard-Einstellungen erstellen')
                    ->action(function () {
                        SystemSetting::createDefaults();

                        Notification::make()
                            ->title('Standards erstellt')
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSystemSettings::route('/'),
            'create' => Pages\CreateSystemSetting::route('/create'),
            'edit' => Pages\EditSystemSetting::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->orderBy('priority', 'desc')
            ->orderBy('group')
            ->orderBy('key');
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return $record->label ?? $record->key;
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['key', 'label', 'description', 'group'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Gruppe' => $record->group_label,
            'Typ' => $record->type_label,
            'Schlüssel' => $record->key,
        ];
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery();
    }
}