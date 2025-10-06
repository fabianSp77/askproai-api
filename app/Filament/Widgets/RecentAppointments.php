<?php

namespace App\Filament\Widgets;

use App\Models\Appointment;
use App\Filament\Resources\AppointmentResource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\HtmlString;
use Carbon\Carbon;

class RecentAppointments extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Anstehende Termine';

    protected static ?string $pollingInterval = '300s';

    protected static ?int $sort = 6;

    public function table(Table $table): Table
    {
        try {
        return $table
            ->query(
                Appointment::query()
                    ->with(['customer', 'service', 'staff'])
                    ->where(function($query) {
                        // Nur zukünftige Termine ODER heute noch nicht abgeschlossene
                        $query->where('starts_at', '>=', now())
                              ->orWhere(function($q) {
                                  $q->whereDate('starts_at', today())
                                    ->whereNotIn('status', ['completed', 'cancelled', 'no-show'])
                                    ->where('starts_at', '<', now());
                              });
                    })
                    ->orderByRaw("
                        CASE
                            WHEN starts_at BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 30 MINUTE) THEN 1
                            WHEN starts_at BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 2 HOUR) THEN 2
                            WHEN DATE(starts_at) = CURDATE() THEN 3
                            WHEN starts_at > NOW() THEN 4
                            ELSE 5
                        END,
                        starts_at ASC
                    ")
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('starts_at')
                    ->label('Zeit')
                    ->formatStateUsing(function (Appointment $record) {
                        $starts = Carbon::parse($record->starts_at);
                        if ($starts->isToday()) {
                            return 'Heute ' . $starts->format('H:i');
                        } elseif ($starts->isTomorrow()) {
                            return 'Morgen ' . $starts->format('H:i');
                        } else {
                            return $starts->format('d.m. H:i');
                        }
                    })
                    ->description(fn (Appointment $record) =>
                        $record->ends_at ? 'bis ' . Carbon::parse($record->ends_at)->format('H:i') : null
                    )
                    ->icon('heroicon-m-calendar')
                    ->color(fn (Appointment $record) =>
                        Carbon::parse($record->starts_at)->isPast() ? 'gray' :
                        (Carbon::parse($record->starts_at)->diffInMinutes(now()) < 30 ? 'warning' : 'success')
                    )
                    ->weight('medium')
                    ->sortable(),

                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Kunde')
                    ->icon('heroicon-m-user')
                    ->searchable()
                    ->limit(25)
                    ->tooltip(fn (Appointment $record) => $record->customer?->name)
                    ->description(fn (Appointment $record) =>
                        $record->customer?->phone ?: $record->customer?->email
                    )
                    ->url(fn (Appointment $record) => $record->customer_id
                        ? route('filament.admin.resources.customers.view', $record->customer_id)
                        : null)
                    ->color('primary'),

                Tables\Columns\TextColumn::make('service.name')
                    ->label('Service')
                    ->icon('heroicon-m-briefcase')
                    ->badge()
                    ->color('info')
                    ->limit(20)
                    ->tooltip(fn (Appointment $record) => $record->service?->name),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match($state) {
                        'scheduled' => 'Geplant',
                        'confirmed' => 'Bestätigt',
                        'completed' => 'Abgeschlossen',
                        'cancelled' => 'Storniert',
                        'no-show' => 'Nicht erschienen',
                        'rescheduled' => 'Verschoben',
                        default => ucfirst($state ?? 'Unbekannt')
                    })
                    ->color(fn ($state) => match($state) {
                        'scheduled' => 'warning',
                        'confirmed' => 'success',
                        'completed' => 'gray',
                        'cancelled' => 'danger',
                        'no-show' => 'danger',
                        'rescheduled' => 'info',
                        default => 'gray'
                    })
                    ->icon(fn ($state) => match($state) {
                        'scheduled' => 'heroicon-o-clock',
                        'confirmed' => 'heroicon-o-check-circle',
                        'completed' => 'heroicon-o-check-badge',
                        'cancelled' => 'heroicon-o-x-circle',
                        'no-show' => 'heroicon-o-user-minus',
                        'rescheduled' => 'heroicon-o-arrow-path',
                        default => 'heroicon-o-question-mark-circle'
                    }),

                Tables\Columns\TextColumn::make('staff.name')
                    ->label('Mitarbeiter')
                    ->icon('heroicon-m-user-circle')
                    ->placeholder('Nicht zugewiesen')
                    ->color('gray')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('reminder_sent')
                    ->label('Erinnerung')
                    ->formatStateUsing(fn ($state) => $state ? '✅' : '⏳')
                    ->alignment('center')
                    ->tooltip(fn (Appointment $record) =>
                        $record->reminder_sent
                            ? 'Erinnerung gesendet: ' . Carbon::parse($record->reminder_sent_at)->format('d.m. H:i')
                            : 'Noch keine Erinnerung'
                    ),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('Details')
                    ->icon('heroicon-m-eye')
                    ->color('gray')
                    ->url(fn (Appointment $record): string =>
                        AppointmentResource::getUrl('view', ['record' => $record])
                    ),

                Tables\Actions\Action::make('confirm')
                    ->label('Bestätigen')
                    ->icon('heroicon-m-check')
                    ->color('success')
                    ->visible(fn (Appointment $record) => $record->status === 'scheduled')
                    ->requiresConfirmation()
                    ->action(fn (Appointment $record) => $record->update(['status' => 'confirmed'])),

                Tables\Actions\Action::make('cancel')
                    ->label('Stornieren')
                    ->icon('heroicon-m-x-mark')
                    ->color('danger')
                    ->visible(fn (Appointment $record) =>
                        in_array($record->status, ['scheduled', 'confirmed']) &&
                        Carbon::parse($record->starts_at)->isFuture()
                    )
                    ->requiresConfirmation()
                    ->action(fn (Appointment $record) => $record->update(['status' => 'cancelled'])),
            ])
            ->bulkActions([])
            ->paginated(false)
            ->striped()
            ->poll('300s')
            ->defaultSort('starts_at', 'asc');
        } catch (\Exception $e) {
            \Log::error('RecentAppointments Widget Error: ' . $e->getMessage());
            return $table
                ->query(Appointment::query()->whereRaw('0=1')) // Empty query on error
                ->columns([]);
        }
    }

    protected function getTableHeading(): string|HtmlString|null
    {
        $todayCount = Appointment::whereDate('starts_at', today())->count();
        $upcomingCount = Appointment::where('starts_at', '>', now())
            ->where('status', 'scheduled')
            ->count();

        return new HtmlString("
            <div class='flex items-center justify-between'>
                <span class='text-lg font-semibold'>Anstehende Termine</span>
                <div class='flex gap-4 text-sm text-gray-500'>
                    <span>Heute: {$todayCount}</span>
                    <span>Anstehend: {$upcomingCount}</span>
                </div>
            </div>
        ");
    }
}
