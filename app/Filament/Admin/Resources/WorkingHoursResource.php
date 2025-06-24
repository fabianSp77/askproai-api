<?php

namespace App\Filament\Admin\Resources;

use Filament\Resources\Resource;
use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\WorkingHours;

class WorkingHoursResource extends Resource
{
    protected static ?string $model = WorkingHours::class;
//     protected static ?string $navigationIcon = 'heroicon-o-clock';
    protected static ?string $navigationGroup = 'Personal & Services';
    protected static ?string $navigationLabel = 'Arbeitszeiten';
    protected static bool $shouldRegisterNavigation = false; // Disabled - using WorkingHourResource instead

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('staff_id')
                ->label('Mitarbeiter')
                ->relationship('staff', 'name')
                ->required(),
            Forms\Components\Select::make('weekday')
                ->label('Wochentag')
                ->options([
                    1 => 'Montag',
                    2 => 'Dienstag',
                    3 => 'Mittwoch',
                    4 => 'Donnerstag',
                    5 => 'Freitag',
                    6 => 'Samstag',
                    7 => 'Sonntag',
                ])
                ->required(),
            Forms\Components\TimePicker::make('start')->label('Beginn')->required(),
            Forms\Components\TimePicker::make('end')->label('Ende')->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('staff.branch.company.name')->label('Unternehmen')->sortable()->searchable()
                    ->getStateUsing(fn ($record) => $record?->staff?->branch?->company?->name ?? '-'),
                Tables\Columns\TextColumn::make('staff.branch.name')->label('Filiale')->sortable()->searchable()
                    ->getStateUsing(fn ($record) => $record?->staff?->branch?->name ?? '-'),
                Tables\Columns\TextColumn::make('staff.name')->label('Mitarbeiter')->sortable()->searchable()
                    ->getStateUsing(fn ($record) => $record?->staff?->name ?? '-'),
                Tables\Columns\TextColumn::make('weekday')->label('Wochentag')->formatStateUsing(function ($state) {
                    $map = [
                        1 => 'Montag',
                        2 => 'Dienstag',
                        3 => 'Mittwoch',
                        4 => 'Donnerstag',
                        5 => 'Freitag',
                        6 => 'Samstag',
                        7 => 'Sonntag',
                    ];
                    return $map[$state] ?? $state;
                }),
                Tables\Columns\TextColumn::make('start')->label('Beginn'),
                Tables\Columns\TextColumn::make('end')->label('Ende'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('company_id')
                    ->label('Unternehmen')
                    ->relationship('staff.branch.company', 'name'),
                Tables\Filters\SelectFilter::make('branch_id')
                    ->label('Filiale')
                    ->relationship('staff.branch', 'name'),
                Tables\Filters\SelectFilter::make('staff_id')
                    ->label('Mitarbeiter')
                    ->relationship('staff', 'name'),
            ])
            ->defaultSort('staff.branch.company.name')
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Admin\Resources\WorkingHoursResource\Pages\ListWorkingHours::route('/'),
            'create' => \App\Filament\Admin\Resources\WorkingHoursResource\Pages\CreateWorkingHours::route('/create'),
            'edit' => \App\Filament\Admin\Resources\WorkingHoursResource\Pages\EditWorkingHours::route('/{record}/edit'),
        ];
    }
}
