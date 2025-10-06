<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServiceResource\Pages;
use App\Models\Service;
use App\Models\Company;
use App\Models\Branch;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Filament\Notifications\Notification;

class ServiceResource extends Resource
{
    protected static ?string $model = Service::class;
    protected static ?string $navigationIcon = 'heroicon-o-briefcase';
    protected static ?string $navigationGroup = 'Stammdaten';
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationLabel = 'Services';
    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('active', true)->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $count = static::getModel()::where('active', true)->count();
        return $count > 50 ? 'success' : ($count > 20 ? 'warning' : 'info');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Optimized to 3 logical tabs for service management
                Tabs::make('Service Details')
                    ->tabs([
                        // Tab 1: Essential Service Information
                        Tabs\Tab::make('Service')
                            ->icon('heroicon-m-briefcase')
                            ->schema([
                                Section::make('Grundinformationen')
                                    ->schema([
                                        Grid::make(2)->schema([
                                            Forms\Components\TextInput::make('name')
                                                ->label('Service Name')
                                                ->required()
                                                ->maxLength(255)
                                                ->placeholder('Beratungsgespräch'),

                                            Forms\Components\Select::make('category')
                                                ->label('Kategorie')
                                                ->options([
                                                    'consulting' => '💼 Beratung',
                                                    'support' => '🛠️ Support',
                                                    'development' => '💻 Entwicklung',
                                                    'maintenance' => '🔧 Wartung',
                                                    'training' => '📚 Schulung',
                                                    'premium' => '⭐ Premium',
                                                ])
                                                ->native(false)
                                                ->searchable(),
                                        ]),

                                        Grid::make(3)->schema([
                                            Forms\Components\Toggle::make('active')
                                                ->label('Aktiv')
                                                ->default(true),

                                            Forms\Components\Toggle::make('is_online_bookable')
                                                ->label('Online buchbar')
                                                ->default(true),

                                            Forms\Components\Select::make('complexity_level')
                                                ->label('Komplexität')
                                                ->options([
                                                    'basic' => '🟢 Einfach',
                                                    'intermediate' => '🟡 Mittel',
                                                    'advanced' => '🔴 Schwer',
                                                    'expert' => '🟣 Expert',
                                                ])
                                                ->default('basic')
                                                ->required()
                                                ->native(false),
                                        ]),

                                        Forms\Components\Textarea::make('description')
                                            ->label('Beschreibung')
                                            ->rows(3)
                                            ->maxLength(500)
                                            ->placeholder('Detaillierte Beschreibung des Services...'),

                                        Grid::make(2)->schema([
                                            Forms\Components\Select::make('company_id')
                                                ->label('Unternehmen')
                                                ->relationship('company', 'name')
                                                ->searchable()
                                                ->preload(),

                                            Forms\Components\TextInput::make('branch_id')
                                                ->label('Filial-ID')
                                                ->maxLength(36),
                                        ]),
                                    ]),
                            ]),

                        // Tab 2: Pricing & Duration
                        Tabs\Tab::make('Preise')
                            ->icon('heroicon-m-currency-euro')
                            ->badge(fn ($record) => $record?->price ? '€' . number_format($record->price, 2) : null)
                            ->schema([
                                Section::make('Preise & Zeiten')
                                    ->schema([
                                        Grid::make(3)->schema([
                                            Forms\Components\TextInput::make('price')
                                                ->label('Preis')
                                                ->prefix('€')
                                                ->numeric()
                                                ->step(0.01)
                                                ->default(0.00),

                                            Forms\Components\TextInput::make('default_duration_minutes')
                                                ->label('Standard Dauer')
                                                ->suffix('min')
                                                ->numeric()
                                                ->default(30),

                                            Forms\Components\TextInput::make('buffer_time_minutes')
                                                ->label('Pufferzeit')
                                                ->suffix('min')
                                                ->numeric()
                                                ->default(0),
                                        ]),

                                        Grid::make(3)->schema([
                                            Forms\Components\TextInput::make('min_staff_required')
                                                ->label('Min. Personal')
                                                ->numeric()
                                                ->default(1),

                                            Forms\Components\TextInput::make('max_bookings_per_day')
                                                ->label('Max. Buchungen/Tag')
                                                ->numeric()
                                                ->placeholder('Unbegrenzt'),

                                            Forms\Components\TextInput::make('sort_order')
                                                ->label('Sortierung')
                                                ->numeric()
                                                ->default(0),
                                        ]),

                                        Forms\Components\TextInput::make('calcom_event_type_id')
                                            ->label('Cal.com Event Type ID')
                                            ->maxLength(255)
                                            ->placeholder('Für Online-Buchungen'),
                                    ]),
                            ]),

                        // Tab 3: Requirements & Skills
                        Tabs\Tab::make('Anforderungen')
                            ->icon('heroicon-m-academic-cap')
                            ->schema([
                                Section::make('Anforderungen & Qualifikationen')
                                    ->schema([
                                        Forms\Components\Textarea::make('required_skills')
                                            ->label('Erforderliche Fähigkeiten')
                                            ->rows(3)
                                            ->placeholder('JavaScript, React, Node.js...')
                                            ->helperText('Kommagetrennte Liste der erforderlichen Fähigkeiten'),

                                        Forms\Components\Textarea::make('required_certifications')
                                            ->label('Erforderliche Zertifikate')
                                            ->rows(3)
                                            ->placeholder('AWS Certified, Scrum Master...')
                                            ->helperText('Kommagetrennte Liste der erforderlichen Zertifikate'),

                                        Forms\Components\TextInput::make('tenant_id')
                                            ->label('Tenant ID')
                                            ->maxLength(36)
                                            ->disabled()
                                            ->visible(fn () => auth()->user()?->hasRole('admin')),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            // Performance: Eager load relationships
            ->modifyQueryUsing(fn (Builder $query) =>
                $query->with(['company'])
                    ->withCount(['appointments' => fn ($q) => $q->where('status', 'confirmed')])
            )
            // Optimized to 9 essential columns with rich visual information
            ->columns([
                // Service name with category indicator
                Tables\Columns\TextColumn::make('name')
                    ->label('Service')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn ($record) =>
                        ($record->category ? ucfirst($record->category) . ' • ' : '') .
                        $record->default_duration_minutes . ' min'
                    )
                    ->icon(fn ($record) => match($record->category) {
                        'consulting' => 'heroicon-m-chat-bubble-left-right',
                        'support' => 'heroicon-m-wrench-screwdriver',
                        'development' => 'heroicon-m-code-bracket',
                        'maintenance' => 'heroicon-m-cog',
                        'training' => 'heroicon-m-academic-cap',
                        'premium' => 'heroicon-m-star',
                        default => 'heroicon-m-briefcase',
                    }),

                // Category with visual coding
                Tables\Columns\BadgeColumn::make('category')
                    ->label('Kategorie')
                    ->colors([
                        'info' => 'consulting',
                        'warning' => 'support',
                        'success' => 'development',
                        'gray' => 'maintenance',
                        'indigo' => 'training',
                        'yellow' => 'premium',
                    ])
                    ->formatStateUsing(fn ($state) => match($state) {
                        'consulting' => '💼 Beratung',
                        'support' => '🛠️ Support',
                        'development' => '💻 Entwicklung',
                        'maintenance' => '🔧 Wartung',
                        'training' => '📚 Schulung',
                        'premium' => '⭐ Premium',
                        default => $state ?: '📋 Sonstige',
                    }),

                // Price with visual emphasis
                Tables\Columns\TextColumn::make('price')
                    ->label('Preis')
                    ->money('EUR')
                    ->sortable()
                    ->alignEnd()
                    ->weight('bold')
                    ->color(fn ($state) =>
                        $state == 0 ? 'success' :
                        ($state > 100 ? 'warning' : 'gray')
                    )
                    ->description(fn ($record) =>
                        $record->price > 0 ? '€' . number_format($record->price / ($record->default_duration_minutes / 60), 2) . '/h' : 'Kostenlos'
                    ),

                // Duration and requirements
                Tables\Columns\TextColumn::make('service_details')
                    ->label('Details')
                    ->getStateUsing(fn ($record) =>
                        $record->default_duration_minutes . ' min' .
                        ($record->min_staff_required > 1 ? ' • ' . $record->min_staff_required . ' Personal' : '') .
                        ($record->buffer_time_minutes > 0 ? ' • +' . $record->buffer_time_minutes . 'min Puffer' : '')
                    )
                    ->badge()
                    ->color('info'),

                // Complexity level
                Tables\Columns\BadgeColumn::make('complexity_level')
                    ->label('Komplexität')
                    ->colors([
                        'success' => 'basic',
                        'warning' => 'intermediate',
                        'danger' => 'advanced',
                        'purple' => 'expert',
                    ])
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'basic' => '🟢 Einfach',
                        'intermediate' => '🟡 Mittel',
                        'advanced' => '🔴 Schwer',
                        'expert' => '🟣 Expert',
                        default => $state,
                    }),

                // Booking status
                Tables\Columns\TextColumn::make('booking_status')
                    ->label('Buchung')
                    ->getStateUsing(fn ($record) =>
                        $record->is_online_bookable ?
                        ($record->calcom_event_type_id ? '🌐 Online verfügbar' : '📞 Nur telefonisch') :
                        '❌ Nicht buchbar'
                    )
                    ->badge()
                    ->color(fn ($record) =>
                        $record->is_online_bookable ?
                        ($record->calcom_event_type_id ? 'success' : 'warning') :
                        'danger'
                    ),

                // Activity and popularity
                Tables\Columns\TextColumn::make('appointments_count')
                    ->label('Beliebtheit')
                    ->badge()
                    ->formatStateUsing(fn ($state) => '📅 ' . $state . ' Buchungen')
                    ->color(fn ($state) => $state > 10 ? 'success' : ($state > 0 ? 'warning' : 'gray'))
                    ->sortable()
                    ->toggleable(),

                // Service status
                Tables\Columns\BadgeColumn::make('active')
                    ->label('Status')
                    ->boolean()
                    ->trueLabel('✅ Aktiv')
                    ->falseLabel('⏸️ Inaktiv')
                    ->trueColor('success')
                    ->falseColor('danger'),

                // Company assignment (hidden by default)
                Tables\Columns\TextColumn::make('company.name')
                    ->label('Unternehmen')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            // Smart business filters for service management
            ->filters([
                SelectFilter::make('category')
                    ->label('Kategorie')
                    ->multiple()
                    ->options([
                        'consulting' => '💼 Beratung',
                        'support' => '🛠️ Support',
                        'development' => '💻 Entwicklung',
                        'maintenance' => '🔧 Wartung',
                        'training' => '📚 Schulung',
                        'premium' => '⭐ Premium',
                    ]),

                SelectFilter::make('complexity_level')
                    ->label('Komplexität')
                    ->options([
                        'basic' => 'Einfach',
                        'intermediate' => 'Mittel',
                        'advanced' => 'Schwer',
                        'expert' => 'Expert',
                    ]),

                Filter::make('active_services')
                    ->label('Aktive Services')
                    ->query(fn (Builder $query): Builder =>
                        $query->where('active', true)
                    )
                    ->default(),

                Filter::make('online_bookable')
                    ->label('Online buchbar')
                    ->query(fn (Builder $query): Builder =>
                        $query->where('is_online_bookable', true)
                    ),

                Filter::make('free_services')
                    ->label('Kostenlose Services')
                    ->query(fn (Builder $query): Builder =>
                        $query->where('price', 0)
                    ),

                Filter::make('premium_services')
                    ->label('Premium Services (>€100)')
                    ->query(fn (Builder $query): Builder =>
                        $query->where('price', '>', 100)
                    ),

                Filter::make('popular_services')
                    ->label('Beliebte Services (>10 Buchungen)')
                    ->query(fn (Builder $query): Builder =>
                        $query->has('appointments', '>', 10)
                    ),

                SelectFilter::make('company_id')
                    ->label('Unternehmen')
                    ->relationship('company', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->filtersLayout(Tables\Enums\FiltersLayout::AboveContent)
            ->filtersFormColumns(3)
            // Quick actions for service management
            ->actions([
                Tables\Actions\ActionGroup::make([
                    // Quick booking
                    Tables\Actions\Action::make('bookService')
                        ->label('Service buchen')
                        ->icon('heroicon-m-calendar')
                        ->color('success')
                        ->url(fn ($record) => route('filament.admin.resources.appointments.create', [
                            'service_id' => $record->id
                        ]))
                        ->visible(fn ($record) => $record->active),

                    // Price update
                    Tables\Actions\Action::make('updatePrice')
                        ->label('Preis anpassen')
                        ->icon('heroicon-m-currency-euro')
                        ->color('warning')
                        ->form([
                            Forms\Components\TextInput::make('price')
                                ->label('Neuer Preis')
                                ->prefix('€')
                                ->numeric()
                                ->step(0.01)
                                ->default(fn ($record) => $record->price),
                            Forms\Components\TextInput::make('default_duration_minutes')
                                ->label('Dauer (Minuten)')
                                ->suffix('min')
                                ->numeric()
                                ->default(fn ($record) => $record->default_duration_minutes),
                        ])
                        ->action(function ($record, array $data) {
                            $oldPrice = $record->price;
                            $record->update($data);

                            Notification::make()
                                ->title('Preis aktualisiert')
                                ->body("Preis von €{$oldPrice} auf €{$data['price']} geändert.")
                                ->success()
                                ->send();
                        }),

                    // Toggle availability
                    Tables\Actions\Action::make('toggleAvailability')
                        ->label('Verfügbarkeit')
                        ->icon('heroicon-m-power')
                        ->color(fn ($record) => $record->active ? 'danger' : 'success')
                        ->form([
                            Forms\Components\Toggle::make('active')
                                ->label('Service aktiv')
                                ->default(fn ($record) => $record->active),
                            Forms\Components\Toggle::make('is_online_bookable')
                                ->label('Online buchbar')
                                ->default(fn ($record) => $record->is_online_bookable),
                            Forms\Components\TextInput::make('max_bookings_per_day')
                                ->label('Max. Buchungen pro Tag')
                                ->numeric()
                                ->default(fn ($record) => $record->max_bookings_per_day),
                        ])
                        ->action(function ($record, array $data) {
                            $record->update($data);

                            Notification::make()
                                ->title('Verfügbarkeit aktualisiert')
                                ->body('Service-Verfügbarkeit wurde geändert.')
                                ->success()
                                ->send();
                        }),

                    // Category management
                    Tables\Actions\Action::make('updateCategory')
                        ->label('Kategorie ändern')
                        ->icon('heroicon-m-tag')
                        ->color('info')
                        ->form([
                            Forms\Components\Select::make('category')
                                ->label('Kategorie')
                                ->options([
                                    'consulting' => '💼 Beratung',
                                    'support' => '🛠️ Support',
                                    'development' => '💻 Entwicklung',
                                    'maintenance' => '🔧 Wartung',
                                    'training' => '📚 Schulung',
                                    'premium' => '⭐ Premium',
                                ])
                                ->default(fn ($record) => $record->category)
                                ->native(false),
                            Forms\Components\Select::make('complexity_level')
                                ->label('Komplexität')
                                ->options([
                                    'basic' => 'Einfach',
                                    'intermediate' => 'Mittel',
                                    'advanced' => 'Schwer',
                                    'expert' => 'Expert',
                                ])
                                ->default(fn ($record) => $record->complexity_level)
                                ->native(false),
                        ])
                        ->action(function ($record, array $data) {
                            $record->update($data);

                            Notification::make()
                                ->title('Kategorie aktualisiert')
                                ->body('Service-Kategorie wurde erfolgreich geändert.')
                                ->success()
                                ->send();
                        }),

                    // Duplicate service
                    Tables\Actions\Action::make('duplicateService')
                        ->label('Service duplizieren')
                        ->icon('heroicon-m-document-duplicate')
                        ->color('gray')
                        ->form([
                            Forms\Components\TextInput::make('name')
                                ->label('Name für Kopie')
                                ->default(fn ($record) => $record->name . ' (Kopie)')
                                ->required(),
                        ])
                        ->action(function ($record, array $data) {
                            $duplicate = $record->replicate();
                            $duplicate->name = $data['name'];
                            $duplicate->active = false; // Deactivate duplicates by default
                            $duplicate->save();

                            Notification::make()
                                ->title('Service dupliziert')
                                ->body("'{$data['name']}' wurde erstellt (inaktiv).")
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                ]),
            ])
            // Bulk operations for service management
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('bulkCategoryUpdate')
                        ->label('Kategorie setzen')
                        ->icon('heroicon-m-tag')
                        ->color('info')
                        ->form([
                            Forms\Components\Select::make('category')
                                ->label('Neue Kategorie')
                                ->options([
                                    'consulting' => '💼 Beratung',
                                    'support' => '🛠️ Support',
                                    'development' => '💻 Entwicklung',
                                    'maintenance' => '🔧 Wartung',
                                    'training' => '📚 Schulung',
                                    'premium' => '⭐ Premium',
                                ])
                                ->required()
                                ->native(false),
                        ])
                        ->action(function ($records, array $data) {
                            $records->each->update($data);

                            Notification::make()
                                ->title('Kategorie aktualisiert')
                                ->body(count($records) . ' Services wurden kategorisiert.')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\BulkAction::make('bulkPriceUpdate')
                        ->label('Preise anpassen')
                        ->icon('heroicon-m-currency-euro')
                        ->color('warning')
                        ->form([
                            Forms\Components\Select::make('adjustment_type')
                                ->label('Anpassungstyp')
                                ->options([
                                    'percentage' => 'Prozentual',
                                    'fixed' => 'Fester Betrag',
                                ])
                                ->required()
                                ->reactive(),
                            Forms\Components\TextInput::make('adjustment_value')
                                ->label(fn (Get $get) => $get('adjustment_type') === 'percentage' ? 'Prozent (±)' : 'Betrag (±)')
                                ->numeric()
                                ->required()
                                ->suffix(fn (Get $get) => $get('adjustment_type') === 'percentage' ? '%' : '€'),
                        ])
                        ->action(function ($records, array $data) {
                            foreach ($records as $record) {
                                if ($data['adjustment_type'] === 'percentage') {
                                    $newPrice = $record->price * (1 + $data['adjustment_value'] / 100);
                                } else {
                                    $newPrice = $record->price + $data['adjustment_value'];
                                }
                                $record->update(['price' => max(0, $newPrice)]);
                            }

                            Notification::make()
                                ->title('Preise aktualisiert')
                                ->body(count($records) . ' Service-Preise wurden angepasst.')
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation(),

                    Tables\Actions\BulkAction::make('bulkStatusUpdate')
                        ->label('Status ändern')
                        ->icon('heroicon-m-power')
                        ->color('warning')
                        ->form([
                            Forms\Components\Toggle::make('active')
                                ->label('Services aktivieren'),
                            Forms\Components\Toggle::make('is_online_bookable')
                                ->label('Online-Buchung aktivieren'),
                        ])
                        ->action(function ($records, array $data) {
                            $updates = array_filter($data, fn ($value) => $value !== null);
                            $records->each->update($updates);

                            Notification::make()
                                ->title('Status aktualisiert')
                                ->body(count($records) . ' Services wurden aktualisiert.')
                                ->success()
                                ->send();
                        }),

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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServices::route('/'),
            'create' => Pages\CreateService::route('/create'),
            'edit' => Pages\EditService::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['company'])
            ->withCount(['appointments' => fn ($q) => $q->where('status', 'confirmed')]);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'category', 'description'];
    }
}