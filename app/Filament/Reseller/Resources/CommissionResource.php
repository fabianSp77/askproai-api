<?php

namespace App\Filament\Reseller\Resources;

use App\Filament\Reseller\Resources\CommissionResource\Pages;
use App\Models\CommissionLedger;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class CommissionResource extends Resource
{
    protected static ?string $model = CommissionLedger::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-euro';
    
    protected static ?string $navigationLabel = 'Provisionen';
    
    protected static ?string $modelLabel = 'Provision';
    
    protected static ?string $pluralModelLabel = 'Provisionen';
    
    protected static ?string $navigationGroup = 'Provisionen';
    
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        // Commission records are read-only (created by system)
        return $form
            ->schema([
                Forms\Components\Section::make('Provisionsdetails')
                    ->schema([
                        Forms\Components\TextInput::make('customer.name')
                            ->label('Kunde')
                            ->disabled(),
                        Forms\Components\TextInput::make('transaction_type')
                            ->label('Transaktionstyp')
                            ->disabled(),
                        Forms\Components\TextInput::make('amount_cents')
                            ->label('Transaktionsbetrag')
                            ->prefix('€')
                            ->disabled()
                            ->formatStateUsing(fn ($state) => number_format($state / 100, 2, ',', '.')),
                        Forms\Components\TextInput::make('commission_cents')
                            ->label('Provision')
                            ->prefix('€')
                            ->disabled()
                            ->formatStateUsing(fn ($state) => number_format($state / 100, 2, ',', '.')),
                        Forms\Components\TextInput::make('commission_rate')
                            ->label('Provisionssatz')
                            ->suffix('%')
                            ->disabled(),
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'pending' => 'Ausstehend',
                                'processing' => 'In Bearbeitung',
                                'paid' => 'Ausgezahlt',
                                'cancelled' => 'Storniert',
                            ])
                            ->disabled(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Datum')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Kunde')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('transaction_type')
                    ->label('Typ')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'call_minutes' => 'Anruf',
                        'appointment' => 'Termin',
                        'api_usage' => 'API',
                        'manual_topup' => 'Aufladung',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match($state) {
                        'call_minutes' => 'info',
                        'appointment' => 'success',
                        'api_usage' => 'warning',
                        'manual_topup' => 'primary',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('amount_cents')
                    ->label('Umsatz')
                    ->money('EUR', divideBy: 100)
                    ->sortable()
                    ->alignEnd(),
                Tables\Columns\TextColumn::make('commission_rate')
                    ->label('Satz')
                    ->formatStateUsing(fn ($state) => $state . '%')
                    ->sortable()
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('commission_cents')
                    ->label('Provision')
                    ->money('EUR', divideBy: 100)
                    ->sortable()
                    ->alignEnd()
                    ->weight('bold')
                    ->color('success'),
                Tables\Columns\TextColumn::make('bonus_cents')
                    ->label('Bonus')
                    ->money('EUR', divideBy: 100)
                    ->sortable()
                    ->alignEnd()
                    ->toggleable()
                    ->color(fn ($state) => $state > 0 ? 'success' : 'gray'),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'pending',
                        'info' => 'processing',
                        'success' => 'paid',
                        'danger' => 'cancelled',
                    ])
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'pending' => 'Ausstehend',
                        'processing' => 'In Bearbeitung',
                        'paid' => 'Ausgezahlt',
                        'cancelled' => 'Storniert',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('period')
                    ->label('Periode')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Ausstehend',
                        'processing' => 'In Bearbeitung',
                        'paid' => 'Ausgezahlt',
                        'cancelled' => 'Storniert',
                    ]),
                Tables\Filters\SelectFilter::make('transaction_type')
                    ->label('Typ')
                    ->options([
                        'call_minutes' => 'Anrufe',
                        'appointment' => 'Termine',
                        'api_usage' => 'API',
                        'manual_topup' => 'Aufladungen',
                    ]),
                Tables\Filters\Filter::make('current_month')
                    ->label('Aktueller Monat')
                    ->query(fn (Builder $query): Builder => $query->where('period', now()->format('Y-m')))
                    ->default(),
                Tables\Filters\Filter::make('with_bonus')
                    ->label('Mit Bonus')
                    ->query(fn (Builder $query): Builder => $query->where('bonus_cents', '>', 0)),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                // No bulk actions for commission records
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('30s');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Provisionsdetails')
                    ->schema([
                        Infolists\Components\TextEntry::make('customer.name')
                            ->label('Kunde'),
                        Infolists\Components\TextEntry::make('transaction_type')
                            ->label('Transaktionstyp')
                            ->formatStateUsing(fn (string $state): string => match($state) {
                                'call_minutes' => 'Anruf',
                                'appointment' => 'Termin',
                                'api_usage' => 'API Nutzung',
                                'manual_topup' => 'Manuelle Aufladung',
                                default => $state,
                            })
                            ->badge(),
                        Infolists\Components\TextEntry::make('amount_cents')
                            ->label('Transaktionsbetrag')
                            ->money('EUR', divideBy: 100),
                        Infolists\Components\TextEntry::make('commission_rate')
                            ->label('Provisionssatz')
                            ->formatStateUsing(fn ($state) => $state . '%'),
                        Infolists\Components\TextEntry::make('commission_cents')
                            ->label('Provision')
                            ->money('EUR', divideBy: 100)
                            ->weight('bold')
                            ->color('success'),
                        Infolists\Components\TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => match($state) {
                                'pending' => 'Ausstehend',
                                'processing' => 'In Bearbeitung',
                                'paid' => 'Ausgezahlt',
                                'cancelled' => 'Storniert',
                                default => $state,
                            }),
                    ])
                    ->columns(2),
                
                Infolists\Components\Section::make('Bonus Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('bonus_cents')
                            ->label('Bonus Betrag')
                            ->money('EUR', divideBy: 100)
                            ->visible(fn ($record) => $record->bonus_cents > 0),
                        Infolists\Components\KeyValueEntry::make('bonus_details')
                            ->label('Bonus Aufschlüsselung')
                            ->visible(fn ($record) => $record->bonus_cents > 0),
                    ])
                    ->visible(fn ($record) => $record->bonus_cents > 0),
                
                Infolists\Components\Section::make('Zeitstempel')
                    ->schema([
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Erstellt am')
                            ->dateTime('d.m.Y H:i:s'),
                        Infolists\Components\TextEntry::make('updated_at')
                            ->label('Aktualisiert am')
                            ->dateTime('d.m.Y H:i:s'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // No relations for commission resource
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCommissions::route('/'),
            'view' => Pages\ViewCommission::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        // CRITICAL: Data isolation - only show commissions for current reseller
        $reseller = app('current_reseller');
        
        return parent::getEloquentQuery()
            ->where('reseller_id', $reseller->id);
    }

    public static function canCreate(): bool
    {
        // Commissions are system-generated only
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        // Commissions cannot be edited
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        // Commissions cannot be deleted
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $reseller = app('current_reseller');
        
        // Show pending commission amount
        $pendingAmount = static::getModel()::where('reseller_id', $reseller->id)
            ->where('status', 'pending')
            ->sum('commission_cents');
        
        if ($pendingAmount > 0) {
            return number_format($pendingAmount / 100, 2, ',', '.') . ' €';
        }
        
        return null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }
}