<?php

namespace App\Services\Billing;

use App\Models\Company;
use App\Models\PricingPlan;
use App\Models\PriceRule;
use App\Models\ServiceAddon;
use App\Models\Subscription;
use App\Models\Customer;
use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdvancedPricingService
{
    /**
     * Calculate total subscription cost including addons and discounts.
     */
    public function calculateSubscriptionCost(Subscription $subscription, array $context = []): array
    {
        $breakdown = [
            'base_price' => 0,
            'addons' => [],
            'discounts' => [],
            'rules_applied' => [],
            'subtotal' => 0,
            'total' => 0,
            'currency' => 'EUR',
        ];

        // Get base price
        if ($subscription->custom_price !== null) {
            $basePrice = $subscription->custom_price;
        } elseif ($subscription->pricingPlan) {
            $basePrice = $subscription->pricingPlan->base_price;
            $breakdown['currency'] = $subscription->pricingPlan->currency;
        } else {
            return $breakdown;
        }

        $breakdown['base_price'] = $basePrice;
        $runningTotal = $basePrice;

        // Add active addons
        foreach ($subscription->activeAddons as $addon) {
            $addonPrice = $addon->pivot->price_override ?? $addon->calculatePrice($addon->pivot->quantity);
            
            $breakdown['addons'][] = [
                'id' => $addon->id,
                'name' => $addon->name,
                'quantity' => $addon->pivot->quantity,
                'unit_price' => $addon->price,
                'total_price' => $addonPrice,
            ];
            
            $runningTotal += $addonPrice;
        }

        $breakdown['subtotal'] = $runningTotal;

        // Apply price rules
        if ($subscription->pricingPlan) {
            $applicableRules = $this->getApplicableRules($subscription->pricingPlan, $context);
            
            foreach ($applicableRules as $rule) {
                $originalTotal = $runningTotal;
                $runningTotal = $rule->applyToPrice($runningTotal);
                $discountAmount = $originalTotal - $runningTotal;
                
                if ($discountAmount > 0) {
                    $breakdown['discounts'][] = [
                        'rule_id' => $rule->id,
                        'name' => $rule->name,
                        'amount' => $discountAmount,
                    ];
                    
                    $breakdown['rules_applied'][] = $rule->name;
                }
            }
        }

        // Apply volume discounts if applicable
        if ($subscription->pricingPlan && !empty($subscription->pricingPlan->volume_discounts)) {
            $usage = $context['usage'] ?? [];
            $minutesUsed = $usage['minutes'] ?? 0;
            
            $volumePrice = $subscription->pricingPlan->calculatePriceWithDiscount($minutesUsed, 'minutes');
            if ($volumePrice < $basePrice) {
                $volumeDiscount = $basePrice - $volumePrice;
                $runningTotal -= $volumeDiscount;
                
                $breakdown['discounts'][] = [
                    'name' => 'Volume Discount',
                    'amount' => $volumeDiscount,
                ];
            }
        }

        $breakdown['total'] = max(0, $runningTotal); // Ensure non-negative

        return $breakdown;
    }

    /**
     * Get applicable price rules for a pricing plan.
     */
    public function getApplicableRules(PricingPlan $plan, array $context = []): Collection
    {
        return PriceRule::where('company_id', $plan->company_id)
            ->where(function ($query) use ($plan) {
                $query->whereNull('pricing_plan_id')
                    ->orWhere('pricing_plan_id', $plan->id);
            })
            ->currentlyValid()
            ->orderBy('priority', 'desc')
            ->get()
            ->filter(function ($rule) use ($context) {
                return $rule->appliesTo($context);
            });
    }

    /**
     * Calculate overage charges for a billing period.
     */
    public function calculateOverageCharges(Subscription $subscription, int $usedMinutes, int $usedAppointments): array
    {
        if (!$subscription->pricingPlan) {
            return [
                'minutes' => ['overage' => 0, 'cost' => 0],
                'appointments' => ['overage' => 0, 'cost' => 0],
                'total' => 0,
            ];
        }

        return $subscription->pricingPlan->calculateOverageCost($usedMinutes, $usedAppointments);
    }

    /**
     * Get recommended pricing plan for a customer based on usage.
     */
    public function recommendPlanForCustomer(Customer $customer, Company $company): ?PricingPlan
    {
        // Calculate average monthly usage
        $threeMonthsAgo = Carbon::now()->subMonths(3);
        
        $usage = DB::table('calls')
            ->join('appointments', 'calls.appointment_id', '=', 'appointments.id')
            ->where('appointments.customer_id', $customer->id)
            ->where('calls.created_at', '>=', $threeMonthsAgo)
            ->selectRaw('AVG(calls.duration_sec / 60) as avg_minutes_per_month')
            ->selectRaw('COUNT(DISTINCT appointments.id) / 3 as avg_appointments_per_month')
            ->first();

        if (!$usage) {
            // Return default plan for new customers
            return PricingPlan::where('company_id', $company->id)
                ->where('is_active', true)
                ->where('is_default', true)
                ->first();
        }

        $avgMinutes = (int) ceil($usage->avg_minutes_per_month ?? 0);
        $avgAppointments = (int) ceil($usage->avg_appointments_per_month ?? 0);

        // Find the most cost-effective plan
        $plans = PricingPlan::where('company_id', $company->id)
            ->where('is_active', true)
            ->orderBy('base_price')
            ->get();

        $bestPlan = null;
        $bestCost = PHP_FLOAT_MAX;

        foreach ($plans as $plan) {
            $overages = $plan->calculateOverageCost($avgMinutes, $avgAppointments);
            $totalCost = $plan->base_price + $overages['total'];
            
            if ($totalCost < $bestCost) {
                $bestCost = $totalCost;
                $bestPlan = $plan;
            }
        }

        return $bestPlan;
    }

    /**
     * Apply promotional code to subscription.
     */
    public function applyPromoCode(Subscription $subscription, string $promoCode): array
    {
        $result = [
            'success' => false,
            'message' => '',
            'discount' => 0,
        ];

        // Find active promotional rule
        $promoRule = PriceRule::where('company_id', $subscription->company_id)
            ->where('type', 'promotional')
            ->currentlyValid()
            ->get()
            ->first(function ($rule) use ($promoCode) {
                $conditions = $rule->conditions;
                return isset($conditions['promo_code']) && $conditions['promo_code'] === $promoCode;
            });

        if (!$promoRule) {
            $result['message'] = 'Invalid or expired promotional code.';
            return $result;
        }

        // Check if already used (if max uses limit exists)
        if (isset($promoRule->conditions['max_uses'])) {
            $currentUses = DB::table('promo_code_uses')
                ->where('price_rule_id', $promoRule->id)
                ->count();
                
            if ($currentUses >= $promoRule->conditions['max_uses']) {
                $result['message'] = 'This promotional code has reached its usage limit.';
                return $result;
            }
        }

        // Apply the discount
        $context = ['promo_code' => $promoCode];
        if ($promoRule->appliesTo($context)) {
            // Record the usage
            DB::table('promo_code_uses')->insert([
                'price_rule_id' => $promoRule->id,
                'subscription_id' => $subscription->id,
                'applied_at' => now(),
            ]);

            // Calculate discount
            $currentCost = $this->calculateSubscriptionCost($subscription);
            $discountedCost = $promoRule->applyToPrice($currentCost['total']);
            
            $result['success'] = true;
            $result['discount'] = $currentCost['total'] - $discountedCost;
            $result['message'] = "Promotional code '{$promoCode}' applied successfully!";
        } else {
            $result['message'] = 'This promotional code is not applicable to your subscription.';
        }

        return $result;
    }

    /**
     * Get available addons for a subscription.
     */
    public function getAvailableAddons(Subscription $subscription): Collection
    {
        if (!$subscription->pricingPlan) {
            return collect();
        }

        // Get all active addons for the company
        $addons = ServiceAddon::where('company_id', $subscription->company_id)
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        // Filter by compatibility
        return $addons->filter(function ($addon) use ($subscription) {
            // Check if already subscribed
            $alreadySubscribed = $subscription->addons->contains($addon->id);
            if ($alreadySubscribed) {
                return false;
            }

            // Check compatibility with pricing plan
            return $addon->isCompatibleWith($subscription->pricingPlan);
        });
    }

    /**
     * Add addon to subscription.
     */
    public function addAddonToSubscription(Subscription $subscription, ServiceAddon $addon, array $options = []): bool
    {
        try {
            DB::beginTransaction();

            // Verify compatibility
            if ($subscription->pricingPlan && !$addon->isCompatibleWith($subscription->pricingPlan)) {
                throw new \Exception('This addon is not compatible with your current pricing plan.');
            }

            // Check if already exists
            if ($subscription->addons()->where('service_addon_id', $addon->id)->exists()) {
                throw new \Exception('This addon is already added to your subscription.');
            }

            // Add the addon
            $subscription->addons()->attach($addon->id, [
                'price_override' => $options['price_override'] ?? null,
                'quantity' => $options['quantity'] ?? 1,
                'start_date' => $options['start_date'] ?? now()->startOfDay(),
                'end_date' => $options['end_date'] ?? null,
                'status' => 'active',
                'metadata' => $options['metadata'] ?? [],
            ]);

            // Update Stripe subscription if needed
            if ($addon->type === 'recurring' && $subscription->stripe_subscription_id) {
                // TODO: Add Stripe subscription item
                Log::info('TODO: Add Stripe subscription item for addon', [
                    'subscription_id' => $subscription->id,
                    'addon_id' => $addon->id,
                ]);
            }

            DB::commit();
            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to add addon to subscription', [
                'subscription_id' => $subscription->id,
                'addon_id' => $addon->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Remove addon from subscription.
     */
    public function removeAddonFromSubscription(Subscription $subscription, ServiceAddon $addon, bool $immediately = false): bool
    {
        try {
            $subscriptionAddon = $subscription->addons()
                ->where('service_addon_id', $addon->id)
                ->first();

            if (!$subscriptionAddon) {
                throw new \Exception('This addon is not found in your subscription.');
            }

            DB::beginTransaction();

            // Cancel the addon
            $pivot = $subscriptionAddon->pivot;
            if ($immediately) {
                $pivot->update([
                    'status' => 'cancelled',
                    'end_date' => now()->startOfDay(),
                ]);
            } else {
                // Cancel at end of billing period
                $endDate = $subscription->next_billing_date ?? $subscription->current_period_end;
                $pivot->update([
                    'status' => 'cancelled',
                    'end_date' => $endDate,
                ]);
            }

            // Update Stripe if needed
            if ($addon->type === 'recurring' && $subscription->stripe_subscription_id) {
                // TODO: Remove Stripe subscription item
                Log::info('TODO: Remove Stripe subscription item for addon', [
                    'subscription_id' => $subscription->id,
                    'addon_id' => $addon->id,
                ]);
            }

            DB::commit();
            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to remove addon from subscription', [
                'subscription_id' => $subscription->id,
                'addon_id' => $addon->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}