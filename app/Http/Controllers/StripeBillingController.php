<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Subscription;
use App\Services\Billing\StripeSubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Stripe\StripeClient;

class StripeBillingController extends Controller
{
    protected StripeSubscriptionService $subscriptionService;
    protected StripeClient $stripe;
    
    public function __construct(StripeSubscriptionService $subscriptionService)
    {
        $this->subscriptionService = $subscriptionService;
        $this->stripe = new StripeClient(config('services.stripe.secret'));
    }
    
    /**
     * Get available pricing plans
     */
    public function getPlans(): JsonResponse
    {
        try {
            // Fetch products and prices from Stripe
            $products = $this->stripe->products->all(['active' => true]);
            $prices = $this->stripe->prices->all(['active' => true]);
            
            // Build plans array
            $plans = [];
            foreach ($products->data as $product) {
                $productPrices = array_filter($prices->data, function($price) use ($product) {
                    return $price->product === $product->id;
                });
                
                $plans[] = [
                    'id' => $product->id,
                    'name' => $product->name,
                    'description' => $product->description,
                    'metadata' => $product->metadata,
                    'prices' => array_map(function($price) {
                        return [
                            'id' => $price->id,
                            'currency' => $price->currency,
                            'unit_amount' => $price->unit_amount,
                            'interval' => $price->recurring->interval ?? null,
                            'interval_count' => $price->recurring->interval_count ?? null,
                            'trial_period_days' => $price->recurring->trial_period_days ?? null,
                        ];
                    }, $productPrices)
                ];
            }
            
            return response()->json([
                'success' => true,
                'plans' => $plans
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Create a new subscription
     */
    public function createSubscription(Request $request): JsonResponse
    {
        $request->validate([
            'company_id' => 'required|exists:companies,id',
            'price_id' => 'required|string',
            'payment_method_id' => 'required_without:setup_intent|string',
            'quantity' => 'integer|min:1',
            'trial_days' => 'integer|min:0',
        ]);
        
        try {
            $company = Company::findOrFail($request->company_id);
            
            // Ensure company doesn't already have an active subscription
            if ($company->activeSubscription()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Company already has an active subscription'
                ], 422);
            }
            
            $options = [
                'quantity' => $request->quantity ?? 1,
            ];
            
            if ($request->trial_days) {
                $options['trial_period_days'] = $request->trial_days;
            }
            
            if ($request->payment_method_id) {
                $options['default_payment_method'] = $request->payment_method_id;
            }
            
            $subscription = $this->subscriptionService->createSubscription(
                $company,
                $request->price_id,
                $options
            );
            
            return response()->json([
                'success' => true,
                'subscription' => $subscription->load('items'),
                'client_secret' => $subscription->latestInvoice?->payment_intent?->client_secret,
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Update a subscription
     */
    public function updateSubscription(Request $request, Subscription $subscription): JsonResponse
    {
        $request->validate([
            'price_id' => 'string',
            'quantity' => 'integer|min:1',
            'cancel_at_period_end' => 'boolean',
        ]);
        
        try {
            $updates = [];
            
            if ($request->has('price_id')) {
                // Change subscription plan
                $updates['items'] = [[
                    'id' => $subscription->items->first()->stripe_subscription_item_id,
                    'price' => $request->price_id,
                ]];
            }
            
            if ($request->has('quantity')) {
                $updates['items'] = [[
                    'id' => $subscription->items->first()->stripe_subscription_item_id,
                    'quantity' => $request->quantity,
                ]];
            }
            
            if ($request->has('cancel_at_period_end')) {
                $updates['cancel_at_period_end'] = $request->cancel_at_period_end;
            }
            
            $subscription = $this->subscriptionService->updateSubscription($subscription, $updates);
            
            return response()->json([
                'success' => true,
                'subscription' => $subscription->load('items')
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Cancel a subscription
     */
    public function cancelSubscription(Request $request, Subscription $subscription): JsonResponse
    {
        $request->validate([
            'immediately' => 'boolean',
        ]);
        
        try {
            $subscription = $this->subscriptionService->cancelSubscription(
                $subscription,
                $request->immediately ?? false
            );
            
            return response()->json([
                'success' => true,
                'subscription' => $subscription,
                'message' => $request->immediately 
                    ? 'Subscription cancelled immediately' 
                    : 'Subscription will cancel at period end'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Resume a cancelled subscription
     */
    public function resumeSubscription(Subscription $subscription): JsonResponse
    {
        try {
            $subscription = $this->subscriptionService->resumeSubscription($subscription);
            
            return response()->json([
                'success' => true,
                'subscription' => $subscription,
                'message' => 'Subscription resumed'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get subscription usage
     */
    public function getUsage(Subscription $subscription): JsonResponse
    {
        try {
            $usage = $this->subscriptionService->getUsage($subscription);
            
            return response()->json([
                'success' => true,
                'usage' => $usage
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Create a billing portal session
     */
    public function createPortalSession(Request $request): JsonResponse
    {
        $request->validate([
            'company_id' => 'required|exists:companies,id',
            'return_url' => 'required|url',
        ]);
        
        try {
            $company = Company::findOrFail($request->company_id);
            
            if (!$company->stripe_customer_id) {
                return response()->json([
                    'success' => false,
                    'error' => 'Company has no Stripe customer'
                ], 422);
            }
            
            $session = $this->stripe->billingPortal->sessions->create([
                'customer' => $company->stripe_customer_id,
                'return_url' => $request->return_url,
            ]);
            
            return response()->json([
                'success' => true,
                'url' => $session->url
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Create a checkout session
     */
    public function createCheckoutSession(Request $request): JsonResponse
    {
        $request->validate([
            'company_id' => 'required|exists:companies,id',
            'price_id' => 'required|string',
            'success_url' => 'required|url',
            'cancel_url' => 'required|url',
            'quantity' => 'integer|min:1',
            'trial_days' => 'integer|min:0',
        ]);
        
        try {
            $company = Company::findOrFail($request->company_id);
            
            // Ensure company has Stripe customer
            $customerId = $this->subscriptionService->ensureStripeCustomer($company);
            
            $sessionData = [
                'customer' => $customerId,
                'payment_method_types' => ['card', 'sepa_debit'],
                'line_items' => [[
                    'price' => $request->price_id,
                    'quantity' => $request->quantity ?? 1,
                ]],
                'mode' => 'subscription',
                'success_url' => $request->success_url,
                'cancel_url' => $request->cancel_url,
                'metadata' => [
                    'company_id' => $company->id,
                ],
            ];
            
            if ($request->trial_days) {
                $sessionData['subscription_data'] = [
                    'trial_period_days' => $request->trial_days,
                ];
            }
            
            $session = $this->stripe->checkout->sessions->create($sessionData);
            
            return response()->json([
                'success' => true,
                'checkout_url' => $session->url,
                'session_id' => $session->id,
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}