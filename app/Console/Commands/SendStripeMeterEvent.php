<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Stripe\StripeClient;

class SendStripeMeterEvent extends Command
{
    protected $signature = 'stripe:meter
                            {subscription_item : z.B. si_SGyycOxUig4SRf}
                            {quantity          : Nutzungseinheiten (Minuten)}
                            {--timestamp=      : Unix-Timestamp (Default: jetzt)}';

    protected $description = 'Sendet ein Meter-Event an Stripe (Billing Meter Events API)';

    /**  Konstante: Stripe-Ereignisname des Zählers  */
    private const EVENT_NAME = 'call-minutes';

    /**  Konstante: Key, mit dem Stripe den Kunden zuordnet  */
    private const CUSTOMER_KEY = 'stripe_customer_id';

    public function handle(): int
    {
        // 1 · Secret Key besorgen
        $secret = config('services.stripe.secret') ?: env('STRIPE_SECRET');
        if (! $secret) {
            $this->error('❌  STRIPE_SECRET fehlt – .env prüfen!');
            return self::FAILURE;
        }

        // 2 · Basisinfos aus CLI-Argumenten
        $itemId    = $this->argument('subscription_item');
        $value     = (int) $this->argument('quantity');
        $timestamp = (int) ($this->option('timestamp') ?: time());

        // 3 · Kunde aus Subscription-Item ermitteln
        $stripe     = new StripeClient($secret);
        $customerId = $this->resolveCustomerId($stripe, $itemId);

        if (! $customerId) {
            $this->error('❌  Kunde zum übergebenen subscription_item nicht gefunden.');
            return self::FAILURE;
        }

        // 4 · Meter-Event absetzen
        try {
            $event = $stripe->billing->meterEvents->create([
                'event_name' => self::EVENT_NAME,
                'timestamp'  => $timestamp,
                'payload'    => [
                    self::CUSTOMER_KEY => $customerId,
                    'value'            => $value,
                ],
            ]);

            $this->info('✅  MeterEvent gesendet  →  '.$event->id);
            return self::SUCCESS;

        } catch (\Throwable $e) {
            $this->error('❌  Stripe-Fehler: '.$e->getMessage());
            return self::FAILURE;
        }
    }

    /**
     * Holt zum gegebenen Subscription-Item die zugehörige stripe_customer_id.
     */
    private function resolveCustomerId(StripeClient $stripe, string $itemId): ?string
    {
        try {
            // subscription + customer in einem Rutsch mit expand holen
            $subItem = $stripe->subscriptionItems->retrieve(
                $itemId,
                ['expand' => ['subscription.customer']]
            );

            // subscription ist hier bereits ein Objekt
            return $subItem->subscription->customer ?? null;

        } catch (\Throwable $e) {
            // Fehler protokollieren, aber nicht abstürzen
            $this->error('⚠️  Kunde konnte nicht ermittelt werden: '.$e->getMessage());
            return null;
        }
    }
}
