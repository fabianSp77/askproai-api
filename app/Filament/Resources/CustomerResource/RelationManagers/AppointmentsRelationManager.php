<?php

namespace App\Filament\Resources\CustomerResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AppointmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'appointments';

    protected static ?string $title = 'Termine';

    protected static ?string $icon = 'heroicon-o-calendar-days';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Termindetails')
                    ->schema([
                        Forms\Components\Select::make('branch_id')
                            ->label('Filiale')
                            ->relationship('branch', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('staff_id')
                            ->label('Mitarbeiter')
                            ->relationship('staff', 'name')
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('service_id')
                            ->label('Dienstleistung')
                            ->relationship('service', 'name')
                            ->searchable()
                            ->preload(),
                        Forms\Components\DateTimePicker::make('starts_at')
                            ->label('Startzeit')
                            ->required()
                            ->native(false)
                            ->displayFormat('d.m.Y H:i')
                            ->minutesStep(15)
                            ->minDate(now())
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $set('ends_at', \Carbon\Carbon::parse($state)->addMinutes(30));
                                }
                            }),
                        Forms\Components\DateTimePicker::make('ends_at')
                            ->label('Endzeit')
                            ->required()
                            ->native(false)
                            ->displayFormat('d.m.Y H:i')
                            ->minutesStep(15)
                            ->after('starts_at'),
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
                            ->rows(3)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('price')
                            ->label('Preis')
                            ->numeric()
                            ->prefix('€')
                            ->default(0),
                        Forms\Components\DateTimePicker::make('reminder_24h_sent_at')
                            ->label('Erinnerung gesendet am')
                            ->disabled()
                            ->displayFormat('d.m.Y H:i'),
                    ])
                    ->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('starts_at')
            ->columns([
                Tables\Columns\TextColumn::make('starts_at')
                    ->label('Termin')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->icon('heroicon-m-calendar')
                    ->description(function ($record) {
                        if ($record->starts_at && $record->ends_at) {
                            $duration = \Carbon\Carbon::parse($record->starts_at)->diffInMinutes($record->ends_at);
                            return "{$duration} Min.";
                        }
                        return null;
                    }),
                Tables\Columns\TextColumn::make('branch.name')
                    ->label('Filiale')
                    ->searchable(),
                Tables\Columns\TextColumn::make('staff.name')
                    ->label('Mitarbeiter')
                    ->searchable(),
                Tables\Columns\TextColumn::make('service.name')
                    ->label('Dienstleistung')
                    ->searchable()
                    ->limit(20),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->colors([
                        'primary' => 'scheduled',
                        'success' => 'confirmed',
                        'info' => 'completed',
                        'warning' => 'cancelled',
                        'danger' => 'no_show',
                    ])
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'scheduled' => 'Geplant',
                        'confirmed' => 'Bestätigt',
                        'completed' => 'Abgeschlossen',
                        'cancelled' => 'Abgesagt',
                        'no_show' => 'Nicht erschienen',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('price')
                    ->label('Preis')
                    ->money('EUR')
                    ->alignEnd(),
                Tables\Columns\IconColumn::make('reminder_24h_sent_at')
                    ->label('Erinnerung')
                    ->boolean()
                    ->getStateUsing(fn ($record) => !is_null($record->reminder_24h_sent_at))
                    ->trueIcon('heroicon-o-bell')
                    ->falseIcon('heroicon-o-bell-slash'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt')
                    ->dateTime('d.m.Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('starts_at', 'desc')
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
                Tables\Filters\Filter::make('upcoming')
                    ->label('Anstehende Termine')
                    ->query(fn (Builder $query): Builder => $query->where('starts_at', '>=', now()))
                    ->default(),
                Tables\Filters\Filter::make('past')
                    ->label('Vergangene Termine')
                    ->query(fn (Builder $query): Builder => $query->where('starts_at', '<', now())),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Neuer Termin')
                    ->modalHeading('Neuen Termin anlegen')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['customer_id'] = $this->ownerRecord->id;
                        $data['company_id'] = $this->ownerRecord->company_id;
                        $data['source'] = 'admin';
                        $data['booking_type'] = 'single';
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Bearbeiten'),
                Tables\Actions\Action::make('confirm')
                    ->label('Bestätigen')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn ($record) => $record->status === 'scheduled')
                    ->action(fn ($record) => $record->update(['status' => 'confirmed']))
                    ->requiresConfirmation(),
                Tables\Actions\Action::make('cancel')
                    ->label('Absagen')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn ($record) => in_array($record->status, ['scheduled', 'confirmed']))
                    ->action(fn ($record) => $record->update(['status' => 'cancelled']))
                    ->requiresConfirmation(),
                Tables\Actions\DeleteAction::make()
                    ->label('Löschen'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('sendReminders')
                        ->label('Erinnerungen senden')
                        ->icon('heroicon-o-bell')
                        ->action(fn ($records) => $records->each->update(['reminder_24h_sent_at' => now()]))
                        ->deselectRecordsAfterCompletion()
                        ->requiresConfirmation(),
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Löschen'),
                ]),
            ])
            ->emptyStateHeading('Keine Termine vorhanden')
            ->emptyStateDescription('Erstellen Sie einen neuen Termin für diesen Kunden.')
            ->emptyStateIcon('heroicon-o-calendar');
    }
}