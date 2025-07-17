<?php

namespace App\Services;

use App\Models\Company;
use App\Models\PrepaidBalance;
use App\Models\BalanceTopup;
use App\Models\BalanceTransaction;
use App\Services\StripeTopupService;
use App\Services\PrepaidBillingService;
use App\Mail\AutoTopupSuccessful;
use App\Mail\AutoTopupFailed;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class AutoTopupService
{
    protected StripeTopupService $stripeService;
    protected PrepaidBillingService $billingService;

    public function __construct(
        StripeTopupService $stripeService,
        PrepaidBillingService $billingService
    ) {
        $this->stripeService = $stripeService;
        $this->billingService = $billingService;
    }

    /**
     * Check and execute auto-topup if needed
     */
    public function checkAndExecuteAutoTopup(Company $company): ?array
    {
        $balance = $this->billingService->getOrCreateBalance($company);
        
        // Check if auto-topup is enabled
        if (!$balance->auto_topup_enabled) {
            return null;
        }

        // Check if payment method is configured
        if (!$balance->stripe_payment_method_id) {
            Log::warning('Auto-topup enabled but no payment method configured', [
                'company_id' => $company->id
            ]);
            return null;
        }

        // Check if threshold is reached
        $totalBalance = $balance->getEffectiveTotalBalance();
        if ($totalBalance > $balance->auto_topup_threshold) {
            return null;
        }

        // Check daily limit
        if (!$this->checkDailyLimit($balance)) {
            Log::info('Auto-topup daily limit reached', [
                'company_id' => $company->id,
                'daily_count' => $balance->auto_topup_daily_count
            ]);
            return null;
        }

        // Check monthly limit
        if (!$this->checkMonthlyLimit($balance)) {
            Log::info('Auto-topup monthly limit reached', [
                'company_id' => $company->id
            ]);
            return null;
        }

        // Execute auto-topup
        return $this->executeAutoTopup($company, $balance);
    }

    /**
     * Execute auto-topup transaction
     */
    protected function executeAutoTopup(Company $company, PrepaidBalance $balance): array
    {
        try {
            DB::beginTransaction();

            // Create topup record
            $topup = BalanceTopup::create([
                'company_id' => $company->id,
                'amount' => $balance->auto_topup_amount,
                'currency' => 'EUR',
                'status' => 'processing',
                'initiated_by' => null, // System initiated
            ]);

            // Create Stripe payment intent with saved payment method
            $paymentIntent = $this->stripeService->createPaymentIntentWithPaymentMethod(
                $company,
                $balance->auto_topup_amount,
                $balance->stripe_payment_method_id
            );

            if (!$paymentIntent) {
                throw new \Exception('Failed to create payment intent');
            }

            // Update topup with Stripe info
            $topup->update([
                'stripe_payment_intent_id' => $paymentIntent->id,
                'stripe_response' => $paymentIntent->toArray(),
            ]);

            // Confirm payment intent (auto-confirm with saved method)
            $confirmed = $this->stripeService->confirmPaymentIntent($paymentIntent->id);
            
            if ($confirmed && $confirmed->status === 'succeeded') {
                // Process successful payment
                $this->processSuccessfulAutoTopup($company, $balance, $topup, $balance->auto_topup_amount);
                
                DB::commit();
                
                return [
                    'success' => true,
                    'amount' => $balance->auto_topup_amount,
                    'new_balance' => $balance->fresh()->getTotalBalance(),
                    'topup_id' => $topup->id,
                ];
            } else {
                throw new \Exception('Payment confirmation failed');
            }

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Auto-topup failed', [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Update topup status if created
            if (isset($topup)) {
                $topup->update(['status' => 'failed']);
            }

            // Send failure notification
            $this->sendFailureNotification($company, $e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Process successful auto-topup
     */
    protected function processSuccessfulAutoTopup(
        Company $company, 
        PrepaidBalance $balance, 
        BalanceTopup $topup,
        float $amount
    ): void {
        // Update topup status
        $topup->update([
            'status' => 'succeeded',
            'paid_at' => now(),
        ]);

        // Add balance
        $balance->addBalance(
            $amount,
            'Automatische Aufladung',
            'auto_topup',
            $topup->id
        );

        // Apply bonus if applicable
        $bonusAmount = $this->billingService->applyBonusRules($company, $amount, $topup);

        // Update auto-topup tracking
        $balance->update([
            'last_auto_topup_at' => now(),
            'auto_topup_daily_count' => $balance->auto_topup_daily_count + 1,
        ]);

        // Send success notification
        $this->sendSuccessNotification($company, $amount, $bonusAmount);

        Log::info('Auto-topup successful', [
            'company_id' => $company->id,
            'amount' => $amount,
            'bonus' => $bonusAmount,
            'new_balance' => $balance->fresh()->getTotalBalance(),
        ]);
    }

    /**
     * Check daily auto-topup limit
     */
    protected function checkDailyLimit(PrepaidBalance $balance): bool
    {
        // Reset counter if new day
        if ($balance->last_auto_topup_at && 
            $balance->last_auto_topup_at->lt(now()->startOfDay())) {
            $balance->update(['auto_topup_daily_count' => 0]);
        }

        // Default max 2 auto-topups per day
        return $balance->auto_topup_daily_count < 2;
    }

    /**
     * Check monthly auto-topup limit
     */
    protected function checkMonthlyLimit(PrepaidBalance $balance): bool
    {
        // Calculate total auto-topups this month
        $monthlyTotal = BalanceTransaction::where('company_id', $balance->company_id)
            ->where('type', 'topup')
            ->where('reference_type', 'auto_topup')
            ->where('created_at', '>=', now()->startOfMonth())
            ->sum('amount');

        return $monthlyTotal < $balance->auto_topup_monthly_limit;
    }

    /**
     * Send success notification
     */
    protected function sendSuccessNotification(Company $company, float $amount, ?float $bonusAmount): void
    {
        $recipients = $company->portalUsers()
            ->where('is_active', true)
            ->whereHas('permissions', function ($q) {
                $q->where('permission', 'billing.view')
                  ->orWhere('permission', 'billing.pay');
            })
            ->pluck('email')
            ->toArray();

        if (empty($recipients)) {
            return;
        }

        try {
            Mail::to($recipients)->send(new AutoTopupSuccessful(
                $company,
                $amount,
                $bonusAmount,
                $company->prepaidBalance->getTotalBalance()
            ));
        } catch (\Exception $e) {
            Log::error('Failed to send auto-topup success email', [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send failure notification
     */
    protected function sendFailureNotification(Company $company, string $error): void
    {
        $recipients = $company->portalUsers()
            ->where('is_active', true)
            ->whereHas('permissions', function ($q) {
                $q->where('permission', 'billing.view')
                  ->orWhere('permission', 'billing.pay');
            })
            ->pluck('email')
            ->toArray();

        if (empty($recipients)) {
            return;
        }

        try {
            Mail::to($recipients)->send(new AutoTopupFailed(
                $company,
                $error,
                $company->prepaidBalance->getTotalBalance()
            ));
        } catch (\Exception $e) {
            Log::error('Failed to send auto-topup failure email', [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Configure auto-topup settings
     */
    public function configureAutoTopup(
        Company $company,
        bool $enabled,
        ?float $threshold = null,
        ?float $amount = null,
        ?string $paymentMethodId = null
    ): PrepaidBalance {
        $balance = $this->billingService->getOrCreateBalance($company);
        
        $updateData = ['auto_topup_enabled' => $enabled];
        
        if ($threshold !== null) {
            $updateData['auto_topup_threshold'] = $threshold;
        }
        
        if ($amount !== null) {
            // Validate amount is greater than threshold
            if ($threshold !== null && $amount <= $threshold) {
                throw new \InvalidArgumentException(
                    'Auto-topup amount must be greater than threshold'
                );
            }
            $updateData['auto_topup_amount'] = $amount;
        }
        
        if ($paymentMethodId !== null) {
            // Validate payment method belongs to company's Stripe customer
            if (!$this->stripeService->validatePaymentMethod($company, $paymentMethodId)) {
                throw new \InvalidArgumentException('Invalid payment method');
            }
            $updateData['stripe_payment_method_id'] = $paymentMethodId;
        }
        
        $balance->update($updateData);
        
        Log::info('Auto-topup configuration updated', [
            'company_id' => $company->id,
            'settings' => $updateData,
        ]);
        
        return $balance->fresh();
    }

    /**
     * Get auto-topup history
     */
    public function getAutoTopupHistory(Company $company, int $limit = 50): array
    {
        return BalanceTransaction::where('company_id', $company->id)
            ->where('reference_type', 'auto_topup')
            ->with(['createdBy'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($transaction) {
                $topup = BalanceTopup::find($transaction->reference_id);
                return [
                    'id' => $transaction->id,
                    'amount' => abs($transaction->amount),
                    'bonus_amount' => $transaction->bonus_amount,
                    'created_at' => $transaction->created_at,
                    'status' => $topup?->status ?? 'unknown',
                    'payment_method' => $topup?->stripe_response['payment_method'] ?? null,
                ];
            })
            ->toArray();
    }
}