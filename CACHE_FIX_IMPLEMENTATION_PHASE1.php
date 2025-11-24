<?php
/**
 * PHASE 1: IMMEDIATE SECURITY FIX
 *
 * File: app/Filament/Widgets/CallStatsOverview.php
 * Priority: 🔴 CRITICAL
 * Time: 1 hour
 * Risk: LOW (removes complexity, improves security)
 *
 * This is the UNCACHED version - direct queries with proper filtering.
 * Deploy this immediately to fix multi-tenant cache corruption.
 */

namespace App\Filament\Widgets;

use App\Models\Call;
use App\Services\CostCalculator;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;

class CallStatsOverview extends BaseWidget
{
    protected static ?int $sort = 0;

    // Reduce polling frequency to match removed cache
    protected static ?string $pollingInterval = '120s'; // Was 60s

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
        // 🔴 SECURITY FIX: NO CACHING - Direct query with proper role/company filtering
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

        // Get aggregated stats in single query
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
            // Retrieve calls for cost calculation
            $calls = $query->get();
            foreach ($calls as $call) {
                $todayCost += $costCalculator->getDisplayCost($call, $user);

                // Calculate profit for eligible users
                $profitData = $costCalculator->getDisplayProfit($call, $user);
                if ($profitData['type'] !== 'none') {
                    $todayProfit += $profitData['profit'];
                }
            }

            // Calculate average profit margin
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
        $statsArray = [
            Stat::make('Anrufe heute', $todayCount)
                ->description($todayCount > 0 ? "Gesamt: {$formattedTotalDuration}" : 'Keine Anrufe')
                ->descriptionIcon('heroicon-m-phone')
                ->chart($this->getHourlyCallData($user))
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
        if ($user && ($user->hasRole(['super-admin', 'super_admin', 'Super Admin']) ||
                      $user->hasRole(['reseller_admin', 'reseller_owner', 'reseller_support']))) {

            $profitLabel = $user->hasRole(['super-admin', 'super_admin', 'Super Admin'])
                ? 'Profit heute (Gesamt)'
                : 'Profit heute (Mandant)';

            $statsArray[] = Stat::make(
                $profitLabel,
                '€ ' . number_format($todayProfit / 100, 2, ',', '.')
            )
                ->description($todayProfitMargin > 0 ? "Marge: {$todayProfitMargin}%" : 'Keine Marge')
                ->descriptionIcon('heroicon-m-banknotes')
                ->chart($this->getHourlyProfitData($user))
                ->color($todayProfitMargin > 50 ? 'success' : ($todayProfitMargin > 20 ? 'warning' : 'gray'));
        }

        return $statsArray;
    }

    /**
     * 🔴 SECURITY FIX: User-scoped hourly data (NO CACHING)
     */
    protected function getHourlyCallData(?\App\Models\User $user): array
    {
        // Build query with proper user scoping
        $query = Call::whereDate('created_at', today());

        // Apply same filtering logic as main stats
        if ($user && $user->hasRole(['company_admin', 'company_owner', 'company_staff'])) {
            $query->where('company_id', $user->company_id);
        } elseif ($user && $user->hasRole(['reseller_admin', 'reseller_owner', 'reseller_support']) && $user->company) {
            $query->whereHas('company', function ($q) use ($user) {
                $q->where('parent_company_id', $user->company_id);
            });
        }
        // Super-admin sees all

        // Get hourly counts
        $hourlyData = $query
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
    }

    /**
     * 🔴 SECURITY FIX: User-scoped profit data (NO CACHING)
     */
    protected function getHourlyProfitData(?\App\Models\User $user): array
    {
        if (!$user) return array_fill(0, 24, 0);

        // Build query with proper user scoping
        $query = Call::whereDate('created_at', today());

        // Apply same filtering logic as main stats
        if ($user->hasRole(['company_admin', 'company_owner', 'company_staff'])) {
            $query->where('company_id', $user->company_id);
        } elseif ($user->hasRole(['reseller_admin', 'reseller_owner', 'reseller_support']) && $user->company) {
            $query->whereHas('company', function ($q) use ($user) {
                $q->where('parent_company_id', $user->company_id);
            });
        }
        // Super-admin sees all

        $calls = $query->get();
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

        // Fill in missing hours with 0 and convert cents to euros
        $data = [];
        for ($hour = 0; $hour < 24; $hour++) {
            $data[] = isset($hourlyProfit[$hour]) ? round($hourlyProfit[$hour] / 100, 2) : 0;
        }

        return $data;
    }
}

