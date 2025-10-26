<?php

namespace App\Filament\Customer\Resources\CustomerResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class NotesRelationManager extends RelationManager
{
    protected static string $relationship = 'notes';

    protected static ?string $title = 'Notizen';

    protected static ?string $icon = 'heroicon-o-document-text';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('subject')
            ->columns([
                Tables\Columns\IconColumn::make('is_pinned')
                    ->label('')
                    ->boolean()
                    ->trueIcon('heroicon-s-bookmark')
                    ->falseIcon(null)
                    ->trueColor('warning')
                    ->sortable(),
                Tables\Columns\TextColumn::make('subject')
                    ->label('Betreff')
                    ->searchable()
                    ->weight(fn ($record) => $record->text_weight)
                    ->description(fn ($record) => $record->content_preview)
                    ->limit(50),
                Tables\Columns\BadgeColumn::make('type')
                    ->label('Typ')
                    ->colors([
                        'primary' => 'general',
                        'info' => 'appointment',
                        'success' => 'call',
                        'danger' => 'complaint',
                        'warning' => 'feedback',
                        'secondary' => 'internal',
                    ])
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'general' => 'Allgemein',
                        'appointment' => 'Termin',
                        'call' => 'Anruf',
                        'complaint' => 'Beschwerde',
                        'feedback' => 'Feedback',
                        'internal' => 'Intern',
                        default => $state,
                    }),
                Tables\Columns\BadgeColumn::make('visibility')
                    ->label('Sichtbarkeit')
                    ->colors([
                        'success' => 'public',
                        'warning' => 'internal',
                        'danger' => 'management',
                    ])
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'public' => 'Öffentlich',
                        'internal' => 'Intern',
                        'management' => 'Management',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('created_by')
                    ->label('Erstellt von')
                    ->getStateUsing(fn ($record) => $record->creator_name)
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt am')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_important')
                    ->label('Wichtig')
                    ->boolean()
                    ->trueIcon('heroicon-o-exclamation-circle')
                    ->trueColor('danger')
                    ->falseIcon(null),
            ])
            ->defaultSort('is_pinned', 'desc')
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Typ')
                    ->options([
                        'general' => 'Allgemein',
                        'appointment' => 'Termin',
                        'call' => 'Anruf',
                        'complaint' => 'Beschwerde',
                        'feedback' => 'Feedback',
                        'internal' => 'Intern',
                    ]),
                Tables\Filters\SelectFilter::make('visibility')
                    ->label('Sichtbarkeit')
                    ->options([
                        'public' => 'Öffentlich',
                        'internal' => 'Intern',
                        'management' => 'Management',
                    ]),
                Tables\Filters\TernaryFilter::make('is_important')
                    ->label('Wichtig'),
                Tables\Filters\TernaryFilter::make('is_pinned')
                    ->label('Angeheftet'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Anzeigen')
                    ->modalHeading('Notiz anzeigen'),
            ])
            ->emptyStateHeading('Keine Notizen vorhanden')
            ->emptyStateDescription('Noch keine Notizen für diesen Kunden vorhanden.')
            ->emptyStateIcon('heroicon-o-document-text');
    }

    public function isReadOnly(): bool
    {
        return true;
    }
}
