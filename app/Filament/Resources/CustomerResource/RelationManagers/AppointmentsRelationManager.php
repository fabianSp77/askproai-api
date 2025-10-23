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
                    ->description(fn ($record) => $record->duration_formatted),
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
                    ->mutateFormDataUsing(fn (array $data): array => $this->prepareAppointmentData($data)),
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
            ->emptyStateHeading(function () {
                $callCount = $this->ownerRecord->calls()->count();
                $failedBookings = $this->ownerRecord->calls()
                    ->where('appointment_made', 1)
                    ->whereNull('converted_appointment_id')
                    ->count();

                if ($failedBookings > 0) {
                    return "⚠️ {$failedBookings} fehlgeschlagene Buchung(en) gefunden!";
                }
                if ($callCount > 0) {
                    return "Noch keine Termine trotz {$callCount} Anruf" . ($callCount === 1 ? '' : 'en');
                }
                return 'Noch keine Termine vorhanden';
            })
            ->emptyStateDescription(function () {
                $callCount = $this->ownerRecord->calls()->count();
                $failedBookings = $this->ownerRecord->calls()
                    ->where('appointment_made', 1)
                    ->whereNull('converted_appointment_id')
                    ->count();

                if ($failedBookings > 0) {
                    return 'Der AI-Agent versuchte Termine zu buchen, aber die Buchungen schlugen fehl. Bitte manuell nachbuchen.';
                }
                if ($callCount > 0) {
                    return 'Dieser Kunde hat bereits Anrufe, aber noch keine Termine. Jetzt ersten Termin buchen!';
                }
                return 'Erstellen Sie den ersten Termin für diesen Kunden.';
            })
            ->emptyStateIcon(function () {
                $failedBookings = $this->ownerRecord->calls()
                    ->where('appointment_made', 1)
                    ->whereNull('converted_appointment_id')
                    ->count();

                return $failedBookings > 0 ? 'heroicon-o-exclamation-triangle' : 'heroicon-o-calendar';
            })
            ->emptyStateActions([
                Tables\Actions\Action::make('create')
                    ->label('Ersten Termin buchen')
                    ->icon('heroicon-o-calendar-days')
                    ->color('primary')
                    ->url(fn () => route('filament.admin.resources.appointments.create', [
                        'customer_id' => $this->ownerRecord->id
                    ])),
                Tables\Actions\Action::make('viewFailedCalls')
                    ->label('Fehlgeschlagene Anrufe anzeigen')
                    ->icon('heroicon-o-phone-x-mark')
                    ->color('warning')
                    ->visible(fn () => $this->ownerRecord->calls()
                        ->where('appointment_made', 1)
                        ->whereNull('converted_appointment_id')
                        ->count() > 0)
                    ->action(fn () => $this->scrollToCallsRelation()),
            ]);
    }

    /**
     * Scroll to calls relation manager
     * Extracted from closure for Livewire serialization
     */
    public function scrollToCallsRelation(): void
    {
        $this->dispatch('scrollToRelation', relation: 'calls');
    }

    /**
     * Prepare appointment data before creation
     * Extracted from mutateFormDataUsing closure for Livewire serialization
     */
    protected function prepareAppointmentData(array $data): array
    {
        $data['customer_id'] = $this->ownerRecord->id;
        $data['company_id'] = $this->ownerRecord->company_id;
        $data['source'] = 'admin';
        $data['booking_type'] = 'single';
        return $data;
    }
}