<?php

namespace App\Services\Billing;

use App\Models\Company;
use App\Models\Subscription;
use App\Models\SubscriptionItem;
use Illuminate\Support\Facades\Log;
use Stripe\StripeClient;
use Stripe\Exception\ApiErrorException;

class StripeSubscriptionService
{
    protected StripeClient $stripe;
    
    public function __construct()
    {
        $this->stripe = new StripeClient(config('services.stripe.secret'));
    }
    
    /**
     * Create a new subscription for a company
     */
    public function createSubscription(Company $company, string $priceId, array $options = []): Subscription
    {
        try {
            // Ensure company has a Stripe customer
            $customerId = $this->ensureStripeCustomer($company);
            
            // Create subscription in Stripe
            $stripeSubscription = $this->stripe->subscriptions->create(array_merge([
                'customer' => $customerId,
                'items' => [
                    ['price' => $priceId, 'quantity' => $options['quantity'] ?? 1]
                ],
                'payment_behavior' => 'default_incomplete',
                'payment_settings' => ['save_default_payment_method' => 'on_subscription'],
                'expand' => ['latest_invoice.payment_intent'],
                'metadata' => [
                    'company_id' => $company->id,
                    'company_name' => $company->name,
                ],
            ], $options));
            
            // Create local subscription record
            $subscription = $this->createLocalSubscription($company, $stripeSubscription);
            
            Log::info('Subscription created', [
                'company_id' => $company->id,
                'subscription_id' => $subscription->id,
                'stripe_subscription_id' => $stripeSubscription->id,
            ]);
            
            return $subscription;
            
        } catch (ApiErrorException $e) {
            Log::error('Failed to create Stripe subscription', [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
    
    /**
     * Update an existing subscription
     */
    public function updateSubscription(Subscription $subscription, array $updates): Subscription
    {
        try {
            $stripeSubscription = $this->stripe->subscriptions->update(
                $subscription->stripe_subscription_id,
                $updates
            );
            
            // Sync local record
            $subscription->syncWithStripe($stripeSubscription->toArray());
            
            // Sync items if they changed
            if (isset($updates['items'])) {
                $this->syncSubscriptionItems($subscription, $stripeSubscription);
            }
            
            Log::info('Subscription updated', [
                'subscription_id' => $subscription->id,
                'updates' => array_keys($updates),
            ]);
            
            return $subscription;
            
        } catch (ApiErrorException $e) {
            Log::error('Failed to update Stripe subscription', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
    
    /**
     * Cancel a subscription
     */
    public function cancelSubscription(Subscription $subscription, bool $immediately = false): Subscription
    {
        try {
            if ($immediately) {
                // Cancel immediately
                $stripeSubscription = $this->stripe->subscriptions->cancel(
                    $subscription->stripe_subscription_id
                );
            } else {
                // Cancel at period end
                $stripeSubscription = $this->stripe->subscriptions->update(
                    $subscription->stripe_subscription_id,
                    ['cancel_at_period_end' => true]
                );
            }
            
            $subscription->syncWithStripe($stripeSubscription->toArray());
            
            Log::info('Subscription canceled', [
                'subscription_id' => $subscription->id,
                'immediately' => $immediately,
            ]);
            
            return $subscription;
            
        } catch (ApiErrorException $e) {
            Log::error('Failed to cancel Stripe subscription', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
    
    /**
     * Resume a canceled subscription
     */
    public function resumeSubscription(Subscription $subscription): Subscription
    {
        if (!$subscription->onGracePeriod()) {
            throw new \Exception('Subscription is not on grace period');
        }
        
        try {
            $stripeSubscription = $this->stripe->subscriptions->update(
                $subscription->stripe_subscription_id,
                ['cancel_at_period_end' => false]
            );
            
            $subscription->syncWithStripe($stripeSubscription->toArray());
            
            Log::info('Subscription resumed', [
                'subscription_id' => $subscription->id,
            ]);
            
            return $subscription;
            
        } catch (ApiErrorException $e) {
            Log::error('Failed to resume Stripe subscription', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
    
    /**
     * Sync subscription from Stripe webhook
     */
    public function syncFromWebhook(array $webhookData): ?Subscription
    {
        $stripeSubscription = $webhookData['data']['object'];
        
        // Find local subscription
        $subscription = Subscription::where('stripe_subscription_id', $stripeSubscription['id'])->first();
        
        if (!$subscription) {
            // Check if we should create it
            if (isset($stripeSubscription['metadata']['company_id'])) {
                $company = Company::find($stripeSubscription['metadata']['company_id']);
                if ($company) {
                    $subscription = $this->createLocalSubscription($company, (object)$stripeSubscription);
                }
            }
            
            if (!$subscription) {
                Log::warning('Subscription not found for webhook', [
                    'stripe_subscription_id' => $stripeSubscription['id'],
                ]);
                return null;
            }
        }
        
        // Sync subscription data
        $subscription->syncWithStripe($stripeSubscription);
        
        // Sync items if included
        if (isset($stripeSubscription['items'])) {
            $this->syncSubscriptionItems($subscription, (object)$stripeSubscription);
        }
        
        return $subscription;
    }
    
    /**
     * Get or create Stripe customer for company
     */
    public function ensureStripeCustomer(Company $company): string
    {
        if ($company->stripe_customer_id) {
            return $company->stripe_customer_id;
        }
        
        // Create new customer
        $customer = $this->stripe->customers->create([
            'name' => $company->name,
            'email' => $company->billing_email ?? $company->email,
            'phone' => $company->phone,
            'metadata' => [
                'company_id' => $company->id,
            ],
            'address' => [
                'line1' => $company->address,
                'city' => $company->city,
                'postal_code' => $company->postal_code,
                'country' => $company->country ?? 'DE',
            ],
        ]);
        
        // Save customer ID
        $company->update(['stripe_customer_id' => $customer->id]);
        
        Log::info('Created Stripe customer', [
            'company_id' => $company->id,
            'stripe_customer_id' => $customer->id,
        ]);
        
        return $customer->id;
    }
    
    /**
     * Create local subscription record from Stripe data
     */
    protected function createLocalSubscription(Company $company, $stripeSubscription): Subscription
    {
        $subscription = Subscription::create([
            'company_id' => $company->id,
            'stripe_subscription_id' => $stripeSubscription->id,
            'stripe_customer_id' => $stripeSubscription->customer,
            'name' => $stripeSubscription->description ?? 'AskProAI Subscription',
            'stripe_status' => $stripeSubscription->status,
            'stripe_price_id' => $stripeSubscription->items->data[0]->price->id ?? null,
            'quantity' => $stripeSubscription->items->data[0]->quantity ?? 1,
            'trial_ends_at' => $stripeSubscription->trial_end 
                ? now()->createFromTimestamp($stripeSubscription->trial_end)
                : null,
            'current_period_start' => now()->createFromTimestamp($stripeSubscription->current_period_start),
            'current_period_end' => now()->createFromTimestamp($stripeSubscription->current_period_end),
            'cancel_at_period_end' => $stripeSubscription->cancel_at_period_end ?? false,
            'metadata' => $stripeSubscription->metadata ?? [],
        ]);
        
        // Create subscription items
        foreach ($stripeSubscription->items->data as $item) {
            SubscriptionItem::create([
                'subscription_id' => $subscription->id,
                'stripe_subscription_item_id' => $item->id,
                'stripe_price_id' => $item->price->id,
                'stripe_product_id' => $item->price->product,
                'quantity' => $item->quantity,
                'metadata' => $item->metadata ?? [],
            ]);
        }
        
        return $subscription;
    }
    
    /**
     * Sync subscription items from Stripe
     */
    protected function syncSubscriptionItems(Subscription $subscription, $stripeSubscription): void
    {
        $existingItemIds = $subscription->items->pluck('stripe_subscription_item_id')->toArray();
        $stripeItemIds = [];
        
        foreach ($stripeSubscription->items->data as $stripeItem) {
            $stripeItemIds[] = $stripeItem->id;
            
            $item = $subscription->items()
                ->where('stripe_subscription_item_id', $stripeItem->id)
                ->first();
                
            if ($item) {
                // Update existing item
                $item->syncWithStripe((array)$stripeItem);
            } else {
                // Create new item
                SubscriptionItem::create([
                    'subscription_id' => $subscription->id,
                    'stripe_subscription_item_id' => $stripeItem->id,
                    'stripe_price_id' => $stripeItem->price->id,
                    'stripe_product_id' => $stripeItem->price->product,
                    'quantity' => $stripeItem->quantity,
                    'metadata' => $stripeItem->metadata ?? [],
                ]);
            }
        }
        
        // Remove items that no longer exist in Stripe
        $subscription->items()
            ->whereNotIn('stripe_subscription_item_id', $stripeItemIds)
            ->delete();
    }
    
    /**
     * Get subscription usage for current period
     */
    public function getUsage(Subscription $subscription): array
    {
        // This would integrate with your usage tracking
        // For now, return mock data
        return [
            'period_start' => $subscription->current_period_start,
            'period_end' => $subscription->current_period_end,
            'usage' => [
                'calls' => 1250,
                'minutes' => 3420,
                'appointments' => 186,
            ],
        ];
    }
    
    /**
     * Report usage to Stripe (for metered billing)
     */
    public function reportUsage(Subscription $subscription, string $metricKey, int $quantity): void
    {
        // Find the subscription item for this metric
        $item = $subscription->items()->where('stripe_product_id', $metricKey)->first();
        
        if (!$item) {
            Log::warning('No subscription item found for metric', [
                'subscription_id' => $subscription->id,
                'metric_key' => $metricKey,
            ]);
            return;
        }
        
        try {
            $this->stripe->subscriptionItems->createUsageRecord(
                $item->stripe_subscription_item_id,
                [
                    'quantity' => $quantity,
                    'timestamp' => now()->timestamp,
                    'action' => 'increment', // or 'set' for absolute values
                ]
            );
            
            Log::info('Usage reported to Stripe', [
                'subscription_id' => $subscription->id,
                'metric' => $metricKey,
                'quantity' => $quantity,
            ]);
            
        } catch (ApiErrorException $e) {
            Log::error('Failed to report usage to Stripe', [
                'subscription_id' => $subscription->id,
                'metric' => $metricKey,
                'error' => $e->getMessage(),
            ]);
        }
    }
}