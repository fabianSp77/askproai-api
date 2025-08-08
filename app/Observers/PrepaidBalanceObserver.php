<?php

namespace App\Observers;

use App\Models\PrepaidBalance;
use App\Services\CostTrackingAlertService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class PrepaidBalanceObserver
{
    protected CostTrackingAlertService $costTrackingService;

    public function __construct(CostTrackingAlertService $costTrackingService)
    {
        $this->costTrackingService = $costTrackingService;
    }

    /**
     * Handle the PrepaidBalance "created" event.
     */
    public function created(PrepaidBalance $prepaidBalance): void
    {
        Log::info('New prepaid balance created', [
            'company_id' => $prepaidBalance->company_id,
            'balance' => $prepaidBalance->balance,
            'low_threshold' => $prepaidBalance->low_balance_threshold
        ]);

        // Clear cache for this company
        $this->clearRelatedCache($prepaidBalance);
    }

    /**
     * Handle the PrepaidBalance "updated" event.
     */
    public function updated(PrepaidBalance $prepaidBalance): void
    {
        try {
            // Check what fields have changed
            $changes = $prepaidBalance->getChanges();
            $original = $prepaidBalance->getOriginal();
            
            Log::info('Prepaid balance updated', [
                'company_id' => $prepaidBalance->company_id,
                'changes' => $changes,
                'current_balance' => $prepaidBalance->getEffectiveBalance()
            ]);

            // Clear cache first
            $this->clearRelatedCache($prepaidBalance);

            // Check for significant balance changes that require immediate alerts
            if (array_key_exists('balance', $changes) || 
                array_key_exists('bonus_balance', $changes) || 
                array_key_exists('reserved_balance', $changes)) {
                
                $this->handleBalanceChange($prepaidBalance, $original);
            }

            // Check for threshold changes
            if (array_key_exists('low_balance_threshold', $changes)) {
                $this->handleThresholdChange($prepaidBalance, $original);
            }

            // Check for auto-topup configuration changes
            if (array_key_exists('auto_topup_enabled', $changes) || 
                array_key_exists('auto_topup_threshold', $changes) ||
                array_key_exists('auto_topup_amount', $changes)) {
                
                $this->handleAutoTopupConfigChange($prepaidBalance, $changes);
            }

        } catch (\Exception $e) {
            Log::error('Error in PrepaidBalanceObserver::updated', [
                'company_id' => $prepaidBalance->company_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Handle the PrepaidBalance "deleted" event.
     */
    public function deleted(PrepaidBalance $prepaidBalance): void
    {
        Log::warning('Prepaid balance deleted', [
            'company_id' => $prepaidBalance->company_id,
            'final_balance' => $prepaidBalance->getEffectiveBalance()
        ]);

        // Clear cache
        $this->clearRelatedCache($prepaidBalance);
    }

    /**
     * Handle balance changes and trigger alerts if necessary
     */
    protected function handleBalanceChange(PrepaidBalance $prepaidBalance, array $original): void
    {
        $currentBalance = $prepaidBalance->getEffectiveBalance();
        $originalBalance = ($original['balance'] ?? 0) + ($original['bonus_balance'] ?? 0) - ($original['reserved_balance'] ?? 0);
        
        $balanceChange = $currentBalance - $originalBalance;
        $changePercentage = $originalBalance > 0 ? ($balanceChange / $originalBalance) * 100 : 0;

        Log::info('Balance change detected', [
            'company_id' => $prepaidBalance->company_id,
            'original_balance' => $originalBalance,
            'current_balance' => $currentBalance,
            'change_amount' => $balanceChange,
            'change_percentage' => round($changePercentage, 2)
        ]);

        // Trigger immediate alert check if balance decreased significantly or reached zero
        if ($balanceChange < 0) {
            // Balance decreased
            if ($currentBalance <= 0) {
                // Zero balance - trigger immediate alert
                $this->triggerImmediateAlertCheck($prepaidBalance, 'zero_balance');
            } elseif ($prepaidBalance->isLowBalance()) {
                // Low balance - trigger immediate alert
                $this->triggerImmediateAlertCheck($prepaidBalance, 'low_balance');
            } elseif (abs($changePercentage) >= 20) {
                // Significant balance drop (20% or more)
                $this->triggerImmediateAlertCheck($prepaidBalance, 'significant_decrease');
            }
        }

        // Check for potential cost anomalies based on recent balance changes
        if ($balanceChange < -10) { // Lost more than €10
            $this->checkForCostAnomaly($prepaidBalance, abs($balanceChange));
        }
    }

    /**
     * Handle threshold changes
     */
    protected function handleThresholdChange(PrepaidBalance $prepaidBalance, array $original): void
    {
        $oldThreshold = $original['low_balance_threshold'] ?? 0;
        $newThreshold = $prepaidBalance->low_balance_threshold;

        Log::info('Low balance threshold changed', [
            'company_id' => $prepaidBalance->company_id,
            'old_threshold' => $oldThreshold,
            'new_threshold' => $newThreshold,
            'current_balance' => $prepaidBalance->getEffectiveBalance()
        ]);

        // If new threshold is higher and current balance is now below it, trigger alert
        if ($newThreshold > $oldThreshold && $prepaidBalance->isLowBalance()) {
            $this->triggerImmediateAlertCheck($prepaidBalance, 'threshold_increased');
        }
    }

    /**
     * Handle auto-topup configuration changes
     */
    protected function handleAutoTopupConfigChange(PrepaidBalance $prepaidBalance, array $changes): void
    {
        Log::info('Auto-topup configuration changed', [
            'company_id' => $prepaidBalance->company_id,
            'changes' => $changes,
            'should_auto_topup' => $prepaidBalance->shouldAutoTopup()
        ]);

        // If auto-topup was just enabled and should trigger, log it
        if (isset($changes['auto_topup_enabled']) && $changes['auto_topup_enabled'] && $prepaidBalance->shouldAutoTopup()) {
            Log::info('Auto-topup enabled and should trigger', [
                'company_id' => $prepaidBalance->company_id,
                'current_balance' => $prepaidBalance->getEffectiveBalance(),
                'auto_topup_threshold' => $prepaidBalance->auto_topup_threshold
            ]);
        }
    }

    /**
     * Trigger immediate alert check for specific scenarios
     */
    protected function triggerImmediateAlertCheck(PrepaidBalance $prepaidBalance, string $reason): void
    {
        try {
            Log::info('Triggering immediate alert check', [
                'company_id' => $prepaidBalance->company_id,
                'reason' => $reason,
                'current_balance' => $prepaidBalance->getEffectiveBalance()
            ]);

            // Load the company with necessary relationships
            $company = $prepaidBalance->company()->with('billingAlertConfigs')->first();
            
            if (!$company) {
                Log::error('Company not found for prepaid balance', [
                    'prepaid_balance_id' => $prepaidBalance->id,
                    'company_id' => $prepaidBalance->company_id
                ]);
                return;
            }

            // Check cost alerts for this specific company
            $results = $this->costTrackingService->checkCompanyCostAlerts($company);
            
            Log::info('Immediate alert check completed', [
                'company_id' => $company->id,
                'reason' => $reason,
                'results' => $results
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to trigger immediate alert check', [
                'company_id' => $prepaidBalance->company_id,
                'reason' => $reason,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Check for cost anomaly based on balance changes
     */
    protected function checkForCostAnomaly(PrepaidBalance $prepaidBalance, float $amountSpent): void
    {
        try {
            $cacheKey = "cost_anomaly_check:{$prepaidBalance->company_id}";
            
            // Prevent too frequent checks (max once per hour)
            if (Cache::has($cacheKey)) {
                return;
            }
            
            Cache::put($cacheKey, true, 3600); // 1 hour
            
            Log::info('Checking for cost anomaly due to significant balance decrease', [
                'company_id' => $prepaidBalance->company_id,
                'amount_spent' => $amountSpent,
                'current_balance' => $prepaidBalance->getEffectiveBalance()
            ]);

            // Load company and check for anomalies
            $company = $prepaidBalance->company()->with('billingAlertConfigs')->first();
            
            if ($company) {
                // Get recent usage data to check for anomalies
                $usageData = $this->costTrackingService->getCachedUsageData($company);
                
                // If current hour cost is unusually high, it might be an anomaly
                if (isset($usageData['current_hour_cost']) && 
                    isset($usageData['average_hourly_cost']) && 
                    $usageData['average_hourly_cost'] > 0) {
                    
                    $multiplier = $usageData['current_hour_cost'] / $usageData['average_hourly_cost'];
                    
                    if ($multiplier >= 3.0) { // 3x average
                        Log::warning('Potential cost anomaly detected via balance observer', [
                            'company_id' => $company->id,
                            'current_hour_cost' => $usageData['current_hour_cost'],
                            'average_hourly_cost' => $usageData['average_hourly_cost'],
                            'multiplier' => round($multiplier, 2)
                        ]);
                    }
                }
            }

        } catch (\Exception $e) {
            Log::error('Error checking for cost anomaly', [
                'company_id' => $prepaidBalance->company_id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Clear related cache entries
     */
    protected function clearRelatedCache(PrepaidBalance $prepaidBalance): void
    {
        try {
            // Clear usage data cache for this company
            $this->costTrackingService->clearUsageDataCache($prepaidBalance->company);
            
            // Clear other related caches
            Cache::forget("prepaid_balance:{$prepaidBalance->company_id}");
            Cache::forget("company_balance_status:{$prepaidBalance->company_id}");
            
            // Clear dashboard cache if exists
            Cache::forget("cost_tracking_dashboard:{$prepaidBalance->company_id}");
            
        } catch (\Exception $e) {
            Log::error('Error clearing related cache', [
                'company_id' => $prepaidBalance->company_id,
                'error' => $e->getMessage()
            ]);
        }
    }
}