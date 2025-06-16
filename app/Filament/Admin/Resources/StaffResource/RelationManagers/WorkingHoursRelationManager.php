<?php

namespace App\Filament\Admin\Resources\StaffResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class WorkingHoursRelationManager extends RelationManager
{
    protected static string $relationship = 'workingHours';
    protected static ?string $title = 'Arbeitszeiten';
    protected static ?string $modelLabel = 'Arbeitszeit';
    protected static ?string $pluralModelLabel = 'Arbeitszeiten';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('day_of_week')
                    ->label('Wochentag')
                    ->options([
                        0 => 'Sonntag',
                        1 => 'Montag',
                        2 => 'Dienstag',
                        3 => 'Mittwoch',
                        4 => 'Donnerstag',
                        5 => 'Freitag',
                        6 => 'Samstag',
                    ])
                    ->required(),
                Forms\Components\TimePicker::make('start_time')
                    ->label('Startzeit')
                    ->required(),
                Forms\Components\TimePicker::make('end_time')
                    ->label('Endzeit')
                    ->required(),
                Forms\Components\Toggle::make('is_active')
                    ->label('Aktiv')
                    ->default(true),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('day_of_week')
                    ->label('Wochentag')
                    ->formatStateUsing(fn ($state) => [
                        0 => 'Sonntag',
                        1 => 'Montag',
                        2 => 'Dienstag',
                        3 => 'Mittwoch',
                        4 => 'Donnerstag',
                        5 => 'Freitag',
                        6 => 'Samstag',
                    ][$state] ?? ''),
                Tables\Columns\TextColumn::make('start_time')
                    ->label('Von'),
                Tables\Columns\TextColumn::make('end_time')
                    ->label('Bis'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktiv')
                    ->boolean(),
            ])
            ->defaultSort('day_of_week')
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}
