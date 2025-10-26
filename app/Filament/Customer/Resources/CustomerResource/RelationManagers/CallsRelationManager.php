<?php

namespace App\Filament\Customer\Resources\CustomerResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CallsRelationManager extends RelationManager
{
    protected static string $relationship = 'calls';

    protected static ?string $title = 'Anrufe';

    protected static ?string $icon = 'heroicon-o-phone';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('created_at')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Anrufzeit')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->icon('heroicon-m-phone')
                    ->description(fn ($record) => $record->duration_formatted),
                Tables\Columns\BadgeColumn::make('direction')
                    ->label('Richtung')
                    ->colors([
                        'success' => 'inbound',
                        'primary' => 'outbound',
                    ])
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'inbound' => '← Eingehend',
                        'outbound' => '→ Ausgehend',
                        default => $state,
                    }),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'success' => 'answered',
                        'danger' => 'missed',
                        'warning' => 'busy',
                        'secondary' => 'voicemail',
                    ])
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'answered' => 'Beantwortet',
                        'missed' => 'Verpasst',
                        'busy' => 'Besetzt',
                        'failed' => 'Fehlgeschlagen',
                        'voicemail' => 'Voicemail',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('staff.name')
                    ->label('Mitarbeiter')
                    ->searchable()
                    ->default('-'),
                Tables\Columns\TextColumn::make('session_outcome')
                    ->label('Ergebnis')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match($state) {
                        'appointment_scheduled' => 'Termin vereinbart',
                        'appointment_cancelled' => 'Termin abgesagt',
                        'callback_requested' => 'Rückruf erwünscht',
                        'info_provided' => 'Info gegeben',
                        'transferred' => 'Weitergeleitet',
                        'no_action' => 'Keine Aktion',
                        null => '-',
                        default => $state,
                    })
                    ->toggleable(),
                Tables\Columns\IconColumn::make('appointment_made')
                    ->label('Termin')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->toggleable()
                    ->tooltip(fn ($record) => $record->appointment_status_info['tooltip'])
                    ->color(fn ($record) => $record->appointment_status_info['color']),
                Tables\Columns\TextColumn::make('booking_status')
                    ->label('Buchungsstatus')
                    ->badge()
                    ->getStateUsing(fn ($record) => $record->booking_status)
                    ->color(fn (string $state): string => match ($state) {
                        'Fehlgeschlagen' => 'danger',
                        'Erfolgreich' => 'success',
                        'Nicht versucht' => 'gray',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'Fehlgeschlagen' => 'heroicon-o-exclamation-triangle',
                        'Erfolgreich' => 'heroicon-o-check-circle',
                        default => 'heroicon-o-minus-circle',
                    })
                    ->toggleable(),
                Tables\Columns\TextColumn::make('from_number')
                    ->label('Von')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('to_number')
                    ->label('An')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('recording_url')
                    ->label('Aufnahme')
                    ->icon('heroicon-o-microphone')
                    ->boolean()
                    ->getStateUsing(fn ($record) => $record->has_recording)
                    ->action(fn ($record) => $record->recording_url ? redirect($record->recording_url) : null),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt')
                    ->dateTime('d.m.Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('direction')
                    ->label('Richtung')
                    ->options([
                        'inbound' => 'Eingehend',
                        'outbound' => 'Ausgehend',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'answered' => 'Beantwortet',
                        'missed' => 'Verpasst',
                        'busy' => 'Besetzt',
                        'voicemail' => 'Voicemail',
                    ]),
                Tables\Filters\Filter::make('today')
                    ->label('Heute')
                    ->query(fn (Builder $query): Builder => $query->whereDate('created_at', today())),
                Tables\Filters\Filter::make('this_week')
                    ->label('Diese Woche')
                    ->query(fn (Builder $query): Builder => $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])),
                Tables\Filters\Filter::make('missed_calls')
                    ->label('Verpasste Anrufe')
                    ->query(fn (Builder $query): Builder => $query->where('status', 'missed')),
            ])
            ->actions([
                Tables\Actions\Action::make('viewTranscript')
                    ->label('Transcript')
                    ->icon('heroicon-o-document-text')
                    ->color('info')
                    ->visible(fn ($record) => !empty($record->transcript))
                    ->modalHeading(fn ($record) => 'Gesprächsverlauf - ' . $record->created_at->format('d.m.Y H:i'))
                    ->modalContent(fn ($record) => view('filament.modals.call-transcript', [
                        'call' => $record,
                        'transcript' => $record->transcript,
                        'transcript_object' => json_decode($record->raw ?? '{}', true)['transcript_object'] ?? null,
                    ]))
                    ->modalWidth('4xl')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Schließen'),

                Tables\Actions\ViewAction::make()
                    ->label('Anzeigen'),

                Tables\Actions\Action::make('playRecording')
                    ->label('Aufnahme abspielen')
                    ->icon('heroicon-o-play')
                    ->color('info')
                    ->visible(fn ($record) => !empty($record->recording_url))
                    ->url(fn ($record) => $record->recording_url)
                    ->openUrlInNewTab(),
            ])
            ->emptyStateHeading('Keine Anrufe vorhanden')
            ->emptyStateDescription('Noch keine Anrufe für diesen Kunden dokumentiert.')
            ->emptyStateIcon('heroicon-o-phone');
    }

    public function isReadOnly(): bool
    {
        return true;
    }
}
