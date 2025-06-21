<?php

namespace App\Filament\Admin\Resources;

use App\Models\WorkingHour;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;

class WorkingHourResource extends Resource
{
    protected static ?string $model = WorkingHour::class;
    protected static ?string $navigationIcon = 'heroicon-o-clock';
    protected static ?string $navigationLabel = 'Arbeitszeiten';
    protected static ?string $navigationGroup = 'Personal & Services';
    protected static ?int $navigationSort = 250;

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
        return $table->columns([
            Tables\Columns\TextColumn::make('staff.name')->label('Mitarbeiter'),
            Tables\Columns\TextColumn::make('weekday')->label('Tag'),
            Tables\Columns\TextColumn::make('start')->label('Beginn'),
            Tables\Columns\TextColumn::make('end')->label('Ende'),
        ])
        ->filters([])
        ->actions([
            Tables\Actions\EditAction::make(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Admin\Resources\WorkingHourResource\Pages\ListWorkingHours::route('/'),
            'create' => \App\Filament\Admin\Resources\WorkingHourResource\Pages\CreateWorkingHour::route('/create'),
            'edit' => \App\Filament\Admin\Resources\WorkingHourResource\Pages\EditWorkingHour::route('/{record}/edit'),
        ];
    }
}
