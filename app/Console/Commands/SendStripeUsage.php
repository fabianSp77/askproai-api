<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Stripe\Stripe;
use Stripe\UsageRecord;

class SendStripeUsage extends Command
{
    protected $signature = 'stripe:usage
                            {subscription_item : z. B. si_********}
                            {quantity : Nutzungseinheiten – Sekunden oder Minuten}
                            {--timestamp= : Unix-Timestamp (Default: jetzt)}';

    protected $description = 'Überträgt einzelne Usage-Records an Stripe';

    public function handle(): int
    {
        // 1) API-Key setzen
        $secret = config('services.stripe.secret') ?: env('STRIPE_SECRET');
        if (! $secret) {
            $this->error('❌  STRIPE_SECRET fehlt – .env prüfen!');
            return self::FAILURE;
        }
        Stripe::setApiKey($secret);

        // 2) Parameter einlesen
        $item      = $this->argument('subscription_item');
        $quantity  = (int) $this->argument('quantity');
        $timestamp = $this->option('timestamp') ?: time();

        try {
            // 3) UsageRecord erstellen
            $record = UsageRecord::create([
                'subscription_item' => $item,
                'quantity'          => $quantity,
                'timestamp'         => $timestamp,
                'action'            => 'increment',
            ]);

            $this->info('✅  UsageRecord sent  →  ' . $record->id);
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('❌  Stripe-Fehler: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