/**
 * DEPLOYMENT CHECKLIST:
 *
 * ✅ Pre-Deployment:
 * 1. Backup current file: cp app/Filament/Widgets/CallStatsOverview.php app/Filament/Widgets/CallStatsOverview.php.backup
 * 2. Review diff: diff app/Filament/Widgets/CallStatsOverview.php CACHE_FIX_IMPLEMENTATION_PHASE1.php
 * 3. Run tests: vendor/bin/pest --filter CallStats
 *
 * ✅ Deployment:
 * 1. Replace file: cp CACHE_FIX_IMPLEMENTATION_PHASE1.php app/Filament/Widgets/CallStatsOverview.php
 * 2. Clear cache: php artisan cache:clear
 * 3. Clear views: php artisan view:clear
 * 4. Restart queue workers: php artisan queue:restart
 *
 * ✅ Post-Deployment Verification:
 * 1. Test as super-admin: Load dashboard, verify call count
 * 2. Test as company-admin: Load dashboard, verify ONLY company calls shown
 * 3. Test as reseller: Load dashboard, verify only customer calls shown
 * 4. Compare with database: SELECT COUNT(*) FROM calls WHERE DATE(created_at) = CURDATE() AND company_id = ?
 * 5. Monitor logs: tail -f storage/logs/laravel.log
 * 6. Check performance: Monitor page load time (should be <500ms)
 *
 * ✅ Rollback Plan:
 * If issues occur: cp app/Filament/Widgets/CallStatsOverview.php.backup app/Filament/Widgets/CallStatsOverview.php
 * Then: php artisan cache:clear && php artisan view:clear
 *
 * TESTING COMMANDS:
 *
 * # Test super-admin view
 * php artisan tinker --execute="
 *   \$admin = App\Models\User::role('super-admin')->first();
 *   auth()->login(\$admin);
 *   \$widget = app(App\Filament\Widgets\CallStatsOverview::class);
 *   \$stats = \$widget->getStats();
 *   echo 'Super-admin calls: ' . \$stats[0]->getValue() . PHP_EOL;
 * "
 *
 * # Test company-admin view
 * php artisan tinker --execute="
 *   \$admin = App\Models\User::role('company_admin')->first();
 *   auth()->login(\$admin);
 *   \$widget = app(App\Filament\Widgets\CallStatsOverview::class);
 *   \$stats = \$widget->getStats();
 *   echo 'Company-admin calls: ' . \$stats[0]->getValue() . PHP_EOL;
 *   echo 'Company ID: ' . \$admin->company_id . PHP_EOL;
 * "
 *
 * # Compare with DB
 * php artisan tinker --execute="
 *   \$companyId = 1;
 *   \$count = App\Models\Call::whereDate('created_at', today())->where('company_id', \$companyId)->count();
 *   echo 'DB count for company ' . \$companyId . ': ' . \$count . PHP_EOL;
 * "
 *
 * PERFORMANCE EXPECTATIONS:
 * - Query time: 50-100ms (acceptable)
 * - Widget load: 200-300ms total
 * - Database impact: Minimal (5 calls/day = low load)
 * - Polling: Every 120s (was 60s)
 * - Concurrent users: Up to 50 without performance issues
 *
 * KNOWN LIMITATIONS:
 * - No caching means every widget load hits DB
 * - Higher load if >100 concurrent users
 * - Phase 2 will re-introduce secure caching
 *
 * NEXT STEPS (PHASE 2):
 * - Implement Filter-Outside-Cache pattern
 * - Add cache invalidation listeners
 * - Comprehensive multi-tenant testing
 * - Cache architecture review (other widgets)
 *
 * See: CACHE_CORRUPTION_ANALYSIS_2025-11-21.md for full details
 */