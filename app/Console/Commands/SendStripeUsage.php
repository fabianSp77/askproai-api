<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Stripe\Stripe;
use Stripe\SubscriptionItem;

class SendStripeUsage extends Command
{
    protected $signature = 'stripe:usage
                            {subscription_item : z. B. si_********}
                            {quantity : Nutzungseinheiten (Sek./Min.)}
                            {--timestamp= : Unix-Timestamp (Default: jetzt)}';

    protected $description = 'Überträgt einen Usage-Record an Stripe (Metered Billing)';

    public function handle(): int
    {
        /* 1) API-Key ermitteln ------------------------------------------------ */
        $secret = config('services.stripe.secret') ?: env('STRIPE_SECRET');
        if (! $secret) {
            $this->error('❌  STRIPE_SECRET fehlt – .env prüfen!');
            return self::FAILURE;
        }
        Stripe::setApiKey($secret);

        /* 2) Argumente einlesen --------------------------------------------- */
        $itemId    = $this->argument('subscription_item');
        $quantity  = (int) $this->argument('quantity');
        $timestamp = $this->option('timestamp') ?: time();

        /* 3) UsageRecord erzeugen ------------------------------------------- */
        try {
            $record = SubscriptionItem::createUsageRecord([
                'subscription_item' => $itemId,
                'quantity'          => $quantity,
                'timestamp'         => $timestamp,
                'action'            => 'increment',
            ]);

            $this->info('✅  UsageRecord gesendet →  ' . $record->id);
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('❌  Stripe-Fehler: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
