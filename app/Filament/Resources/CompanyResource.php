<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CompanyResource\Pages;
use App\Filament\Resources\CompanyResource\RelationManagers;
use App\Filament\Traits\HasColumnOrdering;
use App\Models\Company;
use App\Models\Branch;
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

class CompanyResource extends Resource
{
    use HasColumnOrdering;

    protected static ?string $model = Company::class;
    protected static ?string $navigationIcon = 'heroicon-o-building-office';
    protected static ?string $navigationGroup = 'Stammdaten';
    protected static ?int $navigationSort = 4;
    protected static ?string $navigationLabel = 'Unternehmen';
    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationBadge(): ?string
    {
        return null; // EMERGENCY: Disabled to prevent memory exhaustion
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return null; // EMERGENCY: Disabled to prevent memory exhaustion
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->can('viewAny', static::getModel());
    }

    public static function canCreate(): bool
    {
        return auth()->user()->can('create', static::getModel());
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return auth()->user()->can('update', $record);
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return auth()->user()->can('delete', $record);
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()->hasRole(['admin']);
    }

    public static function canForceDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return auth()->user()->can('forceDelete', $record);
    }

    public static function canForceDeleteAny(): bool
    {
        return auth()->user()->hasRole(['super_admin']);
    }

    public static function canRestore(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return auth()->user()->can('restore', $record);
    }

    public static function canRestoreAny(): bool
    {
        return auth()->user()->hasRole(['admin']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Optimized to 4 logical tabs instead of massive single form
                Tabs::make('Company Details')
                    ->tabs([
                        // Tab 1: Essential Business Information
                        Tabs\Tab::make('Unternehmen')
                            ->icon('heroicon-m-building-office')
                            ->schema([
                                Section::make('Grundinformationen')
                                    ->schema([
                                        Grid::make(2)->schema([
                                            Forms\Components\TextInput::make('name')
                                                ->label('Firmenname')
                                                ->required()
                                                ->maxLength(255)
                                                ->placeholder('Muster GmbH')
                                                ->unique(ignoreRecord: true)
                                                ->validationMessages([
                                                    'required' => 'Der Firmenname ist erforderlich.',
                                                    'unique' => 'Dieser Firmenname existiert bereits.',
                                                ]),

                                            Forms\Components\Select::make('company_type')
                                                ->label('Unternehmenstyp')
                                                ->options([
                                                    'customer' => '🏢 Kunde',
                                                    'partner' => '🤝 Partner',
                                                    'reseller' => '🔄 Mandant',
                                                    'internal' => '🏠 Intern',
                                                ])
                                                ->default('customer')
                                                ->required()
                                                ->native(false),
                                        ]),

                                        Grid::make(3)->schema([
                                            Forms\Components\Toggle::make('is_active')
                                                ->label('Aktiv')
                                                ->default(true),

                                            Forms\Components\Toggle::make('is_white_label')
                                                ->label('White Label')
                                                ->default(false),

                                            Forms\Components\Toggle::make('can_make_outbound_calls')
                                                ->label('Ausgehende Anrufe'),
                                        ]),

                                        Grid::make(2)->schema([
                                            Forms\Components\TextInput::make('contact_person')
                                                ->label('Ansprechpartner')
                                                ->maxLength(255),

                                            Forms\Components\TextInput::make('phone')
                                                ->label('Telefon')
                                                ->tel()
                                                ->maxLength(255)
                                                ->regex('/^\+?[0-9\s\-\(\)]+$/')
                                                ->validationMessages([
                                                    'regex' => 'Bitte geben Sie eine gültige Telefonnummer ein.',
                                                ]),
                                        ]),

                                        Grid::make(2)->schema([
                                            Forms\Components\TextInput::make('email')
                                                ->label('E-Mail')
                                                ->email()
                                                ->maxLength(255)
                                                ->unique(ignoreRecord: true)
                                                ->validationMessages([
                                                    'email' => 'Bitte geben Sie eine gültige E-Mail-Adresse ein.',
                                                    'unique' => 'Diese E-Mail-Adresse wird bereits verwendet.',
                                                ]),

                                            Forms\Components\TextInput::make('website')
                                                ->label('Website')
                                                ->url()
                                                ->maxLength(255)
                                                ->validationMessages([
                                                    'url' => 'Bitte geben Sie eine gültige URL ein (z.B. https://example.com).',
                                                ]),
                                        ]),

                                        Forms\Components\Textarea::make('address')
                                            ->label('Adresse')
                                            ->rows(3)
                                            ->maxLength(500),

                                        Grid::make(3)->schema([
                                            Forms\Components\TextInput::make('city')
                                                ->label('Stadt')
                                                ->maxLength(255),

                                            Forms\Components\TextInput::make('postal_code')
                                                ->label('PLZ')
                                                ->maxLength(20)
                                                ->regex('/^[0-9]{5}$/')
                                                ->validationMessages([
                                                    'regex' => 'Bitte geben Sie eine gültige 5-stellige Postleitzahl ein.',
                                                ]),

                                            Forms\Components\TextInput::make('country')
                                                ->label('Land')
                                                ->default('DE')
                                                ->maxLength(2)
                                                ->regex('/^[A-Z]{2}$/')
                                                ->validationMessages([
                                                    'regex' => 'Bitte verwenden Sie einen 2-stelligen Ländercode (z.B. DE).',
                                                ]),
                                        ]),
                                    ]),
                            ]),

                        // Tab 2: Billing & Credits
                        Tabs\Tab::make('Billing')
                            ->icon('heroicon-m-currency-euro')
                            ->badge(fn ($record) => $record?->credit_balance ? '€' . number_format($record->credit_balance, 2) : null)
                            ->schema([
                                Section::make('Abrechnung & Guthaben')
                                    ->schema([
                                        Grid::make(3)->schema([
                                            Forms\Components\TextInput::make('credit_balance')
                                                ->label('Guthaben')
                                                ->prefix('€')
                                                ->numeric()
                                                ->default(0.00)
                                                ->minValue(0)
                                                ->maxValue(999999.99)
                                                ->step(0.01),

                                            Forms\Components\TextInput::make('low_credit_threshold')
                                                ->label('Warnschwelle')
                                                ->prefix('€')
                                                ->numeric()
                                                ->default(10.00)
                                                ->minValue(0)
                                                ->maxValue(1000)
                                                ->step(0.01),

                                            Forms\Components\TextInput::make('commission_rate')
                                                ->label('Provision')
                                                ->suffix('%')
                                                ->numeric()
                                                ->default(0.00)
                                                ->minValue(0)
                                                ->maxValue(100)
                                                ->step(0.01),
                                        ]),

                                        Grid::make(2)->schema([
                                            Forms\Components\Select::make('billing_status')
                                                ->label('Abrechnungsstatus')
                                                ->options([
                                                    'active' => '✅ Aktiv',
                                                    'suspended' => '⏸️ Pausiert',
                                                    'overdue' => '⚠️ Überfällig',
                                                    'blocked' => '🚫 Gesperrt',
                                                ])
                                                ->default('active')
                                                ->required(),

                                            Forms\Components\Select::make('billing_type')
                                                ->label('Abrechnungstyp')
                                                ->options([
                                                    'prepaid' => '💳 Prepaid',
                                                    'postpaid' => '📄 Postpaid',
                                                    'free' => '🆓 Kostenlos',
                                                ])
                                                ->default('prepaid')
                                                ->required(),
                                        ]),

                                        Grid::make(2)->schema([
                                            Forms\Components\TextInput::make('billing_contact_email')
                                                ->label('Billing E-Mail')
                                                ->email()
                                                ->maxLength(255),

                                            Forms\Components\TextInput::make('billing_contact_phone')
                                                ->label('Billing Telefon')
                                                ->tel()
                                                ->maxLength(255),
                                        ]),

                                        Grid::make(2)->schema([
                                            Forms\Components\Toggle::make('prepaid_billing_enabled')
                                                ->label('Prepaid Billing'),

                                            Forms\Components\Toggle::make('alerts_enabled')
                                                ->label('Alerts aktiviert'),
                                        ]),
                                    ]),
                            ]),

                        // Tab 3: Settings & Preferences
                        Tabs\Tab::make('Einstellungen')
                            ->icon('heroicon-m-cog')
                            ->schema([
                                Section::make('Präferenzen & Einstellungen')
                                    ->schema([
                                        Grid::make(3)->schema([
                                            Forms\Components\TextInput::make('timezone')
                                                ->label('Zeitzone')
                                                ->default('Europe/Berlin')
                                                ->maxLength(50),

                                            Forms\Components\TextInput::make('default_language')
                                                ->label('Sprache')
                                                ->default('de')
                                                ->maxLength(5),

                                            Forms\Components\TextInput::make('currency')
                                                ->label('Währung')
                                                ->default('EUR')
                                                ->maxLength(3),
                                        ]),

                                        Grid::make(2)->schema([
                                            Forms\Components\TextInput::make('industry')
                                                ->label('Branche')
                                                ->maxLength(50),

                                            Forms\Components\Select::make('subscription_status')
                                                ->label('Abo Status')
                                                ->options([
                                                    'trial' => '🆓 Trial',
                                                    'active' => '✅ Aktiv',
                                                    'cancelled' => '❌ Gekündigt',
                                                    'expired' => '⏰ Abgelaufen',
                                                ]),
                                        ]),

                                        Grid::make(3)->schema([
                                            Forms\Components\Toggle::make('send_call_summaries')
                                                ->label('Call Summaries senden'),

                                            Forms\Components\Toggle::make('email_notifications_enabled')
                                                ->label('E-Mail Benachrichtigungen'),

                                            Forms\Components\Toggle::make('auto_translate')
                                                ->label('Auto-Übersetzung'),
                                        ]),
                                    ]),
                            ]),

                        // Tab 4: Policies
                        Tabs\Tab::make('Richtlinien')
                            ->icon('heroicon-m-shield-check')
                            ->schema([
                                static::getPolicySection('cancellation', 'Stornierungsrichtlinie'),
                                static::getPolicySection('reschedule', 'Umbuchungsrichtlinie'),
                                static::getPolicySection('recurring', 'Wiederholungsrichtlinie'),
                            ]),

                        // Tab 5: Integration & API (Admin only)
                        Tabs\Tab::make('Integration')
                            ->icon('heroicon-m-bolt')
                            ->visible(fn () => auth()->user()?->hasRole('admin'))
                            ->schema([
                                Section::make('API & Integration')
                                    ->schema([
                                        // Retell Integration
                                        Forms\Components\Section::make('Retell Integration')
                                            ->schema([
                                                Grid::make(2)->schema([
                                                    Forms\Components\Textarea::make('retell_api_key')
                                                        ->label('Retell API Key')
                                                        ->rows(2)
                                                        ->helperText('API Key für Retell Phone System'),

                                                    Forms\Components\TextInput::make('retell_agent_id')
                                                        ->label('Default Retell Agent ID')
                                                        ->maxLength(255)
                                                        ->helperText('Standard Agent für neue Telefonnummern'),
                                                ]),

                                                Forms\Components\Toggle::make('retell_enabled')
                                                    ->label('Retell Integration aktiv')
                                                    ->default(true),
                                            ])
                                            ->collapsible()
                                            ->collapsed(false),

                                        // Cal.com Integration
                                        Forms\Components\Section::make('Cal.com Integration')
                                            ->schema([
                                                Grid::make(2)->schema([
                                                    Forms\Components\Textarea::make('calcom_api_key')
                                                        ->label('Cal.com API Key')
                                                        ->rows(2)
                                                        ->helperText('API Key für Cal.com Integration'),

                                                    Forms\Components\TextInput::make('calcom_team_slug')
                                                        ->label('Cal.com Team Slug')
                                                        ->maxLength(255)
                                                        ->helperText('Team-Slug in Cal.com'),
                                                ]),

                                                Grid::make(2)->schema([
                                                    Forms\Components\TextInput::make('calcom_team_id')
                                                        ->label('Cal.com Team ID')
                                                        ->numeric()
                                                        ->required()
                                                        ->helperText('WICHTIG: Team ID für Event Type Zuordnung'),

                                                    Forms\Components\TextInput::make('calcom_team_name')
                                                        ->label('Cal.com Team Name')
                                                        ->maxLength(255)
                                                        ->disabled()
                                                        ->helperText('Wird automatisch beim Sync aktualisiert'),
                                                ]),

                                                // Team sync status display
                                                Grid::make(3)->schema([
                                                    Forms\Components\Placeholder::make('team_sync_status_display')
                                                        ->label('Team Sync Status')
                                                        ->content(function ($record) {
                                                            if (!$record) return 'Not synced';

                                                            $status = $record->team_sync_status ?? 'pending';
                                                            return match($status) {
                                                                'synced' => '✓ Synchronized',
                                                                'syncing' => '⟳ Syncing...',
                                                                'error' => '✗ Error',
                                                                'pending' => '○ Pending',
                                                                default => 'Unknown'
                                                            };
                                                        }),

                                                    Forms\Components\Placeholder::make('last_team_sync_display')
                                                        ->label('Last Sync')
                                                        ->content(function ($record) {
                                                            if (!$record || !$record->last_team_sync) {
                                                                return 'Never';
                                                            }
                                                            return $record->last_team_sync->diffForHumans();
                                                        }),

                                                    Forms\Components\Placeholder::make('team_stats')
                                                        ->label('Team Stats')
                                                        ->content(function ($record) {
                                                            if (!$record) return 'No data';

                                                            $members = $record->team_member_count ?? 0;
                                                            $eventTypes = $record->team_event_type_count ?? 0;
                                                            return $members . ' Members, ' . $eventTypes . ' Event Types';
                                                        }),
                                                ]),

                                                // Show sync error if exists
                                                Forms\Components\Placeholder::make('team_sync_error_display')
                                                    ->label('Sync Error')
                                                    ->content(function ($record) {
                                                        if (!$record || !$record->team_sync_error) {
                                                            return '';
                                                        }
                                                        return $record->team_sync_error;
                                                    })
                                                    ->visible(function ($record) {
                                                        return $record && !empty($record->team_sync_error);
                                                    }),

                                                Grid::make(2)->schema([
                                                    Forms\Components\TextInput::make('calcom_user_id')
                                                        ->label('Cal.com User ID (deprecated)')
                                                        ->maxLength(255)
                                                        ->disabled(),

                                                    Forms\Components\Select::make('calcom_calendar_mode')
                                                        ->label('Kalender Modus')
                                                        ->options([
                                                            'zentral' => '🏢 Zentral (Team)',
                                                            'filiale' => '🏪 Filiale (Branch)',
                                                            'mitarbeiter' => '👤 Mitarbeiter (Individual)',
                                                        ])
                                                        ->default('zentral')
                                                        ->helperText('Bestimmt wie Termine verwaltet werden'),
                                                ]),
                                            ])
                                            ->collapsible()
                                            ->collapsed(false),

                                        // Notification Settings
                                        Forms\Components\Section::make('Benachrichtigungen')
                                            ->schema([
                                                Grid::make(2)->schema([
                                                    Forms\Components\Toggle::make('send_call_summaries')
                                                        ->label('Anrufzusammenfassungen senden'),

                                                    Forms\Components\Toggle::make('include_transcript_in_summary')
                                                        ->label('Transkript in Zusammenfassung'),
                                                ]),

                                                Forms\Components\Textarea::make('call_summary_recipients')
                                                    ->label('Empfänger für Zusammenfassungen')
                                                    ->rows(2)
                                                    ->placeholder('email1@example.com, email2@example.com')
                                                    ->helperText('Komma-getrennte E-Mail-Adressen'),

                                                Grid::make(2)->schema([
                                                    Forms\Components\Toggle::make('email_notifications_enabled')
                                                        ->label('E-Mail Benachrichtigungen'),

                                                    Forms\Components\Toggle::make('calcom_handles_notifications')
                                                        ->label('Cal.com übernimmt Benachrichtigungen'),
                                                ]),
                                            ])
                                            ->collapsible()
                                            ->collapsed(true),

                                        // Other Integrations
                                        Forms\Components\Section::make('Weitere Integrationen')
                                            ->schema([
                                                Grid::make(2)->schema([
                                                    Forms\Components\TextInput::make('stripe_customer_id')
                                                        ->label('Stripe Customer ID')
                                                        ->maxLength(255)
                                                        ->disabled(),

                                                    Forms\Components\TextInput::make('webhook_signing_secret')
                                                        ->label('Webhook Secret')
                                                        ->maxLength(255)
                                                        ->password(),
                                                ]),
                                            ])
                                            ->collapsible()
                                            ->collapsed(true),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        $table = $table
            // Performance: Eager load relationships and count aggregates
            ->modifyQueryUsing(fn (Builder $query) =>
                $query->withCount(['branches', 'staff'])
                    ->with(['branches' => fn ($q) => $q->limit(3)])
            )
            // Optimized to 9 essential columns with rich visual information
            ->columns([
                // Company name with status indicator
                Tables\Columns\TextColumn::make('name')
                    ->label('Unternehmen')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->toggleable()
                    ->description(fn ($record) =>
                        ($record->city ? $record->city . ' • ' : '') .
                        $record->company_type
                    )
                    ->icon(fn ($record) => match($record->company_type) {
                        'customer' => 'heroicon-m-building-office',
                        'partner' => 'heroicon-m-handshake',
                        'reseller' => 'heroicon-m-arrow-path',
                        'internal' => 'heroicon-m-home',
                        default => 'heroicon-m-building-office',
                    }),

                // Contact information in compact format
                Tables\Columns\TextColumn::make('contact_info')
                    ->label('Kontakt')
                    ->toggleable()
                    ->getStateUsing(fn ($record) =>
                        $record->contact_person .
                        ($record->phone ? ' • ' . $record->phone : '') .
                        ($record->email ? ' • ' . $record->email : '')
                    )
                    ->searchable(['contact_person', 'phone', 'email'])
                    
                    ->limit(50)
                    ->tooltip(fn ($record) =>
                        "Ansprechpartner: {$record->contact_person}\n" .
                        "Telefon: {$record->phone}\n" .
                        "E-Mail: {$record->email}"
                    ),

                // Business status with visual coding
                Tables\Columns\TextColumn::make('company_type')
                    ->label('Typ')
                    ->toggleable()
                    ->badge()
                    ->colors([
                        'success' => 'customer',
                        'info' => 'partner',
                        'warning' => 'reseller',
                        'gray' => 'internal',
                    ])
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'customer' => '🏢 Kunde',
                        'partner' => '🤝 Partner',
                        'reseller' => '🔄 Mandant',
                        'internal' => '🏠 Intern',
                        default => $state,
                    }),

                // Billing status with clear indicators
                Tables\Columns\TextColumn::make('billing_status')
                    ->label('Billing')
                    ->toggleable()
                    ->badge()
                    ->colors([
                        'success' => 'active',
                        'warning' => 'suspended',
                        'danger' => ['overdue', 'blocked'],
                    ])
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'active' => '✅ Aktiv',
                        'suspended' => '⏸️ Pausiert',
                        'overdue' => '⚠️ Überfällig',
                        'blocked' => '🚫 Gesperrt',
                        default => $state,
                    }),

                // Credit balance with color coding
                Tables\Columns\TextColumn::make('credit_balance')
                    ->label('Guthaben')
                    ->toggleable()
                    ->money('EUR')
                    ->sortable()
                    ->alignEnd()
                    ->weight('bold')
                    ->color(fn ($state, $record) =>
                        $state <= $record->low_credit_threshold ? 'danger' :
                        ($state <= ($record->low_credit_threshold * 2) ? 'warning' : 'success')
                    )
                    ->description(fn ($record) =>
                        'Warnung bei €' . number_format($record->low_credit_threshold, 2)
                    ),

                // Branch & staff count
                Tables\Columns\TextColumn::make('infrastructure')
                    ->label('Infrastruktur')
                    ->toggleable()
                    ->getStateUsing(fn ($record) =>
                        '🏪 ' . $record->branches_count . ' Filialen • ' .
                        '👥 ' . $record->staff_count . ' Mitarbeiter'
                    )
                    ->badge()
                    ->color('info'),

                // Profit column (only for admin/reseller)
                Tables\Columns\TextColumn::make('total_profit')
                    ->label('Profit (30 Tage) 💰')
                    ->getStateUsing(function ($record) {
                        $user = auth()->user();
                        $isSuperAdmin = $user->hasRole(['super-admin', 'super_admin', 'Super Admin']);
                        $isReseller = $user->hasRole(['reseller_admin', 'reseller_owner', 'reseller_support']);

                        if (!$isSuperAdmin && !$isReseller) return '---';

                        // Calculate last 30 days profit
                        $query = $record->calls()
                            ->whereBetween('created_at', [now()->subDays(30), now()]);

                        if ($isSuperAdmin) {
                            $profit = $query->sum('total_profit');
                        } elseif ($isReseller && $record->parent_company_id == $user->company_id) {
                            $profit = $query->sum('reseller_profit');
                        } else {
                            return '---';
                        }

                        return '€' . number_format($profit / 100, 2, ',', '.');
                    })
                    ->badge()
                    ->color(fn ($state) =>
                        $state === '---' ? 'gray' :
                        (floatval(str_replace(['€', '.', ','], ['', '', '.'], $state)) > 0 ? 'success' : 'danger')
                    )
                    ->visible(fn () =>
                        auth()->user()->hasRole(['super-admin', 'super_admin', 'Super Admin']) ||
                        auth()->user()->hasRole(['reseller_admin', 'reseller_owner', 'reseller_support'])
                    ),

                // Subscription status
                Tables\Columns\TextColumn::make('subscription_status')
                    ->label('Abo')
                    ->badge()
                    ->colors([
                        'info' => 'trial',
                        'success' => 'active',
                        'danger' => 'cancelled',
                        'gray' => 'expired',
                    ])
                    ->formatStateUsing(fn ($state) => match($state) {
                        'trial' => '🆓 Trial',
                        'active' => '✅ Aktiv',
                        'cancelled' => '❌ Gekündigt',
                        'expired' => '⏰ Abgelaufen',
                        default => $state ?: '➖ Unbekannt',
                    })
                    ->toggleable(),

                // Activity status
                Tables\Columns\TextColumn::make('activity_status')
                    ->label('Status')
                    ->toggleable()
                    ->getStateUsing(fn ($record) =>
                        $record->is_active ?
                        ($record->can_make_outbound_calls ? '🟢 Voll aktiv' : '🟡 Eingeschränkt') :
                        '🔴 Inaktiv'
                    )
                    ->badge()
                    ->color(fn ($record) =>
                        $record->is_active ?
                        ($record->can_make_outbound_calls ? 'success' : 'warning') :
                        'danger'
                    ),

                // Last activity (hidden by default)
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Aktualisiert')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                // Additional important columns (hidden by default)
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('email')
                    ->label('E-Mail')
                    ->searchable()
                    ->copyable()
                    ->icon('heroicon-m-envelope')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('phone')
                    ->label('Telefon')
                    ->searchable()
                    ->copyable()
                    ->icon('heroicon-m-phone')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('address')
                    ->label('Adresse')
                    ->searchable()
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('city')
                    ->label('Stadt')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('website')
                    ->label('Website')
                    ->searchable()
                    ->url(fn ($state) => $state)
                    ->openUrlInNewTab()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('archived_at')
                    ->label('Archiviert am')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->placeholder('Nicht archiviert')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('trial_ends_at')
                    ->label('Trial endet')
                    ->dateTime('d.m.Y')
                    ->sortable()
                    ->color(fn ($state) =>
                        $state && $state->isPast() ? 'danger' :
                        ($state && $state->diffInDays(now()) < 7 ? 'warning' : 'success'))
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('calcom_team_id')
                    ->label('Cal.com Team ID')
                    ->searchable()
                    ->placeholder('Nicht konfiguriert')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('retell_agent_id')
                    ->label('Retell Agent ID')
                    ->searchable()
                    ->placeholder('Nicht konfiguriert')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('low_credit_threshold')
                    ->label('Warnschwelle')
                    ->money('EUR')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\BooleanColumn::make('is_white_label')
                    ->label('White Label')
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\BooleanColumn::make('can_make_outbound_calls')
                    ->label('Outbound erlaubt')
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            // Smart business filters
            ->filters([
                // Quick filters for company management
                SelectFilter::make('company_type')
                    ->label('Unternehmenstyp')
                    ->multiple()
                    ->options([
                        'customer' => '🏢 Kunde',
                        'partner' => '🤝 Partner',
                        'reseller' => '🔄 Mandant',
                        'internal' => '🏠 Intern',
                    ]),

                SelectFilter::make('billing_status')
                    ->label('Billing Status')
                    ->options([
                        'active' => 'Aktiv',
                        'suspended' => 'Pausiert',
                        'overdue' => 'Überfällig',
                        'blocked' => 'Gesperrt',
                    ]),

                Filter::make('low_credit')
                    ->label('Niedriges Guthaben')
                    ->query(fn (Builder $query): Builder =>
                        $query->whereRaw('credit_balance <= low_credit_threshold')
                    ),

                Filter::make('active_companies')
                    ->label('Aktive Unternehmen')
                    ->query(fn (Builder $query): Builder =>
                        $query->where('is_active', true)->whereNull('archived_at')
                    )
                    ->default(),

                Filter::make('archived')
                    ->label('Archivierte Unternehmen')
                    ->query(fn (Builder $query): Builder =>
                        $query->whereNotNull('archived_at')
                    ),

                Filter::make('trial_companies')
                    ->label('Trial Unternehmen')
                    ->query(fn (Builder $query): Builder =>
                        $query->where('subscription_status', 'trial')
                    ),

                Filter::make('white_label')
                    ->label('White Label')
                    ->query(fn (Builder $query): Builder =>
                        $query->where('is_white_label', true)
                    ),

                SelectFilter::make('subscription_status')
                    ->label('Abo Status')
                    ->options([
                        'trial' => '🆓 Trial',
                        'active' => '✅ Aktiv',
                        'cancelled' => '❌ Gekündigt',
                        'expired' => '⏰ Abgelaufen',
                    ]),

                Filter::make('has_calcom')
                    ->label('Mit Cal.com Integration')
                    ->query(fn (Builder $query): Builder =>
                        $query->whereNotNull('calcom_team_id')
                    ),

                Filter::make('has_retell')
                    ->label('Mit Retell Integration')
                    ->query(fn (Builder $query): Builder =>
                        $query->whereNotNull('retell_agent_id')
                    ),

                Filter::make('created_this_month')
                    ->label('Diesen Monat erstellt')
                    ->query(fn (Builder $query): Builder =>
                        $query->where('created_at', '>=', now()->startOfMonth())
                    ),

                Filter::make('trial_ending_soon')
                    ->label('Trial endet bald')
                    ->query(fn (Builder $query): Builder =>
                        $query->whereNotNull('trial_ends_at')
                              ->where('trial_ends_at', '>=', now())
                              ->where('trial_ends_at', '<=', now()->addDays(7))
                    ),
            ])
            ->filtersLayout(Tables\Enums\FiltersLayout::AboveContent)
            ->filtersFormColumns(4)
            // Quick actions for company management
            ->actions([
                Tables\Actions\ActionGroup::make([
                    // Credit management
                    Tables\Actions\Action::make('addCredit')
                        ->label('Guthaben aufladen')
                        ->icon('heroicon-m-plus-circle')
                        ->color('success')
                        ->form([
                            Forms\Components\TextInput::make('amount')
                                ->label('Betrag')
                                ->prefix('€')
                                ->numeric()
                                ->required()
                                ->rules(['min:1', 'max:10000']),
                            Forms\Components\Textarea::make('note')
                                ->label('Notiz')
                                ->rows(2),
                        ])
                        ->action(function ($record, array $data) {
                            $oldBalance = $record->credit_balance;
                            $record->increment('credit_balance', $data['amount']);

                            // TODO: Log transaction

                            Notification::make()
                                ->title('Guthaben aufgeladen')
                                ->body("€{$data['amount']} hinzugefügt. Neues Guthaben: €{$record->fresh()->credit_balance}")
                                ->success()
                                ->send();
                        }),

                    // Branch management
                    Tables\Actions\Action::make('manageBranches')
                        ->label('Filialen verwalten')
                        ->icon('heroicon-m-building-storefront')
                        ->color('info')
                        ->url(fn ($record) => route('filament.admin.resources.branches.index', [
                            'tableFilters' => ['company_id' => ['value' => $record->id]]
                        ])),

                    // Cal.com Team Sync
                    Tables\Actions\Action::make('syncTeamEventTypes')
                        ->label('Team Event Types synchronisieren')
                        ->icon('heroicon-m-arrow-path')
                        ->color('primary')
                        ->visible(function ($record) {
                            return $record && $record->calcom_team_id > 0;
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Team Event Types synchronisieren')
                        ->modalDescription(function ($record) {
                            if (!$record) return '';
                            return 'Dies wird alle Event Types für Team ID ' . $record->calcom_team_id . ' importieren und als Services anlegen.';
                        })
                        ->modalIcon('heroicon-o-arrow-path')
                        ->action(function ($record) {
                            try {
                                // Dispatch the sync job
                                dispatch(new \App\Jobs\ImportTeamEventTypesJob($record));

                                $teamName = $record->calcom_team_name ?: ('ID: ' . $record->calcom_team_id);

                                Notification::make()
                                    ->title('Team Sync gestartet')
                                    ->body('Event Types für Team ' . $teamName . ' werden synchronisiert...')
                                    ->success()
                                    ->send();
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('Sync fehlgeschlagen')
                                    ->body('Fehler: ' . $e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),

                    // Status management
                    Tables\Actions\Action::make('toggleStatus')
                        ->label('Status ändern')
                        ->icon('heroicon-m-power')
                        ->color('warning')
                        ->form([
                            Forms\Components\Toggle::make('is_active')
                                ->label('Aktiv')
                                ->default(fn ($record) => $record->is_active),
                            Forms\Components\Toggle::make('can_make_outbound_calls')
                                ->label('Ausgehende Anrufe erlaubt')
                                ->default(fn ($record) => $record->can_make_outbound_calls),
                            Forms\Components\Select::make('billing_status')
                                ->label('Billing Status')
                                ->options([
                                    'active' => 'Aktiv',
                                    'suspended' => 'Pausiert',
                                    'overdue' => 'Überfällig',
                                    'blocked' => 'Gesperrt',
                                ])
                                ->default(fn ($record) => $record->billing_status),
                        ])
                        ->action(function ($record, array $data) {
                            $record->update($data);

                            Notification::make()
                                ->title('Status aktualisiert')
                                ->body('Unternehmensstatus wurde erfolgreich geändert.')
                                ->success()
                                ->send();
                        }),

                    // Communication
                    Tables\Actions\Action::make('sendNotification')
                        ->label('Benachrichtigung senden')
                        ->icon('heroicon-m-bell')
                        ->color('gray')
                        ->form([
                            Forms\Components\Select::make('type')
                                ->label('Typ')
                                ->options([
                                    'billing' => 'Billing',
                                    'technical' => 'Technisch',
                                    'general' => 'Allgemein',
                                ])
                                ->required(),
                            Forms\Components\TextInput::make('subject')
                                ->label('Betreff')
                                ->required(),
                            Forms\Components\Textarea::make('message')
                                ->label('Nachricht')
                                ->required()
                                ->rows(4),
                        ])
                        ->action(function ($record, array $data) {
                            // TODO: Send notification

                            Notification::make()
                                ->title('Benachrichtigung gesendet')
                                ->body("Benachrichtigung an {$record->name} wurde gesendet.")
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),

                    // Smart Delete - One button that handles everything
                    Tables\Actions\DeleteAction::make()
                        ->requiresConfirmation()
                        ->modalHeading(fn ($record) => "Löschen: {$record->name}")
                        ->modalDescription(function ($record) {
                            $branches = $record->branches()->count();
                            $staff = $record->staff()->count();
                            $customers = $record->customers()->count();
                            $phones = $record->phoneNumbers()->count();
                            $calls = \App\Models\Call::where('company_id', $record->id)->count();
                            $appointments = \App\Models\Appointment::where('company_id', $record->id)->count();

                            $hasData = ($branches + $staff + $customers + $phones + $calls + $appointments) > 0;

                            if (!$hasData) {
                                return "✅ Dieses Unternehmen hat keine verknüpften Daten und kann gelöscht werden.";
                            }

                            $details = [];
                            if ($branches > 0) $details[] = "• {$branches} Filialen";
                            if ($staff > 0) $details[] = "• {$staff} Mitarbeiter";
                            if ($customers > 0) $details[] = "• {$customers} Kunden";
                            if ($phones > 0) $details[] = "• {$phones} Telefonnummern";
                            if ($calls > 0) $details[] = "• {$calls} Anrufe";
                            if ($appointments > 0) $details[] = "• {$appointments} Termine";

                            return "📊 DIESES UNTERNEHMEN HAT VERKNÜPFTE DATEN:\n\n" .
                                   implode("\n", $details) . "\n\n" .
                                   "🧹 Möchten Sie das Unternehmen MIT ALLEN verknüpften Daten löschen?\n\n" .
                                   "⚠️ WARNUNG: Diese Aktion kann NICHT rückgängig gemacht werden!";
                        })
                        ->modalSubmitActionLabel(function ($record) {
                            $hasData = ($record->branches()->count() +
                                       $record->staff()->count() +
                                       $record->customers()->count() +
                                       $record->phoneNumbers()->count() > 0);

                            return $hasData ? 'Ja, mit allen Daten löschen!' : 'Löschen';
                        })
                        ->modalCancelActionLabel('Abbrechen')
                        ->action(function ($record) {
                            $hasRelatedData = ($record->branches()->count() +
                                              $record->staff()->count() +
                                              $record->customers()->count() +
                                              $record->phoneNumbers()->count() +
                                              \App\Models\Call::where('company_id', $record->id)->count() +
                                              \App\Models\Appointment::where('company_id', $record->id)->count()) > 0;

                            if ($hasRelatedData) {
                                // Force delete with cleanup
                                try {
                                    $record->services()->delete();
                                    $record->staff()->delete();
                                    $record->customers()->delete();
                                    $record->phoneNumbers()->delete();
                                    \App\Models\Appointment::where('company_id', $record->id)->delete();
                                    \App\Models\Call::where('company_id', $record->id)->delete();
                                    \App\Models\WorkingHour::where('company_id', $record->id)->delete();
                                    \App\Models\PricingPlan::where('company_id', $record->id)->delete();
                                    $record->branches()->delete();
                                    $record->delete();

                                    \Filament\Notifications\Notification::make()
                                        ->title('Komplett gelöscht')
                                        ->body("'{$record->name}' und alle verknüpften Daten wurden gelöscht.")
                                        ->success()
                                        ->send();
                                } catch (\Exception $e) {
                                    \Filament\Notifications\Notification::make()
                                        ->title('Fehler beim Löschen')
                                        ->body('Es ist ein Fehler aufgetreten: ' . $e->getMessage())
                                        ->danger()
                                        ->send();
                                    throw $e;
                                }
                            } else {
                                // Simple delete - no related data
                                $record->delete();
                                \Filament\Notifications\Notification::make()
                                    ->title('Gelöscht')
                                    ->body("'{$record->name}' wurde gelöscht.")
                                    ->success()
                                    ->send();
                            }
                        }),

                    // Archive Company
                    Tables\Actions\Action::make('archive')
                        ->label('Archivieren')
                        ->icon('heroicon-o-archive-box')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading(fn ($record) => "Archivieren: {$record->name}")
                        ->modalDescription('Das Unternehmen wird archiviert und aus allen aktiven Listen entfernt. Alle Daten bleiben für Reporting und Compliance erhalten.')
                        ->form([
                            \Filament\Forms\Components\Textarea::make('archive_reason')
                                ->label('Grund für Archivierung')
                                ->placeholder('z.B. Vertrag beendet, Insolvenz, Fusion...')
                                ->required()
                                ->rows(3),
                        ])
                        ->modalSubmitActionLabel('Jetzt archivieren')
                        ->modalCancelActionLabel('Abbrechen')
                        ->action(function ($record, array $data) {
                            $record->update([
                                'archived_at' => now(),
                                'archive_reason' => $data['archive_reason'],
                                'archived_by' => auth()->user()->email,
                                'is_active' => false,
                                'billing_status' => 'blocked',
                            ]);

                            // Deactivate all branches
                            $record->branches()->update(['active' => false]);

                            // Deactivate all services
                            $record->services()->update(['is_active' => false]);

                            \Filament\Notifications\Notification::make()
                                ->title('Unternehmen archiviert')
                                ->body("'{$record->name}' wurde erfolgreich archiviert.")
                                ->success()
                                ->send();
                        })
                        ->visible(fn ($record) => !$record->archived_at),

                    // Restore from Archive
                    Tables\Actions\Action::make('restore')
                        ->label('Wiederherstellen')
                        ->icon('heroicon-o-arrow-uturn-up')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading(fn ($record) => "Wiederherstellen: {$record->name}")
                        ->modalDescription('Das Unternehmen wird wiederhergestellt und ist wieder aktiv nutzbar.')
                        ->modalSubmitActionLabel('Wiederherstellen')
                        ->modalCancelActionLabel('Abbrechen')
                        ->action(function ($record) {
                            $record->update([
                                'archived_at' => null,
                                'archive_reason' => null,
                                'archived_by' => null,
                                'is_active' => true,
                                'billing_status' => 'active',
                            ]);

                            \Filament\Notifications\Notification::make()
                                ->title('Unternehmen wiederhergestellt')
                                ->body("'{$record->name}' wurde erfolgreich wiederhergestellt.")
                                ->success()
                                ->send();
                        })
                        ->visible(fn ($record) => $record->archived_at !== null),
                ]),
            ])
            // Bulk operations for company management
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('bulkStatusUpdate')
                        ->label('Status aktualisieren')
                        ->icon('heroicon-m-power')
                        ->color('warning')
                        ->form([
                            Forms\Components\Select::make('billing_status')
                                ->label('Billing Status')
                                ->options([
                                    'active' => 'Aktiv',
                                    'suspended' => 'Pausiert',
                                    'overdue' => 'Überfällig',
                                    'blocked' => 'Gesperrt',
                                ]),
                            Forms\Components\Toggle::make('is_active')
                                ->label('Aktiv setzen'),
                        ])
                        ->action(function ($records, array $data) {
                            $updates = array_filter($data);
                            $records->each->update($updates);

                            Notification::make()
                                ->title('Massen-Update durchgeführt')
                                ->body(count($records) . ' Unternehmen wurden aktualisiert.')
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation(),

                    Tables\Actions\ExportBulkAction::make()
                        ->label('Exportieren'),

                    // Bulk Archive
                    Tables\Actions\BulkAction::make('bulkArchive')
                        ->label('Archivieren')
                        ->icon('heroicon-o-archive-box')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Mehrere Unternehmen archivieren')
                        ->modalDescription(fn ($records) =>
                            "Folgende Unternehmen werden archiviert:\n\n• " .
                            $records->pluck('name')->implode("\n• "))
                        ->form([
                            \Filament\Forms\Components\Textarea::make('archive_reason')
                                ->label('Grund für Archivierung')
                                ->placeholder('z.B. Vertrag beendet, Insolvenz, Fusion...')
                                ->required()
                                ->rows(3),
                        ])
                        ->modalSubmitActionLabel('Alle archivieren')
                        ->modalCancelActionLabel('Abbrechen')
                        ->action(function ($records, array $data) {
                            $archived = 0;
                            foreach ($records as $company) {
                                if (!$company->archived_at) {
                                    $company->update([
                                        'archived_at' => now(),
                                        'archive_reason' => $data['archive_reason'],
                                        'archived_by' => auth()->user()->email,
                                        'is_active' => false,
                                        'billing_status' => 'blocked',
                                    ]);
                                    $company->branches()->update(['active' => false]);
                                    $company->services()->update(['is_active' => false]);
                                    $archived++;
                                }
                            }

                            \Filament\Notifications\Notification::make()
                                ->title('Unternehmen archiviert')
                                ->body("{$archived} Unternehmen wurden erfolgreich archiviert.")
                                ->success()
                                ->send();
                        }),

                    // Smart Bulk Delete
                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->modalHeading('Unternehmen löschen')
                        ->modalDescription(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $companiesWithData = [];
                            $companiesWithoutData = [];

                            foreach ($records as $company) {
                                $totalData = $company->branches()->count() +
                                           $company->staff()->count() +
                                           $company->customers()->count() +
                                           $company->phoneNumbers()->count() +
                                           \App\Models\Call::where('company_id', $company->id)->count() +
                                           \App\Models\Appointment::where('company_id', $company->id)->count();

                                if ($totalData > 0) {
                                    $companiesWithData[] = $company->name;
                                } else {
                                    $companiesWithoutData[] = $company->name;
                                }
                            }

                            $message = "";

                            if (!empty($companiesWithoutData)) {
                                $message .= "✅ OHNE DATEN (werden gelöscht):\n• " .
                                           implode("\n• ", $companiesWithoutData) . "\n\n";
                            }

                            if (!empty($companiesWithData)) {
                                $message .= "📊 MIT VERKNÜPFTEN DATEN (werden mit allen Daten gelöscht):\n• " .
                                           implode("\n• ", $companiesWithData) . "\n\n";
                            }

                            $message .= "⚠️ WARNUNG: Diese Aktion kann NICHT rückgängig gemacht werden!";

                            return $message;
                        })
                        ->modalSubmitActionLabel(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $hasAnyData = false;
                            foreach ($records as $company) {
                                if ($company->branches()->count() > 0 ||
                                    $company->staff()->count() > 0 ||
                                    $company->phoneNumbers()->count() > 0) {
                                    $hasAnyData = true;
                                    break;
                                }
                            }
                            return $hasAnyData ? 'Ja, alle mit Daten löschen!' : 'Löschen';
                        })
                        ->modalCancelActionLabel('Abbrechen')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $deletedCount = 0;
                            $errors = [];

                            foreach ($records as $company) {
                                try {
                                    // Delete all related data first
                                    $company->services()->delete();
                                    $company->staff()->delete();
                                    $company->customers()->delete();
                                    $company->phoneNumbers()->delete();
                                    \App\Models\Appointment::where('company_id', $company->id)->delete();
                                    \App\Models\Call::where('company_id', $company->id)->delete();
                                    \App\Models\WorkingHour::where('company_id', $company->id)->delete();
                                    \App\Models\PricingPlan::where('company_id', $company->id)->delete();
                                    $company->branches()->delete();
                                    $company->delete();
                                    $deletedCount++;
                                } catch (\Exception $e) {
                                    $errors[] = $company->name;
                                }
                            }

                            if ($deletedCount > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Erfolgreich gelöscht')
                                    ->body("{$deletedCount} Unternehmen wurden mit allen Daten gelöscht.")
                                    ->success()
                                    ->send();
                            }

                            if (!empty($errors)) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Fehler')
                                    ->body("Fehler bei: " . implode(", ", $errors))
                                    ->danger()
                                    ->send();
                            }
                        }),
                ]),
            ])
            // Performance optimizations
            ->defaultPaginationPageOption(25)
            ->poll('60s')
            ->deferLoading()
            ->persistFiltersInSession()
            ->persistSortInSession()
            ->striped();

        // Apply column ordering if user has preferences
        return static::applyColumnOrdering($table, 'companies');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\BranchesRelationManager::class,
            RelationManagers\StaffRelationManager::class,
            RelationManagers\PhoneNumbersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCompanies::route('/'),
            'create' => Pages\CreateCompany::route('/create'),
            'view' => Pages\ViewCompany::route('/{record}'),
            'edit' => Pages\EditCompany::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withCount(['branches', 'staff']);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'contact_person', 'email', 'phone', 'city'];
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
                            PolicyConfiguration::where('configurable_type', Company::class)
                                ->where('configurable_id', $record->id)
                                ->where('policy_type', $policyType)
                                ->delete();
                        }
                    })
                    ->dehydrated(false)
                    ->default(function ($record) use ($policyType) {
                        if (!$record) return false;
                        return PolicyConfiguration::where('configurable_type', Company::class)
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
                        $policy = PolicyConfiguration::where('configurable_type', Company::class)
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
                            return new HtmlString('<span class="text-gray-500">Keine Vererbung auf Unternehmensebene (höchste Ebene)</span>');
                        }

                        $hasOverride = PolicyConfiguration::where('configurable_type', Company::class)
                            ->where('configurable_id', $record->id)
                            ->where('policy_type', $policyType)
                            ->exists();

                        if ($hasOverride) {
                            return new HtmlString('<span class="text-primary-600 font-medium">Eigene Konfiguration aktiv</span>');
                        }

                        return new HtmlString('<span class="text-gray-500">Systemstandardwerte (keine Konfiguration)</span>');
                    })
                    ->visible(fn (Get $get) => $get("override_{$policyType}") !== true),

                Forms\Components\Placeholder::make("hierarchy_info_{$policyType}")
                    ->label('Hierarchie')
                    ->content(new HtmlString(
                        '<div class="text-sm text-gray-600">
                            <p class="font-medium mb-1">Vererbungsreihenfolge:</p>
                            <ol class="list-decimal list-inside space-y-1">
                                <li><strong>Unternehmen</strong> (aktuelle Ebene)</li>
                                <li>Filiale (erbt von Unternehmen)</li>
                                <li>Dienstleistung (erbt von Filiale oder Unternehmen)</li>
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
        if (!$record) return null;

        // Company is the top level, so no parent to inherit from
        return null;
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

                PolicyConfiguration::updateOrCreate(
                    [
                        'configurable_type' => Company::class,
                        'configurable_id' => $record->id,
                        'policy_type' => $policyType,
                    ],
                    [
                        'config' => $config,
                        'is_override' => false, // Company level doesn't override anything
                        'overrides_id' => null,
                    ]
                );
            } else {
                // Remove policy configuration if override is disabled
                PolicyConfiguration::where('configurable_type', Company::class)
                    ->where('configurable_id', $record->id)
                    ->where('policy_type', $policyType)
                    ->delete();
            }
        }
    }
}