<?php

namespace App\Filament\Widgets;

use App\Models\Appointment;
use App\Models\AppointmentModificationStat;
use Filament\Widgets\ChartWidget;
use Carbon\Carbon;

class TimeBasedAnalyticsWidget extends ChartWidget
{
    protected static ?string $heading = 'Terminverteilung nach Wochentag';

    protected static ?int $sort = 7;

    protected static ?string $pollingInterval = '120s';

    protected int | string | array $columnSpan = 'full';

    public ?string $filter = 'weekday';

    /**
     * Widget disabled - stat_type column doesn't exist in appointment_modification_stats table (Sept 21 backup)
     * TODO: Re-enable when database is fully restored
     */
    public static function canView(): bool
    {
        return false;
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $companyId = auth()->user()->company_id;

        if ($this->filter === 'weekday') {
            return $this->getWeekdayData($companyId);
        } elseif ($this->filter === 'hour') {
            return $this->getHourlyData($companyId);
        }

        return $this->getWeekdayData($companyId);
    }

    protected function getWeekdayData(int $companyId): array
    {
        $weekdays = ['Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag', 'Sonntag'];
        $appointmentCounts = array_fill(0, 7, 0);
        $violationCounts = array_fill(0, 7, 0);

        // Get appointments by weekday
        $appointments = Appointment::where('company_id', $companyId)
            ->where('starts_at', '>=', now()->subDays(30))
            ->get()
            ->groupBy(function ($appointment) {
                return $appointment->starts_at->dayOfWeek;
            });

        foreach ($appointments as $dayOfWeek => $dayAppointments) {
            // Convert Sunday (0) to index 6, Monday (1) to 0, etc.
            $index = $dayOfWeek == 0 ? 6 : $dayOfWeek - 1;
            $appointmentCounts[$index] = $dayAppointments->count();
        }

        // Get violations by weekday
        $violations = AppointmentModificationStat::whereHas('customer', function ($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })
            ->where('stat_type', 'violation')
            ->where('created_at', '>=', now()->subDays(30))
            ->get()
            ->groupBy(function ($violation) {
                return $violation->created_at->dayOfWeek;
            });

        foreach ($violations as $dayOfWeek => $dayViolations) {
            $index = $dayOfWeek == 0 ? 6 : $dayOfWeek - 1;
            $violationCounts[$index] = $dayViolations->sum('count');
        }

        return [
            'datasets' => [
                [
                    'label' => 'Termine',
                    'data' => $appointmentCounts,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.8)',
                    'borderColor' => 'rgb(59, 130, 246)',
                    'borderWidth' => 2,
                ],
                [
                    'label' => 'Verstöße',
                    'data' => $violationCounts,
                    'backgroundColor' => 'rgba(239, 68, 68, 0.8)',
                    'borderColor' => 'rgb(239, 68, 68)',
                    'borderWidth' => 2,
                ],
            ],
            'labels' => $weekdays,
        ];
    }

    protected function getHourlyData(int $companyId): array
    {
        $hours = [];
        for ($i = 8; $i <= 20; $i++) {
            $hours[] = sprintf('%02d:00', $i);
        }

        $appointmentCounts = array_fill(0, count($hours), 0);

        // Get appointments by hour
        $appointments = Appointment::where('company_id', $companyId)
            ->where('starts_at', '>=', now()->subDays(30))
            ->get()
            ->groupBy(function ($appointment) {
                return $appointment->starts_at->hour;
            });

        foreach ($appointments as $hour => $hourAppointments) {
            if ($hour >= 8 && $hour <= 20) {
                $index = $hour - 8;
                $appointmentCounts[$index] = $hourAppointments->count();
            }
        }

        return [
            'datasets' => [
                [
                    'label' => 'Termine',
                    'data' => $appointmentCounts,
                    'backgroundColor' => 'rgba(16, 185, 129, 0.8)',
                    'borderColor' => 'rgb(16, 185, 129)',
                    'borderWidth' => 2,
                ],
            ],
            'labels' => $hours,
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
                'tooltip' => [
                    'enabled' => true,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'precision' => 0,
                    ],
                ],
            ],
        ];
    }

    protected function getFilters(): ?array
    {
        return [
            'weekday' => 'Nach Wochentag',
            'hour' => 'Nach Stunde (8-20 Uhr)',
        ];
    }

    public function getColumnSpan(): string | array | int
    {
        return 'full';
    }
}
