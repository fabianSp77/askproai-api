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

    /**  Stripe-Ereignisname des Zählers  */
    private const EVENT_NAME   = 'call-minutes';

    /**  Key, mit dem Stripe den Kunden zuordnet  */
    private const CUSTOMER_KEY = 'stripe_customer_id';

    public function handle(): int
    {
        /* 1 ▸ Secret Key holen  */
        $secret = config('services.stripe.secret') ?: env('STRIPE_SECRET');
        if (! $secret) {
            $this->error('❌  STRIPE_SECRET fehlt – .env prüfen!');
            return self::FAILURE;
        }

        /* 2 ▸ CLI-Argumente  */
        $itemId    = $this->argument('subscription_item');
        $value     = (int) $this->argument('quantity');
        $timestamp = (int) ($this->option('timestamp') ?: time());

        /* 3 ▸ Kunde auflösen  */
        $stripe     = new StripeClient($secret);
        $customerId = $this->resolveCustomerId($stripe, $itemId);
        if (! $customerId) {
            $this->error('❌  Kunde zum übergebenen subscription_item nicht gefunden.');
            return self::FAILURE;
        }

        /* 4 ▸ Meter-Event senden  */
        try {
            $event = $stripe->billing->meterEvents->create([
                'event_name' => self::EVENT_NAME,
                'timestamp'  => $timestamp,
                'payload'    => [
                    self::CUSTOMER_KEY => $customerId,
                    'value'            => $value,
                ],
            ]);

            $this->info('✅  MeterEvent gesendet  →  ' . $event->id);
            return self::SUCCESS;

        } catch (\Throwable $e) {
            $this->error('❌  Stripe-Fehler: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    /**
     * Ermittelt zur subscription_item-ID die zugehörige stripe_customer_id.
     */
    private function resolveCustomerId(StripeClient $stripe, string $itemId): ?string
    {
        try {
            // A ▸ subscription_item abrufen (inkl. Subscription-Objekt)
            $item = $stripe->subscriptionItems->retrieve(
                $itemId,
                ['expand' => ['subscription']]   // nur 'subscription' expanden
            );

            // B ▸ Falls expand geklappt hat: Customer direkt auslesen
            if (!empty($item->subscription->customer)) {
                return $item->subscription->customer;
            }

            // C ▸ Fallback: separate Abfrage über die Subscription-ID
            $subscriptionId = $item->subscription;
            if ($subscriptionId) {
                $subscription = $stripe->subscriptions->retrieve($subscriptionId);
                return $subscription->customer ?? null;
            }

        } catch (\Throwable $e) {
            $this->error('⚠️  Kunde konnte nicht ermittelt werden: ' . $e->getMessage());
        }

        return null;
    }
}
