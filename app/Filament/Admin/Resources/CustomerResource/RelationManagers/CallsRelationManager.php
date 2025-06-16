<?php

namespace App\Filament\Admin\Resources\CustomerResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CallsRelationManager extends RelationManager
{
    protected static string $relationship = 'calls';
    protected static ?string $title = 'Anrufe';
    protected static ?string $modelLabel = 'Anruf';
    protected static ?string $pluralModelLabel = 'Anrufe';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('from_number')
                    ->label('Von Nummer')
                    ->tel()
                    ->required(),
                Forms\Components\Select::make('status')
                    ->label('Status')
                    ->options([
                        'completed' => 'Abgeschlossen',
                        'failed' => 'Fehlgeschlagen',
                        'no-answer' => 'Keine Antwort',
                        'busy' => 'Besetzt',
                        'cancelled' => 'Abgebrochen',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('duration')
                    ->label('Dauer (Sekunden)')
                    ->numeric()
                    ->minValue(0),
                Forms\Components\Textarea::make('transcript')
                    ->label('Transkript')
                    ->rows(5),
                Forms\Components\Textarea::make('notes')
                    ->label('Notizen')
                    ->rows(3),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('from_number')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Datum & Zeit')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('from_number')
                    ->label('Telefonnummer')
                    ->searchable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'success' => 'completed',
                        'danger' => 'failed',
                        'warning' => fn ($state) => in_array($state, ['no-answer', 'busy']),
                        'gray' => 'cancelled',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'completed' => 'Abgeschlossen',
                        'failed' => 'Fehlgeschlagen',
                        'no-answer' => 'Keine Antwort',
                        'busy' => 'Besetzt',
                        'cancelled' => 'Abgebrochen',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('duration')
                    ->label('Dauer')
                    ->formatStateUsing(fn ($state) => $state ? gmdate('i:s', $state) : '-'),
                Tables\Columns\IconColumn::make('has_transcript')
                    ->label('Transkript')
                    ->boolean()
                    ->trueIcon('heroicon-o-document-text')
                    ->falseIcon('heroicon-o-x-mark')
                    ->getStateUsing(fn ($record) => !empty($record->transcript)),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'completed' => 'Abgeschlossen',
                        'failed' => 'Fehlgeschlagen',
                        'no-answer' => 'Keine Antwort',
                        'busy' => 'Besetzt',
                        'cancelled' => 'Abgebrochen',
                    ]),
                Tables\Filters\Filter::make('with_transcript')
                    ->label('Mit Transkript')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('transcript')->where('transcript', '!=', '')),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('view_transcript')
                    ->label('Transkript anzeigen')
                    ->icon('heroicon-m-document-text')
                    ->modalHeading('Anruf-Transkript')
                    ->modalContent(fn ($record) => view('filament.modals.transcript', ['transcript' => $record->transcript]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Schließen')
                    ->visible(fn ($record) => !empty($record->transcript)),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}