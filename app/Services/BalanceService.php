<?php

namespace App\Services;

use App\Models\BalanceTopup;
use App\Models\BalanceBonusTier;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\PaymentIntent;
use Stripe\Refund;
use Stripe\Stripe;
use Stripe\Customer;
use Stripe\PaymentMethod;
use Exception;

class BalanceService
{
    protected $stripe;

    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    /**
     * Prozessiere eine Guthaben-Aufladung mit Bonus-Berechnung
     */
    public function processTopup(Tenant $tenant, float $amount, array $paymentData = []): BalanceTopup
    {
        return DB::transaction(function () use ($tenant, $amount, $paymentData) {
            // Berechne Bonus
            $bonusInfo = BalanceBonusTier::getBonusForAmount($amount);

            // Erstelle Aufladungseintrag
            $topup = BalanceTopup::create([
                'tenant_id' => $tenant->id,
                'amount' => $amount,
                'bonus_percentage' => $bonusInfo['percentage'],
                'bonus_amount' => $bonusInfo['bonus_amount'],
                'total_credited' => $bonusInfo['total_amount'],
                'refundable_amount' => $bonusInfo['refundable_amount'],
                'remaining_amount' => $bonusInfo['total_amount'],
                'bonus_remaining' => $bonusInfo['bonus_amount'],
                'payment_method' => $paymentData['payment_method'] ?? 'stripe',
                'status' => 'pending',
                'reference_number' => $this->generateReferenceNumber(),
                'stripe_payment_intent_id' => $paymentData['payment_intent_id'] ?? null,
                'stripe_customer_id' => $paymentData['customer_id'] ?? null,
                'transaction_date' => now(),
            ]);

            Log::info('Guthaben-Aufladung erstellt', [
                'tenant_id' => $tenant->id,
                'amount' => $amount,
                'bonus' => $bonusInfo['bonus_amount'],
                'total' => $bonusInfo['total_amount'],
                'tier' => $bonusInfo['tier_name'],
            ]);

            return $topup;
        });
    }

