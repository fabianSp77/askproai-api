<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Stripe\Stripe;
use Stripe\SubscriptionItem;

class SendStripeUsage extends Command
{
    protected $signature = 'stripe:usage
                            {subscription_item : z.B. si_******** }
                            {quantity          : Nutzungseinheiten (Sekunden / Minuten)}
                            {--timestamp=      : Unix-Timestamp (Default: jetzt)}';

    protected $description = 'Überträgt einzelne Usage-Records an Stripe';

    public function handle(): int
    {
        /** 1) API-Key laden */
        $secret = config('services.stripe.secret');
        if (! $secret) {
            $this->error('❌  STRIPE_SECRET fehlt – .env prüfen!');
            return self::FAILURE;
        }
        Stripe::setApiKey($secret);

        /** 2) Parameter einlesen */
        $itemId    = $this->argument('subscription_item');
        $quantity  = (int) $this->argument('quantity');
        $timestamp = $this->option('timestamp') ?: time();

        try {
            /** 3) Usage-Record anlegen (Stripe API) */
            $record = SubscriptionItem::createUsageRecord($itemId, [
                'quantity'  => $quantity,
                'timestamp' => $timestamp,
                'action'    => 'increment',
            ]);

            $this->info('✅  UsageRecord gesendet  →  ' . $record->id);
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('❌  Stripe-Fehler: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
