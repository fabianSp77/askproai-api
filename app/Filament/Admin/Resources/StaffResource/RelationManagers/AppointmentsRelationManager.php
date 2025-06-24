<?php

namespace App\Filament\Admin\Resources\StaffResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class AppointmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'appointments';
    protected static ?string $title = 'Termine';
    protected static ?string $modelLabel = 'Termin';
    protected static ?string $pluralModelLabel = 'Termine';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DateTimePicker::make('start_time')
                    ->label('Startzeit')
                    ->required(),
                Forms\Components\DateTimePicker::make('end_time')
                    ->label('Endzeit')
                    ->required(),
                Forms\Components\Select::make('customer_id')
                    ->relationship('customer', 'name')
                    ->label('Kunde')
                    ->searchable()
                    ->required(),
                Forms\Components\Select::make('service_id')
                    ->relationship('service', 'name')
                    ->label('Dienstleistung')
                    ->required(),
                Forms\Components\Select::make('status')
                    ->label('Status')
                    ->options([
                        'scheduled' => 'Geplant',
                        'confirmed' => 'Bestätigt',
                        'completed' => 'Abgeschlossen',
                        'cancelled' => 'Abgesagt',
                        'no_show' => 'Nicht erschienen',
                    ])
                    ->default('scheduled')
                    ->required(),
                Forms\Components\Textarea::make('notes')
                    ->label('Notizen')
                    ->rows(3),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('start_time')
                    ->label('Termin')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Kunde')
                    ->searchable()
                    ->getStateUsing(fn ($record) => $record?->customer?->name ?? '-'),
                Tables\Columns\TextColumn::make('service.name')
                    ->label('Dienstleistung')
                    ->getStateUsing(fn ($record) => $record?->service?->name ?? '-'),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'primary' => 'scheduled',
                        'success' => 'confirmed',
                        'success' => 'completed',
                        'danger' => 'cancelled',
                        'warning' => 'no_show',
                    ]),
            ])
            ->defaultSort('start_time', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'scheduled' => 'Geplant',
                        'confirmed' => 'Bestätigt',
                        'completed' => 'Abgeschlossen',
                        'cancelled' => 'Abgesagt',
                        'no_show' => 'Nicht erschienen',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ]);
    }
}
