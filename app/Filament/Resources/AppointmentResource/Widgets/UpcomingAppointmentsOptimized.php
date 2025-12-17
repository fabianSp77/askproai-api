<?php

namespace App\Filament\Resources\AppointmentResource\Widgets;

use App\Models\Appointment;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class UpcomingAppointmentsOptimized extends BaseWidget
{
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 'full';

    /**
     * Configure the table with optimizations
     */
    public function table(Table $table): Table
    {
        return $table
            ->query($this->getOptimizedQuery())
            ->columns($this->getOptimizedColumns())
            ->actions($this->getOptimizedActions())
            ->paginated([5, 10])
            ->poll('30s')
            ->heading('Kommende Termine (48 Stunden)')
            ->description('Nächste Termine in den kommenden 2 Tagen')
            ->striped()
            ->defaultSort('starts_at', 'asc');
    }

    /**
     * Get optimized query with proper indexing
     */
    protected function getOptimizedQuery(): Builder
    {
        $companyId = auth()->user()->company_id ?? 1;

        // Use the upcoming index for optimal performance
        return Appointment::query()
            ->from('appointments USE INDEX (idx_appt_upcoming)')
            ->with([
                'customer:id,name,phone',
                'service:id,name,duration_minutes',
                'staff:id,name',
                'branch:id,name'
            ])
            ->where('company_id', $companyId)
            ->whereBetween('starts_at', [
                now(),
                now()->addHours(48)
            ])
            ->whereIn('status', ['pending', 'confirmed', 'accepted', 'scheduled'])
            ->orderBy('starts_at', 'asc')
            ->limit(20);  // Hard limit for performance
    }

    /**
     * Get optimized column definitions
     */
    protected function getOptimizedColumns(): array
    {
        return [
            // Time column with smart formatting
            Tables\Columns\TextColumn::make('starts_at')
                ->label('Zeit')
                ->dateTime('H:i')
                ->date('d.m.')
                ->description(function ($record) {
                    // Cache the Carbon instance
                    static $now;
                    $now ??= now();

                    $starts = Carbon::parse($record->starts_at);
                    $diff = $starts->diffForHumans($now, [
                        'short' => true,
                        'parts' => 1
                    ]);

                    return $diff;
                })
                ->sortable()
                ->icon('heroicon-m-clock')
                ->iconColor(fn ($record) =>
                    Carbon::parse($record->starts_at)->isToday() ? 'warning' : 'primary'
                ),

            // Customer with cached phone
            Tables\Columns\TextColumn::make('customer.name')
                ->label('Kunde')
                ->searchable(['customers.name', 'customers.phone'])
                ->icon('heroicon-m-user')
                ->description(fn ($record) => $record->customer?->phone)
                ->url(fn ($record) => $record->customer_id
                    ? route('filament.admin.resources.customers.view', $record->customer_id)
                    : null
                ),

            // Service badge
            Tables\Columns\TextColumn::make('service.name')
                ->label('Service')
                ->badge()
                ->color('info')
                ->limit(20),

            // Staff assignment
            Tables\Columns\TextColumn::make('staff.name')
                ->label('Mitarbeiter')
                ->badge()
                ->color('gray')
                ->default('—')
                ->toggleable(),

            // Status with optimized formatting
            Tables\Columns\BadgeColumn::make('status')
                ->label('Status')
                ->colors([
                    'warning' => 'pending',
                    'success' => 'confirmed',
                    'info' => 'accepted',
                    'primary' => 'scheduled',
                ])
                ->formatStateUsing(fn (string $state): string =>
                    $this->getCachedStatusLabel($state)
                ),

            // Duration calculation
            Tables\Columns\TextColumn::make('duration')
                ->label('Dauer')
                ->getStateUsing(function ($record) {
                    if ($record->starts_at && $record->ends_at) {
                        return Carbon::parse($record->starts_at)
                            ->diffInMinutes($record->ends_at) . ' Min';
                    }
                    return $record->service?->duration_minutes
                        ? $record->service->duration_minutes . ' Min'
                        : '—';
                })
                ->badge()
                ->color('gray')
                ->toggleable(),
        ];
    }

    /**
     * Get optimized actions
     */
    protected function getOptimizedActions(): array
    {
        return [
            Tables\Actions\Action::make('confirm')
                ->label('Bestätigen')
                ->icon('heroicon-m-check')
                ->color('success')
                ->visible(fn ($record) => $record->status === 'pending')
                ->action(function ($record) {
                    $record->update(['status' => 'confirmed']);

                    // Clear relevant caches
                    $companyId = $record->company_id;
                    Cache::tags(['appointments', "company-{$companyId}"])->flush();

                    $this->dispatch('$refresh');
                }),

            Tables\Actions\Action::make('view')
                ->label('Anzeigen')
                ->icon('heroicon-m-eye')
                ->url(fn ($record) =>
                    route('filament.admin.resources.appointments.view', $record)
                ),
        ];
    }

    /**
     * Cache status labels for performance
     */
    protected function getCachedStatusLabel(string $state): string
    {
        static $labels;

        $labels ??= [
            'pending' => 'Ausstehend',
            'confirmed' => 'Bestätigt',
            'accepted' => 'Akzeptiert',
            'scheduled' => 'Geplant',
            'completed' => 'Abgeschlossen',
            'cancelled' => 'Storniert',
            'no_show' => 'Nicht erschienen',
        ];

        return $labels[$state] ?? $state;
    }

    /**
     * Override to use cursor pagination for large datasets
     */
    protected function paginateTableQuery(Builder $query): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $perPage = $this->getTableRecordsPerPage();

        // Use simple pagination for better performance
        if ($query->count() > 1000) {
            return $query->simplePaginate($perPage);
        }

        return $query->paginate($perPage);
    }

    /**
     * Cache the query results for widget
     */
    public function getCachedTableRecords(): \Illuminate\Support\Collection
    {
        $companyId = auth()->user()->company_id ?? 1;
        $cacheKey = "upcoming-appts:{$companyId}:" . now()->format('Y-m-d-H-i');

        return Cache::tags(['appointments', "company-{$companyId}"])
            ->remember($cacheKey, 60, function() {
                return $this->getTableQuery()->get();
            });
    }
}