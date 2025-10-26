<?php

namespace App\Filament\Customer\Resources\CustomerResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AppointmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'appointments';

    protected static ?string $title = 'Termine';

    protected static ?string $icon = 'heroicon-o-calendar-days';

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
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Anzeigen'),
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
                    return 'Der AI-Agent versuchte Termine zu buchen, aber die Buchungen schlugen fehl.';
                }
                if ($callCount > 0) {
                    return 'Dieser Kunde hat bereits Anrufe, aber noch keine Termine.';
                }
                return 'Noch keine Termine vorhanden.';
            })
            ->emptyStateIcon(function () {
                $failedBookings = $this->ownerRecord->calls()
                    ->where('appointment_made', 1)
                    ->whereNull('converted_appointment_id')
                    ->count();

                return $failedBookings > 0 ? 'heroicon-o-exclamation-triangle' : 'heroicon-o-calendar';
            });
    }

    public function isReadOnly(): bool
    {
        return true;
    }
}
