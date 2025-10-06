<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Transaction;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Appointment;
use App\Models\BalanceTopup;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\Customer as StripeCustomer;
use Stripe\PaymentMethod as StripePaymentMethod;
use Stripe\Refund;
use PayPal\Rest\ApiContext;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Api\Payment as PayPalPayment;
use PayPal\Api\PaymentExecution;
use PayPal\Api\Amount;
use PayPal\Api\Transaction as PayPalTransaction;
use PayPal\Api\Payer;
use PayPal\Api\RedirectUrls;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    protected array $gateways = [];
    protected $stripeClient;
    protected $paypalContext;

    public function __construct()
    {
        $this->initializeGateways();
    }

    /**
     * Initialize payment gateways
     */
    protected function initializeGateways(): void
    {
        // Stripe
        if (config('services.stripe.secret')) {
            Stripe::setApiKey(config('services.stripe.secret'));
            $this->stripeClient = new \Stripe\StripeClient(config('services.stripe.secret'));
            $this->gateways['stripe'] = true;
        }

        // PayPal
        if (config('services.paypal.client_id') && config('services.paypal.secret')) {
            $this->paypalContext = new ApiContext(
                new OAuthTokenCredential(
                    config('services.paypal.client_id'),
                    config('services.paypal.secret')
                )
            );

            $this->paypalContext->setConfig([
                'mode' => config('services.paypal.mode', 'sandbox'),
                'http.ConnectionTimeOut' => 30,
                'log.LogEnabled' => true,
                'log.FileName' => storage_path('logs/paypal.log'),
                'log.LogLevel' => 'ERROR'
            ]);

            $this->gateways['paypal'] = true;
        }

        // Additional gateways can be added here
        $this->gateways['bank_transfer'] = true;
        $this->gateways['cash'] = true;
        $this->gateways['sepa'] = config('services.sepa.enabled', false);
    }

    /**
     * Process payment for appointment
     */
    public function processAppointmentPayment(Appointment $appointment, array $paymentData): PaymentResult
    {
        DB::beginTransaction();

        try {
            $customer = $appointment->customer;
            $amount = $appointment->price;

            // Check if payment is required
            if ($amount <= 0) {
                return new PaymentResult(true, 'No payment required');
            }

            // Process based on payment method
            $result = match($paymentData['method']) {
                'stripe' => $this->processStripePayment($customer, $amount, $paymentData),
                'paypal' => $this->processPayPalPayment($customer, $amount, $paymentData),
                'bank_transfer' => $this->processBankTransfer($customer, $amount, $paymentData),
                'cash' => $this->processCashPayment($customer, $amount, $paymentData),
                'balance' => $this->processBalancePayment($customer, $amount, $paymentData),
                default => throw new \Exception('Invalid payment method')
            };

            if ($result->success) {
                // Create payment record
                $payment = Payment::create([
                    'customer_id' => $customer->id,
                    'appointment_id' => $appointment->id,
                    'amount' => $amount,
                    'currency' => $paymentData['currency'] ?? 'EUR',
                    'method' => $paymentData['method'],
                    'status' => 'completed',
                    'transaction_id' => $result->transactionId,
                    'gateway_response' => $result->gatewayResponse,
                    'processed_at' => now()
                ]);

                // Update appointment
                $appointment->update([
                    'payment_status' => 'paid',
                    'payment_id' => $payment->id
                ]);

                // Create transaction record
                $this->createTransaction(
                    'payment',
                    $amount,
                    $customer,
                    $payment,
                    "Zahlung für Termin #{$appointment->id}"
                );

                DB::commit();

                // Send payment confirmation
                $this->sendPaymentConfirmation($payment);

                return $result;
            }

            DB::rollBack();
            return $result;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Payment processing failed', [
                'appointment_id' => $appointment->id,
                'error' => $e->getMessage()
            ]);

            return new PaymentResult(false, $e->getMessage());
        }
    }

    /**
     * Process Stripe payment
     */
    protected function processStripePayment(Customer $customer, float $amount, array $data): PaymentResult
    {
        try {
            // Get or create Stripe customer
            $stripeCustomer = $this->getOrCreateStripeCustomer($customer);

            // Create payment intent
            $paymentIntent = PaymentIntent::create([
                'amount' => (int)($amount * 100), // Amount in cents
                'currency' => $data['currency'] ?? 'eur',
                'customer' => $stripeCustomer->id,
                'payment_method' => $data['payment_method_id'],
                'confirmation_method' => 'manual',
                'confirm' => true,
                'description' => $data['description'] ?? 'Appointment payment',
                'metadata' => [
                    'customer_id' => $customer->id,
                    'appointment_id' => $data['appointment_id'] ?? null
                ]
            ]);

            if ($paymentIntent->status === 'succeeded') {
                return new PaymentResult(
                    success: true,
                    message: 'Payment successful',
                    transactionId: $paymentIntent->id,
                    gatewayResponse: $paymentIntent->toArray()
                );
            }

            if ($paymentIntent->status === 'requires_action') {
                return new PaymentResult(
                    success: false,
                    message: 'Payment requires additional authentication',
                    requiresAction: true,
                    clientSecret: $paymentIntent->client_secret
                );
            }

            return new PaymentResult(
                success: false,
                message: 'Payment failed: ' . $paymentIntent->status
            );

        } catch (\Stripe\Exception\ApiErrorException $e) {
            Log::error('Stripe payment failed', [
                'error' => $e->getMessage(),
                'code' => $e->getStripeCode()
            ]);

            return new PaymentResult(
                success: false,
                message: 'Stripe payment failed: ' . $e->getMessage()
            );
        }
    }

    /**
     * Process PayPal payment
     */
    protected function processPayPalPayment(Customer $customer, float $amount, array $data): PaymentResult
    {
        try {
            $payer = new Payer();
            $payer->setPaymentMethod('paypal');

            $amountObj = new Amount();
            $amountObj->setTotal($amount)
                ->setCurrency($data['currency'] ?? 'EUR');

            $transaction = new PayPalTransaction();
            $transaction->setAmount($amountObj)
                ->setDescription($data['description'] ?? 'Appointment payment');

            $redirectUrls = new RedirectUrls();
            $redirectUrls->setReturnUrl(route('payment.paypal.success'))
                ->setCancelUrl(route('payment.paypal.cancel'));

            $payment = new PayPalPayment();
            $payment->setIntent('sale')
                ->setPayer($payer)
                ->setTransactions([$transaction])
                ->setRedirectUrls($redirectUrls);

            $payment->create($this->paypalContext);

            return new PaymentResult(
                success: false,
                message: 'Redirect to PayPal',
                requiresAction: true,
                redirectUrl: $payment->getApprovalLink()
            );

        } catch (\Exception $e) {
            Log::error('PayPal payment failed', [
                'error' => $e->getMessage()
            ]);

            return new PaymentResult(
                success: false,
                message: 'PayPal payment failed: ' . $e->getMessage()
            );
        }
    }

    /**
     * Execute PayPal payment after approval
     */
    public function executePayPalPayment(string $paymentId, string $payerId): PaymentResult
    {
        try {
            $payment = PayPalPayment::get($paymentId, $this->paypalContext);

            $execution = new PaymentExecution();
            $execution->setPayerId($payerId);

            $result = $payment->execute($execution, $this->paypalContext);

            if ($result->getState() === 'approved') {
                return new PaymentResult(
                    success: true,
                    message: 'PayPal payment successful',
                    transactionId: $result->getId(),
                    gatewayResponse: $result->toArray()
                );
            }

            return new PaymentResult(
                success: false,
                message: 'PayPal payment not approved'
            );

        } catch (\Exception $e) {
            Log::error('PayPal execution failed', [
                'payment_id' => $paymentId,
                'error' => $e->getMessage()
            ]);

            return new PaymentResult(
                success: false,
                message: 'PayPal execution failed: ' . $e->getMessage()
            );
        }
    }

    /**
     * Process balance payment
     */
    protected function processBalancePayment(Customer $customer, float $amount, array $data): PaymentResult
    {
        if ($customer->balance < $amount) {
            return new PaymentResult(
                success: false,
                message: 'Insufficient balance',
                currentBalance: $customer->balance,
                requiredAmount: $amount
            );
        }

        // Deduct from balance
        $customer->decrement('balance', $amount);

        return new PaymentResult(
            success: true,
            message: 'Payment successful using balance',
            transactionId: 'BAL-' . uniqid(),
            newBalance: $customer->balance
        );
    }

    /**
     * Process balance topup
     */
    public function processBalanceTopup(Customer $customer, float $amount, array $paymentData): PaymentResult
    {
        DB::beginTransaction();

        try {
            // Process payment first
            $result = match($paymentData['method']) {
                'stripe' => $this->processStripePayment($customer, $amount, $paymentData),
                'paypal' => $this->processPayPalPayment($customer, $amount, $paymentData),
                'bank_transfer' => $this->processBankTransfer($customer, $amount, $paymentData),
                default => throw new \Exception('Invalid topup method')
            };

            if ($result->success) {
                // Calculate bonus
                $bonus = $this->calculateTopupBonus($amount);
                $totalCredit = $amount + $bonus;

                // Create topup record
                $topup = BalanceTopup::create([
                    'customer_id' => $customer->id,
                    'amount' => $amount,
                    'bonus' => $bonus,
                    'total_credit' => $totalCredit,
                    'payment_method' => $paymentData['method'],
                    'transaction_id' => $result->transactionId,
                    'status' => 'completed',
                    'processed_at' => now()
                ]);

                // Update customer balance
                $customer->increment('balance', $totalCredit);

                // Create transaction
                $this->createTransaction(
                    'topup',
                    $totalCredit,
                    $customer,
                    $topup,
                    "Guthabenaufladung + {$bonus}€ Bonus"
                );

                DB::commit();

                // Send confirmation
                $this->sendTopupConfirmation($topup);

                return new PaymentResult(
                    success: true,
                    message: 'Balance topped up successfully',
                    transactionId: $result->transactionId,
                    newBalance: $customer->balance,
                    bonusReceived: $bonus
                );
            }

            DB::rollBack();
            return $result;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Balance topup failed', [
                'customer_id' => $customer->id,
                'error' => $e->getMessage()
            ]);

            return new PaymentResult(false, $e->getMessage());
        }
    }

    /**
     * Process refund
     */
    public function processRefund(Payment $payment, float $amount = null, string $reason = null): RefundResult
    {
        if ($payment->status !== 'completed') {
            return new RefundResult(false, 'Payment not completed');
        }

        $amount = $amount ?? $payment->amount;

        if ($amount > $payment->amount - $payment->refunded_amount) {
            return new RefundResult(false, 'Refund amount exceeds payment amount');
        }

        DB::beginTransaction();

        try {
            $result = match($payment->method) {
                'stripe' => $this->processStripeRefund($payment, $amount),
                'paypal' => $this->processPayPalRefund($payment, $amount),
                'balance' => $this->processBalanceRefund($payment, $amount),
                default => new RefundResult(false, 'Refund not supported for this payment method')
            };

            if ($result->success) {
                // Update payment record
                $payment->increment('refunded_amount', $amount);
                if ($payment->refunded_amount >= $payment->amount) {
                    $payment->update(['status' => 'refunded']);
                } else {
                    $payment->update(['status' => 'partially_refunded']);
                }

                // Create refund transaction
                $this->createTransaction(
                    'refund',
                    -$amount,
                    $payment->customer,
                    $payment,
                    $reason ?? 'Refund for payment #' . $payment->id
                );

                DB::commit();

                // Send refund confirmation
                $this->sendRefundConfirmation($payment, $amount);

                return $result;
            }

            DB::rollBack();
            return $result;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Refund processing failed', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage()
            ]);

            return new RefundResult(false, $e->getMessage());
        }
    }

    /**
     * Process Stripe refund
     */
    protected function processStripeRefund(Payment $payment, float $amount): RefundResult
    {
        try {
            $refund = Refund::create([
                'payment_intent' => $payment->transaction_id,
                'amount' => (int)($amount * 100),
                'reason' => 'requested_by_customer'
            ]);

            return new RefundResult(
                success: true,
                message: 'Refund processed successfully',
                refundId: $refund->id,
                amount: $amount
            );

        } catch (\Stripe\Exception\ApiErrorException $e) {
            Log::error('Stripe refund failed', [
                'error' => $e->getMessage()
            ]);

            return new RefundResult(
                success: false,
                message: 'Stripe refund failed: ' . $e->getMessage()
            );
        }
    }

    /**
     * Create or get Stripe customer
     */
    protected function getOrCreateStripeCustomer(Customer $customer): StripeCustomer
    {
        if ($customer->stripe_customer_id) {
            try {
                return StripeCustomer::retrieve($customer->stripe_customer_id);
            } catch (\Exception $e) {
                Log::warning('Failed to retrieve Stripe customer', [
                    'customer_id' => $customer->id,
                    'stripe_id' => $customer->stripe_customer_id
                ]);
            }
        }

        // Create new Stripe customer
        $stripeCustomer = StripeCustomer::create([
            'email' => $customer->email,
            'phone' => $customer->phone,
            'name' => $customer->full_name,
            'metadata' => [
                'customer_id' => $customer->id,
                'company_id' => $customer->company_id
            ]
        ]);

        $customer->update(['stripe_customer_id' => $stripeCustomer->id]);

        return $stripeCustomer;
    }

    /**
     * Save payment method for customer
     */
    public function savePaymentMethod(Customer $customer, array $methodData): PaymentMethod
    {
        $paymentMethod = PaymentMethod::create([
            'customer_id' => $customer->id,
            'type' => $methodData['type'],
            'provider' => $methodData['provider'],
            'provider_id' => $methodData['provider_id'] ?? null,
            'last_four' => $methodData['last_four'] ?? null,
            'brand' => $methodData['brand'] ?? null,
            'exp_month' => $methodData['exp_month'] ?? null,
            'exp_year' => $methodData['exp_year'] ?? null,
            'is_default' => $methodData['is_default'] ?? false,
            'metadata' => $methodData['metadata'] ?? []
        ]);

        if ($paymentMethod->is_default) {
            // Remove default from other methods
            PaymentMethod::where('customer_id', $customer->id)
                ->where('id', '!=', $paymentMethod->id)
                ->update(['is_default' => false]);
        }

        return $paymentMethod;
    }

    /**
     * Create transaction record
     */
    protected function createTransaction(
        string $type,
        float $amount,
        Customer $customer,
        $relatedModel,
        string $description
    ): Transaction {
        return Transaction::create([
            'customer_id' => $customer->id,
            'type' => $type,
            'amount' => $amount,
            'balance_before' => $customer->balance - $amount,
            'balance_after' => $customer->balance,
            'description' => $description,
            'related_type' => get_class($relatedModel),
            'related_id' => $relatedModel->id,
            'created_at' => now()
        ]);
    }

    /**
     * Calculate topup bonus based on amount
     */
    protected function calculateTopupBonus(float $amount): float
    {
        $bonusTiers = config('payment.bonus_tiers', [
            ['min' => 100, 'bonus' => 10],
            ['min' => 200, 'bonus' => 25],
            ['min' => 500, 'bonus' => 75],
            ['min' => 1000, 'bonus' => 200]
        ]);

        $bonus = 0;
        foreach ($bonusTiers as $tier) {
            if ($amount >= $tier['min']) {
                $bonus = $tier['bonus'];
            }
        }

        return $bonus;
    }

    /**
     * Send payment confirmation
     */
    protected function sendPaymentConfirmation(Payment $payment): void
    {
        // Implementation would send email/SMS confirmation
        Log::info('Payment confirmation sent', ['payment_id' => $payment->id]);
    }

    /**
     * Send topup confirmation
     */
    protected function sendTopupConfirmation(BalanceTopup $topup): void
    {
        // Implementation would send email/SMS confirmation
        Log::info('Topup confirmation sent', ['topup_id' => $topup->id]);
    }

    /**
     * Send refund confirmation
     */
    protected function sendRefundConfirmation(Payment $payment, float $amount): void
    {
        // Implementation would send email/SMS confirmation
        Log::info('Refund confirmation sent', [
            'payment_id' => $payment->id,
            'amount' => $amount
        ]);
    }

    /**
     * Get payment statistics
     */
    public function getPaymentStatistics(array $filters = []): array
    {
        $query = Payment::query();

        if (!empty($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        if (!empty($filters['company_id'])) {
            $query->whereHas('customer', function ($q) use ($filters) {
                $q->where('company_id', $filters['company_id']);
            });
        }

        return [
            'total_revenue' => $query->sum('amount'),
            'total_payments' => $query->count(),
            'average_payment' => $query->avg('amount'),
            'by_method' => $query->groupBy('method')
                ->selectRaw('method, count(*) as count, sum(amount) as total')
                ->get(),
            'by_status' => $query->groupBy('status')
                ->selectRaw('status, count(*) as count, sum(amount) as total')
                ->get()
        ];
    }
}

// Result classes
class PaymentResult
{
    public function __construct(
        public bool $success,
        public string $message = '',
        public ?string $transactionId = null,
        public ?array $gatewayResponse = null,
        public bool $requiresAction = false,
        public ?string $redirectUrl = null,
        public ?string $clientSecret = null,
        public ?float $newBalance = null,
        public ?float $bonusReceived = null,
        public ?float $currentBalance = null,
        public ?float $requiredAmount = null
    ) {}
}

class RefundResult
{
    public function __construct(
        public bool $success,
        public string $message = '',
        public ?string $refundId = null,
        public ?float $amount = null
    ) {}
}