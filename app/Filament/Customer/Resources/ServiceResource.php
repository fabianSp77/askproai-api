<?php

namespace App\Filament\Customer\Resources;

use App\Filament\Customer\Resources\ServiceResource\Pages;
use App\Models\Service;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section as InfoSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Grid as InfoGrid;
use Illuminate\Database\Eloquent\Builder;

class ServiceResource extends Resource
{
    protected static ?string $model = Service::class;
    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';
    protected static ?string $navigationGroup = 'Stammdaten';
    protected static ?string $navigationLabel = 'Dienstleistungen';
    protected static ?string $modelLabel = 'Dienstleistung';
    protected static ?string $pluralModelLabel = 'Dienstleistungen';
    protected static ?int $navigationSort = 1;

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
                $query->withCount('bookings')
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Service')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn ($record) => $record->category ?: 'Keine Kategorie')
                    ->icon('heroicon-m-wrench-screwdriver'),

                Tables\Columns\TextColumn::make('duration_minutes')
                    ->label('Dauer')
                    ->formatStateUsing(fn ($state) => $state . ' Min')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                Tables\Columns\TextColumn::make('price')
                    ->label('Preis')
                    ->money('EUR')
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('category')
                    ->label('Kategorie')
                    ->badge()
                    ->color('gray')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktiv')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle'),

                Tables\Columns\IconColumn::make('is_online_bookable')
                    ->label('Online buchbar')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('bookings_count')
                    ->label('Buchungen')
                    ->badge()
                    ->color('info')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->label('Kategorie')
                    ->options(function () {
                        return Service::where('company_id', auth()->user()->company_id)
                            ->distinct()
                            ->pluck('category', 'category')
                            ->toArray();
                    }),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status')
                    ->placeholder('Alle')
                    ->trueLabel('Aktiv')
                    ->falseLabel('Inaktiv'),

                Tables\Filters\TernaryFilter::make('is_online_bookable')
                    ->label('Online buchbar')
                    ->placeholder('Alle')
                    ->trueLabel('Ja')
                    ->falseLabel('Nein'),
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
                InfoSection::make('Service-Informationen')
                    ->schema([
                        InfoGrid::make(2)
                            ->schema([
                                TextEntry::make('name')
                                    ->label('Name')
                                    ->icon('heroicon-o-wrench-screwdriver')
                                    ->size('lg'),

                                TextEntry::make('category')
                                    ->label('Kategorie')
                                    ->badge()
                                    ->placeholder('Keine Kategorie'),
                            ]),

                        TextEntry::make('description')
                            ->label('Beschreibung')
                            ->placeholder('Keine Beschreibung')
                            ->columnSpanFull(),

                        InfoGrid::make(3)
                            ->schema([
                                TextEntry::make('duration_minutes')
                                    ->label('Dauer')
                                    ->formatStateUsing(fn ($state) => $state . ' Min')
                                    ->badge()
                                    ->color('info'),

                                TextEntry::make('price')
                                    ->label('Preis')
                                    ->money('EUR')
                                    ->icon('heroicon-o-currency-euro'),

                                TextEntry::make('booking_type')
                                    ->label('Buchungstyp')
                                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                                        'single' => 'Einzeltermin',
                                        'series' => 'Serie',
                                        'package' => 'Paket',
                                        'composite' => 'Kombination',
                                        default => $state ?? 'Einzeltermin',
                                    }),
                            ]),

                        InfoGrid::make(3)
                            ->schema([
                                TextEntry::make('is_active')
                                    ->label('Aktiv')
                                    ->badge()
                                    ->formatStateUsing(fn ($state) => $state ? 'Ja' : 'Nein')
                                    ->color(fn ($state) => $state ? 'success' : 'gray'),

                                TextEntry::make('is_online_bookable')
                                    ->label('Online buchbar')
                                    ->badge()
                                    ->formatStateUsing(fn ($state) => $state ? 'Ja' : 'Nein')
                                    ->color(fn ($state) => $state ? 'success' : 'gray'),

                                TextEntry::make('requires_approval')
                                    ->label('Genehmigung erforderlich')
                                    ->badge()
                                    ->formatStateUsing(fn ($state) => $state ? 'Ja' : 'Nein')
                                    ->color(fn ($state) => $state ? 'warning' : 'gray'),
                            ]),
                    ])
                    ->icon('heroicon-o-wrench-screwdriver')
                    ->collapsible(),

                InfoSection::make('Kapazität & Einstellungen')
                    ->schema([
                        InfoGrid::make(3)
                            ->schema([
                                TextEntry::make('min_booking_notice_hours')
                                    ->label('Min. Vorlaufzeit')
                                    ->formatStateUsing(fn ($state) => $state ? $state . ' Std' : 'Keine'),

                                TextEntry::make('max_advance_booking_days')
                                    ->label('Max. Vorausbuchung')
                                    ->formatStateUsing(fn ($state) => $state ? $state . ' Tage' : 'Keine Begrenzung'),

                                TextEntry::make('buffer_time_before')
                                    ->label('Puffer davor')
                                    ->formatStateUsing(fn ($state) => $state ? $state . ' Min' : 'Kein Puffer'),
                            ]),

                        InfoGrid::make(2)
                            ->schema([
                                TextEntry::make('max_participants')
                                    ->label('Max. Teilnehmer')
                                    ->badge()
                                    ->placeholder('Keine Begrenzung'),

                                TextEntry::make('priority')
                                    ->label('Priorität')
                                    ->badge()
                                    ->color(fn ($state) => match($state) {
                                        1 => 'danger',
                                        2 => 'warning',
                                        3 => 'info',
                                        default => 'gray',
                                    })
                                    ->formatStateUsing(fn ($state) => match($state) {
                                        1 => 'Hoch',
                                        2 => 'Mittel',
                                        3 => 'Niedrig',
                                        default => 'Normal',
                                    }),
                            ]),

                        TextEntry::make('notes')
                            ->label('Interne Notizen')
                            ->placeholder('Keine Notizen')
                            ->columnSpanFull(),
                    ])
                    ->icon('heroicon-o-cog')
                    ->collapsible()
                    ->collapsed(true),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServices::route('/'),
            'view' => Pages\ViewService::route('/{record}'),
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
        return ['name', 'description', 'category'];
    }
}
