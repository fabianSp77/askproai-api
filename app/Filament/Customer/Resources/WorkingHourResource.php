<?php

namespace App\Filament\Customer\Resources;

use App\Filament\Customer\Resources\WorkingHourResource\Pages;
use App\Models\WorkingHour;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;

class WorkingHourResource extends Resource
{
    protected static ?string $model = WorkingHour::class;
    protected static ?string $navigationIcon = 'heroicon-o-clock';
    protected static ?string $navigationGroup = 'Stammdaten';
    protected static ?string $navigationLabel = 'Arbeitszeiten';
    protected static ?string $modelLabel = 'Arbeitszeit';
    protected static ?string $pluralModelLabel = 'Arbeitszeiten';
    protected static ?int $navigationSort = 4;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('staff.name')
                    ->label('Mitarbeiter')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-m-user'),

                Tables\Columns\TextColumn::make('branch.name')
                    ->label('Filiale')
                    ->badge()
                    ->color('gray')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('day_of_week')
                    ->label('Wochentag')
                    ->formatStateUsing(fn ($state) => match($state) {
                        0 => 'Sonntag',
                        1 => 'Montag',
                        2 => 'Dienstag',
                        3 => 'Mittwoch',
                        4 => 'Donnerstag',
                        5 => 'Freitag',
                        6 => 'Samstag',
                        default => 'Unbekannt',
                    })
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        0, 6 => 'warning',
                        default => 'success',
                    }),

                Tables\Columns\TextColumn::make('start_time')
                    ->label('Von')
                    ->time('H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('end_time')
                    ->label('Bis')
                    ->time('H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('duration')
                    ->label('Dauer')
                    ->getStateUsing(function ($record) {
                        if (!$record->start_time || !$record->end_time) return '-';
                        $start = \Carbon\Carbon::parse($record->start_time);
                        $end = \Carbon\Carbon::parse($record->end_time);
                        return $start->diff($end)->format('%h Std %i Min');
                    })
                    ->badge()
                    ->color('info'),

                Tables\Columns\IconColumn::make('is_available')
                    ->label('Verfügbar')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('staff_id')
                    ->label('Mitarbeiter')
                    ->relationship('staff', 'name', fn ($query) =>
                        $query->where('company_id', auth()->user()->company_id)
                    )
                    ->searchable()
                    ->preload(),

                SelectFilter::make('branch_id')
                    ->label('Filiale')
                    ->relationship('branch', 'name', fn ($query) =>
                        $query->where('company_id', auth()->user()->company_id)
                    )
                    ->searchable()
                    ->preload(),

                SelectFilter::make('day_of_week')
                    ->label('Wochentag')
                    ->options([
                        1 => 'Montag',
                        2 => 'Dienstag',
                        3 => 'Mittwoch',
                        4 => 'Donnerstag',
                        5 => 'Freitag',
                        6 => 'Samstag',
                        0 => 'Sonntag',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->defaultSort('day_of_week', 'asc')
            ->defaultPaginationPageOption(25)
            ->persistFiltersInSession()
            ->persistSortInSession()
            ->striped();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWorkingHours::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('staff', fn ($query) =>
                $query->where('company_id', auth()->user()->company_id)
            )
            ->with(['staff:id,name', 'branch:id,name']);
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
        return ['staff.name'];
    }
}
