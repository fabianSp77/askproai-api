<?php

namespace App\Filament\Customer\Resources;

use App\Filament\Concerns\HasCachedNavigationBadge;
use App\Filament\Customer\Resources\TransactionResource\Pages;
use App\Models\Transaction;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Database\Eloquent\Builder;

class TransactionResource extends Resource
{
    use HasCachedNavigationBadge;

    protected static ?string $model = Transaction::class;
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationLabel = 'Transaktionen';
    protected static ?string $navigationGroup = 'Abrechnung';
    protected static ?int $navigationSort = 3;
    protected static ?string $slug = 'transactions';
    protected static ?string $recordTitleAttribute = 'description';

    public static function getNavigationBadge(): ?string
    {
        return static::getCachedBadge(function() {
            return static::getModel()::withoutGlobalScopes()
                ->where('tenant_id', auth()->user()->company_id)
                ->where('created_at', '>=', now()->subDays(7))
                ->count();
        });
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\BadgeColumn::make('type')
                    ->label('Typ')
                    ->colors([
                        'success' => Transaction::TYPE_TOPUP,
                        'danger' => Transaction::TYPE_USAGE,
                        'warning' => Transaction::TYPE_REFUND,
                        'info' => Transaction::TYPE_ADJUSTMENT,
                        'primary' => Transaction::TYPE_BONUS,
                        'secondary' => Transaction::TYPE_FEE,
                    ])
                    ->formatStateUsing(fn ($state) => match($state) {
                        Transaction::TYPE_TOPUP => 'Aufladung',
                        Transaction::TYPE_USAGE => 'Verbrauch',
                        Transaction::TYPE_REFUND => 'Erstattung',
                        Transaction::TYPE_ADJUSTMENT => 'Anpassung',
                        Transaction::TYPE_BONUS => 'Bonus',
                        Transaction::TYPE_FEE => 'Gebühr',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('amount_cents')
                    ->label('Betrag')
                    ->formatStateUsing(function ($state) {
                        $prefix = $state > 0 ? '+' : '';
                        $color = $state > 0 ? 'text-green-600' : 'text-red-600';
                        $amount = number_format($state / 100, 2);
                        return "<span class='{$color} font-semibold'>{$prefix}{$amount} €</span>";
                    })
                    ->html()
                    ->sortable(),

                Tables\Columns\TextColumn::make('balance_after_cents')
                    ->label('Saldo danach')
                    ->formatStateUsing(fn ($state) => number_format($state / 100, 2) . ' €')
                    ->sortable()
                    ->color(fn ($state) => $state < 0 ? 'danger' : ($state < 1000 ? 'warning' : 'success')),

                Tables\Columns\TextColumn::make('description')
                    ->label('Beschreibung')
                    ->searchable()
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->description),

                Tables\Columns\IconColumn::make('call_id')
                    ->label('Anruf')
                    ->boolean()
                    ->trueIcon('heroicon-o-phone')
                    ->falseIcon('')
                    ->exists('call'),

                Tables\Columns\IconColumn::make('appointment_id')
                    ->label('Termin')
                    ->boolean()
                    ->trueIcon('heroicon-o-calendar')
                    ->falseIcon('')
                    ->exists('appointment'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Datum')
                    ->dateTime('d.m.Y H:i:s')
                    ->sortable()
                    ->description(fn ($record) => $record->created_at->diffForHumans()),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Typ')
                    ->options([
                        Transaction::TYPE_TOPUP => 'Aufladung',
                        Transaction::TYPE_USAGE => 'Verbrauch',
                        Transaction::TYPE_REFUND => 'Erstattung',
                        Transaction::TYPE_ADJUSTMENT => 'Anpassung',
                        Transaction::TYPE_BONUS => 'Bonus',
                        Transaction::TYPE_FEE => 'Gebühr',
                    ])
                    ->multiple(),

                Tables\Filters\Filter::make('credits')
                    ->label('Nur Gutschriften')
                    ->query(fn (Builder $query) => $query->where('amount_cents', '>', 0)),

                Tables\Filters\Filter::make('debits')
                    ->label('Nur Belastungen')
                    ->query(fn (Builder $query) => $query->where('amount_cents', '<', 0)),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('30s')
            ->striped();
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Transaktionsdetails')
                    ->schema([
                        Infolists\Components\Grid::make(3)->schema([
                            Infolists\Components\TextEntry::make('id')
                                ->label('Transaktions-ID')
                                ->badge(),

                            Infolists\Components\TextEntry::make('type')
                                ->label('Typ')
                                ->formatStateUsing(fn ($state) => match($state) {
                                    'topup' => 'Aufladung',
                                    'usage' => 'Verbrauch',
                                    'refund' => 'Erstattung',
                                    'adjustment' => 'Anpassung',
                                    'bonus' => 'Bonus',
                                    'fee' => 'Gebühr',
                                    default => $state ?? '-',
                                })
                                ->badge()
                                ->color(fn ($state) => match($state) {
                                    'topup' => 'success',
                                    'usage' => 'danger',
                                    'refund' => 'warning',
                                    'adjustment' => 'info',
                                    'bonus' => 'primary',
                                    'fee' => 'gray',
                                    default => 'gray',
                                }),

                            Infolists\Components\TextEntry::make('created_at')
                                ->label('Datum & Zeit')
                                ->dateTime('d.m.Y H:i:s'),
                        ]),

                        Infolists\Components\Grid::make(3)->schema([
                            Infolists\Components\TextEntry::make('amount_cents')
                                ->label('Betrag')
                                ->formatStateUsing(function ($state) {
                                    $prefix = $state > 0 ? '+' : '';
                                    return $prefix . number_format(($state ?? 0) / 100, 2) . ' €';
                                })
                                ->color(fn ($state) => $state > 0 ? 'success' : 'danger')
                                ->weight('bold'),

                            Infolists\Components\TextEntry::make('balance_before_cents')
                                ->label('Saldo vorher')
                                ->formatStateUsing(fn ($state) => number_format(($state ?? 0) / 100, 2) . ' €'),

                            Infolists\Components\TextEntry::make('balance_after_cents')
                                ->label('Saldo nachher')
                                ->formatStateUsing(fn ($state) => number_format(($state ?? 0) / 100, 2) . ' €')
                                ->color(fn ($state) => $state < 0 ? 'danger' : ($state < 1000 ? 'warning' : 'success'))
                                ->weight('semibold'),
                        ]),

                        Infolists\Components\TextEntry::make('description')
                            ->label('Beschreibung')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTransactions::route('/'),
            'view' => Pages\ViewTransaction::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes()
            ->where('tenant_id', auth()->user()->company_id);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }
}
