<?php

namespace App\Filament\Customer\Resources;

use App\Filament\Customer\Resources\BalanceBonusTierResource\Pages;
use App\Models\BalanceBonusTier;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BalanceBonusTierResource extends Resource
{
    protected static ?string $model = BalanceBonusTier::class;
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationLabel = 'Bonus-Stufen';
    protected static ?string $modelLabel = 'Bonus-Stufe';
    protected static ?string $pluralModelLabel = 'Bonus-Stufen';
    protected static ?string $navigationGroup = 'Abrechnung';
    protected static ?int $navigationSort = 5;

    /**
     * SECURITY: Safe scope bypass - Global reference table (no company_id column)
     * Pattern: withoutGlobalScopes() for tenant-agnostic system data
     * This table contains system-wide bonus tier definitions shared across all tenants.
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScopes();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Tier Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->icon(fn ($record) => match($record->icon ?? 'trophy') {
                        'medal' => 'heroicon-o-star',
                        'star' => 'heroicon-o-star',
                        'crown' => 'heroicon-o-sparkles',
                        'gem' => 'heroicon-o-cube-transparent',
                        'fire' => 'heroicon-o-fire',
                        default => 'heroicon-o-trophy'
                    })
                    ->color(fn ($record) => match(strtolower($record->name)) {
                        'bronze' => 'warning',
                        'silver' => 'gray',
                        'gold' => 'warning',
                        'platinum' => 'primary',
                        'diamond' => 'success',
                        default => 'secondary'
                    }),

                Tables\Columns\TextColumn::make('min_amount')
                    ->label('Min Amount')
                    ->money('EUR')
                    ->sortable()
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('max_amount')
                    ->label('Max Amount')
                    ->money('EUR')
                    ->sortable()
                    ->alignEnd()
                    ->placeholder('Unlimited'),

                Tables\Columns\TextColumn::make('bonus_percentage')
                    ->label('Bonus %')
                    ->suffix('%')
                    ->numeric(2)
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        $state >= 20 => 'success',
                        $state >= 10 => 'warning',
                        $state >= 5 => 'primary',
                        default => 'gray'
                    }),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktiv')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Aktiv')
                    ->placeholder('Alle')
                    ->trueLabel('Nur aktive')
                    ->falseLabel('Nur inaktive'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->defaultSort('min_amount', 'asc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBalanceBonusTiers::route('/'),
            'view' => Pages\ViewBalanceBonusTier::route('/{record}'),
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
