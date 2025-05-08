<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Stripe\StripeClient;

class SendStripeMeterEvent extends Command
{
    protected $signature = 'stripe:meter
                            {subscription_item : z.B. si_SGyycOxUig4SRf }
                            {quantity          : Nutzungseinheiten (Sekunden / Minuten)}
                            {--timestamp=      : Unix-Timestamp (Default: jetzt)}';

    protected $description = 'Sendet ein Meter Event an Stripe (neues Usage-Based-Billing)';

    public function handle(): int
    {
        $secret = config('services.stripe.secret') ?: env('STRIPE_SECRET');
        if (! $secret) {
            $this->error('❌  STRIPE_SECRET fehlt – .env prüfen!');
            return self::FAILURE;
        }

        $stripe = new StripeClient($secret);

        $itemId    = $this->argument('subscription_item');
        $quantity  = (int) $this->argument('quantity');
        $timestamp = $this->option('timestamp') ?: time();

        try {
            // Das PHP-SDK hat (Stand Mai 2025) noch keine Convenience-Klasse,
            // daher roher Request:
            $resp = $stripe->request('post', '/v1/billing/meter_events', [
                'subscription_item' => $itemId,
                'quantity'          => $quantity,
                'timestamp'         => $timestamp,
            ]);

            $this->info('✅  MeterEvent gesendet  →  ' . $resp->json()['id']);
            return self::SUCCESS;

        } catch (\Throwable $e) {
            $this->error('❌  Stripe-Fehler: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
