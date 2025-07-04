<?php

namespace App\Services;

use App\Models\Company;
use App\Models\PrepaidBalance;
use App\Notifications\LowBalanceWarningNotification;
use Illuminate\Support\Facades\Log;

class BalanceMonitoringService
{
    /**
     * Check all companies for low balance
     */
    public function checkAllCompaniesForLowBalance(): array
    {
        $results = [
            'checked' => 0,
            'warnings_sent' => 0,
            'errors' => 0,
        ];

        // Get all companies with prepaid billing enabled
        $companies = Company::where('prepaid_billing_enabled', true)
                           ->with('prepaidBalance')
                           ->get();

        foreach ($companies as $company) {
            $results['checked']++;
            
            try {
                if ($this->checkAndNotifyLowBalance($company)) {
                    $results['warnings_sent']++;
                }
            } catch (\Exception $e) {
                $results['errors']++;
                Log::error('Failed to check balance for company', [
                    'company_id' => $company->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }

    /**
     * Check if company has low balance and send warning if needed
     */
    public function checkAndNotifyLowBalance(Company $company): bool
    {
        $balance = $company->prepaidBalance;
        
        if (!$balance) {
            return false;
        }

        // Check if balance is low
        if (!$balance->isLowBalance()) {
            return false;
        }

        // Check if we already sent a warning recently
        if ($balance->last_warning_sent_at && 
            $balance->last_warning_sent_at->isAfter(now()->subHours(24))) {
            return false;
        }

        // Send warning notification
        $this->sendLowBalanceWarning($company, $balance);

        // Update last warning timestamp
        $balance->update(['last_warning_sent_at' => now()]);

        return true;
    }

    /**
     * Send low balance warning notification
     */
    protected function sendLowBalanceWarning(Company $company, PrepaidBalance $balance): void
    {
        // Get all portal users who should receive billing notifications
        $recipients = $company->portalUsers()
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereIn('role', ['owner', 'admin'])
                      ->orWhere('permissions', 'like', '%billing.view%');
            })
            ->get();

        if ($recipients->isEmpty()) {
            Log::warning('No recipients found for low balance warning', [
                'company_id' => $company->id,
            ]);
            return;
        }

        // Send notification to each recipient
        foreach ($recipients as $user) {
            try {
                $user->notify(new LowBalanceWarningNotification($balance));
            } catch (\Exception $e) {
                Log::error('Failed to send low balance notification', [
                    'user_id' => $user->id,
                    'company_id' => $company->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Check if company should be blocked due to insufficient balance
     */
    public function shouldBlockCalls(Company $company): bool
    {
        if (!$company->prepaid_billing_enabled) {
            return false;
        }

        $balance = $company->prepaidBalance;
        if (!$balance) {
            return true; // No balance record = no credit
        }

        // Get minimum required balance for one minute
        $billingService = new PrepaidBillingService();
        $minimumRequired = $billingService->getCompanyBillingRate($company)->getMinimumBalanceRequired();

        return $balance->getEffectiveBalance() < $minimumRequired;
    }

    /**
     * Get balance status for display
     */
    public function getBalanceStatus(Company $company): array
    {
        $balance = $company->prepaidBalance;
        
        if (!$balance) {
            return [
                'status' => 'no_balance',
                'balance' => 0.00,
                'effective_balance' => 0.00,
                'reserved_balance' => 0.00,
                'percentage' => 0,
                'is_low' => false,
                'can_make_calls' => false,
                'estimated_minutes' => 0,
            ];
        }

        $billingService = new PrepaidBillingService();
        $rate = $billingService->getCompanyBillingRate($company);
        
        $effectiveBalance = $balance->getEffectiveBalance();
        $estimatedMinutes = $rate->rate_per_minute > 0 
            ? floor($effectiveBalance / $rate->rate_per_minute) 
            : 0;

        return [
            'status' => $this->getStatusLabel($balance),
            'balance' => $balance->balance,
            'effective_balance' => $effectiveBalance,
            'reserved_balance' => $balance->reserved_balance,
            'percentage' => $balance->getBalancePercentage(),
            'is_low' => $balance->isLowBalance(),
            'can_make_calls' => !$this->shouldBlockCalls($company),
            'estimated_minutes' => $estimatedMinutes,
            'last_warning_sent' => $balance->last_warning_sent_at,
        ];
    }

    /**
     * Get status label based on balance
     */
    protected function getStatusLabel(PrepaidBalance $balance): string
    {
        $percentage = $balance->getBalancePercentage();

        if ($percentage <= 0) {
            return 'exhausted';
        } elseif ($percentage <= 20) {
            return 'critical';
        } elseif ($percentage <= 50) {
            return 'low';
        } else {
            return 'good';
        }
    }

    /**
     * Get estimated remaining call time
     */
    public function getEstimatedRemainingTime(Company $company): array
    {
        $balance = $company->prepaidBalance;
        if (!$balance) {
            return ['minutes' => 0, 'seconds' => 0];
        }

        $billingService = new PrepaidBillingService();
        $rate = $billingService->getCompanyBillingRate($company);
        
        if ($rate->rate_per_minute <= 0) {
            return ['minutes' => 0, 'seconds' => 0];
        }

        $effectiveBalance = $balance->getEffectiveBalance();
        $totalSeconds = ($effectiveBalance / $rate->rate_per_minute) * 60;
        
        return [
            'minutes' => floor($totalSeconds / 60),
            'seconds' => $totalSeconds % 60,
        ];
    }
}