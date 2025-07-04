<?php

namespace App\Services;

use App\Models\BalanceTopup;
use App\Models\Company;
use App\Models\PortalUser;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;
use Stripe\Checkout\Session as CheckoutSession;
use Stripe\PaymentIntent;
use Stripe\Exception\ApiErrorException;

class StripeTopupService
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    /**
     * Create a Stripe Checkout Session for balance topup
     */
    public function createCheckoutSession(Company $company, float $amount, PortalUser $user): ?CheckoutSession
    {
        try {
            // Create a pending topup record
            $topup = BalanceTopup::create([
                'company_id' => $company->id,
                'amount' => $amount,
                'currency' => 'EUR',
                'status' => BalanceTopup::STATUS_PENDING,
                'initiated_by' => $user->id,
            ]);

            // Create Stripe Checkout Session
            $session = CheckoutSession::create([
                'payment_method_types' => ['card', 'sepa_debit'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'eur',
                        'product_data' => [
                            'name' => 'Guthaben-Aufladung',
                            'description' => sprintf('Guthaben-Aufladung für %s', $company->name),
                        ],
                        'unit_amount' => $amount * 100, // Stripe uses cents
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => route('business.billing.topup.success') . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('business.billing.topup.cancel'),
                'client_reference_id' => $topup->id,
                'customer_email' => $user->email,
                'metadata' => [
                    'company_id' => $company->id,
                    'topup_id' => $topup->id,
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                ],
                'locale' => 'de',
            ]);

            // Update topup with session ID
            $topup->update([
                'stripe_checkout_session_id' => $session->id,
                'status' => BalanceTopup::STATUS_PROCESSING,
            ]);

            return $session;

        } catch (ApiErrorException $e) {
            Log::error('Stripe Checkout Session creation failed', [
                'company_id' => $company->id,
                'amount' => $amount,
                'error' => $e->getMessage(),
            ]);

            if (isset($topup)) {
                $topup->markAsFailed($e->getMessage());
            }

            return null;
        }
    }

    /**
     * Create a Payment Intent for direct payment
     */
    public function createPaymentIntent(Company $company, float $amount, PortalUser $user): ?PaymentIntent
    {
        try {
            // Create a pending topup record
            $topup = BalanceTopup::create([
                'company_id' => $company->id,
                'amount' => $amount,
                'currency' => 'EUR',
                'status' => BalanceTopup::STATUS_PENDING,
                'initiated_by' => $user->id,
            ]);

            // Create Payment Intent
            $intent = PaymentIntent::create([
                'amount' => $amount * 100, // Stripe uses cents
                'currency' => 'eur',
                'description' => sprintf('Guthaben-Aufladung für %s', $company->name),
                'metadata' => [
                    'company_id' => $company->id,
                    'topup_id' => $topup->id,
                    'user_id' => $user->id,
                ],
                'receipt_email' => $user->email,
            ]);

            // Update topup with payment intent ID
            $topup->update([
                'stripe_payment_intent_id' => $intent->id,
                'status' => BalanceTopup::STATUS_PROCESSING,
            ]);

            return $intent;

        } catch (ApiErrorException $e) {
            Log::error('Stripe Payment Intent creation failed', [
                'company_id' => $company->id,
                'amount' => $amount,
                'error' => $e->getMessage(),
            ]);

            if (isset($topup)) {
                $topup->markAsFailed($e->getMessage());
            }

            return null;
        }
    }

    /**
     * Handle successful payment webhook
     */
    public function handlePaymentSuccess(string $paymentIntentId): void
    {
        $topup = BalanceTopup::where('stripe_payment_intent_id', $paymentIntentId)
                            ->first();

        if (!$topup) {
            Log::warning('Topup not found for payment intent', [
                'payment_intent_id' => $paymentIntentId,
            ]);
            return;
        }

        // Prevent double processing
        if ($topup->isSuccessful()) {
            return;
        }

        // Mark as succeeded - this will also update the balance
        $topup->markAsSucceeded();

        // Send confirmation email
        try {
            $topup->initiatedBy->notify(new \App\Notifications\TopupSuccessfulNotification($topup));
        } catch (\Exception $e) {
            Log::error('Failed to send topup confirmation email', [
                'topup_id' => $topup->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle failed payment webhook
     */
    public function handlePaymentFailed(string $paymentIntentId, string $reason = null): void
    {
        $topup = BalanceTopup::where('stripe_payment_intent_id', $paymentIntentId)
                            ->first();

        if (!$topup) {
            Log::warning('Topup not found for failed payment intent', [
                'payment_intent_id' => $paymentIntentId,
            ]);
            return;
        }

        $topup->markAsFailed($reason);
    }

    /**
     * Handle checkout session completion
     */
    public function handleCheckoutSessionCompleted(string $sessionId): void
    {
        try {
            // Retrieve the session from Stripe
            $session = CheckoutSession::retrieve($sessionId);

            $topup = BalanceTopup::where('stripe_checkout_session_id', $sessionId)
                                ->first();

            if (!$topup) {
                Log::warning('Topup not found for checkout session', [
                    'session_id' => $sessionId,
                ]);
                return;
            }

            // Update payment intent ID if available
            if ($session->payment_intent) {
                $topup->update(['stripe_payment_intent_id' => $session->payment_intent]);
            }

            // Update topup status based on payment status
            if ($session->payment_status === 'paid') {
                $topup->markAsSucceeded();
            } else {
                $topup->markAsFailed('Payment not completed');
            }

        } catch (ApiErrorException $e) {
            Log::error('Failed to retrieve checkout session', [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get or create Stripe customer for company
     */
    public function getOrCreateCustomer(Company $company): ?string
    {
        try {
            // Check if company already has a Stripe customer ID
            if ($company->stripe_customer_id) {
                return $company->stripe_customer_id;
            }

            // Create new customer
            $customer = \Stripe\Customer::create([
                'name' => $company->name,
                'email' => $company->email,
                'phone' => $company->phone,
                'metadata' => [
                    'company_id' => $company->id,
                ],
            ]);

            // Save customer ID
            $company->update(['stripe_customer_id' => $customer->id]);

            return $customer->id;

        } catch (ApiErrorException $e) {
            Log::error('Failed to create Stripe customer', [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Get suggested topup amounts based on usage
     */
    public function getSuggestedAmounts(Company $company): array
    {
        // Get average monthly usage
        $avgMonthlyUsage = $company->callCharges()
            ->where('charged_at', '>=', now()->subMonths(3))
            ->avg('amount_charged') * 30;

        if ($avgMonthlyUsage > 0) {
            return [
                round($avgMonthlyUsage * 0.5, -1), // 2 weeks
                round($avgMonthlyUsage, -1),       // 1 month
                round($avgMonthlyUsage * 2, -1),   // 2 months
                round($avgMonthlyUsage * 3, -1),   // 3 months
            ];
        }

        // Default amounts if no usage history
        return [50, 100, 200, 500];
    }
}