<?php

namespace App\Filament\Widgets\Premium;

use App\Filament\Widgets\Premium\Concerns\HasPremiumStyling;
use App\Models\Appointment;
use Carbon\Carbon;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Log;

/**
 * Premium Calendar Widget
 *
 * Interactive mini calendar showing appointments for the current month.
 * Features month navigation and event indicators.
 */
class CalendarWidget extends Widget
{
    use InteractsWithPageFilters;
    use HasPremiumStyling;

    protected static string $view = 'filament.widgets.premium.calendar';
    protected static bool $isLazy = false;
    protected int|string|array $columnSpan = 1;

    public int $currentMonth;
    public int $currentYear;

    public function mount(): void
    {
        $this->currentMonth = now()->month;
        $this->currentYear = now()->year;
    }

    /**
     * Navigate to previous/next month.
     */
    public function navigateMonth(string $direction): void
    {
        $date = Carbon::createFromDate($this->currentYear, $this->currentMonth, 1);

        if ($direction === 'prev') {
            $date->subMonth();
        } else {
            $date->addMonth();
        }

        $this->currentMonth = $date->month;
        $this->currentYear = $date->year;
    }

    /**
     * Select a specific date and update dashboard filters.
     */
    public function selectDate(string $dateString): void
    {
        // Dispatch event to parent dashboard to update filters
        $this->dispatch('setDashboardFilters', filters: [
            'time_range' => 'custom',
            'date_from' => $dateString,
            'date_to' => $dateString,
        ]);
    }

    /**
     * Get calendar data for current month.
     */
    public function getCalendarData(): array
    {
        $firstDay = Carbon::createFromDate($this->currentYear, $this->currentMonth, 1);
        $lastDay = $firstDay->copy()->endOfMonth();
        $startOfCalendar = $firstDay->copy()->startOfWeek();
        $endOfCalendar = $lastDay->copy()->endOfWeek();

        $days = [];
        $current = $startOfCalendar->copy();

        // Get events for the month
        $events = $this->getEventsForMonth();

        while ($current <= $endOfCalendar) {
            $dateKey = $current->format('Y-m-d');
            $days[] = [
                'date' => $current->day,
                'fullDate' => $dateKey,
                'isToday' => $current->isToday(),
                'isCurrentMonth' => $current->month === $this->currentMonth,
                'events' => $events[$dateKey] ?? [],
            ];
            $current->addDay();
        }

        return [
            'monthName' => $firstDay->translatedFormat('F Y'),
            'weekdays' => ['Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So'],
            'days' => $days,
        ];
    }

    /**
     * Get events (appointments) for the current month.
     * Note: Caching disabled for reactive filter updates.
     */
    protected function getEventsForMonth(): array
    {
        try {
            $companyId = $this->getEffectiveCompanyId();
            $firstDay = Carbon::createFromDate($this->currentYear, $this->currentMonth, 1)->startOfDay();
            $lastDay = $firstDay->copy()->endOfMonth()->endOfDay();

            $query = Appointment::query()
                ->whereBetween('start_time', [$firstDay, $lastDay])
                ->whereNull('cancelled_at');

            if ($companyId) {
                $query->where('company_id', $companyId);
            }

            $appointments = $query->get();
            $events = [];

            foreach ($appointments as $appointment) {
                $dateKey = Carbon::parse($appointment->start_time)->format('Y-m-d');
                if (!isset($events[$dateKey])) {
                    $events[$dateKey] = [];
                }
                $events[$dateKey][] = [
                    'id' => $appointment->id,
                    'color' => $this->getEventColor($appointment),
                ];
            }

            return $events;
        } catch (\Throwable $e) {
            Log::error('[CalendarWidget] getEventsForMonth failed', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Handle filter updates - refresh the widget when filters change.
     */
    public function updatedFilters(): void
    {
        // Widget will automatically re-render with new data
    }

    /**
     * Get event color based on appointment status.
     */
    protected function getEventColor($appointment): string
    {
        // Color based on status or time
        if ($appointment->status === 'confirmed') {
            return '#22C55E'; // Green
        } elseif ($appointment->status === 'pending') {
            return '#F59E0B'; // Yellow
        }
        return '#3B82F6'; // Blue default
    }

    /**
     * Get KPI data for footer.
     */
    public function getKpiData(): array
    {
        try {
            $companyId = $this->getEffectiveCompanyId();
            $firstDay = Carbon::createFromDate($this->currentYear, $this->currentMonth, 1);
            $lastDay = $firstDay->copy()->endOfMonth();

            $query = Appointment::query()
                ->whereBetween('start_time', [$firstDay, $lastDay]);

            if ($companyId) {
                $query->where('company_id', $companyId);
            }

            $total = (clone $query)->count();
            $confirmed = (clone $query)->where('status', 'confirmed')->count();

            // Calculate growth vs previous month
            $prevFirstDay = $firstDay->copy()->subMonth();
            $prevLastDay = $prevFirstDay->copy()->endOfMonth();

            $prevQuery = Appointment::query()
                ->whereBetween('start_time', [$prevFirstDay, $prevLastDay]);

            if ($companyId) {
                $prevQuery->where('company_id', $companyId);
            }

            $prevTotal = $prevQuery->count();
            $growth = $prevTotal > 0 ? round((($total - $prevTotal) / $prevTotal) * 100, 1) : 0;

            return [
                'total' => $total,
                'confirmed' => $confirmed,
                'growth' => $growth,
            ];
        } catch (\Throwable $e) {
            return ['total' => 0, 'confirmed' => 0, 'growth' => 0];
        }
    }
}
