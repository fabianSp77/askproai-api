<?php

namespace App\Filament\Customer\Resources;

use App\Filament\Customer\Resources\CurrencyExchangeRateResource\Pages;
use App\Models\CurrencyExchangeRate;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CurrencyExchangeRateResource extends Resource
{
    protected static ?string $model = CurrencyExchangeRate::class;
    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?string $navigationLabel = 'Wechselkurse';
    protected static ?string $modelLabel = 'Wechselkurs';
    protected static ?string $pluralModelLabel = 'Wechselkurse';
    protected static ?string $navigationGroup = 'Abrechnung';
    protected static ?int $navigationSort = 6;

    /**
     * SECURITY: Safe scope bypass - Global reference table (no company_id column)
     * Pattern: withoutGlobalScopes() for tenant-agnostic system data
     * This table contains system-wide exchange rates shared across all tenants.
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScopes();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('from_currency')
                    ->label('Von')
                    ->badge()
                    ->color('primary')
                    ->sortable(),

                Tables\Columns\IconColumn::make('direction')
                    ->label('')
                    ->icon('heroicon-o-arrow-right')
                    ->color('gray'),

                Tables\Columns\TextColumn::make('to_currency')
                    ->label('Nach')
                    ->badge()
                    ->color('success')
                    ->sortable(),

                Tables\Columns\TextColumn::make('rate')
                    ->label('Kurs')
                    ->formatStateUsing(fn ($state) => number_format($state, 6, ',', '.'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('source')
                    ->label('Quelle')
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'manual' => 'Manuell',
                        'ecb' => 'ECB',
                        'fixer' => 'Fixer.io',
                        'openexchange' => 'Open Exchange',
                        default => ucfirst($state)
                    })
                    ->badge()
                    ->color(fn (string $state): string => match($state) {
                        'manual' => 'gray',
                        'ecb' => 'success',
                        'fixer' => 'info',
                        'openexchange' => 'warning',
                        default => 'secondary'
                    }),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktiv')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('valid_from')
                    ->label('Gültig ab')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('from_currency')
                    ->options([
                        'USD' => 'USD',
                        'EUR' => 'EUR',
                        'GBP' => 'GBP'
                    ])
                    ->label('Von Währung'),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Aktiv')
                    ->placeholder('Alle')
                    ->trueLabel('Nur aktive')
                    ->falseLabel('Nur inaktive'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCurrencyExchangeRates::route('/'),
            'view' => Pages\ViewCurrencyExchangeRate::route('/{record}'),
        ];
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
}
