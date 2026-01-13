<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServiceChangeFeeResource\Pages;
use App\Models\ServiceChangeFee;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ServiceChangeFeeResource extends Resource
{
    protected static ?string $model = ServiceChangeFee::class;

    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static ?string $navigationLabel = 'Service-Gebühren';

    protected static ?string $modelLabel = 'Service-Gebühr';

    protected static ?string $pluralModelLabel = 'Service-Gebühren';

    protected static ?string $navigationGroup = 'Abrechnung';

    protected static ?int $navigationSort = 24;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Unternehmen & Kategorie')
                    ->schema([
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\Select::make('company_id')
                                ->label('Unternehmen')
                                ->relationship('company', 'name')
                                ->required()
                                ->searchable()
                                ->preload(),

                            Forms\Components\Select::make('category')
                                ->label('Kategorie')
                                ->options(ServiceChangeFee::getCategoryOptions())
                                ->required()
                                ->native(false)
                                ->reactive()
                                ->afterStateUpdated(function ($state, Forms\Set $set) {
                                    // Auto-fill default price for category
                                    $defaultPrices = ServiceChangeFee::getCategoryDefaultPrices();
                                    if ($state && isset($defaultPrices[$state]) && $defaultPrices[$state] > 0) {
                                        $set('amount', $defaultPrices[$state]);
                                    }
                                })
                                ->helperText(fn ($state) => $state
                                    ? (ServiceChangeFee::getCategoryDescriptions()[$state] ?? '')
                                    : 'Wähle eine Kategorie für Preisvorschlag'),
                        ]),
                    ]),

                Forms\Components\Section::make('Leistungsbeschreibung')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Titel')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('z.B. Call Flow Anpassung: Neue Begrüßung'),

                        Forms\Components\Textarea::make('description')
                            ->label('Beschreibung')
                            ->rows(3)
                            ->maxLength(2000)
                            ->placeholder('Detaillierte Beschreibung der durchgeführten Änderungen...'),

                        Forms\Components\DatePicker::make('service_date')
                            ->label('Leistungsdatum')
                            ->default(now())
                            ->required(),
                    ]),

                Forms\Components\Section::make('Preisgestaltung')
                    ->schema([
                        Forms\Components\Grid::make(3)->schema([
                            Forms\Components\TextInput::make('amount')
                                ->label('Betrag')
                                ->numeric()
                                ->prefix('EUR')
                                ->required()
                                ->minValue(0)
                                ->step(0.01)
                                ->reactive()
                                ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                    // Don't override if manually set
                                }),

                            Forms\Components\TextInput::make('hours_worked')
                                ->label('Arbeitsstunden')
                                ->numeric()
                                ->step(0.25)
                                ->minValue(0)
                                ->reactive()
                                ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                    $rate = $get('hourly_rate');
                                    if ($state && $rate) {
                                        $set('amount', round($state * $rate, 2));
                                    }
                                }),

                            Forms\Components\TextInput::make('hourly_rate')
                                ->label('Stundensatz')
                                ->numeric()
                                ->prefix('EUR')
                                ->step(0.01)
                                ->default(75)
                                ->reactive()
                                ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                    $hours = $get('hours_worked');
                                    if ($state && $hours) {
                                        $set('amount', round($hours * $state, 2));
                                    }
                                }),
                        ]),

                        Forms\Components\Placeholder::make('amount_preview')
                            ->label('Berechneter Betrag')
                            ->content(function (Forms\Get $get) {
                                $hours = $get('hours_worked');
                                $rate = $get('hourly_rate');
                                if ($hours && $rate) {
                                    return number_format($hours * $rate, 2, ',', '.') . ' EUR (' . $hours . 'h × ' . $rate . ' EUR/h)';
                                }
                                return '-';
                            })
                            ->visible(fn (Forms\Get $get) => $get('hours_worked') && $get('hourly_rate')),
                    ]),

                Forms\Components\Section::make('Status & Abrechnung')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options(ServiceChangeFee::getStatusOptions())
                            ->default(ServiceChangeFee::STATUS_PENDING)
                            ->required()
                            ->native(false)
                            ->disabled(fn ($record) => $record && !$record->canBeEdited()),
                    ]),

                Forms\Components\Section::make('Verknüpfungen')
                    ->schema([
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\Select::make('related_entity_type')
                                ->label('Bezogenes Objekt')
                                ->options([
                                    'App\\Models\\RetellAgent' => 'Retell Agent',
                                    'App\\Models\\ConversationFlow' => 'Conversation Flow',
                                    'App\\Models\\Company' => 'Unternehmen',
                                    'App\\Models\\ServiceOutputConfiguration' => 'Service Gateway Ausgabe',
                                ])
                                ->native(false)
                                ->placeholder('Optional'),

                            Forms\Components\TextInput::make('related_entity_id')
                                ->label('Objekt-ID')
                                ->numeric()
                                ->placeholder('Optional'),
                        ]),
                    ])->collapsed(),

                Forms\Components\Section::make('Interne Notizen')
                    ->schema([
                        Forms\Components\Textarea::make('internal_notes')
                            ->label('Interne Notizen')
                            ->rows(3)
                            ->maxLength(2000)
                            ->placeholder('Nur für internen Gebrauch...'),
                    ])->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('company.name')
                    ->label('Unternehmen')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('category')
                    ->label('Kategorie')
                    ->colors([
                        'primary' => 'agent_change',
                        'info' => 'flow_change',
                        'warning' => 'gateway_config',
                        'success' => 'integration',
                        'gray' => 'support',
                        'secondary' => 'custom',
                    ])
                    ->formatStateUsing(fn ($state) => ServiceChangeFee::getCategoryOptions()[$state] ?? $state),

                Tables\Columns\TextColumn::make('title')
                    ->label('Titel')
                    ->searchable()
                    ->limit(40),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Betrag')
                    ->money('EUR', locale: 'de')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors(ServiceChangeFee::getStatusColors())
                    ->formatStateUsing(fn ($state) => ServiceChangeFee::getStatusOptions()[$state] ?? $state),

                Tables\Columns\TextColumn::make('service_date')
                    ->label('Datum')
                    ->date('d.m.Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('createdByUser.name')
                    ->label('Erstellt von')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('company_id')
                    ->label('Unternehmen')
                    ->relationship('company', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('category')
                    ->label('Kategorie')
                    ->options(ServiceChangeFee::getCategoryOptions()),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(ServiceChangeFee::getStatusOptions())
                    ->multiple(),

                Tables\Filters\Filter::make('service_date')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('Von'),
                        Forms\Components\DatePicker::make('until')
                            ->label('Bis'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn ($q, $date) => $q->whereDate('service_date', '>=', $date))
                            ->when($data['until'], fn ($q, $date) => $q->whereDate('service_date', '<=', $date));
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn ($record) => $record->canBeEdited()),

                Tables\Actions\Action::make('charge')
                    ->label('Abrechnen')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->visible(fn ($record) => $record->canBeInvoiced())
                    ->requiresConfirmation()
                    ->modalHeading('Gebühr abrechnen')
                    ->modalDescription(fn ($record) => "Soll die Gebühr von {$record->amount} EUR für '{$record->title}' vom Guthaben abgezogen werden?")
                    ->action(function ($record) {
                        $feeService = app(\App\Services\Billing\FeeService::class);
                        $transaction = $feeService->chargeServiceChangeFee($record, 'balance');

                        if ($transaction) {
                            \Filament\Notifications\Notification::make()
                                ->title('Gebühr abgerechnet')
                                ->body("Transaction #{$transaction->id} erstellt")
                                ->success()
                                ->send();
                        }
                    }),

                Tables\Actions\Action::make('waive')
                    ->label('Erlassen')
                    ->icon('heroicon-o-x-circle')
                    ->color('warning')
                    ->visible(fn ($record) => $record->status === ServiceChangeFee::STATUS_PENDING)
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Begründung')
                            ->required()
                            ->maxLength(500),
                    ])
                    ->action(function ($record, array $data) {
                        $feeService = app(\App\Services\Billing\FeeService::class);
                        $feeService->waiveFee($record, auth()->id(), $data['reason']);

                        \Filament\Notifications\Notification::make()
                            ->title('Gebühr erlassen')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn ($records) => $records?->every(fn ($r) => $r->canBeEdited()) ?? true),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServiceChangeFees::route('/'),
            'create' => Pages\CreateServiceChangeFee::route('/create'),
            'view' => Pages\ViewServiceChangeFee::route('/{record}'),
            'edit' => Pages\EditServiceChangeFee::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where('status', ServiceChangeFee::STATUS_PENDING)->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
