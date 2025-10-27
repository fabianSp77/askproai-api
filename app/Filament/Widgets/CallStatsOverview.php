<?php

namespace App\Filament\Widgets;

use App\Models\Call;
use App\Services\CostCalculator;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;

class CallStatsOverview extends BaseWidget
{
    protected static ?int $sort = 0;

    protected static ?string $pollingInterval = '60s';

    protected function getCostLabel(?\App\Models\User $user): string
    {
        if (!$user) {
            return 'Kosten heute';
        }

        if ($user->hasRole(['super-admin', 'super_admin', 'Super Admin'])) {
            return 'Kosten heute (Kunde)';
        } elseif ($user->hasRole(['reseller_admin', 'reseller_owner', 'reseller_support'])) {
            return 'Kosten heute (Mandant)';
        }

        return 'Kosten heute';
    }

    protected function getStats(): array
    {
        // Cache stats for 2 minutes to reduce database load
        $cacheKey = 'call-stats-overview-' . (auth()->user()->company_id ?? 'global');
        $cacheMinutes = floor(now()->format('i') / 2); // Changes every 2 minutes
        $fullCacheKey = $cacheKey . '-' . now()->format('Y-m-d-H') . '-' . $cacheMinutes;

        return \Illuminate\Support\Facades\Cache::remember($fullCacheKey, 120, function () {
            // Get all data in one query using aggregation
            $user = auth()->user();
            $costCalculator = new CostCalculator();

            // Get base query
            $query = Call::whereDate('created_at', today());

            // Apply company filter based on user role
            if ($user && $user->hasRole(['company_admin', 'company_owner', 'company_staff'])) {
                $query->where('company_id', $user->company_id);
            } elseif ($user && $user->hasRole(['reseller_admin', 'reseller_owner', 'reseller_support']) && $user->company) {
                // Show calls for reseller's customers
                $query->whereHas('company', function ($q) use ($user) {
                    $q->where('parent_company_id', $user->company_id);
                });
            }
            // Super-admin sees all calls

            // ✅ FIXED: uses has_appointment (actual DB column) instead of appointment_made
            $stats = $query->selectRaw('
                    COUNT(*) as total_count,
                    SUM(duration_sec) as total_duration,
                    SUM(CASE WHEN has_appointment = 1 THEN 1 ELSE 0 END) as appointment_count
                ')
                ->first();

            $todayCount = $stats->total_count ?? 0;
            $todayDuration = $stats->total_duration ?? 0;
            $todayAppointments = $stats->appointment_count ?? 0;

            // Calculate costs and profits based on user role
            $todayCost = 0;
            $todayProfit = 0;
            $todayProfitMargin = 0;

            if ($todayCount > 0) {
                $calls = $query->get();
                foreach ($calls as $call) {
                    $todayCost += $costCalculator->getDisplayCost($call, $user);

                    // Calculate profit for eligible users
                    $profitData = $costCalculator->getDisplayProfit($call, $user);
                    if ($profitData['type'] !== 'none') {
                        $todayProfit += $profitData['profit'];
                    }
                }

                // Calculate average profit margin (profit as percentage of cost)
                if ($todayCost > 0 && $todayProfit > 0) {
                    $todayProfitMargin = round(($todayProfit / $todayCost) * 100, 1);
                }
            }

            // Calculate averages
            $avgDuration = $todayCount > 0 ? round($todayDuration / $todayCount) : 0;
            $appointmentRate = $todayCount > 0 ? round(($todayAppointments / $todayCount) * 100, 1) : 0;
            $avgCost = $todayCount > 0 ? $todayCost / $todayCount : 0;

            // Format duration
            $formattedAvgDuration = gmdate("i:s", $avgDuration);
            $totalHours = floor($todayDuration / 3600);
            $totalMinutes = floor(($todayDuration % 3600) / 60);
            $formattedTotalDuration = "{$totalHours}h {$totalMinutes}m";

            // Build stats array
            $stats = [
                Stat::make('Anrufe heute', $todayCount)
                    ->description($todayCount > 0 ? "Gesamt: {$formattedTotalDuration}" : 'Keine Anrufe')
                    ->descriptionIcon('heroicon-m-phone')
                    ->chart($this->getHourlyCallData())
                    ->color($todayCount > 50 ? 'success' : 'primary'),

                Stat::make('⌀ Dauer', $formattedAvgDuration)
                    ->description($todayDuration > 0 ? 'Minuten:Sekunden' : 'Keine Daten')
                    ->descriptionIcon('heroicon-m-clock')
                    ->color($avgDuration > 300 ? 'danger' : ($avgDuration > 180 ? 'warning' : 'success')),

                Stat::make('Termine', $todayAppointments)
                    ->description("{$appointmentRate}% Erfolgsquote")
                    ->descriptionIcon('heroicon-m-calendar-days')
                    ->color($appointmentRate > 30 ? 'success' : ($appointmentRate > 15 ? 'warning' : 'danger')),

                Stat::make(
                    $this->getCostLabel(auth()->user()),
                    '€ ' . number_format($todayCost / 100, 2, ',', '.')
                )
                    ->description('⌀ € ' . number_format($avgCost / 100, 2, ',', '.') . ' pro Anruf')
                    ->descriptionIcon('heroicon-m-currency-euro')
                    ->color($avgCost > 500 ? 'danger' : ($avgCost > 200 ? 'warning' : 'success')),
            ];

            // Add profit stat for eligible users
            $user = auth()->user();
            if ($user && ($user->hasRole(['super-admin', 'super_admin', 'Super Admin']) ||
                          $user->hasRole(['reseller_admin', 'reseller_owner', 'reseller_support']))) {

                $profitLabel = $user->hasRole(['super-admin', 'super_admin', 'Super Admin'])
                    ? 'Profit heute (Gesamt)'
                    : 'Profit heute (Mandant)';

                $stats[] = Stat::make(
                    $profitLabel,
                    '€ ' . number_format($todayProfit / 100, 2, ',', '.')
                )
                    ->description($todayProfitMargin > 0 ? "Marge: {$todayProfitMargin}%" : 'Keine Marge')
                    ->descriptionIcon('heroicon-m-banknotes')
                    ->chart($this->getHourlyProfitData())
                    ->color($todayProfitMargin > 50 ? 'success' : ($todayProfitMargin > 20 ? 'warning' : 'gray'));
            }

            return $stats;
        });
    }

    protected function getHourlyCallData(): array
    {
        // Cache hourly data for 5 minutes
        $cacheKey = 'call-hourly-data-' . now()->format('Y-m-d');
        $cacheMinutes = floor(now()->format('i') / 5);

        return \Illuminate\Support\Facades\Cache::remember($cacheKey . '-' . $cacheMinutes, 300, function () {
            // Get all hourly counts in one query
            $hourlyData = Call::whereDate('created_at', today())
                ->selectRaw('HOUR(created_at) as hour, COUNT(*) as count')
                ->groupBy('hour')
                ->pluck('count', 'hour')
                ->toArray();

            // Fill in missing hours with 0
            $data = [];
            for ($hour = 0; $hour < 24; $hour++) {
                $data[] = $hourlyData[$hour] ?? 0;
            }
            return $data;
        });
    }

    protected function getHourlyProfitData(): array
    {
        // Cache hourly profit data for 5 minutes
        $cacheKey = 'profit-hourly-data-' . now()->format('Y-m-d');
        $cacheMinutes = floor(now()->format('i') / 5);

        return \Illuminate\Support\Facades\Cache::remember($cacheKey . '-' . $cacheMinutes, 300, function () {
            $user = auth()->user();
            if (!$user) return array_fill(0, 24, 0);

            // Get all calls for today with profit data
            // ⚠️ DISABLED: total_profit column doesn't exist in Sept 21 backup
            // TODO: Re-enable when database is fully restored
            $calls = Call::whereDate('created_at', today())
                // ->whereNotNull('total_profit')  // ❌ Column doesn't exist
                ->get();

            $costCalculator = new CostCalculator();
            $hourlyProfit = [];

            foreach ($calls as $call) {
                $hour = $call->created_at->hour;
                $profitData = $costCalculator->getDisplayProfit($call, $user);

                if (!isset($hourlyProfit[$hour])) {
                    $hourlyProfit[$hour] = 0;
                }

                if ($profitData['type'] !== 'none') {
                    $hourlyProfit[$hour] += $profitData['profit'];
                }
            }

            // Fill in missing hours with 0 and convert to cents to euros
            $data = [];
            for ($hour = 0; $hour < 24; $hour++) {
                $data[] = isset($hourlyProfit[$hour]) ? round($hourlyProfit[$hour] / 100, 2) : 0;
            }

            return $data;
        });
    }
}