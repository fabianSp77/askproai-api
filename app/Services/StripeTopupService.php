<?php

namespace App\Services;

use App\Models\BalanceTopup;
use App\Models\Company;
use App\Models\PortalUser;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Services\StripeInvoiceService;
use App\Mail\InvoiceMail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Stripe\Stripe;
use Stripe\Checkout\Session as CheckoutSession;
use Stripe\PaymentIntent;
use Stripe\PaymentMethod;
use Stripe\SetupIntent;
use Stripe\Exception\ApiErrorException;

class StripeTopupService
{
    protected StripeInvoiceService $invoiceService;
    
    public function __construct(StripeInvoiceService $invoiceService)
    {
        Stripe::setApiKey(config('services.stripe.secret'));
        $this->invoiceService = $invoiceService;
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
        
        // Create invoice for the successful topup
        $this->createTopupInvoice($topup);

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
                
                // Create invoice for the successful topup
                $this->createTopupInvoice($topup);
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

    /**
     * Create payment intent with saved payment method
     */
    public function createPaymentIntentWithPaymentMethod(
        Company $company,
        float $amount,
        string $paymentMethodId
    ): ?PaymentIntent {
        try {
            $customerId = $this->getOrCreateCustomer($company);
            if (!$customerId) {
                throw new \Exception('Failed to get customer ID');
            }

            $paymentIntent = PaymentIntent::create([
                'amount' => $amount * 100, // Amount in cents
                'currency' => 'eur',
                'customer' => $customerId,
                'payment_method' => $paymentMethodId,
                'off_session' => true,
                'confirm' => true,
                'description' => sprintf('Auto-Topup für %s', $company->name),
                'metadata' => [
                    'company_id' => $company->id,
                    'type' => 'auto_topup',
                ],
            ]);

            return $paymentIntent;

        } catch (ApiErrorException $e) {
            Log::error('Failed to create payment intent with payment method', [
                'company_id' => $company->id,
                'amount' => $amount,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Confirm payment intent
     */
    public function confirmPaymentIntent(string $paymentIntentId): ?PaymentIntent
    {
        try {
            $paymentIntent = PaymentIntent::retrieve($paymentIntentId);
            
            if ($paymentIntent->status === 'requires_confirmation') {
                $paymentIntent = $paymentIntent->confirm();
            }
            
            return $paymentIntent;

        } catch (ApiErrorException $e) {
            Log::error('Failed to confirm payment intent', [
                'payment_intent_id' => $paymentIntentId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Validate payment method belongs to customer
     */
    public function validatePaymentMethod(Company $company, string $paymentMethodId): bool
    {
        try {
            $customerId = $this->getOrCreateCustomer($company);
            if (!$customerId) {
                return false;
            }

            $paymentMethod = PaymentMethod::retrieve($paymentMethodId);
            
            return $paymentMethod->customer === $customerId;

        } catch (ApiErrorException $e) {
            Log::error('Failed to validate payment method', [
                'company_id' => $company->id,
                'payment_method_id' => $paymentMethodId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Create setup intent for saving payment method
     */
    public function createSetupIntent(Company $company): ?SetupIntent
    {
        try {
            $customerId = $this->getOrCreateCustomer($company);
            if (!$customerId) {
                return null;
            }

            $setupIntent = SetupIntent::create([
                'customer' => $customerId,
                'payment_method_types' => ['card', 'sepa_debit'],
                'usage' => 'off_session',
                'metadata' => [
                    'company_id' => $company->id,
                ],
            ]);

            return $setupIntent;

        } catch (ApiErrorException $e) {
            Log::error('Failed to create setup intent', [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * List payment methods for customer
     */
    public function listPaymentMethods(Company $company): array
    {
        try {
            $customerId = $this->getOrCreateCustomer($company);
            if (!$customerId) {
                return [];
            }

            $paymentMethods = PaymentMethod::all([
                'customer' => $customerId,
                'type' => 'card',
            ]);

            $sepaMethods = PaymentMethod::all([
                'customer' => $customerId,
                'type' => 'sepa_debit',
            ]);

            $methods = [];
            
            foreach ($paymentMethods->data as $method) {
                $methods[] = [
                    'id' => $method->id,
                    'type' => 'card',
                    'brand' => $method->card->brand,
                    'last4' => $method->card->last4,
                    'exp_month' => $method->card->exp_month,
                    'exp_year' => $method->card->exp_year,
                ];
            }
            
            foreach ($sepaMethods->data as $method) {
                $methods[] = [
                    'id' => $method->id,
                    'type' => 'sepa_debit',
                    'bank_code' => $method->sepa_debit->bank_code,
                    'last4' => $method->sepa_debit->last4,
                ];
            }

            return $methods;

        } catch (ApiErrorException $e) {
            Log::error('Failed to list payment methods', [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Delete payment method
     */
    public function deletePaymentMethod(Company $company, string $paymentMethodId): bool
    {
        try {
            // Validate it belongs to this customer
            if (!$this->validatePaymentMethod($company, $paymentMethodId)) {
                return false;
            }

            $paymentMethod = PaymentMethod::retrieve($paymentMethodId);
            $paymentMethod->detach();
            
            // If this was the auto-topup method, clear it
            $balance = $company->prepaidBalance;
            if ($balance && $balance->stripe_payment_method_id === $paymentMethodId) {
                $balance->update(['stripe_payment_method_id' => null]);
            }

            return true;

        } catch (ApiErrorException $e) {
            Log::error('Failed to delete payment method', [
                'company_id' => $company->id,
                'payment_method_id' => $paymentMethodId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
    
    /**
     * Get saved payment methods for company
     */
    public function getSavedPaymentMethods(Company $company)
    {
        try {
            $customerId = $this->getOrCreateCustomer($company);
            if (!$customerId) {
                return collect();
            }

            $paymentMethods = PaymentMethod::all([
                'customer' => $customerId,
                'type' => 'card',
            ]);

            return collect($paymentMethods->data);

        } catch (ApiErrorException $e) {
            Log::error('Failed to get saved payment methods', [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
            ]);
            return collect();
        }
    }
    
    /**
     * Get payment method details
     */
    public function getPaymentMethod(string $paymentMethodId): ?PaymentMethod
    {
        try {
            return PaymentMethod::retrieve($paymentMethodId);
        } catch (ApiErrorException $e) {
            Log::error('Failed to retrieve payment method', [
                'payment_method_id' => $paymentMethodId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
    
    /**
     * Attach payment method to customer
     */
    public function attachPaymentMethod(Company $company, string $paymentMethodId): bool
    {
        try {
            $customerId = $this->getOrCreateCustomer($company);
            if (!$customerId) {
                return false;
            }

            $paymentMethod = PaymentMethod::retrieve($paymentMethodId);
            $paymentMethod->attach(['customer' => $customerId]);

            return true;

        } catch (ApiErrorException $e) {
            Log::error('Failed to attach payment method', [
                'company_id' => $company->id,
                'payment_method_id' => $paymentMethodId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
    
    /**
     * Detach payment method
     */
    public function detachPaymentMethod(string $paymentMethodId): bool
    {
        try {
            $paymentMethod = PaymentMethod::retrieve($paymentMethodId);
            $paymentMethod->detach();
            return true;

        } catch (ApiErrorException $e) {
            Log::error('Failed to detach payment method', [
                'payment_method_id' => $paymentMethodId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
    
    /**
     * Create invoice for successful topup
     */
    public function createTopupInvoice(BalanceTopup $topup): ?Invoice
    {
        try {
            $company = $topup->company;
            
            // Generate invoice number
            $invoiceNumber = $this->generateTopupInvoiceNumber($company);
            
            // Check if company is small business (no VAT)
            $isSmallBusiness = $company->is_small_business ?? false;
            $taxRate = $isSmallBusiness ? 0 : 19.0;
            $taxAmount = $topup->amount * ($taxRate / 100);
            $total = $topup->amount + $taxAmount;
            
            // Create invoice
            $invoice = Invoice::create([
                'company_id' => $company->id,
                'number' => $invoiceNumber,
                'status' => 'paid',
                'subtotal' => $topup->amount,
                'tax_rate' => $taxRate,
                'tax_amount' => $taxAmount,
                'total' => $total,
                'currency' => $topup->currency ?? 'EUR',
                'invoice_date' => now(),
                'due_date' => now(),
                'paid_at' => $topup->paid_at ?? now(),
                'payment_method' => 'stripe',
                'billing_reason' => 'topup',
                'metadata' => [
                    'topup_id' => $topup->id,
                    'stripe_payment_intent_id' => $topup->stripe_payment_intent_id,
                    'stripe_checkout_session_id' => $topup->stripe_checkout_session_id,
                ],
            ]);
            
            // Create invoice item
            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'type' => 'service',
                'description' => 'Guthaben-Aufladung',
                'quantity' => 1,
                'unit' => 'Stück',
                'unit_price' => $topup->amount,
                'amount' => $topup->amount,
                'tax_rate' => $taxRate,
            ]);
            
            // Update topup with invoice ID
            $topup->update(['invoice_id' => $invoice->id]);
            
            // If we have a Stripe invoice ID, save it
            if ($topup->stripe_invoice_id) {
                $invoice->update(['stripe_invoice_id' => $topup->stripe_invoice_id]);
            }
            
            // Generate PDF
            if (class_exists(\App\Services\InvoicePdfService::class)) {
                try {
                    app(\App\Services\InvoicePdfService::class)->generatePdf($invoice);
                } catch (\Exception $e) {
                    Log::error('Failed to generate invoice PDF', [
                        'invoice_id' => $invoice->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
            
            Log::info('Topup invoice created', [
                'invoice_id' => $invoice->id,
                'topup_id' => $topup->id,
                'amount' => $topup->amount,
            ]);
            
            // Send invoice email
            try {
                // Get current balance
                $prepaidBalance = $company->prepaidBalance;
                $currentBalance = $prepaidBalance ? $prepaidBalance->getTotalBalance() : 0;
                
                // Calculate bonus (if any)
                $bonusAmount = 0;
                if ($topup->transaction) {
                    $bonusTransaction = \App\Models\BalanceTransaction::where('company_id', $company->id)
                        ->where('type', 'topup_bonus')
                        ->where('reference_id', $topup->id)
                        ->first();
                    
                    if ($bonusTransaction) {
                        $bonusAmount = $bonusTransaction->amount;
                    }
                }
                
                // Send email to company email
                if ($company->email) {
                    Mail::to($company->email)->send(new InvoiceMail($invoice, $currentBalance, $bonusAmount));
                }
                
                // Also send to the user who initiated the topup
                if ($topup->initiatedBy && $topup->initiatedBy->email) {
                    Mail::to($topup->initiatedBy->email)->send(new InvoiceMail($invoice, $currentBalance, $bonusAmount));
                }
                
                Log::info('Invoice email sent', [
                    'invoice_id' => $invoice->id,
                    'recipients' => [$company->email, $topup->initiatedBy?->email],
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to send invoice email', [
                    'invoice_id' => $invoice->id,
                    'error' => $e->getMessage(),
                ]);
            }
            
            return $invoice;
            
        } catch (\Exception $e) {
            Log::error('Failed to create topup invoice', [
                'topup_id' => $topup->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
    
    /**
     * Generate invoice number for topup
     */
    protected function generateTopupInvoiceNumber(Company $company): string
    {
        $year = now()->year;
        $prefix = 'TOP'; // Topup prefix
        
        // Get the last invoice number for this year
        $lastInvoice = Invoice::where('company_id', $company->id)
            ->where('number', 'like', "{$prefix}-{$year}-%")
            ->orderByRaw('CAST(SUBSTRING_INDEX(number, "-", -1) AS UNSIGNED) DESC')
            ->first();
            
        if ($lastInvoice) {
            // Extract the number and increment
            $parts = explode('-', $lastInvoice->number);
            $nextNumber = intval(end($parts)) + 1;
        } else {
            $nextNumber = 1;
        }
        
        return sprintf('%s-%d-%05d', $prefix, $year, $nextNumber);
    }
    
    /**
     * Process topup with payment method
     */
    public function processTopup(Company $company, float $amount, string $paymentMethodId, PortalUser $user): array
    {
        try {
            // Validate payment method belongs to customer
            if (!$this->validatePaymentMethod($company, $paymentMethodId)) {
                throw new \Exception('Invalid payment method');
            }
            
            // Create a pending topup record
            $topup = BalanceTopup::create([
                'company_id' => $company->id,
                'amount' => $amount,
                'currency' => 'EUR',
                'status' => BalanceTopup::STATUS_PENDING,
                'initiated_by' => $user->id,
                'metadata' => [
                    'payment_method_id' => $paymentMethodId,
                    'source' => 'direct_payment',
                ],
            ]);
            
            // Create and confirm payment intent
            $paymentIntent = $this->createPaymentIntentWithPaymentMethod(
                $company,
                $amount,
                $paymentMethodId
            );
            
            if (!$paymentIntent) {
                throw new \Exception('Failed to create payment intent');
            }
            
            // Update topup with payment intent ID
            $topup->update([
                'stripe_payment_intent_id' => $paymentIntent->id,
                'status' => BalanceTopup::STATUS_PROCESSING,
            ]);
            
            // Check payment status
            if ($paymentIntent->status === 'succeeded') {
                // Mark as succeeded - this will also update the balance
                $topup->markAsSucceeded();
                
                // Get updated balance
                $prepaidBalance = app(PrepaidBillingService::class)->getOrCreateBalance($company);
                $newBalance = $prepaidBalance->getTotalBalance();
                
                // Calculate bonus if applicable
                $bonusCalc = app(PrepaidBillingService::class)->calculateBonus($amount, $company);
                $bonusAmount = $bonusCalc['bonus_amount'] ?? 0;
                
                // Get the transaction that was created
                $transaction = \App\Models\BalanceTransaction::where('company_id', $company->id)
                    ->where('reference_id', $topup->id)
                    ->where('type', 'topup')
                    ->first();
                
                // Create invoice for the successful topup
                $this->createTopupInvoice($topup);
                
                return [
                    'transaction' => $transaction,
                    'new_balance' => $newBalance,
                    'bonus_amount' => $bonusAmount,
                    'payment_intent' => $paymentIntent,
                ];
            } elseif ($paymentIntent->status === 'requires_action') {
                // Payment requires additional authentication
                return [
                    'requires_action' => true,
                    'payment_intent_client_secret' => $paymentIntent->client_secret,
                    'transaction' => null,
                    'new_balance' => null,
                    'bonus_amount' => 0,
                ];
            } else {
                // Payment failed
                $topup->markAsFailed('Payment intent status: ' . $paymentIntent->status);
                throw new \Exception('Payment failed: ' . $paymentIntent->status);
            }
            
        } catch (\Exception $e) {
            if (isset($topup) && $topup->status !== BalanceTopup::STATUS_SUCCEEDED) {
                $topup->markAsFailed($e->getMessage());
            }
            
            Log::error('Topup processing failed', [
                'company_id' => $company->id,
                'amount' => $amount,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            throw $e;
        }
    }
    
    /**
     * Create a Stripe Payment Link for balance topup
     */
    public function createPaymentLink(Company $company, float $amount = null, array $metadata = []): ?string
    {
        try {
            $stripe = new \Stripe\StripeClient(config('services.stripe.secret'));
            
            // For payment links, we need to create a price object first
            $lineItems = [];
            
            if ($amount) {
                // Fixed amount payment link - create one-time price
                $price = $stripe->prices->create([
                    'currency' => 'eur',
                    'unit_amount' => $amount * 100, // Stripe uses cents
                    'product_data' => [
                        'name' => 'Guthaben-Aufladung',
                        'metadata' => [
                            'company_id' => $company->id,
                            'company_name' => $company->name,
                        ],
                    ],
                ]);
                
                $lineItems[] = [
                    'price' => $price->id,
                    'quantity' => 1,
                ];
            } else {
                // For variable amounts, create a product first then use adjustable_quantity
                $product = $stripe->products->create([
                    'name' => 'Guthaben-Aufladung',
                    'description' => sprintf('Guthaben-Aufladung für %s', $company->name),
                    'metadata' => [
                        'company_id' => $company->id,
                        'company_name' => $company->name,
                    ],
                ]);
                
                // Create a price with €1 unit amount
                $price = $stripe->prices->create([
                    'currency' => 'eur',
                    'unit_amount' => 100, // €1 per unit
                    'product' => $product->id,
                ]);
                
                $lineItems[] = [
                    'price' => $price->id,
                    'adjustable_quantity' => [
                        'enabled' => true,
                        'minimum' => 10,  // €10 minimum
                        'maximum' => 5000, // €5000 maximum
                    ],
                ];
            }
            
            // Create payment link
            $paymentLink = $stripe->paymentLinks->create([
                'line_items' => $lineItems,
                'after_completion' => [
                    'type' => 'redirect',
                    'redirect' => [
                        'url' => route('public.topup.success', ['company' => $company->id])
                    ],
                ],
                'metadata' => array_merge([
                    'company_id' => $company->id,
                    'company_name' => $company->name,
                    'type' => 'balance_topup',
                ], $metadata),
                'payment_method_types' => ['card', 'sepa_debit'],
                'locale' => 'de',
                'invoice_creation' => [
                    'enabled' => true,
                ],
            ]);
            
            // Store payment link reference in company metadata or dedicated table
            $company->update([
                'metadata' => array_merge($company->metadata ?? [], [
                    'stripe_payment_link_id' => $paymentLink->id,
                    'stripe_payment_link_url' => $paymentLink->url,
                    'stripe_payment_link_created_at' => now()->toIso8601String(),
                ]),
            ]);
            
            Log::info('Stripe payment link created', [
                'company_id' => $company->id,
                'payment_link_id' => $paymentLink->id,
                'url' => $paymentLink->url,
            ]);
            
            return $paymentLink->url;
            
        } catch (ApiErrorException $e) {
            Log::error('Failed to create Stripe payment link', [
                'company_id' => $company->id,
                'amount' => $amount,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
}