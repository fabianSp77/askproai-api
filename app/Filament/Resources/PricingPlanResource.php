<?php

namespace App\Filament\Resources;

use Filament\Facades\Filament;

use App\Filament\Resources\PricingPlanResource\Pages;
use App\Models\PricingPlan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Filament\Support\Enums\FontWeight;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PricingPlanResource extends Resource
{
    protected static ?string $model = PricingPlan::class;

    /**
     * Resource disabled - pricing_plans table doesn't exist in Sept 21 database backup
     * TODO: Re-enable when database is fully restored
     */
    public static function shouldRegisterNavigation(): bool
    {
        // ✅ Super admin can see all resources
        $user = Filament::auth()->user();
        return $user && $user->hasRole('super_admin');
    }


    public static function canViewAny(): bool
    {
        // ✅ Super admin can access all resources
        $user = Filament::auth()->user();
        return $user && $user->hasRole('super_admin');
    }


    protected static ?string $navigationIcon = 'heroicon-o-sparkles';
    protected static ?string $navigationGroup = 'Abrechnung';
    protected static ?string $navigationLabel = 'Preispläne';
    protected static ?string $label = 'Preisplan';
    protected static ?string $pluralLabel = 'Preispläne';
    protected static ?int $navigationSort = 20;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationBadge(): ?string
    {
        return null; // EMERGENCY: Disabled to prevent memory exhaustion
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return null; // EMERGENCY: Disabled to prevent memory exhaustion
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'internal_name', 'description', 'category'];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Plan Details')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('Grunddaten')
                            ->icon('heroicon-o-information-circle')
                            ->schema([
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\Section::make('Plan-Identifikation')
                                            ->description('Grundlegende Informationen zum Preisplan')
                                            ->icon('heroicon-o-identification')
                                            ->schema([
                                                Forms\Components\TextInput::make('name')
                                                    ->label('Anzeigename')
                                                    ->placeholder('z.B. Professional')
                                                    ->required()
                                                    ->maxLength(255)
                                                    ->live()
                                                    ->afterStateUpdated(fn (Set $set, ?string $state) =>
                                                        $set('internal_name', str($state)->slug()->toString())
                                                    ),

                                                Forms\Components\TextInput::make('internal_name')
                                                    ->label('Interner Name')
                                                    ->placeholder('professional')
                                                    ->required()
                                                    ->maxLength(255)
                                                    ->unique(ignoreRecord: true)
                                                    ->helperText('Wird für API-Aufrufe verwendet'),

                                                Forms\Components\Select::make('category')
                                                    ->label('Kategorie')
                                                    ->options([
                                                        'starter' => '🌱 Starter',
                                                        'professional' => '💼 Professional',
                                                        'business' => '🏢 Business',
                                                        'enterprise' => '🚀 Enterprise',
                                                        'custom' => '⚙️ Custom',
                                                    ])
                                                    ->required()
                                                    ->native(false),

                                                Forms\Components\Select::make('billing_period')
                                                    ->label('Abrechnungszeitraum')
                                                    ->options([
                                                        'monthly' => 'Monatlich',
                                                        'yearly' => 'Jährlich',
                                                        'one_time' => 'Einmalig',
                                                        'custom' => 'Individuell',
                                                    ])
                                                    ->required()
                                                    ->default('monthly')
                                                    ->live(),
                                            ])->columns(2),

                                        Forms\Components\Section::make('Beschreibungen')
                                            ->description('Marketing- und Verkaufstexte')
                                            ->icon('heroicon-o-document-text')
                                            ->schema([
                                                Forms\Components\TextInput::make('tagline')
                                                    ->label('Tagline')
                                                    ->placeholder('Perfekt für wachsende Unternehmen')
                                                    ->maxLength(255)
                                                    ->helperText('Kurzer Werbetext'),

                                                Forms\Components\Textarea::make('description')
                                                    ->label('Beschreibung')
                                                    ->placeholder('Detaillierte Beschreibung des Plans...')
                                                    ->rows(3)
                                                    ->maxLength(1000)
                                                    ->columnSpanFull(),

                                                Forms\Components\RichEditor::make('long_description')
                                                    ->label('Ausführliche Beschreibung')
                                                    ->toolbarButtons([
                                                        'bold',
                                                        'italic',
                                                        'link',
                                                        'bulletList',
                                                        'orderedList',
                                                    ])
                                                    ->columnSpanFull(),
                                            ]),
                                    ]),
                            ]),

                        Forms\Components\Tabs\Tab::make('Preisgestaltung')
                            ->icon('heroicon-o-currency-euro')
                            ->schema([
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\Section::make('Basispreise')
                                            ->description('Grundpreise und Rabatte')
                                            ->icon('heroicon-o-banknotes')
                                            ->schema([
                                                Forms\Components\TextInput::make('price_monthly')
                                                    ->label('Monatspreis')
                                                    ->numeric()
                                                    ->prefix('€')
                                                    ->suffix('/ Monat')
                                                    ->required()
                                                    ->live()
                                                    ->afterStateUpdated(function (Set $set, Get $get, ?string $state) {
                                                        if ($state && $get('yearly_discount_percentage')) {
                                                            $yearlyPrice = $state * 12 * (1 - $get('yearly_discount_percentage') / 100);
                                                            $set('price_yearly', round($yearlyPrice, 2));
                                                        }
                                                    }),

                                                Forms\Components\TextInput::make('yearly_discount_percentage')
                                                    ->label('Jahresrabatt')
                                                    ->numeric()
                                                    ->suffix('%')
                                                    ->default(10)
                                                    ->minValue(0)
                                                    ->maxValue(50)
                                                    ->live()
                                                    ->helperText('Rabatt bei jährlicher Zahlung'),

                                                Forms\Components\TextInput::make('price_yearly')
                                                    ->label('Jahrespreis')
                                                    ->numeric()
                                                    ->prefix('€')
                                                    ->suffix('/ Jahr')
                                                    ->helperText('Wird automatisch berechnet'),

                                                Forms\Components\TextInput::make('setup_fee')
                                                    ->label('Einrichtungsgebühr')
                                                    ->numeric()
                                                    ->prefix('€')
                                                    ->default(0)
                                                    ->helperText('Einmalige Gebühr'),
                                            ])->columns(2),

                                        Forms\Components\Section::make('Anrufkontingente')
                                            ->description('Inkludierte Leistungen und Überziehungspreise')
                                            ->icon('heroicon-o-phone')
                                            ->schema([
                                                Forms\Components\TextInput::make('minutes_included')
                                                    ->label('Inkludierte Minuten')
                                                    ->numeric()
                                                    ->suffix('Minuten/Monat')
                                                    ->required()
                                                    ->default(0)
                                                    ->live(),

                                                Forms\Components\TextInput::make('sms_included')
                                                    ->label('Inkludierte SMS')
                                                    ->numeric()
                                                    ->suffix('SMS/Monat')
                                                    ->default(0),

                                                Forms\Components\TextInput::make('price_per_minute')
                                                    ->label('Preis pro Minute')
                                                    ->numeric()
                                                    ->prefix('€')
                                                    ->suffix('/ Minute')
                                                    ->step(0.001)
                                                    ->required()
                                                    ->helperText('Nach Verbrauch der inkludierten Minuten'),

                                                Forms\Components\TextInput::make('price_per_sms')
                                                    ->label('Preis pro SMS')
                                                    ->numeric()
                                                    ->prefix('€')
                                                    ->suffix('/ SMS')
                                                    ->step(0.001)
                                                    ->default(0.19),

                                                Forms\Components\Toggle::make('unlimited_minutes')
                                                    ->label('Unbegrenzte Minuten')
                                                    ->live()
                                                    ->afterStateUpdated(fn (Set $set, ?bool $state) =>
                                                        $state ? $set('minutes_included', 999999) : null
                                                    ),

                                                Forms\Components\Toggle::make('fair_use_policy')
                                                    ->label('Fair-Use-Policy')
                                                    ->helperText('Bei unbegrenzten Minuten')
                                                    ->visible(fn (Get $get) => $get('unlimited_minutes')),
                                            ])->columns(2),
                                    ]),
                            ]),

                        Forms\Components\Tabs\Tab::make('Features & Limits')
                            ->icon('heroicon-o-sparkles')
                            ->schema([
                                Forms\Components\Section::make('Inkludierte Features')
                                    ->description('Was ist in diesem Plan enthalten?')
                                    ->icon('heroicon-o-check-badge')
                                    ->schema([
                                        Forms\Components\Repeater::make('features')
                                            ->label('Features')
                                            ->schema([
                                                Forms\Components\Grid::make(12)
                                                    ->schema([
                                                        Forms\Components\TextInput::make('name')
                                                            ->label('Feature')
                                                            ->placeholder('24/7 Support')
                                                            ->required()
                                                            ->columnSpan(4),

                                                        Forms\Components\TextInput::make('value')
                                                            ->label('Wert/Menge')
                                                            ->placeholder('Unbegrenzt')
                                                            ->columnSpan(3),

                                                        Forms\Components\Select::make('type')
                                                            ->label('Typ')
                                                            ->options([
                                                                'boolean' => 'Ja/Nein',
                                                                'numeric' => 'Numerisch',
                                                                'text' => 'Text',
                                                            ])
                                                            ->default('boolean')
                                                            ->columnSpan(2),

                                                        Forms\Components\Toggle::make('is_highlighted')
                                                            ->label('Hervorheben')
                                                            ->columnSpan(2),

                                                        Forms\Components\TextInput::make('icon')
                                                            ->label('Icon')
                                                            ->placeholder('heroicon-o-check')
                                                            ->columnSpan(1),
                                                    ]),
                                            ])
                                            ->defaultItems(3)
                                            ->collapsible()
                                            ->cloneable()
                                            ->reorderableWithButtons()
                                            ->columnSpanFull(),
                                    ]),

                                Forms\Components\Section::make('Technische Limits')
                                    ->description('Technische Beschränkungen des Plans')
                                    ->icon('heroicon-o-adjustments-horizontal')
                                    ->collapsible()
                                    ->schema([
                                        Forms\Components\Grid::make(3)
                                            ->schema([
                                                Forms\Components\TextInput::make('max_users')
                                                    ->label('Max. Benutzer')
                                                    ->numeric()
                                                    ->suffix('Benutzer')
                                                    ->default(1),

                                                Forms\Components\TextInput::make('max_agents')
                                                    ->label('Max. Agenten')
                                                    ->numeric()
                                                    ->suffix('Agenten')
                                                    ->default(1),

                                                Forms\Components\TextInput::make('max_campaigns')
                                                    ->label('Max. Kampagnen')
                                                    ->numeric()
                                                    ->suffix('Kampagnen')
                                                    ->default(5),

                                                Forms\Components\TextInput::make('storage_gb')
                                                    ->label('Speicherplatz')
                                                    ->numeric()
                                                    ->suffix('GB')
                                                    ->default(10),

                                                Forms\Components\TextInput::make('api_calls_per_month')
                                                    ->label('API-Aufrufe')
                                                    ->numeric()
                                                    ->suffix('/ Monat')
                                                    ->default(10000),

                                                Forms\Components\TextInput::make('retention_days')
                                                    ->label('Datenaufbewahrung')
                                                    ->numeric()
                                                    ->suffix('Tage')
                                                    ->default(90),
                                            ]),
                                    ]),
                            ]),

                        Forms\Components\Tabs\Tab::make('Verfügbarkeit & Bedingungen')
                            ->icon('heroicon-o-calendar-days')
                            ->schema([
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\Section::make('Verfügbarkeit')
                                            ->description('Wann und wo ist der Plan verfügbar?')
                                            ->icon('heroicon-o-globe-europe-africa')
                                            ->schema([
                                                Forms\Components\DateTimePicker::make('available_from')
                                                    ->label('Verfügbar ab')
                                                    ->native(false)
                                                    ->displayFormat('d.m.Y H:i')
                                                    ->default(now()),

                                                Forms\Components\DateTimePicker::make('available_until')
                                                    ->label('Verfügbar bis')
                                                    ->native(false)
                                                    ->displayFormat('d.m.Y H:i')
                                                    ->after('available_from'),

                                                Forms\Components\Select::make('target_countries')
                                                    ->label('Zielländer')
                                                    ->multiple()
                                                    ->options([
                                                        'DE' => '🇩🇪 Deutschland',
                                                        'AT' => '🇦🇹 Österreich',
                                                        'CH' => '🇨🇭 Schweiz',
                                                        'EU' => '🇪🇺 Gesamte EU',
                                                    ])
                                                    ->default(['DE'])
                                                    ->required(),

                                                Forms\Components\Select::make('customer_types')
                                                    ->label('Kundentypen')
                                                    ->multiple()
                                                    ->options([
                                                        'b2b' => 'Business (B2B)',
                                                        'b2c' => 'Privatkunden (B2C)',
                                                        'enterprise' => 'Enterprise',
                                                        'government' => 'Behörden',
                                                        'nonprofit' => 'Non-Profit',
                                                    ])
                                                    ->default(['b2b', 'b2c']),

                                                Forms\Components\TextInput::make('min_contract_months')
                                                    ->label('Mindestlaufzeit')
                                                    ->numeric()
                                                    ->suffix('Monate')
                                                    ->default(1)
                                                    ->minValue(0),

                                                Forms\Components\TextInput::make('notice_period_days')
                                                    ->label('Kündigungsfrist')
                                                    ->numeric()
                                                    ->suffix('Tage')
                                                    ->default(30),
                                            ])->columns(2),

                                        Forms\Components\Section::make('Status & Sichtbarkeit')
                                            ->description('Aktivierung und Promotion')
                                            ->icon('heroicon-o-eye')
                                            ->schema([
                                                Forms\Components\Toggle::make('is_active')
                                                    ->label('Aktiv')
                                                    ->helperText('Plan ist buchbar')
                                                    ->default(true),

                                                Forms\Components\Toggle::make('is_visible')
                                                    ->label('Sichtbar')
                                                    ->helperText('Wird auf der Website angezeigt')
                                                    ->default(true),

                                                Forms\Components\Toggle::make('is_popular')
                                                    ->label('Beliebt')
                                                    ->helperText('Als "Beliebt" markieren'),

                                                Forms\Components\Toggle::make('is_new')
                                                    ->label('Neu')
                                                    ->helperText('Als "Neu" markieren'),

                                                Forms\Components\Toggle::make('requires_approval')
                                                    ->label('Genehmigung erforderlich')
                                                    ->helperText('Manuelle Freigabe nötig'),

                                                Forms\Components\Toggle::make('auto_upgrade_eligible')
                                                    ->label('Auto-Upgrade möglich')
                                                    ->helperText('Kann automatisch upgraded werden'),
                                            ])->columns(3),
                                    ]),
                            ]),

                        Forms\Components\Tabs\Tab::make('Erweiterte Einstellungen')
                            ->icon('heroicon-o-cog-6-tooth')
                            ->schema([
                                Forms\Components\Section::make('Technische Konfiguration')
                                    ->description('API und System-Einstellungen')
                                    ->icon('heroicon-o-code-bracket')
                                    ->collapsible()
                                    ->schema([
                                        Forms\Components\TextInput::make('stripe_price_id')
                                            ->label('Stripe Price ID')
                                            ->placeholder('price_1234567890')
                                            ->helperText('Für automatische Abrechnung'),

                                        Forms\Components\TextInput::make('stripe_product_id')
                                            ->label('Stripe Product ID')
                                            ->placeholder('prod_1234567890'),

                                        Forms\Components\Select::make('tax_category')
                                            ->label('Steuerkategorie')
                                            ->options([
                                                'standard' => 'Standard (19%)',
                                                'reduced' => 'Ermäßigt (7%)',
                                                'exempt' => 'Steuerfrei',
                                            ])
                                            ->default('standard'),

                                        Forms\Components\Textarea::make('metadata')
                                            ->label('Metadaten (JSON)')
                                            ->placeholder('{"key": "value"}')
                                            ->rows(3),
                                    ])->columns(2),

                                Forms\Components\Section::make('Benachrichtigungen')
                                    ->description('E-Mail-Vorlagen und Trigger')
                                    ->icon('heroicon-o-bell')
                                    ->collapsed()
                                    ->schema([
                                        Forms\Components\Select::make('welcome_email_template')
                                            ->label('Willkommens-E-Mail')
                                            ->options([
                                                'default' => 'Standard',
                                                'premium' => 'Premium',
                                                'enterprise' => 'Enterprise',
                                            ]),

                                        Forms\Components\Toggle::make('send_usage_alerts')
                                            ->label('Verbrauchswarnungen senden'),

                                        Forms\Components\TextInput::make('usage_alert_threshold')
                                            ->label('Warnschwelle')
                                            ->numeric()
                                            ->suffix('%')
                                            ->default(80)
                                            ->visible(fn (Get $get) => $get('send_usage_alerts')),
                                    ]),
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
                Tables\Columns\Layout\Split::make([
                    Tables\Columns\Layout\Stack::make([
                        Tables\Columns\TextColumn::make('name')
                            ->label('Plan')
                            ->searchable()
                            ->sortable()
                            ->weight(FontWeight::Bold)
                            ->size(Tables\Columns\TextColumn\TextColumnSize::Large),
                        Tables\Columns\TextColumn::make('category')
                            ->label('Kategorie')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'starter' => 'gray',
                                'professional' => 'info',
                                'business' => 'primary',
                                'enterprise' => 'warning',
                                'custom' => 'danger',
                                default => 'secondary',
                            })
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'starter' => '🌱 Starter',
                                'professional' => '💼 Professional',
                                'business' => '🏢 Business',
                                'enterprise' => '🚀 Enterprise',
                                'custom' => '⚙️ Custom',
                                default => $state,
                            }),
                        Tables\Columns\TextColumn::make('tagline')
                            ->label('Tagline')
                            ->color('gray')
                            ->size(Tables\Columns\TextColumn\TextColumnSize::Small),
                    ])->space(2),
                    Tables\Columns\Layout\Stack::make([
                        Tables\Columns\TextColumn::make('price_monthly')
                            ->label('Monatspreis')
                            ->money('EUR', locale: 'de_DE')
                            ->sortable()
                            ->weight(FontWeight::Bold)
                            ->size(Tables\Columns\TextColumn\TextColumnSize::Large),
                        Tables\Columns\TextColumn::make('yearly_discount_percentage')
                            ->label('Jahresrabatt')
                            ->formatStateUsing(fn (?string $state): string => $state ? "-{$state}%" : '-')
                            ->badge()
                            ->color('success')
                            ->visible(fn ($record) => $record && $record->yearly_discount_percentage > 0),
                        Tables\Columns\TextColumn::make('billing_period')
                            ->label('Abrechnung')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'monthly' => 'primary',
                                'yearly' => 'success',
                                'one_time' => 'warning',
                                'custom' => 'danger',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'monthly' => 'Monatlich',
                                'yearly' => 'Jährlich',
                                'one_time' => 'Einmalig',
                                'custom' => 'Individuell',
                                default => $state,
                            }),
                    ])->space(2),
                    Tables\Columns\Layout\Stack::make([
                        Tables\Columns\TextColumn::make('minutes_included')
                            ->label('Inkludierte Minuten')
                            ->formatStateUsing(fn (string $state): string =>
                                $state >= 999999 ? '∞ Minuten' : number_format($state, 0, ',', '.') . ' Min'
                            )
                            ->icon('heroicon-o-phone')
                            ->sortable(),
                        Tables\Columns\TextColumn::make('price_per_minute')
                            ->label('€/Minute')
                            ->money('EUR', locale: 'de_DE')
                            ->formatStateUsing(fn (string $state): string =>
                                $state > 0 ? number_format($state, 3, ',', '.') . ' €' : 'inkl.'
                            ),
                        Tables\Columns\TextColumn::make('sms_included')
                            ->label('SMS')
                            ->formatStateUsing(fn (?string $state): string =>
                                $state > 0 ? number_format($state, 0, ',', '.') . ' SMS' : '-'
                            )
                            ->icon('heroicon-o-chat-bubble-left')
                            ->visible(fn ($record) => $record && $record->sms_included > 0),
                    ])->space(2),
                    Tables\Columns\Layout\Stack::make([
                        Tables\Columns\TextColumn::make('features_count')
                            ->label('Features')
                            ->getStateUsing(fn ($record) => $record && is_array($record->features) ? count($record->features) : 0)
                            ->formatStateUsing(fn (string $state): string => $state . ' Features')
                            ->icon('heroicon-o-check-circle')
                            ->color('success'),
                        Tables\Columns\TextColumn::make('max_users')
                            ->label('Max. Benutzer')
                            ->formatStateUsing(fn (?string $state): string => $state ? $state . ' User' : '∞')
                            ->icon('heroicon-o-users'),
                        Tables\Columns\TextColumn::make('max_agents')
                            ->label('Max. Agenten')
                            ->formatStateUsing(fn (?string $state): string => $state ? $state . ' Agenten' : '∞')
                            ->icon('heroicon-o-cpu-chip'),
                    ])->space(2),
                    Tables\Columns\Layout\Stack::make([
                        Tables\Columns\ToggleColumn::make('is_active')
                            ->label('Aktiv'),
                        Tables\Columns\IconColumn::make('status_badges')
                            ->label('Status')
                            ->getStateUsing(fn ($record) => $record ? [
                                'popular' => $record->is_popular,
                                'new' => $record->is_new,
                                'visible' => $record->is_visible,
                            ] : [])
                            ->icons([
                                'heroicon-o-star' => fn ($state) => $state['popular'] ?? false,
                                'heroicon-o-sparkles' => fn ($state) => $state['new'] ?? false,
                                'heroicon-o-eye' => fn ($state) => $state['visible'] ?? false,
                            ])
                            ->colors([
                                'warning' => fn ($state) => $state['popular'] ?? false,
                                'success' => fn ($state) => $state['new'] ?? false,
                                'info' => fn ($state) => $state['visible'] ?? false,
                            ]),
                    ])->space(2),
                ])
                    ->from('md'),

                // Note: Active subscriptions and MRR are shown in the stats widget
                // to avoid N+1 query performance issues in the table listing

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Aktualisiert')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label('Kategorie')
                    ->multiple()
                    ->options([
                        'starter' => '🌱 Starter',
                        'professional' => '💼 Professional',
                        'business' => '🏢 Business',
                        'enterprise' => '🚀 Enterprise',
                        'custom' => '⚙️ Custom',
                    ]),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Aktiv')
                    ->trueLabel('Nur aktive')
                    ->falseLabel('Nur inaktive')
                    ->placeholder('Alle'),

                Tables\Filters\TernaryFilter::make('is_popular')
                    ->label('Beliebt')
                    ->trueLabel('Nur beliebte')
                    ->falseLabel('Nur normale')
                    ->placeholder('Alle'),

                Tables\Filters\TernaryFilter::make('unlimited_minutes')
                    ->label('Unbegrenzte Minuten')
                    ->trueLabel('Nur unbegrenzt')
                    ->falseLabel('Nur limitiert')
                    ->placeholder('Alle'),

                Tables\Filters\Filter::make('price_range')
                    ->form([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('price_from')
                                    ->label('Preis von')
                                    ->numeric()
                                    ->prefix('€'),
                                Forms\Components\TextInput::make('price_to')
                                    ->label('Preis bis')
                                    ->numeric()
                                    ->prefix('€'),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['price_from'], fn ($q, $price) => $q->where('price_monthly', '>=', $price))
                            ->when($data['price_to'], fn ($q, $price) => $q->where('price_monthly', '<=', $price));
                    }),

                Tables\Filters\Filter::make('has_subscriptions')
                    ->label('Mit aktiven Abonnements')
                    ->query(fn (Builder $query): Builder => $query->whereExists(function ($subquery) {
                        $subquery->select(DB::raw(1))
                            ->from('tenants')
                            ->whereColumn('tenants.pricing_plan', 'pricing_plans.internal_name')
                            ->where('tenants.is_active', true);
                    })),
            ])
            ->actions([
                Tables\Actions\Action::make('preview')
                    ->label('Vorschau')
                    ->icon('heroicon-o-eye')
                    ->modalHeading(fn ($record) => $record ? "Vorschau: {$record->name}" : 'Vorschau')
                    ->modalContent(fn ($record) => $record ? view('filament.resources.pricing-plan-preview', compact('record')) : null)
                    ->modalWidth('5xl'),

                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('duplicate')
                        ->label('Duplizieren')
                        ->icon('heroicon-o-document-duplicate')
                        ->requiresConfirmation()
                        ->action(function ($record) {
                            $newPlan = $record->replicate();
                            $newPlan->name = $record->name . ' (Kopie)';
                            $newPlan->internal_name = $record->internal_name . '-copy-' . time();
                            $newPlan->is_active = false;
                            $newPlan->save();
                        })
                        ->successNotificationTitle('Plan dupliziert'),

                    Tables\Actions\Action::make('toggle_popular')
                        ->label(fn ($record) => $record && $record->is_popular ? 'Als normal markieren' : 'Als beliebt markieren')
                        ->icon('heroicon-o-star')
                        ->action(fn ($record) => $record ? $record->update(['is_popular' => !$record->is_popular]) : null)
                        ->successNotificationTitle(fn ($record) => $record && $record->is_popular ? 'Als beliebt markiert' : 'Als normal markiert'),

                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make()
                        ->requiresConfirmation()
                        ->modalHeading('Plan löschen?')
                        ->modalDescription('Sind Sie sicher? Aktive Abonnements werden nicht gelöscht.')
                        ->modalSubmitActionLabel('Ja, löschen')
                        ->before(function ($record, Tables\Actions\DeleteAction $action) {
                            $hasActiveSubscriptions = \App\Models\Tenant::where('pricing_plan', $record->internal_name)
                                ->where('is_active', true)
                                ->exists();

                            if ($hasActiveSubscriptions) {
                                $action->cancel();
                                $action->failureNotificationTitle('Plan hat aktive Abonnements');
                            }
                        }),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('activate')
                    ->label('Aktivieren')
                    ->icon('heroicon-o-check')
                    ->action(fn ($records) => $records->each->update(['is_active' => true]))
                    ->deselectRecordsAfterCompletion()
                    ->requiresConfirmation(),

                Tables\Actions\BulkAction::make('deactivate')
                    ->label('Deaktivieren')
                    ->icon('heroicon-o-x-mark')
                    ->action(fn ($records) => $records->each->update(['is_active' => false]))
                    ->deselectRecordsAfterCompletion()
                    ->requiresConfirmation(),

                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Löschen')
                        ->before(function ($records, Tables\Actions\DeleteBulkAction $action) {
                            $hasActiveSubscriptions = false;
                            foreach ($records as $record) {
                                $count = \App\Models\Tenant::where('pricing_plan', $record->internal_name)
                                    ->where('is_active', true)
                                    ->exists();
                                if ($count) {
                                    $hasActiveSubscriptions = true;
                                    break;
                                }
                            }

                            if ($hasActiveSubscriptions) {
                                $action->cancel();
                                $action->failureNotificationTitle('Einige Pläne haben aktive Abonnements');
                            }
                        }),
                ]),
            ])
            ->defaultSort('price_monthly', 'asc')
            ->reorderable('sort_order')
            ->poll('60s');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Tabs::make('Plan Details')
                    ->tabs([
                        Infolists\Components\Tabs\Tab::make('Übersicht')
                            ->icon('heroicon-o-information-circle')
                            ->schema([
                                Infolists\Components\Grid::make(3)
                                    ->schema([
                                        Infolists\Components\Section::make('Plan-Informationen')
                                            ->schema([
                                                Infolists\Components\TextEntry::make('name')
                                                    ->label('Name')
                                                    ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                                                    ->weight(FontWeight::Bold),
                                                Infolists\Components\TextEntry::make('category')
                                                    ->label('Kategorie')
                                                    ->badge()
                                                    ->color(fn (string $state): string => match ($state) {
                                                        'starter' => 'gray',
                                                        'professional' => 'info',
                                                        'business' => 'primary',
                                                        'enterprise' => 'warning',
                                                        'custom' => 'danger',
                                                        default => 'secondary',
                                                    }),
                                                Infolists\Components\TextEntry::make('tagline')
                                                    ->label('Tagline'),
                                                Infolists\Components\TextEntry::make('description')
                                                    ->label('Beschreibung')
                                                    ->columnSpanFull(),
                                            ])->columnSpan(2),

                                        Infolists\Components\Section::make('Status')
                                            ->schema([
                                                Infolists\Components\IconEntry::make('is_active')
                                                    ->label('Aktiv')
                                                    ->boolean(),
                                                Infolists\Components\IconEntry::make('is_popular')
                                                    ->label('Beliebt')
                                                    ->boolean(),
                                                Infolists\Components\IconEntry::make('is_new')
                                                    ->label('Neu')
                                                    ->boolean(),
                                                Infolists\Components\TextEntry::make('active_subscriptions_count')
                                                    ->label('Aktive Abos')
                                                    ->badge()
                                                    ->color('success'),
                                            ])->columnSpan(1),
                                    ]),
                            ]),

                        Infolists\Components\Tabs\Tab::make('Preise')
                            ->icon('heroicon-o-currency-euro')
                            ->schema([
                                Infolists\Components\Grid::make(2)
                                    ->schema([
                                        Infolists\Components\Section::make('Grundpreise')
                                            ->schema([
                                                Infolists\Components\TextEntry::make('price_monthly')
                                                    ->label('Monatspreis')
                                                    ->money('EUR', locale: 'de_DE'),
                                                Infolists\Components\TextEntry::make('price_yearly')
                                                    ->label('Jahrespreis')
                                                    ->money('EUR', locale: 'de_DE'),
                                                Infolists\Components\TextEntry::make('yearly_discount_percentage')
                                                    ->label('Jahresrabatt')
                                                    ->formatStateUsing(fn (?string $state): string => $state ? "{$state}%" : '-'),
                                                Infolists\Components\TextEntry::make('setup_fee')
                                                    ->label('Einrichtungsgebühr')
                                                    ->money('EUR', locale: 'de_DE'),
                                            ]),

                                        Infolists\Components\Section::make('Kontingente')
                                            ->schema([
                                                Infolists\Components\TextEntry::make('minutes_included')
                                                    ->label('Inkludierte Minuten')
                                                    ->formatStateUsing(fn (string $state): string =>
                                                        $state >= 999999 ? '∞' : number_format($state, 0, ',', '.')
                                                    ),
                                                Infolists\Components\TextEntry::make('sms_included')
                                                    ->label('Inkludierte SMS')
                                                    ->formatStateUsing(fn (?string $state): string =>
                                                        $state ? number_format($state, 0, ',', '.') : '0'
                                                    ),
                                                Infolists\Components\TextEntry::make('price_per_minute')
                                                    ->label('Preis pro Minute')
                                                    ->money('EUR', locale: 'de_DE'),
                                                Infolists\Components\TextEntry::make('price_per_sms')
                                                    ->label('Preis pro SMS')
                                                    ->money('EUR', locale: 'de_DE'),
                                            ]),
                                    ]),
                            ]),

                        Infolists\Components\Tabs\Tab::make('Features')
                            ->icon('heroicon-o-sparkles')
                            ->schema([
                                Infolists\Components\RepeatableEntry::make('features')
                                    ->label('Inkludierte Features')
                                    ->schema([
                                        Infolists\Components\TextEntry::make('name')
                                            ->label('Feature')
                                            ->weight(FontWeight::Bold),
                                        Infolists\Components\TextEntry::make('value')
                                            ->label('Wert'),
                                        Infolists\Components\IconEntry::make('is_highlighted')
                                            ->label('Hervorgehoben')
                                            ->boolean(),
                                    ])
                                    ->columns(3),
                            ]),

                        Infolists\Components\Tabs\Tab::make('Technische Details')
                            ->icon('heroicon-o-cog')
                            ->schema([
                                Infolists\Components\Grid::make(3)
                                    ->schema([
                                        Infolists\Components\TextEntry::make('max_users')
                                            ->label('Max. Benutzer'),
                                        Infolists\Components\TextEntry::make('max_agents')
                                            ->label('Max. Agenten'),
                                        Infolists\Components\TextEntry::make('max_campaigns')
                                            ->label('Max. Kampagnen'),
                                        Infolists\Components\TextEntry::make('storage_gb')
                                            ->label('Speicherplatz')
                                            ->suffix(' GB'),
                                        Infolists\Components\TextEntry::make('api_calls_per_month')
                                            ->label('API-Aufrufe/Monat'),
                                        Infolists\Components\TextEntry::make('retention_days')
                                            ->label('Datenaufbewahrung')
                                            ->suffix(' Tage'),
                                        Infolists\Components\TextEntry::make('stripe_product_id')
                                            ->label('Stripe Product ID')
                                            ,
                                        Infolists\Components\TextEntry::make('stripe_price_id')
                                            ->label('Stripe Price ID')
                                            ,
                                    ]),
                            ]),

                        Infolists\Components\Tabs\Tab::make('Historie')
                            ->icon('heroicon-o-clock')
                            ->schema([
                                Infolists\Components\TextEntry::make('created_at')
                                    ->label('Erstellt')
                                    ->dateTime('d.m.Y H:i:s'),
                                Infolists\Components\TextEntry::make('updated_at')
                                    ->label('Zuletzt aktualisiert')
                                    ->dateTime('d.m.Y H:i:s'),
                            ]),
                    ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // Add subscription relation manager if needed
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPricingPlans::route('/'),
            'create' => Pages\CreatePricingPlan::route('/create'),
            'edit' => Pages\EditPricingPlan::route('/{record}/edit'),
            'view' => Pages\ViewPricingPlan::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery();
    }

    public static function getWidgets(): array
    {
        return [
            // Could add pricing statistics widgets
        ];
    }
}