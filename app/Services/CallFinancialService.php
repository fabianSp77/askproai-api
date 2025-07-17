<?php

namespace App\Services;

use App\Models\Call;
use App\Models\BillingRate;
use App\Services\ExchangeRateService;
use Illuminate\Support\Facades\Cache;

class CallFinancialService
{
    /**
     * Calculate all financial metrics for a call
     */
    public function calculateMetrics(Call $call): array
    {
        // Get call cost in EUR
        $callCostEUR = $this->getCallCost($call);
        
        // Get revenue
        $revenue = $this->getRevenue($call);
        
        // Calculate profit and margin
        $profit = $revenue - $callCostEUR;
        $margin = $revenue > 0 ? ($profit / $revenue * 100) : 0;
        
        // Get company averages for comparison
        $averages = $this->getCompanyAverages($call->company_id);
        
        // Calculate trend
        $trend = 'neutral';
        $trendPercent = 0;
        if ($averages['avgProfit'] > 0) {
            $trendPercent = (($profit - $averages['avgProfit']) / $averages['avgProfit']) * 100;
            $trend = $trendPercent > 5 ? 'up' : ($trendPercent < -5 ? 'down' : 'neutral');
        }
        
        // Calculate ROI
        $roi = $callCostEUR > 0 ? (($profit / $callCostEUR) * 100) : 0;
        
        return [
            'cost' => round($callCostEUR, 2),
            'revenue' => round($revenue, 2),
            'profit' => round($profit, 2),
            'margin' => round($margin, 1),
            'marginPercent' => min(100, max(0, round($margin))),
            'ratePerMinute' => $this->getRatePerMinute($call),
            'trend' => $trend,
            'trendPercent' => round(abs($trendPercent), 1),
            'roi' => round($roi, 0),
            'avgDuration' => round($averages['avgDuration']),
        ];
    }
    
    /**
     * Get call cost in EUR
     */
    protected function getCallCost(Call $call): float
    {
        $callCostCents = 0;
        
        if (isset($call->webhook_data['call_cost'])) {
            if (is_array($call->webhook_data['call_cost'])) {
                $callCostCents = $call->webhook_data['call_cost']['combined_cost'] ?? 
                                $call->webhook_data['call_cost']['total_cost'] ?? 
                                $call->webhook_data['call_cost']['total'] ?? 0;
            } else {
                $callCostCents = floatval($call->webhook_data['call_cost']);
            }
        }
        
        return ExchangeRateService::convertCentsToEur($callCostCents);
    }
    
    /**
     * Get revenue for the call
     */
    protected function getRevenue(Call $call): float
    {
        // Check if company and billingRate are already loaded
        if ($call->relationLoaded('company') && $call->company && $call->company->relationLoaded('billingRate')) {
            $billingRate = $call->company->billingRate;
        } else {
            // Load billing rate directly without going through company relation
            $billingRate = BillingRate::where('company_id', $call->company_id)->first();
        }
        
        if ($billingRate) {
            return $billingRate->calculateCharge($call->duration_sec);
        }
        
        $defaultRate = BillingRate::getDefaultRate();
        return ($call->duration_sec / 60) * $defaultRate;
    }
    
    /**
     * Get rate per minute
     */
    protected function getRatePerMinute(Call $call): float
    {
        // Check if company and billingRate are already loaded
        if ($call->relationLoaded('company') && $call->company && $call->company->relationLoaded('billingRate')) {
            $billingRate = $call->company->billingRate;
        } else {
            // Load billing rate directly without going through company relation
            $billingRate = BillingRate::where('company_id', $call->company_id)->first();
        }
        
        return $billingRate ? $billingRate->rate_per_minute : BillingRate::getDefaultRate();
    }
    
    /**
     * Get company averages for comparison
     */
    protected function getCompanyAverages(int $companyId): array
    {
        return Cache::remember("company_averages_{$companyId}", 3600, function () use ($companyId) {
            // Get the billing rate for this company once
            $billingRate = BillingRate::where('company_id', $companyId)->first();
            $defaultRate = BillingRate::getDefaultRate();
            $ratePerMinute = $billingRate ? $billingRate->rate_per_minute : $defaultRate;
            
            // Get aggregated call statistics
            $stats = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
                ->where('company_id', $companyId)
                ->where('duration_sec', '>', 0)
                ->whereDate('created_at', '>=', now()->subDays(30))
                ->selectRaw('
                    COUNT(*) as count,
                    AVG(duration_sec) as avg_duration,
                    SUM(duration_sec) as total_duration
                ')
                ->first();
            
            if (!$stats || $stats->count == 0) {
                return [
                    'avgDuration' => 0,
                    'avgCost' => 0,
                    'avgRevenue' => 0,
                    'avgProfit' => 0,
                ];
            }
            
            // Calculate average cost per call
            $avgDurationMinutes = $stats->avg_duration / 60;
            $avgRevenue = $avgDurationMinutes * $ratePerMinute;
            
            // For cost, we need to get a sample of recent calls to estimate
            $sampleCalls = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
                ->where('company_id', $companyId)
                ->where('duration_sec', '>', 0)
                ->whereDate('created_at', '>=', now()->subDays(30))
                ->whereNotNull('webhook_data->call_cost')
                ->limit(10)
                ->get();
            
            $avgCost = 0;
            if ($sampleCalls->isNotEmpty()) {
                $totalCost = 0;
                foreach ($sampleCalls as $call) {
                    $totalCost += $this->getCallCost($call);
                }
                $avgCost = $totalCost / $sampleCalls->count();
            }
            
            return [
                'avgDuration' => $stats->avg_duration,
                'avgCost' => $avgCost,
                'avgRevenue' => $avgRevenue,
                'avgProfit' => $avgRevenue - $avgCost,
            ];
        });
    }
}