<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\StripeClient;
use Stripe\Exception\ApiErrorException;

class PaymentMethodController extends Controller
{
    private StripeClient $stripe;
    
    public function __construct()
    {
        $this->stripe = new StripeClient(config('services.stripe.secret'));
    }
    
    /**
     * Display payment methods
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $tenant = $user->tenant;
        
        try {
            // Ensure customer exists in Stripe
            $customerId = $this->ensureStripeCustomer($tenant);
            
            // Get payment methods
            $paymentMethods = $this->stripe->paymentMethods->all([
                'customer' => $customerId,
                'type' => 'card'
            ]);
            
            // Get default payment method
            $defaultMethodId = $tenant->settings['stripe_payment_method_id'] ?? null;
            
            return view('customer.billing.payment-methods', [
                'paymentMethods' => $paymentMethods->data,
                'defaultMethodId' => $defaultMethodId,
                'stripePublishableKey' => config('services.stripe.key')
            ]);
            
        } catch (ApiErrorException $e) {
            Log::error('Failed to fetch payment methods', [
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage()
            ]);
            
            return view('customer.billing.payment-methods', [
                'paymentMethods' => [],
                'error' => 'Zahlungsmethoden konnten nicht geladen werden'
            ]);
        }
    }
    
    /**
     * Store new payment method
     */
    public function store(Request $request)
    {
        $request->validate([
            'payment_method_id' => 'required|string',
            'set_as_default' => 'boolean'
        ]);
        
        $user = $request->user();
        $tenant = $user->tenant;
        
        try {
            // Ensure customer exists
            $customerId = $this->ensureStripeCustomer($tenant);
            
            // Attach payment method to customer
            $paymentMethod = $this->stripe->paymentMethods->attach(
                $request->payment_method_id,
                ['customer' => $customerId]
            );
            
            // Set as default if requested
            if ($request->set_as_default) {
                $this->setDefaultPaymentMethod($tenant, $request->payment_method_id);
                
                // Update customer's default payment method in Stripe
                $this->stripe->customers->update($customerId, [
                    'invoice_settings' => [
                        'default_payment_method' => $request->payment_method_id
                    ]
                ]);
            }
            
            Log::info('Payment method added', [
                'tenant_id' => $tenant->id,
                'payment_method_id' => $request->payment_method_id
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Zahlungsmethode erfolgreich hinzugefügt',
                'payment_method' => [
                    'id' => $paymentMethod->id,
                    'brand' => $paymentMethod->card->brand,
                    'last4' => $paymentMethod->card->last4,
                    'exp_month' => $paymentMethod->card->exp_month,
                    'exp_year' => $paymentMethod->card->exp_year
                ]
            ]);
            
        } catch (ApiErrorException $e) {
            Log::error('Failed to add payment method', [
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'error' => 'Die Zahlungsmethode konnte nicht hinzugefügt werden'
            ], 400);
        }
    }
    
    /**
     * Remove payment method
     */
    public function destroy(Request $request, string $methodId)
    {
        $user = $request->user();
        $tenant = $user->tenant;
        
        try {
            // Check if this is the default method
            $defaultMethodId = $tenant->settings['stripe_payment_method_id'] ?? null;
            
            if ($defaultMethodId === $methodId) {
                // Check if auto-topup is enabled
                if ($tenant->settings['auto_topup_enabled'] ?? false) {
                    return response()->json([
                        'error' => 'Die Standard-Zahlungsmethode kann nicht entfernt werden, während die automatische Aufladung aktiviert ist'
                    ], 400);
                }
            }
            
            // Detach payment method
            $this->stripe->paymentMethods->detach($methodId);
            
            // Clear from settings if it was default
            if ($defaultMethodId === $methodId) {
                $settings = $tenant->settings ?? [];
                unset($settings['stripe_payment_method_id']);
                $tenant->settings = $settings;
                $tenant->save();
            }
            
            Log::info('Payment method removed', [
                'tenant_id' => $tenant->id,
                'payment_method_id' => $methodId
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Zahlungsmethode erfolgreich entfernt'
            ]);
            
        } catch (ApiErrorException $e) {
            Log::error('Failed to remove payment method', [
                'tenant_id' => $tenant->id,
                'payment_method_id' => $methodId,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'error' => 'Die Zahlungsmethode konnte nicht entfernt werden'
            ], 400);
        }
    }
    
    /**
     * Set default payment method
     */
    public function setDefault(Request $request, string $methodId)
    {
        $user = $request->user();
        $tenant = $user->tenant;
        
        try {
            // Verify payment method belongs to customer
            $paymentMethod = $this->stripe->paymentMethods->retrieve($methodId);
            
            if ($paymentMethod->customer !== $tenant->stripe_customer_id) {
                return response()->json([
                    'error' => 'Diese Zahlungsmethode gehört nicht zu Ihrem Konto'
                ], 403);
            }
            
            // Update tenant settings
            $this->setDefaultPaymentMethod($tenant, $methodId);
            
            // Update Stripe customer
            $this->stripe->customers->update($tenant->stripe_customer_id, [
                'invoice_settings' => [
                    'default_payment_method' => $methodId
                ]
            ]);
            
            Log::info('Default payment method updated', [
                'tenant_id' => $tenant->id,
                'payment_method_id' => $methodId
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Standard-Zahlungsmethode erfolgreich aktualisiert'
            ]);
            
        } catch (ApiErrorException $e) {
            Log::error('Failed to set default payment method', [
                'tenant_id' => $tenant->id,
                'payment_method_id' => $methodId,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'error' => 'Die Standard-Zahlungsmethode konnte nicht aktualisiert werden'
            ], 400);
        }
    }
    
    /**
     * Ensure Stripe customer exists
     */
    private function ensureStripeCustomer($tenant): string
    {
        if ($tenant->stripe_customer_id) {
            try {
                // Verify customer still exists
                $this->stripe->customers->retrieve($tenant->stripe_customer_id);
                return $tenant->stripe_customer_id;
            } catch (ApiErrorException $e) {
                // Customer doesn't exist, create new one
                Log::warning('Stripe customer not found, creating new', [
                    'tenant_id' => $tenant->id,
                    'old_customer_id' => $tenant->stripe_customer_id
                ]);
            }
        }
        
        // Create new customer
        $customer = $this->stripe->customers->create([
            'email' => $tenant->billing_email ?? $tenant->users->first()?->email,
            'name' => $tenant->name,
            'metadata' => [
                'tenant_id' => $tenant->id,
                'tenant_type' => $tenant->tenant_type
            ]
        ]);
        
        // Save customer ID
        $tenant->stripe_customer_id = $customer->id;
        $tenant->save();
        
        Log::info('Stripe customer created', [
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id
        ]);
        
        return $customer->id;
    }
    
    /**
     * Update default payment method in tenant settings
     */
    private function setDefaultPaymentMethod($tenant, string $methodId): void
    {
        $settings = $tenant->settings ?? [];
        $settings['stripe_payment_method_id'] = $methodId;
        $settings['payment_method_updated_at'] = now()->toIso8601String();
        
        $tenant->settings = $settings;
        $tenant->save();
    }
}