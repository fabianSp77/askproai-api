<?php

namespace App\Filament\Admin\Resources\CustomerResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class CallsRelationManager extends RelationManager
{
    protected static string $relationship = 'calls';
    protected static ?string $title = 'Anrufe';
    protected static ?string $recordTitleAttribute = 'call_id';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('call_id')
                    ->label('Anruf ID')
                    ->searchable(),
                Tables\Columns\TextColumn::make('from_number')
                    ->label('Von')
                    ->searchable(),
                Tables\Columns\TextColumn::make('to_number')
                    ->label('An')
                    ->searchable(),
                Tables\Columns\TextColumn::make('start_timestamp')
                    ->label('Datum/Zeit')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('duration_sec')
                    ->label('Dauer')
                    ->formatStateUsing(fn ($state) => $state ? gmdate('i:s', $state) : '-'),
                Tables\Columns\IconColumn::make('call_successful')
                    ->label('Erfolgreich')
                    ->boolean(),
                Tables\Columns\BadgeColumn::make('call_status')
                    ->label('Status')
                    ->colors([
                        'success' => 'completed',
                        'danger' => 'missed',
                        'warning' => 'ongoing',
                    ]),
                Tables\Columns\TextColumn::make('summary')
                    ->label('Zusammenfassung')
                    ->limit(50)
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('call_successful')
                    ->label('Erfolgreich')
                    ->options([
                        '1' => 'Ja',
                        '0' => 'Nein',
                    ]),
            ])
            ->headerActions([
                // Anrufe werden über RetellAI erstellt, nicht manuell
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                // Keine Bulk-Actions für Anrufe
            ])
            ->defaultSort('start_timestamp', 'desc');
    }
}