    /**
     * Bestätige eine erfolgreiche Zahlung und schreibe Guthaben gut
     */
    public function confirmPayment(BalanceTopup $topup, array $stripeData = []): bool
    {
        return DB::transaction(function () use ($topup, $stripeData) {
            // Update Topup Status
            $topup->update([
                'status' => 'completed',
                'stripe_charge_id' => $stripeData['charge_id'] ?? null,
                'stripe_metadata' => $stripeData['metadata'] ?? null,
                'processed_at' => now(),
            ]);

            // Update Tenant Guthaben
            $tenant = $topup->tenant;
            $tenant->increment('balance', $topup->amount);
            $tenant->increment('bonus_balance', $topup->bonus_amount);
            $tenant->increment('total_deposited', $topup->amount);
            $tenant->increment('total_bonus_received', $topup->bonus_amount);

            // Erstelle Balance-Transaktion für Audit
            DB::table('balance_transactions')->insert([
                [
                    'tenant_id' => $tenant->id,
                    'balance_topup_id' => $topup->id,
                    'type' => 'credit',
                    'amount' => $topup->amount,
                    'balance_before' => $tenant->balance - $topup->amount,
                    'balance_after' => $tenant->balance,
                    'bonus_balance_before' => $tenant->bonus_balance - $topup->bonus_amount,
                    'bonus_balance_after' => $tenant->bonus_balance,
                    'description' => "Guthaben-Aufladung #{$topup->reference_number}",
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'tenant_id' => $tenant->id,
                    'balance_topup_id' => $topup->id,
                    'type' => 'bonus',
                    'amount' => $topup->bonus_amount,
                    'balance_before' => $tenant->balance,
                    'balance_after' => $tenant->balance,
                    'bonus_balance_before' => $tenant->bonus_balance - $topup->bonus_amount,
                    'bonus_balance_after' => $tenant->bonus_balance,
                    'description' => "Bonus {$topup->bonus_percentage}% - {$this->getBonusTierName($topup->amount)}",
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            ]);

            Log::info('Zahlung bestätigt und Guthaben gutgeschrieben', [
                'topup_id' => $topup->id,
                'tenant_id' => $tenant->id,
                'credited' => $topup->total_credited,
            ]);

            return true;
        });
    }

    /**
     * Verbrauche Guthaben (Bonus wird zuerst verwendet)
     */
    public function consumeBalance(Tenant $tenant, float $amount, string $description, $relatedModel = null): bool
    {
        return DB::transaction(function () use ($tenant, $amount, $description, $relatedModel) {
            $totalBalance = $tenant->balance + $tenant->bonus_balance;

            if ($totalBalance < $amount) {
                throw new Exception("Unzureichendes Guthaben. Verfügbar: {$totalBalance}€, Benötigt: {$amount}€");
            }

            $balanceBefore = $tenant->balance;
            $bonusBalanceBefore = $tenant->bonus_balance;
            $remainingAmount = $amount;

            // Verwende zuerst Bonus-Guthaben
            if ($tenant->bonus_balance > 0) {
                $bonusToUse = min($tenant->bonus_balance, $remainingAmount);
                $tenant->decrement('bonus_balance', $bonusToUse);
                $remainingAmount -= $bonusToUse;

                // Update Topup Tracking
                $this->updateTopupUsage($tenant, $bonusToUse, true);
            }

            // Verwende normales Guthaben für den Rest
            if ($remainingAmount > 0) {
                $tenant->decrement('balance', $remainingAmount);
                $this->updateTopupUsage($tenant, $remainingAmount, false);
            }

            $tenant->increment('total_consumed', $amount);

            // Erstelle Transaktion
            $transaction = DB::table('balance_transactions')->insertGetId([
                'tenant_id' => $tenant->id,
                'type' => 'debit',
                'amount' => -$amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $tenant->balance,
                'bonus_balance_before' => $bonusBalanceBefore,
                'bonus_balance_after' => $tenant->bonus_balance,
                'description' => $description,
                'related_type' => $relatedModel ? get_class($relatedModel) : null,
                'related_id' => $relatedModel ? $relatedModel->id : null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Prüfe Low-Balance und sende ggf. Warnung
            $this->checkLowBalance($tenant);

            return true;
        });
    }

    /**
     * Prozessiere Rückerstattung (nur eingezahlter Betrag, kein Bonus)
     */
    public function processRefund(BalanceTopup $topup, float $refundAmount, string $reason): bool
    {
        return DB::transaction(function () use ($topup, $refundAmount, $reason) {
            // Validierung: Kann nur unverbrauchtes, eingezahltes Guthaben zurückerstattet werden
            $maxRefundable = min(
                $topup->refundable_amount - $topup->refunded_amount,
                $topup->amount - $topup->used_amount
            );

            if ($refundAmount > $maxRefundable) {
                throw new Exception("Maximal erstattungsfähig: {$maxRefundable}€ (ohne Bonus)");
            }

            // Stripe Refund
            if ($topup->stripe_charge_id) {
                try {
                    $refund = Refund::create([
                        'charge' => $topup->stripe_charge_id,
                        'amount' => $refundAmount * 100, // In Cents
                        'reason' => 'requested_by_customer',
                        'metadata' => [
                            'topup_id' => $topup->id,
                            'reason' => $reason,
                        ],
                    ]);

                    $topup->stripe_refund_id = $refund->id;
                } catch (Exception $e) {
                    Log::error('Stripe Refund fehlgeschlagen', [
                        'topup_id' => $topup->id,
                        'error' => $e->getMessage(),
                    ]);
                    throw $e;
                }
            }

            // Update Topup
            $topup->refunded_amount += $refundAmount;
            $topup->refund_reason = $reason;
            $topup->refunded_at = now();
            $topup->refund_status = $topup->refunded_amount >= $topup->amount ? 'full' : 'partial';
            $topup->save();

            // Update Tenant Guthaben (ziehe nur normales Guthaben ab, nicht Bonus)
            $tenant = $topup->tenant;
            $tenant->decrement('balance', $refundAmount);
            $tenant->increment('total_refunded', $refundAmount);

            // Wenn vollständig erstattet, entferne auch den Bonus
            if ($topup->refund_status === 'full' && $topup->bonus_remaining > 0) {
                $tenant->decrement('bonus_balance', $topup->bonus_remaining);
                $topup->bonus_remaining = 0;
                $topup->save();
            }

            // Erstelle Refund-Transaktion
            DB::table('balance_transactions')->insert([
                'tenant_id' => $tenant->id,
                'balance_topup_id' => $topup->id,
                'type' => 'refund',
                'amount' => -$refundAmount,
                'balance_before' => $tenant->balance + $refundAmount,
                'balance_after' => $tenant->balance,
                'bonus_balance_before' => $tenant->bonus_balance,
                'bonus_balance_after' => $tenant->bonus_balance,
                'description' => "Rückerstattung #{$topup->reference_number}: {$reason}",
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            Log::info('Rückerstattung prozessiert', [
                'topup_id' => $topup->id,
                'refunded' => $refundAmount,
                'reason' => $reason,
            ]);

            return true;
        });
    }

    /**
     * Erstelle Stripe PaymentIntent für Aufladung
     */
    public function createStripePaymentIntent(Tenant $tenant, float $amount): PaymentIntent
    {
        // Get or create Stripe Customer
        $stripeCustomer = $this->getOrCreateStripeCustomer($tenant);

        // Create PaymentIntent
        $paymentIntent = PaymentIntent::create([
            'amount' => $amount * 100, // In Cents
            'currency' => 'eur',
            'customer' => $stripeCustomer->id,
            'metadata' => [
                'tenant_id' => $tenant->id,
                'tenant_name' => $tenant->name,
                'type' => 'balance_topup',
            ],
            'description' => "Guthaben-Aufladung für {$tenant->name}",
            'statement_descriptor' => 'ASKPRO GUTHABEN',
        ]);

        return $paymentIntent;
    }

    /**
     * Get oder erstelle Stripe Customer
     */
    protected function getOrCreateStripeCustomer(Tenant $tenant)
    {
        $stripeCustomerRecord = DB::table('stripe_customers')
            ->where('tenant_id', $tenant->id)
            ->first();

        if ($stripeCustomerRecord) {
            return Customer::retrieve($stripeCustomerRecord->stripe_customer_id);
        }

        // Create new Stripe Customer
        $customer = Customer::create([
            'email' => $tenant->billing_email ?? $tenant->email,
            'name' => $tenant->name,
            'metadata' => [
                'tenant_id' => $tenant->id,
            ],
            'address' => [
                'line1' => $tenant->billing_address ?? '',
                'country' => 'DE',
            ],
        ]);

        // Save to database
        DB::table('stripe_customers')->insert([
            'tenant_id' => $tenant->id,
            'stripe_customer_id' => $customer->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $customer;
    }

    /**
     * Update Topup Usage Tracking
     */
    protected function updateTopupUsage(Tenant $tenant, float $amount, bool $isBonus): void
    {
        // Finde älteste Aufladung mit verbleibendem Guthaben
        $topups = BalanceTopup::where('tenant_id', $tenant->id)
            ->where('status', 'completed')
            ->where(function ($query) use ($isBonus) {
                if ($isBonus) {
                    $query->where('bonus_remaining', '>', 0);
                } else {
                    $query->where('remaining_amount', '>', 0);
                }
            })
            ->orderBy('created_at')
            ->get();

        $remainingToDeduct = $amount;

        foreach ($topups as $topup) {
            if ($remainingToDeduct <= 0) break;

            if ($isBonus) {
                $available = $topup->bonus_remaining;
                $toDeduct = min($available, $remainingToDeduct);
                $topup->bonus_remaining -= $toDeduct;
                $topup->bonus_used += $toDeduct;
            } else {
                $available = $topup->remaining_amount - $topup->bonus_remaining;
                $toDeduct = min($available, $remainingToDeduct);
                $topup->remaining_amount -= $toDeduct;
                $topup->used_amount += $toDeduct;
            }

            $topup->save();
            $remainingToDeduct -= $toDeduct;
        }
    }

    /**
     * Prüfe niedriges Guthaben und sende Warnung
     */
    protected function checkLowBalance(Tenant $tenant): void
    {
        $totalBalance = $tenant->balance + $tenant->bonus_balance;

        if ($totalBalance <= $tenant->low_balance_threshold) {
            // Sende Benachrichtigung nur alle 24h
            if (!$tenant->last_low_balance_notification ||
                $tenant->last_low_balance_notification < now()->subDay()) {

                // TODO: Sende E-Mail oder Notification
                Log::warning('Niedriges Guthaben', [
                    'tenant_id' => $tenant->id,
                    'balance' => $totalBalance,
                    'threshold' => $tenant->low_balance_threshold,
                ]);

                $tenant->last_low_balance_notification = now();
                $tenant->save();
            }
        }
    }

    /**
     * Generiere eindeutige Referenznummer
     */
    protected function generateReferenceNumber(): string
    {
        do {
            $reference = 'TOP-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
        } while (BalanceTopup::where('reference_number', $reference)->exists());

        return $reference;
    }

    /**
     * Get Bonus Tier Name für Betrag
     */
    protected function getBonusTierName(float $amount): string
    {
        $bonusInfo = BalanceBonusTier::getBonusForAmount($amount);
        return $bonusInfo['tier_name'] ?? 'Standard';
    }
}