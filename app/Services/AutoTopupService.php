<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\BalanceTopup;
use App\Models\Transaction;
use App\Notifications\AutoTopupProcessed;
use App\Notifications\LowBalanceWarning;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class AutoTopupService
{
    private StripeCheckoutService $stripeService;
    private int $minimumBalance = 500; // 5€ minimum before warning
    private int $cooldownMinutes = 60; // Prevent multiple topups within an hour
    
    public function __construct()
    {
        $this->stripeService = new StripeCheckoutService();
    }
    
    /**
     * Monitor all tenants for auto-topup triggers
     * This should be run via scheduled job every 5 minutes
     */
    public function monitorAllTenants(): array
    {
        $results = [];
        
        // Get tenants with auto-topup enabled
        $tenants = Tenant::whereJsonContains('settings->auto_topup_enabled', true)
            ->where('tenant_type', '!=', 'platform')
            ->get();
        
        foreach ($tenants as $tenant) {
            try {
                $result = $this->checkAndProcessTopup($tenant);
                $results[] = $result;
            } catch (\Exception $e) {
                Log::error("Auto-topup check failed for tenant {$tenant->id}", [
                    'error' => $e->getMessage()
                ]);
                $results[] = [
                    'tenant_id' => $tenant->id,
                    'status' => 'error',
                    'message' => $e->getMessage()
                ];
            }
        }
        
        return $results;
    }
    
    /**
     * Check if tenant needs auto-topup and process if needed
     */
    public function checkAndProcessTopup(Tenant $tenant): array
    {
        $settings = $tenant->settings ?? [];
        
        // Check if auto-topup is enabled
        if (!($settings['auto_topup_enabled'] ?? false)) {
            return [
                'tenant_id' => $tenant->id,
                'status' => 'skipped',
                'reason' => 'auto_topup_disabled'
            ];
        }
        
        $threshold = $settings['auto_topup_threshold'] ?? 1000; // Default 10€
        $topupAmount = $settings['auto_topup_amount'] ?? 5000; // Default 50€
        
        // Check current balance
        $currentBalance = $tenant->balance_cents;
        
        if ($currentBalance >= $threshold) {
            return [
                'tenant_id' => $tenant->id,
                'status' => 'skipped',
                'reason' => 'balance_sufficient',
                'balance' => $currentBalance
            ];
        }
        
        // Check cooldown period
        $cooldownKey = "auto-topup.cooldown.{$tenant->id}";
        if (Cache::has($cooldownKey)) {
            $remainingMinutes = Cache::get($cooldownKey);
            
            return [
                'tenant_id' => $tenant->id,
                'status' => 'skipped',
                'reason' => 'cooldown_active',
                'cooldown_remaining' => $remainingMinutes
            ];
        }
        
        // Check if there's already a pending topup
        $pendingTopup = BalanceTopup::where('tenant_id', $tenant->id)
            ->where('status', 'pending')
            ->where('created_at', '>', now()->subHour())
            ->exists();
        
        if ($pendingTopup) {
            return [
                'tenant_id' => $tenant->id,
                'status' => 'skipped',
                'reason' => 'pending_topup_exists'
            ];
        }
        
        // Process auto-topup
        return $this->processAutoTopup($tenant, $topupAmount, $threshold);
    }
    
    /**
     * Process automatic topup for a tenant
     */
    private function processAutoTopup(Tenant $tenant, int $amountCents, int $threshold): array
    {
        DB::beginTransaction();
        
        try {
            // Check if tenant has a saved payment method
            $paymentMethodId = $tenant->settings['stripe_payment_method_id'] ?? null;
            
            if (!$paymentMethodId) {
                // Send notification to add payment method
                $this->sendPaymentMethodRequiredNotification($tenant);
                
                DB::rollBack();
                
                return [
                    'tenant_id' => $tenant->id,
                    'status' => 'failed',
                    'reason' => 'no_payment_method'
                ];
            }
            
            // Create topup record
            $topup = BalanceTopup::create([
                'tenant_id' => $tenant->id,
                'amount' => $amountCents / 100,
                'currency' => 'EUR',
                'status' => 'processing',
                'metadata' => [
                    'type' => 'auto_topup',
                    'trigger_balance' => $tenant->balance_cents,
                    'threshold' => $threshold,
                    'auto_topup' => true
                ]
            ]);
            
            // Create Stripe payment intent with saved payment method
            $stripe = new \Stripe\StripeClient(config('services.stripe.secret'));
            
            $paymentIntent = $stripe->paymentIntents->create([
                'amount' => $amountCents,
                'currency' => 'eur',
                'customer' => $tenant->stripe_customer_id,
                'payment_method' => $paymentMethodId,
                'off_session' => true,
                'confirm' => true,
                'description' => "Auto-Aufladung für {$tenant->name}",
                'metadata' => [
                    'tenant_id' => $tenant->id,
                    'topup_id' => $topup->id,
                    'type' => 'auto_topup'
                ]
            ]);
            
            if ($paymentIntent->status === 'succeeded') {
                // Update topup status
                $topup->update([
                    'status' => 'succeeded',
                    'stripe_payment_intent_id' => $paymentIntent->id,
                    'paid_at' => now(),
                    'payment_method' => 'card'
                ]);
                
                // Add balance to tenant
                $oldBalance = $tenant->balance_cents;
                $tenant->increment('balance_cents', $amountCents);
                
                // Create transaction record
                Transaction::create([
                    'tenant_id' => $tenant->id,
                    'type' => 'topup',
                    'amount_cents' => $amountCents,
                    'balance_before_cents' => $oldBalance,
                    'balance_after_cents' => $oldBalance + $amountCents,
                    'description' => "Automatische Aufladung",
                    'topup_id' => $topup->id,
                    'reference' => $paymentIntent->id,
                    'status' => 'completed'
                ]);
                
                // Set cooldown
                Cache::put("auto-topup.cooldown.{$tenant->id}", $this->cooldownMinutes, $this->cooldownMinutes * 60);
                
                // Send success notification
                $this->sendSuccessNotification($tenant, $amountCents);
                
                // Clear balance cache
                Cache::forget("balance.tenant.{$tenant->id}");
                
                // Trigger balance update event
                event(new \App\Events\BalanceUpdated($tenant, $amountCents));
                
                DB::commit();
                
                Log::info("Auto-topup processed successfully", [
                    'tenant_id' => $tenant->id,
                    'amount' => $amountCents
                ]);
                
                return [
                    'tenant_id' => $tenant->id,
                    'status' => 'success',
                    'amount' => $amountCents,
                    'new_balance' => $tenant->balance_cents
                ];
                
            } else {
                // Payment requires additional action
                $topup->update([
                    'status' => 'requires_action',
                    'stripe_payment_intent_id' => $paymentIntent->id
                ]);
                
                // Send notification for required action
                $this->sendActionRequiredNotification($tenant, $paymentIntent);
                
                DB::commit();
                
                return [
                    'tenant_id' => $tenant->id,
                    'status' => 'requires_action',
                    'payment_intent_id' => $paymentIntent->id
                ];
            }
            
        } catch (\Stripe\Exception\CardException $e) {
            DB::rollBack();
            
            // Payment failed
            $this->sendPaymentFailedNotification($tenant, $e->getMessage());
            
            // Disable auto-topup after multiple failures
            $this->handlePaymentFailure($tenant);
            
            return [
                'tenant_id' => $tenant->id,
                'status' => 'failed',
                'reason' => 'payment_failed',
                'error' => $e->getMessage()
            ];
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error("Auto-topup failed", [
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage()
            ]);
            
            return [
                'tenant_id' => $tenant->id,
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Check for low balance and send warnings
     */
    public function checkLowBalanceWarnings(): void
    {
        $tenants = Tenant::where('balance_cents', '<', $this->minimumBalance)
            ->where('tenant_type', '!=', 'platform')
            ->get();
        
        foreach ($tenants as $tenant) {
            $warningKey = "low-balance-warning.{$tenant->id}";
            
            // Only send warning once per day
            if (!Cache::has($warningKey)) {
                $this->sendLowBalanceWarning($tenant);
                Cache::put($warningKey, true, 86400); // 24 hours
            }
        }
    }
    
    /**
     * Handle payment failure
     */
    private function handlePaymentFailure(Tenant $tenant): void
    {
        $failureKey = "auto-topup.failures.{$tenant->id}";
        $failures = Cache::get($failureKey, 0) + 1;
        
        Cache::put($failureKey, $failures, 86400); // Remember for 24 hours
        
        // Disable auto-topup after 3 failures
        if ($failures >= 3) {
            $settings = $tenant->settings ?? [];
            $settings['auto_topup_enabled'] = false;
            $settings['auto_topup_disabled_reason'] = 'multiple_payment_failures';
            $settings['auto_topup_disabled_at'] = now()->toIso8601String();
            
            $tenant->settings = $settings;
            $tenant->save();
            
            Log::warning("Auto-topup disabled due to multiple failures", [
                'tenant_id' => $tenant->id,
                'failures' => $failures
            ]);
        }
    }
    
    /**
     * Send success notification
     */
    private function sendSuccessNotification(Tenant $tenant, int $amount): void
    {
        if ($tenant->users()->exists()) {
            $tenant->users->each(function ($user) use ($amount) {
                $user->notify(new AutoTopupProcessed($amount));
            });
        }
    }
    
    /**
     * Send low balance warning
     */
    private function sendLowBalanceWarning(Tenant $tenant): void
    {
        if ($tenant->users()->exists()) {
            $tenant->users->each(function ($user) use ($tenant) {
                $user->notify(new LowBalanceWarning($tenant->balance_cents));
            });
        }
    }
    
    /**
     * Send payment method required notification
     */
    private function sendPaymentMethodRequiredNotification(Tenant $tenant): void
    {
        Log::info("Payment method required for auto-topup", ['tenant_id' => $tenant->id]);
        // Implementation for notification
    }
    
    /**
     * Send action required notification
     */
    private function sendActionRequiredNotification(Tenant $tenant, $paymentIntent): void
    {
        Log::info("Action required for auto-topup", [
            'tenant_id' => $tenant->id,
            'payment_intent' => $paymentIntent->id
        ]);
        // Implementation for notification
    }
    
    /**
     * Send payment failed notification
     */
    private function sendPaymentFailedNotification(Tenant $tenant, string $error): void
    {
        Log::warning("Auto-topup payment failed", [
            'tenant_id' => $tenant->id,
            'error' => $error
        ]);
        // Implementation for notification
    }
}