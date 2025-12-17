<?php

namespace App\Filament\Resources\StaffResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class AppointmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'appointments';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('customer_id')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\Select::make('service_id')
                    ->relationship('service', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\DateTimePicker::make('start_time')
                    ->required()
                    ->native(false),
                Forms\Components\DateTimePicker::make('end_time')
                    ->required()
                    ->native(false)
                    ->after('start_time'),
                Forms\Components\Select::make('status')
                    ->options([
                        'scheduled' => 'Scheduled',
                        'confirmed' => 'Confirmed',
                        'in_progress' => 'In Progress',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                        'no_show' => 'No Show',
                    ])
                    ->default('scheduled')
                    ->required(),
                Forms\Components\Textarea::make('notes')
                    ->maxLength(1000)
                    ->rows(3),
                Forms\Components\Toggle::make('is_recurring')
                    ->default(false),
                Forms\Components\Toggle::make('send_reminder')
                    ->default(true),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('service.name')
                    ->label('Service')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('start_time')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_time')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'info' => 'scheduled',
                        'success' => ['confirmed', 'completed'],
                        'warning' => 'in_progress',
                        'danger' => ['cancelled', 'no_show'],
                    ]),
                Tables\Columns\TextColumn::make('rescheduled_count')
                    ->label('Verschoben')
                    ->badge()
                    ->color('warning')
                    ->icon('heroicon-m-arrow-path-rounded-square')
                    ->formatStateUsing(function ($state, $record) {
                        if (!$state || $state == 0) {
                            return null;
                        }
                        return $state > 1 ? "Verschoben ({$state}x)" : 'Verschoben';
                    })
                    ->tooltip(function ($record) {
                        if (!$record->rescheduled_at) {
                            return null;
                        }
                        $info = "Zuletzt verschoben: " . $record->rescheduled_at->format('d.m.Y H:i');
                        if ($record->previous_starts_at) {
                            $info .= "\nUrsprünglich: " . $record->previous_starts_at->format('d.m.Y H:i');
                        }
                        return $info;
                    })
                    ->visible(fn ($record) => $record && $record->rescheduled_count > 0)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('duration')
                    ->label('Duration')
                    ->getStateUsing(fn ($record) =>
                        $record->start_time && $record->end_time
                            ? $record->start_time->diffForHumans($record->end_time, true)
                            : '-'
                    ),
                Tables\Columns\IconColumn::make('is_recurring')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'scheduled' => 'Scheduled',
                        'confirmed' => 'Confirmed',
                        'in_progress' => 'In Progress',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                        'no_show' => 'No Show',
                    ]),
                Tables\Filters\SelectFilter::make('customer')
                    ->relationship('customer', 'name'),
                Tables\Filters\SelectFilter::make('service')
                    ->relationship('service', 'name'),
                Tables\Filters\Filter::make('today')
                    ->label('Today')
                    ->query(fn ($query) => $query->whereDate('start_time', today())),
                Tables\Filters\Filter::make('this_week')
                    ->label('This Week')
                    ->query(fn ($query) => $query->whereBetween('start_time', [now()->startOfWeek(), now()->endOfWeek()])),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('start_time', 'desc');
    }
}