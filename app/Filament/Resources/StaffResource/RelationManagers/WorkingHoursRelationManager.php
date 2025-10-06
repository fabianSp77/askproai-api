<?php

namespace App\Filament\Resources\StaffResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class WorkingHoursRelationManager extends RelationManager
{
    protected static string $relationship = 'workingHours';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('day_of_week')
                    ->options([
                        0 => 'Sunday',
                        1 => 'Monday',
                        2 => 'Tuesday',
                        3 => 'Wednesday',
                        4 => 'Thursday',
                        5 => 'Friday',
                        6 => 'Saturday',
                    ])
                    ->required(),
                Forms\Components\TimePicker::make('start_time')
                    ->required()
                    ->seconds(false),
                Forms\Components\TimePicker::make('end_time')
                    ->required()
                    ->seconds(false)
                    ->after('start_time'),
                Forms\Components\TimePicker::make('break_start')
                    ->seconds(false),
                Forms\Components\TimePicker::make('break_end')
                    ->seconds(false)
                    ->after('break_start'),
                Forms\Components\Toggle::make('is_active')
                    ->label('Active')
                    ->default(true),
                Forms\Components\Toggle::make('is_available_online')
                    ->label('Available Online')
                    ->default(true),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('day_of_week')
            ->columns([
                Tables\Columns\TextColumn::make('day_of_week')
                    ->label('Day')
                    ->formatStateUsing(fn ($state) => match($state) {
                        0 => 'Sunday',
                        1 => 'Monday',
                        2 => 'Tuesday',
                        3 => 'Wednesday',
                        4 => 'Thursday',
                        5 => 'Friday',
                        6 => 'Saturday',
                        default => $state,
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('start_time')
                    ->time('H:i'),
                Tables\Columns\TextColumn::make('end_time')
                    ->time('H:i'),
                Tables\Columns\TextColumn::make('break_time')
                    ->label('Break')
                    ->getStateUsing(fn ($record) =>
                        $record->break_start && $record->break_end
                            ? $record->break_start->format('H:i') . ' - ' . $record->break_end->format('H:i')
                            : '-'
                    ),
                Tables\Columns\TextColumn::make('total_hours')
                    ->label('Total Hours')
                    ->getStateUsing(function ($record) {
                        if (!$record->start_time || !$record->end_time) {
                            return '-';
                        }
                        $start = \Carbon\Carbon::parse($record->start_time);
                        $end = \Carbon\Carbon::parse($record->end_time);
                        $total = $end->diffInMinutes($start);

                        if ($record->break_start && $record->break_end) {
                            $breakStart = \Carbon\Carbon::parse($record->break_start);
                            $breakEnd = \Carbon\Carbon::parse($record->break_end);
                            $total -= $breakEnd->diffInMinutes($breakStart);
                        }

                        $hours = floor($total / 60);
                        $minutes = $total % 60;
                        return sprintf('%dh %02dm', $hours, $minutes);
                    }),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_available_online')
                    ->label('Online')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->trueLabel('Active days')
                    ->falseLabel('Inactive days')
                    ->native(false),
                Tables\Filters\TernaryFilter::make('is_available_online')
                    ->label('Online Availability')
                    ->boolean()
                    ->trueLabel('Available online')
                    ->falseLabel('Not available online')
                    ->native(false),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('day_of_week', 'asc')
            ->reorderable(false);
    }
}