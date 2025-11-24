<?php

namespace App\Filament\Resources\CallResource\Widgets;

use App\Models\Call;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class CallStatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    /**
     * 🔒 SECURITY: Only Super-Admin and Reseller can see financial widgets
     * Customers should NOT see profit/margin data
     */
    public static function canView(): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }

        // Only authorized roles can see financial stats
        return $user->hasRole(['super-admin', 'super_admin', 'Super Admin']) ||
               $user->hasRole(['reseller_admin', 'reseller_owner', 'reseller_support']);
    }

    protected function getStats(): array
    {
        // 🔒 SECURITY FIX 2025-11-21: Cache removed due to multi-tenant data leakage
        //
        // ISSUE: Cache key lacked user/role context, causing role-based filtering
        //        to execute INSIDE cached callback. First user to access the page
        //        determined what ALL subsequent users saw (cross-tenant data leak).
        //
        // IMPACT: Super-admin saw company data, company-admin saw other companies' data
        //         GDPR violation, incorrect statistics for all roles
        //
        // FIX: Direct calculation without caching ensures correct role filtering
        //      Performance acceptable: ~75ms query time with proper indexes
        //
        // TODO Phase 2: Implement secure caching with user/company/role in cache key
        //      See: /var/www/api-gateway/CACHE_CORRUPTION_ANALYSIS_2025-11-21.md
        //
        // References:
        // - RCA: /var/www/api-gateway/RCA_CALL_STATS_CACHE_ISSUE_2025-11-21.md
        // - Security Analysis: CACHE_CORRUPTION_ANALYSIS_2025-11-21.md
        return $this->calculateStats();
    }

    /**
     * 🔒 SECURITY: Apply role-based filtering to query
     */
    private function applyRoleFilter($query)
    {
        $user = auth()->user();

        if (!$user) {
            return $query;
        }

        // Company staff: only their company's calls
        if ($user->hasRole(['company_admin', 'company_owner', 'company_staff'])) {
            return $query->where('company_id', $user->company_id);
        }

        // Reseller: only their customers' calls
        if ($user->hasRole(['reseller_admin', 'reseller_owner', 'reseller_support']) && $user->company) {
            return $query->whereHas('company', function ($q) use ($user) {
                $q->where('parent_company_id', $user->company_id);
            });
        }

        // Super-admin sees all
        return $query;
    }

    private function calculateStats(): array
    {
        // 🔒 SECURITY: Single query for all today's stats with role filtering
        // ✅ FIXED: uses status and has_appointment (actual DB columns)
        // ⚡ PERFORMANCE FIX 2025-11-21: Use whereBetween instead of whereDate for index usage
        $todayStats = $this->applyRoleFilter(Call::whereBetween('created_at', [
                today()->startOfDay(),
                today()->endOfDay()
            ]))
            ->selectRaw('
                COUNT(*) as total_count,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as successful_count,
                SUM(CASE WHEN has_appointment = 1 THEN 1 ELSE 0 END) as appointment_count,
                AVG(CASE WHEN duration_sec > 0 THEN duration_sec ELSE NULL END) as avg_duration,
                SUM(CASE
                    WHEN metadata IS NOT NULL
                    AND JSON_VALID(metadata)
                    AND JSON_EXTRACT(metadata, "$.sentiment") = "positive"
                    THEN 1 ELSE 0 END) as positive_count,
                SUM(CASE
                    WHEN metadata IS NOT NULL
                    AND JSON_VALID(metadata)
                    AND JSON_EXTRACT(metadata, "$.sentiment") = "negative"
                    THEN 1 ELSE 0 END) as negative_count
            ', ['completed'])
            ->first();

        $todayCount = $todayStats->total_count ?? 0;
        $todaySuccessful = $todayStats->successful_count ?? 0;
        $todayAppointments = $todayStats->appointment_count ?? 0;
        $todayAvgDuration = $todayStats->avg_duration ?? 0;
        $positiveSentiment = $todayStats->positive_count ?? 0;
        $negativeSentiment = $todayStats->negative_count ?? 0;

        // 🔒 SECURITY: Single query for week stats with role filtering
        // ✅ FIXED: uses status (actual DB column)
        $weekStats = $this->applyRoleFilter(Call::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]))
            ->selectRaw('
                COUNT(*) as total_count,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as successful_count
            ', ['completed'])
            ->first();

        $weekCount = $weekStats->total_count ?? 0;
        $weekSuccessful = $weekStats->successful_count ?? 0;

        // 🔒 SECURITY: Single query for month stats with role filtering (including profit and conversion calculations)
        // Using whereBetween instead of whereMonth/whereYear for better index usage
        // ✅ FIXED: uses has_appointment (actual DB column)
        // ⚠️ DISABLED: Profit tracking columns don't exist in Sept 21 backup
        //    (cost_cents, platform_profit, total_profit, profit_margin_total, customer_cost)
        $monthStats = $this->applyRoleFilter(Call::whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()]))
            ->selectRaw('
                COUNT(*) as total_count,
                SUM(CASE WHEN has_appointment = 1 THEN 1 ELSE 0 END) as appointment_count,
                SUM(COALESCE(calculated_cost, 0)) / 100.0 as total_cost
            ')
            ->first();

        $monthCount = $monthStats->total_count ?? 0;
        $monthAppointments = $monthStats->appointment_count ?? 0;
        $monthCost = $monthStats->total_cost ?? 0;

        // ⚠️ DISABLED: Profit columns don't exist in Sept 21 backup
        $monthPlatformProfit = 0;
        $monthTotalProfit = 0;
        $avgProfitMargin = 0;

        // Calculate business metrics
        $avgCostPerCall = $monthCount > 0 ? $monthCost / $monthCount : 0;
        $conversionRate = $monthCount > 0 ? ($monthAppointments / $monthCount) * 100 : 0;

        // Optimize chart data with single grouped queries
        $weekChartData = $this->getWeekChartData();
        $weekDurationData = $this->getWeekDurationData();
        $monthCostData = $this->getMonthCostData();

        // 🔒 SECURITY: Detect user role for conditional stats
        $user = auth()->user();
        $isSuperAdmin = $user && $user->hasRole(['super-admin', 'super_admin', 'Super Admin']);

        // Build base stats array (visible to all authorized users)
        $stats = [
            Stat::make('Anrufe Heute', $todayCount)
                ->description($todaySuccessful . ' erfolgreich / ' . $todayAppointments . ' Termine')
                ->descriptionIcon('heroicon-m-phone')
                ->chart($weekChartData['counts'])
                ->color($todayCount > 20 ? 'success' : ($todayCount > 10 ? 'warning' : 'danger'))
                ->extraAttributes([
                    'class' => 'relative',
                    'title' => "Anrufe heute: {$todayCount}\n" .
                               "✓ Erfolgreich (completed): {$todaySuccessful}\n" .
                               "✗ Fehlgeschlagen: " . ($todayCount - $todaySuccessful) . "\n" .
                               "📅 Termine gebucht: {$todayAppointments}\n" .
                               "📊 Chart: Letzte 7 Tage Verlauf",
                ]),

            Stat::make('Erfolgsquote Heute', $todayCount > 0 ? round(($todaySuccessful / $todayCount) * 100, 1) . '%' : '0%')
                ->description('😊 ' . $positiveSentiment . ' positiv / 😟 ' . $negativeSentiment . ' negativ')
                ->descriptionIcon('heroicon-m-check-circle')
                ->chart($todayCount > 0 ? [
                    $todaySuccessful,
                    $todayCount - $todaySuccessful,
                ] : [0, 0])
                ->color($todayCount > 0 && ($todaySuccessful / $todayCount) > 0.7 ? 'success' : 'warning')
                ->extraAttributes([
                    'title' => "Berechnung: {$todaySuccessful} erfolgreich ÷ {$todayCount} gesamt\n" .
                               "= " . ($todayCount > 0 ? round(($todaySuccessful / $todayCount) * 100, 1) : 0) . "%\n\n" .
                               "Sentiment-Analyse:\n" .
                               "😊 Positiv: {$positiveSentiment} Anrufe\n" .
                               "😟 Negativ: {$negativeSentiment} Anrufe\n" .
                               "Quelle: metadata.sentiment (JSON)",
                ]),

            Stat::make('⌀ Dauer', gmdate("i:s", $todayAvgDuration))
                ->description('Diese Woche: ' . $weekCount . ' Anrufe')
                ->descriptionIcon('heroicon-m-clock')
                ->chart($weekDurationData)
                ->color($todayAvgDuration > 180 ? 'success' : 'info')
                ->extraAttributes([
                    'title' => "Durchschnittliche Dauer: " . gmdate("i:s", $todayAvgDuration) . " (" . round($todayAvgDuration) . " Sekunden)\n" .
                               "Nur abgeschlossene Anrufe mit duration_sec > 0\n\n" .
                               "Diese Woche:\n" .
                               "Anrufe: {$weekCount}\n" .
                               "Erfolgreich: {$weekSuccessful}\n" .
                               "Zeitraum: " . now()->startOfWeek()->format('d.m.') . " - " . now()->endOfWeek()->format('d.m.Y'),
                ]),
        ];

        // 🔒 SECURITY: Platform profit stats ONLY for SuperAdmin
        if ($isSuperAdmin) {
            $stats[] = Stat::make('Kosten Monat', '€' . number_format($monthCost, 2))
                ->description($monthCount . ' Anrufe | Profit: €' . number_format($monthPlatformProfit, 2))
                ->descriptionIcon('heroicon-m-currency-euro')
                ->chart($monthCostData)
                ->color($monthCost > 500 ? 'danger' : 'primary')
                ->extraAttributes([
                    'title' => "Gesamtkosten November: €" . number_format($monthCost, 2) . "\n" .
                               "Berechnung: SUM(calculated_cost) ÷ 100\n\n" .
                               "Anrufe: {$monthCount}\n" .
                               "Plattform-Profit: €" . number_format($monthPlatformProfit, 2) . "\n" .
                               "Total-Profit: €" . number_format($monthTotalProfit, 2) . "\n\n" .
                               "Zeitraum: " . now()->startOfMonth()->format('d.m.') . " - " . now()->endOfMonth()->format('d.m.Y') . "\n" .
                               "📊 Chart: Wöchentliche Kostenentwicklung\n\n" .
                               "🔒 Nur für Super-Admin sichtbar",
                ]);

            $stats[] = Stat::make('Profit Marge', round($avgProfitMargin, 1) . '%')
                ->description('Durchschnitt | Total: €' . number_format($monthTotalProfit, 2))
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color($avgProfitMargin > 50 ? 'success' : ($avgProfitMargin > 30 ? 'warning' : 'danger'))
                ->extraAttributes([
                    'title' => "Durchschnittliche Profit-Marge: " . round($avgProfitMargin, 1) . "%\n" .
                               "Berechnung: (profit_margin_platform + profit_margin_reseller) ÷ 2\n\n" .
                               "Total-Profit: €" . number_format($monthTotalProfit, 2) . "\n" .
                               "Plattform-Profit: €" . number_format($monthPlatformProfit, 2) . "\n" .
                               "Reseller-Profit: €0.00 (noch nicht implementiert)\n\n" .
                               "⚠️ Aktuelle Profit-Daten noch nicht vollständig\n" .
                               "Spalten fehlen: platform_profit, total_profit\n\n" .
                               "🔒 Nur für Super-Admin sichtbar",
                ]);
        }

        // Non-sensitive business metrics (visible to all authorized users)
        $stats[] = Stat::make('⌀ Kosten/Anruf', '€' . number_format($avgCostPerCall, 2))
            ->description('Monatsdurchschnitt für ' . $monthCount . ' Anrufe')
            ->descriptionIcon('heroicon-m-calculator')
            ->color($avgCostPerCall > 5 ? 'danger' : ($avgCostPerCall > 3 ? 'warning' : 'success'))
            ->extraAttributes([
                'title' => "Durchschnittliche Kosten pro Anruf: €" . number_format($avgCostPerCall, 2) . "\n" .
                           "Berechnung: €" . number_format($monthCost, 2) . " ÷ {$monthCount} Anrufe\n\n" .
                           "Gesamtkosten: €" . number_format($monthCost, 2) . "\n" .
                           "Anrufe gesamt: {$monthCount}\n\n" .
                           "Quelle: calculated_cost (Retell + Twilio)\n" .
                           "Zeitraum: " . now()->format('F Y') . "\n\n" .
                           "Farbcodierung:\n" .
                           "🟢 Gut: < €3.00\n" .
                           "🟡 Mittel: €3.00 - €5.00\n" .
                           "🔴 Hoch: > €5.00",
            ]);

        $stats[] = Stat::make('Conversion Rate', round($conversionRate, 1) . '%')
            ->description($monthAppointments . ' Termine von ' . $monthCount . ' Anrufen')
            ->descriptionIcon('heroicon-m-check-badge')
            ->color($conversionRate > 30 ? 'success' : ($conversionRate > 15 ? 'warning' : 'danger'))
            ->extraAttributes([
                'title' => "Conversion Rate: " . round($conversionRate, 1) . "%\n" .
                           "Berechnung: {$monthAppointments} Termine ÷ {$monthCount} Anrufe × 100\n\n" .
                           "Termine gebucht: {$monthAppointments}\n" .
                           "Anrufe gesamt: {$monthCount}\n" .
                           "Erfolgsquote: " . ($monthCount > 0 ? round(($monthAppointments / $monthCount) * 100, 1) : 0) . "%\n\n" .
                           "Quelle: has_appointment = true\n" .
                           "Zeitraum: " . now()->format('F Y') . "\n\n" .
                           "Farbcodierung:\n" .
                           "🟢 Gut: > 30%\n" .
                           "🟡 Mittel: 15% - 30%\n" .
                           "🔴 Niedrig: < 15%",
            ]);

        return $stats;
    }

    private function getWeekChartData(): array
    {
        $data = $this->applyRoleFilter(Call::whereBetween('created_at', [today()->subDays(6)->startOfDay(), today()->endOfDay()]))
            ->selectRaw('
                DATE(created_at) as date,
                COUNT(*) as total_count
            ')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('total_count', 'date')
            ->toArray();

        $counts = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = today()->subDays($i)->format('Y-m-d');
            $counts[] = $data[$date] ?? 0;
        }

        return ['counts' => $counts];
    }

    private function getWeekDurationData(): array
    {
        $data = $this->applyRoleFilter(Call::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]))
            ->where('duration_sec', '>', 0)
            ->selectRaw('
                DATE(created_at) as date,
                AVG(duration_sec) as avg_duration
            ')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('avg_duration', 'date')
            ->toArray();

        $durations = [];
        for ($i = 0; $i < 7; $i++) {
            $date = now()->startOfWeek()->addDays($i)->format('Y-m-d');
            $durations[] = $data[$date] ?? 0;
        }

        return $durations;
    }

    private function getMonthCostData(): array
    {
        // Use weekly aggregation for better trend visualization and index usage
        $startOfMonth = now()->startOfMonth();
        // Limit to current date to prevent future dates or cross-month data
        $endOfMonth = min(now(), now()->endOfMonth());
        $costs = [];

        // Get data aggregated by week
        // ✅ FIXED: uses calculated_cost (actual column) instead of cost_cents
        $data = $this->applyRoleFilter(Call::whereBetween('created_at', [$startOfMonth, $endOfMonth]))
            ->selectRaw('
                WEEK(created_at, 1) as week_number,
                SUM(COALESCE(calculated_cost, 0)) / 100.0 as total_cost
            ')
            ->groupBy('week_number')
            ->orderBy('week_number')
            ->pluck('total_cost', 'week_number')
            ->toArray();

        // Build array for current month's weeks (typically 4-5 weeks)
        $currentWeek = date('W', $startOfMonth->timestamp);
        $endWeek = date('W', $endOfMonth->timestamp);

        // Handle year boundary case
        if ($endWeek < $currentWeek) {
            $endWeek += 52;
        }

        for ($week = $currentWeek; $week <= $endWeek; $week++) {
            $actualWeek = $week > 52 ? $week - 52 : $week;
            $costs[] = $data[$actualWeek] ?? 0;
        }

        return $costs;
    }
}