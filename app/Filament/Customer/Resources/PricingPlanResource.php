<?php

namespace App\Filament\Customer\Resources;

use App\Filament\Customer\Resources\PricingPlanResource\Pages;
use App\Models\PricingPlan;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Database\Eloquent\Builder;
use Filament\Support\Enums\FontWeight;

class PricingPlanResource extends Resource
{
    protected static ?string $model = PricingPlan::class;
    protected static ?string $navigationIcon = 'heroicon-o-sparkles';
    protected static ?string $navigationGroup = 'Abrechnung';
    protected static ?string $navigationLabel = 'Preispläne';
    protected static ?string $label = 'Preisplan';
    protected static ?string $pluralLabel = 'Preispläne';
    protected static ?int $navigationSort = 4;
    protected static ?string $recordTitleAttribute = 'name';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
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

                Tables\Columns\TextColumn::make('price_monthly')
                    ->label('Monatspreis')
                    ->money('EUR', locale: 'de_DE')
                    ->sortable()
                    ->weight(FontWeight::Bold),

                Tables\Columns\TextColumn::make('minutes_included')
                    ->label('Inkludierte Minuten')
                    ->formatStateUsing(fn (string $state): string =>
                        $state >= 999999 ? '∞ Minuten' : number_format($state, 0, ',', '.') . ' Min'
                    )
                    ->icon('heroicon-o-phone')
                    ->sortable(),

                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Aktiv')
                    ->disabled(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label('Kategorie')
                    ->options([
                        'starter' => '🌱 Starter',
                        'professional' => '💼 Professional',
                        'business' => '🏢 Business',
                        'enterprise' => '🚀 Enterprise',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->defaultSort('price_monthly', 'asc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Übersicht')
                    ->schema([
                        Infolists\Components\Grid::make(3)->schema([
                            Infolists\Components\TextEntry::make('name')
                                ->label('Name')
                                ->weight(FontWeight::Bold),
                            Infolists\Components\TextEntry::make('category')
                                ->label('Kategorie')
                                ->badge(),
                            Infolists\Components\IconEntry::make('is_active')
                                ->label('Aktiv')
                                ->boolean(),
                        ]),
                    ]),

                Infolists\Components\Section::make('Preise')
                    ->schema([
                        Infolists\Components\Grid::make(2)->schema([
                            Infolists\Components\TextEntry::make('price_monthly')
                                ->label('Monatspreis')
                                ->money('EUR', locale: 'de_DE'),
                            Infolists\Components\TextEntry::make('price_yearly')
                                ->label('Jahrespreis')
                                ->money('EUR', locale: 'de_DE'),
                        ]),
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPricingPlans::route('/'),
            'view' => Pages\ViewPricingPlan::route('/{record}'),
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
