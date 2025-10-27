<?php

namespace App\Filament\Resources\AppointmentModificationResource\Widgets;

use App\Models\AppointmentModification;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class ModificationStatsWidget extends BaseWidget
{
    /**
     * Widget disabled - appointment_modifications table doesn't exist in Sept 21 database backup
     * TODO: Re-enable when database is fully restored
     */
    public static function canView(): bool
    {
        return false;
    }

    protected function getStats(): array
    {
        $thirtyDaysAgo = now()->subDays(30);
        $sevenDaysAgo = now()->subDays(7);

        // Total modifications in last 30 days
        $totalModifications = AppointmentModification::where('created_at', '>=', $thirtyDaysAgo)->count();
        $totalModificationsLastWeek = AppointmentModification::where('created_at', '>=', $sevenDaysAgo)->count();

        // Cancellations in last 30 days
        $totalCancellations = AppointmentModification::where('created_at', '>=', $thirtyDaysAgo)
            ->where('modification_type', AppointmentModification::TYPE_CANCEL)
            ->count();
        $cancellationsLastWeek = AppointmentModification::where('created_at', '>=', $sevenDaysAgo)
            ->where('modification_type', AppointmentModification::TYPE_CANCEL)
            ->count();
        $cancellationTrend = $cancellationsLastWeek > 0
            ? (($cancellationsLastWeek / 7) - ($totalCancellations / 30)) * 100
            : 0;

        // Reschedules in last 30 days
        $totalReschedules = AppointmentModification::where('created_at', '>=', $thirtyDaysAgo)
            ->where('modification_type', AppointmentModification::TYPE_RESCHEDULE)
            ->count();
        $reschedulesLastWeek = AppointmentModification::where('created_at', '>=', $sevenDaysAgo)
            ->where('modification_type', AppointmentModification::TYPE_RESCHEDULE)
            ->count();
        $rescheduleTrend = $reschedulesLastWeek > 0
            ? (($reschedulesLastWeek / 7) - ($totalReschedules / 30)) * 100
            : 0;

        // Policy compliance rate
        $withinPolicyCount = AppointmentModification::where('created_at', '>=', $thirtyDaysAgo)
            ->where('within_policy', true)
            ->count();
        $complianceRate = $totalModifications > 0
            ? round(($withinPolicyCount / $totalModifications) * 100, 1)
            : 0;

        // Total fees charged
        $totalFees = AppointmentModification::where('created_at', '>=', $thirtyDaysAgo)
            ->sum('fee_charged');
        $feesLastWeek = AppointmentModification::where('created_at', '>=', $sevenDaysAgo)
            ->sum('fee_charged');

        // Top customer by modifications
        $topCustomer = AppointmentModification::where('created_at', '>=', $thirtyDaysAgo)
            ->select('customer_id', DB::raw('count(*) as modifications_count'))
            ->groupBy('customer_id')
            ->orderByDesc('modifications_count')
            ->with('customer')
            ->first();

        return [
            Stat::make('Stornierungen (30 Tage)', $totalCancellations)
                ->description($cancellationTrend > 0 ? 'Anstieg' : 'Rückgang')
                ->descriptionIcon($cancellationTrend > 0 ? 'heroicon-o-arrow-trending-up' : 'heroicon-o-arrow-trending-down')
                ->color($cancellationTrend > 0 ? 'danger' : 'success')
                ->chart(self::getModificationChart(AppointmentModification::TYPE_CANCEL, 30)),

            Stat::make('Umplanungen (30 Tage)', $totalReschedules)
                ->description($rescheduleTrend > 0 ? 'Anstieg' : 'Rückgang')
                ->descriptionIcon($rescheduleTrend > 0 ? 'heroicon-o-arrow-trending-up' : 'heroicon-o-arrow-trending-down')
                ->color($rescheduleTrend > 0 ? 'warning' : 'success')
                ->chart(self::getModificationChart(AppointmentModification::TYPE_RESCHEDULE, 30)),

            Stat::make('Richtlinienkonformität', "{$complianceRate}%")
                ->description("{$withinPolicyCount} von {$totalModifications} innerhalb der Richtlinien")
                ->descriptionIcon('heroicon-o-check-circle')
                ->color($complianceRate >= 70 ? 'success' : ($complianceRate >= 50 ? 'warning' : 'danger')),

            Stat::make('Berechnete Gebühren', '€' . number_format($totalFees, 2))
                ->description("€" . number_format($feesLastWeek, 2) . " in den letzten 7 Tagen")
                ->descriptionIcon('heroicon-o-currency-euro')
                ->color($totalFees > 0 ? 'warning' : 'gray'),

            Stat::make('Häufigster Kunde', $topCustomer?->customer?->name ?? 'Keine Daten')
                ->description($topCustomer ? "{$topCustomer->modifications_count} Änderungen" : '')
                ->descriptionIcon('heroicon-o-user')
                ->color('info'),

            Stat::make('Gesamtänderungen', $totalModifications)
                ->description("{$totalModificationsLastWeek} in den letzten 7 Tagen")
                ->descriptionIcon('heroicon-o-chart-bar')
                ->color('primary')
                ->chart(self::getTotalModificationChart(30)),
        ];
    }

    /**
     * Get modification chart data for a specific type
     */
    protected static function getModificationChart(string $type, int $days): array
    {
        $data = [];
        $startDate = now()->subDays($days);

        for ($i = 0; $i < $days; $i++) {
            $date = $startDate->copy()->addDays($i);
            $count = AppointmentModification::whereDate('created_at', $date)
                ->where('modification_type', $type)
                ->count();
            $data[] = $count;
        }

        return $data;
    }

    /**
     * Get total modification chart data
     */
    protected static function getTotalModificationChart(int $days): array
    {
        $data = [];
        $startDate = now()->subDays($days);

        for ($i = 0; $i < $days; $i++) {
            $date = $startDate->copy()->addDays($i);
            $count = AppointmentModification::whereDate('created_at', $date)->count();
            $data[] = $count;
        }

        return $data;
    }
}
