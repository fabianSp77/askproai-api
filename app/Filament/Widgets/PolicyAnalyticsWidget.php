<?php

namespace App\Filament\Widgets;

use App\Models\PolicyConfiguration;
use App\Models\AppointmentModificationStat;
use App\Models\Appointment;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PolicyAnalyticsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        // Get company_id from authenticated user
        $companyId = auth()->user()->company_id;

        // 1. Total Active Policies (soft-deleted ones are automatically excluded)
        $activePolicies = PolicyConfiguration::where('company_id', $companyId)
            ->count();

        // 2. Total Policy Configurations
        $totalConfigurations = PolicyConfiguration::where('company_id', $companyId)
            ->count();

        // 3. Total Policy Violations (last 30 days)
        $violations30Days = AppointmentModificationStat::whereHas('customer', function ($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })
            ->where('stat_type', 'violation')
            ->where('created_at', '>=', now()->subDays(30))
            ->sum('count');

        // 4. Compliance Rate (percentage of appointments without violations)
        $totalAppointments30Days = Appointment::where('company_id', $companyId)
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        $complianceRate = $totalAppointments30Days > 0
            ? round((($totalAppointments30Days - $violations30Days) / $totalAppointments30Days) * 100, 1)
            : 100;

        // 5. Policy effectiveness trend (last 7 days vs previous 7 days)
        $violations7Days = AppointmentModificationStat::whereHas('customer', function ($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })
            ->where('stat_type', 'violation')
            ->where('created_at', '>=', now()->subDays(7))
            ->sum('count');

        $violationsPrevious7Days = AppointmentModificationStat::whereHas('customer', function ($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })
            ->where('stat_type', 'violation')
            ->whereBetween('created_at', [now()->subDays(14), now()->subDays(7)])
            ->sum('count');

        $violationTrend = $violationsPrevious7Days > 0
            ? round((($violations7Days - $violationsPrevious7Days) / $violationsPrevious7Days) * 100, 1)
            : 0;

        // 6. Most violated policy type (soft-deleted ones are automatically excluded)
        $mostViolatedPolicy = PolicyConfiguration::where('policy_configurations.company_id', $companyId)
            ->select('policy_type', DB::raw('COUNT(*) as violation_count'))
            ->join('appointment_modification_stats', function ($join) {
                $join->on('policy_configurations.id', '=', DB::raw('JSON_EXTRACT(appointment_modification_stats.metadata, "$.policy_id")'))
                    ->where('appointment_modification_stats.stat_type', '=', 'violation');
            })
            ->groupBy('policy_type')
            ->orderByDesc('violation_count')
            ->first();

        $mostViolatedPolicyName = $mostViolatedPolicy
            ? ucfirst(str_replace('_', ' ', $mostViolatedPolicy->policy_type))
            : 'Keine Verstöße';

        return [
            Stat::make('Aktive Policies', $activePolicies)
                ->description('Derzeit aktive Policy-Konfigurationen')
                ->descriptionIcon('heroicon-m-shield-check')
                ->color('success')
                ->chart($this->getActivePoliciesChart($companyId)),

            Stat::make('Gesamt-Konfigurationen', $totalConfigurations)
                ->description('Alle Policy-Konfigurationen (aktiv + inaktiv)')
                ->descriptionIcon('heroicon-m-cog-6-tooth')
                ->color('info'),

            Stat::make('Verstöße (30 Tage)', $violations30Days)
                ->description($violationTrend < 0 ? "{$violationTrend}% vs. letzte Woche" : "+{$violationTrend}% vs. letzte Woche")
                ->descriptionIcon($violationTrend < 0 ? 'heroicon-m-arrow-trending-down' : 'heroicon-m-arrow-trending-up')
                ->color($violationTrend < 0 ? 'success' : 'danger')
                ->chart($this->getViolationsChart($companyId)),

            Stat::make('Compliance-Rate', "{$complianceRate}%")
                ->description('Termine ohne Verstöße (30 Tage)')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color($complianceRate >= 90 ? 'success' : ($complianceRate >= 70 ? 'warning' : 'danger')),

            Stat::make('Meist verletzter Policy-Typ', $mostViolatedPolicyName)
                ->description('Policy mit den meisten Verstößen')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('warning'),

            Stat::make('Durchschn. Verstöße/Tag', $totalAppointments30Days > 0 ? round($violations30Days / 30, 1) : 0)
                ->description('Letzte 30 Tage')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('gray'),
        ];
    }

    /**
     * Get chart data for active policies over time
     */
    protected function getActivePoliciesChart(int $companyId): array
    {
        $data = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->startOfDay();
            $count = PolicyConfiguration::where('company_id', $companyId)
                ->where('created_at', '<=', $date->endOfDay())
                ->count();

            $data[] = $count;
        }

        return $data;
    }

    /**
     * Get chart data for violations over last 7 days
     */
    protected function getViolationsChart(int $companyId): array
    {
        $data = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $count = AppointmentModificationStat::whereHas('customer', function ($query) use ($companyId) {
                    $query->where('company_id', $companyId);
                })
                ->where('stat_type', 'violation')
                ->whereDate('created_at', $date)
                ->sum('count');

            $data[] = $count;
        }

        return $data;
    }

    /**
     * Get column span
     */
    public function getColumnSpan(): string | array | int
    {
        return 'full';
    }
}
