<?php

namespace App\Filament\Customer\Widgets;

use App\Models\Appointment;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\HtmlString;
use Carbon\Carbon;

class RecentAppointmentsWidget extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Anstehende Termine';

    protected static ?string $pollingInterval = '300s';

    protected static ?int $sort = 1;

    public function table(Table $table): Table
    {
        $companyId = auth()->user()->company_id;

        try {
            return $table
                ->query(
                    Appointment::query()
                        ->with(['customer', 'service', 'staff', 'branch'])
                        ->where('company_id', $companyId)
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
                        ->limit(5)
                )
                ->columns([
                    Tables\Columns\TextColumn::make('starts_at')
                        ->label('Datum')
                        ->formatStateUsing(function (Appointment $record) {
                            $starts = Carbon::parse($record->starts_at);
                            if ($starts->isToday()) {
                                return 'Heute ' . $starts->format('H:i');
                            } elseif ($starts->isTomorrow()) {
                                return 'Morgen ' . $starts->format('H:i');
                            } else {
                                return $starts->format('d.m.Y H:i');
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

                    Tables\Columns\TextColumn::make('service.name')
                        ->label('Service')
                        ->icon('heroicon-m-briefcase')
                        ->badge()
                        ->color('info')
                        ->limit(30)
                        ->tooltip(fn (Appointment $record) => $record->service?->name),

                    Tables\Columns\TextColumn::make('staff.name')
                        ->label('Mitarbeiter')
                        ->icon('heroicon-m-user-circle')
                        ->placeholder('Nicht zugewiesen')
                        ->color('gray')
                        ->limit(25),

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

                    Tables\Columns\TextColumn::make('branch.name')
                        ->label('Filiale')
                        ->icon('heroicon-m-building-office')
                        ->placeholder('Keine Filiale')
                        ->color('gray')
                        ->toggleable(),
                ])
                ->actions([
                    Tables\Actions\Action::make('view')
                        ->label('Details')
                        ->icon('heroicon-m-eye')
                        ->color('gray')
                        ->url(fn (Appointment $record): string =>
                            route('filament.customer.resources.appointments.view', ['record' => $record])
                        ),
                ])
                ->emptyState(
                    view('filament.widgets.empty-state', [
                        'icon' => 'heroicon-o-calendar',
                        'heading' => 'Keine anstehenden Termine',
                        'description' => 'Es sind aktuell keine Termine geplant.',
                    ])
                )
                ->bulkActions([])
                ->paginated(false)
                ->striped()
                ->poll('300s')
                ->defaultSort('starts_at', 'asc');
        } catch (\Exception $e) {
            \Log::error('RecentAppointmentsWidget Error: ' . $e->getMessage());
            return $table
                ->query(Appointment::query()->whereRaw('0=1')) // Empty query on error
                ->columns([]);
        }
    }

    protected function getTableHeading(): string|HtmlString|null
    {
        $companyId = auth()->user()->company_id;

        $todayCount = Appointment::where('company_id', $companyId)
            ->whereDate('starts_at', today())
            ->whereNotIn('status', ['cancelled', 'no-show'])
            ->count();

        $upcomingCount = Appointment::where('company_id', $companyId)
            ->where('starts_at', '>', now())
            ->whereNotIn('status', ['cancelled', 'no-show'])
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
