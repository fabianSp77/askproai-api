<?php

namespace App\Services;

use App\Models\Company;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ResellerAnalyticsService
{
    /**
     * Get reseller performance metrics
     */
    public function getResellerMetrics(Company $reseller): array
    {
        if ($reseller->company_type !== 'reseller') {
            throw new \InvalidArgumentException('Company must be a reseller');
        }

        $clients = $reseller->childCompanies();
        
        // Use the new metrics service for revenue data
        $metricsService = app(ResellerMetricsService::class);
        $revenueMetrics = $metricsService->getRevenueMetrics($reseller);
        
        return [
            'total_clients' => $clients->count(),
            'active_clients' => $clients->where('is_active', true)->count(),
            'inactive_clients' => $clients->where('is_active', false)->count(),
            'revenue_ytd' => $revenueMetrics['ytd_revenue'] ?? 0,
            'commission_earned' => $revenueMetrics['commission_earned'] ?? 0,
            'average_revenue_per_client' => $this->calculateAverageRevenuePerClient($reseller),
            'growth_rate' => $this->calculateGrowthRate($reseller),
            'client_retention_rate' => $this->calculateClientRetentionRate($reseller),
        ];
    }

    /**
     * Calculate commission earned by reseller
     */
    public function calculateCommissionEarned(Company $reseller): float
    {
        // Use the new metrics service
        $metricsService = app(ResellerMetricsService::class);
        $revenueMetrics = $metricsService->getRevenueMetrics($reseller);
        
        return $revenueMetrics['commission_earned'] ?? 0;
    }

    /**
     * Calculate average revenue per client
     */
    public function calculateAverageRevenuePerClient(Company $reseller): float
    {
        $clientCount = $reseller->childCompanies()->count();
        
        // Use the new metrics service
        $metricsService = app(ResellerMetricsService::class);
        $revenueMetrics = $metricsService->getRevenueMetrics($reseller);
        $revenue = $revenueMetrics['total_revenue'] ?? 0;
        
        return $clientCount > 0 ? $revenue / $clientCount : 0;
    }

    /**
     * Calculate growth rate (simplified - would need historical data)
     */
    public function calculateGrowthRate(Company $reseller): float
    {
        // This is a simplified calculation
        // In a real application, you'd compare current vs previous period
        $currentRevenue = $reseller->revenue_ytd ?? 0;
        $previousRevenue = $reseller->revenue_previous_year ?? 1; // Avoid division by zero
        
        if ($previousRevenue == 0) {
            return $currentRevenue > 0 ? 100 : 0;
        }
        
        return (($currentRevenue - $previousRevenue) / $previousRevenue) * 100;
    }

    /**
     * Calculate client retention rate
     */
    public function calculateClientRetentionRate(Company $reseller): float
    {
        $totalClients = $reseller->childCompanies()->count();
        $activeClients = $reseller->childCompanies()->where('is_active', true)->count();
        
        return $totalClients > 0 ? ($activeClients / $totalClients) * 100 : 0;
    }

    /**
     * Get top performing resellers
     */
    public function getTopResellers(int $limit = 10): Collection
    {
        // Use the new metrics service for top resellers
        $metricsService = app(ResellerMetricsService::class);
        $topResellers = $metricsService->getTopResellers($limit);
        
        return collect($topResellers)->map(function ($resellerData) {
            $reseller = Company::find($resellerData->id);
            return [
                'reseller' => $reseller,
                'metrics' => $this->getResellerMetrics($reseller),
            ];
        });
    }

    /**
     * Get reseller hierarchy data for visualization
     */
    public function getResellerHierarchy(): array
    {
        $resellers = Company::query()
            ->where('company_type', 'reseller')
            ->with(['childCompanies' => function ($query) {
                $query->withCount(['branches', 'staff', 'customers', 'appointments']);
            }])
            ->get();

        return $resellers->map(function (Company $reseller) {
            return [
                'data' => [
                    'id' => $reseller->id,
                    'name' => $reseller->name,
                    'is_active' => $reseller->is_active,
                    'commission_rate' => $reseller->commission_rate,
                    'is_white_label' => $reseller->is_white_label,
                    'clients' => $reseller->childCompanies->map(function (Company $client) {
                        return [
                            'id' => $client->id,
                            'name' => $client->name,
                            'is_active' => $client->is_active,
                            'industry' => $client->industry,
                            'branches_count' => $client->branches_count ?? 0,
                            'staff_count' => $client->staff_count ?? 0,
                            'customers_count' => $client->customers_count ?? 0,
                            'appointments_count' => $client->appointments_count ?? 0,
                        ];
                    })->toArray(),
                ],
                'metrics' => $this->getResellerMetrics($reseller),
            ];
        })->toArray();
    }

    /**
     * Generate monthly revenue data for charts
     */
    public function getMonthlyRevenueData(Company $reseller, int $months = 12): array
    {
        // Use the new metrics service for real data
        $metricsService = app(ResellerMetricsService::class);
        return $metricsService->getMonthlyRevenueData($reseller, $months);
    }

    /**
     * Get industry distribution for reseller's clients
     */
    public function getClientIndustryDistribution(Company $reseller): array
    {
        return $reseller->childCompanies()
            ->select('industry', DB::raw('count(*) as count'))
            ->groupBy('industry')
            ->pluck('count', 'industry')
            ->toArray();
    }

    /**
     * Calculate total commission payout for all resellers
     */
    public function getTotalCommissionPayout(): float
    {
        return Company::query()
            ->where('company_type', 'reseller')
            ->get()
            ->sum(function (Company $reseller) {
                return $this->calculateCommissionEarned($reseller);
            });
    }

    /**
     * Get reseller performance comparison
     */
    public function compareResellerPerformance(): array
    {
        $resellers = Company::query()
            ->where('company_type', 'reseller')
            ->where('is_active', true)
            ->withCount('childCompanies')
            ->get();

        $totalRevenue = $resellers->sum('revenue_ytd');
        $totalClients = $resellers->sum('child_companies_count');

        return $resellers->map(function (Company $reseller) use ($totalRevenue, $totalClients) {
            $resellerRevenue = $reseller->revenue_ytd ?? 0;
            $resellerClients = $reseller->child_companies_count ?? 0;
            
            return [
                'reseller' => $reseller,
                'revenue_share' => $totalRevenue > 0 ? ($resellerRevenue / $totalRevenue) * 100 : 0,
                'client_share' => $totalClients > 0 ? ($resellerClients / $totalClients) * 100 : 0,
                'performance_score' => $this->calculatePerformanceScore($reseller),
            ];
        })->sortByDesc('performance_score')->values()->toArray();
    }

    /**
     * Calculate performance score (0-100)
     */
    private function calculatePerformanceScore(Company $reseller): float
    {
        $metrics = $this->getResellerMetrics($reseller);
        
        // Weighted scoring
        $revenueScore = min(($metrics['revenue_ytd'] / 10000) * 40, 40); // Max 40 points
        $clientScore = min($metrics['total_clients'] * 5, 30); // Max 30 points
        $retentionScore = ($metrics['client_retention_rate'] / 100) * 20; // Max 20 points
        $growthScore = max(min($metrics['growth_rate'] / 10, 10), 0); // Max 10 points
        
        return round($revenueScore + $clientScore + $retentionScore + $growthScore, 1);
    }
}