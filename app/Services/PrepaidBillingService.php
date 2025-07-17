<?php

namespace App\Services;

use App\Models\Call;
use App\Models\Company;
use App\Models\PrepaidBalance;
use App\Models\BillingRate;
use App\Models\CallCharge;
use App\Models\BillingBonusRule;
use App\Models\BalanceTransaction;
use App\Models\BalanceTopup;
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

    /**
     * Apply bonus rules to a topup
     */
    public function applyBonusRules(Company $company, float $topupAmount, BalanceTopup $topup = null): ?float
    {
        // Check if this is the first topup
        $isFirstTopup = !BalanceTransaction::where('company_id', $company->id)
            ->where('type', 'topup')
            ->where('amount', '>', 0)
            ->exists();

        // Get applicable bonus rules
        $bonusRule = BillingBonusRule::forCompany($company->id)
            ->active()
            ->valid()
            ->applicableForAmount($topupAmount)
            ->orderBy('priority', 'desc')
            ->orderBy('bonus_percentage', 'desc')
            ->first();

        if (!$bonusRule || !$bonusRule->isApplicable($topupAmount, $isFirstTopup)) {
            return null;
        }

        // Calculate bonus
        $bonusAmount = $bonusRule->calculateBonus($topupAmount);
        
        if ($bonusAmount <= 0) {
            return null;
        }

        // Apply bonus
        $balance = $this->getOrCreateBalance($company);
        $balance->addBonusBalance(
            $bonusAmount,
            sprintf('Bonus %s%% für Aufladung von %.2f€', $bonusRule->bonus_percentage, $topupAmount),
            'topup_bonus',
            $topup?->id
        );

        // Record usage
        $bonusRule->recordUsage($bonusAmount);

        Log::info('Bonus applied', [
            'company_id' => $company->id,
            'topup_amount' => $topupAmount,
            'bonus_amount' => $bonusAmount,
            'bonus_rule_id' => $bonusRule->id,
            'is_first_topup' => $isFirstTopup,
        ]);

        return $bonusAmount;
    }

    /**
     * Process withdrawal with bonus handling
     */
    public function processWithdrawal(Company $company, float $amount): array
    {
        $balance = $this->getOrCreateBalance($company);
        
        // Check if enough withdrawable balance
        $withdrawableBalance = $balance->getWithdrawableBalance();
        if ($withdrawableBalance < $amount) {
            throw new \Exception(sprintf(
                'Insufficient withdrawable balance. Available: %.2f€, Requested: %.2f€',
                $withdrawableBalance,
                $amount
            ));
        }

        // Process withdrawal
        DB::transaction(function () use ($balance, $amount) {
            $balance->lockForUpdate();
            
            // Only deduct from normal balance
            $balance->decrement('balance', $amount);
            
            // Create transaction
            BalanceTransaction::create([
                'company_id' => $balance->company_id,
                'type' => 'withdrawal',
                'amount' => -$amount,
                'balance_before' => $balance->balance + $amount,
                'balance_after' => $balance->balance,
                'description' => 'Guthaben-Auszahlung',
                'created_by' => auth()->guard('portal')->user()->id ?? null,
            ]);
        });

        return [
            'withdrawn' => $amount,
            'remaining_balance' => $balance->fresh()->balance,
            'remaining_bonus' => $balance->fresh()->bonus_balance,
        ];
    }

    /**
     * Get bonus rules for display
     */
    public function getApplicableBonusRules(Company $company): array
    {
        return BillingBonusRule::forCompany($company->id)
            ->active()
            ->valid()
            ->orderBy('min_amount')
            ->get()
            ->map(function ($rule) {
                return [
                    'name' => $rule->name,
                    'min_amount' => $rule->min_amount,
                    'max_amount' => $rule->max_amount,
                    'bonus_percentage' => $rule->bonus_percentage,
                    'max_bonus_amount' => $rule->max_bonus_amount,
                    'is_first_time_only' => $rule->is_first_time_only,
                    'description' => $rule->description ?? sprintf(
                        'Erhalte %s%% Bonus bei Aufladungen ab %.2f€',
                        $rule->bonus_percentage,
                        $rule->min_amount
                    ),
                ];
            })
            ->toArray();
    }
    
    /**
     * Calculate bonus for amount
     */
    public function calculateBonus(float $amount, Company $company): array
    {
        // Check if this is first topup
        $isFirstTopup = !BalanceTransaction::where('company_id', $company->id)
            ->where('type', 'topup')
            ->exists();
            
        // Hardcoded bonus structure for now
        $bonusPercentage = 0;
        if ($amount >= 5000) {
            $bonusPercentage = 50;
        } elseif ($amount >= 3000) {
            $bonusPercentage = 40;
        } elseif ($amount >= 2000) {
            $bonusPercentage = 30;
        } elseif ($amount >= 1000) {
            $bonusPercentage = 20;
        } elseif ($amount >= 500) {
            $bonusPercentage = 15;
        } elseif ($amount >= 250) {
            $bonusPercentage = 10;
        }
        
        $bonusAmount = $amount * ($bonusPercentage / 100);
        
        // Create a mock rule object for compatibility
        $mockRule = new \stdClass();
        $mockRule->bonus_percentage = $bonusPercentage;
        $mockRule->id = 'hardcoded';
        
        // Try to get from database first
        $rules = BillingBonusRule::forCompany($company->id)
            ->active()
            ->valid()
            ->where('min_amount', '<=', $amount)
            ->where(function ($query) use ($amount) {
                $query->whereNull('max_amount')
                    ->orWhere('max_amount', '>=', $amount);
            })
            ->when(!$isFirstTopup, function ($query) {
                $query->where('is_first_time_only', false);
            })
            ->orderBy('priority', 'desc')
            ->orderBy('bonus_percentage', 'desc')
            ->get();
            
        // Find best bonus from database
        $bestBonus = $bonusAmount;
        $bestRule = $mockRule;
        
        foreach ($rules as $rule) {
            $dbBonusAmount = $amount * ($rule->bonus_percentage / 100);
            
            // Apply max bonus cap if set
            if ($rule->max_bonus_amount && $dbBonusAmount > $rule->max_bonus_amount) {
                $dbBonusAmount = $rule->max_bonus_amount;
            }
            
            if ($dbBonusAmount > $bestBonus) {
                $bestBonus = $dbBonusAmount;
                $bestRule = $rule;
            }
        }
        
        return [
            'rule' => $bestRule,
            'bonus_amount' => $bestBonus,
            'is_first_topup' => $isFirstTopup,
        ];
    }}