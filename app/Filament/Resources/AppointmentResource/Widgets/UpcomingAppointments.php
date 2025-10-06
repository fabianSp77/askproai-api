<?php

namespace App\Filament\Resources\AppointmentResource\Widgets;

use App\Models\Appointment;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class UpcomingAppointments extends BaseWidget
{
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Appointment::query()
                    ->with(['customer', 'service', 'staff', 'branch'])
                    ->where('starts_at', '>=', now())
                    ->where('starts_at', '<=', now()->addHours(48))
                    ->whereIn('status', ['pending', 'confirmed', 'accepted', 'scheduled'])
                    ->orderBy('starts_at', 'asc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('starts_at')
                    ->label('Zeit')
                    ->dateTime('H:i')
                    ->date('d.m.')
                    ->description(fn ($record) =>
                        Carbon::parse($record->starts_at)->diffForHumans()
                    )
                    ->sortable()
                    ->icon('heroicon-m-clock')
                    ->iconColor(fn ($record) =>
                        Carbon::parse($record->starts_at)->isToday() ? 'warning' : 'primary'
                    ),

                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Kunde')
                    ->searchable()
                    ->icon('heroicon-m-user')
                    ->description(fn ($record) => $record->customer?->phone),

                Tables\Columns\TextColumn::make('service.name')
                    ->label('Service')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('staff.name')
                    ->label('Mitarbeiter')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'confirmed',
                        'info' => 'accepted',
                        'primary' => 'scheduled',
                    ])
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'pending' => 'Ausstehend',
                        'confirmed' => 'Bestätigt',
                        'accepted' => 'Akzeptiert',
                        'scheduled' => 'Geplant',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('duration')
                    ->label('Dauer')
                    ->getStateUsing(fn ($record) =>
                        $record->starts_at && $record->ends_at
                            ? Carbon::parse($record->starts_at)->diffInMinutes($record->ends_at) . ' Min'
                            : '-'
                    )
                    ->badge()
                    ->color('gray'),
            ])
            ->actions([
                Tables\Actions\Action::make('confirm')
                    ->label('Bestätigen')
                    ->icon('heroicon-m-check')
                    ->color('success')
                    ->visible(fn ($record) => $record->status === 'pending')
                    ->action(fn ($record) => $record->update(['status' => 'confirmed'])),

                Tables\Actions\Action::make('view')
                    ->label('Anzeigen')
                    ->icon('heroicon-m-eye')
                    ->url(fn ($record) => route('filament.admin.resources.appointments.view', $record)),
            ])
            ->paginated([5, 10, 15])
            ->poll('30s')
            ->heading('Kommende Termine (48 Stunden)')
            ->description('Nächste Termine in den kommenden 2 Tagen');
    }

    protected function getTableQuery(): Builder
    {
        return Appointment::query()
            ->with(['customer', 'service', 'staff', 'branch'])
            ->where('starts_at', '>=', now())
            ->where('starts_at', '<=', now()->addHours(48))
            ->whereIn('status', ['pending', 'confirmed', 'accepted', 'scheduled']);
    }
}