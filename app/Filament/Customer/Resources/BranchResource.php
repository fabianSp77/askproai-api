<?php

namespace App\Filament\Customer\Resources;

use App\Filament\Customer\Resources\BranchResource\Pages;
use App\Models\Branch;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section as InfoSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Grid as InfoGrid;
use Illuminate\Database\Eloquent\Builder;

class BranchResource extends Resource
{
    protected static ?string $model = Branch::class;
    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';
    protected static ?string $navigationGroup = 'Stammdaten';
    protected static ?string $navigationLabel = 'Filialen';
    protected static ?string $modelLabel = 'Filiale';
    protected static ?string $pluralModelLabel = 'Filialen';
    protected static ?int $navigationSort = 3;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('company_id', auth()->user()->company_id)
            ->where('is_active', true)
            ->count();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) =>
                $query->withCount('staff')
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Filiale')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->icon('heroicon-m-building-storefront')
                    ->description(fn ($record) =>
                        $record->city ? $record->city : 'Keine Stadt'
                    ),

                Tables\Columns\TextColumn::make('address')
                    ->label('Adresse')
                    ->searchable()
                    ->limit(40)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('phone')
                    ->label('Telefon')
                    ->searchable()
                    ->icon('heroicon-m-phone')
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktiv')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle'),

                Tables\Columns\TextColumn::make('staff_count')
                    ->label('Mitarbeiter')
                    ->badge()
                    ->color('info')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('opening_hours')
                    ->label('Öffnungszeiten')
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status')
                    ->placeholder('Alle')
                    ->trueLabel('Aktiv')
                    ->falseLabel('Inaktiv'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->defaultPaginationPageOption(25)
            ->persistFiltersInSession()
            ->persistSortInSession()
            ->striped();
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                InfoSection::make('Filial-Informationen')
                    ->schema([
                        InfoGrid::make(2)
                            ->schema([
                                TextEntry::make('name')
                                    ->label('Name')
                                    ->icon('heroicon-o-building-storefront')
                                    ->size('lg')
                                    ->weight('bold'),

                                TextEntry::make('is_active')
                                    ->label('Status')
                                    ->badge()
                                    ->formatStateUsing(fn ($state) => $state ? 'Aktiv' : 'Inaktiv')
                                    ->color(fn ($state) => $state ? 'success' : 'gray'),
                            ]),

                        TextEntry::make('address')
                            ->label('Adresse')
                            ->icon('heroicon-o-map-pin')
                            ->columnSpanFull(),

                        InfoGrid::make(3)
                            ->schema([
                                TextEntry::make('city')
                                    ->label('Stadt'),

                                TextEntry::make('postal_code')
                                    ->label('PLZ'),

                                TextEntry::make('country')
                                    ->label('Land')
                                    ->default('Deutschland'),
                            ]),

                        InfoGrid::make(2)
                            ->schema([
                                TextEntry::make('phone')
                                    ->label('Telefon')
                                    ->icon('heroicon-o-phone')
                                    ->placeholder('Keine Telefonnummer'),

                                TextEntry::make('email')
                                    ->label('E-Mail')
                                    ->icon('heroicon-o-envelope')
                                    ->placeholder('Keine E-Mail'),
                            ]),
                    ])
                    ->icon('heroicon-o-building-storefront')
                    ->collapsible(),

                InfoSection::make('Öffnungszeiten')
                    ->schema([
                        TextEntry::make('opening_hours')
                            ->label('Öffnungszeiten')
                            ->placeholder('Keine Öffnungszeiten hinterlegt')
                            ->columnSpanFull()
                            ->markdown(),

                        TextEntry::make('timezone')
                            ->label('Zeitzone')
                            ->default('Europe/Berlin'),
                    ])
                    ->icon('heroicon-o-clock')
                    ->collapsible()
                    ->collapsed(true),

                InfoSection::make('Weitere Details')
                    ->schema([
                        InfoGrid::make(2)
                            ->schema([
                                TextEntry::make('staff_count')
                                    ->label('Anzahl Mitarbeiter')
                                    ->getStateUsing(fn ($record) => $record->staff()->count())
                                    ->badge()
                                    ->color('info'),

                                TextEntry::make('created_at')
                                    ->label('Erstellt am')
                                    ->dateTime('d.m.Y H:i'),
                            ]),

                        TextEntry::make('notes')
                            ->label('Notizen')
                            ->placeholder('Keine Notizen')
                            ->columnSpanFull(),
                    ])
                    ->icon('heroicon-o-information-circle')
                    ->collapsible()
                    ->collapsed(true),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBranches::route('/'),
            'view' => Pages\ViewBranch::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('company_id', auth()->user()->company_id);
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

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'address', 'city', 'phone'];
    }
}
