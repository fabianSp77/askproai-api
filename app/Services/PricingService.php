<?php

namespace App\Services;

use App\Models\Call;
use App\Models\Company;
use App\Models\Branch;
use App\Models\CompanyPricing;
use App\Models\BranchPricingOverride;
use App\Models\BillingPeriod;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PricingService
{
    /**
     * Calculate the customer price for a call
     */
    public function calculateCallPrice(Call $call): array
    {
        try {
            Log::info('PricingService: Calculating price for call', [
                'call_id' => $call->id,
                'company_id' => $call->company_id,
                'branch_id' => $call->branch_id,
                'duration_sec' => $call->duration_sec,
            ]);
            
            // Get the pricing model for this call
            $pricing = $this->getPricingForCall($call);
            
            if (!$pricing) {
                Log::warning('PricingService: No pricing model found for call', [
                    'call_id' => $call->id,
                    'company_id' => $call->company_id,
                    'branch_id' => $call->branch_id,
                ]);
                
                return [
                    'customer_price' => 0,
                    'price_per_minute' => 0,
                    'pricing_model_id' => null,
                    'error' => 'Kein Preismodell gefunden',
                ];
            }
            
            // Get current month usage
            $currentMonthMinutes = $this->getCurrentMonthMinutes($call->company_id, $call->branch_id);
            
            Log::debug('PricingService: Current month usage', [
                'company_id' => $call->company_id,
                'branch_id' => $call->branch_id,
                'current_month_minutes' => $currentMonthMinutes,
                'included_minutes' => $pricing->included_minutes,
            ]);
            
            // Calculate price
            $price = $pricing->calculatePrice($call->duration_sec, $currentMonthMinutes);
            
            $result = [
                'customer_price' => $price,
                'price_per_minute' => $pricing->price_per_minute,
                'pricing_model_id' => $pricing->id,
                'included_minutes' => $pricing->included_minutes,
                'current_month_minutes' => $currentMonthMinutes,
                'minutes_used' => $call->duration_sec / 60,
            ];
            
            Log::info('PricingService: Price calculated successfully', [
                'call_id' => $call->id,
                'customer_price' => $price,
                'minutes_used' => $call->duration_sec / 60,
            ]);
            
            return $result;
            
        } catch (\Exception $e) {
            Log::error('PricingService: Error calculating call price', [
                'call_id' => $call->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return [
                'customer_price' => 0,
                'price_per_minute' => 0,
                'pricing_model_id' => null,
                'error' => 'Fehler bei der Preisberechnung: ' . $e->getMessage(),
            ];
        }
    }
    
    /**
     * Get the applicable pricing model for a call
     */
    protected function getPricingForCall(Call $call)
    {
        try {
            // First check for branch-specific override
            if ($call->branch_id) {
                Log::debug('PricingService: Checking for branch-specific pricing', [
                    'branch_id' => $call->branch_id
                ]);
                
                $branchOverride = BranchPricingOverride::where('branch_id', $call->branch_id)
                    ->where('is_active', true)
                    ->first();
                    
                if ($branchOverride && $branchOverride->price_per_minute) {
                    Log::info('PricingService: Using branch-specific pricing', [
                        'branch_id' => $call->branch_id,
                        'override_id' => $branchOverride->id,
                        'price_per_minute' => $branchOverride->price_per_minute
                    ]);
                    
                    // Create a virtual pricing object with branch override values
                    $pricing = new CompanyPricing();
                    $pricing->price_per_minute = $branchOverride->price_per_minute;
                    $pricing->included_minutes = $branchOverride->included_minutes ?? 0;
                    $pricing->overage_price_per_minute = $branchOverride->price_per_minute;
                    $pricing->id = 'branch_override_' . $branchOverride->id;
                    
                    return $pricing;
                }
            }
            
            // Get company pricing
            $companyPricing = CompanyPricing::getCurrentForCompany($call->company_id);
            
            if ($companyPricing) {
                Log::debug('PricingService: Using company pricing', [
                    'company_id' => $call->company_id,
                    'pricing_id' => $companyPricing->id,
                    'price_per_minute' => $companyPricing->price_per_minute
                ]);
            }
            
            return $companyPricing;
            
        } catch (\Exception $e) {
            Log::error('PricingService: Error getting pricing for call', [
                'call_id' => $call->id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
    
    /**
     * Get total minutes used in current month
     */
    public function getCurrentMonthMinutes($companyId, $branchId = null): float
    {
        try {
            $query = Call::where('company_id', $companyId)
                ->whereMonth('created_at', Carbon::now()->month)
                ->whereYear('created_at', Carbon::now()->year);
                
            if ($branchId) {
                $query->where('branch_id', $branchId);
            }
            
            $totalSeconds = $query->sum('duration_sec');
            $totalMinutes = $totalSeconds / 60;
            
            Log::debug('PricingService: Calculated current month minutes', [
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'total_seconds' => $totalSeconds,
                'total_minutes' => $totalMinutes,
                'month' => Carbon::now()->format('Y-m')
            ]);
            
            return $totalMinutes;
            
        } catch (\Exception $e) {
            Log::error('PricingService: Error calculating current month minutes', [
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }
    
    /**
     * Calculate billing for a period
     */
    public function calculateBillingPeriod($companyId, Carbon $startDate, Carbon $endDate)
    {
        $company = Company::findOrFail($companyId);
        
        // Get all calls in period
        $calls = Call::where('company_id', $companyId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();
            
        $totalCost = 0;
        $totalRevenue = 0;
        $totalMinutes = 0;
        
        foreach ($calls as $call) {
            // Our cost
            $totalCost += $call->cost ?? 0;
            
            // Customer price
            $pricing = $this->calculateCallPrice($call);
            $totalRevenue += $pricing['customer_price'];
            
            $totalMinutes += $call->duration_sec / 60;
        }
        
        // Get pricing model for monthly fees
        $pricing = CompanyPricing::getCurrentForCompany($companyId);
        if ($pricing && $pricing->monthly_base_fee) {
            $totalRevenue += $pricing->monthly_base_fee;
        }
        
        $margin = $totalRevenue - $totalCost;
        $marginPercentage = $totalRevenue > 0 ? ($margin / $totalRevenue) * 100 : 0;
        
        return [
            'company_id' => $companyId,
            'period_start' => $startDate,
            'period_end' => $endDate,
            'total_minutes' => round($totalMinutes, 2),
            'included_minutes' => $pricing->included_minutes ?? 0,
            'overage_minutes' => max(0, $totalMinutes - ($pricing->included_minutes ?? 0)),
            'total_cost' => round($totalCost, 2),
            'total_revenue' => round($totalRevenue, 2),
            'margin' => round($margin, 2),
            'margin_percentage' => round($marginPercentage, 2),
            'call_count' => $calls->count(),
        ];
    }
    
    /**
     * Create or update billing period record
     */
    public function saveBillingPeriod(array $billingData): BillingPeriod
    {
        return BillingPeriod::updateOrCreate(
            [
                'company_id' => $billingData['company_id'],
                'period_start' => $billingData['period_start'],
                'period_end' => $billingData['period_end'],
            ],
            $billingData
        );
    }
}