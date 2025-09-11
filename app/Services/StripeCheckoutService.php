<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\BalanceTopup;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Stripe\StripeClient;
use Stripe\Checkout\Session;
use Stripe\PaymentIntent;

class StripeCheckoutService
{
    private StripeClient $stripe;
    private int $lockTimeout = 30; // seconds
    
    public function __construct()
    {
        $this->stripe = new StripeClient(config('services.stripe.secret'));
    }
    
    /**
     * Create a checkout session with idempotency and race condition prevention
     * 
     * This addresses the critical payment race condition issue identified in ULTRATHINK
     */
    public function createTopupSession(Tenant $tenant, int $amountCents, array $options = []): array
    {
        // Generate or use provided idempotency key
        $idempotencyKey = $options['idempotency_key'] ?? $this->generateIdempotencyKey($tenant, $amountCents);
        
        // Check idempotency cache first
        $cachedResult = Cache::get("stripe.checkout.{$idempotencyKey}");
        if ($cachedResult) {
            Log::info("Returning cached checkout session", [
                'tenant_id' => $tenant->id,
                'idempotency_key' => $idempotencyKey
            ]);
            return $cachedResult;
        }
        
        // Acquire lock to prevent race conditions
        $lock = Cache::lock("stripe.checkout.lock.{$tenant->id}", $this->lockTimeout);
        
        if (!$lock->get()) {
            throw new \Exception('Another payment is being processed. Please try again in a moment.');
        }
        
        try {
            // Double-check idempotency after acquiring lock
            $cachedResult = Cache::get("stripe.checkout.{$idempotencyKey}");
            if ($cachedResult) {
                return $cachedResult;
            }
            
            // Calculate bonus if applicable
            $bonusAmount = $this->calculateBonus($amountCents);
            $totalCredit = $amountCents + $bonusAmount;
            
            // Create pending topup record
            $topup = DB::transaction(function () use ($tenant, $amountCents, $bonusAmount, $totalCredit) {
                return BalanceTopup::create([
                    'tenant_id' => $tenant->id,
                    'amount' => $amountCents / 100,
                    'bonus_amount' => $bonusAmount / 100,
                    'currency' => 'EUR',
                    'status' => 'pending',
                    'initiated_by' => auth()->id(),
                    'metadata' => [
                        'ip_address' => request()->ip(),
                        'user_agent' => request()->userAgent(),
                        'idempotency_key' => request()->header('Idempotency-Key')
                    ]
                ]);
            });
            
            // Create Stripe checkout session
            $session = $this->stripe->checkout->sessions->create([
                'payment_method_types' => ['card', 'sepa_debit'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'eur',
                        'product_data' => [
                            'name' => 'Guthaben-Aufladung',
                            'description' => $bonusAmount > 0 
                                ? "Aufladung + {$bonusAmount}€ Bonus"
                                : "Guthaben-Aufladung für {$tenant->name}",
                            'metadata' => [
                                'tenant_id' => $tenant->id,
                                'topup_id' => $topup->id
                            ]
                        ],
                        'unit_amount' => $amountCents,
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => route('customer.billing.success') . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('customer.billing.cancel'),
                'customer_email' => auth()->user()->email,
                'client_reference_id' => $tenant->id,
                'metadata' => [
                    'tenant_id' => $tenant->id,
                    'topup_id' => $topup->id,
                    'bonus_amount' => $bonusAmount,
                    'total_credit' => $totalCredit,
                    'reseller_id' => $tenant->parent_id,
                    'idempotency_key' => $idempotencyKey
                ],
                'payment_intent_data' => [
                    'metadata' => [
                        'tenant_id' => $tenant->id,
                        'topup_id' => $topup->id
                    ]
                ],
                'locale' => 'de',
                'expires_at' => now()->addMinutes(30)->timestamp
            ], [
                'idempotency_key' => $idempotencyKey
            ]);
            
            // Update topup with Stripe session ID
            $topup->update([
                'stripe_checkout_session_id' => $session->id
            ]);
            
            $result = [
                'session_id' => $session->id,
                'checkout_url' => $session->url,
                'topup_id' => $topup->id,
                'amount' => $amountCents,
                'bonus' => $bonusAmount,
                'total_credit' => $totalCredit,
                'expires_at' => now()->addMinutes(30)->toIso8601String()
            ];
            
            // Cache result for idempotency
            Cache::put("stripe.checkout.{$idempotencyKey}", $result, 1800); // 30 minutes
            
            Log::info("Stripe checkout session created", [
                'tenant_id' => $tenant->id,
                'session_id' => $session->id,
                'amount' => $amountCents
            ]);
            
            return $result;
            
        } finally {
            $lock->release();
        }
    }
    
    /**
     * Process successful payment webhook with double-spending prevention
     */
    public function processSuccessfulPayment(string $sessionId): void
    {
        // Retrieve session from Stripe
        $session = $this->stripe->checkout->sessions->retrieve($sessionId, [
            'expand' => ['payment_intent', 'line_items']
        ]);
        
        if ($session->payment_status !== 'paid') {
            Log::warning("Payment session not paid", ['session_id' => $sessionId]);
            return;
        }
        
        $topupId = $session->metadata->topup_id ?? null;
        if (!$topupId) {
            Log::error("No topup_id in session metadata", ['session_id' => $sessionId]);
            return;
        }
        
        // Use pessimistic locking to prevent double processing
        DB::transaction(function () use ($session, $topupId) {
            $topup = BalanceTopup::lockForUpdate()->find($topupId);
            
            if (!$topup) {
                Log::error("Topup not found", ['topup_id' => $topupId]);
                return;
            }
            
            // Check if already processed (idempotency)
            if ($topup->status === 'succeeded') {
                Log::info("Payment already processed", ['topup_id' => $topupId]);
                return;
            }
            
            // Update topup status
            $topup->update([
                'status' => 'succeeded',
                'stripe_payment_intent_id' => $session->payment_intent,
                'paid_at' => now(),
                'payment_method' => $session->payment_method_types[0] ?? 'card',
                'stripe_response' => $session->toArray()
            ]);
            
            // Add balance to tenant with atomic operation
            $tenant = Tenant::lockForUpdate()->find($topup->tenant_id);
            $oldBalance = $tenant->balance_cents;
            $creditAmount = ($topup->amount + $topup->bonus_amount) * 100;
            
            $tenant->increment('balance_cents', $creditAmount);
            
            // Create transaction record
            Transaction::create([
                'tenant_id' => $tenant->id,
                'type' => 'topup',
                'amount_cents' => $creditAmount,
                'balance_before_cents' => $oldBalance,
                'balance_after_cents' => $oldBalance + $creditAmount,
                'description' => $topup->bonus_amount > 0 
                    ? "Aufladung {$topup->amount}€ + Bonus {$topup->bonus_amount}€"
                    : "Guthaben-Aufladung {$topup->amount}€",
                'topup_id' => $topup->id,
                'reference' => $session->payment_intent,
                'status' => 'completed'
            ]);
            
            // Track commission if reseller customer
            if ($tenant->parent_id && $tenant->tenant_type === 'reseller_customer') {
                $commissionService = new CommissionCalculator();
                $commissionService->calculateTopupCommission($topup);
            }
            
            // Clear balance cache
            Cache::forget("balance.tenant.{$tenant->id}");
            
            // Trigger balance update event for SSE
            event(new \App\Events\BalanceUpdated($tenant, $creditAmount));
            
            Log::info("Payment processed successfully", [
                'tenant_id' => $tenant->id,
                'topup_id' => $topup->id,
                'amount' => $creditAmount
            ]);
        });
    }
    
    /**
     * Handle payment cancellation
     */
    public function processCancelledPayment(string $sessionId): void
    {
        $session = $this->stripe->checkout->sessions->retrieve($sessionId);
        
        $topupId = $session->metadata->topup_id ?? null;
        if (!$topupId) {
            return;
        }
        
        BalanceTopup::where('id', $topupId)
            ->where('status', 'pending')
            ->update(['status' => 'cancelled']);
        
        Log::info("Payment cancelled", ['session_id' => $sessionId, 'topup_id' => $topupId]);
    }
    
    /**
     * Calculate bonus based on amount
     */
    private function calculateBonus(int $amountCents): int
    {
        // Bonus tiers
        if ($amountCents >= 10000) { // 100€+
            return 1000; // 10€ bonus
        } elseif ($amountCents >= 5000) { // 50€+
            return 400; // 4€ bonus
        } elseif ($amountCents >= 2500) { // 25€+
            return 150; // 1.50€ bonus
        }
        
        return 0;
    }
    
    /**
     * Generate idempotency key
     */
    private function generateIdempotencyKey(Tenant $tenant, int $amount): string
    {
        return hash('sha256', implode('-', [
            $tenant->id,
            auth()->id(),
            $amount,
            now()->format('Y-m-d-H'),
            Str::random(8)
        ]));
    }
    
    /**
     * Verify webhook signature
     */
    public function verifyWebhookSignature(string $payload, string $signature): bool
    {
        $secret = config('services.stripe.webhook_secret');
        
        try {
            \Stripe\Webhook::constructEvent($payload, $signature, $secret);
            return true;
        } catch (\Exception $e) {
            Log::error("Invalid webhook signature", ['error' => $e->getMessage()]);
            return false;
        }
    }
}