<?php

namespace App\Filament\Widgets;

use App\Models\Appointment;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class RecentAppointmentsWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    protected static ?string $heading = 'Heutige Termine';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Appointment::query()
                    ->with(['customer', 'staff', 'service', 'branch'])
                    ->whereDate('starts_at', today())
                    ->orderBy('starts_at', 'asc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('starts_at')
                    ->label('Zeit')
                    ->time('H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Kunde')
                    ->searchable()
                    ->url(fn ($record) => route('filament.admin.resources.customers.view', $record->customer)),

                Tables\Columns\TextColumn::make('staff.name')
                    ->label('Mitarbeiter')
                    ->searchable(),

                Tables\Columns\TextColumn::make('service.name')
                    ->label('Service')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('branch.name')
                    ->label('Filiale')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->colors([
                        'info' => 'scheduled',
                        'success' => 'confirmed',
                        'warning' => 'in_progress',
                        'success' => 'completed',
                        'danger' => ['cancelled', 'no_show'],
                    ])
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'scheduled' => 'Geplant',
                        'confirmed' => 'Bestätigt',
                        'in_progress' => 'Läuft',
                        'completed' => 'Abgeschlossen',
                        'cancelled' => 'Abgesagt',
                        'no_show' => 'Nicht erschienen',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('duration')
                    ->label('Dauer')
                    ->getStateUsing(fn ($record) =>
                        $record->starts_at && $record->ends_at
                            ? $record->starts_at->diffInMinutes($record->ends_at) . ' min'
                            : '-'
                    ),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'scheduled' => 'Geplant',
                        'confirmed' => 'Bestätigt',
                        'in_progress' => 'Läuft',
                        'completed' => 'Abgeschlossen',
                        'cancelled' => 'Abgesagt',
                        'no_show' => 'Nicht erschienen',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('start')
                    ->label('Starten')
                    ->icon('heroicon-m-play')
                    ->color('success')
                    ->visible(fn ($record) => in_array($record->status, ['scheduled', 'confirmed']))
                    ->action(fn ($record) => $record->update(['status' => 'in_progress'])),

                Tables\Actions\Action::make('complete')
                    ->label('Abschließen')
                    ->icon('heroicon-m-check')
                    ->color('info')
                    ->visible(fn ($record) => $record->status === 'in_progress')
                    ->action(fn ($record) => $record->update(['status' => 'completed'])),

                Tables\Actions\ViewAction::make()
                    ->url(fn ($record) => route('filament.admin.resources.appointments.view', $record)),
            ])
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5);
    }
}