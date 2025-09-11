<?php

namespace App\Filament\Admin\Resources\CustomerResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class AppointmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'appointments';
    protected static ?string $title = 'Termine';
    protected static ?string $recordTitleAttribute = 'id';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DateTimePicker::make('starts_at')
                    ->label('Beginnt um')
                    ->required(),
                Forms\Components\DateTimePicker::make('ends_at')
                    ->label('Endet um')
                    ->required()
                    ->after('starts_at'),
                Forms\Components\Select::make('status')
                    ->label('Status')
                    ->options([
                        'scheduled' => 'Geplant',
                        'completed' => 'Abgeschlossen',
                        'cancelled' => 'Abgesagt',
                        'no-show' => 'Nicht erschienen',
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
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('starts_at')
                    ->label('Beginnt')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('ends_at')
                    ->label('Endet')
                    ->dateTime('d.m.Y H:i'),
                Tables\Columns\TextColumn::make('staff.name')
                    ->label('Mitarbeiter')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('service.name')
                    ->label('Service')
                    ->toggleable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'info' => 'scheduled',
                        'success' => 'completed',
                        'danger' => 'cancelled',
                        'warning' => 'no-show',
                    ]),
                Tables\Columns\TextColumn::make('calcom_booking_id')
                    ->label('Cal.com ID')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'scheduled' => 'Geplant',
                        'completed' => 'Abgeschlossen',
                        'cancelled' => 'Abgesagt',
                        'no-show' => 'Nicht erschienen',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Termin erstellen'),
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
            ->defaultSort('starts_at', 'desc');
    }
}