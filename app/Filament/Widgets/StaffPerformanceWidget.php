<?php

namespace App\Filament\Widgets;

use App\Models\Staff;
use App\Models\Appointment;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class StaffPerformanceWidget extends BaseWidget
{
    protected static ?int $sort = 6;

    protected static ?string $pollingInterval = '60s';

    protected function getStats(): array
    {
        $companyId = auth()->user()->company_id;

        // Get staff with appointment counts
        $staffMetrics = Staff::where('company_id', $companyId)
            ->where('is_active', true)
            ->withCount([
                'appointments as total_appointments',
                'appointments as completed_appointments' => function ($query) {
                    $query->where('status', 'completed');
                },
                'appointments as cancelled_appointments' => function ($query) {
                    $query->where('status', 'cancelled');
                },
            ])
            ->get();

        // Calculate averages
        $avgAppointments = $staffMetrics->avg('total_appointments') ?? 0;
        $avgCompleted = $staffMetrics->avg('completed_appointments') ?? 0;
        $avgCancelled = $staffMetrics->avg('cancelled_appointments') ?? 0;

        // Get top performer (most completed appointments)
        $topPerformer = $staffMetrics->sortByDesc('completed_appointments')->first();
        $topPerformerName = $topPerformer ? $topPerformer->name : 'N/A';
        $topPerformerCount = $topPerformer ? $topPerformer->completed_appointments : 0;

        // Get staff with lowest cancellation rate
        $lowestCancellation = $staffMetrics
            ->filter(fn($s) => $s->total_appointments > 0)
            ->map(function($staff) {
                $staff->cancellation_rate = $staff->total_appointments > 0
                    ? ($staff->cancelled_appointments / $staff->total_appointments) * 100
                    : 0;
                return $staff;
            })
            ->sortBy('cancellation_rate')
            ->first();

        $bestComplianceStaff = $lowestCancellation ? $lowestCancellation->name : 'N/A';
        $bestComplianceRate = $lowestCancellation ? round($lowestCancellation->cancellation_rate, 1) : 0;

        // Get total active staff
        $activeStaff = $staffMetrics->count();

        // Get staff utilization rate
        $totalPossibleAppointments = $activeStaff * 40; // Assuming 40 appointments per staff per period
        $utilizationRate = $totalPossibleAppointments > 0
            ? round(($staffMetrics->sum('total_appointments') / $totalPossibleAppointments) * 100, 1)
            : 0;

        return [
            Stat::make('Aktive Mitarbeiter', $activeStaff)
                ->description('Derzeit aktive Mitarbeiter')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('info')
                ->chart($this->getActiveStaffChart($companyId)),

            Stat::make('Ø Termine pro Mitarbeiter', round($avgAppointments, 1))
                ->description('Durchschnittliche Termineanzahl')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('primary'),

            Stat::make('Top Performer', $topPerformerName)
                ->description("{$topPerformerCount} abgeschlossene Termine")
                ->descriptionIcon('heroicon-m-trophy')
                ->color('success'),

            Stat::make('Beste Compliance', $bestComplianceStaff)
                ->description("{$bestComplianceRate}% Stornierungsrate")
                ->descriptionIcon('heroicon-m-star')
                ->color('success'),

            Stat::make('Auslastungsrate', "{$utilizationRate}%")
                ->description('Mitarbeiterauslastung')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color($utilizationRate >= 80 ? 'success' : ($utilizationRate >= 60 ? 'warning' : 'danger')),

            Stat::make('Ø Abschlussrate', round(($avgCompleted / max($avgAppointments, 1)) * 100, 1) . '%')
                ->description('Durchschnittliche Abschlussquote')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('primary'),
        ];
    }

    /**
     * Get chart data for active staff over time
     */
    protected function getActiveStaffChart(int $companyId): array
    {
        $data = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->startOfDay();
            $count = Staff::where('company_id', $companyId)
                ->where('is_active', true)
                ->where('created_at', '<=', $date->endOfDay())
                ->count();

            $data[] = $count;
        }

        return $data;
    }

    public function getColumnSpan(): string | array | int
    {
        return 'full';
    }
}
