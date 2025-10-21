<?php

namespace App\Filament\Resources\CustomerResource\RelationManagers;

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

    protected static ?string $icon = 'heroicon-o-phone';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Anrufdetails')
                    ->schema([
                        Forms\Components\DateTimePicker::make('created_at')
                            ->label('Anrufzeit')
                            ->required()
                            ->default(now())
                            ->displayFormat('d.m.Y H:i:s'),
                        Forms\Components\Select::make('direction')
                            ->label('Richtung')
                            ->options([
                                'inbound' => 'Eingehend',
                                'outbound' => 'Ausgehend',
                            ])
                            ->required()
                            ->default('inbound'),
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'answered' => 'Beantwortet',
                                'missed' => 'Verpasst',
                                'busy' => 'Besetzt',
                                'failed' => 'Fehlgeschlagen',
                                'voicemail' => 'Voicemail',
                            ])
                            ->required()
                            ->default('answered'),
                        Forms\Components\TextInput::make('duration_sec')
                            ->label('Dauer (Sek.)')
                            ->numeric()
                            ->suffix('Sekunden')
                            ->default(0),
                        Forms\Components\TextInput::make('from_number')
                            ->label('Von Nummer')
                            ->tel()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('to_number')
                            ->label('An Nummer')
                            ->tel()
                            ->maxLength(255),
                        Forms\Components\Select::make('staff_id')
                            ->label('Bearbeitet von')
                            ->relationship('staff', 'name')
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('session_outcome')
                            ->label('Ergebnis')
                            ->options([
                                'appointment_scheduled' => 'Termin vereinbart',
                                'appointment_cancelled' => 'Termin abgesagt',
                                'callback_requested' => 'Rückruf erwünscht',
                                'info_provided' => 'Info gegeben',
                                'transferred' => 'Weitergeleitet',
                                'no_action' => 'Keine Aktion',
                            ]),
                        Forms\Components\Toggle::make('appointment_made')
                            ->label('Termin vereinbart')
                            ->helperText('Wurde ein Termin während des Anrufs vereinbart?'),
                        Forms\Components\Textarea::make('notes')
                            ->label('Notizen')
                            ->rows(3)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('recording_url')
                            ->label('Aufnahme URL')
                            ->url()
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

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
                    ->description(function ($record) {
                        if ($record->duration_sec) {
                            $minutes = floor($record->duration_sec / 60);
                            $seconds = $record->duration_sec % 60;
                            return $minutes > 0 ? "{$minutes}m {$seconds}s" : "{$seconds}s";
                        }
                        return null;
                    }),
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
                    ->getStateUsing(fn ($record) => !empty($record->recording_url))
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
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Anruf dokumentieren')
                    ->modalHeading('Anruf dokumentieren')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['customer_id'] = $this->ownerRecord->id;
                        $data['company_id'] = $this->ownerRecord->company_id;
                        $data['type'] = 'manual';
                        $data['successful'] = 1;
                        $data['retell_call_id'] = 'manual_' . uniqid();
                        $data['call_id'] = 'manual_' . uniqid();
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Bearbeiten'),
                Tables\Actions\Action::make('playRecording')
                    ->label('Aufnahme abspielen')
                    ->icon('heroicon-o-play')
                    ->color('info')
                    ->visible(fn ($record) => !empty($record->recording_url))
                    ->url(fn ($record) => $record->recording_url)
                    ->openUrlInNewTab(),
                Tables\Actions\DeleteAction::make()
                    ->label('Löschen'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Löschen'),
                ]),
            ])
            ->emptyStateHeading('Keine Anrufe vorhanden')
            ->emptyStateDescription('Dokumentieren Sie Anrufe für diesen Kunden.')
            ->emptyStateIcon('heroicon-o-phone');
    }

    public function isReadOnly(): bool
    {
        return false;
    }
}