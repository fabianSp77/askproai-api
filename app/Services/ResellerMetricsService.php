<?php

namespace App\Services;

use App\Models\Company;
use App\Models\PrepaidTransaction;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ResellerMetricsService
{
    /**
     * Get revenue metrics for a specific reseller
     */
    public function getRevenueMetrics(Company $reseller, int $ttl = 300): array
    {
        $cacheKey = "reseller_revenue_metrics_{$reseller->id}";
        
        return Cache::remember($cacheKey, $ttl, function() use ($reseller) {
            $childCompanyIds = $reseller->childCompanies()->pluck('id')->toArray();
            
            if (empty($childCompanyIds)) {
                return [
                    'total_revenue' => 0,
                    'ytd_revenue' => 0,
                    'mtd_revenue' => 0,
                    'commission_earned' => 0,
                ];
            }
            
            // Single optimized query for all metrics
            $metrics = DB::table('prepaid_transactions')
                ->whereIn('company_id', $childCompanyIds)
                ->where('type', 'deduction')
                ->selectRaw('
                    SUM(amount) as total_revenue,
                    SUM(CASE WHEN YEAR(created_at) = YEAR(CURDATE()) THEN amount ELSE 0 END) as ytd_revenue,
                    SUM(CASE WHEN MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE()) THEN amount ELSE 0 END) as mtd_revenue
                ')
                ->first();
            
            $totalRevenue = $metrics->total_revenue ?? 0;
            $commissionRate = $reseller->commission_rate ?? 0;
            
            return [
                'total_revenue' => $totalRevenue,
                'ytd_revenue' => $metrics->ytd_revenue ?? 0,
                'mtd_revenue' => $metrics->mtd_revenue ?? 0,
                'commission_earned' => $totalRevenue * ($commissionRate / 100),
            ];
        });
    }
    
    /**
     * Get aggregated stats for all resellers
     */
    public function getAggregatedStats(int $ttl = 300): array
    {
        return Cache::remember('reseller_aggregated_stats', $ttl, function() {
            // Single query with JOINs for all stats
            $stats = DB::table('companies as r')
                ->selectRaw('
                    COUNT(DISTINCT r.id) as total_resellers,
                    COUNT(DISTINCT CASE WHEN r.is_active = 1 THEN r.id END) as active_resellers,
                    COUNT(DISTINCT c.id) as total_clients,
                    COALESCE(SUM(pt.amount), 0) as total_revenue
                ')
                ->leftJoin('companies as c', function($join) {
                    $join->on('c.parent_company_id', '=', 'r.id')
                         ->where('c.company_type', '=', 'client')
                         ->whereNull('c.deleted_at');
                })
                ->leftJoin('prepaid_transactions as pt', function($join) {
                    $join->on('pt.company_id', '=', 'c.id')
                         ->where('pt.type', '=', 'deduction');
                })
                ->where('r.company_type', 'reseller')
                ->whereNull('r.deleted_at')
                ->first();
                
            return (array) $stats;
        });
    }
    
    /**
     * Get monthly revenue data for charts
     */
    public function getMonthlyRevenueData(Company $reseller, int $months = 12, int $ttl = 600): array
    {
        $cacheKey = "reseller_monthly_revenue_{$reseller->id}_{$months}";
        
        return Cache::remember($cacheKey, $ttl, function() use ($reseller, $months) {
            $childCompanyIds = $reseller->childCompanies()->pluck('id')->toArray();
            
            if (empty($childCompanyIds)) {
                return $this->getEmptyChartData($months);
            }
            
            // Get real monthly data
            $monthlyData = DB::table('prepaid_transactions')
                ->whereIn('company_id', $childCompanyIds)
                ->where('type', 'deduction')
                ->where('created_at', '>=', now()->subMonths($months))
                ->selectRaw('
                    YEAR(created_at) as year,
                    MONTH(created_at) as month,
                    SUM(amount) as revenue
                ')
                ->groupBy('year', 'month')
                ->orderBy('year')
                ->orderBy('month')
                ->get();
            
            return $this->formatChartData($monthlyData, $reseller->commission_rate ?? 0, $months);
        });
    }
    
    /**
     * Get top performing resellers
     */
    public function getTopResellers(int $limit = 10, int $ttl = 300): array
    {
        return Cache::remember("top_resellers_{$limit}", $ttl, function() use ($limit) {
            return DB::table('companies as r')
                ->selectRaw('
                    r.id,
                    r.name,
                    r.commission_rate,
                    COUNT(DISTINCT c.id) as client_count,
                    COALESCE(SUM(pt.amount), 0) as total_revenue
                ')
                ->leftJoin('companies as c', function($join) {
                    $join->on('c.parent_company_id', '=', 'r.id')
                         ->where('c.company_type', '=', 'client')
                         ->whereNull('c.deleted_at');
                })
                ->leftJoin('prepaid_transactions as pt', function($join) {
                    $join->on('pt.company_id', '=', 'c.id')
                         ->where('pt.type', '=', 'deduction');
                })
                ->where('r.company_type', 'reseller')
                ->where('r.is_active', true)
                ->whereNull('r.deleted_at')
                ->groupBy('r.id', 'r.name', 'r.commission_rate')
                ->orderByDesc('total_revenue')
                ->limit($limit)
                ->get()
                ->toArray();
        });
    }
    
    /**
     * Invalidate cache for a specific reseller
     */
    public function invalidateResellerCache(int $resellerId): void
    {
        Cache::forget("reseller_revenue_metrics_{$resellerId}");
        Cache::forget("reseller_monthly_revenue_{$resellerId}_12");
        Cache::forget("reseller_performance_{$resellerId}");
        Cache::forget('reseller_aggregated_stats');
        Cache::forget('top_resellers_10');
    }
    
    /**
     * Invalidate all reseller caches
     */
    public function invalidateAllCaches(): void
    {
        Cache::tags(['reseller_metrics'])->flush();
    }
    
    /**
     * Format chart data
     */
    private function formatChartData($monthlyData, float $commissionRate, int $months): array
    {
        $labels = [];
        $revenues = [];
        $commissions = [];
        
        for ($i = $months - 1; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $labels[] = $date->format('M Y');
            
            $monthData = $monthlyData->firstWhere(function($item) use ($date) {
                return $item->year == $date->year && $item->month == $date->month;
            });
            
            $revenue = $monthData->revenue ?? 0;
            $revenues[] = round($revenue, 2);
            $commissions[] = round($revenue * ($commissionRate / 100), 2);
        }
        
        return [
            'labels' => $labels,
            'revenues' => $revenues,
            'commissions' => $commissions,
        ];
    }
    
    /**
     * Get empty chart data structure
     */
    private function getEmptyChartData(int $months): array
    {
        $labels = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $labels[] = now()->subMonths($i)->format('M Y');
        }
        
        return [
            'labels' => $labels,
            'revenues' => array_fill(0, $months, 0),
            'commissions' => array_fill(0, $months, 0),
        ];
    }
}