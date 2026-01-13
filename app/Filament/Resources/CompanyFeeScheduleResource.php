<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CompanyFeeScheduleResource\Pages;
use App\Models\CompanyFeeSchedule;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CompanyFeeScheduleResource extends Resource
{
    protected static ?string $model = CompanyFeeSchedule::class;

    protected static ?string $navigationIcon = 'heroicon-o-calculator';

    protected static ?string $navigationLabel = 'Gebührenpläne';

    protected static ?string $modelLabel = 'Gebührenplan';

    protected static ?string $pluralModelLabel = 'Gebührenpläne';

    protected static ?string $navigationGroup = 'Abrechnung';

    protected static ?int $navigationSort = 22;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Unternehmenszuordnung')
                    ->schema([
                        Forms\Components\Select::make('company_id')
                            ->label('Unternehmen')
                            ->relationship('company', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->unique(ignoreRecord: true)
                            ->helperText('Jedes Unternehmen kann nur einen Gebührenplan haben'),
                    ])->columns(1),

                Forms\Components\Section::make('Abrechnungsmodus')
                    ->description('Wählen Sie, wie Anrufkosten berechnet werden sollen')
                    ->schema([
                        Forms\Components\ToggleButtons::make('billing_mode')
                            ->label('Abrechnungsart')
                            ->options([
                                'per_second' => 'Pro Sekunde',
                                'per_minute' => 'Pro Minute (Legacy)',
                            ])
                            ->icons([
                                'per_second' => 'heroicon-o-clock',
                                'per_minute' => 'heroicon-o-calculator',
                            ])
                            ->colors([
                                'per_second' => 'success',
                                'per_minute' => 'warning',
                            ])
                            ->default('per_second')
                            ->inline()
                            ->required()
                            ->helperText(fn ($state) => match ($state) {
                                'per_second' => 'Beispiel: 2:37 Anruf = 2.617 Minuten abgerechnet',
                                'per_minute' => 'Beispiel: 2:37 Anruf = 3 Minuten abgerechnet (aufgerundet)',
                                default => 'Wählen Sie einen Abrechnungsmodus',
                            }),
                    ]),

                Forms\Components\Section::make('Einrichtungsgebühr')
                    ->schema([
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('setup_fee')
                                ->label('Setup Fee')
                                ->numeric()
                                ->prefix('EUR')
                                ->default(0)
                                ->minValue(0)
                                ->step(0.01)
                                ->helperText('Einmalige Gebühr bei Onboarding'),

                            Forms\Components\Placeholder::make('setup_fee_status')
                                ->label('Abrechnungsstatus')
                                ->content(function ($record) {
                                    if (!$record) {
                                        return 'Neu';
                                    }
                                    return $record->isSetupFeeBilled()
                                        ? '✅ Abgerechnet am ' . $record->setup_fee_billed_at->format('d.m.Y H:i')
                                        : '⏳ Noch nicht abgerechnet';
                                }),
                        ]),
                    ]),

                Forms\Components\Section::make('Tarif-Overrides')
                    ->description('Diese Werte überschreiben die Einstellungen aus dem Pricing Plan')
                    ->schema([
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('override_per_minute_rate')
                                ->label('Override: Minutenpreis')
                                ->numeric()
                                ->prefix('EUR')
                                ->step(0.001)
                                ->placeholder('Standard aus Pricing Plan')
                                ->helperText('Leer lassen = Wert aus Pricing Plan verwenden'),

                            Forms\Components\TextInput::make('override_discount_percentage')
                                ->label('Override: Rabatt')
                                ->numeric()
                                ->suffix('%')
                                ->minValue(0)
                                ->maxValue(100)
                                ->step(0.01)
                                ->placeholder('Kein Rabatt')
                                ->helperText('Leer lassen = Kein zusätzlicher Rabatt'),
                        ]),
                    ]),

                Forms\Components\Section::make('Notizen')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Interne Notizen')
                            ->rows(3)
                            ->maxLength(1000),
                    ])->collapsed(),
            ]);
    }

    /**
     * Optimize query with eager loading to prevent N+1 queries.
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['company']);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->deferLoading()
            ->columns([
                Tables\Columns\TextColumn::make('company.name')
                    ->label('Unternehmen')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('billing_mode')
                    ->label('Modus')
                    ->colors([
                        'success' => 'per_second',
                        'warning' => 'per_minute',
                    ])
                    ->formatStateUsing(fn ($state) => $state === 'per_second' ? 'Pro Sekunde' : 'Pro Minute'),

                Tables\Columns\TextColumn::make('setup_fee')
                    ->label('Setup Fee')
                    ->money('EUR', locale: 'de'),

                Tables\Columns\IconColumn::make('setup_fee_billed_at')
                    ->label('Abgerechnet')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-clock')
                    ->trueColor('success')
                    ->falseColor('warning'),

                Tables\Columns\TextColumn::make('override_per_minute_rate')
                    ->label('EUR/Min')
                    ->money('EUR', locale: 'de')
                    ->placeholder('Standard')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('override_discount_percentage')
                    ->label('Rabatt')
                    ->formatStateUsing(fn ($state) => $state ? "{$state}%" : '-')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Aktualisiert')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('billing_mode')
                    ->label('Abrechnungsmodus')
                    ->options([
                        'per_second' => 'Pro Sekunde',
                        'per_minute' => 'Pro Minute',
                    ]),

                Tables\Filters\TernaryFilter::make('setup_fee_billed')
                    ->label('Setup Fee Status')
                    ->placeholder('Alle')
                    ->trueLabel('Abgerechnet')
                    ->falseLabel('Ausstehend')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('setup_fee_billed_at'),
                        false: fn ($query) => $query->whereNull('setup_fee_billed_at'),
                    ),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),

                Tables\Actions\Action::make('charge_setup_fee')
                    ->label('Setup Fee abrechnen')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->visible(fn ($record) => !$record->isSetupFeeBilled() && $record->setup_fee > 0)
                    ->requiresConfirmation()
                    ->modalHeading('Setup Fee abrechnen')
                    ->modalDescription(fn ($record) => "Möchten Sie die Einrichtungsgebühr von {$record->setup_fee} EUR für {$record->company->name} abrechnen?")
                    ->action(function ($record) {
                        $feeService = app(\App\Services\Billing\FeeService::class);
                        $transaction = $feeService->chargeSetupFee($record->company);

                        if ($transaction) {
                            \Filament\Notifications\Notification::make()
                                ->title('Setup Fee abgerechnet')
                                ->body("Transaction #{$transaction->id} erstellt")
                                ->success()
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('updated_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCompanyFeeSchedules::route('/'),
            'create' => Pages\CreateCompanyFeeSchedule::route('/create'),
            'edit' => Pages\EditCompanyFeeSchedule::route('/{record}/edit'),
        ];
    }
}
