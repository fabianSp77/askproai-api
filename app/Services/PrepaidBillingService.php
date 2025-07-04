<?php

namespace App\Services;

use App\Models\Call;
use App\Models\Company;
use App\Models\PrepaidBalance;
use App\Models\BillingRate;
use App\Models\CallCharge;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PrepaidBillingService
{
    /**
     * Calculate charge for a call
     */
    public function calculateCallCharge(Call $call): float
    {
        if (!$call->duration_sec || $call->duration_sec <= 0) {
            return 0.0;
        }

        $company = $call->company;
        if (!$company) {
            throw new \Exception('Call has no associated company');
        }

        $billingRate = $this->getCompanyBillingRate($company);
        return $billingRate->calculateCharge($call->duration_sec);
    }

    /**
     * Charge a completed call
     */
    public function chargeCall(Call $call): ?CallCharge
    {
        // Use the static method from CallCharge model
        return CallCharge::chargeCall($call);
    }

    /**
     * Reserve balance for an ongoing call
     */
    public function reserveBalanceForCall(Company $company, float $estimatedMinutes = 5.0): bool
    {
        $balance = $this->getOrCreateBalance($company);
        $billingRate = $this->getCompanyBillingRate($company);
        
        // Calculate estimated charge
        $estimatedCharge = $estimatedMinutes * $billingRate->rate_per_minute;
        
        return $balance->reserveBalance($estimatedCharge);
    }

    /**
     * Release reserved balance after call
     */
    public function releaseReservedBalance(Company $company, float $amount): void
    {
        $balance = $this->getOrCreateBalance($company);
        $balance->releaseReservedBalance($amount);
    }

    /**
     * Get effective balance (balance - reserved)
     */
    public function getEffectiveBalance(Company $company): float
    {
        $balance = $this->getOrCreateBalance($company);
        return $balance->getEffectiveBalance();
    }

    /**
     * Check if company has sufficient balance for a call
     */
    public function hasSufficientBalance(Company $company): bool
    {
        $balance = $this->getOrCreateBalance($company);
        $billingRate = $this->getCompanyBillingRate($company);
        
        // Need at least balance for minimum charge or 1 minute
        $minimumRequired = $billingRate->getMinimumBalanceRequired();
        
        return $balance->getEffectiveBalance() >= $minimumRequired;
    }

    /**
     * Get or create prepaid balance for company
     */
    public function getOrCreateBalance(Company $company): PrepaidBalance
    {
        return PrepaidBalance::firstOrCreate(
            ['company_id' => $company->id],
            [
                'balance' => 0.00,
                'reserved_balance' => 0.00,
                'low_balance_threshold' => 20.00, // 20% as default
            ]
        );
    }

    /**
     * Get company billing rate
     */
    public function getCompanyBillingRate(Company $company): BillingRate
    {
        $rate = BillingRate::where('company_id', $company->id)
                          ->active()
                          ->first();
        
        if (!$rate) {
            $rate = BillingRate::createDefaultForCompany($company);
        }
        
        return $rate;
    }

    /**
     * Process all uncharged calls for a company
     */
    public function processUnchargedCalls(Company $company): array
    {
        $results = [
            'processed' => 0,
            'failed' => 0,
            'skipped' => 0,
            'total_charged' => 0.0,
        ];

        // Get all uncharged calls
        $unchargedCalls = Call::where('company_id', $company->id)
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                      ->from('call_charges')
                      ->whereColumn('call_charges.call_id', 'calls.id');
            })
            ->where('duration_sec', '>', 0)
            ->where('created_at', '>=', now()->subDays(30)) // Only last 30 days
            ->get();

        foreach ($unchargedCalls as $call) {
            try {
                $charge = $this->chargeCall($call);
                if ($charge) {
                    $results['processed']++;
                    $results['total_charged'] += $charge->amount_charged;
                } else {
                    $results['skipped']++;
                }
            } catch (\Exception $e) {
                $results['failed']++;
                Log::error('Failed to charge call', [
                    'call_id' => $call->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }

    /**
     * Get usage statistics for a period
     */
    public function getUsageStatistics(Company $company, \DateTime $startDate, \DateTime $endDate): array
    {
        $charges = CallCharge::where('company_id', $company->id)
            ->whereBetween('charged_at', [$startDate, $endDate])
            ->get();

        return [
            'total_calls' => $charges->count(),
            'total_duration_seconds' => $charges->sum('duration_seconds'),
            'total_duration_minutes' => round($charges->sum('duration_seconds') / 60, 2),
            'total_charged' => $charges->sum('amount_charged'),
            'average_call_duration' => $charges->avg('duration_seconds'),
            'average_call_cost' => $charges->avg('amount_charged'),
            'daily_breakdown' => $this->getDailyBreakdown($charges),
        ];
    }

    /**
     * Get daily breakdown of charges
     */
    private function getDailyBreakdown($charges): array
    {
        return $charges->groupBy(function ($charge) {
            return $charge->charged_at->format('Y-m-d');
        })->map(function ($dayCharges) {
            return [
                'calls' => $dayCharges->count(),
                'duration_minutes' => round($dayCharges->sum('duration_seconds') / 60, 2),
                'total_charged' => $dayCharges->sum('amount_charged'),
            ];
        })->toArray();
    }

    /**
     * Estimate monthly cost based on recent usage
     */
    public function estimateMonthlyCost(Company $company): float
    {
        // Get average daily usage from last 30 days
        $avgDailyUsage = CallCharge::where('company_id', $company->id)
            ->where('charged_at', '>=', now()->subDays(30))
            ->groupBy(DB::raw('DATE(charged_at)'))
            ->selectRaw('AVG(sum_amount) as avg_daily')
            ->selectRaw('SUM(amount_charged) as sum_amount')
            ->value('avg_daily') ?? 0;

        return $avgDailyUsage * 30;
    }
}